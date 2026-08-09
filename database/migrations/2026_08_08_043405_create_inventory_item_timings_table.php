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
        Schema::create('inventory_item_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_submission_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->integer('item_number');
            $table->integer('time_spent_ms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_item_timings');
    }
};
