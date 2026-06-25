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
        'eircode',
        'daft_link',
        'status',
        'campaign_start',
        'campaign_end',
        'internal_days',
        'cover_image',
    ];

    protected $casts = [
        'features' => 'array',
        'campaign_start' => 'date',
        'campaign_end' => 'date',
    ];

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function getCollectiveFunds()
    {
        return $this->bids()->sum('amount');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class, 'property_id');
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function updates()
    {
        return $this->hasMany(Update::class);
    }

    public function offerings()
    {
        return $this->hasMany(Offering::class);
    }

    public function assetOperationsChecklists()
    {
        return $this->hasMany(AssetOperationsChecklist::class);
    }

    public function hotelOperationsChecklists()
    {
        return $this->assetOperationsChecklists();
    }
}
