@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/explore-search.css') }}?v={{ filemtime(public_path('css/explore-search.css')) }}">
    @endpush

    <div class="xs2-page">

        {{-- ── Top bar ──────────────────────────────────────────────── --}}
        <div class="xs2-topbar">
            <a href="{{ route('explore.index') }}" class="xs2-icon-btn" aria-label="Back to Explore">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h1 class="xs2-topbar-title">Search Trips</h1>
        </div>

        {{-- ── Two-field location search (Grab-style stacked pins) ─────── --}}
        <form method="GET" action="{{ route('explore.index') }}" id="exploreSearchForm" class="xs2-form">
            <div class="xs2-fields">
                <div class="xs2-field-pill" data-target="pickup">
                    <span class="xs2-field-dot xs2-field-dot-pickup"></span>
                    <input
                        id="search_pickup"
                        type="text"
                        name="pickup"
                        class="xs2-field-input"
                        value="{{ request('pickup') }}"
                        placeholder="Pickup area (optional)"
                        autocomplete="off"
                    >
                </div>
                <div class="xs2-field-connector"><span></span><span></span><span></span></div>
                <div class="xs2-field-pill" data-target="destination">
                    <span class="xs2-field-dot xs2-field-dot-dest"></span>
                    <input
                        id="search_destination"
                        type="text"
                        name="destination"
                        class="xs2-field-input"
                        value="{{ old('destination', $prefill) }}"
                        placeholder="Where to?"
                        autocomplete="off"
                        autofocus
                    >
                </div>
            </div>

            {{-- Quick filters --}}
            <div class="xs2-quick-row">
                <label class="xs2-pill-field">
                    <i class="fa-regular fa-calendar"></i>
                    <input type="date" name="date" value="{{ request('date') }}">
                </label>
                <select class="xs2-pill-field" name="seats">
                    <option value="">Any seats</option>
                    <option value="1" {{ request('seats') === '1' ? 'selected' : '' }}>1 seat</option>
                    <option value="2plus" {{ request('seats') === '2plus' ? 'selected' : '' }}>2+ seats</option>
                </select>
                <select class="xs2-pill-field" name="sort">
                    <option value="nearest" {{ request('sort', 'nearest') === 'nearest' ? 'selected' : '' }}>Nearest date</option>
                    <option value="latest"  {{ request('sort') === 'latest' ? 'selected' : '' }}>Latest date</option>
                </select>
            </div>

            {{-- Hidden coordinate inputs, set by the map picker below --}}
            <input type="hidden" id="search_radius_km"      name="radius_km"      value="{{ request('radius_km', 5) }}">
            <input type="hidden" id="search_center_lat"      name="center_lat"      value="{{ request('center_lat') }}">
            <input type="hidden" id="search_center_lng"      name="center_lng"      value="{{ request('center_lng') }}">
            <input type="hidden" id="search_pickup_lat"      name="pickup_lat"      value="{{ request('pickup_lat') }}">
            <input type="hidden" id="search_pickup_lng"      name="pickup_lng"      value="{{ request('pickup_lng') }}">
            <input type="hidden" id="search_destination_lat" name="destination_lat" value="{{ request('destination_lat') }}">
            <input type="hidden" id="search_destination_lng" name="destination_lng" value="{{ request('destination_lng') }}">

            <button type="submit" class="xs2-submit-btn">
                <i class="fa-solid fa-magnifying-glass"></i> Find Trips
            </button>
        </form>

        {{-- ── Recent / Suggested tabs, replaced by live search results while typing ── --}}
        <div class="xs2-list-card">
            <div class="xs2-tabs" role="tablist" id="searchTabs">
                <button type="button" class="xs2-tab active" data-panel="recent" role="tab" aria-selected="true">Recent</button>
                <button type="button" class="xs2-tab" data-panel="suggested" role="tab" aria-selected="false">Suggested</button>
            </div>

            <div class="xs2-results" data-panel="live-search" id="liveSearchResults" hidden></div>

            <div class="xs2-results" data-panel="recent">
                <button type="button" class="xs2-result-row" id="currentLocationBtn">
                    <span class="xs2-result-icon xs2-result-icon-current"><i class="fa-solid fa-location-crosshairs"></i></span>
                    <span class="xs2-result-text">
                        <strong>Current location</strong>
                        <small id="currentLocationLabel">Tap to use as your pickup point</small>
                    </span>
                    <i class="fa-solid fa-chevron-right xs2-result-chevron"></i>
                </button>
                @forelse($recentSearches as $item)
                    <button type="button" class="xs2-result-row" data-fill="{{ $item }}">
                        <span class="xs2-result-icon xs2-result-icon-recent"><i class="fa-regular fa-clock"></i></span>
                        <span class="xs2-result-text">
                            <strong>{{ $item }}</strong>
                            <small>Recent search</small>
                        </span>
                        <i class="fa-solid fa-chevron-right xs2-result-chevron"></i>
                    </button>
                @empty
                    <div class="xs2-empty-state">
                        <i class="fa-regular fa-clock"></i>
                        <p>No recent searches yet — try searching a destination above.</p>
                    </div>
                @endforelse
            </div>

            <div class="xs2-results" data-panel="suggested" hidden>
                @forelse($suggestedDestinations as $item)
                    <button type="button" class="xs2-result-row" data-fill="{{ $item }}">
                        <span class="xs2-result-icon xs2-result-icon-suggested"><i class="fa-solid fa-location-dot"></i></span>
                        <span class="xs2-result-text">
                            <strong>{{ $item }}</strong>
                            <small>Popular destination</small>
                        </span>
                        <i class="fa-solid fa-chevron-right xs2-result-chevron"></i>
                    </button>
                @empty
                    <div class="xs2-empty-state">
                        <i class="fa-solid fa-compass"></i>
                        <p>No suggested destinations yet — check back once more trips are posted.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── Floating button to pick a location on the map — JS keeps it
             pinned above the on-screen keyboard when one is open ────────── --}}
        <button type="button" class="xs2-fab" id="openMapPickerBtn">
            <i class="fa-solid fa-map-location-dot"></i> Choose on map
        </button>

        {{-- ── Full-screen map picker overlay ───────────────────────── --}}
        <div class="xs2-map-overlay" id="mapOverlay" hidden>
            {{-- Which field this sets (Pickup or Destination) is decided by
                 whichever field was active before the map opened — not a
                 manual toggle. Tapping this bar (like the back arrow) exits
                 back to the search page without picking a location. --}}
            <div class="xs2-map-overlay-top">
                <button type="button" class="xs2-icon-btn" id="closeMapPickerBtn" aria-label="Back to search">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <button type="button" class="xs2-map-search-bar" id="mapBackToSearchBtn">
                    <span class="xs2-field-dot xs2-field-dot-dest" id="mapSearchBarIcon"></span>
                    <span id="mapSearchBarLabel">Where to?</span>
                </button>
            </div>
            <div class="xs2-map-container">
                <div id="exploreSearchMap"></div>
                <div class="xs2-center-pin" id="centerPin"><i class="fa-solid fa-location-dot"></i></div>
                <button type="button" class="xs2-locate-btn" id="locateMeBtn" aria-label="Center on my location">
                    <i class="fa-solid fa-location-crosshairs"></i>
                </button>
            </div>
            <div class="xs2-map-sheet">
                <div class="xs2-map-sheet-handle"></div>
                <p class="xs2-map-sheet-label" id="searchMapStatus">Move the map to set your pin.</p>
                <div class="xs2-sheet-options" id="mapSheetOptions" hidden></div>
                <button type="button" class="xs2-map-confirm-btn" id="confirmPinBtn" disabled>Choose this location</button>
            </div>
        </div>

    </div>

    <script src="{{ asset('js/explore-search.js') }}?v={{ filemtime(public_path('js/explore-search.js')) }}"></script>
@endsection
