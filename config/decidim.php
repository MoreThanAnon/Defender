<?php

/*
| Optional Decidim governance integration (deferred; proof of concept only).
| With "enabled" false no Decidim routes are registered and nothing is sent to Decidim.
*/
return [
    'enabled' => (bool) env('DECIDIM_ENABLED', false),
    'dry_run' => (bool) env('DECIDIM_DRY_RUN', true),
    'base_url' => env('DECIDIM_BASE_URL'),
    'property_governance_token' => env('DECIDIM_PROPERTY_GOVERNANCE_TOKEN', env('DECIDIM_API_SECRET')),
    'oauth' => [
        'client_id' => env('DECIDIM_OAUTH_CLIENT_ID'),
        'client_secret' => env('DECIDIM_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('DECIDIM_OAUTH_REDIRECT_URI', 'http://localhost:3000/users/auth/laravel/callback'),
    ],
];
