<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\QuestionCategory;
use App\Models\InventorySubmission;
use App\Models\InventoryResponse;
use App\Models\InventoryItemTiming;
use App\Services\InventoryScoringService;
use App\Services\ResponseQualityAssessmentService;
use App\Services\DashboardStatsService;

class MockStudentResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $academicYear = AcademicYear::firstOrCreate(['label' => '2026-2027'], ['is_current' => true]);
        }

        $scoringService = app(InventoryScoringService::class);
        $qualityService = app(ResponseQualityAssessmentService::class);

        // Fetch categories for 3rd year
        $categories = QuestionCategory::where('academic_year_id', $academicYear->id)
            ->where('year_level', '3rd')
            ->with(['questionItems', 'correlatedPairs'])
            ->orderBy('display_order')
            ->get();

        if ($categories->isEmpty()) {
            // Fallback to any 3rd year categories
            $categories = QuestionCategory::where('year_level', '3rd')
                ->with(['questionItems', 'correlatedPairs'])
                ->orderBy('display_order')
                ->get();
        }

        // Define mock profiles
        $profiles = [
            // PASSED PROFILES
            [
                'email' => 's1@cct.edu.ph',
                'name' => 'Student One',
                'behavior' => 'passed_balanced',
                'description' => 'Acceptable - Balanced normal profile',
                'item_pace_ms' => 4200,
            ],
            [
                'email' => 'stud1113@citycollegeoftagaytay.edu.ph',
                'name' => 'Maria Santos',
                'behavior' => 'passed_mild_dass',
                'description' => 'Acceptable - Varied responses with mild stress',
                'item_pace_ms' => 3800,
            ],
            [
                'email' => 'jasmine.ilagan@citycollegeoftagaytay.edu.ph',
                'name' => 'Jasmine Ilagan',
                'behavior' => 'passed_moderate_dass',
                'description' => 'Acceptable - Varied responses with moderate anxiety',
                'item_pace_ms' => 4500,
            ],

            // ISSUE PROFILES
            [
                'email' => 's2@cct.edu.ph',
                'name' => 'Student Two',
                'behavior' => 'issue_speed',
                'description' => 'Issue - Unusually fast completion (rushed through questionnaire)',
                'item_pace_ms' => 450, // 0.45s per item (below 1.5s threshold)
            ],
            [
                'email' => 's3@cct.edu.ph',
                'name' => 'Student Three',
                'behavior' => 'issue_straightlining',
                'description' => 'Issue - Excessive repetitive answers across rating batteries',
                'item_pace_ms' => 3600,
            ],
            [
                'email' => 's4@cct.edu.ph',
                'name' => 'Student Four',
                'behavior' => 'issue_contradiction',
                'description' => 'Issue - Contradictory polar opposite answers on correlated pairs',
                'item_pace_ms' => 4100,
            ],
            [
                'email' => 's5@cct.edu.ph',
                'name' => 'Student Five',
                'behavior' => 'issue_combined',
                'description' => 'Issue - Combined speed and repetitive straightline pattern',
                'item_pace_ms' => 500, // 0.5s per item (rushed)
            ],
        ];

        foreach ($profiles as $prof) {
            $user = User::where('email', $prof['email'])->first();
            if (!$user) {
                // Find or create student
                $user = User::factory()->create([
                    'role' => 'student',
                    'year_level' => '3rd',
                    'email' => $prof['email'],
                    'first_name' => explode(' ', $prof['name'])[0],
                    'last_name' => explode(' ', $prof['name'])[1] ?? 'Student',
                    'program' => 'BSCS',
                    'section' => '3-1',
                ]);
            }

            // Remove previous submission for this academic year to avoid duplicates
            $existing = InventorySubmission::where('user_id', $user->id)
                ->where('academic_year', $academicYear->label)
                ->first();

            if ($existing) {
                $existing->responses()->delete();
                $existing->timings()->delete();
                $existing->scores()->delete();
                $existing->flags()->delete();
                $existing->qualityAssessments()->delete();
                $existing->delete();
            }

            $totalItems = $categories->sum(fn($c) => $c->questionItems->count());
            $totalDurationSeconds = max(20, (int) (($prof['item_pace_ms'] * $totalItems) / 1000));
            $startedAt = now()->subSeconds($totalDurationSeconds + rand(10, 60));
            $submittedAt = (clone $startedAt)->addSeconds($totalDurationSeconds);

            $submission = InventorySubmission::create([
                'user_id' => $user->id,
                'academic_year' => $academicYear->label,
                'started_at' => $startedAt,
                'submitted_at' => $submittedAt,
                'consent_given_at' => $startedAt,
                'consent_version' => '1.0',
                'signature_type' => 'typed',
                'signature_data' => "{$user->first_name} {$user->last_name}",
                'signature_font' => 'Dancing Script',
            ]);

            $responsesData = [];
            $timingsData = [];

            foreach ($categories as $cat) {
                $catName = $cat->name;
                $scaleMin = $cat->scale_min ?? 0;
                $scaleMax = $cat->scale_max ?? 3;
                $scaleRange = $scaleMax - $scaleMin;

                foreach ($cat->questionItems as $item) {
                    $itemNum = $item->item_number;
                    $value = $this->determineResponseValue($prof['behavior'], $catName, $itemNum, $scaleMin, $scaleMax, $item->options);
                    $itemTiming = max(100, $prof['item_pace_ms'] + rand(-150, 150));

                    $responsesData[] = [
                        'inventory_submission_id' => $submission->id,
                        'category' => $catName,
                        'item_number' => $itemNum,
                        'response_value' => (string) $value,
                        'created_at' => $submittedAt,
                        'updated_at' => $submittedAt,
                    ];

                    $timingsData[] = [
                        'inventory_submission_id' => $submission->id,
                        'category' => $catName,
                        'item_number' => $itemNum,
                        'time_spent_ms' => $itemTiming,
                        'created_at' => $submittedAt,
                        'updated_at' => $submittedAt,
                    ];
                }
            }

            // Batch insert responses and timings
            InventoryResponse::insert($responsesData);
            InventoryItemTiming::insert($timingsData);

            // Compute DASS-21 / other scores
            $scoringService->computeScores($submission);

            // Perform automatic Response Quality Assessment
            $qualityService->assess($submission);
        }

        DashboardStatsService::clearCache($academicYear->label);
        \App\Http\Controllers\Staff\AnalyticsController::clearCache();
    }

    /**
     * Determine realistic response values based on the assigned profile behavior.
     */
    protected function determineResponseValue(string $behavior, string $category, int $itemNumber, int $min, int $max, $options)
    {
        $catLower = strtolower($category);

        switch ($behavior) {
            case 'issue_speed':
                // Rushed through: varied answers so it specifically tests speed detection
                if ($catLower === 'dass21') return ($itemNumber % 4);
                if ($max > $min) return rand($min, $max);
                if (is_array($options) && !empty($options)) return $options[array_rand($options)];
                return '1';

            case 'issue_straightlining':
                // Selects identical option across almost all items in rating scales
                if ($catLower === 'dass21') return '3'; // All 3s (100% straightline)
                if ($catLower === 'cat') return '5'; // All 5s (100% straightline)
                if ($catLower === 'ars30') return '4'; // All 4s (100% straightline)
                if ($catLower === 'learning_style') {
                    // Always pick first option (Visual)
                    return is_array($options) && !empty($options) ? $options[0] : '1';
                }
                if ($max > $min) return (string) $max;
                if (is_array($options) && !empty($options)) return $options[0];
                return '1';

            case 'issue_contradiction':
                // In CAT (1-7 scale):
                // Item 8 = "Worried about college in general"
                // Item 13 = "Liked college in general"
                // Opposing pair: student answers 7 on BOTH!
                if ($catLower === 'cat') {
                    if ($itemNumber === 8) return '7';
                    if ($itemNumber === 13) return '7'; // Extreme contradiction!
                    if ($itemNumber === 17) return '7'; // "Felt depressed"
                    if ($itemNumber === 19) return '7'; // "Felt good about yourself" -> Contradiction!
                    return (string) (($itemNumber % 5) + 2);
                }
                // In ARS-30 (1-5 scale):
                // Item 1 = "Would not accept tutor feedback"
                // Item 2 = "Would use feedback to improve"
                if ($catLower === 'ars30') {
                    if ($itemNumber === 1) return '5'; // Strongly agree
                    if ($itemNumber === 2) return '5'; // Strongly agree -> Contradiction!
                    return (string) (($itemNumber % 4) + 1);
                }
                if ($catLower === 'dass21') return (string) ($itemNumber % 3);
                if ($max > $min) return (string) rand($min, $max);
                if (is_array($options) && !empty($options)) return $options[array_rand($options)];
                return '1';

            case 'issue_combined':
                // Both rushed speed and straightlining
                if ($catLower === 'dass21') return '2';
                if ($catLower === 'cat') return '4';
                if ($catLower === 'ars30') return '3';
                if ($max > $min) return (string) $min;
                if (is_array($options) && !empty($options)) return $options[0];
                return '1';

            case 'passed_mild_dass':
                // Mild stress / anxiety on DASS-21
                if ($catLower === 'dass21') {
                    // Higher values on stress subscale (items 1, 6, 8, 11, 12, 14, 18)
                    if (in_array($itemNumber, [1, 6, 8, 11, 12, 14, 18])) return rand(1, 2);
                    return rand(0, 1);
                }
                if ($catLower === 'cat') {
                    // Answer consistent with low worry and high liking
                    if ($itemNumber === 8) return '2';
                    if ($itemNumber === 13) return '6';
                    return (string) rand(3, 6);
                }
                if ($max > $min) return (string) rand($min, $max);
                if (is_array($options) && !empty($options)) return $options[array_rand($options)];
                return '1';

            case 'passed_moderate_dass':
                // Moderate anxiety on DASS-21
                if ($catLower === 'dass21') {
                    // Anxiety subscale (items 2, 4, 7, 9, 15, 19, 20)
                    if (in_array($itemNumber, [2, 4, 7, 9, 15, 19, 20])) return rand(1, 3);
                    return rand(0, 1);
                }
                if ($catLower === 'cat') {
                    if ($itemNumber === 8) return '4';
                    if ($itemNumber === 13) return '5';
                    return (string) rand(3, 6);
                }
                if ($max > $min) return (string) rand($min, $max);
                if (is_array($options) && !empty($options)) return $options[array_rand($options)];
                return '1';

            case 'passed_balanced':
            default:
                // Normal healthy profile across all questionnaires
                if ($catLower === 'dass21') return rand(0, 1);
                if ($catLower === 'cat') {
                    if ($itemNumber === 8) return '2'; // Low worry
                    if ($itemNumber === 13) return '6'; // High college liking (consistent opposite)
                    if ($itemNumber === 17) return '1'; // Low depression
                    if ($itemNumber === 19) return '6'; // High self-esteem (consistent opposite)
                    return (string) rand(3, 6);
                }
                if ($catLower === 'ars30') {
                    if ($itemNumber === 1) return '1'; // Disagree that wouldn't accept feedback
                    if ($itemNumber === 2) return '5'; // Agree would use feedback (consistent opposite)
                    if ($itemNumber === 3) return '1'; // Disagree would give up
                    if ($itemNumber === 16) return '5'; // Agree would keep trying (consistent opposite)
                    return (string) rand(3, 5);
                }
                if ($max > $min) return (string) rand($min, $max);
                if (is_array($options) && !empty($options)) return $options[array_rand($options)];
                return '1';
        }
    }
}

