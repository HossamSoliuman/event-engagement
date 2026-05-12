<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FotoUpload extends Model
{
    protected $fillable = ['event_id', 'file_path', 'thumbnail_path', 'video_path', 'media_type', 'video_duration', 'original_filename', 'file_size', 'mime_type', 'uploader_name', 'uploader_phone', 'uploader_session', 'status', 'on_screen', 'display_order', 'admin_note', 'approved_at', 'displayed_at', 'approved_by'];
    protected $casts = ['on_screen' => 'boolean', 'approved_at' => 'datetime', 'displayed_at' => 'datetime', 'video_duration' => 'float'];
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
    public function getThumbnailUrlAttribute(): string
    {
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : $this->file_url;
    }
    public function getVideoUrlAttribute(): ?string
    {
        return $this->video_path ? Storage::disk('public')->url($this->video_path) : null;
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
    public function approve(int $adminId): void
    {
        $this->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => $adminId]);
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
