<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\InventorySubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // 1. Get filter options
        $academicYears = Cache::remember('filter_academic_years_all', 3600, fn() => AcademicYear::orderBy('label', 'desc')->get());
        
        $allowedDeptIds = $user->isCounselor() ? $user->getAllowedDepartmentIds() : [];
        if ($user->isCounselor()) {
            if (!empty($allowedDeptIds)) {
                $departments = Department::whereIn('id', $allowedDeptIds)->orderBy('name')->get();
                $programs = Program::whereIn('department_id', $allowedDeptIds)->orderBy('name')->get();
            } else {
                $departments = collect();
                $programs = collect();
            }
        } else {
            $departments = Cache::remember('filter_departments', 3600, fn() => Department::orderBy('name')->get());
            $programs = Cache::remember('filter_programs', 3600, fn() => Program::orderBy('name')->get());
        }

        $currentYear = Cache::remember('current_academic_year', 3600, fn() => AcademicYear::where('is_current', true)->first());
        $filters = $this->parseFilters($request, $user, $currentYear, $allowedDeptIds);

        // 3. Build Base Query for Submissions
        $submissionsQuery = $this->buildFilteredSubmissionsQuery($user, $filters);

        // Subquery of filtered submission IDs to avoid plucking large arrays into PHP memory
        $submissionsSubQuery = $submissionsQuery->select('inventory_submissions.id');

        $cacheVersion = Cache::get('analytics_cache_version', 1);
        $userScope = $user->isAdmin() ? 'all' : implode('-', $allowedDeptIds ?: ['none']);
        $cacheKey = 'analytics_charts_v' . $cacheVersion . '_' . $userScope . '_' . md5(json_encode($filters));

        $chartData = Cache::remember($cacheKey, 300, function () use ($submissionsSubQuery) {
            // Chart 1: DASS21 Severity Distribution
            $dass21Data = DB::table('inventory_scores')
                ->whereIn('inventory_submission_id', $submissionsSubQuery)
                ->whereIn('category_name', ['dass21', 'DASS21', 'DASS-21'])
                ->whereNotNull('subscale_name')
                ->select('subscale_name', 'severity_label', DB::raw('count(*) as count'))
                ->groupBy('subscale_name', 'severity_label')
                ->get();

            // Chart 2: Submission completion trend over time
            $trendData = DB::table('inventory_submissions')
                ->whereIn('id', $submissionsSubQuery)
                ->select(DB::raw('DATE(submitted_at) as date'), DB::raw('count(*) as count'))
                ->groupBy(DB::raw('DATE(submitted_at)'))
                ->orderBy('date')
                ->get();

            // Chart 3: Learning Style distribution
            $learningStyleData = DB::table('inventory_scores')
                ->whereIn('inventory_submission_id', $submissionsSubQuery)
                ->whereIn('category_name', ['learning_style', 'Learning Style', 'learning style'])
                ->whereNotNull('subscale_name')
                ->select('subscale_name', DB::raw('SUM(raw_score) as total_score'))
                ->groupBy('subscale_name')
                ->get();

            // Chart 4: Flag rate breakdown
            $flagData = DB::table('inventory_flags')
                ->whereIn('inventory_submission_id', $submissionsSubQuery)
                ->select('flag_type', 'is_reviewed', DB::raw('count(*) as count'))
                ->groupBy('flag_type', 'is_reviewed')
                ->get();

            // Chart 5: Comparison by program/section (Average DASS21 raw scores)
            $comparisonData = DB::table('inventory_scores')
                ->join('inventory_submissions', 'inventory_scores.inventory_submission_id', '=', 'inventory_submissions.id')
                ->join('users', 'inventory_submissions.user_id', '=', 'users.id')
                ->leftJoin('programs', 'users.program_id', '=', 'programs.id')
                ->whereIn('inventory_submissions.id', $submissionsSubQuery)
                ->whereIn('inventory_scores.category_name', ['dass21', 'DASS21', 'DASS-21'])
                ->whereNotNull('inventory_scores.subscale_name')
                ->select(
                    DB::raw('COALESCE(programs.name, users.section) as group_name'), 
                    'inventory_scores.subscale_name', 
                    DB::raw('AVG(inventory_scores.raw_score) as avg_score')
                )
                ->groupBy('group_name', 'inventory_scores.subscale_name')
                ->get();

            return compact('dass21Data', 'trendData', 'learningStyleData', 'flagData', 'comparisonData');
        });

        $dass21Data = $chartData['dass21Data'];
        $trendData = $chartData['trendData'];
        $learningStyleData = $chartData['learningStyleData'];
        $flagData = $chartData['flagData'];
        $comparisonData = $chartData['comparisonData'];

        return view('staff.analytics.index', compact(
            'academicYears', 'departments', 'programs', 'filters',
            'dass21Data', 'trendData', 'learningStyleData', 'flagData', 'comparisonData'
        ));
    }

    public static function clearCache(): void
    {
        Cache::put('analytics_cache_version', time());
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $currentYear = Cache::remember('current_academic_year', 3600, fn() => AcademicYear::where('is_current', true)->first());
        $allowedDeptIds = $user->isCounselor() ? $user->getAllowedDepartmentIds() : [];
        $filters = $this->parseFilters($request, $user, $currentYear, $allowedDeptIds, strictUnauthorizedDept: true);
        $academicYear = $filters['academic_year'];

        $submissionsQuery = $this->buildFilteredSubmissionsQuery($user, $filters);
        $submissionsSubQuery = $submissionsQuery->select('inventory_submissions.id');

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
            ->whereIn('inventory_submissions.id', $submissionsSubQuery)
            ->whereIn('inventory_scores.category_name', ['dass21', 'DASS21', 'DASS-21'])
            ->whereNotNull('inventory_scores.subscale_name')
            ->select(
                DB::raw("COALESCE(programs.name, users.section, 'Unknown') as group_name"),
                'inventory_scores.subscale_name',
                'inventory_scores.severity_label',
                DB::raw('count(*) as count')
            )
            ->groupBy('group_name', 'inventory_scores.subscale_name', 'inventory_scores.severity_label')
            ->get();

        $sanitizeCsvCell = static function ($value): string {
            $str = (string) ($value ?? '');
            if ($str !== '' && preg_match('/^[\s]*[=+\-@\t\r]/', $str)) {
                return "'" . $str;
            }

            return $str;
        };

        $callback = function () use ($comparisonData, $sanitizeCsvCell) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Group (Program/Section)', 'Subscale', 'Severity', 'Count']);

            foreach ($comparisonData as $row) {
                fputcsv($file, [
                    $sanitizeCsvCell($row->group_name),
                    $sanitizeCsvCell($row->subscale_name),
                    $sanitizeCsvCell($row->severity_label),
                    $row->count,
                ]);
            }

            fclose($file);
        };

        app(\App\Services\ActivityLogService::class)->log(
            action: 'report.analytics_exported',
            module: 'Reports',
            description: 'Downloaded analytics summary report' . ($academicYear ? " for A.Y. {$academicYear}" : '') . '.',
            newValues: array_filter([
                'report_type' => 'analytics_csv',
                'academic_year' => $academicYear,
                'year_level' => $filters['year_level'],
                'department_id' => $filters['department_id'],
                'program_id' => $filters['program_id'],
                'section' => $filters['section'],
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
            ], fn ($v) => $v !== null && $v !== ''),
        );

        return response()->stream($callback, 200, $headers);
    }

    private function parseFilters(Request $request, $user, ?AcademicYear $currentYear, array $allowedDeptIds, bool $strictUnauthorizedDept = false): array
    {
        $requestedDeptId = $request->input('department_id');
        $effectiveDeptId = null;
        $unauthorizedDept = false;

        if ($user->isCounselor()) {
            if ($requestedDeptId !== null && $requestedDeptId !== '') {
                if (in_array((int) $requestedDeptId, $allowedDeptIds, true)) {
                    $effectiveDeptId = (int) $requestedDeptId;
                } elseif ($strictUnauthorizedDept) {
                    $unauthorizedDept = true;
                }
            }
        } else {
            $effectiveDeptId = ($requestedDeptId !== null && $requestedDeptId !== '') ? (int) $requestedDeptId : null;
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $validDate = static fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

        return [
            'academic_year' => $request->input('academic_year', $currentYear ? $currentYear->label : null),
            'year_level' => $request->input('year_level'),
            'department_id' => $effectiveDeptId,
            'program_id' => $request->filled('program_id') ? (int) $request->input('program_id') : null,
            'section' => $request->input('section'),
            'date_from' => $validDate($dateFrom) ? $dateFrom : null,
            'date_to' => $validDate($dateTo) ? $dateTo : null,
            '_unauthorized_dept' => $unauthorizedDept,
        ];
    }

    private function buildFilteredSubmissionsQuery($user, array $filters)
    {
        $query = InventorySubmission::query()
            ->join('users', 'inventory_submissions.user_id', '=', 'users.id')
            ->leftJoin('programs', 'users.program_id', '=', 'programs.id')
            ->whereNotNull('inventory_submissions.submitted_at');

        if (! empty($filters['_unauthorized_dept'])) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isCounselor()) {
            $allowedPrograms = $user->getAllowedProgramIds();
            if (empty($allowedPrograms)) {
                return $query->whereRaw('1 = 0');
            }
            $allowedStrings = $user->getAllowedProgramStrings();
            $query->where(function ($q) use ($allowedPrograms, $allowedStrings) {
                $q->whereIn('users.program_id', $allowedPrograms);
                if (! empty($allowedStrings)) {
                    $q->orWhere(function ($fallback) use ($allowedStrings) {
                        $fallback->whereNull('users.program_id')
                            ->whereIn('users.program', $allowedStrings);
                    });
                }
            });
        }

        if ($filters['academic_year']) {
            $query->where('inventory_submissions.academic_year', $filters['academic_year']);
        }
        if ($filters['year_level']) {
            $query->where('users.year_level', $filters['year_level']);
        }
        if ($filters['program_id']) {
            if ($user->isCounselor() && ! in_array((int) $filters['program_id'], $user->getAllowedProgramIds() ?? [], true)) {
                return $query->whereRaw('1 = 0');
            }
            $query->where('users.program_id', $filters['program_id']);
        } elseif ($filters['department_id']) {
            $query->where('programs.department_id', $filters['department_id']);
        }
        if ($filters['section']) {
            $query->where('users.section', $filters['section']);
        }
        if ($filters['date_from']) {
            $query->where('inventory_submissions.submitted_at', '>=', $filters['date_from'] . ' 00:00:00');
        }
        if ($filters['date_to']) {
            $query->where('inventory_submissions.submitted_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query;
    }
}
