<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAssignedContractorTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_has_an_assigned_contractor_relation(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::create([
            'project_no'              => 'PRJ-TEST-001',
            'project_name'            => 'Test Project',
            'developer_name'          => 'Dev Co',
            'contractor_name'         => 'Contractor Co',
            'building_type'           => 'teres',
            'total_units'             => 10,
            'floor_area_sqm'          => 100,
            'calculated_samples'      => 2,
            'status'                  => 'draf',
            'created_by'              => $contractor->id,
            'assigned_contractor_id'  => $contractor->id,
        ]);

        $this->assertTrue($project->assignedContractor->is($contractor));
    }
}
