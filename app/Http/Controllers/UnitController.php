<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Project $project)
    {
        $this->guardProjectVisible($project);

        $units = $project->units()->withCount('defects')->orderBy('unit_reference')->get();

        return view('units.index', compact('project', 'units'));
    }

    public function create(Project $project)
    {
        $this->guardProjectVisible($project);

        return view('units.create', compact('project'));
    }

    public function store(Request $request, Project $project)
    {
        $this->guardProjectVisible($project);

        $data = $this->validated($request, $project);
        $project->units()->create($data);

        return redirect()->route('projects.units.index', $project)
            ->with('success', 'House added.');
    }

    public function edit(Unit $unit)
    {
        $this->guardProjectVisible($unit->project);

        return view('units.edit', ['project' => $unit->project, 'unit' => $unit]);
    }

    public function update(Request $request, Unit $unit)
    {
        $this->guardProjectVisible($unit->project);

        $unit->update($this->validated($request, $unit->project, $unit));

        return redirect()->route('projects.units.index', $unit->project)
            ->with('success', 'House updated.');
    }

    public function destroy(Unit $unit)
    {
        $this->guardProjectVisible($unit->project);

        $project = $unit->project;
        $unit->delete();

        return redirect()->route('projects.units.index', $project)
            ->with('success', 'House removed.');
    }

    private function validated(Request $request, Project $project, ?Unit $unit = null): array
    {
        return $request->validate([
            'unit_reference' => [
                'required', 'string', 'max:255',
                'unique:units,unit_reference,' . ($unit?->id ?? 'NULL') . ',id,project_id,' . $project->id,
            ],
            'owner_name'              => 'nullable|string|max:255',
            'house_type'              => 'nullable|string|max:255',
            'owner_phone'             => 'nullable|string|max:50',
            'owner_address'           => 'nullable|string|max:500',
            'report_date'             => 'nullable|date',
            'rectification_deadline'  => 'nullable|date',
            'handover_date'           => 'nullable|date',
        ]);
    }
}
