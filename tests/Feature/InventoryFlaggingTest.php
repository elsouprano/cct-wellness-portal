<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\InventorySubmission;
use App\Models\InventoryResponse;
use App\Models\InventoryItemTiming;
use App\Models\InventoryFlag;
use App\Models\FlagSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryScoringService;
use App\Services\InventoryFlaggingService;

class InventoryFlaggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic dependencies
        $this->artisan('db:seed', ['--class' => 'FlagSettingSeeder']);

        $this->year = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);
        $this->student = User::factory()->create(['role' => 'student', 'year_level' => '3rd', 'program' => 'BSCS']);

        // Setup test categories and items (simulating DASS21 with 7 items per subscale)
        $this->dass21 = QuestionCategory::create([
            'academic_year_id' => $this->year->id,
            'year_level' => '3rd',
            'name' => 'DASS21',
            'display_order' => 1,
            'scale_type' => 'likert',
        ]);

        $subscales = ['depression', 'anxiety', 'stress'];
        $itemNumber = 1;
        foreach ($subscales as $subscale) {
            for ($i = 1; $i <= 7; $i++) {
                QuestionItem::create([
                    'question_category_id' => $this->dass21->id,
                    'item_number' => $itemNumber++,
                    'question_text' => "DASS21 Question {$itemNumber}",
                    'subscale_tag' => $subscale,
                    'prompt' => 'Rate how you feel.',
                    'options' => [0 => 'Never', 1 => 'Sometimes', 2 => 'Often', 3 => 'Almost Always'],
                ]);
            }
        }
    }

    public function test_identical_answers_trigger_straight_line_but_not_contradiction()
    {
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2025-2026',
            'started_at' => now()->subMinutes(30), // Total 30 mins, won't trigger speed
            'submitted_at' => now(),
            'consent_given_at' => now(),
            'signature_type' => 'typed',
            'signature_data' => 'Test',
        ]);

        // Submit ALL 1s for DASS21 (identical answers = zero spread)
        for ($i = 1; $i <= 21; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'DASS21',
                'item_number' => $i,
                'response_value' => '1',
            ]);
            
            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'DASS21',
                'item_number' => $i,
                'time_spent_ms' => 5000, // 5 secs each, not suspiciously fast
            ]);
        }

        // Run analysis
        $scoringService = new InventoryScoringService();
        $flaggingService = new InventoryFlaggingService();
        
        $scoringService->computeScores($submission);
        $flaggingService->analyze($submission);

        $flags = $submission->flags;
        
        // Assertions
        $this->assertTrue($flags->contains('flag_type', 'straight_line'), 'Missing straight_line flag');
        $this->assertFalse($flags->contains('flag_type', 'contradiction'), 'Contradiction flag triggered erroneously');
        $this->assertFalse($flags->contains('flag_type', 'speed'), 'Speed flag triggered erroneously');
    }

    public function test_fast_submission_triggers_speed_flag()
    {
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2025-2026',
            'started_at' => now()->subSeconds(20), 
            'submitted_at' => now(),
        ]);

        for ($i = 1; $i <= 21; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'DASS21',
                'item_number' => $i,
                'response_value' => rand(0,3),
            ]);
            
            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'DASS21',
                'item_number' => $i,
                'time_spent_ms' => 500, // 0.5 secs each = VERY FAST
            ]);
        }

        $flaggingService = new InventoryFlaggingService();
        $flaggingService->analyze($submission);

        $this->assertTrue($submission->flags->contains('flag_type', 'speed'));
    }

    public function test_contradiction_triggers_on_high_spread()
    {
        $item1 = \App\Models\QuestionItem::where('question_category_id', $this->dass21->id)->where('item_number', 1)->first();
        $item2 = \App\Models\QuestionItem::where('question_category_id', $this->dass21->id)->where('item_number', 2)->first();

        \App\Models\CorrelatedQuestionPair::create([
            'question_category_id' => $this->dass21->id,
            'question_item_id_a' => $item1->id,
            'question_item_id_b' => $item2->id,
            'relationship_type' => 'similar',
            'contradiction_threshold' => 50,
            'notes' => 'Test contradiction rule',
            'created_by' => $this->student->id
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2025-2026',
            'started_at' => now()->subMinutes(30), 
            'submitted_at' => now(),
        ]);

        // Submit completely alternating 0s and 3s within Depression subscale
        // This will create a spread of 3, which is 100% of max range, triggering contradiction.
        for ($i = 1; $i <= 21; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'DASS21',
                'item_number' => $i,
                'response_value' => ($i % 2 == 0) ? '3' : '0',
            ]);
            
            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'DASS21',
                'item_number' => $i,
                'time_spent_ms' => 5000, 
            ]);
        }

        $flaggingService = new InventoryFlaggingService();
        $flaggingService->analyze($submission);

        $flags = $submission->flags;
        
        $this->assertTrue($flags->contains('flag_type', 'contradiction'));
    }
}
