<?php

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * The vote's turn on the vidiwall: how the results are presented and the show
 * that is on screen right now. Live results and the podium follow the votes as
 * they come in. A winner reveal is timed from `show_starts_ms` (intro,
 * countdown, then the winner) and keeps the standings from the moment it
 * started, so a late vote cannot change the winner halfway through. Every
 * change bumps `revision`.
 */
class VotingScreen extends Model
{
    use HasFactory;

    public const SHOW_LIVE = 'live';

    public const SHOW_REVEAL = 'reveal';

    public const SHOW_PODIUM = 'podium';

    public const PHASE_INTRO = 'intro';

    public const PHASE_COUNTDOWN = 'countdown';

    public const PHASE_RESULT = 'result';

    /**
     * Lead-in before a reveal's countdown, so a screen polling once a second
     * has the show before its first number is due.
     */
    public const INTRO_MS = 1500;

    /**
     * The places on the podium.
     */
    public const PODIUM_PLACES = 3;

    protected $fillable = [
        'event_id',
        'headline',
        'top_count',
        'show_votes',
        'show_percent',
        'show_total',
        'show_position',
        'show_qr',
        'countdown_seconds',
        'result_seconds',
        'confetti',
        'close_on_reveal',
    ];

    protected $casts = [
        'top_count' => 'integer',
        'show_votes' => 'boolean',
        'show_percent' => 'boolean',
        'show_total' => 'boolean',
        'show_position' => 'boolean',
        'show_qr' => 'boolean',
        'countdown_seconds' => 'integer',
        'result_seconds' => 'integer',
        'confetti' => 'boolean',
        'close_on_reveal' => 'boolean',
        'show_starts_ms' => 'integer',
        'show_countdown_ms' => 'integer',
        'show_result_ms' => 'integer',
        'show_standings' => 'array',
        'resume_player' => 'boolean',
        'revision' => 'integer',
    ];

    protected $attributes = [
        'top_count' => 0,
        'show_votes' => true,
        'show_percent' => true,
        'show_total' => true,
        'show_position' => true,
        'show_qr' => true,
        'countdown_seconds' => 5,
        'result_seconds' => 0,
        'confetti' => true,
        'close_on_reveal' => true,
        'show_countdown_ms' => 0,
        'show_result_ms' => 0,
        'resume_player' => false,
        'revision' => 0,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function headlineText(): string
    {
        return $this->headline ?: ($this->event->voting_title ?: 'Fan Vote');
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
     * winner has had their time.
     */
    public function phase(): ?string
    {
        if (! $this->show_type || ! $this->show_starts_ms) {
            return null;
        }

        $elapsed = $this->elapsedMs();

        return match (true) {
            $elapsed < 0 => self::PHASE_INTRO,
            $elapsed < $this->show_countdown_ms => self::PHASE_COUNTDOWN,
            $this->show_result_ms === 0, $elapsed < $this->show_countdown_ms + $this->show_result_ms => self::PHASE_RESULT,
            default => null,
        };
    }

    public function isShowing(): bool
    {
        return $this->phase() !== null;
    }

    /**
     * True while a reveal is still counting down. Nothing else may take the
     * screen until the winner is out, so the room never misses them.
     */
    public function isRevealPending(): bool
    {
        return $this->show_type === self::SHOW_REVEAL
            && in_array($this->phase(), [self::PHASE_INTRO, self::PHASE_COUNTDOWN], true);
    }

    /**
     * Puts the standings up; they move with every vote until hidden.
     */
    public function showLive(): void
    {
        $this->begin(self::SHOW_LIVE);
    }

    /**
     * Counts down to the winner of the standings as they are now.
     *
     * @param  list<array{key: string, name: string, position: string|null, votes: int, percent: int, rank: int}>  $standings
     */
    public function startReveal(array $standings): void
    {
        $this->begin(self::SHOW_REVEAL, fn (self $screen) => [
            'show_starts_ms' => now()->getTimestampMs() + self::INTRO_MS,
            'show_countdown_ms' => $screen->countdown_seconds * 1000,
            'show_result_ms' => $screen->result_seconds * 1000,
            'show_standings' => $standings,
        ]);
    }

    /**
     * Puts the top three on a podium; it stays up until it is hidden.
     */
    public function showPodium(): void
    {
        $this->begin(self::SHOW_PODIUM);
    }

    /**
     * Skips the rest of the countdown and goes to the winner.
     */
    public function revealNow(): void
    {
        $this->mutate(function (self $screen): void {
            if ($screen->isRevealPending()) {
                $screen->show_starts_ms = now()->getTimestampMs() - $screen->show_countdown_ms;
            }
        });
    }

    /**
     * Takes the vote off the screen and lets the playlist carry on if the show
     * paused it.
     */
    public function hide(): void
    {
        $this->mutate(fn (self $screen) => $screen->clearShow());
    }

    /**
     * Clears the show for another one taking the screen, leaving the playlist
     * paused for it. True when this show was holding the playlist paused.
     */
    public function yieldScreen(): bool
    {
        return $this->mutate(function (self $screen): bool {
            $heldPlayer = $screen->show_type !== null && $screen->resume_player;
            $screen->clearShow(resumePlayer: false);

            return $heldPlayer;
        });
    }

    /**
     * Ends a reveal whose winner has had their time.
     */
    public function settle(): void
    {
        if (! $this->show_type || $this->isShowing()) {
            return;
        }

        $this->mutate(function (self $screen): void {
            if ($screen->show_type && ! $screen->isShowing()) {
                $screen->clearShow();
            }
        });
    }

    /**
     * The standings the show counts: frozen when a reveal started, live for
     * everything else.
     *
     * @return list<array{key: string, name: string, position: string|null, votes: int, percent: int, rank: int}>
     */
    public function standings(): array
    {
        return $this->show_type === self::SHOW_REVEAL
            ? ($this->show_standings ?? [])
            : $this->event->voteStandings();
    }

    /**
     * Everyone sharing first place, once anyone has a vote.
     *
     * @param  list<array{key: string, name: string, position: string|null, votes: int, percent: int, rank: int}>  $standings
     * @return list<array{key: string, name: string, position: string|null, votes: int, percent: int, rank: int}>
     */
    public static function winnersOf(array $standings): array
    {
        return array_values(array_filter($standings, fn (array $athlete) => $athlete['rank'] === 1 && $athlete['votes'] > 0));
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

        $standings = $this->standings();
        $open = ! $this->event->voting_closed;

        return [
            'revision' => $this->revision,
            'show' => [
                'type' => $this->show_type,
                'phase' => $this->phase(),
                'elapsed_ms' => $this->elapsedMs(),
                'countdown_ms' => $this->show_countdown_ms,
                'result_ms' => $this->show_result_ms,
                'headline' => $this->headlineText(),
                'confetti' => $this->confetti,
                'open' => $open,
                'qr' => $this->show_type === self::SHOW_LIVE && $this->show_qr && $open,
                'total' => $this->show_total ? array_sum(array_column($standings, 'votes')) : null,
                'standings' => $this->present($this->athletesOnScreen($standings), $standings),
                'winners' => $this->show_type === self::SHOW_REVEAL ? $this->present(self::winnersOf($standings), $standings) : [],
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
                'result_ms' => $this->show_result_ms,
                'winners' => array_column(self::winnersOf($this->show_type === self::SHOW_REVEAL ? ($this->show_standings ?? []) : []), 'name'),
            ] : null,
        ];
    }

    /**
     * Live results show everyone down to the place the settings stop at (ties
     * for that place included); the podium shows the top three places that
     * have votes.
     *
     * @param  list<array{key: string, name: string, position: string|null, votes: int, percent: int, rank: int}>  $standings
     * @return list<array{key: string, name: string, position: string|null, votes: int, percent: int, rank: int}>
     */
    protected function athletesOnScreen(array $standings): array
    {
        $shown = match ($this->show_type) {
            self::SHOW_LIVE => array_filter($standings, fn (array $athlete) => $this->top_count === 0 || $athlete['rank'] <= $this->top_count),
            self::SHOW_PODIUM => array_filter($standings, fn (array $athlete) => $athlete['rank'] <= self::PODIUM_PLACES && $athlete['votes'] > 0),
            default => [],
        };

        return array_values($shown);
    }

    /**
     * Athletes with only what the settings put on screen. `share` sizes the
     * bars against the leader.
     *
     * @param  list<array{key: string, name: string, position: string|null, votes: int, percent: int, rank: int}>  $athletes
     * @param  list<array{key: string, name: string, position: string|null, votes: int, percent: int, rank: int}>  $standings
     * @return list<array{key: string, rank: int, name: string, position: string|null, votes: int|null, percent: int|null, share: float}>
     */
    protected function present(array $athletes, array $standings): array
    {
        $leaderVotes = max([0, ...array_column($standings, 'votes')]);

        return array_map(fn (array $athlete) => [
            'key' => $athlete['key'],
            'rank' => $athlete['rank'],
            'name' => $athlete['name'],
            'position' => $this->show_position ? $athlete['position'] : null,
            'votes' => $this->show_votes ? $athlete['votes'] : null,
            'percent' => $this->show_percent ? $athlete['percent'] : null,
            'share' => $leaderVotes > 0 ? round($athlete['votes'] / $leaderVotes, 4) : 0.0,
        ], $athletes);
    }

    /**
     * @param  (Closure(self): array<string, mixed>)|null  $timing
     */
    protected function begin(string $type, ?Closure $timing = null): void
    {
        $this->mutate(function (self $screen) use ($type, $timing): void {
            $lotteryHeldPlayer = $screen->takeScreenFromLottery();
            $resumePlayer = $screen->pausePlayer() || $lotteryHeldPlayer || ($screen->show_type && $screen->resume_player);

            $screen->forceFill([
                'show_type' => $type,
                'show_starts_ms' => now()->getTimestampMs(),
                'show_countdown_ms' => 0,
                'show_result_ms' => 0,
                'show_standings' => null,
                'resume_player' => $resumePlayer,
                ...($timing ? $timing($screen) : []),
            ]);
        });
    }

    protected function clearShow(bool $resumePlayer = true): void
    {
        if (! $this->show_type) {
            return;
        }

        if ($resumePlayer && $this->resume_player) {
            $player = $this->event->vidiwallPlayer;

            if ($player?->isPaused()) {
                $player->setRelation('event', $this->event)->play();
            }
        }

        $this->forceFill([
            'show_type' => null,
            'show_starts_ms' => null,
            'show_countdown_ms' => 0,
            'show_result_ms' => 0,
            'show_standings' => null,
            'resume_player' => false,
        ]);
    }

    /**
     * Takes the screen from a lottery show, so only one show is ever up. True
     * when the lottery was holding the playlist paused, which this show then
     * holds instead.
     */
    protected function takeScreenFromLottery(): bool
    {
        $lottery = $this->event->lotteryScreen;

        return $lottery ? $lottery->setRelation('event', $this->event)->yieldScreen() : false;
    }

    /**
     * Pauses the playlist while the vote has the screen, so no photo plays
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

        return $result;
    }
}
