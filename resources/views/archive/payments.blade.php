@extends('layouts.app')

@section('content')
    <style>
        .archive-payments-page { display: grid; gap: 16px; }

        /* ── Shared card shell ── */
        .archive-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 20px;
            box-shadow: var(--shadow-1);
        }

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
            font-size: clamp(1.4rem, 2vw, 1.8rem);
            color: var(--ink);
            line-height: 1.05;
        }

        .archive-subtitle { margin: 6px 0 0; color: var(--muted); font-size: 14px; }

        /* ── Filter ── */
        .archive-filter-row {
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
        }

        .archive-select:focus { border-color: var(--ch-yellow); }

        .archive-input {
            width: 100%;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface);
            color: var(--ink);
            padding: 9px 12px;
            font-size: 13px;
            outline: none;
        }

        .archive-input:focus { border-color: var(--ch-yellow); }

        /* ── Notices ── */
        .archive-notice {
            border-radius: var(--r-md);
            padding: 12px 16px;
            font-size: 14px;
        }

        .archive-notice.success {
            border: 1px solid #86efac;
            background: var(--success-soft);
            color: var(--success-ink);
        }

        .archive-notice.error {
            border: 1px solid #fecaca;
            background: var(--danger-soft);
            color: var(--danger-ink);
        }

        /* ── Section labels ── */
        .archive-section-title {
            margin: 0 0 6px;
            color: var(--ink);
            font-size: 17px;
            font-weight: 700;
            font-family: var(--font-display), sans-serif;
        }

        .archive-section-subtitle { margin: 0 0 14px; color: var(--muted); font-size: 14px; }

        /* ── Summary KPIs ── */
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
            font-size: 20px;
            color: var(--ink);
            font-weight: 800;
            font-family: var(--font-ui), sans-serif;
        }

        .archive-summary-count { font-size: 12px; color: var(--muted); }

        /* ── Empty state ── */
        .archive-empty {
            border: 1px dashed var(--hairline-strong);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 32px;
            color: var(--muted);
            font-size: 14px;
            text-align: center;
        }

        /* ── Payment row detail lines ── */
        .archive-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            padding: 7px 10px;
            font-size: 12px;
            color: var(--ink-3);
        }

        .archive-line strong { color: var(--ink); text-align: right; }

        /* ── Status chips ── */
        .status-chip {
            display: inline-flex;
            align-items: center;
            border-radius: var(--r-pill);
            border: 1px solid var(--hairline);
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-unpaid {
            color: var(--danger-ink);
            border-color: #fecaca;
            background: var(--danger-soft);
        }

        .status-pending_confirmation {
            color: var(--info-ink);
            border-color: #bfdbfe;
            background: var(--info-soft);
        }

        .status-paid {
            color: var(--success-ink);
            border-color: #86efac;
            background: var(--success-soft);
        }

        /* ── Action status notes ── */
        .archive-payments-status-note { font-size: 12px; font-weight: 700; }
        .archive-payments-status-note.pending { color: var(--warning); }
        .archive-payments-status-note.done { color: var(--success); }

        /* ── Mobile card list ── */
        .archive-payments-mobile-list { display: grid; gap: 12px; }

        .archive-payments-mobile-card {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface);
            padding: 16px;
            display: grid;
            gap: 10px;
        }

        .archive-payments-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .archive-payments-trip-id { color: var(--ink); font-size: 15px; font-weight: 800; line-height: 1.2; }
        .archive-payments-route { color: var(--muted); font-size: 13px; line-height: 1.4; }

        .archive-payments-link {
            border: 0;
            background: transparent;
            color: var(--muted);
            padding: 0;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .archive-payments-link:hover { color: var(--ink); }

        .archive-payments-grid { display: grid; gap: 8px; }

        .archive-payments-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            padding: 10px 12px;
            font-size: 13px;
            color: var(--ink-3);
        }

        .archive-payments-line strong { color: var(--ink); text-align: right; }

        /* ── Inline payment form ── */
        .archive-payments-action-form { display: grid; gap: 10px; }
        .archive-payments-action-row { display: grid; gap: 10px; }

        .archive-payments-action-group {
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 42px;
            align-items: center;
        }

        .archive-payments-soft-btn {
            width: 42px;
            height: 42px;
            border-radius: var(--r-sm);
            border: 1px solid var(--hairline);
            background: var(--surface);
            color: var(--ink-3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .archive-payments-soft-btn:hover { background: var(--surface-2); }

        .archive-payments-submit-wrap { display: flex; justify-content: flex-end; }

        /* ── Desktop table ── */
        .archive-payments-table-wrap { display: none; }

        .archive-payments-table { width: 100%; border-collapse: collapse; }

        .archive-payments-table th {
            text-align: left;
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 0 14px 14px;
            border-bottom: 1px solid var(--hairline);
            background: var(--surface-2);
        }

        .archive-payments-table td {
            vertical-align: top;
            padding: 14px;
            border-bottom: 1px solid var(--hairline);
            color: var(--ink-3);
            font-size: 13px;
        }

        .archive-payments-table tbody tr:hover td { background: var(--ch-yellow-tint); }
        .archive-payments-table tbody tr:last-child td { border-bottom: 0; }

        .archive-payments-table th.right,
        .archive-payments-table td.right { text-align: right; }

        .archive-payment-trip { font-size: 14px; font-weight: 700; color: var(--ink); line-height: 1.3; }
        .archive-payment-sub { margin-top: 2px; font-size: 12px; color: var(--muted); line-height: 1.35; }

        /* ── Driver Details Modal ── */
        .archive-modal {
            position: fixed;
            inset: 0;
            background: rgba(11, 18, 32, .54);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 2600;
        }

        .archive-modal.show { display: flex; }

        .archive-modal-card {
            width: min(700px, 100%);
            max-height: 84vh;
            overflow: auto;
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            background: var(--surface);
            padding: 20px;
            display: grid;
            gap: 12px;
            box-shadow: var(--shadow-3);
        }

        .archive-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .archive-modal-title {
            margin: 0;
            color: var(--ink);
            font-size: 18px;
            font-weight: 700;
            font-family: var(--font-display), sans-serif;
        }

        .archive-modal-close {
            width: 34px;
            height: 34px;
            border-radius: var(--r-sm);
            border: 1px solid var(--hairline);
            background: var(--surface);
            color: var(--ink-3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }

        .archive-modal-line {
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            padding: 10px 12px;
            display: grid;
            gap: 2px;
        }

        .archive-modal-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .archive-modal-value { color: var(--ink); font-size: 13px; font-weight: 600; word-break: break-word; }

        /* ── Point cards ── */
        .archive-point-grid { display: grid; gap: 8px; grid-template-columns: 1fr; }

        .archive-point-card {
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            padding: 10px 12px;
            display: grid;
            gap: 3px;
        }

        .archive-point-card.pickup { border-color: #bbf7d0; background: var(--success-soft); }
        .archive-point-card.destination { border-color: #bfdbfe; background: var(--info-soft); }

        .archive-point-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .archive-point-card.pickup .archive-point-label { color: var(--success-ink); }
        .archive-point-card.destination .archive-point-label { color: var(--info-ink); }

        .archive-point-value { color: var(--ink); font-size: 13px; font-weight: 700; line-height: 1.3; }

        /* ── Driver profile block ── */
        .archive-driver-scroll { display: grid; gap: 12px; }

        .archive-driver-profile {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .archive-driver-avatar {
            width: 48px;
            height: 48px;
            border-radius: var(--r-pill);
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            font-size: 20px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 auto;
        }

        .archive-driver-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .archive-driver-meta { display: grid; gap: 2px; min-width: 0; }
        .archive-driver-name { color: var(--ink); font-size: 16px; font-weight: 800; line-height: 1.2; }
        .archive-driver-email { color: var(--muted); font-size: 13px; line-height: 1.35; word-break: break-word; }

        .archive-driver-grid { display: grid; gap: 10px; grid-template-columns: 1fr; }

        .archive-driver-line {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 14px;
            display: grid;
            gap: 6px;
        }

        .archive-driver-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .archive-driver-value { color: var(--ink); font-size: 15px; font-weight: 700; word-break: break-word; }

        .archive-driver-qr-grid { display: grid; gap: 10px; grid-template-columns: 1fr; }

        .archive-driver-qr-card {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 14px;
            display: grid;
            gap: 10px;
        }

        .archive-driver-qr-title {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .archive-driver-qr-preview {
            min-height: 180px;
            border: 1px dashed var(--hairline-strong);
            border-radius: var(--r-md);
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .archive-driver-qr-preview img { width: 100%; height: 100%; object-fit: contain; }
        .archive-driver-qr-empty { color: var(--muted-2); font-size: 14px; font-weight: 700; }

        .archive-action-row { display: flex; gap: 8px; flex-wrap: wrap; }

        /* ── Responsive ── */
        @media (min-width: 768px) {
            .archive-filter-row { grid-template-columns: 1fr auto auto; align-items: end; }
            .archive-summary-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .archive-point-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .archive-payments-mobile-list { display: none; }
            .archive-payments-table-wrap { display: block; overflow-x: auto; }
            .archive-payments-action-group { grid-template-columns: 170px 150px 40px auto; }
            .archive-driver-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .archive-driver-qr-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

    <div class="archive-payments-page">

        {{-- Notices --}}
        @if(session('status'))
            <div class="archive-notice success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="archive-notice error">{{ $errors->first() }}</div>
        @endif

        {{-- Hero --}}
        <section class="archive-hero">
            <div>
                <h1 class="archive-title">Archived Payments</h1>
                <p class="archive-subtitle">Read-only payment history for archived billing cycles.</p>
            </div>
            <a href="{{ route('archive.index', ['month' => $monthKey]) }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Archive
            </a>
        </section>

        {{-- Month Filter --}}
        <section class="archive-card">
            <form method="GET" action="{{ route('archive.payments.index') }}" class="archive-filter-row">
                <div>
                    <label class="archive-label" for="month">Month</label>
                    <select class="archive-select" name="month" id="month">
                        <option value="" disabled {{ $monthKey ? '' : 'selected' }}>Select archive month</option>
                        @foreach($months as $month)
                            <option value="{{ $month['value'] }}" {{ $monthKey === $month['value'] ? 'selected' : '' }}>{{ $month['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-dark btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
                <a href="{{ route('archive.payments.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </form>
        </section>

        @if(! $monthKey)
            <section class="archive-card">
                <div class="archive-empty">
                    <i class="fa-solid fa-calendar" style="font-size:24px; margin-bottom:8px; display:block; color:var(--muted-2);"></i>
                    Select an archive month first to load payment summaries and records.
                </div>
            </section>
        @else
            {{-- Payment Summary --}}
            <section class="archive-card">
                <h2 class="archive-section-title">Payment Summary</h2>
                <div class="archive-summary-grid">
                    <div class="archive-summary-item">
                        <span class="archive-summary-label">My Unpaid</span>
                        <span class="archive-summary-value" style="color:var(--danger);">RM {{ number_format((float) ($summary['my']['unpaid']['amount'] ?? 0), 2) }}</span>
                        <span class="archive-summary-count">{{ $summary['my']['unpaid']['count'] ?? 0 }} records</span>
                    </div>
                    <div class="archive-summary-item">
                        <span class="archive-summary-label">My Pending</span>
                        <span class="archive-summary-value" style="color:var(--info);">RM {{ number_format((float) ($summary['my']['pending_confirmation']['amount'] ?? 0), 2) }}</span>
                        <span class="archive-summary-count">{{ $summary['my']['pending_confirmation']['count'] ?? 0 }} records</span>
                    </div>
                    <div class="archive-summary-item">
                        <span class="archive-summary-label">My Paid</span>
                        <span class="archive-summary-value" style="color:var(--success);">RM {{ number_format((float) ($summary['my']['paid']['amount'] ?? 0), 2) }}</span>
                        <span class="archive-summary-count">{{ $summary['my']['paid']['count'] ?? 0 }} records</span>
                    </div>
                    <div class="archive-summary-item">
                        <span class="archive-summary-label">Review Queue</span>
                        <span class="archive-summary-value">RM {{ number_format((float) ($summary['driver']['total']['amount'] ?? 0), 2) }}</span>
                        <span class="archive-summary-count">{{ $summary['driver']['total']['count'] ?? 0 }} records</span>
                    </div>
                </div>
            </section>

            {{-- My Archived Payments --}}
            <section class="archive-card">
                <h2 class="archive-section-title">My Archived Payments</h2>
                <p class="archive-section-subtitle">Mark your archived unpaid records and track confirmation.</p>

                @if($myPayments->isEmpty())
                    <div class="archive-empty">No archived payment records found for your account.</div>
                @else
                    {{-- Mobile card list --}}
                    <div class="archive-payments-mobile-list">
                        @foreach($myPayments as $payment)
                            @php
                                $trip = $payment->archivedTrip;
                                $pickupName = $trip?->pickup_name ?? '-';
                                $pickupLat = $trip?->pickup_latitude ?? '';
                                $pickupLng = $trip?->pickup_longitude ?? '';
                                $destinationName = $trip?->destination_name ?? '-';
                                $destinationLat = $trip?->destination_latitude ?? '';
                                $destinationLng = $trip?->destination_longitude ?? '';
                                $routeName = $trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                                $statusSlug = strtolower((string) $payment->payment_status);
                                $statusTextMap = ['unpaid' => 'Unpaid', 'pending_confirmation' => 'Pending Confirmation', 'paid' => 'Paid'];
                                $statusText = $statusTextMap[$payment->payment_status] ?? ucfirst((string) $payment->payment_status);
                                $driverPhotoUrl = $trip?->driver?->profile_photo
                                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($trip->driver->profile_photo)
                                    : '';
                                $driverBank = $trip?->driver?->payment_bank_name ?: '-';
                                $driverAccountName = $trip?->driver?->payment_account_name ?: '-';
                                $driverAccountNumber = $trip?->driver?->payment_account_number ?: '-';
                                $driverDuitnowQr = $trip?->driver?->payment_qr_duitnow_url ?: '';
                                $driverTngQr = $trip?->driver?->payment_qr_tng_url ?: '';
                            @endphp
                            <article class="archive-payments-mobile-card">
                                <div class="archive-payments-mobile-top">
                                    <div>
                                        <div class="archive-payments-trip-id">Trip #{{ $trip?->id ?? '—' }}</div>
                                        <div class="archive-payments-route">{{ $routeName }}</div>
                                        <button
                                            type="button"
                                            class="archive-payments-link open-archive-payment-trip-modal"
                                            data-route-name="{{ $routeName }}"
                                            data-trip-ids="#{{ $trip?->id ?? '—' }}"
                                            data-driver="{{ $trip?->driver?->name ?: '—' }}"
                                            data-datetime="{{ $trip?->trip_datetime?->format('Y-m-d H:i') ?: '—' }}"
                                            data-pickup="{{ $pickupName }}"
                                            data-destination="{{ $destinationName }}"
                                            data-mode="{{ ((string) ($trip?->trip_mode ?? 'one_way')) === 'two_way' ? 'Two-way' : 'One-way' }}"
                                            data-fare="RM {{ number_format((float) (($trip?->fare_total ?? 0) + ($trip?->returnTrip?->fare_total ?? 0)), 2) }}"
                                        ><i class="fa-regular fa-eye"></i><span>View Details</span></button>
                                    </div>
                                    <span class="status-chip status-{{ $statusSlug }}">{{ $statusText }}</span>
                                </div>
                                <div class="archive-payments-grid">
                                    <div class="archive-payments-line"><span>Driver</span><strong>{{ $trip?->driver?->name ?: '—' }}</strong></div>
                                    <div class="archive-payments-line"><span>Amount Due</span><strong>RM {{ number_format((float) $payment->amount_due, 2) }}</strong></div>
                                </div>
                                <div class="archive-payments-action-row">
                                    @if($payment->payment_status === 'unpaid')
                                        <form method="POST" action="{{ route('archive.payments.mark-paid', $payment) }}" class="archive-payments-action-form">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="month" value="{{ $monthKey }}">
                                            <div class="archive-payments-action-group">
                                                <select class="archive-select" name="payment_method" required>
                                                    <option value="" disabled selected>Select method</option>
                                                    <option value="duitnow_qr">DuitNow QR</option>
                                                    <option value="bank_account">Bank Account</option>
                                                    <option value="digital_wallet">Digital Wallet</option>
                                                    <option value="others">Others</option>
                                                </select>
                                                <input class="archive-input" type="text" name="remarks" placeholder="Remarks">
                                                <button
                                                    type="button"
                                                    class="archive-payments-soft-btn open-archive-driver-payment-details-btn"
                                                    title="View driver payment details"
                                                    data-driver-name="{{ $trip?->driver?->name ?: '—' }}"
                                                    data-driver-email="{{ $trip?->driver?->email ?: '—' }}"
                                                    data-driver-photo="{{ $driverPhotoUrl }}"
                                                    data-driver-bank="{{ $driverBank }}"
                                                    data-driver-account-name="{{ $driverAccountName }}"
                                                    data-driver-account-number="{{ $driverAccountNumber }}"
                                                    data-driver-duitnow-qr="{{ $driverDuitnowQr }}"
                                                    data-driver-tng-qr="{{ $driverTngQr }}"
                                                ><i class="fa-solid fa-circle-info"></i></button>
                                            </div>
                                            <div class="archive-payments-submit-wrap">
                                                <button type="submit" class="btn btn-primary btn-sm">Mark as Paid</button>
                                            </div>
                                        </form>
                                    @elseif($payment->payment_status === 'pending_confirmation')
                                        <span class="archive-payments-status-note pending"><i class="fa-solid fa-clock"></i> Waiting for Driver Confirmation</span>
                                    @else
                                        <span class="archive-payments-status-note done"><i class="fa-solid fa-circle-check"></i> Completed</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- Desktop table --}}
                    <div class="archive-payments-table-wrap">
                        <table class="archive-payments-table">
                            <thead>
                            <tr>
                                <th>Trip</th>
                                <th>Driver</th>
                                <th class="right">Amount Due</th>
                                <th>Status</th>
                                <th class="right">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($myPayments as $payment)
                                @php
                                    $trip = $payment->archivedTrip;
                                    $pickupName = $trip?->pickup_name ?? '-';
                                    $destinationName = $trip?->destination_name ?? '-';
                                    $routeName = $trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                                    $statusSlug = strtolower((string) $payment->payment_status);
                                    $statusText = $payment->payment_status === 'pending_confirmation'
                                        ? 'Pending Confirmation'
                                        : ucfirst((string) $payment->payment_status);
                                    $driverPhotoUrl = $trip?->driver?->profile_photo
                                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($trip->driver->profile_photo)
                                        : '';
                                    $driverBank = $trip?->driver?->payment_bank_name ?: '-';
                                    $driverAccountName = $trip?->driver?->payment_account_name ?: '-';
                                    $driverAccountNumber = $trip?->driver?->payment_account_number ?: '-';
                                    $driverDuitnowQr = $trip?->driver?->payment_qr_duitnow_url ?: '';
                                    $driverTngQr = $trip?->driver?->payment_qr_tng_url ?: '';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="archive-payment-trip">#{{ $trip?->id ?? '—' }}</div>
                                        <div class="archive-payment-sub">{{ $routeName }}</div>
                                        <button
                                            type="button"
                                            class="archive-payments-link open-archive-payment-trip-modal"
                                            data-route-name="{{ $routeName }}"
                                            data-trip-ids="#{{ $trip?->id ?? '—' }}"
                                            data-driver="{{ $trip?->driver?->name ?: '—' }}"
                                            data-datetime="{{ $trip?->trip_datetime?->format('Y-m-d H:i') ?: '—' }}"
                                            data-pickup="{{ $pickupName }}"
                                            data-destination="{{ $destinationName }}"
                                            data-mode="{{ ((string) ($trip?->trip_mode ?? 'one_way')) === 'two_way' ? 'Two-way' : 'One-way' }}"
                                            data-fare="RM {{ number_format((float) (($trip?->fare_total ?? 0) + ($trip?->returnTrip?->fare_total ?? 0)), 2) }}"
                                        ><i class="fa-regular fa-eye"></i><span>View Details</span></button>
                                    </td>
                                    <td>{{ $trip?->driver?->name ?: '—' }}</td>
                                    <td class="right">RM {{ number_format((float) $payment->amount_due, 2) }}</td>
                                    <td><span class="status-chip status-{{ $statusSlug }}">{{ $statusText }}</span></td>
                                    <td class="right">
                                        @if($payment->payment_status === 'unpaid')
                                            <form method="POST" action="{{ route('archive.payments.mark-paid', $payment) }}" class="archive-payments-action-form">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="month" value="{{ $monthKey }}">
                                                <div class="archive-payments-action-group">
                                                    <select class="archive-select" name="payment_method" required>
                                                        <option value="" disabled selected>Select method</option>
                                                        <option value="duitnow_qr">DuitNow QR</option>
                                                        <option value="bank_account">Bank Account</option>
                                                        <option value="digital_wallet">Digital Wallet</option>
                                                        <option value="others">Others</option>
                                                    </select>
                                                    <input class="archive-input" type="text" name="remarks" placeholder="Remarks">
                                                    <button
                                                        type="button"
                                                        class="archive-payments-soft-btn open-archive-driver-payment-details-btn"
                                                        title="View driver payment details"
                                                        data-driver-name="{{ $trip?->driver?->name ?: '—' }}"
                                                        data-driver-email="{{ $trip?->driver?->email ?: '—' }}"
                                                        data-driver-photo="{{ $driverPhotoUrl }}"
                                                        data-driver-bank="{{ $driverBank }}"
                                                        data-driver-account-name="{{ $driverAccountName }}"
                                                        data-driver-account-number="{{ $driverAccountNumber }}"
                                                        data-driver-duitnow-qr="{{ $driverDuitnowQr }}"
                                                        data-driver-tng-qr="{{ $driverTngQr }}"
                                                    ><i class="fa-solid fa-circle-info"></i></button>
                                                    <button type="submit" class="btn btn-primary btn-sm">Mark as Paid</button>
                                                </div>
                                            </form>
                                        @elseif($payment->payment_status === 'pending_confirmation')
                                            <span class="archive-payments-status-note pending">Waiting for Driver</span>
                                        @else
                                            <span class="archive-payments-status-note done">Completed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:14px;">{{ $myPayments->appends(request()->query())->links() }}</div>
                @endif
            </section>

            {{-- Driver Review Queue --}}
            @if($driverPayments)
                <section class="archive-card">
                    <h2 class="archive-section-title">Archived Driver Review Queue</h2>
                    <p class="archive-section-subtitle">Driver approval, confirmations, and rejection for archived records are managed in the main payment page.</p>
                    <div class="archive-summary-grid" style="grid-template-columns:1fr;">
                        <div class="archive-summary-item" style="gap:12px;">
                            <span class="archive-summary-label">Open Payment Rows</span>
                            <span class="archive-summary-value">{{ $summary['driver']['total']['count'] ?? 0 }} archived records</span>
                            <span class="archive-summary-count">Use the payment page for archived row actions.</span>
                            <div>
                                <a href="{{ route('payments.index') }}#archived-queue" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-wallet"></i> Go to Payment Page
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        @endif
    </div>

    {{-- Driver Payment Details Modal --}}
    <div class="archive-modal" id="archiveDriverPaymentDetailsModal" aria-hidden="true">
        <div class="archive-modal-card">
            <div class="archive-modal-head">
                <h3 class="archive-modal-title">Driver Payment Details</h3>
                <button type="button" class="archive-modal-close" id="archiveDriverPaymentDetailsModalClose"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="archive-driver-scroll">
                <div class="archive-driver-profile">
                    <span class="archive-driver-avatar" id="archiveDriverPaymentAvatar">D</span>
                    <span class="archive-driver-meta">
                        <span class="archive-driver-name" id="archiveDriverPaymentName">-</span>
                        <span class="archive-driver-email" id="archiveDriverPaymentEmail">-</span>
                    </span>
                </div>
                <div class="archive-driver-grid">
                    <div class="archive-driver-line">
                        <span class="archive-driver-label"><i class="fa-solid fa-building-columns"></i> Bank / Wallet</span>
                        <span class="archive-driver-value" id="archiveDriverPaymentBank">-</span>
                    </div>
                    <div class="archive-driver-line">
                        <span class="archive-driver-label"><i class="fa-solid fa-user"></i> Account Holder Name</span>
                        <span class="archive-driver-value" id="archiveDriverPaymentAccountName">-</span>
                    </div>
                    <div class="archive-driver-line">
                        <span class="archive-driver-label"><i class="fa-solid fa-hashtag"></i> Account Number</span>
                        <span class="archive-driver-value" id="archiveDriverPaymentAccountNumber">-</span>
                    </div>
                </div>
                <div class="archive-driver-qr-grid">
                    <div class="archive-driver-qr-card">
                        <span class="archive-driver-qr-title"><i class="fa-solid fa-qrcode"></i> DuitNow QR</span>
                        <div class="archive-driver-qr-preview" id="archiveDriverPaymentDuitnowWrap">
                            <span class="archive-driver-qr-empty">No QR uploaded</span>
                        </div>
                    </div>
                    <div class="archive-driver-qr-card">
                        <span class="archive-driver-qr-title"><i class="fa-solid fa-qrcode"></i> Touch 'n Go QR</span>
                        <div class="archive-driver-qr-preview" id="archiveDriverPaymentTngWrap">
                            <span class="archive-driver-qr-empty">No QR uploaded</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="archive-action-row" style="justify-content:flex-end;">
                <button type="button" class="btn btn-ghost btn-sm" id="archiveDriverPaymentDetailsClose">Close</button>
            </div>
        </div>
    </div>

    {{-- Trip Detail Modal --}}
    <div class="archive-modal" id="archivePaymentTripModal" aria-hidden="true">
        <div class="archive-modal-card">
            <div class="archive-modal-head">
                <h3 class="archive-modal-title">Archived Trip Details</h3>
                <button type="button" class="archive-modal-close" id="archivePaymentTripModalClose"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Trip ID</span>
                <span class="archive-modal-value" id="archivePaymentTripIds">-</span>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Route</span>
                <span class="archive-modal-value" id="archivePaymentRouteName">-</span>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Date &amp; Time</span>
                <span class="archive-modal-value" id="archivePaymentTripDatetime">-</span>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Driver</span>
                <span class="archive-modal-value" id="archivePaymentTripDriver">-</span>
            </div>
            <div class="archive-point-grid">
                <div class="archive-point-card pickup">
                    <span class="archive-point-label"><i class="fa-solid fa-location-dot"></i> Pickup Point</span>
                    <span class="archive-point-value" id="archivePaymentTripPickup">-</span>
                </div>
                <div class="archive-point-card destination">
                    <span class="archive-point-label"><i class="fa-solid fa-flag-checkered"></i> Destination Point</span>
                    <span class="archive-point-value" id="archivePaymentTripDestination">-</span>
                </div>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Trip Type</span>
                <span class="archive-modal-value" id="archivePaymentTripMode">-</span>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Combined Fare</span>
                <span class="archive-modal-value" id="archivePaymentTripFare">-</span>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const driverModal = document.getElementById('archiveDriverPaymentDetailsModal');
            const driverModalClose = document.getElementById('archiveDriverPaymentDetailsClose');
            const driverModalCloseTop = document.getElementById('archiveDriverPaymentDetailsModalClose');
            const driverButtons = document.querySelectorAll('.open-archive-driver-payment-details-btn');

            if (driverModal && driverButtons.length) {
                if (driverModal.parentElement !== document.body) document.body.appendChild(driverModal);

                const avatarEl = document.getElementById('archiveDriverPaymentAvatar');
                const nameEl = document.getElementById('archiveDriverPaymentName');
                const emailEl = document.getElementById('archiveDriverPaymentEmail');
                const bankEl = document.getElementById('archiveDriverPaymentBank');
                const accountNameEl = document.getElementById('archiveDriverPaymentAccountName');
                const accountNumberEl = document.getElementById('archiveDriverPaymentAccountNumber');
                const duitnowWrap = document.getElementById('archiveDriverPaymentDuitnowWrap');
                const tngWrap = document.getElementById('archiveDriverPaymentTngWrap');

                const renderQr = (wrapEl, qrUrl, label) => {
                    if (!wrapEl) return;
                    const url = String(qrUrl || '').trim();
                    if (!url) {
                        wrapEl.innerHTML = '<span class="archive-driver-qr-empty">No QR uploaded</span>';
                        return;
                    }
                    wrapEl.innerHTML = `<img src="${url}" alt="${label}">`;
                };

                const closeDriverModal = () => {
                    driverModal.classList.remove('show');
                    driverModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                };

                driverButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const driverName = String(button.dataset.driverName || '-').trim() || '-';
                        const driverEmail = String(button.dataset.driverEmail || '-').trim() || '-';
                        const driverPhoto = String(button.dataset.driverPhoto || '').trim();

                        if (nameEl) nameEl.textContent = driverName;
                        if (emailEl) emailEl.textContent = driverEmail;
                        if (bankEl) bankEl.textContent = button.dataset.driverBank || '-';
                        if (accountNameEl) accountNameEl.textContent = button.dataset.driverAccountName || '-';
                        if (accountNumberEl) accountNumberEl.textContent = button.dataset.driverAccountNumber || '-';

                        if (avatarEl) {
                            if (driverPhoto) {
                                avatarEl.innerHTML = `<img src="${driverPhoto}" alt="${driverName}">`;
                            } else {
                                avatarEl.textContent = (driverName.charAt(0) || 'D').toUpperCase();
                            }
                        }

                        renderQr(duitnowWrap, button.dataset.driverDuitnowQr, 'DuitNow QR');
                        renderQr(tngWrap, button.dataset.driverTngQr, "Touch 'n Go QR");

                        driverModal.classList.add('show');
                        driverModal.setAttribute('aria-hidden', 'false');
                        document.body.classList.add('modal-open');
                    });
                });

                if (driverModalClose) driverModalClose.addEventListener('click', closeDriverModal);
                if (driverModalCloseTop) driverModalCloseTop.addEventListener('click', closeDriverModal);
                driverModal.addEventListener('click', (event) => {
                    if (event.target === driverModal) closeDriverModal();
                });
            }

            const modal = document.getElementById('archivePaymentTripModal');
            const closeBtn = document.getElementById('archivePaymentTripModalClose');
            const buttons = document.querySelectorAll('.open-archive-payment-trip-modal');
            if (!modal || !closeBtn || buttons.length === 0) return;
            if (modal.parentElement !== document.body) document.body.appendChild(modal);

            const idsEl = document.getElementById('archivePaymentTripIds');
            const routeEl = document.getElementById('archivePaymentRouteName');
            const datetimeEl = document.getElementById('archivePaymentTripDatetime');
            const driverEl = document.getElementById('archivePaymentTripDriver');
            const pickupEl = document.getElementById('archivePaymentTripPickup');
            const destinationEl = document.getElementById('archivePaymentTripDestination');
            const modeEl = document.getElementById('archivePaymentTripMode');
            const fareEl = document.getElementById('archivePaymentTripFare');

            const closeModal = () => {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            };

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    if (idsEl) idsEl.textContent = button.dataset.tripIds || '-';
                    if (routeEl) routeEl.textContent = button.dataset.routeName || '-';
                    if (datetimeEl) datetimeEl.textContent = button.dataset.datetime || '-';
                    if (driverEl) driverEl.textContent = button.dataset.driver || '-';
                    if (pickupEl) pickupEl.textContent = button.dataset.pickup || '-';
                    if (destinationEl) destinationEl.textContent = button.dataset.destination || '-';
                    if (modeEl) modeEl.textContent = button.dataset.mode || '-';
                    if (fareEl) fareEl.textContent = button.dataset.fare || '-';
                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                });
            });

            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
        })();
    </script>
@endsection
