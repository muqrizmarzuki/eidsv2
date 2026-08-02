<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectStatusDisplayAndCountersTest extends TestCase
{
    use RefreshDatabase;

    private function projectWithOneOfEachStatus(User $owner): Project
    {
        $project = Project::factory()->create([
            'assigned_to'   => $owner->id,
            'overall_score' => 85.0,
        ]);

        foreach (['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION', 'RESOLVED'] as $status) {
            Defect::factory()->create(['project_id' => $project->id, 'status' => $status]);
        }

        return $project;
    }

    public function test_status_label_accessor_covers_all_four_states(): void
    {
        $expected = [
            'OPEN'                 => 'Open',
            'IN_PROGRESS'          => 'In Progress',
            'PENDING_VERIFICATION' => 'Pending Verification',
            'RESOLVED'             => 'Resolved',
        ];

        foreach ($expected as $status => $label) {
            $defect = Defect::factory()->make(['status' => $status]);
            $this->assertSame($label, $defect->status_label);
        }
    }

    public function test_dashboard_open_counter_includes_in_progress_and_pending_verification(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $this->projectWithOneOfEachStatus($inspector);

        $response = $this->actingAs($inspector)->get('/dashboard');

        $response->assertViewHas('openDefects', 3);
        $response->assertViewHas('resolvedDefects', 1);
    }

    public function test_project_show_open_counter_includes_in_progress_and_pending_verification(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->projectWithOneOfEachStatus($inspector);

        $response = $this->actingAs($inspector)->get("/projects/{$project->id}");

        $response->assertViewHas('openDefects', 3);
        $response->assertViewHas('resolvedDefects', 1);
    }

    public function test_report_show_counters_and_pending_verification_label(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->projectWithOneOfEachStatus($inspector);

        $response = $this->actingAs($inspector)->get("/reports/{$project->id}");

        $response->assertOk();
        $response->assertViewHas('openDefects', 3);
        $response->assertViewHas('resolvedDefects', 1);
        // I4: the defect table must render the friendly label, not the raw enum.
        $response->assertSee('Pending Verification');
        $response->assertDontSee('PENDING_VERIFICATION');
    }

    public function test_project_summary_counters_and_pending_verification_label(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->projectWithOneOfEachStatus($inspector);

        $response = $this->actingAs($inspector)->get("/projects/{$project->id}/summary");

        $response->assertOk();
        $response->assertViewHas('openDefects', 3);
        $response->assertViewHas('resolvedDefects', 1);
        $response->assertSee('Pending Verification');
        $response->assertDontSee('PENDING_VERIFICATION');
    }

    public function test_pdf_report_renders_pending_verification_label(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->projectWithOneOfEachStatus($inspector);

        // Render the PDF Blade directly — asserting on binary PDF output is brittle.
        $controller = new \ReflectionMethod(\App\Http\Controllers\ReportController::class, 'buildScoreData');
        $controller->setAccessible(true);
        $data = $controller->invoke(new \App\Http\Controllers\ReportController(), $project);

        $html = view('reports.pdf', array_merge(['project' => $project], $data))->render();

        $this->assertStringContainsString('Pending Verification', $html);
        $this->assertStringNotContainsString('PENDING_VERIFICATION', $html);
    }
}
