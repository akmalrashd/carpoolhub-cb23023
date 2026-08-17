@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
{{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
<link rel="stylesheet" href="{{ asset('css/saved-routes.css') }}?v={{ filemtime(public_path('css/saved-routes.css')) }}">

{{-- Page header --}}
<div style="padding:20px var(--page-gutter, 28px) 0">
    <div style="font-size:11px;font-weight:800;color:var(--muted);letter-spacing:.06em;text-transform:uppercase">Saved Routes</div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-top:6px;flex-wrap:wrap">
        <div>
            <h1 style="margin:0;font-family:var(--font-display);font-size:28px;font-weight:800">My Routes</h1>
            <p style="margin:4px 0 0;color:var(--muted);font-size:13px">Reusable templates for daily commutes.</p>
        </div>
        <div>
            <a href="{{ route('saved-routes.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Create Saved Route
            </a>
        </div>
    </div>
</div>

{{-- Card grid area --}}
<div style="padding:20px var(--page-gutter, 28px) 28px">

    @if(!($initialLoad ?? false) && $savedRoutes->isEmpty())
        <div class="sr-empty">
            <i class="fa-solid fa-route sr-empty-icon"></i>
            <p class="sr-empty-title">No saved routes yet</p>
            <p class="sr-empty-copy">Save a route to reuse it across multiple trips.</p>
            <a href="{{ route('saved-routes.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Create your first route
            </a>
        </div>
    @else
        @if(!$savedRoutes->isEmpty())
            <div class="sr-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="srSearchInput" type="search" placeholder="Search route name..." autocomplete="off">
            </div>
        @endif

        <div style="position: relative; min-height: 250px;">
            {{-- Skeleton Loading Container --}}
            <div class="sr-skel-container" id="sr-skel-container" style="position:absolute; inset:0; z-index:10; background:var(--canvas, #f8fafc); width:100%; height:100%;">
                <div class="sr-grid">
                    @for($i = 0; $i < min(2, $savedRoutes->count()); $i++)
                        <div class="card" style="padding:0; overflow:hidden;">
                            {{-- Map Thumbnail Skeleton --}}
                            <div class="sr-thumb-skel sk" style="height:150px; position:relative; overflow:hidden;"></div>
                            <div class="sr-body" style="padding:16px; display:flex; flex-direction:column; gap:12px;">
                                <div style="display:flex; gap:6px;">
                                    <span class="sk" style="height:18px; width:64px; border-radius:99px;"></span>
                                </div>
                                <div style="height:22px; width:75%; border-radius:6px;" class="sk"></div>
                                <div class="sr-kv-grid" style="margin-top:4px;">
                                    @for($j = 0; $j < 4; $j++)
                                        <div class="sr-kv">
                                            <div style="height:10px; width:36px; border-radius:3px;" class="sk"></div>
                                            <div style="height:14px; width:48px; border-radius:4px; margin-top:4px;" class="sk"></div>
                                        </div>
                                    @endfor
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-top:12px; border-top:1px solid var(--hairline); padding-top:12px;">
                                    <div style="height:14px; width:100px; border-radius:4px;" class="sk"></div>
                                    <div style="height:28px; width:120px; border-radius:6px;" class="sk"></div>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <div class="sr-grid" id="srGrid" style="opacity:0; transition:opacity .35s ease;" data-initial-load="{{ ($initialLoad ?? false) ? 'true' : 'false' }}">
                @foreach($savedRoutes as $savedRoute)
                @php
                    $customPreviewPoints = $savedRoute->passengerStops
                        ->where('is_active', true)
                        ->flatMap(function ($stop) use ($savedRoute) {
                            $points = [];

                            $hasCustomPickup = $stop->pickup_latitude
                                && $stop->pickup_longitude
                                && (
                                    (string) $stop->pickup_latitude !== (string) $savedRoute->point_a_latitude
                                    || (string) $stop->pickup_longitude !== (string) $savedRoute->point_a_longitude
                                );

                            $hasCustomDropoff = $stop->dropoff_latitude
                                && $stop->dropoff_longitude
                                && (
                                    (string) $stop->dropoff_latitude !== (string) $savedRoute->point_b_latitude
                                    || (string) $stop->dropoff_longitude !== (string) $savedRoute->point_b_longitude
                                );

                            if ($hasCustomPickup) {
                                $points[] = [
                                    'type' => 'pickup',
                                    'lat' => $stop->pickup_latitude,
                                    'lng' => $stop->pickup_longitude,
                                ];
                            }

                            if ($hasCustomDropoff) {
                                $points[] = [
                                    'type' => 'dropoff',
                                    'lat' => $stop->dropoff_latitude,
                                    'lng' => $stop->dropoff_longitude,
                                ];
                            }

                            return $points;
                        })
                        ->values();
                @endphp
                <article class="card" style="padding:0;overflow:hidden;transition:transform .18s,box-shadow .18s,border-color .18s"
                         data-route-name="{{ strtolower($savedRoute->route_name ?? 'untitled route') }}"
                         data-point-a-lat="{{ $savedRoute->point_a_latitude }}"
                         data-point-a-lng="{{ $savedRoute->point_a_longitude }}"
                         data-point-b-lat="{{ $savedRoute->point_b_latitude }}"
                         data-point-b-lng="{{ $savedRoute->point_b_longitude }}"
                         data-custom-points='@json($customPreviewPoints)'>

                    {{-- Map thumbnail placeholder --}}
                    <div class="sr-thumb" aria-label="Route preview">
                        <div class="sr-thumb-map" data-route-map></div>
                        <svg class="sr-thumb-road" viewBox="0 0 360 150" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M0 98 C68 96 98 92 138 98 C184 105 202 88 236 76 C284 60 304 58 360 26" stroke="rgba(255,255,255,.96)" stroke-width="18" fill="none" />
                            <path d="M0 58 C72 62 104 55 145 54 C188 52 214 62 254 62 C306 62 322 50 360 42" stroke="rgba(255,255,255,.86)" stroke-width="10" fill="none" />
                            <path class="sr-thumb-route-shadow" d="M32 114 C80 112 110 104 138 91 C170 76 190 83 216 72 C250 58 274 52 330 36" />
                            <path class="sr-thumb-route" d="M32 114 C80 112 110 104 138 91 C170 76 190 83 216 72 C250 58 274 52 330 36" />
                        </svg>
                        <span class="sr-thumb-pin pickup"></span>
                        <span class="sr-thumb-pin destination"></span>
                        <span class="sr-thumb-name">{{ $savedRoute->route_name ?? 'Untitled Route' }}</span>
                    </div>

                    {{-- Card body --}}
                    <div class="sr-body">

                        {{-- Status + default badge --}}
                        <div class="sr-badges">
                            @if($savedRoute->is_active ?? true)
                                <span class="sr-badge-active"><i class="fa-solid fa-circle" style="font-size:7px"></i>Active</span>
                            @else
                                <span class="sr-badge-inactive"><i class="fa-solid fa-circle" style="font-size:7px"></i>Inactive</span>
                            @endif
                            @if($savedRoute->is_default ?? false)
                                <span class="sr-badge-default"><i class="fa-solid fa-star" style="font-size:9px"></i>Default</span>
                            @endif
                        </div>

                        {{-- Title + edit button --}}
                        <div class="sr-title-row">
                            <h2 class="sr-route-title">{{ $savedRoute->route_name ?? 'Untitled Route' }}</h2>
                            <a href="{{ route('saved-routes.edit', $savedRoute) }}" class="sr-edit-btn">
                                <i class="fa-solid fa-pen-to-square"></i>
                                Edit
                            </a>
                        </div>

                        {{-- 4-col KV grid --}}
                        <div class="sr-kv-grid">
                            <div class="sr-kv">
                                <span class="sr-kv-label">Stops</span>
                                <span class="sr-kv-value">{{ 2 + ($savedRoute->passengerStops->where('is_active', true)->count() * 2) }}</span>
                            </div>
                            <div class="sr-kv">
                                <span class="sr-kv-label">Distance</span>
                                <span class="sr-kv-value" data-route-distance>—</span>
                            </div>
                            <div class="sr-kv">
                                <span class="sr-kv-label">Time</span>
                                <span class="sr-kv-value" data-route-time>—</span>
                            </div>
                            <div class="sr-kv">
                                <span class="sr-kv-label">Trip fee</span>
                                <span class="sr-kv-value">RM&nbsp;{{ number_format((float)($savedRoute->default_fare ?? 0), 2) }}</span>
                            </div>
                        </div>

                        {{-- Footer row --}}
                        <div class="sr-card-footer">
                            <span class="sr-footer-meta">
                                <i class="fa-solid fa-bookmark" style="font-size:10px"></i>
                                Saved {{ $savedRoute->created_at ? $savedRoute->created_at->diffForHumans() : '—' }}
                            </span>
                            <div class="sr-footer-actions">
                                <a href="{{ route('trips.create', ['route_id' => $savedRoute->id]) }}" class="sr-use-btn">
                                    <i class="fa-solid fa-arrow-right"></i>
                                    Use in new trip
                                </a>
                                <form
                                    method="POST"
                                    action="{{ route('saved-routes.destroy', $savedRoute) }}"
                                    onsubmit="return confirm('Delete this route? All trips using this route may also be affected.');"
                                    style="display:contents"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sr-delete-btn" title="Delete route">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>{{-- /.sr-body --}}
                </article>
            @endforeach
        </div>
        </div>

        <div id="srSearchEmpty" class="sr-empty" style="margin-top:12px">
            <i class="fa-solid fa-magnifying-glass sr-empty-icon"></i>
            <p class="sr-empty-title">No routes found</p>
            <p class="sr-empty-copy">Try a different route name.</p>
        </div>

        @if(method_exists($savedRoutes, 'links'))
        <div style="margin-top:14px">
            {{ $savedRoutes->links() }}
        </div>
        @endif
    @endif

</div>

<script src="{{ asset('js/saved-routes.js') }}?v={{ filemtime(public_path('js/saved-routes.js')) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const grid = document.getElementById('srGrid');
        if (grid && grid.dataset.initialLoad === 'true') {
            const skel = document.getElementById('sr-skel-container');
            if (skel) skel.style.display = 'block';
            grid.style.opacity = '0';
            grid.dataset.initialLoad = 'false';

            fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const currentMain = document.querySelector('main') || document.body;
                    const newMain = doc.querySelector('main') || doc.body;
                    
                    // Replace grid
                    const newGrid = doc.getElementById('srGrid');
                    if (newGrid && grid) {
                        grid.innerHTML = newGrid.innerHTML;
                        grid.style.opacity = '1';
                    }
                    if (skel) skel.style.display = 'none';

                    // Re-run JS
                    const oldScript = document.querySelector('script[src*="saved-routes.js"]');
                    if (oldScript) {
                        const newScript = document.createElement('script');
                        newScript.src = oldScript.src;
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    }
                });
        }
    });
</script>
@endsection
