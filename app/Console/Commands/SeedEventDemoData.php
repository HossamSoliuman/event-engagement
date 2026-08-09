<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventPageView;
use App\Models\FotoUpload;
use App\Models\LotteryEntry;
use App\Models\Membership;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\QuizRound;
use App\Models\Vote;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fills an event with a believable night of traffic so the statistics screens
 * can be judged with real-looking shapes instead of empty charts.
 */
class SeedEventDemoData extends Command
{
    protected $signature = 'event:demo-data
                            {event : Event id or slug}
                            {--visitors=240 : How many distinct people show up}
                            {--hours=6 : How long the event runs}';

    protected $description = 'Seed one event with a realistic evening of visits and module activity';

    /** @var array<int, array{device: string, os: string, browser: string, weight: int}> */
    private const CLIENTS = [
        ['device' => 'mobile', 'os' => 'iOS', 'browser' => 'Safari', 'weight' => 34],
        ['device' => 'mobile', 'os' => 'Android', 'browser' => 'Chrome', 'weight' => 26],
        ['device' => 'mobile', 'os' => 'Android', 'browser' => 'Samsung Internet', 'weight' => 9],
        ['device' => 'mobile', 'os' => 'iOS', 'browser' => 'Chrome', 'weight' => 7],
        ['device' => 'tablet', 'os' => 'iOS', 'browser' => 'Safari', 'weight' => 7],
        ['device' => 'desktop', 'os' => 'Windows', 'browser' => 'Chrome', 'weight' => 8],
        ['device' => 'desktop', 'os' => 'Windows', 'browser' => 'Edge', 'weight' => 4],
        ['device' => 'desktop', 'os' => 'macOS', 'browser' => 'Safari', 'weight' => 3],
        ['device' => 'mobile', 'os' => 'Android', 'browser' => 'Firefox', 'weight' => 2],
    ];

    private const REFERRERS = [
        null,
        null,
        null,
        'https://www.instagram.com/',
        'https://l.facebook.com/',
        'https://www.google.com/',
    ];

    public function handle(): int
    {
        $event = $this->resolveEvent();

        if (! $event) {
            $this->components->error('No event matches "'.$this->argument('event').'".');

            return self::FAILURE;
        }

        $visitorCount = max(1, (int) $this->option('visitors'));
        $hours = max(1, (int) $this->option('hours'));

        [$startsAt, $endsAt] = $this->scheduleEvent($event, $hours);

        $created = [
            'page views' => 0,
            'visitors' => $visitorCount,
            'fotos' => 0,
            'votes' => 0,
            'lottery entries' => 0,
            'memberships' => 0,
            'quiz answers' => 0,
        ];

        $candidates = $this->candidates($event);
        $question = QuizQuestion::query()->where('event_id', $event->id)->where('is_active', true)->first();
        $round = QuizRound::query()->where('event_id', $event->id)->latest('id')->first();
        $fotoFiles = $this->fotoFiles($event);

        $this->components->info("Seeding {$visitorCount} visitors into \"{$event->name}\" ({$startsAt->format('d M H:i')} — {$endsAt->format('d M H:i')})");

        $bar = $this->output->createProgressBar($visitorCount);
        $bar->start();

        /** Timestamps are guarded on these models, and a seed needs to place rows in the past. */
        Model::unguard();

        try {
            DB::transaction(function () use ($event, $visitorCount, $startsAt, $endsAt, $candidates, $question, $round, $fotoFiles, $bar, &$created) {
                for ($person = 0; $person < $visitorCount; $person++) {
                    $visitorId = Str::random(32);
                    $client = $this->weightedClient();
                    $arrival = $this->arrivalTime($startsAt, $endsAt);

                    $created['page views'] += $this->recordVisit($event, $visitorId, $client, $arrival, $endsAt);

                    if ($event->module_fotobomb && $fotoFiles->isNotEmpty() && $this->chance(16)) {
                        $this->recordFoto($event, $visitorId, $this->momentAfter($arrival, $endsAt), $fotoFiles->random());
                        $created['fotos']++;
                    }

                    if ($event->module_voting && $candidates->isNotEmpty() && $this->chance(46)) {
                        $this->recordVote($event, $visitorId, $this->momentAfter($arrival, $endsAt), $candidates->random());
                        $created['votes']++;
                    }

                    if ($event->module_lottery && $this->chance(24)) {
                        $this->recordLotteryEntry($event, $visitorId, $this->momentAfter($arrival, $endsAt));
                        $created['lottery entries']++;
                    }

                    if ($event->module_membership && $this->chance(13)) {
                        $this->recordMembership($event, $visitorId, $this->momentAfter($arrival, $endsAt));
                        $created['memberships']++;
                    }

                    if ($event->module_quiz && $question && $round && $this->chance(31)) {
                        $this->recordQuizAnswer($event, $visitorId, $this->momentAfter($arrival, $endsAt), $round, $question);
                        $created['quiz answers']++;
                    }

                    $bar->advance();
                }

                $created['page views'] += $this->recordBigScreenViews($event, $startsAt, $endsAt);
            });
        } finally {
            Model::reguard();
        }

        $bar->finish();
        $this->newLine(2);

        $this->components->twoColumnDetail('<fg=green>Seeded</fg=green>', '');
        foreach ($created as $label => $count) {
            $this->components->twoColumnDetail($label, (string) $count);
        }

        return self::SUCCESS;
    }

    private function resolveEvent(): ?Event
    {
        $key = (string) $this->argument('event');

        return Event::query()
            ->when(ctype_digit($key), fn ($query) => $query->where('id', (int) $key), fn ($query) => $query->where('slug', $key))
            ->first();
    }

    /**
     * Put the event on the clock so it is running right now — the statistics
     * pages read this window and nothing else.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function scheduleEvent(Event $event, int $hours): array
    {
        $endsAt = now()->addHour()->startOfHour();
        $startsAt = $endsAt->copy()->subHours($hours);

        $event->forceFill(['starts_at' => $startsAt, 'ends_at' => $endsAt])->save();

        return [$startsAt, $endsAt];
    }

    /**
     * Arrivals cluster after the doors open and thin out towards the end.
     */
    private function arrivalTime(Carbon $startsAt, Carbon $endsAt): Carbon
    {
        $minutes = $startsAt->diffInMinutes($endsAt);
        $bell = (mt_rand(0, 1000) + mt_rand(0, 1000)) / 2000;
        $skewed = min(0.99, $bell ** 0.75);

        return $startsAt->copy()->addMinutes((int) round($skewed * $minutes));
    }

    private function momentAfter(Carbon $arrival, Carbon $endsAt): Carbon
    {
        $window = max(1, $arrival->diffInMinutes($endsAt));

        return $arrival->copy()->addMinutes(mt_rand(1, min(45, $window)));
    }

    /**
     * One first visit, sometimes followed by a couple of returns.
     */
    private function recordVisit(Event $event, string $visitorId, array $client, Carbon $arrival, Carbon $endsAt): int
    {
        $this->pageView($event, $visitorId, $client, $arrival, 'landing', true);
        $views = 1;

        $returns = $this->chance(38) ? mt_rand(1, 3) : 0;

        for ($i = 0; $i < $returns; $i++) {
            $moment = $this->momentAfter($arrival, $endsAt);

            if ($moment->gt($endsAt)) {
                break;
            }

            $this->pageView($event, $visitorId, $client, $moment, 'landing', false);
            $views++;
        }

        return $views;
    }

    private function recordBigScreenViews(Event $event, Carbon $startsAt, Carbon $endsAt): int
    {
        $screens = mt_rand(2, 4);

        for ($i = 0; $i < $screens; $i++) {
            $this->pageView(
                $event,
                Str::random(32),
                ['device' => 'desktop', 'os' => 'Windows', 'browser' => 'Chrome'],
                $startsAt->copy()->addMinutes(mt_rand(0, max(1, $startsAt->diffInMinutes($endsAt)))),
                'vidiwall',
                true
            );
        }

        return $screens;
    }

    /**
     * @param  array{device: string, os: string, browser: string}  $client
     */
    private function pageView(Event $event, string $visitorId, array $client, Carbon $at, string $pageType, bool $isFirstVisit): void
    {
        EventPageView::create([
            'event_id' => $event->id,
            'visitor_id' => $visitorId,
            'page_type' => $pageType,
            'device_type' => $client['device'],
            'os' => $client['os'],
            'browser' => $client['browser'],
            'referrer' => $pageType === 'landing' ? self::REFERRERS[array_rand(self::REFERRERS)] : null,
            'is_first_visit' => $isFirstVisit,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /**
     * @param  array{path: string, thumb: ?string, type: string}  $file
     */
    private function recordFoto(Event $event, string $visitorId, Carbon $at, array $file): void
    {
        $status = $this->pick(['approved' => 74, 'pending' => 18, 'rejected' => 8]);

        FotoUpload::create([
            'event_id' => $event->id,
            'visitor_id' => $visitorId,
            'file_path' => $file['path'],
            'thumbnail_path' => $file['thumb'],
            'video_path' => $file['type'] === 'video' ? $file['path'] : null,
            'media_type' => $file['type'],
            'uploader_name' => fake()->firstName(),
            'status' => $status,
            'on_screen' => $status === 'approved' && $this->chance(45),
            'approved_at' => $status === 'approved' ? $at->copy()->addMinutes(mt_rand(1, 6)) : null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /**
     * @param  array{name: string, slug: string}  $candidate
     */
    private function recordVote(Event $event, string $visitorId, Carbon $at, array $candidate): void
    {
        Vote::create([
            'event_id' => $event->id,
            'visitor_id' => $visitorId,
            'candidate_name' => $candidate['name'],
            'candidate_slug' => $candidate['slug'],
            'voter_session' => Str::random(40),
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function recordLotteryEntry(Event $event, string $visitorId, Carbon $at): void
    {
        LotteryEntry::create([
            'event_id' => $event->id,
            'visitor_id' => $visitorId,
            'name' => fake()->name(),
            'phone' => '+43 6'.fake()->numerify('## ### ####'),
            'email' => fake()->safeEmail(),
            'entry_token' => Str::random(32),
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function recordMembership(Event $event, string $visitorId, Carbon $at): void
    {
        Membership::create([
            'event_id' => $event->id,
            'visitor_id' => $visitorId,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+43 6'.fake()->numerify('## ### ####'),
            'newsletter_opt_in' => $this->chance(62),
            'membership_number' => 'EB-'.fake()->unique()->numerify('#####'),
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function recordQuizAnswer(Event $event, string $visitorId, Carbon $at, QuizRound $round, QuizQuestion $question): void
    {
        $options = is_array($question->options) ? $question->options : [];
        $selected = $this->chance(58) ? (int) $question->correct_option : mt_rand(0, max(0, count($options) - 1));

        QuizAnswer::create([
            'event_id' => $event->id,
            'visitor_id' => $visitorId,
            'quiz_round_id' => $round->id,
            'quiz_question_id' => $question->id,
            'session_token' => Str::random(40),
            'guest_name' => fake()->firstName(),
            'selected_option' => $selected,
            'is_correct' => $selected === (int) $question->correct_option,
            'answered_at' => $at,
            'time_taken_ms' => mt_rand(1800, 14000),
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /**
     * @return Collection<int, array{name: string, slug: string}>
     */
    private function candidates(Event $event): Collection
    {
        return collect($event->voting_options ?: [])
            ->map(fn ($option) => (array) $option)
            ->filter(fn (array $option) => ! empty($option['name']))
            ->map(fn (array $option) => [
                'name' => $option['name'],
                'slug' => $option['slug'] ?? Str::slug($option['name']),
            ])
            ->values();
    }

    /**
     * Reuse media that already sits in storage so the moderation screens show
     * real thumbnails rather than broken images.
     *
     * @return Collection<int, array{path: string, thumb: ?string, type: string}>
     */
    private function fotoFiles(Event $event): Collection
    {
        $directory = storage_path('app/public/fotos/event-'.$event->id);

        if (! is_dir($directory)) {
            return collect();
        }

        return collect(scandir($directory) ?: [])
            ->reject(fn (string $name) => in_array($name, ['.', '..'], true) || str_starts_with($name, 'thumb_'))
            ->map(function (string $name) use ($event, $directory) {
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $thumb = 'thumb_'.$name;

                return [
                    'path' => "fotos/event-{$event->id}/{$name}",
                    'thumb' => is_file($directory.'/'.$thumb) ? "fotos/event-{$event->id}/{$thumb}" : null,
                    'type' => in_array($extension, ['mp4', 'mov', 'webm'], true) ? 'video' : 'photo',
                ];
            })
            ->values();
    }

    /**
     * @return array{device: string, os: string, browser: string}
     */
    private function weightedClient(): array
    {
        $total = array_sum(array_column(self::CLIENTS, 'weight'));
        $roll = mt_rand(1, $total);

        foreach (self::CLIENTS as $client) {
            $roll -= $client['weight'];

            if ($roll <= 0) {
                return $client;
            }
        }

        return self::CLIENTS[0];
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function pick(array $weights): string
    {
        $roll = mt_rand(1, array_sum($weights));

        foreach ($weights as $value => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return (string) $value;
            }
        }

        return (string) array_key_first($weights);
    }

    private function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }
}
