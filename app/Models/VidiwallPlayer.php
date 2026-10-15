<?php

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * The vidiwall as a media player. Approved uploads form the event's playlist
 * and nothing plays until an operator presses Play. The screen and the control
 * pages poll the same row; every playback change bumps `revision` so a poller
 * can tell a fresh command from a state it has already applied.
 *
 * The screen shows a page: the item at the playhead plus, when the event shows
 * several photos at once, the photos that follow it. A video always plays on
 * its own. The page is frozen when it is cued (`page_ids`), so approvals and
 * reorders during its slot do not reshuffle what is on the wall.
 *
 * The QR screen covers the wall with the event QR code on its own. The
 * playlist pauses under it and keeps its place, and it never runs while the
 * QR screen is up.
 */
class VidiwallPlayer extends Model
{
    use HasFactory;

    public const STATE_STOPPED = 'stopped';

    public const STATE_PLAYING = 'playing';

    public const STATE_PAUSED = 'paused';

    /** How many photos the screen can show at once. */
    public const PHOTOS_PER_SCREEN = [1, 2, 4];

    protected $fillable = ['event_id', 'state', 'foto_upload_id', 'page_ids', 'started_ms', 'paused_elapsed_ms', 'loop', 'qr_screen', 'resume_after_qr', 'revision'];

    protected $casts = [
        'foto_upload_id' => 'integer',
        'page_ids' => 'array',
        'started_ms' => 'integer',
        'paused_elapsed_ms' => 'integer',
        'loop' => 'boolean',
        'qr_screen' => 'boolean',
        'resume_after_qr' => 'boolean',
        'revision' => 'integer',
    ];

    protected $attributes = [
        'state' => self::STATE_STOPPED,
        'loop' => false,
        'qr_screen' => false,
        'resume_after_qr' => false,
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
     * The ids of the page on screen, playhead first; empty while stopped. A
     * player cued before pages existed has none and shows the playhead alone.
     *
     * @return list<int>
     */
    public function pageIds(): array
    {
        if ($this->isStopped() || ! $this->foto_upload_id) {
            return [];
        }

        $ids = array_values(array_map('intval', (array) $this->page_ids));

        return ($ids[0] ?? null) === $this->foto_upload_id ? $ids : [$this->foto_upload_id];
    }

    /**
     * The item the screen shows right now: the playhead's item while playing or
     * paused, as long as it is still approved. With several photos on screen it
     * is the first of the page, and it is what the screen's done report names.
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
     * Every item the screen shows right now, playhead first, leaving out any
     * that are no longer approved. Empty whenever screenFoto() is null.
     *
     * @return Collection<int, FotoUpload>
     */
    public function screenFotos(): Collection
    {
        $ids = $this->pageIds();

        if ($ids === []) {
            return new Collection;
        }

        $fotos = FotoUpload::query()->whereIn('id', $ids)->where('status', 'approved')->get()->keyBy('id');

        if (! $fotos->has($this->foto_upload_id)) {
            return new Collection;
        }

        $page = new Collection;

        foreach ($ids as $id) {
            if ($fotos->has($id)) {
                $page->push($fotos->get($id)->setRelation('event', $this->event));
            }
        }

        return $page;
    }

    /**
     * The page that plays after the current one, or the first page while
     * stopped, so the screen can load it before it is due. Empty when nothing
     * new comes next.
     *
     * @return Collection<int, FotoUpload>
     */
    public function upNextPage(): Collection
    {
        $current = $this->isStopped() ? null : $this->foto;

        if (! $current) {
            $first = $this->playlist()->first();

            return $first ? $this->buildPage($first) : new Collection;
        }

        $next = $this->playlist()->afterInPlaylist($this->pageEnd() ?? $current)->first();

        if (! $next && $this->loop) {
            $first = $this->playlist()->first();
            $next = $first && ! in_array($first->id, $this->pageIds(), true) ? $first : null;
        }

        return $next ? $this->buildPage($next) : new Collection;
    }

    /**
     * Starts from the top of the playlist when stopped and resumes when paused,
     * taking the QR screen down. Returns false when the playlist is empty.
     */
    public function play(): bool
    {
        return $this->mutate(function (self $player): bool {
            $current = $player->screenFoto();

            if ($player->isPlaying() && $current) {
                return true;
            }

            if ($player->isPaused() && $current) {
                $player->leaveQrScreen();
                $player->resume();

                return true;
            }

            $first = $player->playlist()->first();

            if (! $first) {
                $player->resetToStopped();

                return false;
            }

            $player->leaveQrScreen();
            $player->cue($first, self::STATE_PLAYING);

            return true;
        });
    }

    public function pause(): void
    {
        $this->mutate(fn (self $player) => $player->hold());
    }

    /**
     * Clears the screen back to the idle QR code, taking the QR screen down
     * too; the next Play starts from the top.
     */
    public function stop(): void
    {
        $this->mutate(function (self $player): void {
            $player->leaveQrScreen();
            $player->resetToStopped();
        });
    }

    /**
     * Puts the QR screen up or takes it down. A running playlist pauses under
     * it and resumes where it left off when it comes down; a paused or stopped
     * one stays as it was.
     */
    public function setQrScreen(bool $show): void
    {
        $this->mutate(function (self $player) use ($show): void {
            if ($player->qr_screen === $show) {
                return;
            }

            if ($show) {
                $resume = $player->isPlaying();
                $player->hold();
                $player->forceFill(['qr_screen' => true, 'resume_after_qr' => $resume]);

                return;
            }

            $resume = $player->resume_after_qr && $player->isPaused() && $player->screenFoto();
            $player->leaveQrScreen();

            if ($resume) {
                $player->resume();
            }
        });
    }

    public function next(): void
    {
        $this->mutate(function (self $player): void {
            if (! $player->isStopped()) {
                $player->advance();
            }
        });
    }

    /**
     * Steps back to the page that ends right before the current one, wrapping
     * around to the end of the playlist when looping. On the first page without
     * loop the page starts over.
     */
    public function previous(): void
    {
        $this->mutate(function (self $player): void {
            if ($player->isStopped()) {
                return;
            }

            $current = $player->foto;
            $previous = $current ? $player->pageEndingBefore($current) : null;

            if (! $previous && $player->loop) {
                $previous = $player->pageEndingBefore(null);
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
        $this->mutate(function (self $player) use ($foto): void {
            $player->leaveQrScreen();
            $player->cue($foto, self::STATE_PLAYING);
        });
    }

    /**
     * "Push Live": slots the item in right after the current page and plays it
     * straight away, so the playlist carries on from where it was afterwards.
     */
    public function playNow(FotoUpload $foto): void
    {
        $this->mutate(function (self $player) use ($foto): void {
            if ($player->foto_upload_id !== $foto->id) {
                $player->moveAfterCurrent($foto);
            }

            $player->leaveQrScreen();
            $player->cue($foto->fresh(), self::STATE_PLAYING);
        });
    }

    /**
     * Moves the item to play right after the current page, or to the top of
     * the playlist when nothing is playing. An item already on screen stays put.
     */
    public function playNext(FotoUpload $foto): void
    {
        $this->mutate(function (self $player) use ($foto): void {
            if (! in_array($foto->id, $player->pageIds(), true)) {
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
     * Changes how many photos the screen shows at once. The page on screen is
     * rebuilt from its playhead straight away and keeps its time.
     */
    public function setPhotosPerScreen(int $count): void
    {
        if ((int) $this->event->vidiwall_photos_per_screen === $count) {
            return;
        }

        $this->mutate(function (self $player) use ($count): void {
            $player->event->update(['vidiwall_photos_per_screen' => $count]);

            $current = $player->screenFoto();

            if ($current) {
                $pageIds = $player->buildPage($current)->modelKeys();
                $player->markDisplayed(array_values(array_diff($pageIds, $player->pageIds())));
                $player->page_ids = $pageIds;
            }

            $player->revision++;
        });
    }

    /**
     * Moves the playhead on once the current page is done. The screen reports
     * the playhead's item (and the revision it was showing) when it finishes,
     * which is what normally advances the playlist; the slot is the fallback
     * for when no screen is connected. Videos get a grace period on top so a
     * screen that is still buffering one does not get cut off.
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
     * Takes the item off the screen when it is on the page there; used when it
     * is skipped, rejected or deleted. The rest of the page stays up and keeps
     * its time (the next item takes over the playhead when the playhead goes),
     * and the next page comes up once nothing is left.
     */
    public function skip(FotoUpload $foto): void
    {
        $this->mutate(function (self $player) use ($foto): void {
            $pageIds = $player->pageIds();

            if (! in_array($foto->id, $pageIds, true)) {
                if ($player->foto_upload_id === $foto->id) {
                    $player->resetToStopped();
                }

                return;
            }

            $approved = $player->playlist()->whereIn('id', array_values(array_diff($pageIds, [$foto->id])))->pluck('id')->map(fn ($id) => (int) $id)->all();
            $remaining = array_values(array_intersect($pageIds, $approved));

            if ($remaining === []) {
                $player->advance($foto);

                return;
            }

            $player->forceFill(['foto_upload_id' => $remaining[0], 'page_ids' => $remaining])->unsetRelation('foto');
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
     * Steps to the page after the current one, wrapping around at the end when
     * looping and stopping otherwise. `$leaving` is left out of the playlist
     * because it is about to disappear from it.
     */
    protected function advance(?FotoUpload $leaving = null): void
    {
        $anchor = $this->pageEnd($leaving) ?? $this->foto;
        $playlist = fn () => $this->playlist()->when($leaving, fn (Builder $query) => $query->whereKeyNot($leaving->id));

        $next = $anchor ? $playlist()->afterInPlaylist($anchor)->first() : null;

        if (! $next && $this->loop) {
            $next = $playlist()->first();
        }

        $next ? $this->cue($next, $this->state, $leaving) : $this->resetToStopped();
    }

    /**
     * The last item of a page of several that is still in the playlist, which
     * the next page starts after. Null for a single item; the playhead stands
     * in then.
     */
    protected function pageEnd(?FotoUpload $leaving = null): ?FotoUpload
    {
        $ids = $this->pageIds();

        if (count($ids) < 2) {
            return null;
        }

        $fotos = $this->playlist()
            ->whereIn('id', $ids)
            ->when($leaving, fn (Builder $query) => $query->whereKeyNot($leaving->id))
            ->get()
            ->keyBy('id');

        foreach (array_reverse($ids) as $id) {
            if ($fotos->has($id)) {
                return $fotos->get($id);
            }
        }

        return null;
    }

    /**
     * The page that starts at `$start`: the photos that follow it, up to the
     * event's photos per screen and stopping before the next video. A video is
     * a page on its own, and pages never wrap around the end of the playlist.
     *
     * @return Collection<int, FotoUpload>
     */
    protected function buildPage(FotoUpload $start, ?FotoUpload $leaving = null): Collection
    {
        $page = new Collection([$start]);
        $size = $this->event->photosPerScreen();

        if ($size === 1 || $start->isVideo()) {
            return $page;
        }

        $following = $this->playlist()
            ->afterInPlaylist($start)
            ->when($leaving, fn (Builder $query) => $query->whereKeyNot($leaving->id))
            ->limit($size - 1)
            ->get();

        foreach ($following as $foto) {
            if ($foto->isVideo()) {
                break;
            }

            $page->push($foto);
        }

        return $page;
    }

    /**
     * Where the page that ends right before `$anchor` starts, or the page that
     * ends the playlist when there is no anchor: a video just before it plays
     * on its own, otherwise up to a screenful of photos back to the last video.
     */
    protected function pageEndingBefore(?FotoUpload $anchor): ?FotoUpload
    {
        $before = $anchor
            ? $this->playlist()->beforeInPlaylist($anchor)
            : $this->playlist()->reorder()->orderByDesc('display_order')->orderByDesc('id');

        $start = null;

        foreach ($before->limit($this->event->photosPerScreen())->get() as $foto) {
            if ($foto->isVideo()) {
                return $start ?? $foto;
            }

            $start = $foto;
        }

        return $start;
    }

    /**
     * Freezes the item on screen where it is.
     */
    protected function hold(): void
    {
        if (! $this->isPlaying()) {
            return;
        }

        $current = $this->screenFoto();

        $this->forceFill([
            'state' => self::STATE_PAUSED,
            'started_ms' => null,
            'paused_elapsed_ms' => $current ? min($this->elapsedMs(), $current->screenSlotMs()) : 0,
        ]);
    }

    /**
     * Plays the held item on from where it was frozen.
     */
    protected function resume(): void
    {
        $this->forceFill([
            'state' => self::STATE_PLAYING,
            'started_ms' => now()->getTimestampMs() - (int) $this->paused_elapsed_ms,
            'paused_elapsed_ms' => null,
        ]);
    }

    protected function leaveQrScreen(): void
    {
        $this->forceFill(['qr_screen' => false, 'resume_after_qr' => false]);
    }

    protected function cue(FotoUpload $foto, string $state, ?FotoUpload $leaving = null): void
    {
        $pageIds = array_map('intval', $this->buildPage($foto, $leaving)->modelKeys());

        $this->markDisplayed($pageIds);
        $foto->forceFill(['displayed_at' => now()])->syncOriginalAttribute('displayed_at');

        $this->forceFill([
            'foto_upload_id' => $foto->id,
            'page_ids' => $pageIds,
            'state' => $state,
            'started_ms' => $state === self::STATE_PLAYING ? now()->getTimestampMs() : null,
            'paused_elapsed_ms' => $state === self::STATE_PAUSED ? 0 : null,
        ])->setRelation('foto', $foto);
    }

    /**
     * @param  list<int>  $ids
     */
    protected function markDisplayed(array $ids): void
    {
        if ($ids !== []) {
            FotoUpload::query()->whereIn('id', $ids)->update(['displayed_at' => now()]);
        }
    }

    protected function resetToStopped(): void
    {
        $this->forceFill([
            'state' => self::STATE_STOPPED,
            'foto_upload_id' => null,
            'page_ids' => null,
            'started_ms' => null,
            'paused_elapsed_ms' => null,
        ])->setRelation('foto', null);
    }

    /**
     * Moves the item to play right after the last item of the current page
     * that is still in the playlist, or to the top when nothing is on screen.
     */
    protected function moveAfterCurrent(FotoUpload $foto): void
    {
        $ids = array_values(array_filter($this->playlistIds(), fn (int $id) => $id !== $foto->id));
        $position = false;

        foreach (array_reverse($this->pageIds()) as $pageId) {
            $position = array_search($pageId, $ids, true);

            if ($position !== false) {
                break;
            }
        }

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
