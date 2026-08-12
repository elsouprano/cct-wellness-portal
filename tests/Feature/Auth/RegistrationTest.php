<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->withoutExceptionHandling();
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
            'email' => 'test@citycollegeoftagaytay.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'year_level' => '1st',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
    public function test_new_users_cannot_escalate_privileges(): void
    {
        $departmentId = \Illuminate\Support\Facades\DB::table('departments')->insertGetId(['name' => 'CS']);
        $programId = \Illuminate\Support\Facades\DB::table('programs')->insertGetId(['department_id' => $departmentId, 'name' => 'BSCS', 'code' => 'BSCS']);
        
        $response = $this->post('/register', [
            'first_name' => 'Hacker',
            'last_name' => 'Man',
            'birthdate' => '2000-01-01',
            'program_id' => $programId,
            'section' => '1-1',
            'contact_number' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Manila',
            'province' => 'NCR',
            'student_id' => '2023-9999',
            'email' => 'hacker@citycollegeoftagaytay.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'year_level' => '1st',
            'role' => 'admin',
            'is_paying_student' => true,
            'year_level_confirmed' => false,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $user = \App\Models\User::where('email', 'hacker@citycollegeoftagaytay.edu.ph')->first();
        
        $this->assertEquals('student', $user->role);
        $this->assertFalse((bool)$user->is_paying_student);
        $this->assertTrue((bool)$user->year_level_confirmed);
    }

    public function test_registration_fails_with_invalid_program(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'birthdate' => '2000-01-01',
            'program_id' => 99999, // Non-existent
            'section' => '1-1',
            'contact_number' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Manila',
            'province' => 'NCR',
            'student_id' => '2023-1111',
            'email' => 'test2@citycollegeoftagaytay.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'year_level' => '1st',
        ]);
        $response->assertSessionHasErrors('program_id');
    }

    public function test_registration_fails_with_invalid_year_level(): void
    {
        $departmentId = \Illuminate\Support\Facades\DB::table('departments')->insertGetId(['name' => 'IT']);
        $programId = \Illuminate\Support\Facades\DB::table('programs')->insertGetId(['department_id' => $departmentId, 'name' => 'BSIT', 'code' => 'BSIT']);
        
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'birthdate' => '2000-01-01',
            'program_id' => $programId,
            'section' => '1-1',
            'contact_number' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Manila',
            'province' => 'NCR',
            'student_id' => '2023-2222',
            'email' => 'test3@citycollegeoftagaytay.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'year_level' => 'graduate', // Invalid
        ]);
        $response->assertSessionHasErrors('year_level');
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        \App\Models\User::factory()->create(['email' => 'existing@citycollegeoftagaytay.edu.ph']);
        
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'birthdate' => '2000-01-01',
            'program_id' => 1,
            'section' => '1-1',
            'contact_number' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Manila',
            'province' => 'NCR',
            'student_id' => '2023-3333',
            'email' => 'existing@citycollegeoftagaytay.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'year_level' => '1st',
        ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_registration_fails_with_duplicate_student_id(): void
    {
        \App\Models\User::factory()->create(['student_id' => '2023-4444']);
        
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'birthdate' => '2000-01-01',
            'program_id' => 1,
            'section' => '1-1',
            'contact_number' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Manila',
            'province' => 'NCR',
            'student_id' => '2023-4444',
            'email' => 'test5@citycollegeoftagaytay.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'year_level' => '1st',
        ]);
        $response->assertSessionHasErrors('student_id');
    }
}
