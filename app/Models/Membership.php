<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'team_preference',
        'newsletter_opt_in',
    ];
    protected $casts = ['newsletter_opt_in' => 'boolean'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
