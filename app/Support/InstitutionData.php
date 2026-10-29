<?php

namespace App\Support;

class InstitutionData
{
    /**
     * Existing department renames: old name => new name.
     *
     * @return array<string, string>
     */
    public static function departmentRenames(): array
    {
        return [
            'School of Business Management' => 'School of Business and Management',
            'School of Hospitality Management' => 'School of Hospitality and Tourism Management',
            'School of Education, Arts and Sciences' => 'School of Education',
        ];
    }

    /**
     * Canonical departments and their open programs for AY 2026-2027.
     *
     * @return array<string, list<array{name: string, code: string}>>
     */
    public static function canonicalDepartments(): array
    {
        return [
            'School of Education' => [
                ['name' => 'Bachelor of Secondary Education – Major in English', 'code' => 'BSED-ENG'],
                ['name' => 'Bachelor of Secondary Education – Major in Filipino', 'code' => 'BSED-FIL'],
                ['name' => 'Bachelor of Secondary Education – Major in Mathematics', 'code' => 'BSED-MATH'],
                ['name' => 'Bachelor of Secondary Education – Major in Social Studies', 'code' => 'BSED-SS'],
                ['name' => 'Bachelor of Secondary Education – Major in General Science', 'code' => 'BSED-GS'],
            ],
            'School of Arts and Sciences' => [
                ['name' => 'BS in Psychology', 'code' => 'BSPsych'],
                ['name' => 'BS in Social Work', 'code' => 'BSSW'],
                ['name' => 'BS in Exercise and Sports Sciences – Major in Fitness and Sports Management', 'code' => 'BSESS-FSM'],
            ],
            'School of Computer Studies' => [
                ['name' => 'BS in Information Technology', 'code' => 'BSIT'],
                ['name' => 'BS in Computer Science', 'code' => 'BSCS'],
            ],
            'School of Hospitality and Tourism Management' => [
                ['name' => 'BS in Hospitality Management', 'code' => 'BSHM'],
                ['name' => 'BS in Tourism Management', 'code' => 'BSTM'],
            ],
            'School of Business and Management' => [
                ['name' => 'BS in Business Administration – Major in Marketing Management', 'code' => 'BSBA-MM'],
                ['name' => 'BS in Business Administration – Major in Human Resource Development Management', 'code' => 'BSBA-HRDM'],
                ['name' => 'BS in Office Administration', 'code' => 'BSOA'],
            ],
        ];
    }

    /**
     * Legacy program mappings (for programs moved or preserved from pre-2026 data).
     *
     * @return array<string, array{name: string, department: string}>
     */
    public static function legacyPrograms(): array
    {
        return [
            'ABPsy' => [
                'name' => 'AB in Psychology',
                'department' => 'School of Arts and Sciences',
            ],
            'BSA' => [
                'name' => 'BS in Accountancy',
                'department' => 'School of Business and Management',
            ],
            'BSBA' => [
                'name' => 'BS in Business Administration',
                'department' => 'School of Business and Management',
            ],
            'BEED' => [
                'name' => 'Bachelor of Elementary Education',
                'department' => 'School of Education',
            ],
            'BSED' => [
                'name' => 'Bachelor of Secondary Education',
                'department' => 'School of Education',
            ],
        ];
    }
}

