<aside class="desktop-sidebar">
    <nav class="desktop-nav">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') || request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i><span class="desktop-nav-label">Utama</span>
        </a>
        <a href="{{ route('trips.index') }}" class="{{ request()->routeIs('trips.*') ? 'active' : '' }}">
            <i class="fa-solid fa-car-side"></i><span class="desktop-nav-label">Trip Saya</span>
        </a>
        <a href="{{ route('explore.index') }}" class="{{ request()->routeIs('explore.*') ? 'active' : '' }}">
            <i class="fa-solid fa-compass"></i><span class="desktop-nav-label">Jelajah Trip</span>
        </a>
        <a href="{{ route('connections.index') }}" class="{{ request()->routeIs('connections.*') ? 'active' : '' }}">
            <i class="fa-solid fa-user-group"></i><span class="desktop-nav-label">Sambungan</span>
        </a>
        <a href="{{ route('saved-routes.index') }}" class="{{ request()->routeIs('saved-routes.*') ? 'active' : '' }}">
            <i class="fa-solid fa-route"></i><span class="desktop-nav-label">Laluan</span>
        </a>
        <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}">
            <i class="fa-solid fa-wallet"></i><span class="desktop-nav-label">Pembayaran</span>
        </a>
        <a href="{{ route('billing-cycles.index') }}" class="{{ request()->routeIs('billing-cycles.*') ? 'active' : '' }}">
            <i class="fa-solid fa-calendar-check"></i><span class="desktop-nav-label">Ringkasan Bulanan</span>
        </a>
        <a href="{{ route('archive.index') }}" class="{{ request()->routeIs('archive.*') ? 'active' : '' }}">
            <i class="fa-solid fa-box-archive"></i><span class="desktop-nav-label">Arkib</span>
        </a>
        <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">
            <i class="fa-solid fa-bell"></i><span class="desktop-nav-label">Notifikasi</span>
        </a>
        <a href="{{ route('profile.index') }}" class="{{ request()->routeIs('profile.*') || request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="fa-solid fa-user-gear"></i><span class="desktop-nav-label">Tetapan</span>
        </a>
        @if(auth()->check() && auth()->user()->role === 'admin')
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users-gear"></i><span class="desktop-nav-label">Pengguna Admin</span>
            </a>
            <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-line"></i><span class="desktop-nav-label">Laporan</span>
            </a>
        @endif
    </nav>
</aside>
