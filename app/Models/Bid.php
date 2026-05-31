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
        'is_local',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_local' => 'boolean',
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
}
