<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Role;
use Laravel\Cashier\Billable; // ✅ Add this
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements MustVerifyEmail, JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    use Billable; // ✅ Add this to enable Cashier/Stripe features

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
        'pm_type',
        'pm_last_four',
        'balance'
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

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}


