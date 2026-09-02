/* Extracted from resources/views/explore/search.blade.php — cacheable. */
(() => {
    const destinationInput = document.getElementById('search_destination');
    const pickupInput      = document.getElementById('search_pickup');
    const centerLatInput      = document.getElementById('search_center_lat');
    const centerLngInput      = document.getElementById('search_center_lng');
    const pickupLatInput      = document.getElementById('search_pickup_lat');
    const pickupLngInput      = document.getElementById('search_pickup_lng');
    const destinationLatInput = document.getElementById('search_destination_lat');
    const destinationLngInput = document.getElementById('search_destination_lng');

    const debounce = (fn, wait = 350) => {
        let timer = null;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), wait);
        };
    };

    // Overpass's public server can be considerably slower than Nominatim
    // (occasionally 10s+ under load) — races it against a plain timer so a
    // slow response degrades to "no nearby places" instead of leaving the
    // pin's own address stuck on "Loading..." indefinitely.
    const withTimeout = (promise, ms, fallback) => Promise.race([
        promise,
        new Promise((resolve) => setTimeout(() => resolve(fallback), ms)),
    ]);

    // Malaysia-only — countrycodes restricts Nominatim's own search, not just
    // a client-side filter, so a generic word like "bandar" surfaces actual
    // Malaysian places instead of matches in Indonesia/India/Bangladesh too.
    const fetchSuggestions = async (query) => {
        const q = String(query || '').trim();
        if (q.length < 1) return [];
        const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=10&addressdetails=1&countrycodes=my&q=' + encodeURIComponent(q);
        const response = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } });
        if (!response.ok) return [];
        const payload = await response.json();
        return Array.isArray(payload) ? payload : [];
    };

    const reverseGeocode = async (lat, lng) => {
        const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);
        const response = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } });
        if (!response.ok) return null;
        const payload = await response.json();
        return payload?.display_name || null;
    };

    const toRad = (deg) => (deg * Math.PI) / 180;

    // Metres between two lat/lng points (haversine) — used to sort nearby
    // places by actual distance from the pin, not whatever order Overpass
    // happened to return them in.
    const distanceMeters = (lat1, lng1, lat2, lng2) => {
        const R = 6371000;
        const dLat = toRad(lat2 - lat1);
        const dLng = toRad(lng2 - lng1);
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    };

    // Named, generally-recognisable places near the pin (malls, restaurants,
    // schools, landmarks, parks...) — what someone actually means by "picking
    // a place near here", as opposed to reverseGeocode's plain street address
    // for the exact tapped point. Nominatim can't answer "what's nearby" without
    // a search term, so this queries OSM data directly via Overpass instead.
    const fetchNearbyPlaces = async (lat, lng, radiusMeters = 400) => {
        const tags = ['amenity', 'shop', 'tourism', 'leisure'];
        const clauses = tags.map((tag) => `  node["name"]["${tag}"](around:${radiusMeters},${lat},${lng});\n  way["name"]["${tag}"](around:${radiusMeters},${lat},${lng});`).join('\n');
        const query = `[out:json][timeout:10];\n(\n${clauses}\n);\nout center 40;`;

        const response = await fetch('https://overpass-api.de/api/interpreter', {
            method: 'POST',
            headers: { 'Content-Type': 'text/plain' },
            body: query,
        });
        if (!response.ok) return [];
        const payload = await response.json();
        const elements = Array.isArray(payload?.elements) ? payload.elements : [];

        const seen = new Set();
        const places = [];
        for (const el of elements) {
            const name = el.tags?.name?.trim();
            if (!name || seen.has(name.toLowerCase())) continue;
            const placeLat = el.lat ?? el.center?.lat;
            const placeLng = el.lon ?? el.center?.lon;
            if (!Number.isFinite(placeLat) || !Number.isFinite(placeLng)) continue;

            seen.add(name.toLowerCase());
            const category = el.tags?.amenity || el.tags?.shop || el.tags?.tourism || el.tags?.leisure || '';
            places.push({
                name,
                category: category.replace(/_/g, ' '),
                lat: placeLat,
                lng: placeLng,
                distance: distanceMeters(lat, lng, placeLat, placeLng),
            });
        }

        return places.sort((a, b) => a.distance - b.distance).slice(0, 8);
    };

    const setCoords = (target, lat, lng) => {
        if (target === 'destination') {
            if (destinationLatInput) destinationLatInput.value = String(lat);
            if (destinationLngInput) destinationLngInput.value = String(lng);
        } else {
            if (pickupLatInput) pickupLatInput.value = String(lat);
            if (pickupLngInput) pickupLngInput.value = String(lng);
        }
        if (centerLatInput) centerLatInput.value = String(lat);
        if (centerLngInput) centerLngInput.value = String(lng);
    };

    // ── Current location row — geolocation is requested lazily, only on tap,
    // not on page load, so the browser's permission prompt is never a surprise.
    const currentLocationBtn   = document.getElementById('currentLocationBtn');
    const currentLocationLabel = document.getElementById('currentLocationLabel');
    if (currentLocationBtn && currentLocationLabel && 'geolocation' in navigator) {
        let resolving = false;
        currentLocationBtn.addEventListener('click', () => {
            if (resolving) return;
            resolving = true;
            currentLocationLabel.textContent = 'Detecting your location...';

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    try {
                        const label = await reverseGeocode(lat, lng) || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                        currentLocationLabel.textContent = label;
                        if (pickupInput) pickupInput.value = label;
                        setCoords('pickup', lat, lng);
                        destinationInput?.focus();
                    } catch (_e) {
                        currentLocationLabel.textContent = 'Could not look up your address — try again.';
                    } finally {
                        resolving = false;
                    }
                },
                () => {
                    currentLocationLabel.textContent = 'Location unavailable — check permissions and try again.';
                    resolving = false;
                },
                { timeout: 8000, maximumAge: 60000 }
            );
        });
    } else if (currentLocationBtn) {
        currentLocationBtn.style.display = 'none';
    }

    // Whichever field the user last focused is the "active" field — starts on
    // Destination (it carries autofocus), and always holds exactly one of the
    // two, since that's what Recent/Suggested/live-search results fill into.
    // The visual highlight is driven by this JS state, not CSS :focus-within —
    // native focus disappears the moment the user taps a chip/button/FAB
    // elsewhere on the page, but the active field must stay marked regardless.
    let lastFocusedTarget = 'destination';
    const pickupPill      = document.querySelector('.xs2-field-pill[data-target="pickup"]');
    const destinationPill = document.querySelector('.xs2-field-pill[data-target="destination"]');
    const updateActiveFieldUI = () => {
        pickupPill?.classList.toggle('is-active-target', lastFocusedTarget === 'pickup');
        destinationPill?.classList.toggle('is-active-target', lastFocusedTarget === 'destination');
    };
    updateActiveFieldUI();

    // ── Recent / Suggested tabs, and the live-search panel that replaces them
    // while typing (Grab-style: results grow out of the list card itself,
    // not a floating dropdown that overlaps the content under it) ──────────
    const tabButtons        = Array.from(document.querySelectorAll('.xs2-tab'));
    const panels            = Array.from(document.querySelectorAll('.xs2-results'));
    const searchTabsEl      = document.getElementById('searchTabs');
    const liveSearchResults = document.getElementById('liveSearchResults');

    const showPanel = (panelName) => {
        tabButtons.forEach((b) => {
            const isActive = b.dataset.panel === panelName;
            b.classList.toggle('active', isActive);
            b.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        panels.forEach((panel) => {
            panel.hidden = panel.dataset.panel !== panelName;
        });
        if (searchTabsEl) searchTabsEl.hidden = panelName === 'live-search';
    };

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => showPanel(btn.dataset.panel));
    });

    const escapeHtml = (value) => String(value).replace(/</g, '&lt;').replace(/>/g, '&gt;');

    // Nominatim can take 1-3s to respond, so show this the instant a search
    // actually starts (not on every keystroke) — otherwise a quiet screen
    // for a few seconds reads as broken, not "still working."
    const renderLoading = () => {
        if (!liveSearchResults) return;
        liveSearchResults.innerHTML = `
            <div class="xs2-empty-state xs2-loading-state">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
                <p>Searching...</p>
            </div>
        `;
    };

    const renderLiveResults = (items, target) => {
        if (!liveSearchResults) return;

        if (!items.length) {
            liveSearchResults.innerHTML = `
                <div class="xs2-empty-state">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <p>No matching places found in Malaysia. Try a different spelling.</p>
                </div>
            `;
            return;
        }

        liveSearchResults.innerHTML = items.map((item, index) => {
            const main = escapeHtml(String(item.display_name || '').split(',')[0] || 'Unnamed location');
            const sub  = escapeHtml(String(item.display_name || '').trim());
            return `
                <button type="button" class="xs2-result-row" data-index="${index}">
                    <span class="xs2-result-icon xs2-result-icon-suggested"><i class="fa-solid fa-location-dot"></i></span>
                    <span class="xs2-result-text">
                        <strong>${main}</strong>
                        <small>${sub}</small>
                    </span>
                    <i class="fa-solid fa-chevron-right xs2-result-chevron"></i>
                </button>
            `;
        }).join('');

        Array.from(liveSearchResults.querySelectorAll('.xs2-result-row')).forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx    = Number.parseInt(btn.dataset.index || '-1', 10);
                const picked = items[idx];
                if (!picked) return;

                const label = String(picked.display_name || '').trim();
                const lat   = Number.parseFloat(String(picked.lat || ''));
                const lng   = Number.parseFloat(String(picked.lon || ''));
                const targetInput = target === 'pickup' ? pickupInput : destinationInput;
                if (targetInput) {
                    targetInput.value = label;
                    targetInput.blur();
                }
                if (Number.isFinite(lat) && Number.isFinite(lng)) setCoords(target, lat, lng);
                showPanel('recent');
            });
        });
    };

    const runLiveSearch = debounce(async (inputEl, target) => {
        const query = inputEl.value.trim();
        if (query.length < 1) {
            showPanel('recent');
            return;
        }
        showPanel('live-search');
        renderLoading();
        try {
            const items = await fetchSuggestions(query);
            // Drop a stale response that resolved after the user kept typing.
            if (inputEl.value.trim() !== query) return;
            renderLiveResults(items, target);
        } catch (_e) {
            renderLiveResults([], target);
        }
    }, 300);

    destinationInput?.addEventListener('input', () => {
        if (destinationLatInput) destinationLatInput.value = '';
        if (destinationLngInput) destinationLngInput.value = '';
        runLiveSearch(destinationInput, 'destination');
    });
    pickupInput?.addEventListener('input', () => {
        if (pickupLatInput) pickupLatInput.value = '';
        if (pickupLngInput) pickupLngInput.value = '';
        runLiveSearch(pickupInput, 'pickup');
    });

    destinationInput?.addEventListener('focus', () => {
        lastFocusedTarget = 'destination';
        updateActiveFieldUI();
        if (!destinationInput.value.trim()) showPanel('recent');
    });
    pickupInput?.addEventListener('focus', () => {
        lastFocusedTarget = 'pickup';
        updateActiveFieldUI();
        if (!pickupInput.value.trim()) showPanel('recent');
    });

    // ── Recent / Suggested rows fill the active field instead of navigating ──
    document.querySelectorAll('.xs2-result-row[data-fill]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const value = btn.dataset.fill || '';
            const targetInput = lastFocusedTarget === 'pickup' ? pickupInput : destinationInput;
            if (!targetInput) return;

            targetInput.value = value;
            // A picked list item is plain text, not a geocoded pin — clear any
            // stale coordinates so the form doesn't submit an old lat/lng next
            // to a now-different typed value.
            if (lastFocusedTarget === 'pickup') {
                if (pickupLatInput) pickupLatInput.value = '';
                if (pickupLngInput) pickupLngInput.value = '';
            } else {
                if (destinationLatInput) destinationLatInput.value = '';
                if (destinationLngInput) destinationLngInput.value = '';
            }
            targetInput.focus();
        });
    });

    // ── Keep the "Choose on map" pill above the on-screen keyboard ─────────
    // Mobile browsers shrink the visual viewport (not the layout viewport)
    // when the keyboard opens, so a plain `position: fixed; bottom: 0` button
    // can end up hidden behind it. VisualViewport reports the real visible
    // height, so the gap between it and window.innerHeight is the keyboard.
    if (window.visualViewport) {
        const fabEl = document.getElementById('openMapPickerBtn');
        const repositionFab = () => {
            if (!fabEl) return;
            const viewport = window.visualViewport;
            const keyboardInset = window.innerHeight - viewport.height - viewport.offsetTop;
            // A few px of slack for browser UI chrome so this only fires for
            // an actual keyboard, not address-bar show/hide noise.
            fabEl.style.bottom = keyboardInset > 60 ? `${keyboardInset + 12}px` : '';
        };
        window.visualViewport.addEventListener('resize', repositionFab);
        window.visualViewport.addEventListener('scroll', repositionFab);
    }

    // ── Full-screen map picker overlay ──────────────────────────────────
    const overlay        = document.getElementById('mapOverlay');
    const mapEl           = document.getElementById('exploreSearchMap');
    const statusEl         = document.getElementById('searchMapStatus');
    const confirmBtn       = document.getElementById('confirmPinBtn');
    const openBtn           = document.getElementById('openMapPickerBtn');
    const closeBtn          = document.getElementById('closeMapPickerBtn');
    const mapSearchBarBtn   = document.getElementById('mapBackToSearchBtn');
    const mapSearchBarIcon  = document.getElementById('mapSearchBarIcon');
    const mapSearchBarLabel = document.getElementById('mapSearchBarLabel');
    const mapSheetOptions   = document.getElementById('mapSheetOptions');
    const locateMeBtn       = document.getElementById('locateMeBtn');

    if (!overlay || !mapEl || !openBtn || typeof window.L === 'undefined') return;

    let map = null;
    let pinMarker = null; // real Leaflet marker — tappable/draggable, not a screen-fixed overlay
    let activeTarget = 'destination';
    let currentCenter = null; // { lat, lng, label }
    let moveTimer = null;

    const setStatus = (text) => { if (statusEl) statusEl.textContent = text; };

    const pinColor = () => (activeTarget === 'destination' ? '#dc2626' : '#16a34a');

    // Leaflet positions the marker's own element via an inline transform (for
    // panning), so the drag "lift" animation is applied to an inner <span>
    // instead — animating the marker element's own transform would fight
    // Leaflet's positioning and the marker would never actually move.
    const buildPinIcon = () => window.L.divIcon({
        className: 'xs2-map-pin-icon',
        html: `<span class="xs2-pin-inner"><i class="fa-solid fa-location-dot" style="color:${pinColor()};"></i></span>`,
        iconSize: [30, 42],
        iconAnchor: [15, 42],
    });

    // Which field this pin sets was already decided by whichever field was
    // active before the map opened (see openOverlay below) — this just
    // reflects that choice, it's not an interactive toggle.
    const updateTargetUI = () => {
        const isDestination = activeTarget === 'destination';
        pinMarker?.setIcon(buildPinIcon());
        if (mapSearchBarIcon) {
            mapSearchBarIcon.className = 'xs2-field-dot ' + (isDestination ? 'xs2-field-dot-dest' : 'xs2-field-dot-pickup');
        }
        if (mapSearchBarLabel) {
            const typedValue = (isDestination ? destinationInput?.value : pickupInput?.value)?.trim();
            mapSearchBarLabel.textContent = typedValue || (isDestination ? 'Where to?' : 'Pickup area (optional)');
        }
    };

    // First row is always the exact tapped point's own address (tapping it
    // just confirms that label — the pin is already there); every row after
    // that is a real named place found nearby, and tapping one actually
    // moves the pin to that place's own coordinates.
    const renderSheetOptions = (options) => {
        if (!mapSheetOptions) return;
        if (!options.length) {
            mapSheetOptions.hidden = true;
            mapSheetOptions.innerHTML = '';
            return;
        }

        mapSheetOptions.innerHTML = options.map((opt, index) => `
            <button type="button" class="xs2-sheet-option-row${index === 0 ? ' is-selected' : ''}" data-index="${index}">
                <i class="fa-solid ${opt.isExact ? 'fa-location-dot' : 'fa-star'}"></i>
                <span class="xs2-sheet-option-text">
                    <strong>${escapeHtml(opt.label)}</strong>
                    ${opt.sub ? `<small>${escapeHtml(opt.sub)}</small>` : ''}
                </span>
            </button>
        `).join('');
        mapSheetOptions.hidden = false;

        Array.from(mapSheetOptions.querySelectorAll('.xs2-sheet-option-row')).forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = Number.parseInt(btn.dataset.index || '0', 10);
                const opt = options[idx];
                if (!opt) return;

                Array.from(mapSheetOptions.querySelectorAll('.xs2-sheet-option-row')).forEach((b) => b.classList.remove('is-selected'));
                btn.classList.add('is-selected');

                if (opt.isExact) {
                    if (currentCenter) currentCenter.label = opt.label;
                    bouncePin();
                    return;
                }

                // A genuinely different nearby place — pan there (it may sit
                // outside the current view) and move the pin to its real spot.
                map.panTo([opt.lat, opt.lng]);
                placePinAt(opt.lat, opt.lng);
            });
        });
    };

    const bouncePin = () => {
        const el = pinMarker?.getElement()?.querySelector('.xs2-pin-inner');
        if (!el) return;
        el.classList.remove('is-dragging');
        // Restart the animation even if it's already mid-bounce from a rapid
        // second tap — forcing reflow between remove/add is what makes that work.
        void el.offsetWidth;
        el.classList.add('is-bouncing');
        setTimeout(() => el.classList.remove('is-bouncing'), 220);
    };

    // Moves the pin to lat/lng (creating the marker on first use) and looks up
    // its address — shared by tapping the map, dragging the marker, and the
    // initial placement on open. This is the one path that ever sets currentCenter.
    const placePinAt = (lat, lng) => {
        if (!pinMarker) {
            pinMarker = window.L.marker([lat, lng], {
                icon: buildPinIcon(),
                draggable: true,
                autoPan: true,
            }).addTo(map);
            pinMarker.on('dragstart', () => {
                pinMarker.getElement()?.querySelector('.xs2-pin-inner')?.classList.add('is-dragging');
            });
            pinMarker.on('dragend', () => {
                pinMarker.getElement()?.querySelector('.xs2-pin-inner')?.classList.remove('is-dragging');
                const pos = pinMarker.getLatLng();
                placePinAt(pos.lat, pos.lng);
            });
        } else {
            pinMarker.setLatLng([lat, lng]);
        }

        currentCenter = { lat, lng, label: null };
        if (confirmBtn) confirmBtn.disabled = true;
        setStatus('Loading nearby locations...');
        if (mapSheetOptions) mapSheetOptions.hidden = true;

        clearTimeout(moveTimer);
        moveTimer = setTimeout(async () => {
            const fallback = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            try {
                const [exactLabel, nearbyPlaces] = await Promise.all([
                    reverseGeocode(lat, lng).catch(() => null),
                    withTimeout(fetchNearbyPlaces(lat, lng).catch(() => []), 8000, []),
                ]);

                currentCenter = { lat, lng, label: exactLabel || fallback };
                setStatus(nearbyPlaces.length
                    ? 'Choose a nearby place, or tap elsewhere on the map.'
                    : 'No notable places nearby — tap elsewhere on the map.');

                const options = [
                    { label: exactLabel || fallback, sub: null, lat, lng, isExact: true },
                    ...nearbyPlaces.map((place) => ({
                        label: place.name,
                        sub: place.category ? `${place.category} · ${Math.round(place.distance)}m away` : `${Math.round(place.distance)}m away`,
                        lat: place.lat,
                        lng: place.lng,
                        isExact: false,
                    })),
                ];
                renderSheetOptions(options);
            } catch (_e) {
                currentCenter = { lat, lng, label: fallback };
                setStatus('Could not look up this address, but the pin is still usable.');
            } finally {
                if (confirmBtn) confirmBtn.disabled = false;
            }
        }, 300);
    };

    const initMap = () => {
        if (map) return;
        map = window.L.map(mapEl, { zoomControl: true, attributionControl: false })
            .setView([3.139, 101.6869], 12);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

        // Tap/click anywhere on the map to drop the pin there — like any other
        // map app — rather than the old "drag the map under a fixed pin" trick.
        map.on('click', (e) => placePinAt(e.latlng.lat, e.latlng.lng));
    };

    // Picking a starting view for the map, in priority order:
    // 1. The active field already has a pinned lat/lng — show that.
    // 2. The active field has typed text but no pin yet — search it and zoom
    //    there (same concept for either field, per the user's request).
    // 3. Field is empty — fall back to the device's current location.
    const focusMapOnOpen = async () => {
        map.invalidateSize();

        const activeInput = activeTarget === 'destination' ? destinationInput : pickupInput;
        const existingLat = activeTarget === 'destination' ? destinationLatInput?.value : pickupLatInput?.value;
        const existingLng = activeTarget === 'destination' ? destinationLngInput?.value : pickupLngInput?.value;
        const lat = Number.parseFloat(existingLat || '');
        const lng = Number.parseFloat(existingLng || '');

        if (Number.isFinite(lat) && Number.isFinite(lng)) {
            map.setView([lat, lng], 15);
            placePinAt(lat, lng);
            return;
        }

        // Nothing pinned yet for this field (or the last open was for the
        // other field) — no stale pin should carry over onto this one.
        if (pinMarker) {
            pinMarker.remove();
            pinMarker = null;
        }
        currentCenter = null;
        if (confirmBtn) confirmBtn.disabled = true;

        const typedQuery = activeInput?.value.trim();
        if (typedQuery) {
            setStatus(`Finding "${typedQuery}"...`);
            try {
                const matches = await fetchSuggestions(typedQuery);
                const best = matches[0];
                const foundLat = Number.parseFloat(best?.lat);
                const foundLng = Number.parseFloat(best?.lon);
                if (Number.isFinite(foundLat) && Number.isFinite(foundLng)) {
                    map.setView([foundLat, foundLng], 14);
                    placePinAt(foundLat, foundLng);
                    return;
                }
                setStatus(`Couldn't find "${typedQuery}" — tap the map to set your pin.`);
            } catch (_e) {
                setStatus('Search failed — tap the map to set your pin.');
            }
            return;
        }

        if ('geolocation' in navigator) {
            setStatus('Finding your current location...');
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    map.setView([position.coords.latitude, position.coords.longitude], 15);
                    placePinAt(position.coords.latitude, position.coords.longitude);
                },
                () => setStatus('Tap anywhere on the map to drop your pin.'),
                { timeout: 8000, maximumAge: 60000 }
            );
        } else {
            setStatus('Tap anywhere on the map to drop your pin.');
        }
    };

    const openOverlay = () => {
        activeTarget = lastFocusedTarget;
        updateTargetUI();
        overlay.hidden = false;
        document.body.classList.add('modal-open');
        if (confirmBtn && !currentCenter) confirmBtn.disabled = true;
        initMap();
        setTimeout(focusMapOnOpen, 50);
    };

    const closeOverlay = () => {
        overlay.hidden = true;
        document.body.classList.remove('modal-open');
    };

    openBtn.addEventListener('click', openOverlay);
    closeBtn?.addEventListener('click', closeOverlay);
    mapSearchBarBtn?.addEventListener('click', closeOverlay);

    locateMeBtn?.addEventListener('click', () => {
        if (!('geolocation' in navigator) || !map) return;
        locateMeBtn.classList.add('is-locating');
        navigator.geolocation.getCurrentPosition(
            (position) => {
                map.setView([position.coords.latitude, position.coords.longitude], 16);
                placePinAt(position.coords.latitude, position.coords.longitude);
                locateMeBtn.classList.remove('is-locating');
            },
            () => {
                setStatus('Location unavailable — check permissions and try again.');
                locateMeBtn.classList.remove('is-locating');
            },
            { timeout: 8000, maximumAge: 60000 }
        );
    });

    confirmBtn?.addEventListener('click', () => {
        if (!currentCenter) return;
        setCoords(activeTarget, currentCenter.lat, currentCenter.lng);
        const targetInput = activeTarget === 'destination' ? destinationInput : pickupInput;
        if (targetInput && currentCenter.label) targetInput.value = currentCenter.label;
        closeOverlay();
    });

    updateTargetUI();
})();
