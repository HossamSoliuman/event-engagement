@extends('layouts.admin')
@section('title', 'Quiz Leaderboard — Round #' . $round->id)
@section('page-title')
    <i data-lucide="trophy" class="lucide-icon"></i> Quiz Leaderboard — Round #{{ $round->id }}
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.quiz.index', $event) }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="lucide-icon"></i> Quiz</a>
@endsection

@section('content')

@if ($winner)
    <div class="winner-card mb-3" id="winnerCard">
        <div class="trophy"><i data-lucide="trophy" class="lucide-icon"></i></div>
        <h2>WINNER!</h2>
        <div style="font-size:28px;font-weight:800;color:var(--text);margin:10px 0">{{ $winner->guest_name }}</div>
        <p style="font-size:16px">{{ $winner->correct_count }} correct answer{{ $winner->correct_count != 1 ? 's' : '' }}</p>
        <p class="text-muted" style="font-size:14px">Total time: {{ number_format($winner->total_ms / 1000, 2) }}s</p>
    </div>
@else
    <div class="empty-state mb-3">
        <div class="empty-icon"><i data-lucide="users" class="lucide-icon"></i></div>
        <h3>No answers yet</h3>
        <p>Guests haven't answered any questions in this round.</p>
    </div>
@endif

<div class="card mb-3">
    <div class="card-header">
        <h3>Leaderboard</h3>
        <div class="text-muted text-xs">
            Round #{{ $round->id }} · {{ $round->questions->count() }} questions · {{ $round->time_limit_seconds }}s each
            @if ($round->status === 'active') · <span style="color:#4ade80">Active</span>
            @elseif ($round->status === 'finished') · <span style="color:var(--muted)">Finished</span>
            @endif
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Guest</th>
                    <th>Correct</th>
                    <th>Total Time</th>
                    @foreach ($questions as $q)
                        <th style="max-width:120px;font-size:10px">Q{{ $loop->iteration }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($leaderboard as $i => $row)
                    @php
                        $answers = \App\Models\QuizAnswer::where('quiz_round_id', $round->id)
                            ->where('session_token', $row->session_token)
                            ->get()
                            ->keyBy('quiz_question_id');
                    @endphp
                    <tr {{ $i === 0 ? 'style=background:rgba(255,215,0,.04)' : '' }}>
                        <td style="font-weight:800;font-size:18px;color:{{ $i === 0 ? 'var(--gold)' : 'var(--muted)' }}">
                            {{ $i === 0 ? '1' : ($i + 1) }}
                        </td>
                        <td><span class="font-bold">{{ $row->guest_name }}</span></td>
                        <td>
                            <span style="color:{{ $row->correct_count > 0 ? '#4ade80' : 'var(--muted)' }};font-weight:700">
                                {{ $row->correct_count }}/{{ $questions->count() }}
                            </span>
                        </td>
                        <td class="text-muted text-xs">{{ number_format($row->total_ms / 1000, 2) }}s</td>
                        @foreach ($questions as $q)
                            @php $a = $answers->get($q->id); @endphp
                            <td>
                                @if ($a)
                                    @if ($a->is_correct)
                                        <span style="color:#4ade80" title="{{ number_format($a->time_taken_ms/1000,2) }}s"><i data-lucide="check" class="lucide-icon"></i></span>
                                    @else
                                        <span style="color:var(--red)" title="{{ number_format($a->time_taken_ms/1000,2) }}s"><i data-lucide="x" class="lucide-icon"></i></span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 4 + $questions->count() }}">
                            <div class="empty-state" style="padding:30px">No answers submitted.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Questions</h3></div>
    <div class="card-body" style="padding:0">
        @foreach ($questions as $i => $q)
            <div style="padding:14px 18px;{{ !$loop->last ? 'border-bottom:1px solid var(--border)' : '' }}">
                <div style="font-weight:600;font-size:13px;margin-bottom:8px">{{ $i + 1 }}. {{ $q->question_text }}</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                    @foreach ($q->options as $j => $opt)
                        <div style="font-size:12px;padding:5px 9px;border-radius:6px;background:{{ $j === $q->correct_option ? 'rgba(74,222,128,.12)' : 'var(--dark)' }};border:1px solid {{ $j === $q->correct_option ? 'rgba(74,222,128,.35)' : 'var(--border)' }}">
                            <span style="font-weight:700;color:{{ $j === $q->correct_option ? '#4ade80' : 'var(--muted)' }}">{{ chr(65 + $j) }}.</span>
                            {{ $opt }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection

@push('scripts')
    @if ($winner && $round->status === 'finished')
        <script>
            (function confetti() {
                const canvas = document.createElement('canvas');
                canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999';
                document.body.appendChild(canvas);
                const ctx = canvas.getContext('2d');
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
                const pieces = Array.from({ length: 120 }, () => ({
                    x: Math.random() * canvas.width,
                    y: -20,
                    r: Math.random() * 8 + 4,
                    d: Math.random() * 80 + 20,
                    color: ['#FF3D00', '#FFD700', '#22C55E', '#3B82F6', '#fff'][Math.floor(Math.random() * 5)],
                    tilt: Math.random() * 10 - 10,
                    tiltAngle: 0,
                    tiltSpeed: Math.random() * 0.1 + 0.05
                }));
                let angle = 0, frame;
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
                    else { cancelAnimationFrame(frame); canvas.remove(); }
                }
                draw();
                setTimeout(() => { cancelAnimationFrame(frame); canvas.remove(); }, 5000);
            })();
        </script>
    @endif
@endpush
