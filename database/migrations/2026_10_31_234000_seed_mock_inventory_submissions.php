<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Don't pollute test database when RefreshDatabase runs during tests
        if (app()->environment('testing')) {
            return;
        }

        // Execute the mock response seeder to generate realistic passed and issue student submissions
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MockStudentResponseSeeder',
            '--force' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert mock submissions generated for these specific mock student accounts
        $mockEmails = [
            's1@cct.edu.ph',
            's2@cct.edu.ph',
            's3@cct.edu.ph',
            's4@cct.edu.ph',
            's5@cct.edu.ph',
            'stud1113@citycollegeoftagaytay.edu.ph',
            'jasmine.ilagan@citycollegeoftagaytay.edu.ph',
        ];

        $userIds = \App\Models\User::whereIn('email', $mockEmails)->pluck('id');
        $submissions = \App\Models\InventorySubmission::whereIn('user_id', $userIds)->get();

        foreach ($submissions as $sub) {
            $sub->responses()->delete();
            $sub->timings()->delete();
            $sub->scores()->delete();
            $sub->flags()->delete();
            $sub->qualityAssessments()->delete();
            $sub->delete();
        }
    }
};

