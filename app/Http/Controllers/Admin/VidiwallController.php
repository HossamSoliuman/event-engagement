<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;

class VidiwallController extends Controller
{
    public function show(string $slug)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('vidiwall.show', compact('event'));
    }

    public function feed(string $slug)
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
                    'uploader' => $f->uploader_name,
                    'displayed_at' => $f->displayed_at,
                ]);

            return response()->json([
                'mode' => 'slideshow',
                'fotos' => $fotos,
                'interval' => $event->vidiwall_slideshow_interval,
                'show_uploader' => $event->vidiwall_show_uploader,
                'overlay_text' => $event->vidiwall_overlay_text,
                'primary_color' => $event->primary_color,
                'accent_color' => $event->accent_color,
                'event_name' => $event->name,
            ]);
        }

        $onScreen = $event->fotoUploads()
            ->where('status', 'approved')
            ->where('on_screen', true)
            ->latest('displayed_at')
            ->first();

        return response()->json([
            'mode' => 'single',
            'foto' => $onScreen ? [
                'id' => $onScreen->id,
                'media_type' => $onScreen->media_type,
                'url' => $onScreen->file_url,
                'video_url' => $onScreen->video_url,
                'uploader' => $onScreen->uploader_name,
                'displayed_at' => $onScreen->displayed_at,
            ] : null,
            'show_uploader' => $event->vidiwall_show_uploader,
            'overlay_text' => $event->vidiwall_overlay_text,
            'primary_color' => $event->primary_color,
            'accent_color' => $event->accent_color,
            'event_name' => $event->name,
        ]);
    }

    /**
     * Fan Clash rope feed. Reads the two round counters (O(1)), finalizes the
     * round lazily on expiry, and keeps a freshly finished round visible for a
     * short window so the big screen can hold the winner reveal.
     */
    public function clashFeed(string $slug)
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        $round = $event->activeFanClashRound();
        if ($round) {
            $round->finalizeIfExpired();
        }

        if (! $round) {
            $round = $event->fanClashRounds()
                ->where('status', 'finished')
                ->where('finished_at', '>=', now()->subSeconds(10))
                ->latest('finished_at')
                ->first();
        }

        if (! $round) {
            return response()->json(['status' => 'idle']);
        }

        $headcounts = $round->headcounts();

        return response()->json([
            'status' => $round->status,
            'round_id' => $round->id,
            'category' => $round->category,
            'side_a_name' => $round->side_a_name,
            'side_b_name' => $round->side_b_name,
            'side_a_color' => $round->side_a_color,
            'side_b_color' => $round->side_b_color,
            'side_a_taps' => $round->side_a_taps,
            'side_b_taps' => $round->side_b_taps,
            'headcount_a' => $headcounts['a'],
            'headcount_b' => $headcounts['b'],
            'remaining_ms' => $round->remainingMs(),
            'winner_side' => $round->winner_side,
            'sponsor_logo_url' => $round->sponsor_logo_url,
            'event_name' => $event->name,
        ]);
    }
}
