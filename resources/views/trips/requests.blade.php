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
                'photo_url' => $participant->user?->profile_photo_url,
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


    {{-- Page values for the extracted script below. --}}
    <script>
        window.CH_TRIPREQ = {
            endpoint: @json(route('refresh.trips.requests', $trip)),
            page: @json((int) request('page', 1)),
        };
    </script>
    <script src="{{ asset('js/trips-requests.js') }}?v={{ filemtime(public_path('js/trips-requests.js')) }}"></script>
@endsection
