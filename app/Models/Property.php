<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'beds',
        'baths',
        'size',
        'type',
        'ber_rating',
        'address',
        'description',
        'sale_type',
        'stamp_duty',
        'features',
        'auctioneer_name',
        'latitude',
        'longitude',
        'images',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
    ];
}
