<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(int $count = 100): void
    {
        $this->command?->info("Generating {$count} realistic student accounts across randomized year levels (1st–4th)...");

        User::factory()->count($count)->seeded()->create([
            'is_active' => true,
            'year_level_confirmed' => true,
            'needs_section_confirmation' => false,
            'email_verified_at' => now(),
        ]);
        
        $this->command?->info("Successfully generated {$count} student accounts.");
    }
}
