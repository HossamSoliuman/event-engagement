<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isModerator()) {
            abort(403, 'Unauthorised.');
        }
        return $next($request);
    }
}
