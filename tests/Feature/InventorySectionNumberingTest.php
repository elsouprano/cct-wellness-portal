<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\CategoryYearLevel;
use App\Models\InventoryResponse;
use App\Models\InventorySubmission;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventorySectionNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_consent_is_section_1_and_16_categories_total_17_sections_with_matching_progress(): void
    {
        $academicYear = AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);

        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => '4th',
            'is_active' => true,
            'email_verified_at' => now(),
            'needs_section_confirmation' => false,
        ]);

        $categories = [];
        for ($i = 1; $i <= 16; $i++) {
            $cat = QuestionCategory::create([
                'academic_year_id' => $academicYear->id,
                'year_level' => '4th',
                'name' => "section_code_{$i}",
                'display_name' => "Instrument {$i}",
                'display_order' => $i,
                'scale_type' => 'numeric_scale',
                'scale_min' => 1,
                'scale_max' => 5,
            ]);

            CategoryYearLevel::create([
                'question_category_id' => $cat->id,
                'academic_year_id' => $academicYear->id,
                'year_level' => '4th',
                'display_order' => $i,
            ]);

            QuestionItem::create([
                'question_category_id' => $cat->id,
                'item_number' => 1,
                'prompt' => "Prompt {$i}",
            ]);

            $categories[$i] = $cat;
        }

        AssessmentSchedule::create([
            'academic_year_id' => $academicYear->id,
            'year_level' => '4th',
            'program_id' => null,
            'open_date' => now()->subDay()->toDateString(),
            'open_time' => '00:00',
            'close_date' => now()->addDays(7)->toDateString(),
            'close_time' => '23:59',
        ]);

        // Initial visit: step = 0 (Consent = Section 1 of 17, 0 completed)
        $initial = $this->actingAs($student)->get('/inventory');
        $initial->assertOk();
        $initial->assertViewHas('startStep', 0);
        $initial->assertSee('Section 1 of 17');
        $initial->assertSee('Section 17 of 17');
        $initial->assertSee('return 17;', false);

        // Save Consent + first 3 categories -> 4 sections completed (Consent + 3 categories), resuming at step = 4 (Section 5 of 17)
        $submission = InventorySubmission::where('user_id', $student->id)
            ->where('academic_year', '2026-2027')
            ->firstOrFail();

        $submission->update([
            'consent_given_at' => now(),
            'signature_type' => 'typed',
            'signature_data' => 'Juan Dela Cruz',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            InventoryResponse::create([
                'inventory_submission_id' => $submission->id,
                'category' => $categories[$i]->name,
                'item_number' => 1,
                'response_value' => '4',
            ]);
        }

        $resumed = $this->actingAs($student)->get('/inventory');
        $resumed->assertOk();
        $resumed->assertViewHas('startStep', 4);
        $resumed->assertSee('step: 4', false);
        $resumed->assertSee('maxStep: 4', false);
    }
}
