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

    // Nominatim's reverse endpoint only ever returns one match per call — to
    // offer a short pick-list for the same pin (building/POI, street, area),
    // this asks it at a few zoom levels and keeps whatever comes back
    // distinct, closest match first.
    const reverseGeocodeMulti = async (lat, lng) => {
        const zooms = [18, 17, 14];
        const results = await Promise.all(zooms.map(async (zoom) => {
            try {
                const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&zoom=' + zoom;
                const response = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } });
                if (!response.ok) return null;
                const payload = await response.json();
                return payload?.display_name || null;
            } catch (_e) {
                return null;
            }
        }));

        const seen = new Set();
        return results.filter((label) => {
            if (!label || seen.has(label)) return false;
            seen.add(label);
            return true;
        });
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
    const centerPin        = document.getElementById('centerPin');
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
    let activeTarget = 'destination';
    let currentCenter = null; // { lat, lng, label }
    let moveTimer = null;

    const setStatus = (text) => { if (statusEl) statusEl.textContent = text; };

    // Which field this pin sets was already decided by whichever field was
    // active before the map opened (see openOverlay below) — this just
    // reflects that choice, it's not an interactive toggle.
    const updateTargetUI = () => {
        const isDestination = activeTarget === 'destination';
        centerPin.style.color = isDestination ? '#dc2626' : '#16a34a';
        if (mapSearchBarIcon) {
            mapSearchBarIcon.className = 'xs2-field-dot ' + (isDestination ? 'xs2-field-dot-dest' : 'xs2-field-dot-pickup');
        }
        if (mapSearchBarLabel) {
            const typedValue = (isDestination ? destinationInput?.value : pickupInput?.value)?.trim();
            mapSearchBarLabel.textContent = typedValue || (isDestination ? 'Where to?' : 'Pickup area (optional)');
        }
    };

    // Renders the 2-4 zoom-level labels reverseGeocodeMulti found for the
    // current pin as a selectable list — first (most precise) pre-selected,
    // tapping another just swaps which label currentCenter.label commits.
    const renderSheetOptions = (labels) => {
        if (!mapSheetOptions) return;
        if (!labels.length) {
            mapSheetOptions.hidden = true;
            mapSheetOptions.innerHTML = '';
            return;
        }

        mapSheetOptions.innerHTML = labels.map((label, index) => `
            <button type="button" class="xs2-sheet-option-row${index === 0 ? ' is-selected' : ''}" data-index="${index}">
                <i class="fa-solid fa-location-dot"></i>
                <span>${escapeHtml(label)}</span>
            </button>
        `).join('');
        mapSheetOptions.hidden = false;

        Array.from(mapSheetOptions.querySelectorAll('.xs2-sheet-option-row')).forEach((btn) => {
            btn.addEventListener('click', () => {
                Array.from(mapSheetOptions.querySelectorAll('.xs2-sheet-option-row')).forEach((b) => b.classList.remove('is-selected'));
                btn.classList.add('is-selected');
                const idx = Number.parseInt(btn.dataset.index || '0', 10);
                if (currentCenter) currentCenter.label = labels[idx];
            });
        });
    };

    const initMap = () => {
        if (map) return;
        map = window.L.map(mapEl, { zoomControl: true, attributionControl: false })
            .setView([3.139, 101.6869], 12);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

        map.on('movestart', () => centerPin.classList.add('is-dragging'));
        map.on('move', () => centerPin.classList.remove('is-dragging'));
        map.on('moveend', async () => {
            centerPin.classList.remove('is-dragging');
            const c = map.getCenter();
            currentCenter = { lat: c.lat, lng: c.lng, label: null };
            if (confirmBtn) confirmBtn.disabled = true;
            setStatus('Loading nearby locations...');
            if (mapSheetOptions) mapSheetOptions.hidden = true;
            clearTimeout(moveTimer);
            moveTimer = setTimeout(async () => {
                try {
                    const labels = await reverseGeocodeMulti(c.lat, c.lng);
                    const fallback = `${c.lat.toFixed(5)}, ${c.lng.toFixed(5)}`;
                    currentCenter = { lat: c.lat, lng: c.lng, label: labels[0] || fallback };
                    setStatus('Choose the closest match, or keep dragging.');
                    renderSheetOptions(labels.length ? labels : [fallback]);
                } catch (_e) {
                    currentCenter = { lat: c.lat, lng: c.lng, label: `${c.lat.toFixed(5)}, ${c.lng.toFixed(5)}` };
                    setStatus('Could not look up this address, but the pin is still usable.');
                } finally {
                    if (confirmBtn) confirmBtn.disabled = false;
                }
            }, 300);
        });
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
            return;
        }

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
                    return;
                }
                setStatus(`Couldn't find "${typedQuery}" — move the map to set your pin.`);
            } catch (_e) {
                setStatus('Search failed — move the map to set your pin.');
            }
            return;
        }

        if ('geolocation' in navigator) {
            setStatus('Finding your current location...');
            navigator.geolocation.getCurrentPosition(
                (position) => map.setView([position.coords.latitude, position.coords.longitude], 15),
                () => map.setView(map.getCenter(), map.getZoom()),
                { timeout: 8000, maximumAge: 60000 }
            );
        } else {
            map.setView(map.getCenter(), map.getZoom());
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
