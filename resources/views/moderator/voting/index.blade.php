@extends('layouts.moderator')
@section('title', 'Voting')
@section('page-title', 'Voting — ' . $event->name)

@section('topbar-actions')
    <a href="{{ route('moderator.voting.export', $event) }}" class="btn btn-secondary btn-sm">Export CSV</a>
    @if($event->voting_closed)
        <form method="POST" action="{{ route('moderator.voting.reopen', $event) }}">@csrf<button class="btn btn-outline btn-sm">Reopen Voting</button></form>
    @else
        <form method="POST" action="{{ route('moderator.voting.close', $event) }}">@csrf<button class="btn btn-outline btn-sm">Close Voting</button></form>
    @endif
@endsection

@section('content')

<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
    <div class="stat-card">
        <div class="stat-label">Total Votes</div>
        <div class="stat-value c-mod">{{ $totalVotes }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Status</div>
        <div style="margin-top:10px">
            @if($event->voting_closed)
                <span class="badge badge-rejected" style="font-size:12px;padding:5px 12px">Closed</span>
            @else
                <span class="badge badge-approved" style="font-size:12px;padding:5px 12px">Open</span>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Candidates</div>
        <div class="stat-value">{{ count($tallies) }}</div>
    </div>
</div>

@if(count($tallies))
<div class="card mb-3">
    <div class="card-header">
        <h3>Live Tallies</h3>
        <form method="POST" action="{{ route('moderator.voting.reset', $event) }}" data-confirm="This will delete ALL votes. Are you sure?">
            @csrf <button class="btn btn-danger btn-sm">Reset All Votes</button>
        </form>
    </div>
    <div class="card-body">
        @foreach($tallies as $i => $t)
        <div style="margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px">
                <span style="font-weight:{{ $i===0 ? 700 : 500 }}">
                    {{ $i === 0 ? '1st' : ($i === 1 ? '2nd' : ($i === 2 ? '3rd' : ($i+1).'th')) }}
                    · {{ $t['candidate_name'] }}
                </span>
                <span class="text-muted">{{ $t['total'] }} votes · {{ $t['pct'] }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill {{ $i===0 ? '' : 'green' }}" style="width:{{ $t['pct'] }}%"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@else
<div class="card mb-3">
    <div class="card-body">
        <div class="empty-state" style="padding:40px">
            <h3>No votes yet</h3>
            <div class="text-muted text-sm" style="margin-top:6px">Votes will appear here as guests submit them.</div>
        </div>
    </div>
</div>
@endif

@if($votes->count())
<div class="card">
    <div class="card-header"><h3>Individual Votes</h3></div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Voted For</th>
                    <th>Session</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($votes as $vote)
                <tr>
                    <td class="text-muted">{{ $vote->id }}</td>
                    <td class="font-bold">{{ $vote->candidate_name }}</td>
                    <td class="text-muted text-xs">{{ Str::limit($vote->voter_session, 16) }}</td>
                    <td class="text-muted text-xs">{{ $vote->created_at->format('d M, H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($votes->hasPages())
    <div style="padding:16px 20px">{{ $votes->links() }}</div>
    @endif
</div>
@endif

@endsection

@push('scripts')
<script>
document.querySelectorAll('form[data-confirm]').forEach(f => {
    f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});
</script>
@endpush
