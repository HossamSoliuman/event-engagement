    @include('admin.statistics._styles')

    {{-- Headline numbers: the figure is the chart --}}
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));margin-bottom:20px">
        <div class="stat-card">
            <div class="stat-label">Page Openings</div>
            <div class="stat-value c-red">{{ number_format($stats['opens']['total']) }}</div>
            <div class="stat-sub">{{ number_format($stats['opens']['unique']) }} unique visitors</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Participation</div>
            <div class="stat-value c-green">{{ $stats['engagement']['rate'] }}%</div>
            <div class="stat-sub">{{ number_format($stats['engagement']['participants']) }} of
                {{ number_format($stats['engagement']['visitors']) }} took part</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Fotos &amp; Videos</div>
            <div class="stat-value c-gold">{{ number_format($stats['fotos']['total']) }}</div>
            <div class="stat-sub">{{ number_format($stats['fotos']['photos']) }} photos ·
                {{ number_format($stats['fotos']['videos']) }} videos</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Interactions</div>
            <div class="stat-value c-blue">{{ number_format($stats['engagement']['actions']) }}</div>
            <div class="stat-sub">across all modules</div>
        </div>
    </div>

    {{-- Openings by hour of day --}}
    @php $hourMax = max($stats['by_hour'] ?: [0]); @endphp
    <div class="card mb-3">
        <div class="card-header">
            <h3>Page Openings by Time of Day</h3>
            @if ($stats['peak']['hour'] !== null)
                <span class="text-muted" style="font-size:12px">Peak
                    {{ sprintf('%02d:00', $stats['peak']['hour']) }} · {{ $stats['peak']['count'] }} openings</span>
            @endif
        </div>
        <div class="card-body">
            @if ($hourMax === 0)
                <p class="text-muted empty-note">No page openings recorded in this period.</p>
            @else
                <div class="chart">
                    <div class="chart-plot" style="height:180px">
                        @foreach ($stats['by_hour'] as $hour => $count)
                            <div class="chart-col" title="{{ sprintf('%02d:00', $hour) }} — {{ $count }} openings">
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
                </div>

                {{-- Table twin: values never live only in a tooltip --}}
                <details class="table-twin">
                    <summary>View as table</summary>
                    <div class="table-wrap" style="margin-top:10px">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Hour</th>
                                    <th style="text-align:right">Openings</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stats['by_hour'] as $hour => $count)
                                    @if ($count > 0)
                                        <tr>
                                            <td>{{ sprintf('%02d:00', $hour) }}–{{ sprintf('%02d:59', $hour) }}</td>
                                            <td style="text-align:right;font-variant-numeric:tabular-nums">{{ $count }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </div>
    </div>

    {{-- Visits along the event's own clock, with the people behind them --}}
    @php
        $timeline = $stats['timeline'];
        $points = $timeline['points'];
        $pointCount = count($points);
        $timelineMax = max($timeline['peak'], 1);
        $peakPoint = collect($points)->sortByDesc('visits')->first();
    @endphp
    <div class="card mb-3">
        <div class="card-header">
            <h3>Visits Over Time</h3>
            <div class="chart-legend">
                <span class="legend-item"><i class="legend-swatch" style="background:var(--red)"></i> Visits</span>
                <span class="legend-item"><i class="legend-swatch" style="background:var(--blue)"></i> Unique
                    visitors</span>
            </div>
        </div>
        <div class="card-body">
            @if ($timeline['peak'] === 0)
                <p class="text-muted empty-note">No visits recorded while the event was running.</p>
            @else
                @php
                    $plotX = fn(int $index) => $pointCount > 1 ? round(($index / ($pointCount - 1)) * 1000, 2) : 500;
                    $plotY = fn(int $value) => round(200 - ($value / $timelineMax) * 190, 2);
                    $visitsLine = collect($points)->map(fn($point, $index) => $plotX($index) . ',' . $plotY($point['visits']))->implode(' ');
                    $visitorsLine = collect($points)->map(fn($point, $index) => $plotX($index) . ',' . $plotY($point['visitors']))->implode(' ');
                    $bandWidth = $pointCount > 1 ? 1000 / ($pointCount - 1) : 1000;
                    $spans = collect([4, 3, 2])->first(fn($divisions) => $pointCount - 1 >= $divisions && ($pointCount - 1) % $divisions === 0) ?? min(4, $pointCount - 1);
                    $tickIndices = collect(range(0, $spans))
                        ->map(fn($tick) => (int) round(($tick * ($pointCount - 1)) / max(1, $spans)))
                        ->unique()
                        ->values();
                @endphp
                <div class="ts">
                    <div class="ts-axis-y">
                        <span>{{ number_format($timelineMax) }}</span>
                        <span>{{ number_format(intdiv($timelineMax, 2)) }}</span>
                        <span>0</span>
                    </div>
                    <div class="ts-plot">
                        <svg class="ts-svg" viewBox="0 0 1000 200" preserveAspectRatio="none" role="img"
                            aria-label="Visits and unique visitors across the event timeline">
                            <defs>
                                {{-- var() resolves in style, not in presentation attributes --}}
                                <linearGradient id="tsVisitsFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" style="stop-color:var(--red);stop-opacity:.28" />
                                    <stop offset="100%" style="stop-color:var(--red);stop-opacity:0" />
                                </linearGradient>
                            </defs>

                            <line class="ts-grid" x1="0" y1="10" x2="1000" y2="10" vector-effect="non-scaling-stroke" />
                            <line class="ts-grid" x1="0" y1="105" x2="1000" y2="105"
                                vector-effect="non-scaling-stroke" />
                            <line class="ts-grid" x1="0" y1="200" x2="1000" y2="200"
                                vector-effect="non-scaling-stroke" />

                            <polygon fill="url(#tsVisitsFill)"
                                points="0,200 {{ $visitsLine }} {{ $plotX($pointCount - 1) }},200" />
                            <polyline class="ts-line" style="stroke:var(--red)" points="{{ $visitsLine }}"
                                vector-effect="non-scaling-stroke" />
                            <polyline class="ts-line" style="stroke:var(--blue)" points="{{ $visitorsLine }}"
                                vector-effect="non-scaling-stroke" />

                            @foreach ($points as $index => $point)
                                <rect class="ts-band" x="{{ max(0, $plotX($index) - $bandWidth / 2) }}" y="0"
                                    width="{{ $bandWidth }}" height="200">
                                    <title>{{ $point['label'] }} — {{ number_format($point['visits']) }} visits ·
                                        {{ number_format($point['visitors']) }} unique visitors</title>
                                </rect>
                            @endforeach
                        </svg>
                        {{-- Ticks sit at the x of the point they name, not at even flex intervals --}}
                        <div class="ts-axis-x">
                            @foreach ($tickIndices as $index)
                                <span style="left:{{ $plotX($index) / 10 }}%">{{ $points[$index]['tick'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($peakPoint)
                    <p class="text-muted" style="font-size:12px;margin-top:12px">
                        Busiest {{ $timeline['granularity'] === 'hour' ? 'hour' : 'day' }}:
                        {{ $peakPoint['label'] }} — {{ number_format($peakPoint['visits']) }} visits from
                        {{ number_format($peakPoint['visitors']) }} people.
                    </p>
                @endif

                {{-- Table twin: values never live only in a tooltip --}}
                <details class="table-twin">
                    <summary>View as table</summary>
                    <div class="table-wrap" style="margin-top:10px">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ $timeline['granularity'] === 'hour' ? 'Hour' : 'Day' }}</th>
                                    <th style="text-align:right">Visits</th>
                                    <th style="text-align:right">Unique visitors</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($points as $point)
                                    @if ($point['visits'] > 0)
                                        <tr>
                                            <td>{{ $point['label'] }}</td>
                                            <td style="text-align:right;font-variant-numeric:tabular-nums">
                                                {{ number_format($point['visits']) }}</td>
                                            <td style="text-align:right;font-variant-numeric:tabular-nums">
                                                {{ number_format($point['visitors']) }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </div>
    </div>

    <div class="split-grid mb-3">
        {{-- Module usage --}}
        <div class="card">
            <div class="card-header">
                <h3>Module Usage</h3>
            </div>
            <div class="card-body">
                @php $moduleMax = max(collect($stats['modules'])->pluck('actions')->toArray() ?: [0]); @endphp
                @foreach ($stats['modules'] as $module)
                    @if ($module['enabled'])
                        <div class="usage-row">
                            <div class="usage-head">
                                <span>{{ $module['label'] }}</span>
                                <span class="usage-figure">{{ number_format($module['actions']) }}
                                    <span class="text-muted">· {{ number_format($module['participants']) }} people ·
                                        {{ $module['rate'] }}%</span>
                                </span>
                            </div>
                            <div class="usage-track">
                                <div class="usage-fill"
                                    style="width:{{ $moduleMax > 0 && $module['actions'] > 0 ? max(1, round(($module['actions'] / $moduleMax) * 100)) : 0 }}%">
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
                @if (collect($stats['modules'])->where('enabled', true)->isEmpty())
                    <p class="text-muted empty-note">No modules are enabled for this event.</p>
                @endif
            </div>
        </div>

        {{-- How far the crowd went: every step is a subset of the one above it --}}
        @php
            $visitors = $stats['engagement']['visitors'];
            $share = fn(int $count) => $visitors > 0 ? round(($count / $visitors) * 100, 1) : 0.0;
            $funnel = [
                ['label' => 'Opened the guest page', 'count' => $visitors, 'note' => 'unique people, however often they came back'],
                ['label' => 'Took part', 'count' => $stats['engagement']['participants'], 'note' => 'used at least one module'],
                ['label' => 'Went deeper', 'count' => $stats['engagement']['multi_module'], 'note' => 'used two or more modules'],
            ];
        @endphp
        <div class="card">
            <div class="card-header">
                <h3>Engagement Funnel</h3>
                <span class="text-muted" style="font-size:12px">share of everyone who opened the page</span>
            </div>
            <div class="card-body">
                @if ($visitors === 0)
                    <p class="text-muted empty-note">No visitors yet, so there is nothing to break down.</p>
                @else
                    @foreach ($funnel as $step)
                        <div class="usage-row">
                            <div class="usage-head">
                                <span>{{ $step['label'] }}</span>
                                <span class="usage-figure">{{ number_format($step['count']) }}
                                    <span class="text-muted">· {{ $share($step['count']) }}%</span>
                                </span>
                            </div>
                            <div class="usage-track">
                                <div class="usage-fill"
                                    style="width:{{ $step['count'] > 0 ? max(1, $share($step['count'])) : 0 }}%"></div>
                            </div>
                            <div class="funnel-note">{{ $step['note'] }}</div>
                        </div>
                    @endforeach

                    <div class="sub-heading">Also worth knowing</div>
                    <div class="fact-row">
                        <span>Contact details captured</span>
                        <span class="usage-figure">{{ number_format($stats['engagement']['leads']) }}
                            <span class="text-muted">· {{ $share($stats['engagement']['leads']) }}% of
                                visitors</span>
                        </span>
                    </div>
                    <div class="fact-row">
                        <span>Came back for another look</span>
                        <span class="usage-figure">{{ number_format($stats['opens']['returning']) }}
                            <span class="text-muted">· {{ $share($stats['opens']['returning']) }}% of
                                visitors</span>
                        </span>
                    </div>
                    <div class="fact-row">
                        <span>Actions per participant</span>
                        <span class="usage-figure">
                            {{ $stats['engagement']['participants'] > 0 ? round($stats['engagement']['actions'] / $stats['engagement']['participants'], 1) : '0' }}
                            <span class="text-muted">· {{ number_format($stats['engagement']['actions']) }} in
                                total</span>
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Foto detail --}}
    <div class="card mb-3">
        <div class="card-header">
            <h3>Foto Bomb Detail</h3>
            <span class="text-muted" style="font-size:12px">{{ number_format($stats['fotos']['uploaders']) }} unique
                uploaders</span>
        </div>
        <div class="card-body">
            <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr))">
                <div class="mini-stat">
                    <span class="badge badge-approved">Approved</span>
                    <div class="mini-value">{{ number_format($stats['fotos']['approved']) }}</div>
                </div>
                <div class="mini-stat">
                    <span class="badge badge-pending">Pending</span>
                    <div class="mini-value">{{ number_format($stats['fotos']['pending']) }}</div>
                </div>
                <div class="mini-stat">
                    <span class="badge badge-rejected">Rejected</span>
                    <div class="mini-value">{{ number_format($stats['fotos']['rejected']) }}</div>
                </div>
                <div class="mini-stat">
                    <span class="badge badge-on-screen">On Screen</span>
                    <div class="mini-value">{{ number_format($stats['fotos']['shown_on_screen']) }}</div>
                </div>
            </div>
        </div>
    </div>
