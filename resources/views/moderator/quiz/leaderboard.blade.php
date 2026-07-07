@extends('layouts.moderator')
@section('title', 'Quiz Leaderboard — Round #' . $round->id)
@section('page-title', '<i data-lucide="trophy" class="lucide-icon"></i> Quiz Leaderboard — Round #' . $round->id)

@section('topbar-actions')
    <a href="{{ route('moderator.quiz.index', $event) }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="lucide-icon"></i> Quiz</a>
@endsection

@section('content')

@if ($winner)
    <div class="winner-card mb-3">
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
        <div class="text-muted text-xs">Round #{{ $round->id }} · {{ $round->questions->count() }} questions · {{ $round->time_limit_seconds }}s each</div>
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
                        <td style="font-weight:800;font-size:18px;color:{{ $i === 0 ? 'var(--gold)' : 'var(--muted)' }}">{{ $i + 1 }}</td>
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

@endsection
