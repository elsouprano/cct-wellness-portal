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
            $submission = null;
            
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

            $hasMoreAnnouncements = \App\Models\Announcement::current()->count() > 3;
            $recentAnnouncements = \App\Models\Announcement::current()
                ->latest('published_at')
                ->take(3)
                ->get();

            return view('dashboard', compact('academicYearLabel', 'activeSchedule', 'upcomingSchedule', 'hasSubmitted', 'submission', 'recentAnnouncements', 'hasMoreAnnouncements'));
        }

        // Staff Dashboard Logic
        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYear = $currentYear ? $currentYear->label : null;

        // 1. Assigned departments
        $assignedDepartments = collect();
        if ($user->isCounselor()) {
            $assignedDepartments = $user->assignedDepartments()->get();
            if ($assignedDepartments->isEmpty() && $user->assignedDepartment) {
                $assignedDepartments = collect([$user->assignedDepartment]);
            }
        } else {
            $assignedDepartments = \App\Models\Department::orderBy('name')->get();
        }

        // 2. Assigned / Registered Students (active students under staff scope)
        $registeredStudentsCount = $statsService->getRegisteredStudentsCount($user);
        $assignedStudentsCount = $registeredStudentsCount;

        // 3. Submissions for academic year
        $submittedCount = $statsService->getSubmittedStudentsCount($academicYear, $user);
        $submissionsQuery = InventorySubmission::accessibleByStaff($user)
            ->whereNotNull('submitted_at');
        if ($academicYear) {
            $submissionsQuery->where('academic_year', $academicYear);
        }

        // 4. Pending inventories
        $pendingCount = max(0, $assignedStudentsCount - $submittedCount);

        // 5. Flagged Submissions (response quality review)
        $flaggedResponsesCount = (clone $submissionsQuery)
            ->where(function ($q) {
                $q->where('quality_classification', 'FOR REVIEW')
                  ->orWhereHas('flags');
            })
            ->count();

        // 6. Completion percentage
        $completionPercentage = $assignedStudentsCount > 0 
            ? round(($submittedCount / $assignedStudentsCount) * 100, 1) 
            : null;

        // 7. Needs Attention items (Unreviewed flags)
        $unreviewedFlagsCount = \App\Models\InventoryFlag::whereHas('submission', function ($q) use ($user, $academicYear) {
            $q->whereNotNull('submitted_at')->accessibleByStaff($user);
            if ($academicYear) {
                $q->where('academic_year', $academicYear);
            }
        })->where('is_reviewed', false)->count();

        // Active schedules
        $now = now();
        $todayStr = $now->toDateString();
        $nowHi = $now->format('H:i');
        $activeSchedules = collect();
        if ($currentYear) {
            $activeSchedules = AssessmentSchedule::visibleToStaff($user)
                ->with('structuredProgram')
                ->where('academic_year_id', $currentYear->id)
                ->where(function ($q) use ($todayStr, $nowHi) {
                    $q->where(function ($q2) use ($todayStr, $nowHi) {
                        $q2->where('open_date', '<', $todayStr)
                           ->orWhere(function ($q3) use ($todayStr, $nowHi) {
                               $q3->where('open_date', '=', $todayStr)
                                  ->whereRaw('SUBSTR(open_time, 1, 5) <= ?', [$nowHi]);
                           });
                    })
                    ->where(function ($q2) use ($todayStr, $nowHi) {
                        $q2->whereNull('close_date')
                           ->orWhere('close_date', '>', $todayStr)
                           ->orWhere(function ($q3) use ($todayStr, $nowHi) {
                               $q3->where('close_date', '=', $todayStr)
                                  ->whereRaw('SUBSTR(close_time, 1, 5) >= ?', [$nowHi]);
                           });
                    });
                })->get();
        }

        // 8. Recent Unreviewed Flagged Submissions
        $recentFlags = (clone $submissionsQuery)
            ->whereHas('flags', fn ($q) => $q->where('is_reviewed', false))
            ->with(['user', 'flags' => fn ($q) => $q->where('is_reviewed', false)])
            ->latest('submitted_at')
            ->take(5)
            ->get();

        // 9. Recent Submissions
        $recentSubmissions = (clone $submissionsQuery)
            ->with(['user.structuredProgram.department', 'qualityAssessment', 'flags'])
            ->latest('submitted_at')
            ->take(6)
            ->get();

        $stats = [
            'totalSubmissions' => $submittedCount,
            'totalUnreviewedFlags' => $unreviewedFlagsCount,
        ];

        return view('dashboard', compact(
            'academicYear',
            'assignedDepartments',
            'assignedStudentsCount',
            'registeredStudentsCount',
            'submittedCount',
            'pendingCount',
            'flaggedResponsesCount',
            'completionPercentage',
            'unreviewedFlagsCount',
            'activeSchedules',
            'recentFlags',
            'recentSubmissions',
            'stats'
        ));
    }
}
