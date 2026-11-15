# Defender

A business operating interface. Not a bank, not a crowdfunding platform.

An independently owned business views its finances, coordinates its people, approves work and tracks
operations here, while its own bank and its own directors keep control of every euro. Defender never
receives, holds or moves customer money and never asks for banking passwords.

## How the code is laid out

| Where | What |
| --- | --- |
| `packages/business-core/` | **The reusable module.** All business logic, database tables, screens and routes. Knows nothing about Defender. |
| `app/`, `routes/web.php`, `resources/views/auth` | The Defender host: sign-in, registration, password reset, account page, landing page. |
| `app/Http/Controllers/DecidimOAuthController.php`, `app/Services/DecidimUserProvisioningService.php` | Optional Decidim single sign-on proof of concept. Off unless `DECIDIM_ENABLED=true`. |
| `docs/legacy/` | The retired May/June 2026 fundraising design note, kept for history only. |

## What a business gets

One record per house, business or piece of land. Inside each:

- **Dashboard**: bank balance with its source and date, money in/out, things needing attention
- **Bank**: upload a CSV statement, categorise lines, assign to projects, match to payment tasks
- **Budgets & projects**, with an asset register: budget against bank-confirmed spend
- **Proposals**: discuss, vote (one person one vote, quorum), director records the decision
- **Payments**: prepared instructions only. You pay in your own bank; a statement line confirms it
- **Tasks**, **Suppliers**, **Records** (private document storage), **People** (roles and invitations)
- **Reports**: monthly management report (print to PDF, CSV), sign-off, audit history, full zip export

Roles: director, finance lead, site/operator, member, accountant/auditor. Edit them in
`config/business-core.php` (publish it first, see below).

### Storage and live

Every business starts **in storage**: a sandbox clearly labelled as practice data. The platform
administrator (`users.role = 'admin'`) has a *Storage & admin* screen listing every business, can
shelve new ones, hand one to its real owner, and mark it **live** from the business's Settings once the
real company and its bank account have been checked. Going live records the registered name, number and
bank, and can wipe practice statements. The administrator sees other people's businesses read-only.

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan db:seed      # creates the platform admin if none exists; locally also a demo business
php artisan test
```

Served by WAMP at `http://localhost/defender/public/`. No front-end build step is needed; pages use
Bootstrap from a CDN.

Upgrading a database from the old investment platform: `php artisan migrate` runs
`drop_legacy_investment_schema`, which removes the old tables and the money columns on `users`. It cannot
be undone, so take a dump first.

## Using business-core in another Laravel project

1. Copy `packages/business-core` into the other project (or move it to its own git repository).
2. In that project's `composer.json`:
   ```json
   "repositories": [{ "type": "path", "url": "packages/business-core" }],
   "require": { "local/business-core": "@dev" }
   ```
   then `composer update local/business-core`. The service provider is auto-discovered.
3. Add `use BusinessCore\Concerns\HasBusinesses;` to the User model and override `isPlatformAdmin()`.
4. `php artisan migrate`. Screens are at `/businesses`.
5. Optional: `php artisan vendor:publish --tag=business-core-config` to change roles, business types,
   categories, the route prefix, the layout (`layout`), or which user attribute is shown as a name
   (`user_display`). `--tag=business-core-views` copies the Blade files for restyling.

It needs a `login` route and the `auth` middleware from the host, and nothing else.

Extension points, all in `BusinessCore\Contracts`:

- `StatementParser`: bind your own to read another bank format
- `AuditRecorder`: send audit events somewhere else as well
- `BankConnector`: placeholder for a future read-only feed from a licensed provider; not implemented

## Rules the code enforces

- Every tenant table has `business_id`; queries are scoped to the current business and a URL that
  names another business's record returns 404 (`tests/Feature/TenantIsolationTest.php`).
- Imported bank lines cannot be edited. Audit events cannot be edited or deleted.
- A statement file imports whole or not at all, and never twice.
- A payment task is only "confirmed" by a bank statement line for the same amount.
- A balance is always shown with its source and as-of date, and flagged when stale.

## Not built yet

Two-factor sign-in, emailing invitations (a link is shown to copy), Defender's own subscription
billing, OFX import, the ownership viewer, maps, cameras and the 3D twin.
