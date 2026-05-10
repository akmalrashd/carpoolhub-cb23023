@extends('layouts.app')

@section('content')
    <style>
        .archive-page { display: grid; gap: 12px; }
        .archive-card { background: #fff; border: 1px solid #dbe2ea; border-radius: 16px; padding: 14px; }
        .archive-title { margin: 0; font-family: Poppins, sans-serif; font-size: 30px; color: #0f172a; line-height: 1.05; }
        .archive-subtitle { margin: 6px 0 0; color: #64748b; font-size: 14px; }
        .archive-filter-form { display: grid; gap: 10px; }
        .archive-filter-grid { display: grid; gap: 10px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .archive-label { font-size: 12px; color: #475569; font-weight: 700; }
        .archive-select {
            width: 100%;
            border: 1px solid #dbe2ea;
            border-radius: 11px;
            background: #f8fafc;
            color: #0f172a;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
        }
        .archive-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .archive-btn {
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            padding: 9px 12px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .archive-btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .archive-summary-grid { display: grid; gap: 8px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .archive-summary-item { border: 1px solid #dbe2ea; border-radius: 12px; background: #f8fafc; padding: 10px; display: grid; gap: 2px; }
        .archive-summary-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
        .archive-summary-value { font-size: 20px; color: #0f172a; font-weight: 700; line-height: 1.15; }
        .archive-summary-note { font-size: 12px; color: #64748b; }
        .archive-link-grid { display: grid; gap: 8px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .archive-link-card {
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            padding: 14px;
            display: grid;
            gap: 8px;
        }
        .archive-link-title { margin: 0; color: #0f172a; font-size: 16px; font-weight: 700; }
        .archive-link-text { margin: 0; color: #64748b; font-size: 13px; line-height: 1.45; }

        @media (min-width: 768px) {
            .archive-filter-grid { grid-template-columns: 1fr auto; align-items: end; }
            .archive-summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .archive-link-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

    <div class="archive-page">
        <section class="archive-card">
            <h1 class="archive-title">Archive</h1>
            <p class="archive-subtitle">Review closed-cycle trips and payments by month using the same workflow as active records, but in read-only mode.</p>
        </section>

        <section class="archive-card">
            <form method="GET" action="{{ route('archive.index') }}" class="archive-filter-form">
                <div class="archive-filter-grid">
                    <div>
                        <label class="archive-label" for="month">Month</label>
                        <select class="archive-select" name="month" id="month">
                            <option value="" disabled {{ $monthKey ? '' : 'selected' }}>Select archive month</option>
                            @foreach($months as $month)
                                <option value="{{ $month }}" {{ $monthKey === $month ? 'selected' : '' }}>{{ $month }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="archive-actions">
                        <button type="submit" class="archive-btn primary"><i class="fa-solid fa-filter"></i>Apply</button>
                        <a href="{{ route('archive.index') }}" class="archive-btn">Reset</a>
                    </div>
                </div>
            </form>
        </section>

        <section class="archive-card">
            <div class="archive-summary-grid">
                <div class="archive-summary-item">
                    <span class="archive-summary-label">Trip</span>
                    <span class="archive-summary-value">{{ number_format((int) ($summary['trip_count'] ?? 0)) }}</span>
                    <span class="archive-summary-note">Archived trips for the selected month</span>
                </div>
                <div class="archive-summary-item">
                    <span class="archive-summary-label">Payments</span>
                    <span class="archive-summary-value">{{ number_format((int) ($summary['payment_count'] ?? 0)) }}</span>
                    <span class="archive-summary-note">Archived payment records</span>
                </div>
                <div class="archive-summary-item">
                    <span class="archive-summary-label">Total Fare</span>
                    <span class="archive-summary-value">RM {{ number_format((float) ($summary['fare_total'] ?? 0), 2) }}</span>
                    <span class="archive-summary-note">Total archived trip fare</span>
                </div>
                <div class="archive-summary-item">
                    <span class="archive-summary-label">Paid</span>
                    <span class="archive-summary-value">RM {{ number_format((float) ($summary['paid_total'] ?? 0), 2) }}</span>
                    <span class="archive-summary-note">Confirmed archived payments</span>
                </div>
                <div class="archive-summary-item">
                    <span class="archive-summary-label">Pending</span>
                    <span class="archive-summary-value">RM {{ number_format((float) ($summary['pending_total'] ?? 0), 2) }}</span>
                    <span class="archive-summary-note">Records pending confirmation</span>
                </div>
                <div class="archive-summary-item">
                    <span class="archive-summary-label">Unpaid</span>
                    <span class="archive-summary-value">RM {{ number_format((float) ($summary['unpaid_total'] ?? 0), 2) }}</span>
                    <span class="archive-summary-note">Outstanding archived records</span>
                </div>
            </div>
        </section>

        <section class="archive-card">
            <div class="archive-link-grid">
                <article class="archive-link-card">
                    <h2 class="archive-link-title">Archived Trips</h2>
                    <p class="archive-link-text">Open the trip-style interface for the selected archive month. Cards and trip detail popups remain available in read-only mode.</p>
                    <a href="{{ route('archive.trips.index', ['month' => $monthKey]) }}" class="archive-btn primary"><i class="fa-solid fa-route"></i>Open Archive Trip</a>
                </article>
                <article class="archive-link-card">
                    <h2 class="archive-link-title">Archived Payments</h2>
                    <p class="archive-link-text">Open the payment-style interface for the selected archive month with monthly summaries and read-only trip detail popups.</p>
                    <a href="{{ route('archive.payments.index', ['month' => $monthKey]) }}" class="archive-btn primary"><i class="fa-solid fa-wallet"></i>Open Archive Payments</a>
                </article>
            </div>
        </section>
    </div>
@endsection
