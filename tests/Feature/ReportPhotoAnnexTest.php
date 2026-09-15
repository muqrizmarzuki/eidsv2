<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\WeightageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A defect can carry up to three photos, and the report annexes have to show
 * every one of them — the PDF embeds each as base64 (dompdf cannot fetch from
 * R2), the web report links each one.
 */
class ReportPhotoAnnexTest extends TestCase
{
    use RefreshDatabase;

    private User $inspector;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // The web report extends the Vite-built layout; assets are not built in CI.
        $this->withoutVite();
        $this->seed(WeightageSeeder::class);

        Storage::fake('r2');
        config(['media-library.disk_name' => 'r2']);

        $this->inspector = User::factory()->create(['role' => 'inspector']);
        $this->project   = Project::factory()->create(['assigned_to' => $this->inspector->id]);
    }

    private function defectWith(int $photos, string $component = 'Floor Tiles'): Defect
    {
        $defect = Defect::factory()->create([
            'project_id'     => $this->project->id,
            'component_name' => $component,
        ]);

        foreach (range(1, $photos) as $i) {
            $defect->addMedia(UploadedFile::fake()->image("{$component}-{$i}.jpg"))
                ->preservingOriginal()
                ->toMediaCollection('photos');
        }

        return $defect;
    }

    private function render(string $view): string
    {
        $project = $this->project->fresh();

        $buildScoreData = new ReflectionMethod(ReportController::class, 'buildScoreData');
        $buildScoreData->setAccessible(true);
        $data = $buildScoreData->invoke(app(ReportController::class), $project);

        return view($view, array_merge(['project' => $project], $data))->render();
    }

    public function test_pdf_annex_embeds_every_photo_of_every_defect(): void
    {
        $this->defectWith(3, 'Floor');
        $this->defectWith(2, 'Wall');

        $html = $this->render('reports.pdf');

        $this->assertSame(5, substr_count($html, 'src="data:image'));
    }

    public function test_pdf_annex_counts_photos_not_defects_in_its_header(): void
    {
        $this->defectWith(3);

        $html = $this->render('reports.pdf');

        $this->assertStringContainsString('Defect Photographic Evidence (3 Photos)', $html);
    }

    public function test_pdf_annex_numbers_each_photo_of_a_multi_photo_defect(): void
    {
        $this->defectWith(3);

        $html = $this->render('reports.pdf');

        $this->assertStringContainsString('Photo 1 of 3', $html);
        $this->assertStringContainsString('Photo 3 of 3', $html);
    }

    public function test_pdf_annex_does_not_number_a_single_photo_defect(): void
    {
        $this->defectWith(1);

        $html = $this->render('reports.pdf');

        $this->assertStringNotContainsString('Photo 1 of 1', $html);
    }

    public function test_web_report_annex_shows_every_photo(): void
    {
        $this->defectWith(3);

        $html = $this->actingAs($this->inspector)
            ->get("/reports/{$this->project->id}")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Photographic Evidence (3)', $html);
        $this->assertSame(3, substr_count($html, 'alt="Defect photo'));
    }
}
