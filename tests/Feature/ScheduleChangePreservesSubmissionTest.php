<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\CategoryYearLevel;
use App\Models\Department;
use App\Models\InventoryItemTiming;
use App\Models\InventoryResponse;
use App\Models\InventorySubmission;
use App\Models\Program;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleChangePreservesSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_or_editing_schedule_never_resets_saved_student_responses_or_resume_step(): void
    {
        $academicYear = AcademicYear::create(['label' => '2026-2027', 'is_current' => true]);
        $dept = Department::create(['name' => 'School of Computer Studies']);
        $bsit = Program::create([
            'department_id' => $dept->id,
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
        ]);

        $admin = User::factory()->create([
            'role' => 'system_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $dept->id,
            'program_id' => $bsit->id,
            'program' => 'BSIT',
            'year_level' => '4th',
            'section' => '4-1',
            'is_active' => true,
            'email_verified_at' => now(),
            'needs_section_confirmation' => false,
        ]);

        foreach (['erq' => 1, 'dass21' => 2] as $code => $order) {
            $cat = QuestionCategory::create([
                'academic_year_id' => $academicYear->id,
                'year_level' => '4th',
                'name' => $code,
                'display_name' => strtoupper($code),
                'display_order' => $order,
                'scale_type' => 'numeric_scale',
                'scale_min' => 1,
                'scale_max' => 5,
            ]);

            CategoryYearLevel::create([
                'question_category_id' => $cat->id,
                'academic_year_id' => $academicYear->id,
                'year_level' => '4th',
                'display_order' => $order,
            ]);

            QuestionItem::create([
                'question_category_id' => $cat->id,
                'item_number' => 1,
                'prompt' => "Item 1 for {$code}",
            ]);
            QuestionItem::create([
                'question_category_id' => $cat->id,
                'item_number' => 2,
                'prompt' => "Item 2 for {$code}",
            ]);
        }

        $openDate = now()->format('Y-m-d');
        $schedule = AssessmentSchedule::create([
            'academic_year_id' => $academicYear->id,
            'year_level' => '4th',
            'program_id' => null,
            'open_date' => $openDate,
            'open_time' => '00:00',
            'close_date' => now()->addDays(5)->format('Y-m-d'),
            'close_time' => '23:59',
        ]);

        // 1. Student opens inventory and saves Consent + Section 2 (erq)
        $this->actingAs($student)->get('/inventory')->assertOk();

        $this->actingAs($student)->postJson('/inventory/validate-section', [
            'category' => 'consent',
            'consent_checkbox' => '1',
            'signature_type' => 'typed',
            'signature_data' => 'Juan Student',
        ])->assertOk();

        $this->actingAs($student)->postJson('/inventory/validate-section', [
            'category' => 'erq',
            'responses' => ['erq' => [1 => 4, 2 => 5]],
            'timings' => ['erq' => [1 => 1250, 2 => 980]],
        ])->assertOk();

        $submission = InventorySubmission::where('user_id', $student->id)
            ->where('academic_year', '2026-2027')
            ->firstOrFail();

        // 2. Admin edits the running schedule (narrows to BSIT and extends close_date)
        $this->actingAs($admin)->put(route('schedules.update', $schedule), [
            'academic_year_id' => $academicYear->id,
            'year_level' => '4th',
            'program_id' => $bsit->id,
            'open_date' => $openDate,
            'open_time' => '00:00',
            'close_date' => now()->addDays(10)->format('Y-m-d'),
            'close_time' => '22:30',
        ])->assertRedirect(route('schedules.index'));

        // 3. Admin creates an additional upcoming schedule for the same academic year
        $this->actingAs($admin)->post(route('schedules.store'), [
            'academic_year_id' => $academicYear->id,
            'year_level' => '4th',
            'program_id' => null,
            'open_date' => now()->addDays(12)->format('Y-m-d'),
            'open_time' => '08:00',
            'close_date' => now()->addDays(20)->format('Y-m-d'),
            'close_time' => '17:00',
        ])->assertRedirect(route('schedules.index'));

        // 4. Student's submission, consent, responses, timings, and resume step are 100% preserved
        $submission->refresh();
        $this->assertNotNull($submission->consent_given_at);
        $this->assertEquals('Juan Student', $submission->signature_data);
        $this->assertEquals(2, InventoryResponse::where('inventory_submission_id', $submission->id)->count());
        $this->assertEquals('4', InventoryResponse::where('inventory_submission_id', $submission->id)->where('category', 'erq')->where('item_number', 1)->value('response_value'));
        $this->assertEquals(1250, InventoryItemTiming::where('inventory_submission_id', $submission->id)->where('category', 'erq')->where('item_number', 1)->value('time_spent_ms'));

        $resumeResponse = $this->actingAs($student)->get('/inventory');
        $resumeResponse->assertOk();
        $resumeResponse->assertViewHas('startStep', 2);

        // 5. Legacy student with program_id = null (only string program = 'BSIT') still matches program-scoped schedule
        $student->update(['program_id' => null, 'program' => 'BSIT']);
        $legacyResume = $this->actingAs($student->fresh())->get('/inventory');
        $legacyResume->assertOk();
        $legacyResume->assertViewHas('startStep', 2);
    }
}
