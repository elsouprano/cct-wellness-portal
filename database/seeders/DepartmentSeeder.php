<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Program;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'School of Computer Studies' => [
                ['name' => 'BS in Information Technology', 'code' => 'BSIT'],
                ['name' => 'BS in Computer Science', 'code' => 'BSCS'],
            ],
            'School of Business Management' => [
                ['name' => 'BS in Business Administration', 'code' => 'BSBA'],
                ['name' => 'BS in Accountancy', 'code' => 'BSA'],
            ],
            'School of Hospitality Management' => [
                ['name' => 'BS in Hospitality Management', 'code' => 'BSHM'],
                ['name' => 'BS in Tourism Management', 'code' => 'BSTM'],
            ],
            'School of Education, Arts and Sciences' => [
                ['name' => 'Bachelor of Elementary Education', 'code' => 'BEED'],
                ['name' => 'Bachelor of Secondary Education', 'code' => 'BSED'],
                ['name' => 'AB in Psychology', 'code' => 'ABPsy'],
            ]
        ];

        foreach ($departments as $deptName => $programs) {
            $department = Department::firstOrCreate(['name' => $deptName]);

            foreach ($programs as $programData) {
                Program::firstOrCreate([
                    'department_id' => $department->id,
                    'name' => $programData['name'],
                    'code' => $programData['code']
                ]);
            }
        }
    }
}
