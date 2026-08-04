<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GmailJobAlertService;
use Illuminate\Console\Command;

/**
 * Pulls job-alert emails from your inbox and turns them into jobs.
 * Scheduled hourly (routes/console.php). Run on demand:  php artisan copilot:ingest-email
 */
class IngestEmailJobsCommand extends Command
{
    protected $signature = 'copilot:ingest-email {--days=3}';
    protected $description = 'Ingest jobs from Gmail job-alert emails (read-only).';

    public function handle(GmailJobAlertService $service): int
    {
        foreach (User::whereHas('settings', fn ($q) => $q->where('key', 'google_refresh_token'))->get() as $user) {
            $count = $service->ingestForUser($user, (int) $this->option('days'));
            $this->info("Ingested {$count} new job(s) for {$user->email}.");
        }
        return self::SUCCESS;
    }
}
