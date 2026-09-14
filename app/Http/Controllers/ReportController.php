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
        $project->load([
            'assessments.sample', 'assessments.externalSample', 'assessments.archSample',
            'assessments.answers.checklistItem',
            'samples', 'externalSamples', 'defects.media', 'creator', 'qpDeclarations',
        ]);

        $breakdown       = $this->scoring->scoreBreakdown($project);
        $findings        = $this->scoring->failedFindings($project);
        $openDefects     = $project->defects->whereIn('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION'])->count();
        $resolvedDefects = $project->defects->where('status', 'RESOLVED')->count();

        return array_merge($breakdown, compact('findings', 'openDefects', 'resolvedDefects'));
    }

    public function index()
    {
        $ratingBaik = (float) setting('rating_baik', 85);
        $ratingMod  = (float) setting('rating_sederhana', 70);

        $base = Project::visibleTo(auth()->user())->where('overall_score', '>', 0);

        $good     = $base->clone()->where('overall_score', '>=', $ratingBaik)->count();
        $moderate = $base->clone()->where('overall_score', '>=', $ratingMod)->where('overall_score', '<', $ratingBaik)->count();
        $weak     = $base->clone()->where('overall_score', '<', $ratingMod)->count();

        $projects = $base->clone()
            ->with('creator')
            ->withCount('defects')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('reports.index', compact('projects', 'ratingBaik', 'ratingMod', 'good', 'moderate', 'weak'));
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
     * Report generation is no longer gated on inspection completeness — a
     * project can generate its E-IDS report at any stage.
     */
    private function reportBlockedReason(Project $project): ?string
    {
        return null;
    }
}
