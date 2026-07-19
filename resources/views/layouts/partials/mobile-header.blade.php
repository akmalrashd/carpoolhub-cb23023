<header class="mobile-header {{ request()->routeIs('home') || request()->routeIs('dashboard') ? '' : 'has-back-btn' }}">
    <div class="mobile-header-left">
        @if(!request()->routeIs('home') && !request()->routeIs('dashboard'))
            <button
                type="button"
                class="mobile-back-btn"
                id="mobileBackBtn"
                aria-label="Back"
                title="Back"
                data-fallback-url="{{ route('home') }}"
            >
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        @endif
        <a href="{{ route('home') }}" class="header-logo-link" aria-label="Go to Home">
            <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="" class="mobile-brand-logo">
            <span class="mobile-brand-text">Carpool<span>Hub</span></span>
        </a>
    </div>

    <div class="mobile-header-right">
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
        <details class="notification-wrap">
            <summary class="notification-toggle {{ $headerUnreadCount > 0 ? 'has-unread' : '' }}" style="list-style:none;">
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

                <div class="notification-footer">
                    <a href="{{ route('notifications.index') }}" class="notification-view-all">View All</a>
                </div>
            </div>
        </details>

        <details class="profile-wrap">
            <summary class="profile-toggle" style="list-style:none;">
                <span class="avatar-initial">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
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
                    <i class="fa-solid fa-user-gear"></i>
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
