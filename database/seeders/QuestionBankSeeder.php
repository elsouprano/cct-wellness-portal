<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\AcademicYear;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use Illuminate\Support\Facades\File;

class QuestionBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::create([
            'label' => '2025-2026',
            'is_current' => true
        ]);

        $jsonPath = resource_path('data/inventory-items.json');
        if (!File::exists($jsonPath)) {
            $this->command->error('inventory-items.json not found!');
            return;
        }

        $config = json_decode(File::get($jsonPath), true);

        $displayOrder = 1;
        foreach ($config as $categoryKey => $data) {
            $category = QuestionCategory::create([
                'academic_year_id' => $academicYear->id,
                'year_level' => '3rd',
                'name' => $categoryKey,
                'display_order' => $displayOrder++,
                'instructions' => $data['instructions'] ?? '',
                'scale_type' => $data['scale_type'] ?? 'multiple_choice_unscored',
                'scale_min' => $data['scale_min'] ?? null,
                'scale_max' => $data['scale_max'] ?? null,
                'is_locked' => false
            ]);

            foreach ($data['items'] as $itemData) {
                $itemNumber = (int)$itemData['item_number'];
                $subscaleTag = null;

                if ($categoryKey === 'dass21') {
                    if (in_array($itemNumber, [3, 5, 10, 13, 16, 17, 21])) {
                        $subscaleTag = 'depression';
                    } elseif (in_array($itemNumber, [2, 4, 7, 9, 15, 19, 20])) {
                        $subscaleTag = 'anxiety';
                    } elseif (in_array($itemNumber, [1, 6, 8, 11, 12, 14, 18])) {
                        $subscaleTag = 'stress';
                    }
                } elseif ($categoryKey === 'cat') {
                    if ($itemNumber >= 4 && $itemNumber <= 8) {
                        $subscaleTag = 'worried_cluster';
                    } elseif ($itemNumber >= 9 && $itemNumber <= 13) {
                        $subscaleTag = 'liked_cluster';
                    }
                }

                QuestionItem::create([
                    'question_category_id' => $category->id,
                    'item_number' => $itemNumber,
                    'prompt' => $itemData['prompt'],
                    'options' => $itemData['options'] ?? null,
                    'subscale_tag' => $subscaleTag
                ]);
            }
        }
    }
}
