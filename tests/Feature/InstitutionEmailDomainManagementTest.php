<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InstitutionDomain;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class InstitutionEmailDomainManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createAcademicScope(): array
    {
        $dept = Department::create(['name' => 'College of Computer Studies']);
        $prog = Program::create([
            'department_id' => $dept->id,
            'name' => 'Bachelor of Science in Computer Science',
            'code' => 'BSCS',
        ]);

        return [$dept, $prog];
    }

    public function test_registration_accepts_seeded_institution_domain(): void
    {
        [$dept, $prog] = $this->createAcademicScope();

        $response = $this->post('/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birthdate' => '2004-01-01',
            'program_id' => $prog->id,
            'section' => '1-1',
            'contact_number' => '09171234567',
            'address_line1' => 'Tagaytay City',
            'city' => 'Tagaytay',
            'province' => 'Cavite',
            'student_id' => '2026-0001',
            'email' => 'juan.delacruz@citycollegeoftagaytay.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'year_level' => '1st',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'juan.delacruz@citycollegeoftagaytay.edu.ph',
        ]);
    }

    public function test_registration_rejects_unlisted_domain_with_exact_message(): void
    {
        [$dept, $prog] = $this->createAcademicScope();

        $response = $this->post('/register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'birthdate' => '2004-01-01',
            'program_id' => $prog->id,
            'section' => '1-1',
            'contact_number' => '09171234567',
            'address_line1' => 'Tagaytay City',
            'city' => 'Tagaytay',
            'province' => 'Cavite',
            'student_id' => '2026-0002',
            'email' => 'jane.doe@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'year_level' => '1st',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'This email domain is not allowed. Please use your institution email.',
        ]);
        $this->assertGuest();
    }

    public function test_admin_can_add_new_domain_and_user_can_register_immediately(): void
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email' => 'admin@citycollegeoftagaytay.edu.ph',
        ]);

        // Post with uppercase and leading @ to test normalization
        $addResponse = $this->actingAs($admin)->post(route('institution.domains.store'), [
            'domain' => '@Tagaytay.EDU.PH',
            'institution_name' => 'Tagaytay City Campus',
            'is_active' => '1',
        ]);

        $addResponse->assertRedirect(route('institution.index'));
        $this->assertDatabaseHas('institution_domains', [
            'domain' => 'tagaytay.edu.ph',
            'institution_name' => 'Tagaytay City Campus',
            'is_active' => true,
        ]);

        // Immediately register a student with this new domain without restart
        $this->post('/logout');

        [$dept, $prog] = $this->createAcademicScope();

        $regResponse = $this->post('/register', [
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'birthdate' => '2004-05-12',
            'program_id' => $prog->id,
            'section' => '1-2',
            'contact_number' => '09181234567',
            'address_line1' => 'Tagaytay City',
            'city' => 'Tagaytay',
            'province' => 'Cavite',
            'student_id' => '2026-0003',
            'email' => 'maria.clara@tagaytay.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'year_level' => '1st',
        ]);

        $regResponse->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'maria.clara@tagaytay.edu.ph',
        ]);
    }

    public function test_deactivated_domain_blocks_new_registrations(): void
    {
        $domain = InstitutionDomain::create([
            'domain' => 'satellite.edu.ph',
            'institution_name' => 'Satellite Campus',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email' => 'admin@citycollegeoftagaytay.edu.ph',
        ]);

        // Deactivate via toggle
        $toggleResponse = $this->actingAs($admin)->patch(route('institution.domains.toggle', $domain));
        $toggleResponse->assertRedirect(route('institution.index'));
        $this->assertFalse($domain->fresh()->is_active);

        // Attempt registration
        $this->post('/logout');
        [$dept, $prog] = $this->createAcademicScope();

        $regResponse = $this->post('/register', [
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
            'birthdate' => '2004-03-10',
            'program_id' => $prog->id,
            'section' => '1-1',
            'contact_number' => '09191234567',
            'address_line1' => 'Tagaytay City',
            'city' => 'Tagaytay',
            'province' => 'Cavite',
            'student_id' => '2026-0004',
            'email' => 'pedro@satellite.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'year_level' => '1st',
        ]);

        $regResponse->assertSessionHasErrors([
            'email' => 'This email domain is not allowed. Please use your institution email.',
        ]);
        $this->assertGuest();
    }

    public function test_deleted_domain_blocks_new_registrations(): void
    {
        $domain = InstitutionDomain::create([
            'domain' => 'temp-branch.edu.ph',
            'institution_name' => 'Temporary Branch',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email' => 'admin@citycollegeoftagaytay.edu.ph',
        ]);

        $delResponse = $this->actingAs($admin)->delete(route('institution.domains.destroy', $domain));
        $delResponse->assertRedirect(route('institution.index'));
        $this->assertDatabaseMissing('institution_domains', [
            'domain' => 'temp-branch.edu.ph',
        ]);

        // Attempt registration
        $this->post('/logout');
        [$dept, $prog] = $this->createAcademicScope();

        $regResponse = $this->post('/register', [
            'first_name' => 'Jose',
            'last_name' => 'Rizal',
            'birthdate' => '2004-06-19',
            'program_id' => $prog->id,
            'section' => '1-1',
            'contact_number' => '09191234568',
            'address_line1' => 'Calamba',
            'city' => 'Tagaytay',
            'province' => 'Cavite',
            'student_id' => '2026-0005',
            'email' => 'jose@temp-branch.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'year_level' => '1st',
        ]);

        $regResponse->assertSessionHasErrors([
            'email' => 'This email domain is not allowed. Please use your institution email.',
        ]);
        $this->assertGuest();
    }

    public function test_non_admin_cannot_manage_domains(): void
    {
        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'email' => 'counselor@citycollegeoftagaytay.edu.ph',
        ]);

        $response = $this->actingAs($counselor)->post(route('institution.domains.store'), [
            'domain' => 'unauthorized.edu.ph',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_staff_account_creation_enforces_domain_rule(): void
    {
        $admin = User::factory()->create([
            'role' => 'system_admin',
            'email' => 'admin@citycollegeoftagaytay.edu.ph',
        ]);

        $dept = Department::create(['name' => 'School of Education']);

        // Attempt creation with unauthorized domain
        $failResponse = $this->actingAs($admin)->post(route('manage.accounts.store'), [
            'first_name' => 'Sarah',
            'last_name' => 'Counselor',
            'email' => 'sarah@yahoo.com',
            'department_ids' => [$dept->id],
        ]);

        $failResponse->assertSessionHasErrors([
            'email' => 'This email domain is not allowed. Please use your institution email.',
        ]);

        // Attempt creation with approved domain
        $successResponse = $this->actingAs($admin)->post(route('manage.accounts.store'), [
            'first_name' => 'Sarah',
            'last_name' => 'Counselor',
            'email' => 'sarah.counselor@citycollegeoftagaytay.edu.ph',
            'department_ids' => [$dept->id],
        ]);

        $successResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'email' => 'sarah.counselor@citycollegeoftagaytay.edu.ph',
            'role' => 'guidance_counselor',
        ]);
    }

    public function test_password_reset_handles_transport_exception_gracefully_without_500(): void
    {
        $user = User::factory()->create([
            'email' => 'test.student@citycollegeoftagaytay.edu.ph',
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Password reset email failed');
            });

        // Mock Password facade sendResetLink to throw mail TransportException
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andThrow(new TransportException('Connection could not be established with host smtp.gmail.com:587'));

        $response = $this->post('/forgot-password', [
            'email' => 'test.student@citycollegeoftagaytay.edu.ph',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => "We couldn't send the email right now. Please try again later.",
        ]);
    }

    public function test_password_reset_does_not_reveal_user_existence(): void
    {
        // Request password reset for non-existent email
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent.user@citycollegeoftagaytay.edu.ph',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('status', __(Password::RESET_LINK_SENT));
        $response->assertSessionHasNoErrors();
    }
}

