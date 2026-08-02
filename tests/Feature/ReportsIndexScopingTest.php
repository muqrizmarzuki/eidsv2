<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsIndexScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_only_shows_visible_scored_projects(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);

        Project::factory()->create([
            'assigned_to'   => $inspector->id,
            'project_name'  => 'Assigned Scored Residence',
            'project_no'    => 'PRJ-MINE-1',
            'overall_score' => 88.5,
        ]);

        Project::factory()->create([
            'project_name'  => 'Foreign Scored Residence',
            'project_no'    => 'PRJ-OTHER-1',
            'overall_score' => 91.2,
        ]);

        $response = $this->actingAs($inspector)->get('/reports');

        $response->assertOk();
        $response->assertSee('Assigned Scored Residence');
        $response->assertSee('PRJ-MINE-1');
        $response->assertDontSee('Foreign Scored Residence');
        $response->assertDontSee('PRJ-OTHER-1');
    }

    public function test_admin_still_sees_every_scored_project_on_reports_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Project::factory()->create(['project_name' => 'Alpha Scored', 'overall_score' => 70.0]);
        Project::factory()->create(['project_name' => 'Beta Scored', 'overall_score' => 80.0]);

        $response = $this->actingAs($admin)->get('/reports');

        $response->assertOk();
        $response->assertSee('Alpha Scored');
        $response->assertSee('Beta Scored');
    }
}
