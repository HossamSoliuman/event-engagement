<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->name }} — Vidiwall</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root { --p:{{ $event->primary_color }};--bg:{{ $event->secondary_color }};--acc:{{ $event->accent_color }}; }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html,body{width:100%;height:100%;overflow:hidden;background:var(--bg);color:#fff;font-family:'DM Sans',sans-serif}

        .wrap{width:100%;height:100%;display:grid;grid-template-rows:60px 1fr 48px}

        .v-header{display:flex;align-items:center;justify-content:space-between;padding:0 36px;background:rgba(0,0,0,.45);border-bottom:2px solid var(--p)}
        .v-brand{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--p)}
        .v-name{font-family:'Syne',sans-serif;font-size:18px;font-weight:700}
        .v-clock{font-size:18px;color:var(--acc);font-variant-numeric:tabular-nums;font-weight:600}

        .stage{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center}

        .stage-bg{position:absolute;inset:0;background-size:cover;background-position:center;filter:blur(40px) brightness(.35);transform:scale(1.1);transition:background-image .8s ease}

        .photo-frame{position:relative;z-index:2;max-height:84%;max-width:70%;border-radius:14px;overflow:hidden;box-shadow:0 0 80px rgba(0,0,0,.8),0 0 0 4px var(--p);animation:photoIn .5s cubic-bezier(.22,1,.36,1)}
        @keyframes photoIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}
        .photo-frame img{display:block;max-height:82vh;max-width:68vw;object-fit:contain}

        .video-frame{position:relative;z-index:2;max-height:84%;max-width:75%;border-radius:14px;overflow:hidden;box-shadow:0 0 80px rgba(0,0,0,.8),0 0 0 4px var(--p);animation:photoIn .5s cubic-bezier(.22,1,.36,1)}
        .video-frame video{display:block;max-height:82vh;max-width:72vw;object-fit:contain;background:#000}

        .uploader{position:absolute;bottom:26px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.75);border:1px solid var(--acc);border-radius:40px;padding:8px 24px;font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--acc);z-index:3;white-space:nowrap;animation:tagIn .4s .2s both}
        @keyframes tagIn{from{opacity:0;transform:translateX(-50%) translateY(18px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}

        .slide-counter{position:absolute;top:14px;right:18px;z-index:4;background:rgba(0,0,0,.6);border-radius:20px;padding:5px 14px;font-size:12px;font-weight:600;color:rgba(255,255,255,.7)}

        .slide-progress{position:absolute;bottom:0;left:0;height:3px;background:var(--p);z-index:4;transition:width linear}

        .live-badge{position:absolute;top:14px;left:18px;z-index:4;background:var(--p);color:#fff;font-family:'Syne',sans-serif;font-size:11px;font-weight:800;padding:5px 14px;border-radius:40px;letter-spacing:2px;animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.55}}

        .video-badge{position:absolute;top:14px;left:18px;z-index:4;background:rgba(99,102,241,.9);color:#fff;font-family:'Syne',sans-serif;font-size:11px;font-weight:800;padding:5px 14px;border-radius:40px;letter-spacing:2px}

        .idle{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;text-align:center;padding:40px;position:relative;z-index:2}
        .idle-icon{font-size:72px;margin-bottom:20px}
        .idle-title{font-family:'Syne',sans-serif;font-size:clamp(32px,5vw,56px);font-weight:800;color:var(--p)}
        .idle-sub{font-size:clamp(14px,2vw,22px);color:rgba(255,255,255,.55);margin-top:10px}
        .idle-qr{width:160px;height:160px;background:#fff;padding:8px;border-radius:12px;margin:20px auto}
        .idle-url{font-size:clamp(13px,1.5vw,18px);color:var(--acc);font-weight:600;letter-spacing:1px;margin-top:8px}

        .v-footer{display:flex;align-items:center;gap:16px;padding:0 36px;background:var(--p);border-top:2px solid var(--acc)}
        .ticker{font-size:13px;opacity:.9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .ticker strong{font-family:'Syne',sans-serif;font-size:13px;margin-right:8px}
        .v-footer img{height:26px;margin-left:auto;opacity:.8;flex-shrink:0}

        .overlay-text{position:absolute;bottom:55px;left:0;right:0;z-index:3;text-align:center}
        .overlay-pill{display:inline-block;background:rgba(0,0,0,.65);border-radius:30px;padding:6px 22px;font-size:clamp(12px,1.5vw,15px);color:rgba(255,255,255,.7);letter-spacing:.5px}
    </style>
</head>
<body>
<div class="wrap">

    <header class="v-header">
        <div class="v-brand">EventBomb</div>
        <div class="v-name">{{ $event->name }}</div>
        <div class="v-clock" id="clock">--:--:--</div>
    </header>

    <div class="stage" id="stage">
        <div class="stage-bg" id="stageBg"></div>

        {{-- Idle state --}}
        <div class="idle" id="idleState">
            <div class="idle-icon">&#128247;</div>
            <div class="idle-title">Foto Bomb</div>
            <div class="idle-sub">Scan the QR · Upload your photo or video · Go live!</div>
            @if($event->qr_code_path)
                <img src="{{ $event->qr_code_url }}" class="idle-qr" alt="QR">
            @endif
            <div class="idle-url">{{ $event->getGuestUrl() }}</div>
        </div>

        {{-- Photo frame --}}
        <div class="photo-frame" id="photoFrame" style="display:none">
            <img id="liveImg" src="" alt="">
        </div>

        {{-- Video frame --}}
        <div class="video-frame" id="videoFrame" style="display:none">
            <video id="liveVideo" src="" autoplay muted playsinline></video>
        </div>

        {{-- Uploader name --}}
        <div class="uploader" id="uploaderTag" style="display:none">
            <span id="uploaderName"></span>
        </div>

        {{-- Live badge (photo) --}}
        <div class="live-badge" id="liveBadge" style="display:none">&#9679; LIVE</div>

        {{-- Video badge --}}
        <div class="video-badge" id="videoBadge" style="display:none">&#9654; VIDEO</div>

        {{-- Slideshow counter --}}
        <div class="slide-counter" id="slideCounter" style="display:none"></div>

        {{-- Progress bar (slideshow) --}}
        <div class="slide-progress" id="slideProgress" style="width:0;display:none"></div>

        {{-- Overlay text --}}
        @if($event->vidiwall_overlay_text)
        <div class="overlay-text" id="overlayText" style="display:none">
            <span class="overlay-pill">{{ $event->vidiwall_overlay_text }}</span>
        </div>
        @endif
    </div>

    <footer class="v-footer">
        <div class="ticker">
            <strong>JOIN:</strong>
            Scan the QR code · Upload your photo or video · See it live on screen!
        </div>
        @if($event->sponsor_logo_path)
            <img src="{{ $event->sponsor_logo_url }}" alt="Sponsor">
        @endif
    </footer>
</div>

<script>
const SLUG      = '{{ $event->slug }}';
const SHOW_NAME = {{ $event->vidiwall_show_uploader ? 'true' : 'false' }};

setInterval(() => {
    document.getElementById('clock').textContent = new Date().toLocaleTimeString('en-GB',{hour12:false});
}, 1000);

let lastFotoId   = null;
let slideIndex   = 0;
let slideTimer   = null;
let slideFotos   = [];

function setBackground(url) {
    document.getElementById('stageBg').style.backgroundImage = url ? `url('${url}')` : '';
}

function showUploaderTag(name) {
    const upTag  = document.getElementById('uploaderTag');
    const nameSp = document.getElementById('uploaderName');
    if (SHOW_NAME && name) { upTag.style.display = 'block'; nameSp.textContent = name; }
    else { upTag.style.display = 'none'; }
}

function showSlideInfo(slideNum, total) {
    const counter = document.getElementById('slideCounter');
    if (slideNum !== undefined) {
        counter.style.display = 'block';
        counter.textContent   = `${slideNum} / ${total}`;
    } else {
        counter.style.display = 'none';
    }
}

function showPhoto(url, name, slideNum, total) {
    document.getElementById('idleState').style.display   = 'none';
    document.getElementById('videoFrame').style.display  = 'none';
    document.getElementById('videoBadge').style.display  = 'none';

    const liveVideo = document.getElementById('liveVideo');
    liveVideo.pause();
    liveVideo.src = '';

    document.getElementById('liveBadge').style.display = 'block';

    const overlay = document.getElementById('overlayText');
    if (overlay) overlay.style.display = 'block';

    showUploaderTag(name);
    showSlideInfo(slideNum, total);

    const frame = document.getElementById('photoFrame');
    const img   = document.getElementById('liveImg');
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
    document.getElementById('idleState').style.display   = 'none';
    document.getElementById('photoFrame').style.display  = 'none';
    document.getElementById('liveBadge').style.display   = 'none';

    document.getElementById('videoBadge').style.display = 'block';

    const overlay = document.getElementById('overlayText');
    if (overlay) overlay.style.display = 'block';

    showUploaderTag(name);
    showSlideInfo(slideNum, total);

    const frame     = document.getElementById('videoFrame');
    const liveVideo = document.getElementById('liveVideo');
    liveVideo.src = videoUrl;
    liveVideo.onended = onEnded || null;
    liveVideo.play().catch(() => {});
    frame.style.display = 'block';

    setBackground(null);
}

function showIdle() {
    document.getElementById('idleState').style.display   = 'flex';
    document.getElementById('photoFrame').style.display  = 'none';
    document.getElementById('videoFrame').style.display  = 'none';
    document.getElementById('uploaderTag').style.display = 'none';
    document.getElementById('liveBadge').style.display   = 'none';
    document.getElementById('videoBadge').style.display  = 'none';
    document.getElementById('slideCounter').style.display= 'none';
    document.getElementById('slideProgress').style.display='none';
    const overlay = document.getElementById('overlayText');
    if (overlay) overlay.style.display = 'none';
    setBackground(null);
    lastFotoId = null;
    slideFotos = [];
    clearTimeout(slideTimer);
    document.getElementById('liveVideo').pause();
    document.getElementById('liveVideo').src = '';
}

function startSlideshow(fotos, interval) {
    slideFotos = fotos;
    if (!slideFotos.length) { showIdle(); return; }
    slideIndex = 0;

    const progress = document.getElementById('slideProgress');
    progress.style.display = 'block';

    function nextSlide() {
        const f = slideFotos[slideIndex];
        const goNext = () => {
            slideIndex = (slideIndex + 1) % slideFotos.length;
            nextSlide();
        };

        if (f.media_type === 'video' && f.video_url) {
            progress.style.transition = 'none';
            progress.style.width = '0%';
            showVideo(f.video_url, f.uploader, slideIndex + 1, slideFotos.length, goNext);
            slideTimer = setTimeout(goNext, (interval + 2) * 1000);
        } else {
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
        const res  = await fetch(`/screen/${SLUG}/feed`);
        const data = await res.json();

        if (data.mode === 'slideshow') {
            if (!data.fotos.length) { showIdle(); return; }
            const newIds = data.fotos.map(f=>f.id).join(',');
            const oldIds = slideFotos.map(f=>f.id).join(',');
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
    } catch(e) {}
}

poll();
setInterval(poll, 3000);
</script>
</body>
</html>
