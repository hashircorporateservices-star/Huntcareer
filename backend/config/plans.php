<?php

/**
 * Subscription plans. Prices in AED. `save` percentages match the pricing toggle.
 * Limits are enforced at the app layer (Scout count, daily match cap, credits).
 *
 * A "Scout" is one auto-apply agent (an auto_apply_rule). Premium = 1, Elite = 3.
 */
return [

    'currency' => 'AED',

    // Lemon Squeezy variant IDs per plan+cycle. Fill from your LS dashboard.
    // The webhook reverse-maps a variant_id back to [plan, cycle].
    'variants' => [
        'premium' => [
            'weekly'    => env('LS_VARIANT_PREMIUM_WEEKLY'),
            'monthly'   => env('LS_VARIANT_PREMIUM_MONTHLY'),
            'quarterly' => env('LS_VARIANT_PREMIUM_QUARTERLY'),
        ],
        'elite' => [
            'weekly'    => env('LS_VARIANT_ELITE_WEEKLY'),
            'monthly'   => env('LS_VARIANT_ELITE_MONTHLY'),
            'quarterly' => env('LS_VARIANT_ELITE_QUARTERLY'),
        ],
    ],

    'billing_cycles' => [
        'weekly'    => ['label' => 'Weekly',              'save' => 0],
        'monthly'   => ['label' => 'Monthly (save 43%)',  'save' => 43],
        'quarterly' => ['label' => 'Quarterly (save 55%)','save' => 55],
    ],

    'plans' => [
        'premium' => [
            'name'  => 'Premium',
            'price' => ['weekly' => 39, 'monthly' => 99, 'quarterly' => 267],
            'limits' => [
                'scouts'                  => 1,
                'daily_match_cap'         => 20,
                'monthly_credits'         => 12,
                'tailor_every_application'=> false,
            ],
            'features' => [
                '1 Scout',
                'Up to 20 job matches daily',
                'Automate applications',
                'Save applications for review',
                'Hiring manager contacts',
                'Job application tracker',
                '12 credits / month',
                'Chrome extension',
                'AI resume builder',
                'AI cover letter builder',
                'AI interview roleplay',
                'AI career tools',
            ],
        ],

        'elite' => [
            'name'  => 'Elite',
            'price' => ['weekly' => 49, 'monthly' => 129, 'quarterly' => 349],
            'highlight' => true,
            'limits' => [
                'scouts'                  => 3,
                'daily_match_cap'         => 50,
                'monthly_credits'         => 20,
                'tailor_every_application'=> true,
            ],
            'features' => [
                '3 Scouts',
                'Up to 50 job matches daily',
                'Tailor your resume for every application',
                'Automate applications',
                'Save applications for review',
                'Hiring manager contacts',
                'Job application tracker',
                '20 credits / month',
                'Chrome extension',
                'AI resume builder',
                'AI cover letter builder',
                'AI interview roleplay',
                'AI career tools',
            ],
        ],
    ],
];
