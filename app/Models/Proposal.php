<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'title',
        'description',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
}
