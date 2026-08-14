<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_management_flow()
    {
        // 1. Setup Admin and Counselor
        $admin = clone \App\Models\User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
        ]);

        $counselor = clone \App\Models\User::factory()->create([
            'role' => 'guidance_counselor',
            'is_active' => true,
            'password' => Hash::make('password123'),
        ]);

        // 2. Test Counselor gets 403 when accessing manage/accounts
        $response = $this->actingAs($counselor)->get('/manage/accounts');
        $response->assertStatus(403);

        // 3. Test Admin can access manage/accounts
        $response = $this->actingAs($admin)->get('/manage/accounts');
        $response->assertStatus(200);

        // 4. Test Deactivating Counselor
        $response = $this->actingAs($admin)->patch("/manage/accounts/{$counselor->id}/toggle-status");
        $response->assertRedirect(route('manage.accounts.index'));
        
        $this->assertDatabaseHas('users', [
            'id' => $counselor->id,
            'is_active' => false,
            'deactivated_by' => $admin->id,
        ]);

        // 5. Test Counselor Cannot Login when deactivated
        // We must log out the admin first
        $this->post('/logout');

        $response = $this->post('/login', [
            'identifier' => $counselor->email,
            'password' => 'password123',
        ]);
        
        // Should have validation error
        $response->assertSessionHasErrors(['identifier' => 'Your account has been deactivated. Contact your system administrator.']);
        $this->assertGuest();

        // 6. Test Reactivating Counselor
        $response = $this->actingAs($admin)->patch("/manage/accounts/{$counselor->id}/toggle-status");
        
        $this->assertDatabaseHas('users', [
            'id' => $counselor->id,
            'is_active' => true,
            'deactivated_by' => null,
            'deactivated_at' => null,
        ]);

        // 7. Test Counselor Can Login after reactivation
        $this->post('/logout');

        $response = $this->post('/login', [
            'identifier' => $counselor->email,
            'password' => 'password123',
        ]);
        
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($counselor);
    }
}
