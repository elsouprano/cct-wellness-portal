<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\InventorySubmission;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatCardsLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_cards_have_correct_href_targets_and_styling(): void
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
        ]);

        $ay = AcademicYear::create([
            'label' => '2025-2026',
            'is_current' => true,
        ]);

        $department = Department::create(['name' => 'School of Computer Studies']);
        $program = Program::create([
            'department_id' => $department->id,
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'department_id' => $department->id,
            'program_id' => $program->id,
            'email' => 'student.unique.email@cct.edu.ph',
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => '2025-2026',
            'year_level' => '1st',
            'status' => 'completed',
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();

        // 1. Registered Students card link
        $expectedRegisteredUrl = route('staff.students.index', ['status' => 'active']);
        $response->assertSee(e($expectedRegisteredUrl), false);

        // 2. Submitted card link
        $expectedSubmittedUrl = route('staff.inventory.index', ['academic_year' => '2025-2026']);
        $response->assertSee(e($expectedSubmittedUrl), false);

        // 3. Pending card link
        $expectedPendingUrl = route('staff.students.index', ['status' => 'active', 'submission_status' => 'pending']);
        $response->assertSee(e($expectedPendingUrl), false);

        // 4. Flagged Responses card link
        $expectedFlagsUrl = route('staff.flags.index', ['status' => 'unreviewed', 'academic_year' => '2025-2026']);
        $response->assertSee(e($expectedFlagsUrl), false);

        // 5. Card focus visible indicators
        $response->assertSee('focus-visible:ring-2', false);

        // 6. Terminology: "School" instead of "Department"
        $response->assertSee('All Schools');
        $response->assertDontSee('All Departments');

        // 7. Recent Submissions table does not display student email
        $response->assertDontSee('student.unique.email@cct.edu.ph');
    }
}

