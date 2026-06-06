<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (! $request->user()) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $allowed = collect($roles)->contains(fn (string $role) => $request->user()->hasRole($role));

        if (! $allowed && $request->session()->has('impersonator_id')) {
            $impersonator = User::find($request->session()->get('impersonator_id'));
            $allowed = $impersonator && collect($roles)->contains(fn (string $role) => $impersonator->hasRole($role));
        }

        if ($roles !== [] && ! $allowed) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        return $next($request);
    }
}
