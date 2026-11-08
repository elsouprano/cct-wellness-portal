<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequiredAsterisksVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_label_required_and_legend()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // Required fields
        $response->assertSee('label-required', false);
        $response->assertSee('Required field');
        $response->assertSee('identifier', false);
        $response->assertSee('password', false);
    }

    public function test_register_page_renders_label_required_and_legend()
    {
        $dept = Department::create(['name' => 'School of Computing']);
        Program::create(['name' => 'BS Computer Science', 'code' => 'BSCS', 'department_id' => $dept->id]);

        $response = $this->get(route('register'));
        $response->assertStatus(200);

        // Required fields on wizard
        $response->assertSee('label-required', false);
        $response->assertSee('Required field');
        $response->assertSee('Institutional Email');
        $response->assertSee('First Name');
        $response->assertSee('Last Name');
        $response->assertSee('Birthdate');
        $response->assertSee('Contact Number');
        $response->assertSee('Student ID');
        $response->assertSee('Section');
        $response->assertSee('School');
        $response->assertSee('Degree Program');
        $response->assertSee('Address Line 1 (Street, Barangay)');
        $response->assertSee('City / Municipality');
        $response->assertSee('Province');

        // Optional field has (Opt)
        $response->assertSee('M.I.');
        $response->assertSee('(Opt)');
    }

    public function test_forgot_password_page_renders_label_required_and_legend()
    {
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);

        $response->assertSee('label-required', false);
        $response->assertSee('Required field');
        $response->assertSee('Email Address');
    }

    public function test_schedules_create_and_edit_render_label_required_and_legend()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $ay = AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);

        $response = $this->actingAs($admin)->get(route('schedules.create'));
        $response->assertStatus(200);

        $response->assertSee('label-required', false);
        $response->assertSee('Required field');
        $response->assertSee('Academic Year');
        $response->assertSee('Year Level');
        $response->assertSee('Open Date');
        $response->assertSee('Open Time');
        $response->assertSee('Close Date');
        $response->assertSee('Close Time');
    }

    public function test_question_bank_create_renders_label_required_and_legend()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);

        $response = $this->actingAs($admin)->get(route('question-bank.create'));
        $response->assertStatus(200);

        $response->assertSee('label-required', false);
        $response->assertSee('Required field');
        $response->assertSee('Category Code / Key');
        $response->assertSee('Scale Type');
        $response->assertSee('Display Order');
    }

    public function test_institution_data_page_renders_required_labels_and_legends()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);

        $response = $this->actingAs($admin)->get(route('institution.index'));
        $response->assertStatus(200);

        $response->assertSee('label-required', false);
        $response->assertSee('Required field');
    }

    public function test_accounts_management_page_renders_required_labels_and_legends()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        $response = $this->actingAs($admin)->get(route('manage.accounts.index'));
        $response->assertStatus(200);

        $response->assertSee('label-required', false);
        $response->assertSee('Required field');
        $response->assertSee('First Name');
        $response->assertSee('Last Name');
        $response->assertSee('Email Address (Institutional)');
        $response->assertSee('Assigned Schools (Select one or more)');
    }

    public function test_flag_settings_page_renders_required_labels_and_legends()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        $response = $this->actingAs($admin)->get(route('flag-settings.index'));
        $response->assertStatus(200);

        $response->assertSee('label-required', false);
        $response->assertSee('Required field');
    }

    public function test_profile_edit_page_renders_required_labels_and_legends()
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('profile.edit'));
        $response->assertStatus(200);

        $response->assertSee('label-required', false);
        $response->assertSee('Required field');
        $response->assertSee('Contact number');
        $response->assertSee('Address line 1');
        $response->assertSee('City');
        $response->assertSee('Province');
        $response->assertSee('Current password');
        $response->assertSee('New password');
        $response->assertSee('Confirm new password');
    }
}
