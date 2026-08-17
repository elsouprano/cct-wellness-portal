<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'School of Computer Studies' => [
                ['name' => 'Bachelor of Science in Computer Science', 'code' => 'BSCS'],
                ['name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT'],
            ],
            'School of Business Management' => [
                ['name' => 'Bachelor of Science in Business Administration', 'code' => 'BSBA'],
                ['name' => 'Bachelor of Science in Hospitality Management', 'code' => 'BSHM'],
                ['name' => 'Bachelor of Science in Tourism Management', 'code' => 'BSTM'],
            ],
            'School of Education' => [
                ['name' => 'Bachelor of Elementary Education', 'code' => 'BEED'],
                ['name' => 'Bachelor of Secondary Education', 'code' => 'BSED'],
            ],
            'School of Arts and Sciences' => [
                ['name' => 'Bachelor of Arts in Psychology', 'code' => 'AB Psych'],
            ],
        ];

        foreach ($departments as $deptName => $programs) {
            $department = Department::firstOrCreate(['name' => $deptName]);

            foreach ($programs as $prog) {
                Program::firstOrCreate([
                    'department_id' => $department->id,
                    'name' => $prog['name'],
                    'code' => $prog['code'],
                ]);
            }
        }
    }
}
