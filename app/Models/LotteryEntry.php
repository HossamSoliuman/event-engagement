<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryEntry extends Model
{
    protected $fillable = ['event_id', 'name', 'phone', 'email', 'is_winner', 'won_at', 'entry_token','extra_fields'];
    protected $casts =
    [
        'is_winner' => 'boolean',
        'won_at' => 'datetime',
        'extra_fields' => 'array',
    ];
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
