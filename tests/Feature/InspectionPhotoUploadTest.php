<?php

namespace Tests\Feature;

use App\Models\ChecklistItem;
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
 * Photos are stored in media library on the cloud media disk (R2 in
 * production) and nowhere else. The regression these tests guard: a single
 * upload has to feed both the assessment and the defect its FAIL creates.
 * PHP gives one request-scoped temp file per upload and media library moves
 * it onto the media disk, so attaching it twice used to blow up with
 * "File /tmp/phpXXXXXX does not exist".
 */
class InspectionPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $inspector;
    private Project $project;
    private ProjectSample $sample;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([WeightageSeeder::class, ChecklistItemSeeder::class]);

        // The media disk stands in for R2; the local ones must stay untouched.
        Storage::fake('r2');
        Storage::fake('public');
        Storage::fake('local');
        config(['media-library.disk_name' => 'r2']);

        $this->inspector = User::factory()->create(['role' => 'inspector']);
        $this->project   = Project::factory()->create(['assigned_to' => $this->inspector->id]);
        $this->sample    = ProjectSample::create([
            'project_id'    => $this->project->id,
            'sample_index'  => 1,
            'location_name' => 'Living Room',
        ]);
    }

    private function componentCode(): string
    {
        return $this->project->roomBasedComponentCodes()[0];
    }

    /** @return array<int, string> answers keyed by checklist item id */
    private function answers(string $result): array
    {
        return ChecklistItem::forComponent($this->componentCode())
            ->get()
            ->mapWithKeys(fn ($item) => [$item->id => $result])
            ->all();
    }

    /** @param array<int, UploadedFile> $photos */
    private function submit(string $result, array $photos = [])
    {
        $payload = [
            'component_code' => $this->componentCode(),
            'answers'        => $this->answers($result),
        ];

        if ($photos) {
            $payload['photos'] = $photos;
        }

        return $this->actingAs($this->inspector)
            ->post("/projects/{$this->project->id}/inspect/{$this->sample->id}", $payload);
    }

    private function assessment(): ComponentAssessment
    {
        return ComponentAssessment::firstOrFail();
    }

    private function defect(): Defect
    {
        return Defect::where('assessment_id', $this->assessment()->id)->firstOrFail();
    }

    public function test_one_upload_reaches_both_the_assessment_and_its_defect(): void
    {
        $this->submit('FAIL', [UploadedFile::fake()->image('defect.jpg')])
            ->assertRedirect();

        $assessment = ComponentAssessment::firstOrFail();
        $defect     = Defect::where('assessment_id', $assessment->id)->firstOrFail();

        $this->assertTrue($assessment->hasMedia('photos'), 'assessment kept no photo');
        $this->assertTrue($defect->hasMedia('photos'), 'defect kept no photo');

        foreach ([$assessment, $defect] as $model) {
            $media = $model->getFirstMedia('photos');
            $this->assertSame('r2', $media->disk);
            $this->assertTrue(Storage::disk('r2')->exists($media->getPathRelativeToRoot()));
        }
    }

    public function test_photos_are_not_written_to_a_local_disk(): void
    {
        $this->submit('FAIL', [UploadedFile::fake()->image('defect.jpg')])
            ->assertRedirect();

        $this->assertEmpty(Storage::disk('public')->allFiles());
        $this->assertEmpty(Storage::disk('local')->allFiles());
        $this->assertNull(ComponentAssessment::firstOrFail()->photo_path);
    }

    public function test_a_passing_assessment_keeps_its_photo_and_creates_no_defect(): void
    {
        $this->submit('PASS', [UploadedFile::fake()->image('ok.jpg')])
            ->assertRedirect();

        $this->assertTrue(ComponentAssessment::firstOrFail()->hasMedia('photos'));
        $this->assertSame(0, Defect::count());
    }

    public function test_three_photos_in_one_submit_all_attach_to_both_records(): void
    {
        $this->submit('FAIL', [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.jpg'),
            UploadedFile::fake()->image('three.jpg'),
        ])->assertRedirect();

        $this->assertCount(3, $this->assessment()->getMedia('photos'));
        $this->assertCount(3, $this->defect()->getMedia('photos'));
    }

    public function test_a_later_submit_adds_to_the_photos_already_there(): void
    {
        $this->submit('FAIL', [UploadedFile::fake()->image('first.jpg')])->assertRedirect();
        $this->submit('FAIL', [UploadedFile::fake()->image('second.jpg')])->assertRedirect();

        $this->assertSame(
            ['first.jpg', 'second.jpg'],
            $this->assessment()->getMedia('photos')->pluck('file_name')->all()
        );
        $this->assertCount(2, $this->defect()->getMedia('photos'));
    }

    public function test_more_than_three_photos_in_one_submit_is_rejected(): void
    {
        $this->submit('FAIL', [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.jpg'),
            UploadedFile::fake()->image('three.jpg'),
            UploadedFile::fake()->image('four.jpg'),
        ])->assertSessionHasErrors('photos');

        $this->assertSame(0, ComponentAssessment::count());
    }

    public function test_a_photo_beyond_the_third_is_rejected_rather_than_pushing_one_out(): void
    {
        $this->submit('FAIL', [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.jpg'),
            UploadedFile::fake()->image('three.jpg'),
        ])->assertRedirect();

        $this->submit('FAIL', [UploadedFile::fake()->image('four.jpg')])
            ->assertSessionHasErrors('photos');

        $this->assertSame(
            ['one.jpg', 'two.jpg', 'three.jpg'],
            $this->assessment()->getMedia('photos')->pluck('file_name')->all()
        );
    }

    public function test_a_photo_over_three_megabytes_is_rejected(): void
    {
        $this->submit('FAIL', [UploadedFile::fake()->image('huge.jpg')->size(3073)])
            ->assertSessionHasErrors('photos.0');

        $this->assertSame(0, ComponentAssessment::count());
    }

    public function test_a_later_fail_without_a_new_upload_copies_the_existing_photo(): void
    {
        $this->submit('PASS', [
            UploadedFile::fake()->image('ok.jpg'),
            UploadedFile::fake()->image('also-ok.jpg'),
        ])->assertRedirect();
        $this->submit('FAIL')->assertRedirect();

        $assessment = ComponentAssessment::firstOrFail();
        $defect     = Defect::where('assessment_id', $assessment->id)->firstOrFail();

        $this->assertSame(
            ['ok.jpg', 'also-ok.jpg'],
            $defect->getMedia('photos')->pluck('file_name')->all()
        );
        $this->assertCount(2, $assessment->getMedia('photos'));
    }
}
