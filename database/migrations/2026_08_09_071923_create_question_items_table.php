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
        Schema::create('question_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_category_id')->constrained()->cascadeOnDelete();
            $table->integer('item_number');
            $table->text('prompt');
            $table->json('options')->nullable();
            $table->string('subscale_tag')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_items');
    }
};
