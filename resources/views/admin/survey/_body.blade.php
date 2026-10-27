{{-- Shared by the admin and moderator survey pages; $routePrefix picks the route set. --}}
@php
    $svRoute = fn (string $name, array $extra = []) => route($routePrefix . 'survey.' . $name, array_merge([$event], $extra));
    $svTypeIcons = [
        \App\Models\SurveyQuestion::TYPE_CHOICE => 'circle-dot',
        \App\Models\SurveyQuestion::TYPE_MULTIPLE => 'list-checks',
        \App\Models\SurveyQuestion::TYPE_TEXT => 'text-cursor-input',
    ];
    $svOpenEdit = str_starts_with((string) old('_form'), 'edit-') ? (int) substr(old('_form'), 5) : null;
@endphp

@push('styles')
    <style>
        .sv-card { --sv-accent: var(--mod, var(--red)); }
        .sv-q { position: relative; border-bottom: 1px solid var(--border); padding: 14px 18px; scroll-margin-top: 84px; }
        .sv-q:last-child { border-bottom: none; }
        .sv-q.sv-dragging { z-index: 5; background: var(--card2); border-radius: 10px; box-shadow: 0 14px 34px rgba(0, 0, 0, .5), 0 0 0 1px var(--sv-accent); }
        .sv-drag { display: flex; align-items: center; justify-content: center; width: 22px; height: 26px; margin: 0 -6px 0 -8px; border-radius: 6px; color: var(--muted); opacity: .55; cursor: grab; touch-action: none; flex-shrink: 0; }
        .sv-drag:hover { opacity: 1; color: var(--text); background: rgba(255, 255, 255, .05); }
        .sv-dragging .sv-drag, body.sv-sorting, body.sv-sorting * { cursor: grabbing !important; }
        body.sv-sorting { user-select: none; }
        .sv-order-error { margin: 12px 18px 0; }
        .sv-order-error a { color: inherit; font-weight: 700; }
        .sv-q.sv-flash { animation: svFlash 2.4s ease-out; }
        @keyframes svFlash { from { background: color-mix(in srgb, var(--sv-accent) 16%, transparent); } to { background: transparent; } }
        .sv-q-row { display: flex; align-items: flex-start; gap: 12px; }
        .sv-q-num { width: 26px; height: 26px; border-radius: 50%; background: var(--card2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: var(--muted); flex-shrink: 0; }
        .sv-q-main { flex: 1; min-width: 0; }
        .sv-q-text { font-weight: 700; font-size: 14px; line-height: 1.4; word-break: break-word; }
        .sv-q-meta { display: flex; align-items: center; gap: 6px; margin-top: 4px; font-size: 10px; letter-spacing: 1.2px; text-transform: uppercase; color: var(--muted); }
        .sv-q-meta .lucide-icon { width: 12px; height: 12px; }
        .sv-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .sv-chip { font-size: 12px; padding: 3px 10px; border-radius: 20px; background: var(--card2); border: 1px solid var(--border); word-break: break-word; }
        .sv-q-actions { display: flex; gap: 2px; flex-shrink: 0; }
        .sv-q-actions form { display: contents; }
        .sv-q-actions .btn:disabled { opacity: .25; cursor: default; background: transparent; }
        .sv-q-edit { margin: 14px 0 4px 38px; }
        #sv-add { scroll-margin-top: 84px; }
        .sv-types { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .sv-type { position: relative; cursor: pointer; }
        .sv-type input { position: absolute; opacity: 0; pointer-events: none; }
        .sv-type span { display: flex; align-items: center; justify-content: center; gap: 7px; height: 100%; padding: 10px 8px; border: 1px solid var(--border); border-radius: 8px; background: var(--dark); color: var(--muted); font-size: 13px; font-weight: 600; text-align: center; transition: all .15s; }
        .sv-type:hover span { color: var(--text); }
        .sv-type input:checked + span { border-color: var(--sv-accent); color: var(--text); background: color-mix(in srgb, var(--sv-accent) 10%, var(--dark)); }
        .sv-type input:checked + span .lucide-icon { color: var(--sv-accent); }
        .sv-type input:focus-visible + span { outline: 2px solid var(--sv-accent); outline-offset: 2px; }
        .sv-form[data-type="text"] .sv-options { display: none; }
        .sv-option-list { display: flex; flex-direction: column; gap: 8px; }
        .sv-option { display: flex; align-items: center; gap: 10px; }
        .sv-option .form-control { flex: 1; min-width: 0; }
        .sv-mark { width: 14px; height: 14px; border: 2px solid var(--border); border-radius: 50%; flex-shrink: 0; }
        .sv-form[data-type="multiple"] .sv-mark { border-radius: 4px; }
        .sv-option-remove { display: flex; padding: 6px; border: none; border-radius: 6px; background: none; color: var(--muted); cursor: pointer; }
        .sv-option-remove:hover { color: var(--red); background: rgba(255, 255, 255, .04); }
        .sv-add-option { margin-top: 8px; }
        .sv-check { display: flex; align-items: center; gap: 8px; margin-bottom: 18px; font-size: 13px; cursor: pointer; }
        .sv-check input { width: 15px; height: 15px; accent-color: var(--sv-accent); cursor: pointer; }
        .sv-form-actions { display: flex; gap: 8px; }
        @media (max-width: 560px) {
            .sv-types { grid-template-columns: 1fr; }
            .sv-q-edit { margin-left: 0; }
        }
    </style>
@endpush

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
        <div class="card sv-card mb-3" id="sv-add">
            <div class="card-header"><h3><i data-lucide="plus" class="lucide-icon"></i> Add Question</h3></div>
            <div class="card-body">
                @include('admin.survey._question-form', ['action' => $svRoute('questions.store'), 'question' => null])
            </div>
        </div>

        <div class="card sv-card" id="sv-questions">
            <div class="card-header">
                <h3><i data-lucide="list-checks" class="lucide-icon"></i> Questions ({{ $questions->count() }})</h3>
            </div>

            <div class="alert alert-error sv-order-error" style="display:none">
                <span class="sv-order-error-text"></span> <a href="{{ $svRoute('index') }}">Reload</a>
            </div>

            <div class="sv-q-list" data-reorder-url="{{ $svRoute('questions.reorder') }}">
            @forelse ($questions as $question)
                <div class="sv-q {{ session('sv_highlight') == $question->id ? 'sv-flash' : '' }}" id="sv-q-{{ $question->id }}" data-id="{{ $question->id }}">
                    <div class="sv-q-row">
                        <div class="sv-drag" title="Drag to reorder" aria-hidden="true">
                            <i data-lucide="grip-vertical" class="lucide-icon"></i>
                        </div>
                        <div class="sv-q-num">{{ $loop->iteration }}</div>
                        <div class="sv-q-main">
                            <div class="sv-q-text">{{ $question->question }}</div>
                            <div class="sv-q-meta">
                                <i data-lucide="{{ $svTypeIcons[$question->answerType()] }}" class="lucide-icon"></i>
                                {{ $question->answerTypeLabel() }}{{ $question->is_required ? '' : ' · Optional' }}
                            </div>
                            @if ($question->isChoice())
                                <div class="sv-chips">
                                    @foreach ($question->optionList() as $option)
                                        <span class="sv-chip">{{ $option }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="sv-q-actions">
                            <button type="button" class="btn btn-ghost btn-sm sv-move" data-step="-1" title="Move up" aria-label="Move up"
                                onclick="svMove(this, -1)" @disabled($loop->first)>
                                <i data-lucide="chevron-up" class="lucide-icon"></i>
                            </button>
                            <button type="button" class="btn btn-ghost btn-sm sv-move" data-step="1" title="Move down" aria-label="Move down"
                                onclick="svMove(this, 1)" @disabled($loop->last)>
                                <i data-lucide="chevron-down" class="lucide-icon"></i>
                            </button>
                            <button type="button" class="btn btn-ghost btn-sm" title="Edit" aria-label="Edit" onclick="svToggleEdit({{ $question->id }})">
                                <i data-lucide="pencil" class="lucide-icon"></i>
                            </button>
                            <form method="POST" action="{{ $svRoute('questions.destroy', [$question]) }}"
                                data-confirm="Delete this question?" data-confirm-message="Answers already collected for it are deleted too." data-confirm-button="Delete" data-confirm-danger>
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm" title="Delete" aria-label="Delete" style="color:var(--red)">
                                    <i data-lucide="trash-2" class="lucide-icon"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="sv-q-edit" id="sv-edit-{{ $question->id }}" @if ($svOpenEdit !== $question->id) style="display:none" @endif>
                        @if ($results[$question->id]['answered'] > 0)
                            <div class="form-hint" style="margin:0 0 10px">
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
                    <p>Add your first question above. Fans see them in this order.</p>
                </div>
            @endforelse
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

            <div class="sv-results">
            @forelse ($questions as $question)
                @php $result = $results[$question->id]; @endphp
                <div class="sv-r" data-id="{{ $question->id }}" style="padding:14px 18px;border-bottom:1px solid var(--border)">
                    <div style="display:flex;justify-content:space-between;gap:10px;margin-bottom:10px">
                        <div style="font-weight:700;font-size:13px;word-break:break-word">Q<span class="sv-r-num">{{ $loop->iteration }}</span>. {{ $question->question }}</div>
                        <div class="text-muted text-xs" style="white-space:nowrap">
                            {{ $result['answered'] }} {{ \Illuminate\Support\Str::plural($question->allowsMultiple() ? 'fan' : 'answer', $result['answered']) }}
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
        </div>

        @if ($responseCount > 0)
            <div class="card">
                <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                    <div>
                        <div style="font-weight:600;font-size:13px">Clear all responses</div>
                        <div class="text-muted text-xs">Deletes every answer collected so far; the questions stay. Download the CSV first.</div>
                    </div>
                    <form method="POST" action="{{ $svRoute('reset') }}"
                        data-confirm="Delete all {{ $responseCount }} survey responses?" data-confirm-message="This cannot be undone." data-confirm-button="Delete all" data-confirm-danger>
                        @csrf
                        <button class="btn btn-danger btn-sm">Clear Responses</button>
                    </form>
                </div>
            </div>
        @endif
    </div>

</div>

<template id="sv-option-template">
    @include('admin.survey._option-row', ['value' => '', 'number' => 1])
</template>

@push('scripts')
    <script>
        const SV_MIN_OPTIONS = {{ \App\Models\SurveyQuestion::MIN_OPTIONS }};
        const SV_MAX_OPTIONS = {{ \App\Models\SurveyQuestion::MAX_OPTIONS }};

        function svToggleEdit(id) {
            const el = document.getElementById('sv-edit-' + id);
            const opening = el.style.display === 'none';
            el.style.display = opening ? 'block' : 'none';
            if (opening) {
                el.querySelector('.sv-question-input').focus();
            }
        }

        function svTypeChanged(radio) {
            radio.form.dataset.type = radio.value;
        }

        function svRefreshOptions(form) {
            const rows = form.querySelectorAll('.sv-option');
            rows.forEach((row, index) => {
                const input = row.querySelector('input');
                input.placeholder = 'Option ' + (index + 1);
                input.setAttribute('aria-label', 'Option ' + (index + 1));
            });
            form.querySelector('.sv-add-option').disabled = rows.length >= SV_MAX_OPTIONS;
        }

        /** Adds an option row (after the given row, or at the end) and returns its input, or null when full. */
        function svAddOption(form, afterRow = null, value = '') {
            const list = form.querySelector('.sv-option-list');
            if (list.children.length >= SV_MAX_OPTIONS) {
                return null;
            }
            const row = document.getElementById('sv-option-template').content.firstElementChild.cloneNode(true);
            row.querySelector('input').value = value;
            afterRow ? afterRow.after(row) : list.appendChild(row);
            svRefreshOptions(form);
            return row.querySelector('input');
        }

        function svRemoveOption(button) {
            const row = button.closest('.sv-option');
            const form = row.closest('form');
            const input = row.querySelector('input');
            if (form.querySelectorAll('.sv-option').length <= SV_MIN_OPTIONS) {
                input.value = '';
                input.focus();
                return;
            }
            const neighbour = (row.previousElementSibling || row.nextElementSibling).querySelector('input');
            row.remove();
            svRefreshOptions(form);
            neighbour.focus();
        }

        document.addEventListener('keydown', (event) => {
            const input = event.target;
            const form = input.closest?.('.sv-form');
            if (!form || event.isComposing) {
                return;
            }
            if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
                event.preventDefault();
                form.requestSubmit();
                return;
            }
            if (input.matches('.sv-question-input') && event.key === 'Enter' && form.dataset.type !== 'text') {
                event.preventDefault();
                form.querySelector('.sv-option input').focus();
                return;
            }
            if (!input.matches('.sv-option input')) {
                return;
            }
            const row = input.closest('.sv-option');
            if (event.key === 'Enter') {
                event.preventDefault();
                const next = row.nextElementSibling?.querySelector('input');
                if (next && next.value.trim() === '') {
                    next.focus();
                } else if (input.value.trim() !== '') {
                    svAddOption(form, row)?.focus();
                }
            } else if (event.key === 'Backspace' && input.value === '' && form.querySelectorAll('.sv-option').length > SV_MIN_OPTIONS) {
                event.preventDefault();
                svRemoveOption(row.querySelector('.sv-option-remove'));
            }
        });

        /** Pasting a list (one per line, or a row or column copied from Excel) fills one option per entry. */
        document.addEventListener('paste', (event) => {
            const input = event.target;
            if (!input.matches?.('.sv-form .sv-option input')) {
                return;
            }
            const lines = (event.clipboardData?.getData('text') || '')
                .split(/\r?\n|\t/)
                .map(line => line.replace(/^\s*[-*•·▪◦]\s+/, '').trim())
                .filter(Boolean);
            if (lines.length < 2) {
                return;
            }
            event.preventDefault();
            const form = input.closest('form');
            let row = input.closest('.sv-option');
            if (input.value.trim() === '') {
                input.value = lines.shift();
            }
            for (const line of lines) {
                const next = row.nextElementSibling;
                if (next && next.querySelector('input').value.trim() === '') {
                    next.querySelector('input').value = line;
                    row = next;
                    continue;
                }
                const added = svAddOption(form, row, line);
                if (!added) {
                    break;
                }
                row = added.closest('.sv-option');
            }
            row.querySelector('input').focus();
        });

        const svList = document.querySelector('.sv-q-list');
        let svDrag = null;
        let svSaveTimer = null;
        let svSaving = Promise.resolve();

        function svRows() {
            return [...svList.querySelectorAll(':scope > .sv-q')];
        }

        function svOrder() {
            return svRows().map(row => row.dataset.id);
        }

        function svMeasure() {
            return new Map(svRows().map(row => [row, row.getBoundingClientRect().top]));
        }

        /** Slides every row from where it was (in tops) to where it is now; the dragged row follows the pointer instead. */
        function svSlideFrom(tops) {
            svRows().forEach(row => {
                if (row === svDrag?.row || !tops.has(row)) {
                    return;
                }
                row.style.transition = 'none';
                row.style.transform = '';
                const delta = tops.get(row) - row.getBoundingClientRect().top;
                if (!delta) {
                    return;
                }
                row.style.transform = `translateY(${delta}px)`;
                row.getBoundingClientRect();
                row.style.transition = 'transform .2s ease';
                row.style.transform = '';
            });
        }

        function svFlash(row) {
            row.classList.remove('sv-flash');
            void row.offsetWidth;
            row.classList.add('sv-flash');
        }

        /** Renumbers the list, refreshes the arrow buttons and puts the results column in the same order. */
        function svRenumber() {
            const rows = svRows();
            const results = document.querySelector('.sv-results');
            rows.forEach((row, index) => {
                row.querySelector('.sv-q-num').textContent = index + 1;
                row.querySelector('.sv-move[data-step="-1"]').disabled = index === 0;
                row.querySelector('.sv-move[data-step="1"]').disabled = index === rows.length - 1;
                const result = results?.querySelector(`.sv-r[data-id="${row.dataset.id}"]`);
                if (result) {
                    results.appendChild(result);
                    result.querySelector('.sv-r-num').textContent = index + 1;
                }
            });
        }

        function svOrderChanged(row) {
            svRenumber();
            svFlash(row);
            clearTimeout(svSaveTimer);
            svSaveTimer = setTimeout(() => {
                svSaving = svSaving.then(svSaveOrder);
            }, 400);
        }

        function svShowOrderError(message) {
            const box = document.querySelector('.sv-order-error');
            box.querySelector('.sv-order-error-text').textContent = message;
            box.style.display = message ? '' : 'none';
        }

        async function svSaveOrder() {
            try {
                const response = await fetch(svList.dataset.reorderUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ order: svOrder() }),
                });
                if (response.status === 419) {
                    return svShowOrderError('Your session expired, so the new order was not saved.');
                }
                const data = await response.json().catch(() => ({}));
                svShowOrderError(response.ok ? '' : (data.message || 'The new order could not be saved.'));
            } catch {
                svShowOrderError('No connection, so the new order was not saved.');
            }
        }

        function svMove(button, step) {
            const row = button.closest('.sv-q');
            const neighbour = step < 0 ? row.previousElementSibling : row.nextElementSibling;
            if (!neighbour?.matches('.sv-q')) {
                return;
            }
            const tops = svMeasure();
            step < 0 ? row.after(neighbour) : row.before(neighbour);
            svSlideFrom(tops);
            svOrderChanged(row);
            setTimeout(() => row.scrollIntoView({ block: 'nearest', behavior: 'smooth' }), 200);
            if (button.disabled) {
                row.querySelector(`.sv-move[data-step="${-step}"]`).focus();
            }
        }

        function svDragUpdate() {
            const { row } = svDrag;
            const pointer = svDrag.clientY + window.scrollY;
            let offset = pointer - svDrag.startY;
            let next = row.nextElementSibling;
            let prev = row.previousElementSibling;
            while (next?.matches('.sv-q') && offset > next.offsetHeight / 2) {
                const tops = svMeasure();
                row.before(next);
                svDrag.startY += next.offsetHeight;
                offset = pointer - svDrag.startY;
                svSlideFrom(tops);
                next = row.nextElementSibling;
            }
            while (prev?.matches('.sv-q') && offset < -prev.offsetHeight / 2) {
                const tops = svMeasure();
                row.after(prev);
                svDrag.startY -= prev.offsetHeight;
                offset = pointer - svDrag.startY;
                svSlideFrom(tops);
                prev = row.previousElementSibling;
            }
            row.style.transform = `translateY(${offset}px)`;
        }

        /** Scrolls the page while the pointer is held near the top or bottom edge, so long lists can be dragged through. */
        function svDragFrame() {
            if (!svDrag) {
                return;
            }
            const edge = 70;
            const top = 56 + edge;
            if (svDrag.clientY < top) {
                window.scrollBy(0, -Math.ceil((top - svDrag.clientY) / 6));
            } else if (svDrag.clientY > window.innerHeight - edge) {
                window.scrollBy(0, Math.ceil((svDrag.clientY - window.innerHeight + edge) / 6));
            }
            svDragUpdate();
            svDrag.frame = requestAnimationFrame(svDragFrame);
        }

        function svDragEnd() {
            if (!svDrag) {
                return;
            }
            const { row, startOrder, frame } = svDrag;
            cancelAnimationFrame(frame);
            svDrag = null;
            document.body.classList.remove('sv-sorting');
            row.style.transition = 'transform .18s ease';
            row.style.transform = '';
            setTimeout(() => row.classList.remove('sv-dragging'), 180);
            if (svOrder().join() !== startOrder) {
                svOrderChanged(row);
            }
        }

        svList?.addEventListener('pointerdown', (event) => {
            const handle = event.target.closest('.sv-drag');
            if (!handle || event.button !== 0 || svDrag) {
                return;
            }
            event.preventDefault();
            handle.setPointerCapture(event.pointerId);
            const row = handle.closest('.sv-q');
            row.style.transition = 'none';
            row.classList.add('sv-dragging');
            document.body.classList.add('sv-sorting');
            svDrag = { row, clientY: event.clientY, startY: event.clientY + window.scrollY, startOrder: svOrder().join() };
            svDrag.frame = requestAnimationFrame(svDragFrame);
        });

        svList?.addEventListener('pointermove', (event) => {
            if (svDrag) {
                svDrag.clientY = event.clientY;
                svDragUpdate();
            }
        });

        svList?.addEventListener('pointerup', (event) => {
            if (svDrag) {
                svDrag.clientY = event.clientY;
                svDragUpdate();
            }
            svDragEnd();
        });
        svList?.addEventListener('pointercancel', svDragEnd);
        svList?.addEventListener('lostpointercapture', svDragEnd);

        @if (session('sv_continue'))
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelector('#sv-add .sv-question-input')?.focus();
            });
        @endif
    </script>
@endpush
