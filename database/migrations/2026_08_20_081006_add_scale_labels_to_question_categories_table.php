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
        Schema::table('question_categories', function (Blueprint $table) {
            $table->json('scale_labels')->nullable()->after('scale_max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_categories', function (Blueprint $table) {
            $table->dropColumn('scale_labels');
        });
    }
};
