@extends('layouts.admin')
@section('title', $event->name)
@section('page-title', $event->name)

@section('topbar-actions')
    <a href="{{ route('admin.events.edit',$event) }}" class="btn btn-secondary btn-sm">✏ Edit</a>
    <form method="POST" action="{{ route('admin.events.generate-qr',$event) }}" style="display:inline">
        @csrf <button class="btn btn-outline btn-sm">↻ QR</button>
    </form>
    <a href="{{ route('vidiwall.show',$event->slug) }}" target="_blank" class="btn btn-gold btn-sm">📺 Vidiwall</a>
@endsection

@section('content')

{{-- Stats row --}}
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
    <div class="stat-card"><div class="stat-label">Pending Fotos</div><div class="stat-value c-red">{{ $event->pending_count }}</div></div>
    <div class="stat-card"><div class="stat-label">Approved Fotos</div><div class="stat-value c-green">{{ $event->approved_count }}</div></div>
    <div class="stat-card"><div class="stat-label">Total Uploads</div><div class="stat-value">{{ $event->foto_uploads_count }}</div></div>
    <div class="stat-card"><div class="stat-label">Lottery Entries</div><div class="stat-value c-gold">{{ $event->lottery_entries_count }}</div></div>
    <div class="stat-card"><div class="stat-label">Votes</div><div class="stat-value c-blue">{{ $event->votes_count }}</div></div>
    <div class="stat-card"><div class="stat-label">Members</div><div class="stat-value">{{ $event->memberships_count }}</div></div>
</div>

<div class="grid-2" style="gap:16px;margin-bottom:16px">

{{-- QR Code --}}
<div class="card">
    <div class="card-header"><h3>QR Code & Links</h3></div>
    <div class="card-body" style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap">
        @if($event->qr_code_path)
        <div style="text-align:center;flex-shrink:0">
            <img src="{{ $event->qr_code_url }}" style="width:150px;height:150px;background:#fff;padding:8px;border-radius:10px;display:block">
            <a href="{{ $event->qr_code_url }}" download class="btn btn-secondary btn-sm" style="margin-top:8px;width:150px;justify-content:center">⬇ Download</a>
        </div>
        @endif
        <div style="flex:1;min-width:160px">
            <div class="form-label">Guest URL</div>
            <div style="background:var(--dark);border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:12px;font-family:monospace;margin-bottom:12px;word-break:break-all">{{ $event->getGuestUrl() }}</div>
            <div class="form-label">Vidiwall URL</div>
            <div style="background:var(--dark);border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:12px;font-family:monospace;margin-bottom:12px;word-break:break-all">{{ url('/screen/'.$event->slug) }}</div>
            <div style="display:flex;gap:7px;flex-wrap:wrap">
                <a href="{{ route('event.landing',$event->slug) }}" target="_blank" class="btn btn-outline btn-sm">📱 Guest Page ↗</a>
                <a href="{{ route('vidiwall.show',$event->slug) }}" target="_blank" class="btn btn-gold btn-sm">📺 Open Vidiwall ↗</a>
            </div>
        </div>
    </div>
</div>

{{-- Module Toggles --}}
<div class="card">
    <div class="card-header"><h3>Module Controls</h3></div>
    <div class="card-body" style="padding:0">
        @foreach([
            ['fotobomb','📷','Foto Bomb','Let guests upload photos for vidiwall'],
            ['lottery','🎰','Lottery','Guests enter the prize draw'],
            ['voting','🏆','Athlete Vote','Vote for athlete of the day'],
            ['membership','⭐','Membership','Guest community sign-ups'],
        ] as [$key,$ico,$label,$desc])
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border)">
            <div>
                <div style="font-weight:600;font-size:13px">{{ $ico }} {{ $label }}</div>
                <div class="text-muted text-xs">{{ $desc }}</div>
            </div>
            <label class="toggle">
                <input type="checkbox" {{ $event->{'module_'.$key} ? 'checked' : '' }}
                    onchange="toggleModule('{{ $key }}',this,{{ $event->id }})">
                <span class="toggle-slider"></span>
            </label>
        </div>
        @endforeach
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px">
            <div>
                <div style="font-weight:600;font-size:13px">🌐 Event Active</div>
                <div class="text-muted text-xs">Guests can access the event page</div>
            </div>
            <span class="badge {{ $event->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $event->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
    </div>
</div>

</div>{{-- /grid --}}

{{-- Live Vote Tallies --}}
@if($event->module_voting && count($tallies))
<div class="card mb-3">
    <div class="card-header">
        <h3>🏆 Live Vote Tallies</h3>
        <div style="display:flex;gap:6px">
            <a href="{{ route('admin.voting.index',$event) }}" class="btn btn-secondary btn-sm">Full Results</a>
            @if($event->voting_closed)
                <form method="POST" action="{{ route('admin.voting.reopen',$event) }}">@csrf<button class="btn btn-outline btn-sm">🔓 Reopen</button></form>
            @else
                <form method="POST" action="{{ route('admin.voting.close',$event) }}">@csrf<button class="btn btn-outline btn-sm">🔒 Close Voting</button></form>
            @endif
        </div>
    </div>
    <div class="card-body">
        @php $totalVotes = array_sum(array_column($tallies,'total')); @endphp
        @foreach($tallies as $i => $t)
        @php $pct = $totalVotes > 0 ? round(($t['total']/$totalVotes)*100) : 0; @endphp
        <div style="margin-bottom:12px">
            <div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:13px">
                <span style="font-weight:{{ $i===0 ? 700 : 400 }}">{{ $i===0 ? '🥇' : ($i===1 ? '🥈' : '🥉') }} {{ $t['candidate_name'] }}</span>
                <span class="text-muted">{{ $t['total'] }} votes · {{ $pct }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill {{ $i===0 ? '' : 'green' }}" style="width:{{ $pct }}%"></div>
            </div>
        </div>
        @endforeach
        <div class="text-xs text-muted mt-2">Total: {{ $totalVotes }} votes {{ $event->voting_closed ? '· 🔒 Closed' : '· 🔓 Open' }}</div>
    </div>
</div>
@endif

{{-- Currently on screen --}}
@if($onScreen)
<div class="card mb-3" style="border-color:var(--gold);background:rgba(255,215,0,.04)">
    <div class="card-header"><h3>📺 Currently on Vidiwall</h3></div>
    <div class="card-body" style="display:flex;align-items:center;gap:16px">
        <img src="{{ $onScreen->thumbnail_url }}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid var(--gold)">
        <div style="flex:1">
            <div class="font-bold">{{ $onScreen->uploader_name ?? 'Anonymous' }}</div>
            <div class="text-muted text-sm">On screen since {{ $onScreen->displayed_at?->diffForHumans() }}</div>
        </div>
        <form method="POST" action="{{ route('admin.fotos.remove-from-screen',$onScreen) }}">
            @csrf <button class="btn btn-danger btn-sm">Remove</button>
        </form>
    </div>
</div>
@endif

{{-- Quick Actions --}}
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
    <a href="{{ route('admin.fotos.index',$event) }}" class="btn btn-primary">
        📷 Foto Queue @if($event->pending_count > 0)<span style="background:rgba(255,255,255,.2);padding:1px 8px;border-radius:10px;font-size:11px">{{ $event->pending_count }}</span>@endif
    </a>
    <a href="{{ route('admin.lottery.index',$event) }}" class="btn btn-secondary">🎰 Lottery</a>
    <a href="{{ route('admin.voting.index',$event) }}" class="btn btn-secondary">🏆 Voting</a>
    <a href="{{ route('admin.membership.index',$event) }}" class="btn btn-secondary">⭐ Members</a>
</div>

{{-- Recent Activity --}}
@if($recentLog->count())
<div class="card">
    <div class="card-header"><h3>Recent Activity</h3></div>
    <div class="card-body" style="padding:10px 16px">
        @foreach($recentLog as $log)
        @php $label = str_replace(['.','-','_'],' ',$log->action); @endphp
        <div class="activity-item">
            <div class="activity-dot"></div>
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
