<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InventorySubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $year = \App\Models\AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);
        
        $cat = \App\Models\QuestionCategory::create([
            'academic_year_id' => $year->id,
            'year_level' => '3rd',
            'name' => 'learning_style',
            'display_order' => 1,
            'scale_type' => 'likert',
        ]);
        
        for ($i = 1; $i <= 14; $i++) {
            \App\Models\QuestionItem::create([
                'question_category_id' => $cat->id,
                'item_number' => $i,
                'question_text' => "Question {$i}",
                'prompt' => 'Rate this',
                'options' => [1, 2, 3, 4],
            ]);
        }
    }

    /**
     * A basic feature test example.
     */
    public function test_cannot_submit_twice_per_academic_year(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);
        
        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/inventory', []);

        $response->assertRedirect('/inventory');
        $response->assertSessionHas('status', 'Already submitted.');
    }

    public function test_partial_submission_fails_validation(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);

        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
        ]);

        // Submit only 1 response
        $response = $this->actingAs($user)->post('/inventory', [
            'responses' => [
                'learning_style' => [1 => 1]
            ],
            'timings' => [
                'learning_style' => [1 => 1500]
            ]
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_db_unique_constraint_on_submission(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);

        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
        ]);
    }

    public function test_validate_section_fails_on_missing_items(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);

        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/inventory/validate-section', [
            'category' => 'learning_style',
            'responses' => [
                'learning_style' => [
                    1 => 1 // missing items 2-14
                ]
            ]
        ]);

        $response->assertStatus(422);
    }

    public function test_validate_section_succeeds_when_all_items_present(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);

        \App\Models\InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
        ]);

        $responses = [];
        for ($i = 1; $i <= 14; $i++) {
            $responses[$i] = 1;
        }

        $response = $this->actingAs($user)->postJson('/inventory/validate-section', [
            'category' => 'learning_style',
            'responses' => [
                'learning_style' => $responses
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_student_with_null_year_level_is_blocked_from_inventory(): void
    {
        $userWithoutYear = \App\Models\User::factory()->create([
            'role' => 'student',
            'year_level' => null,
            'needs_section_confirmation' => false,
        ]);

        $response = $this->actingAs($userWithoutYear)->get('/inventory');
        $response->assertOk();
        $response->assertSee('Your student record does not have an assigned year level');
        $response->assertDontSee('3rd Year');

        $userNeedingConfirmation = \App\Models\User::factory()->create([
            'role' => 'student',
            'year_level' => null,
            'needs_section_confirmation' => true,
        ]);

        $redirectResponse = $this->actingAs($userNeedingConfirmation)->get('/inventory');
        $redirectResponse->assertRedirect(route('student.section-confirmation.show'));

        $postJsonResponse = $this->actingAs($userWithoutYear)->postJson('/inventory/validate-section', [
            'category' => 'learning_style',
            'responses' => ['learning_style' => [1 => 1]]
        ]);
        $postJsonResponse->assertStatus(400);
    }

    public function test_fourth_year_student_inventory_uses_fourth_year_schedule_and_categories(): void
    {
        $year = \App\Models\AcademicYear::where('is_current', true)->first();
        
        $cat4th = \App\Models\QuestionCategory::create([
            'academic_year_id' => $year->id,
            'year_level' => '4th',
            'name' => 'career_readiness',
            'display_order' => 1,
            'scale_type' => 'likert',
        ]);
        
        \App\Models\QuestionItem::create([
            'question_category_id' => $cat4th->id,
            'item_number' => 1,
            'question_text' => 'Question 1',
            'prompt' => 'Rate readiness',
            'options' => [1, 2, 3, 4],
        ]);

        \App\Models\AssessmentSchedule::create([
            'academic_year_id' => $year->id,
            'year_level' => '4th',
            'program_id' => null,
            'open_date' => now()->subDay()->toDateString(),
            'open_time' => '00:00:00',
            'close_date' => now()->addDay()->toDateString(),
            'close_time' => '23:59:59',
        ]);

        $student4th = \App\Models\User::factory()->create([
            'role' => 'student',
            'year_level' => '4th',
            'section' => '4-1',
            'needs_section_confirmation' => false,
        ]);

        $response = $this->actingAs($student4th)->get('/inventory');
        $response->assertOk();
        $response->assertSee('Student Individual Inventory');
        $response->assertSee('4th Year');
        $response->assertDontSee('3rd Year');
        $response->assertSee('Career Readiness');

        $dashResponse = $this->actingAs($student4th)->get('/dashboard');
        $dashResponse->assertOk();
        $dashResponse->assertSee('Your Student Individual Inventory for A.Y.');
        $dashResponse->assertDontSee('3rd Year Individual Inventory');
        $dashResponse->assertDontSee('Your 3rd Individual Inventory');
        $dashResponse->assertDontSee('3rd Year Student Individual Inventory');
    }
}
