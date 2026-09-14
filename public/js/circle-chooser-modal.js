/* Reusable "Start Group Chat" chooser — exposes window.CarpoolCircleChooser.
   startFor(config), where config = { csrf, circleOptionsUrl, createUrl,
   linkCircleUrlTemplate } for one specific trip. Any page that wants to let
   a driver start/reuse a circle for a trip calls this (trips/show.blade.php,
   the shared Trip Details modal, the Payments page's own copy) — the modal
   markup/wiring below is a single shared instance, not per-caller.

   Always opens the modal now (even with zero circles, straight into the
   name-entry view) rather than falling back to the browser's native
   prompt()/alert() — those looked jarringly out of place next to the rest
   of the app's themed UI. Reuses the same modal shell/classes as
   trip-requests-modal.blade.php (trips.css) rather than introducing a
   parallel set of styles. */

(() => {
    const modal = document.getElementById('circleChooserModal');
    const list = document.getElementById('circleChooserList');
    const subEl = document.getElementById('circleChooserSub');
    const closeBtn = document.getElementById('circleChooserClose');

    // Set on every startFor() call — the list-click delegate below reads
    // from this rather than a fixed config, since the same modal instance
    // now serves whichever trip's button was last clicked.
    let activeConfig = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[ch]));

    const submitForm = (url, extraFields = {}) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        let inner = `<input type="hidden" name="_token" value="${escapeHtml(activeConfig.csrf)}">`;
        for (const [key, value] of Object.entries(extraFields)) {
            inner += `<input type="hidden" name="${escapeHtml(key)}" value="${escapeHtml(value)}">`;
        }
        form.innerHTML = inner;
        document.body.appendChild(form);
        form.submit();
    };

    const close = () => {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    const renderCreateForm = () => {
        if (!list) return;
        if (subEl) subEl.textContent = 'Give it a name so you recognise it next time, or leave it blank.';

        list.innerHTML = `
            <div class="trip-payment-review-item">
                <label class="trip-modal-label" for="circleNameInput" style="display:block; margin-bottom:8px;">Circle name (optional)</label>
                <input
                    type="text"
                    id="circleNameInput"
                    class="trip-request-tool"
                    style="width:100%; box-sizing:border-box;"
                    placeholder="e.g. Family Trip"
                    maxlength="255"
                >
                <div style="display:flex; gap:8px; margin-top:14px; justify-content:flex-end;">
                    <button type="button" class="btn btn-ghost btn-sm" id="circleCreateCancelBtn">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="circleCreateConfirmBtn">Create Circle</button>
                </div>
            </div>
        `;

        const input = document.getElementById('circleNameInput');
        setTimeout(() => {
            try {
                input?.focus({ preventScroll: true });
            } catch (_error) {
                input?.focus();
            }
        }, 30);

        const confirmCreate = () => {
            const name = (input?.value || '').trim();
            submitForm(activeConfig.createUrl, name ? { name } : {});
        };
        input?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                confirmCreate();
            }
        });
        document.getElementById('circleCreateConfirmBtn')?.addEventListener('click', confirmCreate);
        document.getElementById('circleCreateCancelBtn')?.addEventListener('click', close);
    };

    const renderChooser = (payload) => {
        if (!list) return;
        if (subEl) subEl.textContent = "Reuse a circle you've chatted with before, or start a new one.";

        list.style.gap = '0';

        // "Start a new circle" leads — a driver picking between several
        // circles still has "start fresh" as a real option, not just a
        // fallback buried under the list.
        const newCircleRow = payload.at_cap
            ? `<div style="padding:10px 2px; ${payload.circles.length > 0 ? 'border-bottom:1px solid var(--hairline);' : ''} font-size:13px; color:var(--muted); font-weight:600;">
                   You've reached your limit of ${payload.cap_limit} circles. Retire one from its chat page before starting another.
               </div>`
            : `<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 2px; ${payload.circles.length > 0 ? 'border-bottom:1px solid var(--hairline);' : ''}">
                   <div style="font-weight:800; color:var(--ink);">Start a new circle</div>
                   <button type="button" class="btn btn-primary btn-sm" id="circleStartNewBtn" style="min-width:118px;">New Circle</button>
               </div>`;

        const circleRows = payload.circles.map((circle, i) => `
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 2px; ${i < payload.circles.length - 1 ? 'border-bottom:1px solid var(--hairline);' : ''}">
                <div style="min-width:0;">
                    <div style="font-weight:800; color:var(--ink);">${escapeHtml(circle.name)}</div>
                    <div style="font-size:12px; color:var(--muted); font-weight:600; margin-top:2px;">
                        ${circle.member_count} ${circle.member_count === 1 ? 'member' : 'members'} · ${escapeHtml(circle.linked_trip_label)}
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm circle-use-btn" data-circle-id="${escapeHtml(circle.id)}" style="min-width:118px; flex-shrink:0;">Use this circle</button>
            </div>
        `).join('');

        list.innerHTML = newCircleRow + circleRows;
    };

    const open = () => {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    if (modal) {
        window.CarpoolBottomSheet?.enable({
            modal,
            card: modal.querySelector('.trip-payment-review-card'),
            head: modal.querySelector('.trip-payment-review-head'),
            closeFn: close,
        });
        closeBtn?.addEventListener('click', close);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) close();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
        });
        list?.addEventListener('click', (event) => {
            const useBtn = event.target.closest('.circle-use-btn');
            if (useBtn) {
                submitForm(activeConfig.linkCircleUrlTemplate.replace('__ID__', useBtn.dataset.circleId));
                return;
            }
            if (event.target.closest('#circleStartNewBtn')) {
                renderCreateForm();
            }
        });
    }

    window.CarpoolCircleChooser = {
        async startFor(config) {
            activeConfig = config;

            let payload;
            try {
                const response = await fetch(config.circleOptionsUrl, { headers: { Accept: 'application/json' } });
                payload = await response.json();
            } catch {
                if (list) list.innerHTML = '<div class="trip-payment-review-item" style="color:var(--danger-ink,#dc2626);">Could not load your circles. Please try again.</div>';
                open();
                return;
            }

            if (!payload.circles || payload.circles.length === 0) {
                renderCreateForm();
            } else {
                renderChooser(payload);
            }
            open();
        },
    };
})();
