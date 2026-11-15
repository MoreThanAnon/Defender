<?php

namespace BusinessCore\Models;

use BusinessCore\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

/** Base for every table that carries a business_id. */
abstract class TenantModel extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];

    protected function userRelation(string $column)
    {
        return $this->belongsTo(config('business-core.user_model'), $column);
    }
}
