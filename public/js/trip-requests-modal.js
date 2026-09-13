/* Extracted from resources/views/trips/index.blade.php — the "Manage
   requests" driver-side popup (+ its Reject/Remove reason sub-popups),
   including the multi-passenger route-optimization map. Moved so the chat
   thread page can reuse this exact same popup without pulling in everything
   else in trips-index.js. Page values (CSRF token) come from window.CH_TRIPS,
   same convention as trips-index.js itself — any page loading this file must
   also define that global. showModalSkeleton below is a local copy of the
   one trips-index.js keeps at its own top level (for its other popups) —
   duplicated here, not shared, since this file must also stand alone on
   pages that don't load trips-index.js at all. */

        (() => {
        const showModalSkeleton = (listEl) => {
            if (!listEl) return;
            listEl.innerHTML = `
                <div style="display:flex; flex-direction:column; gap:10px; width:100%; pointer-events:none; opacity:0.85;">
                    <div style="border:1px solid var(--hairline); border-radius:14px; padding:12px; display:grid; gap:10px; background:var(--surface);">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="sk" style="width:34px; height:34px; border-radius:999px;"></span>
                                <div>
                                    <div class="sk" style="height:14px; width:100px; border-radius:4px;"></div>
                                    <div class="sk" style="height:10px; width:120px; border-radius:3px; margin-top:4px;"></div>
                                </div>
                            </div>
                            <span class="sk" style="width:50px; height:18px; border-radius:99px;"></span>
                        </div>
                        <div class="sk" style="height:68px; border-radius:10px;"></div>
                        <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:8px;">
                            <div class="sk" style="height:34px; border-radius:9px;"></div>
                            <div class="sk" style="height:34px; border-radius:9px;"></div>
                        </div>
                    </div>
                </div>
            `;
        };

            const modal = document.getElementById('tripRequestsReviewModal');
            const list = document.getElementById('tripRequestsReviewList');
            const closeBtn = document.getElementById('tripRequestsReviewClose');
            if (!modal || !list || !closeBtn) return;

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const rejectModal = document.getElementById('tripRejectRequestModal');
            const rejectCloseTopBtn = document.getElementById('tripRejectRequestCloseTop');
            const rejectCancelBtn = document.getElementById('tripRejectRequestCancel');
            const rejectConfirmBtn = document.getElementById('tripRejectRequestConfirm');
            const rejectPassengerEl = document.getElementById('tripRejectRequestPassenger');
            const rejectTripEl = document.getElementById('tripRejectRequestTrip');
            const rejectReasonEl = document.getElementById('tripRejectRequestReason');
            let activeRejectForm = null;
            let activeRejectButton = null;
            if (rejectModal && rejectModal.parentElement !== document.body) {
                document.body.appendChild(rejectModal);
            }
            const openRejectModal = (form, button) => {
                activeRejectForm = form;
                activeRejectButton = button;
                if (rejectPassengerEl) rejectPassengerEl.textContent = button.dataset.passenger || '-';
                if (rejectTripEl) rejectTripEl.textContent = button.dataset.trip || '-';
                if (rejectReasonEl) rejectReasonEl.value = '';
                rejectModal?.classList.add('is-open');
                rejectModal?.setAttribute('aria-hidden', 'false');
                setTimeout(() => {
                    try {
                        rejectReasonEl?.focus({ preventScroll: true });
                    } catch (_error) {
                        rejectReasonEl?.focus();
                    }
                }, 30);
            };
            const closeRejectModal = () => {
                activeRejectForm = null;
                activeRejectButton = null;
                rejectModal?.classList.remove('is-open');
                rejectModal?.setAttribute('aria-hidden', 'true');
                if (rejectReasonEl) rejectReasonEl.value = '';
            };
            if (rejectModal) {
                window.CarpoolBottomSheet?.enable({
                    modal: rejectModal,
                    card: rejectModal.querySelector('.trip-payment-review-card'),
                    head: rejectModal.querySelector('.trip-payment-review-head'),
                    closeFn: closeRejectModal,
                });
                list.addEventListener('click', (event) => {
                    const rejectBtn = event.target.closest('.open-trip-reject-reason');
                    if (!rejectBtn) return;
                    event.preventDefault();
                    const form = rejectBtn.closest('form');
                    if (form) openRejectModal(form, rejectBtn);
                });
                rejectCloseTopBtn?.addEventListener('click', closeRejectModal);
                rejectCancelBtn?.addEventListener('click', closeRejectModal);
                rejectModal.addEventListener('click', (event) => {
                    if (event.target === rejectModal) closeRejectModal();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && rejectModal.classList.contains('is-open')) closeRejectModal();
                });
                rejectConfirmBtn?.addEventListener('click', () => {
                    const reason = (rejectReasonEl?.value || '').trim();
                    if (!reason) {
                        rejectReasonEl?.focus();
                        return;
                    }
                    if (activeRejectForm && activeRejectButton) {
                        const noteInput = activeRejectForm.querySelector('input[name="response_note"]');
                        if (noteInput) noteInput.value = reason;
                        submitJoinRequestResponse(activeRejectForm, activeRejectButton);
                    }
                    closeRejectModal();
                });
            }
            list.addEventListener('click', (event) => {
                const approveBtn = event.target.closest('.open-trip-request-approve');
                if (!approveBtn) return;
                event.preventDefault();
                const form = approveBtn.closest('form');
                if (form) submitJoinRequestResponse(form, approveBtn);
            });

            // ── Remove-participant reason modal — same shape as the reject
            // modal above, targeting an already-approved passenger instead.
            const removeModal = document.getElementById('tripRemoveParticipantModal');
            const removeCloseTopBtn = document.getElementById('tripRemoveParticipantCloseTop');
            const removeCancelBtn = document.getElementById('tripRemoveParticipantCancel');
            const removeConfirmBtn = document.getElementById('tripRemoveParticipantConfirm');
            const removePassengerEl = document.getElementById('tripRemoveParticipantPassenger');
            const removeTripEl = document.getElementById('tripRemoveParticipantTrip');
            const removeReasonEl = document.getElementById('tripRemoveParticipantReason');
            let activeRemoveForm = null;
            let activeRemoveButton = null;
            if (removeModal && removeModal.parentElement !== document.body) {
                document.body.appendChild(removeModal);
            }
            const openRemoveModal = (form, button) => {
                activeRemoveForm = form;
                activeRemoveButton = button;
                if (removePassengerEl) removePassengerEl.textContent = button.dataset.passenger || '-';
                if (removeTripEl) removeTripEl.textContent = button.dataset.trip || '-';
                if (removeReasonEl) removeReasonEl.value = '';
                removeModal?.classList.add('is-open');
                removeModal?.setAttribute('aria-hidden', 'false');
                setTimeout(() => {
                    try {
                        removeReasonEl?.focus({ preventScroll: true });
                    } catch (_error) {
                        removeReasonEl?.focus();
                    }
                }, 30);
            };
            const closeRemoveModal = () => {
                activeRemoveForm = null;
                activeRemoveButton = null;
                removeModal?.classList.remove('is-open');
                removeModal?.setAttribute('aria-hidden', 'true');
                if (removeReasonEl) removeReasonEl.value = '';
            };
            if (removeModal) {
                window.CarpoolBottomSheet?.enable({
                    modal: removeModal,
                    card: removeModal.querySelector('.trip-payment-review-card'),
                    head: removeModal.querySelector('.trip-payment-review-head'),
                    closeFn: closeRemoveModal,
                });
                list.addEventListener('click', (event) => {
                    const removeBtn = event.target.closest('.open-trip-remove-reason');
                    if (!removeBtn) return;
                    event.preventDefault();
                    const form = removeBtn.closest('form');
                    if (form) openRemoveModal(form, removeBtn);
                });
                removeCloseTopBtn?.addEventListener('click', closeRemoveModal);
                removeCancelBtn?.addEventListener('click', closeRemoveModal);
                removeModal.addEventListener('click', (event) => {
                    if (event.target === removeModal) closeRemoveModal();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && removeModal.classList.contains('is-open')) closeRemoveModal();
                });
                removeConfirmBtn?.addEventListener('click', () => {
                    const reason = (removeReasonEl?.value || '').trim();
                    if (!reason) {
                        removeReasonEl?.focus();
                        return;
                    }
                    if (activeRemoveForm && activeRemoveButton) {
                        const reasonInput = activeRemoveForm.querySelector('input[name="reason"]');
                        if (reasonInput) reasonInput.value = reason;
                        submitAttendanceAction(activeRemoveForm, activeRemoveButton);
                    }
                    closeRemoveModal();
                });
            }

            // ── Mark-absent — no reason needed, just a confirm() before submit.
            list.addEventListener('click', (event) => {
                const absentBtn = event.target.closest('.open-trip-mark-absent');
                if (!absentBtn) return;
                event.preventDefault();
                const passengerName = absentBtn.dataset.passenger || 'this passenger';
                if (!window.confirm(`Mark ${passengerName} as absent for this trip? This cannot be undone.`)) return;
                const form = absentBtn.closest('form');
                if (form) submitAttendanceAction(form, absentBtn);
            });

            const csrf = window.CH_TRIPS.csrf;
            const decodePayload = (encoded) => {
                try {
                    const bytes = Uint8Array.from(atob(String(encoded || '')), (char) => char.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                } catch (_error) {
                    return [];
                }
            };
            const encodePayload = (value) => {
                try {
                    const bytes = new TextEncoder().encode(JSON.stringify(value));
                    let binary = '';
                    bytes.forEach((byte) => { binary += String.fromCharCode(byte); });
                    return btoa(binary);
                } catch (_error) {
                    return '';
                }
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const responseForm = (request, action, label, classes, icon) => {
                if (action === 'reject') {
                    return `
                        <form method="POST" action="${escapeHtml(request.respond_url)}">
                            <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                            <input type="hidden" name="_method" value="PATCH">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="response_note" value="">
                            <button type="button" class="trip-payment-review-btn ${classes} open-trip-reject-reason" data-request-id="${escapeHtml(request.id)}" data-passenger="${escapeHtml(request.passenger)}" data-trip="${escapeHtml(request.trip)}">
                                <i class="${escapeHtml(icon)}"></i>${escapeHtml(label)}
                            </button>
                        </form>
                    `;
                }
                return `
                    <form method="POST" action="${escapeHtml(request.respond_url)}">
                        <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                        <input type="hidden" name="_method" value="PATCH">
                        <input type="hidden" name="action" value="${escapeHtml(action)}">
                        <input type="hidden" name="response_note" value="">
                        <button type="button" class="trip-payment-review-btn ${classes} open-trip-request-approve" data-request-id="${escapeHtml(request.id)}">
                            <i class="${escapeHtml(icon)}"></i>${escapeHtml(label)}
                        </button>
                    </form>
                `;
            };
            const removeForm = (request) => `
                <form method="POST" action="${escapeHtml(request.remove_url)}">
                    <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="reason" value="">
                    <button type="button" class="trip-payment-review-btn danger open-trip-remove-reason" data-request-id="${escapeHtml(request.id)}" data-passenger="${escapeHtml(request.passenger)}" data-trip="${escapeHtml(request.trip)}">
                        <i class="fa-solid fa-user-xmark"></i>Remove
                    </button>
                </form>
            `;
            const absenceForm = (request) => `
                <form method="POST" action="${escapeHtml(request.absence_url)}">
                    <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="button" class="trip-payment-review-btn warn open-trip-mark-absent" data-request-id="${escapeHtml(request.id)}" data-passenger="${escapeHtml(request.passenger)}">
                        <i class="fa-solid fa-user-clock"></i>Absent
                    </button>
                </form>
            `;
            const syncRequestTriggerButtons = (tripId, requestsB64, seats, pendingCount) => {
                document.querySelectorAll(`.open-trip-requests-review[data-trip-id="${CSS.escape(String(tripId))}"]`).forEach((triggerBtn) => {
                    triggerBtn.dataset.requestsB64 = requestsB64;
                    if (seats !== null) triggerBtn.dataset.seats = seats;

                    let badge = triggerBtn.querySelector('.trip-request-badge');
                    if (pendingCount > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'trip-request-badge';
                            triggerBtn.appendChild(badge);
                        }
                        badge.textContent = pendingCount > 9 ? '9+' : String(pendingCount);
                    } else if (badge) {
                        badge.remove();
                    }
                });
            };
            const submitJoinRequestResponse = async (form, triggerBtn) => {
                if (!activeRequestButton) return;
                const originalHtml = triggerBtn.innerHTML;
                triggerBtn.disabled = true;
                triggerBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                try {
                    // form.action would return the <input name="action"> element
                    // instead of the URL (form controls shadow same-named
                    // HTMLFormElement properties) — read the attribute directly.
                    const response = await fetch(form.getAttribute('action'), {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Request could not be updated.');
                    }

                    const requestId = String(triggerBtn.dataset.requestId || '');
                    let updatedSeats = null;
                    if (payload.action === 'approve') {
                        activeRequestRows = activeRequestRows.map((row) => (
                            String(row.id) === requestId ? { ...row, status: payload.status || 'approved' } : row
                        ));
                        const seatsLeft = Number(activeRequestButton.dataset.seats);
                        if (Number.isFinite(seatsLeft) && seatsLeft > 0) {
                            updatedSeats = String(seatsLeft - 1);
                            activeRequestButton.dataset.seats = updatedSeats;
                        }
                    } else {
                        activeRequestRows = activeRequestRows.filter((row) => String(row.id) !== requestId);
                    }

                    const requestsB64 = encodePayload(activeRequestRows);
                    activeRequestButton.dataset.requestsB64 = requestsB64;
                    const pendingCount = activeRequestRows.filter((row) => row.status === 'pending').length;
                    syncRequestTriggerButtons(activeRequestButton.dataset.tripId, requestsB64, updatedSeats, pendingCount);

                    if (window.showToast) window.showToast(payload.message || 'Request updated.', 'success');
                    render(activeRequestRows, activeRequestButton);
                } catch (error) {
                    triggerBtn.disabled = false;
                    triggerBtn.innerHTML = originalHtml;
                    if (window.showToast) window.showToast(error.message || 'Request could not be updated.', 'error');
                }
            };
            // Shared by Remove and Mark-absent — both just flip attendance_status
            // (plus an optional note) on the existing row, unlike respond() which
            // can also remove the row entirely (reject) or touch seat counts
            // (approve). Neither of those apply here, so this stays simpler.
            const submitAttendanceAction = async (form, triggerBtn) => {
                if (!activeRequestButton) return;
                const originalHtml = triggerBtn.innerHTML;
                triggerBtn.disabled = true;
                triggerBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                try {
                    const response = await fetch(form.getAttribute('action'), {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Request could not be updated.');
                    }

                    const requestId = String(triggerBtn.dataset.requestId || '');
                    activeRequestRows = activeRequestRows.map((row) => (
                        String(row.id) === requestId
                            ? { ...row, attendance_status: payload.attendance_status, attendance_note: payload.attendance_note ?? row.attendance_note }
                            : row
                    ));

                    const requestsB64 = encodePayload(activeRequestRows);
                    activeRequestButton.dataset.requestsB64 = requestsB64;

                    if (window.showToast) window.showToast(payload.message || 'Updated.', 'success');
                    render(activeRequestRows, activeRequestButton);
                } catch (error) {
                    triggerBtn.disabled = false;
                    triggerBtn.innerHTML = originalHtml;
                    if (window.showToast) window.showToast(error.message || 'Request could not be updated.', 'error');
                }
            };
            const requestToggleForm = (button) => {
                const isOpen = String(button.dataset.isOpenForRequest || '') === '1';
                const hint = isOpen
                    ? 'Passengers can currently send join requests for this trip.'
                    : 'Join requests are closed — passengers cannot request to join right now.';

                return `
                    <form method="POST" action="${escapeHtml(button.dataset.toggleUrl || '')}" class="trip-request-toggle-form" data-request-toggle-form>
                        <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                        <input type="hidden" name="_method" value="PATCH">
                        <label class="trip-request-switch-row" data-request-open-state>
                            <span class="trip-request-switch-text">
                                <span class="trip-request-switch-title">${isOpen ? 'Open for requests' : 'Closed for requests'}</span>
                                <span class="trip-request-switch-hint">${hint}</span>
                            </span>
                            <span class="trip-request-switch">
                                <input type="hidden" name="is_open_for_request" value="0">
                                <input type="checkbox" name="is_open_for_request" value="1" ${isOpen ? 'checked' : ''} data-request-toggle-input>
                                <span class="trip-request-switch-track"><span class="trip-request-switch-thumb"></span></span>
                            </span>
                        </label>
                    </form>
                `;
            };
            const renderHero = (button, pendingCount) => {
                return `
                    <div class="trip-meta-line">
                        <span class="trip-meta-item"><i class="fa-solid fa-hashtag"></i><span>${escapeHtml(button.dataset.tripRef || '-')}</span></span>
                        <span class="trip-meta-dot">&middot;</span>
                        <span class="trip-meta-item"><i class="fa-regular fa-calendar"></i><span>${escapeHtml(button.dataset.tripDatetime || '-')}</span></span>
                        <span class="trip-meta-dot">&middot;</span>
                        <span class="trip-meta-item trip-meta-route"><i class="fa-solid fa-road"></i><span class="trip-meta-route-text">${escapeHtml(button.dataset.routeName || '-')}</span></span>
                    </div>
                    <div class="trip-driver-card">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-people-arrows"></i>Public Join</span>
                        ${requestToggleForm(button)}
                    </div>
                    <div class="trip-secondary-grid">
                        <div class="trip-secondary-item">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-chair"></i>Seats Left</span>
                            <span class="trip-modal-value">${escapeHtml(button.dataset.seats || '-')}</span>
                        </div>
                        <div class="trip-secondary-item">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user-clock"></i>Pending</span>
                            <span class="trip-modal-value">${Number(pendingCount) || 0}</span>
                        </div>
                        <div class="trip-secondary-item">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-circle-check"></i>Trip Status</span>
                            <span class="trip-modal-value">${escapeHtml(button.dataset.tripStatus || '-')}</span>
                        </div>
                    </div>
                `;
            };
            let requestMap = null;
            let activeRequestButton = null;
            let activeRequestRows = [];
            const num = (value) => {
                const parsed = Number.parseFloat(String(value ?? '').trim());
                return Number.isFinite(parsed) ? parsed : null;
            };
            const drawMap = async (button, requests) => {
                const mapEl = document.getElementById('tripRequestsInlineMap');
                const stopsEl = document.getElementById('tripRequestsInlineStops');
                const metricsEl = document.getElementById('tripRequestsInlineMetrics');
                const googleMapsLink = document.getElementById('tripRequestsInlineGoogleMaps');
                if (!mapEl || typeof L === 'undefined') return;
                if (requestMap) {
                    requestMap.remove();
                    requestMap = null;
                }
                const toLatLng = (raw) => {
                    const lat = num(raw?.lat);
                    const lng = num(raw?.lng);
                    return lat !== null && lng !== null ? L.latLng(lat, lng) : null;
                };
                const driverPickup = L.latLng(num(button.dataset.pickupLat), num(button.dataset.pickupLng));
                const driverDropoff = L.latLng(num(button.dataset.destinationLat), num(button.dataset.destinationLng));
                if (!Number.isFinite(driverPickup.lat) || !Number.isFinite(driverPickup.lng) || !Number.isFinite(driverDropoff.lat) || !Number.isFinite(driverDropoff.lng)) {
                    mapEl.innerHTML = '<div class="trip-payment-review-empty">No coordinates available for route preview.</div>';
                    return;
                }
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
                    return Object.values(grouped).every((group) => group.pickup === undefined || group.dropoff === undefined || group.pickup < group.dropoff);
                };
                const straightDistanceKm = (points) => {
                    let total = 0;
                    for (let index = 0; index < points.length - 1; index += 1) total += points[index].distanceTo(points[index + 1]) / 1000;
                    return total;
                };
                const fetchRoute = async (points) => {
                    const waypoints = uniqueWaypoints(points);
                    if (waypoints.length < 2) return { points: waypoints, distanceKm: 0, durationMinutes: 0 };
                    const coordinates = waypoints.map((point) => `${encodeURIComponent(point.lng)},${encodeURIComponent(point.lat)}`).join(';');
                    const url = `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`;
                    try {
                        const response = await fetch(url);
                        if (!response.ok) throw new Error('route');
                        const data = await response.json();
                        const route = data?.routes?.[0];
                        const routePoints = (route?.geometry?.coordinates ?? [])
                            .map((coord) => L.latLng(Number(coord[1]), Number(coord[0])))
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
                const passengerPalette = ['#7c3aed', '#0f766e', '#dc2626', '#2563eb', '#9333ea', '#c2410c', '#0891b2', '#be123c'];
                const colorForRequest = (requestId) => passengerPalette[Math.abs(Number.parseInt(String(requestId || 0), 10) || 0) % passengerPalette.length];
                const stops = (requests || []).flatMap((request) => {
                    const pickup = toLatLng(request.pickup_point);
                    const dropoff = toLatLng(request.dropoff_point);
                    const color = colorForRequest(request.id);
                    return [
                        pickup ? { requestId: request.id, kind: 'pickup', point: pickup, label: request.pickup_point?.label || `${request.passenger} pickup`, status: request.status, color } : null,
                        dropoff ? { requestId: request.id, kind: 'dropoff', point: dropoff, label: request.dropoff_point?.label || `${request.passenger} drop-off`, status: request.status, color } : null,
                    ].filter(Boolean);
                }).map((stop, index) => ({ ...stop, marker: String(index + 1) }));
                const visibleRequestIds = new Set((requests || []).map((request) => String(request.id)));
                const visibleStops = () => stops.filter((stop) => visibleRequestIds.has(String(stop.requestId)));
                const shortestMiddleRoute = async (activeStops) => {
                    const usableStops = activeStops.filter((stop) => stop.point);
                    const orders = usableStops.length <= 7 ? permutations(usableStops).filter(validPassengerOrder) : [usableStops];
                    const candidates = orders.length ? orders : [[]];
                    const routes = await Promise.all(candidates.map(async (order) => ({
                        ...(await fetchRoute([driverPickup, ...order.map((item) => item.point), driverDropoff])),
                        order,
                    })));
                    return routes.reduce((best, route) => (!best || route.distanceKm < best.distanceKm ? route : best), null);
                };

                requestMap = L.map(mapEl, { scrollWheelZoom: false, zoomControl: true, attributionControl: false });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(requestMap);
                const markerRefs = new Map();
                const numberedIcon = (className, marker, fill = '') => L.divIcon({
                    className: '',
                    html: `<span class="summary-pin-icon ${className}" data-summary-marker="${marker}" style="${fill ? `--pin-fill:${fill}` : ''}">${marker}</span>`,
                    iconSize: [26, 26],
                    iconAnchor: [13, 13],
                    tooltipAnchor: [0, -14],
                });
                const addPoint = (point, className, label, marker, fill = '') => {
                    // Passenger-supplied labels reach this tooltip (line ~1184);
                    // Leaflet assigns string tooltip content via innerHTML.
                    const mapMarker = L.marker(point, { icon: numberedIcon(className, marker, fill), title: label })
                        .addTo(requestMap)
                        .bindTooltip(escapeHtml(label), { permanent: false, direction: 'top', offset: [0, -10] });
                    markerRefs.set(marker, mapMarker);
                };
                const renderStopList = () => {
                    if (!stopsEl) return;
                    const rows = [
                        { marker: 'A', label: 'Driver Pickup', meta: 'Driver Point', className: 'driver-pickup' },
                        ...stops.map((stop) => ({
                            marker: stop.marker,
                            label: stop.label,
                            meta: `${stop.kind} - ${stop.status}`,
                            className: `${stop.status} ${stop.kind}`,
                            color: stop.color,
                            requestId: String(stop.requestId),
                        })),
                        { marker: 'B', label: 'Driver Drop-off', meta: 'Driver Point', className: 'driver-dropoff' },
                    ];
                    stopsEl.innerHTML = rows.map((row) => `
                        <div class="summary-stop-item ${row.requestId && !visibleRequestIds.has(row.requestId) ? 'is-hidden' : ''}" data-summary-stop="${row.marker}">
                            <span class="summary-stop-marker ${row.className}" style="${row.color ? `--pin-fill:${row.color}` : ''}">${row.marker}</span>
                            <span class="summary-stop-text"><span class="summary-stop-label">${escapeHtml(row.label)}</span><span class="summary-stop-meta">${escapeHtml(row.meta)}</span></span>
                            ${row.requestId ? `<button type="button" class="summary-stop-toggle ${visibleRequestIds.has(row.requestId) ? '' : 'is-off'}" data-summary-toggle="${row.requestId}"><i class="fas ${visibleRequestIds.has(row.requestId) ? 'fa-eye' : 'fa-eye-slash'}"></i></button>` : ''}
                        </div>
                    `).join('');
                    stopsEl.querySelectorAll('[data-summary-toggle]').forEach((toggle) => {
                        toggle.addEventListener('click', async (event) => {
                            event.stopPropagation();
                            const id = String(toggle.dataset.summaryToggle || '');
                            visibleRequestIds.has(id) ? visibleRequestIds.delete(id) : visibleRequestIds.add(id);
                            await redraw();
                        });
                    });
                };
                const formatKm = (value) => `${(Number(value) || 0).toFixed(2)} km`;
                const formatMinutes = (value) => value === null || value === undefined || !Number.isFinite(Number(value)) ? '-' : `${Math.max(1, Math.round(Number(value)))} min`;
                const formatMoney = (value) => `RM ${(Number(value) || 0).toFixed(2)}`;
                const renderMetrics = (originalRoute, suggestedRoute, activeStops) => {
                    if (!metricsEl) return;
                    const activeRequestIds = new Set((activeStops || []).map((stop) => String(stop.requestId)));
                    const activeRequests = (requests || []).filter((request) => activeRequestIds.has(String(request.id)));
                    const originalKm = Number(originalRoute?.distanceKm) || 0;
                    const suggestedKm = Number(suggestedRoute?.distanceKm) || originalKm;
                    const extraKm = Math.max(0, suggestedKm - originalKm);
                    const originalMinutes = originalRoute?.durationMinutes;
                    const suggestedMinutes = suggestedRoute?.durationMinutes;
                    const extraMinutes = originalMinutes !== null && suggestedMinutes !== null ? Math.max(0, Number(suggestedMinutes) - Number(originalMinutes)) : null;
                    const totalFare = activeRequests.reduce((sum, request) => sum + (Number(request.fare) || 0), 0);
                    const customStops = activeStops.length;
                    const approvedCount = activeRequests.filter((request) => request.status === 'approved').length;
                    const pendingCount = activeRequests.filter((request) => request.status === 'pending').length;
                    const totalDeviation = activeRequests.reduce((sum, request) => sum + (Number(request.deviationKm) || 0), 0);
                    metricsEl.innerHTML = `
                        <div class="summary-metric-item"><span class="summary-metric-label">Route distance</span><span class="summary-metric-value">${formatKm(suggestedKm)}</span><span class="summary-metric-meta">Original ${formatKm(originalKm)} / extra ${formatKm(extraKm)}</span></div>
                        <div class="summary-metric-item"><span class="summary-metric-label">Estimated time</span><span class="summary-metric-value">${formatMinutes(suggestedMinutes)}</span><span class="summary-metric-meta">Original ${formatMinutes(originalMinutes)} / extra ${formatMinutes(extraMinutes)}</span></div>
                        <div class="summary-metric-item"><span class="summary-metric-label">Extra fees</span><span class="summary-metric-value">${formatMoney(totalFare)}</span><span class="summary-metric-meta">${approvedCount} approved / ${pendingCount} pending / ${customStops} custom stops / ${formatKm(totalDeviation)} deviation</span></div>
                    `;
                };
                const setGoogleMapsLink = (orderedStops) => {
                    if (!googleMapsLink) return;
                    const formatPoint = (point) => `${point.lat.toFixed(7)},${point.lng.toFixed(7)}`;
                    const params = new URLSearchParams({ api: '1', travelmode: 'driving', origin: formatPoint(driverPickup), destination: formatPoint(driverDropoff) });
                    const waypoints = (orderedStops || []).map((stop) => stop.point).filter(Boolean).slice(0, 23).map(formatPoint);
                    if (waypoints.length) params.set('waypoints', waypoints.join('|'));
                    googleMapsLink.href = `https://www.google.com/maps/dir/?${params.toString()}`;
                };
                const redraw = async () => {
                    const activeStops = visibleStops();
                    const [originalRoute, suggestedRoute] = await Promise.all([fetchRoute([driverPickup, driverDropoff]), shortestMiddleRoute(activeStops)]);
                    requestMap.eachLayer((layer) => { if (!(layer instanceof L.TileLayer)) requestMap.removeLayer(layer); });
                    L.polyline(originalRoute.points, { color: '#64748b', weight: 9, opacity: .38, lineCap: 'round', interactive: false }).addTo(requestMap);
                    if (suggestedRoute?.points?.length > 1) L.polyline(suggestedRoute.points, { color: '#1d4ed8', weight: 5, opacity: .92, lineCap: 'round', interactive: false }).addTo(requestMap);
                    addPoint(driverPickup, 'driver-pickup', 'Pickup Driver', 'A');
                    addPoint(driverDropoff, 'driver-dropoff', 'Driver Drop-off', 'B');
                    activeStops.forEach((stop) => addPoint(stop.point, stop.status, `${stop.label} · ${stop.status}`, stop.marker, stop.color));
                    renderStopList();
                    renderMetrics(originalRoute, suggestedRoute, activeStops);
                    setGoogleMapsLink(suggestedRoute?.order || activeStops);
                    const bounds = L.latLngBounds([...originalRoute.points, ...(suggestedRoute?.points ?? []), ...activeStops.map((stop) => stop.point)]);
                    if (bounds.isValid()) requestMap.fitBounds(bounds, { padding: [28, 28] });
                    setTimeout(() => requestMap?.invalidateSize(), 100);
                };
                await redraw();
            };
            const render = (requests, button) => {
                const rows = Array.isArray(requests) ? requests : [];
                activeRequestRows = rows;
                if (rows.length === 0) {
                    list.innerHTML = `
                        ${renderHero(button, 0)}
                        <div class="trip-request-empty-state">
                            <span class="trip-request-empty-icon"><i class="fa-solid fa-inbox"></i></span>
                            <p class="trip-request-empty-title">No requests yet</p>
                            <p class="trip-request-empty-sub">Passengers who ask to join this public trip will show up here for you to review and approve.</p>
                        </div>
                    `;
                    return;
                }

                const pendingCount = rows.filter((request) => request.status === 'pending').length;
                const approvedCount = rows.filter((request) => request.status === 'approved').length;
                const customStops = rows.filter((request) => String(request.pickup || '').startsWith('Custom') || String(request.dropoff || '').startsWith('Custom')).length;
                const extraKm = rows.reduce((sum, request) => sum + (Number(request.detour_km) || 0), 0);
                const extraMin = rows.reduce((sum, request) => sum + (Number(request.detour_min) || 0), 0);
                const suggestedFare = rows.reduce((sum, request) => sum + (Number(request.fare) || 0), 0);
                const routeUrl = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(button.dataset.pickupName || '')}&destination=${encodeURIComponent(button.dataset.destinationName || '')}`;

                list.innerHTML = `
                    ${renderHero(button, pendingCount)}
                    <section class="trip-request-summary-card">
                        <div>
                            <h3 class="trip-request-section-title">Passenger Route Summary</h3>
                            <p class="trip-request-section-sub">Pending and approved custom stops with the shortest middle route as driver reference. Driver pickup and drop-off remain fixed.</p>
                        </div>
                        <span class="trip-request-count-pill">${rows.length} active request${rows.length === 1 ? '' : 's'}</span>
                        <div class="trip-request-map" id="tripRequestsInlineMap"></div>
                        <div class="trip-request-map-legend">
                            <span><i class="original"></i>Original route</span>
                            <span><i class="suggested"></i>Suggested route</span>
                        </div>
                        <div class="trip-request-stops" id="tripRequestsInlineStops"></div>
                        <div class="summary-metrics-grid" id="tripRequestsInlineMetrics"></div>
                        <a class="trip-paynow-submit" id="tripRequestsInlineGoogleMaps" href="${routeUrl}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;"><i class="fa-solid fa-map-location-dot" style="margin-right:6px;"></i>Open in Google Maps</a>
                    </section>
                    <section class="trip-request-summary-card">
                        <div>
                            <h3 class="trip-request-section-title">Passenger Requests</h3>
                            <p class="trip-request-section-sub">Review pending and approved passengers, route preferences, fare preview, and risk signals.</p>
                        </div>
                        <div class="trip-request-tools">
                            <input class="trip-request-tool" type="search" placeholder="Search passenger, note, or route..." data-request-search>
                            <select class="trip-request-tool" data-request-status-filter>
                                <option value="all">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                            </select>
                        </div>
                        ${rows.map((request) => `
                    <article class="trip-payment-review-item">
                        <div class="trip-payment-review-top">
                            <div class="trip-payment-review-person">
                                <span class="trip-payment-review-avatar">${escapeHtml(request.initials || 'P')}</span>
                                <span>
                                    <span class="trip-payment-review-name">${escapeHtml(request.passenger)}</span>
                                    <span class="trip-payment-review-route">${escapeHtml(request.trip)} · ${escapeHtml(request.requested_at || '-')}</span>
                                </span>
                            </div>
                            <span class="trip-payment-review-status">${escapeHtml(request.status || 'pending')}</span>
                        </div>
                        <div class="trip-request-route-grid">
                            <div class="trip-request-route-item">
                                <span>Pickup</span>
                                <strong>${escapeHtml(request.pickup)}</strong>
                                <small>${escapeHtml(request.pickup_meta || '-')}</small>
                            </div>
                            <div class="trip-request-route-item">
                                <span>Drop-off</span>
                                <strong>${escapeHtml(request.dropoff)}</strong>
                                <small>${escapeHtml(request.dropoff_meta || '-')}</small>
                            </div>
                            <div class="trip-request-route-item">
                                <span>Extra fee</span>
                                <strong>${request.fare ? `+ RM ${escapeHtml(request.fare)}` : 'No extra fee'}</strong>
                                <small>Added only to this passenger</small>
                            </div>
                            <div class="trip-request-route-item">
                                <span>Route fit</span>
                                <strong>${escapeHtml(request.fit || 'Review')}</strong>
                                <small>${escapeHtml(request.fit_label || 'Driver review')}</small>
                            </div>
                        </div>
                        <div class="trip-request-risk-card">
                            <div class="trip-request-risk-top">
                                <span class="trip-request-risk-title"><i class="fa-solid fa-shield-halved"></i> AI Passenger Risk</span>
                                <span class="trip-request-risk-badge ${(() => {
                                    const s = Number(request.risk_score) || 70;
                                    return s >= 80 ? 'risk-low' : s >= 60 ? 'risk-moderate' : s >= 40 ? 'risk-high' : 'risk-very-high';
                                })()}">${escapeHtml(request.risk_level || 'Moderate Risk')}</span>
                            </div>
                            <div class="trip-request-risk-gauge">
                                <div class="trip-request-risk-score">${Number(request.risk_score) || 70}<span>/100</span></div>
                                <div class="trip-request-risk-gauge-track">
                                    <div class="trip-request-risk-gauge-marker" style="left:${Math.max(0, Math.min(100, Number(request.risk_score) || 70))}%"></div>
                                </div>
                                <div class="trip-request-risk-gauge-scale"><span>Higher risk</span><span>Lower risk</span></div>
                            </div>
                            <div class="trip-request-risk-meta">
                                <div class="trip-request-risk-meta-item">
                                    <span class="trip-request-risk-meta-icon"><i class="fa-solid fa-shield-heart"></i></span>
                                    <span class="trip-request-risk-meta-text"><strong>${Number(request.risk_reliability || 5.0).toFixed(1)}/5.0</strong><small>Reliability</small></span>
                                </div>
                                <div class="trip-request-risk-meta-item">
                                    <span class="trip-request-risk-meta-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                                    <span class="trip-request-risk-meta-text"><strong>${Number(request.risk_unpaid) || 0}</strong><small>Overdue</small></span>
                                </div>
                                <div class="trip-request-risk-meta-item">
                                    <span class="trip-request-risk-meta-icon"><i class="fa-solid fa-clock"></i></span>
                                    <span class="trip-request-risk-meta-text"><strong>${Number(request.risk_cancelled) || 0}</strong><small>Cancelled</small></span>
                                </div>
                                <div class="trip-request-risk-meta-item">
                                    <span class="trip-request-risk-meta-icon"><i class="fa-solid fa-user-clock"></i></span>
                                    <span class="trip-request-risk-meta-text"><strong>${Number(request.risk_absent) || 0}</strong><small>Absences</small></span>
                                </div>
                            </div>
                        </div>
                        ${request.note ? `<div class="trip-request-note">Passenger note: ${escapeHtml(request.note)}</div>` : ''}
                        ${request.status === 'pending' ? `
                            <div class="trip-request-actions">
                                ${responseForm(request, 'reject', 'Reject', 'danger', 'fa-solid fa-xmark')}
                                ${responseForm(request, 'approve', 'Approve', 'confirm', 'fa-solid fa-check')}
                            </div>
                        ` : request.attendance_status === 'removed' ? `
                            <div class="trip-request-note trip-request-note-removed"><i class="fa-solid fa-user-xmark"></i> Removed${request.attendance_note ? ' — ' + escapeHtml(request.attendance_note) : ''}</div>
                        ` : request.attendance_status === 'absent' ? `
                            <div class="trip-request-note trip-request-note-absent"><i class="fa-solid fa-user-clock"></i> Marked absent</div>
                        ` : `
                            <div class="trip-request-actions">
                                ${removeForm(request)}
                                ${request.absence_available ? absenceForm(request) : ''}
                            </div>
                        `}
                    </article>
                `).join('')}
                    </section>
                `;
                drawMap(button, rows);
            };
            const open = (button) => {
                activeRequestButton = button;
                const requests = decodePayload(button.dataset.requestsB64 || '');
                activeRequestRows = requests;
                showModalSkeleton(list);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    render(requests, button);
                }, 240);
            };
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };
            window.CarpoolBottomSheet?.enable({
                modal: modal,
                card: modal.querySelector('.trip-payment-review-card'),
                head: modal.querySelector('.trip-payment-review-head'),
                closeFn: close,
            });

            // Delegated on document, not bound per-button: switching the trips
            // tab/status filter replaces #trips-real-container's innerHTML
            // wholesale (see fetchPage()), which silently drops any listener
            // that had been attached directly to the old button elements —
            // this is what made "Manage requests" go dead after the first tab
            // switch. A delegated listener re-resolves the target from the
            // live DOM on every click, so it keeps working after any swap.
            document.addEventListener('click', (event) => {
                const button = event.target instanceof Element
                    ? event.target.closest('.open-trip-requests-review')
                    : null;
                if (!(button instanceof HTMLElement)) return;

                event.preventDefault();
                event.stopPropagation();
                open(button);
            });
            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
            });
            list.addEventListener('change', (event) => {
                const toggleInput = event.target.closest('[data-request-toggle-input]');
                if (!toggleInput) return;
                toggleInput.closest('form')?.requestSubmit();
            });
            list.addEventListener('submit', async (event) => {
                const toggleForm = event.target.closest('[data-request-toggle-form]');
                if (!toggleForm || !activeRequestButton) return;

                event.preventDefault();
                const toggleInput = toggleForm.querySelector('[data-request-toggle-input]');
                const previousChecked = toggleInput ? toggleInput.checked : null;
                const formData = new FormData(toggleForm);
                if (toggleInput) toggleInput.disabled = true;

                try {
                    const response = await fetch(toggleForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Public join setting could not be updated.');
                    }

                    const tripId = activeRequestButton.dataset.tripId || '';
                    const nextOpen = payload.is_open_for_request ? '1' : '0';
                    const nextState = payload.open_state || (payload.is_open_for_request ? 'Open' : 'Closed');
                    document.querySelectorAll(`.open-trip-requests-review[data-trip-id="${CSS.escape(tripId)}"]`).forEach((button) => {
                        button.dataset.isOpenForRequest = nextOpen;
                        button.dataset.openState = nextState;
                    });
                    activeRequestButton.dataset.isOpenForRequest = nextOpen;
                    activeRequestButton.dataset.openState = nextState;
                    render(decodePayload(activeRequestButton.dataset.requestsB64 || ''), activeRequestButton);
                } catch (error) {
                    if (toggleInput) {
                        toggleInput.disabled = false;
                        toggleInput.checked = previousChecked;
                    }
                    if (window.showToast) window.showToast(error.message || 'Public join setting could not be updated.', 'error');
                }
            });
            list.addEventListener('input', (event) => {
                if (!event.target.matches('[data-request-search]')) return;
                const term = event.target.value.trim().toLowerCase();
                list.querySelectorAll('.trip-payment-review-item').forEach((item) => {
                    item.classList.toggle('trip-request-card-hidden', term && !item.textContent.toLowerCase().includes(term));
                });
            });
            list.addEventListener('change', (event) => {
                if (!event.target.matches('[data-request-status-filter]')) return;
                const status = event.target.value;
                list.querySelectorAll('.trip-payment-review-item').forEach((item) => {
                    item.classList.toggle('trip-request-card-hidden', status !== 'all' && !item.textContent.toLowerCase().includes(status));
                });
            });
        })();
