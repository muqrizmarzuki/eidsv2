<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActionRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspector_sees_their_unstarted_project_in_action_required(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id, 'project_name' => 'Needs Action']);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $response = $this->actingAs($inspector)->get('/dashboard');

        $response->assertSee('Action Required');
        $response->assertSee('Needs Action');
    }

    public function test_supervisor_dashboard_has_no_action_required_widget(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $response = $this->actingAs($supervisor)->get('/dashboard');

        $response->assertDontSee('Action Required');
    }
}
