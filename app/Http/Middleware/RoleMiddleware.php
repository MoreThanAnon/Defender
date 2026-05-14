<?php

namespace App\Http\Middleware;

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

        if ($roles !== [] && ! collect($roles)->contains(fn (string $role) => $request->user()->hasRole($role))) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        return $next($request);
    }
}
