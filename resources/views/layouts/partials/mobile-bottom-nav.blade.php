@php
    $role = auth()->user()?->role;

    $navItems = match ($role) {
        'admin' => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon_inactive' => 'fa-solid fa-house', 'icon_active' => 'fa-solid fa-house', 'label' => 'Home'],
            ['route' => 'admin.users.index', 'active' => ['admin.users.*'], 'icon_inactive' => 'fa-regular fa-user', 'icon_active' => 'fa-solid fa-user', 'label' => 'Users'],
            ['route' => 'admin.reports.index', 'active' => ['admin.reports.*'], 'icon_inactive' => 'fa-regular fa-chart-bar', 'icon_active' => 'fa-solid fa-chart-bar', 'label' => 'Reports'],
            ['route' => 'trips.index', 'active' => ['trips.*'], 'icon_inactive' => 'fa-solid fa-car-side', 'icon_active' => 'fa-solid fa-car-side', 'label' => 'Trips'],
            ['route' => 'payments.index', 'active' => ['payments.*'], 'icon_inactive' => 'fa-regular fa-credit-card', 'icon_active' => 'fa-solid fa-credit-card', 'label' => 'Payments'],
        ],
        'passenger' => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon_inactive' => 'fa-solid fa-house', 'icon_active' => 'fa-solid fa-house', 'label' => 'Home'],
            ['route' => 'trips.index', 'active' => ['trips.*'], 'icon_inactive' => 'fa-solid fa-car-side', 'icon_active' => 'fa-solid fa-car-side', 'label' => 'Trips'],
            ['route' => 'explore.index', 'active' => ['explore.*'], 'icon_inactive' => 'fa-regular fa-compass', 'icon_active' => 'fa-solid fa-compass', 'label' => 'Explore'],
            ['route' => 'payments.index', 'active' => ['payments.*'], 'icon_inactive' => 'fa-regular fa-credit-card', 'icon_active' => 'fa-solid fa-credit-card', 'label' => 'Payments'],
            ['route' => 'connections.index', 'active' => ['connections.*'], 'icon_inactive' => 'fa-solid fa-user-group', 'icon_active' => 'fa-solid fa-user-group', 'label' => 'Connect'],
        ],
        default => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon_inactive' => 'fa-solid fa-house', 'icon_active' => 'fa-solid fa-house', 'label' => 'Home'],
            ['route' => 'explore.index', 'active' => ['explore.*'], 'icon_inactive' => 'fa-regular fa-compass', 'icon_active' => 'fa-solid fa-compass', 'label' => 'Explore'],
            ['route' => 'trips.create', 'active' => ['trips.create'], 'icon_inactive' => 'fa-regular fa-square-plus', 'icon_active' => 'fa-solid fa-square-plus', 'label' => 'New Trip', 'aria' => 'Create trip'],
            ['route' => 'trips.index', 'active' => ['trips.index', 'trips.show', 'trips.edit', 'trips.requests.*'], 'icon_inactive' => 'fa-solid fa-car-side', 'icon_active' => 'fa-solid fa-car-side', 'label' => 'Trips'],
            ['route' => 'payments.index', 'active' => ['payments.*'], 'icon_inactive' => 'fa-regular fa-credit-card', 'icon_active' => 'fa-solid fa-credit-card', 'label' => 'Payments'],
        ],
    };
@endphp

<nav class="mobile-bottom-nav">
    @foreach($navItems as $item)
        @php
            $isActive = request()->routeIs(...$item['active']);
            $classes = $isActive ? 'active' : '';
            $iconClass = $isActive ? $item['icon_active'] : $item['icon_inactive'];
        @endphp
        <a href="{{ route($item['route']) }}" class="{{ $classes }}" @isset($item['aria']) aria-label="{{ $item['aria'] }}" @endisset>
            <span class="icon"><i class="{{ $iconClass }}"></i></span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
