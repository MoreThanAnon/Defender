<?php

namespace BusinessCore\Concerns;

use BusinessCore\Models\Business;
use BusinessCore\Models\Membership;

/** Add to the host application's User model. */
trait HasBusinesses
{
    public function businessMemberships()
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    public function businesses()
    {
        return $this->belongsToMany(Business::class, 'business_memberships', 'user_id', 'business_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * The platform operator (you), not a customer. Override in the User model.
     * Grants read-only support access to every business and the storage screen.
     */
    public function isPlatformAdmin(): bool
    {
        return false;
    }
}
