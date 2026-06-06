<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanNote extends Model
{
    protected $fillable = [
        'offering_id','lender_user_id','investor_id','principal','principal_cents',
        'interest_rate_bp','term_months','repayment_frequency','maturity_date','status',
        'ranking','early_repayment_allowed','early_repayment_fee_bp','default_interest_rate_bp',
        'has_protective_votes','voting_scope'
    ];

    protected $casts = [
        'principal' => 'decimal:2',
        'principal_cents' => 'integer',
        'maturity_date' => 'date',
        'early_repayment_allowed' => 'boolean',
        'has_protective_votes' => 'boolean',
    ];

    public function offering(){ return $this->belongsTo(Offering::class); }
    public function lender()  { return $this->belongsTo(User::class, 'lender_user_id'); }
}
