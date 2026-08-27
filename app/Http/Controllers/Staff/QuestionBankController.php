<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\QuestionSubcategory;
use App\Models\CorrelatedQuestionPair;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class QuestionBankController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        $categories = collect();
        if ($currentYear) {
            $categories = QuestionCategory::where('academic_year_id', $currentYear->id)
                ->withCount('questionItems')
                ->orderBy('year_level')
                ->orderBy('display_order')
                ->get();
            
            foreach($categories as $cat) {
                $cat->isDynamicallyLocked();
            }
        }
        
        return view('staff.question-bank.index', compact('categories', 'currentYear'));
    }

    public function create(Request $request)
    {
        $currentYear = AcademicYear::where('is_current', true)->firstOrFail();
        $defaultYearLevel = $request->query('year_level', '1st');
        return view('staff.question-bank.create', compact('currentYear', 'defaultYearLevel'));
    }

    public function store(Request $request)
    {
        $currentYear = AcademicYear::where('is_current', true)->firstOrFail();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year_level' => 'required|in:1st,2nd,3rd,4th',
            'display_order' => 'required|integer',
            'instructions' => 'nullable|string',
            'scale_type' => 'required|string|in:numeric_scale,multiple_choice_unscored',
            'scale_min' => 'required_if:scale_type,numeric_scale|nullable|integer',
            'scale_max' => 'required_if:scale_type,numeric_scale|nullable|integer|gt:scale_min',
            'default_options' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('scale_type') === 'multiple_choice_unscored') {
                        $items = $request->input('items', []);
                        $hasDefaultOptions = !empty(trim($value ?? ''));
                        $allItemsHaveOptions = collect($items)->every(function ($item) {
                            return !empty(trim($item['options'] ?? ''));
                        });

                        if (!$hasDefaultOptions && !$allItemsHaveOptions) {
                            $fail('For Multiple Choice categories, you must either provide Default Options or ensure every individual item has custom options provided.');
                        }
                    }
                }
            ],
            'scale_labels' => 'nullable|array',
            'scale_labels.*' => 'nullable|string|max:255',
            'items' => 'required|array',
            'items.*.item_number' => 'required|integer',
            'items.*.prompt' => 'required|string',
            'items.*.options' => 'nullable|string', // newline separated in UI
            'items.*.question_subcategory_id' => 'nullable|string',
            'subcategories' => 'nullable|array',
            'subcategories.*.temp_id' => 'nullable|string',
            'subcategories.*.name' => 'required|string|max:255',
            'subcategories.*.display_order' => 'required|integer',
        ]);

        DB::transaction(function () use ($validated, $currentYear) {
            $defaultOptions = null;
            if ($validated['scale_type'] === 'multiple_choice_unscored' && !empty($validated['default_options'])) {
                $defaultOptions = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $validated['default_options']))));
            }

            $category = QuestionCategory::create([
                'academic_year_id' => $currentYear->id,
                'year_level' => $validated['year_level'],
                'name' => $validated['name'],
                'display_order' => $validated['display_order'],
                'instructions' => $validated['instructions'] ?? '',
                'scale_type' => $validated['scale_type'],
                'scale_min' => $validated['scale_type'] === 'numeric_scale' ? $validated['scale_min'] : null,
                'scale_max' => $validated['scale_type'] === 'numeric_scale' ? $validated['scale_max'] : null,
                'scale_labels' => $validated['scale_type'] === 'numeric_scale' ? ($validated['scale_labels'] ?? null) : null,
                'default_options' => $defaultOptions,
                'is_locked' => false,
            ]);

            // Save subcategories and build temp_id mapping
            $subcatMap = [];
            if (!empty($validated['subcategories'])) {
                foreach ($validated['subcategories'] as $subData) {
                    $sub = QuestionSubcategory::create([
                        'question_category_id' => $category->id,
                        'name' => $subData['name'],
                        'display_order' => $subData['display_order'],
                    ]);
                    if (!empty($subData['temp_id'])) {
                        $subcatMap[$subData['temp_id']] = $sub->id;
                    }
                }
            }

            foreach ($validated['items'] as $itemData) {
                $options = null;
                if (!empty($itemData['options'])) {
                    $parsed = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $itemData['options']))));
                    $options = empty($parsed) ? null : $parsed;
                }

                $subcatId = $itemData['question_subcategory_id'] ?? null;
                if ($subcatId && isset($subcatMap[$subcatId])) {
                    $subcatId = $subcatMap[$subcatId];
                }

                QuestionItem::create([
                    'question_category_id' => $category->id,
                    'item_number' => $itemData['item_number'],
                    'prompt' => $itemData['prompt'],
                    'options' => $options,
                    'question_subcategory_id' => $subcatId,
                ]);
            }
        });

        return redirect()->route('question-bank.index')->with('success', 'Question category created successfully.');
    }

    public function edit(QuestionCategory $category)
    {
        $this->authorize('update', $category);
        $category->load(['questionItems', 'correlatedPairs.itemA', 'correlatedPairs.itemB', 'interpretationRanges', 'subcategories']);
        return view('staff.question-bank.edit', compact('category'));
    }

    public function update(Request $request, QuestionCategory $category)
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year_level' => 'required|in:1st,2nd,3rd,4th',
            'display_order' => 'required|integer',
            'instructions' => 'nullable|string',
            'scale_type' => 'required|string|in:numeric_scale,multiple_choice_unscored',
            'scale_min' => 'required_if:scale_type,numeric_scale|nullable|integer',
            'scale_max' => 'required_if:scale_type,numeric_scale|nullable|integer|gt:scale_min',
            'default_options' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('scale_type') === 'multiple_choice_unscored') {
                        $items = $request->input('items', []);
                        $hasDefaultOptions = !empty(trim($value ?? ''));
                        $allItemsHaveOptions = collect($items)->every(function ($item) {
                            return !empty(trim($item['options'] ?? ''));
                        });

                        if (!$hasDefaultOptions && !$allItemsHaveOptions) {
                            $fail('For Multiple Choice categories, you must either provide Default Options or ensure every individual item has custom options provided.');
                        }
                    }
                }
            ],
            'scale_labels' => 'nullable|array',
            'scale_labels.*' => 'nullable|string|max:255',
            'items' => 'required|array',
            'items.*.id' => 'nullable|integer|exists:question_items,id',
            'items.*.item_number' => 'required|integer',
            'items.*.prompt' => 'required|string',
            'items.*.options' => 'nullable|string',
            'items.*.question_subcategory_id' => 'nullable|string',
            'subcategories' => 'nullable|array',
            'subcategories.*.id' => 'nullable|exists:question_subcategories,id',
            'subcategories.*.temp_id' => 'nullable|string',
            'subcategories.*.name' => 'required|string|max:255',
            'subcategories.*.display_order' => 'required|integer',
            'pairs' => 'nullable|array',
            'pairs.*.id' => 'nullable|exists:correlated_question_pairs,id',
            'pairs.*.question_item_id_a' => 'required|exists:question_items,id',
            'pairs.*.question_item_id_b' => 'required|exists:question_items,id|different:pairs.*.question_item_id_a',
            'pairs.*.relationship_type' => 'required|in:similar,inverse',
            'pairs.*.contradiction_threshold' => 'required|numeric|min:0|max:100',
            'pairs.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $category) {
            $defaultOptions = null;
            if ($validated['scale_type'] === 'multiple_choice_unscored' && !empty($validated['default_options'])) {
                $defaultOptions = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $validated['default_options']))));
            }

            $category->update([
                'year_level' => $validated['year_level'],
                'name' => $validated['name'],
                'display_order' => $validated['display_order'],
                'instructions' => $validated['instructions'] ?? '',
                'scale_type' => $validated['scale_type'],
                'scale_min' => $validated['scale_type'] === 'numeric_scale' ? $validated['scale_min'] : null,
                'scale_max' => $validated['scale_type'] === 'numeric_scale' ? $validated['scale_max'] : null,
                'scale_labels' => $validated['scale_type'] === 'numeric_scale' ? ($validated['scale_labels'] ?? null) : null,
                'default_options' => $defaultOptions,
            ]);

            // Sync subcategories
            $existingSubIds = [];
            $subcatMap = [];
            if (!empty($validated['subcategories'])) {
                foreach ($validated['subcategories'] as $subData) {
                    if (!empty($subData['id'])) {
                        $sub = QuestionSubcategory::find($subData['id']);
                        $sub->update([
                            'name' => $subData['name'],
                            'display_order' => $subData['display_order'],
                        ]);
                        $existingSubIds[] = $sub->id;
                    } else {
                        $sub = QuestionSubcategory::create([
                            'question_category_id' => $category->id,
                            'name' => $subData['name'],
                            'display_order' => $subData['display_order'],
                        ]);
                        $existingSubIds[] = $sub->id;
                        if (!empty($subData['temp_id'])) {
                            $subcatMap[$subData['temp_id']] = $sub->id;
                        }
                    }
                }
            }
            // Remove deleted subcategories
            $category->subcategories()->whereNotIn('id', $existingSubIds)->delete();

            $existingIds = [];
            foreach ($validated['items'] as $itemData) {
                $options = null;
                if (!empty($itemData['options'])) {
                    $parsed = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $itemData['options']))));
                    $options = empty($parsed) ? null : $parsed;
                }

                $subcatId = $itemData['question_subcategory_id'] ?? null;
                if ($subcatId && isset($subcatMap[$subcatId])) {
                    $subcatId = $subcatMap[$subcatId];
                }

                if (!empty($itemData['id'])) {
                    $item = QuestionItem::find($itemData['id']);
                    $item->update([
                        'item_number' => $itemData['item_number'],
                        'prompt' => $itemData['prompt'],
                        'options' => $options,
                        'question_subcategory_id' => $subcatId,
                    ]);
                    $existingIds[] = $item->id;
                } else {
                    $newItem = QuestionItem::create([
                        'question_category_id' => $category->id,
                        'item_number' => $itemData['item_number'],
                        'prompt' => $itemData['prompt'],
                        'options' => $options,
                        'question_subcategory_id' => $subcatId,
                    ]);
                    $existingIds[] = $newItem->id;
                }
            }

            // Delete removed items
            $category->questionItems()->whereNotIn('id', $existingIds)->delete();

            // Handle pairs
            $existingPairIds = [];
            if (!empty($validated['pairs'])) {
                foreach ($validated['pairs'] as $pairData) {
                    // Check if items belong to this category
                    $itemA = QuestionItem::find($pairData['question_item_id_a']);
                    $itemB = QuestionItem::find($pairData['question_item_id_b']);
                    
                    if ($itemA->question_category_id != $category->id || $itemB->question_category_id != $category->id) {
                        continue; // Skip invalid pairs
                    }

                    if (!empty($pairData['id'])) {
                        $pair = \App\Models\CorrelatedQuestionPair::find($pairData['id']);
                        if ($pair && $pair->question_category_id == $category->id) {
                            $pair->update([
                                'question_item_id_a' => $pairData['question_item_id_a'],
                                'question_item_id_b' => $pairData['question_item_id_b'],
                                'relationship_type' => $pairData['relationship_type'],
                                'contradiction_threshold' => $pairData['contradiction_threshold'],
                                'notes' => $pairData['notes'] ?? '',
                            ]);
                            $existingPairIds[] = $pair->id;
                        }
                    } else {
                        // Avoid duplicates if same item pair exists
                        $existingPair = \App\Models\CorrelatedQuestionPair::where('question_category_id', $category->id)
                            ->where('question_item_id_a', $pairData['question_item_id_a'])
                            ->where('question_item_id_b', $pairData['question_item_id_b'])
                            ->first();

                        if ($existingPair) {
                            $existingPair->update([
                                'relationship_type' => $pairData['relationship_type'],
                                'contradiction_threshold' => $pairData['contradiction_threshold'],
                                'notes' => $pairData['notes'] ?? '',
                            ]);
                            $existingPairIds[] = $existingPair->id;
                        } else {
                            $newPair = \App\Models\CorrelatedQuestionPair::create([
                                'question_category_id' => $category->id,
                                'question_item_id_a' => $pairData['question_item_id_a'],
                                'question_item_id_b' => $pairData['question_item_id_b'],
                                'relationship_type' => $pairData['relationship_type'],
                                'contradiction_threshold' => $pairData['contradiction_threshold'],
                                'notes' => $pairData['notes'] ?? '',
                                'created_by' => auth()->id(),
                            ]);
                            $existingPairIds[] = $newPair->id;
                        }
                    }
                }
            }

            // Delete removed pairs
            $category->correlatedPairs()->whereNotIn('id', $existingPairIds)->delete();
        });

        return redirect()->route('question-bank.index')->with('success', 'Question category updated successfully.');
    }

    public function destroy(QuestionCategory $category)
    {
        $this->authorize('delete', $category);
        
        $category->questionItems()->delete();
        $category->delete();

        return redirect()->route('question-bank.index')->with('success', 'Question category deleted successfully.');
    }
}
