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

        $onScreen = $event->fotoUploads()
            ->where('status', 'approved')
            ->where('on_screen', true)
            ->latest('displayed_at')
            ->first();

        return response()->json([
            'foto'       => $onScreen ? [
                'id'          => $onScreen->id,
                'url'         => $onScreen->file_url,
                'uploader'    => $onScreen->uploader_name,
                'displayed_at' => $onScreen->displayed_at,
            ] : null,
            'event_name'      => $event->name,
            'primary_color'   => $event->primary_color,
            'accent_color'    => $event->accent_color,
        ]);
    }
}
