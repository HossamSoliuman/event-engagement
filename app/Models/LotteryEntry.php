<?php // app/Models/LotteryEntry.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotteryEntry extends Model
{
    protected $fillable = ['event_id', 'name', 'phone', 'email', 'is_winner'];
    protected $casts    = ['is_winner' => 'boolean'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
