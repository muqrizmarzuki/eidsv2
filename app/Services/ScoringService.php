<?php

namespace App\Services;

use App\Models\Project;
use App\Models\QpDeclaration;
use App\Models\WeightageArchitecturalElement;
use App\Models\WeightageLocation;
use App\Models\WeightageOverall;
use Illuminate\Support\Collection;

/**
 * Implements the CIS 7:2021 scoring pipeline: per-component pass rate (location
 * weighted for internal finishes) -> architectural subtotal (Table 2, with
 * proportional redistribution for absent optional elements) -> overall E-IDS
 * score (Table 1). M&E (Annex B) and External Works (Annex C) are each their
 * own flat pass-rate pool, scored against Table 1's me_pct/external_pct.
 */
class ScoringService
{
    private const INTERNAL_FINISH_CODES = [
        'A1_FLOOR', 'A2_WALL', 'A3_CEILING', 'A4_DOOR', 'A5_WINDOW', 'A6_FIXTURES',
    ];

    public function sampleCount(string $buildingCategory, float $gfaSqm): int
    {
        return \App\Models\SamplingRule::forCategory($buildingCategory)->sampleCountFor($gfaSqm);
    }

    /**
     * The registry of architectural elements applicable to this project, with
     * their Table 2 weightage rescaled to 100 after excluding any optional
     * element (Car Park, Apron/Drain) the project has flagged as absent.
     *
     * @return Collection<string, array{el: WeightageArchitecturalElement, weightage: float}>
     */
    public function redistributedWeights(Project $project): Collection
    {
        $all      = WeightageArchitecturalElement::ordered();
        $active   = $all->filter(fn ($el) => !$el->optional || $project->elementPresent($el->component_code));
        $rawTotal = $active->sum(fn ($el) => (float) $el->breakdown_pct);

        return $active->map(fn ($el) => [
            'el'        => $el,
            'weightage' => $rawTotal > 0 ? (float) $el->breakdown_pct / $rawTotal * 100 : 0,
        ]);
    }

    /**
     * Per-component rows for the score/summary/report views: name, weightage
     * (post-redistribution), pass/fail counts, pass rate, and sComp (this
     * component's contribution to the 0-100 architectural quality score).
     */
    public function componentRows(Project $project): array
    {
        $project->loadMissing(['assessments.sample']);
        $weights = $this->redistributedWeights($project);
        $rows    = [];

        foreach ($weights as $code => $entry) {
            $el        = $entry['el'];
            $weightage = $entry['weightage'];

            if ($el->scoring_mode === 'declaration') {
                $decl     = $project->qpDeclarations->firstWhere('item_code', $code)
                            ?? new QpDeclaration(['item_code' => $code, 'declared' => false]);
                $earned   = $decl->is_earned;
                $total    = 1;
                $pass     = $earned ? 1 : 0;
                $passRate = $earned ? 100.0 : 0.0;
            } else {
                $assessments = $project->assessments->where('component_code', $code)->where('na', false);
                $total       = $assessments->count();
                $pass        = $assessments->where('overall_sample_status', 'PASS')->count();

                $passRate = (in_array($code, self::INTERNAL_FINISH_CODES, true) && $total > 0)
                    ? $this->locationWeightedPassRate($project, $assessments)
                    : ($total > 0 ? ($pass / $total) * 100 : 0);
            }

            $sComp = ($passRate / 100) * $weightage;

            $rows[$code] = [
                'code'      => $code,
                'name'      => $el->name,
                'group'     => $el->group,
                'weightage' => round($weightage, 2),
                'total'     => $total,
                'pass'      => $pass,
                'fail'      => max(0, $total - $pass),
                'passRate'  => round($passRate, 1),
                'sComp'     => round($sComp, 2),
            ];
        }

        return $rows;
    }

    /**
     * Table 4: within an internal-finish component, weight the pass rate across
     * Principal/Service/Circulation sample buckets rather than a flat average.
     * A bucket with zero samples is dropped and the remaining buckets' Table 4
     * percentages are rescaled to still sum to 100 (same redistribution pattern
     * as §4.4 of the design spec).
     */
    private function locationWeightedPassRate(Project $project, Collection $assessments): float
    {
        $locWeights = WeightageLocation::forCategory($project->building_category);
        $buckets    = [
            'principal'   => (float) $locWeights->principal_pct,
            'service'     => (float) $locWeights->service_pct,
            'circulation' => (float) $locWeights->circulation_pct,
        ];

        $byType = $assessments->groupBy(fn ($a) => $a->sample->location_type ?? 'principal');

        $presentTotal = 0.0;
        foreach ($buckets as $type => $pct) {
            if ($byType->has($type)) $presentTotal += $pct;
        }
        if ($presentTotal <= 0) return 0.0;

        $weightedSum = 0.0;
        foreach ($buckets as $type => $pct) {
            if (!$byType->has($type)) continue;
            $group    = $byType->get($type);
            $groupPass = $group->where('overall_sample_status', 'PASS')->count();
            $groupRate = $group->count() > 0 ? ($groupPass / $group->count()) * 100 : 0;
            $weightedSum += $groupRate * ($pct / $presentTotal);
        }

        return $weightedSum;
    }

    /**
     * M&E pass rate (Annex B, assessed at the same sample locations as internal
     * finishes per Table 5's note) — a flat average across all M&E fitting
     * assessments, same shape as an architectural row for display reuse.
     */
    public function meRow(Project $project): array
    {
        $project->loadMissing('assessments');
        $assessments = $project->assessments->where('component_code', 'ME_FITTING')->where('na', false);
        $total       = $assessments->count();
        $pass        = $assessments->where('overall_sample_status', 'PASS')->count();
        $passRate    = $total > 0 ? ($pass / $total) * 100 : 0;

        return [
            'code' => 'ME_FITTING', 'name' => 'M&E Fittings', 'total' => $total,
            'pass' => $pass, 'fail' => max(0, $total - $pass), 'passRate' => round($passRate, 1),
        ];
    }

    /**
     * External Works pass rate (Annex C) — a single flat pass-rate pooled
     * across every checklist answer from every present element's samples.
     * The standard gives no per-element weightage breakdown (unlike Table 2),
     * so there's nothing to weight or redistribute here — see §4.6.
     */
    public function externalRow(Project $project): array
    {
        $project->loadMissing('assessments');
        $presentCodes = $project->activeExternalElementCodes();
        $assessments  = $project->assessments
            ->whereNotNull('external_sample_id')
            ->where('na', false)
            ->whereIn('component_code', $presentCodes);

        $total    = $assessments->count();
        $pass     = $assessments->where('overall_sample_status', 'PASS')->count();
        $passRate = $total > 0 ? ($pass / $total) * 100 : 0;

        return [
            'code' => 'EXTERNAL', 'name' => 'External Works', 'total' => $total,
            'pass' => $pass, 'fail' => max(0, $total - $pass), 'passRate' => round($passRate, 1),
        ];
    }

    /**
     * Full E-IDS score breakdown for a project: architectural subtotal (points),
     * M&E subtotal (Annex B pass rate x Table 1's me_pct), External subtotal
     * (Annex C pass rate x Table 1's external_pct), total score, and rating.
     */
    public function scoreBreakdown(Project $project): array
    {
        $rows       = $this->componentRows($project);
        $quality    = collect($rows)->sum('sComp'); // 0-100 architectural quality score
        $overallW   = WeightageOverall::forCategory($project->building_category);
        $archPct    = (float) $overallW->architectural_pct;
        $mePct      = (float) $overallW->me_pct;
        $extPct     = (float) $overallW->external_pct;

        // An uninspected M&E fitting or External element contributes 0, same
        // convention as an uninspected architectural component (componentRows()).
        $meRow       = $this->meRow($project);
        $externalRow = $this->externalRow($project);

        $sArch      = round($quality * $archPct / 100, 2);
        $meScore    = round(($meRow['passRate'] / 100) * $mePct, 2);
        $extScore   = round(($externalRow['passRate'] / 100) * $extPct, 2);
        $totalScore = round($sArch + $meScore + $extScore, 2);

        $ratingBaik = (float) setting('rating_baik', 85);
        $ratingMod  = (float) setting('rating_sederhana', 70);
        $rating     = $totalScore >= $ratingBaik ? 'GOOD' : ($totalScore >= $ratingMod ? 'MODERATE' : 'WEAK');

        return compact('rows', 'sArch', 'meScore', 'extScore', 'totalScore', 'rating',
            'ratingBaik', 'ratingMod', 'archPct', 'mePct', 'extPct', 'meRow', 'externalRow');
    }

    public function recalculateAndSave(Project $project): void
    {
        $breakdown = $this->scoreBreakdown($project);
        $project->update(['overall_score' => $breakdown['totalScore']]);
    }

    /**
     * Every FAILED checklist answer across architectural, M&E, and External
     * assessments, with the question text and location — the per-question
     * detail a formal report needs without listing every passing answer too.
     */
    public function failedFindings(Project $project): array
    {
        $registry     = WeightageArchitecturalElement::ordered();
        $extRegistry  = \App\Models\ExternalElement::ordered();
        $componentName = function (string $code) use ($registry, $extRegistry) {
            return match (true) {
                $code === 'ME_FITTING'        => 'M&E Fittings',
                $registry->has($code)         => $registry[$code]->name,
                $extRegistry->has($code)      => $extRegistry[$code]->name,
                default                       => $code,
            };
        };

        // loadMissing so a caller (e.g. ReportController::buildScoreData) that
        // already eager-loaded ->assessments isn't hit with a second, separate
        // query here — only the nested relations still missing are fetched.
        $project->loadMissing([
            'assessments.sample', 'assessments.externalSample', 'assessments.archSample',
            'assessments.answers.checklistItem',
        ]);

        $findings = [];
        foreach ($project->assessments as $assessment) {
            $failedAnswers = $assessment->answers->where('result', 'FAIL');
            if ($failedAnswers->isEmpty()) continue;

            $location = $assessment->sample?->location_name
                ?? $assessment->externalSample?->label
                ?? $assessment->archSample?->label
                ?? '—';
            foreach ($failedAnswers as $answer) {
                $findings[] = [
                    'component' => $componentName($assessment->component_code),
                    'location'  => $location,
                    'question'  => $answer->checklistItem->question_text,
                    'value'     => $answer->numeric_value,
                    'remarks'   => $assessment->remarks,
                ];
            }
        }

        return $findings;
    }
}
