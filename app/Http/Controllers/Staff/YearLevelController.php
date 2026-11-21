<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\YearLevelAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class YearLevelController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'student')->accessibleByStaff(auth()->user());

        if ($request->filled('search')) {
            $search = addcslashes((string) $request->search, '%_\\');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('program', 'like', "%{$search}%")
                    ->orWhere('section', 'like', "%{$search}%");
            });
        }

        if ($request->filled('year_level')) {
            if ($request->year_level === 'unconfirmed') {
                $query->where('year_level_confirmed', false);
            } else {
                $query->where('year_level', $request->year_level);
            }
        }

        $students = $query->orderBy('last_name')->paginate(20)->withQueryString();

        return view('staff.year-level.index', compact('students'));
    }

    public function bulkPromote(Request $request)
    {
        abort_if(! $request->user()->isAdmin(), 403, 'Unauthorized. Only system administrators may perform bulk promotion.');

        $affectedCount = 0;
        $manualSectionUpdates = 0;

        DB::transaction(function () use ($request, &$affectedCount, &$manualSectionUpdates) {
            User::where('role', 'student')
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('year_level', '!=', '4th')->orWhereNull('year_level');
                })
                ->chunkById(200, function ($students) use ($request, &$affectedCount, &$manualSectionUpdates) {
                    $auditRecords = [];
                    $changedLevelUserIds = [];
                    $allPromotedIds = [];

                    foreach ($students as $student) {
                        $oldLevel = $student->year_level;
                        $newLevel = match ($oldLevel) {
                            '1st' => '2nd',
                            '2nd' => '3rd',
                            '3rd' => '4th',
                            default => '1st', // If null, assume 1st
                        };

                        $oldSection = $student->section;
                        if (! $student->syncSectionToYearLevel($newLevel)) {
                            $manualSectionUpdates++;
                        }

                        if ($oldLevel !== $newLevel) {
                            $changedLevelUserIds[] = $student->id;
                        }

                        $student->year_level = $newLevel;
                        $student->year_level_confirmed = true;
                        $student->save();
                        $allPromotedIds[] = $student->id;

                        $auditRecords[] = [
                            'user_id' => $student->id,
                            'actor_id' => $request->user()->id,
                            'action' => 'bulk_promote',
                            'old_year_level' => $oldLevel,
                            'new_year_level' => $newLevel,
                            'old_section' => $oldSection,
                            'new_section' => $student->section,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $affectedCount++;
                    }

                    // Batch-discard unfinished drafts for students whose level changed
                    if (! empty($changedLevelUserIds)) {
                        $draftIds = DB::table('inventory_submissions')
                            ->whereIn('user_id', $changedLevelUserIds)
                            ->whereNull('submitted_at')
                            ->pluck('id')
                            ->all();

                        if (! empty($draftIds)) {
                            DB::table('inventory_responses')->whereIn('inventory_submission_id', $draftIds)->delete();
                            DB::table('inventory_item_timings')->whereIn('inventory_submission_id', $draftIds)->delete();
                            DB::table('inventory_submissions')->whereIn('id', $draftIds)->delete();
                        }
                    }

                    if (! empty($auditRecords)) {
                        YearLevelAuditLog::insert($auditRecords);
                    }

                    foreach ($allPromotedIds as $id) {
                        Cache::forget("user_{$id}");
                        Cache::forget("student_profile_{$id}");
                    }
                });

            if ($affectedCount > 0) {
                app(\App\Services\ActivityLogService::class)->log(
                    action: 'student.bulk_promoted',
                    module: 'Student Management',
                    description: "Bulk promoted {$affectedCount} student(s) to the next year level ({$manualSectionUpdates} requiring manual section update).",
                    newValues: [
                        'affected_count' => $affectedCount,
                        'manual_section_updates' => $manualSectionUpdates,
                    ],
                    critical: true,
                );
            }
        });

        if ($affectedCount > 0) {
            \App\Services\DashboardStatsService::clearCache();
            AnalyticsController::clearCache();
        }

        $message = "Successfully promoted {$affectedCount} students.";
        if ($manualSectionUpdates > 0) {
            $message .= " {$manualSectionUpdates} students need their section updated manually.";
        }

        return redirect()->route('year-levels.index')->with($manualSectionUpdates > 0 ? 'warning' : 'success', $message);
    }

    public function override(Request $request, User $user)
    {
        if (! $request->user()->canAccessStudent($user)) {
            abort(404);
        }

        abort_if(! $request->user()->isAdmin(), 403, 'Unauthorized. Only admins may change year levels.');

        if ($user->role !== 'student') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Can only update students.'], 422);
            }

            return back()->withErrors(['error' => 'Can only update students.']);
        }

        $validated = $request->validate([
            'year_level' => 'required|in:1st,2nd,3rd,4th',
        ]);

        $oldLevel = $user->year_level;
        $newLevel = $validated['year_level'];

        if ($oldLevel !== $newLevel || ! $user->year_level_confirmed) {
            $manualUpdateNeeded = false;
            $oldSection = $user->section;
            if ($oldLevel !== $newLevel) {
                if (! $user->syncSectionToYearLevel($newLevel)) {
                    $manualUpdateNeeded = true;
                }
            }

            DB::transaction(function () use ($user, $request, $oldLevel, $newLevel, $oldSection) {
                // Discard unfinished draft submissions for old year level questions
                if ($oldLevel !== $newLevel) {
                    $user->discardUnfinishedDraft();
                }

                $user->year_level = $newLevel;
                $user->year_level_confirmed = true;
                $user->save();

                $auditLog = YearLevelAuditLog::create([
                    'user_id' => $user->id,
                    'actor_id' => $request->user()->id,
                    'action' => 'individual_override',
                    'old_year_level' => $oldLevel,
                    'new_year_level' => $newLevel,
                    'old_section' => $oldSection,
                    'new_section' => $user->section,
                ]);

                app(\App\Services\ActivityLogService::class)->log(
                    action: 'student.year_level_overridden',
                    module: 'Student Management',
                    description: "Updated year level for {$user->first_name} {$user->last_name} from " . ($oldLevel ?? 'Unassigned') . " to {$newLevel}.",
                    subject: $user,
                    oldValues: ['year_level' => $oldLevel, 'section' => $oldSection],
                    newValues: ['year_level' => $newLevel, 'section' => $user->section],
                    sourceType: YearLevelAuditLog::class,
                    sourceId: $auditLog->id,
                    critical: true,
                );
            });

            // Clear any cached copy of student data
            Cache::forget("user_{$user->id}");
            Cache::forget("student_profile_{$user->id}");

            $msg = "Year level updated to {$newLevel}.";
            if ($manualUpdateNeeded) {
                $msg .= ' The section was left unchanged and needs manual update.';
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'year_level' => $user->year_level,
                    'section' => $user->section,
                    'section_matches' => $user->sectionMatchesYearLevel(),
                    'warning' => $manualUpdateNeeded ? 'The section was left unchanged and needs manual update.' : null,
                ]);
            }

            return back()->with($manualUpdateNeeded ? 'warning' : 'success', $msg);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Year level is already {$newLevel}.",
                'year_level' => $user->year_level,
                'section' => $user->section,
                'section_matches' => $user->sectionMatchesYearLevel(),
            ]);
        }

        return back()->with('info', "Year level is already {$newLevel}.");
    }

    public function audit()
    {
        $logs = YearLevelAuditLog::with(['user', 'actor'])
            ->whereHas('user', fn ($q) => $q->accessibleByStaff(auth()->user()))
            ->latest()
            ->paginate(50);

        return view('staff.year-level.audit', compact('logs'));
    }
}
