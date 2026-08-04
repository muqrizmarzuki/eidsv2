<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ComponentAssessment;
use App\Models\Defect;
use Illuminate\Support\Collection;

/**
 * Shared per-question answer-saving logic used by both the internal-sample
 * (InspectionController) and external-element (ExternalInspectionController)
 * inspection flows — same checklist_items/assessment_answers mechanics either
 * way, only the sample type and routing differ.
 */
trait HandlesChecklistAnswers
{
    /**
     * Saves one answer per checklist item, derives overall PASS/FAIL, and
     * returns [overallStatus, failCount].
     */
    private function saveAnswers(ComponentAssessment $assessment, Collection $items, array $rawAnswers): array
    {
        $anyFail   = false;
        $failCount = 0;

        foreach ($items as $item) {
            $raw = $rawAnswers[$item->id] ?? null;

            if ($item->input_type === 'numeric_with_tolerance') {
                $numericValue = ($raw === null || $raw === '') ? null : (float) $raw;
                $max          = $item->tolerance_max_mm !== null ? (float) $item->tolerance_max_mm : null;
                $result       = ($numericValue === null || $max === null || $numericValue <= $max) ? 'PASS' : 'FAIL';

                $assessment->answers()->updateOrCreate(
                    ['checklist_item_id' => $item->id],
                    ['numeric_value' => $numericValue, 'result' => $result]
                );
            } else {
                $result = $raw === 'FAIL' ? 'FAIL' : 'PASS';
                $assessment->answers()->updateOrCreate(
                    ['checklist_item_id' => $item->id],
                    ['result' => $result, 'numeric_value' => null]
                );
            }

            $anyFail    = $anyFail || $result === 'FAIL';
            $failCount += $result === 'FAIL' ? 1 : 0;
        }

        return [$anyFail ? 'FAIL' : 'PASS', $failCount];
    }

    /**
     * Creates/updates or clears the auto-defect for an assessment based on its
     * overall status, copying the photo across either way.
     */
    private function syncDefect(
        ComponentAssessment $assessment,
        string $overallStatus,
        int $failCount,
        int $projectId,
        string $componentCode,
        string $componentName,
        string $locationLabel,
        ?string $remarks,
        bool $hasNewPhoto
    ): void {
        if ($overallStatus === 'FAIL') {
            $defect = Defect::updateOrCreate(
                ['assessment_id' => $assessment->id],
                [
                    'project_id'         => $projectId,
                    'component_name'     => $componentName,
                    'location'           => $locationLabel,
                    'defect_description' => "FAIL on {$componentCode} ({$componentName}) at {$locationLabel}. " . ($remarks ?? ''),
                    'photo_path'         => $assessment->photo_path,
                    'severity'           => $this->inferSeverity($failCount),
                    'status'             => 'OPEN',
                ]
            );

            if ($hasNewPhoto) {
                $defect->addMediaFromRequest('photo')->toMediaCollection('photos');
            } elseif ($assessment->hasMedia('photos')) {
                $mediaItem = $assessment->getFirstMedia('photos');
                if ($mediaItem) {
                    $mediaItem->copy($defect, 'photos');
                }
            }
        } else {
            Defect::where('assessment_id', $assessment->id)->delete();
        }
    }

    private function inferSeverity(int $failCount): string
    {
        if ($failCount >= 3) return 'high';
        if ($failCount === 2) return 'medium';
        return 'low';
    }
}
