{{-- The preview inside a Foto Queue or Archived card: a paused video frame with a play button, or the photo thumbnail, each labelled with its type. --}}
@if($foto->isVideo())
    <video src="{{ $foto->media_url }}#t=0.5" muted playsinline loop preload="metadata"></video>
    <span class="fq-play" aria-hidden="true"><i data-lucide="play" class="lucide-icon"></i></span>
    <span class="fq-kind is-video"><i data-lucide="video" class="lucide-icon"></i>Video{{ $foto->video_duration ? ' · '.round($foto->video_duration, 1).'s' : '' }}</span>
@else
    <img src="{{ $foto->thumbnail_url }}" alt="" loading="lazy" decoding="async">
    <span class="fq-kind"><i data-lucide="image" class="lucide-icon"></i>Photo</span>
@endif
<span class="fq-preview" aria-hidden="true"><i data-lucide="eye" class="lucide-icon"></i><span class="fq-preview-label">Preview</span></span>
