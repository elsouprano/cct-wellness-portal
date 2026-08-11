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
        Schema::create('correlated_question_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_category_id')->constrained('question_categories')->cascadeOnDelete();
            $table->foreignId('question_item_id_a')->constrained('question_items')->cascadeOnDelete();
            $table->foreignId('question_item_id_b')->constrained('question_items')->cascadeOnDelete();
            $table->enum('relationship_type', ['similar', 'inverse']);
            $table->decimal('contradiction_threshold', 5, 2)->default(75.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['question_item_id_a', 'question_item_id_b'], 'unique_item_pair');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correlated_question_pairs');
    }
};
