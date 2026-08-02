<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectListScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_defects_index_only_shows_defects_for_visible_projects(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $mineProject = Project::factory()->create(['assigned_to' => $inspector->id]);
        $otherProject = Project::factory()->create();

        Defect::factory()->create(['project_id' => $mineProject->id, 'component_name' => 'Mine D1']);
        Defect::factory()->create(['project_id' => $otherProject->id, 'component_name' => 'Not Mine D2']);

        $response = $this->actingAs($inspector)->get('/defects');

        $response->assertSee('Mine D1');
        $response->assertDontSee('Not Mine D2');
    }

    public function test_contractor_project_filter_dropdown_only_lists_their_own_project(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $mineProject = Project::factory()->create([
            'assigned_contractor_id' => $contractor->id,
            'project_name'           => 'Contractor Own Residence',
            'project_no'             => 'PRJ-OWN-1',
        ]);
        Project::factory()->create([
            'project_name' => 'Foreign Register Residence',
            'project_no'   => 'PRJ-FOREIGN-1',
        ]);

        Defect::factory()->create(['project_id' => $mineProject->id]);

        $response = $this->actingAs($contractor)->get('/defects');

        $response->assertOk();
        $response->assertViewHas('projects', function ($projects) {
            return $projects->count() === 1 && $projects->first()->project_no === 'PRJ-OWN-1';
        });
        $response->assertDontSee('Foreign Register Residence');
        $response->assertDontSee('PRJ-FOREIGN-1');
    }

    public function test_contractor_does_not_get_a_link_to_projects_show(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::factory()->create([
            'assigned_contractor_id' => $contractor->id,
            'project_no'             => 'PRJ-OWN-2',
        ]);
        Defect::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($contractor)->get('/defects');

        $response->assertOk();
        $response->assertSee('PRJ-OWN-2');
        $response->assertDontSee('href="' . route('projects.show', $project) . '"', false);
    }
}
