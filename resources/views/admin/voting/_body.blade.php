{{-- Shared by the admin and moderator voting pages; $routePrefix picks the route set. --}}
@php
    $vtRoute = fn (string $name) => route($routePrefix . 'voting.' . $name, $event);
    $holdResult = (bool) old('hold_result', $screen->result_seconds === 0);
    $resultSeconds = old('result_seconds', $screen->result_seconds ?: 30);
    $showEveryone = (bool) old('show_everyone', $screen->top_count === 0);
    $topCount = old('top_count', $screen->top_count ?: 3);
    $settingChecked = fn (string $field) => (bool) old($field, $screen->{$field});
    $athleteCount = count($standings);
@endphp

@push('styles')
    <style>
        [hidden] { display: none !important; }
        .vt { --vt-accent: var(--mod, var(--red)); }

        .vt-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr); gap: 16px; align-items: start; margin-bottom: 16px; }
        .vt-grid > .card { margin-top: 0; }

        .vt-console { overflow: hidden; }
        .vt-shows { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; padding: 20px; }
        .vt-shows form { display: contents; }
        .vt-show { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; min-height: 118px; padding: 16px 10px; border: 1px solid var(--border); border-radius: 12px; background: var(--card2); color: var(--text); font: inherit; font-size: 13px; font-weight: 700; text-align: center; cursor: pointer; transition: border-color .15s, background .15s, transform .1s; }
        .vt-show:hover:not(:disabled) { border-color: var(--muted); }
        .vt-show:active:not(:disabled) { transform: scale(.98); }
        .vt-show:disabled { opacity: .4; cursor: default; }
        .vt-show .lucide-icon { width: 28px; height: 28px; color: var(--gold); }
        .vt-show.is-live { border-color: var(--gold); background: rgba(255, 215, 0, .08); }
        .vt-show-pill { position: absolute; top: 8px; right: 8px; padding: 2px 7px; border-radius: 20px; font-size: 9px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; background: var(--gold); color: #0A0A18; }
        .vt-note { margin: -6px 20px 18px; font-size: 12px; color: var(--gold); text-align: center; }

        .vt-live { padding: 22px 24px; background: linear-gradient(160deg, rgba(255, 215, 0, .1), rgba(255, 215, 0, 0) 60%); border-bottom: 1px solid var(--border); }
        .vt-live-head { display: flex; align-items: center; gap: 8px; font-size: 10px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--gold); }
        .vt-live-dot { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; animation: vtPing 1.6s infinite; }
        @keyframes vtPing { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, .6); } 80%, 100% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } }
        .vt-live-type { margin-left: auto; color: var(--muted); }
        .vt-live-main { text-align: center; padding: 18px 0 16px; }
        .vt-live-big { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 46px; line-height: 1.05; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .vt-live-big.is-name { font-size: 30px; color: var(--gold); }
        .vt-live-label { margin-top: 6px; font-size: 13px; color: var(--muted); }
        .vt-steps { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 16px; }
        .vt-step span { display: block; margin-top: 6px; font-size: 11px; font-weight: 600; color: var(--muted); text-align: center; }
        .vt-step.is-active span { color: var(--text); }
        .vt-step-bar { height: 5px; border-radius: 5px; background: rgba(255, 255, 255, .08); overflow: hidden; }
        .vt-step-bar > div { height: 100%; width: 0; background: var(--gold); border-radius: 5px; }
        .vt-live-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .vt-live-actions form { display: contents; }

        .vt-result { padding: 14px 20px; border-bottom: 1px solid var(--border); }
        .vt-result:last-child { border-bottom: none; }
        .vt-result-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
        .vt-result-who { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .vt-rank { flex: 0 0 auto; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 13px; background: rgba(255, 255, 255, .06); color: var(--text); }
        .vt-rank-1 { background: linear-gradient(135deg, #FFE066, #E6B800); color: #1a1400; }
        .vt-rank-2 { background: linear-gradient(135deg, #E5E7EB, #9CA3AF); color: #111827; }
        .vt-rank-3 { background: linear-gradient(135deg, #F0B27A, #B8732E); color: #1f1206; }
        .vt-result-name { font-weight: 700; font-size: 15px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vt-result-who > div:last-child { min-width: 0; }
        .vt-result-score { flex: 0 0 auto; text-align: right; }
        .vt-result-votes { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .vt-result.is-leader .vt-result-votes { color: var(--gold); }
        .vt-result .progress-bar { height: 10px; }
        .vt-empty { padding: 40px 20px; }

        .vt-timing { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
        .vt-field .form-label { margin-bottom: 8px; }
        .vt-stepper { display: inline-flex; align-items: center; height: 40px; border: 1px solid var(--border); border-radius: 30px; background: var(--card2); overflow: hidden; transition: border-color .15s, opacity .15s; }
        .vt-stepper:focus-within { border-color: var(--vt-accent); }
        .vt-stepper button { width: 38px; height: 100%; border: none; background: none; color: var(--muted); font-size: 18px; line-height: 1; cursor: pointer; }
        .vt-stepper button:hover:not(:disabled) { color: var(--text); background: rgba(255, 255, 255, .05); }
        .vt-stepper input { width: 50px; height: 100%; border: none; background: none; color: var(--text); font-size: 15px; font-weight: 700; text-align: center; font-variant-numeric: tabular-nums; -moz-appearance: textfield; appearance: textfield; }
        .vt-stepper input::-webkit-outer-spin-button, .vt-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .vt-stepper input:focus { outline: none; }
        .vt-stepper.is-off { opacity: .35; pointer-events: none; }
        .vt-unit { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }
        .vt-hold { display: flex; width: fit-content; align-items: center; gap: 7px; margin-top: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .vt-headline { max-width: 420px; }
        .vt-checks { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 24px; margin-top: 4px; }
        .vt-check { display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; }
        .vt-check input, .vt-hold input { width: 16px; height: 16px; accent-color: var(--gold); cursor: pointer; }
        .vt-settings-foot { display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--border); }

        .vt-toast { position: fixed; left: 50%; bottom: 26px; z-index: 300; transform: translate(-50%, 20px); padding: 11px 18px; border-radius: 10px; background: var(--card2); border: 1px solid var(--border); box-shadow: 0 16px 40px rgba(0, 0, 0, .45); font-size: 13px; font-weight: 600; opacity: 0; pointer-events: none; transition: opacity .2s, transform .2s; max-width: calc(100vw - 32px); }
        .vt-toast.is-shown { opacity: 1; transform: translate(-50%, 0); }
        .vt-toast.is-error { border-color: rgba(239, 68, 68, .45); color: #f87171; }

        @media (max-width: 900px) {
            .vt-grid, .vt-timing, .vt-checks { grid-template-columns: 1fr; }
        }

        @media (max-width: 520px) {
            .vt-shows { grid-template-columns: 1fr; }
            .vt-show { min-height: 0; flex-direction: row; justify-content: flex-start; }
        }
    </style>
@endpush

<div class="vt">
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:16px">
        <div class="stat-card">
            <div class="stat-label">Total Votes</div>
            <div class="stat-value c-gold" id="vtTotal">{{ $totalVotes }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Athletes</div>
            <div class="stat-value">{{ $athleteCount }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Status</div>
            <div class="stat-value" style="font-size:16px;padding-top:8px">
                @if ($event->voting_closed)
                    <i data-lucide="lock" class="lucide-icon"></i> Closed
                @else
                    <i data-lucide="lock-open" class="lucide-icon"></i> Open
                @endif
            </div>
        </div>
    </div>

    <div class="vt-grid">
        <div class="card vt-console">
            <div class="vt-live" id="vtLive" hidden>
                <div class="vt-live-head">
                    <span class="vt-live-dot"></span>
                    <span>On the vidiwall</span>
                    <span class="vt-live-type" id="vtLiveType"></span>
                </div>
                <div class="vt-live-main">
                    <div class="vt-live-big" id="vtLiveBig"></div>
                    <div class="vt-live-label" id="vtLiveLabel"></div>
                </div>
                <div class="vt-steps" id="vtSteps">
                    <div class="vt-step" data-step="countdown"><div class="vt-step-bar"><div></div></div><span>Countdown</span></div>
                    <div class="vt-step" data-step="result"><div class="vt-step-bar"><div></div></div><span>Winner</span></div>
                </div>
                <div class="vt-live-actions">
                    <form method="POST" action="{{ $vtRoute('screen.reveal-now') }}" data-vt-ajax>
                        @csrf
                        <button type="submit" class="btn btn-gold" id="vtRevealNowBtn"><i data-lucide="sparkles" class="lucide-icon"></i> Reveal now</button>
                    </form>
                    <form method="POST" action="{{ $vtRoute('screen.hide') }}" data-vt-ajax>
                        @csrf
                        <button type="submit" class="btn btn-secondary"><i data-lucide="eye-off" class="lucide-icon"></i> Hide from screen</button>
                    </form>
                </div>
            </div>

            <div class="card-header">
                <h3><i data-lucide="monitor-play" class="lucide-icon"></i> Vidiwall</h3>
                <a href="{{ route('vidiwall.show', $event->slug) }}" target="_blank" class="btn btn-ghost btn-sm">Open vidiwall <i data-lucide="arrow-up-right" class="lucide-icon"></i></a>
            </div>

            <div class="vt-shows">
                <form method="POST" action="{{ $vtRoute('screen.live') }}" data-vt-ajax>
                    @csrf
                    <button type="submit" class="vt-show" data-vt-show="live" @disabled($athleteCount === 0)>
                        <span class="vt-show-pill" hidden>On screen</span>
                        <i data-lucide="bar-chart-3" class="lucide-icon"></i> Live results
                    </button>
                </form>
                <form method="POST" action="{{ $vtRoute('screen.reveal') }}" data-vt-ajax id="vtRevealForm">
                    @csrf
                    <button type="submit" class="vt-show" data-vt-show="reveal" data-needs-votes @disabled($totalVotes === 0)>
                        <span class="vt-show-pill" hidden>On screen</span>
                        <i data-lucide="trophy" class="lucide-icon"></i> Reveal the winner
                    </button>
                </form>
                <form method="POST" action="{{ $vtRoute('screen.podium') }}" data-vt-ajax>
                    @csrf
                    <button type="submit" class="vt-show" data-vt-show="podium" data-needs-votes @disabled($totalVotes === 0)>
                        <span class="vt-show-pill" hidden>On screen</span>
                        <i data-lucide="award" class="lucide-icon"></i> Podium
                    </button>
                </form>
            </div>
            <div class="vt-note" id="vtNote" hidden></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i data-lucide="trophy" class="lucide-icon"></i> Live Results</h3>
            </div>

            <div id="vtResults">
                @forelse ($standings as $athlete)
                    <div class="vt-result {{ $athlete['rank'] === 1 && $athlete['votes'] > 0 ? 'is-leader' : '' }}" data-key="{{ $athlete['key'] }}">
                        <div class="vt-result-head">
                            <div class="vt-result-who">
                                <div class="vt-rank vt-rank-{{ $athlete['votes'] > 0 ? min($athlete['rank'], 4) : 4 }}" data-rank>{{ $athlete['rank'] }}</div>
                                <div>
                                    <div class="vt-result-name">{{ $athlete['name'] }}</div>
                                    @if ($athlete['position'])
                                        <div class="text-muted text-xs">{{ $athlete['position'] }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="vt-result-score">
                                <div class="vt-result-votes" data-votes>{{ $athlete['votes'] }}</div>
                                <div class="text-muted text-xs"><span data-percent>{{ $athlete['percent'] }}</span>%</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill {{ $athlete['rank'] === 1 ? 'gold' : ($athlete['rank'] === 2 ? 'green' : '') }}" data-bar style="width:{{ $athlete['percent'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state vt-empty">
                        <div class="empty-icon"><i data-lucide="vote" class="lucide-icon" style="width:44px;height:44px"></i></div>
                        <h3>No athletes yet</h3>
                        <p>Add athletes to the vote in the event settings.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card mb-3" id="vidiwall-show">
        <div class="card-header">
            <h3><i data-lucide="monitor-play" class="lucide-icon"></i> Vidiwall Show</h3>
        </div>
        <form method="POST" action="{{ $vtRoute('screen.update') }}" class="card-body" id="vtSettings">
            @csrf
            @method('PUT')

            <div class="vt-timing">
                <div class="vt-field">
                    <label class="form-label" for="vtTop">Live results show</label>
                    <div class="vt-stepper {{ $showEveryone ? 'is-off' : '' }}" id="vtTopStepper">
                        <button type="button" data-step="-1" aria-label="Fewer places">−</button>
                        <input type="number" id="vtTop" name="top_count" min="1" max="20" value="{{ $topCount }}" @disabled($showEveryone)>
                        <button type="button" data-step="1" aria-label="More places">+</button>
                    </div>
                    <label class="vt-hold">
                        <input type="hidden" name="show_everyone" value="0">
                        <input type="checkbox" name="show_everyone" value="1" id="vtEveryone" @checked($showEveryone)> Every athlete
                    </label>
                </div>
                <div class="vt-field">
                    <label class="form-label" for="vtCountdown">Countdown to the winner</label>
                    <div class="vt-stepper">
                        <button type="button" data-step="-1" aria-label="Shorter countdown">−</button>
                        <input type="number" id="vtCountdown" name="countdown_seconds" min="0" max="60" required value="{{ old('countdown_seconds', $screen->countdown_seconds) }}">
                        <button type="button" data-step="1" aria-label="Longer countdown">+</button>
                    </div>
                    <span class="vt-unit">seconds, 0 skips it</span>
                </div>
                <div class="vt-field">
                    <label class="form-label" for="vtResult">Winner on screen</label>
                    <div class="vt-stepper {{ $holdResult ? 'is-off' : '' }}" id="vtResultStepper">
                        <button type="button" data-step="-1" aria-label="Shorter winner time">−</button>
                        <input type="number" id="vtResult" name="result_seconds" min="3" max="600" value="{{ $resultSeconds }}" @disabled($holdResult)>
                        <button type="button" data-step="1" aria-label="Longer winner time">+</button>
                    </div>
                    <label class="vt-hold">
                        <input type="hidden" name="hold_result" value="0">
                        <input type="checkbox" name="hold_result" value="1" id="vtHold" @checked($holdResult)> Until I hide it
                    </label>
                </div>
            </div>

            <div class="form-group vt-headline">
                <label class="form-label" for="vtHeadline">Headline</label>
                <input type="text" id="vtHeadline" name="headline" class="form-control" maxlength="60"
                    placeholder="{{ $event->voting_title ?: 'Fan Vote' }}" value="{{ old('headline', $screen->headline) }}">
            </div>

            <div class="vt-checks">
                @foreach ([
                    'show_votes' => 'Show the vote counts',
                    'show_percent' => 'Show percentages',
                    'show_total' => 'Show the total number of votes',
                    'show_position' => 'Show each athlete\'s position',
                    'show_qr' => 'Show the QR code while voting is open',
                    'confetti' => 'Confetti on the winner',
                    'close_on_reveal' => 'Close voting when the winner is revealed',
                ] as $field => $label)
                    <label class="vt-check">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1" @checked($settingChecked($field))> {{ $label }}
                    </label>
                @endforeach
            </div>

            <div class="vt-settings-foot">
                <button type="submit" class="btn btn-primary">Save show settings</button>
            </div>
        </form>
    </div>

    <div class="vt-toast" id="vtToast" role="status" aria-live="polite"></div>
</div>

@push('scripts')
    <script>
        (function () {
            const STATUS_URL = @json($vtRoute('screen'));
            const CSRF = document.querySelector('meta[name=csrf-token]').content;
            const CLOSES_ON_REVEAL = @json($screen->close_on_reveal);
            const PAGE_CLOSED = @json((bool) $event->voting_closed);
            const PAGE_KEYS = @json(array_column($standings, 'key'));
            const REVEAL_PHASES = ['intro', 'countdown'];
            const TYPE_LABELS = { live: 'Live results', reveal: 'Winner reveal', podium: 'Podium' };

            const $ = id => document.getElementById(id);
            const live = $('vtLive'), liveType = $('vtLiveType'), liveBig = $('vtLiveBig'), liveLabel = $('vtLiveLabel');
            const steps = $('vtSteps'), revealNowBtn = $('vtRevealNowBtn'), note = $('vtNote'), toast = $('vtToast');
            const results = $('vtResults'), totalEl = $('vtTotal');
            const showButtons = document.querySelectorAll('[data-vt-show]');

            let status = @json($screenStatus);
            let origin = performance.now() - (status.show ? status.show.elapsed_ms : 0);
            let navigating = false;
            let pollTimer = null;
            let toastTimer = null;

            function phaseOf(show, elapsed) {
                if (elapsed < 0) return 'intro';
                if (elapsed < show.countdown_ms) return 'countdown';
                if (show.result_ms === 0 || elapsed < show.countdown_ms + show.result_ms) return 'result';
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
                step.querySelector('.vt-step-bar > div').style.width = (Math.max(0, Math.min(1, fraction)) * 100) + '%';
            }

            function renderLive(now) {
                const show = status.show;
                const elapsed = show ? now - origin : 0;
                const phase = show ? phaseOf(show, elapsed) : null;
                const revealPending = !!phase && show.type === 'reveal' && REVEAL_PHASES.includes(phase);
                const blocked = revealPending
                    ? 'The winner is still being revealed. Reveal it now or hide it first.'
                    : status.blocked;

                live.hidden = !phase;
                note.hidden = !blocked;
                note.textContent = blocked || '';

                showButtons.forEach(button => {
                    const onAir = !!phase && show.type === button.dataset.vtShow;
                    button.classList.toggle('is-live', onAir);
                    button.querySelector('.vt-show-pill').hidden = !onAir;
                    const lacksVotes = button.hasAttribute('data-needs-votes') && status.total === 0;
                    const lacksAthletes = status.standings.length === 0;
                    button.disabled = !!blocked || lacksVotes || lacksAthletes || navigating || button.dataset.busy === '1';
                });

                if (!phase) return;

                liveType.textContent = TYPE_LABELS[show.type] || '';
                steps.hidden = show.type !== 'reveal';
                revealNowBtn.closest('form').hidden = !revealPending;
                liveBig.classList.toggle('is-name', phase === 'result' && show.type === 'reveal');

                if (show.type === 'live') {
                    liveBig.textContent = status.total.toLocaleString();
                    liveLabel.textContent = (status.total === 1 ? 'vote' : 'votes') + ' · Stays up until you hide it';
                    return;
                }

                if (show.type === 'podium') {
                    liveBig.textContent = 'Top 3';
                    liveLabel.textContent = 'Stays up until you hide it';
                    return;
                }

                if (phase === 'intro') {
                    liveBig.textContent = 'Get ready';
                    liveLabel.textContent = 'Starting on the vidiwall';
                } else if (phase === 'countdown') {
                    liveBig.textContent = seconds(show.countdown_ms - elapsed);
                    liveLabel.textContent = 'Countdown';
                } else {
                    liveBig.textContent = show.winners.join(' & ') || 'Winner';
                    const tie = show.winners.length > 1 ? "It's a tie · " : '';
                    const left = show.result_ms - (elapsed - show.countdown_ms);
                    liveLabel.textContent = tie + (show.result_ms === 0 ? 'Stays up until you hide it' : `Back to the playlist in ${seconds(left)}s`);
                }

                setStep('countdown', show.countdown_ms ? elapsed / show.countdown_ms : (elapsed >= 0 ? 1 : 0), phase === 'countdown');
                setStep('result', phase === 'result' ? (show.result_ms ? (elapsed - show.countdown_ms) / show.result_ms : 1) : 0, phase === 'result');
            }

            function renderResults() {
                const keys = status.standings.map(athlete => athlete.key);
                if (keys.length !== PAGE_KEYS.length || keys.some(key => !PAGE_KEYS.includes(key))) {
                    reloadWhenIdle();
                    return;
                }

                totalEl.textContent = status.total;
                const rows = new Map(Array.from(results.querySelectorAll('[data-key]')).map(row => [row.dataset.key, row]));
                const inOrder = Array.from(results.children).map(row => row.dataset.key).join('|') === keys.join('|');

                status.standings.forEach(athlete => {
                    const row = rows.get(athlete.key);
                    const scored = athlete.votes > 0;
                    row.classList.toggle('is-leader', scored && athlete.rank === 1);
                    const rank = row.querySelector('[data-rank]');
                    rank.textContent = athlete.rank;
                    rank.className = 'vt-rank vt-rank-' + (scored ? Math.min(athlete.rank, 4) : 4);
                    row.querySelector('[data-votes]').textContent = athlete.votes;
                    row.querySelector('[data-percent]').textContent = athlete.percent;
                    const bar = row.querySelector('[data-bar]');
                    bar.style.width = athlete.percent + '%';
                    bar.className = 'progress-fill' + (athlete.rank === 1 ? ' gold' : (athlete.rank === 2 ? ' green' : ''));
                    if (!inOrder) results.appendChild(row);
                });
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
                renderResults();
                if (next.voting_closed !== PAGE_CLOSED) {
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
                schedulePoll(status.show ? 1000 : 3000);
            }

            document.querySelectorAll('form[data-vt-ajax]').forEach(form => {
                form.addEventListener('submit', async e => {
                    e.preventDefault();
                    if (form.id === 'vtRevealForm' && CLOSES_ON_REVEAL && !status.voting_closed
                        && !confirm('Close voting and reveal the winner on the vidiwall?')) {
                        return;
                    }
                    const button = form.querySelector('button[type=submit]');
                    if (button) {
                        button.disabled = true;
                        button.dataset.busy = '1';
                    }
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
                        if (button) {
                            button.disabled = false;
                            delete button.dataset.busy;
                        }
                        schedulePoll(1000);
                    }
                });
            });

            document.querySelectorAll('form:not([data-vt-ajax])').forEach(form => {
                form.addEventListener('submit', e => {
                    if (!e.defaultPrevented) navigating = true;
                });
            });

            /* ── Show settings: steppers and their "no limit" switches ── */
            const settings = $('vtSettings');

            function clampInput(input) {
                const min = Number(input.min), max = Number(input.max);
                let value = parseInt(input.value, 10);
                if (Number.isNaN(value)) value = min;
                input.value = Math.max(min, Math.min(max, value));
            }

            settings.querySelectorAll('.vt-stepper').forEach(stepper => {
                const input = stepper.querySelector('input');
                stepper.querySelectorAll('button[data-step]').forEach(button => {
                    button.addEventListener('click', () => {
                        input.value = (parseInt(input.value, 10) || 0) + Number(button.dataset.step);
                        clampInput(input);
                    });
                });
                input.addEventListener('change', () => clampInput(input));
            });

            [[$('vtHold'), $('vtResultStepper'), $('vtResult')], [$('vtEveryone'), $('vtTopStepper'), $('vtTop')]].forEach(([toggle, stepper, input]) => {
                const sync = () => {
                    stepper.classList.toggle('is-off', toggle.checked);
                    input.disabled = toggle.checked;
                };
                toggle.addEventListener('change', sync);
                sync();
            });

            requestAnimationFrame(frame);
            poll();
        })();
    </script>
@endpush
