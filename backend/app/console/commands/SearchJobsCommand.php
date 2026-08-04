<?php

namespace App\Console\Commands;

use App\Models\AutoApplyRule;
use App\Services\JobSearchService;
use Illuminate\Console\Command;

/**
 * Searches every enabled source for each active Scout and stores new jobs.
 * Scheduled every 4 hours (routes/console.php). On demand: php artisan copilot:search
 */
class SearchJobsCommand extends Command
{
    protected $signature = 'copilot:search {--scout= : Search one scout only}';
    protected $description = 'Search all job sources for active Scouts and store new jobs.';

    public function handle(JobSearchService $search): int
    {
        $scouts = $this->option('scout')
            ? AutoApplyRule::where('id', $this->option('scout'))->get()
            : AutoApplyRule::where('active', true)->get();

        foreach ($scouts as $scout) {
            $count = $search->runForScout($scout);
            $this->info("Scout #{$scout->id}: stored {$count} new job(s) from all sources.");
        }

        return self::SUCCESS;
    }
}
