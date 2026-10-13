<?php

namespace App\Models;

use App\Traits\ReferencesMediaFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FotoUpload extends Model
{
    use ReferencesMediaFiles;

    /** @var list<string> */
    public const MEDIA_COLUMNS = ['file_path', 'thumbnail_path', 'video_path'];

    /** Default seconds a photo holds the vidiwall before the queue advances; events can override it. */
    public const SCREEN_SECONDS = 4;

    /** Extra seconds the server waits before expiring an item the screen never acknowledged. */
    public const SCREEN_GRACE_SECONDS = 5;

    protected $fillable = ['event_id', 'visitor_id', 'file_path', 'thumbnail_path', 'video_path', 'media_type', 'video_duration', 'original_filename', 'file_size', 'mime_type', 'uploader_name', 'uploader_phone', 'uploader_session', 'status', 'on_screen', 'display_order', 'admin_note', 'approved_at', 'displayed_at', 'approved_by'];

    protected $casts = ['on_screen' => 'boolean', 'display_order' => 'integer', 'approved_at' => 'datetime', 'displayed_at' => 'datetime', 'video_duration' => 'float'];

    protected static function booted(): void
    {
        static::deleting(function (FotoUpload $foto) {
            VidiwallPlayer::query()->where('foto_upload_id', $foto->id)->first()?->skip($foto);
        });
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

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('media')->url($this->file_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->thumbnail_path ? Storage::disk('media')->url($this->thumbnail_path) : $this->file_url;
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->video_path ? Storage::disk('media')->url($this->video_path) : null;
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
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
     * Where this item stands with the vidiwall: live (at the player's playhead),
     * queued (not played yet), shown, or its moderation status when it is not approved.
     */
    public function screenState(): string
    {
        if ($this->isApproved() && $this->event?->onScreenFotoId() === $this->id) {
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
     * Skips the item when it is the one on the vidiwall.
     */
    public function removeFromScreen(): void
    {
        VidiwallPlayer::query()->where('foto_upload_id', $this->id)->first()?->skip($this);
    }
}
