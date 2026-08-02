<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectAccessGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspector_cannot_edit_a_defect_outside_their_visibility(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $otherProject = Project::factory()->create();
        $defect = Defect::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($inspector)->get("/defects/{$defect->id}/edit")->assertForbidden();
    }

    public function test_inspector_cannot_store_a_defect_against_an_invisible_project(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $foreignProject = Project::factory()->create();

        $response = $this->actingAs($inspector)->post('/defects', [
            'project_id'         => $foreignProject->id,
            'component_name'     => 'D1',
            'location'           => 'Living Room',
            'defect_description' => 'Injected defect',
            'severity'           => 'low',
            'status'             => 'OPEN',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('defects', ['defect_description' => 'Injected defect']);
    }

    public function test_inspector_cannot_reparent_a_defect_into_an_invisible_project(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $mineProject = Project::factory()->create(['assigned_to' => $inspector->id]);
        $foreignProject = Project::factory()->create();
        $defect = Defect::factory()->create(['project_id' => $mineProject->id]);

        $response = $this->actingAs($inspector)->put("/defects/{$defect->id}", [
            'project_id'         => $foreignProject->id,
            'component_name'     => 'D1',
            'location'           => 'Living Room',
            'defect_description' => 'Sample defect',
            'severity'           => 'low',
        ]);

        $response->assertForbidden();
        $this->assertSame($mineProject->id, $defect->fresh()->project_id);
    }

    public function test_contractor_cannot_advance_a_defect_on_a_project_they_are_not_assigned_to(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $foreignProject = Project::factory()->create();
        $defect = Defect::factory()->create([
            'project_id' => $foreignProject->id,
            'status'     => 'OPEN',
        ]);

        $response = $this->actingAs($contractor)
            ->post("/defects/{$defect->id}/advance", ['to' => 'IN_PROGRESS']);

        $response->assertForbidden();
        $this->assertSame('OPEN', $defect->fresh()->status);
    }

    public function test_contractor_can_advance_a_defect_on_their_own_project(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::factory()->create(['assigned_contractor_id' => $contractor->id]);
        $defect = Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);

        $this->actingAs($contractor)
            ->post("/defects/{$defect->id}/advance", ['to' => 'IN_PROGRESS']);

        $this->assertSame('IN_PROGRESS', $defect->fresh()->status);
    }

    public function test_inspector_cannot_change_status_through_the_edit_form(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        $defect = Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);

        $response = $this->actingAs($inspector)->put("/defects/{$defect->id}", [
            'project_id'         => $project->id,
            'component_name'     => 'Renamed Component',
            'location'           => 'Kitchen',
            'defect_description' => 'Updated description',
            'severity'           => 'high',
            'status'             => 'RESOLVED',
        ]);

        $response->assertRedirect(route('defects.index'));

        $defect->refresh();
        $this->assertSame('OPEN', $defect->status, 'Status must only change via advanceStatus().');
        $this->assertSame('Renamed Component', $defect->component_name);
        $this->assertSame('high', $defect->severity);
    }
}
