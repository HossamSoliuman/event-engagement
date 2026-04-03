@extends('layouts.admin')
@section('title', 'Lottery — ' . $event->name)
@section('page-title', '🎰 Lottery')

@section('topbar-actions')
    <a href="{{ route('admin.lottery.export', $event) }}" class="btn btn-ghost btn-sm">⬇ CSV</a>
    <a href="{{ route('admin.events.show', $event) }}" class="btn btn-secondary btn-sm">← Event</a>
@endsection

@section('content')

    {{-- Stats --}}
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:20px">
        <div class="stat-card">
            <div class="stat-label">Total Entries </div>
            <div class="stat-value c-gold">{{ $totalCount }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Status</div>
            <div class="stat-value" style="font-size:18px;padding-top:6px">
                @if ($event->lottery_drawn)
                    🎉 Winner Drawn
                @else
                    🎰 Open
                @endif
            </div>
        </div>
    </div>

    {{-- Winner Card --}}
    @if ($winner && $event->lottery_drawn)
        <div class="winner-card mb-3" id="winnerCard">
            <div class="trophy">🏆</div>
            <h2>WINNER!</h2>
            <div style="font-size:28px;font-weight:800;color:var(--text);margin:10px 0">{{ $winner->name }}</div>
            <p style="font-size:16px">{{ $winner->phone }}</p>
            @if ($winner->email)
                <p class="text-muted" style="font-size:13px">{{ $winner->email }}</p>
            @endif
            <p class="text-muted" style="margin-top:8px;font-size:12px">Drawn {{ $winner->won_at?->format('M d Y · H:i') }}
            </p>
            <div style="margin-top:18px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
                <form method="POST" action="{{ route('admin.lottery.reset', $event) }}">
                    @csrf <button class="btn btn-outline btn-sm"
                        onclick="return confirm('Reset the lottery? The winner will be cleared.')">↺ Reset Lottery</button>
                </form>
            </div>
        </div>
    @elseif($totalCount > 0)
        {{-- Draw Button --}}
        <div class="card mb-3" style="border-color:rgba(255,215,0,.25)">
            <div class="card-body" style="text-align:center;padding:40px 20px">
                <div style="font-size:52px;margin-bottom:14px" id="drumRoll">🎰</div>
                <h2 style="font-size:24px;margin-bottom:8px">Ready to Draw!</h2>
                <p class="text-muted" style="margin-bottom:24px">{{ $totalCount }} entries in the draw. One lucky winner
                    will be selected at random.</p>
                <form method="POST" action="{{ route('admin.lottery.draw', $event) }}" id="drawForm">
                    @csrf
                    <button type="button" class="btn btn-gold btn-lg" onclick="animateDraw()" id="drawBtn">
                        🎰 Draw the Winner!
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="empty-state mb-3">
            <div class="empty-icon">🎟</div>
            <h3>No entries yet</h3>
            <p>Guests need to enter via the lottery module on the event page.</p>
        </div>
    @endif

    {{-- Search + Entries --}}
    <div class="card">
        <div class="card-header">
            <h3>All Entries ({{ $totalCount }})</h3>
            <form method="GET" action="{{ route('admin.lottery.index', $event) }}" style="display:flex;gap:8px">
                <div class="search-bar">
                    <input name="search" class="form-control" placeholder="Search name or phone…"
                        value="{{ $search }}" style="width:220px">
                </div>
                <button class="btn btn-secondary btn-sm">Search</button>
                @if ($search)
                    <a href="{{ route('admin.lottery.index', $event) }}" class="btn btn-ghost btn-sm">✕</a>
                @endif
            </form>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        @foreach ($lotteryExtraKeys as $key)
                            <th>{{ $key }}</th>
                        @endforeach
                        <th>Entered</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr {{ $entry->is_winner ? 'style=background:rgba(255,215,0,.05)' : '' }}>
                            <td class="text-muted text-xs">{{ $entry->id }}</td>
                            <td><span class="font-bold">{{ $entry->name }}</span></td>
                            <td>{{ $entry->phone }}</td>
                            <td class="text-muted">{{ $entry->email ?? '—' }}</td>
                            @foreach ($lotteryExtraKeys as $key)
                                <td class="text-muted">{{ $entry->extra_fields[$key] ?? '—' }}</td>
                            @endforeach
                            <td class="text-muted text-xs">{{ $entry->created_at->format('M d · H:i') }}</td>
                            <td>
                                @if ($entry->is_winner)
                                    <span class="badge badge-winner">🏆 WINNER</span>
                                @else
                                    <span class="badge badge-pending">Entered</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.lottery.destroy', $entry) }}"
                                    onsubmit="return confirm('Remove this entry?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm" style="color:var(--red)" title="Remove">🗑</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 7 + count($lotteryExtraKeys) }}">
                                <div class="empty-state" style="padding:30px">No entries found.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entries->hasPages())
            <div style="padding:12px 18px">{{ $entries->appends(['search' => $search])->links() }}</div>
        @endif
    </div>

@endsection

@push('scripts')
    @if (!$event->lottery_drawn && $totalCount > 0)
        <script>
            function animateDraw() {
                const btn = document.getElementById('drawBtn');
                const icon = document.getElementById('drumRoll');
                btn.disabled = true;
                btn.textContent = '🎲 Drawing…';
                const emojis = ['🎰', '🎲', '🎟', '🎉', '✨', '🏆', '⭐', '🎊'];
                let i = 0;
                const spin = setInterval(() => {
                    icon.textContent = emojis[i++ % emojis.length];
                }, 120);
                setTimeout(() => {
                    clearInterval(spin);
                    icon.textContent = '🎊';
                    document.getElementById('drawForm').submit();
                }, 2800);
            }

            // Confetti if winner just drawn
            @if (session('winner_drawn'))
                (function confetti() {
                    const canvas = document.createElement('canvas');
                    canvas.style.cssText =
                        'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999';
                    document.body.appendChild(canvas);
                    const ctx = canvas.getContext('2d');
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;
                    const pieces = Array.from({
                        length: 120
                    }, () => ({
                        x: Math.random() * canvas.width,
                        y: -20,
                        r: Math.random() * 8 + 4,
                        d: Math.random() * 80 + 20,
                        color: ['#FF3D00', '#FFD700', '#22C55E', '#3B82F6', '#fff'][Math.floor(Math.random() *
                            5)],
                        tilt: Math.random() * 10 - 10,
                        tiltAngle: 0,
                        tiltSpeed: Math.random() * 0.1 + 0.05
                    }));
                    let angle = 0,
                        frame;

                    function draw() {
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        angle += 0.01;
                        pieces.forEach(p => {
                            p.tiltAngle += p.tiltSpeed;
                            p.y += (Math.cos(angle + p.d) + 1.5) * 1.8;
                            p.x += Math.sin(angle) * 1.5;
                            p.tilt = Math.sin(p.tiltAngle) * 15;
                            ctx.beginPath();
                            ctx.lineWidth = p.r / 2;
                            ctx.strokeStyle = p.color;
                            ctx.moveTo(p.x + p.tilt + p.r / 4, p.y);
                            ctx.lineTo(p.x + p.tilt, p.y + p.tilt + p.r / 4);
                            ctx.stroke();
                        });
                        if (pieces.some(p => p.y <= canvas.height)) frame = requestAnimationFrame(draw);
                        else {
                            cancelAnimationFrame(frame);
                            canvas.remove();
                        }
                    }
                    draw();
                    setTimeout(() => {
                        cancelAnimationFrame(frame);
                        canvas.remove();
                    }, 5000);
                })();
            @endif
        </script>
    @endif
@endpush
