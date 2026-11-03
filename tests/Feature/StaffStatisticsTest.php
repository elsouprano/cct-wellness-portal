<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\InventorySubmission;
use App\Models\Program;
use App\Models\User;
use App\Services\DashboardStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardStatsService $statsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statsService = new DashboardStatsService();
    }

    public function test_registered_count_includes_only_active_students_and_excludes_staff_and_deactivated(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $counselor = User::factory()->create(['role' => 'guidance_counselor', 'is_active' => true]);

        // Active student
        $activeStudent = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'deactivated_at' => null,
        ]);

        // Deactivated student
        User::factory()->create([
            'role' => 'student',
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        $count = $this->statsService->getRegisteredStudentsCount($admin);

        // Should count only the 1 active student
        $this->assertEquals(1, $count);
    }

    public function test_submitted_count_counts_distinct_students_for_selected_academic_year_and_ignores_incomplete_or_other_years(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $ayCurrent = AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);
        $ayPast = AcademicYear::create(['label' => '2025-2026', 'is_current' => false]);

        $student1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student2 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student3 = User::factory()->create(['role' => 'student', 'is_active' => true]);

        // Student 1 submitted in 2026-2027
        InventorySubmission::create([
            'user_id' => $student1->id,
            'academic_year' => $ayCurrent->label,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        // Student 2 has started but NOT submitted in 2026-2027
        InventorySubmission::create([
            'user_id' => $student2->id,
            'academic_year' => $ayCurrent->label,
            'started_at' => now()->subMinutes(5),
            'submitted_at' => null,
            'consent_version' => '1.0',
        ]);

        // Student 3 submitted in 2025-2026 (other year)
        InventorySubmission::create([
            'user_id' => $student3->id,
            'academic_year' => $ayPast->label,
            'started_at' => now()->subMinutes(30),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        $submittedCurrent = $this->statsService->getSubmittedStudentsCount($ayCurrent->label, $admin);
        $submittedPast = $this->statsService->getSubmittedStudentsCount($ayPast->label, $admin);

        $this->assertEquals(1, $submittedCurrent);
        $this->assertEquals(1, $submittedPast);
    }

    public function test_registered_count_updates_immediately_without_stale_cache(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->assertEquals(1, $this->statsService->getRegisteredStudentsCount($admin));

        // Create a new student
        User::factory()->create(['role' => 'student', 'is_active' => true]);

        // Immediate subsequent call must reflect 2 without calling clearCache()
        $this->assertEquals(2, $this->statsService->getRegisteredStudentsCount($admin));
    }

    public function test_zero_registered_students_handles_zero_division_and_shows_dash(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);

        // Zero students in database
        $this->assertEquals(0, $this->statsService->getRegisteredStudentsCount($admin));

        // Manage inventory page
        $inventoryResponse = $this->actingAs($admin)->get(route('staff.inventory.index'));
        $inventoryResponse->assertOk();
        $inventoryResponse->assertSee('Registered students');
        $inventoryResponse->assertSee('Total submissions');
        // Dash shown instead of percentage
        $inventoryResponse->assertSee('—');

        // Staff dashboard
        $dashboardResponse = $this->actingAs($admin)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Registered Students');
        $dashboardResponse->assertSee('—');
    }

    public function test_counselor_scoping_for_registered_and_submitted_counts(): void
    {
        $deptA = Department::create(['name' => 'School of Computer Studies']);
        $deptB = Department::create(['name' => 'School of Business']);

        $progA = Program::create(['department_id' => $deptA->id, 'name' => 'BSIT', 'code' => 'BSIT']);
        $progB = Program::create(['department_id' => $deptB->id, 'name' => 'BSBA', 'code' => 'BSBA']);

        $counselorA = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $deptA->id,
            'is_active' => true,
        ]);

        $ay = AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);

        $studentA = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'program_id' => $progA->id,
        ]);

        $studentB = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'program_id' => $progB->id,
        ]);

        InventorySubmission::create([
            'user_id' => $studentA->id,
            'academic_year' => $ay->label,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        InventorySubmission::create([
            'user_id' => $studentB->id,
            'academic_year' => $ay->label,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        // Counselor A sees only 1 registered student and 1 submitted
        $this->assertEquals(1, $this->statsService->getRegisteredStudentsCount($counselorA));
        $this->assertEquals(1, $this->statsService->getSubmittedStudentsCount($ay->label, $counselorA));
    }
}
