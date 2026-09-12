@php
    $role = auth()->user()?->role;
    $chatBadge = $headerChatUnreadCount ?? 0;

    $navItems = match ($role) {
        'admin' => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon_inactive' => 'fa-solid fa-house', 'icon_active' => 'fa-solid fa-house', 'label' => 'Home'],
            // 'admin.*' minus admin.reports.* — Reports gets its own icon below, so
            // this stays exclusive with it instead of both lighting up on /admin/reports.
            ['route' => 'admin.users.index', 'active' => ['admin.users.*', 'admin.audit-log.*', 'admin.messages.*', 'admin.system-settings.*'], 'icon_inactive' => 'fa-solid fa-user-shield', 'icon_active' => 'fa-solid fa-user-shield', 'label' => 'Admin'],
            ['route' => 'admin.reports.index', 'active' => ['admin.reports.*'], 'icon_inactive' => 'fa-regular fa-chart-bar', 'icon_active' => 'fa-solid fa-chart-bar', 'label' => 'Reports'],
            ['route' => 'trips.index', 'active' => ['trips.*'], 'icon_inactive' => 'fa-solid fa-car-side', 'icon_active' => 'fa-solid fa-car-side', 'label' => 'Trips'],
            ['route' => 'payments.index', 'active' => ['payments.*'], 'icon_inactive' => 'fa-regular fa-credit-card', 'icon_active' => 'fa-solid fa-credit-card', 'label' => 'Payments'],
        ],
        'passenger' => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon_inactive' => 'fa-solid fa-house', 'icon_active' => 'fa-solid fa-house', 'label' => 'Home'],
            ['route' => 'trips.index', 'active' => ['trips.*'], 'icon_inactive' => 'fa-solid fa-car-side', 'icon_active' => 'fa-solid fa-car-side', 'label' => 'Trips'],
            ['route' => 'explore.index', 'active' => ['explore.*'], 'icon_inactive' => 'fa-regular fa-compass', 'icon_active' => 'fa-solid fa-compass', 'label' => 'Explore'],
            ['route' => 'payments.index', 'active' => ['payments.*'], 'icon_inactive' => 'fa-regular fa-credit-card', 'icon_active' => 'fa-solid fa-credit-card', 'label' => 'Payments'],
            // Was Connect -> connections.index; that page is still reachable
            // from the header dropdown, desktop sidebar, and home — freeing
            // this slot for Chat.
            ['route' => 'chats.index', 'active' => ['chats.*'], 'icon_inactive' => 'fa-regular fa-comment-dots', 'icon_active' => 'fa-solid fa-comment-dots', 'label' => 'Chat', 'badge' => $chatBadge],
        ],
        default => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon_inactive' => 'fa-solid fa-house', 'icon_active' => 'fa-solid fa-house', 'label' => 'Home'],
            ['route' => 'trips.index', 'active' => ['trips.index', 'trips.show', 'trips.edit', 'trips.requests.*'], 'icon_inactive' => 'fa-solid fa-car-side', 'icon_active' => 'fa-solid fa-car-side', 'label' => 'Trips'],
            ['route' => 'explore.index', 'active' => ['explore.*'], 'icon_inactive' => 'fa-regular fa-compass', 'icon_active' => 'fa-solid fa-compass', 'label' => 'Explore'],
            ['route' => 'payments.index', 'active' => ['payments.*'], 'icon_inactive' => 'fa-regular fa-credit-card', 'icon_active' => 'fa-solid fa-credit-card', 'label' => 'Payments'],
            // Was New Trip -> trips.create; that button still lives at the top
            // of the Trips page itself, so this slot goes to Chat — same
            // order/position as the passenger nav above.
            ['route' => 'chats.index', 'active' => ['chats.*'], 'icon_inactive' => 'fa-regular fa-comment-dots', 'icon_active' => 'fa-solid fa-comment-dots', 'label' => 'Chat', 'badge' => $chatBadge],
        ],
    };
@endphp

<nav class="mobile-bottom-nav">
    @foreach($navItems as $item)
        @php
            $isActive = request()->routeIs(...$item['active']);
            $classes = $isActive ? 'active' : '';
            $iconClass = $isActive ? $item['icon_active'] : $item['icon_inactive'];
            $badge = $item['badge'] ?? 0;
        @endphp
        <a href="{{ route($item['route']) }}" class="{{ $classes }}" @isset($item['aria']) aria-label="{{ $item['aria'] }}" @endisset>
            <span class="icon">
                <i class="{{ $iconClass }}"></i>
                @if($badge > 0)
                    <span class="notification-badge">{{ $badge > 99 ? '99+' : $badge }}</span>
                @endif
            </span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
