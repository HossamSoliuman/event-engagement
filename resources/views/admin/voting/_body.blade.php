{{-- Shared by the admin and moderator voting pages; $routePrefix picks the route set. --}}
@php
    $vtRoute = fn (string $name) => route($routePrefix . 'voting.' . $name, $event);
    $holdResult = (bool) old('hold_result', $screen->result_seconds === 0);
    $resultSeconds = old('result_seconds', $screen->result_seconds ?: 30);
    $showEveryone = (bool) old('show_everyone', $screen->top_count === 0);
    $topCount = old('top_count', $screen->top_count ?: 3);
    $settingChecked = fn (string $field) => (bool) old($field, $screen->{$field});
@endphp

@push('styles')
    <style>
        [hidden] { display: none !important; }
        .vt { --vt-accent: var(--mod, var(--red)); }

        .vt-deck { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); overflow: hidden; margin-bottom: 16px; }

        /* ── Preview of the wall ── */
        .vt-screen { position: relative; aspect-ratio: 16 / 9; overflow: hidden; border-right: 1px solid var(--border); background: var(--bg); background: radial-gradient(120% 90% at 50% 30%, color-mix(in srgb, var(--p) 22%, var(--bg)) 0%, var(--bg) 66%); color: var(--on-bg); container-type: inline-size; }
        .vt-pv { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2cqw; padding: 4cqw 5cqw; text-align: center; }
        .vt-pv-idle { background: #05050c; color: var(--muted); font-size: 13px; gap: 10px; }
        .vt-pv-idle .lucide-icon { width: 40px; height: 40px; stroke-width: 1.5px; opacity: .6; }
        .vt-pv-head { display: flex; flex-direction: column; align-items: center; gap: 1cqw; }
        .vt-pv-headline { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 3.2cqw; letter-spacing: .24em; text-transform: uppercase; color: var(--acc); }
        .vt-pv-chip { display: inline-flex; align-items: center; gap: .6em; padding: .3em 1em; border-radius: 999px; background: color-mix(in srgb, var(--on-bg) 10%, transparent); font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.7cqw; letter-spacing: .2em; text-transform: uppercase; }
        .vt-pv-chip i { width: .6em; height: .6em; border-radius: 50%; background: #ef4444; }
        .vt-pv-chip.is-final i { background: var(--acc); }
        .vt-pv-live { justify-content: flex-start; }
        .vt-pv-body { flex: 1; width: 100%; min-height: 0; display: flex; align-items: center; gap: 4cqw; }
        .vt-pv-bars { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1.6cqw; }
        .vt-pv-row { display: grid; grid-template-columns: 4.4cqw minmax(0, 1fr); align-items: center; gap: 1.6cqw; text-align: left; }
        .vt-pv-rank { display: flex; align-items: center; justify-content: center; width: 4.4cqw; height: 4.4cqw; border-radius: 50%; background: color-mix(in srgb, var(--on-bg) 12%, transparent); font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.8cqw; }
        .vt-pv-row.is-r1 .vt-pv-rank { background: linear-gradient(135deg, #FFE066, #E6B800); color: #1a1400; }
        .vt-pv-row.is-r2 .vt-pv-rank { background: linear-gradient(135deg, #E5E7EB, #9CA3AF); color: #111827; }
        .vt-pv-row.is-r3 .vt-pv-rank { background: linear-gradient(135deg, #F0B27A, #B8732E); color: #1f1206; }
        .vt-pv-line { display: flex; justify-content: space-between; gap: 1cqw; margin-bottom: .6cqw; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 2cqw; white-space: nowrap; }
        .vt-pv-line span:first-child { overflow: hidden; text-overflow: ellipsis; }
        .vt-pv-line span:last-child { font-variant-numeric: tabular-nums; }
        .vt-pv-row.is-r1 .vt-pv-line span:last-child { color: var(--acc); }
        .vt-pv-track { height: 1.2cqw; border-radius: 999px; background: color-mix(in srgb, var(--on-bg) 9%, transparent); overflow: hidden; }
        .vt-pv-fill { height: 100%; border-radius: inherit; background: var(--p); transition: width .8s cubic-bezier(.22, 1, .36, 1); }
        .vt-pv-row.is-r1 .vt-pv-fill { background: var(--acc); }
        .vt-pv-side { flex: 0 0 auto; width: 20cqw; display: flex; flex-direction: column; align-items: center; gap: 2cqw; }
        .vt-pv-timer small { display: block; font-size: 1.3cqw; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; opacity: .7; white-space: nowrap; }
        .vt-pv-timer strong { display: block; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 6.4cqw; line-height: 1; font-variant-numeric: tabular-nums; color: var(--acc); }
        .vt-pv-timer.is-ending strong { color: #ef4444; }
        .vt-pv-qr { width: 15cqw; aspect-ratio: 1; padding: 1cqw; border-radius: 1.4cqw; background: #fff; box-shadow: 0 0 0 .4cqw var(--acc); display: flex; align-items: center; justify-content: center; color: #111; }
        .vt-pv-qr img { width: 100%; height: 100%; object-fit: contain; }
        .vt-pv-qr .lucide-icon { width: 70%; height: 70%; }
        .vt-pv-big { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 13cqw; line-height: 1; font-variant-numeric: tabular-nums; color: var(--acc); }
        .vt-pv-eyebrow { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 2.4cqw; letter-spacing: .3em; text-transform: uppercase; color: var(--acc); }
        .vt-pv-name { max-width: 100%; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 6.4cqw; line-height: 1.1; overflow-wrap: anywhere; }
        .vt-pv-trophy .lucide-icon { width: 14cqw; height: 14cqw; color: var(--acc); }
        .vt-pv-podium { flex-direction: row; align-items: flex-end; gap: 1.6cqw; padding-bottom: 0; }
        .vt-pv-place { width: 22cqw; display: flex; flex-direction: column; align-items: center; gap: 1cqw; }
        .vt-pv-place span { max-width: 100%; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 2.2cqw; line-height: 1.15; overflow-wrap: anywhere; }
        .vt-pv-place i { display: block; width: 100%; border-radius: 1cqw 1cqw 0 0; background: linear-gradient(180deg, color-mix(in srgb, var(--p) 80%, #fff), var(--p)); }
        .vt-pv-place[data-place="1"] i { height: 30cqw; background: linear-gradient(180deg, color-mix(in srgb, var(--acc) 80%, #fff), var(--acc)); }
        .vt-pv-place[data-place="2"] i { height: 22cqw; }
        .vt-pv-place[data-place="3"] i { height: 15cqw; }
        .vt-screen-tag { position: absolute; top: 12px; left: 12px; padding: 4px 11px; border-radius: 30px; font-size: 10px; font-weight: 800; letter-spacing: 1.6px; background: var(--gold); color: #0A0A18; }

        /* ── Control panel ── */
        .vt-panel { display: flex; flex-direction: column; justify-content: center; gap: 16px; padding: 22px 24px; min-width: 0; }
        .vt-label { display: flex; align-items: center; gap: 8px; font-size: 10px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); }
        .vt-label-dot { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; animation: vtPing 1.6s infinite; }
        @keyframes vtPing { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, .6); } 80%, 100% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } }
        .vt[data-stage="voting"] .vt-label, .vt[data-stage="winner"] .vt-label, .vt[data-stage="podium"] .vt-label { color: var(--gold); }
        .vt-big { margin-top: 6px; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 44px; line-height: 1.05; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .vt-big.is-name { font-size: 28px; color: var(--gold); }
        .vt-big.is-ending { color: #ef4444; }
        .vt-meta { margin-top: 6px; font-size: 13px; color: var(--muted); }
        .vt-progress { height: 6px; border-radius: 6px; background: rgba(255, 255, 255, .08); overflow: hidden; }
        .vt-progress > div { height: 100%; width: 0; background: var(--gold); border-radius: 6px; }
        .vt-progress.is-ending > div { background: #ef4444; }

        .vt-transport { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .vt-main { display: inline-flex; align-items: center; justify-content: center; gap: 10px; height: 58px; padding: 0 28px; border: none; border-radius: 40px; background: var(--vt-accent); color: #fff; font: inherit; font-size: 16px; font-weight: 700; cursor: pointer; transition: filter .15s, opacity .15s, transform .1s; }
        .vt-main:hover:not(:disabled) { filter: brightness(1.1); }
        .vt-main:active:not(:disabled) { transform: scale(.97); }
        .vt-main:disabled { opacity: .35; cursor: default; }
        .vt-main .lucide-icon { width: 22px; height: 22px; }
        .vt-main [data-icon] { display: none; }
        .vt-main[data-action="start"] [data-icon="start"], .vt-main[data-action="end"] [data-icon="end"], .vt-main[data-action="reveal"] [data-icon="reveal"],
        .vt-main[data-action="podium"] [data-icon="podium"], .vt-main[data-action="finish"] [data-icon="finish"], .vt-main[data-action="new"] [data-icon="new"] { display: inline-flex; }
        .vt-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 46px; padding: 0 18px; border: 1px solid var(--border); border-radius: 30px; background: var(--card2); color: var(--text); font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s, border-color .15s, opacity .15s, transform .1s; }
        .vt-btn .lucide-icon { width: 16px; height: 16px; }
        .vt-btn:hover:not(:disabled) { border-color: var(--muted); }
        .vt-btn:active:not(:disabled) { transform: scale(.96); }
        .vt-btn:disabled { opacity: .35; cursor: default; }
        .vt-btn[aria-pressed] { color: var(--muted); }
        .vt-btn[aria-pressed="true"] { color: var(--text); border-color: var(--vt-accent); background: color-mix(in srgb, var(--vt-accent) 14%, var(--card2)); }
        .vt-hint { font-size: 13px; color: var(--gold); }

        .vt-setting { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border); }
        .vt-setting-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--muted); }
        .vt-setting-label .lucide-icon { width: 16px; height: 16px; }
        .vt-segments { display: inline-flex; flex-wrap: wrap; padding: 3px; gap: 3px; border: 1px solid var(--border); border-radius: 24px; background: var(--card2); }
        .vt-segments button { min-width: 44px; height: 34px; padding: 0 12px; border: none; border-radius: 30px; background: none; color: var(--muted); font: inherit; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; cursor: pointer; transition: color .15s, background .15s, box-shadow .15s; }
        .vt-segments button:hover:not([aria-pressed="true"]) { color: var(--text); background: rgba(255, 255, 255, .05); }
        .vt-segments button[aria-pressed="true"] { color: var(--text); background: color-mix(in srgb, var(--vt-accent) 22%, var(--card2)); box-shadow: inset 0 0 0 1px var(--vt-accent); cursor: default; }
        .vt-segments small { font-size: 11px; font-weight: 600; margin-left: 2px; }
        .vt-new { align-self: flex-start; display: inline-flex; align-items: center; gap: 6px; padding: 0; border: none; background: none; color: var(--muted); font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; }
        .vt-new:hover { color: #f87171; }
        .vt-new .lucide-icon { width: 14px; height: 14px; }

        /* ── Steps ── */
        .vt-steps { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border-top: 1px solid var(--border); }
        .vt-step { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 10px; font-size: 13px; font-weight: 600; color: var(--muted); }
        .vt-step + .vt-step { border-left: 1px solid var(--border); }
        .vt-step-num { display: flex; align-items: center; justify-content: center; flex: 0 0 auto; width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--border); font-size: 12px; font-weight: 800; }
        .vt-step.is-active { color: var(--text); background: color-mix(in srgb, var(--vt-accent) 10%, transparent); }
        .vt-step.is-active .vt-step-num { border-color: var(--vt-accent); background: var(--vt-accent); color: #fff; }
        .vt-step.is-done .vt-step-num { border-color: var(--gold); color: var(--gold); }

        /* ── Results and screen options ── */
        .vt-result { padding: 14px 20px; border-bottom: 1px solid var(--border); }
        .vt-result:last-child { border-bottom: none; }
        .vt-result-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
        .vt-result-who { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .vt-rank { flex: 0 0 auto; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 13px; background: rgba(255, 255, 255, .06); color: var(--text); }
        .vt-rank-1 { background: linear-gradient(135deg, #FFE066, #E6B800); color: #1a1400; }
        .vt-rank-2 { background: linear-gradient(135deg, #E5E7EB, #9CA3AF); color: #111827; }
        .vt-rank-3 { background: linear-gradient(135deg, #F0B27A, #B8732E); color: #1f1206; }
        .vt-result-name { font-weight: 700; font-size: 15px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vt-result-who > div:last-child { min-width: 0; }
        .vt-result-score { flex: 0 0 auto; text-align: right; }
        .vt-result-votes { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .vt-result.is-leader .vt-result-votes { color: var(--gold); }
        .vt-result .progress-bar { height: 10px; }
        .vt-empty { padding: 40px 20px; }

        .vt-options > summary { list-style: none; cursor: pointer; }
        .vt-options > summary::-webkit-details-marker { display: none; }
        .vt-options > summary h3 { display: flex; align-items: center; gap: 8px; }
        .vt-options-chevron { margin-left: auto; transition: transform .2s; }
        .vt-options[open] .vt-options-chevron { transform: rotate(180deg); }
        .vt-saved { font-size: 12px; font-weight: 600; color: #4ade80; opacity: 0; transition: opacity .2s; }
        .vt-saved.is-shown { opacity: 1; }
        .vt-timing { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
        .vt-field .form-label { margin-bottom: 8px; }
        .vt-stepper { display: inline-flex; align-items: center; height: 40px; border: 1px solid var(--border); border-radius: 30px; background: var(--card2); overflow: hidden; transition: border-color .15s, opacity .15s; }
        .vt-stepper:focus-within { border-color: var(--vt-accent); }
        .vt-stepper button { width: 38px; height: 100%; border: none; background: none; color: var(--muted); font-size: 18px; line-height: 1; cursor: pointer; }
        .vt-stepper button:hover:not(:disabled) { color: var(--text); background: rgba(255, 255, 255, .05); }
        .vt-stepper input { width: 50px; height: 100%; border: none; background: none; color: var(--text); font-size: 15px; font-weight: 700; text-align: center; font-variant-numeric: tabular-nums; -moz-appearance: textfield; appearance: textfield; }
        .vt-stepper input::-webkit-outer-spin-button, .vt-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .vt-stepper input:focus { outline: none; }
        .vt-stepper.is-off { opacity: .35; pointer-events: none; }
        .vt-unit { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }
        .vt-hold { display: flex; width: fit-content; align-items: center; gap: 7px; margin-top: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .vt-headline { max-width: 420px; }
        .vt-checks { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 24px; margin-top: 4px; }
        .vt-check { display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; }
        .vt-check input, .vt-hold input { width: 16px; height: 16px; accent-color: var(--gold); cursor: pointer; }

        .vt-toast { position: fixed; left: 50%; bottom: 26px; z-index: 300; transform: translate(-50%, 20px); padding: 11px 18px; border-radius: 10px; background: var(--card2); border: 1px solid var(--border); box-shadow: 0 16px 40px rgba(0, 0, 0, .45); font-size: 13px; font-weight: 600; opacity: 0; pointer-events: none; transition: opacity .2s, transform .2s; max-width: calc(100vw - 32px); }
        .vt-toast.is-shown { opacity: 1; transform: translate(-50%, 0); }
        .vt-toast.is-error { border-color: rgba(239, 68, 68, .45); color: #f87171; }

        @media (max-width: 900px) {
            .vt-deck { grid-template-columns: 1fr; }
            .vt-screen { border-right: none; border-bottom: 1px solid var(--border); }
            .vt-timing, .vt-checks { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .vt-panel { padding: 18px 16px; }
            .vt-main { width: 100%; }
            .vt-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .vt-step:nth-child(3) { border-left: none; }
            .vt-step:nth-child(n + 3) { border-top: 1px solid var(--border); }
        }
    </style>
@endpush

<div class="vt" id="vt" data-stage="ready">
    <div class="card vt-deck">
        <div class="vt-screen" id="vtScreen"
            style="--p: {{ $event->primary_color }}; --bg: {{ $event->secondary_color }}; --acc: {{ $event->accent_color }}; --on-bg: {{ $event->readableInk($event->secondary_color) }}">
            <div class="vt-pv vt-pv-idle" data-pv="idle">
                <i data-lucide="monitor-off" class="lucide-icon"></i>
                <span>The vote is not on the vidiwall.</span>
            </div>

            <div class="vt-pv vt-pv-live" data-pv="live" hidden>
                <div class="vt-pv-head">
                    <div class="vt-pv-headline" data-pv-headline></div>
                    <div class="vt-pv-chip" id="vtPvChip"><i></i><span></span></div>
                </div>
                <div class="vt-pv-body">
                    <div class="vt-pv-bars" id="vtPvBars"></div>
                    <div class="vt-pv-side" id="vtPvSide">
                        <div class="vt-pv-timer" id="vtPvTimer"><small>Voting closes in</small><strong></strong></div>
                        <div class="vt-pv-qr" id="vtPvQr">
                            @if ($event->qr_code_path)
                                <img src="{{ $event->qr_code_url }}" alt="QR code">
                            @else
                                <i data-lucide="qr-code" class="lucide-icon"></i>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="vt-pv" data-pv="intro" hidden>
                <div class="vt-pv-trophy"><i data-lucide="trophy" class="lucide-icon"></i></div>
                <div class="vt-pv-eyebrow">And the winner is…</div>
            </div>

            <div class="vt-pv" data-pv="countdown" hidden>
                <div class="vt-pv-big" id="vtPvCount"></div>
            </div>

            <div class="vt-pv" data-pv="winner" hidden>
                <div class="vt-pv-eyebrow" id="vtPvEyebrow">Winner</div>
                <div class="vt-pv-name" id="vtPvWinner"></div>
            </div>

            <div class="vt-pv vt-pv-podium" data-pv="podium" hidden>
                @foreach ([2, 1, 3] as $place)
                    <div class="vt-pv-place" data-place="{{ $place }}"><span></span><i></i></div>
                @endforeach
            </div>

            <span class="vt-screen-tag" id="vtTag" hidden>LIVE</span>
        </div>

        <div class="vt-panel">
            <div>
                <div class="vt-label"><span class="vt-label-dot" id="vtLabelDot" hidden></span><span id="vtLabel">Ready</span></div>
                <div class="vt-big" id="vtBig"></div>
                <div class="vt-meta" id="vtMeta"></div>
            </div>

            <div class="vt-progress" id="vtProgress" hidden><div id="vtBar"></div></div>

            <div class="vt-transport">
                <button type="button" class="vt-main" id="vtMain" data-action="start">
                    <span data-icon="start"><i data-lucide="play" class="lucide-icon"></i></span>
                    <span data-icon="end"><i data-lucide="square" class="lucide-icon"></i></span>
                    <span data-icon="reveal"><i data-lucide="trophy" class="lucide-icon"></i></span>
                    <span data-icon="podium"><i data-lucide="award" class="lucide-icon"></i></span>
                    <span data-icon="finish"><i data-lucide="check" class="lucide-icon"></i></span>
                    <span data-icon="new"><i data-lucide="rotate-ccw" class="lucide-icon"></i></span>
                    <span id="vtMainLabel">Start voting</span>
                </button>
                <button type="button" class="vt-btn" id="vtExtend" hidden><i data-lucide="plus" class="lucide-icon"></i> 1 min</button>
                <button type="button" class="vt-btn" id="vtWall" aria-pressed="false" hidden><i data-lucide="monitor" class="lucide-icon"></i> On the vidiwall</button>
                <button type="button" class="vt-btn" id="vtFinish" hidden><i data-lucide="check" class="lucide-icon"></i> Done</button>
            </div>

            <div class="vt-hint" id="vtHint" hidden></div>

            <div class="vt-setting" id="vtTimerSetting">
                <span class="vt-setting-label" id="vtTimerLabel"><i data-lucide="timer" class="lucide-icon"></i> Voting time</span>
                <div class="vt-segments" role="group" aria-labelledby="vtTimerLabel">
                    @foreach (\App\Models\VotingScreen::VOTING_MINUTES as $minutes)
                        <button type="button" data-minutes="{{ $minutes }}" aria-pressed="{{ $minutes === $screen->voting_minutes ? 'true' : 'false' }}">
                            @if ($minutes === 0)
                                No limit
                            @else
                                {{ $minutes }}<small>min</small>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <button type="button" class="vt-new" id="vtNew" hidden><i data-lucide="rotate-ccw" class="lucide-icon"></i> New vote</button>
        </div>

        <div class="vt-steps" id="vtSteps">
            @foreach (['Start', 'Voting', 'Winner', 'Podium'] as $index => $step)
                <div class="vt-step" data-flow-step="{{ $index }}"><span class="vt-step-num">{{ $index + 1 }}</span> {{ $step }}</div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i data-lucide="trophy" class="lucide-icon"></i> Live Results</h3>
        </div>

        <div id="vtResults">
            @forelse ($standings as $athlete)
                <div class="vt-result {{ $athlete['rank'] === 1 && $athlete['votes'] > 0 ? 'is-leader' : '' }}" data-key="{{ $athlete['key'] }}">
                    <div class="vt-result-head">
                        <div class="vt-result-who">
                            <div class="vt-rank vt-rank-{{ $athlete['votes'] > 0 ? min($athlete['rank'], 4) : 4 }}" data-rank>{{ $athlete['rank'] }}</div>
                            <div>
                                <div class="vt-result-name">{{ $athlete['name'] }}</div>
                                @if ($athlete['position'])
                                    <div class="text-muted text-xs">{{ $athlete['position'] }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="vt-result-score">
                            <div class="vt-result-votes" data-votes>{{ $athlete['votes'] }}</div>
                            <div class="text-muted text-xs"><span data-percent>{{ $athlete['percent'] }}</span>%</div>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill {{ $athlete['rank'] === 1 ? 'gold' : ($athlete['rank'] === 2 ? 'green' : '') }}" data-bar style="width:{{ $athlete['percent'] }}%"></div>
                    </div>
                </div>
            @empty
                <div class="empty-state vt-empty">
                    <div class="empty-icon"><i data-lucide="vote" class="lucide-icon" style="width:44px;height:44px"></i></div>
                    <h3>No athletes yet</h3>
                    <p>Add athletes to the vote in the event settings.</p>
                </div>
            @endforelse
        </div>
    </div>

    <details class="card mb-3 vt-options" id="vtOptions" @if ($errors->any()) open @endif>
        <summary class="card-header">
            <h3><i data-lucide="sliders-horizontal" class="lucide-icon"></i> Screen options <span class="vt-saved" id="vtSaved">Saved</span><i data-lucide="chevron-down" class="lucide-icon vt-options-chevron"></i></h3>
        </summary>
        <form method="POST" action="{{ $vtRoute('screen.update') }}" class="card-body" id="vtSettings">
            @csrf
            @method('PUT')

            <div class="vt-timing">
                <div class="vt-field">
                    <label class="form-label" for="vtTop">Live results show</label>
                    <div class="vt-stepper {{ $showEveryone ? 'is-off' : '' }}" id="vtTopStepper">
                        <button type="button" data-step="-1" aria-label="Fewer places">−</button>
                        <input type="number" id="vtTop" name="top_count" min="1" max="20" value="{{ $topCount }}" @disabled($showEveryone)>
                        <button type="button" data-step="1" aria-label="More places">+</button>
                    </div>
                    <label class="vt-hold">
                        <input type="hidden" name="show_everyone" value="0">
                        <input type="checkbox" name="show_everyone" value="1" id="vtEveryone" @checked($showEveryone)> Every athlete
                    </label>
                </div>
                <div class="vt-field">
                    <label class="form-label" for="vtCountdown">Countdown to the winner</label>
                    <div class="vt-stepper">
                        <button type="button" data-step="-1" aria-label="Shorter countdown">−</button>
                        <input type="number" id="vtCountdown" name="countdown_seconds" min="0" max="60" required value="{{ old('countdown_seconds', $screen->countdown_seconds) }}">
                        <button type="button" data-step="1" aria-label="Longer countdown">+</button>
                    </div>
                    <span class="vt-unit">seconds, 0 skips it</span>
                </div>
                <div class="vt-field">
                    <label class="form-label" for="vtResult">Winner on screen</label>
                    <div class="vt-stepper {{ $holdResult ? 'is-off' : '' }}" id="vtResultStepper">
                        <button type="button" data-step="-1" aria-label="Shorter winner time">−</button>
                        <input type="number" id="vtResult" name="result_seconds" min="3" max="600" value="{{ $resultSeconds }}" @disabled($holdResult)>
                        <button type="button" data-step="1" aria-label="Longer winner time">+</button>
                    </div>
                    <label class="vt-hold">
                        <input type="hidden" name="hold_result" value="0">
                        <input type="checkbox" name="hold_result" value="1" id="vtHold" @checked($holdResult)> Until I move on
                    </label>
                </div>
            </div>

            <div class="form-group vt-headline">
                <label class="form-label" for="vtHeadline">Headline</label>
                <input type="text" id="vtHeadline" name="headline" class="form-control" maxlength="60"
                    placeholder="{{ $event->voting_title ?: 'Fan Vote' }}" value="{{ old('headline', $screen->headline) }}">
            </div>

            <div class="vt-checks">
                @foreach ([
                    'show_votes' => 'Show the vote counts',
                    'show_percent' => 'Show percentages',
                    'show_total' => 'Show the total number of votes',
                    'show_position' => 'Show each athlete\'s position',
                    'show_qr' => 'Show the QR code while voting is open',
                    'confetti' => 'Confetti on the winner',
                ] as $field => $label)
                    <label class="vt-check">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1" id="vt-{{ $field }}" @checked($settingChecked($field))> {{ $label }}
                    </label>
                @endforeach
            </div>
        </form>
    </details>

    <div class="vt-toast" id="vtToast" role="status" aria-live="polite"></div>
</div>

@push('scripts')
    <script>
        (function () {
            const URLS = {
                status: @json($vtRoute('screen')),
                start: @json($vtRoute('start')),
                end: @json($vtRoute('close')),
                extend: @json($vtRoute('extend')),
                timer: @json($vtRoute('timer')),
                live: @json($vtRoute('screen.live')),
                hide: @json($vtRoute('screen.hide')),
                reveal: @json($vtRoute('screen.reveal')),
                podium: @json($vtRoute('screen.podium')),
                finish: @json($vtRoute('screen.finish')),
                new: @json($vtRoute('reset')),
            };
            const CSRF = document.querySelector('meta[name=csrf-token]').content;
            const PAGE_KEYS = @json(array_column($standings, 'key'));
            const STEP_OF = { ready: 0, voting: 1, closed: 1, winner: 2, podium: 3, done: 4 };
            const MAIN = {
                ready: ['start', 'Start voting'],
                voting: ['end', 'End voting'],
                closed: ['reveal', 'Reveal winner'],
                winner: ['podium', 'Show podium'],
                podium: ['finish', 'Done'],
                done: ['new', 'New vote'],
            };

            const $ = id => document.getElementById(id);
            const root = $('vt');
            const main = $('vtMain'), mainLabel = $('vtMainLabel');
            const extendBtn = $('vtExtend'), wallBtn = $('vtWall'), finishBtn = $('vtFinish'), newBtn = $('vtNew');
            const hint = $('vtHint'), toast = $('vtToast');
            const results = $('vtResults');
            const scenes = root.querySelectorAll('[data-pv]');
            const minuteButtons = [...root.querySelectorAll('[data-minutes]')];
            const settings = $('vtSettings');

            let status = @json($screenStatus);
            let origin = 0;
            let deadline = null;
            let longestTimer = 0;
            let busy = false;
            let seq = 0;
            let navigating = false;
            let pollTimer = null;
            let toastTimer = null;
            let scene = null;
            let previewKey = null;

            function esc(value) {
                return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            }

            function setText(node, text) {
                if (node.textContent !== text) node.textContent = text;
            }

            function clock(ms) {
                const total = Math.max(0, Math.ceil(ms / 1000));
                return Math.floor(total / 60) + ':' + String(total % 60).padStart(2, '0');
            }

            function votesText(total) {
                return `${total.toLocaleString()} ${total === 1 ? 'vote' : 'votes'}`;
            }

            function showToast(message, isError) {
                if (!message) return;
                toast.textContent = message;
                toast.classList.toggle('is-error', !!isError);
                toast.classList.add('is-shown');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toast.classList.remove('is-shown'), 3200);
            }

            function phaseOf(show, elapsed) {
                if (elapsed < 0) return 'intro';
                if (elapsed < show.countdown_ms) return 'countdown';
                if (show.result_ms === 0 || elapsed < show.countdown_ms + show.result_ms) return 'result';
                return null;
            }

            function leaders() {
                return status.standings.filter(athlete => athlete.rank === 1 && athlete.votes > 0).map(athlete => athlete.name);
            }

            function checked(field) {
                const box = $('vt-' + field);
                return box ? box.checked : true;
            }

            /* Everything the page shows, worked out from the last status and the clock. */
            function current(now) {
                const show = status.show;
                const elapsed = show ? now - origin : 0;
                const phase = show ? phaseOf(show, elapsed) : null;
                const left = deadline === null ? null : Math.max(0, deadline - now);
                let stage = status.stage;
                if (stage === 'voting' && left === 0) stage = 'closed';
                const onWall = !!phase && show.type === 'live';
                const revealPending = !!phase && show.type === 'reveal' && (phase === 'intro' || phase === 'countdown');

                return { show, elapsed, phase, left, stage, onWall, revealPending };
            }

            /* ── Control panel ── */
            function renderPanel(view) {
                const { show, phase, left, stage, onWall, revealPending } = view;
                const total = status.total;
                const athletes = status.standings.length;
                root.dataset.stage = stage;

                const [action, label] = MAIN[stage];
                main.dataset.action = action;
                setText(mainLabel, revealPending ? 'Revealing…' : label);

                let blockedBy = null;
                if (['start', 'reveal', 'podium'].includes(action) && status.blocked) blockedBy = status.blocked;
                else if (action === 'start' && athletes === 0) blockedBy = 'Add athletes to the vote in the event settings first.';
                else if (['reveal', 'podium'].includes(action) && total === 0) blockedBy = 'No votes yet, so there is no winner to reveal.';
                main.disabled = busy || navigating || !!blockedBy || (action === 'podium' && revealPending);
                hint.hidden = !blockedBy;
                setText(hint, blockedBy || '');

                extendBtn.hidden = stage !== 'voting' || left === null;
                wallBtn.hidden = stage !== 'voting' && stage !== 'closed';
                wallBtn.setAttribute('aria-pressed', onWall ? 'true' : 'false');
                finishBtn.hidden = stage !== 'winner';
                newBtn.hidden = stage === 'done' || (stage === 'ready' && total === 0);
                $('vtTimerSetting').hidden = stage !== 'ready';
                [extendBtn, wallBtn, finishBtn, newBtn].forEach(button => button.disabled = busy || navigating);
                if (!onWall && status.blocked) wallBtn.disabled = true;

                const big = $('vtBig');
                let labelText, bigText, metaText, isName = false, ending = false;
                const wallText = onWall ? 'on the vidiwall' : 'not on the vidiwall';

                if (stage === 'ready') {
                    labelText = 'Ready';
                    bigText = status.voting_minutes ? clock(status.voting_minutes * 60000) : 'No time limit';
                    metaText = `${athletes} ${athletes === 1 ? 'athlete' : 'athletes'} · ${votesText(total)} so far`;
                } else if (stage === 'voting') {
                    labelText = 'Voting open';
                    bigText = left !== null ? clock(left) : total.toLocaleString();
                    metaText = left !== null ? `${votesText(total)} · ${wallText}` : `${total === 1 ? 'vote' : 'votes'} · no time limit · ${wallText}`;
                    ending = left !== null && left <= 10000;
                } else if (stage === 'closed') {
                    labelText = 'Voting closed';
                    bigText = total.toLocaleString();
                    metaText = `${total === 1 ? 'vote' : 'votes'} · final results ${wallText}`;
                } else if (stage === 'winner') {
                    if (revealPending) {
                        labelText = 'Revealing the winner';
                        bigText = phase === 'intro' ? 'Get ready' : String(Math.max(1, Math.ceil((show.countdown_ms - view.elapsed) / 1000)));
                        metaText = 'On the vidiwall';
                    } else {
                        const names = show && show.type === 'reveal' ? show.winners : leaders();
                        labelText = names.length > 1 ? "It's a tie" : 'Winner';
                        bigText = names.join(' & ') || 'Winner';
                        isName = true;
                        metaText = phase ? 'On the vidiwall' : 'Back to the playlist';
                    }
                } else if (stage === 'podium') {
                    labelText = 'Podium';
                    bigText = 'Top 3';
                    metaText = phase ? 'On the vidiwall' : 'Back to the playlist';
                } else {
                    const names = leaders();
                    labelText = 'Finished';
                    bigText = names.join(' & ') || 'No votes';
                    isName = names.length > 0;
                    metaText = `${votesText(total)} · the playlist is back on the vidiwall`;
                }

                setText($('vtLabel'), labelText);
                $('vtLabelDot').hidden = stage !== 'voting';
                setText(big, bigText);
                big.classList.toggle('is-name', isName);
                big.classList.toggle('is-ending', ending);
                setText($('vtMeta'), metaText);

                const progress = $('vtProgress');
                let fraction = null;
                if (stage === 'voting' && left !== null) {
                    fraction = longestTimer ? left / longestTimer : 1;
                } else if (revealPending && phase === 'countdown' && show.countdown_ms) {
                    fraction = view.elapsed / show.countdown_ms;
                }
                progress.hidden = fraction === null;
                progress.classList.toggle('is-ending', ending);
                if (fraction !== null) $('vtBar').style.width = (Math.max(0, Math.min(1, fraction)) * 100) + '%';

                const active = STEP_OF[stage];
                root.querySelectorAll('[data-flow-step]').forEach(step => {
                    const index = Number(step.dataset.flowStep);
                    step.classList.toggle('is-active', index === active);
                    step.classList.toggle('is-done', index < active);
                    setText(step.querySelector('.vt-step-num'), index < active ? '✓' : String(index + 1));
                });
            }

            /* ── Preview of the wall ── */
            function setScene(name) {
                if (scene === name) return;
                scene = name;
                scenes.forEach(node => node.hidden = node.dataset.pv !== name);
            }

            function renderPreview(view) {
                const { show, phase, left, elapsed } = view;
                $('vtTag').hidden = !phase;

                if (!phase) {
                    setScene('idle');
                    return;
                }

                if (show.type === 'live') {
                    setScene('live');
                    const open = !status.voting_closed && left !== 0;
                    const timer = $('vtPvTimer');
                    timer.hidden = !open || left === null;
                    if (!timer.hidden) {
                        setText(timer.querySelector('strong'), clock(left));
                        timer.classList.toggle('is-ending', left <= 10000);
                    }
                    $('vtPvQr').hidden = !open || !checked('show_qr');
                    $('vtPvSide').hidden = timer.hidden && $('vtPvQr').hidden;
                    const chip = $('vtPvChip');
                    chip.classList.toggle('is-final', !open);
                    setText(chip.querySelector('span'), open ? 'Voting open' : 'Final results');
                } else if (show.type === 'podium') {
                    setScene('podium');
                } else if (phase === 'intro') {
                    setScene('intro');
                } else if (phase === 'countdown') {
                    setScene('countdown');
                    setText($('vtPvCount'), String(Math.max(1, Math.ceil((show.countdown_ms - elapsed) / 1000))));
                } else {
                    setScene('winner');
                }
            }

            /* The parts of the preview that only change with the status. */
            function buildPreview() {
                const headline = $('vtHeadline');
                root.querySelectorAll('[data-pv-headline]').forEach(node => setText(node, headline.value.trim() || headline.placeholder));

                const everyone = $('vtEveryone').checked;
                const limit = everyone ? Infinity : Number($('vtTop').value) || 3;
                const shown = status.standings.filter(athlete => athlete.rank <= limit).slice(0, 6);
                const leaderVotes = Math.max(0, ...status.standings.map(athlete => athlete.votes));
                const showVotes = checked('show_votes'), showPercent = checked('show_percent');
                const key = JSON.stringify([shown, leaderVotes, showVotes, showPercent]);
                if (key !== previewKey) {
                    previewKey = key;
                    $('vtPvBars').innerHTML = shown.map(athlete => {
                        const medal = athlete.votes > 0 && athlete.rank <= 3 ? ' is-r' + athlete.rank : '';
                        const value = showVotes ? athlete.votes.toLocaleString() : (showPercent ? athlete.percent + '%' : '');
                        const share = leaderVotes > 0 ? athlete.votes / leaderVotes * 100 : 0;
                        return `<div class="vt-pv-row${medal}"><div class="vt-pv-rank">${athlete.rank}</div><div>`
                            + `<div class="vt-pv-line"><span>${esc(athlete.name)}</span><span>${esc(value)}</span></div>`
                            + `<div class="vt-pv-track"><div class="vt-pv-fill" style="width:${share.toFixed(1)}%"></div></div></div></div>`;
                    }).join('');
                }

                const winners = status.show && status.show.type === 'reveal' ? status.show.winners : leaders();
                setText($('vtPvEyebrow'), winners.length > 1 ? "It's a tie" : 'Winner');
                setText($('vtPvWinner'), winners.join(' & '));

                root.querySelectorAll('.vt-pv-place').forEach(place => {
                    const names = status.standings
                        .filter(athlete => athlete.votes > 0 && athlete.rank === Number(place.dataset.place))
                        .map(athlete => athlete.name);
                    place.style.visibility = names.length ? '' : 'hidden';
                    setText(place.querySelector('span'), names.join(' & '));
                });
            }

            /* ── Results list ── */
            function renderResults() {
                const keys = status.standings.map(athlete => athlete.key);
                if (keys.length !== PAGE_KEYS.length || keys.some(key => !PAGE_KEYS.includes(key))) {
                    reloadWhenIdle();
                    return;
                }

                const rows = new Map(Array.from(results.querySelectorAll('[data-key]')).map(row => [row.dataset.key, row]));
                const inOrder = Array.from(results.children).map(row => row.dataset.key).join('|') === keys.join('|');

                status.standings.forEach(athlete => {
                    const row = rows.get(athlete.key);
                    const scored = athlete.votes > 0;
                    row.classList.toggle('is-leader', scored && athlete.rank === 1);
                    const rank = row.querySelector('[data-rank]');
                    rank.textContent = athlete.rank;
                    rank.className = 'vt-rank vt-rank-' + (scored ? Math.min(athlete.rank, 4) : 4);
                    row.querySelector('[data-votes]').textContent = athlete.votes;
                    row.querySelector('[data-percent]').textContent = athlete.percent;
                    const bar = row.querySelector('[data-bar]');
                    bar.style.width = athlete.percent + '%';
                    bar.className = 'progress-fill' + (athlete.rank === 1 ? ' gold' : (athlete.rank === 2 ? ' green' : ''));
                    if (!inOrder) results.appendChild(row);
                });
            }

            function renderMinutes(minutes) {
                minuteButtons.forEach(button => button.setAttribute('aria-pressed', Number(button.dataset.minutes) === minutes ? 'true' : 'false'));
            }

            function frame() {
                const view = current(performance.now());
                renderPanel(view);
                renderPreview(view);
                requestAnimationFrame(frame);
            }

            function applyStatus(next) {
                if (next.revision < status.revision) return;
                const now = performance.now();
                const nextOrigin = now - (next.show ? next.show.elapsed_ms : 0);
                if (next.revision !== status.revision || Math.abs(nextOrigin - origin) > 400) origin = nextOrigin;

                if (next.ends_in_ms === null || next.ends_in_ms === undefined) {
                    deadline = null;
                    longestTimer = 0;
                } else {
                    const at = now + next.ends_in_ms;
                    if (deadline === null || Math.abs(at - deadline) > 400) deadline = at;
                    longestTimer = Math.max(longestTimer, next.ends_in_ms, next.voting_minutes * 60000);
                }

                status = next;
                renderMinutes(next.voting_minutes);
                renderResults();
                buildPreview();
            }

            function reloadWhenIdle() {
                const focused = document.activeElement;
                const typing = focused && ['INPUT', 'TEXTAREA', 'SELECT'].includes(focused.tagName) && focused.type !== 'submit';
                if (navigating) return;
                if (typing) {
                    setTimeout(reloadWhenIdle, 3000);
                    return;
                }
                navigating = true;
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
                const active = status.show || ['voting', 'closed'].includes(status.stage);
                schedulePoll(active ? 1000 : 3000);
            }

            async function send(name, body = {}, method = 'POST') {
                if (busy) return null;
                busy = true;
                seq++;
                let data = null;
                try {
                    const res = await fetch(URLS[name], {
                        method,
                        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify(body),
                    });
                    if (res.status === 419) {
                        showToast('Your session expired. Reload the page.', true);
                        return null;
                    }
                    data = await res.json().catch(() => ({}));
                    if (data.revision !== undefined) applyStatus(data);
                    showToast(data.message, !res.ok);
                    if (!res.ok) data = null;
                } catch (err) {
                    showToast('Could not reach the server. Try again.', true);
                } finally {
                    busy = false;
                    schedulePoll(1000);
                }
                return data;
            }

            main.addEventListener('click', async () => {
                const action = main.dataset.action;
                const view = current(performance.now());
                if (action === 'end' && view.left !== null && view.left > 0
                    && !(await appConfirm('End voting now?', { message: `The timer still has ${clock(view.left)} left.`, confirmLabel: 'End voting' }))) {
                    return;
                }
                if (action === 'new' && !(await confirmNewVote())) {
                    return;
                }
                send(action);
            });

            extendBtn.addEventListener('click', () => send('extend'));
            wallBtn.addEventListener('click', () => send(wallBtn.getAttribute('aria-pressed') === 'true' ? 'hide' : 'live'));
            finishBtn.addEventListener('click', () => send('finish'));
            newBtn.addEventListener('click', async () => {
                if (await confirmNewVote()) send('new');
            });

            function confirmNewVote() {
                const total = status.total;
                return appConfirm('Start a new vote?', {
                    message: total ? `This deletes all ${votesText(total)} and cannot be undone.` : 'The vote goes back to the start.',
                    confirmLabel: total ? 'Delete votes and start over' : 'Start over',
                    danger: total > 0,
                });
            }

            minuteButtons.forEach(button => {
                button.addEventListener('click', async () => {
                    if (button.getAttribute('aria-pressed') === 'true') return;
                    const minutes = Number(button.dataset.minutes);
                    const before = status.voting_minutes;
                    renderMinutes(minutes);
                    status = { ...status, voting_minutes: minutes };
                    const data = await send('timer', { minutes });
                    if (!data) {
                        status = { ...status, voting_minutes: before };
                        renderMinutes(before);
                    }
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
                const body = new FormData(settings);
                try {
                    const res = await fetch(settings.action, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        showToast(data.message || 'The screen options could not be saved.', true);
                        return;
                    }
                    const saved = $('vtSaved');
                    saved.classList.add('is-shown');
                    clearTimeout(savedTimer);
                    savedTimer = setTimeout(() => saved.classList.remove('is-shown'), 1500);
                } catch (e) {
                    showToast('Could not reach the server. Try again.', true);
                }
            }

            function queueSave() {
                buildPreview();
                clearTimeout(saveTimer);
                saveTimer = setTimeout(saveSettings, 600);
            }

            settings.addEventListener('submit', event => {
                event.preventDefault();
                saveSettings();
            });

            settings.querySelectorAll('.vt-stepper').forEach(stepper => {
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

            [[$('vtHold'), $('vtResultStepper'), $('vtResult')], [$('vtEveryone'), $('vtTopStepper'), $('vtTop')]].forEach(([toggle, stepper, input]) => {
                const sync = () => {
                    stepper.classList.toggle('is-off', toggle.checked);
                    input.disabled = toggle.checked;
                };
                toggle.addEventListener('change', sync);
                sync();
            });

            settings.querySelectorAll('input[type=checkbox], #vtHeadline').forEach(input => input.addEventListener('change', queueSave));
            $('vtHeadline').addEventListener('input', buildPreview);

            applyStatus(status);
            requestAnimationFrame(frame);
            schedulePoll(1000);
        })();
    </script>
@endpush
