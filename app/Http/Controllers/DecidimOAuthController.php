<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Minimal OAuth 2.0 authorization-code provider for the Decidim POC.
 *
 * Replace this controller with Laravel Passport before production use. Codes and
 * access tokens intentionally live in the configured cache, so they expire on a
 * cache reset and do not become a second user database.
 */
class DecidimOAuthController extends Controller
{
    public function oauthAuthorize(Request $request): RedirectResponse
    {
        $request->validate([
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
            'response_type' => ['required', 'in:code'],
            'state' => ['required', 'string', 'max:512'],
        ]);

        abort_unless(
            hash_equals((string) config('decidim.oauth.client_id'), (string) $request->string('client_id'))
            && hash_equals((string) config('decidim.oauth.redirect_uri'), (string) $request->string('redirect_uri')),
            403,
            'Unknown Decidim OAuth client.'
        );

        $code = Str::random(80);
        Cache::put($this->codeCacheKey($code), [
            'user_id' => $request->user()->getKey(),
            'redirect_uri' => (string) $request->string('redirect_uri'),
        ], now()->addMinutes(2));

        return redirect()->away($request->string('redirect_uri').'?'.http_build_query([
            'code' => $code,
            'state' => (string) $request->string('state'),
        ]));
    }

    public function token(Request $request): JsonResponse
    {
        if ($request->input('grant_type') !== 'authorization_code'
            || ! is_string($request->input('code'))
            || ! filter_var($request->input('redirect_uri'), FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'invalid_request'], 400);
        }

        [$clientId, $clientSecret] = $this->clientCredentials($request);
        if (! hash_equals((string) config('decidim.oauth.client_id'), $clientId)
            || ! hash_equals((string) config('decidim.oauth.client_secret'), $clientSecret)) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        $grant = Cache::pull($this->codeCacheKey((string) $request->string('code')));
        if (! is_array($grant) || ! hash_equals($grant['redirect_uri'], (string) $request->string('redirect_uri'))) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $accessToken = Str::random(100);
        Cache::put($this->tokenCacheKey($accessToken), ['user_id' => $grant['user_id']], now()->addHour());

        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);
    }

    public function userinfo(Request $request): JsonResponse
    {
        $token = (string) Str::of((string) $request->header('Authorization'))
            ->after('Bearer ')
            ->trim();
        $grant = $token !== '' ? Cache::get($this->tokenCacheKey($token)) : null;

        if (! is_array($grant) || ! ($user = \App\Models\User::find($grant['user_id']))) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        return response()->json([
            'id' => (string) $user->getKey(),
            'email' => $user->email,
            'name' => $user->real_name ?: $user->username,
            'nickname' => $user->username,
            'avatar_url' => null,
        ]);
    }

    private function clientCredentials(Request $request): array
    {
        $basic = $request->getUser() !== null ? [$request->getUser(), $request->getPassword()] : [null, null];

        return [
            (string) ($basic[0] ?? $request->input('client_id')),
            (string) ($basic[1] ?? $request->input('client_secret')),
        ];
    }

    private function codeCacheKey(string $code): string
    {
        return 'decidim-oauth:code:'.hash('sha256', $code);
    }

    private function tokenCacheKey(string $token): string
    {
        return 'decidim-oauth:token:'.hash('sha256', $token);
    }
}
