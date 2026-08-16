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
        Schema::table('question_items', function (Blueprint $table) {
            $table->foreignId('question_subcategory_id')->nullable()->after('question_category_id')->constrained('question_subcategories')->nullOnDelete();
        });

        // Data Backfill
        $categories = DB::table('question_categories')->get();
        foreach ($categories as $category) {
            // Only backfill categories that use subscale_tags (DASS21, CAT)
            $itemsWithTags = DB::table('question_items')
                ->where('question_category_id', $category->id)
                ->whereNotNull('subscale_tag')
                ->where('subscale_tag', '!=', '')
                ->get();
                
            if ($itemsWithTags->isEmpty()) {
                continue;
            }

            $tags = $itemsWithTags->pluck('subscale_tag')->unique();
            $order = 1;
            foreach ($tags as $tag) {
                // Formatting name (e.g., 'worried_cluster' -> 'Worried Cluster')
                $name = ucwords(str_replace('_', ' ', $tag));
                
                $subcatId = DB::table('question_subcategories')->insertGetId([
                    'question_category_id' => $category->id,
                    'name' => $name,
                    'display_order' => $order++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('question_items')
                    ->where('question_category_id', $category->id)
                    ->where('subscale_tag', $tag)
                    ->update(['question_subcategory_id' => $subcatId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_items', function (Blueprint $table) {
            $table->dropForeign(['question_subcategory_id']);
            $table->dropColumn('question_subcategory_id');
        });
    }
};
