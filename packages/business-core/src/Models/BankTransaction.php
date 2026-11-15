<?php

namespace BusinessCore\Models;

use LogicException;

/**
 * One line from an uploaded bank statement. What the bank said is frozen;
 * only the business's own annotations (category, project, match, note) change.
 */
class BankTransaction extends TenantModel
{
    public const BANK_FIELDS = ['statement_import_id', 'row_number', 'raw', 'fingerprint', 'booked_on', 'description', 'amount', 'balance_after'];

    public const STATUSES = ['unreviewed' => 'Unreviewed', 'categorised' => 'Categorised', 'matched' => 'Matched', 'flagged' => 'Flagged'];

    protected $table = 'business_bank_transactions';

    protected $casts = [
        'raw' => 'array',
        'booked_on' => 'date',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $tx) {
            if ($tx->isDirty(self::BANK_FIELDS)) {
                throw new LogicException('Imported bank lines are immutable.');
            }
        });
    }

    public function statementImport()
    {
        return $this->belongsTo(StatementImport::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function paymentTask()
    {
        return $this->belongsTo(PaymentTask::class);
    }

    /** Derive the review status from the annotations. */
    public function refreshStatus(bool $flagged = false): void
    {
        $this->status = match (true) {
            $flagged => 'flagged',
            $this->payment_task_id !== null => 'matched',
            filled($this->category) => 'categorised',
            default => 'unreviewed',
        };
    }
}
