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
        Schema::create('response_quality_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_submission_id')->constrained('inventory_submissions')->cascadeOnDelete();
            $table->enum('classification', ['ACCEPTABLE', 'FOR REVIEW'])->index();
            $table->string('speed_status')->default('normal'); // 'normal', 'flagged', 'unavailable', 'unreliable'
            $table->string('straight_line_status')->default('normal'); // 'normal', 'flagged'
            $table->string('contradiction_status')->default('normal'); // 'normal', 'flagged', 'not_configured'
            $table->json('flag_reasons')->nullable();
            $table->json('metrics')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('assessed_at');
            $table->timestamps();

            $table->index(['inventory_submission_id', 'version'], 'rqa_sub_version_idx');
        });

        Schema::table('inventory_submissions', function (Blueprint $table) {
            $table->enum('quality_classification', ['ACCEPTABLE', 'FOR REVIEW'])->nullable()->after('academic_year')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_submissions', function (Blueprint $table) {
            $table->dropIndex(['quality_classification']);
            $table->dropColumn('quality_classification');
        });

        Schema::dropIfExists('response_quality_assessments');
    }
};
