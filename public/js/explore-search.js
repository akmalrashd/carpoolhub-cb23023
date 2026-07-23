/* Extracted from resources/views/explore/search.blade.php — cacheable. */
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
