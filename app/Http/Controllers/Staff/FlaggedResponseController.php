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
            $escaped = addcslashes((string) $search, '%_\\');
            $query->whereHas('submission.user', function ($q) use ($escaped) {
                $q->where('first_name', 'like', "%{$escaped}%")
                  ->orWhere('last_name', 'like', "%{$escaped}%")
                  ->orWhere('student_id', 'like', "%{$escaped}%")
                  ->orWhere('email', 'like', "%{$escaped}%");
            });
        }

        // Summary counts (single conditional aggregate query)
        $counts = InventoryFlag::whereHas('submission', function ($q) use ($user, $academicYear) {
            $q->whereNotNull('submitted_at');
            if ($academicYear) {
                $q->where('academic_year', $academicYear);
            }
            $q->accessibleByStaff($user);
        })->selectRaw('
            COUNT(*) as total_flags,
            SUM(CASE WHEN is_reviewed = 0 THEN 1 ELSE 0 END) as unreviewed_count,
            SUM(CASE WHEN is_reviewed = 1 THEN 1 ELSE 0 END) as reviewed_count
        ')->first();

        $totalFlagsCount = (int) ($counts->total_flags ?? 0);
        $unreviewedCount = (int) ($counts->unreviewed_count ?? 0);
        $reviewedCount = (int) ($counts->reviewed_count ?? 0);

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

        $flag->loadMissing('submission.user');
        $studentName = $flag->submission?->user
            ? trim("{$flag->submission->user->first_name} {$flag->submission->user->last_name}")
            : 'a student';

        if ($flag->is_reviewed) {
            $flag->update([
                'is_reviewed' => false,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'reviewer_notes' => null,
                'review_status' => null,
            ]);
            app(\App\Services\ActivityLogService::class)->log(
                action: 'flag.unreviewed',
                module: 'Flagged Responses',
                description: "Unmarked flagged response as reviewed for {$studentName}.",
                subject: $flag,
                oldValues: ['is_reviewed' => true],
                newValues: ['is_reviewed' => false, 'flag_type' => $flag->flag_type],
            );
            $msg = 'Flag marked as unreviewed.';
        } else {
            $validated = $request->validate([
                'reviewer_notes' => 'nullable|string|max:2000',
                'notes' => 'nullable|string|max:2000',
            ]);
            $notes = $validated['reviewer_notes'] ?? ($validated['notes'] ?? null);
            $flag->update([
                'is_reviewed' => true,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $notes,
            ]);
            app(\App\Services\ActivityLogService::class)->log(
                action: 'flag.reviewed',
                module: 'Flagged Responses',
                description: "Reviewed flagged response for {$studentName}.",
                subject: $flag,
                oldValues: ['is_reviewed' => false],
                newValues: ['is_reviewed' => true, 'flag_type' => $flag->flag_type],
            );
            $msg = 'Flag marked as reviewed.';
        }

        DashboardStatsService::clearCache($flag->submission?->academic_year);
        AnalyticsController::clearCache();

        return back()->with('status', $msg);
    }
}
