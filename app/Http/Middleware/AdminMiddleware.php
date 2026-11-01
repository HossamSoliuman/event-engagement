<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * The admin panel and API are for admins only. Moderators work in the
     * per-event moderator portal, so a page visit sends them there.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $next($request);
        }

        if ($user?->role === 'moderator' && $request->isMethod('GET') && ! $request->expectsJson()) {
            $event = $user->defaultModeratedEvent();

            if ($event) {
                return redirect()->route('moderator.dashboard', $event);
            }
        }

        abort(403, 'Unauthorised.');
    }
}
