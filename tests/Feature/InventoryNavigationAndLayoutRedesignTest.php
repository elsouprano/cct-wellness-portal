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
            'options' => ['With parents', 'With relatives', 'Others', 'N/A'],
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
        $response->assertSee('Please specify', false);
        $response->assertSee('otherTexts', false);
        $response->assertSee('N/A');

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

    public function test_others_specification_and_na_options_validate_and_persist_cleanly(): void
    {
        $this->actingAs($this->student)->get('/inventory')->assertOk();

        // Empty specification after "Others:" is rejected
        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'rapid_headsss',
                'responses' => ['rapid_headsss' => [1 => 'Others:   ']],
                'timings' => ['rapid_headsss' => [1 => 1200]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('responses.rapid_headsss.1');

        // Specified "Others: ..." value is accepted and persisted
        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'rapid_headsss',
                'responses' => ['rapid_headsss' => [1 => 'Others: Living with guardian']],
                'timings' => ['rapid_headsss' => [1 => 1200]],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('inventory_responses', [
            'category' => 'rapid_headsss',
            'item_number' => 1,
            'response_value' => 'Others: Living with guardian',
        ]);

        // "N/A" opt-out option is accepted cleanly
        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'rapid_headsss',
                'responses' => ['rapid_headsss' => [1 => 'N/A']],
                'timings' => ['rapid_headsss' => [1 => 900]],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // Resuming renders pre-filled "Please specify" text & checked state for "Others: Living with guardian"
        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'rapid_headsss',
                'responses' => ['rapid_headsss' => [1 => 'Others: Living with guardian']],
                'timings' => ['rapid_headsss' => [1 => 900]],
            ])
            ->assertOk();

        $resume = $this->actingAs($this->student)->get('/inventory');
        $resume->assertOk();
        $resume->assertSee('Living with guardian');
    }

    public function test_multi_select_checkboxes_validate_and_persist_multiple_answers(): void
    {
        $skillsCategory = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'skills_inventory',
            'display_name' => InstrumentCatalog::get('skills_inventory'),
            'year_level' => '1st',
            'scale_type' => 'multiple_choice_unscored',
            'allow_multiple' => true,
            'display_order' => 3,
        ]);

        CategoryYearLevel::create([
            'question_category_id' => $skillsCategory->id,
            'year_level' => '1st',
            'display_order' => 3,
        ]);

        $manySkills = [
            'Manages self effectively',
            'Sets clear priorities',
            'Identifies strategic direction',
            'Works reliably without supervision',
            'Accepts responsibility',
            'Delegates appropriately',
            'Monitors project progress',
            'Manages meetings and conferences',
            'Identifies problems and solutions',
            'Adapts to new situations',
            'Builds cohesive teams',
            'Works well independently',
            'Motivates teammates',
            'Guides and teaches peers',
            'Demonstrates integrity and values',
        ];

        QuestionItem::create([
            'question_category_id' => $skillsCategory->id,
            'item_number' => 1,
            'prompt' => 'Skills Domain: Administrative Skills',
            'options' => $manySkills,
            'allow_multiple' => true,
        ]);

        $page = $this->actingAs($this->student)->get('/inventory');
        $page->assertOk();
        $page->assertSee('Select all that apply', false);
        $page->assertSee('data-multi-select="true"', false);
        $page->assertSee('type="checkbox"', false);

        // Empty selection must be rejected
        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'skills_inventory',
                'responses' => ['skills_inventory' => [1 => '']],
                'timings' => ['skills_inventory' => [1 => 1100]],
            ])
            ->assertStatus(422);

        // Invalid token must be rejected
        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'skills_inventory',
                'responses' => ['skills_inventory' => [1 => 'Setting goals/priorities, NonExistentSkill']],
                'timings' => ['skills_inventory' => [1 => 1100]],
            ])
            ->assertStatus(422);

        // Selecting all 12 options (>255 chars total) succeeds & persists as comma-separated canonical string
        $joined = implode(', ', $manySkills);
        $this->assertGreaterThan(255, strlen($joined));

        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'skills_inventory',
                'responses' => ['skills_inventory' => [1 => $manySkills]],
                'timings' => ['skills_inventory' => [1 => 1800]],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('inventory_responses', [
            'category' => 'skills_inventory',
            'item_number' => 1,
            'response_value' => $joined,
        ]);
    }

    public function test_student_information_sheet_renders_validates_and_persists_before_inventory(): void
    {
        $response = $this->actingAs($this->student)->get('/inventory');
        $response->assertOk();
        $response->assertSee('Before Inventory Questions', false);
        $response->assertSee('Student Information Sheet', false);
        $response->assertViewHas('sisCompleted', false);

        // Missing required SIS fields returns 422
        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'student_information_sheet',
                'sis' => [
                    'nickname' => '',
                    'phone' => '1234',
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sis.nickname', 'sis.phone', 'sis.family_medical_conditions']);

        // Complete valid SIS submission persists and marks student_information_sheets completed
        $validPayload = [
            'nickname' => 'Juan',
            'sex' => 'Male',
            'place_of_birth' => 'Tagaytay City',
            'birthdate' => '2006-05-12',
            'civil_status' => 'Single',
            'address_line1' => 'Brgy. Maitim II',
            'city' => 'Tagaytay',
            'province' => 'Cavite',
            'citizenship' => 'Filipino',
            'religion' => 'Catholic',
            'course' => 'Bachelor of Science in Information Technology',
            'student_number' => '2026-0123',
            'section' => '1-1',
            'type_of_student' => 'New Student',
            'school_year_admitted' => '2026-2027',
            'phone' => '09171234567',
            'personal_email' => 'juan@example.com',
            'school_email' => 'juan@cct.edu.ph',
            'is_working_student' => 'No',
            'employment_status' => 'N/A',
            'company_name' => 'N/A',
            'company_address' => 'N/A',
            'elementary_school' => 'Tagaytay Central School',
            'elementary_year_graduated' => '2018',
            'secondary_school' => 'Tagaytay City Science NHS',
            'secondary_year_graduated' => '2024',
            'attended_als' => 'No',
            'als_school' => 'N/A',
            'als_year_graduated' => 'N/A',
            'tertiary_school' => 'N/A',
            'tertiary_last_year_attended' => 'N/A',
            'course_choice' => 'First Choice',
            'special_status' => 'Not Applicable',
            'disability_type' => 'Not Applicable',
            'hospitalized_before' => 'No',
            'hospitalization_cause' => 'N/A',
            'family_medical_conditions' => ['None that I know of.'],
            'smoking_frequency' => 'Never',
            'liquor_frequency' => 'Never',
            'coffee_frequency' => 'Seldom',
            'prohibited_drugs_frequency' => 'Never',
            'psych_consultation_before' => 'No',
            'psych_consultation_date' => 'N/A',
            'psych_consultation_reason' => 'N/A',
            'residence_nature' => "Parents' house",
            'residence_ownership' => 'Owned',
            'boarding_house_address' => 'N/A',
            'family_setup' => 'Living with both parents',
            'one_parent_reason' => 'N/A',
            'living_with_relatives_relation' => 'N/A',
            'father_name' => 'Pedro Dela Cruz',
            'father_age' => '48',
            'father_contact' => '09171112222',
            'father_email' => '',
            'father_education' => 'College Graduate',
            'father_occupation' => 'Engineer',
            'mother_name' => 'Maria Dela Cruz',
            'mother_age' => '46',
            'mother_contact' => '09173334444',
            'mother_email' => '',
            'mother_education' => 'College Graduate',
            'mother_occupation' => 'Teacher',
            'emergency_contact_name' => 'Maria Dela Cruz',
            'emergency_relationship' => 'Mother',
            'emergency_contact_number' => '09173334444',
            'emergency_email' => 'maria@example.com',
            'emergency_address' => 'Tagaytay City',
            'siblings_names' => 'Ana Dela Cruz',
            'siblings_ages' => '16',
            'siblings_education_occupation' => 'Grade 10 Student',
            'family_monthly_income' => 'PhP 20,001- 30,000',
            'interests_hobbies_skills' => 'Coding and Reading',
            'awards_recognitions' => 'With Honors',
            'cct_org_member' => 'No',
            'cct_org_name' => 'N/A',
            'strengths_traits' => 'Patient and resourceful',
            'traits_to_change' => 'Procrastination',
            'personal_goals' => 'Graduate with honors',
            'happiness_source' => 'Family',
            'worries_difficulties' => 'Time management',
            'college_success_plan' => 'Study consistently',
            'close_friend_name' => 'Jose Santos',
            'guidance_concerns' => ['Academics', 'Career'],
            'guidance_concerns_explanation' => 'Study habits support',
            'residence_google_maps_link' => '',
        ];

        $this->actingAs($this->student)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'student_information_sheet',
                'sis' => $validPayload,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('student_information_sheets', [
            'user_id' => $this->student->id,
            'academic_year' => $this->academicYear->label,
        ]);

        $originalGender = $this->student->gender;
        $afterSave = $this->actingAs($this->student)->get('/inventory');
        $afterSave->assertOk();
        $afterSave->assertViewHas('sisCompleted', true);
        // Existing non-empty gender lock on User is preserved while blank gender gets backfilled
        $this->assertSame($originalGender, $this->student->fresh()->gender);

        $studentNoGender = User::factory()->create([
            'role' => 'student',
            'year_level' => '1st',
            'gender' => null,
            'is_active' => true,
            'email_verified_at' => now(),
            'needs_section_confirmation' => false,
        ]);
        $this->actingAs($studentNoGender)->get('/inventory')->assertOk();
        $this->actingAs($studentNoGender)
            ->postJson(route('inventory.validate-section'), [
                'category' => 'student_information_sheet',
                'sis' => $validPayload,
            ])
            ->assertOk();
        $this->assertSame('Male', $studentNoGender->fresh()->gender);
    }

    public function test_question_bank_json_matches_google_forms_multi_select_and_na_options(): void
    {
        $firstYear = json_decode(file_get_contents(resource_path('data/inventory-items-1st-year.json')), true, 512, JSON_THROW_ON_ERROR);
        $upperYears = json_decode(file_get_contents(resource_path('data/inventory-items-upper-years.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue((bool) ($upperYears['skills_inventory']['allow_multiple'] ?? false));

        foreach ($firstYear['sdq']['items'] as $item) {
            if ($item['item_number'] >= 31 && $item['item_number'] <= 37) {
                $this->assertContains('N/A', $item['options']);
            }
        }
        foreach ($firstYear['sfs']['items'] as $item) {
            if (in_array((int) $item['item_number'], [87, 88], true)) {
                $this->assertContains('N/A', $item['options']);
            }
        }

        // Student Profile & Demographics must be first before Rapid HEADSSS in both year-level JSONs
        $firstKeys = array_keys($firstYear);
        $upperKeys = array_keys($upperYears);
        $this->assertSame('profile', $firstKeys[0]);
        $this->assertSame('rapid_headsss', $firstKeys[1]);
        $this->assertSame('profile', $upperKeys[0]);
        $this->assertSame('rapid_headsss', $upperKeys[1]);
    }
}
