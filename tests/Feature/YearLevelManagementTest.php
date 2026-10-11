<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\YearLevelAuditLog;

class YearLevelManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_year_level_and_logs()
    {
        $departmentId = \Illuminate\Support\Facades\DB::table('departments')->insertGetId(['name' => 'CS']);
        $programId = \Illuminate\Support\Facades\DB::table('programs')->insertGetId(['department_id' => $departmentId, 'name' => 'BSCS', 'code' => 'BSCS']);
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
        $this->assertTrue((bool)$user->year_level_confirmed);

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
        $this->assertTrue((bool)$student1->year_level_confirmed);

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
            'year_level' => '4th'
        ]);

        $response->assertRedirect();

        $student->refresh();
        $this->assertEquals('4th', $student->year_level);
        $this->assertEquals('4-1', $student->section);
        $this->assertTrue((bool)$student->year_level_confirmed);

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
            'year_level' => '2nd'
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
        \Illuminate\Support\Facades\Schema::drop('year_level_audit_logs');

        try {
            $this->actingAs($admin)->post("/manage/year-levels/{$student->id}/override", [
                'year_level' => '2nd'
            ]);
        } catch (\Exception $e) {
            // expected
        }

        $student->refresh();
        $this->assertEquals('1st', $student->year_level);
        $this->assertEquals('1-1', $student->section);
        $this->assertFalse((bool)$student->year_level_confirmed);
    }
}
