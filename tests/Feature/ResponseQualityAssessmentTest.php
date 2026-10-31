<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\CorrelatedQuestionPair;
use App\Models\InventorySubmission;
use App\Models\InventoryResponse;
use App\Models\InventoryItemTiming;
use App\Models\ResponseQualityAssessment;
use App\Services\ResponseQualityAssessmentService;

class ResponseQualityAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected $year;
    protected $student;
    protected $dass21;
    protected $cat;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'FlagSettingSeeder']);

        $this->year = AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);
        $this->student = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'TeststudentAlpha',
            'role' => 'student',
            'year_level' => '3rd',
            'program' => 'BSCS',
            'section' => '3-1',
        ]);

        // Setup DASS-21 (Numeric scale 0 to 3, 21 items)
        $this->dass21 = QuestionCategory::create([
            'academic_year_id' => $this->year->id,
            'year_level' => '3rd',
            'name' => 'dass21',
            'display_order' => 1,
            'scale_type' => 'numeric_scale',
            'scale_min' => 0,
            'scale_max' => 3,
        ]);

        for ($i = 1; $i <= 21; $i++) {
            QuestionItem::create([
                'question_category_id' => $this->dass21->id,
                'item_number' => $i,
                'prompt' => "DASS21 Question prompt {$i}",
            ]);
        }

        // Setup CAT (Numeric scale 1 to 7, 19 items)
        $this->cat = QuestionCategory::create([
            'academic_year_id' => $this->year->id,
            'year_level' => '3rd',
            'name' => 'cat',
            'display_order' => 2,
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 7,
        ]);

        for ($i = 1; $i <= 19; $i++) {
            QuestionItem::create([
                'question_category_id' => $this->cat->id,
                'item_number' => $i,
                'prompt' => "CAT Question prompt {$i}",
            ]);
        }

        // Correlated Pair on CAT: Item 8 ("Worried college") vs Item 13 ("Liked college") -> inverse
        $item8 = QuestionItem::where('question_category_id', $this->cat->id)->where('item_number', 8)->first();
        $item13 = QuestionItem::where('question_category_id', $this->cat->id)->where('item_number', 13)->first();

        CorrelatedQuestionPair::create([
            'question_category_id' => $this->cat->id,
            'question_item_id_a' => $item8->id,
            'question_item_id_b' => $item13->id,
            'relationship_type' => 'inverse',
            'contradiction_threshold' => 75.0,
            'notes' => 'College outlook inverse pair',
            'created_by' => $this->student->id,
        ]);

        $this->service = app(ResponseQualityAssessmentService::class);
    }

    /**
     * Scenario 5: Respondent with normal completion time and varied answers.
     * Expected: ACCEPTABLE classification.
     */
    public function test_normal_completion_and_varied_answers_is_classified_acceptable()
    {
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subMinutes(15), // 15 mins total
            'submitted_at' => now(),
        ]);

        // Submit varied answers
        for ($i = 1; $i <= 21; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'response_value' => (string) ($i % 4), // 0, 1, 2, 3 distributed
            ]);

            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'time_spent_ms' => 4000, // 4s per item = normal
            ]);
        }

        $assessment = $this->service->assess($submission);

        $this->assertNotNull($assessment);
        $this->assertEquals('ACCEPTABLE', $assessment->classification);
        $this->assertEquals('normal', $assessment->speed_status);
        $this->assertEquals('normal', $assessment->straight_line_status);
        $this->assertEmpty($assessment->flag_reasons);
        $this->assertCount(0, $submission->fresh()->flags);
        $this->assertEquals('ACCEPTABLE', $submission->fresh()->quality_classification);
    }

    /**
     * Scenario 3: Respondent who completes the questionnaire unusually quickly (<1.5s per item).
     * Expected: FOR REVIEW classification, speed indicator triggered.
     */
    public function test_unusually_fast_completion_is_flagged_for_review()
    {
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subSeconds(12), // 12 seconds for 21 questions = ~0.57s/item
            'submitted_at' => now(),
        ]);

        for ($i = 1; $i <= 21; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'response_value' => (string) ($i % 4),
            ]);

            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'time_spent_ms' => 500, // 0.5s per item
            ]);
        }

        $assessment = $this->service->assess($submission);

        $this->assertNotNull($assessment);
        $this->assertEquals('FOR REVIEW', $assessment->classification);
        $this->assertEquals('flagged', $assessment->speed_status);
        $this->assertNotEmpty($assessment->flag_reasons);
        $this->assertStringContainsString('Unusually fast completion', $assessment->flag_reasons[0]);
        $this->assertTrue($submission->fresh()->flags->contains('flag_type', 'speed'));
        $this->assertEquals('FOR REVIEW', $submission->fresh()->quality_classification);
    }

    /**
     * Scenario 2: Respondent who selects identical answers across >=90% of applicable questions.
     * Expected: FOR REVIEW classification, straightlining indicator triggered.
     */
    public function test_excessive_repetitive_answers_triggers_straightlining_flag()
    {
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subMinutes(10), // 10 minutes (not fast)
            'submitted_at' => now(),
        ]);

        // Submit 20 out of 21 identical answers ('2') = 95.2% >= 90% threshold
        for ($i = 1; $i <= 21; $i++) {
            $val = ($i === 21) ? '1' : '2';

            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'response_value' => $val,
            ]);

            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'time_spent_ms' => 3500,
            ]);
        }

        $assessment = $this->service->assess($submission);

        $this->assertNotNull($assessment);
        $this->assertEquals('FOR REVIEW', $assessment->classification);
        $this->assertEquals('flagged', $assessment->straight_line_status);
        $this->assertStringContainsString('Excessive repetitive answers', implode(' ', $assessment->flag_reasons));
        $this->assertTrue($submission->fresh()->flags->contains('flag_type', 'straight_line'));
    }

    /**
     * Scenario 1: Respondent who consistently provides legitimate answers on small batteries (<5 items).
     * Expected: NOT flagged for straightlining because small sample does not establish an excessive repetitive pattern.
     */
    public function test_small_groups_are_not_erroneously_flagged_for_straightlining()
    {
        $smallCat = QuestionCategory::create([
            'academic_year_id' => $this->year->id,
            'year_level' => '3rd',
            'name' => 'short_screener',
            'display_order' => 5,
            'scale_type' => 'numeric_scale',
            'scale_min' => 1,
            'scale_max' => 5,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            QuestionItem::create([
                'question_category_id' => $smallCat->id,
                'item_number' => $i,
                'prompt' => "Short item {$i}",
            ]);
        }

        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
        ]);

        // Student answered '3' on all 3 items (100%), but battery has <5 items
        for ($i = 1; $i <= 3; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'short_screener',
                'item_number' => $i,
                'response_value' => '3',
            ]);

            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'short_screener',
                'item_number' => $i,
                'time_spent_ms' => 4000,
            ]);
        }

        $assessment = $this->service->assess($submission);

        $this->assertNotNull($assessment);
        $this->assertEquals('normal', $assessment->straight_line_status);
        $this->assertFalse($submission->fresh()->flags->contains('flag_type', 'straight_line'));
    }

    /**
     * Scenario 4: Respondent with contradictory answers to predefined related/opposing questions.
     * Expected: FOR REVIEW classification, contradiction indicator with exact item details.
     */
    public function test_contradictory_answers_between_correlated_pair_triggers_flag()
    {
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
        ]);

        // In CAT (1-7 scale): Item 8 is "Worried about college in general" and Item 13 is "Liked college in general".
        // Inverse relationship: High on Item 8 (7) should correspond to Low on Item 13 (1).
        // If student answers 7 on BOTH: invertedA = (7+1)-7 = 1; abs(1 - 7) = 6.0 deviation on 6.0 range (100% spread > 75% threshold).
        InventoryResponse::create([
            'inventory_submission_id' => $submission->id,
            'category' => 'cat',
            'item_number' => 8,
            'response_value' => '7',
        ]);
        InventoryResponse::create([
            'inventory_submission_id' => $submission->id,
            'category' => 'cat',
            'item_number' => 13,
            'response_value' => '7',
        ]);

        // Fill remaining items with varied answers to avoid straightlining
        for ($i = 1; $i <= 19; $i++) {
            if ($i === 8 || $i === 13) continue;
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'cat',
                'item_number' => $i,
                'response_value' => (string) (($i % 6) + 1),
            ]);
            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'cat',
                'item_number' => $i,
                'time_spent_ms' => 3000,
            ]);
        }

        $assessment = $this->service->assess($submission);

        $this->assertNotNull($assessment);
        $this->assertEquals('FOR REVIEW', $assessment->classification);
        $this->assertEquals('flagged', $assessment->contradiction_status);
        $reasonStr = implode(' ', $assessment->flag_reasons);
        $this->assertStringContainsString('Potentially contradictory answers between Q8', $reasonStr);
        $this->assertStringContainsString('Q13', $reasonStr);
        $this->assertTrue($submission->fresh()->flags->contains('flag_type', 'contradiction'));
    }

    /**
     * Scenario 6: Respondent whose timing data is unavailable.
     * Expected: Timing recorded safely as unavailable, NOT flagged for speed.
     */
    public function test_missing_timing_data_is_safely_recorded_without_speed_flag()
    {
        $now = now();
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => $now,
            'submitted_at' => $now, // Exact same timestamp (0 second duration = unavailable duration)
        ]);

        for ($i = 1; $i <= 21; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'response_value' => (string) ($i % 4),
            ]);
            // No item timings recorded
        }

        $assessment = $this->service->assess($submission);

        $this->assertNotNull($assessment);
        $this->assertEquals('unavailable', $assessment->speed_status);
        $this->assertFalse($assessment->hasSpeedFlag());
        $this->assertEquals('ACCEPTABLE', $assessment->classification);
        $this->assertFalse($submission->fresh()->flags->contains('flag_type', 'speed'));
    }

    /**
     * Scenario 7: Respondent with answers that are unusual but not actually contradictory.
     * Expected: ACCEPTABLE classification (logical opposites answered consistently).
     */
    public function test_consistent_opposite_answers_do_not_trigger_contradiction()
    {
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
        ]);

        // Student answered 7 on "Worried about college" and 1 on "Liked college" (perfect inverse consistency)
        InventoryResponse::create([
            'inventory_submission_id' => $submission->id,
            'category' => 'cat',
            'item_number' => 8,
            'response_value' => '7',
        ]);
        InventoryResponse::create([
            'inventory_submission_id' => $submission->id,
            'category' => 'cat',
            'item_number' => 13,
            'response_value' => '1',
        ]);

        for ($i = 1; $i <= 19; $i++) {
            if ($i === 8 || $i === 13) continue;
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'cat',
                'item_number' => $i,
                'response_value' => (string) (($i % 6) + 1),
            ]);
            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'cat',
                'item_number' => $i,
                'time_spent_ms' => 3000,
            ]);
        }

        $assessment = $this->service->assess($submission);

        $this->assertNotNull($assessment);
        $this->assertEquals('normal', $assessment->contradiction_status);
        $this->assertEquals('ACCEPTABLE', $assessment->classification);
        $this->assertFalse($submission->fresh()->flags->contains('flag_type', 'contradiction'));
    }

    /**
     * Scenario 8: Reassessment preserves version history.
     */
    public function test_reassessment_increments_and_preserves_version_history()
    {
        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
        ]);

        for ($i = 1; $i <= 21; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'response_value' => (string) ($i % 4),
            ]);
            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'time_spent_ms' => 3000,
            ]);
        }

        $v1 = $this->service->assess($submission);
        $this->assertEquals(1, $v1->version);

        // Run re-assessment
        $v2 = $this->service->assess($submission, true);
        $this->assertEquals(2, $v2->version);

        // Both versions remain in database
        $this->assertCount(2, $submission->fresh()->qualityAssessments);
        $this->assertEquals(2, $submission->fresh()->qualityAssessment->version);
    }

    /**
     * Scenario 9: Administrative Settings - Reset to Defaults.
     */
    public function test_admin_can_reset_thresholds_to_established_defaults()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        // First modify settings to custom numbers
        $this->actingAs($admin)->put(route('flag-settings.update'), [
            'settings' => [
                1 => 5.0, // speed
                2 => 70.0, // straightline
                3 => 40.0, // contradiction
            ],
        ])->assertRedirect();

        // Reset to established defaults
        $response = $this->actingAs($admin)->post(route('flag-settings.reset'));
        $response->assertRedirect(route('flag-settings.index'));

        // Verify DB values are back to defaults
        $speed = \App\Models\FlagSetting::where('flag_type', 'speed')->value('setting_value');
        $straight = \App\Models\FlagSetting::where('flag_type', 'straight_line')->value('setting_value');
        $contradiction = \App\Models\FlagSetting::where('flag_type', 'contradiction')->value('setting_value');

        $this->assertEquals('1.5', $speed);
        $this->assertEquals('90', $straight);
        $this->assertEquals('75', $contradiction);
    }

    /**
     * Scenario 10: Guidance Review Interface displays metrics, classification badges, and filters.
     */
    public function test_review_interface_displays_quality_summary_and_filtered_submissions()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        // Create 1 acceptable submission and 1 flagged submission
        $sub1 = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
            'quality_classification' => 'ACCEPTABLE',
        ]);
        ResponseQualityAssessment::create([
            'inventory_submission_id' => $sub1->id,
            'classification' => 'ACCEPTABLE',
            'speed_status' => 'normal',
            'straight_line_status' => 'normal',
            'contradiction_status' => 'normal',
            'version' => 1,
            'assessed_at' => now(),
        ]);

        $student2 = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'TeststudentBeta',
            'role' => 'student',
            'year_level' => '3rd',
            'program' => 'BSCS',
        ]);
        $sub2 = InventorySubmission::create([
            'user_id' => $student2->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subSeconds(10),
            'submitted_at' => now(),
            'quality_classification' => 'FOR REVIEW',
        ]);
        ResponseQualityAssessment::create([
            'inventory_submission_id' => $sub2->id,
            'classification' => 'FOR REVIEW',
            'speed_status' => 'flagged',
            'straight_line_status' => 'normal',
            'contradiction_status' => 'normal',
            'flag_reasons' => ['Unusually fast completion: average 0.48s per question.'],
            'version' => 1,
            'assessed_at' => now(),
        ]);

        // View index
        $response = $this->actingAs($admin)->get(route('staff.inventory.index', ['academic_year' => '2026-2027']));
        $response->assertOk();
        $response->assertSee('Assessed Submissions');
        $response->assertSee('Acceptable Quality');
        $response->assertSee('Flagged for Review');
        $response->assertSee('Acceptable');
        $response->assertSee('For Review');

        // Test filter by quality_classification = FOR REVIEW
        $filterResponse = $this->actingAs($admin)->get(route('staff.inventory.index', [
            'academic_year' => '2026-2027',
            'quality_classification' => 'FOR REVIEW',
        ]));
        $filterResponse->assertOk();
        $filterResponse->assertSee($student2->last_name);
        $filterResponse->assertDontSee($this->student->last_name);
    }

    /**
     * Scenario 11: Flagged submission detail view displays specific reasons and original responses.
     */
    public function test_flagged_submission_detail_view_displays_specific_indicators_and_original_responses()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subSeconds(10),
            'submitted_at' => now(),
            'quality_classification' => 'FOR REVIEW',
        ]);

        for ($i = 1; $i <= 21; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'response_value' => '2',
            ]);
        }

        $this->service->assess($submission);

        $response = $this->actingAs($admin)->get(route('staff.inventory.show', $submission));
        $response->assertOk();
        $response->assertSee('Response Quality Assessment');
        $response->assertSee('FOR REVIEW');
        $response->assertSee('Original Student Responses');
        $response->assertSee('DASS21 Question prompt 1');
    }

    /**
     * Scenario 12: Re-assess endpoint re-runs assessment and redirects back.
     */
    public function test_reassess_endpoint_updates_classification_and_assessment_history()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
        ]);

        for ($i = 1; $i <= 21; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'response_value' => (string) ($i % 4),
            ]);
            InventoryItemTiming::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'time_spent_ms' => 4000,
            ]);
        }

        $response = $this->actingAs($admin)->post(route('staff.inventory.reassess', $submission));
        $response->assertRedirect();
        $response->assertSessionHas('status', 'Response quality re-assessment completed.');

        $this->assertEquals('ACCEPTABLE', $submission->fresh()->quality_classification);
        $this->assertNotNull($submission->fresh()->qualityAssessment);
    }

    /**
     * Scenario 13: Student profile view renders questionnaire responses tab and answers.
     */
    public function test_student_profile_view_displays_questionnaire_responses()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        $submission = InventorySubmission::create([
            'user_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
        ]);

        for ($i = 1; $i <= 21; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => 'dass21',
                'item_number' => $i,
                'response_value' => '1',
            ]);
        }

        $this->service->assess($submission);

        $response = $this->actingAs($admin)->get(route('staff.students.show', $this->student));
        $response->assertOk();
        $response->assertSee('Questionnaire Responses');
        $response->assertSee('Student Questionnaire Responses');
        $response->assertSee('DASS21 Question prompt 1');
    }
}
