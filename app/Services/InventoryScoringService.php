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
            if (strtolower($categoryName) === 'dass21') {
                $this->scoreDASS21($submission, $categoryName, $catResponses);
            } elseif (strtolower($categoryName) === 'cat') {
                $this->scoreCAT($submission, $categoryName, $catResponses);
            } elseif (strtolower($categoryName) === 'learning_style') {
                $this->scoreLearningStyle($submission, $categoryName, $catResponses);
            } else {
                $this->scoreStandardCategory($submission, $categoryName, $catResponses);
            }
        }
    }

    private function scoreDASS21(InventorySubmission $submission, string $categoryName, $responses)
    {
        $groupedByTag = $responses->groupBy(function ($r) {
            return $r->questionItem->subscale_tag;
        });

        foreach ($groupedByTag as $tag => $tagResponses) {
            if (empty($tag)) continue;

            $rawSum = $tagResponses->sum(function ($r) {
                return (int) $r->response_value;
            });

            $scaledScore = $rawSum * 2;
            $severity = $this->getDassSeverity($tag, $scaledScore);

            InventoryScore::create([
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $tag,
                'raw_score' => $rawSum,
                'scaled_score' => $scaledScore,
                'severity_label' => $severity,
            ]);
        }
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

    private function scoreCAT(InventorySubmission $submission, string $categoryName, $responses)
    {
        $groupedByTag = $responses->groupBy(function ($r) {
            return $r->questionItem->subscale_tag;
        });

        // Compute subscales
        foreach ($groupedByTag as $tag => $tagResponses) {
            if (empty($tag)) continue;

            $rawSum = $tagResponses->sum(function ($r) {
                return (int) $r->response_value;
            });

            InventoryScore::create([
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $tag,
                'raw_score' => $rawSum,
                'scaled_score' => null,
                'severity_label' => null,
            ]);
        }

        // Overall CAT Total
        $totalRaw = $responses->sum(function ($r) {
            return (int) $r->response_value;
        });

        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => $categoryName,
            'subscale_name' => null, // Total
            'raw_score' => $totalRaw,
            'scaled_score' => null,
            'severity_label' => null,
        ]);
    }

    private function scoreStandardCategory(InventorySubmission $submission, string $categoryName, $responses)
    {
        $groupedByTag = $responses->groupBy(function ($r) {
            return $r->questionItem->subscale_tag;
        });

        // Subscales if any
        foreach ($groupedByTag as $tag => $tagResponses) {
            if (empty($tag)) continue;
            
            $rawSum = $tagResponses->sum(function ($r) {
                return (int) $r->response_value;
            });

            InventoryScore::create([
                'inventory_submission_id' => $submission->id,
                'category_name' => $categoryName,
                'subscale_name' => $tag,
                'raw_score' => $rawSum,
                'scaled_score' => null,
                'severity_label' => null,
            ]);
        }

        // Overall Total
        $totalRaw = $responses->sum(function ($r) {
            return (int) $r->response_value;
        });

        InventoryScore::create([
            'inventory_submission_id' => $submission->id,
            'category_name' => $categoryName,
            'subscale_name' => null, // Total
            'raw_score' => $totalRaw,
            'scaled_score' => null,
            'severity_label' => null,
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
