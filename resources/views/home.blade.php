@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
        $role = $role ?? $user->role;

        $quickActions = match ($role) {
            'passenger' => [
                ['route' => 'explore.index', 'title' => 'Explore Trips', 'meta' => 'Find public trips', 'art' => 'quick-explore-3d.svg', 'tone' => 'sky'],
                ['route' => 'trips.index', 'title' => 'My Trips', 'meta' => 'Joined and created trips', 'art' => 'quick-trip-3d.svg', 'tone' => 'amber'],
                ['route' => 'connections.index', 'title' => 'Connections', 'meta' => 'Manage carpool contacts', 'art' => 'quick-users-3d.svg', 'tone' => 'emerald'],
                ['route' => 'payments.index', 'title' => 'Pay Fare', 'meta' => 'Review and pay outstanding fares', 'art' => 'quick-payment-3d.svg', 'tone' => 'amber'],
            ],
            'admin' => [
                ['route' => 'admin.users.index', 'title' => 'Manage Users', 'meta' => 'User roles and status', 'art' => 'quick-users-3d.svg', 'tone' => 'emerald'],
                ['route' => 'admin.reports.index', 'title' => 'Reports', 'meta' => 'System overview', 'art' => 'quick-summary-3d.svg', 'tone' => 'slate'],
                ['route' => 'trips.index', 'title' => 'All Trips', 'meta' => 'Monitor trip records', 'art' => 'quick-trip-3d.svg', 'tone' => 'amber'],
                ['route' => 'saved-routes.index', 'title' => 'Manage Routes', 'meta' => 'Route presets', 'art' => 'quick-route-3d.svg', 'tone' => 'sky'],
            ],
            default => [
                ['route' => 'trips.create', 'title' => 'Create Trip', 'meta' => 'Publish a new ride', 'art' => 'quick-trip-3d.svg', 'tone' => 'amber'],
                ['route' => 'trips.index', 'title' => 'My Trips', 'meta' => 'Manage your trip list', 'art' => 'quick-route-3d.svg', 'tone' => 'sky'],
                ['route' => 'explore.index', 'title' => 'Explore', 'meta' => 'Find nearby rides', 'art' => 'quick-explore-3d.svg', 'tone' => 'slate'],
                ['route' => 'connections.index', 'title' => 'Connections', 'meta' => 'Manage your driver network', 'art' => 'quick-users-3d.svg', 'tone' => 'emerald'],
                ['route' => 'payments.index', 'title' => 'Pay Fare', 'meta' => 'Review and pay outstanding fares', 'art' => 'quick-payment-3d.svg', 'tone' => 'amber'],
            ],
        };

        $managementActions = match ($role) {
            'admin' => [
                ['route' => 'admin.users.index', 'title' => 'Users', 'meta' => 'Manage roles and status', 'art' => 'quick-users-3d.svg', 'tone' => 'emerald'],
                ['route' => 'admin.reports.index', 'title' => 'Reports', 'meta' => 'System analytics and export', 'art' => 'quick-summary-3d.svg', 'tone' => 'slate'],
                ['route' => 'trips.index', 'title' => 'Trip', 'meta' => 'Monitor all trip records', 'art' => 'quick-trip-3d.svg', 'tone' => 'amber'],
                ['route' => 'explore.index', 'title' => 'Explore Trips', 'meta' => 'Browse public listings', 'art' => 'quick-explore-3d.svg', 'tone' => 'sky'],
                ['route' => 'connections.index', 'title' => 'Connections', 'meta' => 'User network records', 'art' => 'quick-users-3d.svg', 'tone' => 'emerald'],
                ['route' => 'saved-routes.index', 'title' => 'Routes', 'meta' => 'Route preset administration', 'art' => 'quick-route-3d.svg', 'tone' => 'sky'],
                ['route' => 'payments.index', 'title' => 'Payments', 'meta' => 'Review and track fare status', 'art' => 'quick-payment-3d.svg', 'tone' => 'amber'],
                ['route' => 'notifications.index', 'title' => 'Notifications', 'meta' => 'System alerts and updates', 'art' => 'quick-notification-3d.svg', 'tone' => 'emerald'],
                ['route' => 'settings.index', 'title' => 'Settings', 'meta' => 'Profile and account options', 'art' => 'quick-settings-3d.svg', 'tone' => 'slate'],
            ],
            'driver' => [
                ['route' => 'saved-routes.index', 'title' => 'Routes', 'meta' => 'Route preset management', 'art' => 'quick-route-3d.svg', 'tone' => 'sky'],
                ['route' => 'payments.index', 'url' => route('payments.index') . '#queue-summary', 'title' => 'Payments', 'meta' => 'Track fare status', 'art' => 'quick-payment-3d.svg', 'tone' => 'amber'],
            ],
            default => [],
        };

        $heroPrimary = match ($role) {
            'passenger' => ['label' => 'Find Trips', 'route' => 'explore.index', 'icon' => 'fa-solid fa-magnifying-glass-location'],
            'admin' => ['label' => 'Open Reports', 'route' => 'admin.reports.index', 'icon' => 'fa-solid fa-chart-line'],
            default => ['label' => 'Create Trip', 'route' => 'trips.create', 'icon' => 'fa-solid fa-plus'],
        };
        $heroSecondary = match ($role) {
            'passenger' => ['label' => 'My Trips', 'route' => 'trips.index', 'icon' => 'fa-solid fa-car-side'],
            'admin' => ['label' => 'Manage Users', 'route' => 'admin.users.index', 'icon' => 'fa-solid fa-users-gear'],
            default => ['label' => 'Explore Trips', 'route' => 'explore.index', 'icon' => 'fa-solid fa-compass'],
        };
        $heroKicker = match ($role) {
            'passenger' => 'Find public rides, track payments, and manage joined trips.',
            'admin' => 'Monitor users, trips, reports, and payment activity from one place.',
            default => 'Publish rides, review requests, and keep your route workflow running smoothly.',
        };

        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        // Stat values
        $reviewQueue = $driverReviewQueue ?? collect();
        $upcomingCreatedTrips = $upcomingCreatedTrips ?? collect($upcomingCreatedTrip ? [$upcomingCreatedTrip] : []);
        $upcomingJoinedTrips = $upcomingJoinedTrips ?? collect($upcomingJoinedTrip ? [$upcomingJoinedTrip] : []);
        $nextTrip = $upcomingCreatedTrip ?? $upcomingJoinedTrip;
        $nextRoute = $nextTrip
            ? ($nextTrip->savedRoute?->route_name ?: (($nextTrip->pickup_name ?? 'Pickup') . ' -> ' . ($nextTrip->destination_name ?? 'Destination')))
            : null;
        $nextTripMinutes = $nextTrip?->trip_datetime ? max(0, now()->diffInMinutes($nextTrip->trip_datetime, false)) : null;
        $nextTripCountdown = $nextTripMinutes !== null
            ? (int) floor($nextTripMinutes / 60) . ' h ' . ($nextTripMinutes % 60) . ' min'
            : null;
        $mobileHeroPrimaryUrl = $nextTrip
            ? route('trips.index', ['focus_trip' => $nextTrip->id])
            : route($heroPrimary['route']);
        $mobileHeroPrimaryLabel = $nextTrip ? 'Open Trip' : $heroPrimary['label'];
        $mobileHeroSecondaryUrl = $nextTrip
            ? route($heroSecondary['route'])
            : route('explore.index');
        $mobileHeroSecondaryLabel = $nextTrip ? $heroSecondary['label'] : 'Browse Trips';
        $tripsThisWeek    = (int) ($stats['trips_this_week'] ?? 0);
        $tripsThisMonth   = (int) ($stats['trips_this_month'] ?? 0);
        $totalEarnings    = $stats['total_earnings'] ?? 'RM 0.00';
        $pendingReviews   = (int) $reviewQueue->count();
        $pendingRequests  = (int) ($pendingJoinRequests ?? $stats['pending_requests'] ?? 0);
        $unpaidCount      = (int) ($stats['unpaid_count'] ?? 0);
        $unpaidAmount     = $stats['unpaid_amount'] ?? 0;
        $savedRoutes      = (int) ($stats['saved_routes'] ?? 0);

        // Stat subtitle
        $totalTripsAll    = (int) ($stats['total_trips'] ?? 0);
        $statSubtitle = ($upcomingCreatedTrips->count() + $upcomingJoinedTrips->count()) . ' trip'
            . (($upcomingCreatedTrips->count() + $upcomingJoinedTrips->count()) !== 1 ? 's' : '')
            . ' on your schedule'
            . ($pendingRequests > 0 ? ' · ' . $pendingRequests . ' new request' . ($pendingRequests !== 1 ? 's' : '') : '');

    @endphp

    {{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
    <link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ filemtime(public_path('css/home.css')) }}">

    {{-- ════════════════════════════════════════════════════════════════════════
         DESKTOP LAYOUT  (≥ 1024px)
    ════════════════════════════════════════════════════════════════════════ --}}
    <div class="hp-desktop-only">

        {{-- 1. Page header ------------------------------------------------- --}}
        <div class="hp-page-header">
            <div class="hp-page-header-left">
                <span class="hp-eyebrow">{{ $greeting }}, {{ $user->name }}</span>
                <h1 class="hp-h1">Today's ride hub</h1>
                <p class="hp-subtitle">{{ $statSubtitle }}</p>
            </div>
            <div class="hp-header-actions">
                <a href="{{ route('trips.index', ['date_from' => $stats['week_start'], 'date_to' => $stats['week_end']]) }}" class="btn btn-ghost btn-sm">
                    <i class="fa-regular fa-calendar-days"></i> This week
                </a>
                <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Create Trip
                </a>
            </div>
        </div>

        {{-- 2. Stats strip -------------------------------------------------- --}}
        {{-- Real stats strip --}}
        <div class="hp-stats-strip">

            {{-- Card 1: Trips this week --}}
            <a href="{{ route('trips.index', ['date_from' => $stats['week_start'], 'date_to' => $stats['week_end']]) }}" class="hp-stat-card">
                <div class="hp-stat-icon"><i class="fa-solid fa-car"></i></div>
                <span class="hp-stat-label">Trips this week</span>
                <span class="hp-stat-value">{{ $tripsThisWeek }}</span>
                <span class="hp-stat-delta">{{ $tripsThisMonth }} this month</span>
            </a>

            @if($role === 'passenger')
                {{-- Passenger Card 2: Outstanding payments --}}
                <a href="{{ route('payments.index') }}" class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-wallet"></i></div>
                    <span class="hp-stat-label">Outstanding</span>
                    <span class="hp-stat-value" style="font-size:20px;">
                        RM {{ number_format((float) $unpaidAmount, 2) }}
                    </span>
                    <span class="hp-stat-delta">{{ $unpaidCount > 0 ? $unpaidCount . ' unpaid fare' . ($unpaidCount !== 1 ? 's' : '') : 'All fares settled' }}</span>
                </a>

                {{-- Passenger Card 3: Connections --}}
                <a href="{{ route('connections.index') }}" class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-user-group"></i></div>
                    <span class="hp-stat-label">Connections</span>
                    <span class="hp-stat-value">{{ $stats['connections_count'] ?? 0 }}</span>
                    <span class="hp-stat-delta">Accepted contacts</span>
                </a>

                {{-- Passenger Card 4: Requests you've sent --}}
                <a href="{{ route('explore.index') }}" class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-paper-plane"></i></div>
                    <span class="hp-stat-label">Join requests</span>
                    <span class="hp-stat-value">{{ $stats['sent_pending_requests'] ?? 0 }}</span>
                    <span class="hp-stat-delta">{{ ($stats['sent_pending_requests'] ?? 0) > 0 ? 'Awaiting driver response' : 'None pending' }}</span>
                </a>

            @elseif($role === 'admin')
                {{-- Admin Card 2: Total trips --}}
                <a href="{{ route('trips.index') }}" class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-route"></i></div>
                    <span class="hp-stat-label">Total trips</span>
                    <span class="hp-stat-value">{{ $stats['total_trips'] ?? 0 }}</span>
                    <span class="hp-stat-delta">All time</span>
                </a>

                {{-- Admin Card 3: Payment reviews --}}
                <a href="{{ route('payments.index', ['payment_filter' => 'review']) }}" class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <span class="hp-stat-label">Payment review</span>
                    <span class="hp-stat-value">{{ $pendingReviews }}</span>
                    <span class="hp-stat-delta">{{ $pendingReviews > 0 ? 'Awaiting confirmation' : 'Queue clear' }}</span>
                </a>

                {{-- Admin Card 4: Pending requests (platform-wide) --}}
                <a href="{{ route('trips.index') }}" class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-inbox"></i></div>
                    <span class="hp-stat-label">Join requests</span>
                    <span class="hp-stat-value">{{ $stats['platform_pending_requests'] ?? 0 }}</span>
                    <span class="hp-stat-delta">Platform-wide pending</span>
                </a>

            @else
                {{-- Driver Card 2: Earnings this month --}}
                <a href="{{ route('payments.index', ['date_from' => $stats['month_start'], 'date_to' => $stats['month_end']]) }}" class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-receipt"></i></div>
                    <span class="hp-stat-label">Monthly Earnings</span>
                    <span class="hp-stat-value" style="font-size:20px;">{{ $totalEarnings }}</span>
                    <span class="hp-stat-delta">Paid fares this month</span>
                </a>

                {{-- Driver Card 3: Payment review queue --}}
                <a href="{{ route('payments.index', ['payment_filter' => 'review']) }}" class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <span class="hp-stat-label">Payment review</span>
                    <span class="hp-stat-value">{{ $pendingReviews }}</span>
                    <span class="hp-stat-delta">{{ $pendingReviews > 0 ? 'Awaiting your approval' : 'Queue clear' }}</span>
                </a>

                {{-- Driver Card 4: Pending join requests on your trips --}}
                <a href="{{ route('trips.index') }}" class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-inbox"></i></div>
                    <span class="hp-stat-label">Join requests</span>
                    <span class="hp-stat-value">{{ $pendingRequests }}</span>
                    <span class="hp-stat-delta">{{ $pendingRequests > 0 ? 'Awaiting your response' : 'None pending' }}</span>
                </a>
            @endif
        </div>{{-- /hp-stats-strip real --}}

        {{-- 3. Main body: 2fr + 1fr ---------------------------------------- --}}
        <div class="hp-body">

            {{-- ── LEFT COLUMN (2fr) ──────────────────────────────────────── --}}
            <div style="display:grid;gap:18px;align-content:start;">

                {{-- Upcoming trips card --}}
                <div class="hp-section">
                    <div class="hp-section-head">
                        <h3 class="hp-section-title">Upcoming trips</h3>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="hp-tabs" id="hp-trip-tabs">
                                <button type="button" class="hp-tab active" onclick="hpSwitchTab('driver', this)">As driver</button>
                                <button type="button" class="hp-tab" onclick="hpSwitchTab('passenger', this)">As passenger</button>
                            </div>
                        </div>
                    </div>
                    <div class="hp-section-body">
                        {{-- Real content --}}
                        <div id="hp-trips-real">
                        {{-- As driver panel --}}
                        <div id="hp-tab-driver">
                            @if($upcomingCreatedTrips->isNotEmpty())
                                <div class="hp-trip-list">
                                    @foreach($upcomingCreatedTrips as $trip)
                                    @php
                                        $pickupLabel = $trip->pickup_name ?: 'Pickup';
                                        $destinationLabel = $trip->destination_name ?: 'Destination';
                                        $seats = $trip->participants->where('is_driver', false)->count();
                                        $seatDriverIncluded = (int) $trip->participant_count > $seats;
                                        $seatLimit = $trip->seat_limit !== null ? ((int) $trip->seat_limit + ($seatDriverIncluded ? 1 : 0)) : '?';
                                        $seats = $seats + ($seatDriverIncluded ? 1 : 0);
                                        $statusClass = $trip->status === 'active' ? 'badge-success' : ($trip->status === 'cancelled' ? 'badge-danger' : 'badge-info');
                                    @endphp
                                    <a href="{{ route('trips.index', ['focus_trip' => $trip->id]) }}" class="hp-trip-row">
                                        <div>
                                            <div class="hp-trip-time-date">{{ $trip->trip_datetime?->format('d M Y') ?? '—' }}</div>
                                            <div class="hp-trip-time-clock">{{ $trip->trip_datetime?->format('H:i') ?? '' }}</div>
                                        </div>
                                        <div class="hp-trip-route-points">
                                            <div class="hp-trip-point">
                                                <span class="hp-trip-dot pickup"></span>
                                                <span>
                                                    <span class="hp-trip-point-label">Pickup</span>
                                                    <span class="hp-trip-point-text">{{ $pickupLabel }}</span>
                                                </span>
                                            </div>
                                            <div class="hp-trip-point">
                                                <span class="hp-trip-dot destination"></span>
                                                <span>
                                                    <span class="hp-trip-point-label">Destination</span>
                                                    <span class="hp-trip-point-text">{{ $destinationLabel }}</span>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="badge {{ $statusClass }}">{{ ucfirst($trip->status ?? 'pending') }}</span>
                                        <span class="hp-trip-seats"><i class="fa-solid fa-users" style="font-size:11px;color:var(--muted-2);"></i> {{ $seats }}/{{ $seatLimit }}</span>
                                        <div class="hp-trip-fare">
                                            <span class="hp-trip-fare-amount">RM {{ number_format((float) ($trip->fare_per_person ?? 0), 2) }}</span>
                                            <i class="fa-solid fa-chevron-right hp-trip-fare-chevron"></i>
                                        </div>
                                    </a>
                                    @endforeach
                                </div>
                            @else
                                <x-empty icon="fa-solid fa-car" title="No upcoming trips" body="Create or join a trip to get started.">
                                    <div style="margin-top:12px; display:flex; gap:8px; justify-content:center;">
                                        <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">Post a Trip</a>
                                        <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">Find a Ride</a>
                                    </div>
                                </x-empty>
                            @endif
                        </div>
                        {{-- As passenger panel --}}
                        <div id="hp-tab-passenger" style="display:none;">
                            @if($upcomingJoinedTrips->isNotEmpty())
                                <div class="hp-trip-list">
                                    @foreach($upcomingJoinedTrips as $trip)
                                    @php
                                        $pickupLabel = $trip->pickup_name ?: 'Pickup';
                                        $destinationLabel = $trip->destination_name ?: 'Destination';
                                        $seats = $trip->participants->where('is_driver', false)->count();
                                        $seatDriverIncluded = (int) $trip->participant_count > $seats;
                                        $seatLimit = $trip->seat_limit !== null ? ((int) $trip->seat_limit + ($seatDriverIncluded ? 1 : 0)) : '?';
                                        $seats = $seats + ($seatDriverIncluded ? 1 : 0);
                                        $statusClass = $trip->status === 'active' ? 'badge-success' : ($trip->status === 'cancelled' ? 'badge-danger' : 'badge-info');
                                    @endphp
                                    <a href="{{ route('trips.index', ['focus_trip' => $trip->id]) }}" class="hp-trip-row">
                                        <div>
                                            <div class="hp-trip-time-date">{{ $trip->trip_datetime?->format('d M Y') ?? '—' }}</div>
                                            <div class="hp-trip-time-clock">{{ $trip->trip_datetime?->format('H:i') ?? '' }}</div>
                                        </div>
                                        <div class="hp-trip-route-points">
                                            <div class="hp-trip-point">
                                                <span class="hp-trip-dot pickup"></span>
                                                <span>
                                                    <span class="hp-trip-point-label">Pickup</span>
                                                    <span class="hp-trip-point-text">{{ $pickupLabel }}</span>
                                                </span>
                                            </div>
                                            <div class="hp-trip-point">
                                                <span class="hp-trip-dot destination"></span>
                                                <span>
                                                    <span class="hp-trip-point-label">Destination</span>
                                                    <span class="hp-trip-point-text">{{ $destinationLabel }}</span>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="badge {{ $statusClass }}">{{ ucfirst($trip->status ?? 'pending') }}</span>
                                        <span class="hp-trip-seats"><i class="fa-solid fa-users" style="font-size:11px;color:var(--muted-2);"></i> {{ $seats }}/{{ $seatLimit }}</span>
                                        <div class="hp-trip-fare">
                                            <span class="hp-trip-fare-amount">RM {{ number_format((float) ($trip->fare_per_person ?? 0), 2) }}</span>
                                            <i class="fa-solid fa-chevron-right hp-trip-fare-chevron"></i>
                                        </div>
                                    </a>
                                    @endforeach
                                </div>
                            @else
                                <x-empty icon="fa-solid fa-car" title="No upcoming trips" body="Create or join a trip to get started.">
                                    <div style="margin-top:12px; display:flex; gap:8px; justify-content:center;">
                                        <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">Post a Trip</a>
                                        <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">Find a Ride</a>
                                    </div>
                                </x-empty>
                            @endif
                        </div>{{-- /hp-tab-passenger --}}
                        </div>{{-- /hp-trips-real --}}
                    </div>{{-- /hp-section-body --}}
                </div>{{-- /hp-section upcoming --}}

                {{-- Public trips near you --}}
                <div class="hp-section">
                    <div class="hp-section-head">
                        <h3 class="hp-section-title">Public trips near you</h3>
                        <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">Explore <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></a>
                    </div>
                    <div class="hp-section-body">
                        @php
                            $exploreSlice = ($publicExploreTrips ?? collect())->take(10);
                            $explorePages = $exploreSlice->chunk(4)->values();
                        @endphp
                        @if($exploreSlice->isNotEmpty())
                            <div class="hp-pub-carousel-track" id="hp-pub-desktop-carousel-track">
                                @foreach($explorePages as $page)
                                    <div class="hp-pub-carousel-slide">
                                        <div class="hp-pub-grid">
                                            @foreach($page as $trip)
                                                @php
                                                    $origin = $trip->pickup_name ?: 'Pickup';
                                                    $dest = $trip->destination_name ?: 'Destination';
                                                    $takenSeats = (int) $trip->participants->where('is_driver', false)->count();
                                                    $availSeats = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : 0;
                                                @endphp
                                                <a href="{{ route('explore.show', $trip->id) }}" class="hp-pub-mini" style="text-decoration:none;color:inherit;">
                                                    <div class="hp-pub-driver-row">
                                                        <span class="hp-pub-mobile-avatar">{{ strtoupper(substr($trip->driver?->name ?? 'U', 0, 2)) }}</span>
                                                        <span class="hp-pub-driver-name">{{ $trip->driver?->name ?? '-' }}</span>
                                                        <span class="hp-pub-driver-rating"><i class="fa-solid fa-star"></i> {{ number_format($trip->driver?->rating ?? 5.0, 2) }}</span>
                                                    </div>
                                                    <div class="hp-pub-divider"></div>
                                                    <div class="hp-pub-mobile-route">
                                                        <div class="hp-pub-point">
                                                            <span class="hp-pub-dot pickup"></span>
                                                            <span>
                                                                <span class="hp-pub-point-label">Pickup</span>
                                                                <span class="hp-pub-point-text">{{ $origin }}</span>
                                                            </span>
                                                        </div>
                                                        <div class="hp-pub-point">
                                                            <span class="hp-pub-dot destination"></span>
                                                            <span>
                                                                <span class="hp-pub-point-label">Destination</span>
                                                                <span class="hp-pub-point-text">{{ $dest }}</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="hp-pub-divider"></div>
                                                    <div class="hp-pub-mini-footer">
                                                        <span class="hp-pub-footer-meta">{{ $trip->trip_datetime?->format('d M Y, H:i') ?? '-' }} &middot; {{ $availSeats }} seat{{ $availSeats === 1 ? '' : 's' }}</span>
                                                        <span style="display:flex;align-items:center;gap:8px;">
                                                            <span class="hp-pub-mini-fare">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                                            <span class="btn btn-primary btn-sm">Request</span>
                                                        </span>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if($explorePages->count() > 1)
                                <div class="hp-pub-carousel-dots" id="hp-pub-desktop-carousel-dots">
                                    @for($i = 0; $i < $explorePages->count(); $i++)
                                        <button type="button" class="hp-pub-carousel-dot {{ $i === 0 ? 'active' : '' }}" aria-label="Go to page {{ $i + 1 }}" onclick="hpGoToCarouselPage('hp-pub-desktop-carousel-track', {{ $i }})"></button>
                                    @endfor
                                </div>
                            @endif
                        @else
                            <x-empty icon="fa-solid fa-car-side" title="No public trips near you" body="No public trips near you right now." />
                        @endif
                    </div>
                </div>

            </div>{{-- /left column --}}

            {{-- ── RIGHT COLUMN (1fr) ─────────────────────────────────────── --}}
            <div style="display:grid;gap:18px;align-content:start;">

                {{-- Quick actions --}}
                <div class="hp-section">
                    <div class="hp-section-head">
                        <h3 class="hp-section-title">Quick actions</h3>
                    </div>
                    <div class="hp-section-body">
                            @php
                                $qaDriver = [
                                    ['art' => 'quick-newtrip-3d.svg',    'label' => 'New Trip',     'route' => 'trips.create'],
                                    ['art' => 'quick-route-3d.svg',      'label' => 'Saved Routes', 'route' => 'saved-routes.index'],
                                    ['art' => 'quick-payment-3d.svg',    'label' => 'Payments',     'route' => 'payments.index', 'badge' => $unpaidCount],
                                    ['art' => 'quick-connection-3d.svg', 'label' => 'Connections',  'route' => 'connections.index'],
                                    ['art' => 'quick-explore-3d.svg',    'label' => 'Explore',      'route' => 'explore.index'],
                                    ['art' => 'quick-trip-3d.svg',       'label' => 'My Trips',     'route' => 'trips.index'],
                                ];
                                $qaPassenger = [
                                    ['art' => 'quick-explore-3d.svg',    'label' => 'Explore',     'route' => 'explore.index'],
                                    ['art' => 'quick-trip-3d.svg',       'label' => 'My Trips',    'route' => 'trips.index'],
                                    ['art' => 'quick-payment-3d.svg',    'label' => 'Payments',    'route' => 'payments.index', 'badge' => $unpaidCount],
                                    ['art' => 'quick-connection-3d.svg', 'label' => 'Connections', 'route' => 'connections.index'],
                                ];
                                $qaAdmin = [
                                    ['art' => 'quick-users-3d.svg',   'label' => 'Users',   'route' => 'admin.users.index'],
                                    ['art' => 'quick-summary-3d.svg', 'label' => 'Reports', 'route' => 'admin.reports.index'],
                                    ['art' => 'quick-trip-3d.svg',    'label' => 'Trips',   'route' => 'trips.index'],
                                    ['art' => 'quick-route-3d.svg',   'label' => 'Routes',  'route' => 'saved-routes.index'],
                                ];
                                $qaItems = match($role) {
                                    'passenger' => $qaPassenger,
                                    'admin'     => $qaAdmin,
                                    default     => $qaDriver,
                                };
                            @endphp
                            <div class="hp-quick-grid {{ count($qaItems) > 4 ? 'hp-quick-grid--wide' : '' }}">
                                @foreach($qaItems as $qa)
                                    <a href="{{ route($qa['route']) }}" class="hp-quick-item">
                                        @if(!empty($qa['badge']) && $qa['badge'] > 0)
                                            <span class="hp-quick-badge">{{ $qa['badge'] > 99 ? '99+' : $qa['badge'] }}</span>
                                        @endif
                                        <span class="hp-quick-icon">
                                            <img src="{{ asset('assets/illustrations/' . $qa['art']) }}" alt="" class="hp-quick-icon-img">
                                        </span>
                                        <span class="hp-quick-label">{{ $qa['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                {{-- Driver review queue --}}
                @if($role === 'driver')
                    <div class="hp-section">
                        <div class="hp-section-head">
                            <div>
                                <h3 class="hp-section-title">Driver review queue</h3>
                                <p style="margin:3px 0 0;font-size:12px;color:var(--muted);">Confirm passenger payments</p>
                            </div>
                        </div>
                        <div class="hp-section-body">
                            @if($reviewQueue->isNotEmpty())
                                @foreach($reviewQueue->take(5) as $item)
                                    @php
                                        $drMethodLabel = match ($item->payment_method ?? null) {
                                            'duitnow_qr' => 'DuitNow QR',
                                            'bank_account' => 'Bank Account',
                                            'digital_wallet' => 'Digital Wallet',
                                            'others' => 'Others',
                                            default => 'Payment',
                                        };
                                    @endphp
                                    <div class="hp-review-row">
                                        <div class="hp-review-avatar">
                                            {{ strtoupper(substr($item->passenger?->name ?? $item->user?->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div class="hp-review-name">{{ $item->passenger?->name ?? $item->user?->name ?? 'Passenger' }}</div>
                                            <div class="hp-review-sub">{{ $drMethodLabel }} &middot; {{ $item->updated_at?->diffForHumans() ?? '' }}</div>
                                        </div>
                                        <span class="hp-review-amount">RM {{ number_format((float) ($item->amount_due ?? $item->amount ?? $item->fare_per_person ?? 0), 2) }}</span>
                                        <a href="{{ route('payments.index', ['review_payment' => $item->id]) }}" class="btn btn-primary btn-sm" style="margin-left:8px;">Review</a>
                                    </div>
                                @endforeach
                            @else
                                {{-- Fallback: show pending count without detail rows --}}
                                <div style="padding:10px 0 4px;">
                                    <p style="font-size:13px;color:var(--warning-ink);font-weight:700;margin:0 0 4px;">
                                        {{ $pendingReviews }} request(s) awaiting review
                                    </p>
                                    <p style="font-size:12px;color:var(--muted);margin:0;">
                                        Open driver payments to review individual entries.
                                    </p>
                                </div>
                            @endif
                            <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--hairline);">
                                <a href="{{ route('payments.index', ['payment_filter' => 'review']) }}" class="btn btn-ghost btn-sm btn-block" style="justify-content:center;">
                                    Open Driver Review
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Pending requests alert for driver when no review queue card shown --}}
                @if($role === 'driver' && $pendingReviews > 0 && $reviewQueue->isEmpty())
                    {{-- already handled above via fallback --}}
                @elseif($role === 'driver' && ($pendingJoinRequests ?? 0) > 0 && empty($upcomingCreatedTrip) && $pendingReviews === 0)
                    <div class="hp-section" style="background:var(--warning-soft);border-color:rgba(180,83,9,.22);">
                        <div class="hp-section-body" style="display:flex;align-items:center;gap:14px;">
                            <div style="width:42px;height:42px;border-radius:var(--r-md);background:var(--warning);color:#fff;display:grid;place-items:center;font-size:18px;flex-shrink:0;">
                                <i class="fa-solid fa-inbox"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <p style="margin:0 0 2px;font-size:15px;font-weight:800;color:var(--warning-ink);">Pending Join Requests</p>
                                <p style="margin:0;font-size:13px;color:var(--warning);font-weight:600;">{{ (int) $pendingJoinRequests }} request(s) awaiting your approval</p>
                            </div>
                            <a href="{{ route('trips.index') }}" class="btn btn-sm" style="background:var(--surface);color:var(--warning-ink);border-color:rgba(180,83,9,.30);white-space:nowrap;">
                                Review <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endif

            </div>{{-- /right column --}}

        </div>{{-- /.hp-body --}}

    </div>{{-- /.hp-desktop-only --}}

    {{-- ════════════════════════════════════════════════════════════════════════
         MOBILE LAYOUT  (< 1024px)
    ════════════════════════════════════════════════════════════════════════ --}}
    <div class="hp-mobile-only">
        <div class="hp-mobile-wrap">

            {{-- Yellow gradient hero --}}
            <div class="hp-mobile-hero">
                <div>
                    <div class="hp-mobile-hero-eyebrow">{{ strtoupper($greeting) }}, {{ strtoupper(strtok($user->name, ' ') ?: $user->name) }}</div>
                    @if(!empty($nextTrip))
                        <h1 class="hp-mobile-hero-title">Your next trip is in {{ $nextTripCountdown ?? '0 h 0 min' }}</h1>
                        <p class="hp-mobile-hero-next">
                            {{ $nextRoute }} &middot; {{ $nextTrip->trip_datetime?->format('H:i') ?? '-' }}
                        </p>
                    @else
                        <h1 class="hp-mobile-hero-title">No upcoming trip yet</h1>
                        <p class="hp-mobile-hero-next">Create or join a trip to get started.</p>
                    @endif
                </div>
                <div class="hp-mobile-hero-actions">
                    <a href="{{ $mobileHeroPrimaryUrl }}" class="hp-mobile-cta dark">{{ $mobileHeroPrimaryLabel }}</a>
                    <a href="{{ $mobileHeroSecondaryUrl }}" class="hp-mobile-cta ghost">{{ $mobileHeroSecondaryLabel }}</a>
                </div>
            </div>

            {{-- 2-col mini stats --}}
            <div class="hp-mobile-stats">
                @if($role === 'passenger')
                    <a href="{{ route('payments.index') }}" class="hp-mobile-stat">
                        <span class="hp-mobile-stat-label">Outstanding</span>
                        <span class="hp-mobile-stat-value" style="font-size:18px;">RM {{ number_format((float) $unpaidAmount, 2) }}</span>
                        <span class="hp-mobile-stat-delta {{ $unpaidCount > 0 ? 'warning' : '' }}">{{ $unpaidCount > 0 ? $unpaidCount . ' unpaid' : 'All settled' }}</span>
                    </a>
                    <a href="{{ route('trips.index', ['date_from' => $stats['week_start'], 'date_to' => $stats['week_end']]) }}" class="hp-mobile-stat">
                        <span class="hp-mobile-stat-label">Trips this week</span>
                        <span class="hp-mobile-stat-value">{{ $tripsThisWeek }}</span>
                        <span class="hp-mobile-stat-delta">{{ $tripsThisMonth }} this month</span>
                    </a>
                @elseif($role === 'admin')
                    <a href="{{ route('trips.index') }}" class="hp-mobile-stat">
                        <span class="hp-mobile-stat-label">Total trips</span>
                        <span class="hp-mobile-stat-value">{{ $stats['total_trips'] ?? 0 }}</span>
                        <span class="hp-mobile-stat-delta">All time</span>
                    </a>
                    <a href="{{ route('payments.index', ['payment_filter' => 'review']) }}" class="hp-mobile-stat">
                        <span class="hp-mobile-stat-label">Reviews</span>
                        <span class="hp-mobile-stat-value">{{ $pendingReviews }}</span>
                        <span class="hp-mobile-stat-delta {{ $pendingReviews > 0 ? 'warning' : '' }}">{{ $pendingReviews > 0 ? 'Action needed' : 'Queue clear' }}</span>
                    </a>
                @else
                    <a href="{{ route('payments.index', ['date_from' => $stats['month_start'], 'date_to' => $stats['month_end']]) }}" class="hp-mobile-stat">
                        <span class="hp-mobile-stat-label">Monthly Earnings</span>
                        <span class="hp-mobile-stat-value" style="font-size:18px;">{{ str_replace('RM ', '', $totalEarnings) }}</span>
                        <span class="hp-mobile-stat-delta">Paid fares</span>
                    </a>
                    <a href="{{ route('payments.index', ['payment_filter' => 'review']) }}" class="hp-mobile-stat">
                        <span class="hp-mobile-stat-label">Reviews</span>
                        <span class="hp-mobile-stat-value">{{ $pendingReviews }}</span>
                        <span class="hp-mobile-stat-delta {{ $pendingReviews > 0 ? 'warning' : '' }}">{{ $pendingReviews > 0 ? 'Action needed' : 'Queue clear' }}</span>
                    </a>
                @endif
            </div>

            {{-- Quick actions: 4-col icon grid --}}
            @php
                $mobileQuick = match($role) {
                    'passenger' => [
                        ['art' => 'quick-explore-3d.svg',    'label' => 'Explore',     'route' => 'explore.index'],
                        ['art' => 'quick-trip-3d.svg',       'label' => 'My Trips',    'route' => 'trips.index'],
                        ['art' => 'quick-payment-3d.svg',    'label' => 'Payments',    'route' => 'payments.index'],
                        ['art' => 'quick-connection-3d.svg', 'label' => 'Connections', 'route' => 'connections.index'],
                    ],
                    'admin' => [
                        ['art' => 'quick-users-3d.svg',   'label' => 'Users',   'route' => 'admin.users.index'],
                        ['art' => 'quick-summary-3d.svg', 'label' => 'Reports', 'route' => 'admin.reports.index'],
                        ['art' => 'quick-trip-3d.svg',     'label' => 'Trips',   'route' => 'trips.index'],
                        ['art' => 'quick-route-3d.svg',    'label' => 'Routes',  'route' => 'saved-routes.index'],
                    ],
                    default => [
                        ['art' => 'quick-explore-3d.svg',    'label' => 'Explore',  'route' => 'explore.index'],
                        ['art' => 'quick-route-3d.svg',      'label' => 'Routes',   'route' => 'saved-routes.index'],
                        ['art' => 'quick-payment-3d.svg',    'label' => 'Payments', 'route' => 'payments.index'],
                        ['art' => 'quick-connection-3d.svg', 'label' => 'Connect',  'route' => 'connections.index'],
                    ],
                };
                $mobileExtraQuick = match($role) {
                    'passenger' => [
                        ['art' => 'quick-notification-3d.svg', 'label' => 'Notifications', 'route' => 'notifications.index'],
                        ['art' => 'quick-settings-3d.svg',     'label' => 'Settings', 'route' => 'settings.index'],
                    ],
                    'admin' => [
                        ['art' => 'quick-explore-3d.svg',      'label' => 'Explore', 'route' => 'explore.index'],
                        ['art' => 'quick-notification-3d.svg', 'label' => 'Notifications', 'route' => 'notifications.index'],
                        ['art' => 'quick-settings-3d.svg',     'label' => 'Settings', 'route' => 'settings.index'],
                    ],
                    default => [
                        ['art' => 'quick-newtrip-3d.svg',      'label' => 'New Trip', 'route' => 'trips.create'],
                        ['art' => 'quick-trip-3d.svg',         'label' => 'My Trips', 'route' => 'trips.index'],
                        ['art' => 'quick-notification-3d.svg', 'label' => 'Notifications', 'route' => 'notifications.index'],
                        ['art' => 'quick-settings-3d.svg',     'label' => 'Settings', 'route' => 'settings.index'],
                    ],
                };
                $mobileQuickAll = [...$mobileQuick, ...$mobileExtraQuick];
                $mobileQuickDots = count($mobileQuickAll) > 4 ? (int) ceil(count($mobileQuickAll) / 4) : 0;
            @endphp
            <div>
                <div class="hp-mobile-section-head">
                    <h3 class="hp-mobile-section-title">Quick actions</h3>
                </div>
                <div class="hp-mobile-quick" id="hp-mobile-quick-track">
                    @foreach($mobileQuickAll as $mqa)
                        <a href="{{ route($mqa['route']) }}" class="hp-mobile-quick-item">
                            @if(($mqa['route'] ?? '') === 'payments.index' && $unpaidCount > 0)
                                <span class="hp-mobile-quick-badge">{{ $unpaidCount > 99 ? '99+' : $unpaidCount }}</span>
                            @endif
                            <span class="hp-mobile-quick-icon">
                                <img src="{{ asset('assets/illustrations/' . $mqa['art']) }}" alt="" class="hp-mobile-quick-icon-img">
                            </span>
                            <span class="hp-mobile-quick-label">{{ $mqa['label'] }}</span>
                        </a>
                    @endforeach
                </div>
                @if($mobileQuickDots > 1)
                    <div class="hp-mobile-quick-dots" id="hp-mobile-quick-dots">
                        @for($i = 0; $i < $mobileQuickDots; $i++)
                            <button type="button" class="hp-mobile-quick-dot {{ $i === 0 ? 'active' : '' }}" aria-label="Go to quick actions page {{ $i + 1 }}" onclick="hpGoToQuickPage({{ $i }})"></button>
                        @endfor
                    </div>
                @endif
            </div>

            {{-- Upcoming trips --}}
            <div>
                <div class="hp-mobile-section-head">
                    <h3 class="hp-mobile-section-title">Upcoming trips</h3>
                    @if($role !== 'passenger')
                        <div class="hp-tabs" id="hp-mobile-trip-tabs">
                            <button type="button" class="hp-tab active" onclick="hpSwitchTab('driver', this, 'hp-mobile')">As driver</button>
                            <button type="button" class="hp-tab" onclick="hpSwitchTab('passenger', this, 'hp-mobile')">As passenger</button>
                        </div>
                    @endif
                </div>
                <div id="hp-mobile-tab-driver" @if($role === 'passenger') style="display:none;" @endif>
                    @if($upcomingCreatedTrips->isNotEmpty())
                        <div style="display:grid;gap:10px;">
                            @foreach($upcomingCreatedTrips->take(4) as $trip)
                                @php
                                    $mUpPickup = $trip->pickup_name ?: 'Pickup';
                                    $mUpDest = $trip->destination_name ?: 'Destination';
                                    $mUpSeats = $trip->participants->where('is_driver', false)->count();
                                    $mUpDriverIncluded = (int) $trip->participant_count > $mUpSeats;
                                    $mUpSeatLimit = $trip->seat_limit !== null ? ((int) $trip->seat_limit + ($mUpDriverIncluded ? 1 : 0)) : '?';
                                    $mUpSeats = $mUpSeats + ($mUpDriverIncluded ? 1 : 0);
                                    $mUpStatusClass = $trip->status === 'active' ? 'badge-success' : ($trip->status === 'cancelled' ? 'badge-danger' : 'badge-info');
                                @endphp
                                <a href="{{ route('trips.index', ['focus_trip' => $trip->id]) }}" class="hp-pub-mini" style="text-decoration:none;color:inherit;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span class="badge {{ $mUpStatusClass }}">{{ ucfirst($trip->status ?? 'pending') }}</span>
                                        <span class="hp-pub-mobile-time">{{ $trip->trip_datetime?->format('d M, H:i') ?? '-' }}</span>
                                    </div>
                                    <div class="hp-pub-mobile-route">
                                        <div class="hp-pub-point">
                                            <span class="hp-pub-dot pickup"></span>
                                            <span>
                                                <span class="hp-pub-point-label">Pickup</span>
                                                <span class="hp-pub-point-text">{{ $mUpPickup }}</span>
                                            </span>
                                        </div>
                                        <div class="hp-pub-point">
                                            <span class="hp-pub-dot destination"></span>
                                            <span>
                                                <span class="hp-pub-point-label">Destination</span>
                                                <span class="hp-pub-point-text">{{ $mUpDest }}</span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="hp-pub-mini-footer">
                                        <span class="hp-pub-mini-meta"><i class="fa-solid fa-users" style="font-size:11px;"></i> {{ $mUpSeats }}/{{ $mUpSeatLimit }} seats</span>
                                        <span class="hp-pub-mini-fare">RM {{ number_format((float) ($trip->fare_per_person ?? 0), 2) }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <x-empty icon="fa-solid fa-car" title="No upcoming trips" body="Create or join a trip to get started.">
                            <div style="margin-top:12px; display:flex; gap:8px; justify-content:center;">
                                <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">Post a Trip</a>
                                <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">Find a Ride</a>
                            </div>
                        </x-empty>
                    @endif
                </div>
                <div id="hp-mobile-tab-passenger" style="display:{{ $role === 'passenger' ? 'block' : 'none' }};">
                    @if($upcomingJoinedTrips->isNotEmpty())
                        <div style="display:grid;gap:10px;">
                            @foreach($upcomingJoinedTrips->take(4) as $trip)
                                @php
                                    $mUpPickup = $trip->pickup_name ?: 'Pickup';
                                    $mUpDest = $trip->destination_name ?: 'Destination';
                                    $mUpSeats = $trip->participants->where('is_driver', false)->count();
                                    $mUpDriverIncluded = (int) $trip->participant_count > $mUpSeats;
                                    $mUpSeatLimit = $trip->seat_limit !== null ? ((int) $trip->seat_limit + ($mUpDriverIncluded ? 1 : 0)) : '?';
                                    $mUpSeats = $mUpSeats + ($mUpDriverIncluded ? 1 : 0);
                                    $mUpStatusClass = $trip->status === 'active' ? 'badge-success' : ($trip->status === 'cancelled' ? 'badge-danger' : 'badge-info');
                                @endphp
                                <a href="{{ route('trips.index', ['focus_trip' => $trip->id]) }}" class="hp-pub-mini" style="text-decoration:none;color:inherit;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span class="badge {{ $mUpStatusClass }}">{{ ucfirst($trip->status ?? 'pending') }}</span>
                                        <span class="hp-pub-mobile-time">{{ $trip->trip_datetime?->format('d M, H:i') ?? '-' }}</span>
                                    </div>
                                    <div class="hp-pub-mobile-route">
                                        <div class="hp-pub-point">
                                            <span class="hp-pub-dot pickup"></span>
                                            <span>
                                                <span class="hp-pub-point-label">Pickup</span>
                                                <span class="hp-pub-point-text">{{ $mUpPickup }}</span>
                                            </span>
                                        </div>
                                        <div class="hp-pub-point">
                                            <span class="hp-pub-dot destination"></span>
                                            <span>
                                                <span class="hp-pub-point-label">Destination</span>
                                                <span class="hp-pub-point-text">{{ $mUpDest }}</span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="hp-pub-mini-footer">
                                        <span class="hp-pub-mini-meta"><i class="fa-solid fa-users" style="font-size:11px;"></i> {{ $mUpSeats }}/{{ $mUpSeatLimit }} seats</span>
                                        <span class="hp-pub-mini-fare">RM {{ number_format((float) ($trip->fare_per_person ?? 0), 2) }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <x-empty icon="fa-solid fa-car" title="No upcoming trips" body="Create or join a trip to get started.">
                            <div style="margin-top:12px; display:flex; gap:8px; justify-content:center;">
                                <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">Post a Trip</a>
                                <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">Find a Ride</a>
                            </div>
                        </x-empty>
                    @endif
                </div>
            </div>

            {{-- Driver review queue --}}
            @if($role === 'driver' && $pendingReviews > 0)
                <div class="hp-mobile-section-head">
                    <h3 class="hp-mobile-section-title">Driver review queue</h3>
                    <a href="{{ route('payments.index', ['payment_filter' => 'review']) }}" class="btn btn-ghost btn-sm">
                        Review <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                    </a>
                </div>
                <div style="background:var(--surface);border:1px solid var(--hairline);border-radius:16px;padding:14px 16px 16px;box-shadow:var(--shadow-1);">
                    <div>
                        @if($reviewQueue->isNotEmpty())
                            @foreach($reviewQueue->take(3) as $item)
                                @php
                                    $mrMethodLabel = match ($item->payment_method ?? null) {
                                        'duitnow_qr' => 'DuitNow QR',
                                        'bank_account' => 'Bank Account',
                                        'digital_wallet' => 'Digital Wallet',
                                        'others' => 'Others',
                                        default => 'Payment',
                                    };
                                @endphp
                                <a href="{{ route('payments.index', ['review_payment' => $item->id]) }}" class="hp-review-row" style="text-decoration:none;color:inherit;">
                                    <div class="hp-review-avatar">
                                        {{ strtoupper(substr($item->passenger?->name ?? $item->user?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div class="hp-review-name">{{ $item->passenger?->name ?? $item->user?->name ?? 'Passenger' }}</div>
                                        <div class="hp-review-sub">{{ $mrMethodLabel }} &middot; {{ $item->updated_at?->diffForHumans() ?? '' }}</div>
                                    </div>
                                    <span class="hp-review-amount">RM {{ number_format((float) ($item->amount_due ?? $item->amount ?? $item->fare_per_person ?? 0), 2) }}</span>
                                    <i class="fa-solid fa-chevron-right" style="font-size:11px;color:var(--muted-2);margin-left:6px;"></i>
                                </a>
                            @endforeach
                        @else
                            <p style="font-size:12px;color:var(--warning-ink);font-weight:700;margin:6px 0 0;">
                                {{ $pendingReviews }} request(s) awaiting review
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Public trips today --}}
            <div class="hp-section">
                <div class="hp-mobile-section-head">
                    <h3 class="hp-mobile-section-title">Public trips today</h3>
                    <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">
                        Explore
                        <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                    </a>
                </div>
                <div class="hp-section-body">
                    @php $mobileExplore = ($publicExploreTrips ?? collect())->take(10); @endphp
                    @if($mobileExplore->isNotEmpty())
                        <div class="hp-pub-carousel-track" id="hp-pub-mobile-carousel-track">
                            @foreach($mobileExplore as $trip)
                                @php
                                    $origin = $trip->pickup_name ?: 'Pickup';
                                    $dest = $trip->destination_name ?: 'Destination';
                                    $takenSeats = (int) $trip->participants->where('is_driver', false)->count();
                                    $seatCount = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : 0;
                                @endphp
                                <div class="hp-pub-carousel-slide">
                                    <a href="{{ route('explore.show', $trip->id) }}" class="hp-pub-mini" style="text-decoration:none;color:inherit;">
                                        <div class="hp-pub-driver-row">
                                            <span class="hp-pub-mobile-avatar">{{ strtoupper(substr($trip->driver?->name ?? 'U', 0, 2)) }}</span>
                                            <span class="hp-pub-driver-name">{{ $trip->driver?->name ?? '-' }}</span>
                                            <span class="hp-pub-driver-rating"><i class="fa-solid fa-star"></i> {{ number_format($trip->driver?->rating ?? 5.0, 2) }}</span>
                                        </div>
                                        <div class="hp-pub-divider"></div>
                                        <div class="hp-pub-mobile-route">
                                            <div class="hp-pub-point">
                                                <span class="hp-pub-dot pickup"></span>
                                                <span>
                                                    <span class="hp-pub-point-label">Pickup</span>
                                                    <span class="hp-pub-point-text">{{ $origin }}</span>
                                                </span>
                                            </div>
                                            <div class="hp-pub-point">
                                                <span class="hp-pub-dot destination"></span>
                                                <span>
                                                    <span class="hp-pub-point-label">Destination</span>
                                                    <span class="hp-pub-point-text">{{ $dest }}</span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="hp-pub-divider"></div>
                                        <div class="hp-pub-mini-footer">
                                            <span class="hp-pub-footer-meta">{{ $trip->trip_datetime?->format('d M Y, H:i') ?? '-' }} &middot; {{ $seatCount }} seat{{ $seatCount === 1 ? '' : 's' }}</span>
                                            <span style="display:flex;align-items:center;gap:8px;">
                                                <span class="hp-pub-mini-fare">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                                <span class="btn btn-primary btn-sm">Request</span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        @if($mobileExplore->count() > 1)
                            <div class="hp-pub-carousel-dots" id="hp-pub-mobile-carousel-dots">
                                @for($i = 0; $i < $mobileExplore->count(); $i++)
                                    <button type="button" class="hp-pub-carousel-dot {{ $i === 0 ? 'active' : '' }}" aria-label="Go to trip {{ $i + 1 }}" onclick="hpGoToCarouselPage('hp-pub-mobile-carousel-track', {{ $i }})"></button>
                                @endfor
                            </div>
                        @endif
                    @else
                        <x-empty icon="fa-solid fa-car-side" title="No public trips today" body="No public trips available right now." />
                    @endif
                </div>
            </div>

        </div>{{-- /.hp-mobile-wrap --}}
    </div>{{-- /.hp-mobile-only --}}

    <script src="{{ asset('js/home.js') }}?v={{ filemtime(public_path('js/home.js')) }}"></script>
@endsection
