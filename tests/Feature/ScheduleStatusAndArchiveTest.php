<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleStatusAndArchiveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $academicYear;
    private Department $dept;
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

        $this->dept = Department::create(['name' => 'School of Computer Studies']);
        $this->program = Program::create([
            'department_id' => $this->dept->id,
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
        ]);
    }

    public function test_computed_status_logic(): void
    {
        // Upcoming: opens tomorrow
        $upcoming = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => now()->addDays(2)->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->addDays(5)->format('Y-m-d'),
            'close_time' => '17:00',
        ]);
        $this->assertEquals('Upcoming', $upcoming->computed_status);
        $this->assertFalse($upcoming->isArchived());

        // Open: opened yesterday, closes in 4 days (>48 hours)
        $open = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => now()->subDay()->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->addDays(4)->format('Y-m-d'),
            'close_time' => '17:00',
        ]);
        $this->assertEquals('Open', $open->computed_status);
        $this->assertFalse($open->isArchived());

        // Closing soon: opened 2 days ago, closes in 24 hours (<= 48 hours)
        $closingSoon = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => now()->subDays(2)->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->addHours(20)->format('Y-m-d'),
            'close_time' => now()->addHours(20)->format('H:i'),
        ]);
        $this->assertEquals('Closing soon', $closingSoon->computed_status);
        $this->assertFalse($closingSoon->isArchived());

        // Archived: closed yesterday
        $archived = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => now()->subDays(5)->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->subDay()->format('Y-m-d'),
            'close_time' => '17:00',
        ]);
        $this->assertEquals('Archived', $archived->computed_status);
        $this->assertTrue($archived->isArchived());
    }

    public function test_archived_schedule_cannot_be_edited_or_updated(): void
    {
        $archived = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => now()->subDays(5)->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->subDay()->format('Y-m-d'),
            'close_time' => '17:00',
        ]);

        // Attempting to edit returns 403
        $editResponse = $this->actingAs($this->admin)->get(route('schedules.edit', $archived->id));
        $editResponse->assertStatus(403);

        // Attempting to update returns 403
        $updateResponse = $this->actingAs($this->admin)->put(route('schedules.update', $archived->id), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => now()->addDays(1)->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->addDays(3)->format('Y-m-d'),
            'close_time' => '17:00',
        ]);
        $updateResponse->assertStatus(403);
    }

    public function test_delete_endpoint_is_removed(): void
    {
        $schedule = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => now()->addDays(1)->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->addDays(3)->format('Y-m-d'),
            'close_time' => '17:00',
        ]);

        $deleteResponse = $this->actingAs($this->admin)->delete('/manage/schedules/' . $schedule->id);
        $this->assertTrue(in_array($deleteResponse->getStatusCode(), [404, 405]));
        $this->assertDatabaseHas('assessment_schedules', ['id' => $schedule->id]);
    }

    public function test_schedules_index_segmented_tabs_and_rendering(): void
    {
        // 1 active schedule
        AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => now()->addDays(1)->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->addDays(3)->format('Y-m-d'),
            'close_time' => '17:00',
        ]);

        // 1 archived schedule
        AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->program->id,
            'year_level' => '2nd',
            'open_date' => now()->subDays(5)->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->subDay()->format('Y-m-d'),
            'close_time' => '17:00',
        ]);

        // Default Active View
        $activeResponse = $this->actingAs($this->admin)->get(route('schedules.index'));
        $activeResponse->assertStatus(200);
        $activeResponse->assertSee('Active (1)');
        $activeResponse->assertSee('Archived (1)');
        $activeResponse->assertSee('Upcoming');
        $activeResponse->assertDontSee('>Delete<', false);

        // Archived View (?archived=1)
        $archivedResponse = $this->actingAs($this->admin)->get(route('schedules.index', ['archived' => 1]));
        $archivedResponse->assertStatus(200);
        $archivedResponse->assertSee('Archived');
        $archivedResponse->assertSee('Closed on');
        $archivedResponse->assertDontSee('>Edit<', false);
    }
}

