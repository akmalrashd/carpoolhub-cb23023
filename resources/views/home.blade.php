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

<div id="page-content-wrapper" 
    @if($initialLoad ?? false) 
        hx-get="{{ request()->fullUrl() }}" 
        hx-trigger="load" 
        hx-swap="outerHTML" 
        hx-select="#page-content-wrapper" 
    @endif
>

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
                <a href="{{ route('trips.index') }}" class="btn btn-ghost btn-sm">
                    <i class="fa-regular fa-calendar-days"></i> This week
                </a>
                <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Create Trip
                </a>
            </div>
        </div>

        {{-- 2. Stats strip -------------------------------------------------- --}}
        <div class="hp-stats-skel-wrap">
            {{-- Skeleton overlay: absolutely positioned over real stats --}}
            <div id="hp-stats-skel-overlay" style="{{ ($initialLoad ?? false) ? 'display:block;' : 'display:none;' }}">
                <div class="hp-stats-strip" style="position:relative;z-index:1;">
                    @for($sk = 0; $sk < 4; $sk++)
                        <div class="hp-stat-card" style="pointer-events:none;">
                            <span class="sk" style="width:32px;height:32px;border-radius:var(--r-sm);position:absolute;top:16px;right:16px;"></span>
                            <span class="sk" style="height:11px;width:52%;margin-top:2px;"></span>
                            <span class="sk" style="height:28px;width:38%;margin-top:4px;border-radius:var(--r-md);"></span>
                            <span class="sk" style="height:10px;width:42%;"></span>
                        </div>
                    @endfor
                </div>
            </div>
            {{-- Real stats strip (always visible, provides natural height) --}}
        <div class="hp-stats-strip">

            {{-- Card 1: Trips this week --}}
            <div class="hp-stat-card">
                <div class="hp-stat-icon"><i class="fa-solid fa-car"></i></div>
                <span class="hp-stat-label">Trips this week</span>
                <span class="hp-stat-value">{{ $tripsThisWeek }}</span>
                <span class="hp-stat-delta">{{ $tripsThisMonth }} this month</span>
            </div>

            @if($role === 'passenger')
                {{-- Passenger Card 2: Outstanding payments --}}
                <div class="hp-stat-card {{ $unpaidCount > 0 ? 'warning-tone' : '' }}">
                    <div class="hp-stat-icon"><i class="fa-solid fa-wallet"></i></div>
                    <span class="hp-stat-label">Outstanding</span>
                    <span class="hp-stat-value" style="font-size:20px;{{ $unpaidCount > 0 ? 'color:var(--warning-ink)' : '' }}">
                        RM {{ number_format((float) $unpaidAmount, 2) }}
                    </span>
                    <span class="hp-stat-delta">{{ $unpaidCount > 0 ? $unpaidCount . ' unpaid fare' . ($unpaidCount !== 1 ? 's' : '') : 'All fares settled' }}</span>
                </div>

                {{-- Passenger Card 3: Connections --}}
                <div class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-user-group"></i></div>
                    <span class="hp-stat-label">Connections</span>
                    <span class="hp-stat-value">{{ $stats['total_trips'] ?? 0 }}</span>
                    <span class="hp-stat-delta">Trips joined total</span>
                </div>

                {{-- Passenger Card 4: Pending requests --}}
                <div class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-paper-plane"></i></div>
                    <span class="hp-stat-label">Join requests</span>
                    <span class="hp-stat-value">{{ $pendingRequests }}</span>
                    <span class="hp-stat-delta">{{ $pendingRequests > 0 ? 'Awaiting driver response' : 'None pending' }}</span>
                </div>

            @elseif($role === 'admin')
                {{-- Admin Card 2: Total trips --}}
                <div class="hp-stat-card highlighted">
                    <div class="hp-stat-icon"><i class="fa-solid fa-route"></i></div>
                    <span class="hp-stat-label">Total trips</span>
                    <span class="hp-stat-value">{{ $stats['total_trips'] ?? 0 }}</span>
                    <span class="hp-stat-delta">All time</span>
                </div>

                {{-- Admin Card 3: Payment reviews --}}
                <div class="hp-stat-card {{ $pendingReviews > 0 ? 'warning-tone' : '' }}">
                    <div class="hp-stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <span class="hp-stat-label">Payment review</span>
                    <span class="hp-stat-value" style="{{ $pendingReviews > 0 ? 'color:var(--warning-ink)' : '' }}">{{ $pendingReviews }}</span>
                    <span class="hp-stat-delta">{{ $pendingReviews > 0 ? 'Awaiting confirmation' : 'Queue clear' }}</span>
                </div>

                {{-- Admin Card 4: Pending requests --}}
                <div class="hp-stat-card">
                    <div class="hp-stat-icon"><i class="fa-solid fa-inbox"></i></div>
                    <span class="hp-stat-label">Join requests</span>
                    <span class="hp-stat-value">{{ $pendingRequests }}</span>
                    <span class="hp-stat-delta">Platform-wide pending</span>
                </div>

            @else
                {{-- Driver Card 2: Earnings this month --}}
                <div class="hp-stat-card highlighted">
                    <div class="hp-stat-icon"><i class="fa-solid fa-receipt"></i></div>
                    <span class="hp-stat-label">Earnings &middot; Month</span>
                    <span class="hp-stat-value" style="font-size:20px;">{{ $totalEarnings }}</span>
                    <span class="hp-stat-delta">Paid fares this month</span>
                </div>

                {{-- Driver Card 3: Payment review queue --}}
                <div class="hp-stat-card {{ $pendingReviews > 0 ? 'warning-tone' : '' }}">
                    <div class="hp-stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <span class="hp-stat-label">Payment review</span>
                    <span class="hp-stat-value" style="{{ $pendingReviews > 0 ? 'color:var(--warning-ink)' : '' }}">{{ $pendingReviews }}</span>
                    <span class="hp-stat-delta">{{ $pendingReviews > 0 ? 'Awaiting your approval' : 'Queue clear' }}</span>
                </div>

                {{-- Driver Card 4: Pending join requests --}}
                <div class="hp-stat-card {{ $pendingRequests > 0 ? 'warning-tone' : '' }}">
                    <div class="hp-stat-icon"><i class="fa-solid fa-inbox"></i></div>
                    <span class="hp-stat-label">Join requests</span>
                    <span class="hp-stat-value" style="{{ $pendingRequests > 0 ? 'color:var(--warning-ink)' : '' }}">{{ $pendingRequests }}</span>
                    <span class="hp-stat-delta">{{ $pendingRequests > 0 ? 'Awaiting your response' : 'None pending' }}</span>
                </div>
            @endif

        </div>{{-- /hp-stats-strip real --}}
        </div>{{-- /hp-stats-skel-wrap --}}

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
                        {{-- Skeleton: shown briefly then fades --}}
                        <div id="hp-trips-skel" style="{{ ($initialLoad ?? false) ? 'display:block;' : 'display:none;' }}">
                            @for($sk = 0; $sk < 3; $sk++)
                                <div style="display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:{{ $sk < 2 ? '1px solid var(--hairline)' : 'none' }};">
                                    <div style="display:grid;gap:5px;width:52px;flex-shrink:0;">
                                        <span class="sk" style="height:11px;width:100%;"></span>
                                        <span class="sk" style="height:10px;width:70%;"></span>
                                    </div>
                                    <div style="flex:1;display:grid;gap:7px;">
                                        <span class="sk" style="height:12px;width:{{ [72,60,80][$sk] }}%;"></span>
                                        <span class="sk" style="height:11px;width:{{ [55,68,50][$sk] }}%;"></span>
                                    </div>
                                    <span class="sk" style="height:24px;width:58px;border-radius:var(--r-pill);flex-shrink:0;"></span>
                                </div>
                            @endfor
                        </div>
                        {{-- Real content: hidden then fades in --}}
                        <div id="hp-trips-real" style="{{ ($initialLoad ?? false) ? 'display:none;' : 'display:block;' }}">
                        @if(!($initialLoad ?? false))
                        {{-- As driver panel --}}
                        <div id="hp-tab-driver">
                            @if($upcomingCreatedTrips->isNotEmpty())
                                <div class="hp-trip-list">
                                    @foreach($upcomingCreatedTrips as $trip)
                                    @php
                                        $pickupLabel = $trip->pickup_name ?: 'Pickup';
                                        $destinationLabel = $trip->destination_name ?: 'Destination';
                                        $seats = $trip->participants->where('is_driver', false)->count();
                                        $seatLimit = $trip->seat_limit ?? '?';
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
                                <x-empty icon="fa-solid fa-car" title="No upcoming trips" body="Create or join a trip to get started." />
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
                                        $seatLimit = $trip->seat_limit ?? '?';
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
                                <x-empty icon="fa-solid fa-car" title="No upcoming trips" body="Create or join a trip to get started." />
                            @endif
                        </div>{{-- /hp-tab-passenger --}}
                        @endif
                        </div>{{-- /hp-trips-real --}}
                    </div>{{-- /hp-section-body --}}
                </div>{{-- /hp-section upcoming --}}

                {{-- Public trips near you --}}
                <div class="hp-section">
                    <div class="hp-section-head">
                        <h3 class="hp-section-title">Public trips near you</h3>
                        <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">Open Explore</a>
                    </div>
                    <div class="hp-section-body">
                        @php $exploreSlice = ($publicExploreTrips ?? collect())->take(4); @endphp
                        @if($exploreSlice->isNotEmpty())
                            <div class="hp-pub-grid">
                                @foreach($exploreSlice as $trip)
                                    @php
                                        $origin = $trip->pickup_name ?: 'Pickup';
                                        $dest = $trip->destination_name ?: 'Destination';
                                        $takenSeats = (int) $trip->participants->where('is_driver', false)->count();
                                        $availSeats = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : 0;
                                    @endphp
                                    <div class="hp-pub-mini">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <span class="badge badge-info"><span class="dot"></span>Public &middot; {{ $availSeats }} seats</span>
                                            <span class="hp-pub-mobile-time">{{ $trip->trip_datetime?->format('H:i') ?? '-' }}</span>
                                        </div>
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
                                        <div class="hp-pub-mini-footer">
                                        <span class="hp-pub-mobile-driver">
                                            <span class="hp-pub-mobile-avatar">{{ strtoupper(substr($trip->driver?->name ?? 'U', 0, 2)) }}</span>
                                            <span>{{ $trip->driver?->name ?? '-' }}</span>
                                        </span>
                                            <span class="hp-pub-mini-fare">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                            <a href="{{ route('explore.show', $trip->id) }}" class="btn btn-primary btn-sm">Request</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-empty icon="fa-solid fa-car-side" title="No public trips near you" body="No public trips near you right now." style="box-shadow:none; border:none; background:transparent; padding:24px 0;" />
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
                        <div class="hp-quick-grid">
                            @php
                                $qaDriver = [
                                    ['icon' => 'fa-solid fa-plus',           'label' => 'New Trip',     'route' => 'trips.create',         'yellow' => true],
                                    ['icon' => 'fa-solid fa-route',          'label' => 'Saved Routes', 'route' => 'saved-routes.index',   'yellow' => false],
                                    ['icon' => 'fa-solid fa-credit-card',    'label' => 'Payments',     'route' => 'payments.index',       'yellow' => false, 'badge' => $pendingReviews],
                                    ['icon' => 'fa-solid fa-users',          'label' => 'Connections',  'route' => 'connections.index',    'yellow' => false],
                                ];
                                $qaPassenger = [
                                    ['icon' => 'fa-solid fa-magnifying-glass-location', 'label' => 'Explore',     'route' => 'explore.index',  'yellow' => true],
                                    ['icon' => 'fa-solid fa-car',                       'label' => 'My Trips',    'route' => 'trips.index',    'yellow' => false],
                                    ['icon' => 'fa-solid fa-credit-card',               'label' => 'Payments',    'route' => 'payments.index', 'yellow' => false],
                                    ['icon' => 'fa-solid fa-users',                     'label' => 'Connections', 'route' => 'connections.index', 'yellow' => false],
                                ];
                                $qaAdmin = [
                                    ['icon' => 'fa-solid fa-users-gear',  'label' => 'Users',   'route' => 'admin.users.index',   'yellow' => true],
                                    ['icon' => 'fa-solid fa-chart-line',  'label' => 'Reports', 'route' => 'admin.reports.index', 'yellow' => false],
                                    ['icon' => 'fa-solid fa-car',         'label' => 'Trips',   'route' => 'trips.index',         'yellow' => false],
                                    ['icon' => 'fa-solid fa-route',       'label' => 'Routes',  'route' => 'saved-routes.index',  'yellow' => false],
                                ];
                                $qaItems = match($role) {
                                    'passenger' => $qaPassenger,
                                    'admin'     => $qaAdmin,
                                    default     => $qaDriver,
                                };
                            @endphp
                            @foreach($qaItems as $qa)
                                <a href="{{ route($qa['route']) }}" class="hp-quick-item">
                                    @if(!empty($qa['badge']) && $qa['badge'] > 0)
                                        <span class="hp-quick-badge">{{ $qa['badge'] > 99 ? '99+' : $qa['badge'] }}</span>
                                    @endif
                                    <span class="hp-quick-icon {{ $qa['yellow'] ? 'yellow' : '' }}">
                                        <i class="{{ $qa['icon'] }}"></i>
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
                                    <div class="hp-review-row">
                                        <div class="hp-review-avatar">
                                            {{ strtoupper(substr($item->passenger?->name ?? $item->user?->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div class="hp-review-name">{{ $item->passenger?->name ?? $item->user?->name ?? 'Passenger' }}</div>
                                            <div class="hp-review-sub">{{ $item->payment_method ?? 'Payment' }} &middot; {{ $item->updated_at?->diffForHumans() ?? '' }}</div>
                                        </div>
                                        <span class="hp-review-amount">RM {{ number_format((float) ($item->amount_due ?? $item->amount ?? $item->fare_per_person ?? 0), 2) }}</span>
                                        <a href="{{ route('payments.index') }}" class="btn btn-primary btn-sm" style="margin-left:8px;">Review</a>
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
                                <a href="{{ route('payments.index') }}" class="btn btn-ghost btn-sm btn-block">
                                    Open Driver Review <i class="fa-solid fa-arrow-right"></i>
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
                    <div class="hp-mobile-stat {{ $unpaidCount > 0 ? 'hp-mobile-stat--warn' : '' }}" style="{{ $unpaidCount > 0 ? 'background:var(--warning-soft);border-color:rgba(180,83,9,0.25);' : '' }}">
                        <span class="hp-mobile-stat-label">Outstanding</span>
                        <span class="hp-mobile-stat-value" style="font-size:18px;">RM {{ number_format((float) $unpaidAmount, 2) }}</span>
                        <span class="hp-mobile-stat-delta {{ $unpaidCount > 0 ? 'warning' : '' }}">{{ $unpaidCount > 0 ? $unpaidCount . ' unpaid' : 'All settled' }}</span>
                    </div>
                    <div class="hp-mobile-stat">
                        <span class="hp-mobile-stat-label">Trips this week</span>
                        <span class="hp-mobile-stat-value">{{ $tripsThisWeek }}</span>
                        <span class="hp-mobile-stat-delta">{{ $tripsThisMonth }} this month</span>
                    </div>
                @elseif($role === 'admin')
                    <div class="hp-mobile-stat">
                        <span class="hp-mobile-stat-label">Total trips</span>
                        <span class="hp-mobile-stat-value">{{ $stats['total_trips'] ?? 0 }}</span>
                        <span class="hp-mobile-stat-delta">All time</span>
                    </div>
                    <div class="hp-mobile-stat {{ $pendingReviews > 0 ? 'hp-mobile-stat--warn' : '' }}" style="{{ $pendingReviews > 0 ? 'background:var(--warning-soft);border-color:rgba(180,83,9,0.25);' : '' }}">
                        <span class="hp-mobile-stat-label">Reviews</span>
                        <span class="hp-mobile-stat-value">{{ $pendingReviews }}</span>
                        <span class="hp-mobile-stat-delta {{ $pendingReviews > 0 ? 'warning' : '' }}">{{ $pendingReviews > 0 ? 'Action needed' : 'Queue clear' }}</span>
                    </div>
                @else
                    <div class="hp-mobile-stat">
                        <span class="hp-mobile-stat-label">Earnings &middot; Month</span>
                        <span class="hp-mobile-stat-value" style="font-size:18px;">{{ str_replace('RM ', '', $totalEarnings) }}</span>
                        <span class="hp-mobile-stat-delta">Paid fares</span>
                    </div>
                    <div class="hp-mobile-stat {{ $pendingReviews > 0 ? 'hp-mobile-stat--warn' : '' }}" style="{{ $pendingReviews > 0 ? 'background:var(--warning-soft);border-color:rgba(180,83,9,0.25);' : '' }}">
                        <span class="hp-mobile-stat-label">Reviews</span>
                        <span class="hp-mobile-stat-value">{{ $pendingReviews }}</span>
                        <span class="hp-mobile-stat-delta {{ $pendingReviews > 0 ? 'warning' : '' }}">{{ $pendingReviews > 0 ? 'Action needed' : 'Queue clear' }}</span>
                    </div>
                @endif
            </div>

            {{-- Quick actions: 4-col icon grid --}}
            @php
                $mobileQuick = match($role) {
                    'passenger' => [
                        ['icon' => 'fa-solid fa-magnifying-glass-location', 'label' => 'Explore',     'route' => 'explore.index',     'yellow' => true],
                        ['icon' => 'fa-solid fa-car',                       'label' => 'My Trips',    'route' => 'trips.index',       'yellow' => false],
                        ['icon' => 'fa-solid fa-credit-card',               'label' => 'Payments',    'route' => 'payments.index',    'yellow' => false],
                        ['icon' => 'fa-solid fa-users',                     'label' => 'Connections', 'route' => 'connections.index', 'yellow' => false],
                    ],
                    'admin' => [
                        ['icon' => 'fa-solid fa-users-gear',  'label' => 'Users',   'route' => 'admin.users.index',   'yellow' => true],
                        ['icon' => 'fa-solid fa-chart-line',  'label' => 'Reports', 'route' => 'admin.reports.index', 'yellow' => false],
                        ['icon' => 'fa-solid fa-car',         'label' => 'Trips',   'route' => 'trips.index',         'yellow' => false],
                        ['icon' => 'fa-solid fa-route',       'label' => 'Routes',  'route' => 'saved-routes.index',  'yellow' => false],
                    ],
                    default => [
                        ['icon' => 'fa-solid fa-compass',     'label' => 'Explore',   'route' => 'explore.index',      'yellow' => false],
                        ['icon' => 'fa-solid fa-route',       'label' => 'Routes',    'route' => 'saved-routes.index', 'yellow' => false],
                        ['icon' => 'fa-solid fa-receipt',     'label' => 'Payments',  'route' => 'payments.index',     'yellow' => false],
                        ['icon' => 'fa-solid fa-users',       'label' => 'Connect',   'route' => 'connections.index',  'yellow' => false],
                    ],
                };
                $mobileExtraQuick = match($role) {
                    'passenger' => [
                        ['icon' => 'fa-solid fa-bell', 'label' => 'Notifications', 'route' => 'notifications.index'],
                        ['icon' => 'fa-solid fa-user-gear', 'label' => 'Settings', 'route' => 'settings.index'],
                    ],
                    'admin' => [
                        ['icon' => 'fa-solid fa-compass', 'label' => 'Explore', 'route' => 'explore.index'],
                        ['icon' => 'fa-solid fa-bell', 'label' => 'Notifications', 'route' => 'notifications.index'],
                        ['icon' => 'fa-solid fa-user-gear', 'label' => 'Settings', 'route' => 'settings.index'],
                    ],
                    default => [
                        ['icon' => 'fa-solid fa-plus', 'label' => 'New Trip', 'route' => 'trips.create'],
                        ['icon' => 'fa-solid fa-car-side', 'label' => 'My Trips', 'route' => 'trips.index'],
                        ['icon' => 'fa-solid fa-bell', 'label' => 'Notifications', 'route' => 'notifications.index'],
                        ['icon' => 'fa-solid fa-user-gear', 'label' => 'Settings', 'route' => 'settings.index'],
                    ],
                };
            @endphp
            <div>
                <div class="hp-mobile-section-head">
                    <h3 class="hp-mobile-section-title">Quick actions</h3>
                    <button type="button" class="hp-mobile-section-link" onclick="hpToggleMobileActions(this)">View all</button>
                </div>
            <div class="hp-mobile-quick">
                @foreach($mobileQuick as $mqa)
                    <a href="{{ route($mqa['route']) }}" class="hp-mobile-quick-item">
                        @if(($mqa['route'] ?? '') === 'payments.index' && $pendingReviews > 0)
                            <span class="hp-mobile-quick-badge">{{ $pendingReviews > 99 ? '99+' : $pendingReviews }}</span>
                        @endif
                        <span class="hp-mobile-quick-icon {{ $mqa['yellow'] ? 'yellow' : '' }}">
                            <i class="{{ $mqa['icon'] }}"></i>
                        </span>
                        <span class="hp-mobile-quick-label">{{ $mqa['label'] }}</span>
                    </a>
                @endforeach
            </div>
            <div class="hp-mobile-quick hp-mobile-extra-actions" id="hp-mobile-extra-actions">
                @foreach($mobileExtraQuick as $mqa)
                    <a href="{{ route($mqa['route']) }}" class="hp-mobile-quick-item">
                        <span class="hp-mobile-quick-icon">
                            <i class="{{ $mqa['icon'] }}"></i>
                        </span>
                        <span class="hp-mobile-quick-label">{{ $mqa['label'] }}</span>
                    </a>
                @endforeach
            </div>
            </div>

            {{-- Public trips today --}}
            <div class="hp-section">
                <div class="hp-mobile-section-head">
                    <h3 class="hp-mobile-section-title">Public trips today</h3>
                    <a href="{{ route('explore.index') }}" class="hp-mobile-section-link">Explore &rarr;</a>
                </div>
                <div class="hp-section-body">
                    @php $mobileExplore = ($publicExploreTrips ?? collect())->take(4); @endphp
                    @if($mobileExplore->isNotEmpty())
                        <div style="display:grid;gap:10px;">
                            @foreach($mobileExplore as $trip)
                                @php
                                    $origin = $trip->pickup_name ?: 'Pickup';
                                    $dest = $trip->destination_name ?: 'Destination';
                                    $takenSeats = (int) $trip->participants->where('is_driver', false)->count();
                                    $seatCount = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : 0;
                                @endphp
                                <div class="hp-pub-mini">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span class="badge badge-info"><span class="dot"></span>Public &middot; {{ $seatCount }} seats</span>
                                        <span class="hp-pub-mobile-time">{{ $trip->trip_datetime?->format('H:i') ?? '-' }}</span>
                                    </div>
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
                                    <div class="hp-pub-mini-footer">
                                        <span class="hp-pub-mobile-driver">
                                            <span class="hp-pub-mobile-avatar">{{ strtoupper(substr($trip->driver?->name ?? 'U', 0, 2)) }}</span>
                                            <span>{{ $trip->driver?->name ?? '-' }}</span>
                                        </span>
                                        <span class="hp-pub-mini-fare">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                        <a href="{{ route('explore.show', $trip->id) }}" class="btn btn-primary btn-sm">Request</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-empty icon="fa-solid fa-car-side" title="No public trips today" body="No public trips available right now." style="box-shadow:none; border:none; background:transparent; padding:20px 0;" />
                    @endif
                    </div>
                </div>
            @endif

        </div>{{-- /.hp-mobile-wrap --}}
    </div>{{-- /.hp-mobile-only --}}
</div>{{-- /#page-content-wrapper --}}

<script src="{{ asset('js/home.js') }}?v={{ filemtime(public_path('js/home.js')) }}"></script>
@endsection
