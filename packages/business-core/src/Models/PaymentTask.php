<?php

namespace BusinessCore\Models;

/**
 * A prepared payment instruction. The software never sends money: someone pays
 * in the business's own bank, and the task is only settled when a line on an
 * imported bank statement is matched to it.
 */
class PaymentTask extends TenantModel
{
    public const STATUSES = [
        'prepared' => 'Prepared',
        'sent' => 'Paid in bank — awaiting statement',
        'reconciled' => 'Confirmed on statement',
        'cancelled' => 'Cancelled',
    ];

    protected $table = 'business_payment_tasks';

    protected $casts = ['amount' => 'decimal:2', 'due_date' => 'date', 'sent_at' => 'datetime', 'reconciled_at' => 'datetime'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transaction()
    {
        return $this->hasOne(BankTransaction::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['prepared', 'sent'], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
