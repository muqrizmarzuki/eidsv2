<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use App\Models\WeightageArchitecturalElement;
use App\Models\WeightageOverall;
use App\Services\ScoringService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private ScoringService $scoring)
    {
    }

    public function dashboard()
    {
        $visible = Project::visibleTo(auth()->user());

        $statusCounts = $visible->clone()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');
        $total     = $statusCounts->sum();
        $active    = $statusCounts->get('dalam_pemeriksaan', 0);
        $completed = $statusCounts->get('selesai', 0);
        $draft     = $statusCounts->get('draf', 0);

        $recent          = $visible->clone()->with(['creator', 'assignedInspector'])->latest()->take(8)->get();
        $avgScore        = $visible->clone()->where('overall_score', '>', 0)->avg('overall_score') ?? 0;

        $defectCounts    = Defect::visibleTo(auth()->user())
            ->selectRaw("count(case when status in ('OPEN','IN_PROGRESS','PENDING_VERIFICATION') then 1 end) as open_c")
            ->selectRaw("count(case when status = 'RESOLVED' then 1 end) as resolved_c")
            ->first();
        $openDefects     = $defectCounts->open_c ?? 0;
        $resolvedDefects = $defectCounts->resolved_c ?? 0;

        $ratingBaik      = (float) setting('rating_baik', 85);
        $ratingMod       = (float) setting('rating_sederhana', 70);
        $meScore         = (float) WeightageOverall::forCategory('A')->me_pct;

        $actionRequired = collect();
        if (in_array(auth()->user()->role, ['admin', 'inspector'])) {
            $actionRequired = $visible->clone()
                ->with(['assignedInspector', 'samples', 'defects'])
                ->withCount('assessments')
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
        $inspectors = User::whereIn('role', ['admin', 'inspector'])->orderBy('name')->get();

        return view('projects.index', compact('projects', 'inspectors'));
    }

    public function show(Project $project)
    {
        $this->guardProjectVisible($project);

        $project->load(['samples', 'defects', 'creator', 'assignedInspector', 'assessments']);
        $openDefects     = $project->defects->whereIn('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION'])->count();
        $resolvedDefects = $project->defects->where('status', 'RESOLVED')->count();

        return view('projects.show', compact('project', 'openDefects', 'resolvedDefects'));
    }

    public function create()
    {
        $inspectors  = User::whereIn('role', ['admin', 'inspector'])->orderBy('name')->get();
        $contractors = User::where('role', 'contractor')->orderBy('name')->get();
        return view('projects.create', compact('inspectors', 'contractors'));
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
            'building_category' => 'required|in:A,B,C,D',
            'car_park_present'    => 'nullable|boolean',
            'apron_drain_present' => 'nullable|boolean',
            'total_units'     => 'required|integer|min:1',
            'floor_area_sqm'  => 'required|numeric|min:1',
            'status'          => 'required|in:draf,dalam_pemeriksaan',
            'assigned_to'     => 'nullable|exists:users,id',
            'assigned_contractor_id' => 'nullable|exists:users,id',
        ]);

        $data['car_park_present']    = $request->boolean('car_park_present', true);
        $data['apron_drain_present'] = $request->boolean('apron_drain_present', true);

        if (!auth()->user()->isAdmin()) {
            // Inspector may still choose the Assigned Contractor; the Assigned Inspector
            // field remains Admin-only.
            // Only Admin may choose who a project is assigned to. A non-privileged
            // creator (Inspector) is always assigned to themselves — their own
            // visibility is scoped strictly to assigned_to, so anything else would
            // lock them out of the project they just created.
            $data['assigned_to'] = auth()->id();
        }

        $data['calculated_samples'] = $this->scoring->sampleCount($data['building_category'], (float) $data['floor_area_sqm']);
        $data['created_by']         = auth()->id();

        $project = Project::create($data);

        // Auto-generate sample slots, distributed across the project's units
        // per CIS 7:2021 §1.7 ("distributed as uniformly as possible throughout
        // the project") rather than confined to a single representative unit.
        $locations = json_decode(setting('default_locations', '[]'), true) ?: config('eids.default_locations');
        $plan      = ProjectSample::distributeAcrossUnits($project->calculated_samples, $locations, $project->total_units);
        $rows      = [];
        foreach ($plan as $i => $entry) {
            $rows[] = [
                'project_id'     => $project->id,
                'unit_reference' => $entry['unit_reference'],
                'sample_index'   => $i + 1,
                'location_name'  => $entry['location_name'],
                'location_type'  => ProjectSample::guessLocationType($entry['location_name']),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }
        ProjectSample::insert($rows);

        return redirect()->route('projects.samples', $project)
            ->with('success', "Project '{$project->project_name}' created. {$project->calculated_samples} sample(s) generated across {$project->total_units} unit(s).");
    }

    public function edit(Project $project)
    {
        $this->guardProjectVisible($project);

        $inspectors  = User::whereIn('role', ['admin', 'inspector'])->orderBy('name')->get();
        $contractors = User::where('role', 'contractor')->orderBy('name')->get();
        return view('projects.edit', compact('project', 'inspectors', 'contractors'));
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
            'building_category' => 'required|in:A,B,C,D',
            'total_units'     => 'required|integer|min:1',
            'floor_area_sqm'  => 'required|numeric|min:1',
            'status'          => 'required|in:draf,dalam_pemeriksaan,selesai',
            'assigned_to'     => 'nullable|exists:users,id',
            'assigned_contractor_id' => 'nullable|exists:users,id',
        ]);
        // car_park_present / apron_drain_present are managed on the Sample Setup
        // page (storeSamples()), not this form — leave them untouched here.

        if ($data['status'] === 'selesai' && $project->status !== 'selesai') {
            // Completing a project is only allowed through the explicit "Mark as Completed"
            // action (see markComplete()), which enforces inspection-done + defects-resolved.
            // This form's status field can only move a project between Draft and In Inspection.
            $data['status'] = $project->status;
        }

        if (!auth()->user()->isAdmin()) {
            // Inspector may still change the Assigned Contractor; the Assigned Inspector
            // field remains Admin-only. Only Admin may reassign who the project belongs
            // to — leave the existing assigned_to untouched for anyone else editing it.
            unset($data['assigned_to']);
        }

        $data['calculated_samples'] = $this->scoring->sampleCount($data['building_category'], (float) $data['floor_area_sqm']);
        $project->update($data);

        $added = $this->growSamplesIfNeeded($project);

        $this->scoring->recalculateAndSave($project);

        $message = 'Project updated successfully.';
        if ($added > 0) {
            $message .= " {$added} additional sample unit(s) generated to match the recalculated target of {$project->calculated_samples}.";
        }

        return redirect()->route('projects.show', $project)
            ->with('success', $message);
    }

    /**
     * If a GFA/category edit raised calculated_samples above the project's
     * current sample count, generate the extra samples continuing the same
     * unit-distribution plan — never deletes or renumbers existing samples,
     * so in-progress inspections are untouched.
     */
    private function growSamplesIfNeeded(Project $project): int
    {
        $existing = $project->samples()->count();
        $target   = $project->calculated_samples;
        if ($target <= $existing) return 0;

        $locations = json_decode(setting('default_locations', '[]'), true) ?: config('eids.default_locations');
        $fullPlan  = ProjectSample::distributeAcrossUnits($target, $locations, $project->total_units);
        $newSlice  = array_slice($fullPlan, $existing);

        $rows = [];
        foreach ($newSlice as $i => $entry) {
            $rows[] = [
                'project_id'     => $project->id,
                'unit_reference' => $entry['unit_reference'],
                'sample_index'   => $existing + $i + 1,
                'location_name'  => $entry['location_name'],
                'location_type'  => ProjectSample::guessLocationType($entry['location_name']),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }
        ProjectSample::insert($rows);

        return count($rows);
    }

    public function markComplete(Project $project)
    {
        $this->guardProjectVisible($project);
        abort_unless(auth()->user()->canInspect(), 403);

        if ($project->status === 'selesai') {
            return redirect()->route('projects.show', $project);
        }

        $project->update(['status' => 'selesai']);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project marked as Completed. The official certificate is now available.');
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
        $rule             = \App\Models\SamplingRule::forCategory($project->building_category);
        $divisor          = (float) $rule->gfa_divisor;
        $minSamples       = $rule->min_samples;
        $maxSamples       = $rule->max_samples;
        $components       = WeightageArchitecturalElement::ordered();
        $defaultLocations = json_decode(setting('default_locations', '[]'), true) ?: config('eids.default_locations');

        return view('projects.samples', compact('project', 'divisor', 'minSamples', 'maxSamples', 'components', 'defaultLocations'));
    }

    public function storeSamples(Request $request, Project $project)
    {
        $this->guardProjectVisible($project);

        $data = $request->validate([
            'locations'            => 'required|array',
            'locations.*'          => 'required|string|max:255',
            'location_types'       => 'nullable|array',
            'location_types.*'     => 'nullable|in:principal,service,circulation',
            'car_park_present'     => 'nullable|boolean',
            'apron_drain_present'  => 'nullable|boolean',
        ]);

        foreach ($data['locations'] as $id => $name) {
            ProjectSample::where('id', $id)->where('project_id', $project->id)
                ->update([
                    'location_name' => $name,
                    'location_type' => $data['location_types'][$id] ?? ProjectSample::guessLocationType($name),
                ]);
        }

        $project->update([
            'car_park_present'    => $request->boolean('car_park_present', true),
            'apron_drain_present' => $request->boolean('apron_drain_present', true),
        ]);
        $this->scoring->recalculateAndSave($project);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project setup saved. The assigned inspector can now begin the component inspection.');
    }
}
