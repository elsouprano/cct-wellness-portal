<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\InventoryFlag;
use App\Models\InventorySubmission;
use App\Models\AcademicYear;
use App\Services\DashboardStatsService;
use App\Http\Controllers\Staff\AnalyticsController;
use Illuminate\Http\Request;

class FlaggedResponseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $academicYearModel = AcademicYear::where('is_current', true)->first();
        $academicYear = $request->input('academic_year', $academicYearModel?->label);
        $academicYears = AcademicYear::orderByDesc('label')->pluck('label');

        $status = $request->input('status', 'unreviewed'); // 'all', 'unreviewed', 'reviewed'
        $flagType = $request->input('flag_type');
        $search = $request->input('search');

        $query = InventoryFlag::query()
            ->with(['submission.user.structuredProgram.department', 'submission.qualityAssessment', 'reviewer'])
            ->whereHas('submission', function ($q) use ($user, $academicYear) {
                $q->whereNotNull('submitted_at');
                if ($academicYear) {
                    $q->where('academic_year', $academicYear);
                }
                $q->accessibleByStaff($user);
            });

        if ($status === 'unreviewed') {
            $query->where('is_reviewed', false);
        } elseif ($status === 'reviewed') {
            $query->where('is_reviewed', true);
        }

        if ($flagType && $flagType !== 'all') {
            $query->where('flag_type', $flagType);
        }

        if ($search) {
            $query->whereHas('submission.user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Summary counts
        $baseCountQuery = InventoryFlag::whereHas('submission', function ($q) use ($user, $academicYear) {
            $q->whereNotNull('submitted_at');
            if ($academicYear) {
                $q->where('academic_year', $academicYear);
            }
            $q->accessibleByStaff($user);
        });

        $totalFlagsCount = (clone $baseCountQuery)->count();
        $unreviewedCount = (clone $baseCountQuery)->where('is_reviewed', false)->count();
        $reviewedCount = (clone $baseCountQuery)->where('is_reviewed', true)->count();

        $flags = $query->orderBy('is_reviewed', 'asc')
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('staff.flags.index', compact(
            'flags',
            'status',
            'flagType',
            'search',
            'academicYear',
            'academicYears',
            'totalFlagsCount',
            'unreviewedCount',
            'reviewedCount'
        ));
    }

    public function toggleReview(Request $request, InventoryFlag $flag)
    {
        $user = $request->user();

        if (! $flag->submission || ! $user->canAccessSubmission($flag->submission)) {
            abort(404);
        }

        if ($flag->is_reviewed) {
            $flag->update([
                'is_reviewed' => false,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'reviewer_notes' => null,
            ]);
            $msg = 'Flag marked as unreviewed.';
        } else {
            $notes = $request->input('reviewer_notes', $request->input('notes'));
            $flag->update([
                'is_reviewed' => true,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $notes,
            ]);
            $msg = 'Flag marked as reviewed.';
        }

        DashboardStatsService::clearCache($flag->submission?->academic_year);
        AnalyticsController::clearCache();

        return back()->with('status', $msg);
    }
}
