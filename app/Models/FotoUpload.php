<?php

namespace App\Models;

use App\Traits\HasFotoMedia;
use App\Traits\ReferencesMediaFiles;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FotoUpload extends Model
{
    use HasFotoMedia, ReferencesMediaFiles;

    /** @var list<string> */
    public const MEDIA_COLUMNS = ['file_path', 'thumbnail_path', 'video_path'];

    /** Default seconds a photo holds the vidiwall before the queue advances; events can override it. */
    public const SCREEN_SECONDS = 4;

    /** Extra seconds the server waits before expiring an item the screen never acknowledged. */
    public const SCREEN_GRACE_SECONDS = 5;

    /** Longest guest video we accept, as shown to guests. */
    public const MAX_VIDEO_SECONDS = 6;

    protected $fillable = ['event_id', 'visitor_id', 'file_path', 'thumbnail_path', 'video_path', 'media_type', 'video_duration', 'original_filename', 'file_size', 'mime_type', 'uploader_name', 'uploader_phone', 'uploader_session', 'status', 'on_screen', 'display_order', 'admin_note', 'approved_at', 'displayed_at', 'approved_by'];

    protected $casts = ['on_screen' => 'boolean', 'display_order' => 'integer', 'approved_at' => 'datetime', 'displayed_at' => 'datetime', 'video_duration' => 'float', 'archived_at' => 'datetime'];

    protected static function booted(): void
    {
        static::deleting(fn (FotoUpload $foto) => $foto->removeFromScreen());
    }

    /**
     * Foto Queue uploads and archived ones as one read-only query, so downloads,
     * exports and statistics cover every day of an event. `$constrain` narrows
     * both tables before they are combined; archived rows carry `archived_at`.
     *
     * @param  (Closure(Builder): mixed)|null  $constrain
     */
    public static function withArchived(?Closure $constrain = null): Builder
    {
        $current = static::query()->select(ArchivedFotoUpload::COPIED_COLUMNS)->selectRaw('NULL as archived_at');
        $archived = ArchivedFotoUpload::query()->select([...ArchivedFotoUpload::COPIED_COLUMNS, 'archived_at']);

        if ($constrain) {
            $constrain($current);
            $constrain($archived);
        }

        return static::query()->fromSub($current->toBase()->unionAll($archived->toBase()), (new static)->getTable());
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approved items that have not played on the vidiwall yet.
     */
    public function scopeQueuedForScreen(Builder $query): Builder
    {
        return $query->where('status', 'approved')->whereNull('displayed_at');
    }

    /**
     * Approved items in the order the vidiwall plays them.
     */
    public function scopeInPlaylist(Builder $query): Builder
    {
        return $query->where('status', 'approved')->orderBy('display_order')->orderBy('id');
    }

    public function scopeAfterInPlaylist(Builder $query, FotoUpload $foto): Builder
    {
        return $query->where(fn (Builder $query) => $query->where('display_order', '>', $foto->display_order)
            ->orWhere(fn (Builder $query) => $query->where('display_order', $foto->display_order)->where('id', '>', $foto->id)));
    }

    public function scopeBeforeInPlaylist(Builder $query, FotoUpload $foto): Builder
    {
        return $query->where(fn (Builder $query) => $query->where('display_order', '<', $foto->display_order)
            ->orWhere(fn (Builder $query) => $query->where('display_order', $foto->display_order)->where('id', '<', $foto->id)))
            ->reorder()
            ->orderByDesc('display_order')
            ->orderByDesc('id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isQueuedForScreen(): bool
    {
        return $this->isApproved() && $this->displayed_at === null;
    }

    /**
     * Where this item stands with the vidiwall: live (on the page the player is
     * showing), queued (not played yet), shown, or its moderation status when it
     * is not approved.
     */
    public function screenState(): string
    {
        if ($this->isApproved() && in_array($this->id, $this->event?->onScreenFotoIds() ?? [], true)) {
            return 'live';
        }
        if ($this->isQueuedForScreen()) {
            return 'queued';
        }
        if ($this->isApproved()) {
            return 'shown';
        }

        return $this->status;
    }

    /**
     * How long this item holds the screen: the event's photo slot for photos, the clip length for videos.
     */
    public function screenSeconds(): float
    {
        if ($this->isVideo() && $this->video_duration) {
            return (float) $this->video_duration;
        }

        return (float) ($this->event?->vidiwall_photo_seconds ?: self::SCREEN_SECONDS);
    }

    /**
     * The duration actually enforced: phones record a "6 s" clip as 6.0x s, so allow half a second over the shown limit.
     */
    public static function maxVideoSecondsAccepted(): float
    {
        return self::MAX_VIDEO_SECONDS + 0.5;
    }

    public function screenSlotMs(): int
    {
        return (int) round($this->screenSeconds() * 1000);
    }

    /**
     * Approving (or restoring) an item adds it to the end of the vidiwall playlist, unplayed.
     */
    public function approve(int $adminId): void
    {
        $attributes = ['status' => 'approved', 'approved_at' => now(), 'approved_by' => $adminId];

        if (! $this->isApproved()) {
            $attributes['displayed_at'] = null;
            $attributes['display_order'] = (int) static::query()
                ->where('event_id', $this->event_id)
                ->where('status', 'approved')
                ->max('display_order') + 1;
        }

        $this->update($attributes);
    }

    public function reject(?string $note = null): void
    {
        $this->update(['status' => 'rejected', 'admin_note' => $note]);

        $this->removeFromScreen();
    }

    /**
     * "Push Live": plays the item right now, then the playlist carries on.
     */
    public function pushToScreen(): void
    {
        $this->event->player()->playNow($this);
    }

    /**
     * Takes the item off the vidiwall when it is on the page there.
     */
    public function removeFromScreen(): void
    {
        $player = VidiwallPlayer::query()->where('event_id', $this->event_id)->first();

        if ($player && in_array($this->id, $player->pageIds(), true)) {
            $player->skip($this);
        }
    }
}
