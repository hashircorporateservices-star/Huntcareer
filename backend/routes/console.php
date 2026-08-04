<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Laravel 12 schedules live here. Make sure the scheduler is running on the VPS:
 *   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
 */

// Check every minute whether any auto-apply rule is due at its configured time.
Schedule::command('copilot:auto-apply')->everyMinute()->withoutOverlapping();

// Search all job sources for active Scouts every 4 hours.
Schedule::command('copilot:search')->everyFourHours()->withoutOverlapping();

// Pull job-alert emails from your inbox into jobs, hourly.
Schedule::command('copilot:ingest-email')->hourly()->withoutOverlapping();

// Nightly analytics rollup so the Analytics page loads instantly.
Schedule::command('copilot:rollup-analytics')->dailyAt('00:15');
