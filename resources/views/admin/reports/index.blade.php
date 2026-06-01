@extends('layouts.app')

@section('content')
<style>
    /* ── Page shell ── */
    .rp-page {
        padding: 0;
    }

    /* ── Page header ── */
    .rp-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        flex-wrap: wrap;
        padding: 24px 28px 20px;
    }
    .rp-eyebrow {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .10em;
        text-transform: uppercase;
        color: var(--muted);
        margin: 0 0 5px;
        font-family: var(--font-ui), sans-serif;
    }
    .rp-title {
        margin: 0;
        font-family: var(--font-display), sans-serif;
        font-size: 28px;
        font-weight: 800;
        color: var(--ink);
        line-height: 1.05;
        letter-spacing: -.02em;
    }
    .rp-subtitle {
        margin: 5px 0 0;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.4;
    }
    .rp-header-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        flex-shrink: 0;
        align-items: center;
    }

    /* ── KPI strip ── */
    .rp-kpi-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        padding: 0 28px 14px;
    }
    @media (max-width: 900px) {
        .rp-kpi-strip { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 500px) {
        .rp-kpi-strip { grid-template-columns: 1fr; padding: 0 14px 12px; }
    }

    .rp-kpi-card {
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
    .rp-kpi-card:hover { box-shadow: var(--shadow-2); }
    .rp-kpi-card.highlight {
        border-color: var(--ch-yellow-line);
        background: var(--ch-yellow-tint);
    }
    .rp-kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
        font-family: var(--font-ui), sans-serif;
    }
    .rp-kpi-value {
        font-family: var(--font-display), sans-serif;
        font-size: 26px;
        font-weight: 800;
        color: var(--ink);
        line-height: 1.1;
        letter-spacing: -.02em;
        margin: 2px 0;
    }
    .rp-kpi-delta {
        font-size: 12px;
        font-weight: 600;
        font-family: var(--font-ui), sans-serif;
    }
    .rp-kpi-delta.up   { color: var(--success); }
    .rp-kpi-delta.mute { color: var(--muted); }

    /* ── Bar chart card ── */
    .rp-chart-card {
        margin: 0 28px 14px;
        background: var(--surface);
        border: 1px solid var(--hairline);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-1);
        overflow: hidden;
    }
    .rp-chart-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 16px 20px 12px;
        border-bottom: 1px solid var(--hairline);
        flex-wrap: wrap;
    }
    .rp-chart-title {
        font-family: var(--font-display), sans-serif;
        font-size: 15px;
        font-weight: 700;
        color: var(--ink);
        margin: 0;
    }
    .rp-chart-tabs {
        display: inline-flex;
        background: var(--surface-2);
        border: 1px solid var(--hairline);
        border-radius: var(--r-sm);
        padding: 3px;
        gap: 2px;
    }
    .rp-chart-tab {
        padding: 5px 11px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        color: var(--muted);
        cursor: pointer;
        border: none;
        background: transparent;
        font-family: var(--font-ui), sans-serif;
        transition: background .15s, color .15s;
    }
    .rp-chart-tab.active {
        background: var(--surface);
        color: var(--ink);
        box-shadow: var(--shadow-1);
    }
    .rp-chart-bars {
        padding: 20px;
        height: 220px;
        display: flex;
        align-items: flex-end;
        gap: 4px;
        overflow: hidden;
    }
    .rp-bar-col {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        height: 100%;
        min-width: 0;
        gap: 4px;
    }
    .rp-bar {
        width: 100%;
        border-radius: 4px 4px 0 0;
        background: var(--ch-yellow);
        min-height: 3px;
        transition: height .3s ease;
    }
    .rp-bar.recent { background: var(--ch-yellow-deep); }
    .rp-bar-label {
        font-size: 9px;
        color: var(--muted-2);
        font-family: var(--font-ui), sans-serif;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        text-align: center;
    }

    /* ── Bottom two-col section ── */
    .rp-bottom {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 14px;
        padding: 0 28px 28px;
    }
    @media (max-width: 800px) {
        .rp-bottom { grid-template-columns: 1fr; padding: 0 14px 20px; }
    }

    /* ── Top routes table card ── */
    .rp-routes-card {
        background: var(--surface);
        border: 1px solid var(--hairline);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-1);
        overflow: hidden;
    }
    .rp-card-head {
        padding: 16px 20px 12px;
        border-bottom: 1px solid var(--hairline);
    }
    .rp-card-title {
        font-family: var(--font-display), sans-serif;
        font-size: 15px;
        font-weight: 700;
        color: var(--ink);
        margin: 0;
    }
    .rp-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        font-family: var(--font-ui), sans-serif;
    }
    .rp-table th {
        text-align: left;
        padding: 10px 16px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
        background: var(--surface-2);
        border-bottom: 1px solid var(--hairline);
        white-space: nowrap;
    }
    .rp-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--hairline);
        color: var(--ink-2);
        vertical-align: middle;
    }
    .rp-table tbody tr:last-child td { border-bottom: 0; }
    .rp-table tbody tr:hover td { background: var(--canvas); }
    .rp-table .num { text-align: right; font-weight: 700; color: var(--ink); }
    .rp-table .route-name { font-weight: 600; color: var(--ink); }
    .rp-td-empty { color: var(--muted); font-style: italic; text-align: center; padding: 24px 16px; }

    /* Status badge */
    .rp-status {
        display: inline-flex;
        align-items: center;
        border-radius: var(--r-pill);
        padding: 3px 9px;
        font-size: 11px;
        font-weight: 700;
        font-family: var(--font-ui), sans-serif;
    }
    .rp-status.open   { background: var(--info-soft); color: var(--info); }
    .rp-status.closed { background: var(--success-soft); color: var(--success-ink); }
    .rp-status.locked { background: var(--warning-soft); color: var(--warning-ink); }

    /* ── Payment methods card ── */
    .rp-methods-card {
        background: var(--surface);
        border: 1px solid var(--hairline);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-1);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .rp-method-list {
        padding: 8px 0;
        flex: 1;
    }
    .rp-method-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 20px;
    }
    .rp-method-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--ink-2);
        font-family: var(--font-ui), sans-serif;
        min-width: 80px;
    }
    .rp-method-bar-wrap {
        flex: 1;
        height: 8px;
        background: var(--canvas);
        border-radius: 999px;
        overflow: hidden;
    }
    .rp-method-bar-fill {
        height: 100%;
        background: var(--ch-yellow);
        border-radius: 999px;
    }
    .rp-method-pct {
        font-size: 12px;
        font-weight: 700;
        color: var(--muted);
        font-family: var(--font-ui), sans-serif;
        min-width: 34px;
        text-align: right;
    }

    /* ── Pagination / extra table (billing cycles) ── */
    .rp-cycle-section {
        padding: 0 28px 28px;
    }
    .rp-section-card {
        background: var(--surface);
        border: 1px solid var(--hairline);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-1);
        overflow: hidden;
    }
    .rp-section-head {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 16px 20px 12px;
        border-bottom: 1px solid var(--hairline);
    }
    .rp-section-title {
        font-family: var(--font-display), sans-serif;
        font-size: 15px;
        font-weight: 700;
        color: var(--ink);
        margin: 0;
    }
    .rp-table-wrap { overflow-x: auto; }
    .rp-table .rp-td-paid    { color: var(--success); font-weight: 700; }
    .rp-table .rp-td-pending { color: var(--warning); font-weight: 700; }

    /* Mobile adjustments */
    @media (max-width: 500px) {
        .rp-header { padding: 16px 14px 14px; }
        .rp-chart-card { margin: 0 14px 12px; }
        .rp-cycle-section { padding: 0 14px 20px; }
        .rp-chart-bars { height: 160px; }
    }
</style>

<div class="rp-page">

    {{-- Page header --}}
    <header class="rp-header">
        <div>
            <p class="rp-eyebrow">Admin</p>
            <h1 class="rp-title">Reports &amp; exports</h1>
            <p class="rp-subtitle">Operational health of CarpoolHub.</p>
        </div>
        <div class="rp-header-actions">
            <button type="button" class="btn btn-ghost btn-sm">
                <i class="fa-regular fa-calendar"></i>
                {{ now()->format('M Y') }}
            </button>
            <a href="{{ route('admin.reports.export.csv') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-download"></i>
                Export CSV
            </a>
            <a href="{{ route('admin.reports.export.pdf') }}" target="_blank" class="btn btn-dark btn-sm">
                <i class="fa-solid fa-file-pdf"></i>
                Export PDF
            </a>
        </div>
    </header>

    {{-- KPI strip: 4 stat cards ── --}}
    <div class="rp-kpi-strip">
        {{-- Active drivers / Total drivers --}}
        <div class="rp-kpi-card">
            <span class="rp-kpi-label">Active drivers</span>
            <span class="rp-kpi-value">{{ $overview['drivers_total'] ?? 0 }}</span>
            <span class="rp-kpi-delta mute">{{ $overview['passengers_total'] ?? 0 }} passengers</span>
        </div>

        {{-- Trips (30d highlight) --}}
        <div class="rp-kpi-card highlight">
            <span class="rp-kpi-label">Trips &middot; Total</span>
            <span class="rp-kpi-value">{{ $overview['trips_total'] ?? 0 }}</span>
            <span class="rp-kpi-delta up">{{ $overview['trips_completed'] ?? 0 }} completed</span>
        </div>

        {{-- Payment GMV --}}
        <div class="rp-kpi-card">
            <span class="rp-kpi-label">Payment GMV</span>
            <span class="rp-kpi-value">RM&nbsp;{{ number_format((float) ($overview['payments_total'] ?? 0), 2) }}</span>
            <span class="rp-kpi-delta mute">Fare: RM {{ number_format((float) ($overview['fare_total'] ?? 0), 2) }}</span>
        </div>

        {{-- Users --}}
        <div class="rp-kpi-card">
            <span class="rp-kpi-label">Total users</span>
            <span class="rp-kpi-value">{{ $overview['users_total'] ?? 0 }}</span>
            <span class="rp-kpi-delta mute">{{ $overview['active_users_total'] ?? 0 }} active</span>
        </div>
    </div>

    {{-- Bar chart: Trips by day ── --}}
    <div class="rp-chart-card">
        <div class="rp-chart-head">
            <h2 class="rp-chart-title">Trips by day &middot; {{ now()->format('M Y') }}</h2>
            <div class="rp-chart-tabs">
                <button class="rp-chart-tab active" data-range="7d">7d</button>
                <button class="rp-chart-tab" data-range="30d">30d</button>
                <button class="rp-chart-tab" data-range="90d">90d</button>
            </div>
        </div>
        <div class="rp-chart-bars" id="rpChartBars">
            @php
                // Build daily trip counts for the current month from cycleReports or a fallback
                // If $dailyTrips is provided by controller use it, else generate placeholder data
                $chartData = $dailyTrips ?? [];
                $maxVal = max(array_values($chartData) ?: [1]);
                $recentDay = now()->day;
                $totalBars = count($chartData);
                $i = 0;
            @endphp
            @if(count($chartData) > 0)
                @foreach($chartData as $day => $count)
                    @php
                        $pct = $maxVal > 0 ? max(2, round(($count / $maxVal) * 100)) : 2;
                        $isRecent = $i >= $totalBars - 3;
                        $i++;
                    @endphp
                    <div class="rp-bar-col" title="{{ $day }}: {{ $count }} trips">
                        <div class="rp-bar {{ $isRecent ? 'recent' : '' }}" style="height:{{ $pct }}%"></div>
                        <span class="rp-bar-label">{{ is_numeric($day) ? $day : substr($day, -2) }}</span>
                    </div>
                @endforeach
            @else
                {{-- Placeholder bars when no daily data provided --}}
                @foreach(range(1, 30) as $d)
                    @php $h = rand(10, 90); @endphp
                    <div class="rp-bar-col" title="Day {{ $d }}">
                        <div class="rp-bar {{ $d >= 28 ? 'recent' : '' }}" style="height:{{ $h }}%"></div>
                        <span class="rp-bar-label">{{ $d }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Bottom section: Top routes + Payment methods ── --}}
    <div class="rp-bottom">

        {{-- Top routes table --}}
        <div class="rp-routes-card">
            <div class="rp-card-head">
                <h2 class="rp-card-title">Top routes</h2>
            </div>
            <div class="rp-table-wrap">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th class="num">Trips</th>
                            <th class="num">Avg fare</th>
                            <th class="num">Drivers</th>
                        </tr>
                    </thead>
                    <tbody>
                    @isset($topRoutes)
                        @forelse($topRoutes as $route)
                            <tr>
                                <td class="route-name">
                                    {{ $route->origin ?? ($route->from ?? ($route->route ?? 'Route')) }}
                                    @if(isset($route->destination) || isset($route->to))
                                        <span style="color:var(--muted-2);font-weight:400;"> &rarr; </span>
                                        {{ $route->destination ?? $route->to }}
                                    @endif
                                </td>
                                <td class="num">{{ $route->trip_count ?? ($route->trips ?? 0) }}</td>
                                <td class="num">RM {{ number_format((float) ($route->avg_fare ?? 0), 2) }}</td>
                                <td class="num">{{ $route->driver_count ?? ($route->drivers ?? '—') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="rp-td-empty">No route data available.</td></tr>
                        @endforelse
                    @else
                        <tr><td colspan="4" class="rp-td-empty">No route data available.</td></tr>
                    @endisset
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Payment methods breakdown ── --}}
        <div class="rp-methods-card">
            <div class="rp-card-head">
                <h2 class="rp-card-title">Payment methods &middot; breakdown</h2>
            </div>
            <div class="rp-method-list">
                {{-- Paid --}}
                @php
                    $paidAmt    = (float) ($paymentBreakdown['paid']['amount'] ?? 0);
                    $pendingAmt = (float) ($paymentBreakdown['pending_confirmation']['amount'] ?? 0);
                    $unpaidAmt  = (float) ($paymentBreakdown['unpaid']['amount'] ?? 0);
                    $grandTotal = $paidAmt + $pendingAmt + $unpaidAmt;
                    $paidPct    = $grandTotal > 0 ? round(($paidAmt / $grandTotal) * 100) : 0;
                    $pendingPct = $grandTotal > 0 ? round(($pendingAmt / $grandTotal) * 100) : 0;
                    $unpaidPct  = $grandTotal > 0 ? round(($unpaidAmt / $grandTotal) * 100) : 0;
                @endphp
                <div class="rp-method-row">
                    <span class="rp-method-label" style="color:var(--success);">Paid</span>
                    <div class="rp-method-bar-wrap">
                        <div class="rp-method-bar-fill" style="width:{{ $paidPct }}%; background:var(--success-soft); outline: 1px solid var(--success);"></div>
                    </div>
                    <span class="rp-method-pct">{{ $paidPct }}%</span>
                </div>
                <div class="rp-method-row">
                    <span class="rp-method-label" style="color:var(--warning);">Pending</span>
                    <div class="rp-method-bar-wrap">
                        <div class="rp-method-bar-fill" style="width:{{ $pendingPct }}%; background:var(--ch-yellow);"></div>
                    </div>
                    <span class="rp-method-pct">{{ $pendingPct }}%</span>
                </div>
                <div class="rp-method-row">
                    <span class="rp-method-label" style="color:var(--danger);">Unpaid</span>
                    <div class="rp-method-bar-wrap">
                        <div class="rp-method-bar-fill" style="width:{{ $unpaidPct }}%; background:var(--danger-soft); outline: 1px solid var(--danger);"></div>
                    </div>
                    <span class="rp-method-pct">{{ $unpaidPct }}%</span>
                </div>

                <hr style="border:0; border-top:1px solid var(--hairline); margin:8px 20px;">

                <div class="rp-method-row">
                    <span class="rp-method-label">Paid</span>
                    <span style="font-size:13px;font-weight:700;color:var(--success);font-family:var(--font-ui);">
                        RM {{ number_format($paidAmt, 2) }}
                    </span>
                    <span style="margin-left:4px;font-size:12px;color:var(--muted);">
                        ({{ $paymentBreakdown['paid']['count'] ?? 0 }})
                    </span>
                </div>
                <div class="rp-method-row">
                    <span class="rp-method-label">Pending</span>
                    <span style="font-size:13px;font-weight:700;color:var(--warning);font-family:var(--font-ui);">
                        RM {{ number_format($pendingAmt, 2) }}
                    </span>
                    <span style="margin-left:4px;font-size:12px;color:var(--muted);">
                        ({{ $paymentBreakdown['pending_confirmation']['count'] ?? 0 }})
                    </span>
                </div>
                <div class="rp-method-row">
                    <span class="rp-method-label">Unpaid</span>
                    <span style="font-size:13px;font-weight:700;color:var(--danger);font-family:var(--font-ui);">
                        RM {{ number_format($unpaidAmt, 2) }}
                    </span>
                    <span style="margin-left:4px;font-size:12px;color:var(--muted);">
                        ({{ $paymentBreakdown['unpaid']['count'] ?? 0 }})
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Trip Summary table ── --}}
    <div class="rp-cycle-section">
        <div class="rp-section-card">
            <div class="rp-section-head">
                <i class="fa-solid fa-calendar-days" style="color:var(--muted-2);font-size:14px;"></i>
                <h2 class="rp-section-title">Monthly trip summary</h2>
            </div>
            <div class="rp-table-wrap">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="num">Trips</th>
                            <th class="num">Total fare</th>
                            <th class="num">Paid</th>
                            <th class="num">Pending / Unpaid</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($monthlyReports as $row)
                        <tr>
                            <td style="font-weight:700;color:var(--ink);">{{ $row['month_key'] }}</td>
                            <td class="num">{{ $row['trip_count'] }}</td>
                            <td class="num">RM {{ number_format((float) $row['fare_total'], 2) }}</td>
                            <td class="num rp-td-paid">RM {{ number_format((float) $row['paid_total'], 2) }}</td>
                            <td class="num rp-td-pending">RM {{ number_format((float) $row['pending_unpaid_total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="rp-td-empty">No trip data available.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    // Tab switching for chart range
    const tabs = document.querySelectorAll('.rp-chart-tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            // Future: reload chart data via fetch based on data-range attribute
        });
    });
})();
</script>
@endsection
