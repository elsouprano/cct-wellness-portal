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
        Schema::table('inventory_submissions', function (Blueprint $table) {
            $table->index('submitted_at');
        });

        Schema::table('inventory_flags', function (Blueprint $table) {
            $table->index('is_reviewed');
        });

        Schema::table('inventory_scores', function (Blueprint $table) {
            $table->index('severity_label');
            $table->index('category_name');
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->index(['first_name', 'last_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_submissions', function (Blueprint $table) {
            $table->dropIndex(['submitted_at']);
        });

        Schema::table('inventory_flags', function (Blueprint $table) {
            $table->dropIndex(['is_reviewed']);
        });

        Schema::table('inventory_scores', function (Blueprint $table) {
            $table->dropIndex(['severity_label']);
            $table->dropIndex(['category_name']);
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['first_name', 'last_name']);
        });
    }
};
