<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AutoApplyQueue;
use App\Models\AutoApplyRule;
use App\Models\AuditLog;
use App\Models\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The scheduled auto-apply engine.
 *
 * For one rule it: finds matching jobs -> scores them -> tailors resume + cover
 * letter -> drops a fully-prepared item into auto_apply_queue with status
 * `pending_review`. It STOPS there. Nothing is submitted to a third-party board.
 *
 * Actual submission is a separate, explicit step (see submitApproved()), triggered
 * only after you approve an item in the Review Queue.
 */
class AutoApplyService
{
    public function __construct(
        protected JobMatchingService $matcher,
        protected ResumeTailoringService $tailor,
        protected CoverLetterService $coverLetters,
    ) {}

    /**
     * Prepare (not submit) up to max_per_run applications for a rule.
     *
     * @return int number of items queued
     */
    public function runRule(AutoApplyRule $rule, ?string $country = null): int
    {
        if (! $rule->active) {
            return 0;
        }

        $isAdmin = (bool) $rule->user->is_admin;

        // Prep cap only. Admins (you) have none. Preparing a large backlog is safe;
        // submission is capped separately (also lifted for admins).
        if ($isAdmin) {
            $limit = 1000;                        // effectively unlimited per run for you
        } else {
            $preparedToday = AutoApplyQueue::where('user_id', $rule->user_id)
                ->whereDate('prepared_at', now($rule->timezone)->toDateString())
                ->count();
            $globalRemaining = max(0, config('copilot.auto_apply.prep_daily_cap') - $preparedToday);
            if ($globalRemaining === 0) {
                return 0;
            }
            $limit = min($rule->max_per_run, $globalRemaining);
        }
        $resume = $rule->resume ?? $rule->user->baseResume;

        if (! $resume) {
            AuditLog::record('auto_apply.skipped_no_resume', $rule);
            return 0;
        }

        $candidates = $this->findCandidateJobs($rule, $country)->take($limit * 3); // over-fetch, filter by score
        $queued = 0;

        foreach ($candidates as $job) {
            if ($queued >= $limit) {
                break;
            }

            // Never queue something already applied to or already in the queue.
            if ($this->alreadyHandled($rule->user_id, $job->id)) {
                continue;
            }

            $match = $this->matcher->scoreAndStore($job, $resume);
            $score = $match->score;
            $tier  = max(50, (int) $rule->min_match_score);   // tiers: 50 / 75 / 100 (floor 50)

            // Decide what to do with this score:
            //   >= tier                       -> full application prepared (tailored)
            //   50..tier                      -> borderline: review-only suggestion
            //   25..50 (only if opted in)     -> borderline: review-only suggestion
            //   < 25 (or <50 without opt-in)  -> skip
            $full = $score >= $tier;
            $borderline = ! $full && ($score >= 50 || ($rule->include_below_threshold && $score >= 25));
            if (! $full && ! $borderline) {
                continue;
            }

            $queued += DB::transaction(function () use ($rule, $job, $resume, $match, $full) {
                // Borderline suggestions are NOT tailored or auto-applied — they're
                // just surfaced for your optional review.
                $tailoredResume = $full && $rule->tailor_resume
                    ? $this->tailor->tailorForJob($resume, $job)
                    : $resume;

                $coverLetter = $full && $rule->generate_cover_letter
                    ? $this->coverLetters->generate($rule->user, $job, $tailoredResume)
                    : null;

                $method = match (true) {
                    $job->is_direct_ats                         => 'ats_api',
                    str_starts_with($job->apply_url, 'mailto:') => 'email_draft',
                    default                                     => 'browser_assisted',
                };

                AutoApplyQueue::create([
                    'user_id'            => $rule->user_id,
                    'auto_apply_rule_id' => $rule->id,
                    'job_id'             => $job->id,
                    'resume_id'          => $tailoredResume->id,
                    'cover_letter_id'    => $coverLetter?->id,
                    'match_score'        => $match->score,
                    'is_borderline'      => ! $full,
                    'status'             => 'pending_review',
                    'submit_method'      => $method,
                    'prepared_summary'   => $full
                        ? $this->summaryLine($job, $match->score, $method)
                        : "{$match->score}% — below your tier. Review only; not auto-prepared.",
                ]);

                AuditLog::record('auto_apply.prepared', $job, [
                    'rule_id' => $rule->id, 'score' => $match->score, 'borderline' => ! $full,
                ]);

                return 1;
            });
        }

        $rule->update(['last_run_at' => now()]);
        return $queued;
    }

    /**
     * Submit ONE approved queue item. Called from the Review Queue after you approve.
     * For ats_api jobs it posts to the official ATS. For browser_assisted it just
     * marks the item ready and returns the pre-filled apply URL for you to open —
     * it does not drive a headless browser against a board that forbids it.
     *
     * @return array{status:string, open_url?:string}
     */
    public function submitApproved(AutoApplyQueue $item): array
    {
        abort_unless($item->status === 'approved', 422, 'Item is not approved.');

        // Reputation guard: cap how many actually go out per day. Admins (you) are exempt.
        if (! $item->user->is_admin) {
            $submittedToday = AutoApplyQueue::where('user_id', $item->user_id)
                ->where('status', 'submitted')
                ->whereDate('submitted_at', now()->toDateString())
                ->count();
            if ($submittedToday >= config('copilot.auto_apply.submit_daily_cap')) {
                return [
                    'result'  => 'capped',
                    'message' => 'Daily submit limit reached. The rest stay approved and ready for tomorrow.',
                ];
            }
        }

        try {
            if ($item->submit_method === 'ats_api' && $item->job->is_direct_ats) {
                // Push to the official ATS via its authorised API.
                app(AtsSubmissionService::class)->submit($item);
                $this->recordSubmission($item, 'ats_api');
                return ['result' => 'submitted'];
            }

            if ($item->submit_method === 'email_draft') {
                // Recruiter jobs that want an emailed CV: build a pre-filled Gmail
                // compose URL. YOU open it, attach the resume, and click send.
                // We record it only once you've been handed the draft.
                $url = $this->gmailComposeUrl($item);
                $this->recordSubmission($item, 'email_draft');
                return ['result' => 'submitted', 'open_url' => $url, 'attach_resume' => true];
            }

            // Browser-assisted: hand back the apply URL. You complete + send it yourself.
            $this->recordSubmission($item, 'browser_assisted');
            return ['result' => 'submitted', 'open_url' => $item->job->apply_url];
        } catch (\Throwable $e) {
            report($e);
            $item->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            return ['result' => 'failed'];
        }
    }

    /** Pre-filled Gmail compose window (to + subject + cover-letter body). */
    protected function gmailComposeUrl(AutoApplyQueue $item): string
    {
        $to      = str_replace('mailto:', '', $item->job->apply_url);
        $subject = "Application: {$item->job->title}";
        $body    = $item->coverLetter?->body ?? '';

        return 'https://mail.google.com/mail/?view=cm&fs=1'
            . '&to=' . rawurlencode($to)
            . '&su=' . rawurlencode($subject)
            . '&body=' . rawurlencode($body);
    }

    protected function recordSubmission(AutoApplyQueue $item, string $via): void
    {
        DB::transaction(function () use ($item, $via) {
            $item->update([
                'status'       => 'submitted',
                'submitted_at' => now(),
            ]);

            Application::updateOrCreate(
                ['user_id' => $item->user_id, 'job_id' => $item->job_id],
                [
                    'resume_id'       => $item->resume_id,
                    'cover_letter_id' => $item->cover_letter_id,
                    'status'          => 'applied',
                    'submitted_via'   => $via,
                    'applied_at'      => now(),
                ]
            );

            AuditLog::record('application.submitted', $item->job, ['via' => $via]);
        });
    }

    protected function findCandidateJobs(AutoApplyRule $rule, ?string $country = null)
    {
        return Job::query()
            // Any job: match if the job title contains any of the Scout's titles.
            ->where(function ($q) use ($rule) {
                foreach ($rule->job_titles ?? [] as $title) {
                    $q->orWhere('title', 'ilike', '%' . $title . '%');
                }
            })
            ->when($country, fn ($q) => $q->where('country', $country))
            ->when($rule->remote && ! $rule->onsite, fn ($q) => $q->where('work_mode', 'remote'))
            ->when($rule->onsite && ! $rule->remote, fn ($q) => $q->whereIn('work_mode', ['onsite', 'hybrid']))
            ->when($rule->sources, fn ($q) => $q->whereIn('source', $rule->sources))
            ->when($rule->require_visa_sponsorship, fn ($q) => $q->where('visa_sponsorship', true))
            ->when($rule->salary_min, fn ($q) => $q->where('salary_max', '>=', $rule->salary_min))
            ->whereNull('closed_at')
            ->where('fetched_at', '>=', now()->subDays(14))
            ->orderByDesc('posted_at')
            ->get();
    }

    protected function alreadyHandled(int $userId, int $jobId): bool
    {
        return Application::where('user_id', $userId)->where('job_id', $jobId)->exists()
            || AutoApplyQueue::where('user_id', $userId)->where('job_id', $jobId)->exists();
    }

    protected function summaryLine(Job $job, int $score, string $method): string
    {
        $label = $method === 'ats_api' ? 'submits via official ATS on approval' : 'opens pre-filled on approval';
        return "{$score}% match · {$job->title} · {$job->country} · {$label}.";
    }
}
