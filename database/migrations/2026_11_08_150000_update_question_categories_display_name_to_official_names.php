<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Support\InstrumentCatalog;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update display_name for all categories in the catalog
        foreach (InstrumentCatalog::all() as $code => $fullName) {
            DB::table('question_categories')
                ->whereRaw('LOWER(name) = ?', [strtolower($code)])
                ->update(['display_name' => $fullName]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: down does not wipe display names
    }
};

