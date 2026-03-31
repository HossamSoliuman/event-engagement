<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="{{ $event->primary_color }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>{{ $event->name }}</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --p: {{ $event->primary_color }};
            --bg: {{ $event->secondary_color }};
            --acc: {{ $event->accent_color }};
            --surface: color-mix(in srgb, var(--p) 7%, var(--bg));
            --card: color-mix(in srgb, #fff 5%, var(--bg));
            --border: rgba(255, 255, 255, .1);
            --text: #f0f0f8;
            --muted: rgba(240, 240, 248, .5);
            --r: 14px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html {
            scroll-behavior: smooth;
            -webkit-tap-highlight-color: transparent
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            background-image: radial-gradient(ellipse at 0% 0%, color-mix(in srgb, var(--p) 12%, transparent), transparent 50%)
        }

        /* Header */
        .hdr {
            background: linear-gradient(180deg, color-mix(in srgb, var(--p) 20%, var(--bg)), var(--bg));
            padding: env(safe-area-inset-top, 0) 0 0;
            text-align: center;
            position: relative;
            overflow: hidden
        }

        .hdr::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 50% -20%, color-mix(in srgb, var(--p) 25%, transparent), transparent 70%);
            pointer-events: none
        }

        .hdr-inner {
            padding: 32px 20px 20px;
            position: relative;
            z-index: 1
        }

        .hdr-logo {
            height: 48px;
            margin-bottom: 12px
        }

        .hdr-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(22px, 7vw, 30px);
            font-weight: 800;
            line-height: 1.1
        }

        .hdr-sub {
            color: var(--muted);
            font-size: 13px;
            margin-top: 5px
        }

        /* Sponsor */
        .sponsor-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(0, 0, 0, .2);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border)
        }

        .sponsor-strip img {
            height: 24px;
            opacity: .65
        }

        .sponsor-strip span {
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted)
        }

        /* Nav */
        .nav-bar {
            display: flex;
            gap: 6px;
            padding: 12px 14px;
            overflow-x: auto;
            scrollbar-width: none;
            background: rgba(0, 0, 0, .15);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px)
        }

        .nav-bar::-webkit-scrollbar {
            display: none
        }

        .nav-pill {
            flex-shrink: 0;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            transition: all .2s;
            white-space: nowrap;
            font-family: 'DM Sans', sans-serif
        }

        .nav-pill.active {
            background: var(--p);
            border-color: var(--p);
            color: #fff;
            box-shadow: 0 4px 14px color-mix(in srgb, var(--p) 40%, transparent)
        }

        /* Sections */
        .section {
            display: none;
            padding: 16px;
            animation: sectionIn .25s ease
        }

        .section.active {
            display: block
        }

        @keyframes sectionIn {
            from {
                opacity: 0;
                transform: translateY(6px)
            }

            to {
                opacity: 1;
                transform: none
            }
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 21px;
            font-weight: 800;
            margin-bottom: 5px
        }

        .section-desc {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
            margin-bottom: 18px
        }

        /* Glass card */
        .glass {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--r);
            padding: 16px;
            margin-bottom: 12px
        }

        /* Upload zone */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: var(--r);
            padding: 28px 16px;
            text-align: center;
            cursor: pointer;
            background: rgba(255, 255, 255, .02);
            position: relative;
            overflow: hidden;
            transition: all .2s
        }

        .upload-zone:hover,
        .upload-zone.drag-over {
            border-color: var(--p);
            background: rgba(255, 255, 255, .04)
        }

        .upload-zone input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            font-size: 0
        }

        .upload-zone .uz-icon {
            font-size: 38px;
            margin-bottom: 10px
        }

        .upload-zone p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5
        }

        .upload-zone strong {
            color: var(--text)
        }

        .preview-img {
            width: 100%;
            max-height: 260px;
            object-fit: cover;
            border-radius: 10px;
            display: none;
            margin-bottom: 12px
        }

        .preview-img.show {
            display: block
        }

        /* Form fields */
        .field-group {
            margin-bottom: 12px
        }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 5px
        }

        .field {
            width: 100%;
            background: rgba(0, 0, 0, .3);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px;
            color: var(--text);
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            -webkit-appearance: none;
            transition: border-color .15s
        }

        .field:focus {
            outline: none;
            border-color: var(--p)
        }

        .field::placeholder {
            color: var(--muted)
        }

        /* Main button */
        .btn-main {
            width: 100%;
            background: var(--p);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Syne', sans-serif;
            cursor: pointer;
            transition: all .2s;
            letter-spacing: .2px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px
        }

        .btn-main:hover {
            filter: brightness(1.1)
        }

        .btn-main:active {
            transform: scale(.98)
        }

        .btn-main:disabled {
            opacity: .45;
            cursor: not-allowed;
            transform: none
        }

        /* Success state */
        .success-state {
            text-align: center;
            padding: 32px 16px;
            animation: sectionIn .3s ease
        }

        .success-state .s-icon {
            font-size: 56px;
            margin-bottom: 14px
        }

        .success-state h3 {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            margin-bottom: 8px
        }

        .success-state p {
            color: var(--muted);
            font-size: 14px
        }

        .btn-again {
            margin-top: 20px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 24px;
            color: var(--text);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif
        }

        /* Vote cards */
        .vote-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 16px
        }

        .vote-card {
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(255, 255, 255, .02)
        }

        .vote-card:active {
            transform: scale(.98)
        }

        .vote-card.sel {
            border-color: var(--p);
            background: color-mix(in srgb, var(--p) 10%, transparent)
        }

        .v-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--p), var(--acc));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 18px;
            flex-shrink: 0
        }

        .v-name {
            font-weight: 700;
            font-size: 15px
        }

        .v-pos {
            font-size: 12px;
            color: var(--muted)
        }

        .v-bar {
            height: 5px;
            background: rgba(255, 255, 255, .1);
            border-radius: 3px;
            margin-top: 6px;
            overflow: hidden
        }

        .v-fill {
            height: 100%;
            background: var(--p);
            border-radius: 3px;
            transition: width .5s ease;
            width: 0%
        }

        .v-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid var(--border);
            flex-shrink: 0;
            margin-left: auto;
            transition: all .2s
        }

        .vote-card.sel .v-dot {
            background: var(--p);
            border-color: var(--p)
        }

        /* Toast */
        #toast {
            position: fixed;
            bottom: calc(env(safe-area-inset-bottom, 0px)+16px);
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: #1a2a1a;
            border: 1px solid #22c55e;
            color: #4ade80;
            padding: 12px 22px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            z-index: 999;
            transition: transform .3s ease;
            white-space: nowrap;
            max-width: 90vw;
            text-align: center;
            pointer-events: none
        }

        #toast.show {
            transform: translateX(-50%) translateY(0)
        }

        #toast.err {
            background: #2a1212;
            border-color: #ef4444;
            color: #f87171
        }

        /* Upload progress bar */
        .upload-progress {
            height: 4px;
            background: rgba(255, 255, 255, .1);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 12px;
            display: none
        }

        .upload-progress-fill {
            height: 100%;
            background: var(--p);
            width: 0%;
            transition: width .3s;
            border-radius: 2px
        }

        /* Checkbox */
        .check-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 0;
            cursor: pointer;
            color: var(--muted);
            font-size: 13px
        }

        .check-row input {
            accent-color: var(--p);
            width: 17px;
            height: 17px;
            cursor: pointer;
            flex-shrink: 0
        }
    </style>
</head>

<body>

    {{-- Header --}}
    <div class="hdr">
        <div class="hdr-inner">
            @if ($event->logo_path)
                <img src="{{ $event->logo_url }}" class="hdr-logo" alt="{{ $event->name }}">
            @endif
            <h1 class="hdr-title">{{ $event->name }}</h1>
            <p class="hdr-sub">{{ $event->subtitle }}</p>
        </div>
    </div>

    {{-- Sponsor --}}
    @if ($event->sponsor_logo_path)
        <div class="sponsor-strip">
            <span>Sponsored by</span>
            <img src="{{ $event->sponsor_logo_url }}" alt="Sponsor">
        </div>
    @endif

    {{-- Nav --}}
    <nav class="nav-bar" id="navBar">
        @if ($event->module_fotobomb)
            <button class="nav-pill active" data-sec="fotobomb">📷 {{ $event->fotobomb_title }}</button>
        @endif
        @if ($event->module_lottery)
            <button class="nav-pill {{ !$event->module_fotobomb ? 'active' : '' }}" data-sec="lottery">🎰
                {{ $event->lottery_title }}</button>
        @endif
        @if ($event->module_voting)
            <button class="nav-pill" data-sec="voting">🏆 {{ $event->voting_title }}</button>
        @endif
        @if ($event->module_membership)
            <button class="nav-pill" data-sec="membership">⭐ {{ $event->membership_title }}</button>
        @endif
    </nav>

    {{-- ── FOTO BOMB ── --}}
    @if ($event->module_fotobomb)
        <div class="section active" id="fotobomb">
            <div id="fotoForm">
                <p class="section-title">📷 {{ $event->fotobomb_title }}</p>
                <p class="section-desc">{{ $event->fotobomb_desc }}</p>

                <img id="preview" class="preview-img" src="" alt="Preview">

                <div class="upload-zone" id="uploadZone">
                    <input type="file" id="photoInput" accept="image/*" capture="environment">
                    <div class="uz-icon">📸</div>
                    <p><strong>Tap to take a photo</strong><br>or choose from your gallery<br><small
                            style="color:var(--muted);font-size:11px">JPG · PNG · WEBP — max 10MB</small></p>
                </div>

                <div class="upload-progress" id="uploadProgress">
                    <div class="upload-progress-fill" id="uploadProgressFill"></div>
                </div>

                <div style="margin-top:14px">
                    <div class="field-group">
                        <label class="field-label">Your Name</label>
                        <input type="text" class="field" id="fotoName" placeholder="e.g. Ahmed from Row D"
                            autocomplete="name">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Phone (optional)</label>
                        <input type="tel" class="field" id="fotoPhone" placeholder="+20 1xx xxx xxxx"
                            autocomplete="tel">
                    </div>
                    <button class="btn-main" id="uploadBtn" onclick="submitFoto()" disabled>
                        🚀 Send to Vidiwall
                    </button>
                </div>
            </div>

            <div class="success-state" id="fotoSuccess" style="display:none">
                <div class="s-icon">🎉</div>
                <h3>Photo Submitted!</h3>
                <p>Watch the big screen — you might be up next!</p>
                <button class="btn-again" onclick="resetFoto()">📷 Upload Another</button>
            </div>
        </div>
    @endif

    {{-- ── LOTTERY ── --}}
    @if ($event->module_lottery)
        <div class="section" id="lottery">
            <div id="lotteryForm">
                <p class="section-title">🎰 {{ $event->lottery_title }}</p>
                <p class="section-desc">{{ $event->lottery_desc }}</p>
                <div class="glass">
                    <div class="field-group">
                        <label class="field-label">Full Name *</label>
                        <input type="text" class="field" id="lName" placeholder="Your full name"
                            autocomplete="name">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Phone Number *</label>
                        <input type="tel" class="field" id="lPhone" placeholder="+20 1xx xxx xxxx"
                            autocomplete="tel">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Email (optional)</label>
                        <input type="email" class="field" id="lEmail" placeholder="your@email.com"
                            autocomplete="email">
                    </div>
                    <button class="btn-main" onclick="submitLottery()">🎰 Enter the Draw</button>
                </div>
            </div>
            <div class="success-state" id="lotterySuccess" style="display:none">
                <div class="s-icon">🎟</div>
                <h3>You're In!</h3>
                <p>Good luck! The winner will be announced live.</p>
            </div>
        </div>
    @endif

    {{-- ── VOTING ── --}}
    @if ($event->module_voting)
        <div class="section" id="voting">
            <p class="section-title">🏆 {{ $event->voting_title }}</p>
            <p class="section-desc">{{ $event->voting_desc }}</p>

            @if ($event->voting_closed)
                <div
                    style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:12px;padding:24px;text-align:center">
                    <div style="font-size:36px;margin-bottom:10px">🔒</div>
                    <div style="font-weight:700;margin-bottom:6px">Voting is closed</div>
                    <div class="text-muted" style="font-size:13px;color:var(--muted)">Thank you for participating!
                        Results will be announced shortly.</div>
                </div>
            @else
                <div class="vote-grid" id="voteGrid">
                    @php $opts = $event->voting_options ?? [] @endphp
                    @foreach ($opts as $i => $opt)
                        <div class="vote-card" data-cand="{{ $opt['name'] }}" onclick="selectVote(this)">
                            <div class="v-avatar">{{ strtoupper(substr($opt['name'], 0, 1)) }}</div>
                            <div style="flex:1;min-width:0">
                                <div class="v-name">{{ $opt['name'] }}</div>
                                @if ($opt['position'] ?? null)
                                    <div class="v-pos">{{ $opt['position'] }}</div>
                                @endif
                                <div class="v-bar">
                                    <div class="v-fill" id="vbar-{{ $i }}"></div>
                                </div>
                            </div>
                            <div class="v-dot"></div>
                        </div>
                    @endforeach
                </div>
                <div id="voteAction">
                    <button class="btn-main" id="voteBtn" onclick="submitVote()" style="margin-bottom:6px">🗳️
                        Cast My Vote</button>
                </div>
                <div class="success-state" id="voteSuccess" style="display:none">
                    <div class="s-icon">🏆</div>
                    <h3>Vote Recorded!</h3>
                    <p>Live results are shown on the big screen.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- ── MEMBERSHIP ── --}}
    @if ($event->module_membership)
        <div class="section" id="membership">
            <div id="memberForm">
                <p class="section-title">⭐ {{ $event->membership_title }}</p>
                <p class="section-desc">{{ $event->membership_desc }}</p>
                <div class="glass">
                    <div class="field-group">
                        <label class="field-label">Full Name *</label>
                        <input type="text" class="field" id="mName" placeholder="Your full name"
                            autocomplete="name">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Email Address *</label>
                        <input type="email" class="field" id="mEmail" placeholder="your@email.com"
                            autocomplete="email">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Phone (optional)</label>
                        <input type="tel" class="field" id="mPhone" placeholder="+20 1xx xxx xxxx"
                            autocomplete="tel">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Favourite Team (optional)</label>
                        <input type="text" class="field" id="mTeam" placeholder="e.g. Al Ahly">
                    </div>
                    <label class="check-row" style="margin-bottom:16px">
                        <input type="checkbox" id="mNewsletter"> Subscribe to news &amp; exclusive offers
                    </label>
                    <button class="btn-main" onclick="submitMembership()">⭐ Join Now</button>
                </div>
            </div>
            <div class="success-state" id="memberSuccess" style="display:none">
                <div class="s-icon">⭐</div>
                <h3>Welcome to the Club!</h3>
                <p>Membership confirmed. Stay tuned for exclusive updates.</p>
            </div>
        </div>
    @endif

    <div id="toast"></div>

    <script>
        const SLUG = '{{ $event->slug }}';
        const CSRF = '{{ csrf_token() }}';
        let selCandidate = null,
            selFile = null;

        // ── Nav ──────────────────────────────────────────────────────────────────────
        document.querySelectorAll('.nav-pill').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.nav-pill').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.sec)?.classList.add('active');
            });
        });

        // ── Toast ─────────────────────────────────────────────────────────────────────
        function toast(msg, err = false) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'show' + (err ? ' err' : '');
            clearTimeout(t._t);
            t._t = setTimeout(() => t.className = '', 3500);
        }

        // ── POST helper ───────────────────────────────────────────────────────────────
        async function post(url, body, fd = false) {
            const h = {
                'X-CSRF-TOKEN': CSRF
            };
            if (!fd) h['Content-Type'] = 'application/json';
            const r = await fetch(url, {
                method: 'POST',
                headers: h,
                body: fd ? body : JSON.stringify(body)
            });
            return r.json();
        }

        // ── Foto Bomb ─────────────────────────────────────────────────────────────────
        document.getElementById('photoInput')?.addEventListener('change', e => {
            const f = e.target.files[0];
            if (!f) return;
            selFile = f;
            const prev = document.getElementById('preview');
            prev.src = URL.createObjectURL(f);
            prev.classList.add('show');
            document.getElementById('uploadZone').style.display = 'none';
            document.getElementById('uploadBtn').disabled = false;
        });

        async function submitFoto() {
            if (!selFile) return toast('Please take a photo first.', true);
            const btn = document.getElementById('uploadBtn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Uploading…';
            const progress = document.getElementById('uploadProgress');
            const fill = document.getElementById('uploadProgressFill');
            progress.style.display = 'block';
            fill.style.width = '30%';
            setTimeout(() => fill.style.width = '70%', 400);

            const fd = new FormData();
            fd.append('photo', selFile);
            fd.append('uploader_name', document.getElementById('fotoName')?.value || '');
            fd.append('uploader_phone', document.getElementById('fotoPhone')?.value || '');
            fd.append('_token', CSRF);

            try {
                const d = await post(`/e/${SLUG}/foto/upload`, fd, true);
                fill.style.width = '100%';
                setTimeout(() => {
                    if (d.success) {
                        document.getElementById('fotoForm').style.display = 'none';
                        document.getElementById('fotoSuccess').style.display = 'block';
                    } else {
                        toast(d.message || 'Upload failed.', true);
                        resetFotoBtn();
                    }
                }, 400);
            } catch {
                toast('Network error. Please try again.', true);
                resetFotoBtn();
            }
        }

        function resetFotoBtn() {
            const btn = document.getElementById('uploadBtn');
            btn.disabled = false;
            btn.innerHTML = '🚀 Send to Vidiwall';
            document.getElementById('uploadProgress').style.display = 'none';
            document.getElementById('uploadProgressFill').style.width = '0%';
        }

        function resetFoto() {
            selFile = null;
            document.getElementById('fotoForm').style.display = 'block';
            document.getElementById('fotoSuccess').style.display = 'none';
            document.getElementById('preview').classList.remove('show');
            document.getElementById('uploadZone').style.display = '';
            document.getElementById('uploadBtn').disabled = true;
            document.getElementById('uploadBtn').innerHTML = '🚀 Send to Vidiwall';
            document.getElementById('photoInput').value = '';
        }

        // ── Lottery ───────────────────────────────────────────────────────────────────
        async function submitLottery() {
            const name = document.getElementById('lName')?.value.trim();
            const phone = document.getElementById('lPhone')?.value.trim();
            if (!name || !phone) return toast('Name and phone are required.', true);
            const d = await post(`/e/${SLUG}/lottery`, {
                name,
                phone,
                email: document.getElementById('lEmail')?.value
            });
            if (d.success) {
                document.getElementById('lotteryForm').style.display = 'none';
                document.getElementById('lotterySuccess').style.display = 'block';
            } else {
                toast(d.message, true);
            }
        }

        // ── Voting ────────────────────────────────────────────────────────────────────
        function selectVote(card) {
            document.querySelectorAll('.vote-card').forEach(c => c.classList.remove('sel'));
            card.classList.add('sel');
            selCandidate = card.dataset.cand;
        }

        async function submitVote() {
            if (!selCandidate) return toast('Please select an athlete first.', true);
            const btn = document.getElementById('voteBtn');
            btn.disabled = true;
            const d = await post(`/e/${SLUG}/vote`, {
                candidate: selCandidate
            });
            if (d.success) {
                if (d.tallies) {
                    const total = Object.values(d.tallies).reduce((a, b) => a + b, 0);
                    document.querySelectorAll('.vote-card').forEach((card, i) => {
                        const c = card.dataset.cand;
                        const cnt = d.tallies[c] || 0;
                        const pct = total > 0 ? Math.round((cnt / total) * 100) : 0;
                        card.querySelector('.v-fill').style.width = pct + '%';
                    });
                }
                document.getElementById('voteAction').style.display = 'none';
                document.getElementById('voteSuccess').style.display = 'block';
            } else {
                toast(d.message, true);
                btn.disabled = false;
            }
        }

        // ── Membership ────────────────────────────────────────────────────────────────
        async function submitMembership() {
            const name = document.getElementById('mName')?.value.trim();
            const email = document.getElementById('mEmail')?.value.trim();
            if (!name || !email) return toast('Name and email are required.', true);
            const d = await post(`/e/${SLUG}/membership`, {
                name,
                email,
                phone: document.getElementById('mPhone')?.value,
                team_preference: document.getElementById('mTeam')?.value,
                newsletter_opt_in: document.getElementById('mNewsletter')?.checked ? 1 : 0,
            });
            if (d.success) {
                document.getElementById('memberForm').style.display = 'none';
                document.getElementById('memberSuccess').style.display = 'block';
            } else {
                toast(d.message, true);
            }
        }
    </script>
</body>

</html>
