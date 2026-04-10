<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = ['name','email','password','role','avatar_path','is_active','last_login_at'];
    protected $hidden = ['password','remember_token'];
    protected $casts = ['email_verified_at'=>'datetime','password'=>'hashed','is_active'=>'boolean','last_login_at'=>'datetime'];
    public function isSuperAdmin(): bool { return $this->role==='superadmin'; }
    public function isAdmin(): bool { return in_array($this->role,['superadmin','admin']); }
    public function isModerator(): bool { return in_array($this->role,['superadmin','admin','moderator']); }
    public function moderatedEvents()
    {
        return $this->belongsToMany(\App\Models\Event::class, 'event_moderators')->withTimestamps();
    }
    public function getAvatarUrlAttribute(): string {
        return $this->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path) : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=FF3D00&color=fff&size=64';
    }
}
