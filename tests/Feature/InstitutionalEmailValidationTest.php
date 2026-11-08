<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionalEmailValidationTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicYear $academicYear;
    protected Department $department;
    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'label' => '2026-2027',
            'is_current' => true,
        ]);

        $this->department = Department::create([
            'name' => 'School of Computer Studies',
        ]);

        $this->program = Program::create([
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
            'department_id' => $this->department->id,
        ]);
    }

    public function test_registration_rejects_non_institutional_email()
    {
        $response = $this->post(route('register'), [
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'jane.student@gmail.com',
            'student_id' => '2026-0001',
            'birthdate' => '2004-01-15',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'year_level' => '1st',
            'section' => '1-A',
            'contact_number' => '09123456789',
            'address_line1' => '123 Tagaytay St',
            'city' => 'Tagaytay',
            'province' => 'Cavite',
            'program_id' => $this->program->id,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'jane.student@gmail.com']);
    }

    public function test_registration_accepts_valid_institutional_email()
    {
        $response = $this->post(route('register'), [
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'jane.student@citycollegeoftagaytay.edu.ph',
            'student_id' => '2026-0002',
            'birthdate' => '2004-01-15',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'year_level' => '1st',
            'section' => '1-A',
            'contact_number' => '09123456789',
            'address_line1' => '123 Tagaytay St',
            'city' => 'Tagaytay',
            'province' => 'Cavite',
            'program_id' => $this->program->id,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('users', ['email' => 'jane.student@citycollegeoftagaytay.edu.ph']);
    }

    public function test_account_creation_rejects_non_institutional_email()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email' => 'admin@citycollegeoftagaytay.edu.ph',
        ]);

        $response = $this->actingAs($admin)->post(route('manage.accounts.store'), [
            'first_name' => 'Counselor',
            'last_name' => 'Test',
            'email' => 'counselor@yahoo.com',
            'department_id' => $this->department->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'counselor@yahoo.com']);
    }

    public function test_account_creation_accepts_institutional_email()
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email' => 'admin@citycollegeoftagaytay.edu.ph',
        ]);

        $response = $this->actingAs($admin)->post(route('manage.accounts.store'), [
            'first_name' => 'Counselor',
            'last_name' => 'Valid',
            'email' => 'counselor.valid@citycollegeoftagaytay.edu.ph',
            'department_id' => $this->department->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('manage.accounts.index'));
        $this->assertDatabaseHas('users', ['email' => 'counselor.valid@citycollegeoftagaytay.edu.ph']);
    }
}
