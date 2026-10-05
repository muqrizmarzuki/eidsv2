<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DefectRectificationProofTest extends TestCase
{
    use RefreshDatabase;

    private function setupProjectWithContractor(): array
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $inspector  = User::factory()->create(['role' => 'inspector']);
        $project    = Project::factory()->create([
            'assigned_contractor_id' => $contractor->id,
            'created_by'             => $inspector->id,
        ]);

        $defect = Defect::create([
            'project_id'         => $project->id,
            'component_name'     => 'A1_FLOOR - Kemasan Lantai',
            'location'           => 'Ruang Tamu',
            'defect_description' => 'Hollow tiles near entrance door',
            'severity'           => 'medium',
            'status'             => 'IN_PROGRESS',
        ]);

        return [$contractor, $inspector, $project, $defect];
    }

    public function test_contractor_can_attach_rectification_photos_and_notes_when_marking_settled(): void
    {
        Storage::fake('public');
        [$contractor, , , $defect] = $this->setupProjectWithContractor();

        $photo1 = UploadedFile::fake()->image('after1.jpg', 800, 600);
        $photo2 = UploadedFile::fake()->image('after2.jpg', 800, 600);

        $response = $this->actingAs($contractor)->post(route('defects.advance', $defect), [
            'to'                   => 'PENDING_VERIFICATION',
            'rectification_notes'  => 'Tiles replaced, re-grouted and cleaned.',
            'rectification_photos' => [$photo1, $photo2],
        ]);

        $response->assertRedirect();
        $defect->refresh();

        $this->assertEquals('PENDING_VERIFICATION', $defect->status);
        $this->assertEquals('Tiles replaced, re-grouted and cleaned.', $defect->rectification_notes);
        $this->assertCount(2, $defect->getMedia('rectification_photos'));
        $this->assertTrue($defect->has_rectification);
        $this->assertCount(2, $defect->rectification_photo_urls);
    }

    public function test_contractor_can_delete_their_rectification_photo(): void
    {
        Storage::fake('public');
        [$contractor, , , $defect] = $this->setupProjectWithContractor();

        $photo = UploadedFile::fake()->image('after.jpg', 800, 600);
        $media = $defect->addMedia($photo)->toMediaCollection('rectification_photos');

        $this->assertCount(1, $defect->fresh()->getMedia('rectification_photos'));

        $response = $this->actingAs($contractor)->delete(route('media.destroy', $media));
        $response->assertRedirect();

        $this->assertCount(0, $defect->fresh()->getMedia('rectification_photos'));
    }

    public function test_contractor_cannot_delete_rectification_photo_once_defect_is_resolved(): void
    {
        Storage::fake('public');
        [$contractor, , , $defect] = $this->setupProjectWithContractor();

        $photo = UploadedFile::fake()->image('after.jpg', 800, 600);
        $media = $defect->addMedia($photo)->toMediaCollection('rectification_photos');

        $defect->update(['status' => 'RESOLVED']);

        $response = $this->actingAs($contractor)->delete(route('media.destroy', $media));
        $response->assertForbidden();

        $this->assertCount(1, $defect->fresh()->getMedia('rectification_photos'));
    }

    public function test_before_and_after_photos_are_separated_by_collection(): void
    {
        Storage::fake('public');
        [, , , $defect] = $this->setupProjectWithContractor();

        $beforePhoto = UploadedFile::fake()->image('before.jpg', 800, 600);
        $afterPhoto  = UploadedFile::fake()->image('after.jpg', 800, 600);

        $defect->addMedia($beforePhoto)->toMediaCollection('photos');
        $defect->addMedia($afterPhoto)->toMediaCollection('rectification_photos');

        $defect->refresh();

        $this->assertCount(1, $defect->before_photos);
        $this->assertCount(1, $defect->after_photos);
        $this->assertEquals('before.jpg', $defect->before_photos->first()->file_name);
        $this->assertEquals('after.jpg', $defect->after_photos->first()->file_name);
    }
}
