<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Program;
use App\Support\InstitutionData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Handle renames on existing databases
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

        // 4. Seed or update canonical programs
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
    }
}
