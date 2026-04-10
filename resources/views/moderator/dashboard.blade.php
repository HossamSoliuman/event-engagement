@extends('layouts.moderator')
@section('title', 'Dashboard')
@section('page-title', $event->name . ' — Dashboard')

@section('topbar-actions')
    <a href="{{ route('moderator.fotos.index', $event) }}" class="btn btn-primary btn-sm">
        Foto Queue
        @if($event->pending_count > 0)<span style="background:rgba(255,255,255,.25);padding:1px 6px;border-radius:8px;margin-left:2px">{{ $event->pending_count }}</span>@endif
    </a>
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall</a>
@endsection

@section('content')

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Pending Fotos</div>
        <div class="stat-value c-red">{{ $event->pending_count }}</div>
        <div class="stat-change">Awaiting moderation</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Approved Fotos</div>
        <div class="stat-value c-green">{{ $event->approved_count }}</div>
        <div class="stat-change">Total approved</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Uploads</div>
        <div class="stat-value">{{ $event->foto_uploads_count }}</div>
        <div class="stat-change">All submissions</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lottery Entries</div>
        <div class="stat-value c-gold">{{ $event->lottery_entries_count }}</div>
        <div class="stat-change">In the draw</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Votes Cast</div>
        <div class="stat-value c-mod">{{ $event->votes_count }}</div>
        <div class="stat-change">Total votes</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Members</div>
        <div class="stat-value c-green">{{ $event->memberships_count }}</div>
        <div class="stat-change">Signed up</div>
    </div>
</div>

<div class="grid-2" style="gap:16px">

<div class="card">
    <div class="card-header">
        <h3>Quick Actions</h3>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
        <a href="{{ route('moderator.fotos.index', $event) }}" class="btn btn-primary">
            Foto Queue
            @if($event->pending_count > 0)<span style="background:rgba(255,255,255,.2);padding:1px 8px;border-radius:10px;font-size:11px">{{ $event->pending_count }} pending</span>@endif
        </a>
        @if($event->module_lottery)
        <a href="{{ route('moderator.lottery.index', $event) }}" class="btn btn-secondary">Lottery</a>
        @endif
        @if($event->module_voting)
        <a href="{{ route('moderator.voting.index', $event) }}" class="btn btn-secondary">Voting</a>
        @endif
        @if($event->module_membership)
        <a href="{{ route('moderator.membership.index', $event) }}" class="btn btn-secondary">Members</a>
        @endif
    </div>
</div>

@if($onScreen)
<div class="card" style="border-color:var(--gold);background:rgba(255,215,0,.04)">
    <div class="card-header"><h3>Currently on Vidiwall</h3></div>
    <div class="card-body" style="display:flex;align-items:center;gap:16px">
        <img src="{{ $onScreen->thumbnail_url }}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid var(--gold)">
        <div style="flex:1">
            <div class="font-bold">{{ $onScreen->uploader_name ?? 'Anonymous' }}</div>
            <div class="text-muted text-sm">On screen since {{ $onScreen->displayed_at?->diffForHumans() }}</div>
        </div>
        <form method="POST" action="{{ route('moderator.fotos.remove-from-screen', [$event, $onScreen]) }}">
            @csrf <button class="btn btn-danger btn-sm">Remove</button>
        </form>
    </div>
</div>
@else
<div class="card">
    <div class="card-header"><h3>Vidiwall Status</h3></div>
    <div class="card-body">
        <div class="empty-state" style="padding:20px">
            <div class="text-muted text-sm">No photo currently on screen</div>
        </div>
    </div>
</div>
@endif

</div>

@if($event->module_voting && count($tallies))
<div class="card mt-3">
    <div class="card-header">
        <h3>Live Vote Tallies</h3>
        <div style="display:flex;gap:6px">
            <a href="{{ route('moderator.voting.index', $event) }}" class="btn btn-secondary btn-sm">Full Results</a>
            @if($event->voting_closed)
                <form method="POST" action="{{ route('moderator.voting.reopen', $event) }}">@csrf<button class="btn btn-outline btn-sm">Reopen</button></form>
            @else
                <form method="POST" action="{{ route('moderator.voting.close', $event) }}">@csrf<button class="btn btn-outline btn-sm">Close Voting</button></form>
            @endif
        </div>
    </div>
    <div class="card-body">
        @php $totalVotes = array_sum(array_column($tallies,'total')); @endphp
        @foreach($tallies as $i => $t)
        @php $pct = $totalVotes > 0 ? round(($t['total']/$totalVotes)*100) : 0; @endphp
        <div style="margin-bottom:12px">
            <div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:13px">
                <span style="font-weight:{{ $i===0 ? 700 : 400 }}">{{ $t['candidate_name'] }}</span>
                <span class="text-muted">{{ $t['total'] }} votes · {{ $pct }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill {{ $i===0 ? '' : 'green' }}" style="width:{{ $pct }}%"></div>
            </div>
        </div>
        @endforeach
        <div class="text-xs text-muted mt-2">Total: {{ $totalVotes }} votes {{ $event->voting_closed ? '· Closed' : '· Open' }}</div>
    </div>
</div>
@endif

@if($recentLog->count())
<div class="card mt-3">
    <div class="card-header"><h3>Recent Activity</h3></div>
    <div class="card-body" style="padding:10px 16px">
        @foreach($recentLog as $log)
        @php $label = str_replace(['.','-','_'],' ',$log->action); @endphp
        <div class="activity-item">
            <div class="activity-dot {{ str_contains($log->action,'foto') ? 'red' : (str_contains($log->action,'lottery') ? 'gold' : 'green') }}"></div>
            <div style="flex:1;font-size:12px">
                <span style="text-transform:capitalize">{{ $label }}</span>
                @if($log->meta && isset($log->meta['uploader'])) — {{ $log->meta['uploader'] }}@endif
            </div>
            <div class="text-xs text-muted">{{ $log->user?->name ?? 'Guest' }} · {{ $log->created_at->diffForHumans() }}</div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
