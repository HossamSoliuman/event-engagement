@extends('layouts.admin')
@section('title', isset($event) ? 'Edit ' . $event->name : 'New Event')
@section('page-title', isset($event) ? 'Edit Event' : 'New Event')

@push('styles')
    <style>
        .ev-form-wrap { max-width: 980px; }
        /* The Landing Page designer needs room for the phone preview next to the controls */
        .ev-form-wrap.is-wide { max-width: 1360px; }

        /* Tab bar */
        .ev-tabs { display:flex; gap:4px; flex-wrap:wrap; background:var(--card); border:1px solid var(--border); border-radius:12px; padding:6px; margin-bottom:20px; position:sticky; top:12px; z-index:50 }
        .ev-tab { display:flex; align-items:center; gap:8px; padding:9px 15px; font-size:13px; font-weight:600; color:var(--muted); cursor:pointer; border-radius:8px; border:none; background:none; font-family:'DM Sans',sans-serif; transition:all .15s; white-space:nowrap }
        .ev-tab:hover { color:var(--text); background:rgba(255,255,255,.04) }
        .ev-tab.active { color:#fff; background:var(--red) }
        .ev-tab .lucide-icon { width:15px; height:15px }

        /* Panels */
        .ev-panel { display:none }
        .ev-panel.active { display:block; animation:evFade .2s ease }
        @keyframes evFade { from{opacity:0; transform:translateY(4px)} to{opacity:1; transform:none} }

        /* Sticky save bar */
        .ev-actions { position:sticky; bottom:0; display:flex; gap:10px; align-items:center; padding:14px 0; margin-top:20px; background:var(--dark); border-top:1px solid var(--border); z-index:40 }
        .ev-actions .spacer { flex:1 }

        /* Sub-item grids */
        .ev-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px }
        .ev-grid-2 > .card { margin:0 }
        .ev-subcard { background:var(--dark); border:1px solid var(--border); border-radius:10px; padding:16px }
        .ev-subcard-title { font-weight:700; font-size:13px; margin-bottom:12px; display:flex; align-items:center; gap:6px }

        /* Fields: a lifted surface with a visible edge, so empty inputs read as inputs */
        .ev-form-wrap { --field-bg:#17172b; --field-edge:#2f2f50; --field-edge-hover:#41416a }
        .ev-form-wrap .form-control { background:var(--field-bg); border-color:var(--field-edge); border-radius:9px;
            padding:10px 13px; color-scheme:dark; transition:border-color .15s, box-shadow .15s }
        .ev-form-wrap .form-control:hover { border-color:var(--field-edge-hover) }
        .ev-form-wrap .form-control:focus { border-color:var(--red); box-shadow:0 0 0 3px rgba(255,61,0,.16) }
        .ev-form-wrap .form-control::placeholder { color:#55557c }
        .ev-form-wrap .form-label { color:#8d8db3 }
        .ev-form-wrap .form-hint { line-height:1.5 }

        /* Colour: swatch and hex in one field */
        .ev-color { display:flex; align-items:center; gap:10px; height:42px; padding:0 12px 0 6px; border-radius:9px;
            background:var(--field-bg); border:1px solid var(--field-edge); transition:border-color .15s, box-shadow .15s }
        .ev-color:hover { border-color:var(--field-edge-hover) }
        .ev-color:focus-within { border-color:var(--red); box-shadow:0 0 0 3px rgba(255,61,0,.16) }
        .ev-color input[type=color] { width:30px; height:30px; flex-shrink:0; padding:0; border:none; border-radius:7px;
            background:none; cursor:pointer }
        .ev-color input[type=color]::-webkit-color-swatch-wrapper { padding:0 }
        .ev-color input[type=color]::-webkit-color-swatch { border:1px solid rgba(255,255,255,.18); border-radius:7px }
        .ev-color input[type=color]::-moz-color-swatch { border:1px solid rgba(255,255,255,.18); border-radius:7px }
        .ev-color input[type=text] { flex:1; min-width:0; height:100%; padding:0; border:none; outline:none; background:none;
            color:var(--text); font-family:ui-monospace,monospace; font-size:13px; letter-spacing:.3px; text-transform:uppercase }
        .ev-color input[type=text]::placeholder { color:#55557c; font-family:'DM Sans',sans-serif; letter-spacing:0; text-transform:none }

        /* Number with its unit inside the field */
        .ev-unit { position:relative; max-width:180px }
        .ev-unit .form-control { padding-right:70px }
        .ev-unit span { position:absolute; right:13px; top:50%; transform:translateY(-50%); font-size:13px; color:var(--muted);
            pointer-events:none }

        .ev-grid-3 { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px }
        .ev-stack { display:flex; flex-direction:column; gap:18px }

        @media(max-width:768px){
            .ev-grid-2, .ev-grid-3 { grid-template-columns:1fr }
            .ev-tabs { top:0 }
        }
    </style>
@endpush

@section('content')
    <div class="ev-form-wrap">
        <form method="POST" action="{{ isset($event) ? route('admin.events.update', $event) : route('admin.events.store') }}"
            enctype="multipart/form-data">
            @csrf
            @if (isset($event))
                @method('PUT')
            @endif

            {{-- ── Tab navigation ────────────────────────────────────────────── --}}
            <div class="ev-tabs">
                <button type="button" class="ev-tab active" data-tab="details">
                    <i data-lucide="file-text" class="lucide-icon"></i> Details
                </button>
                <button type="button" class="ev-tab" data-tab="branding">
                    <i data-lucide="palette" class="lucide-icon"></i> Branding
                </button>
                <button type="button" class="ev-tab" data-tab="landing">
                    <i data-lucide="smartphone" class="lucide-icon"></i> Landing Page
                </button>
                <button type="button" class="ev-tab" data-tab="vidiwall">
                    <i data-lucide="monitor-play" class="lucide-icon"></i> Vidiwall
                </button>
                <button type="button" class="ev-tab" data-tab="modules">
                    <i data-lucide="layout-grid" class="lucide-icon"></i> Modules &amp; Forms
                </button>
            </div>

            {{-- ── Panel: Details ────────────────────────────────────────────── --}}
            <div class="ev-panel active" data-panel="details">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3>Event Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Event Name *</label>
                            <input name="name" class="form-control" value="{{ old('name', $event->name ?? '') }}" required
                                placeholder="Championship Night 2025">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subtitle</label>
                            <input name="subtitle" class="form-control"
                                value="{{ old('subtitle', $event->subtitle ?? '') }}"
                                placeholder="The Ultimate Fan Experience">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Tell guests about this event...">{{ old('description', $event->description ?? '') }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group mb-0">
                                <label class="form-label">Starts At</label>
                                <input type="datetime-local" name="starts_at" class="form-control"
                                    value="{{ old('starts_at', isset($event->starts_at) ? $event->starts_at->format('Y-m-d\TH:i') : '') }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Ends At</label>
                                <input type="datetime-local" name="ends_at" class="form-control"
                                    value="{{ old('ends_at', isset($event->ends_at) ? $event->ends_at->format('Y-m-d\TH:i') : '') }}">
                            </div>
                        </div>
                        @if (isset($event))
                            <div class="form-group mb-0" style="margin-top:16px">
                                <label class="form-label">Status</label>
                                <label class="form-check" style="gap:10px">
                                    <span class="toggle">
                                        <input type="checkbox" name="is_active"
                                            {{ $event->is_active ?? false ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </span>
                                    Event is live (guests can access it)
                                </label>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i data-lucide="lock" class="lucide-icon"></i> Data Protection / Privacy Policy</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label class="form-label">Consent Text shown to guests *</label>
                            <textarea name="privacy_policy_text" class="form-control" rows="4"
                                placeholder="e.g. I agree to the processing of my personal data in accordance with the Privacy Policy of [Organisation Name]. My data will be used solely for event participation and will not be shared with third parties.">{{ old('privacy_policy_text', $event->privacy_policy_text ?? '') }}</textarea>
                            <div class="form-hint">
                                Guests must tick and accept this exact text before submitting any form (photo upload, lottery,
                                voting, membership). Write it in the language(s) of your audience. Leave blank to use the
                                default fallback text.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Panel: Branding ───────────────────────────────────────────── --}}
            <div class="ev-panel" data-panel="branding">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i data-lucide="palette" class="lucide-icon"></i> Branding &amp; Colours</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row-3" style="margin-bottom:18px">
                            @foreach ([['primary_color', 'Primary (buttons, highlights)', '#FF3D00'], ['secondary_color', 'Background', '#0A0A18'], ['accent_color', 'Accent (gold)', '#FFD700']] as [$field, $lbl, $def])
                                <div class="form-group mb-0">
                                    <label class="form-label" for="{{ $field }}">{{ $lbl }}</label>
                                    <div class="ev-color">
                                        <input type="color" id="picker_{{ $field }}" data-target="{{ $field }}"
                                            value="{{ old($field, $event->{$field} ?? $def) }}" aria-label="{{ $lbl }}">
                                        <input type="text" name="{{ $field }}" id="{{ $field }}"
                                            value="{{ old($field, $event->{$field} ?? $def) }}" maxlength="7">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-row">
                            <div class="form-group mb-0">
                                <label class="form-label">Event Logo</label>
                                @include('admin.events._image-upload', [
                                    'name' => 'logo',
                                    'current' => isset($event) && $event->logo_path ? $event->logo_url : null,
                                    'hint' => 'PNG, JPG, WebP or SVG',
                                    'resize' => '1200x1200',
                                    'maxKb' => 2048,
                                ])
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Sponsor Logo</label>
                                @include('admin.events._image-upload', [
                                    'name' => 'sponsor_logo',
                                    'current' => isset($event) && $event->sponsor_logo_path ? $event->sponsor_logo_url : null,
                                    'hint' => 'PNG, JPG, WebP or SVG',
                                    'resize' => '1200x1200',
                                    'maxKb' => 2048,
                                ])
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i data-lucide="type" class="lucide-icon"></i> Fonts &amp; Typography</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group mb-0">
                                <label class="form-label">Heading Font</label>
                                <input type="text" name="font_heading" class="form-control" placeholder="Syne"
                                    value="{{ old('font_heading', $event->font_heading ?? 'Syne') }}" id="fontHeadingInput">
                                <div class="form-hint">Used for event name, tile names, section titles</div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Body Font</label>
                                <input type="text" name="font_body" class="form-control" placeholder="DM Sans"
                                    value="{{ old('font_body', $event->font_body ?? 'DM Sans') }}" id="fontBodyInput">
                                <div class="form-hint">Used for descriptions, form labels, body text</div>
                            </div>
                        </div>
                        <div class="form-hint">Any Google Fonts name works, e.g. Oswald, Bebas Neue, Raleway, Montserrat,
                            Playfair Display or Inter.</div>
                        <div
                            style="margin-top:14px;padding:12px 14px;background:var(--dark);border:1px solid var(--border);border-radius:8px">
                            <div id="fontPreviewHeading" style="font-size:20px;font-weight:800;margin-bottom:4px">Championship
                                Night 2025</div>
                            <div id="fontPreviewBody" style="font-size:14px;color:var(--muted)">Upload your photo and be part
                                of the show!</div>
                        </div>
                    </div>
                </div>
            </div>

            @include('admin.events._landing-design')

            {{-- ── Panel: Vidiwall ───────────────────────────────────────────── --}}
            <div class="ev-panel" data-panel="vidiwall">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i data-lucide="timer" class="lucide-icon"></i> Playback</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label class="form-label" for="vidiwall_photo_seconds">Photo duration</label>
                            <div class="ev-unit">
                                <input type="number" name="vidiwall_photo_seconds" id="vidiwall_photo_seconds"
                                    class="form-control" min="1" max="60" step="1" required
                                    value="{{ old('vidiwall_photo_seconds', $event->vidiwall_photo_seconds ?? \App\Models\FotoUpload::SCREEN_SECONDS) }}">
                                <span>seconds</span>
                            </div>
                            <div class="form-hint">How long each photo stays on the wall. Videos always play to the end.</div>
                        </div>
                    </div>
                </div>

                @php $fc = isset($event) ? $event->frameConfig() : (new \App\Models\Event)->frameConfig(); @endphp
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i data-lucide="frame" class="lucide-icon"></i> Sponsor Frame</h3>
                    </div>
                    <div class="card-body ev-stack">
                        <div class="ev-grid-2">
                            <div>
                                <label class="form-label" for="frame_color">Frame colour</label>
                                <div class="ev-color">
                                    <input type="color" data-target="frame_color" aria-label="Frame colour"
                                        data-fallback="{{ $event->primary_color ?? '#FF3D00' }}"
                                        value="{{ old('frame_color', $fc['frame_color'] ?: ($event->primary_color ?? '#FF3D00')) }}">
                                    <input type="text" name="frame_color" id="frame_color" maxlength="7"
                                        value="{{ old('frame_color', $fc['frame_color'] ?? '') }}"
                                        placeholder="Event primary colour">
                                </div>
                            </div>
                            <div>
                                <label class="form-label" for="frame_text_color">Text colour</label>
                                <div class="ev-color">
                                    <input type="color" data-target="frame_text_color" aria-label="Text colour"
                                        data-fallback="#ffffff"
                                        value="{{ old('frame_text_color', $fc['text_color'] ?: '#ffffff') }}">
                                    <input type="text" name="frame_text_color" id="frame_text_color" maxlength="7"
                                        value="{{ old('frame_text_color', $fc['text_color'] ?? '#ffffff') }}"
                                        placeholder="White">
                                </div>
                            </div>
                        </div>
                        <div class="ev-grid-3">
                            <div>
                                <label class="form-label" for="frame_top_text">Top bar</label>
                                <input type="text" name="frame_top_text" id="frame_top_text" class="form-control"
                                    value="{{ old('frame_top_text', $fc['top_text'] ?? '') }}" placeholder="Optional">
                            </div>
                            <div>
                                <label class="form-label" for="frame_bottom_text">Bottom bar</label>
                                <input type="text" name="frame_bottom_text" id="frame_bottom_text" class="form-control"
                                    value="{{ old('frame_bottom_text', $fc['bottom_text'] ?? '') }}"
                                    placeholder="Same as top bar">
                            </div>
                            <div>
                                <label class="form-label" for="frame_side_text">Side rail</label>
                                <input type="text" name="frame_side_text" id="frame_side_text" class="form-control"
                                    value="{{ old('frame_side_text', $fc['side_text'] ?? '') }}" placeholder="Optional">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Logo in the bars</label>
                            @include('admin.events._image-upload', [
                                'name' => 'frame_logo',
                                'current' => !empty($fc['logo_path'])
                                    ? \Illuminate\Support\Facades\Storage::disk('media')->url($fc['logo_path'])
                                    : null,
                                'clear' => 'frame_clear_logo',
                                'hint' => 'PNG, JPG, WebP or SVG',
                                'resize' => '1200x1200',
                                'maxKb' => 2048,
                            ])
                        </div>
                    </div>
                </div>

                <div class="ev-grid-2 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h3><i data-lucide="image" class="lucide-icon"></i> Wall Background</h3>
                        </div>
                        <div class="card-body">
                            @include('admin.events._image-upload', [
                                'name' => 'vidiwall_background',
                                'current' => isset($event) && $event->hasVidiwallBackground() ? $event->vidiwall_background_url : null,
                                'clear' => 'clear_vidiwall_background',
                                'accept' => 'image/jpeg,image/png,image/webp',
                                'hint' => '1920×1080 · JPG, PNG or WebP',
                                'resize' => '1920x1080',
                                'mode' => 'cover',
                                'ratio' => '16 / 9',
                                'thumb' => 144,
                                'cropNote' => 'Not 16:9, so the wall crops the edges to fill the screen.',
                            ])
                            <div class="form-hint">When set, it replaces the sponsor frame on the wall.</div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i data-lucide="qr-code" class="lucide-icon"></i> QR Screen Background</h3>
                        </div>
                        <div class="card-body">
                            @include('admin.events._image-upload', [
                                'name' => 'vidiwall_qr_background',
                                'current' => $event->vidiwall_qr_background_url ?? null,
                                'clear' => 'clear_vidiwall_qr_background',
                                'accept' => 'image/jpeg,image/png,image/webp',
                                'hint' => '1920×1080 · JPG, PNG or WebP',
                                'resize' => '1920x1080',
                                'mode' => 'cover',
                                'ratio' => '16 / 9',
                                'thumb' => 144,
                                'cropNote' => 'Not 16:9, so the wall crops the edges to fill the screen.',
                            ])
                            <div class="form-hint">Shown behind the QR code when the operator presses QR. Without it, the background colour is used.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Panel: Modules & Forms ────────────────────────────────────── --}}
            <div class="ev-panel" data-panel="modules">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i data-lucide="layout-grid" class="lucide-icon"></i> Module Content</h3>
                    </div>
                    <div class="card-body">
                        <div class="ev-grid-2">
                            @foreach ([['fotobomb', '<i data-lucide="camera" class="lucide-icon"></i>', 'Foto Bomb'], ['lottery', '<i data-lucide="ticket" class="lucide-icon"></i>', 'Lottery'], ['voting', '<i data-lucide="trophy" class="lucide-icon"></i>', 'Voting'], ['membership', '<i data-lucide="star" class="lucide-icon"></i>', 'Membership'], ['quiz', '<i data-lucide="help-circle" class="lucide-icon"></i>', 'Quiz to Win'], ['fanclash', '<i data-lucide="swords" class="lucide-icon"></i>', 'Fan Clash'], ['survey', '<i data-lucide="clipboard-list" class="lucide-icon"></i>', 'Fan Survey']] as [$key, $ico, $def])
                                <div class="ev-subcard">
                                    <div class="ev-subcard-title">{!! $ico !!} {{ $def }}</div>
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input name="{{ $key }}_title" class="form-control"
                                            value="{{ old($key . '_title', $event->{$key . '_title'} ?? $def) }}">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Description</label>
                                        <input name="{{ $key }}_desc" class="form-control"
                                            value="{{ old($key . '_desc', $event->{$key . '_desc'} ?? '') }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i data-lucide="trophy" class="lucide-icon"></i> Voting Candidates</h3>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addCandidate()">+ Add</button>
                    </div>
                    <div class="card-body">
                        <div id="candidates">
                            @php
                                $opts = old('candidate_names')
                                    ? array_combine(old('candidate_names', []), old('candidate_positions', []))
                                    : collect($event->voting_options ?? [])
                                        ->pluck('position', 'name')
                                        ->toArray();
                            @endphp
                            @if (!empty($opts))
                                @foreach ($opts as $name => $position)
                                    <div class="candidate-row form-row" style="margin-bottom:10px">
                                        <input name="candidate_names[]" class="form-control" value="{{ $name }}"
                                            placeholder="Athlete name">
                                        <div style="display:flex;gap:6px">
                                            <input name="candidate_positions[]" class="form-control"
                                                value="{{ $position }}" placeholder="Position (optional)">
                                            <button type="button" onclick="this.closest('.candidate-row').remove()"
                                                class="btn btn-danger btn-sm"><i data-lucide="x" class="lucide-icon"></i></button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="candidate-row form-row" style="margin-bottom:10px">
                                    <input name="candidate_names[]" class="form-control" placeholder="Athlete name">
                                    <div style="display:flex;gap:6px">
                                        <input name="candidate_positions[]" class="form-control"
                                            placeholder="Position (optional)">
                                        <button type="button" onclick="this.closest('.candidate-row').remove()"
                                            class="btn btn-danger btn-sm"><i data-lucide="x" class="lucide-icon"></i></button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="form-hint">Add up to 8 candidates. Leave blank to disable voting.</div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i data-lucide="plus" class="lucide-icon"></i> Extra Form Fields</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom:24px">
                            <div style="font-weight:700;font-size:13px;margin-bottom:10px"><i data-lucide="ticket" class="lucide-icon"></i> Lottery — Extra Fields</div>
                            <div id="lotteryExtraFields">
                                @foreach (old('lottery_field_label', array_column($event->lottery_extra_fields ?? [], 'label')) as $i => $lbl)
                                    @php
                                        $types = old(
                                            'lottery_field_type',
                                            array_column($event->lottery_extra_fields ?? [], 'type'),
                                        );
                                        $reqs = old(
                                            'lottery_field_required',
                                            array_column($event->lottery_extra_fields ?? [], 'required'),
                                        );
                                    @endphp
                                    <div class="extra-field-row"
                                        style="display:grid;grid-template-columns:1fr 120px auto auto;gap:8px;margin-bottom:8px;align-items:center">
                                        <input type="text" name="lottery_field_label[]" class="form-control"
                                            value="{{ $lbl }}" placeholder="Field label e.g. Date of Birth">
                                        <select name="lottery_field_type[]" class="form-control">
                                            @foreach (['text', 'number', 'email', 'tel', 'date', 'select'] as $t)
                                                <option value="{{ $t }}"
                                                    {{ ($types[$i] ?? 'text') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label class="form-check" style="white-space:nowrap"><input type="checkbox"
                                                name="lottery_field_required[]" value="1"
                                                {{ $reqs[$i] ?? false ? 'checked' : '' }}> Required</label>
                                        <button type="button" onclick="this.closest('.extra-field-row').remove()"
                                            class="btn btn-danger btn-sm"><i data-lucide="x" class="lucide-icon"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick="addExtraField('lotteryExtraFields','lottery')">+ Add Field</button>
                        </div>

                        <div>
                            <div style="font-weight:700;font-size:13px;margin-bottom:10px"><i data-lucide="star" class="lucide-icon"></i> Membership — Extra Fields</div>
                            <div id="membershipExtraFields">
                                @foreach (old('membership_field_label', array_column($event->membership_extra_fields ?? [], 'label')) as $i => $lbl)
                                    @php
                                        $mtypes = old(
                                            'membership_field_type',
                                            array_column($event->membership_extra_fields ?? [], 'type'),
                                        );
                                        $mreqs = old(
                                            'membership_field_required',
                                            array_column($event->membership_extra_fields ?? [], 'required'),
                                        );
                                    @endphp
                                    <div class="extra-field-row"
                                        style="display:grid;grid-template-columns:1fr 120px auto auto;gap:8px;margin-bottom:8px;align-items:center">
                                        <input type="text" name="membership_field_label[]" class="form-control"
                                            value="{{ $lbl }}" placeholder="Field label e.g. Club Name">
                                        <select name="membership_field_type[]" class="form-control">
                                            @foreach (['text', 'number', 'email', 'tel', 'date', 'select'] as $t)
                                                <option value="{{ $t }}"
                                                    {{ ($mtypes[$i] ?? 'text') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label class="form-check" style="white-space:nowrap"><input type="checkbox"
                                                name="membership_field_required[]" value="1"
                                                {{ $mreqs[$i] ?? false ? 'checked' : '' }}> Required</label>
                                        <button type="button" onclick="this.closest('.extra-field-row').remove()"
                                            class="btn btn-danger btn-sm"><i data-lucide="x" class="lucide-icon"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick="addExtraField('membershipExtraFields','membership')">+ Add Field</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Sticky save bar ───────────────────────────────────────────── --}}
            <div class="ev-actions">
                <button type="submit"
                    class="btn btn-primary btn-lg">{!! isset($event) ? '<i data-lucide="check" class="lucide-icon"></i> Save Changes' : '+ Create Event' !!}</button>
                <a href="{{ isset($event) ? route('admin.events.show', $event) : route('admin.events.index') }}"
                    class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // Tab switching
        (function () {
            const tabs = document.querySelectorAll('.ev-tab');
            const panels = document.querySelectorAll('.ev-panel');

            function activate(name) {
                tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === name));
                panels.forEach(p => p.classList.toggle('active', p.dataset.panel === name));
                document.querySelector('.ev-form-wrap')?.classList.toggle('is-wide', name === 'landing');
            }

            tabs.forEach(tab => tab.addEventListener('click', () => {
                activate(tab.dataset.tab);
                history.replaceState(null, '', '#' + tab.dataset.tab);
            }));

            const hash = location.hash.replace('#', '');
            if (hash && document.querySelector(`.ev-tab[data-tab="${hash}"]`)) {
                activate(hash);
            }

            // If a hidden field fails native validation, reveal its panel so the
            // browser can focus and highlight it.
            const form = document.querySelector('form');
            form?.addEventListener('invalid', e => {
                const panel = e.target.closest('.ev-panel');
                if (panel && !panel.classList.contains('active')) {
                    activate(panel.dataset.panel);
                }
            }, true);
        })();

        function addCandidate() {
            const row = document.createElement('div');
            row.className = 'candidate-row form-row';
            row.style.marginBottom = '10px';
            row.innerHTML = `<input name="candidate_names[]" class="form-control" placeholder="Athlete name">
        <div style="display:flex;gap:6px">
            <input name="candidate_positions[]" class="form-control" placeholder="Position (optional)">
            <button type="button" onclick="this.closest('.candidate-row').remove()" class="btn btn-danger btn-sm"><i data-lucide="x" class="lucide-icon"></i></button>
        </div>`;
            document.getElementById('candidates').appendChild(row);
            window.lucide?.createIcons();
        }

        // An emptied colour falls back to its default, so the swatch shows what the wall will use.
        document.querySelectorAll('.ev-color input[type=color][data-fallback]').forEach(picker => {
            document.getElementById(picker.dataset.target)?.addEventListener('input', e => {
                if (e.target.value.trim() === '') picker.value = picker.dataset.fallback;
            });
        });

        // Font live preview
        function updateFontPreview() {
            const h = document.getElementById('fontHeadingInput')?.value || 'Syne';
            const b = document.getElementById('fontBodyInput')?.value || 'DM Sans';
            const url =
                `https://fonts.googleapis.com/css2?family=${encodeURIComponent(h)}:wght@700;800&family=${encodeURIComponent(b)}:wght@400;500&display=swap`;
            let link = document.getElementById('fontPreviewLink');
            if (!link) {
                link = document.createElement('link');
                link.rel = 'stylesheet';
                link.id = 'fontPreviewLink';
                document.head.appendChild(link);
            }
            link.href = url;
            document.getElementById('fontPreviewHeading').style.fontFamily = `'${h}', sans-serif`;
            document.getElementById('fontPreviewBody').style.fontFamily = `'${b}', sans-serif`;
        }
        document.getElementById('fontHeadingInput')?.addEventListener('input', updateFontPreview);
        document.getElementById('fontBodyInput')?.addEventListener('input', updateFontPreview);
        updateFontPreview();

        // Extra field rows
        function addExtraField(containerId, prefix) {
            const row = document.createElement('div');
            row.className = 'extra-field-row';
            row.style.cssText =
                'display:grid;grid-template-columns:1fr 120px auto auto;gap:8px;margin-bottom:8px;align-items:center';
            row.innerHTML =
                `
        <input type="text" name="${prefix}_field_label[]" class="form-control" placeholder="Field label">
        <select name="${prefix}_field_type[]" class="form-control">
            <option value="text">Text</option>
            <option value="number">Number</option>
            <option value="email">Email</option>
            <option value="tel">Tel</option>
            <option value="date">Date</option>
            <option value="select">Select</option>
        </select>
        <label class="form-check" style="white-space:nowrap"><input type="checkbox" name="${prefix}_field_required[]" value="1"> Required</label>
        <button type="button" onclick="this.closest('.extra-field-row').remove()" class="btn btn-danger btn-sm"><i data-lucide="x" class="lucide-icon"></i></button>`;
            document.getElementById(containerId).appendChild(row);
            window.lucide?.createIcons();
        }
    </script>
@endpush
