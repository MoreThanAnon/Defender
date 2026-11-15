<?php

namespace BusinessCore\Models;

use LogicException;

/** Append-only history of who did what. */
class AuditEvent extends TenantModel
{
    public const UPDATED_AT = null;

    protected $table = 'business_audit_events';

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit events cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit events cannot be deleted.'));
    }

    public function user()
    {
        return $this->userRelation('user_id');
    }
}
