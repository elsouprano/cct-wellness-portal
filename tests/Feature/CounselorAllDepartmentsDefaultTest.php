<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\InventoryFlag;
use App\Models\InventorySubmission;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CounselorAllDepartmentsDefaultTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Department $deptA;
    private Department $deptB;
    private Department $archivedDept;
    private Program $progA;
    private Program $progB;
    private User $studentA;
    private User $studentB;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'label' => '2026-2027',
            'is_current' => true,
        ]);

        $this->deptA = Department::create(['name' => 'School of Computer Studies']);
        $this->deptB = Department::create(['name' => 'School of Business and Management']);
        $this->archivedDept = Department::create([
            'name' => 'Archived School',
            'archived_at' => now(),
        ]);

        $this->progA = Program::create([
            'department_id' => $this->deptA->id,
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
        ]);

        $this->progB = Program::create([
            'department_id' => $this->deptB->id,
            'name' => 'BS Business Administration',
            'code' => 'BSBA',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->studentA = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Alice',
            'last_name' => 'Compute',
            'department_id' => $this->deptA->id,
            'program_id' => $this->progA->id,
            'year_level' => '3rd',
            'section' => '3-1',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->studentB = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Bob',
            'last_name' => 'Biz',
            'department_id' => $this->deptB->id,
            'program_id' => $this->progB->id,
            'year_level' => '3rd',
            'section' => '3-1',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_new_counselor_accounts_default_to_all_active_departments_and_exclude_archived(): void
    {
        Notification::fake();

        // Create view pre-selects all active departments and excludes archived departments
        $indexRes = $this->actingAs($this->admin)->get(route('manage.accounts.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('School of Computer Studies');
        $indexRes->assertSee('School of Business and Management');
        $indexRes->assertDontSee('Archived School');
        $indexRes->assertSee('Select All');

        // Backend store defaults to all active departments when department_ids is omitted
        $storeRes = $this->actingAs($this->admin)->post(route('manage.accounts.store'), [
            'first_name' => 'Clara',
            'last_name' => 'Default',
            'email' => 'clara.default@citycollegeoftagaytay.edu.ph',
        ]);
        $storeRes->assertRedirect(route('manage.accounts.index'));

        $counselor = User::where('email', 'clara.default@citycollegeoftagaytay.edu.ph')->firstOrFail();
        $allowed = $counselor->getAllowedDepartmentIds();

        $this->assertContains($this->deptA->id, $allowed);
        $this->assertContains($this->deptB->id, $allowed);
        $this->assertNotContains($this->archivedDept->id, $allowed);

        // Validation failure when all checkboxes are explicitly unchecked preserves empty selection
        $failRes = $this->actingAs($this->admin)->from(route('manage.accounts.index'))->post(route('manage.accounts.store'), [
            'first_name' => 'Empty',
            'last_name' => 'Check',
            'email' => 'empty.check@citycollegeoftagaytay.edu.ph',
            'departments_submitted' => '1',
        ]);
        $failRes->assertSessionHasErrors('department_id');
        $this->assertNull(User::where('email', 'empty.check@citycollegeoftagaytay.edu.ph')->first());
    }

    public function test_backfill_migration_adds_active_departments_idempotently_and_ignores_students(): void
    {
        $legacyCounselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $this->deptA->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_11_18_100000_backfill_all_active_departments_for_counselors.php');
        $migration->up();
        $migration->up(); // Run twice to verify idempotency

        $pivotCount = DB::table('counselor_departments')
            ->where('user_id', $legacyCounselor->id)
            ->count();

        $this->assertEquals(2, $pivotCount);
        $this->assertEquals($this->deptA->id, $legacyCounselor->fresh()->department_id);
        $this->assertDatabaseMissing('counselor_departments', [
            'user_id' => $legacyCounselor->id,
            'department_id' => $this->archivedDept->id,
        ]);

        // Students never receive counselor_departments assignments
        $this->assertEquals(0, DB::table('counselor_departments')->where('user_id', $this->studentA->id)->count());
        $this->assertEquals(0, DB::table('counselor_departments')->where('user_id', $this->studentB->id)->count());
    }

    public function test_unchecking_department_revokes_access_across_records_flags_and_exports(): void
    {
        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $this->deptA->id, // Legacy department_id points to deptA
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $counselor->assignedDepartments()->sync([$this->deptA->id, $this->deptB->id]);

        $subA = InventorySubmission::create([
            'user_id' => $this->studentA->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'consent_given_at' => now(),
        ]);
        $flagA = InventoryFlag::create([
            'inventory_submission_id' => $subA->id,
            'flag_type' => 'speed',
            'description' => 'Fast completion',
            'is_reviewed' => false,
        ]);

        $subB = InventorySubmission::create([
            'user_id' => $this->studentB->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'consent_given_at' => now(),
        ]);
        $flagB = InventoryFlag::create([
            'inventory_submission_id' => $subB->id,
            'flag_type' => 'speed',
            'description' => 'Fast completion',
            'is_reviewed' => false,
        ]);

        // Admin unchecks deptA, leaving only deptB
        $this->actingAs($this->admin)->put(route('manage.accounts.update', $counselor), [
            'first_name' => $counselor->first_name,
            'last_name' => $counselor->last_name,
            'email' => $counselor->email,
            'department_ids' => [$this->deptB->id],
        ])->assertRedirect(route('manage.accounts.index'));

        $counselor->refresh();
        $this->assertEquals([$this->deptB->id], $counselor->getAllowedDepartmentIds());

        // Dept A is now blocked across profiles, submissions, PDF export, and flagged responses
        $this->actingAs($counselor)->get(route('staff.students.show', $this->studentA))->assertNotFound();
        $this->actingAs($counselor)->get(route('staff.inventory.show', $subA))->assertNotFound();
        $this->actingAs($counselor)->get(route('staff.inventory.export', $subA))->assertNotFound();
        $this->actingAs($counselor)->patch(route('staff.flags.review', $flagA))->assertNotFound();

        // Dept B remains accessible
        $this->actingAs($counselor)->get(route('staff.students.show', $this->studentB))->assertOk();
        $this->actingAs($counselor)->get(route('staff.inventory.show', $subB))->assertOk();
        $this->actingAs($counselor)->get(route('staff.inventory.export', $subB))->assertOk();
        $this->actingAs($counselor)->patch(route('staff.flags.review', $flagB))->assertRedirect();
    }

    public function test_empty_assignment_never_grants_unrestricted_access(): void
    {
        $unassigned = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->assertEquals([], $unassigned->getAllowedDepartmentIds());
        $this->assertEquals([], $unassigned->getAllowedProgramIds());
        $this->assertFalse($unassigned->canAccessStudent($this->studentA));
        $this->actingAs($unassigned)->get(route('staff.students.show', $this->studentA))->assertNotFound();
    }

    public function test_new_department_auto_assigns_to_all_dept_counselors_only_and_admin_stays_unrestricted(): void
    {
        $allDeptCounselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $this->deptA->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $allDeptCounselor->assignedDepartments()->sync([$this->deptA->id, $this->deptB->id]);

        $restrictedCounselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $this->deptA->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $restrictedCounselor->assignedDepartments()->sync([$this->deptA->id]);

        $this->actingAs($this->admin)->post(route('institution.departments.store'), [
            'name' => 'School of Engineering',
            'programs' => [
                ['name' => 'BS Civil Engineering', 'code' => 'BSCE'],
            ],
        ])->assertRedirect(route('institution.index'));

        $newDept = Department::where('name', 'School of Engineering')->firstOrFail();

        // All-department counselor received the new department
        $this->assertContains($newDept->id, $allDeptCounselor->fresh()->getAllowedDepartmentIds());

        // Intentionally restricted counselor did NOT receive the new department
        $this->assertNotContains($newDept->id, $restrictedCounselor->fresh()->getAllowedDepartmentIds());

        // Students never receive department assignments
        $this->assertDatabaseMissing('counselor_departments', ['user_id' => $this->studentA->id]);

        // System Admin retains unrestricted access across all records
        $subA = InventorySubmission::create([
            'user_id' => $this->studentA->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'consent_given_at' => now(),
        ]);
        $this->assertNull($this->admin->getAllowedProgramIds());
        $this->actingAs($this->admin)->get(route('staff.students.show', $this->studentA))->assertOk();
        $this->actingAs($this->admin)->get(route('staff.inventory.show', $subA))->assertOk();
        $this->actingAs($this->admin)->get(route('staff.inventory.export', $subA))->assertOk();
        $this->actingAs($this->admin)->get(route('activity-logs.index'))->assertOk();
    }

    public function test_account_management_displays_compact_school_badges_with_full_name_tooltips(): void
    {
        $shtm = Department::create(['name' => 'School of Hospitality and Tourism Management']);
        $sed = Department::create(['name' => 'School of Education']);
        $sas = Department::create(['name' => 'School of Arts and Sciences']);

        $allSchoolsCounselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'first_name' => 'Jane',
            'last_name' => 'Guidance',
            'department_id' => $this->deptA->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $allSchoolsCounselor->assignedDepartments()->sync([
            $this->deptA->id,
            $this->deptB->id,
            $shtm->id,
            $sed->id,
            $sas->id,
        ]);

        $unassignedCounselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'first_name' => 'No',
            'last_name' => 'School',
            'department_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('manage.accounts.index'));
        $res->assertOk();

        // Compact badges in table with full-name tooltips
        $res->assertSee('title="School of Computer Studies"', false);
        $res->assertSee('SCS');
        $res->assertSee('title="School of Business and Management"', false);
        $res->assertSee('SBM');
        $res->assertSee('title="School of Hospitality and Tourism Management"', false);
        $res->assertSee('SHTM');
        $res->assertSee('title="School of Education"', false);
        $res->assertSee('SED');
        $res->assertSee('title="School of Arts and Sciences"', false);
        $res->assertSee('SAS');

        // Empty assignment state
        $res->assertSee('Unassigned');

        // Modals still display full school names beside checkboxes
        $res->assertSee('School of Hospitality and Tourism Management');
        $res->assertSee('School of Arts and Sciences');
    }
}
