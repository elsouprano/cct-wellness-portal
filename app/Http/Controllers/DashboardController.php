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
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearLabel = $currentYear ? $currentYear->label : null;
            
            $activeSchedule = null;
            $upcomingSchedule = null;
            $hasSubmitted = false;
            
            if ($currentYear) {
                $activeSchedule = AssessmentSchedule::getActiveForUser($user, $currentYear->id);
                if (!$activeSchedule) {
                    $upcomingSchedule = AssessmentSchedule::getUpcomingForUser($user, $currentYear->id);
                }
                $submission = InventorySubmission::where('user_id', $user->id)
                    ->where('academic_year', $academicYearLabel)
                    ->whereNotNull('submitted_at')
                    ->first();
                $hasSubmitted = $submission ? true : false;
            }

            $announcements = \App\Models\Announcement::whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->latest('published_at')
                ->take(4)
                ->get();

            $hasMoreAnnouncements = $announcements->count() > 3;
            $recentAnnouncements = $announcements->take(3);

            return view('dashboard', compact('academicYearLabel', 'activeSchedule', 'upcomingSchedule', 'hasSubmitted', 'recentAnnouncements', 'hasMoreAnnouncements'));
        }

        // Staff Dashboard Logic
        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYear = $currentYear ? $currentYear->label : null;

        $stats = ['totalSubmissions' => 0, 'totalUnreviewedFlags' => 0, 'dass21Stats' => []];
        $recentFlags = collect();
        $activeSchedules = collect();

        if ($academicYear) {
            // Use existing service for stats
            $rawStats = $statsService->getStats($academicYear, $user);
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
            $recentFlags = InventorySubmission::accessibleByStaff($user)
                ->where('academic_year', $academicYear)
                ->whereNotNull('submitted_at')
                ->whereHas('flags', function($q) {
                    $q->where('is_reviewed', false);
                })
                ->with(['user', 'flags' => function($q) {
                    $q->where('is_reviewed', false)->latest();
                }])
                ->latest('submitted_at')
                ->take(5)
                ->get();
                
            // Active schedules
            $now = now();
            $activeSchedules = AssessmentSchedule::visibleToStaff($user)
                ->where('academic_year_id', $currentYear->id)
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
