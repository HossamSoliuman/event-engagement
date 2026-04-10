<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;

class ModeratorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        $user = auth()->user();

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->role !== 'moderator') {
            abort(403, 'Unauthorised.');
        }

        $event = $request->route('event');

        if (!$event instanceof Event) {
            $event = Event::where('slug', $event)->first();
        }

        if (!$event || !$event->hasModerator($user)) {
            abort(403, 'You are not assigned as a moderator for this event.');
        }

        return $next($request);
    }
}
