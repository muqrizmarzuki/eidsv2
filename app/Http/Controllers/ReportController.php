<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ScoringService;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct(private ScoringService $scoring)
    {
    }

    private function buildScoreData(Project $project): array
    {
        $project->load(['assessments', 'samples', 'defects', 'creator']);

        $breakdown       = $this->scoring->scoreBreakdown($project);
        $openDefects     = $project->defects->whereIn('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION'])->count();
        $resolvedDefects = $project->defects->where('status', 'RESOLVED')->count();

        return array_merge($breakdown, compact('openDefects', 'resolvedDefects'));
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
