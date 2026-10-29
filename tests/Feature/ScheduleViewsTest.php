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
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email_verified_at' => now(),
        ]);
        AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        $response = $this->actingAs($admin)->get('/manage/schedules/create');

        $response->assertStatus(200);
        $response->assertSee('Create Assessment Schedule');
        $response->assertSee('2025-2026');
    }

    public function test_schedules_edit_view_loads_for_admin()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email_verified_at' => now(),
        ]);
        $year = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);
        $schedule = AssessmentSchedule::create([
            'academic_year_id' => $year->id,
            'year_level' => '3rd',
            'open_date' => '2026-08-01',
            'open_time' => '08:00',
            'close_date' => '2026-08-31',
            'close_time' => '17:00',
        ]);

        $response = $this->actingAs($admin)->get("/manage/schedules/{$schedule->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Edit Assessment Schedule');
        $response->assertSee('3rd Year');
    }

    public function test_cannot_create_schedule_with_same_day_time_inversion()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email_verified_at' => now(),
        ]);
        $year = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        $response = $this->actingAs($admin)->post('/manage/schedules', [
            'academic_year_id' => $year->id,
            'year_level' => '3rd',
            'open_date' => '2026-08-15',
            'open_time' => '14:00',
            'close_date' => '2026-08-15',
            'close_time' => '10:00', // Earlier than open time on same day!
        ]);

        $response->assertSessionHasErrors('close_time');
        $this->assertDatabaseMissing('assessment_schedules', [
            'academic_year_id' => $year->id,
            'open_date' => '2026-08-15',
        ]);
    }

    public function test_can_create_valid_assessment_schedule()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email_verified_at' => now(),
        ]);
        $year = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        $response = $this->actingAs($admin)->post('/manage/schedules', [
            'academic_year_id' => $year->id,
            'year_level' => '3rd',
            'open_date' => '2026-08-15',
            'open_time' => '08:00',
            'close_date' => '2026-08-15',
            'close_time' => '17:00',
        ]);

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('assessment_schedules', [
            'academic_year_id' => $year->id,
            'year_level' => '3rd',
            'open_date' => '2026-08-15',
            'open_time' => '08:00',
            'close_date' => '2026-08-15',
            'close_time' => '17:00',
        ]);
    }

    public function test_schedules_index_view_loads_for_admin_with_pagination()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email_verified_at' => now(),
        ]);
        $year = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        for ($i = 1; $i <= 20; $i++) {
            AssessmentSchedule::create([
                'academic_year_id' => $year->id,
                'year_level' => '1st',
                'open_date' => '2026-08-01',
                'open_time' => '08:00',
                'close_date' => '2026-08-31',
                'close_time' => '17:00',
            ]);
        }

        $response = $this->actingAs($admin)->get('/manage/schedules');
        $response->assertStatus(200);
        $response->assertSee('Assessment Schedules');
        $schedules = $response->viewData('schedules');
        $this->assertEquals(15, $schedules->count());
        $this->assertEquals(20, $schedules->total());
        $this->assertEquals(2, $schedules->lastPage());
    }
}
