<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();
        $originalFirstName = $user->first_name;
        $originalLastName = $user->last_name;

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'HackedName',
                'last_name' => 'HackedLast',
                'birthdate' => '2000-01-01',
                'contact_number' => '09123456789',
                'address_line1' => '123 Test St',
                'city' => 'Test City',
                'province' => 'Test Province',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        // Identity fields should remain untouched
        $this->assertSame($originalFirstName, $user->first_name);
        $this->assertSame($originalLastName, $user->last_name);

        // Contact and address should be updated
        $this->assertSame('09123456789', $user->contact_number);
        $this->assertSame('123 Test St', $user->address_line1);
        $this->assertSame('Test City', $user->city);
        $this->assertSame('Test Province', $user->province);
    }

    public function test_profile_update_ignores_email_changes(): void
    {
        $user = User::factory()->create();
        $oldEmail = $user->email;

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'birthdate' => '2000-01-01',
                'contact_number' => '09123456789',
                'address_line1' => '123 Test St',
                'city' => 'Test City',
                'province' => 'Test Province',
                'email' => 'hacked@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame($oldEmail, $user->refresh()->email);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
