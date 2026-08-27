<?php

namespace Database\Seeders;

<<<<<<< HEAD
use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Program;
=======
use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
>>>>>>> 2dd3c26381d3a8605dd001bfac524362e84b137d

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'School of Computer Studies' => [
<<<<<<< HEAD
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
=======
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
>>>>>>> 2dd3c26381d3a8605dd001bfac524362e84b137d
        ];

        foreach ($departments as $deptName => $programs) {
            $department = Department::firstOrCreate(['name' => $deptName]);

<<<<<<< HEAD
            foreach ($programs as $programData) {
                Program::firstOrCreate([
                    'department_id' => $department->id,
                    'name' => $programData['name'],
                    'code' => $programData['code']
=======
            foreach ($programs as $prog) {
                Program::firstOrCreate([
                    'department_id' => $department->id,
                    'name' => $prog['name'],
                    'code' => $prog['code'],
>>>>>>> 2dd3c26381d3a8605dd001bfac524362e84b137d
                ]);
            }
        }
    }
}
