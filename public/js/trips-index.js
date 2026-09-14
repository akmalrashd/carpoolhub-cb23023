/* Extracted from resources/views/trips/index.blade.php — logic; page values come from window.CH_TRIPS.
   confirmTripCancel() and the "Trip Details" modal's own open/populate logic
   now live in public/js/trip-details-modal.js (loaded alongside this file on
   this page) — moved so the chat thread page can reuse that exact same
   popup without pulling in everything else in this file. */

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

        // ── Skeleton screen & AJAX Page Loader ──
        (() => {
            const skel = document.getElementById('trips-skel-container');
            const real = document.getElementById('trips-real-container');

            const showSkeleton = () => {
                if (real) {
                    real.style.transition = 'opacity 0.12s ease';
                    real.style.opacity = '0.4';
                    real.style.pointerEvents = 'none';
                }
            };

            const hideSkeleton = () => {
                if (real) {
                    real.style.display = '';
                    real.style.opacity = '1';
                    real.style.pointerEvents = '';
                    if (skel) {
                        skel.style.display = 'none';
                        skel.style.opacity = '0';
                        skel.style.pointerEvents = 'none';
                    }
                }
            };

            const initOrHide = () => {
                hideSkeleton();
            };

            // Run hide on page ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initOrHide);
            } else {
                initOrHide();
            }

            // AJAX fetching function
            const fetchPage = async (url) => {
                showSkeleton();

                try {
                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error();
                    const html = await res.text();
                    
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const newReal = doc.getElementById('trips-real-container');
                    const currentReal = document.getElementById('trips-real-container');
                    
                    // Replace active states for chips and tabs (since they are outside the real container)
                    const newChipsRow = doc.querySelector('.trips-chip-row');
                    const currentChipsRow = document.querySelector('.trips-chip-row');
                    if (newChipsRow && currentChipsRow) {
                        currentChipsRow.innerHTML = newChipsRow.innerHTML;
                    }
                    const newTabsRow = doc.querySelector('.tabs');
                    const currentTabsRow = document.querySelector('.tabs');
                    if (newTabsRow && currentTabsRow) {
                        currentTabsRow.innerHTML = newTabsRow.innerHTML;
                    }

                    // The pager lives outside #trips-real-container, so swapping
                    // the list alone left the old one in place: it kept the
                    // previous page highlighted and its links still carried the
                    // previous filter, which is why clicking through pages
                    // wandered off. Swap it with the list.
                    const newPager = doc.querySelector('.pagination-wrap');
                    const currentPager = document.querySelector('.pagination-wrap');
                    if (newPager && currentPager) {
                        currentPager.innerHTML = newPager.innerHTML;
                    }

                    if (newReal && currentReal) {
                        currentReal.innerHTML = newReal.innerHTML;
                        history.pushState(null, '', url);

                        if (typeof window.initTripsBulkSelect === 'function') {
                            window.initTripsBulkSelect();
                        }
                    }
                    // A new page starts at the top of the list, same as the
                    // payments ledger — otherwise you land mid-list on rows you
                    // have not seen.
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } catch (_e) {
                    // Fallback to normal navigation
                    window.location.href = url;
                } finally {
                    hideSkeleton();
                }
            };

            // Chips, tabs and page links are all handled by one delegated
            // listener. Binding the page links individually meant every AJAX
            // swap added another listener to them, so a single click fired as
            // many fetches as pages you had already visited.
            document.addEventListener('click', (e) => {
                const pageLink = e.target.closest('.pagination-wrap a');
                if (pageLink) {
                    e.preventDefault();
                    fetchPage(pageLink.href);
                    return;
                }

                const tab = e.target.closest('.tab[data-tab]');
                if (tab) {
                    e.preventDefault();
                    const key = tab.dataset.tab;
                    const url = new URL(window.location.href);
                    url.searchParams.set('status_filter', key);
                    url.searchParams.delete('page');
                    fetchPage(url.toString());
                    return;
                }

                const chip = e.target.closest('.trips-chip');
                if (chip) {
                    e.preventDefault();
                    fetchPage(chip.href);
                }
            });

            // Form Submit Interceptor
            const filterForm = document.getElementById('tripsFilterPanel');
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
                    const isTextField = field.tagName === 'INPUT' && (field.type === 'text' || field.type === 'search');
                    field.addEventListener(isTextField ? 'input' : 'change', () => {
                        window.clearTimeout(submitTimer);
                        submitTimer = window.setTimeout(() => {
                            filterForm.dispatchEvent(new Event('submit', { cancelable: true }));
                        }, isTextField ? 400 : 250);
                    });
                });
            }

        })();


        (() => {
            const filterForm = document.querySelector('.trips-filter-form');
            if (!filterForm) return;

            let submitTimer = null;
            const autoSubmit = () => {
                window.clearTimeout(submitTimer);
                submitTimer = window.setTimeout(() => filterForm.requestSubmit(), 250);
            };

            filterForm.querySelectorAll('input, select').forEach((field) => {
                field.addEventListener('change', autoSubmit);
            });
        })();

        (() => {
            const params = new URLSearchParams(window.location.search);
            const focusTrip = String(params.get('focus_trip') || '').trim();
            if (!focusTrip) return;

            const targets = Array.from(document.querySelectorAll('[data-trip-anchor]'))
                .filter((el) => String(el.getAttribute('data-trip-anchor') || '').trim() === focusTrip);
            if (targets.length === 0) return;

            const target = targets.find((el) => el instanceof HTMLElement && el.offsetParent !== null) || targets[0];
            if (!(target instanceof HTMLElement)) return;

            requestAnimationFrame(() => {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                target.classList.add('trip-focus-highlight');
                window.setTimeout(() => target.classList.remove('trip-focus-highlight'), 2200);
            });

            // Deep-link: ?focus_trip=<id> (e.g. tapping a trip card on Home) also
            // opens that trip's details popup directly instead of just scrolling to it.
            // ?open_requests=1 alongside it (a new-join-request notification) cascades
            // one step further into the "Manage requests" popup on top of that — the
            // request button's own dataset is populated synchronously inside the Trip
            // Details click handler, so a short delay after it opens is all that's
            // needed before the second click finds it ready.
            const openRequests = params.get('open_requests') === '1';
            const detailButtons = Array.from(document.querySelectorAll('.open-trip-modal-btn'))
                .filter((el) => String(el.dataset.tripId || '').trim() === focusTrip);
            const detailBtn = detailButtons.find((el) => el instanceof HTMLElement && el.offsetParent !== null) || detailButtons[0];
            if (detailBtn instanceof HTMLElement) {
                window.setTimeout(() => {
                    detailBtn.click();
                    if (openRequests) {
                        window.setTimeout(() => {
                            document.getElementById('tripModalRequestsBtn')?.click();
                        }, 400);
                    }
                }, 300);
            }
        })();

        (() => {
            const modal = document.getElementById('tripPaymentReviewModal');
            const list = document.getElementById('tripPaymentReviewList');
            const sub = document.getElementById('tripPaymentReviewSub');
            const closeBtn = document.getElementById('tripPaymentReviewClose');
            const buttons = document.querySelectorAll('.open-trip-payment-review');
            if (!modal || !list || !closeBtn) return;

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const csrf = window.CH_TRIPS.csrf;
            const decodePayload = (encoded) => {
                try {
                    const bytes = Uint8Array.from(atob(String(encoded || '')), (char) => char.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                } catch (_error) {
                    return [];
                }
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const qrPreviewHtml = (url, label) => {
                const safeUrl = String(url || '').trim();
                return safeUrl
                    ? `<img src="${escapeHtml(safeUrl)}" alt="${escapeHtml(label)}">`
                    : '<span class="trip-paynow-qr-empty">No QR uploaded</span>';
            };
            const resultHtml = (message, isError = false) => `
                <div class="trip-payment-popup-result ${isError ? 'error' : ''}">
                    <span class="trip-payment-popup-icon"><i class="fa-solid ${isError ? 'fa-xmark' : 'fa-check'}"></i></span>
                    <span class="trip-payment-popup-title">${isError ? 'Action failed' : 'Successful'}</span>
                    <span class="trip-payment-popup-message">${escapeHtml(message)}</span>
                </div>
            `;
            let activePayments = [];
            let shouldOpenHistory = false;
            let shouldRefreshOnClose = false;
            const normalizeUrlPath = (url) => {
                try { return new URL(url, window.location.origin).pathname; } catch (_error) { return String(url || ''); }
            };
            const findPaymentForForm = (formEl) => {
                const actionPath = normalizeUrlPath(formEl.action);
                return activePayments.find((payment) => [payment.confirm_url, payment.reject_url, payment.reminder_url]
                    .filter(Boolean)
                    .some((url) => normalizeUrlPath(url) === actionPath));
            };
            const applyPaymentUpdate = (payment, payload) => {
                if (!payment || !payload || !payload.payment_status) return;
                const nowText = new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Kuala_Lumpur' });
                payment.status = payload.payment_status;
                payment.status_label = payload.payment_status === 'paid'
                    ? 'Paid'
                    : (payload.payment_status === 'pending_confirmation' ? 'Awaiting confirmation' : 'Unpaid');
                if (payload.payment_status === 'paid') {
                    payment.confirmed_at = nowText;
                    payment.marked_at = payment.marked_at || nowText;
                    payment.marked_at_full = payment.marked_at_full || nowText;
                }
            };
            const removeSuccessCard = (containerEl, afterRemove = null) => {
                window.setTimeout(() => {
                    containerEl.classList.add('is-removing');
                    window.setTimeout(() => {
                        containerEl.remove();
                        if (typeof afterRemove === 'function') afterRemove();
                    }, 180);
                }, 2500);
            };
            const submitPopupForm = (formEl, containerEl) => {
                const submitBtn = formEl.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Processing';
                }

                return fetch(formEl.action, {
                    method: 'POST',
                    body: new FormData(formEl),
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
                        const payment = findPaymentForForm(formEl);
                        applyPaymentUpdate(payment, payload);
                        shouldOpenHistory = true;
                        shouldRefreshOnClose = true;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                        containerEl.innerHTML = resultHtml(payload.message || 'Payment updated.');
                        removeSuccessCard(containerEl, () => render(activePayments));
                    })
                    .catch((error) => {
                        containerEl.innerHTML = resultHtml(error.message || 'The payment action could not be completed.', true);
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });
            };
            const form = (action, method, label, classes, icon, extra = '') => `
                <form method="POST" action="${escapeHtml(action)}">
                    <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                    ${method ? `<input type="hidden" name="_method" value="${escapeHtml(method)}">` : ''}
                    ${extra}
                    <button type="submit" class="trip-payment-review-btn ${classes}">
                        <i class="${escapeHtml(icon)}"></i>${escapeHtml(label)}
                    </button>
                </form>
            `;
            const renderActions = (payment) => {
                if (payment.status === 'pending_confirmation') {
                    return `
                        <button type="button"
                            class="trip-payment-review-btn danger js-trip-payment-dispute"
                            data-action="${escapeHtml(payment.reject_url)}"
                            data-passenger="${escapeHtml(payment.passenger)}">
                            Dispute
                        </button>
                        ${form(payment.confirm_url, 'PATCH', 'Confirm', 'confirm', 'fa-solid fa-check')}
                    `;
                }

                return `
                    ${form(payment.reminder_url, '', 'Notify', '', 'fa-regular fa-bell')}
                    ${form(payment.confirm_url, 'PATCH', 'Mark paid', 'warn', 'fa-solid fa-check')}
                `;
            };
            const render = (payments) => {
                const rows = Array.isArray(payments) ? payments : [];
                const activeRows = rows.filter((payment) => payment.status !== 'paid');
                const historyRows = rows.filter((payment) => payment.status === 'paid');

                if (rows.length === 0) {
                    list.innerHTML = '<div class="trip-payment-review-empty">No pending payment records for this trip.</div>';
                    return;
                }

                const activeHtml = activeRows.length
                    ? activeRows.map((payment) => `
                    <article class="trip-payment-review-item">
                        <div class="trip-payment-review-top">
                            <div class="trip-payment-review-person">
                                <span class="trip-payment-review-avatar">${escapeHtml(payment.initials || 'P')}</span>
                                <span>
                                    <span class="trip-payment-review-name">${escapeHtml(payment.passenger)}</span>
                                    <span class="trip-payment-review-route">${escapeHtml(payment.trip)} · ${escapeHtml(payment.method || 'DuitNow')}</span>
                                </span>
                            </div>
                            <span class="trip-payment-review-status">${escapeHtml(payment.status_label || payment.status)}</span>
                        </div>
                        <div class="trip-payment-review-amount">
                            <span>
                                <span>${escapeHtml(payment.method || 'DuitNow')}</span>
                                <strong>RM ${escapeHtml(payment.amount)}</strong>
                            </span>
                            <span class="trip-payment-review-time">${escapeHtml(payment.marked_at || '-')}</span>
                        </div>
                        <div class="trip-payment-review-actions">
                            ${renderActions(payment)}
                        </div>
                    </article>
                `).join('')
                    : (historyRows.length ? '' : '<div class="trip-payment-review-empty">No pending payment records for this trip.</div>');

                const historyHtml = historyRows.length
                    ? `
                        <details class="trip-payment-review-history" ${shouldOpenHistory ? 'open' : ''}>
                            <summary>Confirmed history · ${historyRows.length}</summary>
                            <div class="trip-payment-review-history-list">
                                ${historyRows.map((payment) => `
                                    <div class="trip-payment-review-history-row">
                                        <span>
                                            <strong>${escapeHtml(payment.passenger)}</strong>
                                            <span>${escapeHtml(payment.trip)} · ${escapeHtml(payment.confirmed_at || payment.marked_at || '-')}</span>
                                        </span>
                                        <span class="trip-payment-review-history-amount">RM ${escapeHtml(payment.amount)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </details>
                    `
                    : '';

                list.innerHTML = activeHtml + historyHtml;
            };
            const open = (button) => {
                activePayments = decodePayload(button.dataset.paymentsB64 || '');
                shouldOpenHistory = false;
                shouldRefreshOnClose = false;
                const tripIds = String(button.dataset.tripIds || '').trim();
                sub.textContent = tripIds
                    ? `${button.dataset.routeName || 'Trip'} · Trip IDs ${tripIds}`
                    : (button.dataset.routeName || 'Confirm passenger payments for this trip.');
                showModalSkeleton(list);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    render(activePayments);
                }, 240);
            };
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                if (shouldRefreshOnClose) {
                    window.location.reload();
                }
            };
            window.CarpoolBottomSheet?.enable({
                modal: modal,
                card: modal.querySelector('.trip-payment-review-card'),
                head: modal.querySelector('.trip-payment-review-head'),
                closeFn: close,
            });

            buttons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    open(button);
                });
            });
            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
            });
            list.addEventListener('click', (event) => {
                const dispute = event.target.closest('.js-trip-payment-dispute');
                if (!dispute) return;

                const reason = window.prompt(`Reason for disputing ${dispute.dataset.passenger || 'this payment'}?`);
                if (!reason || !reason.trim()) return;

                const rejectForm = document.createElement('form');
                rejectForm.method = 'POST';
                rejectForm.action = dispute.dataset.action;
                rejectForm.innerHTML = `
                    <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="rejection_reason" value="${escapeHtml(reason.trim())}">
                `;
                submitPopupForm(rejectForm, dispute.closest('.trip-payment-review-item') || list);
            });
            list.addEventListener('submit', (event) => {
                const formEl = event.target.closest('form');
                if (!formEl) return;
                event.preventDefault();
                submitPopupForm(formEl, formEl.closest('.trip-payment-review-item') || list);
            });
        })();

        (() => {
            const modal = document.getElementById('tripPayNowModal');
            const list = document.getElementById('tripPayNowList');
            const sub = document.getElementById('tripPayNowSub');
            const closeBtn = document.getElementById('tripPayNowClose');
            const buttons = document.querySelectorAll('.open-trip-paynow');
            if (!modal || !list || !closeBtn) return;

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const csrf = window.CH_TRIPS.csrf;
            const decodePayload = (encoded) => {
                try {
                    const bytes = Uint8Array.from(atob(String(encoded || '')), (char) => char.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                } catch (_error) {
                    return [];
                }
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const qrPreviewHtml = (url, label) => {
                const safeUrl = String(url || '').trim();
                return safeUrl
                    ? `<img src="${escapeHtml(safeUrl)}" alt="${escapeHtml(label)}">`
                    : '<span class="trip-paynow-qr-empty">No QR uploaded</span>';
            };
            const resultHtml = (message, isError = false) => `
                <div class="trip-payment-popup-result ${isError ? 'error' : ''}">
                    <span class="trip-payment-popup-icon"><i class="fa-solid ${isError ? 'fa-xmark' : 'fa-check'}"></i></span>
                    <span class="trip-payment-popup-title">${isError ? 'Action failed' : 'Successful'}</span>
                    <span class="trip-payment-popup-message">${escapeHtml(message)}</span>
                </div>
            `;
            let activePayments = [];
            let shouldOpenHistory = false;
            let shouldRefreshOnClose = false;
            const normalizeUrlPath = (url) => {
                try { return new URL(url, window.location.origin).pathname; } catch (_error) { return String(url || ''); }
            };
            const findPaymentForForm = (formEl) => {
                const actionPath = normalizeUrlPath(formEl.action);
                return activePayments.find((payment) => normalizeUrlPath(payment.mark_url) === actionPath);
            };
            const applyPaymentUpdate = (payment, payload) => {
                if (!payment || !payload || !payload.payment_status) return;
                const nowText = new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Kuala_Lumpur' });
                payment.status = payload.payment_status;
                payment.status_label = payload.payment_status === 'paid'
                    ? 'Paid'
                    : (payload.payment_status === 'pending_confirmation' ? 'Awaiting confirmation' : 'Unpaid');
                payment.marked_at = nowText;
                payment.marked_at_full = nowText;
                if (payload.payment_status === 'paid') {
                    payment.confirmed_at = nowText;
                }
            };
            const removeSuccessCard = (containerEl, afterRemove = null) => {
                window.setTimeout(() => {
                    containerEl.classList.add('is-removing');
                    window.setTimeout(() => {
                        containerEl.remove();
                        if (typeof afterRemove === 'function') afterRemove();
                    }, 180);
                }, 2500);
            };
            const submitPopupForm = (formEl, containerEl) => {
                const submitBtn = formEl.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Processing';
                }

                return fetch(formEl.action, {
                    method: 'POST',
                    body: new FormData(formEl),
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
                        const payment = findPaymentForForm(formEl);
                        applyPaymentUpdate(payment, payload);
                        shouldOpenHistory = true;
                        shouldRefreshOnClose = true;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                        containerEl.innerHTML = resultHtml(payload.message || 'Payment updated.');
                        removeSuccessCard(containerEl, () => render(activePayments));
                    })
                    .catch((error) => {
                        containerEl.innerHTML = resultHtml(error.message || 'The payment action could not be completed.', true);
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });
            };
            const render = (payments) => {
                const allRows = Array.isArray(payments) ? payments : [];
                const rows = allRows.filter((payment) => payment.status === 'unpaid');
                const awaitingRows = allRows.filter((payment) => payment.status === 'pending_confirmation');
                const historyRows = allRows.filter((payment) => payment.status === 'paid');
                const fareBreakdown = (payment) => payment?.has_extra_fee
                    ? `<span style="display:block;color:#64748b;font-size:12px;">Base RM ${escapeHtml(payment.base_amount || '0.00')} + extra RM ${escapeHtml(payment.extra_fee || '0.00')}</span>`
                    : '';

                const hasStatusRows = awaitingRows.length || historyRows.length;
                const unpaidHtml = rows.length ? rows.map((payment) => `
                    <article class="trip-payment-review-item">
                        <div class="trip-payment-review-top">
                            <div class="trip-payment-review-person">
                                <span class="trip-payment-review-avatar">${escapeHtml(payment.initials || 'P')}</span>
                                <span>
                                    <span class="trip-payment-review-name">${escapeHtml(payment.passenger)}</span>
                                    <span class="trip-payment-review-route">${escapeHtml(payment.trip)} · ${escapeHtml(payment.method || 'DuitNow')}</span>
                                </span>
                            </div>
                            <span class="trip-payment-review-status">Unpaid</span>
                        </div>
                        <div class="trip-payment-review-amount">
                            <span>
                                <span>Amount due</span>
                                <strong>RM ${escapeHtml(payment.amount)}</strong>
                                ${fareBreakdown(payment)}
                            </span>
                        </div>
                        <div class="trip-paynow-driver">
                            <div class="trip-paynow-driver-head">
                                <span class="trip-paynow-driver-avatar">
                                    ${payment.driver_photo ? `<img src="${escapeHtml(payment.driver_photo)}" alt="${escapeHtml(payment.driver || 'Driver')}">` : escapeHtml(String(payment.driver || 'D').trim().charAt(0).toUpperCase() || 'D')}
                                </span>
                                <span>
                                    <span class="trip-paynow-driver-name">${escapeHtml(payment.driver || 'Driver')}</span>
                                    <span class="trip-paynow-driver-email">${escapeHtml(payment.driver_email || '-')}</span>
                                </span>
                            </div>
                            <div class="trip-paynow-driver-fields">
                                <div class="trip-paynow-driver-field">
                                    <span>Bank / Wallet</span>
                                    <strong>${escapeHtml(payment.driver_bank || '-')}</strong>
                                </div>
                                <div class="trip-paynow-driver-field">
                                    <span>Account Holder</span>
                                    <strong>${escapeHtml(payment.driver_account_name || '-')}</strong>
                                </div>
                                <div class="trip-paynow-driver-field">
                                    <span>Account Number</span>
                                    <strong>${escapeHtml(payment.driver_account_number || '-')}</strong>
                                </div>
                            </div>
                            <div class="trip-paynow-qr-grid">
                                <div class="trip-paynow-qr-card">
                                    <span class="trip-paynow-qr-title"><i class="fa-solid fa-qrcode"></i>DuitNow QR</span>
                                    <div class="trip-paynow-qr-preview">${qrPreviewHtml(payment.driver_duitnow_qr, 'DuitNow QR')}</div>
                                </div>
                                <div class="trip-paynow-qr-card">
                                    <span class="trip-paynow-qr-title"><i class="fa-solid fa-qrcode"></i>Touch 'n Go QR</span>
                                    <div class="trip-paynow-qr-preview">${qrPreviewHtml(payment.driver_tng_qr, "Touch 'n Go QR")}</div>
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="${escapeHtml(payment.mark_url)}" class="trip-paynow-form">
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
                `).join('') : (hasStatusRows ? '' : '<div class="trip-payment-review-empty">No unpaid payment record for this trip.</div>');

                const awaitingHtml = awaitingRows.length ? `
                    <div class="trip-payment-review-history" style="padding:8px 12px;">
                        ${awaitingRows.map((payment) => `
                            <div class="trip-payment-review-history-row" style="padding:4px 0;border-top:0;align-items:center;">
                                <span>
                                    <strong>${escapeHtml(payment.trip)}</strong>
                                    <span>Pending driver confirmation · ${escapeHtml(payment.marked_at_full || payment.marked_at || '-')}</span>
                                </span>
                                <span class="trip-payment-review-status">Awaiting</span>
                            </div>
                        `).join('')}
                    </div>
                ` : '';

                const historyHtml = historyRows.length ? `
                    <details class="trip-payment-review-history" ${shouldOpenHistory ? 'open' : ''}>
                        <summary>Your payment history · ${historyRows.length}</summary>
                        <div class="trip-payment-review-history-list">
                            ${historyRows.map((payment) => `
                                <button type="button" class="trip-payment-review-history-row js-view-receipt" data-payment='${escapeHtml(JSON.stringify(payment))}' style="width:100%;border:0;background:transparent;text-align:left;cursor:pointer;">
                                    <span>
                                        <strong>${escapeHtml(payment.trip)}</strong>
                                        <span>${escapeHtml(payment.status_label || payment.status)} · ${escapeHtml(payment.marked_at_full || payment.marked_at || '-')}</span>
                                    </span>
                                    <span class="trip-payment-review-history-amount">View receipt<br>RM ${escapeHtml(payment.amount)}${fareBreakdown(payment)}</span>
                                </button>
                            `).join('')}
                        </div>
                    </details>
                ` : '';

                list.innerHTML = unpaidHtml + awaitingHtml + historyHtml;
            };
            const open = (button) => {
                activePayments = decodePayload(button.dataset.paymentsB64 || '');
                shouldOpenHistory = false;
                shouldRefreshOnClose = false;
                sub.textContent = button.dataset.routeName || 'Mark your trip payment as paid.';
                showModalSkeleton(list);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    render(activePayments);
                }, 240);
            };
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                if (shouldRefreshOnClose) {
                    window.location.reload();
                }
            };
            window.CarpoolBottomSheet?.enable({
                modal: modal,
                card: modal.querySelector('.trip-payment-review-card'),
                head: modal.querySelector('.trip-payment-review-head'),
                closeFn: close,
            });

            buttons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    open(button);
                });
            });
            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
            });
            list.addEventListener('submit', (event) => {
                const formEl = event.target.closest('form');
                if (!formEl) return;
                event.preventDefault();
                submitPopupForm(formEl, formEl.closest('.trip-payment-review-item') || list);
            });
        })();

        (() => {
            const modal = document.getElementById('tripReceiptsModal');
            const list = document.getElementById('tripReceiptsList');
            const sub = document.getElementById('tripReceiptsSub');
            const closeBtn = document.getElementById('tripReceiptsClose');
            const buttons = document.querySelectorAll('.open-trip-receipts');
            if (!modal || !list || !closeBtn) return;

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const decodePayload = (encoded) => {
                try {
                    const bytes = Uint8Array.from(atob(String(encoded || '')), (char) => char.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                } catch (_error) {
                    return [];
                }
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const receiptHtml = (payment) => `
                <article class="trip-receipt-card" id="tripReceiptPrintable">
                    <div class="trip-receipt-head">
                        <span>
                            <h4 class="trip-receipt-title">CarpoolHub Receipt</h4>
                            <span class="trip-receipt-id">Receipt ${escapeHtml(payment.receipt_no || ('PAY-' + payment.id))} · ${escapeHtml(payment.trip)}</span>
                        </span>
                        <span class="trip-receipt-status ${payment.status === 'paid' ? 'paid' : ''}">${escapeHtml(payment.status_label || payment.status)}</span>
                    </div>
                    <div class="trip-receipt-total">
                        <span>Amount paid</span>
                        <strong>RM ${escapeHtml(payment.amount)}</strong>
                        ${payment?.has_extra_fee ? `<span style="display:block;color:#64748b;font-size:12px;">Base RM ${escapeHtml(payment.base_amount || '0.00')} + custom extra RM ${escapeHtml(payment.extra_fee || '0.00')}</span>` : ''}
                    </div>
                    <div class="trip-receipt-lines">
                        <div class="trip-receipt-line"><span>Passenger</span><strong>${escapeHtml(payment.passenger)}</strong></div>
                        <div class="trip-receipt-line"><span>Driver</span><strong>${escapeHtml(payment.driver || '-')}</strong></div>
                        <div class="trip-receipt-line"><span>Route</span><strong>${escapeHtml(payment.route_name || '-')}</strong></div>
                        <div class="trip-receipt-line"><span>Method</span><strong>${escapeHtml(payment.method || '-')}</strong></div>
                        <div class="trip-receipt-line"><span>Marked paid</span><strong>${escapeHtml(payment.marked_at_full || payment.marked_at || '-')}</strong></div>
                        <div class="trip-receipt-line"><span>Confirmed</span><strong>${escapeHtml(payment.confirmed_at || '-')}</strong></div>
                    </div>
                    <div class="trip-receipt-actions">
                        <button type="button" class="trip-payment-review-btn js-print-receipt"><i class="fa-solid fa-print"></i>Print / Save PDF</button>
                        <button type="button" class="trip-payment-review-btn confirm js-back-receipts"><i class="fa-solid fa-list"></i>Back</button>
                    </div>
                </article>
            `;
            const renderList = (payments) => {
                const rows = Array.isArray(payments) ? payments : [];
                if (!rows.length) {
                    list.innerHTML = '<div class="trip-payment-review-empty">No receipt available for this trip yet.</div>';
                    return;
                }

                list.innerHTML = rows.map((payment) => `
                    <button type="button" class="trip-payment-review-item js-receipt-row" data-payment='${escapeHtml(JSON.stringify(payment))}' style="text-align:left;cursor:pointer;">
                        <div class="trip-payment-review-top">
                            <div class="trip-payment-review-person">
                                <span class="trip-payment-review-avatar">${escapeHtml(payment.initials || 'P')}</span>
                                <span>
                                    <span class="trip-payment-review-name">${escapeHtml(payment.trip)}</span>
                                    <span class="trip-payment-review-route">${escapeHtml(payment.method || '-')} · ${escapeHtml(payment.marked_at_full || payment.marked_at || '-')}</span>
                                </span>
                            </div>
                            <span class="trip-payment-review-status">${escapeHtml(payment.status_label || payment.status)}</span>
                        </div>
                        <div class="trip-payment-review-amount">
                            <span><span>Amount</span><strong>RM ${escapeHtml(payment.amount)}</strong></span>
                            <span class="trip-payment-review-time">View receipt</span>
                        </div>
                    </button>
                `).join('');
            };
            let activeRows = [];
            const open = (payments, routeName = '') => {
                document.querySelectorAll('.trip-payment-review-modal.is-open').forEach((openModal) => {
                    if (openModal !== modal) {
                        openModal.classList.remove('is-open');
                        openModal.setAttribute('aria-hidden', 'true');
                    }
                });
                activeRows = Array.isArray(payments) ? payments : [];
                sub.textContent = routeName || 'View and save your trip payment receipts.';
                showModalSkeleton(list);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    renderList(activeRows);
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

            buttons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    open(decodePayload(button.dataset.paymentsB64 || ''), button.dataset.routeName || '');
                });
            });
            document.addEventListener('click', (event) => {
                const fromHistory = event.target.closest('.js-view-receipt');
                if (fromHistory) {
                    event.preventDefault();
                    event.stopPropagation();
                    try {
                        const payment = JSON.parse(fromHistory.dataset.payment || '{}');
                        open([payment], 'Payment receipt');
                        list.innerHTML = receiptHtml(payment);
                    } catch (_error) {}
                    return;
                }
                const row = event.target.closest('.js-receipt-row');
                if (row) {
                    try { list.innerHTML = receiptHtml(JSON.parse(row.dataset.payment || '{}')); } catch (_error) {}
                    return;
                }
                if (event.target.closest('.js-back-receipts')) {
                    renderList(activeRows);
                    return;
                }
                if (event.target.closest('.js-print-receipt')) {
                    const receipt = event.target.closest('.trip-receipt-card');
                    if (!receipt) return;
                    const printable = receipt.cloneNode(true);
                    printable.querySelectorAll('.trip-receipt-actions').forEach((node) => node.remove());
                    const iframe = document.createElement('iframe');
                    iframe.style.position = 'fixed';
                    iframe.style.right = '0';
                    iframe.style.bottom = '0';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = '0';
                    iframe.setAttribute('aria-hidden', 'true');
                    document.body.appendChild(iframe);
                    const printDoc = iframe.contentWindow?.document;
                    if (!printDoc) {
                        iframe.remove();
                        return;
                    }
                    printDoc.open();
                    printDoc.write(`
                        <!doctype html>
                        <html>
                        <head>
                            <title>CarpoolHub receipt</title>
                            <style>
                                @page{size:auto;margin:14mm}
                                *{box-sizing:border-box}
                                body{font-family:Inter,Arial,sans-serif;margin:0;padding:28px;background:#f7f2e7;color:#0f172a}
                                .trip-receipt-card{max-width:640px;margin:0 auto;background:#fff;border:1px solid #e4d8bf;border-radius:18px;padding:22px}
                                .trip-receipt-head{display:flex;justify-content:space-between;gap:16px;border-bottom:1px solid #eadfc8;padding-bottom:16px;margin-bottom:16px}
                                .trip-receipt-title{margin:0;font-size:24px;font-weight:900}
                                .trip-receipt-id,.trip-receipt-line span,.trip-receipt-total span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
                                .trip-receipt-status{border:1px solid #22c55e;border-radius:999px;padding:7px 12px;color:#047857;font-weight:800;height:max-content}
                                .trip-receipt-total{background:#faf7ef;border-radius:14px;padding:16px;margin-bottom:14px}
                                .trip-receipt-total strong{font-size:34px;font-weight:950}
                                .trip-receipt-line{display:flex;justify-content:space-between;gap:16px;border-top:1px solid #f0e5cf;padding:12px 0}
                                .trip-receipt-line strong{text-align:right}
                                .trip-receipt-actions{display:none}
                                @media print{body{background:#fff;padding:0}.trip-receipt-card{box-shadow:none}}
                            </style>
                        </head>
                        <body>${printable.outerHTML}</body>
                        </html>
                    `);
                    printDoc.close();
                    setTimeout(() => {
                        iframe.contentWindow?.focus();
                        iframe.contentWindow?.print();
                        setTimeout(() => iframe.remove(), 500);
                    }, 100);
                }
            });
            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
            });
        })();

        // The "Manage requests" popup (+ its Reject/Remove reason sub-popups
        // and route-optimization map) now lives in
        // public/js/trip-requests-modal.js — moved so the chat thread page
        // can reuse that exact same popup without pulling in everything else
        // in this file.

        // ── "My Request" — the passenger-side counterpart of "Manage requests"
        // above: one card for the viewer's own request, Cancel instead of
        // Reject/Approve/Remove/Absent. Also shows trip date/fare/seats and a
        // lightweight route-preview map (driver pickup/drop-off plus other
        // approved passengers' custom stops) — visually modelled on Manage
        // Requests' hero + map, minus the driver-only optimisation tooling
        // (stop toggles, route-fit search) since a passenger isn't managing
        // anyone else's stop here.
        (() => {
            const modal = document.getElementById('tripMyRequestModal');
            const list = document.getElementById('tripMyRequestList');
            const closeBtn = document.getElementById('tripMyRequestClose');
            const buttons = document.querySelectorAll('.open-my-request-review');
            if (!modal || !list || !closeBtn || !buttons.length) return;

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            // Pending-request card — same trigger button as above, but a request
            // still awaiting driver approval has nothing to manage, so it opens
            // this simpler, Explore-styled read-only view instead (see the
            // status branch in the click handler near the bottom of this IIFE).
            const pendingModal = document.getElementById('tripPendingRequestModal');
            const pendingCloseBtn = document.getElementById('tripPendingRequestClose');
            const pendingCancelBtn = document.getElementById('tripPendingRequestCancelBtn');
            if (pendingModal && pendingModal.parentElement !== document.body) {
                document.body.appendChild(pendingModal);
            }
            const pendingOpen = () => {
                pendingModal?.classList.add('is-open');
                pendingModal?.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };
            const pendingClose = () => {
                pendingModal?.classList.remove('is-open');
                pendingModal?.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };
            if (pendingModal) {
                // .xp-modal's mobile sheet breakpoint (explore.css) is 639px,
                // not the 767px the .trip-payment-review-modal family above uses.
                window.CarpoolBottomSheet?.enable({
                    modal: pendingModal,
                    card: pendingModal.querySelector('.xp-modal-card'),
                    head: pendingModal.querySelector('.xp-modal-head'),
                    closeFn: pendingClose,
                    breakpoint: 639,
                });
            }
            const renderPendingCard = (request) => {
                if (!pendingModal) return;
                const setText = (id, value) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = value;
                };
                const pendingAvatarEl = document.getElementById('tripPendingRequestDriverAvatar');
                if (pendingAvatarEl) {
                    const photoUrl = request.driver_photo || '';
                    pendingAvatarEl.innerHTML = window.CarpoolAvatar.innerHtml({ photoUrl: photoUrl, name: request.driver_name });
                    pendingAvatarEl.style.cssText = photoUrl ? '' : window.CarpoolAvatar.bgStyle(request.driver_id);
                }
                setText('tripPendingRequestDriver', request.driver_name || 'Driver');
                setText('tripPendingRequestTime', request.trip_datetime || '-');
                setText('tripPendingRequestSeats', request.seats_available ?? '-');
                setText('tripPendingRequestFare', request.fare_per_person ? `RM ${request.fare_per_person}` : '-');
                setText('tripPendingRequestPickup', request.pickup_name || '-');
                setText('tripPendingRequestDestination', request.destination_name || '-');
                setText('tripPendingRequestVehicle', request.vehicle_text || '-');
                if (pendingCancelBtn) pendingCancelBtn.dataset.cancelUrl = request.cancel_url || '';
            };
            if (pendingModal) {
                pendingCloseBtn?.addEventListener('click', pendingClose);
                pendingModal.addEventListener('click', (event) => {
                    if (event.target === pendingModal) pendingClose();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && pendingModal.classList.contains('is-open')) pendingClose();
                });
                pendingCancelBtn?.addEventListener('click', async () => {
                    const cancelUrl = pendingCancelBtn.dataset.cancelUrl;
                    if (!cancelUrl) return;
                    if (!window.confirm('Cancel your join request for this trip?')) return;

                    const originalHtml = pendingCancelBtn.innerHTML;
                    pendingCancelBtn.disabled = true;
                    pendingCancelBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                    try {
                        const form = new FormData();
                        form.append('_token', csrf);
                        form.append('_method', 'PATCH');
                        const response = await fetch(cancelUrl, {
                            method: 'POST',
                            body: form,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(payload.message || 'Request could not be cancelled.');
                        }
                        if (window.showToast) window.showToast(payload.message || 'Request cancelled.', 'success');
                        pendingClose();
                        window.setTimeout(() => window.location.reload(), 600);
                    } catch (error) {
                        pendingCancelBtn.disabled = false;
                        pendingCancelBtn.innerHTML = originalHtml;
                        if (window.showToast) window.showToast(error.message || 'Request could not be cancelled.', 'error');
                    }
                });
            }

            const csrf = window.CH_TRIPS.csrf;
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const decodePayload = (encoded) => {
                try {
                    const bytes = Uint8Array.from(atob(String(encoded || '')), (char) => char.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                } catch (_error) {
                    return null;
                }
            };
            const num = (value) => {
                const parsed = Number.parseFloat(String(value ?? '').trim());
                return Number.isFinite(parsed) ? parsed : null;
            };

            let myRequestMap = null;
            const drawMap = async (request) => {
                const mapEl = document.getElementById('tripMyRequestMap');
                if (!mapEl || typeof L === 'undefined') return;
                if (myRequestMap) {
                    myRequestMap.remove();
                    myRequestMap = null;
                }

                const pickup = L.latLng(num(request.driver_pickup_lat), num(request.driver_pickup_lng));
                const dropoff = L.latLng(num(request.driver_dropoff_lat), num(request.driver_dropoff_lng));
                if (!Number.isFinite(pickup.lat) || !Number.isFinite(pickup.lng) || !Number.isFinite(dropoff.lat) || !Number.isFinite(dropoff.lng)) {
                    mapEl.innerHTML = '<div class="trip-payment-review-empty">No coordinates available for route preview.</div>';
                    return;
                }

                myRequestMap = L.map(mapEl, { scrollWheelZoom: false, zoomControl: true, attributionControl: false });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(myRequestMap);

                const numberedIcon = (className, marker, fill = '') => L.divIcon({
                    className: '',
                    html: `<span class="summary-pin-icon ${className}" data-summary-marker="${marker}" style="${fill ? `--pin-fill:${fill}` : ''}">${marker}</span>`,
                    iconSize: [26, 26],
                    iconAnchor: [13, 13],
                    tooltipAnchor: [0, -14],
                });
                const addPoint = (point, className, label, marker, fill = '') => {
                    L.marker(point, { icon: numberedIcon(className, marker, fill), title: label })
                        .addTo(myRequestMap)
                        .bindTooltip(escapeHtml(label), { permanent: false, direction: 'top', offset: [0, -10] });
                };

                const stops = (Array.isArray(request.approved_stops) ? request.approved_stops : [])
                    .map((stop) => ({ point: L.latLng(num(stop.lat), num(stop.lng)), label: stop.label || 'Passenger stop' }))
                    .filter((stop) => Number.isFinite(stop.point.lat) && Number.isFinite(stop.point.lng));

                const points = [pickup, ...stops.map((stop) => stop.point), dropoff];

                // Straight reference line first (instant), swapped for the real
                // road route once OSRM responds — best-effort, no permutation
                // search needed since the passenger isn't optimising stop order.
                let referenceLine = L.polyline(points, { color: '#64748b', weight: 5, opacity: .55, lineCap: 'round', interactive: false }).addTo(myRequestMap);
                addPoint(pickup, 'driver-pickup', 'Driver Pickup', 'A');
                stops.forEach((stop, index) => addPoint(stop.point, 'approved', stop.label, String(index + 1), '#22c55e'));
                addPoint(dropoff, 'driver-dropoff', 'Driver Drop-off', 'B');

                const bounds = L.latLngBounds(points);
                if (bounds.isValid()) myRequestMap.fitBounds(bounds, { padding: [28, 28] });
                setTimeout(() => myRequestMap?.invalidateSize(), 100);

                try {
                    const coordinates = points.map((point) => `${point.lng},${point.lat}`).join(';');
                    const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`);
                    if (!response.ok) return;
                    const data = await response.json();
                    const routeCoords = (data?.routes?.[0]?.geometry?.coordinates ?? [])
                        .map((coord) => L.latLng(Number(coord[1]), Number(coord[0])))
                        .filter((coord) => Number.isFinite(coord.lat) && Number.isFinite(coord.lng));
                    if (routeCoords.length > 1 && myRequestMap) {
                        referenceLine.remove();
                        L.polyline(routeCoords, { color: '#1d4ed8', weight: 5, opacity: .92, lineCap: 'round', interactive: false }).addTo(myRequestMap);
                    }
                } catch (_error) {
                    // Reference line already drawn — nothing else to do.
                }
            };

            const open = () => {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                if (myRequestMap) {
                    myRequestMap.remove();
                    myRequestMap = null;
                }
            };
            window.CarpoolBottomSheet?.enable({
                modal: modal,
                card: modal.querySelector('.trip-payment-review-card'),
                head: modal.querySelector('.trip-payment-review-head'),
                closeFn: close,
            });

            const render = (request) => {
                if (!request) {
                    list.innerHTML = '<div class="trip-request-empty-state"><p class="trip-request-empty-title">Request not found</p></div>';
                    return;
                }

                const statusLabel = request.status === 'pending' ? 'Pending' : 'Approved';
                const approvedStopCount = Array.isArray(request.approved_stops) ? request.approved_stops.length : 0;

                list.innerHTML = `
                    <div class="trip-meta-line">
                        <span class="trip-meta-item"><i class="fa-solid fa-hashtag"></i><span>${escapeHtml(request.trip || '-')}</span></span>
                        <span class="trip-meta-dot">&middot;</span>
                        <span class="trip-meta-item"><i class="fa-regular fa-calendar"></i><span>${escapeHtml(request.trip_datetime || '-')}</span></span>
                        <span class="trip-meta-dot">&middot;</span>
                        <span class="trip-meta-item trip-meta-route"><i class="fa-solid fa-road"></i><span class="trip-meta-route-text">${escapeHtml(request.route_name || '-')}</span></span>
                    </div>
                    <div class="trip-secondary-grid">
                        <div class="trip-secondary-item">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-chair"></i>Seats Left</span>
                            <span class="trip-modal-value">${escapeHtml(request.seats_available ?? '-')}</span>
                        </div>
                        <div class="trip-secondary-item">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-users"></i>Approved</span>
                            <span class="trip-modal-value">${Number(request.approved_count) || 0}</span>
                        </div>
                        <div class="trip-secondary-item">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-money-bill-wave"></i>Trip Fare</span>
                            <span class="trip-modal-value">RM ${escapeHtml(request.fare_per_person || '0.00')}</span>
                        </div>
                    </div>
                    <section class="trip-request-summary-card">
                        <div>
                            <h3 class="trip-request-section-title">Route Preview</h3>
                            <p class="trip-request-section-sub">Driver pickup/drop-off and other approved passengers' custom stops.</p>
                        </div>
                        <span class="trip-request-count-pill">${approvedStopCount} approved stop${approvedStopCount === 1 ? '' : 's'}</span>
                        <div class="trip-request-map" id="tripMyRequestMap"></div>
                        <div class="trip-request-map-legend">
                            <span><i class="original"></i>Route preview</span>
                        </div>
                    </section>
                    <article class="trip-payment-review-item">
                        <div class="trip-payment-review-top">
                            <div class="trip-payment-review-person">
                                <span class="trip-payment-review-avatar">${escapeHtml(request.initials || 'P')}</span>
                                <span>
                                    <span class="trip-payment-review-name">${escapeHtml(request.passenger || 'You')}</span>
                                </span>
                            </div>
                            <span class="trip-payment-review-status">${escapeHtml(statusLabel)}</span>
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
                                <small>Added only to you</small>
                            </div>
                            <div class="trip-request-route-item">
                                <span>Route fit</span>
                                <strong>${escapeHtml(request.fit || 'Review')}</strong>
                                <small>${escapeHtml(request.fit_label || 'Driver review')}</small>
                            </div>
                        </div>
                        ${request.can_cancel ? `
                            <div class="trip-request-actions">
                                <button type="button" class="trip-payment-review-btn danger open-my-request-cancel" data-cancel-url="${escapeHtml(request.cancel_url)}">
                                    <i class="fa-solid fa-ban"></i> Cancel
                                </button>
                            </div>
                        ` : `
                            <div class="trip-request-note">This trip has already passed — it can no longer be cancelled.</div>
                        `}
                    </article>
                `;

                drawMap(request);
            };

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    const request = decodePayload(button.dataset.requestB64);
                    if (request && request.status === 'pending' && pendingModal) {
                        renderPendingCard(request);
                        pendingOpen();
                        return;
                    }
                    showModalSkeleton(list);
                    open();
                    setTimeout(() => render(request), 240);
                });
            });

            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
            });

            list.addEventListener('click', async (event) => {
                const cancelBtn = event.target.closest('.open-my-request-cancel');
                if (!cancelBtn) return;
                if (!window.confirm('Cancel your request for this trip? You will be removed from the trip immediately.')) return;

                const originalHtml = cancelBtn.innerHTML;
                cancelBtn.disabled = true;
                cancelBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                try {
                    const form = new FormData();
                    form.append('_token', csrf);
                    form.append('_method', 'PATCH');
                    const response = await fetch(cancelBtn.dataset.cancelUrl, {
                        method: 'POST',
                        body: form,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Request could not be cancelled.');
                    }
                    if (window.showToast) window.showToast(payload.message || 'Request cancelled.', 'success');
                    close();
                    // Seat counts, tab counters, and this trip's own card all need
                    // a fresh server render — simplest correct option here.
                    window.setTimeout(() => window.location.reload(), 600);
                } catch (error) {
                    cancelBtn.disabled = false;
                    cancelBtn.innerHTML = originalHtml;
                    if (window.showToast) window.showToast(error.message || 'Request could not be cancelled.', 'error');
                }
            });
        })();

        // ── Bulk Select & Floating Action Bar ──
        (() => {
            const initTripsBulkSelect = () => {
                const selectAllCb = document.getElementById('selectAllTrips');
                const floatingBar = document.getElementById('tripsBatchFloatingBar');
                const countSpan = document.getElementById('tripsSelectedCount');
                const cancelBtn = document.getElementById('tripsCancelBatchBtn');
                const floatingSelectAllBtn = document.getElementById('tripsSelectAllBtn');

                // The trip list renders a desktop table row and a mobile card for every
                // trip, each with its own .trip-row-checkbox; only one is visible at a
                // time via CSS. Scope to the visible set so counts/selection aren't doubled.
                const visibleRowCheckboxes = () => Array.from(document.querySelectorAll('.trip-row-checkbox'))
                    .filter((cb) => cb.offsetParent !== null);

                const updateFloatingBar = () => {
                    const totalCbs = visibleRowCheckboxes();
                    const checkedCbs = totalCbs.filter((cb) => cb.checked);
                    const count = checkedCbs.length;

                    if (countSpan) countSpan.textContent = count;

                    if (floatingBar) {
                        if (count > 0) {
                            floatingBar.style.display = 'flex';
                            floatingBar.classList.remove('closing');
                        } else if (floatingBar.style.display !== 'none' && !floatingBar.classList.contains('closing')) {
                            floatingBar.classList.add('closing');
                            setTimeout(() => {
                                if (floatingBar.classList.contains('closing')) {
                                    floatingBar.style.display = 'none';
                                    floatingBar.classList.remove('closing');
                                }
                            }, 220); // match animation duration
                        }
                    }

                    if (selectAllCb) {
                        selectAllCb.checked = totalCbs.length > 0 && count === totalCbs.length;
                        selectAllCb.indeterminate = count > 0 && count < totalCbs.length;
                    }

                    if (floatingSelectAllBtn) {
                        const allSelected = totalCbs.length > 0 && count === totalCbs.length;
                        floatingSelectAllBtn.innerHTML = allSelected
                            ? '<i class="fa-solid fa-square-minus"></i>'
                            : '<i class="fa-solid fa-check-double"></i>';
                        const label = allSelected ? 'Deselect all' : 'Select all';
                        floatingSelectAllBtn.title = label;
                        floatingSelectAllBtn.setAttribute('aria-label', label);
                    }
                };

                if (selectAllCb) {
                    selectAllCb.onclick = null;
                    selectAllCb.onchange = function() {
                        const isChecked = this.checked;
                        visibleRowCheckboxes().forEach(cb => {
                            cb.checked = isChecked;
                        });
                        updateFloatingBar();
                    };
                }

                if (floatingSelectAllBtn) {
                    floatingSelectAllBtn.onclick = function() {
                        const totalCbs = visibleRowCheckboxes();
                        const checkedCbs = totalCbs.filter((cb) => cb.checked);
                        const targetState = !(totalCbs.length > 0 && checkedCbs.length === totalCbs.length);
                        totalCbs.forEach(cb => {
                            cb.checked = targetState;
                        });
                        updateFloatingBar();
                    };
                }

                document.querySelectorAll('.trip-row-checkbox').forEach(cb => {
                    cb.onchange = updateFloatingBar;
                });

                if (cancelBtn) {
                    cancelBtn.onclick = function() {
                        visibleRowCheckboxes().forEach(cb => {
                            cb.checked = false;
                        });
                        if (selectAllCb) {
                            selectAllCb.checked = false;
                            selectAllCb.indeterminate = false;
                        }
                        updateFloatingBar();
                    };
                }

                updateFloatingBar();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTripsBulkSelect);
            } else {
                initTripsBulkSelect();
            }

            window.initTripsBulkSelect = initTripsBulkSelect;
        })();
