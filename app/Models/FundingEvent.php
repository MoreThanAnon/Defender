<?php

namespace App\Models;

use App\Models\Offering;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FundingEvent extends Model
{
    protected $fillable = [
        'offering_id',
        'user_id',
        'event_type',
        'amount',
        'equity_pct',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'equity_pct' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
