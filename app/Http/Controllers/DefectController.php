<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Project;
use Illuminate\Http\Request;

class DefectController extends Controller
{
    public function index(Request $request)
    {
        $query = Defect::with(['project', 'media'])->latest();

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('component_name', 'like', "%$s%")
                  ->orWhere('location', 'like', "%$s%")
                  ->orWhere('defect_description', 'like', "%$s%");
            });
        }

        $defects  = $query->paginate(20)->withQueryString();
        $projects = Project::orderBy('project_name')->get(['id', 'project_name', 'project_no']);

        return view('defects.index', compact('defects', 'projects'));
    }

    public function create()
    {
        $projects = Project::orderBy('project_name')->get(['id', 'project_name', 'project_no']);
        return view('defects.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id'         => 'required|exists:projects,id',
            'component_name'     => 'required|string|max:255',
            'location'           => 'required|string|max:255',
            'defect_description' => 'required|string',
            'severity'           => 'required|in:low,medium,high',
            'status'             => 'required|in:OPEN,IN_PROGRESS,RESOLVED',
            'photo'              => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('defect_photos', 'public');
        }

        $defect = Defect::create($data);

        if ($request->hasFile('photo')) {
            $defect->addMediaFromRequest('photo')->toMediaCollection('photos');
        }

        return redirect()->route('defects.index')
            ->with('success', 'Defect recorded successfully.');
    }

    public function edit(Defect $defect)
    {
        $projects = Project::orderBy('project_name')->get(['id', 'project_name', 'project_no']);
        return view('defects.edit', compact('defect', 'projects'));
    }

    public function update(Request $request, Defect $defect)
    {
        $data = $request->validate([
            'project_id'         => 'required|exists:projects,id',
            'component_name'     => 'required|string|max:255',
            'location'           => 'required|string|max:255',
            'defect_description' => 'required|string',
            'severity'           => 'required|in:low,medium,high',
            'status'             => 'required|in:OPEN,IN_PROGRESS,RESOLVED',
            'photo'              => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('defect_photos', 'public');
        }

        $defect->update($data);

        if ($request->hasFile('photo')) {
            $defect->addMediaFromRequest('photo')->toMediaCollection('photos');
        }

        return redirect()->route('defects.index')
            ->with('success', 'Defect updated successfully.');
    }

    public function destroy(Defect $defect)
    {
        $defect->delete();

        return redirect()->route('defects.index')
            ->with('success', 'Defect deleted.');
    }

    public function toggleStatus(Defect $defect)
    {
        $next = match($defect->status) {
            'OPEN'        => 'IN_PROGRESS',
            'IN_PROGRESS' => 'RESOLVED',
            'RESOLVED'    => 'OPEN',
            default       => 'OPEN',
        };
        $defect->update(['status' => $next]);

        return redirect()->back()
            ->with('success', "Defect status updated to {$next}.");
    }
}
