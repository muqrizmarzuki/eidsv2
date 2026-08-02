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

    public function test_admin_can_assign_a_contractor_on_create(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contractor = User::factory()->create(['role' => 'contractor']);

        $this->actingAs($admin)->post('/projects', $this->validPayload([
            'assigned_contractor_id' => $contractor->id,
        ]));

        $project = Project::where('project_no', 'PRJ-FORM-001')->firstOrFail();
        $this->assertSame($contractor->id, $project->assigned_contractor_id);
    }

    public function test_inspector_can_set_contractor_but_not_assigned_to_via_edit(): void
    {
        $inspectorA = User::factory()->create(['role' => 'inspector']);
        $inspectorB = User::factory()->create(['role' => 'inspector']);
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::factory()->create(['assigned_to' => $inspectorA->id]);

        $this->actingAs($inspectorA)->put("/projects/{$project->id}", $this->validPayload([
            'project_no'             => $project->project_no,
            'assigned_contractor_id' => $contractor->id,
            'assigned_to'            => $inspectorB->id,
        ]));

        $fresh = $project->fresh();
        $this->assertSame($contractor->id, $fresh->assigned_contractor_id, 'Inspector should be able to set the Assigned Contractor.');
        $this->assertSame($inspectorA->id, $fresh->assigned_to, 'Inspector must not be able to reassign the project to someone else.');
    }
}
