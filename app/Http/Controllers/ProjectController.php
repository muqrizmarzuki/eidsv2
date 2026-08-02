<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function dashboard()
    {
        $visible         = Project::visibleTo(auth()->user());
        $total           = $visible->clone()->count();
        $active          = $visible->clone()->where('status', 'dalam_pemeriksaan')->count();
        $completed       = $visible->clone()->where('status', 'selesai')->count();
        $draft           = $visible->clone()->where('status', 'draf')->count();
        $recent          = $visible->clone()->with(['creator', 'assignedInspector'])->latest()->take(8)->get();
        $avgScore        = $visible->clone()->where('overall_score', '>', 0)->avg('overall_score') ?? 0;
        $openDefects     = Defect::visibleTo(auth()->user())->whereIn('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION'])->count();
        $resolvedDefects = Defect::visibleTo(auth()->user())->where('status', 'RESOLVED')->count();
        $ratingBaik      = (float) setting('rating_baik', 85);
        $ratingMod       = (float) setting('rating_sederhana', 70);
        $meScore         = (float) setting('me_score', 2.0);

        $actionRequired = collect();
        if (in_array(auth()->user()->role, ['admin', 'lead_auditor', 'inspector'])) {
            $actionRequired = $visible->clone()
                ->with(['assignedInspector'])
                ->get()
                ->filter(function ($project) {
                    $action = $project->nextActionFor(auth()->user());
                    return !str_contains($action['text'], 'complete') && $action['text'] !== '';
                })
                ->take(5);
        }

        return view('dashboard', compact(
            'total', 'active', 'completed', 'draft',
            'recent', 'avgScore', 'openDefects', 'resolvedDefects',
            'ratingBaik', 'ratingMod', 'meScore', 'actionRequired'
        ));
    }

    public function index(Request $request)
    {
        $query = Project::visibleTo(auth()->user())->with(['creator', 'assignedInspector'])->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('project_name', 'like', "%$s%")
                  ->orWhere('project_no', 'like', "%$s%")
                  ->orWhere('developer_name', 'like', "%$s%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('building_type', $request->type);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $projects = $query->paginate(15)->withQueryString();
        $inspectors = User::whereIn('role', ['admin', 'lead_auditor', 'inspector'])->orderBy('name')->get();

        return view('projects.index', compact('projects', 'inspectors'));
    }

    public function show(Project $project)
    {
        $this->guardProjectVisible($project);

        $project->load(['samples', 'defects', 'creator', 'assignedInspector']);
        $openDefects     = $project->defects->whereIn('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION'])->count();
        $resolvedDefects = $project->defects->where('status', 'RESOLVED')->count();

        return view('projects.show', compact('project', 'openDefects', 'resolvedDefects'));
    }

    public function create()
    {
        $inspectors  = User::whereIn('role', ['admin', 'lead_auditor', 'inspector'])->orderBy('name')->get();
        $contractors = User::where('role', 'contractor')->orderBy('name')->get();
        $supervisors = User::where('role', 'supervisor')->orderBy('name')->get();
        return view('projects.create', compact('inspectors', 'contractors', 'supervisors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_no'      => 'required|string|max:255|unique:projects,project_no',
            'project_name'    => 'required|string|max:255',
            'location'        => 'nullable|string|max:255',
            'developer_name'  => 'required|string|max:255',
            'contractor_name' => 'required|string|max:255',
            'building_type'   => 'required|in:teres,semi_d,banglo',
            'total_units'     => 'required|integer|min:1',
            'floor_area_sqm'  => 'required|numeric|min:1',
            'status'          => 'required|in:draf,dalam_pemeriksaan,selesai',
            'assigned_to'     => 'nullable|exists:users,id',
            'assigned_contractor_id' => 'nullable|exists:users,id',
            'supervisor_ids'         => 'nullable|array',
            'supervisor_ids.*'       => 'exists:users,id',
        ]);

        $supervisorIds = $data['supervisor_ids'] ?? [];
        unset($data['supervisor_ids']);

        if (!auth()->user()->isAdmin() && !auth()->user()->isLeadAuditor()) {
            unset($data['assigned_contractor_id']);
            $supervisorIds = [];
        }

        $data['calculated_samples'] = max(1, (int) ceil($data['floor_area_sqm'] / (float) setting('sample_divisor', 60)));
        $data['created_by']         = auth()->id();

        $project = Project::create($data);
        $project->supervisors()->sync($supervisorIds);

        // Auto-generate sample slots
        $locations = json_decode(setting('default_locations', '[]'), true) ?: config('eids.default_locations');
        for ($i = 0; $i < $project->calculated_samples; $i++) {
            ProjectSample::create([
                'project_id'    => $project->id,
                'sample_index'  => $i + 1,
                'location_name' => $locations[$i] ?? "Sample " . ($i + 1),
            ]);
        }

        return redirect()->route('projects.samples', $project)
            ->with('success', "Project '{$project->project_name}' created. {$project->calculated_samples} sample(s) generated.");
    }

    public function edit(Project $project)
    {
        $this->guardProjectVisible($project);

        $inspectors  = User::whereIn('role', ['admin', 'lead_auditor', 'inspector'])->orderBy('name')->get();
        $contractors = User::where('role', 'contractor')->orderBy('name')->get();
        $supervisors = User::where('role', 'supervisor')->orderBy('name')->get();
        return view('projects.edit', compact('project', 'inspectors', 'contractors', 'supervisors'));
    }

    public function update(Request $request, Project $project)
    {
        $this->guardProjectVisible($project);

        $data = $request->validate([
            'project_no'      => 'required|string|max:255|unique:projects,project_no,' . $project->id,
            'project_name'    => 'required|string|max:255',
            'location'        => 'nullable|string|max:255',
            'developer_name'  => 'required|string|max:255',
            'contractor_name' => 'required|string|max:255',
            'building_type'   => 'required|in:teres,semi_d,banglo',
            'total_units'     => 'required|integer|min:1',
            'floor_area_sqm'  => 'required|numeric|min:1',
            'status'          => 'required|in:draf,dalam_pemeriksaan,selesai',
            'assigned_to'     => 'nullable|exists:users,id',
            'assigned_contractor_id' => 'nullable|exists:users,id',
            'supervisor_ids'         => 'nullable|array',
            'supervisor_ids.*'       => 'exists:users,id',
        ]);

        $supervisorIds = $request->has('supervisors_submitted') ? ($data['supervisor_ids'] ?? []) : null;
        unset($data['supervisor_ids']);

        if (!auth()->user()->isAdmin() && !auth()->user()->isLeadAuditor()) {
            unset($data['assigned_contractor_id']);
            $supervisorIds = null;
        }

        $project->update($data);

        if ($supervisorIds !== null) {
            $project->supervisors()->sync($supervisorIds);
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $this->guardProjectVisible($project);

        $name = $project->project_name;
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Project '{$name}' deleted.");
    }

    public function samples(Project $project)
    {
        $this->guardProjectVisible($project);

        $project->load('samples');
        $divisor          = (float) setting('sample_divisor', 60);
        $components       = config('eids.components');
        $defaultLocations = json_decode(setting('default_locations', '[]'), true) ?: config('eids.default_locations');

        return view('projects.samples', compact('project', 'divisor', 'components', 'defaultLocations'));
    }

    public function storeSamples(Request $request, Project $project)
    {
        $this->guardProjectVisible($project);

        $data = $request->validate([
            'locations'   => 'required|array',
            'locations.*' => 'required|string|max:255',
        ]);

        foreach ($data['locations'] as $id => $name) {
            ProjectSample::where('id', $id)->where('project_id', $project->id)
                ->update(['location_name' => $name]);
        }

        $user = auth()->user();

        if ($user->isAdmin() || $user->isLeadAuditor()) {
            return redirect()->route('projects.show', $project)
                ->with('success', 'Sample locations saved. The assigned inspector can now begin the component inspection.');
        }

        return redirect()->route('projects.components', $project)
            ->with('success', 'Sample locations saved. Select components to inspect.');
    }
}
