<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InstitutionDomain;
use App\Models\Program;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationAgeAndGenderTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        InstitutionDomain::firstOrCreate(
            ['domain' => 'citycollegeoftagaytay.edu.ph'],
            ['is_active' => true]
        );

        $this->department = Department::create([
            'name' => 'School of Computer Studies',
            'code' => 'SCS',
        ]);

        $this->program = Program::create([
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
            'department_id' => $this->department->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Juan',
            'middle_initial' => 'D',
            'last_name' => 'Dela Cruz',
            'birthdate' => '2004-05-10',
            'gender' => 'Male',
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'section' => '1-1',
            'contact_number' => '09171234567',
            'address_line1' => '123 Kaybagal South',
            'city' => 'Tagaytay',
            'province' => 'Cavite',
            'email' => 'juan.delacruz@citycollegeoftagaytay.edu.ph',
            'student_id' => '2026-9001',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ], $overrides);
    }

    public function test_registration_requires_gender_and_rejects_invalid_values(): void
    {
        $missingGender = $this->post('/register', $this->validPayload(['gender' => '']));
        $missingGender->assertSessionHasErrors('gender');

        $invalidGender = $this->post('/register', $this->validPayload([
            'email' => 'juan2@citycollegeoftagaytay.edu.ph',
            'student_id' => '2026-9002',
            'gender' => 'InvalidOption',
        ]));
        $invalidGender->assertSessionHasErrors('gender');
    }

    public function test_registration_persists_valid_gender_without_storing_age_column(): void
    {
        $response = $this->post('/register', $this->validPayload([
            'gender' => 'Prefer not to say',
        ]));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertFalse(Schema::hasColumn('users', 'age'));

        $user = User::where('email', 'juan.delacruz@citycollegeoftagaytay.edu.ph')->firstOrFail();
        $this->assertSame('Prefer not to say', $user->gender);
    }

    public function test_user_age_accessor_computes_exact_age_across_birthday_boundary(): void
    {
        $student = User::factory()->make([
            'birthdate' => '2004-05-10',
        ]);

        Carbon::setTestNow('2026-05-09 23:59:59');
        $this->assertSame(21, $student->age);

        Carbon::setTestNow('2026-05-10 00:00:01');
        $this->assertSame(22, $student->age);

        $noDob = User::factory()->make(['birthdate' => null]);
        $this->assertNull($noDob->age);
    }

    public function test_registration_validates_birthdate_range(): void
    {
        $today = $this->post('/register', $this->validPayload([
            'birthdate' => now()->toDateString(),
            'email' => 'today@citycollegeoftagaytay.edu.ph',
            'student_id' => '2026-9003',
        ]));
        $today->assertSessionHasErrors('birthdate');

        $tooOld = $this->post('/register', $this->validPayload([
            'birthdate' => now()->subYears(105)->toDateString(),
            'email' => 'old@citycollegeoftagaytay.edu.ph',
            'student_id' => '2026-9004',
        ]));
        $tooOld->assertSessionHasErrors('birthdate');
    }

    public function test_student_profile_allows_setting_gender_once_and_locks_it_afterward(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'gender' => null,
            'birthdate' => '2005-03-15',
            'program_id' => $this->program->id,
            'program' => 'BSIT',
        ]);

        $this->actingAs($student)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Not set')
            ->assertSee('<select name="gender"', false);

        $this->actingAs($student)
            ->patch(route('profile.update'), [
                'contact_number' => '09181112222',
                'address_line1' => '456 Silang St',
                'city' => 'Tagaytay',
                'province' => 'Cavite',
                'gender' => 'Female',
            ])
            ->assertRedirect(route('profile.edit'));

        $student->refresh();
        $this->assertSame('Female', $student->gender);

        $this->actingAs($student)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Female')
            ->assertDontSee('<select name="gender"', false);

        // Second PATCH cannot overwrite an already-saved gender
        $this->actingAs($student)
            ->patch(route('profile.update'), [
                'contact_number' => '09181112222',
                'address_line1' => '456 Silang St',
                'city' => 'Tagaytay',
                'province' => 'Cavite',
                'gender' => 'Male',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('Female', $student->fresh()->gender);
    }

    public function test_staff_student_profile_displays_age_gender_and_not_set_fallback(): void
    {
        Carbon::setTestNow('2026-11-27 12:00:00');

        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $this->department->id,
            'program_id' => null,
            'program' => 'N/A',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $withGender = User::factory()->create([
            'role' => 'student',
            'birthdate' => '2005-01-20',
            'gender' => 'Male',
            'department_id' => $this->department->id,
            'program_id' => $this->program->id,
            'program' => 'BSIT',
        ]);

        $withoutGender = User::factory()->create([
            'role' => 'student',
            'birthdate' => '2006-02-14',
            'gender' => null,
            'department_id' => $this->department->id,
            'program_id' => $this->program->id,
            'program' => 'BSIT',
        ]);

        $this->actingAs($counselor)
            ->get(route('staff.students.show', $withGender))
            ->assertOk()
            ->assertSee('21 years old')
            ->assertSee('Male');

        $this->actingAs($counselor)
            ->get(route('staff.students.show', $withoutGender))
            ->assertOk()
            ->assertSee('Not set');
    }
}
