<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\InventoryFlag;
use App\Models\InventoryScore;
use App\Models\InventorySubmission;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\User;
use App\Support\InstrumentLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstrumentDisplayAndFlagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        InstrumentLabel::clearCache();
    }

    public function test_inventory_flag_speed_description_is_human_readable(): void
    {
        $flag = new InventoryFlag([
            'flag_type' => 'speed',
            'details' => ['avg_seconds_per_item' => 0.8],
        ]);

        $this->assertEquals('Unusually fast responses', $flag->readable_description);
    }

    public function test_inventory_flag_straight_line_description_includes_full_instrument_name(): void
    {
        $flag = new InventoryFlag([
            'flag_type' => 'straight_line',
            'category' => 'ars30',
        ]);

        $this->assertEquals(
            'Repeated identical answers (straight-lining) in Academic Resilience Scale (ARS-30)',
            $flag->readable_description
        );
    }

    public function test_inventory_flag_straight_line_without_category(): void
    {
        $flag = new InventoryFlag([
            'flag_type' => 'straight_line',
            'category' => null,
        ]);

        $this->assertEquals('Repeated identical answers (straight-lining)', $flag->readable_description);
    }

    public function test_inventory_flag_contradiction_description_includes_questions_and_instrument(): void
    {
        $flag = new InventoryFlag([
            'flag_type' => 'contradiction',
            'category' => 'dass21',
            'details' => [
                'item_a' => ['item_number' => 3],
                'item_b' => ['item_number' => 10],
            ],
        ]);

        $this->assertEquals(
            'Contradictory answers in Depression Anxiety Stress Scales (DASS-21) between Q3 and Q10',
            $flag->readable_description
        );
    }

    public function test_inventory_flag_fallback_has_no_snake_case(): void
    {
        $flag = new InventoryFlag([
            'flag_type' => 'pattern_inconsistency',
            'category' => 'bpaq',
        ]);

        $this->assertEquals(
            'Pattern inconsistency flag on Buss-Perry Aggression Questionnaire (BPAQ)',
            $flag->readable_description
        );
    }

    public function test_staff_student_profile_displays_full_instrument_name_and_no_unknown_category(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin', 'is_active' => true]);
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'year_level' => '1st',
        ]);

        $academicYear = AcademicYear::create([
            'label' => '2025-2026',
            'is_current' => true,
        ]);

        $category = QuestionCategory::create([
            'academic_year_id' => $academicYear->id,
            'year_level' => '1st',
            'name' => 'dass21',
            'display_name' => 'Depression Anxiety Stress Scales (DASS-21)',
            'display_order' => 1,
            'instructions' => 'Test instructions',
            'scale_type' => 'numeric_scale',
            'scale_min' => 0,
            'scale_max' => 3,
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => '2025-2026',
            'year_level' => '1st',
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
        ]);

        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => 'dass21',
            'subscale_name' => 'depression',
            'raw_score' => 15,
            'severity_label' => 'Moderate',
        ]);

        InventoryFlag::create([
            'inventory_submission_id' => $submission->id,
            'flag_type' => 'straight_line',
            'category' => 'dass21',
            'is_reviewed' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('staff.students.show', $student));

        $response->assertOk();
        $response->assertDontSee('Unknown Category');
        $response->assertSee('Depression Anxiety Stress Scales (DASS-21)');
        $response->assertSee('Repeated identical answers (straight-lining) in Depression Anxiety Stress Scales (DASS-21)');
    }

    public function test_question_bank_stores_and_updates_display_name(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin', 'is_active' => true]);
        $academicYear = AcademicYear::create([
            'label' => '2025-2026',
            'is_current' => true,
        ]);

        $payload = [
            'name' => 'new_instrument',
            'display_name' => 'New Official Instrument Scale (NOIS)',
            'year_level' => '1st',
            'display_order' => 1,
            'instructions' => 'Instructions',
            'scale_type' => 'numeric_scale',
            'scale_min' => 0,
            'scale_max' => 4,
            'items' => [
                [
                    'item_number' => 1,
                    'prompt' => 'Item 1 prompt',
                ],
            ],
        ];

        $response = $this->actingAs($admin)->post(route('question-bank.store'), $payload);
        $response->assertRedirect(route('question-bank.index'));

        $this->assertDatabaseHas('question_categories', [
            'name' => 'new_instrument',
            'display_name' => 'New Official Instrument Scale (NOIS)',
        ]);

        $category = QuestionCategory::where('name', 'new_instrument')->first();

        // Update display_name
        $updatePayload = [
            'name' => 'new_instrument',
            'display_name' => 'Updated Official Instrument Scale (UOIS)',
            'year_level' => '1st',
            'display_order' => 1,
            'instructions' => 'Instructions',
            'scale_type' => 'numeric_scale',
            'scale_min' => 0,
            'scale_max' => 4,
            'items' => [
                [
                    'id' => $category->questionItems->first()->id,
                    'item_number' => 1,
                    'prompt' => 'Item 1 prompt updated',
                ],
            ],
        ];

        $updateResponse = $this->actingAs($admin)->put(route('question-bank.update', $category), $updatePayload);
        $updateResponse->assertRedirect(route('question-bank.index'));

        $this->assertDatabaseHas('question_categories', [
            'name' => 'new_instrument',
            'display_name' => 'Updated Official Instrument Scale (UOIS)',
        ]);
    }
}
