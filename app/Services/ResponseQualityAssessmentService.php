<?php

namespace App\Services;

use App\Models\InventorySubmission;
use App\Models\InventoryFlag;
use App\Models\FlagSetting;
use App\Models\QuestionCategory;
use App\Models\AcademicYear;
use App\Models\ResponseQualityAssessment;
use Illuminate\Support\Facades\Cache;

class ResponseQualityAssessmentService
{
    /**
     * Cache-backed map of flag settings.
     */
    protected $settings;

    public function __construct()
    {
        $this->settings = Cache::remember('flag_settings_mapped', 3600, function () {
            return FlagSetting::all()->mapWithKeys(function ($setting) {
                return ["{$setting->flag_type}.{$setting->setting_key}" => $setting->setting_value];
            });
        });
    }

    /**
     * Retrieve a configuration setting with fallback default.
     */
    public function getSetting(string $key, $default = null)
    {
        if ($this->settings && $this->settings->has($key)) {
            return $this->settings->get($key);
        }

        $parts = explode('.', $key);
        if (count($parts) === 2) {
            $dbSetting = FlagSetting::where('flag_type', $parts[0])
                ->where('setting_key', $parts[1])
                ->value('setting_value');

            if ($dbSetting !== null) {
                return $dbSetting;
            }
        }

        return $default;
    }

    /**
     * Assess the response quality of a student submission using deterministic rules.
     */
    public function assess(InventorySubmission $submission, bool $isReassessment = false): ?ResponseQualityAssessment
    {
        $academicYear = AcademicYear::where('label', $submission->academic_year)->first();
        
        $submission->loadMissing(['responses', 'timings', 'user']);
        $responses = $submission->responses;

        if ($responses->isEmpty()) {
            return null;
        }

        $yearLevel = $submission->user?->year_level ?? '3rd';
        $categoriesQuery = QuestionCategory::where('year_level', $yearLevel)
            ->with(['questionItems', 'correlatedPairs.itemA', 'correlatedPairs.itemB']);

        if ($academicYear) {
            $categoriesQuery->where('academic_year_id', $academicYear->id);
        }

        $categories = $categoriesQuery->get();

        // 1. Evaluate Detection Methods
        $speedResult = $this->evaluateSpeed($submission, $responses);
        $straightLineResult = $this->evaluateStraightlining($submission, $responses, $categories);
        $contradictionResult = $this->evaluateContradictions($submission, $responses, $categories);

        // 2. Classify: ACCEPTABLE vs FOR REVIEW
        $isFlagged = ($speedResult['status'] === 'flagged')
            || ($straightLineResult['status'] === 'flagged')
            || ($contradictionResult['status'] === 'flagged');

        $classification = $isFlagged ? 'FOR REVIEW' : 'ACCEPTABLE';

        // 3. Assemble human-readable reasons and metrics
        $reasons = [];
        if ($speedResult['status'] === 'flagged') {
            $reasons[] = $speedResult['reason'];
        }
        foreach ($straightLineResult['flags'] as $slFlag) {
            $reasons[] = $slFlag['reason'];
        }
        foreach ($contradictionResult['flags'] as $cdFlag) {
            $reasons[] = $cdFlag['reason'];
        }

        $metrics = [
            'speed' => $speedResult['metrics'],
            'straight_line' => $straightLineResult['metrics'],
            'contradiction' => $contradictionResult['metrics'],
            'total_items_answered' => $responses->count(),
        ];

        // 4. Versioning
        $latestVersion = ResponseQualityAssessment::where('inventory_submission_id', $submission->id)->max('version') ?? 0;
        $version = $latestVersion + 1;

        // 5. Store assessment record
        $assessment = ResponseQualityAssessment::create([
            'inventory_submission_id' => $submission->id,
            'classification' => $classification,
            'speed_status' => $speedResult['status'],
            'straight_line_status' => $straightLineResult['status'],
            'contradiction_status' => $contradictionResult['status'],
            'flag_reasons' => $reasons,
            'metrics' => $metrics,
            'version' => $version,
            'assessed_at' => now(),
        ]);

        // 6. Update denormalized column on submission for indexed fast queries
        $submission->update(['quality_classification' => $classification]);

        // 7. Sync InventoryFlag records for counselor review dashboard workflow
        $this->syncInventoryFlags($submission, $speedResult, $straightLineResult, $contradictionResult);

        return $assessment;
    }

    /**
     * Detection Method A: Unusually Fast Response Detection
     */
    public function evaluateSpeed(InventorySubmission $submission, $responses): array
    {
        $totalItems = $responses->count();
        if ($totalItems === 0) {
            return [
                'status' => 'unavailable',
                'reason' => 'Timing data unavailable: zero answered items.',
                'metrics' => [
                    'avg_seconds_per_item' => null,
                    'total_duration_seconds' => null,
                    'threshold' => (float) $this->getSetting('speed.seconds_per_item_threshold', 1.5),
                    'total_items' => 0,
                    'timing_status' => 'unavailable',
                ],
            ];
        }

        // Timing data extraction
        $sumItemMs = (int) $submission->timings()->sum('time_spent_ms');
        $sessionMs = 0;

        if ($submission->started_at && $submission->submitted_at) {
            if ($submission->submitted_at->lt($submission->started_at)) {
                // Negative time duration indicates clock skew/corrupted timing
                return [
                    'status' => 'unreliable',
                    'reason' => 'Timing data unreliable: submission timestamp preceded session start timestamp.',
                    'metrics' => [
                        'avg_seconds_per_item' => null,
                        'total_duration_seconds' => null,
                        'threshold' => (float) $this->getSetting('speed.seconds_per_item_threshold', 1.5),
                        'total_items' => $totalItems,
                        'timing_status' => 'unreliable',
                    ],
                ];
            }
            $sessionMs = $submission->started_at->diffInMilliseconds($submission->submitted_at);
        }

        $totalTimeMs = $sumItemMs > 0 ? $sumItemMs : $sessionMs;

        // If no positive timing information exists, record reason and DO NOT flag
        if ($totalTimeMs <= 0) {
            return [
                'status' => 'unavailable',
                'reason' => 'Timing data unavailable: completion timestamps or item timings were not recorded.',
                'metrics' => [
                    'avg_seconds_per_item' => null,
                    'total_duration_seconds' => null,
                    'threshold' => (float) $this->getSetting('speed.seconds_per_item_threshold', 1.5),
                    'total_items' => $totalItems,
                    'timing_status' => 'unavailable',
                ],
            ];
        }

        $totalSeconds = $totalTimeMs / 1000.0;
        $avgSeconds = $totalSeconds / $totalItems;
        $threshold = (float) $this->getSetting('speed.seconds_per_item_threshold', 1.5);

        if ($avgSeconds < $threshold) {
            $avgFormatted = number_format($avgSeconds, 2);
            $totalFormatted = number_format($totalSeconds, 1);
            $threshFormatted = number_format($threshold, 2);

            return [
                'status' => 'flagged',
                'reason' => "Unusually fast completion: average of {$avgFormatted}s per question is below minimum threshold of {$threshFormatted}s (total: {$totalFormatted}s across {$totalItems} questions).",
                'metrics' => [
                    'avg_seconds_per_item' => round($avgSeconds, 2),
                    'total_duration_seconds' => round($totalSeconds, 2),
                    'threshold' => $threshold,
                    'total_items' => $totalItems,
                    'timing_status' => 'valid',
                ],
            ];
        }

        return [
            'status' => 'normal',
            'reason' => 'Completion pace within normal range.',
            'metrics' => [
                'avg_seconds_per_item' => round($avgSeconds, 2),
                'total_duration_seconds' => round($totalSeconds, 2),
                'threshold' => $threshold,
                'total_items' => $totalItems,
                'timing_status' => 'valid',
            ],
        ];
    }

    /**
     * Detection Method B: Straightlining Detection
     */
    public function evaluateStraightlining(InventorySubmission $submission, $responses, $categories): array
    {
        $thresholdPercentage = (float) $this->getSetting('straight_line.percentage_threshold', 90.0);
        $groupedByCategory = $responses->groupBy('category');

        $flaggedCategories = [];
        $categoryMetrics = [];

        foreach ($groupedByCategory as $categoryName => $catResponses) {
            $totalInCategory = $catResponses->count();
            if ($totalInCategory === 0) {
                continue;
            }

            $catModel = $categories->firstWhere('name', $categoryName);

            // Rule: Group size check.
            // Batteries with fewer than 5 items are too small to reliably establish an excessive repetitive pattern.
            if ($totalInCategory < 5) {
                $categoryMetrics[$categoryName] = [
                    'total_items' => $totalInCategory,
                    'status' => 'skipped_size_too_small',
                ];
                continue;
            }

            // Rule: Scale applicability check.
            // Demographic, profile, or disparate multiple choice items without a continuous or uniform scale
            // (e.g., profile questions on PWD, civil status, online gaming) should not be treated as straightlining batteries.
            if ($catModel && $catModel->scale_type === 'multiple_choice_unscored') {
                if (strtolower($categoryName) === 'learning_style') {
                    // Learning style uses consistent option position across items (0: Visual, 1: Auditory, 2: Kinesthetic)
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

                    if (empty($positionCounts)) {
                        continue;
                    }
                    $maxFrequency = max($positionCounts);
                    $repeatedValue = 'Option Position ' . (array_search($maxFrequency, $positionCounts) + 1);
                } else {
                    // Skip demographic / unordered disparate multi-choice questions
                    $categoryMetrics[$categoryName] = [
                        'total_items' => $totalInCategory,
                        'status' => 'skipped_non_rating_scale',
                    ];
                    continue;
                }
            } else {
                // Numeric / Likert scales: count identical response values
                $valueCounts = $catResponses->groupBy('response_value')->map->count();
                $maxFrequency = $valueCounts->max();
                $repeatedValue = (string) $valueCounts->sortDesc()->keys()->first();
            }

            $percentage = ($maxFrequency / $totalInCategory) * 100.0;
            $pctFormatted = number_format($percentage, 1);
            $threshFormatted = number_format($thresholdPercentage, 1);

            $categoryMetrics[$categoryName] = [
                'total_items' => $totalInCategory,
                'max_frequency' => $maxFrequency,
                'percentage' => round($percentage, 2),
                'repeated_value' => $repeatedValue,
                'threshold' => $thresholdPercentage,
            ];

            if ($percentage >= $thresholdPercentage) {
                $displayName = ucwords(str_replace(['_', '-'], ' ', $categoryName));
                $flaggedCategories[] = [
                    'category' => $categoryName,
                    'percentage' => round($percentage, 2),
                    'max_frequency' => $maxFrequency,
                    'total_in_category' => $totalInCategory,
                    'repeated_value' => $repeatedValue,
                    'threshold' => $thresholdPercentage,
                    'reason' => "Excessive repetitive answers: {$pctFormatted}% identical responses in {$displayName} ({$maxFrequency} of {$totalInCategory} items answered with '{$repeatedValue}', threshold: {$threshFormatted}%).",
                ];
            }
        }

        return [
            'status' => !empty($flaggedCategories) ? 'flagged' : 'normal',
            'flags' => $flaggedCategories,
            'metrics' => [
                'categories' => $categoryMetrics,
                'threshold' => $thresholdPercentage,
            ],
        ];
    }

    /**
     * Detection Method C: Contradictory Answer Detection
     */
    public function evaluateContradictions(InventorySubmission $submission, $responses, $categories): array
    {
        $keyedResponses = $responses->keyBy(function ($r) {
            return strtolower($r->category) . '|' . $r->item_number;
        });

        $flaggedPairs = [];
        $hasAnyConfiguredPairs = false;
        $evaluatedPairsCount = 0;

        foreach ($categories as $category) {
            if ($category->correlatedPairs->isEmpty()) {
                continue;
            }

            $hasAnyConfiguredPairs = true;

            foreach ($category->correlatedPairs as $pair) {
                $itemA = $category->questionItems->firstWhere('id', $pair->question_item_id_a);
                $itemB = $category->questionItems->firstWhere('id', $pair->question_item_id_b);

                if (!$itemA || !$itemB) {
                    continue;
                }

                $responseA = $keyedResponses->get(strtolower($category->name) . '|' . $itemA->item_number);
                $responseB = $keyedResponses->get(strtolower($category->name) . '|' . $itemB->item_number);

                if (!$responseA || !$responseB) {
                    continue; // Skip if student did not answer both questions
                }

                $evaluatedPairsCount++;

                $optionsA = $itemA->options;
                $optionsB = $itemB->options;

                $valA = is_numeric($responseA->response_value) 
                    ? (float) $responseA->response_value 
                    : (is_array($optionsA) ? array_search($responseA->response_value, $optionsA) : false);

                $valB = is_numeric($responseB->response_value) 
                    ? (float) $responseB->response_value 
                    : (is_array($optionsB) ? array_search($responseB->response_value, $optionsB) : false);

                if ($valA === false || $valB === false) {
                    continue;
                }

                // Determine scale bounds
                $scaleMin = 0.0;
                $scaleMax = 0.0;

                if ($category->scale_type === 'numeric_scale' && $category->scale_min !== null && $category->scale_max !== null) {
                    $scaleMin = (float) $category->scale_min;
                    $scaleMax = (float) $category->scale_max;
                    $maxRange = $scaleMax - $scaleMin;
                } elseif (is_array($optionsA) && count($optionsA) > 1) {
                    $scaleMin = 0.0;
                    $scaleMax = (float) (count($optionsA) - 1);
                    $maxRange = $scaleMax - $scaleMin;
                } else {
                    $maxRange = 0.0;
                }

                if ($maxRange <= 0) {
                    continue;
                }

                $pairThreshold = (float) ($pair->contradiction_threshold ?? $this->getSetting('contradiction.spread_percentage', 75.0));
                $thresholdValue = ($pairThreshold / 100.0) * $maxRange;

                $deviation = 0.0;
                if ($pair->relationship_type === 'similar') {
                    $deviation = abs($valA - $valB);
                } elseif ($pair->relationship_type === 'inverse') {
                    $invertedA = ($scaleMax + $scaleMin) - $valA;
                    $deviation = abs($invertedA - $valB);
                }

                if ($deviation > $thresholdValue) {
                    $categoryDisplay = ucwords(str_replace(['_', '-'], ' ', $category->name));
                    $itemAPromptShort = \Illuminate\Support\Str::limit($itemA->prompt, 50);
                    $itemBPromptShort = \Illuminate\Support\Str::limit($itemB->prompt, 50);

                    $flaggedPairs[] = [
                        'pair_id' => $pair->id,
                        'category' => $category->name,
                        'item_a' => [
                            'item_number' => $itemA->item_number,
                            'prompt' => $itemA->prompt,
                            'response' => $valA,
                        ],
                        'item_b' => [
                            'item_number' => $itemB->item_number,
                            'prompt' => $itemB->prompt,
                            'response' => $valB,
                        ],
                        'relationship_type' => $pair->relationship_type,
                        'deviation' => round($deviation, 2),
                        'threshold_value' => round($thresholdValue, 2),
                        'threshold_percentage' => $pairThreshold,
                        'max_range' => $maxRange,
                        'notes' => $pair->notes,
                        'reason' => "Potentially contradictory answers between Q{$itemA->item_number} (\"{$itemAPromptShort}\") [Answer: {$valA}] and Q{$itemB->item_number} (\"{$itemBPromptShort}\") [Answer: {$valB}] in {$categoryDisplay} (expected {$pair->relationship_type} relationship, deviation: " . round($deviation, 2) . ", threshold: {$pairThreshold}%).",
                    ];
                }
            }
        }

        $status = 'normal';
        if (!$hasAnyConfiguredPairs) {
            $status = 'not_configured';
        } elseif (!empty($flaggedPairs)) {
            $status = 'flagged';
        }

        return [
            'status' => $status,
            'flags' => $flaggedPairs,
            'metrics' => [
                'has_configured_pairs' => $hasAnyConfiguredPairs,
                'evaluated_pairs_count' => $evaluatedPairsCount,
                'flagged_pairs_count' => count($flaggedPairs),
                'flagged_pairs' => $flaggedPairs,
            ],
        ];
    }

    /**
     * Synchronize individual InventoryFlag records for downstream review dashboards.
     */
    protected function syncInventoryFlags(
        InventorySubmission $submission,
        array $speedResult,
        array $straightLineResult,
        array $contradictionResult
    ): void {
        $now = now();
        $flagsToInsert = [];

        // Speed Flag
        if ($speedResult['status'] === 'flagged') {
            $flagsToInsert[] = [
                'inventory_submission_id' => $submission->id,
                'flag_type' => 'speed',
                'category' => null,
                'subscale_tag' => null,
                'details' => json_encode($speedResult['metrics']),
                'is_reviewed' => false,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'reviewer_notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Straightline Flags
        foreach ($straightLineResult['flags'] as $sl) {
            $flagsToInsert[] = [
                'inventory_submission_id' => $submission->id,
                'flag_type' => 'straight_line',
                'category' => $sl['category'],
                'subscale_tag' => null,
                'details' => json_encode([
                    'percentage' => $sl['percentage'],
                    'threshold' => $sl['threshold'],
                    'total_in_category' => $sl['total_in_category'],
                    'max_frequency' => $sl['max_frequency'],
                    'repeated_value' => $sl['repeated_value'],
                ]),
                'is_reviewed' => false,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'reviewer_notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Contradiction Flags
        foreach ($contradictionResult['flags'] as $cd) {
            $flagsToInsert[] = [
                'inventory_submission_id' => $submission->id,
                'flag_type' => 'contradiction',
                'category' => $cd['category'],
                'subscale_tag' => null,
                'details' => json_encode([
                    'pair_id' => $cd['pair_id'],
                    'item_a' => $cd['item_a'],
                    'item_b' => $cd['item_b'],
                    'expected_relationship' => $cd['relationship_type'],
                    'actual_deviation' => $cd['deviation'],
                    'threshold_value' => $cd['threshold_value'],
                    'threshold_percentage' => $cd['threshold_percentage'],
                    'max_range' => $cd['max_range'],
                    'notes' => $cd['notes'],
                ]),
                'is_reviewed' => false,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'reviewer_notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Delete previous flags for this submission and replace with current assessment
        $submission->flags()->delete();

        if (!empty($flagsToInsert)) {
            InventoryFlag::insert($flagsToInsert);
        }
    }
}

