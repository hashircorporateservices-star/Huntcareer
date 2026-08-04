<?php

/*
 * This is the stock Laravel services.php with the extra blocks this app needs
 * (ai, ats, google) appended. If you scaffold Laravel fresh, keep its existing
 * entries and just add the 'ai', 'ats', and 'google' keys below.
 */
return [

    // --- App-specific integrations ---

    'ai' => [
        'endpoint' => env('AI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        'key'      => env('AI_KEY'),
        'model'    => env('AI_MODEL', 'gpt-4o-mini'),
    ],

    // Adzuna aggregator (the main "all sources" search API).
    'adzuna' => [
        'app_id'  => env('ADZUNA_APP_ID'),
        'app_key' => env('ADZUNA_APP_KEY'),
    ],

    // Lemon Squeezy billing. One variant ID per plan+cycle.
    'lemonsqueezy' => [
        'api_key'        => env('LEMONSQUEEZY_API_KEY'),
        'store_id'       => env('LEMONSQUEEZY_STORE_ID'),
        'signing_secret' => env('LEMONSQUEEZY_SIGNING_SECRET'),
        'variants' => [
            'premium' => [
                'weekly'    => env('LS_PREMIUM_WEEKLY'),
                'monthly'   => env('LS_PREMIUM_MONTHLY'),
                'quarterly' => env('LS_PREMIUM_QUARTERLY'),
            ],
            'elite' => [
                'weekly'    => env('LS_ELITE_WEEKLY'),
                'monthly'   => env('LS_ELITE_MONTHLY'),
                'quarterly' => env('LS_ELITE_QUARTERLY'),
            ],
        ],
    ],

    // Lemon Squeezy billing.
    'lemonsqueezy' => [
        'api_key'        => env('LEMONSQUEEZY_API_KEY'),
        'store_id'       => env('LEMONSQUEEZY_STORE_ID'),
        'webhook_secret' => env('LEMONSQUEEZY_WEBHOOK_SECRET'),
    ],

    // Official ATS integrations — the ONLY programmatic submit path.
    'ats' => [
        'greenhouse' => [
            'enabled'    => env('ATS_GREENHOUSE_ENABLED', false),
            'token'      => env('ATS_GREENHOUSE_TOKEN'),
            'submit_url' => env('ATS_GREENHOUSE_SUBMIT_URL'),
            // Public board tokens to search, comma-separated (e.g. "stripe,airbnb").
            'boards'     => array_filter(explode(',', (string) env('ATS_GREENHOUSE_BOARDS', ''))),
        ],
        'lever' => [
            'enabled'    => env('ATS_LEVER_ENABLED', false),
            'token'      => env('ATS_LEVER_TOKEN'),
            'submit_url' => env('ATS_LEVER_SUBMIT_URL'),
        ],
    ],

    // Google OAuth — sign-in + read-only Gmail alert ingestion.
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'      => env('FACEBOOK_REDIRECT_URI'),
    ],

    // Microsoft OAuth — sign-in (socialiteproviders/microsoft).
    'microsoft' => [
        'client_id'     => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect'      => env('MICROSOFT_REDIRECT_URI'),
        'tenant'        => env('MICROSOFT_TENANT', 'common'),
    ],

];
