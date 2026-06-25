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
        'reviewed_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
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

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
