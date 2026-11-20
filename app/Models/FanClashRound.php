<?php

namespace App\Models;

use App\Traits\ReferencesMediaFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FanClashRound extends Model
{
    use ReferencesMediaFiles;

    /** @var list<string> */
    public const MEDIA_COLUMNS = ['side_a_image_path', 'side_b_image_path', 'sponsor_logo_path'];

    /**
     * How long after the clock runs out the round still takes taps that
     * were made in time and are on their way, before it closes.
     */
    public const LATE_TAP_GRACE_MS = 1500;

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
        $endsAt = $this->endsAtMs();

        return $endsAt === null ? $this->duration_seconds * 1000 : max(0, $endsAt - self::nowMs());
    }

    /**
     * When the clock runs out, in server milliseconds.
     */
    public function endsAtMs(): ?int
    {
        return $this->started_at ? $this->started_at->getTimestampMs() + $this->duration_seconds * 1000 : null;
    }

    /**
     * The round closes a moment after its clock runs out, so the taps made
     * just before the end that are still on their way from the phones count.
     */
    public function isExpired(): bool
    {
        $endsAt = $this->endsAtMs();

        return $endsAt !== null && self::nowMs() >= $endsAt + self::LATE_TAP_GRACE_MS;
    }

    /**
     * The round's moments in server milliseconds with the server's time
     * now, so every screen counts against the same clock however long the
     * answer took to reach it.
     *
     * @return array{server_now_ms: int, starts_at_ms: int|null, ends_at_ms: int|null, closes_at_ms: int|null}
     */
    public function clock(): array
    {
        $endsAt = $this->isActive() ? $this->endsAtMs() : null;

        return [
            'server_now_ms' => self::nowMs(),
            'starts_at_ms' => $endsAt === null ? null : $this->started_at->getTimestampMs(),
            'ends_at_ms' => $endsAt,
            'closes_at_ms' => $endsAt === null ? null : $endsAt + self::LATE_TAP_GRACE_MS,
        ];
    }

    public static function nowMs(): int
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * Milliseconds until the taps start counting, while the room is counted in.
     */
    public function startsInMs(): int
    {
        if (! $this->isActive() || ! $this->started_at) {
            return 0;
        }

        return max(0, $this->started_at->getTimestampMs() - self::nowMs());
    }

    public function hasStarted(): bool
    {
        return $this->started_at !== null && ! $this->started_at->isFuture();
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

        $this->finish();

        return true;
    }

    /**
     * Closes the round in one statement that also picks the winner, so the
     * result always matches the counters even while taps are arriving.
     * False when another request closed it first.
     */
    public function finish(): bool
    {
        $closed = static::query()
            ->whereKey($this->getKey())
            ->where('status', 'active')
            ->update([
                'status' => 'finished',
                'winner_side' => DB::raw("CASE WHEN side_a_taps > side_b_taps THEN 'a' WHEN side_b_taps > side_a_taps THEN 'b' ELSE 'tie' END"),
                'finished_at' => now(),
            ]) > 0;

        $this->refresh();

        return $closed;
    }

    /**
     * Counts taps for one side only while the round is still open, so a tap
     * that races the end of the round can never change a decided result.
     */
    public function addTaps(string $side, int $taps): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->where('status', 'active')
            ->increment($side === 'a' ? 'side_a_taps' : 'side_b_taps', $taps) > 0;
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
        return $this->side_a_image_path ? Storage::disk('media')->url($this->side_a_image_path) : null;
    }

    public function getSideBImageUrlAttribute(): ?string
    {
        return $this->side_b_image_path ? Storage::disk('media')->url($this->side_b_image_path) : null;
    }

    public function getSponsorLogoUrlAttribute(): ?string
    {
        return $this->sponsor_logo_path ? Storage::disk('media')->url($this->sponsor_logo_path) : null;
    }
}
