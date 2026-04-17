@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <style>
        .page-shell {
            display: grid;
            gap: 12px;
        }

        .page-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
        }

        .title-card {
            position: relative;
            overflow: hidden;
        }

        .title-card::after {
            content: "";
            position: absolute;
            right: -18px;
            top: -8px;
            width: 128px;
            height: 128px;
            background: no-repeat center/contain url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Cg fill='none' stroke='%230f172a' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M18 58c8-16 16-10 24-24 7-13 16-13 22-22'/%3E%3Ccircle cx='16' cy='60' r='5'/%3E%3Cpath d='M66 13l5 9h-10z' fill='%230f172a' stroke='none'/%3E%3Cpath d='M34 44l5-5M40 50l5-5'/%3E%3C/g%3E%3C/svg%3E");
            opacity: .08;
            transform: rotate(18deg);
            pointer-events: none;
        }

        .page-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 28px;
            line-height: 1.1;
            color: #0f172a;
        }

        .page-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .route-form-grid {
            margin-top: 12px;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .map-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .map-tools {
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr;
        }

        .map-search-row {
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr auto auto;
        }

        .map-search-row input {
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #fff;
            padding: 9px 11px;
            font-size: 13px;
            outline: none;
        }

        .map-search-row button {
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            padding: 0 12px;
            cursor: pointer;
        }

        .map-search-row button:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .map-help {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }

        .map-status {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px;
            display: grid;
            gap: 4px;
        }

        .map-step-title {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }

        .map-step-text {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .map-step-hint {
            font-size: 12px;
            color: #64748b;
            line-height: 1.3;
        }

        .map-step-actions {
            margin-top: 4px;
        }

        .map-reset-btn {
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #b91c1c;
            border-radius: 9px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: none;
        }

        .map-reset-btn.show {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .map-step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 24px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            background: #eff6ff;
            margin-right: 6px;
        }

        #routeMap {
            width: 100%;
            height: 300px;
            border-radius: 12px;
            border: 1px solid #dbe2ea;
        }

        .route-options {
            display: grid;
            gap: 8px;
        }

        .route-option-btn {
            border: 1px solid #dbe2ea;
            background: #fff;
            border-radius: 12px;
            padding: 10px;
            text-align: left;
            cursor: pointer;
            display: grid;
            gap: 4px;
        }

        .route-option-btn.active {
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .route-option-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .route-option-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .route-option-meta {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
        }

        .route-option-road {
            font-size: 11px;
            color: #64748b;
            line-height: 1.3;
        }
        .route-option-suggestion {
            margin-top: 4px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 700;
        }
        .route-option-reason {
            font-size: 11px;
            color: #475569;
            line-height: 1.35;
        }

        .route-empty {
            border: 1px dashed #dbe2ea;
            border-radius: 10px;
            padding: 8px 10px;
            color: #64748b;
            font-size: 12px;
        }

        .pickup-pin,
        .destination-pin {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.3);
        }

        .pickup-pin { background: #16a34a; }
        .destination-pin { background: #dc2626; }

        .field-block {
            display: grid;
            gap: 6px;
        }

        .field-block-full {
            grid-column: 1 / -1;
        }

        .field-block label {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
        }

        .field-block input {
            width: 100%;
            border-radius: 11px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #0f172a;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
            transition: border-color .15s ease, background-color .15s ease;
        }

        .field-block input:focus {
            border-color: #94a3b8;
            background: #fff;
        }

        .coords-head {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 2px;
        }

        .coords-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            background: #f8fafc;
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            padding: 10px;
        }

        .coords-grid > div {
            display: grid;
            gap: 6px;
        }

        .active-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .active-toggle input {
            width: 16px;
            height: 16px;
            accent-color: #0f172a;
        }

        .form-error {
            margin-top: 10px;
            color: #b91c1c;
            border: 1px solid rgba(185, 28, 28, 0.25);
            background: rgba(185, 28, 28, 0.06);
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
        }

        .form-actions {
            margin-top: 14px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn-primary,
        .btn-secondary {
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 12px;
            text-decoration: none;
            border: 1px solid #dbe2ea;
            cursor: pointer;
        }

        .btn-primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }

        .btn-secondary {
            background: #fff;
            color: #0f172a;
        }

        @media (min-width: 768px) {
            .page-card {
                padding: 16px;
            }

            .route-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .coords-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .map-tools {
                grid-template-columns: 1fr;
                align-items: center;
            }

            .map-search-row {
                min-width: 360px;
            }
        }
    </style>

    <div class="page-shell">
        <section class="page-card title-card">
            <h1 class="page-title">Edit Saved Route</h1>
            <p class="page-subtitle">Update your saved route details and map points.</p>
        </section>

        <section class="page-card">
            <div class="map-card">
                <div class="map-tools">
                    <div class="map-search-row">
                        <input type="text" id="mapSearchInput" placeholder="Search address or place">
                        <button type="button" id="mapSearchBtn">Search</button>
                        <button type="button" id="mapLocateBtn">Current Location</button>
                    </div>
                </div>
                <p class="map-help">Tap once to set Point A, tap again to set Point B. A route line will be drawn automatically.</p>
                <div class="map-status">
                    <div class="map-step-title">
                        <span class="map-step-badge" id="mapStepNumber">1/3</span>
                        Route Setup Progress
                    </div>
                    <div class="map-step-text" id="mapStepText">Tap on the map to set Point A.</div>
                    <div class="map-step-hint" id="mapStepHint">Then tap again to set Point B and view route options.</div>
                    <div class="map-step-actions">
                        <button type="button" class="map-reset-btn" id="mapResetBtn"><i class="fa-solid fa-rotate-left"></i><span>Reset Route</span></button>
                    </div>
                </div>
                <div id="routeMap"></div>
                <div class="route-options" id="routeOptions">
                    <div class="route-empty">Set Point A and Point B to see route options with distance and ETA.</div>
                </div>
            </div>

            <form action="{{ route('saved-routes.update', $savedRoute) }}" method="POST">
                @method('PUT')
                @include('saved-routes._form', ['submitLabel' => 'Update Route'])
            </form>
        </section>
    </div>

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

            var searchInput = document.getElementById('mapSearchInput');
            var searchBtn = document.getElementById('mapSearchBtn');
            var locateBtn = document.getElementById('mapLocateBtn');
            var routeOptionsEl = document.getElementById('routeOptions');
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

            var nextTarget = 'pickup';
            var pickupMarker = null;
            var destinationMarker = null;
            var routeLayers = [];
            var selectedRouteIndex = 0;
            var fetchedRoutes = [];
            var currentLocationMarker = null;

            function toNumber(value) {
                var num = parseFloat(value);
                return Number.isFinite(num) ? num : null;
            }

            function updateFields(target, lat, lng, placeName) {
                if (target === 'pickup') {
                    pickupLat.value = lat.toFixed(7);
                    pickupLng.value = lng.toFixed(7);
                    if (placeName && !pickupName.value.trim()) pickupName.value = placeName;
                } else {
                    destinationLat.value = lat.toFixed(7);
                    destinationLng.value = lng.toFixed(7);
                    if (placeName && !destinationName.value.trim()) destinationName.value = placeName;
                }
            }

            function getMarker(target) {
                return target === 'pickup' ? pickupMarker : destinationMarker;
            }

            function updateStepIndicator() {
                if (!pickupMarker) {
                    mapStepNumber.textContent = '1/3';
                    mapStepText.textContent = 'Tap on the map to set Point A.';
                    mapStepHint.textContent = 'Then tap again to set Point B and view route options.';
                    mapResetBtn.classList.remove('show');
                    return;
                }

                if (pickupMarker && !destinationMarker) {
                    mapStepNumber.textContent = '2/3';
                    mapStepText.textContent = 'Now tap to set Point B.';
                    mapStepHint.textContent = 'After Point B is set, you can compare route options below.';
                    mapResetBtn.classList.remove('show');
                    return;
                }

                mapStepNumber.textContent = '3/3';
                mapStepText.textContent = 'Route complete. Review and choose your preferred option.';
                mapStepHint.textContent = 'Tap map again anytime to reset and start a new Point A.';
                mapResetBtn.classList.add('show');
            }

            function getMarkerIcon(target) {
                var className = target === 'pickup' ? 'pickup-pin' : 'destination-pin';
                return L.divIcon({
                    className: '',
                    html: '<div class="' + className + '"></div>',
                    iconSize: [18, 18],
                    iconAnchor: [9, 9]
                });
            }

            function setMarker(target, lat, lng, placeName) {
                var marker = getMarker(target);

                if (!marker) {
                    marker = L.marker([lat, lng], { draggable: true, icon: getMarkerIcon(target) }).addTo(map);
                    marker.bindTooltip(target === 'pickup' ? 'Point A' : 'Point B', {
                        permanent: true,
                        direction: 'top',
                        offset: [0, -10],
                        className: 'map-point-tooltip'
                    });
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
                fetchRouteOptions();
            }

            function clearRoute() {
                routeLayers.forEach(function (layer) {
                    map.removeLayer(layer);
                });
                routeLayers = [];
            }

            function clearRouteOptions(message) {
                fetchedRoutes = [];
                selectedRouteIndex = 0;
                clearRoute();
                routeOptionsEl.innerHTML = '<div class="route-empty">' + message + '</div>';
                updateStepIndicator();
            }

            function formatDistance(meters) {
                return (meters / 1000).toFixed(1) + ' km';
            }

            function formatDuration(seconds) {
                var totalMinutes = Math.round(seconds / 60);
                if (totalMinutes < 60) return totalMinutes + ' min';
                var hours = Math.floor(totalMinutes / 60);
                var mins = totalMinutes % 60;
                return hours + 'h ' + mins + 'm';
            }

            function suggestedFare(distanceMeters, durationSeconds) {
                var distanceKm = (distanceMeters || 0) / 1000;
                var base = 2.5;
                var perKm = 1.1;
                var timeBuffer = (durationSeconds || 0) >= 1800 ? 1.2 : ((durationSeconds || 0) >= 900 ? 0.6 : 0.3);
                var fare = base + (distanceKm * perKm) + timeBuffer;
                return Math.max(2.5, Math.round(fare * 20) / 20);
            }

            function suggestionReason(route) {
                var distanceKm = ((route.distance || 0) / 1000);
                var minutes = Math.round((route.duration || 0) / 60);

                return 'AI reason: base fare + distance (' + distanceKm.toFixed(1) + ' km) + time buffer for ~' + minutes + ' min travel. Time affects the fare, but distance is still the bigger factor.';
            }

            function summarizeRoads(route) {
                if (!route || !route.legs || !route.legs.length || !route.legs[0].steps) {
                    return 'Main route';
                }

                var names = [];
                route.legs[0].steps.forEach(function (step) {
                    var name = (step.name || '').trim();
                    if (name && names.indexOf(name) === -1) {
                        names.push(name);
                    }
                });

                return names.slice(0, 3).join(' • ') || 'Main route';
            }

            function drawSelectedRoute() {
                if (!fetchedRoutes.length) {
                    clearRoute();
                    return;
                }

                var minDuration = Math.min.apply(null, fetchedRoutes.map(function (r) { return r.duration || 0; }));
                clearRoute();

                fetchedRoutes.forEach(function (route, index) {
                    var coordinates = route.geometry.coordinates.map(function (pair) {
                        return [pair[1], pair[0]];
                    });

                    var isPrimary = index === selectedRouteIndex;
                    var isSlower = (route.duration || 0) > (minDuration + 60);

                    var layer = L.polyline(coordinates, {
                        color: isPrimary ? '#2563eb' : (isSlower ? '#bfdbfe' : '#93c5fd'),
                        weight: isPrimary ? 4.5 : 3,
                        opacity: isPrimary ? 0.94 : 0.75,
                        dashArray: null
                    }).addTo(map);

                    routeLayers.push(layer);
                });
            }

            function renderRouteOptions() {
                if (!fetchedRoutes.length) {
                    clearRouteOptions('No route options found. Try another point.');
                    return;
                }

                routeOptionsEl.innerHTML = fetchedRoutes.map(function (route, index) {
                    var activeClass = index === selectedRouteIndex ? ' active' : '';
                    var roadSummary = summarizeRoads(route);
                    return ''
                        + '<button type="button" class="route-option-btn' + activeClass + '" data-route-index="' + index + '">'
                        + '  <div class="route-option-top">'
                        + '    <span class="route-option-name">Option ' + (index + 1) + '</span>'
                        + '    <span class="route-option-meta">' + formatDistance(route.distance) + ' • ' + formatDuration(route.duration) + '</span>'
                        + '  </div>'
                        + '  <div class="route-option-road">' + roadSummary + '</div>'
                        + '  <div class="route-option-suggestion">Suggested fare RM ' + suggestedFare(route.distance, route.duration).toFixed(2) + '</div>'
                        + '  <div class="route-option-reason">' + suggestionReason(route) + '</div>'
                        + '</button>';
                }).join('');

                Array.prototype.forEach.call(routeOptionsEl.querySelectorAll('.route-option-btn'), function (btn) {
                    btn.addEventListener('click', function () {
                        selectedRouteIndex = parseInt(btn.getAttribute('data-route-index'), 10) || 0;
                        renderRouteOptions();
                        drawSelectedRoute();
                    });
                });
            }

            function drawStraightLine() {
                if (!pickupMarker || !destinationMarker) {
                    clearRoute();
                    return;
                }

                clearRoute();
                routeLayers.push(L.polyline(
                    [pickupMarker.getLatLng(), destinationMarker.getLatLng()],
                    { color: '#2563eb', weight: 4, opacity: 0.9, dashArray: '8 8' }
                ).addTo(map));
            }

            function fetchRouteOptions() {
                if (!pickupMarker || !destinationMarker) {
                    clearRouteOptions('Set Point A and Point B to see route options with distance and ETA.');
                    return;
                }

                var p = pickupMarker.getLatLng();
                var d = destinationMarker.getLatLng();
                var url = 'https://router.project-osrm.org/route/v1/driving/'
                    + encodeURIComponent(p.lng) + ',' + encodeURIComponent(p.lat) + ';'
                    + encodeURIComponent(d.lng) + ',' + encodeURIComponent(d.lat)
                    + '?overview=full&geometries=geojson&steps=true&alternatives=true';

                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data || !data.routes || !data.routes.length) {
                            drawStraightLine();
                            routeOptionsEl.innerHTML = '<div class="route-empty">Routing service unavailable. Showing direct line only.</div>';
                            return;
                        }
                        fetchedRoutes = data.routes.slice(0, 3);
                        selectedRouteIndex = 0;
                        renderRouteOptions();
                        drawSelectedRoute();
                    })
                    .catch(function () {
                        drawStraightLine();
                        routeOptionsEl.innerHTML = '<div class="route-empty">Could not fetch route options. Showing direct line only.</div>';
                    });
            }

            function clearMarker(target) {
                if (target === 'pickup' && pickupMarker) {
                    map.removeLayer(pickupMarker);
                    pickupMarker = null;
                    pickupLat.value = '';
                    pickupLng.value = '';
                    pickupName.value = '';
                }

                if (target === 'destination' && destinationMarker) {
                    map.removeLayer(destinationMarker);
                    destinationMarker = null;
                    destinationLat.value = '';
                    destinationLng.value = '';
                    destinationName.value = '';
                }

                updateStepIndicator();
            }

            function resetRouteSelection() {
                clearMarker('pickup');
                clearMarker('destination');
                nextTarget = 'pickup';
                clearRouteOptions('Set Point A and Point B to see route options with distance and ETA.');
                updateStepIndicator();
            }

            function reverseGeocode(lat, lng, onDone) {
                var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data) return;
                        var text = data.display_name || '';
                        onDone(text);
                    })
                    .catch(function () {});
            }

            function searchPlace() {
                var query = (searchInput.value || '').trim();
                if (!query) return;

                var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' + encodeURIComponent(query);
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : []; })
                    .then(function (items) {
                        if (!items.length) return;
                        var first = items[0];
                        var lat = parseFloat(first.lat);
                        var lng = parseFloat(first.lon);
                        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                        if (nextTarget === 'pickup') {
                            clearMarker('pickup');
                            clearMarker('destination');
                        }
                        if (nextTarget === 'destination') {
                            clearMarker('destination');
                        }
                        setMarker(nextTarget, lat, lng, first.display_name || query);
                        if (nextTarget === 'pickup') {
                            nextTarget = 'destination';
                        } else {
                            nextTarget = 'pickup';
                        }
                        map.setView([lat, lng], 16);
                    })
                    .catch(function () {});
            }

            function goToCurrentLocation() {
                if (!navigator.geolocation) return;
                locateBtn.disabled = true;
                locateBtn.textContent = 'Locating...';

                navigator.geolocation.getCurrentPosition(function (position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    map.setView([lat, lng], 16);

                    if (currentLocationMarker) {
                        map.removeLayer(currentLocationMarker);
                    }

                    currentLocationMarker = L.circleMarker([lat, lng], {
                        radius: 7,
                        color: '#0f172a',
                        weight: 2,
                        fillColor: '#60a5fa',
                        fillOpacity: 0.95
                    }).addTo(map);

                    locateBtn.disabled = false;
                    locateBtn.textContent = 'Current Location';
                }, function () {
                    locateBtn.disabled = false;
                    locateBtn.textContent = 'Current Location';
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                });
            }

            map.on('click', function (event) {
                var assignedTarget = nextTarget;

                if (assignedTarget === 'pickup') {
                    clearMarker('pickup');
                    clearMarker('destination');
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
            searchBtn.addEventListener('click', searchPlace);
            locateBtn.addEventListener('click', goToCurrentLocation);
            mapResetBtn.addEventListener('click', resetRouteSelection);
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchPlace();
                }
            });

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
                if (existingPickupLat === null || existingPickupLng === null) {
                    map.setView([existingDestLat, existingDestLng], 14);
                }
                nextTarget = 'pickup';
            }

            if (existingPickupLat !== null && existingPickupLng !== null && existingDestLat !== null && existingDestLng !== null) {
                fetchRouteOptions();
            }

            updateStepIndicator();
        })();
    </script>
@endsection

