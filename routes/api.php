<?php

use App\Http\Controllers\DecidimOAuthController;
use Illuminate\Support\Facades\Route;

// Optional Decidim single sign-on (proof of concept). Off unless DECIDIM_ENABLED=true.
if (config('decidim.enabled')) {
    Route::post('/oauth/token', [DecidimOAuthController::class, 'token'])->middleware('throttle:30,1');
    Route::get('/oauth/userinfo', [DecidimOAuthController::class, 'userinfo'])->middleware('throttle:60,1');
}
