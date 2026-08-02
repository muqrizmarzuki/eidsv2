<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectShowBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_the_next_action_banner_on_project_show(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($admin)->get("/projects/{$project->id}");

        $response->assertSee('Finish naming sample locations');
    }

    public function test_admin_stepper_marks_grid_as_handled_by_inspector(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();

        $response = $this->actingAs($admin)->get("/projects/{$project->id}");

        $response->assertSee('Handled by Inspector');
    }

    public function test_inspector_stepper_does_not_show_handled_by_inspector_label(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);

        $response = $this->actingAs($inspector)->get("/projects/{$project->id}");

        $response->assertDontSee('Handled by Inspector');
    }
}
