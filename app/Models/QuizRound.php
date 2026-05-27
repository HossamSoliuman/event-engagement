<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class QuizRound extends Model
{
    protected $fillable = [
        'event_id',
        'status',
        'time_limit_seconds',
        'questions_per_round',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(QuizQuestion::class, 'quiz_round_questions')
            ->withPivot('sort_order')
            ->orderBy('quiz_round_questions.sort_order');
    }

    /**
     * The randomized subset of pool questions a single guest answers, in a
     * per-guest shuffled order. Seeded by the session token so the same guest
     * always receives the same questions for this round.
     */
    public function questionsForSession(string $sessionToken): Collection
    {
        $perGuest = $this->questions_per_round ?: 3;

        return $this->questions()->get()
            ->shuffle(crc32($sessionToken.'|'.$this->id))
            ->take($perGuest)
            ->values();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function getLeaderboard(): Collection
    {
        return $this->answers()
            ->selectRaw('session_token, guest_name, SUM(is_correct) as correct_count, SUM(time_taken_ms) as total_ms')
            ->groupBy('session_token', 'guest_name')
            ->orderByDesc('correct_count')
            ->orderBy('total_ms')
            ->get();
    }

    public function getWinner(): ?object
    {
        return $this->getLeaderboard()->first();
    }
}
