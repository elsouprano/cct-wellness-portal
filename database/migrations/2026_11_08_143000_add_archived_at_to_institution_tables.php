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
        if (! Schema::hasColumn('academic_years', 'archived_at')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable()->after('is_current');
            });
        }

        if (! Schema::hasColumn('departments', 'archived_at')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable()->after('code');
            });
        }

        if (! Schema::hasColumn('programs', 'archived_at')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable()->after('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('programs', 'archived_at')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }

        if (Schema::hasColumn('departments', 'archived_at')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }

        if (Schema::hasColumn('academic_years', 'archived_at')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }
    }
};

