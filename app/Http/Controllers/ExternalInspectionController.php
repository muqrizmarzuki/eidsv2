<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesChecklistAnswers;
use App\Models\ChecklistItem;
use App\Models\ComponentAssessment;
use App\Models\ExternalElement;
use App\Models\ExternalSample;
use App\Models\Project;
use App\Services\ScoringService;
use Illuminate\Http\Request;

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
        $registry     = ExternalElement::ordered();
        $presentCodes = $project->activeExternalElementCodes();

        $elements = $registry->map(fn ($el) => [
            'el'      => $el,
            'present' => in_array($el->element_code, $presentCodes, true),
            'samples' => $project->externalSamples->where('element_code', $el->element_code),
        ]);

        return view('projects.external', compact('project', 'elements'));
    }

    public function togglePresence(Request $request, Project $project, string $elementCode)
    {
        $this->guardProjectVisible($project);

        $element = ExternalElement::where('element_code', $elementCode)->firstOrFail();
        $present = $request->boolean('present');

        $project->externalElementSettings()->updateOrCreate(
            ['element_code' => $elementCode],
            ['present' => $present]
        );

        // Generate the fixed sample set (Table 6) the first time an element is
        // switched on — existing samples/assessments are preserved if toggled
        // off and back on.
        if ($present && $project->externalSamples()->where('element_code', $elementCode)->doesntExist()) {
            for ($i = 0; $i < $element->sample_count; $i++) {
                ExternalSample::create([
                    'project_id'   => $project->id,
                    'element_code' => $elementCode,
                    'sample_index' => $i + 1,
                    'label'        => "{$element->name} #" . ($i + 1),
                ]);
            }
        }

        $this->scoring->recalculateAndSave($project);

        return redirect()->route('projects.external', $project)
            ->with('success', "{$element->name} " . ($present ? 'marked present.' : 'marked not present.'));
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

        $data = $request->validate([
            'answers' => 'required|array',
            'remarks' => 'nullable|string|max:1000',
            'photo'   => 'nullable|image|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store("inspections/external/{$project->id}/{$sample->id}", 'public');
        }

        $existing = ComponentAssessment::where([
            'project_id'         => $project->id,
            'external_sample_id' => $sample->id,
            'component_code'     => $componentCode,
        ])->first();

        $payload = ['remarks' => $data['remarks'] ?? null];
        if ($photoPath) $payload['photo_path'] = $photoPath;

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

        if ($request->hasFile('photo')) {
            $assessment->addMediaFromRequest('photo')->toMediaCollection('photos');
        }

        $this->syncDefect(
            $assessment, $overallStatus, $failCount, $project->id, $componentCode,
            $element->name, $sample->label, $data['remarks'] ?? null, $request->hasFile('photo')
        );

        $this->scoring->recalculateAndSave($project);

        return redirect()
            ->route('projects.external', $project)
            ->with('success', "{$sample->label} saved.");
    }
}
