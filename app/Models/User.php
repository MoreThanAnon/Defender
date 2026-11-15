<?php

namespace App\Models;

use BusinessCore\Concerns\HasBusinesses;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasBusinesses, HasFactory, Notifiable;

    /** Platform-level roles. What someone may do inside a business lives on their membership, not here. */
    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';

    // "role" is deliberately not fillable: it can only be set in code, never from a form.
    protected $fillable = ['username', 'real_name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /** The platform operator: sees every business read-only and manages storage / go-live. */
    public function isPlatformAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
