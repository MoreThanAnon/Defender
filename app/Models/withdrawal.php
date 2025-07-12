<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class withdrawal extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'gross_amount',
        'site_fee',
        'net_amount',
        'created_at',
    ];

    protected $table = 'withdrawal';
    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
