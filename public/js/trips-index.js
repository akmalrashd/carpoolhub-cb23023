/* Extracted from resources/views/trips/index.blade.php — logic; page values come from window.CH_TRIPS. */
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
            const detailButtons = Array.from(document.querySelectorAll('.open-trip-modal-btn'))
                .filter((el) => String(el.dataset.tripId || '').trim() === focusTrip);
            const detailBtn = detailButtons.find((el) => el instanceof HTMLElement && el.offsetParent !== null) || detailButtons[0];
            if (detailBtn instanceof HTMLElement) {
                window.setTimeout(() => detailBtn.click(), 300);
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
                const nowText = new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
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
                const nowText = new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
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

        (() => {
            const modal = document.getElementById('tripRequestsReviewModal');
            const list = document.getElementById('tripRequestsReviewList');
            const closeBtn = document.getElementById('tripRequestsReviewClose');
            const buttons = document.querySelectorAll('.open-trip-requests-review');
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
            const renderPendingCard = (request) => {
                if (!pendingModal) return;
                const setText = (id, value) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = value;
                };
                setText('tripPendingRequestDriverAvatar', request.driver_initial || 'DR');
                setText('tripPendingRequestDriver', request.driver_name || 'Driver');
                setText('tripPendingRequestRating', request.driver_rating || '5.00');
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

        (() => {
            const modal      = document.getElementById('tripDetailsModal');
            const closeBtn   = document.getElementById('tripDetailsCloseBtn');
            const detailButtons = document.querySelectorAll('.open-trip-modal-btn');
            if (!modal || !closeBtn) return;
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const tripIdsEl          = document.getElementById('tripModalTripIds');
            const modeEl             = document.getElementById('tripModalMode');
            const pairHintEl         = document.getElementById('tripModalPairHint');
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
                    if (driverAvatarEl) driverAvatarEl.textContent  = ((btn.dataset.driverName || 'D').trim().charAt(0) || 'D').toUpperCase();
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

                    if (pointALabelEl) pointALabelEl.innerHTML      = '<i class="fa-solid fa-location-dot"></i>Pickup Point';
                    if (pointBLabelEl) pointBLabelEl.innerHTML      = '<i class="fa-solid fa-flag-checkered"></i>Destination Point';
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

                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');

                    const pickupLat      = toNum(btn.dataset.pickupLat);
                    const pickupLng      = toNum(btn.dataset.pickupLng);
                    const destinationLat = toNum(btn.dataset.destinationLat);
                    const destinationLng = toNum(btn.dataset.destinationLng);

                    setTimeout(() => {
                        drawMap(pickupLat, pickupLng, destinationLat, destinationLng, routePointsPayload).then(() => {
                            if (miniMap) miniMap.invalidateSize();
                        });
                    }, 40);
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
