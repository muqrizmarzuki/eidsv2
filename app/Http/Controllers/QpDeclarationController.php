<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\QpDeclaration;
use App\Services\ScoringService;
use Illuminate\Http\Request;

class QpDeclarationController extends Controller
{
    public function __construct(private ScoringService $scoring)
    {
    }

    public function update(Request $request, Project $project)
    {
        $this->guardProjectVisible($project);

        $data = $request->validate([
            'item_code' => 'required|in:QP_SKIM_COAT,QP_WATER_TIGHTNESS',
            'declared'  => 'nullable|boolean',
            'evidence'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $declaration = QpDeclaration::firstOrNew([
            'project_id' => $project->id,
            'item_code'  => $data['item_code'],
        ]);

        $declared = $request->boolean('declared');
        $declaration->declared = $declared;

        if ($request->hasFile('evidence')) {
            $declaration->evidence_path = $request->file('evidence')->store("qp-declarations/{$project->id}", 'public');
        }

        $declaration->declared_at = $declared ? now() : null;
        $declaration->save();

        $this->scoring->recalculateAndSave($project);

        return redirect()->route('projects.show', $project)
            ->with('success', 'QP declaration updated.');
    }
}
