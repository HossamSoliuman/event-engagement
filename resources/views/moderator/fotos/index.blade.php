@extends('layouts.moderator')
@section('title', 'Foto Queue')
@section('page-title', 'Foto Queue — ' . $event->name)
@php $pendingCount = $counts['pending']; @endphp

@section('topbar-actions')
    <a href="{{ route('moderator.fotos.export', $event) }}" class="btn btn-secondary btn-sm">Export CSV</a>
    <a href="{{ route('moderator.fotos.download-all', $event) }}" class="btn btn-secondary btn-sm">Download All</a>
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall</a>
@endsection

@push('styles')
<style>
    [hidden]{display:none!important}
    .live-progress{height:3px;background:rgba(255,215,0,.15);border-radius:0 0 10px 10px;overflow:hidden}
    .live-progress>div{height:100%;width:0;background:var(--gold)}
</style>
@endpush

@section('content')

<div id="liveCard" class="card mb-3" style="border-color:var(--gold);background:rgba(255,215,0,.04)" @if(!$onScreen) hidden @endif>
    <div class="card-header"><h3>Currently on Vidiwall</h3></div>
    <div class="card-body" style="display:flex;align-items:center;gap:16px">
        <div id="liveVideoIcon" style="width:80px;height:80px;border-radius:8px;border:2px solid var(--gold);background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0" @if(!$onScreen?->isVideo()) hidden @endif>
            <span style="opacity:.8">&#9654;</span>
        </div>
        <img id="liveThumb" src="{{ $onScreen?->isVideo() ? '' : $onScreen?->thumbnail_url }}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid var(--gold)" @if(!$onScreen || $onScreen->isVideo()) hidden @endif>
        <div style="flex:1">
            <div class="font-bold"><span id="liveName">{{ $onScreen?->uploader_name ?? 'Anonymous' }}</span>
                <span id="liveVideoBadge" style="font-size:11px;margin-left:6px;background:rgba(99,102,241,.2);color:#818cf8;padding:2px 7px;border-radius:10px" @if(!$onScreen?->isVideo()) hidden @endif>VIDEO{{ $onScreen?->video_duration ? ' '.round($onScreen->video_duration,1).'s' : '' }}</span>
            </div>
            <div class="text-muted text-sm" id="liveMeta">On screen since {{ $onScreen?->displayed_at?->diffForHumans() }} &middot; plays {{ $onScreen?->screenSeconds() }}s, then the queue continues</div>
        </div>
        <form method="POST" action="{{ $onScreen ? route('moderator.fotos.remove-from-screen', [$event, $onScreen]) : '' }}" id="liveSkipForm">
            @csrf <button class="btn btn-danger btn-sm">Skip</button>
        </form>
    </div>
    <div class="live-progress"><div id="liveProgress"></div></div>
</div>

@if($status === 'approved')
<div class="alert alert-info" style="margin-bottom:16px">
    Approved items play on the vidiwall automatically, once each, {{ \App\Models\FotoUpload::SCREEN_SECONDS }}s per photo (videos play through).
    <span id="queueSummary">@if($queued) <strong>{{ $queued }}</strong> waiting in the queue. @else The queue is empty &mdash; the screen shows the QR code. @endif</span>
    "Push Live" shows an item immediately, then the queue resumes.
</div>
@endif

<div class="card">
    <div class="card-header">
        <div class="tabs" style="margin:0;border:none">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $s => $label)
            <a href="{{ route('moderator.fotos.index', [$event, 'status' => $s]) }}"
               class="tab {{ $status === $s ? 'active' : '' }}">
                {{ $label }}<span class="tab-count" data-count="{{ $s }}">{{ $counts[$s] }}</span>
            </a>
            @endforeach
        </div>
    </div>

    <div class="card-body" id="fotoGrid" data-status="{{ $status }}" data-rendered="{{ $counts[$status] }}">
        @forelse($fotos as $foto)
        @if($loop->first)<div class="foto-grid">@endif
        @php $state = $foto->screenState(); @endphp
        <div class="foto-card {{ $state === 'live' ? 'on-screen' : '' }}" data-foto-id="{{ $foto->id }}">
            @if($foto->isVideo())
                <div style="position:relative">
                    <video src="{{ $foto->file_url }}" controls preload="metadata"
                        style="width:100%;max-height:200px;object-fit:cover;display:block;background:#000"></video>
                    <span style="position:absolute;top:8px;left:8px;background:rgba(99,102,241,.85);color:#fff;font-size:10px;font-weight:700;padding:3px 8px;border-radius:10px;letter-spacing:.5px">
                        VIDEO{{ $foto->video_duration ? ' '.round($foto->video_duration,1).'s' : '' }}
                    </span>
                </div>
            @else
                <img src="{{ $foto->thumbnail_url ?? $foto->file_url }}" loading="lazy">
            @endif
            <div class="foto-meta">
                <strong>{{ $foto->uploader_name ?? 'Anonymous' }}</strong>
                <span>{{ $foto->created_at->diffForHumans() }}</span>
                <div @if(!$foto->isApproved()) hidden @endif>
                    <span class="badge foto-state {{ $state === 'live' ? 'badge-on-screen' : '' }}" style="{{ $state === 'shown' ? 'opacity:.6' : '' }}">
                        @if($state === 'live') On Screen @elseif($state === 'queued') In Queue @elseif($state === 'shown') Shown {{ $foto->displayed_at?->diffForHumans() }} @endif
                    </span>
                </div>
            </div>
            <div class="foto-actions">
                @if($foto->status === 'pending')
                    <form method="POST" action="{{ route('moderator.fotos.approve', [$event, $foto]) }}">
                        @csrf <button class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('moderator.fotos.reject', [$event, $foto]) }}" data-confirm="Reject this item?">
                        @csrf <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                @endif
                @if($foto->status === 'approved')
                    <form method="POST" action="{{ route('moderator.fotos.push-to-screen', [$event, $foto]) }}" class="push-live-form" @if($state === 'live') hidden @endif>
                        @csrf <button class="btn btn-gold btn-sm">Push Live</button>
                    </form>
                    <form method="POST" action="{{ route('moderator.fotos.remove-from-screen', [$event, $foto]) }}" class="skip-form" @if($state !== 'live') hidden @endif>
                        @csrf <button class="btn btn-secondary btn-sm">Skip</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('moderator.fotos.destroy', [$event, $foto]) }}" data-confirm="Delete permanently?">
                    @csrf @method('DELETE') <button class="btn btn-ghost btn-sm">Delete</button>
                </form>
            </div>
        </div>
        @if($loop->last)</div>@endif
        @empty
        <div class="empty-state">
            <h3>No {{ $status }} items</h3>
            <div class="text-muted text-sm" style="margin-top:6px">Guests haven't submitted anything yet.</div>
        </div>
        @endforelse

        @if($fotos->hasPages())
        <div class="mt-3">{{ $fotos->withQueryString()->links() }}</div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
document.querySelectorAll('form[data-confirm]').forEach(f => {
    f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});

(() => {
    const STATUS_URL = @json(route('moderator.fotos.status', $event));
    const SKIP_URL = @json(route('moderator.fotos.remove-from-screen', [$event, '__ID__']));
    const POLL_MS = 2000;
    const grid = document.getElementById('fotoGrid');
    const currentStatus = grid.dataset.status;
    const renderedCount = parseInt(grid.dataset.rendered, 10);
    const cards = Array.from(document.querySelectorAll('.foto-card[data-foto-id]'));
    const ids = cards.map(c => c.dataset.fotoId).join(',');
    const $ = id => document.getElementById(id);
    let liveId = $('liveCard').hidden ? null : 'initial';
    let reloading = false;

    function setText(id, text) { const el = $(id); if (el) el.textContent = text; }
    function show(id, visible) { const el = $(id); if (el) el.hidden = !visible; }

    function renderLive(live) {
        show('liveCard', !!live);
        if (!live) { liveId = null; return; }
        setText('liveName', live.uploader);
        show('liveVideoIcon', live.is_video);
        show('liveThumb', !live.is_video);
        if (!live.is_video && $('liveThumb').getAttribute('src') !== live.thumbnail_url) $('liveThumb').src = live.thumbnail_url;
        show('liveVideoBadge', live.is_video);
        setText('liveVideoBadge', 'VIDEO' + (live.video_duration ? ' ' + live.video_duration + 's' : ''));
        setText('liveMeta', 'On screen since ' + live.displayed_human + ' · plays ' + live.screen_seconds + 's, then the queue continues');
        $('liveSkipForm').action = SKIP_URL.replace('__ID__', live.id);
        if (liveId !== live.id) {
            liveId = live.id;
            const bar = $('liveProgress');
            bar.style.transition = 'none';
            bar.style.width = Math.min(100, live.elapsed_ms / live.slot_ms * 100) + '%';
            void bar.offsetWidth;
            bar.style.transition = 'width ' + Math.max(0, live.slot_ms - live.elapsed_ms) + 'ms linear';
            bar.style.width = '100%';
        }
    }

    function renderQueue(queued) {
        const summary = $('queueSummary');
        if (summary) {
            summary.innerHTML = queued > 0
                ? ' <strong>' + queued + '</strong> waiting in the queue. '
                : ' The queue is empty — the screen shows the QR code. ';
        }
    }

    function renderCards(items) {
        const byId = Object.fromEntries(items.map(i => [String(i.id), i]));
        cards.forEach(card => {
            const item = byId[card.dataset.fotoId];
            if (!item) return;
            const isLive = item.state === 'live';
            card.classList.toggle('on-screen', isLive);
            const badge = card.querySelector('.foto-state');
            if (badge) {
                const approved = ['live', 'queued', 'shown'].includes(item.state);
                badge.parentElement.hidden = !approved;
                badge.classList.toggle('badge-on-screen', isLive);
                badge.style.opacity = item.state === 'shown' ? '.6' : '';
                badge.textContent = isLive ? 'On Screen' : item.state === 'queued' ? 'In Queue' : item.state === 'shown' ? 'Shown ' + (item.shown_human || '') : '';
            }
            const push = card.querySelector('.push-live-form');
            const skip = card.querySelector('.skip-form');
            if (push) push.hidden = isLive;
            if (skip) skip.hidden = !isLive;
        });
    }

    async function poll() {
        if (document.hidden || reloading) return;
        try {
            const res = await fetch(STATUS_URL + (ids ? '?ids=' + ids : ''), { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();
            renderLive(data.live);
            renderQueue(data.queued);
            renderCards(data.items);
            document.querySelectorAll('.tab-count[data-count]').forEach(el => { el.textContent = data.counts[el.dataset.count]; });
            if (data.counts[currentStatus] !== renderedCount) {
                reloading = true;
                window.location.reload();
            }
        } catch (e) {}
    }

    setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
})();
</script>
@endpush
