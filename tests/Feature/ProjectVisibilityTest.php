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

    public function test_user_with_unrecognized_role_sees_nothing(): void
    {
        // `role` is a DB-level enum (admin/lead_auditor/inspector/supervisor/contractor),
        // so a User with a bogus role can never be persisted — Eloquent's ->create()
        // would throw a CHECK-constraint violation. We build an unpersisted instance
        // instead: scopeVisibleTo's default arm only reads $user->role, so this still
        // exercises the exact code path a real "role not covered by any match arm"
        // scenario would hit, without fighting the DB constraint that guards against
        // it in production.
        $bogusRoleUser = User::factory()->make(['role' => 'auditor_trainee']);
        Project::factory()->count(2)->create();

        $this->assertCount(0, Project::visibleTo($bogusRoleUser)->get());
    }
}
