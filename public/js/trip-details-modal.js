/* Shared "Trip Details" popup — opens for any element with class
   "open-trip-modal-btn" carrying the full data-* set (see the click handler
   below for every key read). Originally lived only in trips-index.js; moved
   here so other pages (e.g. the chat thread header) can trigger the exact
   same popup by loading this one file plus trips.css and
   resources/views/trips/partials/trip-details-modal.blade.php, without
   pulling in the rest of trips-index.js's list-page-only logic. */

/* Shared by every cancel/delete trip form (list row, table row, detail modal,
   bulk bar) — confirms, then optionally collects a reason into the form's
   hidden "reason" input so TripService::delete() can keep it on the
   trip_cancellation_logs snapshot instead of the reason being lost. */
function confirmTripCancel(form, confirmMessage) {
    if (!window.confirm(confirmMessage || 'Cancel this trip? This will delete the trip and all related records.')) {
        return false;
    }
    const reason = window.prompt('Optional: why is this being cancelled? (leave blank to skip)', '');
    if (reason === null) {
        return false;
    }
    const reasonInput = form.querySelector('input[name="reason"]');
    if (reasonInput) {
        reasonInput.value = reason.trim();
    }
    return true;
}

(() => {
    const modal      = document.getElementById('tripDetailsModal');
    const closeBtn   = document.getElementById('tripDetailsCloseBtn');
    if (!modal || !closeBtn) return;
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const tripIdsEl          = document.getElementById('tripModalTripIds');
    const modeEl             = document.getElementById('tripModalMode');
    const routeNameEl        = document.getElementById('tripModalRouteName');
    const driverEl           = document.getElementById('tripModalDriver');
    const driverAvatarEl     = document.getElementById('tripModalDriverAvatar');
    const driverEmailEl      = document.getElementById('tripModalDriverEmail');
    const statusEl           = document.getElementById('tripModalStatus');
    const outboundTimeEl     = document.getElementById('tripModalOutboundTime');
    const fareLabelEl        = document.getElementById('tripModalFareLabel');
    const fareValueEl        = document.getElementById('tripModalFareValue');
    const totalPassengersEl  = document.getElementById('tripModalTotalPassengers');
    const splitTypeEl        = document.getElementById('tripModalSplitType');
    const passengerCountEl   = document.getElementById('tripModalPassengerCount');
    const passengerListEl    = document.getElementById('tripModalPassengerList');
    const pickupPointEl      = document.getElementById('tripModalPickupPoint');
    const destinationPointEl = document.getElementById('tripModalDestinationPoint');
    const pointALabelEl      = document.getElementById('tripModalPointALabel');
    const pointBLabelEl      = document.getElementById('tripModalPointBLabel');
    const mapEl              = document.getElementById('tripModalMap');
    const whatsappEl         = document.getElementById('tripModalWhatsapp');
    const emailEl            = document.getElementById('tripModalEmail');
    const manageActionsEl    = document.getElementById('tripModalManageActions');
    const contactActionsEl   = document.getElementById('tripModalContactActions');
    const editBtnEl          = document.getElementById('tripModalEditBtn');
    const deleteFormEl       = document.getElementById('tripModalDeleteForm');
    const requestsBtnEl      = document.getElementById('tripModalRequestsBtn');

    const warnIfUnavailable = (el, label) => {
        if (!el) return;
        el.addEventListener('click', (event) => {
            if (el.dataset.unavailable === '1') {
                event.preventDefault();
                if (window.showToast) window.showToast(`${label} not available for this driver.`, 'error');
            }
        });
    };
    warnIfUnavailable(emailEl, 'Email');
    warnIfUnavailable(whatsappEl, 'WhatsApp');

    let miniMap    = null;
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
            const avatarStyle = item?.photo_url ? '' : (' style="' + window.CarpoolAvatar.bgStyle(item?.user_id) + '"');
            const avatarHtml = `<span class="trip-passenger-avatar"${avatarStyle}>${window.CarpoolAvatar.innerHtml({ photoUrl: item?.photo_url, name: item?.name })}</span>`;
            return `
                <div class="trip-passenger-item">
                    ${avatarHtml}
                    <div class="trip-passenger-meta">
                        <span class="trip-passenger-name">${name}</span>
                        <span class="trip-passenger-email">${email || '-'}</span>
                    </div>
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
            const sequence  = index + 1;
            const pickup    = item?.pickup  || null;
            const dropoff   = item?.dropoff || null;
            const pickupLat  = toNum(pickup?.lat);
            const pickupLng  = toNum(pickup?.lng);
            const dropoffLat = toNum(dropoff?.lat);
            const dropoffLng = toNum(dropoff?.lng);

            if (pickupLat !== null && pickupLng !== null) {
                stops.push({ type: 'pickup',  sequence, lat: pickupLat,  lng: pickupLng,  label: pickup?.label  || `${item?.name || 'Passenger'} pickup` });
            }
            if (dropoffLat !== null && dropoffLng !== null) {
                stops.push({ type: 'dropoff', sequence, lat: dropoffLat, lng: dropoffLng, label: dropoff?.label || `${item?.name || 'Passenger'} drop-off` });
            }
        });
        return stops;
    };

    const drawMap = async (pickupLat, pickupLng, destinationLat, destinationLng, routePointsPayload = []) => {
        const map = ensureMap();
        if (!map) return;
        if ([pickupLat, pickupLng, destinationLat, destinationLng].some((v) => v === null)) return;

        if (routeLayer)  { map.removeLayer(routeLayer);  routeLayer  = null; }
        if (markerLayer) { map.removeLayer(markerLayer); markerLayer = null; }

        const passengerStops = passengerStopsFromPayload(routePointsPayload);
        const markerLayers   = [
            window.L.circleMarker([pickupLat, pickupLng],           { radius: 6, color: '#fff', weight: 2, fillColor: '#16a34a', fillOpacity: 1 }).bindTooltip('Pickup Driver',   { direction: 'top', offset: [0, -8] }),
            window.L.circleMarker([destinationLat, destinationLng], { radius: 6, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1 }).bindTooltip('Driver Drop-off', { direction: 'top', offset: [0, -8] }),
        ];

        passengerStops.forEach((stop) => {
            const icon = window.L.divIcon({
                className: '',
                html: `<span class="trip-passenger-map-pin ${stop.type === 'dropoff' ? 'dropoff' : ''}">${stop.sequence}</span>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10],
            });
            markerLayers.push(
                window.L.marker([stop.lat, stop.lng], { icon, interactive: true })
                    .bindTooltip(escapeHtml(stop.label), { direction: 'top', offset: [0, -10] })
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
            const payload   = await response.json();
            const geometry  = payload?.routes?.[0]?.geometry?.coordinates ?? [];
            const latLngs   = geometry
                .map((coord) => [Number(coord[1]), Number(coord[0])])
                .filter((coord) => Number.isFinite(coord[0]) && Number.isFinite(coord[1]));

            if (latLngs.length > 1) {
                routeLayer = window.L.polyline(latLngs, { color: '#1d4ed8', weight: 4, opacity: 0.95 }).addTo(map);
                map.fitBounds(routeLayer.getBounds(), { padding: [16, 16] });
            } else {
                routeLayer = window.L.polyline(waypointPoints, { color: '#60a5fa', weight: 3, opacity: 0.9, dashArray: '8 6' }).addTo(map);
            }
        } catch (_e) {
            routeLayer = window.L.polyline(waypointPoints, { color: '#60a5fa', weight: 3, opacity: 0.9, dashArray: '8 6' }).addTo(map);
        }
    };

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('.open-trip-modal-btn');
        if (!btn) return;
            const tripId            = String(btn.dataset.tripId || '-');
            const tripRef           = String(btn.dataset.tripRef || '').trim() || (tripId !== '-' ? `TRP-${tripId.padStart(5, '0')}` : '-');
            const driverId          = Number.parseInt(String(btn.dataset.driverId || ''), 10);
            const driverEmail       = String(btn.dataset.driverEmail || '').trim();
            const driverWhatsappUrl = String(btn.dataset.driverWhatsappUrl || '').trim();
            const driverPhoneRaw    = String(btn.dataset.driverPhone || '');

            let participantsPayload = [];
            try {
                const encoded = String(btn.dataset.participantsB64 || '').trim();
                participantsPayload = encoded ? JSON.parse(atob(encoded)) : JSON.parse(btn.dataset.participants || '[]');
            } catch (_e) { participantsPayload = []; }

            let routePointsPayload = [];
            try {
                const encoded = String(btn.dataset.routePointsB64 || '').trim();
                routePointsPayload = encoded ? JSON.parse(atob(encoded)) : [];
            } catch (_e) { routePointsPayload = []; }

            const digitsRaw = driverPhoneRaw.replace(/\D+/g, '');
            let waDigits    = digitsRaw.replace(/^00+/, '');
            if (/^01\d{8,9}$/.test(waDigits)) { waDigits = `60${waDigits.slice(1)}`; }
            const waUrl = /^https?:\/\/wa\.me\/\d+$/i.test(driverWhatsappUrl)
                ? driverWhatsappUrl
                : (waDigits ? `https://wa.me/${waDigits}` : '');

            if (tripIdsEl)      tripIdsEl.textContent      = tripRef;
            if (modeEl)         modeEl.textContent          = btn.dataset.mode || '-';
            if (routeNameEl)    routeNameEl.textContent     = btn.dataset.routeName || '-';
            if (driverEl)       driverEl.textContent        = btn.dataset.driverName || '-';
            if (driverAvatarEl) {
                const photoUrl = btn.dataset.driverPhoto || '';
                driverAvatarEl.innerHTML = window.CarpoolAvatar.innerHtml({ photoUrl: photoUrl, name: btn.dataset.driverName });
                driverAvatarEl.style.cssText = photoUrl ? '' : window.CarpoolAvatar.bgStyle(btn.dataset.driverId);
            }
            if (driverEmailEl)  driverEmailEl.textContent   = driverEmail || '-';
            if (statusEl) {
                const statusText = btn.dataset.status || '-';
                const slug       = toStatusSlug(statusText);
                statusEl.textContent = statusText;
                statusEl.className   = `trip-modal-value trip-status-badge trip-status-${slug || 'draft'}`;
            }
            if (outboundTimeEl)    outboundTimeEl.textContent    = btn.dataset.outboundDatetime || '-';
            if (fareLabelEl)       fareLabelEl.textContent       = btn.dataset.fareLabel || 'Fare';
            if (fareValueEl)       fareValueEl.textContent       = btn.dataset.fareDisplay || '-';
            const totalPassengersText = btn.dataset.totalPassengers || '0';
            if (totalPassengersEl) totalPassengersEl.textContent = totalPassengersText;
            if (splitTypeEl)       splitTypeEl.textContent       = btn.dataset.splitType || '-';

            renderPassengerList(participantsPayload, driverId);

            if (passengerCountEl && (!participantsPayload || participantsPayload.length === 0)) {
                const n = Number.parseInt(totalPassengersText, 10);
                if (Number.isFinite(n) && n > 0) {
                    passengerCountEl.textContent = `${n} passengers`;
                }
            }

            if (pointALabelEl) pointALabelEl.textContent = 'Pickup Point';
            if (pointBLabelEl) pointBLabelEl.textContent = 'Destination Point';
            if (pickupPointEl)      pickupPointEl.textContent      = btn.dataset.pickupName || '-';
            if (destinationPointEl) destinationPointEl.textContent = btn.dataset.destinationName || '-';

            if (emailEl) {
                emailEl.setAttribute('href', driverEmail ? `mailto:${driverEmail}` : '#');
                emailEl.dataset.unavailable = driverEmail ? '' : '1';
            }
            if (whatsappEl) {
                whatsappEl.setAttribute('href', waUrl || '#');
                whatsappEl.dataset.unavailable = waUrl ? '' : '1';
            }

            // Action row: trip owners (or admins) get manage actions (Edit/Delete),
            // matching the same buttons and flow as the Action column in the list;
            // everyone else gets contact actions instead.
            const canManage = String(btn.dataset.canManage || '0') === '1';
            if (manageActionsEl) manageActionsEl.style.display = canManage ? '' : 'none';
            if (contactActionsEl) contactActionsEl.style.display = canManage ? 'none' : '';
            if (canManage) {
                if (editBtnEl) editBtnEl.setAttribute('href', btn.dataset.editUrl || '#');
                const canDelete = String(btn.dataset.canDelete || '0') === '1';
                if (deleteFormEl) {
                    deleteFormEl.style.display = canDelete ? '' : 'none';
                    deleteFormEl.setAttribute('action', btn.dataset.deleteUrl || '#');
                }
            }

            // "Manage requests" trigger, reusing the exact same popup as
            // trips/index.blade.php's own row button (public/js/
            // trip-requests-modal.js) — this button just needs class
            // "open-trip-requests-review" plus that popup's own expected
            // dataset; most of it (trip id/ref/route/pickup/destination) is
            // already on this same trigger button under other names.
            if (requestsBtnEl) {
                const canManageRequests = canManage && String(btn.dataset.canManageRequests || '0') === '1';
                requestsBtnEl.style.display = canManageRequests ? '' : 'none';
                if (canManageRequests) {
                    requestsBtnEl.dataset.requestsB64 = btn.dataset.requestsB64 || '';
                    requestsBtnEl.dataset.routeName = btn.dataset.routeName || '';
                    requestsBtnEl.dataset.tripId = btn.dataset.tripId || '';
                    requestsBtnEl.dataset.tripRef = tripRef;
                    requestsBtnEl.dataset.tripDatetime = btn.dataset.outboundDatetime || '-';
                    requestsBtnEl.dataset.tripStatus = btn.dataset.status || '-';
                    requestsBtnEl.dataset.isOpenForRequest = btn.dataset.requestsIsOpenForRequest || '0';
                    requestsBtnEl.dataset.seats = btn.dataset.requestsSeats || '-';
                    requestsBtnEl.dataset.toggleUrl = btn.dataset.requestsToggleUrl || '';
                    requestsBtnEl.dataset.pickupName = btn.dataset.pickupName || '';
                    requestsBtnEl.dataset.destinationName = btn.dataset.destinationName || '';
                    requestsBtnEl.dataset.pickupLat = btn.dataset.pickupLat || '';
                    requestsBtnEl.dataset.pickupLng = btn.dataset.pickupLng || '';
                    requestsBtnEl.dataset.destinationLat = btn.dataset.destinationLat || '';
                    requestsBtnEl.dataset.destinationLng = btn.dataset.destinationLng || '';

                    const pendingCount = Number.parseInt(btn.dataset.requestsPendingCount || '0', 10) || 0;
                    let badge = requestsBtnEl.querySelector('.trip-request-badge');
                    if (pendingCount > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'trip-request-badge';
                            requestsBtnEl.appendChild(badge);
                        }
                        badge.textContent = pendingCount > 9 ? '9+' : String(pendingCount);
                    } else if (badge) {
                        badge.remove();
                    }
                }
            }

            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');

            const pickupLat      = toNum(btn.dataset.pickupLat);
            const pickupLng      = toNum(btn.dataset.pickupLng);
            const destinationLat = toNum(btn.dataset.destinationLat);
            const destinationLng = toNum(btn.dataset.destinationLng);

            setTimeout(() => {
                drawMap(pickupLat, pickupLng, destinationLat, destinationLng, routePointsPayload).then(() => {
                    if (!miniMap) return;
                    // invalidateSize() alone only fixes the tile grid — it
                    // does not re-run fitBounds(), so if the container was
                    // still mid-layout (0 width, or the wrong width) at the
                    // first fitBounds() call inside drawMap(), the map stays
                    // zoomed/centred on that wrong measurement forever. This
                    // page nests the modal inside more surrounding layout
                    // than trips/index (the fullscreen chat shell) so that
                    // race is more likely to lose here — refitting once more
                    // after invalidateSize() (now that layout has settled)
                    // is what actually corrects it, matching the exact
                    // bounds drawMap() itself would have used.
                    miniMap.invalidateSize();
                    if ([pickupLat, pickupLng, destinationLat, destinationLng].every((v) => v !== null)) {
                        miniMap.fitBounds(window.L.latLngBounds([[pickupLat, pickupLng], [destinationLat, destinationLng]]), { padding: [16, 16] });
                    }
                });
            }, 120);
    });

    const interactiveSelector = 'a, button, input, select, textarea, form, label';
    document.addEventListener('click', (event) => {
        const card = event.target.closest('.open-trip-card');
        if (!card) return;
        if (event.target.closest(interactiveSelector)) return;
        const btn = card.querySelector('.open-trip-modal-btn');
        if (btn instanceof HTMLButtonElement) btn.click();
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

    // Close this popup first so the two don't stack — the click still
    // bubbles up to trip-requests-modal.js's own document-level listener
    // (matched by the "open-trip-requests-review" class), which opens the
    // Manage Requests popup right after.
    requestsBtnEl?.addEventListener('click', closeModal);

    // Drag-to-dismiss on mobile — see public/js/bottom-sheet-drag.js.
    // The grabber pill above trip-modal-head was purely decorative
    // before this; the sheet never actually tracked a finger.
    window.CarpoolBottomSheet?.enable({
        modal: modal,
        card: modal.querySelector('.trip-modal-card'),
        head: modal.querySelector('.trip-modal-head'),
        closeFn: closeModal,
    });
})();
