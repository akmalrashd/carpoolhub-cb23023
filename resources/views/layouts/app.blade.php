<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    {{-- user-scalable=no stops pinch zoom of the page itself; the Leaflet maps
         still zoom, they drive it from JS. viewport-fit=cover lets the layout
         reach under a notch, which the safe-area rules then pad back. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <title>CarpoolHub</title>
    {{-- Base stylesheet: Tailwind preflight, the design tokens, and the utilities
         the default paginator needs. Pre-compiled to a static file so the app has
         no Node build step; the link sits at exactly the position the old bundler
         directive did, so its cascade order against the fonts and the page
         stylesheet below is unchanged. --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/icon.png') }}">

    {{-- Shared shell styles, extracted to a cacheable static file. The link sits exactly where the <style> block did so cascade order is unchanged; pwa-head.blade.php (included after </head>) still overrides it. --}}
    <link rel="stylesheet" href="{{ asset('css/shell.css') }}?v={{ filemtime(public_path('css/shell.css')) }}">
    @include('layouts.partials.pwa-head')
</head>
<body>

@php
    $headerNotifications = collect();
    $headerUnreadCount = 0;
    if (auth()->check()) {
        $headerNotifications = \App\Models\UserNotification::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(6)
            ->get();
        $headerUnreadCount = \App\Models\UserNotification::query()
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();
    }
@endphp

@auth
    @include('layouts.partials.mobile-header')

    <header class="desktop-topbar">
        <div class="desktop-topbar-left">
            <button type="button" class="menu-toggle-btn desktop-menu-toggle" id="sidebarToggle" aria-label="Togol bar sisi">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
            <div class="desktop-brand-wrap">
                <a href="{{ route('home') }}" class="header-logo-link" aria-label="Go to Home">
                    <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="" class="desktop-brand-logo">
                    <span class="desktop-brand-text">Carpool<span>Hub</span></span>
                </a>
            </div>
        </div>
        <div class="desktop-topbar-right">
            <details class="bento-menu-wrap">
                <summary class="bento-menu-toggle" aria-label="Toggle Menu">
                    <i class="fa-solid fa-grip" aria-hidden="true"></i>
                </summary>
                <div class="bento-menu-dropdown">
                    <h2 class="bento-menu-title">Menu</h2>
                    <div class="bento-menu-container">
                        <!-- Left Panel (Social/Lists) -->
                        <div class="bento-menu-main">
                            <div class="bento-menu-search-wrap">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" placeholder="Search menu..." class="bento-menu-search-input" data-bento-search>
                            </div>
                            <div class="bento-menu-sections-wrapper">
                                <!-- Section: Navigation -->
                                <div class="bento-menu-section" data-bento-section>
                                    <h3 class="bento-section-title">Navigation</h3>
                                    <div class="bento-grid">
                                        <a href="{{ route('home') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(37,99,235,0.1); color: #2563eb;">
                                                <i class="fa-solid fa-house"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Dashboard</strong>
                                                <span class="bento-desc">Go to home view, see trip statistics and summaries.</span>
                                            </div>
                                        </a>
                                        <a href="{{ route('explore.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(147,51,234,0.1); color: #9333ea;">
                                                <i class="fa-solid fa-compass"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Explore</strong>
                                                <span class="bento-desc">Browse, search and filter active carpool trips.</span>
                                            </div>
                                        </a>
                                        <a href="{{ route('trips.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(22,163,74,0.1); color: #16a34a;">
                                                <i class="fa-solid fa-car-side"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">My Trips</strong>
                                                <span class="bento-desc">View and manage your upcoming, past, and draft journeys.</span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                <!-- Section: Workspace -->
                                <div class="bento-menu-section" data-bento-section>
                                    <h3 class="bento-section-title">Workspace</h3>
                                    <div class="bento-grid">
                                        <a href="{{ route('saved-routes.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(220,38,38,0.1); color: #dc2626;">
                                                <i class="fa-solid fa-route"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Saved Routes</strong>
                                                <span class="bento-desc">Quickly define recurrent starting and destination points.</span>
                                            </div>
                                        </a>
                                        <a href="{{ route('connections.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(2,132,199,0.1); color: #0284c7;">
                                                <i class="fa-solid fa-users"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Connections</strong>
                                                <span class="bento-desc">Network with drivers and riders in your circle.</span>
                                            </div>
                                        </a>
                                        <a href="{{ route('payments.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(234,179,8,0.15); color: #ca8a04;">
                                                <i class="fa-solid fa-wallet"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Payments Ledger</strong>
                                                <span class="bento-desc">Track and review trip fees and driver collection receipts.</span>
                                            </div>
                                        </a>
                                        <a href="{{ route('settings.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(100,116,139,0.1); color: #64748b;">
                                                <i class="fa-solid fa-gears"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Account Settings</strong>
                                                <span class="bento-desc">Manage your profile, vehicle, and payment accounts.</span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Right Panel (Create) -->
                        <div class="bento-menu-side">
                            <h3 class="bento-side-title">Create</h3>
                            <div class="bento-side-list">
                                @if(auth()->user()?->role === 'admin')
                                    <a href="{{ route('admin.users.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-user-plus"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">Manage Users</strong>
                                            <span class="bento-side-desc">Register or update user accounts.</span>
                                        </div>
                                    </a>
                                    <a href="{{ route('admin.reports.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-chart-pie"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">View Reports</strong>
                                            <span class="bento-side-desc">Analyze system metrics and export CSVs.</span>
                                        </div>
                                    </a>
                                @elseif(auth()->user()?->role === 'passenger')
                                    <a href="{{ route('explore.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">Request Seat</strong>
                                            <span class="bento-side-desc">Search active rides and request joins.</span>
                                        </div>
                                    </a>
                                    <a href="{{ route('connections.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-user-group"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">Add Connection</strong>
                                            <span class="bento-side-desc">Find and connect with verified drivers.</span>
                                        </div>
                                    </a>
                                @else
                                    {{-- Default / Driver --}}
                                    <a href="{{ route('trips.create') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-plus"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">Post Trip</strong>
                                            <span class="bento-side-desc">Offer empty seats to passengers.</span>
                                        </div>
                                    </a>
                                    <a href="{{ route('saved-routes.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-map-location-dot"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">New Route</strong>
                                            <span class="bento-side-desc">Pre-define a route template.</span>
                                        </div>
                                    </a>
                                    <a href="{{ route('settings.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-qrcode"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">Setup Wallet</strong>
                                            <span class="bento-side-desc">Add bank or DuitNow payment details.</span>
                                        </div>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </details>
            <button id="ai-fab" class="header-ai-fab" onclick="aiChat.toggle(event)" aria-label="CarpoolHub AI" title="CarpoolHub AI">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span class="ai-fab-label">AI</span>
            </button>
            <details class="notification-wrap">
                <summary class="notification-toggle {{ $headerUnreadCount > 0 ? 'has-unread' : '' }}">
                    <i class="fa-solid fa-bell" aria-hidden="true"></i>
                    @if($headerUnreadCount > 0)
                        <span class="notification-badge">{{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}</span>
                    @endif
                </summary>
                <div class="notification-dropdown">
                    <div class="notification-dropdown-head">
                        <strong>Notifications</strong>
                        <form method="POST" action="{{ route('notifications.read-all') }}" class="notif-dropdown-mark-all-form">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="link-action">Mark All</button>
                        </form>
                    </div>
                    <div class="notification-items" data-notification-items>
                        {{-- Skeleton shown until first poll resolves --}}
                        <div id="notif-skeleton-initial" style="padding:4px 0;">
                            @for($sk = 0; $sk < 4; $sk++)
                                <div class="sk-notif-row">
                                    <span class="sk" style="width:34px;height:34px;border-radius:999px;flex-shrink:0;"></span>
                                    <div style="flex:1;display:grid;gap:7px;padding-top:3px;">
                                        <span class="sk" style="height:11px;width:{{ [62,75,55,68][$sk] }}%;"></span>
                                        <span class="sk" style="height:10px;width:{{ [38,28,45,32][$sk] }}%;"></span>
                                    </div>
                                </div>
                            @endfor
                        </div>
                        {{-- Real content rendered server-side as fallback --}}
                        <div id="notif-real-content" style="display:none;">
                            @forelse($headerNotifications as $notification)
                                @php
                                    $titleLower = strtolower($notification->title);
                                    $notifIcon = 'fa-bell';
                                    $notifBg = '#f1f5f9';
                                    $notifColor = '#64748b';

                                    if (str_contains($titleLower, 'join') || str_contains($titleLower, 'request')) {
                                        $notifIcon = 'fa-user-plus';
                                        $notifBg = '#e0f2fe';
                                        $notifColor = '#0284c7';
                                    } elseif (str_contains($titleLower, 'payment') || str_contains($titleLower, 'fare') || str_contains($titleLower, 'paid')) {
                                        $notifIcon = 'fa-credit-card';
                                        $notifBg = '#dcfce7';
                                        $notifColor = '#16a34a';
                                    } elseif (str_contains($titleLower, 'trip') || str_contains($titleLower, 'car') || str_contains($titleLower, 'ride')) {
                                        $notifIcon = 'fa-car-side';
                                        $notifBg = '#f3e8ff';
                                        $notifColor = '#9333ea';
                                    }
                                @endphp
                                <div class="notification-item {{ $notification->is_read ? '' : 'unread' }}">
                                    <div class="notification-item-icon-col">
                                        <span class="notification-icon-badge" style="background: {{ $notifBg }}; color: {{ $notifColor }};">
                                            <i class="fa-solid {{ $notifIcon }}"></i>
                                        </span>
                                    </div>
                                    <div class="notification-item-content-col">
                                        <a href="{{ route('notifications.open', $notification) }}" class="notification-item-link">
                                            <div class="notification-item-title-row">
                                                <span class="notification-item-title">{{ $notification->title }}</span>
                                                @if(! $notification->is_read)
                                                    <span class="unread-dot-indicator"></span>
                                                @endif
                                            </div>
                                            <div class="notification-item-message">{{ $notification->message }}</div>
                                        </a>
                                        <div class="notification-item-row">
                                            <span class="notification-item-time">{{ $notification->created_at?->diffForHumans() }}</span>
                                            @if(! $notification->is_read)
                                                <form method="POST" action="{{ route('notifications.read', $notification) }}" class="notif-dropdown-mark-read-form" style="display:inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="link-action">Mark Read</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="notification-empty">No notifications.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="notification-footer">
                        <a href="{{ route('notifications.index') }}" class="notification-view-all">View All</a>
                    </div>
                </div>
            </details>
            <details class="profile-wrap">
                <summary class="profile-toggle">
                    <span class="avatar-initial">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                    <div class="profile-details">
                        <span class="profile-name">{{ explode(' ', auth()->user()->name)[0] }}</span>
                        <span class="profile-role">{{ ucfirst(auth()->user()->role ?? 'driver') }}</span>
                    </div>
                    <i class="fa-solid fa-chevron-down profile-chevron"></i>
                </summary>
                <div class="profile-dropdown">
                    <div class="profile-dropdown-header">
                        <span class="avatar-initial">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                        <div class="profile-dropdown-meta">
                            <span class="profile-dropdown-name">{{ auth()->user()->name }}</span>
                            <span class="profile-dropdown-role">{{ ucfirst(auth()->user()->role ?? 'driver') }}</span>
                        </div>
                    </div>
                    <div class="profile-dropdown-divider"></div>
                    <a href="{{ route('profile.index') }}" class="profile-menu-link">
                        <i class="fa-solid fa-gear"></i>
                        <span>Profile</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="profile-menu-btn profile-menu-logout">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </header>
    <div class="page-load-line" id="pageLoadLine" aria-hidden="true"></div>

    <div class="mobile-drawer-overlay" id="mobileDrawerOverlay"></div>
    <aside class="mobile-drawer" id="mobileDrawer">
        <div class="mobile-drawer-head">
            <div class="mobile-drawer-head-row">
                <button type="button" class="mobile-close-btn" id="mobileDrawerClose" aria-label="Close menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="CarpoolHub" class="mobile-drawer-logo">
                <div class="mobile-drawer-title">CarpoolHub</div>
            </div>
            <input type="text" class="mobile-drawer-search" placeholder="Search menu..." aria-label="Search menu">
        </div>

        @php
            $drawerRole = auth()->user()?->role;
            $drawerNavItems = match ($drawerRole) {
                'passenger' => [
                    ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
                    ['route' => 'explore.index', 'active' => ['explore.*'], 'icon' => 'fa-solid fa-compass', 'label' => 'Explore'],
                    ['route' => 'trips.index', 'active' => ['trips.*'], 'icon' => 'fa-solid fa-car-side', 'label' => 'My Trips'],
                    ['route' => 'payments.index', 'active' => ['payments.*'], 'icon' => 'fa-solid fa-wallet', 'label' => 'Payments'],
                    ['route' => 'connections.index', 'active' => ['connections.*'], 'icon' => 'fa-solid fa-user-group', 'label' => 'Connections'],
                    ['route' => 'notifications.index', 'active' => ['notifications.*'], 'icon' => 'fa-solid fa-bell', 'label' => 'Notifications', 'badge' => true],
                    ['route' => 'profile.index', 'active' => ['profile.*', 'settings.*'], 'icon' => 'fa-solid fa-user-gear', 'label' => 'Settings'],
                ],
                'admin' => [
                    ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
                    ['route' => 'trips.index', 'active' => ['trips.*'], 'icon' => 'fa-solid fa-car-side', 'label' => 'All Trips'],
                    ['route' => 'saved-routes.index', 'active' => ['saved-routes.*'], 'icon' => 'fa-solid fa-route', 'label' => 'Routes'],
                    ['route' => 'explore.index', 'active' => ['explore.*'], 'icon' => 'fa-solid fa-compass', 'label' => 'Explore'],
                    ['route' => 'connections.index', 'active' => ['connections.*'], 'icon' => 'fa-solid fa-user-group', 'label' => 'Connections'],
                    ['route' => 'payments.index', 'active' => ['payments.*'], 'icon' => 'fa-solid fa-wallet', 'label' => 'Payments'],
                    ['route' => 'notifications.index', 'active' => ['notifications.*'], 'icon' => 'fa-solid fa-bell', 'label' => 'Notifications', 'badge' => true],
                    ['route' => 'admin.users.index', 'active' => ['admin.users.*'], 'icon' => 'fa-solid fa-users-gear', 'label' => 'Users Admin'],
                    ['route' => 'admin.reports.index', 'active' => ['admin.reports.*'], 'icon' => 'fa-solid fa-chart-line', 'label' => 'Reports'],
                    ['route' => 'profile.index', 'active' => ['profile.*', 'settings.*'], 'icon' => 'fa-solid fa-user-gear', 'label' => 'Settings'],
                ],
                default => [
                    ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
                    ['route' => 'trips.create', 'active' => ['trips.create'], 'icon' => 'fa-solid fa-plus', 'label' => 'New Trip'],
                    ['route' => 'trips.index', 'active' => ['trips.index', 'trips.show', 'trips.edit', 'trips.requests.*'], 'icon' => 'fa-solid fa-car-side', 'label' => 'My Trips'],
                    ['route' => 'saved-routes.index', 'active' => ['saved-routes.*'], 'icon' => 'fa-solid fa-route', 'label' => 'Routes'],
                    ['route' => 'explore.index', 'active' => ['explore.*'], 'icon' => 'fa-solid fa-compass', 'label' => 'Explore'],
                    ['route' => 'payments.index', 'active' => ['payments.*'], 'icon' => 'fa-solid fa-wallet', 'label' => 'Payments'],
                    ['route' => 'connections.index', 'active' => ['connections.*'], 'icon' => 'fa-solid fa-user-group', 'label' => 'Connections'],
                    ['route' => 'notifications.index', 'active' => ['notifications.*'], 'icon' => 'fa-solid fa-bell', 'label' => 'Notifications', 'badge' => true],
                    ['route' => 'profile.index', 'active' => ['profile.*', 'settings.*'], 'icon' => 'fa-solid fa-user-gear', 'label' => 'Settings'],
                ],
            };
        @endphp

        <nav class="mobile-drawer-nav">
            @foreach($drawerNavItems as $item)
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs(...$item['active']) ? 'active' : '' }}">
                    <i class="{{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
                    @if(!empty($item['badge']) && ($headerUnreadCount ?? 0) > 0)
                        <span class="notification-badge mobile-drawer-badge">{{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}</span>
                    @endif
                </a>
            @endforeach
            <form action="{{ route('logout') }}" method="POST" style="padding: 0 8px;">
                @csrf
                <button type="submit" class="profile-menu-btn profile-menu-logout" style="width:100%;">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
        </nav>
    </aside>
@endauth

<div class="app-shell">
    @auth
        @include('layouts.partials.desktop-sidebar')
    @endauth

    <main>
        <section class="main-content">
            @if(session('status'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        window.showToast("{{ session('status') }}", 'success');
                    });
                </script>
            @endif
            @if(session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        window.showToast("{{ session('success') }}", 'success');
                    });
                </script>
            @endif
            @if(session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        window.showToast("{{ session('error') }}", 'error');
                    });
                </script>
            @endif
            @if($errors->any())
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        window.showToast("{{ $errors->first() }}", 'error');
                    });
                </script>
            @endif

            @yield('content')
        </section>
    </main>
</div>

@auth
    @include('layouts.partials.mobile-bottom-nav')
    <x-ai-chat />
@endauth

<script>
    window.showToast = function(message, type) {
        type = type || 'success';
        var container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        var card = document.createElement('div');
        card.className = 'toast-card';
        
        var iconHtml = '';
        if (type === 'success') {
            iconHtml = '<span class="toast-icon toast-icon-success"><i class="fa-solid fa-check"></i></span>';
        } else if (type === 'error') {
            iconHtml = '<span class="toast-icon toast-icon-error"><i class="fa-solid fa-xmark"></i></span>';
        } else {
            iconHtml = '<span class="toast-icon toast-icon-info"><i class="fa-solid fa-info"></i></span>';
        }

        function escHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        card.innerHTML = iconHtml + '<span class="toast-message">' + escHtml(message) + '</span>';
        container.appendChild(card);

        requestAnimationFrame(function() {
            card.classList.add('show');
        });

        setTimeout(function() {
            card.classList.add('hide');
            card.classList.remove('show');
            setTimeout(function() {
                if (card.parentNode) {
                    card.parentNode.removeChild(card);
                }
            }, 350);
        }, 3200);
    };

    (function () {
        var pageLoadLine = document.getElementById('pageLoadLine');
        var pageLoading = false;

        function startPageLoadLine() {
            if (!pageLoadLine || pageLoading) {
                return;
            }
            pageLoading = true;
            pageLoadLine.classList.add('active');
        }

        function stopPageLoadLine() {
            if (!pageLoadLine) {
                return;
            }
            pageLoadLine.classList.remove('active');
            pageLoading = false;
        }

        window.addEventListener('pageshow', stopPageLoadLine);
        window.addEventListener('load', stopPageLoadLine);

        var desktopToggle = document.getElementById('sidebarToggle');
        var desktopKey = 'carpoolhub_sidebar_expanded';
        var desktopQuery = window.matchMedia('(min-width: 1024px)');

        function forceCloseMobileDrawerOnDesktop() {
            if (!desktopQuery.matches) {
                return;
            }

            document.body.classList.remove('mobile-drawer-open');

            var drawer = document.getElementById('mobileDrawer');
            var overlay = document.getElementById('mobileDrawerOverlay');

            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
                overlay.style.display = 'none';
            }

            if (drawer) {
                drawer.style.pointerEvents = 'none';
                drawer.style.display = 'none';
            }
        }

        function syncDesktop() {
            var expanded = localStorage.getItem(desktopKey) === '1';
            document.body.classList.toggle('sidebar-expanded', expanded && desktopQuery.matches);
            forceCloseMobileDrawerOnDesktop();
        }

        syncDesktop();

        if (desktopToggle) {
            desktopToggle.addEventListener('click', function () {
                var nowExpanded = !document.body.classList.contains('sidebar-expanded');
                document.body.classList.toggle('sidebar-expanded', nowExpanded);
                localStorage.setItem(desktopKey, nowExpanded ? '1' : '0');
            });
        }

        desktopQuery.addEventListener('change', syncDesktop);
        forceCloseMobileDrawerOnDesktop();
        window.addEventListener('resize', forceCloseMobileDrawerOnDesktop);
        window.addEventListener('pageshow', forceCloseMobileDrawerOnDesktop);

        var mobileToggle = document.getElementById('mobileMenuToggle');
        var mobileBackBtn = document.getElementById('mobileBackBtn');
        var mobileClose = document.getElementById('mobileDrawerClose');
        var mobileOverlay = document.getElementById('mobileDrawerOverlay');
        var dropdownDetails = document.querySelectorAll('.notification-wrap, .profile-wrap, .more-menu, .bento-menu-wrap');

        function closeOpenPopups() {
            dropdownDetails.forEach(function (detail) {
                if (detail.hasAttribute('open')) {
                    detail.removeAttribute('open');
                }
            });
        }

        function openDrawer() {
            if (mobileOverlay) {
                mobileOverlay.style.display = '';
                mobileOverlay.style.opacity = '';
                mobileOverlay.style.pointerEvents = '';
            }
            var drawer = document.getElementById('mobileDrawer');
            if (drawer) {
                drawer.style.display = '';
                drawer.style.pointerEvents = '';
            }
            document.body.classList.add('mobile-drawer-open');
        }

        function closeDrawer() {
            document.body.classList.remove('mobile-drawer-open');
        }

        if (mobileToggle) {
            mobileToggle.addEventListener('click', openDrawer);
        }

        if (mobileBackBtn) {
            mobileBackBtn.addEventListener('click', function () {
                var beforeBackEvent = new CustomEvent('carpoolhub:mobile-back', { cancelable: true });
                var shouldContinueDefaultBack = window.dispatchEvent(beforeBackEvent);
                if (!shouldContinueDefaultBack) {
                    return;
                }

                if (document.referrer && document.referrer.indexOf(window.location.origin) === 0) {
                    window.history.back();
                    return;
                }

                var fallbackUrl = mobileBackBtn.getAttribute('data-fallback-url') || '/home';
                window.location.href = fallbackUrl;
            });
        }

        if (mobileClose) {
            mobileClose.addEventListener('click', closeDrawer);
        }

        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', closeDrawer);
        }

        document.addEventListener('click', function (event) {
            dropdownDetails.forEach(function (detail) {
                if (detail.hasAttribute('open') && !detail.contains(event.target)) {
                    detail.removeAttribute('open');
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeOpenPopups();
                closeDrawer();
            }
        });

        document.addEventListener('submit', function (e) {
            var form = e.target.closest('.notif-dropdown-mark-all-form');
            if (form) {
                e.preventDefault();
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: '_method=PATCH',
                    credentials: 'same-origin'
                }).then(function (r) {
                    if (!r.ok) return;
                    document.querySelectorAll('.notification-badge').forEach(function (badge) {
                        badge.style.display = 'none';
                    });
                    document.querySelectorAll('.notification-item.unread').forEach(function (item) {
                        item.classList.remove('unread');
                    });
                    document.querySelectorAll('.unread-dot-indicator').forEach(function (dot) {
                        dot.remove();
                    });
                    document.querySelectorAll('.notification-item-content-col form').forEach(function (f) {
                        f.remove();
                    });
                    if (window.showToast) {
                        window.showToast("All notifications marked as read.", "success");
                    }
                }).catch(function () {});
            }

            var readForm = e.target.closest('.notif-dropdown-mark-read-form');
            if (readForm) {
                e.preventDefault();
                fetch(readForm.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: '_method=PATCH',
                    credentials: 'same-origin'
                }).then(function (r) {
                    if (!r.ok) return;
                    var item = readForm.closest('.notification-item');
                    if (item) {
                        item.classList.remove('unread');
                        var dot = item.querySelector('.unread-dot-indicator');
                        if (dot) dot.remove();
                    }
                    readForm.remove();
                    document.querySelectorAll('.notification-badge').forEach(function (badge) {
                        var current = parseInt(badge.textContent.replace(/\D/g, ''), 10) || 0;
                        var next = current - 1;
                        if (next <= 0) {
                            badge.style.display = 'none';
                        } else {
                            badge.textContent = next > 99 ? '99+' : next;
                        }
                    });
                    if (window.showToast) {
                        window.showToast("Notification marked as read.", "success");
                    }
                }).catch(function () {});
            }
        });

        var bottomNavItems = document.querySelectorAll('.mobile-bottom-nav > a, .mobile-bottom-nav > details > summary');
        var bottomNavLinks = Array.prototype.slice.call(document.querySelectorAll('.mobile-bottom-nav > a'));
        var desktopNavLinks = Array.prototype.slice.call(document.querySelectorAll('.desktop-nav a'));
        var mainContent = document.querySelector('.main-content');
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var isMobileViewport = window.matchMedia('(max-width: 1023px)').matches;
        var isDesktopViewport = window.matchMedia('(min-width: 1024px)').matches;
        var enterKey = 'carpoolhub_mobile_enter_direction';
        var desktopEnterKey = 'carpoolhub_desktop_enter_direction';

        if (!reduceMotion && mainContent) {
            var savedEnterDirection = sessionStorage.getItem(enterKey);
            var savedDesktopEnterDirection = sessionStorage.getItem(desktopEnterKey);
            if (isMobileViewport && (savedEnterDirection === 'left' || savedEnterDirection === 'right')) {
                mainContent.classList.add(savedEnterDirection === 'right' ? 'page-enter-from-right' : 'page-enter-from-left');
                sessionStorage.removeItem(enterKey);
            } else if (isDesktopViewport && (savedDesktopEnterDirection === 'left' || savedDesktopEnterDirection === 'right')) {
                mainContent.classList.add(savedDesktopEnterDirection === 'right' ? 'page-enter-from-right' : 'page-enter-from-left');
                sessionStorage.removeItem(desktopEnterKey);
            } else if (isDesktopViewport) {
                mainContent.classList.add('page-enter-fade');
            }
        }

        if (mainContent) {
            mainContent.addEventListener('animationend', function () {
                mainContent.classList.remove('page-enter-from-right', 'page-enter-from-left', 'page-exit-to-left', 'page-exit-to-right', 'page-enter-fade', 'page-exit-fade');
            });
        }

        bottomNavItems.forEach(function (item) {
            item.addEventListener('click', function (event) {
                item.classList.remove('tap-animate');
                void item.offsetWidth;
                item.classList.add('tap-animate');

                if (item.tagName !== 'A') {
                    return;
                }

                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                var href = item.getAttribute('href');
                if (!href || href.charAt(0) === '#') {
                    return;
                }

                if (reduceMotion || !isMobileViewport || !mainContent) {
                    return;
                }

                var currentIndex = bottomNavLinks.findIndex(function (link) { return link.classList.contains('active'); });
                var targetIndex = bottomNavLinks.indexOf(item);
                if (currentIndex === -1 || targetIndex === -1 || currentIndex === targetIndex) {
                    return;
                }

                event.preventDefault();
                startPageLoadLine();

                var movingRight = targetIndex > currentIndex;
                var exitClass = movingRight ? 'page-exit-to-left' : 'page-exit-to-right';
                var enterDirection = movingRight ? 'right' : 'left';
                sessionStorage.setItem(enterKey, enterDirection);

                mainContent.classList.remove('page-exit-to-left', 'page-exit-to-right', 'page-enter-from-right', 'page-enter-from-left');
                void mainContent.offsetWidth;
                mainContent.classList.add(exitClass);

                window.setTimeout(function () {
                    window.location.href = href;
                }, 230);
            });
        });

        document.addEventListener('click', function (event) {
            var link = event.target.closest('a[href]');
            if (!link) {
                return;
            }
            if (link.classList.contains('mobile-back-btn') || link.hasAttribute('download')) {
                return;
            }
            if (link.target && link.target.toLowerCase() === '_blank') {
                return;
            }
            var href = link.getAttribute('href');
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
                return;
            }
            if (link.origin !== window.location.origin) {
                return;
            }
            if (link.pathname === window.location.pathname && link.search === window.location.search && link.hash) {
                return;
            }

            if (!reduceMotion && isDesktopViewport && mainContent) {
                var desktopTargetIndex = desktopNavLinks.indexOf(link);
                var desktopCurrentIndex = desktopNavLinks.findIndex(function (navLink) { return navLink.classList.contains('active'); });

                if (desktopTargetIndex !== -1 && desktopCurrentIndex !== -1 && desktopTargetIndex !== desktopCurrentIndex) {
                    event.preventDefault();
                    startPageLoadLine();

                    var desktopMovingRight = desktopTargetIndex > desktopCurrentIndex;
                    var desktopExitClass = desktopMovingRight ? 'page-exit-to-left' : 'page-exit-to-right';
                    var desktopEnterDirection = desktopMovingRight ? 'right' : 'left';
                    sessionStorage.setItem(desktopEnterKey, desktopEnterDirection);

                    mainContent.classList.remove('page-exit-to-left', 'page-exit-to-right', 'page-enter-from-right', 'page-enter-from-left', 'page-enter-fade', 'page-exit-fade');
                    void mainContent.offsetWidth;
                    mainContent.classList.add(desktopExitClass);

                    window.setTimeout(function () {
                        window.location.href = href;
                    }, 220);
                    return;
                }

                if (desktopTargetIndex === -1) {
                    event.preventDefault();
                    startPageLoadLine();

                    mainContent.classList.remove('page-exit-to-left', 'page-exit-to-right', 'page-enter-from-right', 'page-enter-from-left', 'page-enter-fade', 'page-exit-fade');
                    void mainContent.offsetWidth;
                    mainContent.classList.add('page-exit-fade');

                    window.setTimeout(function () {
                        window.location.href = href;
                    }, 190);
                    return;
                }
            }

            startPageLoadLine();
        });

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            startPageLoadLine();
        });

        var notificationsEndpoint = @json(route('refresh.notifications.latest'));
        var csrfToken = @json(csrf_token());
        var notificationsInFlight = false;
        var readRouteTemplate = @json(route('notifications.read', ['notification' => '__ID__']));
        function escHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function buildNotificationItemHtml(item) {
            var readAction = readRouteTemplate.replace('__ID__', String(item.id));
            var unreadClass = item.is_read ? '' : ' unread';
            
            var titleLower = String(item.title || '').toLowerCase();
            var notifIcon = 'fa-bell';
            var notifBg = '#f1f5f9';
            var notifColor = '#64748b';

            if (titleLower.indexOf('join') !== -1 || titleLower.indexOf('request') !== -1) {
                notifIcon = 'fa-user-plus';
                notifBg = '#e0f2fe';
                notifColor = '#0284c7';
            } else if (titleLower.indexOf('payment') !== -1 || titleLower.indexOf('fare') !== -1 || titleLower.indexOf('paid') !== -1) {
                notifIcon = 'fa-credit-card';
                notifBg = '#dcfce7';
                notifColor = '#16a34a';
            } else if (titleLower.indexOf('trip') !== -1 || titleLower.indexOf('car') !== -1 || titleLower.indexOf('ride') !== -1) {
                notifIcon = 'fa-car-side';
                notifBg = '#f3e8ff';
                notifColor = '#9333ea';
            }

            var unreadDot = item.is_read
                ? ''
                : '<span class="unread-dot-indicator"></span>';

            var readButton = item.is_read
                ? ''
                : '<form method="POST" action="' + readAction + '" style="display:inline;">\
                        <input type="hidden" name="_token" value="' + csrfToken + '">\
                        <input type="hidden" name="_method" value="PATCH">\
                        <button type="submit" class="link-action">Mark Read</button>\
                   </form>';

            return '<div class="notification-item' + unreadClass + '">\
                        <div class="notification-item-icon-col">\
                            <span class="notification-icon-badge" style="background: ' + notifBg + '; color: ' + notifColor + ';">\
                                <i class="fa-solid ' + notifIcon + '"></i>\
                            </span>\
                        </div>\
                        <div class="notification-item-content-col">\
                            <a href="' + escHtml(item.open_url || item.target_url || '#') + '" class="notification-item-link">\
                                <div class="notification-item-title-row">\
                                    <span class="notification-item-title">' + escHtml(item.title || '') + '</span>\
                                    ' + unreadDot + '\
                                </div>\
                                <div class="notification-item-message">' + escHtml(item.message || '') + '</div>\
                            </a>\
                            <div class="notification-item-row">\
                                <span class="notification-item-time">' + escHtml(item.time_ago || '') + '</span>\
                                ' + readButton + '\
                            </div>\
                        </div>\
                    </div>';
        }

        function refreshNotificationDropdown(payload) {
            var unreadCount = Number(payload && payload.unread_count ? payload.unread_count : 0);
            var notificationItems = Array.isArray(payload && payload.notifications) ? payload.notifications : [];

            document.querySelectorAll('.notification-badge').forEach(function (badge) {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                }
            });

            var listHtml = notificationItems.length
                ? notificationItems.map(buildNotificationItemHtml).join('')
                : '<div class=\"notification-empty\">No notifications.</div>';

            document.querySelectorAll('[data-notification-items]').forEach(function (container) {
                container.innerHTML = listHtml;
            });
        }

        function shouldPollNotifications() {
            if (document.visibilityState !== 'visible') {
                return false;
            }
            var openDropdown = document.querySelector('.notification-wrap[open]');
            if (openDropdown) {
                return true;
            }
            return window.location.pathname === @json(route('notifications.index', [], false));
        }

        var notifFirstLoad = true;

        function showNotifSkeleton() {
            var sk = document.getElementById('notif-skeleton-initial');
            var real = document.getElementById('notif-real-content');
            if (sk) sk.style.display = '';
            if (real) real.style.display = 'none';
        }

        function hideNotifSkeleton() {
            var sk = document.getElementById('notif-skeleton-initial');
            var real = document.getElementById('notif-real-content');
            if (sk) sk.style.display = 'none';
            if (real) real.style.display = '';
        }

        function pollNotifications() {
            if (notificationsInFlight || !shouldPollNotifications()) {
                return;
            }
            notificationsInFlight = true;
            fetch(notificationsEndpoint, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) return null;
                    return response.json();
                })
                .then(function (payload) {
                    if (payload) {
                        refreshNotificationDropdown(payload);
                        if (notifFirstLoad) {
                            hideNotifSkeleton();
                            notifFirstLoad = false;
                        }
                    }
                })
                .catch(function () {
                    if (notifFirstLoad) { hideNotifSkeleton(); notifFirstLoad = false; }
                })
                .finally(function () {
                    notificationsInFlight = false;
                });
        }

        /* Show skeleton when dropdown is freshly opened */
        document.addEventListener('toggle', function (e) {
            if (e.target && e.target.classList && e.target.classList.contains('notification-wrap')) {
                if (e.target.open && notifFirstLoad) { showNotifSkeleton(); }
            }
            /* Lock background scroll while the bento menu is open — it has no
               backdrop of its own, so without this the page behind it still scrolls. */
            if (e.target && e.target.classList && e.target.classList.contains('bento-menu-wrap')) {
                document.body.classList.toggle('bento-menu-open', e.target.open);
            }
        }, true);

        window.setInterval(pollNotifications, 5000);
    })();

    // Bento Menu search filtering
    document.querySelectorAll('[data-bento-search]').forEach(function(searchInput) {
        searchInput.addEventListener('input', function() {
            var query = searchInput.value.trim().toLowerCase();
            var dropdown = searchInput.closest('.bento-menu-dropdown');
            if (!dropdown) return;

            var items = dropdown.querySelectorAll('[data-bento-item]');
            var sections = dropdown.querySelectorAll('[data-bento-section]');

            items.forEach(function(item) {
                var text = item.textContent.toLowerCase();
                if (text.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });

            sections.forEach(function(section) {
                var visibleItems = section.querySelectorAll('[data-bento-item]:not([style*="display: none"])');
                if (visibleItems.length === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = 'block';
                }
            });
        });
    });
</script>
</body>
</html>
