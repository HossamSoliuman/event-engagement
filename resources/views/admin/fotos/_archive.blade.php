{{-- The Archived tab, shared by the admin and moderator Foto Queue. $routePrefix picks the route set. --}}
@php
    $queueTotal = $counts['pending'] + $counts['approved'] + $counts['rejected'];
    $statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
@endphp

@push('styles')
    @include('admin.fotos._shared-styles')
    <style>
        [hidden] { display: none !important; }
        .fa, .fq-lb { --fq-accent: var(--mod, var(--red)); }
        .fa { display: flex; flex-direction: column; gap: 28px; }
        .fa-bar { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 18px 20px; background: var(--card2); border: 1px solid var(--border); border-radius: 14px; }
        .fa-bar p { margin: 0; color: var(--muted); font-size: 13.5px; line-height: 1.5; max-width: 620px; }
        .fa-bar p strong { color: var(--text); }
        .fa-bar-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .fa-bar-actions form { margin: 0; }
        .fa-bar .btn .lucide-icon { width: 15px; height: 15px; }
        .fa-day { display: flex; flex-direction: column; gap: 14px; }
        .fa-day h3 { margin: 0; font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; }
        .fa-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
        .fa-card { display: flex; flex-direction: column; min-width: 0; background: var(--card2); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; transition: border-color .15s, box-shadow .15s, opacity .2s, transform .2s; }
        .fa-card:hover { border-color: #34345a; box-shadow: 0 10px 26px rgba(0, 0, 0, .35); }
        .fa-card.is-leaving { opacity: 0; transform: scale(.94); pointer-events: none; }
        .fa-media { position: relative; display: block; width: 100%; aspect-ratio: 1; padding: 0; border: 0; background: #07070f; overflow: hidden; cursor: zoom-in; }
        .fa-media:focus-visible { outline: 2px solid var(--fq-accent); outline-offset: -2px; }
        .fa-media img, .fa-media video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .fa-chip { position: absolute; top: 8px; left: 8px; padding: 3px 8px; border-radius: 20px; font-size: 9.5px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; background: rgba(10, 10, 24, .75); color: #c9c9de; backdrop-filter: blur(6px); }
        .fa-chip[data-status="approved"] { color: #4ade80; }
        .fa-chip[data-status="rejected"] { color: #f87171; }
        .fa-media .fq-kind { position: absolute; top: 8px; right: 8px; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; border-radius: 20px; font-size: 9.5px; font-weight: 800; background: rgba(10, 10, 24, .75); color: #fff; }
        .fa-media .fq-kind .lucide-icon { width: 10px; height: 10px; fill: currentColor; }
        .fa-info { padding: 8px 10px 0; min-width: 0; }
        .fa-name { font-weight: 700; font-size: 12.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fa-time { margin-top: 1px; font-size: 11px; color: var(--muted); }
        .fa-actions { display: flex; margin-top: auto; padding: 8px 10px 10px; }
        .fa-actions .fq-btn { height: 32px; font-size: 12.5px; }
        @media (max-width: 640px) {
            .fa-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .fa-bar-actions, .fa-bar-actions form, .fa-bar .btn { width: 100%; justify-content: center; }
        }
    </style>
@endpush

@include('admin.fotos._tabs', ['routePrefix' => $routePrefix, 'active' => 'archived'])

<div class="fa" id="fa">
    <div class="fa-bar">
        <p>
            <span id="faQueueSome" @if($queueTotal === 0) hidden @endif><strong id="faQueueTotal">{{ $queueTotal }} {{ Str::plural('upload', $queueTotal) }}</strong> in the Foto Queue right now. Archiving moves them all here, empties the playlist and stops the vidiwall. The QR codes and design stay the same.</span>
            <span id="faQueueNone" @if($queueTotal > 0) hidden @endif>The Foto Queue is empty, so there is nothing to archive yet.</span>
        </p>
        <div class="fa-bar-actions">
            <form method="POST" action="{{ route($routePrefix . 'fotos.restore-archive', $event) }}" id="faRestoreAllForm"
                  data-confirm="Restore all {{ $counts['archived'] }} archived {{ Str::plural('upload', $counts['archived']) }}?" data-confirm-message="Each one goes back to the Foto Queue with its status, and approved ones join the end of the playlist." data-confirm-button="Restore all">
                @csrf
                <button type="submit" class="btn btn-secondary" id="faRestoreAll" @disabled($counts['archived'] === 0)>
                    <i data-lucide="rotate-ccw" class="lucide-icon"></i> Restore all
                </button>
            </form>
            <form method="POST" action="{{ route($routePrefix . 'fotos.archive', $event) }}" id="faArchiveAllForm"
                  data-confirm="Archive all {{ $queueTotal }} {{ Str::plural('upload', $queueTotal) }}?" data-confirm-message="The Foto Queue and the playlist start empty and the vidiwall stops. The QR codes stay the same." data-confirm-button="Archive all" data-confirm-danger>
                @csrf
                <button type="submit" class="btn btn-primary" id="faArchiveAll" @disabled($queueTotal === 0)>
                    <i data-lucide="archive" class="lucide-icon"></i> Archive all
                </button>
            </form>
        </div>
    </div>

    @forelse($days as $archivedAt => $fotos)
        @php
            $total = $dayTotals[$archivedAt] ?? $fotos->count();
            $dayLabel = $fotos->first()->archived_at->format('j M Y, H:i');
        @endphp
        <section class="fa-day" data-total="{{ $total }}" data-label="{{ $dayLabel }}">
            <h3>{{ $dayLabel }} · {{ $total }} {{ Str::plural('upload', $total) }}</h3>
            <div class="fa-grid">
                @foreach($fotos as $foto)
                    @php
                        $name = $foto->uploader_name ?: 'Anonymous';
                        $statusLabel = $statusLabels[$foto->status] ?? $foto->status;
                    @endphp
                    <article class="fa-card" data-id="{{ $foto->id }}" data-kind="{{ $foto->isVideo() ? 'video' : 'photo' }}" data-src="{{ $foto->media_url }}"
                             data-name="{{ $name }}" data-meta="{{ $statusLabel }} · Uploaded {{ $foto->created_at->format('j M, H:i') }}">
                        <button type="button" class="fa-media" data-act="view" aria-label="Open {{ $name }}'s {{ $foto->isVideo() ? 'video' : 'photo' }}">
                            @include('admin.fotos._media')
                            <span class="fa-chip" data-status="{{ $foto->status }}">{{ $statusLabel }}</span>
                        </button>
                        <div class="fa-info">
                            <div class="fa-name" title="{{ $name }}">{{ $name }}</div>
                            <div class="fa-time">{{ $foto->created_at->format('j M, H:i') }}</div>
                        </div>
                        <div class="fa-actions">
                            <button type="button" class="fq-btn fq-btn-approve" data-act="restore"><i data-lucide="rotate-ccw" class="lucide-icon"></i><span class="fq-btn-label">Restore</span></button>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="empty-state">
            <div class="empty-icon"><i data-lucide="archive" class="lucide-icon"></i></div>
            <h3>Nothing archived yet</h3>
        </div>
    @endforelse

    @if($archived->hasPages())
        <div>{{ $archived->links() }}</div>
    @endif
</div>

<div class="fq-lb" id="faLightbox" role="dialog" aria-modal="true" aria-label="Preview" hidden>
    <div class="fq-lb-top">
        <span id="faLbPos"></span>
        <button type="button" class="fq-lb-round" id="faLbClose" aria-label="Close"><i data-lucide="x" class="lucide-icon"></i></button>
    </div>
    <div class="fq-lb-stage" id="faLbStage">
        <button type="button" class="fq-lb-round fq-lb-prev" id="faLbPrev" aria-label="Previous"><i data-lucide="chevron-left" class="lucide-icon"></i></button>
        <img id="faLbImg" alt="" hidden>
        <video id="faLbVideo" controls playsinline hidden></video>
        <button type="button" class="fq-lb-round fq-lb-next" id="faLbNext" aria-label="Next"><i data-lucide="chevron-right" class="lucide-icon"></i></button>
    </div>
    <div class="fq-lb-bar">
        <div class="fq-lb-info">
            <div class="fq-lb-name" id="faLbName"></div>
            <div class="fq-lb-meta" id="faLbMeta"></div>
        </div>
        <div class="fq-lb-actions">
            <a class="fq-btn fq-btn-reject" id="faLbOpen" target="_blank" rel="noopener"><i data-lucide="external-link" class="lucide-icon"></i><span class="fq-btn-label">Open</span><kbd>O</kbd></a>
            <button type="button" class="fq-btn fq-btn-approve" id="faLbRestore"><i data-lucide="rotate-ccw" class="lucide-icon"></i><span class="fq-btn-label">Restore</span><kbd>R</kbd></button>
        </div>
    </div>
</div>

<div class="fq-toasts" id="faToasts" aria-live="polite"></div>

@push('scripts')
    <script>
        (() => {
            const RESTORE_URL = @json(route($routePrefix . 'fotos.restore-archived', [$event, '__ID__']));
            const CSRF = document.querySelector('meta[name=csrf-token]').content;
            const $ = id => document.getElementById(id);
            const lightbox = $('faLightbox');
            const plural = (count, word) => count + ' ' + word + (count === 1 ? '' : 's');

            let counts = @json($counts);
            let lbCard = null;
            const restoring = new Set();

            const cards = () => [...document.querySelectorAll('.fa-card:not(.is-leaving)')];
            const queueTotal = () => counts.pending + counts.approved + counts.rejected;

            function toast(text, isError = false) {
                const el = document.createElement('div');
                el.className = 'fq-toast' + (isError ? ' is-error' : '');
                el.textContent = text;
                $('faToasts').appendChild(el);
                setTimeout(() => {
                    el.classList.add('is-out');
                    setTimeout(() => el.remove(), 250);
                }, 3500);
            }

            function setCounts(next) {
                counts = { ...counts, ...next };
                const total = queueTotal();
                $('faQueueTotal').textContent = plural(total, 'upload');
                $('faQueueSome').hidden = total === 0;
                $('faQueueNone').hidden = total > 0;
                $('faArchiveAll').disabled = total === 0;
                $('faRestoreAll').disabled = counts.archived === 0;
                $('faArchiveAllForm').dataset.confirm = 'Archive all ' + plural(total, 'upload') + '?';
                $('faRestoreAllForm').dataset.confirm = 'Restore all ' + plural(counts.archived, 'archived upload') + '?';
                document.dispatchEvent(new CustomEvent('fotoqueue:status', { detail: { counts } }));
            }

            function removeCard(card) {
                const day = card.closest('.fa-day');
                const total = Number(day.dataset.total) - 1;
                day.dataset.total = total;
                day.querySelector('h3').textContent = day.dataset.label + ' · ' + plural(total, 'upload');

                card.classList.add('is-leaving');
                setTimeout(() => {
                    card.remove();
                    if (!day.querySelector('.fa-card')) day.remove();
                    if (!document.querySelector('.fa-card')) location.reload();
                }, 200);
            }

            async function restore(card) {
                const id = card.dataset.id;
                if (restoring.has(id)) return;
                restoring.add(id);

                try {
                    const response = await fetch(RESTORE_URL.replace('__ID__', id), {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await response.json().catch(() => ({}));

                    if (response.status === 401 || response.status === 419) {
                        toast('Your session expired. Reload the page.', true);
                    } else if (!response.ok) {
                        toast(data.message || 'Could not restore it. Try again.', true);
                    } else {
                        if (lbCard === card) stepLightboxAway(card);
                        removeCard(card);
                        setCounts(data.counts);
                        toast(data.message);
                    }
                } catch (error) {
                    toast('Could not restore it. Check the connection and try again.', true);
                } finally {
                    restoring.delete(id);
                }
            }

            $('fa').addEventListener('click', event => {
                const button = event.target.closest('[data-act]');
                const card = button?.closest('.fa-card');
                if (!card || card.classList.contains('is-leaving')) return;
                if (button.dataset.act === 'view') openLightbox(card);
                if (button.dataset.act === 'restore') restore(card);
            });

            /* ── Preview ─────────────────────────────────────────────── */

            function renderLightbox() {
                const list = cards();
                const index = list.indexOf(lbCard);
                const isVideo = lbCard.dataset.kind === 'video';
                const img = $('faLbImg');
                const video = $('faLbVideo');

                $('faLbPos').textContent = (index + 1) + ' / ' + list.length;
                $('faLbName').textContent = lbCard.dataset.name;
                $('faLbMeta').textContent = lbCard.dataset.meta;
                $('faLbOpen').href = lbCard.dataset.src;
                $('faLbPrev').disabled = index <= 0;
                $('faLbNext').disabled = index >= list.length - 1;

                img.hidden = isVideo;
                video.hidden = !isVideo;
                if (isVideo) {
                    img.removeAttribute('src');
                    if (video.dataset.src !== lbCard.dataset.src) {
                        video.dataset.src = lbCard.dataset.src;
                        video.src = lbCard.dataset.src;
                        video.play().catch(() => {});
                    }
                } else {
                    video.pause();
                    video.removeAttribute('src');
                    video.dataset.src = '';
                    img.src = lbCard.dataset.src;
                }
            }

            function openLightbox(card) {
                lbCard = card;
                renderLightbox();
                lightbox.hidden = false;
                document.body.style.overflow = 'hidden';
                requestAnimationFrame(() => lightbox.classList.add('is-open'));
            }

            function closeLightbox() {
                if (lightbox.hidden) return;
                const video = $('faLbVideo');
                video.pause();
                video.removeAttribute('src');
                video.dataset.src = '';
                video.load();
                lightbox.classList.remove('is-open');
                lightbox.hidden = true;
                document.body.style.overflow = '';
                lbCard?.querySelector('.fa-media')?.focus({ preventScroll: true });
                lbCard = null;
            }

            function moveLightbox(step) {
                const list = cards();
                const next = list[list.indexOf(lbCard) + step];
                if (next) {
                    lbCard = next;
                    renderLightbox();
                }
            }

            /** Shows the next item (or the previous one at the end) before the restored card leaves. */
            function stepLightboxAway(card) {
                const list = cards();
                const index = list.indexOf(card);
                const next = list[index + 1] || list[index - 1];
                if (next) {
                    lbCard = next;
                    renderLightbox();
                } else {
                    closeLightbox();
                }
            }

            $('faLbClose').addEventListener('click', closeLightbox);
            $('faLbPrev').addEventListener('click', () => moveLightbox(-1));
            $('faLbNext').addEventListener('click', () => moveLightbox(1));
            $('faLbRestore').addEventListener('click', () => { if (lbCard) restore(lbCard); });
            $('faLbStage').addEventListener('click', event => {
                if (event.target === event.currentTarget) closeLightbox();
            });

            let touchX = null;
            $('faLbStage').addEventListener('touchstart', event => { touchX = event.touches[0].clientX; }, { passive: true });
            $('faLbStage').addEventListener('touchend', event => {
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
                } else if (key === 'r' && lbCard) {
                    event.preventDefault();
                    restore(lbCard);
                } else if (key === 'o' && lbCard) {
                    event.preventDefault();
                    window.open(lbCard.dataset.src, '_blank', 'noopener');
                }
            });
        })();
    </script>
@endpush
