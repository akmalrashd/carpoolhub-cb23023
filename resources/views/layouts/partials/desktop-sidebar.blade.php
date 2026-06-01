<aside class="desktop-sidebar">
    {{-- MAIN group --}}
    <div class="desktop-nav-group">
        <div class="desktop-nav-group-label">Main</div>
        <nav class="desktop-nav">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') || request()->routeIs('dashboard') ? 'active' : '' }}" title="Home">
                <i class="fa-solid fa-house"></i><span class="desktop-nav-label">Home</span>
            </a>
            <a href="{{ route('trips.index') }}" class="{{ request()->routeIs('trips.*') ? 'active' : '' }}" title="My Trips">
                <i class="fa-solid fa-car-side"></i><span class="desktop-nav-label">My Trips</span>
            </a>
            <a href="{{ route('explore.index') }}" class="{{ request()->routeIs('explore.*') ? 'active' : '' }}" title="Explore">
                <i class="fa-solid fa-compass"></i><span class="desktop-nav-label">Explore</span>
            </a>
            <a href="{{ route('saved-routes.index') }}" class="{{ request()->routeIs('saved-routes.*') ? 'active' : '' }}" title="Routes">
                <i class="fa-solid fa-route"></i><span class="desktop-nav-label">Routes</span>
            </a>
            <a href="{{ route('connections.index') }}" class="{{ request()->routeIs('connections.*') ? 'active' : '' }}" title="Connections">
                <i class="fa-solid fa-user-group"></i><span class="desktop-nav-label">Connections</span>
            </a>
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
                <i class="fa-solid fa-bell"></i><span class="desktop-nav-label">Notifications</span>
            </a>
            <a href="{{ route('profile.index') }}" class="{{ request()->routeIs('profile.*') || request()->routeIs('settings.*') ? 'active' : '' }}" title="Settings">
                <i class="fa-solid fa-user-gear"></i><span class="desktop-nav-label">Settings</span>
            </a>
        </nav>
    </div>

    @if(auth()->check() && auth()->user()->role === 'admin')
        {{-- ADMIN group --}}
        <div class="desktop-nav-group">
            <div class="desktop-nav-group-label">Admin</div>
            <nav class="desktop-nav">
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" title="Users Admin">
                    <i class="fa-solid fa-users-gear"></i><span class="desktop-nav-label">Users</span>
                </a>
                <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" title="Reports">
                    <i class="fa-solid fa-chart-line"></i><span class="desktop-nav-label">Reports</span>
                </a>
            </nav>
        </div>
    @endif
</aside>
