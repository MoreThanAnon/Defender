<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Role;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username', 
        'email', 
        'password', 
        'role', 
        'real_name', 
        'eircode', 
        'address1', 
        'address2', 
        'address3', 
        'city', 
        'county',
        'funds', 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function hasRole(string $role): bool
    {
        // Assuming the User model has a relationship with roles
        // Adjust the logic based on your database structure
        return $this->roles()->where('name', $role)->exists();
    }

    public function roles()
    {
        // Define the relationship between User and Role
        return $this->belongsToMany(Role::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function userHasSufficientFunds($amount)
    {
        // Assuming the User model has a 'balance' attribute
        return $this->balance >= $amount;
    }

    public function bids()
    {
        return $this->hasMany(Bids::class);
    }
    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }
}


