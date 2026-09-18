@extends('layouts.admin')
@section('title','Foto Queue — '.$event->name)
@section('page-title','Foto Queue')

@section('topbar-actions')
    <span id="liveFotoBadge" class="badge badge-on-screen" @if(!$onScreen) hidden @endif>Live: {{ Str::limit($onScreen?->uploader_name ?? 'Guest',16) }}</span>
    <span id="queueBadge" class="badge" title="Approved items waiting to play on the vidiwall" @if(!$queued) hidden @endif>Queue: {{ $queued }}</span>
    <a href="{{ route('vidiwall.show',$event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
    <a href="{{ route('admin.fotos.export',$event) }}" class="btn btn-ghost btn-sm">CSV</a>
    <a href="{{ route('admin.fotos.download-all',$event) }}" class="btn btn-ghost btn-sm">Download All</a>
@endsection

@push('styles')
<style>
    [hidden] { display: none !important }
    .foto-state { text-align: center; font-size: 10px; font-weight: 800; padding: 4px; letter-spacing: 1px }
    .foto-state.is-live { background: var(--gold); color: #0A0A18 }
    .foto-state.is-queued { background: rgba(99, 102, 241, .25); color: #a5b4fc }
    .foto-state.is-shown { background: rgba(255, 255, 255, .06); color: var(--muted) }
    .live-progress { height: 3px; background: rgba(255, 215, 0, .15); border-radius: 0 0 10px 10px; overflow: hidden }
    .live-progress > div { height: 100%; width: 0; background: var(--gold) }
</style>
@endpush

@section('content')

<div class="tabs">
    @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $s=>$lbl)
    <a href="{{ route('admin.fotos.index',[$event,'status'=>$s]) }}" class="tab {{ $status===$s?'active':'' }}">
        {{ $lbl }} <span class="tab-count" data-count="{{ $s }}">{{ $counts[$s] }}</span>
    </a>
    @endforeach
</div>

@if($status==='approved')
<div id="liveCard" class="card mb-3" style="border-color:var(--gold);background:rgba(255,215,0,.04)" @if(!$onScreen) hidden @endif>
    <div class="card-body" style="display:flex;align-items:center;gap:14px;padding:14px 18px">
        <div id="liveVideoIcon" style="width:64px;height:64px;border-radius:8px;border:2px solid var(--gold);background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0" @if(!$onScreen?->isVideo()) hidden @endif>
            <span style="opacity:.8">&#9654;</span>
        </div>
        <img id="liveThumb" src="{{ $onScreen?->isVideo() ? '' : $onScreen?->thumbnail_url }}" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:2px solid var(--gold)" @if(!$onScreen || $onScreen->isVideo()) hidden @endif>
        <div style="flex:1">
            <div style="color:var(--gold);font-weight:700;font-size:13px;margin-bottom:2px">Currently Live on Vidiwall</div>
            <div><span id="liveName">{{ $onScreen?->uploader_name ?? 'Anonymous' }}</span>
                <span id="liveVideoBadge" style="font-size:11px;margin-left:6px;background:rgba(99,102,241,.2);color:#818cf8;padding:2px 7px;border-radius:10px" @if(!$onScreen?->isVideo()) hidden @endif>VIDEO {{ $onScreen?->video_duration ? round($onScreen->video_duration, 1).'s' : '' }}</span>
            </div>
            <div class="text-muted text-xs" id="liveMeta">Displayed {{ $onScreen?->displayed_at?->diffForHumans() }} &middot; plays {{ $onScreen?->screenSeconds() }}s, then the queue continues</div>
        </div>
        <form method="POST" action="{{ $onScreen ? route('admin.fotos.remove-from-screen',$onScreen) : '' }}" id="liveSkipForm">
            @csrf <button class="btn btn-danger btn-sm">Skip</button>
        </form>
    </div>
    <div class="live-progress"><div id="liveProgress"></div></div>
</div>

<div class="alert alert-info" style="margin-bottom:16px">
    Approved items play on the vidiwall automatically, once each, {{ \App\Models\FotoUpload::SCREEN_SECONDS }}s per photo (videos play through).
    <span id="queueSummary">@if($queued) <strong>{{ $queued }}</strong> waiting in the queue. @else The queue is empty &mdash; the screen shows the QR code. @endif</span>
    "Push Live" shows an item immediately, then the queue resumes.
</div>
@endif

@if($status==='pending')
<div class="alert alert-info" style="margin-bottom:16px">
    Page auto-refreshes every 10 seconds. <span id="refreshCountdown" style="font-weight:700">10</span>s
</div>
@endif

@if($fotos->count())
<div class="foto-grid" id="fotoGrid" data-status="{{ $status }}" data-rendered="{{ $counts[$status] }}">
    @foreach($fotos as $foto)
    @php $state = $foto->screenState(); @endphp
    <div class="foto-card {{ $state === 'live' ? 'on-screen' : '' }}" id="foto-{{ $foto->id }}" data-foto-id="{{ $foto->id }}">

        <div class="foto-state {{ $foto->isApproved() ? 'is-'.$state : '' }}" @if(!$foto->isApproved()) hidden @endif>
            @if($state === 'live') ON SCREEN @elseif($state === 'queued') IN QUEUE @elseif($state === 'shown') SHOWN {{ $foto->displayed_at?->diffForHumans() }} @endif
        </div>

        @if($foto->isVideo())
            <div style="position:relative">
                <video src="{{ $foto->file_url }}" controls preload="metadata"
                    style="width:100%;max-height:200px;object-fit:cover;display:block;background:#000"></video>
                <span style="position:absolute;top:8px;left:8px;background:rgba(99,102,241,.85);color:#fff;font-size:10px;font-weight:700;padding:3px 8px;border-radius:10px;letter-spacing:.5px">
                    VIDEO{{ $foto->video_duration ? ' '.round($foto->video_duration, 1).'s' : '' }}
                </span>
            </div>
        @else
            <a href="{{ $foto->file_url }}" target="_blank">
                <img src="{{ $foto->thumbnail_url }}" alt="Photo by {{ $foto->uploader_name }}" loading="lazy">
            </a>
        @endif

        <div class="foto-meta">
            <strong>{{ $foto->uploader_name ?? 'Anonymous' }}</strong>
            <span>{{ $foto->uploader_phone }}</span>
            <div style="margin-top:3px;color:var(--muted);font-size:11px">{{ $foto->created_at->diffForHumans() }}</div>
            @if($foto->admin_note)
            <div style="margin-top:4px;color:var(--red);font-size:11px">Note: {{ $foto->admin_note }}</div>
            @endif
        </div>

        <div class="foto-actions">
            @if($foto->isPending())
                <form method="POST" action="{{ route('admin.fotos.approve',$foto) }}">
                    @csrf <button class="btn btn-success btn-sm">Approve</button>
                </form>
                <form method="POST" action="{{ route('admin.fotos.reject',$foto) }}">
                    @csrf <button class="btn btn-danger btn-sm">Reject</button>
                </form>
            @endif

            @if($foto->isApproved())
                <form method="POST" action="{{ route('admin.fotos.push-to-screen',$foto) }}" class="push-live-form" @if($state === 'live') hidden @endif>
                    @csrf <button class="btn btn-gold btn-sm" style="width:100%">Push Live</button>
                </form>
                <form method="POST" action="{{ route('admin.fotos.remove-from-screen',$foto) }}" class="skip-form" @if($state !== 'live') hidden @endif>
                    @csrf <button class="btn btn-secondary btn-sm">Skip</button>
                </form>
            @endif

            @if($foto->isRejected())
                <form method="POST" action="{{ route('admin.fotos.approve',$foto) }}">
                    @csrf <button class="btn btn-success btn-sm">Restore</button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.fotos.destroy',$foto) }}"
                  onsubmit="return confirm('Delete permanently?')" style="margin-left:auto">
                @csrf @method('DELETE')
                <button class="btn btn-ghost btn-sm" style="color:var(--red)" title="Delete">Delete</button>
            </form>
        </div>
    </div>
    @endforeach
</div>
<div style="margin-top:20px">{{ $fotos->appends(['status'=>$status])->links() }}</div>

@else
<div class="empty-state" id="fotoGrid" data-status="{{ $status }}" data-rendered="{{ $counts[$status] }}">
    <div class="empty-icon">
        @if($status==='pending') Pending @elseif($status==='approved') Approved @else Rejected @endif
    </div>
    <h3>No {{ $status }} items</h3>
    <p>
        @if($status==='pending') Guests haven't uploaded yet — or all photos/videos are moderated.
        @elseif($status==='approved') Approve items from the pending tab to display them.
        @else No items have been rejected.
        @endif
    </p>
</div>
@endif

@endsection

@push('scripts')
@if($status==='pending')
<script>
let countdown = 10;
const cd = document.getElementById('refreshCountdown');
const timer = setInterval(() => {
    countdown--;
    if (cd) cd.textContent = countdown;
    if (countdown <= 0) { window.location.reload(); }
}, 1000);
</script>
@endif
<script>
(() => {
    const STATUS_URL = @json(route('admin.fotos.status', $event));
    const SKIP_URL = @json(route('admin.fotos.remove-from-screen', ['foto' => '__ID__']));
    const POLL_MS = 2000;
    const grid = document.getElementById('fotoGrid');
    const currentStatus = grid ? grid.dataset.status : null;
    const renderedCount = grid ? parseInt(grid.dataset.rendered, 10) : null;
    const cards = Array.from(document.querySelectorAll('.foto-card[data-foto-id]'));
    const ids = cards.map(c => c.dataset.fotoId).join(',');
    const $ = id => document.getElementById(id);
    let liveId = $('liveCard') && !$('liveCard').hidden ? 'initial' : null;
    let reloading = false;

    function setText(id, text) { const el = $(id); if (el) el.textContent = text; }
    function show(id, visible) { const el = $(id); if (el) el.hidden = !visible; }

    function renderLive(live) {
        show('liveFotoBadge', !!live);
        if (live) setText('liveFotoBadge', 'Live: ' + (live.uploader.length > 16 ? live.uploader.slice(0, 16) + '...' : live.uploader));
        if (!$('liveCard')) return;
        show('liveCard', !!live);
        if (!live) { liveId = null; return; }
        setText('liveName', live.uploader);
        show('liveVideoIcon', live.is_video);
        show('liveThumb', !live.is_video);
        if (!live.is_video && $('liveThumb').getAttribute('src') !== live.thumbnail_url) $('liveThumb').src = live.thumbnail_url;
        show('liveVideoBadge', live.is_video);
        setText('liveVideoBadge', 'VIDEO ' + (live.video_duration ? live.video_duration + 's' : ''));
        setText('liveMeta', 'Displayed ' + live.displayed_human + ' · plays ' + live.screen_seconds + 's, then the queue continues');
        $('liveSkipForm').action = SKIP_URL.replace('__ID__', live.id);
        if (liveId !== live.id) {
            liveId = live.id;
            const bar = $('liveProgress');
            const startPct = Math.min(100, live.elapsed_ms / live.slot_ms * 100);
            bar.style.transition = 'none';
            bar.style.width = startPct + '%';
            void bar.offsetWidth;
            bar.style.transition = 'width ' + Math.max(0, live.slot_ms - live.elapsed_ms) + 'ms linear';
            bar.style.width = '100%';
        }
    }

    function renderQueue(queued) {
        show('queueBadge', queued > 0);
        setText('queueBadge', 'Queue: ' + queued);
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
            const banner = card.querySelector('.foto-state');
            if (banner) {
                const approved = ['live', 'queued', 'shown'].includes(item.state);
                banner.hidden = !approved;
                banner.className = 'foto-state' + (approved ? ' is-' + item.state : '');
                banner.textContent = isLive ? 'ON SCREEN' : item.state === 'queued' ? 'IN QUEUE' : item.state === 'shown' ? 'SHOWN ' + (item.shown_human || '') : '';
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
            if (currentStatus && renderedCount !== null && data.counts[currentStatus] !== renderedCount) {
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
