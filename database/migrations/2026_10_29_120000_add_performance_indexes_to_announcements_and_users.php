<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->index('published_at', 'idx_announcements_published_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_active'], 'idx_users_role_is_active');
            $table->index(['role', 'year_level'], 'idx_users_role_year_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex('idx_announcements_published_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role_is_active');
            $table->dropIndex('idx_users_role_year_level');
        });
    }
};

