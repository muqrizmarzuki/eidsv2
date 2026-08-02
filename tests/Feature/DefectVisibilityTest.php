<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_defect_visibility_follows_its_project(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $ownProject = Project::factory()->create(['assigned_contractor_id' => $contractor->id]);
        $otherProject = Project::factory()->create();

        Defect::factory()->create(['project_id' => $ownProject->id]);
        Defect::factory()->create(['project_id' => $otherProject->id]);

        $visible = Defect::visibleTo($contractor)->get();
        $this->assertCount(1, $visible);
        $this->assertSame($ownProject->id, $visible->first()->project_id);
    }
}
