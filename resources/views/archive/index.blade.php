@extends('layouts.app')

@section('content')
    <style>
        .archive-page { display: grid; gap: 16px; }

        /* ── Hero ── */
        .archive-hero {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: var(--shadow-1);
        }

        .archive-title {
            margin: 0;
            font-family: var(--font-display), sans-serif;
            font-size: clamp(1.5rem, 2.2vw, 1.9rem);
            color: var(--ink);
            line-height: 1.05;
        }

        .archive-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        /* ── Card Shell ── */
        .archive-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 20px;
            box-shadow: var(--shadow-1);
        }

        .archive-card-title {
            margin: 0 0 14px;
            font-family: var(--font-display), sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
        }

        /* ── Filter ── */
        .archive-filter-form { display: grid; gap: 12px; }

        .archive-filter-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr;
        }

        .archive-label {
            display: block;
            font-size: 12px;
            color: var(--ink-3);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .archive-select {
            width: 100%;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            color: var(--ink);
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
            transition: border-color .15s;
        }

        .archive-select:focus { border-color: var(--ch-yellow); }

        .archive-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end; }

        /* ── KPI Grid ── */
        .archive-summary-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr;
        }

        .archive-summary-item {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 14px;
            display: grid;
            gap: 3px;
        }

        .archive-summary-label {
            font-size: 11px;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .archive-summary-value {
            font-size: 22px;
            color: var(--ink);
            font-weight: 800;
            line-height: 1.15;
            font-family: var(--font-ui), sans-serif;
        }

        .archive-summary-note {
            font-size: 12px;
            color: var(--muted);
        }

        /* ── Navigation Cards ── */
        .archive-link-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr;
        }

        .archive-link-card {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: linear-gradient(135deg, var(--surface-2) 0%, var(--surface) 100%);
            padding: 18px;
            display: grid;
            gap: 10px;
        }

        .archive-link-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--r-sm);
            background: var(--ch-yellow-tint);
            border: 1px solid var(--ch-yellow-line);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--ch-yellow-ink);
            font-size: 16px;
        }

        .archive-link-title {
            margin: 0;
            color: var(--ink);
            font-size: 16px;
            font-weight: 700;
            font-family: var(--font-display), sans-serif;
        }

        .archive-link-text {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        /* ── Responsive ── */
        @media (min-width: 768px) {
            .archive-filter-grid { grid-template-columns: 1fr auto; align-items: end; }
            .archive-summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .archive-link-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

    <div class="archive-page">

        {{-- Hero --}}
        <section class="archive-hero">
            <div>
                <h1 class="archive-title">Archive</h1>
                <p class="archive-subtitle">Review closed-cycle trips and payments by month — read-only mode.</p>
            </div>
        </section>

        {{-- Month Filter --}}
        <section class="archive-card">
            <p class="archive-card-title">Select Archive Month</p>
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
                        <button type="submit" class="btn btn-dark btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
                        <a href="{{ route('archive.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    </div>
                </div>
            </form>
        </section>

        {{-- Summary KPIs --}}
        <section class="archive-card">
            <p class="archive-card-title">Summary</p>
            <div class="archive-summary-grid">
                <div class="archive-summary-item">
                    <span class="archive-summary-label">Trips</span>
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
                    <span class="archive-summary-value" style="color:var(--success);">RM {{ number_format((float) ($summary['paid_total'] ?? 0), 2) }}</span>
                    <span class="archive-summary-note">Confirmed archived payments</span>
                </div>
                <div class="archive-summary-item">
                    <span class="archive-summary-label">Pending</span>
                    <span class="archive-summary-value" style="color:var(--info);">RM {{ number_format((float) ($summary['pending_total'] ?? 0), 2) }}</span>
                    <span class="archive-summary-note">Records pending confirmation</span>
                </div>
                <div class="archive-summary-item">
                    <span class="archive-summary-label">Unpaid</span>
                    <span class="archive-summary-value" style="color:var(--danger);">RM {{ number_format((float) ($summary['unpaid_total'] ?? 0), 2) }}</span>
                    <span class="archive-summary-note">Outstanding archived records</span>
                </div>
            </div>
        </section>

        {{-- Navigation Cards --}}
        <section class="archive-card">
            <p class="archive-card-title">Browse Archives</p>
            <div class="archive-link-grid">
                <article class="archive-link-card">
                    <span class="archive-link-icon"><i class="fa-solid fa-route"></i></span>
                    <h2 class="archive-link-title">Archived Trips</h2>
                    <p class="archive-link-text">Open the trip-style interface for the selected archive month. Cards and trip detail popups are available in read-only mode.</p>
                    <div>
                        <a href="{{ route('archive.trips.index', ['month' => $monthKey]) }}" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-route"></i> View Archived Trips
                        </a>
                    </div>
                </article>
                <article class="archive-link-card">
                    <span class="archive-link-icon"><i class="fa-solid fa-wallet"></i></span>
                    <h2 class="archive-link-title">Archived Payments</h2>
                    <p class="archive-link-text">Open the payment-style interface for the selected archive month with monthly summaries and read-only trip detail popups.</p>
                    <div>
                        <a href="{{ route('archive.payments.index', ['month' => $monthKey]) }}" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-wallet"></i> View Archived Payments
                        </a>
                    </div>
                </article>
            </div>
        </section>

    </div>
@endsection
