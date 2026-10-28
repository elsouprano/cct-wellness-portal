<?php

namespace Tests\Feature;

use App\Models\InventoryResponse;
use App\Models\InventorySubmission;
use App\Models\User;
use App\Models\YearLevelAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class YearLevelManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_year_level_and_logs()
    {
        $departmentId = DB::table('departments')->insertGetId(['name' => 'CS']);
        $programId = DB::table('programs')->insertGetId(['department_id' => $departmentId, 'name' => 'BSCS', 'code' => 'BSCS']);
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => '2000-01-01',
            'program_id' => $programId,
            'section' => '1-1',
            'contact_number' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Manila',
            'province' => 'NCR',
            'student_id' => '2023-0001',
            'email' => 'john.doe@citycollegeoftagaytay.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'year_level' => '1st',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'john.doe@citycollegeoftagaytay.edu.ph')->first();
        $this->assertEquals('1st', $user->year_level);
        $this->assertTrue((bool) $user->year_level_confirmed);

        $log = YearLevelAuditLog::where('user_id', $user->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('registration', $log->action);
        $this->assertEquals('1st', $log->new_year_level);
    }

    public function test_bulk_promote_advances_levels_and_updates_sections()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student1 = User::factory()->create(['role' => 'student', 'year_level' => '1st', 'section' => '1-1', 'year_level_confirmed' => false]);
        $student2 = User::factory()->create(['role' => 'student', 'year_level' => '2nd', 'section' => '2-A', 'year_level_confirmed' => true]);
        $student3 = User::factory()->create(['role' => 'student', 'year_level' => '3rd', 'section' => '3 B', 'year_level_confirmed' => true]);
        $student4 = User::factory()->create(['role' => 'student', 'year_level' => '4th', 'section' => '4-1', 'year_level_confirmed' => true]);

        $response = $this->actingAs($admin)->post('/manage/year-levels/bulk-promote');

        $response->assertRedirect('/manage/year-levels');

        $student1->refresh();
        $student2->refresh();
        $student3->refresh();
        $student4->refresh();

        $this->assertEquals('2nd', $student1->year_level);
        $this->assertEquals('2-1', $student1->section);
        $this->assertTrue((bool) $student1->year_level_confirmed);

        $this->assertEquals('3rd', $student2->year_level);
        $this->assertEquals('3-A', $student2->section);

        $this->assertEquals('4th', $student3->year_level);
        $this->assertEquals('4 B', $student3->section);

        $this->assertEquals('4th', $student4->year_level);
        $this->assertEquals('4-1', $student4->section); // Ignored

        $logs = YearLevelAuditLog::all();
        $this->assertCount(3, $logs); // student1, student2, student3 promoted
    }

    public function test_individual_override_updates_section()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student = User::factory()->create(['role' => 'student', 'year_level' => '3rd', 'section' => '3-1', 'year_level_confirmed' => false]);

        $response = $this->actingAs($admin)->post("/manage/year-levels/{$student->id}/override", [
            'year_level' => '4th',
        ]);

        $response->assertRedirect();

        $student->refresh();
        $this->assertEquals('4th', $student->year_level);
        $this->assertEquals('4-1', $student->section);
        $this->assertTrue((bool) $student->year_level_confirmed);

        $log = YearLevelAuditLog::where('user_id', $student->id)->first();
        $this->assertEquals('individual_override', $log->action);
        $this->assertEquals('4th', $log->new_year_level);
        $this->assertEquals('3rd', $log->old_year_level);
        $this->assertEquals('4-1', $log->new_section);
        $this->assertEquals('3-1', $log->old_section);
        $this->assertEquals($admin->id, $log->actor_id);
    }

    public function test_individual_override_with_invalid_section_warns()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student = User::factory()->create(['role' => 'student', 'year_level' => '1st', 'section' => '1A', 'year_level_confirmed' => false]);

        $response = $this->actingAs($admin)->post("/manage/year-levels/{$student->id}/override", [
            'year_level' => '2nd',
        ]);

        $response->assertSessionHas('warning');

        $student->refresh();
        $this->assertEquals('2nd', $student->year_level);
        $this->assertEquals('1A', $student->section);
    }

    public function test_rollback_on_audit_insert_failure()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student = User::factory()->create(['role' => 'student', 'year_level' => '1st', 'section' => '1-1', 'year_level_confirmed' => false]);

        // Simulate DB failure by dropping the table
        Schema::drop('year_level_audit_logs');

        try {
            $this->actingAs($admin)->post("/manage/year-levels/{$student->id}/override", [
                'year_level' => '2nd',
            ]);
        } catch (\Exception $e) {
            // expected
        }

        $student->refresh();
        $this->assertEquals('1st', $student->year_level);
        $this->assertEquals('1-1', $student->section);
        $this->assertFalse((bool) $student->year_level_confirmed);
    }

    public function test_set_saves_and_new_year_shows_everywhere()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => '1st',
            'section' => '1-1',
            'year_level_confirmed' => true,
        ]);

        // 1. Admin sets year level to 3rd
        $response = $this->actingAs($admin)->post("/manage/year-levels/{$student->id}/override", [
            'year_level' => '3rd',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertEquals('3rd', $student->year_level);
        $this->assertEquals('3-1', $student->section);

        // 2. Admin Year Level list shows 3rd
        $listResponse = $this->actingAs($admin)->get('/manage/year-levels');
        $listResponse->assertOk();
        $listResponse->assertSee('3rd');

        // 3. Admin Student Profile shows 3rd
        $profileAdminResponse = $this->actingAs($admin)->get("/manage/students/{$student->id}");
        $profileAdminResponse->assertOk();
        $profileAdminResponse->assertSee('Year Level');
        $profileAdminResponse->assertSee('3rd');

        // 4. Student Official Records (/profile) shows 3rd
        $studentProfileResponse = $this->actingAs($student)->get('/profile');
        $studentProfileResponse->assertOk();
        $studentProfileResponse->assertSee('Official Records');
        $studentProfileResponse->assertSee('3rd');

        // 5. Student Dashboard (/dashboard) shows 3rd
        $dashboardResponse = $this->actingAs($student)->get('/dashboard');
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('3rd');
    }

    public function test_invalid_year_value_is_rejected()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => '2nd',
            'section' => '2-1',
        ]);

        $response = $this->actingAs($admin)->post("/manage/year-levels/{$student->id}/override", [
            'year_level' => '5th',
        ]);

        $response->assertSessionHasErrors('year_level');

        $student->refresh();
        $this->assertEquals('2nd', $student->year_level);
    }

    public function test_non_admin_is_blocked_from_setting_year_level()
    {
        $counselor = User::factory()->create(['role' => 'guidance_counselor']);
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => '1st',
            'section' => '1-1',
        ]);
        $otherStudent = User::factory()->create([
            'role' => 'student',
            'year_level' => '1st',
            'section' => '1-2',
        ]);

        // Counselor is blocked
        $counselorResponse = $this->actingAs($counselor)->post("/manage/year-levels/{$student->id}/override", [
            'year_level' => '2nd',
        ]);
        $counselorResponse->assertForbidden();

        // Another student is blocked
        $studentResponse = $this->actingAs($otherStudent)->post("/manage/year-levels/{$student->id}/override", [
            'year_level' => '2nd',
        ]);
        $studentResponse->assertForbidden();

        $student->refresh();
        $this->assertEquals('1st', $student->year_level);
    }

    public function test_past_submission_snapshots_are_unchanged_but_unfinished_drafts_discarded()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => '1st',
            'section' => '1-1',
        ]);

        // Completed past submission
        $pastSubmission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => '2025-2026',
            'started_at' => now()->subMonths(6),
            'submitted_at' => now()->subMonths(6),
            'consent_given_at' => now()->subMonths(6),
            'consent_version' => '1.0',
            'signature_type' => 'typed',
            'signature_data' => 'John Doe',
        ]);
        $pastResponse = InventoryResponse::create([
            'inventory_submission_id' => $pastSubmission->id,
            'category' => 'DASS-21',
            'item_number' => 1,
            'response_value' => '2',
        ]);

        // Unfinished draft submission
        $draftSubmission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => '2026-2027',
            'started_at' => now(),
            'submitted_at' => null,
            'consent_given_at' => now(),
            'consent_version' => '1.0',
            'signature_type' => 'typed',
            'signature_data' => 'John Doe',
        ]);
        $draftResponse = InventoryResponse::create([
            'inventory_submission_id' => $draftSubmission->id,
            'category' => 'DASS-21',
            'item_number' => 1,
            'response_value' => '3',
        ]);

        // Admin changes year level to 2nd
        $this->actingAs($admin)->post("/manage/year-levels/{$student->id}/override", [
            'year_level' => '2nd',
        ]);

        // Past submission MUST be intact
        $this->assertDatabaseHas('inventory_submissions', [
            'id' => $pastSubmission->id,
            'academic_year' => '2025-2026',
        ]);
        $this->assertDatabaseHas('inventory_responses', [
            'id' => $pastResponse->id,
            'response_value' => '2',
        ]);

        // Draft submission MUST be discarded
        $this->assertDatabaseMissing('inventory_submissions', [
            'id' => $draftSubmission->id,
        ]);
        $this->assertDatabaseMissing('inventory_responses', [
            'id' => $draftResponse->id,
        ]);
    }

    public function test_set_saves_via_ajax_returns_json_and_leaves_unmatched_section()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => '1st',
            'section' => '3B',
        ]);

        $response = $this->actingAs($admin)->postJson("/manage/year-levels/{$student->id}/override", [
            'year_level' => '2nd',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'year_level' => '2nd',
            'section' => '3B',
            'section_matches' => false,
        ]);

        $student->refresh();
        $this->assertEquals('2nd', $student->year_level);
        $this->assertEquals('3B', $student->section);
    }
}
