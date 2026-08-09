<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_scores', function (Blueprint $table) {
            $table->foreignId('inventory_submission_id')->constrained()->onDelete('cascade')->after('id');
            $table->string('category_name')->after('inventory_submission_id');
            $table->string('subscale_name')->nullable()->after('category_name');
            $table->integer('raw_score')->after('subscale_name');
            $table->integer('scaled_score')->nullable()->after('raw_score');
            $table->string('severity_label')->nullable()->after('scaled_score');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_scores', function (Blueprint $table) {
            $table->dropForeign(['inventory_submission_id']);
            $table->dropColumn([
                'inventory_submission_id',
                'category_name',
                'subscale_name',
                'raw_score',
                'scaled_score',
                'severity_label'
            ]);
        });
    }
};
