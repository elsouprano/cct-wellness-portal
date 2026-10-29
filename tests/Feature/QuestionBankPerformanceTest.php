<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionBankPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_bank_index_has_bounded_constant_queries(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin', 'is_active' => true]);
        $ay = AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);

        // Create 10 categories
        for ($i = 1; $i <= 10; $i++) {
            QuestionCategory::create([
                'academic_year_id' => $ay->id,
                'year_level' => '1st',
                'name' => "Category {$i}",
                'display_order' => $i,
                'scale_type' => 'numeric_scale',
                'scale_min' => 1,
                'scale_max' => 5,
            ]);
        }

        AssessmentSchedule::create([
            'academic_year_id' => $ay->id,
            'year_level' => '1st',
            'open_date' => now()->subDay()->toDateString(),
            'open_time' => '08:00',
            'close_date' => now()->addDays(5)->toDateString(),
            'close_time' => '17:00',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get('/manage/question-bank');
        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        // For 10 categories, N+1 would have caused > 12 queries.
        // With preloading and bulk updates, total query count is at most 5 queries.
        $this->assertLessThanOrEqual(5, $queryCount, "Query count was {$queryCount}, expected <= 5");
    }
}

