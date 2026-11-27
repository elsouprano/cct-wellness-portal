<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('question_categories', 'allow_multiple')) {
                $table->boolean('allow_multiple')->default(false)->after('default_options');
            }
        });

        Schema::table('question_items', function (Blueprint $table) {
            if (! Schema::hasColumn('question_items', 'allow_multiple')) {
                $table->boolean('allow_multiple')->nullable()->after('options');
            }
            if (! Schema::hasColumn('question_items', 'is_required')) {
                $table->boolean('is_required')->default(true)->after('allow_multiple');
            }
        });

        Schema::table('inventory_responses', function (Blueprint $table) {
            $table->text('response_value')->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_responses', function (Blueprint $table) {
            $table->string('response_value')->change();
        });

        Schema::table('question_items', function (Blueprint $table) {
            $dropCols = array_values(array_filter(
                ['allow_multiple', 'is_required'],
                static fn (string $col): bool => Schema::hasColumn('question_items', $col)
            ));
            if ($dropCols !== []) {
                $table->dropColumn($dropCols);
            }
        });

        Schema::table('question_categories', function (Blueprint $table) {
            if (Schema::hasColumn('question_categories', 'allow_multiple')) {
                $table->dropColumn('allow_multiple');
            }
        });
    }
};
