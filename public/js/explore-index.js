/* Extracted from resources/views/explore/index.blade.php — logic; page values come from window.CH_EXPLORE. */
        (() => {
            // Scroll to focused card on page load
            const target = document.querySelector('[data-explore-focus-card="1"]');
            if (!target) return;
            window.setTimeout(() => {
                const y = target.getBoundingClientRect().top + window.scrollY - 104;
                window.scrollTo({ top: Math.max(y, 0), behavior: 'smooth' });
            }, 220);
        })();

        (async () => {
            const mapEl = document.getElementById('explore-map');
            const cards = Array.from(document.querySelectorAll('.open-explore-card[data-pickup-lat][data-destination-lat]'));
            if (!mapEl) return;

            const toNumber = (value) => {
                const number = Number.parseFloat(String(value ?? ''));
                return Number.isFinite(number) ? number : null;
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const routeRows = cards.map((card, index) => {
                const pickupLat = toNumber(card.dataset.pickupLat);
                const pickupLng = toNumber(card.dataset.pickupLng);
                const destinationLat = toNumber(card.dataset.destinationLat);
                const destinationLng = toNumber(card.dataset.destinationLng);
                if ([pickupLat, pickupLng, destinationLat, destinationLng].some((value) => value === null)) return null;

                return {
                    card,
                    index,
                    routeName: card.dataset.routeName || `Route ${index + 1}`,
                    driver: card.dataset.driver || 'Driver',
                    time: card.dataset.time || '-',
                    seats: card.dataset.seats || '-',
                    fare: card.dataset.fare || '-',
                    pickup: card.dataset.pickup || 'Pickup',
                    destination: card.dataset.destination || 'Destination',
                    tripUrl: card.dataset.tripUrl || '#',
                    pickupPoint: [pickupLat, pickupLng],
                    destinationPoint: [destinationLat, destinationLng],
                    isFocused: card.dataset.exploreFocusCard === '1',
                };
            }).filter(Boolean);
            const routeGroups = Array.from(routeRows.reduce((groups, route) => {
                const key = [
                    route.pickupPoint[0].toFixed(5),
                    route.pickupPoint[1].toFixed(5),
                    route.destinationPoint[0].toFixed(5),
                    route.destinationPoint[1].toFixed(5),
                ].join('|');
                if (!groups.has(key)) {
                    groups.set(key, {
                        key,
                        pickup: route.pickup,
                        destination: route.destination,
                        pickupPoint: route.pickupPoint,
                        destinationPoint: route.destinationPoint,
                        routes: [],
                    });
                }
                groups.get(key).routes.push(route);
                route.groupKey = key;
                return groups;
            }, new Map()).values());

            if (typeof window.L === 'undefined') {
                mapEl.innerHTML = '<div class="xp-map-empty">Map is unavailable right now.</div>';
                return;
            }

            if (!routeRows.length) {
                mapEl.innerHTML = '<div class="xp-map-empty">No mapped route coordinates for the current trips.</div>';
                return;
            }

            const map = window.L.map(mapEl, {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: false,
            });

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);

            const bounds = window.L.latLngBounds([]);
            const routeLayers = new Map();
            const pickupIcon = window.L.divIcon({
                className: '',
                html: '<span class="xp-map-pin pickup"></span>',
                iconSize: [18, 18],
                iconAnchor: [9, 9],
            });
            const destinationIcon = window.L.divIcon({
                className: '',
                html: '<span class="xp-map-pin destination"></span>',
                iconSize: [18, 18],
                iconAnchor: [9, 9],
            });
            const fetchShortestPath = async (group) => {
                const coordinates = [
                    `${encodeURIComponent(group.pickupPoint[1])},${encodeURIComponent(group.pickupPoint[0])}`,
                    `${encodeURIComponent(group.destinationPoint[1])},${encodeURIComponent(group.destinationPoint[0])}`,
                ].join(';');
                const fallback = [group.pickupPoint, group.destinationPoint];
                try {
                    const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`);
                    if (!response.ok) return fallback;
                    const payload = await response.json();
                    const points = (payload?.routes?.[0]?.geometry?.coordinates ?? [])
                        .map((coord) => [Number(coord[1]), Number(coord[0])])
                        .filter((point) => Number.isFinite(point[0]) && Number.isFinite(point[1]));
                    return points.length > 1 ? points : fallback;
                } catch (_error) {
                    return fallback;
                }
            };

            const setRouteActive = (route, active) => {
                const layers = routeLayers.get(route.groupKey);
                if (!layers) return;
                layers.shadow.setStyle({
                    weight: active ? 18 : 12,
                    opacity: active ? 0.22 : 0.12,
                });
                layers.line.setStyle({
                    weight: active ? 6 : 4,
                    opacity: active ? 0.95 : 0.68,
                    color: active ? '#facc15' : '#0f172a',
                });
                if (active) {
                    layers.shadow.bringToFront();
                    layers.line.bringToFront();
                    layers.pickup.bringToFront();
                    layers.destination.bringToFront();
                }
            };

            for (const group of routeGroups) {
                const latLngs = await fetchShortestPath(group);
                latLngs.forEach((point) => bounds.extend(point));
                const isFocused = group.routes.some((route) => route.isFocused);
                const title = group.routes.length === 1
                    ? escapeHtml(group.routes[0].driver)
                    : `${group.routes.length} trips on this route`;
                const tripItems = group.routes.map((route) => `
                    <li>
                        <div class="xp-map-popup-trip-head">
                            <strong class="xp-map-popup-driver">${escapeHtml(route.driver)}</strong>
                            <button type="button" class="xp-map-popup-action" data-map-card-id="${escapeHtml(route.card.id)}">Details</button>
                        </div>
                        <div class="xp-map-popup-meta">
                            <span class="xp-map-popup-pill">${escapeHtml(route.time)}</span>
                            <span class="xp-map-popup-pill">${escapeHtml(route.fare)}</span>
                            <span class="xp-map-popup-pill">${escapeHtml(route.seats)}</span>
                        </div>
                    </li>
                `).join('');

                const popupHtml = `
                    <div class="xp-map-popup">
                        <div class="xp-map-popup-title">${title}</div>
                        <div class="xp-map-popup-route">
                            ${escapeHtml(group.pickup)}<br>
                            ${escapeHtml(group.destination)}
                        </div>
                        <ul class="xp-map-popup-list">${tripItems}</ul>
                    </div>
                `;
                const shadow = window.L.polyline(latLngs, {
                    color: '#0f172a',
                    weight: isFocused ? 18 : 12,
                    opacity: isFocused ? 0.22 : 0.12,
                    lineCap: 'round',
                    interactive: false,
                }).addTo(map);
                const line = window.L.polyline(latLngs, {
                    color: isFocused ? '#facc15' : '#0f172a',
                    weight: isFocused ? 6 : 4,
                    opacity: isFocused ? 0.95 : 0.68,
                    lineCap: 'round',
                }).addTo(map).bindPopup(popupHtml);
                const pickup = window.L.marker(group.pickupPoint, { icon: pickupIcon }).addTo(map).bindTooltip(escapeHtml(group.pickup)).bindPopup(popupHtml);
                const destination = window.L.marker(group.destinationPoint, { icon: destinationIcon }).addTo(map).bindTooltip(escapeHtml(group.destination)).bindPopup(popupHtml);

                routeLayers.set(group.key, { shadow, line, pickup, destination, latLngs });
            }

            routeRows.forEach((route) => {
                route.card.addEventListener('mouseenter', () => setRouteActive(route, true));
                route.card.addEventListener('mouseleave', () => setRouteActive(route, route.isFocused));
                route.card.addEventListener('focus', () => setRouteActive(route, true));
                route.card.addEventListener('blur', () => setRouteActive(route, route.isFocused));
                route.card.addEventListener('click', () => {
                    setRouteActive(route, true);
                    const layers = routeLayers.get(route.groupKey);
                    if (layers) map.fitBounds(window.L.latLngBounds(layers.latLngs), { padding: [42, 42], maxZoom: 15 });
                });
            });

            map.fitBounds(bounds, { padding: [26, 26], maxZoom: 14 });
            window.setTimeout(() => map.invalidateSize(), 120);
        })();

        (() => {
            // Whole-card popup details
            const cards = document.querySelectorAll('.open-explore-card[data-trip-url]');
            const modal = document.getElementById('exploreTripModal');
            const closeBtn = document.getElementById('exploreTripModalClose');
            const form = document.getElementById('exploreModalJoinForm');
            const noteInput = document.getElementById('exploreModalNote');
            const joinBtn = document.getElementById('exploreModalJoinButton');
            const feedback = document.getElementById('exploreModalFeedback');
            const mapCard = document.getElementById('exploreModalMapCard');
            const mapStatus = document.getElementById('requestMapStatus');
            const mapTargetButtons = document.querySelectorAll('[data-map-target]');
            let activeCard = null;
            let activeMapTarget = 'pickup';
            let modalMap = null;
            let routeLayerGroup = null;
            let markerLayerGroup = null;
            let previewLayerGroup = null;
            let pickupMarker = null;
            let dropoffMarker = null;
            let routeLinePoints = [];
            let pickupEndpoint = null;
            let dropoffEndpoint = null;
            let baseRouteDistanceKm = null;
            let previewRequestToken = 0;
            let allowedRouteRadiusKm = 0.2;
            let allowedEndpointRadiusKm = 0.5;
            if (!cards.length) return;

            const isInteractive = (el) => !!el.closest('a, button, input, textarea, select, label, form');
            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value || '-';
            };
            const configureJoinButton = (card) => {
                const state = card.dataset.joinState || 'open';
                const config = {
                    joined: ['Joined', 'fa-solid fa-check', true],
                    pending: ['Request pending', 'fa-regular fa-clock', true],
                    full: ['Trip full', 'fa-solid fa-ban', true],
                    closed: ['Requests closed', 'fa-solid fa-lock', true],
                    open: ['Request seat', 'fa-solid fa-user-plus', false],
                }[state] || ['Request seat', 'fa-solid fa-user-plus', false];

                joinBtn.innerHTML = `<i class="${config[1]}"></i>${config[0]}`;
                joinBtn.disabled = config[2];
                document.querySelectorAll('#exploreModalRoutePreference input').forEach((input) => {
                    input.disabled = false;
                });
            };
            const toNumber = (value) => {
                const number = Number.parseFloat(String(value ?? ''));
                return Number.isFinite(number) ? number : null;
            };
            const formatMoney = (value) => `RM ${Math.max(0, Number(value) || 0).toFixed(2)}`;
            const currentPassengerName = window.CH_EXPLORE.passengerName;
            const passengerLabel = (suffix) => `${currentPassengerName} ${suffix}`;
            const formInput = (name) => form.querySelector(`[name="${name}"]`);
            const setStatus = (message, type = '') => {
                if (!mapStatus) return;
                mapStatus.textContent = message;
                mapStatus.classList.toggle('blocked', type === 'blocked');
                mapStatus.classList.toggle('ok', type === 'ok');
            };
            const setMode = (target, mode) => {
                const modeInput = document.querySelector(`#exploreModalRoutePreference input[name="${target}_mode"][value="${mode}"]`);
                if (modeInput) modeInput.checked = true;
            };
            const readMode = (target) => {
                const checked = document.querySelector(`#exploreModalRoutePreference input[name="${target}_mode"]:checked`);
                return checked?.value === 'custom' ? 'custom' : 'default';
            };
            const setCoordinate = (target, lat, lng) => {
                const latInput = formInput(`${target}_latitude`);
                const lngInput = formInput(`${target}_longitude`);
                if (latInput) latInput.value = lat === null ? '' : lat.toFixed(7);
                if (lngInput) lngInput.value = lng === null ? '' : lng.toFixed(7);
            };
            const setPlaceName = (target, value) => {
                const input = formInput(`${target}_name`);
                if (input) input.value = value || '';
            };
            const distanceForPointsKm = (points) => {
                let total = 0;
                for (let index = 0; index < points.length - 1; index += 1) {
                    total += points[index].distanceTo(points[index + 1]) / 1000;
                }
                return total;
            };
            const updateAllowedRadiusForDistance = (distanceKm) => {
                const routeKm = Number(distanceKm) || (pickupEndpoint && dropoffEndpoint ? distanceForPointsKm([pickupEndpoint, dropoffEndpoint]) : 1) || 1;
                if (routeKm <= 3) {
                    allowedRouteRadiusKm = 0.40;
                    allowedEndpointRadiusKm = 0.50;
                } else if (routeKm <= 10) {
                    allowedRouteRadiusKm = 0.70;
                    allowedEndpointRadiusKm = 0.80;
                } else if (routeKm <= 25) {
                    allowedRouteRadiusKm = 1.00;
                    allowedEndpointRadiusKm = 1.20;
                } else {
                    allowedRouteRadiusKm = 1.30;
                    allowedEndpointRadiusKm = 1.50;
                }
                const label = document.getElementById('routeAllowedLabel');
                if (label) label.textContent = `Allowed: route ${Math.round(allowedRouteRadiusKm * 1000)}m, Point A/B ${Math.round(allowedEndpointRadiusKm * 1000)}m`;
            };
            const setMapTarget = (target) => {
                activeMapTarget = target === 'dropoff' ? 'dropoff' : 'pickup';
                mapTargetButtons.forEach((button) => {
                    button.classList.toggle('active', button.dataset.mapTarget === activeMapTarget);
                });
                setStatus(`Tap near the route to pin ${activeMapTarget === 'pickup' ? 'pickup' : 'drop-off'}.`);
            };
            const pointIcon = (type) => window.L.divIcon({
                className: '',
                html: `<span class="passenger-pin-icon ${type === 'dropoff' ? 'dropoff' : 'pickup'}"></span>`,
                iconSize: [25, 25],
                iconAnchor: [12, 25],
                tooltipAnchor: [0, -23],
            });
            const drawRoute = (points) => {
                if (!routeLayerGroup || !modalMap || !pickupEndpoint || !dropoffEndpoint) return;
                routeLayerGroup.clearLayers();
                const latLngs = points.map((point) => Array.isArray(point) ? window.L.latLng(point[0], point[1]) : point);
                window.L.polyline(latLngs, { color: '#475569', weight: 18, opacity: 0.16, lineCap: 'round', interactive: false }).addTo(routeLayerGroup);
                window.L.polyline(latLngs, { color: '#64748b', weight: 4, opacity: 0.82, lineCap: 'round', interactive: false }).addTo(routeLayerGroup);
                window.L.circle(pickupEndpoint, { radius: allowedEndpointRadiusKm * 1000, color: '#16a34a', weight: 1, fillColor: '#16a34a', fillOpacity: 0.07, opacity: 0.28, interactive: false }).addTo(routeLayerGroup);
                window.L.circle(dropoffEndpoint, { radius: allowedEndpointRadiusKm * 1000, color: '#2563eb', weight: 1, fillColor: '#2563eb', fillOpacity: 0.07, opacity: 0.28, interactive: false }).addTo(routeLayerGroup);
                window.L.circleMarker(pickupEndpoint, { radius: 6, color: '#fff', weight: 2, fillColor: '#16a34a', fillOpacity: 1, interactive: false }).addTo(routeLayerGroup);
                window.L.circleMarker(dropoffEndpoint, { radius: 6, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1, interactive: false }).addTo(routeLayerGroup);
                modalMap.fitBounds(window.L.latLngBounds(latLngs), { padding: [22, 22] });
            };
            const pointToLocalKm = (latLng, origin) => {
                const lat = latLng.lat ?? latLng[0];
                const lng = latLng.lng ?? latLng[1];
                const originLat = origin.lat ?? origin[0];
                const originLng = origin.lng ?? origin[1];
                return {
                    x: (lng - originLng) * 111.32 * Math.cos((originLat * Math.PI) / 180),
                    y: (lat - originLat) * 110.57,
                };
            };
            const distanceToSegmentKm = (point, start, end) => {
                const p = pointToLocalKm(point, start);
                const b = pointToLocalKm(end, start);
                const lengthSquared = (b.x ** 2) + (b.y ** 2);
                if (lengthSquared === 0) return Math.sqrt((p.x ** 2) + (p.y ** 2));
                const t = Math.max(0, Math.min(1, ((p.x * b.x) + (p.y * b.y)) / lengthSquared));
                return Math.sqrt(((p.x - (t * b.x)) ** 2) + ((p.y - (t * b.y)) ** 2));
            };
            const distanceToRouteKm = (latLng) => {
                if (routeLinePoints.length < 2) return null;
                let nearest = Infinity;
                for (let index = 0; index < routeLinePoints.length - 1; index += 1) {
                    nearest = Math.min(nearest, distanceToSegmentKm(latLng, routeLinePoints[index], routeLinePoints[index + 1]));
                }
                return Number.isFinite(nearest) ? nearest : null;
            };
            const endpointDistanceKm = (latLng) => {
                if (!pickupEndpoint || !dropoffEndpoint) return Infinity;
                return Math.min(latLng.distanceTo(pickupEndpoint) / 1000, latLng.distanceTo(dropoffEndpoint) / 1000);
            };
            const uniqueWaypoints = (points) => points.reduce((items, point) => {
                if (!point) return items;
                const last = items[items.length - 1];
                if (!last || Math.abs(last.lat - point.lat) > 0.00001 || Math.abs(last.lng - point.lng) > 0.00001) {
                    items.push(point);
                }
                return items;
            }, []);
            const fetchRoute = async (points) => {
                const waypoints = uniqueWaypoints(points);
                if (waypoints.length < 2) return { points: waypoints, distanceKm: 0 };

                const coordinates = waypoints
                    .map((point) => `${encodeURIComponent(point.lng)},${encodeURIComponent(point.lat)}`)
                    .join(';');
                const url = `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`;

                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('route');
                    const payload = await response.json();
                    const route = payload?.routes?.[0];
                    const routePoints = (route?.geometry?.coordinates ?? [])
                        .map((coord) => window.L.latLng(Number(coord[1]), Number(coord[0])))
                        .filter((coord) => Number.isFinite(coord.lat) && Number.isFinite(coord.lng));

                    return {
                        points: routePoints.length > 1 ? routePoints : waypoints,
                        distanceKm: route?.distance ? Number(route.distance) / 1000 : distanceForPointsKm(waypoints),
                    };
                } catch (_error) {
                    return {
                        points: waypoints,
                        distanceKm: distanceForPointsKm(waypoints),
                    };
                }
            };
            const updateFarePreview = async () => {
                const token = ++previewRequestToken;
                const defaultFarePerPerson = Number(activeCard?.dataset.fareRaw || 0);
                const tripFareTotal = Number(activeCard?.dataset.fareTotal || defaultFarePerPerson);
                const customPickup = readMode('pickup') === 'custom';
                const customDropoff = readMode('dropoff') === 'custom';
                const pickupLat = toNumber(formInput('pickup_latitude')?.value);
                const pickupLng = toNumber(formInput('pickup_longitude')?.value);
                const dropoffLat = toNumber(formInput('dropoff_latitude')?.value);
                const dropoffLng = toNumber(formInput('dropoff_longitude')?.value);
                const passengerPickup = customPickup
                    ? (pickupLat !== null && pickupLng !== null ? window.L.latLng(pickupLat, pickupLng) : null)
                    : pickupEndpoint;
                const passengerDropoff = customDropoff
                    ? (dropoffLat !== null && dropoffLng !== null ? window.L.latLng(dropoffLat, dropoffLng) : null)
                    : dropoffEndpoint;
                const hasCustom = customPickup || customDropoff;

                if (!pickupEndpoint || !dropoffEndpoint) return;
                if ((customPickup && !passengerPickup) || (customDropoff && !passengerDropoff)) {
                    const previewPickup = customPickup && passengerPickup ? passengerPickup : pickupEndpoint;
                    const previewDropoff = customDropoff && passengerDropoff ? passengerDropoff : dropoffEndpoint;
                    const previewPoints = [pickupEndpoint, ...(customPickup && passengerPickup ? [passengerPickup] : []), ...(customDropoff && passengerDropoff ? [passengerDropoff] : []), dropoffEndpoint];
                    const previewRoute = await fetchRoute(previewPoints.length > 1 ? previewPoints : [previewPickup, previewDropoff]);
                    if (token !== previewRequestToken) return;
                    previewLayerGroup?.clearLayers();
                    if (previewLayerGroup && previewRoute.points.length > 1) {
                        window.L.polyline(previewRoute.points, { color: '#1d4ed8', weight: 6, opacity: 0.9, lineCap: 'round', interactive: false }).addTo(previewLayerGroup);
                        window.L.polyline(previewRoute.points, { color: '#facc15', weight: 2, opacity: 0.95, dashArray: '7 8', lineCap: 'round', interactive: false }).addTo(previewLayerGroup);
                    }
                    formInput('extra_fee_amount').value = '';
                    formInput('detour_distance_km').value = '';
                    setText('farePreviewBadge', 'Waiting for pin');
                    setText('farePreviewRoute', previewRoute.distanceKm ? `${previewRoute.distanceKm.toFixed(2)} km` : '-');
                    setText('farePreviewSegment', '-');
                    setText('farePreviewPassenger', formatMoney(defaultFarePerPerson));
                    setText('farePreviewOthers', formatMoney(defaultFarePerPerson));
                    setText('farePreviewNote', customDropoff && !passengerDropoff && customPickup && passengerPickup
                        ? 'Pickup custom is already in the suggested route. Pin the custom drop-off to finish fare preview.'
                        : 'Pin custom points to preview base split + extra fee.');
                    return;
                }

                const routePoints = [pickupEndpoint, ...(customPickup ? [passengerPickup] : []), ...(customDropoff ? [passengerDropoff] : []), dropoffEndpoint];
                const suggestedRoute = await fetchRoute(routePoints);
                if (token !== previewRequestToken) return;

                previewLayerGroup?.clearLayers();
                if (previewLayerGroup && suggestedRoute.points.length > 1) {
                    window.L.polyline(suggestedRoute.points, { color: '#1d4ed8', weight: 6, opacity: 0.9, lineCap: 'round', interactive: false }).addTo(previewLayerGroup);
                    window.L.polyline(suggestedRoute.points, { color: '#facc15', weight: 2, opacity: 0.95, dashArray: '7 8', lineCap: 'round', interactive: false }).addTo(previewLayerGroup);
                }

                const baseKm = baseRouteDistanceKm || distanceForPointsKm([pickupEndpoint, dropoffEndpoint]) || 1;
                const routeDeviationKm = hasCustom
                    ? Math.max(0, suggestedRoute.distanceKm - baseKm)
                    : 0;
                const passengerFare = hasCustom ? defaultFarePerPerson + ((routeDeviationKm / baseKm) * tripFareTotal) : defaultFarePerPerson;
                const passengerDelta = passengerFare - defaultFarePerPerson;
                formInput('extra_fee_amount').value = hasCustom ? passengerDelta.toFixed(2) : '';
                formInput('detour_distance_km').value = hasCustom ? routeDeviationKm.toFixed(2) : '';
                setText('farePreviewBadge', hasCustom ? 'Extra fee added' : 'Default split');
                setText('farePreviewRoute', `${baseKm.toFixed(2)} km`);
                setText('farePreviewSegment', `${routeDeviationKm.toFixed(2)} km`);
                setText('farePreviewPassenger', formatMoney(passengerFare));
                setText('farePreviewOthers', formatMoney(defaultFarePerPerson));
                setText('farePreviewNote', hasCustom
                    ? `Normal split stays as base fare. Extra ${formatMoney(passengerDelta)} is based on ${routeDeviationKm.toFixed(2)} km custom deviation from the driver's original route. Driver can review before approve.`
                    : 'Default trip points selected, normal fare split is used.');
            };
            const applyPin = (target, latLng) => {
                setMode(target, 'custom');
                setCoordinate(target, latLng.lat, latLng.lng);
                setPlaceName(target, `Pinned ${target === 'pickup' ? 'pickup' : 'drop-off'} near route (${latLng.lat.toFixed(5)}, ${latLng.lng.toFixed(5)})`);
                if (target === 'pickup') {
                    if (pickupMarker) pickupMarker.remove();
                    pickupMarker = window.L.marker(latLng, { icon: pointIcon('pickup') }).addTo(markerLayerGroup);
                    pickupMarker.bindTooltip(passengerLabel('pickup pin'), { permanent: true, direction: 'top', offset: [0, -10] });
                } else {
                    if (dropoffMarker) dropoffMarker.remove();
                    dropoffMarker = window.L.marker(latLng, { icon: pointIcon('dropoff') }).addTo(markerLayerGroup);
                    dropoffMarker.bindTooltip(passengerLabel('drop-off pin'), { permanent: true, direction: 'top', offset: [0, -10] });
                }
                updateFarePreview();
            };
            const restorePinnedMarkers = () => {
                if (!markerLayerGroup) return;
                const pickupLat = toNumber(formInput('pickup_latitude')?.value);
                const pickupLng = toNumber(formInput('pickup_longitude')?.value);
                const dropoffLat = toNumber(formInput('dropoff_latitude')?.value);
                const dropoffLng = toNumber(formInput('dropoff_longitude')?.value);
                if (readMode('pickup') === 'custom' && pickupLat !== null && pickupLng !== null) {
                    pickupMarker = window.L.marker(window.L.latLng(pickupLat, pickupLng), { icon: pointIcon('pickup') }).addTo(markerLayerGroup);
                    pickupMarker.bindTooltip(passengerLabel('pickup pin'), { permanent: true, direction: 'top', offset: [0, -10] });
                }
                if (readMode('dropoff') === 'custom' && dropoffLat !== null && dropoffLng !== null) {
                    dropoffMarker = window.L.marker(window.L.latLng(dropoffLat, dropoffLng), { icon: pointIcon('dropoff') }).addTo(markerLayerGroup);
                    dropoffMarker.bindTooltip(passengerLabel('drop-off pin'), { permanent: true, direction: 'top', offset: [0, -10] });
                }
            };
            const handleMapClick = (latLng) => {
                if (readMode('pickup') !== 'custom' && readMode('dropoff') !== 'custom') {
                    setStatus('Default trip points selected. Choose custom pickup or drop-off to pin a nearby stop.');
                    return;
                }
                const routeDistance = distanceToRouteKm(latLng);
                const pointDistance = endpointDistanceKm(latLng);
                const allowed = (routeDistance !== null && routeDistance <= allowedRouteRadiusKm) || pointDistance <= allowedEndpointRadiusKm;
                if (!allowed) {
                    const routeDistanceText = routeDistance === null ? 'unknown' : `${routeDistance.toFixed(1)} km`;
                    setStatus(`Blocked: selected point is outside the allowed route area. Nearest route distance: ${routeDistanceText}.`, 'blocked');
                    window.L.circleMarker(latLng, { radius: 8, color: '#b91c1c', weight: 2, fillColor: '#ef4444', fillOpacity: 0.2 }).addTo(markerLayerGroup).bindTooltip('Outside allowed area', { direction: 'top', offset: [0, -8] }).openTooltip();
                    return;
                }
                applyPin(activeMapTarget, latLng);
                const distanceText = routeDistance === null ? 'near endpoint' : `${routeDistance.toFixed(2)} km from route`;
                setStatus(`${activeMapTarget === 'pickup' ? passengerLabel('pickup') : passengerLabel('drop-off')} pin saved, ${distanceText}.`, 'ok');
            };
            const ensureModalMap = async () => {
                if (typeof window.L === 'undefined' || !mapCard || !activeCard) return;
                const pickupLat = toNumber(activeCard.dataset.pickupLat);
                const pickupLng = toNumber(activeCard.dataset.pickupLng);
                const destLat = toNumber(activeCard.dataset.destinationLat);
                const destLng = toNumber(activeCard.dataset.destinationLng);
                const hasDriverPoints = [pickupLat, pickupLng, destLat, destLng].every((value) => value !== null);
                const center = hasDriverPoints ? [pickupLat, pickupLng] : [3.139, 101.6869];

                if (!modalMap) {
                    modalMap = window.L.map('requestRouteMapForm', { zoomControl: true, attributionControl: false, scrollWheelZoom: false }).setView(center, hasDriverPoints ? 13 : 7);
                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(modalMap);
                    modalMap.on('click', (event) => handleMapClick(event.latlng));
                    routeLayerGroup = window.L.layerGroup().addTo(modalMap);
                    markerLayerGroup = window.L.layerGroup().addTo(modalMap);
                    previewLayerGroup = window.L.layerGroup().addTo(modalMap);
                }

                routeLayerGroup.clearLayers();
                markerLayerGroup.clearLayers();
                previewLayerGroup.clearLayers();
                pickupMarker = null;
                dropoffMarker = null;

                if (!hasDriverPoints) {
                    modalMap.setView(center, 7);
                    setStatus('Map is unavailable because this route has no coordinates.', 'blocked');
                    return;
                }

                pickupEndpoint = window.L.latLng(pickupLat, pickupLng);
                dropoffEndpoint = window.L.latLng(destLat, destLng);
                routeLinePoints = [[pickupLat, pickupLng], [destLat, destLng]];
                baseRouteDistanceKm = null;
                updateAllowedRadiusForDistance(distanceForPointsKm([pickupEndpoint, dropoffEndpoint]));
                drawRoute(routeLinePoints);
                restorePinnedMarkers();
                updateFarePreview();
                setTimeout(() => modalMap?.invalidateSize(), 80);

                const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${encodeURIComponent(pickupLng)},${encodeURIComponent(pickupLat)};${encodeURIComponent(destLng)},${encodeURIComponent(destLat)}?overview=full&geometries=geojson&alternatives=false&steps=false`;
                try {
                    const response = await fetch(osrmUrl);
                    const payload = response.ok ? await response.json() : null;
                    if (payload?.routes?.[0]?.distance) {
                        baseRouteDistanceKm = Number(payload.routes[0].distance) / 1000;
                        updateAllowedRadiusForDistance(baseRouteDistanceKm);
                    }
                    const points = (payload?.routes?.[0]?.geometry?.coordinates ?? [])
                        .map((coord) => [Number(coord[1]), Number(coord[0])])
                        .filter((coord) => Number.isFinite(coord[0]) && Number.isFinite(coord[1]));
                    if (points.length > 1) {
                        routeLinePoints = points;
                        drawRoute(routeLinePoints);
                    }
                    updateFarePreview();
                } catch (_error) {
                    updateFarePreview();
                }
            };
            const syncCustomFields = (preferredTarget = null) => {
                const pickupMode = document.querySelector('#exploreModalRoutePreference input[name="pickup_mode"]:checked')?.value || 'default';
                const dropoffMode = document.querySelector('#exploreModalRoutePreference input[name="dropoff_mode"]:checked')?.value || 'default';
                const hasCustom = pickupMode === 'custom' || dropoffMode === 'custom';
                const pickupTarget = document.querySelector('.request-map-target[data-map-target="pickup"]');
                const dropoffTarget = document.querySelector('.request-map-target[data-map-target="dropoff"]');
                if (pickupTarget) pickupTarget.hidden = pickupMode !== 'custom';
                if (dropoffTarget) dropoffTarget.hidden = dropoffMode !== 'custom';
                mapCard.hidden = false;
                if (pickupMode !== 'custom') {
                    setCoordinate('pickup', null, null);
                    setPlaceName('pickup', '');
                    if (pickupMarker) {
                        pickupMarker.remove();
                        pickupMarker = null;
                    }
                }
                if (dropoffMode !== 'custom') {
                    setCoordinate('dropoff', null, null);
                    setPlaceName('dropoff', '');
                    if (dropoffMarker) {
                        dropoffMarker.remove();
                        dropoffMarker = null;
                    }
                }
                if (hasCustom) {
                    if (preferredTarget === 'dropoff' && dropoffMode === 'custom') {
                        setMapTarget('dropoff');
                    } else if (preferredTarget === 'pickup' && pickupMode === 'custom') {
                        setMapTarget('pickup');
                    } else {
                        setMapTarget(pickupMode === 'custom' ? 'pickup' : 'dropoff');
                    }
                } else {
                    mapTargetButtons.forEach((button) => button.classList.remove('active'));
                    setStatus('Default trip points selected. Choose custom pickup or drop-off to pin a nearby stop.');
                }
                ensureModalMap();
                updateFarePreview();
            };
            const openModal = (card) => {
                activeCard = card;
                setText('exploreTripModalTitle', 'Trip details');
                setText('exploreTripModalSub', card.dataset.tripRef || '-');
                setText('exploreModalDriverAvatar', card.dataset.driverInitial || 'DR');
                setText('exploreModalDriver', card.dataset.driver || 'Driver');
                setText('exploreModalRating', card.dataset.rating || '5.00');
                setText('exploreModalTime', card.dataset.time || '-');
                setText('exploreModalSeats', card.dataset.seats || '-');
                setText('exploreModalFare', card.dataset.fare || '-');
                setText('exploreModalPickup', card.dataset.pickup || '-');
                setText('exploreModalDestination', card.dataset.destination || '-');
                setText('exploreModalVehicle', card.dataset.vehicle || '-');
                const noteBlock = document.getElementById('exploreModalNoteBlock');
                const noteText = (card.dataset.note || '').trim();
                if (noteBlock) noteBlock.hidden = noteText === '';
                setText('exploreModalNoteText', noteText || '-');
                form.action = card.dataset.joinUrl || card.dataset.tripUrl || '';
                form.dataset.cardId = card.id || '';
                form.reset();
                form.querySelector('input[name="pickup_latitude"]').value = '';
                form.querySelector('input[name="pickup_longitude"]').value = '';
                form.querySelector('input[name="pickup_name"]').value = '';
                form.querySelector('input[name="dropoff_latitude"]').value = '';
                form.querySelector('input[name="dropoff_longitude"]').value = '';
                form.querySelector('input[name="dropoff_name"]').value = '';
                form.querySelector('input[name="extra_fee_amount"]').value = '';
                form.querySelector('input[name="detour_distance_km"]').value = '';
                if (pickupMarker && modalMap) modalMap.removeLayer(pickupMarker);
                if (dropoffMarker && modalMap) modalMap.removeLayer(dropoffMarker);
                pickupMarker = null;
                dropoffMarker = null;
                noteInput.value = '';
                feedback.hidden = true;
                feedback.textContent = '';
                configureJoinButton(card);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                document.body.classList.add('explore-modal-open');
                syncCustomFields();
            };
            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                document.body.classList.remove('explore-modal-open');
            };

            cards.forEach((card) => {
                card.addEventListener('click', (e) => {
                    if (isInteractive(e.target) && !e.target.closest('.open-explore-modal')) return;
                    e.preventDefault();
                    openModal(card);
                });

                card.addEventListener('keydown', (e) => {
                    if (e.key !== 'Enter' && e.key !== ' ') return;
                    if (isInteractive(e.target)) return;
                    e.preventDefault();
                    openModal(card);
                });
            });
            document.querySelectorAll('.open-explore-modal').forEach((link) => {
                link.addEventListener('click', (event) => {
                    const card = event.currentTarget.closest('.open-explore-card');
                    if (!card) return;
                    event.preventDefault();
                    event.stopPropagation();
                    openModal(card);
                });
            });
            document.addEventListener('click', (event) => {
                const button = event.target.closest('.xp-map-popup-action[data-map-card-id]');
                if (!button) return;
                event.preventDefault();
                event.stopPropagation();
                const card = document.getElementById(button.dataset.mapCardId || '');
                if (card) openModal(card);
            });
            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
            // The map is created as soon as the modal opens (ensureModalMap runs
            // unconditionally in syncCustomFields), but its <details> wrapper is
            // collapsed by default, so Leaflet sizes it against a 0x0 container.
            // Re-measure once the panel is actually visible.
            const routePrefDetails = document.getElementById('exploreModalRoutePreference');
            if (routePrefDetails) {
                routePrefDetails.addEventListener('toggle', () => {
                    if (routePrefDetails.open) {
                        window.setTimeout(() => modalMap?.invalidateSize(), 50);
                    }
                });
            }
            // Deep-link: ?focus_trip=<id> (e.g. tapping a trip card on Home) opens
            // that trip's details popup directly instead of just scrolling to it.
            const focusedCard = document.querySelector('[data-explore-focus-card="1"]');
            if (focusedCard) {
                window.setTimeout(() => openModal(focusedCard), 300);
            }
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
            });
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (joinBtn.disabled) return;

                const original = joinBtn.innerHTML;
                joinBtn.disabled = true;
                joinBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Sending';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || 'Join request could not be submitted.');

                    const card = document.getElementById(form.dataset.cardId || '');
                    if (card) {
                        card.dataset.joinState = 'pending';
                        card.querySelectorAll('.btn-primary').forEach((button) => {
                            button.classList.remove('btn-primary');
                            button.classList.add('btn-soft');
                            button.innerHTML = '<i class="fa-regular fa-clock"></i> Pending';
                        });
                    }
                    joinBtn.innerHTML = '<i class="fa-solid fa-check"></i>Request sent';
                    feedback.textContent = payload.message || 'Join request submitted.';
                    feedback.hidden = false;
                } catch (error) {
                    joinBtn.disabled = false;
                    joinBtn.innerHTML = original;
                    feedback.textContent = error.message || 'Join request could not be submitted.';
                    feedback.hidden = false;
                }
            });
            form.addEventListener('change', (event) => {
                if (event.target.matches('input[name="pickup_mode"], input[name="dropoff_mode"]')) {
                    syncCustomFields(event.target.name.replace('_mode', ''));
                }
            });
            document.getElementById('exploreModalRoutePreference')?.addEventListener('change', (event) => {
                if (event.target.matches('input[name="pickup_mode"], input[name="dropoff_mode"]')) {
                    syncCustomFields(event.target.name.replace('_mode', ''));
                }
            });
            mapTargetButtons.forEach((button) => {
                button.addEventListener('click', () => setMapTarget(button.dataset.mapTarget || 'pickup'));
            });
        })();

        /* ── Skeleton: show on filter/search form submit ────────── */
        (function () {
            var skelList = document.getElementById('xp-skel-list');
            var realList = document.getElementById('xp-real-list');

            function showSearchSkeleton() {
                if (!skelList || !realList) return;
                skelList.style.display = 'grid';
                realList.classList.add('xp-list-loading');
            }

            // Intercept search card form submit
            var searchForms = document.querySelectorAll('.xp-search-card form, form[data-search-form]');
            searchForms.forEach(function (form) {
                form.addEventListener('submit', showSearchSkeleton);
            });

            // Also intercept sort/filter chip clicks (href navigation)
            document.querySelectorAll('.xp-sort-row .chip[href]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    showSearchSkeleton();
                });
            });

            // Search button in top bar
            var searchBtn = document.querySelector('.xp-search-btn, button[type="submit"]');
            if (searchBtn) {
                searchBtn.addEventListener('click', function () {
                    setTimeout(showSearchSkeleton, 50);
                });
            }
        })();
