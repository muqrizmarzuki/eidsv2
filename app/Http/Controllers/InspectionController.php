<?php

namespace App\Http\Controllers;

use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InspectionController extends Controller
{
    public function components(Project $project)
    {
        $this->guardProjectVisible($project);

        $project->load(['samples.assessments']);
        $components = config('eids.components');

        return view('projects.components', compact('project', 'components'));
    }

    public function inspect(Project $project, ProjectSample $sample)
    {
        $this->guardProjectVisible($project);

        abort_if($sample->project_id !== $project->id, 404);

        $components    = config('eids.components');
        $componentCode = request('component');

        if (!$componentCode || !isset($components[$componentCode])) {
            $done = $sample->assessments->pluck('component_code')->toArray();
            $componentCode = collect(array_keys($components))
                ->first(fn ($c) => !in_array($c, $done))
                ?? array_key_first($components);
        }

        $component      = $components[$componentCode];
        $assessment     = $sample->assessments->where('component_code', $componentCode)->first();
        $levellingMax   = (float) setting('levelling_max_mm', 3.0);
        $jointMax       = (float) setting('joint_max_mm', 1.0);

        $componentKeys  = array_keys($components);
        $currentIdx     = array_search($componentCode, $componentKeys);
        $prevCode       = $currentIdx > 0 ? $componentKeys[$currentIdx - 1] : null;
        $nextCode       = $currentIdx < count($componentKeys) - 1 ? $componentKeys[$currentIdx + 1] : null;
        $componentPos   = $currentIdx + 1;
        $componentCount = count($componentKeys);

        return view('projects.inspect', compact(
            'project', 'sample', 'components', 'componentCode',
            'component', 'assessment', 'levellingMax', 'jointMax',
            'prevCode', 'nextCode', 'componentPos', 'componentCount'
        ));
    }

    public function storeAssessment(Request $request, Project $project, ProjectSample $sample)
    {
        $this->guardProjectVisible($project);

        abort_if($sample->project_id !== $project->id, 404);

        $components    = config('eids.components');
        $componentCode = $request->input('component_code');
        $componentCfg  = $components[$componentCode] ?? null;
        abort_unless($componentCfg, 422);

        $data = $request->validate([
            'component_code'   => 'required|string',
            'finishing_status' => 'required|in:PASS,FAIL',
            'hollow_status'    => 'required|in:PASS,FAIL',
            'levelling_mm'     => 'nullable|numeric|min:0',
            'joint_mm'         => 'nullable|numeric|min:0',
            'crack_status'     => 'required|in:PASS,FAIL',
            'remarks'          => 'nullable|string|max:1000',
            'photo'            => 'nullable|image|max:5120',
        ]);

        $levellingMm = isset($data['levelling_mm']) ? (float) $data['levelling_mm'] : null;
        $jointMm     = isset($data['joint_mm'])     ? (float) $data['joint_mm']     : null;

        $levellingStatus = is_null($levellingMm) ? 'PASS'
            : ($levellingMm <= (float) setting('levelling_max_mm', 3.0) ? 'PASS' : 'FAIL');
        $jointStatus = is_null($jointMm) ? 'PASS'
            : ($jointMm <= (float) setting('joint_max_mm', 1.0) ? 'PASS' : 'FAIL');

        $allStatuses   = [$data['finishing_status'], $data['hollow_status'], $levellingStatus, $jointStatus, $data['crack_status']];
        $overallStatus = in_array('FAIL', $allStatuses) ? 'FAIL' : 'PASS';

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store("inspections/{$project->id}/{$sample->id}", 'public');
        }

        $payload = [
            'project_id'            => $project->id,
            'sample_id'             => $sample->id,
            'component_code'        => $componentCode,
            'component_name'        => $componentCfg['name'],
            'weightage'             => $componentCfg['weightage'],
            'finishing_status'      => $data['finishing_status'],
            'hollow_status'         => $data['hollow_status'],
            'levelling_mm'          => $levellingMm,
            'levelling_status'      => $levellingStatus,
            'joint_mm'              => $jointMm,
            'joint_status'          => $jointStatus,
            'crack_status'          => $data['crack_status'],
            'overall_sample_status' => $overallStatus,
            'remarks'               => $data['remarks'] ?? null,
        ];
        if ($photoPath) {
            $payload['photo_path'] = $photoPath;
        }

        $existing = ComponentAssessment::where([
            'project_id'     => $project->id,
            'sample_id'      => $sample->id,
            'component_code' => $componentCode,
        ])->first();

        if ($existing) {
            if (!$photoPath) unset($payload['photo_path']);
            $existing->update($payload);
            $assessment = $existing->fresh();
        } else {
            $assessment = ComponentAssessment::create($payload);
        }

        if ($request->hasFile('photo')) {
            $assessment->addMediaFromRequest('photo')->toMediaCollection('photos');
        }

        if ($overallStatus === 'FAIL') {
            $defect = Defect::updateOrCreate(
                ['assessment_id' => $assessment->id],
                [
                    'project_id'         => $project->id,
                    'component_name'     => $componentCfg['name'],
                    'location'           => $sample->location_name,
                    'defect_description' => "FAIL on {$componentCode} ({$componentCfg['name']}) at {$sample->location_name}. " . ($data['remarks'] ?? ''),
                    'photo_path'         => $assessment->photo_path,
                    'severity'           => $this->inferSeverity($allStatuses),
                    'status'             => 'OPEN',
                ]
            );

            if ($request->hasFile('photo')) {
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

        $this->recalculateScore($project);

        $nextCode = $this->nextComponent($sample, $componentCode);

        if ($nextCode) {
            return redirect()
                ->route('projects.inspect', [$project, $sample, 'component' => $nextCode])
                ->with('success', "{$componentCode} saved — next: {$nextCode}.");
        }

        return redirect()
            ->route('projects.components', $project)
            ->with('success', "All components for {$sample->location_name} completed.");
    }

    public function score(Project $project)
    {
        $this->guardProjectVisible($project);

        $project->load('assessments');
        $components = config('eids.components');
        $meScore    = (float) setting('me_score', 2.0);
        $extScore   = (float) setting('external_score', 11.8);
        $ratingBaik = (float) setting('rating_baik', 85);
        $ratingMod  = (float) setting('rating_sederhana', 70);

        $rows = $this->buildRows($project->assessments, $components);

        $sArch      = collect($rows)->sum('sComp');
        $totalScore = $sArch + $meScore + $extScore;
        $rating     = $totalScore >= $ratingBaik ? 'GOOD' : ($totalScore >= $ratingMod ? 'MODERATE' : 'WEAK');

        return view('projects.score', compact(
            'project', 'rows', 'sArch', 'meScore', 'extScore',
            'totalScore', 'rating', 'components', 'ratingBaik', 'ratingMod'
        ));
    }

    public function summary(Project $project)
    {
        $this->guardProjectVisible($project);

        $project->load(['samples.assessments', 'defects', 'creator']);
        $components = config('eids.components');
        $meScore    = (float) setting('me_score', 2.0);
        $extScore   = (float) setting('external_score', 11.8);
        $ratingBaik = (float) setting('rating_baik', 85);
        $ratingMod  = (float) setting('rating_sederhana', 70);

        $rows = $this->buildRows($project->assessments, $components);

        $sArch           = collect($rows)->sum('sComp');
        $totalScore      = $sArch + $meScore + $extScore;
        $rating          = $totalScore >= $ratingBaik ? 'GOOD' : ($totalScore >= $ratingMod ? 'MODERATE' : 'WEAK');
        $openDefects     = $project->defects->whereIn('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION'])->count();
        $resolvedDefects = $project->defects->where('status', 'RESOLVED')->count();

        return view('projects.summary', compact(
            'project', 'rows', 'sArch', 'meScore', 'extScore',
            'totalScore', 'rating', 'openDefects', 'resolvedDefects',
            'components', 'ratingBaik', 'ratingMod'
        ));
    }

    private function buildRows($assessments, array $components): array
    {
        $rows = [];
        foreach ($components as $code => $cfg) {
            $subset   = $assessments->where('component_code', $code);
            $total    = $subset->count();
            $pass     = $subset->where('overall_sample_status', 'PASS')->count();
            $passRate = $total > 0 ? ($pass / $total) * 100 : 0;
            $sComp    = ($passRate / 100) * $cfg['weightage'];
            $rows[$code] = [
                'name'      => $cfg['name'],
                'weightage' => $cfg['weightage'],
                'total'     => $total,
                'pass'      => $pass,
                'fail'      => $total - $pass,
                'passRate'  => round($passRate, 1),
                'sComp'     => round($sComp, 2),
                'cfg'       => $cfg,
                'code'      => $code,
            ];
        }
        return $rows;
    }

    private function recalculateScore(Project $project): void
    {
        $project->load('assessments');
        $components = config('eids.components');
        $sArch      = 0;

        foreach ($components as $code => $cfg) {
            $assessments = $project->assessments->where('component_code', $code);
            $total       = $assessments->count();
            if ($total === 0) continue;
            $pass  = $assessments->where('overall_sample_status', 'PASS')->count();
            $sArch += ($pass / $total) * $cfg['weightage'];
        }

        $totalScore = $sArch + (float) setting('me_score', 2.0) + (float) setting('external_score', 11.8);
        $project->update(['overall_score' => round($totalScore, 2)]);
    }

    private function nextComponent(ProjectSample $sample, string $currentCode): ?string
    {
        $components = array_keys(config('eids.components'));
        $done       = $sample->fresh()->assessments->pluck('component_code')->toArray();
        $currentIdx = array_search($currentCode, $components);
        $next       = array_slice($components, $currentIdx + 1);

        return collect($next)->first(fn ($c) => !in_array($c, $done));
    }

    private function inferSeverity(array $statuses): string
    {
        $failCount = count(array_filter($statuses, fn ($s) => $s === 'FAIL'));
        if ($failCount >= 3) return 'high';
        if ($failCount === 2) return 'medium';
        return 'low';
    }
}
