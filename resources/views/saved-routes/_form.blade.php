@php
    $selectableParticipants = $selectableParticipants ?? collect();
    $storedPresetStops = isset($savedRoute)
        ? $savedRoute->passengerStops->map(fn ($stop) => [
            'user_id' => $stop->user_id,
            'pickup_name' => $stop->pickup_name,
            'pickup_latitude' => $stop->pickup_latitude,
            'pickup_longitude' => $stop->pickup_longitude,
            'dropoff_name' => $stop->dropoff_name,
            'dropoff_latitude' => $stop->dropoff_latitude,
            'dropoff_longitude' => $stop->dropoff_longitude,
            'extra_fee_amount' => $stop->extra_fee_amount,
            'note' => $stop->note,
            'is_active' => $stop->is_active,
        ])->values()->all()
        : [];
    $presetStops = collect(old('passenger_stops', $storedPresetStops))->values();
@endphp

{{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
<link rel="stylesheet" href="{{ asset('css/saved-routes-form.css') }}?v={{ filemtime(public_path('css/saved-routes-form.css')) }}">

<div class="rf-shell">

    {{-- LEFT: Full map card --}}
    <div class="card rf-map-card">

        {{-- Map toolbar --}}
        <div class="rf-map-toolbar">
            <div class="rf-map-toolbar-right">
                <span class="rf-map-stat" id="mapStatDistance"><span>Distance</span> —</span>
                <span class="rf-map-stat" id="mapStatTime"><span>ETA</span> —</span>
                <span class="rf-map-stat" id="mapStatFare"><span>Fare</span> —</span>
            </div>
        </div>

        {{-- Inner: search + controls + map --}}
        <div class="rf-map-inner">
            <div class="rf-map-section-head">
                <span class="rf-map-section-title">
                    <i class="fa-solid fa-map-location-dot"></i>
                    Point A and Point B
                </span>
                <p class="rf-map-section-hint">Set your saved route's Point A and Point B.</p>
            </div>

            <div class="rf-target-switch" aria-label="Route point target">
                <button type="button" class="rf-target-btn active" data-map-target="pickup">
                    <i class="fa-solid fa-location-dot"></i>Point A
                </button>
                <button type="button" class="rf-target-btn" data-map-target="destination">
                    <i class="fa-solid fa-flag-checkered"></i>Point B
                </button>
            </div>

            <div class="rf-preview-card" id="mapSearchPreview">
                <div class="rf-preview-top">
                    <div>
                        <div class="rf-preview-title" id="mapSearchPreviewTitle">Location Preview</div>
                        <div class="rf-preview-sub" id="mapSearchPreviewSub">Move the map and confirm the correct point.</div>
                    </div>
                    <span class="rf-preview-badge">Preview only</span>
                </div>
                <div class="rf-preview-actions">
                    <button type="button" class="rf-preview-use-btn primary" id="mapPreviewUseBtn">Use as selected point</button>
                    <button type="button" class="rf-preview-close-btn" id="mapPreviewCloseBtn">Discard preview</button>
                </div>
            </div>

            <div class="rf-map-help-row">
                <p class="rf-map-help">Search or tap the map, then confirm the point.</p>
                <button type="button" class="rf-reset-btn" id="mapResetBtn">
                    <i class="fa-solid fa-rotate-left"></i><span>Reset Route</span>
                </button>
            </div>

            <div class="rf-map-status" hidden>
                <div class="rf-step-title">
                    <span class="rf-step-badge" id="mapStepNumber"></span>
                </div>
                <div class="rf-step-text" id="mapStepText"></div>
                <div class="rf-step-hint" id="mapStepHint"></div>
            </div>

            <div class="rf-custom-stop-top">
                <div>
                    <p class="rf-custom-stop-title"><i class="fa-solid fa-users-line"></i>Custom passenger stops</p>
                    <p class="rf-custom-stop-sub">Add only for passengers with fixed pickup or drop-off points.</p>
                </div>
                <button type="button" class="rf-custom-stop-toggle {{ $presetStops->isNotEmpty() ? 'secondary' : '' }}" id="togglePresetStopsBtn">
                    <i class="fa-solid {{ $presetStops->isNotEmpty() ? 'fa-eye-slash' : 'fa-plus' }}"></i>
                    <span>{{ $presetStops->isNotEmpty() ? 'Hide custom stops' : 'Add custom stop' }}</span>
                </button>
            </div>

            <div class="rf-custom-stop-panel {{ $presetStops->isNotEmpty() ? 'show' : '' }}" id="presetStopPanel">
                <div class="rf-preset-head">
                    <div>
                        <h3 class="rf-ctrl-head" style="margin-bottom:0"><i class="fa-solid fa-user-plus"></i> Fixed passenger stops</h3>
                        <p class="rf-preset-hint">Select a passenger and set their fixed stops.</p>
                    </div>
                    <button type="button" class="rf-preset-add" id="addPresetStopBtn" {{ $selectableParticipants->isEmpty() ? 'disabled' : '' }}>
                        <i class="fa-solid fa-plus"></i>Add
                    </button>
                </div>

                <div class="rf-preset-list" id="presetStopList">
                    @foreach($presetStops as $index => $presetStop)
                        <div class="rf-preset-row" data-preset-stop-row>
                            <div class="rf-preset-row-head">
                                <select class="rf-preset-select" name="passenger_stops[{{ $index }}][user_id]">
                                    <option value="">Select passenger</option>
                                    @foreach($selectableParticipants as $participant)
                                        <option value="{{ $participant->id }}" {{ (string) ($presetStop['user_id'] ?? '') === (string) $participant->id ? 'selected' : '' }}>
                                            {{ $participant->name }} · {{ $participant->email }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="rf-preset-remove" data-remove-preset-stop aria-label="Remove preset passenger">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <div class="rf-preset-grid">
                                <input class="rf-hidden" type="text" name="passenger_stops[{{ $index }}][pickup_name]" value="{{ $presetStop['pickup_name'] ?? '' }}" placeholder="Passenger pickup / boarding point">
                                <input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[{{ $index }}][pickup_latitude]" value="{{ $presetStop['pickup_latitude'] ?? '' }}">
                                <input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[{{ $index }}][pickup_longitude]" value="{{ $presetStop['pickup_longitude'] ?? '' }}">
                                <input class="rf-hidden" type="text" name="passenger_stops[{{ $index }}][dropoff_name]" value="{{ $presetStop['dropoff_name'] ?? '' }}" placeholder="Passenger drop-off point">
                                <input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[{{ $index }}][dropoff_latitude]" value="{{ $presetStop['dropoff_latitude'] ?? '' }}">
                                <input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[{{ $index }}][dropoff_longitude]" value="{{ $presetStop['dropoff_longitude'] ?? '' }}">
                                <div class="rf-preset-compact">
                                    <span class="rf-preset-distance" data-preset-distance></span>
                                    <label class="rf-preset-fare"><span>Extra fee</span>
                                        <input type="number" step="0.01" min="0" name="passenger_stops[{{ $index }}][extra_fee_amount]" value="{{ $presetStop['extra_fee_amount'] ?? '' }}" placeholder="Auto" data-auto-fare="{{ ($presetStop['extra_fee_amount'] ?? '') === '' ? '1' : '0' }}">
                                    </label>
                                </div>
                                <input class="rf-hidden" type="text" name="passenger_stops[{{ $index }}][note]" value="{{ $presetStop['note'] ?? '' }}" placeholder="Optional note">
                                <input type="hidden" name="passenger_stops[{{ $index }}][is_active]" value="1">
                            </div>
                            <div class="rf-preset-actions">
                                <button type="button" class="rf-preset-mini-btn" data-fill-preset="ab" hidden>
                                    <i class="fa-solid fa-route"></i>Use A to B
                                </button>
                                <button type="button" class="rf-preset-mini-btn" data-fill-preset="ba" hidden>
                                    <i class="fa-solid fa-right-left"></i>Use B to A
                                </button>
                                <button type="button" class="rf-preset-mini-btn" data-capture-preset="pickup">
                                    <i class="fa-solid fa-location-dot"></i>Passenger pickup
                                </button>
                                <button type="button" class="rf-preset-mini-btn" data-capture-preset="dropoff">
                                    <i class="fa-solid fa-flag-checkered"></i>Passenger drop-off
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="rf-preset-empty" id="presetStopEmpty" {{ $presetStops->isNotEmpty() ? 'hidden' : '' }}>
                    No preset passenger stops yet.
                </p>
            </div>

            <div class="rf-route-recommendation" id="routeRecommendation" hidden></div>
            <div class="rf-route-options" id="routeOptions">
                <div class="rf-route-empty">Set both points to view route options.</div>
            </div>
        </div>

        {{-- Leaflet map — full width at bottom of card --}}
        <div class="rf-map-map-shell">
            <div class="rf-map-search-row">
                <div class="rf-map-search-wrap">
                    <input type="text" id="mapSearchInput" placeholder="Search address, place, faculty, or neighbourhood in Malaysia..." autocomplete="off">
                    <div class="rf-map-search-suggest" id="mapSearchSuggest"></div>
                </div>
                <button type="button" id="mapSearchBtn" class="rf-map-btn">Search</button>
                <button type="button" id="mapLocateBtn" class="rf-map-btn" title="Use current location" aria-label="Use current location">
                    <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                </button>
            </div>
            <div id="routeMap"></div>
            <div class="rf-map-loading-overlay" id="routeMapLoading" hidden>
                <div class="rf-map-loading-spinner"></div>
                <span id="routeMapLoadingText">Finding best routes…</span>
            </div>
        </div>

    </div>{{-- /.rf-map-card --}}

    {{-- RIGHT: Controls panel --}}
    <div class="rf-controls">

        {{-- Route name card --}}
        <div class="card rf-ctrl-card">
            <h3 class="rf-ctrl-head"><i class="fa-solid fa-tag"></i> Route name</h3>
            <input
                id="route_name"
                class="input"
                type="text"
                name="route_name"
                value="{{ old('route_name', $savedRoute->route_name ?? '') }}"
                placeholder="e.g. Home to Office"
                autocomplete="off"
                style="width:100%;box-sizing:border-box"
            >
        </div>

        {{-- Stops card --}}
        <div class="card rf-ctrl-card">
            <h3 class="rf-ctrl-head"><i class="fa-solid fa-map-pin"></i> Route points</h3>
            <div class="rf-stop-list">

                {{-- Point A --}}
                <div class="rf-stop-row">
                    <span class="rf-stop-dot pickup"></span>
                    <div class="rf-stop-meta">
                        <span class="rf-stop-eyebrow">Point A</span>
                        <input
                            id="point_a_name"
                            class="rf-stop-input"
                            type="text"
                            name="point_a_name"
                            value="{{ old('point_a_name', $savedRoute->point_a_name ?? '') }}"
                            placeholder="Name auto-filled"
                            readonly
                            required
                        >
                        <div class="rf-stop-coord-grid">
                            <label class="rf-stop-coord-field" for="point_a_latitude">
                                <span class="rf-stop-coord-label">Lat</span>
                                <input id="point_a_latitude" class="rf-stop-input" type="number" step="0.0000001" min="-90" max="90" name="point_a_latitude" value="{{ old('point_a_latitude',  $savedRoute->point_a_latitude  ?? '') }}" placeholder="3.5461234" required>
                            </label>
                            <label class="rf-stop-coord-field" for="point_a_longitude">
                                <span class="rf-stop-coord-label">Lng</span>
                                <input id="point_a_longitude" class="rf-stop-input" type="number" step="0.0000001" min="-180" max="180" name="point_a_longitude" value="{{ old('point_a_longitude', $savedRoute->point_a_longitude ?? '') }}" placeholder="103.4321234" required>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Point B --}}
                <div class="rf-stop-row">
                    <span class="rf-stop-dot dest"></span>
                    <div class="rf-stop-meta">
                        <span class="rf-stop-eyebrow">Point B</span>
                        <input
                            id="point_b_name"
                            class="rf-stop-input"
                            type="text"
                            name="point_b_name"
                            value="{{ old('point_b_name', $savedRoute->point_b_name ?? '') }}"
                            placeholder="Name auto-filled"
                            readonly
                            required
                        >
                        <div class="rf-stop-coord-grid">
                            <label class="rf-stop-coord-field" for="point_b_latitude">
                                <span class="rf-stop-coord-label">Lat</span>
                                <input id="point_b_latitude" class="rf-stop-input" type="number" step="0.0000001" min="-90" max="90" name="point_b_latitude" value="{{ old('point_b_latitude',  $savedRoute->point_b_latitude  ?? '') }}" placeholder="3.5461234" required>
                            </label>
                            <label class="rf-stop-coord-field" for="point_b_longitude">
                                <span class="rf-stop-coord-label">Lng</span>
                                <input id="point_b_longitude" class="rf-stop-input" type="number" step="0.0000001" min="-180" max="180" name="point_b_longitude" value="{{ old('point_b_longitude', $savedRoute->point_b_longitude ?? '') }}" placeholder="103.4321234" required>
                            </label>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @if(false)
        {{-- Preset passenger stops are managed inside the map card. --}}
        <div class="card rf-ctrl-card rf-preset-card" hidden>
            <div class="rf-preset-head">
                <div>
                    <h3 class="rf-ctrl-head" style="margin-bottom:0"><i class="fa-solid fa-user-plus"></i> Preset passenger stops</h3>
                    <p class="rf-preset-hint">Optional. Pick accepted connections that should be auto-added when this saved route is used.</p>
                </div>
                <button type="button" class="rf-preset-add" id="addPresetStopBtn" {{ $selectableParticipants->isEmpty() ? 'disabled' : '' }}>
                    <i class="fa-solid fa-plus"></i>Add
                </button>
            </div>

            <div class="rf-preset-list" id="presetStopList">
                @foreach($presetStops as $index => $presetStop)
                    <div class="rf-preset-row" data-preset-stop-row>
                        <div class="rf-preset-row-head">
                            <select class="rf-preset-select" name="passenger_stops[{{ $index }}][user_id]">
                                <option value="">Select passenger</option>
                                @foreach($selectableParticipants as $participant)
                                    <option value="{{ $participant->id }}" {{ (string) ($presetStop['user_id'] ?? '') === (string) $participant->id ? 'selected' : '' }}>
                                        {{ $participant->name }} · {{ $participant->email }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="rf-preset-remove" data-remove-preset-stop aria-label="Remove preset passenger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                        <div class="rf-preset-grid">
                            <input class="rf-preset-input" type="text" name="passenger_stops[{{ $index }}][pickup_name]" value="{{ $presetStop['pickup_name'] ?? '' }}" placeholder="Passenger pickup / boarding point">
                            <input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[{{ $index }}][pickup_latitude]" value="{{ $presetStop['pickup_latitude'] ?? '' }}">
                            <input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[{{ $index }}][pickup_longitude]" value="{{ $presetStop['pickup_longitude'] ?? '' }}">
                            <input class="rf-preset-input" type="text" name="passenger_stops[{{ $index }}][dropoff_name]" value="{{ $presetStop['dropoff_name'] ?? '' }}" placeholder="Passenger drop-off point">
                            <input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[{{ $index }}][dropoff_latitude]" value="{{ $presetStop['dropoff_latitude'] ?? '' }}">
                            <input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[{{ $index }}][dropoff_longitude]" value="{{ $presetStop['dropoff_longitude'] ?? '' }}">
                            <input class="rf-preset-input" type="number" step="0.01" min="0" name="passenger_stops[{{ $index }}][extra_fee_amount]" value="{{ $presetStop['extra_fee_amount'] ?? '' }}" placeholder="Optional extra fee for this passenger">
                            <input class="rf-preset-input" type="text" name="passenger_stops[{{ $index }}][note]" value="{{ $presetStop['note'] ?? '' }}" placeholder="Optional note">
                            <input type="hidden" name="passenger_stops[{{ $index }}][is_active]" value="1">
                        </div>
                        <div class="rf-preset-actions">
                            <button type="button" class="rf-preset-mini-btn" data-fill-preset="ab">
                                <i class="fa-solid fa-route"></i>Use A to B
                            </button>
                            <button type="button" class="rf-preset-mini-btn" data-fill-preset="ba">
                                <i class="fa-solid fa-right-left"></i>Use B to A
                            </button>
                            <button type="button" class="rf-preset-mini-btn" data-capture-preset="pickup">
                                <i class="fa-solid fa-location-dot"></i>Set pickup on map
                            </button>
                            <button type="button" class="rf-preset-mini-btn" data-capture-preset="dropoff">
                                <i class="fa-solid fa-flag-checkered"></i>Set drop-off on map
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="rf-preset-empty" id="presetStopEmpty" {{ $presetStops->isNotEmpty() ? 'hidden' : '' }}>
                No custom stops yet.
            </p>
        </div>
        @endif

        {{-- Fare card --}}
        <div class="card rf-ctrl-card">
            <h3 class="rf-ctrl-head"><i class="fa-solid fa-coins"></i> Fare</h3>
            <div class="rf-fare-grid">
                <div class="rf-fare-field">
                    <label class="rf-fare-label" for="default_fare">Default fare</label>
                    <input
                        id="default_fare"
                        class="rf-fare-input"
                        type="number"
                        step="0.01"
                        min="0"
                        name="default_fare"
                        value="{{ old('default_fare', $savedRoute->default_fare ?? '0.00') }}"
                        placeholder="0.00"
                        required
                    >
                    <p class="field-hint" style="margin:0">Default fare for this route. You can change it anytime.</p>
                </div>
            </div>
            <div class="rf-fare-advisor">
                <div class="rf-fare-advisor-head">
                    <div>
                        <p class="rf-fare-advisor-title"><x-mascot size="16" variant="yellow" state="idle" />AI cost-based fare</p>
                        <p class="rf-fare-advisor-sub">Fuel efficiency and toll are estimated from vehicle and selected route. You can edit every value.</p>
                    </div>
                    <span class="rf-fare-advisor-badge" id="fareAdvisorStatus">Waiting route</span>
                </div>

                <div class="rf-fare-advisor-grid">
                    <div class="rf-fare-advisor-field">
                        <label for="fareFuelType">Fuel type</label>
                        <select id="fareFuelType">
                            <option value="RON95">RON95</option>
                            <option value="RON97">RON97</option>
                            <option value="Diesel">Diesel</option>
                        </select>
                    </div>
                    <div class="rf-fare-advisor-field">
                        <label for="fareFuelPrice">Fuel price / L</label>
                        <input id="fareFuelPrice" type="number" step="0.01" min="0" value="1.99">
                        <span class="rf-fuel-price-asof" id="fuelPriceAsOf" hidden></span>
                    </div>
                    <label class="rf-fare-toggle wide">
                        <input id="fareUseBudi" type="checkbox" checked>
                        <span>Use BUDI95 subsidised RON95 price</span>
                    </label>
                    <div class="rf-fare-advisor-field">
                        <label for="fareKmPerLiter">AI km / L</label>
                        <input id="fareKmPerLiter" type="number" step="0.1" min="0" value="11.5">
                    </div>
                    <div class="rf-fare-advisor-field">
                        <label for="fareTollCost">Toll cost</label>
                        <input id="fareTollCost" type="number" step="0.01" min="0" value="0.00">
                    </div>
                    <div class="rf-fare-advisor-field">
                        <label for="fareBufferRate">Buffer %</label>
                        <input id="fareBufferRate" type="number" step="1" min="0" max="60" value="15">
                    </div>
                    <div class="rf-fare-advisor-field">
                        <label for="fareSplitCount">Split count</label>
                        <input id="fareSplitCount" type="number" step="1" min="1" value="1">
                    </div>
                </div>

                <div class="rf-fare-breakdown">
                    <div class="rf-fare-breakdown-row"><span>Fuel used</span><strong id="fareFuelUsed">-</strong></div>
                    <div class="rf-fare-breakdown-row"><span>Fuel cost</span><strong id="fareFuelCost">-</strong></div>
                    <div class="rf-fare-breakdown-row"><span>Toll + buffer</span><strong id="fareTollBuffer">-</strong></div>
                    <div class="rf-fare-breakdown-row"><span>Suggested total</span><strong id="fareSuggestedTotal">-</strong></div>
                    <div class="rf-fare-breakdown-row"><span>Per split</span><strong id="fareSuggestedPerPerson">-</strong></div>
                </div>
                <div class="rf-fare-ai-reason" id="fareAiReason">Set both points to get AI fuel and toll advice.</div>
            </div>
        </div>

        {{-- Settings card --}}
        <div class="card rf-ctrl-card">
            <h3 class="rf-ctrl-head"><i class="fa-solid fa-sliders"></i> Route settings</h3>
            <div class="rf-toggle-group">
                <label class="rf-toggle-row">
                    <div class="rf-toggle-text">
                        <span class="rf-toggle-title">Active route</span>
                        <span class="rf-toggle-hint">Available to pick when starting a new trip. Turn off to keep it saved without using it right now.</span>
                    </div>
                    <span class="rf-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $savedRoute->is_active ?? true) ? 'checked' : '' }}>
                        <span class="rf-switch-track"><span class="rf-switch-thumb"></span></span>
                    </span>
                </label>
                <label class="rf-toggle-row">
                    <div class="rf-toggle-text">
                        <span class="rf-toggle-title">Make default route</span>
                        <span class="rf-toggle-hint">Pre-selected first when you start creating a new trip.</span>
                    </div>
                    <span class="rf-switch">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default', $savedRoute->is_default ?? false) ? 'checked' : '' }}>
                        <span class="rf-switch-track"><span class="rf-switch-thumb"></span></span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Validation errors --}}
        @if($errors->any())
            <div class="rf-error-banner">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ $errors->first() }}
            </div>
        @endif

    </div>{{-- /.rf-controls --}}

</div>{{-- /.rf-shell --}}

<script>
    (function () {
        if (typeof L === 'undefined') {
            return;
        }

        var malaysiaCenter = [3.139, 101.6869];
        var map = L.map('routeMap').setView(malaysiaCenter, 12);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // The map card is stretched to match the (usually taller) controls
        // column via CSS, and #routeMap fills that with flex — but Leaflet
        // measures its container once at init, before webfonts/icons finish
        // loading and settle that final height, leaving unrendered tiles
        // below the fold until something forces a remeasure.
        setTimeout(function () { map.invalidateSize(); }, 0);
        window.addEventListener('load', function () { map.invalidateSize(); });
        if (window.ResizeObserver) {
            new ResizeObserver(function () { map.invalidateSize(); }).observe(document.getElementById('routeMap'));
        } else {
            window.addEventListener('resize', function () { map.invalidateSize(); });
        }

        var searchInput = document.getElementById('mapSearchInput');
        var searchBtn = document.getElementById('mapSearchBtn');
        var searchSuggest = document.getElementById('mapSearchSuggest');
        var locateBtn = document.getElementById('mapLocateBtn');
        var previewCard = document.getElementById('mapSearchPreview');
        var previewTitle = document.getElementById('mapSearchPreviewTitle');
        var previewSub = document.getElementById('mapSearchPreviewSub');
        var previewUseBtn = document.getElementById('mapPreviewUseBtn');
        var previewCloseBtn = document.getElementById('mapPreviewCloseBtn');
        var routeOptionsEl = document.getElementById('routeOptions');
        var routeRecommendationEl = document.getElementById('routeRecommendation');
        var routeMapLoadingEl = document.getElementById('routeMapLoading');
        var routeMapLoadingTextEl = document.getElementById('routeMapLoadingText');

        function showMapLoading(text) {
            if (!routeMapLoadingEl) return;
            if (routeMapLoadingTextEl) routeMapLoadingTextEl.textContent = text;
            routeMapLoadingEl.hidden = false;
        }

        function hideMapLoading() {
            if (routeMapLoadingEl) routeMapLoadingEl.hidden = true;
        }
        var mapStepNumber = document.getElementById('mapStepNumber');
        var mapStepText = document.getElementById('mapStepText');
        var mapStepHint = document.getElementById('mapStepHint');
        var mapResetBtn = document.getElementById('mapResetBtn');

        var pickupName = document.getElementById('point_a_name');
        var pickupLat = document.getElementById('point_a_latitude');
        var pickupLng = document.getElementById('point_a_longitude');
        var destinationName = document.getElementById('point_b_name');
        var destinationLat = document.getElementById('point_b_latitude');
        var destinationLng = document.getElementById('point_b_longitude');
        var defaultFareInput = document.getElementById('default_fare');
        var presetStopList = document.getElementById('presetStopList');
        var presetStopEmpty = document.getElementById('presetStopEmpty');
        var addPresetStopBtn = document.getElementById('addPresetStopBtn');
        var presetStopPanel = document.getElementById('presetStopPanel');
        var togglePresetStopsBtn = document.getElementById('togglePresetStopsBtn');
        var statDistance = document.getElementById('mapStatDistance');
        var statTime = document.getElementById('mapStatTime');
        var statFare = document.getElementById('mapStatFare');
        var fareAdvisorStatus = document.getElementById('fareAdvisorStatus');
        var fareFuelType = document.getElementById('fareFuelType');
        var fareUseBudi = document.getElementById('fareUseBudi');
        var fareFuelPrice = document.getElementById('fareFuelPrice');
        var fuelPriceAsOfEl = document.getElementById('fuelPriceAsOf');
        var fareKmPerLiter = document.getElementById('fareKmPerLiter');
        var fareTollCost = document.getElementById('fareTollCost');
        var fareBufferRate = document.getElementById('fareBufferRate');
        var fareSplitCount = document.getElementById('fareSplitCount');
        var fareFuelUsed = document.getElementById('fareFuelUsed');
        var fareFuelCost = document.getElementById('fareFuelCost');
        var fareTollBuffer = document.getElementById('fareTollBuffer');
        var fareSuggestedTotal = document.getElementById('fareSuggestedTotal');
        var fareSuggestedPerPerson = document.getElementById('fareSuggestedPerPerson');
        var fareAiReason = document.getElementById('fareAiReason');
        var passengerOptionsHtml = @json($selectableParticipants->map(fn ($participant) => [
            'id' => $participant->id,
            'label' => $participant->name.' · '.$participant->email,
        ])->values());
        var presetStopIndex = presetStopList ? presetStopList.querySelectorAll('[data-preset-stop-row]').length : 0;

        var nextTarget = 'pickup';
        var pickupMarker = null;
        var destinationMarker = null;
        var routeLayers = [];
        var selectedRouteIndex = 0;
        var fetchedRoutes = [];
        var fareAdviceByRoute = {};
        var previewMarker = null;
        var previewPlace = null;
        var fareEditedByUser = defaultFareInput && parseFloat(defaultFareInput.value || '0') > 0;
        var fareAdvisorEdited = false;
        var presetCapture = null;
        var activePresetRow = null;
        // Same two-part rule the Explore join-request map uses: a custom stop is
        // allowed if it's close to the fetched route path itself (not just a
        // circle around Point A/B), or still within a wider circle around the
        // anchor point. Both radii scale with the route's total length.
        var allowedRouteRadiusKm = 0.20;
        var allowedEndpointRadiusKm = 0.50;
        var presetMarkerLayer = L.layerGroup().addTo(map);
        var presetRadiusLayers = [];

        function toNumber(value) {
            var num = parseFloat(value);
            return Number.isFinite(num) ? num : null;
        }

        function escapeText(value) {
            return String(value || '').replace(/[&<>"']/g, function (char) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char] || char;
            });
        }

        function showMapToast(message) {
            // Match the app's one standard toast (top toast-container/toast-card,
            // used on trips/payments/notifications) instead of this page's own
            // bottom pill, so a blocked map point looks consistent everywhere else.
            if (window.showToast) window.showToast(message, 'error');
        }

        function updatePresetStopEmpty() {
            if (!presetStopList || !presetStopEmpty) return;
            presetStopEmpty.hidden = presetStopList.querySelectorAll('[data-preset-stop-row]').length > 0;
            syncPresetStopMarkers();
        }

        function setCustomStopPanel(open) {
            if (!presetStopPanel || !togglePresetStopsBtn) return;
            presetStopPanel.classList.toggle('show', open);
            togglePresetStopsBtn.classList.toggle('secondary', open);
            togglePresetStopsBtn.innerHTML = open
                ? '<i class="fa-solid fa-eye-slash"></i><span>Hide custom stops</span>'
                : '<i class="fa-solid fa-plus"></i><span>Add custom stop</span>';
        }

        function passengerOptions(selectedValue) {
            var html = '<option value="">Select passenger</option>';
            passengerOptionsHtml.forEach(function (participant) {
                var selected = String(selectedValue || '') === String(participant.id) ? ' selected' : '';
                html += '<option value="' + escapeText(participant.id) + '"' + selected + '>' + escapeText(participant.label) + '</option>';
            });
            return html;
        }

        function presetInput(row, namePart) {
            return row.querySelector('[name$="[' + namePart + ']"]');
        }

        function latLngFromInputs(latInput, lngInput) {
            var lat = latInput ? toNumber(latInput.value) : null;
            var lng = lngInput ? toNumber(lngInput.value) : null;
            return lat !== null && lng !== null ? L.latLng(lat, lng) : null;
        }

        function distanceKmBetween(a, b) {
            if (!a || !b) return null;
            return a.distanceTo(b) / 1000;
        }

        function formatRadiusDistance(km) {
            if (km === null) return '';
            return km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1) + ' km';
        }

        // Local equirectangular projection + point-to-segment distance, same
        // formula the Explore join-request map uses to measure how far a
        // candidate point sits from the actual route path (not just its ends).
        function pointToLocalKm(latLng, origin) {
            return {
                x: (latLng.lng - origin.lng) * 111.32 * Math.cos((origin.lat * Math.PI) / 180),
                y: (latLng.lat - origin.lat) * 110.57
            };
        }

        function distanceToSegmentKm(point, start, end) {
            var p = pointToLocalKm(point, start);
            var b = pointToLocalKm(end, start);
            var lengthSquared = (b.x * b.x) + (b.y * b.y);
            if (lengthSquared === 0) return Math.sqrt((p.x * p.x) + (p.y * p.y));
            var t = Math.max(0, Math.min(1, ((p.x * b.x) + (p.y * b.y)) / lengthSquared));
            return Math.sqrt(Math.pow(p.x - (t * b.x), 2) + Math.pow(p.y - (t * b.y), 2));
        }

        function currentRouteLinePoints() {
            var selectedRoute = fetchedRoutes[selectedRouteIndex] || fetchedRoutes[0] || null;
            if (selectedRoute && selectedRoute.geometry && Array.isArray(selectedRoute.geometry.coordinates)) {
                return selectedRoute.geometry.coordinates.map(function (pair) { return L.latLng(pair[1], pair[0]); });
            }
            if (pickupMarker && destinationMarker) {
                return [pickupMarker.getLatLng(), destinationMarker.getLatLng()];
            }
            return [];
        }

        function distanceToRouteKm(latLng) {
            var points = currentRouteLinePoints();
            if (points.length < 2) return null;
            var nearest = Infinity;
            for (var i = 0; i < points.length - 1; i += 1) {
                nearest = Math.min(nearest, distanceToSegmentKm(latLng, points[i], points[i + 1]));
            }
            return Number.isFinite(nearest) ? nearest : null;
        }

        function updateAllowedPresetRadius() {
            var selectedRoute = fetchedRoutes[selectedRouteIndex] || fetchedRoutes[0] || null;
            var fallbackKm = pickupMarker && destinationMarker
                ? distanceKmBetween(pickupMarker.getLatLng(), destinationMarker.getLatLng())
                : null;
            var routeKm = (selectedRoute && Number.isFinite(Number(selectedRoute.distance)) && Number(selectedRoute.distance) > 0)
                ? Number(selectedRoute.distance) / 1000
                : (fallbackKm || 1);

            if (routeKm <= 3) {
                allowedRouteRadiusKm = 0.40; allowedEndpointRadiusKm = 0.50;
            } else if (routeKm <= 10) {
                allowedRouteRadiusKm = 0.70; allowedEndpointRadiusKm = 0.80;
            } else if (routeKm <= 25) {
                allowedRouteRadiusKm = 1.00; allowedEndpointRadiusKm = 1.20;
            } else {
                allowedRouteRadiusKm = 1.30; allowedEndpointRadiusKm = 1.50;
            }
        }

        function customPinIcon(kind, sequence) {
            var color = kind === 'dropoff' ? '#ea580c' : '#16a34a';
            return L.divIcon({
                className: '',
                html: '<div style="width:24px;height:24px;border-radius:999px;background:' + color + ';border:3px solid #fff;box-shadow:0 6px 12px rgba(15,23,42,.25);color:#fff;font-size:11px;font-weight:900;display:flex;align-items:center;justify-content:center">' + sequence + '</div>',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
        }

        function customPinLabel(kind, sequence) {
            return (kind === 'dropoff' ? 'Drop-off ' : 'Pickup ') + sequence;
        }

        function syncPresetRadius() {
            updateAllowedPresetRadius();
            presetRadiusLayers.forEach(function (layer) { map.removeLayer(layer); });
            presetRadiusLayers = [];
            if (pickupMarker) {
                presetRadiusLayers.push(L.circle(pickupMarker.getLatLng(), {
                    radius: allowedEndpointRadiusKm * 1000,
                    color: '#16a34a',
                    weight: 1,
                    fillColor: '#16a34a',
                    fillOpacity: .06,
                    dashArray: '4 5'
                }).addTo(map));
            }
            if (destinationMarker) {
                presetRadiusLayers.push(L.circle(destinationMarker.getLatLng(), {
                    radius: allowedEndpointRadiusKm * 1000,
                    color: '#ea580c',
                    weight: 1,
                    fillColor: '#ea580c',
                    fillOpacity: .06,
                    dashArray: '4 5'
                }).addTo(map));
            }
        }

        function presetRows() {
            return presetStopList ? Array.prototype.slice.call(presetStopList.querySelectorAll('[data-preset-stop-row]')) : [];
        }

        function setActivePresetRow(row) {
            activePresetRow = row || presetRows()[0] || null;
            presetRows().forEach(function (item) {
                item.classList.toggle('is-active-capture', item === activePresetRow);
            });
        }

        function syncPresetCaptureButtons() {
            presetRows().forEach(function (row) {
                row.querySelectorAll('[data-capture-preset]').forEach(function (button) {
                    var isActive = presetCapture
                        && presetCapture.row === row
                        && button.getAttribute('data-capture-preset') === presetCapture.kind;
                    button.classList.toggle('active', Boolean(isActive));
                });
            });
        }

        function clearPresetCapture() {
            presetCapture = null;
            syncTargetButtons();
            syncPresetCaptureButtons();
        }

        function syncPresetStopMarkers() {
            if (!presetMarkerLayer) return;
            presetMarkerLayer.clearLayers();
            presetRows().forEach(function (row, index) {
                var pickupPoint = latLngFromInputs(presetInput(row, 'pickup_latitude'), presetInput(row, 'pickup_longitude'));
                var dropoffPoint = latLngFromInputs(presetInput(row, 'dropoff_latitude'), presetInput(row, 'dropoff_longitude'));
                var distanceText = row.querySelector('[data-preset-distance]');
                var pickupDistance = distanceKmBetween(pickupPoint, pickupMarker ? pickupMarker.getLatLng() : null);
                var dropoffDistance = distanceKmBetween(dropoffPoint, destinationMarker ? destinationMarker.getLatLng() : null);

                if (pickupPoint) {
                    L.marker(pickupPoint, { icon: customPinIcon('pickup', index + 1) })
                        .bindTooltip(customPinLabel('pickup', index + 1), { permanent: true, direction: 'top', offset: [0, -10], className: 'map-point-tooltip' })
                        .addTo(presetMarkerLayer);
                }
                if (dropoffPoint) {
                    L.marker(dropoffPoint, { icon: customPinIcon('dropoff', index + 1) })
                        .bindTooltip(customPinLabel('dropoff', index + 1), { permanent: true, direction: 'top', offset: [0, -10], className: 'map-point-tooltip' })
                        .addTo(presetMarkerLayer);
                }
                if (distanceText) {
                    var parts = [];
                    if (pickupDistance !== null) parts.push('Pickup ' + formatRadiusDistance(pickupDistance) + ' from A');
                    if (dropoffDistance !== null) parts.push('Drop-off ' + formatRadiusDistance(dropoffDistance) + ' from B');
                    distanceText.textContent = parts.join(' · ');
                }
            });
        }

        function refreshPresetCaptureStepText() {
            if (!presetCapture) return;
            mapStepNumber.textContent = 'Preset';
            mapStepText.textContent = presetCapture.kind === 'dropoff'
                ? 'Tap the map near Point B to set passenger drop-off. Tap again to adjust it.'
                : 'Tap the map near Point A to set passenger pickup. Tap again to adjust it.';
            updateAllowedPresetRadius();
            mapStepHint.textContent = 'Allowed: within ' + formatRadiusDistance(allowedRouteRadiusKm) + ' of the route path, or ' + formatRadiusDistance(allowedEndpointRadiusKm) + ' of the route point. Switch to Point A / Point B above to leave this mode.';
        }

        function startPresetCapture(kind, row) {
            setCustomStopPanel(true);
            if (row) {
                setActivePresetRow(row);
            }
            if (!activePresetRow) {
                addPresetStopRow();
            }
            if (!activePresetRow) return;
            setActivePresetRow(activePresetRow);
            presetCapture = {
                row: activePresetRow,
                kind: kind === 'dropoff' ? 'dropoff' : 'pickup'
            };
            refreshPresetCaptureStepText();
            syncTargetButtons();
            syncPresetCaptureButtons();
        }

        function applyPresetMapPoint(row, kind, lat, lng, placeName) {
            var targetPrefix = kind === 'dropoff' ? 'dropoff' : 'pickup';
            var anchor = targetPrefix === 'dropoff'
                ? (destinationMarker ? destinationMarker.getLatLng() : null)
                : (pickupMarker ? pickupMarker.getLatLng() : null);
            var selectedPoint = L.latLng(lat, lng);
            var distanceFromAnchor = distanceKmBetween(selectedPoint, anchor);

            if (!anchor) {
                var missingMessage = targetPrefix === 'dropoff'
                    ? 'Set Point B first, then choose the passenger drop-off.'
                    : 'Set Point A first, then choose the passenger pickup.';
                showMapToast(missingMessage);
                mapStepText.textContent = targetPrefix === 'dropoff'
                    ? 'Set Point B first before adding a passenger drop-off.'
                    : 'Set Point A first before adding a passenger pickup.';
                mapStepHint.textContent = 'Custom stops are tied to the fixed route points.';
                return false;
            }

            updateAllowedPresetRadius();
            var distanceFromRoute = distanceToRouteKm(selectedPoint);
            var withinRoute = distanceFromRoute !== null && distanceFromRoute <= allowedRouteRadiusKm;
            var withinEndpoint = distanceFromAnchor !== null && distanceFromAnchor <= allowedEndpointRadiusKm;

            if (!withinRoute && !withinEndpoint) {
                var nearestRouteText = distanceFromRoute === null ? 'unknown' : formatRadiusDistance(distanceFromRoute);
                showMapToast('Pick a point closer to the route. Nearest route distance: ' + nearestRouteText + '.');
                mapStepText.textContent = 'That point is too far from the route.';
                mapStepHint.textContent = 'Allowed: within ' + formatRadiusDistance(allowedRouteRadiusKm) + ' of the route path, or ' + formatRadiusDistance(allowedEndpointRadiusKm) + ' of Point ' + (targetPrefix === 'dropoff' ? 'B' : 'A') + '. Nearest route distance: ' + nearestRouteText + '.';
                return false;
            }

            var nameInput = presetInput(row, targetPrefix + '_name');
            var latInput = presetInput(row, targetPrefix + '_latitude');
            var lngInput = presetInput(row, targetPrefix + '_longitude');

            if (nameInput && placeName) nameInput.value = placeName;
            if (latInput) latInput.value = Number(lat).toFixed(7);
            if (lngInput) lngInput.value = Number(lng).toFixed(7);

            syncPresetStopMarkers();
            syncPresetFares(false);
            fetchRouteOptions();
            return true;
        }

        function selectedRouteDistanceKm() {
            var selectedRoute = fetchedRoutes[selectedRouteIndex] || fetchedRoutes[0] || null;
            if (selectedRoute && Number.isFinite(Number(selectedRoute.distance)) && Number(selectedRoute.distance) > 0) {
                return Number(selectedRoute.distance) / 1000;
            }
            if (pickupMarker && destinationMarker) {
                return Math.max(0.1, distanceKmBetween(pickupMarker.getLatLng(), destinationMarker.getLatLng()) || 0.1);
            }
            return 1;
        }

        function suggestedPresetFare(row) {
            var baseFare = defaultFareInput ? (parseFloat(defaultFareInput.value || '0') || 0) : 0;
            var pickupPoint = latLngFromInputs(presetInput(row, 'pickup_latitude'), presetInput(row, 'pickup_longitude'));
            var dropoffPoint = latLngFromInputs(presetInput(row, 'dropoff_latitude'), presetInput(row, 'dropoff_longitude'));
            var pickupDistance = distanceKmBetween(pickupPoint, pickupMarker ? pickupMarker.getLatLng() : null) || 0;
            var dropoffDistance = distanceKmBetween(dropoffPoint, destinationMarker ? destinationMarker.getLatLng() : null) || 0;
            var detour = pickupDistance + dropoffDistance;
            var routeDistanceKm = selectedRouteDistanceKm();
            var fare = baseFare > 0 ? ((detour / routeDistanceKm) * baseFare) : 0;

            return Math.max(0, Math.round(fare * 20) / 20);
        }

        function syncPresetFares(force) {
            presetRows().forEach(function (row) {
                var input = presetInput(row, 'extra_fee_amount');
                if (!input) return;
                if (!force && input.dataset.autoFare === '0') return;
                input.value = suggestedPresetFare(row).toFixed(2);
                input.dataset.autoFare = '1';
            });
        }

        function fillPresetFromRoute(row, direction) {
            if (!row) return;
            var pickupSource = direction === 'ba'
                ? { name: destinationName, lat: destinationLat, lng: destinationLng }
                : { name: pickupName, lat: pickupLat, lng: pickupLng };
            var dropoffSource = direction === 'ba'
                ? { name: pickupName, lat: pickupLat, lng: pickupLng }
                : { name: destinationName, lat: destinationLat, lng: destinationLng };

            var pickup = presetInput(row, 'pickup_name');
            var pickupLatInput = presetInput(row, 'pickup_latitude');
            var pickupLngInput = presetInput(row, 'pickup_longitude');
            var dropoff = presetInput(row, 'dropoff_name');
            var dropoffLatInput = presetInput(row, 'dropoff_latitude');
            var dropoffLngInput = presetInput(row, 'dropoff_longitude');

            if (pickup && pickupSource.name) pickup.value = pickupSource.name.value || '';
            if (pickupLatInput && pickupSource.lat) pickupLatInput.value = pickupSource.lat.value || '';
            if (pickupLngInput && pickupSource.lng) pickupLngInput.value = pickupSource.lng.value || '';
            if (dropoff && dropoffSource.name) dropoff.value = dropoffSource.name.value || '';
            if (dropoffLatInput && dropoffSource.lat) dropoffLatInput.value = dropoffSource.lat.value || '';
            if (dropoffLngInput && dropoffSource.lng) dropoffLngInput.value = dropoffSource.lng.value || '';
            syncPresetStopMarkers();
            syncPresetFares(true);
            fetchRouteOptions();
        }

        function bindPresetRow(row) {
            if (!row) return;
            row.addEventListener('click', function (event) {
                if (event.target && event.target.closest && event.target.closest('button')) return;
                setActivePresetRow(row);
            });
            var fareInput = presetInput(row, 'extra_fee_amount');
            if (fareInput) {
                fareInput.addEventListener('input', function () {
                    fareInput.dataset.autoFare = '0';
                });
            }
            row.querySelectorAll('[data-fill-preset]').forEach(function (button) {
                button.addEventListener('click', function () {
                    fillPresetFromRoute(row, button.getAttribute('data-fill-preset') || 'ab');
                });
            });
            row.querySelectorAll('[data-capture-preset]').forEach(function (button) {
                button.addEventListener('click', function () {
                    startPresetCapture(button.getAttribute('data-capture-preset'), row);
                });
            });
            var removeBtn = row.querySelector('[data-remove-preset-stop]');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    row.remove();
                    updatePresetStopEmpty();
                });
            }
        }

        function addPresetStopRow() {
            if (!presetStopList || !passengerOptionsHtml.length) return;
            var index = presetStopIndex++;
            var row = document.createElement('div');
            row.className = 'rf-preset-row';
            row.setAttribute('data-preset-stop-row', '');
            row.innerHTML =
                '<div class="rf-preset-row-head">' +
                    '<select class="rf-preset-select" name="passenger_stops[' + index + '][user_id]">' + passengerOptions('') + '</select>' +
                    '<button type="button" class="rf-preset-remove" data-remove-preset-stop aria-label="Remove preset passenger"><i class="fa-solid fa-trash"></i></button>' +
                '</div>' +
                '<div class="rf-preset-grid">' +
                    '<input class="rf-hidden" type="text" name="passenger_stops[' + index + '][pickup_name]" placeholder="Passenger pickup / boarding point">' +
                    '<input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[' + index + '][pickup_latitude]">' +
                    '<input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[' + index + '][pickup_longitude]">' +
                    '<input class="rf-hidden" type="text" name="passenger_stops[' + index + '][dropoff_name]" placeholder="Passenger drop-off point">' +
                    '<input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[' + index + '][dropoff_latitude]">' +
                    '<input class="rf-hidden" type="number" step="0.0000001" name="passenger_stops[' + index + '][dropoff_longitude]">' +
                    '<div class="rf-preset-compact">' +
                        '<span class="rf-preset-distance" data-preset-distance></span>' +
                        '<label class="rf-preset-fare"><span>Extra fee</span><input type="number" step="0.01" min="0" name="passenger_stops[' + index + '][extra_fee_amount]" placeholder="Auto" data-auto-fare="1"></label>' +
                    '</div>' +
                    '<input class="rf-hidden" type="text" name="passenger_stops[' + index + '][note]" placeholder="Optional note">' +
                    '<input type="hidden" name="passenger_stops[' + index + '][is_active]" value="1">' +
                '</div>' +
                '<div class="rf-preset-actions">' +
                    '<button type="button" class="rf-preset-mini-btn" data-fill-preset="ab" hidden><i class="fa-solid fa-route"></i>Use A to B</button>' +
                    '<button type="button" class="rf-preset-mini-btn" data-fill-preset="ba" hidden><i class="fa-solid fa-right-left"></i>Use B to A</button>' +
                    '<button type="button" class="rf-preset-mini-btn" data-capture-preset="pickup"><i class="fa-solid fa-location-dot"></i>Passenger pickup</button>' +
                    '<button type="button" class="rf-preset-mini-btn" data-capture-preset="dropoff"><i class="fa-solid fa-flag-checkered"></i>Passenger drop-off</button>' +
                    '<span class="rf-preset-distance" data-preset-distance></span>' +
                '</div>';
            presetStopList.appendChild(row);
            bindPresetRow(row);
            setActivePresetRow(row);
            syncPresetStopMarkers();
            syncPresetFares(false);
            updatePresetStopEmpty();
        }

        function setActiveTarget(target) {
            nextTarget = target === 'destination' ? 'destination' : 'pickup';
            clearPresetCapture();
            Array.prototype.forEach.call(document.querySelectorAll('.rf-target-btn'), function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-map-target') === nextTarget);
            });
            if (!pickupMarker && nextTarget === 'destination') {
                mapStepText.textContent = 'Point B selected. Set Point A first if this is a new route.';
                mapStepHint.textContent = 'You can still preview places, but the route requires both points.';
            }
        }

        function syncTargetButtons() {
            Array.prototype.forEach.call(document.querySelectorAll('.rf-target-btn'), function (btn) {
                var routeActive = !presetCapture && btn.getAttribute('data-map-target') === nextTarget;
                var presetActive = presetCapture && btn.getAttribute('data-preset-target') === presetCapture.kind;
                btn.classList.toggle('active', Boolean(routeActive || presetActive));
            });
        }

        function updateFields(target, lat, lng, placeName) {
            var fallbackName = 'Coordinates ' + Number(lat).toFixed(7) + ', ' + Number(lng).toFixed(7);
            if (target === 'pickup') {
                pickupLat.value = lat.toFixed(7);
                pickupLng.value = lng.toFixed(7);
                pickupName.value = placeName || pickupName.value.trim() || fallbackName;
            } else {
                destinationLat.value = lat.toFixed(7);
                destinationLng.value = lng.toFixed(7);
                destinationName.value = placeName || destinationName.value.trim() || fallbackName;
            }
        }

        function syncMarkerFromCoordinateInputs(target) {
            var latInput = target === 'pickup' ? pickupLat : destinationLat;
            var lngInput = target === 'pickup' ? pickupLng : destinationLng;
            var lat = parseFloat(latInput.value);
            var lng = parseFloat(lngInput.value);

            if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                return;
            }

            setMarker(target, lat, lng);
            reverseGeocode(lat, lng, function (resolvedName) {
                if (target === 'pickup') pickupName.value = resolvedName || pickupName.value;
                if (target === 'destination') destinationName.value = resolvedName || destinationName.value;
            });
            updateStepIndicator();
        }

        function bindCoordinateInputs(target) {
            var latInput = target === 'pickup' ? pickupLat : destinationLat;
            var lngInput = target === 'pickup' ? pickupLng : destinationLng;
            var timer = null;
            var schedule = function () {
                if (timer) clearTimeout(timer);
                timer = setTimeout(function () { syncMarkerFromCoordinateInputs(target); }, 350);
            };
            latInput.addEventListener('input', schedule);
            lngInput.addEventListener('input', schedule);
            latInput.addEventListener('change', function () { syncMarkerFromCoordinateInputs(target); });
            lngInput.addEventListener('change', function () { syncMarkerFromCoordinateInputs(target); });
        }

        function getMarker(target) {
            return target === 'pickup' ? pickupMarker : destinationMarker;
        }

        function updateStepIndicator() {
            if (!pickupMarker) {
                syncTargetButtons();
                mapStepNumber.textContent = '1/3';
                mapStepText.textContent = nextTarget === 'destination'
                    ? 'Point B target selected.'
                    : 'Tap the map to set Point A.';
                mapStepHint.textContent = nextTarget === 'destination'
                    ? 'You can set Point B first, but the route still requires Point A before options appear.'
                    : 'Search results can preview a place first. Confirm only when the pin is correct.';
                mapResetBtn.classList.remove('show');
                return;
            }

            if (pickupMarker && !destinationMarker) {
                syncTargetButtons();
                mapStepNumber.textContent = '2/3';
                mapStepText.textContent = nextTarget === 'pickup'
                    ? 'Point A is set. You can adjust it or switch to Point B.'
                    : 'Tap now to set Point B.';
                mapStepHint.textContent = nextTarget === 'pickup'
                    ? 'The search preview will replace Point A and reset Point B.'
                    : 'Use the search preview or tap the exact drop-off point.';
                mapResetBtn.classList.remove('show');
                return;
            }

            syncTargetButtons();
            mapStepNumber.textContent = '3/3';
            mapStepText.textContent = 'Route complete.';
            mapStepHint.textContent = 'Add passenger stops if needed, then review the shortest route option.';
            mapResetBtn.classList.add('show');
        }

        function getMarkerIcon(target) {
            var className = target === 'pickup' ? 'pickup-pin' : 'destination-pin';
            return L.divIcon({ className: '', html: '<div class="' + className + '"></div>', iconSize: [18, 18], iconAnchor: [9, 9] });
        }

        function setMarker(target, lat, lng, placeName) {
            var marker = getMarker(target);

            if (!marker) {
                marker = L.marker([lat, lng], { draggable: true, icon: getMarkerIcon(target) }).addTo(map);
                marker.bindTooltip(target === 'pickup' ? 'Point A' : 'Point B', { permanent: true, direction: 'top', offset: [0, -10], className: 'map-point-tooltip' });
                marker.on('dragend', function (event) {
                    var pos = event.target.getLatLng();
                    updateFields(target, pos.lat, pos.lng);
                    reverseGeocode(pos.lat, pos.lng, function (resolvedName) {
                        if (target === 'pickup' && resolvedName) pickupName.value = resolvedName;
                        if (target === 'destination' && resolvedName) destinationName.value = resolvedName;
                        fetchRouteOptions();
                    });
                    fetchRouteOptions();
                });

                if (target === 'pickup') pickupMarker = marker;
                if (target === 'destination') destinationMarker = marker;
            } else {
                marker.setLatLng([lat, lng]);
            }

            updateFields(target, lat, lng, placeName);
            syncPresetRadius();
            syncPresetStopMarkers();
            fetchRouteOptions();
        }

        function clearRoute() {
            routeLayers.forEach(function (layer) { map.removeLayer(layer); });
            routeLayers = [];
        }

        function clearRouteOptions(message) {
            fetchedRoutes = [];
            selectedRouteIndex = 0;
            fareAdviceByRoute = {};
            clearRoute();
            routeOptionsEl.innerHTML = '<div class="rf-route-empty">' + message + '</div>';
            if (fareAdvisorStatus) fareAdvisorStatus.textContent = 'Waiting route';
            if (fareAiReason) fareAiReason.textContent = 'Set both points to get AI fuel and toll advice.';
            updateFareBreakdown(null, false);
            updateStepIndicator();
            updateToolbarStats(null);
            syncPresetFares(false);
        }

        function formatDistance(meters) { return (meters / 1000).toFixed(1) + ' km'; }

        function formatDuration(seconds) {
            var totalMinutes = Math.round(seconds / 60);
            if (totalMinutes < 60) return totalMinutes + ' min';
            var hours = Math.floor(totalMinutes / 60);
            var mins = totalMinutes % 60;
            return hours + 'h ' + mins + 'm';
        }

        // Instant placeholder shown before the live fetch below resolves —
        // overwritten as soon as real data.gov.my prices arrive, kept only
        // as a network-failure fallback.
        var fuelPricePresets = {
            RON95: { budi: 1.99, market: 2.60 },
            RON97: { market: 3.47 },
            Diesel: { market: 2.95 }
        };

        function loadLiveFuelPrices() {
            fetch('{{ route('fuel-prices.current') }}', { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data || !data.RON95 || !data.RON97 || !data.Diesel) return;
                    fuelPricePresets = {
                        RON95: {
                            budi: Number(data.RON95.budi) || fuelPricePresets.RON95.budi,
                            market: Number(data.RON95.market) || fuelPricePresets.RON95.market
                        },
                        RON97: { market: Number(data.RON97.market) || fuelPricePresets.RON97.market },
                        Diesel: { market: Number(data.Diesel.market) || fuelPricePresets.Diesel.market }
                    };
                    // false: don't clobber a price the driver already typed by hand
                    applyFuelPricePreset(false);
                    refreshFareAdvisorFromInputs(false);
                    if (fuelPriceAsOfEl && data.as_of) {
                        fuelPriceAsOfEl.textContent = 'Live price as of ' + data.as_of;
                        fuelPriceAsOfEl.hidden = false;
                    }
                })
                .catch(function () {});
        }

        function money(value) {
            return 'RM ' + (Number.isFinite(Number(value)) ? Number(value).toFixed(2) : '0.00');
        }

        function roundedFare(value) {
            return Math.max(0, Math.round((Number(value) || 0) * 20) / 20);
        }

        function advisorNumber(input, fallback) {
            var value = input ? parseFloat(input.value) : NaN;
            return Number.isFinite(value) ? value : fallback;
        }

        function applyFuelPricePreset(force) {
            if (!fareFuelType || !fareFuelPrice) return;
            if (!force && fareFuelPrice.dataset.edited === '1') return;
            var type = fareFuelType.value || 'RON95';
            var preset = fuelPricePresets[type] || fuelPricePresets.RON95;
            var price = type === 'RON95' && fareUseBudi && fareUseBudi.checked
                ? preset.budi
                : (preset.market ?? preset.budi ?? 0);
            fareFuelPrice.value = Number(price || 0).toFixed(2);
        }

        function estimateFromVehicleFallback() {
            var vehicle = (fareReasonVehicle || '').toLowerCase();
            if (/diesel|hilux|triton|d-max|dmax|navara|fortuner|transit|van|lorry|truck|pickup/.test(vehicle)) {
                return { fuel_type: 'Diesel', estimated_km_per_liter: 9.5, estimated_toll_cost: 0, confidence: 'low', reason: 'Fallback estimate based on vehicle keywords.' };
            }
            if (/myvi|axia|bezza|iriz|saga|persona|city|vios|almera|jazz|yaris/.test(vehicle)) {
                return { fuel_type: 'RON95', estimated_km_per_liter: 13, estimated_toll_cost: 0, confidence: 'low', reason: 'Fallback estimate based on compact car fuel economy.' };
            }
            return { fuel_type: 'RON95', estimated_km_per_liter: 11.5, estimated_toll_cost: 0, confidence: 'low', reason: 'Fallback estimate used until AI advice is available.' };
        }

        function calculateCostFare(route, adviceOverride) {
            if (!route) {
                return { total: 0, perSplit: 0, liters: 0, fuelCost: 0, toll: 0, buffer: 0, split: 1 };
            }
            var advice = adviceOverride || {};
            var kmPerLiter = Number(advice.estimated_km_per_liter ?? advisorNumber(fareKmPerLiter, 11.5));
            var toll = Number(advice.estimated_toll_cost ?? advisorNumber(fareTollCost, 0));
            var fuelPrice = advisorNumber(fareFuelPrice, 0);
            var bufferRate = advisorNumber(fareBufferRate, 15) / 100;
            var split = Math.max(1, Math.round(advisorNumber(fareSplitCount, 1)));
            var distanceKm = Math.max(0, (route.distance || 0) / 1000);
            var liters = kmPerLiter <= 0 ? 0 : distanceKm / kmPerLiter;
            var fuelCost = liters * fuelPrice;
            var buffer = fuelCost * Math.max(0, bufferRate);
            var total = roundedFare(fuelCost + Math.max(0, toll) + buffer);

            return {
                total: total,
                perSplit: roundedFare(total / split),
                liters: liters,
                fuelCost: fuelCost,
                toll: Math.max(0, toll),
                buffer: buffer,
                split: split
            };
        }

        function suggestedFare(distanceMeters, durationSeconds) {
            return calculateCostFare({ distance: distanceMeters || 0, duration: durationSeconds || 0 }).total;
        }

        function applyFareAdviceToControls(advice, force) {
            if (!advice || (fareAdvisorEdited && !force)) return;
            if (fareFuelType && advice.fuel_type) fareFuelType.value = advice.fuel_type;
            if (fareKmPerLiter && Number.isFinite(Number(advice.estimated_km_per_liter))) {
                fareKmPerLiter.value = Number(advice.estimated_km_per_liter).toFixed(1);
            }
            if (fareTollCost && Number.isFinite(Number(advice.estimated_toll_cost))) {
                fareTollCost.value = Number(advice.estimated_toll_cost).toFixed(2);
            }
            if (fareUseBudi && fareFuelType) {
                fareUseBudi.disabled = fareFuelType.value !== 'RON95';
            }
            applyFuelPricePreset(false);
        }

        function updateFareBreakdown(route, writeDefault) {
            var calculation = calculateCostFare(route || fetchedRoutes[selectedRouteIndex] || fetchedRoutes[0] || null);
            if (fareFuelUsed) fareFuelUsed.textContent = calculation.liters > 0 ? calculation.liters.toFixed(2) + ' L' : '-';
            if (fareFuelCost) fareFuelCost.textContent = money(calculation.fuelCost);
            if (fareTollBuffer) fareTollBuffer.textContent = money(calculation.toll) + ' + ' + money(calculation.buffer);
            if (fareSuggestedTotal) fareSuggestedTotal.textContent = money(calculation.total);
            if (fareSuggestedPerPerson) fareSuggestedPerPerson.textContent = money(calculation.perSplit) + ' / split';
            if (writeDefault && defaultFareInput && Number.isFinite(calculation.total)) {
                defaultFareInput.value = calculation.total.toFixed(2);
            }
        }

        function updateToolbarStats(route) {
            if (!route) {
                statDistance.innerHTML = '<span>Distance</span> —';
                statTime.innerHTML = '<span>ETA</span> —';
                statFare.innerHTML = '<span>Fare</span> —';
                return;
            }
            statDistance.innerHTML = '<span>Distance</span> ' + formatDistance(route.distance);
            statTime.innerHTML = '<span>ETA</span> ' + formatDuration(route.duration);
            statFare.innerHTML = '<span>Fare</span> RM ' + suggestedFare(route.distance, route.duration).toFixed(2);
            updateFareBreakdown(route, false);
        }

        function autoFillLowestSuggestedFare() {
            if (!defaultFareInput || fareEditedByUser || !fetchedRoutes.length) return;
            var lowestFare = fetchedRoutes.reduce(function (lowest, route) {
                var fare = suggestedFare(route.distance, route.duration);
                return lowest === null ? fare : Math.min(lowest, fare);
            }, null);
            if (lowestFare !== null && Number.isFinite(lowestFare)) {
                defaultFareInput.value = lowestFare.toFixed(2);
            }
        }

        function fillFareFromRoute(route) {
            if (!defaultFareInput || !route) return;
            var fare = suggestedFare(route.distance, route.duration);
            if (Number.isFinite(fare)) { defaultFareInput.value = fare.toFixed(2); }
            updateFareBreakdown(route, false);
        }

        function refreshFareAdvisorFromInputs(markEdited) {
            if (markEdited) fareAdvisorEdited = true;
            var selectedRoute = fetchedRoutes[selectedRouteIndex] || fetchedRoutes[0] || null;
            if (selectedRoute) {
                fillFareFromRoute(selectedRoute);
                updateToolbarStats(selectedRoute);
            } else {
                updateFareBreakdown(null, false);
            }
            fetchedRoutes.forEach(function (route, index) {
                var fareEl = document.querySelector('[data-ai-fare="' + index + '"]');
                if (fareEl) {
                    fareEl.textContent = 'Suggested Fare RM ' + calculateCostFare(route).total.toFixed(2);
                }
            });
            syncPresetFares(false);
        }

        var fareReasonUrl     = '{{ route('ai.fare-advice') }}';
        var recommendRouteUrl = '{{ route('ai.recommend-route') }}';
        var fareReasonCsrf    = '{{ csrf_token() }}';
        var fareReasonVehicle = '{{ addslashes(auth()->user()->vehicle_model ?? '') }}';

        function suggestionReason(route) {
            // Fallback — shown instantly while AI loads
            var distanceKm = ((route.distance || 0) / 1000);
            var minutes = Math.round((route.duration || 0) / 60);
            return 'Fare based on ' + distanceKm.toFixed(1) + ' km distance and ~' + minutes + ' min travel time.';
        }

        function fetchAiReason(routeIndex, distanceKm, durationMin, baseFare, roads, forcedFuelType) {
            // Check element exists before firing request
            if (!document.querySelector('[data-ai-reason="' + routeIndex + '"]')) return Promise.resolve();
            if (fareAdvisorStatus && routeIndex === selectedRouteIndex) fareAdvisorStatus.textContent = 'AI checking';
            if (fareAiReason && routeIndex === selectedRouteIndex) {
                fareAiReason.innerHTML = Mascot.html({ size: 14, state: 'thinking', className: 'ai-inline-mascot' }) + 'AI is analyzing route... (may take up to 20s)';
                Mascot.initAll();
            }

            return fetch(fareReasonUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': fareReasonCsrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    distance_km:  distanceKm,
                    duration_min: durationMin,
                    roads:        roads,
                    vehicle:      fareReasonVehicle || null,
                    fuel_type:    forcedFuelType || null,
                }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                // Re-query at resolution time so we always hit the current DOM element
                var reasonEl = document.querySelector('[data-ai-reason="' + routeIndex + '"]');
                var fareEl   = document.querySelector('[data-ai-fare="' + routeIndex + '"]');
                if (!reasonEl) return;

                fareAdviceByRoute[routeIndex] = data || {};
                var route = fetchedRoutes[routeIndex] || null;
                if (route && fareEl) {
                    var optionFare = calculateCostFare(route, data).total;
                    fareEl.textContent = 'Suggested Fare RM ' + optionFare.toFixed(2);
                    fareEl.dataset.adjustedFare = optionFare.toFixed(2);
                }

                if (routeIndex === selectedRouteIndex) {
                    // A confirmed fuel type is the driver's own choice, not an AI
                    // guess — km/L and toll must still refresh for it even though
                    // fareAdvisorEdited is already true from picking the dropdown.
                    applyFareAdviceToControls(data, Boolean(forcedFuelType));
                    updateFareBreakdown(route, !fareEditedByUser);
                    updateToolbarStats(route);
                    if (fareAdvisorStatus) fareAdvisorStatus.textContent = (data.confidence || 'medium') + ' confidence';
                    if (fareAiReason) fareAiReason.textContent = data.reason || 'AI estimated fuel efficiency and toll from vehicle and route context.';
                }

                var baseReason = reasonEl.dataset.baseReason || '';
                var tollRoads = Array.isArray(data.toll_roads) && data.toll_roads.length ? ' Toll roads: ' + data.toll_roads.join(', ') + '.' : '';
                var costLine = data.estimated_km_per_liter !== undefined
                    ? ' AI: ' + (data.fuel_type || 'fuel') + ', ' + Number(data.estimated_km_per_liter || 0).toFixed(1) + ' km/L, toll ' + money(Number(data.estimated_toll_cost || 0)) + '.'
                    : '';
                var aiLine = data.reason
                    ? '<br>' + Mascot.html({ size: 12, state: 'idle', className: 'ai-inline-mascot' }) + escapeText(costLine + tollRoads + ' ' + data.reason)
                    : '';
                reasonEl.innerHTML = baseReason + aiLine;
                Mascot.initAll();
            })
            .catch(function () {
                var reasonEl = document.querySelector('[data-ai-reason="' + routeIndex + '"]');
                if (reasonEl && reasonEl.dataset.baseReason) {
                    reasonEl.innerHTML = reasonEl.dataset.baseReason;
                }
            });
        }

        // Compares the now-complete options (real fuel+toll numbers, not just
        // distance/time) and picks which one to pre-select, with a reason —
        // a genuine multi-factor trade-off call, not a fixed "always cheapest"
        // or "always fastest" rule.
        function requestRouteRecommendation() {
            if (!routeRecommendationEl || fetchedRoutes.length < 2) return;

            var options = fetchedRoutes.map(function (route, idx) {
                var calc = calculateCostFare(route, fareAdviceByRoute[idx]);
                return {
                    distance_km: (route.distance || 0) / 1000,
                    duration_min: Math.round((route.duration || 0) / 60),
                    toll_cost: calc.toll,
                    total_cost: calc.total,
                };
            });

            routeRecommendationEl.hidden = false;
            routeRecommendationEl.innerHTML = Mascot.html({ size: 16, state: 'thinking', className: 'ai-inline-mascot' }) + ' AI comparing ' + options.length + ' options...';
            Mascot.initAll();

            fetch(recommendRouteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': fareReasonCsrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ options: options }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var index = Number.isInteger(data.recommended_index) && fetchedRoutes[data.recommended_index]
                    ? data.recommended_index
                    : 0;

                selectedRouteIndex = index;
                Array.prototype.forEach.call(routeOptionsEl.querySelectorAll('.rf-route-option-btn'), function (btn) {
                    btn.classList.toggle('active', parseInt(btn.getAttribute('data-route-index'), 10) === index);
                });
                applyFareAdviceToControls(fareAdviceByRoute[index], true);
                fillFareFromRoute(fetchedRoutes[index]);
                updateToolbarStats(fetchedRoutes[index]);
                if (fareAiReason && fareAdviceByRoute[index]) {
                    fareAiReason.textContent = fareAdviceByRoute[index].reason || '';
                }
                if (fareAdvisorStatus && fareAdviceByRoute[index]) {
                    fareAdvisorStatus.textContent = (fareAdviceByRoute[index].confidence || 'medium') + ' confidence';
                }
                drawSelectedRoute();

                routeRecommendationEl.innerHTML = Mascot.html({ size: 16, state: 'idle', id: 'routeRecommendationMascot', className: 'ai-inline-mascot' }) + ' <span><strong>AI recommends Option ' + (index + 1) + '.</strong> ' + escapeText(data.reason || '') + '</span>';
                Mascot.initAll();
                Mascot.play('routeRecommendationMascot', 'wink', { duration: 800 });
            })
            .catch(function () {
                routeRecommendationEl.hidden = true;
            });
        }

        function summarizeRoads(route) {
            if (!route || !route.legs || !route.legs.length || !route.legs[0].steps) return 'Main route';
            var names = [];
            route.legs[0].steps.forEach(function (step) {
                var name = (step.name || '').trim();
                if (name && names.indexOf(name) === -1) names.push(name);
            });
            return names.slice(0, 3).join(' • ') || 'Main route';
        }

        // Full distinct road-name list (not the 3-name display summary above) —
        // this is what toll detection needs. A long trip's toll highway (e.g.
        // "Lebuhraya DUKE") often only shows up well past the first 3 named
        // streets leaving the pickup point, so sending the truncated summary
        // to the fare advisor made it blind to real tolls on longer routes.
        function allRoadNames(route) {
            if (!route || !route.legs) return '';
            var names = [];
            route.legs.forEach(function (leg) {
                (leg.steps || []).forEach(function (step) {
                    var name = (step.name || '').trim();
                    if (name && names.indexOf(name) === -1) names.push(name);
                });
            });
            var joined = names.slice(0, 25).join(' • ');
            return joined.length > 480 ? joined.slice(0, 480) : joined;
        }

        function drawSelectedRoute() {
            if (!fetchedRoutes.length) { clearRoute(); return; }
            var minDuration = Math.min.apply(null, fetchedRoutes.map(function (r) { return r.duration || 0; }));
            clearRoute();
            fetchedRoutes.forEach(function (route, index) {
                var coordinates = route.geometry.coordinates.map(function (pair) { return [pair[1], pair[0]]; });
                var isPrimary = index === selectedRouteIndex;
                var isSlower = (route.duration || 0) > (minDuration + 60);
                var layer = L.polyline(coordinates, {
                    color: isPrimary ? '#2563eb' : (isSlower ? '#bfdbfe' : '#93c5fd'),
                    weight: isPrimary ? 4.5 : 3,
                    opacity: isPrimary ? 0.94 : 0.75
                }).addTo(map);
                routeLayers.push(layer);
            });
        }

        function renderRouteOptions() {
            if (!fetchedRoutes.length) { clearRouteOptions('No route options found. Try another point.'); return; }
            routeOptionsEl.innerHTML = fetchedRoutes.map(function (route, index) {
                var activeClass = index === selectedRouteIndex ? ' active' : '';
                var roadSummary = summarizeRoads(route);
                var fare = suggestedFare(route.distance, route.duration);
                return '<button type="button" class="rf-route-option-btn' + activeClass + '" data-route-index="' + index + '">'
                    + '<div class="rf-route-option-top">'
                    + '<span class="rf-route-option-name">Option ' + (index + 1) + '</span>'
                    + '<span class="rf-route-option-meta">' + formatDistance(route.distance) + ' • ' + formatDuration(route.duration) + '</span>'
                    + '</div>'
                    + '<div class="rf-route-option-road">' + roadSummary + '</div>'
                    + '<div class="rf-route-option-fare" data-ai-fare="' + index + '">Suggested Fare RM ' + fare.toFixed(2) + '</div>'
                    + '<div class="rf-route-option-reason" data-ai-reason="' + index + '" data-base-reason="' + suggestionReason(route) + '">'
                    + '<div class="rf-ai-loading" style="color: #64748b; font-size: 13px; margin-top: 4px;">' + Mascot.html({ size: 14, state: 'thinking', className: 'ai-inline-mascot' }) + 'AI is analyzing route... (may take up to 20s)</div>'
                    + '</div>'
                    + '</button>';
            }).join('');
            Mascot.initAll();

            // Async: fetch AI-generated reasons after DOM renders. Collected so
            // the cross-option recommendation (below) only runs once every
            // option's real fuel/toll numbers are actually in.
            var aiReasonPromises = fetchedRoutes.map(function (route, index) {
                var distKm  = (route.distance || 0) / 1000;
                var durMin  = Math.round((route.duration || 0) / 60);
                var fare    = suggestedFare(route.distance, route.duration);
                var roads   = allRoadNames(route);
                return fetchAiReason(index, distKm, durMin, fare, roads);
            });

            if (fetchedRoutes.length > 1) {
                Promise.all(aiReasonPromises).then(requestRouteRecommendation);
            } else if (routeRecommendationEl) {
                routeRecommendationEl.hidden = true;
            }
            Array.prototype.forEach.call(routeOptionsEl.querySelectorAll('.rf-route-option-btn'), function (btn) {
                btn.addEventListener('click', function () {
                    selectedRouteIndex = parseInt(btn.getAttribute('data-route-index'), 10) || 0;
                    applyFareAdviceToControls(fareAdviceByRoute[selectedRouteIndex]);
                    fillFareFromRoute(fetchedRoutes[selectedRouteIndex]);
                    updateToolbarStats(fetchedRoutes[selectedRouteIndex]);
                    if (fareAiReason && fareAdviceByRoute[selectedRouteIndex]) {
                        fareAiReason.textContent = fareAdviceByRoute[selectedRouteIndex].reason || 'AI estimated fuel efficiency and toll from route context.';
                    }
                    if (fareAdvisorStatus && fareAdviceByRoute[selectedRouteIndex]) {
                        fareAdvisorStatus.textContent = (fareAdviceByRoute[selectedRouteIndex].confidence || 'medium') + ' confidence';
                    }
                    syncPresetFares(false);
                    // Toggle active class only — do NOT re-render, preserves AI reasoning text
                    Array.prototype.forEach.call(routeOptionsEl.querySelectorAll('.rf-route-option-btn'), function (b) {
                        b.classList.toggle('active', b === btn);
                    });
                    drawSelectedRoute();
                });
            });
        }

        function drawStraightLine() {
            if (!pickupMarker || !destinationMarker) { clearRoute(); return; }
            clearRoute();
            routeLayers.push(L.polyline(routeWaypoints(), { color: '#2563eb', weight: 4, opacity: 0.9, dashArray: '8 8' }).addTo(map));
        }

        function middleCustomStops() {
            var points = [];
            presetRows().forEach(function (row) {
                var pickupPoint = latLngFromInputs(presetInput(row, 'pickup_latitude'), presetInput(row, 'pickup_longitude'));
                var dropoffPoint = latLngFromInputs(presetInput(row, 'dropoff_latitude'), presetInput(row, 'dropoff_longitude'));
                if (pickupPoint) points.push(pickupPoint);
                if (dropoffPoint) points.push(dropoffPoint);
            });
            return points;
        }

        function pathDistance(points) {
            var total = 0;
            for (var i = 1; i < points.length; i += 1) {
                total += points[i - 1].distanceTo(points[i]);
            }
            return total;
        }

        function permutations(items) {
            if (items.length <= 1) return [items.slice()];
            var result = [];
            items.forEach(function (item, index) {
                var rest = items.slice(0, index).concat(items.slice(index + 1));
                permutations(rest).forEach(function (tail) {
                    result.push([item].concat(tail));
                });
            });
            return result;
        }

        function nearestNeighborOrder(start, middle) {
            var remaining = middle.slice();
            var ordered = [];
            var cursor = start;
            while (remaining.length) {
                var bestIndex = 0;
                var bestDistance = cursor.distanceTo(remaining[0]);
                for (var i = 1; i < remaining.length; i += 1) {
                    var distance = cursor.distanceTo(remaining[i]);
                    if (distance < bestDistance) {
                        bestDistance = distance;
                        bestIndex = i;
                    }
                }
                cursor = remaining.splice(bestIndex, 1)[0];
                ordered.push(cursor);
            }
            return ordered;
        }

        function optimizedMiddleStops(start, middle, end) {
            if (middle.length <= 1) return middle;
            if (middle.length > 7) return nearestNeighborOrder(start, middle);

            var best = middle;
            var bestDistance = Infinity;
            permutations(middle).forEach(function (order) {
                var distance = pathDistance([start].concat(order, [end]));
                if (distance < bestDistance) {
                    bestDistance = distance;
                    best = order;
                }
            });
            return best;
        }

        function routeWaypoints() {
            if (!pickupMarker || !destinationMarker) return [];
            var start = pickupMarker.getLatLng();
            var end = destinationMarker.getLatLng();
            var middle = optimizedMiddleStops(start, middleCustomStops(), end);
            return [start].concat(middle, [end]);
        }

        function osrmUrl(points) {
            return 'https://router.project-osrm.org/route/v1/driving/'
                + points.map(function (point) {
                    return encodeURIComponent(point.lng) + ',' + encodeURIComponent(point.lat);
                }).join(';')
                + '?overview=full&geometries=geojson&steps=true&alternatives=true';
        }

        function fetchOsrmRoutes(points) {
            return fetch(osrmUrl(points), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) { return (data && data.routes) ? data.routes : []; })
                .catch(function () { return []; });
        }

        // A point offset perpendicular to a start->end line, used to force
        // OSRM onto a genuinely different corridor. The public OSRM demo
        // server has no `exclude=` support and its own alternatives= search
        // frequently returns just one route for anything past ~150km (a long
        // intercity trip on one dominant highway corridor has nothing to find
        // an alternative against) — routing a candidate via this point is
        // what actually produces a second, third option to compare.
        function perpendicularOffset(start, end, sideSign, fraction) {
            var midLat = (start.lat + end.lat) / 2;
            var midLng = (start.lng + end.lng) / 2;
            var dLat = end.lat - start.lat;
            var dLng = end.lng - start.lng;
            var perpLat = -dLng;
            var perpLng = dLat;
            var perpLen = Math.sqrt(perpLat * perpLat + perpLng * perpLng) || 1;
            var straightLineDegrees = Math.sqrt(dLat * dLat + dLng * dLng);
            var offsetDegrees = straightLineDegrees * fraction;
            return L.latLng(
                midLat + (perpLat / perpLen) * offsetDegrees * sideSign,
                midLng + (perpLng / perpLen) * offsetDegrees * sideSign
            );
        }

        // With custom passenger stops, `points` has more than 2 entries — all
        // of them mandatory, in order. The detour point can't replace any of
        // them, so it goes on whichever consecutive pair is furthest apart
        // (that's both where OSRM is most likely to have failed to find a
        // native alternative, and where a detour is actually meaningful
        // rather than a rounding error on a 500m hop between two stops).
        function longestSegmentIndex(points) {
            var maxDistance = -1;
            var maxIndex = 0;
            for (var i = 0; i < points.length - 1; i += 1) {
                var distance = points[i].distanceTo(points[i + 1]);
                if (distance > maxDistance) {
                    maxDistance = distance;
                    maxIndex = i;
                }
            }
            return { index: maxIndex, distance: maxDistance };
        }

        function withOffsetInsertedAt(points, segmentIndex, sideSign, fraction) {
            var offsetPoint = perpendicularOffset(points[segmentIndex], points[segmentIndex + 1], sideSign, fraction);
            var withOffset = points.slice();
            withOffset.splice(segmentIndex + 1, 0, offsetPoint);
            return withOffset;
        }

        function routesAreSimilar(a, b) {
            var distDiff = Math.abs(a.distance - b.distance) / Math.max(a.distance, b.distance, 1);
            var durDiff = Math.abs(a.duration - b.duration) / Math.max(a.duration, b.duration, 1);
            return distDiff < 0.03 && durDiff < 0.03;
        }

        function mergeDistinctRoutes(routeLists) {
            var merged = [];
            routeLists.forEach(function (list) {
                list.forEach(function (route) {
                    var isDuplicate = merged.some(function (existing) { return routesAreSimilar(existing, route); });
                    if (!isDuplicate) merged.push(route);
                });
            });
            merged.sort(function (a, b) { return a.duration - b.duration; });

            // The offset-detour trick sometimes routes through a genuinely bad
            // via-point (nothing there, wrong direction) and OSRM dutifully
            // returns a technically-valid but absurd multi-hour-worse path.
            // A "different route" still has to be a plausible real choice.
            var fastest = merged.length ? merged[0].duration : 0;
            var reasonable = merged.filter(function (route, index) {
                return index === 0 || route.duration <= fastest * 1.6;
            });

            return reasonable.slice(0, 5);
        }

        function fetchRouteOptions() {
            if (!pickupMarker || !destinationMarker) {
                clearRouteOptions('Set both points to view route options.');
                return;
            }
            syncPresetRadius();
            syncPresetStopMarkers();
            var points = routeWaypoints();

            showMapLoading('Finding best routes…');

            fetchOsrmRoutes(points).then(function (baseRoutes) {
                if (!baseRoutes.length) {
                    fetchedRoutes = [];
                    fareAdviceByRoute = {};
                    drawStraightLine();
                    routeOptionsEl.innerHTML = '<div class="rf-route-empty">Routing service is unavailable. Showing a straight line only.</div>';
                    if (fareAdvisorStatus) fareAdvisorStatus.textContent = 'Route fallback';
                    hideMapLoading();
                    return;
                }

                // Only try the offset-detour trick when OSRM's own alternatives
                // search came up short, and only on a segment long enough that
                // a detour would mean something (skip a 500m hop between two
                // closely-spaced custom stops).
                var longestSegment = longestSegmentIndex(points);
                var tryingDetour = baseRoutes.length < 3 && longestSegment.distance >= 3000;
                var extraFetches = tryingDetour ? [
                    fetchOsrmRoutes(withOffsetInsertedAt(points, longestSegment.index, 1, 0.18)),
                    fetchOsrmRoutes(withOffsetInsertedAt(points, longestSegment.index, -1, 0.18)),
                ] : [];

                if (tryingDetour) showMapLoading('Checking alternate routes…');

                Promise.all(extraFetches).then(function (extraLists) {
                    fetchedRoutes = mergeDistinctRoutes([baseRoutes].concat(extraLists));
                    fareAdviceByRoute = {};
                    selectedRouteIndex = 0;
                    renderRouteOptions();
                    drawSelectedRoute();
                    autoFillLowestSuggestedFare();
                    updateToolbarStats(fetchedRoutes[0]);
                    syncPresetFares(false);
                    hideMapLoading();
                });
            });
        }

        function clearMarker(target) {
            if (target === 'pickup' && pickupMarker) {
                map.removeLayer(pickupMarker); pickupMarker = null;
                pickupLat.value = ''; pickupLng.value = ''; pickupName.value = '';
            }
            if (target === 'destination' && destinationMarker) {
                map.removeLayer(destinationMarker); destinationMarker = null;
                destinationLat.value = ''; destinationLng.value = ''; destinationName.value = '';
            }
            syncPresetRadius();
            syncPresetStopMarkers();
            updateStepIndicator();
        }

        function resetRouteSelection() {
            clearMarker('pickup');
            clearMarker('destination');
            clearSearchPreview();
            nextTarget = 'pickup';
            clearRouteOptions('Set both points to view route options.');
            updateStepIndicator();
        }

        function reverseGeocode(lat, lng, onDone) {
            var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) { if (!data) return; onDone(data.display_name || ''); })
                .catch(function () {});
        }

        function searchPlace() {
            var query = (searchInput.value || '').trim();
            if (!query) return;
            fetchMalaysiaPlaces(query, 10)
                .then(function (items) {
                    if (!items.length) return;
                    previewSearchResult(items[0], query);
                    renderSearchSuggestions(items);
                })
                .catch(function () {});
        }

        function previewSearchResult(item, fallbackText) {
            if (!item) return;
            var lat = parseFloat(item.lat);
            var lng = parseFloat(item.lon);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            previewPlace = { lat: lat, lng: lng, name: item.display_name || fallbackText || '', type: item.type || item.class || '' };
            if (previewMarker) map.removeLayer(previewMarker);
            previewMarker = L.marker([lat, lng], {
                draggable: true,
                icon: L.divIcon({ className: '', html: '<div class="preview-pin"></div>', iconSize: [24, 24], iconAnchor: [12, 12] })
            }).addTo(map);
            previewMarker.bindTooltip('Preview pin', { permanent: true, direction: 'top', offset: [0, -12], className: 'map-point-tooltip' });
            previewMarker.on('dragend', function (event) {
                var pos = event.target.getLatLng();
                previewPlace.lat = pos.lat; previewPlace.lng = pos.lng;
                reverseGeocode(pos.lat, pos.lng, function (resolvedName) {
                    if (resolvedName) { previewPlace.name = resolvedName; updatePreviewCard(); }
                });
                updatePreviewCard();
            });
            map.setView([lat, lng], Math.max(map.getZoom(), 16));
            updatePreviewCard();
        }

        function updatePreviewCard() {
            if (!previewPlace || !previewCard) return;
            var targetText = presetCapture
                ? (presetCapture.kind === 'dropoff' ? 'passenger drop-off' : 'passenger pickup')
                : (nextTarget === 'pickup' ? 'Point A' : 'Point B');
            var title = String(previewPlace.name || 'Selected location').split(',')[0] || 'Selected location';
            previewTitle.textContent = title;
            previewSub.textContent = String(previewPlace.name || '').trim() || 'Drag the preview pin or confirm this point.';
            previewUseBtn.textContent = 'Use as ' + targetText;
            previewCard.classList.add('show');
        }

        function clearSearchPreview() {
            previewPlace = null;
            if (previewMarker) { map.removeLayer(previewMarker); previewMarker = null; }
            if (previewCard) previewCard.classList.remove('show');
        }

        function applyPreviewAsTarget(target) {
            if (!previewPlace) return;
            if (presetCapture && presetCapture.row) {
                applyPresetMapPoint(presetCapture.row, presetCapture.kind, previewPlace.lat, previewPlace.lng, previewPlace.name || '');
                presetCapture.row.classList.remove('is-active-capture');
                clearSearchPreview();
                clearPresetCapture();
                updateStepIndicator();
                return;
            }
            if (target === 'pickup') clearMarker('pickup');
            if (target === 'destination') clearMarker('destination');
            setMarker(target, previewPlace.lat, previewPlace.lng, previewPlace.name || '');
            nextTarget = nextTarget === 'pickup' ? 'destination' : 'pickup';
            clearSearchPreview();
            updateStepIndicator();
        }

        function renderSearchSuggestions(items) {
            if (!searchSuggest) return;
            if (!items || !items.length) { searchSuggest.classList.remove('show'); searchSuggest.innerHTML = ''; return; }
            searchSuggest.innerHTML = items.map(function (item, index) {
                var display = String(item.display_name || '').trim();
                var main = display.split(',')[0] || 'Select location';
                return '<button type="button" class="rf-map-suggest-btn" data-index="' + index + '">'
                    + '<span class="rf-map-suggest-main">' + escapeText(main) + '</span>'
                    + '<span class="rf-map-suggest-sub">' + escapeText(display) + '</span>'
                    + '</button>';
            }).join('');
            searchSuggest.classList.add('show');
            Array.from(searchSuggest.querySelectorAll('.rf-map-suggest-btn')).forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var idx = parseInt(btn.getAttribute('data-index') || '-1', 10);
                    var picked = items[idx];
                    if (!picked) return;
                    searchInput.value = String(picked.display_name || '').trim();
                    renderSearchSuggestions([]);
                    previewSearchResult(picked, searchInput.value);
                });
            });
        }

        function uniqueSearchItems(items) {
            var seen = {};
            return (items || []).filter(function (item) {
                var lat = parseFloat(item.lat);
                var lng = parseFloat(item.lon);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false;
                var key = lat.toFixed(5) + ':' + lng.toFixed(5) + ':' + String(item.display_name || '').toLowerCase().slice(0, 48);
                if (seen[key]) return false;
                seen[key] = true;
                return true;
            });
        }

        function rankMalaysiaResult(item, query) {
            var display = String(item.display_name || '').toLowerCase();
            var main = display.split(',')[0] || '';
            var q = String(query || '').toLowerCase();
            var score = 0;
            if (String(item.provider || '') === 'Photon') score += 10;
            if (display.indexOf('malaysia') !== -1) score += 40;
            if (display.indexOf('pahang') !== -1) score += 10;
            if (display.indexOf('pekan') !== -1) score += 8;
            if (main.indexOf(q) !== -1) score += 28;
            if (display.indexOf(q) !== -1) score += 18;
            if (['university','college','school','hospital','clinic','bus_stop','fuel','parking','residential','road','house','street','locality'].indexOf(String(item.type || '')) !== -1) score += 8;
            if (['amenity','highway','building','place','tourism','street'].indexOf(String(item.class || '')) !== -1) score += 6;
            if (/taman|kampung|jalan|lorong|persiaran|universiti|fakulti|sekolah|masjid|surau|klinik|hospital|terminal|stesen/.test(display)) score += 8;
            score += Math.max(0, Math.min(20, Number(item.importance || 0) * 20));
            return score;
        }

        function normalizePhotonFeature(feature) {
            var props = feature && feature.properties ? feature.properties : {};
            var coordinates = feature && feature.geometry && Array.isArray(feature.geometry.coordinates) ? feature.geometry.coordinates : [];
            var lng = parseFloat(coordinates[0]);
            var lat = parseFloat(coordinates[1]);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
            var parts = [props.name, props.street, props.district, props.city, props.county, props.state, props.country].filter(function (part, index, items) {
                return part && items.indexOf(part) === index;
            });
            return { provider: 'Photon', display_name: parts.join(', ') || props.name || 'Selected location', lat: String(lat), lon: String(lng), type: props.type || props.osm_value || 'place', class: props.osm_key || 'place', importance: 0.7, address: { road: props.street || '', suburb: props.district || '', city: props.city || '', town: props.city || '', county: props.county || '', state: props.state || '', country: props.country || '' } };
        }

        function normalizeNominatimItem(item) {
            if (!item) return null;
            return { provider: 'OSM', display_name: item.display_name || '', lat: item.lat, lon: item.lon, type: item.type || '', class: item.class || '', importance: item.importance || 0, address: item.address || {} };
        }

        function fetchPhotonPlaces(query, limit) {
            var clean = String(query || '').trim();
            if (!clean) return Promise.resolve([]);
            var url = 'https://photon.komoot.io/api/?q=' + encodeURIComponent(clean) + '&limit=' + encodeURIComponent(limit || 10) + '&lang=en&lat=3.139&lon=101.6869';
            return fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (payload) {
                    var features = payload && Array.isArray(payload.features) ? payload.features : [];
                    return features.map(normalizePhotonFeature).filter(function (item) {
                        return item && String(item.display_name || '').toLowerCase().indexOf('malaysia') !== -1;
                    });
                })
                .catch(function () { return []; });
        }

        function fetchNominatimPlaces(query, limit) {
            var clean = String(query || '').trim();
            if (!clean) return Promise.resolve([]);
            var queries = [clean];
            if (!/malaysia|malaisie|malaysian/i.test(clean)) queries.push(clean + ', Malaysia');
            var params = function (q) {
                return 'format=jsonv2&addressdetails=1&namedetails=1&dedupe=1&countrycodes=my&bounded=1&viewbox=99.0,7.8,119.5,0.5&accept-language=en-MY,ms,en&limit=' + encodeURIComponent(limit || 10) + '&q=' + encodeURIComponent(q);
            };
            return Promise.all(queries.map(function (q) {
                var url = 'https://nominatim.openstreetmap.org/search?' + params(q);
                return fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : []; })
                    .catch(function () { return []; });
            })).then(function (groups) {
                return groups.reduce(function (carry, group) {
                    return carry.concat(Array.isArray(group) ? group.map(normalizeNominatimItem).filter(Boolean) : []);
                }, []);
            });
        }

        function fetchMalaysiaPlaces(query, limit) {
            var clean = String(query || '').trim();
            if (!clean) return Promise.resolve([]);
            return Promise.all([fetchPhotonPlaces(clean, limit || 10), fetchNominatimPlaces(clean, limit || 10)])
                .then(function (groups) {
                    return uniqueSearchItems(groups.reduce(function (carry, group) {
                        return carry.concat(Array.isArray(group) ? group : []);
                    }, [])).sort(function (a, b) {
                        return rankMalaysiaResult(b, clean) - rankMalaysiaResult(a, clean);
                    }).slice(0, limit || 10);
                });
        }

        var searchDebounceTimer = null;
        function fetchSearchSuggestions() {
            var query = (searchInput.value || '').trim();
            if (query.length < 1) { renderSearchSuggestions([]); return; }
            fetchMalaysiaPlaces(query, 10)
                .then(function (items) { renderSearchSuggestions(Array.isArray(items) ? items : []); })
                .catch(function () { renderSearchSuggestions([]); });
        }

        function goToCurrentLocation() {
            if (!navigator.geolocation) return;
            locateBtn.disabled = true;
            locateBtn.classList.add('is-loading');
            locateBtn.innerHTML = '<i class="fa-solid fa-spinner" aria-hidden="true"></i>';
            navigator.geolocation.getCurrentPosition(function (position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 16);
                locateBtn.disabled = false;
                locateBtn.classList.remove('is-loading');
                locateBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>';
            }, function () {
                locateBtn.disabled = false;
                locateBtn.classList.remove('is-loading');
                locateBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>';
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
        }

        map.on('click', function (event) {
            if (presetCapture && presetCapture.row) {
                var activeRow = presetCapture.row;
                var activeKind = presetCapture.kind;
                if (!applyPresetMapPoint(activeRow, activeKind, event.latlng.lat, event.latlng.lng, '')) {
                    return;
                }
                reverseGeocode(event.latlng.lat, event.latlng.lng, function (resolvedName) {
                    if (resolvedName) {
                        applyPresetMapPoint(activeRow, activeKind, event.latlng.lat, event.latlng.lng, resolvedName);
                    }
                });
                // Stay in this same custom-stop capture (pickup or drop-off) so the
                // next tap can still adjust it — only leave capture mode when the
                // user explicitly clicks Point A / Point B, or picks another
                // capture button (both already call clearPresetCapture()).
                updateStepIndicator();
                refreshPresetCaptureStepText();
                return;
            }

            var assignedTarget = nextTarget;
            clearSearchPreview();
            if (assignedTarget === 'pickup') {
                clearMarker('pickup');
                setMarker('pickup', event.latlng.lat, event.latlng.lng);
                nextTarget = 'destination';
            } else {
                clearMarker('destination');
                setMarker('destination', event.latlng.lat, event.latlng.lng);
                nextTarget = 'pickup';
            }
            reverseGeocode(event.latlng.lat, event.latlng.lng, function (resolvedName) {
                if (assignedTarget === 'pickup' && resolvedName) pickupName.value = resolvedName;
                if (assignedTarget === 'destination' && resolvedName) destinationName.value = resolvedName;
            });
            updateStepIndicator();
        });

        Array.prototype.forEach.call(document.querySelectorAll('.rf-target-btn'), function (btn) {
            btn.addEventListener('click', function () {
                setActiveTarget(btn.getAttribute('data-map-target'));
                updatePreviewCard();
                updateStepIndicator();
            });
        });

        searchBtn.addEventListener('click', searchPlace);
        locateBtn.addEventListener('click', goToCurrentLocation);
        mapResetBtn.addEventListener('click', resetRouteSelection);

        if (defaultFareInput) {
            defaultFareInput.addEventListener('input', function () { fareEditedByUser = true; });
        }

        applyFareAdviceToControls(estimateFromVehicleFallback());
        applyFuelPricePreset(true);
        updateFareBreakdown(null, false);
        loadLiveFuelPrices();

        if (fareFuelType) {
            fareFuelType.addEventListener('change', function () {
                fareAdvisorEdited = true;
                if (fareUseBudi) fareUseBudi.disabled = fareFuelType.value !== 'RON95';
                applyFuelPricePreset(true);

                // The driver just confirmed the actual fuel type — ask the AI to
                // re-estimate km/L and toll for THAT fuel type instead of reusing
                // whatever numbers were guessed for the previous one.
                var selectedRoute = fetchedRoutes[selectedRouteIndex] || fetchedRoutes[0] || null;
                if (selectedRoute) {
                    var distKm = (selectedRoute.distance || 0) / 1000;
                    var durMin = Math.round((selectedRoute.duration || 0) / 60);
                    var roads  = allRoadNames(selectedRoute);
                    fetchAiReason(selectedRouteIndex, distKm, durMin, suggestedFare(selectedRoute.distance, selectedRoute.duration), roads, fareFuelType.value);
                } else {
                    refreshFareAdvisorFromInputs(false);
                }
            });
        }
        if (fareUseBudi) {
            fareUseBudi.addEventListener('change', function () {
                applyFuelPricePreset(true);
                refreshFareAdvisorFromInputs(true);
            });
        }
        if (fareFuelPrice) {
            fareFuelPrice.addEventListener('input', function () {
                fareFuelPrice.dataset.edited = '1';
                refreshFareAdvisorFromInputs(true);
            });
        }
        [fareKmPerLiter, fareTollCost, fareBufferRate, fareSplitCount].forEach(function (input) {
            if (!input) return;
            input.addEventListener('input', function () { refreshFareAdvisorFromInputs(true); });
        });

        bindCoordinateInputs('pickup');
        bindCoordinateInputs('destination');

        previewUseBtn.addEventListener('click', function () { applyPreviewAsTarget(nextTarget); });
        previewCloseBtn.addEventListener('click', clearSearchPreview);

        searchInput.addEventListener('input', function () {
            if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(fetchSearchSuggestions, 220);
        });
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); searchPlace(); }
        });
        document.addEventListener('click', function (e) {
            if (!searchSuggest) return;
            var inWrap = e.target && e.target.closest ? e.target.closest('.rf-map-search-wrap') : null;
            if (!inWrap) renderSearchSuggestions([]);
        });

        // Toolbar ghost buttons — wire up after map is ready
        if (togglePresetStopsBtn) {
            togglePresetStopsBtn.addEventListener('click', function () {
                setCustomStopPanel(!presetStopPanel.classList.contains('show'));
            });
        }

        if (addPresetStopBtn) {
            addPresetStopBtn.addEventListener('click', function () {
                setCustomStopPanel(true);
                addPresetStopRow();
            });
        }

        Array.prototype.forEach.call(document.querySelectorAll('[data-preset-stop-row]'), bindPresetRow);
        updatePresetStopEmpty();
        setCustomStopPanel(Boolean(presetStopPanel && presetStopPanel.classList.contains('show')));

        var existingPickupLat = toNumber(pickupLat.value);
        var existingPickupLng = toNumber(pickupLng.value);
        var existingDestLat = toNumber(destinationLat.value);
        var existingDestLng = toNumber(destinationLng.value);

        if (existingPickupLat !== null && existingPickupLng !== null) {
            setMarker('pickup', existingPickupLat, existingPickupLng);
            map.setView([existingPickupLat, existingPickupLng], 14);
            nextTarget = 'destination';
        }
        if (existingDestLat !== null && existingDestLng !== null) {
            setMarker('destination', existingDestLat, existingDestLng);
            if (existingPickupLat === null || existingPickupLng === null) map.setView([existingDestLat, existingDestLng], 14);
            nextTarget = 'pickup';
        }
        if (existingPickupLat !== null && existingPickupLng !== null && existingDestLat !== null && existingDestLng !== null) {
            fetchRouteOptions();
        }

        updateStepIndicator();

        if (existingPickupLat === null && existingPickupLng === null && existingDestLat === null && existingDestLng === null) {
            setTimeout(goToCurrentLocation, 350);
        }

        // AI pre-fill bridge — used by sessionStorage draft on create page
        window.__chRouteSetPoint = function (target, lat, lng, name) {
            setMarker(target, parseFloat(lat), parseFloat(lng), name || '');
        };
    })();
</script>
