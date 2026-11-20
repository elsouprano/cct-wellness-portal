<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\InventorySubmission;
use App\Services\DashboardStatsService;
use App\Services\ResponseQualityAssessmentService;
use Illuminate\Http\Request;

class InventorySubmissionController extends Controller
{
    public function index(Request $request, DashboardStatsService $statsService)
    {
        $user = auth()->user();
        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        $academicYear = $request->input('academic_year', $currentYear ? $currentYear->label : null);

        $stats = $statsService->getStats($academicYear, $user);
        $totalSubmissions = $stats['totalSubmissions'];
        $totalFlagged = $stats['totalFlagged'];
        $totalUnreviewedFlags = $stats['totalUnreviewedFlags'];
        $distributionCounts = $stats['distributionCounts'];

        // Response Quality Assessment summary metrics (single conditional aggregate query)
        $summaryCounts = InventorySubmission::accessibleByStaff($user)
            ->whereNotNull('submitted_at')
            ->when($academicYear, fn ($q) => $q->where('academic_year', $academicYear))
            ->selectRaw("
                COUNT(*) as total_assessed,
                SUM(CASE WHEN quality_classification = 'ACCEPTABLE' THEN 1 ELSE 0 END) as total_acceptable,
                SUM(CASE WHEN quality_classification = 'FOR REVIEW' THEN 1 ELSE 0 END) as total_for_review
            ")
            ->first();

        $totalAssessed = (int) ($summaryCounts->total_assessed ?? 0);
        $totalAcceptable = (int) ($summaryCounts->total_acceptable ?? 0);
        $totalFlaggedForReview = (int) ($summaryCounts->total_for_review ?? 0);

        $dass21Stats = [
            'Extremely Severe' => $distributionCounts[5] ?? 0,
            'Severe' => $distributionCounts[4] ?? 0,
            'Moderate' => $distributionCounts[3] ?? 0,
            'Mild' => $distributionCounts[2] ?? 0,
            'Normal' => $distributionCounts[1] ?? 0,
        ];
        $dass21SeverePlus = ($dass21Stats['Severe'] ?? 0) + ($dass21Stats['Extremely Severe'] ?? 0);

        // Registered and Submitted student statistics
        $registeredStudentsCount = $statsService->getRegisteredStudentsCount($user);
        $submittedCount = $statsService->getSubmittedStudentsCount($academicYear, $user);
        $completionPercentage = $registeredStudentsCount > 0 
            ? round(($submittedCount / $registeredStudentsCount) * 100, 1) 
            : null;

        // Main List Query
        $query = InventorySubmission::select('inventory_submissions.*')
            ->accessibleByStaff($user)
            ->with(['user.structuredProgram', 'qualityAssessment', 'scores' => function($q) {
                $q->whereIn('category_name', ['dass21', 'DASS21', 'DASS-21']);
            }])
            ->withCount('flags')
            ->withCount(['flags as unreviewed_flags' => function($q) {
                $q->where('is_reviewed', false);
            }])
            ->whereNotNull('submitted_at')
            ->when($academicYear, fn ($q) => $q->where('academic_year', $academicYear));

        // Quality Classification Filter
        if ($request->filled('quality_classification')) {
            if (in_array($request->quality_classification, ['ACCEPTABLE', 'FOR REVIEW'])) {
                $query->where('quality_classification', $request->quality_classification);
            }
        }

        // Filters
        if ($request->filled('year_level')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('year_level', $request->year_level);
            });
        }
        if ($request->filled('program')) {
            $programInput = $request->program;
            $matchedProgram = is_numeric($programInput)
                ? \App\Models\Program::find((int) $programInput)
                : \App\Models\Program::where('code', $programInput)->orWhere('name', $programInput)->first();

            $query->whereHas('user', function ($q) use ($programInput, $matchedProgram) {
                $q->where(function ($sub) use ($programInput, $matchedProgram) {
                    $sub->where('program', $programInput);
                    if ($matchedProgram) {
                        $sub->orWhere('program_id', $matchedProgram->id)
                            ->orWhereIn('program', array_filter([$matchedProgram->code, $matchedProgram->name]));
                    }
                });
            });
        }
        if ($request->filled('section')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('section', $request->section);
            });
        }
        if ($request->filled('search')) {
            $escapedSearch = addcslashes((string) $request->search, '%_\\');
            $searchTerm = '%' . $escapedSearch . '%';
            $query->whereHas('user', function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm);
            });
        }
        if ($request->filled('date_from') && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->date_from)) {
            $query->where('submitted_at', '>=', $request->date_from . ' 00:00:00');
        }
        if ($request->filled('date_to') && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->date_to)) {
            $query->where('submitted_at', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->filled('has_flags')) {
            if ($request->has_flags === 'any') {
                $query->has('flags');
            } elseif ($request->has_flags === 'unreviewed') {
                $query->whereHas('flags', function($q) {
                    $q->where('is_reviewed', false);
                });
            } elseif ($request->has_flags === 'none') {
                $query->doesntHave('flags');
            }
        }
        if ($request->filled('min_severity')) {
            $minVal = match($request->min_severity) {
                'Extremely Severe' => 5,
                'Severe' => 4,
                'Moderate' => 3,
                'Mild' => 2,
                'Normal' => 1,
                default => 0
            };
            if ($minVal > 0) {
                $query->whereHas('scores', function($q) use ($minVal) {
                    $q->whereIn('category_name', ['dass21', 'DASS21', 'DASS-21'])
                      ->whereRaw("(CASE 
                        WHEN severity_label = 'Extremely Severe' THEN 5 
                        WHEN severity_label = 'Severe' THEN 4 
                        WHEN severity_label = 'Moderate' THEN 3 
                        WHEN severity_label = 'Mild' THEN 2 
                        WHEN severity_label = 'Normal' THEN 1 
                        ELSE 0 END) >= ?", [$minVal]);
                });
            }
        }

        // Sorting
        $sort = $request->input('sort', 'submitted_at');
        $direction = $request->input('direction', 'desc');
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'desc';

        if ($sort === 'flag_count') {
            $query->orderBy('flags_count', $direction);
        } elseif ($sort === 'student_name') {
            $query->join('users', 'inventory_submissions.user_id', '=', 'users.id')
                  ->orderBy('users.last_name', $direction)
                  ->orderBy('users.first_name', $direction);
        } elseif ($sort === 'quality_classification') {
            $query->orderBy('quality_classification', $direction);
        } elseif ($sort === 'dass21_severity') {
            $sub = \Illuminate\Support\Facades\DB::table('inventory_scores')
                ->whereIn('category_name', ['dass21', 'DASS21', 'DASS-21'])
                ->selectRaw("inventory_submission_id, MAX(CASE WHEN severity_label = 'Extremely Severe' THEN 5 WHEN severity_label = 'Severe' THEN 4 WHEN severity_label = 'Moderate' THEN 3 WHEN severity_label = 'Mild' THEN 2 WHEN severity_label = 'Normal' THEN 1 ELSE 0 END) as max_sev")
                ->groupBy('inventory_submission_id');

            $query->leftJoinSub($sub, 'dass_sev', function($join) {
                $join->on('inventory_submissions.id', '=', 'dass_sev.inventory_submission_id');
            })
            ->orderByRaw('COALESCE(dass_sev.max_sev, 0) ' . $direction);
        } else {
            $query->orderBy('submitted_at', $direction);
        }

        $submissions = $query->paginate(25)->withQueryString();
        $academicYears = \App\Models\AcademicYear::orderByDesc('label')->pluck('label');

        return view('staff.inventory.index', compact(
            'submissions', 'academicYear', 'academicYears', 'totalSubmissions',
            'totalFlagged', 'totalUnreviewedFlags', 'totalAssessed', 'totalAcceptable',
            'totalFlaggedForReview', 'dass21Stats', 'registeredStudentsCount',
            'submittedCount', 'completionPercentage', 'dass21SeverePlus'
        ));
    }

    public function show(InventorySubmission $submission, ResponseQualityAssessmentService $qualityService)
    {
        if (! auth()->user()->canAccessSubmission($submission)) {
            abort(404);
        }

        // Auto-run quality assessment if completed but missing
        if ($submission->submitted_at && ! $submission->qualityAssessment) {
            $qualityService->assess($submission);
            DashboardStatsService::clearCache($submission->academic_year);
            \App\Http\Controllers\Staff\AnalyticsController::clearCache();
        }

        $submission->load(['user.structuredProgram.department', 'scores', 'flags.reviewer', 'responses', 'timings', 'qualityAssessment', 'qualityAssessments']);
        
        $academicYear = \App\Models\AcademicYear::where('label', $submission->academic_year)->first();
        $responseCategoryNames = $submission->responses->pluck('category')->unique()->filter()->values()->all();

        $categories = collect();
        if ($academicYear && ! empty($responseCategoryNames)) {
            $categories = \App\Models\QuestionCategory::with(['questionItems.subcategory', 'correlatedPairs'])
                ->where('academic_year_id', $academicYear->id)
                ->whereIn('name', $responseCategoryNames)
                ->orderBy('display_order')
                ->get();
        }

        if ($categories->isEmpty()) {
            $categoriesQuery = \App\Models\QuestionCategory::with(['questionItems.subcategory', 'correlatedPairs'])
                ->forYearLevel($submission->user?->year_level ?? '3rd', $academicYear?->id)
                ->orderBy('display_order');

            $categories = $academicYear
                ? (clone $categoriesQuery)->where('academic_year_id', $academicYear->id)->get()
                : collect();

            if ($categories->isEmpty()) {
                $categories = $categoriesQuery->get();
            }
        }
            
        foreach ($submission->responses as $response) {
            $cat = $categories->firstWhere('name', $response->category);
            if ($cat) {
                $response->questionItem = $cat->questionItems->firstWhere('item_number', $response->item_number);
            }
        }
        
        $scoresByCategory = $submission->scores->groupBy('category_name');
        $responsesByCategory = $submission->responses->groupBy('category');
        $inventoryConfig = $categories;

        $studentName = $submission->user
            ? trim("{$submission->user->first_name} {$submission->user->last_name}")
            : 'a student';

        app(\App\Services\ActivityLogService::class)->log(
            action: 'inventory.reviewed',
            module: 'Inventory Management',
            description: "Viewed student inventory record for {$studentName} (A.Y. {$submission->academic_year}).",
            subject: $submission,
            newValues: [
                'academic_year' => $submission->academic_year,
            ],
        );
        
        return view('staff.inventory.show', compact('submission', 'scoresByCategory', 'responsesByCategory', 'categories', 'inventoryConfig'));
    }

    public function reassess(InventorySubmission $submission, ResponseQualityAssessmentService $qualityService)
    {
        if (! auth()->user()->canAccessSubmission($submission)) {
            abort(404);
        }

        abort_if(! $submission->submitted_at, 404, 'Cannot reassess an unsubmitted draft.');

        $qualityService->assess($submission, true);

        DashboardStatsService::clearCache($submission->academic_year);
        \App\Http\Controllers\Staff\AnalyticsController::clearCache();

        $submission->loadMissing('user');
        $studentName = $submission->user
            ? trim("{$submission->user->first_name} {$submission->user->last_name}")
            : 'a student';

        app(\App\Services\ActivityLogService::class)->log(
            action: 'inventory.reassessed',
            module: 'Inventory Management',
            description: "Re-checked response quality for {$studentName} (A.Y. {$submission->academic_year}).",
            subject: $submission,
            newValues: ['academic_year' => $submission->academic_year],
        );

        return back()->with('status', 'Response quality re-assessment completed.');
    }

    public function exportPdf(InventorySubmission $submission)
    {
        if (! auth()->user()->canAccessSubmission($submission)) {
            abort(404);
        }

        abort_if(! $submission->submitted_at, 404, 'Cannot export an unsubmitted draft.');

        $submission->load(['user.structuredProgram.department', 'scores', 'responses', 'qualityAssessment']);
        
        $academicYear = \App\Models\AcademicYear::where('label', $submission->academic_year)->first();
        $responseCategoryNames = $submission->responses->pluck('category')->unique()->filter()->values()->all();

        $categories = collect();
        if ($academicYear && ! empty($responseCategoryNames)) {
            $categories = \App\Models\QuestionCategory::with('questionItems')
                ->where('academic_year_id', $academicYear->id)
                ->whereIn('name', $responseCategoryNames)
                ->orderBy('display_order')
                ->get();
        }

        if ($categories->isEmpty()) {
            $categoriesQuery = \App\Models\QuestionCategory::with('questionItems')
                ->forYearLevel($submission->user?->year_level ?? '3rd', $academicYear?->id)
                ->orderBy('display_order');

            $categories = $academicYear
                ? (clone $categoriesQuery)->where('academic_year_id', $academicYear->id)->get()
                : $categoriesQuery->get();
        }
            
        foreach ($submission->responses as $response) {
            $cat = $categories->firstWhere('name', $response->category);
            if ($cat) {
                $response->questionItem = $cat->questionItems->firstWhere('item_number', $response->item_number);
            }
        }
        
        $scoresByCategory = $submission->scores->groupBy('category_name');
        $responsesByCategory = $submission->responses->groupBy('category');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.inventory-submission', compact('submission', 'scoresByCategory', 'responsesByCategory', 'categories'))
            ->setOption(['isRemoteEnabled' => false]);

        $studentName = $submission->user
            ? trim("{$submission->user->first_name} {$submission->user->last_name}")
            : 'a student';

        app(\App\Services\ActivityLogService::class)->log(
            action: 'inventory.exported_pdf',
            module: 'Reports',
            description: "Downloaded student inventory report for {$studentName} (A.Y. {$submission->academic_year}).",
            subject: $submission,
            newValues: [
                'report_type' => 'inventory_submission_pdf',
                'academic_year' => $submission->academic_year,
            ],
        );
        
        $safeSlug = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($submission->user?->student_id ?? $submission->id));

        return $pdf->stream('student-inventory-' . $safeSlug . '.pdf');
    }

    public function reviewFlag(Request $request, \App\Models\InventoryFlag $flag)
    {
        if (! $flag->submission || ! auth()->user()->canAccessSubmission($flag->submission)) {
            abort(404);
        }

        $validated = $request->validate([
            'reviewer_notes' => 'nullable|string|max:2000',
        ]);

        $wasReviewed = (bool) $flag->is_reviewed;

        $flag->update([
            'is_reviewed' => true,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'reviewer_notes' => $validated['reviewer_notes'] ?? null,
        ]);

        DashboardStatsService::clearCache($flag->submission?->academic_year);
        \App\Http\Controllers\Staff\AnalyticsController::clearCache();

        $flag->loadMissing('submission.user');
        $studentName = $flag->submission?->user
            ? trim("{$flag->submission->user->first_name} {$flag->submission->user->last_name}")
            : 'a student';

        app(\App\Services\ActivityLogService::class)->log(
            action: 'flag.reviewed',
            module: 'Flagged Responses',
            description: "Reviewed flagged response for {$studentName}.",
            subject: $flag,
            oldValues: ['is_reviewed' => $wasReviewed],
            newValues: ['is_reviewed' => true, 'flag_type' => $flag->flag_type],
        );

        return back()->with('status', 'Flag marked as reviewed.');
    }
}
