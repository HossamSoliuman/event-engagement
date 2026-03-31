@extends('layouts.admin')
@section('title','Voting — '.$event->name)
@section('page-title','🏆 Voting Results')

@section('topbar-actions')
    <a href="{{ route('admin.voting.export',$event) }}" class="btn btn-ghost btn-sm">⬇ CSV</a>
    @if($event->voting_closed)
        <form method="POST" action="{{ route('admin.voting.reopen',$event) }}">@csrf<button class="btn btn-success btn-sm">🔓 Reopen Voting</button></form>
    @else
        <form method="POST" action="{{ route('admin.voting.close',$event) }}">@csrf<button class="btn btn-danger btn-sm">🔒 Close Voting</button></form>
    @endif
    <a href="{{ route('admin.events.show',$event) }}" class="btn btn-secondary btn-sm">← Event</a>
@endsection

@section('content')

{{-- Status Banner --}}
@if($event->voting_closed)
<div class="alert alert-info">🔒 Voting is closed. Guests can no longer cast votes.</div>
@endif

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-label">Total Votes</div>
        <div class="stat-value c-blue">{{ $totalVotes }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Candidates</div>
        <div class="stat-value">{{ count($tallies) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Status</div>
        <div class="stat-value" style="font-size:16px;padding-top:8px">{{ $event->voting_closed ? '🔒 Closed' : '🔓 Open' }}</div>
    </div>
</div>

{{-- Results Podium --}}
@if(count($tallies))
<div class="card mb-3">
    <div class="card-header">
        <h3>Live Results</h3>
        @if(!$event->voting_closed)
        <span style="font-size:11px;color:var(--muted)" id="liveIndicator">● Auto-refreshes every 15s</span>
        @endif
    </div>
    <div class="card-body">
        @foreach($tallies as $i => $t)
        <div style="margin-bottom:18px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--red),var(--gold));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:15px;flex-shrink:0">
                        {{ $i===0?'🥇':($i===1?'🥈':'🥉') }}
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:15px">{{ $t['candidate_name'] }}</div>
                        @php $opt = collect($event->voting_options??[])->firstWhere('name',$t['candidate_name']); @endphp
                        @if($opt && $opt['position'])<div class="text-muted text-xs">{{ $opt['position'] }}</div>@endif
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:{{ $i===0?'var(--gold)':'var(--text)' }}">{{ $t['total'] }}</div>
                    <div class="text-muted text-xs">{{ $t['pct'] }}%</div>
                </div>
            </div>
            <div class="progress-bar" style="height:10px">
                <div class="progress-fill {{ $i===0?'gold':($i===1?'green':'') }}"
                     style="width:0%" data-width="{{ $t['pct'] }}%"
                     id="bar-{{ $i }}"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Podium Visual --}}
@if(count($tallies) >= 2)
<div class="card mb-3">
    <div class="card-header"><h3>Podium</h3></div>
    <div class="card-body">
        <div style="display:flex;align-items:flex-end;justify-content:center;gap:12px;height:140px;padding-bottom:0">
            @php
                $podium = [
                    1 => $tallies[1] ?? null,
                    0 => $tallies[0] ?? null,
                    2 => $tallies[2] ?? null,
                ];
            @endphp
            @foreach($podium as $pos => $t)
            @if($t)
            @php $heights = [0=>120, 1=>80, 2=>60]; $medals=['🥇','🥈','🥉']; $colors=['#FFD700','#C0C0C0','#CD7F32']; @endphp
            <div style="display:flex;flex-direction:column;align-items:center;gap:6px;width:100px">
                <div class="text-xs font-bold" style="color:{{ $colors[$pos] }};text-align:center">{{ Str::words($t['candidate_name'],1,'') }}</div>
                <div style="font-size:20px">{{ $medals[$pos] }}</div>
                <div style="width:100%;height:{{ $heights[$pos] }}px;background:{{ $colors[$pos] }};border-radius:6px 6px 0 0;display:flex;align-items:center;justify-content:center;color:#000;font-family:'Syne',sans-serif;font-weight:800;font-size:18px">
                    {{ $t['total'] }}
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Danger Zone --}}
<div class="card" style="border-color:rgba(239,68,68,.25)">
    <div class="card-header"><h3 style="color:var(--red)">⚠ Danger Zone</h3></div>
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <div class="font-bold">Reset All Votes</div>
            <div class="text-muted text-xs">This permanently deletes all {{ $totalVotes }} votes and cannot be undone.</div>
        </div>
        <form method="POST" action="{{ route('admin.voting.reset',$event) }}"
              onsubmit="return confirm('Delete ALL votes? This cannot be undone.')">
            @csrf <button class="btn btn-danger btn-sm">🗑 Reset All Votes</button>
        </form>
    </div>
</div>

@else
<div class="empty-state">
    <div class="empty-icon">🗳</div>
    <h3>No votes yet</h3>
    <p>Make sure voting is enabled and candidates are configured in the event settings.</p>
</div>
@endif

@endsection

@push('scripts')
<script>
// Animate bars in
window.addEventListener('load', () => {
    document.querySelectorAll('.progress-fill[data-width]').forEach((el, i) => {
        setTimeout(() => { el.style.width = el.dataset.width; }, i * 150 + 200);
    });
});

// Auto-refresh tallies
@if(!$event->voting_closed)
setInterval(() => window.location.reload(), 15000);
@endif
</script>
@endpush
