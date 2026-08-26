{{-- ── Panel: Landing Page (full designer + live iPhone preview) ────────────── --}}
@php
    $ld = isset($event) ? $event->landingDesign() : \App\Models\Event::landingDesignDefaults();
    $ldModules = [
        'fotobomb' => ['Foto Bomb / Selfie Wall', 'camera'],
        'voting' => ['Athlete of the Day / Voting', 'trophy'],
        'lottery' => ['Lottery / Win', 'ticket'],
        'membership' => ['Membership / Community', 'crown'],
        'quiz' => ['Quiz to Win', 'brain'],
        'fanclash' => ['Fan Clash', 'swords'],
    ];
@endphp

<div class="ev-panel" data-panel="landing" id="ldPanel">
    <div class="ld-layout">

        {{-- ══ Controls ══════════════════════════════════════════════════════ --}}
        <div class="ld-controls">

            <div class="ld-intro">
                <i data-lucide="wand-2" class="lucide-icon"></i>
                <div>
                    <strong>Design your landing page.</strong>
                    Drag a slider and the phone on the right updates instantly. Nothing is saved until you press
                    <em>Save Changes</em>.
                </div>
            </div>

            {{-- ── 1. Logo & header ─────────────────────────────────────────── --}}
            <details class="ld-sec" open>
                <summary><span class="ld-num">1</span> Logo &amp; Header <i data-lucide="chevron-down"
                        class="lucide-icon ld-chev"></i></summary>
                <div class="ld-sec-body">
                    <div class="ld-note">Upload the logo itself on the <strong>Branding</strong> tab.</div>

                    @include('admin.events._design-range', [
                        'name' => 'design[logo_size]',
                        'label' => 'Logo size',
                        'value' => $ld['logo_size'],
                        'min' => 16,
                        'max' => 160,
                        'suffix' => 'px',
                        'var' => '--cl-logo-h',
                        'unit' => 'px',
                    ])

                    <div class="ld-ctl">
                        <label class="ld-ctl-label" for="design_logo_position"><span>Logo &amp; label
                                arrangement</span></label>
                        <select class="form-control" id="design_logo_position" name="design[logo_position]"
                            data-dcls=".cl-header|is-stacked|stacked">
                            <option value="inline" @selected(($ld['logo_position'] ?? 'inline') === 'inline')>Side by side</option>
                            <option value="stacked" @selected(($ld['logo_position'] ?? 'inline') === 'stacked')>Logo on top, label below</option>
                        </select>
                    </div>

                    <label class="ld-switch">
                        <input type="checkbox" name="design[wordmark_show]" value="1" @checked($ld['wordmark_show'])
                            data-dshow=".cl-wordmark|.cl-divider">
                        <span>Show the text label next to the logo</span>
                    </label>

                    <div class="ld-ctl">
                        <label class="ld-ctl-label" for="landing_wordmark"><span>Text label</span></label>
                        <input name="landing_wordmark" id="landing_wordmark" class="form-control"
                            value="{{ old('landing_wordmark', $event->landing_wordmark ?? '') }}"
                            placeholder="FAN EXPERIENCE" data-dtext=".cl-wordmark" data-dfallback="FAN EXPERIENCE">
                    </div>

                    @include('admin.events._design-range', [
                        'name' => 'design[wordmark_size]',
                        'label' => 'Text label size',
                        'value' => $ld['wordmark_size'],
                        'min' => 9,
                        'max' => 40,
                        'suffix' => 'px',
                        'var' => '--cl-wordmark-size',
                        'unit' => 'px',
                    ])

                    @include('admin.events._design-range', [
                        'name' => 'design[wordmark_spacing]',
                        'label' => 'Letter spacing',
                        'value' => $ld['wordmark_spacing'],
                        'min' => 0,
                        'max' => 60,
                        'var' => '--cl-wordmark-spacing',
                        'unit' => 'em',
                        'scale' => 0.01,
                    ])
                </div>
            </details>

            {{-- ── 2. Headline ──────────────────────────────────────────────── --}}
            <details class="ld-sec">
                <summary><span class="ld-num">2</span> Headline <i data-lucide="chevron-down"
                        class="lucide-icon ld-chev"></i></summary>
                <div class="ld-sec-body">
                    <label class="ld-switch">
                        <input type="checkbox" name="design[hero_show]" value="1" @checked($ld['hero_show'])
                            data-dshow=".cl-hero">
                        <span>Show the headline block</span>
                    </label>

                    <div class="ld-ctl">
                        <label class="ld-ctl-label" for="landing_hero_title"><span>Headline</span></label>
                        <input name="landing_hero_title" id="landing_hero_title" class="form-control"
                            value="{{ old('landing_hero_title', $event->landing_hero_title ?? '') }}"
                            placeholder="Your **Fan Experience** starts here." data-dhtml=".cl-hero-title"
                            data-dfallback="Your **Fan Experience** starts here.">
                        <div class="ld-hint">Put <code>**stars**</code> around a phrase to make it bold.</div>
                    </div>

                    @include('admin.events._design-range', [
                        'name' => 'design[hero_title_size]',
                        'label' => 'Headline size',
                        'value' => $ld['hero_title_size'],
                        'min' => 11,
                        'max' => 40,
                        'suffix' => 'px',
                        'var' => '--cl-hero-size',
                        'unit' => 'px',
                    ])

                    <div class="ld-ctl">
                        <label class="ld-ctl-label" for="landing_hero_sub"><span>Sub-headline</span></label>
                        <input name="landing_hero_sub" id="landing_hero_sub" class="form-control"
                            value="{{ old('landing_hero_sub', $event->landing_hero_sub ?? '') }}"
                            placeholder="Be part of the show. **Tap a tile** to get started."
                            data-dhtml=".cl-hero-sub"
                            data-dfallback="Be part of the show. **Tap a tile** to get started.">
                    </div>

                    @include('admin.events._design-range', [
                        'name' => 'design[hero_sub_size]',
                        'label' => 'Sub-headline size',
                        'value' => $ld['hero_sub_size'],
                        'min' => 9,
                        'max' => 28,
                        'suffix' => 'px',
                        'var' => '--cl-hero-sub-size',
                        'unit' => 'px',
                    ])
                </div>
            </details>

            {{-- ── 3. Button grid ───────────────────────────────────────────── --}}
            <details class="ld-sec">
                <summary><span class="ld-num">3</span> Button Grid <span class="ld-tag">applies to all 6</span> <i
                        data-lucide="chevron-down" class="lucide-icon ld-chev"></i></summary>
                <div class="ld-sec-body">
                    @include('admin.events._design-range', [
                        'name' => 'design[card_columns]',
                        'label' => 'Buttons per row',
                        'value' => $ld['card_columns'],
                        'min' => 1,
                        'max' => 3,
                        'var' => '--cl-cols',
                    ])
                    @include('admin.events._design-range', [
                        'name' => 'design[card_ratio]',
                        'label' => 'Button shape',
                        'value' => $ld['card_ratio'],
                        'min' => 0.7,
                        'max' => 2.4,
                        'step' => 0.02,
                        'var' => '--cl-ratio',
                        'hint' => 'Low = tall buttons · High = wide, flat buttons.',
                    ])
                    @include('admin.events._design-range', [
                        'name' => 'design[card_gap]',
                        'label' => 'Space between buttons',
                        'value' => $ld['card_gap'],
                        'min' => 0,
                        'max' => 40,
                        'suffix' => 'px',
                        'var' => '--cl-gap',
                        'unit' => 'px',
                    ])
                    @include('admin.events._design-range', [
                        'name' => 'design[card_radius]',
                        'label' => 'Corner rounding',
                        'value' => $ld['card_radius'],
                        'min' => 0,
                        'max' => 40,
                        'suffix' => 'px',
                        'var' => '--cl-radius',
                        'unit' => 'px',
                    ])
                    @include('admin.events._design-range', [
                        'name' => 'design[card_shadow]',
                        'label' => 'Shadow strength',
                        'value' => $ld['card_shadow'],
                        'min' => 0,
                        'max' => 60,
                        'suffix' => 'px',
                        'var' => '--cl-shadow',
                        'unit' => 'px',
                    ])
                    @include('admin.events._design-range', [
                        'name' => 'design[page_padding]',
                        'label' => 'Page side margin',
                        'value' => $ld['page_padding'],
                        'min' => 0,
                        'max' => 48,
                        'suffix' => 'px',
                        'var' => '--cl-pad',
                        'unit' => 'px',
                    ])
                    @include('admin.events._design-range', [
                        'name' => 'design[section_spacing]',
                        'label' => 'Space between sections',
                        'value' => $ld['section_spacing'],
                        'min' => 4,
                        'max' => 60,
                        'suffix' => 'px',
                        'var' => '--cl-sp',
                        'unit' => 'px',
                    ])
                </div>
            </details>

            {{-- ── 4-9. The six buttons ─────────────────────────────────────── --}}
            @foreach ($ldModules as $mod => [$modLabel, $modIcon])
                @php
                    $tc = isset($event) ? $event->tileConfig($mod) : \App\Models\Event::tileConfigDefaults();
                    $isOn = isset($event) ? (bool) $event->{'module_' . $mod} : true;
                    $sel = '.cl-card[data-mod=\'' . $mod . '\']';
                @endphp
                <details class="ld-sec">
                    <summary>
                        <span class="ld-num">{{ $loop->iteration + 3 }}</span>
                        <i data-lucide="{{ $modIcon }}" class="lucide-icon"></i> {{ $modLabel }}
                        @unless ($isOn)
                            <span class="ld-tag ld-tag-off">hidden</span>
                        @endunless
                        <i data-lucide="chevron-down" class="lucide-icon ld-chev"></i>
                    </summary>
                    <div class="ld-sec-body">
                        @unless ($isOn)
                            <div class="ld-note ld-note-warn">This module is switched off, so the button is not shown on
                                the landing page. Turn it on from the event dashboard.</div>
                        @endunless

                        <div class="ld-ctl">
                            <label class="ld-ctl-label"><span>Logo / image</span></label>
                            <div class="ld-img-row">
                                @if (!empty($tc['image_path']))
                                    <div class="ld-thumb">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($tc['image_path']) }}"
                                            alt="">
                                        <label class="ld-thumb-x" title="Remove image">
                                            <input type="checkbox" name="tile_{{ $mod }}_clear_image" value="1"
                                                data-dclear="{{ $mod }}">
                                            <i data-lucide="x" class="lucide-icon"></i>
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="tile_{{ $mod }}_image" class="form-control"
                                    accept="image/*" data-dfile="{{ $mod }}">
                            </div>
                            <div class="ld-hint">PNG with a transparent background looks best. Around 600×600px.</div>
                        </div>

                        @include('admin.events._design-range', [
                            'name' => 'tile_' . $mod . '_logo_size',
                            'label' => 'Logo size',
                            'value' => $tc['logo_size'],
                            'min' => 20,
                            'max' => 200,
                            'suffix' => '%',
                            'var' => '--cl-logo-scale',
                            'unit' => '%',
                            'scope' => $mod,
                            'hint' => '100% ≈ half the button height. Push higher and the logo takes room from the text.',
                        ])

                        <div class="ld-ctl">
                            <label class="ld-ctl-label"
                                for="tile_{{ $mod }}_logo_fit"><span>Image behaviour</span></label>
                            <select class="form-control" id="tile_{{ $mod }}_logo_fit"
                                name="tile_{{ $mod }}_logo_fit" data-dvar="--cl-fit" data-dscope="{{ $mod }}">
                                <option value="contain" @selected(($tc['logo_fit'] ?? 'contain') === 'contain')>Show the whole logo</option>
                                <option value="cover" @selected(($tc['logo_fit'] ?? 'contain') === 'cover')>Fill the button (crop)</option>
                            </select>
                        </div>

                        @include('admin.events._design-range', [
                            'name' => 'tile_' . $mod . '_media_padding',
                            'label' => 'Inner padding',
                            'value' => $tc['media_padding'],
                            'min' => 0,
                            'max' => 40,
                            'suffix' => 'px',
                            'var' => '--cl-media-pad',
                            'unit' => 'px',
                            'scope' => $mod,
                        ])

                        <div class="ld-ctl">
                            <label class="ld-ctl-label" for="tile_{{ $mod }}_label"><span>Main text</span></label>
                            <input type="text" name="tile_{{ $mod }}_label" id="tile_{{ $mod }}_label"
                                class="form-control" placeholder="e.g. SELFIE CAM"
                                value="{{ old('tile_' . $mod . '_label', $tc['label'] ?? '') }}"
                                data-dtext="{{ $sel }} .cl-card-label"
                                data-dfallback="{{ $event->{$mod . '_title'} ?? ucfirst($mod) }}">
                        </div>

                        @include('admin.events._design-range', [
                            'name' => 'tile_' . $mod . '_label_size',
                            'label' => 'Main text size',
                            'value' => $tc['label_size'],
                            'min' => 7,
                            'max' => 22,
                            'step' => 0.5,
                            'suffix' => 'px',
                            'var' => '--cl-label-size',
                            'unit' => 'px',
                            'scope' => $mod,
                        ])

                        <div class="ld-ctl">
                            <label class="ld-ctl-label" for="tile_{{ $mod }}_sublabel"><span>Small text
                                    underneath</span></label>
                            <input type="text" name="tile_{{ $mod }}_sublabel"
                                id="tile_{{ $mod }}_sublabel" class="form-control"
                                placeholder="e.g. presented by UNIQA"
                                value="{{ old('tile_' . $mod . '_sublabel', $tc['sublabel'] ?? '') }}"
                                data-dtext="{{ $sel }} .cl-card-sub" data-dhide-empty="1">
                        </div>

                        @include('admin.events._design-range', [
                            'name' => 'tile_' . $mod . '_sublabel_size',
                            'label' => 'Small text size',
                            'value' => $tc['sublabel_size'],
                            'min' => 6,
                            'max' => 18,
                            'step' => 0.5,
                            'suffix' => 'px',
                            'var' => '--cl-sub-size',
                            'unit' => 'px',
                            'scope' => $mod,
                        ])

                        <div class="ld-colors">
                            <div class="ld-ctl">
                                <label class="ld-ctl-label"
                                    for="tile_{{ $mod }}_bg_color"><span>Background colour</span></label>
                                <div class="ld-color-row">
                                    <input type="color" value="{{ $tc['bg_color'] ?: '#ffffff' }}"
                                        data-dsync="tile_{{ $mod }}_bg_color">
                                    <input type="text" name="tile_{{ $mod }}_bg_color"
                                        id="tile_{{ $mod }}_bg_color" class="form-control ld-hex"
                                        value="{{ old('tile_' . $mod . '_bg_color', $tc['bg_color'] ?? '') }}"
                                        placeholder="#ffffff" data-dink="{{ $mod }}">
                                </div>
                            </div>
                            <div class="ld-ctl">
                                <label class="ld-ctl-label" for="tile_{{ $mod }}_text_color"><span>Text
                                        colour</span></label>
                                <div class="ld-color-row">
                                    <input type="color" value="{{ $tc['text_color'] ?: '#101828' }}"
                                        data-dsync="tile_{{ $mod }}_text_color">
                                    <input type="text" name="tile_{{ $mod }}_text_color"
                                        id="tile_{{ $mod }}_text_color" class="form-control ld-hex"
                                        value="{{ old('tile_' . $mod . '_text_color', $tc['text_color'] ?? '') }}"
                                        placeholder="auto" data-dink-text="{{ $mod }}">
                                </div>
                            </div>
                        </div>
                        <div class="ld-hint">Leave the text colour blank and it picks black or white automatically.</div>

                        <label class="ld-switch">
                            <input type="checkbox" name="tile_{{ $mod }}_show_rule" value="1"
                                @checked($tc['show_rule']) data-dshow="{{ $sel }} .cl-card-rule">
                            <span>Show the thin divider line</span>
                        </label>

                        <div class="ld-ctl">
                            <label class="ld-ctl-label" for="tile_{{ $mod }}_link_url"><span>Open a link instead of
                                    the module</span></label>
                            <input type="url" name="tile_{{ $mod }}_link_url"
                                id="tile_{{ $mod }}_link_url" class="form-control"
                                placeholder="https://sponsor.example.com"
                                value="{{ old('tile_' . $mod . '_link_url', $tc['link_url'] ?? '') }}">
                            <label class="ld-switch" style="margin-top:8px">
                                <input type="checkbox" name="tile_{{ $mod }}_link_external" value="1"
                                    @checked($tc['link_external'] ?? false)>
                                <span>Open in a new tab</span>
                            </label>
                        </div>
                    </div>
                </details>
            @endforeach

            {{-- ── 10. Hashtag & footer ─────────────────────────────────────── --}}
            <details class="ld-sec">
                <summary><span class="ld-num">10</span> Hashtag &amp; Footer <i data-lucide="chevron-down"
                        class="lucide-icon ld-chev"></i></summary>
                <div class="ld-sec-body">
                    <label class="ld-switch">
                        <input type="checkbox" name="design[hashtag_show]" value="1" @checked($ld['hashtag_show'])
                            data-dshow=".cl-hashtag">
                        <span>Show the big hashtag</span>
                    </label>

                    <div class="ld-ctl">
                        <label class="ld-ctl-label" for="vidiwall_overlay_text"><span>Hashtag text</span></label>
                        <input name="vidiwall_overlay_text" id="vidiwall_overlay_text" class="form-control"
                            value="{{ old('vidiwall_overlay_text', $event->vidiwall_overlay_text ?? '') }}"
                            placeholder="#skiverrueckt" data-dtext=".cl-hashtag"
                            data-dfallback="{{ $event->name ?? 'Your Event' }}">
                        <div class="ld-hint">Also used as the overlay on the Vidiwall.</div>
                    </div>

                    @include('admin.events._design-range', [
                        'name' => 'design[hashtag_size]',
                        'label' => 'Hashtag size',
                        'value' => $ld['hashtag_size'],
                        'min' => 10,
                        'max' => 48,
                        'suffix' => 'px',
                        'var' => '--cl-hashtag-size',
                        'unit' => 'px',
                    ])
                    @include('admin.events._design-range', [
                        'name' => 'design[footer_size]',
                        'label' => 'Footer text size',
                        'value' => $ld['footer_size'],
                        'min' => 8,
                        'max' => 18,
                        'suffix' => 'px',
                        'var' => '--cl-footer-size',
                        'unit' => 'px',
                    ])
                    @include('admin.events._design-range', [
                        'name' => 'design[watermark_opacity]',
                        'label' => 'Background watermark',
                        'value' => $ld['watermark_opacity'],
                        'min' => 0,
                        'max' => 40,
                        'suffix' => '%',
                        'var' => '--cl-wm-opacity',
                        'scale' => 0.01,
                        'hint' => 'A faint, oversized copy of your logo behind the page. 0 = off.',
                    ])
                </div>
            </details>
        </div>

        {{-- ══ Live iPhone 16 preview ════════════════════════════════════════ --}}
        <div class="ld-preview-col">
            <div class="card ld-preview-card">
                <div class="card-header ld-preview-head">
                    <h3><i data-lucide="smartphone" class="lucide-icon"></i> Live Preview</h3>
                    <div class="ld-preview-tools">
                        <button type="button" class="btn btn-secondary btn-sm" id="ldZoomOut"
                            title="Smaller">&minus;</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="ldZoomIn"
                            title="Bigger">+</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="ldReload" title="Reload preview">
                            <i data-lucide="rotate-cw" class="lucide-icon"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body ld-preview-body">
                    @if (isset($event))
                        <div class="ld-phone-stage" id="ldStage">
                            <div class="ld-phone">
                                <div class="ld-phone-btn ld-phone-btn-silent"></div>
                                <div class="ld-phone-btn ld-phone-btn-up"></div>
                                <div class="ld-phone-btn ld-phone-btn-down"></div>
                                <div class="ld-phone-btn ld-phone-btn-power"></div>
                                <div class="ld-phone-screen">
                                    <div class="ld-phone-island"></div>
                                    <iframe id="ldPreview" title="Landing page preview"
                                        src="{{ route('admin.events.preview', $event) }}"></iframe>
                                    <div class="ld-phone-home"></div>
                                </div>
                            </div>
                        </div>
                        <div class="ld-preview-foot">
                            iPhone 16 · 393 × 852 — taps are disabled in the preview.
                            <a href="{{ $event->getGuestUrl() }}" target="_blank">Open the real page</a>
                        </div>
                    @else
                        <div class="ld-phone-empty">
                            <i data-lucide="smartphone" class="lucide-icon"></i>
                            <p>Create the event first — the live phone preview appears here as soon as it exists.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .ld-layout { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:22px; align-items:start }
        .ld-controls { display:flex; flex-direction:column; gap:10px; min-width:0 }

        .ld-intro { display:flex; gap:10px; align-items:flex-start; padding:13px 15px; border-radius:11px;
            background:color-mix(in srgb, var(--red) 12%, transparent); border:1px solid color-mix(in srgb, var(--red) 30%, transparent);
            font-size:13px; line-height:1.55 }
        .ld-intro .lucide-icon { width:17px; height:17px; flex-shrink:0; margin-top:2px; color:var(--red) }

        /* Sections */
        .ld-sec { background:var(--card); border:1px solid var(--border); border-radius:12px; overflow:hidden }
        .ld-sec[open] { border-color:color-mix(in srgb, var(--red) 40%, var(--border)) }
        .ld-sec > summary { display:flex; align-items:center; gap:9px; padding:13px 15px; cursor:pointer;
            font-weight:700; font-size:13.5px; list-style:none; user-select:none }
        .ld-sec > summary::-webkit-details-marker { display:none }
        .ld-sec > summary:hover { background:rgba(255,255,255,.03) }
        .ld-sec > summary .lucide-icon { width:15px; height:15px }
        .ld-chev { margin-left:auto; opacity:.5; transition:transform .2s }
        .ld-sec[open] > summary .ld-chev { transform:rotate(180deg) }
        .ld-num { display:grid; place-items:center; width:21px; height:21px; border-radius:6px; flex-shrink:0;
            background:var(--red); color:#fff; font-size:11px; font-weight:800 }
        .ld-tag { font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; opacity:.55;
            border:1px solid var(--border); border-radius:20px; padding:2px 8px }
        .ld-tag-off { color:#f87171; border-color:rgba(248,113,113,.4); opacity:1 }
        .ld-sec-body { padding:4px 15px 16px; display:flex; flex-direction:column; gap:13px;
            border-top:1px solid var(--border) }

        .ld-note { font-size:12px; line-height:1.5; color:var(--muted); padding:9px 11px; border-radius:8px;
            background:rgba(255,255,255,.03); margin-top:12px }
        .ld-note-warn { color:#fca5a5; background:rgba(248,113,113,.08) }
        .ld-hint { font-size:11.5px; color:var(--muted); line-height:1.45; margin-top:5px }

        /* Controls */
        .ld-ctl { display:block }
        .ld-ctl-label { display:flex; align-items:baseline; justify-content:space-between; gap:10px;
            font-size:12px; font-weight:600; color:var(--muted); margin-bottom:6px }
        .ld-ctl-label output { font-family:ui-monospace,monospace; font-size:11.5px; font-weight:700; color:var(--text);
            background:var(--dark); border:1px solid var(--border); border-radius:6px; padding:1px 7px; flex-shrink:0 }

        .ld-range { -webkit-appearance:none; appearance:none; width:100%; height:5px; border-radius:3px;
            background:linear-gradient(to right, var(--red) var(--pct,50%), rgba(255,255,255,.12) var(--pct,50%));
            outline:none; cursor:pointer; display:block }
        .ld-range::-webkit-slider-thumb { -webkit-appearance:none; width:17px; height:17px; border-radius:50%;
            background:#fff; border:3px solid var(--red); cursor:grab; box-shadow:0 1px 5px rgba(0,0,0,.5) }
        .ld-range::-moz-range-thumb { width:14px; height:14px; border-radius:50%; background:#fff;
            border:3px solid var(--red); cursor:grab }

        .ld-switch { display:flex; align-items:center; gap:10px; font-size:13px; cursor:pointer; user-select:none }
        .ld-switch input { accent-color:var(--red); width:16px; height:16px; cursor:pointer; flex-shrink:0 }

        .ld-colors { display:grid; grid-template-columns:1fr 1fr; gap:12px }
        .ld-color-row { display:flex; gap:8px; align-items:center }
        .ld-color-row input[type=color] { width:34px; height:34px; padding:2px; border-radius:7px;
            border:1px solid var(--border); background:var(--dark); cursor:pointer; flex-shrink:0 }
        .ld-hex { font-family:ui-monospace,monospace; font-size:12px }

        .ld-img-row { display:flex; gap:11px; align-items:center; flex-wrap:wrap }
        .ld-img-row .form-control { flex:1; min-width:150px }
        .ld-thumb { position:relative; width:60px; height:60px; flex-shrink:0; border-radius:9px;
            border:1px solid var(--border); background:#fff; overflow:hidden }
        .ld-thumb img { width:100%; height:100%; object-fit:contain }
        .ld-thumb-x { position:absolute; top:-6px; right:-6px; width:20px; height:20px; border-radius:50%;
            background:var(--red); color:#fff; display:grid; place-items:center; cursor:pointer }
        .ld-thumb-x input { display:none }
        .ld-thumb-x .lucide-icon { width:12px; height:12px }
        .ld-thumb-x:has(input:checked) { opacity:.45 }

        /* ── iPhone 16 preview ───────────────────────────────────────────── */
        .ld-preview-col { position:sticky; top:78px }
        .ld-preview-card { margin-bottom:0 }
        .ld-preview-head { display:flex; align-items:center; justify-content:space-between; gap:10px }
        .ld-preview-tools { display:flex; gap:5px }
        .ld-preview-tools .btn { padding:4px 9px; line-height:1 }
        .ld-preview-tools .lucide-icon { width:13px; height:13px }
        .ld-preview-body { display:flex; flex-direction:column; align-items:center; gap:10px }

        .ld-phone-stage { --s:.62; width:calc(419px * var(--s)); height:calc(878px * var(--s)); position:relative }
        .ld-phone { position:absolute; top:0; left:0; width:419px; height:878px; transform:scale(var(--s));
            transform-origin:top left; padding:13px; border-radius:60px;
            background:linear-gradient(145deg,#5a5a5f 0%,#232326 26%,#1b1b1e 72%,#48484d 100%);
            box-shadow:0 30px 70px -24px rgba(0,0,0,.85), 0 0 0 1px rgba(255,255,255,.06) inset }
        .ld-phone-screen { position:relative; width:393px; height:852px; border-radius:47px; overflow:hidden;
            background:#000; box-shadow:0 0 0 2px #0b0b0d }
        .ld-phone-screen iframe { width:393px; height:852px; border:0; display:block; background:#000 }
        .ld-phone-island { position:absolute; top:12px; left:50%; transform:translateX(-50%); width:122px; height:34px;
            border-radius:20px; background:#000; z-index:5; pointer-events:none }
        .ld-phone-home { position:absolute; bottom:9px; left:50%; transform:translateX(-50%); width:138px; height:5px;
            border-radius:3px; background:rgba(255,255,255,.55); z-index:5; pointer-events:none }
        .ld-phone-btn { position:absolute; background:linear-gradient(180deg,#3a3a3f,#202024); border-radius:2px }
        .ld-phone-btn-silent { left:-2px; top:150px; width:4px; height:32px }
        .ld-phone-btn-up { left:-2px; top:210px; width:4px; height:58px }
        .ld-phone-btn-down { left:-2px; top:282px; width:4px; height:58px }
        .ld-phone-btn-power { right:-2px; top:232px; width:4px; height:92px }

        .ld-preview-foot { font-size:11px; color:var(--muted); text-align:center; line-height:1.6 }
        .ld-preview-foot a { color:var(--red) }
        .ld-phone-empty { text-align:center; padding:50px 20px; color:var(--muted); font-size:13px; line-height:1.6 }
        .ld-phone-empty .lucide-icon { width:32px; height:32px; opacity:.4; margin-bottom:10px }

        @media(max-width:1180px){
            .ld-layout { grid-template-columns:1fr }
            .ld-preview-col { position:static; order:-1 }
            .ld-colors { grid-template-columns:1fr }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const panel = document.getElementById('ldPanel');
            if (!panel) return;

            const frame = document.getElementById('ldPreview');
            const stage = document.getElementById('ldStage');
            const tileImages = {};

            // Remember the saved artwork so un-ticking "remove image" can put it back.
            const savedImages = {};
            panel.querySelectorAll('[data-dclear]').forEach(el => {
                savedImages[el.dataset.dclear] = el.closest('.ld-img-row')?.querySelector('.ld-thumb img')?.src || '';
            });

            const HEX = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i;

            function readableInk(hex) {
                let h = hex.replace('#', '');
                if (h.length === 3) h = h.replace(/(.)/g, '$1$1');
                const lum = (0.299 * parseInt(h.slice(0, 2), 16)
                    + 0.587 * parseInt(h.slice(2, 4), 16)
                    + 0.114 * parseInt(h.slice(4, 6), 16)) / 255;
                return lum > 0.55 ? '#101828' : '#ffffff';
            }

            function escapeHtml(s) {
                return s.replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                }[c]));
            }

            // **phrase** → <strong>phrase</strong>, mirroring the Blade helper.
            function emphasize(s) {
                return escapeHtml(s).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            }

            function paintRange(el) {
                const min = parseFloat(el.min), max = parseFloat(el.max);
                const pct = max > min ? ((parseFloat(el.value) - min) / (max - min)) * 100 : 0;
                el.style.setProperty('--pct', pct + '%');
                const out = el.closest('.ld-ctl')?.querySelector('output');
                if (out) out.textContent = (+parseFloat(el.value)) + (el.dataset.dsuffix || '');
            }

            function bucket(store, scope) {
                if (scope === 'root') return store.root;
                store.tiles[scope] = store.tiles[scope] || {};
                return store.tiles[scope];
            }

            function snapshot() {
                const s = { __eb: 'design', root: {}, tiles: {}, text: {}, html: {}, show: {}, cls: {}, img: {} };

                panel.querySelectorAll('[data-dvar]').forEach(el => {
                    if (!el.dataset.dvar) return;
                    const target = bucket(s, el.dataset.dscope || 'root');
                    if (el.type === 'range') {
                        const n = parseFloat(el.value) * parseFloat(el.dataset.dscale || '1');
                        target[el.dataset.dvar] = (+n.toFixed(4)) + (el.dataset.dunit || '');
                    } else {
                        target[el.dataset.dvar] = el.value;
                    }
                });

                panel.querySelectorAll('[data-dtext]').forEach(el => {
                    const value = el.value.trim();
                    s.text[el.dataset.dtext] = value || (el.dataset.dfallback || '');
                    if (el.dataset.dhideEmpty) s.show[el.dataset.dtext] = !!value;
                });

                panel.querySelectorAll('[data-dhtml]').forEach(el => {
                    s.html[el.dataset.dhtml] = emphasize(el.value.trim() || (el.dataset.dfallback || ''));
                });

                panel.querySelectorAll('[data-dshow]').forEach(el => {
                    el.dataset.dshow.split('|').forEach(sel => { s.show[sel] = el.checked; });
                });

                panel.querySelectorAll('[data-dcls]').forEach(el => {
                    const [sel, name, whenValue] = el.dataset.dcls.split('|');
                    s.cls[sel] = s.cls[sel] || {};
                    s.cls[sel][name] = el.value === whenValue;
                });

                // Per-tile colours: blank text colour falls back to automatic contrast.
                panel.querySelectorAll('[data-dink]').forEach(el => {
                    const mod = el.dataset.dink;
                    const t = bucket(s, mod);
                    const bg = HEX.test(el.value.trim()) ? el.value.trim() : '#ffffff';
                    const custom = document.getElementById('tile_' + mod + '_text_color')?.value.trim() || '';
                    const ink = HEX.test(custom) ? custom : readableInk(bg);
                    t['--cl-card-bg'] = bg;
                    t['--cl-card-ink'] = ink;
                    t['--cl-icon'] = ink;
                });

                for (const mod in tileImages) s.img[mod] = tileImages[mod];

                return s;
            }

            function push() {
                frame?.contentWindow?.postMessage(snapshot(), location.origin);
            }

            // Keep the slider fill + number badge in sync, then update the phone.
            panel.querySelectorAll('.ld-range').forEach(paintRange);
            panel.addEventListener('input', e => {
                if (e.target.classList.contains('ld-range')) paintRange(e.target);
                if (e.target.dataset.dsync) {
                    const target = document.getElementById(e.target.dataset.dsync);
                    if (target) target.value = e.target.value;
                }
                push();
            });
            panel.addEventListener('change', e => {
                if (e.target.dataset.dfile) {
                    const file = e.target.files?.[0];
                    const mod = e.target.dataset.dfile;
                    if (!file) { tileImages[mod] = savedImages[mod] ?? ''; push(); return; }
                    const reader = new FileReader();
                    reader.onload = () => { tileImages[mod] = reader.result; push(); };
                    reader.readAsDataURL(file);
                    return;
                }
                if (e.target.dataset.dclear) {
                    const mod = e.target.dataset.dclear;
                    // An empty string tells the preview to fall back to the module icon.
                    tileImages[mod] = e.target.checked ? '' : (savedImages[mod] ?? '');
                }
                push();
            });

            window.addEventListener('message', e => {
                if (e.origin === location.origin && e.data?.__eb === 'preview-ready') push();
            });

            // Zoom + reload
            const zoom = d => {
                if (!stage) return;
                const now = parseFloat(getComputedStyle(stage).getPropertyValue('--s')) || 0.62;
                stage.style.setProperty('--s', Math.min(1, Math.max(0.35, now + d)).toFixed(2));
            };
            document.getElementById('ldZoomIn')?.addEventListener('click', () => zoom(0.08));
            document.getElementById('ldZoomOut')?.addEventListener('click', () => zoom(-0.08));
            document.getElementById('ldReload')?.addEventListener('click', () => {
                if (frame) frame.src = frame.src;
            });
        })();
    </script>
@endpush
