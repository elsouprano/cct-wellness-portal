<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill all active departments into counselor_departments for existing
     * guidance_counselor accounts, idempotently and without modifying student records.
     */
    public function up(): void
    {
        $activeDepartmentIds = DB::table('departments')
            ->whereNull('archived_at')
            ->pluck('id')
            ->all();

        if (empty($activeDepartmentIds)) {
            return;
        }

        $counselors = DB::table('users')
            ->where('role', 'guidance_counselor')
            ->select('id', 'department_id')
            ->get();

        if ($counselors->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($counselors as $counselor) {
            foreach ($activeDepartmentIds as $deptId) {
                $rows[] = [
                    'user_id' => $counselor->id,
                    'department_id' => $deptId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Only populate users.department_id if currently null; never overwrite an existing value.
            if ($counselor->department_id === null) {
                DB::table('users')
                    ->where('id', $counselor->id)
                    ->whereNull('department_id')
                    ->update(['department_id' => $activeDepartmentIds[0]]);
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('counselor_departments')->insertOrIgnore($chunk);
        }
    }

    /**
     * Non-destructive reverse: preserve existing assignments.
     */
    public function down(): void
    {
        // Intentionally left non-destructive to preserve counselor department assignments.
    }
};
