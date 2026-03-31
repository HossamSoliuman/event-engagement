<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\LotteryEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LotteryController extends Controller
{
    public function enter(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->where('module_lottery', true)->firstOrFail();

        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);

        if (LotteryEntry::where('event_id', $event->id)->where('phone', $data['phone'])->exists()) {
            return response()->json(['success' => false, 'message' => 'This phone number is already entered!'], 422);
        }

        LotteryEntry::create(array_merge($data, [
            'event_id'    => $event->id,
            'entry_token' => Str::random(32),
        ]));

        ActivityLog::record('lottery.entered', ['name' => $data['name']], $event->id);
        return response()->json(['success' => true, 'message' => "🎰 You're in! Good luck!",]);
    }
}
