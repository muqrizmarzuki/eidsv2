<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function dashboard()
    {
        $total           = Project::count();
        $active          = Project::where('status', 'dalam_pemeriksaan')->count();
        $completed       = Project::where('status', 'selesai')->count();
        $draft           = Project::where('status', 'draf')->count();
        $recent          = Project::latest()->take(8)->get();
        $avgScore        = Project::where('overall_score', '>', 0)->avg('overall_score') ?? 0;
        $openDefects     = Defect::where('status', 'OPEN')->count();
        $resolvedDefects = Defect::where('status', 'RESOLVED')->count();
        $ratingBaik      = (float) setting('rating_baik', 85);
        $ratingMod       = (float) setting('rating_sederhana', 70);
        $meScore         = (float) setting('me_score', 2.0);

        return view('dashboard', compact(
            'total', 'active', 'completed', 'draft',
            'recent', 'avgScore', 'openDefects', 'resolvedDefects',
            'ratingBaik', 'ratingMod', 'meScore'
        ));
    }

    public function index(Request $request)
    {
        $query = Project::with('creator')->latest();

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

        $projects = $query->paginate(15)->withQueryString();

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        $project->load(['samples', 'defects', 'creator']);
        $openDefects     = $project->defects->where('status', 'OPEN')->count();
        $resolvedDefects = $project->defects->where('status', 'RESOLVED')->count();

        return view('projects.show', compact('project', 'openDefects', 'resolvedDefects'));
    }

    public function create()
    {
        return view('projects.create');
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
        ]);

        $data['calculated_samples'] = max(1, (int) ceil($data['floor_area_sqm'] / (float) setting('sample_divisor', 60)));
        $data['created_by']         = auth()->id();

        $project = Project::create($data);

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
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
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
        ]);

        $project->update($data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $name = $project->project_name;
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Project '{$name}' deleted.");
    }

    public function samples(Project $project)
    {
        $project->load('samples');
        $divisor          = (float) setting('sample_divisor', 60);
        $components       = config('eids.components');
        $defaultLocations = json_decode(setting('default_locations', '[]'), true) ?: config('eids.default_locations');

        return view('projects.samples', compact('project', 'divisor', 'components', 'defaultLocations'));
    }

    public function storeSamples(Request $request, Project $project)
    {
        $data = $request->validate([
            'locations'   => 'required|array',
            'locations.*' => 'required|string|max:255',
        ]);

        foreach ($data['locations'] as $id => $name) {
            ProjectSample::where('id', $id)->where('project_id', $project->id)
                ->update(['location_name' => $name]);
        }

        return redirect()->route('projects.components', $project)
            ->with('success', 'Sample locations saved. Select components to inspect.');
    }
}
