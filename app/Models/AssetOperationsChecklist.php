<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetOperationsChecklist extends Model
{
    protected $table = 'asset_operations_checklists';

    protected $fillable = [
        'offering_id',
        'property_id',
        'bank_balance',
        'bills_due',
        'maintenance_tasks',
        'meeting_status',
        'proposal_status',
        'legal_review',
        'admin_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'bank_balance' => 'decimal:2',
        'bills_due' => 'array',
        'maintenance_tasks' => 'array',
        'meeting_status' => 'array',
        'proposal_status' => 'array',
        'legal_review' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
