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
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => '2000-01-01',
            'program' => 'BSCS',
            'section' => '1-1',
            'contact_number' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Manila',
            'province' => 'NCR',
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

    public function test_bulk_promote_advances_levels_and_ignores_4th_years()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student1 = User::factory()->create(['role' => 'student', 'year_level' => '1st', 'year_level_confirmed' => false]);
        $student3 = User::factory()->create(['role' => 'student', 'year_level' => '3rd', 'year_level_confirmed' => true]);
        $student4 = User::factory()->create(['role' => 'student', 'year_level' => '4th', 'year_level_confirmed' => true]);

        $response = $this->actingAs($admin)->post('/manage/year-levels/bulk-promote');

        $response->assertRedirect('/manage/year-levels');

        $student1->refresh();
        $student3->refresh();
        $student4->refresh();

        $this->assertEquals('2nd', $student1->year_level);
        $this->assertTrue((bool)$student1->year_level_confirmed);

        $this->assertEquals('4th', $student3->year_level);
        $this->assertEquals('4th', $student4->year_level); // Ignored

        $logs = YearLevelAuditLog::all();
        $this->assertCount(2, $logs); // Only student1 and student3 were promoted
    }

    public function test_individual_override()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student = User::factory()->create(['role' => 'student', 'year_level' => '2nd', 'year_level_confirmed' => false]);

        $response = $this->actingAs($admin)->post("/manage/year-levels/{$student->id}/override", [
            'year_level' => '3rd'
        ]);

        $response->assertRedirect();

        $student->refresh();
        $this->assertEquals('3rd', $student->year_level);
        $this->assertTrue((bool)$student->year_level_confirmed);

        $log = YearLevelAuditLog::where('user_id', $student->id)->first();
        $this->assertEquals('individual_override', $log->action);
        $this->assertEquals('3rd', $log->new_year_level);
        $this->assertEquals('2nd', $log->old_year_level);
        $this->assertEquals($admin->id, $log->actor_id);
    }
}
