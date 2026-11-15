<?php

namespace BusinessCore\Models;

class Invitation extends TenantModel
{
    protected $table = 'business_invitations';

    protected $casts = ['expires_at' => 'datetime', 'accepted_at' => 'datetime'];

    public function isOpen(): bool
    {
        return $this->accepted_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
