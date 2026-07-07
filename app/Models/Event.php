<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'module_quiz',
        'module_fanclash',
        'fotobomb_title',
        'lottery_title',
        'voting_title',
        'membership_title',
        'quiz_title',
        'fanclash_title',
        'fotobomb_desc',
        'lottery_desc',
        'voting_desc',
        'membership_desc',
        'quiz_desc',
        'fanclash_desc',
        'quiz_winner_text',
        'quiz_end_sponsor_logo_path',
        'voting_options',
        'voting_closed',
        'lottery_drawn',
        'lottery_winner_id',
        'vidiwall_show_uploader',
        'vidiwall_slideshow_mode',
        'vidiwall_slideshow_interval',
        'vidiwall_overlay_text',
        'vidiwall_frame_config',
        'landing_style',
        'landing_wordmark',
        'landing_hero_title',
        'landing_hero_sub',
        'privacy_policy_text',
        'starts_at',
        'ends_at',
        'privacy_policy_text',
        'privacy_policy_url',
        'font_heading',
        'font_body',
        'tile_fotobomb_config',
        'tile_voting_config',
        'tile_lottery_config',
        'tile_membership_config',
        'tile_quiz_config',
        'tile_fanclash_config',
        'lottery_extra_fields',
        'membership_extra_fields',
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
        'module_quiz' => 'boolean',
        'module_fanclash' => 'boolean',
        'voting_closed' => 'boolean',
        'lottery_drawn' => 'boolean',
        'vidiwall_show_uploader' => 'boolean',
        'vidiwall_slideshow_mode' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'vidiwall_frame_config' => 'array',
        'tile_fotobomb_config' => 'array',
        'tile_voting_config' => 'array',
        'tile_lottery_config' => 'array',
        'tile_membership_config' => 'array',
        'tile_quiz_config' => 'array',
        'tile_fanclash_config' => 'array',
        'lottery_extra_fields' => 'array',
        'membership_extra_fields' => 'array',
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

    public function moderators()
    {
        return $this->belongsToMany(User::class, 'event_moderators')->withTimestamps();
    }

    public function hasModerator(User $user): bool
    {
        return $this->moderators()->where('user_id', $user->id)->exists();
    }

    public function activityLog()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function quizQuestions()
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function quizRounds()
    {
        return $this->hasMany(QuizRound::class);
    }

    public function quizAnswers()
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function activeQuizRound(): ?QuizRound
    {
        return $this->quizRounds()->where('status', 'active')->latest()->first();
    }

    public function fanClashMatchups(): HasMany
    {
        return $this->hasMany(FanClashMatchup::class);
    }

    public function fanClashRounds(): HasMany
    {
        return $this->hasMany(FanClashRound::class);
    }

    public function activeFanClashRound(): ?FanClashRound
    {
        return $this->fanClashRounds()->where('status', 'active')->latest()->first();
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

    public function getQuizEndSponsorLogoUrlAttribute(): ?string
    {
        return $this->quiz_end_sponsor_logo_path ? Storage::disk('public')->url($this->quiz_end_sponsor_logo_path) : null;
    }

    public function generateQrCode(): string
    {
        $filename = "qrcodes/event-{$this->id}.svg";
        $url = $this->getGuestUrl();

        // Use Google Charts QR API - no server-side rendering, no memory issues
        $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?'
            .http_build_query([
                'data' => $url,
                'size' => '400x400',
                'format' => 'svg',
                'ecc' => 'H',
                'margin' => '1',
            ]);

        $svg = file_get_contents($apiUrl);

        if ($svg === false || strlen($svg) < 100) {
            throw new \RuntimeException('Failed to fetch QR code from API');
        }

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
        if ($this->lottery_drawn) {
            return $this->lotteryEntries()->where('is_winner', true)->first();
        }
        $winner = $this->lotteryEntries()->inRandomOrder()->first();
        if (! $winner) {
            return null;
        }
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

    public function tileConfig(string $module): array
    {
        $field = "tile_{$module}_config";
        $defaults = [
            'label' => '',
            'sublabel' => '',
            'bg_color' => '',
            'image_path' => null,
            'link_url' => '',
            'link_external' => false,
        ];
        $config = $this->$field ?? [];
        if (! is_array($config)) {
            $config = [];
        }

        return array_merge($defaults, $config);
    }

    public function getTileImageUrl(string $module): ?string
    {
        $config = $this->tileConfig($module);

        return ! empty($config['image_path'])
            ? Storage::disk('public')->url($config['image_path'])
            : null;
    }

    /**
     * @return array{enabled: bool, frame_color: string, text_color: string, top_text: string, bottom_text: string, side_text: string, logo_path: ?string}
     */
    public function frameConfig(): array
    {
        $defaults = [
            'enabled' => false,
            'frame_color' => '',
            'text_color' => '#ffffff',
            'top_text' => '',
            'bottom_text' => '',
            'side_text' => '',
            'logo_path' => null,
        ];
        $config = $this->vidiwall_frame_config ?? [];
        if (! is_array($config)) {
            $config = [];
        }

        return array_merge($defaults, $config);
    }

    public function getFrameLogoUrlAttribute(): ?string
    {
        $config = $this->frameConfig();

        return ! empty($config['logo_path'])
            ? Storage::disk('public')->url($config['logo_path'])
            : null;
    }

    public function isLightColor(?string $hex): bool
    {
        $hex = ltrim((string) $hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return false;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return ((($r * 299) + ($g * 587) + ($b * 114)) / 1000) > 150;
    }

    public function readableInk(?string $hex, string $light = '#111827', string $dark = '#ffffff'): string
    {
        return $this->isLightColor($hex) ? $light : $dark;
    }

    public function hasLightBackground(): bool
    {
        return $this->isLightColor($this->secondary_color);
    }
}
