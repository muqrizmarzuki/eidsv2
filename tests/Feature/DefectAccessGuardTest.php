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
}
