<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'slug',
        'subtitle',
        'description',
        'qr_code_path',
        'logo_path',
        'sponsor_logo_path',
        'background_image_path',
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
        'fotobomb_desc',
        'lottery_desc',
        'voting_desc',
        'membership_desc',
        'voting_options',
        'voting_closed',
        'lottery_drawn',
        'lottery_winner_id',
        'vidiwall_show_uploader',
        'vidiwall_slideshow_mode',
        'vidiwall_slideshow_interval',
        'vidiwall_overlay_text',
        'starts_at',
        'ends_at',
    ];
    protected $casts = [
        'voting_options' => 'array',
        'is_active' => 'boolean',
        'module_fotobomb' => 'boolean',
        'module_lottery' => 'boolean',
        'module_voting' => 'boolean',
        'module_membership' => 'boolean',
        'voting_closed' => 'boolean',
        'lottery_drawn' => 'boolean',
        'vidiwall_show_uploader' => 'boolean',
        'vidiwall_slideshow_mode' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
    public function fotoUploads()
    {
        return $this->hasMany(FotoUpload::class);
    }
    public function lotteryEntries()
    {
        return $this->hasMany(LotteryEntry::class);
    }
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }
    public function sessions()
    {
        return $this->hasMany(EventSession::class);
    }
    public function activityLog()
    {
        return $this->hasMany(ActivityLog::class);
    }
    public function getGuestUrl(): string
    {
        return url("/e/{$this->slug}");
    }
    public function getQrCodeUrlAttribute(): string
    {
        return $this->qr_code_path ? Storage::disk('public')->url($this->qr_code_path) : '';
    }
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
    public function getSponsorLogoUrlAttribute(): ?string
    {
        return $this->sponsor_logo_path ? Storage::disk('public')->url($this->sponsor_logo_path) : null;
    }
    public function generateQrCode(): string
    {
        $filename = "qrcodes/event-{$this->id}.svg";
        $svg = QrCode::format('svg')->size(400)->margin(1)->errorCorrection('H')->generate($this->getGuestUrl());
        Storage::disk('public')->put($filename, $svg);
        $this->update(['qr_code_path' => $filename]);
        return $filename;
    }
    public function getVotingOptionsAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        if (is_string($decoded)) {
            return json_decode($decoded, true) ?? [];
        }

        return $decoded ?? [];
    }
    public function getPendingFotosCount(): int
    {
        return $this->fotoUploads()->where('status', 'pending')->count();
    }
    public function getVoteTallies(): array
    {
        return $this->votes()->selectRaw('candidate_name, candidate_slug, COUNT(*) as total')->groupBy('candidate_name', 'candidate_slug')->orderByDesc('total')->get()->toArray();
    }
    public function drawLotteryWinner(): ?LotteryEntry
    {
        if ($this->lottery_drawn) return $this->lotteryEntries()->where('is_winner', true)->first();
        $winner = $this->lotteryEntries()->inRandomOrder()->first();
        if (!$winner) return null;
        $winner->update(['is_winner' => true, 'won_at' => now()]);
        $this->update(['lottery_drawn' => true, 'lottery_winner_id' => $winner->id]);
        return $winner;
    }
    public function resetLottery(): void
    {
        $this->lotteryEntries()->update(['is_winner' => false, 'won_at' => null]);
        $this->update(['lottery_drawn' => false, 'lottery_winner_id' => null]);
    }
    public function getOnScreenFotos()
    {
        return $this->fotoUploads()->where('status', 'approved')->where('on_screen', true)->orderByDesc('displayed_at')->get();
    }
}
