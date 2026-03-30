<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }
    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }
    public function isModerator(): bool
    {
        return in_array($this->role, ['superadmin', 'admin', 'moderator']);
    }
}
