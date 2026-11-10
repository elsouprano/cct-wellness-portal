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

        $response->assertSessionHasErrors(['open_date' => 'The opening date and time cannot be in the past.']);
        $this->assertDatabaseMissing('assessment_schedules', [
            'program_id' => $this->program->id,
            'open_date' => $pastDate,
        ]);
    }

    public function test_cannot_create_schedule_with_open_time_in_the_past_today(): void
    {
        $this->travelTo(\Carbon\Carbon::create(2026, 11, 10, 22, 46, 30, 'Asia/Manila'));

        $response = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => '2026-11-10',
            'open_time' => '22:00',
            'close_date' => '2026-11-12',
            'close_time' => '17:00',
        ]);

        $response->assertSessionHasErrors([
            'open_date' => 'The opening date and time cannot be in the past.',
            'open_time' => 'The opening date and time cannot be in the past.',
        ]);

        // Also test 1 minute ago (22:45 when current time is 22:46)
        $responseOneMinAgo = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => '2026-11-10',
            'open_time' => '22:45',
            'close_date' => '2026-11-12',
            'close_time' => '17:00',
        ]);

        $responseOneMinAgo->assertSessionHasErrors([
            'open_time' => 'The opening date and time cannot be in the past.',
        ]);
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

        $response->assertSessionHasErrors([
            'close_date' => 'The closing date and time must be later than the opening date and time.',
        ]);
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

        $response->assertSessionHasErrors(['open_date' => 'The opening date and time cannot be in the past.']);
    }

    public function test_comprehensive_schedule_datetime_validation_matrix(): void
    {
        // Freeze time to November 10, 2026, 10:46:35 PM Asia/Manila
        $this->travelTo(\Carbon\Carbon::create(2026, 11, 10, 22, 46, 35, 'Asia/Manila'));

        // 1. Yesterday (Nov 9, 2026, 10:00 PM) -> Invalid
        $resYesterday = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => '2026-11-09',
            'open_time' => '22:00',
            'close_date' => '2026-11-11',
            'close_time' => '17:00',
        ]);
        $resYesterday->assertSessionHasErrors(['open_date' => 'The opening date and time cannot be in the past.']);

        // 2. Today exactly at current minute (Nov 10, 2026, 10:46 PM) -> Valid
        $resExactNow = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => '2026-11-10',
            'open_time' => '22:46',
            'close_date' => '2026-11-10',
            'close_time' => '23:30',
        ]);
        $resExactNow->assertRedirect(route('schedules.index'));

        // 3. Today with future time & same-day close (Nov 10, 2026 11:00 PM -> Nov 10, 2026 11:45 PM) -> Valid
        $resTodayFuture = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '2nd',
            'open_date' => '2026-11-10',
            'open_time' => '23:00',
            'close_date' => '2026-11-10',
            'close_time' => '23:45',
        ]);
        $resTodayFuture->assertRedirect(route('schedules.index'));

        // 4. Open today 11:00 PM, close tomorrow 12:00 AM (different days, close_time < open_time) -> Valid
        $resCrossMidnight = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '3rd',
            'open_date' => '2026-11-10',
            'open_time' => '23:00',
            'close_date' => '2026-11-11',
            'close_time' => '00:00',
        ]);
        $resCrossMidnight->assertRedirect(route('schedules.index'));

        // 5. Tomorrow (Nov 11, 2026, 12:00 AM) -> Valid
        $resTomorrow = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '4th',
            'open_date' => '2026-11-11',
            'open_time' => '00:00',
            'close_date' => '2026-11-15',
            'close_time' => '17:00',
        ]);
        $resTomorrow->assertRedirect(route('schedules.index'));

        // 6. Different month (Dec 10, 2026) -> Valid
        $resDiffMonth = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => '2026-12-10',
            'open_time' => '08:00',
            'close_date' => '2026-12-20',
            'close_time' => '17:00',
        ]);
        $resDiffMonth->assertRedirect(route('schedules.index'));

        // 7. Different year (Jan 15, 2027) -> Valid
        $resDiffYear = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '2nd',
            'open_date' => '2027-01-15',
            'open_time' => '08:00',
            'close_date' => '2027-01-25',
            'close_time' => '17:00',
        ]);
        $resDiffYear->assertRedirect(route('schedules.index'));

        // 8. Same-day close time equal to open time (Nov 10, 2026 11:00 PM -> Nov 10, 2026 11:00 PM) -> Invalid
        $resEqualClose = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => '2026-11-10',
            'open_time' => '23:00',
            'close_date' => '2026-11-10',
            'close_time' => '23:00',
        ]);
        $resEqualClose->assertSessionHasErrors([
            'close_time' => 'The closing date and time must be later than the opening date and time.',
        ]);

        // 9. Same-day close time before open time (Nov 10, 2026 11:00 PM -> Nov 10, 2026 10:00 PM) -> Invalid
        $resEarlierClose = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => '2026-11-10',
            'open_time' => '23:00',
            'close_date' => '2026-11-10',
            'close_time' => '22:00',
        ]);
        $resEarlierClose->assertSessionHasErrors([
            'close_time' => 'The closing date and time must be later than the opening date and time.',
        ]);

        // 10. Invalid / ambiguous date format (10/11/2026) -> Must show 'Please select a valid date.', NOT 'in the past'
        $resAmbiguousDate = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => '10/11/2026',
            'open_time' => '23:00',
            'close_date' => '2026-11-12',
            'close_time' => '17:00',
        ]);
        $resAmbiguousDate->assertSessionHasErrors([
            'open_date' => 'Please select a valid date.',
        ]);
        $this->assertNotEquals(
            'The opening date and time cannot be in the past.',
            session('errors')->first('open_date')
        );

        // 11. Invalid time format -> Must show 'Please select a valid time.'
        $resInvalidTime = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => '2026-11-11',
            'open_time' => 'invalid-time',
            'close_date' => '2026-11-12',
            'close_time' => '17:00',
        ]);
        $resInvalidTime->assertSessionHasErrors([
            'open_time' => 'Please select a valid time.',
        ]);
    }
}
