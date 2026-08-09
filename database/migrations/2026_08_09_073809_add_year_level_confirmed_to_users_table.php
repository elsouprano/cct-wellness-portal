<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'year_level_confirmed')) {
                $table->boolean('year_level_confirmed')->default(false)->after('year_level');
            }
        });

        // Backfill logic
        $users = DB::table('users')->whereNotNull('section')->get();
        foreach ($users as $user) {
            $section = $user->section;
            if (preg_match('/^(\d)[-\s]/', $section, $matches)) {
                $digit = $matches[1];
                $yearLevel = match($digit) {
                    '1' => '1st',
                    '2' => '2nd',
                    '3' => '3rd',
                    '4' => '4th',
                    default => null,
                };
                
                if ($yearLevel) {
                    DB::table('users')->where('id', $user->id)->update([
                        'year_level' => $yearLevel,
                        'year_level_confirmed' => false
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('year_level_confirmed');
        });
    }
};
