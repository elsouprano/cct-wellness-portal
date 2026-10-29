<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use App\Support\InstitutionData;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InstitutionDataUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_data_defines_ay_2026_2027_schools_and_programs(): void
    {
        $departments = InstitutionData::canonicalDepartments();

        $this->assertArrayHasKey('School of Education', $departments);
        $this->assertArrayHasKey('School of Arts and Sciences', $departments);
        $this->assertArrayHasKey('School of Computer Studies', $departments);
        $this->assertArrayHasKey('School of Hospitality and Tourism Management', $departments);
        $this->assertArrayHasKey('School of Business and Management', $departments);

        $this->assertCount(5, $departments['School of Education']);
        $this->assertCount(3, $departments['School of Arts and Sciences']);
        $this->assertCount(2, $departments['School of Computer Studies']);
        $this->assertCount(2, $departments['School of Hospitality and Tourism Management']);
        $this->assertCount(3, $departments['School of Business and Management']);
    }

    public function test_department_seeder_is_idempotent_and_seeds_canonical_programs(): void
    {
        $this->seed(DepartmentSeeder::class);

        $deptCount = Department::count();
        $progCount = Program::count();

        $this->assertEquals(5, $deptCount);
        $this->assertEquals(15, $progCount);

        // Run seeder a second time
        $this->seed(DepartmentSeeder::class);

        $this->assertEquals($deptCount, Department::count(), 'Running seeder twice must not duplicate departments');
        $this->assertEquals($progCount, Program::count(), 'Running seeder twice must not duplicate programs');

        // Confirm hyphenated codes exist
        $this->assertNotNull(Program::where('code', 'BSBA-HRDM')->first());
        $this->assertNotNull(Program::where('code', 'BSBA-MM')->first());
        $this->assertNotNull(Program::where('code', 'BSED-ENG')->first());
        $this->assertNotNull(Program::where('code', 'BSESS-FSM')->first());
    }

    public function test_user_factory_resolves_valid_program_from_database(): void
    {
        $this->seed(DepartmentSeeder::class);

        $student = User::factory()->create(['role' => 'student']);

        $this->assertNotNull($student->program_id);
        $this->assertNotNull($student->program);

        $program = Program::find($student->program_id);
        $this->assertEquals($program->code, $student->program);
    }
}

