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
        Schema::table('year_level_audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('year_level_audit_logs', 'old_section')) {
                $table->string('old_section')->nullable()->after('new_year_level');
            }
            if (!Schema::hasColumn('year_level_audit_logs', 'new_section')) {
                $table->string('new_section')->nullable()->after('old_section');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('year_level_audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('year_level_audit_logs', 'old_section')) {
                $table->dropColumn('old_section');
            }
            if (Schema::hasColumn('year_level_audit_logs', 'new_section')) {
                $table->dropColumn('new_section');
            }
        });
    }
};
