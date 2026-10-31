<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('counselor_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'department_id']);
        });

        // Migrate existing department_id on guidance counselors into the pivot table
        $counselors = DB::table('users')
            ->where('role', 'guidance_counselor')
            ->whereNotNull('department_id')
            ->get();

        $now = now();
        foreach ($counselors as $counselor) {
            DB::table('counselor_departments')->insertOrIgnore([
                'user_id' => $counselor->id,
                'department_id' => $counselor->department_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counselor_departments');
    }
};

