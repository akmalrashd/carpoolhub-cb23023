@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    {{-- Page styles, extracted to a cacheable static file; link kept at the same position as the <style> block so cascade order is unchanged. --}}
    <link rel="stylesheet" href="{{ asset('css/trips.css') }}?v={{ filemtime(public_path('css/trips.css')) }}">


    @php
        $tripStatusCounts = $tripStatusCounts ?? [];
        $allCount         = (int) ($tripStatusCounts['all'] ?? $trips->total());
        $upcomingCount    = (int) ($tripStatusCounts['upcoming'] ?? 0);
        $completedCount   = (int) ($tripStatusCounts['completed'] ?? 0);
        $draftCount       = (int) ($tripStatusCounts['draft'] ?? 0);
        $cancelledCount   = (int) ($tripStatusCounts['cancelled'] ?? 0);
        $inProgressCount  = 0;
        $isAdmin = auth()->user()->role === 'admin';

        $activeChip = $filters['status_filter'] ?? request('status_filter', 'all');
    @endphp

    {{-- ── Page header ── --}}
    <div class="trips-page-header">
        <div class="trips-page-header-left">
            <p class="trips-eyebrow">{{ $isAdmin ? 'Admin trips' : 'My trips' }}</p>
            <h1 class="trips-h1">{{ $isAdmin ? 'All user trips' : 'Your trips' }}</h1>
            <p class="trips-sub">{{ $isAdmin ? 'Review and manage every trip created by users.' : "All trips you've driven or joined." }}</p>
            <div class="tabs">
                <span class="tab {{ $activeChip === 'all' ? 'active' : '' }}" data-tab="all">All &middot; {{ $allCount }}</span>
                <span class="tab {{ $activeChip === 'upcoming' ? 'active' : '' }}" data-tab="upcoming">Upcoming &middot; {{ $upcomingCount }}</span>
                <span class="tab {{ $activeChip === 'completed' ? 'active' : '' }}" data-tab="completed">Past &middot; {{ $completedCount }}</span>
                <span class="tab {{ $activeChip === 'draft' ? 'active' : '' }}" data-tab="draft">Drafts &middot; {{ $draftCount }}</span>
                <span class="tab {{ $activeChip === 'cancelled' ? 'active' : '' }}" data-tab="cancelled">Cancelled &middot; {{ $cancelledCount }}</span>
            </div>
        </div>
        <div class="trips-header-actions">
            <button type="button" class="btn btn-ghost btn-sm" id="tripsFilterBtn" onclick="(function(){var p=document.getElementById('tripsFilterPanel');p.style.display=p.offsetParent===null?'grid':'none';})()">
                <i class="fa-solid fa-sliders"></i>
                Filter
            </button>
            @if(in_array(auth()->user()->role, ['admin', 'driver'], true))
                <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i>
                    New Trip
                </a>
            @endif
        </div>
    </div>

    {{-- ── Filter form (standalone, outside table card) ── --}}
    <form method="GET" action="{{ route('trips.index') }}" class="trips-filter-form" id="tripsFilterPanel" style="{{ (request()->hasAny(['date_from','date_to','visibility','trip_search'])) ? '' : 'display:none' }}">
        @if($activeChip !== 'all')
            <input type="hidden" name="status_filter" value="{{ $activeChip }}">
        @endif
        <p class="trips-filter-hint">Filters apply automatically on change.</p>
        <div class="trips-filter-field">
            <label class="trips-filter-label" for="trip_date_from">From Date</label>
            <input id="trip_date_from" name="date_from" type="date" class="trips-filter-input"
                value="{{ $filters['date_from'] ?? request('date_from') }}" onchange="this.form.submit()">
        </div>
        <div class="trips-filter-field">
            <label class="trips-filter-label" for="trip_date_to">To Date</label>
            <input id="trip_date_to" name="date_to" type="date" class="trips-filter-input"
                value="{{ $filters['date_to'] ?? request('date_to') }}" onchange="this.form.submit()">
        </div>
        <div class="trips-filter-field">
            <label class="trips-filter-label" for="trip_visibility">Visibility</label>
            <select id="trip_visibility" name="visibility" class="trips-filter-input" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="public"  {{ ($filters['visibility'] ?? request('visibility')) === 'public'  ? 'selected' : '' }}>Public</option>
                <option value="private" {{ ($filters['visibility'] ?? request('visibility')) === 'private' ? 'selected' : '' }}>Private</option>
            </select>
        </div>
        <div class="trips-filter-field">
            <label class="trips-filter-label" for="trip_search">Search</label>
            <input id="trip_search" name="trip_search" type="text" class="trips-filter-input"
                placeholder="Route, driver, or passenger"
                value="{{ $filters['trip_search'] ?? request('trip_search') }}">
        </div>
        <div class="trips-filter-actions">
            <a href="{{ route('trips.index') }}" class="trips-filter-reset">Reset</a>
        </div>
    </form>

    {{-- ── Table card ── --}}
    <div class="trips-table-section">
        <div class="trips-table-card">

            {{-- Filter chips --}}
            <div class="trips-chip-row">
                @php
                    $chips = [
                        ['key' => 'all',         'label' => 'All',         'count' => null],
                        ['key' => 'upcoming',    'label' => 'Upcoming',    'count' => $upcomingCount],
                        ['key' => 'in_progress', 'label' => 'In Progress', 'count' => $inProgressCount],
                        ['key' => 'completed',   'label' => 'Completed',   'count' => $completedCount],
                        ['key' => 'cancelled',   'label' => 'Cancelled',   'count' => $cancelledCount],
                    ];
                @endphp
                @foreach($chips as $chip)
                    <a
                        href="{{ route('trips.index', array_merge(request()->except('status_filter','page'), ['status_filter' => $chip['key']])) }}"
                        class="trips-chip {{ $activeChip === $chip['key'] ? 'active' : '' }}"
                    >
                        {{ $chip['label'] }}
                        @if($chip['count'] !== null)
                            <span class="trips-chip-count">{{ $chip['count'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>


            <div style="position: relative;">
                {{-- Skeleton Loading Container --}}
                <style>
                    .trips-skel-container .trip-mobile-skel { display: flex; flex-direction: column; gap: 10px; }
                    .trips-skel-container .trip-table-skel { display: none; }
                    @media (min-width: 1024px) {
                        .trips-skel-container .trip-mobile-skel { display: none; }
                        .trips-skel-container .trip-table-skel { display: block; padding: 12px 16px; }
                    }
                </style>
                <div class="trips-skel-container" id="trips-skel-container">
                {{-- Desktop Table Skeleton --}}
                <div class="trip-table-skel">
                    <table class="trip-table" style="pointer-events:none; margin:0; border:0; width:100%;">
                        <thead>
                            <tr>
                                <th>Trip</th>
                                <th>When</th>
                                <th>Visibility</th>
                                <th>Seats</th>
                                <th>Status</th>
                                <th style="text-align:right;">Fare</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                        <span class="sk" style="height:16px; width:180px; display:block; border-radius:6px;"></span>
                                        <span class="sk" style="height:11px; width:110px; display:block; border-radius:4px;"></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="sk" style="height:13px; width:110px; display:block; border-radius:4px;"></span>
                                </td>
                                <td>
                                    <span class="sk" style="height:22px; width:75px; display:block; border-radius:999px;"></span>
                                </td>
                                <td>
                                    <span class="sk" style="height:13px; width:45px; display:block; border-radius:4px;"></span>
                                </td>
                                <td>
                                    <span class="sk" style="height:24px; width:85px; display:block; border-radius:999px;"></span>
                                </td>
                                <td style="text-align:right;">
                                    <span class="sk" style="height:16px; width:75px; display:inline-block; border-radius:6px;"></span>
                                </td>
                                <td style="text-align:right;">
                                    <span class="sk" style="height:34px; width:100px; display:inline-block; border-radius:11px;"></span>
                                </td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                {{-- Mobile List Skeleton --}}
                <div class="trip-mobile-skel">
                    @for($i = 0; $i < 5; $i++)
                    <div class="trip-mobile-item" style="pointer-events:none; opacity:0.95; background:var(--surface) !important; border:1px solid var(--hairline) !important; border-radius:13px !important; padding:12px !important; display:flex !important; flex-direction:column !important; gap:9px !important; box-shadow:0 5px 12px rgba(15,23,42,.06) !important;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div style="flex:1; display:flex; flex-direction:column; gap:6px; min-width:0; padding-right:12px;">
                                <span class="sk" style="height:18px; width:65%; border-radius:6px; display:block;"></span>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <span class="sk" style="height:11px; width:90px; border-radius:4px; display:inline-block;"></span>
                                    <span class="sk" style="height:11px; width:60px; border-radius:4px; display:inline-block;"></span>
                                </div>
                            </div>
                            <span class="sk" style="height:24px; width:75px; border-radius:999px; flex-shrink:0;"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span class="sk" style="height:12px; width:130px; border-radius:4px;"></span>
                            <span class="sk" style="height:12px; width:60px; border-radius:4px;"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:2px;">
                            <span class="sk" style="height:20px; width:75px; border-radius:6px;"></span>
                            <span class="sk" style="height:36px; width:120px; border-radius:11px;"></span>
                        </div>
                    </div>
                    @endfor
                </div>
            </div>

            <div class="trips-real-container" id="trips-real-container" data-initial-load="{{ ($initialLoad ?? false) ? 'true' : 'false' }}" style="display:none; opacity:0;">
            {{-- Empty state --}}
            @if($trips->isEmpty())
                <div class="trips-empty">
                    <div class="trips-empty-icon"><i class="fa-solid fa-compass"></i></div>
                    <p class="trips-empty-title">No trips found</p>
                    <p class="trips-empty-copy">No public trips match your filters right now. Try changing your search or check back later.</p>
                    @if(in_array(auth()->user()->role, ['admin', 'driver'], true))
                        <a href="{{ route('trips.create') }}" class="ch-empty-state-btn">
                            <i class="fa-solid fa-plus"></i>
                            Post a New Trip
                        </a>
                    @endif
                </div>

            @else

                {{-- Mobile card list --}}
                <div class="trip-mobile-list">
                    @foreach($trips as $trip)
                        @php
                            $hasReturn           = (bool) $trip->returnTrip;
                            $pickupName          = $trip->pickup_name ?? 'Pickup';
                            $destinationName     = $trip->destination_name ?? 'Destination';
                            $directionText       = $pickupName . ' → ' . $destinationName;
                            $returnDirectionText = $destinationName . ' → ' . $pickupName;
                            $routeName           = $trip->savedRoute?->route_name ?: $directionText;
                            $modeText            = $hasReturn ? 'Two-Way' : 'One-Way';
                            $visibilityText      = ucfirst((string) ($trip->visibility ?? 'private')) . ' Trip';
                            $visibilityIcon      = ($trip->visibility ?? 'private') === 'public' ? 'fa-solid fa-lock-open' : 'fa-solid fa-lock';
                            $combinedFare        = (float) $trip->fare_total + (float) ($trip->returnTrip?->fare_total ?? 0);
                            $myFare              = (float) ($trip->payments->where('user_id', auth()->id())->first()?->amount_due ?? 0)
                                                 + (float) ($trip->returnTrip?->payments?->where('user_id', auth()->id())->first()?->amount_due ?? 0);
                            $showTotalFare       = auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id;
                            $fareLabel           = 'Fare';
                            $displayFare         = $showTotalFare ? $combinedFare : $myFare;
                            $tripRef             = $trip->trip_ref ?: 'TRP-' . str_pad($trip->id, 5, '0', STR_PAD_LEFT);
                            $pairedTripId        = $trip->returnTrip?->id;
                            $paymentFocusIds     = array_values(array_filter([
                                (int) $trip->id,
                                $pairedTripId ? (int) $pairedTripId : null,
                            ]));
                            $paymentFocusQuery   = implode(',', $paymentFocusIds);
                            $participantPayload  = $trip->participants
                                ->map(fn ($participant) => [
                                    'user_id'   => $participant->user_id,
                                    'name'      => $participant->user?->name ?? '-',
                                    'email'     => $participant->user?->email ?? '',
                                    'photo_url' => $participant->user?->profile_photo_url ?? null,
                                    'is_driver' => (bool) $participant->is_driver,
                                ])
                                ->values();
                            $participantPayloadB64 = base64_encode($participantPayload->toJson());
                            $routePointPayload   = $trip->passengerRoutePoints
                                ->filter(fn ($point) => in_array((string) $point->status, ['accepted', 'approved'], true))
                                ->filter(fn ($point) => ! $point->uses_default_pickup || ! $point->uses_default_dropoff)
                                ->map(fn ($point) => [
                                    'name'    => $point->user?->name ?? 'Passenger',
                                    'pickup'  => $point->uses_default_pickup ? null : [
                                        'lat'   => $point->pickup_latitude !== null ? (float) $point->pickup_latitude : null,
                                        'lng'   => $point->pickup_longitude !== null ? (float) $point->pickup_longitude : null,
                                        'label' => ($point->user?->name ?? 'Passenger') . ' pickup',
                                    ],
                                    'dropoff' => $point->uses_default_dropoff ? null : [
                                        'lat'   => $point->dropoff_latitude !== null ? (float) $point->dropoff_latitude : null,
                                        'lng'   => $point->dropoff_longitude !== null ? (float) $point->dropoff_longitude : null,
                                        'label' => ($point->user?->name ?? 'Passenger') . ' drop-off',
                                    ],
                                ])
                                ->values();
                            $routePointPayloadB64 = base64_encode($routePointPayload->toJson());
                            $passengerCount = (int) $trip->participants->where('is_driver', false)->count();
                            if ($passengerCount === 0 && (int) $trip->participant_count > 0) {
                                $passengerCount = (int) $trip->participant_count;
                            }
                            $seatsTaken      = $passengerCount;
                            $seatsAvailable  = $trip->seat_limit ?? $trip->available_seats ?? '-';
                            $splitType = ((int) $trip->participant_count > $passengerCount)
                                ? 'Driver Included in Fare Split'
                                : 'Driver Excluded from Fare Split';

                            $statusSlug = strtolower((string) $trip->status);
                            $badgeStatus = $statusSlug;
                            $statusLabel = match($statusSlug) {
                                'scheduled' => 'Scheduled',
                                'recorded' => 'Recorded',
                                'confirmed' => 'Confirmed',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'draft' => 'Draft',
                                default => \Illuminate\Support\Str::headline((string) $trip->status),
                            };
                            $paymentUrl = route('payments.index', $paymentFocusQuery ? ['trip_ids' => $paymentFocusQuery] : ['trip_id' => $trip->id]);
                            $paymentStatuses = $trip->payments->pluck('payment_status');
                            if ($trip->returnTrip?->payments) {
                                $paymentStatuses = $paymentStatuses->merge($trip->returnTrip->payments->pluck('payment_status'));
                            }
                            $reviewTripPayments = collect([$trip, $trip->returnTrip])->filter();
                            $paymentReviewTripIds = $reviewTripPayments->pluck('id')->filter()->implode(', ');
                            $reviewPayments = $reviewTripPayments
                                ->flatMap(fn ($reviewTrip) => $reviewTrip->payments->map(function ($payment) use ($reviewTrip, $trip) {
                                    $payment->review_leg_label = ((int) $reviewTrip->id === (int) $trip->id) ? 'Outbound' : 'Return';
                                    return $payment;
                                }))
                                ->values();
                                $paymentReviewPayload = $reviewPayments
                                ->map(function ($payment) use ($reviewTripPayments, $routeName, $trip) {
                                    $paymentTrip = $reviewTripPayments->firstWhere('id', $payment->trip_id);
                                    $routePoint = $paymentTrip?->passengerRoutePoints?->first(fn ($point) => (int) $point->user_id === (int) $payment->user_id
                                        && in_array((string) $point->status, ['accepted', 'approved'], true)
                                        && (float) ($point->extra_fee_amount ?? 0) > 0);
                                    $extraFee = (float) ($routePoint?->extra_fee_amount ?? 0);
                                    $amountDue = (float) $payment->amount_due;

                                    return [
                                        'id' => $payment->id,
                                        'passenger' => $payment->user?->name ?: 'Passenger',
                                        'initials' => collect(explode(' ', $payment->user?->name ?: 'P'))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode(''),
                                        'trip' => ($payment->review_leg_label ?? 'Trip') . ' · ' . ($trip->trip_ref ?: 'TRP-' . str_pad($trip->id, 5, '0', STR_PAD_LEFT)),
                                        'amount' => number_format($amountDue, 2),
                                        'base_amount' => number_format(max(0, $amountDue - $extraFee), 2),
                                        'extra_fee' => number_format($extraFee, 2),
                                        'has_extra_fee' => $extraFee > 0,
                                        'status' => (string) $payment->payment_status,
                                        'status_label' => match ((string) $payment->payment_status) {
                                            'pending_confirmation' => 'Awaiting',
                                            'paid' => 'Paid',
                                            'unpaid' => 'Unpaid',
                                            default => \Illuminate\Support\Str::headline((string) $payment->payment_status),
                                        },
                                        'receipt_no' => 'PAY-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                                        'method' => $payment->payment_method ? ucfirst(str_replace('_', ' ', (string) $payment->payment_method)) : 'DuitNow',
                                        'marked_at' => $payment->marked_paid_at?->diffForHumans() ?: '-',
                                        'marked_at_full' => $payment->marked_paid_at?->format('d M Y, H:i') ?: '-',
                                        'confirmed_at' => $payment->confirmed_at?->format('d M Y, H:i') ?: '-',
                                        'route_name' => $routeName,
                                        'driver' => $trip->driver?->name ?: '-',
                                        'driver_email' => $trip->driver?->email ?: '-',
                                        'driver_photo' => ($trip->driver?->profile_photo_url ?? ''),
                                        'driver_bank' => $trip->driver?->payment_bank_name ?: '-',
                                        'driver_account_name' => $trip->driver?->payment_account_name ?: '-',
                                        'driver_account_number' => $trip->driver?->payment_account_number ?: '-',
                                        'driver_duitnow_qr' => $trip->driver?->payment_qr_duitnow_url ?: '',
                                        'driver_tng_qr' => $trip->driver?->payment_qr_tng_url ?: '',
                                        'mark_url' => route('payments.mark-paid', $payment),
                                        'confirm_url' => route('payments.confirm-paid', $payment),
                                        'reject_url' => route('payments.reject-paid', $payment),
                                        'reminder_url' => route('payments.send-reminder', $payment),
                                    ];
                                })
                                ->values();
                            $paymentReviewPayloadB64 = base64_encode($paymentReviewPayload->toJson());
                            $currentUserPayments = $reviewPayments->where('user_id', auth()->id())->values();
                            $currentUserPaymentStatuses = $currentUserPayments->pluck('payment_status');
                            $requestPayload = $trip->joinRequests
                                ->filter(fn ($joinRequest) => in_array((string) $joinRequest->status, ['pending', 'approved'], true))
                                ->map(function ($joinRequest) use ($trip) {
                                    $routePoint = $joinRequest->routePoint;

                                    return [
                                        'id' => $joinRequest->id,
                                        'passenger' => $joinRequest->user?->name ?: 'Passenger',
                                        'initials' => collect(explode(' ', $joinRequest->user?->name ?: 'P'))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode(''),
                                        'status' => (string) $joinRequest->status,
                                        'requested_at' => $joinRequest->created_at?->diffForHumans() ?: '-',
                                        'note' => $joinRequest->request_note ?: '',
                                        'pickup' => $routePoint ? ($routePoint->uses_default_pickup ? 'Default pickup' : ($routePoint->pickup_name ?: 'Custom pickup')) : 'Default pickup',
                                        'dropoff' => $routePoint ? ($routePoint->uses_default_dropoff ? 'Default drop-off' : ($routePoint->dropoff_name ?: 'Custom drop-off')) : 'Default drop-off',
                                        'pickup_meta' => $routePoint && ! $routePoint->uses_default_pickup
                                            ? trim(collect([
                                                $routePoint->pickup_distance_km !== null ? number_format((float) $routePoint->pickup_distance_km, 2) . ' km from route' : null,
                                                $routePoint->requested_pickup_time?->format('d M Y, H:i'),
                                            ])->filter()->implode(' · '))
                                            : "Uses driver's route starting point",
                                        'dropoff_meta' => $routePoint && ! $routePoint->uses_default_dropoff
                                            ? trim(collect([
                                                $routePoint->dropoff_distance_km !== null ? number_format((float) $routePoint->dropoff_distance_km, 2) . ' km from route' : null,
                                                $routePoint->detour_distance_km !== null ? 'Detour ' . number_format((float) $routePoint->detour_distance_km, 2) . ' km' : null,
                                            ])->filter()->implode(' · '))
                                            : "Uses driver's route ending point",
                                        'fare' => $routePoint?->extra_fee_amount !== null ? number_format((float) $routePoint->extra_fee_amount, 2) : null,
                                        'detour_km' => $routePoint?->detour_distance_km !== null ? (float) $routePoint->detour_distance_km : null,
                                        'detour_min' => $routePoint?->detour_duration_minutes !== null ? (int) $routePoint->detour_duration_minutes : null,
                                        'deviationKm' => $routePoint?->detour_distance_km !== null ? (float) $routePoint->detour_distance_km : 0,
                                        'name' => $joinRequest->user?->name ?: 'Passenger',
                                        'pickup_point' => [
                                            'lat' => $routePoint && ! $routePoint->uses_default_pickup && $routePoint->pickup_latitude !== null ? (float) $routePoint->pickup_latitude : null,
                                            'lng' => $routePoint && ! $routePoint->uses_default_pickup && $routePoint->pickup_longitude !== null ? (float) $routePoint->pickup_longitude : null,
                                            'label' => $routePoint && ! $routePoint->uses_default_pickup ? (($joinRequest->user?->name ?: 'Passenger') . ' pickup') : null,
                                        ],
                                        'dropoff_point' => [
                                            'lat' => $routePoint && ! $routePoint->uses_default_dropoff && $routePoint->dropoff_latitude !== null ? (float) $routePoint->dropoff_latitude : null,
                                            'lng' => $routePoint && ! $routePoint->uses_default_dropoff && $routePoint->dropoff_longitude !== null ? (float) $routePoint->dropoff_longitude : null,
                                            'label' => $routePoint && ! $routePoint->uses_default_dropoff ? (($joinRequest->user?->name ?: 'Passenger') . ' drop-off') : null,
                                        ],
                                        'pickup_lat' => $routePoint?->pickup_latitude !== null ? (float) $routePoint->pickup_latitude : null,
                                        'pickup_lng' => $routePoint?->pickup_longitude !== null ? (float) $routePoint->pickup_longitude : null,
                                        'dropoff_lat' => $routePoint?->dropoff_latitude !== null ? (float) $routePoint->dropoff_latitude : null,
                                        'dropoff_lng' => $routePoint?->dropoff_longitude !== null ? (float) $routePoint->dropoff_longitude : null,
                                        'fit' => $routePoint?->route_fit_score !== null ? ((int) $routePoint->route_fit_score . '%') : null,
                                        'fit_label' => $routePoint?->route_fit_label ?: 'Driver review',
                                        'respond_url' => route('trips.join-requests.respond', $joinRequest),
                                        'trip' => $trip->trip_ref ?: 'TRP-' . str_pad($trip->id, 5, '0', STR_PAD_LEFT),
                                        'risk_score' => $joinRequest->user?->riskProfile?->risk_score ?? 70,
                                        'risk_level' => $joinRequest->user?->riskProfile?->risk_level ?? 'Moderate Risk',
                                        'risk_reliability' => $joinRequest->user?->riskProfile?->payment_reliability_score ?? 5.0,
                                        'risk_cancelled' => $joinRequest->user?->riskProfile?->cancelled_request_count ?? 0,
                                        'risk_absent' => $joinRequest->user?->riskProfile?->attendance_absent_count ?? 0,
                                        'risk_unpaid' => $joinRequest->user?->riskProfile?->overdue_case_count ?? 0,
                                    ];
                                })
                                ->values();
                            $requestPayloadB64 = base64_encode($requestPayload->toJson());
                            $currentUserPaymentPayload = $paymentReviewPayload
                                ->filter(fn ($payment) => $currentUserPayments->pluck('id')->contains($payment['id']))
                                ->values();
                            $payNowPayload = $currentUserPaymentPayload
                                ->filter(fn ($payment) => in_array((string) ($payment['status'] ?? ''), ['unpaid', 'paid', 'pending_confirmation'], true))
                                ->values();
                            $receiptPayload = $paymentReviewPayload
                                ->filter(fn ($payment) => (string) ($payment['status'] ?? '') === 'paid' && $currentUserPayments->pluck('id')->contains($payment['id']))
                                ->values();
                            $receiptPayloadB64 = base64_encode($receiptPayload->toJson());
                            $payNowPayloadB64 = base64_encode($payNowPayload->toJson());
                            $canManageTripPayment = in_array(auth()->user()->role, ['admin', 'driver'], true)
                                && (auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id);
                            $paymentActionLabel = null;
                            $paymentActionIcon = 'fa-solid fa-credit-card';
                            $paymentTableLabel = null;
                            if (! in_array($statusSlug, ['draft', 'cancelled'], true)) {
                                if (in_array($statusSlug, ['scheduled', 'confirmed'], true)) {
                                    $paymentActionLabel = null;
                                    $paymentTableLabel = null;
                                } elseif ($canManageTripPayment && $paymentStatuses->intersect(['unpaid', 'pending_confirmation'])->isNotEmpty()) {
                                    $paymentActionLabel = 'Review Payment';
                                    $paymentTableLabel = 'Review';
                                    $paymentActionIcon = 'fa-solid fa-clipboard-check';
                                } elseif ($canManageTripPayment && $statusSlug === 'recorded') {
                                    $paymentActionLabel = 'Review Payment';
                                    $paymentTableLabel = 'Review';
                                    $paymentActionIcon = 'fa-solid fa-clipboard-check';
                                } elseif ($currentUserPaymentStatuses->contains('unpaid')) {
                                    $paymentActionLabel = 'Pay Now';
                                    $paymentTableLabel = 'Pay';
                                    $paymentActionIcon = 'fa-solid fa-credit-card';
                                } elseif ($currentUserPaymentStatuses->contains('pending_confirmation')) {
                                    $paymentActionLabel = 'Pending';
                                    $paymentTableLabel = 'Pending';
                                    $paymentActionIcon = 'fa-regular fa-clock';
                                } elseif ($currentUserPaymentStatuses->contains('paid')) {
                                    $paymentActionLabel = 'Receipt';
                                    $paymentTableLabel = 'Receipt';
                                    $paymentActionIcon = 'fa-solid fa-receipt';
                                }
                            }
                            $canOpenPaymentReview = $canManageTripPayment && $paymentReviewPayload->isNotEmpty() && $paymentActionLabel === 'Review Payment';
                            $canManageRequests = $canManageTripPayment && in_array($statusSlug, ['scheduled', 'confirmed'], true) && $requestPayload->isNotEmpty();
                        @endphp
                        <article class="trip-mobile-item open-trip-card" data-trip-anchor="{{ $trip->id }}">
                            <div style="display:flex; gap:10px; align-items:flex-start;">
                                @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                                    <div style="padding-top: 3px; flex-shrink: 0;" onclick="event.stopPropagation();">
                                        <input type="checkbox" name="ids[]" value="{{ $trip->id }}" class="trip-select-checkbox trip-row-checkbox" form="tripsBulkDeleteForm">
                                    </div>
                                @endif
                                <div style="min-width:0; flex:1;">
                                    <div class="trip-mobile-head">
                                <div style="min-width:0;">
                                    <h2 class="trip-route-title">{{ $routeName }}</h2>
                                    <div class="trip-meta-inline">
                                        <span class="trip-meta-inline-item">
                                            <i class="fa-solid fa-user"></i>
                                            <span>{{ $trip->driver?->name ?: '-' }}</span>
                                        </span>
                                        <span class="trip-meta-inline-item">
                                            <i class="fa-solid fa-route"></i>
                                            <span>{{ $modeText }}</span>
                                        </span>
                                        <span class="trip-meta-inline-item">
                                            <i class="{{ $visibilityIcon }}"></i>
                                            <span>{{ $visibilityText }}</span>
                                        </span>
                                        <span class="trip-meta-inline-item" style="font-family:var(--font-mono,monospace);color:var(--muted-2);">
                                            <i class="fa-solid fa-hashtag"></i>
                                            <span>{{ $tripRef }}</span>
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        class="trip-inline-details-btn open-trip-modal-btn"
                                        data-trip-id="{{ $trip->id }}"
                                        data-trip-ref="{{ $tripRef }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-route-name="{{ $routeName }}"
                                        data-driver-name="{{ $trip->driver?->name ?: '-' }}"
                                        data-driver-id="{{ $trip->driver_id }}"
                                        data-driver-email="{{ $trip->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $trip->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $trip->driver?->whatsapp_digits ?: '' }}"
                                        data-mode="{{ $modeText }}"
                                        data-status="{{ $statusLabel }}"
                                        data-outbound-datetime="{{ $trip->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-return-datetime="{{ $trip->returnTrip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-outbound-route="{{ $directionText }}"
                                        data-return-route="{{ $returnDirectionText }}"
                                        data-fare-label="{{ $fareLabel }}"
                                        data-fare-display="RM {{ number_format($displayFare, 2) }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                                        data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                                        data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                                        data-total-passengers="{{ $passengerCount }}"
                                        data-split-type="{{ $splitType }}"
                                        data-participants-b64="{{ $participantPayloadB64 }}"
                                        data-route-points-b64="{{ $routePointPayloadB64 }}"
                                    >
                                        <i class="fa-regular fa-eye"></i>
                                        <span>View Details</span>
                                    </button>
                                </div>
                                <span class="status-chip status-{{ $badgeStatus }}">{{ $statusLabel }}</span>
                            </div>

                            <div class="trip-detail-grid">
                                <div class="trip-detail-line">
                                    <span class="trip-detail-label">Date &amp; Time</span>
                                    <span class="trip-detail-value">{{ $trip->trip_datetime?->format('d M Y, H:i') ?: '-' }}</span>
                                    <span class="trip-mobile-seat-line">
                                        <i class="fa-solid fa-chair"></i>
                                        {{ $seatsTaken }}/{{ $seatsAvailable }} seats
                                    </span>
                                </div>
                            </div>

                            <div class="trip-bottom-row">
                                <div class="trip-fare-card">
                                    <span class="trip-fare-label">{{ $fareLabel }}</span>
                                    <span class="trip-fare-value">RM {{ number_format($displayFare, 2) }}</span>
                                </div>
                                <div class="trip-actions">
                                    @if($canManageRequests)
                                        <a href="{{ route('trips.requests.index', $trip) }}"
                                           class="trip-action-btn trip-payment-action open-trip-requests-review"
                                           data-route-name="{{ $routeName }}"
                                           data-trip-id="{{ $trip->id }}"
                                           data-trip-status="{{ $statusLabel }}"
                                           data-open-state="{{ $trip->is_open_for_request ? 'Open' : 'Closed' }}"
                                           data-is-open-for-request="{{ $trip->is_open_for_request ? '1' : '0' }}"
                                           data-toggle-url="{{ route('trips.requests.toggle-open', $trip) }}"
                                           data-seats="{{ $seatsTaken }}/{{ $seatsAvailable }}"
                                           data-pickup-name="{{ $pickupName }}"
                                           data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                                           data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                                           data-destination-name="{{ $destinationName }}"
                                           data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                                           data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                                           data-requests-b64="{{ $requestPayloadB64 }}"
                                        >
                                            <i class="fa-solid fa-user-check"></i> Manage Requests
                                        </a>
                                    @elseif($paymentActionLabel)
                                        <a href="{{ $paymentUrl }}" class="trip-action-btn trip-payment-action {{ $canOpenPaymentReview ? 'open-trip-payment-review' : (in_array($paymentActionLabel, ['Pay Now', 'Pending'], true) ? 'open-trip-paynow' : ($paymentActionLabel === 'Receipt' ? 'open-trip-receipts' : '')) }}"
                                           @if($canOpenPaymentReview)
                                               data-route-name="{{ $routeName }}"
                                               data-trip-ids="{{ $paymentReviewTripIds }}"
                                               data-payments-b64="{{ $paymentReviewPayloadB64 }}"
                                           @elseif(in_array($paymentActionLabel, ['Pay Now', 'Pending'], true))
                                               data-route-name="{{ $routeName }}"
                                               data-payments-b64="{{ $payNowPayloadB64 }}"
                                           @elseif($paymentActionLabel === 'Receipt')
                                               data-route-name="{{ $routeName }}"
                                               data-payments-b64="{{ $receiptPayloadB64 }}"
                                           @endif
                                        >
                                            <i class="{{ $paymentActionIcon }}"></i> {{ $paymentActionLabel }}
                                        </a>
                                    @endif
                                    @if($trip->status === 'scheduled' && ($trip->visibility === 'public') && (auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id))
                                        <a href="{{ route('trips.requests.index', $trip) }}"
                                           class="trip-action-btn open-trip-requests-review"
                                           data-route-name="{{ $routeName }}"
                                           data-trip-id="{{ $trip->id }}"
                                           data-trip-status="{{ $statusLabel }}"
                                           data-open-state="{{ $trip->is_open_for_request ? 'Open' : 'Closed' }}"
                                           data-is-open-for-request="{{ $trip->is_open_for_request ? '1' : '0' }}"
                                           data-toggle-url="{{ route('trips.requests.toggle-open', $trip) }}"
                                           data-seats="{{ $seatsTaken }}/{{ $seatsAvailable }}"
                                           data-pickup-name="{{ $pickupName }}"
                                           data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                                           data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                                           data-destination-name="{{ $destinationName }}"
                                           data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                                           data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                                           data-requests-b64="{{ $requestPayloadB64 }}"
                                        >
                                            <i class="fa-solid fa-inbox"></i> Requests
                                        </a>
                                    @endif
                                </div>
                                @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                                    <div class="trip-mobile-owner-actions">
                                            <a href="{{ route('trips.edit', $trip) }}" class="trip-action-btn icon-only" title="Edit trip" aria-label="Edit trip">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            @if($isAdmin || !in_array($trip->status, ['cancelled', 'completed'], true))
                                                <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="trip-action-form" onsubmit="return confirm('Cancel this trip? This will delete the trip and all related records.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="trip-action-btn trip-action-btn-danger icon-only" title="Delete trip" aria-label="Delete trip">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>

                {{-- Desktop table --}}
                <div class="trip-table-wrap">
                    <table class="trip-table">
                        <thead>
                            <tr>
                                <th class="trip-select-cell">
                                    <input type="checkbox" id="selectAllTrips" class="trip-select-checkbox" title="Select all trips on this page">
                                </th>
                                <th>Trip</th>
                                <th>When</th>
                                <th>Visibility</th>
                                <th>Seats</th>
                                <th>Status</th>
                                <th style="text-align:right;">Fare</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($trips as $trip)
                            @php
                                $hasReturn           = (bool) $trip->returnTrip;
                                $pickupName          = $trip->pickup_name ?? 'Pickup';
                                $destinationName     = $trip->destination_name ?? 'Destination';
                                $directionText       = $pickupName . ' → ' . $destinationName;
                                $returnDirectionText = $destinationName . ' → ' . $pickupName;
                                $routeName           = $trip->savedRoute?->route_name ?: $directionText;
                                $modeText            = $hasReturn ? 'Two-Way' : 'One-Way';
                                $visibilityText      = ($trip->visibility ?? 'private') === 'public' ? 'Public Trip' : 'Private Trip';
                                $visibilityIcon      = ($trip->visibility ?? 'private') === 'public' ? 'fa-solid fa-lock-open' : 'fa-solid fa-lock';
                                $combinedFare        = (float) $trip->fare_total + (float) ($trip->returnTrip?->fare_total ?? 0);
                                $myFare              = (float) ($trip->payments->where('user_id', auth()->id())->first()?->amount_due ?? 0)
                                                     + (float) ($trip->returnTrip?->payments?->where('user_id', auth()->id())->first()?->amount_due ?? 0);
                                $showTotalFare       = auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id;
                                $displayFare         = $showTotalFare ? $combinedFare : $myFare;
                                $tripRef             = $trip->trip_ref ?: 'TRP-' . str_pad($trip->id, 5, '0', STR_PAD_LEFT);
                                $pairedTripId        = $trip->returnTrip?->id;
                                $paymentFocusIds     = array_values(array_filter([
                                    (int) $trip->id,
                                    $pairedTripId ? (int) $pairedTripId : null,
                                ]));
                                $paymentFocusQuery   = implode(',', $paymentFocusIds);
                                $pickupShort         = \Illuminate\Support\Str::limit($pickupName, 32, '…');
                                $destinationShort    = \Illuminate\Support\Str::limit($destinationName, 32, '…');
                                $pickupShort         = \Illuminate\Support\Str::limit($pickupName, 24, '...');
                                $destinationShort    = \Illuminate\Support\Str::limit($destinationName, 24, '...');
                                $participantPayload  = $trip->participants
                                    ->map(fn ($participant) => [
                                        'user_id'   => $participant->user_id,
                                        'name'      => $participant->user?->name ?? '-',
                                        'email'     => $participant->user?->email ?? '',
                                        'photo_url' => $participant->user?->profile_photo_url ?? null,
                                        'is_driver' => (bool) $participant->is_driver,
                                    ])
                                    ->values();
                                $participantPayloadB64 = base64_encode($participantPayload->toJson());
                                $routePointPayload   = $trip->passengerRoutePoints
                                    ->filter(fn ($point) => in_array((string) $point->status, ['accepted', 'approved'], true))
                                    ->filter(fn ($point) => ! $point->uses_default_pickup || ! $point->uses_default_dropoff)
                                    ->map(fn ($point) => [
                                        'name'    => $point->user?->name ?? 'Passenger',
                                        'pickup'  => $point->uses_default_pickup ? null : [
                                            'lat'   => $point->pickup_latitude !== null ? (float) $point->pickup_latitude : null,
                                            'lng'   => $point->pickup_longitude !== null ? (float) $point->pickup_longitude : null,
                                            'label' => ($point->user?->name ?? 'Passenger') . ' pickup',
                                        ],
                                        'dropoff' => $point->uses_default_dropoff ? null : [
                                            'lat'   => $point->dropoff_latitude !== null ? (float) $point->dropoff_latitude : null,
                                            'lng'   => $point->dropoff_longitude !== null ? (float) $point->dropoff_longitude : null,
                                            'label' => ($point->user?->name ?? 'Passenger') . ' drop-off',
                                        ],
                                    ])
                                    ->values();
                                $routePointPayloadB64 = base64_encode($routePointPayload->toJson());
                                $passengerCount = (int) $trip->participants->where('is_driver', false)->count();
                                if ($passengerCount === 0 && (int) $trip->participant_count > 0) {
                                    $passengerCount = (int) $trip->participant_count;
                                }
                                $seatsTaken      = $passengerCount;
                                $seatsAvailable  = $trip->seat_limit ?? $trip->available_seats ?? '—';
                                $splitType = ((int) $trip->participant_count > $passengerCount)
                                    ? 'Driver Included in Fare Split'
                                    : 'Driver Excluded from Fare Split';

                                $statusSlug = strtolower((string) $trip->status);
                                $pendingRequestCount = (int) ($trip->joinRequests?->where('status', 'pending')->count() ?? 0);
                                $isFull = $trip->seat_limit !== null && $seatsTaken >= (int) $trip->seat_limit;
                                $badgeStatus = $statusSlug;
                                $statusLabel = match($statusSlug) {
                                    'scheduled' => 'Scheduled',
                                    'recorded' => 'Recorded',
                                    'confirmed' => 'Confirmed',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                    'draft' => 'Draft',
                                    default => \Illuminate\Support\Str::headline((string) $trip->status),
                                };
                                $whenLabel = $trip->trip_datetime?->isToday()
                                    ? $trip->trip_datetime?->format('d M Y')
                                    : ($trip->trip_datetime?->format('d M Y') ?: '-');
                                $paymentUrl = route('payments.index', $paymentFocusQuery ? ['trip_ids' => $paymentFocusQuery] : ['trip_id' => $trip->id]);
                                $paymentStatuses = $trip->payments->pluck('payment_status');
                                if ($trip->returnTrip?->payments) {
                                    $paymentStatuses = $paymentStatuses->merge($trip->returnTrip->payments->pluck('payment_status'));
                                }
                                $reviewTripPayments = collect([$trip, $trip->returnTrip])->filter();
                                $paymentReviewTripIds = $reviewTripPayments->pluck('id')->filter()->implode(', ');
                                $reviewPayments = $reviewTripPayments
                                    ->flatMap(fn ($reviewTrip) => $reviewTrip->payments->map(function ($payment) use ($reviewTrip, $trip) {
                                        $payment->review_leg_label = ((int) $reviewTrip->id === (int) $trip->id) ? 'Outbound' : 'Return';
                                        return $payment;
                                    }))
                                    ->values();
                            $paymentReviewPayload = $reviewPayments
                                    ->map(function ($payment) use ($reviewTripPayments, $routeName, $trip) {
                                        $paymentTrip = $reviewTripPayments->firstWhere('id', $payment->trip_id);
                                        $routePoint = $paymentTrip?->passengerRoutePoints?->first(fn ($point) => (int) $point->user_id === (int) $payment->user_id
                                            && in_array((string) $point->status, ['accepted', 'approved'], true)
                                            && (float) ($point->extra_fee_amount ?? 0) > 0);
                                        $extraFee = (float) ($routePoint?->extra_fee_amount ?? 0);
                                        $amountDue = (float) $payment->amount_due;

                                        return [
                                            'id' => $payment->id,
                                            'passenger' => $payment->user?->name ?: 'Passenger',
                                            'initials' => collect(explode(' ', $payment->user?->name ?: 'P'))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode(''),
                                            'trip' => ($payment->review_leg_label ?? 'Trip') . ' · ' . ($trip->trip_ref ?: 'TRP-' . str_pad($trip->id, 5, '0', STR_PAD_LEFT)),
                                            'amount' => number_format($amountDue, 2),
                                            'base_amount' => number_format(max(0, $amountDue - $extraFee), 2),
                                            'extra_fee' => number_format($extraFee, 2),
                                            'has_extra_fee' => $extraFee > 0,
                                            'status' => (string) $payment->payment_status,
                                            'status_label' => match ((string) $payment->payment_status) {
                                                'pending_confirmation' => 'Awaiting',
                                                'paid' => 'Paid',
                                                'unpaid' => 'Unpaid',
                                                default => \Illuminate\Support\Str::headline((string) $payment->payment_status),
                                            },
                                            'receipt_no' => 'PAY-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                                            'method' => $payment->payment_method ? ucfirst(str_replace('_', ' ', (string) $payment->payment_method)) : 'DuitNow',
                                            'marked_at' => $payment->marked_paid_at?->diffForHumans() ?: '-',
                                            'marked_at_full' => $payment->marked_paid_at?->format('d M Y, H:i') ?: '-',
                                            'confirmed_at' => $payment->confirmed_at?->format('d M Y, H:i') ?: '-',
                                            'route_name' => $routeName,
                                            'driver' => $trip->driver?->name ?: '-',
                                            'driver_email' => $trip->driver?->email ?: '-',
                                            'driver_photo' => ($trip->driver?->profile_photo_url ?? ''),
                                            'driver_bank' => $trip->driver?->payment_bank_name ?: '-',
                                            'driver_account_name' => $trip->driver?->payment_account_name ?: '-',
                                            'driver_account_number' => $trip->driver?->payment_account_number ?: '-',
                                            'driver_duitnow_qr' => $trip->driver?->payment_qr_duitnow_url ?: '',
                                            'driver_tng_qr' => $trip->driver?->payment_qr_tng_url ?: '',
                                            'mark_url' => route('payments.mark-paid', $payment),
                                            'confirm_url' => route('payments.confirm-paid', $payment),
                                            'reject_url' => route('payments.reject-paid', $payment),
                                            'reminder_url' => route('payments.send-reminder', $payment),
                                        ];
                                    })
                                    ->values();
                                $paymentReviewPayloadB64 = base64_encode($paymentReviewPayload->toJson());
                                $currentUserPayments = $reviewPayments->where('user_id', auth()->id())->values();
                                $currentUserPaymentStatuses = $currentUserPayments->pluck('payment_status');
                                $requestPayload = $trip->joinRequests
                                    ->filter(fn ($joinRequest) => in_array((string) $joinRequest->status, ['pending', 'approved'], true))
                                    ->map(function ($joinRequest) use ($trip) {
                                        $routePoint = $joinRequest->routePoint;

                                        return [
                                            'id' => $joinRequest->id,
                                            'passenger' => $joinRequest->user?->name ?: 'Passenger',
                                            'initials' => collect(explode(' ', $joinRequest->user?->name ?: 'P'))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode(''),
                                            'status' => (string) $joinRequest->status,
                                            'requested_at' => $joinRequest->created_at?->diffForHumans() ?: '-',
                                            'note' => $joinRequest->request_note ?: '',
                                            'pickup' => $routePoint ? ($routePoint->uses_default_pickup ? 'Default pickup' : ($routePoint->pickup_name ?: 'Custom pickup')) : 'Default pickup',
                                            'dropoff' => $routePoint ? ($routePoint->uses_default_dropoff ? 'Default drop-off' : ($routePoint->dropoff_name ?: 'Custom drop-off')) : 'Default drop-off',
                                            'pickup_meta' => $routePoint && ! $routePoint->uses_default_pickup
                                                ? trim(collect([
                                                    $routePoint->pickup_distance_km !== null ? number_format((float) $routePoint->pickup_distance_km, 2) . ' km from route' : null,
                                                    $routePoint->requested_pickup_time?->format('d M Y, H:i'),
                                                ])->filter()->implode(' · '))
                                                : "Uses driver's route starting point",
                                            'dropoff_meta' => $routePoint && ! $routePoint->uses_default_dropoff
                                                ? trim(collect([
                                                    $routePoint->dropoff_distance_km !== null ? number_format((float) $routePoint->dropoff_distance_km, 2) . ' km from route' : null,
                                                    $routePoint->detour_distance_km !== null ? 'Detour ' . number_format((float) $routePoint->detour_distance_km, 2) . ' km' : null,
                                                ])->filter()->implode(' · '))
                                                : "Uses driver's route ending point",
                                            'fare' => $routePoint?->extra_fee_amount !== null ? number_format((float) $routePoint->extra_fee_amount, 2) : null,
                                            'detour_km' => $routePoint?->detour_distance_km !== null ? (float) $routePoint->detour_distance_km : null,
                                            'detour_min' => $routePoint?->detour_duration_minutes !== null ? (int) $routePoint->detour_duration_minutes : null,
                                            'deviationKm' => $routePoint?->detour_distance_km !== null ? (float) $routePoint->detour_distance_km : 0,
                                            'name' => $joinRequest->user?->name ?: 'Passenger',
                                            'pickup_point' => [
                                                'lat' => $routePoint && ! $routePoint->uses_default_pickup && $routePoint->pickup_latitude !== null ? (float) $routePoint->pickup_latitude : null,
                                                'lng' => $routePoint && ! $routePoint->uses_default_pickup && $routePoint->pickup_longitude !== null ? (float) $routePoint->pickup_longitude : null,
                                                'label' => $routePoint && ! $routePoint->uses_default_pickup ? (($joinRequest->user?->name ?: 'Passenger') . ' pickup') : null,
                                            ],
                                            'dropoff_point' => [
                                                'lat' => $routePoint && ! $routePoint->uses_default_dropoff && $routePoint->dropoff_latitude !== null ? (float) $routePoint->dropoff_latitude : null,
                                                'lng' => $routePoint && ! $routePoint->uses_default_dropoff && $routePoint->dropoff_longitude !== null ? (float) $routePoint->dropoff_longitude : null,
                                                'label' => $routePoint && ! $routePoint->uses_default_dropoff ? (($joinRequest->user?->name ?: 'Passenger') . ' drop-off') : null,
                                            ],
                                            'pickup_lat' => $routePoint?->pickup_latitude !== null ? (float) $routePoint->pickup_latitude : null,
                                            'pickup_lng' => $routePoint?->pickup_longitude !== null ? (float) $routePoint->pickup_longitude : null,
                                            'dropoff_lat' => $routePoint?->dropoff_latitude !== null ? (float) $routePoint->dropoff_latitude : null,
                                            'dropoff_lng' => $routePoint?->dropoff_longitude !== null ? (float) $routePoint->dropoff_longitude : null,
                                            'fit' => $routePoint?->route_fit_score !== null ? ((int) $routePoint->route_fit_score . '%') : null,
                                            'fit_label' => $routePoint?->route_fit_label ?: 'Driver review',
                                            'respond_url' => route('trips.join-requests.respond', $joinRequest),
                                            'trip' => $trip->trip_ref ?: 'TRP-' . str_pad($trip->id, 5, '0', STR_PAD_LEFT),
                                            'risk_score' => $joinRequest->user?->riskProfile?->risk_score ?? 70,
                                            'risk_level' => $joinRequest->user?->riskProfile?->risk_level ?? 'Moderate Risk',
                                            'risk_reliability' => $joinRequest->user?->riskProfile?->payment_reliability_score ?? 5.0,
                                            'risk_cancelled' => $joinRequest->user?->riskProfile?->cancelled_request_count ?? 0,
                                            'risk_absent' => $joinRequest->user?->riskProfile?->attendance_absent_count ?? 0,
                                            'risk_unpaid' => $joinRequest->user?->riskProfile?->overdue_case_count ?? 0,
                                        ];
                                    })
                                    ->values();
                                $requestPayloadB64 = base64_encode($requestPayload->toJson());
                                $currentUserPaymentPayload = $paymentReviewPayload
                                    ->filter(fn ($payment) => $currentUserPayments->pluck('id')->contains($payment['id']))
                                    ->values();
                                $payNowPayload = $currentUserPaymentPayload
                                    ->filter(fn ($payment) => in_array((string) ($payment['status'] ?? ''), ['unpaid', 'paid', 'pending_confirmation'], true))
                                    ->values();
                                $receiptPayload = $paymentReviewPayload
                                    ->filter(fn ($payment) => (string) ($payment['status'] ?? '') === 'paid' && $currentUserPayments->pluck('id')->contains($payment['id']))
                                    ->values();
                                $receiptPayloadB64 = base64_encode($receiptPayload->toJson());
                                $payNowPayloadB64 = base64_encode($payNowPayload->toJson());
                                $canManageTripPayment = in_array(auth()->user()->role, ['admin', 'driver'], true)
                                    && (auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id);
                                $paymentActionLabel = null;
                                $paymentActionIcon = 'fa-solid fa-credit-card';
                                $paymentTableLabel = null;
                                if (! in_array($statusSlug, ['draft', 'cancelled'], true)) {
                                    if (in_array($statusSlug, ['scheduled', 'confirmed'], true)) {
                                        $paymentActionLabel = null;
                                        $paymentTableLabel = null;
                                    } elseif ($canManageTripPayment && $paymentStatuses->intersect(['unpaid', 'pending_confirmation'])->isNotEmpty()) {
                                        $paymentActionLabel = 'Review Payment';
                                        $paymentTableLabel = 'Review';
                                        $paymentActionIcon = 'fa-solid fa-clipboard-check';
                                    } elseif ($canManageTripPayment && $statusSlug === 'recorded') {
                                        $paymentActionLabel = 'Review Payment';
                                        $paymentTableLabel = 'Review';
                                        $paymentActionIcon = 'fa-solid fa-clipboard-check';
                                    } elseif ($currentUserPaymentStatuses->contains('unpaid')) {
                                        $paymentActionLabel = 'Pay Now';
                                        $paymentTableLabel = 'Pay';
                                        $paymentActionIcon = 'fa-solid fa-credit-card';
                                    } elseif ($currentUserPaymentStatuses->contains('pending_confirmation')) {
                                        $paymentActionLabel = 'Pending';
                                        $paymentTableLabel = 'Pending';
                                        $paymentActionIcon = 'fa-regular fa-clock';
                                    } elseif ($currentUserPaymentStatuses->contains('paid')) {
                                        $paymentActionLabel = 'Receipt';
                                        $paymentTableLabel = 'Receipt';
                                        $paymentActionIcon = 'fa-solid fa-receipt';
                                    }
                                }
                                $canOpenPaymentReview = $canManageTripPayment && $paymentReviewPayload->isNotEmpty() && $paymentActionLabel === 'Review Payment';
                                $canManageRequests = $canManageTripPayment && in_array($statusSlug, ['scheduled', 'confirmed'], true) && $requestPayload->isNotEmpty();
                            @endphp
                            <tr class="open-trip-card" data-trip-anchor="{{ $trip->id }}">
                                <td class="trip-select-cell">
                                    @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                                        <input type="checkbox" name="ids[]" value="{{ $trip->id }}" class="trip-select-checkbox trip-row-checkbox" form="tripsBulkDeleteForm" onclick="event.stopPropagation();">
                                    @endif
                                </td>
                                {{-- Trip column --}}
                                <td>
                                    <div class="trip-route-main trip-route-replacement">{{ $pickupShort }} &rarr; {{ $destinationShort }}</div>
                                    <div class="trip-route-subline trip-route-replacement">
                                        <span><i class="fa-solid fa-hashtag"></i> {{ $tripRef }}</span>
                                        <span><i class="{{ $visibilityIcon }}"></i> {{ $visibilityText }}</span>
                                        <span><i class="{{ $hasReturn ? 'fa-solid fa-repeat' : 'fa-solid fa-route' }}"></i> {{ $hasReturn ? 'Round trip' : $modeText }}</span>
                                        <span><i class="fa-solid fa-location-dot"></i> {{ $trip->savedRoute?->distance_km ? number_format((float) $trip->savedRoute->distance_km, 0) . ' km' : '24 km' }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="trip-inline-details-btn open-trip-modal-btn"
                                        data-trip-id="{{ $trip->id }}"
                                        data-trip-ref="{{ $tripRef }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-route-name="{{ $routeName }}"
                                        data-driver-name="{{ $trip->driver?->name ?: '-' }}"
                                        data-driver-id="{{ $trip->driver_id }}"
                                        data-driver-email="{{ $trip->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $trip->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $trip->driver?->whatsapp_digits ?: '' }}"
                                        data-mode="{{ $modeText }}"
                                        data-status="{{ $statusLabel }}"
                                        data-outbound-datetime="{{ $trip->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-return-datetime="{{ $trip->returnTrip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-outbound-route="{{ $directionText }}"
                                        data-return-route="{{ $returnDirectionText }}"
                                        data-fare-label="Passenger total"
                                        data-fare-display="RM {{ number_format($displayFare, 2) }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                                        data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                                        data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                                        data-total-passengers="{{ $passengerCount }}"
                                        data-split-type="{{ $splitType }}"
                                        data-participants-b64="{{ $participantPayloadB64 }}"
                                        data-route-points-b64="{{ $routePointPayloadB64 }}"
                                    >
                                        <i class="fa-regular fa-eye"></i>
                                        <span>View Details</span>
                                    </button>
                                </td>

                                {{-- When --}}
                                <td>
                                    <div class="trip-table-date">{{ $whenLabel }} <span style="color:var(--muted);padding:0 6px;">&middot;</span> {{ $trip->trip_datetime?->format('H:i') ?: '-' }}</div>
                                </td>

                                {{-- Visibility --}}
                                <td>
                                    @if(($trip->visibility ?? 'private') === 'public')
                                        <span class="trip-visibility-pill public">Public Trip</span>
                                    @else
                                        <span class="trip-visibility-pill private">Private Trip</span>
                                    @endif
                                </td>

                                {{-- Seats --}}
                                <td>
                                    <span class="trip-table-passengers"><i class="fa-solid fa-chair"></i>{{ $seatsTaken }}/{{ $seatsAvailable }}</span>
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="status-chip status-{{ $badgeStatus }}"><span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;"></span>{{ $statusLabel }}</span>
                                </td>

                                {{-- Fare --}}
                                <td style="text-align:right;">
                                    <span class="trip-table-fare">RM {{ number_format($displayFare, 2) }}</span>
                                </td>

                                {{-- Actions --}}
                                <td style="text-align:right;">
                                    <div class="trip-table-actions">
                                        @if($canManageRequests)
                                            <a href="{{ route('trips.requests.index', $trip) }}"
                                               class="btn btn-ghost btn-sm trip-payment-table-action open-trip-requests-review"
                                               data-route-name="{{ $routeName }}"
                                               data-trip-id="{{ $trip->id }}"
                                               data-trip-status="{{ $statusLabel }}"
                                               data-open-state="{{ $trip->is_open_for_request ? 'Open' : 'Closed' }}"
                                               data-is-open-for-request="{{ $trip->is_open_for_request ? '1' : '0' }}"
                                               data-toggle-url="{{ route('trips.requests.toggle-open', $trip) }}"
                                               data-seats="{{ $seatsTaken }}/{{ $seatsAvailable }}"
                                               data-pickup-name="{{ $pickupName }}"
                                               data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                                               data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                                               data-destination-name="{{ $destinationName }}"
                                               data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                                               data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                                               data-requests-b64="{{ $requestPayloadB64 }}"
                                            ><i class="fa-solid fa-inbox" style="font-size:10px;"></i> Requests</a>
                                        @elseif($paymentActionLabel)
                                            <a href="{{ $paymentUrl }}"
                                               class="btn btn-ghost btn-sm trip-payment-table-action {{ $paymentTableLabel === 'Pending' ? 'is-muted' : '' }} {{ $canOpenPaymentReview ? 'open-trip-payment-review' : (in_array($paymentActionLabel, ['Pay Now', 'Pending'], true) ? 'open-trip-paynow' : ($paymentActionLabel === 'Receipt' ? 'open-trip-receipts' : '')) }}"
                                               @if($canOpenPaymentReview)
                                                   data-route-name="{{ $routeName }}"
                                                   data-trip-ids="{{ $paymentReviewTripIds }}"
                                                   data-payments-b64="{{ $paymentReviewPayloadB64 }}"
                                               @elseif(in_array($paymentActionLabel, ['Pay Now', 'Pending'], true))
                                                   data-route-name="{{ $routeName }}"
                                                   data-payments-b64="{{ $payNowPayloadB64 }}"
                                               @elseif($paymentActionLabel === 'Receipt')
                                                   data-route-name="{{ $routeName }}"
                                                   data-payments-b64="{{ $receiptPayloadB64 }}"
                                               @endif
                                            ><i class="{{ $paymentActionIcon }}"></i> {{ $paymentTableLabel }}</a>
                                        @else
                                            <a href="{{ route('trips.show', $trip) }}" class="btn btn-ghost btn-sm">Open <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></a>
                                        @endif
                                        @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                                            <a href="{{ route('trips.edit', $trip) }}" class="trip-row-icon-btn" title="Edit trip" aria-label="Edit trip">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            @if($isAdmin || $trip->status !== 'completed')
                                                <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="trip-row-icon-form" onsubmit="return confirm('Delete this trip? This will remove the trip and all related records.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="trip-row-icon-btn danger" title="Delete trip" aria-label="Delete trip">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            @endif

            @if($trips->hasPages())
                <div class="pagination-wrap">
                    {{ $trips->appends(request()->query())->links() }}
                </div>
            @endif
            </div>{{-- /trips-real-container --}}
            </div>{{-- /relative-wrapper --}}
        </div>
    </div>



    {{-- ── Trip details modal ── --}}
    <div class="trip-payment-review-modal" id="tripPaymentReviewModal" aria-hidden="true">
        <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="tripPaymentReviewTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="tripPaymentReviewTitle">Review payments</h3>
                    <p class="trip-payment-review-sub" id="tripPaymentReviewSub">Confirm passenger payments for this trip.</p>
                </div>
                <button type="button" class="trip-payment-review-close" id="tripPaymentReviewClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list" id="tripPaymentReviewList"></div>
        </div>
    </div>

    <div class="trip-payment-review-modal" id="tripPayNowModal" aria-hidden="true">
        <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="tripPayNowTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="tripPayNowTitle">Pay now</h3>
                    <p class="trip-payment-review-sub" id="tripPayNowSub">Mark your trip payment as paid.</p>
                </div>
                <button type="button" class="trip-payment-review-close" id="tripPayNowClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list" id="tripPayNowList"></div>
        </div>
    </div>

    <div class="trip-payment-review-modal" id="tripReceiptsModal" aria-hidden="true">
        <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="tripReceiptsTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="tripReceiptsTitle">Payment receipts</h3>
                    <p class="trip-payment-review-sub" id="tripReceiptsSub">View and save your trip payment receipts.</p>
                </div>
                <button type="button" class="trip-payment-review-close" id="tripReceiptsClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list" id="tripReceiptsList"></div>
        </div>
    </div>

    <div class="trip-payment-review-modal" id="tripRequestsReviewModal" aria-hidden="true">
        <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="tripRequestsReviewTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="tripRequestsReviewTitle">Manage requests</h3>
                    <p class="trip-payment-review-sub" id="tripRequestsReviewSub">Review passenger requests and custom route preferences.</p>
                </div>
                <button type="button" class="trip-payment-review-close" id="tripRequestsReviewClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list" id="tripRequestsReviewList"></div>
        </div>
    </div>

    <div class="trip-modal" id="tripDetailsModal" aria-hidden="true">
        <div class="trip-modal-card">
            <div class="trip-modal-head">
                <h3 class="trip-modal-title">Trip Details</h3>
                <button type="button" class="trip-modal-close" id="tripDetailsCloseBtn" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-modal-scroll">
                <div class="trip-modal-grid">
                    <div class="trip-details-pairs">
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Trip Ref</span>
                            <span class="trip-modal-value" id="tripModalTripIds">-</span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-regular fa-calendar"></i>Date &amp; Time</span>
                            <span class="trip-modal-value" id="tripModalOutboundTime">-</span>
                        </div>
                    </div>
                    <div class="trip-modal-line">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-road"></i>Route Name</span>
                        <span class="trip-modal-value" id="tripModalRouteName">-</span>
                    </div>
                    <div class="trip-point-cards">
                        <div class="trip-point-card pickup">
                            <span class="trip-point-label" id="tripModalPointALabel"><i class="fa-solid fa-location-dot"></i>Pickup Point</span>
                            <span class="trip-point-value" id="tripModalPickupPoint">-</span>
                        </div>
                        <div class="trip-point-card destination">
                            <span class="trip-point-label" id="tripModalPointBLabel"><i class="fa-solid fa-flag-checkered"></i>Destination Point</span>
                            <span class="trip-point-value" id="tripModalDestinationPoint">-</span>
                        </div>
                    </div>
                    <div class="trip-map-card">
                        <div class="trip-map-head">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-regular fa-map"></i>Route Preview</span>
                            <span class="trip-map-hint">Read-only</span>
                        </div>
                        <div class="trip-modal-map" id="tripModalMap"></div>
                    </div>
                    <div class="trip-modal-line">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Driver</span>
                        <div class="trip-modal-driver">
                            <span class="trip-modal-driver-avatar" id="tripModalDriverAvatar">D</span>
                            <span class="trip-modal-driver-meta">
                                <span class="trip-modal-driver-name" id="tripModalDriver">-</span>
                                <span class="trip-modal-driver-email" id="tripModalDriverEmail">-</span>
                            </span>
                        </div>
                    </div>
                    <div class="trip-modal-line">
                        <div class="trip-passenger-header">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-users"></i>Passengers</span>
                            <span class="trip-passenger-count" id="tripModalPassengerCount">0 passengers</span>
                        </div>
                        <div class="trip-passenger-list" id="tripModalPassengerList"></div>
                    </div>
                    <div class="trip-details-pairs">
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-route"></i>Trip Type</span>
                            <span class="trip-modal-value" id="tripModalMode">-</span>
                            <span class="trip-modal-hint" id="tripModalPairHint" style="display:none;"></span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-regular fa-circle-check"></i>Status</span>
                            <span class="trip-modal-value trip-status-badge" id="tripModalStatus">-</span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user-group"></i>Total Passengers</span>
                            <span class="trip-modal-value" id="tripModalTotalPassengers">-</span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-scale-balanced"></i>Fare Split Type</span>
                            <span class="trip-modal-value" id="tripModalSplitType">-</span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-wallet"></i><span id="tripModalFareLabel">Fare</span></span>
                            <span class="trip-modal-value" id="tripModalFareValue">-</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="trip-contact-bar">
                <p class="trip-contact-text">Having an issue with this trip? Please contact the driver.</p>
                <div class="trip-contact-actions">
                    <a href="#" target="_blank" rel="noopener" class="trip-contact-link whatsapp is-disabled" id="tripModalWhatsapp">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>WhatsApp</span>
                    </a>
                    <a href="#" class="trip-contact-link email is-disabled" id="tripModalEmail">
                        <i class="fa-regular fa-envelope"></i>
                        <span>Driver Email</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Floating Batch Delete Action Bar (1-to-1 matching Payments Floating Bar) --}}
    <form id="tripsBulkDeleteForm" action="{{ route('trips.bulk-destroy') }}" method="POST">
        @csrf
        @method('DELETE')
        <div id="tripsBatchFloatingBar" class="trips-batch-floating-bar" style="display: none;">
            <div class="trips-batch-content" style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 10px;">
                <span id="tripsSelectedCountText" style="font-weight: 800; color: #0f172a; font-size: 14px; font-family: var(--font-ui), sans-serif; white-space: nowrap;">
                    <span id="tripsSelectedCount">0</span> selected
                </span>
                <div style="margin: 0; display: flex; gap: 6px; align-items: center;">
                    <button type="button" id="tripsCancelBatchBtn" class="btn btn-ghost" style="height: 38px; padding: 0 14px; font-size: 13.5px; font-weight: 700; border-radius: 10px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        Cancel
                    </button>
                    <button type="button" id="tripsSelectAllBtn" class="btn btn-ghost" style="height: 38px; padding: 0 14px; font-size: 13.5px; font-weight: 700; border-radius: 10px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        Select All
                    </button>
                    <button type="submit" class="btn btn-danger" style="height: 38px; padding: 0 16px; font-size: 13.5px; font-weight: 800; border-radius: 10px; background: #e11d48; color: #ffffff; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" onclick="return confirm('Are you sure you want to delete all selected trips?');">
                        <i class="fa-solid fa-trash-can"></i> Delete All
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>window.CH_TRIPS = { csrf: @json(csrf_token()) };</script>
    <script src="{{ asset('js/trips-index.js') }}?v={{ filemtime(public_path('js/trips-index.js')) }}"></script>
@endsection
