<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\InventorySubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_and_generate_flags()
    {
        // Setup
        $this->artisan('db:seed', ['--class' => 'FlagSettingSeeder']);

        $year = AcademicYear::create(['label' => '2025-2026', 'is_current' => true]);
        $student = User::factory()->create(['role' => 'student', 'year_level' => '3rd', 'program' => 'BSCS']);

        $cat = QuestionCategory::create([
            'academic_year_id' => $year->id,
            'year_level' => '3rd',
            'name' => 'DASS21',
            'display_order' => 1,
            'scale_type' => 'likert',
        ]);
        QuestionItem::create(['question_category_id' => $cat->id, 'item_number' => 1, 'prompt' => 'Q1', 'options' => [0,1,2,3], 'subscale_tag' => 'depression']);
        QuestionItem::create(['question_category_id' => $cat->id, 'item_number' => 2, 'prompt' => 'Q2', 'options' => [0,1,2,3], 'subscale_tag' => 'depression']);
        QuestionItem::create(['question_category_id' => $cat->id, 'item_number' => 3, 'prompt' => 'Q3', 'options' => [0,1,2,3], 'subscale_tag' => 'depression']);

        AssessmentSchedule::create([
            'academic_year_id' => $year->id,
            'year_level' => '3rd',
            'program' => 'All',
            'open_date' => now()->subDay(),
            'close_date' => now()->addDay(),
            'open_time' => '00:00:00',
            'close_time' => '23:59:59',
        ]);

        $this->actingAs($student);
        $this->get(route('inventory.index')); // creates draft submission

        // Act
        $response = $this->post(route('inventory.store'), [
            'consent_checkbox' => 'on',
            'signature_type' => 'typed',
            'signature_data' => 'Test Name',
            'responses' => [
                'DASS21' => [
                    '1' => '1',
                    '2' => '1',
                    '3' => '1',
                ]
            ],
            'timings' => [
                'DASS21' => [
                    '1' => 1000,
                    '2' => 1000,
                    '3' => 1000,
                ]
            ]
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(200); // Wait, store returns view('inventory.success')

        $submission = InventorySubmission::first();
        $this->assertNotNull($submission->submitted_at);
        $this->assertEquals(3, $submission->responses()->count());
        $this->assertNotEmpty($submission->flags);
    }
}
