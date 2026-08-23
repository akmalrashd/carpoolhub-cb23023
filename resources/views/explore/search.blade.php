@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/explore-search.css') }}?v={{ filemtime(public_path('css/explore-search.css')) }}">
    @endpush

    <div class="xs-page">

        {{-- ── Search form card ──────────────────────────────────────────── --}}
        <section class="xs-card">
            <h1 class="xs-title">Search Trips</h1>
            <p class="xs-subtitle">Find public rides by destination, date, and seat preference.</p>

            <form method="GET" action="{{ route('explore.index') }}" style="display:contents;" id="exploreSearchForm">

                {{-- Primary destination --}}
                <div class="xs-dest-box">
                    <label class="xs-dest-label" for="search_destination">Where are you going?</label>
                    <div class="xs-auto-wrap">
                        <input
                            id="search_destination"
                            type="text"
                            name="destination"
                            class="xs-dest-input"
                            value="{{ old('destination', $prefill) }}"
                            placeholder="Search destination..."
                            autocomplete="off"
                            autofocus
                        >
                        <div class="xs-suggest-list" id="destinationSuggestList"></div>
                    </div>
                </div>

                {{-- Secondary fields --}}
                <div class="xs-grid">
                    <div class="xs-field">
                        <label class="xs-label" for="search_date">Date</label>
                        <input id="search_date" class="xs-input" type="date" name="date" value="{{ request('date') }}">
                    </div>
                    <div class="xs-field">
                        <label class="xs-label" for="search_pickup">Pickup area (optional)</label>
                        <div class="xs-auto-wrap">
                            <input id="search_pickup" class="xs-input" type="text" name="pickup" value="{{ request('pickup') }}" placeholder="Pickup area" autocomplete="off">
                            <div class="xs-suggest-list" id="pickupSuggestList"></div>
                        </div>
                    </div>
                    <div class="xs-field">
                        <label class="xs-label" for="search_seats">Seats needed</label>
                        <select id="search_seats" class="xs-input" name="seats">
                            <option value="">Any</option>
                            <option value="1" {{ request('seats') === '1' ? 'selected' : '' }}>1 seat</option>
                            <option value="2plus" {{ request('seats') === '2plus' ? 'selected' : '' }}>2+ seats</option>
                        </select>
                    </div>
                    <div class="xs-field">
                        <label class="xs-label" for="search_sort">Sort by</label>
                        <select id="search_sort" class="xs-input" name="sort">
                            <option value="nearest" {{ request('sort', 'nearest') === 'nearest' ? 'selected' : '' }}>Nearest date</option>
                            <option value="latest"  {{ request('sort') === 'latest' ? 'selected' : '' }}>Latest date</option>
                        </select>
                    </div>
                    <div class="xs-field">
                        <label class="xs-label" for="search_radius_km">Radius (km from pin)</label>
                        <input id="search_radius_km" class="xs-input" type="number" name="radius_km" min="0.5" step="0.5" value="{{ request('radius_km', 5) }}">
                    </div>
                </div>

                {{-- Hidden coordinate inputs --}}
                <input type="hidden" id="search_center_lat"      name="center_lat"      value="{{ request('center_lat') }}">
                <input type="hidden" id="search_center_lng"      name="center_lng"      value="{{ request('center_lng') }}">
                <input type="hidden" id="search_pickup_lat"      name="pickup_lat"      value="{{ request('pickup_lat') }}">
                <input type="hidden" id="search_pickup_lng"      name="pickup_lng"      value="{{ request('pickup_lng') }}">
                <input type="hidden" id="search_destination_lat" name="destination_lat" value="{{ request('destination_lat') }}">
                <input type="hidden" id="search_destination_lng" name="destination_lng" value="{{ request('destination_lng') }}">

                {{-- Map picker --}}
                <div class="xs-map-card">
                    <div class="xs-map-head">
                        <div>
                            <p class="xs-map-title"><i class="fa-solid fa-map-location-dot" style="margin-right:6px;color:var(--warning-ink);"></i>Pin Location on Map</p>
                            <p class="xs-map-hint">Select a target, then tap the map to drop a pin.</p>
                        </div>
                        <div class="xs-map-targets">
                            <button type="button" class="xs-map-target-btn active" id="targetDestinationBtn">
                                <i class="fa-solid fa-flag-checkered" style="margin-right:4px;"></i>Destination
                            </button>
                            <button type="button" class="xs-map-target-btn" id="targetPickupBtn">
                                <i class="fa-solid fa-location-dot" style="margin-right:4px;"></i>Pickup
                            </button>
                        </div>
                    </div>
                    <div class="xs-map-status" id="searchMapStatus">No destination pin set yet.</div>
                    <div id="exploreSearchMap"></div>
                </div>

                {{-- Submit / clear --}}
                <div class="xs-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-magnifying-glass"></i> Find Trips
                    </button>
                    <a href="{{ route('explore.search') }}" class="btn btn-ghost">
                        <i class="fa-solid fa-rotate-left"></i> Clear All
                    </a>
                    <a href="{{ route('explore.index') }}" class="btn btn-ghost">
                        <i class="fa-solid fa-compass"></i> Browse All
                    </a>
                </div>

            </form>
        </section>

        {{-- ── Recent searches ───────────────────────────────────────────── --}}
        <section class="xs-card">
            <h2 class="xs-section-title">Recent Searches</h2>
            @if($recentSearches->isEmpty())
                <p class="xs-empty">No recent searches yet.</p>
            @else
                <div class="xs-tag-list">
                    @foreach($recentSearches as $item)
                        <a href="{{ route('explore.index', ['destination' => $item]) }}" class="xs-tag">{{ $item }}</a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ── Suggested destinations ─────────────────────────────────────── --}}
        <section class="xs-card">
            <h2 class="xs-section-title">Suggested Destinations</h2>
            @if($suggestedDestinations->isEmpty())
                <p class="xs-empty">No suggested destinations available yet.</p>
            @else
                <div class="xs-suggested-list">
                    @foreach($suggestedDestinations as $item)
                        <a href="{{ route('explore.index', ['destination' => $item]) }}" class="xs-suggested-item">
                            <i class="fa-solid fa-location-dot" style="margin-right:6px;color:var(--warning-ink);"></i>{{ $item }}
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

    </div>

    <script src="{{ asset('js/explore-search.js') }}?v={{ filemtime(public_path('js/explore-search.js')) }}"></script>
@endsection
