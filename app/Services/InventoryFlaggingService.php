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
        $thresholdPercentage = (float) $this->settings->get('contradiction.spread_percentage', 75);
        $responses = $submission->responses;

        $academicYear = AcademicYear::where('label', $submission->academic_year)->first();
        $categoriesModel = QuestionCategory::where('academic_year_id', $academicYear->id)
            ->where('year_level', $submission->user->year_level ?? '3rd')
            ->with('questionItems')
            ->get();
            
        // Map response to its subscale tag and compute range
        $subscaleGroups = [];
        
        foreach ($responses as $r) {
            $catModel = $categoriesModel->firstWhere('name', $r->category);
            if (!$catModel || strtolower($r->category) === 'learning_style') continue; // Skip string-based categories
            
            $item = $catModel->questionItems->firstWhere('item_number', $r->item_number);
            if (!$item || empty($item->subscale_tag)) continue;
            
            $options = $item->options;
            if (!is_array($options) || count($options) < 2) continue;
            
            $groupKey = $r->category . '|' . $item->subscale_tag;
            
            if (!isset($subscaleGroups[$groupKey])) {
                // Determine max range from options (assuming likert where keys/values are sequential or count - 1)
                $maxRange = count($options) - 1;
                $subscaleGroups[$groupKey] = [
                    'category' => $r->category,
                    'subscale_tag' => $item->subscale_tag,
                    'values' => [],
                    'max_range' => $maxRange,
                ];
            }
            
            $subscaleGroups[$groupKey]['values'][] = (int) $r->response_value;
        }

        foreach ($subscaleGroups as $group) {
            if (count($group['values']) < 3) continue; // Only apply if 3 or more items share the tag
            
            $min = min($group['values']);
            $max = max($group['values']);
            $spread = $max - $min;
            
            $spreadPercentage = ($spread / $group['max_range']) * 100;
            
            if ($spreadPercentage >= $thresholdPercentage) {
                InventoryFlag::create([
                    'inventory_submission_id' => $submission->id,
                    'flag_type' => 'contradiction',
                    'category' => $group['category'],
                    'subscale_tag' => $group['subscale_tag'],
                    'details' => [
                        'spread' => $spread,
                        'spread_percentage' => round($spreadPercentage, 2),
                        'threshold' => $thresholdPercentage,
                        'max_range' => $group['max_range'],
                        'values' => $group['values']
                    ]
                ]);
            }
        }
    }
}
