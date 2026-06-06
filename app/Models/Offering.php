<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offering extends Model
{
    protected $fillable = [
        'property_id',
        'name',
        'status',
        'target_raise',
        'target_raise_cents',
        'hard_cap_cents',
        'overfunding_policy',
        'org_reserve_pct',
        'min_local_capital_pct',
        'min_local_investor_count',
        'require_local_threshold_for_settlement',
        'max_local_voting_equity_bp',
        'local_area_type',
        'local_area_config',
        'local_eligibility_locked_at',
        'failure_reason',
        'failure_notes',
        'resolved_at',
        'default_loan_interest_rate_bp',
        'default_loan_term_months',
        'default_loan_repayment_frequency',
        'default_loan_ranking',
        'org_reserved_matter_veto',
        'treasury_votes',
        'lender_ordinary_votes',
        'delegated_voting_enabled',
        'min_investment',
        'campaign_end',
        'price_per_percent',
    ];

    protected $casts = [
        'target_raise' => 'decimal:2',
        'target_raise_cents' => 'integer',
        'hard_cap_cents' => 'integer',
        'org_reserve_pct' => 'decimal:2',
        'min_local_capital_pct' => 'integer',
        'min_local_investor_count' => 'integer',
        'require_local_threshold_for_settlement' => 'boolean',
        'max_local_voting_equity_bp' => 'integer',
        'local_area_config' => 'array',
        'local_eligibility_locked_at' => 'datetime',
        'resolved_at' => 'datetime',
        'org_reserved_matter_veto' => 'boolean',
        'treasury_votes' => 'boolean',
        'lender_ordinary_votes' => 'boolean',
        'delegated_voting_enabled' => 'boolean',
        'min_investment' => 'decimal:2',
        'campaign_end' => 'datetime',
        'price_per_percent' => 'decimal:4',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function equityPositions()
    {
        return $this->hasMany(EquityPosition::class);
    }

    public function loanNotes()
    {
        return $this->hasMany(LoanNote::class);
    }

    public function refundRecords()
    {
        return $this->hasMany(RefundRecord::class);
    }
}
