<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectAdvanceStatusTest extends TestCase
{
    use RefreshDatabase;

    private function defectFor(User $contractor): Defect
    {
        $project = Project::factory()->create(['assigned_contractor_id' => $contractor->id]);
        return Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);
    }

    public function test_contractor_can_start_repair(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $defect = $this->defectFor($contractor);

        $this->actingAs($contractor)
            ->post("/defects/{$defect->id}/advance", ['to' => 'IN_PROGRESS'])
            ->assertRedirect();

        $this->assertSame('IN_PROGRESS', $defect->fresh()->status);
    }

    public function test_contractor_can_mark_settled(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $defect = $this->defectFor($contractor);
        $defect->update(['status' => 'IN_PROGRESS']);

        $this->actingAs($contractor)
            ->post("/defects/{$defect->id}/advance", ['to' => 'PENDING_VERIFICATION'])
            ->assertRedirect();

        $this->assertSame('PENDING_VERIFICATION', $defect->fresh()->status);
    }

    public function test_contractor_cannot_confirm_resolved(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $defect = $this->defectFor($contractor);
        $defect->update(['status' => 'PENDING_VERIFICATION']);

        $this->actingAs($contractor)
            ->post("/defects/{$defect->id}/advance", ['to' => 'RESOLVED'])
            ->assertForbidden();

        $this->assertSame('PENDING_VERIFICATION', $defect->fresh()->status);
    }

    public function test_inspector_can_confirm_resolved_from_pending_verification(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        $defect = Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $this->actingAs($inspector)
            ->post("/defects/{$defect->id}/advance", ['to' => 'RESOLVED'])
            ->assertRedirect();

        $this->assertSame('RESOLVED', $defect->fresh()->status);
    }

    public function test_inspector_can_reject_from_pending_verification_back_to_in_progress(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        $defect = Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $this->actingAs($inspector)
            ->post("/defects/{$defect->id}/advance", ['to' => 'IN_PROGRESS'])
            ->assertRedirect();

        $this->assertSame('IN_PROGRESS', $defect->fresh()->status);
    }

    public function test_cannot_skip_a_state(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        $defect = Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);

        $this->actingAs($inspector)
            ->post("/defects/{$defect->id}/advance", ['to' => 'RESOLVED'])
            ->assertStatus(422);

        $this->assertSame('OPEN', $defect->fresh()->status);
    }
}
