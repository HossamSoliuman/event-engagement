<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="{{ $event->primary_color }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>{{ $event->name }}</title>
    <link 
        @php
$fontH = $event->font_heading ?: 'Syne';
        $fontB = $event->font_body    ?: 'DM Sans'; @endphp <link
        href="https://fonts.googleapis.com/css2?family={{ urlencode($fontH) }}:wght@700;800&family={{ urlencode($fontB) }}:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    @php $lightBg = $event->hasLightBackground(); @endphp
    <style>
        :root {
            --p: {{ $event->primary_color }};
            --bg: {{ $event->secondary_color }};
            --acc: {{ $event->accent_color }};
            --on-p: {{ $event->readableInk($event->primary_color) }};
            --on-acc: {{ $event->readableInk($event->accent_color) }};
            --text: {{ $lightBg ? '#16162a' : '#f0f0f8' }};
            --muted: {{ $lightBg ? 'rgba(20, 20, 40, .6)' : 'rgba(240, 240, 248, .55)' }};
            --border: {{ $lightBg ? 'rgba(0, 0, 0, .14)' : 'rgba(255, 255, 255, .12)' }};
            --card: {{ $lightBg ? 'rgba(0, 0, 0, .05)' : 'rgba(255, 255, 255, .07)' }};
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
        .lucide-icon { width: 1em; height: 1em; vertical-align: text-bottom; stroke-width: 2px; }
        .s-icon .lucide-icon, .uz-icon .lucide-icon, .cl-card-media .lucide-icon, .tile-icon .lucide-icon { stroke-width: 1.5px; }
        @keyframes ebspin { to { transform: rotate(360deg); } }
        .lucide-spin { animation: ebspin .9s linear infinite; }

        #screen-fotobomb .mod-header-title .lucide-icon, #screen-fotobomb .section-title .lucide-icon, #screen-fotobomb .s-icon .lucide-icon, #screen-fotobomb .uz-icon .lucide-icon { color: var(--p); }
        #screen-voting .mod-header-title .lucide-icon, #screen-voting .section-title .lucide-icon, #screen-voting .s-icon .lucide-icon { color: var(--acc); }
        #screen-lottery .mod-header-title .lucide-icon, #screen-lottery .section-title .lucide-icon, #screen-lottery .s-icon .lucide-icon { color: #6366f1; }
        #screen-membership .mod-header-title .lucide-icon, #screen-membership .section-title .lucide-icon, #screen-membership .s-icon .lucide-icon { color: #22c55e; }
        #screen-quiz .mod-header-title .lucide-icon, #screen-quiz .section-title .lucide-icon, #screen-quiz .s-icon .lucide-icon { color: #f59e0b; }
        #screen-fanclash .mod-header-title .lucide-icon { color: #ef4444; }

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

        .tile-quiz {
            background: linear-gradient(160deg, #1c1200 0%, #0a0800 100%);
            --tile-accent: #f59e0b
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
            color: var(--on-p)
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
            color: var(--on-p);
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
            color: var(--on-p);
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
            background: rgba(0, 0, 0, .3);
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
            color: var(--on-p)
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
            background: rgba(99, 102, 241, .15);
            color: #818cf8
        }

        .duration-badge.over {
            background: rgba(239, 68, 68, .15);
            color: #f87171
        }

        /* ── Clean / Sponsor landing style ─────────────────────────── */
        #landing.landing-clean {
            position: relative;
            --sp: clamp(18px, 4.6vw, 30px);
            background: var(--bg);
            background-image: radial-gradient(ellipse at 50% -12%, color-mix(in srgb, var(--p) 16%, transparent) 0%, transparent 58%)
        }

        .cl-watermark {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none
        }

        .cl-watermark img {
            position: absolute;
            right: -16%;
            top: 5%;
            width: 92vw;
            max-width: 540px;
            opacity: .045;
            filter: brightness(0) invert(1);
            transform: rotate(-8deg)
        }

        .cl-header {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            padding: max(env(safe-area-inset-top, 0px), 46px) 22px 0;
            flex-shrink: 0
        }

        .cl-logo {
            height: 42px;
            width: auto;
            max-width: 38vw;
            object-fit: contain;
            display: block
        }

        .cl-divider {
            width: 1px;
            height: 30px;
            background: rgba(255, 255, 255, .3);
            flex-shrink: 0
        }

        .cl-wordmark {
            font-family: var(--font-h);
            font-size: clamp(14px, 4.4vw, 19px);
            font-weight: 800;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: #fff;
            line-height: 1.3
        }

        .cl-hero {
            position: relative;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            padding: var(--sp) 18px
        }

        .cl-hero-title {
            font-family: var(--font-h);
            font-weight: 600;
            font-size: clamp(14px, 4vw, 18px);
            line-height: 1.35;
            color: #fff
        }

        .cl-hero-title strong {
            font-weight: 800
        }

        .cl-hero-sub {
            margin-top: 6px;
            font-size: clamp(12px, 3.4vw, 14px);
            line-height: 1.5;
            color: var(--muted)
        }

        .cl-hero-sub strong {
            font-weight: 700;
            color: #fff
        }

        .cl-grid {
            position: relative;
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            padding: var(--sp) 18px;
            align-content: center
        }

        .cl-card {
            position: relative;
            display: flex;
            flex-direction: column;
            aspect-ratio: 1.42;
            border-radius: 16px;
            background: var(--cl-card-bg, #fff);
            color: var(--cl-card-ink, #101828);
            box-shadow: 0 14px 34px rgba(0, 0, 0, .32);
            cursor: pointer;
            overflow: hidden;
            text-align: center;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            transition: transform .18s ease
        }

        .cl-card:active {
            transform: scale(.965)
        }

        .cl-card-media {
            flex: 1;
            min-height: 0;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden
        }

        .cl-card-media img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block
        }

        .cl-card-media .lucide-icon {
            width: 36px;
            height: 36px;
            color: var(--cl-icon, var(--p))
        }

        .cl-grid .cl-card:nth-child(odd):last-child {
            grid-column: 1 / -1;
            aspect-ratio: 3
        }

        .cl-card-foot {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            padding: 10px 12px 12px
        }

        .cl-card-rule {
            width: 100%;
            height: 1px;
            background: currentColor;
            opacity: .18;
            margin: 0 0 8px;
            flex-shrink: 0
        }

        .cl-card-label {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .15em;
            text-transform: uppercase;
            line-height: 1.35;
            opacity: .85
        }

        .cl-card-sub {
            font-size: 9px;
            letter-spacing: .08em;
            text-transform: uppercase;
            opacity: .5;
            margin-top: 3px
        }

        .cl-hashtag {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-family: var(--font-h);
            font-weight: 800;
            font-size: clamp(19px, 5.6vw, 25px);
            color: #fff;
            padding: var(--sp) 20px
        }

        .cl-footer {
            position: relative;
            text-align: center;
            padding: var(--sp) 20px max(env(safe-area-inset-bottom, 14px), 18px);
            font-size: 11px;
            color: var(--muted);
            flex-shrink: 0
        }

        .cl-footer a {
            color: var(--muted);
            text-decoration: underline;
            text-underline-offset: 2px
        }

        @keyframes clRise {
            from {
                opacity: 0;
                transform: translateY(14px)
            }

            to {
                opacity: 1;
                transform: none
            }
        }

        .landing-clean .cl-header,
        .landing-clean .cl-hero,
        .landing-clean .cl-card,
        .landing-clean .cl-hashtag,
        .landing-clean .cl-footer {
            animation: clRise .5s cubic-bezier(.22, 1, .36, 1) both
        }

        .landing-clean .cl-hero {
            animation-delay: .06s
        }

        .landing-clean .cl-card:nth-child(1) {
            animation-delay: .12s
        }

        .landing-clean .cl-card:nth-child(2) {
            animation-delay: .18s
        }

        .landing-clean .cl-card:nth-child(3) {
            animation-delay: .24s
        }

        .landing-clean .cl-card:nth-child(4) {
            animation-delay: .3s
        }

        .landing-clean .cl-card:nth-child(5) {
            animation-delay: .36s
        }

        .landing-clean .cl-hashtag {
            animation-delay: .42s
        }

        .landing-clean .cl-footer {
            animation-delay: .48s
        }

        @media (prefers-reduced-motion: reduce) {

            .landing-clean .cl-header,
            .landing-clean .cl-hero,
            .landing-clean .cl-card,
            .landing-clean .cl-hashtag,
            .landing-clean .cl-footer {
                animation: none
            }
        }
    </style>
</head>

<body>
    
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

    
    @php
        $isClean = true;
        $wordmark = $event->landing_wordmark ?: 'FAN EXPERIENCE';
        $heroTitle = $event->landing_hero_title;
        $heroSub = $event->landing_hero_sub;

        // Turn **phrase** into <strong>phrase</strong>; content is escaped first so it is safe to echo raw.
        $emphasize = function (?string $text): string {
            return preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', e((string) $text));
        };

        // Card background + auto-contrast ink for the clean tile style
        $cardInk = function (?string $hex): array {
            $hex = ltrim((string) $hex, '#');
            if (strlen($hex) === 3) {
                $hex = preg_replace('/(.)/', '$1$1', $hex);
            }
            if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
                return ['#ffffff', '#101828'];
            }
            $lum =
                (0.299 * hexdec(substr($hex, 0, 2)) +
                    0.587 * hexdec(substr($hex, 2, 2)) +
                    0.114 * hexdec(substr($hex, 4, 2))) /
                255;

            return ['#' . $hex, $lum > 0.55 ? '#101828' : '#ffffff'];
        };
    @endphp
    <div id="landing" @if ($isClean) class="landing-clean" @endif>
        @if ($isClean)
            @if ($event->logo_path)
                <div class="cl-watermark" aria-hidden="true"><img src="{{ $event->logo_url }}" alt=""></div>
            @endif

            <header class="cl-header">
                @if ($event->logo_path)
                    <img src="{{ $event->logo_url }}" class="cl-logo" alt="{{ $event->name }}">
                    <div class="cl-divider"></div>
                @endif
                <div class="cl-wordmark">{{ $wordmark }}</div>
            </header>

            <div class="cl-hero">
                <div class="cl-hero-title"
                    @unless ($heroTitle) data-html data-en="Your <strong>Fan Experience</strong> starts here." data-de="Deine <strong>Fan Experience</strong> startet hier." @endunless>
                    {!! $heroTitle ? $emphasize($heroTitle) : 'Your <strong>Fan Experience</strong> starts here.' !!}</div>
                <div class="cl-hero-sub"
                    @unless ($heroSub) data-html data-en="Be part of the show. <strong>Tap a tile</strong> to get started." data-de="Sei Teil des Erlebnisses. <strong>Tippe auf eine Kachel.</strong>" @endunless>
                    {!! $heroSub ? $emphasize($heroSub) : 'Be part of the show. <strong>Tap a tile</strong> to get started.' !!}</div>
            </div>

            <div class="cl-grid">
                @foreach ([['fotobomb', 'camera'], ['voting', 'trophy'], ['lottery', 'ticket'], ['membership', 'crown'], ['quiz', 'brain'], ['fanclash', 'swords']] as [$mod, $iconName])
                    @continue(!$event->{'module_' . $mod})
                    @php
                        $tc = $event->tileConfig($mod);
                        $imgUrl = $event->getTileImageUrl($mod);
                        if (!$imgUrl) {
                            $imgUrl = match (true) {
                                $mod === 'fotobomb' && !empty($event->sponsor_logo_path) => $event->sponsor_logo_url,
                                $mod === 'voting' && !empty($event->logo_path) => $event->logo_url,
                                default => null,
                            };
                        }
                        [$cardBg, $cardInkColor] = $cardInk($tc['bg_color'] ?? '');
                        $linkUrl = $tc['link_url'] ?? '';
                        $isLink = !empty($linkUrl);
                        $external = $tc['link_external'] ?? false;
                        $capsLabel = $tc['label'] ?: $event->{$mod . '_title'};
                    @endphp
                    <div class="cl-card"
                        style="--cl-card-bg:{{ $cardBg }};--cl-card-ink:{{ $cardInkColor }}{{ !empty($tc['bg_color']) ? ';--cl-icon:' . $cardInkColor : '' }}"
                        onclick="{{ $isLink
                            ? "window.open('" . addslashes($linkUrl) . "','" . ($external ? '_blank' : '_self') . "')"
                            : "openModule('{$mod}')" }}">
                        <div class="cl-card-media">
                            @if ($imgUrl)
                                <img src="{{ $imgUrl }}" alt="">
                            @else
                                <i data-lucide="{{ $iconName }}" class="lucide-icon"></i>
                            @endif
                        </div>
                        <div class="cl-card-foot">
                            <div class="cl-card-rule"></div>
                            <div class="cl-card-label">{{ $capsLabel }}</div>
                            @if (!empty($tc['sublabel']))
                                <div class="cl-card-sub">{{ $tc['sublabel'] }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="cl-hashtag">{{ $event->vidiwall_overlay_text ?: $event->name }}</div>
            <div class="cl-footer">© {{ now()->year }} EventBomb
                @if ($privacyUrl !== '#')
                    · <a href="{{ $privacyUrl }}" target="_blank" data-en="Privacy Policy"
                        data-de="Datenschutz">Privacy Policy</a>
                @endif
            </div>
        @else
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
            <div class="land-hero-text"
                @unless ($heroTitle) data-en="Your Fan Experience starts here." data-de="Deine Fan Experience startet hier." @endunless>
                {{ $heroTitle ?: 'Your Fan Experience starts here.' }}</div>
            <div class="land-hero-sub"
                @unless ($heroSub) data-en="Be part of the show. Tap a tile to get started." data-de="Sei Teil des Erlebnisses. Tippe auf eine Kachel." @endunless>
                {{ $heroSub ?: 'Be part of the show. Tap a tile to get started.' }}</div>
        </div>

        <div class="tile-grid">
            @foreach ([['fotobomb', '<i data-lucide="camera" class="lucide-icon"></i>', $event->fotobomb_title, '#FF3D00'], ['voting', '<i data-lucide="trophy" class="lucide-icon"></i>', $event->voting_title, '#FFD700'], ['lottery', '<i data-lucide="ticket" class="lucide-icon"></i>', $event->lottery_title, '#6366f1'], ['membership', '<i data-lucide="crown" class="lucide-icon"></i>', $event->membership_title, '#22c55e'], ['quiz', '<i data-lucide="brain" class="lucide-icon"></i>', $event->quiz_title, '#f59e0b'], ['fanclash', '<i data-lucide="swords" class="lucide-icon"></i>', $event->fanclash_title, '#ef4444']] as [$mod, $defaultIcon, $defaultName, $defaultAccent])
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
                            'quiz' => 'background:linear-gradient(160deg,#1c1200,#0a0800)',
                            'fanclash' => 'background:linear-gradient(160deg,#2a0808,#0d0303)',
                        };

                    $accentStyle = match ($mod) {
                        'fotobomb' => 'background:var(--p)',
                        'voting' => 'background:var(--acc)',
                        'lottery' => 'background:#6366f1',
                        'membership' => 'background:#22c55e',
                        'quiz' => 'background:#f59e0b',
                        'fanclash' => 'background:#ef4444',
                    };
                @endphp

                <div class="tile" style="{{ $bgStyle }}"
                    onclick="{{ $isLink
                        ? "window.open('" . addslashes($linkUrl) . "','" . ($external ? '_blank' : '_self') . "')"
                        : "openModule('{$mod}')" }}">

                    <div style="position:absolute;bottom:0;left:0;right:0;height:3px;z-index:3;{{ $accentStyle }}">
                    </div>

                    <div class="tile-arrow">{!! $isLink ? '<i data-lucide="arrow-up-right" class="lucide-icon"></i>' : '<i data-lucide="arrow-right" class="lucide-icon"></i>' !!}</div>

                    @if ($imgUrl)
                        <img src="{{ $imgUrl }}"
                            style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.5;pointer-events:none;filter:brightness(.85) contrast(1.05);"
                            alt="">
                    @else
                        <div class="tile-icon" style="color: {{ $defaultAccent }};">{!! $defaultIcon !!}</div>
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
                            @if ($isLink)
                                <i data-lucide="external-link" class="lucide-icon"></i> {{ __('Tap to visit') }}
                            @else
                                {{ match ($mod) {
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
                                    'quiz' => $event->quiz_desc
                                        ? \Illuminate\Support\Str::limit($event->quiz_desc, 30)
                                        : 'Fastest answer wins',
                                    'fanclash' => $event->fanclash_desc
                                        ? \Illuminate\Support\Str::limit($event->fanclash_desc, 30)
                                        : 'Tap for your side',
                                } }}
                            @endif
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
        @endif
    </div>

    
    @if ($event->module_fotobomb)
        <div class="module-screen" id="screen-fotobomb">
            <div class="mod-header">
                <button class="mod-back" onclick="closeModule('fotobomb')"><i data-lucide="arrow-left" class="lucide-icon"></i></button>
                <div class="mod-header-title">{{ $event->fotobomb_title }}</div>
            </div>
            <div class="mod-body">
                <div id="fotoForm">
                    <p class="section-title">{{ $event->fotobomb_title }}</p>
                    <p class="section-desc" data-en="{{ $event->fotobomb_desc }}"
                        data-de="{{ $event->fotobomb_desc }}">{{ $event->fotobomb_desc }}</p>

                    <div class="media-toggle">
                        <button class="media-toggle-btn active" id="togglePhoto" onclick="switchMedia('photo')"
                            data-en="Photo" data-de="Foto">Photo</button>
                        <button class="media-toggle-btn" id="toggleVideo" onclick="switchMedia('video')" data-en="Video"
                            data-de="Video">Video</button>
                    </div>

                    <div id="photoSection">
                        <img id="preview" class="preview-img" src="" alt="">
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" id="photoInput" accept="image/*" capture="environment">
                            <div class="uz-icon"><i data-lucide="camera" class="lucide-icon"></i></div>
                            <p><strong data-en="Tap to take a photo" data-de="Foto aufnehmen">Tap to take a
                                    photo</strong><br>
                                <span data-en="or choose from your gallery" data-de="oder aus der Galerie wählen">or
                                    choose from your gallery</span><br>
                                <small style="color:var(--muted);font-size:11px">JPG · PNG · WEBP — max 10MB</small>
                            </p>
                        </div>
                    </div>

                    <div id="videoSection" style="display:none">
                        <video id="previewVideo" class="preview-video" controls></video>
                        <div id="durationBadge" class="duration-badge" style="display:none"></div>
                        <div class="upload-zone" id="uploadZoneVideo">
                            <input type="file" id="videoInput" accept="video/*" capture="environment">
                            <div class="uz-icon"><i data-lucide="video" class="lucide-icon"></i></div>
                            <p><strong data-en="Tap to record a video" data-de="Video aufnehmen">Tap to record a
                                    video</strong><br>
                                <span data-en="or choose from your gallery" data-de="oder aus der Galerie wählen">or
                                    choose from your gallery</span><br>
                                <small style="color:var(--muted);font-size:11px">MP4 · MOV · WebM — max 10 sec · max
                                    25MB</small>
                            </p>
                        </div>
                        <div id="videoDurationError"
                            style="display:none;color:#f87171;font-size:12px;margin-bottom:10px;padding:8px 12px;background:rgba(239,68,68,.1);border-radius:8px">
                        </div>
                    </div>

                    <div class="upload-progress" id="uploadProgress">
                        <div class="upload-progress-fill" id="uploadProgressFill"></div>
                    </div>

                    <div style="margin-top:16px">
                        <div class="field-group">
                            <label class="field-label" data-en="Your Name" data-de="Dein Name">Your Name</label>
                            <input type="text" class="field" id="fotoName" data-ph-en="e.g. Ahmed from Row D"
                                data-ph-de="z.B. Max aus Reihe D" placeholder="e.g. Ahmed from Row D"
                                autocomplete="name">
                        </div>
                        {!! $gdprSnippet('foto') !!}
                        <button class="btn-main" id="uploadBtn" onclick="submitFoto()" disabled>
                            <span id="uploadBtnText" data-en="Send to Vidiwall" data-de="Auf die Vidiwall">Send to
                                Vidiwall</span>
                        </button>
                    </div>
                </div>
                <div class="success-state" id="fotoSuccess" style="display:none">
                    <div class="s-icon"><i data-lucide="party-popper" class="lucide-icon"></i></div>
                    <h3 id="fotoSuccessTitle" data-en="Submitted!" data-de="Eingereicht!">Submitted!</h3>
                    <p data-en="Watch the big screen — you might be up next!"
                        data-de="Schau auf die Leinwand — vielleicht bist du als nächstes dran!">Watch the big screen —
                        you might be up next!</p>
                    <button class="btn-again" onclick="resetFoto()"><span data-en="Upload Another"
                            data-de="Weiteres hochladen">Upload Another</span></button>
                </div>
            </div>
        </div>
    @endif

    
    @if ($event->module_lottery)
        <div class="module-screen" id="screen-lottery">
            <div class="mod-header">
                <button class="mod-back" onclick="closeModule('lottery')"><i data-lucide="arrow-left" class="lucide-icon"></i></button>
                <div class="mod-header-title"><i data-lucide="ticket" class="lucide-icon"></i> {{ $event->lottery_title }}</div>
            </div>
            <div class="mod-body">
                <div id="lotteryForm">
                    <p class="section-title"><i data-lucide="ticket" class="lucide-icon"></i> {{ $event->lottery_title }}</p>
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
                            <span data-html data-en='<i data-lucide="ticket" class="lucide-icon"></i> Enter the Draw' data-de='<i data-lucide="ticket" class="lucide-icon"></i> Jetzt teilnehmen'><i data-lucide="ticket" class="lucide-icon"></i> Enter the Draw</span>
                        </button>
                    </div>
                </div>
                <div class="success-state" id="lotterySuccess" style="display:none">
                    <div class="s-icon"><i data-lucide="ticket-check" class="lucide-icon"></i></div>
                    <h3 data-en="You're In!" data-de="Du bist dabei!">You're In!</h3>
                    <p data-en="Good luck! The winner will be announced live."
                        data-de="Viel Glück! Der Gewinner wird live bekannt gegeben.">Good luck! The winner will be
                        announced live.</p>
                </div>
            </div>
        </div>
    @endif

    
    @if ($event->module_voting)
        <div class="module-screen" id="screen-voting">
            <div class="mod-header">
                <button class="mod-back" onclick="closeModule('voting')"><i data-lucide="arrow-left" class="lucide-icon"></i></button>
                <div class="mod-header-title"><i data-lucide="trophy" class="lucide-icon"></i> {{ $event->voting_title }}</div>
            </div>
            <div class="mod-body">
                <p class="section-title"><i data-lucide="trophy" class="lucide-icon"></i> {{ $event->voting_title }}</p>
                <p class="section-desc">{{ $event->voting_desc }}</p>
                @if ($event->voting_closed)
                    <div
                        style="background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:14px;padding:28px;text-align:center">
                        <div style="font-size:40px;margin-bottom:12px"><i data-lucide="lock" class="lucide-icon"></i></div>
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
                        <button class="btn-main" id="voteBtn" onclick="submitVote()"> <span data-html
                                data-en='<i data-lucide="check-square" class="lucide-icon"></i> Cast My Vote' data-de='<i data-lucide="check-square" class="lucide-icon"></i> Abstimmen'><i data-lucide="check-square" class="lucide-icon"></i> Cast My Vote</span>
                        </button>
                    </div>
                    <div class="success-state" id="voteSuccess" style="display:none">
                        <div class="s-icon"><i data-lucide="trophy" class="lucide-icon"></i></div>
                        <h3 data-en="Vote Recorded!" data-de="Stimme gezählt!">Vote Recorded!</h3>
                        <p data-en="Live results shown on the big screen."
                            data-de="Live-Ergebnisse auf der Leinwand.">Live results shown on the big screen.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    
    @if ($event->module_membership)
        <div class="module-screen" id="screen-membership">
            <div class="mod-header">
                <button class="mod-back" onclick="closeModule('membership')"><i data-lucide="arrow-left" class="lucide-icon"></i></button>
                <div class="mod-header-title"><i data-lucide="crown" class="lucide-icon"></i> {{ $event->membership_title }}</div>
            </div>
            <div class="mod-body">
                <div id="memberForm">
                    <p class="section-title"><i data-lucide="crown" class="lucide-icon"></i> {{ $event->membership_title }}</p>
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
                        <button class="btn-main" onclick="submitMembership()"> <span data-html data-en='<i data-lucide="crown" class="lucide-icon"></i> Join Now'
                                data-de='<i data-lucide="crown" class="lucide-icon"></i> Jetzt beitreten'><i data-lucide="crown" class="lucide-icon"></i> Join Now</span></button>
                    </div>
                </div>
                <div class="success-state" id="memberSuccess" style="display:none">
                    <div class="s-icon"><i data-lucide="crown" class="lucide-icon"></i></div>
                    <h3 data-en="Welcome to the Club!" data-de="Willkommen im Club!">Welcome to the Club!</h3>
                    <p data-en="Membership confirmed. Stay tuned for exclusive updates."
                        data-de="Mitgliedschaft bestätigt. Bleib dran für exklusive Updates.">Membership confirmed.</p>
                </div>
            </div>
        </div>
    @endif

    @if ($event->module_quiz)
        <div class="module-screen" id="screen-quiz">
            <div class="mod-header">
                <button class="mod-back" onclick="closeModule('quiz')"><i data-lucide="arrow-left" class="lucide-icon"></i></button>
                <div class="mod-header-title"><i data-lucide="brain" class="lucide-icon"></i> {{ $event->quiz_title }}</div>
            </div>
            <div class="mod-body">
                <style>
                    .quiz-timer-bar-wrap{background:rgba(255,255,255,.1);border-radius:6px;height:8px;overflow:hidden;margin-bottom:6px}
                    .quiz-timer-bar{height:100%;background:#f59e0b;border-radius:6px;transition:width .1s linear}
                    .quiz-timer-secs{font-size:13px;font-weight:700;color:#f59e0b;text-align:right;margin-bottom:14px}
                    .quiz-opts{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:16px}
                    .quiz-opt-btn{background:rgba(255,255,255,.07);border:1.5px solid rgba(255,255,255,.15);border-radius:12px;padding:14px 10px;font-size:15px;font-weight:600;color:#fff;cursor:pointer;text-align:left;transition:background .15s,border-color .15s}
                    .quiz-opt-btn:active{transform:scale(.97)}
                    .quiz-opt-btn.correct{background:rgba(34,197,94,.18);border-color:#22c55e;color:#86efac}
                    .quiz-opt-btn.wrong{background:rgba(239,68,68,.15);border-color:#ef4444;color:#fca5a5}
                    .quiz-opt-btn.reveal{background:rgba(34,197,94,.12);border-color:rgba(34,197,94,.4);color:rgba(134,239,172,.7)}
                    .quiz-opt-btn:disabled{cursor:default}
                    .quiz-q-counter{font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:10px}
                    .quiz-q-text{font-size:17px;font-weight:700;line-height:1.4;margin-bottom:4px}
                    .quiz-feedback{margin-top:14px;padding:12px 14px;border-radius:10px;font-size:14px;font-weight:600;display:none}
                    .quiz-feedback.ok{background:rgba(34,197,94,.15);color:#86efac;display:block}
                    .quiz-feedback.bad{background:rgba(239,68,68,.13);color:#fca5a5;display:block}
                    .quiz-waiting{text-align:center;padding:40px 20px}
                    .quiz-waiting-pulse{width:60px;height:60px;border-radius:50%;background:#f59e0b;margin:0 auto 18px;animation:qpulse 1.6s ease-in-out infinite}
                    @keyframes qpulse{0%,100%{transform:scale(1);opacity:.7}50%{transform:scale(1.12);opacity:1}}
                    .quiz-results{padding:20px}
                    .quiz-winner-card{background:linear-gradient(135deg,rgba(245,158,11,.18),rgba(245,158,11,.06));border:1.5px solid rgba(245,158,11,.35);border-radius:14px;padding:20px;text-align:center;margin-bottom:18px}
                    .quiz-lb-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.07);font-size:14px}
                    .quiz-lb-rank{width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0}
                    .quiz-lb-name{flex:1;font-weight:600}
                    .quiz-lb-score{font-size:12px;color:rgba(255,255,255,.55)}
                    .quiz-name-screen{padding:20px}
                </style>

                <div id="quizNameScreen" class="quiz-name-screen">
                    <p class="section-title"><i data-lucide="brain" class="lucide-icon"></i> {{ $event->quiz_title }}</p>
                    <p class="section-desc" style="margin-bottom:20px">{{ $event->quiz_desc }}</p>
                    <div class="glass">
                        <div class="field-group">
                            <label class="field-label">Your Name</label>
                            <input type="text" class="field" id="quizGuestName" placeholder="Enter your name" autocomplete="name">
                        </div>
                        <button class="btn-main" id="quizStartBtn" onclick="quizEnter()">Start Quiz</button>
                    </div>
                </div>

                <div id="quizWaitingScreen" style="display:none">
                    <div class="quiz-waiting">
                        <div class="quiz-waiting-pulse"></div>
                        <p class="section-title" style="margin-bottom:8px">Waiting for Quiz...</p>
                        <p style="color:rgba(255,255,255,.5);font-size:14px">The quiz will start soon. Stay on this screen.</p>
                    </div>
                </div>

                <div id="quizActiveScreen" style="display:none;padding:20px">
                    <div class="quiz-q-counter" id="quizQCounter">Question 1 of 3</div>
                    <div class="quiz-timer-bar-wrap"><div class="quiz-timer-bar" id="quizTimerBar" style="width:100%"></div></div>
                    <div class="quiz-timer-secs" id="quizTimerSecs">30</div>
                    <div id="quizQSponsorWrap" style="display:none;text-align:center;margin-bottom:12px">
                        <img id="quizQSponsor" alt="Sponsor" style="max-height:46px;width:auto;max-width:70%;background:#fff;border-radius:8px;padding:6px 10px">
                    </div>
                    <div class="quiz-q-text" id="quizQText"></div>
                    <div class="quiz-opts" id="quizOpts"></div>
                    <div class="quiz-feedback" id="quizFeedback"></div>
                </div>

                <div id="quizCompleteScreen" style="display:none;padding:20px;text-align:center">
                    <div class="s-icon"><i data-lucide="check-circle" class="lucide-icon"></i></div>
                    <h3 style="font-size:22px;font-weight:800;margin-bottom:8px">Quiz Complete!</h3>
                    <p id="quizSummaryText" style="color:rgba(255,255,255,.6);font-size:15px;margin-bottom:20px"></p>
                    <div id="quizAnswerSummary" style="text-align:left;margin-bottom:20px"></div>
                    <p style="color:rgba(255,255,255,.4);font-size:13px">Waiting for results...</p>
                </div>

                <div id="quizResultsScreen" style="display:none">
                    <div class="quiz-results">
                        <div id="quizEndSponsorWrap" style="display:none;text-align:center;margin-bottom:16px">
                            <img id="quizEndSponsor" alt="Sponsor" style="max-height:60px;width:auto;max-width:80%;background:#fff;border-radius:10px;padding:8px 12px">
                        </div>
                        <div id="quizWinnerText" style="display:none;text-align:center;font-size:15px;font-weight:600;line-height:1.5;color:#f59e0b;margin-bottom:18px"></div>
                        <p class="section-title" style="margin-bottom:14px">Results</p>
                        <div class="quiz-winner-card" id="quizWinnerCard" style="display:none">
                            <div style="font-size:28px;margin-bottom:8px;color:#f59e0b"><i data-lucide="trophy" class="lucide-icon"></i></div>
                            <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#f59e0b;margin-bottom:4px">Winner</div>
                            <div style="font-size:20px;font-weight:800" id="quizWinnerName"></div>
                        </div>
                        <div id="quizLeaderboard"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($event->module_fanclash)
        <div class="module-screen" id="screen-fanclash">
            <div class="mod-header">
                <button class="mod-back" onclick="closeModule('fanclash')"><i data-lucide="arrow-left" class="lucide-icon"></i></button>
                <div class="mod-header-title"><i data-lucide="swords" class="lucide-icon"></i> {{ $event->fanclash_title }}</div>
            </div>
            <div class="mod-body">
                <style>
                    #screen-fanclash .mod-body{display:flex;flex-direction:column}
                    .fc-screen{flex:1;display:flex;flex-direction:column}
                    .fc-eyebrow{font-size:11px;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:rgba(255,255,255,.5);text-align:center;margin-bottom:20px;display:flex;align-items:center;justify-content:center;gap:9px}
                    .fc-eyebrow .fc-dot{width:7px;height:7px;border-radius:50%;background:#ef4444;box-shadow:0 0 10px #ef4444;animation:fcpip 1.6s infinite}
                    @keyframes fcpip{0%,100%{opacity:1}50%{opacity:.3}}

                    .fc-pick-title{font-family:'Syne',sans-serif;font-weight:800;font-size:32px;text-align:center;letter-spacing:-.02em;line-height:1.05}
                    .fc-pick-sub{color:rgba(255,255,255,.55);text-align:center;font-size:14px;line-height:1.5;margin:10px 0 30px}
                    .fc-pick-btns{display:flex;flex-direction:column;gap:14px;margin-top:auto}
                    .fc-vs{text-align:center;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.3em;color:rgba(255,255,255,.35);margin:2px 0}
                    .fc-pick-btn{border:none;border-radius:16px;padding:28px 18px;min-height:104px;cursor:pointer;font-family:'Syne',sans-serif;font-weight:800;font-size:23px;letter-spacing:.03em;text-transform:uppercase;position:relative;transition:transform .12s;display:flex;align-items:center;justify-content:center;text-align:center;line-height:1.1;-webkit-tap-highlight-color:transparent}
                    .fc-pick-btn:active{transform:scale(.98)}

                    .fc-tap-top{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:16px}
                    .fc-side-label{font-family:'Syne',sans-serif;font-weight:800;font-size:17px;letter-spacing:.12em;text-transform:uppercase;color:var(--fc-me,#ef4444)}
                    .fc-your-taps{font-size:13px;color:rgba(255,255,255,.5);font-variant-numeric:tabular-nums}
                    .fc-your-taps b{font-family:'Syne',sans-serif;font-weight:800;color:#fff;font-size:17px}
                    .fc-tap-btn{flex:1;min-height:46vh;border:none;border-radius:26px;background:var(--fc-me,#ef4444);color:var(--fc-me-ink,#fff);cursor:pointer;user-select:none;-webkit-user-select:none;position:relative;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;touch-action:manipulation;transition:transform .05s}
                    .fc-tap-btn:active{transform:scale(.985)}
                    .fc-tap-btn.fc-dis{opacity:.45;pointer-events:none}
                    .fc-tap-word{font-family:'Syne',sans-serif;font-weight:800;font-size:clamp(56px,20vw,80px);letter-spacing:.02em;line-height:1;pointer-events:none}
                    .fc-tap-rate{font-size:15px;font-weight:600;opacity:.82;font-variant-numeric:tabular-nums;pointer-events:none}
                    .fc-float{position:absolute;font-family:'Syne',sans-serif;font-weight:800;font-size:28px;pointer-events:none;animation:fcfloat .7s ease-out forwards}
                    @keyframes fcfloat{from{opacity:.9;transform:translateY(0) scale(1)}to{opacity:0;transform:translateY(-56px) scale(1.5)}}
                    .fc-count{font-family:'Syne',sans-serif;font-weight:800;font-size:52px;text-align:center;font-variant-numeric:tabular-nums;margin-top:20px;letter-spacing:.02em}
                    .fc-count.fc-low{color:#ef4444}

                    .fc-waiting{text-align:center;margin:auto 0}
                    .fc-pulse{width:64px;height:64px;border-radius:50%;background:#ef4444;margin:0 auto 20px;animation:fcpulse 1.6s ease-in-out infinite}
                    @keyframes fcpulse{0%,100%{transform:scale(1);opacity:.7}50%{transform:scale(1.14);opacity:1}}
                    .fc-muted{color:rgba(255,255,255,.5);font-size:14px;line-height:1.55}

                    .fc-result{text-align:center;margin:auto 0;width:100%}
                    .fc-result-spon{margin-bottom:24px}
                    .fc-result-spon img{max-height:56px;width:auto;max-width:70%;background:#fff;border-radius:10px;padding:8px 12px}
                    .fc-result-eyebrow{font-size:11px;font-weight:700;letter-spacing:.26em;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:12px}
                    .fc-winner-name{font-family:'Syne',sans-serif;font-weight:800;font-size:clamp(34px,11vw,46px);line-height:1.05;letter-spacing:-.01em;color:var(--fc-win,#fff)}
                    .fc-winner-tag{font-size:13px;letter-spacing:.24em;text-transform:uppercase;color:rgba(255,255,255,.55);margin-top:10px}
                    .fc-score{font-family:'Syne',sans-serif;font-weight:800;font-size:28px;font-variant-numeric:tabular-nums;margin:26px 0 6px;letter-spacing:.04em}
                    .fc-score .a{color:var(--fc-a,#ef4444)}
                    .fc-score .b{color:var(--fc-b,#3B82F6)}
                    .fc-score .sep{color:rgba(255,255,255,.3);margin:0 10px}
                    #screen-fanclash .fc-again{margin-top:30px}
                </style>

                <div id="fcPick" class="fc-screen">
                    <div class="fc-eyebrow" id="fcPickCat"><span class="fc-dot"></span> Live now</div>
                    <h2 class="fc-pick-title">Who's faster?</h2>
                    <p class="fc-pick-sub">Pick your side, then tap as fast as you can until the buzzer.</p>
                    <div class="fc-pick-btns">
                        <button class="fc-pick-btn" id="fcPickA" onclick="fcChoose('a')">Side A</button>
                        <div class="fc-vs">VS</div>
                        <button class="fc-pick-btn" id="fcPickB" onclick="fcChoose('b')">Side B</button>
                    </div>
                </div>

                <div id="fcWaiting" class="fc-screen" style="display:none">
                    <div class="fc-waiting">
                        <div class="fc-pulse"></div>
                        <p class="section-title" style="margin-bottom:8px">Waiting for the clash</p>
                        <p class="fc-muted">The next round will start soon. Stay on this screen.</p>
                    </div>
                </div>

                <div id="fcTap" class="fc-screen" style="display:none">
                    <div class="fc-tap-top">
                        <div class="fc-side-label" id="fcSideLabel">Your side</div>
                        <div class="fc-your-taps"><b id="fcYourTaps">0</b> taps</div>
                    </div>
                    <button class="fc-tap-btn" id="fcTapBtn">
                        <div class="fc-tap-word">TAP</div>
                        <div class="fc-tap-rate"><span id="fcRate">0</span>/s</div>
                    </button>
                    <div class="fc-count" id="fcCount">20</div>
                </div>

                <div id="fcResult" class="fc-screen" style="display:none">
                    <div class="fc-result">
                        <div class="fc-result-spon" id="fcResultSponWrap" style="display:none">
                            <img id="fcResultSpon" alt="Sponsor">
                        </div>
                        <div class="fc-result-eyebrow" id="fcResultEyebrow">Winner</div>
                        <div class="fc-winner-name" id="fcWinnerName">—</div>
                        <div class="fc-winner-tag" id="fcWinnerTag">wins</div>
                        <div class="fc-score" id="fcScore"></div>
                        <p class="fc-muted" id="fcResultNote" style="margin-top:14px">Stay for the next round.</p>
                        <button class="btn-main fc-again" onclick="fcBackToStart()">Back to start</button>
                    </div>
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
                if (v === undefined) return;
                if (el.hasAttribute('data-html')) {
                    el.innerHTML = v;
                } else {
                    el.textContent = v;
                }
            });
            document.querySelectorAll('[data-ph-' + l + ']').forEach(el => {
                el.placeholder = el.dataset['ph' + l.charAt(0).toUpperCase() + l.slice(1)] || el.placeholder;
            });
            if (window.lucide) {
                lucide.createIcons();
            }
        }
        setLang(lang);

        function openModule(n) {
            document.getElementById('landing').style.display = 'none';
            document.getElementById('screen-' + n)?.classList.add('open');
            window.scrollTo(0, 0);
            if (n === 'fanclash' && window.fcOpen) { window.fcOpen(); }
        }

        function closeModule(n) {
            document.getElementById('screen-' + n)?.classList.remove('open');
            document.getElementById('landing').style.display = 'flex';
            window.scrollTo(0, 0);
            if (n === 'fanclash' && window.fcClose) { window.fcClose(); }
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
                    errEl.textContent = lang === 'de' ?
                        'Das Video ist zu lang. Maximal 10 Sekunden erlaubt.' :
                        'Video is too long. Maximum 10 seconds allowed.';
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
                errEl.textContent = lang === 'de' ? 'Videodatei konnte nicht gelesen werden.' :
                    'Could not read video file.';
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
                return toast(lang === 'de' ? 'Bitte zuerst ein Foto aufnehmen.' : 'Please take a photo first.',
                    true);
            }
            if (currentMedia === 'video' && !selVideo) {
                return toast(lang === 'de' ? 'Bitte zuerst ein Video aufnehmen.' :
                    'Please record a video first.', true);
            }
            if (!checkGdpr('foto')) return;
            const btn = document.getElementById('uploadBtn');
            const txt = document.getElementById('uploadBtnText');
            btn.disabled = true;
            if (txt) { txt.innerHTML = '<i data-lucide="loader-circle" class="lucide-icon lucide-spin"></i>'; window.lucide && lucide.createIcons(); }
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
                        c.querySelector('.v-fill').style.width = (total > 0 ? Math.round((cnt / total) *
                                100) :
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
        let quizState = 'idle';
        let quizGuestName = '';
        let quizSessionToken = localStorage.getItem('eb_quiz_token') || (() => {
            const t = Math.random().toString(36).slice(2) + Date.now().toString(36);
            localStorage.setItem('eb_quiz_token', t);
            return t;
        })();
        let quizQuestions = [];
        let quizCurrentIdx = 0;
        let quizRoundId = null;
        let quizTimerInterval = null;
        let quizTimeLimitMs = 30000;
        let quizQuestionStartTime = null;
        let quizAnswered = false;
        let quizResults = [];
        let quizPollInterval = null;
        let quizAnsweredRoundId = null;

        function quizShow(id) {
            ['quizNameScreen','quizWaitingScreen','quizActiveScreen','quizCompleteScreen','quizResultsScreen'].forEach(s => {
                const el = document.getElementById(s);
                if (el) el.style.display = s === id ? '' : 'none';
            });
        }

        function quizEnter() {
            quizGuestName = document.getElementById('quizGuestName').value.trim();
            if (!quizGuestName) { toast('Please enter your name.', true); return; }
            quizShow('quizWaitingScreen');
            quizState = 'waiting';
            quizStartPolling();
        }

        function quizStartPolling() {
            clearInterval(quizPollInterval);
            quizPollInterval = setInterval(quizPoll, 3000);
            quizPoll();
        }

        async function quizPoll() {
            try {
                const r = await fetch(`/e/${SLUG}/quiz/status?session_token=${encodeURIComponent(quizSessionToken)}`);
                const d = await r.json();
                if (d.status === 'active' && d.round_id !== quizRoundId) {
                    if (quizAnsweredRoundId === d.round_id) return;
                    quizRoundId = d.round_id;
                    quizQuestions = d.questions;
                    quizTimeLimitMs = (d.time_limit_seconds || 30) * 1000;
                    quizCurrentIdx = 0;
                    quizResults = [];
                    quizState = 'active';
                    clearInterval(quizPollInterval);
                    quizShowQuestion();
                } else if (d.status === 'finished' && quizState === 'complete') {
                    quizShowResults(d.round_id);
                } else if (d.status === 'finished' && quizState === 'idle') {
                    quizShowResults(d.round_id);
                }
            } catch {}
        }

        function quizShowQuestion() {
            if (quizCurrentIdx >= quizQuestions.length) {
                quizShowComplete();
                return;
            }
            const q = quizQuestions[quizCurrentIdx];
            document.getElementById('quizQCounter').textContent = `Question ${quizCurrentIdx + 1} of ${quizQuestions.length}`;
            const sponsorWrap = document.getElementById('quizQSponsorWrap');
            if (q.sponsor_logo_url) {
                document.getElementById('quizQSponsor').src = q.sponsor_logo_url;
                sponsorWrap.style.display = '';
            } else {
                sponsorWrap.style.display = 'none';
            }
            document.getElementById('quizQText').textContent = q.question_text;
            const labels = ['A', 'B', 'C', 'D'];
            document.getElementById('quizOpts').innerHTML = q.options.map((opt, i) =>
                `<button class="quiz-opt-btn" id="qopt${i}" onclick="quizSelectAnswer(${i})">${labels[i]}. ${opt}</button>`
            ).join('');
            document.getElementById('quizFeedback').className = 'quiz-feedback';
            quizAnswered = false;
            quizShow('quizActiveScreen');
            quizStartQuestionTimer(q.time_limit_seconds || 30);
            quizQuestionStartTime = performance.now();
        }

        function quizStartQuestionTimer(secs) {
            clearInterval(quizTimerInterval);
            const totalMs = secs * 1000;
            const bar = document.getElementById('quizTimerBar');
            const secsEl = document.getElementById('quizTimerSecs');
            const start = performance.now();
            quizTimerInterval = setInterval(() => {
                const elapsed = performance.now() - start;
                const remaining = Math.max(0, totalMs - elapsed);
                const pct = (remaining / totalMs) * 100;
                bar.style.width = pct + '%';
                bar.style.background = pct < 30 ? '#ef4444' : pct < 60 ? '#f59e0b' : '#f59e0b';
                secsEl.textContent = Math.ceil(remaining / 1000);
                if (remaining <= 0) {
                    clearInterval(quizTimerInterval);
                    if (!quizAnswered) quizTimeUp();
                }
            }, 100);
        }

        async function quizSelectAnswer(idx) {
            if (quizAnswered) return;
            quizAnswered = true;
            const timeTakenMs = Math.round(performance.now() - quizQuestionStartTime);
            clearInterval(quizTimerInterval);
            const q = quizQuestions[quizCurrentIdx];
            document.querySelectorAll('.quiz-opt-btn').forEach(b => b.disabled = true);
            try {
                const d = await post(`/e/${SLUG}/quiz/answer`, {
                    quiz_question_id: q.id,
                    selected_option: idx,
                    time_taken_ms: timeTakenMs,
                    session_token: quizSessionToken,
                    guest_name: quizGuestName,
                });
                quizResults.push({ question: q.question_text, correct: d.correct, correctText: d.correct_text });
                const fb = document.getElementById('quizFeedback');
                document.querySelectorAll('.quiz-opt-btn').forEach((b, i) => {
                    if (i === q.correct_option) b.classList.add('reveal');
                    else if (i === idx && !d.correct) b.classList.add('wrong');
                });
                document.getElementById(`qopt${idx}`)?.classList.add(d.correct ? 'correct' : 'wrong');
                fb.textContent = d.correct ? 'Correct!' : `Wrong! The answer is: ${d.correct_text}`;
                fb.className = 'quiz-feedback ' + (d.correct ? 'ok' : 'bad');
            } catch {
                quizResults.push({ question: q.question_text, correct: false, correctText: '' });
            }
            setTimeout(() => {
                quizCurrentIdx++;
                quizShowQuestion();
            }, 1800);
        }

        function quizTimeUp() {
            quizAnswered = true;
            const q = quizQuestions[quizCurrentIdx];
            document.querySelectorAll('.quiz-opt-btn').forEach((b, i) => {
                b.disabled = true;
                if (i === q.correct_option) b.classList.add('reveal');
            });
            const fb = document.getElementById('quizFeedback');
            fb.textContent = 'Time\'s up! The answer was: ' + (q.options[q.correct_option] || '');
            fb.className = 'quiz-feedback bad';
            quizResults.push({ question: q.question_text, correct: false, correctText: q.options[q.correct_option] || '' });
            setTimeout(() => {
                quizCurrentIdx++;
                quizShowQuestion();
            }, 1800);
        }

        function quizShowComplete() {
            quizState = 'complete';
            quizAnsweredRoundId = quizRoundId;
            const correctCount = quizResults.filter(r => r.correct).length;
            document.getElementById('quizSummaryText').textContent = `You got ${correctCount} out of ${quizResults.length} correct!`;
            const summaryEl = document.getElementById('quizAnswerSummary');
            summaryEl.innerHTML = quizResults.map((r, i) =>
                `<div style="display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.07)">
                    <div style="font-size:16px;flex-shrink:0">${r.correct ? '' : ''}</div>
                    <div><div style="font-size:13px;font-weight:600;margin-bottom:2px">Q${i+1}: ${r.question}</div>${!r.correct && r.correctText ? `<div style="font-size:12px;color:rgba(255,255,255,.45)">Answer: ${r.correctText}</div>` : ''}</div>
                </div>`
            ).join('');
            quizShow('quizCompleteScreen');
            quizStartPolling();
        }

        async function quizShowResults(roundId) {
            clearInterval(quizPollInterval);
            try {
                const r = await fetch(`/e/${SLUG}/quiz/results`);
                const d = await r.json();
                if (d.status !== 'finished') return;
                const endSponsorWrap = document.getElementById('quizEndSponsorWrap');
                if (d.end_sponsor_logo_url) {
                    document.getElementById('quizEndSponsor').src = d.end_sponsor_logo_url;
                    endSponsorWrap.style.display = '';
                } else {
                    endSponsorWrap.style.display = 'none';
                }
                const winnerTextEl = document.getElementById('quizWinnerText');
                if (d.winner_text) {
                    winnerTextEl.textContent = d.winner_text;
                    winnerTextEl.style.display = '';
                } else {
                    winnerTextEl.style.display = 'none';
                }
                const winnerCard = document.getElementById('quizWinnerCard');
                if (d.winner) {
                    document.getElementById('quizWinnerName').textContent = d.winner.guest_name;
                    winnerCard.style.display = '';
                } else {
                    winnerCard.style.display = 'none';
                }
                const lb = document.getElementById('quizLeaderboard');
                lb.innerHTML = (d.leaderboard || []).map(row =>
                    `<div class="quiz-lb-row">
                        <div class="quiz-lb-rank">${row.rank}</div>
                        <div class="quiz-lb-name">${row.guest_name}</div>
                        <div class="quiz-lb-score">${row.correct_count} correct &middot; ${(row.total_ms/1000).toFixed(1)}s</div>
                    </div>`
                ).join('');
                quizShow('quizResultsScreen');
                quizState = 'results';
            } catch {}
        }

        /* ============ FAN CLASH — tug-of-war tap battle ============ */
        (() => {
            const root = document.getElementById('screen-fanclash');
            if (!root) return;

            const MAX_TPS = 25;
            let state = 'idle';           // idle | pick | waiting | tap | ending | result
            let side = null;              // 'a' | 'b'
            let roundId = null;
            let resultRoundId = null;
            let colA = '#ef4444', colB = '#3B82F6', nameA = 'Side A', nameB = 'Side B';
            let localTaps = 0, pendingTaps = 0, stamps = [];
            let syncPerf = 0, syncRemaining = 20000;
            let rafId = null, flushId = null, pollId = null, celebrated = false;

            const token = localStorage.getItem('eb_clash_token') || (() => {
                const t = Math.random().toString(36).slice(2) + Date.now().toString(36);
                localStorage.setItem('eb_clash_token', t);
                return t;
            })();

            const $ = id => document.getElementById(id);
            const tapBtn = $('fcTapBtn'), countEl = $('fcCount'), rateEl = $('fcRate'),
                yourTapsEl = $('fcYourTaps'), sideLabel = $('fcSideLabel');

            function ink(hex) {
                let h = (hex || '').replace('#', '');
                if (h.length === 3) h = h[0]+h[0]+h[1]+h[1]+h[2]+h[2];
                if (h.length !== 6) return '#fff';
                const r = parseInt(h.slice(0,2),16), g = parseInt(h.slice(2,4),16), b = parseInt(h.slice(4,6),16);
                return (r*299 + g*587 + b*114) / 1000 > 150 ? '#111827' : '#ffffff';
            }
            function setVar(k, v) { root.style.setProperty(k, v); }
            function show(id) {
                ['fcPick','fcWaiting','fcTap','fcResult'].forEach(s => {
                    const el = $(s); if (el) el.style.display = s === id ? 'flex' : 'none';
                });
            }
            function remainingNow() {
                return Math.max(0, syncRemaining - (performance.now() - syncPerf));
            }

            function renderPick(d) {
                colA = d.side_a_color || colA; colB = d.side_b_color || colB;
                nameA = d.side_a_name || nameA; nameB = d.side_b_name || nameB;
                setVar('--fc-a', colA); setVar('--fc-b', colB);
                const a = $('fcPickA'), b = $('fcPickB');
                a.style.background = colA; a.style.color = ink(colA); a.textContent = nameA;
                b.style.background = colB; b.style.color = ink(colB); b.textContent = nameB;
                $('fcPickCat').innerHTML = '<span class="fc-dot"></span> ' + (d.category ? d.category : 'Live now');
                syncPerf = performance.now(); syncRemaining = d.remaining_ms ?? (d.duration_seconds || 20) * 1000;
            }

            window.fcChoose = function (s) {
                side = s;
                const col = s === 'a' ? colA : colB;
                setVar('--fc-me', col); setVar('--fc-me-ink', ink(col));
                sideLabel.textContent = s === 'a' ? nameA : nameB;
                sideLabel.style.color = col;
                localTaps = 0; pendingTaps = 0; stamps = [];
                yourTapsEl.textContent = '0'; rateEl.textContent = '0';
                countEl.textContent = Math.ceil(remainingNow() / 1000);
                countEl.classList.remove('fc-low');
                tapBtn.classList.remove('fc-dis');
                state = 'tap';
                show('fcTap');
                clearInterval(flushId); flushId = setInterval(flush, 800);
                cancelAnimationFrame(rafId); rafId = requestAnimationFrame(frame);
            };

            function registerTap(e) {
                if (state !== 'tap') return;
                localTaps++; pendingTaps++;
                stamps.push(performance.now());
                yourTapsEl.textContent = localTaps;
                const f = document.createElement('div');
                f.className = 'fc-float'; f.textContent = '+1';
                f.style.color = getComputedStyle(root).getPropertyValue('--fc-me-ink') || '#fff';
                const r = tapBtn.getBoundingClientRect();
                f.style.left = ((e && e.clientX ? e.clientX - r.left : r.width / 2) - 8) + 'px';
                f.style.top = ((e && e.clientY ? e.clientY - r.top : r.height / 2) - 14) + 'px';
                tapBtn.appendChild(f);
                setTimeout(() => f.remove(), 700);
            }

            function frame() {
                if (state !== 'tap') return;
                const rem = remainingNow();
                countEl.textContent = Math.ceil(rem / 1000);
                countEl.classList.toggle('fc-low', rem <= 5000);
                const now = performance.now();
                stamps = stamps.filter(t => now - t < 1000);
                rateEl.textContent = stamps.length;
                if (rem <= 0) { endLocal(); return; }
                rafId = requestAnimationFrame(frame);
            }

            async function flush() {
                if (pendingTaps <= 0) return;
                const batch = pendingTaps; pendingTaps = 0;
                try {
                    const d = await post(`/e/${SLUG}/clash/tap`, { session_token: token, side, taps: batch });
                    if (d && d.remaining_ms !== undefined) { syncPerf = performance.now(); syncRemaining = d.remaining_ms; }
                    if (d && d.status === 'finished') { endLocal(); }
                } catch { pendingTaps += batch; }
            }

            function endLocal() {
                if (state !== 'tap') return;
                state = 'ending';
                cancelAnimationFrame(rafId);
                clearInterval(flushId);
                tapBtn.classList.add('fc-dis');
                countEl.textContent = '0';
                flush();
                clearInterval(pollId); pollId = setInterval(poll, 700); poll();
            }

            function showResult(d) {
                resultRoundId = d.round_id;
                state = 'result';
                setVar('--fc-a', d.side_a_color || colA); setVar('--fc-b', d.side_b_color || colB);
                const w = d.winner_side;
                const winName = w === 'a' ? d.side_a_name : w === 'b' ? d.side_b_name : null;
                const winCol = w === 'a' ? (d.side_a_color || colA) : w === 'b' ? (d.side_b_color || colB) : '#fff';
                setVar('--fc-win', winCol);
                if (w === 'tie') {
                    $('fcWinnerName').textContent = "It's a tie!";
                    $('fcWinnerTag').style.display = 'none';
                } else {
                    $('fcWinnerName').textContent = winName || '—';
                    $('fcWinnerTag').style.display = '';
                }
                $('fcScore').innerHTML = `<span class="a">${d.side_a_taps ?? 0}</span><span class="sep">–</span><span class="b">${d.side_b_taps ?? 0}</span>`;
                const spon = $('fcResultSponWrap');
                if (d.sponsor_logo_url) { $('fcResultSpon').src = d.sponsor_logo_url; spon.style.display = ''; }
                else { spon.style.display = 'none'; }
                const won = side && w === side;
                $('fcResultNote').textContent = won ? 'Your side won it. Stay for the next round.' : 'Stay for the next round.';
                show('fcResult');
                if (won && !celebrated) { celebrated = true; confettiBurst(winCol); }
                clearInterval(pollId); pollId = setInterval(poll, 1500);
            }

            function applyStatus(d) {
                if (d.status === 'active') {
                    if (roundId !== d.round_id) {
                        roundId = d.round_id; side = null; celebrated = false;
                        renderPick(d); show('fcPick'); state = 'pick';
                    } else if (state === 'pick') {
                        renderPick(d);
                    } else if (state === 'tap') {
                        syncPerf = performance.now(); syncRemaining = d.remaining_ms;
                    }
                } else if (d.status === 'finished') {
                    if (state === 'tap' || state === 'ending' || state === 'idle' ||
                        (state === 'result' && resultRoundId !== d.round_id) ||
                        (state === 'pick') || (state === 'waiting')) {
                        showResult(d);
                    }
                } else { // waiting
                    if (state !== 'tap' && state !== 'ending') {
                        roundId = null;
                        show('fcWaiting'); state = 'waiting';
                    }
                }
            }

            async function poll() {
                try {
                    const r = await fetch(`/e/${SLUG}/clash/status`);
                    applyStatus(await r.json());
                } catch {}
            }

            window.fcOpen = function () {
                celebrated = false;
                if (state === 'idle') { show('fcWaiting'); state = 'waiting'; }
                clearInterval(pollId); pollId = setInterval(poll, 1500); poll();
            };
            window.fcClose = function () {
                clearInterval(pollId); clearInterval(flushId); cancelAnimationFrame(rafId);
            };

            tapBtn.addEventListener('pointerdown', registerTap);
            tapBtn.addEventListener('contextmenu', e => e.preventDefault());

            /* compact confetti — fires only when your side wins */
            let cc = null, cx = null, parts = [], craf = null;
            function confettiBurst(color) {
                if (!cc) {
                    cc = document.createElement('canvas');
                    cc.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:9999';
                    document.body.appendChild(cc); cx = cc.getContext('2d');
                    const rs = () => { cc.width = innerWidth; cc.height = innerHeight; };
                    addEventListener('resize', rs); rs();
                }
                const cols = [color, '{{ $event->accent_color ?: '#FFD700' }}', '#ffffff'];
                const ox = innerWidth / 2, oy = innerHeight * 0.42;
                for (let i = 0; i < 120; i++) {
                    const a = Math.random() * 6.283, sp = 4 + Math.random() * 9;
                    parts.push({ x: ox, y: oy, vx: Math.cos(a)*sp, vy: Math.sin(a)*sp - 5,
                        s: 6 + Math.random()*8, c: cols[i % cols.length], rot: Math.random()*6,
                        vr: (Math.random()-.5)*.5, life: 60 + Math.random()*45, max: 105 });
                }
                if (!craf) craf = requestAnimationFrame(ccLoop);
            }
            function ccLoop() {
                cx.clearRect(0, 0, cc.width, cc.height);
                for (const p of parts) {
                    p.vy += .16; p.x += p.vx; p.y += p.vy; p.rot += p.vr; p.life--;
                    cx.save(); cx.translate(p.x, p.y); cx.rotate(p.rot);
                    cx.globalAlpha = Math.max(0, p.life / p.max); cx.fillStyle = p.c;
                    cx.fillRect(-p.s/2, -p.s/2, p.s, p.s*.62); cx.restore();
                }
                parts = parts.filter(p => p.life > 0 && p.y < cc.height + 50);
                craf = parts.length ? requestAnimationFrame(ccLoop) : null;
            }
        })();

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
            if (prevVid) {
                prevVid.classList.remove('show');
                prevVid.src = '';
            }
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
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>
