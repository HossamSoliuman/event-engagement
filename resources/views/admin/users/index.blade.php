@extends('layouts.admin')
@section('title','Users')
@section('page-title','Users')

@section('topbar-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">+ New User</a>
@endsection

@section('content')

@php
    $moderators = $users->filter(fn($u) => $u->role === 'moderator');
    $admins     = $users->filter(fn($u) => in_array($u->role, ['admin','superadmin']));
    $events     = \App\Models\Event::withCount('moderators')->latest()->get();
@endphp

<div class="grid-2" style="gap:16px;align-items:start;margin-bottom:20px">

<div class="card">
    <div class="card-header">
        <h3>Moderators <span class="text-muted" style="font-weight:400;font-size:12px">{{ $moderators->count() }} accounts</span></h3>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">+ Create</a>
    </div>
    @forelse($moderators as $user)
    <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid rgba(255,255,255,.03)">
        <img src="{{ $user->avatar_url }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid rgba(99,102,241,.3);flex-shrink:0">
        <div style="flex:1;min-width:0">
            <div class="font-bold truncate" style="font-size:13px">{{ $user->name }}</div>
            <div class="text-muted text-xs">{{ $user->email }}</div>
            @php
                $assignedEvents = $user->moderatedEvents;
            @endphp
            @if($assignedEvents->count())
            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:5px">
                @foreach($assignedEvents as $ev)
                <a href="{{ route('admin.events.moderators', $ev) }}"
                   style="font-size:10px;background:rgba(99,102,241,.12);color:#818cf8;border:1px solid rgba(99,102,241,.2);border-radius:4px;padding:2px 7px;text-decoration:none;transition:opacity .15s"
                   onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
                    {{ Str::limit($ev->name, 22) }}
                </a>
                @endforeach
            </div>
            @else
            <div class="text-xs text-muted" style="margin-top:4px">Not assigned to any event</div>
            @endif
        </div>
        <div style="display:flex;gap:5px;flex-shrink:0">
            <span class="badge {{ $user->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost btn-sm">Edit</a>
            @if($user->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm">Delete</button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="card-body">
        <div class="empty-state" style="padding:30px">
            <h3 style="font-size:15px">No moderator accounts</h3>
            <div class="text-muted text-sm" style="margin-top:4px">Create a user with the "Moderator" role to get started.</div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm" style="margin-top:12px">+ Create Moderator</a>
        </div>
    </div>
    @endforelse
</div>

<div>
    <div class="card mb-3">
        <div class="card-header">
            <h3>Event Assignments</h3>
            <span class="text-muted text-xs">Click an event to manage its moderators</span>
        </div>
        @forelse($events as $event)
        <div style="display:flex;align-items:center;gap:12px;padding:11px 20px;border-bottom:1px solid rgba(255,255,255,.03)">
            <div style="width:8px;height:8px;border-radius:50%;background:{{ $event->is_active ? 'var(--green)' : 'var(--border)' }};flex-shrink:0"></div>
            <div style="flex:1;min-width:0">
                <div class="font-bold truncate" style="font-size:13px">{{ $event->name }}</div>
                @if($event->moderators_count > 0)
                <div class="text-xs" style="color:#818cf8;margin-top:2px">{{ $event->moderators_count }} moderator{{ $event->moderators_count > 1 ? 's' : '' }} assigned</div>
                @else
                <div class="text-xs text-muted" style="margin-top:2px">No moderators</div>
                @endif
            </div>
            <a href="{{ route('admin.events.moderators', $event) }}" class="btn btn-ghost btn-sm">Manage</a>
        </div>
        @empty
        <div class="card-body">
            <div class="text-muted text-sm">No events yet.</div>
        </div>
        @endforelse
    </div>

    <div class="card">
        <div class="card-header"><h3>Admins & Super Admins</h3></div>
        @forelse($admins as $user)
        <div style="display:flex;align-items:center;gap:12px;padding:11px 20px;border-bottom:1px solid rgba(255,255,255,.03)">
            <img src="{{ $user->avatar_url }}" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0">
            <div style="flex:1;min-width:0">
                <div class="font-bold truncate" style="font-size:13px">
                    {{ $user->name }}
                    @if($user->id === auth()->id()) <span class="text-muted" style="font-size:10px">(you)</span>@endif
                </div>
                <div class="text-muted text-xs">{{ $user->email }}</div>
            </div>
            <span class="badge badge-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost btn-sm">Edit</a>
            @if($user->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete?')">
                @csrf @method('DELETE') <button class="btn btn-danger btn-sm">Delete</button>
            </form>
            @endif
        </div>
        @empty
        <div class="card-body"><div class="text-muted text-sm">No admin accounts.</div></div>
        @endforelse
    </div>
</div>

</div>

<div class="card" style="background:rgba(99,102,241,.04);border-color:rgba(99,102,241,.2)">
    <div class="card-body">
        <div style="font-weight:700;font-size:13px;color:#818cf8;margin-bottom:8px">How Moderators Work</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
            <div style="font-size:12px;color:var(--muted);line-height:1.6">
                <strong style="color:var(--text)">Step 1</strong> — Create a user account with the <em>Moderator</em> role using the "+ Create" button above.
            </div>
            <div style="font-size:12px;color:var(--muted);line-height:1.6">
                <strong style="color:var(--text)">Step 2</strong> — Go to any event and click <em>Moderators</em> to assign the moderator to that event.
            </div>
            <div style="font-size:12px;color:var(--muted);line-height:1.6">
                <strong style="color:var(--text)">Step 3</strong> — Share the Moderator Portal URL with them. They log in at <code>/admin/login</code> and are redirected to their portal.
            </div>
            <div style="font-size:12px;color:var(--muted);line-height:1.6">
                <strong style="color:var(--text)">Access</strong> — Moderators can manage fotos, lottery, voting and members — only for their assigned events.
            </div>
        </div>
    </div>
</div>

@if($users->hasPages())
<div style="margin-top:16px">{{ $users->links() }}</div>
@endif

@endsection
