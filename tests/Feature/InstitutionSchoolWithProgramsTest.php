<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InstitutionSchoolWithProgramsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
        ]);
    }

    public function test_can_create_school_without_programs(): void
    {
        $response = $this->actingAs($this->admin)->post(route('institution.departments.store'), [
            'name' => 'School of Arts and Sciences',
        ]);

        $response->assertRedirect(route('institution.index'));
        $response->assertSessionHas('status', 'School created successfully.');

        $this->assertDatabaseHas('departments', [
            'name' => 'School of Arts and Sciences',
        ]);
    }

    public function test_can_create_school_with_multiple_programs_in_single_transaction(): void
    {
        $payload = [
            'name' => 'School of Engineering and Technology',
            'programs' => [
                ['name' => 'Bachelor of Science in Civil Engineering', 'code' => 'BSCE'],
                ['name' => 'Bachelor of Science in Electrical Engineering', 'code' => 'BSEE'],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('institution.departments.store'), $payload);

        $response->assertRedirect(route('institution.index'));
        $response->assertSessionHas('status', 'School created successfully.');

        $school = Department::where('name', 'School of Engineering and Technology')->first();
        $this->assertNotNull($school);

        $this->assertDatabaseHas('programs', [
            'department_id' => $school->id,
            'name' => 'Bachelor of Science in Civil Engineering',
            'code' => 'BSCE',
        ]);

        $this->assertDatabaseHas('programs', [
            'department_id' => $school->id,
            'name' => 'Bachelor of Science in Electrical Engineering',
            'code' => 'BSEE',
        ]);
    }

    public function test_duplicate_program_code_in_request_fails_validation_and_rolls_back(): void
    {
        $payload = [
            'name' => 'School of Health Sciences',
            'programs' => [
                ['name' => 'BS Nursing', 'code' => 'BSN'],
                ['name' => 'BS Nutrition', 'code' => 'BSN'], // duplicate code in same request
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('institution.departments.store'), $payload);

        $response->assertSessionHasErrors(['programs.0.code', 'programs.1.code']);

        // Entire school creation rolled back
        $this->assertDatabaseMissing('departments', [
            'name' => 'School of Health Sciences',
        ]);
        $this->assertDatabaseMissing('programs', [
            'code' => 'BSN',
        ]);
    }

    public function test_duplicate_program_code_existing_in_database_fails_validation_and_rolls_back(): void
    {
        $existingSchool = Department::create(['name' => 'Existing School']);
        Program::create([
            'department_id' => $existingSchool->id,
            'name' => 'Existing BSIT',
            'code' => 'BSIT',
        ]);

        $payload = [
            'name' => 'New Computing School',
            'programs' => [
                ['name' => 'Information Technology', 'code' => 'BSIT'], // already taken
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('institution.departments.store'), $payload);

        $response->assertSessionHasErrors(['programs.0.code']);

        $this->assertDatabaseMissing('departments', [
            'name' => 'New Computing School',
        ]);
    }

    public function test_cache_keys_are_cleared_after_school_and_programs_created(): void
    {
        Cache::put('filter_departments', 'cached_data', 3600);
        Cache::put('filter_departments_with_programs', 'cached_data', 3600);
        Cache::put('filter_programs', 'cached_data', 3600);

        $this->actingAs($this->admin)->post(route('institution.departments.store'), [
            'name' => 'School of Business',
            'programs' => [
                ['name' => 'BS Accountancy', 'code' => 'BSA'],
            ],
        ]);

        $this->assertNull(Cache::get('filter_departments'));
        $this->assertNull(Cache::get('filter_departments_with_programs'));
        $this->assertNull(Cache::get('filter_programs'));
    }

    public function test_cannot_archive_school_with_active_schedule(): void
    {
        $school = Department::create(['name' => 'School of Education']);
        $program = Program::create([
            'department_id' => $school->id,
            'name' => 'Bachelor of Secondary Education',
            'code' => 'BSED',
        ]);
        $ay = \App\Models\AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);
        \App\Models\AssessmentSchedule::create([
            'academic_year_id' => $ay->id,
            'program_id' => $program->id,
            'year_level' => '1st',
            'open_date' => now()->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->addDays(5)->format('Y-m-d'),
            'close_time' => '17:00',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('institution.departments.destroy', $school->id));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('departments', ['id' => $school->id]);
        $this->assertNull($school->fresh()->archived_at);
    }
}

