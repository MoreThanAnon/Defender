<?php

namespace BusinessCore\Models;

class Membership extends TenantModel
{
    protected $table = 'business_memberships';

    public function user()
    {
        return $this->userRelation('user_id');
    }

    public function roleLabel(): string
    {
        return config("business-core.roles.{$this->role}.label", ucfirst($this->role));
    }
}
