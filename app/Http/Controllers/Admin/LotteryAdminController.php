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
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%")->orWhere('phone', 'like', "%$search%"))
            ->latest()
            ->paginate(30);

        $winner      = $event->lotteryEntries()->where('is_winner', true)->first();
        $totalCount  = $event->lotteryEntries()->count();

        $lotteryExtraKeys = [];
        foreach ($entries as $entry) {
            if (!empty($entry->extra_fields) && is_array($entry->extra_fields)) {
                $lotteryExtraKeys = array_keys($entry->extra_fields);
                break;
            }
        }
        return view('admin.lottery.index', compact('event', 'entries', 'winner', 'totalCount', 'search', 'lotteryExtraKeys'));
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

        // Collect all extra field keys across all entries
        $extraKeys = [];
        foreach ($entries as $e) {
            if (!empty($e->extra_fields)) {
                foreach (array_keys($e->extra_fields) as $k) {
                    if (!in_array($k, $extraKeys)) $extraKeys[] = $k;
                }
            }
        }

        $headers = array_merge(['ID', 'Name', 'Phone', 'Email'], $extraKeys, ['Winner', 'Entry Time']);
        $csv     = implode(',', array_map(fn($h) => '"' . $h . '"', $headers)) . "\n";

        foreach ($entries as $e) {
            $row = [
                $e->id,
                '"' . $e->name . '"',
                '"' . $e->phone . '"',
                '"' . ($e->email ?? '') . '"',
            ];
            foreach ($extraKeys as $k) {
                $row[] = '"' . ($e->extra_fields[$k] ?? '') . '"';
            }
            $row[] = $e->is_winner ? 'YES' : 'No';
            $row[] = $e->created_at->format('Y-m-d H:i');
            $csv  .= implode(',', $row) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"lottery-{$event->slug}.csv\"",
        ]);
    }
}
