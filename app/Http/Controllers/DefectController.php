<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Project;
use Illuminate\Http\Request;

class DefectController extends Controller
{
    public function index(Request $request)
    {
        $query = Defect::visibleTo(auth()->user())->with(['project', 'media'])->latest();

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
        $projects = Project::visibleTo(auth()->user())->orderBy('project_name')->get(['id', 'project_name', 'project_no']);

        return view('defects.index', compact('defects', 'projects'));
    }

    public function create()
    {
        $projects = Project::visibleTo(auth()->user())->orderBy('project_name')->get(['id', 'project_name', 'project_no']);
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
            'status'             => 'required|in:OPEN,IN_PROGRESS,PENDING_VERIFICATION,RESOLVED',
            'photo'              => 'nullable|image|max:5120',
        ]);

        $this->guardProjectVisible(Project::findOrFail($data['project_id']));

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
        $this->guardDefectVisible($defect);

        $projects = Project::visibleTo(auth()->user())->orderBy('project_name')->get(['id', 'project_name', 'project_no']);
        return view('defects.edit', compact('defect', 'projects'));
    }

    public function update(Request $request, Defect $defect)
    {
        $this->guardDefectVisible($defect);

        $data = $request->validate([
            'project_id'         => 'required|exists:projects,id',
            'component_name'     => 'required|string|max:255',
            'location'           => 'required|string|max:255',
            'defect_description' => 'required|string',
            'severity'           => 'required|in:low,medium,high',
            'photo'              => 'nullable|image|max:5120',
        ]);

        $this->guardProjectVisible(Project::findOrFail($data['project_id']));

        // Status transitions are owned exclusively by advanceStatus() and its
        // TRANSITIONS map — the edit form must never move a defect's status.
        unset($data['status']);

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
        $this->guardDefectVisible($defect);

        $defect->delete();

        return redirect()->route('defects.index')
            ->with('success', 'Defect deleted.');
    }

    private const TRANSITIONS = [
        'OPEN'                 => ['IN_PROGRESS'],
        'IN_PROGRESS'          => ['PENDING_VERIFICATION'],
        'PENDING_VERIFICATION' => ['RESOLVED', 'IN_PROGRESS'],
        'RESOLVED'             => ['OPEN'],
    ];

    private const CONTRACTOR_ALLOWED_EDGES = [
        'OPEN->IN_PROGRESS',
        'IN_PROGRESS->PENDING_VERIFICATION',
    ];

    public function advanceStatus(Request $request, Defect $defect)
    {
        $this->guardDefectVisible($defect);

        $data = $request->validate([
            'to' => 'required|in:OPEN,IN_PROGRESS,PENDING_VERIFICATION,RESOLVED',
        ]);

        $allowedTargets = self::TRANSITIONS[$defect->status] ?? [];
        abort_unless(in_array($data['to'], $allowedTargets, true), 422, 'Invalid status transition.');

        if (auth()->user()->role === 'contractor') {
            $edge = "{$defect->status}->{$data['to']}";
            abort_unless(in_array($edge, self::CONTRACTOR_ALLOWED_EDGES, true), 403, 'Contractors cannot perform this transition.');
        }

        $defect->update(['status' => $data['to']]);

        return redirect()->back()->with('success', "Defect status updated to {$data['to']}.");
    }
}
