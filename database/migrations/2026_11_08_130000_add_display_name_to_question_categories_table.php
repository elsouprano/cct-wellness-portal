<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Support\InstrumentCatalog;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('question_categories', 'display_name')) {
            Schema::table('question_categories', function (Blueprint $table) {
                $table->string('display_name', 150)->nullable()->after('name');
            });
        }

        // Idempotently populate display_name for verified instruments
        foreach (InstrumentCatalog::all() as $code => $fullName) {
            DB::table('question_categories')
                ->whereRaw('LOWER(name) = ?', [strtolower($code)])
                ->where(function ($query) {
                    $query->whereNull('display_name')
                          ->orWhere('display_name', '');
                })
                ->update(['display_name' => $fullName]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('question_categories', 'display_name')) {
            Schema::table('question_categories', function (Blueprint $table) {
                $table->dropColumn('display_name');
            });
        }
    }
};

