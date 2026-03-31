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

        // Slideshow mode: return all approved on-screen fotos
        if ($event->vidiwall_slideshow_mode) {
            $fotos = $event->fotoUploads()
                ->where('status', 'approved')
                ->where('on_screen', true)
                ->orderByDesc('displayed_at')
                ->get()
                ->map(fn($f) => [
                    'id'          => $f->id,
                    'url'         => $f->file_url,
                    'uploader'    => $f->uploader_name,
                    'displayed_at'=> $f->displayed_at,
                ]);

            return response()->json([
                'mode'             => 'slideshow',
                'fotos'            => $fotos,
                'interval'         => $event->vidiwall_slideshow_interval,
                'show_uploader'    => $event->vidiwall_show_uploader,
                'overlay_text'     => $event->vidiwall_overlay_text,
                'primary_color'    => $event->primary_color,
                'accent_color'     => $event->accent_color,
                'event_name'       => $event->name,
            ]);
        }

        // Single photo mode
        $onScreen = $event->fotoUploads()
            ->where('status', 'approved')
            ->where('on_screen', true)
            ->latest('displayed_at')
            ->first();

        return response()->json([
            'mode'          => 'single',
            'foto'          => $onScreen ? [
                'id'          => $onScreen->id,
                'url'         => $onScreen->file_url,
                'uploader'    => $onScreen->uploader_name,
                'displayed_at'=> $onScreen->displayed_at,
            ] : null,
            'show_uploader' => $event->vidiwall_show_uploader,
            'overlay_text'  => $event->vidiwall_overlay_text,
            'primary_color' => $event->primary_color,
            'accent_color'  => $event->accent_color,
            'event_name'    => $event->name,
        ]);
    }
}
