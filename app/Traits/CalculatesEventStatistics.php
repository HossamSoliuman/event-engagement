<?php

namespace App\Traits;

use App\Http\Middleware\TrackPageView;
use App\Models\Event;
use App\Models\EventPageView;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the client-facing statistics for a single event over a date range.
 *
 * Everything here is derived from the shared visitor_id issued by
 * {@see TrackPageView}, which is what makes "unique
 * people" answerable rather than just "number of actions".
 */
trait CalculatesEventStatistics
{
    /** Events no longer than this are plotted hour by hour rather than day by day. */
    private const TIMELINE_HOURLY_LIMIT = 48;

    /**
     * The event's own running time — every figure on the statistics page is
     * scoped to it, so the numbers always describe the event itself.
     *
     * Events without a scheduled window count from the day they were created
     * up to now, which is the whole life of the event so far.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function eventRange(Event $event): array
    {
        return [
            $event->starts_at?->copy() ?? $event->created_at->copy()->startOfDay(),
            $event->ends_at?->copy() ?? now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildStatistics(Event $event, CarbonInterface $from, CarbonInterface $to): array
    {
        $views = $this->pageViewsQuery($event, $from, $to);

        $landingViews = (clone $views)->where('page_type', 'landing');
        $uniqueVisitors = (clone $landingViews)->distinct()->count('visitor_id');

        $byHour = $this->opensByHour($event, $from, $to);

        return [
            'range' => ['from' => $from, 'to' => $to],
            'opens' => [
                'total' => (clone $landingViews)->count(),
                'unique' => $uniqueVisitors,
                'returning' => max(0, (clone $landingViews)->where('is_first_visit', false)->distinct()->count('visitor_id')),
                'vidiwall' => (clone $views)->where('page_type', 'vidiwall')->count(),
            ],
            'by_hour' => $byHour,
            'timeline' => $this->visitsTimeline($event, $from, $to),
            'peak' => $this->peakHour($byHour),
            'devices' => $this->breakdown((clone $landingViews), 'device_type'),
            'browsers' => $this->breakdown((clone $landingViews), 'browser'),
            'fotos' => $this->fotoStatistics($event, $from, $to),
            'modules' => $this->moduleStatistics($event, $from, $to, $uniqueVisitors),
            'engagement' => $this->engagement($event, $from, $to, $uniqueVisitors),
        ];
    }

    /**
     * @return Builder<EventPageView>
     */
    private function pageViewsQuery(Event $event, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return EventPageView::query()
            ->where('event_id', $event->id)
            ->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Hour-of-day distribution — answers the client's "in which time".
     *
     * @return array<int, int>
     */
    private function opensByHour(Event $event, CarbonInterface $from, CarbonInterface $to): array
    {
        $counts = $this->pageViewsQuery($event, $from, $to)
            ->where('page_type', 'landing')
            ->selectRaw($this->hourExpression().' as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $hours = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hours[$hour] = (int) ($counts[$hour] ?? $counts[sprintf('%02d', $hour)] ?? 0);
        }

        return $hours;
    }

    /**
     * Visits along the event's own clock: how many openings happened, and how
     * many distinct people were behind them, in each slice of the timeline.
     *
     * Short events are sliced by hour, multi-day events by day, so the chart
     * keeps a readable number of points either way.
     *
     * @return array{granularity: string, peak: int, points: array<int, array{label: string, tick: string, visits: int, visitors: int}>}
     */
    private function visitsTimeline(Event $event, CarbonInterface $from, CarbonInterface $to): array
    {
        $start = Carbon::parse($from);
        $end = Carbon::parse($to);
        $byHour = $start->diffInHours($end) <= self::TIMELINE_HOURLY_LIMIT;

        $rows = $this->pageViewsQuery($event, $from, $to)
            ->where('page_type', 'landing')
            ->selectRaw($this->timelineExpression($byHour).' as bucket, COUNT(*) as visits, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $points = [];
        $cursor = $byHour ? $start->copy()->startOfHour() : $start->copy()->startOfDay();
        $limit = $byHour ? self::TIMELINE_HOURLY_LIMIT + 1 : 366;

        while ($cursor->lte($end) && count($points) < $limit) {
            $row = $rows->get($cursor->format($byHour ? 'Y-m-d H:00:00' : 'Y-m-d'));

            $points[] = [
                'label' => $cursor->format($byHour ? 'd M, H:i' : 'd M Y'),
                'tick' => $cursor->format($byHour ? 'H:i' : 'd/m'),
                'visits' => (int) ($row->visits ?? 0),
                'visitors' => (int) ($row->visitors ?? 0),
            ];

            if ($byHour) {
                $cursor->addHour();
            } else {
                $cursor->addDay();
            }
        }

        return [
            'granularity' => $byHour ? 'hour' : 'day',
            'peak' => (int) max(array_column($points, 'visits') ?: [0]),
            'points' => $points,
        ];
    }

    private function timelineExpression(bool $byHour): string
    {
        if (! $byHour) {
            return $this->dateExpression();
        }

        return $this->isSqlite()
            ? "strftime('%Y-%m-%d %H:00:00', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')";
    }

    /**
     * @param  array<int, int>  $byHour
     * @return array{hour: ?int, count: int}
     */
    private function peakHour(array $byHour): array
    {
        $peak = array_keys($byHour, max($byHour))[0] ?? null;

        return [
            'hour' => max($byHour) > 0 ? (int) $peak : null,
            'count' => (int) max($byHour ?: [0]),
        ];
    }

    /**
     * @param  Builder<EventPageView>  $query
     * @return Collection<string, int>
     */
    private function breakdown(Builder $query, string $column): Collection
    {
        return $query->selectRaw("{$column} as label, COUNT(*) as total")
            ->whereNotNull($column)
            ->groupBy($column)
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->map(fn ($total) => (int) $total);
    }

    /**
     * @return array<string, mixed>
     */
    private function fotoStatistics(Event $event, CarbonInterface $from, CarbonInterface $to): array
    {
        $base = fn () => $event->fotoUploads()->whereBetween('created_at', [$from, $to]);

        return [
            'total' => $base()->count(),
            'photos' => $base()->where('media_type', 'photo')->count(),
            'videos' => $base()->where('media_type', 'video')->count(),
            'approved' => $base()->where('status', 'approved')->count(),
            'pending' => $base()->where('status', 'pending')->count(),
            'rejected' => $base()->where('status', 'rejected')->count(),
            'shown_on_screen' => $base()->where('on_screen', true)->count(),
            'uploaders' => $base()->whereNotNull('visitor_id')->distinct()->count('visitor_id'),
            'by_hour' => $this->uploadsByHour($event, $from, $to),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function uploadsByHour(Event $event, CarbonInterface $from, CarbonInterface $to): array
    {
        $counts = $event->fotoUploads()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw($this->hourExpression().' as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $hours = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hours[$hour] = (int) ($counts[$hour] ?? $counts[sprintf('%02d', $hour)] ?? 0);
        }

        return $hours;
    }

    /**
     * Per-module actions and unique participants, plus the share of visitors
     * who engaged with each module.
     *
     * @return array<string, array{label: string, enabled: bool, actions: int, participants: int, rate: float}>
     */
    private function moduleStatistics(Event $event, CarbonInterface $from, CarbonInterface $to, int $uniqueVisitors): array
    {
        $range = [$from, $to];

        $modules = [
            'fotobomb' => ['Foto Bomb', $event->module_fotobomb, fn () => $event->fotoUploads()],
            'voting' => ['Voting', $event->module_voting, fn () => $event->votes()],
            'lottery' => ['Lottery', $event->module_lottery, fn () => $event->lotteryEntries()],
            'membership' => ['Membership', $event->module_membership, fn () => $event->memberships()],
            'quiz' => ['Quiz', $event->module_quiz, fn () => $event->quizAnswers()],
            'fanclash' => ['Fan Clash', $event->module_fanclash, fn () => $event->fanClashParticipants()],
        ];

        $statistics = [];

        foreach ($modules as $key => [$label, $enabled, $relation]) {
            $actions = $relation()->whereBetween('created_at', $range)->count();
            $participants = $relation()
                ->whereBetween('created_at', $range)
                ->whereNotNull('visitor_id')
                ->distinct()
                ->count('visitor_id');

            $statistics[$key] = [
                'label' => $label,
                'enabled' => (bool) $enabled,
                'actions' => $actions,
                'participants' => $participants,
                'rate' => $uniqueVisitors > 0 ? round($participants / $uniqueVisitors * 100, 1) : 0.0,
            ];
        }

        return $statistics;
    }

    /**
     * The opened-to-participated funnel, plus the two things a client asks
     * next: who went deeper than one module, and who left contact details.
     *
     * @return array{visitors: int, participants: int, rate: float, actions: int, multi_module: int, leads: int}
     */
    private function engagement(Event $event, CarbonInterface $from, CarbonInterface $to, int $uniqueVisitors): array
    {
        $range = [$from, $to];
        $actions = 0;

        /** @var array<string, Collection<int, string>> $participantsByModule */
        $participantsByModule = [];

        $relations = [
            'fotobomb' => fn () => $event->fotoUploads(),
            'voting' => fn () => $event->votes(),
            'lottery' => fn () => $event->lotteryEntries(),
            'membership' => fn () => $event->memberships(),
            'quiz' => fn () => $event->quizAnswers(),
            'fanclash' => fn () => $event->fanClashParticipants(),
        ];

        foreach ($relations as $key => $relation) {
            $actions += $relation()->whereBetween('created_at', $range)->count();
            $participantsByModule[$key] = $relation()
                ->whereBetween('created_at', $range)
                ->whereNotNull('visitor_id')
                ->distinct()
                ->pluck('visitor_id');
        }

        /** Each module contributes a visitor once, so the tally is "how many modules this person used". */
        $modulesPerVisitor = collect($participantsByModule)->flatten()->countBy();
        $participants = $modulesPerVisitor->count();

        return [
            'visitors' => $uniqueVisitors,
            'participants' => $participants,
            'actions' => $actions,
            'rate' => $uniqueVisitors > 0 ? round($participants / $uniqueVisitors * 100, 1) : 0.0,
            'multi_module' => $modulesPerVisitor->filter(fn (int $modules) => $modules >= 2)->count(),
            'leads' => collect([$participantsByModule['lottery'], $participantsByModule['membership']])
                ->flatten()
                ->unique()
                ->count(),
        ];
    }

    private function hourExpression(): string
    {
        return $this->isSqlite() ? "CAST(strftime('%H', created_at) AS INTEGER)" : 'HOUR(created_at)';
    }

    private function dateExpression(): string
    {
        return $this->isSqlite() ? "strftime('%Y-%m-%d', created_at)" : 'DATE(created_at)';
    }

    private function isSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }
}
