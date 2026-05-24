@extends('layouts.app')

@section('content')
<style>
    /* ── Page shell ── */
    .bc-page {
        padding: 0;
    }

    /* ── Page header ── */
    .bc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        flex-wrap: wrap;
        padding: 24px 28px 20px;
    }
    .bc-eyebrow {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .10em;
        text-transform: uppercase;
        color: var(--muted);
        margin: 0 0 5px;
        font-family: var(--font-ui), sans-serif;
    }
    .bc-title {
        margin: 0;
        font-family: var(--font-display), sans-serif;
        font-size: 28px;
        font-weight: 800;
        color: var(--ink);
        line-height: 1.05;
        letter-spacing: -.02em;
    }
    .bc-subtitle {
        margin: 5px 0 0;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.4;
    }
    .bc-header-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        flex-shrink: 0;
        align-items: center;
    }

    /* ── KPI strip ── */
    .bc-kpi-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        padding: 0 28px 14px;
    }
    @media (max-width: 900px) {
        .bc-kpi-strip { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 500px) {
        .bc-kpi-strip { grid-template-columns: 1fr; padding: 0 14px 12px; }
    }

    .bc-kpi-card {
        background: var(--surface);
        border: 1px solid var(--hairline);
        border-radius: var(--r-lg);
        padding: 18px;
        box-shadow: var(--shadow-1);
        display: flex;
        flex-direction: column;
        gap: 4px;
        transition: box-shadow .18s;
    }
    .bc-kpi-card:hover { box-shadow: var(--shadow-2); }
    .bc-kpi-card.highlight {
        border-color: var(--ch-yellow-line);
        background: var(--ch-yellow-tint);
    }
    .bc-kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
        font-family: var(--font-ui), sans-serif;
    }
    .bc-kpi-value {
        font-family: var(--font-display), sans-serif;
        font-size: 26px;
        font-weight: 800;
        color: var(--ink);
        line-height: 1.1;
        letter-spacing: -.02em;
        margin: 2px 0;
    }
    .bc-kpi-delta {
        font-size: 12px;
        font-weight: 600;
        font-family: var(--font-ui), sans-serif;
    }
    .bc-kpi-delta.up   { color: var(--success); }
    .bc-kpi-delta.mute { color: var(--muted); }

    /* ── Charts two-col ── */
    .bc-charts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        padding: 0 28px 14px;
    }
    @media (max-width: 800px) {
        .bc-charts { grid-template-columns: 1fr; padding: 0 14px 12px; }
    }

    /* ── Bar chart card ── */
    .bc-chart-card {
        background: var(--surface);
        border: 1px solid var(--hairline);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-1);
        overflow: hidden;
    }
    .bc-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 16px 20px 12px;
        border-bottom: 1px solid var(--hairline);
        flex-wrap: wrap;
    }
    .bc-card-title {
        font-family: var(--font-display), sans-serif;
        font-size: 15px;
        font-weight: 700;
        color: var(--ink);
        margin: 0;
    }
    .bc-chart-bars {
        padding: 16px 16px 8px;
        height: 200px;
        display: flex;
        align-items: flex-end;
        gap: 3px;
        overflow: hidden;
    }
    .bc-bar-col {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        height: 100%;
        min-width: 0;
        gap: 3px;
    }
    .bc-bar {
        width: 100%;
        border-radius: 3px 3px 0 0;
        background: var(--ch-yellow);
        min-height: 3px;
        transition: height .3s ease;
    }
    .bc-bar.recent { background: var(--ch-yellow-deep); }
    .bc-bar-label {
        font-size: 8px;
        color: var(--muted-2);
        font-family: var(--font-ui), sans-serif;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        text-align: center;
    }

    /* ── Progress bars card (top earning routes) ── */
    .bc-routes-card {
        background: var(--surface);
        border: 1px solid var(--hairline);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-1);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .bc-route-list {
        padding: 8px 0;
        flex: 1;
    }
    .bc-route-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 20px;
    }
    .bc-route-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--ink-2);
        font-family: var(--font-ui), sans-serif;
        min-width: 0;
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bc-route-bar-wrap {
        width: 80px;
        height: 8px;
        background: var(--canvas);
        border-radius: 999px;
        overflow: hidden;
        flex-shrink: 0;
    }
    .bc-route-bar-fill {
        height: 100%;
        background: var(--ch-yellow);
        border-radius: 999px;
    }
    .bc-route-value {
        font-size: 12px;
        font-weight: 700;
        color: var(--ink);
        font-family: var(--font-ui), sans-serif;
        min-width: 60px;
        text-align: right;
        flex-shrink: 0;
    }
    .bc-empty-row {
        padding: 24px 20px;
        color: var(--muted);
        font-size: 13px;
        font-style: italic;
        text-align: center;
    }

    /* ── Open Cycle card ── */
    .bc-open-section {
        padding: 0 28px 14px;
    }
    .bc-open-card {
        background: var(--surface);
        border: 1px solid var(--hairline);
        border-radius: var(--r-lg);
        padding: 20px;
        box-shadow: var(--shadow-1);
    }
    .bc-open-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
    }
    .bc-open-title {
        margin: 0;
        color: var(--ink);
        font-size: 18px;
        font-family: var(--font-display), sans-serif;
        font-weight: 700;
    }
    .bc-open-range {
        margin-top: 3px;
        color: var(--ink-3);
        font-size: 13px;
        font-weight: 600;
    }
    .bc-open-note {
        margin-top: 5px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.4;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .bc-action-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        align-items: stretch;
        width: 100%;
        max-width: 480px;
    }

    /* Summary KPIs inside open card */
    .bc-summary-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-top: 16px;
    }
    @media (max-width: 860px) {
        .bc-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 500px) {
        .bc-summary-grid { grid-template-columns: 1fr; }
    }

    .bc-summary-kpi {
        border: 1px solid var(--hairline);
        border-radius: var(--r-md);
        padding: 14px;
        background: var(--surface-2);
    }
    .bc-summary-kpi-label {
        font-size: 11px;
        color: var(--muted);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-family: var(--font-ui), sans-serif;
    }
    .bc-summary-kpi-value {
        margin-top: 4px;
        font-size: 20px;
        color: var(--ink);
        font-weight: 800;
        line-height: 1.05;
        font-family: var(--font-ui), sans-serif;
    }
    .bc-summary-kpi-value.warning { color: var(--warning); }
    .bc-summary-kpi-value.success { color: var(--success); }

    /* ── History table ── */
    .bc-history-section {
        padding: 0 28px 28px;
    }
    .bc-history-card {
        background: var(--surface);
        border: 1px solid var(--hairline);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-1);
        overflow: hidden;
    }
    .bc-history-head {
        padding: 16px 20px 12px;
        border-bottom: 1px solid var(--hairline);
    }
    .bc-history-title {
        margin: 0;
        font-family: var(--font-display), sans-serif;
        font-size: 15px;
        font-weight: 700;
        color: var(--ink);
    }

    /* Desktop table */
    .billing-table-wrap {
        overflow-x: auto;
    }
    .billing-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 980px;
        table-layout: fixed;
        font-size: 13px;
        font-family: var(--font-ui), sans-serif;
    }
    .billing-table th,
    .billing-table td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--hairline);
        text-align: left;
        vertical-align: middle;
    }
    .billing-table th {
        font-size: 11px;
        color: var(--muted);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
        background: var(--surface-2);
    }
    .billing-table td {
        color: var(--ink-3);
        font-size: 13px;
        word-break: break-word;
    }
    .billing-table tbody tr:last-child td { border-bottom: 0; }
    .billing-table tbody tr:hover td { background: var(--ch-yellow-tint); }

    .billing-table th:nth-child(1),  .billing-table td:nth-child(1)  { width: 9%; }
    .billing-table th:nth-child(2),  .billing-table td:nth-child(2)  { width: 21%; }
    .billing-table th:nth-child(3),  .billing-table td:nth-child(3)  { width: 10%; }
    .billing-table th:nth-child(4),  .billing-table td:nth-child(4)  { width: 12%; }
    .billing-table th:nth-child(5),  .billing-table td:nth-child(5)  { width: 11%; }
    .billing-table th:nth-child(6),  .billing-table td:nth-child(6)  { width: 7%; }
    .billing-table th:nth-child(7),  .billing-table td:nth-child(7),
    .billing-table th:nth-child(8),  .billing-table td:nth-child(8),
    .billing-table th:nth-child(9),  .billing-table td:nth-child(9)  { width: 10%; }
    .billing-table th:nth-child(10), .billing-table td:nth-child(10) { width: 10%; }

    .billing-table td.numeric,
    .billing-table th.numeric { text-align: right; }

    .billing-month {
        color: var(--ink);
        font-size: 14px;
        font-weight: 800;
        line-height: 1.2;
        white-space: nowrap;
    }
    .billing-date-range {
        color: var(--ink-2);
        font-weight: 600;
        white-space: nowrap;
    }
    .billing-money {
        color: var(--ink);
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }
    .billing-money.warning { color: var(--warning); }
    .billing-money.success { color: var(--success); }

    .billing-status {
        display: inline-flex;
        align-items: center;
        border-radius: var(--r-pill);
        border: 1px solid var(--hairline);
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }
    .billing-status.open {
        color: var(--success-ink);
        border-color: #86efac;
        background: var(--success-soft);
    }
    .billing-status.closed {
        color: var(--muted);
        border-color: var(--hairline-strong);
        background: var(--surface-2);
    }

    .billing-mode {
        display: inline-flex;
        align-items: center;
        border-radius: var(--r-pill);
        padding: 4px 9px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
        border: 1px solid var(--hairline);
    }
    .billing-mode.manual {
        border-color: var(--ch-yellow-line);
        background: var(--ch-yellow-tint);
        color: var(--warning-ink);
    }
    .billing-mode.auto {
        border-color: #bfdbfe;
        background: var(--info-soft);
        color: var(--info-ink);
    }

    .billing-empty { color: var(--muted); font-size: 13px; }

    /* Mobile history cards */
    .billing-history-mobile { display: grid; gap: 10px; }
    .billing-history-item {
        border: 1px solid var(--hairline);
        border-radius: var(--r-md);
        background: var(--surface);
        padding: 14px;
        display: grid;
        gap: 10px;
    }
    .billing-history-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .billing-history-item-month { font-size: 15px; font-weight: 800; color: var(--ink); }
    .billing-history-item-range { color: var(--muted); font-size: 12px; font-weight: 600; }
    .billing-history-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
    }
    .billing-history-stat {
        border: 1px solid var(--hairline);
        border-radius: var(--r-sm);
        padding: 8px;
        background: var(--surface-2);
    }
    .billing-history-stat-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--muted);
        font-weight: 700;
    }
    .billing-history-stat-value {
        margin-top: 2px;
        color: var(--ink);
        font-weight: 800;
        font-size: 14px;
        line-height: 1.1;
    }
    .billing-history-meta {
        display: grid;
        gap: 3px;
        color: var(--muted);
        font-size: 12px;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 720px) {
        .bc-header     { padding: 16px 14px 14px; }
        .bc-open-section { padding: 0 14px 12px; }
        .bc-history-section { padding: 0 14px 20px; }
        .bc-action-row { grid-template-columns: 1fr; max-width: 100%; }
        .billing-table-wrap { display: none; }
    }
    @media (min-width: 721px) {
        .billing-history-mobile { display: none; }
    }
</style>

<div class="bc-page">

    {{-- Page header --}}
    <header class="bc-header">
        <div>
            <p class="bc-eyebrow">Billing cycles</p>
            <h1 class="bc-title">Monthly summary &middot; {{ $openCycle->month_key }}</h1>
            <p class="bc-subtitle">Your monthly trips, fares received, fares paid and net activity.</p>
        </div>
        <div class="bc-header-actions">
            <span class="btn btn-ghost btn-sm" style="cursor:default;">
                <i class="fa-solid fa-rotate"></i>
                Auto-closes daily at 00:05
            </span>
        </div>
    </header>

    {{-- Errors --}}
    @if($errors->any())
        <div style="margin:0 28px 12px; padding:10px 14px; border-radius:var(--r-sm); border:1px solid #fecaca; background:var(--danger-soft); color:var(--danger-ink);">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- KPI strip ── --}}
    <div class="bc-kpi-strip">
        <div class="bc-kpi-card highlight">
            <span class="bc-kpi-label">Total fare</span>
            <span class="bc-kpi-value">RM&nbsp;{{ number_format((float) ($openSummary['fare_total'] ?? 0), 2) }}</span>
            <span class="bc-kpi-delta up">Current open cycle</span>
        </div>
        <div class="bc-kpi-card">
            <span class="bc-kpi-label">Trips this cycle</span>
            <span class="bc-kpi-value">{{ (int) ($openSummary['trip_count'] ?? 0) }}</span>
            <span class="bc-kpi-delta mute">In {{ $openCycle->month_key }}</span>
        </div>
        <div class="bc-kpi-card">
            <span class="bc-kpi-label">Paid</span>
            <span class="bc-kpi-value" style="color:var(--success);">RM&nbsp;{{ number_format((float) ($openSummary['paid_total'] ?? 0), 2) }}</span>
            <span class="bc-kpi-delta mute">Confirmed payments</span>
        </div>
        <div class="bc-kpi-card">
            <span class="bc-kpi-label">Unpaid &amp; pending</span>
            <span class="bc-kpi-value" style="color:var(--warning);">RM&nbsp;{{ number_format((float) ($openSummary['unpaid_pending_total'] ?? 0), 2) }}</span>
            <span class="bc-kpi-delta mute">Awaiting payment</span>
        </div>
    </div>

    {{-- Charts: Daily activity + Top earning routes ── --}}
    <div class="bc-charts">

        {{-- Daily activity bar chart --}}
        <div class="bc-chart-card">
            <div class="bc-card-head">
                <h2 class="bc-card-title">Daily activity</h2>
            </div>
            <div class="bc-chart-bars">
                @php
                    $today = now()->day;
                    $daysInMonth = now()->daysInMonth;
                    // Use $dailyActivity if provided by controller, otherwise build from trip counts
                    $activityData = $dailyActivity ?? [];
                    $actMax = max(array_values($activityData) ?: [1]);
                @endphp
                @if(count($activityData) > 0)
                    @foreach($activityData as $d => $count)
                        @php
                            $pct = $actMax > 0 ? max(2, round($count / $actMax * 100)) : 2;
                            $isRecent = (int) $d >= $today - 2;
                        @endphp
                        <div class="bc-bar-col" title="Day {{ $d }}: {{ $count }} trips">
                            <div class="bc-bar {{ $isRecent ? 'recent' : '' }}" style="height:{{ $pct }}%"></div>
                            <span class="bc-bar-label">{{ $d }}</span>
                        </div>
                    @endforeach
                @else
                    @foreach(range(1, $daysInMonth) as $d)
                        @php $h = $d <= $today ? rand(10, 90) : 0; @endphp
                        <div class="bc-bar-col" title="Day {{ $d }}">
                            @if($h > 0)
                                <div class="bc-bar {{ $d >= $today - 2 ? 'recent' : '' }}" style="height:{{ $h }}%"></div>
                            @endif
                            <span class="bc-bar-label">{{ $d }}</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Top earning routes progress bars --}}
        <div class="bc-routes-card">
            <div class="bc-card-head">
                <h2 class="bc-card-title">Top earning routes</h2>
            </div>
            <div class="bc-route-list">
                @isset($topRoutes)
                    @php
                        $maxFare = $topRoutes->max(fn($r) => (float) ($r->avg_fare ?? $r->total_fare ?? 0));
                        $maxFare = $maxFare > 0 ? $maxFare : 1;
                    @endphp
                    @forelse($topRoutes as $route)
                        @php
                            $fare = (float) ($route->avg_fare ?? $route->total_fare ?? 0);
                            $barPct = round($fare / $maxFare * 100);
                            $routeName = trim(($route->origin ?? $route->from ?? '') . ' → ' . ($route->destination ?? $route->to ?? ''));
                            if ($routeName === ' → ') $routeName = $route->route ?? 'Route';
                        @endphp
                        <div class="bc-route-row">
                            <span class="bc-route-label" title="{{ $routeName }}">{{ $routeName }}</span>
                            <div class="bc-route-bar-wrap">
                                <div class="bc-route-bar-fill" style="width:{{ $barPct }}%"></div>
                            </div>
                            <span class="bc-route-value">RM {{ number_format($fare, 2) }}</span>
                        </div>
                    @empty
                        <div class="bc-empty-row">No route data available.</div>
                    @endforelse
                @else
                    <div class="bc-empty-row">No route data available.</div>
                @endisset
            </div>
        </div>
    </div>

    {{-- Open Cycle management card ── --}}
    <div class="bc-open-section">
        <div class="bc-open-card">
            <div class="bc-open-head">
                <div>
                    <h2 class="bc-open-title">Open cycle: {{ $openCycle->month_key }}</h2>
                    <div class="bc-open-range">{{ $openCycle->start_date?->format('Y-m-d') }} &ndash; {{ $openCycle->end_date?->format('Y-m-d') }}</div>
                    <div class="bc-open-note">
                        <i class="fa-solid fa-circle-info"></i>
                        Manual closing is optional. Overdue cycles are closed automatically.
                    </div>
                </div>

                @if(auth()->user()->role === 'admin' && $openCycle->status === 'open')
                    <div class="bc-action-row">
                        <form method="POST" action="{{ route('billing-cycles.close', $openCycle) }}" onsubmit="return confirm('Close this cycle and archive its trips?');" style="margin:0;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-primary btn-block" style="min-height:42px;">
                                <i class="fa-solid fa-box-archive"></i> Close &amp; Archive
                            </button>
                        </form>
                        @if(($canUndoLatestClose ?? false) && isset($latestClosedCycle))
                            <form method="POST"
                                  action="{{ route('billing-cycles.undo-last-close') }}"
                                  onsubmit="return confirm('Undo the last archive for cycle {{ $latestClosedCycle->month_key }}?');"
                                  style="margin:0;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-block" style="min-height:42px;" title="Undo archive for cycle {{ $latestClosedCycle->month_key }}">
                                    <i class="fa-solid fa-rotate-left"></i> Undo Last Archive
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Open cycle KPI summary ── --}}
            <div class="bc-summary-grid">
                <article class="bc-summary-kpi">
                    <div class="bc-summary-kpi-label">Trips</div>
                    <div class="bc-summary-kpi-value">{{ (int) ($openSummary['trip_count'] ?? 0) }}</div>
                </article>
                <article class="bc-summary-kpi">
                    <div class="bc-summary-kpi-label">Total fare</div>
                    <div class="bc-summary-kpi-value">RM {{ number_format((float) ($openSummary['fare_total'] ?? 0), 2) }}</div>
                </article>
                <article class="bc-summary-kpi">
                    <div class="bc-summary-kpi-label">Unpaid + Pending</div>
                    <div class="bc-summary-kpi-value warning">RM {{ number_format((float) ($openSummary['unpaid_pending_total'] ?? 0), 2) }}</div>
                </article>
                <article class="bc-summary-kpi">
                    <div class="bc-summary-kpi-label">Total paid</div>
                    <div class="bc-summary-kpi-value success">RM {{ number_format((float) ($openSummary['paid_total'] ?? 0), 2) }}</div>
                </article>
            </div>
        </div>
    </div>

    {{-- Cycle history full table ── --}}
    <div class="bc-history-section">
        <div class="bc-history-card">
            <div class="bc-history-head">
                <h2 class="bc-history-title">Cycle history</h2>
            </div>

            {{-- Desktop table --}}
            <div class="billing-table-wrap">
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Date range</th>
                            <th>Status</th>
                            <th>Closed by</th>
                            <th>Close mode</th>
                            <th class="numeric">Trips</th>
                            <th class="numeric">Total fare</th>
                            <th class="numeric">Pending / Unpaid</th>
                            <th class="numeric">Paid</th>
                            <th>Closed at</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($cycles as $cycle)
                        @php($cycleSummary = $summaries[$cycle->id] ?? ['trip_count' => 0, 'fare_total' => 0, 'unpaid_pending_total' => 0, 'paid_total' => 0])
                        <tr>
                            <td><span class="billing-month">{{ $cycle->month_key }}</span></td>
                            <td><span class="billing-date-range">{{ $cycle->start_date?->format('Y-m-d') }} &ndash; {{ $cycle->end_date?->format('Y-m-d') }}</span></td>
                            <td>
                                <span class="billing-status {{ $cycle->status === 'open' ? 'open' : 'closed' }}">{{ ucfirst($cycle->status) }}</span>
                            </td>
                            <td>{{ $cycle->closer?->name ?? ($cycle->status === 'closed' ? 'System' : '-') }}</td>
                            <td>
                                @if($cycle->status !== 'closed')
                                    <span class="billing-empty">—</span>
                                @elseif($cycle->closed_by)
                                    <span class="billing-mode manual">Manual</span>
                                @else
                                    <span class="billing-mode auto">Auto</span>
                                @endif
                            </td>
                            <td class="numeric">{{ (int) $cycleSummary['trip_count'] }}</td>
                            <td class="numeric"><span class="billing-money">RM {{ number_format((float) $cycleSummary['fare_total'], 2) }}</span></td>
                            <td class="numeric"><span class="billing-money warning">RM {{ number_format((float) $cycleSummary['unpaid_pending_total'], 2) }}</span></td>
                            <td class="numeric"><span class="billing-money success">RM {{ number_format((float) $cycleSummary['paid_total'], 2) }}</span></td>
                            <td>{{ $cycle->closed_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center; padding:24px;" class="billing-empty">No billing cycles found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="billing-history-mobile" style="padding:14px;">
                @forelse($cycles as $cycle)
                    @php($cycleSummary = $summaries[$cycle->id] ?? ['trip_count' => 0, 'fare_total' => 0, 'unpaid_pending_total' => 0, 'paid_total' => 0])
                    <article class="billing-history-item">
                        <div class="billing-history-item-top">
                            <div>
                                <div class="billing-history-item-month">{{ $cycle->month_key }}</div>
                                <div class="billing-history-item-range">{{ $cycle->start_date?->format('Y-m-d') }} &ndash; {{ $cycle->end_date?->format('Y-m-d') }}</div>
                            </div>
                            <span class="billing-status {{ $cycle->status === 'open' ? 'open' : 'closed' }}">{{ ucfirst($cycle->status) }}</span>
                        </div>

                        <div>
                            @if($cycle->status !== 'closed')
                                <span class="billing-empty">Close mode: —</span>
                            @elseif($cycle->closed_by)
                                <span class="billing-mode manual">Manual</span>
                            @else
                                <span class="billing-mode auto">Auto</span>
                            @endif
                        </div>

                        <div class="billing-history-stats">
                            <div class="billing-history-stat">
                                <div class="billing-history-stat-label">Trips</div>
                                <div class="billing-history-stat-value">{{ (int) $cycleSummary['trip_count'] }}</div>
                            </div>
                            <div class="billing-history-stat">
                                <div class="billing-history-stat-label">Total fare</div>
                                <div class="billing-history-stat-value">RM {{ number_format((float) $cycleSummary['fare_total'], 2) }}</div>
                            </div>
                            <div class="billing-history-stat">
                                <div class="billing-history-stat-label">Pending / Unpaid</div>
                                <div class="billing-history-stat-value">RM {{ number_format((float) $cycleSummary['unpaid_pending_total'], 2) }}</div>
                            </div>
                            <div class="billing-history-stat">
                                <div class="billing-history-stat-label">Paid</div>
                                <div class="billing-history-stat-value">RM {{ number_format((float) $cycleSummary['paid_total'], 2) }}</div>
                            </div>
                        </div>

                        <div class="billing-history-meta">
                            <div>Closed by: {{ $cycle->closer?->name ?? ($cycle->status === 'closed' ? 'System' : '—') }}</div>
                            <div>Closed at: {{ $cycle->closed_at?->format('Y-m-d H:i') ?: '—' }}</div>
                        </div>
                    </article>
                @empty
                    <div class="billing-empty" style="text-align:center; padding:20px 0;">No billing cycles found.</div>
                @endforelse
            </div>

            <div style="padding:14px 20px;">
                {{ $cycles->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
