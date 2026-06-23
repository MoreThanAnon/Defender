# Decidim SSO proof of concept

Laravel remains the identity source. Decidim consumes a Laravel OAuth 2.0
authorization-code flow through OmniAuth and creates a Decidim identity using
`provider = laravel` and `uid = Laravel user id`.

This is intentionally a small local POC. Its Laravel provider stores one-time
codes for two minutes and access tokens for one hour in the Laravel cache.
Replace it with Laravel Passport before production; Passport could not be
installed here because Composer's HTTPS certificate verification is intercepted
by the local Avast setup.

## Local configuration

Laravel's local `.env` contains the active values. For a fresh setup, set:

```dotenv
DECIDIM_BASE_URL=http://localhost:3000
DECIDIM_OAUTH_CLIENT_ID=decidim-poc
DECIDIM_OAUTH_CLIENT_SECRET=<one-long-random-secret>
DECIDIM_OAUTH_REDIRECT_URI=http://localhost:3000/sso/laravel/callback
```

Set the exact same ID and secret in `C:\wamp64\www\Decidim\.env`:

```dotenv
LARAVEL_OAUTH_CLIENT_ID=decidim-poc
LARAVEL_OAUTH_CLIENT_SECRET=<the-same-secret>
LARAVEL_OAUTH_ENABLED=true
LARAVEL_OAUTH_AUTO_ACCEPT_TOS=true
```

The Decidim Docker service also uses:

```dotenv
LARAVEL_OAUTH_AUTHORIZE_URL=http://127.0.0.1/defender/public/oauth/authorize
LARAVEL_OAUTH_TOKEN_BASE_URL=http://host.docker.internal/defender/public
LARAVEL_OAUTH_TOKEN_URL=http://host.docker.internal/defender/public/api/oauth/token
LARAVEL_OAUTH_USERINFO_URL=http://host.docker.internal/defender/public/api/oauth/userinfo
```

Use the same browser host for Laravel everywhere. This local setup uses
`127.0.0.1`; `host.docker.internal` is required only for Decidim's container
to call Laravel's token and user-info endpoints.

After changing Decidim configuration, restart it:

```powershell
docker compose -f C:\wamp64\www\Decidim\docker-compose.yml up -d --force-recreate decidim
```

## URLs and check

1. Log in to Laravel at `http://localhost/defender/public/login`.
2. Open `http://localhost/defender/public/governance`.
3. Decidim starts `/sso/laravel`, redirects to Laravel authorization,
   then returns to `/sso/laravel/callback` as the signed-in user.
4. Confirm the participant in Decidim's admin panel and confirm the identity
   is provider `laravel` with the Laravel numeric ID as UID.

The first POC sign-in provisions the participant automatically because
`LARAVEL_OAUTH_AUTO_ACCEPT_TOS=true`. Set it to `false` in any non-POC
environment so Decidim presents and records terms acceptance instead.

## Registration and admin access

In the Decidim System panel, edit the organization at
`http://localhost:3000/system/organizations/1/edit` and choose **Existing
accounts only**. It prevents native self-registration but preserves password
login for the emergency Decidim admin account.

Do not choose **Access only with external accounts** while relying on a
password-admin fallback: in Decidim 0.31.5 that global setting also sets
`sign_in_enabled?` to false, so it disables the normal sign-in page for admins
as well. The safe POC compromise is `existing`, plus the Laravel button and
the `/governance` entry point. Enforcing external-only participants while
allowing only administrators to use passwords requires a small custom Decidim
authorization/UI override.
