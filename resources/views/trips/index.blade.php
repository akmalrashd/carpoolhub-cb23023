@extends('layouts.app')

@section('content')
    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    {{-- explore.css supplies the shared .xp-modal-* "Trip details" card styling,
         reused here for the pending-request read-only view instead of duplicating
         those ~150 lines of CSS — loaded first so trips.css still wins on any overlap. --}}
    <link rel="stylesheet" href="{{ asset('css/explore.css') }}?v={{ filemtime(public_path('css/explore.css')) }}">
    {{-- Page styles, extracted to a cacheable static file; link kept at the same position as the <style> block so cascade order is unchanged. --}}
    <link rel="stylesheet" href="{{ asset('css/trips.css') }}?v={{ filemtime(public_path('css/trips.css')) }}">
    @endpush
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>


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
        <div class="trips-page-header-top">
            <div class="trips-page-header-left">
                <p class="trips-eyebrow">{{ $isAdmin ? 'Admin trips' : 'My trips' }}</p>
                <h1 class="trips-h1">{{ $isAdmin ? 'All user trips' : 'Your trips' }}</h1>
                <p class="trips-sub">{{ $isAdmin ? 'Review and manage every trip created by users.' : "All trips you've driven or joined." }}</p>
            </div>
            <div class="trips-header-actions">
                @if(in_array(auth()->user()->role, ['admin', 'driver'], true))
                    <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i>
                        New Trip
                    </a>
                @endif
            </div>
        </div>
        <div class="trips-tabs-row">
            <div class="tabs">
                <span class="tab {{ $activeChip === 'all' ? 'active' : '' }}" data-tab="all">All &middot; {{ $allCount }}</span>
                <span class="tab {{ $activeChip === 'upcoming' ? 'active' : '' }}" data-tab="upcoming">Upcoming &middot; {{ $upcomingCount }}</span>
                <span class="tab {{ $activeChip === 'completed' ? 'active' : '' }}" data-tab="completed">Past &middot; {{ $completedCount }}</span>
                <span class="tab {{ $activeChip === 'draft' ? 'active' : '' }}" data-tab="draft">Drafts &middot; {{ $draftCount }}</span>
                <span class="tab {{ $activeChip === 'cancelled' ? 'active' : '' }}" data-tab="cancelled">Cancelled &middot; {{ $cancelledCount }}</span>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" id="tripsFilterBtn" onclick="(function(){var p=document.getElementById('tripsFilterPanel');p.style.display=p.offsetParent===null?'grid':'none';})()">
                <i class="fa-solid fa-sliders"></i>
                Filter
            </button>
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
                placeholder="Trip ID, route, driver, or passenger"
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


                @php
                    $hasCheckboxes = false;
                    foreach($trips as $t) {
                        if(auth()->user()->role === 'admin' || auth()->id() === $t->driver_id) {
                            $hasCheckboxes = true;
                            break;
                        }
                    }
                @endphp
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
                                @if($hasCheckboxes)
                                <th class="trip-select-cell col-select"></th>
                                @endif
                                <th class="col-trip">Trip</th>
                                <th class="col-when">When</th>
                                <th class="col-vis">Visibility</th>
                                <th class="col-seats">Seats</th>
                                <th class="col-status">Status</th>
                                <th class="col-fare">Fare</th>
                                <th class="col-action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 5; $i++)
                            <tr>
                                @if($hasCheckboxes)
                                <td class="trip-select-cell col-select">
                                    <span class="sk" style="height:18px; width:18px; display:inline-block; border-radius:4px;"></span>
                                </td>
                                @endif
                                <td class="col-trip">
                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                        <span class="sk" style="height:16px; width:180px; display:block; border-radius:6px;"></span>
                                        <span class="sk" style="height:11px; width:110px; display:block; border-radius:4px;"></span>
                                    </div>
                                </td>
                                <td class="col-when">
                                    <span class="sk" style="height:13px; width:110px; display:block; margin:0 auto; border-radius:4px;"></span>
                                </td>
                                <td class="col-vis">
                                    <span class="sk" style="height:22px; width:75px; display:block; margin:0 auto; border-radius:999px;"></span>
                                </td>
                                <td class="col-seats">
                                    <span class="sk" style="height:13px; width:45px; display:block; margin:0 auto; border-radius:4px;"></span>
                                </td>
                                <td class="col-status">
                                    <span class="sk" style="height:24px; width:85px; display:block; margin:0 auto; border-radius:999px;"></span>
                                </td>
                                <td class="col-fare">
                                    <span class="sk" style="height:16px; width:75px; display:inline-block; border-radius:6px;"></span>
                                </td>
                                <td class="col-action">
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
                            $fareLabel           = 'Total Fare';
                            $displayFare         = $combinedFare;
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
                            $seatsTaken      = $passengerCount;
                            $driverIncludedInSplit = (int) $trip->participant_count > $passengerCount;
                            $seatsAvailable  = $trip->seat_limit !== null
                                ? ((int) $trip->seat_limit + ($driverIncludedInSplit ? 1 : 0))
                                : ($trip->available_seats ?? '-');
                            $seatsTakenDisplay = $seatsTaken + ($driverIncludedInSplit ? 1 : 0);
                            $splitType = $driverIncludedInSplit
                                ? 'Driver Included in Fare Split'
                                : 'Driver Excluded from Fare Split';

                            $statusSlug = strtolower((string) $trip->status);
                            $pendingRequestCount = (int) ($trip->joinRequests?->where('status', 'pending')->count() ?? 0);
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
                            // From a requesting passenger's own view (not the driver/admin
                            // managing the trip), a still-pending join request matters more
                            // than the trip's own scheduled/recorded status — overlay it.
                            $isOwnerView = auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id;
                            $myPendingJoinRequest = ! $isOwnerView
                                ? $trip->joinRequests->first(fn ($jr) => (int) $jr->user_id === (int) auth()->id() && $jr->status === 'pending')
                                : null;
                            if ($myPendingJoinRequest) {
                                $badgeStatus = 'pending';
                                $statusLabel = 'Pending';
                            }
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
                                    $participant = $trip->participants->firstWhere('user_id', $joinRequest->user_id);

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
                                        'attendance_status' => $participant?->attendance_status,
                                        'attendance_note' => $participant?->attendance_note,
                                        'remove_url' => route('trips.join-requests.remove', $joinRequest),
                                        'absence_url' => route('trips.join-requests.mark-absent', $joinRequest),
                                        'absence_available' => (bool) ($trip->trip_datetime && now()->gte($trip->trip_datetime->clone()->subMinutes(\App\Services\TripJoinRequestService::ABSENCE_WINDOW_MINUTES))),
                                    ];
                                })
                                ->values();
                            $requestPayloadB64 = base64_encode($requestPayload->toJson());
                            // The current viewer's own join request on THIS trip, if they're
                            // riding as a passenger (works for a driver-role account too, since
                            // this only checks "did I request this trip", not account role).
                            $myJoinRequest = $trip->joinRequests->firstWhere('user_id', auth()->id());
                            $myRequestRow = ($myJoinRequest && in_array((string) $myJoinRequest->status, ['pending', 'approved'], true))
                                ? $requestPayload->firstWhere('id', $myJoinRequest->id)
                                : null;
                            if ($myRequestRow) {
                                $myRequestRow = array_merge($myRequestRow, [
                                    'cancel_url' => route('trips.join-requests.cancel', $myJoinRequest),
                                    'can_cancel' => ! $trip->trip_datetime || ! $trip->trip_datetime->isPast(),
                                ]);
                            }
                            // Pre-selected participants (added directly by the driver at trip
                            // creation) have a TripParticipant + TripPayment row but no
                            // TripJoinRequest at all, so the lookup above misses them entirely.
                            // Only applies to public trips — private trips are direct invites,
                            // not "requests", so there's nothing to show here for those.
                            if (! $myRequestRow && ($trip->visibility ?? 'private') === 'public') {
                                $myParticipant = $trip->participants->first(fn ($p) => (int) $p->user_id === (int) auth()->id() && ! $p->is_driver);
                                if ($myParticipant) {
                                    $myRequestRow = [
                                        'id' => 'participant-' . $myParticipant->id,
                                        'passenger' => auth()->user()->name,
                                        'initials' => collect(explode(' ', auth()->user()->name ?: 'P'))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode(''),
                                        'status' => 'approved',
                                        'requested_at' => $myParticipant->created_at?->diffForHumans() ?: '-',
                                        'note' => '',
                                        'pickup' => 'Default pickup',
                                        'dropoff' => 'Default drop-off',
                                        'pickup_meta' => "Uses driver's route starting point",
                                        'dropoff_meta' => "Uses driver's route ending point",
                                        'fare' => null,
                                        'fit' => null,
                                        'fit_label' => null,
                                        'trip' => $tripRef,
                                        'cancel_url' => route('trips.leave', $trip),
                                        'can_cancel' => ! $trip->trip_datetime || ! $trip->trip_datetime->isPast(),
                                    ];
                                }
                            }
                            // Once the trip has departed, "My Request" no longer has anything
                            // actionable to offer (Cancel is already gated off by can_cancel,
                            // but the trigger button itself should stop showing too).
                            if ($myRequestRow && $trip->trip_datetime && $trip->trip_datetime->isPast()) {
                                $myRequestRow = null;
                            }
                            // Trip-level context for the "My Request" popup (date, route,
                            // fare, seats, other approved passengers' stops for the map
                            // preview) — same regardless of which branch above built the row.
                            if ($myRequestRow) {
                                $approvedStopsForMap = $requestPayload
                                    ->where('status', 'approved')
                                    ->flatMap(fn ($row) => collect([$row['pickup_point'], $row['dropoff_point']])
                                        ->filter(fn ($point) => $point && $point['lat'] !== null && $point['lng'] !== null))
                                    ->values();
                                $myVehicleModel = trim((string) ($trip->driver?->vehicle_model ?? ''));
                                $myVehiclePlate = trim((string) ($trip->driver?->vehicle_plate ?? ''));
                                $myRequestRow = array_merge($myRequestRow, [
                                    'trip_datetime' => $trip->trip_datetime?->format('d M Y, H:i') ?: '-',
                                    'route_name' => $routeName,
                                    'driver_pickup_lat' => $trip->pickup_latitude !== null ? (float) $trip->pickup_latitude : null,
                                    'driver_pickup_lng' => $trip->pickup_longitude !== null ? (float) $trip->pickup_longitude : null,
                                    'driver_dropoff_lat' => $trip->destination_latitude !== null ? (float) $trip->destination_latitude : null,
                                    'driver_dropoff_lng' => $trip->destination_longitude !== null ? (float) $trip->destination_longitude : null,
                                    'fare_per_person' => number_format((float) $trip->fare_per_person, 2),
                                    'seats_available' => is_numeric($seatsAvailable) ? max(0, $seatsAvailable - $seatsTakenDisplay) : '-',
                                    'approved_count' => $passengerCount,
                                    'approved_stops' => $approvedStopsForMap->toArray(),
                                    // Pending-request card (Explore-style read-only view) only
                                    // needs these — kept here so both cards share one payload.
                                    'driver_name' => $trip->driver?->name ?: 'Driver',
                                    'driver_initial' => strtoupper(substr($trip->driver?->name ?? '?', 0, 2)),
                                    'driver_rating' => number_format($trip->driver?->rating ?? 5.0, 2),
                                    'pickup_name' => $pickupName,
                                    'destination_name' => $destinationName,
                                    'vehicle_text' => trim($myVehicleModel . ($myVehicleModel && $myVehiclePlate ? ' · ' : '') . $myVehiclePlate) ?: '-',
                                ]);
                            }
                            $myRequestPayloadB64 = $myRequestRow ? base64_encode(json_encode($myRequestRow)) : null;
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
                            $canManageRequests = $canManageTripPayment && ($trip->visibility ?? 'private') === 'public' && in_array($statusSlug, ['scheduled', 'recorded'], true);
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
                                        data-can-manage="{{ ($isAdmin || auth()->id() === $trip->driver_id) ? '1' : '0' }}"
                                        data-can-delete="{{ ($isAdmin || !in_array($trip->status, ['cancelled', 'completed'], true)) ? '1' : '0' }}"
                                        data-edit-url="{{ route('trips.edit', $trip) }}"
                                        data-delete-url="{{ route('trips.destroy', $trip) }}"
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
                                        {{ $seatsTakenDisplay }}/{{ $seatsAvailable }} seats
                                    </span>
                                </div>
                            </div>

                            <div class="trip-bottom-row">
                                <div class="trip-fare-card">
                                    <span class="trip-fare-label">Trip ID</span>
                                    <span class="trip-fare-value">{{ $tripRef }}</span>
                                </div>
                                <div class="trip-actions trip-actions-filled">
                                    @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                                        @if($canManageRequests)
                                            <button
                                                type="button"
                                                class="trip-action-btn is-filled requests-btn open-trip-requests-review"
                                                title="Manage requests"
                                                data-requests-b64="{{ $requestPayloadB64 }}"
                                                data-route-name="{{ $routeName }}"
                                                data-trip-id="{{ $trip->id }}"
                                                data-trip-ref="{{ $tripRef }}"
                                                data-trip-datetime="{{ $trip->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                                data-open-state="{{ $trip->is_open_for_request ? 'Open' : 'Closed' }}"
                                                data-is-open-for-request="{{ $trip->is_open_for_request ? '1' : '0' }}"
                                                data-seats="{{ is_numeric($seatsAvailable) ? max(0, $seatsAvailable - $seatsTakenDisplay) : '-' }}"
                                                data-trip-status="{{ $statusLabel }}"
                                                data-status-slug="{{ $statusSlug }}"
                                                data-pickup-name="{{ $pickupName }}"
                                                data-destination-name="{{ $destinationName }}"
                                                data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                                                data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                                                data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                                                data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                                                data-toggle-url="{{ route('trips.requests.toggle-open', $trip) }}"
                                            >
                                                <i class="fa-solid fa-inbox"></i> Requests
                                                @if($pendingRequestCount > 0)
                                                    <span class="trip-request-badge">{{ $pendingRequestCount > 9 ? '9+' : $pendingRequestCount }}</span>
                                                @endif
                                            </button>
                                        @endif
                                        <a href="{{ route('trips.edit', $trip) }}" class="trip-action-btn is-filled edit-btn @if($canManageRequests) icon-only @endif" title="Edit trip" aria-label="Edit trip">
                                            <i class="fa-regular fa-pen-to-square"></i> @if(!$canManageRequests) Edit @endif
                                        </a>
                                        @if($isAdmin || !in_array($trip->status, ['cancelled', 'completed'], true))
                                            <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="trip-action-form" onsubmit="return confirmTripCancel(this);">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="reason">
                                                <button type="submit" class="trip-action-btn is-filled delete-btn @if($canManageRequests) icon-only @endif" title="Delete trip" aria-label="Delete trip">
                                                    <i class="fa-regular fa-trash-can"></i> @if(!$canManageRequests) Delete @endif
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        @if($myRequestRow)
                                            <button type="button" class="trip-action-btn is-filled myrequest-btn open-my-request-review" data-request-b64="{{ $myRequestPayloadB64 }}" title="View my request" aria-label="View my request">
                                                <i class="fa-solid fa-inbox"></i> My Request
                                            </button>
                                        @endif
                                        <a href="mailto:{{ $trip->driver->email ?? '' }}" class="trip-action-btn is-filled email-btn @if($myRequestRow) icon-only @endif" title="Email driver" @if(!($trip->driver && $trip->driver->email)) onclick="alert('Email address not specified.'); return false;" @endif>
                                            <i class="fa-regular fa-envelope"></i> @if(!$myRequestRow) Email @endif
                                        </a>
                                        <a href="{{ $trip->driver && $trip->driver->whatsapp_url ? $trip->driver->whatsapp_url : '#' }}" class="trip-action-btn is-filled whatsapp-btn @if($myRequestRow) icon-only @endif" target="_blank" title="Contact WhatsApp" @if(!($trip->driver && $trip->driver->whatsapp_url)) onclick="alert('WhatsApp contact not specified.'); return false;" @endif>
                                            <i class="fa-brands fa-whatsapp"></i> @if(!$myRequestRow) WhatsApp @endif
                                        </a>
                                    @endif
                                </div>
                            </div>
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
                            @if($hasCheckboxes)
                            <th class="trip-select-cell col-select">
                                <input type="checkbox" id="selectAllTrips" class="trip-select-checkbox" title="Select all trips on this page">
                            </th>
                            @endif
                            <th class="col-trip">Trip</th>
                            <th class="col-when">When</th>
                            <th class="col-vis">Visibility</th>
                            <th class="col-seats">Seats</th>
                            <th class="col-status">Status</th>
                            <th class="col-fare">Fare</th>
                            <th class="col-action">Action</th>
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
                                $displayFare         = $combinedFare;
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
                                $seatsTaken      = $passengerCount;
                                $driverIncludedInSplit = (int) $trip->participant_count > $passengerCount;
                                $seatsAvailable  = $trip->seat_limit !== null
                                    ? ((int) $trip->seat_limit + ($driverIncludedInSplit ? 1 : 0))
                                    : ($trip->available_seats ?? '—');
                                $seatsTakenDisplay = $seatsTaken + ($driverIncludedInSplit ? 1 : 0);
                                $splitType = $driverIncludedInSplit
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
                                // From a requesting passenger's own view (not the driver/admin
                                // managing the trip), a still-pending join request matters more
                                // than the trip's own scheduled/recorded status — overlay it.
                                $isOwnerView = auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id;
                                $myPendingJoinRequest = ! $isOwnerView
                                    ? $trip->joinRequests->first(fn ($jr) => (int) $jr->user_id === (int) auth()->id() && $jr->status === 'pending')
                                    : null;
                                if ($myPendingJoinRequest) {
                                    $badgeStatus = 'pending';
                                    $statusLabel = 'Pending';
                                }
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
                                        $participant = $trip->participants->firstWhere('user_id', $joinRequest->user_id);

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
                                            'attendance_status' => $participant?->attendance_status,
                                            'attendance_note' => $participant?->attendance_note,
                                            'remove_url' => route('trips.join-requests.remove', $joinRequest),
                                            'absence_url' => route('trips.join-requests.mark-absent', $joinRequest),
                                            'absence_available' => (bool) ($trip->trip_datetime && now()->gte($trip->trip_datetime->clone()->subMinutes(\App\Services\TripJoinRequestService::ABSENCE_WINDOW_MINUTES))),
                                        ];
                                    })
                                    ->values();
                                $requestPayloadB64 = base64_encode($requestPayload->toJson());
                                $myJoinRequest = $trip->joinRequests->firstWhere('user_id', auth()->id());
                                $myRequestRow = ($myJoinRequest && in_array((string) $myJoinRequest->status, ['pending', 'approved'], true))
                                    ? $requestPayload->firstWhere('id', $myJoinRequest->id)
                                    : null;
                                if ($myRequestRow) {
                                    $myRequestRow = array_merge($myRequestRow, [
                                        'cancel_url' => route('trips.join-requests.cancel', $myJoinRequest),
                                        'can_cancel' => ! $trip->trip_datetime || ! $trip->trip_datetime->isPast(),
                                    ]);
                                }
                                // Pre-selected participants (added directly by the driver at
                                // trip creation) have a TripParticipant + TripPayment row but
                                // no TripJoinRequest at all, so the lookup above misses them.
                                // Only applies to public trips — private trips are direct
                                // invites, not "requests", so there's nothing to show here.
                                if (! $myRequestRow && ($trip->visibility ?? 'private') === 'public') {
                                    $myParticipant = $trip->participants->first(fn ($p) => (int) $p->user_id === (int) auth()->id() && ! $p->is_driver);
                                    if ($myParticipant) {
                                        $myRequestRow = [
                                            'id' => 'participant-' . $myParticipant->id,
                                            'passenger' => auth()->user()->name,
                                            'initials' => collect(explode(' ', auth()->user()->name ?: 'P'))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode(''),
                                            'status' => 'approved',
                                            'requested_at' => $myParticipant->created_at?->diffForHumans() ?: '-',
                                            'note' => '',
                                            'pickup' => 'Default pickup',
                                            'dropoff' => 'Default drop-off',
                                            'pickup_meta' => "Uses driver's route starting point",
                                            'dropoff_meta' => "Uses driver's route ending point",
                                            'fare' => null,
                                            'fit' => null,
                                            'fit_label' => null,
                                            'trip' => $tripRef,
                                            'cancel_url' => route('trips.leave', $trip),
                                            'can_cancel' => ! $trip->trip_datetime || ! $trip->trip_datetime->isPast(),
                                        ];
                                    }
                                }
                                // Once the trip has departed, "My Request" no longer has anything
                                // actionable to offer (Cancel is already gated off by can_cancel,
                                // but the trigger button itself should stop showing too).
                                if ($myRequestRow && $trip->trip_datetime && $trip->trip_datetime->isPast()) {
                                    $myRequestRow = null;
                                }
                                // Trip-level context for the "My Request" popup (date, route,
                                // fare, seats, other approved passengers' stops for the map
                                // preview) — same regardless of which branch above built the row.
                                if ($myRequestRow) {
                                    $approvedStopsForMap = $requestPayload
                                        ->where('status', 'approved')
                                        ->flatMap(fn ($row) => collect([$row['pickup_point'], $row['dropoff_point']])
                                            ->filter(fn ($point) => $point && $point['lat'] !== null && $point['lng'] !== null))
                                        ->values();
                                    $myVehicleModel = trim((string) ($trip->driver?->vehicle_model ?? ''));
                                    $myVehiclePlate = trim((string) ($trip->driver?->vehicle_plate ?? ''));
                                    $myRequestRow = array_merge($myRequestRow, [
                                        'trip_datetime' => $trip->trip_datetime?->format('d M Y, H:i') ?: '-',
                                        'route_name' => $routeName,
                                        'driver_pickup_lat' => $trip->pickup_latitude !== null ? (float) $trip->pickup_latitude : null,
                                        'driver_pickup_lng' => $trip->pickup_longitude !== null ? (float) $trip->pickup_longitude : null,
                                        'driver_dropoff_lat' => $trip->destination_latitude !== null ? (float) $trip->destination_latitude : null,
                                        'driver_dropoff_lng' => $trip->destination_longitude !== null ? (float) $trip->destination_longitude : null,
                                        'fare_per_person' => number_format((float) $trip->fare_per_person, 2),
                                        'seats_available' => is_numeric($seatsAvailable) ? max(0, $seatsAvailable - $seatsTakenDisplay) : '-',
                                        'approved_count' => $passengerCount,
                                        'approved_stops' => $approvedStopsForMap->toArray(),
                                        // Pending-request card (Explore-style read-only view) only
                                        // needs these — kept here so both cards share one payload.
                                        'driver_name' => $trip->driver?->name ?: 'Driver',
                                        'driver_initial' => strtoupper(substr($trip->driver?->name ?? '?', 0, 2)),
                                        'driver_rating' => number_format($trip->driver?->rating ?? 5.0, 2),
                                        'pickup_name' => $pickupName,
                                        'destination_name' => $destinationName,
                                        'vehicle_text' => trim($myVehicleModel . ($myVehicleModel && $myVehiclePlate ? ' · ' : '') . $myVehiclePlate) ?: '-',
                                    ]);
                                }
                                $myRequestPayloadB64 = $myRequestRow ? base64_encode(json_encode($myRequestRow)) : null;
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
                                $canManageRequests = $canManageTripPayment && ($trip->visibility ?? 'private') === 'public' && in_array($statusSlug, ['scheduled', 'recorded'], true);
                            @endphp
                            <tr class="open-trip-card" data-trip-anchor="{{ $trip->id }}">
                                @if($hasCheckboxes)
                                <td class="trip-select-cell col-select">
                                    @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                                        <input type="checkbox" name="ids[]" value="{{ $trip->id }}" class="trip-select-checkbox trip-row-checkbox" form="tripsBulkDeleteForm" onclick="event.stopPropagation();">
                                    @endif
                                </td>
                                @endif
                                {{-- Trip column --}}
                                <td class="col-trip">
                                    <div class="trip-route-main trip-route-replacement">{{ $pickupShort }} &rarr; {{ $destinationShort }}</div>
                                    <div class="trip-route-subline trip-route-replacement">
                                        <span><i class="fa-solid fa-hashtag"></i> {{ $tripRef }}</span>
                                        <span><i class="{{ $visibilityIcon }}"></i> {{ $visibilityText }}</span>
                                        <span><i class="{{ $hasReturn ? 'fa-solid fa-repeat' : 'fa-solid fa-route' }}"></i> {{ $modeText }}</span>
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
                                        data-fare-label="Total Fare"
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
                                        data-can-manage="{{ ($isAdmin || auth()->id() === $trip->driver_id) ? '1' : '0' }}"
                                        data-can-delete="{{ ($isAdmin || !in_array($trip->status, ['cancelled', 'completed'], true)) ? '1' : '0' }}"
                                        data-edit-url="{{ route('trips.edit', $trip) }}"
                                        data-delete-url="{{ route('trips.destroy', $trip) }}"
                                    >
                                        <i class="fa-regular fa-eye"></i>
                                        <span>View Details</span>
                                    </button>
                                </td>

                                {{-- When --}}
                                <td class="col-when">
                                    <div class="trip-table-date">{{ $whenLabel }} <span style="color:var(--muted);padding:0 6px;">&middot;</span> {{ $trip->trip_datetime?->format('H:i') ?: '-' }}</div>
                                </td>

                                {{-- Visibility --}}
                                <td class="col-vis">
                                    @if(($trip->visibility ?? 'private') === 'public')
                                        <span class="trip-visibility-pill public">Public Trip</span>
                                    @else
                                        <span class="trip-visibility-pill private">Private Trip</span>
                                    @endif
                                </td>

                                {{-- Seats --}}
                                <td class="col-seats">
                                    <span class="trip-table-passengers"><i class="fa-solid fa-chair"></i>{{ $seatsTakenDisplay }}/{{ $seatsAvailable }}</span>
                                </td>

                                {{-- Status --}}
                                <td class="col-status">
                                    <span class="status-chip status-{{ $badgeStatus }}">{{ $statusLabel }}</span>
                                </td>

                                {{-- Fare --}}
                                <td class="col-fare">
                                    <span class="trip-table-fare">RM {{ number_format($displayFare, 2) }}</span>
                                </td>

                                {{-- Actions --}}
                                <td class="col-action">
                                    <div class="trip-table-actions" style="justify-content:center;">
                                        @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                                            @if($canManageRequests)
                                                <button
                                                    type="button"
                                                    class="trip-row-icon-btn is-filled requests-btn open-trip-requests-review"
                                                    title="Manage requests"
                                                    aria-label="Manage requests"
                                                    data-requests-b64="{{ $requestPayloadB64 }}"
                                                    data-route-name="{{ $routeName }}"
                                                    data-trip-id="{{ $trip->id }}"
                                                    data-open-state="{{ $trip->is_open_for_request ? 'Open' : 'Closed' }}"
                                                    data-is-open-for-request="{{ $trip->is_open_for_request ? '1' : '0' }}"
                                                    data-seats="{{ is_numeric($seatsAvailable) ? max(0, $seatsAvailable - $seatsTakenDisplay) : '-' }}"
                                                    data-trip-status="{{ $statusLabel }}"
                                                data-status-slug="{{ $statusSlug }}"
                                                    data-pickup-name="{{ $pickupName }}"
                                                    data-destination-name="{{ $destinationName }}"
                                                    data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                                                    data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                                                    data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                                                    data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                                                    data-toggle-url="{{ route('trips.requests.toggle-open', $trip) }}"
                                                >
                                                    <i class="fa-solid fa-inbox"></i>
                                                    @if($pendingRequestCount > 0)
                                                        <span class="trip-request-badge">{{ $pendingRequestCount > 9 ? '9+' : $pendingRequestCount }}</span>
                                                    @endif
                                                </button>
                                            @endif
                                            <a href="{{ route('trips.edit', $trip) }}" class="trip-row-icon-btn is-filled edit-btn" title="Edit trip" aria-label="Edit trip">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            @if($isAdmin || !in_array($trip->status, ['cancelled', 'completed'], true))
                                                <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="trip-row-icon-form" onsubmit="return confirmTripCancel(this);">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="reason">
                                                    <button type="submit" class="trip-row-icon-btn is-filled delete-btn" title="Delete trip" aria-label="Delete trip">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            @if($myRequestRow)
                                                <button type="button" class="trip-row-icon-btn is-filled myrequest-btn open-my-request-review" data-request-b64="{{ $myRequestPayloadB64 }}" title="View my request" aria-label="View my request">
                                                    <i class="fa-solid fa-inbox"></i>
                                                </button>
                                            @endif
                                            <a href="mailto:{{ $trip->driver->email ?? '' }}" class="trip-row-icon-btn is-filled email-btn" title="Email driver" aria-label="Email" @if(!($trip->driver && $trip->driver->email)) onclick="alert('Email address not specified.'); return false;" @endif>
                                                <i class="fa-regular fa-envelope"></i>
                                            </a>
                                            <a href="{{ $trip->driver && $trip->driver->whatsapp_url ? $trip->driver->whatsapp_url : '#' }}" class="trip-row-icon-btn is-filled whatsapp-btn" target="_blank" title="Contact WhatsApp" aria-label="WhatsApp" @if(!($trip->driver && $trip->driver->whatsapp_url)) onclick="alert('WhatsApp contact not specified.'); return false;" @endif>
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            @endif

            </div>{{-- /trips-real-container --}}
            </div>{{-- /relative-wrapper --}}
        </div>

        {{--
            Always emit the wrapper, even with nothing to page. Paging here is an
            AJAX swap, and the swap needs a node that is always present to write
            into — when this was wrapped in @if the pager simply stopped being
            replaced, so it kept highlighting the page you had left and its links
            kept pointing at the previous filter. `.pagination-wrap:empty` hides
            it when there is only one page, hence no whitespace inside the tag.
        --}}
        <div class="pagination-wrap">@if($trips->hasPages()){{ $trips->onEachSide(1)->appends(request()->query())->links() }}@endif</div>
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
                </div>
                <button type="button" class="trip-payment-review-close" id="tripRequestsReviewClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list" id="tripRequestsReviewList"></div>
        </div>
    </div>

    <div class="trip-payment-review-modal" id="tripRejectRequestModal" aria-hidden="true">
        <div class="trip-payment-review-card trip-reject-request-card" role="dialog" aria-modal="true" aria-labelledby="tripRejectRequestTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="tripRejectRequestTitle">Reject Request</h3>
                    <p class="trip-payment-review-sub">State the reason why this join request is rejected.</p>
                </div>
                <button type="button" class="trip-payment-review-close" id="tripRejectRequestCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list">
                <div class="trip-secondary-grid" style="grid-template-columns: repeat(2, minmax(0,1fr));">
                    <div class="trip-secondary-item">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Passenger</span>
                        <span class="trip-modal-value" id="tripRejectRequestPassenger">-</span>
                    </div>
                    <div class="trip-secondary-item">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Trip Ref</span>
                        <span class="trip-modal-value" id="tripRejectRequestTrip">-</span>
                    </div>
                </div>
                <div>
                    <label class="trip-modal-label trip-icon-label trip-reject-reason-label" for="tripRejectRequestReason">
                        <i class="fa-solid fa-triangle-exclamation" style="color:#eab308;"></i>Rejection Reason
                    </label>
                    <textarea
                        class="trip-request-tool trip-reject-reason-input"
                        id="tripRejectRequestReason"
                        rows="4"
                        placeholder="Explain briefly why this request was rejected..."
                        required
                    ></textarea>
                </div>
                <div class="trip-reject-request-actions">
                    <button type="button" class="trip-action-btn" id="tripRejectRequestCancel">Cancel</button>
                    <button type="button" class="trip-payment-review-btn danger trip-reject-request-confirm" id="tripRejectRequestConfirm">
                        <i class="fa-solid fa-xmark"></i> Reject Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Remove-participant reason modal — mirrors the reject-request modal above,
         but targets an already-approved passenger instead of a pending request. --}}
    <div class="trip-payment-review-modal" id="tripRemoveParticipantModal" aria-hidden="true">
        <div class="trip-payment-review-card trip-reject-request-card" role="dialog" aria-modal="true" aria-labelledby="tripRemoveParticipantTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="tripRemoveParticipantTitle">Remove Passenger</h3>
                    <p class="trip-payment-review-sub">State the reason why this passenger is being removed from the trip.</p>
                </div>
                <button type="button" class="trip-payment-review-close" id="tripRemoveParticipantCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list">
                <div class="trip-secondary-grid" style="grid-template-columns: repeat(2, minmax(0,1fr));">
                    <div class="trip-secondary-item">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Passenger</span>
                        <span class="trip-modal-value" id="tripRemoveParticipantPassenger">-</span>
                    </div>
                    <div class="trip-secondary-item">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Trip Ref</span>
                        <span class="trip-modal-value" id="tripRemoveParticipantTrip">-</span>
                    </div>
                </div>
                <div>
                    <label class="trip-modal-label trip-icon-label trip-reject-reason-label" for="tripRemoveParticipantReason">
                        <i class="fa-solid fa-triangle-exclamation" style="color:#eab308;"></i>Removal Reason
                    </label>
                    <textarea
                        class="trip-request-tool trip-reject-reason-input"
                        id="tripRemoveParticipantReason"
                        rows="4"
                        placeholder="Explain briefly why this passenger is being removed..."
                        required
                    ></textarea>
                </div>
                <div class="trip-reject-request-actions">
                    <button type="button" class="trip-action-btn" id="tripRemoveParticipantCancel">Cancel</button>
                    <button type="button" class="trip-payment-review-btn danger trip-reject-request-confirm" id="tripRemoveParticipantConfirm">
                        <i class="fa-solid fa-user-xmark"></i> Remove Passenger
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- "My Request" — the passenger-side counterpart of "Manage requests" above.
         Shows just the viewer's own request on a public trip they don't drive,
         with a Cancel action instead of Reject/Approve/Remove/Absent. --}}
    <div class="trip-payment-review-modal" id="tripMyRequestModal" aria-hidden="true">
        <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="tripMyRequestTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="tripMyRequestTitle">My Request</h3>
                </div>
                <button type="button" class="trip-payment-review-close" id="tripMyRequestClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list" id="tripMyRequestList"></div>
        </div>
    </div>

    {{-- Pending-request read-only view — same "Trip details" card style as the
         Explore page's request modal (reusing its .xp-modal-* CSS), since a
         still-pending request has nothing to manage, just a Cancel action. --}}
    <div class="xp-modal" id="tripPendingRequestModal" aria-hidden="true">
        <div class="xp-modal-card" role="dialog" aria-modal="true" aria-labelledby="tripPendingRequestTitle">
            <div class="xp-modal-head">
                <div>
                    <h3 class="xp-modal-title" id="tripPendingRequestTitle">Trip details</h3>
                    <span class="xp-modal-sub">Waiting for the driver to approve your request.</span>
                </div>
                <button type="button" class="xp-modal-close" id="tripPendingRequestClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="xp-modal-body">
                <div class="xp-modal-driver">
                    <span class="xp-modal-avatar" id="tripPendingRequestDriverAvatar">DR</span>
                    <span>
                        <strong class="xp-driver-name" id="tripPendingRequestDriver">Driver</strong>
                        <span class="xp-driver-rating"><i class="fa-solid fa-star"></i><span id="tripPendingRequestRating">5.00</span></span>
                    </span>
                </div>
                <span class="xp-modal-section-label">Trip details</span>
                <div class="xp-modal-kv">
                    <div class="xp-modal-kv-item"><span>Time</span><strong id="tripPendingRequestTime">-</strong></div>
                    <div class="xp-modal-kv-item"><span>Seats Available</span><strong id="tripPendingRequestSeats">-</strong></div>
                    <div class="xp-modal-kv-item"><span>Fare</span><strong id="tripPendingRequestFare">-</strong></div>
                </div>
                <div class="xp-modal-route">
                    <div class="xp-modal-point">
                        <span class="xp-modal-point-dot"></span>
                        <span><span>Pickup</span><strong id="tripPendingRequestPickup">-</strong></span>
                    </div>
                    <div class="xp-modal-point">
                        <span class="xp-modal-point-dot dest"></span>
                        <span><span>Destination</span><strong id="tripPendingRequestDestination">-</strong></span>
                    </div>
                </div>
                <div class="xp-modal-kv">
                    <div class="xp-modal-kv-item" style="grid-column:1/-1"><span>Vehicle</span><strong id="tripPendingRequestVehicle">-</strong></div>
                </div>
            </div>
            <div class="xp-modal-foot">
                <button type="button" class="xp-modal-join-btn danger" id="tripPendingRequestCancelBtn">
                    <i class="fa-solid fa-ban"></i>
                    Cancel Request
                </button>
            </div>
        </div>
    </div>

    <div class="trip-modal" id="tripDetailsModal" aria-hidden="true">
        <div class="trip-modal-card">
            <div class="trip-modal-head">
                <div class="trip-modal-head-text">
                    <h3 class="trip-modal-title">Trip Details</h3>
                    <span class="trip-status-badge" id="tripModalStatus">-</span>
                </div>
                <button type="button" class="trip-modal-close" id="tripDetailsCloseBtn" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-modal-scroll">
                <div class="trip-modal-grid">
                    <div class="trip-meta-line">
                        <span class="trip-meta-item"><i class="fa-solid fa-hashtag"></i><span id="tripModalTripIds">-</span></span>
                        <span class="trip-meta-dot">&middot;</span>
                        <span class="trip-meta-item"><i class="fa-regular fa-calendar"></i><span id="tripModalOutboundTime">-</span></span>
                        <span class="trip-meta-dot">&middot;</span>
                        <span class="trip-meta-item trip-meta-route"><i class="fa-solid fa-road"></i><span class="trip-meta-route-text" id="tripModalRouteName">-</span></span>
                    </div>

                    <div class="trip-route-card">
                        <div class="trip-modal-map" id="tripModalMap"></div>
                        <div class="trip-route-timeline">
                            <div class="trip-route-point">
                                <span class="trip-route-dot pickup"></span>
                                <span class="trip-route-text">
                                    <span class="trip-route-label" id="tripModalPointALabel">Pickup Point</span>
                                    <span class="trip-route-value" id="tripModalPickupPoint">-</span>
                                </span>
                            </div>
                            <div class="trip-route-point">
                                <span class="trip-route-dot destination"></span>
                                <span class="trip-route-text">
                                    <span class="trip-route-label" id="tripModalPointBLabel">Destination Point</span>
                                    <span class="trip-route-value" id="tripModalDestinationPoint">-</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="trip-driver-card">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Driver</span>
                        <span class="trip-driver-row">
                            <span class="trip-modal-driver-avatar" id="tripModalDriverAvatar">D</span>
                            <span class="trip-modal-driver-meta">
                                <span class="trip-modal-driver-name" id="tripModalDriver">-</span>
                                <span class="trip-modal-driver-email" id="tripModalDriverEmail">-</span>
                            </span>
                        </span>
                    </div>

                    <div class="trip-passenger-card">
                        <div class="trip-passenger-header">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-users"></i>Passengers</span>
                            <span class="trip-passenger-count" id="tripModalPassengerCount">0 passengers</span>
                        </div>
                        <div class="trip-passenger-list" id="tripModalPassengerList"></div>
                    </div>

                    <div class="trip-secondary-grid">
                        <div class="trip-secondary-item">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-route"></i>Trip Type</span>
                            <span class="trip-modal-value" id="tripModalMode">-</span>
                        </div>
                        <div class="trip-secondary-item">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user-group"></i>Total Passengers</span>
                            <span class="trip-modal-value" id="tripModalTotalPassengers">-</span>
                        </div>
                        <div class="trip-secondary-item">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-scale-balanced"></i>Fare Split Type</span>
                            <span class="trip-modal-value" id="tripModalSplitType">-</span>
                        </div>
                    </div>

                    <div class="trip-fare-highlight">
                        <span class="trip-fare-highlight-label"><i class="fa-solid fa-wallet"></i><span id="tripModalFareLabel">Fare</span></span>
                        <span class="trip-fare-highlight-value" id="tripModalFareValue">-</span>
                    </div>
                </div>
            </div>
            <div class="trip-contact-bar">
                <div class="trip-actions-filled" id="tripModalManageActions" style="display:none;">
                    <a href="#" class="trip-action-btn is-filled edit-btn" id="tripModalEditBtn">
                        <i class="fa-regular fa-pen-to-square"></i> Edit
                    </a>
                    <form method="POST" id="tripModalDeleteForm" class="trip-action-form" onsubmit="return confirmTripCancel(this);">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="reason">
                        <button type="submit" class="trip-action-btn is-filled delete-btn">
                            <i class="fa-regular fa-trash-can"></i> Delete
                        </button>
                    </form>
                </div>
                <div class="trip-actions-filled" id="tripModalContactActions" style="display:none;">
                    <a href="#" class="trip-action-btn is-filled email-btn" id="tripModalEmail">
                        <i class="fa-regular fa-envelope"></i> Email
                    </a>
                    <a href="#" target="_blank" rel="noopener" class="trip-action-btn is-filled whatsapp-btn" id="tripModalWhatsapp">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Floating Batch Delete Action Bar (1-to-1 matching Payments Floating Bar) --}}
    <form id="tripsBulkDeleteForm" action="{{ route('trips.bulk-destroy') }}" method="POST" onsubmit="return confirmTripCancel(this, 'Are you sure you want to delete all selected trips?');">
        @csrf
        @method('DELETE')
        <input type="hidden" name="reason">
        <div id="tripsBatchFloatingBar" class="trips-batch-floating-bar" style="display: none;">
            <div class="trips-batch-content" style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 10px;">
                <span id="tripsSelectedCountText" style="font-weight: 800; color: #0f172a; font-size: 14px; font-family: var(--font-ui), sans-serif; white-space: nowrap;">
                    <span id="tripsSelectedCount">0</span> selected
                </span>
                <div style="margin: 0; display: flex; gap: 6px; align-items: center;">
                    <button type="button" id="tripsCancelBatchBtn" class="btn btn-ghost trips-batch-icon-btn" title="Cancel selection" aria-label="Cancel selection" style="border-radius: 10px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <button type="button" id="tripsSelectAllBtn" class="btn btn-ghost trips-batch-icon-btn" title="Select all" aria-label="Select all" style="border-radius: 10px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-check-double"></i>
                    </button>
                    <button type="submit" class="btn btn-danger" style="height: 38px; padding: 0 16px; font-size: 13.5px; font-weight: 800; border-radius: 10px; background: #e11d48; color: #ffffff; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-trash-can"></i> Delete All
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>window.CH_TRIPS = { csrf: @json(csrf_token()) };</script>
    <script src="{{ asset('js/trips-index.js') }}?v={{ filemtime(public_path('js/trips-index.js')) }}"></script>
@endsection
