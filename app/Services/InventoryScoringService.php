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
        
        $categories = \App\Models\QuestionCategory::with('questionItems')
            ->where('academic_year_id', \App\Models\AcademicYear::where('label', $submission->academic_year)->value('id'))
            ->get();
            
        // Map category_name and item_number to questionItem
        foreach ($responses as $response) {
            $cat = $categories->firstWhere('name', $response->category);
            if ($cat) {
                $item = $cat->questionItems->firstWhere('item_number', $response->item_number);
                $response->questionItem = $item;
            }
        }

        $groupedByCategory = $responses->groupBy(function ($response) {
            return $response->category;
        });

        foreach ($groupedByCategory as $categoryName => $catResponses) {
            $cat = $categories->firstWhere('name', $categoryName);
            if (strtolower($categoryName) === 'dass21') {
                $this->scoreDASS21($submission, $categoryName, $catResponses, $cat);
            } elseif (strtolower($categoryName) === 'cat') {
                $this->scoreCAT($submission, $categoryName, $catResponses, $cat);
            } elseif (strtolower($categoryName) === 'learning_style') {
                $this->scoreLearningStyle($submission, $categoryName, $catResponses);
            } else {
                $this->scoreStandardCategory($submission, $categoryName, $catResponses, $cat);
            }
        }
    }

    private function scoreDASS21(InventorySubmission $submission, string $categoryName, $responses, $category = null)
    {
        $groupedBySubcategory = $responses->groupBy(function ($r) {
            return $r->questionItem->question_subcategory_id;
        });

        foreach ($groupedBySubcategory as $subcatId => $subResponses) {
            if (empty($subcatId)) continue;
            
            $subcat = $subResponses->first()->questionItem->subcategory;
            $tagName = $subcat ? $subcat->name : null;

            $rawSum = $subResponses->sum(function ($r) {
                return (int) $r->response_value;
            });

            $scaledScore = $rawSum * 2;
            $interpretation = $this->getInterpretation($scaledScore, $category, $tagName);
            
            $severity = $interpretation ? $interpretation['label'] : $this->getDassSeverity(strtolower($tagName ?? ''), $scaledScore);
            $color = $interpretation ? $interpretation['color_tag'] : null;

            InventoryScore::create([
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $tagName,
                'raw_score' => $rawSum,
                'scaled_score' => $scaledScore,
                'severity_label' => $severity,
                'severity_color' => $color,
            ]);
        }
        
        // Overall DASS21 Total
        $totalRaw = $responses->sum(function ($r) {
            return (int) $r->response_value;
        });
        
        $totalScaled = $totalRaw * 2;
        $totalInterpretation = $this->getInterpretation($totalScaled, $category, null);
        
        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => $categoryName,
            'subscale_name' => null, // Total
            'raw_score' => $totalRaw,
            'scaled_score' => $totalScaled,
            'severity_label' => $totalInterpretation ? $totalInterpretation['label'] : null,
            'severity_color' => $totalInterpretation ? $totalInterpretation['color_tag'] : null,
        ]);
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

    private function scoreCAT(InventorySubmission $submission, string $categoryName, $responses, $category = null)
    {
        $groupedBySubcategory = $responses->groupBy(function ($r) {
            return $r->questionItem->question_subcategory_id;
        });

        // Compute subscales
        foreach ($groupedBySubcategory as $subcatId => $subResponses) {
            if (empty($subcatId)) continue;
            
            $subcat = $subResponses->first()->questionItem->subcategory;
            $tagName = $subcat ? $subcat->name : null;

            $rawSum = $subResponses->sum(function ($r) {
                return (int) $r->response_value;
            });
            
            $interpretation = $this->getInterpretation($rawSum, $category, $tagName);

            InventoryScore::create([
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $tagName,
                'raw_score' => $rawSum,
                'scaled_score' => null,
                'severity_label' => $interpretation ? $interpretation['label'] : null,
                'severity_color' => $interpretation ? $interpretation['color_tag'] : null,
            ]);
        }

        // Overall CAT Total
        $totalRaw = $responses->sum(function ($r) {
            return (int) $r->response_value;
        });

        $interpretation = $this->getInterpretation($totalRaw, $category, null);

        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => $categoryName,
            'subscale_name' => null, // Total
            'raw_score' => $totalRaw,
            'scaled_score' => null,
            'severity_label' => $interpretation ? $interpretation['label'] : null,
            'severity_color' => $interpretation ? $interpretation['color_tag'] : null,
        ]);
    }

    private function scoreStandardCategory(InventorySubmission $submission, string $categoryName, $responses, $category = null)
    {
        $groupedBySubcategory = $responses->groupBy(function ($r) {
            return $r->questionItem->question_subcategory_id;
        });

        // Subscales if any
        foreach ($groupedBySubcategory as $subcatId => $subResponses) {
            if (empty($subcatId)) continue;
            
            $subcat = $subResponses->first()->questionItem->subcategory;
            $tagName = $subcat ? $subcat->name : null;
            
            $rawSum = $subResponses->sum(function ($r) {
                return (int) $r->response_value;
            });

            $interpretation = $this->getInterpretation($rawSum, $category, $tagName);

            InventoryScore::create([
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $tagName,
                'raw_score' => $rawSum,
                'scaled_score' => null,
                'severity_label' => $interpretation ? $interpretation['label'] : null,
                'severity_color' => $interpretation ? $interpretation['color_tag'] : null,
            ]);
        }

        // Overall category total
        $totalRaw = $responses->sum(function ($r) {
            return (int) $r->response_value;
        });
        
        $interpretation = $this->getInterpretation($totalRaw, $category, null);

        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => $categoryName,
            'subscale_name' => null, // Total
            'raw_score' => $totalRaw,
            'scaled_score' => null,
            'severity_label' => $interpretation ? $interpretation['label'] : null,
            'severity_color' => $interpretation ? $interpretation['color_tag'] : null,
        ]);
    }


    private function scoreLearningStyle(InventorySubmission $submission, string $categoryName, $responses)
    {
        $counts = [
            'Visual' => 0,
            'Auditory' => 0,
            'Kinesthetic' => 0,
        ];

        foreach ($responses as $response) {
            $value = $response->response_value;
            $options = $response->questionItem->options ?? [];
            $index = array_search($value, $options);
            
            if ($index === 0) $counts['Visual']++;
            elseif ($index === 1) $counts['Auditory']++;
            elseif ($index === 2) $counts['Kinesthetic']++;
        }

        foreach ($counts as $style => $count) {
            InventoryScore::create([
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $style,
                'raw_score' => $count,
                'scaled_score' => null,
                'severity_label' => null,
            ]);
        }
    }
}
