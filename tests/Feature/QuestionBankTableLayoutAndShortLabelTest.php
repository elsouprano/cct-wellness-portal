<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CategoryYearLevel;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Support\InstrumentCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBankTableLayoutAndShortLabelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->academicYear = AcademicYear::create([
            'label' => '2026-2027',
            'is_current' => true,
        ]);
    }

    public function test_question_bank_renders_table_fixed_and_shortened_rapid_headsss_label(): void
    {
        $headsss = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'rapid_headsss',
            'display_name' => InstrumentCatalog::get('rapid_headsss'),
            'year_level' => '1st',
            'scale_type' => 'multiple_choice',
            'display_order' => 1,
        ]);

        CategoryYearLevel::create([
            'question_category_id' => $headsss->id,
            'year_level' => '1st',
            'display_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('question-bank.index', ['year_level' => '1st']));

        $response->assertOk();
        $response->assertSee('table-fixed', false);
        $response->assertSee('Rapid HEADSSS Assessment (RAPID-HEADSSS)');
        // Ensure the old long label with adjacent domain parens is NOT used
        $response->assertDontSee('Rapid HEADSSS Assessment (RAPID-HEADSSS) (Home, Education/Employment');
        // Ensure domains are preserved in the tooltip
        $response->assertSee('Domains: Home, Education/Employment, Activities, Drugs, Sexuality, Suicide/Depression, Safety');

        // Check centralized repository view
        $repoResponse = $this->actingAs($this->admin)->get(route('question-bank.index', ['view' => 'repository']));
        $repoResponse->assertOk();
        $repoResponse->assertSee('table-fixed', false);
        $repoResponse->assertSee('Rapid HEADSSS Assessment (RAPID-HEADSSS)');
    }

    public function test_question_bank_edit_view_displays_domain_guidance_for_rapid_headsss(): void
    {
        $headsss = QuestionCategory::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'rapid_headsss',
            'display_name' => InstrumentCatalog::get('rapid_headsss'),
            'year_level' => '1st',
            'scale_type' => 'multiple_choice',
            'display_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('question-bank.edit', $headsss));

        $response->assertOk();
        $response->assertSee('RAPID-HEADSSS domains: Home, Education/Employment, Activities, Drugs, Sexuality, Suicide/Depression, Safety.');
    }
}
