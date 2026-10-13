<?php

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * The vidiwall as a media player. Approved uploads form the event's playlist
 * and nothing plays until an operator presses Play. The screen and the control
 * pages poll the same row; every playback change bumps `revision` so a poller
 * can tell a fresh command from a state it has already applied.
 */
class VidiwallPlayer extends Model
{
    use HasFactory;

    public const STATE_STOPPED = 'stopped';

    public const STATE_PLAYING = 'playing';

    public const STATE_PAUSED = 'paused';

    protected $fillable = ['event_id', 'state', 'foto_upload_id', 'started_ms', 'paused_elapsed_ms', 'loop', 'revision'];

    protected $casts = [
        'foto_upload_id' => 'integer',
        'started_ms' => 'integer',
        'paused_elapsed_ms' => 'integer',
        'loop' => 'boolean',
        'revision' => 'integer',
    ];

    protected $attributes = [
        'state' => self::STATE_STOPPED,
        'loop' => false,
        'revision' => 0,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function foto(): BelongsTo
    {
        return $this->belongsTo(FotoUpload::class, 'foto_upload_id');
    }

    public function isPlaying(): bool
    {
        return $this->state === self::STATE_PLAYING;
    }

    public function isPaused(): bool
    {
        return $this->state === self::STATE_PAUSED;
    }

    public function isStopped(): bool
    {
        return $this->state === self::STATE_STOPPED;
    }

    /**
     * Approved items of this event in the order they play.
     *
     * @return Builder<FotoUpload>
     */
    public function playlist(): Builder
    {
        return FotoUpload::query()->where('event_id', $this->event_id)->inPlaylist();
    }

    /**
     * How far into the current item playback is; frozen while paused.
     */
    public function elapsedMs(): int
    {
        if ($this->isPaused()) {
            return (int) $this->paused_elapsed_ms;
        }

        if (! $this->isPlaying() || ! $this->started_ms) {
            return 0;
        }

        return max(0, now()->getTimestampMs() - $this->started_ms);
    }

    /**
     * The item the screen shows right now: the playhead's item while playing or
     * paused, as long as it is still approved.
     */
    public function screenFoto(): ?FotoUpload
    {
        if ($this->isStopped() || ! $this->foto_upload_id) {
            return null;
        }

        $foto = $this->foto;

        if (! $foto || ! $foto->isApproved()) {
            return null;
        }

        return $foto->setRelation('event', $this->event);
    }

    /**
     * The item that plays after the current one, or the top of the playlist while
     * stopped, so the screen can load it before it is due.
     */
    public function upNext(): ?FotoUpload
    {
        $current = $this->isStopped() ? null : $this->foto;

        if (! $current) {
            return $this->playlist()->first();
        }

        return $this->playlist()->afterInPlaylist($current)->first()
            ?? ($this->loop ? $this->playlist()->whereKeyNot($current->id)->first() : null);
    }

    /**
     * Starts from the top of the playlist when stopped and resumes when paused.
     * Returns false when the playlist is empty.
     */
    public function play(): bool
    {
        return $this->mutate(function (self $player): bool {
            $current = $player->screenFoto();

            if ($player->isPlaying() && $current) {
                return true;
            }

            if ($player->isPaused() && $current) {
                $player->forceFill([
                    'state' => self::STATE_PLAYING,
                    'started_ms' => now()->getTimestampMs() - (int) $player->paused_elapsed_ms,
                    'paused_elapsed_ms' => null,
                ]);

                return true;
            }

            $first = $player->playlist()->first();

            if (! $first) {
                $player->resetToStopped();

                return false;
            }

            $player->cue($first, self::STATE_PLAYING);

            return true;
        });
    }

    public function pause(): void
    {
        $this->mutate(function (self $player): void {
            if (! $player->isPlaying()) {
                return;
            }

            $current = $player->screenFoto();

            $player->forceFill([
                'state' => self::STATE_PAUSED,
                'started_ms' => null,
                'paused_elapsed_ms' => $current ? min($player->elapsedMs(), $current->screenSlotMs()) : 0,
            ]);
        });
    }

    /**
     * Clears the screen back to the QR code; the next Play starts from the top.
     */
    public function stop(): void
    {
        $this->mutate(fn (self $player) => $player->resetToStopped());
    }

    public function next(): void
    {
        $this->mutate(function (self $player): void {
            if (! $player->isStopped()) {
                $player->advance();
            }
        });
    }

    public function previous(): void
    {
        $this->mutate(function (self $player): void {
            if ($player->isStopped()) {
                return;
            }

            $current = $player->foto;
            $previous = $current ? $player->playlist()->beforeInPlaylist($current)->first() : null;

            if (! $previous && $player->loop) {
                $previous = $player->playlist()->reorder()->orderByDesc('display_order')->orderByDesc('id')->first();
            }

            $previous ??= $current?->isApproved() ? $current : $player->playlist()->first();

            $previous ? $player->cue($previous, $player->state) : $player->resetToStopped();
        });
    }

    /**
     * Jumps the playhead to the item and plays from there.
     */
    public function playFrom(FotoUpload $foto): void
    {
        $this->mutate(fn (self $player) => $player->cue($foto, self::STATE_PLAYING));
    }

    /**
     * "Push Live": slots the item in right after the current one and plays it
     * straight away, so the playlist carries on from where it was afterwards.
     */
    public function playNow(FotoUpload $foto): void
    {
        $this->mutate(function (self $player) use ($foto): void {
            if ($player->foto_upload_id !== $foto->id) {
                $player->moveAfterCurrent($foto);
            }

            $player->cue($foto->fresh(), self::STATE_PLAYING);
        });
    }

    /**
     * Moves the item to play right after the current one, or to the top of the
     * playlist when nothing is playing.
     */
    public function playNext(FotoUpload $foto): void
    {
        $this->mutate(function (self $player) use ($foto): void {
            if ($player->isStopped() || $player->foto_upload_id !== $foto->id) {
                $player->moveAfterCurrent($foto);
            }
        });
    }

    /**
     * Saves a dragged order. Ids that are no longer in the playlist are ignored
     * and items the operator has not seen yet (approved since the page last
     * refreshed) keep their place at the end.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->mutate(function (self $player) use ($orderedIds): void {
            $playlist = $player->playlistIds();
            $known = array_flip($playlist);
            $ordered = array_values(array_unique(array_filter($orderedIds, fn (int $id) => isset($known[$id]))));

            $player->writeOrder([...$ordered, ...array_values(array_diff($playlist, $ordered))]);
        });
    }

    public function setLoop(bool $loop): void
    {
        $this->mutate(fn (self $player) => $player->forceFill(['loop' => $loop]));
    }

    /**
     * Changes how long each photo holds the screen. The revision moves on so a
     * screen in the middle of a photo re-times it with the new length.
     */
    public function setPhotoSeconds(int $seconds): void
    {
        if ((int) $this->event->vidiwall_photo_seconds === $seconds) {
            return;
        }

        $this->mutate(function (self $player) use ($seconds): void {
            $player->event->update(['vidiwall_photo_seconds' => $seconds]);
            $player->revision++;
        });
    }

    /**
     * Moves the playhead on once the current item is done. The screen reports
     * the item (and the revision it was showing) when it finishes, which is what
     * normally advances the playlist; the item's slot is the fallback for when
     * no screen is connected. Videos get a grace period on top so a screen that
     * is still buffering one does not get cut off.
     */
    public function sync(?int $finishedFotoId = null, ?int $finishedRevision = null): void
    {
        if (! $this->isPlaying() || ! $this->currentItemIsDone($finishedFotoId, $finishedRevision)) {
            return;
        }

        $this->mutate(function (self $player) use ($finishedFotoId, $finishedRevision): void {
            if ($player->isPlaying() && $player->currentItemIsDone($finishedFotoId, $finishedRevision)) {
                $player->advance();
            }
        });
    }

    /**
     * Moves past the item when it is the one at the playhead, used when it is
     * skipped, rejected or deleted.
     */
    public function skip(FotoUpload $foto): void
    {
        $this->mutate(function (self $player) use ($foto): void {
            if ($player->foto_upload_id !== $foto->id) {
                return;
            }

            $player->isStopped() ? $player->resetToStopped() : $player->advance($foto);
        });
    }

    protected function currentItemIsDone(?int $finishedFotoId, ?int $finishedRevision): bool
    {
        $current = $this->screenFoto();

        if (! $current) {
            return true;
        }

        if ($finishedFotoId === $current->id && $finishedRevision === $this->revision) {
            return true;
        }

        $graceMs = $current->isVideo() ? FotoUpload::SCREEN_GRACE_SECONDS * 1000 : 0;

        return $this->elapsedMs() >= $current->screenSlotMs() + $graceMs;
    }

    /**
     * Steps to the item after the current one, wrapping around at the end when
     * looping and stopping otherwise. `$leaving` is left out of the playlist
     * because it is about to disappear from it.
     */
    protected function advance(?FotoUpload $leaving = null): void
    {
        $current = $this->foto;
        $playlist = fn () => $this->playlist()->when($leaving, fn (Builder $query) => $query->whereKeyNot($leaving->id));

        $next = $current ? $playlist()->afterInPlaylist($current)->first() : null;

        if (! $next && $this->loop) {
            $next = $playlist()->first();
        }

        $next ? $this->cue($next, $this->state) : $this->resetToStopped();
    }

    protected function cue(FotoUpload $foto, string $state): void
    {
        $foto->update(['displayed_at' => now()]);

        $this->forceFill([
            'foto_upload_id' => $foto->id,
            'state' => $state,
            'started_ms' => $state === self::STATE_PLAYING ? now()->getTimestampMs() : null,
            'paused_elapsed_ms' => $state === self::STATE_PAUSED ? 0 : null,
        ])->setRelation('foto', $foto);
    }

    protected function resetToStopped(): void
    {
        $this->forceFill([
            'state' => self::STATE_STOPPED,
            'foto_upload_id' => null,
            'started_ms' => null,
            'paused_elapsed_ms' => null,
        ])->setRelation('foto', null);
    }

    protected function moveAfterCurrent(FotoUpload $foto): void
    {
        $ids = array_values(array_filter($this->playlistIds(), fn (int $id) => $id !== $foto->id));
        $position = $this->isStopped() ? false : array_search($this->foto_upload_id, $ids, true);

        array_splice($ids, $position === false ? 0 : $position + 1, 0, [$foto->id]);

        $this->writeOrder($ids);
    }

    /**
     * @return list<int>
     */
    protected function playlistIds(): array
    {
        return $this->playlist()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Renumbers the playlist 1..n in the given order, writing only the rows whose position changed.
     *
     * @param  list<int>  $orderedIds
     */
    protected function writeOrder(array $orderedIds): void
    {
        $positions = FotoUpload::query()->whereIn('id', $orderedIds)->pluck('display_order', 'id');

        foreach ($orderedIds as $index => $id) {
            if ((int) $positions->get($id) !== $index + 1) {
                FotoUpload::query()->whereKey($id)->update(['display_order' => $index + 1]);
            }
        }
    }

    /**
     * Applies a change to the freshest copy of the player under a row lock, so
     * the screen and several operators polling at once cannot double-advance.
     * The lock is claimed with a write before anything is read: SQLite has no
     * row locks and fails a transaction that reads and then writes when another
     * write got in between, while one that writes first waits its turn.
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
        $this->unsetRelation('foto');

        return $result;
    }
}
