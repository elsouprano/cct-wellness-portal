<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\InventoryResponse;
use App\Models\InventorySubmission;
use App\Models\Program;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryHistoryViewTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $ay;
    private Department $dept;
    private Program $prog;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ay = AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);
        $this->dept = Department::create(['name' => 'School of Computer Studies']);
        $this->prog = Program::create([
            'department_id' => $this->dept->id,
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023011068',
            'email' => 'student@citycollegeoftagaytay.edu.ph',
            'password' => Hash::make('SecretPass123!'),
            'department_id' => $this->dept->id,
            'program_id' => $this->prog->id,
            'year_level' => '4th',
            'section' => '4-1',
            'is_active' => true,
            'email_verified_at' => now(),
            'needs_section_confirmation' => false,
        ]);
    }

    public function test_student_views_history_with_scales_all_choices_highlighted_answers_and_display_order(): void
    {
        // Note: display_order 1 is 'caq' (later alphabetically) and display_order 2 is 'ars30'
        // to verify sections sort by display_order rather than category slug.
        $caq = QuestionCategory::create([
            'academic_year_id' => $this->ay->id,
            'year_level' => '4th',
            'name' => 'caq',
            'display_name' => 'Creative Achievement Questionnaire (CAQ)',
            'display_order' => 1,
            'scale_type' => 'multiple_choice',
            'default_options' => ['No experience', 'School level', 'National level'],
        ]);

        QuestionItem::create([
            'question_category_id' => $caq->id,
            'item_number' => 1,
            'prompt' => 'Indicate your level of achievement in visual arts.',
        ]);

        $ars = QuestionCategory::create([
            'academic_year_id' => $this->ay->id,
            'year_level' => '4th',
            'name' => 'ars30',
            'display_name' => 'Academic Resilience Scale (ARS-30)',
            'display_order' => 2,
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 5,
            'scale_labels' => [
                1 => 'Likely',
                2 => 'Somewhat Likely',
                3 => 'Neutral',
                4 => 'Somewhat Unlikely',
                5 => 'Unlikely',
            ],
        ]);

        QuestionItem::create([
            'question_category_id' => $ars->id,
            'item_number' => 1,
            'prompt' => 'I would not accept the tutors’ feedback.',
        ]);

        $profile = QuestionCategory::create([
            'academic_year_id' => $this->ay->id,
            'year_level' => '4th',
            'name' => 'profile',
            'display_name' => 'Personal Profile',
            'display_order' => 3,
            'scale_type' => 'multiple_choice',
            'allow_multiple' => true,
        ]);

        QuestionItem::create([
            'question_category_id' => $profile->id,
            'item_number' => 1,
            'prompt' => 'Which support resources have you used?',
            'allow_multiple' => true,
            'options' => ['Peer Mentoring', 'Counseling Center', 'Others'],
        ]);

        QuestionItem::create([
            'question_category_id' => $profile->id,
            'item_number' => 2,
            'prompt' => 'Share any additional notes for your counselor.',
            'options' => [],
        ]);

        // Also seed another year level's ARS-30 with conflicting labels to ensure 4th year is preferred.
        $otherYearArs = QuestionCategory::create([
            'academic_year_id' => $this->ay->id,
            'year_level' => '1st',
            'name' => 'ars30',
            'display_name' => 'Wrong Year ARS-30',
            'display_order' => 99,
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 5,
            'scale_labels' => [1 => 'Wrong Label'],
        ]);
        QuestionItem::create([
            'question_category_id' => $otherYearArs->id,
            'item_number' => 1,
            'prompt' => 'Wrong year prompt that should not render',
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        InventoryResponse::create([
            'inventory_submission_id' => $submission->id,
            'category' => 'ars30',
            'item_number' => 1,
            'response_value' => '2',
        ]);
        InventoryResponse::create([
            'inventory_submission_id' => $submission->id,
            'category' => 'caq',
            'item_number' => 1,
            'response_value' => 'School level',
        ]);
        InventoryResponse::create([
            'inventory_submission_id' => $submission->id,
            'category' => 'profile',
            'item_number' => 1,
            'response_value' => 'Peer Mentoring, Others: Study Group',
        ]);
        InventoryResponse::create([
            'inventory_submission_id' => $submission->id,
            'category' => 'profile',
            'item_number' => 2,
            'response_value' => 'Prefer morning check-ins.',
        ]);

        $response = $this->actingAs($this->student)->get(route('inventory.history.show', $submission->id));

        $response->assertOk();
        $html = $response->getContent();

        // Vertical sections rail & accessible tab markup present, no legacy wrong-year text
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('aria-orientation="vertical"', $html);
        $this->assertStringContainsString('hidden="until-found"', $html);
        $this->assertStringNotContainsString('Wrong Year ARS-30', $html);
        $this->assertStringNotContainsString('Wrong year prompt that should not render', $html);

        // Display order respected (CAQ before ARS-30 before Personal Profile)
        $caqPos = strpos($html, 'Creative Achievement Questionnaire (CAQ)');
        $arsPos = strpos($html, 'Academic Resilience Scale (ARS-30)');
        $profilePos = strpos($html, 'Personal Profile');
        $this->assertNotFalse($caqPos);
        $this->assertNotFalse($arsPos);
        $this->assertNotFalse($profilePos);
        $this->assertLessThan($arsPos, $caqPos);
        $this->assertLessThan($profilePos, $arsPos);

        // Scale legend & all choices rendered alongside chosen answers
        $response->assertSee('Somewhat Likely');
        $response->assertSee('Unlikely');
        $response->assertSee('2 — Somewhat Likely');
        $response->assertSee('No experience');
        $response->assertSee('School level');
        $response->assertSee('National level');
        $response->assertSee('Counseling Center');
        $response->assertSee('Study Group');
        $response->assertSee('Prefer morning check-ins.');
    }

    public function test_promoted_student_falls_back_to_other_year_level_categories_when_viewing_prior_history(): void
    {
        // Student is now 4th year, viewing a historical category that only exists for 1st year.
        $firstYearCat = QuestionCategory::create([
            'academic_year_id' => $this->ay->id,
            'year_level' => '1st',
            'name' => 'legacy_intake',
            'display_name' => 'First Year Intake',
            'display_order' => 1,
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 4,
            'scale_labels' => [1 => 'Never', 2 => 'Sometimes', 3 => 'Often', 4 => 'Always'],
        ]);

        QuestionItem::create([
            'question_category_id' => $firstYearCat->id,
            'item_number' => 1,
            'prompt' => 'How excited are you for your first semester?',
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subDays(10),
            'submitted_at' => now()->subDays(9),
        ]);

        InventoryResponse::create([
            'inventory_submission_id' => $submission->id,
            'category' => 'legacy_intake',
            'item_number' => 1,
            'response_value' => '3',
        ]);

        $response = $this->actingAs($this->student)->get(route('inventory.history.show', $submission->id));

        $response->assertOk();
        $response->assertSee('First Year Intake');
        $response->assertSee('How excited are you for your first semester?');
        $response->assertSee('3 — Often');
    }

    public function test_other_students_and_staff_cannot_view_another_students_history_via_student_route(): void
    {
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $otherStudent = User::factory()->create([
            'role' => 'student',
            'student_id' => '2023019999',
            'email' => 'other@citycollegeoftagaytay.edu.ph',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $staff = User::factory()->create([
            'role' => 'counselor',
            'email' => 'counselor@citycollegeoftagaytay.edu.ph',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($otherStudent)
            ->get(route('inventory.history.show', $submission->id))
            ->assertNotFound();

        $this->actingAs($staff)
            ->get(route('inventory.history.show', $submission->id))
            ->assertForbidden();
    }
}
