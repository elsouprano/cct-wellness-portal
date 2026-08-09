<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;

class ScheduleViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedules_create_view_loads_for_admin()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        $response = $this->actingAs($admin)->get('/manage/schedules/create');

        $response->assertStatus(200);
        $response->assertSee('Create Assessment Schedule');
        $response->assertSee('2025-2026');
    }

    public function test_schedules_edit_view_loads_for_admin()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $year = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);
        $schedule = AssessmentSchedule::create([
            'academic_year_id' => $year->id,
            'year_level' => '3rd',
            'open_date' => '2026-08-01',
            'open_time' => '08:00:00',
            'close_date' => '2026-08-31',
            'close_time' => '17:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/manage/schedules/{$schedule->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Edit Assessment Schedule');
        $response->assertSee('3rd Year');
    }
}
