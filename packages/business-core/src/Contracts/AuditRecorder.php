<?php

namespace BusinessCore\Contracts;

use BusinessCore\Models\Business;
use Illuminate\Database\Eloquent\Model;

interface AuditRecorder
{
    /** Record who did what. Uses the current business and signed-in user unless told otherwise. */
    public function record(string $action, ?Model $subject = null, array $meta = [], ?Business $business = null): void;
}
