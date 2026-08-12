<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\QuestionCategory;
use Illuminate\Http\Request;
use App\Models\InventorySubmission;
use App\Models\InventoryResponse;
use App\Models\InventoryItemTiming;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryScoringService;

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
        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return view('inventory.closed', ['message' => 'No current academic year configured.']);
        }
        
        $user = $request->user();
        
        // Ensure user has a year_level (default to 3rd if null for backward compatibility temporarily)
        $yearLevel = $user->year_level ?? '3rd';

        $now = now();
        $dateStr = $now->toDateString();
        $timeStr = $now->toTimeString();

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

        $submission = InventorySubmission::where('user_id', $user->id)
            ->where('academic_year', $currentYear->label)
            ->first();

        if ($submission && $submission->submitted_at) {
            return view('inventory.submitted', compact('submission'));
        }

        if (!$submission) {
            $submission = InventorySubmission::create([
                'user_id' => $user->id,
                'academic_year' => $currentYear->label,
                'started_at' => now(),
            ]);
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
            ->where('year_level', $yearLevel)
            ->with('questionItems')
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
        
        $submission = InventorySubmission::where('user_id', $user->id)
            ->latest('started_at')
            ->firstOrFail();

        if ($submission->submitted_at) {
            return redirect()->route('inventory.index')->with('status', 'Already submitted.');
        }

        // Build validation rules
        $rules = [
            'consent_checkbox' => 'required|accepted',
            'signature_type' => 'required|in:drawn,typed',
            'signature_data' => 'required|string',
            'signature_font' => 'nullable|string'
        ];
        $messages = [
            'consent_checkbox.required' => 'You must check the agreement box.',
            'signature_data.required' => 'You must provide a signature.'
        ];
        
        $academicYearModel = AcademicYear::where('label', $submission->academic_year)->firstOrFail();
        $yearLevel = $user->year_level ?? '3rd';
        $categories = QuestionCategory::where('academic_year_id', $academicYearModel->id)
            ->where('year_level', $yearLevel)
            ->with('questionItems')
            ->get();

        foreach ($categories as $category) {
            foreach ($category->questionItems as $item) {
                $rules["responses.{$category->name}.{$item->item_number}"] = 'required';
                $rules["timings.{$category->name}.{$item->item_number}"] = 'required|integer|min:0';
                $messages["responses.{$category->name}.{$item->item_number}.required"] = "Please answer all questions in the {$category->name} section.";
            }
        }

        $validated = $request->validate($rules, $messages);

        DB::transaction(function () use ($validated, $submission) {
            foreach ($validated['responses'] as $category => $items) {
                foreach ($items as $itemNumber => $value) {
                    InventoryResponse::updateOrCreate(
                        [
                            'inventory_submission_id' => $submission->id,
                            'category' => $category,
                            'item_number' => $itemNumber,
                        ],
                        [
                            'response_value' => $value,
                        ]
                    );
                }
            }

            foreach ($validated['timings'] as $category => $items) {
                foreach ($items as $itemNumber => $timeMs) {
                    InventoryItemTiming::updateOrCreate(
                        [
                            'inventory_submission_id' => $submission->id,
                            'category' => $category,
                            'item_number' => $itemNumber,
                        ],
                        [
                            'time_spent_ms' => $timeMs,
                        ]
                    );
                }
            }

            $submission->update([
                'submitted_at' => now(),
                'consent_given_at' => now(),
                'consent_version' => '1.0',
                'signature_type' => $validated['signature_type'],
                'signature_data' => $validated['signature_data'],
                'signature_font' => $validated['signature_font'] ?? null,
            ]);

            $this->scoringService->computeScores($submission);
            $this->flaggingService->analyze($submission);
        });

        return view('inventory.success');
    }

    public function validateSection(Request $request)
    {
        $user = $request->user();
        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return response()->json(['message' => 'No academic year configured'], 400);
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
                'signature_data' => 'required|string',
                'signature_font' => 'nullable|string'
            ], [
                'consent_checkbox.required' => 'You must check the agreement box.',
                'signature_data.required' => 'You must provide a signature.'
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
        
        $yearLevel = $request->user()->year_level ?? '3rd';
        $catModel = QuestionCategory::where('academic_year_id', $currentYear->id)
            ->where('year_level', $yearLevel)
            ->where('name', $category)
            ->with('questionItems')
            ->first();
        
        if (!$catModel) {
            return response()->json(['message' => 'Invalid category'], 400);
        }
        
        $rules = [];
        $messages = [];
        foreach ($catModel->questionItems as $item) {
            $rules["responses.{$category}.{$item->item_number}"] = 'required';
            $rules["timings.{$category}.{$item->item_number}"] = 'nullable|integer|min:0';
            $messages["responses.{$category}.{$item->item_number}.required"] = "Item {$item->item_number} is required.";
        }
        
        $validated = $request->validate($rules, $messages);

        DB::transaction(function () use ($validated, $submission, $category) {
            if (isset($validated['responses'][$category])) {
                foreach ($validated['responses'][$category] as $itemNumber => $value) {
                    InventoryResponse::updateOrCreate(
                        [
                            'inventory_submission_id' => $submission->id,
                            'category' => $category,
                            'item_number' => $itemNumber,
                        ],
                        [
                            'response_value' => $value,
                        ]
                    );
                }
            }

            if (isset($validated['timings'][$category])) {
                foreach ($validated['timings'][$category] as $itemNumber => $timeMs) {
                    if ($timeMs !== null) {
                        InventoryItemTiming::updateOrCreate(
                            [
                                'inventory_submission_id' => $submission->id,
                                'category' => $category,
                                'item_number' => $itemNumber,
                            ],
                            [
                                'time_spent_ms' => $timeMs,
                            ]
                        );
                    }
                }
            }
        });
        
        return response()->json(['success' => true]);
    }
}
