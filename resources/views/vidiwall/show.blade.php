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
