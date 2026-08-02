# Role-Scoped Visibility & Contractor Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scope Project/Dashboard/Defect visibility per role, add a `contractor` role that can self-report repair progress, and give every role a clear "what do I do next" signal on projects.

**Architecture:** Centralized `visibleTo()` query scopes on `Project`/`Defect` reused across list controllers, dashboard, and single-resource `403` guards; a role-gated defect status transition map replacing the old blind toggle; role-aware Blade components driven by a single `Project::nextActionFor()` method.

**Tech Stack:** Laravel 13.8, PHP 8.3, MySQL (prod) / SQLite in-memory (tests), Blade + Alpine.js, PHPUnit.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-02-role-visibility-and-contractor-workflow-design.md` — every task below implements one section of it.
- Roles are exactly: `admin`, `lead_auditor`, `inspector`, `supervisor`, `contractor` (`users.role`).
- Defect statuses are exactly: `OPEN`, `IN_PROGRESS`, `PENDING_VERIFICATION`, `RESOLVED` (`defects.status`).
- **Schema changes must run on both MySQL (prod) and SQLite in-memory (tests, per `phpunit.xml`'s `DB_CONNECTION=sqlite`/`DB_DATABASE=:memory:`).** Raw `DB::statement("ALTER TABLE ... MODIFY ...")` is MySQL-only syntax and will hard-fail on SQLite. This plan deviates from the spec's Section 3 code samples (which used raw `MODIFY` SQL) and instead uses Laravel's native `Schema::table(...)->change()`, which the installed Laravel version (`laravel/framework: ^13.8`) compiles correctly for both `mysql` and `sqlite` grammars without requiring `doctrine/dbal` (verified: both `MySqlGrammar` and `SQLiteGrammar` in `vendor/laravel/framework` implement `compileChange()` natively). The outcome (allowed enum values) is identical to the spec — only the migration mechanism changes.
- Every new/changed route or controller method must go through `Project::visibleTo()` / `Defect::visibleTo()` — never write a new one-off role check for list filtering.
- No new composer dependencies.

---

## File Structure

New files:
- `database/migrations/2026_08_02_000007_add_contractor_role_to_users_table.php` — role enum
- `database/migrations/2026_08_02_000008_add_assigned_contractor_to_projects_table.php` — `assigned_contractor_id`
- `database/migrations/2026_08_02_000009_create_project_supervisor_table.php` — pivot
- `database/migrations/2026_08_02_000010_add_pending_verification_to_defects_status.php` — status enum
- `database/factories/ProjectFactory.php`, `database/factories/DefectFactory.php` — test data builders (added in Task 5, first task needing multi-project/multi-role fixtures)
- `tests/Feature/ProjectVisibilityTest.php`, `tests/Feature/DefectVisibilityTest.php`, `tests/Feature/ProjectAccessGuardTest.php`, `tests/Feature/DefectAdvanceStatusTest.php`, `tests/Feature/ContractorAccessTest.php`, `tests/Unit/ProjectNextActionTest.php`, `tests/Feature/DashboardActionRequiredTest.php`, `tests/Feature/ProjectAssignmentFormTest.php`

Modified files (touched across the tasks below): `app/Models/{Project,User,Defect}.php`, `app/Http/Controllers/{ProjectController,DefectController,InspectionController,ReportController,AuthController,UserController}.php`, `routes/web.php`, `resources/views/{layouts/app,dashboard,defects/index,defects/create,defects/edit,projects/index,projects/show,projects/create,projects/edit,users/create,users/edit,components/workflow-step}.blade.php`.

---

### Task 1: `contractor` role — migration, User helper, Admin user form

**Files:**
- Create: `database/migrations/2026_08_02_000007_add_contractor_role_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `app/Http/Controllers/UserController.php` (`store`, `update` validation)
- Modify: `resources/views/users/create.blade.php`, `resources/views/users/edit.blade.php`
- Test: `tests/Feature/UserContractorRoleTest.php`

**Interfaces:**
- Produces: `User::isContractor(): bool`, `role` enum now accepts `'contractor'`, `users.store`/`users.update` accept `role=contractor`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserContractorRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_contractor_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/users', [
            'name'                  => 'Bina Jaya QC',
            'email'                 => 'qc@binajaya.test',
            'role'                  => 'contractor',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => 'qc@binajaya.test', 'role' => 'contractor']);
    }

    public function test_is_contractor_helper(): void
    {
        $user = User::factory()->create(['role' => 'contractor']);
        $this->assertTrue($user->isContractor());
        $this->assertFalse($user->isAdmin());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UserContractorRoleTest`
Expected: FAIL — `role=contractor` rejected by the `in:admin,lead_auditor,inspector,supervisor` validation rule, and `isContractor()` doesn't exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'lead_auditor', 'inspector', 'supervisor', 'contractor'])
                  ->default('inspector')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'lead_auditor', 'inspector', 'supervisor'])
                  ->default('inspector')
                  ->change();
        });
    }
};
```

- [ ] **Step 4: Add the `isContractor()` helper**

In `app/Models/User.php`, immediately after `isLeadAuditor()`:

```php
    public function isContractor(): bool
    {
        return $this->role === 'contractor';
    }
```

And update `getRoleLabel()`'s match arms to add, right after the `'supervisor'` line:

```php
            'contractor'   => 'Kontraktor',
```

- [ ] **Step 5: Update `UserController` validation**

In `app/Http/Controllers/UserController.php`, in both `store()` and `update()`, change:

```php
            'role'        => 'required|in:admin,lead_auditor,inspector,supervisor',
```

to:

```php
            'role'        => 'required|in:admin,lead_auditor,inspector,supervisor,contractor',
```

- [ ] **Step 6: Add the Contractor option to the role dropdowns**

In `resources/views/users/create.blade.php`, after the `supervisor` `<option>` line:

```blade
                            <option value="contractor"   {{ old('role') === 'contractor'   ? 'selected' : '' }}>Contractor</option>
```

In `resources/views/users/edit.blade.php`, after the `supervisor` `<option>` line:

```blade
                            <option value="contractor"   {{ old('role', $user->role) === 'contractor'   ? 'selected' : '' }}>Contractor</option>
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=UserContractorRoleTest`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_08_02_000007_add_contractor_role_to_users_table.php \
        app/Models/User.php app/Http/Controllers/UserController.php \
        resources/views/users/create.blade.php resources/views/users/edit.blade.php \
        tests/Feature/UserContractorRoleTest.php
git commit -m "feat: add contractor role"
```

---

### Task 2: `assigned_contractor_id` on projects

**Files:**
- Create: `database/migrations/2026_08_02_000008_add_assigned_contractor_to_projects_table.php`
- Modify: `app/Models/Project.php`
- Test: `tests/Feature/ProjectAssignedContractorTest.php`

**Interfaces:**
- Consumes: `User::isContractor()` (Task 1)
- Produces: `Project::assignedContractor(): BelongsTo`, `projects.assigned_contractor_id` column, `Project::$fillable` includes `assigned_contractor_id`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAssignedContractorTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_has_an_assigned_contractor_relation(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::create([
            'project_no'              => 'PRJ-TEST-001',
            'project_name'            => 'Test Project',
            'developer_name'          => 'Dev Co',
            'contractor_name'         => 'Contractor Co',
            'building_type'           => 'teres',
            'total_units'             => 10,
            'floor_area_sqm'          => 100,
            'calculated_samples'      => 2,
            'status'                  => 'draf',
            'created_by'              => $contractor->id,
            'assigned_contractor_id'  => $contractor->id,
        ]);

        $this->assertTrue($project->assignedContractor->is($contractor));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProjectAssignedContractorTest`
Expected: FAIL — unknown column `assigned_contractor_id` / undefined relation method.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('assigned_contractor_id')->nullable()->after('assigned_to')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['assigned_contractor_id']);
            $table->dropColumn('assigned_contractor_id');
        });
    }
};
```

- [ ] **Step 4: Add the fillable field and relation**

In `app/Models/Project.php`, add `'assigned_contractor_id'` to `$fillable` (right after `'assigned_to'`):

```php
    protected $fillable = [
        'project_no', 'project_name', 'location', 'developer_name',
        'contractor_name', 'building_type', 'total_units', 'floor_area_sqm',
        'calculated_samples', 'overall_score', 'status', 'created_by', 'assigned_to',
        'assigned_contractor_id',
    ];
```

Add the relation, right after `assignedInspector()`:

```php
    public function assignedContractor()
    {
        return $this->belongsTo(User::class, 'assigned_contractor_id');
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=ProjectAssignedContractorTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_08_02_000008_add_assigned_contractor_to_projects_table.php \
        app/Models/Project.php tests/Feature/ProjectAssignedContractorTest.php
git commit -m "feat: add assigned_contractor_id to projects"
```

---

### Task 3: `project_supervisor` pivot

**Files:**
- Create: `database/migrations/2026_08_02_000009_create_project_supervisor_table.php`
- Modify: `app/Models/Project.php`
- Test: `tests/Feature/ProjectSupervisorsTest.php`

**Interfaces:**
- Produces: `Project::supervisors(): BelongsToMany`, `project_supervisor` table (`project_id`, `user_id`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSupervisorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_project_can_have_multiple_supervisors(): void
    {
        $creator = User::factory()->create(['role' => 'lead_auditor']);
        $supervisorA = User::factory()->create(['role' => 'supervisor']);
        $supervisorB = User::factory()->create(['role' => 'supervisor']);

        $project = Project::create([
            'project_no'         => 'PRJ-TEST-002',
            'project_name'       => 'Test Project',
            'developer_name'     => 'Dev Co',
            'contractor_name'    => 'Contractor Co',
            'building_type'      => 'teres',
            'total_units'        => 10,
            'floor_area_sqm'     => 100,
            'calculated_samples' => 2,
            'status'             => 'draf',
            'created_by'         => $creator->id,
        ]);

        $project->supervisors()->sync([$supervisorA->id, $supervisorB->id]);

        $this->assertCount(2, $project->fresh()->supervisors);
        $this->assertTrue($project->fresh()->supervisors->contains($supervisorA));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProjectSupervisorsTest`
Expected: FAIL — `supervisors()` relation undefined / `project_supervisor` table missing.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_supervisor', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_supervisor');
    }
};
```

- [ ] **Step 4: Add the relation**

In `app/Models/Project.php`, right after `assignedContractor()`:

```php
    public function supervisors()
    {
        return $this->belongsToMany(User::class, 'project_supervisor');
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=ProjectSupervisorsTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_08_02_000009_create_project_supervisor_table.php \
        app/Models/Project.php tests/Feature/ProjectSupervisorsTest.php
git commit -m "feat: add project_supervisor pivot"
```

---

### Task 4: `PENDING_VERIFICATION` defect status

**Files:**
- Create: `database/migrations/2026_08_02_000010_add_pending_verification_to_defects_status.php`
- Modify: `app/Models/Defect.php`
- Modify: `app/Http/Controllers/DefectController.php` (`store`, `update` validation)
- Modify: `resources/views/defects/create.blade.php`, `resources/views/defects/edit.blade.php`
- Test: `tests/Feature/DefectPendingVerificationStatusTest.php`

**Interfaces:**
- Produces: `defects.status` accepts `'PENDING_VERIFICATION'`; `Defect::getStatusBadgeClassAttribute()` has a case for it.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectPendingVerificationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_defect_status_column_accepts_pending_verification(): void
    {
        $creator = User::factory()->create(['role' => 'inspector']);
        $project = Project::create([
            'project_no' => 'PRJ-TEST-003', 'project_name' => 'Test',
            'developer_name' => 'Dev', 'contractor_name' => 'Con',
            'building_type' => 'teres', 'total_units' => 10, 'floor_area_sqm' => 100,
            'calculated_samples' => 1, 'status' => 'draf', 'created_by' => $creator->id,
        ]);

        $defect = Defect::create([
            'project_id'         => $project->id,
            'component_name'     => 'D1',
            'location'           => 'Living Room',
            'defect_description' => 'Test defect',
            'severity'           => 'low',
            'status'             => 'PENDING_VERIFICATION',
        ]);

        $this->assertSame('PENDING_VERIFICATION', $defect->fresh()->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DefectPendingVerificationStatusTest`
Expected: FAIL — CHECK constraint / enum rejects `PENDING_VERIFICATION`.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION', 'RESOLVED'])
                  ->default('OPEN')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'RESOLVED'])
                  ->default('OPEN')
                  ->change();
        });
    }
};
```

- [ ] **Step 4: Add the badge class case**

In `app/Models/Defect.php`, in `getStatusBadgeClassAttribute()`, add a line right after the `'IN_PROGRESS'` case:

```php
            'PENDING_VERIFICATION' => 'bg-blue-100 text-blue-700',
```

- [ ] **Step 5: Update `DefectController` validation**

In `app/Http/Controllers/DefectController.php`, in both `store()` and `update()`, change:

```php
            'status'             => 'required|in:OPEN,IN_PROGRESS,RESOLVED',
```

to:

```php
            'status'             => 'required|in:OPEN,IN_PROGRESS,PENDING_VERIFICATION,RESOLVED',
```

- [ ] **Step 6: Add the option to the status dropdowns**

In `resources/views/defects/create.blade.php`, after the `IN_PROGRESS` `<option>`:

```blade
                            <option value="PENDING_VERIFICATION" {{ old('status') === 'PENDING_VERIFICATION' ? 'selected' : '' }}>Pending Verification</option>
```

In `resources/views/defects/edit.blade.php`, after the `IN_PROGRESS` `<option>`:

```blade
                            <option value="PENDING_VERIFICATION" {{ old('status', $defect->status) === 'PENDING_VERIFICATION' ? 'selected' : '' }}>Pending Verification</option>
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=DefectPendingVerificationStatusTest`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_08_02_000010_add_pending_verification_to_defects_status.php \
        app/Models/Defect.php app/Http/Controllers/DefectController.php \
        resources/views/defects/create.blade.php resources/views/defects/edit.blade.php \
        tests/Feature/DefectPendingVerificationStatusTest.php
git commit -m "feat: add PENDING_VERIFICATION defect status"
```

---

### Task 5: `Project::scopeVisibleTo` / `Defect::scopeVisibleTo`

**Files:**
- Modify: `app/Models/Project.php`
- Modify: `app/Models/Defect.php`
- Create: `database/factories/ProjectFactory.php`
- Create: `database/factories/DefectFactory.php`
- Test: `tests/Feature/ProjectVisibilityTest.php`, `tests/Feature/DefectVisibilityTest.php`

**Interfaces:**
- Consumes: `Project::assignedContractor()` (Task 2), `Project::supervisors()` (Task 3)
- Produces: `Project::scopeVisibleTo(Builder $query, User $user): Builder`, `Defect::scopeVisibleTo(Builder $query, User $user): Builder`, `Project::factory()`, `Defect::factory()`

- [ ] **Step 1: Write the failing tests**

`database/factories/ProjectFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'project_no'         => 'PRJ-' . fake()->unique()->numerify('####'),
            'project_name'       => fake()->streetName() . ' Residence',
            'developer_name'     => fake()->company(),
            'contractor_name'    => fake()->company(),
            'building_type'      => 'teres',
            'total_units'        => 10,
            'floor_area_sqm'     => 100,
            'calculated_samples' => 2,
            'overall_score'      => 0,
            'status'             => 'dalam_pemeriksaan',
            'created_by'         => User::factory(),
        ];
    }
}
```

`database/factories/DefectFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Defect;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Defect>
 */
class DefectFactory extends Factory
{
    protected $model = Defect::class;

    public function definition(): array
    {
        return [
            'project_id'         => Project::factory(),
            'component_name'     => 'D1',
            'location'           => 'Living Room',
            'defect_description' => 'Sample defect',
            'severity'           => 'low',
            'status'             => 'OPEN',
        ];
    }
}
```

`tests/Feature/ProjectVisibilityTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_every_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Project::factory()->count(3)->create();

        $this->assertCount(3, Project::visibleTo($admin)->get());
    }

    public function test_lead_auditor_sees_only_projects_they_created(): void
    {
        $auditorA = User::factory()->create(['role' => 'lead_auditor']);
        $auditorB = User::factory()->create(['role' => 'lead_auditor']);
        Project::factory()->create(['created_by' => $auditorA->id]);
        Project::factory()->create(['created_by' => $auditorB->id]);

        $visible = Project::visibleTo($auditorA)->get();
        $this->assertCount(1, $visible);
        $this->assertSame($auditorA->id, $visible->first()->created_by);
    }

    public function test_inspector_sees_only_assigned_projects(): void
    {
        $inspectorA = User::factory()->create(['role' => 'inspector']);
        $inspectorB = User::factory()->create(['role' => 'inspector']);
        Project::factory()->create(['assigned_to' => $inspectorA->id]);
        Project::factory()->create(['assigned_to' => $inspectorB->id]);

        $visible = Project::visibleTo($inspectorA)->get();
        $this->assertCount(1, $visible);
        $this->assertSame($inspectorA->id, $visible->first()->assigned_to);
    }

    public function test_supervisor_sees_only_linked_projects(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $linked = Project::factory()->create();
        $linked->supervisors()->attach($supervisor->id);
        Project::factory()->create(); // unlinked, not visible

        $visible = Project::visibleTo($supervisor)->get();
        $this->assertCount(1, $visible);
        $this->assertSame($linked->id, $visible->first()->id);
    }

    public function test_contractor_sees_only_their_assigned_project(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        Project::factory()->create(['assigned_contractor_id' => $contractor->id]);
        Project::factory()->create();

        $visible = Project::visibleTo($contractor)->get();
        $this->assertCount(1, $visible);
    }
}
```

`tests/Feature/DefectVisibilityTest.php`:

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ProjectVisibilityTest`
Run: `php artisan test --filter=DefectVisibilityTest`
Expected: FAIL — `Project::factory()` has no model bound (or `scopeVisibleTo` undefined method).

- [ ] **Step 3: Implement the scopes**

In `app/Models/Project.php`, add `use HasFactory;` to the class (add `use Illuminate\Database\Eloquent\Factories\HasFactory;` to the imports and `use HasFactory;` inside the class body, alongside the existing class declaration), and add the scope right after `getInspectionProgressAttribute()`:

```php
    public function scopeVisibleTo($query, User $user)
    {
        return match ($user->role) {
            'admin'        => $query,
            'lead_auditor' => $query->where('created_by', $user->id),
            'inspector'    => $query->where('assigned_to', $user->id),
            'supervisor'   => $query->whereHas('supervisors', fn ($q) => $q->where('user_id', $user->id)),
            'contractor'   => $query->where('assigned_contractor_id', $user->id),
            default        => $query->whereRaw('1 = 0'),
        };
    }
```

In `app/Models/Defect.php`, add `use HasFactory;` the same way, and add the scope right after `getStatusBadgeClassAttribute()`:

```php
    public function scopeVisibleTo($query, User $user)
    {
        return $query->whereHas('project', fn ($q) => $q->visibleTo($user));
    }
```

(`Defect.php` needs `use App\Models\User;` added to its imports for the type hint.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ProjectVisibilityTest`
Run: `php artisan test --filter=DefectVisibilityTest`
Expected: PASS (5 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Models/Project.php app/Models/Defect.php \
        database/factories/ProjectFactory.php database/factories/DefectFactory.php \
        tests/Feature/ProjectVisibilityTest.php tests/Feature/DefectVisibilityTest.php
git commit -m "feat: add visibleTo scopes for Project and Defect"
```

---

### Task 6: Apply scoping to list controllers (Projects index, Dashboard, Defects index)

**Files:**
- Modify: `app/Http/Controllers/ProjectController.php` (`index`, `dashboard`)
- Modify: `app/Http/Controllers/DefectController.php` (`index`)
- Test: `tests/Feature/ProjectListScopingTest.php`, `tests/Feature/DefectListScopingTest.php`

**Interfaces:**
- Consumes: `Project::scopeVisibleTo`, `Defect::scopeVisibleTo` (Task 5)

- [ ] **Step 1: Write the failing tests**

`tests/Feature/ProjectListScopingTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectListScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_index_only_shows_visible_projects(): void
    {
        $inspectorA = User::factory()->create(['role' => 'inspector']);
        $inspectorB = User::factory()->create(['role' => 'inspector']);
        $mine = Project::factory()->create(['assigned_to' => $inspectorA->id, 'project_name' => 'Mine']);
        Project::factory()->create(['assigned_to' => $inspectorB->id, 'project_name' => 'Not Mine']);

        $response = $this->actingAs($inspectorA)->get('/projects');

        $response->assertSee('Mine');
        $response->assertDontSee('Not Mine');
    }

    public function test_dashboard_totals_are_scoped(): void
    {
        $inspectorA = User::factory()->create(['role' => 'inspector']);
        $inspectorB = User::factory()->create(['role' => 'inspector']);
        Project::factory()->create(['assigned_to' => $inspectorA->id]);
        Project::factory()->create(['assigned_to' => $inspectorB->id]);

        $response = $this->actingAs($inspectorA)->get('/dashboard');

        $response->assertViewHas('total', 1);
    }
}
```

`tests/Feature/DefectListScopingTest.php`:

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ProjectListScopingTest`
Run: `php artisan test --filter=DefectListScopingTest`
Expected: FAIL — both roles currently see all projects/defects.

- [ ] **Step 3: Apply the scope**

In `app/Http/Controllers/ProjectController.php`, change the first line of `dashboard()`:

```php
        $total           = Project::count();
```
to:
```php
        $visible         = Project::visibleTo(auth()->user());
        $total           = $visible->count();
```

and change every subsequent `Project::` call in that method to `$visible->clone()->` to avoid the query builder being consumed twice — replace the whole method body's project queries:

```php
    public function dashboard()
    {
        $visible         = Project::visibleTo(auth()->user());
        $total           = $visible->clone()->count();
        $active          = $visible->clone()->where('status', 'dalam_pemeriksaan')->count();
        $completed       = $visible->clone()->where('status', 'selesai')->count();
        $draft           = $visible->clone()->where('status', 'draf')->count();
        $recent          = $visible->clone()->with(['creator', 'assignedInspector'])->latest()->take(8)->get();
        $avgScore        = $visible->clone()->where('overall_score', '>', 0)->avg('overall_score') ?? 0;
        $openDefects     = Defect::visibleTo(auth()->user())->where('status', 'OPEN')->count();
        $resolvedDefects = Defect::visibleTo(auth()->user())->where('status', 'RESOLVED')->count();
        $ratingBaik      = (float) setting('rating_baik', 85);
        $ratingMod       = (float) setting('rating_sederhana', 70);
        $meScore         = (float) setting('me_score', 2.0);

        return view('dashboard', compact(
            'total', 'active', 'completed', 'draft',
            'recent', 'avgScore', 'openDefects', 'resolvedDefects',
            'ratingBaik', 'ratingMod', 'meScore'
        ));
    }
```

In `index()`, change:
```php
        $query = Project::with(['creator', 'assignedInspector'])->latest();
```
to:
```php
        $query = Project::visibleTo(auth()->user())->with(['creator', 'assignedInspector'])->latest();
```

- [ ] **Step 4: Apply the scope to `DefectController::index`**

In `app/Http/Controllers/DefectController.php`, change:
```php
        $query = Defect::with(['project', 'media'])->latest();
```
to:
```php
        $query = Defect::visibleTo(auth()->user())->with(['project', 'media'])->latest();
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=ProjectListScopingTest`
Run: `php artisan test --filter=DefectListScopingTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ProjectController.php app/Http/Controllers/DefectController.php \
        tests/Feature/ProjectListScopingTest.php tests/Feature/DefectListScopingTest.php
git commit -m "feat: scope Projects/Dashboard/Defects lists to visibleTo()"
```

---

### Task 7: Route restructuring — exclude Contractor from Dashboard/Projects/Reports

**Files:**
- Modify: `routes/web.php`
- Test: `tests/Feature/ContractorRouteAccessTest.php`

**Interfaces:**
- Produces: `defects.advance` route name (added alongside the still-functional `defects.toggle`, wired up to a not-yet-existing controller method in Task 9); Contractor gets `403` on dashboard/projects/reports routes.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractorRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_contractor_is_blocked_from_dashboard_projects_and_reports(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);

        $this->actingAs($contractor)->get('/dashboard')->assertForbidden();
        $this->actingAs($contractor)->get('/projects')->assertForbidden();
        $this->actingAs($contractor)->get('/reports')->assertForbidden();
    }

    public function test_contractor_can_view_defects_index(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);

        $this->actingAs($contractor)->get('/defects')->assertOk();
    }

    public function test_supervisor_still_reaches_dashboard_projects_and_reports(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)->get('/dashboard')->assertOk();
        $this->actingAs($supervisor)->get('/projects')->assertOk();
        $this->actingAs($supervisor)->get('/reports')->assertOk();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ContractorRouteAccessTest`
Expected: FAIL — contractor currently gets `200` on `/dashboard`, `/projects`, `/reports`.

- [ ] **Step 3: Restructure the routes**

In `routes/web.php`, replace the whole authenticated group (from `Route::middleware('auth')->group(function () {` through its closing `});`) with:

```php
Route::middleware('auth')->group(function () {

    // Dashboard — everyone except Contractor (their surface is My Defects only)
    Route::middleware('role:admin,lead_auditor,inspector,supervisor')->group(function () {
        Route::get('/dashboard', [ProjectController::class, 'dashboard'])->name('dashboard');
    });

    // Projects — static routes FIRST to avoid {project} swallowing them
    Route::middleware('role:admin,lead_auditor,inspector')->group(function () {
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects',       [ProjectController::class, 'store'])->name('projects.store');
    });

    Route::middleware('role:admin,lead_auditor,inspector,supervisor')->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');

        // Projects with {project} parameter
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

        // Score & summary
        Route::get('/projects/{project}/score',   [InspectionController::class, 'score'])->name('projects.score');
        Route::get('/projects/{project}/summary', [InspectionController::class, 'summary'])->name('projects.summary');
    });

    Route::middleware('role:admin,lead_auditor,inspector')->group(function () {
        Route::get('/projects/{project}/edit',  [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}',       [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}',    [ProjectController::class, 'destroy'])->name('projects.destroy');

        // Sample generation
        Route::get('/projects/{project}/samples',  [ProjectController::class, 'samples'])->name('projects.samples');
        Route::post('/projects/{project}/samples', [ProjectController::class, 'storeSamples'])->name('projects.samples.store');

        // Inspection workflow
        Route::get('/projects/{project}/components',                 [InspectionController::class, 'components'])->name('projects.components');
        Route::get('/projects/{project}/inspect/{sample}',           [InspectionController::class, 'inspect'])->name('projects.inspect');
        Route::post('/projects/{project}/inspect/{sample}',          [InspectionController::class, 'storeAssessment'])->name('projects.inspect.store');
    });

    // Defects — static create route before parameterized routes. Index is open to
    // all 5 roles (Contractor's "My Defects" reuses it, scoped by Defect::visibleTo()).
    Route::get('/defects', [DefectController::class, 'index'])->name('defects.index');

    Route::middleware('role:admin,lead_auditor,inspector')->group(function () {
        Route::get('/defects/create',           [DefectController::class, 'create'])->name('defects.create');
        Route::post('/defects',                 [DefectController::class, 'store'])->name('defects.store');
        Route::get('/defects/{defect}/edit',    [DefectController::class, 'edit'])->name('defects.edit');
        Route::put('/defects/{defect}',         [DefectController::class, 'update'])->name('defects.update');
        Route::delete('/defects/{defect}',      [DefectController::class, 'destroy'])->name('defects.destroy');
        Route::post('/defects/{defect}/toggle', [DefectController::class, 'toggleStatus'])->name('defects.toggle');
    });

    Route::middleware('role:admin,lead_auditor,inspector,contractor')->group(function () {
        Route::post('/defects/{defect}/advance', [DefectController::class, 'advanceStatus'])->name('defects.advance');
    });

    // Reports — Contractor excluded (no Dashboard/Projects/Reports access)
    Route::middleware('role:admin,lead_auditor,inspector,supervisor')->group(function () {
        Route::get('/reports',               [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{project}',     [ReportController::class, 'show'])->name('reports.show');
        Route::get('/reports/{project}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    });

    // Users & Settings — admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/users',             [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create',      [UserController::class, 'create'])->name('users.create');
        Route::post('/users',            [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}',      [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}',   [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/settings',  [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
```

Note: this task ADDS the new `defects.advance` route alongside the existing `defects.toggle` route — it does NOT remove `defects.toggle` or touch `DefectController::toggleStatus()`. `defects.advance` points at `DefectController::advanceStatus()`, a method that doesn't exist yet (added in Task 9) — registering a route to a not-yet-existing controller method is harmless in Laravel; it only fails if that specific route is actually invoked, and nothing invokes `defects.advance` until Task 9/10. Keeping `defects.toggle` alive means `resources/views/defects/index.blade.php` (which still calls `route('defects.toggle', $defect)`) keeps working completely unchanged — no transient breakage, no dependency on jumping ahead to later tasks. Task 10 is what finally retires `defects.toggle` and `toggleStatus()`, in the same commit where it rewrites the view to stop referencing them.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ContractorRouteAccessTest`
Expected: PASS

Run: `php artisan test` (full suite)
Expected: All prior tests still PASS — `defects/index.blade.php`'s dead route reference doesn't break any test because no existing test renders that view yet (Task 6's `DefectListScopingTest` hits `/defects` and asserts visible `assertSee` text, which does not require rendering the per-row action buttons to pass — Laravel doesn't fail a request just because a named route doesn't exist unless that code path executes; confirm this test still passes before continuing, and if it doesn't, proceed to Task 9/10 immediately before shipping this task's commit).

- [ ] **Step 5: Commit**

```bash
git add routes/web.php tests/Feature/ContractorRouteAccessTest.php
git commit -m "feat: exclude Contractor from Dashboard/Projects/Reports routes"
```

---

### Task 8: Single-resource `403` visibility guards

**Files:**
- Modify: `app/Http/Controllers/Controller.php` (shared guard helpers)
- Modify: `app/Http/Controllers/ProjectController.php` (`show`, `edit`, `update`, `destroy`, `samples`, `storeSamples`)
- Modify: `app/Http/Controllers/InspectionController.php` (`components`, `inspect`, `storeAssessment`, `score`, `summary`)
- Modify: `app/Http/Controllers/ReportController.php` (`show`, `pdf`)
- Modify: `app/Http/Controllers/DefectController.php` (`edit`, `update`, `destroy`)
- Modify: `resources/views/components/workflow-step.blade.php` (pre-existing crash fix, see Step 7a)
- Test: `tests/Feature/ProjectAccessGuardTest.php`, `tests/Feature/DefectAccessGuardTest.php`

**Interfaces:**
- Consumes: `Project::scopeVisibleTo`, `Defect::scopeVisibleTo` (Task 5)
- Produces: `Controller::guardProjectVisible(Project $project): void`, `Controller::guardDefectVisible(Defect $defect): void` — every controller in this task and Task 9/15 calls these instead of a private per-controller copy, since `ProjectController`/`InspectionController`/`ReportController`/`DefectController` all already extend this shared base class.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/ProjectAccessGuardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAccessGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspector_cannot_open_a_project_not_assigned_to_them_by_url(): void
    {
        $inspectorA = User::factory()->create(['role' => 'inspector']);
        $inspectorB = User::factory()->create(['role' => 'inspector']);
        $othersProject = Project::factory()->create(['assigned_to' => $inspectorB->id]);

        $this->actingAs($inspectorA)->get("/projects/{$othersProject->id}")->assertForbidden();
        $this->actingAs($inspectorA)->get("/projects/{$othersProject->id}/score")->assertForbidden();
        $this->actingAs($inspectorA)->get("/projects/{$othersProject->id}/summary")->assertForbidden();
    }

    public function test_inspector_can_open_their_own_assigned_project(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $ownProject = Project::factory()->create(['assigned_to' => $inspector->id]);

        $this->actingAs($inspector)->get("/projects/{$ownProject->id}")->assertOk();
    }

    public function test_admin_can_open_any_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();

        $this->actingAs($admin)->get("/projects/{$project->id}")->assertOk();
    }
}
```

`tests/Feature/DefectAccessGuardTest.php`:

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ProjectAccessGuardTest`
Run: `php artisan test --filter=DefectAccessGuardTest`
Expected: FAIL — every URL currently returns `200` regardless of visibility.

- [ ] **Step 3: Add the shared guards to the base `Controller`**

In `app/Http/Controllers/Controller.php`, replace the whole file with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Project;

abstract class Controller
{
    protected function guardProjectVisible(Project $project): void
    {
        abort_unless(Project::visibleTo(auth()->user())->whereKey($project->id)->exists(), 403);
    }

    protected function guardDefectVisible(Defect $defect): void
    {
        abort_unless(Defect::visibleTo(auth()->user())->whereKey($defect->id)->exists(), 403);
    }
}
```

- [ ] **Step 4: Call the guard from `ProjectController`**

Add `$this->guardProjectVisible($project);` as the first line of the body of `show()`, `edit()`, `update()`, `destroy()`, `samples()`, and `storeSamples()`. For example, `show()` becomes:

```php
    public function show(Project $project)
    {
        $this->guardProjectVisible($project);

        $project->load(['samples', 'defects', 'creator', 'assignedInspector']);
        $openDefects     = $project->defects->where('status', 'OPEN')->count();
        $resolvedDefects = $project->defects->where('status', 'RESOLVED')->count();

        return view('projects.show', compact('project', 'openDefects', 'resolvedDefects'));
    }
```

Apply the identical one-line insertion (`$this->guardProjectVisible($project);` as the first statement) to `edit(Project $project)`, `update(Request $request, Project $project)`, `destroy(Project $project)`, `samples(Project $project)`, and `storeSamples(Request $request, Project $project)`.

- [ ] **Step 5: Call the guard from `InspectionController`**

Add `$this->guardProjectVisible($project);` as the first statement in `components()`, `inspect()`, `storeAssessment()`, `score()`, and `summary()`.

- [ ] **Step 6: Call the guard from `ReportController`**

Add `$this->guardProjectVisible($project);` as the first statement in `show(Project $project)` and `pdf(Project $project)`.

- [ ] **Step 7: Call the guard from `DefectController`**

Add `$this->guardDefectVisible($defect);` as the first statement in `edit(Defect $defect)`, `update(Request $request, Defect $defect)`, and `destroy(Defect $defect)`.

- [ ] **Step 7a: Fix a pre-existing crash this task's own tests expose**

`test_inspector_can_open_their_own_assigned_project` and `test_admin_can_open_any_project` (Step 1) create a `Project` via the factory with zero `ProjectSample` rows, then `GET /projects/{project}`. That page renders `<x-workflow-step>`, whose step 4 ("Inspection") disabled-check is `$isDisabled = ($num > (int)$step && !$project)` — since `$project` is truthy, this evaluates false regardless of whether any samples exist, so step 4 renders as a clickable link and tries `route('projects.inspect', [$project])`. That route requires two params (`{project}/inspect/{sample}`); with none of the project's (zero) samples available to fill the second slot, route generation throws and the page 500s. This is a pre-existing bug (real projects always get samples auto-generated by `ProjectController::store()`, so it was never hit before — factory-built test projects are the first thing to expose it).

In `resources/views/components/workflow-step.blade.php`, change:
```php
                $isDisabled  = ($num > (int)$step && !$project);
```
to:
```php
                $needsSample = $num === 4 && $project && $project->samples->isEmpty();
                $isDisabled  = ($num > (int)$step && !$project) || $needsSample;
```
(Task 13 later replaces this whole file's disabled-logic with a role-aware version that already includes this same `$needsSample` check — this fix is not lost, just superseded there.)

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test --filter=ProjectAccessGuardTest`
Run: `php artisan test --filter=DefectAccessGuardTest`
Expected: PASS

Run: `php artisan test` (full suite) to confirm no regressions from the new guards on previously-passing tests (e.g. Task 6/7's tests use projects the acting user is actually visible to, so they should be unaffected).

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Controller.php app/Http/Controllers/ProjectController.php \
        app/Http/Controllers/InspectionController.php \
        app/Http/Controllers/ReportController.php app/Http/Controllers/DefectController.php \
        resources/views/components/workflow-step.blade.php \
        tests/Feature/ProjectAccessGuardTest.php tests/Feature/DefectAccessGuardTest.php
git commit -m "feat: enforce visibility on single-resource project/defect routes"
```

---

### Task 9: Defect status transition guard (`advanceStatus`)

**Files:**
- Modify: `app/Http/Controllers/DefectController.php` (add `advanceStatus`, alongside the still-existing `toggleStatus` — do not remove `toggleStatus` in this task)
- Test: `tests/Feature/DefectAdvanceStatusTest.php`

**Interfaces:**
- Consumes: `Defect::visibleTo()` (Task 5)
- Produces: `DefectController::advanceStatus(Request $request, Defect $defect)`, POST body `to: string`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectAdvanceStatusTest extends TestCase
{
    use RefreshDatabase;

    private function defectFor(User $contractor): Defect
    {
        $project = Project::factory()->create(['assigned_contractor_id' => $contractor->id]);
        return Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);
    }

    public function test_contractor_can_start_repair(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $defect = $this->defectFor($contractor);

        $this->actingAs($contractor)
            ->post("/defects/{$defect->id}/advance", ['to' => 'IN_PROGRESS'])
            ->assertRedirect();

        $this->assertSame('IN_PROGRESS', $defect->fresh()->status);
    }

    public function test_contractor_can_mark_settled(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $defect = $this->defectFor($contractor);
        $defect->update(['status' => 'IN_PROGRESS']);

        $this->actingAs($contractor)
            ->post("/defects/{$defect->id}/advance", ['to' => 'PENDING_VERIFICATION'])
            ->assertRedirect();

        $this->assertSame('PENDING_VERIFICATION', $defect->fresh()->status);
    }

    public function test_contractor_cannot_confirm_resolved(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $defect = $this->defectFor($contractor);
        $defect->update(['status' => 'PENDING_VERIFICATION']);

        $this->actingAs($contractor)
            ->post("/defects/{$defect->id}/advance", ['to' => 'RESOLVED'])
            ->assertForbidden();

        $this->assertSame('PENDING_VERIFICATION', $defect->fresh()->status);
    }

    public function test_inspector_can_confirm_resolved_from_pending_verification(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        $defect = Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $this->actingAs($inspector)
            ->post("/defects/{$defect->id}/advance", ['to' => 'RESOLVED'])
            ->assertRedirect();

        $this->assertSame('RESOLVED', $defect->fresh()->status);
    }

    public function test_inspector_can_reject_from_pending_verification_back_to_in_progress(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        $defect = Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $this->actingAs($inspector)
            ->post("/defects/{$defect->id}/advance", ['to' => 'IN_PROGRESS'])
            ->assertRedirect();

        $this->assertSame('IN_PROGRESS', $defect->fresh()->status);
    }

    public function test_cannot_skip_a_state(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        $defect = Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);

        $this->actingAs($inspector)
            ->post("/defects/{$defect->id}/advance", ['to' => 'RESOLVED'])
            ->assertStatus(422);

        $this->assertSame('OPEN', $defect->fresh()->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DefectAdvanceStatusTest`
Expected: FAIL — `advanceStatus` method / route doesn't exist yet (route was added in Task 7 pointing at this not-yet-written method).

- [ ] **Step 3: Add `advanceStatus` alongside the existing `toggleStatus`**

In `app/Http/Controllers/DefectController.php`, leave the existing `toggleStatus()` method exactly as it is (it's still wired to the `defects.toggle` route and still used by the current view) and add this new method right after it:

```php
    private const TRANSITIONS = [
        'OPEN'                 => ['IN_PROGRESS'],
        'IN_PROGRESS'          => ['PENDING_VERIFICATION'],
        'PENDING_VERIFICATION' => ['RESOLVED', 'IN_PROGRESS'],
        'RESOLVED'             => ['OPEN'],
    ];

    private const CONTRACTOR_ALLOWED_EDGES = [
        'OPEN->IN_PROGRESS',
        'IN_PROGRESS->PENDING_VERIFICATION',
    ];

    public function advanceStatus(Request $request, Defect $defect)
    {
        $this->guardDefectVisible($defect);

        $data = $request->validate([
            'to' => 'required|in:OPEN,IN_PROGRESS,PENDING_VERIFICATION,RESOLVED',
        ]);

        $allowedTargets = self::TRANSITIONS[$defect->status] ?? [];
        abort_unless(in_array($data['to'], $allowedTargets, true), 422, 'Invalid status transition.');

        if (auth()->user()->role === 'contractor') {
            $edge = "{$defect->status}->{$data['to']}";
            abort_unless(in_array($edge, self::CONTRACTOR_ALLOWED_EDGES, true), 403, 'Contractors cannot perform this transition.');
        }

        $defect->update(['status' => $data['to']]);

        return redirect()->back()->with('success', "Defect status updated to {$data['to']}.");
    }
```

(`guardDefectVisible()` is inherited from the base `Controller` class added in Task 8.)

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=DefectAdvanceStatusTest`
Expected: PASS (6 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/DefectController.php tests/Feature/DefectAdvanceStatusTest.php
git commit -m "feat: add role-gated advanceStatus alongside existing toggleStatus"
```

---

### Task 10: Defects Register UI — per-state action buttons, Contractor "My Defects" relabel

**Files:**
- Modify: `resources/views/defects/index.blade.php`
- Modify: `app/Http/Controllers/DefectController.php` (remove the now-unused `toggleStatus()`)
- Modify: `routes/web.php` (remove the now-unused `defects.toggle` route)
- Test: `tests/Feature/DefectsIndexUiTest.php`

**Interfaces:**
- Consumes: `defects.advance` route (Task 7), `DefectController::advanceStatus()` (Task 9)

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectsIndexUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_contractor_sees_start_repair_button_on_open_defect(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::factory()->create(['assigned_contractor_id' => $contractor->id]);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'OPEN']);

        $response = $this->actingAs($contractor)->get('/defects');

        $response->assertSee('My Defects');
        $response->assertSee('Start Repair');
    }

    public function test_contractor_does_not_see_confirm_resolved_button(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::factory()->create(['assigned_contractor_id' => $contractor->id]);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $response = $this->actingAs($contractor)->get('/defects');

        $response->assertSee('Awaiting Verification');
        $response->assertDontSee('Confirm Resolved');
    }

    public function test_inspector_sees_confirm_and_reject_on_pending_verification(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $response = $this->actingAs($inspector)->get('/defects');

        $response->assertSee('Confirm Resolved');
        $response->assertSee('Reject');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DefectsIndexUiTest`
Expected: FAIL — the view still renders the old single toggle icon (no "Start Repair"/"Confirm Resolved"/"Awaiting Verification" text anywhere), and the title is still "Defects Register" for everyone.

- [ ] **Step 3: Update the page title/breadcrumb**

In `resources/views/defects/index.blade.php`, change line 3:

```blade
@section('title', 'Defects Register')
```
to:
```blade
@section('title', auth()->user()->role === 'contractor' ? 'My Defects' : 'Defects Register')
```

And change the breadcrumb section (lines 5-9):

```blade
@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">Defects Register</span>
@endsection
```
to:
```blade
@section('breadcrumb')
    @unless(auth()->user()->role === 'contractor')
        <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
        <span class="material-symbols-outlined text-sm">chevron_right</span>
    @endunless
    <span class="text-gray-900 font-bold">{{ auth()->user()->role === 'contractor' ? 'My Defects' : 'Defects Register' }}</span>
@endsection
```

- [ ] **Step 4: Replace the status filter's option list and the row action buttons**

Add a `PENDING_VERIFICATION` option to the filter dropdown — change:

```blade
            <option value="RESOLVED"    {{ request('status') === 'RESOLVED'    ? 'selected' : '' }}>Resolved</option>
```
to:
```blade
            <option value="PENDING_VERIFICATION" {{ request('status') === 'PENDING_VERIFICATION' ? 'selected' : '' }}>Pending Verification</option>
            <option value="RESOLVED"    {{ request('status') === 'RESOLVED'    ? 'selected' : '' }}>Resolved</option>
```

Add `PENDING_VERIFICATION` to the `$stCls`/`$stLabel` maps inside the `@foreach` block — change:

```blade
                                $stCls  = [
                                    'OPEN'        => 'bg-red-100 text-red-900 border-red-300',
                                    'IN_PROGRESS' => 'bg-amber-100 text-amber-900 border-amber-300',
                                    'RESOLVED'    => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                                ];
                                $stLabel = [
                                    'OPEN' => 'Open', 'IN_PROGRESS' => 'In Progress', 'RESOLVED' => 'Resolved'
                                ];
```
to:
```blade
                                $stCls  = [
                                    'OPEN'                 => 'bg-red-100 text-red-900 border-red-300',
                                    'IN_PROGRESS'          => 'bg-amber-100 text-amber-900 border-amber-300',
                                    'PENDING_VERIFICATION' => 'bg-blue-100 text-blue-900 border-blue-300',
                                    'RESOLVED'             => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                                ];
                                $stLabel = [
                                    'OPEN' => 'Open', 'IN_PROGRESS' => 'In Progress',
                                    'PENDING_VERIFICATION' => 'Pending Verification', 'RESOLVED' => 'Resolved',
                                ];
                                $role = auth()->user()->role;
                                $canAdvance = in_array($role, ['admin', 'lead_auditor', 'inspector', 'contractor']);
                                $canConfirmOrReject = in_array($role, ['admin', 'lead_auditor', 'inspector']);
```

Replace the entire Actions `<td>` block:

```blade
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        @if(auth()->user()->canInspect())
                                            {{-- Toggle Status Button --}}
                                            <form method="POST" action="{{ route('defects.toggle', $defect) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="p-2 text-gray-500 hover:text-amber-700 hover:bg-amber-50 rounded-xl transition min-h-[36px] flex items-center justify-center"
                                                        title="Advance Status (OPEN -> IN_PROGRESS -> RESOLVED)">
                                                    <span class="material-symbols-outlined text-lg">update</span>
                                                </button>
                                            </form>
                                            <a href="{{ route('defects.edit', $defect) }}"
                                               class="p-2 text-gray-500 hover:text-amber-700 hover:bg-amber-50 rounded-xl transition min-h-[36px] flex items-center justify-center" title="Edit Defect">
                                                <span class="material-symbols-outlined text-lg">edit</span>
                                            </a>
                                            <button
                                                @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('defects.destroy', $defect) }}', method: 'DELETE' })"
                                                class="p-2 text-gray-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition min-h-[36px] flex items-center justify-center" title="Delete Defect">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
```

with:

```blade
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        @if($defect->status === 'OPEN' && $canAdvance)
                                            <form method="POST" action="{{ route('defects.advance', $defect) }}">
                                                @csrf
                                                <input type="hidden" name="to" value="IN_PROGRESS">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition min-h-[36px]">
                                                    Start Repair
                                                </button>
                                            </form>
                                        @elseif($defect->status === 'IN_PROGRESS' && $canAdvance)
                                            <form method="POST" action="{{ route('defects.advance', $defect) }}">
                                                @csrf
                                                <input type="hidden" name="to" value="PENDING_VERIFICATION">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-blue-800 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition min-h-[36px]">
                                                    Mark Settled
                                                </button>
                                            </form>
                                        @elseif($defect->status === 'PENDING_VERIFICATION' && $canConfirmOrReject)
                                            <form method="POST" action="{{ route('defects.advance', $defect) }}">
                                                @csrf
                                                <input type="hidden" name="to" value="RESOLVED">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition min-h-[36px]">
                                                    Confirm Resolved
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('defects.advance', $defect) }}">
                                                @csrf
                                                <input type="hidden" name="to" value="IN_PROGRESS">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-red-800 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition min-h-[36px]">
                                                    Reject – Not Fixed
                                                </button>
                                            </form>
                                        @elseif($defect->status === 'PENDING_VERIFICATION')
                                            <span class="px-3 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg">
                                                Awaiting Verification
                                            </span>
                                        @elseif($defect->status === 'RESOLVED' && $canConfirmOrReject)
                                            <form method="POST" action="{{ route('defects.advance', $defect) }}">
                                                @csrf
                                                <input type="hidden" name="to" value="OPEN">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-gray-700 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition min-h-[36px]">
                                                    Reopen
                                                </button>
                                            </form>
                                        @endif
                                        @if(auth()->user()->canInspect())
                                            <a href="{{ route('defects.edit', $defect) }}"
                                               class="p-2 text-gray-500 hover:text-amber-700 hover:bg-amber-50 rounded-xl transition min-h-[36px] flex items-center justify-center" title="Edit Defect">
                                                <span class="material-symbols-outlined text-lg">edit</span>
                                            </a>
                                            <button
                                                @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('defects.destroy', $defect) }}', method: 'DELETE' })"
                                                class="p-2 text-gray-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition min-h-[36px] flex items-center justify-center" title="Delete Defect">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
```

- [ ] **Step 5: Retire the now-unused `toggleStatus()` and `defects.toggle` route**

The view no longer references `route('defects.toggle', ...)` anywhere after Step 4's replacement — confirm with `grep -rn "defects.toggle" resources/views/` (expect no matches). Now remove what nothing points to anymore:

In `app/Http/Controllers/DefectController.php`, delete the `toggleStatus()` method entirely (it's the old cyclical `OPEN -> IN_PROGRESS -> RESOLVED -> OPEN` method that `advanceStatus()`, added in Task 9, has fully replaced).

In `routes/web.php`, delete this line from the `role:admin,lead_auditor,inspector` Defects group:
```php
        Route::post('/defects/{defect}/toggle', [DefectController::class, 'toggleStatus'])->name('defects.toggle');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=DefectsIndexUiTest`
Expected: PASS

Run: `php artisan test` (full suite) to confirm nothing else referenced `defects.toggle`/`toggleStatus` and everything is still green now that both are gone.

- [ ] **Step 7: Commit**

```bash
git add resources/views/defects/index.blade.php app/Http/Controllers/DefectController.php \
        routes/web.php tests/Feature/DefectsIndexUiTest.php
git commit -m "feat: role/state-aware defect action buttons, retire toggleStatus"
```

---

### Task 11: Contractor login redirect & navigation

**Files:**
- Modify: `app/Http/Controllers/AuthController.php`
- Modify: `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/ContractorLoginRedirectTest.php`

**Interfaces:**
- Consumes: `User::isContractor()` (Task 1)

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContractorLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_contractor_is_redirected_to_defects_index_after_login(): void
    {
        User::factory()->create(['role' => 'contractor', 'email' => 'qc@test.local', 'password' => Hash::make('password123')]);

        $response = $this->post('/login', ['email' => 'qc@test.local', 'password' => 'password123']);

        $response->assertRedirect(route('defects.index'));
    }

    public function test_inspector_is_still_redirected_to_dashboard_after_login(): void
    {
        User::factory()->create(['role' => 'inspector', 'email' => 'insp@test.local', 'password' => Hash::make('password123')]);

        $response = $this->post('/login', ['email' => 'insp@test.local', 'password' => 'password123']);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_contractor_nav_shows_only_my_defects(): void
    {
        $contractor = User::factory()->create(['role' => 'contractor']);

        $response = $this->actingAs($contractor)->get('/defects');

        $response->assertDontSee('System Administration');
        $response->assertSee('My Defects');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ContractorLoginRedirectTest`
Expected: FAIL — login always redirects to `dashboard`, which now 403s for Contractor (breaking the redirect assertion); nav shows the generic "Defects Register" label and all four links regardless of role.

- [ ] **Step 3: Add the role-based redirect helper**

In `app/Http/Controllers/AuthController.php`, add a private method and use it in both places that redirect to `dashboard`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private function homeRoute(User $user): string
    {
        return $user->isContractor() ? route('defects.index') : route('dashboard');
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect($this->homeRoute(Auth::user()));
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended($this->homeRoute(Auth::user()))
                ->with('success', 'Selamat datang, ' . Auth::user()->name . '!');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'E-mel atau kata laluan tidak sah.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Anda telah log keluar.');
    }
}
```

- [ ] **Step 4: Update the sidebar navigation**

In `resources/views/layouts/app.blade.php`, replace:

```blade
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <x-nav-item route="dashboard"      icon="grid_view" label="Dashboard" />
                <x-nav-item route="projects.index" icon="domain"    label="Projects" :match="['projects.*']" />
                <x-nav-item route="defects.index"  icon="warning"   label="Defects Register" />
                <x-nav-item route="reports.index"  icon="description" label="G-IDS Reports" :match="['reports.*']" />

                @if(auth()->user()->isAdmin())
```

with:

```blade
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                @if(auth()->user()->isContractor())
                    <x-nav-item route="defects.index"  icon="warning"   label="My Defects" />
                @else
                    <x-nav-item route="dashboard"      icon="grid_view" label="Dashboard" />
                    <x-nav-item route="projects.index" icon="domain"    label="Projects" :match="['projects.*']" />
                    <x-nav-item route="defects.index"  icon="warning"   label="Defects Register" />
                    <x-nav-item route="reports.index"  icon="description" label="G-IDS Reports" :match="['reports.*']" />
                @endif

                @if(auth()->user()->isAdmin())
```

(the rest of the `nav` block, the admin-only section, stays unchanged.)

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=ContractorLoginRedirectTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/AuthController.php resources/views/layouts/app.blade.php \
        tests/Feature/ContractorLoginRedirectTest.php
git commit -m "feat: contractor login redirect and My Defects only navigation"
```

---

### Task 12: `Project::nextActionFor()`

**Files:**
- Modify: `app/Models/Project.php`
- Test: `tests/Unit/ProjectNextActionTest.php`

**Interfaces:**
- Produces: `Project::nextActionFor(User $user): array{icon: string, text: string}`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

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
    }

    public function test_admin_is_told_inspection_is_pending_once_locations_are_named(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inspector = User::factory()->create(['role' => 'inspector', 'name' => 'Aiman']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($admin);

        $this->assertStringContainsString('Aiman', $action['text']);
    }

    public function test_inspector_is_told_to_start_the_grid_when_nothing_inspected(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('Start the Components Grid', $action['text']);
    }

    public function test_inspector_sees_pending_verification_count(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = $this->baseProject(['assigned_to' => $inspector->id]);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);
        Defect::factory()->create(['project_id' => $project->id, 'status' => 'PENDING_VERIFICATION']);

        $action = $project->fresh()->nextActionFor($inspector);

        $this->assertStringContainsString('awaiting your verification', $action['text']);
    }

    public function test_supervisor_sees_a_read_only_progress_line(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = $this->baseProject();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $action = $project->fresh()->nextActionFor($supervisor);

        $this->assertStringContainsString('sample units inspected', $action['text']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProjectNextActionTest`
Expected: FAIL — `nextActionFor()` doesn't exist.

- [ ] **Step 3: Implement `nextActionFor()`**

In `app/Models/Project.php`, add right after `getInspectionProgressAttribute()`:

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

        if (in_array($user->role, ['admin', 'lead_auditor'])) {
            if (!$hasNamedLocations) {
                return ['icon' => 'tune', 'text' => 'Finish naming sample locations.'];
            }
            if (!$inspectionStarted) {
                $name = $this->assignedInspector->name ?? 'the assigned inspector';
                return ['icon' => 'grid_on', 'text' => "Waiting on Inspector {$name} to begin the Components Grid inspection."];
            }
            if ($openDefects > 0 || $pendingVerify > 0) {
                $count = $openDefects + $pendingVerify;
                return ['icon' => 'warning', 'text' => "{$count} defect(s) still open — waiting on Contractor & Inspector verification."];
            }
            return ['icon' => 'analytics', 'text' => 'Inspection complete — ready to generate the signed G-IDS PDF.'];
        }

        if ($user->role === 'inspector') {
            if (!$inspectionStarted) {
                return ['icon' => 'grid_on', 'text' => 'Start the Components Grid inspection.'];
            }
            if ($pendingVerify > 0) {
                return ['icon' => 'fact_check', 'text' => "{$pendingVerify} defect(s) awaiting your verification."];
            }
            if (!$inspectionDone) {
                return ['icon' => 'grid_on', 'text' => "Continue — {$inspectedCount}/{$totalSamples} sample units done."];
            }
            return ['icon' => 'task_alt', 'text' => 'Inspection complete — notify your Lead Auditor.'];
        }

        if ($user->role === 'supervisor') {
            if ($this->status === 'selesai') {
                return ['icon' => 'description', 'text' => 'Certificate ready for download.'];
            }
            return ['icon' => 'schedule', 'text' => "In progress — {$inspectedCount}/{$totalSamples} sample units inspected."];
        }

        return ['icon' => 'info', 'text' => ''];
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ProjectNextActionTest`
Expected: PASS (5 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Models/Project.php tests/Unit/ProjectNextActionTest.php
git commit -m "feat: add Project::nextActionFor for role-aware guidance"
```

---

### Task 13: Role-aware stepper + banner on the project page

**Files:**
- Modify: `resources/views/components/workflow-step.blade.php`
- Modify: `resources/views/projects/show.blade.php`
- Test: `tests/Feature/ProjectShowBannerTest.php`

**Interfaces:**
- Consumes: `Project::nextActionFor()` (Task 12)

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectShowBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_the_next_action_banner_on_project_show(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Sample 1']);

        $response = $this->actingAs($admin)->get("/projects/{$project->id}");

        $response->assertSee('Finish naming sample locations');
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
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProjectShowBannerTest`
Expected: FAIL — no banner text rendered yet, no "Handled by Inspector" state on the stepper.

- [ ] **Step 3: Add a `:role` prop to `<x-workflow-step>`**

In `resources/views/components/workflow-step.blade.php`, change the `@props` block and the per-step rendering. Replace the whole file with:

```blade
@props([
    'step' => 1,
    'project' => null,
    'role' => null,
])

@php
    $steps = [
        1 => ['label' => 'Project Details', 'route' => 'projects.show', 'icon' => 'assignment'],
        2 => ['label' => 'Sample Setup',   'route' => 'projects.samples', 'icon' => 'view_module'],
        3 => ['label' => 'Components Grid','route' => 'projects.components', 'icon' => 'grid_on'],
        4 => ['label' => 'Inspection',     'route' => 'projects.inspect', 'icon' => 'rule'],
        5 => ['label' => 'G-IDS Score',    'route' => 'projects.score', 'icon' => 'analytics'],
    ];
    $isManager = in_array($role, ['admin', 'lead_auditor']);
@endphp

<div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-4 mb-6">
    <div class="flex items-center justify-between overflow-x-auto gap-2 no-scrollbar py-1">
        @foreach($steps as $num => $info)
            @php
                $isCurrent      = ($num === (int)$step);
                $isCompleted    = ($num < (int)$step);
                $isNotMyStep    = $isManager && $num >= 3;
                $needsSample    = $num === 4 && $project && $project->samples->isEmpty();
                $isDisabled     = ($num > (int)$step && !$project) || $isNotMyStep || $needsSample;

                $routeParams = $project ? [$project] : [];
                if ($num === 4 && $project && $project->samples->first()) {
                    $routeParams = [$project, $project->samples->first()];
                }
            @endphp

            @if($isDisabled || !$project)
                <div class="flex items-center gap-2 text-gray-300 opacity-60 shrink-0 select-none">
                    <div class="w-8 h-8 rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center text-xs font-bold">
                        {{ $num }}
                    </div>
                    <span class="text-xs font-medium text-gray-400 hidden lg:inline">
                        {{ $isNotMyStep ? 'Handled by Inspector' : $info['label'] }}
                    </span>
                </div>
            @else
                <a href="{{ route($info['route'], $routeParams) }}"
                   class="flex items-center gap-2 shrink-0 group focus:outline-none focus:ring-2 focus:ring-eids-accent rounded-full min-h-[44px] px-2"
                   title="{{ $info['label'] }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-200
                        {{ $isCurrent ? 'bg-eids-primary text-white shadow-md shadow-eids-primary/20 ring-4 ring-eids-primary/10 scale-105' : ($isCompleted ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-gray-100 text-gray-600 group-hover:bg-gray-200') }}">
                        @if($isCompleted)
                            <span class="material-symbols-outlined text-base font-extrabold text-emerald-700">check</span>
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    <span class="text-xs transition-colors
                        {{ $isCurrent ? 'text-eids-primary font-bold' : ($isCompleted ? 'text-gray-800 font-semibold' : 'text-gray-500 group-hover:text-gray-800') }} hidden sm:inline">
                        {{ $info['label'] }}
                    </span>
                </a>
            @endif

            @if($num < count($steps))
                <div class="flex-1 min-w-3 h-0.5 {{ $num < (int)$step ? 'bg-emerald-400' : 'bg-gray-200' }} hidden sm:block"></div>
            @endif
        @endforeach
    </div>
</div>
```

- [ ] **Step 4: Wire the banner and role-aware stepper into `projects/show.blade.php`**

Replace the entire `@php ... @endphp` block right after the `@endsection` breadcrumb section (the one computing `$doneAssessments`/`$hasConfiguredSamples`/`$nextRoute`/`$nextLabel`/`$nextIcon`) — delete it. It's replaced by the model method; nothing in the file references `$nextRoute`/`$nextLabel`/`$nextIcon` after this task, since Step 5 below rewrites the block that used them.

Change the `@section('topbar-actions')` block from:

```blade
@section('topbar-actions')
    @if(auth()->user()->canInspect())
        <a href="{{ route('projects.edit', $project) }}"
           class="flex items-center gap-1.5 px-4 py-2.5 border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-100 transition min-h-[44px]">
            <span class="material-symbols-outlined text-lg">edit</span>
            Edit
        </a>
        <a href="{{ $nextRoute }}"
           class="flex items-center gap-2 px-5 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs min-h-[44px]">
            <span class="material-symbols-outlined text-lg">{{ $nextIcon }}</span>
            {{ $nextLabel }}
        </a>
    @endif
@endsection
```

to:

```blade
@php
    $nextAction = $project->nextActionFor(auth()->user());
@endphp

@section('topbar-actions')
    @if(auth()->user()->canInspect())
        <a href="{{ route('projects.edit', $project) }}"
           class="flex items-center gap-1.5 px-4 py-2.5 border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-100 transition min-h-[44px]">
            <span class="material-symbols-outlined text-lg">edit</span>
            Edit
        </a>
    @endif
@endsection
```

Change the `{{-- Pipeline Step Indicator --}}` line from:

```blade
    <x-workflow-step step="1" :project="$project" />
```

to:

```blade
    <x-workflow-step step="1" :project="$project" :role="auth()->user()->role" />

    {{-- Next Action Banner --}}
    <div class="bg-eids-primary/5 border border-eids-primary/15 rounded-2xl p-4 mb-6 flex items-center gap-3">
        <span class="material-symbols-outlined text-eids-accent text-2xl shrink-0">{{ $nextAction['icon'] }}</span>
        <span class="text-sm font-bold text-gray-900">{{ $nextAction['text'] }}</span>
    </div>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=ProjectShowBannerTest`
Expected: PASS

Run: `php artisan test` (full suite) — the deleted `$nextRoute`/`$nextLabel`/`$nextIcon` variables are no longer referenced anywhere in the file, so no undefined-variable errors should surface; if `php artisan test --filter=ProjectAccessGuardTest` (Task 8) breaks because it rendered `projects.show` and depended on the old variables, fix by confirming no other section of the view still calls `$nextRoute` (grep the file for `nextRoute` to confirm zero remaining references before moving on).

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/workflow-step.blade.php resources/views/projects/show.blade.php \
        tests/Feature/ProjectShowBannerTest.php
git commit -m "feat: role-aware stepper and next-action banner on project page"
```

---

### Task 14: Dashboard "Action Required" widget

**Files:**
- Modify: `app/Http/Controllers/ProjectController.php` (`dashboard`)
- Modify: `resources/views/dashboard.blade.php`
- Test: `tests/Feature/DashboardActionRequiredTest.php`

**Interfaces:**
- Consumes: `Project::visibleTo()` (Task 5), `Project::nextActionFor()` (Task 12)

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActionRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspector_sees_their_unstarted_project_in_action_required(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id, 'project_name' => 'Needs Action']);
        ProjectSample::create(['project_id' => $project->id, 'sample_index' => 1, 'location_name' => 'Master Bedroom']);

        $response = $this->actingAs($inspector)->get('/dashboard');

        $response->assertSee('Action Required');
        $response->assertSee('Needs Action');
    }

    public function test_supervisor_dashboard_has_no_action_required_widget(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $response = $this->actingAs($supervisor)->get('/dashboard');

        $response->assertDontSee('Action Required');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DashboardActionRequiredTest`
Expected: FAIL — no such section exists on the dashboard yet.

- [ ] **Step 3: Compute the widget's project list in the controller**

In `app/Http/Controllers/ProjectController.php`, in `dashboard()`, add right before the `return view(...)` line:

```php
        $actionRequired = collect();
        if (in_array(auth()->user()->role, ['admin', 'lead_auditor', 'inspector'])) {
            $actionRequired = $visible->clone()
                ->with(['assignedInspector'])
                ->get()
                ->filter(function ($project) {
                    $action = $project->nextActionFor(auth()->user());
                    return !str_contains($action['text'], 'complete') && $action['text'] !== '';
                })
                ->take(5);
        }
```

And add `'actionRequired'` to the `compact(...)` call in the `return view('dashboard', compact(...))` line.

- [ ] **Step 4: Render the widget**

In `resources/views/dashboard.blade.php`, add this block right before the closing `{{-- Global Defect Overview Widget --}}`'s wrapping `</div>` — i.e. insert it as a new card right after the `{{-- Average G-IDS Performance Hero Card --}}` block's closing `</div>` and before `{{-- Global Defect Overview Widget --}}`:

```blade
            {{-- Action Required Widget --}}
            @if($actionRequired->isNotEmpty())
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
                    <div class="text-xs uppercase tracking-wider text-gray-500 font-bold mb-4 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-amber-500">priority_high</span>
                        Action Required ({{ $actionRequired->count() }})
                    </div>
                    <div class="space-y-2">
                        @foreach($actionRequired as $project)
                            @php $action = $project->nextActionFor(auth()->user()); @endphp
                            <a href="{{ route('projects.show', $project) }}"
                               class="flex items-center gap-2 p-2.5 rounded-xl hover:bg-gray-50 transition text-xs">
                                <span class="material-symbols-outlined text-base text-eids-accent shrink-0">{{ $action['icon'] }}</span>
                                <span class="font-bold text-gray-900 shrink-0">{{ $project->project_name }}</span>
                                <span class="text-gray-500 truncate">— {{ $action['text'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=DashboardActionRequiredTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ProjectController.php resources/views/dashboard.blade.php \
        tests/Feature/DashboardActionRequiredTest.php
git commit -m "feat: add Action Required widget to dashboard"
```

---

### Task 15: Project Create/Edit — Assigned Contractor & Supervisors fields

**Files:**
- Modify: `app/Http/Controllers/ProjectController.php` (`create`, `store`, `edit`, `update`)
- Modify: `resources/views/projects/create.blade.php`, `resources/views/projects/edit.blade.php`
- Test: `tests/Feature/ProjectAssignmentFormTest.php`

**Interfaces:**
- Consumes: `Project::assignedContractor()` (Task 2), `Project::supervisors()` (Task 3)

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAssignmentFormTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'project_no'      => 'PRJ-FORM-001',
            'project_name'    => 'Form Test',
            'developer_name'  => 'Dev Co',
            'contractor_name' => 'Contractor Co',
            'building_type'   => 'teres',
            'total_units'     => 10,
            'floor_area_sqm'  => 100,
            'status'          => 'draf',
        ], $overrides);
    }

    public function test_admin_can_assign_a_contractor_and_supervisors_on_create(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contractor = User::factory()->create(['role' => 'contractor']);
        $supervisorA = User::factory()->create(['role' => 'supervisor']);
        $supervisorB = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($admin)->post('/projects', $this->validPayload([
            'assigned_contractor_id' => $contractor->id,
            'supervisor_ids'         => [$supervisorA->id, $supervisorB->id],
        ]));

        $project = Project::where('project_no', 'PRJ-FORM-001')->firstOrFail();
        $this->assertSame($contractor->id, $project->assigned_contractor_id);
        $this->assertCount(2, $project->supervisors);
    }

    public function test_inspector_cannot_set_contractor_or_supervisors_via_edit(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);
        $contractor = User::factory()->create(['role' => 'contractor']);
        $project = Project::factory()->create(['assigned_to' => $inspector->id]);

        $this->actingAs($inspector)->put("/projects/{$project->id}", $this->validPayload([
            'project_no'             => $project->project_no,
            'assigned_contractor_id' => $contractor->id,
        ]));

        $this->assertNull($project->fresh()->assigned_contractor_id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProjectAssignmentFormTest`
Expected: FAIL — `store`/`update` don't accept or persist `assigned_contractor_id`/`supervisor_ids` yet.

- [ ] **Step 3: Update `create()` and `edit()` to load the new dropdown data**

In `app/Http/Controllers/ProjectController.php`, change `create()`:

```php
    public function create()
    {
        $inspectors  = User::whereIn('role', ['admin', 'lead_auditor', 'inspector'])->orderBy('name')->get();
        $contractors = User::where('role', 'contractor')->orderBy('name')->get();
        $supervisors = User::where('role', 'supervisor')->orderBy('name')->get();
        return view('projects.create', compact('inspectors', 'contractors', 'supervisors'));
    }
```

Change `edit()`:

```php
    public function edit(Project $project)
    {
        $this->guardProjectVisible($project);

        $inspectors  = User::whereIn('role', ['admin', 'lead_auditor', 'inspector'])->orderBy('name')->get();
        $contractors = User::where('role', 'contractor')->orderBy('name')->get();
        $supervisors = User::where('role', 'supervisor')->orderBy('name')->get();
        return view('projects.edit', compact('project', 'inspectors', 'contractors', 'supervisors'));
    }
```

- [ ] **Step 4: Update `store()` to accept and persist the new fields**

In `store()`, change the validation array to add, right after `'assigned_to' => 'nullable|exists:users,id',`:

```php
            'assigned_contractor_id' => 'nullable|exists:users,id',
            'supervisor_ids'         => 'nullable|array',
            'supervisor_ids.*'       => 'exists:users,id',
```

Right after the `$data = $request->validate([...]);` block (before `$data['calculated_samples'] = ...`), add:

```php
        $supervisorIds = $data['supervisor_ids'] ?? [];
        unset($data['supervisor_ids']);

        if (!auth()->user()->isAdmin() && !auth()->user()->isLeadAuditor()) {
            unset($data['assigned_contractor_id']);
            $supervisorIds = [];
        }
```

Right after `$project = Project::create($data);`, add:

```php
        $project->supervisors()->sync($supervisorIds);
```

- [ ] **Step 5: Update `update()` the same way**

In `update()`, add the same three validation lines after `'assigned_to' => 'nullable|exists:users,id',`, and right after the `$data = $request->validate([...]);` block (before `$project->update($data);`), add:

```php
        $supervisorIds = $data['supervisor_ids'] ?? null;
        unset($data['supervisor_ids']);

        if (!auth()->user()->isAdmin() && !auth()->user()->isLeadAuditor()) {
            unset($data['assigned_contractor_id']);
            $supervisorIds = null;
        }

        $project->update($data);

        if ($supervisorIds !== null) {
            $project->supervisors()->sync($supervisorIds);
        }
```

(remove the old standalone `$project->update($data);` line right after this block since it's now inside the snippet above.)

- [ ] **Step 6: Add the fields to the Create form**

In `resources/views/projects/create.blade.php`, right after the "Assigned Inspector" `<div>` block (after its closing `</div>`, before the closing `</div>` of the grid), add:

```blade
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Assigned Contractor</label>
                    <select name="assigned_contractor_id"
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="">Unassigned</option>
                        @foreach($contractors as $contractor)
                            <option value="{{ $contractor->id }}" {{ old('assigned_contractor_id') == $contractor->id ? 'selected' : '' }}>
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_contractor_id')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Supervisors / Client Viewers</label>
                    <div class="flex flex-wrap gap-3 p-3 border border-gray-200 rounded-xl">
                        @forelse($supervisors as $supervisor)
                            <label class="flex items-center gap-1.5 text-sm font-medium">
                                <input type="checkbox" name="supervisor_ids[]" value="{{ $supervisor->id }}"
                                       {{ collect(old('supervisor_ids', []))->contains($supervisor->id) ? 'checked' : '' }}>
                                {{ $supervisor->name }}
                            </label>
                        @empty
                            <span class="text-xs text-gray-400 italic">No supervisor accounts yet.</span>
                        @endforelse
                    </div>
                </div>
```

- [ ] **Step 7: Add the fields to the Edit form**

In `resources/views/projects/edit.blade.php`, right after the "Assigned Inspector" `<div>` block, add the same two blocks, with `old(..., $project->assigned_contractor_id)` for the select's comparison and `collect(old('supervisor_ids', $project->supervisors->pluck('id')->toArray()))` for the checkboxes:

```blade
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Assigned Contractor</label>
                    <select name="assigned_contractor_id"
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="">Unassigned</option>
                        @foreach($contractors as $contractor)
                            <option value="{{ $contractor->id }}" {{ old('assigned_contractor_id', $project->assigned_contractor_id) == $contractor->id ? 'selected' : '' }}>
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_contractor_id')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Supervisors / Client Viewers</label>
                    <div class="flex flex-wrap gap-3 p-3 border border-gray-200 rounded-xl">
                        @forelse($supervisors as $supervisor)
                            <label class="flex items-center gap-1.5 text-sm font-medium">
                                <input type="checkbox" name="supervisor_ids[]" value="{{ $supervisor->id }}"
                                       {{ collect(old('supervisor_ids', $project->supervisors->pluck('id')->toArray()))->contains($supervisor->id) ? 'checked' : '' }}>
                                {{ $supervisor->name }}
                            </label>
                        @empty
                            <span class="text-xs text-gray-400 italic">No supervisor accounts yet.</span>
                        @endforelse
                    </div>
                </div>
```

- [ ] **Step 8: Run test to verify it passes**

Run: `php artisan test --filter=ProjectAssignmentFormTest`
Expected: PASS

Run: `php artisan test` (full suite) — this is the last task in the plan; confirm every test across every prior task still passes.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/ProjectController.php \
        resources/views/projects/create.blade.php resources/views/projects/edit.blade.php \
        tests/Feature/ProjectAssignmentFormTest.php
git commit -m "feat: assign contractor and supervisors on project create/edit"
```
