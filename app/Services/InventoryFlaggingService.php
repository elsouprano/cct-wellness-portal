<?php

namespace App\Services;

use App\Models\InventorySubmission;
use App\Models\InventoryFlag;
use App\Models\FlagSetting;
use App\Models\QuestionCategory;
use App\Models\AcademicYear;

class InventoryFlaggingService
{
    protected $settings;

    public function __construct()
    {
        $this->settings = FlagSetting::all()->mapWithKeys(function ($setting) {
            return ["{$setting->flag_type}.{$setting->setting_key}" => $setting->setting_value];
        });
    }

    public function analyze(InventorySubmission $submission)
    {
        $this->detectSpeedFlag($submission);
        $this->detectStraightLining($submission);
        $this->detectContradiction($submission);
    }

    protected function detectSpeedFlag(InventorySubmission $submission)
    {
        $threshold = (float) $this->settings->get('speed.seconds_per_item_threshold', 1.5);
        
        $totalItems = $submission->responses()->count();
        if ($totalItems === 0) return;

        // If we have timings, sum them up
        $totalTimeMs = $submission->timings()->sum('time_spent_ms');
        
        if ($totalTimeMs == 0 && $submission->started_at && $submission->submitted_at) {
            $totalTimeMs = $submission->started_at->diffInMilliseconds($submission->submitted_at);
        }
        
        $avgSecondsPerItem = ($totalTimeMs / 1000) / $totalItems;

        if ($avgSecondsPerItem < $threshold) {
            InventoryFlag::create([
                'inventory_submission_id' => $submission->id,
                'flag_type' => 'speed',
                'details' => [
                    'avg_seconds_per_item' => round($avgSecondsPerItem, 2),
                    'threshold' => $threshold,
                    'total_items' => $totalItems
                ]
            ]);
        }
    }

    protected function detectStraightLining(InventorySubmission $submission)
    {
        $thresholdPercentage = (float) $this->settings->get('straight_line.percentage_threshold', 90);
        $responses = $submission->responses;
        
        $groupedByCategory = $responses->groupBy('category');

        $academicYear = AcademicYear::where('label', $submission->academic_year)->first();
        $categoriesModel = QuestionCategory::where('academic_year_id', $academicYear->id)
            ->where('year_level', $submission->user->year_level ?? '3rd')
            ->with('questionItems')
            ->get();

        foreach ($groupedByCategory as $categoryName => $catResponses) {
            $totalInCategory = $catResponses->count();
            if ($totalInCategory === 0) continue;

            $catModel = $categoriesModel->firstWhere('name', $categoryName);
            
            if (strtolower($categoryName) === 'learning_style' && $catModel) {
                // Special case: Learning Style options are strings, we need to check if the same option *position* was used
                $positionCounts = [];
                foreach ($catResponses as $r) {
                    $item = $catModel->questionItems->firstWhere('item_number', $r->item_number);
                    if ($item && is_array($item->options)) {
                        $index = array_search($r->response_value, $item->options);
                        if ($index !== false) {
                            $positionCounts[$index] = ($positionCounts[$index] ?? 0) + 1;
                        }
                    }
                }
                
                if (empty($positionCounts)) continue;
                $maxFrequency = max($positionCounts);
                
            } else {
                // Likert scales: just group by response value
                $valueCounts = $catResponses->groupBy('response_value')->map->count();
                $maxFrequency = $valueCounts->max();
            }

            $percentage = ($maxFrequency / $totalInCategory) * 100;

            if ($percentage >= $thresholdPercentage) {
                InventoryFlag::create([
                    'inventory_submission_id' => $submission->id,
                    'flag_type' => 'straight_line',
                    'category' => $categoryName,
                    'details' => [
                        'percentage' => round($percentage, 2),
                        'threshold' => $thresholdPercentage,
                        'total_in_category' => $totalInCategory,
                        'max_frequency' => $maxFrequency
                    ]
                ]);
            }
        }
    }

    protected function detectContradiction(InventorySubmission $submission)
    {
        $responses = $submission->responses->keyBy(function ($r) {
            // Key by category name and item number for easy lookup
            return strtolower($r->category) . '|' . $r->item_number;
        });

        $academicYear = AcademicYear::where('label', $submission->academic_year)->first();
        $categoriesModel = QuestionCategory::where('academic_year_id', $academicYear->id)
            ->with(['questionItems', 'correlatedPairs'])
            ->get();
            
        foreach ($categoriesModel as $category) {
            foreach ($category->correlatedPairs as $pair) {
                // Get the actual items
                $itemA = $category->questionItems->firstWhere('id', $pair->question_item_id_a);
                $itemB = $category->questionItems->firstWhere('id', $pair->question_item_id_b);
                
                if (!$itemA || !$itemB) continue;

                // Look up student's responses
                $responseA = $responses->get(strtolower($category->name) . '|' . $itemA->item_number);
                $responseB = $responses->get(strtolower($category->name) . '|' . $itemB->item_number);

                if (!$responseA || !$responseB) continue; // Didn't answer both

                // Need scale range to calculate threshold
                $optionsA = $itemA->options;
                $optionsB = $itemB->options;
                
                // Assuming standard Likert if options are null, or use options array length
                $maxRangeA = is_array($optionsA) && count($optionsA) > 1 ? count($optionsA) - 1 : $this->getScaleRange($category->scale_type);
                $maxRangeB = is_array($optionsB) && count($optionsB) > 1 ? count($optionsB) - 1 : $this->getScaleRange($category->scale_type);
                
                // Assume scales are identical for pairs. If not, fallback to largest range
                $maxRange = max($maxRangeA, $maxRangeB);
                
                if ($maxRange <= 0) continue; // Can't calculate percentage without a scale

                $valA = (float) $responseA->response_value;
                $valB = (float) $responseB->response_value;
                $thresholdValue = ($pair->contradiction_threshold / 100) * $maxRange;

                $deviation = 0;
                $expected = $pair->relationship_type;

                if ($pair->relationship_type === 'similar') {
                    $deviation = abs($valA - $valB);
                } elseif ($pair->relationship_type === 'inverse') {
                    // Assuming min scale is 0. Inverse of valA is (maxRange - valA)
                    $invertedA = $maxRange - $valA;
                    $deviation = abs($invertedA - $valB);
                }

                \Log::info("Contradiction check", ['valA' => $valA, 'valB' => $valB, 'dev' => $deviation, 'thresh' => $thresholdValue, 'maxRange' => $maxRange]);

                if ($deviation > $thresholdValue) {
                    InventoryFlag::create([
                        'inventory_submission_id' => $submission->id,
                        'flag_type' => 'contradiction',
                        'category' => $category->name,
                        'subscale_tag' => null, // No longer subscale-based
                        'details' => [
                            'pair_id' => $pair->id,
                            'item_a' => [
                                'prompt' => $itemA->prompt,
                                'response' => $valA
                            ],
                            'item_b' => [
                                'prompt' => $itemB->prompt,
                                'response' => $valB
                            ],
                            'expected_relationship' => $expected,
                            'actual_deviation' => $deviation,
                            'threshold_value' => $thresholdValue,
                            'threshold_percentage' => $pair->contradiction_threshold,
                            'max_range' => $maxRange,
                            'notes' => $pair->notes
                        ]
                    ]);
                }
            }
        }
    }

    protected function getScaleRange(string $scaleType): int
    {
        if (str_contains($scaleType, 'likert_1_5')) return 4; // 1 to 5 = 4
        if (str_contains($scaleType, 'likert_1_7')) return 6; // 1 to 7 = 6
        if (str_contains($scaleType, 'likert_0_3')) return 3; // 0 to 3 = 3
        if (str_contains($scaleType, 'likert_1_4')) return 3; // 1 to 4 = 3
        return 0; // Unknown
    }
}
