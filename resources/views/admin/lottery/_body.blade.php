{{-- Shared by the admin and moderator lottery pages; $routePrefix picks the route set. --}}
@php
    $ltRoute = fn (string $name, array $extra = []) => route($routePrefix . 'lottery.' . $name, array_merge([$event], $extra));
    $destroyRoute = fn ($entry) => $routePrefix === 'admin.'
        ? route('admin.lottery.destroy', $entry)
        : route('moderator.lottery.destroy', [$event, $entry]);
    $nextRank = $winners->count() + 1;
    $drawLabel = $winners->isEmpty() ? 'Draw the Winner!' : 'Draw winner #' . $nextRank;
    $drawOnScreen = (bool) old('on_screen', $screen->draw_on_screen);
    $holdResult = (bool) old('hold_result', $screen->result_seconds === 0);
    $resultSeconds = old('result_seconds', $screen->result_seconds ?: 30);
    $nameFormat = old('name_format', $screen->name_format);
    $settingChecked = fn (string $field) => (bool) old($field, $screen->{$field});
@endphp

@push('styles')
    <style>
        [hidden] { display: none !important; }
        .lt { --lt-accent: var(--mod, var(--red)); }

        .lt-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: 16px; align-items: start; margin-bottom: 16px; }
        .lt-grid > .card { margin-top: 0; }

        .lt-console { overflow: hidden; }
        .lt-draw { text-align: center; padding: 34px 26px 30px; }
        .lt-draw-icon { display: inline-flex; align-items: center; justify-content: center; width: 84px; height: 84px; border-radius: 50%; background: rgba(255, 215, 0, .1); color: var(--gold); margin-bottom: 14px; }
        .lt-draw-icon .lucide-icon { width: 40px; height: 40px; }
        .lt-draw-title { font-size: 24px; margin-bottom: 6px; }
        .lt-draw-sub { color: var(--muted); font-size: 13px; }
        .lt-draw-form { max-width: 420px; margin: 22px auto 0; display: flex; flex-direction: column; gap: 14px; text-align: left; }
        .lt-draw-form .form-group { margin-bottom: 0; }
        .lt-draw-btn { justify-content: center; width: 100%; font-size: 16px; padding: 14px 24px; }
        .lt-draw-note { font-size: 12px; color: var(--gold); text-align: center; }

        .lt-switch { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px; background: var(--card2); cursor: pointer; user-select: none; transition: border-color .15s; }
        .lt-switch:hover { border-color: var(--muted); }
        .lt-switch input[type=checkbox] { position: absolute; opacity: 0; pointer-events: none; }
        .lt-switch-track { position: relative; flex: 0 0 auto; width: 38px; height: 22px; border-radius: 22px; background: var(--border); transition: background .2s; }
        .lt-switch-track::after { content: ""; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; transition: transform .2s; }
        .lt-switch input:checked + .lt-switch-track { background: var(--gold); }
        .lt-switch input:checked + .lt-switch-track::after { transform: translateX(16px); }
        .lt-switch input:focus-visible + .lt-switch-track { outline: 2px solid var(--gold); outline-offset: 2px; }
        .lt-switch-text { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; }

        .lt-live { padding: 22px 24px; background: linear-gradient(160deg, rgba(255, 215, 0, .1), rgba(255, 215, 0, 0) 60%); border-bottom: 1px solid var(--border); }
        .lt-live-head { display: flex; align-items: center; gap: 8px; font-size: 10px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--gold); }
        .lt-live-dot { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; box-shadow: 0 0 0 0 rgba(239, 68, 68, .6); animation: ltPing 1.6s infinite; }
        @keyframes ltPing { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, .6); } 80%, 100% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } }
        .lt-live-type { margin-left: auto; color: var(--muted); }
        .lt-live-main { text-align: center; padding: 18px 0 16px; }
        .lt-live-big { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 46px; line-height: 1.05; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .lt-live-big.is-name { font-size: 30px; color: var(--gold); }
        .lt-live-label { margin-top: 6px; font-size: 13px; color: var(--muted); }
        .lt-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
        .lt-step span { display: block; margin-top: 6px; font-size: 11px; font-weight: 600; color: var(--muted); text-align: center; }
        .lt-step.is-active span { color: var(--text); }
        .lt-step-bar { height: 5px; border-radius: 5px; background: rgba(255, 255, 255, .08); overflow: hidden; }
        .lt-step-bar > div { height: 100%; width: 0; background: var(--gold); border-radius: 5px; }
        .lt-live-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .lt-live-actions form { display: contents; }

        .lt-winners .card-header h3 { display: flex; align-items: center; gap: 8px; }
        .lt-head-actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .lt-head-actions form { display: contents; }
        .lt-danger { color: #f87171 !important; }
        .lt-winner { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border-bottom: 1px solid var(--border); transition: background .3s; }
        .lt-winner:last-child { border-bottom: none; }
        .lt-winner.is-new { background: rgba(255, 215, 0, .07); }
        .lt-winner.is-live { background: rgba(255, 215, 0, .05); box-shadow: inset 3px 0 0 var(--gold); }
        .lt-rank { flex: 0 0 auto; display: flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 50%; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 14px; background: rgba(255, 255, 255, .06); color: var(--text); }
        .lt-rank-1 { background: linear-gradient(135deg, #FFE066, #E6B800); color: #1a1400; }
        .lt-rank-2 { background: linear-gradient(135deg, #E5E7EB, #9CA3AF); color: #111827; }
        .lt-rank-3 { background: linear-gradient(135deg, #F0B27A, #B8732E); color: #1f1206; }
        .lt-winner-info { flex: 1; min-width: 0; }
        .lt-winner-name { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 15px; }
        .lt-winner-name > span:first-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .lt-onair { flex: 0 0 auto; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; background: var(--gold); color: #0A0A18; }
        .lt-winner-meta { margin-top: 3px; font-size: 12px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .lt-prize { display: inline-flex; align-items: center; gap: 5px; margin-top: 6px; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: rgba(255, 215, 0, .1); color: var(--gold); max-width: 100%; }
        .lt-prize .lucide-icon { width: 13px; height: 13px; flex: 0 0 auto; }
        .lt-winner-actions { display: flex; gap: 4px; flex: 0 0 auto; }
        .lt-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--border); background: var(--card2); color: var(--muted); cursor: pointer; transition: color .15s, border-color .15s; }
        .lt-icon-btn:hover:not(:disabled) { color: var(--text); border-color: var(--muted); }
        .lt-icon-btn:disabled { opacity: .35; cursor: default; }
        .lt-empty { padding: 40px 20px; }

        .lt-timeline { display: flex; gap: 4px; height: 46px; margin-bottom: 22px; }
        .lt-seg { display: flex; flex-direction: column; justify-content: center; min-width: 76px; padding: 0 12px; border-radius: 8px; font-size: 11px; transition: flex-grow .3s; overflow: hidden; white-space: nowrap; }
        .lt-seg span { color: var(--muted); font-weight: 600; }
        .lt-seg b { font-family: 'Syne', sans-serif; font-size: 14px; }
        .lt-seg-countdown { background: rgba(59, 130, 246, .14); }
        .lt-seg-countdown b { color: #60a5fa; }
        .lt-seg-drawing { background: rgba(168, 85, 247, .14); }
        .lt-seg-drawing b { color: #c084fc; }
        .lt-seg-result { background: rgba(255, 215, 0, .12); }
        .lt-seg-result b { color: var(--gold); }

        .lt-timing { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
        .lt-field .form-label { margin-bottom: 8px; }
        .lt-stepper { display: inline-flex; align-items: center; height: 40px; border: 1px solid var(--border); border-radius: 30px; background: var(--card2); overflow: hidden; transition: border-color .15s, opacity .15s; }
        .lt-stepper:focus-within { border-color: var(--lt-accent); }
        .lt-stepper button { width: 38px; height: 100%; border: none; background: none; color: var(--muted); font-size: 18px; line-height: 1; cursor: pointer; }
        .lt-stepper button:hover:not(:disabled) { color: var(--text); background: rgba(255, 255, 255, .05); }
        .lt-stepper button:disabled { opacity: .3; cursor: default; }
        .lt-stepper input { width: 50px; height: 100%; border: none; background: none; color: var(--text); font-size: 15px; font-weight: 700; text-align: center; font-variant-numeric: tabular-nums; -moz-appearance: textfield; appearance: textfield; }
        .lt-stepper input::-webkit-outer-spin-button, .lt-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .lt-stepper input:focus { outline: none; }
        .lt-stepper.is-off { opacity: .35; pointer-events: none; }
        .lt-unit { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }
        .lt-hold { display: flex; width: fit-content; align-items: center; gap: 7px; margin-top: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }

        .lt-display { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 4px 24px; }
        .lt-segmented { display: inline-flex; padding: 3px; border: 1px solid var(--border); border-radius: 10px; background: var(--card2); flex-wrap: wrap; }
        .lt-segmented label { cursor: pointer; }
        .lt-segmented input { position: absolute; opacity: 0; pointer-events: none; }
        .lt-segmented span { display: block; padding: 7px 14px; border-radius: 7px; font-size: 13px; font-weight: 600; color: var(--muted); transition: background .15s, color .15s; }
        .lt-segmented input:checked + span { background: var(--border); color: var(--text); }
        .lt-segmented input:focus-visible + span { outline: 2px solid var(--lt-accent); }
        .lt-checks { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 24px; margin-top: 4px; }
        .lt-check { display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; }
        .lt-check input, .lt-hold input { width: 16px; height: 16px; accent-color: var(--gold); cursor: pointer; }
        .lt-settings-foot { display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--border); }

        .lt-entry-winner { background: rgba(255, 215, 0, .04); }

        .lt-toast { position: fixed; left: 50%; bottom: 26px; z-index: 300; transform: translate(-50%, 20px); padding: 11px 18px; border-radius: 10px; background: var(--card2); border: 1px solid var(--border); box-shadow: 0 16px 40px rgba(0, 0, 0, .45); font-size: 13px; font-weight: 600; opacity: 0; pointer-events: none; transition: opacity .2s, transform .2s; max-width: calc(100vw - 32px); }
        .lt-toast.is-shown { opacity: 1; transform: translate(-50%, 0); }
        .lt-toast.is-error { border-color: rgba(239, 68, 68, .45); color: #f87171; }

        @media (max-width: 900px) {
            .lt-grid, .lt-timing, .lt-display, .lt-checks { grid-template-columns: 1fr; }
        }
    </style>
@endpush

<div class="lt">
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:16px">
        <div class="stat-card">
            <div class="stat-label">Total Entries</div>
            <div class="stat-value c-gold">{{ $totalCount }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Winners</div>
            <div class="stat-value">{{ $winners->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Still in the Draw</div>
            <div class="stat-value">{{ $remainingCount }}</div>
        </div>
    </div>

    <div class="lt-grid">
        <div class="card lt-console">
            <div class="lt-live" id="ltLive" hidden>
                <div class="lt-live-head">
                    <span class="lt-live-dot"></span>
                    <span>On the vidiwall</span>
                    <span class="lt-live-type" id="ltLiveType"></span>
                </div>
                <div class="lt-live-main">
                    <div class="lt-live-big" id="ltLiveBig"></div>
                    <div class="lt-live-label" id="ltLiveLabel"></div>
                </div>
                <div class="lt-steps" id="ltSteps">
                    <div class="lt-step" data-step="countdown"><div class="lt-step-bar"><div></div></div><span>Countdown</span></div>
                    <div class="lt-step" data-step="drawing"><div class="lt-step-bar"><div></div></div><span>Drawing</span></div>
                    <div class="lt-step" data-step="result"><div class="lt-step-bar"><div></div></div><span>Winner</span></div>
                </div>
                <div class="lt-live-actions">
                    <form method="POST" action="{{ $ltRoute('screen.reveal') }}" data-lt-ajax>
                        @csrf
                        <button type="submit" class="btn btn-gold" id="ltRevealBtn"><i data-lucide="sparkles" class="lucide-icon"></i> Reveal now</button>
                    </form>
                    <form method="POST" action="{{ $ltRoute('screen.hide') }}" data-lt-ajax>
                        @csrf
                        <button type="submit" class="btn btn-secondary"><i data-lucide="eye-off" class="lucide-icon"></i> Hide from screen</button>
                    </form>
                </div>
            </div>

            <div class="lt-draw">
                @if ($totalCount === 0)
                    <div class="lt-draw-icon"><i data-lucide="ticket" class="lucide-icon"></i></div>
                    <h2 class="lt-draw-title">No entries yet</h2>
                    <p class="lt-draw-sub">Guests need to enter via the lottery module on the event page.</p>
                @elseif ($remainingCount === 0)
                    <div class="lt-draw-icon"><i data-lucide="party-popper" class="lucide-icon"></i></div>
                    <h2 class="lt-draw-title">Everyone has won</h2>
                    <p class="lt-draw-sub">All {{ $totalCount }} {{ Str::plural('entry', $totalCount) }} are winners. Put a winner back into the draw or reset the lottery to draw again.</p>
                @else
                    <div class="lt-draw-icon" id="drumRoll"><i data-lucide="ticket" class="lucide-icon"></i></div>
                    <h2 class="lt-draw-title">{{ $winners->isEmpty() ? 'Ready to Draw!' : 'Draw winner #' . $nextRank }}</h2>
                    <form method="POST" action="{{ $ltRoute('draw') }}" id="drawForm" class="lt-draw-form">
                        @csrf
                        <input type="hidden" name="next_rank" value="{{ $nextRank }}">
                        <div class="form-group">
                            <label class="form-label" for="ltPrize">Prize</label>
                            <input type="text" id="ltPrize" name="prize" class="form-control" maxlength="120" autocomplete="off"
                                placeholder="Optional, e.g. Signed helmet" value="{{ old('prize') }}">
                        </div>
                        <label class="lt-switch">
                            <input type="hidden" name="on_screen" value="0">
                            <input type="checkbox" name="on_screen" value="1" id="ltOnScreen" @checked($drawOnScreen)>
                            <span class="lt-switch-track"></span>
                            <span class="lt-switch-text"><i data-lucide="monitor-play" class="lucide-icon"></i> Run the draw on the vidiwall</span>
                        </label>
                        <button type="submit" class="btn btn-gold btn-lg lt-draw-btn" id="drawBtn">
                            <i data-lucide="ticket" class="lucide-icon"></i> <span id="drawBtnLabel">{{ $drawLabel }}</span>
                        </button>
                        <div class="lt-draw-note" id="ltDrawNote" hidden>A draw is still running on the vidiwall. Reveal it or hide it first.</div>
                    </form>
                @endif
            </div>
        </div>

        <div class="card lt-winners">
            <div class="card-header">
                <h3><i data-lucide="trophy" class="lucide-icon"></i> Winners ({{ $winners->count() }})</h3>
                @if ($winners->isNotEmpty())
                    <div class="lt-head-actions">
                        <form method="POST" action="{{ $ltRoute('screen.board') }}" data-lt-ajax>
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm lt-screen-btn"><i data-lucide="monitor-play" class="lucide-icon"></i> Show all on vidiwall</button>
                        </form>
                        <form method="POST" action="{{ $ltRoute('reset') }}"
                            onsubmit="return confirm(@js($winners->count() === 1 ? 'Clear ' . $winners->first()->name . ' as the winner and reopen the draw?' : 'Clear all ' . $winners->count() . ' winners and reopen the draw?'))">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm lt-danger"><i data-lucide="rotate-ccw" class="lucide-icon"></i> Reset Lottery</button>
                        </form>
                    </div>
                @endif
            </div>

            @forelse ($winners as $winner)
                <div class="lt-winner {{ (int) session('winner_drawn') === $winner->id ? 'is-new' : '' }}" data-entry="{{ $winner->id }}">
                    <div class="lt-rank lt-rank-{{ min($winner->winner_rank, 4) }}">#{{ $winner->winner_rank }}</div>
                    <div class="lt-winner-info">
                        <div class="lt-winner-name">
                            <span>{{ $winner->name }}</span>
                            <span class="lt-onair" hidden>On screen</span>
                        </div>
                        <div class="lt-winner-meta">
                            {{ $winner->phone }}@if ($winner->email) · {{ $winner->email }}@endif · drawn {{ $winner->won_at?->format('H:i') }}
                        </div>
                        @if ($winner->prize)
                            <div class="lt-prize"><i data-lucide="gift" class="lucide-icon"></i> <span class="truncate">{{ $winner->prize }}</span></div>
                        @endif
                    </div>
                    <div class="lt-winner-actions">
                        <form method="POST" action="{{ $ltRoute('screen.winner', [$winner]) }}" data-lt-ajax>
                            @csrf
                            <button type="submit" class="lt-icon-btn lt-screen-btn" title="Show on the vidiwall" aria-label="Show {{ $winner->name }} on the vidiwall"><i data-lucide="cast" class="lucide-icon"></i></button>
                        </form>
                        <form method="POST" action="{{ $ltRoute('redraw', [$winner]) }}"
                            onsubmit="return confirm(@js('Redraw winner #' . $winner->winner_rank . '? ' . $winner->name . ' goes back into the draw and a new winner takes their place.'))">
                            @csrf
                            <input type="hidden" name="on_screen" value="{{ $drawOnScreen ? 1 : 0 }}" data-lt-follow-screen>
                            <button type="submit" class="lt-icon-btn lt-redraw-btn" title="Redraw this place" aria-label="Redraw winner #{{ $winner->winner_rank }}" @disabled($remainingCount === 0)><i data-lucide="refresh-cw" class="lucide-icon"></i></button>
                        </form>
                        <form method="POST" action="{{ $ltRoute('release', [$winner]) }}"
                            onsubmit="return confirm(@js('Put ' . $winner->name . ' back into the draw? They will no longer be winner #' . $winner->winner_rank . '.'))">
                            @csrf
                            <button type="submit" class="lt-icon-btn" title="Put back into the draw" aria-label="Put {{ $winner->name }} back into the draw"><i data-lucide="undo-2" class="lucide-icon"></i></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty-state lt-empty">
                    <div class="empty-icon"><i data-lucide="trophy" class="lucide-icon"></i></div>
                    <h3>No winners yet</h3>
                    <p>Every draw adds a winner here, numbered in the order they were drawn.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="card mb-3" id="vidiwall-show">
        <div class="card-header">
            <h3><i data-lucide="monitor-play" class="lucide-icon"></i> Vidiwall Show</h3>
            <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-ghost btn-sm">Open vidiwall <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
        </div>
        <form method="POST" action="{{ $ltRoute('screen.update') }}" class="card-body" id="ltSettings">
            @csrf
            @method('PUT')

            <div class="lt-timeline" aria-hidden="true">
                <div class="lt-seg lt-seg-countdown" id="ltSegCountdown"><span>Countdown</span><b></b></div>
                <div class="lt-seg lt-seg-drawing" id="ltSegDrawing"><span>Drawing</span><b></b></div>
                <div class="lt-seg lt-seg-result" id="ltSegResult"><span>Winner</span><b></b></div>
            </div>

            <div class="lt-timing">
                <div class="lt-field">
                    <label class="form-label" for="ltCountdown">Countdown</label>
                    <div class="lt-stepper">
                        <button type="button" data-step="-1" aria-label="Shorter countdown">−</button>
                        <input type="number" id="ltCountdown" name="countdown_seconds" min="0" max="60" required value="{{ old('countdown_seconds', $screen->countdown_seconds) }}">
                        <button type="button" data-step="1" aria-label="Longer countdown">+</button>
                    </div>
                    <span class="lt-unit">seconds, 0 skips it</span>
                </div>
                <div class="lt-field">
                    <label class="form-label" for="ltDrawing">Drawing</label>
                    <div class="lt-stepper">
                        <button type="button" data-step="-1" aria-label="Shorter drawing">−</button>
                        <input type="number" id="ltDrawing" name="drawing_seconds" min="1" max="30" required value="{{ old('drawing_seconds', $screen->drawing_seconds) }}">
                        <button type="button" data-step="1" aria-label="Longer drawing">+</button>
                    </div>
                    <span class="lt-unit">seconds of names spinning</span>
                </div>
                <div class="lt-field">
                    <label class="form-label" for="ltResult">Winner on screen</label>
                    <div class="lt-stepper {{ $holdResult ? 'is-off' : '' }}" id="ltResultStepper">
                        <button type="button" data-step="-1" aria-label="Shorter result">−</button>
                        <input type="number" id="ltResult" name="result_seconds" min="3" max="600" value="{{ $resultSeconds }}" @disabled($holdResult)>
                        <button type="button" data-step="1" aria-label="Longer result">+</button>
                    </div>
                    <label class="lt-hold">
                        <input type="hidden" name="hold_result" value="0">
                        <input type="checkbox" name="hold_result" value="1" id="ltHold" @checked($holdResult)> Until I hide it
                    </label>
                </div>
            </div>

            <div class="lt-display">
                <div class="form-group">
                    <label class="form-label" for="ltHeadline">Headline</label>
                    <input type="text" id="ltHeadline" name="headline" class="form-control" maxlength="60"
                        placeholder="{{ $event->lottery_title ?: 'Lucky Draw' }}" value="{{ old('headline', $screen->headline) }}">
                </div>
                <div class="form-group">
                    <span class="form-label">Winner name</span>
                    <div class="lt-segmented" role="radiogroup" aria-label="Winner name">
                        <label><input type="radio" name="name_format" value="full" @checked($nameFormat === 'full')><span>Full name</span></label>
                        <label><input type="radio" name="name_format" value="short" @checked($nameFormat === 'short')><span>First name + initial</span></label>
                    </div>
                </div>
                <div class="lt-checks">
                    @foreach ([
                        'show_prize' => 'Show the prize',
                        'show_phone' => 'Show the last 3 phone digits',
                        'show_entry_count' => 'Show how many entries are in the draw',
                        'confetti' => 'Confetti on the reveal',
                    ] as $field => $label)
                        <label class="lt-check">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input type="checkbox" name="{{ $field }}" value="1" @checked($settingChecked($field))> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="lt-settings-foot">
                <button type="submit" class="btn btn-primary">Save show settings</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>All Entries ({{ $totalCount }})</h3>
            <form method="GET" action="{{ $ltRoute('index') }}" style="display:flex;gap:8px">
                <div class="search-bar">
                    <input name="search" class="form-control" placeholder="Search name or phone…" value="{{ $search }}" style="width:220px">
                </div>
                <button class="btn btn-secondary btn-sm">Search</button>
                @if ($search)
                    <a href="{{ $ltRoute('index') }}" class="btn btn-ghost btn-sm"><i data-lucide="x" class="lucide-icon"></i></a>
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
                    @forelse ($entries as $entry)
                        <tr class="{{ $entry->is_winner ? 'lt-entry-winner' : '' }}">
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
                                    <span class="badge badge-winner"><i data-lucide="trophy" class="lucide-icon"></i> Winner #{{ $entry->winner_rank }}</span>
                                @else
                                    <span class="badge badge-pending">Entered</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ $destroyRoute($entry) }}"
                                    onsubmit="return confirm(@js($entry->is_winner ? 'Remove ' . $entry->name . '? They are winner #' . $entry->winner_rank . ' and the winners after them move up a place.' : 'Remove this entry?'))">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm" style="color:var(--red)" title="Remove"><i data-lucide="trash-2" class="lucide-icon"></i></button>
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

    <div class="lt-toast" id="ltToast" role="status" aria-live="polite"></div>
</div>

@push('scripts')
    <script>
        function ltConfetti() {
            const canvas = document.createElement('canvas');
            canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999';
            document.body.appendChild(canvas);
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            const pieces = Array.from({ length: 120 }, () => ({
                x: Math.random() * canvas.width,
                y: -20 - Math.random() * canvas.height * .3,
                r: Math.random() * 8 + 4,
                d: Math.random() * 80 + 20,
                color: ['#FF3D00', '#FFD700', '#22C55E', '#3B82F6', '#fff'][Math.floor(Math.random() * 5)],
                tilt: 0,
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
        }

        /* Off-screen draws keep the quick drum roll here before the form goes. */
        function animateDraw() {
            const btn = document.getElementById('drawBtn');
            const icon = document.getElementById('drumRoll');
            btn.disabled = true;
            document.getElementById('drawBtnLabel').textContent = 'Drawing…';
            const iconNames = ['ticket', 'dices', 'gift', 'party-popper', 'sparkles', 'trophy', 'star'];
            let i = 0;
            const spin = setInterval(() => {
                icon.innerHTML = '<i data-lucide="' + iconNames[i++ % iconNames.length] + '" class="lucide-icon"></i>';
                lucide.createIcons();
            }, 120);
            setTimeout(() => {
                clearInterval(spin);
                document.getElementById('drawForm').submit();
            }, 2400);
        }

        (function () {
            const STATUS_URL = @json($ltRoute('screen'));
            const CSRF = document.querySelector('meta[name=csrf-token]').content;
            const JUST_DRAWN = @json(session('winner_drawn') ? (int) session('winner_drawn') : null);
            const WINNERS_VERSION = @json($screenStatus['winners_version']);
            const REVEAL_PHASES = ['intro', 'countdown', 'drawing'];

            const $ = id => document.getElementById(id);
            const live = $('ltLive'), liveType = $('ltLiveType'), liveBig = $('ltLiveBig'), liveLabel = $('ltLiveLabel');
            const steps = $('ltSteps'), revealBtn = $('ltRevealBtn');
            const drawForm = $('drawForm'), drawBtn = $('drawBtn'), drawNote = $('ltDrawNote'), onScreen = $('ltOnScreen');
            const toast = $('ltToast');

            let status = @json($screenStatus);
            let origin = performance.now() - (status.show ? status.show.elapsed_ms : 0);
            let shownRevision = null;
            let celebrated = false;
            let navigating = false;
            let pollTimer = null;
            let toastTimer = null;

            function phaseOf(show, elapsed) {
                const revealAt = show.countdown_ms + show.drawing_ms;
                if (elapsed < 0) return 'intro';
                if (elapsed < show.countdown_ms) return 'countdown';
                if (elapsed < revealAt) return 'drawing';
                if (show.result_ms === 0 || elapsed < revealAt + show.result_ms) return 'result';
                return null;
            }

            function seconds(ms) {
                return Math.max(0, Math.ceil(ms / 1000));
            }

            function showToast(message, isError) {
                if (!message) return;
                toast.textContent = message;
                toast.classList.toggle('is-error', !!isError);
                toast.classList.add('is-shown');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toast.classList.remove('is-shown'), 3200);
            }

            function setStep(name, fraction, active) {
                const step = steps.querySelector(`[data-step="${name}"]`);
                step.classList.toggle('is-active', active);
                step.querySelector('.lt-step-bar > div').style.width = (Math.max(0, Math.min(1, fraction)) * 100) + '%';
            }

            function resultLabel(show, elapsed) {
                if (show.result_ms === 0) return 'Stays up until you hide it';
                const left = show.result_ms - (elapsed - show.countdown_ms - show.drawing_ms);
                return `Back to the playlist in ${seconds(left)}s`;
            }

            function winnerLine(show) {
                const parts = [];
                if (show.rank) parts.push('Winner #' + show.rank);
                if (show.prize) parts.push(show.prize);
                return parts.join(' · ');
            }

            function renderLive(now) {
                const show = status.show;
                const elapsed = show ? now - origin : 0;
                const phase = show ? phaseOf(show, elapsed) : null;

                live.hidden = !phase;
                document.querySelectorAll('.lt-winner').forEach(row => {
                    const onAir = !!phase && show.type !== 'board' && Number(row.dataset.entry) === show.entry_id;
                    row.classList.toggle('is-live', onAir);
                    row.querySelector('.lt-onair').hidden = !onAir;
                });

                const revealPending = !!phase && show.type === 'draw' && REVEAL_PHASES.includes(phase);
                if (drawBtn) {
                    const blocked = revealPending && onScreen.checked;
                    drawBtn.disabled = blocked || navigating;
                    drawNote.hidden = !blocked;
                }
                document.querySelectorAll('.lt-screen-btn').forEach(btn => btn.disabled = revealPending);

                if (!phase) return;

                const isDraw = show.type === 'draw';
                steps.hidden = !isDraw;
                revealBtn.closest('form').hidden = !revealPending;
                liveBig.classList.toggle('is-name', phase === 'result' && show.type !== 'board');

                if (show.type === 'board') {
                    liveType.textContent = 'Winners board';
                    liveBig.textContent = 'All winners';
                    liveLabel.textContent = 'Stays up until you hide it';
                    return;
                }

                liveType.textContent = isDraw ? 'Draw' : 'Winner';
                const countdownEnd = show.countdown_ms, revealAt = show.countdown_ms + show.drawing_ms;

                if (phase === 'intro') {
                    liveBig.textContent = 'Get ready';
                    liveLabel.textContent = 'Starting on the vidiwall';
                } else if (phase === 'countdown') {
                    liveBig.textContent = seconds(countdownEnd - elapsed);
                    liveLabel.textContent = 'Countdown';
                } else if (phase === 'drawing') {
                    liveBig.textContent = 'Drawing…';
                    liveLabel.textContent = `Reveal in ${seconds(revealAt - elapsed)}s`;
                } else {
                    liveBig.textContent = show.name || 'Winner';
                    liveLabel.textContent = [winnerLine(show), resultLabel(show, elapsed)].filter(Boolean).join(' · ');
                    if (isDraw && !celebrated && show.entry_id === JUST_DRAWN) {
                        celebrated = true;
                        ltConfetti();
                    }
                }

                if (isDraw) {
                    setStep('countdown', countdownEnd ? elapsed / countdownEnd : (elapsed >= 0 ? 1 : 0), phase === 'countdown');
                    setStep('drawing', show.drawing_ms ? (elapsed - countdownEnd) / show.drawing_ms : 0, phase === 'drawing');
                    setStep('result', phase === 'result' ? (show.result_ms ? (elapsed - revealAt) / show.result_ms : 1) : 0, phase === 'result');
                }
            }

            function frame(now) {
                renderLive(now);
                requestAnimationFrame(frame);
            }

            function applyStatus(next) {
                if (next.revision < status.revision) return;
                const fresh = next.revision !== status.revision;
                const nextOrigin = performance.now() - (next.show ? next.show.elapsed_ms : 0);
                if (fresh || Math.abs(nextOrigin - origin) > 400) {
                    origin = nextOrigin;
                }
                status = next;
                if (next.winners_version && next.winners_version !== WINNERS_VERSION) {
                    reloadWhenIdle();
                }
            }

            function reloadWhenIdle() {
                const focused = document.activeElement;
                const typing = focused && ['INPUT', 'TEXTAREA', 'SELECT'].includes(focused.tagName) && focused.type !== 'submit';
                if (navigating) return;
                if (typing) {
                    setTimeout(reloadWhenIdle, 3000);
                    return;
                }
                navigating = true;
                window.location.reload();
            }

            function schedulePoll(ms) {
                clearTimeout(pollTimer);
                pollTimer = setTimeout(poll, ms);
            }

            async function poll() {
                try {
                    const res = await fetch(STATUS_URL, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                    if (res.ok) applyStatus(await res.json());
                } catch (e) {}
                schedulePoll(status.show ? 1000 : 4000);
            }

            document.querySelectorAll('form[data-lt-ajax]').forEach(form => {
                form.addEventListener('submit', async e => {
                    e.preventDefault();
                    const button = form.querySelector('button[type=submit]');
                    if (button) button.disabled = true;
                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
                            body: new FormData(form),
                        });
                        const data = await res.json();
                        if (data.revision !== undefined) applyStatus(data);
                        showToast(data.message, !res.ok);
                    } catch (err) {
                        showToast('Could not reach the server. Try again.', true);
                    } finally {
                        if (button) button.disabled = false;
                        schedulePoll(1000);
                    }
                });
            });

            if (onScreen) {
                const syncFollowers = () => document.querySelectorAll('[data-lt-follow-screen]').forEach(input => input.value = onScreen.checked ? 1 : 0);
                onScreen.addEventListener('change', syncFollowers);
                syncFollowers();
            }

            if (drawForm) {
                drawForm.addEventListener('submit', e => {
                    if (navigating) {
                        e.preventDefault();
                        return;
                    }
                    navigating = true;
                    if (!onScreen.checked) {
                        e.preventDefault();
                        animateDraw();
                        return;
                    }
                    drawBtn.disabled = true;
                    document.getElementById('drawBtnLabel').textContent = 'Starting on the vidiwall…';
                });
            }

            document.querySelectorAll('form:not([data-lt-ajax]):not(#drawForm)').forEach(form => {
                form.addEventListener('submit', e => {
                    if (!e.defaultPrevented) navigating = true;
                });
            });

            /* ── Show settings: steppers and the timeline preview ── */
            const settings = $('ltSettings');
            const countdownInput = $('ltCountdown'), drawingInput = $('ltDrawing'), resultInput = $('ltResult'), hold = $('ltHold');

            function clampInput(input) {
                const min = Number(input.min), max = Number(input.max);
                let value = parseInt(input.value, 10);
                if (Number.isNaN(value)) value = min;
                input.value = Math.max(min, Math.min(max, value));
            }

            function renderTimeline() {
                const countdown = Number(countdownInput.value) || 0;
                const drawing = Number(drawingInput.value) || 0;
                const result = hold.checked ? null : (Number(resultInput.value) || 0);
                const holdWidth = Math.max(countdown, drawing, 6) * 1.4;
                const segs = [[$('ltSegCountdown'), countdown, countdown + 's'], [$('ltSegDrawing'), drawing, drawing + 's'], [$('ltSegResult'), result ?? holdWidth, result === null ? 'Until hidden' : result + 's']];
                segs.forEach(([seg, value, label]) => {
                    seg.hidden = value <= 0;
                    seg.style.flexGrow = Math.max(value, .001);
                    seg.querySelector('b').textContent = label;
                });
                $('ltResultStepper').classList.toggle('is-off', hold.checked);
                resultInput.disabled = hold.checked;
            }

            settings.querySelectorAll('.lt-stepper').forEach(stepper => {
                const input = stepper.querySelector('input');
                stepper.querySelectorAll('button[data-step]').forEach(button => {
                    button.addEventListener('click', () => {
                        input.value = (parseInt(input.value, 10) || 0) + Number(button.dataset.step);
                        clampInput(input);
                        renderTimeline();
                    });
                });
                input.addEventListener('input', renderTimeline);
                input.addEventListener('change', () => { clampInput(input); renderTimeline(); });
            });
            hold.addEventListener('change', renderTimeline);
            renderTimeline();

            if (JUST_DRAWN && !(status.show && status.show.type === 'draw' && status.show.entry_id === JUST_DRAWN)) {
                ltConfetti();
            }

            requestAnimationFrame(frame);
            poll();
        })();
    </script>
@endpush
