<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->name }} — Vidiwall</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: {{ $event->primary_color }};
            --bg: {{ $event->secondary_color }};
            --accent: {{ $event->accent_color }};
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            background: var(--bg);
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            overflow: hidden;
        }

        /* Layout */
        .vidi-wrap {
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-rows: auto 1fr auto;
        }

        /* Top bar */
        .vidi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 40px;
            background: rgba(0, 0, 0, .4);
            border-bottom: 2px solid var(--primary);
        }

        .vidi-header .brand {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
        }

        .vidi-header .event-name {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 700;
        }

        .vidi-header .clock {
            font-size: 20px;
            color: var(--accent);
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        /* Main photo area */
        .photo-stage {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .photo-bg {
            position: absolute;
            inset: 0;
            filter: blur(40px) brightness(.4);
            background-size: cover;
            background-position: center;
            transform: scale(1.1);
            transition: background-image .5s;
        }

        .photo-frame {
            position: relative;
            z-index: 1;
            max-height: 80%;
            max-width: 70%;
            border-radius: 16px;
            box-shadow: 0 0 60px rgba(0, 0, 0, .8), 0 0 0 4px var(--primary);
            overflow: hidden;
            animation: photoIn .6s cubic-bezier(.22, 1, .36, 1);
        }

        @keyframes photoIn {
            from {
                opacity: 0;
                transform: scale(.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .photo-frame img {
            display: block;
            max-height: 75vh;
            max-width: 68vw;
            object-fit: contain;
        }

        /* Uploader tag */
        .uploader-tag {
            position: absolute;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, .7);
            border: 1px solid var(--accent);
            border-radius: 40px;
            padding: 10px 28px;
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--accent);
            z-index: 2;
            white-space: nowrap;
            animation: tagIn .4s .3s both;
        }

        @keyframes tagIn {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        /* Idle / waiting state */
        .idle-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 40px;
        }

        .idle-qr {
            font-size: 80px;
            margin-bottom: 24px;
        }

        .idle-title {
            font-family: 'Syne', sans-serif;
            font-size: 48px;
            font-weight: 800;
            color: var(--primary);
        }

        .idle-subtitle {
            font-size: 22px;
            color: rgba(255, 255, 255, .6);
            margin-top: 12px;
        }

        .idle-url {
            margin-top: 24px;
            font-size: 18px;
            color: var(--accent);
            font-weight: 600;
            letter-spacing: 1px;
        }

        .qr-img {
            width: 180px;
            height: 180px;
            background: #fff;
            padding: 8px;
            border-radius: 12px;
            margin: 20px auto;
        }

        /* Footer ticker */
        .vidi-footer {
            background: var(--primary);
            padding: 12px 40px;
            display: flex;
            align-items: center;
            gap: 20px;
            border-top: 2px solid var(--accent);
        }

        .ticker-label {
            font-weight: 800;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            white-space: nowrap;
        }

        .ticker-text {
            font-size: 14px;
            opacity: .9;
        }

        /* Live badge */
        .live-badge {
            position: absolute;
            top: 20px;
            right: 28px;
            z-index: 3;
            background: var(--primary);
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 800;
            padding: 6px 16px;
            border-radius: 40px;
            letter-spacing: 2px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .6;
            }
        }
    </style>
</head>

<body>
    <div class="vidi-wrap">

        <!-- Header -->
        <div class="vidi-header">
            <div class="brand">⚡ EventBomb</div>
            <div class="event-name">{{ $event->name }}</div>
            <div class="clock" id="clock">--:--:--</div>
        </div>

        <!-- Photo Stage -->
        <div class="photo-stage" id="stage">
            <div class="photo-bg" id="photoBg"></div>

            <div class="idle-screen" id="idleScreen">
                <div class="idle-qr">📷</div>
                <div class="idle-title">Foto Bomb Live</div>
                <div class="idle-subtitle">Scan the QR code to upload your photo!</div>
                @if ($event->qr_code_path)
                    <img src="{{ $event->qr_code_url }}" class="qr-img" alt="QR">
                @endif
                <div class="idle-url">{{ $event->getGuestUrl() }}</div>
            </div>

            <div class="photo-frame" id="photoFrame" style="display:none;">
                <img id="livePhoto" src="" alt="Live photo">
            </div>

            <div class="uploader-tag" id="uploaderTag" style="display:none;">
                📸 <span id="uploaderName">Guest</span>
            </div>

            <div class="live-badge" id="liveBadge" style="display:none;">● LIVE</div>
        </div>

        <!-- Footer -->
        <div class="vidi-footer">
            <span class="ticker-label">📱 JOIN:</span>
            <span class="ticker-text">Scan the QR code on screen · Upload your photo · Go live!</span>
            @if ($event->sponsor_logo_path)
                <img src="{{ Storage::disk('public')->url($event->sponsor_logo_path) }}"
                    style="height:28px; margin-left:auto; opacity:.8;">
            @endif
        </div>
    </div>

    <script>
        // Clock
        function updateClock() {
            document.getElementById('clock').textContent = new Date().toLocaleTimeString('en-GB', {
                hour12: false
            });
        }
        updateClock();
        setInterval(updateClock, 1000);

        // Photo polling — replace with Pusher/Echo in V2 for instant updates
        let lastFotoId = null;

        async function checkFeed() {
            try {
                const res = await fetch('/screen/{{ $event->slug }}/feed');
                const data = await res.json();

                if (data.foto && data.foto.id !== lastFotoId) {
                    lastFotoId = data.foto.id;
                    showPhoto(data.foto.url, data.foto.uploader || 'A Fan');
                } else if (!data.foto && lastFotoId !== null) {
                    lastFotoId = null;
                    showIdle();
                }
            } catch (e) {
                /* network glitch, ignore */ }
        }

        function showPhoto(url, name) {
            document.getElementById('idleScreen').style.display = 'none';
            document.getElementById('liveBadge').style.display = 'block';
            document.getElementById('uploaderTag').style.display = 'block';
            document.getElementById('uploaderName').textContent = name;

            const frame = document.getElementById('photoFrame');
            const img = document.getElementById('livePhoto');

            // Animate new photo in
            frame.style.display = 'none';
            img.src = url;
            img.onload = () => {
                frame.style.display = 'block';
                frame.style.animation = 'none';
                frame.offsetHeight; // reflow
                frame.style.animation = '';
            };

            document.getElementById('photoBg').style.backgroundImage = `url('${url}')`;
        }

        function showIdle() {
            document.getElementById('idleScreen').style.display = 'flex';
            document.getElementById('photoFrame').style.display = 'none';
            document.getElementById('uploaderTag').style.display = 'none';
            document.getElementById('liveBadge').style.display = 'none';
            document.getElementById('photoBg').style.backgroundImage = '';
        }

        // Poll every 3 seconds (V2: replace with websocket)
        checkFeed();
        setInterval(checkFeed, 3000);
    </script>
</body>

</html>
