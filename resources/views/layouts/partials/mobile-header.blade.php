<header class="mobile-header {{ request()->routeIs('home') || request()->routeIs('dashboard') ? '' : 'has-back-btn' }}">
    <div class="mobile-header-left">
        @if(!request()->routeIs('home') && !request()->routeIs('dashboard'))
            <button
                type="button"
                class="mobile-back-btn"
                id="mobileBackBtn"
                aria-label="Go back"
                title="Go back"
                data-fallback-url="{{ route('home') }}"
            >
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        @endif
        <a href="{{ route('home') }}" class="header-logo-link" aria-label="Go to Home">
            <img src="{{ asset('build/assets/branding/logo-horizontal-b.png') }}" alt="CarpoolHub" class="mobile-brand-logo">
        </a>
    </div>

    <div class="mobile-header-right">
        <details class="notification-wrap">
            <summary class="notification-toggle" style="list-style:none;">
                <i class="fa-solid fa-bell" aria-hidden="true"></i>
                @if($headerUnreadCount > 0)
                    <span class="notification-badge">{{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}</span>
                @endif
            </summary>
            <div class="notification-dropdown">
                <div class="notification-dropdown-head">
                    <strong>Notifications</strong>
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="link-action">Mark all</button>
                    </form>
                </div>
                <div class="notification-items" data-notification-items>
                    @forelse($headerNotifications as $notification)
                        <div class="notification-item {{ $notification->is_read ? '' : 'unread' }}">
                            <a href="{{ route('notifications.open', $notification) }}" class="notification-item-link">
                                <div class="notification-item-title">{{ $notification->title }}</div>
                                <div class="notification-item-message">{{ $notification->message }}</div>
                            </a>
                            <div class="notification-item-row">
                                <span class="notification-item-time">{{ $notification->created_at?->diffForHumans() }}</span>
                                @if(! $notification->is_read)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="link-action">Read</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="notification-empty">No notifications.</div>
                    @endforelse
                </div>

                <div class="notification-footer">
                    <a href="{{ route('notifications.index') }}" class="notification-view-all">View all</a>
                </div>
            </div>
        </details>

        <details class="profile-wrap">
            <summary class="profile-toggle" style="list-style:none;">
                <span class="avatar-initial">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
            </summary>
            <div class="profile-dropdown">
                <a href="{{ route('profile.index') }}" class="profile-menu-link">
                    <i class="fa-solid fa-user-gear"></i>
                    <span>Profile</span>
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="profile-menu-btn">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </details>
    </div>
</header>
