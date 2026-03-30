<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Membership;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function signup(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)
            ->where('is_active', true)
            ->where('module_membership', true)
            ->firstOrFail();

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|max:255',
            'phone'            => 'nullable|string|max:30',
            'team_preference'  => 'nullable|string|max:100',
            'newsletter_opt_in' => 'boolean',
        ]);

        $exists = Membership::where('event_id', $event->id)
            ->where('email', $data['email'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This email is already registered.',
            ], 422);
        }

        Membership::create(array_merge($data, ['event_id' => $event->id]));

        return response()->json([
            'success' => true,
            'message' => '🎉 Welcome to the club! Membership confirmed.',
        ]);
    }
}
