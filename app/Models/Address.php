<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'address';

    protected $fillable = [
        'user_id',
        'eircode',
        'address1',
        'address2',
        'address3',
        'city',
        'county',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
