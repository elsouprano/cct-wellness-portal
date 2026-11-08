<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\InventoryFlag;
use App\Models\InventoryScore;
use App\Models\InventorySubmission;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfilesRedesignTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Department $dept;
    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->dept = Department::create([
            'name' => 'School of Computer Studies',
        ]);

        $this->program = Program::create([
            'department_id' => $this->dept->id,
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
        ]);
    }

    public function test_student_profiles_list_paginates_10_by_default_and_supports_custom_per_page(): void
    {
        // Create 15 active students
        User::factory()->count(15)->sequence(fn ($sq) => [
            'email' => "student_{$sq->index}@test.com",
            'student_id' => "2024-" . str_pad($sq->index, 5, '0', STR_PAD_LEFT),
        ])->create([
            'role' => 'student',
            'is_active' => true,
            'program_id' => $this->program->id,
            'section' => '3-1',
        ]);

        // Default: 10 per page
        $response = $this->actingAs($this->admin)->get(route('staff.students.index'));
        $response->assertOk();
        $response->assertSee('Showing 1–10 of 15 students');

        // Custom per_page = 25
        $response25 = $this->actingAs($this->admin)->get(route('staff.students.index', ['per_page' => 25]));
        $response25->assertOk();
        $response25->assertSee('Showing 1–15 of 15 students');

        // Custom per_page = 50
        $response50 = $this->actingAs($this->admin)->get(route('staff.students.index', ['per_page' => 50]));
        $response50->assertOk();
        $response50->assertSee('Showing 1–15 of 15 students');

        // Invalid per_page falls back to default 10
        $responseInvalid = $this->actingAs($this->admin)->get(route('staff.students.index', ['per_page' => 999]));
        $responseInvalid->assertOk();
        $responseInvalid->assertSee('Showing 1–10 of 15 students');
    }

    public function test_student_profiles_list_renders_program_section_and_profile_links_with_filters(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'student_id' => '2023-01042',
            'program_id' => $this->program->id,
            'section' => '3-1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('staff.students.index', [
            'search' => 'Maria',
            'program' => $this->program->id,
            'section' => '3-1',
        ]));

        $response->assertOk();
        $response->assertSee('Santos, Maria');
        $response->assertSee('2023-01042');
        $response->assertSee('BSIT 3-1');
        // Ensure email is not rendered in the table rows
        $response->assertDontSee($student->email);

        // Assert profile view links preserve search & filters
        $response->assertSee('search=Maria');
        $response->assertSee('program=' . $this->program->id);
        $response->assertSee('section=3-1');
    }

    public function test_profile_page_reconstructs_whitelisted_back_url(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'student_id' => '2023-01088',
            'program_id' => $this->program->id,
            'section' => '3-2',
            'is_active' => true,
        ]);

        // Request with whitelisted query parameters + an unwhitelisted/malicious parameter
        $response = $this->actingAs($this->admin)->get(route('staff.students.show', [
            'student' => $student->id,
            'search' => 'Juan',
            'program' => $this->program->id,
            'per_page' => 25,
            'page' => 2,
            'malicious_redirect' => 'https://evil.com',
            'untrusted_param' => 'inject',
        ]));

        $response->assertOk();
        $response->assertSee('Back to Student Profiles');

        // Back link must contain whitelisted params and NEVER untrusted params
        $expectedParamSearch = 'search=Juan';
        $expectedParamProgram = 'program=' . $this->program->id;
        $expectedParamPerPage = 'per_page=25';
        $expectedParamPage = 'page=2';

        $response->assertSee($expectedParamSearch);
        $response->assertSee($expectedParamProgram);
        $response->assertSee($expectedParamPerPage);
        $response->assertSee($expectedParamPage);
        $response->assertDontSee('malicious_redirect');
        $response->assertDontSee('evil.com');
        $response->assertDontSee('untrusted_param');
    }

    public function test_profile_overview_renders_identity_grid_risk_summary_and_sorted_scoring(): void
    {
        $ay = AcademicYear::create([
            'year' => '2026-2027',
            'label' => '2026-2027',
            'is_current' => true,
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Jasmine',
            'last_name' => 'Ilagan',
            'student_id' => '2023-01115',
            'program_id' => $this->program->id,
            'year_level' => '3rd',
            'section' => '3-1',
            'contact_number' => '0917-123-4567',
            'address_line1' => 'Tagaytay City, Cavite',
            'is_paying_student' => false,
            'is_active' => true,
            'year_level_confirmed' => true,
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => $ay->label,
            'year_level' => '3rd',
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
            'quality_classification' => 'FOR REVIEW',
        ]);

        // Add flags: straight-line on dass21, speed flag
        InventoryFlag::create([
            'inventory_submission_id' => $submission->id,
            'flag_type' => 'straight_line',
            'category' => 'dass21',
            'details' => ['identical_count' => 14],
        ]);

        InventoryFlag::create([
            'inventory_submission_id' => $submission->id,
            'flag_type' => 'speed',
            'category' => 'dass21',
            'details' => ['seconds' => 45],
        ]);

        // Add scores: dass21 with severe score, ars30 with moderate score
        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => 'ars30',
            'raw_score' => 24,
            'severity_label' => 'Moderate',
        ]);

        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => 'dass21',
            'subscale_name' => 'depression',
            'raw_score' => 32,
            'severity_label' => 'Extremely Severe',
        ]);

        $response = $this->actingAs($this->admin)->get(route('staff.students.show', $student));
        $response->assertOk();

        // Check Identity Card
        $response->assertSee('School of Computer Studies');
        $response->assertSee('BSIT');
        $response->assertSee('3rd Year');
        $response->assertSee('3-1');
        $response->assertSee('0917-123-4567');
        $response->assertSee('Tagaytay City, Cavite');
        $response->assertSee('No'); // is_paying_student
        $response->assertSee('Confirmed'); // year_level_confirmed

        // Check Risk Summary
        $response->assertSee('Urgent');
        $response->assertSee('Requires Review');
        $response->assertSee('Review Submission');
        $response->assertSee('Straight Line Flags');
        $response->assertSee('Speed Flags');

        // Check Scoring Results: Full official instrument names (no snake_case, no Unknown Category)
        $response->assertSee('Depression Anxiety Stress Scales (DASS-21)');
        $response->assertSee('Academic Resilience Scale (ARS-30)');
        $response->assertSee('Depression'); // Subscale
        $response->assertDontSee('Unknown Category');
        $response->assertSee('Extremely Severe');
        $response->assertSee('Moderate');
    }
}
