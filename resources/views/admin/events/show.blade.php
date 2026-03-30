@extends('layouts.admin')
@section('page-title', $event->name)

@section('topbar-actions')
    <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-secondary btn-sm">Edit Event</a>
    <form method="POST" action="{{ route('admin.events.generate-qr', $event) }}" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-outline btn-sm">↻ Regenerate QR</button>
    </form>
@endsection

@section('content')

    <div class="grid-2" style="margin-bottom:20px;">

        <!-- QR Code Card -->
        <div class="card">
            <div class="card-header">
                <h3 style="font-size:15px;">Event QR Code</h3>
            </div>
            <div class="card-body" style="text-align:center;">
                @if ($event->qr_code_path)
                    <img src="{{ $event->qr_code_url }}" alt="QR Code"
                        style="width:200px; height:200px; background:#fff; padding:10px; border-radius:10px; display:inline-block;">
                    <div class="text-muted text-sm" style="margin-top:10px;">{{ $event->getGuestUrl() }}</div>
                    <div style="margin-top:14px; display:flex; gap:8px; justify-content:center;">
                        <a href="{{ $event->qr_code_url }}" download class="btn btn-secondary btn-sm">⬇ Download SVG</a>
                        <a href="{{ route('event.landing', $event->slug) }}" target="_blank"
                            class="btn btn-outline btn-sm">Preview ↗</a>
                    </div>
                @else
                    <p class="text-muted">No QR code generated yet.</p>
                    <form method="POST" action="{{ route('admin.events.generate-qr', $event) }}" style="margin-top:12px;">
                        @csrf
                        <button type="submit" class="btn btn-primary">Generate QR Code</button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Module Toggles -->
        <div class="card">
            <div class="card-header">
                <h3 style="font-size:15px;">Module Controls</h3>
            </div>
            <div class="card-body">
                @foreach ([['fotobomb', '📷', 'Foto Bomb', 'Let guests upload photos'], ['lottery', '🎰', 'Lottery', 'Prize draw entries'], ['voting', '🏆', 'Athlete Vote', 'Vote for best athlete'], ['membership', '⭐', 'Membership', 'Guest sign-ups']] as [$key, $icon, $label, $desc])
                    <div
                        style="display:flex; align-items:center; justify-content:space-between; padding: 12px 0; border-bottom: 1px solid var(--eb-border);">
                        <div>
                            <div style="font-weight:600;">{{ $icon }} {{ $label }}</div>
                            <div class="text-muted text-sm">{{ $desc }}</div>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" {{ $event->{'module_' . $key} ? 'checked' : '' }}
                                onchange="toggleModule('{{ $key }}', this)" data-event="{{ $event->id }}">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Pending Fotos</div>
            <div class="stat-value red">{{ $pending }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Approved Fotos</div>
            <div class="stat-value green">{{ $approved }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Lottery Entries</div>
            <div class="stat-value gold">{{ $event->lottery_entries_count }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Votes</div>
            <div class="stat-value">{{ $event->votes_count }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Members Signed Up</div>
            <div class="stat-value">{{ $event->memberships_count }}</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <a href="{{ route('admin.fotos.index', $event) }}" class="btn btn-primary">
            📷 Moderate Fotos
            @if ($pending > 0)
                <span
                    style="background:rgba(255,255,255,.2); padding:2px 8px; border-radius:20px; font-size:11px;">{{ $pending }}</span>
            @endif
        </a>
        <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold">📺 Open Vidiwall</a>
        <a href="{{ route('event.landing', $event->slug) }}" target="_blank" class="btn btn-outline">📱 Guest Page</a>
        <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-secondary">✏️ Edit Event</a>
    </div>

@endsection

@push('scripts')
    <script>
        function toggleModule(module, checkbox) {
            const eventId = checkbox.dataset.event;
            fetch(`/admin/events/${eventId}/toggle-module`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        module
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    checkbox.checked = data.enabled;
                })
                .catch(() => {
                    checkbox.checked = !checkbox.checked; // revert on fail
                });
        }
    </script>
@endpush
