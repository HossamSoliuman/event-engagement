<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Models\EventPageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    /**
     * One shared cookie across every event. Per-event uniqueness comes from the
     * (event_id, visitor_id) pair, so a guest attending two events still counts
     * as one returning visitor in each.
     */
    public const COOKIE = 'eb_visitor';

    private const COOKIE_LIFETIME_MINUTES = 525600;

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|monitor|curl|wget|headless|lighthouse|pingdom/i';

    public function handle(Request $request, Closure $next, string $pageType = 'landing'): Response
    {
        $existingVisitorId = $this->readCookie($request);
        $visitorId = $existingVisitorId ?? Str::random(32);

        $request->attributes->set('visitor_id', $visitorId);

        $response = $next($request);

        Cookie::queue(Cookie::make(self::COOKIE, $visitorId, self::COOKIE_LIFETIME_MINUTES));

        if ($this->shouldRecord($request, $response)) {
            $this->record($request, $visitorId, $pageType, $existingVisitorId === null);
        }

        return $response;
    }

    private function readCookie(Request $request): ?string
    {
        $value = $request->cookie(self::COOKIE);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Only successful GET page loads by a real browser count as an "opening".
     */
    private function shouldRecord(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }

        return ! preg_match(self::BOT_PATTERN, (string) $request->userAgent());
    }

    private function record(Request $request, string $visitorId, string $pageType, bool $isFirstVisit): void
    {
        $event = Event::where('slug', $request->route('slug'))->first();

        if (! $event) {
            return;
        }

        $agent = (string) $request->userAgent();

        EventPageView::create([
            'event_id' => $event->id,
            'visitor_id' => $visitorId,
            'page_type' => $pageType,
            'device_type' => $this->deviceType($agent),
            'os' => $this->operatingSystem($agent),
            'browser' => $this->browser($agent),
            'referrer' => Str::limit((string) $request->headers->get('referer'), 250, ''),
            'is_first_visit' => $isFirstVisit,
        ]);
    }

    private function deviceType(string $agent): string
    {
        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $agent)) {
            return 'tablet';
        }

        if (preg_match('/Mobile|iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini/i', $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function operatingSystem(string $agent): ?string
    {
        $systems = [
            'iOS' => '/iPhone|iPad|iPod/i',
            'Android' => '/Android/i',
            'Windows' => '/Windows/i',
            'macOS' => '/Macintosh|Mac OS X/i',
            'Linux' => '/Linux/i',
        ];

        foreach ($systems as $name => $pattern) {
            if (preg_match($pattern, $agent)) {
                return $name;
            }
        }

        return null;
    }

    private function browser(string $agent): ?string
    {
        $browsers = [
            'Samsung Internet' => '/SamsungBrowser/i',
            'Edge' => '/Edg/i',
            'Opera' => '/OPR|Opera/i',
            'Chrome' => '/Chrome|CriOS/i',
            'Firefox' => '/Firefox|FxiOS/i',
            'Safari' => '/Safari/i',
        ];

        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $agent)) {
                return $name;
            }
        }

        return null;
    }
}
