<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\InventorySubmission;
use Illuminate\Http\Request;

class InventorySubmissionController extends Controller
{
    public function index(Request $request)
    {
        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        $academicYear = $request->input('academic_year', $currentYear ? $currentYear->label : null);

        // Stats queries
        $statsQuery = InventorySubmission::where('academic_year', $academicYear)->whereNotNull('submitted_at');
        $totalSubmissions = (clone $statsQuery)->count();
        $totalFlagged = (clone $statsQuery)->whereHas('flags')->count();
        $totalUnreviewedFlags = \App\Models\InventoryFlag::whereHas('submission', function($q) use ($academicYear) {
            $q->where('academic_year', $academicYear)->whereNotNull('submitted_at');
        })->where('is_reviewed', false)->count();

        // DASS21 distribution
        $dass21Distribution = \Illuminate\Support\Facades\DB::table('inventory_scores')
            ->join('inventory_submissions', 'inventory_scores.inventory_submission_id', '=', 'inventory_submissions.id')
            ->where('inventory_submissions.academic_year', $academicYear)
            ->whereNotNull('inventory_submissions.submitted_at')
            ->where('inventory_scores.category_name', 'DASS21')
            ->whereNotNull('inventory_scores.severity_label')
            ->selectRaw("
                inventory_submissions.id as submission_id,
                MAX(CASE 
                    WHEN severity_label = 'Extremely Severe' THEN 5 
                    WHEN severity_label = 'Severe' THEN 4 
                    WHEN severity_label = 'Moderate' THEN 3 
                    WHEN severity_label = 'Mild' THEN 2 
                    WHEN severity_label = 'Normal' THEN 1 
                    ELSE 0 
                END) as max_severity
            ")
            ->groupBy('inventory_submissions.id');

        $distributionCounts = \Illuminate\Support\Facades\DB::table(\Illuminate\Support\Facades\DB::raw("({$dass21Distribution->toSql()}) as sub"))
            ->mergeBindings($dass21Distribution)
            ->selectRaw('max_severity, COUNT(*) as count')
            ->groupBy('max_severity')
            ->pluck('count', 'max_severity')
            ->toArray();

        $dass21Stats = [
            'Extremely Severe' => $distributionCounts[5] ?? 0,
            'Severe' => $distributionCounts[4] ?? 0,
            'Moderate' => $distributionCounts[3] ?? 0,
            'Mild' => $distributionCounts[2] ?? 0,
            'Normal' => $distributionCounts[1] ?? 0,
        ];

        // Main List Query
        $query = InventorySubmission::with(['user', 'scores' => function($q) {
            $q->where('category_name', 'DASS21');
        }])
        ->withCount('flags')
        ->withCount(['flags as unreviewed_flags' => function($q) {
            $q->where('is_reviewed', false);
        }])
        ->whereNotNull('submitted_at')
        ->where('academic_year', $academicYear);

        // Filters
        if ($request->filled('year_level')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('year_level', $request->year_level);
            });
        }
        if ($request->filled('program')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('program', $request->program);
            });
        }
        if ($request->filled('section')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('section', $request->section);
            });
        }
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->whereHas('user', function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm);
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
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
                    $q->where('category_name', 'DASS21')
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
                  ->orderBy('users.first_name', $direction)
                  ->select('inventory_submissions.*');
        } elseif ($sort === 'dass21_severity') {
            $sub = \Illuminate\Support\Facades\DB::table('inventory_scores')
                ->where('category_name', 'DASS21')
                ->selectRaw("inventory_submission_id, MAX(CASE WHEN severity_label = 'Extremely Severe' THEN 5 WHEN severity_label = 'Severe' THEN 4 WHEN severity_label = 'Moderate' THEN 3 WHEN severity_label = 'Mild' THEN 2 WHEN severity_label = 'Normal' THEN 1 ELSE 0 END) as max_sev")
                ->groupBy('inventory_submission_id');

            $query->leftJoinSub($sub, 'dass_sev', function($join) {
                $join->on('inventory_submissions.id', '=', 'dass_sev.inventory_submission_id');
            })
            ->orderByRaw('COALESCE(dass_sev.max_sev, 0) ' . $direction)
            ->select('inventory_submissions.*');
        } else {
            $query->orderBy('submitted_at', $direction);
        }

        $submissions = $query->paginate(25)->withQueryString();
        $academicYears = \App\Models\AcademicYear::orderByDesc('label')->pluck('label');

        return view('staff.inventory.index', compact(
            'submissions', 'academicYear', 'academicYears', 'totalSubmissions',
            'totalFlagged', 'totalUnreviewedFlags', 'dass21Stats'
        ));
    }

    public function show(InventorySubmission $submission)
    {
        $submission->load(['user', 'scores', 'flags.reviewer', 'responses']);
        
        $academicYear = \App\Models\AcademicYear::where('label', $submission->academic_year)->first();
        $categories = \App\Models\QuestionCategory::with('questionItems')
            ->where('academic_year_id', $academicYear ? $academicYear->id : null)
            ->where('year_level', $submission->user->year_level ?? '3rd')
            ->get();
            
        foreach ($submission->responses as $response) {
            $cat = $categories->firstWhere('name', $response->category);
            if ($cat) {
                $response->questionItem = $cat->questionItems->firstWhere('item_number', $response->item_number);
            }
        }
        
        $scoresByCategory = $submission->scores->groupBy('category_name');
        $responsesByCategory = $submission->responses->groupBy('category');
        
        return view('staff.inventory.show', compact('submission', 'scoresByCategory', 'responsesByCategory'));
    }

    public function reviewFlag(Request $request, \App\Models\InventoryFlag $flag)
    {
        $request->validate([
            'reviewer_notes' => 'nullable|string'
        ]);

        $flag->update([
            'is_reviewed' => true,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'reviewer_notes' => $request->reviewer_notes
        ]);

        return back()->with('status', 'Flag marked as reviewed.');
    }
}
