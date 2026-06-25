<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecidimGovernanceSpace extends Model
{
    protected $fillable = ['offering_id', 'property_id', 'decidim_assembly_id', 'decidim_assembly_slug', 'decidim_assembly_url', 'decidim_debates_component_id', 'decidim_proposals_component_id', 'decidim_meetings_component_id', 'decidim_voting_component_id', 'decidim_taxonomy_id', 'status', 'retry_count', 'last_attempted_at', 'last_succeeded_at', 'last_failed_at', 'error_message', 'metadata'];
    protected $casts = ['metadata' => 'array', 'retry_count' => 'integer', 'last_attempted_at' => 'datetime', 'last_succeeded_at' => 'datetime', 'last_failed_at' => 'datetime'];
    public function offering() { return $this->belongsTo(Offering::class); }
    public function property() { return $this->belongsTo(Property::class); }
}
