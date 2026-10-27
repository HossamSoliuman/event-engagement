<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * Media URLs of a guest upload, shared by Foto Queue uploads and archived ones.
 */
trait HasFotoMedia
{
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

    /**
     * What a preview plays or shows: the video for videos, the full photo otherwise.
     */
    public function getMediaUrlAttribute(): string
    {
        return $this->isVideo() ? ($this->video_url ?? $this->file_url) : $this->file_url;
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }
}
