<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\InventorySubmission;
use App\Models\InventoryScore;
use Database\Seeders\QuestionBankSeeder;

class InventoryScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(QuestionBankSeeder::class);
        
        AssessmentSchedule::create([
            'academic_year_id' => AcademicYear::first()->id,
            'year_level' => '3rd',
            'open_date' => now()->subDay()->toDateString(),
            'open_time' => '00:00:00',
            'close_date' => now()->addDays(5)->toDateString(),
            'close_time' => '23:59:59',
        ]);
    }

    public function test_scores_are_computed_on_submission()
    {
        $student = User::factory()->create(['role' => 'student', 'year_level' => '3rd']);

        $response = $this->actingAs($student)->get('/inventory');
        $response->assertStatus(200);
        $submission = InventorySubmission::where('user_id', $student->id)->first();

        // Prepare dummy payload
        $categories = QuestionCategory::with('questionItems')->get();
        
        $responses = [];
        $timings = [];
        
        foreach ($categories as $category) {
            foreach ($category->questionItems as $item) {
                // Determine a value based on category type so we get predictable results
                if (strtolower($category->name) === 'dass21') {
                    $val = 1; // 7 items per subscale * 1 = 7. Scaled = 14 (Moderate, Mild, Normal depending on scale)
                } elseif (strtolower($category->name) === 'learning_style') {
                    // Kinesthetic (index 2)
                    $val = $item->options[2];
                } else {
                    $val = 3;
                }
                
                $responses[$category->name][$item->item_number] = $val;
                $timings[$category->name][$item->item_number] = 1000;
            }
        }

        $payload = [
            'consent_checkbox' => true,
            'signature_type' => 'typed',
            'signature_data' => 'John Doe',
            'responses' => $responses,
            'timings' => $timings,
        ];

        $postResponse = $this->actingAs($student)->post('/inventory', $payload);
        $postResponse->assertStatus(200); // returns view('inventory.success')

        $submission->refresh();
        $this->assertNotNull($submission->submitted_at);

        // Verify Scores
        $scores = InventoryScore::where('inventory_submission_id', $submission->id)->get();
        $this->assertNotEmpty($scores);

        // DASS21 Check
        $dassScores = $scores->where('category_name', 'dass21');
        $this->assertCount(4, $dassScores); // depression, anxiety, stress, total
        
        $depression = $dassScores->where('subscale_name', 'depression')->first();
        $this->assertEquals(7, $depression->raw_score); // 7 items * 1
        $this->assertEquals(14, $depression->scaled_score); // 7 * 2
        $this->assertEquals('Moderate', $depression->severity_label); // 14 for Depression is Moderate

        $anxiety = $dassScores->where('subscale_name', 'anxiety')->first();
        $this->assertEquals('Moderate', $anxiety->severity_label); // 14 for Anxiety is Moderate

        $stress = $dassScores->where('subscale_name', 'stress')->first();
        $this->assertEquals('Normal', $stress->severity_label); // 14 for Stress is Normal

        // CAT Check
        $catScores = $scores->where('category_name', 'cat');
        $this->assertCount(3, $catScores); // overall, worried, liked
        $overallCat = $catScores->where('subscale_name', null)->first();
        $this->assertEquals(19 * 3, $overallCat->raw_score); // 19 items * 3 = 57

        // Learning Style Check
        $lsScores = $scores->where('category_name', 'learning_style');
        $this->assertCount(3, $lsScores);
        $kinesthetic = $lsScores->where('subscale_name', 'Kinesthetic')->first();
        $this->assertEquals(14, $kinesthetic->raw_score); // 14 items all kinesthetic
    }

    public function test_staff_can_view_scores()
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $student = User::factory()->create(['role' => 'student', 'year_level' => '3rd']);

        $submission = InventorySubmission::create([
            'user_id' => $student->id,
            'academic_year' => '2025-2026',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => 'dass21',
            'subscale_name' => 'depression',
            'raw_score' => 10,
            'scaled_score' => 20,
            'severity_label' => 'Moderate'
        ]);

        $response = $this->actingAs($admin)->get("/manage/inventory/{$submission->id}");
        $response->assertStatus(200);
        $response->assertSee('dass21');
        $response->assertSee('depression');
        $response->assertSee('Moderate');
    }
}
