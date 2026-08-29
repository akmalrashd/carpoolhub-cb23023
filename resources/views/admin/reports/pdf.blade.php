<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarpoolHub Admin Report</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        :root {
            --ink: #0b1220;
            --muted: #64748b;
            --hairline: #ece7da;
            --surface-2: #faf7ee;
            --yellow: #facc15;
            --yellow-deep: #e6b800;
            --yellow-ink: #2a1e04;
            --success: #16a34a;
            --danger: #dc2626;
            --warning-ink: #78350f;
        }
        * { box-sizing: border-box; }
        body {
            font-family: "Inter", system-ui, sans-serif;
            margin: 0;
            padding: 28px 32px 40px;
            color: var(--ink);
            font-size: 13px;
            line-height: 1.4;
        }
        h1, h2 { font-family: "Poppins", "Inter", sans-serif; margin: 0; }

        /* ── Report Header ── */
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 16px;
            border-bottom: 3px solid var(--yellow);
            margin-bottom: 22px;
        }
        .report-brand { display: flex; align-items: center; gap: 10px; }
        .report-brand img { width: 34px; height: 34px; border-radius: 8px; }
        .report-brand-name { font-family: "Poppins", sans-serif; font-weight: 800; font-size: 18px; }
        .report-brand-name .hub { color: var(--yellow-deep); }
        .report-title { font-size: 20px; font-weight: 800; margin: 2px 0 0; }
        .report-meta { text-align: right; font-size: 11px; color: var(--muted); }
        .report-meta strong { color: var(--ink); }

        /* ── Section ── */
        .section { margin-bottom: 22px; page-break-inside: avoid; }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 0 0 10px;
            color: var(--ink);
        }
        .section-title i { color: var(--yellow-deep); font-size: 13px; width: 16px; text-align: center; }

        /* ── KPI cards ── */
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .kpi-card {
            border: 1px solid var(--hairline);
            border-radius: 8px;
            padding: 10px 12px;
            background: #fff;
        }
        .kpi-card.highlight { background: #fffbea; border-color: var(--yellow-deep); }
        .kpi-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
        .kpi-value { font-family: "Poppins", sans-serif; font-size: 18px; font-weight: 800; margin-top: 3px; }
        .kpi-note { font-size: 10.5px; color: var(--muted); margin-top: 2px; }

        /* ── Tables ── */
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid var(--hairline); padding: 7px 10px; text-align: left; font-size: 12px; }
        th { background: var(--surface-2); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
        tbody tr:last-child td { border-bottom: none; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .empty-note { text-align: center; color: var(--muted); font-style: italic; padding: 14px; }
        .paid { color: var(--success); font-weight: 700; }
        .pending { color: var(--warning-ink); font-weight: 700; }
        .danger { color: var(--danger); font-weight: 700; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .no-print { margin-bottom: 16px; }
        .no-print button {
            background: var(--yellow);
            color: var(--yellow-ink);
            border: 1px solid var(--yellow-deep);
            font-weight: 700;
            font-family: "Inter", sans-serif;
            padding: 9px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
        }
        footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid var(--hairline); font-size: 10px; color: var(--muted); text-align: center; }

        @media print {
            .no-print { display: none !important; }
            .section { break-inside: avoid; }
            @page { size: A4 portrait; margin: 14mm 12mm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save as PDF</button>
</div>

<div class="report-header">
    <div>
        <div class="report-brand">
            <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="CarpoolHub">
            <span class="report-brand-name">Carpool<span class="hub">Hub</span></span>
        </div>
        <p class="report-title">Admin Report &mdash; Reports &amp; Analytics</p>
    </div>
    <div class="report-meta">
        Generated <strong>{{ now()->format('d M Y, h:i A') }}</strong><br>
        Scope: <strong>All-time</strong> (except the Overview KPIs on the live page, which can be date-filtered)
    </div>
</div>

<div class="section">
    <h2 class="section-title"><i class="fa-solid fa-gauge-high"></i> Overview</h2>
    <div class="grid">
        <div class="kpi-card"><div class="kpi-label">Total Users</div><div class="kpi-value">{{ $overview['users_total'] }}</div></div>
        <div class="kpi-card"><div class="kpi-label">Drivers</div><div class="kpi-value">{{ $overview['drivers_total'] }}</div></div>
        <div class="kpi-card"><div class="kpi-label">Passengers</div><div class="kpi-value">{{ $overview['passengers_total'] }}</div></div>
        <div class="kpi-card"><div class="kpi-label">Active Users</div><div class="kpi-value">{{ $overview['active_users_total'] }}</div></div>
        <div class="kpi-card highlight"><div class="kpi-label">Total Trips</div><div class="kpi-value">{{ $overview['trips_total'] }}</div><div class="kpi-note">{{ $overview['trips_completed'] }} completed</div></div>
        <div class="kpi-card"><div class="kpi-label">Total Fare</div><div class="kpi-value">RM {{ number_format((float) $overview['fare_total'], 2) }}</div></div>
        <div class="kpi-card"><div class="kpi-label">Payment GMV</div><div class="kpi-value">RM {{ number_format((float) $overview['payments_total'], 2) }}</div><div class="kpi-note">RM {{ number_format((float) $overview['payments_paid'], 2) }} paid</div></div>
        <div class="kpi-card"><div class="kpi-label">Public Trips</div><div class="kpi-value">{{ $overview['public_trips_total'] }}</div></div>
    </div>
</div>

<div class="section two-col">
    <div>
        <h2 class="section-title"><i class="fa-solid fa-wallet"></i> Payment Breakdown</h2>
        <table>
            <thead><tr><th>Status</th><th class="num">Count</th><th class="num">Amount</th></tr></thead>
            <tbody>
            @foreach($paymentBreakdown as $status => $row)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                    <td class="num">{{ $row['count'] }}</td>
                    <td class="num">RM {{ number_format((float) $row['amount'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div>
        <h2 class="section-title"><i class="fa-solid fa-graduation-cap"></i> Thesis Module Evidence</h2>
        <table>
            <thead><tr><th>Module</th><th class="num">Evidence</th><th>Unit</th></tr></thead>
            <tbody>
            @foreach($thesisAlignment as $row)
                <tr>
                    <td>{{ $row['objective'] }}</td>
                    <td class="num">{{ $row['evidence'] }}</td>
                    <td>{{ $row['unit'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="section">
    <h2 class="section-title"><i class="fa-solid fa-sliders"></i> Feature Performance</h2>
    <div class="grid">
        <div class="kpi-card">
            <div class="kpi-label">Custom Route Requests</div>
            <div class="kpi-value">{{ $customRouteSummary['custom_requests'] }}</div>
            <div class="kpi-note">{{ $customRouteSummary['custom_share'] }}% of trips &middot; avg extra fee RM {{ number_format((float) $customRouteSummary['avg_extra_fee'], 2) }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Passenger Approval</div>
            <div class="kpi-value">{{ $requestSummary['approval_rate'] }}%</div>
            <div class="kpi-note">{{ $requestSummary['approved'] }} approved &middot; {{ $requestSummary['pending'] }} pending &middot; {{ $requestSummary['rejected'] }} rejected &middot; {{ $requestSummary['cancelled'] }} cancelled</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">AI Support Usage</div>
            <div class="kpi-value">{{ $aiSupportSummary['recommendation_logs'] }}</div>
            <div class="kpi-note">Avg match score: {{ !empty($aiSupportSummary['avg_match_score_measured']) ? number_format((float) $aiSupportSummary['avg_match_score'], 1).'%' : 'not yet measured' }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Passenger Reliability</div>
            <div class="kpi-value">{{ $reliabilitySummary['avg_payment_reliability'] }}%</div>
            <div class="kpi-note">{{ $reliabilitySummary['high_risk_total'] }} high-risk &middot; RM {{ number_format((float) $reliabilitySummary['outstanding_amount'], 2) }} outstanding</div>
        </div>
    </div>
</div>

<div class="section">
    <h2 class="section-title"><i class="fa-solid fa-route"></i> Top Routes</h2>
    <table>
        <thead><tr><th>Route</th><th class="num">Trips</th><th class="num">Avg Fare</th><th class="num">Drivers</th></tr></thead>
        <tbody>
        @forelse($topRoutes as $row)
            <tr>
                <td>{{ $row['route_name'] }}</td>
                <td class="num">{{ $row['trip_count'] }}</td>
                <td class="num">RM {{ number_format((float) $row['avg_fare'], 2) }}</td>
                <td class="num">{{ $row['driver_count'] }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="empty-note">No data available.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <h2 class="section-title"><i class="fa-solid fa-id-badge"></i> Top Drivers</h2>
    <table>
        <thead><tr><th>Driver</th><th class="num">Trips</th><th class="num">Completed</th><th class="num">Routes</th><th class="num">Avg Fare</th><th class="num">Revenue</th></tr></thead>
        <tbody>
        @forelse($topDrivers as $row)
            <tr>
                <td>{{ $row['driver_name'] }}</td>
                <td class="num">{{ $row['trip_count'] }}</td>
                <td class="num">{{ $row['completed_count'] }} ({{ $row['completion_rate'] }}%)</td>
                <td class="num">{{ $row['route_count'] }}</td>
                <td class="num">RM {{ number_format((float) $row['avg_fare'], 2) }}</td>
                <td class="num">RM {{ number_format((float) $row['fare_total'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty-note">No data available.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <h2 class="section-title"><i class="fa-solid fa-calendar-days"></i> Monthly Summary</h2>
    <table>
        <thead><tr><th>Month</th><th class="num">Trips</th><th class="num">New Users</th><th class="num">Total Fare</th><th class="num">Paid</th><th class="num">Pending/Unpaid</th></tr></thead>
        <tbody>
        @forelse($monthlyReports as $row)
            <tr>
                <td>{{ $row['month_key'] }}</td>
                <td class="num">{{ $row['trip_count'] }}</td>
                <td class="num">{{ $row['new_users'] ?? 0 }}</td>
                <td class="num">RM {{ number_format((float) $row['fare_total'], 2) }}</td>
                <td class="num paid">RM {{ number_format((float) $row['paid_total'], 2) }}</td>
                <td class="num pending">RM {{ number_format((float) $row['pending_unpaid_total'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty-note">No data available.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <h2 class="section-title"><i class="fa-solid fa-chart-column"></i> Trips by Day (last 30 days)</h2>
    <table>
        <thead><tr><th>Date</th><th class="num">Trips</th></tr></thead>
        <tbody>
        @forelse(($dailyTripRanges['30d'] ?? []) as $day => $count)
            <tr><td>{{ $day }}</td><td class="num">{{ $count }}</td></tr>
        @empty
            <tr><td colspan="2" class="empty-note">No data available.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="section two-col">
    <div>
        <h2 class="section-title"><i class="fa-solid fa-robot"></i> AI Usage (Claude API)</h2>
        <table>
            <tbody>
                <tr><td>Total calls</td><td class="num">{{ $aiUsage['total_calls'] ?? 0 }}</td></tr>
                <tr><td>Success rate</td><td class="num">{{ $aiUsage['success_rate'] ?? 0 }}%</td></tr>
                <tr><td>Total input tokens</td><td class="num">{{ number_format($aiUsage['total_input_tokens'] ?? 0) }}</td></tr>
                <tr><td>Total output tokens</td><td class="num">{{ number_format($aiUsage['total_output_tokens'] ?? 0) }}</td></tr>
                <tr><td>Retries</td><td class="num">{{ $aiUsage['retry_count'] ?? 0 }}</td></tr>
            </tbody>
        </table>
    </div>
    <div>
        <h2 class="section-title"><i class="fa-solid fa-server"></i> By Endpoint</h2>
        <table>
            <thead><tr><th>Endpoint</th><th class="num">Calls</th></tr></thead>
            <tbody>
            @forelse(($aiUsage['by_endpoint'] ?? []) as $endpoint => $count)
                <tr><td>{{ $endpoint }}</td><td class="num">{{ $count }}</td></tr>
            @empty
                <tr><td colspan="2" class="empty-note">No calls logged.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!empty($aiUsage['error_breakdown']))
<div class="section">
    <h2 class="section-title"><i class="fa-solid fa-triangle-exclamation"></i> AI Usage Errors</h2>
    <table>
        <thead><tr><th>Error Type</th><th class="num">Count</th></tr></thead>
        <tbody>
        @foreach($aiUsage['error_breakdown'] as $type => $count)
            <tr><td class="danger">{{ $type ?: 'Unknown' }}</td><td class="num">{{ $count }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<footer>CarpoolHub Admin Report &middot; generated {{ now()->format('d M Y, h:i A') }} &middot; internal use only</footer>

<script>
    window.addEventListener('load', () => {
        setTimeout(() => window.print(), 250);
    });
</script>
</body>
</html>
