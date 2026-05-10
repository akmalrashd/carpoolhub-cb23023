@extends('layouts.app')

@section('content')
    <style>
        .archive-payments-page { display: grid; gap: 12px; }
        .archive-card { background: #fff; border: 1px solid #dbe2ea; border-radius: 16px; padding: 14px; }
        .archive-title { margin: 0; font-family: Poppins, sans-serif; font-size: 30px; color: #0f172a; line-height: 1.05; }
        .archive-subtitle { margin: 6px 0 0; color: #64748b; font-size: 14px; }
        .archive-topbar { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .archive-btn { border-radius: 10px; border: 1px solid #dbe2ea; background: #fff; color: #0f172a; padding: 9px 12px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .archive-btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .archive-btn.success { background: #166534; border-color: #166534; color: #fff; }
        .archive-btn.danger { background: #b91c1c; border-color: #b91c1c; color: #fff; }
        .archive-filter-row { display: grid; gap: 10px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .archive-label { font-size: 12px; color: #475569; font-weight: 700; }
        .archive-select { width: 100%; border: 1px solid #dbe2ea; border-radius: 11px; background: #f8fafc; color: #0f172a; padding: 10px 12px; font-size: 14px; outline: none; }
        .archive-input { width: 100%; border: 1px solid #dbe2ea; border-radius: 10px; background: #fff; color: #0f172a; padding: 9px 11px; font-size: 13px; outline: none; }
        .archive-summary-grid { display: grid; gap: 8px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .archive-summary-item { border: 1px solid #dbe2ea; border-radius: 12px; background: #f8fafc; padding: 10px; display: grid; gap: 2px; }
        .archive-summary-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
        .archive-summary-value { font-size: 18px; color: #0f172a; font-weight: 700; }
        .archive-summary-count { font-size: 12px; color: #64748b; }
        .archive-section-title { margin: 0 0 8px; color: #0f172a; font-size: 18px; font-weight: 700; }
        .archive-section-subtitle { margin: -2px 0 14px; color: #64748b; font-size: 14px; }
        .archive-list { display: grid; gap: 8px; }
        .archive-payment-item { border: 1px solid #dbe2ea; border-radius: 12px; background: #fff; padding: 10px; display: grid; gap: 8px; }
        .archive-payment-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .archive-payment-trip { font-size: 14px; font-weight: 700; color: #0f172a; line-height: 1.3; }
        .archive-payment-sub { margin-top: 2px; font-size: 12px; color: #64748b; line-height: 1.35; }
        .archive-line { display: flex; align-items: center; justify-content: space-between; gap: 8px; border: 1px solid #e2e8f0; border-radius: 9px; background: #f8fafc; padding: 7px 8px; font-size: 12px; color: #334155; }
        .archive-line strong { color: #0f172a; text-align: right; }
        .status-chip { display: inline-flex; align-items: center; border-radius: 999px; border: 1px solid #dbe2ea; padding: 4px 9px; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .status-unpaid { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
        .status-pending_confirmation { color: #854d0e; border-color: #fde68a; background: #fefce8; }
        .status-paid { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .archive-empty { border: 1px dashed #dbe2ea; border-radius: 12px; background: #f8fafc; padding: 14px; color: #64748b; font-size: 14px; text-align: center; }
        .archive-notice { border-radius: 12px; padding: 12px 14px; font-size: 14px; }
        .archive-notice.success { border: 1px solid #86efac; background: #f0fdf4; color: #166534; }
        .archive-notice.error { border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c; }
        .archive-action-stack { display: grid; gap: 8px; }
        .archive-inline-form { display: grid; gap: 8px; }
        .archive-action-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .archive-action-note { font-size: 12px; font-weight: 700; }
        .archive-action-note.pending { color: #854d0e; }
        .archive-action-note.done { color: #166534; }
        .archive-btn.is-disabled { opacity: .55; cursor: not-allowed; }
        .archive-payments-table-wrap { display: none; }
        .archive-payments-table { width: 100%; border-collapse: collapse; }
        .archive-payments-table th { text-align: left; color: #64748b; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; padding: 0 12px 14px; border-bottom: 1px solid #e2e8f0; }
        .archive-payments-table td { vertical-align: top; padding: 16px 12px; border-bottom: 1px solid #e2e8f0; }
        .archive-payments-table th.right,
        .archive-payments-table td.right { text-align: right; }
        .archive-payments-mobile-list { display: grid; gap: 12px; }
        .archive-payments-mobile-card { border: 1px solid #dbe2ea; border-radius: 16px; background: #fff; padding: 14px; display: grid; gap: 10px; }
        .archive-payments-mobile-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
        .archive-payments-trip-id { color: #0f172a; font-size: 16px; font-weight: 800; line-height: 1.2; }
        .archive-payments-route { color: #64748b; font-size: 13px; line-height: 1.4; }
        .archive-payments-link { border: 0; background: transparent; color: #64748b; padding: 0; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
        .archive-payments-grid { display: grid; gap: 8px; }
        .archive-payments-line { display: flex; align-items: center; justify-content: space-between; gap: 8px; border: 1px solid #dbe2ea; border-radius: 12px; background: #f8fafc; padding: 10px 12px; font-size: 13px; color: #334155; }
        .archive-payments-line strong { color: #0f172a; text-align: right; }
        .archive-payments-action-form { display: grid; gap: 10px; }
        .archive-payments-action-row { display: grid; gap: 10px; }
        .archive-payments-action-group { display: grid; gap: 8px; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 42px; align-items: center; }
        .archive-payments-soft-btn { width: 40px; height: 40px; border-radius: 12px; border: 1px solid #dbe2ea; background: #fff; color: #475569; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .archive-payments-status-note { font-size: 12px; font-weight: 700; }
        .archive-payments-status-note.pending { color: #854d0e; }
        .archive-payments-status-note.done { color: #166534; }
        .archive-payments-submit-wrap { display: flex; justify-content: flex-end; }
        .archive-payments-submit-wrap .archive-btn { min-height: 40px; }
        .archive-driver-scroll { display: grid; gap: 10px; }
        .archive-driver-profile { border: 1px solid #dbe2ea; border-radius: 14px; background: #f8fafc; padding: 14px; display: flex; align-items: center; gap: 12px; }
        .archive-driver-avatar { width: 48px; height: 48px; border-radius: 999px; border: 1px solid #cbd5e1; background: #fff; color: #0f172a; font-size: 20px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; flex: 0 0 auto; }
        .archive-driver-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .archive-driver-meta { display: grid; gap: 2px; min-width: 0; }
        .archive-driver-name { color: #0f172a; font-size: 16px; font-weight: 800; line-height: 1.2; }
        .archive-driver-email { color: #64748b; font-size: 13px; line-height: 1.35; word-break: break-word; }
        .archive-driver-grid { display: grid; gap: 10px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .archive-driver-line { border: 1px solid #dbe2ea; border-radius: 14px; background: #f8fafc; padding: 14px; display: grid; gap: 6px; }
        .archive-driver-label { color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; display: inline-flex; align-items: center; gap: 6px; }
        .archive-driver-value { color: #0f172a; font-size: 15px; font-weight: 700; word-break: break-word; }
        .archive-driver-qr-grid { display: grid; gap: 10px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .archive-driver-qr-card { border: 1px solid #dbe2ea; border-radius: 14px; background: #f8fafc; padding: 14px; display: grid; gap: 10px; }
        .archive-driver-qr-title { color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; display: inline-flex; align-items: center; gap: 6px; }
        .archive-driver-qr-preview { min-height: 180px; border: 1px dashed #cbd5e1; border-radius: 12px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .archive-driver-qr-preview img { width: 100%; height: 100%; object-fit: contain; }
        .archive-driver-qr-empty { color: #94a3b8; font-size: 14px; font-weight: 700; }
        .archive-modal { position: fixed; inset: 0; background: rgba(15, 23, 42, .52); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 2600; }
        .archive-modal.show { display: flex; }
        .archive-modal-card { width: min(700px, 100%); max-height: 82vh; overflow: auto; border: 1px solid #dbe2ea; border-radius: 14px; background: #fff; padding: 14px; display: grid; gap: 10px; }
        .archive-modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .archive-modal-title { margin: 0; color: #0f172a; font-size: 18px; font-weight: 700; }
        .archive-modal-close { width: 34px; height: 34px; border-radius: 10px; border: 1px solid #dbe2ea; background: #fff; color: #475569; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .archive-modal-line { border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; padding: 8px 10px; display: grid; gap: 2px; }
        .archive-modal-label { color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
        .archive-modal-value { color: #0f172a; font-size: 13px; font-weight: 600; word-break: break-word; }
        .archive-point-grid { display: grid; gap: 8px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .archive-point-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 10px; display: grid; gap: 3px; }
        .archive-point-card.pickup { border-color: #bbf7d0; background: #f0fdf4; }
        .archive-point-card.destination { border-color: #bfdbfe; background: #eff6ff; }
        .archive-point-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; display: inline-flex; align-items: center; gap: 5px; }
        .archive-point-card.pickup .archive-point-label { color: #166534; }
        .archive-point-card.destination .archive-point-label { color: #1d4ed8; }
        .archive-point-value { color: #0f172a; font-size: 13px; font-weight: 700; line-height: 1.3; }
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
        @if(session('status'))
            <div class="archive-notice success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="archive-notice error">{{ $errors->first() }}</div>
        @endif

        <section class="archive-card">
            <div class="archive-topbar">
                <div>
                    <h1 class="archive-title">Pembayaran Diarkib</h1>
                    <p class="archive-subtitle">Sejarah pembayaran baca sahaja untuk kitaran bil diarkib.</p>
                </div>
                <a href="{{ route('archive.index', ['month' => $monthKey]) }}" class="archive-btn"><i class="fa-solid fa-arrow-left"></i>Kembali ke Arkib</a>
            </div>
        </section>

        <section class="archive-card">
            <form method="GET" action="{{ route('archive.payments.index') }}" class="archive-filter-row">
                <div>
                    <label class="archive-label" for="month">Bulan</label>
                    <select class="archive-select" name="month" id="month">
                        <option value="" disabled {{ $monthKey ? '' : 'selected' }}>Pilih bulan arkib</option>
                        @foreach($months as $month)
                            <option value="{{ $month['value'] }}" {{ $monthKey === $month['value'] ? 'selected' : '' }}>{{ $month['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="archive-btn primary"><i class="fa-solid fa-filter"></i>Guna</button>
                <a href="{{ route('archive.payments.index') }}" class="archive-btn">Set Semula</a>
            </form>
        </section>

        @if(! $monthKey)
            <section class="archive-card">
                <div class="archive-empty">Pilih bulan arkib dahulu untuk memuatkan ringkasan pembayaran dan rekod.</div>
            </section>
        @else
            <section class="archive-card">
                <h2 class="archive-section-title">Ringkasan Pembayaran</h2>
                <div class="archive-summary-grid">
                    <div class="archive-summary-item">
                        <span class="archive-summary-label">Belum Bayar Saya</span>
                        <span class="archive-summary-value">RM {{ number_format((float) ($summary['my']['unpaid']['amount'] ?? 0), 2) }}</span>
                        <span class="archive-summary-count">{{ $summary['my']['unpaid']['count'] ?? 0 }} rekod</span>
                    </div>
                    <div class="archive-summary-item">
                        <span class="archive-summary-label">Tertangguh Saya</span>
                        <span class="archive-summary-value">RM {{ number_format((float) ($summary['my']['pending_confirmation']['amount'] ?? 0), 2) }}</span>
                        <span class="archive-summary-count">{{ $summary['my']['pending_confirmation']['count'] ?? 0 }} rekod</span>
                    </div>
                    <div class="archive-summary-item">
                        <span class="archive-summary-label">Dibayar Saya</span>
                        <span class="archive-summary-value">RM {{ number_format((float) ($summary['my']['paid']['amount'] ?? 0), 2) }}</span>
                        <span class="archive-summary-count">{{ $summary['my']['paid']['count'] ?? 0 }} rekod</span>
                    </div>
                    <div class="archive-summary-item">
                        <span class="archive-summary-label">Baris Semakan</span>
                        <span class="archive-summary-value">RM {{ number_format((float) ($summary['driver']['total']['amount'] ?? 0), 2) }}</span>
                        <span class="archive-summary-count">{{ $summary['driver']['total']['count'] ?? 0 }} rekod</span>
                    </div>
                </div>
            </section>

            <section class="archive-card">
                <h2 class="archive-section-title">Pembayaran Diarkib Saya</h2>
                <p class="archive-section-subtitle">Tandakan rekod belum bayar diarkib anda dan pantau pengesahan.</p>
                @if($myPayments->isEmpty())
                    <div class="archive-empty">Tiada rekod pembayaran diarkib dijumpai untuk akaun anda.</div>
                @else
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
                            $statusTextMap = ['unpaid' => 'Belum Bayar', 'pending_confirmation' => 'Menunggu Pengesahan', 'paid' => 'Dibayar'];
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
                                    <div class="archive-payments-trip-id">Trip #{{ $trip?->id ?? '-' }}</div>
                                    <div class="archive-payments-route">{{ $routeName }}</div>
                                    <button
                                        type="button"
                                        class="archive-payments-link open-archive-payment-trip-modal"
                                        data-route-name="{{ $routeName }}"
                                        data-trip-ids="#{{ $trip?->id ?? '-' }}"
                                        data-driver="{{ $trip?->driver?->name ?: '-' }}"
                                        data-datetime="{{ $trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-pickup="{{ $pickupName }}"
                                        data-destination="{{ $destinationName }}"
                                        data-mode="{{ ((string) ($trip?->trip_mode ?? 'one_way')) === 'two_way' ? 'Dua Hala' : 'Sehala' }}"
                                        data-fare="RM {{ number_format((float) (($trip?->fare_total ?? 0) + ($trip?->returnTrip?->fare_total ?? 0)), 2) }}"
                                    ><i class="fa-regular fa-eye"></i><span>Lihat Butiran</span></button>
                                </div>
                                <span class="status-chip status-{{ $statusSlug }}">{{ $statusText }}</span>
                            </div>
                            <div class="archive-payments-grid">
                                <div class="archive-payments-line"><span>Pemandu</span><strong>{{ $trip?->driver?->name ?: '-' }}</strong></div>
                                <div class="archive-payments-line"><span>Amaun Perlu Dibayar</span><strong>RM {{ number_format((float) $payment->amount_due, 2) }}</strong></div>
                            </div>
                            <div class="archive-payments-action-row">
                                @if($payment->payment_status === 'unpaid')
                                    <form method="POST" action="{{ route('archive.payments.mark-paid', $payment) }}" class="archive-payments-action-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="month" value="{{ $monthKey }}">
                                        <div class="archive-payments-action-group">
                                            <select class="archive-select" name="payment_method" required>
                                                <option value="" disabled selected>Pilih kaedah</option>
                                                <option value="duitnow_qr">DuitNow QR</option>
                                                <option value="bank_account">Akaun Bank</option>
                                                <option value="digital_wallet">Dompet Digital</option>
                                                <option value="others">Lain-lain</option>
                                            </select>
                                            <input class="archive-input" type="text" name="remarks" placeholder="Catatan">
                                            <button
                                                type="button"
                                                class="archive-payments-soft-btn open-archive-driver-payment-details-btn"
                                                title="Driver payment details"
                                                data-driver-name="{{ $trip?->driver?->name ?: '-' }}"
                                                data-driver-email="{{ $trip?->driver?->email ?: '-' }}"
                                                data-driver-photo="{{ $driverPhotoUrl }}"
                                                data-driver-bank="{{ $driverBank }}"
                                                data-driver-account-name="{{ $driverAccountName }}"
                                                data-driver-account-number="{{ $driverAccountNumber }}"
                                                data-driver-duitnow-qr="{{ $driverDuitnowQr }}"
                                                data-driver-tng-qr="{{ $driverTngQr }}"
                                            ><i class="fa-solid fa-circle-info"></i></button>
                                        </div>
                                        <div class="archive-payments-submit-wrap">
                                            <button type="submit" class="archive-btn primary">Tandakan Dibayar</button>
                                        </div>
                                    </form>
                                @elseif($payment->payment_status === 'pending_confirmation')
                                    <span class="archive-payments-status-note pending">Menunggu Pemandu</span>
                                @else
                                    <span class="archive-payments-status-note done">Selesai</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="archive-payments-table-wrap">
                    <table class="archive-payments-table">
                        <thead>
                        <tr>
                            <th>Trip</th>
                            <th>Pemandu</th>
                            <th class="right">Amaun Perlu Dibayar</th>
                            <th>Status</th>
                            <th class="right">Tindakan</th>
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
                                    <div class="archive-payment-trip">#{{ $trip?->id ?? '-' }}</div>
                                    <div class="archive-payment-sub">{{ $routeName }}</div>
                                    <button
                                        type="button"
                                        class="archive-payments-link open-archive-payment-trip-modal"
                                        data-route-name="{{ $routeName }}"
                                        data-trip-ids="#{{ $trip?->id ?? '-' }}"
                                        data-driver="{{ $trip?->driver?->name ?: '-' }}"
                                        data-datetime="{{ $trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-pickup="{{ $pickupName }}"
                                        data-destination="{{ $destinationName }}"
                                        data-mode="{{ ((string) ($trip?->trip_mode ?? 'one_way')) === 'two_way' ? 'Dua Hala' : 'Sehala' }}"
                                        data-fare="RM {{ number_format((float) (($trip?->fare_total ?? 0) + ($trip?->returnTrip?->fare_total ?? 0)), 2) }}"
                                    ><i class="fa-regular fa-eye"></i><span>Lihat Butiran</span></button>
                                </td>
                                <td>{{ $trip?->driver?->name ?: '-' }}</td>
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
                                                <input class="archive-input" type="text" name="remarks" placeholder="Catatan">
                                                <button
                                                    type="button"
                                                    class="archive-payments-soft-btn open-archive-driver-payment-details-btn"
                                                    title="Driver payment details"
                                                    data-driver-name="{{ $trip?->driver?->name ?: '-' }}"
                                                    data-driver-email="{{ $trip?->driver?->email ?: '-' }}"
                                                    data-driver-photo="{{ $driverPhotoUrl }}"
                                                    data-driver-bank="{{ $driverBank }}"
                                                    data-driver-account-name="{{ $driverAccountName }}"
                                                    data-driver-account-number="{{ $driverAccountNumber }}"
                                                    data-driver-duitnow-qr="{{ $driverDuitnowQr }}"
                                                    data-driver-tng-qr="{{ $driverTngQr }}"
                                                ><i class="fa-solid fa-circle-info"></i></button>
                                                <button type="submit" class="archive-btn primary">Tandakan Dibayar</button>
                                            </div>
                                        </form>
                                    @elseif($payment->payment_status === 'pending_confirmation')
                                        <span class="archive-payments-status-note pending">Menunggu Pemandu</span>
                                    @else
                                        <span class="archive-payments-status-note done">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:12px;">{{ $myPayments->appends(request()->query())->links() }}</div>
                @endif
            </section>

            @if($driverPayments)
                <section class="archive-card">
                    <h2 class="archive-section-title">Tindakan Baris Arkib</h2>
                    <p class="archive-section-subtitle">Kelulusan pemandu, tandakan dibayar, notifikasi, dan tolak untuk rekod diarkib kini tersedia di baris pembayaran.</p>
                    <div class="archive-summary-grid" style="grid-template-columns: repeat(1, minmax(0, 1fr));">
                        <div class="archive-summary-item" style="gap:10px;">
                            <span class="archive-summary-label">Buka Baris Pembayaran</span>
                            <span class="archive-summary-value">{{ $summary['driver']['total']['count'] ?? 0 }} rekod baris arkib</span>
                            <span class="archive-summary-count">Gunakan halaman pembayaran untuk tindakan baris arkib.</span>
                            <div>
                                <a href="{{ route('payments.index') }}#archived-queue" class="archive-btn primary">
                                    <i class="fa-solid fa-wallet"></i>Pergi ke Baris Pembayaran
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        @endif
    </div>

    <div class="archive-modal" id="archiveDriverPaymentDetailsModal" aria-hidden="true">
        <div class="archive-modal-card">
            <div class="archive-modal-head">
                <h3 class="archive-modal-title">Butiran Pembayaran Pemandu</h3>
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
                        <span class="archive-driver-label"><i class="fa-solid fa-building-columns"></i>Bank / Dompet</span>
                        <span class="archive-driver-value" id="archiveDriverPaymentBank">-</span>
                    </div>
                    <div class="archive-driver-line">
                        <span class="archive-driver-label"><i class="fa-solid fa-user"></i>Nama Pemegang Akaun</span>
                        <span class="archive-driver-value" id="archiveDriverPaymentAccountName">-</span>
                    </div>
                    <div class="archive-driver-line">
                        <span class="archive-driver-label"><i class="fa-solid fa-hashtag"></i>Nombor Akaun</span>
                        <span class="archive-driver-value" id="archiveDriverPaymentAccountNumber">-</span>
                    </div>
                </div>
                <div class="archive-driver-qr-grid">
                    <div class="archive-driver-qr-card">
                        <span class="archive-driver-qr-title"><i class="fa-solid fa-qrcode"></i>DuitNow QR</span>
                        <div class="archive-driver-qr-preview" id="archiveDriverPaymentDuitnowWrap">
                            <span class="archive-driver-qr-empty">Tiada QR dimuat naik</span>
                        </div>
                    </div>
                    <div class="archive-driver-qr-card">
                        <span class="archive-driver-qr-title"><i class="fa-solid fa-qrcode"></i>Touch 'n Go QR</span>
                        <div class="archive-driver-qr-preview" id="archiveDriverPaymentTngWrap">
                            <span class="archive-driver-qr-empty">Tiada QR dimuat naik</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="archive-action-row" style="justify-content:flex-end;">
                <button type="button" class="archive-btn" id="archiveDriverPaymentDetailsClose">Tutup</button>
            </div>
        </div>
    </div>

    <div class="archive-modal" id="archivePaymentTripModal" aria-hidden="true">
        <div class="archive-modal-card">
            <div class="archive-modal-head">
                <h3 class="archive-modal-title">Butiran Trip Diarkib</h3>
                <button type="button" class="archive-modal-close" id="archivePaymentTripModalClose"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">ID Trip</span>
                <span class="archive-modal-value" id="archivePaymentTripIds">-</span>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Laluan</span>
                <span class="archive-modal-value" id="archivePaymentRouteName">-</span>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Tarikh & Masa</span>
                <span class="archive-modal-value" id="archivePaymentTripDatetime">-</span>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Pemandu</span>
                <span class="archive-modal-value" id="archivePaymentTripDriver">-</span>
            </div>
            <div class="archive-point-grid">
                <div class="archive-point-card pickup">
                    <span class="archive-point-label"><i class="fa-solid fa-location-dot"></i>Titik Pickup</span>
                    <span class="archive-point-value" id="archivePaymentTripPickup">-</span>
                </div>
                <div class="archive-point-card destination">
                    <span class="archive-point-label"><i class="fa-solid fa-flag-checkered"></i>Titik Destinasi</span>
                    <span class="archive-point-value" id="archivePaymentTripDestination">-</span>
                </div>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Jenis Trip</span>
                <span class="archive-modal-value" id="archivePaymentTripMode">-</span>
            </div>
            <div class="archive-modal-line">
                <span class="archive-modal-label">Tambang Gabungan</span>
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
                        wrapEl.innerHTML = '<span class="archive-driver-qr-empty">Tiada QR dimuat naik</span>';
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
