<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';
    protected $fillable = ['event_id', 'user_id', 'action', 'subject_type', 'subject_id', 'meta', 'ip_address'];
    protected $casts = ['meta' => 'array'];
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public static function record(string $action, array $meta = [], ?int $eventId = null): void
    {
        static::create(['event_id' => $eventId ?? null, 'user_id' => auth()->id(), 'action' => $action, 'meta' => $meta, 'ip_address' => request()->ip()]);
    }
}
