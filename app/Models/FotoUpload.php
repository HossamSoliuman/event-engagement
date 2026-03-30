<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FotoUpload extends Model
{
    protected $fillable = [
        'event_id', 'file_path', 'thumbnail_path',
        'uploader_name', 'uploader_phone',
        'status', 'on_screen', 'approved_at', 'displayed_at', 'approved_by',
    ];

    protected $casts = [
        'on_screen'   => 'boolean',
        'approved_at' => 'datetime',
        'displayed_at'=> 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->thumbnail_path
            ? Storage::disk('public')->url($this->thumbnail_path)
            : $this->file_url;
    }

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
    public function isRejected(): bool  { return $this->status === 'rejected'; }

    public function approve(int $adminId): void
    {
        $this->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => $adminId,
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected', 'on_screen' => false]);
    }

    public function pushToScreen(): void
    {
        // Clear existing on-screen photos for this event (optional: allow multiple)
        static::where('event_id', $this->event_id)
            ->where('id', '!=', $this->id)
            ->update(['on_screen' => false]);

        $this->update(['on_screen' => true, 'displayed_at' => now()]);
    }

    public function removeFromScreen(): void
    {
        $this->update(['on_screen' => false]);
    }
}