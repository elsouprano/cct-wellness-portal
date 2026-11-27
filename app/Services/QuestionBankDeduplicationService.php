<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\CategoryYearLevel;
use App\Models\CorrelatedQuestionPair;
use App\Models\InterpretationRange;
use App\Models\InventoryItemTiming;
use App\Models\InventoryResponse;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\QuestionSubcategory;
use App\Support\QuestionTextNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class QuestionBankDeduplicationService
{
    private const BTQ_EVENT_TOPICS = [
        1 => 'war zone or combat casualty exposure',
        2 => 'serious vehicle or work accident',
        3 => 'natural or technological disaster',
        4 => 'life-threatening illness',
        5 => 'childhood physical punishment or abuse',
        6 => 'physical attack or mugging',
        7 => 'unwanted sexual contact',
        8 => 'other serious injury or life-danger situation',
        9 => 'violent death of a close family member or friend',
        10 => 'witnessing serious injury or death',
    ];

    /**
     * Export affected Question Bank rows to storage/app/backups/ first,
     * then merge duplicate categories and clean unreferenced duplicate items in one transaction.
     *
     * @return array<string, mixed>
     */
    public function deduplicate(): array
    {
        $backupPath = $this->exportBackupSnapshot();

        $summary = [
            'backup_path' => $backupPath,
            'categories_merged' => 0,
            'redundant_categories_deleted' => 0,
            'duplicate_items_deleted' => 0,
            'duplicate_items_preserved_with_responses' => 0,
            'btq_followups_disambiguated' => 0,
        ];

        DB::transaction(function () use (&$summary) {
            $academicYears = AcademicYear::orderBy('id')->get();

            foreach ($academicYears as $ay) {
                $ayLabel = $ay->label;

                // 1. Merge duplicate QuestionCategory rows sharing the same code in this Academic Year
                $categories = QuestionCategory::with([
                    'questionItems',
                    'subcategories',
                    'yearLevelAssignments',
                    'interpretationRanges',
                    'correlatedPairs',
                ])
                    ->where('academic_year_id', $ay->id)
                    ->orderBy('id')
                    ->get();

                $byCode = $categories->groupBy(fn (QuestionCategory $c) => QuestionTextNormalizer::normalizeCode($c->name));

                foreach ($byCode as $code => $group) {
                    if ($group->count() > 1) {
                        $canonical = $this->pickCanonicalCategory($group);

                        // Collect all assigned year levels & display_order across the group
                        $yearLevelOrders = [];
                        foreach ($group as $cat) {
                            foreach ($cat->yearLevelAssignments as $assign) {
                                if (! isset($yearLevelOrders[$assign->year_level])) {
                                    $yearLevelOrders[$assign->year_level] = $assign->display_order ?? $cat->display_order ?? 1;
                                }
                            }
                            if ($cat->year_level && ! isset($yearLevelOrders[$cat->year_level])) {
                                $yearLevelOrders[$cat->year_level] = $cat->display_order ?? 1;
                            }
                        }

                        // Preserve any extra multiple-choice options from sibling variants (e.g. "Others" on profile Item #2)
                        $canonicalItemsByNum = $canonical->questionItems->keyBy('item_number');
                        foreach ($group as $sibling) {
                            if ($sibling->id === $canonical->id) {
                                continue;
                            }

                            foreach ($sibling->questionItems as $sibItem) {
                                $canonItem = $canonicalItemsByNum->get($sibItem->item_number);
                                if ($canonItem && is_array($canonItem->options) && is_array($sibItem->options)) {
                                    $mergedOpts = $canonItem->options;
                                    $changed = false;
                                    foreach ($sibItem->options as $opt) {
                                        if (! in_array($opt, $mergedOpts, true)) {
                                            $mergedOpts[] = $opt;
                                            $changed = true;
                                        }
                                    }
                                    if ($changed) {
                                        $canonItem->update(['options' => array_values($mergedOpts)]);
                                    }
                                }
                            }

                            // Move interpretation ranges if canonical doesn't already have them
                            if ($canonical->interpretationRanges->isEmpty() && $sibling->interpretationRanges->isNotEmpty()) {
                                InterpretationRange::where('question_category_id', $sibling->id)
                                    ->update(['question_category_id' => $canonical->id]);
                                $canonical->load('interpretationRanges');
                            }

                            // Move correlated pairs if canonical doesn't already have them
                            if ($canonical->correlatedPairs->isEmpty() && $sibling->correlatedPairs->isNotEmpty()) {
                                $sibItemsById = $sibling->questionItems->keyBy('id');
                                foreach ($sibling->correlatedPairs as $pair) {
                                    $numA = $sibItemsById->get($pair->question_item_id_a)?->item_number;
                                    $numB = $sibItemsById->get($pair->question_item_id_b)?->item_number;
                                    $canonA = $numA ? $canonicalItemsByNum->get($numA) : null;
                                    $canonB = $numB ? $canonicalItemsByNum->get($numB) : null;
                                    if ($canonA && $canonB) {
                                        CorrelatedQuestionPair::create([
                                            'question_category_id' => $canonical->id,
                                            'question_item_id_a' => $canonA->id,
                                            'question_item_id_b' => $canonB->id,
                                            'relationship_type' => $pair->relationship_type,
                                            'contradiction_threshold' => $pair->contradiction_threshold,
                                            'notes' => $pair->notes,
                                            'created_by' => $pair->created_by,
                                        ]);
                                    }
                                }
                                $canonical->load('correlatedPairs');
                            }

                            // Delete redundant sibling category and its duplicate child rows (never touching responses!)
                            CorrelatedQuestionPair::where('question_category_id', $sibling->id)->delete();
                            InterpretationRange::where('question_category_id', $sibling->id)->delete();
                            QuestionItem::where('question_category_id', $sibling->id)->delete();
                            QuestionSubcategory::where('question_category_id', $sibling->id)->delete();
                            CategoryYearLevel::where('question_category_id', $sibling->id)->delete();
                            $sibling->delete();

                            $summary['redundant_categories_deleted']++;
                        }

                        // Sync ordered year-level assignments onto canonical row
                        $orderedLevels = $this->sortYearLevels(array_keys($yearLevelOrders));
                        foreach ($orderedLevels as $yl) {
                            CategoryYearLevel::updateOrCreate(
                                [
                                    'question_category_id' => $canonical->id,
                                    'year_level' => $yl,
                                ],
                                [
                                    'academic_year_id' => $ay->id,
                                    'display_order' => $yearLevelOrders[$yl] ?? $canonical->display_order ?? 1,
                                ]
                            );
                        }

                        $canonical->update([
                            'year_level' => $orderedLevels[0] ?? $canonical->year_level,
                            'display_name' => $canonical->display_name ?: $canonical->label(),
                        ]);

                        $summary['categories_merged']++;
                    } else {
                        // Single-row category: ensure CategoryYearLevel row exists with academic_year_id
                        /** @var QuestionCategory $single */
                        $single = $group->first();
                        if ($single->year_level) {
                            CategoryYearLevel::firstOrCreate(
                                [
                                    'question_category_id' => $single->id,
                                    'year_level' => $single->year_level,
                                ],
                                [
                                    'academic_year_id' => $ay->id,
                                    'display_order' => $single->display_order ?? 1,
                                ]
                            );
                        }
                        CategoryYearLevel::where('question_category_id', $single->id)
                            ->whereNull('academic_year_id')
                            ->update(['academic_year_id' => $ay->id]);
                    }
                }

                // 2. Clean intra-category duplicate items (and disambiguate clinical BTQ follow-ups when unreferenced)
                $remainingCategories = QuestionCategory::with(['questionItems', 'correlatedPairs'])
                    ->where('academic_year_id', $ay->id)
                    ->orderBy('id')
                    ->get();

                foreach ($remainingCategories as $category) {
                    if (QuestionTextNormalizer::normalizeCode($category->name) === 'btq') {
                        $summary['btq_followups_disambiguated'] += $this->disambiguateBtqIfUnreferenced($category, $ayLabel);
                        $category->load('questionItems');
                    }

                    $items = $category->questionItems->sortBy('item_number')->values();
                    $seenNorms = [];
                    $itemsDeletedInCategory = false;

                    foreach ($items as $item) {
                        $norm = QuestionTextNormalizer::normalize($item->prompt);
                        if ($norm === '') {
                            continue;
                        }

                        if (! isset($seenNorms[$norm])) {
                            $seenNorms[$norm] = $item;
                            continue;
                        }

                        // Exact normalized duplicate inside the same category!
                        // Check if ANY response or timing in this academic year references this category (so neither the duplicate item nor subsequent renumbered items affect stored item_numbers)
                        $categoryHasAnyResponsesInAy = $this->categoryHasResponsesOrTimingsInYear($category->name, $ayLabel);
                        $itemReferencedByPair = CorrelatedQuestionPair::where('question_item_id_a', $item->id)
                            ->orWhere('question_item_id_b', $item->id)
                            ->exists();

                        if ($categoryHasAnyResponsesInAy || $itemReferencedByPair) {
                            $summary['duplicate_items_preserved_with_responses']++;
                            continue;
                        }

                        $item->delete();
                        $itemsDeletedInCategory = true;
                        $summary['duplicate_items_deleted']++;
                    }

                    // If an unreferenced duplicate item was deleted in a category with 0 responses in this AY, resequence item_number 1..N cleanly
                    if ($itemsDeletedInCategory && ! $this->categoryHasResponsesOrTimingsInYear($category->name, $ayLabel)) {
                        $surviving = QuestionItem::where('question_category_id', $category->id)
                            ->orderBy('item_number')
                            ->orderBy('id')
                            ->get();

                        foreach ($surviving as $idx => $survivingItem) {
                            $targetNumber = $idx + 1;
                            if ((int) $survivingItem->item_number !== $targetNumber) {
                                $survivingItem->update(['item_number' => $targetNumber]);
                            }
                        }
                    }
                }
            }
        });

        \App\Support\InstrumentLabel::clearCache();

        return $summary;
    }

    private function pickCanonicalCategory($group): QuestionCategory
    {
        return $group->sort(function (QuestionCategory $a, QuestionCategory $b) {
            $rangesCmp = $b->interpretationRanges->count() <=> $a->interpretationRanges->count();
            if ($rangesCmp !== 0) {
                return $rangesCmp;
            }

            $pairsCmp = $b->correlatedPairs->count() <=> $a->correlatedPairs->count();
            if ($pairsCmp !== 0) {
                return $pairsCmp;
            }

            // Prefer upper-year variant (4th / 3rd / 2nd) where historical responses were recorded
            $priority = ['4th' => 4, '3rd' => 3, '2nd' => 2, '1st' => 1];
            $pA = $priority[$a->year_level ?? ''] ?? 0;
            $pB = $priority[$b->year_level ?? ''] ?? 0;
            if ($pA !== $pB) {
                return $pB <=> $pA;
            }

            return $a->id <=> $b->id;
        })->first();
    }

    /**
     * @param array<int, string> $levels
     * @return array<int, string>
     */
    private function sortYearLevels(array $levels): array
    {
        $order = ['1st' => 1, '2nd' => 2, '3rd' => 3, '4th' => 4];
        usort($levels, fn (string $a, string $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));

        return array_values(array_unique($levels));
    }

    private function categoryHasResponsesOrTimingsInYear(string $categoryCode, ?string $academicYearLabel): bool
    {
        $hasResp = InventoryResponse::where('category', $categoryCode)
            ->when($academicYearLabel, fn ($q) => $q->whereHas('submission', fn ($s) => $s->where('academic_year', $academicYearLabel)))
            ->exists();

        if ($hasResp) {
            return true;
        }

        return InventoryItemTiming::where('category', $categoryCode)
            ->when($academicYearLabel, fn ($q) => $q->whereHas('submission', fn ($s) => $s->where('academic_year', $academicYearLabel)))
            ->exists();
    }

    private function disambiguateBtqIfUnreferenced(QuestionCategory $category, ?string $academicYearLabel): int
    {
        if ($this->categoryHasResponsesOrTimingsInYear($category->name, $academicYearLabel)) {
            return 0;
        }

        $items = $category->questionItems()->orderBy('item_number')->get();
        $eventIdx = 0;
        $updated = 0;

        foreach ($items as $item) {
            $raw = trim((string) $item->prompt);
            $clean = ltrim($raw, ". \t\n\r\0\x0B");

            if (stripos($clean, 'Regarding Event ') === 0) {
                continue;
            }

            if (stripos($clean, 'If the event happened') === 0) {
                $topic = self::BTQ_EVENT_TOPICS[$eventIdx] ?? "trauma event {$eventIdx}";
                $newPrompt = "Regarding Event {$eventIdx} ({$topic}): {$clean}";
                $item->update(['prompt' => $newPrompt]);
                $updated++;
            } else {
                $eventIdx++;
                if ($clean !== $raw) {
                    $item->update(['prompt' => $clean]);
                }
            }
        }

        return $updated;
    }

    private function exportBackupSnapshot(): string
    {
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $timestamp = now()->format('Ymd_His_u');
        $filePath = $backupDir . DIRECTORY_SEPARATOR . "question_bank_dedup_backup_{$timestamp}.json";

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'academic_years' => AcademicYear::orderBy('id')->get()->toArray(),
            'question_categories' => QuestionCategory::orderBy('id')->get()->toArray(),
            'category_year_levels' => CategoryYearLevel::orderBy('id')->get()->toArray(),
            'question_subcategories' => QuestionSubcategory::orderBy('id')->get()->toArray(),
            'question_items' => QuestionItem::orderBy('id')->get()->toArray(),
            'interpretation_ranges' => InterpretationRange::orderBy('id')->get()->toArray(),
            'correlated_question_pairs' => CorrelatedQuestionPair::orderBy('id')->get()->toArray(),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        File::put($filePath, $json);

        if (! File::exists($filePath) || File::size($filePath) === 0) {
            throw new \RuntimeException("Failed to write Question Bank backup snapshot to {$filePath}.");
        }

        return $filePath;
    }
}
