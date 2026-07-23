/* Extracted from resources/views/trips/requests.blade.php — logic; page values come from window.CH_TRIPREQ. */
        (() => {
            const mapEl = document.getElementById('requestRouteSummaryMap');
            if (!mapEl || typeof window.L === 'undefined') return;

            let payload = null;
            try {
                payload = JSON.parse(mapEl.dataset.routeSummary || '{}');
            } catch (_error) {
                payload = null;
            }
            if (!payload?.driverPickup || !payload?.driverDropoff) return;

            const toPoint = (raw) => {
                const lat = Number.parseFloat(String(raw?.lat ?? ''));
                const lng = Number.parseFloat(String(raw?.lng ?? ''));
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
                return window.L.latLng(lat, lng);
            };
            const driverPickup = toPoint(payload.driverPickup);
            const driverDropoff = toPoint(payload.driverDropoff);
            if (!driverPickup || !driverDropoff) return;

            const samePoint = (a, b) => Math.abs(a.lat - b.lat) < 0.00001 && Math.abs(a.lng - b.lng) < 0.00001;
            const uniqueWaypoints = (points) => points.reduce((items, point) => {
                if (!point) return items;
                if (!items.length || !samePoint(items[items.length - 1], point)) items.push(point);
                return items;
            }, []);
            const permutations = (items) => {
                if (items.length <= 1) return [items];
                return items.flatMap((item, index) => {
                    const remaining = items.filter((_, remainingIndex) => remainingIndex !== index);
                    return permutations(remaining).map((ordered) => [item, ...ordered]);
                });
            };
            const validPassengerOrder = (items) => {
                const grouped = items.reduce((groups, item, index) => {
                    if (!groups[item.requestId]) groups[item.requestId] = {};
                    groups[item.requestId][item.kind] = index;
                    return groups;
                }, {});

                return Object.values(grouped).every((group) => {
                    if (group.pickup === undefined || group.dropoff === undefined) return true;
                    return group.pickup < group.dropoff;
                });
            };
            const straightDistanceKm = (points) => {
                let total = 0;
                for (let index = 0; index < points.length - 1; index += 1) {
                    total += points[index].distanceTo(points[index + 1]) / 1000;
                }
                return total;
            };
            const fetchRoute = async (points) => {
                const waypoints = uniqueWaypoints(points);
                if (waypoints.length < 2) return { points: waypoints, distanceKm: 0, durationMinutes: 0 };
                const coordinates = waypoints
                    .map((point) => `${encodeURIComponent(point.lng)},${encodeURIComponent(point.lat)}`)
                    .join(';');
                const url = `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`;

                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('route');
                    const data = await response.json();
                    const route = data?.routes?.[0];
                    const routePoints = (route?.geometry?.coordinates ?? [])
                        .map((coord) => window.L.latLng(Number(coord[1]), Number(coord[0])))
                        .filter((coord) => Number.isFinite(coord.lat) && Number.isFinite(coord.lng));

                    return {
                        points: routePoints.length > 1 ? routePoints : waypoints,
                        distanceKm: route?.distance ? Number(route.distance) / 1000 : straightDistanceKm(waypoints),
                        durationMinutes: route?.duration ? Number(route.duration) / 60 : null,
                    };
                } catch (_error) {
                    return { points: waypoints, distanceKm: straightDistanceKm(waypoints), durationMinutes: null };
                }
            };
            const shortestMiddleRoute = async (stops) => {
                const usableStops = stops.filter((stop) => stop.point);
                const orders = usableStops.length <= 7
                    ? permutations(usableStops).filter(validPassengerOrder)
                    : [usableStops];
                const candidates = orders.length ? orders : [[]];
                const routes = await Promise.all(candidates.map(async (order) => {
                    const points = uniqueWaypoints([driverPickup, ...order.map((item) => item.point), driverDropoff]);
                    return {
                        ...(await fetchRoute(points)),
                        order,
                    };
                }));

                return routes.reduce((best, route) => {
                    if (!best || route.distanceKm < best.distanceKm) return route;
                    return best;
                }, null);
            };

            const map = window.L.map(mapEl, {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: false,
            });
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            const googleMapsLink = document.getElementById('openSummaryGoogleMaps');
            const originalLayer = window.L.layerGroup().addTo(map);
            const summaryLayer = window.L.layerGroup().addTo(map);
            let originalRouteCache = null;
            let summaryBoundsFitted = false;
            let summaryIsLoading = false;

            const passengerPalette = ['#7c3aed', '#0f766e', '#dc2626', '#2563eb', '#9333ea', '#c2410c', '#0891b2', '#be123c'];
            const colorForRequest = (requestId) => {
                const raw = Number.parseInt(String(requestId ?? '0'), 10);
                const index = Number.isFinite(raw) ? Math.abs(raw) % passengerPalette.length : 0;
                return passengerPalette[index];
            };
            const stops = (payload.requests || []).flatMap((request) => {
                const pickup = toPoint(request.pickup);
                const dropoff = toPoint(request.dropoff);
                const color = colorForRequest(request.id);
                return [
                    pickup ? { requestId: request.id, kind: 'pickup', point: pickup, label: request.pickup.label || `${request.name} pickup`, status: request.status, color } : null,
                    dropoff ? { requestId: request.id, kind: 'dropoff', point: dropoff, label: request.dropoff.label || `${request.name} drop-off`, status: request.status, color } : null,
                ].filter(Boolean);
            }).map((stop, index) => ({ ...stop, marker: String(index + 1) }));
            const visibleRequestIds = new Set((payload.requests || []).map((request) => String(request.id)));
            const visibleStops = () => stops.filter((stop) => visibleRequestIds.has(String(stop.requestId)));
            const setSummaryLoading = (loading) => {
                summaryIsLoading = loading;
                document.querySelectorAll('[data-summary-toggle]').forEach((button) => {
                    button.disabled = loading;
                    button.classList.toggle('is-loading', loading);
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.className = loading ? 'fas fa-spinner fa-spin' : (button.classList.contains('is-off') ? 'fas fa-eye-slash' : 'fas fa-eye');
                    }
                });
            };

            const numberedIcon = (className, marker, fill = '') => window.L.divIcon({
                className: '',
                html: `<span class="summary-pin-icon ${className}" data-summary-marker="${marker}" style="${fill ? `--pin-fill:${fill}` : ''}">${marker}</span>`,
                iconSize: [26, 26],
                iconAnchor: [13, 13],
                tooltipAnchor: [0, -14],
            });
            const markerRefs = new Map();
            let selectedMarkerKey = null;
            const activeMarker = (markerKey, active) => {
                const mapMarker = markerRefs.get(markerKey);
                const listItem = document.querySelector(`[data-summary-stop="${markerKey}"]`);
                listItem?.classList.toggle('active', active);
                const iconEl = mapMarker?.getElement()?.querySelector('.summary-pin-icon');
                iconEl?.classList.toggle('active', active);
                if (active && mapMarker) {
                    mapMarker.setZIndexOffset(1000);
                    mapMarker.openTooltip();
                } else if (mapMarker) {
                    mapMarker.setZIndexOffset(0);
                    mapMarker.closeTooltip();
                }
            };
            const selectMarker = (markerKey) => {
                if (selectedMarkerKey && selectedMarkerKey !== markerKey) {
                    activeMarker(selectedMarkerKey, false);
                }
                selectedMarkerKey = selectedMarkerKey === markerKey ? null : markerKey;
                activeMarker(markerKey, selectedMarkerKey === markerKey);
            };
            const addDriverPoint = (point, type, label, marker) => {
                const mapMarker = window.L.marker(point, {
                    icon: numberedIcon(type === 'pickup' ? 'driver-pickup' : 'driver-dropoff', marker),
                    title: label,
                })
                    .addTo(summaryLayer)
                    .bindTooltip(label, { permanent: false, direction: 'top', offset: [0, -10] });
                markerRefs.set(marker, mapMarker);
                mapMarker.on('mouseover', () => activeMarker(marker, true));
                mapMarker.on('mouseout', () => {
                    if (selectedMarkerKey !== marker) activeMarker(marker, false);
                });
                mapMarker.on('click', () => selectMarker(marker));
            };
            const addPassengerPin = (stop) => {
                const icon = numberedIcon(stop.status, stop.marker, stop.color);
                const label = `${stop.label} · ${stop.status}`;
                const mapMarker = window.L.marker(stop.point, { icon, title: label })
                    .addTo(summaryLayer)
                    .bindTooltip(label, { permanent: false, direction: 'top', offset: [0, -10] });
                markerRefs.set(stop.marker, mapMarker);
                mapMarker.on('mouseover', () => activeMarker(stop.marker, true));
                mapMarker.on('mouseout', () => {
                    if (selectedMarkerKey !== stop.marker) activeMarker(stop.marker, false);
                });
                mapMarker.on('click', () => selectMarker(stop.marker));
            };

            const renderStopList = () => {
                const list = document.getElementById('requestRouteSummaryStops');
                if (!list) return;
                const rows = [
                    { marker: 'A', label: 'Driver Pickup', meta: 'driver point', className: 'driver-pickup' },
                    ...stops.map((stop) => ({
                        marker: stop.marker,
                        label: stop.label,
                        meta: `${stop.kind} - ${stop.status}`,
                        className: `${stop.status} ${stop.kind}`,
                        color: stop.color,
                        requestId: String(stop.requestId),
                    })),
                    { marker: 'B', label: 'Driver Drop-off', meta: 'driver point', className: 'driver-dropoff' },
                ];

                list.innerHTML = rows.map((row) => `
                    <div class="summary-stop-item ${row.requestId && !visibleRequestIds.has(row.requestId) ? 'is-hidden' : ''}" data-summary-stop="${row.marker}" ${row.requestId ? `data-summary-request="${row.requestId}"` : ''}>
                        <span class="summary-stop-marker ${row.className}" style="${row.color ? `--pin-fill:${row.color}` : ''}">${row.marker}</span>
                        <span class="summary-stop-text">
                            <span class="summary-stop-label">${row.label}</span>
                            <span class="summary-stop-meta">${row.meta}</span>
                        </span>
                        ${row.requestId ? `<button type="button" class="summary-stop-toggle ${visibleRequestIds.has(row.requestId) ? '' : 'is-off'}" data-summary-toggle="${row.requestId}" aria-label="Toggle passenger on map"><i class="fas ${visibleRequestIds.has(row.requestId) ? 'fa-eye' : 'fa-eye-slash'}"></i></button>` : ''}
                    </div>
                `).join('');
                rows.forEach((row) => {
                    const item = list.querySelector(`[data-summary-stop="${row.marker}"]`);
                    item?.addEventListener('mouseenter', () => activeMarker(row.marker, true));
                    item?.addEventListener('mouseleave', () => {
                        if (selectedMarkerKey !== row.marker) activeMarker(row.marker, false);
                    });
                    item?.addEventListener('click', () => selectMarker(row.marker));
                });
                list.querySelectorAll('[data-summary-toggle]').forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        if (summaryIsLoading) return;
                        const requestId = String(button.dataset.summaryToggle || '');
                        if (visibleRequestIds.has(requestId)) {
                            visibleRequestIds.delete(requestId);
                        } else {
                            visibleRequestIds.add(requestId);
                        }
                        redrawSummary();
                    });
                });
            };
            renderStopList();

            const setGoogleMapsLink = (orderedStops) => {
                if (!googleMapsLink) return;
                const formatPoint = (point) => `${point.lat.toFixed(7)},${point.lng.toFixed(7)}`;
                const params = new URLSearchParams({
                    api: '1',
                    travelmode: 'driving',
                    origin: formatPoint(driverPickup),
                    destination: formatPoint(driverDropoff),
                });
                const waypoints = (orderedStops || [])
                    .map((stop) => stop.point)
                    .filter(Boolean)
                    .slice(0, 23)
                    .map(formatPoint);

                if (waypoints.length) {
                    params.set('waypoints', waypoints.join('|'));
                }

                googleMapsLink.href = `https://www.google.com/maps/dir/?${params.toString()}`;
                googleMapsLink.setAttribute('aria-disabled', 'false');
                googleMapsLink.classList.remove('disabled');
            };

            const formatKm = (value) => `${(Number(value) || 0).toFixed(2)} km`;
            const formatMinutes = (value) => {
                if (value === null || value === undefined || !Number.isFinite(Number(value))) return '-';
                const minutes = Math.max(1, Math.round(Number(value)));
                if (minutes < 60) return `${minutes} min`;
                const hours = Math.floor(minutes / 60);
                const remainder = minutes % 60;
                return remainder ? `${hours}h ${remainder}m` : `${hours}h`;
            };
            const formatMoney = (value) => `RM ${(Number(value) || 0).toFixed(2)}`;
            const renderSummaryMetrics = (originalRoute, suggestedRoute, activeStops) => {
                const grid = document.getElementById('requestRouteSummaryMetrics');
                if (!grid) return;
                const activeRequestIds = new Set((activeStops || []).map((stop) => String(stop.requestId)));
                const activeRequests = (payload.requests || []).filter((request) => activeRequestIds.has(String(request.id)));
                const originalKm = Number(originalRoute?.distanceKm) || 0;
                const suggestedKm = Number(suggestedRoute?.distanceKm) || originalKm;
                const extraKm = Math.max(0, suggestedKm - originalKm);
                const originalMinutes = originalRoute?.durationMinutes;
                const suggestedMinutes = suggestedRoute?.durationMinutes;
                const extraMinutes = originalMinutes !== null && suggestedMinutes !== null
                    ? Math.max(0, Number(suggestedMinutes) - Number(originalMinutes))
                    : null;
                const passengerFareTotal = activeRequests.reduce((sum, request) => sum + (Number(request.fare) || 0), 0);
                const includesDriver = !!payload.includesDriver;
                const driverShare = includesDriver ? (Number(payload.baseFarePerPerson) || 0) : 0;
                const totalFare = passengerFareTotal + driverShare;
                const splitText = includesDriver
                    ? `includes driver share ${formatMoney(driverShare)}`
                    : 'tidak includes driver share';
                const totalDeviation = activeRequests.reduce((sum, request) => sum + (Number(request.deviationKm) || 0), 0);
                const customStops = activeStops.length;
                const approvedCount = activeRequests.filter((request) => request.status === 'approved').length;
                const pendingCount = activeRequests.filter((request) => request.status === 'pending').length;

                grid.innerHTML = `
                    <div class="summary-metric-item">
                        <span class="summary-metric-label">Route distance</span>
                        <span class="summary-metric-value">${formatKm(suggestedKm)}</span>
                        <span class="summary-metric-meta">Original ${formatKm(originalKm)} / extra ${formatKm(extraKm)}</span>
                    </div>
                    <div class="summary-metric-item">
                        <span class="summary-metric-label">Estimated time</span>
                        <span class="summary-metric-value">${formatMinutes(suggestedMinutes)}</span>
                        <span class="summary-metric-meta">Original ${formatMinutes(originalMinutes)} / extra ${formatMinutes(extraMinutes)}</span>
                    </div>
                    <div class="summary-metric-item">
                        <span class="summary-metric-label">Passenger totals</span>
                        <span class="summary-metric-value">${formatMoney(totalFare)}</span>
                        <span class="summary-metric-meta">${splitText} / ${approvedCount} approved / ${pendingCount} pending / ${customStops} custom stops / ${formatKm(totalDeviation)} deviation</span>
                    </div>
                `;
            };

            const redrawSummary = async () => {
                setSummaryLoading(true);
                const activeStops = visibleStops();
                try {
                    const [originalRoute, suggestedRoute] = await Promise.all([
                        originalRouteCache ? Promise.resolve(originalRouteCache) : fetchRoute([driverPickup, driverDropoff]),
                        shortestMiddleRoute(activeStops),
                    ]);
                    originalRouteCache = originalRoute;
                    summaryLayer.clearLayers();
                    markerRefs.clear();
                    selectedMarkerKey = null;

                    originalLayer.clearLayers();
                    window.L.polyline(originalRoute.points, {
                        color: '#64748b',
                        weight: 9,
                        opacity: 0.38,
                        lineCap: 'round',
                        interactive: false,
                    }).addTo(originalLayer);

                    if (suggestedRoute?.points?.length > 1) {
                        window.L.polyline(suggestedRoute.points, {
                            color: '#1d4ed8',
                            weight: 5,
                            opacity: 0.92,
                            lineCap: 'round',
                            interactive: false,
                        }).addTo(summaryLayer);
                    }

                    addDriverPoint(driverPickup, 'pickup', 'Pickup Driver', 'A');
                    addDriverPoint(driverDropoff, 'dropoff', 'Driver Drop-off', 'B');
                    activeStops.forEach(addPassengerPin);
                    renderStopList();
                    setGoogleMapsLink(suggestedRoute?.order || activeStops);
                    renderSummaryMetrics(originalRoute, suggestedRoute, activeStops);

                    const bounds = window.L.latLngBounds([
                        ...originalRoute.points,
                        ...(suggestedRoute?.points ?? []),
                        ...activeStops.map((stop) => stop.point),
                    ]);
                    if (!summaryBoundsFitted && bounds.isValid()) {
                        map.fitBounds(bounds, { padding: [28, 28] });
                        summaryBoundsFitted = true;
                    }
                    setTimeout(() => map.invalidateSize(), 100);
                } finally {
                    setSummaryLoading(false);
                }
            };
            redrawSummary();
        })();

        (() => {
            const modal = document.getElementById('tripDetailsModal');
            const closeBtn = document.getElementById('tripDetailsCloseBtn');
            const detailButtons = document.querySelectorAll('.open-trip-modal-btn');
            if (!modal || !closeBtn || detailButtons.length === 0) return;
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const tripIdsEl = document.getElementById('tripModalTripIds');
            const modeEl = document.getElementById('tripModalMode');
            const pairHintEl = document.getElementById('tripModalPairHint');
            const routeNameEl = document.getElementById('tripModalRouteName');
            const driverEl = document.getElementById('tripModalDriver');
            const driverAvatarEl = document.getElementById('tripModalDriverAvatar');
            const driverEmailEl = document.getElementById('tripModalDriverEmail');
            const statusEl = document.getElementById('tripModalStatus');
            const outboundTimeEl = document.getElementById('tripModalOutboundTime');
            const fareLabelEl = document.getElementById('tripModalFareLabel');
            const fareValueEl = document.getElementById('tripModalFareValue');
            const totalPassengersEl = document.getElementById('tripModalTotalPassengers');
            const splitTypeEl = document.getElementById('tripModalSplitType');
            const passengerCountEl = document.getElementById('tripModalPassengerCount');
            const passengerListEl = document.getElementById('tripModalPassengerList');
            const pickupPointEl = document.getElementById('tripModalPickupPoint');
            const destinationPointEl = document.getElementById('tripModalDestinationPoint');
            const pointALabelEl = document.getElementById('tripModalPointALabel');
            const pointBLabelEl = document.getElementById('tripModalPointBLabel');
            const mapEl = document.getElementById('tripModalMap');
            const whatsappEl = document.getElementById('tripModalWhatsapp');
            const emailEl = document.getElementById('tripModalEmail');

            let miniMap = null;
            let routeLayer = null;
            let markerLayer = null;

            const toNum = (v) => {
                const n = Number.parseFloat(String(v ?? '').trim());
                return Number.isFinite(n) ? n : null;
            };
            const toStatusSlug = (value) => String(value || '')
                .trim()
                .toLowerCase()
                .replace(/\s+/g, '_')
                .replace(/[^a-z0-9_]/g, '');
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const renderPassengerList = (participantsRaw, driverIdRaw = null) => {
                if (!passengerListEl || !passengerCountEl) return;
                const participants = Array.isArray(participantsRaw) ? participantsRaw : [];
                const toBool = (value) => value === true || value === 1 || value === '1';
                const driverId = Number.parseInt(String(driverIdRaw ?? ''), 10);
                const passengers = participants.filter((item) => {
                    if (!item || (!item.name && !item.email)) return false;
                    if (toBool(item?.is_driver)) return false;
                    const uid = Number.parseInt(String(item?.user_id ?? ''), 10);
                    if (Number.isFinite(driverId) && driverId > 0 && Number.isFinite(uid) && uid === driverId) return false;
                    return true;
                });

                passengerCountEl.textContent = `${passengers.length} passengers`;

                if (passengers.length === 0) {
                    passengerListEl.innerHTML = '<div class="trip-passenger-email">No passenger records found for this trip.</div>';
                    return;
                }

                passengerListEl.innerHTML = passengers.map((item) => {
                    const name = escapeHtml(item?.name || '-');
                    const email = escapeHtml(item?.email || '');
                    const avatarHtml = item?.photo_url
                        ? `<span class="trip-passenger-avatar"><img src="${escapeHtml(item.photo_url)}" alt="${name}"></span>`
                        : `<span class="trip-passenger-avatar">${escapeHtml((item?.name || 'U').trim().charAt(0).toUpperCase() || 'U')}</span>`;

                    return `
                        <div class="trip-passenger-item">
                            ${avatarHtml}
                            <div class="trip-passenger-meta">
                                <span class="trip-passenger-name">${name}</span>
                                <span class="trip-passenger-email">${email || '-'}</span>
                            </div>
                            <span class="trip-passenger-role">Passenger</span>
                        </div>
                    `;
                }).join('');
            };

            const ensureMap = () => {
                if (!mapEl || typeof window.L === 'undefined') return null;
                if (miniMap) return miniMap;

                mapEl.innerHTML = '';
                miniMap = window.L.map(mapEl, {
                    zoomControl: false,
                    attributionControl: false,
                    dragging: false,
                    scrollWheelZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    keyboard: false,
                    tap: false,
                    touchZoom: false,
                });

                window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                }).addTo(miniMap);

                return miniMap;
            };

            const passengerStopsFromPayload = (routePointsPayload) => {
                const stops = [];
                (Array.isArray(routePointsPayload) ? routePointsPayload : []).forEach((item, index) => {
                    const sequence = index + 1;
                    const pickup = item?.pickup || null;
                    const dropoff = item?.dropoff || null;
                    const pickupLat = toNum(pickup?.lat);
                    const pickupLng = toNum(pickup?.lng);
                    const dropoffLat = toNum(dropoff?.lat);
                    const dropoffLng = toNum(dropoff?.lng);

                    if (pickupLat !== null && pickupLng !== null) {
                        stops.push({
                            type: 'pickup',
                            sequence,
                            lat: pickupLat,
                            lng: pickupLng,
                            label: pickup?.label || `${item?.name || 'Passenger'} pickup`,
                        });
                    }
                    if (dropoffLat !== null && dropoffLng !== null) {
                        stops.push({
                            type: 'dropoff',
                            sequence,
                            lat: dropoffLat,
                            lng: dropoffLng,
                            label: dropoff?.label || `${item?.name || 'Passenger'} drop-off`,
                        });
                    }
                });

                return stops;
            };

            const drawMap = async (pickupLat, pickupLng, destinationLat, destinationLng, routePointsPayload = []) => {
                const map = ensureMap();
                if (!map) return;
                if ([pickupLat, pickupLng, destinationLat, destinationLng].some((v) => v === null)) return;

                if (routeLayer) { map.removeLayer(routeLayer); routeLayer = null; }
                if (markerLayer) { map.removeLayer(markerLayer); markerLayer = null; }

                const passengerStops = passengerStopsFromPayload(routePointsPayload);
                const markerLayers = [
                    window.L.circleMarker([pickupLat, pickupLng], {
                        radius: 6, color: '#fff', weight: 2, fillColor: '#16a34a', fillOpacity: 1
                    }).bindTooltip('Pickup Driver', { direction: 'top', offset: [0, -8] }),
                    window.L.circleMarker([destinationLat, destinationLng], {
                        radius: 6, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1
                    }).bindTooltip('Driver Drop-off', { direction: 'top', offset: [0, -8] }),
                ];

                passengerStops.forEach((stop) => {
                    const icon = window.L.divIcon({
                        className: '',
                        html: `<span class="trip-passenger-map-pin ${stop.type === 'dropoff' ? 'dropoff' : 'pickup'}">${stop.sequence}</span>`,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10],
                    });
                    markerLayers.push(
                        window.L.marker([stop.lat, stop.lng], { icon, interactive: true })
                            .bindTooltip(stop.label, { direction: 'top', offset: [0, -10] })
                    );
                });

                markerLayer = window.L.layerGroup(markerLayers).addTo(map);

                const waypointPoints = [
                    [pickupLat, pickupLng],
                    ...passengerStops.map((stop) => [stop.lat, stop.lng]),
                    [destinationLat, destinationLng],
                ];

                map.fitBounds(window.L.latLngBounds(waypointPoints), { padding: [16, 16] });

                const url = 'https://router.project-osrm.org/route/v1/driving/'
                    + waypointPoints
                        .map((point) => `${encodeURIComponent(point[1])},${encodeURIComponent(point[0])}`)
                        .join(';')
                    + '?overview=full&geometries=geojson&alternatives=false&steps=false';

                try {
                    const response = await fetch(url, { method: 'GET' });
                    if (!response.ok) throw new Error('route');
                    const payload = await response.json();
                    const geometry = payload?.routes?.[0]?.geometry?.coordinates ?? [];
                    const latLngs = geometry
                        .map((coord) => [Number(coord[1]), Number(coord[0])])
                        .filter((coord) => Number.isFinite(coord[0]) && Number.isFinite(coord[1]));

                    if (latLngs.length > 1) {
                        routeLayer = window.L.polyline(latLngs, { color: '#1d4ed8', weight: 4, opacity: 0.95 }).addTo(map);
                        map.fitBounds(routeLayer.getBounds(), { padding: [16, 16] });
                    } else {
                        routeLayer = window.L.polyline(waypointPoints, {
                            color: '#60a5fa', weight: 3, opacity: 0.9, dashArray: '8 6'
                        }).addTo(map);
                    }
                } catch (_e) {
                    routeLayer = window.L.polyline(waypointPoints, {
                        color: '#60a5fa', weight: 3, opacity: 0.9, dashArray: '8 6'
                    }).addTo(map);
                }
            };

            detailButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const tripId = String(btn.dataset.tripId || '-');
                    const pairedTripId = String(btn.dataset.pairedTripId || '').trim();
                    const isTwoWay = String(btn.dataset.mode || '').toLowerCase().includes('two-way');
                    const driverId = Number.parseInt(String(btn.dataset.driverId || ''), 10);
                    const driverEmail = String(btn.dataset.driverEmail || '').trim();
                    const driverWhatsappUrl = String(btn.dataset.driverWhatsappUrl || '').trim();
                    const driverPhoneRaw = String(btn.dataset.driverPhone || '');
                    let participantsPayload = [];
                    try {
                        const encoded = String(btn.dataset.participantsB64 || '').trim();
                        if (encoded) {
                            participantsPayload = JSON.parse(atob(encoded));
                        } else {
                            participantsPayload = JSON.parse(btn.dataset.participants || '[]');
                        }
                    } catch (_e) {
                        participantsPayload = [];
                    }
                    let routePointsPayload = [];
                    try {
                        const encoded = String(btn.dataset.routePointsB64 || '').trim();
                        routePointsPayload = encoded ? JSON.parse(atob(encoded)) : [];
                    } catch (_e) {
                        routePointsPayload = [];
                    }
                    const digitsRaw = driverPhoneRaw.replace(/\D+/g, '');
                    let waDigits = digitsRaw.replace(/^00+/, '');
                    if (/^01\d{8,9}$/.test(waDigits)) {
                        waDigits = `60${waDigits.slice(1)}`;
                    }
                    const waUrl = /^https?:\/\/wa\.me\/\d+$/i.test(driverWhatsappUrl)
                        ? driverWhatsappUrl
                        : (waDigits ? `https://wa.me/${waDigits}` : '');

                    if (tripIdsEl) tripIdsEl.textContent = pairedTripId ? `#${tripId} & #${pairedTripId}` : `#${tripId}`;
                    if (modeEl) modeEl.textContent = btn.dataset.mode || '-';
                    if (pairHintEl) {
                        if (isTwoWay && pairedTripId) {
                            pairHintEl.textContent = `Paired trip: Trip #${pairedTripId}`;
                            pairHintEl.style.display = 'block';
                        } else {
                            pairHintEl.textContent = '';
                            pairHintEl.style.display = 'none';
                        }
                    }
                    if (routeNameEl) routeNameEl.textContent = btn.dataset.routeName || '-';
                    if (driverEl) driverEl.textContent = btn.dataset.driverName || '-';
                    if (driverAvatarEl) driverAvatarEl.textContent = ((btn.dataset.driverName || 'D').trim().charAt(0) || 'D').toUpperCase();
                    if (driverEmailEl) driverEmailEl.textContent = driverEmail || '-';
                    if (statusEl) {
                        const statusText = btn.dataset.status || '-';
                        const slug = toStatusSlug(statusText);
                        statusEl.textContent = statusText;
                        statusEl.className = `trip-modal-value trip-status-badge trip-status-${slug || 'draft'}`;
                    }
                    if (outboundTimeEl) outboundTimeEl.textContent = btn.dataset.outboundDatetime || '-';
                    if (fareLabelEl) fareLabelEl.textContent = btn.dataset.fareLabel || 'Fare';
                    if (fareValueEl) fareValueEl.textContent = btn.dataset.fareDisplay || '-';
                    const totalPassengersText = btn.dataset.totalPassengers || '0';
                    if (totalPassengersEl) totalPassengersEl.textContent = totalPassengersText;
                    if (splitTypeEl) splitTypeEl.textContent = btn.dataset.splitType || '-';
                    renderPassengerList(participantsPayload, driverId);
                    if (passengerCountEl && (!participantsPayload || participantsPayload.length === 0)) {
                        const n = Number.parseInt(totalPassengersText, 10);
                        if (Number.isFinite(n) && n > 0) {
                            passengerCountEl.textContent = `${n} passengers`;
                        }
                    }
                    if (pointALabelEl) {
                        pointALabelEl.innerHTML = '<i class="fa-solid fa-location-dot"></i>Pickup Point';
                    }
                    if (pointBLabelEl) {
                        pointBLabelEl.innerHTML = '<i class="fa-solid fa-flag-checkered"></i>Destination Point';
                    }
                    if (pickupPointEl) pickupPointEl.textContent = btn.dataset.pickupName || '-';
                    if (destinationPointEl) destinationPointEl.textContent = btn.dataset.destinationName || '-';
                    if (emailEl) {
                        if (driverEmail) {
                            emailEl.classList.remove('is-disabled');
                            emailEl.setAttribute('href', `mailto:${driverEmail}`);
                        } else {
                            emailEl.classList.add('is-disabled');
                            emailEl.setAttribute('href', '#');
                        }
                    }
                    if (whatsappEl) {
                        if (waUrl) {
                            whatsappEl.classList.remove('is-disabled');
                            whatsappEl.setAttribute('href', waUrl);
                        } else {
                            whatsappEl.classList.add('is-disabled');
                            whatsappEl.setAttribute('href', '#');
                        }
                    }

                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');

                    const pickupLat = toNum(btn.dataset.pickupLat);
                    const pickupLng = toNum(btn.dataset.pickupLng);
                    const destinationLat = toNum(btn.dataset.destinationLat);
                    const destinationLng = toNum(btn.dataset.destinationLng);

                    setTimeout(() => {
                        drawMap(pickupLat, pickupLng, destinationLat, destinationLng, routePointsPayload).then(() => {
                            if (miniMap) miniMap.invalidateSize();
                        });
                    }, 40);
                });
            });

            const closeModal = () => {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            };
            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
        })();

        (() => {
            const rejectModal = document.getElementById('rejectModal');
            const rejectCancelBtn = document.getElementById('rejectModalCancel');
            const rejectCloseTopBtn = document.getElementById('rejectModalCloseTop');
            const rejectForm = document.getElementById('rejectModalForm');
            const rejectPassengerEl = document.getElementById('rejectModalPassenger');
            const rejectTripEl = document.getElementById('rejectModalTrip');
            const rejectReasonEl = document.getElementById('rejectModalReason');
            if (!rejectModal || !rejectCancelBtn || !rejectForm) return;

            const openRejectModal = (action, passenger, trip) => {
                rejectForm.setAttribute('action', action || '');
                if (rejectPassengerEl) rejectPassengerEl.textContent = passenger || '-';
                if (rejectTripEl) rejectTripEl.textContent = trip || '-';
                rejectModal.classList.add('show');
                rejectModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => rejectReasonEl?.focus(), 30);
            };

            const closeRejectModal = () => {
                rejectModal.classList.remove('show');
                rejectModal.setAttribute('aria-hidden', 'true');
                rejectForm.setAttribute('action', '');
                if (rejectReasonEl) rejectReasonEl.value = '';
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.open-reject-btn');
                if (!button) return;
                openRejectModal(button.dataset.action, button.dataset.passenger, button.dataset.trip);
            });

            rejectCancelBtn.addEventListener('click', closeRejectModal);
            if (rejectCloseTopBtn) rejectCloseTopBtn.addEventListener('click', closeRejectModal);
            rejectModal.addEventListener('click', (event) => {
                if (event.target === rejectModal) closeRejectModal();
            });
        })();

        (() => {
            const approveModal = document.getElementById('approveModal');
            const approveCancelBtn = document.getElementById('approveModalCancel');
            const approveCloseTopBtn = document.getElementById('approveModalCloseTop');
            const approveForm = document.getElementById('approveModalForm');
            const approvePassengerEl = document.getElementById('approveModalPassenger');
            const approveTripEl = document.getElementById('approveModalTrip');
            const approveReasonEl = document.getElementById('approveModalReason');
            if (!approveModal || !approveCancelBtn || !approveForm) return;

            const openApproveModal = (action, passenger, trip) => {
                approveForm.setAttribute('action', action || '');
                if (approvePassengerEl) approvePassengerEl.textContent = passenger || '-';
                if (approveTripEl) approveTripEl.textContent = trip || '-';
                approveModal.classList.add('show');
                approveModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => approveReasonEl?.focus(), 30);
            };

            const closeApproveModal = () => {
                approveModal.classList.remove('show');
                approveModal.setAttribute('aria-hidden', 'true');
                approveForm.setAttribute('action', '');
                if (approveReasonEl) approveReasonEl.value = '';
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.open-approve-btn');
                if (!button) return;
                openApproveModal(button.dataset.action, button.dataset.passenger, button.dataset.trip);
            });

            approveCancelBtn.addEventListener('click', closeApproveModal);
            if (approveCloseTopBtn) approveCloseTopBtn.addEventListener('click', closeApproveModal);
            approveModal.addEventListener('click', (event) => {
                if (event.target === approveModal) closeApproveModal();
            });
        })();

        (() => {
            const infoModal = document.getElementById('ratingInfoModal');
            const infoCloseBtn = document.getElementById('ratingInfoCloseBtn');
            const infoCloseTopBtn = document.getElementById('ratingInfoCloseTop');
            if (!infoModal || !infoCloseBtn) return;

            const openInfoModal = () => {
                infoModal.classList.add('show');
                infoModal.setAttribute('aria-hidden', 'false');
            };

            const closeInfoModal = () => {
                infoModal.classList.remove('show');
                infoModal.setAttribute('aria-hidden', 'true');
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.open-rating-info-btn');
                if (!button) return;
                openInfoModal();
            });

            infoCloseBtn.addEventListener('click', closeInfoModal);
            if (infoCloseTopBtn) infoCloseTopBtn.addEventListener('click', closeInfoModal);
            infoModal.addEventListener('click', (event) => {
                if (event.target === infoModal) closeInfoModal();
            });
        })();

        (() => {
            const listContainer = document.getElementById('tripRequestsListContainer');
            const paginationContainer = document.getElementById('tripRequestsPaginationContainer');
            const seatTextEl = document.getElementById('tripSeatText');
            const statusTextEl = document.getElementById('tripStatusText');
            const publicJoinTextEl = document.getElementById('tripPublicJoinText');
            const publicJoinMetaEl = document.getElementById('tripPublicJoinMeta');
            const publicJoinIconEl = document.getElementById('tripPublicJoinIcon');
            const searchInput = document.getElementById('requestSearchInput');
            const statusFilter = document.getElementById('requestStatusFilter');
            const emptyFilterEl = document.getElementById('requestFilterEmpty');
            if (!listContainer || !paginationContainer) return;

            const endpoint = window.CH_TRIPREQ.endpoint;
            const pollMs = 5000;
            let inFlight = false;

            const syncTripMeta = (tripPayload) => {
                if (!tripPayload || typeof tripPayload !== 'object') return;
                if (seatTextEl && typeof tripPayload.available_seats_text === 'string') {
                    seatTextEl.textContent = tripPayload.available_seats_text;
                }
                if (statusTextEl && typeof tripPayload.status_text === 'string') {
                    statusTextEl.textContent = tripPayload.status_text;
                }
                if (publicJoinTextEl && publicJoinMetaEl && publicJoinIconEl && tripPayload.visibility === 'public') {
                    const open = !!tripPayload.is_open_for_request;
                    publicJoinTextEl.textContent = `Public Join: ${open ? 'Open' : 'Close'}`;
                    publicJoinIconEl.className = `fas ${open ? 'fa-lock-open' : 'fa-lock'}`;
                    publicJoinMetaEl.classList.toggle('public-open', open);
                    publicJoinMetaEl.classList.toggle('public-closed', !open);
                }
            };

            const applyRequestFilters = () => {
                const query = (searchInput?.value || '').trim().toLowerCase();
                const status = (statusFilter?.value || 'all').toLowerCase();
                const items = Array.from(listContainer.querySelectorAll('.request-item'));
                let visibleCount = 0;

                items.forEach((item) => {
                    const searchText = String(item.dataset.requestSearch || item.textContent || '').toLowerCase();
                    const itemStatus = String(item.dataset.requestStatus || '').toLowerCase();
                    const matchesSearch = !query || searchText.includes(query);
                    const matchesStatus = status === 'all' || itemStatus === status;
                    const visible = matchesSearch && matchesStatus;
                    item.hidden = !visible;
                    if (visible) visibleCount += 1;
                });

                if (emptyFilterEl) {
                    emptyFilterEl.hidden = visibleCount > 0 || items.length === 0;
                }
            };
            searchInput?.addEventListener('input', applyRequestFilters);
            statusFilter?.addEventListener('change', applyRequestFilters);
            applyRequestFilters();

            const poll = async () => {
                if (inFlight || document.visibilityState !== 'visible') return;
                inFlight = true;
                try {
                    const response = await fetch(endpoint + '?page=' + encodeURIComponent(window.CH_TRIPREQ.page), {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) return;
                    const payload = await response.json();
                    if (typeof payload?.requests_html === 'string') {
                        listContainer.innerHTML = payload.requests_html;
                        applyRequestFilters();
                    }
                    if (typeof payload?.pagination_html === 'string') {
                        paginationContainer.innerHTML = payload.pagination_html;
                    }
                    syncTripMeta(payload?.trip);
                } catch (_error) {
                } finally {
                    inFlight = false;
                }
            };

            window.setInterval(poll, pollMs);
        })();
