{{-- Shared by the admin and moderator player pages; $routePrefix picks the route set. --}}
@php
    $vpRoute = fn (string $name, array $extra = []) => route($routePrefix . 'player.' . $name, array_merge([$event], $extra));
@endphp

@push('styles')
    <style>
        [hidden] { display: none !important; }
        .vp { --vp-accent: var(--mod, var(--red)); }

        .vp-deck { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); overflow: hidden; margin-bottom: 16px; }
        .vp-screen { position: relative; aspect-ratio: 16 / 9; background: #05050c; display: flex; align-items: center; justify-content: center; overflow: hidden; border-right: 1px solid var(--border); }
        .vp-screen img, .vp-screen video { width: 100%; height: 100%; object-fit: contain; display: block; }
        .vp-screen-idle { display: flex; flex-direction: column; align-items: center; gap: 10px; color: var(--muted); font-size: 13px; text-align: center; padding: 20px; }
        .vp-screen-idle .lucide-icon { width: 40px; height: 40px; stroke-width: 1.5px; opacity: .6; }
        .vp-screen-tag { position: absolute; top: 12px; left: 12px; padding: 4px 11px; border-radius: 30px; font-size: 10px; font-weight: 800; letter-spacing: 1.6px; background: var(--gold); color: #0A0A18; }
        .vp[data-state="paused"] .vp-screen-tag { background: rgba(255, 255, 255, .85); }
        .vp-screen-kind { position: absolute; top: 12px; right: 12px; padding: 4px 10px; border-radius: 30px; font-size: 10px; font-weight: 700; letter-spacing: 1px; background: rgba(99, 102, 241, .9); color: #fff; }
        .vp-screen-grid { --cols: 2; --rows: 1; --gap: 6px; position: absolute; inset: 0; display: flex; flex-wrap: wrap; align-content: center; justify-content: center; gap: var(--gap); padding: 10px; }
        .vp-screen-grid.n-4 { --rows: 2; }
        .vp-screen-grid > img { width: calc((100% - (var(--cols) - 1) * var(--gap)) / var(--cols)); height: calc((100% - (var(--rows) - 1) * var(--gap)) / var(--rows)); object-fit: cover; border-radius: 6px; }

        .vp-panel { display: flex; flex-direction: column; justify-content: center; gap: 16px; padding: 22px 24px; min-width: 0; }
        .vp-now-label { font-size: 10px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); }
        .vp[data-state="playing"] .vp-now-label { color: var(--gold); }
        .vp-now-name { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 22px; line-height: 1.15; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vp-now-meta { margin-top: 4px; font-size: 12px; color: var(--muted); }

        .vp-progress { height: 6px; border-radius: 6px; background: rgba(255, 255, 255, .08); overflow: hidden; }
        .vp-progress > div { height: 100%; width: 0; background: var(--gold); border-radius: 6px; }
        .vp-times { display: flex; justify-content: space-between; margin-top: 6px; font-size: 11px; color: var(--muted); font-variant-numeric: tabular-nums; }

        .vp-transport { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .vp-btn { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 50%; border: 1px solid var(--border); background: var(--card2); color: var(--text); cursor: pointer; transition: background .15s, border-color .15s, opacity .15s, transform .1s; }
        .vp-btn .lucide-icon { width: 20px; height: 20px; }
        .vp-btn:hover:not(:disabled) { border-color: var(--muted); }
        .vp-btn:active:not(:disabled) { transform: scale(.94); }
        .vp-btn:disabled { opacity: .3; cursor: default; }
        .vp-btn-main { width: 64px; height: 64px; background: var(--vp-accent); border-color: var(--vp-accent); color: #fff; }
        .vp-btn-main .lucide-icon { width: 26px; height: 26px; }
        .vp-btn-main:hover:not(:disabled) { border-color: var(--vp-accent); filter: brightness(1.1); }
        .vp[data-state="playing"] .vp-ic-play, .vp:not([data-state="playing"]) .vp-ic-pause { display: none; }
        .vp-loop { margin-left: auto; width: auto; padding: 0 16px; border-radius: 30px; gap: 8px; font-size: 13px; font-weight: 600; color: var(--muted); }
        .vp-loop .lucide-icon { width: 16px; height: 16px; }
        .vp-loop[aria-pressed="true"] { color: var(--text); border-color: var(--vp-accent); background: color-mix(in srgb, var(--vp-accent) 14%, var(--card2)); }
        .vp-msg { font-size: 13px; color: var(--gold); }

        .vp-settings { display: flex; flex-direction: column; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border); }
        .vp-setting { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; }
        .vp-setting-label { display: flex; align-items: center; gap: 8px; min-width: 140px; font-size: 13px; font-weight: 600; color: var(--muted); }
        .vp-setting-label .lucide-icon { width: 16px; height: 16px; }
        .vp-segments { display: inline-flex; height: 40px; padding: 3px; gap: 3px; border: 1px solid var(--border); border-radius: 30px; background: var(--card2); }
        .vp-segments button { min-width: 44px; padding: 0 12px; border: none; border-radius: 30px; background: none; color: var(--muted); font-size: 15px; font-weight: 700; font-variant-numeric: tabular-nums; cursor: pointer; transition: color .15s, background .15s, box-shadow .15s; }
        .vp-segments button:hover:not([aria-pressed="true"]) { color: var(--text); background: rgba(255, 255, 255, .05); }
        .vp-segments button[aria-pressed="true"] { color: var(--text); background: color-mix(in srgb, var(--vp-accent) 22%, var(--card2)); box-shadow: inset 0 0 0 1px var(--vp-accent); cursor: default; }
        .vp-stepper { display: inline-flex; align-items: center; height: 40px; border: 1px solid var(--border); border-radius: 30px; background: var(--card2); overflow: hidden; transition: border-color .15s; }
        .vp-stepper:focus-within { border-color: var(--vp-accent); }
        .vp-stepper button { width: 38px; height: 100%; border: none; background: none; color: var(--muted); font-size: 18px; line-height: 1; cursor: pointer; transition: color .15s, background .15s; }
        .vp-stepper button:hover:not(:disabled) { color: var(--text); background: rgba(255, 255, 255, .05); }
        .vp-stepper button:disabled { opacity: .3; cursor: default; }
        .vp-stepper input { width: 44px; height: 100%; border: none; background: none; color: var(--text); font-size: 15px; font-weight: 700; text-align: center; font-variant-numeric: tabular-nums; -moz-appearance: textfield; appearance: textfield; }
        .vp-stepper input::-webkit-outer-spin-button, .vp-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .vp-stepper input:focus { outline: none; }
        .vp-duration-unit { font-size: 13px; color: var(--muted); }
        .vp-duration-saved { font-size: 12px; font-weight: 600; color: #4ade80; opacity: 0; transition: opacity .2s; }
        .vp-duration-saved.vp-show { opacity: 1; }

        .vp-summary { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; padding: 12px 18px; border-bottom: 1px solid var(--border); font-size: 12px; color: var(--muted); }
        .vp-summary strong { color: var(--text); }
        .vp-order-error { margin: 12px 18px 0; }
        .vp-order-error a { color: inherit; font-weight: 700; }

        .vp-row { position: relative; display: flex; align-items: center; gap: 12px; padding: 10px 18px; border-bottom: 1px solid var(--border); background: var(--card); }
        .vp-row:last-child { border-bottom: none; }
        .vp-row.vp-current { background: rgba(255, 215, 0, .05); box-shadow: inset 3px 0 0 var(--gold); }
        .vp-row.vp-dragging { z-index: 5; background: var(--card2); border-radius: 10px; box-shadow: 0 14px 34px rgba(0, 0, 0, .5), 0 0 0 1px var(--vp-accent); }
        .vp-row.vp-flash { animation: vpFlash 2s ease-out; }
        @keyframes vpFlash { from { background: color-mix(in srgb, var(--vp-accent) 16%, var(--card)); } to { background: var(--card); } }
        .vp-drag { display: flex; align-items: center; justify-content: center; width: 24px; height: 40px; margin: 0 -6px 0 -8px; border-radius: 6px; color: var(--muted); opacity: .55; cursor: grab; touch-action: none; flex-shrink: 0; }
        .vp-drag:hover { opacity: 1; color: var(--text); background: rgba(255, 255, 255, .05); }
        .vp-dragging .vp-drag, body.vp-sorting, body.vp-sorting * { cursor: grabbing !important; }
        body.vp-sorting { user-select: none; }
        .vp-num { width: 28px; text-align: right; font-size: 12px; font-weight: 700; color: var(--muted); font-variant-numeric: tabular-nums; flex-shrink: 0; }
        .vp-current .vp-num { color: var(--gold); }
        .vp-thumb { position: relative; width: 56px; height: 56px; border-radius: 8px; overflow: hidden; background: #000; flex-shrink: 0; }
        .vp-thumb img, .vp-thumb video { width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none; }
        .vp-thumb-kind { position: absolute; left: 4px; bottom: 4px; display: flex; align-items: center; gap: 3px; padding: 1px 5px; border-radius: 8px; background: rgba(99, 102, 241, .9); color: #fff; font-size: 9px; font-weight: 700; }
        .vp-thumb-kind .lucide-icon { width: 10px; height: 10px; }
        .vp-info { flex: 1; min-width: 0; }
        .vp-name { font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vp-meta { display: flex; align-items: center; gap: 8px; margin-top: 3px; font-size: 11px; color: var(--muted); }
        .vp-chip { padding: 1px 7px; border-radius: 10px; font-size: 9px; font-weight: 800; letter-spacing: 1px; }
        .vp-chip-new { background: rgba(99, 102, 241, .25); color: #a5b4fc; }
        .vp-chip-live { background: var(--gold); color: #0A0A18; }
        .vp-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .vp-actions .btn { white-space: nowrap; }
        .vp-current .vp-act-next { visibility: hidden; }

        @media (max-width: 900px) {
            .vp-deck { grid-template-columns: 1fr; }
            .vp-screen { border-right: none; border-bottom: 1px solid var(--border); }
        }

        @media (max-width: 560px) {
            .vp-panel { padding: 18px 16px; }
            .vp-setting-label { min-width: 0; width: 100%; }
            .vp-btn { width: 42px; height: 42px; }
            .vp-btn-main { width: 58px; height: 58px; }
            .vp-loop { margin-left: 0; }
            .vp-row { padding: 10px 12px; gap: 10px; }
            .vp-num { display: none; }
            .vp-thumb { width: 46px; height: 46px; }
            .vp-act-label { display: none; }
        }
    </style>
@endpush

<div class="vp" id="vp" data-state="stopped">

    <div class="card vp-deck">
        <div class="vp-screen" id="vpScreen">
            <div class="vp-screen-idle" id="vpIdle">
                <i data-lucide="qr-code" class="lucide-icon"></i>
                <span>Stopped. The screen shows the event QR code.</span>
            </div>
            <img id="vpNowImg" alt="" hidden>
            <video id="vpNowVideo" muted playsinline preload="metadata" hidden></video>
            <div class="vp-screen-grid" id="vpNowGrid" hidden></div>
            <span class="vp-screen-tag" id="vpTag" hidden>LIVE</span>
            <span class="vp-screen-kind" id="vpKind" hidden>VIDEO</span>
        </div>

        <div class="vp-panel">
            <div>
                <div class="vp-now-label" id="vpNowLabel">Stopped</div>
                <div class="vp-now-name" id="vpNowName">Nothing on screen</div>
                <div class="vp-now-meta" id="vpNowMeta">Press play to start the playlist from the top.</div>
            </div>

            <div>
                <div class="vp-progress"><div id="vpBar"></div></div>
                <div class="vp-times"><span id="vpElapsed">0:00</span><span id="vpTotal">0:00</span></div>
            </div>

            <div class="vp-transport">
                <button type="button" class="vp-btn" data-action="previous" title="Previous" aria-label="Previous">
                    <i data-lucide="skip-back" class="lucide-icon"></i>
                </button>
                <button type="button" class="vp-btn vp-btn-main" id="vpPlayPause" title="Play / Pause" aria-label="Play or pause">
                    <span class="vp-ic-play"><i data-lucide="play" class="lucide-icon"></i></span>
                    <span class="vp-ic-pause"><i data-lucide="pause" class="lucide-icon"></i></span>
                </button>
                <button type="button" class="vp-btn" data-action="stop" title="Stop" aria-label="Stop">
                    <i data-lucide="square" class="lucide-icon"></i>
                </button>
                <button type="button" class="vp-btn" data-action="next" title="Next" aria-label="Next">
                    <i data-lucide="skip-forward" class="lucide-icon"></i>
                </button>
                <button type="button" class="vp-btn vp-loop" id="vpLoop" aria-pressed="false" title="Start over automatically after the last item">
                    <i data-lucide="repeat" class="lucide-icon"></i> Loop
                </button>
            </div>

            <div class="vp-settings">
                <div class="vp-setting">
                    <label class="vp-setting-label" for="vpSeconds"><i data-lucide="timer" class="lucide-icon"></i> Photo duration</label>
                    <div class="vp-stepper">
                        <button type="button" data-seconds-step="-1" aria-label="One second less">&minus;</button>
                        <input type="number" id="vpSeconds" min="1" max="60" step="1" inputmode="numeric" value="{{ $snapshot['player']['photo_seconds'] }}">
                        <button type="button" data-seconds-step="1" aria-label="One second more">+</button>
                    </div>
                    <span class="vp-duration-unit">seconds</span>
                    <span class="vp-duration-saved" id="vpSecondsSaved" aria-live="polite">Saved</span>
                </div>

                <div class="vp-setting">
                    <span class="vp-setting-label" id="vpPerScreenLabel"><i data-lucide="layout-grid" class="lucide-icon"></i> Photos on screen</span>
                    <div class="vp-segments" role="group" aria-labelledby="vpPerScreenLabel">
                        @foreach (\App\Models\VidiwallPlayer::PHOTOS_PER_SCREEN as $count)
                            <button type="button" data-per-screen="{{ $count }}" aria-pressed="{{ $count === $snapshot['player']['photos_per_screen'] ? 'true' : 'false' }}">{{ $count }}</button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="vp-msg" id="vpMsg" hidden></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i data-lucide="list-video" class="lucide-icon"></i> Playlist</h3>
        </div>

        <div class="vp-summary">
            <span><strong id="vpCountTotal">0</strong> items</span>
            <span><strong id="vpCountNew">0</strong> not played yet</span>
            <span id="vpPerScreenSummary" @if ($snapshot['player']['photos_per_screen'] === 1) hidden @endif><strong id="vpPerScreenCount">{{ $snapshot['player']['photos_per_screen'] }}</strong> per screen</span>
            <span><span id="vpSecondsSummary">{{ $snapshot['player']['photo_seconds'] }}</span>s per {{ $snapshot['player']['photos_per_screen'] === 1 ? 'photo' : 'page' }}, videos play through</span>
            <span id="vpRunSummary" hidden>&asymp; <strong id="vpRunTime"></strong> per run</span>
        </div>

        <div class="alert alert-error vp-order-error" id="vpOrderError" hidden>
            <span id="vpOrderErrorText"></span> <a href="{{ $vpRoute('index') }}">Reload</a>
        </div>

        <div class="empty-state" id="vpEmpty" hidden>
            <div class="empty-icon"><i data-lucide="images" class="lucide-icon"></i></div>
            <h3>No approved items yet</h3>
            <p>Approved photos and videos line up here in the order they were approved.</p>
        </div>

        <div class="vp-list" id="vpList"></div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const URLS = {
                status: @json($vpRoute('status')),
                play: @json($vpRoute('play')),
                pause: @json($vpRoute('pause')),
                stop: @json($vpRoute('stop')),
                next: @json($vpRoute('next')),
                previous: @json($vpRoute('previous')),
                loop: @json($vpRoute('loop')),
                photoSeconds: @json($vpRoute('photo-seconds')),
                photosPerScreen: @json($vpRoute('photos-per-screen')),
                reorder: @json($vpRoute('reorder')),
                playFrom: @json($vpRoute('play-from', ['fotoUpload' => '__ID__'])),
                playNext: @json($vpRoute('play-next', ['fotoUpload' => '__ID__'])),
            };
            const CSRF = document.querySelector('meta[name=csrf-token]').content;
            const POLL_MS = 1000;

            const $ = id => document.getElementById(id);
            const root = $('vp');
            const list = $('vpList');
            const rows = new Map();

            let version = '';
            let state = 'stopped';
            let currentId = null;
            let currentIds = new Set();
            let shownKey = null;
            let clock = { id: null, elapsed: 0, slot: 0, at: 0 };
            let seq = 0;
            let pending = 0;
            let commands = Promise.resolve();
            let last = null;
            let drag = null;
            let msgTimer = null;
            const itemsById = new Map();

            function esc(value) {
                return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            }

            function clockText(ms) {
                const total = Math.max(0, Math.round(ms / 1000));
                return Math.floor(total / 60) + ':' + String(total % 60).padStart(2, '0');
            }

            function playedText(ms) {
                return 'Played ' + new Date(ms).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }

            function runText(ms) {
                if (ms < 60000) {
                    return Math.max(1, Math.round(ms / 1000)) + ' s';
                }
                const minutes = Math.round(ms / 60000);
                return minutes < 60 ? minutes + ' min' : Math.floor(minutes / 60) + ' h ' + String(minutes % 60).padStart(2, '0') + ' min';
            }

            function icons() {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            }

            function flashMessage(text) {
                const box = $('vpMsg');
                box.textContent = text || '';
                box.hidden = !text;
                clearTimeout(msgTimer);
                if (text) {
                    msgTimer = setTimeout(() => { box.hidden = true; }, 5000);
                }
            }

            /* ── Now playing ─────────────────────────────────────────── */

            /** Where the progress bar is right now, counted locally between server updates. */
            function elapsedNow() {
                const running = state === 'playing' ? performance.now() - clock.at : 0;
                return Math.min(clock.slot, clock.elapsed + running);
            }

            /** Everything on screen with the now playing item, which is the first of its page. */
            function pageOf(now) {
                return now ? (now.page && now.page.length ? now.page : [now]) : [];
            }

            function renderNow(data) {
                const now = data.now_playing;
                const player = data.player;
                const localElapsed = elapsedNow();
                const continuing = now && now.id === clock.id && player.state === state;
                const page = pageOf(now);
                state = player.state;
                currentId = now ? now.id : null;
                currentIds = new Set(page.map(item => item.id));
                root.dataset.state = state;

                $('vpLoop').setAttribute('aria-pressed', player.loop ? 'true' : 'false');
                $('vpPlayPause').disabled = !now && !data.counts.total;
                root.querySelectorAll('[data-action="previous"], [data-action="next"], [data-action="stop"]').forEach(btn => {
                    btn.disabled = state === 'stopped';
                });

                $('vpIdle').hidden = !!now;
                $('vpTag').hidden = !now;
                $('vpTag').textContent = state === 'paused' ? 'PAUSED' : 'LIVE';
                $('vpKind').hidden = !now || !now.is_video;
                $('vpNowLabel').textContent = state === 'playing' ? 'Now playing' : state === 'paused' ? 'Paused' : 'Stopped';

                if (!now) {
                    $('vpNowImg').hidden = true;
                    $('vpNowVideo').hidden = true;
                    $('vpNowVideo').removeAttribute('src');
                    $('vpNowGrid').hidden = true;
                    $('vpNowGrid').innerHTML = '';
                    $('vpNowName').textContent = 'Nothing on screen';
                    $('vpNowMeta').textContent = data.counts.total
                        ? 'Press play to start the playlist from the top.'
                        : 'Approve photos or videos and they will line up here.';
                    shownKey = null;
                    clock = { id: null, elapsed: 0, slot: 0, at: performance.now() };
                    paintClock();
                    return;
                }

                $('vpNowName').textContent = [...new Set(page.map(item => item.uploader))].join(', ');
                const order = data.items && !drag ? data.items.map(item => item.id) : currentOrder();
                const positions = page.map(item => order.indexOf(item.id) + 1).filter(Boolean);
                const from = Math.min(...positions);
                const to = Math.max(...positions);
                $('vpNowMeta').textContent = (now.is_video ? 'Video' : page.length > 1 ? 'Photos' : 'Photo')
                    + (positions.length ? ' · ' + (to > from ? from + '–' + to : from) + ' of ' + data.counts.total : '');

                const layout = player.photos_per_screen || 1;
                const tiled = !now.is_video && layout > 1;
                const key = (tiled ? 'grid' + layout + ':' : '') + page.map(item => item.id).join(',');
                if (shownKey !== key) {
                    shownKey = key;
                    const grid = $('vpNowGrid');
                    grid.hidden = !tiled;
                    if (now.is_video) {
                        $('vpNowImg').hidden = true;
                        grid.innerHTML = '';
                        $('vpNowVideo').src = now.video_url + '#t=0.5';
                        $('vpNowVideo').hidden = false;
                    } else {
                        $('vpNowVideo').hidden = true;
                        $('vpNowVideo').removeAttribute('src');
                        if (tiled) {
                            $('vpNowImg').hidden = true;
                            grid.className = 'vp-screen-grid n-' + layout;
                            grid.innerHTML = page.map(item => `<img src="${esc(item.thumb_url)}" alt="">`).join('');
                        } else {
                            grid.innerHTML = '';
                            $('vpNowImg').src = now.thumb_url;
                            $('vpNowImg').hidden = false;
                        }
                    }
                }

                if (!continuing || now.slot_ms !== clock.slot || Math.abs(player.elapsed_ms - localElapsed) > 600) {
                    clock = { id: now.id, elapsed: player.elapsed_ms, slot: now.slot_ms, at: performance.now() };
                }
                paintClock();
            }

            function paintClock() {
                const elapsed = elapsedNow();
                $('vpBar').style.width = clock.slot ? (elapsed / clock.slot * 100) + '%' : '0';
                $('vpElapsed').textContent = clockText(elapsed);
                $('vpTotal').textContent = clockText(clock.slot);
            }

            (function tick() {
                paintClock();
                requestAnimationFrame(tick);
            })();

            /* ── Playlist ────────────────────────────────────────────── */

            function createRow(item) {
                const row = document.createElement('div');
                row.className = 'vp-row';
                row.dataset.id = item.id;
                const media = item.is_video
                    ? `<video src="${esc(item.video_url)}#t=0.5" muted playsinline preload="metadata"></video>
                       <span class="vp-thumb-kind"><i data-lucide="video" class="lucide-icon"></i>${item.duration ? esc(item.duration) + 's' : ''}</span>`
                    : `<img src="${esc(item.thumb_url)}" alt="" loading="lazy">`;
                row.innerHTML = `
                    <div class="vp-drag" title="Drag to reorder" aria-hidden="true"><i data-lucide="grip-vertical" class="lucide-icon"></i></div>
                    <div class="vp-num"></div>
                    <div class="vp-thumb">${media}</div>
                    <div class="vp-info">
                        <div class="vp-name">${esc(item.uploader)}</div>
                        <div class="vp-meta"></div>
                    </div>
                    <div class="vp-actions">
                        <button type="button" class="btn btn-ghost btn-sm vp-act-next" data-row-action="playNext" title="Play after the current item">
                            <i data-lucide="arrow-up-to-line" class="lucide-icon"></i><span class="vp-act-label">Next</span>
                        </button>
                        <button type="button" class="btn btn-gold btn-sm" data-row-action="playFrom" title="Play from here">
                            <i data-lucide="play" class="lucide-icon"></i><span class="vp-act-label">Play</span>
                        </button>
                    </div>`;
                return row;
            }

            function renderList(items) {
                const seen = new Set();
                items.forEach(item => {
                    seen.add(item.id);
                    let row = rows.get(item.id);
                    if (!row) {
                        row = createRow(item);
                        rows.set(item.id, row);
                    }
                    row.dataset.played = item.played_ms || '';
                });
                rows.forEach((row, id) => {
                    if (!seen.has(id)) {
                        row.remove();
                        rows.delete(id);
                    }
                });

                const order = items.map(item => String(item.id));
                const domOrder = [...list.children].map(row => row.dataset.id);
                if (order.join() !== domOrder.join()) {
                    items.forEach(item => list.appendChild(rows.get(item.id)));
                }

                $('vpEmpty').hidden = items.length > 0;
                paintRows();
                icons();
            }

            function paintRows() {
                [...list.children].forEach((row, index) => {
                    const id = Number(row.dataset.id);
                    const isCurrent = currentIds.has(id);
                    row.classList.toggle('vp-current', isCurrent);
                    row.querySelector('.vp-num').textContent = index + 1;
                    const meta = row.querySelector('.vp-meta');
                    const played = Number(row.dataset.played);
                    const chip = isCurrent
                        ? `<span class="vp-chip vp-chip-live">${state === 'paused' ? 'PAUSED' : 'ON SCREEN'}</span>`
                        : played ? '' : '<span class="vp-chip vp-chip-new">NEW</span>';
                    const html = chip + (played && !isCurrent ? `<span>${esc(playedText(played))}</span>` : '');
                    if (meta.innerHTML !== html) {
                        meta.innerHTML = html;
                    }
                });
            }

            function renderCounts(counts, runMs) {
                $('vpCountTotal').textContent = counts.total;
                $('vpCountNew').textContent = counts.unplayed;
                $('vpRunSummary').hidden = !runMs;
                $('vpRunTime').textContent = runText(runMs || 0);
            }

            function render(data) {
                last = data;
                if (data.items) {
                    data.items.forEach(item => itemsById.set(item.id, item));
                }
                if (data.items && !drag) {
                    version = data.version;
                }
                renderNow(data);
                renderCounts(data.counts, data.run_ms);
                renderSeconds(data.player.photo_seconds);
                renderPerScreen(data.player.photos_per_screen);
                document.dispatchEvent(new CustomEvent('fotoqueue:status', { detail: { counts: data.counts, player_state: data.player.state } }));
                if (data.items && !drag) {
                    renderList(data.items);
                } else {
                    paintRows();
                }
            }

            /* ── Photo duration ──────────────────────────────────────── */

            const secondsInput = $('vpSeconds');
            let savedSeconds = Number(secondsInput.value);
            let secondsTimer = null;
            let secondsSaving = 0;
            let requestedSeconds = null;
            let savedTimer = null;

            function clampSeconds(value) {
                return Math.min(60, Math.max(1, Math.round(value)));
            }

            function paintSeconds(seconds) {
                $('vpSecondsSummary').textContent = seconds;
                root.querySelector('[data-seconds-step="-1"]').disabled = seconds <= 1;
                root.querySelector('[data-seconds-step="1"]').disabled = seconds >= 60;
            }

            /** Takes the server's value unless the operator is in the middle of changing it. */
            function renderSeconds(seconds) {
                savedSeconds = seconds;
                if (document.activeElement !== secondsInput && !secondsTimer && !secondsSaving) {
                    secondsInput.value = seconds;
                    paintSeconds(seconds);
                }
            }

            async function saveSeconds() {
                clearTimeout(secondsTimer);
                secondsTimer = null;
                const raw = Number(secondsInput.value);
                const seconds = secondsInput.value === '' || !Number.isFinite(raw) ? savedSeconds : clampSeconds(raw);
                secondsInput.value = seconds;
                paintSeconds(seconds);
                if (seconds === (secondsSaving ? requestedSeconds : savedSeconds)) {
                    return;
                }
                requestedSeconds = seconds;
                secondsSaving++;
                const data = await send(URLS.photoSeconds, 'POST', { seconds });
                secondsSaving--;
                if (data) {
                    savedSeconds = data.player.photo_seconds;
                }
                if (document.activeElement !== secondsInput && !secondsTimer && !secondsSaving) {
                    secondsInput.value = savedSeconds;
                    paintSeconds(savedSeconds);
                }
                if (!data) {
                    return;
                }
                const saved = $('vpSecondsSaved');
                saved.classList.add('vp-show');
                clearTimeout(savedTimer);
                savedTimer = setTimeout(() => saved.classList.remove('vp-show'), 1500);
            }

            root.querySelectorAll('[data-seconds-step]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const seconds = clampSeconds((Number(secondsInput.value) || savedSeconds) + Number(btn.dataset.secondsStep));
                    secondsInput.value = seconds;
                    paintSeconds(seconds);
                    clearTimeout(secondsTimer);
                    secondsTimer = setTimeout(saveSeconds, 500);
                });
            });
            secondsInput.addEventListener('change', saveSeconds);
            secondsInput.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    secondsInput.blur();
                }
            });

            /* ── Photos on screen ────────────────────────────────────── */

            const perScreenButtons = [...root.querySelectorAll('[data-per-screen]')];
            let savedPerScreen = {{ $snapshot['player']['photos_per_screen'] }};
            let perScreenSaving = 0;

            function paintPerScreen(count) {
                perScreenButtons.forEach(btn => btn.setAttribute('aria-pressed', Number(btn.dataset.perScreen) === count ? 'true' : 'false'));
                $('vpPerScreenSummary').hidden = count === 1;
                $('vpPerScreenCount').textContent = count;
                $('vpSecondsSummary').nextSibling.nodeValue = 's per ' + (count === 1 ? 'photo' : 'page') + ', videos play through';
            }

            /** Takes the server's value unless a change is on its way. */
            function renderPerScreen(count) {
                savedPerScreen = count;
                if (!perScreenSaving) {
                    paintPerScreen(count);
                }
            }

            perScreenButtons.forEach(btn => {
                btn.addEventListener('click', async () => {
                    const count = Number(btn.dataset.perScreen);
                    if (btn.getAttribute('aria-pressed') === 'true') {
                        return;
                    }
                    paintPerScreen(count);
                    if (last) {
                        last = { ...last, player: { ...last.player, photos_per_screen: count } };
                    }
                    perScreenSaving++;
                    const data = await send(URLS.photosPerScreen, 'POST', { count });
                    perScreenSaving--;
                    if (!perScreenSaving) {
                        paintPerScreen(data ? data.player.photos_per_screen : savedPerScreen);
                    }
                });
            });

            /* ── Server calls ────────────────────────────────────────── */

            /**
             * Shows the outcome of a transport press straight away; the server's
             * answer then confirms or corrects it.
             */
            function preview(nextState, item, elapsed) {
                if (!last) {
                    return;
                }
                const data = {
                    ...last,
                    items: undefined,
                    player: { ...last.player, state: item ? nextState : 'stopped', elapsed_ms: elapsed },
                    now_playing: item || null,
                };
                last = data;
                renderNow(data);
                paintRows();
                document.dispatchEvent(new CustomEvent('fotoqueue:status', { detail: { counts: data.counts, player_state: data.player.state } }));
            }

            /** The page that starts at `index` of the playlist, built the way the server builds it, as a now playing item. */
            function pageAt(order, index) {
                const first = itemsById.get(order[index]);
                if (!first) {
                    return null;
                }
                const size = last?.player.photos_per_screen || 1;
                const page = [first];
                for (let i = index + 1; !first.is_video && i < order.length && page.length < size; i++) {
                    const item = itemsById.get(order[i]);
                    if (!item || item.is_video) {
                        break;
                    }
                    page.push(item);
                }
                return { ...first, page_ids: page.map(item => item.id), page };
            }

            /** Where the page ending right before `index` starts: a video on its own, or else up to a screenful of photos. */
            function startBefore(order, index) {
                const size = last?.player.photos_per_screen || 1;
                let start = -1;
                for (let i = index - 1; i >= 0 && index - i <= size; i--) {
                    if (itemsById.get(order[i])?.is_video) {
                        return start === -1 ? i : start;
                    }
                    start = i;
                }
                return start;
            }

            /** The page next or previous would bring up, so the press shows straight away. */
            function pageStep(offset) {
                const order = currentOrder();
                const loop = last?.player.loop;
                if (offset > 0) {
                    const ids = [...currentIds];
                    let end = -1;
                    for (let i = ids.length - 1; i >= 0 && end === -1; i--) {
                        end = order.indexOf(ids[i]);
                    }
                    const start = end + 1 < order.length ? end + 1 : loop ? 0 : -1;
                    return start === -1 ? null : pageAt(order, start);
                }
                const index = order.indexOf(currentId);
                let start = index > 0 ? startBefore(order, index) : -1;
                if (start === -1 && loop) {
                    start = startBefore(order, order.length);
                }
                return start === -1 ? null : pageAt(order, start);
            }

            /**
             * Sends one press. Presses go out one after another in the order they
             * were made, so a quick pause and play cannot reach the server the
             * other way round.
             */
            function send(url, method = 'POST', body = {}) {
                const mine = ++seq;
                pending++;
                const run = async () => {
                    let outcome = { data: null, synced: false };
                    try {
                        outcome = await request(url, method, body, mine);
                    } finally {
                        pending--;
                    }
                    if (!outcome.synced && mine === seq) {
                        poll();
                    }
                    return outcome.data;
                };
                const result = commands.then(run, run);
                commands = result;
                return result;
            }

            /** Answers the server's data (null when the press failed) and whether it came with a snapshot to render. */
            async function request(url, method, body, mine) {
                try {
                    const response = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ ...body, v: version }),
                        signal: window.AbortSignal?.timeout ? AbortSignal.timeout(10000) : undefined,
                    });
                    if (response.status === 419) {
                        flashMessage('Your session expired. Reload the page.');
                        return { data: null, synced: false };
                    }
                    const data = await response.json().catch(() => ({}));
                    if (data.player && mine === seq) {
                        render(data);
                    }
                    if (mine === seq || !response.ok) {
                        flashMessage(data.message || (response.ok ? '' : 'That did not work. Try again.'));
                    }
                    return { data: response.ok ? data : null, synced: !!data.player };
                } catch (e) {
                    flashMessage('No connection to the server.');
                    return { data: null, synced: false };
                }
            }

            /** Skipped while presses are on their way, and dropped when a press happened while it was in flight, so a stale answer never undoes a press. */
            async function poll() {
                if (pending) {
                    return;
                }
                const mine = seq;
                try {
                    const response = await fetch(URLS.status + '?v=' + encodeURIComponent(drag ? '' : version), {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store',
                    });
                    if (response.ok) {
                        const data = await response.json();
                        if (mine === seq && !pending) {
                            render(data);
                        }
                    }
                } catch (e) {}
            }

            $('vpPlayPause').addEventListener('click', () => {
                if (state === 'playing') {
                    preview('paused', last?.now_playing, elapsedNow());
                    send(URLS.pause);
                    return;
                }
                const resume = state === 'paused' && last?.now_playing;
                preview('playing', resume ? last.now_playing : pageAt(currentOrder(), 0), resume ? elapsedNow() : 0);
                send(URLS.play);
            });
            root.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.dataset.action;
                    if (action === 'stop') {
                        preview('stopped', null, 0);
                    } else if (action === 'next') {
                        preview(state, pageStep(1), 0);
                    } else if (action === 'previous') {
                        const order = currentOrder();
                        preview(state, pageStep(-1) || pageAt(order, order.indexOf(currentId)) || last?.now_playing, 0);
                    }
                    send(URLS[action]);
                });
            });
            $('vpLoop').addEventListener('click', () => {
                const loop = $('vpLoop').getAttribute('aria-pressed') !== 'true';
                $('vpLoop').setAttribute('aria-pressed', loop ? 'true' : 'false');
                if (last) {
                    last = { ...last, player: { ...last.player, loop } };
                }
                send(URLS.loop, 'POST', { loop });
            });
            list.addEventListener('click', event => {
                const button = event.target.closest('[data-row-action]');
                if (!button) {
                    return;
                }
                const row = button.closest('.vp-row');
                if (button.dataset.rowAction === 'playFrom') {
                    const order = currentOrder();
                    preview('playing', pageAt(order, order.indexOf(Number(row.dataset.id))), 0);
                }
                send(URLS[button.dataset.rowAction].replace('__ID__', row.dataset.id)).then(data => {
                    if (data && button.dataset.rowAction === 'playNext') {
                        flash(rows.get(Number(row.dataset.id)));
                    }
                });
            });

            /* ── Drag to reorder ─────────────────────────────────────── */

            function listRows() {
                return [...list.querySelectorAll(':scope > .vp-row')];
            }

            function currentOrder() {
                return listRows().map(row => Number(row.dataset.id));
            }

            function measure() {
                return new Map(listRows().map(row => [row, row.getBoundingClientRect().top]));
            }

            /** Slides every row from where it was (in tops) to where it is now; the dragged row follows the pointer instead. */
            function slideFrom(tops) {
                listRows().forEach(row => {
                    if (row === drag?.row || !tops.has(row)) {
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

            function flash(row) {
                if (!row) {
                    return;
                }
                row.classList.remove('vp-flash');
                void row.offsetWidth;
                row.classList.add('vp-flash');
            }

            function showOrderError(message) {
                $('vpOrderErrorText').textContent = message;
                $('vpOrderError').hidden = !message;
            }

            async function saveOrder() {
                const data = await send(URLS.reorder, 'PUT', { order: currentOrder() });
                showOrderError(data ? '' : 'The new order could not be saved.');
            }

            function dragUpdate() {
                const { row } = drag;
                const pointer = drag.clientY + window.scrollY;
                let offset = pointer - drag.startY;
                let next = row.nextElementSibling;
                let prev = row.previousElementSibling;
                while (next?.matches('.vp-row') && offset > next.offsetHeight / 2) {
                    const tops = measure();
                    row.before(next);
                    drag.startY += next.offsetHeight;
                    offset = pointer - drag.startY;
                    slideFrom(tops);
                    next = row.nextElementSibling;
                }
                while (prev?.matches('.vp-row') && offset < -prev.offsetHeight / 2) {
                    const tops = measure();
                    row.after(prev);
                    drag.startY -= prev.offsetHeight;
                    offset = pointer - drag.startY;
                    slideFrom(tops);
                    prev = row.previousElementSibling;
                }
                row.style.transform = `translateY(${offset}px)`;
            }

            /** Scrolls the page while the pointer is held near the top or bottom edge, so long playlists can be dragged through. */
            function dragFrame() {
                if (!drag) {
                    return;
                }
                const edge = 70;
                const top = 56 + edge;
                if (drag.clientY < top) {
                    window.scrollBy(0, -Math.ceil((top - drag.clientY) / 6));
                } else if (drag.clientY > window.innerHeight - edge) {
                    window.scrollBy(0, Math.ceil((drag.clientY - window.innerHeight + edge) / 6));
                }
                dragUpdate();
                drag.frame = requestAnimationFrame(dragFrame);
            }

            function dragEnd() {
                if (!drag) {
                    return;
                }
                const { row, startOrder, frame } = drag;
                cancelAnimationFrame(frame);
                drag = null;
                document.body.classList.remove('vp-sorting');
                row.style.transition = 'transform .18s ease';
                row.style.transform = '';
                setTimeout(() => row.classList.remove('vp-dragging'), 180);
                paintRows();
                if (currentOrder().join() !== startOrder) {
                    flash(row);
                    saveOrder();
                }
            }

            list.addEventListener('pointerdown', event => {
                const handle = event.target.closest('.vp-drag');
                if (!handle || event.button !== 0 || drag) {
                    return;
                }
                event.preventDefault();
                handle.setPointerCapture(event.pointerId);
                const row = handle.closest('.vp-row');
                row.style.transition = 'none';
                row.classList.add('vp-dragging');
                document.body.classList.add('vp-sorting');
                drag = { row, clientY: event.clientY, startY: event.clientY + window.scrollY, startOrder: currentOrder().join() };
                drag.frame = requestAnimationFrame(dragFrame);
            });

            list.addEventListener('pointermove', event => {
                if (drag) {
                    drag.clientY = event.clientY;
                    dragUpdate();
                }
            });

            list.addEventListener('pointerup', event => {
                if (drag) {
                    drag.clientY = event.clientY;
                    dragUpdate();
                }
                dragEnd();
            });
            list.addEventListener('pointercancel', dragEnd);
            list.addEventListener('lostpointercapture', dragEnd);

            render(@json($snapshot));
            setTimeout(async function loop() {
                if (!document.hidden) {
                    await poll();
                }
                setTimeout(loop, POLL_MS);
            }, POLL_MS);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    poll();
                }
            });
        })();
    </script>
@endpush
