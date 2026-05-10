@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <style>
        .explore-search-page { display: grid; gap: 12px; }
        .explore-search-card { background: #fff; border: 1px solid #dbe2ea; border-radius: 16px; padding: 14px; }
        .explore-search-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .explore-search-title { margin: 0; font-family: Poppins, sans-serif; font-size: 28px; color: #0f172a; line-height: 1.1; }
        .explore-search-subtitle { margin: 6px 0 0; color: #64748b; font-size: 14px; }

        .search-main {
            border: 1px solid #fde68a;
            border-radius: 14px;
            background: #fffbeb;
            padding: 10px;
            display: grid;
            gap: 8px;
        }
        .search-main-label { color: #92400e; font-size: 11px; text-transform: uppercase; letter-spacing: .03em; font-weight: 700; }
        .search-main-input {
            width: 100%;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            padding: 10px 12px;
            font-size: 15px;
            outline: none;
        }

        .search-grid { display: grid; gap: 8px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .search-field { display: grid; gap: 4px; }
        .search-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
        .search-input {
            width: 100%;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #f8fafc;
            color: #0f172a;
            padding: 8px 10px;
            font-size: 13px;
            outline: none;
        }

        .search-auto-wrap { position: relative; }
        .search-suggest-list {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 30;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
            max-height: 220px;
            overflow: auto;
            display: none;
        }
        .search-suggest-list.show { display: block; }
        .search-suggest-btn {
            width: 100%;
            border: 0;
            border-bottom: 1px solid #eef2f7;
            background: #fff;
            color: #0f172a;
            padding: 9px 10px;
            text-align: left;
            font-size: 12px;
            cursor: pointer;
            display: grid;
            gap: 2px;
        }
        .search-suggest-btn:last-child { border-bottom: 0; }
        .search-suggest-btn:hover { background: #f8fafc; }
        .search-suggest-main { font-weight: 700; color: #0f172a; }
        .search-suggest-sub { color: #64748b; font-size: 11px; line-height: 1.25; }

        .search-map-card {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px;
            display: grid;
            gap: 8px;
        }
        .search-map-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .search-map-title { margin: 0; color: #0f172a; font-size: 13px; font-weight: 700; }
        .search-map-hint { margin: 0; color: #64748b; font-size: 12px; }
        .search-map-targets { display: inline-flex; gap: 6px; flex-wrap: wrap; }
        .search-map-target-btn {
            border: 1px solid #dbe2ea;
            border-radius: 999px;
            background: #fff;
            color: #334155;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .search-map-target-btn.active {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }
        .search-map-status {
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #fff;
            color: #334155;
            font-size: 12px;
            padding: 7px 9px;
            line-height: 1.35;
        }
        #exploreSearchMap {
            width: 100%;
            height: 260px;
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .search-actions { display: inline-flex; gap: 6px; flex-wrap: wrap; }
        .search-btn {
            border: 1px solid #dbe2ea;
            border-radius: 9px;
            background: #fff;
            color: #0f172a;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .search-btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }

        .search-section-title { margin: 0 0 8px; color: #0f172a; font-size: 15px; font-weight: 700; }
        .search-tag-list { display: flex; gap: 7px; flex-wrap: wrap; }
        .search-tag {
            border: 1px solid #dbe2ea;
            border-radius: 999px;
            background: #fff;
            color: #334155;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 10px;
        }
        .search-suggested-list { display: grid; gap: 8px; margin: 0; }
        .search-suggested-item {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
            padding: 10px 12px;
            text-decoration: none;
            transition: border-color .16s ease, background-color .16s ease, color .16s ease;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .search-suggested-item:hover {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }
        .search-empty { color: #94a3b8; font-size: 13px; }

        @media (min-width: 768px) {
            .search-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .search-suggested-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

    <div class="explore-search-page">
        <section class="explore-search-card">
            <div class="explore-search-head">
                <div>
                    <h1 class="explore-search-title">Cari Destinasi</h1>
                    <p class="explore-search-subtitle">Cari trip awam mengikut destinasi, tarikh, dan pilihan tempat duduk.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('explore.index') }}" style="margin-top:10px; display:grid; gap:10px;" id="exploreSearchForm">
                <div class="search-main">
                    <label class="search-main-label" for="search_destination">Ke mana?</label>
                    <div class="search-auto-wrap">
                        <input
                            id="search_destination"
                            type="text"
                            name="destination"
                            class="search-main-input"
                            value="{{ old('destination', $prefill) }}"
                            placeholder="Cari destinasi"
                            autocomplete="off"
                            autofocus
                        >
                        <div class="search-suggest-list" id="destinationSuggestList"></div>
                    </div>
                </div>

                <div class="search-grid">
                    <div class="search-field">
                        <label class="search-label" for="search_date">Tarikh</label>
                        <input id="search_date" class="search-input" type="date" name="date" value="{{ request('date') }}">
                    </div>
                    <div class="search-field">
                        <label class="search-label" for="search_pickup">Kawasan pickup (pilihan)</label>
                        <div class="search-auto-wrap">
                            <input id="search_pickup" class="search-input" type="text" name="pickup" value="{{ request('pickup') }}" placeholder="Kawasan pickup" autocomplete="off">
                            <div class="search-suggest-list" id="pickupSuggestList"></div>
                        </div>
                    </div>
                    <div class="search-field">
                        <label class="search-label" for="search_seats">Tempat Duduk</label>
                        <select id="search_seats" class="search-input" name="seats">
                            <option value="">Mana-mana tempat</option>
                            <option value="1" {{ request('seats') === '1' ? 'selected' : '' }}>1 tempat</option>
                            <option value="2plus" {{ request('seats') === '2plus' ? 'selected' : '' }}>2+ tempat</option>
                        </select>
                    </div>
                    <div class="search-field">
                        <label class="search-label" for="search_sort">Susun</label>
                        <select id="search_sort" class="search-input" name="sort">
                            <option value="nearest" {{ request('sort', 'nearest') === 'nearest' ? 'selected' : '' }}>Tarikh terdekat</option>
                            <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Tarikh terbaru</option>
                        </select>
                    </div>
                    <div class="search-field">
                        <label class="search-label" for="search_radius_km">Radius (km dari pin)</label>
                        <input id="search_radius_km" class="search-input" type="number" name="radius_km" min="0.5" step="0.5" value="{{ request('radius_km', 5) }}">
                    </div>
                </div>

                <input type="hidden" id="search_center_lat" name="center_lat" value="{{ request('center_lat') }}">
                <input type="hidden" id="search_center_lng" name="center_lng" value="{{ request('center_lng') }}">
                <input type="hidden" id="search_pickup_lat" name="pickup_lat" value="{{ request('pickup_lat') }}">
                <input type="hidden" id="search_pickup_lng" name="pickup_lng" value="{{ request('pickup_lng') }}">
                <input type="hidden" id="search_destination_lat" name="destination_lat" value="{{ request('destination_lat') }}">
                <input type="hidden" id="search_destination_lng" name="destination_lng" value="{{ request('destination_lng') }}">

                <div class="search-map-card">
                    <div class="search-map-head">
                        <p class="search-map-title">Pin Lokasi pada Peta</p>
                        <p class="search-map-hint">Pilih sasaran, kemudian ketuk peta untuk laras pin.</p>
                    </div>
                    <div class="search-map-targets">
                        <button type="button" class="search-map-target-btn active" id="targetDestinationBtn">Laras Destinasi</button>
                        <button type="button" class="search-map-target-btn" id="targetPickupBtn">Laras Pickup</button>
                    </div>
                    <div class="search-map-status" id="searchMapStatus">Tiada pin destinasi lagi.</div>
                    <div id="exploreSearchMap"></div>
                </div>

                <div class="search-actions">
                    <button type="submit" class="search-btn primary"><i class="fa-solid fa-magnifying-glass"></i>Cari Trip</button>
                    <a href="{{ route('explore.search') }}" class="search-btn"><i class="fa-solid fa-rotate-left"></i>Kosongkan Semua</a>
                </div>
            </form>
        </section>

        <section class="explore-search-card">
            <h2 class="search-section-title">Carian Terkini</h2>
            @if($recentSearches->isEmpty())
                <p class="search-empty">Tiada carian terkini lagi.</p>
            @else
                <div class="search-tag-list">
                    @foreach($recentSearches as $item)
                        <a href="{{ route('explore.index', ['destination' => $item]) }}" class="search-tag">{{ $item }}</a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="explore-search-card">
            <h2 class="search-section-title">Destinasi Dicadangkan</h2>
            @if($suggestedDestinations->isEmpty())
                <p class="search-empty">Tiada cadangan destinasi buat masa ini.</p>
            @else
                <div class="search-suggested-list">
                    @foreach($suggestedDestinations as $item)
                        <a href="{{ route('explore.index', ['destination' => $item]) }}" class="search-suggested-item">{{ $item }}</a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <script>
        (() => {
            const destinationInput = document.getElementById('search_destination');
            const pickupInput = document.getElementById('search_pickup');
            const destinationList = document.getElementById('destinationSuggestList');
            const pickupList = document.getElementById('pickupSuggestList');
            const statusEl = document.getElementById('searchMapStatus');
            const mapEl = document.getElementById('exploreSearchMap');
            const targetDestinationBtn = document.getElementById('targetDestinationBtn');
            const targetPickupBtn = document.getElementById('targetPickupBtn');
            const centerLatInput = document.getElementById('search_center_lat');
            const centerLngInput = document.getElementById('search_center_lng');
            const pickupLatInput = document.getElementById('search_pickup_lat');
            const pickupLngInput = document.getElementById('search_pickup_lng');
            const destinationLatInput = document.getElementById('search_destination_lat');
            const destinationLngInput = document.getElementById('search_destination_lng');

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
                    const sub = String(item.display_name || '').trim();
                    return `
                        <button type="button" class="search-suggest-btn" data-index="${index}">
                            <span class="search-suggest-main">${main.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>
                            <span class="search-suggest-sub">${sub.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>
                        </button>
                    `;
                }).join('');
                listEl.classList.add('show');

                Array.from(listEl.querySelectorAll('.search-suggest-btn')).forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const idx = Number.parseInt(btn.getAttribute('data-index') || '-1', 10);
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
                const response = await fetch(url, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                });
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
                    if (destinationMarker) {
                        map.removeLayer(destinationMarker);
                        destinationMarker = null;
                    }
                } else {
                    if (pickupLatInput) pickupLatInput.value = '';
                    if (pickupLngInput) pickupLngInput.value = '';
                    if (pickupMarker) {
                        map.removeLayer(pickupMarker);
                        pickupMarker = null;
                    }
                }
            };

            destinationInput?.addEventListener('input', () => clearTargetCoordinate('destination'));
            pickupInput?.addEventListener('input', () => clearTargetCoordinate('pickup'));

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

            let activeTarget = 'destination';
            let destinationMarker = null;
            let pickupMarker = null;

            const setStatus = (text) => {
                if (statusEl) statusEl.textContent = text;
            };
            const updateTargetUI = () => {
                if (targetDestinationBtn) targetDestinationBtn.classList.toggle('active', activeTarget === 'destination');
                if (targetPickupBtn) targetPickupBtn.classList.toggle('active', activeTarget === 'pickup');
                setStatus(activeTarget === 'destination'
                    ? 'Adjust mode: Destination. Tap map to set destination pin.'
                    : 'Adjust mode: Pickup. Tap map to set pickup pin.');
            };
            if (targetDestinationBtn) {
                targetDestinationBtn.addEventListener('click', () => {
                    activeTarget = 'destination';
                    updateTargetUI();
                });
            }
            if (targetPickupBtn) {
                targetPickupBtn.addEventListener('click', () => {
                    activeTarget = 'pickup';
                    updateTargetUI();
                });
            }

            const map = window.L.map(mapEl, {
                zoomControl: true,
                attributionControl: false,
            }).setView([3.139, 101.6869], 6);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);

            const reverseGeocode = async (lat, lng) => {
                const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);
                const response = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } });
                if (!response.ok) return null;
                const payload = await response.json();
                return payload?.display_name || null;
            };

            const setPin = (target, lat, lng, label = null) => {
                const isDestination = target === 'destination';
                const fillColor = isDestination ? '#dc2626' : '#16a34a';
                const inputEl = isDestination ? destinationInput : pickupInput;

                if (isDestination && destinationMarker) map.removeLayer(destinationMarker);
                if (!isDestination && pickupMarker) map.removeLayer(pickupMarker);

                const marker = window.L.circleMarker([lat, lng], {
                    radius: 7,
                    color: '#fff',
                    weight: 2,
                    fillColor,
                    fillOpacity: 1,
                }).addTo(map);

                if (isDestination) destinationMarker = marker;
                else pickupMarker = marker;

                if (inputEl && label) {
                    inputEl.value = label;
                }
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
                const lat = Number.parseFloat(String(picked.lat || ''));
                const lng = Number.parseFloat(String(picked.lon || ''));
                if (destinationInput) destinationInput.value = label;
                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    setPin('destination', lat, lng, label);
                    map.setView([lat, lng], Math.max(map.getZoom(), 13));
                }
                setStatus('Destination selected from suggestions.');
            });

            wireAutocomplete(pickupInput, pickupList, (picked) => {
                const label = String(picked.display_name || '').trim();
                const lat = Number.parseFloat(String(picked.lat || ''));
                const lng = Number.parseFloat(String(picked.lon || ''));
                if (pickupInput) pickupInput.value = label;
                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    setPin('pickup', lat, lng, label);
                    map.setView([lat, lng], Math.max(map.getZoom(), 13));
                }
                setStatus('Pickup selected from suggestions.');
            });

            const restoredDestination = restorePin('destination', destinationLatInput?.value, destinationLngInput?.value, destinationInput?.value);
            const restoredPickup = restorePin('pickup', pickupLatInput?.value, pickupLngInput?.value, pickupInput?.value);
            if (restoredDestination || restoredPickup) {
                const points = [];
                if (destinationMarker) points.push(destinationMarker.getLatLng());
                if (pickupMarker) points.push(pickupMarker.getLatLng());
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

