<style>
    .stat-sub {
        font-size: 11px;
        color: var(--muted);
        margin-top: 4px;
    }

    /* Bar charts: single hue, thin marks, 2px gaps, recessive chrome.
       Container grows to include the axis band so labels are never clipped. */
    .chart {
        width: 100%;
    }

    .chart-plot {
        display: flex;
        align-items: flex-end;
        gap: 2px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 1px;
    }

    .chart-col {
        flex: 1;
        height: 100%;
        display: flex;
        align-items: flex-end;
        min-width: 0;
    }

    .chart-bar {
        width: 100%;
        background: var(--red);
        border-radius: 4px 4px 0 0;
        opacity: .55;
        transition: opacity .15s;
        min-height: 0;
    }

    .chart-col:hover .chart-bar {
        opacity: 1;
    }

    .chart-bar.is-peak {
        opacity: 1;
    }

    .chart-axis {
        display: flex;
        gap: 2px;
        margin-top: 6px;
    }

    .chart-tick {
        flex: 1;
        min-width: 0;
        text-align: center;
        font-size: 10px;
        color: var(--muted);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    /* Timeline: two series on one axis, plotted across the event's own clock. */
    .ts {
        display: flex;
        gap: 10px;
    }

    .ts-axis-y {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 180px;
        min-width: 24px;
        text-align: right;
        font-size: 10px;
        color: var(--muted);
        font-variant-numeric: tabular-nums;
    }

    .ts-plot {
        flex: 1;
        min-width: 0;
    }

    .ts-svg {
        display: block;
        width: 100%;
        height: 180px;
        overflow: visible;
    }

    .ts-grid {
        stroke: var(--border);
    }

    .ts-line {
        fill: none;
        stroke-width: 2;
        stroke-linejoin: round;
        stroke-linecap: round;
    }

    .ts-band {
        fill: transparent;
    }

    .ts-band:hover {
        fill: rgba(255, 255, 255, .05);
    }

    .ts-axis-x {
        position: relative;
        height: 13px;
        margin-top: 8px;
        font-size: 10px;
        color: var(--muted);
        font-variant-numeric: tabular-nums;
    }

    .ts-axis-x span {
        position: absolute;
        transform: translateX(-50%);
        white-space: nowrap;
    }

    .ts-axis-x span:first-child {
        transform: none;
    }

    .ts-axis-x span:last-child {
        transform: translateX(-100%);
    }

    .chart-legend {
        display: flex;
        gap: 14px;
        font-size: 11px;
        color: var(--muted);
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .legend-swatch {
        width: 8px;
        height: 8px;
        border-radius: 2px;
    }

    .table-twin {
        margin-top: 14px;
    }

    .table-twin summary {
        cursor: pointer;
        font-size: 12px;
        color: var(--muted);
    }

    .table-twin summary:hover {
        color: var(--text);
    }

    /* Horizontal usage bars */
    .usage-row+.usage-row {
        margin-top: 14px;
    }

    .usage-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        font-size: 13px;
        margin-bottom: 6px;
    }

    .usage-figure {
        font-size: 12px;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .usage-track {
        height: 6px;
        background: var(--card2);
        border-radius: 4px;
        overflow: hidden;
    }

    .usage-fill {
        height: 100%;
        background: var(--red);
        border-radius: 4px;
    }

    .funnel-note {
        font-size: 11px;
        color: var(--muted);
        margin-top: 5px;
    }

    .fact-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        font-size: 13px;
        padding: 10px 0;
    }

    .fact-row+.fact-row {
        border-top: 1px solid var(--border);
    }

    .split-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 16px;
    }

    /* The layout's `.card + .card` rule would push the second column down. */
    .split-grid>.card+.card {
        margin-top: 0;
    }

    /* The card body already provides the padding below the mini stats. */
    .card-body>.stats-grid:last-child {
        margin-bottom: 0;
    }

    .sub-heading {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--muted);
        margin: 20px 0 8px;
    }

    .mini-stat {
        text-align: center;
        padding: 12px 8px;
        background: var(--card2);
        border-radius: 9px;
    }

    .mini-value {
        font-family: 'Syne', sans-serif;
        font-size: 24px;
        font-weight: 700;
        margin-top: 8px;
    }

    .empty-note {
        font-size: 13px;
        padding: 8px 0;
    }
</style>
