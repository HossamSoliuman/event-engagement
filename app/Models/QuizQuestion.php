<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class QuizQuestion extends Model
{
    protected $fillable = [
        'event_id',
        'question_text',
        'options',
        'correct_option',
        'sponsor_logo_path',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'correct_option' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function getSponsorLogoUrlAttribute(): ?string
    {
        return $this->sponsor_logo_path ? Storage::disk('public')->url($this->sponsor_logo_path) : null;
    }
}
