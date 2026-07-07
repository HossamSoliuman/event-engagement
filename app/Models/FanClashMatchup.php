<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class FanClashMatchup extends Model
{
    protected $fillable = [
        'event_id',
        'category',
        'side_a_name',
        'side_b_name',
        'side_a_color',
        'side_b_color',
        'side_a_image_path',
        'side_b_image_path',
        'sponsor_logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(FanClashRound::class);
    }

    public function getSideAImageUrlAttribute(): ?string
    {
        return $this->side_a_image_path ? Storage::disk('public')->url($this->side_a_image_path) : null;
    }

    public function getSideBImageUrlAttribute(): ?string
    {
        return $this->side_b_image_path ? Storage::disk('public')->url($this->side_b_image_path) : null;
    }

    public function getSponsorLogoUrlAttribute(): ?string
    {
        return $this->sponsor_logo_path ? Storage::disk('public')->url($this->sponsor_logo_path) : null;
    }
}
