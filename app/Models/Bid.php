<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    protected $fillable = ['offering_id','user_id','amount','is_local','status'];
    protected $casts = ['amount' => 'decimal:2','is_local' => 'boolean'];

    public function offering(){ return $this->belongsTo(Offering::class); }
    public function user()    { return $this->belongsTo(User::class); }
}