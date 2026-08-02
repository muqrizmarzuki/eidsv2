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
        $openDefects     = $project->defects->whereIn('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION'])->count();
        $resolvedDefects = $project->defects->where('status', 'RESOLVED')->count();

        return compact('rows', 'sArch', 'meScore', 'extScore', 'totalScore',
                       'rating', 'openDefects', 'resolvedDefects', 'ratingBaik', 'ratingMod');
    }

    public function index()
    {
        $projects = Project::visibleTo(auth()->user())
            ->where('overall_score', '>', 0)
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
        $this->guardProjectVisible($project);
        if ($error = $this->reportBlockedReason($project)) {
            return redirect()->route('projects.show', $project)->with('error', $error);
        }

        $data = $this->buildScoreData($project);
        return view('reports.show', array_merge(compact('project'), $data));
    }

    public function pdf(Project $project)
    {
        $this->guardProjectVisible($project);
        if ($error = $this->reportBlockedReason($project)) {
            return redirect()->route('projects.show', $project)->with('error', $error);
        }

        $data = $this->buildScoreData($project);
        $pdf  = Pdf::loadView('reports.pdf', array_merge(compact('project'), $data))
                   ->setPaper('a4', 'portrait');

        return $pdf->download("eids-report-{$project->project_no}.pdf");
    }

    /**
     * The signed G-IDS report is a formal certificate — it must reflect a fully
     * inspected project with every defect resolved, never a partial in-progress state.
     */
    private function reportBlockedReason(Project $project): ?string
    {
        if ($project->inspection_progress < 100) {
            return 'The signed G-IDS report is not available yet — every sample unit must be inspected first.';
        }

        $openOrPending = $project->defects()->whereIn('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION'])->count();
        if ($openOrPending > 0) {
            return "{$openOrPending} defect(s) still need to be resolved before the signed G-IDS report can be generated.";
        }

        return null;
    }
}
