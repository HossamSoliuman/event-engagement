<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="{{ $event->primary_color }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>{{ $event->name }}</title>
    <link {{-- Dynamic fonts from admin settings --}}
        @php
$fontH = $event->font_heading ?: 'Syne';
        $fontB = $event->font_body    ?: 'DM Sans'; @endphp <link
        href="https://fonts.googleapis.com/css2?family={{ urlencode($fontH) }}:wght@700;800&family={{ urlencode($fontB) }}:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --p: {{ $event->primary_color }};
            --bg: {{ $event->secondary_color }};
            --acc: {{ $event->accent_color }};
            --text: #f0f0f8;
            --muted: rgba(240, 240, 248, .55);
            --border: rgba(255, 255, 255, .12);
            --card: rgba(255, 255, 255, .07);
            --r: 18px;
            --font-h: '{{ $fontH }}', sans-serif;
            --font-b: '{{ $fontB }}', sans-serif;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html {
            -webkit-tap-highlight-color: transparent;
            height: 100%
        }

        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden
        }

        body {
            font-family: var(--font-b);
        }

        .land-title,
        .land-hero-text,
        .tile-name,
        .section-title,
        .mod-header-title,
        .btn-main,
        .success-state h3,
        .land-hashtag {
            font-family: var(--font-h);
        }

        #landing {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100dvh;
            background: var(--bg);
            background-image: radial-gradient(ellipse at 20% 0%, color-mix(in srgb, var(--p) 20%, transparent) 0%, transparent 55%), radial-gradient(ellipse at 80% 100%, color-mix(in srgb, var(--acc) 10%, transparent) 0%, transparent 50%)
        }

        .land-header {
            padding: max(env(safe-area-inset-top, 0px), 20px) 20px 0;
            text-align: center;
            flex-shrink: 0
        }

        .land-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 4px
        }

        .land-logo {
            height: 80px
        }

        .land-brand-divider {
            width: 1px;
            height: 22px;
            background: var(--border)
        }

        .land-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(15px, 5vw, 20px);
            font-weight: 800;
            letter-spacing: -.3px
        }

        .land-subtitle-tag {
            font-size: clamp(10px, 3vw, 12px);
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--muted)
        }

        .land-sponsor {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 20px;
            background: rgba(0, 0, 0, .2);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            flex-shrink: 0
        }

        .land-sponsor img {
            height: 20px;
            opacity: .65
        }

        .land-sponsor span {
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted)
        }

        .land-hero {
            padding: 16px 24px 10px;
            text-align: center;
            flex-shrink: 0
        }

        .land-hero-text {
            font-family: 'Syne', sans-serif;
            font-size: clamp(18px, 5.5vw, 26px);
            font-weight: 800;
            line-height: 1.25;
            color: var(--text)
        }

        .land-hero-sub {
            font-size: clamp(12px, 3.5vw, 14px);
            color: var(--muted);
            margin-top: 6px;
            line-height: 1.5
        }

        .tile-grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 10px 12px;
            padding-bottom: max(env(safe-area-inset-bottom, 12px), 16px)
        }

        .tile {
            position: relative;
            border-radius: var(--r);
            overflow: hidden;
            cursor: pointer;
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            min-height: 150px;
            transition: transform .18s ease;
            -webkit-tap-highlight-color: transparent;
            user-select: none
        }

        .tile:active {
            transform: scale(.96)
        }

        .tile-fotobomb {
            background: linear-gradient(160deg, color-mix(in srgb, var(--p) 65%, #000) 0%, color-mix(in srgb, var(--p) 35%, #000) 100%)
        }

        .tile-lottery {
            background: linear-gradient(160deg, #1a1040 0%, #0d0820 100%)
        }

        .tile-voting {
            background: linear-gradient(160deg, color-mix(in srgb, var(--acc) 28%, #111) 0%, color-mix(in srgb, var(--acc) 8%, #080808) 100%)
        }

        .tile-membership {
            background: linear-gradient(160deg, #071a07 0%, #030e03 100%)
        }

        .tile-sponsor {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 32px;
            display: flex;
            align-items: center;
            gap: 6px
        }

        .tile-sponsor img {
            height: 18px;
            max-width: 70px;
            object-fit: contain;
            filter: brightness(0) invert(1);
            opacity: .85
        }

        .tile-sponsor-name {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .55);
            line-height: 1
        }

        .tile-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -62%);
            font-size: 48px;
            opacity: .15;
            pointer-events: none;
            line-height: 1
        }

        .tile-body {
            position: relative;
            z-index: 2;
            padding: 9px 11px 11px;
            background: linear-gradient(to top, rgba(0, 0, 0, .72) 0%, transparent 100%)
        }

        .tile-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .5);
            margin-bottom: 3px;
            line-height: 1
        }

        .tile-name {
            font-family: 'Syne', sans-serif;
            font-size: clamp(14px, 4vw, 17px);
            font-weight: 800;
            line-height: 1.15;
            color: #fff
        }

        .tile-arrow {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, .14);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            z-index: 3
        }

        .tile::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--tile-accent, rgba(255, 255, 255, .2));
            z-index: 3
        }

        .tile-fotobomb {
            --tile-accent: var(--p)
        }

        .tile-lottery {
            --tile-accent: #6366f1
        }

        .tile-voting {
            --tile-accent: var(--acc)
        }

        .tile-membership {
            --tile-accent: #22c55e
        }

        .land-footer {
            text-align: center;
            padding: 8px 20px 16px;
            flex-shrink: 0
        }

        .land-hashtag {
            font-family: 'Syne', sans-serif;
            font-size: clamp(16px, 5vw, 22px);
            font-weight: 800;
            color: var(--text);
            opacity: .8
        }

        .land-footer-sub {
            font-size: 10px;
            color: var(--muted);
            margin-top: 3px
        }

        .lang-toggle {
            position: fixed;
            top: max(env(safe-area-inset-top, 0px), 10px);
            right: 12px;
            z-index: 200;
            display: flex;
            background: rgba(0, 0, 0, .45);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(8px)
        }

        .lang-btn {
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--muted);
            letter-spacing: .5px;
            transition: all .15s;
            font-family: 'DM Sans', sans-serif
        }

        .lang-btn.active {
            background: var(--p);
            color: #fff
        }

        .module-screen {
            display: none;
            flex-direction: column;
            min-height: 100dvh;
            background: var(--bg);
            background-image: radial-gradient(ellipse at 0% 0%, color-mix(in srgb, var(--p) 10%, transparent), transparent 50%)
        }

        .module-screen.open {
            display: flex
        }

        .mod-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: max(env(safe-area-inset-top, 0px), 16px) 16px 14px;
            border-bottom: 1px solid var(--border);
            background: rgba(0, 0, 0, .3);
            backdrop-filter: blur(12px);
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 50
        }

        .mod-back {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .1);
            border: none;
            color: var(--text);
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0
        }

        .mod-header-title {
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 800
        }

        .mod-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px 16px;
            padding-bottom: max(env(safe-area-inset-bottom, 0px), 28px)
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px
        }

        .section-desc {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
            margin-bottom: 20px
        }

        .glass {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--r);
            padding: 18px;
            margin-bottom: 14px
        }

        .upload-zone {
            border: 2px dashed rgba(255, 255, 255, .2);
            border-radius: var(--r);
            padding: 32px 16px;
            text-align: center;
            cursor: pointer;
            background: rgba(255, 255, 255, .02);
            position: relative;
            overflow: hidden;
            transition: all .2s
        }

        .upload-zone:hover {
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

        .uz-icon {
            font-size: 40px;
            margin-bottom: 10px
        }

        .upload-zone p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6
        }

        .upload-zone strong {
            color: var(--text)
        }

        .preview-img {
            width: 100%;
            max-height: 280px;
            object-fit: cover;
            border-radius: 12px;
            display: none;
            margin-bottom: 14px
        }

        .preview-img.show {
            display: block
        }

        .upload-progress {
            height: 4px;
            background: rgba(255, 255, 255, .1);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 14px;
            display: none
        }

        .upload-progress-fill {
            height: 100%;
            background: var(--p);
            width: 0%;
            transition: width .3s
        }

        .field-group {
            margin-bottom: 13px
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px
        }

        .btn-main:active {
            transform: scale(.98)
        }

        .btn-main:disabled {
            opacity: .45;
            cursor: not-allowed;
            transform: none
        }

        .success-state {
            text-align: center;
            padding: 40px 16px
        }

        .s-icon {
            font-size: 60px;
            margin-bottom: 16px
        }

        .success-state h3 {
            font-family: 'Syne', sans-serif;
            font-size: 24px;
            margin-bottom: 8px
        }

        .success-state p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5
        }

        .btn-again {
            margin-top: 22px;
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
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--p), var(--acc));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 19px;
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

        .check-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 0;
            cursor: pointer;
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 16px
        }

        .check-row input {
            accent-color: var(--p);
            width: 17px;
            height: 17px;
            cursor: pointer;
            flex-shrink: 0
        }

        #toast {
            position: fixed;
            bottom: max(env(safe-area-inset-bottom, 0px), 16px);
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

        .gdpr-box {
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 14px
        }

        .gdpr-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer
        }

        .gdpr-row input {
            accent-color: var(--p);
            width: 18px;
            height: 18px;
            margin-top: 2px;
            cursor: pointer;
            flex-shrink: 0
        }

        .gdpr-text {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.6
        }

        .gdpr-error {
            color: #f87171;
            font-size: 11px;
            margin-top: 6px;
            display: none
        }

        .media-toggle {
            display: flex;
            background: rgba(0,0,0,.3);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px
        }

        .media-toggle-btn {
            flex: 1;
            padding: 11px 8px;
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all .2s
        }

        .media-toggle-btn.active {
            background: var(--p);
            color: #fff
        }

        .preview-video {
            width: 100%;
            max-height: 280px;
            border-radius: 12px;
            display: none;
            margin-bottom: 14px;
            background: #000
        }

        .preview-video.show {
            display: block
        }

        .duration-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            margin-bottom: 10px;
            background: rgba(99,102,241,.15);
            color: #818cf8
        }

        .duration-badge.over {
            background: rgba(239,68,68,.15);
            color: #f87171
        }
    </style>
</head>

<body>
    {{-- make privacy text available to JS --}}
    <script>
        const PRIVACY_TEXT = @json(
            $event->privacy_policy_text ?:
            'I agree to the Privacy Policy and consent to my data being processed for this event.');
    </script>
    @php
        $privacyText =
            $event->privacy_policy_text ?:
            'I agree to the Privacy Policy and consent to my data being processed for this event.';
        $gdprSnippet = function (string $id) use ($privacyText, $privacyUrl) {
            $linkOpen =
                $privacyUrl !== '#'
                    ? '<a href="' .
                        e($privacyUrl) .
                        '" target="_blank" style="color:var(--p);text-decoration:underline;text-underline-offset:2px">'
                    : '<a href="#" onclick="return false" style="color:var(--p);text-decoration:underline;text-underline-offset:2px">';
            $linkClose = '</a>';

            // Wrap the words "Privacy Policy" / "Datenschutz" in the link if they appear
            $displayText = preg_replace(
                '/(Privacy Policy|Datenschutzerklärung|Datenschutz)/i',
                $linkOpen . '$1' . $linkClose,
                e($privacyText),
            );
            // If none matched, just append a standalone link
            if ($displayText === e($privacyText)) {
                $displayText = e($privacyText) . ' ' . $linkOpen . 'Privacy Policy' . $linkClose;
            }

            return '
<div class="gdpr-box" id="gdpr-box-' .
                $id .
                '">
    <label class="gdpr-row">
        <input type="checkbox" id="gdpr-' .
                $id .
                '">
        <span class="gdpr-text">' .
                $displayText .
                '</span>
    </label>
    <div class="gdpr-error" id="gdpr-err-' .
                $id .
                '"></div>
</div>';
        };
    @endphp
    <div class="lang-toggle">
        <button class="lang-btn active" onclick="setLang('en')">EN</button>
        <button class="lang-btn" onclick="setLang('de')">DE</button>
    </div>

    <!-- LANDING -->
    <div id="landing">
        <div class="land-header" style="display:flex;align-items:center;justify-content:center;padding:22px 18px 10px;">
            <div class="land-brand" style="display:flex;align-items:center;gap:16px;max-width:100%;">
                @if ($event->logo_path)
                    <img src="{{ $event->logo_url }}" class="land-logo"
                        style="height:64px;width:auto;object-fit:contain;display:block;" alt="">
                    <div class="land-brand-divider" style="width:1px;height:46px;opacity:.18;"></div>
                @endif
                <div>
                    <div class="land-title" style="font-size:22px;font-weight:800;line-height:1.2;">
                        {{ $event->name }}
                    </div>
                    <div class="land-subtitle-tag" style="font-size:13px;opacity:.7;margin-top:2px;">
                        {{ $event->subtitle }}
                    </div>
                </div>
            </div>
        </div>

        @if ($event->sponsor_logo_path)
            <div class="land-sponsor"
                style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;margin:18px 0 26px;text-align:center;">
                <span data-en="Sponsored by" data-de="Präsentiert von"
                    style="font-size:11px;letter-spacing:.12em;text-transform:uppercase;opacity:.6;font-weight:700;">
                    Sponsored by
                </span>
                <img src="{{ $event->sponsor_logo_url }}"
                    style="height:96px;max-width:80%;object-fit:contain;display:block;" alt="">
            </div>
        @endif

        <div class="land-hero">
            <div class="land-hero-text" data-en="Your Fan Experience starts here."
                data-de="Deine Fan Experience startet hier.">Your Fan Experience starts here.</div>
            <div class="land-hero-sub" data-en="Be part of the show. Tap a tile to get started."
                data-de="Sei Teil des Erlebnisses. Tippe auf eine Kachel.">Be part of the show. Tap a tile to get
                started.</div>
        </div>

        <div class="tile-grid">
            @foreach ([['fotobomb', '📸', $event->fotobomb_title, '#FF3D00'], ['voting', '🏆', $event->voting_title, '#FFD700'], ['lottery', '🎰', $event->lottery_title, '#6366f1'], ['membership', '⭐', $event->membership_title, '#22c55e']] as [$mod, $defaultIcon, $defaultName, $defaultAccent])
                @if (!$event->{'module_' . $mod})
                    @continue
                @endif

                @php
                    $tc = $event->tileConfig($mod);
                    $imgUrl = $event->getTileImageUrl($mod);
                    $bgColor = !empty($tc['bg_color']) ? $tc['bg_color'] : null;
                    $linkUrl = $tc['link_url'] ?? '';
                    $isLink = !empty($linkUrl);
                    $external = $tc['link_external'] ?? false;
                    $tileLabel = $tc['label'] ?: '';
                    $tileSublabel = $tc['sublabel'] ?: '';

                    $bgStyle = $bgColor
                        ? "background:{$bgColor}"
                        : match ($mod) {
                            'fotobomb'
                                => 'background:linear-gradient(160deg,color-mix(in srgb,var(--p) 65%,#000),color-mix(in srgb,var(--p) 35%,#000))',
                            'voting'
                                => 'background:linear-gradient(160deg,color-mix(in srgb,var(--acc) 28%,#111),color-mix(in srgb,var(--acc) 8%,#080808))',
                            'lottery' => 'background:linear-gradient(160deg,#1a1040,#080520)',
                            'membership' => 'background:linear-gradient(160deg,#071a07,#020d02)',
                        };

                    $accentStyle = match ($mod) {
                        'fotobomb' => 'background:var(--p)',
                        'voting' => 'background:var(--acc)',
                        'lottery' => 'background:#6366f1',
                        'membership' => 'background:#22c55e',
                    };
                @endphp

                <div class="tile" style="{{ $bgStyle }}"
                    onclick="{{ $isLink
                        ? "window.open('" . addslashes($linkUrl) . "','" . ($external ? '_blank' : '_self') . "')"
                        : "openModule('{$mod}')" }}">

                    <div style="position:absolute;bottom:0;left:0;right:0;height:3px;z-index:3;{{ $accentStyle }}">
                    </div>

                    <div class="tile-arrow">{{ $isLink ? '↗' : '↗' }}</div>

                    @if ($imgUrl)
                        <img src="{{ $imgUrl }}"
                            style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.5;pointer-events:none;filter:brightness(.85) contrast(1.05);"
                            alt="">
                    @else
                        <div class="tile-icon">{{ $defaultIcon }}</div>
                    @endif

                    @if ($tileLabel || $tileSublabel)
                        <div class="tile-sponsor"
                            style="position:absolute;top:10px;left:10px;display:flex;align-items:center;gap:8px;z-index:4;padding:6px 8px;backdrop-filter:blur(6px);">
                            <div>
                                @if ($tileLabel)
                                    <div class="tile-sponsor-name"
                                        style="font-size:10px;font-weight:800;color:rgba(255,255,255,.85);letter-spacing:.5px">
                                        {{ $tileLabel }}</div>
                                @endif
                                @if ($tileSublabel)
                                    <div
                                        style="font-size:9px;color:rgba(255,255,255,.45);letter-spacing:1px;text-transform:uppercase;margin-top:1px">
                                        {{ $tileSublabel }}</div>
                                @endif
                            </div>
                        </div>
                    @elseif($event->sponsor_logo_path && $mod === 'fotobomb')
                        <div class="tile-sponsor"
                            style="position:absolute;top:10px;left:10px;display:flex;align-items:center;gap:8px;z-index:4;padding:6px 8px;backdrop-filter:blur(6px);">
                            <img src="{{ $event->sponsor_logo_url }}"
                                style="height:26px;width:auto;object-fit:contain;display:block;" alt="">
                            <div class="tile-sponsor-name">Selfie Wall</div>
                        </div>
                    @elseif($event->logo_path && $mod === 'voting')
                        <div class="tile-sponsor"
                            style="position:absolute;top:10px;left:10px;display:flex;align-items:center;gap:8px;z-index:4;padding:6px 8px;backdrop-filter:blur(6px);">
                            <img src="{{ $event->logo_url }}"
                                style="height:26px;width:auto;object-fit:contain;display:block;" alt="">
                            <div class="tile-sponsor-name">Athlete of the Day</div>
                        </div>
                    @endif

                    <div class="tile-body">
                        <div class="tile-label">
                            {{ $isLink
                                ? '🔗 ' . __('Tap to visit')
                                : match ($mod) {
                                    'fotobomb' => $event->fotobomb_desc
                                        ? \Illuminate\Support\Str::limit($event->fotobomb_desc, 30)
                                        : 'Upload your photo',
                                    'voting' => $event->voting_desc ? \Illuminate\Support\Str::limit($event->voting_desc, 30) : 'Cast your vote',
                                    'lottery' => $event->lottery_desc
                                        ? \Illuminate\Support\Str::limit($event->lottery_desc, 30)
                                        : "Win tonight's prize",
                                    'membership' => $event->membership_desc
                                        ? \Illuminate\Support\Str::limit($event->membership_desc, 30)
                                        : 'Join the community',
                                } }}
                        </div>
                        <div class="tile-name">{{ $defaultName }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="land-footer">
            @if ($event->vidiwall_overlay_text)
                <div class="land-hashtag">{{ $event->vidiwall_overlay_text }}</div>
            @endif
            <div class="land-footer-sub">© {{ now()->year }} EventBomb</div>
        </div>
    </div>

    <!-- FOTO BOMB -->
    @if ($event->module_fotobomb)
        <div class="module-screen" id="screen-fotobomb">
            <div class="mod-header">
                <button class="mod-back" onclick="closeModule('fotobomb')">←</button>
                <div class="mod-header-title">{{ $event->fotobomb_title }}</div>
            </div>
            <div class="mod-body">
                <div id="fotoForm">
                    <p class="section-title">{{ $event->fotobomb_title }}</p>
                    <p class="section-desc" data-en="{{ $event->fotobomb_desc }}" data-de="{{ $event->fotobomb_desc }}">{{ $event->fotobomb_desc }}</p>

                    <div class="media-toggle">
                        <button class="media-toggle-btn active" id="togglePhoto" onclick="switchMedia('photo')" data-en="Photo" data-de="Foto">Photo</button>
                        <button class="media-toggle-btn" id="toggleVideo" onclick="switchMedia('video')" data-en="Video" data-de="Video">Video</button>
                    </div>

                    <div id="photoSection">
                        <img id="preview" class="preview-img" src="" alt="">
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" id="photoInput" accept="image/*" capture="environment">
                            <div class="uz-icon">&#128248;</div>
                            <p><strong data-en="Tap to take a photo" data-de="Foto aufnehmen">Tap to take a photo</strong><br>
                            <span data-en="or choose from your gallery" data-de="oder aus der Galerie wählen">or choose from your gallery</span><br>
                            <small style="color:var(--muted);font-size:11px">JPG · PNG · WEBP — max 10MB</small></p>
                        </div>
                    </div>

                    <div id="videoSection" style="display:none">
                        <video id="previewVideo" class="preview-video" controls></video>
                        <div id="durationBadge" class="duration-badge" style="display:none"></div>
                        <div class="upload-zone" id="uploadZoneVideo">
                            <input type="file" id="videoInput" accept="video/*" capture="environment">
                            <div class="uz-icon">&#127909;</div>
                            <p><strong data-en="Tap to record a video" data-de="Video aufnehmen">Tap to record a video</strong><br>
                            <span data-en="or choose from your gallery" data-de="oder aus der Galerie wählen">or choose from your gallery</span><br>
                            <small style="color:var(--muted);font-size:11px">MP4 · MOV · WebM — max 10 sec · max 25MB</small></p>
                        </div>
                        <div id="videoDurationError" style="display:none;color:#f87171;font-size:12px;margin-bottom:10px;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:8px"></div>
                    </div>

                    <div class="upload-progress" id="uploadProgress">
                        <div class="upload-progress-fill" id="uploadProgressFill"></div>
                    </div>

                    <div style="margin-top:16px">
                        <div class="field-group">
                            <label class="field-label" data-en="Your Name" data-de="Dein Name">Your Name</label>
                            <input type="text" class="field" id="fotoName" data-ph-en="e.g. Ahmed from Row D" data-ph-de="z.B. Max aus Reihe D" placeholder="e.g. Ahmed from Row D" autocomplete="name">
                        </div>
                        {!! $gdprSnippet('foto') !!}
                        <button class="btn-main" id="uploadBtn" onclick="submitFoto()" disabled>
                            <span id="uploadBtnText" data-en="Send to Vidiwall" data-de="Auf die Vidiwall">Send to Vidiwall</span>
                        </button>
                    </div>
                </div>
                <div class="success-state" id="fotoSuccess" style="display:none">
                    <div class="s-icon">&#127881;</div>
                    <h3 id="fotoSuccessTitle" data-en="Submitted!" data-de="Eingereicht!">Submitted!</h3>
                    <p data-en="Watch the big screen — you might be up next!" data-de="Schau auf die Leinwand — vielleicht bist du als nächstes dran!">Watch the big screen — you might be up next!</p>
                    <button class="btn-again" onclick="resetFoto()"><span data-en="Upload Another" data-de="Weiteres hochladen">Upload Another</span></button>
                </div>
            </div>
        </div>
    @endif

    <!-- LOTTERY -->
    @if ($event->module_lottery)
        <div class="module-screen" id="screen-lottery">
            <div class="mod-header">
                <button class="mod-back" onclick="closeModule('lottery')">←</button>
                <div class="mod-header-title">🎰 {{ $event->lottery_title }}</div>
            </div>
            <div class="mod-body">
                <div id="lotteryForm">
                    <p class="section-title">🎰 {{ $event->lottery_title }}</p>
                    <p class="section-desc">{{ $event->lottery_desc }}</p>
                    <div class="glass">
                        <div class="field-group"><label class="field-label" data-en="Full Name *"
                                data-de="Vollständiger Name *">Full Name *</label><input type="text"
                                class="field" id="lName" data-ph-en="Your full name"
                                data-ph-de="Dein vollständiger Name" placeholder="Your full name"
                                autocomplete="name"></div>
                        <div class="field-group"><label class="field-label" data-en="Phone Number *"
                                data-de="Telefonnummer *">Phone Number *</label><input type="tel" class="field"
                                id="lPhone" placeholder="+43 xxx xxx xxxx" autocomplete="tel"></div>
                        <div class="field-group"><label class="field-label" data-en="Email (optional)"
                                data-de="E-Mail (optional)">Email (optional)</label><input type="email"
                                class="field" id="lEmail" data-ph-en="your@email.com"
                                data-ph-de="deine@email.com" placeholder="your@email.com" autocomplete="email"></div>
                        @foreach ($event->lottery_extra_fields ?? [] as $field)
                            <div class="field-group">
                                <label
                                    class="field-label">{{ $field['label'] }}{{ $field['required'] ? ' *' : '' }}</label>
                                <input type="{{ $field['type'] }}" class="field extra-lottery-field"
                                    data-label="{{ $field['label'] }}"
                                    data-required="{{ $field['required'] ? '1' : '0' }}"
                                    placeholder="{{ $field['label'] }}" {{ $field['required'] ? 'required' : '' }}>
                            </div>
                        @endforeach
                        {!! $gdprSnippet('lottery') !!}
                        <button class="btn-main" onclick="submitLottery()">
                            <span data-en="🎰 Enter the Draw" data-de="🎰 Jetzt teilnehmen">🎰 Enter the Draw</span>
                        </button>
                    </div>
                </div>
                <div class="success-state" id="lotterySuccess" style="display:none">
                    <div class="s-icon">🎟</div>
                    <h3 data-en="You're In!" data-de="Du bist dabei!">You're In!</h3>
                    <p data-en="Good luck! The winner will be announced live."
                        data-de="Viel Glück! Der Gewinner wird live bekannt gegeben.">Good luck! The winner will be
                        announced live.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- VOTING -->
    @if ($event->module_voting)
        <div class="module-screen" id="screen-voting">
            <div class="mod-header">
                <button class="mod-back" onclick="closeModule('voting')">←</button>
                <div class="mod-header-title">🏆 {{ $event->voting_title }}</div>
            </div>
            <div class="mod-body">
                <p class="section-title">🏆 {{ $event->voting_title }}</p>
                <p class="section-desc">{{ $event->voting_desc }}</p>
                @if ($event->voting_closed)
                    <div
                        style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:14px;padding:28px;text-align:center">
                        <div style="font-size:40px;margin-bottom:12px">🔒</div>
                        <div style="font-weight:700;font-size:16px;margin-bottom:6px" data-en="Voting is closed"
                            data-de="Abstimmung beendet">Voting is closed</div>
                        <div style="font-size:13px;color:var(--muted)" data-en="Results will be announced shortly."
                            data-de="Ergebnisse folgen in Kürze.">Results will be announced shortly.</div>
                    </div>
                @else
                    <div class="vote-grid" id="voteGrid">
                        @foreach ($event->voting_options ?? [] as $i => $opt)
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
                        {!! $gdprSnippet('vote') !!}
                        <button class="btn-main" id="voteBtn" onclick="submitVote()"> <span
                                data-en="🗳️ Cast My Vote" data-de="🗳️ Abstimmen">🗳️ Cast My Vote</span>
                        </button>
                    </div>
                    <div class="success-state" id="voteSuccess" style="display:none">
                        <div class="s-icon">🏆</div>
                        <h3 data-en="Vote Recorded!" data-de="Stimme gezählt!">Vote Recorded!</h3>
                        <p data-en="Live results shown on the big screen."
                            data-de="Live-Ergebnisse auf der Leinwand.">Live results shown on the big screen.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- MEMBERSHIP -->
    @if ($event->module_membership)
        <div class="module-screen" id="screen-membership">
            <div class="mod-header">
                <button class="mod-back" onclick="closeModule('membership')">←</button>
                <div class="mod-header-title">⭐ {{ $event->membership_title }}</div>
            </div>
            <div class="mod-body">
                <div id="memberForm">
                    <p class="section-title">⭐ {{ $event->membership_title }}</p>
                    <p class="section-desc">{{ $event->membership_desc }}</p>
                    <div class="glass">
                        <div class="field-group"><label class="field-label" data-en="Full Name *"
                                data-de="Vollständiger Name *">Full Name *</label><input type="text"
                                class="field" id="mName" data-ph-en="Your full name"
                                data-ph-de="Dein vollständiger Name" placeholder="Your full name"
                                autocomplete="name"></div>
                        <div class="field-group"><label class="field-label" data-en="Email Address *"
                                data-de="E-Mail-Adresse *">Email Address *</label><input type="email"
                                class="field" id="mEmail" data-ph-en="your@email.com"
                                data-ph-de="deine@email.com" placeholder="your@email.com" autocomplete="email"></div>
                        <div class="field-group"><label class="field-label" data-en="Phone (optional)"
                                data-de="Telefon (optional)">Phone (optional)</label><input type="tel"
                                class="field" id="mPhone" placeholder="+43 xxx xxx xxxx" autocomplete="tel">
                        </div>
                        <div class="field-group"><label class="field-label" data-en="Favourite Team (optional)"
                                data-de="Lieblingsmannschaft (optional)">Favourite Team</label><input type="text"
                                class="field" id="mTeam" data-ph-en="e.g. Al Ahly" data-ph-de="z.B. SK Rapid"
                                placeholder="e.g. Al Ahly"></div>
                        @foreach ($event->membership_extra_fields ?? [] as $field)
                            <div class="field-group">
                                <label
                                    class="field-label">{{ $field['label'] }}{{ $field['required'] ? ' *' : '' }}</label>
                                <input type="{{ $field['type'] }}" class="field extra-membership-field"
                                    data-label="{{ $field['label'] }}"
                                    data-required="{{ $field['required'] ? '1' : '0' }}"
                                    placeholder="{{ $field['label'] }}" {{ $field['required'] ? 'required' : '' }}>
                            </div>
                        @endforeach
                        <label class="check-row"><input type="checkbox" id="mNewsletter"><span
                                data-en="Subscribe to news &amp; offers"
                                data-de="Newsletter &amp; Angebote erhalten">Subscribe to news &amp;
                                offers</span></label>
                        {!! $gdprSnippet('member') !!}
                        <button class="btn-main" onclick="submitMembership()"> <span data-en="⭐ Join Now"
                                data-de="⭐ Jetzt beitreten">⭐ Join Now</span></button>
                    </div>
                </div>
                <div class="success-state" id="memberSuccess" style="display:none">
                    <div class="s-icon">⭐</div>
                    <h3 data-en="Welcome to the Club!" data-de="Willkommen im Club!">Welcome to the Club!</h3>
                    <p data-en="Membership confirmed. Stay tuned for exclusive updates."
                        data-de="Mitgliedschaft bestätigt. Bleib dran für exklusive Updates.">Membership confirmed.</p>
                </div>
            </div>
        </div>
    @endif

    <div id="toast"></div>

    <script>
        const SLUG = '{{ $event->slug }}',
            CSRF = '{{ csrf_token() }}';
        let selCandidate = null,
            selFile = null,
            selVideo = null,
            selVideoDuration = null,
            currentMedia = 'photo';
        let lang = localStorage.getItem('eb_lang') || 'en';

        function setLang(l) {
            lang = l;
            localStorage.setItem('eb_lang', l);
            document.querySelectorAll('.lang-btn').forEach(b => b.classList.toggle('active', b.textContent === l
                .toUpperCase()));
            document.querySelectorAll('[data-en]').forEach(el => {
                if (el.tagName === 'INPUT') return;
                const v = el.dataset[l] || el.dataset.en;
                if (v !== undefined) el.textContent = v;
            });
            document.querySelectorAll('[data-ph-' + l + ']').forEach(el => {
                el.placeholder = el.dataset['ph' + l.charAt(0).toUpperCase() + l.slice(1)] || el.placeholder;
            });
        }
        setLang(lang);

        function openModule(n) {
            document.getElementById('landing').style.display = 'none';
            document.getElementById('screen-' + n)?.classList.add('open');
            window.scrollTo(0, 0);
        }

        function closeModule(n) {
            document.getElementById('screen-' + n)?.classList.remove('open');
            document.getElementById('landing').style.display = 'flex';
            window.scrollTo(0, 0);
        }

        function toast(msg, err = false) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'show' + (err ? ' err' : '');
            clearTimeout(t._t);
            t._t = setTimeout(() => t.className = '', 3500);
        }
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

        function switchMedia(type) {
            currentMedia = type;
            const isPhoto = type === 'photo';
            document.getElementById('photoSection').style.display = isPhoto ? '' : 'none';
            document.getElementById('videoSection').style.display = isPhoto ? 'none' : '';
            document.getElementById('togglePhoto').classList.toggle('active', isPhoto);
            document.getElementById('toggleVideo').classList.toggle('active', !isPhoto);
            selFile = null;
            selVideo = null;
            selVideoDuration = null;
            document.getElementById('uploadBtn').disabled = true;
            const prevImg = document.getElementById('preview');
            prevImg.classList.remove('show');
            document.getElementById('uploadZone').style.display = '';
            const prevVid = document.getElementById('previewVideo');
            prevVid.classList.remove('show');
            prevVid.src = '';
            document.getElementById('uploadZoneVideo').style.display = '';
            document.getElementById('durationBadge').style.display = 'none';
            document.getElementById('videoDurationError').style.display = 'none';
        }

        document.getElementById('photoInput')?.addEventListener('change', e => {
            const f = e.target.files[0];
            if (!f) return;
            selFile = f;
            const p = document.getElementById('preview');
            p.src = URL.createObjectURL(f);
            p.classList.add('show');
            document.getElementById('uploadZone').style.display = 'none';
            document.getElementById('uploadBtn').disabled = false;
        });

        document.getElementById('videoInput')?.addEventListener('change', e => {
            const f = e.target.files[0];
            if (!f) return;

            const tmpUrl = URL.createObjectURL(f);
            const tmpVid = document.createElement('video');
            tmpVid.preload = 'metadata';
            tmpVid.src = tmpUrl;
            tmpVid.onloadedmetadata = () => {
                URL.revokeObjectURL(tmpUrl);
                const dur = tmpVid.duration;
                const badge = document.getElementById('durationBadge');
                const errEl = document.getElementById('videoDurationError');

                badge.style.display = 'inline-block';
                badge.textContent = Math.round(dur * 10) / 10 + 's / 10s max';

                if (dur > 10) {
                    badge.classList.add('over');
                    errEl.textContent = lang === 'de'
                        ? 'Das Video ist zu lang. Maximal 10 Sekunden erlaubt.'
                        : 'Video is too long. Maximum 10 seconds allowed.';
                    errEl.style.display = 'block';
                    document.getElementById('uploadBtn').disabled = true;
                    selVideo = null;
                    selVideoDuration = null;
                    document.getElementById('videoInput').value = '';
                } else {
                    badge.classList.remove('over');
                    errEl.style.display = 'none';
                    selVideo = f;
                    selVideoDuration = dur;
                    const prevVid = document.getElementById('previewVideo');
                    prevVid.src = URL.createObjectURL(f);
                    prevVid.classList.add('show');
                    document.getElementById('uploadZoneVideo').style.display = 'none';
                    document.getElementById('uploadBtn').disabled = false;
                }
            };
            tmpVid.onerror = () => {
                const errEl = document.getElementById('videoDurationError');
                errEl.textContent = lang === 'de' ? 'Videodatei konnte nicht gelesen werden.' : 'Could not read video file.';
                errEl.style.display = 'block';
            };
        });

        function checkGdpr(id) {
            const cb = document.getElementById('gdpr-' + id);
            const err = document.getElementById('gdpr-err-' + id);
            const box = document.getElementById('gdpr-box-' + id);
            if (!cb.checked) {
                err.textContent = lang === 'de' ?
                    'Bitte stimme dem Datenschutz zu, um fortzufahren.' :
                    'Please accept the data protection agreement to continue.';
                err.style.display = 'block';
                box.style.borderColor = '#f87171';
                setTimeout(() => {
                    err.style.display = 'none';
                    box.style.borderColor = '';
                }, 3500);
                return false;
            }
            return true;
        }

        async function submitFoto() {
            if (currentMedia === 'photo' && !selFile) {
                return toast(lang === 'de' ? 'Bitte zuerst ein Foto aufnehmen.' : 'Please take a photo first.', true);
            }
            if (currentMedia === 'video' && !selVideo) {
                return toast(lang === 'de' ? 'Bitte zuerst ein Video aufnehmen.' : 'Please record a video first.', true);
            }
            if (!checkGdpr('foto')) return;
            const btn = document.getElementById('uploadBtn');
            btn.disabled = true;
            btn.innerHTML = '&#8987;';
            const prog = document.getElementById('uploadProgress'),
                fill = document.getElementById('uploadProgressFill');
            prog.style.display = 'block';
            fill.style.width = '30%';
            setTimeout(() => fill.style.width = '70%', 400);
            const fd = new FormData();
            if (currentMedia === 'video') {
                fd.append('video', selVideo);
                fd.append('video_duration', selVideoDuration);
            } else {
                fd.append('photo', selFile);
            }
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
                        setLang(lang);
                    } else {
                        toast(d.message || 'Upload failed.', true);
                        resetFotoBtn();
                    }
                }, 400);
            } catch {
                toast('Network error.', true);
                resetFotoBtn();
            }
        }

        async function submitLottery() {
            const name = document.getElementById('lName')?.value.trim();
            const phone = document.getElementById('lPhone')?.value.trim();
            if (!name || !phone) return toast(lang === 'de' ? 'Name und Telefon sind erforderlich.' :
                'Name and phone required.', true);
            if (!checkGdpr('lottery')) return;

            // Collect extra fields
            const extra = {};
            document.querySelectorAll('.extra-lottery-field').forEach(el => {
                if (el.dataset.required === '1' && !el.value.trim()) {
                    toast((lang === 'de' ? 'Pflichtfeld: ' : 'Required: ') + el.dataset.label, true);
                    throw new Error('validation');
                }
                extra[el.dataset.label] = el.value;
            });

            const d = await post(`/e/${SLUG}/lottery`, {
                name,
                phone,
                email: document.getElementById('lEmail')?.value,
                extra_fields: extra
            });
            if (d.success) {
                document.getElementById('lotteryForm').style.display = 'none';
                document.getElementById('lotterySuccess').style.display = 'block';
                setLang(lang);
            } else toast(d.message, true);
        }

        async function submitMembership() {
            const name = document.getElementById('mName')?.value.trim();
            const email = document.getElementById('mEmail')?.value.trim();
            if (!name || !email) return toast(lang === 'de' ? 'Name und E-Mail sind erforderlich.' :
                'Name and email required.', true);
            if (!checkGdpr('member')) return;

            // Collect extra fields
            const extra = {};
            document.querySelectorAll('.extra-membership-field').forEach(el => {
                if (el.dataset.required === '1' && !el.value.trim()) {
                    toast((lang === 'de' ? 'Pflichtfeld: ' : 'Required: ') + el.dataset.label, true);
                    throw new Error('validation');
                }
                extra[el.dataset.label] = el.value;
            });

            const d = await post(`/e/${SLUG}/membership`, {
                name,
                email,
                phone: document.getElementById('mPhone')?.value,
                team_preference: document.getElementById('mTeam')?.value,
                newsletter_opt_in: document.getElementById('mNewsletter')?.checked ? 1 : 0,
                extra_fields: extra,
            });
            if (d.success) {
                document.getElementById('memberForm').style.display = 'none';
                document.getElementById('memberSuccess').style.display = 'block';
                setLang(lang);
            } else toast(d.message, true);
        }

        function selectVote(card) {
            document.querySelectorAll('.vote-card').forEach(c => c.classList.remove('sel'));
            card.classList.add('sel');
            selCandidate = card.dataset.cand;
        }

        async function submitVote() {
            if (!selCandidate) return toast(lang === 'de' ? 'Bitte einen Athleten auswählen.' :
                'Please select an athlete.', true);
            if (!checkGdpr('vote')) return;
            const btn = document.getElementById('voteBtn');
            btn.disabled = true;
            const d = await post(`/e/${SLUG}/vote`, {
                candidate: selCandidate
            });
            if (d.success) {
                if (d.tallies) {
                    const total = Object.values(d.tallies).reduce((a, b) => a + b, 0);
                    document.querySelectorAll('.vote-card').forEach(c => {
                        const cnt = d.tallies[c.dataset.cand] || 0;
                        c.querySelector('.v-fill').style.width = (total > 0 ? Math.round((cnt / total) * 100) :
                            0) + '%';
                    });
                }
                document.getElementById('voteAction').style.display = 'none';
                document.getElementById('voteSuccess').style.display = 'block';
                setLang(lang);
            } else {
                toast(d.message, true);
                btn.disabled = false;
            }
        }




        function resetFotoBtn() {
            const btn = document.getElementById('uploadBtn');
            btn.disabled = false;
            const txt = document.getElementById('uploadBtnText');
            if (txt) txt.textContent = lang === 'de' ? 'Auf die Vidiwall' : 'Send to Vidiwall';
            document.getElementById('uploadProgress').style.display = 'none';
            document.getElementById('uploadProgressFill').style.width = '0%';
        }

        function resetFoto() {
            selFile = null;
            selVideo = null;
            selVideoDuration = null;
            document.getElementById('fotoForm').style.display = 'block';
            document.getElementById('fotoSuccess').style.display = 'none';
            document.getElementById('preview').classList.remove('show');
            document.getElementById('uploadZone').style.display = '';
            document.getElementById('uploadBtn').disabled = true;
            document.getElementById('photoInput').value = '';
            const prevVid = document.getElementById('previewVideo');
            if (prevVid) { prevVid.classList.remove('show'); prevVid.src = ''; }
            const vidInput = document.getElementById('videoInput');
            if (vidInput) vidInput.value = '';
            const uploadZoneVideo = document.getElementById('uploadZoneVideo');
            if (uploadZoneVideo) uploadZoneVideo.style.display = '';
            const badge = document.getElementById('durationBadge');
            if (badge) badge.style.display = 'none';
            const errEl = document.getElementById('videoDurationError');
            if (errEl) errEl.style.display = 'none';
            switchMedia('photo');
            resetFotoBtn();
        }
    </script>
</body>

</html>
