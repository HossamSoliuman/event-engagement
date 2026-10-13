{{-- Shared by the admin and moderator Foto Queue and player pages; $routePrefix picks the route set, $active the current tab. --}}
@php
    $playerState = $event->vidiwallPlayer?->state ?? \App\Models\VidiwallPlayer::STATE_STOPPED;
@endphp

@push('styles')
    <style>
        .fq-tabs { overflow-x: auto; scrollbar-width: none; }
        .fq-tabs::-webkit-scrollbar { display: none; }
        .fq-tabs .tab { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .fq-tabs .tab-count { margin-left: 0; }
        .fq-tab-player { position: relative; margin-left: 14px; }
        .fq-tab-player::before { content: ''; position: absolute; left: -8px; top: 9px; bottom: 9px; width: 1px; background: var(--border); }
        .fq-tab-player .lucide-icon { width: 15px; height: 15px; }
        .fq-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--muted); opacity: .45; flex-shrink: 0; }
        .fq-dot[data-player-state="playing"] { background: #22c55e; opacity: 1; animation: fqPulse 1.6s ease-in-out infinite; }
        .fq-dot[data-player-state="paused"] { background: var(--gold); opacity: 1; }
        @keyframes fqPulse { 50% { box-shadow: 0 0 0 4px rgba(34, 197, 94, .22); } }
    </style>
@endpush

<nav class="tabs fq-tabs">
    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $tabStatus => $tabLabel)
        <a href="{{ route($routePrefix . 'fotos.index', [$event, 'status' => $tabStatus]) }}" class="tab {{ $active === $tabStatus ? 'active' : '' }}" data-fq-tab="{{ $tabStatus }}">
            {{ $tabLabel }} <span class="tab-count" data-count="{{ $tabStatus }}">{{ $counts[$tabStatus] }}</span>
        </a>
    @endforeach
    <a href="{{ route($routePrefix . 'player.index', $event) }}" class="tab fq-tab-player {{ $active === 'player' ? 'active' : '' }}">
        <i data-lucide="monitor-play" class="lucide-icon"></i> Player
        <span class="fq-dot" id="fqPlayerDot" data-player-state="{{ $playerState }}" title="Player {{ $playerState }}"></span>
    </a>
</nav>

@push('scripts')
    <script>
        document.addEventListener('fotoqueue:status', ({ detail }) => {
            document.querySelectorAll('.fq-tabs .tab-count[data-count]').forEach(el => {
                if (detail.counts && detail.counts[el.dataset.count] !== undefined) {
                    el.textContent = detail.counts[el.dataset.count];
                }
            });
            const dot = document.getElementById('fqPlayerDot');
            if (dot && detail.player_state) {
                dot.dataset.playerState = detail.player_state;
                dot.title = 'Player ' + detail.player_state;
            }
        });
    </script>
@endpush
