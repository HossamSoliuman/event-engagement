@extends('layouts.moderator')
@section('title', 'Quiz to Win — ' . $event->name)
@section('page-title', '<i data-lucide="help-circle" class="lucide-icon"></i> Quiz to Win')

@section('topbar-actions')
    <a href="{{ route('moderator.quiz.export', $event) }}" class="btn btn-ghost btn-sm">⬇ CSV</a>
    <a href="{{ route('moderator.dashboard', $event) }}" class="btn btn-secondary btn-sm">← Dashboard</a>
@endsection

@section('content')

@if ($activeRound)
    <div class="card mb-3" style="border-color:rgba(99,232,130,.3);background:rgba(99,232,130,.04)">
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <div style="font-weight:700;font-size:15px;color:#4ade80">Round #{{ $activeRound->id }} — Active</div>
                <div class="text-muted text-sm">{{ $activeRound->questions_per_round }} questions · {{ $activeRound->time_limit_seconds }}s per question · Started {{ $activeRound->started_at?->diffForHumans() }}</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="{{ route('moderator.quiz.leaderboard', [$event, $activeRound]) }}" class="btn btn-secondary btn-sm">Leaderboard</a>
                <form method="POST" action="{{ route('moderator.quiz.end', $event) }}">
                    @csrf
                    <button class="btn btn-danger btn-sm" onclick="return confirm('End the active round now?')">End Round</button>
                </form>
            </div>
        </div>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
@endif

<div class="grid-2" style="gap:16px;align-items:start">

    <div>
        <div class="card mb-3">
            <div class="card-header">
                <h3><i data-lucide="list" class="lucide-icon"></i> Question Pool ({{ $questions->count() }})</h3>
            </div>

            @forelse ($questions as $q)
                <div style="border-bottom:1px solid var(--border);padding:14px 18px" id="question-row-{{ $q->id }}">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:8px">
                        <div style="display:flex;align-items:flex-start;gap:10px;flex:1">
                            @if ($q->sponsor_logo_url)
                                <img src="{{ $q->sponsor_logo_url }}" alt="Sponsor" style="height:28px;width:auto;border-radius:4px;background:#fff;padding:2px;flex-shrink:0">
                            @endif
                            <div style="font-weight:600;font-size:13px">{{ $q->question_text }}</div>
                        </div>
                        <div style="display:flex;gap:6px;flex-shrink:0">
                            <button class="btn btn-ghost btn-sm" onclick="toggleEdit({{ $q->id }})"><i data-lucide="pencil" class="lucide-icon"></i></button>
                            <form method="POST" action="{{ route('moderator.quiz.questions.destroy', [$event, $q]) }}" onsubmit="return confirm('Delete this question?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm" style="color:var(--red)"><i data-lucide="trash-2" class="lucide-icon"></i></button>
                            </form>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                        @foreach ($q->options as $i => $opt)
                            <div style="font-size:12px;padding:5px 9px;border-radius:6px;background:{{ $i === $q->correct_option ? 'rgba(74,222,128,.12)' : 'var(--dark)' }};border:1px solid {{ $i === $q->correct_option ? 'rgba(74,222,128,.35)' : 'var(--border)' }}">
                                <span style="font-weight:700;color:{{ $i === $q->correct_option ? '#4ade80' : 'var(--muted)' }}">{{ chr(65 + $i) }}.</span>
                                {{ $opt }}
                                @if ($i === $q->correct_option)
                                    <span style="color:#4ade80;font-size:10px"> ✓</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div id="edit-form-{{ $q->id }}" style="display:none;margin-top:12px">
                        <form method="POST" action="{{ route('moderator.quiz.questions.update', [$event, $q]) }}" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            <div class="form-group">
                                <label class="form-label">Question</label>
                                <textarea name="question_text" class="form-control" rows="2" required>{{ $q->question_text }}</textarea>
                            </div>
                            @foreach ($q->options as $i => $opt)
                                <div class="form-group">
                                    <label class="form-label" style="display:flex;align-items:center;gap:8px">
                                        Option {{ chr(65 + $i) }}
                                        <label style="font-weight:400;cursor:pointer">
                                            <input type="radio" name="correct_option" value="{{ $i }}" {{ $i === $q->correct_option ? 'checked' : '' }} required>
                                            Correct
                                        </label>
                                    </label>
                                    <input type="text" name="options[]" class="form-control" value="{{ $opt }}" required>
                                </div>
                            @endforeach
                            <div class="form-group">
                                <label class="form-label">Sponsor logo (optional)</label>
                                @if ($q->sponsor_logo_url)
                                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                        <img src="{{ $q->sponsor_logo_url }}" alt="Sponsor" style="height:32px;width:auto;border-radius:4px;background:#fff;padding:2px">
                                        <label style="font-weight:400;font-size:12px;cursor:pointer">
                                            <input type="checkbox" name="sponsor_logo_clear" value="1"> Remove logo
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="sponsor_logo" class="form-control" accept="image/*">
                            </div>
                            <div style="display:flex;gap:8px">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <button type="button" class="btn btn-ghost btn-sm" onclick="toggleEdit({{ $q->id }})">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty-state" style="padding:30px">
                    <div class="empty-icon"><i data-lucide="help-circle" class="lucide-icon"></i></div>
                    <h3>No questions yet</h3>
                    <p>Add questions to build your quiz pool.</p>
                </div>
            @endforelse
        </div>

        <div class="card">
            <div class="card-header"><h3>Add Question</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('moderator.quiz.questions.store', $event) }}" enctype="multipart/form-data">
                    @csrf
                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
                    @endif
                    <div class="form-group">
                        <label class="form-label">Question *</label>
                        <textarea name="question_text" class="form-control" rows="2" placeholder="e.g. In what year was the club founded?" required>{{ old('question_text') }}</textarea>
                    </div>
                    @for ($i = 0; $i < 4; $i++)
                        <div class="form-group">
                            <label class="form-label" style="display:flex;align-items:center;gap:10px">
                                Option {{ chr(65 + $i) }}
                                <label style="font-weight:400;cursor:pointer;font-size:12px">
                                    <input type="radio" name="correct_option" value="{{ $i }}" {{ old('correct_option', 0) == $i ? 'checked' : '' }} required>
                                    Correct answer
                                </label>
                            </label>
                            <input type="text" name="options[]" class="form-control" placeholder="Option {{ chr(65 + $i) }}" value="{{ old('options.' . $i) }}" required>
                        </div>
                    @endfor
                    <div class="form-group">
                        <label class="form-label">Sponsor logo for this question (optional)</label>
                        <input type="file" name="sponsor_logo" class="form-control" accept="image/*">
                        <div class="text-muted text-xs" style="margin-top:4px">Shown to guests with this question. PNG/JPG, max 2MB.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Question</button>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-header"><h3><i data-lucide="play-circle" class="lucide-icon"></i> Start New Round</h3></div>
            <div class="card-body">
                @if ($activeRound)
                    <div class="empty-state" style="padding:20px">
                        <div class="text-muted text-sm">End the current active round before starting a new one.</div>
                    </div>
                @elseif ($questions->where('is_active', true)->count() === 0)
                    <div class="empty-state" style="padding:20px">
                        <div class="text-muted text-sm">Add at least one question to the pool first.</div>
                    </div>
                @else
                    <form method="POST" action="{{ route('moderator.quiz.start', $event) }}" id="startRoundForm">
                        @csrf
                        <div class="form-row">
                            <div class="form-group mb-0">
                                <label class="form-label">Seconds per question</label>
                                <input type="number" name="time_limit_seconds" class="form-control" value="30" min="5" max="300" required>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Questions each guest answers</label>
                                <input type="number" name="questions_per_round" class="form-control" value="{{ min(3, $questions->where('is_active', true)->count()) }}" min="1" max="{{ $questions->where('is_active', true)->count() }}" required>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top:16px">
                            <label class="form-label">Question pool for this round *</label>
                            <div class="text-muted text-xs" style="margin-bottom:8px">Each guest gets a random, shuffled subset of this size drawn from the questions you select below.</div>
                            <div style="max-height:260px;overflow-y:auto;border:1px solid var(--border);border-radius:8px">
                                @foreach ($questions->where('is_active', true) as $q)
                                    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border);cursor:pointer">
                                        <input type="checkbox" name="question_ids[]" value="{{ $q->id }}" style="margin-top:3px;accent-color:var(--primary)">
                                        <span style="font-size:13px">{{ $q->question_text }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" onclick="return validateRoundForm()">Start Round</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3><i data-lucide="trophy" class="lucide-icon"></i> End Screen / Winner Message</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('moderator.quiz.settings', $event) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Message shown when the quiz ends</label>
                        <textarea name="quiz_winner_text" class="form-control" rows="2" placeholder="e.g. The winner will be presented live at the end of the event!">{{ old('quiz_winner_text', $event->quiz_winner_text) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End screen sponsor logo (optional)</label>
                        @if ($event->quiz_end_sponsor_logo_url)
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                <img src="{{ $event->quiz_end_sponsor_logo_url }}" alt="Sponsor" style="height:36px;width:auto;border-radius:4px;background:#fff;padding:2px">
                                <label style="font-weight:400;font-size:12px;cursor:pointer">
                                    <input type="checkbox" name="quiz_end_sponsor_logo_clear" value="1"> Remove logo
                                </label>
                            </div>
                        @endif
                        <input type="file" name="quiz_end_sponsor_logo" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary">Save End Screen</button>
                </form>
            </div>
        </div>

        @if ($rounds->count())
            <div class="card">
                <div class="card-header"><h3>Round History</h3></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Status</th>
                                <th>Questions</th>
                                <th>Answers</th>
                                <th>Started</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rounds as $round)
                                <tr>
                                    <td class="text-muted text-xs">{{ $round->id }}</td>
                                    <td>
                                        @if ($round->status === 'active')
                                            <span class="badge badge-active">Active</span>
                                        @elseif ($round->status === 'finished')
                                            <span class="badge badge-pending">Finished</span>
                                        @else
                                            <span class="badge">Waiting</span>
                                        @endif
                                    </td>
                                    <td>{{ $round->questions->count() }}</td>
                                    <td>{{ $round->answers_count }}</td>
                                    <td class="text-muted text-xs">{{ $round->started_at?->format('M d · H:i') ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('moderator.quiz.leaderboard', [$event, $round]) }}" class="btn btn-ghost btn-sm">Results</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

</div>

@endsection

@push('scripts')
<script>
    function toggleEdit(id) {
        const form = document.getElementById('edit-form-' + id);
        form.style.display = form.style.display === 'none' ? '' : 'none';
    }

    function validateRoundForm() {
        const checked = document.querySelectorAll('#startRoundForm input[name="question_ids[]"]:checked');
        const perRound = parseInt(document.querySelector('[name="questions_per_round"]').value);
        if (checked.length === 0) {
            alert('Select at least one question.');
            return false;
        }
        if (checked.length < perRound) {
            alert('The pool has fewer questions than each guest should answer. Select more questions or reduce the per-guest count.');
            return false;
        }
        return true;
    }
</script>
@endpush
