@extends('layouts.admin')
@section('title', 'Media Downloads')
@section('page-title', 'Media Downloads')

@section('content')
@php
    $formatBytes = static function (int|float|null $bytes): string {
        $bytes = (int) $bytes;

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1).' '.$units[$power];
    };
@endphp

<section class="media-vault-hero">
    <div class="media-vault-copy">
        <span class="media-vault-kicker"><i data-lucide="hard-drive-download" class="lucide-icon"></i> Platform archive</span>
        <h1>Take every event memory with you.</h1>
        <p>Build a clean ZIP of original guest uploads. Narrow it to one event, one media type, or a moderation status before downloading.</p>
    </div>
    <div class="media-vault-total">
        <span>Archive footprint</span>
        <strong>{{ $formatBytes($summary['bytes']) }}</strong>
        <small>{{ number_format($summary['uploads']) }} original files indexed</small>
    </div>
</section>

<div class="media-stat-grid">
    <article class="media-stat">
        <span class="media-stat-icon media-stat-icon-all"><i data-lucide="files" class="lucide-icon"></i></span>
        <div><strong>{{ number_format($summary['uploads']) }}</strong><span>All uploads</span></div>
    </article>
    <article class="media-stat">
        <span class="media-stat-icon media-stat-icon-photo"><i data-lucide="image" class="lucide-icon"></i></span>
        <div><strong>{{ number_format($summary['photos']) }}</strong><span>Images</span></div>
    </article>
    <article class="media-stat">
        <span class="media-stat-icon media-stat-icon-video"><i data-lucide="video" class="lucide-icon"></i></span>
        <div><strong>{{ number_format($summary['videos']) }}</strong><span>Videos</span></div>
    </article>
    <article class="media-stat">
        <span class="media-stat-icon media-stat-icon-event"><i data-lucide="calendar-range" class="lucide-icon"></i></span>
        <div><strong>{{ number_format($events->count()) }}</strong><span>Events</span></div>
    </article>
</div>

<div class="media-workspace">
    <section class="card media-builder">
        <div class="card-header">
            <div>
                <span class="builder-step">01 / Build archive</span>
                <h2>Choose what to download</h2>
            </div>
            <span class="secure-note"><i data-lucide="shield-check" class="lucide-icon"></i> Admin only</span>
        </div>
        <form method="POST" action="{{ route('admin.media-downloads.download') }}" id="mediaDownloadForm" class="card-body">
            @csrf

            <div class="form-group">
                <label for="event_id" class="form-label">Event</label>
                <div class="select-shell">
                    <i data-lucide="calendar" class="lucide-icon"></i>
                    <select name="event_id" id="event_id" class="form-control">
                        <option value="">Full platform — all events</option>
                        @foreach($events as $event)
                            <option
                                value="{{ $event->id }}"
                                data-total="{{ $event->uploads_count }}"
                                data-photos="{{ $event->photos_count }}"
                                data-videos="{{ $event->videos_count }}"
                                data-bytes="{{ (int) $event->uploads_bytes }}"
                                @selected((string) old('event_id') === (string) $event->id)
                            >
                                {{ $event->name }} — {{ number_format($event->uploads_count) }} uploads
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <fieldset class="form-group">
                <legend class="form-label">Media type</legend>
                <div class="media-choice-grid">
                    <label class="media-choice">
                        <input type="radio" name="media_type" value="all" @checked(old('media_type', 'all') === 'all')>
                        <span class="media-choice-body">
                            <i data-lucide="layers-3" class="lucide-icon"></i>
                            <strong>Everything</strong>
                            <small>Images + videos</small>
                            <i data-lucide="check" class="choice-check lucide-icon"></i>
                        </span>
                    </label>
                    <label class="media-choice">
                        <input type="radio" name="media_type" value="photo" @checked(old('media_type') === 'photo')>
                        <span class="media-choice-body">
                            <i data-lucide="image" class="lucide-icon"></i>
                            <strong>Images only</strong>
                            <small>Original photos</small>
                            <i data-lucide="check" class="choice-check lucide-icon"></i>
                        </span>
                    </label>
                    <label class="media-choice">
                        <input type="radio" name="media_type" value="video" @checked(old('media_type') === 'video')>
                        <span class="media-choice-body">
                            <i data-lucide="video" class="lucide-icon"></i>
                            <strong>Videos only</strong>
                            <small>Original clips</small>
                            <i data-lucide="check" class="choice-check lucide-icon"></i>
                        </span>
                    </label>
                </div>
            </fieldset>

            <div class="filter-row">
                <div class="form-group mb-0">
                    <label for="status" class="form-label">Moderation status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="all" @selected(old('status', 'all') === 'all')>All statuses</option>
                        <option value="approved" @selected(old('status') === 'approved')>Approved only</option>
                        <option value="pending" @selected(old('status') === 'pending')>Pending only</option>
                        <option value="rejected" @selected(old('status') === 'rejected')>Rejected only</option>
                    </select>
                </div>
                <label class="manifest-toggle">
                    <input type="checkbox" name="include_manifest" value="1" @checked(old('include_manifest', '1'))>
                    <span class="manifest-box"><i data-lucide="file-spreadsheet" class="lucide-icon"></i></span>
                    <span><strong>Include manifest.csv</strong><small>Uploader, event, status, and file details</small></span>
                </label>
            </div>

            <div class="archive-summary">
                <div>
                    <span>Your archive</span>
                    <strong id="archiveEstimate">{{ number_format($summary['uploads']) }} files</strong>
                    <small id="archiveScope">Across the full platform</small>
                </div>
                <button class="btn btn-primary btn-lg" type="submit" @disabled($summary['uploads'] === 0)>
                    <i data-lucide="download" class="lucide-icon"></i> Create ZIP
                </button>
            </div>
        </form>
    </section>

    <aside class="card recent-media">
        <div class="card-header">
            <div>
                <span class="builder-step">Latest activity</span>
                <h3>Recently uploaded</h3>
            </div>
            <i data-lucide="radio-tower" class="recent-signal lucide-icon"></i>
        </div>
        <div class="recent-list">
            @forelse($recentUploads as $upload)
                <div class="recent-item">
                    <span class="recent-type {{ $upload->isVideo() ? 'is-video' : 'is-photo' }}">
                        <i data-lucide="{{ $upload->isVideo() ? 'video' : 'image' }}" class="lucide-icon"></i>
                    </span>
                    <div class="recent-copy">
                        <strong>{{ $upload->original_filename ?: ucfirst($upload->media_type).' '.$upload->id }}</strong>
                        <span>{{ $upload->event?->name ?? 'Deleted event' }}</span>
                    </div>
                    <time title="{{ $upload->created_at }}">{{ $upload->created_at?->diffForHumans(null, true) }}</time>
                </div>
            @empty
                <div class="empty-state compact">
                    <i data-lucide="inbox" class="lucide-icon"></i>
                    <p>No media has been uploaded yet.</p>
                </div>
            @endforelse
        </div>
        <div class="archive-structure">
            <i data-lucide="folder-tree" class="lucide-icon"></i>
            <div><strong>Ready to hand off</strong><span>Files are grouped by event and then by images or videos.</span></div>
        </div>
    </aside>
</div>

<section class="card event-media-table">
    <div class="card-header">
        <div>
            <span class="builder-step">Event inventory</span>
            <h3>Uploads by event</h3>
        </div>
        <span class="text-muted text-xs">{{ $events->count() }} event{{ $events->count() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Images</th>
                    <th>Videos</th>
                    <th>Storage</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                    <tr>
                        <td>
                            <strong>{{ $event->name }}</strong>
                            <span class="event-slug">/e/{{ $event->slug }}</span>
                        </td>
                        <td>{{ number_format($event->photos_count) }}</td>
                        <td>{{ number_format($event->videos_count) }}</td>
                        <td>{{ $formatBytes($event->uploads_bytes) }}</td>
                        <td>
                            <button type="button" class="btn btn-ghost btn-sm choose-event" data-event-id="{{ $event->id }}">
                                Select <i data-lucide="arrow-up-right" class="lucide-icon"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state compact"><p>No events available.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('styles')
<style>
    .media-vault-hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:end;gap:32px;padding:30px 32px;margin-bottom:16px;border:1px solid rgba(255,61,0,.3);border-radius:16px;background:radial-gradient(circle at 76% 10%,rgba(255,61,0,.19),transparent 32%),linear-gradient(125deg,#171728 0%,#10101d 62%,#1c1115 100%)}
    .media-vault-hero::after{content:"";position:absolute;right:-30px;top:-80px;width:260px;height:260px;border:1px solid rgba(255,255,255,.045);border-radius:50%;box-shadow:0 0 0 28px rgba(255,255,255,.018),0 0 0 58px rgba(255,255,255,.012);pointer-events:none}
    .media-vault-copy{position:relative;z-index:1;max-width:690px}
    .media-vault-kicker,.builder-step{display:flex;align-items:center;gap:7px;color:var(--red);font-size:10px;font-weight:800;letter-spacing:1.8px;text-transform:uppercase}
    .media-vault-copy h1{margin-top:9px;font-size:clamp(25px,3.1vw,42px);line-height:1.05;letter-spacing:-1.4px}
    .media-vault-copy p{max-width:620px;margin-top:12px;color:#9a9ab5;font-size:14px;line-height:1.6}
    .media-vault-total{position:relative;z-index:1;min-width:180px;padding-left:22px;border-left:1px solid rgba(255,255,255,.12)}
    .media-vault-total span,.media-vault-total small{display:block;color:var(--muted);font-size:11px}
    .media-vault-total strong{display:block;margin:5px 0 3px;font-family:'Syne',sans-serif;font-size:29px;color:#fff}
    .media-stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
    .media-stat{display:flex;align-items:center;gap:13px;padding:14px 16px;background:var(--card);border:1px solid var(--border);border-radius:11px}
    .media-stat-icon{display:grid;place-items:center;width:38px;height:38px;border-radius:9px}
    .media-stat-icon .lucide-icon{width:18px;height:18px}
    .media-stat-icon-all{color:#c4b5fd;background:rgba(139,92,246,.13)}.media-stat-icon-photo{color:#60a5fa;background:rgba(59,130,246,.13)}.media-stat-icon-video{color:#fb7185;background:rgba(244,63,94,.13)}.media-stat-icon-event{color:#facc15;background:rgba(250,204,21,.12)}
    .media-stat strong{display:block;font-family:'Syne',sans-serif;font-size:20px;line-height:1}.media-stat span{display:block;margin-top:3px;color:var(--muted);font-size:11px}
    .media-workspace{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(270px,.75fr);gap:16px;align-items:start}
    .media-builder .card-header{padding:19px 21px}.media-builder .card-header h2{margin-top:4px;font-size:19px}
    .secure-note{display:flex;align-items:center;gap:6px;color:var(--green);font-size:11px}.secure-note .lucide-icon{width:15px;height:15px}
    .select-shell{position:relative}.select-shell>i{position:absolute;left:13px;top:50%;transform:translateY(-50%);z-index:1;color:var(--muted);pointer-events:none}.select-shell .form-control{padding-left:40px}
    select.form-control{appearance:none;background-image:linear-gradient(45deg,transparent 50%,var(--muted) 50%),linear-gradient(135deg,var(--muted) 50%,transparent 50%);background-position:calc(100% - 17px) 50%,calc(100% - 12px) 50%;background-size:5px 5px,5px 5px;background-repeat:no-repeat;padding-right:36px}
    .media-choice-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
    .media-choice{cursor:pointer}.media-choice input{position:absolute;opacity:0;pointer-events:none}
    .media-choice-body{position:relative;display:flex;flex-direction:column;gap:5px;min-height:104px;padding:15px;border:1px solid var(--border);border-radius:10px;background:var(--dark);transition:border-color .18s,background .18s,transform .18s}
    .media-choice:hover .media-choice-body{transform:translateY(-1px);border-color:#3b3b5b}.media-choice>input:checked+.media-choice-body{border-color:var(--red);background:rgba(255,61,0,.055);box-shadow:inset 0 0 0 1px rgba(255,61,0,.12)}
    .media-choice-body>.lucide-icon:not(.choice-check){width:20px;height:20px;margin-bottom:5px;color:var(--muted)}.media-choice>input:checked+.media-choice-body>.lucide-icon:not(.choice-check){color:var(--red)}
    .media-choice-body strong{font-size:13px}.media-choice-body small{color:var(--muted);font-size:11px}.choice-check{position:absolute;right:11px;top:11px;width:16px;height:16px;padding:3px;border-radius:50%;background:var(--red);color:white;opacity:0;transform:scale(.75);transition:.18s}.media-choice>input:checked+.media-choice-body .choice-check{opacity:1;transform:scale(1)}
    .filter-row{display:grid;grid-template-columns:minmax(180px,.7fr) minmax(260px,1.3fr);gap:12px;align-items:end}
    .manifest-toggle{display:flex;align-items:center;gap:10px;min-height:61px;padding:10px 12px;border:1px solid var(--border);border-radius:9px;cursor:pointer}.manifest-toggle>input{position:absolute;opacity:0}.manifest-box{display:grid;place-items:center;width:34px;height:34px;border-radius:8px;background:rgba(34,197,94,.1);color:var(--green)}.manifest-toggle strong,.manifest-toggle small{display:block}.manifest-toggle strong{font-size:12px}.manifest-toggle small{margin-top:2px;color:var(--muted);font-size:10px}.manifest-toggle:has(input:not(:checked)){opacity:.58}.manifest-toggle:has(input:not(:checked)) .manifest-box{color:var(--muted);background:rgba(255,255,255,.04)}
    .archive-summary{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-top:20px;padding:17px 18px;border-radius:11px;background:linear-gradient(90deg,rgba(255,61,0,.09),rgba(255,61,0,.025));border:1px solid rgba(255,61,0,.19)}
    .archive-summary span,.archive-summary small{display:block;color:var(--muted);font-size:10px}.archive-summary strong{display:block;margin:3px 0;font-family:'Syne',sans-serif;font-size:18px}
    .recent-media{overflow:hidden}.recent-media .card-header h3{margin-top:4px}.recent-signal{color:var(--red);animation:signalPulse 2s ease-in-out infinite}
    @keyframes signalPulse{50%{opacity:.35}}
    .recent-list{padding:6px 16px}.recent-item{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04)}.recent-item:last-child{border-bottom:0}
    .recent-type{display:grid;place-items:center;width:32px;height:32px;flex:0 0 32px;border-radius:8px}.recent-type.is-photo{color:#60a5fa;background:rgba(59,130,246,.1)}.recent-type.is-video{color:#fb7185;background:rgba(244,63,94,.1)}.recent-type .lucide-icon{width:15px;height:15px}
    .recent-copy{min-width:0;flex:1}.recent-copy strong,.recent-copy span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.recent-copy strong{font-size:11px}.recent-copy span,.recent-item time{color:var(--muted);font-size:10px}.recent-item time{flex-shrink:0}
    .archive-structure{display:flex;gap:10px;margin:4px 14px 14px;padding:12px;border:1px dashed #373751;border-radius:9px;background:rgba(255,255,255,.015)}.archive-structure>.lucide-icon{flex:0 0 auto;color:var(--gold)}.archive-structure strong,.archive-structure span{display:block;font-size:10px}.archive-structure span{margin-top:3px;color:var(--muted);line-height:1.45}
    .event-media-table{margin-top:16px}.event-media-table .card-header h3{margin-top:4px}.event-media-table td strong,.event-slug{display:block}.event-slug{margin-top:2px;color:var(--muted);font-size:10px}.event-media-table td:last-child{text-align:right}
    .empty-state.compact{padding:28px 15px}.empty-state.compact>.lucide-icon{width:24px;height:24px;margin-bottom:7px}
    @media(max-width:1050px){.media-workspace{grid-template-columns:1fr}.recent-list{display:grid;grid-template-columns:1fr 1fr;column-gap:20px}}
    @media(max-width:760px){.media-vault-hero{grid-template-columns:1fr;padding:24px}.media-vault-total{padding:14px 0 0;border-left:0;border-top:1px solid rgba(255,255,255,.1)}.media-stat-grid{grid-template-columns:1fr 1fr}.media-choice-grid,.filter-row{grid-template-columns:1fr}.recent-list{grid-template-columns:1fr}.archive-summary{align-items:stretch;flex-direction:column}.archive-summary .btn{justify-content:center}.event-media-table th:nth-child(4),.event-media-table td:nth-child(4){display:none}}
    @media(max-width:430px){.media-stat-grid{grid-template-columns:1fr}.media-choice-grid{grid-template-columns:1fr 1fr}.media-choice:first-child{grid-column:1/-1}.media-choice-body{min-height:92px}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const eventSelect = document.getElementById('event_id');
    const statusSelect = document.getElementById('status');
    const estimate = document.getElementById('archiveEstimate');
    const scope = document.getElementById('archiveScope');
    const allCounts = @json(['all' => $summary['uploads'], 'photo' => $summary['photos'], 'video' => $summary['videos']]);

    const updateEstimate = () => {
        const option = eventSelect.options[eventSelect.selectedIndex];
        const mediaType = document.querySelector('input[name="media_type"]:checked')?.value || 'all';
        const status = statusSelect.value;
        const count = option.value
            ? Number(option.dataset[mediaType === 'all' ? 'total' : mediaType === 'photo' ? 'photos' : 'videos'] || 0)
            : Number(allCounts[mediaType] || 0);

        estimate.textContent = `${status === 'all' ? '' : 'Up to '}${count.toLocaleString()} ${count === 1 ? 'file' : 'files'}`;
        const scopeLabel = option.value ? option.text.split(' — ')[0] : 'Across the full platform';
        scope.textContent = status === 'all' ? scopeLabel : `${scopeLabel} · ${status} only`;
    };

    eventSelect.addEventListener('change', updateEstimate);
    statusSelect.addEventListener('change', updateEstimate);
    document.querySelectorAll('input[name="media_type"]').forEach(input => input.addEventListener('change', updateEstimate));

    document.querySelectorAll('.choose-event').forEach(button => {
        button.addEventListener('click', () => {
            eventSelect.value = button.dataset.eventId;
            updateEstimate();
            document.querySelector('.media-builder').scrollIntoView({ behavior: 'smooth', block: 'start' });
            eventSelect.focus({ preventScroll: true });
        });
    });

    updateEstimate();
});
</script>
@endpush
