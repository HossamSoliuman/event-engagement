@extends('layouts.admin')
@section('title','Dashboard')
@section('page-title','Dashboard')

@section('topbar-actions')
@if($activeEvent)
    <a href="{{ route('admin.fotos.index', $activeEvent) }}" class="btn btn-primary btn-sm">
        <i data-lucide="camera" class="lucide-icon"></i> Foto Queue @if($pendingFotos > 0)<span style="background:rgba(255,255,255,.25);padding:1px 6px;border-radius:8px;margin-left:2px">{{ $pendingFotos }}</span>@endif
    </a>
@else
    <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">+ New Event</a>
@endif
@endsection

@section('content')


@if($activeEvent)
<div class="card mb-3" style="background:linear-gradient(135deg,rgba(255,61,0,.1),rgba(10,10,24,.95));border-color:rgba(255,61,0,.25)">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
            <div style="font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--red);margin-bottom:6px">● LIVE NOW</div>
            <h2 style="font-size:24px;margin-bottom:4px">{{ $activeEvent->name }}</h2>
            <div class="text-muted text-sm">{{ $activeEvent->subtitle }}</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">
                <a href="{{ route('event.landing',$activeEvent->slug) }}" target="_blank" class="btn btn-outline btn-sm"><i data-lucide="smartphone" class="lucide-icon"></i> Guest Page</a>
                <a href="{{ route('vidiwall.show',$activeEvent->slug) }}" target="_blank" class="btn btn-gold btn-sm"><i data-lucide="monitor-play" class="lucide-icon"></i> Vidiwall</a>
                <a href="{{ route('admin.fotos.index',$activeEvent) }}" class="btn btn-primary btn-sm"><i data-lucide="camera" class="lucide-icon"></i> Moderate Fotos</a>
                <a href="{{ route('admin.events.show',$activeEvent) }}" class="btn btn-secondary btn-sm">Manage →</a>
            </div>
        </div>
        @if($activeEvent->qr_code_path)
        <div style="text-align:center;flex-shrink:0">
            <img src="{{ $activeEvent->qr_code_url }}" style="width:110px;height:110px;background:#fff;padding:6px;border-radius:10px;display:block">
            <div class="text-muted text-xs" style="margin-top:6px">Scan to preview</div>
        </div>
        @endif
    </div>
</div>
@endif


<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Pending Fotos</div>
        <div class="stat-value c-red">{{ $pendingFotos }}</div>
        <div class="stat-change">Awaiting moderation</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Uploads</div>
        <div class="stat-value">{{ number_format($totalStats['foto_uploads']) }}</div>
        <div class="stat-change">All events</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lottery Entries</div>
        <div class="stat-value c-gold">{{ number_format($totalStats['lottery_entries']) }}</div>
        <div class="stat-change">Across all events</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Votes Cast</div>
        <div class="stat-value c-blue">{{ number_format($totalStats['votes']) }}</div>
        <div class="stat-change">Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Members</div>
        <div class="stat-value c-green">{{ number_format($totalStats['memberships']) }}</div>
        <div class="stat-change">Signed up</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Events</div>
        <div class="stat-value">{{ $events->count() }}</div>
        <div class="stat-change">{{ $events->where('is_active',true)->count() }} active</div>
    </div>
</div>

<div class="grid-2" style="gap:16px">


<div class="card">
    <div class="card-header">
        <h3>All Events</h3>
        <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">+ New</a>
    </div>
    <div style="overflow:hidden;border-radius:0 0 12px 12px">
    @forelse($events as $event)
    <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.03)">
        <div style="width:8px;height:8px;border-radius:50%;background:{{ $event->is_active ? 'var(--green)' : 'var(--border)' }};flex-shrink:0"></div>
        <div style="flex:1;min-width:0">
            <div class="font-bold truncate" style="font-size:13px">{{ $event->name }}</div>
            <div class="text-muted text-xs">
                {{ $event->foto_uploads_count }} fotos · {{ $event->votes_count }} votes · {{ $event->memberships_count }} members
            </div>
        </div>
        <div style="display:flex;gap:5px">
            <a href="{{ route('admin.events.show',$event) }}" class="btn btn-ghost btn-sm">Manage</a>
            <a href="{{ route('admin.fotos.index',$event) }}" class="btn btn-ghost btn-sm">
                <i data-lucide="camera" class="lucide-icon"></i>@if($event->pending_fotos_count > 0) <span style="color:var(--red)">{{ $event->pending_fotos_count }}</span>@endif
            </a>
        </div>
    </div>
    @empty
    <div class="empty-state" style="padding:40px">
        <div>No events yet. <a href="{{ route('admin.events.create') }}" style="color:var(--red)">Create one →</a></div>
    </div>
    @endforelse
    </div>
</div>


<div class="card">
    <div class="card-header"><h3>Recent Activity</h3></div>
    <div class="card-body" style="padding:12px 16px">
    @forelse($recentActivity as $log)
    @php
        $dot = match(true) {
            str_contains($log->action,'foto') => 'red',
            str_contains($log->action,'lottery') => 'gold',
            str_contains($log->action,'vote') => 'green',
            default => ''
        };
        $icons = [
            'foto.approved'        => '<i data-lucide="check-circle" class="lucide-icon"></i>',
            'foto.pushed_to_screen'=> '<i data-lucide="monitor-play" class="lucide-icon"></i>',
            'foto.uploaded'        => '<i data-lucide="camera" class="lucide-icon"></i>',
            'foto.rejected'        => '<i data-lucide="x" class="lucide-icon"></i>',
            'lottery.drawn'        => '<i data-lucide="ticket" class="lucide-icon"></i>',
            'lottery.entered'      => '<i data-lucide="ticket" class="lucide-icon"></i>',
            'vote.cast'            => '<i data-lucide="check-square" class="lucide-icon"></i>',
            'membership.signup'    => '<i data-lucide="star" class="lucide-icon"></i>',
            'admin.login'          => '<i data-lucide="key" class="lucide-icon"></i>',
            'event.created'        => '<i data-lucide="calendar" class="lucide-icon"></i>',
            'event.updated'        => '<i data-lucide="edit-3" class="lucide-icon"></i>',
        ];
        $icon = $icons[$log->action] ?? '·';
        $label = str_replace(['.','-','_'],' ', $log->action);
    @endphp
    <div class="activity-item">
        <div class="activity-dot {{ $dot }}"></div>
        <div style="flex:1;min-width:0">
            <div style="font-size:12px">
                <span style="margin-right:4px">{!! $icon !!}</span>
                <span style="text-transform:capitalize">{{ $label }}</span>
                @if($log->meta && isset($log->meta['name'])) — <span class="text-muted">{{ $log->meta['name'] }}</span>@endif
                @if($log->meta && isset($log->meta['uploader'])) — <span class="text-muted">{{ $log->meta['uploader'] }}</span>@endif
            </div>
            <div class="text-xs text-muted">
                {{ $log->user?->name ?? 'Guest' }} · {{ $log->created_at->diffForHumans() }}
            </div>
        </div>
    </div>
    @empty
    <div class="text-muted text-sm" style="padding:20px 4px">No activity yet.</div>
    @endforelse
    </div>
</div>

</div>




@endsection
