<?php

namespace BusinessCore\Models;

class ReportSignoff extends TenantModel
{
    public $timestamps = false;

    protected $table = 'business_report_signoffs';

    protected $casts = ['signed_at' => 'datetime'];

    public function signer()
    {
        return $this->userRelation('signed_by');
    }
}
