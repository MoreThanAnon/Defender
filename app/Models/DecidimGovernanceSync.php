<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecidimGovernanceSync extends Model
{
    protected $fillable = [
        'offering_id',
        'investor_id',
        'decidim_user_id',
        'role',
        'can_vote',
        'voting_bp',
        'voting_scope',
        'synced_at',
        'payload',
    ];

    protected $casts = [
        'can_vote' => 'boolean',
        'voting_bp' => 'integer',
        'synced_at' => 'datetime',
        'payload' => 'array',
    ];
}
