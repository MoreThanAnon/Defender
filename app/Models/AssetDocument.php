<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDocument extends Model
{
    protected $fillable = [
        'offering_id',
        'property_id',
        'uploaded_by',
        'document_key',
        'title',
        'notes',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'review_status',
        'visible_to_investors',
        'published_at',
        'publication_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'visible_to_investors' => 'boolean',
        'published_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function scopeInvestorVisible($query)
    {
        return $query
            ->where('visible_to_investors', true)
            ->where('review_status', 'reviewed')
            ->whereNotNull('published_at');
    }

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
