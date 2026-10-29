<?php

namespace App\Services;

use App\Models\InventorySubmission;
use App\Models\InventoryFlag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardStatsService
{
    /**
     * Clear cached statistics across all department variants.
     */
    public static function clearCache(?string $academicYear = null): void
    {
        $years = $academicYear ? [$academicYear] : \App\Models\AcademicYear::pluck('label')->all();
        $deptIds = \App\Models\Department::pluck('id')->all();

        foreach ($years as $year) {
            Cache::forget("dashboard_stats_{$year}");
            Cache::forget("dashboard_stats_{$year}_all");
            Cache::forget("dashboard_stats_{$year}_dept_none");

            foreach ($deptIds as $deptId) {
                Cache::forget("dashboard_stats_{$year}_dept_{$deptId}");
            }
        }
    }

    /**
     * Get the core dashboard statistics for a given academic year and staff user scope.
     * 
     * @param string|null $academicYear
     * @param \App\Models\User|null $user
     * @return array
     */
    public function getStats(?string $academicYear, ?\App\Models\User $user = null): array
    {
        if (!$academicYear) {
            return [
                'totalSubmissions' => 0,
                'totalFlagged' => 0,
                'totalUnreviewedFlags' => 0,
                'distributionCounts' => []
            ];
        }

        $user = $user ?? auth()->user();

        if ($user && $user->isCounselor()) {
            if (!$user->department_id) {
                return [
                    'totalSubmissions' => 0,
                    'totalFlagged' => 0,
                    'totalUnreviewedFlags' => 0,
                    'distributionCounts' => []
                ];
            }

            $deptKey = "dept_{$user->department_id}";
            $allowedPrograms = $user->getAllowedProgramIds();
        } else {
            $deptKey = 'all';
            $allowedPrograms = null;
        }

        return Cache::remember("dashboard_stats_{$academicYear}_{$deptKey}", 300, function() use ($academicYear, $allowedPrograms) {
            $submissionsQuery = InventorySubmission::where('academic_year', $academicYear)
                ->whereNotNull('submitted_at');
            
            if ($allowedPrograms !== null) {
                $submissionsQuery->whereHas('user', fn($q) => $q->whereIn('program_id', $allowedPrograms));
            }

            $totalSubmissions = $submissionsQuery->count();
            
            $flaggedQuery = DB::table('inventory_submissions')
                ->join('inventory_flags', 'inventory_submissions.id', '=', 'inventory_flags.inventory_submission_id')
                ->where('inventory_submissions.academic_year', $academicYear)
                ->whereNotNull('inventory_submissions.submitted_at');

            if ($allowedPrograms !== null) {
                $flaggedQuery->join('users', 'inventory_submissions.user_id', '=', 'users.id')
                    ->whereIn('users.program_id', $allowedPrograms);
            }

            $totalFlagged = $flaggedQuery->distinct()->count('inventory_submissions.id');
            
            $unreviewedQuery = DB::table('inventory_flags')
                ->join('inventory_submissions', 'inventory_flags.inventory_submission_id', '=', 'inventory_submissions.id')
                ->where('inventory_submissions.academic_year', $academicYear)
                ->whereNotNull('inventory_submissions.submitted_at')
                ->where('inventory_flags.is_reviewed', false);

            if ($allowedPrograms !== null) {
                $unreviewedQuery->join('users', 'inventory_submissions.user_id', '=', 'users.id')
                    ->whereIn('users.program_id', $allowedPrograms);
            }

            $totalUnreviewedFlags = $unreviewedQuery->count();

            // DASS21 distribution
            $dass21Distribution = DB::table('inventory_scores')
                ->join('inventory_submissions', 'inventory_scores.inventory_submission_id', '=', 'inventory_submissions.id')
                ->where('inventory_submissions.academic_year', $academicYear)
                ->whereNotNull('inventory_submissions.submitted_at')
                ->whereIn('inventory_scores.category_name', ['dass21', 'DASS21', 'DASS-21'])
                ->whereNotNull('inventory_scores.severity_label');

            if ($allowedPrograms !== null) {
                $dass21Distribution->join('users', 'inventory_submissions.user_id', '=', 'users.id')
                    ->whereIn('users.program_id', $allowedPrograms);
            }

            $dass21Distribution->selectRaw("
                inventory_submissions.id as submission_id,
                MAX(CASE 
                    WHEN severity_label = 'Extremely Severe' THEN 5 
                    WHEN severity_label = 'Severe' THEN 4 
                    WHEN severity_label = 'Moderate' THEN 3 
                    WHEN severity_label = 'Mild' THEN 2 
                    WHEN severity_label = 'Normal' THEN 1 
                    ELSE 0 
                END) as max_severity
            ")->groupBy('inventory_submissions.id');

            $distributionCounts = DB::table(DB::raw("({$dass21Distribution->toSql()}) as sub"))
                ->mergeBindings($dass21Distribution)
                ->selectRaw('max_severity, COUNT(*) as count')
                ->groupBy('max_severity')
                ->pluck('count', 'max_severity')
                ->toArray();

            return [
                'totalSubmissions' => $totalSubmissions,
                'totalFlagged' => $totalFlagged,
                'totalUnreviewedFlags' => $totalUnreviewedFlags,
                'distributionCounts' => $distributionCounts,
            ];
        });
    }
}
