<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_does_not_contain_dashboard_pop_up(): void
    {
        $response = $this->withSession([
            'status' => 'Goodbye, Bob! You have been successfully logged out.',
            'success' => 'Goodbye, Bob! You have been successfully logged out.',
        ])->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('login-popup-heading');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'student', 'first_name' => 'Alice', 'is_active' => true, 'email_verified_at' => now()]);

        // Standard web login
        $response = $this->post('/login', [
            'identifier' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('success', "Welcome back, Alice! You have successfully logged in.");

        // When dashboard is loaded, popup is closed (not shown on dashboard)
        $dashboardResponse = $this->actingAs($user)->get('/dashboard');
        $dashboardResponse->assertDontSee('login-popup-heading');
    }

    public function test_login_api_returns_json_for_simple_popup_first_flow(): void
    {
        $student = User::factory()->create(['role' => 'student', 'first_name' => 'Alice', 'is_active' => true, 'email_verified_at' => now()]);

        $response = $this->postJson('/login', [
            'identifier' => $student->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'role' => 'student',
            'first_name' => 'Alice',
        ]);
        $this->assertStringContainsString('dashboard', $response->json('redirect'));
    }

    public function test_counselor_can_authenticate_with_counselor_notification(): void
    {
        $counselor = User::factory()->create(['role' => 'guidance_counselor', 'first_name' => 'Jane', 'is_active' => true, 'email_verified_at' => now()]);

        $response = $this->postJson('/login', [
            'identifier' => $counselor->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'role' => 'guidance_counselor',
            'first_name' => 'Jane',
        ]);
    }

    public function test_system_admin_can_authenticate_with_admin_notification(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin', 'first_name' => 'Root', 'is_active' => true, 'email_verified_at' => now()]);

        $response = $this->postJson('/login', [
            'identifier' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'role' => 'system_admin',
            'first_name' => 'Root',
        ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->post('/login', [
            'identifier' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_student_can_logout_with_notification(): void
    {
        $user = User::factory()->create(['role' => 'student', 'first_name' => 'Bob', 'is_active' => true]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('success', "Goodbye, Bob! You have been successfully logged out.");
    }

    public function test_counselor_can_logout_with_notification(): void
    {
        $counselor = User::factory()->create(['role' => 'guidance_counselor', 'first_name' => 'Jane', 'is_active' => true]);

        $response = $this->actingAs($counselor)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('success', "Guidance Counselor session ended. Goodbye, Jane!");
    }

    public function test_system_admin_can_logout_with_notification(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin', 'first_name' => 'Root', 'is_active' => true]);

        $response = $this->actingAs($admin)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('success', "System Administrator session ended. Goodbye, Root!");
    }
}
