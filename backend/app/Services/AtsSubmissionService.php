<?php

namespace App\Services;

use App\Models\AutoApplyQueue;
use Illuminate\Support\Facades\Http;

/**
 * Submits an application through an OFFICIAL, authorised ATS API only
 * (Greenhouse, Lever, etc. — sources marked official_api in config/copilot.php).
 *
 * This is deliberately the ONLY code path that programmatically sends an
 * application anywhere. It refuses anything that isn't an authorised ATS job,
 * which is what keeps the whole system on the right side of job-board ToS.
 */
class AtsSubmissionService
{
    public function submit(AutoApplyQueue $item): void
    {
        $job = $item->job;

        abort_unless($job->is_direct_ats && $job->ats_provider, 422,
            'Programmatic submission is only supported for authorised ATS jobs. '
            . 'Use browser-assisted apply for this listing.');

        $config = config("services.ats.{$job->ats_provider}");
        abort_unless($config['enabled'] ?? false, 422,
            "The {$job->ats_provider} integration is not configured.");

        // Each provider has its own payload shape; this is the shared entry point.
        $payload = match ($job->ats_provider) {
            'greenhouse' => $this->greenhousePayload($item),
            'lever'      => $this->leverPayload($item),
            default      => abort(422, "Unsupported ATS provider: {$job->ats_provider}"),
        };

        Http::withToken($config['token'])
            ->timeout(45)
            ->post($config['submit_url'], $payload)
            ->throw();
    }

    protected function greenhousePayload(AutoApplyQueue $item): array
    {
        return [
            'job_id'       => $item->job->source_job_id,
            'resume_text'  => $item->resume?->parsed_text,
            'cover_letter' => $item->coverLetter?->body,
        ];
    }

    protected function leverPayload(AutoApplyQueue $item): array
    {
        return [
            'postingId'    => $item->job->source_job_id,
            'resume'       => $item->resume?->parsed_text,
            'coverLetter'  => $item->coverLetter?->body,
        ];
    }
}
