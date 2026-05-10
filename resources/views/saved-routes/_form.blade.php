@csrf
<div class="route-form-grid">
    <div class="field-block">
        <label for="route_name">Route Name</label>
        <input id="route_name" type="text" name="route_name" value="{{ old('route_name', $savedRoute->route_name ?? '') }}" placeholder="e.g. Home to Office">
    </div>
    <div class="field-block">
        <label for="default_fare">Default Fare (RM)</label>
        <input id="default_fare" type="number" step="0.01" min="0" name="default_fare" value="{{ old('default_fare', $savedRoute->default_fare ?? '0.00') }}" required>
    </div>

    <div class="field-block field-block-full route-preference-card">
        <div class="route-section-head">
            <span class="route-section-title"><i class="fa-solid fa-map-location-dot"></i>Pickup and drop-off</span>
            <p class="route-section-hint">Use the map above to pin Point A and Point B, then review or refine the route details below.</p>
        </div>

        <div class="route-point-grid">
            <div class="route-point-group pickup">
                <div class="route-point-group-head">
                    <span class="route-point-group-title"><i class="fa-solid fa-location-dot"></i>Pickup Point A</span>
                    <span class="route-point-group-badge">Start</span>
                </div>

                <div class="field-block">
                    <label for="point_a_name">Point A Name</label>
                    <input id="point_a_name" type="text" name="point_a_name" value="{{ old('point_a_name', $savedRoute->point_a_name ?? '') }}" placeholder="e.g. UMPSA Main Gate" required>
                </div>

                <div class="coords-head">Koordinat Point A</div>
                <div class="coords-grid">
                    <div>
                        <label for="point_a_latitude">Latitude</label>
                        <input id="point_a_latitude" type="number" step="0.0000001" name="point_a_latitude" value="{{ old('point_a_latitude', $savedRoute->point_a_latitude ?? '') }}" required>
                    </div>
                    <div>
                        <label for="point_a_longitude">Longitude</label>
                        <input id="point_a_longitude" type="number" step="0.0000001" name="point_a_longitude" value="{{ old('point_a_longitude', $savedRoute->point_a_longitude ?? '') }}" required>
                    </div>
                </div>
            </div>

            <div class="route-point-group destination">
                <div class="route-point-group-head">
                    <span class="route-point-group-title"><i class="fa-solid fa-flag-checkered"></i>Drop-off Point B</span>
                    <span class="route-point-group-badge">Akhir</span>
                </div>

                <div class="field-block">
                    <label for="point_b_name">Point B Name</label>
                    <input id="point_b_name" type="text" name="point_b_name" value="{{ old('point_b_name', $savedRoute->point_b_name ?? '') }}" placeholder="e.g. Library" required>
                </div>

                <div class="coords-head">Koordinat Point B</div>
                <div class="coords-grid">
                    <div>
                        <label for="point_b_latitude">Latitude</label>
                        <input id="point_b_latitude" type="number" step="0.0000001" name="point_b_latitude" value="{{ old('point_b_latitude', $savedRoute->point_b_latitude ?? '') }}" required>
                    </div>
                    <div>
                        <label for="point_b_longitude">Longitude</label>
                        <input id="point_b_longitude" type="number" step="0.0000001" name="point_b_longitude" value="{{ old('point_b_longitude', $savedRoute->point_b_longitude ?? '') }}" required>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<label class="active-toggle">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $savedRoute->is_active ?? true) ? 'checked' : '' }}>
    Active route
</label>

@if($errors->any())
    <div class="form-error">
        {{ $errors->first() }}
    </div>
@endif

<div class="form-actions">
    <a href="{{ route('saved-routes.index') }}" class="btn-secondary">Cancel</a>
    <button type="submit" class="btn-primary">{{ $submitLabel }}</button>
</div>
