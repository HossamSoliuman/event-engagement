@extends('layouts.admin')
@section('title', isset($event) ? 'Edit '.$event->name : 'New Event')
@section('page-title', isset($event) ? 'Edit Event' : 'New Event')

@section('content')
<div class="max-w-xl">
<form method="POST"
      action="{{ isset($event) ? route('admin.events.update',$event) : route('admin.events.store') }}"
      enctype="multipart/form-data">
    @csrf
    @if(isset($event)) @method('PUT') @endif

    {{-- Basic Info --}}
    <div class="card mb-3">
        <div class="card-header"><h3>Event Details</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Event Name *</label>
                <input name="name" class="form-control" value="{{ old('name',$event->name??'') }}" required placeholder="Championship Night 2025">
            </div>
            <div class="form-row">
                <div class="form-group mb-0">
                    <label class="form-label">Subtitle</label>
                    <input name="subtitle" class="form-control" value="{{ old('subtitle',$event->subtitle??'') }}" placeholder="The Ultimate Fan Experience">
                </div>
                @if(isset($event))
                <div class="form-group mb-0">
                    <label class="form-label">Status</label>
                    <label class="form-check" style="margin-top:10px">
                        <input type="checkbox" name="is_active" {{ ($event->is_active??false) ? 'checked' : '' }}>
                        Event is live (guests can access it)
                    </label>
                </div>
                @endif
            </div>
            <div class="form-group" style="margin-top:16px">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Tell guests about this event...">{{ old('description',$event->description??'') }}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group mb-0">
                    <label class="form-label">Starts At</label>
                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', isset($event->starts_at) ? $event->starts_at->format('Y-m-d\TH:i') : '') }}">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Ends At</label>
                    <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', isset($event->ends_at) ? $event->ends_at->format('Y-m-d\TH:i') : '') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Branding --}}
    <div class="card mb-3">
        <div class="card-header"><h3>Branding & Colours</h3></div>
        <div class="card-body">
            <div class="form-row-3" style="margin-bottom:18px">
                @foreach([['primary_color','Primary (buttons, highlights)','#FF3D00'],['secondary_color','Background','#0A0A18'],['accent_color','Accent (gold)','#FFD700']] as [$field,$lbl,$def])
                <div class="form-group mb-0">
                    <label class="form-label">{{ $lbl }}</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="color" id="picker_{{ $field }}" data-target="{{ $field }}"
                               value="{{ old($field,$event->{$field}??$def) }}"
                               style="width:36px;height:36px;padding:2px;border-radius:6px;border:1px solid var(--border);background:var(--dark);cursor:pointer;flex-shrink:0">
                        <input type="text" name="{{ $field }}" id="{{ $field }}" class="form-control"
                               value="{{ old($field,$event->{$field}??$def) }}" maxlength="7" style="font-family:monospace;font-size:12px">
                    </div>
                </div>
                @endforeach
            </div>
            <div class="form-row">
                <div class="form-group mb-0">
                    <label class="form-label">Event Logo</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    @if(isset($event) && $event->logo_path)
                        <img src="{{ $event->logo_url }}" style="height:38px;margin-top:8px;border-radius:6px">
                    @endif
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Sponsor Logo</label>
                    <input type="file" name="sponsor_logo" class="form-control" accept="image/*">
                    @if(isset($event) && $event->sponsor_logo_path)
                        <img src="{{ $event->sponsor_logo_url }}" style="height:38px;margin-top:8px;border-radius:6px">
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Module Labels & Descriptions --}}
    <div class="card mb-3">
        <div class="card-header"><h3>Module Content</h3></div>
        <div class="card-body">
            @foreach([
                ['fotobomb','📷','Foto Bomb'],
                ['lottery','🎰','Lottery'],
                ['voting','🏆','Voting'],
                ['membership','⭐','Membership'],
            ] as [$key,$ico,$def])
            <div style="background:var(--dark);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
                <div style="font-weight:700;font-size:13px;margin-bottom:10px">{{ $ico }} {{ $def }}</div>
                <div class="form-row">
                    <div class="form-group mb-0">
                        <label class="form-label">Title</label>
                        <input name="{{ $key }}_title" class="form-control" value="{{ old($key.'_title',$event->{$key.'_title'}??$def) }}">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Description</label>
                        <input name="{{ $key }}_desc" class="form-control" value="{{ old($key.'_desc',$event->{$key.'_desc'}??'') }}">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Voting Candidates --}}
    <div class="card mb-3">
        <div class="card-header">
            <h3>🏆 Voting Candidates</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addCandidate()">+ Add</button>
        </div>
        <div class="card-body">
            <div id="candidates">
            @php $opts = old('candidate_names') ? array_combine(old('candidate_names',[]),old('candidate_positions',[])) : collect($event->voting_options??[])->pluck('position','name')->toArray(); @endphp
            @if(!empty($opts))
                @foreach($opts as $name => $position)
                <div class="candidate-row form-row" style="margin-bottom:10px">
                    <input name="candidate_names[]" class="form-control" value="{{ $name }}" placeholder="Athlete name">
                    <div style="display:flex;gap:6px">
                        <input name="candidate_positions[]" class="form-control" value="{{ $position }}" placeholder="Position (optional)">
                        <button type="button" onclick="this.closest('.candidate-row').remove()" class="btn btn-danger btn-sm">✕</button>
                    </div>
                </div>
                @endforeach
            @else
                <div class="candidate-row form-row" style="margin-bottom:10px">
                    <input name="candidate_names[]" class="form-control" placeholder="Athlete name">
                    <div style="display:flex;gap:6px">
                        <input name="candidate_positions[]" class="form-control" placeholder="Position (optional)">
                        <button type="button" onclick="this.closest('.candidate-row').remove()" class="btn btn-danger btn-sm">✕</button>
                    </div>
                </div>
            @endif
            </div>
            <div class="form-hint">Add up to 8 candidates. Leave blank to disable voting.</div>
        </div>
    </div>

    {{-- Vidiwall Settings --}}
    <div class="card mb-3">
        <div class="card-header"><h3>📺 Vidiwall Settings</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Overlay Text (shown on vidiwall)</label>
                <input name="vidiwall_overlay_text" class="form-control" value="{{ old('vidiwall_overlay_text',$event->vidiwall_overlay_text??'') }}" placeholder="e.g. #ChampionshipNight · Tag us @event">
            </div>
            <div class="form-row">
                <div>
                    <label class="form-check">
                        <input type="checkbox" name="vidiwall_show_uploader" {{ ($event->vidiwall_show_uploader??true) ? 'checked' : '' }}>
                        Show uploader name on vidiwall
                    </label>
                </div>
                <div>
                    <label class="form-check">
                        <input type="checkbox" name="vidiwall_slideshow_mode" id="slideshowToggle" {{ ($event->vidiwall_slideshow_mode??false) ? 'checked' : '' }} onchange="toggleSlideshow()">
                        Slideshow mode (rotate approved photos)
                    </label>
                </div>
            </div>
            <div id="slideshowInterval" style="{{ ($event->vidiwall_slideshow_mode??false) ? '' : 'display:none' }};margin-top:12px">
                <label class="form-label">Slideshow interval (seconds)</label>
                <input type="number" name="vidiwall_slideshow_interval" class="form-control" min="3" max="60" value="{{ old('vidiwall_slideshow_interval',$event->vidiwall_slideshow_interval??8) }}" style="width:120px">
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary btn-lg">{{ isset($event) ? '✓ Save Changes' : '+ Create Event' }}</button>
        <a href="{{ isset($event) ? route('admin.events.show',$event) : route('admin.events.index') }}" class="btn btn-secondary btn-lg">Cancel</a>
    </div>
</form>
</div>
@endsection

@push('scripts')
<script>
function addCandidate() {
    const row = document.createElement('div');
    row.className = 'candidate-row form-row';
    row.style.marginBottom = '10px';
    row.innerHTML = `<input name="candidate_names[]" class="form-control" placeholder="Athlete name">
        <div style="display:flex;gap:6px">
            <input name="candidate_positions[]" class="form-control" placeholder="Position (optional)">
            <button type="button" onclick="this.closest('.candidate-row').remove()" class="btn btn-danger btn-sm">✕</button>
        </div>`;
    document.getElementById('candidates').appendChild(row);
}
function toggleSlideshow() {
    document.getElementById('slideshowInterval').style.display =
        document.getElementById('slideshowToggle').checked ? '' : 'none';
}
</script>
@endpush
