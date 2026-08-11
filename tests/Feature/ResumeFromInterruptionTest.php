<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AssessmentSchedule;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\InventorySubmission;
use App\Models\InventoryResponse;

class ResumeFromInterruptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resume_from_interruption_end_to_end()
    {
        // Setup: Create a student and a schedule
        $academicYear = \App\Models\AcademicYear::create(['label' => '2026-2027', 'is_active' => true, 'is_current' => true]);
        
        AssessmentSchedule::create([
            'academic_year_id' => $academicYear->id,
            'year_level' => '1st',
            'open_date' => now()->subDay()->format('Y-m-d'),
            'open_time' => '08:00:00',
            'close_date' => now()->addDays(7)->format('Y-m-d'),
            'close_time' => '17:00:00',
        ]);

        // Create 3 Categories to simulate sections
        $category1 = QuestionCategory::create(['name' => 'Cat 1', 'scale_type' => 'likert', 'academic_year_id' => $academicYear->id, 'year_level' => '1st', 'display_order' => 1]);
        $q1 = QuestionItem::create(['question_category_id' => $category1->id, 'item_number' => 1, 'prompt' => 'Q1']);
        
        $category2 = QuestionCategory::create(['name' => 'Cat 2', 'scale_type' => 'likert', 'academic_year_id' => $academicYear->id, 'year_level' => '1st', 'display_order' => 2]);
        $q2 = QuestionItem::create(['question_category_id' => $category2->id, 'item_number' => 1, 'prompt' => 'Q2']);
        
        $category3 = QuestionCategory::create(['name' => 'Cat 3', 'scale_type' => 'likert', 'academic_year_id' => $academicYear->id, 'year_level' => '1st', 'display_order' => 3]);
        $q3 = QuestionItem::create(['question_category_id' => $category3->id, 'item_number' => 1, 'prompt' => 'Q3']);

        $student = User::factory()->create(['role' => 'student', 'year_level' => '1st', 'program' => 'BSIT']);
        $this->actingAs($student);
        
        // 0. Visit the index to create the initial draft submission
        $this->get(route('inventory.index'))->assertStatus(200);

        // 1. Start a new inventory submission. Complete the consent/signature step. Answer Section 1 fully and click Next.
        $response = $this->postJson(route('inventory.validate-section'), [
            'category' => 'consent',
            'consent_checkbox' => 'on',
            'signature_type' => 'typed',
            'signature_data' => 'John Doe'
        ]);
        $response->assertSessionHasNoErrors();
        
        // Assert submission created with started_at
        $submission = InventorySubmission::where('user_id', $student->id)->first();
        $this->assertNotNull($submission);
        $this->assertNotNull($submission->started_at);
        $this->assertNull($submission->submitted_at);

        // Answer Section 1
        $response = $this->postJson(route('inventory.validate-section'), [
            'category' => $category1->name,
            'responses' => [
                $category1->name => [
                    $q1->item_number => '3'
                ]
            ],
            'timings' => [
                $category1->name => [
                    $q1->item_number => 1200
                ]
            ]
        ]);
        $response->assertSessionHasNoErrors();

        // 2. Answer Section 2 fully and click Next. Stop here.
        $response = $this->postJson(route('inventory.validate-section'), [
            'category' => $category2->name,
            'responses' => [
                $category2->name => [
                    $q2->item_number => '4'
                ]
            ],
            'timings' => [
                $category2->name => [
                    $q2->item_number => 1500
                ]
            ]
        ]);
        $response->assertSessionHasNoErrors();

        // 3. Confirm in the database: responses saved.
        $this->assertEquals(2, InventoryResponse::where('inventory_submission_id', $submission->id)->count());
        $this->assertDatabaseHas('inventory_responses', ['category' => $category1->name, 'item_number' => $q1->item_number]);
        $this->assertDatabaseHas('inventory_responses', ['category' => $category2->name, 'item_number' => $q2->item_number]);
        
        $submission->refresh();
        $this->assertNull($submission->submitted_at);

        // 4. Simulate returning after interruption
        $this->post('/logout');
        $this->actingAs($student);

        // 5. Go back to /inventory. Confirm it resumes at Section 3.
        $response = $this->get(route('inventory.index'));
        $response->assertStatus(200);
        // The view should receive $startStep = 3 (since Step 0=Consent, Step 1=Cat1, Step 2=Cat2 are done, Step 3 is Cat3)
        $response->assertViewHas('startStep', 3);
        $response->assertViewHas('submission');
        // Consent should be marked true
        $this->assertNotNull($response->original->getData()['submission']->consent_given_at);

        // 6. Complete Section 3 and submit fully.
        $response = $this->post(route('inventory.store'), [
            'consent_checkbox' => 'on',
            'signature_type' => 'typed',
            'signature_data' => 'John Doe',
            'responses' => [
                $category1->name => [$q1->item_number => '3'],
                $category2->name => [$q2->item_number => '4'],
                $category3->name => [$q3->item_number => '5']
            ],
            'timings' => [
                $category1->name => [$q1->item_number => 1200],
                $category2->name => [$q2->item_number => 1500],
                $category3->name => [$q3->item_number => 2000]
            ]
        ]);
        
        $response->assertStatus(200);
        $response->assertViewIs('inventory.success');

        $submission->refresh();
        $this->assertNotNull($submission->submitted_at);
        $this->assertEquals(3, InventoryResponse::where('inventory_submission_id', $submission->id)->count());

        // 7. Access /inventory again -> should redirect to read-only or show "already submitted"
        // In our app, if submitted, inventory.index redirects to read-only or similar.
        $response = $this->get(route('inventory.index'));
        $response->assertStatus(200);
        $response->assertViewIs('inventory.submitted');
    }

    public function test_abandoned_submission_does_not_block_new_schedule()
    {
        // Setup: Create a student and an OLD schedule
        $student = User::factory()->create(['role' => 'student', 'year_level' => '1st', 'program' => 'BSIT']);
        $oldAcademicYear = \App\Models\AcademicYear::create(['label' => '2025-2026', 'is_active' => false, 'is_current' => false]);
        
        AssessmentSchedule::create([
            'academic_year_id' => $oldAcademicYear->id,
            'year_level' => '1st',
            'open_date' => now()->subYear()->format('Y-m-d'),
            'open_time' => '08:00:00',
            'close_date' => now()->subYear()->addDays(7)->format('Y-m-d'),
            'close_time' => '17:00:00',
        ]);

        // Student started but abandoned
        $oldSubmission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => '2025-2026',
            'academic_year_id' => $oldAcademicYear->id,
            'year_level' => '1st',
            'started_at' => now()->subMonths(6),
            'submitted_at' => null, // Abandoned
            'consent_given_at' => now()->subMonths(6),
            'consent_signature' => 'Jane Doe'
        ]);

        // Now, a NEW schedule opens
        $newAcademicYear = \App\Models\AcademicYear::create(['label' => '2026-2027', 'is_active' => true, 'is_current' => true]);
        AssessmentSchedule::create([
            'academic_year_id' => $newAcademicYear->id,
            'year_level' => '1st',
            'open_date' => now()->subDay()->format('Y-m-d'),
            'open_time' => '08:00:00',
            'close_date' => now()->addDays(7)->format('Y-m-d'),
            'close_time' => '17:00:00',
        ]);

        $this->actingAs($student);
        
        // Go to take inventory - should see the form because the current academic year's submission doesn't exist
        $response = $this->get(route('inventory.index'));
        $response->assertStatus(200);
        $response->assertViewHas('startStep', 0); // Completely fresh start for the new academic year
    }
}
