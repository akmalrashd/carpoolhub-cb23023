@csrf

<style>
    .trip-form-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .trip-field {
        display: grid;
        gap: 6px;
    }

    .trip-field-full {
        grid-column: 1 / -1;
    }

    .trip-label {
        font-size: 12px;
        color: #475569;
        font-weight: 600;
    }

    .trip-input,
    .trip-select,
    .trip-textarea {
        width: 100%;
        border-radius: 11px;
        border: 1px solid #dbe2ea;
        background: #f8fafc;
        color: #0f172a;
        padding: 10px 12px;
        font-size: 14px;
        outline: none;
        transition: border-color .15s ease, background-color .15s ease;
    }

    .trip-input:focus,
    .trip-select:focus,
    .trip-textarea:focus {
        border-color: #94a3b8;
        background: #fff;
    }

    .saved-route-tools {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .saved-route-toolbar {
        display: flex;
        margin-left: auto;
    }

    .add-route-btn {
        border-radius: 9px;
        border: 1px solid #dbe2ea;
        background: #fff;
        color: #0f172a;
        text-decoration: none;
        padding: 7px 10px;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .route-picker {
        position: relative;
    }

    .route-picker-trigger {
        width: 100%;
        border-radius: 11px;
        border: 1px solid #dbe2ea;
        background: #f8fafc;
        color: #0f172a;
        padding: 10px 12px;
        font-size: 14px;
        text-align: left;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .route-picker-trigger:focus {
        outline: none;
        border-color: #94a3b8;
        background: #fff;
    }

    .route-picker-trigger i {
        font-size: 12px;
        color: #64748b;
        transition: transform .15s ease;
    }

    .route-picker.open .route-picker-trigger i {
        transform: rotate(180deg);
    }

    .route-picker-placeholder {
        color: #64748b;
    }

    .route-picker-panel {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        z-index: 70;
        border: 1px solid #dbe2ea;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.14);
        padding: 8px;
        display: none;
    }

    .route-picker.open .route-picker-panel {
        display: block;
    }

    .route-picker-search {
        width: 100%;
        border-radius: 9px;
        border: 1px solid #dbe2ea;
        background: #f8fafc;
        color: #0f172a;
        padding: 8px 10px;
        font-size: 13px;
        outline: none;
        margin-bottom: 8px;
    }

    .route-picker-search:focus {
        border-color: #94a3b8;
        background: #fff;
    }

    .route-picker-list {
        max-height: 220px;
        overflow: auto;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #fff;
    }

    .route-picker-option {
        width: 100%;
        border: 0;
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
        color: #0f172a;
        padding: 10px;
        text-align: left;
        cursor: pointer;
        font-size: 13px;
        line-height: 1.35;
    }

    .route-picker-option:last-child {
        border-bottom: 0;
    }

    .route-picker-option:hover {
        background: #eff6ff;
    }

    .route-picker-option.active {
        background: #3b82f6;
        color: #fff;
    }

    .route-picker-empty {
        padding: 10px;
        color: #64748b;
        font-size: 12px;
    }

    .route-select-native {
        position: absolute;
        left: 0;
        top: 0;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .status-note {
        border: 1px solid #dbe2ea;
        background: #f8fafc;
        border-radius: 10px;
        padding: 10px;
        color: #475569;
        font-size: 12px;
        line-height: 1.35;
    }

    .status-note strong {
        color: #0f172a;
    }

    .participants-card {
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        background: #f8fafc;
        padding: 10px;
        max-height: 220px;
        overflow: auto;
        display: grid;
        gap: 8px;
    }

    .participant-card {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        background: #fff;
        padding: 9px 10px;
        color: #334155;
        cursor: pointer;
    }

    .participant-card input {
        width: 16px;
        height: 16px;
        margin-top: 1px;
        accent-color: #0f172a;
    }

    .participant-avatar {
        width: 32px;
        height: 32px;
        border-radius: 999px;
        border: 1px solid #dbe2ea;
        background: #f8fafc;
        color: #0f172a;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .participant-meta {
        min-width: 0;
        display: grid;
        gap: 1px;
    }

    .participant-name {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
    }

    .participant-email {
        font-size: 11px;
        color: #64748b;
        line-height: 1.2;
        word-break: break-word;
    }

    .participants-empty {
        margin: 0;
        color: #64748b;
        font-size: 13px;
    }

    .participants-connection-help {
        margin-top: 8px;
        font-size: 12px;
        color: #64748b;
    }

    .participants-connection-help a {
        color: #1e3a8a;
        font-weight: 700;
        text-decoration: none;
    }

    .participants-connection-help a:hover {
        text-decoration: underline;
    }

    .trip-toggle-row {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #dbe2ea;
        border-radius: 11px;
        background: #f8fafc;
        padding: 10px 12px;
        font-size: 13px;
        color: #334155;
    }

    .trip-toggle-row input {
        width: 16px;
        height: 16px;
        accent-color: #0f172a;
    }

    .trip-help-text {
        margin: 0;
        font-size: 12px;
        color: #64748b;
        line-height: 1.35;
    }

    .trip-choice-grid {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .trip-conditional-group {
        display: grid;
        gap: 8px;
    }

    .trip-choice-item {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #dbe2ea;
        border-radius: 11px;
        background: #f8fafc;
        padding: 10px 12px;
        font-size: 13px;
        color: #334155;
        cursor: pointer;
    }

    .trip-choice-text {
        display: inline-flex;
        align-items: baseline;
        gap: 6px;
        flex-wrap: wrap;
    }

    .trip-choice-hint {
        font-size: 10px;
        color: #64748b;
        font-weight: 500;
        opacity: .85;
    }

    .trip-choice-item input {
        width: 16px;
        height: 16px;
        accent-color: #0f172a;
        margin: 0;
    }

    .fare-preview-card {
        border: 1px solid #fcd34d;
        background: #fffbeb;
        border-radius: 12px;
        padding: 12px;
        display: grid;
        gap: 8px;
    }

    .fare-preview-title {
        font-size: 13px;
        font-weight: 700;
        color: #854d0e;
        margin: 0;
    }

    .fare-preview-grid {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .fare-preview-item {
        background: rgba(255, 255, 255, 0.75);
        border: 1px solid #fde68a;
        border-radius: 10px;
        padding: 8px 10px;
        display: grid;
        gap: 1px;
    }

    .fare-preview-label {
        font-size: 11px;
        color: #92400e;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .fare-preview-value {
        font-size: 18px;
        color: #854d0e;
        font-weight: 700;
        line-height: 1.15;
    }

    .trip-form-error {
        margin-top: 10px;
        color: #b91c1c;
        border: 1px solid rgba(185, 28, 28, 0.25);
        background: rgba(185, 28, 28, 0.06);
        border-radius: 10px;
        padding: 8px 10px;
        font-size: 13px;
    }

    .trip-form-actions {
        margin-top: 14px;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap;
        width: 100%;
        grid-column: 1 / -1;
    }

    .trip-btn-primary,
    .trip-btn-secondary {
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        padding: 9px 12px;
        text-decoration: none;
        border: 1px solid #dbe2ea;
        cursor: pointer;
    }

    .trip-btn-primary {
        background: #0f172a;
        border-color: #0f172a;
        color: #fff;
    }

    .trip-btn-secondary {
        background: #fff;
        color: #0f172a;
    }

    .trip-cancel-modal {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.42);
        backdrop-filter: blur(2px);
        z-index: 3000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .trip-cancel-modal.show {
        display: flex;
    }

    .trip-cancel-card {
        width: min(100%, 380px);
        background: #fff;
        border: 1px solid #dbe2ea;
        border-radius: 14px;
        padding: 14px;
        display: grid;
        gap: 10px;
    }

    .trip-cancel-title {
        margin: 0;
        font-size: 18px;
        color: #0f172a;
        font-weight: 700;
    }

    .trip-cancel-text {
        margin: 0;
        font-size: 13px;
        color: #475569;
        line-height: 1.4;
    }

    .trip-cancel-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .trip-btn-danger {
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        padding: 9px 12px;
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #b91c1c;
        cursor: pointer;
    }

    .direction-section {
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        background: #f8fafc;
        padding: 12px;
        display: grid;
        gap: 10px;
        transition: opacity .16s ease, filter .16s ease, background-color .16s ease;
    }

    .direction-section[hidden] {
        display: none;
    }

    .direction-section.is-disabled {
        background: #f1f5f9;
        border-color: #dbe2ea;
        opacity: .72;
        filter: grayscale(.08);
    }

    .direction-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
    }

    .direction-title {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }

    .direction-points {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .direction-point-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        padding: 10px;
        display: grid;
        gap: 3px;
    }

    .direction-point-card.pickup {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .direction-point-card.destination {
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .direction-point-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .direction-point-card.pickup .direction-point-label {
        color: #166534;
    }

    .direction-point-card.destination .direction-point-label {
        color: #1d4ed8;
    }

    .direction-point-value {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .direction-swap-btn {
        border-radius: 10px;
        border: 1px solid #dbe2ea;
        background: #fff;
        color: #0f172a;
        font-size: 12px;
        font-weight: 700;
        padding: 8px 10px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .direction-swap-btn:disabled {
        background: #e2e8f0;
        color: #94a3b8;
        border-color: #cbd5e1;
        cursor: not-allowed;
    }

    .direction-return-block {
        border-top: 1px dashed #cbd5e1;
        padding-top: 10px;
        display: grid;
        gap: 8px;
    }

    .direction-return-block[hidden] {
        display: none;
    }

    @media (min-width: 768px) {
        .trip-form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .fare-preview-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .saved-route-tools {
            gap: 10px;
        }

        .direction-points {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .trip-form-actions .trip-btn-secondary:first-child {
            margin-left: auto;
        }
    }
</style>

@php
    $currentTripType = old('trip_type', (isset($trip) && (($trip->trip_mode ?? 'one_way') === 'two_way' || $trip->returnTrip)) ? 'two_way' : 'one_way');
    $outboundPickupKey = old('outbound_pickup_key');
    if ($outboundPickupKey === null && isset($trip) && $trip?->savedRoute) {
        $outboundPickupKey = $trip->pickup_name === $trip->savedRoute->point_b_name ? 'point_b' : 'point_a';
    }
    $outboundPickupKey = $outboundPickupKey ?: 'point_a';

    $outboundDestinationKey = old('outbound_destination_key');
    if ($outboundDestinationKey === null && isset($trip) && $trip?->savedRoute) {
        $outboundDestinationKey = $trip->destination_name === $trip->savedRoute->point_a_name ? 'point_a' : 'point_b';
    }
    $outboundDestinationKey = $outboundDestinationKey ?: ($outboundPickupKey === 'point_a' ? 'point_b' : 'point_a');

    $returnPickupKey = old('return_pickup_key');
    if ($returnPickupKey === null && isset($trip) && $trip?->returnTrip && $trip?->savedRoute) {
        $returnPickupKey = $trip->returnTrip->pickup_name === $trip->savedRoute->point_b_name ? 'point_b' : 'point_a';
    }
    $returnPickupKey = $returnPickupKey ?: ($outboundPickupKey === 'point_a' ? 'point_b' : 'point_a');

    $returnDestinationKey = old('return_destination_key');
    if ($returnDestinationKey === null && isset($trip) && $trip?->returnTrip && $trip?->savedRoute) {
        $returnDestinationKey = $trip->returnTrip->destination_name === $trip->savedRoute->point_a_name ? 'point_a' : 'point_b';
    }
    $returnDestinationKey = $returnDestinationKey ?: ($returnPickupKey === 'point_a' ? 'point_b' : 'point_a');
@endphp

<div class="trip-form-grid">
    <div class="trip-field">
        <div class="saved-route-tools">
            <label class="trip-label" for="saved_route_id">Laluan Tersimpan</label>
            <div class="saved-route-toolbar">
                <a href="{{ route('saved-routes.create') }}" class="add-route-btn">
                    <i class="fa-solid fa-plus"></i>
                    <span>Tambah Laluan</span>
                </a>
            </div>
        </div>
        <div class="route-picker" id="savedRoutePicker">
            <button type="button" class="route-picker-trigger" id="savedRouteTrigger">
                <span id="savedRouteTriggerText" class="route-picker-placeholder">-- Cari atau pilih laluan --</span>
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="route-picker-panel" id="savedRoutePanel">
                <input id="savedRouteSearchInput" class="route-picker-search" type="text" placeholder="Cari laluan..." autocomplete="off">
                <div class="route-picker-list" id="savedRouteList"></div>
            </div>
            <select id="saved_route_id" class="trip-select route-select-native" name="saved_route_id" required>
                <option value="">Pilih laluan</option>
                @foreach($savedRoutes as $savedRoute)
                    <option
                        value="{{ $savedRoute->id }}"
                        data-fare="{{ number_format((float) $savedRoute->default_fare, 2, '.', '') }}"
                        data-point-a-name="{{ $savedRoute->point_a_name }}"
                        data-point-a-lat="{{ $savedRoute->point_a_latitude }}"
                        data-point-a-lng="{{ $savedRoute->point_a_longitude }}"
                        data-point-b-name="{{ $savedRoute->point_b_name }}"
                        data-point-b-lat="{{ $savedRoute->point_b_latitude }}"
                        data-point-b-lng="{{ $savedRoute->point_b_longitude }}"
                        {{ (string) old('saved_route_id', $trip->saved_route_id ?? '') === (string) $savedRoute->id ? 'selected' : '' }}
                    >
                        {{ $savedRoute->route_name ?: $savedRoute->point_a_name.' -> '.$savedRoute->point_b_name }} (RM {{ number_format((float) $savedRoute->default_fare, 2) }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="trip-field">
        <label class="trip-label" for="trip_datetime">Tarikh & Masa Trip</label>
        <input
            id="trip_datetime"
            class="trip-input"
            type="datetime-local"
            name="trip_datetime"
            value="{{ old('trip_datetime', isset($trip) ? $trip->trip_datetime?->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
            required
        >
    </div>

    <div class="trip-field">
        <label class="trip-label">Keterlihatan</label>
        <div class="trip-choice-grid">
            <label class="trip-choice-item" for="visibility_private">
                <input
                    id="visibility_private"
                    type="radio"
                    name="visibility"
                    value="private"
                    {{ old('visibility', $trip->visibility ?? 'private') === 'private' ? 'checked' : '' }}
                    required
                >
                <span>Peribadi</span>
            </label>
            <label class="trip-choice-item" for="visibility_public">
                <input
                    id="visibility_public"
                    type="radio"
                    name="visibility"
                    value="public"
                    {{ old('visibility', $trip->visibility ?? 'private') === 'public' ? 'checked' : '' }}
                    required
                >
                <span class="trip-choice-text">
                    <span>Awam</span>
                    <span class="trip-choice-hint">(Tunjuk di Explore)</span>
                </span>
            </label>
        </div>
    </div>

    @if(!isset($trip) || !$trip)
        <div class="trip-field">
            <label class="trip-label">Jenis Trip</label>
            <div class="trip-choice-grid">
                <label class="trip-choice-item" for="trip_type_one_way">
                    <input
                        id="trip_type_one_way"
                        type="radio"
                        name="trip_type"
                        value="one_way"
                        {{ old('trip_type') === 'one_way' ? 'checked' : '' }}
                        required
                    >
                    <span>Sehala</span>
                </label>
                <label class="trip-choice-item" for="trip_type_two_way">
                    <input
                        id="trip_type_two_way"
                        type="radio"
                        name="trip_type"
                        value="two_way"
                        {{ old('trip_type') === 'two_way' ? 'checked' : '' }}
                        required
                    >
                    <span class="trip-choice-text">
                        <span>Dua Hala</span>
                        <span class="trip-choice-hint">(Auto cipta trip balik)</span>
                    </span>
                </label>
            </div>
        </div>
    @endif

    <div class="trip-field trip-field-full">
        <input type="hidden" name="outbound_pickup_key" id="outbound_pickup_key" value="{{ $outboundPickupKey }}">
        <input type="hidden" name="outbound_destination_key" id="outbound_destination_key" value="{{ $outboundDestinationKey }}">
        <input type="hidden" name="return_pickup_key" id="return_pickup_key" value="{{ $returnPickupKey }}">
        <input type="hidden" name="return_destination_key" id="return_destination_key" value="{{ $returnDestinationKey }}">

        <div class="direction-section" id="outboundDirectionSection">
            <div class="direction-section-head">
                <div>
                    <p class="direction-title">Arah Trip</p>
                    <p class="trip-help-text" id="directionHelpText">Pilih laluan tersimpan dan jenis trip dahulu. Kemudian tetapkan pickup dan destinasi sebenar di sini.</p>
                </div>
                <button type="button" class="direction-swap-btn" id="swapOutboundDirectionBtn">
                    <i class="fa-solid fa-right-left"></i>
                    <span>Tukar</span>
                </button>
            </div>
            <div class="direction-points">
                <div class="direction-point-card pickup">
                    <span class="direction-point-label"><i class="fa-solid fa-location-dot"></i>Titik Pickup</span>
                    <span class="direction-point-value" id="outboundPickupPreview">Pilih laluan tersimpan dahulu.</span>
                </div>
                <div class="direction-point-card destination">
                    <span class="direction-point-label"><i class="fa-solid fa-flag-checkered"></i>Titik Destinasi</span>
                    <span class="direction-point-value" id="outboundDestinationPreview">Pilih laluan tersimpan dahulu.</span>
                </div>
            </div>
            <div class="direction-return-block" id="returnDirectionBlock" hidden>
                <p class="direction-title">Trip Balik</p>
                <div class="direction-points">
                    <div class="direction-point-card pickup">
                        <span class="direction-point-label"><i class="fa-solid fa-location-dot"></i>Titik Pickup</span>
                        <span class="direction-point-value" id="returnPickupPreview">Pilih laluan tersimpan dahulu.</span>
                    </div>
                    <div class="direction-point-card destination">
                        <span class="direction-point-label"><i class="fa-solid fa-flag-checkered"></i>Titik Destinasi</span>
                        <span class="direction-point-value" id="returnDestinationPreview">Pilih laluan tersimpan dahulu.</span>
                    </div>
                </div>
            </div>
        </div>

    <div class="trip-field">
        <label class="trip-label" for="include_driver_in_split">Agihan Tambang</label>
        <input type="hidden" name="include_driver_in_split" value="0">
        <label class="trip-toggle-row" for="include_driver_in_split">
            <input
                id="include_driver_in_split"
                type="checkbox"
                name="include_driver_in_split"
                value="1"
                {{ (string) old('include_driver_in_split', '1') === '1' ? 'checked' : '' }}
            >
            <span>Termasuk pemandu dalam agihan tambang</span>
        </label>
        <p class="trip-help-text">Untuk trip awam, bilangan bahagi ikut had tempat duduk (penumpang sahaja). Tandakan ini untuk turut masukkan pemandu dalam agihan.</p>
    </div>

    <div class="trip-field trip-field-full trip-conditional-group" id="publicTripFields">
        <div class="trip-field">
            <label class="trip-label" for="seat_limit">Had Tempat Duduk</label>
            <input
                id="seat_limit"
                class="trip-input"
                type="number"
                name="seat_limit"
                min="1"
                max="20"
                value="{{ old('seat_limit', $trip->seat_limit ?? '') }}"
            >
            <p class="trip-help-text">Had tempat duduk untuk penumpang sahaja (pemandu tidak termasuk). Penumpang pra-pilih akan guna had ini, dan tempat yang tinggal dikurangkan selepas trip dicipta.</p>
        </div>

        <div class="trip-field">
            <label class="trip-label" for="public_note">Nota Awam</label>
            <textarea id="public_note" class="trip-textarea" name="public_note" rows="2">{{ old('public_note', $trip->public_note ?? '') }}</textarea>
            <p class="trip-help-text">Nota pendek yang ditunjuk pada kad trip Explore.</p>
        </div>
    </div>

    <input id="status_system_input" type="hidden" name="status" value="">

    <div class="trip-field trip-field-full">
        <label class="trip-label" id="passengerSelectionLabel">Penumpang (Sambungan Diterima)</label>
        <div class="participants-card">
            @forelse($selectableParticipants as $participant)
                <label class="participant-card">
                    <input
                        type="checkbox"
                        name="participant_ids[]"
                        value="{{ $participant->id }}"
                        {{ in_array($participant->id, old('participant_ids', $selectedParticipants ?? []), true) ? 'checked' : '' }}
                    >
                    <span class="participant-avatar" aria-hidden="true">{{ strtoupper(substr($participant->name, 0, 1)) }}</span>
                    <span class="participant-meta">
                        <span class="participant-name">{{ $participant->name }}</span>
                        <span class="participant-email">{{ $participant->email }}</span>
                    </span>
                </label>
            @empty
                <p class="participants-empty">Tiada sambungan diterima lagi.</p>
            @endforelse
        </div>
        <p class="participants-connection-help">Tak jumpa rakan anda di sini? <a href="{{ route('connections.index') }}">Klik di sini untuk buka Sambungan dan tambah mereka dahulu.</a></p>
        <p class="trip-help-text" id="passengerSelectionHint">Pilih penumpang dipercayai untuk trip peribadi. Trip awam boleh terima permohonan sertai dari Explore.</p>
        <p class="trip-help-text" id="privateRoutePointHint">Penumpang peribadi menggunakan tarikh/masa trip dan titik laluan secara lalai. Pickup/drop-off tersuai boleh guna semula data pilihan laluan yang sama kemudian.</p>
    </div>

    <div class="trip-field trip-field-full">
        <label class="trip-label" for="note">Nota</label>
        <textarea id="note" class="trip-textarea" name="note" rows="3">{{ old('note', $trip->note ?? '') }}</textarea>
    </div>

    <div class="trip-field trip-field-full">
        <div class="fare-preview-card">
            <p class="fare-preview-title">Pratonton Tambang</p>
            <p class="trip-help-text" id="fare_preview_hint">Pratonton tambang sehala.</p>
            <div class="fare-preview-grid">
                <div class="fare-preview-item">
                    <span class="fare-preview-label">Tambang Jumlah</span>
                    <span class="fare-preview-value" id="fare_total_preview">RM 0.00</span>
                </div>
                <div class="fare-preview-item">
                    <span class="fare-preview-label">Bilangan Bahagi</span>
                    <span class="fare-preview-value" id="participant_count_preview">1</span>
                </div>
                <div class="fare-preview-item">
                    <span class="fare-preview-label">Tambang / Orang</span>
                    <span class="fare-preview-value" id="fare_per_person_preview">RM 0.00</span>
                </div>
            </div>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="trip-form-error">
        {{ $errors->first() }}
    </div>
@endif

<div class="trip-form-actions">
    <button type="button" class="trip-btn-secondary" id="tripCancelBtn">Batal</button>
    <button type="submit" class="trip-btn-primary">{{ $submitLabel }}</button>
</div>

<div class="trip-cancel-modal" id="tripCancelModal" aria-hidden="true">
    <div class="trip-cancel-card" role="dialog" aria-modal="true" aria-labelledby="tripCancelTitle">
        <h3 class="trip-cancel-title" id="tripCancelTitle">Batal perubahan?</h3>
        <p class="trip-cancel-text">Anda telah mengisi butiran trip. Adakah anda mahu simpan sebagai draf atau buang borang ini?</p>
        <div class="trip-cancel-actions">
            <button type="button" class="trip-btn-secondary" id="tripKeepEditingBtn">Terus Edit</button>
            <button type="button" class="trip-btn-danger" id="tripDiscardBtn">Buang</button>
            <button type="button" class="trip-btn-primary" id="tripSaveDraftBtn">Simpan Draf</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.querySelector('form[action*="trips"]');
        const routeSelect = document.getElementById('saved_route_id');
        const routePicker = document.getElementById('savedRoutePicker');
        const routeTrigger = document.getElementById('savedRouteTrigger');
        const routeTriggerText = document.getElementById('savedRouteTriggerText');
        const routePanel = document.getElementById('savedRoutePanel');
        const routeList = document.getElementById('savedRouteList');
        const routeSearchInput = document.getElementById('savedRouteSearchInput');
        const checkboxes = document.querySelectorAll('input[name="participant_ids[]"]');
        const totalFareEl = document.getElementById('fare_total_preview');
        const participantCountEl = document.getElementById('participant_count_preview');
        const farePerPersonEl = document.getElementById('fare_per_person_preview');
        const statusInput = document.getElementById('status_system_input');
        const cancelBtn = document.getElementById('tripCancelBtn');
        const modal = document.getElementById('tripCancelModal');
        const keepEditingBtn = document.getElementById('tripKeepEditingBtn');
        const discardBtn = document.getElementById('tripDiscardBtn');
        const saveDraftBtn = document.getElementById('tripSaveDraftBtn');
        const noteInput = document.getElementById('note');
        const tripDatetime = document.getElementById('trip_datetime');
        const includeDriverCheckbox = document.getElementById('include_driver_in_split');
        const tripTypeInputs = document.querySelectorAll('input[name="trip_type"]');
        const visibilityInputs = document.querySelectorAll('input[name="visibility"]');
        const publicTripFields = document.getElementById('publicTripFields');
        const seatLimitInput = document.getElementById('seat_limit');
        const passengerSelectionLabel = document.getElementById('passengerSelectionLabel');
        const passengerSelectionHint = document.getElementById('passengerSelectionHint');
        const farePreviewHint = document.getElementById('fare_preview_hint');
        const outboundPickupKeyInput = document.getElementById('outbound_pickup_key');
        const outboundDestinationKeyInput = document.getElementById('outbound_destination_key');
        const returnPickupKeyInput = document.getElementById('return_pickup_key');
        const returnDestinationKeyInput = document.getElementById('return_destination_key');
        const outboundPickupPreview = document.getElementById('outboundPickupPreview');
        const outboundDestinationPreview = document.getElementById('outboundDestinationPreview');
        const returnPickupPreview = document.getElementById('returnPickupPreview');
        const returnDestinationPreview = document.getElementById('returnDestinationPreview');
        const outboundDirectionSection = document.getElementById('outboundDirectionSection');
        const returnDirectionBlock = document.getElementById('returnDirectionBlock');
        const swapOutboundDirectionBtn = document.getElementById('swapOutboundDirectionBtn');
        const directionHelpText = document.getElementById('directionHelpText');
        const fallbackTripType = @json($currentTripType);
        let isSubmittingForm = false;

        if (!form || !routeSelect || !totalFareEl || !participantCountEl || !farePerPersonEl || !statusInput) return;

        const initialSnapshot = {
            savedRouteId: routeSelect.value,
            tripDatetime: tripDatetime ? tripDatetime.value : '',
            note: noteInput ? noteInput.value : '',
            publicNote: document.getElementById('public_note') ? document.getElementById('public_note').value : '',
            includeDriver: includeDriverCheckbox ? includeDriverCheckbox.checked : true,
            visibility: selectedVisibility(),
            seatLimit: seatLimitInput ? seatLimitInput.value : '',
            tripType: selectedTripType(),
            outboundPickupKey: outboundPickupKeyInput ? outboundPickupKeyInput.value : '',
            outboundDestinationKey: outboundDestinationKeyInput ? outboundDestinationKeyInput.value : '',
            returnPickupKey: returnPickupKeyInput ? returnPickupKeyInput.value : '',
            returnDestinationKey: returnDestinationKeyInput ? returnDestinationKeyInput.value : '',
            checkedParticipants: Array.from(checkboxes).filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value).join(',')
        };

        function selectedFare() {
            const option = routeSelect.options[routeSelect.selectedIndex];
            return parseFloat(option && option.dataset ? (option.dataset.fare || '0') : '0') || 0;
        }

        function selectedTripType() {
            const chosen = Array.from(tripTypeInputs).find((input) => input.checked);
            if (chosen) return chosen.value;
            return tripTypeInputs.length ? '' : fallbackTripType;
        }

        function selectedVisibility() {
            const chosen = Array.from(visibilityInputs).find((input) => input.checked);
            return chosen ? chosen.value : 'private';
        }

        function tripMultiplier() {
            return selectedTripType() === 'two_way' ? 2 : 1;
        }

        function participantCount() {
            const isPublic = selectedVisibility() === 'public';
            if (isPublic) {
                const seatLimit = seatLimitInput ? (parseInt(seatLimitInput.value || '0', 10) || 0) : 0;
                const includeDriver = includeDriverCheckbox && includeDriverCheckbox.checked;
                const splitCount = seatLimit + (includeDriver ? 1 : 0);
                return splitCount > 0 ? splitCount : (includeDriver ? 1 : 0);
            }

            let count = includeDriverCheckbox && includeDriverCheckbox.checked ? 1 : 0;
            checkboxes.forEach((checkbox) => {
                if (checkbox.checked) count += 1;
            });
            return count;
        }

        function recalc() {
            const total = selectedFare() * tripMultiplier();
            const count = participantCount();
            const perPerson = count > 0 ? (total / count) : 0;
            totalFareEl.textContent = `RM ${total.toFixed(2)}`;
            participantCountEl.textContent = String(count);
            farePerPersonEl.textContent = `RM ${perPerson.toFixed(2)}`;
            if (farePreviewHint) {
                const publicHint = selectedVisibility() === 'public'
                    ? ' Bilangan bahagi awam ikut had tempat duduk (penumpang sahaja).'
                    : '';
                farePreviewHint.textContent = tripMultiplier() === 2
                    ? `Dua hala dipilih: jumlah termasuk tambang trip balik.${publicHint}`
                    : `Pratonton tambang sehala.${publicHint}`;
            }
            updateDirectionVisibility();
        }

        function currentSnapshot() {
            return {
                savedRouteId: routeSelect.value,
                tripDatetime: tripDatetime ? tripDatetime.value : '',
                note: noteInput ? noteInput.value : '',
                publicNote: document.getElementById('public_note') ? document.getElementById('public_note').value : '',
                includeDriver: includeDriverCheckbox ? includeDriverCheckbox.checked : true,
                visibility: selectedVisibility(),
                seatLimit: seatLimitInput ? seatLimitInput.value : '',
                tripType: selectedTripType(),
                outboundPickupKey: outboundPickupKeyInput ? outboundPickupKeyInput.value : '',
                outboundDestinationKey: outboundDestinationKeyInput ? outboundDestinationKeyInput.value : '',
                returnPickupKey: returnPickupKeyInput ? returnPickupKeyInput.value : '',
                returnDestinationKey: returnDestinationKeyInput ? returnDestinationKeyInput.value : '',
                checkedParticipants: Array.from(checkboxes).filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value).join(',')
            };
        }

        function hasFormProgress() {
            const current = currentSnapshot();
            return current.savedRouteId !== '' ||
                current.note.trim() !== '' ||
                current.checkedParticipants !== '' ||
                (current.tripDatetime !== '' && current.tripDatetime !== initialSnapshot.tripDatetime) ||
                current.savedRouteId !== initialSnapshot.savedRouteId ||
                current.note !== initialSnapshot.note ||
                current.publicNote !== initialSnapshot.publicNote ||
                current.includeDriver !== initialSnapshot.includeDriver ||
                current.visibility !== initialSnapshot.visibility ||
                current.seatLimit !== initialSnapshot.seatLimit ||
                current.tripType !== initialSnapshot.tripType ||
                current.outboundPickupKey !== initialSnapshot.outboundPickupKey ||
                current.outboundDestinationKey !== initialSnapshot.outboundDestinationKey ||
                current.returnPickupKey !== initialSnapshot.returnPickupKey ||
                current.returnDestinationKey !== initialSnapshot.returnDestinationKey ||
                current.checkedParticipants !== initialSnapshot.checkedParticipants;
        }

        function updateVisibilityFields() {
            const visibility = selectedVisibility();
            const isPublic = visibility === 'public';

            if (publicTripFields) {
                publicTripFields.style.display = isPublic ? 'grid' : 'none';
            }
            if (seatLimitInput) {
                seatLimitInput.required = isPublic;
            }
            if (passengerSelectionLabel) {
                passengerSelectionLabel.textContent = isPublic
                    ? 'Penumpang Pra-pilih (Pilihan)'
                    : 'Penumpang (Sambungan Diterima)';
            }
            if (passengerSelectionHint) {
                passengerSelectionHint.textContent = isPublic
                    ? 'Pilihan: tambah penumpang dipercayai terlebih dahulu. Penumpang pra-pilih menggunakan had tempat duduk, dan tempat yang tinggal terbuka untuk permohonan Explore.'
                    : 'Pilih penumpang dipercayai untuk trip peribadi.';
            }
        }


        function selectedRouteData() {
            const option = routeSelect.options[routeSelect.selectedIndex];
            if (!option || !option.value || !option.dataset) return null;

            return {
                point_a: {
                    name: option.dataset.pointAName || 'Point A',
                    lat: option.dataset.pointALat || '',
                    lng: option.dataset.pointALng || '',
                },
                point_b: {
                    name: option.dataset.pointBName || 'Point B',
                    lat: option.dataset.pointBLat || '',
                    lng: option.dataset.pointBLng || '',
                },
            };
        }

        function normalizePointKey(value, fallback = 'point_a') {
            return value === 'point_b' ? 'point_b' : fallback;
        }

        function oppositePointKey(value) {
            return normalizePointKey(value) === 'point_a' ? 'point_b' : 'point_a';
        }

        function hasDirectionPrerequisites() {
            return Boolean(selectedRouteData()) && Boolean(selectedTripType());
        }

        function syncDirectionKeys() {
            const outboundPickupKey = normalizePointKey(outboundPickupKeyInput ? outboundPickupKeyInput.value : 'point_a');
            const outboundDestinationKey = oppositePointKey(outboundPickupKey);
            const returnPickupKey = outboundDestinationKey;
            const returnDestinationKey = outboundPickupKey;

            if (outboundPickupKeyInput) outboundPickupKeyInput.value = outboundPickupKey;
            if (outboundDestinationKeyInput) outboundDestinationKeyInput.value = outboundDestinationKey;
            if (returnPickupKeyInput) returnPickupKeyInput.value = returnPickupKey;
            if (returnDestinationKeyInput) returnDestinationKeyInput.value = returnDestinationKey;
        }

        function updateDirectionPreview() {
            const routeData = selectedRouteData();
            const tripType = selectedTripType();
            const isReady = hasDirectionPrerequisites();
            if (!isReady) {
                const waitingText = routeData ? 'Pilih jenis trip dahulu.' : 'Pilih laluan tersimpan dahulu.';
                if (outboundPickupPreview) outboundPickupPreview.textContent = waitingText;
                if (outboundDestinationPreview) outboundDestinationPreview.textContent = waitingText;
                if (returnPickupPreview) returnPickupPreview.textContent = waitingText;
                if (returnDestinationPreview) returnDestinationPreview.textContent = waitingText;
                if (outboundDirectionSection) outboundDirectionSection.classList.add('is-disabled');
                if (swapOutboundDirectionBtn) swapOutboundDirectionBtn.disabled = true;
                if (directionHelpText) directionHelpText.textContent = 'Pilih laluan tersimpan dan jenis trip dahulu. Kemudian tetapkan pickup dan destinasi sebenar di sini.';
                return;
            }

            syncDirectionKeys();

            const outboundPickupKey = normalizePointKey(outboundPickupKeyInput ? outboundPickupKeyInput.value : 'point_a');
            const outboundDestinationKey = oppositePointKey(outboundPickupKey);
            const returnPickupKey = outboundDestinationKey;
            const returnDestinationKey = outboundPickupKey;

            if (outboundPickupPreview) outboundPickupPreview.textContent = routeData[outboundPickupKey].name || '-';
            if (outboundDestinationPreview) outboundDestinationPreview.textContent = routeData[outboundDestinationKey].name || '-';
            if (returnPickupPreview) returnPickupPreview.textContent = routeData[returnPickupKey].name || '-';
            if (returnDestinationPreview) returnDestinationPreview.textContent = routeData[returnDestinationKey].name || '-';
            if (outboundDirectionSection) outboundDirectionSection.classList.toggle('is-disabled', !isReady);
            if (swapOutboundDirectionBtn) swapOutboundDirectionBtn.disabled = !isReady;
            if (directionHelpText) {
                directionHelpText.textContent = tripType === 'two_way'
                    ? 'Laluan tersimpan hanya menyimpan alamat. Kad ini menunjukkan arah pergi dan balik bersama.'
                    : 'Laluan tersimpan hanya menyimpan alamat. Tetapkan pickup dan destinasi sebenar di sini.';
            }
        }

        function updateDirectionVisibility() {
            const isTwoWay = selectedTripType() === 'two_way';
            if (outboundDirectionSection) outboundDirectionSection.hidden = false;
            if (returnDirectionBlock) returnDirectionBlock.hidden = !isTwoWay;
        }

        function swapDirection() {
            if (!outboundPickupKeyInput || !hasDirectionPrerequisites()) return;
            outboundPickupKeyInput.value = oppositePointKey(outboundPickupKeyInput.value);
            syncDirectionKeys();
            updateDirectionPreview();
        }

        function showModal() {
            if (!modal) return;
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }

        function hideModal() {
            if (!modal) return;
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        }

        function submitAsDraft() {
            isSubmittingForm = true;
            statusInput.value = 'draft';
            form.submit();
        }

        function selectedRouteText() {
            const option = routeSelect.options[routeSelect.selectedIndex];
            if (!option || !option.value) return '-- Cari atau pilih laluan --';
            return (option.textContent || '').trim();
        }

        function updateRouteTriggerText() {
            if (!routeTriggerText) return;
            const text = selectedRouteText();
            routeTriggerText.textContent = text;
            routeTriggerText.classList.toggle('route-picker-placeholder', text === '-- Cari atau pilih laluan --');
        }

        function getRouteOptions() {
            return Array.from(routeSelect.options).filter((option) => option.value);
        }

        function renderRouteOptions() {
            if (!routeList) return;
            const query = (routeSearchInput && routeSearchInput.value ? routeSearchInput.value : '').toLowerCase().trim();
            const selected = routeSelect.value;
            const options = getRouteOptions().filter((option) => {
                const text = (option.textContent || '').toLowerCase();
                return text.indexOf(query) !== -1;
            });

            if (!options.length) {
                routeList.innerHTML = '<div class="route-picker-empty">Tiada laluan dijumpai. Cuba kata kunci lain.</div>';
                return;
            }

            routeList.innerHTML = options.map((option) => {
                const value = String(option.value);
                const text = (option.textContent || '').trim()
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                const isActive = selected === value ? ' active' : '';
                return '<button type="button" class="route-picker-option' + isActive + '" data-value="' + value + '">' + text + '</button>';
            }).join('');

            Array.from(routeList.querySelectorAll('.route-picker-option')).forEach((button) => {
                button.addEventListener('click', function () {
                    routeSelect.value = button.getAttribute('data-value') || '';
                    updateRouteTriggerText();
                    recalc();
                    closeRoutePicker();
                });
            });
        }

        function openRoutePicker() {
            if (!routePicker) return;
            routePicker.classList.add('open');
            renderRouteOptions();
            if (routeSearchInput) {
                routeSearchInput.value = '';
                routeSearchInput.focus();
            }
        }

        function closeRoutePicker() {
            if (!routePicker) return;
            routePicker.classList.remove('open');
        }

        routeSelect.addEventListener('change', () => {
            syncDirectionKeys();
            updateDirectionPreview();
            recalc();
        });
        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', recalc));
        if (includeDriverCheckbox) {
            includeDriverCheckbox.addEventListener('change', recalc);
        }
        tripTypeInputs.forEach((input) => input.addEventListener('change', () => {
            syncDirectionKeys();
            updateDirectionPreview();
            recalc();
        }));
        visibilityInputs.forEach((input) => input.addEventListener('change', () => {
            updateVisibilityFields();
            recalc();
        }));
        if (seatLimitInput) {
            seatLimitInput.addEventListener('input', recalc);
            seatLimitInput.addEventListener('change', recalc);
        }

        if (routeTrigger) {
            routeTrigger.addEventListener('click', function () {
                if (!routePicker) return;
                if (routePicker.classList.contains('open')) {
                    closeRoutePicker();
                } else {
                    openRoutePicker();
                }
            });
        }

        if (routeSearchInput) {
            routeSearchInput.addEventListener('input', renderRouteOptions);
        }

        if (swapOutboundDirectionBtn) {
            swapOutboundDirectionBtn.addEventListener('click', () => {
                swapDirection();
            });
        }

        document.addEventListener('click', function (event) {
            if (!routePicker) return;
            if (!routePicker.contains(event.target)) {
                closeRoutePicker();
            }
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                if (!hasFormProgress()) {
                    isSubmittingForm = true;
                    window.location.href = "{{ route('trips.index') }}";
                    return;
                }
                showModal();
            });
        }

        window.addEventListener('carpoolhub:mobile-back', function (event) {
            if (!hasFormProgress()) {
                return;
            }
            event.preventDefault();
            showModal();
        });

        if (keepEditingBtn) keepEditingBtn.addEventListener('click', hideModal);
        if (discardBtn) {
            discardBtn.addEventListener('click', function () {
                isSubmittingForm = true;
                window.location.href = "{{ route('trips.index') }}";
            });
        }
        if (saveDraftBtn) saveDraftBtn.addEventListener('click', submitAsDraft);

        form.addEventListener('submit', function () {
            isSubmittingForm = true;
        });

        // Intercept browser/device back to show same cancel-confirm modal when form has progress.
        if (!window.__carpoolhubTripCreateBackGuardApplied) {
            window.__carpoolhubTripCreateBackGuardApplied = true;
            history.pushState({ tripCreateGuard: true }, '', window.location.href);
            window.addEventListener('popstate', function () {
                if (isSubmittingForm || !hasFormProgress()) {
                    return;
                }
                showModal();
                history.pushState({ tripCreateGuard: true }, '', window.location.href);
            });
        }

        window.addEventListener('beforeunload', function (event) {
            if (isSubmittingForm || !hasFormProgress()) {
                return;
            }
            event.preventDefault();
            event.returnValue = 'Anda mempunyai data trip yang belum disimpan. Jika anda keluar sekarang, input anda akan hilang.';
        });

        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) hideModal();
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hideModal();
                closeRoutePicker();
            }
        });

        updateRouteTriggerText();
        syncDirectionKeys();
        updateDirectionPreview();
        updateVisibilityFields();
        recalc();
    })();
</script>
