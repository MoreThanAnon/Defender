<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DecidimOAuthController;

Route::post('/oauth/token', [DecidimOAuthController::class, 'token'])->middleware('throttle:30,1');
Route::get('/oauth/userinfo', [DecidimOAuthController::class, 'userinfo'])->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
