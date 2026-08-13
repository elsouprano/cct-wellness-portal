<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * --------------------------------------------------------------------------
 * Database Backup Schedule
 * --------------------------------------------------------------------------
 *
 * Runs a full backup (MariaDB dump + storage/app/public files) daily at 2 AM.
 *
 * ⚠️  LOCAL / XAMPP DEVELOPMENT LIMITATION:
 * Laravel's task scheduler requires a real cron job to call `php artisan schedule:run`
 * every minute. This does NOT happen automatically on a local XAMPP/dev setup.
 *
 * To run the scheduler on your local machine you have three options:
 *
 *   Option A (manual, one-off): Just run the backup command directly:
 *     php artisan backup:run
 *
 *   Option B (Windows Task Scheduler): Set up a Windows Task that runs every
 *     minute and executes:
 *     php "C:\path\to\project\artisan" schedule:run
 *
 *   Option C (PRODUCTION / Linux server): Add this single cron entry:
 *     * * * * * cd /var/www/cct-wellness-portal && php artisan schedule:run >> /dev/null 2>&1
 *
 * TODO (PRODUCTION): Once hosted, set up the above cron entry on the server
 *   AND change the backup disk to an off-server cloud destination (see config/backup.php).
 */
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->appendOutputTo(storage_path('logs/backup.log'));
