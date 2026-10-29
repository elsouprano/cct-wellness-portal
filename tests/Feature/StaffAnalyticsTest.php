<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\InventoryScore;
use App\Models\InventorySubmission;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_dashboard_renders_with_scores_and_export()
    {
        $department = Department::firstOrCreate(['name' => 'Department of Computer Studies']);
        $program = Program::create([
            'department_id' => $department->id,
            'name' => 'Computer Science',
            'code' => 'BSCS',
        ]);

        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $department->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $ay = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        $student = User::factory()->create([
            'role' => 'student',
            'program_id' => $program->id,
            'year_level' => '3rd',
            'email_verified_at' => now(),
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => $ay->label,
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => 'dass21',
            'subscale_name' => 'Anxiety',
            'raw_score' => 8,
            'scaled_score' => 16,
            'severity_label' => 'Moderate',
        ]);

        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => 'learning_style',
            'subscale_name' => 'Visual',
            'raw_score' => 12,
            'scaled_score' => null,
            'severity_label' => null,
        ]);

        // Test dashboard renders with 200
        $response = $this->actingAs($counselor)->get(route('analytics.index'));
        $response->assertStatus(200);
        $response->assertSee('Analytics Dashboard');

        // Test CSV export streams with 200
        $exportResponse = $this->actingAs($counselor)->get(route('analytics.export'));
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('content-type'));
    }

    public function test_analytics_export_sanitizes_formula_injection()
    {
        $department = Department::firstOrCreate(['name' => 'Department of Computer Studies']);
        $program = Program::create([
            'department_id' => $department->id,
            'name' => '=SUM(1,2)',
            'code' => 'BSCS',
        ]);

        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'department_id' => $department->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $ay = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        // Student with section starting with '=' (formula character)
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => '3rd',
            'program_id' => $program->id,
            'section' => '=SUM(1,2)',
            'email_verified_at' => now(),
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => $ay->label,
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => 'dass21',
            'subscale_name' => 'Anxiety',
            'raw_score' => 8,
            'scaled_score' => 16,
            'severity_label' => 'Moderate',
        ]);

        $exportResponse = $this->actingAs($counselor)->get(route('analytics.export'));
        $exportResponse->assertStatus(200);

        ob_start();
        $exportResponse->sendContent();
        $content = ob_get_clean();

        // Must prepend single quote to escape formula
        $this->assertStringContainsString("'=SUM(1,2)", $content);
    }
}
