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
        User::create([
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
            // PLACEHOLDER ADMIN CREDENTIALS
            'email' => 'admin@citycollegeoftagaytay.edu.ph',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
