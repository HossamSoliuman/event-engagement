{{-- Shared by the admin and moderator survey pages; $routePrefix picks the route set. --}}
@php
    $svRoute = fn (string $name, array $extra = []) => route($routePrefix . 'survey.' . $name, array_merge([$event], $extra));
@endphp

@unless ($event->module_survey)
    <div class="alert alert-info mb-3">
        <i data-lucide="eye-off" class="lucide-icon"></i>
        The Fan Survey is switched off, so fans can't see it yet. Switch it on under Module Controls on the event page.
    </div>
@endunless

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Responses</div>
        <div class="stat-value c-blue">{{ number_format($responseCount) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Questions</div>
        <div class="stat-value">{{ $questions->count() }}</div>
    </div>
</div>

<div class="grid-2" style="gap:16px;align-items:start">

    <div>
        <div class="card mb-3">
            <div class="card-header">
                <h3><i data-lucide="list-checks" class="lucide-icon"></i> Questions ({{ $questions->count() }})</h3>
            </div>

            @forelse ($questions as $question)
                <div style="border-bottom:1px solid var(--border);padding:14px 18px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                        <div style="flex:1;min-width:0">
                            <div class="text-muted text-xs" style="letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">
                                Q{{ $loop->iteration }} · {{ $question->isChoice() ? 'Multiple choice' : 'Free text' }}{{ $question->is_required ? '' : ' · optional' }}
                            </div>
                            <div style="font-weight:700;font-size:14px;word-break:break-word">{{ $question->question }}</div>
                            @if ($question->isChoice())
                                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px">
                                    @foreach ($question->optionList() as $option)
                                        <span class="badge">{{ $option }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div style="display:flex;gap:6px;flex-shrink:0">
                            <button type="button" class="btn btn-ghost btn-sm" title="Edit" onclick="svToggleEdit({{ $question->id }})">
                                <i data-lucide="pencil" class="lucide-icon"></i>
                            </button>
                            <form method="POST" action="{{ $svRoute('questions.destroy', [$question]) }}"
                                onsubmit="return confirm('Delete this question? Answers already collected for it are deleted too.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm" title="Delete" style="color:var(--red)">
                                    <i data-lucide="trash-2" class="lucide-icon"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div id="sv-edit-{{ $question->id }}" style="display:none;margin-top:12px">
                        @if ($results[$question->id]['answered'] > 0)
                            <div class="form-hint" style="margin-bottom:10px">
                                Fans already answered this question. Renaming an option won't change answers already collected.
                            </div>
                        @endif
                        @include('admin.survey._question-form', [
                            'action' => $svRoute('questions.update', [$question]),
                            'question' => $question,
                        ])
                    </div>
                </div>
            @empty
                <div class="empty-state" style="padding:30px">
                    <div class="empty-icon"><i data-lucide="clipboard-list" class="lucide-icon"></i></div>
                    <h3>No questions yet</h3>
                    <p>Add your first question below. Fans see them in this order.</p>
                </div>
            @endforelse
        </div>

        <div class="card">
            <div class="card-header"><h3><i data-lucide="plus" class="lucide-icon"></i> Add Question</h3></div>
            <div class="card-body">
                @include('admin.survey._question-form', ['action' => $svRoute('questions.store')])
            </div>
        </div>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-header">
                <h3><i data-lucide="bar-chart-3" class="lucide-icon"></i> Results</h3>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <a href="{{ $svRoute('export') }}" class="btn btn-secondary btn-sm">
                        <i data-lucide="download" class="lucide-icon"></i> All answers
                    </a>
                    <a href="{{ $svRoute('export', ['type' => 'summary']) }}" class="btn btn-secondary btn-sm">
                        <i data-lucide="download" class="lucide-icon"></i> Summary
                    </a>
                </div>
            </div>

            @forelse ($questions as $question)
                @php $result = $results[$question->id]; @endphp
                <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
                    <div style="display:flex;justify-content:space-between;gap:10px;margin-bottom:10px">
                        <div style="font-weight:700;font-size:13px;word-break:break-word">Q{{ $loop->iteration }}. {{ $question->question }}</div>
                        <div class="text-muted text-xs" style="white-space:nowrap">
                            {{ $result['answered'] }} {{ \Illuminate\Support\Str::plural('answer', $result['answered']) }}
                        </div>
                    </div>

                    @if ($question->isChoice())
                        @foreach ($result['rows'] as $row)
                            <div style="margin-bottom:10px">
                                <div style="display:flex;justify-content:space-between;gap:10px;font-size:13px;margin-bottom:5px">
                                    <span style="word-break:break-word">
                                        {{ $row['label'] }}
                                        @unless (in_array($row['label'], $question->optionList(), true))
                                            <span class="text-muted text-xs">(old option)</span>
                                        @endunless
                                    </span>
                                    <span class="text-muted" style="white-space:nowrap">{{ $row['count'] }} · {{ $row['percent'] }}%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:{{ $row['percent'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        @php $latest = $latestTextAnswers[$question->id] ?? []; @endphp
                        @forelse ($latest as $answer)
                            <div style="background:var(--dark);border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;margin-bottom:6px;white-space:pre-line;word-break:break-word">{{ $answer }}</div>
                        @empty
                            <div class="text-muted text-sm">No answers yet.</div>
                        @endforelse
                        @if ($result['answered'] > count($latest))
                            <div class="text-muted text-xs" style="margin-top:6px">
                                Showing the newest {{ count($latest) }} — download "All answers" to see all {{ $result['answered'] }}.
                            </div>
                        @endif
                    @endif
                </div>
            @empty
                <div class="empty-state" style="padding:30px">
                    <div class="empty-icon"><i data-lucide="bar-chart-3" class="lucide-icon"></i></div>
                    <h3>No results yet</h3>
                    <p>Results appear here as soon as fans start answering.</p>
                </div>
            @endforelse
        </div>

        @if ($responseCount > 0)
            <div class="card">
                <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                    <div>
                        <div style="font-weight:600;font-size:13px">Clear all responses</div>
                        <div class="text-muted text-xs">Deletes every answer collected so far; the questions stay. Download the CSV first.</div>
                    </div>
                    <form method="POST" action="{{ $svRoute('reset') }}"
                        onsubmit="return confirm('Delete all {{ $responseCount }} survey responses? This cannot be undone.')">
                        @csrf
                        <button class="btn btn-danger btn-sm">Clear Responses</button>
                    </form>
                </div>
            </div>
        @endif
    </div>

</div>

@push('scripts')
    <script>
        const SV_MAX_OPTIONS = {{ \App\Models\SurveyQuestion::MAX_OPTIONS }};

        function svToggleEdit(id) {
            const el = document.getElementById('sv-edit-' + id);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }

        function svTypeChanged(select) {
            select.form.querySelector('.sv-options').style.display = select.value === 'choice' ? '' : 'none';
        }

        function svAddOption(button) {
            const list = button.closest('.sv-options').querySelector('.sv-option-list');
            if (list.children.length >= SV_MAX_OPTIONS) {
                return;
            }
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'options[]';
            input.className = 'form-control';
            input.maxLength = 120;
            input.placeholder = 'Option ' + (list.children.length + 1);
            list.appendChild(input);
            input.focus();
            button.disabled = list.children.length >= SV_MAX_OPTIONS;
        }
    </script>
@endpush
