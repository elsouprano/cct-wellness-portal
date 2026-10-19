<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('inventory_submissions', 'year_level')) {
            Schema::table('inventory_submissions', function (Blueprint $table) {
                $table->string('year_level', 20)->nullable()->after('academic_year');
                $table->index('year_level');
            });
        }

        // Backfill existing submissions from users table
        $submissions = DB::table('inventory_submissions')
            ->whereNull('inventory_submissions.year_level')
            ->join('users', 'inventory_submissions.user_id', '=', 'users.id')
            ->select('inventory_submissions.id', 'users.year_level')
            ->get();

        foreach ($submissions as $sub) {
            if ($sub->year_level) {
                DB::table('inventory_submissions')
                    ->where('id', $sub->id)
                    ->update(['year_level' => $sub->year_level]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('inventory_submissions', 'year_level')) {
            Schema::table('inventory_submissions', function (Blueprint $table) {
                $table->dropIndex(['year_level']);
                $table->dropColumn('year_level');
            });
        }
    }
};
