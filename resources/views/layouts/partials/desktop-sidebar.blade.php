@php
    $role = auth()->user()?->role;

    $mainNavItems = match ($role) {
        'passenger' => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
            ['route' => 'explore.index', 'active' => ['explore.*'], 'icon' => 'fa-solid fa-compass', 'label' => 'Explore'],
            ['route' => 'trips.index', 'active' => ['trips.*'], 'icon' => 'fa-solid fa-car-side', 'label' => 'My Trips'],
            ['route' => 'connections.index', 'active' => ['connections.*'], 'icon' => 'fa-solid fa-user-group', 'label' => 'Connections'],
        ],
        default => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
            ['route' => 'trips.index', 'active' => ['trips.*'], 'icon' => 'fa-solid fa-car-side', 'label' => $role === 'admin' ? 'All Trips' : 'My Trips'],
            ['route' => 'saved-routes.index', 'active' => ['saved-routes.*'], 'icon' => 'fa-solid fa-route', 'label' => 'Routes'],
            ['route' => 'explore.index', 'active' => ['explore.*'], 'icon' => 'fa-solid fa-compass', 'label' => 'Explore'],
            ['route' => 'connections.index', 'active' => ['connections.*'], 'icon' => 'fa-solid fa-user-group', 'label' => 'Connections'],
        ],
    };
@endphp

<aside class="desktop-sidebar">
    {{-- MAIN group --}}
    <div class="desktop-nav-group">
        <div class="desktop-nav-group-label">Main</div>
        <nav class="desktop-nav">
            @foreach($mainNavItems as $item)
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs(...$item['active']) ? 'active' : '' }}" title="{{ $item['label'] }}">
                    <i class="{{ $item['icon'] }}"></i><span class="desktop-nav-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    {{-- MONEY group --}}
    <div class="desktop-nav-group">
        <div class="desktop-nav-group-label">Money</div>
        <nav class="desktop-nav">
            <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}" title="Payments">
                <i class="fa-solid fa-wallet"></i><span class="desktop-nav-label">Payments</span>
            </a>
        </nav>
    </div>

    {{-- Account (no label in collapsed, unlabeled group) --}}
    <div class="desktop-nav-group">
        <div class="desktop-nav-group-label">Account</div>
        <nav class="desktop-nav">
            <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" title="Notifications">
                <span class="desktop-nav-icon">
                    <i class="fa-solid fa-bell"></i>
                    @if(($headerUnreadCount ?? 0) > 0)
                        <span class="notification-badge">{{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}</span>
                    @endif
                </span>
                <span class="desktop-nav-label">Notifications</span>
            </a>
            <a href="{{ route('profile.index') }}" class="{{ request()->routeIs('profile.*') || request()->routeIs('settings.*') ? 'active' : '' }}" title="Settings">
                <i class="fa-solid fa-user-gear"></i><span class="desktop-nav-label">Settings</span>
            </a>
        </nav>
    </div>

    @if(auth()->check() && auth()->user()->role === 'admin')
        {{-- ADMIN group: single entry point — the 5 admin tools live behind
             the in-page tab-strip now (admin-subnav.blade.php), not here. --}}
        <div class="desktop-nav-group">
            <div class="desktop-nav-group-label">Admin</div>
            <nav class="desktop-nav">
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}" title="Admin Panel">
                    <i class="fa-solid fa-user-shield"></i><span class="desktop-nav-label">Admin Panel</span>
                </a>
            </nav>
        </div>
    @endif
</aside>
