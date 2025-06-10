<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTSessionAuth
{
    public function handle($request, Closure $next)
    {
        if (session()->has('jwt_token')) {
            try {
                JWTAuth::setToken(session('jwt_token'))->authenticate();
            } catch (\Exception $e) {
                session()->forget('jwt_token');
                return redirect()->route('login')->withErrors(['error' => 'Session expired. Please log in again.']);
            }
        }

        return $next($request);
    }
}