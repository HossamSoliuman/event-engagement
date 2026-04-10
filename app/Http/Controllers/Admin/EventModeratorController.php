<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

class EventModeratorController extends Controller
{
    public function index(Event $event)
    {
        $assigned = $event->moderators()->get();
        $available = User::where('role', 'moderator')
            ->whereNotIn('id', $assigned->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('admin.events.moderators', compact('event', 'assigned', 'available'));
    }

    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($data['user_id']);

        if (!in_array($user->role, ['moderator', 'admin', 'superadmin'])) {
            return back()->with('error', 'Selected user does not have the moderator role.');
        }

        if ($event->hasModerator($user)) {
            return back()->with('error', 'This user is already a moderator for this event.');
        }

        $event->moderators()->attach($user->id);
        ActivityLog::record('event.moderator_assigned', ['user' => $user->name], $event->id);

        return back()->with('success', "{$user->name} assigned as moderator.");
    }

    public function destroy(Event $event, User $user)
    {
        $event->moderators()->detach($user->id);
        ActivityLog::record('event.moderator_removed', ['user' => $user->name], $event->id);

        return back()->with('success', "{$user->name} removed from moderators.");
    }
}
