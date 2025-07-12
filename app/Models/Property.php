<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Investment;
use App\Models\Proposal;
class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'price', 'beds', 'baths', 'size', 'type', 'ber_rating',
        'address', 'description', 'sale_type', 'stamp_duty', 'features',
        'auctioneer_name', 'latitude', 'longitude', 'eircode',
    ];

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function SetPropertyActive($campaignDuration = 30,$internal_days = 3)
    {
        // Logic to set the property as active for bidding
        $this->status = 1; // Assuming 1 means active
        $this->campaign_start = now();
        $this->campaign_end = now()->addDays($campaignDuration);
        $this->internal_days = 3;
        $this->save();
    }
    //Functions needed for the new timing system
    //SetPropertyActive -- Allow the property to be bid on
    //SetPropertyInactive -- Stop the property from being bid on (freezes timer)
    //SetInternalDays() -- Set the number of days the property is available for bidding byfor internal bidders
    //SetTimerTotalDays() -- Set the total number of days the property is available for bidding in total.

    public function getCollectiveFunds()
    {
        return $this->bids()->sum('amount');
    }

    public function bids()
    {
        return $this->hasMany(\App\Models\Bids::class, 'property_id');
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }
}