<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuestionCategory;
use App\Models\InterpretationRange;
use App\Models\InterpretationRangeAuditLog;
use Illuminate\Support\Facades\DB;

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

        $range = $question_category->interpretationRanges()->create($validated);

        app(\App\Services\ActivityLogService::class)->log(
            action: 'question_bank.range_created',
            module: 'Question Bank',
            description: "Added interpretation range '{$range->label}' ({$range->min_score}-{$range->max_score}) to {$question_category->label()}.",
            subject: $range,
            newValues: [
                'question_category_id' => $question_category->id,
                'label' => $range->label,
                'min_score' => $range->min_score,
                'max_score' => $range->max_score,
            ],
        );

        return redirect()->back()->with('success', 'Interpretation range added successfully.')->with('activeTab', 'ranges');
    }

    public function update(Request $request, QuestionCategory $question_category, InterpretationRange $range)
    {
        abort_if($range->question_category_id !== $question_category->id, 404);

        $validated = $request->validate([
            'subscale_tag' => 'nullable|string|max:255',
            'min_score' => 'required|integer',
            'max_score' => 'required|integer|gte:min_score',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color_tag' => 'required|string|in:green,yellow,orange,red,purple,gray,blue',
            'display_order' => 'required|integer',
        ]);

        DB::transaction(function () use ($range, $question_category, $validated) {
            $oldRangeValues = $range->only(['subscale_tag', 'min_score', 'max_score', 'label', 'color_tag']);
            $auditLog = null;

            if ($range->is_official_default) {
                $auditLog = InterpretationRangeAuditLog::create([
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

            app(\App\Services\ActivityLogService::class)->log(
                action: 'question_bank.range_updated',
                module: 'Question Bank',
                description: "Updated interpretation range '{$range->label}' in {$question_category->label()}.",
                subject: $range,
                oldValues: $oldRangeValues,
                newValues: $range->only(['subscale_tag', 'min_score', 'max_score', 'label', 'color_tag']),
                sourceType: $auditLog ? InterpretationRangeAuditLog::class : null,
                sourceId: $auditLog?->id,
                critical: true,
            );
        });

        return redirect()->back()->with('success', 'Interpretation range updated successfully.')->with('activeTab', 'ranges');
    }

    public function destroy(QuestionCategory $question_category, InterpretationRange $range)
    {
        abort_if($range->question_category_id !== $question_category->id, 404);

        DB::transaction(function () use ($range, $question_category) {
            $oldRangeValues = $range->only(['subscale_tag', 'min_score', 'max_score', 'label', 'color_tag']);
            $rangeId = $range->id;
            $rangeLabel = $range->label;
            $auditLog = null;

            if ($range->is_official_default) {
                $auditLog = InterpretationRangeAuditLog::create([
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

            app(\App\Services\ActivityLogService::class)->log(
                action: 'question_bank.range_deleted',
                module: 'Question Bank',
                description: "Deleted interpretation range '{$rangeLabel}' from {$question_category->label()}.",
                oldValues: $oldRangeValues,
                sourceType: $auditLog ? InterpretationRangeAuditLog::class : null,
                sourceId: $auditLog?->id,
                critical: true,
                subjectTypeOverride: InterpretationRange::class,
                subjectIdOverride: $rangeId,
            );
        });

        return redirect()->back()->with('success', 'Interpretation range deleted successfully.')->with('activeTab', 'ranges');
    }
}
