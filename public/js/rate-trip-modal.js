/* "Rate Your Driver" — shared across every page that can trigger it
   (trips/index.blade.php's row/Trip Details modal, chats/show.blade.php's
   in-chat Hexa CTA). Delegated on document, not bound per-button, since
   trips-index.js already established that a direct binding goes dead the
   moment a filter/search swap replaces the trigger's container — see its
   own "Manage requests" comment for why. Reads CSRF from the global
   <meta name="csrf-token"> (layouts/app.blade.php) rather than a
   page-specific window.CH_*.csrf config, since this component needs to work
   on pages that don't define one. */

(() => {
    const modal = document.getElementById('rateTripModal');
    const starsWrap = document.getElementById('rateTripStars');
    const sub = document.getElementById('rateTripSub');
    const hint = document.getElementById('rateTripHint');
    const closeBtn = document.getElementById('rateTripClose');
    const cancelBtn = document.getElementById('rateTripCancel');
    const submitBtn = document.getElementById('rateTripSubmit');
    if (!modal || !starsWrap) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const stars = Array.from(starsWrap.querySelectorAll('.rate-trip-star'));
    let activeTrigger = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[ch]));

    const fillTo = (value, className) => {
        stars.forEach((star) => {
            const starValue = Number.parseInt(star.dataset.star || '0', 10);
            star.classList.toggle(className, starValue <= value);
            const icon = star.querySelector('i');
            if (icon && className === 'is-selected') {
                icon.className = starValue <= value ? 'fa-solid fa-star' : 'fa-regular fa-star';
            }
        });
    };

    const resetStars = () => {
        starsWrap.dataset.value = '0';
        fillTo(0, 'is-selected');
        fillTo(0, 'is-hover');
        if (hint) hint.textContent = 'Tap a star to rate';
        if (submitBtn) submitBtn.disabled = true;
    };

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        activeTrigger = null;
    };

    const open = (trigger) => {
        // Re-parent to the very end of <body> on every open, not just once
        // at load time — Trip Details (trip-details-modal.js) does the same
        // "move to end of body" trick on ITS OWN modal, and does it every
        // time this script's own one-time reparent would otherwise be
        // undone by whichever modal was opened more recently. Both modals
        // share the same mobile z-index (100000, trips.css), so DOM order
        // is what actually decides which one paints on top — without this,
        // Trip Details staying open underneath silently covered this
        // modal's full-width mobile sheet, which read as "not full width"
        // even though the card itself really was.
        if (modal.parentElement !== document.body || modal !== document.body.lastElementChild) {
            document.body.appendChild(modal);
        }

        activeTrigger = trigger;
        resetStars();
        if (sub) {
            const tripRef = trigger.dataset.tripRef || '';
            const driverName = trigger.dataset.driverName || 'your driver';
            const routeName = trigger.dataset.routeName || '';
            const headline = [tripRef, driverName].filter(Boolean).join(' · ');
            sub.innerHTML = escapeHtml(headline) + (routeName ? `<br>${escapeHtml(routeName)}` : '');
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    window.CarpoolBottomSheet?.enable({
        modal,
        card: modal.querySelector('.trip-payment-review-card'),
        head: modal.querySelector('.trip-payment-review-head'),
        closeFn: close,
    });
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
    });

    stars.forEach((star) => {
        const value = Number.parseInt(star.dataset.star || '0', 10);
        star.addEventListener('mouseenter', () => fillTo(value, 'is-hover'));
        star.addEventListener('mouseleave', () => fillTo(0, 'is-hover'));
        star.addEventListener('click', () => {
            starsWrap.dataset.value = String(value);
            fillTo(value, 'is-selected');
            if (hint) hint.textContent = `${value} star${value > 1 ? 's' : ''}`;
            if (submitBtn) submitBtn.disabled = false;
        });
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element ? event.target.closest('.open-rate-trip-modal-btn') : null;
        if (!(trigger instanceof HTMLElement)) return;
        if (trigger.classList.contains('is-disabled')) return;

        event.preventDefault();
        open(trigger);
    });

    // Programmatic entry point — for pages that want the modal to open on
    // load (e.g. chats/show.blade.php's ?open_rate=1 deep link) without
    // needing a real .open-rate-trip-modal-btn element already on the page
    // (the in-chat Hexa CTA bubble only exists once the reminder command has
    // posted it — a passenger arriving via the trips-list Rate icon may get
    // there before that has happened). Builds a throwaway trigger element
    // carrying the same data-* attributes open() already reads, so it can
    // reuse all of open()'s logic and still work as ratedTrigger afterwards
    // (mirrors circle-chooser-modal.js's window.CarpoolCircleChooser.startFor
    // pattern).
    window.CarpoolRateTrip = {
        openFor(config) {
            const trigger = document.createElement('a');
            trigger.href = '#';
            trigger.className = 'open-rate-trip-modal-btn';
            if (config.tripId) trigger.dataset.tripId = config.tripId;
            if (config.tripRef) trigger.dataset.tripRef = config.tripRef;
            if (config.rateUrl) trigger.dataset.rateUrl = config.rateUrl;
            if (config.driverName) trigger.dataset.driverName = config.driverName;
            if (config.routeName) trigger.dataset.routeName = config.routeName;
            open(trigger);
        },
    };

    submitBtn?.addEventListener('click', async () => {
        const value = Number.parseInt(starsWrap.dataset.value || '0', 10);
        const rateUrl = activeTrigger?.dataset.rateUrl;
        if (!value || !rateUrl) return;

        submitBtn.disabled = true;
        try {
            const response = await fetch(rateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ stars: value }),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Could not submit your rating.');
            }

            // Captured before close() — close() nulls out activeTrigger, so
            // reading it after would silently make this whole block a no-op.
            const ratedTrigger = activeTrigger;

            close();
            window.showToast?.(payload.message || 'Thanks for rating!', 'success');

            if (ratedTrigger) {
                ratedTrigger.classList.add('is-disabled');
                ratedTrigger.innerHTML = '<i class="fa-solid fa-check"></i> Rated';

                // ratedTrigger (e.g. #tripModalRateBtn) is a different
                // element from the row's own .open-trip-modal-btn, which is
                // what trip-details-modal.js actually re-reads data-can-rate
                // from on every render — without updating that too, closing
                // and reopening Trip Details for the same trip within this
                // same page load would reset the button back to "Rate Trip"
                // from the row's now-stale dataset (a real re-submit would
                // still be rejected server-side, but the button shouldn't
                // invite it). A full page reload fixes this on its own too,
                // since $canRateThisTrip is recomputed server-side by then.
                const tripId = ratedTrigger.dataset.tripId;
                if (tripId) {
                    document.querySelectorAll(`.open-trip-modal-btn[data-trip-id="${CSS.escape(tripId)}"]`)
                        .forEach((row) => { row.dataset.canRate = '0'; });
                }
            }
        } catch (error) {
            window.showToast?.(error.message || 'Could not submit your rating.', 'error');
            submitBtn.disabled = false;
        }
    });
})();
