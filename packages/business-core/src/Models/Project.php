<?php

namespace BusinessCore\Models;

/** A job or cost centre with an optional budget. */
class Project extends TenantModel
{
    public const STATUSES = ['planned' => 'Planned', 'active' => 'Active', 'on_hold' => 'On hold', 'done' => 'Done'];

    protected $table = 'business_projects';

    protected $casts = ['budget_amount' => 'decimal:2', 'due_date' => 'date'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function paymentTasks()
    {
        return $this->hasMany(PaymentTask::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    /** Money out that the bank statement confirms for this project (positive number). */
    public function paid(): float
    {
        return -1 * (float) $this->transactions()->where('amount', '<', 0)->sum('amount');
    }

    /** Payments prepared or sent but not yet seen on a bank statement. */
    public function committed(): float
    {
        return (float) $this->paymentTasks()->whereIn('status', ['prepared', 'sent'])->sum('amount');
    }
}
