<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquityClass extends Model
{
    protected $fillable = ['property_id','code','name','voting_rights','transfer_restrictions'];
    protected $casts = ['voting_rights'=>'array','transfer_restrictions'=>'array'];

    public function Property() { return $this->belongsTo(Property::class); }
}