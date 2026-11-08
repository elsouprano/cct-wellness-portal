<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearPromotionConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $activeYear;
    private AcademicYear $futureYear;
    private AcademicYear $pastYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'email_verified_at' => now(),
        ]);

        $this->pastYear = AcademicYear::create([
            'label' => '2024-2025',
            'is_current' => false,
        ]);

        $this->activeYear = AcademicYear::create([
            'label' => '2025-2026',
            'is_current' => true,
        ]);

        $this->futureYear = AcademicYear::create([
            'label' => '2026-2027',
            'is_current' => false,
        ]);
    }

    public function test_setting_active_academic_year_fails_without_confirmed_parameter(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('institution.academic-years.set-current', $this->futureYear->id), []);

        $response->assertSessionHasErrors('confirmed');
        $this->assertTrue($this->activeYear->fresh()->is_current);
        $this->assertFalse($this->futureYear->fresh()->is_current);
    }

    public function test_setting_active_academic_year_succeeds_with_confirmed_and_promotes_properly(): void
    {
        $dept = Department::create(['name' => 'School of Computer Studies']);
        $prog = Program::create(['name' => 'BSIT', 'code' => 'BSIT', 'department_id' => $dept->id]);

        $student1st = User::factory()->create([
            'role' => 'student',
            'program_id' => $prog->id,
            'year_level' => '1st',
            'section' => '1-A',
            'is_active' => true,
        ]);

        $student3rd = User::factory()->create([
            'role' => 'student',
            'program_id' => $prog->id,
            'year_level' => '3rd',
            'section' => '3-B',
            'is_active' => true,
        ]);

        $student4th = User::factory()->create([
            'role' => 'student',
            'program_id' => $prog->id,
            'year_level' => '4th',
            'section' => '4-A',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('institution.academic-years.set-current', $this->futureYear->id), [
                'confirmed' => '1',
            ]);

        $response->assertRedirect(route('institution.index'));
        $response->assertSessionHas('status');

        $this->assertFalse($this->activeYear->fresh()->is_current);
        $this->assertTrue($this->futureYear->fresh()->is_current);

        // 1st promoted to 2nd, section cleared
        $this->assertEquals('2nd', $student1st->fresh()->year_level);
        $this->assertEmpty($student1st->fresh()->section);

        // 3rd promoted to 4th, section cleared
        $this->assertEquals('4th', $student3rd->fresh()->year_level);
        $this->assertEmpty($student3rd->fresh()->section);

        // 4th remains in 4th year, section intact
        $this->assertEquals('4th', $student4th->fresh()->year_level);
        $this->assertEquals('4-A', $student4th->fresh()->section);
    }

    public function test_cannot_set_past_academic_year_active_even_with_confirmation(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('institution.academic-years.set-current', $this->pastYear->id), [
                'confirmed' => '1',
            ]);

        $response->assertSessionHasErrors('error');
        $this->assertTrue($this->activeYear->fresh()->is_current);
        $this->assertFalse($this->pastYear->fresh()->is_current);
    }

    public function test_institution_page_displays_live_student_counts_and_notes(): void
    {
        User::factory()->create([
            'role' => 'student',
            'year_level' => '1st',
            'is_active' => true,
        ]);
        User::factory()->create([
            'role' => 'student',
            'year_level' => '2nd',
            'is_active' => true,
        ]);
        User::factory()->create([
            'role' => 'student',
            'year_level' => '4th',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('institution.index'));
        $response->assertStatus(200);

        // Check for promotion modal live count rendering
        $response->assertSee('2 active students');
        $response->assertSee('1 4th-year students');
        $response->assertSee('No student changes until this year is set active.');
    }
}

