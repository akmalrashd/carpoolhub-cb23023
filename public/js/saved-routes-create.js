/* Extracted from resources/views/saved-routes/create.blade.php — cacheable. */
// ── AI Chat: pre-fill saved route form from sessionStorage draft ──────
(function () {
    const raw = sessionStorage.getItem('ch_ai_route_draft');
    if (!raw) return;

    let draft;
    try { draft = JSON.parse(raw); } catch { return; }
    sessionStorage.removeItem('ch_ai_route_draft');
    if (!draft || typeof draft !== 'object') return;

    // Banner
    const banner = document.createElement('div');
    banner.style.cssText = 'margin:0 28px 12px;padding:10px 14px;border-radius:10px;background:#dbeafe;border:1px solid rgba(37,99,235,.22);color:#1e3a8a;font-size:12px;font-weight:700;display:flex;align-items:center;gap:8px;';
    banner.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Route pre-filled by AI — coordinates are approximate, please verify on the map.';
    const form = document.getElementById('route-form');
    if (form) form.insertAdjacentElement('afterbegin', banner);

    function fill(id, val) {
        if (!val && val !== 0) return;
        const el = document.getElementById(id);
        if (el) { el.value = val; el.dispatchEvent(new Event('input', { bubbles: true })); }
    }

    fill('route_name',   draft.route_name);
    fill('default_fare', draft.default_fare);

    // Point A/B names
    fill('point_a_name', draft.point_a_name);
    fill('point_b_name', draft.point_b_name);

    // Coordinates + map markers (via bridge exposed by _form.blade.php)
    setTimeout(function () {
        if (draft.point_a_lat && draft.point_a_lng && window.__chRouteSetPoint) {
            window.__chRouteSetPoint('pickup', draft.point_a_lat, draft.point_a_lng, draft.point_a_name || '');
        }
        if (draft.point_b_lat && draft.point_b_lng && window.__chRouteSetPoint) {
            window.__chRouteSetPoint('destination', draft.point_b_lat, draft.point_b_lng, draft.point_b_name || '');
        }
    }, 120);
})();
