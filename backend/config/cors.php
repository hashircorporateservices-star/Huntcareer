<?php

/*
 * Cross-origin config for the first-party SPA and the browser extension.
 * supports_credentials must be true for Sanctum's cookie auth to work.
 */
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'auth/*'],

    'allowed_methods' => ['*'],

    // The frontend origin(s). Add your production domain via FRONTEND_URL.
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
