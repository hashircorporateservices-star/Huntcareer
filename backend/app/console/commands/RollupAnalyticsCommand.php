<?php

namespace App\Console\Commands;

use App\Models\AnalyticsSnapshot;
use App\Models\Application;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Nightly rollup so the Analytics page loads instantly instead of aggregating live.
 * Scheduled in routes/console.php as copilot:rollup-analytics.
 */
class RollupAnalyticsCommand extends Command
{
    protected $signature = 'copilot:rollup-analytics';
    protected $description = 'Snapshot today\'s application funnel per user.';

    public function handle(): int
    {
        $date = now()->toDateString();

        foreach (User::all() as $user) {
            $counts = Application::where('user_id', $user->id)
                ->selectRaw('status, count(*) as n')
                ->groupBy('status')
                ->pluck('n', 'status');

            AnalyticsSnapshot::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'applications_submitted' => (int) ($counts['applied'] ?? 0),
                    'interviews'             => (int) ($counts['interview'] ?? 0),
                    'offers'                 => (int) ($counts['offer'] ?? 0),
                    'rejections'             => (int) ($counts['rejected'] ?? 0),
                ]
            );
        }

        $this->info('Analytics rolled up for ' . $date);
        return self::SUCCESS;
    }
}
