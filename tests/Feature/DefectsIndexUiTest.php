<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectsIndexUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_contractor_sees_start_repair_button_on_open_defect(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::factory()->create(['assigned_contractor_id' => $contractor->id]);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);

        $response = $this->actingAs($contractor)->get('/defects');

        $response->assertSee('My Defects');
        $response->assertSee('Start Repair');
    }

    public function test_contractor_does_not_see_confirm_resolved_button(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::factory()->create(['assigned_contractor_id' => $contractor->id]);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $response = $this->actingAs($contractor)->get('/defects');

        $response->assertSee('Awaiting Verification');
        $response->assertDontSee('Confirm Resolved');
    }

    public function test_inspector_sees_confirm_and_reject_on_pending_verification(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $response = $this->actingAs($inspector)->get('/defects');

        $response->assertSee('Confirm Resolved');
        $response->assertSee('Reject');
    }
}
