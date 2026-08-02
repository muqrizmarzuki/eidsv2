<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAssignmentFormTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'project_no'      => 'PRJ-FORM-001',
            'project_name'    => 'Form Test',
            'developer_name'  => 'Dev Co',
            'contractor_name' => 'Contractor Co',
            'building_type'   => 'teres',
            'total_units'     => 10,
            'floor_area_sqm'  => 100,
            'status'          => 'draf',
        ], $overrides);
    }

    public function test_admin_can_assign_a_contractor_and_supervisors_on_create(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contractor = User::factory()->create(['role' => 'contractor']);
        $supervisorA = User::factory()->create(['role' => 'supervisor']);
        $supervisorB = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($admin)->post('/projects', $this->validPayload([
            'assigned_contractor_id' => $contractor->id,
            'supervisor_ids'         => [$supervisorA->id, $supervisorB->id],
        ]));

        $project = Project::where('project_no', 'PRJ-FORM-001')->firstOrFail();
        $this->assertSame($contractor->id, $project->assigned_contractor_id);
        $this->assertCount(2, $project->supervisors);
    }

    public function test_inspector_cannot_set_contractor_or_supervisors_via_edit(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);

        $this->actingAs($inspector)->put("/projects/{$project->id}", $this->validPayload([
            'project_no'             => $project->project_no,
            'assigned_contractor_id' => $contractor->id,
        ]));

        $this->assertNull($project->fresh()->assigned_contractor_id);
    }
}
