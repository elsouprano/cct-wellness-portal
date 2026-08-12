<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuestionCategory;
use App\Models\InterpretationRange;
use App\Models\InterpretationRangeAuditLog;

class InterpretationRangeController extends Controller
{
    public function store(Request $request, QuestionCategory $question_category)
    {
        $validated = $request->validate([
            'subscale_tag' => 'nullable|string|max:255',
            'min_score' => 'required|integer',
            'max_score' => 'required|integer|gte:min_score',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color_tag' => 'required|string|in:green,yellow,orange,red,purple,gray,blue',
            'display_order' => 'required|integer',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_official_default'] = false;

        $question_category->interpretationRanges()->create($validated);

        return redirect()->back()->with('success', 'Interpretation range added successfully.')->with('activeTab', 'ranges');
    }

    public function update(Request $request, QuestionCategory $question_category, InterpretationRange $range)
    {
        $validated = $request->validate([
            'subscale_tag' => 'nullable|string|max:255',
            'min_score' => 'required|integer',
            'max_score' => 'required|integer|gte:min_score',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color_tag' => 'required|string|in:green,yellow,orange,red,purple,gray,blue',
            'display_order' => 'required|integer',
        ]);

        if ($range->is_official_default) {
            InterpretationRangeAuditLog::create([
                'actor_id' => auth()->id(),
                'action' => 'edited_official_default',
                'details' => [
                    'question_category_id' => $question_category->id,
                    'range_id' => $range->id,
                    'old_values' => $range->toArray(),
                    'new_values' => $validated,
                ],
            ]);
            $validated['is_official_default'] = false;
        }

        $range->update($validated);

        return redirect()->back()->with('success', 'Interpretation range updated successfully.')->with('activeTab', 'ranges');
    }

    public function destroy(QuestionCategory $question_category, InterpretationRange $range)
    {
        if ($range->is_official_default) {
            InterpretationRangeAuditLog::create([
                'actor_id' => auth()->id(),
                'action' => 'deleted_official_default',
                'details' => [
                    'question_category_id' => $question_category->id,
                    'range_id' => $range->id,
                    'deleted_values' => $range->toArray(),
                ],
            ]);
        }
        
        $range->delete();

        return redirect()->back()->with('success', 'Interpretation range deleted successfully.')->with('activeTab', 'ranges');
    }
}
