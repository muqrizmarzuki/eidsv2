<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesChecklistAnswers;
use App\Models\ArchExternalSample;
use App\Models\ChecklistItem;
use App\Models\ComponentAssessment;
use App\Models\Project;
use App\Models\WeightageArchitecturalElement;
use App\Services\ScoringService;
use Illuminate\Http\Request;

/**
 * Table 3's "External finishes" architectural components (Roof, External
 * Wall, Apron & Perimeter Drain, Car Park) — sampled as building-level
 * sections/lengths, never tied to a specific room. See ArchExternalSample.
 */
class BuildingExternalController extends Controller
{
    use HandlesChecklistAnswers;

    public function __construct(private ScoringService $scoring)
    {
    }

    public function elements(Project $project)
    {
        $this->guardProjectVisible($project);

        $registry = WeightageArchitecturalElement::ordered();
        $codes    = $project->buildingBasedComponentCodes();

        foreach ($codes as $code) {
            $this->ensureSamplesGenerated($project, $code);
        }

        $project->load('archExternalSamples.assessments');
        $elements = collect($codes)->map(fn ($code) => [
            'el'      => $registry[$code],
            'samples' => $project->archExternalSamples->where('component_code', $code),
        ]);

        return view('projects.building-external', compact('project', 'elements'));
    }

    /**
     * Generates the sample set for one building-scope component the first
     * time it's visited — existing samples/assessments are left untouched on
     * subsequent visits (e.g. after a unit-count edit changes the target).
     */
    private function ensureSamplesGenerated(Project $project, string $code): void
    {
        $existing = $project->archExternalSamples()->where('component_code', $code)->count();
        $target   = $project->buildingSampleCountFor($code);
        if ($existing >= $target) return;

        $el   = WeightageArchitecturalElement::where('component_code', $code)->firstOrFail();
        $rows = [];
        for ($i = $existing; $i < $target; $i++) {
            $rows[] = [
                'project_id'     => $project->id,
                'component_code' => $code,
                'sample_index'   => $i + 1,
                'label'          => "{$el->name} - Section " . ($i + 1),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }
        ArchExternalSample::insert($rows);
    }

    public function inspect(Project $project, ArchExternalSample $sample)
    {
        $this->guardProjectVisible($project);

        abort_if($sample->project_id !== $project->id, 404);

        $componentCode = $sample->component_code;
        $el            = WeightageArchitecturalElement::where('component_code', $componentCode)->firstOrFail();
        $component     = ['name' => $el->name, 'weightage' => (float) $el->breakdown_pct];

        $assessment = $sample->assessments->first();
        $items      = ChecklistItem::forComponent($componentCode)->get();
        $answers    = $assessment ? $assessment->answers->keyBy('checklist_item_id') : collect();

        $gridUrl  = route('projects.building-external', $project);
        $storeUrl = route('projects.building-external.inspect.store', [$project, $sample]);

        $prevCode = $nextCode = null;
        $prevUrl  = null;
        $componentPos = $componentCount = 1;

        return view('projects.inspect', compact(
            'project', 'sample', 'componentCode', 'component',
            'assessment', 'items', 'answers',
            'prevCode', 'nextCode', 'componentPos', 'componentCount',
            'gridUrl', 'storeUrl', 'prevUrl'
        ));
    }

    public function storeAssessment(Request $request, Project $project, ArchExternalSample $sample)
    {
        $this->guardProjectVisible($project);

        abort_if($sample->project_id !== $project->id, 404);

        $componentCode = $sample->component_code;
        $el            = WeightageArchitecturalElement::where('component_code', $componentCode)->firstOrFail();
        $items         = ChecklistItem::forComponent($componentCode)->get();

        $existing = ComponentAssessment::where([
            'project_id'     => $project->id,
            'arch_sample_id' => $sample->id,
            'component_code' => $componentCode,
        ])->first();

        $data = $request->validate([
            'answers' => 'required|array',
            'remarks' => 'nullable|string|max:1000',
        ] + $this->photoRules($existing), $this->photoMessages());

        $photos = $request->file('photos', []);

        $payload = ['remarks' => $data['remarks'] ?? null];

        if ($existing) {
            $existing->update($payload);
            $assessment = $existing;
        } else {
            $assessment = ComponentAssessment::create(array_merge($payload, [
                'project_id'     => $project->id,
                'arch_sample_id' => $sample->id,
                'component_code' => $componentCode,
            ]));
        }

        [$overallStatus, $failCount] = $this->saveAnswers($assessment, $items, $data['answers']);
        $assessment->update(['overall_sample_status' => $overallStatus]);

        if ($photos) {
            $this->attachPhotos($photos, $assessment);
        }

        $this->syncDefect(
            $assessment, $overallStatus, $failCount, $project->id, $componentCode,
            $el->name, $sample->label, $data['remarks'] ?? null, $photos
        );

        $this->scoring->recalculateAndSave($project);

        return redirect()
            ->route('projects.building-external', $project)
            ->with('success', "{$sample->label} saved.");
    }
}
