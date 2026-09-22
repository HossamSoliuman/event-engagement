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

    /** Seconds a photo holds the vidiwall before the queue advances. */
    public const SCREEN_SECONDS = 4;

    /** Extra seconds the server waits before expiring an item the screen never acknowledged. */
    public const SCREEN_GRACE_SECONDS = 5;

    protected $fillable = ['event_id', 'visitor_id', 'file_path', 'thumbnail_path', 'video_path', 'media_type', 'video_duration', 'original_filename', 'file_size', 'mime_type', 'uploader_name', 'uploader_phone', 'uploader_session', 'status', 'on_screen', 'display_order', 'admin_note', 'approved_at', 'displayed_at', 'approved_by'];

    protected $casts = ['on_screen' => 'boolean', 'approved_at' => 'datetime', 'displayed_at' => 'datetime', 'video_duration' => 'float'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approved items that have not been shown on the vidiwall yet, in approval order.
     */
    public function scopeQueuedForScreen(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->whereNull('displayed_at')
            ->orderBy('approved_at')
            ->orderBy('id');
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
     * Where this item stands with the vidiwall: live, queued, shown, or its
     * moderation status when it is not approved.
     */
    public function screenState(): string
    {
        if ($this->isLiveOnScreen()) {
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
     * How long this item holds the screen: a fixed slot for photos, the clip length for videos.
     */
    public function screenSeconds(): float
    {
        if ($this->isVideo() && $this->video_duration) {
            return max((float) $this->video_duration, self::SCREEN_SECONDS);
        }

        return self::SCREEN_SECONDS;
    }

    public function screenSlotMs(): int
    {
        return (int) round($this->screenSeconds() * 1000);
    }

    /**
     * True while the item is on screen and its slot (plus grace for an unacknowledged screen) has not run out.
     * `displayed_at` is second-precise, so the screen times the slot itself; this is the server-side fallback.
     */
    public function isLiveOnScreen(): bool
    {
        if (! $this->on_screen || ! $this->displayed_at) {
            return false;
        }

        $expiresMs = $this->displayed_at->getTimestampMs() + (int) round(($this->screenSeconds() + self::SCREEN_GRACE_SECONDS) * 1000);

        return $expiresMs > now()->getTimestampMs();
    }

    /**
     * Approving (or restoring) an item drops it into the vidiwall queue.
     */
    public function approve(int $adminId): void
    {
        $attributes = ['status' => 'approved', 'approved_at' => now(), 'approved_by' => $adminId];

        if (! $this->isApproved()) {
            $attributes['on_screen'] = false;
            $attributes['displayed_at'] = null;
        }

        $this->update($attributes);
    }

    public function reject(?string $note = null): void
    {
        $this->update(['status' => 'rejected', 'on_screen' => false, 'admin_note' => $note]);
    }

    public function pushToScreen(): void
    {
        static::where('event_id', $this->event_id)->where('id', '!=', $this->id)->update(['on_screen' => false]);
        $this->update(['on_screen' => true, 'displayed_at' => now()]);
    }

    public function removeFromScreen(): void
    {
        $this->update(['on_screen' => false]);
    }
}
