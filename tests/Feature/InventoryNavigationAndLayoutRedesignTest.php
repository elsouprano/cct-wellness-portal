<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CategoryYearLevel;
use App\Models\Department;
use App\Models\InventorySubmission;
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

        \App\Models\AssessmentSchedule::create([
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

    public function test_inventory_page_renders_desktop_sticky_rail_and_mobile_sheet(): void
    {
        $response = $this->actingAs($this->student)->get('/inventory');

        $response->assertOk();

        // Desktop Rail present inside unified container
        $response->assertSee('aside aria-label="Assessment Sections"', false);
        $response->assertSee('w-72 xl:w-80', false);
        $response->assertSee('border-r border-border', false);
        $response->assertSee('data-rail-step="0"', false);
        $response->assertSee('data-rail-step="1"', false);
        $response->assertSee('data-rail-step="2"', false);

        // Section numbering 1 to 3 in order (Consent is 1, categories are 2 and 3)
        $response->assertSee('Section 1 of 3');
        $response->assertSee('Section 2 of 3');
        $response->assertSee('Section 3 of 3');
        $response->assertDontSee('Step 0 of');

        // Mobile compact bar and dropdown sheet present
        $response->assertSee('id="mobile-sections-sheet"', false);
        $response->assertSee('View Sections');
        $response->assertSee('sections completed');
        $response->assertSee('data-mobile-step="0"', false);
        $response->assertSee('data-mobile-step="1"', false);

        // Official instrument full display names and split acronym sub-labels rendered
        $response->assertSee('Depression Anxiety Stress Scales (DASS-21)');
        $response->assertSee('Rapid HEADSSS Assessment (RAPID-HEADSSS)');
        $response->assertSee('DASS-21');
        $response->assertSee('RAPID-HEADSSS');
        $response->assertSee('Consent');

        // Section status labels and icons (Current, ✓ Completed, Upcoming, Locked)
        $response->assertSee('✓ Completed');
        $response->assertSee('Locked');
        $response->assertSee('Save &amp; Continue', false);
        $response->assertSee('Complete Inventory');

        // Consent document flows naturally without fixed-height nested scroll container
        $response->assertDontSee('h-64 overflow-y-auto');

        // Touch target sizing & focus styling
        $response->assertSee('min-h-[44px]', false);
        $response->assertSee('focus-within:ring-2 focus-within:ring-primary', false);

        // Timings and signature fields
        $response->assertSee('name="signature_type"', false);
        $response->assertSee('name="timings[dass21][1]"', false);
    }

    public function test_inventory_rail_disables_future_unvisited_steps(): void
    {
        $response = $this->actingAs($this->student)->get('/inventory');

        $response->assertOk();

        // Steps beyond maxStep are disabled
        $response->assertSee(':disabled="maxStep < 1"', false);
        $response->assertSee(':disabled="maxStep < 2"', false);
        $response->assertSee('cursor-not-allowed', false);
    }

    public function test_inventory_page_numbers_sections_1_to_16_in_order_for_upper_years(): void
    {
        $fourthYearStudent = User::factory()->create([
            'role' => 'student',
            'year_level' => '4th',
            'is_active' => true,
            'email_verified_at' => now(),
            'needs_section_confirmation' => false,
        ]);

        // Create 15 categories for 4th year to make 16 sections total with Consent
        for ($i = 1; $i <= 15; $i++) {
            $cat = QuestionCategory::create([
                'academic_year_id' => $this->academicYear->id,
                'name' => "cat_upper_{$i}",
                'display_name' => "Upper Section {$i}",
                'year_level' => '4th',
                'scale_type' => 'likert',
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

        \App\Models\AssessmentSchedule::create([
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

        // Consent is Section 1 of 16
        $response->assertSee('Section 1 of 16 &middot; Data Privacy Consent', false);
        $response->assertDontSee('Step 0 of 16');

        // Categories are numbered 2 through 16
        $response->assertSee('Section 2 of 16', false);
        $response->assertSee('Section 16 of 16', false);

        // Unified connected card container
        $response->assertSee('rounded-3xl border border-border shadow-xs overflow-hidden flex flex-col lg:flex-row', false);
        $response->assertSee('border-r border-border bg-white h-full overflow-hidden', false);
    }
}
