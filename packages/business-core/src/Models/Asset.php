<?php

namespace BusinessCore\Models;

/** Land, a building, a vehicle, a piece of equipment. */
class Asset extends TenantModel
{
    protected $table = 'business_assets';

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function kindLabel(): string
    {
        return config('business-core.asset_kinds.'.$this->kind, ucfirst($this->kind));
    }
}
