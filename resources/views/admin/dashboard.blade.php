@extends('layouts.admin')
@section('page-title', 'Dashboard')

@section('topbar-actions')
    @if ($activeEvent)
        <a href="{{ route('admin.events.show', $activeEvent) }}" class="btn btn-primary btn-sm">Manage Event</a>
    @else
        <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">+ New Event</a>
    @endif
@endsection

@section('content')

    @if ($activeEvent)
        <!-- Active Event Hero -->
        <div class="card mb-3"
            style="background: linear-gradient(135deg, rgba(255,61,0,.12), rgba(13,13,26,.8)); border-color: rgba(255,61,0,.3);">
            <div class="card-body"
                style="display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap;">
                <div>
                    <div class="text-muted text-sm mb-1">● LIVE NOW</div>
                    <h2 style="font-size: 26px; margin-bottom: 6px;">{{ $activeEvent->name }}</h2>
                    <div class="text-muted text-sm">{{ $activeEvent->subtitle }}</div>
                    <div style="margin-top: 12px; display:flex; gap:10px; flex-wrap:wrap;">
                        <a href="{{ route('event.landing', $activeEvent->slug) }}" target="_blank"
                            class="btn btn-outline btn-sm">📱 Guest Page ↗</a>
                        <a href="{{ route('vidiwall.show', $activeEvent->slug) }}" target="_blank"
                            class="btn btn-gold btn-sm">📺 Vidiwall ↗</a>
                        <a href="{{ route('admin.fotos.index', $activeEvent) }}" class="btn btn-primary btn-sm">
                            📷 Foto Queue
                            @if ($pendingFotos > 0)
                                ({{ $pendingFotos }} pending)
                            @endif
                        </a>
                    </div>
                </div>
                @if ($activeEvent->qr_code_path)
                    <div style="text-align:center;">
                        <img src="{{ $activeEvent->qr_code_url }}" alt="QR Code"
                            style="width:120px; height:120px; background:#fff; padding:6px; border-radius:8px;">
                        <div class="text-muted text-sm" style="margin-top:6px;">Scan to preview</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Pending Fotos</div>
            <div class="stat-value red">{{ $pendingFotos }}</div>
        </div>
        @foreach ($activeEvents as $event)
            <div class="stat-card">
                <div class="stat-label">{{ Str::limit($event->name, 20) }} — Uploads</div>
                <div class="stat-value">{{ $event->foto_uploads_count }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ Str::limit($event->name, 20) }} — Lottery</div>
                <div class="stat-value gold">{{ $event->lottery_entries_count }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ Str::limit($event->name, 20) }} — Votes</div>
                <div class="stat-value">{{ $event->votes_count }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ Str::limit($event->name, 20) }} — Members</div>
                <div class="stat-value green">{{ $event->memberships_count }}</div>
            </div>
        @endforeach
    </div>

    <!-- Events List -->
    <div class="card">
        <div class="card-header">
            <h3 style="font-size:16px;">All Events</h3>
            <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">+ New Event</a>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Slug</th>
                        <th>Modules</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>
                                <strong>{{ $event->name }}</strong>
                                <div class="text-muted text-sm">{{ $event->subtitle }}</div>
                            </td>
                            <td><code style="color: var(--eb-muted); font-size:12px;">/e/{{ $event->slug }}</code></td>
                            <td>
                                <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                    @foreach (['fotobomb', 'lottery', 'voting', 'membership'] as $mod)
                                        <span
                                            class="badge {{ $event->{'module_' . $mod} ? 'badge-approved' : 'badge-rejected' }}">
                                            {{ ucfirst($mod) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $event->is_active ? 'badge-approved' : 'badge-rejected' }}">
                                    {{ $event->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('admin.events.show', $event) }}"
                                        class="btn btn-secondary btn-sm">Manage</a>
                                    <a href="{{ route('admin.fotos.index', $event) }}"
                                        class="btn btn-outline btn-sm">Fotos</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; color:var(--eb-muted); padding:40px;">No events
                                yet. <a href="{{ route('admin.events.create') }}" style="color:var(--eb-red);">Create one
                                    →</a></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
