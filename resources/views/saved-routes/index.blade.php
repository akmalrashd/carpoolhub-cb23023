@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<style>
    /* ── Route card grid ── */
    .sr-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: 1fr;
    }
    @media (min-width: 1024px) {
        .sr-grid { grid-template-columns: repeat(2, 1fr); }
    }

    /* ── Route card ── */
    .sr-card {
        position: relative;
        overflow: hidden;
    }
    .sr-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-2);
        border-color: var(--hairline-strong);
    }

    /* Map thumbnail */
    .sr-thumb {
        height: 150px;
        background:
            linear-gradient(90deg, rgba(226, 214, 188, .55) 1px, transparent 1px),
            linear-gradient(0deg, rgba(226, 214, 188, .55) 1px, transparent 1px),
            #f7f2e7;
        background-size: 46px 46px;
        overflow: hidden;
        position: relative;
        border-bottom: 1px solid var(--hairline);
    }
    .sr-thumb-map {
        position: absolute;
        inset: 0;
        z-index: 2;
        background: #f7f2e7;
    }
    .sr-thumb.has-live-map .sr-thumb-road,
    .sr-thumb.has-live-map .sr-thumb-pin {
        display: none;
    }
    .sr-thumb .leaflet-container {
        width: 100%;
        height: 100%;
        background: #f7f2e7;
        font-family: var(--font-ui), sans-serif;
    }
    .sr-thumb .leaflet-control-attribution,
    .sr-thumb .leaflet-control-container {
        display: none;
    }
    .sr-mini-pin {
        width: 18px;
        height: 18px;
        border-radius: 999px;
        border: 3px solid #fff;
        box-shadow: 0 7px 16px rgba(15,23,42,.24), 0 0 0 1px rgba(15,23,42,.14);
        background: var(--pin-color, #0f172a);
    }
    .sr-custom-mini-pin {
        width: 13px;
        height: 13px;
        border-radius: 999px;
        border: 2px solid #fff;
        box-shadow: 0 6px 14px rgba(15,23,42,.25), 0 0 0 1px rgba(15,23,42,.12);
        background: var(--pin-color, #f97316);
    }
    .sr-thumb::before,
    .sr-thumb::after {
        content: "";
        position: absolute;
        border-radius: 999px;
        opacity: .72;
        pointer-events: none;
    }
    .sr-thumb::before {
        width: 86px;
        height: 36px;
        right: 54px;
        top: 34px;
        background: #c8e9b9;
    }
    .sr-thumb::after {
        width: 70px;
        height: 70px;
        left: 42px;
        bottom: -20px;
        background: rgba(234, 179, 8, .16);
    }
    .sr-thumb-road {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
    }
    .sr-thumb-route {
        fill: none;
        stroke: var(--ch-yellow, #facc15);
        stroke-width: 5;
        stroke-linecap: round;
        stroke-linejoin: round;
        filter: drop-shadow(0 2px 3px rgba(146, 64, 14, .18));
    }
    .sr-thumb-route-shadow {
        fill: none;
        stroke: rgba(15, 23, 42, .18);
        stroke-width: 7;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .sr-thumb-pin {
        position: absolute;
        z-index: 2;
        width: 18px;
        height: 18px;
        border-radius: 999px;
        border: 3px solid #fff;
        box-shadow: 0 7px 16px rgba(15,23,42,.24), 0 0 0 1px rgba(15,23,42,.14);
    }
    .sr-thumb-pin.pickup {
        left: 24px;
        bottom: 26px;
        background: #16a34a;
    }
    .sr-thumb-pin.destination {
        right: 24px;
        top: 30px;
        background: #0f172a;
    }
    .sr-thumb-name {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 4;
        color: var(--ink);
        font-size: 12px;
        font-weight: 800;
        background: rgba(255,255,255,.88);
        border-top: 1px solid var(--hairline);
        padding: 9px 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sr-thumb-icon {
        display: none;
    }

    /* Card body */
    .sr-body { padding: 16px; display: flex; flex-direction: column; gap: 10px; }

    /* Status + badges row */
    .sr-badges { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .sr-badge-active {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 11px; font-weight: 700; padding: 3px 9px;
        border-radius: var(--r-pill);
        border: 1px solid #bbf7d0; background: #f0fdf4; color: #15803d;
    }
    .sr-badge-inactive {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 11px; font-weight: 700; padding: 3px 9px;
        border-radius: var(--r-pill);
        border: 1px solid var(--hairline-strong); background: var(--surface-2); color: var(--muted);
    }
    .sr-badge-default {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 11px; font-weight: 700; padding: 3px 9px;
        border-radius: var(--r-pill);
        border: 1px solid var(--ch-yellow-line); background: var(--ch-yellow); color: var(--ch-yellow-ink);
    }

    /* Route title + edit button */
    .sr-title-row {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    }
    .sr-route-title {
        margin: 0;
        font-family: var(--font-display), sans-serif;
        font-size: 17px;
        font-weight: 800;
        color: var(--ink);
        line-height: 1.25;
    }
    .sr-edit-btn {
        display: inline-flex; align-items: center; gap: 5px;
        border: 1px solid var(--hairline-strong); border-radius: var(--r-sm);
        background: var(--surface-2); color: var(--ink-2);
        font-size: 12px; font-weight: 700; padding: 5px 10px;
        text-decoration: none; white-space: nowrap; flex-shrink: 0;
        transition: background .15s, border-color .15s;
    }
    .sr-edit-btn:hover { background: var(--canvas); border-color: var(--muted-2); }

    /* 4-col KV grid */
    .sr-kv-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }
    .sr-kv { display: flex; flex-direction: column; gap: 2px; }
    .sr-kv-label {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: var(--muted-2);
    }
    .sr-kv-value {
        font-size: 13px; font-weight: 700; color: var(--ink);
        font-family: var(--font-mono, monospace);
    }

    /* Footer row */
    .sr-card-footer {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; flex-wrap: wrap;
        border-top: 1px solid var(--hairline); padding-top: 10px;
        margin-top: 2px;
    }
    .sr-footer-meta {
        font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 5px;
    }
    .sr-footer-actions { display: flex; align-items: center; gap: 7px; }

    .sr-use-btn {
        display: inline-flex; align-items: center; gap: 5px;
        border: 1px solid var(--hairline-strong); border-radius: var(--r-sm);
        background: var(--surface-2); color: var(--ink-2);
        font-size: 12px; font-weight: 700; padding: 6px 11px;
        text-decoration: none; transition: background .15s;
    }
    .sr-use-btn:hover { background: var(--canvas); }

    .sr-delete-btn {
        display: inline-flex; align-items: center; gap: 5px;
        border: 1px solid #fecaca; border-radius: var(--r-sm);
        background: var(--danger-soft); color: var(--danger);
        font-size: 12px; font-weight: 700; padding: 6px 10px;
        cursor: pointer; transition: background .15s;
    }
    .sr-delete-btn:hover { background: #fee2e2; }

    /* Empty state */
    .sr-empty {
        border: 1px dashed var(--hairline-strong);
        border-radius: var(--r-md);
        background: var(--canvas);
        padding: 56px 24px;
        text-align: center;
    }
    .sr-empty-icon { font-size: 38px; color: var(--muted-2); margin-bottom: 14px; display: block; }
    .sr-empty-title { font-size: 16px; font-weight: 700; color: var(--ink-3); margin: 0 0 6px; font-family: var(--font-display), sans-serif; }
    .sr-empty-copy { margin: 0 0 20px; font-size: 13px; color: var(--muted); }

    /* Search */
    #srSearchEmpty { display: none; margin-top: 12px; }
    .sr-search-wrap { position: relative; max-width: 420px; margin-bottom: 16px; }
    .sr-search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted-2); font-size: 12px; pointer-events: none; }
    .sr-search-wrap input {
        width: 100%; box-sizing: border-box;
        border-radius: var(--r-sm); border: 1px solid var(--hairline-strong);
        background: var(--surface-2); color: var(--ink);
        padding: 9px 12px 9px 34px; font-size: 13px;
        font-family: var(--font-ui), sans-serif;
        outline: none; transition: border-color .15s, background .15s;
    }
    .sr-search-wrap input:focus { border-color: var(--muted); background: var(--surface); }
</style>

{{-- Page header --}}
<div style="padding:20px 28px 0">
    <div style="font-size:11px;font-weight:800;color:var(--muted);letter-spacing:.06em;text-transform:uppercase">Saved Routes</div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-top:6px;flex-wrap:wrap">
        <div>
            <h1 style="margin:0;font-family:var(--font-display);font-size:28px;font-weight:800">My Routes</h1>
            <p style="margin:4px 0 0;color:var(--muted);font-size:13px">Reusable templates for daily commutes.</p>
        </div>
        <div>
            <a href="{{ route('saved-routes.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Create Saved Route
            </a>
        </div>
    </div>
</div>

{{-- Card grid area --}}
<div style="padding:20px 28px 28px">

    @if($savedRoutes->isEmpty())
        <div class="sr-empty">
            <i class="fa-solid fa-route sr-empty-icon"></i>
            <p class="sr-empty-title">No saved routes yet</p>
            <p class="sr-empty-copy">Save a route to reuse it across multiple trips.</p>
            <a href="{{ route('saved-routes.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Create your first route
            </a>
        </div>
    @else
        @if(!$savedRoutes->isEmpty())
            <div class="sr-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="srSearchInput" type="search" placeholder="Search route name..." autocomplete="off">
            </div>
        @endif

        <div class="sr-grid" id="srGrid">
            @foreach($savedRoutes as $savedRoute)
                @php
                    $customPreviewPoints = $savedRoute->passengerStops
                        ->where('is_active', true)
                        ->flatMap(function ($stop) use ($savedRoute) {
                            $points = [];

                            $hasCustomPickup = $stop->pickup_latitude
                                && $stop->pickup_longitude
                                && (
                                    (string) $stop->pickup_latitude !== (string) $savedRoute->point_a_latitude
                                    || (string) $stop->pickup_longitude !== (string) $savedRoute->point_a_longitude
                                );

                            $hasCustomDropoff = $stop->dropoff_latitude
                                && $stop->dropoff_longitude
                                && (
                                    (string) $stop->dropoff_latitude !== (string) $savedRoute->point_b_latitude
                                    || (string) $stop->dropoff_longitude !== (string) $savedRoute->point_b_longitude
                                );

                            if ($hasCustomPickup) {
                                $points[] = [
                                    'type' => 'pickup',
                                    'lat' => $stop->pickup_latitude,
                                    'lng' => $stop->pickup_longitude,
                                ];
                            }

                            if ($hasCustomDropoff) {
                                $points[] = [
                                    'type' => 'dropoff',
                                    'lat' => $stop->dropoff_latitude,
                                    'lng' => $stop->dropoff_longitude,
                                ];
                            }

                            return $points;
                        })
                        ->values();
                @endphp
                <article class="card" style="padding:0;overflow:hidden;transition:transform .18s,box-shadow .18s,border-color .18s"
                         data-route-name="{{ strtolower($savedRoute->route_name ?? 'untitled route') }}"
                         data-point-a-lat="{{ $savedRoute->point_a_latitude }}"
                         data-point-a-lng="{{ $savedRoute->point_a_longitude }}"
                         data-point-b-lat="{{ $savedRoute->point_b_latitude }}"
                         data-point-b-lng="{{ $savedRoute->point_b_longitude }}"
                         data-custom-points='@json($customPreviewPoints)'>

                    {{-- Map thumbnail placeholder --}}
                    <div class="sr-thumb" aria-label="Route preview">
                        <div class="sr-thumb-map" data-route-map></div>
                        <svg class="sr-thumb-road" viewBox="0 0 360 150" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M0 98 C68 96 98 92 138 98 C184 105 202 88 236 76 C284 60 304 58 360 26" stroke="rgba(255,255,255,.96)" stroke-width="18" fill="none" />
                            <path d="M0 58 C72 62 104 55 145 54 C188 52 214 62 254 62 C306 62 322 50 360 42" stroke="rgba(255,255,255,.86)" stroke-width="10" fill="none" />
                            <path class="sr-thumb-route-shadow" d="M32 114 C80 112 110 104 138 91 C170 76 190 83 216 72 C250 58 274 52 330 36" />
                            <path class="sr-thumb-route" d="M32 114 C80 112 110 104 138 91 C170 76 190 83 216 72 C250 58 274 52 330 36" />
                        </svg>
                        <span class="sr-thumb-pin pickup"></span>
                        <span class="sr-thumb-pin destination"></span>
                        <span class="sr-thumb-name">{{ $savedRoute->route_name ?? 'Untitled Route' }}</span>
                    </div>

                    {{-- Card body --}}
                    <div class="sr-body">

                        {{-- Status + default badge --}}
                        <div class="sr-badges">
                            @if($savedRoute->is_active ?? true)
                                <span class="sr-badge-active"><i class="fa-solid fa-circle" style="font-size:7px"></i>Active</span>
                            @else
                                <span class="sr-badge-inactive"><i class="fa-solid fa-circle" style="font-size:7px"></i>Inactive</span>
                            @endif
                            @if($savedRoute->is_default ?? false)
                                <span class="sr-badge-default"><i class="fa-solid fa-star" style="font-size:9px"></i>Default</span>
                            @endif
                        </div>

                        {{-- Title + edit button --}}
                        <div class="sr-title-row">
                            <h2 class="sr-route-title">{{ $savedRoute->route_name ?? 'Untitled Route' }}</h2>
                            <a href="{{ route('saved-routes.edit', $savedRoute) }}" class="sr-edit-btn">
                                <i class="fa-solid fa-pen-to-square"></i>
                                Edit
                            </a>
                        </div>

                        {{-- 4-col KV grid --}}
                        <div class="sr-kv-grid">
                            <div class="sr-kv">
                                <span class="sr-kv-label">Stops</span>
                                <span class="sr-kv-value">{{ 2 + ($savedRoute->passengerStops->where('is_active', true)->count() * 2) }}</span>
                            </div>
                            <div class="sr-kv">
                                <span class="sr-kv-label">Distance</span>
                                <span class="sr-kv-value" data-route-distance>—</span>
                            </div>
                            <div class="sr-kv">
                                <span class="sr-kv-label">Time</span>
                                <span class="sr-kv-value" data-route-time>—</span>
                            </div>
                            <div class="sr-kv">
                                <span class="sr-kv-label">Trip fee</span>
                                <span class="sr-kv-value">RM&nbsp;{{ number_format((float)($savedRoute->default_fare ?? 0), 2) }}</span>
                            </div>
                        </div>

                        {{-- Footer row --}}
                        <div class="sr-card-footer">
                            <span class="sr-footer-meta">
                                <i class="fa-solid fa-bookmark" style="font-size:10px"></i>
                                Saved {{ $savedRoute->created_at ? $savedRoute->created_at->diffForHumans() : '—' }}
                            </span>
                            <div class="sr-footer-actions">
                                <a href="{{ route('trips.create', ['route_id' => $savedRoute->id]) }}" class="sr-use-btn">
                                    <i class="fa-solid fa-arrow-right"></i>
                                    Use in new trip
                                </a>
                                <form
                                    method="POST"
                                    action="{{ route('saved-routes.destroy', $savedRoute) }}"
                                    onsubmit="return confirm('Delete this route? All trips using this route may also be affected.');"
                                    style="display:contents"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sr-delete-btn" title="Delete route">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>{{-- /.sr-body --}}
                </article>
            @endforeach
        </div>

        <div id="srSearchEmpty" class="sr-empty" style="margin-top:12px">
            <i class="fa-solid fa-magnifying-glass sr-empty-icon"></i>
            <p class="sr-empty-title">No routes found</p>
            <p class="sr-empty-copy">Try a different route name.</p>
        </div>

        <div style="margin-top:14px">
            {{ $savedRoutes->links() }}
        </div>
    @endif

</div>

<script>
    (function () {
        var searchInput = document.getElementById('srSearchInput');
        var cards = Array.prototype.slice.call(document.querySelectorAll('#srGrid [data-route-name]'));
        var emptyMsg = document.getElementById('srSearchEmpty');

        function normalize(text) { return (text || '').toLowerCase().trim(); }

        function applyFilter() {
            var query = normalize(searchInput.value);
            var visible = 0;
            cards.forEach(function (card) {
                var match = normalize(card.getAttribute('data-route-name')).indexOf(query) !== -1;
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (emptyMsg) emptyMsg.style.display = visible ? 'none' : 'block';
        }

        if (searchInput) searchInput.addEventListener('input', applyFilter);

        function toNumber(value) {
            var parsed = Number.parseFloat(String(value || '').trim());
            return Number.isFinite(parsed) ? parsed : null;
        }

        function formatDistance(meters) {
            if (!Number.isFinite(meters)) return '—';
            var km = meters / 1000;
            return km >= 10 ? km.toFixed(0) + ' km' : km.toFixed(1) + ' km';
        }

        function formatDuration(seconds) {
            if (!Number.isFinite(seconds)) return '—';
            var minutes = Math.max(1, Math.round(seconds / 60));
            if (minutes >= 60) {
                var hours = Math.floor(minutes / 60);
                var rest = minutes % 60;
                return rest ? hours + 'h ' + rest + 'm' : hours + 'h';
            }
            return minutes + ' min';
        }

        function haversineMeters(aLat, aLng, bLat, bLng) {
            var radius = 6371000;
            var toRad = function (deg) { return deg * Math.PI / 180; };
            var dLat = toRad(bLat - aLat);
            var dLng = toRad(bLng - aLng);
            var lat1 = toRad(aLat);
            var lat2 = toRad(bLat);
            var h = Math.sin(dLat / 2) * Math.sin(dLat / 2)
                + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
            return 2 * radius * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
        }

        function updateRouteStats(card, distanceMeters, durationSeconds) {
            var distanceEl = card.querySelector('[data-route-distance]');
            var timeEl = card.querySelector('[data-route-time]');
            if (distanceEl) distanceEl.textContent = formatDistance(distanceMeters);
            if (timeEl) timeEl.textContent = formatDuration(durationSeconds);
        }

        function fallbackRoute(card, points) {
            var distance = 0;
            for (var index = 1; index < points.length; index++) {
                distance += haversineMeters(points[index - 1][0], points[index - 1][1], points[index][0], points[index][1]) * 1.25;
            }
            var duration = distance / (32 * 1000 / 3600);
            updateRouteStats(card, distance, duration);
            return points;
        }

        function renderMiniMap(card, coordinates, markers) {
            if (!window.L) return;
            var mapEl = card.querySelector('[data-route-map]');
            if (!mapEl || mapEl.dataset.ready === '1') return;
            mapEl.dataset.ready = '1';

            var map = window.L.map(mapEl, {
                zoomControl: false,
                attributionControl: false,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                tap: false,
            });

            window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                crossOrigin: true,
            }).addTo(map);

            window.L.polyline(coordinates, {
                color: '#facc15',
                weight: 5,
                opacity: .95,
                lineCap: 'round',
                lineJoin: 'round',
            }).addTo(map);
            var boundsPoints = coordinates.slice();
            (markers || []).forEach(function (marker) {
                boundsPoints.push(marker.point);
            });

            window.L.circleMarker(coordinates[0], {
                radius: 6,
                color: '#fff',
                weight: 2,
                fillColor: '#16a34a',
                fillOpacity: 1,
            }).addTo(map);
            window.L.circleMarker(coordinates[coordinates.length - 1], {
                radius: 6,
                color: '#fff',
                weight: 2,
                fillColor: '#0f172a',
                fillOpacity: 1,
            }).addTo(map);
            (markers || []).forEach(function (marker) {
                window.L.marker(marker.point, {
                    interactive: false,
                    icon: window.L.divIcon({
                        className: '',
                        html: '<div class="sr-custom-mini-pin" style="--pin-color:' + (marker.type === 'pickup' ? '#22c55e' : '#f97316') + '"></div>',
                        iconSize: [13, 13],
                        iconAnchor: [6, 6],
                    }),
                }).addTo(map);
            });
            map.fitBounds(window.L.latLngBounds(boundsPoints), { padding: [24, 24], animate: false });
            card.querySelector('.sr-thumb')?.classList.add('has-live-map');
            setTimeout(function () { map.invalidateSize(false); }, 80);
        }

        function customMarkers(card) {
            var raw = card.getAttribute('data-custom-points') || '[]';
            var items = [];
            try { items = JSON.parse(raw); } catch (error) { items = []; }
            return (Array.isArray(items) ? items : [])
                .map(function (item) {
                    var lat = toNumber(item.lat);
                    var lng = toNumber(item.lng);
                    if (lat === null || lng === null) return null;
                    return { type: item.type === 'pickup' ? 'pickup' : 'dropoff', point: [lat, lng] };
                })
                .filter(Boolean);
        }

        function initRouteCard(card) {
            var aLat = toNumber(card.dataset.pointALat);
            var aLng = toNumber(card.dataset.pointALng);
            var bLat = toNumber(card.dataset.pointBLat);
            var bLng = toNumber(card.dataset.pointBLng);
            if ([aLat, aLng, bLat, bLng].some(function (value) { return value === null; })) return;

            var markers = customMarkers(card);
            var routePoints = [[aLat, aLng]]
                .concat(markers.map(function (marker) { return marker.point; }))
                .concat([[bLat, bLng]]);
            var url = 'https://router.project-osrm.org/route/v1/driving/'
                + routePoints.map(function (point) {
                    return encodeURIComponent(point[1] + ',' + point[0]);
                }).join(';')
                + '?overview=full&geometries=geojson';

            fetch(url)
                .then(function (response) {
                    if (!response.ok) throw new Error('route');
                    return response.json();
                })
                .then(function (payload) {
                    var route = payload && payload.routes && payload.routes[0];
                    if (!route || !route.geometry || !Array.isArray(route.geometry.coordinates)) {
                        throw new Error('geometry');
                    }
                    updateRouteStats(card, Number(route.distance), Number(route.duration));
                    var coordinates = route.geometry.coordinates
                        .map(function (pair) { return [Number(pair[1]), Number(pair[0])]; })
                        .filter(function (pair) { return Number.isFinite(pair[0]) && Number.isFinite(pair[1]); });
                    renderMiniMap(card, coordinates.length ? coordinates : routePoints, markers);
                })
                .catch(function () {
                    renderMiniMap(card, fallbackRoute(card, routePoints), markers);
                });
        }

        cards.forEach(initRouteCard);
    })();
</script>
@endsection
