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

        // 2. Assigned Students (active students under staff scope)
        $studentsQuery = \App\Models\User::where('role', 'student')
            ->where('is_active', true)
            ->accessibleByStaff($user);
        $assignedStudentsCount = (clone $studentsQuery)->count();

        // 3. Submissions for academic year
        $submissionsQuery = InventorySubmission::accessibleByStaff($user)
            ->whereNotNull('submitted_at');
        if ($academicYear) {
            $submissionsQuery->where('academic_year', $academicYear);
        }
        $submittedCount = (clone $submissionsQuery)->distinct('user_id')->count('user_id');

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
            : 0;

        // 7. Needs Attention items (Unreviewed flags)
        $unreviewedFlagsCount = \App\Models\InventoryFlag::whereHas('submission', function ($q) use ($user, $academicYear) {
            $q->whereNotNull('submitted_at')->accessibleByStaff($user);
            if ($academicYear) {
                $q->where('academic_year', $academicYear);
            }
        })->where('is_reviewed', false)->count();

        // Active schedules
        $now = now();
        $activeSchedules = collect();
        if ($currentYear) {
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

        // 8. Recent Submissions & Recent Flags
        $recentSubmissions = (clone $submissionsQuery)
            ->with(['user.structuredProgram.department', 'qualityAssessment', 'flags'])
            ->latest('submitted_at')
            ->take(6)
            ->get();

        $recentFlags = InventorySubmission::accessibleByStaff($user)
            ->when($academicYear, fn($q) => $q->where('academic_year', $academicYear))
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

        $stats = [
            'totalSubmissions' => $submittedCount,
            'totalUnreviewedFlags' => $unreviewedFlagsCount,
        ];

        return view('dashboard', compact(
            'academicYear',
            'assignedDepartments',
            'assignedStudentsCount',
            'submittedCount',
            'pendingCount',
            'flaggedResponsesCount',
            'completionPercentage',
            'unreviewedFlagsCount',
            'activeSchedules',
            'recentSubmissions',
            'recentFlags',
            'stats'
        ));
    }
}
