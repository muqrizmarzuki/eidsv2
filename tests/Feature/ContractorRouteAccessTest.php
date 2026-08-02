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
