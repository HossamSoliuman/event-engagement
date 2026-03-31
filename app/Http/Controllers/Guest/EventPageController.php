<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSession;
use Illuminate\Http\Request;

class EventPageController extends Controller
{
    public function index(string $slug)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $sessionToken = request()->cookie("eb_session_{$event->id}");
        $guestSession = $sessionToken
            ? EventSession::where('session_token', $sessionToken)->where('event_id', $event->id)->first()
            : null;


        return view('guest.event', compact('event', 'guestSession'));
    }

    public function startSession(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $data  = $request->validate([
            'guest_name'  => 'required|string|max:100',
            'guest_phone' => 'nullable|string|max:30',
        ]);
        $session = EventSession::startSession($event, $data);
        return response()
            ->json(['token' => $session->session_token, 'success' => true])
            ->cookie("eb_session_{$event->id}", $session->session_token, 60 * 24 * 7);
    }
}
