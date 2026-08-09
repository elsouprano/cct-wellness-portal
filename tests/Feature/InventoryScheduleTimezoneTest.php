<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\QuestionCategory;
use Illuminate\Support\Facades\Config;

class InventoryScheduleTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_is_open()
    {
        // Mock timezone
        Config::set('app.timezone', 'Asia/Manila');
        date_default_timezone_set('Asia/Manila');

        $student = User::factory()->create(['role' => 'student', 'year_level' => '3rd', 'program' => 'BSCS']);
        $year = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);
        
        QuestionCategory::create(['academic_year_id' => $year->id, 'year_level' => '3rd', 'name' => 'DASS21', 'display_order' => 1, 'scale_type' => 'likert']);

        $now = now(); // 'Asia/Manila' now
        
        // Let's create a schedule that opened 5 minutes ago and closes tomorrow
        AssessmentSchedule::create([
            'academic_year_id' => $year->id,
            'year_level' => '3rd',
            'program' => 'BSCS',
            'open_date' => now()->subMinutes(5)->toDateString(),
            'open_time' => now()->subMinutes(5)->toTimeString(),
            'close_date' => now()->addDays(1)->toDateString(),
            'close_time' => now()->addDays(1)->toTimeString(),
        ]);

        $response = $this->actingAs($student)->get('/inventory');
        $response->assertStatus(200);
        $response->assertSee('DASS21'); // Which means form loaded, it's OPEN
    }
}
