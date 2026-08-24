@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    @php
        $role = auth()->user()->role;
        $reviewTitle = $role === 'admin' ? 'Global Review Queue' : 'Driver Review Queue';
        $reviewSubtitle = $role === 'admin'
            ? 'Review and confirm all pending payments across trips.'
            : 'Review and confirm payments for trips you drive.';
    @endphp

    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/payments.css') }}?v={{ filemtime(public_path('css/payments.css')) }}">
    @endpush


    @php
        $myRecordCount = isset($myPayments) ? $myPayments->total() : 0;
        $queueCount = (isset($driverPayments) && $driverPayments ? $driverPayments->total() : 0);
        $isAdmin = $role === 'admin';
        $isPassenger = $role === 'passenger';
        $hasSplitPaymentDirections = $canReviewQueue && ! $isAdmin;
        $unpaidAmt = (float) ($summary['my']['unpaid']['amount'] ?? $summary['driver']['unpaid']['amount'] ?? 0);
        $pendingAmt = (float) ($summary['my']['pending_confirmation']['amount'] ?? $summary['driver']['pending_confirmation']['amount'] ?? 0);
        $paidAmt = (float) ($summary['my']['paid']['amount'] ?? 0);
        $myTotalAmt = (float) (($summary['my']['unpaid']['amount'] ?? 0) + ($summary['my']['pending_confirmation']['amount'] ?? 0) + ($summary['my']['paid']['amount'] ?? 0));
        $paidOutAmt = (float) (($summary['my']['unpaid']['amount'] ?? 0) + ($summary['my']['pending_confirmation']['amount'] ?? 0));
        $monthLabel = $summaryLabel;
        $statusWeight = fn ($payment): int => match ((string) ($payment->payment_status ?? 'unpaid')) {
            'unpaid' => 1,
            'pending_confirmation' => 2,
            'paid' => 3,
            default => 4,
        };
        $allLivePayments = ($allPaymentsUnfiltered ?? collect($myPayments?->items() ?? [])->merge(collect(($driverPayments ?? null)?->items() ?? [])))
            ->unique(fn ($payment) => $payment->id . ':' . $payment->trip_id)
            ->sort(function ($a, $b) use ($statusWeight) {
                $wA = $statusWeight($a);
                $wB = $statusWeight($b);
                if ($wA !== $wB) {
                    return $wA <=> $wB;
                }
                $tA = $a->trip?->trip_datetime?->timestamp ?? $a->id;
                $tB = $b->trip?->trip_datetime?->timestamp ?? $b->id;
                return $tB <=> $tA;
            })
            ->values();
        $paymentPerspective = fn ($payment): string => (
            ((int) ($payment->trip?->driver_id ?? 0) === (int) auth()->id()
                && (int) $payment->user_id !== (int) auth()->id())
            || ($isAdmin && (int) ($payment->trip?->driver_id ?? 0) !== (int) auth()->id() && (int) $payment->user_id !== (int) auth()->id())
        )
                ? 'collect'
                : 'pay';
        $activePaymentFilter = $filters['payment_filter'] ?? request('payment_filter', 'all');
        $activeDirection = $filters['direction'] ?? request('direction', 'all');
        $paymentTabUrl = fn (array $overrides = []) => route('payments.index', array_filter(
            array_merge(request()->except(['payment_filter', 'direction', 'mine_page', 'driver_page']), $overrides),
            fn ($value) => $value !== null && $value !== '' && $value !== 'all'
        ));
        $allLiveCount = (int) ($paymentCounts['all'] ?? $allLivePayments->count());
        $payCount = (int) ($paymentCounts['pay'] ?? $allLivePayments->filter(fn ($payment) => $paymentPerspective($payment) === 'pay')->count());
        $collectCount = (int) ($paymentCounts['collect'] ?? $allLivePayments->filter(fn ($payment) => $paymentPerspective($payment) === 'collect')->count());
        $unpaidCount = (int) ($paymentCounts['unpaid'] ?? $allLivePayments->where('payment_status', 'unpaid')->count());
        $reviewCount = (int) ($paymentCounts['review'] ?? $allLivePayments->where('payment_status', 'pending_confirmation')->count());
        $confirmedCount = (int) ($paymentCounts['confirmed'] ?? $allLivePayments->where('payment_status', 'paid')->count());
        $paymentInitials = fn (?string $name): string => collect(explode(' ', trim((string) $name)))
            ->filter()
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('') ?: 'P';
        $paymentFareBreakdown = function ($payment): array {
            $trip = $payment->trip;
            $point = $trip?->passengerRoutePoints
                ?->first(fn ($point) => (int) $point->user_id === (int) $payment->user_id
                    && in_array((string) $point->point_type, ['custom_pickup', 'custom_dropoff'], true));
            $customStop = $point ? ($point->point_name ?: ($point->point_type === 'custom_pickup' ? 'Custom Pickup' : 'Custom Dropoff')) : null;

            $total = (float) ($payment->amount_due ?? 0);
            $extra = (float) ($point?->additional_fee ?? 0);
            $base = max(0, $total - $extra);

            return [
                'total' => $total,
                'base' => $base,
                'extra' => $extra,
                'has_extra' => $extra > 0,
                'custom_stop' => $customStop,
            ];
        };
        $reminderButtonUi = function (bool $canSendReminder, int $secondsLeft): array {
            if ($canSendReminder) {
                return [
                    'icon' => 'fa-regular fa-bell',
                    'label' => '',
                    'title' => 'Send a payment reminder',
                ];
            }

            $hours = intdiv($secondsLeft, 3600);
            $minutes = intdiv($secondsLeft % 3600, 60);
            $compact = $hours >= 1 ? $hours . 'h' : ($minutes >= 1 ? $minutes . 'm' : '<1m');

            return [
                'icon' => 'fa-regular fa-clock',
                'label' => $compact,
                'title' => 'Available again at ' . \Illuminate\Support\Carbon::now()->addSeconds($secondsLeft)->format('d M Y, H:i'),
            ];
        };
        $driverUnpaidAmount = (float) ($summary['driver']['unpaid']['amount'] ?? 0);
        $driverPendingAmount = (float) ($summary['driver']['pending_confirmation']['amount'] ?? 0);
        $driverPaidAmount = (float) ($summary['driver']['paid']['amount'] ?? 0);
        $myUnpaidAmount = (float) ($summary['my']['unpaid']['amount'] ?? 0);
        $myPendingAmount = (float) ($summary['my']['pending_confirmation']['amount'] ?? 0);
        $myPaidAmount = (float) ($summary['my']['paid']['amount'] ?? 0);
        $summaryMainLabel = $isAdmin ? 'All payments' : ($canReviewQueue ? 'To collect' : 'To pay');
        $summaryMainAmount = ($canReviewQueue || $isAdmin)
            ? ($driverUnpaidAmount + $driverPendingAmount + $driverPaidAmount)
            : ($myUnpaidAmount + $myPendingAmount);
        $summaryPrimaryAmount = ($canReviewQueue || $isAdmin) ? $driverUnpaidAmount : $myUnpaidAmount;
        $summaryPrimaryLabel = ($canReviewQueue || $isAdmin) ? 'Unpaid by passengers' : 'Unpaid to drivers';
        $summarySecondaryAmount = ($canReviewQueue || $isAdmin) ? $driverPendingAmount : $myPendingAmount;
        $summarySecondaryLabel = $canReviewQueue ? 'Pending confirmation' : 'Pending confirmation';
        $summaryPaidAmount = ($canReviewQueue || $isAdmin) ? $driverPaidAmount : $myPaidAmount;
        $driverCollectionRows = $allLivePayments
            ->filter(fn ($payment) => $paymentPerspective($payment) === 'collect' && in_array((string) $payment->payment_status, ['unpaid', 'pending_confirmation', 'paid'], true))
            ->groupBy(fn ($payment) => $payment->user?->name ?: 'Passenger')
            ->map(function ($rows, $passengerName) {
                $unpaid = (float) $rows->where('payment_status', 'unpaid')->sum('amount_due');
                $pending = (float) $rows->where('payment_status', 'pending_confirmation')->sum('amount_due');
                $paid = (float) $rows->where('payment_status', 'paid')->sum('amount_due');
                $total = (float) $rows->sum('amount_due');
                $outstanding = $unpaid + $pending;

                return [
                    'name' => $passengerName,
                    'passenger' => $passengerName,
                    'unpaid' => $unpaid,
                    'pending' => $pending,
                    'paid' => $paid,
                    'total' => $total,
                    'outstanding' => $outstanding,
                    'records' => $rows->count(),
                    'count' => $rows->count(),
                    'sample_payment' => $rows->first(),
                ];
            })
            ->sortByDesc(fn ($row) => $row['outstanding'])
            ->values();

        $passengerPayRows = $allLivePayments
            ->filter(fn ($payment) => $paymentPerspective($payment) === 'pay' && in_array((string) $payment->payment_status, ['unpaid', 'pending_confirmation', 'paid'], true))
            ->groupBy(fn ($payment) => $payment->trip?->driver?->name ?: 'Driver')
            ->map(function ($rows, $driverName) {
                $unpaid = (float) $rows->where('payment_status', 'unpaid')->sum('amount_due');
                $pending = (float) $rows->where('payment_status', 'pending_confirmation')->sum('amount_due');
                $paid = (float) $rows->where('payment_status', 'paid')->sum('amount_due');
                $total = (float) $rows->sum('amount_due');
                $outstanding = $unpaid + $pending;

                return [
                    'name' => $driverName,
                    'driver' => $driverName,
                    'unpaid' => $unpaid,
                    'pending' => $pending,
                    'paid' => $paid,
                    'total' => $total,
                    'outstanding' => $outstanding,
                    'records' => $rows->count(),
                    'count' => $rows->count(),
                    'sample_payment' => $rows->first(),
                    'next_trip' => $rows->first()?->trip?->savedRoute?->route_name
                        ?: trim(($rows->first()?->trip?->pickup_name ?: '-') . ' -> ' . ($rows->first()?->trip?->destination_name ?: '-')),
                ];
            })
            ->sortByDesc(fn ($row) => $row['outstanding'])
            ->values();
        $summaryDetailRows = $isAdmin ? $driverCollectionRows : $passengerPayRows;
        $summaryDetailTitle = $isAdmin ? 'All user payments' : 'Where you still need to pay';
        $summaryRecordCount = $isAdmin ? $allLiveCount : ($canReviewQueue ? $collectCount : $payCount);
        // The ledger list is rendered in full and narrowed client-side, so its
        // pager is client-side too; this is the page size it slices with.
        $ledgerPageSize = 12;
        $displayPaymentsDesktop = $allLivePayments;
        $displayPaymentsMobile = $allLivePayments;
    @endphp

    <div class="payments-page">
        <section class="payments-page-header">
            <div class="payments-page-header-left">
                <p class="payments-eyebrow">Payments</p>
                <h1 class="payments-h1">{{ $isPassenger ? 'Your payments' : 'Payment ledger' }}</h1>
                <p class="payments-sub">
                    {{ $isAdmin ? 'Review every user payment in one admin ledger.' : ($canReviewQueue ? 'Payments you need to pay are separated from fares you collect as a driver.' : 'Track fares you need to pay and payments already confirmed.') }}
                </p>
                <div class="payments-tab-strip">
                    <button class="payments-tab {{ $activePaymentFilter === 'all' && $activeDirection === 'all' ? 'active' : '' }}" type="button" onclick="pmtTab(this,'all')">All &middot; {{ $allLiveCount }}</button>
                    @if($hasSplitPaymentDirections)
                        <button class="payments-tab {{ $activeDirection === 'pay' ? 'active' : '' }}" type="button" onclick="pmtTab(this,'pay')">To pay &middot; {{ $payCount }}</button>
                        <button class="payments-tab {{ $activeDirection === 'collect' ? 'active' : '' }}" type="button" onclick="pmtTab(this,'collect')">To collect &middot; {{ $collectCount }}</button>
                    @else
                        <button class="payments-tab {{ $activePaymentFilter === 'unpaid' ? 'active' : '' }}" type="button" onclick="pmtTab(this,'unpaid')">Unpaid &middot; {{ $unpaidCount }}</button>
                    @endif
                    <button class="payments-tab {{ $activePaymentFilter === 'review' ? 'active' : '' }}" type="button" onclick="pmtTab(this,'review')">{{ $canReviewQueue ? 'Review' : 'Pending' }} &middot; {{ $reviewCount }}</button>
                    <button class="payments-tab {{ $activePaymentFilter === 'confirmed' ? 'active' : '' }}" type="button" onclick="pmtTab(this,'confirmed')">Confirmed &middot; {{ $confirmedCount }}</button>
                </div>
            </div>
            <div class="payments-header-actions">
                <button type="button" class="btn btn-ghost btn-sm payments-filter-launch" data-payments-filter-launch>
                    <i class="fa-solid fa-sliders"></i>
                    Filter
                </button>
            </div>
        </section>

        <form method="GET" action="{{ route('payments.index') }}" class="payments-filter-panel trips-filter-form" id="paymentsFilterPanel" style="{{ request()->hasAny(['date_from','date_to','payment_search']) ? 'display:grid' : 'display:none' }}">
            @if($activePaymentFilter !== 'all')
                <input type="hidden" name="payment_filter" value="{{ $activePaymentFilter }}">
            @endif
            @if($activeDirection !== 'all')
                <input type="hidden" name="direction" value="{{ $activeDirection }}">
            @endif
            <p class="trips-filter-hint">Filters apply automatically on change.</p>
            <div class="trips-filter-field">
                <label class="trips-filter-label" for="myPaymentsFromDate">From Date</label>
                <input id="myPaymentsFromDate" name="date_from" class="trips-filter-input" type="date" value="{{ $filters['date_from'] ?? request('date_from') }}">
            </div>
            <div class="trips-filter-field">
                <label class="trips-filter-label" for="myPaymentsToDate">To Date</label>
                <input id="myPaymentsToDate" name="date_to" class="trips-filter-input" type="date" value="{{ $filters['date_to'] ?? request('date_to') }}">
            </div>
            <div class="trips-filter-field">
                <label class="trips-filter-label" for="myPaymentsPassengerSearch">Search</label>
                <input id="myPaymentsPassengerSearch" name="payment_search" class="trips-filter-input" type="search" placeholder="Trip ID, driver, or passenger" value="{{ $filters['payment_search'] ?? request('payment_search') }}">
            </div>
            <div class="trips-filter-actions">
                <a href="{{ route('payments.index', array_filter(['payment_filter' => $activePaymentFilter !== 'all' ? $activePaymentFilter : null, 'direction' => $activeDirection !== 'all' ? $activeDirection : null])) }}" class="trips-filter-reset">Reset</a>
            </div>
        </form>

        <section class="payments-mobile-total">
            @if($hasSplitPaymentDirections)
                <input class="payments-summary-mode-input" type="radio" name="mobile_summary_mode" id="mobileSummaryDriver" checked>
                <input class="payments-summary-mode-input" type="radio" name="mobile_summary_mode" id="mobileSummaryPassenger">
                <div class="payments-summary-top payments-summary-top--switch-only">
                    <div class="payments-summary-switch" aria-label="Summary view">
                        <label for="mobileSummaryDriver">As driver</label>
                        <label for="mobileSummaryPassenger">As passenger</label>
                    </div>
                </div>
                <span class="payments-total-label payments-total-label--range">{{ strtoupper($monthLabel) }}</span>
                <div class="payments-summary-mode-panel payments-summary-driver-panel">
                    <strong>RM {{ number_format($driverUnpaidAmount + $driverPendingAmount, 2) }}</strong>
                    <small>To collect · Paid RM {{ number_format($driverPaidAmount, 2) }}</small>
                    <div class="payments-total-metrics">
                        <div class="payments-total-metric">
                            <span>Pending</span>
                            <b>RM {{ number_format($driverPendingAmount, 2) }}</b>
                        </div>
                        <div class="payments-total-metric">
                            <span>Paid</span>
                            <b>RM {{ number_format($driverPaidAmount, 2) }}</b>
                        </div>
                    </div>
                <details class="payments-summary-detail">
                    <summary>Passenger payment status</summary>
                    <div class="payments-summary-detail-list">
                        @forelse($driverCollectionRows as $debtRow)
                            <div class="payments-summary-detail-row">
                                <div class="payments-summary-card-head">
                                    <span class="payments-summary-card-name">{{ $debtRow['name'] }}</span>
                                    <span class="payments-summary-card-badge">{{ $debtRow['records'] }} records</span>
                                </div>
                                <div class="payments-summary-receipt-body">
                                    <div class="payments-summary-receipt-line is-unpaid">
                                        <span><i class="fa-solid fa-circle-exclamation"></i> Unpaid</span>
                                        <span class="line-val">RM {{ number_format((float) $debtRow['unpaid'], 2) }}</span>
                                    </div>
                                    <div class="payments-summary-receipt-line is-pending">
                                        <span><i class="fa-regular fa-clock"></i> Pending</span>
                                        <span class="line-val">RM {{ number_format((float) $debtRow['pending'], 2) }}</span>
                                    </div>
                                    <div class="payments-summary-receipt-line is-paid">
                                        <span><i class="fa-solid fa-circle-check"></i> Paid</span>
                                        <span class="line-val">RM {{ number_format((float) $debtRow['paid'], 2) }}</span>
                                    </div>
                                    <div class="payments-summary-receipt-total">
                                        <span>Total</span>
                                        <span class="total-val">RM {{ number_format((float) $debtRow['total'], 2) }}</span>
                                    </div>
                                </div>
                                @if(($debtRow['unpaid'] + $debtRow['pending']) > 0)
                                    <button type="button" 
                                            class="btn-select-person-unpaid" 
                                            data-person-name="{{ $debtRow['name'] }}"
                                            data-direction="collect"
                                            title="Select all unpaid payments for {{ $debtRow['name'] }}">
                                        <i class="fa-solid fa-square-check"></i> Select Unpaid (RM {{ number_format((float) ($debtRow['unpaid'] + $debtRow['pending']), 2) }})
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="payments-summary-detail-empty">No passenger payment still pending.</div>
                        @endforelse
                    </div>
                </details>
                </div>
                <div class="payments-summary-mode-panel payments-summary-passenger-panel">
                    <strong>RM {{ number_format($myUnpaidAmount + $myPendingAmount, 2) }}</strong>
                    <small>To pay · Paid RM {{ number_format($myPaidAmount, 2) }}</small>
                    <div class="payments-total-metrics">
                        <div class="payments-total-metric">
                            <span>Pending</span>
                            <b>RM {{ number_format($myPendingAmount, 2) }}</b>
                        </div>
                        <div class="payments-total-metric">
                            <span>Paid</span>
                            <b>RM {{ number_format($myPaidAmount, 2) }}</b>
                        </div>
                    </div>
                </div>
            @else
                <div class="payments-summary-top">
                    <div class="payments-summary-title-block">
                        <span class="payments-total-label">{{ strtoupper($monthLabel) }}</span>
                        <strong>RM {{ number_format($summaryMainAmount, 2) }}</strong>
                        <small>{{ $summaryMainLabel }} · {{ $summaryRecordCount }} records</small>
                    </div>
                </div>
                <div class="payments-total-metrics">
                    @if($summaryMainLabel !== 'To pay')
                        <div class="payments-total-metric">
                            <span>Unpaid</span>
                            <b>RM {{ number_format($summaryPrimaryAmount, 2) }}</b>
                        </div>
                    @endif
                    <div class="payments-total-metric">
                        <span>Pending</span>
                        <b>RM {{ number_format($summarySecondaryAmount, 2) }}</b>
                    </div>
                    <div class="payments-total-metric">
                        <span>Paid</span>
                        <b>RM {{ number_format($summaryPaidAmount, 2) }}</b>
                    </div>
                </div>
                <details class="payments-summary-detail">
                    <summary>{{ $summaryDetailTitle }}</summary>
                    <div class="payments-summary-detail-list">
                        @forelse($summaryDetailRows as $payRow)
                            <div class="payments-summary-detail-row">
                                <div class="payments-summary-card-head">
                                    <span class="payments-summary-card-name">{{ $payRow['name'] }}</span>
                                    <span class="payments-summary-card-badge">{{ $payRow['records'] }} records</span>
                                </div>
                                <div class="payments-summary-receipt-body">
                                    <div class="payments-summary-receipt-line is-unpaid">
                                        <span><i class="fa-solid fa-circle-exclamation"></i> Unpaid</span>
                                        <span class="line-val">RM {{ number_format((float) $payRow['unpaid'], 2) }}</span>
                                    </div>
                                    <div class="payments-summary-receipt-line is-pending">
                                        <span><i class="fa-regular fa-clock"></i> Pending</span>
                                        <span class="line-val">RM {{ number_format((float) $payRow['pending'], 2) }}</span>
                                    </div>
                                    <div class="payments-summary-receipt-line is-paid">
                                        <span><i class="fa-solid fa-circle-check"></i> Confirmed</span>
                                        <span class="line-val">RM {{ number_format((float) $payRow['paid'], 2) }}</span>
                                    </div>
                                    <div class="payments-summary-receipt-total">
                                        <span>Total</span>
                                        <span class="total-val">RM {{ number_format((float) $payRow['total'], 2) }}</span>
                                    </div>
                                </div>
                                @if(($payRow['unpaid'] + $payRow['pending']) > 0)
                                    <button type="button" 
                                            class="btn-select-person-unpaid" 
                                            data-person-name="{{ $payRow['name'] }}"
                                            data-direction="unpaid"
                                            title="Select all unpaid payments for {{ $payRow['name'] }}">
                                        <i class="fa-solid fa-square-check"></i> Select Unpaid (RM {{ number_format((float) ($payRow['unpaid'] + $payRow['pending']), 2) }})
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="payments-summary-detail-empty">No active payment due right now.</div>
                        @endforelse
                    </div>
                </details>
            @endif
        </section>

        <section class="payments-tools-card">
            <div class="payments-tools-grid">
                <div class="payments-tool-item">
                    <span class="payments-tool-label">My Records</span>
                    <span class="payments-tool-value" id="paymentsToolMyRecords">{{ $myRecordCount }}</span>
                </div>
                @if(! $isPassenger)
                    <div class="payments-tool-item">
                        <span class="payments-tool-label">Queue Records</span>
                        <span class="payments-tool-value" id="paymentsToolQueueRecords">{{ $queueCount }}</span>
                    </div>
                @endif
                <div class="payments-tool-item">
                    <span class="payments-tool-label">Total Unpaid</span>
                    <span class="payments-tool-value" id="paymentsToolUnpaidAmount">RM {{ number_format($unpaidAmt, 2) }}</span>
                </div>
                <div class="payments-tool-item">
                    <span class="payments-tool-label">Pending Amount</span>
                    <span class="payments-tool-value" id="paymentsToolPendingAmount">RM {{ number_format($pendingAmt, 2) }}</span>
                </div>
            </div>
        </section>



        @if($showMyPayments)
            <section class="payments-card payments-summary-card">
                <h2 class="payments-section-title">My Summary</h2>
                <div class="payments-summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Unpaid</div>
                        <div id="paymentsMyUnpaidAmount" class="summary-value">RM {{ number_format((float) ($summary['my']['unpaid']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsMyUnpaidCount" class="summary-count">{{ $summary['my']['unpaid']['count'] ?? 0 }} records</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">My Pending</div>
                        <div id="paymentsMyPendingAmount" class="summary-value">RM {{ number_format((float) ($summary['my']['pending_confirmation']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsMyPendingCount" class="summary-count">{{ $summary['my']['pending_confirmation']['count'] ?? 0 }} records</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Paid</div>
                        <div id="paymentsMyPaidAmount" class="summary-value">RM {{ number_format((float) ($summary['my']['paid']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsMyPaidCount" class="summary-count">{{ $summary['my']['paid']['count'] ?? 0 }} records</div>
                    </div>
                </div>
            </section>
        @endif

            <div class="payments-main-grid">
            <section class="payments-card payments-ledger-card" id="my-payments-list">
                <h2 class="payments-section-title">Transactions</h2>
                <p class="payments-section-subtitle">Track fares paid as a passenger and received as a driver.</p>

                <div style="position: relative; min-height: 250px;">
                    {{-- Skeleton Loading Container --}}
                    <style>
                        .payments-skel-container .payments-mobile-skel { display: flex; flex-direction: column; gap: 12px; }
                        .payments-skel-container .payments-table-skel { display: none; }
                        @media (min-width: 1024px) {
                            .payments-skel-container .payments-mobile-skel { display: none; }
                            .payments-skel-container .payments-table-skel { display: block; }
                        }
                    </style>
                    <div class="payments-skel-container" id="payments-skel-container" style="display:block;">
                    {{-- Desktop Table Skeleton --}}
                    <div class="payments-table-skel">
                        <div class="payments-table-wrap">
                            <table class="payments-table" style="pointer-events:none; margin:0; border:0; width:100%;">
                                <thead>
                                    <tr>
                                        @if(!empty($hasCheckboxes))
                                            <th class="col-cb"><span class="sk" style="height:16px; width:16px; border-radius:4px; display:inline-block;"></span></th>
                                        @endif
                                        <th class="col-counterparty">Counterparty</th>
                                        <th class="col-trip">Trip</th>
                                        <th class="col-status">Status</th>
                                        <th class="col-amount right">Amount</th>
                                        <th class="col-date">Date</th>
                                        <th class="col-action right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($i = 0; $i < 4; $i++)
                                    <tr>
                                        @if(!empty($hasCheckboxes))
                                            <td class="col-cb">
                                                <span class="sk" style="height:16px; width:16px; border-radius:4px; display:inline-block; margin:0 auto;"></span>
                                            </td>
                                        @endif
                                        <td class="col-counterparty">
                                            <div style="display:flex; flex-direction:column; gap:5px;">
                                                <span class="sk" style="height:14px; width:110px; display:block; border-radius:4px;"></span>
                                                <span class="sk" style="height:10px; width:65px; display:block; border-radius:4px;"></span>
                                            </div>
                                        </td>
                                        <td class="col-trip">
                                            <div style="display:flex; flex-direction:column; gap:5px;">
                                                <span class="sk" style="height:14px; width:80%; display:block; border-radius:4px;"></span>
                                                <span class="sk" style="height:10px; width:120px; display:block; border-radius:4px;"></span>
                                            </div>
                                        </td>
                                        <td class="col-status" style="text-align:center;">
                                            <span class="sk" style="height:22px; width:70px; display:inline-block; border-radius:999px;"></span>
                                        </td>
                                        <td class="col-amount right" style="text-align:right;">
                                            <span class="sk" style="height:15px; width:65px; display:inline-block; border-radius:4px; margin-left:auto;"></span>
                                        </td>
                                        <td class="col-date">
                                            <div style="display:flex; flex-direction:column; gap:4px;">
                                                <span class="sk" style="height:12px; width:75px; display:block; border-radius:4px;"></span>
                                                <span class="sk" style="height:10px; width:40px; display:block; border-radius:4px;"></span>
                                            </div>
                                        </td>
                                        <td class="col-action right" style="text-align:center;">
                                            <span class="sk" style="height:32px; width:100%; display:block; border-radius:9px;"></span>
                                        </td>
                                    </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- Mobile List Skeleton --}}
                    <div class="payments-mobile-skel">
                        @for($i = 0; $i < 3; $i++)
                        <div class="payment-mobile-item" style="pointer-events:none; opacity:0.95; background:var(--surface) !important; border:1px solid var(--hairline-strong) !important; border-radius:16px !important; padding:14px 14px 12px !important; display:flex !important; flex-direction:column !important; gap:10px !important; box-shadow:0 8px 20px rgba(15,23,42,.05) !important;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div style="flex:1; display:flex; flex-direction:column; gap:6px; min-width:0; padding-right:12px;">
                                    <span class="sk" style="height:18px; width:75%; border-radius:6px; display:block;"></span>
                                    <div style="display:flex; gap:12px; align-items:center;">
                                        <span class="sk" style="height:12px; width:90px; border-radius:4px; display:inline-block;"></span>
                                        <span class="sk" style="height:12px; width:65px; border-radius:4px; display:inline-block;"></span>
                                    </div>
                                </div>
                                <span class="sk" style="height:24px; width:80px; border-radius:999px; flex-shrink:0;"></span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="sk" style="height:12px; width:120px; border-radius:4px;"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:2px;">
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    <span class="sk" style="height:20px; width:75px; border-radius:6px;"></span>
                                </div>
                                <span class="sk" style="height:36px; width:102px; border-radius:11px;"></span>
                            </div>
                        </div>
                        @endfor
                    </div>
                </div>

                <div class="payments-real-container" id="payments-real-container" data-initial-load="{{ ($initialLoad ?? false) ? 'true' : 'false' }}" style="display:none; opacity:0;">
                @if($displayPaymentsMobile->isNotEmpty())
                <div class="payments-mobile-list">
                    @foreach($displayPaymentsMobile as $payment)
                        @php
                            $isReturnTrip = (bool) ($payment->trip?->is_return_trip ?? false);
                            $pickupName = $payment->trip?->pickup_name ?? '-';
                            $pickupLat = $payment->trip?->pickup_latitude ?? '';
                            $pickupLng = $payment->trip?->pickup_longitude ?? '';
                            $destinationName = $payment->trip?->destination_name ?? '-';
                            $destinationLat = $payment->trip?->destination_latitude ?? '';
                            $destinationLng = $payment->trip?->destination_longitude ?? '';
                            $pairedTripId = $isReturnTrip
                                ? ($payment->trip?->parentTrip?->id)
                                : ($payment->trip?->returnTrip?->id);
                            $tripRef = $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT);
                            $routeLabel = $payment->trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                            $statusClass = $payment->payment_status === 'paid'
                                ? 'status-paid'
                                : ($payment->payment_status === 'pending_confirmation' ? 'status-pending' : 'status-unpaid');
                            $statusText = $payment->payment_status === 'pending_confirmation'
                                ? 'Pending Confirmation'
                                : ($payment->payment_status === 'paid' ? 'Paid' : ($payment->payment_status === 'unpaid' ? 'Unpaid' : ucfirst($payment->payment_status)));
                            $methodLabel = match ($payment->payment_method) {
                                'duitnow_qr' => 'DuitNow QR',
                                'bank_account' => 'Bank Account',
                                'digital_wallet' => 'Digital Wallet',
                                'others' => 'Others',
                                default => '-',
                            };
                            $reminderMeta = $reminderState[$payment->id] ?? ['can_send' => true, 'seconds_left' => 0];
                            $canSendReminder = (bool) $reminderMeta['can_send'];
                            $secondsLeft = (int) ($reminderMeta['seconds_left'] ?? 0);
                            $participantsPayload = $payment->trip?->participants?->map(function ($participant) {
                                $participantUser = $participant->user;
                                return [
                                    'name' => $participantUser?->name ?: '-',
                                    'email' => $participantUser?->email ?: '',
                                    'photo_url' => $participantUser?->profile_photo_url,
                                    'is_driver' => (bool) $participant->is_driver,
                                ];
                            })->values()->all() ?? [];
                            $driverPhotoUrl = ($payment->trip?->driver?->profile_photo_url ?? '');
                            $driverBank = $payment->trip?->driver?->payment_bank_name ?: '-';
                            $driverAccountName = $payment->trip?->driver?->payment_account_name ?: '-';
                            $driverAccountNumber = $payment->trip?->driver?->payment_account_number ?: '-';
                            $driverDuitnowQr = $payment->trip?->driver?->payment_qr_duitnow_url ?: '';
                            $driverTngQr = $payment->trip?->driver?->payment_qr_tng_url ?: '';
                            $fareBreakdown = $paymentFareBreakdown($payment);
                            $isDriverQueueRecord = ((int) ($payment->trip?->driver_id ?? 0) === (int) auth()->id() && (int) $payment->user_id !== (int) auth()->id()) || ($isAdmin && (int) ($payment->trip?->driver_id ?? 0) !== (int) auth()->id() && (int) $payment->user_id !== (int) auth()->id());
                            $counterpartyName = $isDriverQueueRecord
                                ? ($payment->user?->name ?: '-')
                                : ($payment->trip?->driver?->name ?: '-');
                            $counterparty = ($counterpartyName === auth()->user()->name && !$isDriverQueueRecord)
                                ? 'Self (Paying Driver)'
                                : $counterpartyName;
                            $initials = $paymentInitials($counterparty);
                            $amountSign = $isDriverQueueRecord ? '+' : '-';
                            $shortStatusText = $payment->payment_status === 'pending_confirmation'
                                ? ($isAdmin ? 'Admin Review' : 'Driver Review')
                                : $statusText;
                            $perspective = $isDriverQueueRecord ? 'collect' : 'pay';
                            $perspectiveLabel = $isAdmin ? 'Admin review' : ($isDriverQueueRecord ? 'You collect' : 'You pay');
                            $isInitialHidden = false;
                            if ($activeDirection === 'pay' && $perspective !== 'pay') {
                                $isInitialHidden = true;
                            } elseif ($activeDirection === 'collect' && $perspective !== 'collect') {
                                $isInitialHidden = true;
                            } elseif ($activePaymentFilter === 'unpaid' && $payment->payment_status !== 'unpaid') {
                                $isInitialHidden = true;
                            } elseif ($activePaymentFilter === 'review' && $payment->payment_status !== 'pending_confirmation') {
                                $isInitialHidden = true;
                            } elseif ($activePaymentFilter === 'confirmed' && $payment->payment_status !== 'paid') {
                                $isInitialHidden = true;
                            }
                            $paymentActionLabel = $isDriverQueueRecord
                                ? ($payment->payment_status === 'pending_confirmation' ? 'Review' : ($payment->payment_status === 'unpaid' ? 'Notify' : 'Receipt'))
                                : ($payment->payment_status === 'unpaid' ? 'Pay' : ($payment->payment_status === 'pending_confirmation' ? 'Pending' : 'Receipt'));
                            $paymentActionIcon = $isDriverQueueRecord
                                ? ($payment->payment_status === 'pending_confirmation' ? 'fa-solid fa-clipboard-check' : ($payment->payment_status === 'unpaid' ? 'fa-regular fa-bell' : 'fa-solid fa-receipt'))
                                : ($payment->payment_status === 'unpaid' ? 'fa-solid fa-credit-card' : ($payment->payment_status === 'pending_confirmation' ? 'fa-regular fa-clock' : 'fa-solid fa-receipt'));
                        @endphp
                        <article
                            class="payment-mobile-item open-trip-card js-payment-filter-item {{ $isInitialHidden ? 'payments-filter-hidden' : '' }}"
                            data-passenger="{{ $counterparty }}"
                            data-payment-perspective="{{ $perspective }}"
                            data-pmt-status="{{ $payment->payment_status }}"
                            data-status-hidden="{{ $isInitialHidden ? '1' : '0' }}"
                            style="{{ $isInitialHidden ? 'display:none !important;' : '' }}"
                            data-filter-date="{{ $payment->trip?->trip_datetime?->format('Y-m-d') ?: '' }}"
                            data-filter-person="{{ trim(($payment->user?->name ?: auth()->user()->name) . ' ' . ($payment->trip?->driver?->name ?: '')) }}"
                            data-trip-id="{{ $payment->trip_id }}"
                            data-trip-ref="{{ $tripRef }}"
                            data-route="{{ $routeLabel }}"
                            data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                            data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                            data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                            data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                            data-pickup-name="{{ $pickupName }}"
                            data-pickup-lat="{{ $pickupLat }}"
                            data-pickup-lng="{{ $pickupLng }}"
                            data-destination-name="{{ $destinationName }}"
                            data-destination-lat="{{ $destinationLat }}"
                            data-destination-lng="{{ $destinationLng }}"
                            data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                            data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                            data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                            data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                            data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                            data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                            data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                            data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                            data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                            data-payment-status="{{ $statusText }}"
                            data-payment-method="{{ $methodLabel }}"
                            data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                            data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                            data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                            data-participants='@json($participantsPayload)'
                            data-passenger-count="{{ count($participantsPayload) }}"
                        >
                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                @php
                                    $canConfirmMobile = $isAdmin || (int) ($payment->trip?->driver_id ?? 0) === (int) auth()->id() || (int) $payment->user_id === (int) auth()->id();
                                @endphp
                                @if($canConfirmMobile && in_array($payment->payment_status, ['unpaid', 'pending_confirmation']))
                                    @php
                                        $isSelfMobile = $counterparty === 'Self (Paying Driver)';
                                    @endphp
                                    <div class="payment-bulk-checkbox-wrapper" style="padding-top:2px;">
                                        <label class="ch-cb-container">
                                            <input type="checkbox" name="payment_ids[]" value="{{ $payment->id }}" class="bulk-payment-cb" data-is-self="{{ $isSelfMobile ? '1' : '0' }}" form="bulk-confirm-form">
                                            <span class="ch-checkbox"><i class="fa-solid fa-check"></i></span>
                                        </label>
                                    </div>
                                @endif
                                <div style="flex:1; min-width:0;">
                                    <div style="margin-bottom:12px;">
                                        <div style="min-width:0;flex:1;">
                                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                                                <h2 class="payment-route-title" style="margin-bottom:2px; font-size:14px;">{{ $counterparty }}</h2>
                                                <span class="status-chip {{ $statusClass }}" style="font-size:10px; padding:4px 8px;">{{ $shortStatusText }}</span>
                                            </div>
                                            <div style="font-size:12px; color:var(--muted); font-weight:600; margin-bottom:4px;">
                                                <i class="{{ $isDriverQueueRecord ? 'fa-solid fa-sack-dollar' : 'fa-solid fa-credit-card' }}" style="color:#b45309;font-size:10px;margin-right:2px;"></i> {{ $perspectiveLabel }} <span style="font-family:var(--font-mono,monospace);"><i class="fa-solid fa-hashtag" style="color:#c2410c;font-size:10px;margin-right:2px;"></i>{{ $tripRef }}</span>
                                            </div>
                                            <div style="font-size:13px; color:var(--ink); font-weight:600; word-break:break-word; margin-bottom:2px; line-height:1.3;">
                                                {{ $routeLabel }}
                                            </div>
                                            <div style="font-size:12px; color:var(--muted-2); font-family:var(--font-ui), sans-serif;">
                                                @if($payment->trip?->trip_datetime)
                                                    {{ $payment->trip->trip_datetime->format('d M Y, H:i') }}
                                                @else
                                                    -
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                            <div class="payment-bottom-row" style="margin-top:10px; padding-top:10px; border-top:1px solid var(--hairline);">
                                <div class="payment-fare-card">
                                    <span class="payment-fare-value">RM {{ number_format((float) $payment->amount_due, 2) }}</span>
                                    @if($fareBreakdown['has_extra'])
                                        <span class="payment-fare-label">Base RM {{ number_format((float) $fareBreakdown['base'], 2) }} + Extra RM {{ number_format((float) $fareBreakdown['extra'], 2) }}</span>
                                    @endif
                                </div>
                                <div class="payments-action-row">
                                @if($isDriverQueueRecord && $payment->payment_status === 'pending_confirmation')
                                    <button
                                        type="button"
                                        class="payments-btn payments-btn-highlight open-request-btn"
                                        data-payment-id="{{ $payment->id }}"
                                        data-passenger="{{ $payment->user?->name ?: '-' }}"
                                        data-trip="{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}"
                                        data-amount="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-method="{{ $methodLabel }}"
                                        data-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-approve-action="{{ route('payments.confirm-paid', $payment) }}"
                                        data-reject-action="{{ route('payments.reject-paid', $payment) }}"
                                    ><i class="{{ $paymentActionIcon }}"></i> {{ $paymentActionLabel }}</button>
                                @elseif($isDriverQueueRecord && $payment->payment_status === 'unpaid')
                                    <div style="display:flex; gap:8px;">
                                        @php($reminderUi = $reminderButtonUi($canSendReminder, $secondsLeft))
                                        <form method="POST" action="{{ route('payments.send-reminder', $payment) }}" class="payments-action-row">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="payments-btn payments-btn-soft {{ $canSendReminder ? '' : 'is-disabled' }} reminder-btn {{ $canSendReminder ? 'is-icon-only' : '' }}"
                                                {{ $canSendReminder ? '' : 'disabled' }}
                                                data-payment-id="{{ $payment->id }}"
                                                data-seconds-left="{{ $secondsLeft }}"
                                                data-ready-style="icon"
                                                title="{{ $reminderUi['title'] }}"
                                            ><i class="{{ $reminderUi['icon'] }}"></i>{{ $reminderUi['label'] ? ' ' . $reminderUi['label'] : '' }}</button>
                                        </form>
                                        <button
                                            type="button"
                                            class="payments-btn ch-btn-green open-mark-paid-modal"
                                            data-action="{{ route('payments.confirm-paid', $payment) }}"
                                            data-passenger="{{ $counterparty !== 'Self (Paying Driver)' ? $counterparty : ($payment->user?->name ?: 'Passenger') }}"
                                            data-trip="{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}"
                                            data-amount="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        >
                                            <i class="fa-solid fa-check"></i> Mark Paid
                                        </button>
                                    </div>
                                @elseif(! $isDriverQueueRecord && $payment->payment_status === 'unpaid')
                                    <form method="POST" action="{{ route('payments.mark-paid', $payment) }}" class="payments-action-row">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="payment_method" value="duitnow_qr">
                                        <input class="payments-input" type="text" name="remarks" placeholder="Remarks">
                                        <button
                                            type="button"
                                            class="payments-btn payments-btn-primary open-payment-paynow-btn ch-btn-green"
                                            data-action="{{ route('payments.mark-paid', $payment) }}"
                                            data-passenger="{{ $payment->user?->name ?: auth()->user()->name }}"
                                            data-initials="{{ $paymentInitials($payment->user?->name ?: auth()->user()->name) }}"
                                            data-trip="{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}"
                                            data-route="{{ $routeLabel }}"
                                            data-amount="{{ number_format((float) $payment->amount_due, 2) }}"
                                            data-base-amount="{{ number_format((float) $fareBreakdown['base'], 2) }}"
                                            data-extra-fee="{{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                            data-has-extra="{{ $fareBreakdown['has_extra'] ? '1' : '0' }}"
                                            data-driver-name="{{ $payment->trip?->driver?->name ?: '-' }}"
                                            data-driver-email="{{ $payment->trip?->driver?->email ?: '-' }}"
                                            data-driver-photo="{{ $driverPhotoUrl }}"
                                            data-driver-bank="{{ $driverBank }}"
                                            data-driver-account-name="{{ $driverAccountName }}"
                                            data-driver-account-number="{{ $driverAccountNumber }}"
                                            data-driver-duitnow-qr="{{ $driverDuitnowQr }}"
                                            data-driver-tng-qr="{{ $driverTngQr }}"
                                        ><i class="{{ $paymentActionIcon }}"></i> {{ $paymentActionLabel }}</button>
                                    </form>
                                @elseif($payment->payment_status === 'pending_confirmation')
                                    <button
                                        type="button"
                                        class="payments-btn payments-btn-soft open-trip-modal-btn"
                                        data-trip-id="{{ $payment->trip_id }}"
                                        data-trip-ref="{{ $tripRef }}"
                                        data-route="{{ $routeLabel }}"
                                        data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                        data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $pickupLat }}"
                                        data-pickup-lng="{{ $pickupLng }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $destinationLat }}"
                                        data-destination-lng="{{ $destinationLng }}"
                                        data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                        data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                                        data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                                        data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                                        data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                        data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                        data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                                        data-payment-status="{{ $statusText }}"
                                        data-payment-method="{{ $methodLabel }}"
                                        data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-participants='@json($participantsPayload)'
                                        data-passenger-count="{{ count($participantsPayload) }}"
                                    ><i class="{{ $paymentActionIcon }}"></i> {{ $paymentActionLabel }}</button>
                                @else
                                    <button
                                        type="button"
                                        class="payments-btn payments-btn-primary open-payment-receipt-btn"
                                        data-receipt-no="PAY-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}"
                                        data-trip-ref="{{ $tripRef }}"
                                        data-trip-mode="{{ ($payment->trip?->is_return_trip ?? false) ? 'Return' : 'Outbound' }}"
                                        data-route="{{ $routeLabel }}"
                                        data-passenger="{{ $payment->user?->name ?: '-' }}"
                                        data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                        data-amount="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                        data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                        data-has-extra="{{ $fareBreakdown['has_extra'] ? '1' : '0' }}"
                                        data-method="{{ $methodLabel }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('d M Y, H:i') ?: '-' }}"
                                        data-confirmed-at="{{ $payment->confirmed_at?->format('d M Y, H:i') ?: '-' }}"
                                    ><i class="{{ $paymentActionIcon }}"></i> {{ $paymentActionLabel }}</button>
                                @endif
                                </div>
                                </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                @endif
                @if($displayPaymentsDesktop->isNotEmpty())
                <div class="payments-table-wrap">
                    @php
                        $hasCheckboxes = false;
                        foreach($displayPaymentsDesktop as $p) {
                            $isDriver = (int) ($p->trip?->driver_id ?? 0) === (int) auth()->id();
                            $isPassenger = (int) $p->user_id === (int) auth()->id();
                            if (($isDriver || $isAdmin || $isPassenger) && in_array($p->payment_status, ['unpaid', 'pending_confirmation'])) {
                                $hasCheckboxes = true;
                                break;
                            }
                        }
                    @endphp
                    <table class="payments-table">
                        <thead>
                        <tr>
                            @if($hasCheckboxes)
                                <th class="col-cb" id="colCbHeader">
                                    @if($activePaymentFilter !== 'confirmed')
                                        <label class="ch-cb-container">
                                            <input type="checkbox" id="bulkSelectAllCb" onchange="var c=this.checked;document.querySelectorAll('.bulk-payment-cb').forEach(function(cb){cb.checked=c;});">
                                            <span class="ch-checkbox"><i class="fa-solid fa-check"></i></span>
                                        </label>
                                    @endif
                                </th>
                            @endif
                            <th class="col-counterparty">Counterparty</th>
                            <th class="col-trip">Trip</th>
                            <th class="col-status">Status</th>
                            <th class="col-amount right">Amount</th>
                            <th class="col-date">Date</th>
                            <th class="col-action right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                    @forelse($displayPaymentsDesktop as $payment)
                            @php
                                $isReturnTrip = (bool) ($payment->trip?->is_return_trip ?? false);
                                $pickupName = $payment->trip?->pickup_name ?? '-';
                                $pickupLat = $payment->trip?->pickup_latitude ?? '';
                                $pickupLng = $payment->trip?->pickup_longitude ?? '';
                                $destinationName = $payment->trip?->destination_name ?? '-';
                                $destinationLat = $payment->trip?->destination_latitude ?? '';
                                $destinationLng = $payment->trip?->destination_longitude ?? '';
                                $pairedTripId = $isReturnTrip
                                    ? ($payment->trip?->parentTrip?->id)
                                    : ($payment->trip?->returnTrip?->id);
                                $tripRef = $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT);
                                $routeLabel = $payment->trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                                $statusClass = $payment->payment_status === 'paid'
                                    ? 'status-paid'
                                    : ($payment->payment_status === 'pending_confirmation' ? 'status-pending' : 'status-unpaid');
                                $statusText = $payment->payment_status === 'pending_confirmation'
                                    ? 'Pending Confirmation'
                                    : ucfirst($payment->payment_status);
                                $methodLabel = match ($payment->payment_method) {
                                    'duitnow_qr' => 'DuitNow QR',
                                    'bank_account' => 'Bank Account',
                                    'digital_wallet' => 'Digital Wallet',
                                    'others' => 'Others',
                                    default => '-',
                                };
                                $reminderMeta = $reminderState[$payment->id] ?? ['can_send' => true, 'seconds_left' => 0];
                                $canSendReminder = (bool) $reminderMeta['can_send'];
                                $secondsLeft = (int) ($reminderMeta['seconds_left'] ?? 0);
                                $participantsPayload = $payment->trip?->participants?->map(function ($participant) {
                                    $participantUser = $participant->user;
                                    return [
                                        'name' => $participantUser?->name ?: '-',
                                        'email' => $participantUser?->email ?: '',
                                        'photo_url' => $participantUser?->profile_photo_url,
                                        'is_driver' => (bool) $participant->is_driver,
                                    ];
                                })->values()->all() ?? [];
                                $driverPhotoUrl = ($payment->trip?->driver?->profile_photo_url ?? '');
                                $driverBank = $payment->trip?->driver?->payment_bank_name ?: '-';
                                $driverAccountName = $payment->trip?->driver?->payment_account_name ?: '-';
                                $driverAccountNumber = $payment->trip?->driver?->payment_account_number ?: '-';
                                $driverDuitnowQr = $payment->trip?->driver?->payment_qr_duitnow_url ?: '';
                                $driverTngQr = $payment->trip?->driver?->payment_qr_tng_url ?: '';
                                $fareBreakdown = $paymentFareBreakdown($payment);
                                $isDriverQueueRecord = ((int) ($payment->trip?->driver_id ?? 0) === (int) auth()->id() && (int) $payment->user_id !== (int) auth()->id()) || ($isAdmin && (int) ($payment->trip?->driver_id ?? 0) !== (int) auth()->id() && (int) $payment->user_id !== (int) auth()->id());
                                $counterpartyName = $isDriverQueueRecord
                                    ? ($payment->user?->name ?: '-')
                                    : ($payment->trip?->driver?->name ?: '-');
                                $counterparty = ($counterpartyName === auth()->user()->name && !$isDriverQueueRecord)
                                    ? 'Self (Paying Driver)'
                                    : $counterpartyName;
                            $amountSign = $isDriverQueueRecord ? '+' : '-';
                            $shortStatusText = $payment->payment_status === 'pending_confirmation'
                                ? ($isAdmin ? 'Admin Review' : 'Driver Review')
                                : $statusText;
                            $perspective = $isDriverQueueRecord ? 'collect' : 'pay';
                            $perspectiveLabel = $isAdmin ? 'Admin review' : ($isDriverQueueRecord ? 'You collect' : 'You pay');
                            $isInitialHidden = false;
                            if ($activeDirection === 'pay' && $perspective !== 'pay') {
                                $isInitialHidden = true;
                            } elseif ($activeDirection === 'collect' && $perspective !== 'collect') {
                                $isInitialHidden = true;
                            } elseif ($activePaymentFilter === 'unpaid' && $payment->payment_status !== 'unpaid') {
                                $isInitialHidden = true;
                            } elseif ($activePaymentFilter === 'review' && $payment->payment_status !== 'pending_confirmation') {
                                $isInitialHidden = true;
                            } elseif ($activePaymentFilter === 'confirmed' && $payment->payment_status !== 'paid') {
                                $isInitialHidden = true;
                            }
                        @endphp
                            <tr
                                class="open-trip-card js-payment-filter-item {{ $isInitialHidden ? 'payments-filter-hidden' : '' }}"
                                data-passenger="{{ $counterparty }}"
                                data-payment-perspective="{{ $perspective }}"
                                data-pmt-status="{{ $payment->payment_status }}"
                                data-status-hidden="{{ $isInitialHidden ? '1' : '0' }}"
                                style="{{ $isInitialHidden ? 'display:none !important;' : '' }}"
                                data-filter-date="{{ $payment->trip?->trip_datetime?->format('Y-m-d') ?: '' }}"
                                data-filter-person="{{ trim(($payment->user?->name ?: auth()->user()->name) . ' ' . ($payment->trip?->driver?->name ?: '')) }}"
                                data-trip-id="{{ $payment->trip_id }}"
                                data-trip-ref="{{ $tripRef }}"
                                data-route="{{ $routeLabel }}"
                                data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                                data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                                data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                                data-pickup-name="{{ $pickupName }}"
                                data-pickup-lat="{{ $pickupLat }}"
                                data-pickup-lng="{{ $pickupLng }}"
                                data-destination-name="{{ $destinationName }}"
                                data-destination-lat="{{ $destinationLat }}"
                                data-destination-lng="{{ $destinationLng }}"
                                data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                                data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                                data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                                data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                                data-payment-status="{{ $statusText }}"
                                data-payment-method="{{ $methodLabel }}"
                                data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                data-participants='@json($participantsPayload)'
                                data-passenger-count="{{ count($participantsPayload) }}"
                            >
                            @if($hasCheckboxes)
                                <td class="col-cb">
                                    @php
                                        $isSelf = $counterparty === 'Self (Paying Driver)';
                                        $canConfirm = $isAdmin || (int) ($payment->trip?->driver_id ?? 0) === (int) auth()->id() || (int) $payment->user_id === (int) auth()->id();
                                    @endphp
                                    @if($canConfirm && in_array($payment->payment_status, ['unpaid', 'pending_confirmation']))
                                        <label class="ch-cb-container">
                                            <input type="checkbox" name="payment_ids[]" value="{{ $payment->id }}" class="bulk-payment-cb" data-is-self="{{ $isSelf ? '1' : '0' }}" form="bulk-confirm-form">
                                            <span class="ch-checkbox"><i class="fa-solid fa-check"></i></span>
                                        </label>
                                    @else
                                        @if($payment->payment_status === 'paid')
                                            <span class="ch-status-box ch-status-paid" title="Confirmed Paid"><i class="fa-solid fa-check"></i></span>
                                        @elseif($payment->payment_status === 'pending_confirmation')
                                            <span class="ch-status-box ch-status-pending {{ $isSelf ? 'is-self' : '' }}" title="Pending Review">
                                                <i class="fa-solid fa-circle-notch fa-spin"></i>
                                            </span>
                                        @else
                                            <span class="ch-status-box ch-status-unpaid {{ $isSelf ? 'is-self' : '' }}" title="Unpaid"></span>
                                        @endif
                                    @endif
                                </td>
                            @endif
                                <td class="col-counterparty">
                                    <div class="payment-person-block">
                                        <div>
                                            <div class="payment-name">{{ $counterparty }}</div>
                                            <div class="payment-meta">{{ $perspectiveLabel }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-trip">
                                    <div class="payment-route-title">{{ $routeLabel }}</div>
                                    <div class="payment-trip-meta">
                                        <span><i class="fa-solid fa-hashtag"></i> {{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}</span>
                                        <span><i class="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'fa-solid fa-repeat' : 'fa-solid fa-route' }}"></i> {{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two-way' : 'One-way' }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="payments-link open-trip-modal-btn"
                                        data-trip-id="{{ $payment->trip_id }}"
                                        data-trip-ref="{{ $tripRef }}"
                                        data-route="{{ $routeLabel }}"
                                        data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                        data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $pickupLat }}"
                                        data-pickup-lng="{{ $pickupLng }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $destinationLat }}"
                                        data-destination-lng="{{ $destinationLng }}"
                                        data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                        data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                                        data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                                        data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                                        data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                        data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                        data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                                        data-payment-status="{{ $statusText }}"
                                        data-payment-method="{{ $methodLabel }}"
                                        data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-participants='@json($participantsPayload)'
                                        data-passenger-count="{{ count($participantsPayload) }}"
                                    ><span>View</span></button>
                                </td>
                                <td class="col-status"><span class="status-chip {{ $statusClass }}">{{ $shortStatusText }}</span></td>
                                <td class="col-amount right">
                                    <span class="payment-table-amount">{{ $amountSign }}RM {{ number_format((float) $payment->amount_due, 2) }}</span>
                                    @if($fareBreakdown['has_extra'])
                                        <div style="font-size:11px;color:#64748b;font-weight:700;">Base RM {{ number_format((float) $fareBreakdown['base'], 2) }} + Extra RM {{ number_format((float) $fareBreakdown['extra'], 2) }}</div>
                                    @endif
                                </td>
                                <td class="col-date">
                                    @if($payment->trip?->trip_datetime)
                                        <span class="payment-table-date">
                                            {{ $payment->trip->trip_datetime->format('d M Y') }}
                                            <span class="payment-table-time">{{ $payment->trip->trip_datetime->format('H:i') }}</span>
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="col-action right">
                                    @if($isDriverQueueRecord && $payment->payment_status === 'pending_confirmation')
                                        <div class="payments-action-row" style="width:100%;">
                                            <button
                                                type="button"
                                                class="payments-btn payment-table-action open-request-btn"
                                                style="width:100%;"
                                                data-payment-id="{{ $payment->id }}"
                                                data-passenger="{{ $payment->user?->name ?: '-' }}"
                                                data-trip="{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}"
                                                data-amount="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                                data-method="{{ $methodLabel }}"
                                                data-remarks="{{ $payment->remarks ?: '-' }}"
                                                data-marked="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                                data-approve-action="{{ route('payments.confirm-paid', $payment) }}"
                                                data-reject-action="{{ route('payments.reject-paid', $payment) }}"
                                            ><i class="fa-solid fa-clipboard-check"></i> Review</button>
                                        </div>
                                    @elseif($isDriverQueueRecord && $payment->payment_status === 'unpaid')
                                        @php($reminderUi = $reminderButtonUi($canSendReminder, $secondsLeft))
                                        <div style="display:flex; flex-direction:column; gap:4px; align-items:stretch; justify-content:center; width:100%; max-width:140px; margin-left:auto;">
                                            <form method="POST" action="{{ route('payments.send-reminder', $payment) }}" class="payments-action-row" style="margin:0; width:100%;">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="payments-btn payment-table-action {{ $canSendReminder ? '' : 'is-disabled' }} reminder-btn"
                                                    {{ $canSendReminder ? '' : 'disabled' }}
                                                    data-payment-id="{{ $payment->id }}"
                                                    data-seconds-left="{{ $secondsLeft }}"
                                                    title="{{ $reminderUi['title'] }}"
                                                    style="width:100%; height:36px !important; min-height:36px !important; max-height:36px !important; flex:none !important; padding:0 10px;"
                                                >
                                                    @if($canSendReminder)
                                                        <i class="fa-regular fa-bell"></i> Notify
                                                    @else
                                                        <i class="fa-regular fa-clock"></i> {{ $reminderUi['label'] }}
                                                    @endif
                                                </button>
                                            </form>
                                            <button
                                                 type="button"
                                                 class="payments-btn payment-table-action ch-btn-green open-mark-paid-modal"
                                                 style="width:100%; height:36px !important; min-height:36px !important; max-height:36px !important; flex:none !important; padding:0 10px;"
                                                 data-action="{{ route('payments.confirm-paid', $payment) }}"
                                                 data-passenger="{{ $payment->user?->name ?: 'Passenger' }}"
                                                 data-trip="{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}"
                                                 data-amount="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                             >
                                                 <i class="fa-solid fa-check"></i> Mark Paid
                                             </button>
                                        </div>
                                    @elseif(! $isDriverQueueRecord && $payment->payment_status === 'unpaid')
                                        <form method="POST" action="{{ route('payments.mark-paid', $payment) }}" class="payments-action-row" style="width:100%;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="payment_method" value="duitnow_qr">
                                            <input class="payments-input" type="text" name="remarks" placeholder="Remarks">
                                            <button
                                                type="button"
                                                class="payments-btn payment-table-action open-payment-paynow-btn ch-btn-green"
                                                data-action="{{ route('payments.mark-paid', $payment) }}"
                                                data-passenger="{{ $payment->user?->name ?: auth()->user()->name }}"
                                                data-initials="{{ $paymentInitials($payment->user?->name ?: auth()->user()->name) }}"
                                                data-trip="{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}"
                                                data-route="{{ $routeLabel }}"
                                                data-amount="{{ number_format((float) $payment->amount_due, 2) }}"
                                                data-base-amount="{{ number_format((float) $fareBreakdown['base'], 2) }}"
                                                data-extra-fee="{{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                                data-has-extra="{{ $fareBreakdown['has_extra'] ? '1' : '0' }}"
                                                data-driver-name="{{ $payment->trip?->driver?->name ?: '-' }}"
                                                data-driver-email="{{ $payment->trip?->driver?->email ?: '-' }}"
                                                data-driver-photo="{{ $driverPhotoUrl }}"
                                                data-driver-bank="{{ $driverBank }}"
                                                data-driver-account-name="{{ $driverAccountName }}"
                                                data-driver-account-number="{{ $driverAccountNumber }}"
                                                data-driver-duitnow-qr="{{ $driverDuitnowQr }}"
                                                style="width:100%;"
                                                data-driver-tng-qr="{{ $driverTngQr }}"
                                            ><i class="fa-solid fa-credit-card"></i> Pay</button>
                                        </form>
                                    @elseif($payment->payment_status === 'pending_confirmation')
                                        <div class="payments-action-row" style="width:100%;">
                                            <span class="payments-btn payment-table-action is-muted" style="width:100%;"><i class="fa-regular fa-clock"></i> Pending</span>
                                        </div>
                                    @else
                                        <div class="payments-action-row" style="width:100%;">
                                            <button
                                                type="button"
                                                class="payments-btn payment-table-action open-payment-receipt-btn"
                                                style="width:100%;"
                                                data-receipt-no="PAY-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}"
                                                data-trip-ref="{{ $tripRef }}"
                                                data-trip-mode="{{ ($payment->trip?->is_return_trip ?? false) ? 'Return' : 'Outbound' }}"
                                                data-route="{{ $routeLabel }}"
                                                data-passenger="{{ $payment->user?->name ?: '-' }}"
                                                data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                                data-amount="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                                data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                                data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                                data-has-extra="{{ $fareBreakdown['has_extra'] ? '1' : '0' }}"
                                                data-method="{{ $methodLabel }}"
                                                data-marked-at="{{ $payment->marked_paid_at?->format('d M Y, H:i') ?: '-' }}"
                                                data-confirmed-at="{{ $payment->confirmed_at?->format('d M Y, H:i') ?: '-' }}"
                                            ><i class="fa-solid fa-receipt"></i> Receipt</button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No payment records found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @endif

                {{--
                    Pagination shells. The ledger deliberately renders every record so the
                    tab strip and the filter panel can narrow the list without a round-trip,
                    so paging has to run on that same client-side result — a server paginator
                    here would report "1 to 12" while the page still showed every row and its
                    page links would just re-render the identical list. payments-index.js
                    fills these shells (same markup/classes as before) and slices the rows
                    that survived the tab + filter passes into pages of {{ $ledgerPageSize }}.
                --}}
                @if($displayPaymentsDesktop->isNotEmpty())
                <div class="payments-pagination-wrap"
                     data-payments-pagination
                     data-page-size="{{ $ledgerPageSize }}"
                     style="display:none;"></div>
                @endif
                
                <div class="payments-filter-empty" data-filter-empty>
                    <div class="ch-empty-state-icon-box"><i class="fa-solid fa-compass"></i></div>
                    <h3 class="ch-empty-state-title">No payments found</h3>
                    <p class="ch-empty-state-body">No payment records match your filters right now. Try changing your search or check back later.</p>
                    <button type="button" class="ch-empty-state-btn" onclick="if(window.clearPaymentFilters) window.clearPaymentFilters();">Clear Filters</button>
                </div>
                </div>{{-- /payments-real-container --}}
                </div>{{-- /relative-wrapper --}}
            </section>

            <aside class="payments-side-panel">
                <section class="payments-total-card">
                    @if($hasSplitPaymentDirections)
                        <input class="payments-summary-mode-input" type="radio" name="desktop_summary_mode" id="desktopSummaryDriver" checked>
                        <input class="payments-summary-mode-input" type="radio" name="desktop_summary_mode" id="desktopSummaryPassenger">
                        <div class="payments-summary-top payments-summary-top--switch-only">
                            <div class="payments-summary-switch" aria-label="Summary view">
                                <label for="desktopSummaryDriver">As driver</label>
                                <label for="desktopSummaryPassenger">As passenger</label>
                            </div>
                        </div>
                        <span class="payments-total-label payments-total-label--range">{{ strtoupper($monthLabel) }}</span>
                        <div class="payments-summary-mode-panel payments-summary-driver-panel">
                            <strong>RM {{ number_format($driverUnpaidAmount + $driverPendingAmount, 2) }}</strong>
                            <small>To collect · Paid RM {{ number_format($driverPaidAmount, 2) }}</small>
                            <div class="payments-total-metrics">
                                <div class="payments-total-metric">
                                    <span>Pending</span>
                                    <b>RM {{ number_format($driverPendingAmount, 2) }}</b>
                                </div>
                                <div class="payments-total-metric">
                                    <span>Paid</span>
                                    <b>RM {{ number_format($driverPaidAmount, 2) }}</b>
                                </div>
                            </div>
                        <div class="payments-summary-panel">
                            <div class="payments-summary-panel-title">Passenger payment status</div>
                            <div class="payments-summary-detail-list">
                                @forelse($driverCollectionRows as $debtRow)
                                    <div class="payments-summary-detail-row">
                                        <div class="payments-summary-card-head">
                                            <span class="payments-summary-card-name">{{ $debtRow['name'] }}</span>
                                            <span class="payments-summary-card-badge">{{ $debtRow['records'] }} records</span>
                                        </div>
                                        <div class="payments-summary-receipt-body">
                                            <div class="payments-summary-receipt-line is-unpaid">
                                                <span><i class="fa-solid fa-circle-exclamation"></i> Unpaid</span>
                                                <span class="line-val">RM {{ number_format((float) $debtRow['unpaid'], 2) }}</span>
                                            </div>
                                            <div class="payments-summary-receipt-line is-pending">
                                                <span><i class="fa-regular fa-clock"></i> Pending</span>
                                                <span class="line-val">RM {{ number_format((float) $debtRow['pending'], 2) }}</span>
                                            </div>
                                            <div class="payments-summary-receipt-line is-paid">
                                                <span><i class="fa-solid fa-circle-check"></i> Paid</span>
                                                <span class="line-val">RM {{ number_format((float) $debtRow['paid'], 2) }}</span>
                                            </div>
                                            <div class="payments-summary-receipt-total">
                                                <span>Total</span>
                                                <span class="total-val">RM {{ number_format((float) $debtRow['total'], 2) }}</span>
                                            </div>
                                        </div>
                                        @if(($debtRow['unpaid'] + $debtRow['pending']) > 0)
                                            <button type="button" 
                                                    class="btn-select-person-unpaid" 
                                                    data-person-name="{{ $debtRow['name'] }}"
                                                    data-direction="collect"
                                                    title="Select all unpaid payments for {{ $debtRow['name'] }}">
                                                <i class="fa-solid fa-square-check"></i> Select Unpaid (RM {{ number_format((float) ($debtRow['unpaid'] + $debtRow['pending']), 2) }})
                                            </button>
                                        @endif
                                    </div>
                                @empty
                                    <div class="payments-summary-detail-empty">No passenger payment still pending.</div>
                                @endforelse
                            </div>
                        </div>
                        </div>
                        <div class="payments-summary-mode-panel payments-summary-passenger-panel">
                            <strong>RM {{ number_format($myUnpaidAmount + $myPendingAmount, 2) }}</strong>
                            <small>To pay · Paid RM {{ number_format($myPaidAmount, 2) }}</small>
                            <div class="payments-total-metrics">
                                <div class="payments-total-metric">
                                    <span>Pending</span>
                                    <b>RM {{ number_format($myPendingAmount, 2) }}</b>
                                </div>
                                <div class="payments-total-metric">
                                    <span>Paid</span>
                                    <b>RM {{ number_format($myPaidAmount, 2) }}</b>
                                </div>
                            </div>
                        </div>
                    @else
                        <span class="payments-total-label">{{ strtoupper($monthLabel) }}</span>
                        <strong>RM {{ number_format($summaryMainAmount, 2) }}</strong>
                        <small>{{ $summaryMainLabel }} · {{ $isAdmin ? 'admin payment view' : 'passenger payment view' }}</small>
                        <div class="payments-total-metrics">
                            @if($summaryMainLabel !== 'To pay')
                                <div class="payments-total-metric">
                                    <span>Unpaid</span>
                                    <b>RM {{ number_format($summaryPrimaryAmount, 2) }}</b>
                                </div>
                            @endif
                            <div class="payments-total-metric">
                                <span>Pending</span>
                                <b>RM {{ number_format($summarySecondaryAmount, 2) }}</b>
                            </div>
                            <div class="payments-total-metric">
                                <span>Paid</span>
                                <b>RM {{ number_format($summaryPaidAmount, 2) }}</b>
                            </div>
                        </div>
                        <div class="payments-summary-panel">
                            <div class="payments-summary-panel-title">{{ $summaryDetailTitle }}</div>
                            <div class="payments-summary-detail-list">
                                @forelse($summaryDetailRows as $payRow)
                                    <div class="payments-summary-detail-row">
                                        <div class="payments-summary-card-head">
                                            <span class="payments-summary-card-name">{{ $payRow['name'] }}</span>
                                            <span class="payments-summary-card-badge">{{ $payRow['records'] }} records</span>
                                        </div>
                                        <div class="payments-summary-receipt-body">
                                            <div class="payments-summary-receipt-line is-unpaid">
                                                <span><i class="fa-solid fa-circle-exclamation"></i> Unpaid</span>
                                                <span class="line-val">RM {{ number_format((float) $payRow['unpaid'], 2) }}</span>
                                            </div>
                                            <div class="payments-summary-receipt-line is-pending">
                                                <span><i class="fa-regular fa-clock"></i> Pending</span>
                                                <span class="line-val">RM {{ number_format((float) $payRow['pending'], 2) }}</span>
                                            </div>
                                            <div class="payments-summary-receipt-line is-paid">
                                                <span><i class="fa-solid fa-circle-check"></i> Confirmed</span>
                                                <span class="line-val">RM {{ number_format((float) $payRow['paid'], 2) }}</span>
                                            </div>
                                            <div class="payments-summary-receipt-total">
                                                <span>Total</span>
                                                <span class="total-val">RM {{ number_format((float) $payRow['total'], 2) }}</span>
                                            </div>
                                        </div>
                                        @if(($payRow['unpaid'] + $payRow['pending']) > 0)
                                            <button type="button" 
                                                    class="btn-select-person-unpaid" 
                                                    data-person-name="{{ $payRow['name'] }}"
                                                    data-direction="unpaid"
                                                    title="Select all unpaid payments for {{ $payRow['name'] }}">
                                                <i class="fa-solid fa-square-check"></i> Select Unpaid (RM {{ number_format((float) ($payRow['unpaid'] + $payRow['pending']), 2) }})
                                            </button>
                                        @endif
                                    </div>
                                @empty
                                    <div class="payments-summary-detail-empty">No active payment due right now.</div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </section>
            </aside>
            </div>

        @if($hasSplitPaymentDirections)
            <section class="payments-card" id="queue-summary">
                <h2 class="payments-section-title">Queue Summary</h2>
                <div class="payments-summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Queue Unpaid</div>
                        <div id="paymentsQueueUnpaidAmount" class="summary-value">RM {{ number_format((float) ($summary['driver']['unpaid']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsQueueUnpaidCount" class="summary-count">{{ $summary['driver']['unpaid']['count'] ?? 0 }} records</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Queue Pending</div>
                        <div id="paymentsQueuePendingAmount" class="summary-value">RM {{ number_format((float) ($summary['driver']['pending_confirmation']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsQueuePendingCount" class="summary-count">{{ $summary['driver']['pending_confirmation']['count'] ?? 0 }} records</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Queue Paid</div>
                        <div id="paymentsQueuePaidAmount" class="summary-value">RM {{ number_format((float) ($summary['driver']['paid']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsQueuePaidCount" class="summary-count">{{ $summary['driver']['paid']['count'] ?? 0 }} records</div>
                    </div>
                </div>

                <div class="debt-summary-card">
                    <div class="debt-summary-top">
                        <h3 class="debt-summary-title">Passenger Debt Summary</h3>
                        <span id="paymentsDebtTotal" class="debt-summary-total">
                            RM {{ number_format((float) ($passengerDebtSummary['total_amount'] ?? 0), 2) }}
                        </span>
                    </div>
                    <div id="paymentsDebtMeta" class="summary-count">
                        {{ (int) ($passengerDebtSummary['passenger_count'] ?? 0) }} passengers,
                        {{ (int) ($passengerDebtSummary['total_records'] ?? 0) }} active records (unpaid + pending).
                    </div>
                    <div class="debt-list">
                        @forelse(($passengerDebtSummary['rows'] ?? []) as $debtRow)
                            <div class="debt-item">
                                <div>
                                    <div class="debt-name">{{ $debtRow['passenger_name'] }}</div>
                                    <div class="debt-meta">
                                        {{ $debtRow['records'] }} records •
                                        Unpaid RM {{ number_format((float) $debtRow['unpaid_amount'], 2) }} •
                                        Pending RM {{ number_format((float) $debtRow['pending_amount'], 2) }}
                                    </div>
                                </div>
                                <div class="debt-amount">RM {{ number_format((float) $debtRow['total_amount'], 2) }}</div>
                            </div>
                        @empty
                            <div class="debt-item">
                                <div class="debt-meta">No active debt records for passengers.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="payments-card" id="driver-review-list">
                <h2 class="payments-section-title">{{ $reviewTitle }}</h2>
                <p class="payments-section-subtitle">{{ $reviewSubtitle }}</p>
                <div class="payments-filter-panel js-payments-filter" data-filter-scope="#driver-review-list">
                    <div class="payments-filter-head">
                        <p class="payments-filter-hint">Filters apply automatically.</p>
                        <button type="button" class="payments-filter-toggle" data-filter-toggle aria-expanded="false">
                            <i class="fa-solid fa-filter"></i><span>Filter</span>
                        </button>
                    </div>
                    <div class="payments-filter-body">
                        <div class="payments-filter-grid">
                            <div class="payments-filter-field">
                                <label for="driverReviewFromDate">From Date</label>
                                <input id="driverReviewFromDate" class="payments-filter-input" type="date" data-filter-from>
                            </div>
                            <div class="payments-filter-field">
                                <label for="driverReviewToDate">To Date</label>
                                <input id="driverReviewToDate" class="payments-filter-input" type="date" data-filter-to>
                            </div>
                            <div class="payments-filter-field">
                                <label for="driverReviewPassengerSearch">Search Passenger</label>
                                <input id="driverReviewPassengerSearch" class="payments-filter-input" type="search" placeholder="Search passenger name or email..." data-filter-person>
                            </div>
                        </div>
                        <div class="payments-filter-actions">
                            <button type="button" class="payments-filter-reset" data-filter-reset>Reset</button>
                        </div>
                    </div>
                </div>
                <div class="payments-mobile-list">
                    @forelse(($driverPayments ?? collect()) as $payment)
                        @php
                            $isReturnTrip = (bool) ($payment->trip?->is_return_trip ?? false);
                            $pickupName = $payment->trip?->pickup_name ?? '-';
                            $pickupLat = $payment->trip?->pickup_latitude ?? '';
                            $pickupLng = $payment->trip?->pickup_longitude ?? '';
                            $destinationName = $payment->trip?->destination_name ?? '-';
                            $destinationLat = $payment->trip?->destination_latitude ?? '';
                            $destinationLng = $payment->trip?->destination_longitude ?? '';
                            $pairedTripId = $isReturnTrip
                                ? ($payment->trip?->parentTrip?->id)
                                : ($payment->trip?->returnTrip?->id);
                            $tripRef = $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT);
                            $routeLabel = $payment->trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                            $statusClass = $payment->payment_status === 'paid'
                                ? 'status-paid'
                                : ($payment->payment_status === 'pending_confirmation' ? 'status-pending' : 'status-unpaid');
                            $statusText = $payment->payment_status === 'pending_confirmation'
                                ? 'Pending Confirmation'
                                : ($payment->payment_status === 'paid' ? 'Paid' : ($payment->payment_status === 'unpaid' ? 'Unpaid' : ucfirst($payment->payment_status)));
                            $methodLabel = match ($payment->payment_method) {
                                'duitnow_qr' => 'DuitNow QR',
                                'bank_account' => 'Bank Account',
                                'digital_wallet' => 'Digital Wallet',
                                'others' => 'Others',
                                default => '-',
                            };
                            $reminderMeta = $reminderState[$payment->id] ?? ['can_send' => true, 'seconds_left' => 0];
                            $canSendReminder = (bool) $reminderMeta['can_send'];
                            $secondsLeft = (int) ($reminderMeta['seconds_left'] ?? 0);
                            $participantsPayload = $payment->trip?->participants?->map(function ($participant) {
                                $participantUser = $participant->user;
                                return [
                                    'name' => $participantUser?->name ?: '-',
                                    'email' => $participantUser?->email ?: '',
                                    'photo_url' => $participantUser?->profile_photo_url,
                                    'is_driver' => (bool) $participant->is_driver,
                                ];
                            })->values()->all() ?? [];
                            $fareBreakdown = $paymentFareBreakdown($payment);
                        @endphp
                        <article
                            class="payment-mobile-item open-trip-card js-payment-filter-item"
                            data-filter-date="{{ $payment->trip?->trip_datetime?->format('Y-m-d') ?: '' }}"
                            data-filter-person="{{ $payment->user?->name ?: '' }}"
                        >
                            <div class="payment-mobile-top">
                                <div>
                                    <div class="payment-mobile-trip"><i class="fa-solid fa-hashtag" style="color:#c2410c;font-size:12px;margin-right:4px;"></i>{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}</div>
                                    <div class="payment-mobile-sub">{{ $routeLabel }}</div>
                                    <button
                                        type="button"
                                        class="payments-link open-trip-modal-btn"
                                        data-trip-id="{{ $payment->trip_id }}"
                                        data-trip-ref="{{ $tripRef }}"
                                        data-route="{{ $routeLabel }}"
                                        data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                        data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $pickupLat }}"
                                        data-pickup-lng="{{ $pickupLng }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $destinationLat }}"
                                        data-destination-lng="{{ $destinationLng }}"
                                        data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                        data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                                        data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                                        data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                                        data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                        data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                        data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                                        data-payment-status="{{ $statusText }}"
                                        data-payment-method="{{ $methodLabel }}"
                                        data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-participants='@json($participantsPayload)'
                                        data-passenger-count="{{ count($participantsPayload) }}"
                                    ><i class="fa-regular fa-eye"></i><span>See Details</span></button>
                                </div>
                                <span class="status-chip {{ $statusClass }}">{{ $statusText }}</span>
                            </div>
                            <div class="payment-mobile-grid">
                                <div class="payment-mobile-line">
                                    <span>Passenger</span>
                                    <strong>{{ $payment->user?->name ?: '-' }}</strong>
                                </div>
                                <div class="payment-mobile-line">
                                    <span>Amount</span>
                                    <strong>
                                        RM {{ number_format((float) $payment->amount_due, 2) }}
                                        @if($fareBreakdown['has_extra'])
                                            <small style="display:block;color:#64748b;">Base RM {{ number_format((float) $fareBreakdown['base'], 2) }} + Extra RM {{ number_format((float) $fareBreakdown['extra'], 2) }}</small>
                                        @endif
                                    </strong>
                                </div>
                                <div class="payment-mobile-line">
                                    <span>Marked At</span>
                                    <strong>{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}</strong>
                                </div>
                            </div>
                            <div class="queue-actions">
                                <div class="queue-actions-main">
                                @if($payment->payment_status === 'pending_confirmation')
                                    <button
                                        type="button"
                                        class="payments-btn payments-btn-highlight open-request-btn"
                                        data-payment-id="{{ $payment->id }}"
                                        data-passenger="{{ $payment->user?->name ?: '-' }}"
                                        data-trip="{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}"
                                        data-amount="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-method="{{ $methodLabel }}"
                                        data-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-approve-action="{{ route('payments.confirm-paid', $payment) }}"
                                        data-reject-action="{{ route('payments.reject-paid', $payment) }}"
                                    >
                                        View Request
                                    </button>
                                @endif

                                @if($payment->payment_status === 'unpaid')
                                    @php($reminderUi = $reminderButtonUi($canSendReminder, $secondsLeft))
                                    <form method="POST" action="{{ route('payments.send-reminder', $payment) }}" class="payments-action-row">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="payments-btn {{ $canSendReminder ? '' : 'is-disabled' }} reminder-btn {{ $canSendReminder ? 'is-icon-only' : '' }}"
                                            {{ $canSendReminder ? '' : 'disabled' }}
                                            data-payment-id="{{ $payment->id }}"
                                            data-seconds-left="{{ $secondsLeft }}"
                                            data-ready-style="icon"
                                            title="{{ $reminderUi['title'] }}"
                                        ><i class="{{ $reminderUi['icon'] }} btn-icon"></i>{{ $reminderUi['label'] }}</button>
                                    </form>
                                @endif

                                @if($payment->payment_status === 'pending_confirmation')
                                    <form method="POST" action="{{ route('payments.confirm-paid', $payment) }}" class="payments-action-row">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="payments-btn payments-btn-success">Approve</button>
                                    </form>
                                    <button
                                        type="button"
                                        class="payments-btn payments-btn-danger open-reject-btn"
                                        data-action="{{ route('payments.reject-paid', $payment) }}"
                                        data-passenger="{{ $payment->user?->name ?: '-' }}"
                                        data-trip="{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}"
                                    >
                                        Reject
                                    </button>
                                @elseif($payment->payment_status === 'unpaid')
                                    <form method="POST" action="{{ route('payments.confirm-paid', $payment) }}" class="payments-action-row">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="payments-btn payments-btn-primary">Tandai Paid</button>
                                    </form>
                                @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="payment-mobile-item" style="text-align:center; padding:32px 16px; color:#64748b; font-size:13px;">No records in the queue.</div>
                    @endforelse
                </div>
                <div class="payments-table-wrap">
                    <table class="payments-table">
                        <thead>
                        <tr>
                            <th>Trip</th>
                            <th>Passenger</th>
                            <th class="right">Amount</th>
                            <th>Marked At</th>
                            <th>Status</th>
                            <th class="right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse(($driverPayments ?? collect()) as $payment)
                        @php
                            $isReturnTrip = (bool) ($payment->trip?->is_return_trip ?? false);
                            $pickupName = $payment->trip?->pickup_name ?? '-';
                            $pickupLat = $payment->trip?->pickup_latitude ?? '';
                            $pickupLng = $payment->trip?->pickup_longitude ?? '';
                            $destinationName = $payment->trip?->destination_name ?? '-';
                            $destinationLat = $payment->trip?->destination_latitude ?? '';
                            $destinationLng = $payment->trip?->destination_longitude ?? '';
                            $pairedTripId = $isReturnTrip
                                ? ($payment->trip?->parentTrip?->id)
                                : ($payment->trip?->returnTrip?->id);
                            $tripRef = $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT);
                            $routeLabel = $payment->trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                            $statusClass = $payment->payment_status === 'paid'
                                ? 'status-paid'
                                : ($payment->payment_status === 'pending_confirmation' ? 'status-pending' : 'status-unpaid');
                            $statusText = $payment->payment_status === 'pending_confirmation'
                                ? 'Pending Confirmation'
                                : ($payment->payment_status === 'paid' ? 'Paid' : ($payment->payment_status === 'unpaid' ? 'Unpaid' : ucfirst($payment->payment_status)));
                            $methodLabel = match ($payment->payment_method) {
                                'duitnow_qr' => 'DuitNow QR',
                                'bank_account' => 'Bank Account',
                                'digital_wallet' => 'Digital Wallet',
                                'others' => 'Others',
                                default => '-',
                            };
                            $participantsPayload = $payment->trip?->participants?->map(function ($participant) {
                                $participantUser = $participant->user;
                                return [
                                    'name' => $participantUser?->name ?: '-',
                                    'email' => $participantUser?->email ?: '',
                                    'photo_url' => $participantUser?->profile_photo_url,
                                    'is_driver' => (bool) $participant->is_driver,
                                ];
                            })->values()->all() ?? [];
                            $fareBreakdown = $paymentFareBreakdown($payment);
                        @endphp
                            <tr
                                class="open-trip-card js-payment-filter-item"
                                data-filter-date="{{ $payment->trip?->trip_datetime?->format('Y-m-d') ?: '' }}"
                                data-filter-person="{{ $payment->user?->name ?: '' }}"
                            >
                                <td>
                                    <div>{{ $tripRef }}</div>
                                    <div style="font-size:12px; color:#64748b;">{{ $routeLabel }}</div>
                                    <button
                                        type="button"
                                        class="payments-link open-trip-modal-btn"
                                        data-trip-id="{{ $payment->trip_id }}"
                                        data-trip-ref="{{ $tripRef }}"
                                        data-route="{{ $routeLabel }}"
                                        data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                        data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $pickupLat }}"
                                        data-pickup-lng="{{ $pickupLng }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $destinationLat }}"
                                        data-destination-lng="{{ $destinationLng }}"
                                        data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                        data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                                        data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                                        data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                                        data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                        data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                        data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                                        data-payment-status="{{ $statusText }}"
                                        data-payment-method="{{ $methodLabel }}"
                                        data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-participants='@json($participantsPayload)'
                                        data-passenger-count="{{ count($participantsPayload) }}"
                                    ><i class="fa-regular fa-eye"></i><span>See Details</span></button>
                                </td>
                                <td>{{ $payment->user?->name ?: '-' }}</td>
                                <td class="right">
                                    RM {{ number_format((float) $payment->amount_due, 2) }}
                                    @if($fareBreakdown['has_extra'])
                                        <div style="font-size:11px;color:#64748b;font-weight:700;">Base RM {{ number_format((float) $fareBreakdown['base'], 2) }} + Extra RM {{ number_format((float) $fareBreakdown['extra'], 2) }}</div>
                                    @endif
                                </td>
                                <td>{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}</td>
                                <td><span class="status-chip {{ $statusClass }}">{{ $statusText }}</span></td>
                                <td class="right">
                                    <div class="queue-actions">
                                        <div class="queue-actions-main">
                                        @if($payment->payment_status === 'pending_confirmation')
                                            <button
                                                type="button"
                                                class="payments-btn payments-btn-highlight open-request-btn"
                                                data-payment-id="{{ $payment->id }}"
                                                data-passenger="{{ $payment->user?->name ?: '-' }}"
                                                data-trip="{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}"
                                                data-amount="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                                data-method="{{ $methodLabel }}"
                                                data-remarks="{{ $payment->remarks ?: '-' }}"
                                                data-marked="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                                data-approve-action="{{ route('payments.confirm-paid', $payment) }}"
                                                data-reject-action="{{ route('payments.reject-paid', $payment) }}"
                                            >
                                                View Request
                                            </button>
                                        @endif
                                        @if($payment->payment_status === 'unpaid')
                                            @php($reminderUi = $reminderButtonUi($canSendReminder, $secondsLeft))
                                            <form method="POST" action="{{ route('payments.send-reminder', $payment) }}" class="payments-action-row">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="payments-btn {{ $canSendReminder ? '' : 'is-disabled' }} reminder-btn"
                                                    {{ $canSendReminder ? '' : 'disabled' }}
                                                    data-payment-id="{{ $payment->id }}"
                                                    data-seconds-left="{{ $secondsLeft }}"
                                                    title="{{ $reminderUi['title'] }}"
                                                >
                                                    {!! $canSendReminder
                                                        ? '<i class="fa-regular fa-bell btn-icon"></i>Notify'
                                                        : '<i class="fa-regular fa-clock btn-icon"></i>' . $reminderUi['label'] !!}
                                                </button>
                                            </form>
                                        @endif
                                        @if($payment->payment_status === 'pending_confirmation')
                                            <form method="POST" action="{{ route('payments.confirm-paid', $payment) }}" class="payments-action-row">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="payments-btn payments-btn-success">Approve</button>
                                            </form>
                                            <button
                                                type="button"
                                                class="payments-btn payments-btn-danger open-reject-btn"
                                                data-action="{{ route('payments.reject-paid', $payment) }}"
                                                data-passenger="{{ $payment->user?->name ?: '-' }}"
                                                data-trip="{{ $payment->trip?->trip_ref ?: 'TRP-' . str_pad($payment->trip_id, 5, '0', STR_PAD_LEFT) }}"
                                            >
                                                Reject
                                            </button>
                                        @elseif($payment->payment_status === 'unpaid')
                                            <form method="POST" action="{{ route('payments.confirm-paid', $payment) }}" class="payments-action-row">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="payments-btn payments-btn-primary">Tandai Paid</button>
                                            </form>
                                        @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No payment records found in queue.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($driverPayments)
                    <div style="margin-top:12px;">
                        {{ $driverPayments->appends(request()->query())->links() }}
                    </div>
                @endif
                <div class="payments-filter-empty" data-filter-empty>
                    <div class="ch-empty-state-icon-box"><i class="fa-solid fa-compass"></i></div>
                    <h3 class="ch-empty-state-title">No payments found</h3>
                    <p class="ch-empty-state-body">No payment records match your filters right now. Try changing your search or check back later.</p>
                    <button type="button" class="ch-empty-state-btn" onclick="if(window.clearPaymentFilters) window.clearPaymentFilters();">Clear Filters</button>
                </div>
            </section>
        @endif
    </div>

    <div class="request-modal" id="requestModal" aria-hidden="true">
        <div class="request-modal-card request-modal-compact">
            <div class="request-modal-head">
                <div>
                    <h3 class="request-modal-title">Payment Request Details</h3>
                    <p class="request-modal-sub">Review payment confirmation submitted by passenger.</p>
                </div>
                <button type="button" class="modal-close-square" id="requestModalCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="request-modal-grid-two">
                <div class="request-modal-line">
                    <span class="request-modal-label"><i class="fa-solid fa-user" style="margin-right:4px;"></i> Passenger</span>
                    <span class="request-modal-value" id="requestModalPassenger">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label"><i class="fa-solid fa-hashtag" style="margin-right:4px;"></i> Trip Ref</span>
                    <span class="request-modal-value" id="requestModalTrip">-</span>
                </div>
                <div class="request-modal-line request-modal-amount-box">
                    <span class="request-modal-label"><i class="fa-solid fa-money-bill-wave" style="margin-right:4px;"></i> Amount Claimed</span>
                    <span class="request-modal-value request-modal-amount-val" id="requestModalAmount">RM 0.00</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label"><i class="fa-regular fa-credit-card" style="margin-right:4px;"></i> Payment Method</span>
                    <span class="request-modal-value" id="requestModalMethod">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label"><i class="fa-regular fa-clock" style="margin-right:4px;"></i> Marked At</span>
                    <span class="request-modal-value" id="requestModalMarked">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label"><i class="fa-regular fa-note-sticky" style="margin-right:4px;"></i> Remarks</span>
                    <span class="request-modal-value" id="requestModalRemarks">-</span>
                </div>
            </div>
            <div class="request-modal-actions">
                <div class="request-modal-primary-actions">
                    <form id="requestModalApproveForm" method="POST" style="margin:0;">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-solid-approve"><i class="fa-solid fa-check"></i> Approve</button>
                    </form>
                    <button type="button" class="btn-solid-reject" id="requestModalReject"><i class="fa-solid fa-xmark"></i> Reject</button>
                </div>
            </div>
        </div>
    </div>

    <div class="trip-payment-review-modal" id="paymentPayNowModal" aria-hidden="true">
        <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="paymentPayNowTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="paymentPayNowTitle">Pay now</h3>
                    <p class="trip-payment-review-sub" id="paymentPayNowSub">Mark your trip payment as paid.</p>
                </div>
                <button type="button" class="trip-payment-review-close" id="paymentPayNowClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list" id="paymentPayNowList"></div>
        </div>
    </div>

    <div class="trip-payment-review-modal" id="paymentReceiptModal" aria-hidden="true">
        <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="paymentReceiptTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="paymentReceiptTitle">Payment receipts</h3>
                </div>
                <button type="button" class="trip-payment-review-close" id="paymentReceiptClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list">
                <article class="trip-receipt-card">
                    <div class="receipt-brand-wrap">
                        <div class="receipt-brand-logo-inline">
                            <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="CarpoolHub" class="receipt-brand-icon">
                            <h4 class="receipt-brand-text">Carpool<span class="hub-yellow">Hub</span></h4>
                        </div>
                        <span class="trip-receipt-status paid">Paid</span>
                    </div>
                    <div class="trip-receipt-head">
                        <div>
                            <span class="trip-receipt-id" id="paymentReceiptNo">Official Payment Receipt · PAY-000000</span>
                        </div>
                    </div>
                    <div class="trip-receipt-total">
                        <span>AMOUNT PAID</span>
                        <strong id="paymentReceiptAmount">RM 0.00</strong>
                    </div>
                    <div class="trip-receipt-lines">
                        <div class="trip-receipt-line"><span>Passenger</span><strong id="paymentReceiptPassenger">-</strong></div>
                        <div class="trip-receipt-line"><span>Driver</span><strong id="paymentReceiptDriver">-</strong></div>
                        <div class="trip-receipt-line"><span>Route</span><strong id="paymentReceiptRoute">-</strong></div>
                        <div class="trip-receipt-line"><span>Method</span><strong id="paymentReceiptMethod">-</strong></div>
                        <div class="trip-receipt-line"><span>Marked paid</span><strong id="paymentReceiptMarked">-</strong></div>
                        <div class="trip-receipt-line"><span>Confirmed</span><strong id="paymentReceiptConfirmed">-</strong></div>
                        <div class="trip-receipt-line" id="paymentReceiptBreakdownRow" style="display:none;"><span>Breakdown</span><strong id="paymentReceiptBreakdown">-</strong></div>
                    </div>
                    <div class="receipt-disclaimer-note">
                        <i class="fa-solid fa-circle-info"></i> This is a computer-generated document. No signature is required.
                    </div>
                    <div class="trip-receipt-footer-actions">
                        <button type="button" class="btn-receipt-print" onclick="window.print()">
                            <i class="fa-solid fa-print"></i> Print / Save PDF
                        </button>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <div class="request-modal" id="tripDetailsModal" aria-hidden="true">
        <div class="request-modal-card trip-details-card">
            <div class="request-modal-head">
                <div class="trip-modal-head-text">
                    <h3 class="request-modal-title">Trip Details</h3>
                    <span class="trip-status-badge" id="tripDetailsPaymentStatus">-</span>
                </div>
                <button type="button" class="modal-close-square" id="tripDetailsCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="trip-details-scroll">
                <div class="trip-meta-line">
                    <span class="trip-meta-item"><i class="fa-solid fa-hashtag"></i><span id="tripDetailsId">-</span></span>
                    <span class="trip-meta-dot">&middot;</span>
                    <span class="trip-meta-item"><i class="fa-regular fa-calendar"></i><span id="tripDetailsDatetime">-</span></span>
                    <span class="trip-meta-dot">&middot;</span>
                    <span class="trip-meta-item trip-meta-route"><i class="fa-solid fa-road"></i><span class="trip-meta-route-text" id="tripDetailsRoute">-</span></span>
                </div>

                <div class="trip-route-card">
                    <div class="trip-mini-map" id="tripDetailsMiniMap"></div>
                    <div class="trip-route-timeline">
                        <div class="trip-route-point">
                            <span class="trip-route-dot pickup"></span>
                            <span class="trip-route-text">
                                <span class="trip-route-label">Pickup Point</span>
                                <span class="trip-route-value" id="tripDetailsPickupPoint">-</span>
                            </span>
                        </div>
                        <div class="trip-route-point">
                            <span class="trip-route-dot destination"></span>
                            <span class="trip-route-text">
                                <span class="trip-route-label">Destination Point</span>
                                <span class="trip-route-value" id="tripDetailsDestinationPoint">-</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="trip-driver-card">
                    <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Driver</span>
                    <span class="trip-driver-content">
                        <span class="trip-driver-avatar" id="tripDetailsDriverAvatar">D</span>
                        <span class="trip-driver-meta">
                            <span class="trip-driver-name" id="tripDetailsDriver">-</span>
                            <span class="trip-driver-email" id="tripDetailsDriverEmail">-</span>
                        </span>
                    </span>
                </div>

                <div class="trip-passenger-card">
                    <div class="trip-passenger-header">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-users"></i>Passengers</span>
                        <span class="trip-passenger-count" id="tripDetailsPassengerCount">0 passengers</span>
                    </div>
                    <div class="trip-passenger-list" id="tripDetailsPassengerList"></div>
                </div>

                <div class="trip-secondary-grid">
                    <div class="trip-secondary-item">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-route"></i>Trip Type</span>
                        <span class="request-modal-value" id="tripDetailsMode">-</span>
                    </div>
                    <div class="trip-secondary-item">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-user-group"></i>Total Passengers</span>
                        <span class="request-modal-value" id="tripDetailsTotalPassengers">-</span>
                    </div>
                    <div class="trip-secondary-item">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-scale-balanced"></i>Fare Split Type</span>
                        <span class="request-modal-value" id="tripDetailsSplitType">-</span>
                    </div>
                </div>

                <div class="trip-secondary-grid trip-secondary-grid-2col">
                    <div class="trip-secondary-item">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-wallet"></i>Payment Method</span>
                        <span class="request-modal-value" id="tripDetailsPaymentMethod">-</span>
                    </div>
                    <div class="trip-secondary-item">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-route"></i>Custom Stop Extra</span>
                        <span class="request-modal-value" id="tripDetailsExtraFee">-</span>
                        <span class="trip-amount-due-hint" id="tripDetailsCustomStop">Only charged to the passenger using the custom stop.</span>
                    </div>
                    <div class="trip-secondary-item">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-sack-dollar"></i>Total Trip Fare</span>
                        <span class="request-modal-value" id="tripDetailsFareTotal">-</span>
                        <span class="trip-amount-due-hint" style="color:#64748b;">This is the full fare for the whole trip.</span>
                    </div>
                    <div class="trip-secondary-item">
                        <span class="request-modal-label trip-icon-label"><i class="fa-regular fa-clock"></i>Marked At</span>
                        <span class="request-modal-value" id="tripDetailsMarkedAt">-</span>
                    </div>
                </div>

                <div class="trip-fare-highlight">
                    <div class="trip-fare-highlight-row">
                        <span class="trip-fare-highlight-label"><i class="fa-solid fa-money-bill-wave"></i>Amount Due</span>
                        <span class="trip-fare-highlight-value" id="tripDetailsAmountDue">-</span>
                    </div>
                    <p class="trip-amount-due-hint" id="tripDetailsFareBreakdown">Base split + custom extra, if any.</p>
                </div>

                <div class="trip-remarks-note">
                    <span class="request-modal-label trip-icon-label"><i class="fa-regular fa-note-sticky"></i>Payment Remarks</span>
                    <p class="trip-remarks-text" id="tripDetailsPaymentRemarks">-</p>
                </div>
            </div>
            <div class="trip-contact-bar">
                <div class="trip-actions-filled" id="tripDetailsPaymentActionWrap" style="display:none;">
                    <button type="button" class="trip-action-btn is-filled" id="tripDetailsPaymentActionBtn"></button>
                </div>
                <div class="trip-actions-filled" id="tripDetailsContactActions">
                    <a href="#" class="trip-action-btn is-filled email-btn" id="tripDetailsEmail">
                        <i class="fa-regular fa-envelope"></i> Email
                    </a>
                    <a href="#" target="_blank" rel="noopener" class="trip-action-btn is-filled whatsapp-btn" id="tripDetailsWhatsapp">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="request-modal" id="rejectModal" aria-hidden="true">
        <div class="request-modal-card request-modal-compact">
            <div class="request-modal-head">
                <div>
                    <h3 class="request-modal-title">Reject Payment</h3>
                    <p class="request-modal-sub">State the reason why this payment request is rejected.</p>
                </div>
                <button type="button" class="modal-close-square" id="rejectModalCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="request-modal-grid-two" style="margin-bottom: 12px;">
                <div class="request-modal-line">
                    <span class="request-modal-label"><i class="fa-solid fa-user" style="margin-right:4px;"></i> Passenger</span>
                    <span class="request-modal-value" id="rejectModalPassenger">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label"><i class="fa-solid fa-hashtag" style="margin-right:4px;"></i> Trip Ref</span>
                    <span class="request-modal-value" id="rejectModalTrip">-</span>
                </div>
            </div>
            <form id="rejectModalForm" method="POST" style="margin-bottom: 20px;">
                @csrf
                @method('PATCH')
                <div class="reject-reason-wrap">
                    <label class="request-modal-label" style="display:block; margin-bottom:6px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px; color:#eab308;"></i> Rejection Reason</label>
                    <textarea
                        class="reject-reason-input-modern"
                        id="rejectModalReason"
                        name="rejection_reason"
                        placeholder="Explain briefly why this payment was rejected..."
                        required
                    ></textarea>
                </div>
            </form>
            <div class="reject-modal-actions">
                <button type="button" class="btn-subtle-close" id="rejectModalCancel">Cancel</button>
                <button type="submit" class="btn-solid-reject" form="rejectModalForm"><i class="fa-solid fa-xmark"></i> Reject Payment</button>
            </div>
        </div>
    </div>

    <div class="request-modal" id="driverPaymentDetailsModal" aria-hidden="true">
        <div class="request-modal-card trip-details-card driver-payment-details-card">
            <div class="request-modal-head">
                <h3 class="request-modal-title">Driver Payment Details</h3>
                <button type="button" class="modal-close-square" id="driverPaymentDetailsCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="driver-payment-scroll">
                <div class="request-modal-grid">
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-id-badge"></i>Driver Profile</span>
                        <div class="driver-payment-head">
                            <span class="driver-payment-avatar" id="driverPaymentAvatar">D</span>
                            <span class="driver-payment-meta">
                                <span class="driver-payment-name" id="driverPaymentName">-</span>
                                <span class="driver-payment-email" id="driverPaymentEmail">-</span>
                            </span>
                        </div>
                    </div>
                    <div class="trip-details-pairs">
                        <div class="request-modal-line">
                            <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-building-columns"></i>Bank / Wallet</span>
                            <span class="request-modal-value" id="driverPaymentBank">-</span>
                        </div>
                        <div class="request-modal-line">
                            <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Account Holder Name</span>
                            <span class="request-modal-value" id="driverPaymentAccountName">-</span>
                        </div>
                        <div class="request-modal-line">
                            <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Account Number</span>
                            <span class="request-modal-value" id="driverPaymentAccountNumber">-</span>
                        </div>
                    </div>
                    <div class="driver-payment-qr-grid">
                        <div class="driver-payment-qr-card">
                            <span class="driver-payment-qr-title"><i class="fa-solid fa-qrcode"></i> DuitNow QR</span>
                            <div class="driver-payment-qr-preview" id="driverPaymentDuitnowWrap">
                                <span class="driver-payment-qr-empty">No QR uploaded</span>
                            </div>
                        </div>
                        <div class="driver-payment-qr-card">
                            <span class="driver-payment-qr-title"><i class="fa-solid fa-qrcode"></i> Touch 'n Go QR</span>
                            <div class="driver-payment-qr-preview" id="driverPaymentTngWrap">
                                <span class="driver-payment-qr-empty">No QR uploaded</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="request-modal-actions" style="justify-content:flex-end;">
                <button type="button" class="payments-btn" id="driverPaymentDetailsClose">Close</button>
            </div>
        </div>
    </div>


    <div id="bulkActionBar" class="bulk-action-bar" style="display: none;">
        <div class="bulk-action-content">
            <span id="bulkActionCount">0 selected</span>
            <div style="margin:0; display:flex; gap:6px; align-items:center;">
                <button type="button" class="btn btn-ghost bulk-icon-btn" id="bulkCancelBtn" title="Cancel selection" aria-label="Cancel selection"><i class="fa-solid fa-xmark"></i></button>
                <button type="button" class="btn btn-ghost bulk-icon-btn" id="floatingSelectAllBtn" title="Select all" aria-label="Select all"><i class="fa-solid fa-check-double"></i></button>
                <button type="button" class="btn btn-success" id="bulkMarkPaidOpenBtn" style="height:38px; font-size:13.5px; border-radius:10px;">
                    <i class="fa-solid fa-check-double"></i> Mark Paid
                </button>
                <button type="button" class="btn-solid-approve" id="bulkApproveOpenBtn" style="height:38px; font-size:13.5px; border-radius:10px; display:none;">
                    <i class="fa-solid fa-check"></i> Approve
                </button>
                <button type="button" class="btn-solid-reject" id="bulkRejectOpenBtn" style="height:38px; font-size:13.5px; border-radius:10px; display:none;">
                    <i class="fa-solid fa-xmark"></i> Reject
                </button>
            </div>
        </div>
    </div>

    <!-- Mark Paid Action Modal (Desktop & Mobile Popup) -->
    <div class="request-modal" id="markPaidModal" aria-hidden="true">
        <div class="request-modal-card mark-paid-modal-card">
            <div class="request-modal-head">
                <h3 class="request-modal-title">Mark Payment as Paid</h3>
                <button type="button" class="modal-close-square" id="markPaidModalCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form method="POST" id="markPaidModalForm" action="">
                @csrf
                @method('PATCH')
                <div class="mark-paid-modal-body">
                    <div class="mark-paid-info-box">
                        <div>
                            <div class="mark-paid-passenger-name" id="markPaidModalPassenger">Passenger Name</div>
                            <div style="font-size:12px; color:var(--muted);" id="markPaidModalTrip">TRP-00000</div>
                        </div>
                        <div class="mark-paid-amount-val" id="markPaidModalAmount">RM 0.00</div>
                    </div>
                    
                    <div class="mark-paid-inputs-row">
                        <select name="payment_method" class="mark-paid-select" id="markPaidModalMethod" required>
                            <option value="" disabled selected>Select method</option>
                            <option value="duitnow_qr">DuitNow QR / Instant Transfer</option>
                            <option value="cash">Cash / Tunai</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other / Lain-lain</option>
                        </select>
                        <input type="text" name="remarks" class="mark-paid-input" id="markPaidModalRemarks" placeholder="Remarks">
                    </div>

                    <button type="submit" class="mark-paid-submit-btn">
                        Mark as paid
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Mark Paid Action Modal (Desktop & Mobile Popup) -->
    <div class="request-modal" id="bulkMarkPaidModal" aria-hidden="true">
        <div class="request-modal-card mark-paid-modal-card">
            <div class="request-modal-head">
                <h3 class="request-modal-title">Mark Selected as Paid</h3>
                <button type="button" class="modal-close-square" id="bulkMarkPaidModalCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form method="POST" id="bulkMarkPaidModalForm" action="{{ route('payments.bulk-confirm') }}">
                @csrf
                @method('PATCH')
                <div id="bulkMarkPaidHiddenInputs"></div>
                <div class="mark-paid-modal-body">
                    <div class="mark-paid-info-box">
                        <div>
                            <div class="mark-paid-passenger-name" id="bulkMarkPaidSelectedCount">0 payments selected</div>
                            <div style="font-size:12px; color:var(--muted);">Bulk Confirmation</div>
                        </div>
                        <div class="mark-paid-amount-val" id="bulkMarkPaidTotalAmount">RM 0.00</div>
                    </div>
                    
                    <div class="bulk-paid-passengers-card" id="bulkMarkPaidPassengersWrap">
                        <div class="bulk-paid-passengers-title" id="bulkMarkPaidPassengersTitle">
                            <i class="fa-solid fa-users"></i> Selected Passengers / Counterparties
                        </div>
                        <div class="bulk-paid-passengers-list" id="bulkMarkPaidPassengersList">
                        </div>
                    </div>
                    
                    <div class="mark-paid-inputs-row">
                        <select name="payment_method" class="mark-paid-select" id="bulkMarkPaidMethod" required>
                            <option value="" disabled selected>Select method</option>
                            <option value="duitnow_qr">DuitNow QR / Instant Transfer</option>
                            <option value="cash">Cash / Tunai</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other / Lain-lain</option>
                        </select>
                        <input type="text" name="remarks" class="mark-paid-input" id="bulkMarkPaidRemarks" placeholder="Remarks">
                    </div>

                    <button type="submit" class="mark-paid-submit-btn" id="bulkMarkPaidSubmitBtn">
                        Mark Selected as Paid
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Approve Modal (Review Tab) -->
    <div class="request-modal" id="bulkApproveModal" aria-hidden="true">
        <div class="request-modal-card request-modal-compact">
            <div class="request-modal-head">
                <div>
                    <h3 class="request-modal-title">Bulk Approve Payments</h3>
                    <p class="request-modal-sub">Approve all selected passenger payment confirmation requests.</p>
                </div>
                <button type="button" class="modal-close-square" id="bulkApproveModalCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form method="POST" id="bulkApproveModalForm" action="{{ route('payments.bulk-confirm') }}">
                @csrf
                @method('PATCH')
                <div id="bulkApproveHiddenInputs"></div>
                <div class="mark-paid-modal-body" style="padding:0; margin-top:16px;">
                    <div class="mark-paid-info-box" style="background:#f0fdf4; border-color:#bbf7d0;">
                        <div>
                            <div class="mark-paid-passenger-name" id="bulkApproveSelectedCount" style="color:#15803d;">0 payments selected</div>
                            <div style="font-size:12px; color:#166534;">Bulk Approval</div>
                        </div>
                        <div class="mark-paid-amount-val" id="bulkApproveTotalAmount" style="color:#15803d;">RM 0.00</div>
                    </div>
                    
                    <div class="bulk-paid-passengers-card" id="bulkApprovePassengersWrap" style="margin-top:12px;">
                        <div class="bulk-paid-passengers-title">
                            <i class="fa-solid fa-users"></i> Selected Passengers
                        </div>
                        <div class="bulk-paid-passengers-list" id="bulkApprovePassengersList"></div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
                        <button type="button" class="btn-subtle-close" id="bulkApproveModalCancel">Cancel</button>
                        <button type="submit" class="btn-solid-approve" id="bulkApproveSubmitBtn">
                            <i class="fa-solid fa-check"></i> Confirm Approve Selected
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Reject Modal (Review Tab) -->
    <div class="request-modal" id="bulkRejectModal" aria-hidden="true">
        <div class="request-modal-card request-modal-compact">
            <div class="request-modal-head">
                <div>
                    <h3 class="request-modal-title">Bulk Reject Payments</h3>
                    <p class="request-modal-sub">State reason for rejecting selected payment requests.</p>
                </div>
                <button type="button" class="modal-close-square" id="bulkRejectModalCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form method="POST" id="bulkRejectModalForm" action="{{ route('payments.bulk-reject') }}">
                @csrf
                @method('PATCH')
                <div id="bulkRejectHiddenInputs"></div>
                <div class="mark-paid-modal-body" style="padding:0; margin-top:16px;">
                    <div class="mark-paid-info-box" style="background:#fef2f2; border-color:#fecaca;">
                        <div>
                            <div class="mark-paid-passenger-name" id="bulkRejectSelectedCount" style="color:#b91c1c;">0 payments selected</div>
                            <div style="font-size:12px; color:#991b1b;">Bulk Rejection</div>
                        </div>
                        <div class="mark-paid-amount-val" id="bulkRejectTotalAmount" style="color:#b91c1c;">RM 0.00</div>
                    </div>

                    <div class="reject-reason-wrap" style="margin-top:14px; margin-bottom:16px;">
                        <label class="request-modal-label" style="display:block; margin-bottom:6px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px; color:#eab308;"></i> Rejection Reason</label>
                        <textarea
                            class="reject-reason-input-modern"
                            id="bulkRejectReason"
                            name="rejection_reason"
                            placeholder="Explain briefly why these payment requests are rejected..."
                            required
                        ></textarea>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
                        <button type="button" class="btn-subtle-close" id="bulkRejectModalCancel">Cancel</button>
                        <button type="submit" class="btn-solid-reject" id="bulkRejectSubmitBtn">
                            <i class="fa-solid fa-xmark"></i> Confirm Reject Selected
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>window.CH_PAYMENTS = { csrf: @json(csrf_token()), endpointBase: @json(route('refresh.payments.summary')) };</script>
    <script src="{{ asset('js/payments-index.js') }}?v={{ filemtime(public_path('js/payments-index.js')) }}"></script>
@endsection
