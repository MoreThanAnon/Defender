<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecidimGovernanceSpace extends Model
{
    protected $fillable = ['offering_id', 'property_id', 'decidim_assembly_id', 'decidim_assembly_slug', 'decidim_assembly_url', 'decidim_debates_component_id', 'decidim_proposals_component_id', 'decidim_meetings_component_id', 'decidim_voting_component_id', 'decidim_taxonomy_id', 'status', 'error_message', 'metadata'];
    protected $casts = ['metadata' => 'array'];
    public function offering() { return $this->belongsTo(Offering::class); }
    public function property() { return $this->belongsTo(Property::class); }
}
