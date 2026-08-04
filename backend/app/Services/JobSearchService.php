<?php

namespace App\Services;

use App\Models\AutoApplyRule;
use App\Models\Company;
use App\Models\Job;
use App\Services\Sources\AdzunaSource;
use App\Services\Sources\GreenhouseSource;
use App\Services\Sources\JobSource;
use Illuminate\Support\Carbon;

/**
 * Runs a Scout's criteria across EVERY enabled source, normalises the results,
 * dedupes, and stores new `jobs`. This is the "search from all sources" layer.
 */
class JobSearchService
{
    public function __construct(protected VisaSponsorshipDetector $visa) {}

    /** @return JobSource[] */
    protected function sources(): array
    {
        return [
            new AdzunaSource(),
            new GreenhouseSource(),
            // add more official/aggregator adapters here; Gmail alerts handled separately
        ];
    }

    /**
     * Search all sources for one Scout and persist new jobs.
     *
     * @return int number of new jobs stored
     */
    public function runForScout(AutoApplyRule $scout): int
    {
        $criteria = [
            'titles'    => $scout->job_titles ?? [],
            'countries' => $this->countriesFor($scout),
            'remote'    => (bool) $scout->remote,
            'onsite'    => (bool) $scout->onsite,
        ];

        $stored = 0;
        foreach ($this->sources() as $source) {
            foreach ($source->search($criteria) as $listing) {
                if ($this->store($listing)) {
                    $stored++;
                }
            }
        }

        return $stored;
    }

    protected function store(array $l): bool
    {
        if (empty($l['apply_url']) || empty($l['title'])) {
            return false;
        }

        // Dedupe: same source+id, or same apply URL.
        $exists = Job::where(function ($q) use ($l) {
            $q->where('apply_url', $l['apply_url']);
            if (! empty($l['source_job_id'])) {
                $q->orWhere(fn ($q2) => $q2->where('source', $l['source'])->where('source_job_id', $l['source_job_id']));
            }
        })->exists();
        if ($exists) {
            return false;
        }

        $companyId = null;
        if (! empty($l['company'])) {
            $companyId = Company::firstOrCreate(['name' => $l['company']])->id;
        }

        Job::create([
            'company_id'      => $companyId,
            'source'          => $l['source'],
            'source_job_id'   => $l['source_job_id'] ?: null,
            'apply_url'       => $l['apply_url'],
            'is_direct_ats'   => $l['is_direct_ats'] ?? false,
            'ats_provider'    => $l['ats_provider'] ?? null,
            'title'           => $l['title'],
            'country'         => $l['country'] ?? null,
            'city'            => $l['city'] ?? null,
            'work_mode'       => $l['work_mode'] ?? null,
            'salary_min'      => $l['salary_min'] ?? null,
            'salary_max'      => $l['salary_max'] ?? null,
            'salary_currency' => $l['salary_currency'] ?? null,
            'description'     => $l['description'] ?? null,
            'visa_sponsorship'=> $this->visa->detect($l['description'] ?? null),
            'posted_at'       => ! empty($l['posted_at']) ? Carbon::parse($l['posted_at']) : null,
            'fetched_at'      => now(),
        ]);

        return true;
    }

    /** Countries to search: onsite locations if given, else the target list. */
    protected function countriesFor(AutoApplyRule $scout): array
    {
        // remote_locations may be ["Worldwide"] — fall back to all target markets then.
        $iso = collect($scout->onsite_locations ?? [])
            ->merge($scout->remote_locations ?? [])
            ->map(fn ($x) => strtoupper(substr(trim($x), 0, 2)))
            ->filter(fn ($x) => array_key_exists($x, config('copilot.countries')))
            ->unique()
            ->values()
            ->all();

        return $iso ?: array_keys(config('copilot.countries'));
    }
}
