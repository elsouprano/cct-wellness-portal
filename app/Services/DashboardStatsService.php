<?php

namespace App\Services;

use App\Models\InventorySubmission;
use App\Models\InventoryFlag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardStatsService
{
    /**
     * Clear cached statistics atomically across all academic years and department combinations.
     */
    public static function clearCache(?string $academicYear = null): void
    {
        Cache::forever('dashboard_stats_version', (string) microtime(true));
    }

    /**
     * Get the total count of active registered students within the staff user's scope.
     * Uncached so newly registered student accounts reflect immediately.
     */
    public function getRegisteredStudentsCount(?\App\Models\User $user = null): int
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return 0;
        }

        return \App\Models\User::where('role', 'student')
            ->where('is_active', true)
            ->whereNull('deactivated_at')
            ->accessibleByStaff($user)
            ->count();
    }

    /**
     * Get the count of distinct active students who submitted an assessment in the given academic year under staff scope.
     */
    public function getSubmittedStudentsCount(?string $academicYear, ?\App\Models\User $user = null): int
    {
        if (! $academicYear) {
            return 0;
        }

        $user = $user ?? auth()->user();
        if (! $user) {
            return 0;
        }

        return InventorySubmission::where('academic_year', $academicYear)
            ->whereNotNull('submitted_at')
            ->whereHas('user', fn ($q) => $q->where('is_active', true)->whereNull('deactivated_at'))
            ->accessibleByStaff($user)
            ->distinct()
            ->count('user_id');
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
        $emptyStats = [
            'totalSubmissions' => 0,
            'totalFlagged' => 0,
            'totalUnreviewedFlags' => 0,
            'distributionCounts' => [],
        ];

        if (!$academicYear) {
            return $emptyStats;
        }

        $user = $user ?? auth()->user();

        if ($user && $user->isCounselor()) {
            $allowedDepts = $user->getAllowedDepartmentIds();
            $allowedPrograms = $user->getAllowedProgramIds();
            if (empty($allowedDepts) || empty($allowedPrograms)) {
                return $emptyStats;
            }

            $allowedStrings = $user->getAllowedProgramStrings();
            sort($allowedDepts);
            $deptKey = count($allowedDepts) === 1 ? "dept_{$allowedDepts[0]}" : 'depts_' . implode('_', $allowedDepts);
        } else {
            $deptKey = 'all';
            $allowedPrograms = null;
            $allowedStrings = [];
        }

        $version = Cache::get('dashboard_stats_version', 'v1');
        $cacheKey = "dashboard_stats_{$version}_{$academicYear}_{$deptKey}";

        $applyUserScope = function ($query) use ($allowedPrograms, $allowedStrings) {
            if ($allowedPrograms === null) {
                return;
            }

            $query->where(function ($q) use ($allowedPrograms, $allowedStrings) {
                $q->whereIn('users.program_id', $allowedPrograms);
                if (! empty($allowedStrings)) {
                    $q->orWhere(function ($fallback) use ($allowedStrings) {
                        $fallback->whereNull('users.program_id')
                            ->whereIn('users.program', $allowedStrings);
                    });
                }
            });
        };

        return Cache::remember($cacheKey, 300, function() use ($academicYear, $allowedPrograms, $applyUserScope) {
            $submissionsQuery = DB::table('inventory_submissions')
                ->where('inventory_submissions.academic_year', $academicYear)
                ->whereNotNull('inventory_submissions.submitted_at');
            
            if ($allowedPrograms !== null) {
                $submissionsQuery->join('users', 'inventory_submissions.user_id', '=', 'users.id');
                $applyUserScope($submissionsQuery);
            }

            $totalSubmissions = $submissionsQuery->count();
            
            $flaggedQuery = DB::table('inventory_submissions')
                ->join('inventory_flags', 'inventory_submissions.id', '=', 'inventory_flags.inventory_submission_id')
                ->where('inventory_submissions.academic_year', $academicYear)
                ->whereNotNull('inventory_submissions.submitted_at');

            if ($allowedPrograms !== null) {
                $flaggedQuery->join('users', 'inventory_submissions.user_id', '=', 'users.id');
                $applyUserScope($flaggedQuery);
            }

            $totalFlagged = $flaggedQuery->distinct()->count('inventory_submissions.id');
            
            $unreviewedQuery = DB::table('inventory_flags')
                ->join('inventory_submissions', 'inventory_flags.inventory_submission_id', '=', 'inventory_submissions.id')
                ->where('inventory_submissions.academic_year', $academicYear)
                ->whereNotNull('inventory_submissions.submitted_at')
                ->where('inventory_flags.is_reviewed', false);

            if ($allowedPrograms !== null) {
                $unreviewedQuery->join('users', 'inventory_submissions.user_id', '=', 'users.id');
                $applyUserScope($unreviewedQuery);
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
                $dass21Distribution->join('users', 'inventory_submissions.user_id', '=', 'users.id');
                $applyUserScope($dass21Distribution);
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
