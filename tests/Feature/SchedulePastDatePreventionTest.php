<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulePastDatePreventionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $academicYear;
    private Program $program;

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

        $dept = Department::create(['name' => 'School of Computer Studies']);
        $this->program = Program::create([
            'department_id' => $dept->id,
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
        ]);
    }

    public function test_cannot_create_schedule_with_open_date_in_the_past(): void
    {
        $pastDate = now()->subDays(2)->format('Y-m-d');
        $futureClose = now()->addDays(5)->format('Y-m-d');

        $response = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => $pastDate,
            'open_time' => '08:00',
            'close_date' => $futureClose,
            'close_time' => '17:00',
        ]);

        $response->assertSessionHasErrors(['open_date' => 'The open date and time cannot be in the past.']);
        $this->assertDatabaseMissing('assessment_schedules', [
            'program_id' => $this->program->id,
            'open_date' => $pastDate,
        ]);
    }

    public function test_cannot_create_schedule_with_open_time_in_the_past_today(): void
    {
        // Use a time definitely before now()
        $now = now();
        if ($now->hour < 2) {
            $this->markTestSkipped('Cannot test past open time if hour is 00 or 01');
        }

        $pastTime = $now->copy()->subHours(2)->format('H:i');
        $today = $now->format('Y-m-d');
        $futureClose = $now->copy()->addDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => $today,
            'open_time' => $pastTime,
            'close_date' => $futureClose,
            'close_time' => '17:00',
        ]);

        $response->assertSessionHasErrors(['open_date' => 'The open date and time cannot be in the past.']);
    }

    public function test_cannot_create_schedule_with_close_datetime_before_open_datetime(): void
    {
        $openDate = now()->addDays(5)->format('Y-m-d');
        $closeDate = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => $openDate,
            'open_time' => '08:00',
            'close_date' => $closeDate,
            'close_time' => '17:00',
        ]);

        $response->assertSessionHasErrors('close_date');
    }

    public function test_can_create_valid_future_schedule(): void
    {
        $openDate = now()->addDays(1)->format('Y-m-d');
        $closeDate = now()->addDays(5)->format('Y-m-d');

        $response = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => $openDate,
            'open_time' => '08:00',
            'close_date' => $closeDate,
            'close_time' => '17:00',
        ]);

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('assessment_schedules', [
            'program_id' => $this->program->id,
            'open_date' => $openDate,
            'close_date' => $closeDate,
        ]);
    }

    public function test_update_allows_keeping_past_open_date_when_unchanged(): void
    {
        // Existing active schedule opened yesterday
        $pastOpenDate = now()->subDays(2)->format('Y-m-d');
        $currentClose = now()->addDays(2)->format('Y-m-d');
        $newClose = now()->addDays(7)->format('Y-m-d');

        $schedule = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => $pastOpenDate,
            'open_time' => '08:00',
            'close_date' => $currentClose,
            'close_time' => '17:00',
        ]);

        // Admin extends close date without touching open date
        $response = $this->actingAs($this->admin)->put(route('schedules.update', $schedule->id), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => $pastOpenDate,
            'open_time' => '08:00',
            'close_date' => $newClose,
            'close_time' => '17:00',
        ]);

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('assessment_schedules', [
            'id' => $schedule->id,
            'close_date' => $newClose,
        ]);
    }

    public function test_update_rejects_modifying_open_date_to_a_new_past_date(): void
    {
        $pastOpenDate = now()->subDays(2)->format('Y-m-d');
        $differentPastOpenDate = now()->subDays(3)->format('Y-m-d');
        $futureClose = now()->addDays(7)->format('Y-m-d');

        $schedule = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => $pastOpenDate,
            'open_time' => '08:00',
            'close_date' => $futureClose,
            'close_time' => '17:00',
        ]);

        // Attempt to change open date to another past date
        $response = $this->actingAs($this->admin)->put(route('schedules.update', $schedule->id), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => $differentPastOpenDate,
            'open_time' => '08:00',
            'close_date' => $futureClose,
            'close_time' => '17:00',
        ]);

        $response->assertSessionHasErrors(['open_date' => 'The open date and time cannot be in the past.']);
    }
}
