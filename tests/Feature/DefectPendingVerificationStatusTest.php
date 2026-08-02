<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectPendingVerificationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_defect_status_column_accepts_pending_verification(): void
    {
        $creator = User::factory()->create(['role' => 'inspector']);
        $project = Project::create([
            'project_no' => 'PRJ-TEST-003', 'project_name' => 'Test',
            'developer_name' => 'Dev', 'contractor_name' => 'Con',
            'building_type' => 'teres', 'total_units' => 10, 'floor_area_sqm' => 100,
            'calculated_samples' => 1, 'status' => 'draf', 'created_by' => $creator->id,
        ]);

        $defect = Defect::create([
            'project_id'         => $project->id,
            'component_name'     => 'D1',
            'location'           => 'Living Room',
            'defect_description' => 'Test defect',
            'severity'           => 'low',
            'status'             => 'PENDING_VERIFICATION',
        ]);

        $this->assertSame('PENDING_VERIFICATION', $defect->fresh()->status);
    }
}
