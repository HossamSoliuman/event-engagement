@extends('layouts.admin')
@section('title', isset($event) ? 'Edit ' . $event->name : 'New Event')
@section('page-title', isset($event) ? 'Edit Event' : 'New Event')

@section('content')
    <div class="max-w-xl">
        <form method="POST" action="{{ isset($event) ? route('admin.events.update', $event) : route('admin.events.store') }}"
            enctype="multipart/form-data">
            @csrf
            @if (isset($event))
                @method('PUT')
            @endif

            {{-- Basic Info --}}
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
                    <div class="form-row">
                        <div class="form-group mb-0">
                            <label class="form-label">Subtitle</label>
                            <input name="subtitle" class="form-control"
                                value="{{ old('subtitle', $event->subtitle ?? '') }}"
                                placeholder="The Ultimate Fan Experience">
                        </div>
                        @if (isset($event))
                            <div class="form-group mb-0">
                                <label class="form-label">Status</label>
                                <label class="form-check" style="margin-top:10px">
                                    <input type="checkbox" name="is_active"
                                        {{ $event->is_active ?? false ? 'checked' : '' }}>
                                    Event is live (guests can access it)
                                </label>
                            </div>
                        @endif
                    </div>
                    <div class="form-group" style="margin-top:16px">
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
                </div>
            </div>

            {{-- Branding --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3>Branding & Colours</h3>
                </div>
                <div class="card-body">
                    <div class="form-row-3" style="margin-bottom:18px">
                        @foreach ([['primary_color', 'Primary (buttons, highlights)', '#FF3D00'], ['secondary_color', 'Background', '#0A0A18'], ['accent_color', 'Accent (gold)', '#FFD700']] as [$field, $lbl, $def])
                            <div class="form-group mb-0">
                                <label class="form-label">{{ $lbl }}</label>
                                <div style="display:flex;gap:8px;align-items:center">
                                    <input type="color" id="picker_{{ $field }}" data-target="{{ $field }}"
                                        value="{{ old($field, $event->{$field} ?? $def) }}"
                                        style="width:36px;height:36px;padding:2px;border-radius:6px;border:1px solid var(--border);background:var(--dark);cursor:pointer;flex-shrink:0">
                                    <input type="text" name="{{ $field }}" id="{{ $field }}"
                                        class="form-control" value="{{ old($field, $event->{$field} ?? $def) }}"
                                        maxlength="7" style="font-family:monospace;font-size:12px">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-row">
                        <div class="form-group mb-0">
                            <label class="form-label">Event Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            @if (isset($event) && $event->logo_path)
                                <img src="{{ $event->logo_url }}" style="height:38px;margin-top:8px;border-radius:6px">
                            @endif
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Sponsor Logo</label>
                            <input type="file" name="sponsor_logo" class="form-control" accept="image/*">
                            @if (isset($event) && $event->sponsor_logo_path)
                                <img src="{{ $event->sponsor_logo_url }}"
                                    style="height:38px;margin-top:8px;border-radius:6px">
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Module Labels & Descriptions --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3>Module Content</h3>
                </div>
                <div class="card-body">
                    @foreach ([['fotobomb', '📷', 'Foto Bomb'], ['lottery', '🎰', 'Lottery'], ['voting', '🏆', 'Voting'], ['membership', '⭐', 'Membership']] as [$key, $ico, $def])
                        <div
                            style="background:var(--dark);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
                            <div style="font-weight:700;font-size:13px;margin-bottom:10px">{{ $ico }}
                                {{ $def }}</div>
                            <div class="form-row">
                                <div class="form-group mb-0">
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
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- ── PRIVACY POLICY ── --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3>🔒 Privacy Policy & Data Protection</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Consent Text (shown inside every checkbox)</label>
                        <textarea name="privacy_policy_text" class="form-control" rows="3"
                            placeholder="e.g. I agree to the processing of my personal data in accordance with the Privacy Policy of [Organisation].">{{ old('privacy_policy_text', $event->privacy_policy_text ?? '') }}</textarea>
                        <div class="form-hint">Guests must tick this before submitting any form. Write in your audience's
                            language(s).</div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Privacy Policy URL <span
                                style="color:var(--muted);font-weight:400">(guests can click "Privacy Policy" to open
                                this)</span></label>
                        <input type="url" name="privacy_policy_url" class="form-control"
                            placeholder="https://yourdomain.com/datenschutz"
                            value="{{ old('privacy_policy_url', $event->privacy_policy_url ?? '') }}">
                        <div class="form-hint">Link to your full privacy policy document or page. Required by law.</div>
                    </div>
                </div>
            </div>

            {{-- ── FONT & STYLE ── --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3>🎨 Fonts & Typography</h3>
                </div>
                <div class="card-body">
                    <div class="form-hint mb-2" style="margin-bottom:12px">Enter any Google Fonts name. Examples: Oswald ·
                        Bebas Neue · Raleway · Montserrat · Playfair Display · Inter</div>
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
                    <div
                        style="margin-top:14px;padding:12px 14px;background:var(--dark);border:1px solid var(--border);border-radius:8px">
                        <div id="fontPreviewHeading" style="font-size:20px;font-weight:800;margin-bottom:4px">Championship
                            Night 2025</div>
                        <div id="fontPreviewBody" style="font-size:14px;color:var(--muted)">Upload your photo and be part
                            of the show!</div>
                    </div>
                </div>
            </div>

            {{-- ── TILE DESIGNER ── --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3>🖼 Tile Designer</h3>
                    <div class="text-muted text-xs">Customise each of the 4 landing page tiles. Upload a graphic, set a
                        background colour, or turn a tile into an external link.</div>
                </div>
                <div class="card-body" style="padding:0">

                    @foreach ([['fotobomb', '📷', 'Foto Bomb / Selfie Wall'], ['voting', '🏆', 'Athlete of the Day / Voting'], ['lottery', '🎰', 'Lottery / Tickets'], ['membership', '⭐', 'Membership / Community']] as [$mod, $ico, $modLabel])
                        @php $tc = isset($event) ? $event->tileConfig($mod) : []; @endphp
                        <div style="border-bottom:1px solid var(--border);padding:18px 20px">
                            <div style="font-weight:700;font-size:14px;margin-bottom:14px">{{ $ico }}
                                {{ $modLabel }}</div>
                            <div class="form-row" style="margin-bottom:12px">
                                <div class="form-group mb-0">
                                    <label class="form-label">Tile Label (top small text)</label>
                                    <input type="text" name="tile_{{ $mod }}_label" class="form-control"
                                        placeholder="e.g. SELFIE WALL"
                                        value="{{ old('tile_' . $mod . '_label', $tc['label'] ?? '') }}">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Tile Sublabel</label>
                                    <input type="text" name="tile_{{ $mod }}_sublabel" class="form-control"
                                        placeholder="e.g. Presented by UNIQA"
                                        value="{{ old('tile_' . $mod . '_sublabel', $tc['sublabel'] ?? '') }}">
                                </div>
                            </div>
                            <div class="form-row" style="margin-bottom:12px">
                                <div class="form-group mb-0">
                                    <label class="form-label">Background Colour (overrides default)</label>
                                    <div style="display:flex;gap:8px;align-items:center">
                                        <input type="color" value="{{ $tc['bg_color'] ?? '#1a1a3a' }}"
                                            style="width:36px;height:36px;padding:2px;border-radius:6px;border:1px solid var(--border);background:var(--dark);cursor:pointer"
                                            oninput="document.getElementById('tile_{{ $mod }}_bg_color').value=this.value">
                                        <input type="text" name="tile_{{ $mod }}_bg_color"
                                            id="tile_{{ $mod }}_bg_color" class="form-control"
                                            style="font-family:monospace;font-size:12px"
                                            value="{{ old('tile_' . $mod . '_bg_color', $tc['bg_color'] ?? '') }}"
                                            placeholder="e.g. #003b8e or leave blank for default">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">External Link URL <span
                                            style="color:var(--muted);font-weight:400">(optional — replaces
                                            module)</span></label>
                                    <input type="url" name="tile_{{ $mod }}_link_url" class="form-control"
                                        placeholder="https://tickets.example.com"
                                        value="{{ old('tile_' . $mod . '_link_url', $tc['link_url'] ?? '') }}">
                                    <label class="form-check" style="margin-top:6px">
                                        <input type="checkbox" name="tile_{{ $mod }}_link_external"
                                            value="1" {{ $tc['link_external'] ?? false ? 'checked' : '' }}>
                                        Open in new tab
                                    </label>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Tile Background Image / Graphic</label>
                                <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap">
                                    @if (!empty($tc['image_path']))
                                        <div style="position:relative;flex-shrink:0">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($tc['image_path']) }}"
                                                style="width:90px;height:90px;object-fit:cover;border-radius:10px;border:1px solid var(--border)">
                                            <label
                                                style="position:absolute;top:-6px;right:-6px;background:var(--red);border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:11px;cursor:pointer">
                                                <input type="checkbox" name="tile_{{ $mod }}_clear_image"
                                                    value="1" style="display:none"
                                                    onchange="this.closest('label').style.opacity=this.checked?.4:1"> ✕
                                            </label>
                                        </div>
                                    @endif
                                    <div style="flex:1">
                                        <input type="file" name="tile_{{ $mod }}_image" class="form-control"
                                            accept="image/*">
                                        <div class="form-hint">Recommended: 400×400px. Will fill the tile as background.
                                            PNG with transparency works great.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

            {{-- ── EXTRA FORM FIELDS ── --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3>➕ Extra Form Fields</h3>
                    <div class="text-muted text-xs">Add custom data fields to the Lottery and Membership forms</div>
                </div>
                <div class="card-body">

                    {{-- Lottery extra fields --}}
                    <div style="margin-bottom:24px">
                        <div style="font-weight:700;font-size:13px;margin-bottom:10px">🎰 Lottery — Extra Fields</div>
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
                                        class="btn btn-danger btn-sm">✕</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm"
                            onclick="addExtraField('lotteryExtraFields','lottery')">+ Add Field</button>
                    </div>

                    {{-- Membership extra fields --}}
                    <div>
                        <div style="font-weight:700;font-size:13px;margin-bottom:10px">⭐ Membership — Extra Fields</div>
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
                                        class="btn btn-danger btn-sm">✕</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm"
                            onclick="addExtraField('membershipExtraFields','membership')">+ Add Field</button>
                    </div>
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
                                            class="btn btn-danger btn-sm">✕</button>
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
                                        class="btn btn-danger btn-sm">✕</button>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="form-hint">Add up to 8 candidates. Leave blank to disable voting.</div>
                </div>
            </div>

            {{-- Vidiwall Settings --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3>📺 Vidiwall Settings</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Overlay Text (shown on vidiwall)</label>
                        <input name="vidiwall_overlay_text" class="form-control"
                            value="{{ old('vidiwall_overlay_text', $event->vidiwall_overlay_text ?? '') }}"
                            placeholder="e.g. #ChampionshipNight · Tag us @event">
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-check">
                                <input type="checkbox" name="vidiwall_show_uploader"
                                    {{ $event->vidiwall_show_uploader ?? true ? 'checked' : '' }}>
                                Show uploader name on vidiwall
                            </label>
                        </div>
                        <div>
                            <label class="form-check">
                                <input type="checkbox" name="vidiwall_slideshow_mode" id="slideshowToggle"
                                    {{ $event->vidiwall_slideshow_mode ?? false ? 'checked' : '' }}
                                    onchange="toggleSlideshow()">
                                Slideshow mode (rotate approved photos)
                            </label>
                        </div>
                    </div>
                    <div id="slideshowInterval"
                        style="{{ $event->vidiwall_slideshow_mode ?? false ? '' : 'display:none' }};margin-top:12px">
                        <label class="form-label">Slideshow interval (seconds)</label>
                        <input type="number" name="vidiwall_slideshow_interval" class="form-control" min="3"
                            max="60"
                            value="{{ old('vidiwall_slideshow_interval', $event->vidiwall_slideshow_interval ?? 8) }}"
                            style="width:120px">
                    </div>
                </div>
            </div>
            {{-- Privacy Policy --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3>🔒 Data Protection / Privacy Policy</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
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

            {{-- Vidiwall Settings --}}

            <div style="display:flex;gap:10px">
                <button type="submit"
                    class="btn btn-primary btn-lg">{{ isset($event) ? '✓ Save Changes' : '+ Create Event' }}</button>
                <a href="{{ isset($event) ? route('admin.events.show', $event) : route('admin.events.index') }}"
                    class="btn btn-secondary btn-lg">Cancel</a>
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
        <button type="button" onclick="this.closest('.extra-field-row').remove()" class="btn btn-danger btn-sm">✕</button>`;
            document.getElementById(containerId).appendChild(row);
        }
    </script>
@endpush
