<?php

namespace Tests\Feature;

use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Photos are deleted one at a time, from whichever record you are looking at.
 * An assessment and the defect it raised hold their own copies of the same
 * upload, and deleting one side deliberately leaves the other alone.
 */
class PhotoDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $inspector;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('r2');
        config(['media-library.disk_name' => 'r2']);

        $this->inspector = User::factory()->create(['role' => 'inspector']);
        $this->project   = Project::factory()->create(['assigned_to' => $this->inspector->id]);
    }

    private function assessmentWithPhotos(int $count): ComponentAssessment
    {
        $assessment = ComponentAssessment::create([
            'project_id'     => $this->project->id,
            'component_code' => 'A1_FLOOR',
        ]);

        foreach (range(1, $count) as $i) {
            $assessment->addMedia(UploadedFile::fake()->image("photo{$i}.jpg"))
                ->preservingOriginal()
                ->toMediaCollection('photos');
        }

        return $assessment->refresh();
    }

    private function defectWithPhoto(): Defect
    {
        $defect = Defect::factory()->create(['project_id' => $this->project->id]);

        $defect->addMedia(UploadedFile::fake()->image('defect.jpg'))
            ->preservingOriginal()
            ->toMediaCollection('photos');

        return $defect->refresh();
    }

    public function test_inspector_deletes_one_assessment_photo_and_keeps_the_rest(): void
    {
        $assessment = $this->assessmentWithPhotos(3);
        $target     = $assessment->getMedia('photos')[1];

        $this->actingAs($this->inspector)
            ->delete("/media/{$target->id}")
            ->assertRedirect();

        $this->assertSame(
            ['photo1.jpg', 'photo3.jpg'],
            $assessment->refresh()->getMedia('photos')->pluck('file_name')->all()
        );
        $this->assertNull(Media::find($target->id));
        $this->assertFalse(Storage::disk('r2')->exists($target->getPathRelativeToRoot()));
    }

    public function test_deleting_an_assessment_photo_leaves_the_defects_copy_alone(): void
    {
        $assessment = $this->assessmentWithPhotos(1);
        $defect     = Defect::factory()->create([
            'project_id'    => $this->project->id,
            'assessment_id' => $assessment->id,
        ]);
        $assessment->getFirstMedia('photos')->copy($defect, 'photos');

        $this->actingAs($this->inspector)
            ->delete("/media/{$assessment->getFirstMedia('photos')->id}")
            ->assertRedirect();

        $this->assertCount(0, $assessment->refresh()->getMedia('photos'));
        $this->assertCount(1, $defect->refresh()->getMedia('photos'));
    }

    public function test_inspector_deletes_a_defect_photo(): void
    {
        $defect = $this->defectWithPhoto();

        $this->actingAs($this->inspector)
            ->delete("/media/{$defect->getFirstMedia('photos')->id}")
            ->assertRedirect();

        $this->assertCount(0, $defect->refresh()->getMedia('photos'));
    }

    public function test_contractor_cannot_delete_photo_evidence(): void
    {
        $defect     = $this->defectWithPhoto();
        $contractor = User::factory()->create(['role' => 'contractor']);
        $this->project->update(['assigned_contractor_id' => $contractor->id]);

        $this->actingAs($contractor)
            ->delete("/media/{$defect->getFirstMedia('photos')->id}")
            ->assertForbidden();

        $this->assertCount(1, $defect->refresh()->getMedia('photos'));
    }

    public function test_inspector_cannot_delete_a_photo_on_someone_elses_project(): void
    {
        $defect = $this->defectWithPhoto();
        $other  = User::factory()->create(['role' => 'inspector']);

        $this->actingAs($other)
            ->delete("/media/{$defect->getFirstMedia('photos')->id}")
            ->assertForbidden();

        $this->assertCount(1, $defect->refresh()->getMedia('photos'));
    }

    public function test_deleting_a_photo_frees_a_slot_for_a_new_one(): void
    {
        $defect = Defect::factory()->create(['project_id' => $this->project->id]);
        foreach (range(1, 3) as $i) {
            $defect->addMedia(UploadedFile::fake()->image("p{$i}.jpg"))
                ->preservingOriginal()
                ->toMediaCollection('photos');
        }

        $payload = [
            'project_id'         => $this->project->id,
            'component_name'     => 'Floor',
            'location'           => 'Living Room',
            'defect_description' => 'Cracked tile',
            'severity'           => 'low',
            'photos'             => [UploadedFile::fake()->image('fourth.jpg')],
        ];

        $this->actingAs($this->inspector)
            ->put("/defects/{$defect->id}", $payload)
            ->assertSessionHasErrors('photos');

        $this->actingAs($this->inspector)
            ->delete("/media/{$defect->refresh()->getFirstMedia('photos')->id}")
            ->assertRedirect();

        $this->actingAs($this->inspector)
            ->put("/defects/{$defect->id}", $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['p2.jpg', 'p3.jpg', 'fourth.jpg'],
            $defect->refresh()->getMedia('photos')->pluck('file_name')->all()
        );
    }
}
