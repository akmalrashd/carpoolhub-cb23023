@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <style>
        /* ── Explore Search Page ────────────────────────────────────────── */
        .xs-page { display: grid; gap: 16px; }

        /* Section card */
        .xs-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 18px 16px;
            box-shadow: var(--shadow-2);
        }

        /* Page heading */
        .xs-title {
            margin: 0;
            font-family: var(--font-display);
            font-size: 26px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.1;
        }
        .xs-subtitle {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        /* Primary destination box */
        .xs-dest-box {
            margin-top: 14px;
            border: 1.5px solid var(--ch-yellow-line);
            border-radius: var(--r-md);
            background: var(--ch-yellow-tint);
            padding: 12px;
            display: grid;
            gap: 8px;
        }
        .xs-dest-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--warning-ink);
        }
        .xs-dest-input {
            width: 100%;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface);
            color: var(--ink);
            padding: 11px 13px;
            font-size: 15px;
            font-family: var(--font-ui);
            outline: none;
        }
        .xs-dest-input:focus { border-color: var(--ch-yellow-deep); }

        /* Secondary field grid */
        .xs-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            margin-top: 12px;
        }
        .xs-field { display: grid; gap: 4px; }
        .xs-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
        }
        .xs-input {
            width: 100%;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            color: var(--ink);
            padding: 9px 11px;
            font-size: 13px;
            font-family: var(--font-ui);
            outline: none;
        }
        .xs-input:focus { border-color: var(--ch-yellow-deep); background: var(--surface); }

        /* Autocomplete dropdown */
        .xs-auto-wrap { position: relative; }
        .xs-suggest-list {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 30;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface);
            box-shadow: var(--shadow-3);
            max-height: 220px;
            overflow: auto;
            display: none;
        }
        .xs-suggest-list.show { display: block; }
        .xs-suggest-btn {
            width: 100%;
            border: 0;
            border-bottom: 1px solid var(--hairline);
            background: var(--surface);
            color: var(--ink);
            padding: 9px 12px;
            text-align: left;
            font-size: 12px;
            font-family: var(--font-ui);
            cursor: pointer;
            display: grid;
            gap: 2px;
        }
        .xs-suggest-btn:last-child { border-bottom: 0; }
        .xs-suggest-btn:hover { background: var(--surface-2); }
        .xs-suggest-main { font-weight: 700; color: var(--ink); }
        .xs-suggest-sub  { color: var(--muted); font-size: 11px; line-height: 1.25; }

        /* Map card */
        .xs-map-card {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 12px;
            display: grid;
            gap: 10px;
            margin-top: 12px;
        }
        .xs-map-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .xs-map-title {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
        }
        .xs-map-hint {
            margin: 2px 0 0;
            font-size: 12px;
            color: var(--muted);
        }
        .xs-map-targets { display: inline-flex; gap: 6px; flex-wrap: wrap; }
        .xs-map-target-btn {
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-pill);
            background: var(--surface);
            color: var(--ink-3);
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            font-family: var(--font-ui);
            transition: border-color .15s ease, background .15s ease, color .15s ease;
        }
        .xs-map-target-btn.active {
            border-color: var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            color: var(--warning-ink);
        }
        .xs-map-status {
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            background: var(--surface);
            color: var(--ink-3);
            font-size: 12px;
            padding: 8px 11px;
            line-height: 1.4;
        }
        #exploreSearchMap {
            width: 100%;
            height: 260px;
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            overflow: hidden;
            background: var(--surface);
        }

        /* Form actions */
        .xs-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        /* Recent / suggested sections */
        .xs-section-title {
            margin: 0 0 10px;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
        }
        .xs-tag-list { display: flex; gap: 7px; flex-wrap: wrap; }
        .xs-tag {
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-pill);
            background: var(--surface-2);
            color: var(--ink-3);
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            transition: border-color .15s, background .15s, color .15s;
        }
        .xs-tag:hover {
            border-color: var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            color: var(--warning-ink);
        }
        .xs-suggested-list { display: grid; gap: 8px; margin: 0; }
        .xs-suggested-item {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface);
            color: var(--ink-3);
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            padding: 11px 13px;
            text-decoration: none;
            box-shadow: var(--shadow-1);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: border-color .15s, background .15s, color .15s;
        }
        .xs-suggested-item:hover {
            border-color: var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            color: var(--warning-ink);
        }
        .xs-empty { color: var(--muted-2); font-size: 13px; margin: 0; }

        @media (min-width: 768px) {
            .xs-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .xs-suggested-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

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

    <script>
        (() => {
            const destinationInput     = document.getElementById('search_destination');
            const pickupInput          = document.getElementById('search_pickup');
            const destinationList      = document.getElementById('destinationSuggestList');
            const pickupList           = document.getElementById('pickupSuggestList');
            const statusEl             = document.getElementById('searchMapStatus');
            const mapEl                = document.getElementById('exploreSearchMap');
            const targetDestinationBtn = document.getElementById('targetDestinationBtn');
            const targetPickupBtn      = document.getElementById('targetPickupBtn');
            const centerLatInput       = document.getElementById('search_center_lat');
            const centerLngInput       = document.getElementById('search_center_lng');
            const pickupLatInput       = document.getElementById('search_pickup_lat');
            const pickupLngInput       = document.getElementById('search_pickup_lng');
            const destinationLatInput  = document.getElementById('search_destination_lat');
            const destinationLngInput  = document.getElementById('search_destination_lng');

            const debounce = (fn, wait = 350) => {
                let timer = null;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn(...args), wait);
                };
            };

            const renderSuggestions = (listEl, items, onPick) => {
                if (!listEl) return;
                if (!items.length) {
                    listEl.classList.remove('show');
                    listEl.innerHTML = '';
                    return;
                }
                listEl.innerHTML = items.map((item, index) => {
                    const main = String(item.display_name || '').split(',')[0] || 'Select location';
                    const sub  = String(item.display_name || '').trim();
                    return `
                        <button type="button" class="xs-suggest-btn" data-index="${index}">
                            <span class="xs-suggest-main">${main.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>
                            <span class="xs-suggest-sub">${sub.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>
                        </button>
                    `;
                }).join('');
                listEl.classList.add('show');

                Array.from(listEl.querySelectorAll('.xs-suggest-btn')).forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const idx    = Number.parseInt(btn.getAttribute('data-index') || '-1', 10);
                        const picked = items[idx];
                        if (!picked) return;
                        onPick(picked);
                        listEl.classList.remove('show');
                        listEl.innerHTML = '';
                    });
                });
            };

            const fetchSuggestions = async (query) => {
                const q = String(query || '').trim();
                if (q.length < 1) return [];
                const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=6&addressdetails=1&q=' + encodeURIComponent(q);
                const response = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } });
                if (!response.ok) return [];
                const payload = await response.json();
                return Array.isArray(payload) ? payload : [];
            };

            const wireAutocomplete = (inputEl, listEl, onPick) => {
                if (!inputEl || !listEl) return;
                const run = debounce(async () => {
                    try {
                        const items = await fetchSuggestions(inputEl.value);
                        renderSuggestions(listEl, items, onPick);
                    } catch (_e) {
                        renderSuggestions(listEl, [], onPick);
                    }
                }, 260);
                inputEl.addEventListener('input', run);
                inputEl.addEventListener('focus', run);
            };

            const clearTargetCoordinate = (target) => {
                const isDestination = target === 'destination';
                if (isDestination) {
                    if (destinationLatInput) destinationLatInput.value = '';
                    if (destinationLngInput) destinationLngInput.value = '';
                    if (destinationMarker) { map.removeLayer(destinationMarker); destinationMarker = null; }
                } else {
                    if (pickupLatInput) pickupLatInput.value = '';
                    if (pickupLngInput) pickupLngInput.value = '';
                    if (pickupMarker) { map.removeLayer(pickupMarker); pickupMarker = null; }
                }
            };

            destinationInput?.addEventListener('input', () => clearTargetCoordinate('destination'));
            pickupInput?.addEventListener('input',      () => clearTargetCoordinate('pickup'));

            document.addEventListener('click', (event) => {
                if (!(event.target instanceof Element)) return;
                if (destinationList && !destinationList.parentElement?.contains(event.target)) {
                    destinationList.classList.remove('show');
                }
                if (pickupList && !pickupList.parentElement?.contains(event.target)) {
                    pickupList.classList.remove('show');
                }
            });

            if (!mapEl || typeof window.L === 'undefined') return;

            let activeTarget     = 'destination';
            let destinationMarker = null;
            let pickupMarker      = null;

            const setStatus    = (text) => { if (statusEl) statusEl.textContent = text; };
            const updateTargetUI = () => {
                if (targetDestinationBtn) targetDestinationBtn.classList.toggle('active', activeTarget === 'destination');
                if (targetPickupBtn)      targetPickupBtn.classList.toggle('active',      activeTarget === 'pickup');
                setStatus(activeTarget === 'destination'
                    ? 'Adjust mode: Destination. Tap the map to set destination pin.'
                    : 'Adjust mode: Pickup. Tap the map to set pickup pin.');
            };

            if (targetDestinationBtn) {
                targetDestinationBtn.addEventListener('click', () => { activeTarget = 'destination'; updateTargetUI(); });
            }
            if (targetPickupBtn) {
                targetPickupBtn.addEventListener('click', () => { activeTarget = 'pickup'; updateTargetUI(); });
            }

            const map = window.L.map(mapEl, { zoomControl: true, attributionControl: false })
                .setView([3.139, 101.6869], 6);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            const reverseGeocode = async (lat, lng) => {
                const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);
                const response = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } });
                if (!response.ok) return null;
                const payload = await response.json();
                return payload?.display_name || null;
            };

            const setPin = (target, lat, lng, label = null) => {
                const isDestination = target === 'destination';
                const fillColor     = isDestination ? '#dc2626' : '#16a34a';
                const inputEl       = isDestination ? destinationInput : pickupInput;

                if (isDestination && destinationMarker) map.removeLayer(destinationMarker);
                if (!isDestination && pickupMarker)     map.removeLayer(pickupMarker);

                const marker = window.L.circleMarker([lat, lng], {
                    radius: 7, color: '#fff', weight: 2, fillColor, fillOpacity: 1,
                }).addTo(map);

                if (isDestination) destinationMarker = marker;
                else               pickupMarker      = marker;

                if (inputEl && label) inputEl.value = label;

                if (isDestination) {
                    if (destinationLatInput) destinationLatInput.value = String(lat);
                    if (destinationLngInput) destinationLngInput.value = String(lng);
                } else {
                    if (pickupLatInput) pickupLatInput.value = String(lat);
                    if (pickupLngInput) pickupLngInput.value = String(lng);
                }
                if (centerLatInput) centerLatInput.value = String(lat);
                if (centerLngInput) centerLngInput.value = String(lng);
            };

            const restorePin = (target, latValue, lngValue, label = null) => {
                const lat = Number.parseFloat(String(latValue || ''));
                const lng = Number.parseFloat(String(lngValue || ''));
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false;
                setPin(target, lat, lng, label);
                return true;
            };

            wireAutocomplete(destinationInput, destinationList, (picked) => {
                const label = String(picked.display_name || '').trim();
                const lat   = Number.parseFloat(String(picked.lat || ''));
                const lng   = Number.parseFloat(String(picked.lon || ''));
                if (destinationInput) destinationInput.value = label;
                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    setPin('destination', lat, lng, label);
                    map.setView([lat, lng], Math.max(map.getZoom(), 13));
                }
                setStatus('Destination selected from suggestions.');
            });

            wireAutocomplete(pickupInput, pickupList, (picked) => {
                const label = String(picked.display_name || '').trim();
                const lat   = Number.parseFloat(String(picked.lat || ''));
                const lng   = Number.parseFloat(String(picked.lon || ''));
                if (pickupInput) pickupInput.value = label;
                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    setPin('pickup', lat, lng, label);
                    map.setView([lat, lng], Math.max(map.getZoom(), 13));
                }
                setStatus('Pickup selected from suggestions.');
            });

            const restoredDestination = restorePin('destination', destinationLatInput?.value, destinationLngInput?.value, destinationInput?.value);
            const restoredPickup      = restorePin('pickup',      pickupLatInput?.value,      pickupLngInput?.value,      pickupInput?.value);
            if (restoredDestination || restoredPickup) {
                const points = [];
                if (destinationMarker) points.push(destinationMarker.getLatLng());
                if (pickupMarker)      points.push(pickupMarker.getLatLng());
                if (points.length) {
                    map.fitBounds(window.L.latLngBounds(points), { padding: [28, 28], maxZoom: 14 });
                }
                setStatus(restoredDestination && restoredPickup
                    ? 'Pickup and destination pins restored.'
                    : (restoredDestination ? 'Destination pin restored.' : 'Pickup pin restored.'));
            }

            map.on('click', async (event) => {
                const lat = Number(event.latlng?.lat);
                const lng = Number(event.latlng?.lng);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                setStatus(`Loading ${activeTarget} location...`);
                try {
                    const label = await reverseGeocode(lat, lng);
                    if (label) {
                        setPin(activeTarget, lat, lng, label);
                        setStatus(activeTarget === 'destination'
                            ? 'Destination pin updated from map.'
                            : 'Pickup pin updated from map.');
                    } else {
                        setStatus('Pin saved, but address could not be resolved.');
                    }
                } catch (_e) {
                    setStatus('Pin saved, but address lookup failed.');
                }
            });

            updateTargetUI();
        })();
    </script>
@endsection
