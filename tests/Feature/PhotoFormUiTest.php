<?php

namespace Tests\Feature;

use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Database\Seeders\ChecklistItemSeeder;
use Database\Seeders\WeightageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The upload controls take several files at once, and every photo already on a
 * record is shown with its own delete control.
 */
class PhotoFormUiTest extends TestCase
{
    use RefreshDatabase;

    private User $inspector;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([WeightageSeeder::class, ChecklistItemSeeder::class]);

        Storage::fake('r2');
        config(['media-library.disk_name' => 'r2']);

        $this->inspector = User::factory()->create(['role' => 'inspector']);
        $this->project   = Project::factory()->create(['assigned_to' => $this->inspector->id]);
    }

    private function addPhotos($model, int $count): void
    {
        foreach (range(1, $count) as $i) {
            $model->addMedia(UploadedFile::fake()->image("p{$i}.jpg"))
                ->preservingOriginal()
                ->toMediaCollection('photos');
        }
    }

    public function test_defect_create_form_accepts_several_photos_at_once(): void
    {
        $html = $this->actingAs($this->inspector)->get('/defects/create')->assertOk()->getContent();

        $this->assertStringContainsString('name="photos[]"', $html);
        $this->assertStringContainsString('multiple', $html);
    }

    public function test_defect_edit_form_offers_a_delete_control_per_photo(): void
    {
        $defect = Defect::factory()->create(['project_id' => $this->project->id]);
        $this->addPhotos($defect, 3);

        $html = $this->actingAs($this->inspector)
            ->get("/defects/{$defect->id}/edit")->assertOk()->getContent();

        $this->assertStringContainsString('name="photos[]"', $html);
        $this->assertSame(3, substr_count($html, 'data-delete-photo="'));

        foreach ($defect->getMedia('photos') as $media) {
            $this->assertStringContainsString('data-delete-photo="' . $media->id . '"', $html);
        }
    }

    public function test_inspection_form_lists_existing_photos_with_delete_controls(): void
    {
        $sample = ProjectSample::create([
            'project_id'    => $this->project->id,
            'sample_index'  => 1,
            'location_name' => 'Living Room',
        ]);
        $code = $this->project->roomBasedComponentCodes()[0];

        $assessment = ComponentAssessment::create([
            'project_id'     => $this->project->id,
            'sample_id'      => $sample->id,
            'component_code' => $code,
        ]);
        $this->addPhotos($assessment, 2);

        $html = $this->actingAs($this->inspector)
            ->get("/projects/{$this->project->id}/inspect/{$sample->id}?component={$code}")
            ->assertOk()->getContent();

        $this->assertStringContainsString('name="photos[]"', $html);
        $this->assertSame(2, substr_count($html, 'data-delete-photo="'));
    }
}
