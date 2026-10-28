<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $counselor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'year_level' => '2nd',
            'year_level_confirmed' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_archive_student_and_student_disappears_from_active_list(): void
    {
        // Student is in the active directory initially
        $indexResponse = $this->actingAs($this->admin)->get(route('staff.students.index'));
        $indexResponse->assertSee($this->student->email);

        // Admin archives the student
        $response = $this->actingAs($this->admin)
            ->from(route('staff.students.show', $this->student))
            ->patch(route('staff.students.archive', $this->student));

        $response->assertRedirect(route('staff.students.show', $this->student));
        $response->assertSessionHas('success', 'Student archived successfully.');

        $this->student->refresh();
        $this->assertFalse((bool) $this->student->is_active);
        $this->assertNotNull($this->student->deactivated_at);
        $this->assertEquals($this->admin->id, $this->student->deactivated_by);

        // Archived student disappears from the normal Student Profiles list
        $indexResponse = $this->actingAs($this->admin)->get(route('staff.students.index'));
        $indexResponse->assertDontSee($this->student->email);

        // Student profile is still accessible directly and shows Archived badge
        $showResponse = $this->actingAs($this->admin)->get(route('staff.students.show', $this->student));
        $showResponse->assertOk();
        $showResponse->assertSee('Archived');
        // The archive button is hidden since already archived
        $showResponse->assertDontSee('Archive Student');
    }

    public function test_non_admin_users_cannot_archive_students(): void
    {
        // Unauthenticated user is redirected to login
        $this->patch(route('staff.students.archive', $this->student))
            ->assertRedirect('/login');

        // Guidance counselor gets 403 Forbidden
        $this->actingAs($this->counselor)
            ->patch(route('staff.students.archive', $this->student))
            ->assertStatus(403);

        // Student gets 403 Forbidden
        $anotherStudent = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->actingAs($anotherStudent)
            ->patch(route('staff.students.archive', $this->student))
            ->assertStatus(403);

        // Verify student remains active
        $this->student->refresh();
        $this->assertTrue((bool) $this->student->is_active);
    }

    public function test_admin_can_unarchive_student_and_student_reappears_in_active_list(): void
    {
        // Setup archived student
        $this->student->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => $this->admin->id,
        ]);

        // Archived student shows Unarchive button
        $showResponse = $this->actingAs($this->admin)->get(route('staff.students.show', $this->student));
        $showResponse->assertSee('Unarchive Student');

        // Admin unarchives student
        $response = $this->actingAs($this->admin)
            ->from(route('staff.students.show', $this->student))
            ->patch(route('staff.students.unarchive', $this->student));

        $response->assertRedirect(route('staff.students.show', $this->student));
        $response->assertSessionHas('success', 'Student unarchived successfully.');

        $this->student->refresh();
        $this->assertTrue((bool) $this->student->is_active);
        $this->assertNull($this->student->deactivated_at);
        $this->assertNull($this->student->deactivated_by);

        // Student reappears in the active directory
        $indexResponse = $this->actingAs($this->admin)->get(route('staff.students.index'));
        $indexResponse->assertSee($this->student->email);
    }

    public function test_status_filter_filters_active_and_archived_students(): void
    {
        $archivedStudent = User::factory()->create([
            'role' => 'student',
            'is_active' => false,
            'email' => 'archived_student@test.com',
        ]);

        // Default / active filter shows active student and hides archived student
        $responseActive = $this->actingAs($this->admin)->get(route('staff.students.index', ['status' => 'active']));
        $responseActive->assertSee($this->student->email);
        $responseActive->assertDontSee($archivedStudent->email);

        // Archived filter shows archived student and hides active student
        $responseArchived = $this->actingAs($this->admin)->get(route('staff.students.index', ['status' => 'archived']));
        $responseArchived->assertSee($archivedStudent->email);
        $responseArchived->assertDontSee($this->student->email);

        // All filter shows both
        $responseAll = $this->actingAs($this->admin)->get(route('staff.students.index', ['status' => 'all']));
        $responseAll->assertSee($this->student->email);
        $responseAll->assertSee($archivedStudent->email);
    }

    public function test_non_admin_users_cannot_unarchive_students(): void
    {
        $this->student->update(['is_active' => false]);

        // Unauthenticated user is redirected to login
        $this->patch(route('staff.students.unarchive', $this->student))
            ->assertRedirect('/login');

        // Guidance counselor gets 403 Forbidden
        $this->actingAs($this->counselor)
            ->patch(route('staff.students.unarchive', $this->student))
            ->assertStatus(403);

        // Student gets 403 Forbidden
        $anotherStudent = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->actingAs($anotherStudent)
            ->patch(route('staff.students.unarchive', $this->student))
            ->assertStatus(403);

        // Verify student remains inactive
        $this->student->refresh();
        $this->assertFalse((bool) $this->student->is_active);
    }

    public function test_actions_fail_if_target_user_is_not_a_student(): void
    {
        // If target is counselor, abort 404
        $this->actingAs($this->admin)
            ->patch(route('staff.students.archive', $this->counselor))
            ->assertStatus(404);

        $this->actingAs($this->admin)
            ->patch(route('staff.students.unarchive', $this->counselor))
            ->assertStatus(404);
    }
}
