@extends('layouts.admin')
@section('title','Foto Queue — '.$event->name)
@section('page-title','Foto Queue')

@section('topbar-actions')
    @if($onScreen)
        <span class="badge badge-on-screen">Live: {{ Str::limit($onScreen->uploader_name??'Guest',16) }}</span>
    @endif
    <a href="{{ route('vidiwall.show',$event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
    <a href="{{ route('admin.fotos.export',$event) }}" class="btn btn-ghost btn-sm">CSV</a>
    <a href="{{ route('admin.fotos.download-all',$event) }}" class="btn btn-ghost btn-sm">Download All</a>
@endsection

@section('content')

<div class="tabs">
    @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $s=>$lbl)
    <a href="{{ route('admin.fotos.index',[$event,'status'=>$s]) }}" class="tab {{ $status===$s?'active':'' }}">
        {{ $lbl }} <span class="tab-count">{{ $counts[$s] }}</span>
    </a>
    @endforeach
</div>

@if($onScreen && $status==='approved')
<div class="card mb-3" style="border-color:var(--gold);background:rgba(255,215,0,.04)">
    <div class="card-body" style="display:flex;align-items:center;gap:14px;padding:14px 18px">
        @if($onScreen->isVideo())
            <div style="width:64px;height:64px;border-radius:8px;border:2px solid var(--gold);background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                <span style="opacity:.8">&#9654;</span>
            </div>
        @else
            <img src="{{ $onScreen->thumbnail_url }}" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:2px solid var(--gold)">
        @endif
        <div style="flex:1">
            <div style="color:var(--gold);font-weight:700;font-size:13px;margin-bottom:2px">Currently Live on Vidiwall</div>
            <div>{{ $onScreen->uploader_name ?? 'Anonymous' }}
                @if($onScreen->isVideo())
                    <span style="font-size:11px;margin-left:6px;background:rgba(99,102,241,.2);color:#818cf8;padding:2px 7px;border-radius:10px">VIDEO {{ $onScreen->video_duration ? round($onScreen->video_duration, 1).'s' : '' }}</span>
                @endif
            </div>
            <div class="text-muted text-xs">Displayed {{ $onScreen->displayed_at?->diffForHumans() }}</div>
        </div>
        <form method="POST" action="{{ route('admin.fotos.remove-from-screen',$onScreen) }}">
            @csrf <button class="btn btn-danger btn-sm">Remove</button>
        </form>
    </div>
</div>
@endif

@if($status==='pending')
<div class="alert alert-info" style="margin-bottom:16px">
    Page auto-refreshes every 10 seconds. <span id="refreshCountdown" style="font-weight:700">10</span>s
</div>
@endif

@if($fotos->count())
<div class="foto-grid">
    @foreach($fotos as $foto)
    <div class="foto-card {{ $foto->on_screen ? 'on-screen' : '' }}" id="foto-{{ $foto->id }}">

        @if($foto->on_screen)
        <div style="background:var(--gold);color:#0A0A18;text-align:center;font-size:10px;font-weight:800;padding:4px;letter-spacing:1px">ON SCREEN</div>
        @endif

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

            @if($foto->isApproved() && !$foto->on_screen)
                <form method="POST" action="{{ route('admin.fotos.push-to-screen',$foto) }}">
                    @csrf <button class="btn btn-gold btn-sm" style="width:100%">Push Live</button>
                </form>
            @endif

            @if($foto->on_screen)
                <form method="POST" action="{{ route('admin.fotos.remove-from-screen',$foto) }}">
                    @csrf <button class="btn btn-secondary btn-sm">Remove</button>
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
<div class="empty-state">
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
@endpush
