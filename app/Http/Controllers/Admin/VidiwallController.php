<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\VidiwallPlayer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VidiwallController extends Controller
{
    public function show(string $slug)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('vidiwall.show', compact('event'));
    }

    /**
     * Vidiwall poll. The screen passes `done=<id>&rev=<revision>` when the page it
     * was showing has finished so the playlist advances on the screen's clock
     * rather than the poll tick. `foto` is the page's first item, which the done
     * report names, and `page` is everything on screen, laid out `layout` to a
     * screen. `elapsed_ms` lets a screen that joins mid-page (or resumes after a
     * pause) pick up where the player is, and `next_page` is the page due after
     * this one so the screen can load it in advance. `qr_screen` covers the
     * wall with the QR code; the playlist is held under it.
     */
    public function feed(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if ($event->vidiwall_slideshow_mode) {
            $fotos = $event->fotoUploads()
                ->where('status', 'approved')
                ->orderByDesc('displayed_at')
                ->get()
                ->map(fn ($f) => [
                    'id' => $f->id,
                    'media_type' => $f->media_type,
                    'url' => $f->file_url,
                    'video_url' => $f->video_url,
                    'displayed_at' => $f->displayed_at,
                ]);

            return response()->json([
                'mode' => 'slideshow',
                'fotos' => $fotos,
                'interval' => $event->vidiwall_slideshow_interval,
                'overlay_text' => $event->vidiwall_overlay_text,
                'primary_color' => $event->primary_color,
                'accent_color' => $event->accent_color,
                'event_name' => $event->name,
            ]);
        }

        $player = $event->syncPlayer(
            $request->integer('done') ?: null,
            $request->has('rev') ? $request->integer('rev') : null,
        );
        $page = $player ? $player->screenFotos() : new Collection;
        $onScreen = $page->first();
        $nextPage = ($player ?? $event->vidiwallPlayer()->make()->setRelation('event', $event))->upNextPage();

        return response()->json([
            'mode' => 'player',
            'state' => $player?->state ?? VidiwallPlayer::STATE_STOPPED,
            'qr_screen' => (bool) $player?->qr_screen,
            'revision' => $player?->revision ?? 0,
            'elapsed_ms' => $onScreen ? $player->elapsedMs() : 0,
            'layout' => $event->photosPerScreen(),
            'foto' => $onScreen ? [
                'id' => $onScreen->id,
                'media_type' => $onScreen->media_type,
                'url' => $onScreen->file_url,
                'video_url' => $onScreen->video_url,
                'displayed_at' => $onScreen->displayed_at,
                'slot_ms' => $onScreen->screenSlotMs(),
            ] : null,
            'page' => $page->map(fn (FotoUpload $foto) => $this->screenItem($foto))->values()->all(),
            'next' => $nextPage->isNotEmpty() ? $this->screenItem($nextPage->first()) : null,
            'next_page' => $nextPage->map(fn (FotoUpload $foto) => $this->screenItem($foto))->values()->all(),
            'overlay_text' => $event->vidiwall_overlay_text,
            'primary_color' => $event->primary_color,
            'accent_color' => $event->accent_color,
            'event_name' => $event->name,
        ]);
    }

    /**
     * Lottery show feed: the draw, a winner or the winners board, timed from
     * the moment of the request so the screen can run it on its own clock.
     */
    public function lotteryFeed(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $screen = $event->lotteryScreen?->setRelation('event', $event);

        return response()->json($screen ? $screen->screenFeed() : ['revision' => 0, 'show' => null]);
    }

    /**
     * Vote show feed: live results, the winner reveal or the podium, timed
     * from the moment of the request so the screen can run it on its own clock.
     */
    public function votingFeed(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $screen = $event->votingScreen?->setRelation('event', $event);

        return response()->json($screen ? $screen->screenFeed() : ['revision' => 0, 'show' => null]);
    }

    /**
     * @return array{id: int, media_type: string, url: string, video_url: string|null}
     */
    private function screenItem(FotoUpload $foto): array
    {
        return [
            'id' => $foto->id,
            'media_type' => $foto->media_type,
            'url' => $foto->file_url,
            'video_url' => $foto->video_url,
        ];
    }

    /**
     * Fan Clash on the vidiwall: the matchup, the round with its two counters
     * (O(1) reads, closed lazily when time is up) and the winner, for as long
     * as the console keeps it on the wall.
     */
    public function clashFeed(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $screen = $event->fanClashScreen?->setRelation('event', $event);

        return response()->json($screen ? $screen->screenFeed() : ['revision' => 0, 'show' => null]);
    }
}
