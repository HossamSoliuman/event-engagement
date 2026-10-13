<?php

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * The lottery's turn on the vidiwall: how a draw is presented (countdown,
 * drawing and result timings plus what the screen shows) and the show that is
 * on screen right now. A show is timed from `show_starts_ms`, so the screen and
 * every control page work out the same phase from the clock, and a screen that
 * joins late picks the show up where it is. Every change bumps `revision`.
 */
class LotteryScreen extends Model
{
    use HasFactory;

    public const SHOW_DRAW = 'draw';

    public const SHOW_WINNER = 'winner';

    public const SHOW_BOARD = 'board';

    public const PHASE_INTRO = 'intro';

    public const PHASE_COUNTDOWN = 'countdown';

    public const PHASE_DRAWING = 'drawing';

    public const PHASE_RESULT = 'result';

    public const NAME_FULL = 'full';

    public const NAME_SHORT = 'short';

    /**
     * Lead-in before a draw's countdown, so a screen polling once a second has
     * the show before its first number is due.
     */
    public const INTRO_MS = 1500;

    /**
     * How many other entrants' names spin past in the drawing reel.
     */
    public const REEL_SIZE = 40;

    protected $fillable = [
        'event_id',
        'draw_on_screen',
        'countdown_seconds',
        'drawing_seconds',
        'result_seconds',
        'headline',
        'name_format',
        'show_prize',
        'show_phone',
        'show_entry_count',
        'confetti',
    ];

    protected $casts = [
        'draw_on_screen' => 'boolean',
        'countdown_seconds' => 'integer',
        'drawing_seconds' => 'integer',
        'result_seconds' => 'integer',
        'show_prize' => 'boolean',
        'show_phone' => 'boolean',
        'show_entry_count' => 'boolean',
        'confetti' => 'boolean',
        'lottery_entry_id' => 'integer',
        'show_starts_ms' => 'integer',
        'show_countdown_ms' => 'integer',
        'show_drawing_ms' => 'integer',
        'show_result_ms' => 'integer',
        'show_reel' => 'array',
        'resume_player' => 'boolean',
        'revision' => 'integer',
    ];

    protected $attributes = [
        'draw_on_screen' => false,
        'countdown_seconds' => 5,
        'drawing_seconds' => 6,
        'result_seconds' => 30,
        'name_format' => self::NAME_FULL,
        'show_prize' => true,
        'show_phone' => false,
        'show_entry_count' => true,
        'confetti' => true,
        'show_countdown_ms' => 0,
        'show_drawing_ms' => 0,
        'show_result_ms' => 0,
        'resume_player' => false,
        'revision' => 0,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(LotteryEntry::class, 'lottery_entry_id');
    }

    public function headlineText(): string
    {
        return $this->headline ?: ($this->event->lottery_title ?: 'Lucky Draw');
    }

    /**
     * Milliseconds since the countdown started; negative during the intro.
     */
    public function elapsedMs(): int
    {
        return $this->show_starts_ms ? now()->getTimestampMs() - $this->show_starts_ms : 0;
    }

    /**
     * Where the show on screen is, or null when nothing is on screen or the
     * result has had its time.
     */
    public function phase(): ?string
    {
        if (! $this->show_type || ! $this->show_starts_ms) {
            return null;
        }

        $elapsed = $this->elapsedMs();
        $revealAt = $this->show_countdown_ms + $this->show_drawing_ms;

        return match (true) {
            $elapsed < 0 => self::PHASE_INTRO,
            $elapsed < $this->show_countdown_ms => self::PHASE_COUNTDOWN,
            $elapsed < $revealAt => self::PHASE_DRAWING,
            $this->show_result_ms === 0, $elapsed < $revealAt + $this->show_result_ms => self::PHASE_RESULT,
            default => null,
        };
    }

    public function isShowing(): bool
    {
        return $this->phase() !== null;
    }

    /**
     * True while a draw is still building up to its reveal. Nothing else may
     * take the screen until it has revealed, so the room never misses a winner.
     */
    public function isRevealPending(): bool
    {
        return $this->show_type === self::SHOW_DRAW
            && in_array($this->phase(), [self::PHASE_INTRO, self::PHASE_COUNTDOWN, self::PHASE_DRAWING], true);
    }

    /**
     * Runs the full draw on screen for a winner that has just been drawn: intro,
     * countdown, the reel of names landing on the winner, then the result.
     */
    public function startDraw(LotteryEntry $winner): void
    {
        $reel = $this->reelFor($winner);

        $this->begin(self::SHOW_DRAW, $winner, fn (self $screen) => [
            'show_starts_ms' => now()->getTimestampMs() + self::INTRO_MS,
            'show_countdown_ms' => $screen->countdown_seconds * 1000,
            'show_drawing_ms' => $screen->drawing_seconds * 1000,
            'show_reel' => $reel,
        ]);
    }

    /**
     * Puts an earlier winner straight back up on screen, without a draw.
     */
    public function showWinner(LotteryEntry $winner): void
    {
        $this->begin(self::SHOW_WINNER, $winner);
    }

    /**
     * Shows every winner so far; it stays up until it is hidden.
     */
    public function showBoard(): void
    {
        $this->begin(self::SHOW_BOARD, null, fn () => ['show_result_ms' => 0]);
    }

    /**
     * Skips the rest of the countdown and drawing and goes to the result.
     */
    public function revealNow(): void
    {
        $this->mutate(function (self $screen): void {
            if ($screen->isRevealPending()) {
                $screen->show_starts_ms = now()->getTimestampMs() - $screen->show_countdown_ms - $screen->show_drawing_ms;
            }
        });
    }

    /**
     * Takes the lottery off the screen and lets the playlist carry on if the
     * show paused it.
     */
    public function hide(): void
    {
        $this->mutate(fn (self $screen) => $screen->clearShow());
    }

    /**
     * Ends a show whose result has had its time, or whose winner was removed.
     */
    public function settle(): void
    {
        if (! $this->show_type || ! $this->showIsOver()) {
            return;
        }

        $this->mutate(function (self $screen): void {
            if ($screen->show_type && $screen->showIsOver()) {
                $screen->clearShow();
            }
        });
    }

    /**
     * What the vidiwall needs to run the show, timed from the moment of the
     * request.
     *
     * @return array{revision: int, show: array<string, mixed>|null}
     */
    public function screenFeed(): array
    {
        $this->settle();

        if (! $this->show_type) {
            return ['revision' => $this->revision, 'show' => null];
        }

        $isDraw = $this->show_type === self::SHOW_DRAW;

        return [
            'revision' => $this->revision,
            'show' => [
                'type' => $this->show_type,
                'phase' => $this->phase(),
                'elapsed_ms' => $this->elapsedMs(),
                'countdown_ms' => $this->show_countdown_ms,
                'drawing_ms' => $this->show_drawing_ms,
                'result_ms' => $this->show_result_ms,
                'headline' => $this->headlineText(),
                'confetti' => $this->confetti,
                'winner' => $this->entry ? $this->presentWinner($this->entry) : null,
                'reel' => $isDraw ? ($this->show_reel ?? []) : [],
                'entries' => $isDraw && $this->show_entry_count ? $this->event->lotteryEntries()->count() : null,
                'winners' => $this->show_type === self::SHOW_BOARD
                    ? $this->event->lotteryWinners()->get()->map(fn (LotteryEntry $winner) => $this->presentWinner($winner))->all()
                    : [],
            ],
        ];
    }

    /**
     * The show as the control pages mirror it.
     *
     * @return array{revision: int, show: array<string, mixed>|null}
     */
    public function controlStatus(): array
    {
        $this->settle();

        return [
            'revision' => $this->revision,
            'show' => $this->show_type ? [
                'type' => $this->show_type,
                'phase' => $this->phase(),
                'elapsed_ms' => $this->elapsedMs(),
                'countdown_ms' => $this->show_countdown_ms,
                'drawing_ms' => $this->show_drawing_ms,
                'result_ms' => $this->show_result_ms,
                'entry_id' => $this->lottery_entry_id,
                'name' => $this->entry?->name,
                'rank' => $this->entry?->winner_rank,
                'prize' => $this->entry?->prize,
            ] : null,
        ];
    }

    /**
     * @return array{rank: int|null, name: string, prize: string|null, detail: string|null}
     */
    public function presentWinner(LotteryEntry $winner): array
    {
        return [
            'rank' => $winner->winner_rank,
            'name' => $winner->screenName($this->name_format),
            'prize' => $this->show_prize ? $winner->prize : null,
            'detail' => $this->show_phone ? $winner->maskedPhone() : null,
        ];
    }

    /**
     * @param  (Closure(self): array<string, mixed>)|null  $timing
     */
    protected function begin(string $type, ?LotteryEntry $winner, ?Closure $timing = null): void
    {
        $this->mutate(function (self $screen) use ($type, $winner, $timing): void {
            $resumePlayer = $screen->pausePlayer() || ($screen->show_type && $screen->resume_player);

            $screen->forceFill([
                'show_type' => $type,
                'lottery_entry_id' => $winner?->id,
                'show_starts_ms' => now()->getTimestampMs(),
                'show_countdown_ms' => 0,
                'show_drawing_ms' => 0,
                'show_result_ms' => $screen->result_seconds * 1000,
                'show_reel' => null,
                'resume_player' => $resumePlayer,
                ...($timing ? $timing($screen) : []),
            ]);
        });
    }

    protected function showIsOver(): bool
    {
        if ($this->phase() === null) {
            return true;
        }

        return $this->show_type !== self::SHOW_BOARD && ! $this->entry()->where('is_winner', true)->exists();
    }

    protected function clearShow(): void
    {
        if (! $this->show_type) {
            return;
        }

        if ($this->resume_player) {
            $player = $this->event->vidiwallPlayer;

            if ($player?->isPaused()) {
                $player->setRelation('event', $this->event)->play();
            }
        }

        $this->forceFill([
            'show_type' => null,
            'lottery_entry_id' => null,
            'show_starts_ms' => null,
            'show_countdown_ms' => 0,
            'show_drawing_ms' => 0,
            'show_result_ms' => 0,
            'show_reel' => null,
            'resume_player' => false,
        ]);
    }

    /**
     * Pauses the playlist while the lottery has the screen, so no photo plays
     * unseen underneath it. True when it was playing.
     */
    protected function pausePlayer(): bool
    {
        $player = $this->event->vidiwallPlayer;

        if (! $player?->isPlaying()) {
            return false;
        }

        $player->setRelation('event', $this->event)->pause();

        return true;
    }

    /**
     * Names that spin past before the reel lands on the winner, in the format
     * the screen shows names in.
     *
     * @return list<string>
     */
    protected function reelFor(LotteryEntry $winner): array
    {
        return $this->event->lotteryEntries()
            ->whereKeyNot($winner->id)
            ->inRandomOrder()
            ->limit(self::REEL_SIZE)
            ->get(['id', 'name'])
            ->map(fn (LotteryEntry $entry) => $entry->screenName($this->name_format))
            ->values()
            ->all();
    }

    /**
     * Applies a change to the freshest copy under a row lock, so a screen ending
     * a finished show cannot wipe out the one an operator has just started. The
     * lock is claimed with a write first, as SQLite has no row locks.
     *
     * @template TReturn
     *
     * @param  Closure(self): TReturn  $change
     * @return TReturn
     */
    protected function mutate(Closure $change): mixed
    {
        $event = $this->event;

        [$fresh, $result] = DB::transaction(function () use ($change, $event): array {
            static::query()->whereKey($this->id)->toBase()->update(['revision' => DB::raw('revision')]);
            $fresh = static::query()->lockForUpdate()->findOrFail($this->id)->setRelation('event', $event);
            $result = $change($fresh);

            if ($fresh->isDirty()) {
                $fresh->revision = (int) $fresh->getOriginal('revision') + 1;
                $fresh->save();
            }

            return [$fresh, $result];
        });

        $this->setRawAttributes($fresh->getAttributes(), true);
        $this->unsetRelation('entry');

        return $result;
    }
}
