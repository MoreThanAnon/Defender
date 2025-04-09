<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bid;
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
        'collective_fund',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array', // Cast images as an array
    ];

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
