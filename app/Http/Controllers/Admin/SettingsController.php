<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return view('admin.settings.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $type = $request->input('type');

        if ($type === 'profile') {
            $data = $request->validate([
                'name'   => 'required|string|max:255',
                'email'  => 'required|email|unique:users,email,'.$user->id,
                'avatar' => 'nullable|image|max:1024',
            ]);
            if ($request->hasFile('avatar')) {
                $data['avatar_path'] = $request->file('avatar')->store('avatars','public');
            }
            $user->update($data);
            return back()->with('success', 'Profile updated!');
        }

        if ($type === 'password') {
            $request->validate([
                'current_password'      => 'required',
                'password'              => 'required|min:8|confirmed',
            ]);
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            $user->update(['password' => Hash::make($request->password)]);
            ActivityLog::record('settings.password_changed');
            return back()->with('success', 'Password changed!');
        }

        return back();
    }
}
