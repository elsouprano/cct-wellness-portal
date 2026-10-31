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

                        // Discard unfinished draft responses from old year level
                        $student->discardUnfinishedDraft();

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
                    }
                });
        });

        return $promotedCount;
    }
}

