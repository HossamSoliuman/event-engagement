@extends('layouts.moderator')
@section('title', 'Foto Queue')
@section('page-title', 'Foto Queue — ' . $event->name)
@php $pendingCount = $counts['pending']; @endphp

@section('topbar-actions')
    <a href="{{ route('moderator.fotos.export', $event) }}" class="btn btn-secondary btn-sm">Export CSV</a>
    <a href="{{ route('moderator.fotos.download-all', $event) }}" class="btn btn-secondary btn-sm">Download Images</a>
    <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-gold btn-sm">Vidiwall</a>
@endsection

@section('content')

@if($onScreen)
<div class="card mb-3" style="border-color:var(--gold);background:rgba(255,215,0,.04)">
    <div class="card-header"><h3>Currently on Vidiwall</h3></div>
    <div class="card-body" style="display:flex;align-items:center;gap:16px">
        <img src="{{ $onScreen->thumbnail_url }}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid var(--gold)">
        <div style="flex:1">
            <div class="font-bold">{{ $onScreen->uploader_name ?? 'Anonymous' }}</div>
            <div class="text-muted text-sm">On screen since {{ $onScreen->displayed_at?->diffForHumans() }}</div>
        </div>
        <form method="POST" action="{{ route('moderator.fotos.remove-from-screen', [$event, $onScreen]) }}">
            @csrf <button class="btn btn-danger btn-sm">Remove</button>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <div class="tabs" style="margin:0;border:none">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $s => $label)
            <a href="{{ route('moderator.fotos.index', [$event, 'status' => $s]) }}"
               class="tab {{ $status === $s ? 'active' : '' }}">
                {{ $label }}<span class="tab-count">{{ $counts[$s] }}</span>
            </a>
            @endforeach
        </div>
    </div>

    <div class="card-body">
        @forelse($fotos as $foto)
        @if($loop->first)<div class="foto-grid">@endif
        <div class="foto-card {{ $foto->on_screen ? 'on-screen' : '' }}">
            <img src="{{ $foto->thumbnail_url ?? $foto->file_url }}" loading="lazy">
            <div class="foto-meta">
                <strong>{{ $foto->uploader_name ?? 'Anonymous' }}</strong>
                <span>{{ $foto->created_at->diffForHumans() }}</span>
                @if($foto->on_screen)<div><span class="badge badge-on-screen">On Screen</span></div>@endif
            </div>
            <div class="foto-actions">
                @if($foto->status === 'pending')
                    <form method="POST" action="{{ route('moderator.fotos.approve', [$event, $foto]) }}">
                        @csrf <button class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('moderator.fotos.reject', [$event, $foto]) }}" data-confirm="Reject this photo?">
                        @csrf <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                @endif
                @if($foto->status === 'approved' && !$foto->on_screen)
                    <form method="POST" action="{{ route('moderator.fotos.push-to-screen', [$event, $foto]) }}">
                        @csrf <button class="btn btn-gold btn-sm">Push Live</button>
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
            <h3>No {{ $status }} photos</h3>
            <div class="text-muted text-sm" style="margin-top:6px">Guests haven't submitted any photos yet.</div>
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
</script>
@endpush
