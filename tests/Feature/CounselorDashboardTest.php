<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\Department;
use App\Models\InventoryFlag;
use App\Models\InventorySubmission;
use App\Models\Program;
use App\Models\StudentFollowUp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounselorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $academicYear;
    private Department $deptA;
    private Department $deptB;
    private Department $deptC;
    private Program $progA;
    private Program $progB;
    private Program $progC;
    private User $admin;
    private User $counselorSingle;
    private User $counselorMulti;
    private User $studentA;
    private User $studentB;
    private User $studentC;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'label' => '2026-2027',
            'is_current' => true,
        ]);

        $this->deptA = Department::create(['name' => 'College of Computer Studies']);
        $this->deptB = Department::create(['name' => 'College of Business Administration']);
        $this->deptC = Department::create(['name' => 'College of Education']);

        $this->progA = Program::create([
            'department_id' => $this->deptA->id,
            'name' => 'Information Technology',
            'code' => 'BSIT',
        ]);

        $this->progB = Program::create([
            'department_id' => $this->deptB->id,
            'name' => 'Business Administration',
            'code' => 'BSBA',
        ]);

        $this->progC = Program::create([
            'department_id' => $this->deptC->id,
            'name' => 'Secondary Education',
            'code' => 'BSED',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Single department counselor (Dept A)
        $this->counselorSingle = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $this->deptA->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->counselorSingle->assignedDepartments()->attach($this->deptA->id);

        // Multi department counselor (Dept A + Dept B)
        $this->counselorMulti = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $this->deptA->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->counselorMulti->assignedDepartments()->attach([$this->deptA->id, $this->deptB->id]);

        // Students in each department
        $this->studentA = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Alice',
            'last_name' => 'Computer',
            'student_id' => '2026-0001',
            'program_id' => $this->progA->id,
            'program' => 'BSIT',
            'year_level' => '3rd',
            'section' => '3A',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->studentB = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Bob',
            'last_name' => 'Business',
            'student_id' => '2026-0002',
            'program_id' => $this->progB->id,
            'program' => 'BSBA',
            'year_level' => '3rd',
            'section' => '3B',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->studentC = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Charlie',
            'last_name' => 'Education',
            'student_id' => '2026-0003',
            'program_id' => $this->progC->id,
            'program' => 'BSED',
            'year_level' => '3rd',
            'section' => '3C',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Scenario 1: System Admin assigns a counselor to one department;
     * counselor sees only that department's students.
     */
    public function test_single_department_counselor_sees_only_assigned_department_students(): void
    {
        $response = $this->actingAs($this->counselorSingle)->get(route('staff.students.index'));

        $response->assertStatus(200);
        $response->assertSee('Alice');
        $response->assertSee('Computer');
        $response->assertDontSee('Business, Bob');
        $response->assertDontSee('Education, Charlie');
    }

    /**
     * Scenario 2: System Admin assigns a counselor to multiple departments;
     * counselor sees students from all assigned departments.
     */
    public function test_multi_department_counselor_sees_students_from_all_assigned_departments(): void
    {
        $response = $this->actingAs($this->counselorMulti)->get(route('staff.students.index'));

        $response->assertStatus(200);
        $response->assertSee('Computer, Alice');
        $response->assertSee('Business, Bob');
        $response->assertDontSee('Education, Charlie');
    }

    /**
     * Scenario 3: Counselor attempts to access student profile from unassigned department by URL/parameter tampering;
     * backend denies access with 404.
     */
    public function test_counselor_cannot_access_unassigned_department_student_profile(): void
    {
        // Counselor Single has only Dept A, attempts to access Student B (Dept B)
        $response = $this->actingAs($this->counselorSingle)->get(route('staff.students.show', $this->studentB->id));
        $response->assertStatus(404);

        // Counselor Multi has Dept A + B, attempts to access Student C (Dept C)
        $responseC = $this->actingAs($this->counselorMulti)->get(route('staff.students.show', $this->studentC->id));
        $responseC->assertStatus(404);
    }

    /**
     * Scenario 4: Counselor attempts to access submission from unassigned department;
     * backend denies access with 404.
     */
    public function test_counselor_cannot_access_unassigned_department_submission(): void
    {
        $subB = InventorySubmission::create([
            'user_id' => $this->studentB->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        // Counselor Single (Dept A) cannot view submission B (Dept B)
        $response = $this->actingAs($this->counselorSingle)->get(route('staff.inventory.show', $subB->id));
        $response->assertStatus(404);

        // Counselor Single cannot export submission B
        $exportResponse = $this->actingAs($this->counselorSingle)->get(route('staff.inventory.export', $subB->id));
        $exportResponse->assertStatus(404);
    }

    /**
     * Scenario 5: Counselor attempts to access follow-up from unassigned department;
     * backend denies access with 404.
     */
    public function test_counselor_cannot_update_unassigned_department_student_follow_up(): void
    {
        $response = $this->actingAs($this->counselorSingle)->post(route('staff.follow-ups.update', $this->studentB->id), [
            'status' => 'for_follow_up',
            'notes' => 'Tampered follow up attempt',
            'priority' => 'high',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Scenario 6: Dashboard stats: Assigned students, submitted, pending, flagged
     * match actual database records.
     */
    public function test_dashboard_stats_match_actual_database_records(): void
    {
        // Student A submits
        $subA = InventorySubmission::create([
            'user_id' => $this->studentA->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'consent_version' => '1.0',
            'quality_classification' => 'FOR REVIEW',
        ]);

        InventoryFlag::create([
            'inventory_submission_id' => $subA->id,
            'flag_type' => 'straight_line',
            'severity' => 'moderate',
            'is_reviewed' => false,
            'details' => ['identical_response_rate' => 0.95],
        ]);

        // Student B submits (Dept B)
        InventorySubmission::create([
            'user_id' => $this->studentB->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now(),
            'consent_version' => '1.0',
            'quality_classification' => 'ACCEPTABLE',
        ]);

        // For Counselor Single (Dept A only):
        // Assigned: 1 (studentA), Submitted: 1, Pending: 0, Flagged: 1
        $responseSingle = $this->actingAs($this->counselorSingle)->get(route('dashboard'));
        $responseSingle->assertStatus(200);
        $responseSingle->assertViewHas('assignedStudentsCount', 1);
        $responseSingle->assertViewHas('submittedCount', 1);
        $responseSingle->assertViewHas('pendingCount', 0);
        $responseSingle->assertViewHas('flaggedResponsesCount', 1);
        $responseSingle->assertViewHas('completionPercentage', 100.0);

        // For Counselor Multi (Dept A + Dept B):
        // Assigned: 2, Submitted: 2, Pending: 0, Flagged: 1
        $responseMulti = $this->actingAs($this->counselorMulti)->get(route('dashboard'));
        $responseMulti->assertStatus(200);
        $responseMulti->assertViewHas('assignedStudentsCount', 2);
        $responseMulti->assertViewHas('submittedCount', 2);
        $responseMulti->assertViewHas('pendingCount', 0);
        $responseMulti->assertViewHas('flaggedResponsesCount', 1);
    }

    /**
     * Scenario 7: Flagged response review: Counselor can view and toggle review status with notes.
     */
    public function test_counselor_can_view_and_toggle_flagged_response_review(): void
    {
        $subA = InventorySubmission::create([
            'user_id' => $this->studentA->id,
            'academic_year' => $this->academicYear->label,
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'consent_version' => '1.0',
            'quality_classification' => 'FOR REVIEW',
        ]);

        $flag = InventoryFlag::create([
            'inventory_submission_id' => $subA->id,
            'flag_type' => 'speed',
            'severity' => 'mild',
            'is_reviewed' => false,
            'details' => ['avg_seconds_per_item' => 0.8],
        ]);

        // View dedicated flagged responses page
        $indexResponse = $this->actingAs($this->counselorSingle)->get(route('staff.flags.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Computer, Alice');

        // Toggle review to reviewed
        $patchResponse = $this->actingAs($this->counselorSingle)->patch(route('staff.flags.review', $flag->id), [
            'notes' => 'Reviewed and deemed acceptable after student check-in.',
        ]);

        $patchResponse->assertRedirect();
        $this->assertDatabaseHas('inventory_flags', [
            'id' => $flag->id,
            'is_reviewed' => true,
            'reviewed_by' => $this->counselorSingle->id,
            'reviewer_notes' => 'Reviewed and deemed acceptable after student check-in.',
        ]);

        // Toggle back to unreviewed
        $unreviewResponse = $this->actingAs($this->counselorSingle)->patch(route('staff.flags.review', $flag->id));
        $unreviewResponse->assertRedirect();
        $this->assertDatabaseHas('inventory_flags', [
            'id' => $flag->id,
            'is_reviewed' => false,
            'reviewed_by' => null,
            'reviewer_notes' => null,
        ]);
    }

    /**
     * Scenario 8: Follow-up management: Counselor can update follow-up status,
     * set priority, and add confidential notes.
     */
    public function test_counselor_can_manage_student_follow_up(): void
    {
        // Counselor updates student follow-up
        $response = $this->actingAs($this->counselorSingle)->post(route('staff.follow-ups.update', $this->studentA->id), [
            'status' => 'for_follow_up',
            'priority' => 'high',
            'notes' => 'Student indicated elevated stress levels. Follow-up interview scheduled.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_follow_ups', [
            'student_id' => $this->studentA->id,
            'counselor_id' => $this->counselorSingle->id,
            'status' => 'for_follow_up',
            'priority' => 'high',
            'notes' => 'Student indicated elevated stress levels. Follow-up interview scheduled.',
        ]);

        // Check index displays follow-up
        $indexResponse = $this->actingAs($this->counselorSingle)->get(route('staff.follow-ups.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Computer, Alice');
        $indexResponse->assertSee('For Follow-up');
    }

    /**
     * Scenario 9: System Admin has unrestricted access to all departments and all students.
     */
    public function test_system_admin_has_unrestricted_access(): void
    {
        $response = $this->actingAs($this->admin)->get(route('staff.students.index'));
        $response->assertStatus(200);
        $response->assertSee('Computer, Alice');
        $response->assertSee('Business, Bob');
        $response->assertSee('Education, Charlie');

        // Admin can access all student profiles
        $responseA = $this->actingAs($this->admin)->get(route('staff.students.show', $this->studentA->id));
        $responseA->assertStatus(200);

        $responseC = $this->actingAs($this->admin)->get(route('staff.students.show', $this->studentC->id));
        $responseC->assertStatus(200);
    }

    /**
     * Scenario 10: Counselor sidebar does not display admin modules.
     */
    public function test_counselor_sidebar_hides_admin_modules(): void
    {
        $response = $this->actingAs($this->counselorSingle)->get(route('dashboard'));

        $response->assertStatus(200);
        // Counselor items visible
        $response->assertSee('Student Profiles');
        $response->assertSee('Submissions');
        $response->assertSee('Flagged Responses');
        $response->assertSee('Follow-ups');
        $response->assertSee('Analytics');
        $response->assertSee('Announcements');

        // Admin-only items NOT visible to counselor
        $response->assertDontSee('Institution Data');
        $response->assertDontSee('Account Management');
        $response->assertDontSee('Year Levels');
        $response->assertDontSee('Flag Settings');
    }
}
