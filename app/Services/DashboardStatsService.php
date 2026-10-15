<?php

namespace App\Services;

use App\Models\InventorySubmission;
use App\Models\InventoryFlag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardStatsService
{
    /**
     * Clear cached statistics.
     */
    public static function clearCache(?string $academicYear = null): void
    {
        if ($academicYear) {
            Cache::forget("dashboard_stats_{$academicYear}");
        }
    }

    /**
     * Get the core dashboard statistics for a given academic year.
     * 
     * @param string $academicYear
     * @return array
     */
    public function getStats(?string $academicYear): array
    {
        if (!$academicYear) {
            return [
                'totalSubmissions' => 0,
                'totalFlagged' => 0,
                'totalUnreviewedFlags' => 0,
                'distributionCounts' => []
            ];
        }

        return Cache::remember("dashboard_stats_{$academicYear}", 300, function() use ($academicYear) {
            $totalSubmissions = InventorySubmission::where('academic_year', $academicYear)
                ->whereNotNull('submitted_at')
                ->count();
            
            $totalFlagged = DB::table('inventory_submissions')
                ->join('inventory_flags', 'inventory_submissions.id', '=', 'inventory_flags.inventory_submission_id')
                ->where('inventory_submissions.academic_year', $academicYear)
                ->whereNotNull('inventory_submissions.submitted_at')
                ->distinct()
                ->count('inventory_submissions.id');
            
            $totalUnreviewedFlags = DB::table('inventory_flags')
                ->join('inventory_submissions', 'inventory_flags.inventory_submission_id', '=', 'inventory_submissions.id')
                ->where('inventory_submissions.academic_year', $academicYear)
                ->whereNotNull('inventory_submissions.submitted_at')
                ->where('inventory_flags.is_reviewed', false)
                ->count();

            // DASS21 distribution
            $dass21Distribution = DB::table('inventory_scores')
                ->join('inventory_submissions', 'inventory_scores.inventory_submission_id', '=', 'inventory_submissions.id')
                ->where('inventory_submissions.academic_year', $academicYear)
                ->whereNotNull('inventory_submissions.submitted_at')
                ->whereIn('inventory_scores.category_name', ['dass21', 'DASS21', 'DASS-21'])
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
