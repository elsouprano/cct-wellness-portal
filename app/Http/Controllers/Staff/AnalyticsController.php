<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\InventorySubmission;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get filter options
        $academicYears = AcademicYear::orderBy('label', 'desc')->get();
        $departments = Department::orderBy('name')->get();
        $programs = Program::orderBy('name')->get();

        $currentYear = AcademicYear::where('is_current', true)->first();
        
        // 2. Parse filters
        $filters = [
            'academic_year' => $request->input('academic_year', $currentYear ? $currentYear->label : null),
            'year_level' => $request->input('year_level'),
            'department_id' => $request->input('department_id'),
            'program_id' => $request->input('program_id'),
            'section' => $request->input('section'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        // 3. Build Base Query for Submissions
        $submissionsQuery = InventorySubmission::query()
            ->join('users', 'inventory_submissions.user_id', '=', 'users.id')
            ->leftJoin('programs', 'users.program_id', '=', 'programs.id')
            ->whereNotNull('inventory_submissions.submitted_at');

        if ($filters['academic_year']) {
            $submissionsQuery->where('inventory_submissions.academic_year', $filters['academic_year']);
        }
        if ($filters['year_level']) {
            $submissionsQuery->where('users.year_level', $filters['year_level']);
        }
        if ($filters['program_id']) {
            $submissionsQuery->where('users.program_id', $filters['program_id']);
        } elseif ($filters['department_id']) {
            $submissionsQuery->where('programs.department_id', $filters['department_id']);
        }
        if ($filters['section']) {
            $submissionsQuery->where('users.section', $filters['section']);
        }
        if ($filters['date_from']) {
            $submissionsQuery->whereDate('inventory_submissions.submitted_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $submissionsQuery->whereDate('inventory_submissions.submitted_at', '<=', $filters['date_to']);
        }

        // We need a subquery or a CTE if we were doing complex things, but since we are using Eloquent query builder, 
        // we can get the IDs of the filtered submissions to use in subsequent queries.
        $filteredSubmissionIds = $submissionsQuery->pluck('inventory_submissions.id');

        // Chart 1: DASS21 Severity Distribution
        $dass21Data = DB::table('inventory_scores')
            ->whereIn('inventory_submission_id', $filteredSubmissionIds)
            ->where('category_name', 'DASS-21')
            ->whereNotNull('subscale_name')
            ->select('subscale_name', 'severity_label', DB::raw('count(*) as count'))
            ->groupBy('subscale_name', 'severity_label')
            ->get();

        // Chart 2: Submission completion trend over time
        $trendData = DB::table('inventory_submissions')
            ->whereIn('id', $filteredSubmissionIds)
            ->select(DB::raw('DATE(submitted_at) as date'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(submitted_at)'))
            ->orderBy('date')
            ->get();

        // Chart 3: Learning Style distribution
        // For learning style, we need to know the dominant style. 
        // Since we store scores per subscale, the one with the highest raw_score is dominant, OR
        // we can just aggregate all raw scores across the population if they are meant to be a general pie chart.
        // Assuming we aggregate total scores for V, A, K across the population.
        $learningStyleData = DB::table('inventory_scores')
            ->whereIn('inventory_submission_id', $filteredSubmissionIds)
            ->where('category_name', 'Learning Style')
            ->whereNotNull('subscale_name')
            ->select('subscale_name', DB::raw('SUM(raw_score) as total_score'))
            ->groupBy('subscale_name')
            ->get();

        // Chart 4: Flag rate breakdown
        $flagData = DB::table('inventory_flags')
            ->whereIn('inventory_submission_id', $filteredSubmissionIds)
            ->select('flag_type', 'is_reviewed', DB::raw('count(*) as count'))
            ->groupBy('flag_type', 'is_reviewed')
            ->get();

        // Chart 5: Comparison by program/section (Average DASS21 raw scores)
        $comparisonData = DB::table('inventory_scores')
            ->join('inventory_submissions', 'inventory_scores.inventory_submission_id', '=', 'inventory_submissions.id')
            ->join('users', 'inventory_submissions.user_id', '=', 'users.id')
            ->leftJoin('programs', 'users.program_id', '=', 'programs.id')
            ->whereIn('inventory_submissions.id', $filteredSubmissionIds)
            ->where('inventory_scores.category_name', 'DASS-21')
            ->whereNotNull('inventory_scores.subscale_name')
            ->select(
                DB::raw('COALESCE(programs.name, users.section) as group_name'), 
                'inventory_scores.subscale_name', 
                DB::raw('AVG(inventory_scores.raw_score) as avg_score')
            )
            ->groupBy('group_name', 'inventory_scores.subscale_name')
            ->get();

        return view('staff.analytics.index', compact(
            'academicYears', 'departments', 'programs', 'filters',
            'dass21Data', 'trendData', 'learningStyleData', 'flagData', 'comparisonData'
        ));
    }

    public function export(Request $request)
    {
        // For CSV export, we'll export the aggregated DASS21 comparison data or raw submission data.
        // Given constraints, a CSV summary of the currently filtered view is practical.
        
        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYear = $request->input('academic_year', $currentYear ? $currentYear->label : null);
        
        $submissionsQuery = InventorySubmission::query()
            ->join('users', 'inventory_submissions.user_id', '=', 'users.id')
            ->leftJoin('programs', 'users.program_id', '=', 'programs.id')
            ->whereNotNull('inventory_submissions.submitted_at');

        if ($academicYear) $submissionsQuery->where('inventory_submissions.academic_year', $academicYear);
        if ($request->filled('year_level')) $submissionsQuery->where('users.year_level', $request->year_level);
        if ($request->filled('program_id')) $submissionsQuery->where('users.program_id', $request->program_id);
        if ($request->filled('department_id')) $submissionsQuery->where('programs.department_id', $request->department_id);
        if ($request->filled('section')) $submissionsQuery->where('users.section', $request->section);
        if ($request->filled('date_from')) $submissionsQuery->whereDate('inventory_submissions.submitted_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $submissionsQuery->whereDate('inventory_submissions.submitted_at', '<=', $request->date_to);

        $filteredSubmissionIds = $submissionsQuery->pluck('inventory_submissions.id');

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=analytics_report.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $comparisonData = DB::table('inventory_scores')
            ->join('inventory_submissions', 'inventory_scores.inventory_submission_id', '=', 'inventory_submissions.id')
            ->join('users', 'inventory_submissions.user_id', '=', 'users.id')
            ->leftJoin('programs', 'users.program_id', '=', 'programs.id')
            ->whereIn('inventory_submissions.id', $filteredSubmissionIds)
            ->where('inventory_scores.category_name', 'DASS-21')
            ->whereNotNull('inventory_scores.subscale_name')
            ->select(
                DB::raw('COALESCE(programs.name, users.section, "Unknown") as group_name'), 
                'inventory_scores.subscale_name', 
                'inventory_scores.severity_label',
                DB::raw('count(*) as count')
            )
            ->groupBy('group_name', 'inventory_scores.subscale_name', 'inventory_scores.severity_label')
            ->get();

        $callback = function() use($comparisonData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Group (Program/Section)', 'Subscale', 'Severity', 'Count']);

            foreach ($comparisonData as $row) {
                fputcsv($file, [
                    $row->group_name,
                    $row->subscale_name,
                    $row->severity_label,
                    $row->count
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
