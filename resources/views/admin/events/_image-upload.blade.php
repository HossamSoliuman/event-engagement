{{--
    Drag-and-drop image picker with a live preview. Wraps a real file input, so the form still posts
    normally. Large photos are shrunk in the browser to fit `resize` (WxH) before they are uploaded.

    name        file input name
    current     URL of the saved image, if any
    clear       name of the "remove the saved image" checkbox (omit when the field can't be cleared)
    accept      accepted MIME types
    hint        short line shown while the field is empty
    resize      "WxH" target; larger photos are scaled down
    mode        "contain" (fit inside WxH) or "cover" (fill WxH, the wall crops the rest)
    ratio       preview shape, e.g. "16 / 9"
    thumb       preview width in px
    maxKb       hard size limit after resizing (mirrors the server rule)
    cropNote    warning shown when the picked image doesn't match `ratio`
    inputAttrs  / clearAttrs  extra attributes for the file input / clear checkbox
--}}
@php
    $upAccept = $accept ?? 'image/png,image/jpeg,image/webp,image/svg+xml,image/gif';
    $upMode = $mode ?? 'contain';
    $upError = $errors->first($name);
@endphp
<div class="up {{ $upError ? 'is-invalid' : '' }}" data-up data-current="{{ $current ?? '' }}"
    data-hint="{{ $hint ?? '' }}" data-resize="{{ $resize ?? '' }}" data-mode="{{ $upMode }}"
    data-ratio="{{ $ratio ?? '' }}" data-max-kb="{{ $maxKb ?? '' }}" data-crop-note="{{ $cropNote ?? '' }}"
    style="--up-ratio: {{ $ratio ?? '1 / 1' }}; --up-w: {{ $thumb ?? 64 }}px; --up-fit: {{ $upMode }}">
    <div class="up-thumb">
        <img class="up-img" alt="">
        <i data-lucide="image-plus" class="lucide-icon up-placeholder"></i>
        <span class="up-spinner"></span>
    </div>
    <div class="up-body">
        <div class="up-title"></div>
        <div class="up-meta"></div>
        <div class="up-msg {{ $upError ? 'is-error' : '' }}">{{ $upError }}</div>
    </div>
    <div class="up-actions">
        <button type="button" class="btn btn-secondary btn-sm" data-up-choose>
            <i data-lucide="upload" class="lucide-icon"></i> <span></span>
        </button>
        @isset($clear)
            <button type="button" class="btn btn-secondary btn-sm" data-up-remove>
                <i data-lucide="trash-2" class="lucide-icon"></i> Remove
            </button>
        @endisset
        <button type="button" class="btn btn-secondary btn-sm" data-up-undo>
            <i data-lucide="undo-2" class="lucide-icon"></i> Undo
        </button>
    </div>
    <input type="file" name="{{ $name }}" class="up-input" accept="{{ $upAccept }}" tabindex="-1"
        {!! $inputAttrs ?? '' !!}>
    @isset($clear)
        <input type="checkbox" name="{{ $clear }}" value="1" class="up-clear" hidden {!! $clearAttrs ?? '' !!}>
    @endisset
</div>

@pushOnce('styles')
    <style>
        .up { position:relative; display:flex; flex-wrap:wrap; align-items:center; gap:10px 14px; padding:12px;
            border:1.5px dashed var(--border); border-radius:10px; background:var(--dark); cursor:pointer;
            transition:border-color .15s, background .15s }
        .up:hover { border-color:#3a3a62 }
        .up:has(:focus-visible) { border-color:var(--red) }
        .up.is-drag { border-color:var(--red); border-style:solid; background:rgba(255,61,0,.07) }
        .up.is-invalid { border-color:#f87171 }

        .up-thumb { position:relative; flex-shrink:0; width:var(--up-w); aspect-ratio:var(--up-ratio); display:grid;
            place-items:center; overflow:hidden; border-radius:8px; border:1px solid var(--border);
            background-color:#191930; background-image:conic-gradient(#222240 25%, transparent 0 50%, #222240 0 75%, transparent 0);
            background-size:12px 12px }
        .up-img { width:100%; height:100%; object-fit:var(--up-fit); display:none; transition:opacity .15s }
        .up-placeholder { color:var(--muted); width:22px; height:22px }
        .up.has-image .up-img { display:block }
        .up.has-image .up-placeholder { display:none }
        .up.is-removing .up-img { opacity:.2; filter:grayscale(1) }

        .up-spinner { position:absolute; inset:0; margin:auto; width:20px; height:20px; display:none; border-radius:50%;
            border:2px solid rgba(255,255,255,.25); border-top-color:#fff; animation:upSpin .7s linear infinite }
        .up.is-busy .up-spinner { display:block }
        .up.is-busy .up-img, .up.is-busy .up-placeholder { opacity:.25 }
        @keyframes upSpin { to { transform:rotate(360deg) } }

        .up-body { flex:1 1 150px; min-width:0; display:flex; flex-direction:column; gap:2px }
        .up-title { font-size:13px; font-weight:600; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
        .up.is-new .up-title::after { content:'New'; margin-left:7px; padding:1px 7px; border-radius:99px; font-size:10px;
            font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--green); background:rgba(34,197,94,.14) }
        .up.is-removing .up-title { color:#f87171 }
        .up-meta { font-size:11.5px; color:var(--muted) }
        .up-msg { font-size:11.5px; line-height:1.4 }
        .up-msg:empty { display:none }
        .up-msg.is-warn { color:var(--gold) }
        .up-msg.is-error { color:#f87171 }

        .up-actions { display:flex; gap:6px; flex-shrink:0 }
        .up-actions .lucide-icon { width:13px; height:13px }
        .up [data-up-remove], .up [data-up-undo] { display:none }
        .up.is-current [data-up-remove] { display:inline-flex }
        .up.is-new [data-up-undo], .up.is-removing [data-up-undo] { display:inline-flex }

        .up-input { position:absolute; width:1px; height:1px; opacity:0; overflow:hidden; clip:rect(0 0 0 0); pointer-events:none }

        .up-saving { display:inline-block; width:15px; height:15px; border-radius:50%; border:2px solid rgba(255,255,255,.35);
            border-top-color:#fff; animation:upSpin .7s linear infinite }
    </style>
@endPushOnce

@pushOnce('scripts')
    <script>
        (function () {
            const RESIZABLE = ['image/jpeg', 'image/png', 'image/webp'];
            const LABELS = { 'image/png': 'PNG', 'image/jpeg': 'JPG', 'image/webp': 'WebP', 'image/svg+xml': 'SVG', 'image/gif': 'GIF' };
            const EXT = { 'image/png': 'png', 'image/jpeg': 'jpg', 'image/webp': 'webp' };

            const fmtBytes = b => b >= 1048576
                ? (b / 1048576).toFixed(1).replace(/\.0$/, '') + ' MB'
                : Math.max(1, Math.round(b / 1024)) + ' KB';

            const listFormats = types => {
                const names = types.map(t => LABELS[t] || t.split('/').pop().toUpperCase());
                return names.length > 1 ? names.slice(0, -1).join(', ') + ' or ' + names.at(-1) : names[0];
            };

            function loadImage(file) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => resolve(img);
                    img.onerror = reject;
                    img.src = URL.createObjectURL(file);
                });
            }

            // Scale down only. "cover" keeps the image at least WxH, "contain" fits it inside WxH.
            async function shrink(file, img, [maxW, maxH], mode) {
                const w = img.naturalWidth, h = img.naturalHeight;
                const scale = mode === 'cover' ? Math.max(maxW / w, maxH / h) : Math.min(maxW / w, maxH / h);
                if (!RESIZABLE.includes(file.type) || !(scale < 1)) return null;

                const canvas = document.createElement('canvas');
                canvas.width = Math.round(w * scale);
                canvas.height = Math.round(h * scale);
                const ctx = canvas.getContext('2d');
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                const blob = await new Promise(r => canvas.toBlob(r, file.type, 0.9));
                if (!blob || blob.size >= file.size) return null;

                const name = blob.type === file.type ? file.name
                    : file.name.replace(/\.[^.]+$/, '') + '.' + (EXT[blob.type] || 'png');
                return { file: new File([blob], name, { type: blob.type }), width: canvas.width, height: canvas.height };
            }

            function setup(root) {
                const input = root.querySelector('.up-input');
                const clear = root.querySelector('.up-clear');
                const img = root.querySelector('.up-img');
                const title = root.querySelector('.up-title');
                const meta = root.querySelector('.up-meta');
                const msg = root.querySelector('.up-msg');
                const chooseLabel = root.querySelector('[data-up-choose] span');

                const current = root.dataset.current;
                const types = input.accept.split(',').map(t => t.trim()).filter(Boolean);
                const resize = root.dataset.resize ? root.dataset.resize.split('x').map(Number) : null;
                const maxBytes = Number(root.dataset.maxKb) * 1024 || 0;
                const [rw, rh] = (root.dataset.ratio || '').split('/').map(Number);

                const state = { file: null, info: '', url: null, currentSize: '', busy: null, seq: 0 };
                root._up = state;

                if (current) {
                    const probe = new Image();
                    probe.onload = () => { state.currentSize = probe.naturalWidth + '×' + probe.naturalHeight; render(); };
                    probe.src = current;
                }

                function setMsg(text, tone) {
                    msg.textContent = text || '';
                    msg.className = 'up-msg' + (text ? ' is-' + tone : '');
                }

                function render() {
                    const mode = state.file ? 'new' : (clear?.checked ? 'removing' : (current ? 'current' : 'empty'));
                    root.classList.remove('is-new', 'is-removing', 'is-current', 'is-empty');
                    root.classList.add('is-' + mode);
                    root.classList.toggle('is-busy', !!state.busy);

                    const src = mode === 'new' ? state.url : (mode === 'empty' ? '' : current);
                    root.classList.toggle('has-image', !!src);
                    if (src && img.getAttribute('src') !== src) img.src = src;

                    if (state.busy) {
                        title.textContent = 'Preparing image…';
                    } else {
                        title.textContent = {
                            new: state.file?.name,
                            removing: 'Removed when you save',
                            current: 'Current image',
                            empty: 'Drop an image here or browse',
                        }[mode];
                    }
                    meta.textContent = mode === 'new' ? state.info
                        : mode === 'empty' ? root.dataset.hint
                        : state.currentSize;
                    chooseLabel.textContent = mode === 'empty' ? 'Browse' : 'Replace';
                }

                // Tell listeners (e.g. the landing-page preview) about the final file, not the raw pick.
                function emit(el) {
                    state.silent = true;
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                    state.silent = false;
                }

                function setFiles(file) {
                    try {
                        const dt = new DataTransfer();
                        if (file) dt.items.add(file);
                        input.files = dt.files;
                    } catch (e) {
                        if (!file) input.value = '';
                    }
                }

                function accept(file, w, h, note, from) {
                    if (state.url) URL.revokeObjectURL(state.url);
                    state.file = file;
                    state.url = URL.createObjectURL(file);
                    state.info = (w ? w + '×' + h + ' · ' : '') + fmtBytes(file.size) + (from ? ', scaled down from ' + from : '');
                    setFiles(file);
                    if (clear?.checked) {
                        clear.checked = false;
                        emit(clear);
                    }
                    render();
                    emit(input);
                    setMsg(note.text, note.tone);
                }

                function reject(text) {
                    setFiles(state.file);
                    render();
                    emit(input);
                    setMsg(text, 'error');
                }

                async function handle(file) {
                    const seq = ++state.seq;
                    if (!file) { reject(''); return; }

                    if (types.length && !types.includes(file.type)) {
                        reject(`${file.name} isn't a supported image. Use ${listFormats(types)}.`);
                        return;
                    }

                    let resolveBusy;
                    const busy = new Promise(r => resolveBusy = r);
                    state.busy = busy;
                    root.classList.remove('is-invalid');
                    setMsg('');
                    render();

                    try {
                        let out = file, w = 0, h = 0, note = {}, from = '';
                        if (file.type !== 'image/svg+xml') {
                            const image = await loadImage(file).catch(() => null);
                            URL.revokeObjectURL(image?.src || '');
                            if (seq !== state.seq) return;
                            if (!image) {
                                reject(`${file.name} can't be read as an image. Use ${listFormats(types)}.`);
                                return;
                            }
                            w = image.naturalWidth;
                            h = image.naturalHeight;

                            const small = resize ? await shrink(file, image, resize, root.dataset.mode) : null;
                            if (seq !== state.seq) return;
                            if (small) {
                                from = `${w}×${h} (${fmtBytes(file.size)})`;
                                out = small.file;
                                w = small.width;
                                h = small.height;
                            }

                            if (rw && rh && root.dataset.cropNote && Math.abs(w / h - rw / rh) / (rw / rh) > 0.03) {
                                note = { text: root.dataset.cropNote, tone: 'warn' };
                            } else if (resize && root.dataset.mode === 'cover' && (w < resize[0] || h < resize[1])) {
                                note = { text: `Smaller than ${resize[0]}×${resize[1]}, so it may look soft on screen.`, tone: 'warn' };
                            }
                        }

                        if (maxBytes && out.size > maxBytes) {
                            reject(`This image is ${fmtBytes(out.size)}. The limit is ${fmtBytes(maxBytes)}.`);
                            return;
                        }

                        accept(out, w, h, note, from);
                    } finally {
                        if (state.busy === busy) state.busy = null;
                        resolveBusy();
                        render();
                    }
                }

                input.addEventListener('change', e => {
                    if (state.silent) return;
                    e.stopPropagation();
                    handle(input.files[0] || null);
                });

                root.addEventListener('click', e => {
                    if (e.target === input || e.target.closest('[data-up-remove], [data-up-undo]')) return;
                    input.click();
                });

                root.querySelector('[data-up-remove]')?.addEventListener('click', () => {
                    clear.checked = true;
                    emit(clear);
                    setMsg('');
                    render();
                });

                root.querySelector('[data-up-undo]').addEventListener('click', () => {
                    state.seq++;
                    if (state.file) {
                        URL.revokeObjectURL(state.url);
                        state.file = null;
                        state.url = null;
                        setFiles(null);
                        emit(input);
                    } else if (clear?.checked) {
                        clear.checked = false;
                        emit(clear);
                    }
                    setMsg('');
                    render();
                });

                let depth = 0;
                root.addEventListener('dragenter', e => {
                    if (!e.dataTransfer.types.includes('Files')) return;
                    depth++;
                    root.classList.add('is-drag');
                });
                root.addEventListener('dragleave', () => {
                    if (--depth <= 0) { depth = 0; root.classList.remove('is-drag'); }
                });
                root.addEventListener('dragover', e => e.preventDefault());
                root.addEventListener('drop', e => {
                    e.preventDefault();
                    depth = 0;
                    root.classList.remove('is-drag');
                    if (e.dataTransfer.files[0]) handle(e.dataTransfer.files[0]);
                });

                render();
            }

            document.querySelectorAll('[data-up]').forEach(setup);

            // A file dropped next to a picker would otherwise open in the tab and lose the form.
            ['dragover', 'drop'].forEach(type => window.addEventListener(type, e => {
                if (e.dataTransfer?.types.includes('Files')) e.preventDefault();
            }));

            // Saving: wait for any image still being prepared, then show progress on the save button.
            document.querySelectorAll('form').forEach(form => {
                const pickers = [...form.querySelectorAll('[data-up]')];
                if (!pickers.length) return;
                const button = form.querySelector('button[type=submit]');
                const original = button?.innerHTML;

                form.addEventListener('submit', e => {
                    if (form.dataset.saving) { e.preventDefault(); return; }

                    const files = form.querySelectorAll('.up-input');
                    const count = [...files].filter(i => i.files.length).length;
                    const label = count ? `Uploading ${count} image${count > 1 ? 's' : ''}…` : 'Saving…';
                    const show = text => { if (button) button.innerHTML = `<span class="up-saving"></span> ${text}`; };

                    form.dataset.saving = '1';
                    setTimeout(() => { if (button) button.disabled = true; });

                    const pending = pickers.map(p => p._up?.busy).filter(Boolean);
                    if (!pending.length) { show(label); return; }

                    e.preventDefault();
                    show('Preparing images…');
                    Promise.allSettled(pending).then(() => {
                        const ready = [...files].filter(i => i.files.length).length;
                        show(ready ? `Uploading ${ready} image${ready > 1 ? 's' : ''}…` : 'Saving…');
                        form.submit();
                    });
                });

                // Coming back with the browser's Back button restores the page as it was left.
                window.addEventListener('pageshow', e => {
                    if (!e.persisted || !button) return;
                    delete form.dataset.saving;
                    button.disabled = false;
                    button.innerHTML = original;
                });
            });
        })();
    </script>
@endPushOnce
