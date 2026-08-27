<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Change this variable to adjust how many users are generated.
        $count = 500;

        $this->command->info("Generating {$count} realistic student accounts...");

        User::factory()->count($count)->create();
        
        $this->command->info("Successfully generated {$count} student accounts.");
    }
}
