@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <style>
        .page-shell {
            display: grid;
            gap: 12px;
        }

        .page-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
        }

        .title-card {
            position: relative;
            overflow: hidden;
        }

        .title-card::after {
            content: "";
            position: absolute;
            right: -18px;
            top: -8px;
            width: 128px;
            height: 128px;
            background: no-repeat center/contain url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Cg fill='none' stroke='%230f172a' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M18 58c8-16 16-10 24-24 7-13 16-13 22-22'/%3E%3Ccircle cx='16' cy='60' r='5'/%3E%3Cpath d='M66 13l5 9h-10z' fill='%230f172a' stroke='none'/%3E%3Cpath d='M34 44l5-5M40 50l5-5'/%3E%3C/g%3E%3C/svg%3E");
            opacity: .08;
            transform: rotate(18deg);
            pointer-events: none;
        }

        .page-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 28px;
            line-height: 1.1;
            color: #0f172a;
        }

        .page-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .route-info-note {
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 12px;
            padding: 10px 12px;
            color: #92400e;
            font-size: 13px;
            line-height: 1.4;
        }

        .route-form-grid {
            margin-top: 12px;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .map-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .route-section-head {
            display: grid;
            gap: 4px;
        }

        .route-section-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .route-section-hint {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.35;
        }

        .map-tools {
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr;
        }

        .map-search-row {
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) auto auto;
        }
        .map-search-input-wrap {
            position: relative;
            min-width: 0;
        }

        .map-search-row input {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #fff;
            padding: 9px 11px;
            font-size: 13px;
            outline: none;
        }

        .map-search-row button {
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            padding: 0 12px;
            cursor: pointer;
        }

        .map-search-row button:disabled {
            opacity: .6;
            cursor: not-allowed;
        }
        #mapLocateBtn {
            width: 40px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        #mapLocateBtn.is-loading i {
            animation: mapLocateSpin .8s linear infinite;
        }
        @keyframes mapLocateSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .map-search-suggest {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 40;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
            max-height: 320px;
            overflow: auto;
            display: none;
        }
        .map-search-suggest.show { display: block; }
        .map-search-suggest-btn {
            width: 100%;
            border: 0;
            border-bottom: 1px solid #eef2f7;
            background: #fff;
            color: #0f172a;
            padding: 8px 10px;
            text-align: left;
            cursor: pointer;
            display: grid;
            gap: 2px;
        }
        .map-search-suggest-btn:last-child { border-bottom: 0; }
        .map-search-suggest-btn:hover { background: #f8fafc; }
        .map-search-suggest-main { font-size: 12px; font-weight: 700; color: #0f172a; }
        .map-search-suggest-sub { font-size: 11px; color: #64748b; line-height: 1.25; }
        .map-search-suggest-meta {
            color: #92400e;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .map-target-switch {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .map-target-btn {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            color: #334155;
            padding: 9px 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .map-target-btn.active {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .map-search-preview {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px;
            display: none;
            gap: 8px;
        }

        .map-search-preview.show { display: grid; }
        .map-search-preview-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }
        .map-search-preview-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.25;
        }
        .map-search-preview-sub {
            margin-top: 2px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.35;
        }
        .map-search-preview-badge {
            border: 1px solid #fde68a;
            border-radius: 999px;
            background: #fffbeb;
            color: #92400e;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }
        .map-search-preview-actions {
            display: grid;
            gap: 7px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .map-preview-use-btn,
        .map-preview-close-btn {
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            min-height: 38px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }
        .map-preview-use-btn.primary {
            border-color: #0f172a;
            background: #0f172a;
            color: #fff;
        }

        .map-help {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }

        .map-status {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px;
            display: grid;
            gap: 4px;
        }

        .map-step-title {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }

        .map-step-text {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .map-step-hint {
            font-size: 12px;
            color: #64748b;
            line-height: 1.3;
        }

        .map-step-actions {
            margin-top: 4px;
        }

        .map-reset-btn {
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #b91c1c;
            border-radius: 9px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: none;
        }

        .map-reset-btn.show {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .map-step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 24px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            background: #eff6ff;
            margin-right: 6px;
        }

        #routeMap {
            width: 100%;
            height: 300px;
            border-radius: 12px;
            border: 1px solid #dbe2ea;
        }

        .route-options {
            display: grid;
            gap: 8px;
        }

        .route-option-btn {
            border: 1px solid #dbe2ea;
            background: #fff;
            border-radius: 12px;
            padding: 10px;
            text-align: left;
            cursor: pointer;
            display: grid;
            gap: 4px;
        }

        .route-option-btn.active {
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .route-option-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .route-option-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .route-option-meta {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
        }

        .route-option-road {
            font-size: 11px;
            color: #64748b;
            line-height: 1.3;
        }
        .route-option-suggestion {
            margin-top: 4px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 700;
        }
        .route-option-reason {
            font-size: 11px;
            color: #475569;
            line-height: 1.35;
        }

        .route-empty {
            border: 1px dashed #dbe2ea;
            border-radius: 10px;
            padding: 8px 10px;
            color: #64748b;
            font-size: 12px;
        }

        .pickup-pin,
        .destination-pin,
        .preview-pin {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.3);
        }

        .pickup-pin { background: #16a34a; }
        .destination-pin { background: #dc2626; }
        .preview-pin {
            width: 24px;
            height: 24px;
            background: #f59e0b;
            border: 3px solid #fff;
            box-shadow: 0 0 0 5px rgba(245, 158, 11, .20), 0 8px 18px rgba(15, 23, 42, .28);
        }

        .field-block {
            display: grid;
            gap: 6px;
        }

        .field-block-full {
            grid-column: 1 / -1;
        }

        .route-preference-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .route-point-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .route-point-group {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .route-point-group.pickup {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .route-point-group.destination {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .route-point-group-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .route-point-group-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .route-point-group.pickup .route-point-group-title {
            color: #166534;
        }

        .route-point-group.destination .route-point-group-title {
            color: #b45309;
        }

        .route-point-group-badge {
            border: 1px solid rgba(255, 255, 255, .75);
            border-radius: 999px;
            background: rgba(255, 255, 255, .78);
            color: #475569;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 800;
        }

        .field-block label {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
        }

        .field-block input {
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

        .field-block input:focus {
            border-color: #94a3b8;
            background: #fff;
        }

        .coords-head {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 2px;
        }

        .coords-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            background: #f8fafc;
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            padding: 10px;
        }

        .coords-grid > div {
            display: grid;
            gap: 6px;
        }

        .active-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .active-toggle input {
            width: 16px;
            height: 16px;
            accent-color: #0f172a;
        }

        .form-error {
            margin-top: 10px;
            color: #b91c1c;
            border: 1px solid rgba(185, 28, 28, 0.25);
            background: rgba(185, 28, 28, 0.06);
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
        }

        .form-actions {
            margin-top: 14px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn-primary,
        .btn-secondary {
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 12px;
            text-decoration: none;
            border: 1px solid #dbe2ea;
            cursor: pointer;
        }

        .btn-primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }

        .btn-secondary {
            background: #fff;
            color: #0f172a;
        }

        @media (min-width: 768px) {
            .page-card {
                padding: 16px;
            }

            .route-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .coords-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .route-point-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .map-tools {
                grid-template-columns: 1fr;
                align-items: center;
            }
        }

    </style>

    <div class="page-shell">
        <section class="page-card title-card">
            <h1 class="page-title">Cipta Laluan Tersimpan</h1>
            <p class="page-subtitle">Sedia laluan boleh guna semula untuk mencipta trip dengan lebih cepat.</p>
        </section>

        <section class="page-card route-info-note">
            <i class="fa-solid fa-circle-info"></i>
            Simpan laluan sehala sahaja. Tidak perlu cipta kedua-dua arah.
            Tetapkan tambang untuk sehala sahaja. Semasa mencipta trip, pilih Sehala atau Dua Hala.
            Jika pilih Dua Hala, sistem akan automatik termasukkan tambang balik.
        </section>

        <section class="page-card">
            <div class="map-card">
                <div class="route-section-head">
                    <span class="route-section-title"><i class="fa-solid fa-map-location-dot"></i>Pickup dan drop-off</span>
                    <p class="route-section-hint">Cari, pratonton, atau ketuk peta untuk sahkan Pickup Titik A dan Drop-off Titik B.</p>
                </div>
                <div class="map-tools">
                    <div class="map-search-row">
                        <div class="map-search-input-wrap">
                            <input type="text" id="mapSearchInput" placeholder="Cari alamat, tempat, fakulti, taman di Malaysia..." autocomplete="off">
                            <div class="map-search-suggest" id="mapSearchSuggest"></div>
                        </div>
                        <button type="button" id="mapSearchBtn">Cari</button>
                        <button type="button" id="mapLocateBtn" title="Guna lokasi semasa" aria-label="Guna lokasi semasa">
                            <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="map-target-switch" aria-label="Route point target">
                    <button type="button" class="map-target-btn active" data-map-target="pickup"><i class="fa-solid fa-location-dot"></i>Pickup Titik A</button>
                    <button type="button" class="map-target-btn" data-map-target="destination"><i class="fa-solid fa-flag-checkered"></i>Drop-off Titik B</button>
                </div>
                <div class="map-search-preview" id="mapSearchPreview">
                    <div class="map-search-preview-top">
                        <div>
                            <div class="map-search-preview-title" id="mapSearchPreviewTitle">Pratonton lokasi</div>
                            <div class="map-search-preview-sub" id="mapSearchPreviewSub">Gerakkan peta dan sahkan titik yang betul.</div>
                        </div>
                        <span class="map-search-preview-badge">Pratonton sahaja</span>
                    </div>
                    <div class="map-search-preview-actions">
                        <button type="button" class="map-preview-use-btn primary" id="mapPreviewUseBtn">Guna sebagai titik dipilih</button>
                        <button type="button" class="map-preview-close-btn" id="mapPreviewCloseBtn">Buang pratonton</button>
                    </div>
                </div>
                <p class="map-help">Carian hanya pratonton tempat dahulu. Sahkan dengan butang, atau ketuk titik tepat pada peta.</p>
                <div class="map-status">
                    <div class="map-step-title">
                        <span class="map-step-badge" id="mapStepNumber">1/3</span>
                        Kemajuan Tetapan Laluan
                    </div>
                    <div class="map-step-text" id="mapStepText">Ketuk pada peta untuk tetapkan Titik A.</div>
                    <div class="map-step-hint" id="mapStepHint">Kemudian ketuk sekali lagi untuk tetapkan Titik B dan lihat pilihan laluan.</div>
                    <div class="map-step-actions">
                        <button type="button" class="map-reset-btn" id="mapResetBtn"><i class="fa-solid fa-rotate-left"></i><span>Set Semula Laluan</span></button>
                    </div>
                </div>
                <div id="routeMap"></div>
                <div class="route-options" id="routeOptions">
                    <div class="route-empty">Tetapkan Titik A dan Titik B untuk lihat pilihan laluan dengan jarak dan ETA.</div>
                </div>
            </div>

            <form action="{{ route('saved-routes.store') }}" method="POST">
                @include('saved-routes._form', ['submitLabel' => 'Simpan Laluan'])
            </form>
        </section>
    </div>

    <script>
        (function () {
            if (typeof L === 'undefined') {
                return;
            }

            var malaysiaCenter = [3.139, 101.6869];
            var map = L.map('routeMap').setView(malaysiaCenter, 12);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var searchInput = document.getElementById('mapSearchInput');
            var searchBtn = document.getElementById('mapSearchBtn');
            var searchSuggest = document.getElementById('mapSearchSuggest');
            var locateBtn = document.getElementById('mapLocateBtn');
            var previewCard = document.getElementById('mapSearchPreview');
            var previewTitle = document.getElementById('mapSearchPreviewTitle');
            var previewSub = document.getElementById('mapSearchPreviewSub');
            var previewUseBtn = document.getElementById('mapPreviewUseBtn');
            var previewCloseBtn = document.getElementById('mapPreviewCloseBtn');
            var routeOptionsEl = document.getElementById('routeOptions');
            var mapStepNumber = document.getElementById('mapStepNumber');
            var mapStepText = document.getElementById('mapStepText');
            var mapStepHint = document.getElementById('mapStepHint');
            var mapResetBtn = document.getElementById('mapResetBtn');

            var pickupName = document.getElementById('point_a_name');
            var pickupLat = document.getElementById('point_a_latitude');
            var pickupLng = document.getElementById('point_a_longitude');
            var destinationName = document.getElementById('point_b_name');
            var destinationLat = document.getElementById('point_b_latitude');
            var destinationLng = document.getElementById('point_b_longitude');
            var defaultFareInput = document.getElementById('default_fare');

            var nextTarget = 'pickup';
            var pickupMarker = null;
            var destinationMarker = null;
            var routeLayers = [];
            var selectedRouteIndex = 0;
            var fetchedRoutes = [];
            var currentLocationMarker = null;
            var previewMarker = null;
            var previewPlace = null;
            var fareEditedByUser = defaultFareInput && parseFloat(defaultFareInput.value || '0') > 0;

            function toNumber(value) {
                var num = parseFloat(value);
                return Number.isFinite(num) ? num : null;
            }

            function escapeText(value) {
                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[char] || char;
                });
            }

            function setActiveTarget(target) {
                nextTarget = target === 'destination' ? 'destination' : 'pickup';
                Array.prototype.forEach.call(document.querySelectorAll('.map-target-btn'), function (btn) {
                    btn.classList.toggle('active', btn.getAttribute('data-map-target') === nextTarget);
                });
                if (!pickupMarker && nextTarget === 'destination') {
                    mapStepText.textContent = 'Titik B dipilih. Tetapkan Titik A dahulu jika ini laluan baharu.';
                    mapStepHint.textContent = 'Anda masih boleh pratonton tempat, tetapi laluan memerlukan kedua-dua titik.';
                }
            }

            function syncTargetButtons() {
                Array.prototype.forEach.call(document.querySelectorAll('.map-target-btn'), function (btn) {
                    btn.classList.toggle('active', btn.getAttribute('data-map-target') === nextTarget);
                });
            }

            function updateFields(target, lat, lng, placeName) {
                if (target === 'pickup') {
                    pickupLat.value = lat.toFixed(7);
                    pickupLng.value = lng.toFixed(7);
                    if (placeName && !pickupName.value.trim()) pickupName.value = placeName;
                } else {
                    destinationLat.value = lat.toFixed(7);
                    destinationLng.value = lng.toFixed(7);
                    if (placeName && !destinationName.value.trim()) destinationName.value = placeName;
                }
            }

            function getMarker(target) {
                return target === 'pickup' ? pickupMarker : destinationMarker;
            }

            function updateStepIndicator() {
                if (!pickupMarker) {
                    syncTargetButtons();
                    mapStepNumber.textContent = '1/3';
                    mapStepText.textContent = nextTarget === 'destination'
                        ? 'Sasaran Titik B dipilih.'
                        : 'Ketuk pada peta untuk tetapkan Titik A.';
                    mapStepHint.textContent = nextTarget === 'destination'
                        ? 'Anda boleh tetapkan Titik B dahulu, tetapi laluan tetap memerlukan Titik A sebelum pilihan muncul.'
                        : 'Carian boleh pratonton tempat dahulu. Sahkan hanya apabila pin sudah betul.';
                    mapResetBtn.classList.remove('show');
                    return;
                }

                if (pickupMarker && !destinationMarker) {
                    syncTargetButtons();
                    mapStepNumber.textContent = '2/3';
                    mapStepText.textContent = nextTarget === 'pickup'
                        ? 'Titik A ditetapkan. Anda boleh laraskan atau tukar ke Titik B.'
                        : 'Ketuk sekarang untuk tetapkan Titik B.';
                    mapStepHint.textContent = nextTarget === 'pickup'
                        ? 'Pratonton carian akan gantikan Titik A dan set semula Titik B.'
                        : 'Guna pratonton carian atau ketuk titik penghantaran yang tepat.';
                    mapResetBtn.classList.remove('show');
                    return;
                }

                syncTargetButtons();
                mapStepNumber.textContent = '3/3';
                mapStepText.textContent = 'Laluan lengkap. Semak dan pilih pilihan yang dikehendaki.';
                mapStepHint.textContent = 'Ketuk peta semula bila-bila masa untuk set semula dan mulakan Titik A baharu.';
                mapResetBtn.classList.add('show');
            }

            function getMarkerIcon(target) {
                var className = target === 'pickup' ? 'pickup-pin' : 'destination-pin';
                return L.divIcon({
                    className: '',
                    html: '<div class="' + className + '"></div>',
                    iconSize: [18, 18],
                    iconAnchor: [9, 9]
                });
            }

            function setMarker(target, lat, lng, placeName) {
                var marker = getMarker(target);

                if (!marker) {
                    marker = L.marker([lat, lng], { draggable: true, icon: getMarkerIcon(target) }).addTo(map);
                    marker.bindTooltip(target === 'pickup' ? 'Point A' : 'Point B', {
                        permanent: true,
                        direction: 'top',
                        offset: [0, -10],
                        className: 'map-point-tooltip'
                    });
                    marker.on('dragend', function (event) {
                        var pos = event.target.getLatLng();
                        updateFields(target, pos.lat, pos.lng);
                        reverseGeocode(pos.lat, pos.lng, function (resolvedName) {
                            if (target === 'pickup' && resolvedName) pickupName.value = resolvedName;
                            if (target === 'destination' && resolvedName) destinationName.value = resolvedName;
                            fetchRouteOptions();
                        });
                        fetchRouteOptions();
                    });

                    if (target === 'pickup') pickupMarker = marker;
                    if (target === 'destination') destinationMarker = marker;
                } else {
                    marker.setLatLng([lat, lng]);
                }

                updateFields(target, lat, lng, placeName);
                fetchRouteOptions();
            }

            function clearRoute() {
                routeLayers.forEach(function (layer) {
                    map.removeLayer(layer);
                });
                routeLayers = [];
            }

            function clearRouteOptions(message) {
                fetchedRoutes = [];
                selectedRouteIndex = 0;
                clearRoute();
                routeOptionsEl.innerHTML = '<div class="route-empty">' + message + '</div>';
                updateStepIndicator();
            }

            function formatDistance(meters) {
                return (meters / 1000).toFixed(1) + ' km';
            }

            function formatDuration(seconds) {
                var totalMinutes = Math.round(seconds / 60);
                if (totalMinutes < 60) return totalMinutes + ' min';
                var hours = Math.floor(totalMinutes / 60);
                var mins = totalMinutes % 60;
                return hours + 'h ' + mins + 'm';
            }

            function suggestedFare(distanceMeters, durationSeconds) {
                var distanceKm = (distanceMeters || 0) / 1000;
                var base = 2.5;
                var perKm = 1.1;
                var timeBuffer = (durationSeconds || 0) >= 1800 ? 1.2 : ((durationSeconds || 0) >= 900 ? 0.6 : 0.3);
                var fare = base + (distanceKm * perKm) + timeBuffer;
                return Math.max(2.5, Math.round(fare * 20) / 20);
            }

            function autoFillLowestSuggestedFare() {
                if (!defaultFareInput || fareEditedByUser || !fetchedRoutes.length) return;

                var lowestFare = fetchedRoutes.reduce(function (lowest, route) {
                    var fare = suggestedFare(route.distance, route.duration);
                    return lowest === null ? fare : Math.min(lowest, fare);
                }, null);

                if (lowestFare !== null && Number.isFinite(lowestFare)) {
                    defaultFareInput.value = lowestFare.toFixed(2);
                }
            }

            function fillFareFromRoute(route) {
                if (!defaultFareInput || !route) return;
                var fare = suggestedFare(route.distance, route.duration);
                if (Number.isFinite(fare)) {
                    defaultFareInput.value = fare.toFixed(2);
                }
            }

            function suggestionReason(route) {
                var distanceKm = ((route.distance || 0) / 1000);
                var minutes = Math.round((route.duration || 0) / 60);
                var timeBuffer = (route.duration || 0) >= 1800 ? 1.2 : ((route.duration || 0) >= 900 ? 0.6 : 0.3);

                return 'Sebab AI: tambang asas + jarak (' + distanceKm.toFixed(1) + ' km) + penimbal masa untuk ~' + minutes + ' min perjalanan. Masa mempengaruhi tambang, tetapi jarak tetap faktor utama.';
            }

            function summarizeRoads(route) {
                if (!route || !route.legs || !route.legs.length || !route.legs[0].steps) {
                    return 'Main route';
                }

                var names = [];
                route.legs[0].steps.forEach(function (step) {
                    var name = (step.name || '').trim();
                    if (name && names.indexOf(name) === -1) {
                        names.push(name);
                    }
                });

                return names.slice(0, 3).join(' • ') || 'Laluan utama';
            }

            function drawSelectedRoute() {
                if (!fetchedRoutes.length) {
                    clearRoute();
                    return;
                }

                var minDuration = Math.min.apply(null, fetchedRoutes.map(function (r) { return r.duration || 0; }));
                clearRoute();

                fetchedRoutes.forEach(function (route, index) {
                    var coordinates = route.geometry.coordinates.map(function (pair) {
                        return [pair[1], pair[0]];
                    });

                    var isPrimary = index === selectedRouteIndex;
                    var isSlower = (route.duration || 0) > (minDuration + 60);

                    var layer = L.polyline(coordinates, {
                        color: isPrimary ? '#2563eb' : (isSlower ? '#bfdbfe' : '#93c5fd'),
                        weight: isPrimary ? 4.5 : 3,
                        opacity: isPrimary ? 0.94 : 0.75,
                        dashArray: null
                    }).addTo(map);

                    routeLayers.push(layer);
                });
            }

            function renderRouteOptions() {
                if (!fetchedRoutes.length) {
                    clearRouteOptions('Tiada pilihan laluan dijumpai. Cuba titik lain.');
                    return;
                }

                routeOptionsEl.innerHTML = fetchedRoutes.map(function (route, index) {
                    var activeClass = index === selectedRouteIndex ? ' active' : '';
                    var roadSummary = summarizeRoads(route);
                    return ''
                        + '<button type="button" class="route-option-btn' + activeClass + '" data-route-index="' + index + '">'
                        + '  <div class="route-option-top">'
                        + '    <span class="route-option-name">Pilihan ' + (index + 1) + '</span>'
                        + '    <span class="route-option-meta">' + formatDistance(route.distance) + ' • ' + formatDuration(route.duration) + '</span>'
                        + '  </div>'
                        + '  <div class="route-option-road">' + roadSummary + '</div>'
                        + '  <div class="route-option-suggestion">Tambang dicadangkan RM ' + suggestedFare(route.distance, route.duration).toFixed(2) + '</div>'
                        + '  <div class="route-option-reason">' + suggestionReason(route) + '</div>'
                        + '</button>';
                }).join('');

                Array.prototype.forEach.call(routeOptionsEl.querySelectorAll('.route-option-btn'), function (btn) {
                    btn.addEventListener('click', function () {
                        selectedRouteIndex = parseInt(btn.getAttribute('data-route-index'), 10) || 0;
                        fillFareFromRoute(fetchedRoutes[selectedRouteIndex]);
                        renderRouteOptions();
                        drawSelectedRoute();
                    });
                });
            }

            function drawStraightLine() {
                if (!pickupMarker || !destinationMarker) {
                    clearRoute();
                    return;
                }

                clearRoute();
                routeLayers.push(L.polyline(
                    [pickupMarker.getLatLng(), destinationMarker.getLatLng()],
                    { color: '#2563eb', weight: 4, opacity: 0.9, dashArray: '8 8' }
                ).addTo(map));
            }

            function fetchRouteOptions() {
                if (!pickupMarker || !destinationMarker) {
                    clearRouteOptions('Tetapkan Titik A dan Titik B untuk melihat pilihan laluan dengan jarak dan ETA.');
                    return;
                }

                var p = pickupMarker.getLatLng();
                var d = destinationMarker.getLatLng();
                var url = 'https://router.project-osrm.org/route/v1/driving/'
                    + encodeURIComponent(p.lng) + ',' + encodeURIComponent(p.lat) + ';'
                    + encodeURIComponent(d.lng) + ',' + encodeURIComponent(d.lat)
                    + '?overview=full&geometries=geojson&steps=true&alternatives=true';

                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data || !data.routes || !data.routes.length) {
                            drawStraightLine();
                            routeOptionsEl.innerHTML = '<div class="route-empty">Perkhidmatan laluan tidak tersedia. Menunjukkan garis lurus sahaja.</div>';
                            return;
                        }
                        fetchedRoutes = data.routes.slice(0, 3);
                        selectedRouteIndex = 0;
                        renderRouteOptions();
                        drawSelectedRoute();
                        autoFillLowestSuggestedFare();
                    })
                    .catch(function () {
                        drawStraightLine();
                        routeOptionsEl.innerHTML = '<div class="route-empty">Gagal mendapatkan pilihan laluan. Menunjukkan garis lurus sahaja.</div>';
                    });
            }

            function clearMarker(target) {
                if (target === 'pickup' && pickupMarker) {
                    map.removeLayer(pickupMarker);
                    pickupMarker = null;
                    pickupLat.value = '';
                    pickupLng.value = '';
                    pickupName.value = '';
                }

                if (target === 'destination' && destinationMarker) {
                    map.removeLayer(destinationMarker);
                    destinationMarker = null;
                    destinationLat.value = '';
                    destinationLng.value = '';
                    destinationName.value = '';
                }

                updateStepIndicator();
            }

            function resetRouteSelection() {
                clearMarker('pickup');
                clearMarker('destination');
                clearSearchPreview();
                nextTarget = 'pickup';
                clearRouteOptions('Tetapkan Titik A dan Titik B untuk melihat pilihan laluan dengan jarak dan ETA.');
                updateStepIndicator();
            }

            function reverseGeocode(lat, lng, onDone) {
                var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data) return;
                        var text = data.display_name || '';
                        onDone(text);
                    })
                    .catch(function () {});
            }

            function searchPlace() {
                var query = (searchInput.value || '').trim();
                if (!query) return;

                fetchMalaysiaPlaces(query, 10)
                    .then(function (items) {
                        if (!items.length) return;
                        previewSearchResult(items[0], query);
                        renderSearchSuggestions(items);
                    })
                    .catch(function () {});
            }

            function previewSearchResult(item, fallbackText) {
                if (!item) return;
                var lat = parseFloat(item.lat);
                var lng = parseFloat(item.lon);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                previewPlace = {
                    lat: lat,
                    lng: lng,
                    name: item.display_name || fallbackText || '',
                    type: item.type || item.class || ''
                };

                if (previewMarker) {
                    map.removeLayer(previewMarker);
                }

                previewMarker = L.marker([lat, lng], {
                    draggable: true,
                    icon: L.divIcon({
                        className: '',
                        html: '<div class="preview-pin"></div>',
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    })
                }).addTo(map);
                previewMarker.bindTooltip('Pin pratonton', {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -12],
                    className: 'map-point-tooltip'
                });
                previewMarker.on('dragend', function (event) {
                    var pos = event.target.getLatLng();
                    previewPlace.lat = pos.lat;
                    previewPlace.lng = pos.lng;
                    reverseGeocode(pos.lat, pos.lng, function (resolvedName) {
                        if (resolvedName) {
                            previewPlace.name = resolvedName;
                            updatePreviewCard();
                        }
                    });
                    updatePreviewCard();
                });

                map.setView([lat, lng], Math.max(map.getZoom(), 16));
                updatePreviewCard();
            }

            function updatePreviewCard() {
                if (!previewPlace || !previewCard) return;
                var targetText = nextTarget === 'pickup' ? 'pickup Titik A' : 'penghantaran Titik B';
                var title = String(previewPlace.name || 'Lokasi dipilih').split(',')[0] || 'Lokasi dipilih';
                previewTitle.textContent = title;
                previewSub.textContent = String(previewPlace.name || '').trim() || 'Seret pin pratonton atau sahkan titik ini.';
                previewUseBtn.textContent = 'Guna sebagai ' + targetText;
                previewCard.classList.add('show');
            }

            function clearSearchPreview() {
                previewPlace = null;
                if (previewMarker) {
                    map.removeLayer(previewMarker);
                    previewMarker = null;
                }
                if (previewCard) {
                    previewCard.classList.remove('show');
                }
            }

            function applyPreviewAsTarget(target) {
                if (!previewPlace) return;

                if (target === 'pickup') {
                    clearMarker('pickup');
                }
                if (target === 'destination') {
                    clearMarker('destination');
                }

                setMarker(target, previewPlace.lat, previewPlace.lng, previewPlace.name || '');
                if (nextTarget === 'pickup') {
                    nextTarget = 'destination';
                } else {
                    nextTarget = 'pickup';
                }
                clearSearchPreview();
                updateStepIndicator();
            }

            function renderSearchSuggestions(items) {
                if (!searchSuggest) return;
                if (!items || !items.length) {
                    searchSuggest.classList.remove('show');
                    searchSuggest.innerHTML = '';
                    return;
                }

                searchSuggest.innerHTML = items.map(function (item, index) {
                    var display = String(item.display_name || '').trim();
                    var main = display.split(',')[0] || 'Pilih lokasi';
                    return (
                        '<button type=\"button\" class=\"map-search-suggest-btn\" data-index=\"' + index + '\">' +
                        '<span class=\"map-search-suggest-main\">' + escapeText(main) + '</span>' +
                        '<span class=\"map-search-suggest-sub\">' + escapeText(display) + '</span>' +
                        '</button>'
                    );
                }).join('');
                searchSuggest.classList.add('show');

                Array.from(searchSuggest.querySelectorAll('.map-search-suggest-btn')).forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var idx = parseInt(btn.getAttribute('data-index') || '-1', 10);
                        var picked = items[idx];
                        if (!picked) return;
                        searchInput.value = String(picked.display_name || '').trim();
                        renderSearchSuggestions([]);
                        previewSearchResult(picked, searchInput.value);
                    });
                });
            }

            function uniqueSearchItems(items) {
                var seen = {};
                return (items || []).filter(function (item) {
                    var lat = parseFloat(item.lat);
                    var lng = parseFloat(item.lon);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false;
                    var key = lat.toFixed(5) + ':' + lng.toFixed(5) + ':' + String(item.display_name || '').toLowerCase().slice(0, 48);
                    if (seen[key]) return false;
                    seen[key] = true;
                    return true;
                });
            }

            function rankMalaysiaResult(item, query) {
                var display = String(item.display_name || '').toLowerCase();
                var main = display.split(',')[0] || '';
                var q = String(query || '').toLowerCase();
                var score = 0;

                if (String(item.provider || '') === 'Photon') score += 10;
                if (display.indexOf('malaysia') !== -1) score += 40;
                if (display.indexOf('pahang') !== -1) score += 10;
                if (display.indexOf('pekan') !== -1) score += 8;
                if (main.indexOf(q) !== -1) score += 28;
                if (display.indexOf(q) !== -1) score += 18;
                if (['university', 'college', 'school', 'hospital', 'clinic', 'bus_stop', 'fuel', 'parking', 'residential', 'road', 'house', 'street', 'locality'].indexOf(String(item.type || '')) !== -1) score += 8;
                if (['amenity', 'highway', 'building', 'place', 'tourism', 'street'].indexOf(String(item.class || '')) !== -1) score += 6;
                if (/taman|kampung|jalan|lorong|persiaran|universiti|fakulti|sekolah|masjid|surau|klinik|hospital|terminal|stesen/.test(display)) score += 8;
                score += Math.max(0, Math.min(20, Number(item.importance || 0) * 20));

                return score;
            }

            function normalizePhotonFeature(feature) {
                var props = feature && feature.properties ? feature.properties : {};
                var coordinates = feature && feature.geometry && Array.isArray(feature.geometry.coordinates)
                    ? feature.geometry.coordinates
                    : [];
                var lng = parseFloat(coordinates[0]);
                var lat = parseFloat(coordinates[1]);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;

                var parts = [
                    props.name,
                    props.street,
                    props.district,
                    props.city,
                    props.county,
                    props.state,
                    props.country
                ].filter(function (part, index, items) {
                    return part && items.indexOf(part) === index;
                });

                return {
                    provider: 'Photon',
                    display_name: parts.join(', ') || props.name || 'Lokasi dipilih',
                    lat: String(lat),
                    lon: String(lng),
                    type: props.type || props.osm_value || 'place',
                    class: props.osm_key || 'place',
                    importance: 0.7,
                    address: {
                        road: props.street || '',
                        suburb: props.district || '',
                        city: props.city || '',
                        town: props.city || '',
                        county: props.county || '',
                        state: props.state || '',
                        country: props.country || ''
                    }
                };
            }

            function normalizeNominatimItem(item) {
                if (!item) return null;
                return {
                    provider: 'OSM',
                    display_name: item.display_name || '',
                    lat: item.lat,
                    lon: item.lon,
                    type: item.type || '',
                    class: item.class || '',
                    importance: item.importance || 0,
                    address: item.address || {}
                };
            }

            function fetchPhotonPlaces(query, limit) {
                var clean = String(query || '').trim();
                if (!clean) return Promise.resolve([]);

                var url = 'https://photon.komoot.io/api/?'
                    + 'q=' + encodeURIComponent(clean)
                    + '&limit=' + encodeURIComponent(limit || 10)
                    + '&lang=en'
                    + '&lat=3.139'
                    + '&lon=101.6869';

                return fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (payload) {
                        var features = payload && Array.isArray(payload.features) ? payload.features : [];
                        return features
                            .map(normalizePhotonFeature)
                            .filter(function (item) {
                                return item && String(item.display_name || '').toLowerCase().indexOf('malaysia') !== -1;
                            });
                    })
                    .catch(function () { return []; });
            }

            function fetchNominatimPlaces(query, limit) {
                var clean = String(query || '').trim();
                if (!clean) return Promise.resolve([]);

                var queries = [clean];
                if (!/malaysia|malaisie|malaysian/i.test(clean)) {
                    queries.push(clean + ', Malaysia');
                }

                var params = function (q) {
                    return 'format=jsonv2'
                        + '&addressdetails=1'
                        + '&namedetails=1'
                        + '&dedupe=1'
                        + '&countrycodes=my'
                        + '&bounded=1'
                        + '&viewbox=99.0,7.8,119.5,0.5'
                        + '&accept-language=en-MY,ms,en'
                        + '&limit=' + encodeURIComponent(limit || 10)
                        + '&q=' + encodeURIComponent(q);
                };

                return Promise.all(queries.map(function (q) {
                    var url = 'https://nominatim.openstreetmap.org/search?' + params(q);
                    return fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.ok ? r.json() : []; })
                        .catch(function () { return []; });
                })).then(function (groups) {
                    return groups.reduce(function (carry, group) {
                        return carry.concat(Array.isArray(group) ? group.map(normalizeNominatimItem).filter(Boolean) : []);
                    }, []);
                });
            }

            function fetchMalaysiaPlaces(query, limit) {
                var clean = String(query || '').trim();
                if (!clean) return Promise.resolve([]);

                return Promise.all([
                    fetchPhotonPlaces(clean, limit || 10),
                    fetchNominatimPlaces(clean, limit || 10)
                ]).then(function (groups) {
                    return uniqueSearchItems(groups.reduce(function (carry, group) {
                        return carry.concat(Array.isArray(group) ? group : []);
                    }, []))
                        .sort(function (a, b) {
                            return rankMalaysiaResult(b, clean) - rankMalaysiaResult(a, clean);
                        })
                        .slice(0, limit || 10);
                });
            }

            var searchDebounceTimer = null;
            function fetchSearchSuggestions() {
                var query = (searchInput.value || '').trim();
                if (query.length < 1) {
                    renderSearchSuggestions([]);
                    return;
                }

                fetchMalaysiaPlaces(query, 10)
                    .then(function (items) {
                        renderSearchSuggestions(Array.isArray(items) ? items : []);
                    })
                    .catch(function () {
                        renderSearchSuggestions([]);
                    });
            }

            function goToCurrentLocation() {
                if (!navigator.geolocation) return;
                locateBtn.disabled = true;
                locateBtn.classList.add('is-loading');
                locateBtn.innerHTML = '<i class="fa-solid fa-spinner" aria-hidden="true"></i>';

                navigator.geolocation.getCurrentPosition(function (position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    map.setView([lat, lng], 16);

                    if (currentLocationMarker) {
                        map.removeLayer(currentLocationMarker);
                    }

                    currentLocationMarker = L.circleMarker([lat, lng], {
                        radius: 7,
                        color: '#0f172a',
                        weight: 2,
                        fillColor: '#60a5fa',
                        fillOpacity: 0.95
                    }).addTo(map);

                    locateBtn.disabled = false;
                    locateBtn.classList.remove('is-loading');
                    locateBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>';
                }, function () {
                    locateBtn.disabled = false;
                    locateBtn.classList.remove('is-loading');
                    locateBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>';
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                });
            }

            map.on('click', function (event) {
                var assignedTarget = nextTarget;
                clearSearchPreview();

                if (assignedTarget === 'pickup') {
                    clearMarker('pickup');
                    setMarker('pickup', event.latlng.lat, event.latlng.lng);
                    nextTarget = 'destination';
                } else {
                    clearMarker('destination');
                    setMarker('destination', event.latlng.lat, event.latlng.lng);
                    nextTarget = 'pickup';
                }

                reverseGeocode(event.latlng.lat, event.latlng.lng, function (resolvedName) {
                    if (assignedTarget === 'pickup' && resolvedName) pickupName.value = resolvedName;
                    if (assignedTarget === 'destination' && resolvedName) destinationName.value = resolvedName;
                });
                updateStepIndicator();
            });
            Array.prototype.forEach.call(document.querySelectorAll('.map-target-btn'), function (btn) {
                btn.addEventListener('click', function () {
                    setActiveTarget(btn.getAttribute('data-map-target'));
                    updatePreviewCard();
                    updateStepIndicator();
                });
            });
            searchBtn.addEventListener('click', searchPlace);
            locateBtn.addEventListener('click', goToCurrentLocation);
            mapResetBtn.addEventListener('click', resetRouteSelection);
            if (defaultFareInput) {
                defaultFareInput.addEventListener('input', function () {
                    fareEditedByUser = true;
                });
            }
            previewUseBtn.addEventListener('click', function () {
                applyPreviewAsTarget(nextTarget);
            });
            previewCloseBtn.addEventListener('click', clearSearchPreview);
            searchInput.addEventListener('input', function () {
                if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(fetchSearchSuggestions, 220);
            });
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchPlace();
                }
            });
            document.addEventListener('click', function (e) {
                if (!searchSuggest) return;
                var inWrap = e.target && e.target.closest ? e.target.closest('.map-search-input-wrap') : null;
                if (!inWrap) {
                    renderSearchSuggestions([]);
                }
            });

            var existingPickupLat = toNumber(pickupLat.value);
            var existingPickupLng = toNumber(pickupLng.value);
            var existingDestLat = toNumber(destinationLat.value);
            var existingDestLng = toNumber(destinationLng.value);

            if (existingPickupLat !== null && existingPickupLng !== null) {
                setMarker('pickup', existingPickupLat, existingPickupLng);
                map.setView([existingPickupLat, existingPickupLng], 14);
                nextTarget = 'destination';
            }

            if (existingDestLat !== null && existingDestLng !== null) {
                setMarker('destination', existingDestLat, existingDestLng);
                if (existingPickupLat === null || existingPickupLng === null) {
                    map.setView([existingDestLat, existingDestLng], 14);
                }
                nextTarget = 'pickup';
            }

            if (existingPickupLat !== null && existingPickupLng !== null && existingDestLat !== null && existingDestLng !== null) {
                fetchRouteOptions();
            }

            updateStepIndicator();

            if (existingPickupLat === null && existingPickupLng === null && existingDestLat === null && existingDestLng === null) {
                setTimeout(goToCurrentLocation, 350);
            }
        })();
    </script>
@endsection
