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

    public function test_supervisor_sees_the_banner_but_no_interactive_stepper(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = Project::factory()->create();
        $project->supervisors()->attach($supervisor->id);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($supervisor)->get("/projects/{$project->id}");

        $response->assertOk();
        // Read-only status line (nextActionFor banner) still renders.
        $response->assertSee('sample units inspected');
        // None of the stepper's step labels render. ("G-IDS Score" is deliberately not
        // asserted on — that string also appears in non-stepper content on this page.)
        $response->assertDontSee('Sample Setup');
        $response->assertDontSee('Components Grid');
        $response->assertDontSee('Handled by Inspector');
        // Nor any of the stepper's navigation links.
        $response->assertDontSee('href="' . route('projects.samples', $project) . '"', false);
        $response->assertDontSee('href="' . route('projects.components', $project) . '"', false);
    }

    public function test_inspector_still_sees_the_interactive_stepper(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);

        $response = $this->actingAs($inspector)->get("/projects/{$project->id}");

        $response->assertSee('Sample Setup');
    }

    public function test_supervisor_sees_no_interactive_stepper_or_grid_link_on_score_page(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = Project::factory()->create();
        $project->supervisors()->attach($supervisor->id);
        $sample = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($supervisor)->get("/projects/{$project->id}/score");

        // Supervisor IS allowed on the score page itself, per route middleware.
        $response->assertOk();
        // None of the stepper's step labels render.
        $response->assertDontSee('Sample Setup');
        $response->assertDontSee('Components Grid');
        // Nor any of the stepper's / quick-link's navigation links.
        $response->assertDontSee('href="' . route('projects.components', $project) . '"', false);
        $response->assertDontSee('href="' . route('projects.samples', $project) . '"', false);
        $response->assertDontSee('href="' . route('projects.inspect', [$project, $sample]) . '"', false);
    }

    public function test_inspector_still_sees_the_interactive_stepper_on_score_page(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);

        $response = $this->actingAs($inspector)->get("/projects/{$project->id}/score");

        $response->assertOk();
        $response->assertSee('Components Grid');
    }
}
