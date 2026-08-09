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
        Schema::table('inventory_responses', function (Blueprint $table) {
            $table->string('response_value')->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_responses', function (Blueprint $table) {
            $table->integer('response_value')->change();
        });
    }
};
