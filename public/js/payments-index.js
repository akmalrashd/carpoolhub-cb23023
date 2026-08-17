/* Extracted from resources/views/payments/index.blade.php — logic; page values come from window.CH_PAYMENTS. */
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

// ── Page Loader & AJAX Pagination ──
(() => {
    const skel = document.getElementById('payments-skel-container');
    const real = document.getElementById('payments-real-container');

    const ensureRealVisible = () => {
        if (skel) {
            skel.style.display = 'none';
            skel.style.opacity = '0';
            skel.style.pointerEvents = 'none';
        }
        if (real) {
            real.style.display = '';
            real.classList.add('loaded');
            real.style.opacity = '1';
            if (real.dataset.initialLoad) real.dataset.initialLoad = 'false';
        }
    };

    // Run immediately on page ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureRealVisible);
    } else {
        ensureRealVisible();
    }

    // Smooth AJAX fetching function (no skeleton flash)
    const fetchPage = async (url) => {
        const currentReal = document.getElementById('payments-real-container');
        if (currentReal) {
            currentReal.style.transition = 'opacity 0.15s ease';
            currentReal.style.opacity = '0.5';
        }

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error();
            const html = await res.text();

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newReal = doc.getElementById('payments-real-container');

            // Replace active states for desktop tabs
            const newTabsRow = doc.querySelector('.payments-tab-strip');
            const currentTabsRow = document.querySelector('.payments-tab-strip');
            if (newTabsRow && currentTabsRow) {
                currentTabsRow.innerHTML = newTabsRow.innerHTML;
            }

            if (newReal && currentReal) {
                currentReal.innerHTML = newReal.innerHTML;
                history.pushState(null, '', url);

                // Re-bind pagination clicks
                bindPaginationEvents();
                if (typeof window.updatePaymentsVisibility === 'function') window.updatePaymentsVisibility();
            }
        } catch (_e) {
            window.location.href = url;
        } finally {
            if (currentReal) {
                currentReal.style.opacity = '1';
            }
        }
    };

    const bindPaginationEvents = () => {
        document.querySelectorAll('.payments-pagination-wrap a, .pagination-wrap a').forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                fetchPage(link.href);
            });
        });
    };

    // Form Submit Interceptor
    const filterForm = document.getElementById('paymentsFilterPanel');
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);
            const url = new URL(filterForm.action || window.location.href);
            for (const [key, val] of params.entries()) {
                if (val) url.searchParams.set(key, val);
                else url.searchParams.delete(key);
            }
            fetchPage(url.toString());
        });

        let submitTimer = null;
        filterForm.querySelectorAll('input, select').forEach((field) => {
            if (field.name === 'perspective' || field.classList.contains('js-summary-radio')) return;
            field.addEventListener('change', () => {
                window.clearTimeout(submitTimer);
                submitTimer = window.setTimeout(() => {
                    filterForm.dispatchEvent(new Event('submit', { cancelable: true }));
                }, 250);
            });
        });
    }

    // Bind first load
    bindPaginationEvents();
})();

(() => {
    const params = new URLSearchParams(window.location.search);
    const multiIds = String(params.get('trip_ids') || '')
        .split(',')
        .map((id) => id.trim())
        .filter(Boolean);
    const focusIds = [...new Set(
        multiIds.length > 0
            ? multiIds
            : [String(params.get('trip_id') || '').trim()].filter(Boolean)
    )];

    if (focusIds.length > 0) {
        const targets = [];
        const isVisibleTarget = (el) => {
            if (!el) return false;
            if (el.offsetParent !== null) return true;
            const rect = el.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        };
        focusIds.forEach((tripId) => {
            document
                .querySelectorAll(`.open-trip-modal-btn[data-trip-id="${tripId}"]`)
                .forEach((btn) => {
                    const target = btn.closest('.open-trip-card') || btn.closest('tr') || btn;
                    if (target && !targets.includes(target)) {
                        targets.push(target);
                    }
                });
        });

        if (targets.length > 0) {
            const myPaymentTargets = targets.filter((target) => {
                return target.closest && target.closest('#my-payments-list');
            });
            const scopedTargets = myPaymentTargets.length > 0 ? myPaymentTargets : targets;
            const preferredTargets = scopedTargets.filter((target) => isVisibleTarget(target));
            const activeTargets = preferredTargets.length > 0 ? preferredTargets : scopedTargets;

            activeTargets.forEach((target) => target.classList.add('payment-focus-highlight'));
            window.setTimeout(() => {
                activeTargets.forEach((target) => target.classList.remove('payment-focus-highlight'));
            }, 2400);
        }
    }
})();

(() => {
    const modal = document.getElementById('paymentPayNowModal');
    const list = document.getElementById('paymentPayNowList');
    const sub = document.getElementById('paymentPayNowSub');
    const closeBtn = document.getElementById('paymentPayNowClose');
    if (!modal || !list || !closeBtn) return;

    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const csrf = window.CH_PAYMENTS.csrf;
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    };
    const resultHtml = (message, isError = false) => `
                <div class="trip-payment-popup-result ${isError ? 'error' : ''}">
                    <span class="trip-payment-popup-icon"><i class="fa-solid ${isError ? 'fa-xmark' : 'fa-check'}"></i></span>
                    <span class="trip-payment-popup-title">${isError ? 'Action failed' : 'Successful'}</span>
                    <span class="trip-payment-popup-message">${escapeHtml(message)}</span>
                </div>
            `;
    const qrPreviewHtml = (url, label) => {
        const safeUrl = String(url || '').trim();
        return safeUrl
            ? `<img src="${escapeHtml(safeUrl)}" alt="${escapeHtml(label)}">`
            : '<span class="driver-payment-qr-empty">No QR uploaded</span>';
    };

    document.addEventListener('click', (event) => {
        const button = event.target instanceof Element
            ? event.target.closest('.open-payment-paynow-btn')
            : null;
        if (!(button instanceof HTMLElement)) return;

        event.preventDefault();
        event.stopPropagation();
        const fareBreakdown = button.dataset.hasExtra === '1'
            ? `<span style="display:block;color:#64748b;font-size:12px;">Base RM ${escapeHtml(button.dataset.baseAmount || '0.00')} + extra RM ${escapeHtml(button.dataset.extraFee || '0.00')}</span>`
            : '';
        const driverName = button.dataset.driverName || '-';
        const driverEmail = button.dataset.driverEmail || '-';
        const driverPhoto = String(button.dataset.driverPhoto || '').trim();
        const driverAvatar = driverPhoto
            ? `<img src="${escapeHtml(driverPhoto)}" alt="${escapeHtml(driverName)}">`
            : escapeHtml((driverName.trim().charAt(0) || 'D').toUpperCase());
        if (sub) sub.textContent = button.dataset.route || 'Mark your trip payment as paid.';
        showModalSkeleton(list);
        document.querySelectorAll('.request-modal.show, .trip-payment-review-modal.is-open').forEach((openModal) => {
            if (openModal !== modal) {
                openModal.classList.remove('show', 'is-open');
                openModal.setAttribute('aria-hidden', 'true');
            }
        });
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            list.innerHTML = `
                        <article class="trip-payment-review-item">
                            <div class="trip-payment-review-top">
                                <div class="trip-payment-review-person">
                                    <span class="trip-payment-review-avatar">${escapeHtml(button.dataset.initials || 'P')}</span>
                                    <span>
                                        <span class="trip-payment-review-name">${escapeHtml(button.dataset.passenger || 'Passenger')}</span>
                                        <span class="trip-payment-review-route">${escapeHtml(button.dataset.trip || 'Trip')} &middot; DuitNow</span>
                                    </span>
                                </div>
                                <span class="trip-payment-review-status">Unpaid</span>
                            </div>
                            <div class="trip-payment-review-amount">
                                <span>
                                    <span>Amount due</span>
                                    <strong>RM ${escapeHtml(button.dataset.amount || '0.00')}</strong>
                                    ${fareBreakdown}
                                </span>
                            </div>
                            <div class="payment-paynow-driver">
                                <div class="driver-payment-head">
                                    <span class="driver-payment-avatar">${driverAvatar}</span>
                                    <span class="driver-payment-meta">
                                        <span class="driver-payment-name">${escapeHtml(driverName)}</span>
                                        <span class="driver-payment-email">${escapeHtml(driverEmail)}</span>
                                    </span>
                                </div>
                                <div class="trip-details-pairs">
                                    <div class="request-modal-line">
                                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-building-columns"></i>Bank / Wallet</span>
                                        <span class="request-modal-value">${escapeHtml(button.dataset.driverBank || '-')}</span>
                                    </div>
                                    <div class="request-modal-line">
                                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Account Holder</span>
                                        <span class="request-modal-value">${escapeHtml(button.dataset.driverAccountName || '-')}</span>
                                    </div>
                                    <div class="request-modal-line">
                                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Account Number</span>
                                        <span class="request-modal-value">${escapeHtml(button.dataset.driverAccountNumber || '-')}</span>
                                    </div>
                                </div>
                                <div class="driver-payment-qr-grid">
                                    <div class="driver-payment-qr-card">
                                        <span class="driver-payment-qr-title"><i class="fa-solid fa-qrcode"></i>DuitNow QR</span>
                                        <div class="driver-payment-qr-preview">${qrPreviewHtml(button.dataset.driverDuitnowQr, 'DuitNow QR')}</div>
                                    </div>
                                    <div class="driver-payment-qr-card">
                                        <span class="driver-payment-qr-title"><i class="fa-solid fa-qrcode"></i>Touch 'n Go QR</span>
                                        <div class="driver-payment-qr-preview">${qrPreviewHtml(button.dataset.driverTngQr, "Touch 'n Go QR")}</div>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="${escapeHtml(button.dataset.action || '#')}" class="trip-paynow-form">
                                <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                                <input type="hidden" name="_method" value="PATCH">
                                <div class="trip-paynow-fields">
                                    <select class="trip-paynow-input" name="payment_method" required>
                                        <option value="" disabled selected>Select method</option>
                                        <option value="duitnow_qr">DuitNow QR</option>
                                        <option value="bank_account">Bank Account</option>
                                        <option value="digital_wallet">Digital Wallet</option>
                                        <option value="others">Others</option>
                                    </select>
                                    <input class="trip-paynow-input" type="text" name="remarks" placeholder="Remarks">
                                </div>
                                <button type="submit" class="trip-paynow-submit">Mark as paid</button>
                            </form>
                        </article>
                    `;
        }, 240);
    }, true);

    list.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains('trip-paynow-form')) return;
        event.preventDefault();

        const card = form.closest('.trip-payment-review-item') || list;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing';
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(async (response) => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(payload.message || 'The payment action could not be completed.');
                }
                card.innerHTML = resultHtml(payload.message || 'Payment updated.');
                window.setTimeout(() => window.location.reload(), 900);
            })
            .catch((error) => {
                card.innerHTML = resultHtml(error.message || 'The payment action could not be completed.', true);
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
    });

    closeBtn.addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });
})();

(() => {
    const modal = document.getElementById('paymentReceiptModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const closeBtn = document.getElementById('paymentReceiptClose');
    const breakdownRow = document.getElementById('paymentReceiptBreakdownRow');
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value || '-';
    };
    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    };

    document.addEventListener('click', (event) => {
        const button = event.target instanceof Element
            ? event.target.closest('.open-payment-receipt-btn')
            : null;
        if (!(button instanceof HTMLElement)) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        event.stopPropagation();
        setText('paymentReceiptNo', button.dataset.receiptNo);
        setText('paymentReceiptAmount', button.dataset.amount);
        setText('paymentReceiptRoute', button.dataset.route);
        setText('paymentReceiptPassenger', button.dataset.passenger);
        setText('paymentReceiptDriver', button.dataset.driver);
        setText('paymentReceiptMethod', button.dataset.method);
        setText('paymentReceiptMarked', button.dataset.markedAt);
        setText('paymentReceiptConfirmed', button.dataset.confirmedAt);
        const hasExtra = button.dataset.hasExtra === '1';
        setText('paymentReceiptBreakdown', hasExtra ? `${button.dataset.baseFare || 'RM 0.00'} base + ${button.dataset.extraFee || 'RM 0.00'} extra` : '-');
        if (breakdownRow) breakdownRow.style.display = hasExtra ? 'flex' : 'none';
        document.querySelectorAll('.request-modal.show, .trip-payment-review-modal.is-open').forEach((openModal) => {
            if (openModal !== modal) {
                openModal.classList.remove('show', 'is-open');
                openModal.setAttribute('aria-hidden', 'true');
            }
        });
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }, true);

    closeBtn?.addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });
})();

(() => {
    const tripDetailsModal = document.getElementById('tripDetailsModal');
    const tripDetailsCloseTop = document.getElementById('tripDetailsCloseTop');
    const tripDetailButtons = document.querySelectorAll('.open-trip-modal-btn');

    if (tripDetailsModal && tripDetailsCloseTop) {
        if (tripDetailsModal.parentElement !== document.body) {
            document.body.appendChild(tripDetailsModal);
        }
        const tripDetailsId = document.getElementById('tripDetailsId');
        const tripDetailsRoute = document.getElementById('tripDetailsRoute');
        const tripDetailsDriver = document.getElementById('tripDetailsDriver');
        const tripDetailsDriverAvatar = document.getElementById('tripDetailsDriverAvatar');
        const tripDetailsDriverEmail = document.getElementById('tripDetailsDriverEmail');
        const tripDetailsPickupPoint = document.getElementById('tripDetailsPickupPoint');
        const tripDetailsDestinationPoint = document.getElementById('tripDetailsDestinationPoint');
        const tripDetailsMiniMap = document.getElementById('tripDetailsMiniMap');
        const tripDetailsPassengerCount = document.getElementById('tripDetailsPassengerCount');
        const tripDetailsPassengerList = document.getElementById('tripDetailsPassengerList');
        const tripDetailsTotalPassengers = document.getElementById('tripDetailsTotalPassengers');
        const tripDetailsSplitType = document.getElementById('tripDetailsSplitType');
        const tripDetailsPairHint = document.getElementById('tripDetailsPairHint');
        const tripDetailsDatetime = document.getElementById('tripDetailsDatetime');
        const tripDetailsMode = document.getElementById('tripDetailsMode');
        const tripDetailsStatus = document.getElementById('tripDetailsStatus');
        const tripDetailsAmountDue = document.getElementById('tripDetailsAmountDue');
        const tripDetailsFareBreakdown = document.getElementById('tripDetailsFareBreakdown');
        const tripDetailsExtraFee = document.getElementById('tripDetailsExtraFee');
        const tripDetailsCustomStop = document.getElementById('tripDetailsCustomStop');
        const tripDetailsFareTotal = document.getElementById('tripDetailsFareTotal');
        const tripDetailsPaymentStatus = document.getElementById('tripDetailsPaymentStatus');
        const tripDetailsPaymentMethod = document.getElementById('tripDetailsPaymentMethod');
        const tripDetailsPaymentRemarks = document.getElementById('tripDetailsPaymentRemarks');
        const tripDetailsMarkedAt = document.getElementById('tripDetailsMarkedAt');
        const tripDetailsWhatsapp = document.getElementById('tripDetailsWhatsapp');
        const tripDetailsEmail = document.getElementById('tripDetailsEmail');
        let tripMiniMap = null;
        let tripMiniRouteLayer = null;
        let tripMiniMarkerLayer = null;
        let tripMiniSeedLine = null;
        const toNum = (v) => {
            const n = Number.parseFloat(String(v ?? '').trim());
            return Number.isFinite(n) ? n : null;
        };
        const toSlug = (value) => String(value || '')
            .trim()
            .toLowerCase()
            .replace(/\s+/g, '_')
            .replace(/[^a-z0-9_]/g, '');
        const setStatusBadge = (el, value) => {
            if (!el) return;
            const slug = toSlug(value);
            el.textContent = value || '-';
            el.className = `request-modal-value trip-status-badge trip-status-${slug || 'draft'}`;
        };
        const esc = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        const renderPassengerList = (participantsRaw) => {
            if (!tripDetailsPassengerList || !tripDetailsPassengerCount) return;
            const participants = Array.isArray(participantsRaw) ? participantsRaw : [];
            const passengerOnly = participants.filter((item) => !item?.is_driver);
            const driverIncludedInSplit = participants.some((item) => !!item?.is_driver);

            tripDetailsPassengerCount.textContent = `${passengerOnly.length} passenger${passengerOnly.length === 1 ? '' : 's'}`;
            if (tripDetailsTotalPassengers) {
                tripDetailsTotalPassengers.textContent = String(passengerOnly.length);
            }
            if (tripDetailsSplitType) {
                tripDetailsSplitType.textContent = driverIncludedInSplit
                    ? 'Include Driver in Fare Split'
                    : 'Exclude Driver from Fare Split';
            }

            if (passengerOnly.length === 0) {
                tripDetailsPassengerList.innerHTML = '<div class="trip-passenger-email">No passenger records found for this trip.</div>';
                return;
            }

            tripDetailsPassengerList.innerHTML = passengerOnly.map((item) => {
                const name = esc(item?.name || '-');
                const email = esc(item?.email || '');
                const avatarHtml = item?.photo_url
                    ? `<span class="trip-passenger-avatar"><img src="${esc(item.photo_url)}" alt="${name}"></span>`
                    : `<span class="trip-passenger-avatar">${esc((item?.name || 'U').trim().charAt(0).toUpperCase() || 'U')}</span>`;

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
        const ensureMiniMap = () => {
            if (!tripDetailsMiniMap || typeof window.L === 'undefined') return null;
            if (tripMiniMap) return tripMiniMap;

            tripDetailsMiniMap.innerHTML = '';
            tripMiniMap = window.L.map(tripDetailsMiniMap, {
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
            }).addTo(tripMiniMap);

            return tripMiniMap;
        };
        const drawRouteFallback = (map, pickupLat, pickupLng, destinationLat, destinationLng) => {
            const coords = [[pickupLat, pickupLng], [destinationLat, destinationLng]];
            tripMiniRouteLayer = window.L.polyline(coords, {
                color: '#1d4ed8',
                weight: 4,
                opacity: 0.95,
                dashArray: '8 6',
            }).addTo(map);
            map.fitBounds(tripMiniRouteLayer.getBounds(), { padding: [16, 16] });
        };
        const drawMiniMap = async (pickupLat, pickupLng, destinationLat, destinationLng) => {
            if (!tripDetailsMiniMap) return;
            if ([pickupLat, pickupLng, destinationLat, destinationLng].some((v) => v === null)) {
                if (tripMiniMap) {
                    tripMiniMap.remove();
                    tripMiniMap = null;
                }
                tripDetailsMiniMap.innerHTML = '<div class="trip-mini-map-empty">Route preview is unavailable for this trip.</div>';
                return;
            }

            const map = ensureMiniMap();
            if (!map) {
                tripDetailsMiniMap.innerHTML = '<div class="trip-mini-map-empty">Route preview is unavailable for this trip.</div>';
                return;
            }

            if (tripMiniRouteLayer) {
                map.removeLayer(tripMiniRouteLayer);
                tripMiniRouteLayer = null;
            }
            if (tripMiniSeedLine) {
                map.removeLayer(tripMiniSeedLine);
                tripMiniSeedLine = null;
            }
            if (tripMiniMarkerLayer) {
                map.removeLayer(tripMiniMarkerLayer);
                tripMiniMarkerLayer = null;
            }

            tripMiniMarkerLayer = window.L.layerGroup([
                window.L.circleMarker([pickupLat, pickupLng], {
                    radius: 6,
                    color: '#ffffff',
                    weight: 2,
                    fillColor: '#16a34a',
                    fillOpacity: 1,
                }),
                window.L.circleMarker([destinationLat, destinationLng], {
                    radius: 6,
                    color: '#ffffff',
                    weight: 2,
                    fillColor: '#2563eb',
                    fillOpacity: 1,
                }),
            ]).addTo(map);
            const markerBounds = window.L.latLngBounds([[pickupLat, pickupLng], [destinationLat, destinationLng]]);
            map.fitBounds(markerBounds, { padding: [18, 18] });

            // Always show at least a direct connection first so users can see the path immediately.
            tripMiniSeedLine = window.L.polyline([[pickupLat, pickupLng], [destinationLat, destinationLng]], {
                color: '#60a5fa',
                weight: 3,
                opacity: 0.9,
                dashArray: '8 6',
            }).addTo(map);

            const routeUrl = 'https://router.project-osrm.org/route/v1/driving/'
                + `${encodeURIComponent(pickupLng)},${encodeURIComponent(pickupLat)};`
                + `${encodeURIComponent(destinationLng)},${encodeURIComponent(destinationLat)}`
                + '?overview=full&geometries=geojson&alternatives=false&steps=false';

            try {
                const response = await fetch(routeUrl, { method: 'GET' });
                if (!response.ok) throw new Error('route fetch failed');
                const payload = await response.json();
                const geometry = payload?.routes?.[0]?.geometry?.coordinates ?? [];
                const latLngs = geometry
                    .map((coord) => [Number(coord[1]), Number(coord[0])])
                    .filter((coord) => Number.isFinite(coord[0]) && Number.isFinite(coord[1]));

                if (latLngs.length > 1) {
                    if (tripMiniSeedLine) {
                        map.removeLayer(tripMiniSeedLine);
                        tripMiniSeedLine = null;
                    }
                    tripMiniRouteLayer = window.L.polyline(latLngs, {
                        color: '#1d4ed8',
                        weight: 4,
                        opacity: 0.95,
                    }).addTo(map);
                    tripMiniRouteLayer.bringToFront();
                    map.fitBounds(tripMiniRouteLayer.getBounds(), { padding: [16, 16] });
                } else {
                    drawRouteFallback(map, pickupLat, pickupLng, destinationLat, destinationLng);
                }
            } catch (error) {
                drawRouteFallback(map, pickupLat, pickupLng, destinationLat, destinationLng);
            }
        };

        const openTripDetails = (source) => {
            const driverName = source.dataset.driver || '-';
            const driverEmail = source.dataset.driverEmail || '';
            const driverWhatsappUrl = (source.dataset.driverWhatsappUrl || '').trim();
            const driverPhoneRaw = source.dataset.driverPhone || '';
            const digitsRaw = driverPhoneRaw.replace(/\D+/g, '');
            let waDigits = digitsRaw.replace(/^00+/, '');
            if (/^01\d{8,9}$/.test(waDigits)) {
                waDigits = `60${waDigits.slice(1)}`;
            }
            const waUrl = /^https?:\/\/wa\.me\/\d+$/i.test(driverWhatsappUrl)
                ? driverWhatsappUrl
                : (waDigits ? `https://wa.me/${waDigits}` : '');
            const pickupName = source.dataset.pickupName || '-';
            const destinationName = source.dataset.destinationName || '-';
            const pickupLat = toNum(source.dataset.pickupLat);
            const pickupLng = toNum(source.dataset.pickupLng);
            const destinationLat = toNum(source.dataset.destinationLat);
            const destinationLng = toNum(source.dataset.destinationLng);
            let participantsPayload = [];
            try {
                participantsPayload = JSON.parse(source.dataset.participants || '[]');
            } catch (_e) {
                participantsPayload = [];
            }

            const tripId = String(source.dataset.tripId || '').trim();
            const tripRef = String(source.dataset.tripRef || '').trim() || (tripId ? `TRP-${tripId.padStart(5, '0')}` : '-');
            if (tripDetailsId) tripDetailsId.textContent = tripRef;
            if (tripDetailsRoute) tripDetailsRoute.textContent = source.dataset.route || '-';
            if (tripDetailsPickupPoint) tripDetailsPickupPoint.textContent = pickupName;
            if (tripDetailsDestinationPoint) tripDetailsDestinationPoint.textContent = destinationName;
            if (tripDetailsDriver) tripDetailsDriver.textContent = driverName;
            if (tripDetailsDriverAvatar) tripDetailsDriverAvatar.textContent = (driverName.trim().charAt(0) || 'D').toUpperCase();
            if (tripDetailsDriverEmail) tripDetailsDriverEmail.textContent = driverEmail || '-';
            if (tripDetailsDatetime) tripDetailsDatetime.textContent = source.dataset.datetime || '-';
            if (tripDetailsMode) tripDetailsMode.textContent = source.dataset.mode || '-';
            if (tripDetailsPairHint) {
                const pairedTripId = String(source.dataset.pairedTripId || '').trim();
                const isTwoWay = String(source.dataset.mode || '').toLowerCase().includes('two way');
                if (isTwoWay && pairedTripId) {
                    tripDetailsPairHint.textContent = `Paired return leg: ${tripRef}`;
                    tripDetailsPairHint.style.display = 'block';
                } else {
                    tripDetailsPairHint.textContent = '';
                    tripDetailsPairHint.style.display = 'none';
                }
            }
            renderPassengerList(participantsPayload);
            setStatusBadge(tripDetailsStatus, source.dataset.status || '-');
            if (tripDetailsAmountDue) tripDetailsAmountDue.textContent = source.dataset.amountDue || '-';
            if (tripDetailsFareBreakdown) {
                const extraFee = String(source.dataset.extraFee || 'RM 0.00').trim();
                tripDetailsFareBreakdown.textContent = extraFee !== 'RM 0.00'
                    ? `${source.dataset.baseFare || 'RM 0.00'} base split + ${extraFee} custom extra`
                    : 'This passenger pays the normal base split.';
            }
            if (tripDetailsExtraFee) tripDetailsExtraFee.textContent = source.dataset.extraFee || 'RM 0.00';
            if (tripDetailsCustomStop) {
                const customStop = String(source.dataset.customStop || '').trim();
                tripDetailsCustomStop.textContent = customStop
                    ? `${customStop}. Extra fee applies only to this passenger.`
                    : 'No custom stop extra for this passenger.';
            }
            if (tripDetailsFareTotal) tripDetailsFareTotal.textContent = source.dataset.fareTotal || '-';
            setStatusBadge(tripDetailsPaymentStatus, source.dataset.paymentStatus || '-');
            if (tripDetailsPaymentMethod) tripDetailsPaymentMethod.textContent = source.dataset.paymentMethod || '-';
            if (tripDetailsPaymentRemarks) tripDetailsPaymentRemarks.textContent = source.dataset.paymentRemarks || '-';
            if (tripDetailsMarkedAt) tripDetailsMarkedAt.textContent = source.dataset.markedAt || '-';
            if (tripDetailsEmail) {
                if (driverEmail) {
                    tripDetailsEmail.classList.remove('is-disabled');
                    tripDetailsEmail.setAttribute('href', `mailto:${driverEmail}`);
                } else {
                    tripDetailsEmail.classList.add('is-disabled');
                    tripDetailsEmail.setAttribute('href', '#');
                }
            }
            if (tripDetailsWhatsapp) {
                if (waUrl) {
                    tripDetailsWhatsapp.classList.remove('is-disabled');
                    tripDetailsWhatsapp.setAttribute('href', waUrl);
                } else {
                    tripDetailsWhatsapp.classList.add('is-disabled');
                    tripDetailsWhatsapp.setAttribute('href', '#');
                }
            }
            tripDetailsModal.classList.add('show');
            tripDetailsModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            setTimeout(() => {
                drawMiniMap(pickupLat, pickupLng, destinationLat, destinationLng).then(() => {
                    if (tripMiniMap) tripMiniMap.invalidateSize();
                });
            }, 40);
        };

        document.addEventListener('click', (event) => {
            const button = event.target.closest('.open-trip-modal-btn');
            if (button) {
                openTripDetails(button);
            }
        });

        const interactiveSelector = 'a, button, input, select, textarea, form, label';
        document.addEventListener('click', (event) => {
            const card = event.target.closest('.open-trip-card');
            if (!card) return;
            if (event.target.closest(interactiveSelector)) return;

            const detailBtn = card.querySelector('.open-trip-modal-btn');
            if (detailBtn instanceof HTMLElement) {
                detailBtn.click();
            } else if (card instanceof HTMLElement && card.dataset.tripId) {
                openTripDetails(card);
            }
        });

        const closeTripDetailsModal = () => {
            tripDetailsModal.classList.remove('show');
            tripDetailsModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        tripDetailsCloseTop.addEventListener('click', closeTripDetailsModal);
        tripDetailsModal.addEventListener('click', (event) => {
            if (event.target === tripDetailsModal) closeTripDetailsModal();
        });
    }

    const modal = document.getElementById('requestModal');
    const closeBtn = document.getElementById('requestModalClose');
    const closeBtnTop = document.getElementById('requestModalCloseTop');
    if (modal && closeBtn) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        const passengerEl = document.getElementById('requestModalPassenger');
        const tripEl = document.getElementById('requestModalTrip');
        const methodEl = document.getElementById('requestModalMethod');
        const remarksEl = document.getElementById('requestModalRemarks');
        const markedEl = document.getElementById('requestModalMarked');
        const approveForm = document.getElementById('requestModalApproveForm');
        const rejectFromRequestBtn = document.getElementById('requestModalReject');

        document.addEventListener('click', (e) => {
            const button = e.target.closest('.open-request-btn');
            if (!button) return;

            if (passengerEl) passengerEl.textContent = button.dataset.passenger || '-';
            if (tripEl) tripEl.textContent = button.dataset.trip || '-';
            if (methodEl) methodEl.textContent = button.dataset.method || '-';
            if (remarksEl) remarksEl.textContent = button.dataset.remarks || '-';
            if (markedEl) markedEl.textContent = button.dataset.marked || '-';
            if (approveForm) {
                approveForm.setAttribute('action', button.dataset.approveAction || '');
            }
            if (rejectFromRequestBtn) {
                rejectFromRequestBtn.dataset.action = button.dataset.rejectAction || '';
                rejectFromRequestBtn.dataset.passenger = button.dataset.passenger || '-';
                rejectFromRequestBtn.dataset.trip = button.dataset.trip || '-';
            }
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        });

        const closeModal = () => {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            if (approveForm) {
                approveForm.setAttribute('action', '');
            }
        };

        closeBtn.addEventListener('click', closeModal);
        if (closeBtnTop) closeBtnTop.addEventListener('click', closeModal);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });
    }

    // ── Mark Paid Action Modal (Popup) ──
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.open-mark-paid-modal');
        if (!btn) return;

        e.preventDefault();
        const modal = document.getElementById('markPaidModal');
        const form = document.getElementById('markPaidModalForm');
        if (!modal || !form) return;

        const passengerEl = document.getElementById('markPaidModalPassenger');
        const tripEl = document.getElementById('markPaidModalTrip');
        const amountEl = document.getElementById('markPaidModalAmount');
        const selectEl = document.getElementById('markPaidModalMethod');
        const remarksEl = document.getElementById('markPaidModalRemarks');

        if (form) form.setAttribute('action', btn.dataset.action || '');
        if (passengerEl) passengerEl.textContent = btn.dataset.passenger || '-';
        if (tripEl) tripEl.textContent = btn.dataset.trip || '-';
        if (amountEl) amountEl.textContent = btn.dataset.amount || 'RM 0.00';
        if (selectEl) selectEl.selectedIndex = 0;
        if (remarksEl) remarksEl.value = '';

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    });

    const closeMarkPaidBtn = document.getElementById('markPaidModalCloseTop');
    const markPaidModal = document.getElementById('markPaidModal');
    if (closeMarkPaidBtn && markPaidModal) {
        const closeMarkPaidModal = () => {
            markPaidModal.classList.remove('show');
            markPaidModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };
        closeMarkPaidBtn.addEventListener('click', closeMarkPaidModal);
        markPaidModal.addEventListener('click', (event) => {
            if (event.target === markPaidModal) closeMarkPaidModal();
        });
    }

    // ── Bulk Mark Paid Action Modal (Popup) ──
    const bulkOpenBtn = document.getElementById('bulkMarkPaidOpenBtn');
    const bulkModal = document.getElementById('bulkMarkPaidModal');
    const bulkModalClose = document.getElementById('bulkMarkPaidModalCloseTop');

    if (bulkOpenBtn && bulkModal) {
        bulkOpenBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const checkedMap = new Map();
            document.querySelectorAll('.bulk-payment-cb:checked').forEach(cb => {
                const row = cb.closest('.js-payment-filter-item');
                if (!row) return;
                if (row.dataset.statusHidden === '1' || row.classList.contains('payments-filter-hidden')) return;

                if (!checkedMap.has(cb.value)) {
                    checkedMap.set(cb.value, { cb, row });
                }
            });

            const checkedItems = Array.from(checkedMap.values());
            if (checkedItems.length === 0) return;

            const hiddenInputsWrap = document.getElementById('bulkMarkPaidHiddenInputs');
            const passengersListWrap = document.getElementById('bulkMarkPaidPassengersList');
            const titleEl = document.getElementById('bulkMarkPaidPassengersTitle');

            const activeTab = document.querySelector('.payments-tab.active');
            const isPayTab = activeTab && activeTab.textContent.toLowerCase().includes('to pay');

            if (titleEl) {
                if (isPayTab) {
                    titleEl.innerHTML = '<i class="fa-solid fa-user-tie"></i> Selected Drivers / Counterparties';
                } else {
                    titleEl.innerHTML = '<i class="fa-solid fa-users"></i> Selected Passengers';
                }
            }

            if (hiddenInputsWrap) {
                hiddenInputsWrap.innerHTML = '';
                if (passengersListWrap) passengersListWrap.innerHTML = '';

                let totalSum = 0;
                const passengerMap = {};

                checkedItems.forEach(({ cb, row }) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'payment_ids[]';
                    input.value = cb.value;
                    hiddenInputsWrap.appendChild(input);

                    if (row) {
                        let passengerName = row.dataset.passenger || '';
                        if (!passengerName) {
                            const nameEl = row.querySelector('.payment-name') || row.querySelector('.col-counterparty div');
                            passengerName = nameEl ? nameEl.textContent.trim() : 'Counterparty';
                        }

                        let val = 0;
                        const amountBtn = row.querySelector('[data-amount]');
                        if (amountBtn && amountBtn.dataset.amount) {
                            val = parseFloat(amountBtn.dataset.amount.replace(/[^0-9.]/g, '')) || 0;
                        } else {
                            const amountCell = row.querySelector('.col-amount');
                            if (amountCell) {
                                val = parseFloat(amountCell.textContent.replace(/[^0-9.]/g, '')) || 0;
                            }
                        }
                        totalSum += val;

                        if (!passengerMap[passengerName]) {
                            passengerMap[passengerName] = { count: 0, total: 0 };
                        }
                        passengerMap[passengerName].count++;
                        passengerMap[passengerName].total += val;
                    }
                });

                if (passengersListWrap) {
                    Object.keys(passengerMap).forEach(name => {
                        const data = passengerMap[name];
                        const chip = document.createElement('span');
                        chip.className = 'bulk-passenger-chip';
                        chip.innerHTML = `<i class="fa-solid fa-user" style="font-size:10px; color:#64748b;"></i> ${name} <span class="bulk-passenger-chip-badge">${data.count}x · RM ${data.total.toFixed(2)}</span>`;
                        passengersListWrap.appendChild(chip);
                    });
                }

                const countEl = document.getElementById('bulkMarkPaidSelectedCount');
                const amountEl = document.getElementById('bulkMarkPaidTotalAmount');
                const submitBtn = document.getElementById('bulkMarkPaidSubmitBtn');
                const selectEl = document.getElementById('bulkMarkPaidMethod');
                const remarksEl = document.getElementById('bulkMarkPaidRemarks');

                if (countEl) countEl.textContent = checkedItems.length + ' payment(s) selected';
                if (amountEl) amountEl.textContent = 'RM ' + totalSum.toFixed(2);
                if (submitBtn) submitBtn.textContent = 'Mark ' + checkedItems.length + ' Selected as Paid';
                if (selectEl) selectEl.selectedIndex = 0;
                if (remarksEl) remarksEl.value = '';
            }

            bulkModal.classList.add('show');
            bulkModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        });

        if (bulkModalClose) {
            bulkModalClose.addEventListener('click', () => {
                bulkModal.classList.remove('show');
                bulkModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            });
        }
        bulkModal.addEventListener('click', (e) => {
            if (e.target === bulkModal) {
                bulkModal.classList.remove('show');
                bulkModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            }
        });
    }

    const rejectModal = document.getElementById('rejectModal');
    const rejectCancelBtn = document.getElementById('rejectModalCancel');
    const rejectCloseTopBtn = document.getElementById('rejectModalCloseTop');
    const rejectForm = document.getElementById('rejectModalForm');
    const rejectPassengerEl = document.getElementById('rejectModalPassenger');
    const rejectTripEl = document.getElementById('rejectModalTrip');
    const rejectReasonEl = document.getElementById('rejectModalReason');
    const openRejectButtons = document.querySelectorAll('.open-reject-btn');

    if (rejectModal && rejectCancelBtn && rejectForm) {
        if (rejectModal.parentElement !== document.body) {
            document.body.appendChild(rejectModal);
        }
        const openRejectModal = (action, passenger, trip) => {
            rejectForm.setAttribute('action', action || '');
            if (rejectPassengerEl) rejectPassengerEl.textContent = passenger || '-';
            if (rejectTripEl) rejectTripEl.textContent = trip || '-';
            rejectModal.classList.add('show');
            rejectModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            setTimeout(() => {
                if (!rejectReasonEl) return;
                try {
                    rejectReasonEl.focus({ preventScroll: true });
                } catch (_error) {
                    rejectReasonEl.focus();
                }
            }, 30);
        };

        const closeRejectModal = () => {
            rejectModal.classList.remove('show');
            rejectModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            rejectForm.setAttribute('action', '');
            if (rejectReasonEl) rejectReasonEl.value = '';
        };

        openRejectButtons.forEach((button) => {
            button.addEventListener('click', () => {
                openRejectModal(button.dataset.action, button.dataset.passenger, button.dataset.trip);
            });
        });

        const rejectFromRequestBtn = document.getElementById('requestModalReject');
        if (rejectFromRequestBtn) {
            rejectFromRequestBtn.addEventListener('click', () => {
                if (modal) {
                    modal.classList.remove('show');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                }
                openRejectModal(
                    rejectFromRequestBtn.dataset.action,
                    rejectFromRequestBtn.dataset.passenger,
                    rejectFromRequestBtn.dataset.trip
                );
            });
        }

        rejectCancelBtn.addEventListener('click', closeRejectModal);
        if (rejectCloseTopBtn) rejectCloseTopBtn.addEventListener('click', closeRejectModal);
        rejectModal.addEventListener('click', (event) => {
            if (event.target === rejectModal) closeRejectModal();
        });
    }

    const driverPaymentDetailsModal = document.getElementById('driverPaymentDetailsModal');
    const driverPaymentDetailsClose = document.getElementById('driverPaymentDetailsClose');
    const driverPaymentDetailsCloseTop = document.getElementById('driverPaymentDetailsCloseTop');
    const driverPaymentButtons = document.querySelectorAll('.open-driver-payment-details-btn');
    if (driverPaymentDetailsModal) {
        if (driverPaymentDetailsModal.parentElement !== document.body) {
            document.body.appendChild(driverPaymentDetailsModal);
        }
        const driverPaymentAvatar = document.getElementById('driverPaymentAvatar');
        const driverPaymentName = document.getElementById('driverPaymentName');
        const driverPaymentEmail = document.getElementById('driverPaymentEmail');
        const driverPaymentBank = document.getElementById('driverPaymentBank');
        const driverPaymentAccountName = document.getElementById('driverPaymentAccountName');
        const driverPaymentAccountNumber = document.getElementById('driverPaymentAccountNumber');
        const driverPaymentDuitnowWrap = document.getElementById('driverPaymentDuitnowWrap');
        const driverPaymentTngWrap = document.getElementById('driverPaymentTngWrap');

        const renderQr = (wrapEl, qrUrl, label) => {
            if (!wrapEl) return;
            const url = String(qrUrl || '').trim();
            if (!url) {
                wrapEl.innerHTML = '<span class="driver-payment-qr-empty">No QR uploaded</span>';
                return;
            }
            // Build the node instead of innerHTML so a QR URL can never
            // be parsed as markup. Same rendered <img> for real values.
            const qrImg = document.createElement('img');
            qrImg.src = url;
            qrImg.alt = label;
            wrapEl.replaceChildren(qrImg);
        };

        const closeDriverPaymentModal = () => {
            driverPaymentDetailsModal.classList.remove('show');
            driverPaymentDetailsModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        driverPaymentButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const driverName = String(button.dataset.driverName || '-').trim() || '-';
                const driverEmail = String(button.dataset.driverEmail || '-').trim() || '-';
                const driverPhoto = String(button.dataset.driverPhoto || '').trim();

                if (driverPaymentName) driverPaymentName.textContent = driverName;
                if (driverPaymentEmail) driverPaymentEmail.textContent = driverEmail;
                if (driverPaymentBank) driverPaymentBank.textContent = button.dataset.driverBank || '-';
                if (driverPaymentAccountName) driverPaymentAccountName.textContent = button.dataset.driverAccountName || '-';
                if (driverPaymentAccountNumber) driverPaymentAccountNumber.textContent = button.dataset.driverAccountNumber || '-';

                if (driverPaymentAvatar) {
                    if (driverPhoto) {
                        // Stored XSS guard: a driver's name/photo is
                        // attacker-controlled and was interpolated raw
                        // into innerHTML, running in the paying
                        // passenger's session. Build the node instead —
                        // identical <img> for legitimate values.
                        const avatarImg = document.createElement('img');
                        avatarImg.src = driverPhoto;
                        avatarImg.alt = driverName;
                        driverPaymentAvatar.replaceChildren(avatarImg);
                    } else {
                        driverPaymentAvatar.textContent = (driverName.charAt(0) || 'D').toUpperCase();
                    }
                }

                renderQr(driverPaymentDuitnowWrap, button.dataset.driverDuitnowQr, 'DuitNow QR');
                renderQr(driverPaymentTngWrap, button.dataset.driverTngQr, "Touch 'n Go QR");

                driverPaymentDetailsModal.classList.add('show');
                driverPaymentDetailsModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            });
        });

        if (driverPaymentDetailsClose) driverPaymentDetailsClose.addEventListener('click', closeDriverPaymentModal);
        if (driverPaymentDetailsCloseTop) driverPaymentDetailsCloseTop.addEventListener('click', closeDriverPaymentModal);
        driverPaymentDetailsModal.addEventListener('click', (event) => {
            if (event.target === driverPaymentDetailsModal) closeDriverPaymentModal();
        });
    }

    document.querySelectorAll('.js-payments-filter').forEach((panel) => {
        const scopeSelector = panel.dataset.filterScope || '';
        const scope = scopeSelector ? document.querySelector(scopeSelector) : null;
        if (!scope) return;

        const applyFilter = () => {
            const fromDate = panel.querySelector('[data-filter-from]')?.value || '';
            const toDate = panel.querySelector('[data-filter-to]')?.value || '';
            const person = (panel.querySelector('[data-filter-person]')?.value || '').trim().toLowerCase();
            const items = Array.from(scope.querySelectorAll('.js-payment-filter-item'));
            let visibleCount = 0;

            items.forEach((item) => {
                const itemDate = item.dataset.filterDate || '';
                const itemPerson = (item.dataset.filterPerson || '').toLowerCase();
                const statusHidden = item.dataset.statusHidden === '1';
                const isVisible = (!fromDate || itemDate >= fromDate)
                    && (!toDate || itemDate <= toDate)
                    && (!person || itemPerson.includes(person))
                    && !statusHidden;

                item.classList.toggle('payments-filter-hidden', !isVisible);
                if (isVisible) visibleCount += 1;
            });

            const emptyState = scope.querySelector('[data-filter-empty]');
            if (emptyState) {
                emptyState.classList.toggle('show', items.length > 0 && visibleCount === 0);
            }

            const hasActiveFilter = Boolean(fromDate || toDate || person);
            panel.classList.toggle('has-active-filter', hasActiveFilter);
            if (typeof window.updatePaymentsVisibility === 'function') window.updatePaymentsVisibility();
        };

        panel.querySelector('[data-filter-toggle]')?.addEventListener('click', () => {
            const isOpen = panel.classList.toggle('is-open');
            panel.querySelector('[data-filter-toggle]')?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        panel.querySelectorAll('input, select').forEach((field) => {
            field.addEventListener('input', applyFilter);
            field.addEventListener('change', applyFilter);
        });

        panel.querySelector('[data-filter-reset]')?.addEventListener('click', () => {
            panel.querySelectorAll('input, select').forEach((field) => {
                field.value = '';
            });
            panel.classList.remove('is-open');
            panel.querySelector('[data-filter-toggle]')?.setAttribute('aria-expanded', 'false');
            applyFilter();
            panel.style.display = 'none';
        });

        applyFilter();
    });

    document.querySelector('[data-payments-filter-launch]')?.addEventListener('click', () => {
        const panel = document.getElementById('paymentsFilterPanel');
        if (!panel) return;
        const isOpen = panel.style.display !== 'none' && panel.style.display !== '';
        panel.style.display = isOpen ? 'none' : 'grid';
        if (isOpen) {
            return;
        }
        if (!isOpen) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    const paymentsFilterPanel = document.getElementById('paymentsFilterPanel');
    if (paymentsFilterPanel instanceof HTMLFormElement) {
        let paymentFilterTimer = null;
        const submitPaymentFilter = () => {
            window.clearTimeout(paymentFilterTimer);
            paymentFilterTimer = window.setTimeout(() => paymentsFilterPanel.requestSubmit(), 250);
        };
        paymentsFilterPanel.querySelectorAll('input, select').forEach((field) => {
            field.addEventListener('change', submitPaymentFilter);
        });
    }

    const reminderButtons = Array.from(document.querySelectorAll('.reminder-btn[data-seconds-left]'));


    const pad = (value) => String(value).padStart(2, '0');
    const toHms = (seconds) => {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${pad(h)}:${pad(m)}:${pad(s)}`;
    };

    const states = {};
    reminderButtons.forEach((button) => {
        const paymentId = button.dataset.paymentId || `row-${Math.random()}`;
        const seconds = parseInt(button.dataset.secondsLeft || '0', 10);
        const safeSeconds = Number.isNaN(seconds) ? 0 : Math.max(0, seconds);
        states[paymentId] = Math.max(states[paymentId] ?? 0, safeSeconds);
    });

    const renderByState = () => {
        reminderButtons.forEach((button) => {
            const paymentId = button.dataset.paymentId;
            const secondsLeft = paymentId && states[paymentId] ? states[paymentId] : 0;

            if (secondsLeft <= 0) {
                button.disabled = false;
                button.classList.remove('is-disabled');
                button.innerHTML = '<i class="fa-regular fa-bell btn-icon"></i>Notify';
                button.dataset.secondsLeft = '0';
                return;
            }

            button.disabled = true;
            button.classList.add('is-disabled');
            button.dataset.secondsLeft = String(secondsLeft);
            button.innerHTML = `<i class="fa-regular fa-clock btn-icon"></i>${toHms(secondsLeft)}`;
        });
    };

    renderByState();

    const tick = () => {
        Object.keys(states).forEach((key) => {
            if (states[key] > 0) {
                states[key] -= 1;
            }
        });
        renderByState();
    };

    setInterval(tick, 1000);
})();

(() => {
    const endpointBase = window.CH_PAYMENTS?.endpointBase;
    if (!endpointBase) return;

    const currentParams = new URLSearchParams(window.location.search);
    const endpoint = currentParams.toString() ? `${endpointBase}?${currentParams.toString()}` : endpointBase;
    const money = (value) => `RM ${Number(value || 0).toFixed(2)}`;
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    let inFlight = false;
    window.refreshPaymentsSummary = async () => {
        if (inFlight) return;
        inFlight = true;
        try {
            const response = await fetch(endpoint, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            const summary = payload?.summary || {};
            const toolCounts = payload?.tool_counts || {};
            const debt = payload?.passenger_debt_summary || null;

            setText('paymentsToolMyRecords', String(Number(toolCounts.my_records || 0)));
            setText('paymentsToolQueueRecords', String(Number(toolCounts.queue_records || 0)));
            setText('paymentsToolUnpaidAmount', money(toolCounts.unpaid_amount || 0));
            setText('paymentsToolPendingAmount', money(toolCounts.pending_amount || 0));

            setText('paymentsMyUnpaidAmount', money(summary?.my?.unpaid?.amount || 0));
            setText('paymentsMyUnpaidCount', `${Number(summary?.my?.unpaid?.count || 0)} records`);
            setText('paymentsMyPendingAmount', money(summary?.my?.pending_confirmation?.amount || 0));
            setText('paymentsMyPendingCount', `${Number(summary?.my?.pending_confirmation?.count || 0)} records`);
            setText('paymentsMyPaidAmount', money(summary?.my?.paid?.amount || 0));
            setText('paymentsMyPaidCount', `${Number(summary?.my?.paid?.count || 0)} records`);

            setText('paymentsQueueUnpaidAmount', money(summary?.driver?.unpaid?.amount || 0));
            setText('paymentsQueueUnpaidCount', `${Number(summary?.driver?.unpaid?.count || 0)} records`);
            setText('paymentsQueuePendingAmount', money(summary?.driver?.pending_confirmation?.amount || 0));
            setText('paymentsQueuePendingCount', `${Number(summary?.driver?.pending_confirmation?.count || 0)} records`);
            setText('paymentsQueuePaidAmount', money(summary?.driver?.paid?.amount || 0));
            setText('paymentsQueuePaidCount', `${Number(summary?.driver?.paid?.count || 0)} records`);

            if (debt) {
                setText('paymentsDebtTotal', money(debt.total_amount || 0));
                setText(
                    'paymentsDebtMeta',
                    `${Number(debt.passenger_count || 0)} passengers, ${Number(debt.total_records || 0)} active records (unpaid + pending).`
                );
            }
        } catch (_error) {
        } finally {
            inFlight = false;
        }
    };
    // Event-Driven: Auto-polling timer removed to prevent server log spam!
})();

function pmtTab(btn, tab, skipAnimation = false) {
    const skel = document.getElementById('payments-skel-container');
    const real = document.getElementById('payments-real-container');

    const applyTabFilter = () => {
        if (btn) {
            document.querySelectorAll('.payments-tab').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
        }

        const driverRadioDesktop = document.getElementById('desktopSummaryDriver');
        const passengerRadioDesktop = document.getElementById('desktopSummaryPassenger');
        const driverRadioMobile = document.getElementById('mobileSummaryDriver');
        const passengerRadioMobile = document.getElementById('mobileSummaryPassenger');

        const driverLabelDesktop = document.querySelector('label[for="desktopSummaryDriver"]');
        const passengerLabelDesktop = document.querySelector('label[for="desktopSummaryPassenger"]');
        const driverLabelMobile = document.querySelector('label[for="mobileSummaryDriver"]');
        const passengerLabelMobile = document.querySelector('label[for="mobileSummaryPassenger"]');

        const setToggleState = (radio, label, enable) => {
            if (radio) radio.disabled = !enable;
            if (label) {
                if (enable) {
                    label.classList.remove('is-disabled');
                } else {
                    label.classList.add('is-disabled');
                }
            }
        };

        if (tab === 'collect') {
            if (driverRadioDesktop) driverRadioDesktop.checked = true;
            if (driverRadioMobile) driverRadioMobile.checked = true;

            setToggleState(driverRadioDesktop, driverLabelDesktop, true);
            setToggleState(driverRadioMobile, driverLabelMobile, true);

            setToggleState(passengerRadioDesktop, passengerLabelDesktop, false);
            setToggleState(passengerRadioMobile, passengerLabelMobile, false);
        } else if (tab === 'pay') {
            if (passengerRadioDesktop) passengerRadioDesktop.checked = true;
            if (passengerRadioMobile) passengerRadioMobile.checked = true;

            setToggleState(passengerRadioDesktop, passengerLabelDesktop, true);
            setToggleState(passengerRadioMobile, passengerLabelMobile, true);

            setToggleState(driverRadioDesktop, driverLabelDesktop, false);
            setToggleState(driverRadioMobile, driverLabelMobile, false);
        } else {
            setToggleState(driverRadioDesktop, driverLabelDesktop, true);
            setToggleState(driverRadioMobile, driverLabelMobile, true);
            setToggleState(passengerRadioDesktop, passengerLabelDesktop, true);
            setToggleState(passengerRadioMobile, passengerLabelMobile, true);
        }

        document.querySelectorAll('.js-payment-filter-item').forEach(function (row) {
            var s = row.dataset.pmtStatus;
            var perspective = row.dataset.paymentPerspective || '';

            var show = false;
            if (tab === 'all') {
                show = true;
            } else if (tab === 'pay') {
                show = (perspective === 'pay');
            } else if (tab === 'collect') {
                show = (perspective === 'collect');
            } else if (tab === 'unpaid') {
                show = (s === 'unpaid');
            } else if (tab === 'review') {
                show = (s === 'pending_confirmation');
            } else if (tab === 'confirmed') {
                show = (s === 'paid');
            } else if (tab === 'disputed') {
                show = (s === 'disputed');
            }

            if (show) {
                row.dataset.statusHidden = '0';
                row.classList.remove('payments-filter-hidden');
                row.style.setProperty('display', '', 'important');
            } else {
                row.dataset.statusHidden = '1';
                row.classList.add('payments-filter-hidden');
                row.style.setProperty('display', 'none', 'important');
            }
        });

        // Show/hide Select All checkbox based on tab
        var colCbTh = document.getElementById('colCbHeader');
        if (colCbTh) {
            var noSelectAll = (tab === 'all' || tab === 'confirmed');
            var label = colCbTh.querySelector('.ch-cb-container');
            if (label) { label.style.visibility = noSelectAll ? 'hidden' : 'visible'; }
            // Uncheck all when switching tab
            document.querySelectorAll('.bulk-payment-cb').forEach(function (cb) { cb.checked = false; });
            var selectAllCb = document.getElementById('bulkSelectAllCb');
            if (selectAllCb) { selectAllCb.checked = false; }
        }
        document.querySelectorAll('.js-payments-filter').forEach(function (panel) {
            panel.querySelector('input, select')?.dispatchEvent(new Event('input', { bubbles: true }));
        });
        if (typeof window.updatePaymentsVisibility === 'function') window.updatePaymentsVisibility();

        if (!skipAnimation && skel && real) {
            real.style.display = '';
            real.classList.add('loaded');
            real.style.opacity = '1';
            skel.style.opacity = '0';
            setTimeout(() => {
                skel.style.display = 'none';
            }, 120);
        }
    };

    if (!skipAnimation && skel && real) {
        skel.style.display = 'grid';
        skel.style.opacity = '1';
        real.classList.remove('loaded');
        real.style.opacity = '0';
        setTimeout(applyTabFilter, 220);
    } else {
        applyTabFilter();
    }
}

// Initial tab filter execution on load
document.addEventListener('DOMContentLoaded', () => {
    const activeTab = document.querySelector('.payments-tab.active');
    if (activeTab) {
        const tabText = activeTab.textContent.toLowerCase();
        let tabKey = 'all';
        if (tabText.includes('to pay')) tabKey = 'pay';
        else if (tabText.includes('to collect')) tabKey = 'collect';
        else if (tabText.includes('unpaid')) tabKey = 'unpaid';
        else if (tabText.includes('review') || tabText.includes('pending')) tabKey = 'review';
        else if (tabText.includes('confirmed')) tabKey = 'confirmed';

        pmtTab(activeTab, tabKey, true);
    }
});

// Handle radio mode switch (As driver vs As passenger)
document.addEventListener('change', (e) => {
    if (['desktopSummaryDriver', 'mobileSummaryDriver', 'desktopSummaryPassenger', 'mobileSummaryPassenger'].includes(e.target.id)) {
        const activeTab = document.querySelector('.payments-tab.active');
        if (activeTab) {
            const tabText = activeTab.textContent.toLowerCase();
            let tabKey = 'all';
            if (tabText.includes('to pay')) tabKey = 'pay';
            else if (tabText.includes('to collect')) tabKey = 'collect';
            else if (tabText.includes('unpaid')) tabKey = 'unpaid';
            else if (tabText.includes('review') || tabText.includes('pending')) tabKey = 'review';
            else if (tabText.includes('confirmed')) tabKey = 'confirmed';

            pmtTab(activeTab, tabKey);
        }
    }
});

(() => {
    const bulkActionBar = document.getElementById('bulkActionBar');
    const bulkActionCount = document.getElementById('bulkActionCount');
    const bulkCancelBtn = document.getElementById('bulkCancelBtn');

    if (!bulkActionBar || !bulkActionCount) return;

    const bulkSubmitBtn = bulkActionBar.querySelector('button[type="submit"]');
    const bulkSelectAllCb = document.getElementById('bulkSelectAllCb');

    const updateBulkActionState = () => {
        const selectedPaymentIds = new Set();
        const visiblePaymentIds = new Set();
        let hasSelf = false;

        document.querySelectorAll('.bulk-payment-cb').forEach(cb => {
            const row = cb.closest('.js-payment-filter-item');
            const isRowHidden = row && (row.dataset.statusHidden === '1' || row.classList.contains('payments-filter-hidden'));

            if (!isRowHidden && cb.value) {
                visiblePaymentIds.add(cb.value);
                if (cb.checked) {
                    selectedPaymentIds.add(cb.value);
                    if (cb.getAttribute('data-is-self') === '1') {
                        hasSelf = true;
                    }
                }
            }
        });

        const count = selectedPaymentIds.size;
        const visibleCountTotal = visiblePaymentIds.size;

        if (bulkSelectAllCb) {
            bulkSelectAllCb.checked = visibleCountTotal > 0 && count === visibleCountTotal;
        }

        const aiFab = document.getElementById('ai-fab');
        if (count > 0) {
            bulkActionCount.textContent = `${count} selected`;
            bulkActionBar.style.display = 'flex';
            if (aiFab) aiFab.style.setProperty('display', 'none', 'important');
            if (bulkSubmitBtn) {
                if (hasSelf) {
                    bulkSubmitBtn.innerHTML = '<i class="fa-solid fa-check-double"></i> Mark Selected as Paid';
                } else {
                    bulkSubmitBtn.innerHTML = '<i class="fa-solid fa-check-double"></i> Confirm Selected Payments';
                }
            }
        } else {
            bulkActionBar.style.display = 'none';
            if (aiFab) aiFab.style.display = '';
        }
    };

    ['change', 'click'].forEach(evtType => {
        document.body.addEventListener(evtType, (e) => {
            if (e.target && e.target.classList.contains('bulk-payment-cb')) {
                const val = e.target.value;
                const isChecked = e.target.checked;

                if (val) {
                    document.querySelectorAll(`.bulk-payment-cb[value="${val}"]`).forEach(sameCb => {
                        sameCb.checked = isChecked;
                    });
                }

                if (isChecked) {
                    const isSelf = e.target.getAttribute('data-is-self') === '1';
                    document.querySelectorAll('.bulk-payment-cb').forEach(cb => {
                        if (cb.value !== val) {
                            const targetIsSelf = cb.getAttribute('data-is-self') === '1';
                            if (isSelf !== targetIsSelf) {
                                cb.checked = false;
                            }
                        }
                    });
                }
                updateBulkActionState();
            }
        });
    });

    // Handle Select All via event delegation (same as row checkboxes)
    document.body.addEventListener('change', (e) => {
        if (e.target && e.target.id === 'bulkSelectAllCb') {
            const checked = e.target.checked;
            document.querySelectorAll('.bulk-payment-cb').forEach(cb => {
                const row = cb.closest('.js-payment-filter-item');
                if (row && (row.dataset.statusHidden === '1' || row.classList.contains('payments-filter-hidden'))) {
                    return;
                }
                cb.checked = checked;
            });
            updateBulkActionState();
        }
    });

    const floatingSelectAllBtn = document.getElementById('floatingSelectAllBtn');
    if (floatingSelectAllBtn) {
        floatingSelectAllBtn.addEventListener('click', () => {
            const visibleCbs = Array.from(document.querySelectorAll('.bulk-payment-cb')).filter(cb => {
                const row = cb.closest('.js-payment-filter-item');
                return row && row.dataset.statusHidden !== '1' && !row.classList.contains('payments-filter-hidden');
            });

            const allChecked = visibleCbs.length > 0 && visibleCbs.every(cb => cb.checked);
            const targetState = !allChecked;

            visibleCbs.forEach(cb => {
                cb.checked = targetState;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            });

            if (bulkSelectAllCb) {
                bulkSelectAllCb.checked = targetState;
            }

            updateBulkActionState();
        });
    }

    if (bulkCancelBtn) {
        bulkCancelBtn.addEventListener('click', () => {
            document.querySelectorAll('.bulk-payment-cb').forEach(cb => {
                cb.checked = false;
            });
            const selectAllCb = document.getElementById('bulkSelectAllCb');
            if (selectAllCb) selectAllCb.checked = false;
            updateBulkActionState();
        });
    }

    // Clear checkboxes when changing tabs
    document.querySelectorAll('.payments-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.bulk-payment-cb').forEach(cb => {
                cb.checked = false;
            });
            if (bulkSelectAllCb) {
                bulkSelectAllCb.checked = false;
            }
            updateBulkActionState();
        });
    });

    // Initialize on load
    updateBulkActionState();
})();

// ── Handle "Select Unpaid" button click on summary receipt cards ──
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-select-person-unpaid');
    if (!btn) return;

    e.preventDefault();
    const personName = (btn.dataset.personName || '').trim().toLowerCase();
    const targetDirection = btn.dataset.direction || 'collect';

    let targetTabBtn = null;
    if (targetDirection === 'pay') {
        targetTabBtn = Array.from(document.querySelectorAll('.payments-tab')).find(b => b.textContent.toLowerCase().includes('to pay'));
    } else if (targetDirection === 'collect') {
        targetTabBtn = Array.from(document.querySelectorAll('.payments-tab')).find(b => b.textContent.toLowerCase().includes('to collect'));
    }

    if (targetTabBtn && typeof pmtTab === 'function') {
        pmtTab(targetTabBtn, targetDirection, true);
    }

    const selectPersonItems = () => {
        // 1. Uncheck all checkboxes first
        document.querySelectorAll('.bulk-payment-cb').forEach(cb => {
            cb.checked = false;
        });

        // 2. Find and check all unpaid/pending rows matching this person's name
        document.querySelectorAll('.js-payment-filter-item').forEach(row => {
            if (row.dataset.statusHidden === '1' || row.classList.contains('payments-filter-hidden')) return;

            const counterpartyEl = row.querySelector('.payment-name');
            const rowPersonName = counterpartyEl ? counterpartyEl.textContent.trim().toLowerCase() : '';
            const rowPassenger = (row.dataset.passenger || '').trim().toLowerCase();
            const status = (row.dataset.pmtStatus || '').trim().toLowerCase();

            // Match counterparty passenger name AND ensure status is unpaid or pending (NEVER paid)
            const isMatch = (personName && rowPersonName && (rowPersonName.includes(personName) || personName.includes(rowPersonName)))
                || (personName && rowPassenger && (rowPassenger.includes(personName) || personName.includes(rowPassenger)));
            const isUnpaidOrPending = (status === 'unpaid' || status === 'pending_confirmation');

            if (isMatch && isUnpaidOrPending) {
                const cb = row.querySelector('.bulk-payment-cb');
                if (cb) {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });

        if (typeof updateBulkActionState === 'function') {
            updateBulkActionState();
        }
    };

    selectPersonItems();
});

window.clearPaymentFilters = function() {
    document.querySelectorAll('.payments-filter-panel input, .payments-filter-panel select').forEach((field) => {
        field.value = '';
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    });
    if (typeof window.updatePaymentsVisibility === 'function') window.updatePaymentsVisibility();
};

window.updatePaymentsVisibility = function() {
    const allItems = document.querySelectorAll('.js-payment-filter-item');
    let visibleCount = 0;
    allItems.forEach((item) => {
        const isHidden = item.classList.contains('payments-filter-hidden') || item.style.display === 'none';
        if (!isHidden) {
            visibleCount += 1;
        }
    });

    const tableWraps = document.querySelectorAll('.payments-table-wrap, .payments-mobile-list');
    tableWraps.forEach((tblWrap) => {
        if (visibleCount === 0) {
            tblWrap.style.setProperty('display', 'none', 'important');
        } else {
            tblWrap.style.setProperty('display', '', '');
        }
    });

    const emptyStates = document.querySelectorAll('[data-filter-empty]');
    emptyStates.forEach((emptyState) => {
        if (visibleCount === 0) {
            emptyState.classList.add('show');
            emptyState.style.setProperty('display', 'flex', 'important');
        } else {
            emptyState.classList.remove('show');
            emptyState.style.setProperty('display', 'none', 'important');
        }
    });

    const paginationWraps = document.querySelectorAll('.payments-pagination-wrap, .pagination-wrap');
    paginationWraps.forEach((pagWrap) => {
        if (visibleCount === 0) {
            pagWrap.style.setProperty('display', 'none', 'important');
        } else {
            pagWrap.style.setProperty('display', '', '');
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if (typeof window.updatePaymentsVisibility === 'function') window.updatePaymentsVisibility();
    }, 150);
});
