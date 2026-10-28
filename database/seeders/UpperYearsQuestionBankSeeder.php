<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\QuestionSubcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UpperYearsQuestionBankSeeder extends Seeder
{
    /**
     * Run the database seeds for 2nd, 3rd, and 4th Year Question Banks.
     */
    public function run(): void
    {
        $jsonPath = resource_path('data/inventory-items-upper-years.json');
        if (! File::exists($jsonPath)) {
            if ($this->command) {
                $this->command->error('inventory-items-upper-years.json not found!');
            }

            return;
        }

        $config = json_decode(File::get($jsonPath), true);

        // Target Academic Years: Active 2025-2026 and Form's 2026-2027
        $academicYears = [
            AcademicYear::firstOrCreate(['label' => '2025-2026'], ['is_current' => true]),
            AcademicYear::firstOrCreate(['label' => '2026-2027'], ['is_current' => false]),
        ];

        $targetYearLevels = ['2nd', '3rd', '4th'];

        foreach ($academicYears as $academicYear) {
            foreach ($targetYearLevels as $yearLevel) {
                if ($this->command) {
                    $this->command->info("Seeding {$yearLevel} Year Question Bank for Academic Year: {$academicYear->label}...");
                }

                DB::transaction(function () use ($academicYear, $yearLevel, $config) {
                    $displayOrder = 1;

                    foreach ($config as $categoryKey => $data) {
                        $category = QuestionCategory::firstOrCreate(
                            [
                                'academic_year_id' => $academicYear->id,
                                'year_level' => $yearLevel,
                                'name' => $categoryKey,
                            ],
                            [
                                'display_order' => $displayOrder++,
                                'instructions' => $data['instructions'] ?? '',
                                'scale_type' => $data['scale_type'] ?? 'multiple_choice_unscored',
                                'scale_min' => $data['scale_min'] ?? null,
                                'scale_max' => $data['scale_max'] ?? null,
                                'scale_labels' => $data['scale_labels'] ?? null,
                                'default_options' => $data['default_options'] ?? null,
                                'is_locked' => false,
                            ]
                        );

                        // Ensure category attributes match current specification
                        $category->update([
                            'instructions' => $data['instructions'] ?? '',
                            'scale_type' => $data['scale_type'] ?? 'multiple_choice_unscored',
                            'scale_min' => $data['scale_min'] ?? null,
                            'scale_max' => $data['scale_max'] ?? null,
                            'scale_labels' => $data['scale_labels'] ?? null,
                            'default_options' => $data['default_options'] ?? null,
                        ]);

                        // Seed subcategories
                        $subcatMap = [];
                        if (! empty($data['subcategories'])) {
                            $subOrder = 1;
                            foreach ($data['subcategories'] as $subKey => $subData) {
                                $subcatName = is_array($subData) ? ($subData['name'] ?? $subKey) : $subData;
                                $order = is_array($subData) ? ($subData['display_order'] ?? $subOrder++) : $subOrder++;

                                $subcategory = QuestionSubcategory::firstOrCreate(
                                    [
                                        'question_category_id' => $category->id,
                                        'name' => $subcatName,
                                    ],
                                    [
                                        'display_order' => $order,
                                    ]
                                );
                                $subcatMap[$subKey] = $subcategory->id;
                                $subcatMap[$subcatName] = $subcategory->id;
                            }
                        }

                        // Seed items
                        foreach ($data['items'] as $itemData) {
                            $itemNumber = (int) $itemData['item_number'];
                            $subscaleTag = $itemData['subscale_tag'] ?? null;
                            $subcatId = null;

                            if ($subscaleTag) {
                                $subcatId = $subcatMap[$subscaleTag] ?? null;
                            }

                            QuestionItem::updateOrCreate(
                                [
                                    'question_category_id' => $category->id,
                                    'item_number' => $itemNumber,
                                ],
                                [
                                    'question_subcategory_id' => $subcatId,
                                    'prompt' => $itemData['prompt'],
                                    'options' => $itemData['options'] ?? null,
                                    'subscale_tag' => $subscaleTag,
                                ]
                            );
                        }
                    }
                });
            }
        }

        if ($this->command) {
            $this->command->info('2nd-4th Year Question Bank seeding completed successfully!');
        }
    }
}
