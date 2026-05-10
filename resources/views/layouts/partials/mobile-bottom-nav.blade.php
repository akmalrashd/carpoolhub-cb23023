<nav class="mobile-bottom-nav">
    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') || request()->routeIs('dashboard') ? 'active' : '' }}">
        <span class="icon"><i class="fa-solid fa-house"></i></span>
        <span>Utama</span>
    </a>
    <a href="{{ route('explore.index') }}" class="{{ request()->routeIs('explore.*') ? 'active' : '' }}">
        <span class="icon"><i class="fa-solid fa-compass"></i></span>
        <span>Jelajah Trip</span>
    </a>
    <a href="{{ route('trips.index') }}" class="{{ request()->routeIs('trips.*') ? 'active' : '' }}">
        <span class="icon"><i class="fa-solid fa-car-side"></i></span>
        <span>Trip Saya</span>
    </a>
    <a href="{{ route('profile.index') }}" class="{{ request()->routeIs('profile.*') || request()->routeIs('settings.*') ? 'active' : '' }}">
        <span class="icon"><i class="fa-solid fa-user-gear"></i></span>
        <span>Profil</span>
    </a>
</nav>
