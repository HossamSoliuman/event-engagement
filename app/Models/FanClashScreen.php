<?php

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Fan Clash's turn on the vidiwall, run like a player. A matchup goes up for
 * the room to pick sides, the round counts in and runs, and the winner stays
 * up until the operator is done. The stage follows the round the console is
 * running; `on_screen` says whether the wall shows it. Every change bumps
 * `revision`.
 */
class FanClashScreen extends Model
{
    public const STAGE_READY = 'ready';

    public const STAGE_MATCHUP = 'matchup';

    public const STAGE_LIVE = 'live';

    public const STAGE_RESULT = 'result';

    /**
     * The round lengths the console offers, in seconds.
     */
    public const ROUND_SECONDS = [10, 15, 20, 30, 45, 60];

    /**
     * What one press of "+10 s" gives a running round.
     */
    public const EXTEND_SECONDS = 10;

    /**
     * The longest a round may run, extensions included.
     */
    public const MAX_ROUND_SECONDS = 180;

    protected $fillable = [
        'event_id',
        'round_seconds',
        'countdown_seconds',
        'show_taps',
        'show_qr',
        'confetti',
    ];

    protected $casts = [
        'round_seconds' => 'integer',
        'countdown_seconds' => 'integer',
        'show_taps' => 'boolean',
        'show_qr' => 'boolean',
        'confetti' => 'boolean',
        'on_screen' => 'boolean',
        'resume_player' => 'boolean',
        'revision' => 'integer',
    ];

    protected $attributes = [
        'round_seconds' => 20,
        'countdown_seconds' => 3,
        'show_taps' => true,
        'show_qr' => true,
        'confetti' => true,
        'on_screen' => false,
        'resume_player' => false,
        'revision' => 0,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(FanClashRound::class, 'fan_clash_round_id');
    }

    /**
     * The round the console is running, closed first when its time is up.
     */
    public function currentRound(): ?FanClashRound
    {
        $round = $this->round;
        $round?->finalizeIfExpired();

        return $round;
    }

    public function stage(): string
    {
        return match ($this->currentRound()?->status) {
            'waiting' => self::STAGE_MATCHUP,
            'active' => self::STAGE_LIVE,
            'finished' => self::STAGE_RESULT,
            default => self::STAGE_READY,
        };
    }

    /**
     * True while the wall shows a round that is still counting taps. Nothing
     * else may take the screen until it is over.
     */
    public function isRoundRunningOnScreen(): bool
    {
        return $this->on_screen && $this->stage() === self::STAGE_LIVE;
    }

    /**
     * Puts a new matchup on the wall, so the room can pick a side before the
     * round starts.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public function prepare(array $snapshot): FanClashRound
    {
        return $this->mutate(function (self $screen) use ($snapshot): FanClashRound {
            $round = FanClashRound::create([
                ...$snapshot,
                'event_id' => $screen->event_id,
                'status' => 'waiting',
                'duration_seconds' => $screen->round_seconds,
                'side_a_taps' => 0,
                'side_b_taps' => 0,
            ]);

            $screen->fan_clash_round_id = $round->id;
            $screen->setRelation('round', $round);
            $screen->takeScreen();

            return $round;
        });
    }

    /**
     * Counts the room in on the wall, then the taps count for the set time.
     */
    public function start(): void
    {
        $this->mutate(function (self $screen): void {
            $round = $screen->round;

            if ($round?->status !== 'waiting') {
                return;
            }

            $countdown = $screen->countdown_seconds;

            $round->update([
                'status' => 'active',
                'duration_seconds' => $screen->round_seconds,
                'started_at' => $countdown > 0
                    ? now()->addSeconds($countdown + 1)->startOfSecond()
                    : now()->startOfSecond(),
            ]);

            $screen->takeScreen();
        });
    }

    /**
     * Ends the running round now; the winner comes from the taps so far.
     */
    public function end(): void
    {
        $this->mutate(function (self $screen): void {
            $round = $screen->round;

            if ($round?->status !== 'active') {
                return;
            }

            $round->update([
                'status' => 'finished',
                'winner_side' => $round->getWinnerSide(),
                'finished_at' => now(),
            ]);
        });
    }

    /**
     * Gives the running round more time.
     */
    public function extend(): void
    {
        $this->mutate(function (self $screen): void {
            $round = $screen->currentRound();

            if ($round?->status !== 'active') {
                return;
            }

            $round->update([
                'duration_seconds' => min(self::MAX_ROUND_SECONDS, $round->duration_seconds + self::EXTEND_SECONDS),
            ]);
        });
    }

    /**
     * Puts the clash back on the wall.
     */
    public function show(): void
    {
        $this->mutate(function (self $screen): void {
            if ($screen->round) {
                $screen->takeScreen();
            }
        });
    }

    /**
     * Takes the clash off the wall and lets the playlist carry on if the clash
     * paused it. The round itself carries on.
     */
    public function hide(): void
    {
        $this->mutate(fn (self $screen) => $screen->releaseScreen());
    }

    /**
     * Done with the round: off the wall, with the playlist carrying on, and
     * the console ready for the next matchup.
     */
    public function finish(): void
    {
        $this->mutate(function (self $screen): void {
            $screen->releaseScreen();
            $screen->fan_clash_round_id = null;
            $screen->setRelation('round', null);
        });
    }

    /**
     * Drops a matchup that has not started, so another one can be picked.
     */
    public function cancel(): void
    {
        $this->mutate(function (self $screen): void {
            $round = $screen->round;

            if ($round?->status !== 'waiting') {
                return;
            }

            $screen->releaseScreen();
            $screen->fan_clash_round_id = null;
            $screen->setRelation('round', null);
            $round->delete();
        });
    }

    /**
     * Clears the clash off the wall for another show taking it, leaving the
     * playlist paused for that show. True when the clash was holding the
     * playlist paused.
     */
    public function yieldScreen(): bool
    {
        if (! $this->on_screen) {
            return false;
        }

        return $this->mutate(function (self $screen): bool {
            $heldPlayer = $screen->on_screen && $screen->resume_player;
            $screen->releaseScreen(resumePlayer: false);

            return $heldPlayer;
        });
    }

    /**
     * Lets the playlist go again when the round the wall was showing is gone.
     */
    public function settle(): void
    {
        if (! $this->on_screen || $this->round) {
            return;
        }

        $this->mutate(function (self $screen): void {
            if (! $screen->round) {
                $screen->releaseScreen();
            }
        });
    }

    /**
     * What the vidiwall needs to run the clash, timed from the moment of the
     * request.
     *
     * @return array{revision: int, show: array<string, mixed>|null}
     */
    public function screenFeed(): array
    {
        $this->settle();
        $round = $this->on_screen ? $this->currentRound() : null;

        if (! $round) {
            return ['revision' => $this->revision, 'show' => null];
        }

        return [
            'revision' => $this->revision,
            'show' => [
                'stage' => $this->stage(),
                ...$this->presentRound($round),
                'headline' => $this->event->fanclash_title ?: 'Fan Clash',
                'show_taps' => $this->show_taps,
                'qr' => $this->show_qr,
                'confetti' => $this->confetti,
            ],
        ];
    }

    /**
     * The clash as the console mirrors it.
     *
     * @return array<string, mixed>
     */
    public function controlStatus(): array
    {
        $this->settle();
        $round = $this->currentRound();
        $stage = $this->stage();
        $last = $stage === self::STAGE_READY
            ? $this->event->fanClashRounds()->where('status', 'finished')->latest('finished_at')->first()
            : null;

        return [
            'revision' => $this->revision,
            'stage' => $stage,
            'on_screen' => $this->on_screen && $round !== null,
            'round_seconds' => $this->round_seconds,
            'countdown_seconds' => $this->countdown_seconds,
            'round' => $round ? [...$this->presentRound($round), 'fans' => $round->headcounts()] : null,
            'last' => $last ? $this->presentRound($last) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentRound(FanClashRound $round): array
    {
        $durationMs = $round->duration_seconds * 1000;

        return [
            'round_id' => $round->id,
            'category' => $round->category,
            'side_a_name' => $round->side_a_name,
            'side_b_name' => $round->side_b_name,
            'side_a_color' => $round->side_a_color,
            'side_b_color' => $round->side_b_color,
            'side_a_taps' => $round->side_a_taps,
            'side_b_taps' => $round->side_b_taps,
            'duration_ms' => $durationMs,
            'starts_in_ms' => $round->startsInMs(),
            'remaining_ms' => match ($round->status) {
                'active' => min($durationMs, $round->remainingMs()),
                'waiting' => $durationMs,
                default => 0,
            },
            'winner_side' => $round->winner_side,
            'sponsor_logo_url' => $round->sponsor_logo_url,
        ];
    }

    protected function takeScreen(): void
    {
        $lotteryHeldPlayer = $this->takeScreenFromLottery();
        $voteHeldPlayer = $this->takeScreenFromVoting();
        $pausedPlayer = $this->pausePlayer();

        $this->resume_player = $pausedPlayer || $lotteryHeldPlayer || $voteHeldPlayer || ($this->on_screen && $this->resume_player);
        $this->on_screen = true;
    }

    protected function releaseScreen(bool $resumePlayer = true): void
    {
        if ($resumePlayer && $this->on_screen && $this->resume_player) {
            $player = $this->event->vidiwallPlayer;

            if ($player?->isPaused()) {
                $player->setRelation('event', $this->event)->play();
            }
        }

        $this->on_screen = false;
        $this->resume_player = false;
    }

    /**
     * Takes the screen from a lottery show, so only one show is ever up. True
     * when the lottery was holding the playlist paused.
     */
    protected function takeScreenFromLottery(): bool
    {
        $lottery = $this->event->lotteryScreen;

        return $lottery ? $lottery->setRelation('event', $this->event)->yieldScreen() : false;
    }

    /**
     * Takes the screen from a vote show. True when the vote was holding the
     * playlist paused.
     */
    protected function takeScreenFromVoting(): bool
    {
        $voting = $this->event->votingScreen;

        return $voting ? $voting->setRelation('event', $this->event)->yieldScreen() : false;
    }

    /**
     * Pauses the playlist while the clash has the screen, so no photo plays
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
     * Applies a change to the freshest copy under a row lock, so two consoles
     * pressing at once cannot undo each other. The lock is claimed with a
     * write first, as SQLite has no row locks.
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

            $fresh->revision = (int) $fresh->getOriginal('revision') + 1;
            $fresh->save();

            return [$fresh, $result];
        });

        $this->setRawAttributes($fresh->getAttributes(), true);
        $this->unsetRelation('round');

        return $result;
    }
}
