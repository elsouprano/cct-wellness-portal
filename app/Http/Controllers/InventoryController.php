<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\QuestionCategory;
use App\Models\QuestionItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use App\Models\InventorySubmission;
use App\Models\InventoryResponse;
use App\Models\InventoryItemTiming;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\InventoryScoringService;
use App\Services\DashboardStatsService;

class InventoryController extends Controller
{
    protected $scoringService;
    protected $flaggingService;

    public function __construct(InventoryScoringService $scoringService, \App\Services\InventoryFlaggingService $flaggingService)
    {
        $this->scoringService = $scoringService;
        $this->flaggingService = $flaggingService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'student', 403, 'Only students can access the inventory assessment.');

        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return view('inventory.closed', ['message' => 'No current academic year configured.']);
        }
        
        if (empty($user->year_level)) {
            if ($user->needs_section_confirmation) {
                return redirect()->route('student.section-confirmation.show');
            }

            return view('inventory.closed', [
                'message' => 'Your student record does not have an assigned year level. Please contact the Guidance Office to update your record.'
            ]);
        }

        $yearLevel = $user->year_level;

        // Check if there is an open schedule for this year level and program
        $schedule = AssessmentSchedule::getActiveForUser($user, $currentYear->id);

        if (!$schedule) {
            // Find the closest upcoming schedule to tell them when it opens
            $upcomingSchedule = AssessmentSchedule::getUpcomingForUser($user, $currentYear->id);

            return view('inventory.closed', [
                'message' => $upcomingSchedule ? 'The assessment is not currently open.' : 'No assessment window is scheduled for you.',
                'schedule' => $upcomingSchedule
            ]);
        }

        try {
            $submission = InventorySubmission::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'academic_year' => $currentYear->label,
                ],
                [
                    'started_at' => now(),
                ]
            );
        } catch (UniqueConstraintViolationException $e) {
            $submission = InventorySubmission::where('user_id', $user->id)
                ->where('academic_year', $currentYear->label)
                ->firstOrFail();
        }

        if ($submission->submitted_at) {
            return view('inventory.submitted', compact('submission'));
        }

        $submission->load(['responses', 'timings']);
        
        $existingResponses = [];
        foreach ($submission->responses as $r) {
            $existingResponses[$r->category][$r->item_number] = $r->response_value;
        }

        $existingTimings = [];
        foreach ($submission->timings as $t) {
            $existingTimings[$t->category][$t->item_number] = $t->time_spent_ms;
        }

        // Pull categories and items from DB
        $inventoryConfig = QuestionCategory::where('academic_year_id', $currentYear->id)
            ->forYearLevel($yearLevel, $currentYear->id)
            ->with(['questionItems.subcategory'])
            ->orderBy('display_order')
            ->get();

        if ($inventoryConfig->isEmpty()) {
            return view('inventory.closed', ['message' => 'No questions configured for your year level.']);
        }

        $startStep = 0;
        if ($submission->consent_given_at) {
            $startStep = 1;
            $stepIndex = 1;
            foreach ($inventoryConfig as $data) {
                $catResponses = $existingResponses[$data->name] ?? [];
                if (count($catResponses) >= $data->questionItems->count()) {
                    $startStep = $stepIndex + 1;
                } else {
                    break;
                }
                $stepIndex++;
            }
            if ($startStep > count($inventoryConfig)) {
                $startStep = count($inventoryConfig);
            }
        }

        return view('inventory.form', compact('inventoryConfig', 'submission', 'existingResponses', 'existingTimings', 'startStep'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'student', 403, 'Only students can submit the inventory assessment.');

        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return redirect()->route('inventory.index')->with('error', 'No current academic year configured.');
        }

        if (empty($user->year_level)) {
            return redirect()->route('inventory.index');
        }

        $submission = InventorySubmission::where('user_id', $user->id)
            ->where('academic_year', $currentYear->label)
            ->firstOrFail();

        if ($submission->submitted_at) {
            return redirect()->route('inventory.index')->with('status', 'Already submitted.');
        }

        // Enforce active assessment schedule window
        $schedule = AssessmentSchedule::getActiveForUser($user, $currentYear->id);
        if (!$schedule) {
            return redirect()->route('inventory.index')->with('error', 'The assessment window is currently closed.');
        }

        // Build validation rules
        $rules = [
            'consent_checkbox' => 'required|accepted',
            'signature_type' => 'required|in:drawn,typed',
            'signature_data' => $this->signatureDataRules($request->input('signature_type')),
            'signature_font' => 'nullable|string|max:100',
        ];
        $messages = [
            'consent_checkbox.required' => 'You must check the agreement box.',
            'signature_data.required' => 'You must provide a signature.',
            'signature_data.regex' => 'Invalid drawn signature format.',
        ];
        
        $academicYearModel = $currentYear;
        $yearLevel = $user->year_level;
        $categories = QuestionCategory::where('academic_year_id', $academicYearModel->id)
            ->forYearLevel($yearLevel, $academicYearModel->id)
            ->with('questionItems')
            ->get();

        if ($categories->isEmpty()) {
            return redirect()->route('inventory.index')->with('error', 'No questions configured for your year level.');
        }

        foreach ($categories as $category) {
            foreach ($category->questionItems as $item) {
                $rules["responses.{$category->name}.{$item->item_number}"] = $this->itemResponseRules($category, $item);
                $rules["timings.{$category->name}.{$item->item_number}"] = 'required|integer|min:0';
                $messages["responses.{$category->name}.{$item->item_number}.required"] = "Please answer all questions in the {$category->name} section.";
            }
        }

        $validated = $request->validate($rules, $messages);

        $alreadySubmitted = false;

        DB::transaction(function () use ($validated, $submission, &$alreadySubmitted) {
            // Lock submission row to prevent concurrent double-submit race condition
            $lockedSubmission = InventorySubmission::where('id', $submission->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubmission->submitted_at) {
                $alreadySubmitted = true;
                return;
            }

            $now = now();
            $responsesData = [];
            foreach (($validated['responses'] ?? []) as $category => $items) {
                foreach ($items as $itemNumber => $value) {
                    $responsesData[] = [
                        'inventory_submission_id' => $lockedSubmission->id,
                        'category' => $category,
                        'item_number' => $itemNumber,
                        'response_value' => (string) $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (!empty($responsesData)) {
                InventoryResponse::upsert(
                    $responsesData,
                    ['inventory_submission_id', 'category', 'item_number'],
                    ['response_value', 'updated_at']
                );
            }

            $timingsData = [];
            foreach (($validated['timings'] ?? []) as $category => $items) {
                foreach ($items as $itemNumber => $timeMs) {
                    $timingsData[] = [
                        'inventory_submission_id' => $lockedSubmission->id,
                        'category' => $category,
                        'item_number' => $itemNumber,
                        'time_spent_ms' => $timeMs,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (!empty($timingsData)) {
                InventoryItemTiming::upsert(
                    $timingsData,
                    ['inventory_submission_id', 'category', 'item_number'],
                    ['time_spent_ms', 'updated_at']
                );
            }

            $lockedSubmission->update([
                'submitted_at' => now(),
                'consent_given_at' => now(),
                'consent_version' => '1.0',
                'signature_type' => $validated['signature_type'],
                'signature_data' => $validated['signature_data'],
                'signature_font' => $validated['signature_font'] ?? null,
            ]);

            // Clear any preexisting scores or flags before inserting to prevent duplication
            $lockedSubmission->scores()->delete();
            $lockedSubmission->flags()->delete();

            $this->scoringService->computeScores($lockedSubmission);
            $this->flaggingService->analyze($lockedSubmission);
        });

        if ($alreadySubmitted) {
            return redirect()->route('inventory.index')->with('status', 'Already submitted.');
        }

        DashboardStatsService::clearCache($submission->academic_year);
        \App\Http\Controllers\Staff\AnalyticsController::clearCache();

        return view('inventory.success');
    }

    public function validateSection(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'student', 403, 'Only students can save inventory responses.');

        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return response()->json(['message' => 'No academic year configured'], 400);
        }

        if (empty($user->year_level)) {
            return response()->json(['message' => 'No year level assigned to your record.'], 400);
        }

        // Enforce active assessment schedule window
        $schedule = AssessmentSchedule::getActiveForUser($user, $currentYear->id);
        if (!$schedule) {
            return response()->json(['message' => 'The assessment window is currently closed.'], 403);
        }

        $submission = InventorySubmission::where('user_id', $user->id)
            ->where('academic_year', $currentYear->label)
            ->first();
            
        if (!$submission || $submission->submitted_at) {
            return response()->json(['message' => 'Invalid or completed submission'], 400);
        }

        $category = $request->input('category');
        
        if ($category === 'consent') {
            $validated = $request->validate([
                'consent_checkbox' => 'required|accepted',
                'signature_type' => 'required|in:drawn,typed',
                'signature_data' => $this->signatureDataRules($request->input('signature_type')),
                'signature_font' => 'nullable|string|max:100',
            ], [
                'consent_checkbox.required' => 'You must check the agreement box.',
                'signature_data.required' => 'You must provide a signature.',
                'signature_data.regex' => 'Invalid drawn signature format.',
            ]);
            
            $submission->update([
                'consent_given_at' => now(),
                'consent_version' => '1.0',
                'signature_type' => $validated['signature_type'],
                'signature_data' => $validated['signature_data'],
                'signature_font' => $validated['signature_font'] ?? null,
            ]);
            
            return response()->json(['success' => true]);
        }
        
        $yearLevel = $user->year_level;
        $catModel = QuestionCategory::where('academic_year_id', $currentYear->id)
            ->forYearLevel($yearLevel, $currentYear->id)
            ->where('name', $category)
            ->with('questionItems')
            ->first();
        
        if (!$catModel) {
            return response()->json(['message' => 'Invalid category'], 400);
        }
        
        $rules = [];
        $messages = [];
        foreach ($catModel->questionItems as $item) {
            $rules["responses.{$category}.{$item->item_number}"] = $this->itemResponseRules($catModel, $item);
            $rules["timings.{$category}.{$item->item_number}"] = 'nullable|integer|min:0';
            $messages["responses.{$category}.{$item->item_number}.required"] = "Item {$item->item_number} is required.";
        }
        
        $validated = $request->validate($rules, $messages);

        DB::transaction(function () use ($validated, $submission, $category) {
            $now = now();
            if (isset($validated['responses'][$category])) {
                $responsesData = [];
                foreach ($validated['responses'][$category] as $itemNumber => $value) {
                    $responsesData[] = [
                        'inventory_submission_id' => $submission->id,
                        'category' => $category,
                        'item_number' => $itemNumber,
                        'response_value' => (string) $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if (!empty($responsesData)) {
                    InventoryResponse::upsert(
                        $responsesData,
                        ['inventory_submission_id', 'category', 'item_number'],
                        ['response_value', 'updated_at']
                    );
                }
            }

            if (isset($validated['timings'][$category])) {
                $timingsData = [];
                foreach ($validated['timings'][$category] as $itemNumber => $timeMs) {
                    if ($timeMs !== null) {
                        $timingsData[] = [
                            'inventory_submission_id' => $submission->id,
                            'category' => $category,
                            'item_number' => $itemNumber,
                            'time_spent_ms' => $timeMs,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
                if (!empty($timingsData)) {
                    InventoryItemTiming::upsert(
                        $timingsData,
                        ['inventory_submission_id', 'category', 'item_number'],
                        ['time_spent_ms', 'updated_at']
                    );
                }
            }
        });
        
        return response()->json(['success' => true]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        
        $submissions = InventorySubmission::where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->get();
            
        return view('inventory.history.index', compact('submissions'));
    }

    public function showHistory(Request $request, $id)
    {
        $user = $request->user();
        abort_unless($user->role === 'student', 403);
        
        $submission = InventorySubmission::where('id', $id)
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->with(['responses'])
            ->firstOrFail();
            
        $academicYearModel = AcademicYear::where('label', $submission->academic_year)->first();
        $responseCategories = $submission->responses->pluck('category')->unique()->filter()->values();
        
        $inventoryConfig = collect();
        if ($academicYearModel && $responseCategories->isNotEmpty()) {
            $inventoryConfig = QuestionCategory::where('academic_year_id', $academicYearModel->id)
                ->whereIn('name', $responseCategories)
                ->with(['questionItems.subcategory'])
                ->orderBy('display_order')
                ->get();
        }

        if ($inventoryConfig->isEmpty() && $academicYearModel && !empty($user->year_level)) {
            $inventoryConfig = QuestionCategory::where('academic_year_id', $academicYearModel->id)
                ->forYearLevel($user->year_level, $academicYearModel->id)
                ->with(['questionItems.subcategory'])
                ->orderBy('display_order')
                ->get();
        }

        return view('inventory.history.show', compact('submission', 'inventoryConfig'));
    }

    private function signatureDataRules(?string $signatureType): array
    {
        if ($signatureType === 'drawn') {
            return ['required', 'string', 'max:150000', 'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/'];
        }

        return ['required', 'string', 'max:255'];
    }

    private function itemResponseRules(QuestionCategory $category, QuestionItem $item): array
    {
        $scalarCheck = function (string $attribute, mixed $value, \Closure $fail) {
            if (!is_scalar($value)) {
                $fail('Invalid response value.');
            }
        };

        if ($category->scale_type === 'numeric_scale' && $category->scale_min !== null && $category->scale_max !== null) {
            return ['required', $scalarCheck, 'integer', "min:{$category->scale_min}", "max:{$category->scale_max}"];
        }

        $options = !empty($item->options) ? $item->options : ($category->default_options ?? []);
        if (is_array($options) && !empty($options)) {
            $allowed = array_map(static fn ($opt) => (string) $opt, array_values($options));

            return ['required', $scalarCheck, 'max:255', Rule::in($allowed)];
        }

        if ($category->scale_min !== null && $category->scale_max !== null) {
            return ['required', $scalarCheck, 'integer', "min:{$category->scale_min}", "max:{$category->scale_max}"];
        }

        return ['required', $scalarCheck, 'max:255'];
    }
}
