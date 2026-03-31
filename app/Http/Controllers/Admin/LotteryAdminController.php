<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\LotteryEntry;
use Illuminate\Http\Request;

class LotteryAdminController extends Controller
{
    public function index(Event $event, Request $request)
    {
        $search  = $request->get('search');
        $entries = $event->lotteryEntries()
            ->when($search, fn($q) => $q->where('name','like',"%$search%")->orWhere('phone','like',"%$search%"))
            ->latest()
            ->paginate(30);

        $winner      = $event->lotteryEntries()->where('is_winner', true)->first();
        $totalCount  = $event->lotteryEntries()->count();

        return view('admin.lottery.index', compact('event', 'entries', 'winner', 'totalCount', 'search'));
    }

    public function draw(Event $event)
    {
        if ($event->lottery_drawn) {
            return back()->with('error', 'Winner already drawn! Reset first to draw again.');
        }
        $winner = $event->drawLotteryWinner();
        if (!$winner) return back()->with('error', 'No entries to draw from.');

        ActivityLog::record('lottery.drawn', ['winner' => $winner->name, 'phone' => $winner->phone], $event->id);
        return redirect()->route('admin.lottery.index', $event)
            ->with('winner_drawn', $winner->id)
            ->with('success', "🎉 Winner drawn: {$winner->name}!");
    }

    public function reset(Event $event)
    {
        $event->resetLottery();
        ActivityLog::record('lottery.reset', [], $event->id);
        return back()->with('success', 'Lottery reset. You can draw again.');
    }

    public function destroy(LotteryEntry $entry)
    {
        $entry->delete();
        return back()->with('success', 'Entry removed.');
    }

    public function export(Event $event)
    {
        $entries = $event->lotteryEntries()->get();
        $csv     = "ID,Name,Phone,Email,Winner,Entry Time\n";
        foreach ($entries as $e) {
            $csv .= implode(',', [
                $e->id,
                '"'.$e->name.'"',
                '"'.$e->phone.'"',
                '"'.($e->email??'').'"',
                $e->is_winner ? 'YES' : 'No',
                $e->created_at->format('Y-m-d H:i'),
            ])."\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"lottery-{$event->slug}.csv\"",
        ]);
    }
}
