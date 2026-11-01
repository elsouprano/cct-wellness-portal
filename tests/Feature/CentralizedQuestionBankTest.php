<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CategoryYearLevel;
use App\Models\InventoryResponse;
use App\Models\InventorySubmission;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralizedQuestionBankTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $counselor;
    protected $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'label' => '2026-2027',
            'is_current' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
        ]);

        $this->counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'is_active' => true,
        ]);
    }

    /**
     * Requirement 1: Staff can create a category in the repository.
     */
    public function test_staff_can_create_category_in_repository(): void
    {
        $response = $this->actingAs($this->counselor)->post(route('question-bank.store'), [
            'name' => 'Wellbeing Battery',
            'scale_type' => 'numeric_scale',
            'scale_min' => 0,
            'scale_max' => 4,
            'display_order' => 1,
            'year_level' => '1st',
            'items' => [
                [
                    'item_number' => 1,
                    'prompt' => 'I feel calm and composed.',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('question_categories', [
            'name' => 'Wellbeing Battery',
            'academic_year_id' => $this->academicYear->id,
            'scale_type' => 'numeric_scale',
        ]);
    }

    /**
     * Requirement 2: Staff can add questions under an existing category.
     */
    public function test_staff_can_add_questions_under_existing_category(): void
    {
        $category = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Stress Scale',
            'scale_type' => 'numeric_scale',
            'scale_min' => 0,
            'scale_max' => 3,
            'display_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->post(route('question-bank.items.store', $category), [
            'prompt' => 'I found it hard to wind down.',
            'item_number' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('question_items', [
            'question_category_id' => $category->id,
            'item_number' => 1,
            'prompt' => 'I found it hard to wind down.',
        ]);
    }

    /**
     * Requirement 3: Existing categories can be assigned to multiple year levels.
     */
    public function test_existing_category_can_be_assigned_to_multiple_year_levels(): void
    {
        $category = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Core Mental Health',
            'scale_type' => 'numeric_scale',
            'scale_min' => 0,
            'scale_max' => 3,
            'display_order' => 1,
        ]);

        // Assign to 1st Year
        $this->actingAs($this->counselor)->post(route('question-bank.assign-year-level'), [
            'category_id' => $category->id,
            'year_level' => '1st',
        ]);

        // Assign to 3rd Year
        $this->actingAs($this->counselor)->post(route('question-bank.assign-year-level'), [
            'category_id' => $category->id,
            'year_level' => '3rd',
        ]);

        $fresh = $category->fresh();
        $this->assertTrue($fresh->isAssignedToYearLevel('1st'));
        $this->assertTrue($fresh->isAssignedToYearLevel('3rd'));
        $this->assertFalse($fresh->isAssignedToYearLevel('4th'));

        // Querying for 1st or 3rd year finds the exact same category
        $cats1st = QuestionCategory::forYearLevel('1st', $this->academicYear->id)->pluck('name')->all();
        $cats3rd = QuestionCategory::forYearLevel('3rd', $this->academicYear->id)->pluck('name')->all();

        $this->assertContains('Core Mental Health', $cats1st);
        $this->assertContains('Core Mental Health', $cats3rd);
    }

    /**
     * Requirement 4: Removing a category from one year level does not delete it from repository.
     */
    public function test_removing_category_from_year_level_does_not_delete_from_repository(): void
    {
        $category = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Adaptability Assessment',
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 5,
            'display_order' => 1,
        ]);

        QuestionItem::create([
            'question_category_id' => $category->id,
            'item_number' => 1,
            'prompt' => 'I adjust easily to change.',
        ]);

        $assignment1 = CategoryYearLevel::create([
            'question_category_id' => $category->id,
            'academic_year_id' => $this->academicYear->id,
            'year_level' => '1st',
        ]);

        $assignment2 = CategoryYearLevel::create([
            'question_category_id' => $category->id,
            'academic_year_id' => $this->academicYear->id,
            'year_level' => '2nd',
        ]);

        // Remove from 1st Year
        $response = $this->actingAs($this->counselor)->delete(route('question-bank.remove-year-level', $assignment1));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assignment for 1st Year is removed
        $this->assertDatabaseMissing('category_year_levels', ['id' => $assignment1->id]);

        // Category and questions STILL exist in repository!
        $this->assertDatabaseHas('question_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('question_items', [
            'question_category_id' => $category->id,
            'prompt' => 'I adjust easily to change.',
        ]);

        // 2nd Year assignment remains intact
        $this->assertDatabaseHas('category_year_levels', ['id' => $assignment2->id]);
    }

    /**
     * Requirement 5: The same category cannot be assigned twice to the same year level.
     */
    public function test_cannot_assign_same_category_twice_to_same_year_level(): void
    {
        $category = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Self-Esteem Inventory',
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 4,
            'display_order' => 1,
        ]);

        CategoryYearLevel::create([
            'question_category_id' => $category->id,
            'academic_year_id' => $this->academicYear->id,
            'year_level' => '2nd',
        ]);

        // Try assigning to 2nd Year again
        $response = $this->actingAs($this->counselor)->post(route('question-bank.assign-year-level'), [
            'category_id' => $category->id,
            'year_level' => '2nd',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, CategoryYearLevel::where('question_category_id', $category->id)->where('year_level', '2nd')->count());
    }

    /**
     * Requirement 6: Duplicate categories and duplicate questions are properly handled.
     */
    public function test_duplicate_category_name_is_rejected_with_warning(): void
    {
        QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Life Satisfaction',
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 5,
            'display_order' => 1,
        ]);

        // Try creating duplicate with same name (case-insensitive)
        $response = $this->actingAs($this->counselor)->post(route('question-bank.store'), [
            'name' => 'life satisfaction',
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 5,
            'display_order' => 2,
            'year_level' => '1st',
            'items' => [
                ['item_number' => 1, 'prompt' => 'New question prompt'],
            ],
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_duplicate_question_prompts_within_same_category_are_prevented(): void
    {
        // 1. In batch store/update
        $response = $this->actingAs($this->counselor)->post(route('question-bank.store'), [
            'name' => 'Focus Battery',
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 5,
            'display_order' => 1,
            'year_level' => '1st',
            'items' => [
                ['item_number' => 1, 'prompt' => 'I find it easy to concentrate.'],
                ['item_number' => 2, 'prompt' => '  I find it easy to concentrate.  '], // Duplicate prompt
            ],
        ]);

        $response->assertSessionHasErrors('items.1.prompt');

        // 2. In single item add
        $category = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Study Skills',
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 5,
            'display_order' => 1,
        ]);

        QuestionItem::create([
            'question_category_id' => $category->id,
            'item_number' => 1,
            'prompt' => 'I review my lecture notes daily.',
        ]);

        $singleItemResponse = $this->actingAs($this->counselor)->post(route('question-bank.items.store', $category), [
            'prompt' => 'i review my lecture notes daily.',
        ]);

        $singleItemResponse->assertRedirect();
        $singleItemResponse->assertSessionHas('error');
        $this->assertEquals(1, $category->questionItems()->count());
    }

    /**
     * Requirement 7: Historical submission protection - Category with responses cannot be deleted.
     */
    public function test_category_with_submitted_responses_cannot_be_deleted_from_repository(): void
    {
        $category = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Historical Assessment',
            'scale_type' => 'numeric_scale',
            'scale_min' => 0,
            'scale_max' => 3,
            'display_order' => 1,
        ]);

        QuestionItem::create([
            'question_category_id' => $category->id,
            'item_number' => 1,
            'prompt' => 'I felt close to panic.',
        ]);

        $student = User::factory()->create(['role' => 'student', 'year_level' => '3rd']);

        $submission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
        ]);

        InventoryResponse::create([
            'inventory_submission_id' => $submission->id,
            'category' => 'Historical Assessment',
            'item_number' => 1,
            'response_value' => '2',
        ]);

        // Attempting to delete category is prevented
        $response = $this->actingAs($this->admin)->delete(route('question-bank.destroy', $category));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Category remains safe in database
        $this->assertDatabaseHas('question_categories', ['id' => $category->id]);
    }
}

