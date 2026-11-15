<?php

namespace BusinessCore\Models;

class Supplier extends TenantModel
{
    protected $table = 'business_suppliers';

    public function paymentTasks()
    {
        return $this->hasMany(PaymentTask::class);
    }
}
