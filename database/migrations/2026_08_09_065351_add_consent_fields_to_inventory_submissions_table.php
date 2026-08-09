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
        Schema::table('inventory_submissions', function (Blueprint $table) {
            $table->timestamp('consent_given_at')->nullable()->after('submitted_at');
            $table->string('consent_version')->default('1.0')->after('consent_given_at');
            $table->enum('signature_type', ['drawn', 'typed'])->nullable()->after('consent_version');
            $table->longText('signature_data')->nullable()->after('signature_type');
            $table->string('signature_font')->nullable()->after('signature_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'consent_given_at',
                'consent_version',
                'signature_type',
                'signature_data',
                'signature_font'
            ]);
        });
    }
};
