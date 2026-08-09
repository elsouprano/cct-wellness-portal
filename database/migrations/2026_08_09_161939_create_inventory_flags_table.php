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
        Schema::create('inventory_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_submission_id')->constrained()->cascadeOnDelete();
            $table->enum('flag_type', ['speed', 'straight_line', 'contradiction']);
            $table->string('category')->nullable();
            $table->string('subscale_tag')->nullable();
            $table->json('details')->nullable();
            $table->boolean('is_reviewed')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_flags');
    }
};
