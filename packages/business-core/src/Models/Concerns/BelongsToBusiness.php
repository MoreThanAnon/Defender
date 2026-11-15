<?php

namespace BusinessCore\Models\Concerns;

use BusinessCore\Models\Business;
use BusinessCore\Support\CurrentBusiness;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * Tenant boundary. While a request is inside a business, every query on the
 * model is limited to that business and new rows are stamped with it.
 */
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $query) {
            if ($id = app(CurrentBusiness::class)->id()) {
                $query->where($query->getModel()->getTable().'.business_id', $id);
            }
        });

        static::creating(function ($model) {
            $model->business_id ??= app(CurrentBusiness::class)->id();

            if (! $model->business_id) {
                throw new LogicException(class_basename($model).' cannot be saved without a business.');
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('business_id')) {
                throw new LogicException('A record cannot be moved to another business.');
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
