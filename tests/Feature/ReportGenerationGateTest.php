<?php

namespace Tests\Feature;

use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportGenerationGateTest extends TestCase
{
    use RefreshDatabase;

    private function fullyInspectedProject(array $overrides = []): Project
    {
        $project = Project::factory()->create(array_merge(['calculated_samples' => 1], $overrides));
        $sample = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        foreach (array_keys(config('eids.components')) as $code) {
            ComponentAssessment::create([
                'project_id' => $project->id, 'sample_id' => $sample->id,
                'component_code' => $code, 'component_name' => $code, 'weightage' => 10,
                'overall_sample_status' => 'PASS',
            ]);
        }
        return $project->fresh();
    }

    public function test_report_is_blocked_when_inspection_is_not_finished(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($admin)->get("/reports/{$project->id}");

        $response->assertRedirect("/projects/{$project->id}");
        $response->assertSessionHas('error');
    }

    public function test_report_pdf_is_available_even_when_defects_are_still_open(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = $this->fullyInspectedProject();
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);

        $response = $this->actingAs($admin)->get("/reports/{$project->id}/pdf");

        $response->assertOk();
    }

    public function test_report_is_available_once_inspection_is_done_and_defects_resolved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = $this->fullyInspectedProject();

        $response = $this->actingAs($admin)->get("/reports/{$project->id}");

        $response->assertOk();
    }
}
