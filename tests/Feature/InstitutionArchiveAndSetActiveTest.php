<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionArchiveAndSetActiveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $activeYear;
    private AcademicYear $pastYear;
    private Department $dept;
    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'system_admin',
            'email_verified_at' => now(),
        ]);

        $this->pastYear = AcademicYear::create([
            'label' => '2025-2026',
            'is_current' => false,
        ]);

        $this->activeYear = AcademicYear::create([
            'label' => '2026-2027',
            'is_current' => true,
        ]);

        $this->dept = Department::create(['name' => 'School of Computer Studies']);
        $this->program = Program::create([
            'department_id' => $this->dept->id,
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
        ]);
    }

    public function test_cannot_set_earlier_academic_year_as_active(): void
    {
        $this->assertFalse($this->pastYear->canBeSetActive());

        $response = $this->actingAs($this->admin)
            ->patch(route('institution.academic-years.set-current', $this->pastYear->id));

        $response->assertSessionHasErrors(['error' => "Cannot set '{$this->pastYear->label}' as active: only a later academic year can be activated."]);
        $this->assertTrue($this->activeYear->fresh()->is_current);
        $this->assertFalse($this->pastYear->fresh()->is_current);
    }

    public function test_institution_view_renders_past_year_label_and_no_set_active_form_for_earlier_year(): void
    {
        $response = $this->actingAs($this->admin)->get(route('institution.index'));
        $response->assertStatus(200);
        $response->assertSee('Institution Data');
        $response->assertSee('Past year');
        $response->assertDontSee("action=\"" . route('institution.academic-years.set-current', $this->pastYear->id) . "\"", false);
    }

    public function test_can_set_later_academic_year_as_active(): void
    {
        $futureYear = AcademicYear::create([
            'label' => '2027-2028',
            'is_current' => false,
        ]);

        $this->assertTrue($futureYear->canBeSetActive());

        $response = $this->actingAs($this->admin)
            ->patch(route('institution.academic-years.set-current', $futureYear->id), [
                'confirmed' => '1',
            ]);

        $response->assertSessionHas('status');
        $this->assertTrue($futureYear->fresh()->is_current);
        $this->assertFalse($this->activeYear->fresh()->is_current);
    }

    public function test_active_academic_year_cannot_be_archived(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('institution.academic-years.destroy', $this->activeYear->id));

        $response->assertSessionHasErrors('error');
        $this->assertNull($this->activeYear->fresh()->archived_at);
    }

    public function test_school_with_counselors_cannot_be_archived(): void
    {
        User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $this->dept->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('institution.departments.destroy', $this->dept->id));

        $response->assertSessionHasErrors('error');
        $this->assertNull($this->dept->fresh()->archived_at);
    }

    public function test_program_with_open_or_upcoming_schedule_cannot_be_archived(): void
    {
        AssessmentSchedule::create([
            'academic_year_id' => $this->activeYear->id,
            'program_id' => $this->program->id,
            'year_level' => '1st',
            'open_date' => now()->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->addDays(5)->format('Y-m-d'),
            'close_time' => '17:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('institution.programs.destroy', $this->program->id));

        $response->assertSessionHasErrors('error');
        $this->assertNull($this->program->fresh()->archived_at);
    }

    public function test_admin_can_archive_and_restore_program_without_active_schedules(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('institution.programs.destroy', $this->program->id));

        $response->assertRedirect(route('institution.index', ['archived' => 1]));
        $this->assertNotNull($this->program->fresh()->archived_at);

        // Restore program
        $restoreResponse = $this->actingAs($this->admin)
            ->patch(route('institution.programs.restore', $this->program->id));

        $restoreResponse->assertRedirect(route('institution.index', ['archived' => 1]));
        $this->assertNull($this->program->fresh()->archived_at);
    }

    public function test_archived_program_is_hidden_from_registration_and_schedule_dropdowns(): void
    {
        $this->program->update(['archived_at' => now()]);

        // Registration page does not include archived program
        $regResponse = $this->get('/register');
        $regResponse->assertStatus(200);
        $regResponse->assertDontSee($this->program->name);

        // Schedule create page does not include archived program
        $schedResponse = $this->actingAs($this->admin)->get(route('schedules.create'));
        $schedResponse->assertStatus(200);
        $schedResponse->assertDontSee($this->program->name);
    }
}

