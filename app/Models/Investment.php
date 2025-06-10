<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'property_id',
        'amount',
        'type',          // 'equity' or 'loan'
        'status',        // 'active', 'paid', 'inactive'
        'interest_rate', // only for loans
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Accessors
     */
    public function isEquity()
    {
        return $this->type === 'equity';
    }

    public function isLoan()
    {
        return $this->type === 'loan';
    }

    public function isActiveLoan()
    {
        return $this->isLoan() && $this->status === 'active';
    }

    /**
     * Scope examples for admin reporting
     */
    public function scopeEquity($query)
    {
        return $query->where('type', 'equity');
    }

    public function scopeLoan($query)
    {
        return $query->where('type', 'loan');
    }

    public function scopeActiveLoan($query)
    {
        return $query->where('type', 'loan')->where('status', 'active');
    }

    public function scopePaidLoan($query)
    {
        return $query->where('type', 'loan')->where('status', 'paid');
    }
}
