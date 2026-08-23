@extends('layouts.app')

@section('content')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-reports.css') }}?v={{ filemtime(public_path('css/admin-reports.css')) }}">
@endpush

<div class="reports-page-container">

    {{-- Page Header Card --}}
    <div class="rp-header-card">
        <div class="rp-header-info">
            <p class="rp-eyebrow">Administrator</p>
            <h1 class="rp-title">Reports &amp; Analytics</h1>
            <p class="rp-subtitle">Operational analytics for CarpoolHub features: AI assistance, custom route preferences, passenger reliability, and payment tracking.</p>
        </div>
        <div class="rp-header-actions">
            <button type="button" class="btn btn-ghost btn-sm" style="font-weight:700; color:var(--muted);">
                <i class="fa-regular fa-calendar"></i>
                {{ now()->format('M Y') }}
            </button>
            <a href="{{ route('admin.reports.export.csv') }}" class="btn btn-primary btn-sm" style="background:var(--ch-yellow); color:var(--ch-yellow-ink); border:1px solid var(--ch-yellow-line); font-weight:800;">
                <i class="fa-solid fa-download"></i>
                Export CSV
            </a>
            <a href="{{ route('admin.reports.export.pdf') }}" target="_blank" class="btn btn-dark btn-sm" style="font-weight:800;">
                <i class="fa-solid fa-file-pdf"></i>
                Export PDF
            </a>
        </div>
    </div>

    {{-- KPI Grid: 4 Stat Cards --}}
    <div class="rp-kpi-grid">
        <div class="rp-kpi-card">
            <span class="rp-kpi-label">Active Drivers</span>
            <span class="rp-kpi-value">{{ $overview['drivers_total'] ?? 0 }}</span>
            <span class="rp-kpi-delta mute">{{ $overview['passengers_total'] ?? 0 }} passengers</span>
        </div>

        <div class="rp-kpi-card highlight">
            <span class="rp-kpi-label">Trips &middot; Total</span>
            <span class="rp-kpi-value">{{ $overview['trips_total'] ?? 0 }}</span>
            <span class="rp-kpi-delta up">{{ $overview['trips_completed'] ?? 0 }} completed</span>
        </div>

        <div class="rp-kpi-card">
            <span class="rp-kpi-label">Payment GMV</span>
            <span class="rp-kpi-value">RM {{ number_format((float) ($overview['payments_total'] ?? 0), 2) }}</span>
            <span class="rp-kpi-delta mute">Fare: RM {{ number_format((float) ($overview['fare_total'] ?? 0), 2) }}</span>
        </div>

        <div class="rp-kpi-card">
            <span class="rp-kpi-label">Total Users</span>
            <span class="rp-kpi-value">{{ $overview['users_total'] ?? 0 }}</span>
            <span class="rp-kpi-delta mute">{{ $overview['active_users_total'] ?? 0 }} active</span>
        </div>
    </div>

    {{-- Bar Chart: Trips by Day --}}
    <div class="rp-chart-card">
        <div class="rp-chart-head">
            <h2 class="rp-chart-title">
                <i class="fa-solid fa-chart-column" style="color:var(--muted);"></i> Trips by Day
            </h2>
            <div class="rp-chart-tabs">
                <button class="rp-chart-tab active" data-range="7d">7d</button>
                <button class="rp-chart-tab" data-range="30d">30d</button>
                <button class="rp-chart-tab" data-range="90d">90d</button>
            </div>
        </div>
        <div class="rp-chart-bars-wrap">
            <div class="rp-chart-bars" id="rpChartBars">
                @php
                    $chartData = $dailyTripRanges['7d'] ?? [];
                    $maxVal = max(array_values($chartData) ?: [1]);
                    $totalBars = count($chartData);
                    $i = 0;
                @endphp
                @if(count($chartData) > 0)
                    @foreach($chartData as $date => $count)
                        @php
                            $pct = $maxVal > 0 && $count > 0 ? max(6, round(($count / $maxVal) * 100)) : 3;
                            $isRecent = $i >= $totalBars - 3;
                            $i++;
                        @endphp
                        <div class="rp-bar-col" title="{{ $date }}: {{ $count }} trips">
                            <div class="rp-bar {{ $isRecent ? 'recent' : '' }}" style="height:{{ $pct }}%"></div>
                            <span class="rp-bar-label">{{ \Illuminate\Support\Carbon::parse($date)->format('d M') }}</span>
                        </div>
                    @endforeach
                @else
                    <div class="rp-td-empty" style="width:100%;">No trip activity in this range.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Bottom Section: Top Routes + Payment Methods Breakdown --}}
    <div class="rp-bottom-grid">

        {{-- Top Routes Table Card --}}
        <div class="rp-section-card">
            <div class="rp-card-head">
                <h2 class="rp-card-title">
                    <i class="fa-solid fa-route" style="color:var(--muted);"></i> Top Routes
                </h2>
            </div>
            <div class="rp-table-wrap">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th class="num">Trips</th>
                            <th class="num">Avg Fare</th>
                            <th class="num">Drivers</th>
                        </tr>
                    </thead>
                    <tbody>
                    @isset($topRoutes)
                        @forelse($topRoutes as $route)
                            <tr>
                                <td class="route-name">{{ $route['route_name'] ?? 'Route' }}</td>
                                <td class="num">{{ $route['trip_count'] ?? 0 }}</td>
                                <td class="num">RM {{ number_format((float) ($route['avg_fare'] ?? 0), 2) }}</td>
                                <td class="num">{{ $route['driver_count'] ?? 0 }}</td>
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

        {{-- Payment Methods Breakdown Card --}}
        <div class="rp-section-card">
            <div class="rp-card-head">
                <h2 class="rp-card-title">
                    <i class="fa-solid fa-wallet" style="color:var(--muted);"></i> Payment Breakdown
                </h2>
            </div>
            <div class="rp-method-list">
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
                        <div class="rp-method-bar-fill" style="width:{{ $paidPct }}%; background:#22c55e;"></div>
                    </div>
                    <span class="rp-method-pct">{{ $paidPct }}%</span>
                </div>
                <div class="rp-method-row">
                    <span class="rp-method-label" style="color:var(--warning-ink);">Pending</span>
                    <div class="rp-method-bar-wrap">
                        <div class="rp-method-bar-fill" style="width:{{ $pendingPct }}%; background:var(--ch-yellow);"></div>
                    </div>
                    <span class="rp-method-pct">{{ $pendingPct }}%</span>
                </div>
                <div class="rp-method-row">
                    <span class="rp-method-label" style="color:var(--danger);">Unpaid</span>
                    <div class="rp-method-bar-wrap">
                        <div class="rp-method-bar-fill" style="width:{{ $unpaidPct }}%; background:#ef4444;"></div>
                    </div>
                    <span class="rp-method-pct">{{ $unpaidPct }}%</span>
                </div>

                <div style="border-top:1px solid var(--hairline); margin:6px 0; padding-top:10px; display:flex; flex-direction:column; gap:6px;">
                    <div class="rp-method-row">
                        <span class="rp-method-label">Paid</span>
                        <span style="font-size:13px; font-weight:800; color:var(--success); font-family:var(--font-ui);">
                            RM {{ number_format($paidAmt, 2) }}
                        </span>
                        <span style="margin-left:auto; font-size:12px; color:var(--muted); font-weight:700;">
                            {{ $paymentBreakdown['paid']['count'] ?? 0 }} payments
                        </span>
                    </div>
                    <div class="rp-method-row">
                        <span class="rp-method-label">Pending</span>
                        <span style="font-size:13px; font-weight:800; color:var(--warning-ink); font-family:var(--font-ui);">
                            RM {{ number_format($pendingAmt, 2) }}
                        </span>
                        <span style="margin-left:auto; font-size:12px; color:var(--muted); font-weight:700;">
                            {{ $paymentBreakdown['pending_confirmation']['count'] ?? 0 }} payments
                        </span>
                    </div>
                    <div class="rp-method-row">
                        <span class="rp-method-label">Unpaid</span>
                        <span style="font-size:13px; font-weight:800; color:var(--danger); font-family:var(--font-ui);">
                            RM {{ number_format($unpaidAmt, 2) }}
                        </span>
                        <span style="margin-left:auto; font-size:12px; color:var(--muted); font-weight:700;">
                            {{ $paymentBreakdown['unpaid']['count'] ?? 0 }} payments
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Feature Performance Overview Grid --}}
    <div class="rp-section-card">
        <div class="rp-card-head">
            <h2 class="rp-card-title">
                <i class="fa-solid fa-sliders" style="color:var(--muted);"></i> Feature Performance Overview
            </h2>
        </div>
        <div style="padding:16px;">
            <div class="rp-module-grid">
                <article class="rp-module-card">
                    <span class="rp-module-title">Custom Route Requests</span>
                    <strong class="rp-module-value">{{ $customRouteSummary['custom_requests'] ?? 0 }}</strong>
                    <span class="rp-module-note">{{ $customRouteSummary['custom_share'] ?? 0 }}% of trips use custom pickup/drop-off. Avg extra fee RM {{ number_format((float) ($customRouteSummary['avg_extra_fee'] ?? 0), 2) }}.</span>
                </article>
                <article class="rp-module-card">
                    <span class="rp-module-title">Passenger Approval</span>
                    <strong class="rp-module-value">{{ $requestSummary['approval_rate'] ?? 0 }}%</strong>
                    <span class="rp-module-note">{{ $requestSummary['approved'] ?? 0 }} approved, {{ $requestSummary['pending'] ?? 0 }} pending, {{ $requestSummary['rejected'] ?? 0 }} rejected.</span>
                </article>
                <article class="rp-module-card">
                    <span class="rp-module-title">AI Support Usage</span>
                    <strong class="rp-module-value">{{ $aiSupportSummary['recommendation_logs'] ?? 0 }}</strong>
                    <span class="rp-module-note">AI-assisted trips (fare calculation + join decisions). Avg match score {{ $aiSupportSummary['avg_match_score'] ?? 0 }}%. Decision support: {{ $aiSupportSummary['strategy_suggestions'] ?? 0 }} cases.</span>
                </article>
                <article class="rp-module-card">
                    <span class="rp-module-title">Passenger Reliability</span>
                    <strong class="rp-module-value">{{ $reliabilitySummary['avg_payment_reliability'] ?? 0 }}%</strong>
                    <span class="rp-module-note">Payment rate: {{ $reliabilitySummary['paid_payments'] ?? 0 }} paid / {{ $reliabilitySummary['total_payments'] ?? $reliabilitySummary['profiles_total'] ?? 0 }} total. High-risk: {{ $reliabilitySummary['high_risk_total'] ?? 0 }} passengers. Outstanding: RM {{ number_format((float)($reliabilitySummary['outstanding_amount'] ?? 0), 2) }}.</span>
                </article>
            </div>
        </div>
    </div>

    {{-- Monthly Trip Summary Table Card --}}
    <div class="rp-section-card">
        <div class="rp-card-head">
            <h2 class="rp-card-title">
                <i class="fa-solid fa-calendar-days" style="color:var(--muted);"></i> Monthly Trip Summary
            </h2>
        </div>
        <div class="rp-table-wrap">
            <table class="rp-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="num">Trips</th>
                        <th class="num">Total Fare</th>
                        <th class="num">Paid</th>
                        <th class="num">Pending / Unpaid</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($monthlyReports as $row)
                    <tr>
                        <td style="font-weight:800; color:var(--ink);">{{ $row['month_key'] }}</td>
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

<script>
(function () {
    const chartRanges = @json($dailyTripRanges ?? []);
    const chartBars = document.getElementById('rpChartBars');
    const tabs = document.querySelectorAll('.rp-chart-tab');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatLabel(dateKey) {
        const date = new Date(`${dateKey}T00:00:00`);
        if (Number.isNaN(date.getTime())) return dateKey;
        return date.toLocaleDateString(undefined, { day: '2-digit', month: 'short' });
    }

    function renderChart(range) {
        if (!chartBars) return;
        const rows = Object.entries(chartRanges[range] || {});
        if (!rows.length) {
            chartBars.innerHTML = '<div class="rp-td-empty" style="width:100%;">No trip activity in this range.</div>';
            return;
        }
        const max = Math.max(1, ...rows.map(([, count]) => Number(count) || 0));
        const recentStart = Math.max(0, rows.length - 3);
        chartBars.innerHTML = rows.map(([date, count], index) => {
            const numericCount = Number(count) || 0;
            const pct = numericCount > 0 ? Math.max(6, Math.round((numericCount / max) * 100)) : 3;
            const recentClass = index >= recentStart ? ' recent' : '';
            return `
                <div class="rp-bar-col" title="${escapeHtml(date)}: ${numericCount} trips">
                    <div class="rp-bar${recentClass}" style="height:${pct}%"></div>
                    <span class="rp-bar-label">${escapeHtml(formatLabel(date))}</span>
                </div>
            `;
        }).join('');
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            renderChart(tab.dataset.range || '7d');
        });
    });
})();
</script>
@endsection
