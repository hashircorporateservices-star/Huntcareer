<?php

/**
 * Central config for the job-search domain. Editing this changes what the whole
 * app targets — no code changes needed.
 */
return [

    // Where OAuth sends the user after sign-in (the Next.js frontend).
    'frontend_url' => env('FRONTEND_URL', '/'),

    // ISO alpha-2 => display name. Your seven target markets.
    'countries' => [
        'GB' => 'United Kingdom',
        'IE' => 'Ireland',
        'MT' => 'Malta',
        'AU' => 'Australia',
        'NZ' => 'New Zealand',
        'DE' => 'Germany',
        'US' => 'United States',
    ],

    // Default timezone per country, used to suggest a per-country apply time.
    'country_timezones' => [
        'GB' => 'Europe/London',
        'IE' => 'Europe/Dublin',
        'MT' => 'Europe/Malta',
        'AU' => 'Australia/Sydney',
        'NZ' => 'Pacific/Auckland',
        'DE' => 'Europe/Berlin',
        'US' => 'America/New_York',
    ],

    // Legacy: title normalisation is optional now (matching is title-based).
    // Kept as an empty map so any config('copilot.role_families') call resolves.
    'role_families' => [],

    // Job titles are now FREE TEXT (any field, up to 5 per Scout). These are just
    // optional quick-pick suggestions shown in the wizard — not a hard whitelist.
    'title_suggestions' => [
        'Finance Manager', 'Financial Controller', 'Senior Accountant',
        'Accounting Manager', 'Head of Finance', 'FP&A Manager',
        'Product Manager', 'Software Engineer', 'Data Analyst',
        'Operations Manager', 'Marketing Manager', 'Sales Manager',
    ],

    'job_types' => [
        'fulltime'   => 'Full-time',
        'part_time'  => 'Part-time',
        'contractor' => 'Contractor / Temp',
        'internship' => 'Internship',
    ],

    'seniority_levels' => [
        'entry'     => 'Entry Level',
        'associate' => 'Associate Level',
        'mid_senior'=> 'Mid-to-Senior Level',
        'director'  => 'Director Level and above',
    ],

    'match_thresholds' => [
        'high'    => 'High',
        'higher'  => 'Higher',
        'highest' => 'Highest',
    ],

    'availability' => [
        'immediately' => 'Immediately',
        '1_week'      => 'In 1 week',
        '2_weeks'     => 'In 2 weeks',
        '1_month'     => 'In 1 month',
        '2_months'    => 'In 2 months',
    ],

    /**
     * Job sources. `official_api` = we have an authorised, ToS-compliant integration
     * and MAY submit programmatically. `search_only` = we read listings but ALL
     * applications go through browser-assisted review (never automated submission).
     */
    'sources' => [
        'greenhouse' => ['mode' => 'official_api'],
        'lever'      => ['mode' => 'official_api'],
        'linkedin'   => ['mode' => 'search_only'],
        'indeed'     => ['mode' => 'search_only'],
        'seek'       => ['mode' => 'search_only'],   // AU / NZ
        'stepstone'  => ['mode' => 'search_only'],   // DE
        'irishjobs'  => ['mode' => 'search_only'],   // IE
        'email_alert'=> ['mode' => 'search_only'],   // parsed from your own Gmail job alerts
    ],

    // Guardrails for the scheduler.
    //
    // prep vs submit are deliberately separate. You can PREPARE a large backlog
    // (tailored + queued for review) without any downside. What must stay small is
    // how many actually go OUT per day — that's the number that protects your email
    // reputation and keeps you off board spam filters. Raising submit_daily_cap past
    // ~30 is how job searches get an inbox blacklisted; leave it low on purpose.
    'auto_apply' => [
        'prep_daily_cap'    => 500,   // how many applications may be prepared into the queue per day
        'submit_daily_cap'  => 30,    // how many may actually be submitted per day (reputation guard)
        'default_min_score' => 80,
    ],
];
