<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    private function buildScoreData(Project $project): array
    {
        $project->load(['assessments', 'samples', 'defects', 'creator']);
        $components = config('eids.components');
        $meScore    = (float) setting('me_score', 2.0);
        $extScore   = (float) setting('external_score', 11.8);
        $ratingBaik = (float) setting('rating_baik', 85);
        $ratingMod  = (float) setting('rating_sederhana', 70);

        $rows = [];
        foreach ($components as $code => $cfg) {
            $assessments = $project->assessments->where('component_code', $code);
            $total       = $assessments->count();
            $pass        = $assessments->where('overall_sample_status', 'PASS')->count();
            $passRate    = $total > 0 ? ($pass / $total) * 100 : 0;
            $sComp       = ($passRate / 100) * $cfg['weightage'];
            $rows[$code] = [
                'name'      => $cfg['name'],
                'weightage' => $cfg['weightage'],
                'total'     => $total,
                'pass'      => $pass,
                'fail'      => $total - $pass,
                'passRate'  => round($passRate, 1),
                'sComp'     => round($sComp, 2),
            ];
        }

        $sArch           = collect($rows)->sum('sComp');
        $totalScore      = $sArch + $meScore + $extScore;
        $rating          = $totalScore >= $ratingBaik ? 'GOOD' : ($totalScore >= $ratingMod ? 'MODERATE' : 'WEAK');
        $openDefects     = $project->defects->where('status', 'OPEN')->count();
        $resolvedDefects = $project->defects->where('status', 'RESOLVED')->count();

        return compact('rows', 'sArch', 'meScore', 'extScore', 'totalScore',
                       'rating', 'openDefects', 'resolvedDefects', 'ratingBaik', 'ratingMod');
    }

    public function index()
    {
        $projects = Project::where('overall_score', '>', 0)
            ->with('creator')
            ->withCount('defects')
            ->orderByDesc('updated_at')
            ->get();

        $ratingBaik = (float) setting('rating_baik', 85);
        $ratingMod  = (float) setting('rating_sederhana', 70);

        return view('reports.index', compact('projects', 'ratingBaik', 'ratingMod'));
    }

    public function show(Project $project)
    {
        $data = $this->buildScoreData($project);
        return view('reports.show', array_merge(compact('project'), $data));
    }

    public function pdf(Project $project)
    {
        $data = $this->buildScoreData($project);
        $pdf  = Pdf::loadView('reports.pdf', array_merge(compact('project'), $data))
                   ->setPaper('a4', 'portrait');

        return $pdf->download("eids-report-{$project->project_no}.pdf");
    }
}
