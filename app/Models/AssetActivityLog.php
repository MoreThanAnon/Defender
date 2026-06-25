<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetActivityLog extends Model
{
    protected $fillable = [
        'offering_id',
        'property_id',
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'summary',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
