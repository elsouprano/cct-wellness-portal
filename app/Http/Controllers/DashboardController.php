<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\DashboardStatsService;
use App\Models\AcademicYear;
use App\Models\InventorySubmission;
use App\Models\AssessmentSchedule;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardStatsService $statsService)
    {
        $user = Auth::user();

        // Pass to standard student dashboard view if not staff
        if ($user->role === 'student') {
            return view('dashboard');
        }

        // Staff Dashboard Logic
        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYear = $currentYear ? $currentYear->label : null;

        $stats = ['totalSubmissions' => 0, 'totalUnreviewedFlags' => 0, 'dass21Stats' => []];
        $recentFlags = collect();
        $activeSchedules = collect();

        if ($academicYear) {
            // Use existing service for stats
            $rawStats = $statsService->getStats($academicYear);
            $stats['totalSubmissions'] = $rawStats['totalSubmissions'];
            $stats['totalUnreviewedFlags'] = $rawStats['totalUnreviewedFlags'];
            
            $distributionCounts = $rawStats['distributionCounts'];
            $stats['dass21Stats'] = [
                'Extremely Severe' => $distributionCounts[5] ?? 0,
                'Severe' => $distributionCounts[4] ?? 0,
                'Moderate' => $distributionCounts[3] ?? 0,
                'Mild' => $distributionCounts[2] ?? 0,
                'Normal' => $distributionCounts[1] ?? 0,
            ];

            // Recent unreviewed flags
            $recentFlags = InventorySubmission::where('academic_year', $academicYear)
                ->whereNotNull('submitted_at')
                ->whereHas('flags', function($q) {
                    $q->where('is_reviewed', false);
                })
                ->with(['user', 'flags' => function($q) {
                    $q->where('is_reviewed', false)->latest()->take(1);
                }])
                ->latest('submitted_at')
                ->take(5)
                ->get();
                
            // Active schedules
            $now = now();
            $activeSchedules = AssessmentSchedule::where('academic_year_id', $currentYear->id)
                ->where(function($q) use ($now) {
                    $q->where(function($q2) use ($now) {
                        $q2->where('open_date', '<', $now->toDateString())
                           ->orWhere(function($q3) use ($now) {
                               $q3->where('open_date', '=', $now->toDateString())
                                  ->where('open_time', '<=', $now->toTimeString());
                           });
                    })
                    ->where(function($q2) use ($now) {
                        $q2->whereNull('close_date')
                           ->orWhere('close_date', '>', $now->toDateString())
                           ->orWhere(function($q3) use ($now) {
                               $q3->where('close_date', '=', $now->toDateString())
                                  ->where('close_time', '>=', $now->toTimeString());
                           });
                    });
                })->get();
        }

        return view('dashboard', compact('stats', 'recentFlags', 'activeSchedules', 'academicYear'));
    }
}
