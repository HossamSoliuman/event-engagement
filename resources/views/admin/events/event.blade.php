<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="{{ $event->primary_color }}">
    <title>{{ $event->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary: {{ $event->primary_color }};
            --bg: {{ $event->secondary_color }};
            --accent: {{ $event->accent_color }};
            --surface: color-mix(in srgb, var(--primary) 8%, var(--bg));
            --text: #f0f0f8;
            --muted: rgba(240, 240, 248, .55);
            --border: rgba(240, 240, 248, .1);
            --radius: 16px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Header */
        .event-header {
            background: linear-gradient(160deg, color-mix(in srgb, var(--primary) 30%, var(--bg)) 0%, var(--bg) 100%);
            padding: 40px 20px 28px;
            text-align: center;
            border-bottom: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .event-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 50% 0%, color-mix(in srgb, var(--primary) 20%, transparent), transparent 70%);
        }

        .logo-wrap {
            position: relative;
            z-index: 1;
        }

        .logo-wrap img {
            height: 50px;
            margin-bottom: 16px;
        }

        .event-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(24px, 7vw, 34px);
            font-weight: 800;
            line-height: 1.1;
            position: relative;
            z-index: 1;
        }

        .event-subtitle {
            color: var(--muted);
            margin-top: 6px;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }

        /* Nav pills */
        .module-nav {
            display: flex;
            gap: 8px;
            padding: 16px;
            overflow-x: auto;
            scrollbar-width: none;
            background: rgba(0, 0, 0, .2);
            border-bottom: 1px solid var(--border);
        }

        .module-nav::-webkit-scrollbar {
            display: none;
        }

        .nav-pill {
            flex-shrink: 0;
            padding: 8px 18px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted);
            transition: all .2s;
            white-space: nowrap;
        }

        .nav-pill.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        /* Sections */
        .section {
            display: none;
            padding: 20px 16px;
            animation: fadeIn .3s ease;
        }

        .section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .section-desc {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        /* Cards */
        .glass-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 14px;
        }

        /* Upload zone */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s;
            background: rgba(255, 255, 255, .03);
            position: relative;
            overflow: hidden;
        }

        .upload-zone.drag-over {
            border-color: var(--primary);
            background: rgba(255, 255, 255, .06);
        }

        .upload-zone input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .upload-zone p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .preview-img {
            width: 100%;
            max-height: 280px;
            object-fit: cover;
            border-radius: 12px;
            display: none;
            margin-bottom: 14px;
        }

        .preview-img.shown {
            display: block;
        }

        /* Form */
        .form-group {
            margin-bottom: 14px;
        }

        label.field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        input.field,
        textarea.field,
        select.field {
            width: 100%;
            background: rgba(0, 0, 0, .3);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px;
            color: var(--text);
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            -webkit-appearance: none;
            transition: border-color .15s;
        }

        input.field:focus,
        textarea.field:focus {
            outline: none;
            border-color: var(--primary);
        }

        input.field::placeholder {
            color: var(--muted);
        }

        /* Buttons */
        .btn-main {
            width: 100%;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Syne', sans-serif;
            cursor: pointer;
            transition: all .2s;
            letter-spacing: .3px;
        }

        .btn-main:hover {
            filter: brightness(1.1);
        }

        .btn-main:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .btn-main.loading::after {
            content: ' <i data-lucide="hourglass" class="lucide-icon"></i>';
        }

        /* Vote cards */
        .vote-card {
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .vote-card:hover,
        .vote-card.selected {
            border-color: var(--primary);
            background: rgba(255, 61, 0, .08);
        }

        .vote-card .athlete-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .vote-card .athlete-name {
            font-weight: 700;
            font-size: 16px;
        }

        .vote-bar {
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            margin-top: 6px;
            overflow: hidden;
        }

        .vote-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 3px;
            transition: width .6s ease;
        }

        /* Toast */
        #toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #1a2a1a;
            border: 1px solid #4ade80;
            color: #4ade80;
            padding: 14px 24px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 14px;
            z-index: 999;
            transition: transform .3s ease;
            white-space: nowrap;
            max-width: 90vw;
            text-align: center;
        }

        #toast.show {
            transform: translateX(-50%) translateY(0);
        }

        #toast.error {
            background: #2a1a1a;
            border-color: #f87171;
            color: #f87171;
        }

        /* Sponsor bar */
        .sponsor-bar {
            padding: 16px;
            text-align: center;
            border-top: 1px solid var(--border);
            margin-top: 32px;
        }

        .sponsor-bar img {
            height: 32px;
            opacity: .6;
        }

        .sponsor-bar p {
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>

    
    <div class="event-header">
        <div class="logo-wrap">
            @if ($event->logo_path)
                <img src="{{ Storage::disk('public')->url($event->logo_path) }}" alt="{{ $event->name }}">
            @endif
            <h1 class="event-title">{{ $event->name }}</h1>
            <p class="event-subtitle">{{ $event->subtitle }}</p>
        </div>
    </div>

    
    <div class="module-nav" id="moduleNav">
        @if ($event->module_fotobomb)
            <button class="nav-pill active" data-section="fotobomb"><i data-lucide="camera" class="lucide-icon"></i> {{ $event->fotobomb_title }}</button>
        @endif
        @if ($event->module_lottery)
            <button class="nav-pill" data-section="lottery"><i data-lucide="ticket" class="lucide-icon"></i> {{ $event->lottery_title }}</button>
        @endif
        @if ($event->module_voting)
            <button class="nav-pill" data-section="voting"><i data-lucide="trophy" class="lucide-icon"></i> {{ $event->voting_title }}</button>
        @endif
        @if ($event->module_membership)
            <button class="nav-pill" data-section="membership"><i data-lucide="star" class="lucide-icon"></i> {{ $event->membership_title }}</button>
        @endif
    </div>

    
    @if ($event->module_fotobomb)
        <div class="section active" id="fotobomb">
            <h2 class="section-title"><i data-lucide="camera" class="lucide-icon"></i> {{ $event->fotobomb_title }}</h2>
            <p class="section-desc">Snap a photo and we might put it on the big screen! 🎬</p>

            <img id="preview" class="preview-img" src="" alt="Preview">

            <div class="upload-zone" id="uploadZone">
                <input type="file" id="photoInput" accept="image/*" capture="environment">
                <div class="upload-icon"><i data-lucide="camera" class="lucide-icon"></i></div>
                <p><strong>Tap to take a photo</strong><br>or choose from your gallery<br><small>JPG, PNG, WEBP — max
                        10MB</small></p>
            </div>

            <div style="margin-top:16px;">
                <div class="form-group">
                    <label class="field-label">Your Name</label>
                    <input type="text" class="field" id="fotoName" placeholder="e.g. Ahmed from Section B">
                </div>
                <div class="form-group">
                    <label class="field-label">Phone (optional)</label>
                    <input type="tel" class="field" id="fotoPhone" placeholder="+20 1xx xxx xxxx">
                </div>
                <button class="btn-main" id="uploadBtn" onclick="submitFoto()" disabled>
                     Send to Vidiwall
                </button>
            </div>
        </div>
    @endif

    
    @if ($event->module_lottery)
        <div class="section" id="lottery">
            <h2 class="section-title"><i data-lucide="ticket" class="lucide-icon"></i> {{ $event->lottery_title }}</h2>
            <p class="section-desc">Enter your details for a chance to win tonight's prize! Winner announced live. <i data-lucide="trophy" class="lucide-icon"></i>
            </p>

            <div class="glass-card">
                <div class="form-group">
                    <label class="field-label">Full Name *</label>
                    <input type="text" class="field" id="lotteryName" placeholder="Your full name">
                </div>
                <div class="form-group">
                    <label class="field-label">Phone Number *</label>
                    <input type="tel" class="field" id="lotteryPhone" placeholder="+20 1xx xxx xxxx">
                </div>
                <div class="form-group">
                    <label class="field-label">Email (optional)</label>
                    <input type="email" class="field" id="lotteryEmail" placeholder="your@email.com">
                </div>
                <button class="btn-main" onclick="submitLottery()"><i data-lucide="ticket" class="lucide-icon"></i> Enter the Draw</button>
            </div>
        </div>
    @endif

    
    @if ($event->module_voting)
        <div class="section" id="voting">
            <h2 class="section-title"><i data-lucide="trophy" class="lucide-icon"></i> {{ $event->voting_title }}</h2>
            <p class="section-desc">Cast your vote! Results are shown live on the big screen.</p>

            @php $options = $event->voting_options ?? []; @endphp
            @foreach ($options as $i => $option)
                <div class="vote-card" data-candidate="{{ $option['name'] }}" onclick="selectVote(this)">
                    <div class="athlete-avatar">{{ strtoupper(substr($option['name'], 0, 1)) }}</div>
                    <div style="flex:1;">
                        <div class="athlete-name">{{ $option['name'] }}</div>
                        <div class="vote-bar">
                            <div class="vote-bar-fill" id="bar-{{ $i }}" style="width:0%"></div>
                        </div>
                    </div>
                    <div style="width:20px; height:20px; border-radius:50%; border:2px solid var(--border); flex-shrink:0;"
                        class="vote-dot"></div>
                </div>
            @endforeach

            <button class="btn-main" style="margin-top:16px;" onclick="submitVote()">🗳️ Cast My Vote</button>
        </div>
    @endif

    
    @if ($event->module_membership)
        <div class="section" id="membership">
            <h2 class="section-title"><i data-lucide="star" class="lucide-icon"></i> {{ $event->membership_title }}</h2>
            <p class="section-desc">Join the community and stay connected with exclusive updates, events, and offers.
            </p>

            <div class="glass-card">
                <div class="form-group">
                    <label class="field-label">Full Name *</label>
                    <input type="text" class="field" id="memberName" placeholder="Your full name">
                </div>
                <div class="form-group">
                    <label class="field-label">Email Address *</label>
                    <input type="email" class="field" id="memberEmail" placeholder="your@email.com">
                </div>
                <div class="form-group">
                    <label class="field-label">Phone (optional)</label>
                    <input type="tel" class="field" id="memberPhone" placeholder="+20 1xx xxx xxxx">
                </div>
                <div class="form-group">
                    <label class="field-label">Favourite Team (optional)</label>
                    <input type="text" class="field" id="memberTeam" placeholder="e.g. Al Ahly">
                </div>
                <label
                    style="display:flex; align-items:center; gap:10px; margin-bottom:16px; color:var(--muted); font-size:14px; cursor:pointer;">
                    <input type="checkbox" id="newsletterOpt"
                        style="accent-color:var(--primary); width:18px; height:18px;">
                    Subscribe to newsletter & updates
                </label>
                <button class="btn-main" onclick="submitMembership()"><i data-lucide="star" class="lucide-icon"></i> Join Now</button>
            </div>
        </div>
    @endif

    
    @if ($event->sponsor_logo_path)
        <div class="sponsor-bar">
            <p>Sponsored by</p>
            <img src="{{ Storage::disk('public')->url($event->sponsor_logo_path) }}" alt="Sponsor">
        </div>
    @endif

    
    <div id="toast"></div>

    <script>
        const SLUG = '{{ $event->slug }}';
        const CSRF = '{{ csrf_token() }}';
        let selectedCandidate = null;
        let selectedFile = null;

        // ── Navigation ──────────────────────────────────────────────────────────────
        document.querySelectorAll('.nav-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                document.querySelectorAll('.nav-pill').forEach(p => p.classList.remove('active'));
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                pill.classList.add('active');
                document.getElementById(pill.dataset.section)?.classList.add('active');
            });
        });

        // ── Toast ───────────────────────────────────────────────────────────────────
        function showToast(msg, isError = false) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'show' + (isError ? ' error' : '');
            setTimeout(() => t.className = '', 3500);
        }

        // ── POST helper ─────────────────────────────────────────────────────────────
        async function post(url, body, isFormData = false) {
            const headers = {
                'X-CSRF-TOKEN': CSRF
            };
            if (!isFormData) headers['Content-Type'] = 'application/json';
            const res = await fetch(url, {
                method: 'POST',
                headers,
                body: isFormData ? body : JSON.stringify(body),
            });
            return res.json();
        }

        // ── Foto Bomb ────────────────────────────────────────────────────────────────
        const photoInput = document.getElementById('photoInput');
        if (photoInput) {
            photoInput.addEventListener('change', e => {
                const file = e.target.files[0];
                if (!file) return;
                selectedFile = file;
                const preview = document.getElementById('preview');
                preview.src = URL.createObjectURL(file);
                preview.classList.add('shown');
                document.getElementById('uploadZone').style.display = 'none';
                document.getElementById('uploadBtn').disabled = false;
            });
        }

        async function submitFoto() {
            if (!selectedFile) return showToast('Please select a photo first.', true);
            const btn = document.getElementById('uploadBtn');
            btn.disabled = true;
            btn.textContent = '<i data-lucide="hourglass" class="lucide-icon"></i> Uploading...';

            const fd = new FormData();
            fd.append('photo', selectedFile);
            fd.append('uploader_name', document.getElementById('fotoName')?.value || '');
            fd.append('uploader_phone', document.getElementById('fotoPhone')?.value || '');
            fd.append('_token', CSRF);

            try {
                const data = await post(`/e/${SLUG}/foto/upload`, fd, true);
                if (data.success) {
                    showToast(data.message);
                    // Reset
                    selectedFile = null;
                    document.getElementById('preview').classList.remove('shown');
                    document.getElementById('uploadZone').style.display = '';
                    btn.textContent = ' Send to Vidiwall';
                    btn.disabled = true;
                } else {
                    showToast(data.message || 'Upload failed.', true);
                    btn.disabled = false;
                    btn.textContent = ' Send to Vidiwall';
                }
            } catch {
                showToast('Network error. Please try again.', true);
                btn.disabled = false;
                btn.textContent = ' Send to Vidiwall';
            }
        }

        // ── Lottery ──────────────────────────────────────────────────────────────────
        async function submitLottery() {
            const name = document.getElementById('lotteryName')?.value.trim();
            const phone = document.getElementById('lotteryPhone')?.value.trim();
            if (!name || !phone) return showToast('Name and phone are required.', true);

            const data = await post(`/e/${SLUG}/lottery`, {
                name,
                phone,
                email: document.getElementById('lotteryEmail')?.value,
            });
            showToast(data.message, !data.success);
        }

        // ── Voting ────────────────────────────────────────────────────────────────────
        function selectVote(card) {
            document.querySelectorAll('.vote-card').forEach(c => {
                c.classList.remove('selected');
                c.querySelector('.vote-dot').style.background = '';
                c.querySelector('.vote-dot').style.borderColor = '';
            });
            card.classList.add('selected');
            card.querySelector('.vote-dot').style.background = 'var(--primary)';
            card.querySelector('.vote-dot').style.borderColor = 'var(--primary)';
            selectedCandidate = card.dataset.candidate;
        }

        async function submitVote() {
            if (!selectedCandidate) return showToast('Please select an athlete first.', true);
            const data = await post(`/e/${SLUG}/vote`, {
                candidate: selectedCandidate
            });
            showToast(data.message, !data.success);

            if (data.tallies) {
                const total = Object.values(data.tallies).reduce((a, b) => a + b, 0);
                document.querySelectorAll('.vote-card').forEach((card, i) => {
                    const name = card.dataset.candidate;
                    const count = data.tallies[name] || 0;
                    const pct = total > 0 ? Math.round((count / total) * 100) : 0;
                    card.querySelector('.vote-bar-fill').style.width = pct + '%';
                });
            }
        }

        // ── Membership ────────────────────────────────────────────────────────────────
        async function submitMembership() {
            const name = document.getElementById('memberName')?.value.trim();
            const email = document.getElementById('memberEmail')?.value.trim();
            if (!name || !email) return showToast('Name and email are required.', true);

            const data = await post(`/e/${SLUG}/membership`, {
                name,
                email,
                phone: document.getElementById('memberPhone')?.value,
                team_preference: document.getElementById('memberTeam')?.value,
                newsletter_opt_in: document.getElementById('newsletterOpt')?.checked ? 1 : 0,
            });
            showToast(data.message, !data.success);
        }
    </script>
</body>

</html>
