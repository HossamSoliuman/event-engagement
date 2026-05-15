@extends('layouts.admin')
@section('title', 'Settings')
@section('page-title', '<i data-lucide="settings" class="lucide-icon"></i> Settings')

@section('content')
    <div style="max-width:560px">
        
        <div class="card mb-3" style="border-color:rgba(255,61,0,.2)">
            <div class="card-header">
                <h3><i data-lucide="lock" class="lucide-icon"></i> Site-wide Privacy Policy</h3>
            </div>
            <div class="card-body">
                @if (!empty($privacy_policy_url))
                    <div
                        style="display:flex;align-items:center;gap:12px;padding:12px;background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.2);border-radius:8px;margin-bottom:16px">
                        <div style="font-size:24px"><i data-lucide="check-circle" class="lucide-icon"></i></div>
                        <div style="flex:1">
                            <div class="font-bold" style="font-size:13px">Privacy policy is live</div>
                            <div class="text-muted text-xs">
                                Guests see a clickable link to this file in every consent checkbox across all events.
                            </div>
                        </div>
                        <a href="{{ $privacy_policy_url }}" target="_blank" class="btn btn-outline btn-sm">View File <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
                    </div>
                @else
                    <div
                        style="display:flex;align-items:center;gap:12px;padding:12px;background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:8px;margin-bottom:16px">
                        <div style="font-size:24px"><i data-lucide="alert-triangle" class="lucide-icon"></i></div>
                        <div>
                            <div class="font-bold" style="font-size:13px;color:#f87171">No privacy policy uploaded yet</div>
                            <div class="text-muted text-xs">Required by law. Upload your PDF or HTML file below.</div>
                        </div>
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="type" value="privacy_policy">
                    <div class="form-group">
                        <label class="form-label">Upload Privacy Policy File</label>
                        <input type="file" name="privacy_policy_file" class="form-control" accept=".pdf,.html">
                        <div class="form-hint">PDF or HTML · max 5MB · replaces any existing file · accessible at a
                            permanent public URL</div>
                    </div>
                    <button type="submit" class="btn btn-primary">⬆ Upload & Publish</button>
                </form>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h3>Profile</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="type" value="profile">
                    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
                        <img src="{{ $user->avatar_url }}"
                            style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--border)">
                        <div>
                            <label class="form-label">Profile Picture</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*" style="width:auto">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}"
                            required>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-top:1px solid var(--border)">
                        <div style="flex:1">
                            <div class="font-bold" style="font-size:13px">Role</div>
                            <div class="text-muted text-xs">Contact a super admin to change your role.</div>
                        </div>
                        <span class="badge badge-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:14px">Save Profile</button>
                </form>
            </div>
        </div>

        
        <div class="card mb-3">
            <div class="card-header">
                <h3>Change Password</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    <input type="hidden" name="type" value="password">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                        @error('current_password')
                            <div style="color:var(--red);font-size:12px;margin-top:4px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <h3><i data-lucide="smartphone" class="lucide-icon"></i> Mobile Admin API Token</h3>
            </div>
            <div class="card-body">
                <p class="text-muted text-sm" style="margin-bottom:14px">
                    Generate a token to authenticate the mobile admin app. Tokens are scoped to your account.
                </p>
                <div
                    style="background:var(--dark);border:1px solid var(--border);border-radius:8px;padding:12px;font-family:monospace;font-size:12px;margin-bottom:14px;word-break:break-all;color:var(--muted)">
                    POST /api/v1/login · Body: { email, password }
                </div>
                <div class="text-xs text-muted">The mobile app uses token auth. Log in via the API to receive your bearer
                    token.</div>
            </div>
        </div>

    </div>
@endsection
