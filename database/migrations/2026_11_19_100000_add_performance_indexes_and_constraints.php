<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('inventory_responses', ['category', 'item_number'], 'idx_inv_responses_cat_item');

        $this->addIndexIfMissing('users', ['last_name', 'first_name'], 'idx_users_last_first');
        $this->addIndexIfMissing('users', ['role', 'is_active', 'program_id'], 'idx_users_role_active_prog');
        $this->addIndexIfMissing('users', ['role', 'is_active', 'year_level'], 'idx_users_role_active_year');
        $this->addIndexIfMissing('users', ['program'], 'idx_users_program');
        $this->addIndexIfMissing('users', ['section'], 'idx_users_section');

        $this->addIndexIfMissing('inventory_flags', ['is_reviewed', 'flag_type', 'created_at'], 'idx_inv_flags_rev_type_created');

        $this->addIndexIfMissing('assessment_schedules', ['close_date', 'close_time'], 'idx_schedules_close_dt');
        $this->addIndexIfMissing('assessment_schedules', ['program_id', 'close_date', 'close_time'], 'idx_schedules_prog_close_dt');

        $this->addIndexIfMissing('inventory_submissions', ['academic_year', 'quality_classification', 'submitted_at'], 'idx_inv_sub_ay_qual_sub');

        $this->addIndexIfMissing('question_items', ['question_category_id', 'item_number'], 'idx_q_items_cat_num');

        $this->addIndexIfMissing('announcements', ['published_at', 'expires_at'], 'idx_announcements_pub_exp');
        $this->addIndexIfMissing('announcements', ['created_at'], 'idx_announcements_created');

        $this->addIndexIfMissing('inventory_scores', ['inventory_submission_id', 'category_name', 'subscale_name'], 'idx_inv_scores_sub_cat_subscale');

        $this->addIndexIfMissing('activity_logs', ['action', 'created_at'], 'idx_activity_logs_action_created');

        $this->addIndexIfMissing('year_level_audit_logs', ['created_at'], 'idx_yl_audit_created');

        // Deduplicate flag_settings by (flag_type, setting_key) if any duplicates exist before adding unique constraint
        if (Schema::hasTable('flag_settings') && ! Schema::hasIndex('flag_settings', 'uniq_flag_settings_type_key')) {
            $duplicates = DB::table('flag_settings')
                ->select('flag_type', 'setting_key', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
                ->groupBy('flag_type', 'setting_key')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicates as $dup) {
                DB::table('flag_settings')
                    ->where('flag_type', $dup->flag_type)
                    ->where('setting_key', $dup->setting_key)
                    ->where('id', '!=', $dup->keep_id)
                    ->delete();
            }

            Schema::table('flag_settings', function (Blueprint $table) {
                $table->unique(['flag_type', 'setting_key'], 'uniq_flag_settings_type_key');
            });
        }

        // Add unique index on programs.code if no duplicates exist
        if (Schema::hasTable('programs') && ! Schema::hasIndex('programs', 'uniq_programs_code')) {
            $hasDuplicateCodes = DB::table('programs')
                ->whereNotNull('code')
                ->where('code', '!=', '')
                ->select('code')
                ->groupBy('code')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if (! $hasDuplicateCodes) {
                Schema::table('programs', function (Blueprint $table) {
                    $table->unique('code', 'uniq_programs_code');
                });
            }
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('inventory_responses', 'idx_inv_responses_cat_item');
        $this->dropIndexIfExists('users', 'idx_users_last_first');
        $this->dropIndexIfExists('users', 'idx_users_role_active_prog');
        $this->dropIndexIfExists('users', 'idx_users_role_active_year');
        $this->dropIndexIfExists('users', 'idx_users_program');
        $this->dropIndexIfExists('users', 'idx_users_section');
        $this->dropIndexIfExists('inventory_flags', 'idx_inv_flags_rev_type_created');
        $this->dropIndexIfExists('assessment_schedules', 'idx_schedules_close_dt');
        $this->dropIndexIfExists('assessment_schedules', 'idx_schedules_prog_close_dt');
        $this->dropIndexIfExists('inventory_submissions', 'idx_inv_sub_ay_qual_sub');
        $this->dropIndexIfExists('question_items', 'idx_q_items_cat_num');
        $this->dropIndexIfExists('announcements', 'idx_announcements_pub_exp');
        $this->dropIndexIfExists('announcements', 'idx_announcements_created');
        $this->dropIndexIfExists('inventory_scores', 'idx_inv_scores_sub_cat_subscale');
        $this->dropIndexIfExists('activity_logs', 'idx_activity_logs_action_created');
        $this->dropIndexIfExists('year_level_audit_logs', 'idx_yl_audit_created');
        $this->dropIndexIfExists('flag_settings', 'uniq_flag_settings_type_key');
        $this->dropIndexIfExists('programs', 'uniq_programs_code');
    }

    private function addIndexIfMissing(string $table, array $columns, string $name): void
    {
        if (Schema::hasTable($table) && ! Schema::hasIndex($table, $name)) {
            Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                $t->index($columns, $name);
            });
        }
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
            Schema::table($table, function (Blueprint $t) use ($name) {
                $t->dropIndex($name);
            });
        }
    }
};

