@php
    $accent = $event->isLightColor($event->primary_color) ? '#111827' : ($event->primary_color ?: '#FF3D00');
    $hourMax = max($stats['by_hour'] ?: [0]);
    $activeModules = collect($stats['modules'])->where('enabled', true);
    $moduleMax = max($activeModules->pluck('actions')->toArray() ?: [0]);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Report — {{ $event->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent: {{ $accent }};
            --ink: #111827;
            --ink-soft: #4B5563;
            --ink-mute: #9CA3AF;
            --rule: #E5E7EB;
            --surface: #F9FAFB;
        }

        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--ink);
            background: #fff;
            font-size: 14px;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        h1, h2, h3 { font-family: 'Syne', sans-serif; }

        .sheet { max-width: 900px; margin: 0 auto; padding: 48px 40px 64px; }

        /* Masthead */
        .masthead {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            padding-bottom: 22px;
            border-bottom: 2px solid var(--accent);
            margin-bottom: 32px;
        }

        .masthead-logo { max-height: 56px; max-width: 220px; object-fit: contain; }

        .masthead h1 { font-size: 27px; font-weight: 800; letter-spacing: -.015em; }

        .masthead .kicker {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 7px;
        }

        .masthead .period { font-size: 12px; color: var(--ink-soft); margin-top: 6px; }
        .masthead-right { text-align: right; flex-shrink: 0; }

        /* Headline figures */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 34px;
        }

        .kpi {
            background: var(--surface);
            border-radius: 11px;
            padding: 18px 16px;
        }

        .kpi-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: var(--ink-mute);
            font-weight: 600;
            margin-bottom: 9px;
        }

        .kpi-value {
            font-family: 'Syne', sans-serif;
            font-size: 30px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: -.02em;
        }

        .kpi-sub { font-size: 11px; color: var(--ink-soft); margin-top: 7px; }

        section { margin-bottom: 32px; break-inside: avoid; }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 14px;
            gap: 16px;
        }

        .section-head h2 { font-size: 15px; font-weight: 700; }
        .section-head .note { font-size: 11px; color: var(--ink-soft); }

        /* Bar chart: single hue, thin marks, 2px gaps */
        .chart-plot {
            display: flex;
            align-items: flex-end;
            gap: 2px;
            height: 132px;
            border-bottom: 1px solid var(--rule);
            padding-bottom: 1px;
        }

        .chart-col { flex: 1; height: 100%; display: flex; align-items: flex-end; min-width: 0; }

        .chart-bar {
            width: 100%;
            background: var(--accent);
            border-radius: 3px 3px 0 0;
            opacity: .5;
        }

        .chart-bar.is-peak { opacity: 1; }

        .chart-axis { display: flex; gap: 2px; margin-top: 6px; }

        .chart-tick {
            flex: 1;
            min-width: 0;
            text-align: center;
            font-size: 9px;
            color: var(--ink-mute);
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        /* Tables */
        table { width: 100%; border-collapse: collapse; }

        th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--ink-mute);
            font-weight: 600;
            padding: 0 10px 8px;
            border-bottom: 1px solid var(--rule);
        }

        td {
            padding: 10px;
            border-bottom: 1px solid var(--rule);
            font-size: 13px;
        }

        tr:last-child td { border-bottom: none; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        th.num { text-align: right; }

        .bar-cell { width: 96px; }

        .bar-track {
            height: 5px;
            background: var(--rule);
            border-radius: 3px;
            overflow: hidden;
        }

        .bar-fill { height: 100%; background: var(--accent); border-radius: 3px; }

        .split { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; }

        .status-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }

        .status-cell {
            border: 1px solid var(--rule);
            border-radius: 9px;
            padding: 13px 12px;
            text-align: center;
        }

        .status-name { font-size: 11px; color: var(--ink-soft); font-weight: 500; }

        .status-figure {
            font-family: 'Syne', sans-serif;
            font-size: 21px;
            font-weight: 700;
            margin-top: 5px;
        }

        .empty { font-size: 13px; color: var(--ink-mute); padding: 14px 0; }

        footer {
            margin-top: 44px;
            padding-top: 16px;
            border-top: 1px solid var(--rule);
            font-size: 10px;
            color: var(--ink-mute);
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .print-bar {
            position: fixed;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
        }

        .print-btn {
            background: var(--ink);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 9px 16px;
            font-size: 13px;
            font-family: inherit;
            font-weight: 500;
            cursor: pointer;
        }

        @media print {
            .print-bar { display: none; }
            .sheet { padding: 0; max-width: none; }
            @page { margin: 14mm; }
            section { break-inside: avoid; }
        }

        @media (max-width: 640px) {
            .kpi-grid, .status-grid { grid-template-columns: repeat(2, 1fr); }
            .split { grid-template-columns: 1fr; }
            .sheet { padding: 28px 20px 40px; }
        }
    </style>
</head>

<body>
    <div class="print-bar">
        <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="sheet">
        <div class="masthead">
            <div>
                <div class="kicker">Event Engagement Report</div>
                <h1>{{ $event->name }}</h1>
                @if ($event->subtitle)
                    <div class="period">{{ $event->subtitle }}</div>
                @endif
                <div class="period">{{ $from->format('d M Y, H:i') }} — {{ $to->format('d M Y, H:i') }}</div>
            </div>
            <div class="masthead-right">
                @if ($event->logo_url)
                    <img src="{{ $event->logo_url }}" alt="{{ $event->name }}" class="masthead-logo">
                @endif
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi">
                <div class="kpi-label">Page Openings</div>
                <div class="kpi-value">{{ number_format($stats['opens']['total']) }}</div>
                <div class="kpi-sub">{{ number_format($stats['opens']['unique']) }} unique visitors</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Participation</div>
                <div class="kpi-value">{{ $stats['engagement']['rate'] }}%</div>
                <div class="kpi-sub">{{ number_format($stats['engagement']['participants']) }} guests took part</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Fotos &amp; Videos</div>
                <div class="kpi-value">{{ number_format($stats['fotos']['total']) }}</div>
                <div class="kpi-sub">{{ number_format($stats['fotos']['photos']) }} photos ·
                    {{ number_format($stats['fotos']['videos']) }} videos</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Interactions</div>
                <div class="kpi-value">{{ number_format($stats['engagement']['actions']) }}</div>
                <div class="kpi-sub">across all modules</div>
            </div>
        </div>

        <section>
            <div class="section-head">
                <h2>When guests opened the page</h2>
                @if ($stats['peak']['hour'] !== null)
                    <span class="note">Peak hour {{ sprintf('%02d:00', $stats['peak']['hour']) }} ·
                        {{ number_format($stats['peak']['count']) }} openings</span>
                @endif
            </div>
            @if ($hourMax === 0)
                <p class="empty">No page openings were recorded in this period.</p>
            @else
                <div class="chart-plot">
                    @foreach ($stats['by_hour'] as $hour => $count)
                        <div class="chart-col">
                            <div class="chart-bar {{ $hour === $stats['peak']['hour'] ? 'is-peak' : '' }}"
                                style="height:{{ $count > 0 ? max(2, round(($count / $hourMax) * 100)) : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="chart-axis">
                    @foreach ($stats['by_hour'] as $hour => $count)
                        <div class="chart-tick">{{ $hour % 3 === 0 ? sprintf('%02d', $hour) : '' }}</div>
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            <div class="section-head">
                <h2>Module usage</h2>
                <span class="note">Share = percentage of unique visitors who took part</span>
            </div>
            @if ($activeModules->isEmpty())
                <p class="empty">No modules were enabled for this event.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th class="bar-cell"></th>
                            <th class="num">Interactions</th>
                            <th class="num">People</th>
                            <th class="num">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activeModules as $module)
                            <tr>
                                <td>{{ $module['label'] }}</td>
                                <td class="bar-cell">
                                    <div class="bar-track">
                                        <div class="bar-fill"
                                            style="width:{{ $moduleMax > 0 && $module['actions'] > 0 ? max(2, round(($module['actions'] / $moduleMax) * 100)) : 0 }}%">
                                        </div>
                                    </div>
                                </td>
                                <td class="num">{{ number_format($module['actions']) }}</td>
                                <td class="num">{{ number_format($module['participants']) }}</td>
                                <td class="num">{{ $module['rate'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section>
            <div class="section-head">
                <h2>Foto Bomb breakdown</h2>
                <span class="note">{{ number_format($stats['fotos']['uploaders']) }} unique uploaders</span>
            </div>
            <div class="status-grid">
                <div class="status-cell">
                    <div class="status-name">Approved</div>
                    <div class="status-figure">{{ number_format($stats['fotos']['approved']) }}</div>
                </div>
                <div class="status-cell">
                    <div class="status-name">Pending</div>
                    <div class="status-figure">{{ number_format($stats['fotos']['pending']) }}</div>
                </div>
                <div class="status-cell">
                    <div class="status-name">Rejected</div>
                    <div class="status-figure">{{ number_format($stats['fotos']['rejected']) }}</div>
                </div>
                <div class="status-cell">
                    <div class="status-name">Shown on screen</div>
                    <div class="status-figure">{{ number_format($stats['fotos']['shown_on_screen']) }}</div>
                </div>
            </div>
        </section>

        <section>
            <div class="split">
                <div>
                    <div class="section-head">
                        <h2>Devices</h2>
                    </div>
                    @php $deviceTotal = $stats['devices']->sum(); @endphp
                    @if ($deviceTotal === 0)
                        <p class="empty">No device data recorded.</p>
                    @else
                        <table>
                            <tbody>
                                @foreach ($stats['devices'] as $label => $count)
                                    <tr>
                                        <td style="text-transform:capitalize">{{ $label }}</td>
                                        <td class="num">{{ number_format($count) }}</td>
                                        <td class="num">{{ round(($count / $deviceTotal) * 100) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
                <div>
                    <div class="section-head">
                        <h2>Reach summary</h2>
                    </div>
                    <table>
                        <tbody>
                            <tr>
                                <td>Total page openings</td>
                                <td class="num">{{ number_format($stats['opens']['total']) }}</td>
                            </tr>
                            <tr>
                                <td>Unique visitors</td>
                                <td class="num">{{ number_format($stats['opens']['unique']) }}</td>
                            </tr>
                            <tr>
                                <td>Returning visitors</td>
                                <td class="num">{{ number_format($stats['opens']['returning']) }}</td>
                            </tr>
                            <tr>
                                <td>Big-screen views</td>
                                <td class="num">{{ number_format($stats['opens']['vidiwall']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <footer>
            <span>{{ $event->name }} · Engagement report</span>
            <span>Generated {{ now()->format('d M Y, H:i') }}</span>
        </footer>
    </div>
</body>

</html>
