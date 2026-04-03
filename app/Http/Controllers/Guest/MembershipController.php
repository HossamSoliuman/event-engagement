<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MembershipController extends Controller
{
    public function signup(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->where('module_membership', true)->firstOrFail();

        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'email'             => 'required|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'team_preference'   => 'nullable|string|max:100',
            'newsletter_opt_in' => 'boolean',
            'extra_fields'      => 'nullable|array',
        ]);

        if (Membership::where('event_id', $event->id)->where('email', $data['email'])->exists()) {
            return response()->json(['success' => false, 'message' => 'This email is already registered.'], 422);
        }

        Membership::create(array_merge($data, [
            'event_id'          => $event->id,
            'membership_number' => 'EB-' . strtoupper(Str::random(6)),
        ]));

        ActivityLog::record('membership.signup', ['name' => $data['name'], 'email' => $data['email']], $event->id);
        return response()->json(['success' => true, 'message' => '⭐ Welcome! Membership confirmed.']);
    }
}
