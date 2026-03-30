<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'subtitle',
        'description',
        'qr_code_path',
        'logo_path',
        'sponsor_logo_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'is_active',
        'module_fotobomb',
        'module_lottery',
        'module_voting',
        'module_membership',
        'fotobomb_title',
        'lottery_title',
        'voting_title',
        'membership_title',
        'voting_options',
    ];

    protected $casts = [
        'voting_options'    => 'array',
        'is_active'         => 'boolean',
        'module_fotobomb'   => 'boolean',
        'module_lottery'    => 'boolean',
        'module_voting'     => 'boolean',
        'module_membership' => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function fotoUploads(): HasMany
    {
        return $this->hasMany(FotoUpload::class);
    }

    public function lotteryEntries(): HasMany
    {
        return $this->hasMany(LotteryEntry::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getGuestUrl(): string
    {
        return url("/e/{$this->slug}");
    }

    public function generateQrCode(): string
    {
        $url      = $this->getGuestUrl();
        $filename = "qrcodes/event-{$this->id}.svg";

        $svg = QrCode::format('svg')
            ->size(config('eventbomb.qr_size', 300))
            ->color(15, 15, 26)
            ->backgroundColor(255, 255, 255)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($url);

        Storage::disk('public')->put($filename, $svg);
        $this->update(['qr_code_path' => $filename]);

        return $filename;
    }

    public function getQrCodeUrlAttribute(): string
    {
        return $this->qr_code_path
            ? Storage::disk('public')->url($this->qr_code_path)
            : '';
    }

    public function getPendingFotosCount(): int
    {
        return $this->fotoUploads()->where('status', 'pending')->count();
    }

    public function getApprovedFotosOnScreen(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->fotoUploads()
            ->where('status', 'approved')
            ->where('on_screen', true)
            ->latest('displayed_at')
            ->get();
    }
}
