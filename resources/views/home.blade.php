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
                ['route' => 'billing-cycles.index', 'title' => 'Monthly Summary', 'meta' => 'Operational summary', 'art' => 'quick-summary-3d.svg', 'tone' => 'amber'],
                ['route' => 'archive.index', 'title' => 'Archive', 'meta' => 'Past cycle records', 'art' => 'quick-archive-3d.svg', 'tone' => 'slate'],
                ['route' => 'notifications.index', 'title' => 'Notifications', 'meta' => 'System alerts and updates', 'art' => 'quick-notification-3d.svg', 'tone' => 'emerald'],
                ['route' => 'settings.index', 'title' => 'Settings', 'meta' => 'Profile and account options', 'art' => 'quick-settings-3d.svg', 'tone' => 'slate'],
            ],
            'driver' => [
                ['route' => 'saved-routes.index', 'title' => 'Routes', 'meta' => 'Route preset management', 'art' => 'quick-route-3d.svg', 'tone' => 'sky'],
                ['route' => 'payments.index', 'url' => route('payments.index') . '#queue-summary', 'title' => 'Payments', 'meta' => 'Track fare status', 'art' => 'quick-payment-3d.svg', 'tone' => 'amber'],
                ['route' => 'billing-cycles.index', 'title' => 'Monthly Summary', 'meta' => 'Cycle summary and closure', 'art' => 'quick-summary-3d.svg', 'tone' => 'slate'],
                ['route' => 'archive.index', 'title' => 'Archive', 'meta' => 'Past cycle records', 'art' => 'quick-archive-3d.svg', 'tone' => 'emerald'],
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
    @endphp

    <style>
        .home-page { display: grid; gap: 12px; }
        .home-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }
        .home-hero {
            display: grid;
            gap: 14px;
        }
        .home-title { margin: 0; font-family: Poppins, sans-serif; font-size: 31px; color: #0f172a; line-height: 1.05; letter-spacing: -0.02em; }
        .home-subtitle { margin: 8px 0 0; color: #475569; font-size: 15px; line-height: 1.4; max-width: 58ch; }
        .home-hero-actions {
            display: grid;
            gap: 10px;
        }
        .home-hero-cta-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 10px;
        }
        .home-hero-cta {
            min-height: 50px;
            border-radius: 14px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 900;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        }
        .home-hero-cta.primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }
        .home-hero-route-card {
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            background: #f8fafc;
            padding: 12px;
            display: grid;
            gap: 10px;
        }
        .home-hero-route-line {
            display: grid;
            grid-template-columns: 28px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            color: inherit;
            text-decoration: none;
            border-radius: 12px;
            padding: 2px;
            transition: background-color .16s ease, transform .16s ease;
        }
        .home-hero-route-line:hover,
        .home-hero-route-line:focus-visible {
            background: #fff;
            transform: translateY(-1px);
            outline: none;
        }
        .home-hero-route-icon {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #fff;
            background: #16a34a;
        }
        .home-hero-route-icon.destination {
            background: #ef4444;
        }
        .home-hero-route-copy {
            min-width: 0;
            display: grid;
            gap: 2px;
        }
        .home-hero-route-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .home-hero-route-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (max-width: 540px) {
            .home-title { font-size: 28px; }
            .home-subtitle { font-size: 14px; }
            .home-hero-cta-row { grid-template-columns: 1fr; }
        }
        .home-section-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
        .home-section-title { margin: 0; color: #0f172a; font-family: Poppins, sans-serif; font-size: 18px; }
        .home-shortcuts { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .home-shortcut {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            padding: 12px;
            text-decoration: none;
            color: #0f172a;
            display: grid;
            gap: 6px;
            min-height: 122px;
            align-content: start;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            position: relative;
        }
        .home-shortcut:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }
        .home-shortcut-media {
            width: 54px;
            height: 54px;
            border-radius: 0;
            border: 0;
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: none;
        }
        .home-shortcut.tone-sky .home-shortcut-media,
        .home-shortcut.tone-emerald .home-shortcut-media,
        .home-shortcut.tone-slate .home-shortcut-media,
        .home-shortcut.tone-amber .home-shortcut-media {
            border: 0;
            background: transparent;
            box-shadow: none;
        }
        .home-shortcut-media img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            display: block;
        }
        .home-shortcut-title { font-size: 15px; font-weight: 800; line-height: 1.2; letter-spacing: -0.01em; }
        .home-shortcut-meta { font-size: 12px; color: #64748b; line-height: 1.3; font-weight: 600; }
        .home-shortcut-unpaid-note {
            margin-top: -2px;
            font-size: 11px;
            font-weight: 700;
            color: #b91c1c;
            line-height: 1.2;
            text-transform: lowercase;
        }
        .home-shortcut-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            min-width: 22px;
            height: 22px;
            border-radius: 999px;
            padding: 0 6px;
            border: 1px solid #fca5a5;
            background: #ef4444;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            line-height: 1;
            box-shadow: none;
            animation: homeBadgePulse 1.8s ease-in-out infinite;
            pointer-events: none;
        }
        @keyframes homeBadgePulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.04); opacity: .9; }
        }
        .home-stats { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .home-stat {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            padding: 11px;
            display: grid;
            gap: 4px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .home-stat-label { font-size: 12px; color: #64748b; font-weight: 700; }
        .home-stat-value { font-size: 22px; color: #0f172a; font-weight: 800; line-height: 1.05; letter-spacing: -0.01em; }
        .home-stat-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .home-stat-link:hover .home-stat {
            border-color: #cbd5e1;
            box-shadow: 0 10px 16px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }
        .home-note {
            margin-top: 10px;
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .home-note-item {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            padding: 11px;
            display: grid;
            gap: 4px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .home-note-label { font-size: 12px; color: #64748b; font-weight: 700; }
        .home-note-value { font-size: 20px; color: #0f172a; font-weight: 800; line-height: 1.15; letter-spacing: -0.01em; }
        .home-note-meta { color: #64748b; font-size: 12px; font-weight: 600; }
        .home-note-muted { color: #64748b; font-weight: 700; }
        .home-note-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .home-note-link:hover .home-note-item {
            border-color: #cbd5e1;
            box-shadow: 0 10px 16px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }
        .home-public-wrap { display: grid; gap: 10px; }
        .home-public-head-link {
            color: #92400e;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: .01em;
        }
        .home-public-head-tools {
            display: inline-flex;
            align-items: center;
        }
        .home-public-carousel {
            overflow: hidden;
            border-radius: 14px;
            background: transparent;
        }
        .home-public-track {
            display: flex;
            transition: transform .42s cubic-bezier(0.22, 1, 0.36, 1);
            will-change: transform;
        }
        .home-public-slide {
            flex: 0 0 100%;
            min-width: 100%;
            padding: 12px;
            text-decoration: none;
            color: inherit;
        }
        .home-public-card {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #ffffff;
            padding: 12px;
            display: grid;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .home-public-slide:hover .home-public-card,
        .home-public-slide:focus-visible .home-public-card {
            transform: translateY(-2px);
            border-color: #cbd5e1;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
        }
        .home-public-title { margin: 0; font-size: 16px; color: #0f172a; font-weight: 800; line-height: 1.25; }
        .home-public-meta {
            margin: 0;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
        }
        .home-public-meta i {
            color: #92400e;
            font-size: 11px;
            flex: 0 0 auto;
        }
        .home-public-meta span {
            display: block;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .home-public-meta-inline {
            margin: 2px 0 0;
            color: #64748b;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            min-width: 0;
        }
        .home-public-meta-inline-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        .home-public-meta-inline-item i {
            color: #92400e;
            font-size: 11px;
            flex: 0 0 auto;
        }
        .home-public-meta-line {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            display: grid;
            grid-template-columns: 12px auto minmax(0, 1fr);
            align-items: center;
            column-gap: 6px;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            min-width: 0;
        }
        .home-public-meta-line i {
            color: #92400e;
            font-size: 11px;
            width: 12px;
            text-align: center;
        }
        .home-public-meta-label {
            font-weight: 700;
            white-space: nowrap;
        }
        .home-public-meta-value {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
        }
        .home-public-mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
        }
        .home-public-pill {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            padding: 7px 8px;
            display: grid;
            gap: 2px;
        }
        .home-public-pill-label { font-size: 11px; color: #64748b; font-weight: 700; }
        .home-public-pill-value { font-size: 13px; color: #0f172a; font-weight: 800; }
        .home-public-nav {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #334155;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color .16s ease, border-color .16s ease, color .16s ease, transform .16s ease;
        }
        .home-public-nav:hover {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
            transform: translateY(-1px);
        }
        .home-public-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .home-public-dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            padding: 0;
        }
        .home-public-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            border: 0;
            background: #cbd5e1;
            padding: 0;
            cursor: pointer;
        }
        .home-public-dot.active { background: #eab308; }
        .home-public-empty {
            border: 1px dashed #dbe2ea;
            border-radius: 12px;
            background: #f8fafc;
            padding: 14px;
            color: #64748b;
            font-size: 13px;
            text-align: center;
            font-weight: 600;
        }
        @media (min-width: 900px) {
            .home-shortcuts { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .home-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .home-note { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

    <div class="home-page">
        <section class="home-card">
            <div class="home-hero">
                <div>
                    <h1 class="home-title">Hi, {{ $user->name }}! 👋</h1>
                    <p class="home-subtitle">{{ $heroKicker }}</p>
                </div>
                <div class="home-hero-actions">
                    <div class="home-hero-cta-row">
                        <a href="{{ route($heroPrimary['route']) }}" class="home-hero-cta primary">
                            <i class="{{ $heroPrimary['icon'] }}"></i>
                            <span>{{ $heroPrimary['label'] }}</span>
                        </a>
                        <a href="{{ route($heroSecondary['route']) }}" class="home-hero-cta">
                            <i class="{{ $heroSecondary['icon'] }}"></i>
                            <span>{{ $heroSecondary['label'] }}</span>
                        </a>
                    </div>
                    <div class="home-hero-route-card" aria-label="Quick route context">
                        <a href="{{ route($heroPrimary['route']) }}" class="home-hero-route-line">
                            <span class="home-hero-route-icon"><i class="fa-solid fa-location-dot"></i></span>
                            <span class="home-hero-route-copy">
                                <span class="home-hero-route-label">Start</span>
                                <span class="home-hero-route-value">{{ $heroPrimary['label'] }}</span>
                            </span>
                        </a>
                        <a href="{{ route($heroSecondary['route']) }}" class="home-hero-route-line">
                            <span class="home-hero-route-icon destination"><i class="fa-solid fa-flag-checkered"></i></span>
                            <span class="home-hero-route-copy">
                                <span class="home-hero-route-label">Destination</span>
                                <span class="home-hero-route-value">{{ $heroSecondary['label'] }}</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-card">
            <div class="home-section-head">
                <h2 class="home-section-title">Quick Actions</h2>
            </div>
            <div class="home-shortcuts">
                @foreach($quickActions as $item)
                    @php
                        $isPayFareAction = (($item['route'] ?? '') === 'payments.index') && (($item['title'] ?? '') === 'Pay Fare');
                        $quickUnpaidCount = (int) ($stats['unpaid_count'] ?? 0);
                    @endphp
                    <a href="{{ $item['url'] ?? route($item['route']) }}" class="home-shortcut tone-{{ $item['tone'] ?? 'amber' }}">
                        @if($isPayFareAction && $quickUnpaidCount > 0)
                            <span class="home-shortcut-badge">{{ $quickUnpaidCount > 99 ? '99+' : $quickUnpaidCount }}</span>
                        @endif
                        <span class="home-shortcut-media">
                            <img src="{{ asset('assets/illustrations/' . $item['art']) }}" alt="" aria-hidden="true">
                        </span>
                        <span class="home-shortcut-title">{{ $item['title'] }}</span>
                        <span class="home-shortcut-meta">{{ $item['meta'] }}</span>
                        @if($isPayFareAction && $quickUnpaidCount > 0)
                            <span class="home-shortcut-unpaid-note">{{ $quickUnpaidCount }} unpaid fares</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>

        @if($role !== 'passenger')
            <section class="home-card">
                <div class="home-section-head">
                <h2 class="home-section-title">Management</h2>
            </div>
            <div class="home-shortcuts">
                @foreach($managementActions as $item)
                        <a href="{{ $item['url'] ?? route($item['route']) }}" class="home-shortcut tone-{{ $item['tone'] ?? 'amber' }}">
                            <span class="home-shortcut-media">
                                <img src="{{ asset('assets/illustrations/' . $item['art']) }}" alt="" aria-hidden="true">
                            </span>
                            <span class="home-shortcut-title">{{ $item['title'] }}</span>
                            <span class="home-shortcut-meta">{{ $item['meta'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="home-card">
            <div class="home-section-head">
                <h2 class="home-section-title">Key Status</h2>
            </div>
            <div class="home-stats">
                <a href="{{ route('trips.index') }}" class="home-stat-link">
                    <div class="home-stat">
                        <span class="home-stat-label">Total Trips</span>
                        <span class="home-stat-value">{{ (int) ($stats['total_trips'] ?? 0) }}</span>
                    </div>
                </a>
                <a href="{{ route('payments.index') }}" class="home-stat-link">
                    <div class="home-stat">
                        <span class="home-stat-label">Outstanding (All Cycles)</span>
                        <span class="home-stat-value">RM {{ number_format((float) ($stats['unpaid_amount'] ?? 0), 2) }}</span>
                    </div>
                </a>
                <a href="{{ route('notifications.index') }}" class="home-stat-link">
                    <div class="home-stat">
                        <span class="home-stat-label">Unread Notifications</span>
                        <span class="home-stat-value">{{ (int) ($stats['unread_notifications'] ?? 0) }}</span>
                    </div>
                </a>
                <a href="{{ route('saved-routes.index') }}" class="home-stat-link">
                    <div class="home-stat">
                        <span class="home-stat-label">Active Routes</span>
                        <span class="home-stat-value">{{ (int) ($stats['saved_routes'] ?? 0) }}</span>
                    </div>
                </a>
            </div>

            @if($role === 'passenger')
                <div class="home-note">
                    @if($upcomingJoinedTrip)
                        <a href="{{ route('trips.index', ['focus_trip' => $upcomingJoinedTrip->id]) }}" class="home-note-link">
                            <div class="home-note-item">
                                <span class="home-note-label">Upcoming Joined Trips</span>
                                <span class="home-note-value">{{ $upcomingJoinedTrip->savedRoute?->route_name ?? ('Trip #' . $upcomingJoinedTrip->id) }}</span>
                                <span class="home-note-meta">{{ $upcomingJoinedTrip->trip_datetime?->format('d M Y, H:i') }}</span>
                            </div>
                        </a>
                    @else
                        <div class="home-note-item">
                            <span class="home-note-label">Upcoming Joined Trips</span>
                            <span class="home-note-value">No joined trips scheduled yet.</span>
                        </div>
                    @endif
                </div>
            @elseif($role === 'driver')
                <div class="home-note">
                    @if($upcomingCreatedTrip)
                        <a href="{{ route('trips.index', ['focus_trip' => $upcomingCreatedTrip->id]) }}" class="home-note-link">
                            <div class="home-note-item">
                                <span class="home-note-label">Upcoming Created Trips</span>
                                <span class="home-note-value">{{ $upcomingCreatedTrip->savedRoute?->route_name ?? ('Trip #' . $upcomingCreatedTrip->id) }}</span>
                                <span class="home-note-meta">{{ $upcomingCreatedTrip->trip_datetime?->format('d M Y, H:i') }}</span>
                            </div>
                        </a>
                    @else
                        <div class="home-note-item">
                            <span class="home-note-label">Upcoming Created Trips</span>
                            <span class="home-note-value">No created trips scheduled yet.</span>
                        </div>
                    @endif
                    @if($upcomingCreatedTrip)
                        <a href="{{ route('trips.requests.index', $upcomingCreatedTrip) }}" class="home-note-link">
                            <div class="home-note-item">
                                <span class="home-note-label">Pending Join Requests</span>
                                <span class="home-note-value">{{ (int) ($pendingJoinRequests ?? 0) }}</span>
                            </div>
                        </a>
                    @else
                        <a href="{{ route('trips.index') }}" class="home-note-link">
                            <div class="home-note-item">
                                <span class="home-note-label">Pending Join Requests</span>
                                <span class="home-note-value">{{ (int) ($pendingJoinRequests ?? 0) }}</span>
                            </div>
                        </a>
                    @endif
                </div>
            @elseif($role === 'admin')
                <div class="home-note">
                    <a href="{{ route('admin.reports.index') }}" class="home-note-link">
                        <div class="home-note-item">
                            <span class="home-note-label">Admin Focus</span>
                            <span class="home-note-value">Review users, reports, and operational summaries.</span>
                        </div>
                    </a>
                </div>
            @endif
        </section>

        <section class="home-card">
            <div class="home-section-head">
                <h2 class="home-section-title">Join Public Trips</h2>
                <div class="home-public-head-tools">
                    <a href="{{ route('explore.index') }}" class="home-public-head-link">Explore Public Trips</a>
                </div>
            </div>
            <div class="home-public-wrap">
                @if(($publicExploreTrips ?? collect())->isNotEmpty())
                    <div class="home-public-carousel" data-home-public-carousel>
                        <div class="home-public-track" data-carousel-track>
                            @foreach($publicExploreTrips as $trip)
                                @php
                                    $routeName = $trip->savedRoute?->route_name ?: (($trip->pickup_name ?? 'Pickup') . ' - ' . ($trip->destination_name ?? 'Destination'));
                                    $takenSeats = (int) $trip->participants->where('is_driver', false)->count();
                                    $availableSeats = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : null;
                                    $tripTypeText = ((string) ($trip->trip_mode ?? 'one_way')) === 'two_way' ? 'Two-way' : 'One-way';
                                    $pickupText = $trip->pickup_name ?? 'Pickup';
                                    $destinationText = $trip->destination_name ?? 'Destination';
                                    $visibilityText = ((string) ($trip->visibility ?? 'public')) === 'public' ? 'Public Trip' : 'Private Trip';
                                    $visibilityIcon = ($trip->visibility ?? 'public') === 'public' ? 'fa-solid fa-lock-open' : 'fa-solid fa-lock';
                                @endphp
                                <a href="{{ route('explore.index', ['sort' => 'latest', 'focus_trip' => $trip->id]) }}" class="home-public-slide">
                                    <article class="home-public-card">
                                        <h3 class="home-public-title">{{ $routeName }}</h3>
                                        <div class="home-public-meta-inline">
                                            <span class="home-public-meta-inline-item">
                                                <i class="fas fa-user"></i>
                                                <span>{{ $trip->driver?->name ?? '-' }}</span>
                                            </span>
                                            <span class="home-public-meta-inline-item">
                                                <i class="fas fa-route"></i>
                                                <span>{{ $tripTypeText }}</span>
                                            </span>
                                            <span class="home-public-meta-inline-item">
                                                <i class="{{ $visibilityIcon }}"></i>
                                                <span>{{ $visibilityText }}</span>
                                            </span>
                                        </div>
                                        <p class="home-public-meta-line" title="{{ $pickupText }}">
                                            <i class="fas fa-location-dot"></i>
                                            <span class="home-public-meta-label">Pickup :</span>
                                            <span class="home-public-meta-value">{{ $pickupText }}</span>
                                        </p>
                                        <p class="home-public-meta-line" title="{{ $destinationText }}">
                                            <i class="fas fa-flag-checkered"></i>
                                            <span class="home-public-meta-label">Destination :</span>
                                            <span class="home-public-meta-value">{{ $destinationText }}</span>
                                        </p>
                                        <div class="home-public-mini-grid">
                                            <div class="home-public-pill">
                                                <span class="home-public-pill-label">Date & Time</span>
                                                <span class="home-public-pill-value">{{ $trip->trip_datetime?->format('d M Y, H:i') ?? '-' }}</span>
                                            </div>
                                            <div class="home-public-pill">
                                                <span class="home-public-pill-label">Fare</span>
                                                <span class="home-public-pill-value">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                            </div>
                                            <div class="home-public-pill">
                                                <span class="home-public-pill-label">Seats</span>
                                                <span class="home-public-pill-value">{{ $availableSeats !== null ? ($availableSeats . ' / ' . (int) $trip->seat_limit) : 'Open' }}</span>
                                            </div>
                                            <div class="home-public-pill">
                                                <span class="home-public-pill-label">Trip ID</span>
                                                <span class="home-public-pill-value">#{{ $trip->id }}</span>
                                            </div>
                                        </div>
                                    </article>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="home-public-controls">
                        <button type="button" class="home-public-nav prev" data-carousel-prev aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
                        <div class="home-public-dots" data-carousel-dots></div>
                        <button type="button" class="home-public-nav next" data-carousel-next aria-label="Next"><i class="fas fa-chevron-right"></i></button>
                    </div>
                @else
                    <div class="home-public-empty">No public trips are available right now.</div>
                @endif
            </div>
        </section>
    </div>

    <script>
        (() => {
            const carousel = document.querySelector('[data-home-public-carousel]');
            if (!carousel) return;

            const track = carousel.querySelector('[data-carousel-track]');
            const slides = Array.from(track.querySelectorAll('.home-public-slide'));
            const prevBtn = document.querySelector('[data-carousel-prev]');
            const nextBtn = document.querySelector('[data-carousel-next]');
            const dotsWrap = document.querySelector('[data-carousel-dots]');
            if (slides.length <= 1) {
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
                return;
            }

            let index = 0;
            let timerId = null;
            const dots = slides.map((_, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'home-public-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('aria-label', `Slide ${i + 1}`);
                dot.addEventListener('click', () => {
                    index = i;
                    render();
                    restart();
                });
                dotsWrap.appendChild(dot);
                return dot;
            });

            const render = () => {
                track.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === index));
            };
            const next = () => {
                index = (index + 1) % slides.length;
                render();
            };
            const prev = () => {
                index = (index - 1 + slides.length) % slides.length;
                render();
            };
            const start = () => {
                timerId = window.setInterval(next, 4200);
            };
            const stop = () => {
                if (timerId) {
                    window.clearInterval(timerId);
                    timerId = null;
                }
            };
            const restart = () => {
                stop();
                start();
            };

            nextBtn?.addEventListener('click', () => {
                next();
                restart();
            });
            prevBtn?.addEventListener('click', () => {
                prev();
                restart();
            });
            carousel.addEventListener('mouseenter', stop);
            carousel.addEventListener('mouseleave', start);
            carousel.addEventListener('focusin', stop);
            carousel.addEventListener('focusout', start);

            render();
            start();
        })();
    </script>
@endsection
