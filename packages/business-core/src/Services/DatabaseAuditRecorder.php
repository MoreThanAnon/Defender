<?php

namespace BusinessCore\Services;

use BusinessCore\Contracts\AuditRecorder;
use BusinessCore\Models\AuditEvent;
use BusinessCore\Models\Business;
use BusinessCore\Support\CurrentBusiness;
use Illuminate\Database\Eloquent\Model;

class DatabaseAuditRecorder implements AuditRecorder
{
    public function __construct(private CurrentBusiness $current)
    {
    }

    public function record(string $action, ?Model $subject = null, array $meta = [], ?Business $business = null): void
    {
        $businessId = $business?->getKey() ?? $this->current->id();
        if (! $businessId) {
            return;
        }

        $request = app()->bound('request') ? request() : null;

        AuditEvent::create([
            'business_id' => $businessId,
            'user_id' => $request?->user()?->getKey(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip' => $request?->ip(),
        ]);
    }
}
