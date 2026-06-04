<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarpoolHub Admin Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111; }
        h1, h2 { margin: 0 0 10px; }
        .meta { margin-bottom: 16px; color: #555; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(150px, 1fr)); gap: 8px; margin-bottom: 16px; }
        .card { border: 1px solid #ddd; border-radius: 6px; padding: 8px; }
        .label { font-size: 12px; color: #666; }
        .value { font-size: 16px; font-weight: 700; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #f2f2f2; }
        .right { text-align: right; }
        @media print {
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 14mm; }
        }
    </style>
</head>
<body>
<div class="no-print" style="margin-bottom:12px;">
    <button onclick="window.print()">Print / Save as PDF</button>
</div>

<h1>Reports Admin CarpoolHub</h1>
<div class="meta">Generated at: {{ now()->format('Y-m-d H:i:s') }}</div>

<h2>Gambaran Keseluruhan</h2>
<div class="grid">
    <div class="card"><div class="label">Total Users</div><div class="value">{{ $overview['users_total'] }}</div></div>
    <div class="card"><div class="label">Driver</div><div class="value">{{ $overview['drivers_total'] }}</div></div>
    <div class="card"><div class="label">Passengers</div><div class="value">{{ $overview['passengers_total'] }}</div></div>
    <div class="card"><div class="label">Active Users</div><div class="value">{{ $overview['active_users_total'] }}</div></div>
    <div class="card"><div class="label">Total Trips</div><div class="value">{{ $overview['trips_total'] }}</div></div>
    <div class="card"><div class="label">Completed Trips</div><div class="value">{{ $overview['trips_completed'] }}</div></div>
    <div class="card"><div class="label">Total Fare</div><div class="value">RM {{ number_format((float) $overview['fare_total'], 2) }}</div></div>
    <div class="card"><div class="label">Total Payments</div><div class="value">RM {{ number_format((float) $overview['payments_total'], 2) }}</div></div>
</div>

<h2>Pecahan Payments</h2>
<table>
    <thead>
    <tr>
        <th>Status</th>
        <th class="right">Bilangan</th>
        <th class="right">Amount</th>
    </tr>
    </thead>
    <tbody>
    @foreach($paymentBreakdown as $status => $row)
        <tr>
            <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
            <td class="right">{{ $row['count'] }}</td>
            <td class="right">RM {{ number_format((float) $row['amount'], 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>Thesis Module Evidence</h2>
<table>
    <thead>
    <tr>
        <th>Module</th>
        <th class="right">Evidence</th>
        <th>Unit</th>
    </tr>
    </thead>
    <tbody>
    @foreach($thesisAlignment as $row)
        <tr>
            <td>{{ $row['objective'] }}</td>
            <td class="right">{{ $row['evidence'] }}</td>
            <td>{{ $row['unit'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>Custom Route Preference</h2>
<div class="grid">
    <div class="card"><div class="label">Custom Requests</div><div class="value">{{ $customRouteSummary['custom_requests'] }}</div></div>
    <div class="card"><div class="label">Custom Share</div><div class="value">{{ $customRouteSummary['custom_share'] }}%</div></div>
    <div class="card"><div class="label">Avg Detour</div><div class="value">{{ $customRouteSummary['avg_detour_km'] }} km</div></div>
    <div class="card"><div class="label">Avg Extra Fee</div><div class="value">RM {{ number_format((float) $customRouteSummary['avg_extra_fee'], 2) }}</div></div>
</div>

<h2>Decision Support</h2>
<div class="grid">
    <div class="card"><div class="label">Join Requests</div><div class="value">{{ $requestSummary['total'] }}</div></div>
    <div class="card"><div class="label">Approval Rate</div><div class="value">{{ $requestSummary['approval_rate'] }}%</div></div>
    <div class="card"><div class="label">AI Recommendations</div><div class="value">{{ $aiSupportSummary['recommendation_logs'] }}</div></div>
    <div class="card"><div class="label">Avg Risk Score</div><div class="value">{{ $reliabilitySummary['avg_risk_score'] }}</div></div>
</div>

<h2>Top Routes</h2>
<table>
    <thead>
    <tr>
        <th>Route</th>
        <th class="right">Trips</th>
        <th class="right">Avg Fare</th>
        <th class="right">Drivers</th>
    </tr>
    </thead>
    <tbody>
    @forelse($topRoutes as $row)
        <tr>
            <td>{{ $row['route_name'] }}</td>
            <td class="right">{{ $row['trip_count'] }}</td>
            <td class="right">RM {{ number_format((float) $row['avg_fare'], 2) }}</td>
            <td class="right">{{ $row['driver_count'] }}</td>
        </tr>
    @empty
        <tr><td colspan="4">No data available.</td></tr>
    @endforelse
    </tbody>
</table>

<h2>Monthly Trip Summary</h2>
<table>
    <thead>
    <tr>
        <th>Month</th>
        <th class="right">Trips</th>
        <th class="right">Total Fare</th>
        <th class="right">Paid</th>
        <th class="right">Pending/Unpaid</th>
    </tr>
    </thead>
    <tbody>
    @forelse($monthlyReports as $row)
        <tr>
            <td>{{ $row['month_key'] }}</td>
            <td class="right">{{ $row['trip_count'] }}</td>
            <td class="right">RM {{ number_format((float) $row['fare_total'], 2) }}</td>
            <td class="right">RM {{ number_format((float) $row['paid_total'], 2) }}</td>
            <td class="right">RM {{ number_format((float) $row['pending_unpaid_total'], 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5">No data available.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<script>
    window.addEventListener('load', () => {
        setTimeout(() => window.print(), 250);
    });
</script>
</body>
</html>
