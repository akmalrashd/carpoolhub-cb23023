@extends('layouts.app')

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
        <h1 style="margin:0; font-family:Poppins, sans-serif;">Reports</h1>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.reports.export.csv') }}"
               style="background:#FACC15; color:#111827; text-decoration:none; border-radius:8px; padding:8px 12px; font-weight:700;">
                Export CSV
            </a>
            <a href="{{ route('admin.reports.export.pdf') }}" target="_blank"
               style="background:transparent; color:#F3F4F6; text-decoration:none; border:1px solid #374151; border-radius:8px; padding:8px 12px; font-weight:700;">
                Export PDF
            </a>
        </div>
    </div>

    <div style="display:grid; gap:12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:14px;">
        <div class="card-dark" style="padding:14px;"><div style="color:#9CA3AF;">Total Users</div><div style="font-weight:700;">{{ $overview['users_total'] }}</div></div>
        <div class="card-dark" style="padding:14px;"><div style="color:#9CA3AF;">Driver</div><div style="font-weight:700;">{{ $overview['drivers_total'] }}</div></div>
        <div class="card-dark" style="padding:14px;"><div style="color:#9CA3AF;">Passengers</div><div style="font-weight:700;">{{ $overview['passengers_total'] }}</div></div>
        <div class="card-dark" style="padding:14px;"><div style="color:#9CA3AF;">Active Users</div><div style="font-weight:700;">{{ $overview['active_users_total'] }}</div></div>
        <div class="card-dark" style="padding:14px;"><div style="color:#9CA3AF;">Total Trips</div><div style="font-weight:700;">{{ $overview['trips_total'] }}</div></div>
        <div class="card-dark" style="padding:14px;"><div style="color:#9CA3AF;">Completed Trips</div><div style="font-weight:700;">{{ $overview['trips_completed'] }}</div></div>
        <div class="card-dark" style="padding:14px;"><div style="color:#9CA3AF;">Total Fare</div><div style="font-weight:700;">RM {{ number_format((float) $overview['fare_total'], 2) }}</div></div>
        <div class="card-dark" style="padding:14px;"><div style="color:#9CA3AF;">Total Payments</div><div style="font-weight:700;">RM {{ number_format((float) $overview['payments_total'], 2) }}</div></div>
    </div>

    <div style="display:grid; gap:12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:14px;">
        <div class="card-dark" style="padding:14px;">
            <div style="color:#9CA3AF;">Unpaid</div>
            <div style="font-weight:700; color:#EF4444;">{{ $paymentBreakdown['unpaid']['count'] }} / RM {{ number_format((float) $paymentBreakdown['unpaid']['amount'], 2) }}</div>
        </div>
        <div class="card-dark" style="padding:14px;">
            <div style="color:#9CA3AF;">Pending Confirmation</div>
            <div style="font-weight:700; color:#FACC15;">{{ $paymentBreakdown['pending_confirmation']['count'] }} / RM {{ number_format((float) $paymentBreakdown['pending_confirmation']['amount'], 2) }}</div>
        </div>
        <div class="card-dark" style="padding:14px;">
            <div style="color:#9CA3AF;">Paid</div>
            <div style="font-weight:700; color:#22C55E;">{{ $paymentBreakdown['paid']['count'] }} / RM {{ number_format((float) $paymentBreakdown['paid']['amount'], 2) }}</div>
        </div>
    </div>

    <div class="card-dark" style="padding:16px;">
        <h2 style="margin:0 0 10px; font-size:16px;">Billing Cycle Financial Summary</h2>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                <tr>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid #374151;">Month</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid #374151;">Status</th>
                    <th style="text-align:right; padding:10px; border-bottom:1px solid #374151;">Trip</th>
                    <th style="text-align:right; padding:10px; border-bottom:1px solid #374151;">Total Fare</th>
                    <th style="text-align:right; padding:10px; border-bottom:1px solid #374151;">Paid</th>
                    <th style="text-align:right; padding:10px; border-bottom:1px solid #374151;">Pending/Unpaid</th>
                </tr>
                </thead>
                <tbody>
                @forelse($cycleReports as $cycle)
                    <tr>
                        <td style="padding:10px; border-bottom:1px solid #374151;">{{ $cycle->month_key }}</td>
                        <td style="padding:10px; border-bottom:1px solid #374151;">{{ ucfirst($cycle->status) }}</td>
                        <td style="padding:10px; border-bottom:1px solid #374151; text-align:right;">{{ $cycle->report_trip_count }}</td>
                        <td style="padding:10px; border-bottom:1px solid #374151; text-align:right;">RM {{ number_format((float) $cycle->report_fare_total, 2) }}</td>
                        <td style="padding:10px; border-bottom:1px solid #374151; text-align:right; color:#22C55E;">RM {{ number_format((float) $cycle->report_paid_total, 2) }}</td>
                        <td style="padding:10px; border-bottom:1px solid #374151; text-align:right; color:#FACC15;">RM {{ number_format((float) $cycle->report_pending_unpaid_total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:10px; color:#9CA3AF;">No cycles available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $cycleReports->links() }}</div>
    </div>
@endsection
