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
