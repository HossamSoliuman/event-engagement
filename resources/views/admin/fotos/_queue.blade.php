{{-- The Foto Queue, shared by the admin and moderator pages. $urls holds the routes the script calls; action urls carry an __ID__ placeholder. --}}
@php
    $emptyLabels = ['pending' => 'Nothing waiting for review', 'approved' => 'No approved items yet', 'rejected' => 'Nothing rejected'];
@endphp

@push('styles')
    <style>
        [hidden] { display: none !important; }
        .fq { --fq-accent: var(--mod, var(--red)); }

        .fq-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 16px; transition: opacity .15s; }
        .fq-grid.is-loading { opacity: .4; pointer-events: none; }

        .fq-card { position: relative; display: flex; flex-direction: column; min-width: 0; background: var(--card2); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; transition: border-color .15s, box-shadow .15s; }
        .fq-card:hover { border-color: #34345a; box-shadow: 0 12px 30px rgba(0, 0, 0, .35); }
        .fq-card.is-live { border-color: var(--gold); box-shadow: 0 0 0 1px var(--gold), 0 0 28px rgba(255, 215, 0, .14); }
        .fq-card.is-leaving { pointer-events: none; }
        .fq-card.is-new { animation: fqEnter .35s cubic-bezier(.2, .8, .2, 1), fqGlow 2.6s ease-out; }
        @keyframes fqEnter { from { opacity: 0; transform: scale(.94); } }
        @keyframes fqGlow { 0%, 35% { box-shadow: 0 0 0 2px var(--fq-accent); } 100% { box-shadow: 0 0 0 0 transparent; } }

        .fq-media { position: relative; display: block; width: 100%; aspect-ratio: 1; padding: 0; border: 0; background: #07070f; cursor: zoom-in; overflow: hidden; }
        .fq-media img, .fq-media video { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .4s ease; }
        .fq-card:hover .fq-media img, .fq-card:hover .fq-media video { transform: scale(1.04); }
        .fq-media::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0, 0, 0, .4) 0, transparent 28%, transparent 72%, rgba(0, 0, 0, .3)); pointer-events: none; }
        .fq-media:focus-visible { outline: 2px solid var(--fq-accent); outline-offset: -2px; }
        .fq-chip, .fq-kind { position: absolute; top: 10px; z-index: 1; display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border-radius: 20px; font-size: 10px; font-weight: 800; letter-spacing: .6px; background: rgba(10, 10, 24, .75); color: #fff; backdrop-filter: blur(6px); }
        .fq-chip { left: 10px; text-transform: uppercase; color: #c9c9de; }
        .fq-kind { right: 10px; }
        .fq-kind .lucide-icon { width: 11px; height: 11px; fill: currentColor; }
        .fq-card[data-state="live"] .fq-chip { background: var(--gold); color: #0A0A18; }
        .fq-card[data-state="queued"] .fq-chip { background: rgba(99, 102, 241, .92); color: #fff; }
        .fq-zoom { position: absolute; right: 10px; bottom: 10px; z-index: 1; display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: rgba(10, 10, 24, .75); color: #fff; opacity: 0; transform: translateY(4px); transition: opacity .15s, transform .15s; }
        .fq-zoom .lucide-icon { width: 14px; height: 14px; }
        .fq-card:hover .fq-zoom, .fq-media:focus-visible .fq-zoom { opacity: 1; transform: none; }

        .fq-info { display: flex; align-items: flex-start; gap: 8px; padding: 11px 13px 2px; min-width: 0; }
        .fq-info-text { flex: 1; min-width: 0; }
        .fq-info .fq-btn-icon { flex: 0 0 32px; height: 32px; border-radius: 8px; }
        .fq-info .fq-btn-icon.is-armed { flex: 0 0 auto; }
        .fq-name { font-weight: 700; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fq-sub { display: flex; gap: 6px; margin-top: 2px; font-size: 11.5px; color: var(--muted); white-space: nowrap; overflow: hidden; }
        .fq-sub > * + *::before { content: '·'; margin-right: 6px; }
        .fq-note { margin-top: 6px; font-size: 11.5px; color: #f87171; }

        .fq-actions { display: flex; gap: 8px; margin-top: auto; padding: 10px 13px 13px; }
        .fq-btn { flex: 1; min-width: 0; display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; padding: 0 12px; border: 1px solid transparent; border-radius: 9px; font: 600 13px 'DM Sans', sans-serif; white-space: nowrap; cursor: pointer; transition: background .15s, border-color .15s, color .15s, transform .08s, filter .15s; }
        .fq-btn:active { transform: scale(.96); }
        .fq-btn:focus-visible { outline: 2px solid var(--fq-accent); outline-offset: 2px; }
        .fq-btn .lucide-icon { width: 15px; height: 15px; flex-shrink: 0; }
        .fq-btn-approve { background: var(--green); color: #04220f; }
        .fq-btn-approve:hover { filter: brightness(1.1); }
        .fq-btn-reject { background: rgba(255, 255, 255, .04); border-color: var(--border); color: var(--text); }
        .fq-btn-reject:hover { border-color: #f59e0b; color: #fbbf24; background: rgba(245, 158, 11, .08); }
        .fq-btn-delete { background: rgba(239, 68, 68, .08); border-color: rgba(239, 68, 68, .22); color: #f87171; }
        .fq-btn-delete:hover { background: rgba(239, 68, 68, .16); border-color: rgba(239, 68, 68, .45); }
        .fq-btn-delete.is-armed { background: #dc2626; border-color: #dc2626; color: #fff; }
        .fq-btn-icon { flex: 0 0 36px; padding: 0; }
        .fq-btn-icon .fq-btn-label { display: none; }
        .fq-btn-icon.is-armed { flex: 0 0 auto; padding: 0 12px; }
        .fq-btn-icon.is-armed .fq-btn-label { display: inline; }
        .fq-btn kbd { margin-left: 2px; padding: 2px 5px; border-radius: 4px; border: 1px solid currentColor; font: 700 10px/1 'DM Sans', sans-serif; opacity: .55; }

        .fq-empty .lucide-icon { opacity: .5; }
        .fq-more { display: flex; justify-content: center; padding: 28px 0 8px; }
        .fq-spinner { width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--border); border-top-color: var(--fq-accent); animation: fqSpin .7s linear infinite; }
        @keyframes fqSpin { to { transform: rotate(360deg); } }

        .fq-lb { position: fixed; inset: 0; z-index: 1000; display: flex; flex-direction: column; background: rgba(5, 5, 12, .95); backdrop-filter: blur(8px); opacity: 0; transition: opacity .15s; }
        .fq-lb.is-open { opacity: 1; }
        .fq-lb-top { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; font-size: 12px; color: var(--muted); font-variant-numeric: tabular-nums; }
        .fq-lb-stage { position: relative; flex: 1; min-height: 0; display: flex; align-items: center; justify-content: center; padding: 0 76px; }
        .fq-lb-stage img, .fq-lb-stage video { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 10px; box-shadow: 0 24px 70px rgba(0, 0, 0, .6); background: #000; }
        .fq-lb-round { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: rgba(26, 26, 46, .85); color: var(--text); cursor: pointer; transition: background .15s, opacity .15s; }
        .fq-lb-round:hover:not(:disabled) { background: var(--border); }
        .fq-lb-round:disabled { opacity: .25; cursor: default; }
        .fq-lb-round .lucide-icon { width: 20px; height: 20px; }
        .fq-lb-prev, .fq-lb-next { position: absolute; top: 50%; transform: translateY(-50%); }
        .fq-lb-prev { left: 18px; }
        .fq-lb-next { right: 18px; }
        .fq-lb-bar { display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%; max-width: 960px; margin: 0 auto; padding: 18px 24px 24px; }
        .fq-lb-name { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 19px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fq-lb-meta { margin-top: 2px; font-size: 12px; color: var(--muted); }
        .fq-lb-info { min-width: 0; }
        .fq-lb-actions { display: flex; gap: 10px; flex-shrink: 0; }
        .fq-lb-actions .fq-btn { flex: none; min-width: 124px; height: 44px; font-size: 14px; }

        .fq-toasts { position: fixed; right: 20px; bottom: 20px; z-index: 1100; display: flex; flex-direction: column; align-items: flex-end; gap: 8px; pointer-events: none; }
        .fq-toast { padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); background: var(--card2); font-size: 13px; box-shadow: 0 12px 30px rgba(0, 0, 0, .45); animation: fqToastIn .2s ease-out; transition: opacity .2s, transform .2s; }
        .fq-toast.is-error { border-color: rgba(239, 68, 68, .45); color: #fca5a5; }
        .fq-toast.is-out { opacity: 0; transform: translateY(6px); }
        .fq-offline { display: inline-flex; align-items: center; gap: 8px; }
        .fq-offline .fq-spinner { width: 14px; height: 14px; }
        @keyframes fqToastIn { from { opacity: 0; transform: translateY(8px); } }

        @media (max-width: 640px) {
            .fq-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
            .fq-info { padding: 9px 10px 2px; }
            .fq-actions { gap: 6px; padding: 8px 10px 10px; }
            .fq-btn { padding: 0 8px; }
            .fq-btn:not(.is-armed) .fq-btn-label { display: none; }
            .fq-btn kbd { display: none; }
            .fq-lb-stage { padding: 0 10px; }
            .fq-lb-prev, .fq-lb-next { display: none; }
            .fq-lb-bar { flex-direction: column; align-items: stretch; padding: 14px 14px 20px; }
            .fq-lb-actions .fq-btn { flex: 1; min-width: 0; }
            .fq-lb-actions .fq-btn-label { display: inline; }
            .fq-toasts { left: 16px; right: 16px; align-items: stretch; }
        }
    </style>
@endpush

@include('admin.fotos._tabs', ['routePrefix' => $routePrefix, 'active' => $status])

<div class="fq" id="fq" data-status="{{ $status }}" data-has-more="{{ $hasMore ? 1 : 0 }}" data-cursor="{{ now()->getTimestamp() }}">
    <div class="fq-grid" id="fqGrid">
        @foreach($fotos as $foto)
            @include('admin.fotos._card', ['foto' => $foto])
        @endforeach
    </div>

    <div class="empty-state fq-empty" id="fqEmpty" @if($fotos->isNotEmpty()) hidden @endif>
        <div class="empty-icon"><i data-lucide="images" class="lucide-icon"></i></div>
        <h3 id="fqEmptyText">{{ $emptyLabels[$status] }}</h3>
    </div>

    <div class="fq-more" id="fqMore" @if(! $hasMore) hidden @endif><span class="fq-spinner"></span></div>
</div>

<div class="fq-lb" id="fqLightbox" role="dialog" aria-modal="true" aria-label="Preview" hidden>
    <div class="fq-lb-top">
        <span id="fqLbPos"></span>
        <button type="button" class="fq-lb-round" id="fqLbClose" aria-label="Close"><i data-lucide="x" class="lucide-icon"></i></button>
    </div>
    <div class="fq-lb-stage" id="fqLbStage">
        <button type="button" class="fq-lb-round fq-lb-prev" id="fqLbPrev" aria-label="Previous"><i data-lucide="chevron-left" class="lucide-icon"></i></button>
        <img id="fqLbImg" alt="" hidden>
        <video id="fqLbVideo" controls playsinline hidden></video>
        <button type="button" class="fq-lb-round fq-lb-next" id="fqLbNext" aria-label="Next"><i data-lucide="chevron-right" class="lucide-icon"></i></button>
    </div>
    <div class="fq-lb-bar">
        <div class="fq-lb-info">
            <div class="fq-lb-name" id="fqLbName"></div>
            <div class="fq-lb-meta" id="fqLbMeta"></div>
        </div>
        <div class="fq-lb-actions" id="fqLbActions"></div>
    </div>
</div>

<div class="fq-toasts" id="fqToasts" aria-live="polite">
    <div class="fq-toast fq-offline" id="fqOffline" hidden><span class="fq-spinner"></span> Reconnecting…</div>
</div>

@push('scripts')
    <script>
        (() => {
            const URLS = @json($urls);
            const EMPTY = @json($emptyLabels);
            const CSRF = document.querySelector('meta[name=csrf-token]').content;
            const POLL_MS = 2000;
            const HIDDEN_POLL_MS = 10000;
            const ACTIONS = {
                pending: [['approve', 'Approve', 'check', 'a'], ['reject', 'Reject', 'x', 'r'], ['delete', 'Delete', 'trash-2', 'd']],
                approved: [['reject', 'Reject', 'x', 'r'], ['delete', 'Delete', 'trash-2', 'd']],
                rejected: [['approve', 'Restore', 'rotate-ccw', 'a'], ['delete', 'Delete', 'trash-2', 'd']],
            };
            const MOVES_TO = { approve: 'approved', reject: 'rejected', delete: null };
            const VERBS = { approve: 'approve', reject: 'reject', delete: 'delete' };

            const $ = id => document.getElementById(id);
            const root = $('fq');
            const grid = $('fqGrid');
            const lightbox = $('fqLightbox');
            const baseTitle = document.title;

            let status = root.dataset.status;
            let hasMore = root.dataset.hasMore === '1';
            let cursor = Number(root.dataset.cursor);
            let counts = @json($counts);
            let playerState = null;
            let changeSeq = 0;
            let tabSeq = 0;
            let inFlight = 0;
            let loadingMore = false;
            let failures = 0;
            let armed = null;
            let lbCard = null;
            const removedIds = new Map();
            const tabCache = new Map();

            /* ── Helpers ─────────────────────────────────────────────── */

            function request(url, method = 'GET') {
                return fetch(url, {
                    method,
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                }).then(response => {
                    if (response.status === 401 || response.status === 419) {
                        toast('Your session expired. Reload the page.', true);
                        throw new Error('session');
                    }
                    if (!response.ok) {
                        throw new Error(String(response.status));
                    }
                    return response.json();
                });
            }

            function withQuery(url, params) {
                const query = new URLSearchParams(params).toString();
                return url + (url.includes('?') ? '&' : '?') + query;
            }

            function ago(ms) {
                const seconds = Math.max(0, (Date.now() - ms) / 1000);
                if (seconds < 45) return 'just now';
                if (seconds < 3600) return Math.round(seconds / 60) + 'm ago';
                if (seconds < 86400) return Math.round(seconds / 3600) + 'h ago';
                return new Date(ms).toLocaleDateString([], { day: 'numeric', month: 'short' });
            }

            function icons() {
                window.lucide?.createIcons();
            }

            function toast(text, isError = false) {
                const el = document.createElement('div');
                el.className = 'fq-toast' + (isError ? ' is-error' : '');
                el.textContent = text;
                $('fqToasts').appendChild(el);
                setTimeout(() => {
                    el.classList.add('is-out');
                    setTimeout(() => el.remove(), 250);
                }, 3500);
            }

            function htmlToCard(html) {
                const template = document.createElement('template');
                template.innerHTML = html.trim();
                return template.content.firstElementChild;
            }

            const allCards = () => [...grid.children];
            const liveCards = () => allCards().filter(card => !card.classList.contains('is-leaving'));
            const cardById = id => grid.querySelector(`.fq-card[data-id="${id}"]`);
            const lowestId = () => Math.min(...allCards().map(card => Number(card.dataset.id)));

            function wasRemoved(id) {
                const until = removedIds.get(String(id));
                return until !== undefined && until > Date.now();
            }

            /* ── Counts, chips, times ────────────────────────────────── */

            function setCounts(next) {
                counts = { ...counts, ...next };
                document.title = (counts.pending ? '(' + counts.pending + ') ' : '') + baseTitle;
                document.querySelectorAll('[data-pending-badge="{{ $event->id }}"]').forEach(badge => {
                    badge.textContent = counts.pending;
                    badge.style.display = counts.pending ? '' : 'none';
                });
                document.dispatchEvent(new CustomEvent('fotoqueue:status', { detail: { counts, player_state: playerState } }));
            }

            function paintChip(card) {
                const chip = card.querySelector('.fq-chip');
                const state = card.dataset.state;
                card.classList.toggle('is-live', state === 'live');
                if (!chip) return;
                const label = state === 'live' ? (playerState === 'paused' ? 'Paused' : 'On screen')
                    : state === 'queued' ? 'In queue'
                    : state === 'shown' ? 'Shown' + (card.dataset.shown ? ' · ' + ago(Number(card.dataset.shown)) : '')
                    : '';
                chip.hidden = !label;
                if (chip.textContent !== label) chip.textContent = label;
            }

            function hydrate(scope = grid) {
                scope.querySelectorAll('time[data-ts]').forEach(el => { el.textContent = ago(Number(el.dataset.ts)); });
                (scope === grid ? allCards() : [scope]).forEach(paintChip);
            }

            function syncEmpty() {
                $('fqEmptyText').textContent = EMPTY[status];
                $('fqEmpty').hidden = grid.children.length > 0;
                $('fqMore').hidden = !hasMore;
            }

            /* ── Card motion ─────────────────────────────────────────── */

            /** Moves the other cards smoothly into the places a change leaves them in. */
            function flip(change) {
                const before = new Map(allCards().map(card => [card, card.getBoundingClientRect()]));
                change();
                allCards().forEach(card => {
                    const old = before.get(card);
                    if (!old) return;
                    const now = card.getBoundingClientRect();
                    const dx = old.left - now.left;
                    const dy = old.top - now.top;
                    if (dx || dy) {
                        card.animate([{ transform: `translate(${dx}px, ${dy}px)` }, { transform: 'none' }], { duration: 280, easing: 'cubic-bezier(.2, .8, .2, 1)' });
                    }
                });
            }

            function removeCard(card) {
                if (!card.isConnected || card.classList.contains('is-leaving')) return;
                card.classList.add('is-leaving');
                card.animate([{ opacity: 1, transform: 'scale(1)' }, { opacity: 0, transform: 'scale(.9)' }], { duration: 170, easing: 'ease-in', fill: 'forwards' });
                setTimeout(() => {
                    if (card.classList.contains('is-leaving') && card.isConnected) {
                        flip(() => card.remove());
                        syncEmpty();
                    }
                }, 170);
            }

            function insertCard(card, isNew) {
                const id = Number(card.dataset.id);
                const before = allCards().find(other => Number(other.dataset.id) < id);
                if (isNew) card.classList.add('is-new');
                flip(() => grid.insertBefore(card, before || null));
                hydrate(card);
                syncEmpty();
            }

            function restoreCard(card) {
                card.getAnimations().forEach(animation => animation.cancel());
                card.classList.remove('is-leaving');
                if (!card.isConnected) insertCard(card, false);
            }

            /* ── Actions ─────────────────────────────────────────────── */

            function deleteButtons(id) {
                return document.querySelectorAll(`.fq-card[data-id="${id}"] [data-act="delete"], #fqLbActions[data-id="${id}"] [data-act="delete"]`);
            }

            function disarm() {
                if (!armed) return;
                clearTimeout(armed.timer);
                deleteButtons(armed.id).forEach(button => {
                    button.classList.remove('is-armed');
                    button.querySelector('.fq-btn-label').textContent = 'Delete';
                });
                armed = null;
            }

            /** Delete takes two presses: the first turns the button into a confirm for a few seconds. */
            function confirmDelete(id) {
                if (armed && armed.id === id) {
                    disarm();
                    return true;
                }
                disarm();
                armed = { id, timer: setTimeout(disarm, 3000) };
                deleteButtons(id).forEach(button => {
                    button.classList.add('is-armed');
                    button.querySelector('.fq-btn-label').textContent = 'Confirm';
                });
                return false;
            }

            async function act(card, action) {
                if (!card || card.classList.contains('is-leaving') || !URLS[action]) return;
                const id = card.dataset.id;
                if (action === 'delete' && !confirmDelete(id)) return;
                if (action !== 'delete') disarm();

                const from = status;
                const to = MOVES_TO[action];
                changeSeq++;
                inFlight++;
                removedIds.set(id, Date.now() + 15000);
                setCounts({ [from]: counts[from] - 1, ...(to ? { [to]: counts[to] + 1 } : {}) });
                if (lbCard === card) stepLightboxAway(card);
                removeCard(card);

                try {
                    const data = await request(URLS[action].replace('__ID__', id), action === 'delete' ? 'DELETE' : 'POST');
                    changeSeq++;
                    if (inFlight === 1) setCounts(data.counts);
                } catch (error) {
                    if (error.message === '404') {
                        return;
                    }
                    removedIds.delete(id);
                    setCounts({ [from]: counts[from] + 1, ...(to ? { [to]: counts[to] - 1 } : {}) });
                    if (status === from) restoreCard(card);
                    if (error.message !== 'session') {
                        toast('Could not ' + VERBS[action] + ' ' + card.dataset.name + '. Try again.', true);
                    }
                } finally {
                    inFlight--;
                }
            }

            grid.addEventListener('click', event => {
                const button = event.target.closest('[data-act]');
                if (!button) return;
                const card = button.closest('.fq-card');
                if (button.dataset.act === 'view') {
                    openLightbox(card);
                } else {
                    act(card, button.dataset.act);
                }
            });

            /* ── Lightbox ────────────────────────────────────────────── */

            function renderLightbox() {
                const card = lbCard;
                const cards = liveCards();
                const index = cards.indexOf(card);
                const isVideo = card.dataset.kind === 'video';
                const img = $('fqLbImg');
                const video = $('fqLbVideo');

                $('fqLbPos').textContent = (index + 1) + ' / ' + counts[status];
                $('fqLbName').textContent = card.dataset.name;
                $('fqLbMeta').textContent = [card.querySelector('.fq-sub')?.textContent.replace(/\s+/g, ' ').trim(), card.querySelector('.fq-chip:not([hidden])')?.textContent]
                    .filter(Boolean).join(' · ');
                $('fqLbPrev').disabled = index <= 0;
                $('fqLbNext').disabled = index >= cards.length - 1 && !hasMore;

                img.hidden = isVideo;
                video.hidden = !isVideo;
                if (isVideo) {
                    img.removeAttribute('src');
                    if (video.dataset.src !== card.dataset.src) {
                        video.dataset.src = card.dataset.src;
                        video.src = card.dataset.src;
                        video.play().catch(() => {});
                    }
                } else {
                    video.pause();
                    video.removeAttribute('src');
                    video.dataset.src = '';
                    img.src = card.querySelector('.fq-media img')?.currentSrc || card.dataset.src;
                    const full = new Image();
                    full.onload = () => { if (lbCard === card) img.src = full.src; };
                    full.src = card.dataset.src;
                }

                [cards[index + 1], cards[index - 1]].forEach(neighbour => {
                    if (neighbour && neighbour.dataset.kind === 'photo') new Image().src = neighbour.dataset.src;
                });
                if (index >= cards.length - 3) loadMore();

                const actions = $('fqLbActions');
                actions.dataset.id = card.dataset.id;
                actions.innerHTML = ACTIONS[status].map(([action, label, icon, key]) =>
                    `<button type="button" class="fq-btn fq-btn-${action === 'approve' ? 'approve' : action}" data-act="${action}">`
                    + `<i data-lucide="${icon}" class="lucide-icon"></i><span class="fq-btn-label">${label}</span><kbd>${key.toUpperCase()}</kbd></button>`
                ).join('');
                if (armed && armed.id === card.dataset.id) {
                    actions.querySelectorAll('[data-act="delete"]').forEach(button => {
                        button.classList.add('is-armed');
                        button.querySelector('.fq-btn-label').textContent = 'Confirm';
                    });
                }
                icons();
            }

            function openLightbox(card) {
                disarm();
                lbCard = card;
                renderLightbox();
                lightbox.hidden = false;
                document.body.style.overflow = 'hidden';
                requestAnimationFrame(() => lightbox.classList.add('is-open'));
            }

            function closeLightbox() {
                if (lightbox.hidden) return;
                disarm();
                const video = $('fqLbVideo');
                video.pause();
                video.removeAttribute('src');
                video.dataset.src = '';
                video.load();
                lightbox.classList.remove('is-open');
                lightbox.hidden = true;
                document.body.style.overflow = '';
                lbCard?.querySelector('.fq-media')?.focus({ preventScroll: true });
                lbCard = null;
            }

            function moveLightbox(step) {
                const cards = liveCards();
                const next = cards[cards.indexOf(lbCard) + step];
                if (next) {
                    disarm();
                    lbCard = next;
                    renderLightbox();
                }
            }

            /** Shows the next item (or the previous one at the end) before the current card leaves. */
            function stepLightboxAway(card) {
                const cards = liveCards();
                const index = cards.indexOf(card);
                const next = cards[index + 1] || cards[index - 1];
                if (next) {
                    lbCard = next;
                    renderLightbox();
                } else {
                    closeLightbox();
                }
            }

            $('fqLbClose').addEventListener('click', closeLightbox);
            $('fqLbPrev').addEventListener('click', () => moveLightbox(-1));
            $('fqLbNext').addEventListener('click', () => moveLightbox(1));
            $('fqLbActions').addEventListener('click', event => {
                const button = event.target.closest('[data-act]');
                if (button && lbCard) act(lbCard, button.dataset.act);
            });
            $('fqLbStage').addEventListener('click', event => {
                if (event.target === event.currentTarget) closeLightbox();
            });

            let touchX = null;
            $('fqLbStage').addEventListener('touchstart', event => { touchX = event.touches[0].clientX; }, { passive: true });
            $('fqLbStage').addEventListener('touchend', event => {
                if (touchX === null) return;
                const dx = event.changedTouches[0].clientX - touchX;
                touchX = null;
                if (Math.abs(dx) > 50) moveLightbox(dx < 0 ? 1 : -1);
            });

            document.addEventListener('keydown', event => {
                if (lightbox.hidden || event.ctrlKey || event.metaKey || event.altKey) return;
                const key = event.key.toLowerCase();
                if (key === 'escape') {
                    closeLightbox();
                } else if (key === 'arrowright') {
                    moveLightbox(1);
                } else if (key === 'arrowleft') {
                    moveLightbox(-1);
                } else {
                    const action = ACTIONS[status].find(entry => entry[3] === key);
                    if (action && lbCard) {
                        event.preventDefault();
                        act(lbCard, action[0]);
                    }
                }
            });

            /* ── Tabs and paging ─────────────────────────────────────── */

            function fetchTab(next) {
                const cached = tabCache.get(next);
                if (cached && Date.now() - cached.at < 4000) return cached.promise;
                const promise = request(withQuery(URLS.list, { status: next }));
                tabCache.set(next, { promise, at: Date.now() });
                promise.catch(() => tabCache.delete(next));
                return promise;
            }

            function appendCards(cards) {
                const fragment = document.createDocumentFragment();
                cards.forEach(({ id, html }) => {
                    if (!cardById(id)) fragment.appendChild(htmlToCard(html));
                });
                grid.appendChild(fragment);
            }

            function markTab(next) {
                document.querySelectorAll('[data-fq-tab]').forEach(tab => tab.classList.toggle('active', tab.dataset.fqTab === next));
            }

            async function switchTab(next, push = true) {
                if (next === status && push) return;
                const seq = ++tabSeq;
                const previous = status;
                changeSeq++;
                closeLightbox();
                status = next;
                root.dataset.status = next;
                markTab(next);
                if (push) history.pushState({ fqStatus: next }, '', withQuery(URLS.list, { status: next }));
                grid.classList.add('is-loading');

                try {
                    const data = await fetchTab(next);
                    tabCache.delete(next);
                    if (seq !== tabSeq) return;
                    grid.replaceChildren();
                    appendCards(data.cards);
                    hasMore = data.has_more;
                    cursor = data.cursor;
                    if (!inFlight) setCounts(data.counts);
                    hydrate();
                    syncEmpty();
                    icons();
                    window.scrollTo({ top: 0 });
                } catch (error) {
                    if (seq !== tabSeq) return;
                    status = previous;
                    root.dataset.status = previous;
                    markTab(previous);
                    if (push) history.replaceState({ fqStatus: previous }, '', withQuery(URLS.list, { status: previous }));
                    if (error.message !== 'session') toast('Could not open that tab. Try again.', true);
                } finally {
                    if (seq === tabSeq) grid.classList.remove('is-loading');
                }
            }

            document.querySelectorAll('[data-fq-tab]').forEach(tab => {
                tab.addEventListener('click', event => {
                    if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey) return;
                    event.preventDefault();
                    switchTab(tab.dataset.fqTab);
                });
                tab.addEventListener('pointerenter', () => { if (tab.dataset.fqTab !== status) fetchTab(tab.dataset.fqTab).catch(() => {}); });
            });

            history.replaceState({ fqStatus: status }, '', location.href);
            window.addEventListener('popstate', event => {
                const next = event.state?.fqStatus || new URLSearchParams(location.search).get('status') || 'pending';
                if (next !== status) switchTab(next, false);
            });

            async function loadMore() {
                if (!hasMore || loadingMore || !grid.children.length) return;
                loadingMore = true;
                const seq = tabSeq;
                let loaded = false;
                try {
                    const data = await request(withQuery(URLS.list, { status, before: lowestId() }));
                    if (seq === tabSeq) {
                        appendCards(data.cards);
                        hasMore = data.has_more;
                        loaded = true;
                    }
                } catch (error) {
                } finally {
                    loadingMore = false;
                }
                if (!loaded) return;
                hydrate();
                syncEmpty();
                icons();
                if (lbCard) renderLightbox();
                if (hasMore && $('fqMore').getBoundingClientRect().top < window.innerHeight + 900) loadMore();
            }

            new IntersectionObserver(entries => {
                if (entries.some(entry => entry.isIntersecting)) loadMore();
            }, { rootMargin: '900px 0px' }).observe($('fqMore'));

            /* ── Live updates ────────────────────────────────────────── */

            function applySnapshot(data, sentIds) {
                playerState = data.player_state;
                if (!inFlight) setCounts(data.counts);

                const liveBadge = $('liveFotoBadge');
                if (liveBadge) {
                    liveBadge.hidden = !data.live;
                    if (data.live) liveBadge.textContent = 'Live: ' + (data.live.uploader.length > 16 ? data.live.uploader.slice(0, 16) + '…' : data.live.uploader);
                }
                const queueBadge = $('queueBadge');
                if (queueBadge) {
                    queueBadge.hidden = !data.queued;
                    queueBadge.textContent = 'Queue: ' + data.queued;
                }

                const states = new Map(data.items.map(item => [String(item.id), item]));
                liveCards().forEach(card => {
                    if (!sentIds.has(card.dataset.id)) return;
                    const item = states.get(card.dataset.id);
                    const itemStatus = item && ['live', 'queued', 'shown'].includes(item.state) ? 'approved' : item?.state;
                    if (itemStatus !== status) {
                        if (card === lbCard) stepLightboxAway(card);
                        removeCard(card);
                        return;
                    }
                    card.dataset.state = item.state;
                    card.dataset.shown = item.shown_ms || '';
                    paintChip(card);
                });

                let inserted = false;
                (data.changed || []).forEach(({ id, html }) => {
                    if (cardById(id) || wasRemoved(id) || (hasMore && id < lowestId())) return;
                    insertCard(htmlToCard(html), true);
                    inserted = true;
                });
                if (inserted) {
                    icons();
                    if (lbCard) renderLightbox();
                }
            }

            async function poll() {
                const seq = changeSeq;
                const tab = status;
                const ids = liveCards().slice(0, 300).map(card => card.dataset.id);
                try {
                    const data = await request(withQuery(URLS.status, { status: tab, since: cursor, ids: ids.join(',') }));
                    failures = 0;
                    $('fqOffline').hidden = true;
                    if (seq !== changeSeq || tab !== status) return;
                    cursor = data.cursor;
                    applySnapshot(data, new Set(ids));
                } catch (error) {
                    if (++failures >= 3 && error.message !== 'session') $('fqOffline').hidden = false;
                }
            }

            (async function loop() {
                await poll();
                setTimeout(loop, document.hidden ? HIDDEN_POLL_MS : POLL_MS);
            })();

            document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
            setInterval(() => hydrate(), 30000);
            hydrate();
            setCounts({});
        })();
    </script>
@endpush
