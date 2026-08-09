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
        if (DB::getDriverName() !== 'sqlite') {
            // Add 'system_admin' to enum values
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'guidance_counselor', 'admin', 'system_admin') NOT NULL DEFAULT 'student'");
        }

        // Update existing 'admin' rows to 'system_admin'
        DB::table('users')->where('role', 'admin')->update(['role' => 'system_admin']);

        if (DB::getDriverName() !== 'sqlite') {
            // Remove 'admin' from enum values
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'guidance_counselor', 'system_admin') NOT NULL DEFAULT 'student'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'guidance_counselor', 'admin', 'system_admin') NOT NULL DEFAULT 'student'");
        }
        DB::table('users')->where('role', 'system_admin')->update(['role' => 'admin']);
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'guidance_counselor', 'admin') NOT NULL DEFAULT 'student'");
        }
    }
};
