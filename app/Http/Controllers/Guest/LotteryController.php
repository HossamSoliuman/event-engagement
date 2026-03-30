<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LotteryEntry;
use Illuminate\Http\Request;

class LotteryController extends Controller
{
    public function enter(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)
            ->where('is_active', true)
            ->where('module_lottery', true)
            ->firstOrFail();

        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);

        // Prevent duplicate phone entries per event
        $exists = LotteryEntry::where('event_id', $event->id)
            ->where('phone', $data['phone'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'You have already entered the lottery!',
            ], 422);
        }

        LotteryEntry::create(array_merge($data, ['event_id' => $event->id]));

        return response()->json([
            'success' => true,
            'message' => '🎰 You\'re in! Good luck!',
        ]);
    }
}
