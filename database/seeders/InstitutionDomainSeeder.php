<?php

namespace Database\Seeders;

use App\Models\InstitutionDomain;
use Illuminate\Database\Seeder;

class InstitutionDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InstitutionDomain::updateOrCreate(
            ['domain' => 'citycollegeoftagaytay.edu.ph'],
            [
                'institution_name' => 'City College of Tagaytay',
                'is_active' => true,
            ]
        );
    }
}

