<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanNote extends Model
{
    protected $fillable = [
        'offering_id','lender_user_id','principal','interest_rate_bp','term_months','status'
    ];

    protected $casts = [
        'principal' => 'decimal:2',
    ];

    public function offering(){ return $this->belongsTo(Offering::class); }
    public function lender()  { return $this->belongsTo(User::class, 'lender_user_id'); }
}