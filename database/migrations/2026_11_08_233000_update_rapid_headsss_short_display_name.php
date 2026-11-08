<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('question_categories')
            ->whereRaw('LOWER(name) = ?', ['rapid_headsss'])
            ->update(['display_name' => 'Rapid HEADSSS Assessment (RAPID-HEADSSS)']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};

