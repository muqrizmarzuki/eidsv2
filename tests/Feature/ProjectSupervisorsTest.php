<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSupervisorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_project_can_have_multiple_supervisors(): void
    {
        $creator = User::factory()->create(['role' => 'lead_auditor']);
        $supervisorA = User::factory()->create(['role' => 'supervisor']);
        $supervisorB = User::factory()->create(['role' => 'supervisor']);

        $project = Project::create([
            'project_no'         => 'PRJ-TEST-002',
            'project_name'       => 'Test Project',
            'developer_name'     => 'Dev Co',
            'contractor_name'    => 'Contractor Co',
            'building_type'      => 'teres',
            'total_units'        => 10,
            'floor_area_sqm'     => 100,
            'calculated_samples' => 2,
            'status'             => 'draf',
            'created_by'         => $creator->id,
        ]);

        $project->supervisors()->sync([$supervisorA->id, $supervisorB->id]);

        $this->assertCount(2, $project->fresh()->supervisors);
        $this->assertTrue($project->fresh()->supervisors->contains($supervisorA));
    }
}
