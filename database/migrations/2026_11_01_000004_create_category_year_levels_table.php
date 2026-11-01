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
        if (!Schema::hasTable('category_year_levels')) {
            Schema::create('category_year_levels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_category_id')->constrained('question_categories')->cascadeOnDelete();
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->cascadeOnDelete();
                $table->string('year_level', 16);
                $table->integer('display_order')->default(1);
                $table->timestamps();

                $table->unique(['question_category_id', 'year_level', 'academic_year_id'], 'cat_year_level_unique');
            });
        }

        try {
            Schema::table('question_categories', function (Blueprint $table) {
                $table->string('year_level', 32)->nullable()->change();
            });
        } catch (\Throwable $e) {
            // Ignore if change is already handled or unsupported
        }

        // Backfill existing question_categories into category_year_levels
        $categories = DB::table('question_categories')->get();
        foreach ($categories as $cat) {
            if (!empty($cat->year_level)) {
                DB::table('category_year_levels')->updateOrInsert(
                    [
                        'question_category_id' => $cat->id,
                        'year_level' => $cat->year_level,
                        'academic_year_id' => $cat->academic_year_id,
                    ],
                    [
                        'display_order' => $cat->display_order ?? 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_year_levels');
    }
};

