@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    {{-- Page styles, extracted to a cacheable static file; link kept at the same position as the <style> block so cascade order is unchanged. --}}
    <link rel="stylesheet" href="{{ asset('css/explore.css') }}?v={{ filemtime(public_path('css/explore.css')) }}">

    @php
        $aiRecommendationMap = $aiRecommendationMap ?? [];
        $recommendedTripIds  = $recommendedTripIds ?? [];
        $activeTimeframe     = $filters['timeframe'] ?? request('timeframe', '');
        $activeSeats         = $filters['seats']     ?? request('seats', '');
        $activeFareMax       = $filters['fare_max']  ?? request('fare_max', '');
        $activeConnections   = $filters['connections'] ?? request('connections', '');
        $focusTripId         = (int) request('focus_trip', 0);
        $searchEditQuery     = request()->except(['page', 'focus_trip']);
        $searchDestination   = trim((string) request('destination', ''));
        $searchPickup        = trim((string) request('pickup', ''));
        $searchDate          = trim((string) request('date', ''));
        $searchSeats         = trim((string) request('seats', ''));
        $searchSort          = trim((string) request('sort', 'nearest'));
        $searchRadiusRaw     = request('radius_km');
        $searchRadius        = is_numeric($searchRadiusRaw) ? (float) $searchRadiusRaw : null;
        $searchPickupLat     = is_numeric(request('pickup_lat'))      ? (float) request('pickup_lat')      : null;
        $searchPickupLng     = is_numeric(request('pickup_lng'))      ? (float) request('pickup_lng')      : null;
        $searchDestinationLat = is_numeric(request('destination_lat')) ? (float) request('destination_lat') : null;
        $searchDestinationLng = is_numeric(request('destination_lng')) ? (float) request('destination_lng') : null;
        $hasPickupPin        = $searchPickupLat !== null && $searchPickupLng !== null;
        $hasDestinationPin   = $searchDestinationLat !== null && $searchDestinationLng !== null;
        $hasSearchSummary    = $searchDestination !== '' || $searchPickup !== '' || $searchDate !== '' || $searchSeats !== '' || (request()->filled('center_lat') && request()->filled('center_lng')) || $hasPickupPin || $hasDestinationPin;

        $distanceKm = static function (float $lat1, float $lng1, float $lat2, float $lng2): float {
            $earthKm = 6371;
            $dLat = deg2rad($lat2 - $lat1);
            $dLng = deg2rad($lng2 - $lng1);
            $a = sin($dLat / 2) ** 2
                + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * (sin($dLng / 2) ** 2);
            return $earthKm * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
        };
        $pointToLocalKm = static function (float $lat, float $lng, float $originLat, float $originLng): array {
            return [
                'x' => ($lng - $originLng) * 111.32 * cos(deg2rad($originLat)),
                'y' => ($lat - $originLat) * 110.57,
            ];
        };
        $distanceToSegmentKm = static function (float $pointLat, float $pointLng, float $startLat, float $startLng, float $endLat, float $endLng) use ($pointToLocalKm): float {
            $p = $pointToLocalKm($pointLat, $pointLng, $startLat, $startLng);
            $b = $pointToLocalKm($endLat, $endLng, $startLat, $startLng);
            $lengthSquared = ($b['x'] ** 2) + ($b['y'] ** 2);
            if ($lengthSquared <= 0) {
                return sqrt(($p['x'] ** 2) + ($p['y'] ** 2));
            }
            $t = max(0, min(1, (($p['x'] * $b['x']) + ($p['y'] * $b['y'])) / $lengthSquared));
            $projection = ['x' => $t * $b['x'], 'y' => $t * $b['y']];
            return sqrt((($p['x'] - $projection['x']) ** 2) + (($p['y'] - $projection['y']) ** 2));
        };
        $allowedRadiiForKm = static function (float $routeKm): array {
            if ($routeKm <= 3)  return ['route' => 0.40, 'endpoint' => 0.50];
            if ($routeKm <= 10) return ['route' => 0.70, 'endpoint' => 0.80];
            if ($routeKm <= 25) return ['route' => 1.00, 'endpoint' => 1.20];
            return ['route' => 1.30, 'endpoint' => 1.50];
        };
        $searchPointFit = static function ($trip, ?float $pointLat, ?float $pointLng) use ($distanceKm, $distanceToSegmentKm, $allowedRadiiForKm): ?array {
            if ($pointLat === null || $pointLng === null) return null;
            $pickupLat  = is_numeric($trip->pickup_latitude)       ? (float) $trip->pickup_latitude       : null;
            $pickupLng  = is_numeric($trip->pickup_longitude)      ? (float) $trip->pickup_longitude      : null;
            $dropoffLat = is_numeric($trip->destination_latitude)  ? (float) $trip->destination_latitude  : null;
            $dropoffLng = is_numeric($trip->destination_longitude) ? (float) $trip->destination_longitude : null;
            if ($pickupLat === null || $pickupLng === null || $dropoffLat === null || $dropoffLng === null) {
                return ['state' => 'review', 'distance' => null, 'label' => 'Route map unavailable'];
            }
            $routeKm        = max(0.01, $distanceKm($pickupLat, $pickupLng, $dropoffLat, $dropoffLng));
            $radii          = $allowedRadiiForKm($routeKm);
            $routeDistance  = $distanceToSegmentKm($pointLat, $pointLng, $pickupLat, $pickupLng, $dropoffLat, $dropoffLng);
            $endpointDistance = min(
                $distanceKm($pointLat, $pointLng, $pickupLat, $pickupLng),
                $distanceKm($pointLat, $pointLng, $dropoffLat, $dropoffLng)
            );
            $nearestDistance = min($routeDistance, $endpointDistance);
            $isAllowed = $routeDistance <= $radii['route'] || $endpointDistance <= $radii['endpoint'];
            return [
                'state'           => $isAllowed ? 'ok' : 'blocked',
                'distance'        => $nearestDistance,
                'route_radius'    => $radii['route'],
                'endpoint_radius' => $radii['endpoint'],
                'label'           => $isAllowed
                    ? (number_format($nearestDistance, 2) . ' km from driver route')
                    : (number_format($nearestDistance, 2) . ' km away, needs manual check'),
            ];
        };

        $summaryParts = [];
        if ($searchDestination !== '') {
            $summaryParts[] = 'to ' . \Illuminate\Support\Str::limit($searchDestination, 56, '...');
        }
        if ($searchPickup !== '') {
            $summaryParts[] = 'from ' . \Illuminate\Support\Str::limit($searchPickup, 40, '...');
        }
        if ($searchDate !== '') {
            try {
                $summaryParts[] = 'on ' . \Illuminate\Support\Carbon::parse($searchDate)->format('d M Y');
            } catch (\Throwable $e) {
                $summaryParts[] = 'on ' . $searchDate;
            }
        }
        if ($searchSeats === '1')      { $summaryParts[] = 'with 1 seat'; }
        elseif ($searchSeats === '2plus') { $summaryParts[] = 'with 2+ seats'; }
        if ($activeFareMax !== '' && is_numeric($activeFareMax)) {
            $summaryParts[] = 'under RM ' . number_format((float) $activeFareMax, 0);
        }
        if ($activeConnections === '1') {
            $summaryParts[] = 'from connections';
        }
        if (request()->filled('center_lat') && request()->filled('center_lng') && $searchRadius !== null && $searchRadius > 0) {
            $summaryParts[] = 'within ' . rtrim(rtrim(number_format($searchRadius, 1, '.', ''), '0'), '.') . ' km pin radius';
        }
        if ($hasPickupPin)      { $summaryParts[] = 'pickup pin set'; }
        if ($hasDestinationPin) { $summaryParts[] = 'destination pin set'; }
        if ($searchSort === 'latest') {
            $summaryParts[] = 'sorted by latest date';
        } else {
            $summaryParts[] = 'sorted by nearest date';
        }

        $searchSummaryText  = $summaryParts
            ? ('Showing trips ' . implode(', ', $summaryParts) . '.')
            : 'Tap to search by destination, date, and seats.';
        $searchSummaryTitle = $hasSearchSummary ? 'Search Summary' : 'Where do you want to go?';
        $acceptedConnectionIdsForChips = auth()->user()?->acceptedConnections()->pluck('users.id')->all() ?? [];
        $visibleTripsForChips = $trips->getCollection();
        $publicTripsCount = $visibleTripsForChips->where('visibility', 'public')->count();
        $connectionTripsCount = $visibleTripsForChips->filter(fn ($trip) => in_array((int) $trip->driver_id, $acceptedConnectionIdsForChips, true))->count();
    @endphp

    <div class="xp-wrap">

        {{-- ── Page Header ─────────────────────────────────────────── --}}
        <div class="xp-page-header">
            <div class="xp-header-copy">
                <p class="xp-eyebrow">Discover</p>
                <h1 class="xp-title">Explore public trips</h1>
                <p class="xp-subtitle">Find rides along your usual routes, or request a custom pickup point.</p>
            </div>
            <div class="xp-header-actions">
                <a href="{{ route('explore.search', $searchEditQuery) }}" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-sliders"></i> Advanced filters
                </a>
                <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Offer a trip
                </a>
            </div>
        </div>

        {{-- ── Search bar ───────────────────────────────────────────── --}}
        <form method="GET" action="{{ route('explore.index') }}" class="xp-search-card">
            <div class="xp-search-field">
                <i class="fa-solid fa-location-dot pickup-pin"></i>
                <input type="text" name="pickup" value="{{ $searchPickup }}" placeholder="From" autocomplete="off">
            </div>
            <div class="xp-search-separator">
                <i class="fa-solid fa-arrow-right-arrow-left" style="font-size:13px;"></i>
            </div>
            <div class="xp-search-field">
                <i class="fa-solid fa-location-dot" style="color:var(--ink-3);"></i>
                <input type="text" name="destination" value="{{ $searchDestination }}" placeholder="To" autocomplete="off">
            </div>
            <div class="xp-search-date">
                <i class="fa-regular fa-calendar"></i>
                <input type="date" name="date" value="{{ $searchDate }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;flex-shrink:0;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Search</span>
            </button>
            <a href="{{ route('explore.search', $searchEditQuery) }}" class="btn btn-ghost btn-sm xp-advanced-mobile">
                <i class="fa-solid fa-sliders"></i>
                <span>Advanced</span>
            </a>
        </form>

        {{-- ── Filter chips + sort ─────────────────────────────────── --}}
        <div class="xp-chips-row">
            <a href="{{ route('explore.index') }}"
               class="chip xp-mobile-chip {{ ($activeTimeframe === '' && $activeSeats === '' && $activeFareMax === '' && $activeConnections === '') ? 'active' : '' }}">
                All @if(!$trips->isEmpty()) &middot; {{ $trips->total() }} @endif
            </a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'fare_max']), ['fare_max' => 10])) }}"
               class="chip xp-mobile-chip {{ (string) $activeFareMax === '10' ? 'active' : '' }}">&le; RM 10</a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'seats']), ['seats' => '2plus'])) }}"
               class="chip xp-mobile-chip {{ $activeSeats === '2plus' ? 'active' : '' }}">2+ seats</a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'connections']), ['connections' => 1])) }}"
               class="chip xp-mobile-chip {{ (string) $activeConnections === '1' ? 'active' : '' }}">Connections</a>

            <a href="{{ route('explore.index') }}"
               class="chip xp-desktop-chip {{ ($activeTimeframe === '' && $activeSeats === '' && $activeFareMax === '' && $activeConnections === '') ? 'active' : '' }}">
                All trips @if(!$trips->isEmpty()) &middot; {{ $trips->total() }} @endif
            </a>
            <a href="{{ route('explore.index', request()->except(['page', 'connections', 'fare_max', 'seats'])) }}"
               class="chip xp-desktop-chip">
                Public Trips &middot; {{ $publicTripsCount }}
            </a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'connections']), ['connections' => 1])) }}"
               class="chip xp-desktop-chip {{ (string) $activeConnections === '1' ? 'active' : '' }}">
                Within Connections &middot; {{ $connectionTripsCount }}
            </a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'fare_max']), ['fare_max' => 10])) }}"
               class="chip xp-desktop-chip {{ (string) $activeFareMax === '10' ? 'active' : '' }}">&le; RM 10</a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'seats']), ['seats' => '2plus'])) }}"
               class="chip xp-desktop-chip {{ $activeSeats === '2plus' ? 'active' : '' }}">2+ seats</a>
            <a href="{{ route('explore.index', request()->except(['page'])) }}"
               class="chip xp-desktop-chip">Auto-approve</a>
            @if($hasSearchSummary || $activeTimeframe !== '' || $activeSeats !== '' || $activeFareMax !== '' || $activeConnections !== '')
                <a href="{{ route('explore.index') }}" class="chip xp-desktop-chip" style="color:var(--danger);border-color:var(--danger-soft);">
                    <i class="fa-solid fa-xmark"></i> Clear
                </a>
            @endif

            @if(!$trips->isEmpty())
                <div class="xp-chips-spacer"></div>
                <span class="xp-sort-label">Sort:</span>
                <a href="{{ route('explore.index', array_merge(request()->except(['page', 'sort']), ['sort' => 'nearest'])) }}"
                   class="chip {{ ($searchSort === 'nearest' || $searchSort === '') ? 'active' : '' }}">Nearest Date</a>
                <a href="{{ route('explore.index', array_merge(request()->except(['page', 'sort']), ['sort' => 'latest'])) }}"
                   class="chip {{ $searchSort === 'latest' ? 'active' : '' }}">Latest Date</a>
            @endif
        </div>

        {{-- ── Main body: list + map ───────────────────────────────── --}}
        <div class="xp-body">

            {{-- Left: trip list --}}
            <div class="xp-list" id="xp-real-list" data-initial-load="{{ ($initialLoad ?? false) ? 'true' : 'false' }}">
                {{-- Skeleton placeholder (hidden, shown on filter submit) --}}
                <div id="xp-skel-list" style="display:none;grid-gap:12px;display:none;">
                    @for($sk = 0; $sk < 5; $sk++)
                        <div class="xp-skel-card">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span class="sk" style="width:40px;height:40px;border-radius:999px;flex-shrink:0;"></span>
                                <div style="flex:1;display:grid;gap:6px;">
                                    <span class="sk" style="height:13px;width:{{ [55,70,48,62,58][$sk] }}%;"></span>
                                    <span class="sk" style="height:11px;width:{{ [38,28,42,32,36][$sk] }}%;"></span>
                                </div>
                                <span class="sk" style="width:62px;height:24px;border-radius:var(--r-pill);"></span>
                            </div>
                            <div style="display:grid;gap:7px;padding:4px 0;">
                                <span class="sk" style="height:12px;width:88%;"></span>
                                <span class="sk" style="height:12px;width:{{ [72,80,68,75,70][$sk] }}%;"></span>
                            </div>
                            <div style="display:flex;gap:8px;">
                                <span class="sk" style="height:28px;flex:1;border-radius:var(--r-pill);"></span>
                                <span class="sk" style="height:28px;flex:1;border-radius:var(--r-pill);"></span>
                                <span class="sk" style="height:28px;flex:1;border-radius:var(--r-pill);"></span>
                            </div>
                        </div>
                    @endfor
                </div>
                @if($trips->isEmpty())
                    <x-empty 
                        icon="fa-solid fa-compass" 
                        title="No trips found" 
                        body="No public trips match your filters right now. Try changing your search or check back later." 
                        style="box-shadow:none; border:none; background:transparent; padding:48px 24px;"
                    >
                        <a href="{{ route('explore.index') }}" class="btn btn-soft" style="margin-top:8px;">Clear Filters</a>
                    </x-empty>
                @else
                    @foreach($trips as $trip)
                        @php
                            $routeName         = $trip->savedRoute?->route_name ?: (($trip->pickup_name ?? 'Pickup') . ' → ' . ($trip->destination_name ?? 'Destination'));
                            $pickupText        = $trip->pickup_name ?? 'Pickup';
                            $destinationText   = $trip->destination_name ?? 'Destination';
                            $pickupShortText   = \Illuminate\Support\Str::limit($pickupText, 52, '...');
                            $destShortText     = \Illuminate\Support\Str::limit($destinationText, 52, '...');
                            $tripTypeText      = ((string) ($trip->trip_mode ?? 'one_way')) === 'two_way' ? 'Two Way' : 'One Way';
                            $takenSeats        = (int) $trip->participants->where('is_driver', false)->count();
                            $availableSeats    = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : null;
                            $isFull            = $availableSeats !== null && $availableSeats <= 0;
                            $driverIncludedInSplit = (int) $trip->participant_count > $takenSeats;
                            $seatLimitDisplay  = $trip->seat_limit ? ((int) $trip->seat_limit + ($driverIncludedInSplit ? 1 : 0)) : null;
                            $isFree            = (float) $trip->fare_per_person <= 0;
                            $myRequest         = $trip->joinRequests->first();
                            $isJoined          = $trip->participants->contains(fn ($p) => (int) $p->user_id === (int) auth()->id());
                            $aiRecommendation  = $aiRecommendationMap[$trip->id] ?? null;
                            $isRecommended     = in_array((int) $trip->id, $recommendedTripIds, true);
                            $driverInitial     = strtoupper(substr($trip->driver?->name ?? '?', 0, 2));
                            $vehicleModel      = trim((string) ($trip->driver?->vehicle_model ?? ''));
                            $vehiclePlate      = trim((string) ($trip->driver?->vehicle_plate ?? ''));
                            $joinState         = $isJoined ? 'joined' : (($myRequest && $myRequest->status === 'pending') ? 'pending' : ($isFull ? 'full' : (! $trip->is_open_for_request ? 'closed' : 'open')));
                            $vehicleText       = trim($vehicleModel . ($vehicleModel && $vehiclePlate ? ' · ' : '') . $vehiclePlate);
                        @endphp

                        <article
                            id="exploreTripCard{{ $trip->id }}"
                            class="xp-card {{ $focusTripId === (int) $trip->id ? 'is-focus' : '' }} {{ $isRecommended ? 'is-recommended' : '' }} open-explore-card"
                            data-trip-url="{{ route('explore.show', $trip) }}"
                            data-join-url="{{ route('explore.request-join', $trip) }}"
                            data-join-state="{{ $joinState }}"
                            data-driver="{{ $trip->driver?->name ?: 'Driver' }}"
                            data-driver-initial="{{ $driverInitial }}"
                            data-rating="{{ number_format($trip->driver?->rating ?? 5.0, 2) }}"
                            data-route-name="{{ $routeName }}"
                            data-pickup="{{ $pickupText }}"
                            data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                            data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                            data-destination="{{ $destinationText }}"
                            data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                            data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                            data-time="{{ $trip->trip_datetime?->format('d M Y, H:i') ?: '-' }}"
                            data-seats="{{ $availableSeats !== null ? $availableSeats : '?' }} / {{ (int) $seatLimitDisplay }}"
                            data-fare="{{ $isFree ? 'Free' : ('RM ' . number_format((float) $trip->fare_per_person, 2)) }}"
                            data-fare-raw="{{ number_format((float) $trip->fare_per_person, 2, '.', '') }}"
                            data-fare-total="{{ number_format((float) ($trip->fare_total ?? $trip->savedRoute?->default_fare ?? $trip->fare_per_person), 2, '.', '') }}"
                            data-vehicle="{{ $vehicleText !== '' ? $vehicleText : 'Vehicle not set' }}"
                            data-note="{{ $trip->public_note ?? '' }}"
                            data-trip-ref="{{ $trip->trip_ref ?: 'TRP-' . str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT) }}"
                            tabindex="0"
                            role="button"
                            @if($focusTripId === (int) $trip->id) data-explore-focus-card="1" @endif
                        >
                            @if($isRecommended)
                            <div class="xp-rec-strip">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                AI Recommended
                                @if(!empty($aiRecommendation['explanations']))
                                    @foreach(array_slice($aiRecommendation['explanations'], 0, 2) as $reason)
                                        <span class="xp-rec-pill">{{ $reason }}</span>
                                    @endforeach
                                @endif
                            </div>
                            @endif
                            <div class="xp-card-body">
                                <div class="xp-card-main">

                                {{-- Driver row --}}
                                    <div class="xp-driver-row">
                                    <span class="xp-avatar">{{ $driverInitial }}</span>
                                    <div class="xp-driver-info">
                                        <span class="xp-driver-name">{{ $trip->driver?->name ?: '—' }}</span>
                                        <span class="xp-driver-rating">
                                            <i class="fa-solid fa-star"></i>
                                            {{ number_format($trip->driver?->rating ?? 5.0, 2) }}
                                            <span class="xp-desktop-label">&middot; {{ $trip->driver?->trips_count ?? 0 }} trips</span>
                                        </span>
                                    </div>
                                    </div>

                                {{-- Route timeline --}}
                                    <div class="xp-route-timeline">
                                    {{-- Pickup --}}
                                    <div class="xp-timeline-row">
                                        <div class="xp-timeline-track">
                                            <span class="xp-timeline-dot pickup"></span>
                                            <span class="xp-timeline-line"></span>
                                        </div>
                                        <span class="xp-timeline-text">
                                            <span class="xp-desktop-label">PICKUP</span>
                                            <span class="xp-mobile-route-label">Pickup</span>
                                            <span class="xp-timeline-value">{{ $pickupShortText }}</span>
                                        </span>
                                    </div>

                                    {{-- Destination --}}
                                    <div class="xp-timeline-row">
                                        <div class="xp-timeline-track">
                                            <span class="xp-timeline-dot dest"></span>
                                        </div>
                                        <span class="xp-timeline-text">
                                            <span class="xp-desktop-label">DESTINATION</span>
                                            <span class="xp-mobile-route-label">Destination</span>
                                            <span class="xp-timeline-value">{{ $destShortText }}</span>
                                        </span>
                                    </div>
                                    </div>

                                    {{-- Driver's note (not a route point — kept out of the timeline) --}}
                                    @if($trip->public_note)
                                        <div class="xp-card-note">
                                            <span class="xp-card-note-label">Notes</span>
                                            <span class="xp-card-note-text">{{ \Illuminate\Support\Str::limit($trip->public_note, 90, '...') }}</span>
                                        </div>
                                    @endif

                                    {{-- Vehicle (mobile only — desktop shows this in the footer instead) --}}
                                    <div class="xp-footer-vehicle-mobile">
                                        <i class="fa-solid fa-car-side"></i>
                                        @if($vehicleText !== '')
                                            <span class="xp-vehicle-model">{{ $vehicleModel }}</span>
                                            @if($vehiclePlate !== '')
                                                <span class="xp-vehicle-plate">&middot; {{ $vehiclePlate }}</span>
                                            @endif
                                        @else
                                            <span class="xp-vehicle-model">Vehicle not set</span>
                                        @endif
                                    </div>

                                {{-- Footer: time · seats | fare + button --}}
                                    <div class="xp-card-footer">
                                    <span class="xp-footer-time">
                                        <span class="xp-desktop-label">
                                            <i class="fa-regular fa-clock"></i>{{ $trip->trip_datetime?->format('d M Y') ?: '—' }}<br>
                                            <span style="padding-left:15px;">{{ $trip->trip_datetime?->format('H:i') ?: '—' }}</span>
                                        </span>
                                        <span class="xp-mobile-label">{{ $trip->trip_datetime?->format('d M Y, H:i') ?: '—' }}</span>
                                    </span>
                                    <span class="xp-footer-dot">&middot;</span>
                                    @if($isFull)
                                        <span class="badge badge-danger" style="font-size:11px;">Full</span>
                                    @else
                                        <span class="xp-footer-seats">
                                            <span class="xp-desktop-label"><i class="fa-regular fa-user"></i>{{ $availableSeats !== null ? $availableSeats : '?' }} / {{ (int) $seatLimitDisplay }} seats</span>
                                            <span class="xp-mobile-label">{{ $availableSeats !== null ? $availableSeats : '?' }} seat{{ ($availableSeats !== 1) ? 's' : '' }}</span>
                                        </span>
                                        @endif

                                        @if($vehicleText !== '')
                                            <span class="xp-footer-vehicle xp-desktop-label">
                                                <span class="xp-vehicle-model">{{ $vehicleModel }} @if($vehiclePlate !== '') &middot; @endif</span>
                                                @if($vehiclePlate !== '')
                                                    <span class="xp-vehicle-plate">{{ $vehiclePlate }}</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="xp-footer-vehicle xp-desktop-label">
                                                <span class="xp-vehicle-model">Vehicle</span>
                                                <span class="xp-vehicle-plate">Not set</span>
                                            </span>
                                        @endif

                                        @if($isFree)
                                        <span class="xp-footer-fare free">Free</span>
                                    @else
                                        <span class="xp-footer-fare">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                    @endif

                                    @if($isJoined)
                                        <span class="btn btn-soft btn-sm" style="color:var(--success-ink);border-color:var(--success-soft);">
                                            <i class="fa-solid fa-check"></i> Joined
                                        </span>
                                    @elseif($myRequest && $myRequest->status === 'pending')
                                        <span class="btn btn-soft btn-sm" style="color:var(--warning-ink);border-color:var(--warning-soft);">
                                            <i class="fa-regular fa-clock"></i> Pending
                                        </span>
                                    @elseif($isFull)
                                        <span class="btn btn-soft btn-sm" style="color:var(--muted);cursor:default;">
                                            Full
                                        </span>
                                    @else
                                        <a href="{{ route('explore.show', $trip) }}#join-request" class="btn btn-primary btn-sm open-explore-modal">
                                            Request
                                        </a>
                                    @endif
                                    </div>
                                </div>

                                <aside class="xp-desktop-fare-panel">
                                    <p class="xp-fare-label">Per seat</p>
                                    @if($isFree)
                                        <p class="xp-fare-price free">Free</p>
                                    @else
                                        <p class="xp-fare-price">RM {{ number_format((float) $trip->fare_per_person, 2) }}</p>
                                    @endif
                                    <div class="xp-fare-actions">
                                        @if($isJoined)
                                            <span class="btn btn-soft btn-sm" style="color:var(--success-ink);border-color:var(--success-soft);">
                                                <i class="fa-solid fa-check"></i> Joined
                                            </span>
                                        @elseif($myRequest && $myRequest->status === 'pending')
                                            <span class="btn btn-soft btn-sm" style="color:var(--warning-ink);border-color:var(--warning-soft);">
                                                <i class="fa-regular fa-clock"></i> Pending
                                            </span>
                                            <a href="{{ route('explore.show', $trip) }}" class="btn btn-ghost btn-sm open-explore-modal">
                                                View details
                                            </a>
                                        @elseif($isFull)
                                            <span class="btn btn-soft btn-sm" style="color:var(--muted);cursor:default;">
                                                Full
                                            </span>
                                            <a href="{{ route('explore.show', $trip) }}" class="btn btn-ghost btn-sm open-explore-modal">
                                                View details
                                            </a>
                                        @else
                                            <a href="{{ route('explore.show', $trip) }}#join-request" class="btn btn-primary btn-sm open-explore-modal">
                                                Request seat
                                            </a>
                                            <a href="{{ route('explore.show', $trip) }}" class="btn btn-ghost btn-sm open-explore-modal">
                                                View details
                                            </a>
                                        @endif
                                    </div>
                                </aside>
                            </div>
                        </article>
                    @endforeach

                    {{-- Pagination --}}
                    @if($trips->hasPages())
                        <div class="xp-pagination">
                            {{ $trips->appends(request()->query())->links() }}
                        </div>
                    @endif
                @endif
            </div>

            {{-- Right: sticky map panel --}}
            <div class="xp-map-panel">
                <div class="xp-map-panel-header">
                    <h3 class="xp-map-panel-title">Map of nearby trips</h3>
                    @if($trips->total() > 0)
                        <span class="badge badge-dark">{{ $trips->total() }} {{ $trips->total() === 1 ? 'route' : 'routes' }}</span>
                    @endif
                </div>
                <div id="explore-map"></div>
                <div class="xp-map-legend">
                    <span class="xp-map-legend-item">
                        <span class="xp-legend-dot pickup"></span> Pickup
                    </span>
                    <span class="xp-map-legend-item">
                        <span class="xp-legend-dot destination"></span> Destination
                    </span>
                    <span class="xp-map-legend-item">
                        <span class="xp-legend-dot custom"></span> Custom stop
                    </span>
                </div>
            </div>

        </div>{{-- .xp-body --}}

    </div>{{-- .xp-wrap --}}

    <div class="xp-modal" id="exploreTripModal" aria-hidden="true">
        <div class="xp-modal-card" role="dialog" aria-modal="true" aria-labelledby="exploreTripModalTitle">
            <div class="xp-modal-head">
                <div>
                    <h3 class="xp-modal-title" id="exploreTripModalTitle">Trip details</h3>
                    <span class="xp-modal-sub" id="exploreTripModalSub">Review the trip before requesting a seat.</span>
                </div>
                <button type="button" class="xp-modal-close" id="exploreTripModalClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="xp-modal-body">
                <div class="xp-modal-driver">
                    <span class="xp-modal-avatar" id="exploreModalDriverAvatar">DR</span>
                    <span>
                        <strong class="xp-driver-name" id="exploreModalDriver">Driver</strong>
                        <span class="xp-driver-rating"><i class="fa-solid fa-star"></i><span id="exploreModalRating">5.00</span></span>
                    </span>
                </div>
                <span class="xp-modal-section-label">Trip details</span>
                <div class="xp-modal-kv">
                    <div class="xp-modal-kv-item"><span>Time</span><strong id="exploreModalTime">-</strong></div>
                    <div class="xp-modal-kv-item"><span>Seats Available</span><strong id="exploreModalSeats">-</strong></div>
                    <div class="xp-modal-kv-item"><span>Fare</span><strong id="exploreModalFare">-</strong></div>
                </div>
                <div class="xp-modal-route">
                    <div class="xp-modal-point">
                        <span class="xp-modal-point-dot"></span>
                        <span><span>Pickup</span><strong id="exploreModalPickup">-</strong></span>
                    </div>
                    <div class="xp-modal-point">
                        <span class="xp-modal-point-dot dest"></span>
                        <span><span>Destination</span><strong id="exploreModalDestination">-</strong></span>
                    </div>
                </div>
                <div class="xp-modal-kv">
                    <div class="xp-modal-kv-item" style="grid-column:1/-1"><span>Vehicle</span><strong id="exploreModalVehicle">-</strong></div>
                </div>
                <div class="xp-modal-note-block" id="exploreModalNoteBlock" hidden>
                    <span class="xp-modal-section-label">Note from driver</span>
                    <p class="xp-modal-note-text" id="exploreModalNoteText">-</p>
                </div>
                <details class="xp-modal-pref" id="exploreModalRoutePreference">
                    <summary class="xp-modal-pref-title">
                        <span class="xp-modal-pref-title-text"><i class="fa-solid fa-route"></i> Customize pickup &amp; drop-off</span>
                        <span class="xp-modal-pref-title-meta">
                            <span class="xp-modal-pref-title-hint">Optional</span>
                            <i class="fa-solid fa-chevron-down xp-modal-pref-chevron"></i>
                        </span>
                    </summary>
                    <div class="xp-modal-pref-body">
                    <div class="xp-modal-pref-grid">
                        <div class="xp-modal-pref-group">
                            <span class="xp-modal-pref-label">Pickup point</span>
                            <div class="xp-modal-radio-row">
                                <label class="xp-modal-radio">
                                    <input type="radio" name="pickup_mode" value="default" form="exploreModalJoinForm" checked>
                                    Use trip pickup
                                </label>
                                <label class="xp-modal-radio">
                                    <input type="radio" name="pickup_mode" value="custom" form="exploreModalJoinForm">
                                    Custom pickup
                                </label>
                            </div>
                        </div>
                        <div class="xp-modal-pref-group">
                            <span class="xp-modal-pref-label">Drop-off point</span>
                            <div class="xp-modal-radio-row">
                                <label class="xp-modal-radio">
                                    <input type="radio" name="dropoff_mode" value="default" form="exploreModalJoinForm" checked>
                                    Use destination
                                </label>
                                <label class="xp-modal-radio">
                                    <input type="radio" name="dropoff_mode" value="custom" form="exploreModalJoinForm">
                                    Custom drop-off
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="xp-modal-pref-group">
                        <span class="xp-modal-pref-label">Preferred pickup time</span>
                        <input class="xp-modal-input" type="datetime-local" name="requested_pickup_time" form="exploreModalJoinForm">
                    </div>
                    <p class="xp-modal-help">Use the standard trip points or pin nearby stops along the current route. The driver will review your request before approval.</p>
                    <div class="request-map-card" id="exploreModalMapCard" data-route-picker>
                        <div class="request-map-head">
                            <span class="request-map-title">Pin custom stops</span>
                            <span class="request-map-targets">
                                <button type="button" class="request-map-target active" data-map-target="pickup" hidden>Pin Pickup</button>
                                <button type="button" class="request-map-target" data-map-target="dropoff" hidden>Pin Drop-off</button>
                            </span>
                        </div>
                        <div id="requestRouteMapForm" class="request-route-map"></div>
                        <div class="request-map-legend">
                            <span><i class="legend-route"></i>Current route</span>
                            <span><i class="legend-preview"></i>Suggested join route</span>
                            <span><i class="legend-zone"></i><span id="routeAllowedLabel">Allowed area</span></span>
                            <span><i class="legend-pin"></i>Your pin</span>
                        </div>
                        <div id="requestMapStatus" class="request-map-status">Default trip points selected. Choose custom pickup or drop-off to pin a nearby stop.</div>
                        <div class="request-fare-preview" id="requestFarePreview">
                            <div class="request-fare-preview-head">
                                <span class="request-fare-preview-title">
                                    <i class="fa-solid fa-receipt"></i>
                                    Fare preview
                                </span>
                                <span class="request-fare-preview-badge" id="farePreviewBadge">Default split</span>
                            </div>
                            <div class="request-fare-grid">
                                <div class="request-fare-item">
                                    <span class="request-fare-label">Base route</span>
                                    <span class="request-fare-value" id="farePreviewRoute">-</span>
                                </div>
                                <div class="request-fare-item">
                                    <span class="request-fare-label">Route detour</span>
                                    <span class="request-fare-value" id="farePreviewSegment">-</span>
                                </div>
                                <div class="request-fare-item">
                                    <span class="request-fare-label">Your total</span>
                                    <span class="request-fare-value" id="farePreviewPassenger">-</span>
                                </div>
                                <div class="request-fare-item">
                                    <span class="request-fare-label">Base split</span>
                                    <span class="request-fare-value" id="farePreviewOthers">-</span>
                                </div>
                            </div>
                            <p class="request-fare-note" id="farePreviewNote">The normal fare remains the base. Custom pickup/drop-off only adds an extra charge based on distance from the original route.</p>
                        </div>
                    </div>
                    </div>
                </details>
                <div class="xp-modal-join-fields" id="exploreModalJoinFields">
                    <textarea class="xp-modal-note" id="exploreModalNote" name="request_note" form="exploreModalJoinForm" placeholder="Optional note for the driver"></textarea>
                </div>
            </div>
            <form class="xp-modal-foot" id="exploreModalJoinForm" method="POST">
                @csrf
                <input type="hidden" name="pickup_latitude" value="">
                <input type="hidden" name="pickup_longitude" value="">
                <input type="hidden" name="pickup_name" value="">
                <input type="hidden" name="dropoff_latitude" value="">
                <input type="hidden" name="dropoff_longitude" value="">
                <input type="hidden" name="dropoff_name" value="">
                <input type="hidden" name="extra_fee_amount" value="">
                <input type="hidden" name="detour_distance_km" value="">
                <button type="submit" class="xp-modal-join-btn" id="exploreModalJoinButton">
                    <i class="fa-solid fa-user-plus"></i>
                    Request seat
                </button>
                <div class="xp-modal-feedback" id="exploreModalFeedback" hidden></div>
            </form>
        </div>
    </div>

    <script>window.CH_EXPLORE = { passengerName: @json(auth()->user()?->name ?? 'Passenger') };</script>
    <script src="{{ asset('js/explore-index.js') }}?v={{ filemtime(public_path('js/explore-index.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const realList = document.getElementById('xp-real-list');
            if (realList && realList.dataset.initialLoad === 'true') {
                const skelList = document.getElementById('xp-skel-list');
                if (skelList) skelList.style.display = 'grid';
                realList.classList.add('xp-list-loading');
                realList.dataset.initialLoad = 'false';

                fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(res => res.text())
                    .then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        // Replace the entire explore container so map and cards get updated HTML
                        const currentWrap = document.querySelector('.xp-wrap');
                        const newWrap = doc.querySelector('.xp-wrap');
                        if (currentWrap && newWrap) {
                            currentWrap.innerHTML = newWrap.innerHTML;
                        }
                        // Re-run the map init script logic here by dispatching a custom event or reloading
                        // Actually, the simplest for explore is to just let normal JS handle it, but wait, explore-index.js is already executed.
                        // We can just reload the script:
                        const oldScript = document.querySelector('script[src*="explore-index.js"]');
                        if (oldScript) {
                            const newScript = document.createElement('script');
                            newScript.src = oldScript.src;
                            oldScript.parentNode.replaceChild(newScript, oldScript);
                        }
                    });
            }
        });
    </script>
@endsection
