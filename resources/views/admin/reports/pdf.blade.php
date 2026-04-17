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

<h1>CarpoolHub Admin Report</h1>
<div class="meta">Generated at: {{ now()->format('Y-m-d H:i:s') }}</div>

<h2>Overview</h2>
<div class="grid">
    <div class="card"><div class="label">Total Users</div><div class="value">{{ $overview['users_total'] }}</div></div>
    <div class="card"><div class="label">Drivers</div><div class="value">{{ $overview['drivers_total'] }}</div></div>
    <div class="card"><div class="label">Passengers</div><div class="value">{{ $overview['passengers_total'] }}</div></div>
    <div class="card"><div class="label">Active Users</div><div class="value">{{ $overview['active_users_total'] }}</div></div>
    <div class="card"><div class="label">Total Trips</div><div class="value">{{ $overview['trips_total'] }}</div></div>
    <div class="card"><div class="label">Completed Trips</div><div class="value">{{ $overview['trips_completed'] }}</div></div>
    <div class="card"><div class="label">Fare Total</div><div class="value">RM {{ number_format((float) $overview['fare_total'], 2) }}</div></div>
    <div class="card"><div class="label">Payments Total</div><div class="value">RM {{ number_format((float) $overview['payments_total'], 2) }}</div></div>
</div>

<h2>Payment Breakdown</h2>
<table>
    <thead>
    <tr>
        <th>Status</th>
        <th class="right">Count</th>
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

<h2>Billing Cycle Financial Summary</h2>
<table>
    <thead>
    <tr>
        <th>Month</th>
        <th>Status</th>
        <th class="right">Trips</th>
        <th class="right">Fare Total</th>
        <th class="right">Paid</th>
        <th class="right">Pending/Unpaid</th>
    </tr>
    </thead>
    <tbody>
    @forelse($cycleReports as $cycle)
        <tr>
            <td>{{ $cycle->month_key }}</td>
            <td>{{ ucfirst($cycle->status) }}</td>
            <td class="right">{{ $cycle->report_trip_count }}</td>
            <td class="right">RM {{ number_format((float) $cycle->report_fare_total, 2) }}</td>
            <td class="right">RM {{ number_format((float) $cycle->report_paid_total, 2) }}</td>
            <td class="right">RM {{ number_format((float) $cycle->report_pending_unpaid_total, 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6">No cycle data.</td>
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

