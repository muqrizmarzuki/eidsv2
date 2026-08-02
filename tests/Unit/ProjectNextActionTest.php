<?php

namespace Tests\Unit;

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
        $creator = User::factory()->create(['role' => 'lead_auditor']);
        return Project::factory()->create(array_merge(['created_by' => $creator->id], $overrides));
    }

    public function test_admin_is_told_to_finish_naming_locations_when_still_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = $this->baseProject();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('naming sample locations', $action['text']);
    }

    public function test_admin_is_told_inspection_is_pending_once_locations_are_named(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector', 'name' => 'Aiman']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('Aiman', $action['text']);
    }

    public function test_inspector_is_told_to_start_the_grid_when_nothing_inspected(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('Start the Components Grid', $action['text']);
    }

    public function test_inspector_sees_pending_verification_count(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('awaiting your verification', $action['text']);
    }

    public function test_supervisor_sees_a_read_only_progress_line(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = $this->baseProject();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($supervisor);

        $this->assertStringContainsString('sample units inspected', $action['text']);
    }
}
