# Next-Action Hero Card Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the passive one-line "next action" banner and the disconnected step pipeline on the project detail and score pages with a single, prominent, actionable "Your Next Step" hero card that includes a real call-to-action button wherever one applies.

**Architecture:** Extend `Project::nextActionFor()`'s existing return array with four new keys (`actionable`, `route`, `params`, `button_label`) so the same single source of truth that already decides *what* to tell each role also decides *whether there's a button and where it goes*. A new `<x-next-action-card>` Blade component renders three states (actionable / waiting / empty) driven entirely by that array, replacing the old banner on the project page and being added for the first time on the score page.

**Tech Stack:** Laravel, Blade + Alpine.js, PHPUnit. Tests run via `docker exec -w /var/www eidsv2 php artisan test` (the host PHP CLI lacks `pdo_sqlite`; never run `php artisan test` directly on the host).

## Global Constraints

- `Project::nextActionFor(User $user): array` keeps its existing signature and its existing `icon`/`text` keys — the Task 14 dashboard "Action Required" widget only reads those two and must keep working unchanged.
- The new keys are exactly: `actionable` (bool), `route` (string|null), `params` (array), `button_label` (string|null).
- "Waiting" is a distinct third state, not "actionable with no button" — `actionable = false` with non-empty `text` renders the muted card; `actionable = true` always has a non-null `route` and `button_label`.
- Scope is exactly `resources/views/projects/show.blade.php` and `resources/views/projects/score.blade.php`. The Dashboard's "Action Required" widget (`resources/views/dashboard.blade.php`) is explicitly unchanged — do not touch it.
- Per the approved design preview, the hero card renders ABOVE the step pipeline on both pages (not below) — this is a layout reorder on `show.blade.php`, since the stepper currently comes first there.

---

### Task 1: Extend `Project::nextActionFor()` with actionable/route/params/button_label

**Files:**
- Modify: `app/Models/Project.php` (`nextActionFor()` method, currently lines 97-143)
- Modify: `tests/Unit/ProjectNextActionTest.php`

**Interfaces:**
- Produces: `nextActionFor()` now returns `['icon' => string, 'text' => string, 'actionable' => bool, 'route' => string|null, 'params' => array, 'button_label' => string|null]` for every branch. Task 2/3/4 consume this shape.

- [ ] **Step 1: Extend the existing tests and add the missing branch coverage**

Replace the entire contents of `tests/Unit/ProjectNextActionTest.php` with:

```php
<?php

namespace Tests\Unit;

use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectNextActionTest extends TestCase
{
    use RefreshDatabase;

    private function baseProject(array $overrides = []): Project
    {
        $creator = User::factory()->create(['role' => 'lead_auditor']);
        return Project::factory()->create(array_merge(['created_by' => $creator->id], $overrides));
    }

    public function test_admin_is_told_to_finish_naming_locations_when_still_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = $this->baseProject();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('naming sample locations', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('projects.samples', $action['route']);
        $this->assertSame('Configure Samples', $action['button_label']);
    }

    public function test_admin_is_told_inspection_is_pending_once_locations_are_named(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector', 'name' => 'Aiman']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('Aiman', $action['text']);
        $this->assertFalse($action['actionable']);
        $this->assertNull($action['route']);
        $this->assertNull($action['button_label']);
    }

    public function test_admin_waiting_on_defects_is_not_actionable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        $sample = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        ComponentAssessment::create([
            'project_id' => $project->id, 'sample_id' => $sample->id,
            'component_code' => 'A1_FLOOR', 'component_name' => 'Floor', 'weightage' => 18,
            'overall_sample_status' => 'FAIL',
        ]);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('defect(s) still open', $action['text']);
        $this->assertFalse($action['actionable']);
    }

    public function test_admin_sees_generate_pdf_action_when_inspection_complete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id, 'calculated_samples' => 1]);
        $sample = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        foreach (array_keys(config('eids.components')) as $code) {
            ComponentAssessment::create([
                'project_id' => $project->id, 'sample_id' => $sample->id,
                'component_code' => $code, 'component_name' => $code, 'weightage' => 10,
                'overall_sample_status' => 'PASS',
            ]);
        }

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('Inspection complete', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('reports.show', $action['route']);
        $this->assertSame('Generate PDF Report', $action['button_label']);
    }

    public function test_inspector_is_told_to_start_the_grid_when_nothing_inspected(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('Start the Components Grid', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('projects.components', $action['route']);
        $this->assertSame('Start Inspecting Now', $action['button_label']);
    }

    public function test_inspector_sees_pending_verification_count(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('awaiting your verification', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('defects.index', $action['route']);
        $this->assertSame(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION'], $action['params']);
        $this->assertSame('Review Defects', $action['button_label']);
    }

    public function test_inspector_sees_continue_action_when_partially_inspected(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id, 'calculated_samples' => 2]);
        $sample1 = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 2, 'location_name' => 'Kitchen']);
        ComponentAssessment::create([
            'project_id' => $project->id, 'sample_id' => $sample1->id,
            'component_code' => 'A1_FLOOR', 'component_name' => 'Floor', 'weightage' => 18,
            'overall_sample_status' => 'PASS',
        ]);

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('Continue', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('projects.components', $action['route']);
        $this->assertSame('Continue Inspecting', $action['button_label']);
    }

    public function test_inspector_sees_view_score_action_when_inspection_complete(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id, 'calculated_samples' => 1]);
        $sample = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        foreach (array_keys(config('eids.components')) as $code) {
            ComponentAssessment::create([
                'project_id' => $project->id, 'sample_id' => $sample->id,
                'component_code' => $code, 'component_name' => $code, 'weightage' => 10,
                'overall_sample_status' => 'PASS',
            ]);
        }

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('notify your Lead Auditor', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('projects.score', $action['route']);
        $this->assertSame('View G-IDS Score', $action['button_label']);
    }

    public function test_supervisor_sees_a_read_only_progress_line(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = $this->baseProject();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($supervisor);

        $this->assertStringContainsString('sample units inspected', $action['text']);
        $this->assertFalse($action['actionable']);
    }

    public function test_supervisor_sees_download_action_when_certificate_ready(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = $this->baseProject(['status' => 'selesai']);

        $action = $project->fresh()->nextActionFor($supervisor);

        $this->assertStringContainsString('Certificate ready', $action['text']);
        $this->assertTrue($action['actionable']);
        $this->assertSame('reports.show', $action['route']);
        $this->assertSame('Download Report', $action['button_label']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker exec -w /var/www eidsv2 php artisan test --filter=ProjectNextActionTest`
Expected: FAIL — `Undefined array key "actionable"` (and similar) on every extended assertion; the 5 brand-new test methods fail the same way.

- [ ] **Step 3: Rewrite `nextActionFor()`**

In `app/Models/Project.php`, replace the entire `nextActionFor()` method (currently lines 97-143) with:

```php
    public function nextActionFor(User $user): array
    {
        $hasNamedLocations = $this->samples->contains(fn ($s) => !str_starts_with($s->location_name, 'Sample '));
        $inspectionStarted = $this->assessments()->exists();
        $inspectionDone    = $this->inspection_progress >= 100;
        $inspectedCount    = $this->samples->whereNotNull('pass_rate')->count();
        $totalSamples      = $this->samples->count();
        $openDefects       = $this->defects()->whereIn('status', ['OPEN', 'IN_PROGRESS'])->count();
        $pendingVerify     = $this->defects()->where('status', 'PENDING_VERIFICATION')->count();

        $waiting = fn (string $icon, string $text) => [
            'icon' => $icon, 'text' => $text, 'actionable' => false,
            'route' => null, 'params' => [], 'button_label' => null,
        ];
        $actionable = fn (string $icon, string $text, string $route, array $params, string $buttonLabel) => [
            'icon' => $icon, 'text' => $text, 'actionable' => true,
            'route' => $route, 'params' => $params, 'button_label' => $buttonLabel,
        ];

        if (in_array($user->role, ['admin', 'lead_auditor'])) {
            if (!$hasNamedLocations) {
                return $actionable('tune', 'Finish naming sample locations.', 'projects.samples', ['project' => $this], 'Configure Samples');
            }
            if (!$inspectionStarted) {
                $name = $this->assignedInspector->name ?? 'the assigned inspector';
                return $waiting('grid_on', "Waiting on Inspector {$name} to begin the Components Grid inspection.");
            }
            if ($openDefects > 0 || $pendingVerify > 0) {
                $count = $openDefects + $pendingVerify;
                return $waiting('warning', "{$count} defect(s) still open — waiting on Contractor & Inspector verification.");
            }
            return $actionable('analytics', 'Inspection complete — ready to generate the signed G-IDS PDF.', 'reports.show', ['project' => $this], 'Generate PDF Report');
        }

        if ($user->role === 'inspector') {
            if ($pendingVerify > 0) {
                return $actionable(
                    'fact_check', "{$pendingVerify} defect(s) awaiting your verification.",
                    'defects.index', ['project_id' => $this->id, 'status' => 'PENDING_VERIFICATION'], 'Review Defects'
                );
            }
            if (!$inspectionStarted) {
                return $actionable('grid_on', 'Start the Components Grid inspection.', 'projects.components', ['project' => $this], 'Start Inspecting Now');
            }
            if (!$inspectionDone) {
                return $actionable(
                    'grid_on', "Continue — {$inspectedCount}/{$totalSamples} sample units done.",
                    'projects.components', ['project' => $this], 'Continue Inspecting'
                );
            }
            return $actionable('task_alt', 'Inspection complete — notify your Lead Auditor.', 'projects.score', ['project' => $this], 'View G-IDS Score');
        }

        if ($user->role === 'supervisor') {
            if ($this->status === 'selesai') {
                return $actionable('description', 'Certificate ready for download.', 'reports.show', ['project' => $this], 'Download Report');
            }
            return $waiting('schedule', "In progress — {$inspectedCount}/{$totalSamples} sample units inspected.");
        }

        return $waiting('info', '');
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker exec -w /var/www eidsv2 php artisan test --filter=ProjectNextActionTest`
Expected: PASS (11 tests)

Run the full suite too (`docker exec -w /var/www eidsv2 php artisan test`) to confirm nothing that reads the old two-key shape broke — in particular the Task 14 dashboard widget test (`DashboardActionRequiredTest`), which only reads `icon`/`text` and should be unaffected.

- [ ] **Step 5: Commit**

```bash
git add app/Models/Project.php tests/Unit/ProjectNextActionTest.php
git commit -m "feat: extend Project::nextActionFor with actionable/route/button data"
```

---

### Task 2: `<x-next-action-card>` component

**Files:**
- Create: `resources/views/components/next-action-card.blade.php`

**Interfaces:**
- Consumes: the array shape from Task 1 (`icon`, `text`, `actionable`, `route`, `params`, `button_label`).
- Produces: `<x-next-action-card :action="$nextAction" />` — a single-prop component. Consumed by Task 3 and Task 4.

There's no isolated unit test for a pure Blade component in this codebase's existing conventions (every Blade piece is tested through the page that renders it) — this task's own correctness is verified by Task 3's tests, which render it for real through `projects/show.blade.php`. Do not skip writing it carefully just because there's no dedicated test file for it here.

- [ ] **Step 1: Write the component**

Create `resources/views/components/next-action-card.blade.php`:

```blade
@props(['action'])

@if(!empty($action['text']))
    @if($action['actionable'])
        <div class="bg-eids-primary/10 border border-eids-primary/20 rounded-2xl p-5 mb-6 flex flex-col sm:flex-row sm:items-center gap-4 shadow-xs">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <span class="material-symbols-outlined text-eids-primary text-3xl shrink-0">{{ $action['icon'] }}</span>
                <div class="min-w-0">
                    <div class="text-[10px] uppercase tracking-widest font-extrabold text-eids-primary/70 mb-0.5">Your Next Step</div>
                    <div class="text-sm font-bold text-gray-900">{{ $action['text'] }}</div>
                </div>
            </div>
            <a href="{{ route($action['route'], $action['params'] ?? []) }}"
               class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs min-h-[44px]">
                {{ $action['button_label'] }}
                <span class="material-symbols-outlined text-lg">arrow_forward</span>
            </a>
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-gray-400 text-2xl shrink-0">{{ $action['icon'] }}</span>
            <div>
                <div class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 mb-0.5">Waiting</div>
                <div class="text-sm font-bold text-gray-700">{{ $action['text'] }}</div>
            </div>
        </div>
    @endif
@endif
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/components/next-action-card.blade.php
git commit -m "feat: add next-action-card component"
```

---

### Task 3: Wire into `projects/show.blade.php` (replace the old banner, reorder above the stepper)

**Files:**
- Modify: `resources/views/projects/show.blade.php`
- Modify: `tests/Feature/ProjectShowBannerTest.php`

**Interfaces:**
- Consumes: `<x-next-action-card>` (Task 2), `$project->nextActionFor(auth()->user())` (Task 1, already computed on this page as `$nextAction`).

- [ ] **Step 1: Write the failing tests**

Replace `tests/Feature/ProjectShowBannerTest.php`'s first test and add two new ones — the file becomes:

```php
<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectShowBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_an_actionable_button_when_locations_need_naming(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($admin)->get("/projects/{$project->id}");

        $response->assertSee('Finish naming sample locations');
        $response->assertSee('Configure Samples');
        $response->assertSee('href="' . route('projects.samples', $project) . '"', false);
    }

    public function test_waiting_state_renders_no_action_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $response = $this->actingAs($admin)->get("/projects/{$project->id}");

        $response->assertSee('Waiting on Inspector', false);
        $response->assertDontSee('href="' . route('projects.components', $project) . '"', false);
        $response->assertDontSee('href="' . route('projects.samples', $project) . '"', false);
    }

    public function test_hero_card_renders_above_the_stepper(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($inspector)->get("/projects/{$project->id}");
        $html = $response->getContent();

        $heroPosition = strpos($html, 'Your Next Step');
        $stepperPosition = strpos($html, 'Sample Setup');
        $this->assertNotFalse($heroPosition);
        $this->assertNotFalse($stepperPosition);
        $this->assertLessThan($stepperPosition, $heroPosition);
    }

    public function test_admin_stepper_marks_grid_as_handled_by_inspector(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();

        $response = $this->actingAs($admin)->get("/projects/{$project->id}");

        $response->assertSee('Handled by Inspector');
    }

    public function test_inspector_stepper_does_not_show_handled_by_inspector_label(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);

        $response = $this->actingAs($inspector)->get("/projects/{$project->id}");

        $response->assertDontSee('Handled by Inspector');
    }

    public function test_supervisor_sees_the_banner_but_no_interactive_stepper(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = Project::factory()->create();
        $project->supervisors()->attach($supervisor->id);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($supervisor)->get("/projects/{$project->id}");

        $response->assertOk();
        // Read-only status line (nextActionFor hero card) still renders.
        $response->assertSee('sample units inspected');
        // None of the stepper's step labels render. ("G-IDS Score" is deliberately not
        // asserted on — that string also appears in non-stepper content on this page.)
        $response->assertDontSee('Sample Setup');
        $response->assertDontSee('Components Grid');
        $response->assertDontSee('Handled by Inspector');
        // Nor any of the stepper's navigation links.
        $response->assertDontSee('href="' . route('projects.samples', $project) . '"', false);
        $response->assertDontSee('href="' . route('projects.components', $project) . '"', false);
    }

    public function test_inspector_still_sees_the_interactive_stepper(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);

        $response = $this->actingAs($inspector)->get("/projects/{$project->id}");

        $response->assertSee('Sample Setup');
    }

    public function test_supervisor_sees_no_interactive_stepper_or_grid_link_on_score_page(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = Project::factory()->create();
        $project->supervisors()->attach($supervisor->id);
        $sample = ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($supervisor)->get("/projects/{$project->id}/score");

        $response->assertOk();
        $response->assertDontSee('Sample Setup');
        $response->assertDontSee('Components Grid');
        $response->assertDontSee('href="' . route('projects.components', $project) . '"', false);
        $response->assertDontSee('href="' . route('projects.samples', $project) . '"', false);
        $response->assertDontSee('href="' . route('projects.inspect', [$project, $sample]) . '"', false);
    }

    public function test_inspector_still_sees_the_interactive_stepper_on_score_page(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);

        $response = $this->actingAs($inspector)->get("/projects/{$project->id}/score");

        $response->assertOk();
        $response->assertSee('Components Grid');
    }
}
```

(This keeps every existing test, updates the first one to the new actionable-button expectation, and adds `test_waiting_state_renders_no_action_button` and `test_hero_card_renders_above_the_stepper`. The two score-page tests at the bottom are untouched — Task 4 doesn't need to modify them, they already pass once Task 4 is done, since neither asserts anything about the hero card's absence/presence, only the stepper's.)

- [ ] **Step 2: Run tests to verify the new/changed ones fail**

Run: `docker exec -w /var/www eidsv2 php artisan test --filter=ProjectShowBannerTest`
Expected: `test_admin_sees_an_actionable_button_when_locations_need_naming` FAILs (no "Configure Samples" text yet, old banner has no button). `test_hero_card_renders_above_the_stepper` FAILs (stepper currently comes first). `test_waiting_state_renders_no_action_button` likely already passes by coincidence (old banner never had buttons) — that's fine, it becomes a real regression guard once Task 3 is done.

- [ ] **Step 3: Reorder and replace the banner in `show.blade.php`**

In `resources/views/projects/show.blade.php`, replace:

```blade
    {{-- Pipeline Step Indicator --}}
    {{-- Supervisors get a read-only status line (the nextActionFor banner) only, no interactive stepper. --}}
    @unless(auth()->user()->role === 'supervisor')
        <x-workflow-step step="1" :project="$project" :role="auth()->user()->role" />
    @endunless

    {{-- Next Action Banner --}}
    <div class="bg-eids-primary/5 border border-eids-primary/15 rounded-2xl p-4 mb-6 flex items-center gap-3">
        <span class="material-symbols-outlined text-eids-accent text-2xl shrink-0">{{ $nextAction['icon'] }}</span>
        <span class="text-sm font-bold text-gray-900">{{ $nextAction['text'] }}</span>
    </div>
```

with:

```blade
    {{-- Next Action Hero Card --}}
    <x-next-action-card :action="$nextAction" />

    {{-- Pipeline Step Indicator --}}
    {{-- Supervisors get a read-only status line (the hero card above) only, no interactive stepper. --}}
    @unless(auth()->user()->role === 'supervisor')
        <x-workflow-step step="1" :project="$project" :role="auth()->user()->role" />
    @endunless
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker exec -w /var/www eidsv2 php artisan test --filter=ProjectShowBannerTest`
Expected: PASS (9 tests)

Run the full suite (`docker exec -w /var/www eidsv2 php artisan test`) to confirm nothing else that renders this page broke (in particular `ProjectAccessGuardTest`, which hits `/projects/{project}` for admin/inspector).

- [ ] **Step 5: Commit**

```bash
git add resources/views/projects/show.blade.php tests/Feature/ProjectShowBannerTest.php
git commit -m "feat: replace project show banner with next-action hero card"
```

---

### Task 4: Wire into `projects/score.blade.php` (net-new)

**Files:**
- Modify: `resources/views/projects/score.blade.php`
- Modify: `tests/Feature/ProjectShowBannerTest.php`

**Interfaces:**
- Consumes: `<x-next-action-card>` (Task 2), `Project::nextActionFor()` (Task 1).

- [ ] **Step 1: Write the failing tests**

Add these two tests to the end of `tests/Feature/ProjectShowBannerTest.php` (inside the class, after `test_inspector_still_sees_the_interactive_stepper_on_score_page`):

```php
    public function test_inspector_sees_actionable_button_on_score_page_when_defects_pending_verification(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $response = $this->actingAs($inspector)->get("/projects/{$project->id}/score");

        $response->assertOk();
        $response->assertSee('Review Defects');
        $response->assertSee(
            'href="' . route('defects.index', ['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']) . '"',
            false
        );
    }

    public function test_supervisor_sees_download_button_on_score_page_when_certificate_ready(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = Project::factory()->create(['status' => 'selesai']);
        $project->supervisors()->attach($supervisor->id);

        $response = $this->actingAs($supervisor)->get("/projects/{$project->id}/score");

        $response->assertOk();
        $response->assertSee('Download Report');
        $response->assertSee('href="' . route('reports.show', $project) . '"', false);
    }
```

Add the `use App\Models\Defect;` import at the top of the file if it isn't already there (it is, from the earlier tests in this file — double-check before adding a duplicate `use` line).

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker exec -w /var/www eidsv2 php artisan test --filter=ProjectShowBannerTest --filter=test_inspector_sees_actionable_button_on_score_page_when_defects_pending_verification`
Run: `docker exec -w /var/www eidsv2 php artisan test --filter=ProjectShowBannerTest --filter=test_supervisor_sees_download_button_on_score_page_when_certificate_ready`
Expected: both FAIL — the score page has no hero card at all yet, so neither "Review Defects" nor "Download Report" appear anywhere.

- [ ] **Step 3: Add the hero card to `score.blade.php`**

In `resources/views/projects/score.blade.php`, add a `$nextAction` computation right after the `@endsection` of the breadcrumb block (mirroring exactly where `show.blade.php` computes it — outside any section, so it's available to both `@section('topbar-actions')` and `@section('content')`). Change:

```blade
@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">G-IDS Score</span>
@endsection

@section('topbar-actions')
```

to:

```blade
@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">G-IDS Score</span>
@endsection

@php
    $nextAction = $project->nextActionFor(auth()->user());
@endphp

@section('topbar-actions')
```

Then, inside `@section('content')`, add the hero card above the stepper. Change:

```blade
@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Pipeline Step Indicator --}}
    @unless(auth()->user()->role === 'supervisor')
        <x-workflow-step step="5" :project="$project" :role="auth()->user()->role" />
    @endunless
```

to:

```blade
@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Next Action Hero Card --}}
    <x-next-action-card :action="$nextAction" />

    {{-- Pipeline Step Indicator --}}
    @unless(auth()->user()->role === 'supervisor')
        <x-workflow-step step="5" :project="$project" :role="auth()->user()->role" />
    @endunless
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker exec -w /var/www eidsv2 php artisan test --filter=ProjectShowBannerTest`
Expected: PASS (11 tests — all 9 from Task 3 plus these 2)

Run the full suite (`docker exec -w /var/www eidsv2 php artisan test`) — this is the last task in the plan, confirm everything is green end to end.

- [ ] **Step 5: Commit**

```bash
git add resources/views/projects/score.blade.php tests/Feature/ProjectShowBannerTest.php
git commit -m "feat: add next-action hero card to the score page"
```
