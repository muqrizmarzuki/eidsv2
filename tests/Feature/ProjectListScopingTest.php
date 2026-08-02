<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectListScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_index_only_shows_visible_projects(): void
    {
        $inspectorA = User::factory()->create(['role' => 'inspector']);
        $inspectorB = User::factory()->create(['role' => 'inspector']);
        $mine = Project::factory()->create(['assigned_to' => $inspectorA->id, 'project_name' => 'Mine']);
        Project::factory()->create(['assigned_to' => $inspectorB->id, 'project_name' => 'Not Mine']);

        $response = $this->actingAs($inspectorA)->get('/projects');

        $response->assertSee('Mine');
        $response->assertDontSee('Not Mine');
    }

    public function test_dashboard_totals_are_scoped(): void
    {
        $inspectorA = User::factory()->create(['role' => 'inspector']);
        $inspectorB = User::factory()->create(['role' => 'inspector']);
        Project::factory()->create(['assigned_to' => $inspectorA->id]);
        Project::factory()->create(['assigned_to' => $inspectorB->id]);

        $response = $this->actingAs($inspectorA)->get('/dashboard');

        $response->assertViewHas('total', 1);
    }
}
