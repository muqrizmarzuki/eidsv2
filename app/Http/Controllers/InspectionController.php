<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesChecklistAnswers;
use App\Models\ChecklistItem;
use App\Models\ComponentAssessment;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\WeightageArchitecturalElement;
use App\Services\ScoringService;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    use HandlesChecklistAnswers;

    public function __construct(private ScoringService $scoring)
    {
    }

    /**
     * Display info (name, weightage%) for every code the inspector can walk
     * through — the Table 2 architectural registry plus a synthetic M&E entry,
     * since M&E is scored under Table 1's me_pct rather than a Table 2 row.
     */
    private function componentRegistry(Project $project): array
    {
        $registry   = WeightageArchitecturalElement::ordered();
        $components = collect($project->roomBasedComponentCodes())->mapWithKeys(fn ($code) => [
            $code => ['name' => $registry[$code]->name, 'weightage' => (float) $registry[$code]->breakdown_pct],
        ])->all();

        $mePct = (float) \App\Models\WeightageOverall::forCategory($project->building_category)->me_pct;
        $components['ME_FITTING'] = ['name' => 'M&E Fittings', 'weightage' => $mePct];

        return $components;
    }

    public function components(Project $project)
    {
        $this->guardProjectVisible($project);

        $project->load(['samples.assessments']);
        $components = $this->componentRegistry($project);

        return view('projects.components', compact('project', 'components'));
    }

    public function inspect(Project $project, ProjectSample $sample)
    {
        $this->guardProjectVisible($project);

        abort_if($sample->project_id !== $project->id, 404);

        $componentCodes = $project->inspectableComponentCodes();
        $registry       = $this->componentRegistry($project);
        $componentCode  = request('component');

        if (!$componentCode || !in_array($componentCode, $componentCodes, true)) {
            $done = $sample->assessments->pluck('component_code')->toArray();
            $componentCode = collect($componentCodes)->first(fn ($c) => !in_array($c, $done))
                ?? $componentCodes[0];
        }

        $component  = $registry[$componentCode];
        $assessment = $sample->assessments->where('component_code', $componentCode)->first();
        $items      = ChecklistItem::forComponent($componentCode)->get();
        $answers    = $assessment ? $assessment->answers->keyBy('checklist_item_id') : collect();

        $currentIdx     = array_search($componentCode, $componentCodes);
        $prevCode       = $currentIdx > 0 ? $componentCodes[$currentIdx - 1] : null;
        $nextCode       = $currentIdx < count($componentCodes) - 1 ? $componentCodes[$currentIdx + 1] : null;
        $componentPos   = $currentIdx + 1;
        $componentCount = count($componentCodes);

        $gridUrl  = route('projects.components', $project);
        $storeUrl = route('projects.inspect.store', [$project, $sample]);
        $prevUrl  = $prevCode ? route('projects.inspect', [$project, $sample, 'component' => $prevCode]) : null;

        return view('projects.inspect', compact(
            'project', 'sample', 'componentCode', 'component',
            'assessment', 'items', 'answers',
            'prevCode', 'nextCode', 'componentPos', 'componentCount',
            'gridUrl', 'storeUrl', 'prevUrl'
        ));
    }

    public function storeAssessment(Request $request, Project $project, ProjectSample $sample)
    {
        $this->guardProjectVisible($project);

        abort_if($sample->project_id !== $project->id, 404);

        $componentCode = $request->input('component_code');
        abort_unless(in_array($componentCode, $project->inspectableComponentCodes(), true), 422);

        $items = ChecklistItem::forComponent($componentCode)->get();

        $existing = ComponentAssessment::where([
            'project_id'     => $project->id,
            'sample_id'      => $sample->id,
            'component_code' => $componentCode,
        ])->first();

        $data = $request->validate([
            'component_code' => 'required|string',
            'answers'        => 'required|array',
            'remarks'        => 'nullable|string|max:1000',
        ] + $this->photoRules($existing), $this->photoMessages());

        $photos = $request->file('photos', []);

        $payload = ['remarks' => $data['remarks'] ?? null];

        if ($existing) {
            $existing->update($payload);
            $assessment = $existing;
        } else {
            $assessment = ComponentAssessment::create(array_merge($payload, [
                'project_id'     => $project->id,
                'sample_id'      => $sample->id,
                'component_code' => $componentCode,
            ]));
        }

        [$overallStatus, $failCount] = $this->saveAnswers($assessment, $items, $data['answers']);
        $assessment->update(['overall_sample_status' => $overallStatus]);

        if ($photos) {
            $this->attachPhotos($photos, $assessment);
        }

        $componentName = $this->componentRegistry($project)[$componentCode]['name'];

        $this->syncDefect(
            $assessment, $overallStatus, $failCount, $project->id, $componentCode,
            $componentName, $sample->location_name, $data['remarks'] ?? null, $photos
        );

        $this->scoring->recalculateAndSave($project);

        $nextCode = $this->nextComponent($project, $sample, $componentCode);

        if ($nextCode) {
            return redirect()
                ->route('projects.inspect', [$project, $sample, 'component' => $nextCode])
                ->with('success', "{$componentCode} saved. Next: {$nextCode}.");
        }

        $redirect = redirect()
            ->route('projects.components', $project)
            ->with('success', "All components for {$sample->location_name} completed.");

        if ($project->fresh()->inspection_progress >= 100) {
            $redirect->with('inspection_complete', true);
        }

        return $redirect;
    }

    public function score(Project $project)
    {
        $this->guardProjectVisible($project);

        $breakdown = $this->scoring->scoreBreakdown($project);
        $findings  = $this->scoring->failedFindings($project);

        return view('projects.score', array_merge(compact('project', 'findings'), $breakdown));
    }

    public function summary(Project $project)
    {
        $this->guardProjectVisible($project);

        $project->load(['samples.assessments', 'defects', 'creator']);
        $breakdown       = $this->scoring->scoreBreakdown($project);
        $findings        = $this->scoring->failedFindings($project);
        $openDefects     = $project->defects->whereIn('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION'])->count();
        $resolvedDefects = $project->defects->where('status', 'RESOLVED')->count();

        return view('projects.summary', array_merge(
            compact('project', 'findings', 'openDefects', 'resolvedDefects'),
            $breakdown
        ));
    }

    private function nextComponent(Project $project, ProjectSample $sample, string $currentCode): ?string
    {
        $components = $project->inspectableComponentCodes();
        $done       = $sample->fresh()->assessments->pluck('component_code')->toArray();
        $currentIdx = array_search($currentCode, $components);
        $next       = array_slice($components, $currentIdx + 1);

        return collect($next)->first(fn ($c) => !in_array($c, $done));
    }
}
