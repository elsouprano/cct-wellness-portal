<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\InventorySubmission;
use App\Models\User;
use App\Models\YearLevelAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearPromotionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $counselor;

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
    }

    public function test_setting_new_academic_year_promotes_students_by_one_year_level(): void
    {
        $ayCurrent = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);
        $ayNext = AcademicYear::create(['label' => '2026-2027', 'is_current' => false]);

        $student1st = User::factory()->create([
            'role' => 'student',
            'student_id' => '2025-0001',
            'first_name' => 'First',
            'last_name' => 'Student',
            'program' => 'BSIT',
            'year_level' => '1st',
            'section' => '1-1',
            'is_active' => true,
            'needs_section_confirmation' => false,
        ]);

        $student2nd = User::factory()->create([
            'role' => 'student',
            'student_id' => '2025-0002',
            'first_name' => 'Second',
            'last_name' => 'Student',
            'program' => 'BSIT',
            'year_level' => '2nd',
            'section' => '2-1',
            'is_active' => true,
            'needs_section_confirmation' => false,
        ]);

        $student3rd = User::factory()->create([
            'role' => 'student',
            'student_id' => '2025-0003',
            'first_name' => 'Third',
            'last_name' => 'Student',
            'program' => 'BSIT',
            'year_level' => '3rd',
            'section' => '3-1',
            'is_active' => true,
            'needs_section_confirmation' => false,
        ]);

        $student4th = User::factory()->create([
            'role' => 'student',
            'student_id' => '2025-0004',
            'first_name' => 'Fourth',
            'last_name' => 'Student',
            'program' => 'BSIT',
            'year_level' => '4th',
            'section' => '4-1',
            'is_active' => true,
            'needs_section_confirmation' => false,
        ]);

        $inactiveStudent = User::factory()->create([
            'role' => 'student',
            'student_id' => '2025-0005',
            'first_name' => 'Inactive',
            'last_name' => 'Student',
            'program' => 'BSIT',
            'year_level' => '1st',
            'section' => '1-1',
            'is_active' => false,
            'needs_section_confirmation' => false,
        ]);

        // Admin sets new academic year as current
        $response = $this->actingAs($this->admin)->patch(route('institution.academic-years.set-current', $ayNext));

        $response->assertRedirect(route('institution.index'));
        $response->assertSessionHas('status');

        $ayCurrent->refresh();
        $ayNext->refresh();
        $this->assertFalse((bool) $ayCurrent->is_current);
        $this->assertTrue((bool) $ayNext->is_current);

        // Verify students 1st, 2nd, 3rd promoted to +1 year level
        $student1st->refresh();
        $this->assertEquals('2nd', $student1st->year_level);
        $this->assertEmpty($student1st->section);
        $this->assertTrue((bool) $student1st->needs_section_confirmation);

        $student2nd->refresh();
        $this->assertEquals('3rd', $student2nd->year_level);
        $this->assertEmpty($student2nd->section);
        $this->assertTrue((bool) $student2nd->needs_section_confirmation);

        $student3rd->refresh();
        $this->assertEquals('4th', $student3rd->year_level);
        $this->assertEmpty($student3rd->section);
        $this->assertTrue((bool) $student3rd->needs_section_confirmation);

        // 4th year student remains 4th year
        $student4th->refresh();
        $this->assertEquals('4th', $student4th->year_level);
        $this->assertEquals('4-1', $student4th->section);
        $this->assertFalse((bool) $student4th->needs_section_confirmation);

        // Inactive student is not promoted
        $inactiveStudent->refresh();
        $this->assertEquals('1st', $inactiveStudent->year_level);
        $this->assertFalse((bool) $inactiveStudent->needs_section_confirmation);

        // Verify audit logs
        $logs = YearLevelAuditLog::where('action', 'academic_year_promotion')->get();
        $this->assertCount(3, $logs);
        $this->assertTrue($logs->contains('user_id', $student1st->id));
        $this->assertTrue($logs->contains('user_id', $student2nd->id));
        $this->assertTrue($logs->contains('user_id', $student3rd->id));
    }

    public function test_setting_academic_year_discards_unfinished_drafts(): void
    {
        $ayNext = AcademicYear::create(['label' => '2026-2027', 'is_current' => false]);

        $student = User::factory()->create([
            'role' => 'student',
            'program' => 'BSIT',
            'year_level' => '1st',
            'section' => '1-1',
            'is_active' => true,
            'needs_section_confirmation' => false,
        ]);

        $draft = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
            'submitted_at' => null,
        ]);

        $this->actingAs($this->admin)->patch(route('institution.academic-years.set-current', $ayNext));

        $this->assertDatabaseMissing('inventory_submissions', ['id' => $draft->id]);
    }

    public function test_promoted_student_is_prompted_to_change_section_and_blocked_until_confirmed(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2026-0001',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'program' => 'BSIT',
            'year_level' => '2nd',
            'section' => '',
            'needs_section_confirmation' => true,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Accessing dashboard redirects to section confirmation prompt
        $response = $this->actingAs($student)->get('/dashboard');
        $response->assertRedirect(route('student.section-confirmation.show'));

        // Accessing other protected student routes is also intercepted
        $this->actingAs($student)->get('/announcements')->assertRedirect(route('student.section-confirmation.show'));
        $this->actingAs($student)->get('/inventory')->assertRedirect(route('student.section-confirmation.show'));

        // Visiting section confirmation page renders prompt
        $pageResponse = $this->actingAs($student)->get(route('student.section-confirmation.show'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee("You've been promoted to", false);
        $pageResponse->assertSee('2nd Year');
        $pageResponse->assertSee('Select Your Section');
        $pageResponse->assertSee('BSIT 2-1');

        // Student can logout safely
        $logout = $this->actingAs($student)->post('/logout');
        $logout->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_submitting_invalid_section_is_rejected(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2026-0001',
            'program' => 'BSIT',
            'year_level' => '2nd',
            'section' => '',
            'needs_section_confirmation' => true,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Wrong section for 2nd year (e.g. 4-99)
        $response = $this->actingAs($student)->post(route('student.section-confirmation.store'), [
            'section' => '4-99',
        ]);

        $response->assertSessionHasErrors('section');

        $student->refresh();
        $this->assertEmpty($student->section);
        $this->assertTrue((bool) $student->needs_section_confirmation);
    }

    public function test_submitting_valid_section_updates_section_and_clears_prompt(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2026-0001',
            'program' => 'BSIT',
            'year_level' => '2nd',
            'section' => '',
            'needs_section_confirmation' => true,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($student)->post(route('student.section-confirmation.store'), [
            'section' => '2-1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertEquals('2-1', $student->section);
        $this->assertFalse((bool) $student->needs_section_confirmation);

        // Audit log created for student section confirmation
        $log = YearLevelAuditLog::where('user_id', $student->id)
            ->where('action', 'student_section_confirmation')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals('2-1', $log->new_section);

        // Student can now access dashboard without redirect
        $this->actingAs($student)->get('/dashboard')->assertStatus(200);

        // Visiting confirmation page redirects back to dashboard
        $this->actingAs($student)->get(route('student.section-confirmation.show'))->assertRedirect(route('dashboard'));
    }

    public function test_creating_academic_year_with_set_as_current_automatically_promotes_students(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'program' => 'BSIT',
            'year_level' => '1st',
            'section' => '1-1',
            'is_active' => true,
            'needs_section_confirmation' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('institution.academic-years.store'), [
            'label' => '2027-2028',
            'set_as_current' => '1',
        ]);

        $response->assertRedirect(route('institution.index'));

        $newAy = AcademicYear::where('label', '2027-2028')->first();
        $this->assertNotNull($newAy);
        $this->assertTrue((bool) $newAy->is_current);

        $student->refresh();
        $this->assertEquals('2nd', $student->year_level);
        $this->assertEmpty($student->section);
        $this->assertTrue((bool) $student->needs_section_confirmation);
    }

    public function test_setting_already_active_academic_year_does_not_re_promote(): void
    {
        $ay = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        $student = User::factory()->create([
            'role' => 'student',
            'program' => 'BSIT',
            'year_level' => '1st',
            'section' => '1-1',
            'is_active' => true,
            'needs_section_confirmation' => false,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('institution.academic-years.set-current', $ay));

        $response->assertRedirect(route('institution.index'));
        $response->assertSessionHas('info');

        $student->refresh();
        $this->assertEquals('1st', $student->year_level);
        $this->assertEquals('1-1', $student->section);
        $this->assertFalse((bool) $student->needs_section_confirmation);
    }

    public function test_counselor_cannot_set_academic_year(): void
    {
        $ay = AcademicYear::create(['label' => '2026-2027', 'is_current' => false]);

        $response = $this->actingAs($this->counselor)->patch(route('institution.academic-years.set-current', $ay));
        $response->assertStatus(403);
    }

    public function test_csv_promotion_routes_are_completely_removed(): void
    {
        $this->actingAs($this->admin)->get('/manage/promotions')->assertStatus(404);
        $this->actingAs($this->admin)->post('/manage/promotions/upload')->assertStatus(404);
        $this->actingAs($this->admin)->post('/manage/promotions/confirm')->assertStatus(404);
        $this->actingAs($this->admin)->post('/manage/promotions/clear')->assertStatus(404);
        $this->actingAs($this->admin)->get('/manage/promotions/sample-csv')->assertStatus(404);
        $this->actingAs($this->admin)->get('/manage/promotions/invalid-rows-csv')->assertStatus(404);
    }
}

