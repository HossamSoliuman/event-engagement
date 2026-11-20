{{-- Shared by the admin and moderator Fan Clash pages; $routePrefix picks the route set. --}}
@php
    $fcRoute = fn (string $name, mixed $param = null) => route($routePrefix . 'fanclash.' . $name, $param === null ? [$event] : [$event, $param]);
    $clashBg = $event->fanClashBackgroundColor();
    $clashInk = $event->readableInk($clashBg);
    $pool = $matchups->where('is_active', true)->values();
    $settingChecked = fn (string $field) => (bool) old($field, $screen->{$field});
@endphp

@push('styles')
    @if ($brandFonts = $event->googleFontsUrl())
        <link href="{{ $brandFonts }}" rel="stylesheet">
    @endif
    <style>
        [hidden] { display: none !important; }
        .fc { --fc-accent: var(--mod, var(--red)); }

        .fc-deck { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); overflow: hidden; margin-bottom: 16px; }

        /* ── Preview of the wall ── */
        .fc-screen { position: relative; aspect-ratio: 16 / 9; overflow: hidden; border-right: 1px solid var(--border); background: var(--cbg); color: var(--cink); container-type: inline-size; }
        .fc-pv { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2.4cqw; padding: 4cqw 5cqw; text-align: center; }
        .fc-pv-idle { background: #05050c; color: var(--muted); font-size: 13px; gap: 10px; }
        .fc-pv-idle .lucide-icon { width: 40px; height: 40px; stroke-width: 1.5px; opacity: .6; }
        .fc-pv-head { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 2cqw; }
        .fc-pv-brand { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: var(--cfont); font-weight: 800; font-size: 2.2cqw; letter-spacing: .2em; text-transform: uppercase; color: var(--chl); }
        .fc-pv-chip { flex: 0 0 auto; padding: .35em 1.1em; border-radius: 999px; background: #ef4444; color: #fff; font-family: var(--cfont); font-weight: 800; font-size: 1.5cqw; letter-spacing: .2em; text-transform: uppercase; }
        .fc-pv-chip.is-ready { background: color-mix(in srgb, var(--cink) 14%, transparent); color: var(--cink); }
        .fc-pv-names { width: 100%; display: flex; align-items: flex-end; justify-content: space-between; gap: 3cqw; }
        .fc-pv-name { max-width: 44%; font-family: var(--cfont); font-weight: 800; font-size: 5cqw; line-height: 1; text-transform: uppercase; }
        .fc-pv-name.is-a { color: var(--ca); text-align: left; }
        .fc-pv-name.is-b { color: var(--cb); text-align: right; }
        .fc-pv-vs { display: flex; align-items: center; justify-content: center; width: 6.4cqw; aspect-ratio: 1; border-radius: 50%; border: .2cqw solid color-mix(in srgb, var(--cink) 28%, transparent); font-family: var(--cfont); font-weight: 800; font-size: 2.2cqw; letter-spacing: .06em; text-indent: .06em; color: var(--chl); }
        [data-pv="matchup"] .fc-pv-head { margin-bottom: auto; }
        [data-pv="matchup"] .fc-pv-names { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); align-items: center; gap: 3cqw; margin-bottom: 1cqw; }
        [data-pv="matchup"] .fc-pv-name { max-width: none; text-align: center; }
        [data-pv="matchup"] .fc-pv-qr { width: 18cqw; }
        [data-pv="matchup"] .fc-pv-hint { margin-bottom: auto; opacity: .7; }
        .fc-pv-bar { position: relative; width: 100%; height: 5cqw; border-radius: 1cqw; overflow: hidden; background: color-mix(in srgb, var(--cink) 8%, transparent); border: 1px solid color-mix(in srgb, var(--cink) 16%, transparent); }
        .fc-pv-fill { position: absolute; top: 0; bottom: 0; width: 50%; transition: width .6s cubic-bezier(.4, 0, .2, 1); }
        .fc-pv-fill.is-a { left: 0; background: var(--ca); }
        .fc-pv-fill.is-b { right: 0; background: var(--cb); }
        .fc-pv-knot { position: absolute; top: 0; bottom: 0; left: 50%; width: .5cqw; background: #fff; transform: translateX(-50%); box-shadow: 0 0 0 .3cqw var(--cbg), 0 0 1.6cqw rgba(255, 255, 255, .85); transition: left .6s cubic-bezier(.4, 0, .2, 1); }
        .fc-pv-row { width: 100%; display: flex; align-items: center; justify-content: space-between; font-family: var(--cfont); font-weight: 800; font-size: 2.6cqw; font-variant-numeric: tabular-nums; }
        .fc-pv-row .is-a { color: var(--ca); }
        .fc-pv-row .is-b { color: var(--cb); }
        .fc-pv-count { font-size: 4cqw; }
        .fc-pv-qr { width: 14cqw; aspect-ratio: 1; padding: 1cqw; border-radius: 1.4cqw; background: #fff; display: flex; align-items: center; justify-content: center; color: #111; }
        .fc-pv-qr img { width: 100%; height: 100%; object-fit: contain; }
        .fc-pv-qr .lucide-icon { width: 70%; height: 70%; }
        .fc-pv-qr.is-mini { width: 7cqw; padding: .5cqw; border-radius: .8cqw; }
        .fc-pv-foot { display: flex; align-items: center; justify-content: center; gap: 1.6cqw; }
        .fc-pv-hint { font-family: var(--cfont); font-weight: 700; font-size: 1.6cqw; letter-spacing: .2em; text-transform: uppercase; opacity: .55; }
        .fc-pv-big { font-family: var(--cfont); font-weight: 800; font-size: 16cqw; line-height: 1; font-variant-numeric: tabular-nums; color: var(--chl); }
        .fc-pv-eyebrow { font-family: var(--cfont); font-weight: 700; font-size: 2.2cqw; letter-spacing: .3em; text-transform: uppercase; opacity: .6; }
        .fc-pv-winner { max-width: 100%; font-family: var(--cfont); font-weight: 800; font-size: 8cqw; line-height: 1; text-transform: uppercase; color: var(--cwin, var(--cink)); }
        .fc-pv-score { font-family: var(--cfont); font-weight: 800; font-size: 3cqw; font-variant-numeric: tabular-nums; }
        .fc-pv-score .is-a { color: var(--ca); }
        .fc-pv-score .is-b { color: var(--cb); }
        .fc-screen-tag { position: absolute; top: 12px; left: 12px; padding: 4px 11px; border-radius: 30px; font-size: 10px; font-weight: 800; letter-spacing: 1.6px; background: var(--gold); color: #0A0A18; }

        /* ── Control panel ── */
        .fc-panel { display: flex; flex-direction: column; justify-content: center; gap: 16px; padding: 22px 24px; min-width: 0; }
        .fc-label { display: flex; align-items: center; gap: 8px; font-size: 10px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); }
        .fc-label-dot { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; animation: fcPing 1.6s infinite; }
        @keyframes fcPing { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, .6); } 80%, 100% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } }
        .fc[data-stage="matchup"] .fc-label, .fc[data-stage="live"] .fc-label, .fc[data-stage="result"] .fc-label { color: var(--gold); }
        .fc-big { margin-top: 6px; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 44px; line-height: 1.05; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .fc-big.is-name { font-size: 28px; color: var(--gold); }
        .fc-big.is-ending { color: #ef4444; }
        .fc-meta { margin-top: 6px; font-size: 13px; color: var(--muted); }
        .fc-progress { height: 6px; border-radius: 6px; background: rgba(255, 255, 255, .08); overflow: hidden; }
        .fc-progress > div { height: 100%; width: 0; background: var(--gold); border-radius: 6px; }
        .fc-progress.is-ending > div { background: #ef4444; }

        .fc-transport { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .fc-main { display: inline-flex; align-items: center; justify-content: center; gap: 10px; height: 58px; padding: 0 28px; border: none; border-radius: 40px; background: var(--fc-accent); color: #fff; font: inherit; font-size: 16px; font-weight: 700; cursor: pointer; transition: filter .15s, opacity .15s, transform .1s; }
        .fc-main:hover:not(:disabled) { filter: brightness(1.1); }
        .fc-main:active:not(:disabled) { transform: scale(.97); }
        .fc-main:disabled { opacity: .35; cursor: default; }
        .fc-main .lucide-icon { width: 22px; height: 22px; }
        .fc-main [data-icon] { display: none; }
        .fc-main[data-action="prepare"] [data-icon="prepare"], .fc-main[data-action="start"] [data-icon="start"],
        .fc-main[data-action="end"] [data-icon="end"], .fc-main[data-action="finish"] [data-icon="finish"] { display: inline-flex; }
        .fc-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 46px; padding: 0 18px; border: 1px solid var(--border); border-radius: 30px; background: var(--card2); color: var(--text); font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s, border-color .15s, opacity .15s, transform .1s; }
        .fc-btn .lucide-icon { width: 16px; height: 16px; }
        .fc-btn:hover:not(:disabled) { border-color: var(--muted); }
        .fc-btn:active:not(:disabled) { transform: scale(.96); }
        .fc-btn:disabled { opacity: .35; cursor: default; }
        .fc-btn[aria-pressed] { color: var(--muted); }
        .fc-btn[aria-pressed="true"] { color: var(--text); border-color: var(--fc-accent); background: color-mix(in srgb, var(--fc-accent) 14%, var(--card2)); }
        .fc-hint { font-size: 13px; color: var(--gold); }
        .fc-link { align-self: flex-start; display: inline-flex; align-items: center; gap: 6px; padding: 0; border: none; background: none; color: var(--muted); font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; }
        .fc-link:hover { color: var(--text); }
        .fc-link:disabled { opacity: .35; cursor: default; }
        .fc-link .lucide-icon { width: 14px; height: 14px; }
        .fc-main.is-busy, .fc-btn.is-busy, .fc-link.is-busy { cursor: progress; }
        .fc-main.is-busy:disabled, .fc-btn.is-busy:disabled, .fc-link.is-busy:disabled { opacity: .8; }
        .fc-main.is-busy [data-icon], .fc-btn.is-busy .lucide-icon, .fc-link.is-busy .lucide-icon { display: none !important; }
        .fc-main.is-busy::before, .fc-btn.is-busy::before, .fc-link.is-busy::before { content: ''; flex: 0 0 auto; width: 16px; height: 16px; border-radius: 50%; border: 2px solid currentColor; border-right-color: transparent; animation: fcSpin .7s linear infinite; }
        .fc-main.is-busy::before { width: 20px; height: 20px; }
        @keyframes fcSpin { to { transform: rotate(360deg); } }

        .fc-setup { display: flex; flex-direction: column; gap: 10px; padding-top: 16px; border-top: 1px solid var(--border); }
        .fc-setup .form-label { margin-bottom: 0; }
        .fc-adhoc { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .fc-side { display: flex; align-items: center; gap: 8px; }
        .fc-side input[type=color] { flex: 0 0 auto; width: 38px; height: 38px; padding: 2px; border: 1px solid var(--border); border-radius: 8px; background: var(--card2); cursor: pointer; }
        .fc-adhoc .fc-category { grid-column: 1 / -1; }

        .fc-setting { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border); }
        .fc-setting-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--muted); }
        .fc-setting-label .lucide-icon { width: 16px; height: 16px; }
        .fc-segments { display: inline-flex; flex-wrap: wrap; padding: 3px; gap: 3px; border: 1px solid var(--border); border-radius: 24px; background: var(--card2); }
        .fc-segments button { min-width: 44px; height: 34px; padding: 0 12px; border: none; border-radius: 30px; background: none; color: var(--muted); font: inherit; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; cursor: pointer; transition: color .15s, background .15s, box-shadow .15s; }
        .fc-segments button:hover:not([aria-pressed="true"]) { color: var(--text); background: rgba(255, 255, 255, .05); }
        .fc-segments button[aria-pressed="true"] { color: var(--text); background: color-mix(in srgb, var(--fc-accent) 22%, var(--card2)); box-shadow: inset 0 0 0 1px var(--fc-accent); cursor: default; }
        .fc-segments small { font-size: 11px; font-weight: 600; margin-left: 1px; }

        /* ── Steps ── */
        .fc-steps { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border-top: 1px solid var(--border); }
        .fc-step { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 10px; font-size: 13px; font-weight: 600; color: var(--muted); }
        .fc-step + .fc-step { border-left: 1px solid var(--border); }
        .fc-step-num { display: flex; align-items: center; justify-content: center; flex: 0 0 auto; width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--border); font-size: 12px; font-weight: 800; }
        .fc-step.is-active { color: var(--text); background: color-mix(in srgb, var(--fc-accent) 10%, transparent); }
        .fc-step.is-active .fc-step-num { border-color: var(--fc-accent); background: var(--fc-accent); color: #fff; }
        .fc-step.is-done .fc-step-num { border-color: var(--gold); color: var(--gold); }

        /* ── Matchups, rounds and screen options ── */
        .fc-swatch { display: inline-block; width: 12px; height: 12px; border-radius: 3px; }
        .fc-matchup { padding: 14px 18px; border-bottom: 1px solid var(--border); }
        .fc-matchup:last-child { border-bottom: none; }
        .fc-matchup-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
        .fc-matchup-names { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; font-size: 14px; font-weight: 700; }
        .fc-matchup-names > span { display: inline-flex; align-items: center; gap: 6px; }
        .fc-matchup-cat { margin-bottom: 6px; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--muted); }
        .fc-matchup-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .fc-matchup-actions img { height: 28px; width: auto; border-radius: 4px; background: #fff; padding: 2px; }
        .fc-color-input { height: 38px; padding: 2px; }

        .fc-options > summary { list-style: none; cursor: pointer; }
        .fc-options > summary::-webkit-details-marker { display: none; }
        .fc-options > summary h3 { flex: 1; display: flex; align-items: center; gap: 8px; }
        .fc-options:not([open]) > summary { border-bottom: none; }
        .fc-options-chevron { margin-left: auto; transition: transform .2s; }
        .fc-options[open] .fc-options-chevron { transform: rotate(180deg); }
        .fc-saved { font-size: 12px; font-weight: 600; color: #4ade80; opacity: 0; transition: opacity .2s; }
        .fc-saved.is-shown { opacity: 1; }
        .fc-field { padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
        .fc-field .form-label { margin-bottom: 8px; }
        .fc-stepper { display: inline-flex; align-items: center; height: 40px; border: 1px solid var(--border); border-radius: 30px; background: var(--card2); overflow: hidden; }
        .fc-stepper:focus-within { border-color: var(--fc-accent); }
        .fc-stepper button { width: 38px; height: 100%; border: none; background: none; color: var(--muted); font-size: 18px; line-height: 1; cursor: pointer; }
        .fc-stepper button:hover { color: var(--text); background: rgba(255, 255, 255, .05); }
        .fc-stepper input { width: 50px; height: 100%; border: none; background: none; color: var(--text); font-size: 15px; font-weight: 700; text-align: center; font-variant-numeric: tabular-nums; -moz-appearance: textfield; appearance: textfield; }
        .fc-stepper input::-webkit-outer-spin-button, .fc-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .fc-stepper input:focus { outline: none; }
        .fc-unit { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }
        .fc-checks { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 24px; }
        .fc-check { display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; }
        .fc-check input { width: 16px; height: 16px; accent-color: var(--gold); cursor: pointer; }

        .fc-toast { position: fixed; left: 50%; bottom: 26px; z-index: 300; transform: translate(-50%, 20px); padding: 11px 18px; border-radius: 10px; background: var(--card2); border: 1px solid var(--border); box-shadow: 0 16px 40px rgba(0, 0, 0, .45); font-size: 13px; font-weight: 600; opacity: 0; pointer-events: none; transition: opacity .2s, transform .2s; max-width: calc(100vw - 32px); }
        .fc-toast.is-shown { opacity: 1; transform: translate(-50%, 0); }
        .fc-toast.is-error { border-color: rgba(239, 68, 68, .45); color: #f87171; }

        @media (max-width: 900px) {
            .fc-deck { grid-template-columns: 1fr; }
            .fc-screen { border-right: none; border-bottom: 1px solid var(--border); }
            .fc-checks { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .fc-panel { padding: 18px 16px; }
            .fc-main { width: 100%; }
            .fc-adhoc { grid-template-columns: 1fr; }
            .fc-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .fc-step:nth-child(3) { border-left: none; }
            .fc-step:nth-child(n + 3) { border-top: 1px solid var(--border); }
        }
    </style>
@endpush

@if (session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<div class="fc" id="fc" data-stage="{{ $screenStatus['stage'] }}">
    <div class="card fc-deck">
        <div class="fc-screen" id="fcScreen"
            style="--cfont: '{{ $event->headingFont() }}', 'Syne', sans-serif; --cbg: {{ $clashBg }}; --cink: {{ $clashInk }}; --chl: {{ $event->fanclash_accent_color ? $event->fanClashAccentColor() : $clashInk }}; --ca: #ef4444; --cb: #3B82F6">
            <div class="fc-pv fc-pv-idle" data-pv="idle">
                <i data-lucide="monitor-off" class="lucide-icon"></i>
                <span>Fan Clash is not on the vidiwall.</span>
            </div>

            <div class="fc-pv" data-pv="matchup" hidden>
                <div class="fc-pv-head">
                    <span class="fc-pv-brand" data-pv-brand></span>
                    <span class="fc-pv-chip is-ready">Get ready</span>
                </div>
                <div class="fc-pv-names">
                    <div class="fc-pv-name is-a" data-pv-a></div>
                    <div class="fc-pv-vs">VS</div>
                    <div class="fc-pv-name is-b" data-pv-b></div>
                </div>
                <div class="fc-pv-qr" id="fcPvQr">
                    @if ($event->qr_code_path)
                        <img src="{{ $event->qr_code_url }}" alt="QR code">
                    @else
                        <i data-lucide="qr-code" class="lucide-icon"></i>
                    @endif
                </div>
                <div class="fc-pv-hint">Scan to pick your side</div>
            </div>

            <div class="fc-pv" data-pv="countdown" hidden>
                <div class="fc-pv-eyebrow">Get ready</div>
                <div class="fc-pv-big" id="fcPvCount"></div>
            </div>

            <div class="fc-pv" data-pv="live" hidden>
                <div class="fc-pv-head">
                    <span class="fc-pv-brand" data-pv-brand></span>
                    <span class="fc-pv-chip">&#9679; Live</span>
                </div>
                <div class="fc-pv-names">
                    <div class="fc-pv-name is-a" data-pv-a></div>
                    <div class="fc-pv-name is-b" data-pv-b></div>
                </div>
                <div class="fc-pv-bar">
                    <div class="fc-pv-fill is-a" id="fcPvFillA"></div>
                    <div class="fc-pv-fill is-b" id="fcPvFillB"></div>
                    <div class="fc-pv-knot" id="fcPvKnot"></div>
                </div>
                <div class="fc-pv-row">
                    <span class="is-a" id="fcPvTapsA"></span>
                    <span class="fc-pv-count" id="fcPvLeft"></span>
                    <span class="is-b" id="fcPvTapsB"></span>
                </div>
                <div class="fc-pv-foot" id="fcPvFoot">
                    <div class="fc-pv-qr is-mini">
                        @if ($event->qr_code_path)
                            <img src="{{ $event->qr_code_url }}" alt="QR code">
                        @else
                            <i data-lucide="qr-code" class="lucide-icon"></i>
                        @endif
                    </div>
                    <div class="fc-pv-hint">Scan &middot; pick a side &middot; tap to win</div>
                </div>
            </div>

            <div class="fc-pv" data-pv="result" hidden>
                <div class="fc-pv-eyebrow" id="fcPvEyebrow">Winner</div>
                <div class="fc-pv-winner" id="fcPvWinner"></div>
                <div class="fc-pv-score" id="fcPvScore"><span class="is-a"></span> &ndash; <span class="is-b"></span></div>
            </div>

            <span class="fc-screen-tag" id="fcTag" hidden>LIVE</span>
        </div>

        <div class="fc-panel">
            <div>
                <div class="fc-label"><span class="fc-label-dot" id="fcLabelDot" hidden></span><span id="fcLabel">Ready</span></div>
                <div class="fc-big" id="fcBig"></div>
                <div class="fc-meta" id="fcMeta"></div>
            </div>

            <div class="fc-progress" id="fcProgress" hidden><div id="fcBar"></div></div>

            <div class="fc-transport">
                <button type="button" class="fc-main" id="fcMain" data-action="prepare">
                    <span data-icon="prepare"><i data-lucide="monitor-play" class="lucide-icon"></i></span>
                    <span data-icon="start"><i data-lucide="play" class="lucide-icon"></i></span>
                    <span data-icon="end"><i data-lucide="square" class="lucide-icon"></i></span>
                    <span data-icon="finish"><i data-lucide="check" class="lucide-icon"></i></span>
                    <span id="fcMainLabel">Show matchup</span>
                </button>
                <button type="button" class="fc-btn" id="fcExtend" hidden><i data-lucide="plus" class="lucide-icon"></i> {{ \App\Models\FanClashScreen::EXTEND_SECONDS }} s</button>
                <button type="button" class="fc-btn" id="fcWall" aria-pressed="false" hidden><i data-lucide="monitor" class="lucide-icon"></i> On the vidiwall</button>
            </div>

            <div class="fc-hint" id="fcHint" hidden></div>

            <div class="fc-setup" id="fcSetup">
                <label class="form-label" for="fcMatchup">Matchup</label>
                @if ($pool->isNotEmpty())
                    <select id="fcMatchup" class="form-control">
                        @foreach ($pool as $matchup)
                            <option value="{{ $matchup->id }}" @selected($matchup->id === $nextMatchupId)
                                data-a="{{ $matchup->side_a_name }}" data-b="{{ $matchup->side_b_name }}">
                                {{ $matchup->side_a_name }} vs {{ $matchup->side_b_name }}{{ $matchup->category ? ' · ' . $matchup->category : '' }}
                            </option>
                        @endforeach
                        <option value="">Type two names</option>
                    </select>
                @endif
                <div class="fc-adhoc" id="fcAdhoc" @if ($pool->isNotEmpty()) hidden @endif>
                    <div class="fc-side">
                        <input type="color" id="fcColorA" value="{{ $event->fanClashAccentColor() }}" aria-label="Side A colour">
                        <input type="text" id="fcNameA" class="form-control" maxlength="60" placeholder="Side A" aria-label="Side A name">
                    </div>
                    <div class="fc-side">
                        <input type="color" id="fcColorB" value="#3B82F6" aria-label="Side B colour">
                        <input type="text" id="fcNameB" class="form-control" maxlength="60" placeholder="Side B" aria-label="Side B name">
                    </div>
                    <input type="text" id="fcCategory" class="form-control fc-category" maxlength="60" placeholder="Category (optional)" aria-label="Category">
                </div>
            </div>

            <div class="fc-setting" id="fcLengthSetting">
                <span class="fc-setting-label" id="fcLengthLabel"><i data-lucide="timer" class="lucide-icon"></i> Round length</span>
                <div class="fc-segments" role="group" aria-labelledby="fcLengthLabel">
                    @foreach (\App\Models\FanClashScreen::ROUND_SECONDS as $seconds)
                        <button type="button" data-seconds="{{ $seconds }}" aria-pressed="{{ $seconds === $screen->round_seconds ? 'true' : 'false' }}">{{ $seconds }}<small>s</small></button>
                    @endforeach
                </div>
            </div>

            <button type="button" class="fc-link" id="fcCancel" hidden><i data-lucide="repeat" class="lucide-icon"></i> Change matchup</button>
        </div>

        <div class="fc-steps">
            @foreach (['Matchup', 'Get ready', 'Clash', 'Winner'] as $index => $step)
                <div class="fc-step" data-flow-step="{{ $index }}"><span class="fc-step-num">{{ $index + 1 }}</span> {{ $step }}</div>
            @endforeach
        </div>
    </div>

    <div class="grid-2" style="gap:16px;align-items:start">
        <div>
            <div class="card mb-3">
                <div class="card-header">
                    <h3><i data-lucide="users" class="lucide-icon"></i> Matchup Pool ({{ $matchups->count() }})</h3>
                </div>

                @forelse ($matchups as $m)
                    <div class="fc-matchup">
                        <div class="fc-matchup-head">
                            <div>
                                @if ($m->category)
                                    <div class="fc-matchup-cat">{{ $m->category }}</div>
                                @endif
                                <div class="fc-matchup-names">
                                    <span><span class="fc-swatch" style="background:{{ $m->side_a_color ?: '#ef4444' }}"></span>{{ $m->side_a_name }}</span>
                                    <span class="text-muted text-xs">vs</span>
                                    <span><span class="fc-swatch" style="background:{{ $m->side_b_color ?: '#3B82F6' }}"></span>{{ $m->side_b_name }}</span>
                                    @unless ($m->is_active)
                                        <span class="badge">Hidden</span>
                                    @endunless
                                </div>
                            </div>
                            <div class="fc-matchup-actions">
                                @if ($m->sponsor_logo_url)
                                    <img src="{{ $m->sponsor_logo_url }}" alt="Sponsor">
                                @endif
                                <button type="button" class="btn btn-ghost btn-sm" onclick="fcToggleEdit({{ $m->id }})" aria-label="Edit matchup"><i data-lucide="pencil" class="lucide-icon"></i></button>
                                <form method="POST" action="{{ $fcRoute('matchups.destroy', $m) }}" data-confirm="Delete this matchup?" data-confirm-button="Delete" data-confirm-danger>
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm" style="color:var(--red)" aria-label="Delete matchup"><i data-lucide="trash-2" class="lucide-icon"></i></button>
                                </form>
                            </div>
                        </div>

                        <div id="matchup-edit-{{ $m->id }}" style="display:none;margin-top:12px">
                            <form method="POST" action="{{ $fcRoute('matchups.update', $m) }}" enctype="multipart/form-data">
                                @csrf @method('PUT')
                                <div class="form-group">
                                    <label class="form-label">Category (optional)</label>
                                    <input type="text" name="category" class="form-control" value="{{ $m->category }}" placeholder="e.g. Downhill">
                                </div>
                                <div class="form-row">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Side A name</label>
                                        <input type="text" name="side_a_name" class="form-control" value="{{ $m->side_a_name }}" required>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Color</label>
                                        <input type="color" name="side_a_color" class="form-control fc-color-input" value="{{ $m->side_a_color ?: '#ef4444' }}">
                                    </div>
                                </div>
                                <div class="form-row" style="margin-top:12px">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Side B name</label>
                                        <input type="text" name="side_b_name" class="form-control" value="{{ $m->side_b_name }}" required>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label">Color</label>
                                        <input type="color" name="side_b_color" class="form-control fc-color-input" value="{{ $m->side_b_color ?: '#3B82F6' }}">
                                    </div>
                                </div>
                                <div class="form-group" style="margin-top:12px">
                                    <label class="form-label" style="display:flex;align-items:center;gap:8px;font-weight:400;cursor:pointer">
                                        <input type="checkbox" name="is_active" value="1" {{ $m->is_active ? 'checked' : '' }}> Show in the matchup picker
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Sponsor logo (optional)</label>
                                    @if ($m->sponsor_logo_url)
                                        <label style="display:block;font-weight:400;font-size:12px;cursor:pointer;margin-bottom:6px">
                                            <input type="checkbox" name="sponsor_logo_clear" value="1"> Remove current logo
                                        </label>
                                    @endif
                                    <input type="file" name="sponsor_logo" class="form-control" accept="image/*">
                                </div>
                                <div style="display:flex;gap:8px">
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                    <button type="button" class="btn btn-ghost btn-sm" onclick="fcToggleEdit({{ $m->id }})">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="padding:30px">
                        <div class="empty-icon"><i data-lucide="swords" class="lucide-icon"></i></div>
                        <h3>No matchups yet</h3>
                        <p>Save matchups to run them in order, or type two names on the console.</p>
                    </div>
                @endforelse
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3>Add Matchup</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ $fcRoute('matchups.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Category (optional)</label>
                            <input type="text" name="category" class="form-control" placeholder="e.g. Downhill" value="{{ old('category') }}">
                        </div>
                        <div class="form-row">
                            <div class="form-group mb-0">
                                <label class="form-label">Side A name *</label>
                                <input type="text" name="side_a_name" class="form-control" placeholder="Odermatt" value="{{ old('side_a_name') }}" required>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Color</label>
                                <input type="color" name="side_a_color" class="form-control fc-color-input" value="{{ old('side_a_color', $event->fanClashAccentColor()) }}">
                            </div>
                        </div>
                        <div class="form-row" style="margin-top:12px">
                            <div class="form-group mb-0">
                                <label class="form-label">Side B name *</label>
                                <input type="text" name="side_b_name" class="form-control" placeholder="Kilde" value="{{ old('side_b_name') }}" required>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Color</label>
                                <input type="color" name="side_b_color" class="form-control fc-color-input" value="{{ old('side_b_color', '#3B82F6') }}">
                            </div>
                        </div>
                        <div class="form-group" style="margin-top:12px">
                            <label class="form-label">Sponsor logo (optional)</label>
                            <input type="file" name="sponsor_logo" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Matchup</button>
                    </form>
                </div>
            </div>
        </div>

        <div>
            <details class="card mb-3 fc-options" id="fcOptions">
                <summary class="card-header">
                    <h3><i data-lucide="sliders-horizontal" class="lucide-icon"></i> Screen options <span class="fc-saved" id="fcSaved">Saved</span><i data-lucide="chevron-down" class="lucide-icon fc-options-chevron"></i></h3>
                </summary>
                <form method="POST" action="{{ $fcRoute('screen.update') }}" class="card-body" id="fcSettings">
                    @csrf
                    @method('PUT')

                    <div class="fc-field">
                        <label class="form-label" for="fcCountdown">Countdown before the start</label>
                        <div class="fc-stepper">
                            <button type="button" data-step="-1" aria-label="Shorter countdown">−</button>
                            <input type="number" id="fcCountdown" name="countdown_seconds" min="0" max="10" required value="{{ old('countdown_seconds', $screen->countdown_seconds) }}">
                            <button type="button" data-step="1" aria-label="Longer countdown">+</button>
                        </div>
                        <span class="fc-unit">seconds, 0 starts the round straight away</span>
                    </div>

                    <div class="fc-checks">
                        @foreach ([
                            'show_taps' => 'Show the tap counts',
                            'show_qr' => 'Show the QR code with the matchup',
                            'confetti' => 'Confetti on the winner',
                        ] as $field => $label)
                            <label class="fc-check">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input type="checkbox" name="{{ $field }}" value="1" id="fc-{{ $field }}" @checked($settingChecked($field))> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </form>
            </details>

            @if ($rounds->count())
                <div class="card">
                    <div class="card-header"><h3>Rounds</h3></div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Status</th>
                                    <th>Matchup</th>
                                    <th>Result</th>
                                    <th>Fans</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rounds as $round)
                                    <tr>
                                        <td class="text-muted text-xs">{{ $round->id }}</td>
                                        <td>
                                            @if ($round->status === 'active')
                                                <span class="badge badge-active">Live</span>
                                            @elseif ($round->status === 'finished')
                                                <span class="badge badge-pending">Finished</span>
                                            @else
                                                <span class="badge">Not started</span>
                                            @endif
                                        </td>
                                        <td class="text-sm">{{ $round->side_a_name }} vs {{ $round->side_b_name }}</td>
                                        <td class="text-sm">
                                            @if ($round->status === 'finished')
                                                {{ $round->side_a_taps }}–{{ $round->side_b_taps }}
                                                <span class="text-muted text-xs">
                                                    ({{ $round->winner_side === 'a' ? $round->side_a_name : ($round->winner_side === 'b' ? $round->side_b_name : 'Tie') }})
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $round->participants_count }}</td>
                                        <td>
                                            @if ($round->id !== $screen->fan_clash_round_id)
                                                <form method="POST" action="{{ $fcRoute('rounds.destroy', $round) }}" data-confirm="Delete this round?" data-confirm-message="Its taps are deleted with it." data-confirm-button="Delete" data-confirm-danger>
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-ghost btn-sm" style="color:var(--muted)" aria-label="Delete round"><i data-lucide="trash-2" class="lucide-icon"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="fc-toast" id="fcToast" role="status" aria-live="polite"></div>
</div>

@push('scripts')
    <script>
        function fcToggleEdit(id) {
            const form = document.getElementById('matchup-edit-' + id);
            form.style.display = form.style.display === 'none' ? '' : 'none';
        }

        (function () {
            const URLS = {
                status: @json($fcRoute('screen')),
                prepare: @json($fcRoute('prepare')),
                start: @json($fcRoute('start')),
                end: @json($fcRoute('end')),
                extend: @json($fcRoute('extend')),
                show: @json($fcRoute('show')),
                hide: @json($fcRoute('hide')),
                finish: @json($fcRoute('finish')),
                cancel: @json($fcRoute('cancel')),
            };
            const CSRF = document.querySelector('meta[name=csrf-token]').content;
            const TOAST_KEY = 'fcToast:' + @json($event->id);
            const STEP_OF = { ready: 0, matchup: 1, live: 2, result: 3 };
            const MAIN = {
                ready: ['prepare', 'Show matchup'],
                matchup: ['start', 'Start clash'],
                live: ['end', 'End round'],
                result: ['finish', 'Done'],
            };

            const $ = id => document.getElementById(id);
            const root = $('fc');
            const main = $('fcMain'), mainLabel = $('fcMainLabel');
            const extendBtn = $('fcExtend'), wallBtn = $('fcWall'), cancelBtn = $('fcCancel');
            const hint = $('fcHint'), toast = $('fcToast');
            const preview = $('fcScreen');
            const scenes = root.querySelectorAll('[data-pv]');
            const select = $('fcMatchup');
            const secondButtons = [...root.querySelectorAll('[data-seconds]')];
            const settings = $('fcSettings');

            let status = @json($screenStatus);
            let seconds = status.round_seconds;
            let startAt = null;
            let endAt = null;
            let busy = false;
            let pressed = null;
            let seq = 0;
            let navigating = false;
            let pollTimer = null;
            let toastTimer = null;
            let scene = null;
            let timeUpPolled = false;

            function setText(node, text) {
                if (node.textContent !== text) node.textContent = text;
            }

            function clock(ms) {
                const total = Math.max(0, Math.ceil(ms / 1000));
                return Math.floor(total / 60) + ':' + String(total % 60).padStart(2, '0');
            }

            function score(round) {
                return `${round.side_a_taps.toLocaleString()} – ${round.side_b_taps.toLocaleString()}`;
            }

            function winnerName(round) {
                if (round.winner_side === 'a') return round.side_a_name;
                if (round.winner_side === 'b') return round.side_b_name;
                return null;
            }

            function lastRoundText(round) {
                const name = winnerName(round);
                return name
                    ? `Last round: ${name} won ${score(round)}`
                    : `Last round: ${round.side_a_name} and ${round.side_b_name} tied ${score(round)}`;
            }

            function showToast(message, isError) {
                if (!message) return;
                toast.textContent = message;
                toast.classList.toggle('is-error', !!isError);
                toast.classList.add('is-shown');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toast.classList.remove('is-shown'), 3200);
            }

            function checked(field) {
                const box = $('fc-' + field);
                return box ? box.checked : true;
            }

            /* ── The matchup picked on the console ── */
            function choice() {
                if (select && select.value) {
                    const option = select.selectedOptions[0];
                    return { id: Number(select.value), a: option.dataset.a, b: option.dataset.b };
                }
                return {
                    id: null,
                    a: $('fcNameA').value.trim(),
                    b: $('fcNameB').value.trim(),
                    colorA: $('fcColorA').value,
                    colorB: $('fcColorB').value,
                    category: $('fcCategory').value.trim(),
                };
            }

            function choiceBody() {
                const picked = choice();
                if (picked.id) return { fan_clash_matchup_id: picked.id, duration_seconds: seconds };
                return {
                    side_a_name: picked.a,
                    side_b_name: picked.b,
                    side_a_color: picked.colorA,
                    side_b_color: picked.colorB,
                    category: picked.category,
                    duration_seconds: seconds,
                };
            }

            /* Everything the page shows, worked out from the last status and the clock. */
            function current(now) {
                const startsIn = startAt === null ? 0 : Math.max(0, startAt - now);
                const left = endAt === null ? null : Math.max(0, endAt - now);
                const live = status.stage === 'live';

                return {
                    round: status.round,
                    stage: status.stage,
                    onWall: status.on_screen,
                    startsIn,
                    left,
                    counting: live && startsIn > 0,
                    timeUp: live && left === 0,
                };
            }

            /* ── Control panel ── */
            function renderPanel(view) {
                const { round, stage, onWall, startsIn, left, counting, timeUp } = view;
                root.dataset.stage = stage;

                const [action, label] = MAIN[stage];
                main.dataset.action = action;
                setText(mainLabel, label);

                const picked = choice();
                const blockedBy = ['prepare', 'start'].includes(action) ? status.blocked : null;
                const missingNames = action === 'prepare' && (!picked.a || !picked.b);
                main.disabled = busy || navigating || !!blockedBy || missingNames;
                hint.hidden = !blockedBy;
                setText(hint, blockedBy || '');

                extendBtn.hidden = stage !== 'live' || counting || timeUp;
                wallBtn.hidden = stage === 'ready';
                wallBtn.setAttribute('aria-pressed', onWall ? 'true' : 'false');
                cancelBtn.hidden = stage !== 'matchup';
                $('fcSetup').hidden = stage !== 'ready';
                $('fcLengthSetting').hidden = stage !== 'ready' && stage !== 'matchup';
                [extendBtn, wallBtn, cancelBtn].forEach(button => button.disabled = busy || navigating);
                [main, extendBtn, wallBtn, cancelBtn].forEach(button => button.classList.toggle('is-busy', (busy || navigating) && pressed === button));
                if (!onWall && status.blocked) wallBtn.disabled = true;

                const big = $('fcBig');
                let labelText, bigText, metaText, isName = false, ending = false;
                const wallText = onWall ? 'on the vidiwall' : 'not on the vidiwall';

                if (stage === 'ready') {
                    labelText = 'Ready';
                    bigText = picked.a && picked.b ? `${picked.a} vs ${picked.b}` : 'Pick a matchup';
                    isName = true;
                    metaText = status.last ? lastRoundText(status.last) : `${seconds} second round`;
                } else if (stage === 'matchup') {
                    labelText = 'Get ready';
                    bigText = `${round.side_a_name} vs ${round.side_b_name}`;
                    isName = true;
                    metaText = onWall ? 'On the vidiwall · fans scan and pick a side' : 'Not on the vidiwall';
                } else if (stage === 'live' && counting) {
                    labelText = 'Starting';
                    bigText = String(Math.ceil(startsIn / 1000));
                    metaText = `${round.side_a_name} vs ${round.side_b_name} · ${wallText}`;
                } else if (stage === 'live') {
                    const fans = round.fans.a + round.fans.b;
                    labelText = timeUp ? 'Time is up' : 'Live';
                    bigText = timeUp ? score(round) : clock(left);
                    ending = !timeUp && left <= 5000;
                    metaText = `${round.side_a_name} ${score(round)} ${round.side_b_name} · ${fans.toLocaleString()} ${fans === 1 ? 'fan' : 'fans'} · ${wallText}`;
                } else {
                    const name = winnerName(round);
                    labelText = name ? 'Winner' : "It's a tie";
                    bigText = name || `${round.side_a_name} & ${round.side_b_name}`;
                    isName = true;
                    metaText = `${score(round)} · ` + (onWall ? 'on the vidiwall until you press Done' : 'not on the vidiwall');
                }

                setText($('fcLabel'), labelText);
                $('fcLabelDot').hidden = !(stage === 'live' && !counting && !timeUp);
                setText(big, bigText);
                big.classList.toggle('is-name', isName);
                big.classList.toggle('is-ending', ending);
                setText($('fcMeta'), metaText);

                const progress = $('fcProgress');
                let fraction = null;
                if (stage === 'live' && !counting && left !== null && round.duration_ms) {
                    fraction = left / round.duration_ms;
                }
                progress.hidden = fraction === null;
                progress.classList.toggle('is-ending', ending);
                if (fraction !== null) $('fcBar').style.width = (Math.max(0, Math.min(1, fraction)) * 100) + '%';

                const active = STEP_OF[stage];
                root.querySelectorAll('[data-flow-step]').forEach(step => {
                    const index = Number(step.dataset.flowStep);
                    step.classList.toggle('is-active', index === active);
                    step.classList.toggle('is-done', index < active);
                    setText(step.querySelector('.fc-step-num'), index < active ? '✓' : String(index + 1));
                });
            }

            /* ── Preview of the wall ── */
            function setScene(name) {
                if (scene === name) return;
                scene = name;
                scenes.forEach(node => node.hidden = node.dataset.pv !== name);
            }

            /* Text shrinks until its longest word fits its place, then wraps between words, as on the wall. */
            function fitText(node, text) {
                const width = String(preview.clientWidth);
                if (node.textContent === text && node.dataset.fitWidth === width) return;
                node.textContent = text;
                node.dataset.fitWidth = width;
                node.style.fontSize = '';
                let size = parseFloat(getComputedStyle(node).fontSize);
                for (let i = 0; i < 14 && node.scrollWidth > node.clientWidth + 1; i++) {
                    size *= .9;
                    node.style.fontSize = size + 'px';
                }
            }

            function renderPreview(view) {
                const { round, stage, onWall, startsIn, left, counting } = view;
                $('fcTag').hidden = !onWall || !round;

                if (!onWall || !round) {
                    setScene('idle');
                    return;
                }

                preview.style.setProperty('--ca', round.side_a_color || '#ef4444');
                preview.style.setProperty('--cb', round.side_b_color || '#3B82F6');
                root.querySelectorAll('[data-pv-brand]').forEach(node => setText(node, 'Fan Clash' + (round.category ? ' · ' + round.category : '')));

                const showTaps = checked('show_taps');

                if (stage === 'matchup') {
                    setScene('matchup');
                    $('fcPvQr').hidden = !checked('show_qr');
                } else if (stage === 'live' && counting) {
                    setScene('countdown');
                    setText($('fcPvCount'), String(Math.ceil(startsIn / 1000)));
                } else if (stage === 'live') {
                    setScene('live');
                    $('fcPvFoot').hidden = !checked('show_qr');
                    const total = round.side_a_taps + round.side_b_taps;
                    const share = total <= 0 ? .5 : Math.max(.04, Math.min(.96, round.side_a_taps / total));
                    $('fcPvFillA').style.width = (share * 100).toFixed(2) + '%';
                    $('fcPvFillB').style.width = (100 - share * 100).toFixed(2) + '%';
                    $('fcPvKnot').style.left = (share * 100).toFixed(2) + '%';
                    setText($('fcPvTapsA'), showTaps ? round.side_a_taps.toLocaleString() : '');
                    setText($('fcPvTapsB'), showTaps ? round.side_b_taps.toLocaleString() : '');
                    setText($('fcPvLeft'), String(Math.ceil((left ?? 0) / 1000)));
                } else {
                    setScene('result');
                    const name = winnerName(round);
                    setText($('fcPvEyebrow'), name ? 'Winner' : "It's a tie");
                    fitText($('fcPvWinner'), name || `${round.side_a_name} & ${round.side_b_name}`);
                    preview.style.setProperty('--cwin', round.winner_side === 'a' ? round.side_a_color : (round.winner_side === 'b' ? round.side_b_color : 'var(--cink)'));
                    const scoreLine = $('fcPvScore');
                    scoreLine.hidden = !showTaps;
                    setText(scoreLine.querySelector('.is-a'), round.side_a_taps.toLocaleString());
                    setText(scoreLine.querySelector('.is-b'), round.side_b_taps.toLocaleString());
                }

                const shown = root.querySelector(`[data-pv="${scene}"]`);
                const nameA = shown.querySelector('[data-pv-a]');
                const nameB = shown.querySelector('[data-pv-b]');
                if (nameA && nameB) fitNames(nameA, nameB, round.side_a_name, round.side_b_name);
            }

            /* Both side names share the smaller of their fitted sizes, as on the wall. */
            function fitNames(nodeA, nodeB, a, b) {
                const key = [preview.clientWidth, a, b].join('|');
                if (nodeA.parentElement.dataset.fit === key) return;
                nodeA.parentElement.dataset.fit = key;
                nodeA.dataset.fitWidth = '';
                nodeB.dataset.fitWidth = '';
                fitText(nodeA, a);
                fitText(nodeB, b);
                const size = Math.min(parseFloat(getComputedStyle(nodeA).fontSize), parseFloat(getComputedStyle(nodeB).fontSize));
                nodeA.style.fontSize = nodeB.style.fontSize = size + 'px';
            }

            function renderSeconds() {
                secondButtons.forEach(button => button.setAttribute('aria-pressed', Number(button.dataset.seconds) === seconds ? 'true' : 'false'));
            }

            function frame() {
                const view = current(performance.now());
                renderPanel(view);
                renderPreview(view);
                if (view.timeUp && !timeUpPolled) {
                    timeUpPolled = true;
                    schedulePoll(400);
                }
                requestAnimationFrame(frame);
            }

            function syncClock(next) {
                const round = next.round;
                if (next.stage !== 'live' || !round) {
                    startAt = null;
                    endAt = null;
                    return;
                }
                const nextEnd = performance.now() + round.starts_in_ms + round.remaining_ms;
                const nextStart = nextEnd - round.duration_ms;
                if (startAt === null || Math.abs(nextStart - startAt) > 400) startAt = nextStart;
                if (endAt === null || Math.abs(nextEnd - endAt) > 400) endAt = nextEnd;
            }

            function applyStatus(next, message) {
                if (next.revision < status.revision) return;
                const before = status.stage;
                status = next;
                syncClock(next);
                if (next.stage !== 'live') timeUpPolled = false;
                if (before !== 'ready' && next.stage === 'ready') reloadWhenIdle(message);
            }

            /* Back to the start reloads the page, so the rounds list and the next matchup are fresh. */
            function reloadWhenIdle(message) {
                const focused = document.activeElement;
                const typing = focused && ['INPUT', 'TEXTAREA', 'SELECT'].includes(focused.tagName) && focused.type !== 'submit';
                if (navigating) return;
                if (typing) {
                    setTimeout(() => reloadWhenIdle(message), 3000);
                    return;
                }
                navigating = true;
                try {
                    if (message) sessionStorage.setItem(TOAST_KEY, message);
                } catch (e) {}
                window.location.reload();
            }

            /* ── Server calls ── */
            function schedulePoll(ms) {
                clearTimeout(pollTimer);
                pollTimer = setTimeout(poll, ms);
            }

            /** A poll that was on its way while a button was pressed is dropped, so it cannot undo the press. */
            async function poll() {
                const mine = seq;
                try {
                    const res = await fetch(URLS.status, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                    if (res.ok && mine === seq && !busy) applyStatus(await res.json());
                } catch (e) {}
                schedulePoll(status.stage === 'ready' ? 3000 : 1000);
            }

            /** The pressed button spins until the answer is back, so a slow connection never looks like a dead button. */
            async function send(name, body = {}, button = main) {
                if (busy) return null;
                busy = true;
                pressed = button;
                seq++;
                let data = null;
                try {
                    const res = await fetch(URLS[name], {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify(body),
                    });
                    if (res.status === 419) {
                        showToast('Your session expired. Reload the page.', true);
                        return null;
                    }
                    data = await res.json().catch(() => ({}));
                    const message = data.message || (data.errors ? Object.values(data.errors)[0][0] : null);
                    if (data.revision !== undefined) applyStatus(data, res.ok ? message : null);
                    if (!navigating) showToast(message, !res.ok);
                    if (!res.ok) data = null;
                } catch (err) {
                    showToast('Could not reach the server. Try again.', true);
                } finally {
                    busy = false;
                    if (!navigating) pressed = null;
                    schedulePoll(1000);
                }
                return data;
            }

            main.addEventListener('click', async () => {
                const action = main.dataset.action;
                const view = current(performance.now());
                if (action === 'end' && !view.timeUp && !(await appConfirm('End the round now?', {
                    message: view.counting ? 'It has not started yet, so it ends as a tie.' : `It still has ${clock(view.left)} to go. Whoever leads now wins.`,
                    confirmLabel: 'End round',
                }))) {
                    return;
                }
                if (action === 'prepare') send('prepare', choiceBody());
                else if (action === 'start') send('start', { duration_seconds: seconds });
                else send(action);
            });

            extendBtn.addEventListener('click', () => send('extend', {}, extendBtn));
            wallBtn.addEventListener('click', () => send(wallBtn.getAttribute('aria-pressed') === 'true' ? 'hide' : 'show', {}, wallBtn));
            cancelBtn.addEventListener('click', () => send('cancel', {}, cancelBtn));

            if (select) {
                select.addEventListener('change', () => $('fcAdhoc').hidden = select.value !== '');
            }

            secondButtons.forEach(button => {
                button.addEventListener('click', () => {
                    seconds = Number(button.dataset.seconds);
                    renderSeconds();
                });
            });

            /* ── Screen options: saved a moment after each change ── */
            let saveTimer = null;
            let savedTimer = null;

            function clampInput(input) {
                const min = Number(input.min), max = Number(input.max);
                let value = parseInt(input.value, 10);
                if (Number.isNaN(value)) value = min;
                input.value = Math.max(min, Math.min(max, value));
            }

            async function saveSettings() {
                clearTimeout(saveTimer);
                try {
                    const res = await fetch(settings.action, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: new FormData(settings),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        showToast(data.message || 'The screen options could not be saved.', true);
                        return;
                    }
                    const saved = $('fcSaved');
                    saved.classList.add('is-shown');
                    clearTimeout(savedTimer);
                    savedTimer = setTimeout(() => saved.classList.remove('is-shown'), 1500);
                } catch (e) {
                    showToast('Could not reach the server. Try again.', true);
                }
            }

            function queueSave() {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(saveSettings, 600);
            }

            settings.addEventListener('submit', event => {
                event.preventDefault();
                saveSettings();
            });

            settings.querySelectorAll('.fc-stepper').forEach(stepper => {
                const input = stepper.querySelector('input');
                stepper.querySelectorAll('button[data-step]').forEach(button => {
                    button.addEventListener('click', () => {
                        input.value = (parseInt(input.value, 10) || 0) + Number(button.dataset.step);
                        clampInput(input);
                        queueSave();
                    });
                });
                input.addEventListener('change', () => {
                    clampInput(input);
                    queueSave();
                });
            });

            settings.querySelectorAll('input[type=checkbox]').forEach(input => input.addEventListener('change', queueSave));

            try {
                const message = sessionStorage.getItem(TOAST_KEY);
                if (message) {
                    sessionStorage.removeItem(TOAST_KEY);
                    showToast(message);
                }
            } catch (e) {}

            applyStatus(status);
            renderSeconds();
            requestAnimationFrame(frame);
            schedulePoll(1000);
        })();
    </script>
@endpush
