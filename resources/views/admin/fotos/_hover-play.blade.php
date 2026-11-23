{{-- Video cards in the Foto Queue and its Archived tab play muted while the mouse rests on them. --}}
<script>
    (() => {
        const target = event => {
            if (event.pointerType !== 'mouse') return null;
            const media = event.target.closest('.fq-media, .fa-media');
            const video = media?.querySelector('video');
            return video && !media.contains(event.relatedTarget) ? { media, video } : null;
        };

        document.addEventListener('pointerover', event => {
            const hit = target(event);
            if (!hit) return;
            hit.media.classList.add('is-playing');
            hit.video.play().catch(() => hit.media.classList.remove('is-playing'));
        });

        document.addEventListener('pointerout', event => {
            const hit = target(event);
            if (!hit) return;
            hit.media.classList.remove('is-playing');
            hit.video.pause();
            hit.video.currentTime = .5;
        });
    })();
</script>
