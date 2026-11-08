<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfilesRedesignToolbarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Department $dept;
    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->dept = Department::create([
            'name' => 'School of Computer Studies',
        ]);

        $this->program = Program::create([
            'department_id' => $this->dept->id,
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
        ]);
    }

    public function test_student_profiles_renders_2_row_toolbar_and_clean_section_dash(): void
    {
        // Student with section
        User::factory()->create([
            'role' => 'student',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'student_id' => '2024-00001',
            'department_id' => $this->dept->id,
            'program_id' => $this->program->id,
            'section' => '4-1',
            'is_active' => true,
        ]);

        // Student without section
        User::factory()->create([
            'role' => 'student',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'student_id' => '2024-00002',
            'department_id' => $this->dept->id,
            'program_id' => $this->program->id,
            'section' => '',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('staff.students.index'));

        $response->assertOk();

        // 2-row toolbar
        $response->assertSee('grid grid-cols-2 lg:grid-cols-4', false);

        // Clean section output: section 4-1 vs em-dash
        $response->assertSee('BSIT 4-1');
        $response->assertSee('BSIT');
        $response->assertSee('—');
        $response->assertDontSee('No section yet');

        // Unbolded tabular nums student ID
        $response->assertSee('tabular-nums');

        // Plain text submission status with 8px dot
        $response->assertSee('Pending');
        $response->assertSee('w-2 h-2 rounded-full bg-amber-500', false);
    }
}
