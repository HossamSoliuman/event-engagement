<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackPageView;
use App\Models\Event;
use App\Models\EventPageView;
use App\Models\FotoUpload;
use App\Models\LotteryEntry;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EventStatisticsTest extends TestCase
{
    use DatabaseTransactions;

    private function event(array $overrides = []): Event
    {
        return Event::factory()->create(array_merge([
            'is_active' => true,
            'module_fotobomb' => true,
            'module_lottery' => true,
            'module_voting' => true,
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function pageView(Event $event, string $visitorId, array $attributes = []): EventPageView
    {
        return EventPageView::create(array_merge([
            'event_id' => $event->id,
            'visitor_id' => $visitorId,
            'page_type' => 'landing',
            'device_type' => 'mobile',
            'os' => 'iOS',
            'browser' => 'Safari',
            'is_first_visit' => true,
        ], $attributes));
    }

    public function test_opening_the_landing_page_records_a_page_view(): void
    {
        $event = $this->event();

        $this->get("/e/{$event->slug}")->assertOk();

        $this->assertSame(1, EventPageView::where('event_id', $event->id)->count());

        $view = EventPageView::where('event_id', $event->id)->first();
        $this->assertSame('landing', $view->page_type);
        $this->assertTrue($view->is_first_visit);
        $this->assertNotEmpty($view->visitor_id);
    }

    public function test_the_visitor_cookie_is_issued_and_reused_across_openings(): void
    {
        $event = $this->event();

        $response = $this->get("/e/{$event->slug}")->assertOk();
        $visitorId = $response->getCookie(TrackPageView::COOKIE, false)->getValue();

        $this->assertNotEmpty($visitorId);

        $this->withUnencryptedCookie(TrackPageView::COOKIE, $visitorId)
            ->get("/e/{$event->slug}")
            ->assertOk();

        $views = EventPageView::where('event_id', $event->id)->get();

        $this->assertCount(2, $views, 'Both openings should be counted.');
        $this->assertSame(1, $views->pluck('visitor_id')->unique()->count(), 'Both openings share one visitor.');
        $this->assertFalse($views->last()->is_first_visit, 'The second opening is a return visit.');
    }

    public function test_bot_traffic_is_not_counted_as_an_opening(): void
    {
        $event = $this->event();

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])
            ->get("/e/{$event->slug}")
            ->assertOk();

        $this->assertSame(0, EventPageView::where('event_id', $event->id)->count());
    }

    public function test_a_missing_event_records_nothing(): void
    {
        $before = EventPageView::count();

        $this->get('/e/does-not-exist')->assertNotFound();

        $this->assertSame($before, EventPageView::count());
    }

    public function test_device_type_is_derived_from_the_user_agent(): void
    {
        $event = $this->event();

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36'])
            ->get("/e/{$event->slug}")
            ->assertOk();

        $view = EventPageView::where('event_id', $event->id)->first();

        $this->assertSame('desktop', $view->device_type);
        $this->assertSame('macOS', $view->os);
        $this->assertSame('Chrome', $view->browser);
    }

    public function test_a_vote_is_attributed_to_the_visitor_issued_on_the_landing_page(): void
    {
        $event = $this->event(['voting_options' => [['name' => 'Marco']]]);

        $landing = $this->get("/e/{$event->slug}")->assertOk();
        $rawCookie = $landing->getCookie(TrackPageView::COOKIE, false)->getValue();
        $visitorId = EventPageView::where('event_id', $event->id)->value('visitor_id');

        $this->withUnencryptedCookie(TrackPageView::COOKIE, $rawCookie)
            ->withCredentials()
            ->postJson("/e/{$event->slug}/vote", ['candidate' => 'Marco'])
            ->assertOk();

        $this->assertSame(
            $visitorId,
            Vote::where('event_id', $event->id)->first()->visitor_id,
            'The vote should carry the same visitor id the landing page issued.'
        );
    }

    public function test_the_statistics_page_reports_openings_unique_visitors_and_participation(): void
    {
        $event = $this->event();

        // Three openings from two people.
        $this->pageView($event, 'visitor-1');
        $this->pageView($event, 'visitor-1', ['is_first_visit' => false]);
        $this->pageView($event, 'visitor-2', ['device_type' => 'desktop']);

        // One of them participates twice, in two different modules.
        FotoUpload::create([
            'event_id' => $event->id,
            'visitor_id' => 'visitor-1',
            'file_path' => 'fotos/a.jpg',
            'media_type' => 'photo',
            'status' => 'approved',
        ]);
        LotteryEntry::create([
            'event_id' => $event->id,
            'visitor_id' => 'visitor-1',
            'name' => 'Anna',
            'phone' => '+43000',
            'entry_token' => 'token-'.uniqid(),
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.statistics.index', $event))
            ->assertOk();

        $stats = $response->viewData('stats');

        $this->assertSame(3, $stats['opens']['total']);
        $this->assertSame(2, $stats['opens']['unique']);
        $this->assertSame(1, $stats['engagement']['participants'], 'Only one distinct person engaged.');
        $this->assertSame(2, $stats['engagement']['actions'], 'They performed two actions.');
        $this->assertSame(50.0, $stats['engagement']['rate']);
        $this->assertSame(1, $stats['fotos']['total']);
        $this->assertSame(1, $stats['fotos']['approved']);
        $this->assertSame(2, $stats['devices']['mobile']);
        $this->assertSame(1, $stats['devices']['desktop']);
    }

    public function test_statistics_only_count_records_from_while_the_event_was_running(): void
    {
        $event = $this->event([
            'starts_at' => now()->subHours(4),
            'ends_at' => now()->addHour(),
        ]);

        $this->pageView($event, 'before-visitor')->forceFill(['created_at' => now()->subHours(9)])->save();
        $this->pageView($event, 'during-visitor')->forceFill(['created_at' => now()->subHour()])->save();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.statistics.index', $event))
            ->assertOk();

        $stats = $response->viewData('stats');

        $this->assertSame(1, $stats['opens']['total'], 'The opening from before the event started is not event traffic.');
        $this->assertTrue($stats['range']['from']->equalTo($event->starts_at), 'The range is the event window itself.');
        $this->assertTrue($stats['range']['to']->equalTo($event->ends_at));
    }

    public function test_an_event_without_a_schedule_counts_from_the_day_it_was_created(): void
    {
        $event = $this->event();

        $this->pageView($event, 'old-visitor')->forceFill(['created_at' => now()->subDays(20)])->save();
        $this->pageView($event, 'recent-visitor');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.statistics.index', $event))
            ->assertOk();

        $stats = $response->viewData('stats');

        $this->assertSame(1, $stats['opens']['total'], 'The 20-day-old opening predates the event.');
    }

    public function test_the_timeline_slices_a_short_event_by_hour_and_reports_visits_and_people(): void
    {
        $event = $this->event([
            'starts_at' => now()->subHours(3)->startOfHour(),
            'ends_at' => now()->startOfHour(),
        ]);

        $busyHour = now()->subHours(2)->startOfHour();

        $this->pageView($event, 'visitor-1')->forceFill(['created_at' => $busyHour->copy()->addMinutes(5)])->save();
        $this->pageView($event, 'visitor-1')->forceFill(['created_at' => $busyHour->copy()->addMinutes(20)])->save();
        $this->pageView($event, 'visitor-2')->forceFill(['created_at' => $busyHour->copy()->addMinutes(40)])->save();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.statistics.index', $event))
            ->assertOk()
            ->assertSee('Visits Over Time')
            ->assertSee('ts-svg', false);

        $timeline = $response->viewData('stats')['timeline'];
        $busy = collect($timeline['points'])->firstWhere('label', $busyHour->format('d M, H:i'));

        $this->assertSame('hour', $timeline['granularity']);
        $this->assertSame(4, count($timeline['points']), 'Four hourly slices cover a three-hour event.');
        $this->assertSame(3, $busy['visits'], 'Three openings landed in that hour.');
        $this->assertSame(2, $busy['visitors'], 'They came from two people.');
        $this->assertSame(3, $timeline['peak']);
    }

    public function test_the_timeline_slices_a_multi_day_event_by_day(): void
    {
        $event = $this->event([
            'starts_at' => now()->subDays(6)->startOfDay(),
            'ends_at' => now()->endOfDay(),
        ]);

        $this->pageView($event, 'visitor-1')->forceFill(['created_at' => now()->subDays(3)->setTime(20, 0)])->save();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.statistics.index', $event))
            ->assertOk();

        $timeline = $response->viewData('stats')['timeline'];

        $this->assertSame('day', $timeline['granularity']);
        $this->assertSame(7, count($timeline['points']));
        $this->assertSame(1, $timeline['peak']);
    }

    public function test_vidiwall_openings_are_tracked_separately_from_landing_openings(): void
    {
        $event = $this->event();

        $this->pageView($event, 'visitor-1');
        $this->pageView($event, 'screen-1', ['page_type' => 'vidiwall']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.statistics.index', $event))
            ->assertOk();

        $stats = $response->viewData('stats');

        $this->assertSame(1, $stats['opens']['total'], 'Big-screen views are not guest page openings.');
        $this->assertSame(1, $stats['opens']['vidiwall']);
    }

    public function test_hourly_distribution_covers_every_hour_of_the_day(): void
    {
        $event = $this->event();

        $openedAt = now();
        $this->pageView($event, 'visitor-1')->forceFill(['created_at' => $openedAt])->save();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.statistics.index', $event))
            ->assertOk();

        $byHour = $response->viewData('stats')['by_hour'];

        $this->assertCount(24, $byHour, 'Every hour of the day is represented, including empty ones.');
        $this->assertSame(1, $byHour[$openedAt->hour]);
        $this->assertSame(0, $byHour[($openedAt->hour + 5) % 24]);
    }

    public function test_the_engagement_funnel_separates_deep_engagement_from_a_single_tap(): void
    {
        $event = $this->event();

        foreach (['visitor-1', 'visitor-2', 'visitor-3'] as $visitor) {
            $this->pageView($event, $visitor);
        }

        // One person votes and enters the lottery, another only votes.
        Vote::create([
            'event_id' => $event->id,
            'visitor_id' => 'visitor-1',
            'candidate_name' => 'Marco',
            'candidate_slug' => 'marco',
        ]);
        LotteryEntry::create([
            'event_id' => $event->id,
            'visitor_id' => 'visitor-1',
            'name' => 'Anna',
            'phone' => '+43000',
            'entry_token' => 'token-'.uniqid(),
        ]);
        Vote::create([
            'event_id' => $event->id,
            'visitor_id' => 'visitor-2',
            'candidate_name' => 'Marco',
            'candidate_slug' => 'marco',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.statistics.index', $event))
            ->assertOk()
            ->assertSee('Engagement Funnel');

        $engagement = $response->viewData('stats')['engagement'];

        $this->assertSame(3, $engagement['visitors']);
        $this->assertSame(2, $engagement['participants'], 'Two of the three did something.');
        $this->assertSame(1, $engagement['multi_module'], 'Only one person used more than one module.');
        $this->assertSame(1, $engagement['leads'], 'Only the lottery entry left contact details.');
        $this->assertSame(3, $engagement['actions']);
    }

    public function test_the_client_report_renders_with_the_event_branding(): void
    {
        $event = $this->event(['name' => 'Ski World Cup Kitzbuehel']);

        $this->pageView($event, 'visitor-1');

        $this->actingAs($this->admin())
            ->get(route('admin.statistics.report', $event))
            ->assertOk()
            ->assertSee('Ski World Cup Kitzbuehel')
            ->assertSee('Event Engagement Report');
    }

    public function test_guests_cannot_reach_the_statistics_page(): void
    {
        $event = $this->event();

        $this->get(route('admin.statistics.index', $event))->assertRedirect();
    }
}
