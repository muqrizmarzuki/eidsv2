<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesChecklistAnswers;
use App\Models\ChecklistItem;
use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\ExternalElement;
use App\Models\ExternalSample;
use App\Models\Project;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExternalInspectionController extends Controller
{
    use HandlesChecklistAnswers;

    public function __construct(private ScoringService $scoring)
    {
    }

    public function elements(Project $project)
    {
        $this->guardProjectVisible($project);

        $project->load('externalSamples.assessments');
        $registry = ExternalElement::ordered();

        $elements = $registry->map(fn ($el) => [
            'el'       => $el,
            'quantity' => $project->externalElementQuantity($el->element_code),
            'samples'  => $project->externalSamples->where('element_code', $el->element_code)
                ->sortBy(['instance_index', 'sample_index']),
        ]);

        return view('projects.external', compact('project', 'elements'));
    }

    private function instanceLabel(ExternalElement $element, int $instance, int $totalInstances, int $section): string
    {
        if ($totalInstances > 1) {
            return $element->sample_count > 1
                ? "{$element->name} {$instance} - Section {$section}"
                : "{$element->name} {$instance}";
        }
        return $element->sample_count > 1 ? "{$element->name} #{$section}" : $element->name;
    }

    /**
     * Relabels every remaining instance of an element after an add/remove so
     * labels always reflect "N of current total" (e.g. adding a 3rd playground
     * renames "Playground" back to "Playground 1"/"Playground 2"/"Playground 3").
     */
    private function relabelInstances(Project $project, string $elementCode, ExternalElement $element): int
    {
        $samples = $project->externalSamples()->where('element_code', $elementCode)
            ->orderBy('instance_index')->orderBy('sample_index')->get();
        $totalInstances = $samples->pluck('instance_index')->unique()->count();

        foreach ($samples as $sample) {
            $sample->update(['label' => $this->instanceLabel($element, $sample->instance_index, $totalInstances, $sample->sample_index)]);
        }

        return $totalInstances;
    }

    /**
     * Adds one more physical instance of an element (e.g. a 3rd playground),
     * generating its full Table 6 sample set.
     */
    public function addInstance(Project $project, string $elementCode)
    {
        $this->guardProjectVisible($project);

        $element        = ExternalElement::where('element_code', $elementCode)->firstOrFail();
        $nextInstance   = ($project->externalSamples()->where('element_code', $elementCode)->max('instance_index') ?? 0) + 1;

        $rows = [];
        for ($section = 1; $section <= $element->sample_count; $section++) {
            $rows[] = [
                'project_id'     => $project->id,
                'element_code'   => $elementCode,
                'instance_index' => $nextInstance,
                'sample_index'   => $section,
                'label'          => '', // relabelled below once the new total is known
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }
        ExternalSample::insert($rows);

        $total = $this->relabelInstances($project, $elementCode, $element);
        $project->externalElementSettings()->updateOrCreate(['element_code' => $elementCode], ['quantity' => $total]);

        $this->scoring->recalculateAndSave($project);

        return redirect()->route('projects.external', $project)
            ->with('success', "Added another {$element->name} instance.");
    }

    /**
     * Removes one physical instance of an element — deletes its sample
     * sections, their assessments/answers, and any defect raised against
     * them, then renumbers the remaining instances to stay contiguous.
     * Unlike other N/A handling in this app, this is a real delete: the
     * instance itself (e.g. "the 2nd playground") no longer exists on the
     * project, so there's nothing meaningful left to preserve.
     */
    public function removeInstance(Project $project, string $elementCode, int $instance)
    {
        $this->guardProjectVisible($project);

        $element = ExternalElement::where('element_code', $elementCode)->firstOrFail();

        DB::transaction(function () use ($project, $elementCode, $instance) {
            $samples = $project->externalSamples()->where('element_code', $elementCode)
                ->where('instance_index', $instance)->get();

            $assessmentIds = ComponentAssessment::whereIn('external_sample_id', $samples->pluck('id'))->pluck('id');
            Defect::whereIn('assessment_id', $assessmentIds)->get()->each->delete(); // model delete, not bulk, so media attachments are cleaned up too
            ComponentAssessment::whereIn('id', $assessmentIds)->get()->each->delete(); // cascades to assessment_answers, clears media
            ExternalSample::whereIn('id', $samples->pluck('id'))->delete();

            // Shift every later instance down by one so numbering stays contiguous.
            $project->externalSamples()->where('element_code', $elementCode)
                ->where('instance_index', '>', $instance)
                ->orderBy('instance_index')
                ->each(fn ($s) => $s->update(['instance_index' => $s->instance_index - 1]));
        });

        $total = $this->relabelInstances($project, $elementCode, $element);
        $project->externalElementSettings()->updateOrCreate(['element_code' => $elementCode], ['quantity' => $total]);

        $this->scoring->recalculateAndSave($project);

        return redirect()->route('projects.external', $project)
            ->with('success', "Removed a {$element->name} instance.");
    }

    public function inspect(Project $project, ExternalSample $sample)
    {
        $this->guardProjectVisible($project);

        abort_if($sample->project_id !== $project->id, 404);

        $componentCode = $sample->element_code;
        $element       = ExternalElement::where('element_code', $componentCode)->firstOrFail();
        $component     = ['name' => $element->name, 'weightage' => null];

        $assessment = $sample->assessments->first();
        $items      = ChecklistItem::forComponent($componentCode)->get();
        $answers    = $assessment ? $assessment->answers->keyBy('checklist_item_id') : collect();

        $gridUrl  = route('projects.external', $project);
        $storeUrl = route('projects.external.inspect.store', [$project, $sample]);

        // A single external element has no "next/prev component" concept —
        // every question for its one component lives on this one page.
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

    public function storeAssessment(Request $request, Project $project, ExternalSample $sample)
    {
        $this->guardProjectVisible($project);

        abort_if($sample->project_id !== $project->id, 404);

        $componentCode = $sample->element_code;
        $element       = ExternalElement::where('element_code', $componentCode)->firstOrFail();
        $items         = ChecklistItem::forComponent($componentCode)->get();

        $existing = ComponentAssessment::where([
            'project_id'         => $project->id,
            'external_sample_id' => $sample->id,
            'component_code'     => $componentCode,
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
                'project_id'         => $project->id,
                'external_sample_id' => $sample->id,
                'component_code'     => $componentCode,
            ]));
        }

        [$overallStatus, $failCount] = $this->saveAnswers($assessment, $items, $data['answers']);
        $assessment->update(['overall_sample_status' => $overallStatus]);

        if ($photos) {
            $this->attachPhotos($photos, $assessment);
        }

        $this->syncDefect(
            $assessment, $overallStatus, $failCount, $project->id, $componentCode,
            $element->name, $sample->label, $data['remarks'] ?? null, $photos
        );

        $this->scoring->recalculateAndSave($project);

        return redirect()
            ->route('projects.external', $project)
            ->with('success', "{$sample->label} saved.");
    }
}
