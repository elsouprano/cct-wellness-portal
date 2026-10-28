<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\QuestionCategory;
use App\Models\User;
use Database\Seeders\FirstYearQuestionBankSeeder;
use Database\Seeders\UpperYearsQuestionBankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstYearQuestionBankTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FirstYearQuestionBankSeeder::class);
        $this->seed(UpperYearsQuestionBankSeeder::class);
    }

    public function test_first_year_question_bank_categories_are_seeded_and_displayed()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $currentYear = AcademicYear::where('is_current', true)->first();
        $this->assertNotNull($currentYear);

        // Verify all 13 categories exist for 1st Year under current AY
        $firstYearCategories = QuestionCategory::where('academic_year_id', $currentYear->id)
            ->where('year_level', '1st')
            ->get();

        $this->assertCount(13, $firstYearCategories);

        $expectedCategories = [
            'rapid_headsss',
            'profile',
            'dass21',
            'sdq',
            'eat',
            'sfs',
            'sfq',
            'audit',
            'btq',
            'learning_style',
            'bpaq',
            'ffmq',
            'caq',
        ];

        foreach ($expectedCategories as $catName) {
            $cat = $firstYearCategories->firstWhere('name', $catName);
            $this->assertNotNull($cat, "Category {$catName} was not found in 1st Year categories");
            $this->assertGreaterThan(0, $cat->questionItems()->count(), "Category {$catName} has 0 items");
        }

        // Verify admin can access question bank index and see categories
        $response = $this->actingAs($admin)->get(route('question-bank.index'));
        $response->assertStatus(200);
        $response->assertSee('rapid_headsss');
        $response->assertSee('dass21');
        $response->assertSee('sdq');
    }

    public function test_upper_years_question_bank_categories_are_seeded_for_2nd_3rd_and_4th_year()
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        $this->assertNotNull($currentYear);

        $expectedCategories = [
            'rapid_headsss',
            'profile',
            'ars30',
            'caq',
            'erq',
            'learning_style',
            'career_interest',
            'career_readiness',
            'skills_inventory',
            'work_values',
            'irq',
            'big_five',
            'dass21',
            'cat',
            'bpaq',
            'ffmq',
        ];

        foreach (['2nd', '3rd', '4th'] as $yl) {
            $categories = QuestionCategory::where('academic_year_id', $currentYear->id)
                ->where('year_level', $yl)
                ->get();

            $this->assertCount(16, $categories, "Year level {$yl} should have 16 categories");

            foreach ($expectedCategories as $catName) {
                $cat = $categories->firstWhere('name', $catName);
                $this->assertNotNull($cat, "Category {$catName} was not found in {$yl} Year categories");
                $this->assertGreaterThan(0, $cat->questionItems()->count(), "Category {$catName} in {$yl} Year has 0 items");
            }
        }
    }

    public function test_ay_2026_2027_also_has_first_year_and_upper_years_categories()
    {
        $ay2026 = AcademicYear::where('label', '2026-2027')->first();
        $this->assertNotNull($ay2026);

        $firstYearCount = QuestionCategory::where('academic_year_id', $ay2026->id)
            ->where('year_level', '1st')
            ->count();
        $this->assertEquals(13, $firstYearCount);

        foreach (['2nd', '3rd', '4th'] as $yl) {
            $count = QuestionCategory::where('academic_year_id', $ay2026->id)
                ->where('year_level', $yl)
                ->count();
            $this->assertEquals(16, $count, "Year level {$yl} in AY 2026-2027 should have 16 categories");
        }
    }
}
