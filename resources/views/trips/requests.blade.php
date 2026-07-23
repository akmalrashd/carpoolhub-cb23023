@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    {{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
    <link rel="stylesheet" href="{{ asset('css/trips-requests.css') }}?v={{ filemtime(public_path('css/trips-requests.css')) }}">

    @php
        $routeName = $trip->savedRoute?->route_name ?: (($trip->pickup_name ?? 'Pickup') . ' -> ' . ($trip->destination_name ?? 'Destination'));
        $pickupName = $trip->pickup_name ?? 'Pickup';
        $destinationName = $trip->destination_name ?? 'Destination';
        $takenSeats = (int) $trip->participants->where('is_driver', false)->count();
        $availableSeats = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : null;
        $hasReturn = (bool) $trip->returnTrip;
        $directionText = $pickupName . ' -> ' . $destinationName;
        $returnDirectionText = $destinationName . ' -> ' . $pickupName;
        $modeText = $hasReturn ? 'Two-way' : 'One-way';
        $combinedFare = (float) $trip->fare_total + (float) ($trip->returnTrip?->fare_total ?? 0);
        $myFare = (float) ($trip->payments->first()?->amount_due ?? 0)
            + (float) ($trip->returnTrip?->payments?->first()?->amount_due ?? 0);
        $showTotalFare = auth()->user()->role === 'admin';
        $displayFare = $showTotalFare ? $combinedFare : $myFare;
        $pairedTripId = $trip->returnTrip?->id;
        $participantPayload = $trip->participants
            ->map(fn ($participant) => [
                'user_id' => $participant->user_id,
                'name' => $participant->user?->name ?? '-',
                'email' => $participant->user?->email ?? '',
                'photo_url' => $participant->user?->profile_photo
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($participant->user->profile_photo)
                    : null,
                'is_driver' => (bool) $participant->is_driver,
            ])
            ->values();
        $participantPayloadB64 = base64_encode($participantPayload->toJson());
        $passengerCount = (int) $trip->participants->where('is_driver', false)->count();
        if ($passengerCount === 0 && (int) $trip->participant_count > 0) {
            $passengerCount = (int) $trip->participant_count;
        }
        $splitType = ((int) $trip->participant_count > $passengerCount)
            ? 'Driver Included in Fare Split'
            : 'Driver Excluded from Fare Split';
        $summaryRouteRequests = $requests
            ->filter(fn ($requestRow) => in_array((string) $requestRow->status, ['pending', 'approved'], true) && $requestRow->routePoint)
            ->map(function ($requestRow) use ($trip) {
                $routePoint = $requestRow->routePoint;

                return [
                    'id' => $requestRow->id,
                    'status' => (string) $requestRow->status,
                    'name' => $requestRow->user?->name ?: 'Passenger',
                    'fare' => (float) $trip->fare_per_person + ($routePoint->extra_fee_amount !== null ? (float) $routePoint->extra_fee_amount : 0),
                    'deviationKm' => $routePoint->detour_distance_km !== null ? (float) $routePoint->detour_distance_km : 0,
                    'pickup' => [
                        'lat' => $routePoint->uses_default_pickup ? null : (float) $routePoint->pickup_latitude,
                        'lng' => $routePoint->uses_default_pickup ? null : (float) $routePoint->pickup_longitude,
                        'label' => $routePoint->uses_default_pickup ? null : (($requestRow->user?->name ?: 'Passenger') . ' pickup'),
                    ],
                    'dropoff' => [
                        'lat' => $routePoint->uses_default_dropoff ? null : (float) $routePoint->dropoff_latitude,
                        'lng' => $routePoint->uses_default_dropoff ? null : (float) $routePoint->dropoff_longitude,
                        'label' => $routePoint->uses_default_dropoff ? null : (($requestRow->user?->name ?: 'Passenger') . ' drop-off'),
                    ],
                ];
            })
            ->values();
        $summaryRoutePayload = [
            'driverPickup' => [
                'lat' => $trip->pickup_latitude !== null ? (float) $trip->pickup_latitude : null,
                'lng' => $trip->pickup_longitude !== null ? (float) $trip->pickup_longitude : null,
                'label' => 'Pickup Driver',
            ],
            'driverDropoff' => [
                'lat' => $trip->destination_latitude !== null ? (float) $trip->destination_latitude : null,
                'lng' => $trip->destination_longitude !== null ? (float) $trip->destination_longitude : null,
                'label' => 'Driver Drop-off',
            ],
            'baseFareTotal' => (float) $trip->fare_total,
            'baseFarePerPerson' => (float) $trip->fare_per_person,
            'includesDriver' => ((int) $trip->participant_count > $passengerCount),
            'requests' => $summaryRouteRequests,
        ];
        $modalRoutePointPayload = $summaryRouteRequests
            ->filter(fn ($requestRow) => (string) ($requestRow['status'] ?? '') === 'approved')
            ->filter(fn ($requestRow) => ! empty($requestRow['pickup']['lat']) || ! empty($requestRow['dropoff']['lat']))
            ->values();
        $modalRoutePointPayloadB64 = base64_encode($modalRoutePointPayload->toJson());
        $reliabilityScoreConfig = (array) config('passenger_reliability.score', []);
        $amountPenaltyConfig = (array) config('passenger_reliability.amount_penalties', []);
        $overduePenaltyConfig = (array) config('passenger_reliability.overdue_penalties', []);
        $casePenaltyConfig = (array) config('passenger_reliability.case_penalties', []);
        $riskLabelConfig = (array) config('passenger_reliability.risk_labels', []);
    @endphp

    <div class="trip-requests-page">
        <section class="trip-requests-card">
            <div class="trip-requests-top">
                <div>
                    <h1 class="trip-requests-title">Join Requests</h1>
                    <p class="trip-requests-subtitle">
                        <span class="trip-route-meta">
                            <span class="trip-route-item">
                                <i class="fas fa-route"></i>
                                {{ $routeName }}
                            </span>
                            <span class="trip-route-item">
                                <i class="fas fa-hashtag"></i>
                                Trip #{{ $trip->id }}
                            </span>
                        </span>
                    </p>
                    @if($trip->visibility === 'public')
                        <p class="trip-requests-subtitle">
                            <span class="trip-sub-meta">
                                <span id="tripPublicJoinMeta" class="trip-sub-meta-item {{ $trip->is_open_for_request ? 'public-open' : 'public-closed' }}">
                                    <i id="tripPublicJoinIcon" class="fas {{ $trip->is_open_for_request ? 'fa-lock-open' : 'fa-lock' }}"></i>
                                    <span id="tripPublicJoinText">Public Join: {{ $trip->is_open_for_request ? 'Open' : 'Close' }}</span>
                                </span>
                            </span>
                        </p>
                    @endif
                    <p class="trip-requests-subtitle">
                        <span class="trip-sub-meta">
                            <span class="trip-sub-meta-item">
                                <i class="fas fa-chair"></i>
                                <span>Seats: <span id="tripSeatText">{{ $availableSeats !== null ? ($availableSeats . ' available / ' . (int) $trip->seat_limit) : 'Open' }}</span></span>
                            </span>
                            <span class="trip-sub-meta-item">
                                <i class="fas fa-circle-check"></i>
                                <span>Status: <span id="tripStatusText">{{ ucfirst($trip->status) }}</span></span>
                            </span>
                        </span>
                    </p>
                </div>
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <button
                        type="button"
                        class="btn open-trip-modal-btn"
                        data-trip-id="{{ $trip->id }}"
                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                        data-route-name="{{ $routeName }}"
                        data-driver-name="{{ $trip->driver?->name ?: '-' }}"
                        data-driver-id="{{ $trip->driver_id }}"
                        data-driver-email="{{ $trip->driver?->email ?: '' }}"
                        data-driver-whatsapp-url="{{ $trip->driver?->whatsapp_url ?: '' }}"
                        data-driver-phone="{{ $trip->driver?->whatsapp_digits ?: '' }}"
                        data-mode="{{ $modeText }}"
                        data-status="{{ ucfirst($trip->status) }}"
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
                        data-route-points-b64="{{ $modalRoutePointPayloadB64 }}"
                    ><i class="fa-regular fa-eye"></i><span>Trip Details</span></button>
                    @if($trip->visibility === 'public')
                        <form method="POST" action="{{ route('trips.requests.toggle-open', $trip) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_open_for_request" value="{{ $trip->is_open_for_request ? '0' : '1' }}">
                            <button type="submit" class="btn {{ $trip->is_open_for_request ? 'danger' : 'success' }}">
                                <i class="fas {{ $trip->is_open_for_request ? 'fa-lock' : 'fa-lock-open' }}"></i>
                                {{ $trip->is_open_for_request ? 'Close Public Join' : 'Open Public Join' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        <section class="trip-requests-card request-route-summary-card">
            <div class="request-route-summary-head">
                <div>
                    <h2 class="request-route-summary-title">Passenger Route Summary</h2>
                    <p class="request-route-summary-subtitle">Pending and approved custom stops with the shortest middle route as driver reference. Driver pickup and drop-off remain fixed.</p>
                </div>
                <span class="request-route-summary-badge">{{ $summaryRouteRequests->count() }} active requests</span>
            </div>
            @if($summaryRouteRequests->isNotEmpty())
                <div id="requestRouteSummaryMap" class="request-route-summary-map" data-route-summary='@json($summaryRoutePayload)'></div>
                <div class="request-route-summary-legend">
                    <span><i class="summary-original-line"></i>Original route</span>
                    <span><i class="summary-optimized-line"></i>Suggested route</span>
                </div>
                <div id="requestRouteSummaryStops" class="summary-stop-list"></div>
                <div id="requestRouteSummaryMetrics" class="summary-metrics-grid">
                    <div class="summary-metric-item">
                        <span class="summary-metric-label">Route distance</span>
                        <span class="summary-metric-value">-</span>
                        <span class="summary-metric-meta">Original vs suggested route</span>
                    </div>
                    <div class="summary-metric-item">
                        <span class="summary-metric-label">Estimated time</span>
                        <span class="summary-metric-value">-</span>
                        <span class="summary-metric-meta">Based on route preview</span>
                    </div>
                    <div class="summary-metric-item">
                        <span class="summary-metric-label">Passenger totals</span>
                        <span class="summary-metric-value">-</span>
                        <span class="summary-metric-meta">Pending and approved requests</span>
                    </div>
                </div>
                <div class="summary-map-actions">
                    <a id="openSummaryGoogleMaps" class="btn primary summary-map-action" href="#" target="_blank" rel="noopener" aria-disabled="true">
                        <i class="fas fa-map-location-dot"></i>
                        Open in Google Maps
                    </a>
                </div>
            @else
                <div class="summary-empty">No pending or approved route points to preview yet.</div>
            @endif
        </section>

        <section class="trip-requests-card">
            <div class="request-list-section-head">
                <h2 class="request-list-section-title">Passenger Requests</h2>
                <p class="request-list-section-subtitle">Review pending and approved passengers, route preferences, fare preview, and risk signals.</p>
                <div class="request-list-tools">
                    <input id="requestSearchInput" class="request-list-tool" type="search" placeholder="Search passenger, email, note, or route...">
                    <select id="requestStatusFilter" class="request-list-tool">
                        <option value="all">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div id="tripRequestsListContainer">
                @include('trips.partials.requests-list', ['requests' => $requests, 'reliabilityMap' => $reliabilityMap, 'aiRiskMap' => $aiRiskMap, 'trip' => $trip])
            </div>
            <div id="requestFilterEmpty" class="request-list-empty-filter" hidden>No passenger requests match the current search or status.</div>
        </section>

        <div id="tripRequestsPaginationContainer">{{ $requests->links() }}</div>
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
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Trip ID</span>
                            <span class="trip-modal-value" id="tripModalTripIds">-</span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-regular fa-calendar"></i>Date & Time</span>
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

    <div class="request-modal" id="rejectModal" aria-hidden="true">
        <div class="request-modal-card">
            <button type="button" class="modal-close-x" id="rejectModalCloseTop" aria-label="Close">&times;</button>
            <h3 class="request-modal-title">Reject Join Requests</h3>
            <div class="request-modal-grid">
                <div class="request-modal-line">
                    <span class="request-modal-label">Passenger</span>
                    <span class="request-modal-value" id="rejectModalPassenger">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label">Trip</span>
                    <span class="request-modal-value" id="rejectModalTrip">-</span>
                </div>
                <form id="rejectModalForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="reject">
                    <textarea
                        class="reject-reason-input"
                        id="rejectModalReason"
                        name="response_note"
                        placeholder="Write the rejection reason..."
                        required
                    ></textarea>
                </form>
            </div>
            <div class="reject-modal-actions">
                <button type="button" class="btn" id="rejectModalCancel">Cancel</button>
                <button type="submit" class="btn danger" form="rejectModalForm"><i class="fas fa-solid fa-xmark"></i>Reject</button>
            </div>
        </div>
    </div>

    <div class="request-modal" id="approveModal" aria-hidden="true">
        <div class="request-modal-card">
            <button type="button" class="modal-close-x" id="approveModalCloseTop" aria-label="Close">&times;</button>
            <h3 class="request-modal-title">Approve Join Requests</h3>
            <div class="request-modal-grid">
                <div class="request-modal-line">
                    <span class="request-modal-label">Passenger</span>
                    <span class="request-modal-value" id="approveModalPassenger">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label">Trip</span>
                    <span class="request-modal-value" id="approveModalTrip">-</span>
                </div>
                <form id="approveModalForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="approve">
                    <textarea
                        class="approve-reason-input"
                        id="approveModalReason"
                        name="response_note"
                        placeholder="Write an approval note (optional)..."
                    ></textarea>
                </form>
            </div>
            <div class="approve-modal-actions">
                <button type="button" class="btn" id="approveModalCancel">Cancel</button>
                <button type="submit" class="btn success" form="approveModalForm"><i class="fas fa-solid fa-check"></i>Approve</button>
            </div>
        </div>
    </div>

    <div class="request-modal" id="ratingInfoModal" aria-hidden="true">
        <div class="request-modal-card">
            <button type="button" class="modal-close-x" id="ratingInfoCloseTop" aria-label="Close">&times;</button>
            <h3 class="request-modal-title">AI Risk & Reliability Details</h3>
            <div class="request-modal-grid">
                <div class="rating-info-formula">
                    <strong>Skor risiko AI:</strong>
                    Starts from a base score and adjusts using payment reliability, unpaid debt, cancellations, and attendance history.
                    <br>
                    <strong>Formula:</strong>
                    Skor = Asas ({{ number_format((float) ($reliabilityScoreConfig['base'] ?? 5.0), 1) }})
                    - Amount Penalty - Overdue Penalty - Case Penalty,
                    then clamped to {{ number_format((float) ($reliabilityScoreConfig['min'] ?? 1.0), 1) }} - {{ number_format((float) ($reliabilityScoreConfig['max'] ?? 5.0), 1) }}.
                    <br>
                    <strong>Overdue outstanding</strong> includes <code>unpaid</code> and <code>pending_confirmation</code> payments from non-draft and non-scheduled trips.
                </div>

                <div class="rating-info-groups">
                    <div class="rating-info-group">
                        <div class="rating-info-group-title"><i class="fas fa-wallet"></i>Amount Penalty</div>
                        <ul class="rating-info-list">
                            @foreach($amountPenaltyConfig as $range)
                                @php
                                    $min = $range['min'] ?? null;
                                    $max = $range['max'] ?? null;
                                    $rangeText = $max === null
                                        ? ('RM ' . number_format((float) $min, 2) . '+')
                                        : ('RM ' . number_format((float) $min, 2) . ' - RM ' . number_format((float) $max, 2));
                                @endphp
                                <li>
                                    <span class="rating-info-range">{{ $rangeText }}</span>
                                    <span class="rating-info-penalty">-{{ number_format((float) ($range['penalty'] ?? 0), 1) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rating-info-group">
                        <div class="rating-info-group-title"><i class="fas fa-clock"></i>Overdue Penalty</div>
                        <ul class="rating-info-list">
                            @foreach($overduePenaltyConfig as $range)
                                @php
                                    $min = (int) ($range['min'] ?? 0);
                                    $max = $range['max'] ?? null;
                                    $rangeText = $max === null
                                        ? ($min . '+ days')
                                        : ($min . ' - ' . (int) $max . ' days');
                                @endphp
                                <li>
                                    <span class="rating-info-range">{{ $rangeText }}</span>
                                    <span class="rating-info-penalty">-{{ number_format((float) ($range['penalty'] ?? 0), 1) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rating-info-group">
                        <div class="rating-info-group-title"><i class="fas fa-file-invoice-dollar"></i>Case Count Penalty</div>
                        <ul class="rating-info-list">
                            @foreach($casePenaltyConfig as $range)
                                @php
                                    $min = (int) ($range['min'] ?? 0);
                                    $max = $range['max'] ?? null;
                                    $rangeText = $max === null
                                        ? ($min . '+ kes')
                                        : ($min . ' - ' . (int) $max . ' kes');
                                @endphp
                                <li>
                                    <span class="rating-info-range">{{ $rangeText }}</span>
                                    <span class="rating-info-penalty">-{{ number_format((float) ($range['penalty'] ?? 0), 1) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rating-info-group">
                        <div class="rating-info-group-title"><i class="fas fa-shield-heart"></i>Label Risiko</div>
                        <ul class="rating-info-list">
                            @foreach($riskLabelConfig as $range)
                                @php
                                    $min = (float) ($range['min'] ?? 0);
                                    $max = (float) ($range['max'] ?? $min);
                                @endphp
                                <li>
                                    <span class="rating-info-range">{{ number_format($min, 1) }} - {{ number_format($max, 1) }}</span>
                                    <span class="rating-info-penalty" style="color:#1e3a8a;">{{ $range['label'] ?? '-' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="approve-modal-actions">
                <button type="button" class="btn" id="ratingInfoCloseBtn">Close</button>
            </div>
        </div>
    </div>


    <script>
        (() => {
            const mapEl = document.getElementById('requestRouteSummaryMap');
            if (!mapEl || typeof window.L === 'undefined') return;

            let payload = null;
            try {
                payload = JSON.parse(mapEl.dataset.routeSummary || '{}');
            } catch (_error) {
                payload = null;
            }
            if (!payload?.driverPickup || !payload?.driverDropoff) return;

            const toPoint = (raw) => {
                const lat = Number.parseFloat(String(raw?.lat ?? ''));
                const lng = Number.parseFloat(String(raw?.lng ?? ''));
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
                return window.L.latLng(lat, lng);
            };
            const driverPickup = toPoint(payload.driverPickup);
            const driverDropoff = toPoint(payload.driverDropoff);
            if (!driverPickup || !driverDropoff) return;

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

                return Object.values(grouped).every((group) => {
                    if (group.pickup === undefined || group.dropoff === undefined) return true;
                    return group.pickup < group.dropoff;
                });
            };
            const straightDistanceKm = (points) => {
                let total = 0;
                for (let index = 0; index < points.length - 1; index += 1) {
                    total += points[index].distanceTo(points[index + 1]) / 1000;
                }
                return total;
            };
            const fetchRoute = async (points) => {
                const waypoints = uniqueWaypoints(points);
                if (waypoints.length < 2) return { points: waypoints, distanceKm: 0, durationMinutes: 0 };
                const coordinates = waypoints
                    .map((point) => `${encodeURIComponent(point.lng)},${encodeURIComponent(point.lat)}`)
                    .join(';');
                const url = `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`;

                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('route');
                    const data = await response.json();
                    const route = data?.routes?.[0];
                    const routePoints = (route?.geometry?.coordinates ?? [])
                        .map((coord) => window.L.latLng(Number(coord[1]), Number(coord[0])))
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
            const shortestMiddleRoute = async (stops) => {
                const usableStops = stops.filter((stop) => stop.point);
                const orders = usableStops.length <= 7
                    ? permutations(usableStops).filter(validPassengerOrder)
                    : [usableStops];
                const candidates = orders.length ? orders : [[]];
                const routes = await Promise.all(candidates.map(async (order) => {
                    const points = uniqueWaypoints([driverPickup, ...order.map((item) => item.point), driverDropoff]);
                    return {
                        ...(await fetchRoute(points)),
                        order,
                    };
                }));

                return routes.reduce((best, route) => {
                    if (!best || route.distanceKm < best.distanceKm) return route;
                    return best;
                }, null);
            };

            const map = window.L.map(mapEl, {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: false,
            });
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            const googleMapsLink = document.getElementById('openSummaryGoogleMaps');
            const originalLayer = window.L.layerGroup().addTo(map);
            const summaryLayer = window.L.layerGroup().addTo(map);
            let originalRouteCache = null;
            let summaryBoundsFitted = false;
            let summaryIsLoading = false;

            const passengerPalette = ['#7c3aed', '#0f766e', '#dc2626', '#2563eb', '#9333ea', '#c2410c', '#0891b2', '#be123c'];
            const colorForRequest = (requestId) => {
                const raw = Number.parseInt(String(requestId ?? '0'), 10);
                const index = Number.isFinite(raw) ? Math.abs(raw) % passengerPalette.length : 0;
                return passengerPalette[index];
            };
            const stops = (payload.requests || []).flatMap((request) => {
                const pickup = toPoint(request.pickup);
                const dropoff = toPoint(request.dropoff);
                const color = colorForRequest(request.id);
                return [
                    pickup ? { requestId: request.id, kind: 'pickup', point: pickup, label: request.pickup.label || `${request.name} pickup`, status: request.status, color } : null,
                    dropoff ? { requestId: request.id, kind: 'dropoff', point: dropoff, label: request.dropoff.label || `${request.name} drop-off`, status: request.status, color } : null,
                ].filter(Boolean);
            }).map((stop, index) => ({ ...stop, marker: String(index + 1) }));
            const visibleRequestIds = new Set((payload.requests || []).map((request) => String(request.id)));
            const visibleStops = () => stops.filter((stop) => visibleRequestIds.has(String(stop.requestId)));
            const setSummaryLoading = (loading) => {
                summaryIsLoading = loading;
                document.querySelectorAll('[data-summary-toggle]').forEach((button) => {
                    button.disabled = loading;
                    button.classList.toggle('is-loading', loading);
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.className = loading ? 'fas fa-spinner fa-spin' : (button.classList.contains('is-off') ? 'fas fa-eye-slash' : 'fas fa-eye');
                    }
                });
            };

            const numberedIcon = (className, marker, fill = '') => window.L.divIcon({
                className: '',
                html: `<span class="summary-pin-icon ${className}" data-summary-marker="${marker}" style="${fill ? `--pin-fill:${fill}` : ''}">${marker}</span>`,
                iconSize: [26, 26],
                iconAnchor: [13, 13],
                tooltipAnchor: [0, -14],
            });
            const markerRefs = new Map();
            let selectedMarkerKey = null;
            const activeMarker = (markerKey, active) => {
                const mapMarker = markerRefs.get(markerKey);
                const listItem = document.querySelector(`[data-summary-stop="${markerKey}"]`);
                listItem?.classList.toggle('active', active);
                const iconEl = mapMarker?.getElement()?.querySelector('.summary-pin-icon');
                iconEl?.classList.toggle('active', active);
                if (active && mapMarker) {
                    mapMarker.setZIndexOffset(1000);
                    mapMarker.openTooltip();
                } else if (mapMarker) {
                    mapMarker.setZIndexOffset(0);
                    mapMarker.closeTooltip();
                }
            };
            const selectMarker = (markerKey) => {
                if (selectedMarkerKey && selectedMarkerKey !== markerKey) {
                    activeMarker(selectedMarkerKey, false);
                }
                selectedMarkerKey = selectedMarkerKey === markerKey ? null : markerKey;
                activeMarker(markerKey, selectedMarkerKey === markerKey);
            };
            const addDriverPoint = (point, type, label, marker) => {
                const mapMarker = window.L.marker(point, {
                    icon: numberedIcon(type === 'pickup' ? 'driver-pickup' : 'driver-dropoff', marker),
                    title: label,
                })
                    .addTo(summaryLayer)
                    .bindTooltip(label, { permanent: false, direction: 'top', offset: [0, -10] });
                markerRefs.set(marker, mapMarker);
                mapMarker.on('mouseover', () => activeMarker(marker, true));
                mapMarker.on('mouseout', () => {
                    if (selectedMarkerKey !== marker) activeMarker(marker, false);
                });
                mapMarker.on('click', () => selectMarker(marker));
            };
            const addPassengerPin = (stop) => {
                const icon = numberedIcon(stop.status, stop.marker, stop.color);
                const label = `${stop.label} · ${stop.status}`;
                const mapMarker = window.L.marker(stop.point, { icon, title: label })
                    .addTo(summaryLayer)
                    .bindTooltip(label, { permanent: false, direction: 'top', offset: [0, -10] });
                markerRefs.set(stop.marker, mapMarker);
                mapMarker.on('mouseover', () => activeMarker(stop.marker, true));
                mapMarker.on('mouseout', () => {
                    if (selectedMarkerKey !== stop.marker) activeMarker(stop.marker, false);
                });
                mapMarker.on('click', () => selectMarker(stop.marker));
            };

            const renderStopList = () => {
                const list = document.getElementById('requestRouteSummaryStops');
                if (!list) return;
                const rows = [
                    { marker: 'A', label: 'Driver Pickup', meta: 'driver point', className: 'driver-pickup' },
                    ...stops.map((stop) => ({
                        marker: stop.marker,
                        label: stop.label,
                        meta: `${stop.kind} - ${stop.status}`,
                        className: `${stop.status} ${stop.kind}`,
                        color: stop.color,
                        requestId: String(stop.requestId),
                    })),
                    { marker: 'B', label: 'Driver Drop-off', meta: 'driver point', className: 'driver-dropoff' },
                ];

                list.innerHTML = rows.map((row) => `
                    <div class="summary-stop-item ${row.requestId && !visibleRequestIds.has(row.requestId) ? 'is-hidden' : ''}" data-summary-stop="${row.marker}" ${row.requestId ? `data-summary-request="${row.requestId}"` : ''}>
                        <span class="summary-stop-marker ${row.className}" style="${row.color ? `--pin-fill:${row.color}` : ''}">${row.marker}</span>
                        <span class="summary-stop-text">
                            <span class="summary-stop-label">${row.label}</span>
                            <span class="summary-stop-meta">${row.meta}</span>
                        </span>
                        ${row.requestId ? `<button type="button" class="summary-stop-toggle ${visibleRequestIds.has(row.requestId) ? '' : 'is-off'}" data-summary-toggle="${row.requestId}" aria-label="Toggle passenger on map"><i class="fas ${visibleRequestIds.has(row.requestId) ? 'fa-eye' : 'fa-eye-slash'}"></i></button>` : ''}
                    </div>
                `).join('');
                rows.forEach((row) => {
                    const item = list.querySelector(`[data-summary-stop="${row.marker}"]`);
                    item?.addEventListener('mouseenter', () => activeMarker(row.marker, true));
                    item?.addEventListener('mouseleave', () => {
                        if (selectedMarkerKey !== row.marker) activeMarker(row.marker, false);
                    });
                    item?.addEventListener('click', () => selectMarker(row.marker));
                });
                list.querySelectorAll('[data-summary-toggle]').forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        if (summaryIsLoading) return;
                        const requestId = String(button.dataset.summaryToggle || '');
                        if (visibleRequestIds.has(requestId)) {
                            visibleRequestIds.delete(requestId);
                        } else {
                            visibleRequestIds.add(requestId);
                        }
                        redrawSummary();
                    });
                });
            };
            renderStopList();

            const setGoogleMapsLink = (orderedStops) => {
                if (!googleMapsLink) return;
                const formatPoint = (point) => `${point.lat.toFixed(7)},${point.lng.toFixed(7)}`;
                const params = new URLSearchParams({
                    api: '1',
                    travelmode: 'driving',
                    origin: formatPoint(driverPickup),
                    destination: formatPoint(driverDropoff),
                });
                const waypoints = (orderedStops || [])
                    .map((stop) => stop.point)
                    .filter(Boolean)
                    .slice(0, 23)
                    .map(formatPoint);

                if (waypoints.length) {
                    params.set('waypoints', waypoints.join('|'));
                }

                googleMapsLink.href = `https://www.google.com/maps/dir/?${params.toString()}`;
                googleMapsLink.setAttribute('aria-disabled', 'false');
                googleMapsLink.classList.remove('disabled');
            };

            const formatKm = (value) => `${(Number(value) || 0).toFixed(2)} km`;
            const formatMinutes = (value) => {
                if (value === null || value === undefined || !Number.isFinite(Number(value))) return '-';
                const minutes = Math.max(1, Math.round(Number(value)));
                if (minutes < 60) return `${minutes} min`;
                const hours = Math.floor(minutes / 60);
                const remainder = minutes % 60;
                return remainder ? `${hours}h ${remainder}m` : `${hours}h`;
            };
            const formatMoney = (value) => `RM ${(Number(value) || 0).toFixed(2)}`;
            const renderSummaryMetrics = (originalRoute, suggestedRoute, activeStops) => {
                const grid = document.getElementById('requestRouteSummaryMetrics');
                if (!grid) return;
                const activeRequestIds = new Set((activeStops || []).map((stop) => String(stop.requestId)));
                const activeRequests = (payload.requests || []).filter((request) => activeRequestIds.has(String(request.id)));
                const originalKm = Number(originalRoute?.distanceKm) || 0;
                const suggestedKm = Number(suggestedRoute?.distanceKm) || originalKm;
                const extraKm = Math.max(0, suggestedKm - originalKm);
                const originalMinutes = originalRoute?.durationMinutes;
                const suggestedMinutes = suggestedRoute?.durationMinutes;
                const extraMinutes = originalMinutes !== null && suggestedMinutes !== null
                    ? Math.max(0, Number(suggestedMinutes) - Number(originalMinutes))
                    : null;
                const passengerFareTotal = activeRequests.reduce((sum, request) => sum + (Number(request.fare) || 0), 0);
                const includesDriver = !!payload.includesDriver;
                const driverShare = includesDriver ? (Number(payload.baseFarePerPerson) || 0) : 0;
                const totalFare = passengerFareTotal + driverShare;
                const splitText = includesDriver
                    ? `includes driver share ${formatMoney(driverShare)}`
                    : 'tidak includes driver share';
                const totalDeviation = activeRequests.reduce((sum, request) => sum + (Number(request.deviationKm) || 0), 0);
                const customStops = activeStops.length;
                const approvedCount = activeRequests.filter((request) => request.status === 'approved').length;
                const pendingCount = activeRequests.filter((request) => request.status === 'pending').length;

                grid.innerHTML = `
                    <div class="summary-metric-item">
                        <span class="summary-metric-label">Route distance</span>
                        <span class="summary-metric-value">${formatKm(suggestedKm)}</span>
                        <span class="summary-metric-meta">Original ${formatKm(originalKm)} / extra ${formatKm(extraKm)}</span>
                    </div>
                    <div class="summary-metric-item">
                        <span class="summary-metric-label">Estimated time</span>
                        <span class="summary-metric-value">${formatMinutes(suggestedMinutes)}</span>
                        <span class="summary-metric-meta">Original ${formatMinutes(originalMinutes)} / extra ${formatMinutes(extraMinutes)}</span>
                    </div>
                    <div class="summary-metric-item">
                        <span class="summary-metric-label">Passenger totals</span>
                        <span class="summary-metric-value">${formatMoney(totalFare)}</span>
                        <span class="summary-metric-meta">${splitText} / ${approvedCount} approved / ${pendingCount} pending / ${customStops} custom stops / ${formatKm(totalDeviation)} deviation</span>
                    </div>
                `;
            };

            const redrawSummary = async () => {
                setSummaryLoading(true);
                const activeStops = visibleStops();
                try {
                    const [originalRoute, suggestedRoute] = await Promise.all([
                        originalRouteCache ? Promise.resolve(originalRouteCache) : fetchRoute([driverPickup, driverDropoff]),
                        shortestMiddleRoute(activeStops),
                    ]);
                    originalRouteCache = originalRoute;
                    summaryLayer.clearLayers();
                    markerRefs.clear();
                    selectedMarkerKey = null;

                    originalLayer.clearLayers();
                    window.L.polyline(originalRoute.points, {
                        color: '#64748b',
                        weight: 9,
                        opacity: 0.38,
                        lineCap: 'round',
                        interactive: false,
                    }).addTo(originalLayer);

                    if (suggestedRoute?.points?.length > 1) {
                        window.L.polyline(suggestedRoute.points, {
                            color: '#1d4ed8',
                            weight: 5,
                            opacity: 0.92,
                            lineCap: 'round',
                            interactive: false,
                        }).addTo(summaryLayer);
                    }

                    addDriverPoint(driverPickup, 'pickup', 'Pickup Driver', 'A');
                    addDriverPoint(driverDropoff, 'dropoff', 'Driver Drop-off', 'B');
                    activeStops.forEach(addPassengerPin);
                    renderStopList();
                    setGoogleMapsLink(suggestedRoute?.order || activeStops);
                    renderSummaryMetrics(originalRoute, suggestedRoute, activeStops);

                    const bounds = window.L.latLngBounds([
                        ...originalRoute.points,
                        ...(suggestedRoute?.points ?? []),
                        ...activeStops.map((stop) => stop.point),
                    ]);
                    if (!summaryBoundsFitted && bounds.isValid()) {
                        map.fitBounds(bounds, { padding: [28, 28] });
                        summaryBoundsFitted = true;
                    }
                    setTimeout(() => map.invalidateSize(), 100);
                } finally {
                    setSummaryLoading(false);
                }
            };
            redrawSummary();
        })();

        (() => {
            const modal = document.getElementById('tripDetailsModal');
            const closeBtn = document.getElementById('tripDetailsCloseBtn');
            const detailButtons = document.querySelectorAll('.open-trip-modal-btn');
            if (!modal || !closeBtn || detailButtons.length === 0) return;
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const tripIdsEl = document.getElementById('tripModalTripIds');
            const modeEl = document.getElementById('tripModalMode');
            const pairHintEl = document.getElementById('tripModalPairHint');
            const routeNameEl = document.getElementById('tripModalRouteName');
            const driverEl = document.getElementById('tripModalDriver');
            const driverAvatarEl = document.getElementById('tripModalDriverAvatar');
            const driverEmailEl = document.getElementById('tripModalDriverEmail');
            const statusEl = document.getElementById('tripModalStatus');
            const outboundTimeEl = document.getElementById('tripModalOutboundTime');
            const fareLabelEl = document.getElementById('tripModalFareLabel');
            const fareValueEl = document.getElementById('tripModalFareValue');
            const totalPassengersEl = document.getElementById('tripModalTotalPassengers');
            const splitTypeEl = document.getElementById('tripModalSplitType');
            const passengerCountEl = document.getElementById('tripModalPassengerCount');
            const passengerListEl = document.getElementById('tripModalPassengerList');
            const pickupPointEl = document.getElementById('tripModalPickupPoint');
            const destinationPointEl = document.getElementById('tripModalDestinationPoint');
            const pointALabelEl = document.getElementById('tripModalPointALabel');
            const pointBLabelEl = document.getElementById('tripModalPointBLabel');
            const mapEl = document.getElementById('tripModalMap');
            const whatsappEl = document.getElementById('tripModalWhatsapp');
            const emailEl = document.getElementById('tripModalEmail');

            let miniMap = null;
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
                    const sequence = index + 1;
                    const pickup = item?.pickup || null;
                    const dropoff = item?.dropoff || null;
                    const pickupLat = toNum(pickup?.lat);
                    const pickupLng = toNum(pickup?.lng);
                    const dropoffLat = toNum(dropoff?.lat);
                    const dropoffLng = toNum(dropoff?.lng);

                    if (pickupLat !== null && pickupLng !== null) {
                        stops.push({
                            type: 'pickup',
                            sequence,
                            lat: pickupLat,
                            lng: pickupLng,
                            label: pickup?.label || `${item?.name || 'Passenger'} pickup`,
                        });
                    }
                    if (dropoffLat !== null && dropoffLng !== null) {
                        stops.push({
                            type: 'dropoff',
                            sequence,
                            lat: dropoffLat,
                            lng: dropoffLng,
                            label: dropoff?.label || `${item?.name || 'Passenger'} drop-off`,
                        });
                    }
                });

                return stops;
            };

            const drawMap = async (pickupLat, pickupLng, destinationLat, destinationLng, routePointsPayload = []) => {
                const map = ensureMap();
                if (!map) return;
                if ([pickupLat, pickupLng, destinationLat, destinationLng].some((v) => v === null)) return;

                if (routeLayer) { map.removeLayer(routeLayer); routeLayer = null; }
                if (markerLayer) { map.removeLayer(markerLayer); markerLayer = null; }

                const passengerStops = passengerStopsFromPayload(routePointsPayload);
                const markerLayers = [
                    window.L.circleMarker([pickupLat, pickupLng], {
                        radius: 6, color: '#fff', weight: 2, fillColor: '#16a34a', fillOpacity: 1
                    }).bindTooltip('Pickup Driver', { direction: 'top', offset: [0, -8] }),
                    window.L.circleMarker([destinationLat, destinationLng], {
                        radius: 6, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1
                    }).bindTooltip('Driver Drop-off', { direction: 'top', offset: [0, -8] }),
                ];

                passengerStops.forEach((stop) => {
                    const icon = window.L.divIcon({
                        className: '',
                        html: `<span class="trip-passenger-map-pin ${stop.type === 'dropoff' ? 'dropoff' : 'pickup'}">${stop.sequence}</span>`,
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
                    const payload = await response.json();
                    const geometry = payload?.routes?.[0]?.geometry?.coordinates ?? [];
                    const latLngs = geometry
                        .map((coord) => [Number(coord[1]), Number(coord[0])])
                        .filter((coord) => Number.isFinite(coord[0]) && Number.isFinite(coord[1]));

                    if (latLngs.length > 1) {
                        routeLayer = window.L.polyline(latLngs, { color: '#1d4ed8', weight: 4, opacity: 0.95 }).addTo(map);
                        map.fitBounds(routeLayer.getBounds(), { padding: [16, 16] });
                    } else {
                        routeLayer = window.L.polyline(waypointPoints, {
                            color: '#60a5fa', weight: 3, opacity: 0.9, dashArray: '8 6'
                        }).addTo(map);
                    }
                } catch (_e) {
                    routeLayer = window.L.polyline(waypointPoints, {
                        color: '#60a5fa', weight: 3, opacity: 0.9, dashArray: '8 6'
                    }).addTo(map);
                }
            };

            detailButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const tripId = String(btn.dataset.tripId || '-');
                    const pairedTripId = String(btn.dataset.pairedTripId || '').trim();
                    const isTwoWay = String(btn.dataset.mode || '').toLowerCase().includes('two-way');
                    const driverId = Number.parseInt(String(btn.dataset.driverId || ''), 10);
                    const driverEmail = String(btn.dataset.driverEmail || '').trim();
                    const driverWhatsappUrl = String(btn.dataset.driverWhatsappUrl || '').trim();
                    const driverPhoneRaw = String(btn.dataset.driverPhone || '');
                    let participantsPayload = [];
                    try {
                        const encoded = String(btn.dataset.participantsB64 || '').trim();
                        if (encoded) {
                            participantsPayload = JSON.parse(atob(encoded));
                        } else {
                            participantsPayload = JSON.parse(btn.dataset.participants || '[]');
                        }
                    } catch (_e) {
                        participantsPayload = [];
                    }
                    let routePointsPayload = [];
                    try {
                        const encoded = String(btn.dataset.routePointsB64 || '').trim();
                        routePointsPayload = encoded ? JSON.parse(atob(encoded)) : [];
                    } catch (_e) {
                        routePointsPayload = [];
                    }
                    const digitsRaw = driverPhoneRaw.replace(/\D+/g, '');
                    let waDigits = digitsRaw.replace(/^00+/, '');
                    if (/^01\d{8,9}$/.test(waDigits)) {
                        waDigits = `60${waDigits.slice(1)}`;
                    }
                    const waUrl = /^https?:\/\/wa\.me\/\d+$/i.test(driverWhatsappUrl)
                        ? driverWhatsappUrl
                        : (waDigits ? `https://wa.me/${waDigits}` : '');

                    if (tripIdsEl) tripIdsEl.textContent = pairedTripId ? `#${tripId} & #${pairedTripId}` : `#${tripId}`;
                    if (modeEl) modeEl.textContent = btn.dataset.mode || '-';
                    if (pairHintEl) {
                        if (isTwoWay && pairedTripId) {
                            pairHintEl.textContent = `Paired trip: Trip #${pairedTripId}`;
                            pairHintEl.style.display = 'block';
                        } else {
                            pairHintEl.textContent = '';
                            pairHintEl.style.display = 'none';
                        }
                    }
                    if (routeNameEl) routeNameEl.textContent = btn.dataset.routeName || '-';
                    if (driverEl) driverEl.textContent = btn.dataset.driverName || '-';
                    if (driverAvatarEl) driverAvatarEl.textContent = ((btn.dataset.driverName || 'D').trim().charAt(0) || 'D').toUpperCase();
                    if (driverEmailEl) driverEmailEl.textContent = driverEmail || '-';
                    if (statusEl) {
                        const statusText = btn.dataset.status || '-';
                        const slug = toStatusSlug(statusText);
                        statusEl.textContent = statusText;
                        statusEl.className = `trip-modal-value trip-status-badge trip-status-${slug || 'draft'}`;
                    }
                    if (outboundTimeEl) outboundTimeEl.textContent = btn.dataset.outboundDatetime || '-';
                    if (fareLabelEl) fareLabelEl.textContent = btn.dataset.fareLabel || 'Fare';
                    if (fareValueEl) fareValueEl.textContent = btn.dataset.fareDisplay || '-';
                    const totalPassengersText = btn.dataset.totalPassengers || '0';
                    if (totalPassengersEl) totalPassengersEl.textContent = totalPassengersText;
                    if (splitTypeEl) splitTypeEl.textContent = btn.dataset.splitType || '-';
                    renderPassengerList(participantsPayload, driverId);
                    if (passengerCountEl && (!participantsPayload || participantsPayload.length === 0)) {
                        const n = Number.parseInt(totalPassengersText, 10);
                        if (Number.isFinite(n) && n > 0) {
                            passengerCountEl.textContent = `${n} passengers`;
                        }
                    }
                    if (pointALabelEl) {
                        pointALabelEl.innerHTML = '<i class="fa-solid fa-location-dot"></i>Pickup Point';
                    }
                    if (pointBLabelEl) {
                        pointBLabelEl.innerHTML = '<i class="fa-solid fa-flag-checkered"></i>Destination Point';
                    }
                    if (pickupPointEl) pickupPointEl.textContent = btn.dataset.pickupName || '-';
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

                    const pickupLat = toNum(btn.dataset.pickupLat);
                    const pickupLng = toNum(btn.dataset.pickupLng);
                    const destinationLat = toNum(btn.dataset.destinationLat);
                    const destinationLng = toNum(btn.dataset.destinationLng);

                    setTimeout(() => {
                        drawMap(pickupLat, pickupLng, destinationLat, destinationLng, routePointsPayload).then(() => {
                            if (miniMap) miniMap.invalidateSize();
                        });
                    }, 40);
                });
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

        (() => {
            const rejectModal = document.getElementById('rejectModal');
            const rejectCancelBtn = document.getElementById('rejectModalCancel');
            const rejectCloseTopBtn = document.getElementById('rejectModalCloseTop');
            const rejectForm = document.getElementById('rejectModalForm');
            const rejectPassengerEl = document.getElementById('rejectModalPassenger');
            const rejectTripEl = document.getElementById('rejectModalTrip');
            const rejectReasonEl = document.getElementById('rejectModalReason');
            if (!rejectModal || !rejectCancelBtn || !rejectForm) return;

            const openRejectModal = (action, passenger, trip) => {
                rejectForm.setAttribute('action', action || '');
                if (rejectPassengerEl) rejectPassengerEl.textContent = passenger || '-';
                if (rejectTripEl) rejectTripEl.textContent = trip || '-';
                rejectModal.classList.add('show');
                rejectModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => rejectReasonEl?.focus(), 30);
            };

            const closeRejectModal = () => {
                rejectModal.classList.remove('show');
                rejectModal.setAttribute('aria-hidden', 'true');
                rejectForm.setAttribute('action', '');
                if (rejectReasonEl) rejectReasonEl.value = '';
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.open-reject-btn');
                if (!button) return;
                openRejectModal(button.dataset.action, button.dataset.passenger, button.dataset.trip);
            });

            rejectCancelBtn.addEventListener('click', closeRejectModal);
            if (rejectCloseTopBtn) rejectCloseTopBtn.addEventListener('click', closeRejectModal);
            rejectModal.addEventListener('click', (event) => {
                if (event.target === rejectModal) closeRejectModal();
            });
        })();

        (() => {
            const approveModal = document.getElementById('approveModal');
            const approveCancelBtn = document.getElementById('approveModalCancel');
            const approveCloseTopBtn = document.getElementById('approveModalCloseTop');
            const approveForm = document.getElementById('approveModalForm');
            const approvePassengerEl = document.getElementById('approveModalPassenger');
            const approveTripEl = document.getElementById('approveModalTrip');
            const approveReasonEl = document.getElementById('approveModalReason');
            if (!approveModal || !approveCancelBtn || !approveForm) return;

            const openApproveModal = (action, passenger, trip) => {
                approveForm.setAttribute('action', action || '');
                if (approvePassengerEl) approvePassengerEl.textContent = passenger || '-';
                if (approveTripEl) approveTripEl.textContent = trip || '-';
                approveModal.classList.add('show');
                approveModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => approveReasonEl?.focus(), 30);
            };

            const closeApproveModal = () => {
                approveModal.classList.remove('show');
                approveModal.setAttribute('aria-hidden', 'true');
                approveForm.setAttribute('action', '');
                if (approveReasonEl) approveReasonEl.value = '';
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.open-approve-btn');
                if (!button) return;
                openApproveModal(button.dataset.action, button.dataset.passenger, button.dataset.trip);
            });

            approveCancelBtn.addEventListener('click', closeApproveModal);
            if (approveCloseTopBtn) approveCloseTopBtn.addEventListener('click', closeApproveModal);
            approveModal.addEventListener('click', (event) => {
                if (event.target === approveModal) closeApproveModal();
            });
        })();

        (() => {
            const infoModal = document.getElementById('ratingInfoModal');
            const infoCloseBtn = document.getElementById('ratingInfoCloseBtn');
            const infoCloseTopBtn = document.getElementById('ratingInfoCloseTop');
            if (!infoModal || !infoCloseBtn) return;

            const openInfoModal = () => {
                infoModal.classList.add('show');
                infoModal.setAttribute('aria-hidden', 'false');
            };

            const closeInfoModal = () => {
                infoModal.classList.remove('show');
                infoModal.setAttribute('aria-hidden', 'true');
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.open-rating-info-btn');
                if (!button) return;
                openInfoModal();
            });

            infoCloseBtn.addEventListener('click', closeInfoModal);
            if (infoCloseTopBtn) infoCloseTopBtn.addEventListener('click', closeInfoModal);
            infoModal.addEventListener('click', (event) => {
                if (event.target === infoModal) closeInfoModal();
            });
        })();

        (() => {
            const listContainer = document.getElementById('tripRequestsListContainer');
            const paginationContainer = document.getElementById('tripRequestsPaginationContainer');
            const seatTextEl = document.getElementById('tripSeatText');
            const statusTextEl = document.getElementById('tripStatusText');
            const publicJoinTextEl = document.getElementById('tripPublicJoinText');
            const publicJoinMetaEl = document.getElementById('tripPublicJoinMeta');
            const publicJoinIconEl = document.getElementById('tripPublicJoinIcon');
            const searchInput = document.getElementById('requestSearchInput');
            const statusFilter = document.getElementById('requestStatusFilter');
            const emptyFilterEl = document.getElementById('requestFilterEmpty');
            if (!listContainer || !paginationContainer) return;

            const endpoint = @json(route('refresh.trips.requests', $trip));
            const pollMs = 5000;
            let inFlight = false;

            const syncTripMeta = (tripPayload) => {
                if (!tripPayload || typeof tripPayload !== 'object') return;
                if (seatTextEl && typeof tripPayload.available_seats_text === 'string') {
                    seatTextEl.textContent = tripPayload.available_seats_text;
                }
                if (statusTextEl && typeof tripPayload.status_text === 'string') {
                    statusTextEl.textContent = tripPayload.status_text;
                }
                if (publicJoinTextEl && publicJoinMetaEl && publicJoinIconEl && tripPayload.visibility === 'public') {
                    const open = !!tripPayload.is_open_for_request;
                    publicJoinTextEl.textContent = `Public Join: ${open ? 'Open' : 'Close'}`;
                    publicJoinIconEl.className = `fas ${open ? 'fa-lock-open' : 'fa-lock'}`;
                    publicJoinMetaEl.classList.toggle('public-open', open);
                    publicJoinMetaEl.classList.toggle('public-closed', !open);
                }
            };

            const applyRequestFilters = () => {
                const query = (searchInput?.value || '').trim().toLowerCase();
                const status = (statusFilter?.value || 'all').toLowerCase();
                const items = Array.from(listContainer.querySelectorAll('.request-item'));
                let visibleCount = 0;

                items.forEach((item) => {
                    const searchText = String(item.dataset.requestSearch || item.textContent || '').toLowerCase();
                    const itemStatus = String(item.dataset.requestStatus || '').toLowerCase();
                    const matchesSearch = !query || searchText.includes(query);
                    const matchesStatus = status === 'all' || itemStatus === status;
                    const visible = matchesSearch && matchesStatus;
                    item.hidden = !visible;
                    if (visible) visibleCount += 1;
                });

                if (emptyFilterEl) {
                    emptyFilterEl.hidden = visibleCount > 0 || items.length === 0;
                }
            };
            searchInput?.addEventListener('input', applyRequestFilters);
            statusFilter?.addEventListener('change', applyRequestFilters);
            applyRequestFilters();

            const poll = async () => {
                if (inFlight || document.visibilityState !== 'visible') return;
                inFlight = true;
                try {
                    const response = await fetch(endpoint + '?page=' + encodeURIComponent(@json((int) request('page', 1))), {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) return;
                    const payload = await response.json();
                    if (typeof payload?.requests_html === 'string') {
                        listContainer.innerHTML = payload.requests_html;
                        applyRequestFilters();
                    }
                    if (typeof payload?.pagination_html === 'string') {
                        paginationContainer.innerHTML = payload.pagination_html;
                    }
                    syncTripMeta(payload?.trip);
                } catch (_error) {
                } finally {
                    inFlight = false;
                }
            };

            window.setInterval(poll, pollMs);
        })();
    </script>
@endsection
