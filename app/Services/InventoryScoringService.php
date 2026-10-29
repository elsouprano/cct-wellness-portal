<?php

namespace App\Services;

use App\Models\InventorySubmission;
use App\Models\InventoryScore;

class InventoryScoringService
{
    /**
     * Compute and materialize scores for a given submission.
     */
    public function computeScores(InventorySubmission $submission)
    {
        $responses = $submission->responses;
        
        $academicYearId = \App\Models\AcademicYear::where('label', $submission->academic_year)->value('id');
        $yearLevel = $submission->user?->year_level ?? '3rd';
        $categories = \App\Models\QuestionCategory::with(['questionItems.subcategory', 'interpretationRanges'])
            ->where('academic_year_id', $academicYearId)
            ->where('year_level', $yearLevel)
            ->get();
            
        // Map category_name and item_number to questionItem via O(1) indexed dictionary
        $categoryMap = [];
        $itemMap = [];
        foreach ($categories as $cat) {
            $categoryMap[$cat->name] = $cat;
            foreach ($cat->questionItems as $item) {
                $itemMap[$cat->name][$item->item_number] = $item;
            }
        }

        foreach ($responses as $response) {
            if (isset($itemMap[$response->category][$response->item_number])) {
                $response->questionItem = $itemMap[$response->category][$response->item_number];
            }
        }

        $groupedByCategory = $responses->groupBy(function ($response) {
            return $response->category;
        });

        $scoresToInsert = [];
        $now = now();

        foreach ($groupedByCategory as $categoryName => $catResponses) {
            $cat = $categoryMap[$categoryName] ?? null;
            if (strtolower($categoryName) === 'dass21') {
                $this->scoreDASS21($submission, $categoryName, $catResponses, $cat, $scoresToInsert, $now);
            } elseif (strtolower($categoryName) === 'cat') {
                $this->scoreCAT($submission, $categoryName, $catResponses, $cat, $scoresToInsert, $now);
            } elseif (strtolower($categoryName) === 'learning_style') {
                $this->scoreLearningStyle($submission, $categoryName, $catResponses, $cat, $scoresToInsert, $now);
            } else {
                $this->scoreStandardCategory($submission, $categoryName, $catResponses, $cat, $scoresToInsert, $now);
            }
        }

        if (!empty($scoresToInsert)) {
            InventoryScore::insert($scoresToInsert);
        }
    }

    private function scoreDASS21(InventorySubmission $submission, string $categoryName, $responses, $category, array &$scoresToInsert, $now)
    {
        $groupedBySubcategory = $responses->groupBy(function ($r) {
            return $r->questionItem?->question_subcategory_id;
        });

        foreach ($groupedBySubcategory as $subcatId => $subResponses) {
            if (empty($subcatId)) continue;
            
            $subcat = $subResponses->first()->questionItem?->subcategory;
            $tagName = $subcat ? $subcat->name : null;

            $rawSum = $subResponses->sum(function ($r) {
                return (int) $r->response_value;
            });

            $scaledScore = $rawSum * 2;
            $interpretation = $this->getInterpretation($scaledScore, $category, $tagName);
            
            $severity = $interpretation ? $interpretation['label'] : $this->getDassSeverity(strtolower($tagName ?? ''), $scaledScore);
            $color = $interpretation ? $interpretation['color_tag'] : null;

            $scoresToInsert[] = [
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $tagName,
                'raw_score' => $rawSum,
                'scaled_score' => $scaledScore,
                'severity_label' => $severity,
                'severity_color' => $color,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        // Overall DASS21 Total
        $totalRaw = $responses->sum(function ($r) {
            return (int) $r->response_value;
        });
        
        $totalScaled = $totalRaw * 2;
        $totalInterpretation = $this->getInterpretation($totalScaled, $category, null);
        
        $scoresToInsert[] = [
            'inventory_submission_id' => $submission->id,
            'category_name' => $categoryName,
            'subscale_name' => null, // Total
            'raw_score' => $totalRaw,
            'scaled_score' => $totalScaled,
            'severity_label' => $totalInterpretation ? $totalInterpretation['label'] : null,
            'severity_color' => $totalInterpretation ? $totalInterpretation['color_tag'] : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function getInterpretation(int $score, $category, string $subscaleTag = null): ?array
    {
        if (!$category || !$category->relationLoaded('interpretationRanges') || $category->interpretationRanges->isEmpty()) {
            return null;
        }

        // Filter ranges by subscale tag. If no subscale is provided, look for ranges with null subscale.
        $ranges = $category->interpretationRanges->filter(function($range) use ($subscaleTag) {
            $rangeSub = trim(strtolower($range->subscale_tag ?? ''));
            $inputSub = trim(strtolower($subscaleTag ?? ''));
            return $rangeSub === $inputSub;
        });

        foreach ($ranges as $range) {
            if ($score >= $range->min_score && $score <= $range->max_score) {
                return [
                    'label' => $range->label,
                    'color_tag' => $range->color_tag
                ];
            }
        }

        return null;
    }

    private function getDassSeverity(string $subscale, int $score): string
    {
        if ($subscale === 'depression') {
            if ($score <= 9) return 'Normal';
            if ($score <= 13) return 'Mild';
            if ($score <= 20) return 'Moderate';
            if ($score <= 27) return 'Severe';
            return 'Extremely Severe';
        } elseif ($subscale === 'anxiety') {
            if ($score <= 7) return 'Normal';
            if ($score <= 9) return 'Mild';
            if ($score <= 14) return 'Moderate';
            if ($score <= 19) return 'Severe';
            return 'Extremely Severe';
        } elseif ($subscale === 'stress') {
            if ($score <= 14) return 'Normal';
            if ($score <= 18) return 'Mild';
            if ($score <= 25) return 'Moderate';
            if ($score <= 33) return 'Severe';
            return 'Extremely Severe';
        }
        
        return 'Unknown';
    }

    private function scoreCAT(InventorySubmission $submission, string $categoryName, $responses, $category, array &$scoresToInsert, $now)
    {
        $groupedBySubcategory = $responses->groupBy(function ($r) {
            return $r->questionItem?->question_subcategory_id;
        });

        // Compute subscales
        foreach ($groupedBySubcategory as $subcatId => $subResponses) {
            if (empty($subcatId)) continue;
            
            $subcat = $subResponses->first()->questionItem?->subcategory;
            $tagName = $subcat ? $subcat->name : null;

            $rawSum = $subResponses->sum(function ($r) {
                return (int) $r->response_value;
            });
            
            $interpretation = $this->getInterpretation($rawSum, $category, $tagName);

            $scoresToInsert[] = [
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $tagName,
                'raw_score' => $rawSum,
                'scaled_score' => null,
                'severity_label' => $interpretation ? $interpretation['label'] : null,
                'severity_color' => $interpretation ? $interpretation['color_tag'] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Overall CAT Total
        $totalRaw = $responses->sum(function ($r) {
            return (int) $r->response_value;
        });

        $interpretation = $this->getInterpretation($totalRaw, $category, null);

        $scoresToInsert[] = [
            'inventory_submission_id' => $submission->id,
            'category_name' => $categoryName,
            'subscale_name' => null, // Total
            'raw_score' => $totalRaw,
            'scaled_score' => null,
            'severity_label' => $interpretation ? $interpretation['label'] : null,
            'severity_color' => $interpretation ? $interpretation['color_tag'] : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function scoreStandardCategory(InventorySubmission $submission, string $categoryName, $responses, $category, array &$scoresToInsert, $now)
    {
        $groupedBySubcategory = $responses->groupBy(function ($r) {
            return $r->questionItem?->question_subcategory_id;
        });

        // Subscales if any
        foreach ($groupedBySubcategory as $subcatId => $subResponses) {
            if (empty($subcatId)) continue;
            
            $subcat = $subResponses->first()->questionItem?->subcategory;
            $tagName = $subcat ? $subcat->name : null;
            
            $rawSum = $subResponses->sum(function ($r) {
                return (int) $r->response_value;
            });

            $interpretation = $this->getInterpretation($rawSum, $category, $tagName);

            $scoresToInsert[] = [
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $tagName,
                'raw_score' => $rawSum,
                'scaled_score' => null,
                'severity_label' => $interpretation ? $interpretation['label'] : null,
                'severity_color' => $interpretation ? $interpretation['color_tag'] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Overall category total
        $totalRaw = $responses->sum(function ($r) {
            return (int) $r->response_value;
        });
        
        $interpretation = $this->getInterpretation($totalRaw, $category, null);

        $scoresToInsert[] = [
            'inventory_submission_id' => $submission->id,
            'category_name' => $categoryName,
            'subscale_name' => null, // Total
            'raw_score' => $totalRaw,
            'scaled_score' => null,
            'severity_label' => $interpretation ? $interpretation['label'] : null,
            'severity_color' => $interpretation ? $interpretation['color_tag'] : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }


    private function scoreLearningStyle(InventorySubmission $submission, string $categoryName, $responses, $category, array &$scoresToInsert, $now)
    {
        $counts = [
            'Visual' => 0,
            'Auditory' => 0,
            'Kinesthetic' => 0,
        ];

        foreach ($responses as $response) {
            $value = $response->response_value;
            $options = $response->questionItem?->options ?: ($category?->default_options ?? []);
            $index = array_search($value, $options);
            
            if ($index === 0) $counts['Visual']++;
            elseif ($index === 1) $counts['Auditory']++;
            elseif ($index === 2) $counts['Kinesthetic']++;
        }

        foreach ($counts as $style => $count) {
            $scoresToInsert[] = [
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $style,
                'raw_score' => $count,
                'scaled_score' => null,
                'severity_label' => null,
                'severity_color' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
    }
}
