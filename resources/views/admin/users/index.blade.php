@extends('layouts.admin')
@section('title','Users')
@section('page-title','👥 Admin Users')
@section('topbar-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">+ New User</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3>All Admin Users ({{ $users->total() }})</h3></div>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>User</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($users as $user)
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <img src="{{ $user->avatar_url }}" class="avatar-sm">
                    <div>
                        <div class="font-bold" style="font-size:13px">{{ $user->name }}
                            @if($user->id===auth()->id()) <span style="font-size:10px;color:var(--muted)">(you)</span>@endif
                        </div>
                        <div class="text-muted text-xs">{{ $user->email }}</div>
                    </div>
                </div>
            </td>
            <td><span class="badge badge-{{ $user->role }}">{{ ucfirst($user->role) }}</span></td>
            <td class="text-muted text-xs">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
            <td><span class="badge {{ $user->is_active ? 'badge-active':'badge-inactive' }}">{{ $user->is_active ? 'Active':'Inactive' }}</span></td>
            <td>
                <div style="display:flex;gap:5px">
                    <a href="{{ route('admin.users.edit',$user) }}" class="btn btn-secondary btn-sm">Edit</a>
                    @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy',$user) }}" onsubmit="return confirm('Delete user?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="5"><div class="empty-state" style="padding:30px">No users found.</div></td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    @if($users->hasPages())<div style="padding:12px 18px">{{ $users->links() }}</div>@endif
</div>
@endsection
