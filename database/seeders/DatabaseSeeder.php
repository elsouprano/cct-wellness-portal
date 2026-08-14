<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@citycollegeoftagaytay.edu.ph'],
            [
                'first_name' => 'Admin',
                'middle_initial' => null,
                'last_name' => 'User',
                'birthdate' => '1990-01-01',
                'program' => 'N/A',
                'section' => 'N/A',
                'contact_number' => '00000000000',
                'address_line1' => 'CCT',
                'city' => 'Tagaytay',
                'province' => 'Cavite',
                'role' => 'system_admin',
                'is_paying_student' => false,
                'is_active' => true,
                'student_id' => null,
                // PLACEHOLDER ADMIN CREDENTIALS
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'jane.guidance@citycollegeoftagaytay.edu.ph'],
            [
                'first_name' => 'Jane',
                'middle_initial' => 'A',
                'last_name' => 'Guidance',
                'birthdate' => '1985-06-15',
                'program' => 'N/A',
                'section' => 'N/A',
                'contact_number' => '09170000000',
                'address_line1' => 'CCT',
                'city' => 'Tagaytay',
                'province' => 'Cavite',
                'role' => 'guidance_counselor',
                'is_paying_student' => false,
                'is_active' => true,
                'student_id' => null,
                // PLACEHOLDER COUNSELOR CREDENTIALS
                // Email: jane.guidance@citycollegeoftagaytay.edu.ph
                // Password: password
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            QuestionBankSeeder::class,
            InterpretationRangeSeeder::class,
            FlagSettingSeeder::class,
        ]);
    }
}
