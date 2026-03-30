<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FotoUpload;

class DashboardController extends Controller
{
    public function index()
    {
        $events       = Event::withCount(['fotoUploads', 'lotteryEntries', 'votes', 'memberships'])->get();
        $activeEvents = Event::where('is_active', true)->withCount(['fotoUploads', 'lotteryEntries', 'votes', 'memberships'])->get();
        $pendingFotos = FotoUpload::where('status', 'pending')->count();
        $activeEvent  = Event::where('is_active', true)->first();

        return view('admin.dashboard', compact('activeEvents', 'events', 'pendingFotos', 'activeEvent'));
    }
}
