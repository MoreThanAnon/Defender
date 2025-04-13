<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'gross_amount',
        'site_fee',
        'net_amount',
    ];

    /**
     * Relationship: A deposit belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}