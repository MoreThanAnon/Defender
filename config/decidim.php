<?php

return [
    'base_url' => env('DECIDIM_BASE_URL', null),
    'api_token' => env('DECIDIM_API_TOKEN', null),
    'space_id' => env('DECIDIM_SPACE_ID', null),
    'assembly_id' => env('DECIDIM_ASSEMBLY_ID', null),
    'api_key' => env('DECIDIM_API_KEY', null),
    'api_secret' => env('DECIDIM_API_SECRET', null),
    'property_governance_token' => env('DECIDIM_PROPERTY_GOVERNANCE_TOKEN', env('DECIDIM_API_SECRET', null)),
    'oauth' => [
        'client_id' => env('DECIDIM_OAUTH_CLIENT_ID'),
        'client_secret' => env('DECIDIM_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('DECIDIM_OAUTH_REDIRECT_URI', 'http://localhost:3000/users/auth/laravel/callback'),
    ],
];
