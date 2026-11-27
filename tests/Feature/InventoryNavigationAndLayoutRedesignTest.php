<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\CategoryYearLevel;
use App\Models\Department;
use App\Models\Program;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\User;
use App\Support\InstrumentCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryNavigationAndLayoutRedesignTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private AcademicYear $academicYear;
    private QuestionCategory $dassCategory;
    private QuestionCategory $headsssCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'label' => '2026-2027',
            'is_current' => true,
        ]);

        $dept = Department::create(['name' => 'School of Computer Studies']);
        $program = Program::create([
            'department_id' => $dept->id,
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'year_level' => '1st',
            'department_id' => $dept->id,
            'program_id' => $program->id,
            'section' => '1-1',
            'is_active' => true,
            'email_verified_at' => now(),
            'needs_section_confirmation' => false,
        ]);

        $this->dassCategory = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'dass21',
            'display_name' => InstrumentCatalog::get('dass21'),
            'year_level' => '1st',
            'scale_type' => 'numeric_scale',
            'scale_min' => 0,
            'scale_max' => 3,
            'display_order' => 1,
        ]);

        $this->headsssCategory = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'rapid_headsss',
            'display_name' => InstrumentCatalog::get('rapid_headsss'),
            'year_level' => '1st',
            'scale_type' => 'multiple_choice',
            'display_order' => 2,
        ]);

        CategoryYearLevel::create([
            'question_category_id' => $this->dassCategory->id,
            'year_level' => '1st',
            'display_order' => 1,
        ]);

        CategoryYearLevel::create([
            'question_category_id' => $this->headsssCategory->id,
            'year_level' => '1st',
            'display_order' => 2,
        ]);

        AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'year_level' => '1st',
            'program_id' => null,
            'open_date' => now()->subDay()->toDateString(),
            'open_time' => '00:00:00',
            'close_date' => now()->addMonth()->toDateString(),
            'close_time' => '23:59:59',
        ]);

        QuestionItem::create([
            'question_category_id' => $this->dassCategory->id,
            'item_number' => 1,
            'question_text' => 'I found it hard to wind down.',
            'prompt' => 'I found it hard to wind down.',
        ]);

        QuestionItem::create([
            'question_category_id' => $this->headsssCategory->id,
            'item_number' => 1,
            'question_text' => 'Who do you live with at home?',
            'prompt' => 'Who do you live with at home?',
            'options' => ['With parents', 'With relatives', 'Independent / Dorm'],
        ]);
    }

    public function test_inventory_page_renders_compact_240px_rail_one_question_stage_and_bottom_sheet(): void
    {
        $response = $this->actingAs($this->student)->get('/inventory');

        $response->assertOk();

        // Compact 260px desktop rail (lg:w-[260px]) without text status pills and with 2-line wrapping
        $response->assertSee('aside aria-label="Assessment Sections"', false);
        $response->assertSee('lg:w-[260px]', false);
        $response->assertSee('line-clamp-2', false);
        $response->assertSee('border-r border-border', false);
        $response->assertSee('data-rail-step="0"', false);
        $response->assertSee('data-rail-step="1"', false);
        $response->assertSee('data-rail-step="2"', false);

        // Section numbering 1 to 3 in order (Consent is 1, categories are 2 and 3)
        $response->assertSee('Section 1 of 3');
        $response->assertSee('Section 2 of 3');
        $response->assertSee('Section 3 of 3');
        $response->assertDontSee('Step 0 of');

        // Mobile slim top bar and bottom sheet present
        $response->assertSee('id="mobile-sections-sheet"', false);
        $response->assertSee('Assessment Sections');
        $response->assertSee('sections completed');
        $response->assertSee('data-mobile-step="0"', false);
        $response->assertSee('data-mobile-step="1"', false);

        // Instrument labels & Consent present
        $response->assertSee('Depression Anxiety Stress Scales (DASS-21)');
        $response->assertSee('Rapid HEADSSS Assessment (RAPID-HEADSSS)');
        $response->assertSee('DASS-21');
        $response->assertSee('RAPID-HEADSSS');
        $response->assertSee('Consent');

        // Compact 3-questions-per-page indicators, responsive grid, instructions toggle, and manual Next navigation
        $response->assertSee('Question', false);
        $response->assertSee('data-q-index="0"', false);
        $response->assertSee('pageSize: 3', false);
        $response->assertSee('qIndex <= 0 && 0 < (qIndex + pageSize)', false);
        $response->assertSee('questionRangeLabel(', false);
        $response->assertSee('min-h-[44px]', false);
        $response->assertSee('toggleInstructions(', false);
        $response->assertSee('@change="handleFormChange($event)"', false);
        $response->assertSee('Please choose an answer to continue.');
        $response->assertSee("isCurrentPageAnswered() ? 'btn-primary shadow-sm' : 'btn-secondary'", false);
        $response->assertSee('Next section', false);
        $response->assertSee('Finish');
        $response->assertSee('Validating&hellip;', false);

        // No auto-advance mechanism anywhere in the inventory script or markup
        $response->assertDontSee('autoAdvanceTimer', false);
        $response->assertDontSee('clearAutoAdvance', false);
        $response->assertDontSee('data-auto-advance', false);
        $response->assertDontSee('onSingleChoiceAnswered', false);

        // Touch target sizing & focus styling
        $response->assertSee('min-h-[44px]', false);
        $response->assertSee('focus-within:ring-2 focus-within:ring-primary', false);

        // Timings and signature fields preserved
        $response->assertSee('name="signature_type"', false);
        $response->assertSee('name="timings[dass21][1]"', false);
    }

    public function test_inventory_rail_disables_future_unvisited_steps(): void
    {
        $response = $this->actingAs($this->student)->get('/inventory');

        $response->assertOk();

        $response->assertSee(':disabled="maxStep < 1"', false);
        $response->assertSee(':disabled="maxStep < 2"', false);
        $response->assertSee('cursor-not-allowed', false);
    }

    public function test_inventory_page_numbers_sections_1_to_17_without_clamping_for_16_categories(): void
    {
        $fourthYearStudent = User::factory()->create([
            'role' => 'student',
            'year_level' => '4th',
            'is_active' => true,
            'email_verified_at' => now(),
            'needs_section_confirmation' => false,
        ]);

        // Create 16 categories for 4th year -> 17 total sections including Consent
        for ($i = 1; $i <= 16; $i++) {
            $cat = QuestionCategory::create([
                'academic_year_id' => $this->academicYear->id,
                'name' => "cat_upper_{$i}",
                'display_name' => "Upper Section {$i}",
                'year_level' => '4th',
                'scale_type' => 'numeric_scale',
                'scale_min' => 1,
                'scale_max' => 5,
                'display_order' => $i,
            ]);

            CategoryYearLevel::create([
                'question_category_id' => $cat->id,
                'year_level' => '4th',
                'display_order' => $i,
            ]);

            QuestionItem::create([
                'question_category_id' => $cat->id,
                'item_number' => 1,
                'prompt' => "Question for {$i}",
            ]);
        }

        AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'year_level' => '4th',
            'program_id' => null,
            'open_date' => now()->subDay()->toDateString(),
            'open_time' => '00:00:00',
            'close_date' => now()->addMonth()->toDateString(),
            'close_time' => '23:59:59',
        ]);

        $response = $this->actingAs($fourthYearStudent)->get('/inventory');
        $response->assertOk();

        // Consent is Section 1 of 17, categories are 2 through 17 (never clamped at 16)
        $response->assertSee('Section 1 of 17 &middot; Data Privacy Consent', false);
        $response->assertSee('Section 2 of 17', false);
        $response->assertSee('Section 16 of 17', false);
        $response->assertSee('Section 17 of 17', false);
        $response->assertDontSee('Section 16 of 16');
    }

    public function test_manual_navigation_first_selection_timing_guard_and_required_section_validation(): void
    {
        // Load /inventory to initialize draft submission and inspect inline script contract
        $getResponse = $this->actingAs($this->student)->get('/inventory');
        $getResponse->assertOk();
        $html = $getResponse->getContent();

        // Confirm first-selection timing guard remains intact and radio change does not mutate qIndex
        $this->assertStringContainsString('if (input.value === "" || input.value === "0")', $html);
        $this->assertStringNotContainsString('onSingleChoiceAnswered', $html);

        // Missing answer on section validation returns 422 without saving
        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'dass21',
                'responses' => ['dass21' => []],
                'timings' => ['dass21' => [1 => 1450]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('responses.dass21.1');

        // Valid answer + first-selection timing persists exact time_spent_ms
        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'dass21',
                'responses' => ['dass21' => [1 => 2]],
                'timings' => ['dass21' => [1 => 1450]],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('inventory_item_timings', [
            'category' => 'dass21',
            'item_number' => 1,
            'time_spent_ms' => 1450,
        ]);
    }
}
