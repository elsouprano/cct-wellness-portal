<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionCategoryRequest;
use App\Http\Requests\UpdateQuestionCategoryRequest;
use Illuminate\Http\Request;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use App\Models\QuestionSubcategory;
use App\Models\CorrelatedQuestionPair;
use App\Models\CategoryYearLevel;
use App\Models\AcademicYear;
use App\Models\InventoryResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class QuestionBankController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        $categories = collect();
        $allRepositoryCategories = collect();
        $yearLevels = ['1st', '2nd', '3rd', '4th'];
        $search = trim($request->input('search', ''));
        $activeTab = $request->input('year_level', '1st');
        $activeView = $request->input('view', 'assignments'); // 'assignments' or 'repository'

        if ($currentYear) {
            // Eager-evaluate active assessment schedules in 1 query
            $schedules = \App\Models\AssessmentSchedule::where('academic_year_id', $currentYear->id)->get();
            $now = now();

            $activeYearLevels = $schedules->filter(function ($schedule) use ($now) {
                $closeDateTime = \Carbon\Carbon::parse($schedule->close_date . ' ' . $schedule->close_time);
                return $now->lessThan($closeDateTime);
            })->pluck('year_level')->unique()->toArray();

            // Load repository categories for current academic year with relations
            $repoQuery = QuestionCategory::where('academic_year_id', $currentYear->id)
                ->with(['yearLevelAssignments'])
                ->withCount('questionItems');

            if (!empty($search)) {
                $repoQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('instructions', 'like', "%{$search}%")
                      ->orWhereHas('questionItems', function ($itemQ) use ($search) {
                          $itemQ->where('prompt', 'like', "%{$search}%");
                      });
                });
            }

            $allRepositoryCategories = $repoQuery->orderBy('display_order')->get();

            // Synchronize dynamic locks and annotate in-memory flags
            $categoriesToLock = [];
            $categoriesToUnlock = [];

            foreach ($allRepositoryCategories as $cat) {
                $assignedLevels = $cat->assigned_year_levels;
                $hasActiveLock = !empty(array_intersect($assignedLevels, $activeYearLevels));

                if ($cat->is_locked !== $hasActiveLock) {
                    if ($hasActiveLock) {
                        $categoriesToLock[] = $cat->id;
                    } else {
                        $categoriesToUnlock[] = $cat->id;
                    }
                    $cat->is_locked = $hasActiveLock;
                }

                $cat->can_delete = !$cat->is_locked;
            }

            if (!empty($categoriesToLock)) {
                DB::table('question_categories')->whereIn('id', $categoriesToLock)->update(['is_locked' => true]);
            }
            if (!empty($categoriesToUnlock)) {
                DB::table('question_categories')->whereIn('id', $categoriesToUnlock)->update(['is_locked' => false]);
            }

            $categories = $allRepositoryCategories;
        }

        // Build assigned categories per year level
        $assignedCategoriesByLevel = [];
        foreach ($yearLevels as $yl) {
            $assignedCategoriesByLevel[$yl] = $categories->filter(function ($cat) use ($yl) {
                return $cat->isAssignedToYearLevel($yl);
            })->sortBy(function ($cat) use ($yl) {
                $assignment = $cat->yearLevelAssignments->firstWhere('year_level', $yl);
                return $assignment?->display_order ?? $cat->display_order;
            });
        }

        return view('staff.question-bank.index', compact(
            'categories',
            'allRepositoryCategories',
            'assignedCategoriesByLevel',
            'currentYear',
            'yearLevels',
            'search',
            'activeTab',
            'activeView'
        ));
    }

    public function create(Request $request)
    {
        $currentYear = AcademicYear::where('is_current', true)->firstOrFail();
        $defaultYearLevel = $request->query('year_level', '1st');
        return view('staff.question-bank.create', compact('currentYear', 'defaultYearLevel'));
    }

    public function store(StoreQuestionCategoryRequest $request)
    {
        $currentYear = AcademicYear::where('is_current', true)->firstOrFail();
        $validated = $request->validated();

        $category = DB::transaction(function () use ($validated, $currentYear) {
            $defaultOptions = null;
            if ($validated['scale_type'] === 'multiple_choice_unscored' && !empty($validated['default_options'])) {
                $defaultOptions = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $validated['default_options']))));
            }

            $primaryYearLevel = $validated['year_level'] ?? (isset($validated['year_levels'][0]) ? $validated['year_levels'][0] : null);

            $category = QuestionCategory::create([
                'academic_year_id' => $currentYear->id,
                'year_level' => $primaryYearLevel,
                'name' => $validated['name'],
                'display_order' => $validated['display_order'] ?? 1,
                'instructions' => $validated['instructions'] ?? '',
                'scale_type' => $validated['scale_type'],
                'scale_min' => $validated['scale_type'] === 'numeric_scale' ? $validated['scale_min'] : null,
                'scale_max' => $validated['scale_type'] === 'numeric_scale' ? $validated['scale_max'] : null,
                'scale_labels' => $validated['scale_type'] === 'numeric_scale' ? ($validated['scale_labels'] ?? null) : null,
                'default_options' => $defaultOptions,
                'is_locked' => false,
            ]);

            // Assign to year level(s)
            $targetYearLevels = [];
            if (!empty($validated['year_levels'])) {
                $targetYearLevels = $validated['year_levels'];
            } elseif (!empty($validated['year_level'])) {
                $targetYearLevels = [$validated['year_level']];
            }

            foreach (array_unique($targetYearLevels) as $yl) {
                CategoryYearLevel::create([
                    'question_category_id' => $category->id,
                    'academic_year_id' => $currentYear->id,
                    'year_level' => $yl,
                    'display_order' => $validated['display_order'] ?? 1,
                ]);
            }

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

            // Save items if provided
            if (!empty($validated['items'])) {
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
            }

            return $category;
        });

        return redirect()->route('question-bank.index')
            ->with('success', "Category '{$category->name}' created in repository successfully.");
    }

    public function edit(QuestionCategory $category)
    {
        $this->authorize('update', $category);
        $category->load(['questionItems', 'yearLevelAssignments', 'correlatedPairs.itemA', 'correlatedPairs.itemB', 'interpretationRanges', 'subcategories']);
        return view('staff.question-bank.edit', compact('category'));
    }

    public function update(UpdateQuestionCategoryRequest $request, QuestionCategory $category)
    {
        $this->authorize('update', $category);
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $category) {
            $defaultOptions = null;
            if ($validated['scale_type'] === 'multiple_choice_unscored' && !empty($validated['default_options'])) {
                $defaultOptions = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $validated['default_options']))));
            }

            $primaryYearLevel = $validated['year_level'] ?? ($category->year_level ?? null);

            $category->update([
                'year_level' => $primaryYearLevel,
                'name' => $validated['name'],
                'display_order' => $validated['display_order'] ?? $category->display_order,
                'instructions' => $validated['instructions'] ?? '',
                'scale_type' => $validated['scale_type'],
                'scale_min' => $validated['scale_type'] === 'numeric_scale' ? $validated['scale_min'] : null,
                'scale_max' => $validated['scale_type'] === 'numeric_scale' ? $validated['scale_max'] : null,
                'scale_labels' => $validated['scale_type'] === 'numeric_scale' ? ($validated['scale_labels'] ?? null) : null,
                'default_options' => $defaultOptions,
            ]);

            // Sync year levels if provided in form
            if (isset($validated['year_levels'])) {
                $currentAssigned = $category->yearLevelAssignments()->pluck('year_level')->all();
                $newAssigned = array_unique($validated['year_levels']);

                // Delete removed
                $category->yearLevelAssignments()->whereNotIn('year_level', $newAssigned)->delete();

                // Add newly checked
                foreach ($newAssigned as $yl) {
                    if (!in_array($yl, $currentAssigned, true)) {
                        CategoryYearLevel::create([
                            'question_category_id' => $category->id,
                            'academic_year_id' => $category->academic_year_id,
                            'year_level' => $yl,
                            'display_order' => $category->display_order ?? 1,
                        ]);
                    }
                }
            }

            // Sync subcategories
            $existingSubIds = [];
            $subcatMap = [];
            if (!empty($validated['subcategories'])) {
                foreach ($validated['subcategories'] as $subData) {
                    if (!empty($subData['id'])) {
                        $sub = QuestionSubcategory::find($subData['id']);
                        if ($sub) {
                            $sub->update([
                                'name' => $subData['name'],
                                'display_order' => $subData['display_order'],
                            ]);
                            $existingSubIds[] = $sub->id;
                        }
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
            $category->subcategories()->whereNotIn('id', $existingSubIds)->delete();

            // Sync items
            $existingIds = [];
            if (!empty($validated['items'])) {
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
                        if ($item) {
                            $item->update([
                                'item_number' => $itemData['item_number'],
                                'prompt' => $itemData['prompt'],
                                'options' => $options,
                                'question_subcategory_id' => $subcatId,
                            ]);
                            $existingIds[] = $item->id;
                        }
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
            }

            // Delete removed items
            $category->questionItems()->whereNotIn('id', $existingIds)->delete();

            // Handle pairs
            $existingPairIds = [];
            if (!empty($validated['pairs'])) {
                foreach ($validated['pairs'] as $pairData) {
                    $itemA = QuestionItem::find($pairData['question_item_id_a']);
                    $itemB = QuestionItem::find($pairData['question_item_id_b']);
                    
                    if (!$itemA || !$itemB || $itemA->question_category_id != $category->id || $itemB->question_category_id != $category->id) {
                        continue;
                    }

                    if (!empty($pairData['id'])) {
                        $pair = CorrelatedQuestionPair::find($pairData['id']);
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
                        $newPair = CorrelatedQuestionPair::create([
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

            $category->correlatedPairs()->whereNotIn('id', $existingPairIds)->delete();
        });

        return redirect()->route('question-bank.index')
            ->with('success', "Question category '{$category->name}' updated successfully.");
    }

    public function destroy(QuestionCategory $category)
    {
        $this->authorize('delete', $category);

        // Historical Submission & Active Schedule Protection
        if (!$category->canBeSafelyDeleted()) {
            if ($category->hasStudentResponses()) {
                return back()->with('error', "Cannot delete category '{$category->name}' because completed student responses are recorded for it. To preserve historical inventory records, please remove its year-level assignment instead.");
            }

            return back()->with('error', "Cannot delete category '{$category->name}' because it is in use by an active assessment schedule.");
        }

        DB::transaction(function () use ($category) {
            $category->questionItems()->delete();
            $category->yearLevelAssignments()->delete();
            $category->delete();
        });

        return redirect()->route('question-bank.index')
            ->with('success', "Question category '{$category->name}' removed from repository.");
    }

    /**
     * Assign an existing repository category to a year level.
     */
    public function assignYearLevel(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:question_categories,id',
            'year_level' => 'required|in:1st,2nd,3rd,4th',
            'display_order' => 'nullable|integer|min:1',
        ]);

        $category = QuestionCategory::findOrFail($request->category_id);
        $yearLevel = $request->year_level;

        // Duplicate prevention check
        if ($category->isAssignedToYearLevel($yearLevel)) {
            return back()->with('error', "Category '{$category->name}' is already assigned to {$yearLevel} Year.");
        }

        // Determine next display order if not specified
        $order = $request->display_order ?? (CategoryYearLevel::where('year_level', $yearLevel)->max('display_order') ?? 0) + 1;

        CategoryYearLevel::create([
            'question_category_id' => $category->id,
            'academic_year_id' => $category->academic_year_id,
            'year_level' => $yearLevel,
            'display_order' => $order,
        ]);

        return back()->with('success', "Category '{$category->name}' assigned to {$yearLevel} Year successfully.");
    }

    /**
     * Remove a category assignment from a year level without deleting it from the repository.
     */
    public function removeYearLevel(Request $request, $assignmentId)
    {
        $assignment = CategoryYearLevel::with('category')->findOrFail($assignmentId);
        $categoryName = $assignment->category?->name ?? 'Category';
        $yearLevel = $assignment->year_level;

        // Check active schedule lock for this specific year level
        $hasActiveSchedule = \App\Models\AssessmentSchedule::where('academic_year_id', $assignment->academic_year_id)
            ->where('year_level', $yearLevel)
            ->get()
            ->contains(function ($s) {
                $close = \Carbon\Carbon::parse($s->close_date . ' ' . $s->close_time);
                return now()->lessThan($close);
            });

        if ($hasActiveSchedule) {
            return back()->with('error', "Cannot remove '{$categoryName}' from {$yearLevel} Year while an assessment schedule is active.");
        }

        $assignment->delete();

        return back()->with('success', "Removed '{$categoryName}' from {$yearLevel} Year. The category and its questions remain safe in the Centralized Repository.");
    }

    /**
     * Quick modal sync of assigned year levels for a repository category.
     */
    public function syncYearLevels(Request $request, QuestionCategory $category)
    {
        $request->validate([
            'year_levels' => 'nullable|array',
            'year_levels.*' => 'in:1st,2nd,3rd,4th',
        ]);

        $selectedLevels = $request->input('year_levels', []);

        DB::transaction(function () use ($category, $selectedLevels) {
            // Delete unselected assignments
            $category->yearLevelAssignments()->whereNotIn('year_level', $selectedLevels)->delete();

            // Add newly selected
            $existing = $category->yearLevelAssignments()->pluck('year_level')->all();
            foreach ($selectedLevels as $yl) {
                if (!in_array($yl, $existing, true)) {
                    CategoryYearLevel::create([
                        'question_category_id' => $category->id,
                        'academic_year_id' => $category->academic_year_id,
                        'year_level' => $yl,
                        'display_order' => (CategoryYearLevel::where('year_level', $yl)->max('display_order') ?? 0) + 1,
                    ]);
                }
            }
        });

        return back()->with('success', "Year-level assignments for '{$category->name}' updated successfully.");
    }

    /**
     * Add a single question directly under a category.
     */
    public function storeItem(Request $request, QuestionCategory $category)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'item_number' => 'nullable|integer|min:1',
            'options' => 'nullable|string',
            'question_subcategory_id' => 'nullable|exists:question_subcategories,id',
        ]);

        // Duplicate question prompt check within category
        $normalizedPrompt = strtolower(trim(preg_replace('/\s+/', ' ', $request->prompt)));
        $existingDuplicate = $category->questionItems->first(function ($item) use ($normalizedPrompt) {
            return strtolower(trim(preg_replace('/\s+/', ' ', $item->prompt))) === $normalizedPrompt;
        });

        if ($existingDuplicate) {
            return back()->with('error', "Duplicate question detected: An identical question already exists in this category as Item #{$existingDuplicate->item_number}.");
        }

        $options = null;
        if (!empty($request->options)) {
            $parsed = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $request->options))));
            $options = empty($parsed) ? null : $parsed;
        }

        $itemNumber = $request->item_number ?? (($category->questionItems()->max('item_number') ?? 0) + 1);

        QuestionItem::create([
            'question_category_id' => $category->id,
            'item_number' => $itemNumber,
            'prompt' => $request->prompt,
            'options' => $options,
            'question_subcategory_id' => $request->question_subcategory_id,
        ]);

        return back()->with('success', "Added Question #{$itemNumber} to '{$category->name}'.");
    }

    /**
     * Edit a single question.
     */
    public function updateItem(Request $request, QuestionCategory $category, QuestionItem $item)
    {
        if ($item->question_category_id != $category->id) {
            abort(404);
        }

        $request->validate([
            'prompt' => 'required|string|max:1000',
            'options' => 'nullable|string',
            'question_subcategory_id' => 'nullable|exists:question_subcategories,id',
        ]);

        $normalizedPrompt = strtolower(trim(preg_replace('/\s+/', ' ', $request->prompt)));
        $existingDuplicate = $category->questionItems->where('id', '!=', $item->id)->first(function ($it) use ($normalizedPrompt) {
            return strtolower(trim(preg_replace('/\s+/', ' ', $it->prompt))) === $normalizedPrompt;
        });

        if ($existingDuplicate) {
            return back()->with('error', "Duplicate question detected: An identical question already exists in this category as Item #{$existingDuplicate->item_number}.");
        }

        $options = null;
        if (!empty($request->options)) {
            $parsed = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $request->options))));
            $options = empty($parsed) ? null : $parsed;
        }

        $item->update([
            'prompt' => $request->prompt,
            'options' => $options,
            'question_subcategory_id' => $request->question_subcategory_id,
        ]);

        return back()->with('success', "Updated Question #{$item->item_number}.");
    }

    /**
     * Delete a single question safely (protecting historical responses).
     */
    public function destroyItem(QuestionCategory $category, QuestionItem $item)
    {
        if ($item->question_category_id != $category->id) {
            abort(404);
        }

        // Check if student responses exist for this item
        $hasResponses = InventoryResponse::where('category', $category->name)
            ->where('item_number', $item->item_number)
            ->exists();

        if ($hasResponses) {
            return back()->with('error', "Cannot delete Question #{$item->item_number} because student submissions already reference it in completed inventories.");
        }

        $item->delete();

        return back()->with('success', "Deleted Question #{$item->item_number}.");
    }
}
