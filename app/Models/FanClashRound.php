<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class FanClashRound extends Model
{
    protected $fillable = [
        'event_id',
        'fan_clash_matchup_id',
        'category',
        'side_a_name',
        'side_b_name',
        'side_a_color',
        'side_b_color',
        'side_a_image_path',
        'side_b_image_path',
        'sponsor_logo_path',
        'status',
        'duration_seconds',
        'side_a_taps',
        'side_b_taps',
        'winner_side',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'side_a_taps' => 'integer',
        'side_b_taps' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function matchup(): BelongsTo
    {
        return $this->belongsTo(FanClashMatchup::class, 'fan_clash_matchup_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(FanClashParticipant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function remainingMs(): int
    {
        if (! $this->started_at) {
            return $this->duration_seconds * 1000;
        }

        $endMs = $this->started_at->getTimestampMs() + ($this->duration_seconds * 1000);

        return max(0, $endMs - (int) (microtime(true) * 1000));
    }

    public function isExpired(): bool
    {
        return $this->started_at !== null && $this->remainingMs() <= 0;
    }

    /**
     * Winner computed purely from the two round counters.
     */
    public function getWinnerSide(): string
    {
        if ($this->side_a_taps > $this->side_b_taps) {
            return 'a';
        }

        if ($this->side_b_taps > $this->side_a_taps) {
            return 'b';
        }

        return 'tie';
    }

    /**
     * Lazily finalize an active round once its clock has run out. Returns true
     * if this call transitioned the round to finished.
     */
    public function finalizeIfExpired(): bool
    {
        if (! $this->isActive() || ! $this->isExpired()) {
            return false;
        }

        $this->update([
            'status' => 'finished',
            'winner_side' => $this->getWinnerSide(),
            'finished_at' => now(),
        ]);

        return true;
    }

    /**
     * @return array{a: int, b: int}
     */
    public function headcounts(): array
    {
        $counts = $this->participants()
            ->selectRaw('side, COUNT(*) as total')
            ->groupBy('side')
            ->pluck('total', 'side');

        return [
            'a' => (int) ($counts['a'] ?? 0),
            'b' => (int) ($counts['b'] ?? 0),
        ];
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
