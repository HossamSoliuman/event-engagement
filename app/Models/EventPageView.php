<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPageView extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'visitor_id',
        'page_type',
        'device_type',
        'os',
        'browser',
        'referrer',
        'is_first_visit',
    ];

    protected $casts = [
        'is_first_visit' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
