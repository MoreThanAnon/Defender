<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRecord extends Model
{
    protected $fillable = [
        'offering_id',
        'bid_id',
        'user_id',
        'amount_cents',
        'status',
        'metadata',
        'completed_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    public function offering() { return $this->belongsTo(Offering::class); }
    public function bid() { return $this->belongsTo(Bid::class); }
    public function user() { return $this->belongsTo(User::class); }
}
