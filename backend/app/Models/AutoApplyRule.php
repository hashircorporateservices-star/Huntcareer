<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AutoApplyRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'active'                   => 'boolean',
        'role_families'            => 'array',
        'job_titles'               => 'array',
        'remote'                   => 'boolean',
        'remote_locations'         => 'array',
        'onsite'                   => 'boolean',
        'onsite_locations'         => 'array',
        'job_types'                => 'array',
        'seniority_levels'         => 'array',
        'time_zones'               => 'array',
        'sources'                  => 'array',
        'work_modes'               => 'array',
        'run_days'                 => 'array',
        'country_schedules'        => 'array',
        'auto_save_jobs'           => 'boolean',
        'require_visa_sponsorship' => 'boolean',
        'include_below_threshold'  => 'boolean',
        'tailor_resume'            => 'boolean',
        'generate_cover_letter'    => 'boolean',
        'require_review'           => 'boolean',
        'run_at'                   => 'datetime:H:i',
        'last_run_at'              => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function resume(): BelongsTo { return $this->belongsTo(Resume::class); }

    /**
     * Which countries are due to run right now.
     *  - If per-country schedules exist: returns the country codes whose time+day
     *    match the current minute (each in its own timezone).
     *  - Otherwise: falls back to the global schedule — returns [null] (meaning
     *    "all target countries in one run") when the global run_at is due, else [].
     *
     * @return array<string|null>
     */
    public function dueCountriesNow(): array
    {
        if (! empty($this->country_schedules)) {
            $due = [];
            foreach ($this->country_schedules as $sched) {
                $country = strtoupper($sched['country'] ?? '');
                $tz = $sched['timezone']
                    ?? config("copilot.country_timezones.$country")
                    ?? $this->timezone;

                $now = Carbon::now($tz);
                $days = $sched['days'] ?? $this->run_days ?? [];
                if ($days && ! in_array(strtolower($now->format('D')), $days, true)) {
                    continue;
                }

                $target = Carbon::parse($sched['run_at'] ?? '08:00', $tz)
                    ->setDate($now->year, $now->month, $now->day);

                if ($now->format('H:i') === $target->format('H:i')) {
                    $due[] = $country;
                }
            }
            return $due;
        }

        // No per-country schedules — use the single global schedule for all countries.
        return $this->isDueNow() ? [null] : [];
    }

    /** Target country codes derived from the Scout's locations, else all markets. */
    public function targetCountries(): array
    {
        $iso = collect($this->onsite_locations ?? [])
            ->merge($this->remote_locations ?? [])
            ->map(fn ($x) => strtoupper(substr(trim($x), 0, 2)))
            ->filter(fn ($x) => array_key_exists($x, config('copilot.countries')))
            ->unique()->values()->all();

        return $iso ?: array_keys(config('copilot.countries'));
    }

    /**
     * True when the current minute (in the rule's timezone) equals run_at AND
     * today is in run_days AND it hasn't already run this minute.
     */
    public function isDueNow(): bool
    {
        $now = Carbon::now($this->timezone);
        $today = strtolower($now->format('D'));            // mon, tue, ...

        if (! in_array($today, $this->run_days ?? [], true)) {
            return false;
        }

        $target = Carbon::parse($this->run_at, $this->timezone)
            ->setDate($now->year, $now->month, $now->day);

        if ($now->format('H:i') !== $target->format('H:i')) {
            return false;
        }

        // Guard against double-firing inside the same minute.
        return ! $this->last_run_at
            || $this->last_run_at->copy()->setTimezone($this->timezone)->format('Y-m-d H:i') !== $now->format('Y-m-d H:i');
    }
}
