<?php

namespace App\Console\Commands;

use App\Models\AutoApplyQueue;
use App\Models\Job;
use Illuminate\Console\Command;

/**
 * Configurable retention: removes stale jobs and cleared queue items older than
 * copilot.retention_days. Scheduled daily. Does NOT touch a user's own
 * applications/resumes — those are deleted only on account deletion or by request.
 */
class PurgeOldDataCommand extends Command
{
    protected $signature = 'copilot:purge {--days=}';
    protected $description = 'Purge stale jobs and old queue items past the retention window.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('copilot.retention_days', 90));
        $cutoff = now()->subDays($days);

        $jobs = Job::where('fetched_at', '<', $cutoff)->delete();
        $queue = AutoApplyQueue::whereIn('status', ['submitted', 'skipped', 'failed'])
            ->where('updated_at', '<', $cutoff)->delete();

        $this->info("Purged {$jobs} old jobs and {$queue} closed queue items (> {$days} days).");
        return self::SUCCESS;
    }
}
