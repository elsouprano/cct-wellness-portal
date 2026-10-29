<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\Department;
use App\Models\InventoryFlag;
use App\Models\InventoryScore;
use App\Models\InventorySubmission;
use App\Models\Program;
use App\Models\User;
use App\Services\DashboardStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PerDepartmentCounselorTest extends TestCase
{
    use RefreshDatabase;

    private Department $deptA;
    private Department $deptB;
    private Program $progA;
    private Program $progB;
    private User $counselorA;
    private User $counselorB;
    private User $unassignedCounselor;
    private User $admin;
    private User $studentA;
    private User $studentB;
    private User $studentNull;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'label' => '2025-2026',
            'is_current' => true,
        ]);

        $this->deptA = Department::create(['name' => 'School of Computer Studies']);
        $this->deptB = Department::create(['name' => 'School of Business Administration']);

        $this->progA = Program::create([
            'department_id' => $this->deptA->id,
            'name' => 'Computer Science',
            'code' => 'BSCS',
        ]);

        $this->progB = Program::create([
            'department_id' => $this->deptB->id,
            'name' => 'Business Administration',
            'code' => 'BSBA',
        ]);

        $this->counselorA = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $this->deptA->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->counselorB = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $this->deptB->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->unassignedCounselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->studentA = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Alice',
            'last_name' => 'Computer',
            'program_id' => $this->progA->id,
            'year_level' => '1st',
            'email_verified_at' => now(),
        ]);

        $this->studentB = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Bob',
            'last_name' => 'Business',
            'program_id' => $this->progB->id,
            'year_level' => '2nd',
            'email_verified_at' => now(),
        ]);

        $this->studentNull = User::factory()->create([
            'role' => 'student',
            'first_name' => 'NoProgram',
            'last_name' => 'Student',
            'program_id' => null,
            'year_level' => '1st',
            'email_verified_at' => now(),
        ]);
    }

    public function test_counselor_in_dept_a_cannot_access_student_submission_in_dept_b()
    {
        $subB = InventorySubmission::create([
            'user_id' => $this->studentB->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        $flagB = InventoryFlag::create([
            'inventory_submission_id' => $subB->id,
            'flag_type' => 'speed',
            'is_reviewed' => false,
            'details' => ['avg_seconds_per_item' => 0.4],
        ]);

        // Counselor A cannot see Student B's submission on index
        $indexResponse = $this->actingAs($this->counselorA)->get(route('staff.inventory.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertDontSee('Business, Bob');

        // Counselor A gets 404 when directly requesting submission B
        $showResponse = $this->actingAs($this->counselorA)->get(route('staff.inventory.show', $subB->id));
        $showResponse->assertStatus(404);

        // Counselor A gets 404 when attempting to export submission B PDF
        $exportPdfResponse = $this->actingAs($this->counselorA)->get(route('staff.inventory.export', $subB->id));
        $exportPdfResponse->assertStatus(404);

        // Counselor A gets 404 when attempting to review flag for submission B
        $reviewResponse = $this->actingAs($this->counselorA)->patch(route('staff.inventory.flags.review', $flagB->id), [
            'notes' => 'Unauthorized review attempt',
        ]);
        $reviewResponse->assertStatus(404);

        // Flag remains unreviewed
        $this->assertFalse((bool) $flagB->fresh()->is_reviewed);
    }

    public function test_counselor_cannot_access_out_of_department_student_profile_or_override()
    {
        // Counselor A cannot see Student B in students index
        $indexResponse = $this->actingAs($this->counselorA)->get(route('staff.students.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertDontSee('Business, Bob');

        // Counselor A gets 404 accessing Student B profile
        $showResponse = $this->actingAs($this->counselorA)->get(route('staff.students.show', $this->studentB->id));
        $showResponse->assertStatus(404);

        // Counselor A gets 403 (or 404) attempting to access admin-only override route for Student B
        $overrideResponse = $this->actingAs($this->counselorA)->post(route('year-levels.override', $this->studentB->id), [
            'year_level' => '3rd',
            'reason' => 'Unauthorized override attempt',
        ]);
        $this->assertTrue(in_array($overrideResponse->status(), [403, 404]));
    }

    public function test_counselor_cannot_manage_another_departments_schedule_or_all_programs_schedule()
    {
        $scheduleA = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->progA->id,
            'year_level' => '1st',
            'open_date' => '2026-08-01',
            'open_time' => '08:00',
            'close_date' => '2026-08-31',
            'close_time' => '17:00',
        ]);

        $scheduleB = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->progB->id,
            'year_level' => '2nd',
            'open_date' => '2026-08-01',
            'open_time' => '08:00',
            'close_date' => '2026-08-31',
            'close_time' => '17:00',
        ]);

        $scheduleAll = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => null,
            'year_level' => '1st',
            'open_date' => '2026-08-01',
            'open_time' => '08:00',
            'close_date' => '2026-08-31',
            'close_time' => '17:00',
        ]);

        // Index shows Schedule A and Schedule All, but hides Schedule B
        $indexResponse = $this->actingAs($this->counselorA)->get(route('schedules.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($this->progA->name);
        $indexResponse->assertSee('Read-only (All Programs)');
        $indexResponse->assertDontSee($this->progB->name);

        // Counselor A gets 404 editing Schedule B
        $editBResponse = $this->actingAs($this->counselorA)->get(route('schedules.edit', $scheduleB->id));
        $editBResponse->assertStatus(404);

        $updateBResponse = $this->actingAs($this->counselorA)->put(route('schedules.update', $scheduleB->id), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->progA->id,
            'year_level' => '1st',
            'open_date' => '2026-08-01',
            'open_time' => '08:00',
            'close_date' => '2026-08-31',
            'close_time' => '17:00',
        ]);
        $updateBResponse->assertStatus(404);

        $deleteBResponse = $this->actingAs($this->counselorA)->delete(route('schedules.destroy', $scheduleB->id));
        $deleteBResponse->assertStatus(404);

        // Counselor A gets 404 editing Schedule All (read-only for counselors)
        $editAllResponse = $this->actingAs($this->counselorA)->get(route('schedules.edit', $scheduleAll->id));
        $editAllResponse->assertStatus(404);

        $updateAllResponse = $this->actingAs($this->counselorA)->put(route('schedules.update', $scheduleAll->id), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->progA->id,
            'year_level' => '1st',
            'open_date' => '2026-08-01',
            'open_time' => '08:00',
            'close_date' => '2026-08-31',
            'close_time' => '17:00',
        ]);
        $updateAllResponse->assertStatus(404);

        $deleteAllResponse = $this->actingAs($this->counselorA)->delete(route('schedules.destroy', $scheduleAll->id));
        $deleteAllResponse->assertStatus(404);
    }

    public function test_counselor_cannot_create_schedule_with_tampered_or_null_program_id()
    {
        // Attempt with null program_id
        $nullResponse = $this->actingAs($this->counselorA)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => null,
            'year_level' => '1st',
            'open_date' => '2026-08-15',
            'open_time' => '08:00',
            'close_date' => '2026-08-15',
            'close_time' => '17:00',
        ]);
        $nullResponse->assertSessionHasErrors('program_id');

        // Attempt with program_id from Department B (tampered)
        $tamperedResponse = $this->actingAs($this->counselorA)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->progB->id,
            'year_level' => '1st',
            'open_date' => '2026-08-15',
            'open_time' => '08:00',
            'close_date' => '2026-08-15',
            'close_time' => '17:00',
        ]);
        $tamperedResponse->assertSessionHasErrors('program_id');

        // Valid creation with program_id from Department A succeeds
        $validResponse = $this->actingAs($this->counselorA)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->progA->id,
            'year_level' => '1st',
            'open_date' => '2026-08-15',
            'open_time' => '08:00',
            'close_date' => '2026-08-15',
            'close_time' => '17:00',
        ]);
        $validResponse->assertRedirect(route('schedules.index'));
        $this->assertDatabaseHas('assessment_schedules', [
            'program_id' => $this->progA->id,
            'year_level' => '1st',
        ]);
    }

    public function test_students_only_receive_schedules_for_their_own_program_or_all_programs()
    {
        $scheduleA = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->progA->id,
            'year_level' => '1st',
            'open_date' => now()->subDay()->toDateString(),
            'open_time' => '08:00',
            'close_date' => now()->addDays(5)->toDateString(),
            'close_time' => '17:00',
        ]);

        $scheduleB = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->progB->id,
            'year_level' => '2nd',
            'open_date' => now()->subDay()->toDateString(),
            'open_time' => '08:00',
            'close_date' => now()->addDays(5)->toDateString(),
            'close_time' => '17:00',
        ]);

        $scheduleAll = AssessmentSchedule::create([
            'academic_year_id' => $this->academicYear->id,
            'program_id' => null,
            'year_level' => '1st',
            'open_date' => now()->subDay()->toDateString(),
            'open_time' => '08:00',
            'close_date' => now()->addDays(5)->toDateString(),
            'close_time' => '17:00',
        ]);

        $activeForStudentA = AssessmentSchedule::getActiveForUser($this->studentA, $this->academicYear->id);
        $this->assertNotNull($activeForStudentA);
        $this->assertEquals($scheduleA->id, $activeForStudentA->id);

        $activeForStudentB = AssessmentSchedule::getActiveForUser($this->studentB, $this->academicYear->id);
        $this->assertNotNull($activeForStudentB);
        $this->assertEquals($scheduleB->id, $activeForStudentB->id);
    }

    public function test_unassigned_counselor_sees_no_student_data_and_cannot_create_schedules()
    {
        // Unassigned counselor dashboard shows banner and empty data
        $dashResponse = $this->actingAs($this->unassignedCounselor)->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('No department assigned yet. Please contact the system administrator.');

        // Schedules create route is blocked (403)
        $createScheduleResponse = $this->actingAs($this->unassignedCounselor)->get(route('schedules.create'));
        $createScheduleResponse->assertStatus(403);

        $storeScheduleResponse = $this->actingAs($this->unassignedCounselor)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => $this->progA->id,
            'year_level' => '1st',
            'open_date' => '2026-08-15',
            'open_time' => '08:00',
            'close_date' => '2026-08-15',
            'close_time' => '17:00',
        ]);
        $storeScheduleResponse->assertStatus(403);

        // Submissions index shows no students
        $invResponse = $this->actingAs($this->unassignedCounselor)->get(route('staff.inventory.index'));
        $invResponse->assertStatus(200);
        $invResponse->assertDontSee($this->studentA->first_name);
        $invResponse->assertDontSee($this->studentB->first_name);

        // Students index shows no students
        $studResponse = $this->actingAs($this->unassignedCounselor)->get(route('staff.students.index'));
        $studResponse->assertStatus(200);
        $studResponse->assertDontSee($this->studentA->first_name);
        $studResponse->assertDontSee($this->studentB->first_name);
    }

    public function test_admin_sees_everything_including_students_without_program()
    {
        $studentsResponse = $this->actingAs($this->admin)->get(route('staff.students.index'));
        $studentsResponse->assertStatus(200);
        $studentsResponse->assertSee('Computer, Alice');
        $studentsResponse->assertSee('Business, Bob');
        $studentsResponse->assertSee('Student, NoProgram');

        // Admin can access student without program
        $nullStudentResponse = $this->actingAs($this->admin)->get(route('staff.students.show', $this->studentNull->id));
        $nullStudentResponse->assertStatus(200);

        // Admin can create schedule for all programs (program_id = null)
        $storeAllResponse = $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_year_id' => $this->academicYear->id,
            'program_id' => null,
            'year_level' => '1st',
            'open_date' => '2026-08-15',
            'open_time' => '08:00',
            'close_date' => '2026-08-15',
            'close_time' => '17:00',
        ]);
        $storeAllResponse->assertRedirect(route('schedules.index'));
        $this->assertDatabaseHas('assessment_schedules', [
            'program_id' => null,
            'year_level' => '1st',
        ]);
    }

    public function test_dashboard_and_analytics_stats_differ_by_department_and_do_not_leak_cache()
    {
        // Create 2 submissions in Dept A and 1 in Dept B
        InventorySubmission::create([
            'user_id' => $this->studentA->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        $studentA2 = User::factory()->create([
            'role' => 'student',
            'program_id' => $this->progA->id,
            'year_level' => '1st',
            'email_verified_at' => now(),
        ]);

        InventorySubmission::create([
            'user_id' => $studentA2->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        InventorySubmission::create([
            'user_id' => $this->studentB->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        $service = new DashboardStatsService();

        $statsA = $service->getStats($this->academicYear->label, $this->counselorA);
        $statsB = $service->getStats($this->academicYear->label, $this->counselorB);
        $statsAdmin = $service->getStats($this->academicYear->label, $this->admin);

        // Dept A counselor sees 2 submissions
        $this->assertEquals(2, $statsA['totalSubmissions']);

        // Dept B counselor sees 1 submission
        $this->assertEquals(1, $statsB['totalSubmissions']);

        // Admin sees all 3 submissions
        $this->assertEquals(3, $statsAdmin['totalSubmissions']);

        // Verify distinct cache keys exist
        $this->assertTrue(Cache::has("dashboard_stats_{$this->academicYear->label}_dept_{$this->deptA->id}"));
        $this->assertTrue(Cache::has("dashboard_stats_{$this->academicYear->label}_dept_{$this->deptB->id}"));
        $this->assertTrue(Cache::has("dashboard_stats_{$this->academicYear->label}_all"));

        // Clear cache removes all variants
        DashboardStatsService::clearCache();
        $this->assertFalse(Cache::has("dashboard_stats_{$this->academicYear->label}_dept_{$this->deptA->id}"));
        $this->assertFalse(Cache::has("dashboard_stats_{$this->academicYear->label}_dept_{$this->deptB->id}"));
        $this->assertFalse(Cache::has("dashboard_stats_{$this->academicYear->label}_all"));
    }

    public function test_institution_controller_prevents_deleting_department_with_assigned_counselors()
    {
        $deptWithCounselorOnly = Department::create(['name' => 'Department of Guidance Staff']);
        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $deptWithCounselorOnly->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('institution.departments.destroy', $deptWithCounselorOnly->id));
        $response->assertSessionHasErrors(['error' => 'Cannot delete department: it still has counselors assigned.']);
        $this->assertDatabaseHas('departments', ['id' => $deptWithCounselorOnly->id]);
    }
}
