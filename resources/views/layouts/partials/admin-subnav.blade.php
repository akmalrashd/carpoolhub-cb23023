{{-- Shared secondary nav for the 5 admin pages — keeps them feeling like one
     section instead of separate destinations. Source of truth: config/admin_nav.php --}}
<nav class="admin-subnav">
    @foreach(config('admin_nav') as $adminItem)
        <a href="{{ route($adminItem['route']) }}" class="{{ request()->routeIs(...$adminItem['active']) ? 'active' : '' }}">
            <i class="{{ $adminItem['icon'] }}"></i> {{ $adminItem['label'] }}
        </a>
    @endforeach
</nav>
