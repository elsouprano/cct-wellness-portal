<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CategoryYearLevel;
use App\Models\Department;
use App\Models\Program;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffUIRedesignTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'email_verified_at' => now(),
        ]);

        $this->academicYear = AcademicYear::create([
            'label' => '2026-2027',
            'is_current' => true,
        ]);
    }

    public function test_question_bank_renders_official_names_without_code_subtitles(): void
    {
        $dass = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'dass21',
            'display_name' => 'Depression Anxiety Stress Scales (DASS-21)',
            'year_level' => '1st',
            'scale_type' => 'numeric_scale',
            'display_order' => 1,
        ]);

        $headsss = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'rapid_headsss',
            'display_name' => 'Rapid HEADSSS Assessment (RAPID-HEADSSS) (Home, Education/Employment, Activities, Drugs, Sexuality, Suicide/Depression, Safety)',
            'year_level' => '1st',
            'scale_type' => 'multiple_choice',
            'display_order' => 2,
        ]);

        $eat = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'eat',
            'display_name' => 'Eating Assessment Tool (EAT-10)',
            'year_level' => '1st',
            'scale_type' => 'numeric_scale',
            'display_order' => 3,
        ]);

        CategoryYearLevel::create(['question_category_id' => $dass->id, 'year_level' => '1st', 'display_order' => 1]);
        CategoryYearLevel::create(['question_category_id' => $headsss->id, 'year_level' => '1st', 'display_order' => 2]);
        CategoryYearLevel::create(['question_category_id' => $eat->id, 'year_level' => '1st', 'display_order' => 3]);

        $response = $this->actingAs($this->admin)->get(route('question-bank.index', ['year_level' => '1st']));

        $response->assertStatus(200);
        $response->assertSee('Depression Anxiety Stress Scales (DASS-21)');
        $response->assertSee('Rapid HEADSSS Assessment (RAPID-HEADSSS) (Home, Education/Employment, Activities, Drugs, Sexuality, Suicide/Depression, Safety)');
        $response->assertSee('Eating Assessment Tool (EAT-10)');

        // Ensure monospace subtitle line is NOT rendered under category names
        $response->assertDontSee('<div class="text-[11px] text-foreground/50 font-mono font-normal">', false);
    }

    public function test_flag_settings_renders_enlarged_comfortable_typography_and_clean_defaults(): void
    {
        $response = $this->actingAs($this->admin)->get(route('flag-settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Unusually Fast Response Detection');
        $response->assertSee('Straightlining Detection');
        $response->assertSee('Contradictory Answer Detection');

        // Font scale classes
        $response->assertSee('text-lg');
        $response->assertSee('text-[15px]');
        $response->assertSee('text-2xl font-semibold text-primary');

        // Clean default labels without repeated units
        $response->assertSee('Default: 1.5 seconds per question');
        $response->assertSee('Default: 90% identical answers');
        $response->assertSee('Default: 75% deviation spread');

        $response->assertDontSee('1.50 seconds per question seconds / question');
        $response->assertDontSee('90% identical answers %');
        $response->assertDontSee('75% deviation spread %');
    }

    public function test_student_profiles_renders_custom_select_widths_and_handles_empty_section(): void
    {
        $dept = Department::create(['name' => 'School of Computer Studies']);
        $program = Program::create([
            'department_id' => $dept->id,
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
        ]);

        $studentWithSection = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'student_id' => '2023-00001',
            'department_id' => $dept->id,
            'program_id' => $program->id,
            'year_level' => '3rd',
            'section' => '3-1',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $studentWithoutSection = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'student_id' => '2023-00002',
            'department_id' => $dept->id,
            'program_id' => $program->id,
            'year_level' => '3rd',
            'section' => '',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('staff.students.index'));

        $response->assertStatus(200);

        // Section rendering
        $response->assertSee('BSIT 3-1');
        $response->assertSee('BSIT');
        $response->assertSee('No section yet');

        // Custom select width classes preventing clipping
        $response->assertSee('w-full sm:w-[170px]', false);
        $response->assertSee('w-full sm:w-[150px]', false);
        $response->assertSee('w-full sm:w-[190px]', false);
        $response->assertSee('w-[76px]', false);
    }
}
