<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    protected $fillable = ['event_id', 'name', 'email', 'phone', 'team_preference', 'newsletter_opt_in', 'membership_number','extra_fields'];
    protected $casts = ['newsletter_opt_in' => 'boolean','extra_fields' => 'array'];
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
