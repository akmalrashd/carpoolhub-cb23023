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


            <div style="position: relative; min-height: 250px;">
                {{-- Skeleton Loading Container --}}
                <div class="trips-skel-container" id="trips-skel-container">
                {{-- Desktop Table Skeleton --}}
                <div class="trip-table-skel" style="display:none; padding:12px 16px;">
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
                <div class="trip-mobile-skel" style="display:none;">
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

            <div class="trips-real-container" id="trips-real-container">
            {{-- Empty state --}}
            @if($trips->isEmpty())
                <div class="trips-empty">
                    <i class="fa-solid fa-car-side trips-empty-icon"></i>
                    <p class="trips-empty-title">No trips yet</p>
                    <p class="trips-empty-copy">You have no trips matching the current filters.</p>
                    @if(in_array(auth()->user()->role, ['admin', 'driver'], true))
                        <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm" style="margin-top:4px;">
                            <i class="fa-solid fa-plus"></i>
                            Create Your First Trip
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
                                        'driver_photo' => $trip->driver?->profile_photo
                                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($trip->driver->profile_photo)
                                            : '',
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
                        </article>
                    @endforeach
                </div>

                {{-- Desktop table --}}
                <div class="trip-table-wrap">
                    <table class="trip-table">
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
                                            'driver_photo' => $trip->driver?->profile_photo
                                                ? \Illuminate\Support\Facades\Storage::disk('public')->url($trip->driver->profile_photo)
                                                : '',
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
                                {{-- Trip column --}}
                                <td>
                                    <div class="trip-route-main trip-route-replacement">{{ $pickupShort }} &rarr; {{ $destinationShort }}</div>
                                    <div class="trip-route-subline trip-route-replacement">
                                        <span><i class="fa-solid fa-hashtag"></i> {{ $tripRef }}</span>
                                        <span><i class="{{ $hasReturn ? 'fa-solid fa-repeat' : 'fa-solid fa-route' }}"></i> {{ $hasReturn ? 'Round trip' : $modeText }}</span>
                                        <span><i class="fa-solid fa-location-dot"></i> {{ $trip->savedRoute?->distance_km ? number_format((float) $trip->savedRoute->distance_km, 0) . ' km' : '24 km' }}</span>
                                    </div>
                                    <div style="font-weight:700">{{ $pickupShort }} → {{ $destinationShort }}</div>
                                    <div style="font-size:11.5px;color:var(--muted)">{{ $modeText }} &middot; {{ $visibilityText }}</div>
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

            {{-- Pagination --}}
            <div class="pagination-wrap">
                {{ $trips->appends(request()->query())->links() }}
            </div>
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

    <script>
        const showModalSkeleton = (listEl) => {
            if (!listEl) return;
            listEl.innerHTML = `
                <div style="display:flex; flex-direction:column; gap:10px; width:100%; pointer-events:none; opacity:0.85;">
                    <div style="border:1px solid var(--hairline); border-radius:14px; padding:12px; display:grid; gap:10px; background:var(--surface);">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="sk" style="width:34px; height:34px; border-radius:999px;"></span>
                                <div>
                                    <div class="sk" style="height:14px; width:100px; border-radius:4px;"></div>
                                    <div class="sk" style="height:10px; width:120px; border-radius:3px; margin-top:4px;"></div>
                                </div>
                            </div>
                            <span class="sk" style="width:50px; height:18px; border-radius:99px;"></span>
                        </div>
                        <div class="sk" style="height:68px; border-radius:10px;"></div>
                        <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:8px;">
                            <div class="sk" style="height:34px; border-radius:9px;"></div>
                            <div class="sk" style="height:34px; border-radius:9px;"></div>
                        </div>
                    </div>
                </div>
            `;
        };

        // ── Skeleton screen & AJAX Page Loader ──
        (() => {
            const skel = document.getElementById('trips-skel-container');
            const real = document.getElementById('trips-real-container');

            const showSkeleton = () => {
                if (skel && real) {
                    skel.style.display = 'grid';
                    skel.style.opacity = '1';
                    skel.style.pointerEvents = 'auto';
                    real.classList.remove('loaded');
                    real.style.opacity = '0';
                    real.style.display = 'none';
                }
            };

            const hideSkeleton = () => {
                if (skel && real) {
                    real.style.display = '';
                    real.classList.add('loaded');
                    real.style.opacity = '1';
                    skel.style.opacity = '0';
                    skel.style.pointerEvents = 'none';
                    setTimeout(() => {
                        skel.style.display = 'none';
                    }, 200);
                }
            };

            // Run hide on page ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', hideSkeleton);
            } else {
                hideSkeleton();
            }

            // AJAX fetching function
            const fetchPage = async (url) => {
                showSkeleton();
                try {
                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error();
                    const html = await res.text();
                    
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const newReal = doc.getElementById('trips-real-container');
                    const currentReal = document.getElementById('trips-real-container');
                    
                    // Replace active states for chips and tabs (since they are outside the real container)
                    const newChipsRow = doc.querySelector('.trips-chip-row');
                    const currentChipsRow = document.querySelector('.trips-chip-row');
                    if (newChipsRow && currentChipsRow) {
                        currentChipsRow.innerHTML = newChipsRow.innerHTML;
                    }
                    const newTabsRow = doc.querySelector('.tabs');
                    const currentTabsRow = document.querySelector('.tabs');
                    if (newTabsRow && currentTabsRow) {
                        currentTabsRow.innerHTML = newTabsRow.innerHTML;
                    }

                    if (newReal && currentReal) {
                        currentReal.innerHTML = newReal.innerHTML;
                        history.pushState(null, '', url);
                        
                        // Re-bind pagination clicks
                        bindPaginationEvents();
                    }
                } catch (_e) {
                    // Fallback to normal navigation
                    window.location.href = url;
                } finally {
                    hideSkeleton();
                }
            };

            const bindPaginationEvents = () => {
                document.querySelectorAll('.pagination-wrap a').forEach((link) => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        fetchPage(link.href);
                    });
                });
            };

            // Bind click events on chips, tabs, and pagination links via event delegation/listeners
            document.addEventListener('click', (e) => {
                const tab = e.target.closest('.tab[data-tab]');
                if (tab) {
                    e.preventDefault();
                    const key = tab.dataset.tab;
                    const url = new URL(window.location.href);
                    url.searchParams.set('status_filter', key);
                    url.searchParams.delete('page');
                    fetchPage(url.toString());
                    return;
                }

                const chip = e.target.closest('.trips-chip');
                if (chip) {
                    e.preventDefault();
                    fetchPage(chip.href);
                }
            });

            // Form Submit Interceptor
            const filterForm = document.getElementById('tripsFilterPanel');
            if (filterForm) {
                filterForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const formData = new FormData(filterForm);
                    const params = new URLSearchParams(formData);
                    const url = new URL(filterForm.action || window.location.href);
                    for (const [key, val] of params.entries()) {
                        if (val) url.searchParams.set(key, val);
                        else url.searchParams.delete(key);
                    }
                    fetchPage(url.toString());
                });

                let submitTimer = null;
                filterForm.querySelectorAll('input, select').forEach((field) => {
                    field.addEventListener('change', () => {
                        window.clearTimeout(submitTimer);
                        submitTimer = window.setTimeout(() => {
                            filterForm.dispatchEvent(new Event('submit', { cancelable: true }));
                        }, 250);
                    });
                });
            }

            // Bind first load
            bindPaginationEvents();
        })();


        (() => {
            const filterForm = document.querySelector('.trips-filter-form');
            if (!filterForm) return;

            let submitTimer = null;
            const autoSubmit = () => {
                window.clearTimeout(submitTimer);
                submitTimer = window.setTimeout(() => filterForm.requestSubmit(), 250);
            };

            filterForm.querySelectorAll('input, select').forEach((field) => {
                field.addEventListener('change', autoSubmit);
            });
        })();

        (() => {
            const params = new URLSearchParams(window.location.search);
            const focusTrip = String(params.get('focus_trip') || '').trim();
            if (!focusTrip) return;

            const targets = Array.from(document.querySelectorAll('[data-trip-anchor]'))
                .filter((el) => String(el.getAttribute('data-trip-anchor') || '').trim() === focusTrip);
            if (targets.length === 0) return;

            const target = targets.find((el) => el instanceof HTMLElement && el.offsetParent !== null) || targets[0];
            if (!(target instanceof HTMLElement)) return;

            requestAnimationFrame(() => {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                target.classList.add('trip-focus-highlight');
                window.setTimeout(() => target.classList.remove('trip-focus-highlight'), 2200);
            });
        })();

        (() => {
            const modal = document.getElementById('tripPaymentReviewModal');
            const list = document.getElementById('tripPaymentReviewList');
            const sub = document.getElementById('tripPaymentReviewSub');
            const closeBtn = document.getElementById('tripPaymentReviewClose');
            const buttons = document.querySelectorAll('.open-trip-payment-review');
            if (!modal || !list || !closeBtn || buttons.length === 0) return;

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const csrf = @json(csrf_token());
            const decodePayload = (encoded) => {
                try {
                    const bytes = Uint8Array.from(atob(String(encoded || '')), (char) => char.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                } catch (_error) {
                    return [];
                }
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const qrPreviewHtml = (url, label) => {
                const safeUrl = String(url || '').trim();
                return safeUrl
                    ? `<img src="${escapeHtml(safeUrl)}" alt="${escapeHtml(label)}">`
                    : '<span class="trip-paynow-qr-empty">No QR uploaded</span>';
            };
            const resultHtml = (message, isError = false) => `
                <div class="trip-payment-popup-result ${isError ? 'error' : ''}">
                    <span class="trip-payment-popup-icon"><i class="fa-solid ${isError ? 'fa-xmark' : 'fa-check'}"></i></span>
                    <span class="trip-payment-popup-title">${isError ? 'Action failed' : 'Successful'}</span>
                    <span class="trip-payment-popup-message">${escapeHtml(message)}</span>
                </div>
            `;
            let activePayments = [];
            let shouldOpenHistory = false;
            let shouldRefreshOnClose = false;
            const normalizeUrlPath = (url) => {
                try { return new URL(url, window.location.origin).pathname; } catch (_error) { return String(url || ''); }
            };
            const findPaymentForForm = (formEl) => {
                const actionPath = normalizeUrlPath(formEl.action);
                return activePayments.find((payment) => [payment.confirm_url, payment.reject_url, payment.reminder_url]
                    .filter(Boolean)
                    .some((url) => normalizeUrlPath(url) === actionPath));
            };
            const applyPaymentUpdate = (payment, payload) => {
                if (!payment || !payload || !payload.payment_status) return;
                const nowText = new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
                payment.status = payload.payment_status;
                payment.status_label = payload.payment_status === 'paid'
                    ? 'Paid'
                    : (payload.payment_status === 'pending_confirmation' ? 'Awaiting confirmation' : 'Unpaid');
                if (payload.payment_status === 'paid') {
                    payment.confirmed_at = nowText;
                    payment.marked_at = payment.marked_at || nowText;
                    payment.marked_at_full = payment.marked_at_full || nowText;
                }
            };
            const removeSuccessCard = (containerEl, afterRemove = null) => {
                window.setTimeout(() => {
                    containerEl.classList.add('is-removing');
                    window.setTimeout(() => {
                        containerEl.remove();
                        if (typeof afterRemove === 'function') afterRemove();
                    }, 180);
                }, 2500);
            };
            const submitPopupForm = (formEl, containerEl) => {
                const submitBtn = formEl.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Processing';
                }

                return fetch(formEl.action, {
                    method: 'POST',
                    body: new FormData(formEl),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(async (response) => {
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(payload.message || 'The payment action could not be completed.');
                        }
                        const payment = findPaymentForForm(formEl);
                        applyPaymentUpdate(payment, payload);
                        shouldOpenHistory = true;
                        shouldRefreshOnClose = true;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                        containerEl.innerHTML = resultHtml(payload.message || 'Payment updated.');
                        removeSuccessCard(containerEl, () => render(activePayments));
                    })
                    .catch((error) => {
                        containerEl.innerHTML = resultHtml(error.message || 'The payment action could not be completed.', true);
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });
            };
            const form = (action, method, label, classes, icon, extra = '') => `
                <form method="POST" action="${escapeHtml(action)}">
                    <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                    ${method ? `<input type="hidden" name="_method" value="${escapeHtml(method)}">` : ''}
                    ${extra}
                    <button type="submit" class="trip-payment-review-btn ${classes}">
                        <i class="${escapeHtml(icon)}"></i>${escapeHtml(label)}
                    </button>
                </form>
            `;
            const renderActions = (payment) => {
                if (payment.status === 'pending_confirmation') {
                    return `
                        <button type="button"
                            class="trip-payment-review-btn danger js-trip-payment-dispute"
                            data-action="${escapeHtml(payment.reject_url)}"
                            data-passenger="${escapeHtml(payment.passenger)}">
                            Dispute
                        </button>
                        ${form(payment.confirm_url, 'PATCH', 'Confirm', 'confirm', 'fa-solid fa-check')}
                    `;
                }

                return `
                    ${form(payment.reminder_url, '', 'Notify', '', 'fa-regular fa-bell')}
                    ${form(payment.confirm_url, 'PATCH', 'Mark paid', 'warn', 'fa-solid fa-check')}
                `;
            };
            const render = (payments) => {
                const rows = Array.isArray(payments) ? payments : [];
                const activeRows = rows.filter((payment) => payment.status !== 'paid');
                const historyRows = rows.filter((payment) => payment.status === 'paid');

                if (rows.length === 0) {
                    list.innerHTML = '<div class="trip-payment-review-empty">No pending payment records for this trip.</div>';
                    return;
                }

                const activeHtml = activeRows.length
                    ? activeRows.map((payment) => `
                    <article class="trip-payment-review-item">
                        <div class="trip-payment-review-top">
                            <div class="trip-payment-review-person">
                                <span class="trip-payment-review-avatar">${escapeHtml(payment.initials || 'P')}</span>
                                <span>
                                    <span class="trip-payment-review-name">${escapeHtml(payment.passenger)}</span>
                                    <span class="trip-payment-review-route">${escapeHtml(payment.trip)} · ${escapeHtml(payment.method || 'DuitNow')}</span>
                                </span>
                            </div>
                            <span class="trip-payment-review-status">${escapeHtml(payment.status_label || payment.status)}</span>
                        </div>
                        <div class="trip-payment-review-amount">
                            <span>
                                <span>${escapeHtml(payment.method || 'DuitNow')}</span>
                                <strong>RM ${escapeHtml(payment.amount)}</strong>
                            </span>
                            <span class="trip-payment-review-time">${escapeHtml(payment.marked_at || '-')}</span>
                        </div>
                        <div class="trip-payment-review-actions">
                            ${renderActions(payment)}
                        </div>
                    </article>
                `).join('')
                    : (historyRows.length ? '' : '<div class="trip-payment-review-empty">No pending payment records for this trip.</div>');

                const historyHtml = historyRows.length
                    ? `
                        <details class="trip-payment-review-history" ${shouldOpenHistory ? 'open' : ''}>
                            <summary>Confirmed history · ${historyRows.length}</summary>
                            <div class="trip-payment-review-history-list">
                                ${historyRows.map((payment) => `
                                    <div class="trip-payment-review-history-row">
                                        <span>
                                            <strong>${escapeHtml(payment.passenger)}</strong>
                                            <span>${escapeHtml(payment.trip)} · ${escapeHtml(payment.confirmed_at || payment.marked_at || '-')}</span>
                                        </span>
                                        <span class="trip-payment-review-history-amount">RM ${escapeHtml(payment.amount)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </details>
                    `
                    : '';

                list.innerHTML = activeHtml + historyHtml;
            };
            const open = (button) => {
                activePayments = decodePayload(button.dataset.paymentsB64 || '');
                shouldOpenHistory = false;
                shouldRefreshOnClose = false;
                const tripIds = String(button.dataset.tripIds || '').trim();
                sub.textContent = tripIds
                    ? `${button.dataset.routeName || 'Trip'} · Trip IDs ${tripIds}`
                    : (button.dataset.routeName || 'Confirm passenger payments for this trip.');
                showModalSkeleton(list);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    render(activePayments);
                }, 240);
            };
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                if (shouldRefreshOnClose) {
                    window.location.reload();
                }
            };

            buttons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    open(button);
                });
            });
            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
            });
            list.addEventListener('click', (event) => {
                const dispute = event.target.closest('.js-trip-payment-dispute');
                if (!dispute) return;

                const reason = window.prompt(`Reason for disputing ${dispute.dataset.passenger || 'this payment'}?`);
                if (!reason || !reason.trim()) return;

                const rejectForm = document.createElement('form');
                rejectForm.method = 'POST';
                rejectForm.action = dispute.dataset.action;
                rejectForm.innerHTML = `
                    <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="rejection_reason" value="${escapeHtml(reason.trim())}">
                `;
                submitPopupForm(rejectForm, dispute.closest('.trip-payment-review-item') || list);
            });
            list.addEventListener('submit', (event) => {
                const formEl = event.target.closest('form');
                if (!formEl) return;
                event.preventDefault();
                submitPopupForm(formEl, formEl.closest('.trip-payment-review-item') || list);
            });
        })();

        (() => {
            const modal = document.getElementById('tripPayNowModal');
            const list = document.getElementById('tripPayNowList');
            const sub = document.getElementById('tripPayNowSub');
            const closeBtn = document.getElementById('tripPayNowClose');
            const buttons = document.querySelectorAll('.open-trip-paynow');
            if (!modal || !list || !closeBtn || buttons.length === 0) return;

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const csrf = @json(csrf_token());
            const decodePayload = (encoded) => {
                try {
                    const bytes = Uint8Array.from(atob(String(encoded || '')), (char) => char.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                } catch (_error) {
                    return [];
                }
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const qrPreviewHtml = (url, label) => {
                const safeUrl = String(url || '').trim();
                return safeUrl
                    ? `<img src="${escapeHtml(safeUrl)}" alt="${escapeHtml(label)}">`
                    : '<span class="trip-paynow-qr-empty">No QR uploaded</span>';
            };
            const resultHtml = (message, isError = false) => `
                <div class="trip-payment-popup-result ${isError ? 'error' : ''}">
                    <span class="trip-payment-popup-icon"><i class="fa-solid ${isError ? 'fa-xmark' : 'fa-check'}"></i></span>
                    <span class="trip-payment-popup-title">${isError ? 'Action failed' : 'Successful'}</span>
                    <span class="trip-payment-popup-message">${escapeHtml(message)}</span>
                </div>
            `;
            let activePayments = [];
            let shouldOpenHistory = false;
            let shouldRefreshOnClose = false;
            const normalizeUrlPath = (url) => {
                try { return new URL(url, window.location.origin).pathname; } catch (_error) { return String(url || ''); }
            };
            const findPaymentForForm = (formEl) => {
                const actionPath = normalizeUrlPath(formEl.action);
                return activePayments.find((payment) => normalizeUrlPath(payment.mark_url) === actionPath);
            };
            const applyPaymentUpdate = (payment, payload) => {
                if (!payment || !payload || !payload.payment_status) return;
                const nowText = new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
                payment.status = payload.payment_status;
                payment.status_label = payload.payment_status === 'paid'
                    ? 'Paid'
                    : (payload.payment_status === 'pending_confirmation' ? 'Awaiting confirmation' : 'Unpaid');
                payment.marked_at = nowText;
                payment.marked_at_full = nowText;
                if (payload.payment_status === 'paid') {
                    payment.confirmed_at = nowText;
                }
            };
            const removeSuccessCard = (containerEl, afterRemove = null) => {
                window.setTimeout(() => {
                    containerEl.classList.add('is-removing');
                    window.setTimeout(() => {
                        containerEl.remove();
                        if (typeof afterRemove === 'function') afterRemove();
                    }, 180);
                }, 2500);
            };
            const submitPopupForm = (formEl, containerEl) => {
                const submitBtn = formEl.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Processing';
                }

                return fetch(formEl.action, {
                    method: 'POST',
                    body: new FormData(formEl),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(async (response) => {
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(payload.message || 'The payment action could not be completed.');
                        }
                        const payment = findPaymentForForm(formEl);
                        applyPaymentUpdate(payment, payload);
                        shouldOpenHistory = true;
                        shouldRefreshOnClose = true;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                        containerEl.innerHTML = resultHtml(payload.message || 'Payment updated.');
                        removeSuccessCard(containerEl, () => render(activePayments));
                    })
                    .catch((error) => {
                        containerEl.innerHTML = resultHtml(error.message || 'The payment action could not be completed.', true);
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });
            };
            const render = (payments) => {
                const allRows = Array.isArray(payments) ? payments : [];
                const rows = allRows.filter((payment) => payment.status === 'unpaid');
                const awaitingRows = allRows.filter((payment) => payment.status === 'pending_confirmation');
                const historyRows = allRows.filter((payment) => payment.status === 'paid');
                const fareBreakdown = (payment) => payment?.has_extra_fee
                    ? `<span style="display:block;color:#64748b;font-size:12px;">Base RM ${escapeHtml(payment.base_amount || '0.00')} + extra RM ${escapeHtml(payment.extra_fee || '0.00')}</span>`
                    : '';

                const hasStatusRows = awaitingRows.length || historyRows.length;
                const unpaidHtml = rows.length ? rows.map((payment) => `
                    <article class="trip-payment-review-item">
                        <div class="trip-payment-review-top">
                            <div class="trip-payment-review-person">
                                <span class="trip-payment-review-avatar">${escapeHtml(payment.initials || 'P')}</span>
                                <span>
                                    <span class="trip-payment-review-name">${escapeHtml(payment.passenger)}</span>
                                    <span class="trip-payment-review-route">${escapeHtml(payment.trip)} · ${escapeHtml(payment.method || 'DuitNow')}</span>
                                </span>
                            </div>
                            <span class="trip-payment-review-status">Unpaid</span>
                        </div>
                        <div class="trip-payment-review-amount">
                            <span>
                                <span>Amount due</span>
                                <strong>RM ${escapeHtml(payment.amount)}</strong>
                                ${fareBreakdown(payment)}
                            </span>
                        </div>
                        <div class="trip-paynow-driver">
                            <div class="trip-paynow-driver-head">
                                <span class="trip-paynow-driver-avatar">
                                    ${payment.driver_photo ? `<img src="${escapeHtml(payment.driver_photo)}" alt="${escapeHtml(payment.driver || 'Driver')}">` : escapeHtml(String(payment.driver || 'D').trim().charAt(0).toUpperCase() || 'D')}
                                </span>
                                <span>
                                    <span class="trip-paynow-driver-name">${escapeHtml(payment.driver || 'Driver')}</span>
                                    <span class="trip-paynow-driver-email">${escapeHtml(payment.driver_email || '-')}</span>
                                </span>
                            </div>
                            <div class="trip-paynow-driver-fields">
                                <div class="trip-paynow-driver-field">
                                    <span>Bank / Wallet</span>
                                    <strong>${escapeHtml(payment.driver_bank || '-')}</strong>
                                </div>
                                <div class="trip-paynow-driver-field">
                                    <span>Account Holder</span>
                                    <strong>${escapeHtml(payment.driver_account_name || '-')}</strong>
                                </div>
                                <div class="trip-paynow-driver-field">
                                    <span>Account Number</span>
                                    <strong>${escapeHtml(payment.driver_account_number || '-')}</strong>
                                </div>
                            </div>
                            <div class="trip-paynow-qr-grid">
                                <div class="trip-paynow-qr-card">
                                    <span class="trip-paynow-qr-title"><i class="fa-solid fa-qrcode"></i>DuitNow QR</span>
                                    <div class="trip-paynow-qr-preview">${qrPreviewHtml(payment.driver_duitnow_qr, 'DuitNow QR')}</div>
                                </div>
                                <div class="trip-paynow-qr-card">
                                    <span class="trip-paynow-qr-title"><i class="fa-solid fa-qrcode"></i>Touch 'n Go QR</span>
                                    <div class="trip-paynow-qr-preview">${qrPreviewHtml(payment.driver_tng_qr, "Touch 'n Go QR")}</div>
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="${escapeHtml(payment.mark_url)}" class="trip-paynow-form">
                            <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                            <input type="hidden" name="_method" value="PATCH">
                            <div class="trip-paynow-fields">
                                <select class="trip-paynow-input" name="payment_method" required>
                                    <option value="" disabled selected>Select method</option>
                                    <option value="duitnow_qr">DuitNow QR</option>
                                    <option value="bank_account">Bank Account</option>
                                    <option value="digital_wallet">Digital Wallet</option>
                                    <option value="others">Others</option>
                                </select>
                                <input class="trip-paynow-input" type="text" name="remarks" placeholder="Remarks">
                            </div>
                            <button type="submit" class="trip-paynow-submit">Mark as paid</button>
                        </form>
                    </article>
                `).join('') : (hasStatusRows ? '' : '<div class="trip-payment-review-empty">No unpaid payment record for this trip.</div>');

                const awaitingHtml = awaitingRows.length ? `
                    <div class="trip-payment-review-history" style="padding:8px 12px;">
                        ${awaitingRows.map((payment) => `
                            <div class="trip-payment-review-history-row" style="padding:4px 0;border-top:0;align-items:center;">
                                <span>
                                    <strong>${escapeHtml(payment.trip)}</strong>
                                    <span>Pending driver confirmation · ${escapeHtml(payment.marked_at_full || payment.marked_at || '-')}</span>
                                </span>
                                <span class="trip-payment-review-status">Awaiting</span>
                            </div>
                        `).join('')}
                    </div>
                ` : '';

                const historyHtml = historyRows.length ? `
                    <details class="trip-payment-review-history" ${shouldOpenHistory ? 'open' : ''}>
                        <summary>Your payment history · ${historyRows.length}</summary>
                        <div class="trip-payment-review-history-list">
                            ${historyRows.map((payment) => `
                                <button type="button" class="trip-payment-review-history-row js-view-receipt" data-payment='${escapeHtml(JSON.stringify(payment))}' style="width:100%;border:0;background:transparent;text-align:left;cursor:pointer;">
                                    <span>
                                        <strong>${escapeHtml(payment.trip)}</strong>
                                        <span>${escapeHtml(payment.status_label || payment.status)} · ${escapeHtml(payment.marked_at_full || payment.marked_at || '-')}</span>
                                    </span>
                                    <span class="trip-payment-review-history-amount">View receipt<br>RM ${escapeHtml(payment.amount)}${fareBreakdown(payment)}</span>
                                </button>
                            `).join('')}
                        </div>
                    </details>
                ` : '';

                list.innerHTML = unpaidHtml + awaitingHtml + historyHtml;
            };
            const open = (button) => {
                activePayments = decodePayload(button.dataset.paymentsB64 || '');
                shouldOpenHistory = false;
                shouldRefreshOnClose = false;
                sub.textContent = button.dataset.routeName || 'Mark your trip payment as paid.';
                showModalSkeleton(list);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    render(activePayments);
                }, 240);
            };
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                if (shouldRefreshOnClose) {
                    window.location.reload();
                }
            };

            buttons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    open(button);
                });
            });
            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
            });
            list.addEventListener('submit', (event) => {
                const formEl = event.target.closest('form');
                if (!formEl) return;
                event.preventDefault();
                submitPopupForm(formEl, formEl.closest('.trip-payment-review-item') || list);
            });
        })();

        (() => {
            const modal = document.getElementById('tripReceiptsModal');
            const list = document.getElementById('tripReceiptsList');
            const sub = document.getElementById('tripReceiptsSub');
            const closeBtn = document.getElementById('tripReceiptsClose');
            const buttons = document.querySelectorAll('.open-trip-receipts');
            if (!modal || !list || !closeBtn) return;

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const decodePayload = (encoded) => {
                try {
                    const bytes = Uint8Array.from(atob(String(encoded || '')), (char) => char.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                } catch (_error) {
                    return [];
                }
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const receiptHtml = (payment) => `
                <article class="trip-receipt-card" id="tripReceiptPrintable">
                    <div class="trip-receipt-head">
                        <span>
                            <h4 class="trip-receipt-title">CarpoolHub Receipt</h4>
                            <span class="trip-receipt-id">Receipt ${escapeHtml(payment.receipt_no || ('PAY-' + payment.id))} · ${escapeHtml(payment.trip)}</span>
                        </span>
                        <span class="trip-receipt-status ${payment.status === 'paid' ? 'paid' : ''}">${escapeHtml(payment.status_label || payment.status)}</span>
                    </div>
                    <div class="trip-receipt-total">
                        <span>Amount paid</span>
                        <strong>RM ${escapeHtml(payment.amount)}</strong>
                        ${payment?.has_extra_fee ? `<span style="display:block;color:#64748b;font-size:12px;">Base RM ${escapeHtml(payment.base_amount || '0.00')} + custom extra RM ${escapeHtml(payment.extra_fee || '0.00')}</span>` : ''}
                    </div>
                    <div class="trip-receipt-lines">
                        <div class="trip-receipt-line"><span>Passenger</span><strong>${escapeHtml(payment.passenger)}</strong></div>
                        <div class="trip-receipt-line"><span>Driver</span><strong>${escapeHtml(payment.driver || '-')}</strong></div>
                        <div class="trip-receipt-line"><span>Route</span><strong>${escapeHtml(payment.route_name || '-')}</strong></div>
                        <div class="trip-receipt-line"><span>Method</span><strong>${escapeHtml(payment.method || '-')}</strong></div>
                        <div class="trip-receipt-line"><span>Marked paid</span><strong>${escapeHtml(payment.marked_at_full || payment.marked_at || '-')}</strong></div>
                        <div class="trip-receipt-line"><span>Confirmed</span><strong>${escapeHtml(payment.confirmed_at || '-')}</strong></div>
                    </div>
                    <div class="trip-receipt-actions">
                        <button type="button" class="trip-payment-review-btn js-print-receipt"><i class="fa-solid fa-print"></i>Print / Save PDF</button>
                        <button type="button" class="trip-payment-review-btn confirm js-back-receipts"><i class="fa-solid fa-list"></i>Back</button>
                    </div>
                </article>
            `;
            const renderList = (payments) => {
                const rows = Array.isArray(payments) ? payments : [];
                if (!rows.length) {
                    list.innerHTML = '<div class="trip-payment-review-empty">No receipt available for this trip yet.</div>';
                    return;
                }

                list.innerHTML = rows.map((payment) => `
                    <button type="button" class="trip-payment-review-item js-receipt-row" data-payment='${escapeHtml(JSON.stringify(payment))}' style="text-align:left;cursor:pointer;">
                        <div class="trip-payment-review-top">
                            <div class="trip-payment-review-person">
                                <span class="trip-payment-review-avatar">${escapeHtml(payment.initials || 'P')}</span>
                                <span>
                                    <span class="trip-payment-review-name">${escapeHtml(payment.trip)}</span>
                                    <span class="trip-payment-review-route">${escapeHtml(payment.method || '-')} · ${escapeHtml(payment.marked_at_full || payment.marked_at || '-')}</span>
                                </span>
                            </div>
                            <span class="trip-payment-review-status">${escapeHtml(payment.status_label || payment.status)}</span>
                        </div>
                        <div class="trip-payment-review-amount">
                            <span><span>Amount</span><strong>RM ${escapeHtml(payment.amount)}</strong></span>
                            <span class="trip-payment-review-time">View receipt</span>
                        </div>
                    </button>
                `).join('');
            };
            let activeRows = [];
            const open = (payments, routeName = '') => {
                document.querySelectorAll('.trip-payment-review-modal.is-open').forEach((openModal) => {
                    if (openModal !== modal) {
                        openModal.classList.remove('is-open');
                        openModal.setAttribute('aria-hidden', 'true');
                    }
                });
                activeRows = Array.isArray(payments) ? payments : [];
                sub.textContent = routeName || 'View and save your trip payment receipts.';
                showModalSkeleton(list);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    renderList(activeRows);
                }, 240);
            };
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            buttons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    open(decodePayload(button.dataset.paymentsB64 || ''), button.dataset.routeName || '');
                });
            });
            document.addEventListener('click', (event) => {
                const fromHistory = event.target.closest('.js-view-receipt');
                if (fromHistory) {
                    event.preventDefault();
                    event.stopPropagation();
                    try {
                        const payment = JSON.parse(fromHistory.dataset.payment || '{}');
                        open([payment], 'Payment receipt');
                        list.innerHTML = receiptHtml(payment);
                    } catch (_error) {}
                    return;
                }
                const row = event.target.closest('.js-receipt-row');
                if (row) {
                    try { list.innerHTML = receiptHtml(JSON.parse(row.dataset.payment || '{}')); } catch (_error) {}
                    return;
                }
                if (event.target.closest('.js-back-receipts')) {
                    renderList(activeRows);
                    return;
                }
                if (event.target.closest('.js-print-receipt')) {
                    const receipt = event.target.closest('.trip-receipt-card');
                    if (!receipt) return;
                    const printable = receipt.cloneNode(true);
                    printable.querySelectorAll('.trip-receipt-actions').forEach((node) => node.remove());
                    const iframe = document.createElement('iframe');
                    iframe.style.position = 'fixed';
                    iframe.style.right = '0';
                    iframe.style.bottom = '0';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = '0';
                    iframe.setAttribute('aria-hidden', 'true');
                    document.body.appendChild(iframe);
                    const printDoc = iframe.contentWindow?.document;
                    if (!printDoc) {
                        iframe.remove();
                        return;
                    }
                    printDoc.open();
                    printDoc.write(`
                        <!doctype html>
                        <html>
                        <head>
                            <title>CarpoolHub receipt</title>
                            <style>
                                @page{size:auto;margin:14mm}
                                *{box-sizing:border-box}
                                body{font-family:Inter,Arial,sans-serif;margin:0;padding:28px;background:#f7f2e7;color:#0f172a}
                                .trip-receipt-card{max-width:640px;margin:0 auto;background:#fff;border:1px solid #e4d8bf;border-radius:18px;padding:22px}
                                .trip-receipt-head{display:flex;justify-content:space-between;gap:16px;border-bottom:1px solid #eadfc8;padding-bottom:16px;margin-bottom:16px}
                                .trip-receipt-title{margin:0;font-size:24px;font-weight:900}
                                .trip-receipt-id,.trip-receipt-line span,.trip-receipt-total span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
                                .trip-receipt-status{border:1px solid #22c55e;border-radius:999px;padding:7px 12px;color:#047857;font-weight:800;height:max-content}
                                .trip-receipt-total{background:#faf7ef;border-radius:14px;padding:16px;margin-bottom:14px}
                                .trip-receipt-total strong{font-size:34px;font-weight:950}
                                .trip-receipt-line{display:flex;justify-content:space-between;gap:16px;border-top:1px solid #f0e5cf;padding:12px 0}
                                .trip-receipt-line strong{text-align:right}
                                .trip-receipt-actions{display:none}
                                @media print{body{background:#fff;padding:0}.trip-receipt-card{box-shadow:none}}
                            </style>
                        </head>
                        <body>${printable.outerHTML}</body>
                        </html>
                    `);
                    printDoc.close();
                    setTimeout(() => {
                        iframe.contentWindow?.focus();
                        iframe.contentWindow?.print();
                        setTimeout(() => iframe.remove(), 500);
                    }, 100);
                }
            });
            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
            });
        })();

        (() => {
            const modal = document.getElementById('tripRequestsReviewModal');
            const list = document.getElementById('tripRequestsReviewList');
            const sub = document.getElementById('tripRequestsReviewSub');
            const closeBtn = document.getElementById('tripRequestsReviewClose');
            const buttons = document.querySelectorAll('.open-trip-requests-review');
            if (!modal || !list || !closeBtn || buttons.length === 0) return;

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const csrf = @json(csrf_token());
            const decodePayload = (encoded) => {
                try {
                    const bytes = Uint8Array.from(atob(String(encoded || '')), (char) => char.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                } catch (_error) {
                    return [];
                }
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const responseForm = (request, action, label, classes, icon) => `
                <form method="POST" action="${escapeHtml(request.respond_url)}">
                    <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="action" value="${escapeHtml(action)}">
                    <input type="hidden" name="response_note" value="">
                    <button type="submit" class="trip-payment-review-btn ${classes}">
                        <i class="${escapeHtml(icon)}"></i>${escapeHtml(label)}
                    </button>
                </form>
            `;
            const requestToggleForm = (button) => {
                const isOpen = String(button.dataset.isOpenForRequest || '') === '1';
                const nextValue = isOpen ? '0' : '1';
                const label = isOpen ? 'Close requests' : 'Open requests';
                const icon = isOpen ? 'fa-solid fa-lock' : 'fa-solid fa-lock-open';
                const classes = isOpen ? 'close' : 'open';

                return `
                    <form method="POST" action="${escapeHtml(button.dataset.toggleUrl || '')}" class="trip-request-toggle-form" data-request-toggle-form>
                        <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                        <input type="hidden" name="_method" value="PATCH">
                        <input type="hidden" name="is_open_for_request" value="${nextValue}">
                        <button type="submit" class="trip-request-toggle-btn ${classes}">
                            <i class="${icon}"></i>${label}
                        </button>
                    </form>
                `;
            };
            let requestMap = null;
            let activeRequestButton = null;
            const num = (value) => {
                const parsed = Number.parseFloat(String(value ?? '').trim());
                return Number.isFinite(parsed) ? parsed : null;
            };
            const drawMap = async (button, requests) => {
                const mapEl = document.getElementById('tripRequestsInlineMap');
                const stopsEl = document.getElementById('tripRequestsInlineStops');
                const metricsEl = document.getElementById('tripRequestsInlineMetrics');
                const googleMapsLink = document.getElementById('tripRequestsInlineGoogleMaps');
                if (!mapEl || typeof L === 'undefined') return;
                if (requestMap) {
                    requestMap.remove();
                    requestMap = null;
                }
                const toLatLng = (raw) => {
                    const lat = num(raw?.lat);
                    const lng = num(raw?.lng);
                    return lat !== null && lng !== null ? L.latLng(lat, lng) : null;
                };
                const driverPickup = L.latLng(num(button.dataset.pickupLat), num(button.dataset.pickupLng));
                const driverDropoff = L.latLng(num(button.dataset.destinationLat), num(button.dataset.destinationLng));
                if (!Number.isFinite(driverPickup.lat) || !Number.isFinite(driverPickup.lng) || !Number.isFinite(driverDropoff.lat) || !Number.isFinite(driverDropoff.lng)) {
                    mapEl.innerHTML = '<div class="trip-payment-review-empty">No coordinates available for route preview.</div>';
                    return;
                }
                const samePoint = (a, b) => Math.abs(a.lat - b.lat) < 0.00001 && Math.abs(a.lng - b.lng) < 0.00001;
                const uniqueWaypoints = (points) => points.reduce((items, point) => {
                    if (!point) return items;
                    if (!items.length || !samePoint(items[items.length - 1], point)) items.push(point);
                    return items;
                }, []);
                const permutations = (items) => {
                    if (items.length <= 1) return [items];
                    return items.flatMap((item, index) => {
                        const remaining = items.filter((_, remainingIndex) => remainingIndex !== index);
                        return permutations(remaining).map((ordered) => [item, ...ordered]);
                    });
                };
                const validPassengerOrder = (items) => {
                    const grouped = items.reduce((groups, item, index) => {
                        if (!groups[item.requestId]) groups[item.requestId] = {};
                        groups[item.requestId][item.kind] = index;
                        return groups;
                    }, {});
                    return Object.values(grouped).every((group) => group.pickup === undefined || group.dropoff === undefined || group.pickup < group.dropoff);
                };
                const straightDistanceKm = (points) => {
                    let total = 0;
                    for (let index = 0; index < points.length - 1; index += 1) total += points[index].distanceTo(points[index + 1]) / 1000;
                    return total;
                };
                const fetchRoute = async (points) => {
                    const waypoints = uniqueWaypoints(points);
                    if (waypoints.length < 2) return { points: waypoints, distanceKm: 0, durationMinutes: 0 };
                    const coordinates = waypoints.map((point) => `${encodeURIComponent(point.lng)},${encodeURIComponent(point.lat)}`).join(';');
                    const url = `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`;
                    try {
                        const response = await fetch(url);
                        if (!response.ok) throw new Error('route');
                        const data = await response.json();
                        const route = data?.routes?.[0];
                        const routePoints = (route?.geometry?.coordinates ?? [])
                            .map((coord) => L.latLng(Number(coord[1]), Number(coord[0])))
                            .filter((coord) => Number.isFinite(coord.lat) && Number.isFinite(coord.lng));
                        return {
                            points: routePoints.length > 1 ? routePoints : waypoints,
                            distanceKm: route?.distance ? Number(route.distance) / 1000 : straightDistanceKm(waypoints),
                            durationMinutes: route?.duration ? Number(route.duration) / 60 : null,
                        };
                    } catch (_error) {
                        return { points: waypoints, distanceKm: straightDistanceKm(waypoints), durationMinutes: null };
                    }
                };
                const passengerPalette = ['#7c3aed', '#0f766e', '#dc2626', '#2563eb', '#9333ea', '#c2410c', '#0891b2', '#be123c'];
                const colorForRequest = (requestId) => passengerPalette[Math.abs(Number.parseInt(String(requestId || 0), 10) || 0) % passengerPalette.length];
                const stops = (requests || []).flatMap((request) => {
                    const pickup = toLatLng(request.pickup_point);
                    const dropoff = toLatLng(request.dropoff_point);
                    const color = colorForRequest(request.id);
                    return [
                        pickup ? { requestId: request.id, kind: 'pickup', point: pickup, label: request.pickup_point?.label || `${request.passenger} pickup`, status: request.status, color } : null,
                        dropoff ? { requestId: request.id, kind: 'dropoff', point: dropoff, label: request.dropoff_point?.label || `${request.passenger} drop-off`, status: request.status, color } : null,
                    ].filter(Boolean);
                }).map((stop, index) => ({ ...stop, marker: String(index + 1) }));
                const visibleRequestIds = new Set((requests || []).map((request) => String(request.id)));
                const visibleStops = () => stops.filter((stop) => visibleRequestIds.has(String(stop.requestId)));
                const shortestMiddleRoute = async (activeStops) => {
                    const usableStops = activeStops.filter((stop) => stop.point);
                    const orders = usableStops.length <= 7 ? permutations(usableStops).filter(validPassengerOrder) : [usableStops];
                    const candidates = orders.length ? orders : [[]];
                    const routes = await Promise.all(candidates.map(async (order) => ({
                        ...(await fetchRoute([driverPickup, ...order.map((item) => item.point), driverDropoff])),
                        order,
                    })));
                    return routes.reduce((best, route) => (!best || route.distanceKm < best.distanceKm ? route : best), null);
                };

                requestMap = L.map(mapEl, { scrollWheelZoom: false, zoomControl: true, attributionControl: false });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(requestMap);
                const markerRefs = new Map();
                const numberedIcon = (className, marker, fill = '') => L.divIcon({
                    className: '',
                    html: `<span class="summary-pin-icon ${className}" data-summary-marker="${marker}" style="${fill ? `--pin-fill:${fill}` : ''}">${marker}</span>`,
                    iconSize: [26, 26],
                    iconAnchor: [13, 13],
                    tooltipAnchor: [0, -14],
                });
                const addPoint = (point, className, label, marker, fill = '') => {
                    const mapMarker = L.marker(point, { icon: numberedIcon(className, marker, fill), title: label })
                        .addTo(requestMap)
                        .bindTooltip(label, { permanent: false, direction: 'top', offset: [0, -10] });
                    markerRefs.set(marker, mapMarker);
                };
                const renderStopList = () => {
                    if (!stopsEl) return;
                    const rows = [
                        { marker: 'A', label: 'Driver Pickup', meta: 'Driver Point', className: 'driver-pickup' },
                        ...stops.map((stop) => ({
                            marker: stop.marker,
                            label: stop.label,
                            meta: `${stop.kind} - ${stop.status}`,
                            className: `${stop.status} ${stop.kind}`,
                            color: stop.color,
                            requestId: String(stop.requestId),
                        })),
                        { marker: 'B', label: 'Driver Drop-off', meta: 'Driver Point', className: 'driver-dropoff' },
                    ];
                    stopsEl.innerHTML = rows.map((row) => `
                        <div class="summary-stop-item ${row.requestId && !visibleRequestIds.has(row.requestId) ? 'is-hidden' : ''}" data-summary-stop="${row.marker}">
                            <span class="summary-stop-marker ${row.className}" style="${row.color ? `--pin-fill:${row.color}` : ''}">${row.marker}</span>
                            <span class="summary-stop-text"><span class="summary-stop-label">${escapeHtml(row.label)}</span><span class="summary-stop-meta">${escapeHtml(row.meta)}</span></span>
                            ${row.requestId ? `<button type="button" class="summary-stop-toggle ${visibleRequestIds.has(row.requestId) ? '' : 'is-off'}" data-summary-toggle="${row.requestId}"><i class="fas ${visibleRequestIds.has(row.requestId) ? 'fa-eye' : 'fa-eye-slash'}"></i></button>` : ''}
                        </div>
                    `).join('');
                    stopsEl.querySelectorAll('[data-summary-toggle]').forEach((toggle) => {
                        toggle.addEventListener('click', async (event) => {
                            event.stopPropagation();
                            const id = String(toggle.dataset.summaryToggle || '');
                            visibleRequestIds.has(id) ? visibleRequestIds.delete(id) : visibleRequestIds.add(id);
                            await redraw();
                        });
                    });
                };
                const formatKm = (value) => `${(Number(value) || 0).toFixed(2)} km`;
                const formatMinutes = (value) => value === null || value === undefined || !Number.isFinite(Number(value)) ? '-' : `${Math.max(1, Math.round(Number(value)))} min`;
                const formatMoney = (value) => `RM ${(Number(value) || 0).toFixed(2)}`;
                const renderMetrics = (originalRoute, suggestedRoute, activeStops) => {
                    if (!metricsEl) return;
                    const activeRequestIds = new Set((activeStops || []).map((stop) => String(stop.requestId)));
                    const activeRequests = (requests || []).filter((request) => activeRequestIds.has(String(request.id)));
                    const originalKm = Number(originalRoute?.distanceKm) || 0;
                    const suggestedKm = Number(suggestedRoute?.distanceKm) || originalKm;
                    const extraKm = Math.max(0, suggestedKm - originalKm);
                    const originalMinutes = originalRoute?.durationMinutes;
                    const suggestedMinutes = suggestedRoute?.durationMinutes;
                    const extraMinutes = originalMinutes !== null && suggestedMinutes !== null ? Math.max(0, Number(suggestedMinutes) - Number(originalMinutes)) : null;
                    const totalFare = activeRequests.reduce((sum, request) => sum + (Number(request.fare) || 0), 0);
                    const customStops = activeStops.length;
                    const approvedCount = activeRequests.filter((request) => request.status === 'approved').length;
                    const pendingCount = activeRequests.filter((request) => request.status === 'pending').length;
                    const totalDeviation = activeRequests.reduce((sum, request) => sum + (Number(request.deviationKm) || 0), 0);
                    metricsEl.innerHTML = `
                        <div class="summary-metric-item"><span class="summary-metric-label">Route distance</span><span class="summary-metric-value">${formatKm(suggestedKm)}</span><span class="summary-metric-meta">Original ${formatKm(originalKm)} / extra ${formatKm(extraKm)}</span></div>
                        <div class="summary-metric-item"><span class="summary-metric-label">Estimated time</span><span class="summary-metric-value">${formatMinutes(suggestedMinutes)}</span><span class="summary-metric-meta">Original ${formatMinutes(originalMinutes)} / extra ${formatMinutes(extraMinutes)}</span></div>
                        <div class="summary-metric-item"><span class="summary-metric-label">Extra fees</span><span class="summary-metric-value">${formatMoney(totalFare)}</span><span class="summary-metric-meta">${approvedCount} approved / ${pendingCount} pending / ${customStops} custom stops / ${formatKm(totalDeviation)} deviation</span></div>
                    `;
                };
                const setGoogleMapsLink = (orderedStops) => {
                    if (!googleMapsLink) return;
                    const formatPoint = (point) => `${point.lat.toFixed(7)},${point.lng.toFixed(7)}`;
                    const params = new URLSearchParams({ api: '1', travelmode: 'driving', origin: formatPoint(driverPickup), destination: formatPoint(driverDropoff) });
                    const waypoints = (orderedStops || []).map((stop) => stop.point).filter(Boolean).slice(0, 23).map(formatPoint);
                    if (waypoints.length) params.set('waypoints', waypoints.join('|'));
                    googleMapsLink.href = `https://www.google.com/maps/dir/?${params.toString()}`;
                };
                const redraw = async () => {
                    const activeStops = visibleStops();
                    const [originalRoute, suggestedRoute] = await Promise.all([fetchRoute([driverPickup, driverDropoff]), shortestMiddleRoute(activeStops)]);
                    requestMap.eachLayer((layer) => { if (!(layer instanceof L.TileLayer)) requestMap.removeLayer(layer); });
                    L.polyline(originalRoute.points, { color: '#64748b', weight: 9, opacity: .38, lineCap: 'round', interactive: false }).addTo(requestMap);
                    if (suggestedRoute?.points?.length > 1) L.polyline(suggestedRoute.points, { color: '#1d4ed8', weight: 5, opacity: .92, lineCap: 'round', interactive: false }).addTo(requestMap);
                    addPoint(driverPickup, 'driver-pickup', 'Pickup Driver', 'A');
                    addPoint(driverDropoff, 'driver-dropoff', 'Driver Drop-off', 'B');
                    activeStops.forEach((stop) => addPoint(stop.point, stop.status, `${stop.label} · ${stop.status}`, stop.marker, stop.color));
                    renderStopList();
                    renderMetrics(originalRoute, suggestedRoute, activeStops);
                    setGoogleMapsLink(suggestedRoute?.order || activeStops);
                    const bounds = L.latLngBounds([...originalRoute.points, ...(suggestedRoute?.points ?? []), ...activeStops.map((stop) => stop.point)]);
                    if (bounds.isValid()) requestMap.fitBounds(bounds, { padding: [28, 28] });
                    setTimeout(() => requestMap?.invalidateSize(), 100);
                };
                await redraw();
            };
            const render = (requests, button) => {
                const rows = Array.isArray(requests) ? requests : [];
                if (rows.length === 0) {
                    list.innerHTML = `
                        <section class="trip-request-hero">
                            <div class="trip-request-hero-head">
                                <h3 class="trip-request-hero-title">Join Requests</h3>
                                ${requestToggleForm(button)}
                            </div>
                            <div class="trip-request-hero-meta">
                                <span><i class="fa-solid fa-route"></i>${escapeHtml(button.dataset.routeName || '-')}</span>
                                <span># Trip #${escapeHtml(button.dataset.tripId || '-')}</span>
                            </div>
                            <div class="trip-request-hero-stats">
                                <span data-request-open-state><i class="fa-solid fa-lock-open"></i>Public Join: ${escapeHtml(button.dataset.openState || '-')}</span>
                                <span><i class="fa-solid fa-chair"></i>Seats: ${escapeHtml(button.dataset.seats || '-')}</span>
                                <span><i class="fa-solid fa-circle-check"></i>Status: ${escapeHtml(button.dataset.tripStatus || '-')}</span>
                            </div>
                        </section>
                        <div class="trip-payment-review-empty">No active passenger requests for this trip.</div>
                    `;
                    return;
                }

                const pendingCount = rows.filter((request) => request.status === 'pending').length;
                const approvedCount = rows.filter((request) => request.status === 'approved').length;
                const customStops = rows.filter((request) => String(request.pickup || '').startsWith('Custom') || String(request.dropoff || '').startsWith('Custom')).length;
                const extraKm = rows.reduce((sum, request) => sum + (Number(request.detour_km) || 0), 0);
                const extraMin = rows.reduce((sum, request) => sum + (Number(request.detour_min) || 0), 0);
                const suggestedFare = rows.reduce((sum, request) => sum + (Number(request.fare) || 0), 0);
                const routeUrl = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(button.dataset.pickupName || '')}&destination=${encodeURIComponent(button.dataset.destinationName || '')}`;

                list.innerHTML = `
                    <section class="trip-request-hero">
                        <div class="trip-request-hero-head">
                            <h3 class="trip-request-hero-title">Join Requests</h3>
                            ${requestToggleForm(button)}
                        </div>
                        <div class="trip-request-hero-meta">
                            <span><i class="fa-solid fa-route"></i>${escapeHtml(button.dataset.routeName || '-')}</span>
                            <span># Trip #${escapeHtml(button.dataset.tripId || '-')}</span>
                        </div>
                        <div class="trip-request-hero-stats">
                            <span data-request-open-state><i class="fa-solid fa-lock-open"></i>Public Join: ${escapeHtml(button.dataset.openState || '-')}</span>
                            <span><i class="fa-solid fa-chair"></i>Seats: ${escapeHtml(button.dataset.seats || '-')}</span>
                            <span><i class="fa-solid fa-circle-check"></i>Status: ${escapeHtml(button.dataset.tripStatus || '-')}</span>
                        </div>
                    </section>
                    <section class="trip-request-summary-card">
                        <div>
                            <h3 class="trip-request-section-title">Passenger Route Summary</h3>
                            <p class="trip-request-section-sub">Pending and approved custom stops with the shortest middle route as driver reference. Driver pickup and drop-off remain fixed.</p>
                        </div>
                        <span class="trip-request-count-pill">${rows.length} active request${rows.length === 1 ? '' : 's'}</span>
                        <div class="trip-request-map" id="tripRequestsInlineMap"></div>
                        <div class="trip-request-map-legend">
                            <span><i class="original"></i>Original route</span>
                            <span><i class="suggested"></i>Suggested route</span>
                        </div>
                        <div class="trip-request-stops" id="tripRequestsInlineStops"></div>
                        <div class="summary-metrics-grid" id="tripRequestsInlineMetrics"></div>
                        <a class="trip-paynow-submit" id="tripRequestsInlineGoogleMaps" href="${routeUrl}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;"><i class="fa-solid fa-map-location-dot" style="margin-right:6px;"></i>Open in Google Maps</a>
                    </section>
                    <section class="trip-request-summary-card">
                        <div>
                            <h3 class="trip-request-section-title">Passenger Requests</h3>
                            <p class="trip-request-section-sub">Review pending and approved passengers, route preferences, fare preview, and risk signals.</p>
                        </div>
                        <div class="trip-request-tools">
                            <input class="trip-request-tool" type="search" placeholder="Search passenger, note, or route..." data-request-search>
                            <select class="trip-request-tool" data-request-status-filter>
                                <option value="all">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                            </select>
                        </div>
                    </section>
                    ${rows.map((request) => `
                    <article class="trip-payment-review-item">
                        <div class="trip-payment-review-top">
                            <div class="trip-payment-review-person">
                                <span class="trip-payment-review-avatar">${escapeHtml(request.initials || 'P')}</span>
                                <span>
                                    <span class="trip-payment-review-name">${escapeHtml(request.passenger)}</span>
                                    <span class="trip-payment-review-route">${escapeHtml(request.trip)} · ${escapeHtml(request.requested_at || '-')}</span>
                                </span>
                            </div>
                            <span class="trip-payment-review-status">${escapeHtml(request.status || 'pending')}</span>
                        </div>
                        <div class="trip-request-route-grid">
                            <div class="trip-request-route-item">
                                <span>Pickup</span>
                                <strong>${escapeHtml(request.pickup)}</strong>
                                <small>${escapeHtml(request.pickup_meta || '-')}</small>
                            </div>
                            <div class="trip-request-route-item">
                                <span>Drop-off</span>
                                <strong>${escapeHtml(request.dropoff)}</strong>
                                <small>${escapeHtml(request.dropoff_meta || '-')}</small>
                            </div>
                            <div class="trip-request-route-item">
                                <span>Extra fee</span>
                                <strong>${request.fare ? `+ RM ${escapeHtml(request.fare)}` : 'No extra fee'}</strong>
                                <small>Added only to this passenger</small>
                            </div>
                            <div class="trip-request-route-item">
                                <span>Route fit</span>
                                <strong>${escapeHtml(request.fit || 'Review')}</strong>
                                <small>${escapeHtml(request.fit_label || 'Driver review')}</small>
                            </div>
                        </div>
                        <div class="trip-request-risk-card">
                            <div class="trip-request-risk-top">
                                <span class="trip-request-risk-title"><i class="fa-solid fa-shield-halved"></i> AI Passenger Risk</span>
                                <span class="trip-request-risk-badge ${Number(request.risk_score) >= 80 ? 'risk-low' : Number(request.risk_score) >= 60 ? 'risk-moderate' : 'risk-high'}">${escapeHtml(request.risk_level || 'Moderate Risk')}</span>
                            </div>
                            <div class="trip-request-risk-score">${Number(request.risk_score) || 70}<span>/100</span></div>
                            <div class="trip-request-risk-meta">
                                <span><i class="fa-solid fa-shield-heart"></i> Reliability: ${Number(request.risk_reliability || 5.0).toFixed(1)}/5.0</span>
                                <span><i class="fa-solid fa-file-invoice-dollar"></i> Overdue: ${Number(request.risk_unpaid) || 0}</span>
                                <span><i class="fa-solid fa-clock"></i> Cancelled: ${Number(request.risk_cancelled) || 0}</span>
                                <span><i class="fa-solid fa-user-clock"></i> Absences: ${Number(request.risk_absent) || 0}</span>
                            </div>
                        </div>
                        ${request.note ? `<div class="trip-request-note">Passenger note: ${escapeHtml(request.note)}</div>` : ''}
                        ${request.status === 'pending' ? `
                            <div class="trip-request-actions">
                                ${responseForm(request, 'reject', 'Reject', 'danger', 'fa-solid fa-xmark')}
                                ${responseForm(request, 'approve', 'Approve', 'confirm', 'fa-solid fa-check')}
                            </div>
                        ` : '<div class="trip-request-note">This request has already been approved.</div>'}
                    </article>
                `).join('')}
                `;
                drawMap(button, rows);
            };
            const open = (button) => {
                activeRequestButton = button;
                const requests = decodePayload(button.dataset.requestsB64 || '');
                sub.textContent = button.dataset.routeName || 'Review passenger requests and custom route preferences.';
                showModalSkeleton(list);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    render(requests, button);
                }, 240);
            };
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            buttons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    open(button);
                });
            });
            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
            });
            list.addEventListener('submit', async (event) => {
                const toggleForm = event.target.closest('[data-request-toggle-form]');
                if (!toggleForm || !activeRequestButton) return;

                event.preventDefault();
                const submitBtn = toggleForm.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Updating';
                }

                try {
                    const response = await fetch(toggleForm.action, {
                        method: 'POST',
                        body: new FormData(toggleForm),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Public join setting could not be updated.');
                    }

                    const tripId = activeRequestButton.dataset.tripId || '';
                    const nextOpen = payload.is_open_for_request ? '1' : '0';
                    const nextState = payload.open_state || (payload.is_open_for_request ? 'Open' : 'Closed');
                    document.querySelectorAll(`.open-trip-requests-review[data-trip-id="${CSS.escape(tripId)}"]`).forEach((button) => {
                        button.dataset.isOpenForRequest = nextOpen;
                        button.dataset.openState = nextState;
                    });
                    activeRequestButton.dataset.isOpenForRequest = nextOpen;
                    activeRequestButton.dataset.openState = nextState;
                    render(decodePayload(activeRequestButton.dataset.requestsB64 || ''), activeRequestButton);
                } catch (error) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                        submitBtn.title = error.message || 'Update failed.';
                    }
                }
            });
            list.addEventListener('input', (event) => {
                if (!event.target.matches('[data-request-search]')) return;
                const term = event.target.value.trim().toLowerCase();
                list.querySelectorAll('.trip-payment-review-item').forEach((item) => {
                    item.classList.toggle('trip-request-card-hidden', term && !item.textContent.toLowerCase().includes(term));
                });
            });
            list.addEventListener('change', (event) => {
                if (!event.target.matches('[data-request-status-filter]')) return;
                const status = event.target.value;
                list.querySelectorAll('.trip-payment-review-item').forEach((item) => {
                    item.classList.toggle('trip-request-card-hidden', status !== 'all' && !item.textContent.toLowerCase().includes(status));
                });
            });
        })();

        (() => {
            const modal      = document.getElementById('tripDetailsModal');
            const closeBtn   = document.getElementById('tripDetailsCloseBtn');
            const detailButtons = document.querySelectorAll('.open-trip-modal-btn');
            if (!modal || !closeBtn || detailButtons.length === 0) return;
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const tripIdsEl          = document.getElementById('tripModalTripIds');
            const modeEl             = document.getElementById('tripModalMode');
            const pairHintEl         = document.getElementById('tripModalPairHint');
            const routeNameEl        = document.getElementById('tripModalRouteName');
            const driverEl           = document.getElementById('tripModalDriver');
            const driverAvatarEl     = document.getElementById('tripModalDriverAvatar');
            const driverEmailEl      = document.getElementById('tripModalDriverEmail');
            const statusEl           = document.getElementById('tripModalStatus');
            const outboundTimeEl     = document.getElementById('tripModalOutboundTime');
            const fareLabelEl        = document.getElementById('tripModalFareLabel');
            const fareValueEl        = document.getElementById('tripModalFareValue');
            const totalPassengersEl  = document.getElementById('tripModalTotalPassengers');
            const splitTypeEl        = document.getElementById('tripModalSplitType');
            const passengerCountEl   = document.getElementById('tripModalPassengerCount');
            const passengerListEl    = document.getElementById('tripModalPassengerList');
            const pickupPointEl      = document.getElementById('tripModalPickupPoint');
            const destinationPointEl = document.getElementById('tripModalDestinationPoint');
            const pointALabelEl      = document.getElementById('tripModalPointALabel');
            const pointBLabelEl      = document.getElementById('tripModalPointBLabel');
            const mapEl              = document.getElementById('tripModalMap');
            const whatsappEl         = document.getElementById('tripModalWhatsapp');
            const emailEl            = document.getElementById('tripModalEmail');

            let miniMap    = null;
            let routeLayer = null;
            let markerLayer = null;

            const toNum = (v) => {
                const n = Number.parseFloat(String(v ?? '').trim());
                return Number.isFinite(n) ? n : null;
            };
            const toStatusSlug = (value) => String(value || '')
                .trim()
                .toLowerCase()
                .replace(/\s+/g, '_')
                .replace(/[^a-z0-9_]/g, '');
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const renderPassengerList = (participantsRaw, driverIdRaw = null) => {
                if (!passengerListEl || !passengerCountEl) return;
                const participants = Array.isArray(participantsRaw) ? participantsRaw : [];
                const toBool = (value) => value === true || value === 1 || value === '1';
                const driverId = Number.parseInt(String(driverIdRaw ?? ''), 10);
                const passengers = participants.filter((item) => {
                    if (!item || (!item.name && !item.email)) return false;
                    if (toBool(item?.is_driver)) return false;
                    const uid = Number.parseInt(String(item?.user_id ?? ''), 10);
                    if (Number.isFinite(driverId) && driverId > 0 && Number.isFinite(uid) && uid === driverId) return false;
                    return true;
                });

                passengerCountEl.textContent = `${passengers.length} passengers`;

                if (passengers.length === 0) {
                    passengerListEl.innerHTML = '<div class="trip-passenger-email">No passenger records found for this trip.</div>';
                    return;
                }

                passengerListEl.innerHTML = passengers.map((item) => {
                    const name = escapeHtml(item?.name || '-');
                    const email = escapeHtml(item?.email || '');
                    const avatarHtml = item?.photo_url
                        ? `<span class="trip-passenger-avatar"><img src="${escapeHtml(item.photo_url)}" alt="${name}"></span>`
                        : `<span class="trip-passenger-avatar">${escapeHtml((item?.name || 'U').trim().charAt(0).toUpperCase() || 'U')}</span>`;
                    return `
                        <div class="trip-passenger-item">
                            ${avatarHtml}
                            <div class="trip-passenger-meta">
                                <span class="trip-passenger-name">${name}</span>
                                <span class="trip-passenger-email">${email || '-'}</span>
                            </div>
                            <span class="trip-passenger-role">Passenger</span>
                        </div>
                    `;
                }).join('');
            };

            const ensureMap = () => {
                if (!mapEl || typeof window.L === 'undefined') return null;
                if (miniMap) return miniMap;

                mapEl.innerHTML = '';
                miniMap = window.L.map(mapEl, {
                    zoomControl: false,
                    attributionControl: false,
                    dragging: false,
                    scrollWheelZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    keyboard: false,
                    tap: false,
                    touchZoom: false,
                });

                window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                }).addTo(miniMap);

                return miniMap;
            };

            const passengerStopsFromPayload = (routePointsPayload) => {
                const stops = [];
                (Array.isArray(routePointsPayload) ? routePointsPayload : []).forEach((item, index) => {
                    const sequence  = index + 1;
                    const pickup    = item?.pickup  || null;
                    const dropoff   = item?.dropoff || null;
                    const pickupLat  = toNum(pickup?.lat);
                    const pickupLng  = toNum(pickup?.lng);
                    const dropoffLat = toNum(dropoff?.lat);
                    const dropoffLng = toNum(dropoff?.lng);

                    if (pickupLat !== null && pickupLng !== null) {
                        stops.push({ type: 'pickup',  sequence, lat: pickupLat,  lng: pickupLng,  label: pickup?.label  || `${item?.name || 'Passenger'} pickup` });
                    }
                    if (dropoffLat !== null && dropoffLng !== null) {
                        stops.push({ type: 'dropoff', sequence, lat: dropoffLat, lng: dropoffLng, label: dropoff?.label || `${item?.name || 'Passenger'} drop-off` });
                    }
                });
                return stops;
            };

            const drawMap = async (pickupLat, pickupLng, destinationLat, destinationLng, routePointsPayload = []) => {
                const map = ensureMap();
                if (!map) return;
                if ([pickupLat, pickupLng, destinationLat, destinationLng].some((v) => v === null)) return;

                if (routeLayer)  { map.removeLayer(routeLayer);  routeLayer  = null; }
                if (markerLayer) { map.removeLayer(markerLayer); markerLayer = null; }

                const passengerStops = passengerStopsFromPayload(routePointsPayload);
                const markerLayers   = [
                    window.L.circleMarker([pickupLat, pickupLng],           { radius: 6, color: '#fff', weight: 2, fillColor: '#16a34a', fillOpacity: 1 }).bindTooltip('Pickup Driver',   { direction: 'top', offset: [0, -8] }),
                    window.L.circleMarker([destinationLat, destinationLng], { radius: 6, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1 }).bindTooltip('Driver Drop-off', { direction: 'top', offset: [0, -8] }),
                ];

                passengerStops.forEach((stop) => {
                    const icon = window.L.divIcon({
                        className: '',
                        html: `<span class="trip-passenger-map-pin ${stop.type === 'dropoff' ? 'dropoff' : ''}">${stop.sequence}</span>`,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10],
                    });
                    markerLayers.push(
                        window.L.marker([stop.lat, stop.lng], { icon, interactive: true })
                            .bindTooltip(stop.label, { direction: 'top', offset: [0, -10] })
                    );
                });

                markerLayer = window.L.layerGroup(markerLayers).addTo(map);

                const waypointPoints = [
                    [pickupLat, pickupLng],
                    ...passengerStops.map((stop) => [stop.lat, stop.lng]),
                    [destinationLat, destinationLng],
                ];

                map.fitBounds(window.L.latLngBounds(waypointPoints), { padding: [16, 16] });

                const url = 'https://router.project-osrm.org/route/v1/driving/'
                    + waypointPoints
                        .map((point) => `${encodeURIComponent(point[1])},${encodeURIComponent(point[0])}`)
                        .join(';')
                    + '?overview=full&geometries=geojson&alternatives=false&steps=false';

                try {
                    const response = await fetch(url, { method: 'GET' });
                    if (!response.ok) throw new Error('route');
                    const payload   = await response.json();
                    const geometry  = payload?.routes?.[0]?.geometry?.coordinates ?? [];
                    const latLngs   = geometry
                        .map((coord) => [Number(coord[1]), Number(coord[0])])
                        .filter((coord) => Number.isFinite(coord[0]) && Number.isFinite(coord[1]));

                    if (latLngs.length > 1) {
                        routeLayer = window.L.polyline(latLngs, { color: '#1d4ed8', weight: 4, opacity: 0.95 }).addTo(map);
                        map.fitBounds(routeLayer.getBounds(), { padding: [16, 16] });
                    } else {
                        routeLayer = window.L.polyline(waypointPoints, { color: '#60a5fa', weight: 3, opacity: 0.9, dashArray: '8 6' }).addTo(map);
                    }
                } catch (_e) {
                    routeLayer = window.L.polyline(waypointPoints, { color: '#60a5fa', weight: 3, opacity: 0.9, dashArray: '8 6' }).addTo(map);
                }
            };

            document.addEventListener('click', (event) => {
                const btn = event.target.closest('.open-trip-modal-btn');
                if (!btn) return;
                    const tripId            = String(btn.dataset.tripId || '-');
                    const tripRef           = String(btn.dataset.tripRef || '').trim() || (tripId !== '-' ? `TRP-${tripId.padStart(5, '0')}` : '-');
                    const pairedTripId      = String(btn.dataset.pairedTripId || '').trim();
                    const isTwoWay          = String(btn.dataset.mode || '').toLowerCase().includes('two-way');
                    const driverId          = Number.parseInt(String(btn.dataset.driverId || ''), 10);
                    const driverEmail       = String(btn.dataset.driverEmail || '').trim();
                    const driverWhatsappUrl = String(btn.dataset.driverWhatsappUrl || '').trim();
                    const driverPhoneRaw    = String(btn.dataset.driverPhone || '');

                    let participantsPayload = [];
                    try {
                        const encoded = String(btn.dataset.participantsB64 || '').trim();
                        participantsPayload = encoded ? JSON.parse(atob(encoded)) : JSON.parse(btn.dataset.participants || '[]');
                    } catch (_e) { participantsPayload = []; }

                    let routePointsPayload = [];
                    try {
                        const encoded = String(btn.dataset.routePointsB64 || '').trim();
                        routePointsPayload = encoded ? JSON.parse(atob(encoded)) : [];
                    } catch (_e) { routePointsPayload = []; }

                    const digitsRaw = driverPhoneRaw.replace(/\D+/g, '');
                    let waDigits    = digitsRaw.replace(/^00+/, '');
                    if (/^01\d{8,9}$/.test(waDigits)) { waDigits = `60${waDigits.slice(1)}`; }
                    const waUrl = /^https?:\/\/wa\.me\/\d+$/i.test(driverWhatsappUrl)
                        ? driverWhatsappUrl
                        : (waDigits ? `https://wa.me/${waDigits}` : '');

                    if (tripIdsEl)      tripIdsEl.textContent      = tripRef;
                    if (modeEl)         modeEl.textContent          = btn.dataset.mode || '-';
                    if (pairHintEl) {
                        if (isTwoWay && pairedTripId) {
                            pairHintEl.textContent = `Paired return leg: ${tripRef}`;
                            pairHintEl.style.display = 'block';
                        } else {
                            pairHintEl.textContent   = '';
                            pairHintEl.style.display = 'none';
                        }
                    }
                    if (routeNameEl)    routeNameEl.textContent     = btn.dataset.routeName || '-';
                    if (driverEl)       driverEl.textContent        = btn.dataset.driverName || '-';
                    if (driverAvatarEl) driverAvatarEl.textContent  = ((btn.dataset.driverName || 'D').trim().charAt(0) || 'D').toUpperCase();
                    if (driverEmailEl)  driverEmailEl.textContent   = driverEmail || '-';
                    if (statusEl) {
                        const statusText = btn.dataset.status || '-';
                        const slug       = toStatusSlug(statusText);
                        statusEl.textContent = statusText;
                        statusEl.className   = `trip-modal-value trip-status-badge trip-status-${slug || 'draft'}`;
                    }
                    if (outboundTimeEl)    outboundTimeEl.textContent    = btn.dataset.outboundDatetime || '-';
                    if (fareLabelEl)       fareLabelEl.textContent       = btn.dataset.fareLabel || 'Fare';
                    if (fareValueEl)       fareValueEl.textContent       = btn.dataset.fareDisplay || '-';
                    const totalPassengersText = btn.dataset.totalPassengers || '0';
                    if (totalPassengersEl) totalPassengersEl.textContent = totalPassengersText;
                    if (splitTypeEl)       splitTypeEl.textContent       = btn.dataset.splitType || '-';

                    renderPassengerList(participantsPayload, driverId);

                    if (passengerCountEl && (!participantsPayload || participantsPayload.length === 0)) {
                        const n = Number.parseInt(totalPassengersText, 10);
                        if (Number.isFinite(n) && n > 0) {
                            passengerCountEl.textContent = `${n} passengers`;
                        }
                    }

                    if (pointALabelEl) pointALabelEl.innerHTML      = '<i class="fa-solid fa-location-dot"></i>Pickup Point';
                    if (pointBLabelEl) pointBLabelEl.innerHTML      = '<i class="fa-solid fa-flag-checkered"></i>Destination Point';
                    if (pickupPointEl)      pickupPointEl.textContent      = btn.dataset.pickupName || '-';
                    if (destinationPointEl) destinationPointEl.textContent = btn.dataset.destinationName || '-';

                    if (emailEl) {
                        if (driverEmail) {
                            emailEl.classList.remove('is-disabled');
                            emailEl.setAttribute('href', `mailto:${driverEmail}`);
                        } else {
                            emailEl.classList.add('is-disabled');
                            emailEl.setAttribute('href', '#');
                        }
                    }
                    if (whatsappEl) {
                        if (waUrl) {
                            whatsappEl.classList.remove('is-disabled');
                            whatsappEl.setAttribute('href', waUrl);
                        } else {
                            whatsappEl.classList.add('is-disabled');
                            whatsappEl.setAttribute('href', '#');
                        }
                    }

                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');

                    const pickupLat      = toNum(btn.dataset.pickupLat);
                    const pickupLng      = toNum(btn.dataset.pickupLng);
                    const destinationLat = toNum(btn.dataset.destinationLat);
                    const destinationLng = toNum(btn.dataset.destinationLng);

                    setTimeout(() => {
                        drawMap(pickupLat, pickupLng, destinationLat, destinationLng, routePointsPayload).then(() => {
                            if (miniMap) miniMap.invalidateSize();
                        });
                    }, 40);
            });

            const interactiveSelector = 'a, button, input, select, textarea, form, label';
            document.addEventListener('click', (event) => {
                const card = event.target.closest('.open-trip-card');
                if (!card) return;
                if (event.target.closest(interactiveSelector)) return;
                const btn = card.querySelector('.open-trip-modal-btn');
                if (btn instanceof HTMLButtonElement) btn.click();
            });

            const closeModal = () => {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            };
            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
        })();
    </script>
@endsection
