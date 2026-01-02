<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquityPosition extends Model
{
    protected $fillable = [
        'offering_id','equity_class_id','holder_user_id','holder_type','pct_ownership','source','locked_until'
    ];

    protected $casts = [
        'pct_ownership' => 'decimal:4',
        'locked_until'  => 'datetime',
    ];

    public function offering()   { return $this->belongsTo(Offering::class); }
    public function equityClass(){ return $this->belongsTo(EquityClass::class); }
    public function holder()     { return $this->belongsTo(User::class, 'holder_user_id'); }
}