<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $profileIds = DB::table('question_categories')
            ->where('name', 'profile')
            ->pluck('id');

        $headsssIds = DB::table('question_categories')
            ->where('name', 'rapid_headsss')
            ->pluck('id');

        if ($profileIds->isNotEmpty()) {
            DB::table('question_categories')
                ->whereIn('id', $profileIds)
                ->update(['display_order' => 1]);

            DB::table('category_year_levels')
                ->whereIn('question_category_id', $profileIds)
                ->update(['display_order' => 1]);
        }

        if ($headsssIds->isNotEmpty()) {
            DB::table('question_categories')
                ->whereIn('id', $headsssIds)
                ->update(['display_order' => 2]);

            DB::table('category_year_levels')
                ->whereIn('question_category_id', $headsssIds)
                ->update(['display_order' => 2]);
        }
    }

    public function down(): void
    {
        $profileIds = DB::table('question_categories')
            ->where('name', 'profile')
            ->pluck('id');

        $headsssIds = DB::table('question_categories')
            ->where('name', 'rapid_headsss')
            ->pluck('id');

        if ($headsssIds->isNotEmpty()) {
            DB::table('question_categories')
                ->whereIn('id', $headsssIds)
                ->update(['display_order' => 1]);

            DB::table('category_year_levels')
                ->whereIn('question_category_id', $headsssIds)
                ->update(['display_order' => 1]);
        }

        if ($profileIds->isNotEmpty()) {
            DB::table('question_categories')
                ->whereIn('id', $profileIds)
                ->update(['display_order' => 2]);

            DB::table('category_year_levels')
                ->whereIn('question_category_id', $profileIds)
                ->update(['display_order' => 2]);
        }
    }
};
