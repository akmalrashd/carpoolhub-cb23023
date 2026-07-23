/* Extracted from resources/views/trips/create.blade.php — cacheable. */
// ── AI Chat: pre-fill form from sessionStorage draft ──────────────────
(function () {
    const raw = sessionStorage.getItem('ch_ai_trip_draft');
    if (!raw) return;

    let draft;
    try { draft = JSON.parse(raw); } catch { return; }
    sessionStorage.removeItem('ch_ai_trip_draft');
    if (!draft || typeof draft !== 'object') return;

    // Show banner
    const banner = document.createElement('div');
    banner.style.cssText = 'margin:0 28px 12px;padding:10px 14px;border-radius:10px;background:var(--ch-yellow-tint);border:1px solid var(--ch-yellow-line);color:var(--ch-yellow-ink);font-size:12px;font-weight:700;display:flex;align-items:center;gap:8px;';
    banner.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Form pre-filled by AI — please review before publishing.';
    const form = document.getElementById('tripCreateForm');
    if (form) form.insertAdjacentElement('afterbegin', banner);

    function fillNonRouteFields() {
        // Direction keys (hidden inputs)
        if (draft.outbound_pickup_key) {
            const el = document.getElementById('outbound_pickup_key');
            if (el) el.value = draft.outbound_pickup_key;
        }
        if (draft.outbound_destination_key) {
            const el = document.getElementById('outbound_destination_key');
            if (el) el.value = draft.outbound_destination_key;
        }

        // Trip datetime
        if (draft.trip_datetime) {
            const dt = document.getElementById('trip_datetime');
            if (dt) {
                dt.value = draft.trip_datetime.replace(' ', 'T').substring(0, 16);
                dt.dispatchEvent(new Event('input',  { bubbles: true }));
                dt.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Trip type radio
        if (draft.trip_type) {
            const r = document.querySelector(`input[name="trip_type"][value="${draft.trip_type}"]`);
            if (r) { r.checked = true; r.dispatchEvent(new Event('change', { bubbles: true })); }
        }

        // Visibility radio
        if (draft.visibility) {
            const r = document.querySelector(`input[name="visibility"][value="${draft.visibility}"]`);
            if (r) { r.checked = true; r.dispatchEvent(new Event('change', { bubbles: true })); }
        }

        // Seat limit
        if (draft.seat_limit) {
            const sl = document.getElementById('seat_limit');
            if (sl) { sl.value = String(draft.seat_limit); sl.dispatchEvent(new Event('input', { bubbles: true })); }
        }

        // Note
        if (draft.note) {
            const note = document.getElementById('note');
            if (note) { note.value = draft.note; }
        }

        // Participants — check matching checkboxes
        if (Array.isArray(draft.participant_ids) && draft.participant_ids.length) {
            draft.participant_ids.forEach(id => {
                const cb = document.querySelector(`input[name="participant_ids[]"][value="${id}"]`);
                if (cb) { cb.checked = true; cb.dispatchEvent(new Event('change', { bubbles: true })); }
            });
        }
    }

    function trySelectRoute(routeId) {
        const trigger = document.getElementById('savedRouteTrigger');
        if (!trigger) { fillNonRouteFields(); return; }

        // Open the custom route picker — this renders the options list
        trigger.click();

        setTimeout(() => {
            // Find the rendered option button matching our route ID
            const btn = document.querySelector(`.route-picker-option[data-value="${routeId}"]`);
            if (btn) {
                btn.click(); // simulates real user selection — updates all UI
            } else {
                // Option not found — fall back to native select
                const sel = document.getElementById('saved_route_id');
                if (sel) {
                    sel.value = String(routeId);
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                }
                // Close picker if still open
                document.getElementById('savedRouteTrigger')?.click();
            }

            // Fill other fields after route is selected
            setTimeout(fillNonRouteFields, 80);
        }, 60);
    }

    // Resolve route ID — use AI suggestion or fall back to first available route
    const routeId = draft.saved_route_id
        ?? (() => {
            const sel = document.getElementById('saved_route_id');
            const first = sel?.querySelector('option[value]:not([value=""])');
            return first ? first.value : null;
        })();

    if (routeId) {
        trySelectRoute(routeId);
    } else {
        fillNonRouteFields();
    }
})();
