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

    <style>
        /* ── Desktop layout wrapper ───────────────────────────── */
        .hp-wrap {
            padding: 0 28px 28px;
        }

        /* ── Page header ──────────────────────────────────────── */
        .hp-page-header {
            padding: 28px 28px 20px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .hp-page-header-left { display: grid; gap: 4px; }
        .hp-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.10em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .hp-h1 {
            margin: 0;
            font-family: var(--font-display);
            font-size: 30px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.02em;
            line-height: 1.1;
        }
        .hp-subtitle {
            margin: 4px 0 0;
            font-size: 14px;
            color: var(--muted);
            font-weight: 500;
        }
        .hp-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        /* ── Stats strip ──────────────────────────────────────── */
        .hp-stats-strip {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            padding: 0 28px 14px;
        }
        @media (min-width: 680px) {
            .hp-stats-strip { grid-template-columns: repeat(4, 1fr); }
        }
        .hp-stat-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 18px;
            box-shadow: var(--shadow-1);
            display: grid;
            gap: 6px;
            position: relative;
        }
        .hp-stat-card.highlighted {
            background: #fffdf4;
            border-color: #f8e7a1;
        }
        .hp-stat-card.warning-tone {
            background: var(--warning-soft);
            border-color: rgba(180,83,9,0.25);
        }
        .hp-stat-icon {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 32px;
            height: 32px;
            border-radius: var(--r-sm);
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            display: grid;
            place-items: center;
            font-size: 14px;
            color: var(--muted);
        }
        .hp-stat-card.highlighted .hp-stat-icon {
            background: #fff8cf;
            border-color: #f4df8a;
            color: var(--warning);
        }
        .hp-stat-card.warning-tone .hp-stat-icon {
            background: var(--warning-soft);
            border-color: rgba(180,83,9,0.25);
            color: var(--warning);
        }
        .hp-stat-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding-right: 40px;
        }
        .hp-stat-value {
            font-family: var(--font-display);
            font-size: 26px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.025em;
            line-height: 1;
        }
        .hp-stat-delta {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted-2);
        }

        /* ── Main body grid ───────────────────────────────────── */
        .hp-body {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
            padding: 0 28px 28px;
        }
        @media (min-width: 1024px) {
            .hp-body { grid-template-columns: 2fr 1fr; }
        }

        /* ── Card shared ──────────────────────────────────────── */
        .hp-section {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-1);
            overflow: hidden;
        }
        .hp-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 16px 18px 0;
        }
        .hp-section-title {
            margin: 0;
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.01em;
        }
        .hp-section-link {
            font-size: 12px;
            font-weight: 700;
            color: var(--warning);
            text-decoration: none;
            white-space: nowrap;
        }
        .hp-section-link:hover { text-decoration: underline; }
        .hp-section-body { padding: 14px 18px 18px; }

        /* ── Tab strip ────────────────────────────────────────── */
        .hp-tabs {
            display: inline-flex;
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            padding: 3px;
            gap: 2px;
        }
        .hp-tab {
            padding: 5px 12px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            border: none;
            background: transparent;
            transition: background-color .15s, color .15s, box-shadow .15s;
            white-space: nowrap;
        }
        .hp-tab.active {
            background: var(--surface);
            color: var(--ink);
            box-shadow: var(--shadow-1);
        }

        /* ── Trip row ─────────────────────────────────────────── */
        .hp-trip-list { display: grid; gap: 8px; }
        .hp-trip-row {
            display: grid;
            grid-template-columns: 110px minmax(0, 1fr) auto auto auto;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid var(--hairline);
            border-radius: 12px;
            background: var(--surface);
            text-decoration: none;
            color: inherit;
            transition: border-color .15s, box-shadow .15s;
        }
        .hp-trip-row > div {
            min-width: 0;
        }
        .hp-trip-row:hover {
            border-color: var(--hairline-strong);
            box-shadow: var(--shadow-2);
        }
        .hp-trip-time-date {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.2;
        }
        .hp-trip-time-clock {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }
        .hp-trip-route {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hp-trip-stops {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hp-trip-route-points {
            display: grid;
            gap: 7px;
            min-width: 0;
        }
        .hp-trip-point {
            position: relative;
            display: grid;
            grid-template-columns: 14px minmax(0, 1fr);
            gap: 8px;
            align-items: start;
            min-width: 0;
        }
        .hp-trip-point:first-child::after {
            content: "";
            position: absolute;
            left: 6px;
            top: 14px;
            bottom: -8px;
            border-left: 1px dashed var(--hairline-strong);
        }
        .hp-trip-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            margin-top: 2px;
            border: 2px solid var(--surface);
            box-shadow: 0 0 0 1px var(--hairline-strong);
            position: relative;
            z-index: 1;
        }
        .hp-trip-dot.pickup { background: #22c55e; }
        .hp-trip-dot.destination { background: #334155; }
        .hp-trip-point-label {
            display: block;
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            line-height: 1;
            text-transform: uppercase;
        }
        .hp-trip-point-text {
            display: block;
            margin-top: 2px;
            color: var(--ink);
            font-size: 12px;
            font-weight: 800;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hp-trip-seats {
            font-size: 12px;
            font-weight: 700;
            color: var(--ink-3);
            white-space: nowrap;
            text-align: center;
        }
        .hp-trip-fare {
            text-align: right;
        }
        .hp-trip-fare-amount {
            font-family: var(--font-display);
            font-size: 14px;
            font-weight: 800;
            color: var(--ink);
            white-space: nowrap;
        }
        .hp-trip-fare-chevron {
            color: var(--muted-2);
            font-size: 11px;
            margin-left: 4px;
        }

        /* ── Quick actions right col ──────────────────────────── */
        .hp-quick-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .hp-quick-item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            padding: 12px;
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface);
            text-decoration: none;
            color: inherit;
            position: relative;
            transition: border-color .15s, box-shadow .15s, transform .15s;
        }
        .hp-quick-item:hover {
            border-color: var(--hairline-strong);
            box-shadow: var(--shadow-2);
            transform: translateY(-2px);
        }
        .hp-quick-icon {
            width: 34px;
            height: 34px;
            border-radius: var(--r-sm);
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            display: grid;
            place-items: center;
            font-size: 15px;
            color: var(--muted);
            flex-shrink: 0;
        }
        .hp-quick-icon.yellow {
            background: var(--ch-yellow);
            border-color: var(--ch-yellow-deep);
            color: var(--ch-yellow-ink);
        }
        .hp-quick-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.2;
        }
        .hp-quick-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            min-width: 18px;
            height: 18px;
            border-radius: var(--r-pill);
            padding: 0 4px;
            background: var(--danger);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Public trips mini grid ───────────────────────────── */
        .hp-pub-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 600px) {
            .hp-pub-grid { grid-template-columns: 1fr; }
        }
        .hp-pub-mini {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface);
            padding: 14px;
            display: grid;
            gap: 8px;
            box-shadow: var(--shadow-1);
            transition: border-color .15s, box-shadow .15s, transform .15s;
        }
        .hp-pub-mini:hover {
            border-color: var(--hairline-strong);
            box-shadow: var(--shadow-2);
            transform: translateY(-2px);
        }
        .hp-pub-mini-route {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hp-pub-mini-meta {
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
        }
        .hp-pub-mini-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .hp-pub-mini-fare {
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 800;
            color: var(--ink);
        }
        .hp-pub-mobile-time {
            margin-left: auto;
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 800;
            color: #7c8ba1;
        }
        .hp-pub-mobile-route {
            display: grid;
            gap: 7px;
            min-width: 0;
        }
        .hp-pub-point {
            position: relative;
            display: grid;
            grid-template-columns: 14px minmax(0, 1fr);
            gap: 8px;
            min-width: 0;
        }
        .hp-pub-point:first-child::after {
            content: "";
            position: absolute;
            left: 6px;
            top: 14px;
            bottom: -8px;
            border-left: 1px dashed var(--hairline-strong);
        }
        .hp-pub-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            margin-top: 2px;
            border: 2px solid var(--surface);
            box-shadow: 0 0 0 1px var(--hairline-strong);
            position: relative;
            z-index: 1;
        }
        .hp-pub-dot.pickup { background: #22c55e; }
        .hp-pub-dot.destination { background: #334155; }
        .hp-pub-point-label {
            display: block;
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            line-height: 1;
            text-transform: uppercase;
        }
        .hp-pub-point-text {
            display: block;
            margin-top: 2px;
            color: var(--ink);
            font-size: 13px;
            font-weight: 800;
            line-height: 1.25;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .hp-pub-mobile-driver {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }
        .hp-pub-mobile-avatar {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: var(--ch-yellow-tint);
            border: 1px solid var(--ch-yellow-line);
            color: var(--ch-yellow-ink);
            font-size: 10px;
            font-weight: 800;
            flex-shrink: 0;
        }

        /* ── Driver review queue ──────────────────────────────── */
        .hp-review-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--hairline);
        }
        .hp-review-row:last-child { border-bottom: 0; }
        .hp-review-avatar {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: var(--ch-yellow-tint);
            border: 1px solid var(--ch-yellow-line);
            display: grid;
            place-items: center;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 13px;
            color: var(--warning-ink);
            flex-shrink: 0;
        }
        .hp-review-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
        }
        .hp-review-sub {
            font-size: 11px;
            color: var(--muted);
        }
        .hp-review-amount {
            font-family: var(--font-display);
            font-size: 14px;
            font-weight: 800;
            color: var(--ink);
            margin-left: auto;
            white-space: nowrap;
        }

        /* ── Mobile hero card ─────────────────────────────────── */
        .hp-mobile-hero {
            background: linear-gradient(135deg, #fffbea 0%, #ffe26a 100%);
            border: 1px solid #f3da73;
            border-radius: 18px;
            padding: 17px 16px 14px;
            box-shadow: 0 8px 20px rgba(180, 133, 10, 0.08);
            display: grid;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }
        .hp-mobile-hero::before { display: none; }
        .hp-mobile-hero-eyebrow {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            color: var(--ch-yellow-ink);
            opacity: 0.72;
        }
        .hp-mobile-hero-title {
            margin: 0;
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
            color: var(--ch-yellow-ink);
            letter-spacing: 0;
            line-height: 1.1;
        }
        .hp-mobile-hero-next {
            font-size: 13px;
            color: var(--ch-yellow-ink);
            font-weight: 600;
            opacity: 0.85;
            margin: 4px 0 0;
        }
        .hp-mobile-hero-actions {
            display: flex;
            gap: 8px;
        }
        .hp-mobile-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 34px;
            padding: 0 13px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            transition: transform .15s ease;
        }
        .hp-mobile-cta:hover { transform: translateY(-1px); }
        .hp-mobile-cta.dark {
            background: var(--ink);
            color: #fff;
        }
        .hp-mobile-cta.ghost {
            background: rgba(255,255,255,0.24);
            color: var(--ch-yellow-ink);
            border: 1px solid rgba(42,30,4,0.12);
        }

        /* ── Mobile 2-col mini stats ──────────────────────────── */
        .hp-mobile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .hp-mobile-stat {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: 16px;
            padding: 16px 14px;
            display: grid;
            gap: 4px;
            box-shadow: var(--shadow-1);
        }
        .hp-mobile-stat-label {
            font-size: 10px;
            font-weight: 800;
            color: #7c8ba1;
            text-transform: uppercase;
            letter-spacing: 0.13em;
        }
        .hp-mobile-stat-value {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: 0;
            line-height: 1;
        }
        .hp-mobile-stat-delta {
            font-size: 12px;
            font-weight: 800;
            color: var(--success-ink);
        }
        .hp-mobile-stat-delta.warning {
            color: var(--warning-ink);
        }

        /* ── Mobile 4-col quick actions ───────────────────────── */
        .hp-mobile-quick {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .hp-mobile-quick-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            color: inherit;
            min-height: 78px;
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: 14px;
            position: relative;
            box-shadow: var(--shadow-1);
        }
        .hp-mobile-quick-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--surface-2);
            border: none;
            display: grid;
            place-items: center;
            font-size: 14px;
            color: var(--ch-yellow-ink);
        }
        .hp-mobile-quick-icon.yellow {
            background: var(--ch-yellow-tint);
            color: var(--ch-yellow-ink);
        }
        .hp-mobile-quick-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--ink-2);
            text-align: center;
            line-height: 1.2;
        }
        .hp-mobile-quick-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--danger);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
        }
        .hp-mobile-section-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin: 0 0 8px;
        }
        .hp-mobile-section-title {
            margin: 0;
            font-family: var(--font-display);
            font-size: 17px;
            font-weight: 800;
            color: var(--ink);
        }
        .hp-mobile-section-link {
            border: 0;
            background: transparent;
            padding: 0;
            color: #2563eb;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
            cursor: pointer;
        }
        .hp-mobile-extra-actions {
            display: none;
            margin-top: 10px;
        }
        .hp-mobile-extra-actions.open {
            display: grid;
        }

        /* ── Show/hide by breakpoint ──────────────────────────── */
        .hp-desktop-only { display: none; }
        .hp-mobile-only  { display: block; }
        @media (min-width: 1024px) {
            .hp-desktop-only { display: block; }
            .hp-mobile-only  { display: none; }
        }

        /* Mobile wrapper padding */
        .hp-mobile-wrap {
            display: grid;
            gap: 14px;
            padding: 14px 14px 92px;
            background: transparent;
            min-height: calc(100vh - 72px);
        }
        @media (max-width: 1023px) {
            .hp-mobile-only .hp-section {
                border: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
                overflow: visible;
            }
            .hp-mobile-only .hp-section-head,
            .hp-mobile-only .hp-section-body {
                padding: 0;
            }
            .hp-mobile-only .hp-pub-mini {
                border-radius: 16px;
                padding: 14px;
                gap: 10px;
            }
            .hp-mobile-only .hp-pub-mini-footer {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto auto;
                gap: 10px;
                align-items: center;
            }
            .hp-mobile-only .hp-pub-mobile-driver {
                min-width: 0;
                gap: 7px;
                overflow: hidden;
            }
            .hp-mobile-only .hp-pub-mobile-driver > span:last-child {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .hp-mobile-only .hp-pub-mobile-avatar {
                width: 26px;
                height: 26px;
                font-size: 10px;
                border-width: 1px;
                background: #fff7d6;
            }
            .hp-mobile-only .hp-pub-mini-fare {
                justify-self: end;
                font-size: 15px;
                white-space: nowrap;
                margin-right: 2px;
            }
            .hp-mobile-only .hp-pub-mini-footer .btn {
                min-width: 86px;
                height: 38px;
                justify-content: center;
                border-radius: 10px;
                font-weight: 900;
            }
        }
    </style>

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

        </div>

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
                        </div>
                    </div>
                </div>

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
                            <div style="text-align:center;padding:24px 0;font-size:13px;color:var(--muted);font-weight:600;">
                                <i class="fa-solid fa-car-side" style="font-size:20px;display:block;margin-bottom:8px;color:var(--muted-2);"></i>
                                No public trips near you right now.
                            </div>
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
                        <div style="text-align:center;padding:20px 0;font-size:13px;color:var(--muted);">
                            No public trips available right now.
                        </div>
                    @endif
                </div>
            </div>

        </div>{{-- /.hp-mobile-wrap --}}
    </div>{{-- /.hp-mobile-only --}}

    <script>
        function hpToggleMobileActions(btn) {
            var panel = document.getElementById('hp-mobile-extra-actions');
            if (!panel) return;
            var open = panel.classList.toggle('open');
            btn.textContent = open ? 'Show less' : 'View all';
        }

        function hpSwitchTab(panel, btn) {
            // Hide all panels
            document.getElementById('hp-tab-driver').style.display    = 'none';
            document.getElementById('hp-tab-passenger').style.display = 'none';
            // Show target panel
            document.getElementById('hp-tab-' + panel).style.display = 'block';
            // Update tab active state
            document.querySelectorAll('#hp-trip-tabs .hp-tab').forEach(function(t) {
                t.classList.remove('active');
            });
            btn.classList.add('active');
        }
    </script>
@endsection
