<?php

namespace BusinessCore\Http\Middleware;

use BusinessCore\Models\Business;
use BusinessCore\Models\Membership;
use BusinessCore\Support\CurrentBusiness;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Enters a business for the length of one request.
 *
 *  - 404 if the business does not exist or the person has no access to it
 *    (members, plus platform administrators as read-only support)
 *  - 404 if any other record named in the URL belongs to a different business
 *  - from here on every tenant model query is limited to this business
 */
class ResolveBusiness
{
    public function __construct(private CurrentBusiness $current)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $param = $route->parameter('business');
        $business = $param instanceof Business ? $param : Business::where('slug', (string) $param)->first();
        $user = $request->user();

        abort_if(! $business || ! $user, 404);

        $membership = Membership::withoutGlobalScope('business')
            ->where('business_id', $business->id)->where('user_id', $user->getKey())->first();

        // Do not reveal that a business exists to people outside it.
        abort_if(! $membership && ! CurrentBusiness::isPlatformAdmin($user), 404);

        foreach ($route->parameters() as $bound) {
            if ($bound instanceof Model && ! $bound instanceof Business
                && array_key_exists('business_id', $bound->getAttributes())
                && (int) $bound->business_id !== (int) $business->id) {
                abort(404);
            }
        }

        $this->current->set($business, $user, $membership);
        $route->forgetParameter('business');
        URL::defaults(['business' => $business->slug]);

        try {
            return $next($request);
        } finally {
            $this->current->clear();
            URL::defaults(['business' => null]);
        }
    }
}
