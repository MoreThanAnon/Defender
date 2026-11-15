<?php

namespace BusinessCore\Support;

use BusinessCore\Models\Business;
use BusinessCore\Models\Membership;

/**
 * The business (tenant) the current request is working in, and what the
 * signed-in person may do there. Every tenant-owned model is automatically
 * filtered by it (see Models\Concerns\BelongsToBusiness).
 */
class CurrentBusiness
{
    private ?Business $business = null;

    private ?Membership $membership = null;

    private mixed $user = null;

    public function set(Business $business, mixed $user = null, ?Membership $membership = null): void
    {
        $this->business = $business;
        $this->user = $user;
        $this->membership = $membership;
    }

    public function clear(): void
    {
        $this->business = null;
        $this->user = null;
        $this->membership = null;
    }

    public function get(): ?Business
    {
        return $this->business;
    }

    public function id(): ?int
    {
        return $this->business?->getKey();
    }

    public function user(): mixed
    {
        return $this->user;
    }

    /** The role this person acts under here, or null when they have no access. */
    public function role(): ?string
    {
        if ($this->membership) {
            return $this->membership->role;
        }

        return static::isPlatformAdmin($this->user) ? config('business-core.platform_admin_role', 'auditor') : null;
    }

    public function can(string $ability): bool
    {
        if (! $this->business || ! ($role = $this->role())) {
            return false;
        }

        // An archived business is read-only for everyone.
        if ($this->business->status === Business::ARCHIVED && ! in_array($ability, ['view', 'reports', 'export'], true)) {
            return false;
        }

        $abilities = (array) config("business-core.roles.$role.abilities", []);

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public function authorize(string $ability): void
    {
        abort_unless($this->can($ability), 403, 'Your role in this business does not allow that.');
    }

    public static function isPlatformAdmin(mixed $user): bool
    {
        return $user !== null && method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin();
    }

    public static function displayName(mixed $user): string
    {
        if (! $user) {
            return '—';
        }

        return (string) ($user->{config('business-core.user_display', 'name')} ?: $user->email);
    }
}
