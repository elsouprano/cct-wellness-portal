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
        Schema::table('question_categories', function (Blueprint $table) {
            $table->integer('scale_min')->nullable()->after('scale_type');
            $table->integer('scale_max')->nullable()->after('scale_min');
        });

        // Backfill existing likert ranges
        DB::table('question_categories')->where('scale_type', 'likert_1_7')->update([
            'scale_min' => 1,
            'scale_max' => 7,
            'scale_type' => 'numeric_scale'
        ]);

        DB::table('question_categories')->where('scale_type', 'likert_0_3')->update([
            'scale_min' => 0,
            'scale_max' => 3,
            'scale_type' => 'numeric_scale'
        ]);

        DB::table('question_categories')->where('scale_type', 'likert_1_5')->update([
            'scale_min' => 1,
            'scale_max' => 5,
            'scale_type' => 'numeric_scale'
        ]);
        
        DB::table('question_categories')->where('scale_type', 'single_choice_no_score')->update([
            'scale_type' => 'multiple_choice_unscored'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('question_categories')->where('scale_type', 'numeric_scale')
            ->where('scale_min', 1)->where('scale_max', 7)->update(['scale_type' => 'likert_1_7']);
            
        DB::table('question_categories')->where('scale_type', 'numeric_scale')
            ->where('scale_min', 0)->where('scale_max', 3)->update(['scale_type' => 'likert_0_3']);
            
        DB::table('question_categories')->where('scale_type', 'numeric_scale')
            ->where('scale_min', 1)->where('scale_max', 5)->update(['scale_type' => 'likert_1_5']);
            
        DB::table('question_categories')->where('scale_type', 'multiple_choice_unscored')->update([
            'scale_type' => 'single_choice_no_score'
        ]);

        Schema::table('question_categories', function (Blueprint $table) {
            $table->dropColumn(['scale_min', 'scale_max']);
        });
    }
};
