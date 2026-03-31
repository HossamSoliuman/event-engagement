<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Vote;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->where('module_voting', true)->firstOrFail();
        if ($event->voting_closed) {
            return response()->json(['success' => false, 'message' => 'Voting is now closed.'], 422);
        }
        $request->validate(['candidate' => 'required|string|max:255']);
        $options = collect($event->voting_options ?? [])->pluck('name')->toArray();
        if (!in_array($request->candidate, $options)) {
            return response()->json(['success' => false, 'message' => 'Invalid candidate.'], 422);
        }
        $sessionKey = "voted_{$event->id}";
        if (session()->has($sessionKey)) {
            return response()->json(['success' => false, 'message' => 'You have already voted!'], 422);
        }
        Vote::create([
            'event_id'       => $event->id,
            'candidate_name' => $request->candidate,
            'candidate_slug' => \Illuminate\Support\Str::slug($request->candidate),
            'voter_session'  => session()->getId(),
            'voter_ip'       => $request->ip(),
        ]);

        session()->put($sessionKey, true);
        
        ActivityLog::record('vote.cast', ['candidate' => $request->candidate], $event->id);
        $tallies = Vote::where('event_id', $event->id)
            ->selectRaw('candidate_name, COUNT(*) as total')
            ->groupBy('candidate_name')->pluck('total', 'candidate_name');

        return response()->json(['success' => true, 'message' => ' Vote recorded!', 'tallies' => $tallies]);
    }
}
