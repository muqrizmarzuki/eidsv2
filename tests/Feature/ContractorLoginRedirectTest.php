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
