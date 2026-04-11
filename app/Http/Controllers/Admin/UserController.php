<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    public function index()
    {
        $users = User::where('email', '!=', 'hossamsoliuman@gmail.com')->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }
    
    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:superadmin,admin,moderator',
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        ActivityLog::record('user.created', ['name' => $user->name, 'role' => $user->role]);
        return redirect()->route('admin.users.index')->with('success', 'User created!');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'      => 'required|in:superadmin,admin,moderator',
            'password'  => 'nullable|string|min:8|confirmed',
        ]);

        if (empty($data['password'])) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);

        $data['is_active'] = $request->boolean('is_active');
        $user->update($data);
        ActivityLog::record('user.updated', ['name' => $user->name]);
        return redirect()->route('admin.users.index')->with('success', 'User updated!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) return back()->with('error', 'Cannot delete your own account.');
        $user->delete();
        ActivityLog::record('user.deleted', ['name' => $user->name]);
        return back()->with('success', 'User deleted.');
    }
}
