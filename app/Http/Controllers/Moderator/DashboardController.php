<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\ActivityLog;
use App\Models\FotoUpload;

class DashboardController extends Controller
{
    public function index(Event $event)
    {
        $event->loadCount([
            'fotoUploads',
            'lotteryEntries',
            'votes',
            'memberships',
            'fotoUploads as pending_count'  => fn($q) => $q->where('status', 'pending'),
            'fotoUploads as approved_count' => fn($q) => $q->where('status', 'approved'),
        ]);

        $tallies    = $event->getVoteTallies();
        $onScreen   = $event->getOnScreenFotos()->first();
        $recentLog  = $event->activityLog()->with('user')->latest()->limit(10)->get();

        return view('moderator.dashboard', compact('event', 'tallies', 'onScreen', 'recentLog'));
    }
}
