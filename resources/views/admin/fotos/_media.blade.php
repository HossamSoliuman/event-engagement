{{-- The preview inside a Foto Queue or Archived card: a paused video frame with its length, or the photo thumbnail. --}}
@if($foto->isVideo())
    <video src="{{ $foto->media_url }}#t=0.5" muted playsinline preload="metadata"></video>
    <span class="fq-kind"><i data-lucide="play" class="lucide-icon"></i>{{ $foto->video_duration ? round($foto->video_duration, 1).'s' : 'Video' }}</span>
@else
    <img src="{{ $foto->thumbnail_url }}" alt="" loading="lazy" decoding="async">
@endif
