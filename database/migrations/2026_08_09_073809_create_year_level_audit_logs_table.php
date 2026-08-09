<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('year_level_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // 'registration', 'bulk_promote', 'individual_override'
            $table->enum('old_year_level', ['1st', '2nd', '3rd', '4th'])->nullable();
            $table->enum('new_year_level', ['1st', '2nd', '3rd', '4th']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('year_level_audit_logs');
    }
};
