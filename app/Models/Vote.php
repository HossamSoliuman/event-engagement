<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    protected $fillable = ['event_id', 'candidate_name', 'voter_session', 'voter_ip'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
