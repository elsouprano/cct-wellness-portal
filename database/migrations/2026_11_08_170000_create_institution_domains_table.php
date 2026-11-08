<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('institution_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('institution_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('institution_domains')->insertOrIgnore([
            'domain' => 'citycollegeoftagaytay.edu.ph',
            'institution_name' => 'City College of Tagaytay',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_domains');
    }
};

