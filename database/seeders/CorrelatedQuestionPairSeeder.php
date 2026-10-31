<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\CorrelatedQuestionPair;
use App\Models\User;

class CorrelatedQuestionPairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds explicitly documented, psychometrically grounded correlated and opposing question pairs.
     */
    public function run(): void
    {
        $admin = User::where('role', 'system_admin')->first();
        $adminId = $admin ? $admin->id : 1;

        // Categories to configure documented pairs for
        $categories = QuestionCategory::with('questionItems')->get();

        foreach ($categories as $category) {
            $catName = strtolower($category->name);

            if ($catName === 'cat') {
                // College Adjustment Test (CAT) documented opposing statements
                $this->seedPair($category, 8, 13, 'inverse', 75.0, 'Opposing college outlook: Worried about college in general vs Liked college in general', $adminId);
                $this->seedPair($category, 17, 19, 'inverse', 75.0, 'Opposing emotional valence: Felt depressed vs Felt good about yourself', $adminId);
                $this->seedPair($category, 2, 11, 'inverse', 75.0, 'Opposing family separation adjustment: Missed home vs Liked being away from parents', $adminId);
            } elseif ($catName === 'ars30') {
                // Academic Resilience Scale (ARS-30) documented opposing statements
                $this->seedPair($category, 1, 2, 'inverse', 75.0, 'Opposing feedback response: Would not accept tutor feedback vs Would use feedback to improve', $adminId);
                $this->seedPair($category, 3, 16, 'inverse', 75.0, 'Opposing persistence: Would just give up vs Would keep trying', $adminId);
            } elseif ($catName === 'erq') {
                // Emotion Regulation Questionnaire (ERQ) documented convergent subscale items
                $this->seedPair($category, 2, 6, 'similar', 75.0, 'Convergent expressive suppression: Keep emotions to myself vs Control emotions by not expressing', $adminId);
            }
        }
    }

    protected function seedPair(
        QuestionCategory $category,
        int $itemNumberA,
        int $itemNumberB,
        string $relationshipType,
        float $threshold,
        string $notes,
        int $createdById
    ): void {
        $itemA = $category->questionItems->firstWhere('item_number', $itemNumberA);
        $itemB = $category->questionItems->firstWhere('item_number', $itemNumberB);

        if (!$itemA || !$itemB) {
            return;
        }

        CorrelatedQuestionPair::firstOrCreate(
            [
                'question_category_id' => $category->id,
                'question_item_id_a' => $itemA->id,
                'question_item_id_b' => $itemB->id,
            ],
            [
                'relationship_type' => $relationshipType,
                'contradiction_threshold' => $threshold,
                'notes' => $notes,
                'created_by' => $createdById,
            ]
        );
    }
}

