<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationReviewModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_view_renders_review_modal_structure(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);

        // Accessible dialog attributes
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
        $response->assertSee('aria-labelledby="review-modal-title"', false);
        $response->assertSee('aria-describedby="review-modal-subtitle"', false);

        // Title and Subtitle
        $response->assertSee('Please Review Your Information');
        $response->assertSee('Is the information you entered correct?');

        // Fields grid container
        $response->assertSee('id="review-fields-grid"', false);
        $response->assertSee('id="review-modal-panel"', false);
    }

    public function test_registration_view_renders_restriction_warning_note(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Make sure your name and student details are correct. Some information may not be editable after registration.');
    }

    public function test_registration_view_renders_modal_buttons_with_correct_labels(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);

        // Modal Action buttons
        $response->assertSee('Go Back and Edit');
        $response->assertSee('Yes, Register');

        // Step 5 Submit button exists
        $response->assertSee('Submit');
        $response->assertSee('openReviewModal($event)', false);
    }

    public function test_registration_script_masks_password_and_prevents_xss(): void
    {
        $response = $this->get('/register');

        $content = $response->getContent();

        // Ensure password is masked as bullet points and not rendered raw
        $this->assertStringContainsString("'••••••••'", $content);
        $this->assertStringContainsString('textContent', $content);

        // Ensure review fields logic uses textContent rather than innerHTML injection
        $this->assertStringContainsString('grid.textContent = \'\';', $content);
        $this->assertStringContainsString('valSpan.textContent = displayValue;', $content);
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

