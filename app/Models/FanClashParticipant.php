<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FanClashParticipant extends Model
{
    protected $fillable = [
        'fan_clash_round_id',
        'event_id',
        'session_token',
        'side',
        'taps',
    ];

    protected $casts = [
        'taps' => 'integer',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(FanClashRound::class, 'fan_clash_round_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
