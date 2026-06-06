<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'offering_id',
        'user_id',
        'amount',
        'original_amount_cents',
        'accepted_amount_cents',
        'refunded_amount_cents',
        'is_local',
        'is_local_at_bid_time',
        'local_eligibility_reason',
        'local_area_version',
        'status',
        'allocation_status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'original_amount_cents' => 'integer',
        'accepted_amount_cents' => 'integer',
        'refunded_amount_cents' => 'integer',
        'is_local' => 'boolean',
        'is_local_at_bid_time' => 'boolean',
        'local_eligibility_reason' => 'array',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function refundRecord()
    {
        return $this->hasOne(RefundRecord::class);
    }
}
