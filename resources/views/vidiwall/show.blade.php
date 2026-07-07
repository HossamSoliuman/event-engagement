@php
    $frame = $event->frameConfig();
    $frameColor = $frame['frame_color'] ?: $event->primary_color;
    $frameInk = $frame['text_color'] ?: '#ffffff';
    $frameLogo = $event->frame_logo_url;
    $frameTopText = $frame['top_text'];
    $frameBottomText = $frame['bottom_text'] !== '' ? $frame['bottom_text'] : $frame['top_text'];
    $frameSideText = $frame['side_text'];
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->name }} — Vidiwall</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --p: {{ $event->primary_color }};
            --bg: {{ $event->secondary_color }};
            --acc: {{ $event->accent_color }};
            --on-p: {{ $event->readableInk($event->primary_color) }};
            --on-bg: {{ $event->readableInk($event->secondary_color) }};
            --frame-c: {{ $frameColor }};
            --frame-t: {{ $frameInk }};
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html,
        body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: var(--bg);
            color: var(--on-bg);
            font-family: 'DM Sans', sans-serif
        }

        .screen {
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-areas: "tl ftop tr" "fleft fmain fright" "bl fbot br";
            grid-template-rows: clamp(38px, 6.4vh, 78px) 1fr clamp(38px, 6.4vh, 78px);
            grid-template-columns: clamp(26px, 3vw, 54px) 1fr clamp(26px, 3vw, 54px);
            background: var(--frame-c)
        }

        /* ── Sponsor frame ─────────────────────────────────────────── */
        .fr-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(16px, 2.4vw, 36px);
            overflow: hidden;
            white-space: nowrap;
            padding: 0 20px;
            color: var(--frame-t);
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            letter-spacing: .22em;
            text-transform: uppercase;
            font-size: clamp(16px, 2.3vw, 38px)
        }

        .fr-top {
            grid-area: ftop
        }

        .fr-bottom {
            grid-area: fbot
        }

        .fr-bar img {
            height: 74%;
            width: auto;
            max-width: 16%;
            object-fit: contain;
            flex: 0 0 auto
        }

        .fr-rail {
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: var(--frame-t);
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            letter-spacing: .3em;
            text-transform: uppercase;
            font-size: clamp(14px, 1.7vw, 26px)
        }

        .fr-left {
            grid-area: fleft
        }

        .fr-right {
            grid-area: fright
        }

        .fr-rail span {
            writing-mode: vertical-rl
        }

        .fr-left span {
            transform: rotate(180deg)
        }

        /* ── Center stage ──────────────────────────────────────────── */
        .stage {
            grid-area: fmain;
            position: relative;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            min-width: 0;
            min-height: 0
        }

        /* ── Idle: title + QR ──────────────────────────────────────── */
        .idle {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: clamp(16px, 4vh, 56px);
            padding: clamp(18px, 4vh, 72px);
            text-align: center;
            max-width: 100%;
            max-height: 100%
        }

        .event-title {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(30px, 5.4vw, 86px);
            line-height: 1.05;
            letter-spacing: .01em;
            color: var(--on-bg);
            max-width: 100%;
            white-space: nowrap;
            flex: 0 0 auto
        }

        .qr-card {
            aspect-ratio: 1;
            height: min(58vh, 60vw);
            width: auto;
            max-width: 100%;
            max-height: 100%;
            flex: 0 1 auto;
            background: #fff;
            padding: clamp(12px, 2vw, 30px);
            border-radius: clamp(16px, 2vw, 28px);
            box-shadow: 0 0 0 6px var(--acc), 0 30px 80px rgba(0, 0, 0, .55)
        }

        .qr-card img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block
        }

        .qr-fallback {
            font-size: clamp(18px, 2.4vw, 34px);
            font-weight: 600;
            color: var(--acc);
            letter-spacing: 1px;
            word-break: break-all;
            max-width: 80%
        }

        /* ── Live media (photo / video) ────────────────────────────── */
        .stage-bg {
            position: absolute;
            inset: 0;
            z-index: 1;
            background-size: cover;
            background-position: center;
            filter: blur(40px) brightness(.35);
            transform: scale(1.1);
            transition: background-image .8s ease
        }

        .photo-frame {
            position: relative;
            z-index: 2;
            max-height: 90%;
            max-width: 92%;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 0 80px rgba(0, 0, 0, .8), 0 0 0 4px var(--p);
            animation: photoIn .5s cubic-bezier(.22, 1, .36, 1)
        }

        @keyframes photoIn {
            from {
                opacity: 0;
                transform: scale(.92)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        .photo-frame img {
            display: block;
            max-height: 88vh;
            max-width: 90vw;
            object-fit: contain
        }

        .video-frame {
            position: relative;
            z-index: 2;
            max-height: 90%;
            max-width: 92%;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 0 80px rgba(0, 0, 0, .8), 0 0 0 4px var(--p);
            animation: photoIn .5s cubic-bezier(.22, 1, .36, 1)
        }

        .video-frame video {
            display: block;
            max-height: 88vh;
            max-width: 90vw;
            object-fit: contain;
            background: #000
        }

        .uploader {
            position: absolute;
            bottom: 26px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, .75);
            border: 1px solid var(--acc);
            border-radius: 40px;
            padding: 8px 24px;
            font-family: 'Syne', sans-serif;
            font-size: clamp(14px, 1.6vw, 20px);
            font-weight: 700;
            color: var(--acc);
            z-index: 3;
            white-space: nowrap;
            animation: tagIn .4s .2s both
        }

        @keyframes tagIn {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(18px)
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0)
            }
        }

        .slide-counter {
            position: absolute;
            top: 14px;
            right: 18px;
            z-index: 4;
            background: rgba(0, 0, 0, .6);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, .7)
        }

        .slide-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: var(--p);
            z-index: 4;
            transition: width linear
        }

        .live-badge {
            position: absolute;
            top: 14px;
            left: 18px;
            z-index: 4;
            background: var(--p);
            color: var(--on-p);
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 14px;
            border-radius: 40px;
            letter-spacing: 2px;
            animation: pulse 2s infinite
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .55
            }
        }

        .video-badge {
            position: absolute;
            top: 14px;
            left: 18px;
            z-index: 4;
            background: rgba(99, 102, 241, .9);
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 14px;
            border-radius: 40px;
            letter-spacing: 2px
        }

        /* ── Fan Clash rope ────────────────────────────────────────── */
        .clash {
            position: absolute;
            inset: 0;
            z-index: 6;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: clamp(14px, 2.8vh, 40px);
            padding: clamp(26px, 5vw, 90px);
            background: var(--bg)
        }

        .clash-head {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between
        }

        .clash-brand {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(14px, 2vw, 32px);
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--on-bg);
            opacity: .9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .clash-live {
            flex: 0 0 auto;
            margin-left: 16px;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(11px, 1.3vw, 20px);
            letter-spacing: .2em;
            color: #fff;
            background: #ef4444;
            padding: 5px 18px;
            border-radius: 40px;
            animation: pulse 2s infinite
        }

        .clash-names {
            width: 100%;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px
        }

        .clash-name {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(26px, 5.4vw, 88px);
            letter-spacing: .01em;
            text-transform: uppercase;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 46%
        }

        .clash-name-a {
            color: var(--ca, #ef4444);
            text-align: left
        }

        .clash-name-b {
            color: var(--cb, #3B82F6);
            text-align: right
        }

        .clash-bar {
            width: 100%;
            height: clamp(42px, 7.5vh, 96px);
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .16)
        }

        .clash-fill-a {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 50%;
            background: var(--ca, #ef4444);
            transition: width .6s cubic-bezier(.4, 0, .2, 1)
        }

        .clash-fill-b {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 50%;
            background: var(--cb, #3B82F6);
            transition: width .6s cubic-bezier(.4, 0, .2, 1)
        }

        .clash-mid {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 2px;
            background: rgba(255, 255, 255, .35);
            transform: translateX(-50%);
            z-index: 2
        }

        .clash-knot {
            position: absolute;
            top: -7px;
            bottom: -7px;
            left: 50%;
            width: 6px;
            background: #fff;
            border-radius: 4px;
            transform: translateX(-50%);
            box-shadow: 0 0 22px rgba(255, 255, 255, .85);
            transition: left .6s cubic-bezier(.4, 0, .2, 1);
            z-index: 3
        }

        .clash-tps {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between
        }

        .clash-tps>div {
            display: flex;
            flex-direction: column
        }

        .clash-tps b {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(20px, 2.8vw, 46px);
            font-variant-numeric: tabular-nums;
            line-height: 1
        }

        .clash-tps span {
            font-size: clamp(10px, 1vw, 15px);
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--on-bg);
            opacity: .5;
            margin-top: 5px
        }

        .clash-tps-a {
            align-items: flex-start
        }

        .clash-tps-a b {
            color: var(--ca, #ef4444)
        }

        .clash-tps-b {
            align-items: flex-end
        }

        .clash-tps-b b {
            color: var(--cb, #3B82F6)
        }

        .clash-count {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(30px, 4.4vw, 72px);
            font-variant-numeric: tabular-nums;
            color: var(--on-bg);
            line-height: 1
        }

        .clash-hint {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: clamp(12px, 1.5vw, 24px);
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--on-bg);
            opacity: .45
        }

        .clash-winner {
            position: absolute;
            inset: 0;
            z-index: 8;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: clamp(10px, 2.6vh, 28px);
            padding: clamp(24px, 5vw, 80px);
            background: var(--bg);
            text-align: center;
            animation: clashWinIn .5s cubic-bezier(.22, 1, .36, 1)
        }

        @keyframes clashWinIn {
            from {
                opacity: 0;
                transform: scale(.96)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        .clash-winner-eyebrow {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: clamp(12px, 1.6vw, 26px);
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--on-bg);
            opacity: .55
        }

        .clash-winner-name {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(46px, 11vw, 170px);
            line-height: .95;
            text-transform: uppercase;
            color: var(--cwin, #fff);
            letter-spacing: -.01em
        }

        .clash-winner-tag {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(16px, 2.4vw, 42px);
            letter-spacing: .28em;
            text-transform: uppercase;
            color: var(--on-bg);
            opacity: .7
        }

        .clash-winner-spon {
            max-height: clamp(60px, 12vh, 150px);
            width: auto;
            max-width: 60%;
            background: #fff;
            border-radius: 14px;
            padding: 12px 22px;
            margin-top: 10px
        }
    </style>
</head>

<body>
    <div class="screen">

        <div class="fr-bar fr-top">
            @if ($frameLogo)
                <img src="{{ $frameLogo }}" alt="">
            @endif
            @if ($frameTopText)
                <span>{{ $frameTopText }}</span>
            @endif
            @if ($frameLogo && $frameTopText)
                <img src="{{ $frameLogo }}" alt="">
            @endif
        </div>

        <div class="fr-rail fr-left">
            @if ($frameSideText)
                <span>{{ $frameSideText }}</span>
            @endif
        </div>

        <div class="stage" id="stage">
            <div class="stage-bg" id="stageBg"></div>

            <div class="idle" id="idleState">
                <div class="event-title">{{ $event->name }}</div>
                @if ($event->qr_code_path)
                    <div class="qr-card">
                        <img src="{{ $event->qr_code_url }}" alt="QR code">
                    </div>
                @else
                    <div class="qr-fallback">{{ $event->getGuestUrl() }}</div>
                @endif
            </div>

            <div class="photo-frame" id="photoFrame" style="display:none">
                <img id="liveImg" src="" alt="">
            </div>

            <div class="video-frame" id="videoFrame" style="display:none">
                <video id="liveVideo" src="" autoplay muted playsinline></video>
            </div>

            <div class="uploader" id="uploaderTag" style="display:none">
                <span id="uploaderName"></span>
            </div>

            <div class="live-badge" id="liveBadge" style="display:none">&#9679; LIVE</div>
            <div class="video-badge" id="videoBadge" style="display:none">&#9654; VIDEO</div>
            <div class="slide-counter" id="slideCounter" style="display:none"></div>
            <div class="slide-progress" id="slideProgress" style="width:0;display:none"></div>

            @if ($event->module_fanclash)
                <div class="clash" id="clashOverlay" style="display:none">
                    <div class="clash-head">
                        <span class="clash-brand" id="clashBrand">FAN CLASH</span>
                        <span class="clash-live" id="clashLive">&#9679; LIVE</span>
                    </div>
                    <div class="clash-names">
                        <div class="clash-name clash-name-a" id="clashNameA">Side A</div>
                        <div class="clash-name clash-name-b" id="clashNameB">Side B</div>
                    </div>
                    <div class="clash-bar" id="clashBar">
                        <div class="clash-fill-a" id="clashFillA"></div>
                        <div class="clash-fill-b" id="clashFillB"></div>
                        <div class="clash-mid"></div>
                        <div class="clash-knot" id="clashKnot"></div>
                    </div>
                    <div class="clash-tps">
                        <div class="clash-tps-a"><b id="clashTapsA">0</b><span>taps</span></div>
                        <div class="clash-count" id="clashCount">20</div>
                        <div class="clash-tps-b"><b id="clashTapsB">0</b><span>taps</span></div>
                    </div>
                    <div class="clash-hint">Scan &middot; pick a side &middot; tap to win</div>

                    <div class="clash-winner" id="clashWinner" style="display:none">
                        <div class="clash-winner-eyebrow">Winner</div>
                        <div class="clash-winner-name" id="clashWinnerName">&mdash;</div>
                        <div class="clash-winner-tag" id="clashWinnerTag">wins</div>
                        <img class="clash-winner-spon" id="clashWinnerSpon" alt="Sponsor" style="display:none">
                    </div>
                </div>
            @endif
        </div>

        <div class="fr-rail fr-right">
            @if ($frameSideText)
                <span>{{ $frameSideText }}</span>
            @endif
        </div>

        <div class="fr-bar fr-bottom">
            @if ($frameLogo)
                <img src="{{ $frameLogo }}" alt="">
            @endif
            @if ($frameBottomText)
                <span>{{ $frameBottomText }}</span>
            @endif
            @if ($frameLogo && $frameBottomText)
                <img src="{{ $frameLogo }}" alt="">
            @endif
        </div>

    </div>

    <script>
        const SLUG = '{{ $event->slug }}';
        const SHOW_NAME = {{ $event->vidiwall_show_uploader ? 'true' : 'false' }};
        const HAS_FANCLASH = {{ $event->module_fanclash ? 'true' : 'false' }};

        let clashActive = false;
        let lastFotoId = null;
        let slideIndex = 0;
        let slideTimer = null;
        let slideFotos = [];

        function setBackground(url) {
            document.getElementById('stageBg').style.backgroundImage = url ? `url('${url}')` : '';
        }

        function showUploaderTag(name) {
            const upTag = document.getElementById('uploaderTag');
            const nameSp = document.getElementById('uploaderName');
            if (SHOW_NAME && name) {
                upTag.style.display = 'block';
                nameSp.textContent = name;
            } else {
                upTag.style.display = 'none';
            }
        }

        function showSlideInfo(slideNum, total) {
            const counter = document.getElementById('slideCounter');
            if (slideNum !== undefined) {
                counter.style.display = 'block';
                counter.textContent = `${slideNum} / ${total}`;
            } else {
                counter.style.display = 'none';
            }
        }

        function showPhoto(url, name, slideNum, total) {
            document.getElementById('idleState').style.display = 'none';
            document.getElementById('videoFrame').style.display = 'none';
            document.getElementById('videoBadge').style.display = 'none';

            const liveVideo = document.getElementById('liveVideo');
            liveVideo.pause();
            liveVideo.src = '';

            document.getElementById('liveBadge').style.display = 'block';

            showUploaderTag(name);
            showSlideInfo(slideNum, total);

            const frame = document.getElementById('photoFrame');
            const img = document.getElementById('liveImg');
            frame.style.display = 'none';
            img.src = url;
            img.onload = () => {
                frame.style.display = 'block';
                frame.style.animation = 'none';
                frame.offsetHeight;
                frame.style.animation = '';
            };
            setBackground(url);
        }

        function showVideo(videoUrl, name, slideNum, total, onEnded) {
            document.getElementById('idleState').style.display = 'none';
            document.getElementById('photoFrame').style.display = 'none';
            document.getElementById('liveBadge').style.display = 'none';

            document.getElementById('videoBadge').style.display = 'block';

            showUploaderTag(name);
            showSlideInfo(slideNum, total);

            const frame = document.getElementById('videoFrame');
            const liveVideo = document.getElementById('liveVideo');
            liveVideo.src = videoUrl;
            liveVideo.onended = onEnded || null;
            liveVideo.play().catch(() => {});
            frame.style.display = 'block';

            setBackground(null);
        }

        function showIdle() {
            document.getElementById('idleState').style.display = 'flex';
            if (window.fitVidiwall) { window.fitVidiwall(); }
            document.getElementById('photoFrame').style.display = 'none';
            document.getElementById('videoFrame').style.display = 'none';
            document.getElementById('uploaderTag').style.display = 'none';
            document.getElementById('liveBadge').style.display = 'none';
            document.getElementById('videoBadge').style.display = 'none';
            document.getElementById('slideCounter').style.display = 'none';
            document.getElementById('slideProgress').style.display = 'none';
            setBackground(null);
            lastFotoId = null;
            slideFotos = [];
            clearTimeout(slideTimer);
            document.getElementById('liveVideo').pause();
            document.getElementById('liveVideo').src = '';
        }

        function startSlideshow(fotos, interval) {
            slideFotos = fotos;
            if (!slideFotos.length) {
                showIdle();
                return;
            }
            slideIndex = 0;

            const progress = document.getElementById('slideProgress');
            const liveVideo = document.getElementById('liveVideo');
            progress.style.display = 'block';

            function nextSlide() {
                clearTimeout(slideTimer);

                let advanced = false;
                const goNext = () => {
                    if (advanced) return;
                    advanced = true;
                    clearTimeout(slideTimer);
                    liveVideo.onended = null;
                    slideIndex = (slideIndex + 1) % slideFotos.length;
                    nextSlide();
                };

                const f = slideFotos[slideIndex];

                if (f.media_type === 'video' && f.video_url) {
                    progress.style.transition = 'none';
                    progress.style.width = '0%';

                    showVideo(f.video_url, f.uploader, slideIndex + 1, slideFotos.length, goNext);

                    slideTimer = setTimeout(goNext, 60_000);
                } else {
                    liveVideo.onended = null;
                    liveVideo.pause();
                    liveVideo.src = '';

                    showPhoto(f.url, f.uploader, slideIndex + 1, slideFotos.length);

                    progress.style.transition = 'none';
                    progress.style.width = '0%';
                    progress.offsetHeight;
                    progress.style.transition = `width ${interval}s linear`;
                    progress.style.width = '100%';

                    slideTimer = setTimeout(goNext, interval * 1000);
                }
            }

            nextSlide();
        }

        async function poll() {
            if (clashActive) return;
            try {
                const res = await fetch(`/screen/${SLUG}/feed`);
                const data = await res.json();

                if (data.mode === 'slideshow') {
                    if (!data.fotos.length) {
                        showIdle();
                        return;
                    }
                    const newIds = data.fotos.map(f => f.id).join(',');
                    const oldIds = slideFotos.map(f => f.id).join(',');
                    if (newIds !== oldIds) {
                        clearTimeout(slideTimer);
                        startSlideshow(data.fotos, data.interval ?? 8);
                    }
                } else {
                    clearTimeout(slideTimer);
                    document.getElementById('slideProgress').style.display = 'none';

                    if (data.foto && data.foto.id !== lastFotoId) {
                        lastFotoId = data.foto.id;
                        if (data.foto.media_type === 'video' && data.foto.video_url) {
                            showVideo(data.foto.video_url, data.foto.uploader);
                        } else {
                            showPhoto(data.foto.url, data.foto.uploader);
                        }
                    } else if (!data.foto) {
                        showIdle();
                    }
                }
            } catch (e) {}
        }

        poll();
        setInterval(poll, 3000);

        /* ── Fan Clash rope: fast poll while a round is live ──────── */
        (function () {
            if (!HAS_FANCLASH) return;

            const overlay = document.getElementById('clashOverlay');
            if (!overlay) return;

            const el = id => document.getElementById(id);
            const brand = el('clashBrand'), live = el('clashLive'), countEl = el('clashCount'),
                nameA = el('clashNameA'), nameB = el('clashNameB'),
                fillA = el('clashFillA'), fillB = el('clashFillB'), knot = el('clashKnot'),
                tapsA = el('clashTapsA'), tapsB = el('clashTapsB'),
                winner = el('clashWinner'), winnerName = el('clashWinnerName'),
                winnerTag = el('clashWinnerTag'), winnerSpon = el('clashWinnerSpon');

            let timer = null;

            function ratio(a, b) {
                const t = a + b;
                return t <= 0 ? 0.5 : a / t;
            }

            function hideStage() {
                ['idleState', 'photoFrame', 'videoFrame', 'uploaderTag', 'liveBadge',
                    'videoBadge', 'slideCounter', 'slideProgress'].forEach(id => {
                    const n = document.getElementById(id);
                    if (n) n.style.display = 'none';
                });
                const v = document.getElementById('liveVideo');
                if (v) { v.pause(); }
            }

            function paintColors(d) {
                overlay.style.setProperty('--ca', d.side_a_color || '#ef4444');
                overlay.style.setProperty('--cb', d.side_b_color || '#3B82F6');
            }

            function render(d) {
                paintColors(d);
                brand.textContent = 'FAN CLASH' + (d.category ? ' · ' + d.category : '');
                nameA.textContent = d.side_a_name || 'Side A';
                nameB.textContent = d.side_b_name || 'Side B';
                tapsA.textContent = (d.side_a_taps ?? 0).toLocaleString();
                tapsB.textContent = (d.side_b_taps ?? 0).toLocaleString();

                let r;
                if (d.status === 'finished') {
                    r = d.winner_side === 'a' ? 1 : d.winner_side === 'b' ? 0 : 0.5;
                } else {
                    r = Math.max(0.04, Math.min(0.96, ratio(d.side_a_taps ?? 0, d.side_b_taps ?? 0)));
                }
                const pct = (r * 100).toFixed(2) + '%';
                fillA.style.width = pct;
                fillB.style.width = (100 - r * 100).toFixed(2) + '%';
                knot.style.left = pct;

                if (d.status === 'finished') {
                    countEl.textContent = '0';
                    live.textContent = '● FINAL';
                    live.style.background = 'rgba(255,255,255,.18)';
                    showWinner(d);
                } else {
                    countEl.textContent = Math.ceil((d.remaining_ms ?? 0) / 1000);
                    live.textContent = '● LIVE';
                    live.style.background = '#ef4444';
                    winner.style.display = 'none';
                }
            }

            function showWinner(d) {
                if (d.winner_side === 'tie') {
                    winnerName.textContent = "It's a tie";
                    winnerTag.style.display = 'none';
                    overlay.style.setProperty('--cwin', '#ffffff');
                } else {
                    winnerName.textContent = (d.winner_side === 'a' ? d.side_a_name : d.side_b_name) || '—';
                    winnerTag.style.display = '';
                    overlay.style.setProperty('--cwin', (d.winner_side === 'a' ? d.side_a_color : d.side_b_color) || '#ffffff');
                }
                if (d.sponsor_logo_url) {
                    winnerSpon.src = d.sponsor_logo_url;
                    winnerSpon.style.display = '';
                } else {
                    winnerSpon.style.display = 'none';
                }
                winner.style.display = 'flex';
            }

            function handle(d) {
                if (d.status === 'active' || d.status === 'finished') {
                    if (!clashActive) {
                        clashActive = true;
                        hideStage();
                        overlay.style.display = 'flex';
                    }
                    render(d);
                } else { // idle
                    if (clashActive) {
                        clashActive = false;
                        overlay.style.display = 'none';
                        winner.style.display = 'none';
                        lastFotoId = null;
                        slideFotos = [];
                        poll();
                    }
                }
            }

            function schedule(ms) {
                clearTimeout(timer);
                timer = setTimeout(tick, ms);
            }

            async function tick() {
                try {
                    const res = await fetch(`/screen/${SLUG}/clash/feed`);
                    const d = await res.json();
                    handle(d);
                    schedule(d.status === 'idle' ? 3000 : 700);
                } catch (e) {
                    schedule(3000);
                }
            }

            tick();
        })();
    </script>

    <script>
        (function () {
            function fit(container, vertical) {
                var span = container.querySelector('span');
                if (!span) {
                    return;
                }
                span.style.fontSize = '';
                var cs = getComputedStyle(container);
                var avail, needed;
                if (vertical) {
                    avail = container.clientHeight
                        - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
                    needed = span.getBoundingClientRect().height;
                } else {
                    var others = 0, count = 0;
                    for (var i = 0; i < container.children.length; i++) {
                        count++;
                        if (container.children[i] !== span) {
                            others += container.children[i].getBoundingClientRect().width;
                        }
                    }
                    var gap = parseFloat(cs.columnGap) || 0;
                    avail = container.clientWidth
                        - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight)
                        - others - (count > 1 ? gap * (count - 1) : 0);
                    needed = span.getBoundingClientRect().width;
                }
                if (needed > avail && avail > 0) {
                    var current = parseFloat(getComputedStyle(span).fontSize);
                    span.style.fontSize = Math.max(8, Math.floor(current * avail / needed)) + 'px';
                }
            }

            function fitTitle() {
                var title = document.querySelector('.event-title');
                var idle = document.getElementById('idleState');
                var stage = document.getElementById('stage');
                if (!title || !idle || !stage) {
                    return;
                }
                title.style.fontSize = '';
                if (idle.style.display === 'none' || idle.offsetParent === null) {
                    return;
                }
                var cs = getComputedStyle(idle);
                var avail = stage.clientWidth
                    - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
                var needed = title.scrollWidth;
                if (needed > avail && avail > 0) {
                    var current = parseFloat(getComputedStyle(title).fontSize);
                    title.style.fontSize = Math.max(14, Math.floor(current * avail / needed) - 1) + 'px';
                }
            }

            function fitAll() {
                document.querySelectorAll('.fr-bar').forEach(function (el) { fit(el, false); });
                document.querySelectorAll('.fr-rail').forEach(function (el) { fit(el, true); });
                fitTitle();
            }

            window.fitVidiwall = fitAll;

            window.addEventListener('resize', fitAll);
            window.addEventListener('load', fitAll);
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(fitAll);
            }
            fitAll();
        })();
    </script>
</body>

</html>
