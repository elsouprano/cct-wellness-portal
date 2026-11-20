<?php

namespace App\Services;

use App\Models\User;
use App\Models\YearLevelAuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StudentPromotionService
{
    /**
     * Automatically promote active students in 1st, 2nd, and 3rd year to the next year level (+1)
     * and flag them to select their new section upon their next visit.
     *
     * @param int|null $actorId The ID of the admin triggering the promotion
     * @return int The total number of students promoted
     */
    public function promoteStudentsToNextYearLevel(?int $actorId = null): int
    {
        $promotedCount = 0;

        DB::transaction(function () use ($actorId, &$promotedCount) {
            User::where('role', 'student')
                ->where('is_active', true)
                ->whereIn('year_level', ['1st', '2nd', '3rd'])
                ->chunkById(200, function ($students) use ($actorId, &$promotedCount) {
                    $groupedByNewYear = [];
                    $auditRecords = [];
                    $studentIds = [];

                    foreach ($students as $student) {
                        $oldLevel = $student->year_level;
                        $newLevel = match ($oldLevel) {
                            '1st' => '2nd',
                            '2nd' => '3rd',
                            '3rd' => '4th',
                            default => null,
                        };

                        if (! $newLevel) {
                            continue;
                        }

                        $groupedByNewYear[$newLevel][] = $student->id;
                        $studentIds[] = $student->id;

                        $auditRecords[] = [
                            'user_id' => $student->id,
                            'actor_id' => $actorId,
                            'action' => 'academic_year_promotion',
                            'old_year_level' => $oldLevel,
                            'new_year_level' => $newLevel,
                            'old_section' => $student->section,
                            'new_section' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $promotedCount++;
                    }

                    // Batch-discard unfinished draft responses for the entire chunk
                    if (! empty($studentIds)) {
                        $draftIds = DB::table('inventory_submissions')
                            ->whereIn('user_id', $studentIds)
                            ->whereNull('submitted_at')
                            ->pluck('id')
                            ->all();

                        if (! empty($draftIds)) {
                            DB::table('inventory_responses')->whereIn('inventory_submission_id', $draftIds)->delete();
                            DB::table('inventory_item_timings')->whereIn('inventory_submission_id', $draftIds)->delete();
                            DB::table('inventory_submissions')->whereIn('id', $draftIds)->delete();
                        }
                    }

                    // Bulk update students per new year level
                    foreach ($groupedByNewYear as $targetYear => $ids) {
                        DB::table('users')
                            ->whereIn('id', $ids)
                            ->where('role', 'student')
                            ->update([
                                'year_level' => $targetYear,
                                'section' => '', // Clear previous section so student must confirm new section
                                'needs_section_confirmation' => true,
                                'year_level_confirmed' => true,
                                'updated_at' => now(),
                            ]);
                    }

                    // Bulk insert audit records
                    if (! empty($auditRecords)) {
                        DB::table('year_level_audit_logs')->insert($auditRecords);
                    }

                    // Invalidate caches
                    foreach ($studentIds as $id) {
                        Cache::forget("user_{$id}");
                        Cache::forget("student_profile_{$id}");
                    }
                });

            if ($promotedCount > 0) {
                $actor = $actorId ? User::find($actorId) : null;
                app(ActivityLogService::class)->log(
                    action: 'student.academic_year_promoted',
                    module: 'Student Management',
                    description: "Promoted {$promotedCount} active student(s) to the next year level for the new academic year.",
                    newValues: ['promoted_count' => $promotedCount],
                    actor: $actor,
                    isSystem: $actor === null,
                    critical: true,
                );
            }
        });

        if ($promotedCount > 0) {
            DashboardStatsService::clearCache();
            \App\Http\Controllers\Staff\AnalyticsController::clearCache();
        }

        return $promotedCount;
    }
}

