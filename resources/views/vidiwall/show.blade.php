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
            grid-template-rows: clamp(54px, 9.5vh, 116px) 1fr clamp(54px, 9.5vh, 116px);
            grid-template-columns: clamp(26px, 3vw, 54px) 1fr clamp(26px, 3vw, 54px);
            background: var(--frame-c)
        }

        /* ── Sponsor frame ─────────────────────────────────────────── */
        .fr-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 42px;
            overflow: hidden;
            white-space: nowrap;
            padding: 0 24px;
            color: var(--frame-t);
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            letter-spacing: .22em;
            text-transform: uppercase;
            font-size: clamp(22px, 2.9vw, 46px)
        }

        .fr-top {
            grid-area: ftop
        }

        .fr-bottom {
            grid-area: fbot
        }

        .fr-bar img {
            height: 80%;
            max-height: 96px;
            width: auto;
            object-fit: contain
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
            background: var(--bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: clamp(16px, 4vh, 56px);
            padding: clamp(18px, 4vh, 72px);
            text-align: center;
            min-width: 0;
            min-height: 0;
            overflow: hidden
        }

        .event-title {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(30px, 5.4vw, 86px);
            line-height: 1.05;
            letter-spacing: .01em;
            color: var(--on-bg);
            max-width: 100%;
            overflow-wrap: break-word;
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

        <div class="stage">
            <div class="event-title">{{ $event->name }}</div>
            @if ($event->qr_code_path)
                <div class="qr-card">
                    <img src="{{ $event->qr_code_url }}" alt="QR code">
                </div>
            @else
                <div class="qr-fallback">{{ $event->getGuestUrl() }}</div>
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

            function fitAll() {
                document.querySelectorAll('.fr-bar').forEach(function (el) { fit(el, false); });
                document.querySelectorAll('.fr-rail').forEach(function (el) { fit(el, true); });
            }

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
