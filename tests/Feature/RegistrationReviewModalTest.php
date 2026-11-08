<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationReviewModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_view_renders_script_with_valid_csp_nonce(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);

        // Verify CSP header and script nonce attribute
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/<script nonce="[A-Za-z0-9]{16,64}">/', $content);
        $this->assertStringContainsString('function registrationWizard()', $content);
    }

    public function test_registration_view_renders_step5_as_single_comprehensive_review_screen(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);

        // Step 5 title & instruction banner
        $response->assertSee('Confirm Your Details');
        $response->assertSee('Please review and verify your information carefully before submitting.');

        // Review cards
        $response->assertSee('Account Credentials');
        $response->assertSee('Personal Details');
        $response->assertSee('Academic Information');
        $response->assertSee('Home Address');
        $response->assertSee('1st Year');

        // Section edit navigation buttons
        $response->assertSee('goToStep(1)', false);
        $response->assertSee('goToStep(2)', false);
        $response->assertSee('goToStep(3)', false);
        $response->assertSee('goToStep(4)', false);

        // Submit button
        $response->assertSee('Submit');
    }

    public function test_registration_view_does_not_contain_duplicate_review_modal(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);

        // Modal and helper methods must not be present
        $response->assertDontSee('id="review-modal-panel"', false);
        $response->assertDontSee('id="review-fields-grid"', false);
        $response->assertDontSee('openReviewModal', false);
        $response->assertDontSee('closeReviewModal', false);
    }

    public function test_registration_view_masks_or_omits_password_in_review_step(): void
    {
        $response = $this->get('/register');

        $content = $response->getContent();
        $step5Start = strpos($content, 'STEP 5: CONFIRM YOUR DETAILS');
        $this->assertNotFalse($step5Start);
        $step5Content = substr($content, $step5Start);

        $this->assertStringNotContainsString('formData.password', $step5Content);
        $this->assertStringNotContainsString('password_confirmation', $step5Content);
    }

    public function test_full_registration_succeeds_with_review_workflow(): void
    {
        $departmentId = DB::table('departments')->insertGetId(['name' => 'College of Arts and Sciences']);
        $programId = DB::table('programs')->insertGetId([
            'department_id' => $departmentId,
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
        ]);

        $payload = [
            'first_name' => 'Maria',
            'middle_initial' => 'A',
            'last_name' => 'Santos',
            'birthdate' => '2004-05-15',
            'department' => $departmentId,
            'program_id' => $programId,
            'year_level' => '1st',
            'section' => '1-A',
            'contact_number' => '09171234567',
            'address_line1' => 'Blk 1 Lot 2 Sunshine Homes',
            'city' => 'Tagaytay City',
            'province' => 'Cavite',
            'student_id' => '2026-0042',
            'email' => 'maria.santos@citycollegeoftagaytay.edu.ph',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $response = $this->post('/register', $payload);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'maria.santos@citycollegeoftagaytay.edu.ph')->first();
        $this->assertNotNull($user);
        $this->assertSame('Maria', $user->first_name);
        $this->assertSame('Santos', $user->last_name);
        $this->assertSame('1st', $user->year_level);
        $this->assertSame('student', $user->role);
    }
}
