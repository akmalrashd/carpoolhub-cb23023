@php
    $role = auth()->user()?->role;

    $navItems = match ($role) {
        'admin' => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
            ['route' => 'admin.users.index', 'active' => ['admin.users.*'], 'icon' => 'fa-solid fa-users-gear', 'label' => 'Users'],
            ['route' => 'admin.reports.index', 'active' => ['admin.reports.*'], 'icon' => 'fa-solid fa-chart-line', 'label' => 'Reports', 'fab' => true],
            ['route' => 'trips.index', 'active' => ['trips.*'], 'icon' => 'fa-solid fa-car-side', 'label' => 'Trips'],
            ['route' => 'profile.index', 'active' => ['profile.*', 'settings.*'], 'icon' => 'fa-solid fa-user', 'label' => 'Profile'],
        ],
        'passenger' => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
            ['route' => 'trips.index', 'active' => ['trips.*'], 'icon' => 'fa-solid fa-car-side', 'label' => 'Trips'],
            ['route' => 'explore.index', 'active' => ['explore.*'], 'icon' => 'fa-solid fa-compass', 'label' => 'Explore', 'fab' => true],
            ['route' => 'payments.index', 'active' => ['payments.*'], 'icon' => 'fa-solid fa-wallet', 'label' => 'Payments'],
            ['route' => 'profile.index', 'active' => ['profile.*', 'settings.*'], 'icon' => 'fa-solid fa-user', 'label' => 'Profile'],
        ],
        default => [
            ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
            ['route' => 'explore.index', 'active' => ['explore.*'], 'icon' => 'fa-solid fa-compass', 'label' => 'Explore'],
            ['route' => 'trips.create', 'active' => ['trips.create'], 'icon' => 'fa-solid fa-plus', 'label' => 'New Trip', 'fab' => true, 'aria' => 'Create trip'],
            ['route' => 'trips.index', 'active' => ['trips.index', 'trips.show', 'trips.edit', 'trips.requests.*'], 'icon' => 'fa-solid fa-car-side', 'label' => 'Trips'],
            ['route' => 'profile.index', 'active' => ['profile.*', 'settings.*'], 'icon' => 'fa-solid fa-user', 'label' => 'Profile'],
        ],
    };
@endphp

<nav class="mobile-bottom-nav">
    @foreach($navItems as $item)
        @php
            $isActive = request()->routeIs(...$item['active']);
            $classes = trim(($item['fab'] ?? false ? 'nav-fab ' : '') . ($isActive ? 'active' : ''));
        @endphp
        <a href="{{ route($item['route']) }}" class="{{ $classes }}" @isset($item['aria']) aria-label="{{ $item['aria'] }}" @endisset>
            <span class="icon"><i class="{{ $item['icon'] }}"></i></span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
