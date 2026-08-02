<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAccessGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspector_cannot_open_a_project_not_assigned_to_them_by_url(): void
    {
        $inspectorA = User::factory()->create(['role' => 'inspector']);
        $inspectorB = User::factory()->create(['role' => 'inspector']);
        $othersProject = Project::factory()->create(['assigned_to' => $inspectorB->id]);

        $this->actingAs($inspectorA)->get("/projects/{$othersProject->id}")->assertForbidden();
        $this->actingAs($inspectorA)->get("/projects/{$othersProject->id}/score")->assertForbidden();
        $this->actingAs($inspectorA)->get("/projects/{$othersProject->id}/summary")->assertForbidden();
    }

    public function test_inspector_can_open_their_own_assigned_project(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $ownProject = Project::factory()->create(['assigned_to' => $inspector->id]);

        $this->actingAs($inspector)->get("/projects/{$ownProject->id}")->assertOk();
    }

    public function test_admin_can_open_any_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();

        $this->actingAs($admin)->get("/projects/{$project->id}")->assertOk();
    }
}
