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
            $table->index(['academic_year', 'submitted_at'], 'idx_inv_submissions_year_submitted');
        });

        Schema::table('inventory_scores', function (Blueprint $table) {
            $table->index(['inventory_submission_id', 'category_name'], 'idx_inv_scores_submission_category');
        });

        Schema::table('inventory_flags', function (Blueprint $table) {
            $table->index(['inventory_submission_id', 'is_reviewed'], 'idx_inv_flags_submission_reviewed');
        });

        Schema::table('inventory_responses', function (Blueprint $table) {
            $table->unique(['inventory_submission_id', 'category', 'item_number'], 'uniq_inv_responses_sub_cat_item');
        });

        Schema::table('inventory_item_timings', function (Blueprint $table) {
            $table->unique(['inventory_submission_id', 'category', 'item_number'], 'uniq_inv_timings_sub_cat_item');
        });

        Schema::table('question_categories', function (Blueprint $table) {
            $table->index(['academic_year_id', 'year_level', 'display_order'], 'idx_qcategories_year_lvl_order');
        });

        Schema::table('assessment_schedules', function (Blueprint $table) {
            $table->index(['academic_year_id', 'year_level', 'open_date', 'close_date'], 'idx_schedules_year_lvl_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_submissions', function (Blueprint $table) {
            $table->dropIndex('idx_inv_submissions_year_submitted');
        });

        Schema::table('inventory_scores', function (Blueprint $table) {
            $table->dropIndex('idx_inv_scores_submission_category');
        });

        Schema::table('inventory_flags', function (Blueprint $table) {
            $table->dropIndex('idx_inv_flags_submission_reviewed');
        });

        Schema::table('inventory_responses', function (Blueprint $table) {
            $table->dropUnique('uniq_inv_responses_sub_cat_item');
        });

        Schema::table('inventory_item_timings', function (Blueprint $table) {
            $table->dropUnique('uniq_inv_timings_sub_cat_item');
        });

        Schema::table('question_categories', function (Blueprint $table) {
            $table->dropIndex('idx_qcategories_year_lvl_order');
        });

        Schema::table('assessment_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_year_lvl_dates');
        });
    }
};
