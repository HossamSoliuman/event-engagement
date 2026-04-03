<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        $user               = auth()->user();
        $privacy_policy_url   = SiteSetting::privacyPolicyUrl();
        $privacyPolicyPath  = SiteSetting::get('privacy_policy_path');


        return view('admin.settings.index', compact('user', 'privacy_policy_url', 'privacyPolicyPath'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $type = $request->input('type');

        if ($type === 'profile') {
            $data = $request->validate([
                'name'   => 'required|string|max:255',
                'email'  => 'required|email|unique:users,email,' . $user->id,
                'avatar' => 'nullable|image|max:1024',
            ]);
            if ($request->hasFile('avatar')) {
                $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
            }
            $user->update($data);
            return back()->with('success', 'Profile updated!');
        }

        if ($type === 'password') {
            $request->validate([
                'current_password' => 'required',
                'password'         => 'required|min:8|confirmed',
            ]);
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            $user->update(['password' => Hash::make($request->password)]);
            ActivityLog::record('settings.password_changed');
            return back()->with('success', 'Password changed!');
        }

        if ($type === 'privacy_policy') {
            $request->validate([
                'privacy_policy_file' => 'required|mimes:pdf,html|max:5120',
            ]);
            $path = $request->file('privacy_policy_file')
                ->storeAs('legal', 'privacy-policy.' . $request->file('privacy_policy_file')->extension(), 'public');
            SiteSetting::set('privacy_policy_path', $path);
            ActivityLog::record('settings.privacy_policy_uploaded');
            return back()->with('success', 'Privacy policy uploaded and is now live site-wide!');
        }

        return back();
    }
}
