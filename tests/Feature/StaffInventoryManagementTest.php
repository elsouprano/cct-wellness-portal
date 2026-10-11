<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\InventoryFlag;
use App\Models\InventoryScore;
use App\Models\InventorySubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffInventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sorting_submissions_by_student_name_preserves_flag_counts()
    {
        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $ay = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        $student = User::factory()->create([
            'role' => 'student',
            'first_name' => 'Alice',
            'last_name' => 'Zeta',
            'year_level' => '3rd',
            'email_verified_at' => now(),
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => $ay->label,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        InventoryFlag::create([
            'inventory_submission_id' => $submission->id,
            'flag_type' => 'speed',
            'is_reviewed' => false,
            'details' => ['avg_seconds_per_item' => 0.5],
        ]);

        // When sorting by student_name, flags_count and unreviewed_flags must be preserved
        $response = $this->actingAs($counselor)->get(route('staff.inventory.index', [
            'sort' => 'student_name',
            'direction' => 'asc',
        ]));

        $response->assertStatus(200);
        $response->assertSee('1 Total');
        $response->assertSee('1 Unreviewed');
    }

    public function test_dass21_severity_filtering_and_display_works_with_lowercase_category()
    {
        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $ay = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);

        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => '3rd',
            'email_verified_at' => now(),
        ]);

        $submission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => $ay->label,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'consent_version' => '1.0',
        ]);

        // Score saved with lowercase 'dass21'
        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => 'dass21',
            'subscale_name' => 'Depression',
            'raw_score' => 14,
            'scaled_score' => 28,
            'severity_label' => 'Extremely Severe',
        ]);

        $response = $this->actingAs($counselor)->get(route('staff.inventory.index', [
            'min_severity' => 'Extremely Severe',
            'sort' => 'dass21_severity',
            'direction' => 'desc',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Extremely Severe');
    }

    public function test_staff_navigation_only_highlights_current_resource()
    {
        $counselor = User::factory()->create([
            'role' => 'guidance_counselor',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // When visiting Submissions (/manage/inventory)
        $inventoryResponse = $this->actingAs($counselor)->get(route('staff.inventory.index'));
        $inventoryResponse->assertStatus(200);
        $inventoryHtml = $inventoryResponse->getContent();

        // Submissions must be active
        $this->assertMatchesRegularExpression(
            '/<a[^>]*href="[^"]*manage\/inventory"[^>]*class="[^"]*bg-primary\/10[^"]*"[^>]*>/',
            $inventoryHtml
        );

        // Student Profiles must NOT be active
        $this->assertDoesNotMatchRegularExpression(
            '/<a[^>]*href="[^"]*manage\/students"[^>]*class="[^"]*bg-primary\/10[^"]*"[^>]*>/',
            $inventoryHtml
        );

        // When visiting Student Profiles (/manage/students)
        $studentsResponse = $this->actingAs($counselor)->get(route('staff.students.index'));
        $studentsResponse->assertStatus(200);
        $studentsHtml = $studentsResponse->getContent();

        // Student Profiles must be active
        $this->assertMatchesRegularExpression(
            '/<a[^>]*href="[^"]*manage\/students"[^>]*class="[^"]*bg-primary\/10[^"]*"[^>]*>/',
            $studentsHtml
        );

        // Submissions must NOT be active
        $this->assertDoesNotMatchRegularExpression(
            '/<a[^>]*href="[^"]*manage\/inventory"[^>]*class="[^"]*bg-primary\/10[^"]*"[^>]*>/',
            $studentsHtml
        );
    }
}
