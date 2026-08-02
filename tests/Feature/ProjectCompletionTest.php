<?php

namespace Tests\Feature;

use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function fullyInspectedProject(array $overrides = []): Project
    {
        $project = Project::factory()->create(array_merge(['calculated_samples' => 1], $overrides));
        $sample = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        foreach (array_keys(config('eids.components')) as $code) {
            ComponentAssessment::create([
                'project_id' => $project->id, 'sample_id' => $sample->id,
                'component_code' => $code, 'component_name' => $code, 'weightage' => 10,
                'overall_sample_status' => 'PASS',
            ]);
        }
        return $project->fresh();
    }

    public function test_admin_can_mark_a_ready_project_as_completed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = $this->fullyInspectedProject();

        $response = $this->actingAs($admin)->post("/projects/{$project->id}/complete");

        $response->assertRedirect("/projects/{$project->id}");
        $response->assertSessionHas('success');
        $this->assertSame('selesai', $project->fresh()->status);
    }

    public function test_cannot_complete_when_inspection_is_not_finished(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($admin)->post("/projects/{$project->id}/complete");

        $response->assertSessionHas('error');
        $this->assertNotSame('selesai', $project->fresh()->status);
    }

    public function test_cannot_complete_while_defects_are_still_open(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = $this->fullyInspectedProject();
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);

        $response = $this->actingAs($admin)->post("/projects/{$project->id}/complete");

        $response->assertSessionHas('error');
        $this->assertNotSame('selesai', $project->fresh()->status);
    }

    public function test_inspector_cannot_mark_a_project_as_completed(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->fullyInspectedProject(['assigned_to' => $inspector->id]);

        $response = $this->actingAs($inspector)->post("/projects/{$project->id}/complete");

        $response->assertForbidden();
        $this->assertNotSame('selesai', $project->fresh()->status);
    }

    public function test_editing_a_project_cannot_manually_set_status_to_completed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['status' => 'dalam_pemeriksaan']);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($admin)->put("/projects/{$project->id}", [
            'project_no'      => $project->project_no,
            'project_name'    => $project->project_name,
            'developer_name'  => $project->developer_name,
            'contractor_name' => $project->contractor_name,
            'building_type'   => $project->building_type,
            'total_units'     => $project->total_units,
            'floor_area_sqm'  => $project->floor_area_sqm,
            'status'          => 'selesai',
        ]);

        $response->assertRedirect("/projects/{$project->id}");
        $this->assertSame('dalam_pemeriksaan', $project->fresh()->status);
    }

    public function test_creating_a_project_cannot_start_as_completed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/projects', [
            'project_no'      => 'TEST-001',
            'project_name'    => 'Test Project',
            'developer_name'  => 'Dev Co',
            'contractor_name' => 'Contractor Co',
            'building_type'   => 'teres',
            'total_units'     => 10,
            'floor_area_sqm'  => 100,
            'status'          => 'selesai',
        ]);

        $response->assertSessionHasErrors('status');
    }
}
