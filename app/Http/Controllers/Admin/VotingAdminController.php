<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Vote;

class VotingAdminController extends Controller
{
    public function index(Event $event)
    {
        $tallies    = $event->getVoteTallies();
        $totalVotes = $event->votes()->count();
        $votes      = $event->votes()->latest()->paginate(30);

        // Enrich tallies with percentages
        $tallies = array_map(function($t) use ($totalVotes) {
            $t['pct'] = $totalVotes > 0 ? round(($t['total'] / $totalVotes) * 100) : 0;
            return $t;
        }, $tallies);

        return view('admin.voting.index', compact('event', 'tallies', 'totalVotes', 'votes'));
    }

    public function close(Event $event)
    {
        $event->update(['voting_closed' => true]);
        ActivityLog::record('voting.closed', [], $event->id);
        return back()->with('success', '🔒 Voting closed.');
    }

    public function reopen(Event $event)
    {
        $event->update(['voting_closed' => false]);
        ActivityLog::record('voting.reopened', [], $event->id);
        return back()->with('success', '🔓 Voting reopened.');
    }

    public function reset(Event $event)
    {
        $event->votes()->delete();
        $event->update(['voting_closed' => false]);
        ActivityLog::record('voting.reset', [], $event->id);
        return back()->with('success', 'All votes cleared.');
    }

    public function export(Event $event)
    {
        $tallies = $event->getVoteTallies();
        $csv     = "Candidate,Votes,Percentage\n";
        $total   = array_sum(array_column($tallies, 'total'));
        foreach ($tallies as $t) {
            $pct = $total > 0 ? round(($t['total']/$total)*100, 1) : 0;
            $csv .= '"'.$t['candidate_name'].'",'.$t['total'].','.$pct."%\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"votes-{$event->slug}.csv\"",
        ]);
    }
}
