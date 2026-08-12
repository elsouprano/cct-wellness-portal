<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InterpretationRangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dassCategories = \App\Models\QuestionCategory::where('name', 'DASS21')->get();

        if ($dassCategories->isEmpty()) {
            return;
        }

        $bands = [
            'depression' => [
                ['min' => 0, 'max' => 9, 'label' => 'Normal', 'color' => 'green', 'order' => 1],
                ['min' => 10, 'max' => 13, 'label' => 'Mild', 'color' => 'yellow', 'order' => 2],
                ['min' => 14, 'max' => 20, 'label' => 'Moderate', 'color' => 'orange', 'order' => 3],
                ['min' => 21, 'max' => 27, 'label' => 'Severe', 'color' => 'red', 'order' => 4],
                ['min' => 28, 'max' => 999, 'label' => 'Extremely Severe', 'color' => 'purple', 'order' => 5],
            ],
            'anxiety' => [
                ['min' => 0, 'max' => 7, 'label' => 'Normal', 'color' => 'green', 'order' => 1],
                ['min' => 8, 'max' => 9, 'label' => 'Mild', 'color' => 'yellow', 'order' => 2],
                ['min' => 10, 'max' => 14, 'label' => 'Moderate', 'color' => 'orange', 'order' => 3],
                ['min' => 15, 'max' => 19, 'label' => 'Severe', 'color' => 'red', 'order' => 4],
                ['min' => 20, 'max' => 999, 'label' => 'Extremely Severe', 'color' => 'purple', 'order' => 5],
            ],
            'stress' => [
                ['min' => 0, 'max' => 14, 'label' => 'Normal', 'color' => 'green', 'order' => 1],
                ['min' => 15, 'max' => 18, 'label' => 'Mild', 'color' => 'yellow', 'order' => 2],
                ['min' => 19, 'max' => 25, 'label' => 'Moderate', 'color' => 'orange', 'order' => 3],
                ['min' => 26, 'max' => 33, 'label' => 'Severe', 'color' => 'red', 'order' => 4],
                ['min' => 34, 'max' => 999, 'label' => 'Extremely Severe', 'color' => 'purple', 'order' => 5],
            ]
        ];

        foreach ($dassCategories as $category) {
            foreach ($bands as $subscale => $ranges) {
                foreach ($ranges as $range) {
                    \App\Models\InterpretationRange::updateOrCreate(
                        [
                            'question_category_id' => $category->id,
                            'subscale_tag' => $subscale,
                            'min_score' => $range['min'],
                            'max_score' => $range['max'],
                        ],
                        [
                            'label' => $range['label'],
                            'color_tag' => $range['color'],
                            'display_order' => $range['order'],
                            'is_official_default' => true,
                            'description' => 'Official DASS-21 clinical cutoff for ' . ucfirst($subscale) . '.',
                        ]
                    );
                }
            }
        }
    }
}
