<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
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
            'scale_type' => 'required|string',
            'items' => 'required|array',
            'items.*.item_number' => 'required|integer',
            'items.*.prompt' => 'required|string',
            'items.*.options' => 'nullable|string', // comma separated in UI
            'items.*.subscale_tag' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $currentYear) {
            $category = QuestionCategory::create([
                'academic_year_id' => $currentYear->id,
                'year_level' => $validated['year_level'],
                'name' => $validated['name'],
                'display_order' => $validated['display_order'],
                'instructions' => $validated['instructions'] ?? '',
                'scale_type' => $validated['scale_type'],
                'is_locked' => false,
            ]);

            foreach ($validated['items'] as $itemData) {
                $options = null;
                if (!empty($itemData['options'])) {
                    $options = array_map('trim', explode(',', $itemData['options']));
                }

                QuestionItem::create([
                    'question_category_id' => $category->id,
                    'item_number' => $itemData['item_number'],
                    'prompt' => $itemData['prompt'],
                    'options' => $options,
                    'subscale_tag' => $itemData['subscale_tag'] ?? null,
                ]);
            }
        });

        return redirect()->route('question-bank.index')->with('success', 'Question category created successfully.');
    }

    public function edit(QuestionCategory $category)
    {
        $this->authorize('update', $category);
        $category->load(['questionItems', 'correlatedPairs.itemA', 'correlatedPairs.itemB']);
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
            'scale_type' => 'required|string',
            'items' => 'required|array',
            'items.*.id' => 'nullable|exists:question_items,id',
            'items.*.item_number' => 'required|integer',
            'items.*.prompt' => 'required|string',
            'items.*.options' => 'nullable|string',
            'items.*.subscale_tag' => 'nullable|string',
            'pairs' => 'nullable|array',
            'pairs.*.id' => 'nullable|exists:correlated_question_pairs,id',
            'pairs.*.question_item_id_a' => 'required|exists:question_items,id',
            'pairs.*.question_item_id_b' => 'required|exists:question_items,id|different:pairs.*.question_item_id_a',
            'pairs.*.relationship_type' => 'required|in:similar,inverse',
            'pairs.*.contradiction_threshold' => 'required|numeric|min:0|max:100',
            'pairs.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $category) {
            $category->update([
                'year_level' => $validated['year_level'],
                'name' => $validated['name'],
                'display_order' => $validated['display_order'],
                'instructions' => $validated['instructions'] ?? '',
                'scale_type' => $validated['scale_type'],
            ]);

            $existingIds = [];
            foreach ($validated['items'] as $itemData) {
                $options = null;
                if (!empty($itemData['options'])) {
                    $options = array_map('trim', explode(',', $itemData['options']));
                }

                if (!empty($itemData['id'])) {
                    $item = QuestionItem::find($itemData['id']);
                    $item->update([
                        'item_number' => $itemData['item_number'],
                        'prompt' => $itemData['prompt'],
                        'options' => $options,
                        'subscale_tag' => $itemData['subscale_tag'] ?? null,
                    ]);
                    $existingIds[] = $item->id;
                } else {
                    $newItem = QuestionItem::create([
                        'question_category_id' => $category->id,
                        'item_number' => $itemData['item_number'],
                        'prompt' => $itemData['prompt'],
                        'options' => $options,
                        'subscale_tag' => $itemData['subscale_tag'] ?? null,
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
