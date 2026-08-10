<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\InventorySubmission;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressiveSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_progressive_save_consent()
    {
        $user = User::factory()->create(['role' => 'student', 'year_level' => '3rd']);
        $academicYear = AcademicYear::create(['is_current' => true, 'label' => '2025-2026']);

        $submission = InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => $academicYear->label,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/inventory/validate-section', [
            'category' => 'consent',
            'consent_checkbox' => true,
            'signature_type' => 'drawn',
            'signature_data' => 'data:image/png;base64,12345',
        ]);

        $response->assertStatus(200);

        $submission->refresh();
        $this->assertNotNull($submission->consent_given_at);
        $this->assertEquals('drawn', $submission->signature_type);
        $this->assertEquals('data:image/png;base64,12345', $submission->signature_data);
    }

    public function test_progressive_save_responses()
    {
        $user = User::factory()->create(['role' => 'student', 'year_level' => '3rd']);
        $academicYear = AcademicYear::create(['is_current' => true, 'label' => '2025-2026']);
        
        $category = QuestionCategory::create([
            'academic_year_id' => $academicYear->id,
            'year_level' => '3rd',
            'name' => 'test_cat',
            'display_order' => 1,
            'scale_type' => 'likert_1_5'
        ]);
        
        $item = QuestionItem::create([
            'question_category_id' => $category->id,
            'item_number' => 1,
            'prompt' => 'Test Prompt'
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $user->id,
            'academic_year' => $academicYear->label,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/inventory/validate-section', [
            'category' => 'test_cat',
            'responses' => [
                'test_cat' => [
                    '1' => '3'
                ]
            ],
            'timings' => [
                'test_cat' => [
                    '1' => '1500'
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('inventory_responses', [
            'inventory_submission_id' => $submission->id,
            'category' => 'test_cat',
            'item_number' => 1,
            'response_value' => '3'
        ]);
        
        $this->assertDatabaseHas('inventory_item_timings', [
            'inventory_submission_id' => $submission->id,
            'category' => 'test_cat',
            'item_number' => 1,
            'time_spent_ms' => 1500
        ]);
    }
}
