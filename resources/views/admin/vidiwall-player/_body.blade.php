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
            <span>{{ $event->vidiwall_photo_seconds }}s per photo, videos play through</span>
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
            let shownKey = null;
            let clock = { id: null, elapsed: 0, slot: 0, at: 0 };
            let seq = 0;
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

            function renderNow(data) {
                const now = data.now_playing;
                const player = data.player;
                const localElapsed = elapsedNow();
                const continuing = now && now.id === clock.id && player.state === state;
                state = player.state;
                currentId = now ? now.id : null;
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
                    $('vpNowName').textContent = 'Nothing on screen';
                    $('vpNowMeta').textContent = data.counts.total
                        ? 'Press play to start the playlist from the top.'
                        : 'Approve photos or videos and they will line up here.';
                    shownKey = null;
                    clock = { id: null, elapsed: 0, slot: 0, at: performance.now() };
                    paintClock();
                    return;
                }

                $('vpNowName').textContent = now.uploader;
                const order = data.items && !drag ? data.items.map(item => item.id) : currentOrder();
                const position = order.indexOf(now.id) + 1;
                $('vpNowMeta').textContent = (now.is_video ? 'Video' : 'Photo')
                    + (position ? ' · ' + position + ' of ' + data.counts.total : '');

                if (shownKey !== now.id) {
                    shownKey = now.id;
                    if (now.is_video) {
                        $('vpNowImg').hidden = true;
                        $('vpNowVideo').src = now.video_url + '#t=0.5';
                        $('vpNowVideo').hidden = false;
                    } else {
                        $('vpNowVideo').hidden = true;
                        $('vpNowVideo').removeAttribute('src');
                        $('vpNowImg').src = now.thumb_url;
                        $('vpNowImg').hidden = false;
                    }
                }

                if (!continuing || Math.abs(player.elapsed_ms - localElapsed) > 600) {
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
                    const isCurrent = id === currentId;
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

            function renderCounts(counts) {
                $('vpCountTotal').textContent = counts.total;
                $('vpCountNew').textContent = counts.unplayed;
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
                renderCounts(data.counts);
                document.dispatchEvent(new CustomEvent('fotoqueue:status', { detail: { counts: data.counts, player_state: data.player.state } }));
                if (data.items && !drag) {
                    renderList(data.items);
                } else {
                    paintRows();
                }
            }

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

            function itemAt(offset) {
                const order = currentOrder();
                const index = order.indexOf(currentId);
                let id = order[index + offset];
                if (id === undefined && last?.player.loop) {
                    id = offset > 0 ? order[0] : order[order.length - 1];
                }
                return id === undefined ? null : itemsById.get(id) || null;
            }

            async function send(url, method = 'POST', body = {}) {
                const mine = ++seq;
                try {
                    const response = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ ...body, v: version }),
                    });
                    if (response.status === 419) {
                        flashMessage('Your session expired. Reload the page.');
                        return null;
                    }
                    const data = await response.json().catch(() => ({}));
                    if (data.player && mine === seq) {
                        render(data);
                    }
                    if (mine === seq || !response.ok) {
                        flashMessage(data.message || (response.ok ? '' : 'That did not work. Try again.'));
                    }
                    if (!data.player && mine === seq) {
                        poll();
                    }
                    return response.ok ? data : null;
                } catch (e) {
                    flashMessage('No connection to the server.');
                    if (mine === seq) {
                        poll();
                    }
                    return null;
                }
            }

            /** A poll is dropped when a press happened while it was in flight, so a stale answer never undoes the press. */
            async function poll() {
                const mine = seq;
                try {
                    const response = await fetch(URLS.status + '?v=' + encodeURIComponent(drag ? '' : version), {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store',
                    });
                    if (response.ok) {
                        const data = await response.json();
                        if (mine === seq) {
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
                preview('playing', resume ? last.now_playing : itemsById.get(currentOrder()[0]), resume ? elapsedNow() : 0);
                send(URLS.play);
            });
            root.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.dataset.action;
                    if (action === 'stop') {
                        preview('stopped', null, 0);
                    } else if (action === 'next') {
                        preview(state, itemAt(1), 0);
                    } else if (action === 'previous') {
                        preview(state, itemAt(-1) || last?.now_playing, 0);
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
                    preview('playing', itemsById.get(Number(row.dataset.id)), 0);
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
