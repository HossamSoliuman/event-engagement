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
    @if ($event->hasQrScreenCallToAction() && ($brandFonts = $event->googleFontsUrl()))
        <link href="{{ $brandFonts }}" rel="stylesheet">
    @endif
    @if ($event->hasVidiwallBackground())
        <link rel="preload" as="image" href="{{ $event->vidiwall_background_url }}">
    @endif
    @if ($event->vidiwall_qr_background_url)
        <link rel="preload" as="image" href="{{ $event->vidiwall_qr_background_url }}">
    @endif
    <style>
        :root {
            --p: {{ $event->primary_color }};
            --bg: {{ $event->secondary_color }};
            --acc: {{ $event->accent_color }};
            --on-p: {{ $event->readableInk($event->primary_color) }};
            --on-bg: {{ $event->readableInk($event->secondary_color) }};
            --font-h: '{{ $event->headingFont() }}', 'Syne', sans-serif;
            --font-b: '{{ $event->bodyFont() }}', 'DM Sans', sans-serif;
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

        /* ── Several photos at once: tiles sized by how many the screen
           shows, so a partial page keeps the tile size, centred ───── */
        .photo-grid {
            --cols: 2;
            --rows: 1;
            --gap: clamp(8px, 1.5vh, 20px);
            position: relative;
            z-index: 2;
            box-sizing: border-box;
            width: 100%;
            height: 100%;
            padding: clamp(14px, 4vh, 56px) clamp(14px, 3vw, 56px);
            display: flex;
            flex-wrap: wrap;
            align-content: center;
            justify-content: center;
            gap: var(--gap)
        }

        .photo-grid.n-4 {
            --rows: 2
        }

        .photo-tile {
            flex: 0 0 auto;
            width: calc((100% - (var(--cols) - 1) * var(--gap)) / var(--cols));
            height: calc((100% - (var(--rows) - 1) * var(--gap)) / var(--rows));
            display: flex;
            align-items: center;
            justify-content: center
        }

        .tile-frame {
            position: relative;
            flex: 0 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 50px rgba(0, 0, 0, .75), 0 0 0 3px var(--p);
            opacity: 0
        }

        .tile-frame.is-in {
            animation: photoIn .5s cubic-bezier(.22, 1, .36, 1) both;
            animation-delay: calc(var(--i, 0) * 90ms)
        }

        .tile-frame.is-shown {
            opacity: 1
        }

        .tile-frame img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover
        }

        .tile-name {
            position: absolute;
            left: 50%;
            bottom: clamp(6px, 1.2vh, 14px);
            transform: translateX(-50%);
            max-width: calc(100% - 16px);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            background: rgba(0, 0, 0, .75);
            border: 1px solid var(--acc);
            border-radius: 40px;
            padding: 4px 14px;
            font-family: 'Syne', sans-serif;
            font-size: clamp(11px, 1.1vw, 16px);
            font-weight: 700;
            color: var(--acc)
        }

        /* ── Safe area: the box live media and the idle card sit in ── */
        .safe-area {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center
        }

        /* Badges, name tag and progress bar sit on the stage corners, or on
           the media itself when the sponsor background is shown. */
        .media-box {
            display: contents
        }

        /* ── Sponsor background: the artwork replaces the frame ────── */
        .screen.has-bg {
            --safe-x: 10vw;
            --safe-y: 10vh;
            grid-template: "fmain" 1fr / 1fr;
            background: var(--wall-bg) center / cover no-repeat, var(--bg)
        }

        .screen.has-bg .fr-bar,
        .screen.has-bg .fr-rail,
        .screen.has-bg .stage-bg {
            display: none
        }

        .screen.has-bg .stage {
            background: transparent
        }

        .screen.has-bg .safe-area {
            inset: var(--safe-y) var(--safe-x)
        }

        .screen.has-bg .media-box {
            display: block;
            position: relative
        }

        .screen.has-bg .photo-frame,
        .screen.has-bg .video-frame {
            max-width: none;
            max-height: none;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .5), 0 0 0 1px rgba(255, 255, 255, .14)
        }

        .screen.has-bg .photo-frame img,
        .screen.has-bg .video-frame video {
            max-width: calc(100vw - 2 * var(--safe-x));
            max-height: calc(100vh - 2 * var(--safe-y))
        }

        .screen.has-bg .photo-grid {
            width: calc(100vw - 2 * var(--safe-x));
            height: calc(100vh - 2 * var(--safe-y));
            padding: 0
        }

        .screen.has-bg .tile-frame {
            box-shadow: 0 24px 70px rgba(0, 0, 0, .5), 0 0 0 1px rgba(255, 255, 255, .14)
        }

        .screen.has-bg .event-title {
            color: #fff;
            text-shadow: 0 2px 6px rgba(0, 0, 0, .45), 0 6px 36px rgba(0, 0, 0, .6)
        }

        /* ── QR screen: the QR code alone, over the frame and the sponsor artwork ── */
        .qr-screen {
            position: fixed;
            inset: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(18px, 4vh, 72px);
            background: var(--bg) center / cover no-repeat;
            animation: qrIn .5s ease
        }

        .qr-screen[hidden] {
            display: none
        }

        .qr-screen .qr-card {
            height: min(72vh, 72vw)
        }

        /* Call to action: the texts keep their full size and the QR code shrinks to whatever room is left */
        .qr-screen.has-cta {
            flex-direction: column
        }

        .qr-cta {
            --qr-size: min(64vh, 72vw);
            flex: 0 1 auto;
            min-height: 0;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: clamp(12px, 2.8vh, 40px);
            text-align: center;
            color: var(--qr-ink)
        }

        .qr-cta.is-solid,
        .qr-cta.is-glass {
            padding: clamp(18px, 3.6vh, 56px) clamp(22px, 3.4vw, 72px);
            border-radius: clamp(20px, 2.6vw, 44px)
        }

        .qr-cta.is-solid {
            background: var(--qr-panel);
            box-shadow: 0 30px 90px rgba(0, 0, 0, .45)
        }

        .qr-cta.is-glass {
            background: color-mix(in srgb, var(--qr-panel) 40%, transparent);
            border: 1px solid rgba(255, 255, 255, .22);
            box-shadow: 0 30px 90px rgba(0, 0, 0, .35), inset 0 1px 0 rgba(255, 255, 255, .18);
            -webkit-backdrop-filter: blur(22px) saturate(1.3);
            backdrop-filter: blur(22px) saturate(1.3)
        }

        .qr-cta.is-none {
            text-shadow: 0 2px 18px rgba(0, 0, 0, .35)
        }

        .qr-cta-headline,
        .qr-cta-text {
            flex: 0 0 auto;
            white-space: pre-line;
            overflow-wrap: anywhere;
            text-wrap: balance
        }

        .qr-cta-headline {
            max-width: 20em;
            font-family: var(--font-h);
            font-weight: 800;
            font-size: clamp(26px, min(5.6vw, 7.2vh), 104px);
            line-height: 1.05;
            letter-spacing: .01em
        }

        .qr-cta-text {
            max-width: 32em;
            font-family: var(--font-b);
            font-weight: 600;
            font-size: clamp(16px, min(3vw, 3.6vh), 48px);
            line-height: 1.3
        }

        .qr-cta-code {
            flex: 0 1 var(--qr-size);
            min-height: 0;
            width: var(--qr-size);
            max-width: 100%;
            display: flex;
            justify-content: center
        }

        .qr-screen .qr-cta-code .qr-card {
            height: 100%;
            width: auto
        }

        @keyframes qrIn {
            from {
                opacity: 0
            }
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

        /* ── Lottery show ──────────────────────────────────────────── */
        .lot {
            position: absolute;
            inset: 0;
            z-index: 20;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow: hidden;
            padding: clamp(18px, 4vh, 56px) clamp(20px, 4vw, 72px);
            background: var(--bg);
            background: radial-gradient(120% 90% at 50% 38%, color-mix(in srgb, var(--p) 24%, var(--bg)) 0%, var(--bg) 64%);
            color: var(--on-bg);
            animation: lotIn .6s cubic-bezier(.22, 1, .36, 1)
        }

        .lot[hidden] {
            display: none
        }

        .lot.is-leaving {
            animation: lotOut .5s ease forwards
        }

        @keyframes lotIn {
            from {
                opacity: 0;
                transform: scale(1.04)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        @keyframes lotOut {
            to {
                opacity: 0;
                transform: scale(.98)
            }
        }

        .lot-head {
            position: relative;
            z-index: 2;
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: clamp(8px, 1.6vh, 18px);
            max-width: 100%;
            text-align: center
        }

        .lot-headline {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(18px, 3vw, 52px);
            letter-spacing: .24em;
            text-transform: uppercase;
            color: var(--acc);
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .lot-prize {
            display: inline-flex;
            align-items: center;
            gap: .5em;
            max-width: 100%;
            padding: .45em 1.2em;
            border-radius: 999px;
            border: 2px solid color-mix(in srgb, var(--acc) 70%, transparent);
            background: color-mix(in srgb, var(--acc) 12%, transparent);
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: clamp(14px, 1.9vw, 32px);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .lot-prize[hidden] {
            display: none
        }

        .lot-prize svg {
            width: 1.05em;
            height: 1.05em;
            flex: 0 0 auto;
            color: var(--acc)
        }

        .lot-body {
            position: relative;
            z-index: 2;
            flex: 1 1 auto;
            width: 100%;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .lot-scene {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center
        }

        .lot-scene[hidden] {
            display: none
        }

        .lot-ticket {
            width: clamp(90px, 22vh, 260px);
            height: clamp(90px, 22vh, 260px);
            color: var(--acc);
            animation: lotFloat 1.4s ease-in-out infinite
        }

        .lot-ticket svg {
            width: 100%;
            height: 100%
        }

        @keyframes lotFloat {

            0%,
            100% {
                transform: translateY(0) rotate(-6deg)
            }

            50% {
                transform: translateY(-4%) rotate(6deg)
            }
        }

        .lot-intro-text {
            margin-top: clamp(10px, 2.4vh, 30px);
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(26px, 5vw, 90px);
            letter-spacing: .04em;
            text-transform: uppercase
        }

        .lot-count {
            --ring: min(62vh, 52vw)
        }

        .lot-ring {
            position: absolute;
            width: var(--ring);
            height: var(--ring);
            transform: rotate(-90deg)
        }

        .lot-ring circle {
            fill: none;
            stroke-width: 5
        }

        .lot-ring-track {
            stroke: color-mix(in srgb, var(--on-bg) 12%, transparent)
        }

        .lot-ring-fill {
            stroke: var(--acc);
            stroke-linecap: round;
            stroke-dasharray: 339.292
        }

        .lot-count-num {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: calc(var(--ring) * .5);
            line-height: 1;
            font-variant-numeric: tabular-nums
        }

        .lot-count-num.is-tick {
            animation: lotTick .9s cubic-bezier(.22, 1, .36, 1)
        }

        @keyframes lotTick {
            from {
                opacity: .2;
                transform: scale(1.35)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        .lot-reel {
            --row: clamp(56px, 13vh, 170px);
            position: relative;
            width: min(100%, 1400px);
            height: calc(var(--row) * 3);
            overflow: hidden;
            -webkit-mask-image: linear-gradient(to bottom, transparent 0%, #000 28%, #000 72%, transparent 100%);
            mask-image: linear-gradient(to bottom, transparent 0%, #000 28%, #000 72%, transparent 100%)
        }

        .lot-reel::before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: var(--row);
            height: var(--row);
            border-top: 3px solid var(--acc);
            border-bottom: 3px solid var(--acc);
            background: color-mix(in srgb, var(--acc) 10%, transparent);
            box-shadow: 0 0 60px color-mix(in srgb, var(--acc) 30%, transparent);
            z-index: 0
        }

        .lot-reel-strip {
            position: relative;
            z-index: 1;
            will-change: transform
        }

        .lot-reel-item {
            height: var(--row);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4%;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: calc(var(--row) * .5);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            opacity: .45;
            transition: opacity .15s
        }

        .lot-reel-item.is-center {
            opacity: 1
        }

        .lot-rays {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 160vmax;
            height: 160vmax;
            margin: -80vmax 0 0 -80vmax;
            background: repeating-conic-gradient(from 0deg, color-mix(in srgb, var(--acc) 16%, transparent) 0deg 7deg, transparent 7deg 22deg);
            -webkit-mask-image: radial-gradient(circle, #000 0%, transparent 42%);
            mask-image: radial-gradient(circle, #000 0%, transparent 42%);
            animation: lotSpin 40s linear infinite;
            pointer-events: none
        }

        @keyframes lotSpin {
            to {
                transform: rotate(360deg)
            }
        }

        .lot-result>*:not(.lot-rays) {
            position: relative
        }

        .lot-eyebrow {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(16px, 2.6vw, 46px);
            letter-spacing: .34em;
            text-transform: uppercase;
            color: var(--acc)
        }

        .lot-name {
            width: 100%;
            margin-top: clamp(6px, 1.6vh, 20px);
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(48px, 11vw, 220px);
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            text-shadow: 0 0 60px color-mix(in srgb, var(--acc) 55%, transparent)
        }

        .lot-name.is-wrapped {
            white-space: normal;
            overflow-wrap: anywhere
        }

        .lot-detail {
            margin-top: clamp(10px, 2.2vh, 28px);
            font-size: clamp(16px, 2.2vw, 38px);
            font-weight: 600;
            letter-spacing: .12em;
            opacity: .7
        }

        .lot-detail:empty {
            display: none
        }

        .lot-result.is-revealed .lot-eyebrow {
            animation: lotRise .7s .05s cubic-bezier(.22, 1, .36, 1) both
        }

        .lot-result.is-revealed .lot-name {
            animation: lotPop .9s .15s cubic-bezier(.34, 1.56, .64, 1) both
        }

        .lot-result.is-revealed .lot-detail {
            animation: lotRise .7s .5s cubic-bezier(.22, 1, .36, 1) both
        }

        @keyframes lotPop {
            from {
                opacity: 0;
                transform: scale(.6)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        @keyframes lotRise {
            from {
                opacity: 0;
                transform: translateY(24px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .lot-board {
            overflow: hidden
        }

        .lot-board-grid {
            --bsz: 48px;
            width: min(100%, 1600px);
            display: grid;
            grid-template-columns: repeat(var(--cols, 1), minmax(0, 1fr));
            gap: clamp(10px, 1.8vh, 22px) clamp(14px, 2vw, 32px)
        }

        .lot-board-card {
            display: flex;
            align-items: center;
            gap: calc(var(--bsz) * .55);
            min-width: 0;
            padding: calc(var(--bsz) * .32) calc(var(--bsz) * .5);
            border-radius: calc(var(--bsz) * .4);
            background: color-mix(in srgb, var(--on-bg) 7%, transparent);
            border: 1px solid color-mix(in srgb, var(--on-bg) 12%, transparent);
            animation: lotRise .6s cubic-bezier(.22, 1, .36, 1) both
        }

        .lot-board-rank {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            width: calc(var(--bsz) * 1.5);
            height: calc(var(--bsz) * 1.5);
            border-radius: 50%;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: calc(var(--bsz) * .6);
            background: var(--acc);
            color: var(--bg)
        }

        .lot-board-text {
            min-width: 0
        }

        .lot-board-name {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: var(--bsz);
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .lot-board-prize {
            margin-top: .2em;
            font-size: calc(var(--bsz) * .5);
            font-weight: 600;
            color: var(--acc);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .lot-foot {
            position: relative;
            z-index: 2;
            flex: 0 0 auto;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: clamp(13px, 1.6vw, 26px);
            letter-spacing: .22em;
            text-transform: uppercase;
            opacity: .55
        }

        .lot-progress {
            position: absolute;
            left: 0;
            bottom: 0;
            height: 4px;
            width: 0;
            background: var(--acc);
            z-index: 3
        }

        .lot-confetti {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 4
        }

        /* ── Vote show ─────────────────────────────────────────────── */
        .vot {
            position: absolute;
            inset: 0;
            z-index: 21;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow: hidden;
            padding: clamp(18px, 4vh, 56px) clamp(20px, 4vw, 72px);
            background: var(--bg);
            background: radial-gradient(120% 90% at 50% 30%, color-mix(in srgb, var(--p) 22%, var(--bg)) 0%, var(--bg) 66%);
            color: var(--on-bg);
            animation: votIn .6s cubic-bezier(.22, 1, .36, 1)
        }

        .vot[hidden] {
            display: none
        }

        .vot.is-leaving {
            animation: votOut .5s ease forwards
        }

        @keyframes votIn {
            from {
                opacity: 0;
                transform: scale(1.04)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        @keyframes votOut {
            to {
                opacity: 0;
                transform: scale(.98)
            }
        }

        .vot-head {
            position: relative;
            z-index: 2;
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: clamp(6px, 1.3vh, 16px);
            max-width: 100%;
            text-align: center
        }

        .vot-headline {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(18px, 3vw, 52px);
            letter-spacing: .24em;
            text-transform: uppercase;
            color: var(--acc);
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .vot-status {
            display: inline-flex;
            align-items: center;
            gap: .6em;
            padding: .35em 1.1em;
            border-radius: 999px;
            background: color-mix(in srgb, var(--on-bg) 10%, transparent);
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: clamp(12px, 1.5vw, 24px);
            letter-spacing: .2em;
            text-transform: uppercase
        }

        .vot-status[hidden] {
            display: none
        }

        .vot-status-dot {
            width: .6em;
            height: .6em;
            border-radius: 50%;
            background: #ef4444;
            animation: votPulse 1.6s infinite
        }

        .vot-status.is-final .vot-status-dot {
            background: var(--acc);
            animation: none
        }

        @keyframes votPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, .7)
            }

            80%,
            100% {
                box-shadow: 0 0 0 .7em rgba(239, 68, 68, 0)
            }
        }

        .vot-body {
            position: relative;
            z-index: 2;
            flex: 1 1 auto;
            width: 100%;
            min-height: 0;
            margin-top: clamp(10px, 2.4vh, 32px)
        }

        .vot-scene {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center
        }

        .vot-scene[hidden] {
            display: none
        }

        .vot-live {
            flex-direction: row;
            align-items: stretch;
            gap: clamp(16px, 3vw, 64px)
        }

        .vot-chart {
            --row: 80px;
            position: relative;
            flex: 1 1 auto;
            min-width: 0;
            max-width: 1500px
        }

        .vot-row {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: var(--row);
            display: grid;
            grid-template-columns: calc(var(--row) * .74) minmax(0, 1fr);
            column-gap: calc(var(--row) * .24);
            align-items: center;
            transition: transform .9s cubic-bezier(.22, 1, .36, 1);
            will-change: transform
        }

        .vot-row.is-entering {
            transition: none
        }

        .vot-row.is-entering>* {
            animation: votSlide .7s var(--d, 0s) cubic-bezier(.22, 1, .36, 1) both
        }

        @keyframes votSlide {
            from {
                opacity: 0;
                transform: translateX(-4%)
            }

            to {
                opacity: 1;
                transform: translateX(0)
            }
        }

        .vot-rank {
            justify-self: center;
            display: flex;
            align-items: center;
            justify-content: center;
            width: calc(var(--row) * .64);
            height: calc(var(--row) * .64);
            border-radius: 50%;
            background: color-mix(in srgb, var(--on-bg) 12%, transparent);
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: calc(var(--row) * .28);
            font-variant-numeric: tabular-nums
        }

        .vot-row.is-r1 .vot-rank {
            background: linear-gradient(135deg, #FFE066, #E6B800);
            color: #1a1400
        }

        .vot-row.is-r2 .vot-rank {
            background: linear-gradient(135deg, #E5E7EB, #9CA3AF);
            color: #111827
        }

        .vot-row.is-r3 .vot-rank {
            background: linear-gradient(135deg, #F0B27A, #B8732E);
            color: #1f1206
        }

        .vot-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: calc(var(--row) * .07)
        }

        .vot-line {
            display: flex;
            align-items: baseline;
            gap: calc(var(--row) * .16);
            min-width: 0
        }

        .vot-name {
            flex: 0 1 auto;
            min-width: 0;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: calc(var(--row) * .32);
            line-height: 1.12;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .vot-pos {
            flex: 0 1 auto;
            min-width: 0;
            font-size: calc(var(--row) * .19);
            font-weight: 600;
            opacity: .6;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .vot-pos[hidden],
        .vot-value[hidden] {
            display: none
        }

        .vot-value {
            flex: 0 0 auto;
            margin-left: auto;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: calc(var(--row) * .3);
            font-variant-numeric: tabular-nums;
            white-space: nowrap
        }

        .vot-value small {
            margin-left: .4em;
            font-size: .66em;
            opacity: .65
        }

        .vot-row.is-r1 .vot-value {
            color: var(--acc)
        }

        .vot-row.is-bump .vot-value {
            animation: votBump .6s cubic-bezier(.22, 1, .36, 1)
        }

        @keyframes votBump {
            40% {
                transform: scale(1.12)
            }
        }

        .vot-track {
            height: calc(var(--row) * .2);
            border-radius: 999px;
            background: color-mix(in srgb, var(--on-bg) 9%, transparent);
            overflow: hidden
        }

        .vot-fill {
            height: 100%;
            width: 0;
            border-radius: inherit;
            background: linear-gradient(90deg, color-mix(in srgb, var(--p) 60%, var(--bg)), var(--p));
            transition: width 1.1s cubic-bezier(.22, 1, .36, 1)
        }

        .vot-row.is-r1 .vot-fill {
            background: linear-gradient(90deg, color-mix(in srgb, var(--acc) 60%, var(--bg)), var(--acc));
            box-shadow: 0 0 calc(var(--row) * .3) color-mix(in srgb, var(--acc) 45%, transparent)
        }

        .vot-qr {
            flex: 0 0 auto;
            align-self: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: clamp(8px, 1.8vh, 22px);
            width: clamp(140px, min(22vw, 46vh), 440px)
        }

        .vot-qr[hidden] {
            display: none
        }

        .vot-qr-card {
            width: 100%;
            aspect-ratio: 1;
            padding: clamp(8px, 1.2vw, 20px);
            border-radius: clamp(12px, 1.6vw, 24px);
            background: #fff;
            box-shadow: 0 0 0 5px var(--acc), 0 24px 60px rgba(0, 0, 0, .5)
        }

        .vot-qr-card img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain
        }

        .vot-qr-url {
            font-size: clamp(14px, 1.6vw, 26px);
            font-weight: 600;
            color: var(--acc);
            text-align: center;
            word-break: break-all
        }

        .vot-qr-text {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(16px, 2.2vw, 40px);
            letter-spacing: .14em;
            text-transform: uppercase;
            text-align: center;
            color: var(--acc)
        }

        .vot-trophy {
            width: clamp(90px, 22vh, 260px);
            height: clamp(90px, 22vh, 260px);
            color: var(--acc);
            animation: votFloat 1.6s ease-in-out infinite
        }

        .vot-trophy svg {
            width: 100%;
            height: 100%
        }

        @keyframes votFloat {

            0%,
            100% {
                transform: translateY(0) scale(1)
            }

            50% {
                transform: translateY(-4%) scale(1.04)
            }
        }

        .vot-intro-text {
            margin-top: clamp(10px, 2.4vh, 30px);
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(26px, 5vw, 90px);
            letter-spacing: .04em;
            text-transform: uppercase;
            text-align: center
        }

        .vot-count {
            --ring: min(62vh, 52vw)
        }

        .vot-ring {
            position: absolute;
            width: var(--ring);
            height: var(--ring);
            transform: rotate(-90deg)
        }

        .vot-ring circle {
            fill: none;
            stroke-width: 5
        }

        .vot-ring-track {
            stroke: color-mix(in srgb, var(--on-bg) 12%, transparent)
        }

        .vot-ring-fill {
            stroke: var(--acc);
            stroke-linecap: round;
            stroke-dasharray: 339.292
        }

        .vot-count-num {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: calc(var(--ring) * .5);
            line-height: 1;
            font-variant-numeric: tabular-nums
        }

        .vot-count-num.is-tick {
            animation: votTick .9s cubic-bezier(.22, 1, .36, 1)
        }

        @keyframes votTick {
            from {
                opacity: .2;
                transform: scale(1.35)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        .vot-rays {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 160vmax;
            height: 160vmax;
            margin: -80vmax 0 0 -80vmax;
            background: repeating-conic-gradient(from 0deg, color-mix(in srgb, var(--acc) 16%, transparent) 0deg 7deg, transparent 7deg 22deg);
            -webkit-mask-image: radial-gradient(circle, #000 0%, transparent 42%);
            mask-image: radial-gradient(circle, #000 0%, transparent 42%);
            animation: votSpin 40s linear infinite;
            pointer-events: none
        }

        @keyframes votSpin {
            to {
                transform: rotate(360deg)
            }
        }

        .vot-result>*:not(.vot-rays) {
            position: relative
        }

        .vot-eyebrow {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(16px, 2.6vw, 46px);
            letter-spacing: .34em;
            text-transform: uppercase;
            color: var(--acc)
        }

        .vot-winners {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: clamp(10px, 2.4vh, 32px);
            margin-top: clamp(6px, 1.6vh, 20px)
        }

        .vot-winner {
            width: 100%;
            text-align: center
        }

        .vot-winner-name {
            width: 100%;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(48px, 11vw, 220px);
            line-height: 1;
            white-space: nowrap;
            text-shadow: 0 0 60px color-mix(in srgb, var(--acc) 55%, transparent)
        }

        .vot-winners.is-tie .vot-winner-name {
            font-size: clamp(32px, 6.4vw, 140px)
        }

        .vot-winner-name.is-wrapped {
            white-space: normal;
            overflow-wrap: anywhere
        }

        .vot-winner-detail {
            margin-top: clamp(8px, 1.6vh, 20px);
            font-size: clamp(16px, 2.2vw, 38px);
            font-weight: 600;
            letter-spacing: .1em;
            opacity: .75
        }

        .vot-winner-detail:empty {
            display: none
        }

        .vot-result.is-revealed .vot-eyebrow {
            animation: votUp .7s .05s cubic-bezier(.22, 1, .36, 1) both
        }

        .vot-result.is-revealed .vot-winner-name {
            animation: votPop .9s .15s cubic-bezier(.34, 1.56, .64, 1) both
        }

        .vot-result.is-revealed .vot-winner-detail {
            animation: votUp .7s .5s cubic-bezier(.22, 1, .36, 1) both
        }

        @keyframes votPop {
            from {
                opacity: 0;
                transform: scale(.6)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        @keyframes votUp {
            from {
                opacity: 0;
                transform: translateY(24px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .vot-podium {
            position: relative;
            width: min(100%, 1700px);
            height: 100%;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.18fr) minmax(0, 1fr);
            gap: clamp(12px, 1.6vw, 32px);
            align-items: end;
            container-type: size
        }

        .vot-podium::after {
            content: '';
            position: absolute;
            left: -6%;
            right: -6%;
            bottom: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--on-bg) 40%, transparent) 20%, color-mix(in srgb, var(--acc) 80%, transparent) 50%, color-mix(in srgb, var(--on-bg) 40%, transparent) 80%, transparent);
            box-shadow: 0 0 24px color-mix(in srgb, var(--acc) 50%, transparent)
        }

        .vot-beam {
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 64%;
            height: 135%;
            transform: translateX(-50%);
            clip-path: polygon(40% 0, 60% 0, 100% 100%, 0 100%);
            background: linear-gradient(180deg, color-mix(in srgb, var(--acc) 30%, transparent), color-mix(in srgb, var(--acc) 10%, transparent) 60%, transparent);
            -webkit-mask-image: linear-gradient(180deg, transparent, #000 30%);
            mask-image: linear-gradient(180deg, transparent, #000 30%);
            filter: blur(10px);
            pointer-events: none;
            animation: votBreathe 4.5s ease-in-out infinite
        }

        @keyframes votBreathe {

            0%,
            100% {
                opacity: .65
            }

            50% {
                opacity: 1
            }
        }

        .vot-place {
            position: relative;
            z-index: 1;
            min-width: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            text-align: center
        }

        .vot-place.is-empty {
            visibility: hidden
        }

        .vot-place[data-place="3"] {
            --d: 0s
        }

        .vot-place[data-place="2"] {
            --d: .3s
        }

        .vot-place[data-place="1"] {
            --d: .6s
        }

        .vot-place-names {
            flex: 0 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: calc(clamp(4px, 1.2cqh, 14px) * var(--k, 1));
            margin-bottom: calc(clamp(8px, 3cqh, 30px) * var(--k, 1))
        }

        .vot-place-name {
            width: 100%;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: calc(clamp(22px, min(5vw, 13cqh), 140px) * var(--k, 1));
            line-height: 1.04;
            text-wrap: balance
        }

        .vot-place-name span {
            display: inline-block
        }

        .vot-pillar {
            position: relative;
            flex: 0 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            container-type: size;
            border-radius: clamp(10px, 1.2vw, 22px) clamp(10px, 1.2vw, 22px) 0 0;
            box-shadow: inset 0 clamp(4px, .7vh, 9px) 0 rgba(255, 255, 255, .45);
            transform-origin: bottom
        }

        .vot-pillar::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0) 4%, rgba(255, 255, 255, .26) 16%, rgba(255, 255, 255, 0) 34%, rgba(0, 0, 0, 0) 70%, rgba(0, 0, 0, .14));
            pointer-events: none
        }

        .vot-pillar-value {
            position: relative;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: min(58cqh, 25cqw);
            line-height: 1;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
            text-shadow: 0 .03em 0 rgba(255, 255, 255, .45)
        }

        .vot-pillar-unit {
            position: relative;
            margin-top: .2em;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: min(12cqh, 6cqw);
            letter-spacing: .2em;
            text-transform: uppercase;
            opacity: .7
        }

        .vot-pillar-unit[hidden] {
            display: none
        }

        .vot-place[data-place="1"] .vot-pillar {
            height: 62%;
            background: linear-gradient(180deg, #FFE27A, #C99A00);
            color: #1a1400;
            box-shadow: inset 0 clamp(4px, .7vh, 9px) 0 rgba(255, 255, 255, .55), 0 0 clamp(30px, 5vw, 110px) rgba(255, 204, 0, .3)
        }

        .vot-place[data-place="1"] .vot-pillar::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: -60%;
            width: 40%;
            background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .5), transparent);
            transform: skewX(-18deg);
            animation: votShine 5s 2.2s ease-in-out infinite;
            pointer-events: none
        }

        @keyframes votShine {
            0% {
                left: -60%
            }

            30%,
            100% {
                left: 130%
            }
        }

        .vot-place[data-place="2"] .vot-pillar {
            height: 47%;
            background: linear-gradient(180deg, #EEF0F3, #8C939E);
            color: #111827
        }

        .vot-place[data-place="3"] .vot-pillar {
            height: 34%;
            background: linear-gradient(180deg, #F5BC88, #A86425);
            color: #1f1206
        }

        .vot-podium.is-rising .vot-pillar {
            animation: votGrow .9s var(--d) cubic-bezier(.22, 1, .36, 1) both
        }

        .vot-podium.is-rising .vot-place-names {
            animation: votUp .7s calc(var(--d) + .45s) cubic-bezier(.22, 1, .36, 1) both
        }

        .vot-podium.is-rising .vot-beam {
            animation: votBeamIn 1.2s 1s ease both, votBreathe 4.5s 2.2s ease-in-out infinite
        }

        @keyframes votBeamIn {
            from {
                opacity: 0
            }

            to {
                opacity: .65
            }
        }

        @keyframes votGrow {
            from {
                transform: scaleY(0)
            }

            to {
                transform: scaleY(1)
            }
        }

        .vot-foot {
            position: relative;
            z-index: 2;
            flex: 0 0 auto;
            margin-top: clamp(8px, 1.8vh, 24px);
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: clamp(13px, 1.6vw, 26px);
            letter-spacing: .22em;
            text-transform: uppercase;
            opacity: .6
        }

        .vot-foot[hidden] {
            display: none
        }

        .vot-progress {
            position: absolute;
            left: 0;
            bottom: 0;
            height: 4px;
            width: 0;
            background: var(--acc);
            z-index: 3
        }

        .vot-confetti {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 4
        }
    </style>
</head>

<body>
    <div @class(['screen', 'has-bg' => $event->hasVidiwallBackground()])
        @if ($event->hasVidiwallBackground()) style="--wall-bg: url('{{ $event->vidiwall_background_url }}')" @endif>

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

            <div class="safe-area" id="safeArea">
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

                <div class="media-box" id="mediaBox">
                    <div class="photo-frame" id="photoFrame" style="display:none">
                        <img id="liveImg" src="" alt="">
                    </div>

                    <div class="video-frame" id="videoFrame" style="display:none">
                        <video id="liveVideo" src="" muted playsinline preload="auto"></video>
                    </div>

                    <div class="photo-grid" id="photoGrid" style="display:none"></div>

                    <div class="uploader" id="uploaderTag" style="display:none">
                        <span id="uploaderName"></span>
                    </div>

                    <div class="live-badge" id="liveBadge" style="display:none">&#9679; LIVE</div>
                    <div class="video-badge" id="videoBadge" style="display:none">&#9654; VIDEO</div>
                    <div class="slide-counter" id="slideCounter" style="display:none"></div>
                </div>
            </div>

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

            @if ($event->module_lottery)
                <div class="lot" id="lotOverlay" hidden>
                    <div class="lot-head">
                        <div class="lot-headline" id="lotHeadline"></div>
                        <div class="lot-prize" id="lotPrize" hidden>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>
                            <span id="lotPrizeText"></span>
                        </div>
                    </div>

                    <div class="lot-body">
                        <div class="lot-scene" data-scene="intro" hidden>
                            <div class="lot-ticket">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>
                            </div>
                            <div class="lot-intro-text">Get ready</div>
                        </div>

                        <div class="lot-scene lot-count" data-scene="countdown" hidden>
                            <svg class="lot-ring" viewBox="0 0 120 120" aria-hidden="true">
                                <circle class="lot-ring-track" cx="60" cy="60" r="54" />
                                <circle class="lot-ring-fill" id="lotRingFill" cx="60" cy="60" r="54" />
                            </svg>
                            <div class="lot-count-num" id="lotCountNum"></div>
                        </div>

                        <div class="lot-scene" data-scene="drawing" hidden>
                            <div class="lot-reel" id="lotReel">
                                <div class="lot-reel-strip" id="lotReelStrip"></div>
                            </div>
                        </div>

                        <div class="lot-scene lot-result" data-scene="result" id="lotResult" hidden>
                            <div class="lot-rays"></div>
                            <div class="lot-eyebrow" id="lotEyebrow">Winner</div>
                            <div class="lot-name" id="lotName"></div>
                            <div class="lot-detail" id="lotDetail"></div>
                        </div>

                        <div class="lot-scene lot-board" data-scene="board" hidden>
                            <div class="lot-board-grid" id="lotBoardGrid"></div>
                        </div>
                    </div>

                    <div class="lot-foot" id="lotFoot" hidden></div>
                    <div class="lot-progress" id="lotProgress"></div>
                    <canvas class="lot-confetti" id="lotConfetti"></canvas>
                </div>
            @endif

            @if ($event->module_voting)
                <div class="vot" id="votOverlay" hidden>
                    <div class="vot-head">
                        <div class="vot-headline" id="votHeadline"></div>
                        <div class="vot-status" id="votStatus" hidden>
                            <span class="vot-status-dot"></span>
                            <span id="votStatusText"></span>
                        </div>
                    </div>

                    <div class="vot-body">
                        <div class="vot-scene vot-live" data-scene="live" hidden>
                            <div class="vot-chart" id="votChart"></div>
                            <div class="vot-qr" id="votQr" hidden>
                                @if ($event->qr_code_path)
                                    <div class="vot-qr-card">
                                        <img src="{{ $event->qr_code_url }}" alt="QR code">
                                    </div>
                                @else
                                    <div class="vot-qr-url">{{ $event->getGuestUrl() }}</div>
                                @endif
                                <div class="vot-qr-text">Scan to vote</div>
                            </div>
                        </div>

                        <div class="vot-scene" data-scene="intro" hidden>
                            <div class="vot-trophy">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                            </div>
                            <div class="vot-intro-text">And the winner is…</div>
                        </div>

                        <div class="vot-scene vot-count" data-scene="countdown" hidden>
                            <svg class="vot-ring" viewBox="0 0 120 120" aria-hidden="true">
                                <circle class="vot-ring-track" cx="60" cy="60" r="54" />
                                <circle class="vot-ring-fill" id="votRingFill" cx="60" cy="60" r="54" />
                            </svg>
                            <div class="vot-count-num" id="votCountNum"></div>
                        </div>

                        <div class="vot-scene vot-result" data-scene="result" id="votResult" hidden>
                            <div class="vot-rays"></div>
                            <div class="vot-eyebrow" id="votEyebrow">Winner</div>
                            <div class="vot-winners" id="votWinners"></div>
                        </div>

                        <div class="vot-scene" data-scene="podium" hidden>
                            <div class="vot-podium" id="votPodium">
                                <div class="vot-beam"></div>
                                @foreach ([2, 1, 3] as $place)
                                    <div class="vot-place" data-place="{{ $place }}">
                                        <div class="vot-place-names"></div>
                                        <div class="vot-pillar">
                                            <span class="vot-pillar-value"></span>
                                            <span class="vot-pillar-unit" hidden>votes</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="vot-foot" id="votFoot" hidden></div>
                    <div class="vot-progress" id="votProgress"></div>
                    <canvas class="vot-confetti" id="votConfetti"></canvas>
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

        <div @class(['qr-screen', 'has-cta' => $event->hasQrScreenCallToAction()]) id="qrScreen" hidden
            @if ($event->vidiwall_qr_background_url) style="background-image: url('{{ $event->vidiwall_qr_background_url }}')" @endif>
            @if ($event->hasQrScreenCallToAction())
                @php $qrCta = $event->qrScreenCallToAction(); @endphp
                <div class="qr-cta is-{{ $qrCta['panel'] }}"
                    style="--qr-ink: {{ $qrCta['text_color'] }}; --qr-panel: {{ $qrCta['panel_color'] }}">
                    @if ($qrCta['headline'] !== '')
                        <div class="qr-cta-headline">{{ $qrCta['headline'] }}</div>
                    @endif
                    @if ($event->qr_code_path)
                        <div class="qr-cta-code">
                            <div class="qr-card">
                                <img src="{{ $event->qr_code_url }}" alt="QR code">
                            </div>
                        </div>
                    @else
                        <div class="qr-fallback">{{ $event->getGuestUrl() }}</div>
                    @endif
                    @if ($qrCta['small_text'] !== '')
                        <div class="qr-cta-text">{{ $qrCta['small_text'] }}</div>
                    @endif
                </div>
            @elseif ($event->qr_code_path)
                <div class="qr-card">
                    <img src="{{ $event->qr_code_url }}" alt="QR code">
                </div>
            @else
                <div class="qr-fallback">{{ $event->getGuestUrl() }}</div>
            @endif
        </div>

    </div>

    <script>
        const SLUG = '{{ $event->slug }}';
        const SHOW_NAME = {{ $event->vidiwall_show_uploader ? 'true' : 'false' }};
        const HAS_FANCLASH = {{ $event->module_fanclash ? 'true' : 'false' }};
        const HAS_LOTTERY = {{ $event->module_lottery ? 'true' : 'false' }};
        const HAS_VOTING = {{ $event->module_voting ? 'true' : 'false' }};
        const HAS_WALL_BG = {{ $event->hasVidiwallBackground() ? 'true' : 'false' }};

        let clashActive = false;
        let lastFotoId = null;
        let lastPageKey = null;
        let slideIndex = 0;
        let slideTimer = null;
        let slideFotos = [];
        let queueTimer = null;
        let pollSeq = 0;
        let playerRev = null;
        let playerState = null;
        let pendingDone = null;
        let preloaded = { key: null, media: [] };
        let qrShown = false;

        /* The operator's QR screen covers everything; the playlist is held under it. */
        function setQrScreen(show) {
            if (show === qrShown) return;
            qrShown = show;
            document.getElementById('qrScreen').hidden = !show;
        }

        function setBackground(url) {
            if (HAS_WALL_BG) {
                return;
            }
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
            hideGrid();

            const liveVideo = document.getElementById('liveVideo');
            liveVideo.onerror = null;
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

        function showVideo(videoUrl, name, slideNum, total, onEnded, autoplay = true) {
            document.getElementById('idleState').style.display = 'none';
            document.getElementById('photoFrame').style.display = 'none';
            document.getElementById('liveBadge').style.display = 'none';
            hideGrid();

            document.getElementById('videoBadge').style.display = 'block';

            showUploaderTag(name);
            showSlideInfo(slideNum, total);

            const frame = document.getElementById('videoFrame');
            const liveVideo = document.getElementById('liveVideo');
            liveVideo.onended = onEnded || null;
            liveVideo.onerror = onEnded || null;
            liveVideo.src = videoUrl;
            if (autoplay) {
                liveVideo.play().catch(() => {});
            }
            frame.style.display = 'block';

            setBackground(null);
        }

        function showIdle() {
            document.getElementById('idleState').style.display = 'flex';
            if (window.fitVidiwall) { window.fitVidiwall(); }
            document.getElementById('photoFrame').style.display = 'none';
            document.getElementById('videoFrame').style.display = 'none';
            hideGrid();
            document.getElementById('uploaderTag').style.display = 'none';
            document.getElementById('liveBadge').style.display = 'none';
            document.getElementById('videoBadge').style.display = 'none';
            document.getElementById('slideCounter').style.display = 'none';
            setBackground(null);
            lastFotoId = null;
            lastPageKey = null;
            slideFotos = [];
            clearTimeout(slideTimer);
            clearTimeout(queueTimer);
            document.getElementById('liveVideo').onerror = null;
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

            const liveVideo = document.getElementById('liveVideo');

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
                    showVideo(f.video_url, f.uploader, slideIndex + 1, slideFotos.length, goNext);

                    slideTimer = setTimeout(goNext, 60_000);
                } else {
                    liveVideo.onended = null;
                    liveVideo.pause();
                    liveVideo.src = '';

                    showPhoto(f.url, f.uploader, slideIndex + 1, slideFotos.length);

                    slideTimer = setTimeout(goNext, interval * 1000);
                }
            }

            nextSlide();
        }

        /* ── Player: the operator's playlist drives the screen. The screen times
              the current item from the server's elapsed time and reports it
              done (with the revision it was showing) so the next one comes up.
              The report rides along on every poll until the server has moved
              on, so a request lost on the way does not hold the item up. ── */
        function hideGrid() {
            const grid = document.getElementById('photoGrid');
            grid.style.display = 'none';
            grid.innerHTML = '';
        }

        /** Sizes a tile's frame to its photo, as large as the tile allows. */
        function fitTile(tile) {
            const img = tile.querySelector('img');
            if (!img.naturalWidth || !tile.clientWidth) {
                return;
            }
            const scale = Math.min(tile.clientWidth / img.naturalWidth, tile.clientHeight / img.naturalHeight);
            tile.firstElementChild.style.width = Math.floor(img.naturalWidth * scale) + 'px';
            tile.firstElementChild.style.height = Math.floor(img.naturalHeight * scale) + 'px';
        }

        function fitGrid() {
            document.querySelectorAll('#photoGrid .photo-tile').forEach(fitTile);
        }

        window.addEventListener('resize', fitGrid);

        function tileKey(item) {
            return item.id + '|' + item.url;
        }

        function makeTile(item, index) {
            const tile = document.createElement('div');
            tile.className = 'photo-tile';
            tile.dataset.key = tileKey(item);

            const frame = document.createElement('div');
            frame.className = 'tile-frame';
            frame.style.setProperty('--i', index);
            frame.addEventListener('animationend', () => frame.classList.replace('is-in', 'is-shown'), { once: true });

            const img = document.createElement('img');
            img.alt = '';
            img.onload = () => {
                fitTile(tile);
                frame.classList.add('is-in');
            };
            img.src = item.url;
            frame.appendChild(img);

            if (SHOW_NAME && item.uploader) {
                const name = document.createElement('div');
                name.className = 'tile-name';
                name.textContent = item.uploader;
                frame.appendChild(name);
            }

            tile.appendChild(frame);
            return tile;
        }

        /* Several photos at once, each leaning towards the middle of the grid
           so portrait and landscape shots sit together as one group. Tiles
           that stay on the page are kept as they are, so a page that loses a
           photo does not flash the others. */
        function showGrid(page, layout) {
            document.getElementById('idleState').style.display = 'none';
            document.getElementById('photoFrame').style.display = 'none';
            document.getElementById('videoFrame').style.display = 'none';
            document.getElementById('videoBadge').style.display = 'none';
            document.getElementById('uploaderTag').style.display = 'none';
            document.getElementById('slideCounter').style.display = 'none';
            document.getElementById('liveBadge').style.display = 'block';
            document.getElementById('liveImg').onload = null;

            const liveVideo = document.getElementById('liveVideo');
            liveVideo.onended = null;
            liveVideo.onerror = null;
            liveVideo.pause();
            liveVideo.src = '';

            setBackground(null);

            const grid = document.getElementById('photoGrid');
            const keys = page.map(tileKey);
            grid.className = 'photo-grid n-' + layout;
            grid.style.display = 'flex';

            [...grid.children].forEach(tile => {
                if (!keys.includes(tile.dataset.key)) {
                    tile.remove();
                }
            });
            const kept = new Map([...grid.children].map(tile => [tile.dataset.key, tile]));
            const cols = parseInt(getComputedStyle(grid).getPropertyValue('--cols'), 10) || 1;
            const rows = Math.ceil(page.length / cols);
            page.forEach((item, index) => {
                const tile = kept.get(keys[index]) || makeTile(item, index);
                const row = Math.floor(index / cols);
                const col = index % cols;
                const inRow = Math.min(cols, page.length - row * cols);
                tile.style.justifyContent = inRow === 1 ? 'center' : col === 0 ? 'flex-end' : col === inRow - 1 ? 'flex-start' : 'center';
                tile.style.alignItems = rows === 1 ? 'center' : row === 0 ? 'flex-end' : row === rows - 1 ? 'flex-start' : 'center';
                if (grid.children[index] !== tile) {
                    grid.insertBefore(tile, grid.children[index] || null);
                }
            });
            fitGrid();
        }

        /** Loads the page due next: every photo, and at most one video. */
        function preloadNext(items) {
            const list = (items || []).filter(Boolean);
            const isVideo = item => item.media_type === 'video' && !!item.video_url;
            const key = list.map(item => isVideo(item) ? item.video_url : item.url).join('|');
            if (key === preloaded.key) {
                return;
            }
            preloaded.media.forEach(media => {
                if (media instanceof HTMLVideoElement) {
                    media.removeAttribute('src');
                    media.load();
                }
            });
            preloaded = { key, media: [] };
            const video = list.find(isVideo);
            if (video) {
                const element = document.createElement('video');
                element.muted = true;
                element.preload = 'auto';
                element.src = video.video_url;
                preloaded.media.push(element);
            }
            list.filter(item => !isVideo(item)).forEach(item => {
                const img = new Image();
                img.src = item.url;
                preloaded.media.push(img);
            });
        }

        function resetPlayerView() {
            lastFotoId = null;
            lastPageKey = null;
            playerRev = null;
            playerState = null;
            pendingDone = null;
        }

        function applyPlayer(data) {
            const foto = data.foto;

            if (pendingDone && (!foto || foto.id !== pendingDone.id || data.revision !== pendingDone.rev)) {
                pendingDone = null;
            }

            if (!foto || data.state === 'stopped') {
                if (lastFotoId !== null || playerState !== 'stopped') {
                    showIdle();
                }
                playerState = 'stopped';
                playerRev = data.revision;
                return;
            }

            const layout = data.layout || 1;
            const page = data.page && data.page.length ? data.page : [foto];
            const tiled = layout > 1 && foto.media_type !== 'video';
            const pageKey = (tiled ? 'grid' + layout + ':' : '') + page.map(item => item.id).join(',');

            if (foto.id === lastFotoId && data.revision === playerRev && pageKey === lastPageKey) {
                return;
            }

            clearTimeout(queueTimer);

            const isNewItem = foto.id !== lastFotoId;
            const isNewPage = pageKey !== lastPageKey;
            const revision = data.revision;
            const elapsed = data.elapsed_ms || 0;
            const slot = foto.slot_ms || 4000;
            const playing = data.state === 'playing';

            lastFotoId = foto.id;
            lastPageKey = pageKey;
            playerRev = revision;
            playerState = data.state;

            let acked = false;
            const finish = () => {
                if (acked) return;
                acked = true;
                clearTimeout(queueTimer);
                pendingDone = { id: foto.id, rev: revision };
                poll();
            };

            if (foto.media_type === 'video' && foto.video_url) {
                const video = document.getElementById('liveVideo');

                if (isNewItem) {
                    const startAt = elapsed > 500 ? '#t=' + (elapsed / 1000).toFixed(1) : '';
                    showVideo(foto.video_url + startAt, foto.uploader, undefined, undefined, finish, playing);
                } else {
                    video.onended = finish;
                    video.onerror = finish;
                    if (Math.abs(video.currentTime * 1000 - elapsed) > 1500) {
                        video.currentTime = elapsed / 1000;
                    }
                }

                if (playing) {
                    video.play().catch(() => {});
                    queueTimer = setTimeout(finish, Math.max(0, slot - elapsed) + 3000);
                } else {
                    video.pause();
                }
            } else {
                if (tiled && isNewPage) {
                    showGrid(page, layout);
                } else if (!tiled && isNewPage) {
                    showPhoto(foto.url, foto.uploader);
                }

                if (playing) {
                    queueTimer = setTimeout(finish, Math.max(0, slot - elapsed));
                }
            }
        }

        async function poll() {
            if (clashActive) return;
            const seq = ++pollSeq;
            const done = pendingDone;
            try {
                const url = `/screen/${SLUG}/feed` + (done ? `?done=${done.id}&rev=${done.rev}` : '');
                const res = await fetch(url, { cache: 'no-store', signal: window.AbortSignal?.timeout ? AbortSignal.timeout(5000) : undefined });
                const data = await res.json();
                if (seq !== pollSeq || clashActive) return;

                if (data.mode === 'slideshow') {
                    setQrScreen(false);
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
                    setQrScreen(!!data.qr_screen);
                    applyPlayer(data);
                    preloadNext(data.next_page || [data.next]);
                }
            } catch (e) {}
        }

        /* Polls again shortly after each answer, so play and pause from the
           operator's remote reach the screen within a fraction of a second. */
        (async function pollLoop() {
            await poll();
            setTimeout(pollLoop, 400);
        })();

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
                ['idleState', 'photoFrame', 'videoFrame', 'photoGrid', 'uploaderTag', 'liveBadge',
                    'videoBadge', 'slideCounter'].forEach(id => {
                    const n = document.getElementById(id);
                    if (n) n.style.display = 'none';
                });
                const v = document.getElementById('liveVideo');
                if (v) { v.pause(); }
                clearTimeout(queueTimer);
                setQrScreen(false);
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
                        resetPlayerView();
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

        /* ── Confetti bursting up from the lower middle of a show, in the
              event's colours. ── */
        function makeConfetti(canvas, host) {
            let frame = null;

            function stop() {
                cancelAnimationFrame(frame);
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            }

            function burst() {
                stop();
                const w = canvas.width = host.clientWidth;
                const h = canvas.height = host.clientHeight;
                const ctx = canvas.getContext('2d');
                const colors = [getComputedStyle(host).getPropertyValue('--acc').trim() || '#FFD700',
                    getComputedStyle(host).getPropertyValue('--p').trim() || '#FF3D00', '#ffffff', '#FFD700', '#22C55E', '#3B82F6'];
                const pieces = Array.from({ length: 220 }, () => {
                    const angle = -Math.PI / 2 + (Math.random() - .5) * Math.PI * .9;
                    const speed = (Math.random() * .55 + .45) * h * .028;
                    return {
                        x: w / 2 + (Math.random() - .5) * w * .3,
                        y: h * .62,
                        vx: Math.cos(angle) * speed,
                        vy: Math.sin(angle) * speed,
                        size: Math.random() * 10 + 6,
                        spin: Math.random() * Math.PI,
                        spinSpeed: (Math.random() - .5) * .3,
                        color: colors[Math.floor(Math.random() * colors.length)],
                    };
                });
                const started = performance.now();

                function draw(now) {
                    ctx.clearRect(0, 0, w, h);
                    const age = now - started;
                    pieces.forEach(p => {
                        p.vy += h * .0006;
                        p.vx *= .992;
                        p.x += p.vx;
                        p.y += p.vy;
                        p.spin += p.spinSpeed;
                        ctx.save();
                        ctx.globalAlpha = Math.max(0, 1 - age / 6000);
                        ctx.translate(p.x, p.y);
                        ctx.rotate(p.spin);
                        ctx.fillStyle = p.color;
                        ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
                        ctx.restore();
                    });
                    if (age < 6000) frame = requestAnimationFrame(draw);
                    else ctx.clearRect(0, 0, w, h);
                }
                frame = requestAnimationFrame(draw);
            }

            return { burst, stop };
        }

        /* ── Lottery show: the server says which show is on and how far into it
              the room is; the screen runs the phases on its own clock from
              there (intro, countdown, the reel landing on the winner, result),
              and re-syncs on every poll. ── */
        (function () {
            if (!HAS_LOTTERY) return;

            const overlay = document.getElementById('lotOverlay');
            if (!overlay) return;

            const el = id => document.getElementById(id);
            const headline = el('lotHeadline'), prize = el('lotPrize'), prizeText = el('lotPrizeText'),
                countNum = el('lotCountNum'), ringFill = el('lotRingFill'),
                reel = el('lotReel'), strip = el('lotReelStrip'),
                result = el('lotResult'), eyebrow = el('lotEyebrow'), nameEl = el('lotName'), detail = el('lotDetail'),
                boardGrid = el('lotBoardGrid'), foot = el('lotFoot'), progress = el('lotProgress'), confettiCanvas = el('lotConfetti');
            const scenes = overlay.querySelectorAll('[data-scene]');
            const RING = 339.292;
            const confetti = makeConfetti(confettiCanvas, overlay);

            let show = null;
            let revision = null;
            let origin = 0;
            let scene = null;
            let lastCount = null;
            let revealedRevision = null;
            let closedRevision = null;
            let reelLength = 0;
            let reelItems = [];
            let leaveTimer = null;
            let pollTimer = null;

            function phaseOf(s, elapsed) {
                const revealAt = s.countdown_ms + s.drawing_ms;
                if (elapsed < 0) return 'intro';
                if (elapsed < s.countdown_ms) return 'countdown';
                if (elapsed < revealAt) return 'drawing';
                if (s.result_ms === 0 || elapsed < revealAt + s.result_ms) return 'result';
                return null;
            }

            function setScene(name) {
                if (scene === name) return;
                scene = name;
                scenes.forEach(node => node.hidden = node.dataset.scene !== name);
                if (name === 'result') fitName();
                if (name === 'board') fitBoard();
            }

            function open() {
                clearTimeout(leaveTimer);
                overlay.classList.remove('is-leaving');
                overlay.hidden = false;
            }

            function close() {
                closedRevision = revision;
                show = null;
                scene = null;
                overlay.classList.add('is-leaving');
                clearTimeout(leaveTimer);
                leaveTimer = setTimeout(() => {
                    overlay.hidden = true;
                    overlay.classList.remove('is-leaving');
                    confetti.stop();
                }, 500);
            }

            function buildReel() {
                const winner = show.winner ? show.winner.name : '';
                const pool = show.reel.length ? show.reel : [winner];
                reelLength = Math.max(24, Math.round(show.drawing_ms / 1000 * 9));
                const names = [];
                for (let i = 0; i < reelLength; i++) names.push(pool[i % pool.length]);
                names.push(winner);
                names.push(pool[reelLength % pool.length], pool[(reelLength + 1) % pool.length]);

                strip.replaceChildren(...names.map(name => {
                    const item = document.createElement('div');
                    item.className = 'lot-reel-item';
                    item.textContent = name;
                    return item;
                }));
                reelItems = Array.from(strip.children);
            }

            function moveReel(t) {
                const row = reel.clientHeight / 3;
                const eased = 1 - Math.pow(1 - Math.min(1, Math.max(0, t)), 4);
                const position = eased * reelLength;
                const speed = 4 * Math.pow(1 - t, 3) * reelLength / Math.max(.001, show.drawing_ms / 1000);
                strip.style.transform = `translate3d(0, ${(1 - position) * row}px, 0)`;
                strip.style.filter = speed > 14 ? `blur(${Math.min(5, speed / 12).toFixed(1)}px)` : 'none';
                const center = Math.round(position);
                reelItems.forEach((item, index) => item.classList.toggle('is-center', index === center));
            }

            function buildResult() {
                const winner = show.winner || {};
                eyebrow.textContent = winner.rank > 1 ? `Winner #${winner.rank}` : 'Winner';
                nameEl.textContent = winner.name || '';
                detail.textContent = winner.detail || '';
            }

            function fitName() {
                nameEl.classList.remove('is-wrapped');
                nameEl.style.fontSize = '';
                const available = nameEl.clientWidth;
                const needed = nameEl.scrollWidth;
                if (needed > available && available > 0) {
                    const size = parseFloat(getComputedStyle(nameEl).fontSize) * available / needed * .96;
                    if (size < 44) {
                        nameEl.style.fontSize = '44px';
                        nameEl.classList.add('is-wrapped');
                    } else {
                        nameEl.style.fontSize = Math.floor(size) + 'px';
                    }
                }
            }

            /* Tries one to three columns, shrinks each until every name fits
               the stage in full, and keeps the layout with the largest text. */
            function fitBoard() {
                const scene = boardGrid.parentElement;
                if (scene.hidden || !boardGrid.children.length) return;
                const available = scene.clientHeight;
                const names = boardGrid.querySelectorAll('.lot-board-name, .lot-board-prize');
                const apply = (cols, size) => {
                    boardGrid.style.setProperty('--cols', cols);
                    boardGrid.style.setProperty('--bsz', size + 'px');
                };
                const sizeFor = cols => {
                    let size = Math.min(96, available * .14);
                    apply(cols, size);
                    for (let i = 0; i < 8; i++) {
                        let overflow = boardGrid.scrollHeight / available;
                        names.forEach(n => overflow = Math.max(overflow, n.scrollWidth / Math.max(1, n.clientWidth)));
                        if (overflow <= 1.005 || size <= 12) break;
                        size = Math.max(12, size / overflow * .97);
                        apply(cols, size);
                    }
                    return size;
                };

                let best = { cols: 1, size: 0 };
                for (let cols = 1; cols <= Math.min(3, boardGrid.children.length); cols++) {
                    const size = sizeFor(cols);
                    if (size > best.size * 1.05) best = { cols, size };
                }
                apply(best.cols, best.size);
            }

            function buildBoard() {
                const winners = show.winners || [];
                boardGrid.replaceChildren(...winners.map((winner, index) => {
                    const card = document.createElement('div');
                    card.className = 'lot-board-card';
                    card.style.animationDelay = (index * 0.08) + 's';
                    const rank = document.createElement('div');
                    rank.className = 'lot-board-rank';
                    rank.textContent = winner.rank ?? index + 1;
                    const text = document.createElement('div');
                    text.className = 'lot-board-text';
                    const name = document.createElement('div');
                    name.className = 'lot-board-name';
                    name.textContent = winner.name;
                    text.appendChild(name);
                    if (winner.prize) {
                        const prizeLine = document.createElement('div');
                        prizeLine.className = 'lot-board-prize';
                        prizeLine.textContent = winner.prize;
                        text.appendChild(prizeLine);
                    }
                    card.append(rank, text);
                    return card;
                }));
            }

            function build() {
                headline.textContent = show.type === 'board' ? `${show.headline} · Winners` : show.headline;
                const prizeName = show.type !== 'board' && show.winner ? show.winner.prize : null;
                prize.hidden = !prizeName;
                prizeText.textContent = prizeName || '';
                foot.textContent = show.entries ? `${show.entries.toLocaleString()} ${show.entries === 1 ? 'entry' : 'entries'} in the draw` : '';
                lastCount = null;
                scene = null;
                if (show.type === 'draw') buildReel();
                if (show.type === 'board') buildBoard();
                else buildResult();
            }

            function reveal() {
                result.classList.remove('is-revealed');
                result.offsetHeight;
                result.classList.add('is-revealed');
                if (show.confetti) confetti.burst();
            }

            function render(now) {
                const elapsed = now - origin;
                const phase = phaseOf(show, elapsed);
                if (!phase) {
                    close();
                    return;
                }

                foot.hidden = !foot.textContent || phase === 'result';

                if (phase === 'intro') {
                    setScene('intro');
                } else if (phase === 'countdown') {
                    setScene('countdown');
                    const left = show.countdown_ms - elapsed;
                    const count = Math.max(1, Math.ceil(left / 1000));
                    if (count !== lastCount) {
                        lastCount = count;
                        countNum.textContent = count;
                        countNum.classList.remove('is-tick');
                        countNum.offsetHeight;
                        countNum.classList.add('is-tick');
                    }
                    ringFill.style.strokeDashoffset = (RING * (1 - left / show.countdown_ms)).toFixed(2);
                } else if (phase === 'drawing') {
                    setScene('drawing');
                    moveReel((elapsed - show.countdown_ms) / show.drawing_ms);
                } else {
                    setScene(show.type === 'board' ? 'board' : 'result');
                    if (revealedRevision !== revision && show.type !== 'board') {
                        revealedRevision = revision;
                        reveal();
                    }
                    if (show.result_ms > 0) {
                        const shown = elapsed - show.countdown_ms - show.drawing_ms;
                        progress.style.width = Math.max(0, 100 - shown / show.result_ms * 100) + '%';
                    } else {
                        progress.style.width = '0';
                    }
                }

                if (phase !== 'result') {
                    progress.style.width = '0';
                }
            }

            function frame(now) {
                if (show) render(now);
                requestAnimationFrame(frame);
            }

            function apply(d) {
                if (!d.show) {
                    if (show) close();
                    revision = d.revision;
                    return;
                }

                /* A show this screen has already timed out is not brought back
                   by a poll that reached the server a moment before it did. */
                if (!show && d.revision === closedRevision) return;

                const nextOrigin = performance.now() - d.show.elapsed_ms;

                if (!show || d.revision !== revision) {
                    const wasRevealed = show && revealedRevision === revision && d.show.type === show.type
                        && JSON.stringify(d.show.winner) === JSON.stringify(show.winner);
                    revision = d.revision;
                    show = d.show;
                    origin = nextOrigin;
                    if (wasRevealed) revealedRevision = revision;
                    open();
                    build();
                    return;
                }

                if (Math.abs(nextOrigin - origin) > 350) origin = nextOrigin;

                if (show.type === 'board' && JSON.stringify(d.show.winners) !== JSON.stringify(show.winners)) {
                    show.winners = d.show.winners;
                    buildBoard();
                    fitBoard();
                }
            }

            function schedule(ms) {
                clearTimeout(pollTimer);
                pollTimer = setTimeout(tick, ms);
            }

            async function tick() {
                try {
                    const res = await fetch(`/screen/${SLUG}/lottery/feed`, { cache: 'no-store' });
                    apply(await res.json());
                    schedule(show ? 600 : 1000);
                } catch (e) {
                    schedule(2000);
                }
            }

            window.addEventListener('resize', () => {
                if (scene === 'result') fitName();
                if (scene === 'board') fitBoard();
            });

            requestAnimationFrame(frame);
            tick();
        })();

        /* ── Vote show: live results that move with every vote, the podium,
              and the winner reveal, which the screen runs on its own clock
              (intro, countdown, winner) from how far into it the room is and
              re-syncs on every poll. ── */
        (function () {
            if (!HAS_VOTING) return;

            const overlay = document.getElementById('votOverlay');
            if (!overlay) return;

            const el = id => document.getElementById(id);
            const headline = el('votHeadline'), statusChip = el('votStatus'), statusText = el('votStatusText'),
                chart = el('votChart'), qr = el('votQr'),
                countNum = el('votCountNum'), ringFill = el('votRingFill'),
                result = el('votResult'), eyebrow = el('votEyebrow'), winnersBox = el('votWinners'),
                podium = el('votPodium'), foot = el('votFoot'), progress = el('votProgress');
            const scenes = overlay.querySelectorAll('[data-scene]');
            const confetti = makeConfetti(el('votConfetti'), overlay);
            const RING = 339.292;
            const rows = new Map();

            let show = null;
            let revision = null;
            let origin = 0;
            let scene = null;
            let lastCount = null;
            let revealedRevision = null;
            let closedRevision = null;
            let podiumOrder = null;
            let podiumFinal = false;
            let podiumTimer = null;
            let leaveTimer = null;
            let pollTimer = null;

            function phaseOf(s, elapsed) {
                if (elapsed < 0) return 'intro';
                if (elapsed < s.countdown_ms) return 'countdown';
                if (s.result_ms === 0 || elapsed < s.countdown_ms + s.result_ms) return 'result';
                return null;
            }

            function votesText(votes) {
                return `${votes.toLocaleString()} ${votes === 1 ? 'vote' : 'votes'}`;
            }

            function scoreText(athlete) {
                return [
                    athlete.votes !== null ? votesText(athlete.votes) : null,
                    athlete.percent !== null ? athlete.percent + '%' : null,
                ].filter(Boolean).join(' · ');
            }

            function fitText(node, min) {
                node.classList.remove('is-wrapped');
                node.style.fontSize = '';
                const available = node.clientWidth;
                const needed = node.scrollWidth;
                if (needed > available && available > 0) {
                    const size = parseFloat(getComputedStyle(node).fontSize) * available / needed * .96;
                    if (size < min) {
                        node.style.fontSize = min + 'px';
                        node.classList.add('is-wrapped');
                    } else {
                        node.style.fontSize = Math.floor(size) + 'px';
                    }
                }
            }

            function setScene(name) {
                if (scene === name) return;
                scene = name;
                scenes.forEach(node => node.hidden = node.dataset.scene !== name);
                if (name === 'live') {
                    layoutChart();
                    requestAnimationFrame(() => requestAnimationFrame(paintBars));
                }
                if (name === 'result') winnersBox.querySelectorAll('.vot-winner-name').forEach(n => fitText(n, 40));
                if (name === 'podium') fitPodium();
            }

            function open() {
                clearTimeout(leaveTimer);
                overlay.classList.remove('is-leaving');
                overlay.hidden = false;
            }

            function close() {
                closedRevision = revision;
                show = null;
                scene = null;
                overlay.classList.add('is-leaving');
                clearTimeout(leaveTimer);
                leaveTimer = setTimeout(() => {
                    overlay.hidden = true;
                    overlay.classList.remove('is-leaving');
                    confetti.stop();
                }, 500);
            }

            function renderHead() {
                headline.textContent = show.headline;
                const final = !show.open;
                statusChip.hidden = show.type === 'reveal';
                statusChip.classList.toggle('is-final', final);
                statusText.textContent = show.type === 'podium'
                    ? (final ? 'Final podium' : 'Top 3 so far')
                    : (final ? 'Final results' : 'Voting open');
                foot.textContent = show.total !== null ? votesText(show.total) : '';
                qr.hidden = !show.qr;
            }

            /* ── Live results: one row per athlete, moved into place when the
                  order changes, with the counts rolling up to each new total. ── */
            function createRow() {
                const row = document.createElement('div');
                row.className = 'vot-row';
                row.innerHTML = '<div class="vot-rank"></div><div class="vot-main"><div class="vot-line">'
                    + '<span class="vot-name"></span><span class="vot-pos"></span>'
                    + '<span class="vot-value"><span class="vot-votes"></span><small class="vot-pct"></small></span>'
                    + '</div><div class="vot-track"><div class="vot-fill"></div></div></div>';
                const q = selector => row.querySelector(selector);

                return {
                    el: row, rank: q('.vot-rank'), name: q('.vot-name'), pos: q('.vot-pos'),
                    value: q('.vot-value'), votes: q('.vot-votes'), pct: q('.vot-pct'), fill: q('.vot-fill'),
                    share: 0, shown: 0, target: null, tween: null, settling: null,
                };
            }

            function rollVotes(row, target) {
                if (row.target === target) return;
                const from = row.shown;
                const started = performance.now();
                if (row.target !== null && target > row.target) {
                    row.el.classList.remove('is-bump');
                    row.el.offsetHeight;
                    row.el.classList.add('is-bump');
                }
                row.target = target;
                cancelAnimationFrame(row.tween);
                const step = now => {
                    const t = Math.min(1, (now - started) / 800);
                    row.shown = Math.round(from + (target - from) * (1 - Math.pow(1 - t, 3)));
                    row.votes.textContent = row.shown.toLocaleString();
                    if (t < 1) row.tween = requestAnimationFrame(step);
                };
                row.tween = requestAnimationFrame(step);
            }

            function setScore(row, athlete) {
                row.value.hidden = athlete.votes === null && athlete.percent === null;
                if (athlete.votes !== null) {
                    rollVotes(row, athlete.votes);
                    row.pct.textContent = athlete.percent !== null ? athlete.percent + '%' : '';
                    return;
                }
                cancelAnimationFrame(row.tween);
                row.target = null;
                row.shown = 0;
                row.votes.textContent = athlete.percent !== null ? athlete.percent + '%' : '';
                row.pct.textContent = '';
            }

            function clearRows() {
                rows.forEach(row => {
                    cancelAnimationFrame(row.tween);
                    clearTimeout(row.settling);
                    row.el.remove();
                });
                rows.clear();
            }

            function renderChart() {
                const list = show.standings;
                const keys = new Set(list.map(athlete => athlete.key));
                rows.forEach((row, key) => {
                    if (!keys.has(key)) {
                        cancelAnimationFrame(row.tween);
                        row.el.remove();
                        rows.delete(key);
                    }
                });

                list.forEach((athlete, index) => {
                    let row = rows.get(athlete.key);
                    if (!row) {
                        row = createRow();
                        rows.set(athlete.key, row);
                        row.el.classList.add('is-entering');
                        row.el.style.setProperty('--d', (index * .07).toFixed(2) + 's');
                        chart.appendChild(row.el);
                    }
                    const medal = athlete.share > 0 && athlete.rank <= 3 ? athlete.rank : 0;
                    [1, 2, 3].forEach(place => row.el.classList.toggle('is-r' + place, medal === place));
                    row.rank.textContent = athlete.rank;
                    row.name.textContent = athlete.name;
                    row.pos.textContent = athlete.position || '';
                    row.pos.hidden = !athlete.position;
                    row.share = athlete.share;
                    row.index = index;
                    setScore(row, athlete);
                });

                layoutChart();
                if (scene === 'live') paintBars();
            }

            function paintBars() {
                rows.forEach(row => row.fill.style.width = (row.share * 100).toFixed(2) + '%');
            }

            /* Rows share the stage height, up to a fifth of it each, and sit
               centred as a block when there are only a few. */
            function layoutChart() {
                const height = chart.clientHeight;
                if (!height || !rows.size) return;
                const rowHeight = Math.min(height / rows.size, height * .2);
                const offset = Math.max(0, (height - rowHeight * rows.size) / 2);
                chart.style.setProperty('--row', rowHeight.toFixed(1) + 'px');
                rows.forEach(row => {
                    row.el.style.transform = `translate3d(0, ${(offset + row.index * rowHeight).toFixed(1)}px, 0)`;
                    if (row.el.classList.contains('is-entering') && !row.settling) {
                        row.settling = setTimeout(() => row.el.classList.remove('is-entering'), 900 + row.index * 70);
                    }
                });
            }

            /* ── Winner reveal ── */
            function buildResult() {
                const winners = show.winners || [];
                eyebrow.textContent = winners.length > 1 ? "It's a tie" : 'Winner';
                winnersBox.classList.toggle('is-tie', winners.length > 1);
                winnersBox.replaceChildren(...winners.map(winner => {
                    const box = document.createElement('div');
                    box.className = 'vot-winner';
                    const name = document.createElement('div');
                    name.className = 'vot-winner-name';
                    name.textContent = winner.name;
                    const detail = document.createElement('div');
                    detail.className = 'vot-winner-detail';
                    detail.textContent = [winner.position, scoreText(winner)].filter(Boolean).join(' · ');
                    box.append(name, detail);
                    return box;
                }));
            }

            function reveal() {
                result.classList.remove('is-revealed');
                result.offsetHeight;
                result.classList.add('is-revealed');
                if (show.confetti) confetti.burst();
            }

            /* ── Podium: the top three places, ties sharing a step, each step
                  showing its share of the votes (or the count when shares are
                  hidden) rolling up as the step rises. ── */
            const pillars = new Map();

            function pillarOf(place) {
                if (!pillars.has(place)) {
                    pillars.set(place, {
                        value: place.querySelector('.vot-pillar-value'),
                        unit: place.querySelector('.vot-pillar-unit'),
                        key: null, shown: 0, frame: null, timer: null,
                    });
                }
                return pillars.get(place);
            }

            function setPillar(place, athlete, rising) {
                const pillar = pillarOf(place);
                const rank = Number(place.dataset.place);
                const target = !athlete ? null
                    : athlete.percent !== null ? { n: athlete.percent, suffix: '%', roll: true }
                    : athlete.votes !== null ? { n: athlete.votes, suffix: '', roll: true }
                    : { n: rank, suffix: '', roll: false };
                const key = target ? `${target.n}${target.suffix}` : null;
                if (!rising && pillar.key === key) return;

                pillar.key = key;
                clearTimeout(pillar.timer);
                cancelAnimationFrame(pillar.frame);
                pillar.unit.hidden = !athlete || athlete.percent !== null || athlete.votes === null;
                pillar.unit.textContent = athlete && athlete.votes === 1 ? 'vote' : 'votes';
                if (!target || !target.roll) {
                    pillar.shown = 0;
                    pillar.value.textContent = target ? target.n : '';
                    return;
                }

                const from = rising ? 0 : pillar.shown;
                const paint = n => {
                    pillar.shown = n;
                    pillar.value.textContent = n.toLocaleString() + target.suffix;
                };
                const roll = () => {
                    const started = performance.now();
                    const step = now => {
                        const t = Math.min(1, (now - started) / 1200);
                        paint(Math.round(from + (target.n - from) * (1 - Math.pow(1 - t, 3))));
                        if (t < 1) pillar.frame = requestAnimationFrame(step);
                    };
                    pillar.frame = requestAnimationFrame(step);
                };
                paint(from);
                if (rising) pillar.timer = setTimeout(roll, { 1: 1000, 2: 700, 3: 400 }[rank]);
                else roll();
            }

            function renderPodium() {
                const places = { 1: [], 2: [], 3: [] };
                show.standings.forEach(athlete => {
                    if (places[athlete.rank]) places[athlete.rank].push(athlete);
                });

                const order = JSON.stringify([1, 2, 3].map(rank => places[rank].map(athlete => athlete.key)));
                const rising = order !== podiumOrder;

                podium.querySelectorAll('[data-place]').forEach(place => {
                    const athletes = places[place.dataset.place];
                    place.classList.toggle('is-empty', !athletes.length);
                    place.querySelector('.vot-place-names').replaceChildren(...athletes.map(athlete => {
                        const name = document.createElement('div');
                        name.className = 'vot-place-name';
                        name.replaceChildren(...athlete.name.split(/\s+/).flatMap((word, index) => {
                            const span = document.createElement('span');
                            span.textContent = word;
                            return index ? [' ', span] : [span];
                        }));
                        return name;
                    }));
                    setPillar(place, athletes[0], rising);
                });

                if (rising) {
                    podiumOrder = order;
                    podium.classList.remove('is-rising');
                    podium.offsetHeight;
                    podium.classList.add('is-rising');
                }

                /* The final podium is celebrated once its steps have risen,
                   and again only if it changes. */
                const final = !show.open;
                if (final && show.confetti && (rising || !podiumFinal)) {
                    clearTimeout(podiumTimer);
                    podiumTimer = setTimeout(() => {
                        if (show && show.type === 'podium') confetti.burst();
                    }, rising ? 1500 : 0);
                }
                podiumFinal = final;
                if (scene === 'podium') fitPodium();
            }

            /* The names scale down until every place fits above its step on
               short screens. */
            function fitPodium() {
                let scale = 1;
                podium.style.setProperty('--k', scale);
                sizePodiumNames();
                while (scale > .5 && podiumOverflows()) {
                    scale -= .06;
                    podium.style.setProperty('--k', scale.toFixed(2));
                    sizePodiumNames();
                }
            }

            function podiumOverflows() {
                return Array.from(podium.querySelectorAll('.vot-place:not(.is-empty) .vot-place-names'))
                    .some(names => names.offsetTop < -1);
            }

            /* Names break between words, sized so the longest word fits its
               step; all take the smallest size, with first place a touch
               larger when its step has the room. */
            function sizePodiumNames() {
                const names = Array.from(podium.querySelectorAll('.vot-place-name'));
                if (!names.length) return;
                const fitted = names.map(name => {
                    name.style.fontSize = '';
                    const size = parseFloat(getComputedStyle(name).fontSize);
                    const widest = Math.max(...Array.from(name.children, word => word.offsetWidth));
                    const available = name.clientWidth;
                    return widest > available && available > 0 ? Math.max(18, size * available / widest * .96) : size;
                });
                const size = Math.min(...fitted);
                names.forEach((name, index) => {
                    const first = name.closest('[data-place="1"]') !== null;
                    name.style.fontSize = Math.floor(first ? Math.min(fitted[index], size * 1.15) : size) + 'px';
                });
            }

            function build() {
                scene = null;
                lastCount = null;
                podiumOrder = null;
                podiumFinal = false;
                clearTimeout(podiumTimer);
                clearRows();
                renderHead();
                if (show.type === 'live') renderChart();
                else if (show.type === 'podium') renderPodium();
                else buildResult();
            }

            function render(now) {
                const elapsed = now - origin;
                const phase = phaseOf(show, elapsed);
                if (!phase) {
                    close();
                    return;
                }

                foot.hidden = !foot.textContent || phase !== 'result';

                if (show.type === 'live') {
                    setScene('live');
                } else if (show.type === 'podium') {
                    setScene('podium');
                } else if (phase === 'intro') {
                    setScene('intro');
                } else if (phase === 'countdown') {
                    setScene('countdown');
                    const left = show.countdown_ms - elapsed;
                    const count = Math.max(1, Math.ceil(left / 1000));
                    if (count !== lastCount) {
                        lastCount = count;
                        countNum.textContent = count;
                        countNum.classList.remove('is-tick');
                        countNum.offsetHeight;
                        countNum.classList.add('is-tick');
                    }
                    ringFill.style.strokeDashoffset = (RING * (1 - left / show.countdown_ms)).toFixed(2);
                } else {
                    setScene('result');
                    if (revealedRevision !== revision) {
                        revealedRevision = revision;
                        reveal();
                    }
                }

                if (show.type === 'reveal' && phase === 'result' && show.result_ms > 0) {
                    const shown = elapsed - show.countdown_ms;
                    progress.style.width = Math.max(0, 100 - shown / show.result_ms * 100) + '%';
                } else {
                    progress.style.width = '0';
                }
            }

            function frame(now) {
                if (show) render(now);
                requestAnimationFrame(frame);
            }

            function apply(d) {
                if (!d.show) {
                    if (show) close();
                    revision = d.revision;
                    return;
                }

                /* A show this screen has already timed out is not brought back
                   by a poll that reached the server a moment before it did. */
                if (!show && d.revision === closedRevision) return;

                const nextOrigin = performance.now() - d.show.elapsed_ms;

                if (!show || d.revision !== revision) {
                    const wasRevealed = show && revealedRevision === revision && show.type === 'reveal' && d.show.type === 'reveal'
                        && JSON.stringify(d.show.winners) === JSON.stringify(show.winners);
                    revision = d.revision;
                    show = d.show;
                    origin = nextOrigin;
                    if (wasRevealed) revealedRevision = revision;
                    open();
                    build();
                    return;
                }

                if (Math.abs(nextOrigin - origin) > 350) origin = nextOrigin;

                const winnersBefore = JSON.stringify(show.winners);
                show = d.show;
                renderHead();
                if (show.type === 'live') renderChart();
                if (show.type === 'podium') renderPodium();
                if (show.type === 'reveal' && JSON.stringify(show.winners) !== winnersBefore) {
                    buildResult();
                    if (scene === 'result') winnersBox.querySelectorAll('.vot-winner-name').forEach(n => fitText(n, 40));
                }
            }

            function schedule(ms) {
                clearTimeout(pollTimer);
                pollTimer = setTimeout(tick, ms);
            }

            async function tick() {
                try {
                    const res = await fetch(`/screen/${SLUG}/voting/feed`, { cache: 'no-store' });
                    apply(await res.json());
                    schedule(show ? 700 : 1000);
                } catch (e) {
                    schedule(2000);
                }
            }

            window.addEventListener('resize', () => {
                if (scene === 'live') layoutChart();
                if (scene === 'result') winnersBox.querySelectorAll('.vot-winner-name').forEach(n => fitText(n, 40));
                if (scene === 'podium') fitPodium();
            });

            requestAnimationFrame(frame);
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
                var box = document.getElementById('safeArea');
                if (!title || !idle || !box) {
                    return;
                }
                title.style.fontSize = '';
                if (idle.style.display === 'none' || idle.offsetParent === null) {
                    return;
                }
                var cs = getComputedStyle(idle);
                var avail = box.clientWidth
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
