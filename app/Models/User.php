<?php

namespace App\Models;

use App\Traits\ReferencesMediaFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, ReferencesMediaFiles;

    /** @var list<string> */
    public const MEDIA_COLUMNS = ['avatar_path'];

    protected $fillable = ['name', 'email', 'password', 'role', 'avatar_path', 'is_active', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean', 'last_login_at' => 'datetime'];

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

    public function moderatedEvents()
    {
        return $this->belongsToMany(Event::class, 'event_moderators')->withTimestamps();
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar_path ? Storage::disk('media')->url($this->avatar_path) : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=FF3D00&color=fff&size=64';
    }
}
