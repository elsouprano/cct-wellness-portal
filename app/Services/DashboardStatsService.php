<?php

namespace App\Services;

use App\Models\InventorySubmission;
use App\Models\InventoryFlag;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
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

        $statsQuery = InventorySubmission::where('academic_year', $academicYear)->whereNotNull('submitted_at');
        
        $totalSubmissions = (clone $statsQuery)->count();
        $totalFlagged = (clone $statsQuery)->whereHas('flags')->count();
        
        $totalUnreviewedFlags = InventoryFlag::whereHas('submission', function($q) use ($academicYear) {
            $q->where('academic_year', $academicYear)->whereNotNull('submitted_at');
        })->where('is_reviewed', false)->count();

        // DASS21 distribution
        $dass21Distribution = DB::table('inventory_scores')
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
    }
}
