<?php

use App\Models\Department;
use App\Models\Program;
use App\Support\InstitutionData;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For fresh empty databases (e.g. during test migrations before seeders run),
        // let DepartmentSeeder populate initial data so test fixtures with fresh databases are not broken.
        if (Department::count() === 0) {
            return;
        }

        DB::transaction(function () {
            // 1. Rename existing departments in place, preserving their IDs
            foreach (InstitutionData::departmentRenames() as $oldName => $newName) {
                $oldDept = Department::where('name', $oldName)->first();
                $targetDept = Department::where('name', $newName)->first();

                if ($oldDept && ! $targetDept) {
                    $oldDept->update(['name' => $newName]);
                }
            }

            // 2. Ensure all canonical departments exist
            $departmentsByName = [];
            foreach (InstitutionData::canonicalDepartments() as $deptName => $programs) {
                $departmentsByName[$deptName] = Department::firstOrCreate(['name' => $deptName]);
            }

            // 3. Reassign legacy ABPsy to School of Arts and Sciences if present
            $sasDept = $departmentsByName['School of Arts and Sciences'] ?? Department::where('name', 'School of Arts and Sciences')->first();
            if ($sasDept) {
                $abpsy = Program::where('code', 'ABPsy')->first();
                if ($abpsy && $abpsy->department_id !== $sasDept->id) {
                    $abpsy->update(['department_id' => $sasDept->id]);
                }
            }

            // 4. Create or update canonical programs under their respective departments
            foreach (InstitutionData::canonicalDepartments() as $deptName => $programs) {
                $department = $departmentsByName[$deptName];

                foreach ($programs as $programData) {
                    $existing = Program::where('code', $programData['code'])->first();

                    if ($existing) {
                        $existing->update([
                            'department_id' => $department->id,
                            'name' => $programData['name'],
                        ]);
                    } else {
                        Program::create([
                            'department_id' => $department->id,
                            'name' => $programData['name'],
                            'code' => $programData['code'],
                        ]);
                    }
                }
            }

            // 5. Clear filter caches
            Cache::forget('filter_departments');
            Cache::forget('filter_departments_with_programs');
            Cache::forget('filter_programs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migration down() is a no-op to preserve existing student and schedule integrity.
    }
};
