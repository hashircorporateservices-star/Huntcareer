<?php

namespace App\Console\Commands;

use App\Models\AutoApplyRule;
use App\Services\AutoApplyService;
use Illuminate\Console\Command;

/**
 * Runs every minute (see routes/console.php) and fires any rule that is "due"
 * right now — i.e. today matches its run_days and the current minute matches its
 * run_at, evaluated in the rule's own timezone. This is how "at mentioned time,
 * given country" works.
 *
 * You can also run a single rule on demand:  php artisan copilot:auto-apply --rule=3
 */
class RunAutoApplyCommand extends Command
{
    protected $signature = 'copilot:auto-apply {--rule= : Run one rule immediately, ignoring schedule}';
    protected $description = 'Prepare auto-apply applications for any rules that are due.';

    public function handle(AutoApplyService $service): int
    {
        if ($ruleId = $this->option('rule')) {
            $rule = AutoApplyRule::findOrFail($ruleId);
            $count = $service->runRule($rule);
            $this->info("Rule #{$rule->id} prepared {$count} application(s).");
            return self::SUCCESS;
        }

        $due = AutoApplyRule::where('active', true)->get();

        foreach ($due as $rule) {
            foreach ($rule->dueCountriesNow() as $country) {
                $count = $service->runRule($rule, $country);
                $label = $country ?? 'all countries';
                if ($count > 0) {
                    $this->info("Scout #{$rule->id} ({$label}) prepared {$count} application(s).");
                }
            }
        }

        return self::SUCCESS;
    }
}
