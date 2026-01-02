<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offering extends Model
{
    protected $fillable = [
        'property_id','name','status','target_raise','org_reserve_pct','min_investment','price_per_percent'
    ];

    protected $casts = [
        'target_raise'      => 'decimal:2',
        'org_reserve_pct'   => 'decimal:2',
        'min_investment'    => 'decimal:2',
        'price_per_percent' => 'decimal:4',
    ];

    public function Properties()          { return $this->belongsTo(Property::class); }
    public function bids()           { return $this->hasMany(Bid::class); }
    public function equityPositions(){ return $this->hasMany(EquityPosition::class); }
    public function loanNotes()      { return $this->hasMany(LoanNote::class); }
}