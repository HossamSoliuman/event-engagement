<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\LotteryEntry;
use App\Models\Membership;
use App\Models\Vote;

class DashboardController extends Controller
{
    public function index()
    {
        $events = Event::withCount([
            'fotoUploads', 'lotteryEntries', 'votes', 'memberships',
            'fotoUploads as pending_fotos_count' => fn ($q) => $q->where('status','pending'),
            'fotoUploads as approved_fotos_count' => fn ($q) => $q->where('status','approved'),
        ])->latest()->get();

        $activeEvent  = $events->where('is_active', true)->first();
        $pendingFotos = FotoUpload::where('status','pending')->count();

        // Global stats
        $totalStats = [
            'foto_uploads'    => FotoUpload::count(),
            'lottery_entries' => LotteryEntry::count(),
            'votes'           => Vote::count(),
            'memberships'     => Membership::count(),
        ];

        // Recent activity
        $recentActivity = ActivityLog::with('user','event')
            ->latest()->limit(15)->get();

        // Hourly upload activity (last 24h)
        $uploadActivity = FotoUpload::selectRaw("HOUR(created_at) as hour, COUNT(*) as total")
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('hour')
            ->pluck('total','hour')
            ->toArray();

        return view('admin.dashboard', compact(
            'events', 'activeEvent', 'pendingFotos',
            'totalStats', 'recentActivity', 'uploadActivity'
        ));
    }
}
