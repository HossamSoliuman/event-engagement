<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'moderator') {
                $event = $user->moderatedEvents()->first();
                if ($event) return redirect()->route('moderator.dashboard', $event);
            }
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (!Auth::user()->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account has been deactivated.']);
            }
            $request->session()->regenerate();
            Auth::user()->update(['last_login_at' => now()]);
            ActivityLog::record('admin.login', ['email' => Auth::user()->email]);

            $user = Auth::user();
            if ($user->role === 'moderator') {
                $event = $user->moderatedEvents()->first();
                if ($event) return redirect()->route('moderator.dashboard', $event);
                return redirect()->route('admin.login')->withErrors(['email' => 'You have not been assigned to any event yet. Please contact your administrator.']);
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        ActivityLog::record('admin.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
