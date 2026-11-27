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

    public function test_student_with_submissions_cannot_delete_account(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertSessionHasErrorsIn('userDeletion', ['error']);
        $this->assertNotNull($user->fresh());
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

    public function test_profile_contact_number_requires_exact_11_digits_without_letters_or_symbols(): void
    {
        $user = User::factory()->create(['contact_number' => '09123456789']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('minlength="11"', false)
            ->assertSee('maxlength="11"', false)
            ->assertSee('pattern="[0-9]{11}"', false);

        $invalidInputs = [
            '091234567890',  // 12 digits
            '0912345678901', // 13 digits
            '0912345678',    // 10 digits
            '0912345678a',   // alphabet
            '0912-345-6789', // hyphens
            '+63912345678',  // plus symbol
            '0912 345 6789', // spaces
        ];

        foreach ($invalidInputs as $invalid) {
            $response = $this->actingAs($user)->patch('/profile', [
                'contact_number' => $invalid,
                'address_line1' => '123 Test St',
                'city' => 'Tagaytay',
                'province' => 'Cavite',
            ]);

            $response->assertSessionHasErrors('contact_number');
            $this->assertSame('09123456789', $user->fresh()->contact_number);
        }

        // Valid 11-digit numeric string succeeds
        $validResponse = $this->actingAs($user)->patch('/profile', [
            'contact_number' => '09987654321',
            'address_line1' => '123 Test St',
            'city' => 'Tagaytay',
            'province' => 'Cavite',
        ]);

        $validResponse->assertSessionHasNoErrors()->assertRedirect('/profile');
        $this->assertSame('09987654321', $user->fresh()->contact_number);
    }
}
