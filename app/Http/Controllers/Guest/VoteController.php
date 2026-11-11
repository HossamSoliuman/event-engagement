<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackPageView;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VoteController extends Controller
{
    /**
     * Whether guests can vote right now and how long for, so an open phone
     * follows the vote without a reload. A phone that already voted also gets
     * its choice and the live tallies; after a new vote it gets neither.
     */
    public function status(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->where('module_voting', true)->firstOrFail();
        $event->closeVotingWhenTimeIsUp();
        $choice = $this->choiceOf($event);

        return response()->json([
            'open' => ! $event->voting_closed,
            'ends_in_ms' => $event->votingMsLeft(),
            'voted' => $choice !== null,
            'choice' => $choice,
            'tallies' => $choice !== null ? $this->talliesOf($event) : null,
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->where('module_voting', true)->firstOrFail();
        $event->closeVotingWhenTimeIsUp();
        if ($event->voting_closed) {
            return response()->json(['success' => false, 'message' => 'Voting is now closed.'], 422);
        }
        $request->validate(['candidate' => 'required|string|max:255']);
        $options = collect($event->voting_options ?? [])->pluck('name')->toArray();
        if (! in_array($request->candidate, $options)) {
            return response()->json(['success' => false, 'message' => 'Invalid candidate.'], 422);
        }
        if ($this->choiceOf($event) !== null) {
            return response()->json(['success' => false, 'message' => 'You have already voted!'], 422);
        }
        $vote = Vote::create([
            'event_id' => $event->id,
            'visitor_id' => $request->cookie(TrackPageView::COOKIE),
            'candidate_name' => $request->candidate,
            'candidate_slug' => Str::slug($request->candidate),
            'voter_session' => session()->getId(),
            'voter_ip' => $request->ip(),
        ]);

        session()->put("voted_{$event->id}", $vote->id);

        ActivityLog::record('vote.cast', ['candidate' => $request->candidate], $event->id);

        return response()->json(['success' => true, 'message' => ' Vote recorded!', 'tallies' => $this->talliesOf($event)]);
    }

    /**
     * The athlete this guest voted for in the current vote. A new vote deletes
     * the votes, so the guest can vote again.
     */
    private function choiceOf(Event $event): ?string
    {
        $voteId = session()->get("voted_{$event->id}");

        return is_int($voteId) ? $event->votes()->whereKey($voteId)->value('candidate_name') : null;
    }

    /**
     * @return Collection<string, int>
     */
    private function talliesOf(Event $event): Collection
    {
        return Vote::where('event_id', $event->id)
            ->selectRaw('candidate_name, COUNT(*) as total')
            ->groupBy('candidate_name')->pluck('total', 'candidate_name');
    }
}
