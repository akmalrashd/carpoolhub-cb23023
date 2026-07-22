@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<style>
    .sr-form-actions {
        display: flex;
        gap: 8px;
    }
    .sr-mobile-form-actions {
        display: none;
    }

    @media (max-width: 1023px) {
        .sr-desktop-form-actions {
            display: none;
        }

        .sr-mobile-form-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 0 28px 28px;
        }

        .sr-form-actions .btn {
            width: 100%;
            min-height: 48px;
            justify-content: center;
        }
    }
</style>

<div style="padding:20px var(--page-gutter, 28px) 0">
    <div style="font-size:11px;font-weight:800;color:var(--muted);letter-spacing:.06em;text-transform:uppercase">Saved Routes</div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-top:6px;flex-wrap:wrap">
        <div>
            <h1 style="margin:0;font-family:var(--font-display);font-size:28px;font-weight:800">Create Saved Route</h1>
            <p style="margin:4px 0 0;color:var(--muted);font-size:13px">Set pickup, destination, and custom stops on the map.</p>
        </div>
        <div class="sr-form-actions sr-desktop-form-actions">
            <a href="{{ route('saved-routes.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" form="route-form" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<form id="route-form" method="POST" action="{{ route('saved-routes.store') }}">
    @csrf
    @include('saved-routes._form')
    <div class="sr-form-actions sr-mobile-form-actions">
        <a href="{{ route('saved-routes.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</form>
<script>
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
</script>
@endsection
