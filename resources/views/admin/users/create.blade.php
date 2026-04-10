@extends('layouts.admin')
@section('title', isset($user) ? 'Edit '.$user->name : 'New User')
@section('page-title', isset($user) ? 'Edit User' : 'New User')

@section('topbar-actions')
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">Back to Users</a>
@endsection

@section('content')
<div style="max-width:500px">
<form method="POST" action="{{ isset($user) ? route('admin.users.update',$user) : route('admin.users.store') }}">
    @csrf @if(isset($user)) @method('PUT') @endif

    <div class="card mb-3">
        <div class="card-header"><h3>{{ isset($user) ? 'Edit User Account' : 'Create User Account' }}</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input name="name" class="form-control" value="{{ old('name',$user->name??'') }}" required placeholder="e.g. Sarah Johnson">
            </div>
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" value="{{ old('email',$user->email??'') }}" required placeholder="sarah@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Role *</label>
                <select name="role" class="form-control" id="roleSelect" onchange="updateRoleHint()">
                    @foreach(['moderator'=>'Moderator','admin'=>'Admin','superadmin'=>'Super Admin'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ old('role',$user->role??'moderator')===$val?'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
                <div class="form-hint" id="roleHint" style="margin-top:6px"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Password {{ isset($user) ? '(leave blank to keep current)' : '*' }}</label>
                <input type="password" name="password" class="form-control" {{ isset($user)?'':'required' }} minlength="8" placeholder="Min 8 characters">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat password">
            </div>
            @if(isset($user))
            <label class="form-check">
                <input type="checkbox" name="is_active" {{ $user->is_active?'checked':'' }}>
                Account is active
            </label>
            @endif
        </div>
    </div>

    @if(isset($user) && $user->role === 'moderator')
    @php $assignedEvents = $user->moderatedEvents; @endphp
    <div class="card mb-3" style="border-color:rgba(99,102,241,.25);background:rgba(99,102,241,.04)">
        <div class="card-header">
            <h3 style="color:#818cf8">Event Assignments</h3>
            <a href="{{ route('admin.events.index') }}" class="btn btn-ghost btn-sm">Manage via Events</a>
        </div>
        <div class="card-body">
            @if($assignedEvents->count())
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                @foreach($assignedEvents as $ev)
                <a href="{{ route('admin.events.moderators', $ev) }}"
                   style="display:flex;align-items:center;gap:6px;background:var(--dark);border:1px solid rgba(99,102,241,.25);border-radius:8px;padding:7px 12px;font-size:12px;color:#818cf8;text-decoration:none">
                    <span style="width:7px;height:7px;border-radius:50%;background:{{ $ev->is_active ? 'var(--green)' : 'var(--muted)' }};flex-shrink:0"></span>
                    {{ $ev->name }}
                </a>
                @endforeach
            </div>
            @else
            <div class="text-muted text-sm">Not assigned to any event yet. Go to an event and click "Moderators" to assign.</div>
            @endif
        </div>
    </div>
    @endif

    <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary">{{ isset($user)?'Save Changes':'Create User' }}</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
</div>
@endsection

@push('scripts')
<script>
const hints = {
    moderator: 'Can manage fotos, lottery, voting and members — but only for events they are explicitly assigned to. Cannot access admin settings or other events.',
    admin: 'Full access to all events and admin features. Cannot manage user accounts.',
    superadmin: 'Unrestricted access including user management and system settings.'
};
function updateRoleHint() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('roleHint').textContent = hints[role] || '';
}
updateRoleHint();
</script>
@endpush
