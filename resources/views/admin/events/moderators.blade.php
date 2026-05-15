@extends('layouts.admin')
@section('title', 'Moderators — ' . $event->name)
@section('page-title', 'Moderators — ' . $event->name)

@section('topbar-actions')
    <a href="{{ route('admin.events.show', $event) }}" class="btn btn-secondary btn-sm">Back to Event</a>
@endsection

@section('content')

<div class="grid-2" style="gap:16px;align-items:start">

<div class="card">
    <div class="card-header">
        <h3>Assigned Moderators</h3>
        <span class="text-muted text-xs">{{ $assigned->count() }} assigned</span>
    </div>
    @forelse($assigned as $user)
    <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid rgba(255,255,255,.03)">
        <img src="{{ $user->avatar_url }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0">
        <div style="flex:1;min-width:0">
            <div class="font-bold truncate" style="font-size:13px">{{ $user->name }}</div>
            <div class="text-muted text-xs">{{ $user->email }}</div>
        </div>
        <div style="display:flex;gap:6px;align-items:center">
            <a href="{{ route('moderator.dashboard', $event) }}" target="_blank" class="btn btn-ghost btn-sm">Portal <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
            <form method="POST" action="{{ route('admin.events.moderators.destroy', [$event, $user]) }}"
                  data-confirm="Remove {{ $user->name }} from this event?">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm">Remove</button>
            </form>
        </div>
    </div>
    @empty
    <div class="card-body">
        <div class="empty-state" style="padding:30px">
            <h3 style="font-size:16px">No moderators assigned</h3>
            <div class="text-muted text-sm" style="margin-top:4px">Add a moderator from the panel on the right.</div>
        </div>
    </div>
    @endforelse
</div>

<div class="card">
    <div class="card-header"><h3>Assign a Moderator</h3></div>
    <div class="card-body">
        @if($available->count())
        <form method="POST" action="{{ route('admin.events.moderators.store', $event) }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Select User</label>
                <select name="user_id" class="form-control" required>
                    <option value="">— Choose a moderator —</option>
                    @foreach($available as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                <div class="form-hint">Only users with the "moderator" role are listed here. Create users first via Admin → Users.</div>
            </div>
            <button type="submit" class="btn btn-primary">Assign Moderator</button>
        </form>
        @else
        <div class="empty-state" style="padding:20px">
            <div class="text-muted text-sm">
                All moderator-role users are already assigned, or no moderator accounts exist yet.
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-secondary btn-sm" style="margin-top:12px">Create User Account</a>
        </div>
        @endif
    </div>
</div>

</div>

<div class="card mt-3" style="background:rgba(99,102,241,.04);border-color:rgba(99,102,241,.2)">
    <div class="card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
            <div style="font-weight:700;font-size:13px;color:#818cf8;margin-bottom:4px">Moderator Portal URL</div>
            <div style="font-family:monospace;font-size:12px;background:var(--dark);border:1px solid var(--border);border-radius:6px;padding:8px 12px;word-break:break-all">
                {{ url('/moderator/' . $event->id) }}
            </div>
            <div class="form-hint" style="margin-top:6px">Share this URL with your assigned moderators. They log in with their own credentials.</div>
        </div>
        <a href="{{ route('moderator.dashboard', $event) }}" target="_blank" class="btn btn-outline">Preview Portal <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.querySelectorAll('form[data-confirm]').forEach(f => {
    f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});
</script>
@endpush
