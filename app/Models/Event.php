<?php

namespace App\Models;

use App\Traits\ReferencesMediaFiles;
use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory, ReferencesMediaFiles, SoftDeletes;

    /** @var list<string> */
    public const MEDIA_COLUMNS = ['qr_code_path', 'logo_path', 'sponsor_logo_path', 'background_image_path', 'quiz_end_sponsor_logo_path', 'vidiwall_background_path'];

    /**
     * Guest page language modes: "switch" shows the EN/DE pill, "en" and "de" lock the page to one language.
     *
     * @var list<string>
     */
    public const GUEST_LANGUAGES = ['switch', 'en', 'de'];

    /**
     * What a lottery entry looks like once it is no longer a winner.
     *
     * @var array<string, mixed>
     */
    private const NOT_A_WINNER = ['is_winner' => false, 'won_at' => null, 'winner_rank' => null, 'prize' => null];

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
        'module_survey',
        'fotobomb_title',
        'lottery_title',
        'voting_title',
        'membership_title',
        'quiz_title',
        'fanclash_title',
        'survey_title',
        'fotobomb_desc',
        'lottery_desc',
        'voting_desc',
        'membership_desc',
        'quiz_desc',
        'fanclash_desc',
        'survey_desc',
        'quiz_winner_text',
        'quiz_end_sponsor_logo_path',
        'voting_options',
        'voting_closed',
        'lottery_drawn',
        'lottery_winner_id',
        'vidiwall_show_uploader',
        'vidiwall_slideshow_mode',
        'vidiwall_slideshow_interval',
        'vidiwall_photo_seconds',
        'vidiwall_photos_per_screen',
        'vidiwall_overlay_text',
        'vidiwall_frame_config',
        'vidiwall_background_path',
        'landing_style',
        'landing_wordmark',
        'landing_hero_title',
        'landing_hero_sub',
        'landing_design',
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
        'tile_survey_config',
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
        'module_survey' => 'boolean',
        'voting_closed' => 'boolean',
        'lottery_drawn' => 'boolean',
        'vidiwall_show_uploader' => 'boolean',
        'vidiwall_slideshow_mode' => 'boolean',
        'vidiwall_photo_seconds' => 'integer',
        'vidiwall_photos_per_screen' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'vidiwall_frame_config' => 'array',
        'landing_design' => 'array',
        'tile_fotobomb_config' => 'array',
        'tile_voting_config' => 'array',
        'tile_lottery_config' => 'array',
        'tile_membership_config' => 'array',
        'tile_quiz_config' => 'array',
        'tile_fanclash_config' => 'array',
        'tile_survey_config' => 'array',
        'lottery_extra_fields' => 'array',
        'membership_extra_fields' => 'array',
    ];

    public function fotoUploads()
    {
        return $this->hasMany(FotoUpload::class);
    }

    /**
     * Approved uploads in the order the vidiwall plays them.
     */
    public function playlist(): HasMany
    {
        return $this->fotoUploads()->inPlaylist();
    }

    public function vidiwallPlayer(): HasOne
    {
        return $this->hasOne(VidiwallPlayer::class);
    }

    /**
     * The event's vidiwall player, created stopped the first time it is needed.
     */
    public function player(): VidiwallPlayer
    {
        $player = $this->vidiwallPlayer ?? $this->vidiwallPlayer()->createOrFirst([]);
        $player->setRelation('event', $this);
        $this->setRelation('vidiwallPlayer', $player);

        return $player;
    }

    /**
     * Lets the player move on if its current item is done, then returns it.
     * Null when no one has used the player for this event yet (it is stopped).
     */
    public function syncPlayer(?int $finishedFotoId = null, ?int $finishedRevision = null): ?VidiwallPlayer
    {
        $player = $this->vidiwallPlayer?->setRelation('event', $this);
        $player?->sync($finishedFotoId, $finishedRevision);

        return $player;
    }

    public function onScreenFoto(): ?FotoUpload
    {
        return $this->vidiwallPlayer?->setRelation('event', $this)->screenFoto();
    }

    /**
     * Every item on the vidiwall right now: the page the player is showing.
     *
     * @return list<int>
     */
    public function onScreenFotoIds(): array
    {
        return $this->vidiwallPlayer?->pageIds() ?? [];
    }

    /**
     * How many photos the vidiwall shows at once, one of VidiwallPlayer::PHOTOS_PER_SCREEN.
     */
    public function photosPerScreen(): int
    {
        $count = (int) $this->vidiwall_photos_per_screen;

        return in_array($count, VidiwallPlayer::PHOTOS_PER_SCREEN, true) ? $count : 1;
    }

    public function lotteryEntries(): HasMany
    {
        return $this->hasMany(LotteryEntry::class);
    }

    /**
     * Winners in the order they were drawn.
     */
    public function lotteryWinners(): HasMany
    {
        return $this->lotteryEntries()->where('is_winner', true)->orderBy('winner_rank')->orderBy('won_at')->orderBy('id');
    }

    public function lotteryScreen(): HasOne
    {
        return $this->hasOne(LotteryScreen::class);
    }

    /**
     * The lottery's vidiwall settings and show, created with the defaults the
     * first time they are needed.
     */
    public function lotteryShow(): LotteryScreen
    {
        $screen = $this->lotteryScreen ?? $this->lotteryScreen()->createOrFirst([]);
        $screen->setRelation('event', $this);
        $this->setRelation('lotteryScreen', $screen);

        return $screen;
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function votingScreen(): HasOne
    {
        return $this->hasOne(VotingScreen::class);
    }

    /**
     * The vote's vidiwall settings and show, created with the defaults the
     * first time they are needed.
     */
    public function votingShow(): VotingScreen
    {
        $screen = $this->votingScreen ?? $this->votingScreen()->createOrFirst([]);
        $screen->setRelation('event', $this);
        $this->setRelation('votingScreen', $screen);

        return $screen;
    }

    /**
     * Every athlete in the vote with their votes, most votes first: the
     * athletes set up for the vote, with or without votes, plus any other name
     * still holding votes. Athletes level on votes share a rank and keep the
     * order they were set up in.
     *
     * @return list<array{key: string, name: string, position: string|null, votes: int, percent: int, rank: int}>
     */
    public function voteStandings(): array
    {
        $totals = $this->votes()->toBase()
            ->selectRaw('candidate_name, COUNT(*) as total')
            ->groupBy('candidate_name')
            ->pluck('total', 'candidate_name')
            ->map(fn (mixed $total) => (int) $total);

        $athletes = collect($this->voting_options ?? [])
            ->filter(fn (mixed $option) => is_array($option) && trim((string) ($option['name'] ?? '')) !== '')
            ->map(fn (array $option) => [
                'name' => trim($option['name']),
                'position' => trim((string) ($option['position'] ?? '')) ?: null,
            ])
            ->unique('name')
            ->values();

        $athletes = $athletes
            ->concat($totals->keys()->map(fn (mixed $name) => (string) $name)->diff($athletes->pluck('name'))->map(fn (string $name) => ['name' => $name, 'position' => null]))
            ->values();

        $votesCast = $totals->sum();

        $ranked = $athletes
            ->map(fn (array $athlete, int $order) => [...$athlete, 'votes' => $totals[$athlete['name']] ?? 0, 'order' => $order])
            ->sortBy([['votes', 'desc'], ['order', 'asc']])
            ->values();

        return $ranked->map(fn (array $athlete) => [
            'key' => substr(md5($athlete['name']), 0, 12),
            'name' => $athlete['name'],
            'position' => $athlete['position'],
            'votes' => $athlete['votes'],
            'percent' => $votesCast > 0 ? (int) round($athlete['votes'] / $votesCast * 100) : 0,
            'rank' => 1 + $ranked->where('votes', '>', $athlete['votes'])->count(),
        ])->all();
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function sessions()
    {
        return $this->hasMany(EventSession::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(EventPageView::class);
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

    public function fanClashParticipants(): HasMany
    {
        return $this->hasMany(FanClashParticipant::class);
    }

    public function activeFanClashRound(): ?FanClashRound
    {
        return $this->fanClashRounds()->where('status', 'active')->latest()->first();
    }

    public function surveyQuestions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('position')->orderBy('id');
    }

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Media-disk paths stored on the event row itself, including the ones
     * tucked inside the tile and vidiwall frame JSON configs.
     *
     * @return Collection<int, string>
     */
    public function mediaPaths(): Collection
    {
        $tileImages = collect(array_keys(self::landingModules()))
            ->map(fn (string $module) => $this->tileConfig($module)['image_path'] ?? null);

        return collect(self::MEDIA_COLUMNS)
            ->map(fn (string $column) => $this->getAttribute($column))
            ->push($this->frameConfig()['logo_path'])
            ->merge($tileImages)
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Every media-disk path owned by this event: its own files plus those of
     * its uploads, quiz questions and fan clash matchups/rounds.
     *
     * @return Collection<int, string>
     */
    public function ownedMediaPaths(): Collection
    {
        return $this->mediaPaths()
            ->merge(FotoUpload::allMediaPaths($this->fotoUploads()->getQuery()))
            ->merge(QuizQuestion::allMediaPaths($this->quizQuestions()->getQuery()))
            ->merge(FanClashMatchup::allMediaPaths($this->fanClashMatchups()->getQuery()))
            ->merge(FanClashRound::allMediaPaths($this->fanClashRounds()->getQuery()))
            ->unique()
            ->values();
    }

    /**
     * Remove every file this event owns from the media disk, then drop its
     * upload directory so stray thumbnails don't linger. Files a duplicated
     * event still points at are kept.
     */
    public function deleteMedia(): void
    {
        $disk = Storage::disk('media');
        $sharedPaths = self::allMediaPaths(self::withTrashed()->whereKeyNot($this->getKey()))->flip();

        $disk->delete($this->ownedMediaPaths()->reject(fn (string $path) => $sharedPaths->has($path))->values()->all());
        $disk->deleteDirectory("fotos/event-{$this->id}");
    }

    public function getGuestUrl(): string
    {
        return url("/e/{$this->slug}");
    }

    public function getQrCodeUrlAttribute(): string
    {
        return $this->qr_code_path ? Storage::disk('media')->url($this->qr_code_path) : '';
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('media')->url($this->logo_path) : null;
    }

    public function getSponsorLogoUrlAttribute(): ?string
    {
        return $this->sponsor_logo_path ? Storage::disk('media')->url($this->sponsor_logo_path) : null;
    }

    public function getQuizEndSponsorLogoUrlAttribute(): ?string
    {
        return $this->quiz_end_sponsor_logo_path ? Storage::disk('media')->url($this->quiz_end_sponsor_logo_path) : null;
    }

    public function getBackgroundImageUrlAttribute(): ?string
    {
        return $this->background_image_path ? Storage::disk('media')->url($this->background_image_path) : null;
    }

    public function getVidiwallBackgroundUrlAttribute(): ?string
    {
        return $this->vidiwall_background_path ? Storage::disk('media')->url($this->vidiwall_background_path) : null;
    }

    /**
     * The sponsor artwork replaces the frame on the vidiwall when set.
     */
    public function hasVidiwallBackground(): bool
    {
        return ! empty($this->vidiwall_background_path);
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

        Storage::disk('media')->put($filename, $svg);
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

    /**
     * Uploads per moderation status, for the Foto Queue tabs.
     *
     * @return array{pending: int, approved: int, rejected: int}
     */
    public function fotoStatusCounts(): array
    {
        $totals = $this->fotoUploads()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($totals['pending'] ?? 0),
            'approved' => (int) ($totals['approved'] ?? 0),
            'rejected' => (int) ($totals['rejected'] ?? 0),
        ];
    }

    public function getVoteTallies(): array
    {
        return $this->votes()->selectRaw('candidate_name, candidate_slug, COUNT(*) as total')->groupBy('candidate_name', 'candidate_slug')->orderByDesc('total')->get()->toArray();
    }

    /**
     * The winner drawn most recently.
     */
    public function latestLotteryWinner(): ?LotteryEntry
    {
        return $this->lotteryEntries()->where('is_winner', true)->latest('won_at')->orderByDesc('winner_rank')->first();
    }

    /**
     * Draws one more winner from the entries that have not won yet; earlier
     * winners keep their places. With `$asRank`, the draw only goes ahead when
     * it would be that winner number, so a form sent twice draws once. Null
     * when there is no one left to draw or that number is already drawn.
     */
    public function drawLotteryWinner(?string $prize = null, ?int $asRank = null): ?LotteryEntry
    {
        return $this->withLotteryLock(function () use ($prize, $asRank): ?LotteryEntry {
            $rank = (int) $this->lotteryEntries()->where('is_winner', true)->max('winner_rank') + 1;
            $winner = $asRank === null || $asRank === $rank ? $this->pickLotteryEntry() : null;

            if (! $winner) {
                return null;
            }

            $winner->update([
                'is_winner' => true,
                'won_at' => now(),
                'winner_rank' => $rank,
                'prize' => $prize,
            ]);
            $this->syncLotteryDrawn();

            return $winner;
        });
    }

    /**
     * Draws a replacement for a winner, say one who did not claim the prize.
     * The new winner takes over the rank and prize and the old one goes back
     * into the draw as a regular entry. Null when there is no one left to draw.
     */
    public function redrawLotteryWinner(LotteryEntry $winner): ?LotteryEntry
    {
        return $this->withLotteryLock(function () use ($winner): ?LotteryEntry {
            $winner->refresh();
            $replacement = $winner->is_winner ? $this->pickLotteryEntry() : null;

            if (! $replacement) {
                return null;
            }

            $replacement->update([
                'is_winner' => true,
                'won_at' => now(),
                'winner_rank' => $winner->winner_rank,
                'prize' => $winner->prize,
            ]);
            $winner->update(self::NOT_A_WINNER);
            $this->syncLotteryDrawn();

            return $replacement;
        });
    }

    /**
     * Puts a winner back into the draw as a regular entry; the winners after
     * them move up a place.
     */
    public function releaseLotteryWinner(LotteryEntry $winner): void
    {
        $this->withLotteryLock(function () use ($winner): void {
            $winner->update(self::NOT_A_WINNER);
            $this->renumberLotteryWinners();
        });
    }

    public function resetLottery(): void
    {
        $this->lotteryEntries()->update(self::NOT_A_WINNER);
        $this->update(['lottery_drawn' => false, 'lottery_winner_id' => null]);
        $this->lotteryScreen?->setRelation('event', $this)->hide();
    }

    /**
     * Closes the gap a removed winner leaves, so winners stay numbered 1, 2, 3
     * in the order they were drawn.
     */
    public function renumberLotteryWinners(): void
    {
        $this->lotteryWinners()->get(['id', 'winner_rank'])->values()->each(function (LotteryEntry $winner, int $index): void {
            if ($winner->winner_rank !== $index + 1) {
                $winner->update(['winner_rank' => $index + 1]);
            }
        });

        $this->syncLotteryDrawn();
    }

    protected function syncLotteryDrawn(): void
    {
        $latest = $this->latestLotteryWinner();

        $this->update(['lottery_drawn' => $latest !== null, 'lottery_winner_id' => $latest?->id]);
    }

    /**
     * A random entry that has not won yet, picked with a cryptographically
     * secure random offset.
     */
    protected function pickLotteryEntry(): ?LotteryEntry
    {
        $pool = $this->lotteryEntries()->where('is_winner', false);
        $count = (clone $pool)->count();

        return $count ? $pool->orderBy('id')->skip(random_int(0, $count - 1))->first() : null;
    }

    /**
     * Runs a change to the winners one at a time per event, so two operators or
     * a double click cannot pick the same entry or hand out the same rank. The
     * lock is claimed with a write first, as SQLite has no row locks.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $change
     * @return TReturn
     */
    protected function withLotteryLock(Closure $change): mixed
    {
        return DB::transaction(function () use ($change): mixed {
            static::query()->whereKey($this->id)->toBase()->update(['lottery_drawn' => DB::raw('lottery_drawn')]);

            return $change();
        });
    }

    public function getQueuedFotosCount(): int
    {
        return $this->fotoUploads()->queuedForScreen()->count();
    }

    /**
     * Snapshot of the vidiwall for the moderation pages, which poll it to stay
     * in sync with the screen. `$fotoIds` are the cards currently rendered.
     *
     * @param  array<int, int>  $fotoIds
     * @return array{live: array<string, mixed>|null, queued: int, counts: array<string, int>, items: array<int, array<string, mixed>>}
     */
    public function screenQueueStatus(array $fotoIds = []): array
    {
        $player = $this->vidiwallPlayer;
        $live = $this->onScreenFoto();

        $items = $fotoIds === [] ? collect() : $this->fotoUploads()->whereIn('id', $fotoIds)->get()->each->setRelation('event', $this);

        return [
            'live' => $live ? [
                'id' => $live->id,
                'uploader' => $live->uploader_name ?? 'Anonymous',
                'is_video' => $live->isVideo(),
                'video_duration' => $live->video_duration ? round((float) $live->video_duration, 1) : null,
                'thumbnail_url' => $live->thumbnail_url,
                'state' => $player->state,
                'revision' => $player->revision,
                'screen_seconds' => $live->screenSeconds(),
                'slot_ms' => $live->screenSlotMs(),
                'elapsed_ms' => $player->elapsedMs(),
            ] : null,
            'player_state' => $player?->state ?? VidiwallPlayer::STATE_STOPPED,
            'queued' => $this->getQueuedFotosCount(),
            'counts' => $this->fotoStatusCounts(),
            'items' => $items->map(fn (FotoUpload $foto) => [
                'id' => $foto->id,
                'state' => $foto->screenState(),
                'shown_human' => $foto->displayed_at?->diffForHumans(),
                'shown_ms' => $foto->displayed_at?->getTimestampMs(),
            ])->values()->all(),
        ];
    }

    /**
     * The landing page modules in the order they are rendered, with their fallback icon.
     *
     * @return array<string, string>
     */
    public static function landingModules(): array
    {
        return [
            'fotobomb' => 'camera',
            'voting' => 'trophy',
            'lottery' => 'ticket',
            'membership' => 'crown',
            'quiz' => 'brain',
            'fanclash' => 'swords',
            'survey' => 'clipboard-list',
        ];
    }

    /**
     * Page-level styling knobs for the landing page, editable from the admin "Landing Design" tab.
     *
     * @return array<string, int|float|string|bool>
     */
    public static function landingDesignDefaults(): array
    {
        return [
            'logo_size' => 46,
            'logo_position' => 'inline',
            'wordmark_show' => true,
            'wordmark_size' => 17,
            'wordmark_spacing' => 22,
            'hero_title_size' => 18,
            'hero_sub_size' => 13,
            'hero_show' => true,
            'card_gap' => 14,
            'card_radius' => 18,
            'card_ratio' => 1.42,
            'card_columns' => 2,
            'page_padding' => 18,
            'section_spacing' => 22,
            'hashtag_show' => true,
            'hashtag_size' => 22,
            'footer_size' => 11,
            'watermark_opacity' => 5,
            'card_shadow' => 30,
            'bg_image_show' => true,
            'bg_fit' => 'cover',
            'bg_position' => 'center',
            'bg_overlay' => 45,
            'bg_blur' => 0,
            'guest_language' => 'switch',
        ];
    }

    /**
     * @return array<string, int|float|string|bool>
     */
    public function landingDesign(): array
    {
        $config = $this->landing_design ?? [];
        if (! is_array($config)) {
            $config = [];
        }

        return array_merge(self::landingDesignDefaults(), $config);
    }

    /**
     * The language the guest page is locked to, or null when guests can switch between EN and DE.
     */
    public function lockedGuestLanguage(): ?string
    {
        $language = $this->landingDesign()['guest_language'];

        return in_array($language, ['en', 'de'], true) ? $language : null;
    }

    /**
     * @return array<string, int|float|string|bool|null>
     */
    public static function tileConfigDefaults(): array
    {
        return [
            'label' => '',
            'sublabel' => '',
            'bg_color' => '',
            'image_path' => null,
            'link_url' => '',
            'link_external' => false,
            // Per-tile styling
            'logo_size' => 100,
            'logo_fit' => 'contain',
            'media_padding' => 14,
            'label_size' => 11,
            'sublabel_size' => 9,
            'text_color' => '',
            'show_rule' => true,
        ];
    }

    public function tileConfig(string $module): array
    {
        $field = "tile_{$module}_config";
        $config = $this->$field ?? [];
        if (! is_array($config)) {
            $config = [];
        }

        return array_merge(self::tileConfigDefaults(), $config);
    }

    public function getTileImageUrl(string $module): ?string
    {
        $config = $this->tileConfig($module);

        return ! empty($config['image_path'])
            ? Storage::disk('media')->url($config['image_path'])
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
            ? Storage::disk('media')->url($config['logo_path'])
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
