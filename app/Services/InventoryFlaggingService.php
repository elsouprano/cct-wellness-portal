<?php

namespace App\Services;

use App\Models\InventorySubmission;
use App\Models\InventoryFlag;
use App\Models\FlagSetting;
use App\Models\QuestionCategory;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Cache;

class InventoryFlaggingService
{
    protected $settings;

    public function __construct()
    {
        $this->settings = Cache::remember('flag_settings_mapped', 3600, function () {
            return FlagSetting::all()->mapWithKeys(function ($setting) {
                return ["{$setting->flag_type}.{$setting->setting_key}" => $setting->setting_value];
            });
        });
    }

    public function analyze(InventorySubmission $submission)
    {
        $academicYear = AcademicYear::where('label', $submission->academic_year)->first();
        if (!$academicYear) return;

        $responses = $submission->responses;
        if ($responses->isEmpty()) return;

        $yearLevel = $submission->user?->year_level ?? '3rd';
        $categoriesModel = QuestionCategory::where('academic_year_id', $academicYear->id)
            ->where('year_level', $yearLevel)
            ->with(['questionItems', 'correlatedPairs'])
            ->get();

        $flagsToInsert = [];
        $now = now();

        $this->detectSpeedFlag($submission, $responses, $flagsToInsert, $now);
        $this->detectStraightLining($submission, $responses, $categoriesModel, $flagsToInsert, $now);
        $this->detectContradiction($submission, $responses, $categoriesModel, $flagsToInsert, $now);

        if (!empty($flagsToInsert)) {
            InventoryFlag::insert($flagsToInsert);
        }
    }

    protected function detectSpeedFlag(InventorySubmission $submission, $responses, array &$flagsToInsert, $now)
    {
        $threshold = (float) $this->settings->get('speed.seconds_per_item_threshold', 1.5);
        
        $totalItems = $responses->count();
        if ($totalItems === 0) return;

        // If we have timings, sum them up
        $totalTimeMs = $submission->timings()->sum('time_spent_ms');
        
        if ($totalTimeMs == 0 && $submission->started_at && $submission->submitted_at) {
            $totalTimeMs = $submission->started_at->diffInMilliseconds($submission->submitted_at);
        }
        
        $avgSecondsPerItem = ($totalTimeMs / 1000) / $totalItems;

        if ($avgSecondsPerItem < $threshold) {
            $flagsToInsert[] = [
                'inventory_submission_id' => $submission->id,
                'flag_type' => 'speed',
                'category' => null,
                'subscale_tag' => null,
                'details' => json_encode([
                    'avg_seconds_per_item' => round($avgSecondsPerItem, 2),
                    'threshold' => $threshold,
                    'total_items' => $totalItems
                ]),
                'is_reviewed' => false,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'reviewer_notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
    }

    protected function detectStraightLining(InventorySubmission $submission, $responses, $categoriesModel, array &$flagsToInsert, $now)
    {
        $thresholdPercentage = (float) $this->settings->get('straight_line.percentage_threshold', 90);
        $groupedByCategory = $responses->groupBy('category');

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
                $flagsToInsert[] = [
                    'inventory_submission_id' => $submission->id,
                    'flag_type' => 'straight_line',
                    'category' => $categoryName,
                    'subscale_tag' => null,
                    'details' => json_encode([
                        'percentage' => round($percentage, 2),
                        'threshold' => $thresholdPercentage,
                        'total_in_category' => $totalInCategory,
                        'max_frequency' => $maxFrequency
                    ]),
                    'is_reviewed' => false,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'reviewer_notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
    }

    protected function detectContradiction(InventorySubmission $submission, $responses, $categoriesModel, array &$flagsToInsert, $now)
    {
        $keyedResponses = $responses->keyBy(function ($r) {
            // Key by category name and item number for easy lookup
            return strtolower($r->category) . '|' . $r->item_number;
        });
            
        foreach ($categoriesModel as $category) {
            foreach ($category->correlatedPairs as $pair) {
                // Get the actual items
                $itemA = $category->questionItems->firstWhere('id', $pair->question_item_id_a);
                $itemB = $category->questionItems->firstWhere('id', $pair->question_item_id_b);
                
                if (!$itemA || !$itemB) continue;

                // Look up student's responses
                $responseA = $keyedResponses->get(strtolower($category->name) . '|' . $itemA->item_number);
                $responseB = $keyedResponses->get(strtolower($category->name) . '|' . $itemB->item_number);

                if (!$responseA || !$responseB) continue; // Didn't answer both

                // Need scale range to calculate threshold
                $optionsA = $itemA->options;
                $optionsB = $itemB->options;
                
                // Assuming standard Likert if options are null, or use options array length
                $maxRangeA = is_array($optionsA) && count($optionsA) > 1 ? count($optionsA) - 1 : $this->getScaleRange($category);
                $maxRangeB = is_array($optionsB) && count($optionsB) > 1 ? count($optionsB) - 1 : $this->getScaleRange($category);
                
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

                if ($deviation > $thresholdValue) {
                    $flagsToInsert[] = [
                        'inventory_submission_id' => $submission->id,
                        'flag_type' => 'contradiction',
                        'category' => $category->name,
                        'subscale_tag' => null, // No longer subscale-based
                        'details' => json_encode([
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
                        ]),
                        'is_reviewed' => false,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'reviewer_notes' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }
    }

    protected function getScaleRange(QuestionCategory $category): int
    {
        if ($category->scale_type === 'numeric_scale' && $category->scale_max !== null && $category->scale_min !== null) {
            return $category->scale_max - $category->scale_min;
        }
        
        // Fallback for non-numeric scales if reached
        return 0;
    }
}
