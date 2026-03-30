@extends('layouts.admin')
@section('page-title', 'Foto Moderation — ' . $event->name)

@section('topbar-actions')
    @if ($onScreen)
        <span class="badge badge-on-screen">📺 Live on Screen:
            {{ Str::limit($onScreen->uploader_name ?? 'Guest', 20) }}</span>
    @endif
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Open Vidiwall ↗</a>
@endsection

@section('content')

    <!-- Status Tabs -->
    <div style="display:flex; gap:4px; margin-bottom:24px; border-bottom: 1px solid var(--eb-border); padding-bottom: 1px;">
        @foreach (['pending' => '⏳ Pending', 'approved' => '✅ Approved', 'rejected' => '✗ Rejected'] as $s => $label)
            <a href="{{ route('admin.fotos.index', [$event, 'status' => $s]) }}"
                style="padding:10px 20px; font-size:14px; font-weight:600; text-decoration:none; border-bottom: 2px solid {{ $status === $s ? 'var(--eb-red)' : 'transparent' }}; color: {{ $status === $s ? 'var(--eb-red)' : 'var(--eb-muted)' }}; transition: color .15s;">
                {{ $label }} <span style="font-size:12px;">({{ $counts[$s] }})</span>
            </a>
        @endforeach
    </div>

    @if ($onScreen && $status === 'approved')
        <!-- On Screen Banner -->
        <div class="card mb-3" style="border-color: var(--eb-gold); background: rgba(255,215,0,.05);">
            <div class="card-body" style="display:flex; align-items:center; gap:20px;">
                <img src="{{ $onScreen->thumbnail_url }}" alt=""
                    style="width:80px; height:80px; object-fit:cover; border-radius:8px; border:2px solid var(--eb-gold);">
                <div style="flex:1;">
                    <div style="color:var(--eb-gold); font-weight:700; margin-bottom:4px;">📺 Currently on Vidiwall</div>
                    <div>{{ $onScreen->uploader_name ?? 'Anonymous' }}</div>
                    <div class="text-muted text-sm">Displayed {{ $onScreen->displayed_at?->diffForHumans() }}</div>
                </div>
                <form method="POST" action="{{ route('admin.fotos.remove-from-screen', $onScreen) }}">
                    @csrf
                    <button class="btn btn-danger btn-sm">Remove from Screen</button>
                </form>
            </div>
        </div>
    @endif

    <!-- Photo Grid -->
    @if ($fotos->count())
        <div class="foto-grid">
            @foreach ($fotos as $foto)
                <div class="foto-card {{ $foto->on_screen ? 'on-screen' : '' }}">
                    @if ($foto->on_screen)
                        <div
                            style="background:var(--eb-gold); color:#000; text-align:center; font-size:11px; font-weight:700; padding:4px; letter-spacing:1px;">
                            📺 ON SCREEN</div>
                    @endif

                    <a href="{{ $foto->file_url }}" target="_blank">
                        <img src="{{ $foto->thumbnail_url }}" alt="Foto by {{ $foto->uploader_name }}">
                    </a>

                    <div class="foto-card-body">
                        <div style="font-weight:600; font-size:14px;">{{ $foto->uploader_name ?? 'Anonymous' }}</div>
                        <div class="text-muted text-sm">{{ $foto->created_at->diffForHumans() }}</div>
                        <div class="text-muted text-sm">{{ $foto->uploader_phone }}</div>
                    </div>

                    <div class="foto-actions">
                        @if ($foto->isPending())
                            <form method="POST" action="{{ route('admin.fotos.approve', $foto) }}">
                                @csrf
                                <button class="btn btn-success btn-sm">✓ Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.fotos.reject', $foto) }}">
                                @csrf
                                <button class="btn btn-danger btn-sm">✗ Reject</button>
                            </form>
                        @endif

                        @if ($foto->isApproved() && !$foto->on_screen)
                            <form method="POST" action="{{ route('admin.fotos.push-to-screen', $foto) }}">
                                @csrf
                                <button class="btn btn-gold btn-sm">📺 Push Live</button>
                            </form>
                        @endif

                        @if ($foto->on_screen)
                            <form method="POST" action="{{ route('admin.fotos.remove-from-screen', $foto) }}">
                                @csrf
                                <button class="btn btn-secondary btn-sm">Remove</button>
                            </form>
                        @endif

                        @if ($foto->isRejected())
                            <form method="POST" action="{{ route('admin.fotos.approve', $foto) }}">
                                @csrf
                                <button class="btn btn-success btn-sm">↩ Restore</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.fotos.destroy', $foto) }}"
                            onsubmit="return confirm('Delete this photo permanently?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline btn-sm" style="color:#f87171; border-color:#f87171;">🗑</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:24px;">{{ $fotos->appends(['status' => $status])->links() }}</div>
    @else
        <div style="text-align:center; padding:80px 20px; color:var(--eb-muted);">
            <div style="font-size:48px; margin-bottom:16px;">
                @if ($status === 'pending')
                    ⏳
                @elseif($status === 'approved')
                    ✅
                @else
                    ✗
                @endif
            </div>
            <div style="font-size:18px; font-weight:600;">No {{ $status }} photos</div>
            <div style="margin-top:8px; font-size:14px;">
                @if ($status === 'pending')
                    Guests haven't uploaded yet, or all photos have been moderated.
                @elseif($status === 'approved')
                    Approve photos from the pending queue to push them live.
                @else
                    No photos have been rejected.
                @endif
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    <script>
        // Auto-refresh pending count every 15 seconds
        @if ($status === 'pending')
            setInterval(() => {
                fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                // Simple polling — in production replace with Pusher/Echo real-time events
            }, 15000);
        @endif
    </script>
@endpush
