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
        'eircode',
        'address',
        'description',
        'latitude',
        'longitude',
        'sale_type',
        'stamp_duty',
        'features',
        'auctioneer_name',
        'collective_fund',
        'fund_goal',
        'daft_link',
    ];

    public function images()
    {
        return $this->hasMany(Image::class);
    }
}