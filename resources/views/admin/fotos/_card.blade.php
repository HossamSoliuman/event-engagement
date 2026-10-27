{{-- One Foto Queue card. Rendered on page load and returned as HTML for the cards the page script inserts. --}}
@php
    $state = $foto->screenState();
    $name = $foto->uploader_name ?: 'Anonymous';
    $chip = ['live' => 'On screen', 'queued' => 'In queue', 'shown' => 'Shown'][$state] ?? null;
@endphp
<article class="fq-card {{ $state === 'live' ? 'is-live' : '' }}" data-id="{{ $foto->id }}" data-state="{{ $state }}"
         data-kind="{{ $foto->isVideo() ? 'video' : 'photo' }}" data-src="{{ $foto->media_url }}" data-name="{{ $name }}"
         data-shown="{{ $foto->displayed_at?->getTimestampMs() }}">
    <button type="button" class="fq-media" data-act="view" aria-label="Open {{ $name }}'s {{ $foto->isVideo() ? 'video' : 'photo' }}">
        @include('admin.fotos._media')
        <span class="fq-chip" @if(! $chip) hidden @endif>{{ $chip }}</span>
        <span class="fq-zoom" aria-hidden="true"><i data-lucide="maximize-2" class="lucide-icon"></i></span>
    </button>

    <div class="fq-info">
        <div class="fq-info-text">
            <div class="fq-name" title="{{ $name }}">{{ $name }}</div>
            <div class="fq-sub">
                @if($foto->uploader_phone)<span>{{ $foto->uploader_phone }}</span>@endif
                <time data-ts="{{ $foto->created_at->getTimestampMs() }}">{{ $foto->created_at->diffForHumans() }}</time>
            </div>
            @if($foto->admin_note)
                <div class="fq-note">{{ $foto->admin_note }}</div>
            @endif
        </div>
        @if($foto->isPending())
            <button type="button" class="fq-btn fq-btn-delete fq-btn-icon" data-act="delete" title="Delete" aria-label="Delete"><i data-lucide="trash-2" class="lucide-icon"></i><span class="fq-btn-label">Delete</span></button>
        @endif
    </div>

    <div class="fq-actions">
        @if($foto->isPending())
            <button type="button" class="fq-btn fq-btn-approve" data-act="approve"><i data-lucide="check" class="lucide-icon"></i><span class="fq-btn-label">Approve</span></button>
            <button type="button" class="fq-btn fq-btn-reject" data-act="reject"><i data-lucide="x" class="lucide-icon"></i><span class="fq-btn-label">Reject</span></button>
        @elseif($foto->isApproved())
            <button type="button" class="fq-btn fq-btn-reject" data-act="reject"><i data-lucide="x" class="lucide-icon"></i><span class="fq-btn-label">Reject</span></button>
            <button type="button" class="fq-btn fq-btn-delete" data-act="delete"><i data-lucide="trash-2" class="lucide-icon"></i><span class="fq-btn-label">Delete</span></button>
        @else
            <button type="button" class="fq-btn fq-btn-approve" data-act="approve"><i data-lucide="rotate-ccw" class="lucide-icon"></i><span class="fq-btn-label">Restore</span></button>
            <button type="button" class="fq-btn fq-btn-delete" data-act="delete"><i data-lucide="trash-2" class="lucide-icon"></i><span class="fq-btn-label">Delete</span></button>
        @endif
    </div>
</article>
