<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectListScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_defects_index_only_shows_defects_for_visible_projects(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $mineProject = Project::factory()->create(['assigned_to' => $inspector->id]);
        $otherProject = Project::factory()->create();

        Defect::factory()->create(['project_id' => $mineProject->id, 'component_name' => 'Mine D1']);
        Defect::factory()->create(['project_id' => $otherProject->id, 'component_name' => 'Not Mine D2']);

        $response = $this->actingAs($inspector)->get('/defects');

        $response->assertSee('Mine D1');
        $response->assertDontSee('Not Mine D2');
    }
}
