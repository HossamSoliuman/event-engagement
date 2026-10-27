<?php

namespace App\Models;

use App\Traits\HasFotoMedia;
use App\Traits\ReferencesMediaFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An upload moved out of the Foto Queue by "Archive all". It keeps its
 * original id, columns and moderation status.
 */
class ArchivedFotoUpload extends Model
{
    use HasFactory, HasFotoMedia, ReferencesMediaFiles;

    /** @var list<string> */
    public const MEDIA_COLUMNS = FotoUpload::MEDIA_COLUMNS;

    /**
     * The foto_uploads columns copied into the archive as they are.
     *
     * @var list<string>
     */
    public const COPIED_COLUMNS = ['id', 'event_id', 'visitor_id', 'file_path', 'thumbnail_path', 'video_path', 'media_type', 'video_duration', 'original_filename', 'file_size', 'mime_type', 'uploader_name', 'uploader_phone', 'uploader_session', 'status', 'on_screen', 'display_order', 'admin_note', 'approved_at', 'displayed_at', 'approved_by', 'created_at', 'updated_at'];

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = ['on_screen' => 'boolean', 'approved_at' => 'datetime', 'displayed_at' => 'datetime', 'archived_at' => 'datetime', 'video_duration' => 'float'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
