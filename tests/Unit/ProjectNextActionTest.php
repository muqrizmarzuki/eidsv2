<?php

namespace Tests\Unit;

use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectNextActionTest extends TestCase
{
    use RefreshDatabase;

    private function baseProject(array $overrides = []): Project
    {
        $creator = User::factory()->create(['role' => 'admin']);
        return Project::factory()->create(array_merge(['created_by' => $creator->id], $overrides));
    }

    public function test_admin_is_told_to_finish_naming_locations_when_still_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = $this->baseProject();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('naming sample locations', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('projects.samples', $action['route']);
        $this->assertSame('Configure Samples', $action['button_label']);
    }

    public function test_admin_is_told_inspection_is_pending_once_locations_are_named(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector', 'name' => 'Aiman']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('Aiman', $action['text']);
        $this->assertFalse($action['actionable']);
        $this->assertNull($action['route']);
        $this->assertNull($action['button_label']);
    }

    public function test_admin_is_not_told_inspection_is_complete_when_only_partially_inspected(): void
    {
        // Regression: with zero defects raised so far (nothing has FAILed yet) and only
        // 1 of 16 components assessed, the admin must NOT be told inspection is complete
        // and offered to generate the signed PDF — that requires inspection_progress = 100.
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id, 'calculated_samples' => 2]);
        $sample1 = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 2, 'location_name' => 'Kitchen']);
        ComponentAssessment::create([
            'project_id' => $project->id, 'sample_id' => $sample1->id,
            'component_code' => 'A1_FLOOR', 'component_name' => 'Floor', 'weightage' => 18,
            'overall_sample_status' => 'PASS',
        ]);

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertFalse($action['actionable']);
        $this->assertStringNotContainsString('Inspection complete', $action['text']);
        $this->assertNull($action['route']);
    }

    public function test_admin_waiting_on_defects_is_not_actionable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id, 'calculated_samples' => 1]);
        $sample = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        foreach (array_keys(config('eids.components')) as $i => $code) {
            ComponentAssessment::create([
                'project_id' => $project->id, 'sample_id' => $sample->id,
                'component_code' => $code, 'component_name' => $code, 'weightage' => 10,
                'overall_sample_status' => $i === 0 ? 'FAIL' : 'PASS',
            ]);
        }
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('defect(s) still open', $action['text']);
        $this->assertFalse($action['actionable']);
    }

    public function test_admin_sees_generate_pdf_action_when_inspection_complete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id, 'calculated_samples' => 1]);
        $sample = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        foreach (array_keys(config('eids.components')) as $code) {
            ComponentAssessment::create([
                'project_id' => $project->id, 'sample_id' => $sample->id,
                'component_code' => $code, 'component_name' => $code, 'weightage' => 10,
                'overall_sample_status' => 'PASS',
            ]);
        }

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('Inspection complete', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('reports.show', $action['route']);
        $this->assertSame('Generate PDF Report', $action['button_label']);
    }

    public function test_inspector_is_told_to_start_the_grid_when_nothing_inspected(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('Start the Components Grid', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('projects.components', $action['route']);
        $this->assertSame('Start Inspecting Now', $action['button_label']);
    }

    public function test_inspector_sees_pending_verification_count(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('awaiting your verification', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('defects.index', $action['route']);
        $this->assertSame(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION'], $action['params']);
        $this->assertSame('Review Defects', $action['button_label']);
    }

    public function test_inspector_sees_continue_action_when_partially_inspected(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id, 'calculated_samples' => 2]);
        $sample1 = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 2, 'location_name' => 'Kitchen']);
        ComponentAssessment::create([
            'project_id' => $project->id, 'sample_id' => $sample1->id,
            'component_code' => 'A1_FLOOR', 'component_name' => 'Floor', 'weightage' => 18,
            'overall_sample_status' => 'PASS',
        ]);

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('Continue', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('projects.components', $action['route']);
        $this->assertSame('Continue Inspecting', $action['button_label']);
    }

    public function test_inspector_sees_view_score_action_when_inspection_complete(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id, 'calculated_samples' => 1]);
        $sample = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        foreach (array_keys(config('eids.components')) as $code) {
            ComponentAssessment::create([
                'project_id' => $project->id, 'sample_id' => $sample->id,
                'component_code' => $code, 'component_name' => $code, 'weightage' => 10,
                'overall_sample_status' => 'PASS',
            ]);
        }

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('notify your Admin', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('projects.score', $action['route']);
        $this->assertSame('View G-IDS Score', $action['button_label']);
    }

}
