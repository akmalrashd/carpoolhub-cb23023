@extends('layouts.app')

@section('content')
    <style>
        .route-page {
            display: grid;
            gap: 14px;
        }

        .route-title-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .route-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 28px;
            color: #0f172a;
            line-height: 1.08;
        }

        .route-subtitle {
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

        .add-route-btn {
            text-decoration: none;
            border-radius: 10px;
            padding: 9px 13px;
            background: #0f172a;
            border: 1px solid #0f172a;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .route-data-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }

        .route-toolbar {
            margin-bottom: 12px;
        }

        .route-search {
            position: relative;
            max-width: 480px;
        }

        .route-search i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 12px;
            pointer-events: none;
        }

        .route-search input {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #0f172a;
            padding: 9px 12px 9px 34px;
            font-size: 13px;
            outline: none;
            transition: border-color .15s ease, background-color .15s ease;
        }

        .route-search input:focus {
            border-color: #94a3b8;
            background: #fff;
        }

        .route-mobile-list {
            display: grid;
            gap: 12px;
        }

        .route-mobile-item {
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #fff;
            padding: 14px;
            display: grid;
            gap: 10px;
            box-shadow: 0 5px 14px rgba(15, 23, 42, 0.04);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .route-mobile-item:hover {
            transform: translateY(-2px);
            border-color: #cbd5e1;
            box-shadow: 0 14px 24px rgba(15, 23, 42, 0.08);
        }

        .route-mobile-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: flex-start;
            gap: 12px;
        }

        .route-head-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .route-edit-chip {
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 9px;
            text-decoration: none;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #0f172a;
            white-space: nowrap;
        }

        .route-mobile-head .route-edit-chip {
            padding: 6px 10px;
        }

        .route-item-title {
            margin: 0;
            font-size: 16px;
            line-height: 1.3;
            color: #0f172a;
            font-weight: 700;
            min-width: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .route-points {
            display: grid;
            gap: 7px;
            padding: 8px 10px;
            border: 1px solid #edf2f7;
            border-radius: 10px;
            background: #fbfdff;
        }

        .route-line {
            font-size: 12px;
            color: #64748b;
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr);
            gap: 8px;
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 0;
            padding: 0;
        }

        .route-line + .route-line {
            padding-top: 7px;
            border-top: 1px solid #eef2f7;
        }

        .route-line strong {
            color: #334155;
            font-weight: 700;
        }

        .point-dot {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            border: 1px solid transparent;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
        }

        .point-dot.pickup {
            color: #15803d;
            background: #dcfce7;
            border-color: #86efac;
        }

        .point-dot.destination {
            color: #b45309;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .route-line-text {
            min-width: 0;
            word-break: normal;
        }

        .route-line-text small {
            display: block;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .route-line-text strong {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .route-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: nowrap;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        .route-price {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .fare-card {
            border: 0;
            background: transparent;
            border-radius: 0;
            padding: 0;
            display: grid;
            gap: 1px;
            justify-items: end;
            white-space: nowrap;
        }

        .fare-head {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .fare-head i {
            display: none;
        }

        .fare-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .fare-value {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .status-pill {
            padding: 4px 9px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            font-size: 12px;
            font-weight: 700;
        }

        .status-active {
            color: #166534;
            border-color: #86efac;
            background: #f0fdf4;
        }

        .status-inactive {
            color: #b91c1c;
            border-color: #fecaca;
            background: #fef2f2;
        }

        .route-actions {
            display: flex;
            gap: 7px;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }

        .route-mobile-head .status-toggle {
            margin-left: 0;
        }

        .toggle-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .toggle-track {
            width: 42px;
            height: 24px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #e2e8f0;
            display: inline-flex;
            align-items: center;
            padding: 2px;
            transition: background-color .2s ease, border-color .2s ease;
            cursor: pointer;
        }

        .toggle-thumb {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.18);
            transition: transform .2s ease;
        }

        .toggle-input:checked + .toggle-track {
            background: #16a34a;
            border-color: #15803d;
        }

        .toggle-input:checked + .toggle-track .toggle-thumb {
            transform: translateX(18px);
        }

        .status-text {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            min-width: 52px;
        }

        .action-link,
        .action-btn {
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            padding: 7px 9px;
            text-decoration: none;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            cursor: pointer;
        }

        .action-btn-danger {
            border-color: #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        @media (max-width: 520px) {
            .route-mobile-head {
                grid-template-columns: minmax(0, 1fr);
            }

            .fare-card {
                justify-items: start;
                grid-template-columns: auto auto;
                align-items: baseline;
                gap: 6px;
            }

            .route-item-row {
                align-items: stretch;
                flex-direction: column;
            }

            .route-head-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
            }

            .route-head-actions form,
            .route-head-actions .route-edit-chip {
                width: 100%;
            }

            .route-head-actions .route-edit-chip,
            .route-head-actions button {
                display: inline-flex;
                justify-content: center;
            }
        }

        .route-table-wrap {
            display: none;
            overflow-x: hidden;
        }

        .route-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .route-table th,
        .route-table td {
            padding: 11px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
        }

        .route-table th {
            font-size: 12px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .route-table td {
            word-break: break-word;
        }

        .route-table td:nth-child(1),
        .route-table th:nth-child(1) {
            width: 18%;
        }

        .route-table td:nth-child(2),
        .route-table th:nth-child(2) {
            width: 27%;
        }

        .route-table td:nth-child(3),
        .route-table th:nth-child(3) {
            width: 27%;
        }

        .route-table td:nth-child(4),
        .route-table th:nth-child(4) {
            width: 10%;
        }

        .route-table td:nth-child(5),
        .route-table th:nth-child(5) {
            width: 8%;
        }

        .route-table td:nth-child(6),
        .route-table th:nth-child(6) {
            width: 10%;
            text-align: right;
        }

        .route-table tbody tr {
            transition: background-color .18s ease;
        }

        .route-table tbody tr:hover {
            background: #f8fafc;
        }

        .route-table .route-line {
            padding: 0;
            border-radius: 0;
            max-width: 100%;
            border: 0;
            background: transparent;
        }

        .route-table .route-line strong {
            line-height: 1.35;
            white-space: normal;
        }

        .desktop-fare-card {
            border: 0;
            background: transparent;
            border-radius: 0;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            text-align: left;
        }

        .desktop-fare-value {
            font-size: 16px;
            line-height: 1.2;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .route-table .status-toggle {
            margin-left: 0;
        }

        .desktop-status-col {
            text-align: left !important;
        }

        .desktop-actions-col .route-actions {
            justify-content: flex-end;
        }

        .desktop-edit-btn {
            background: #f8fafc;
            border-color: #cbd5e1;
            padding: 8px 12px;
        }

        .route-name-main {
            font-weight: 700;
            color: #0f172a;
            display: block;
            margin-bottom: 2px;
        }

        .route-name-sub {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
        }

        .empty-state {
            border: 1px dashed #dbe2ea;
            border-radius: 12px;
            background: #f8fafc;
            padding: 48px 24px;
            color: #64748b;
            font-size: 14px;
            text-align: center;
        }
        .empty-state-icon { font-size: 32px; color: #cbd5e1; margin-bottom: 12px; display: block; }
        .empty-state-title { font-size: 15px; font-weight: 700; color: #475569; margin: 0 0 4px; }
        .empty-state-copy { margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.5; }

        .pagination-wrap {
            margin-top: 12px;
        }

        @media (min-width: 1024px) {
            .route-mobile-list {
                display: none;
            }

            .route-table-wrap {
                display: block;
            }

            .route-data-card {
                padding: 14px;
            }
        }
    </style>

    <div class="route-page">
        <section class="route-title-card">
            <div>
                <h1 class="route-title">Laluan Tersimpan</h1>
                <p class="route-subtitle">Urus laluan Titik A dan Titik B yang boleh diguna semula.</p>
            </div>
            <a href="{{ route('saved-routes.create') }}" class="add-route-btn">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Laluan</span>
            </a>
        </section>

        <section class="route-info-note">
            <i class="fa-solid fa-circle-info" style="margin-right:6px;"></i>
            Simpan laluan sehala sahaja — tiada perlu tambah kedua-dua arah.
            Tambang yang ditunjukkan adalah untuk perjalanan sehala.
            Semasa mencipta trip, pilih Sehala atau Dua Hala dan sistem akan kira jumlah tambang dua hala secara automatik.
        </section>

        <section class="route-data-card">
            @if(!$savedRoutes->isEmpty())
                <div class="route-toolbar">
                    <div class="route-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input id="routeSearchInput" type="search" placeholder="Cari nama laluan..." autocomplete="off">
                    </div>
                </div>
            @endif

            @if($savedRoutes->isEmpty())
                <div class="empty-state">
                    <i class="fa-solid fa-route empty-state-icon"></i>
                    <p class="empty-state-title">Tiada laluan tersimpan</p>
                    <p class="empty-state-copy">Tambah laluan pertama anda untuk mula merancang trip.</p>
                </div>
            @else
                <div class="route-mobile-list">
                    @foreach($savedRoutes as $savedRoute)
                        <article class="route-mobile-item" data-route-name="{{ strtolower($savedRoute->route_name ?: 'Laluan Tidak Bertajuk') }}">
                            <div class="route-mobile-head">
                                <h2 class="route-item-title">{{ $savedRoute->route_name ?: 'Laluan Tidak Bertajuk' }}</h2>
                                <div class="fare-card">
                                    <span class="fare-head">
                                        <i class="fa-solid fa-coins"></i>
                                        <span class="fare-label">Tambang</span>
                                    </span>
                                    <span class="fare-value">RM {{ number_format((float) $savedRoute->default_fare, 2) }}</span>
                                </div>
                            </div>
                            <div class="route-points">
                                <div class="route-line">
                                    <span class="point-dot pickup">A</span>
                                    <div class="route-line-text">
                                        <small>Titik A</small>
                                        <strong>{{ $savedRoute->point_a_name }}</strong>
                                    </div>
                                </div>
                                <div class="route-line">
                                    <span class="point-dot destination">B</span>
                                    <div class="route-line-text">
                                        <small>Titik B</small>
                                        <strong>{{ $savedRoute->point_b_name }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="route-item-row">
                                <form method="POST" action="{{ route('saved-routes.toggle-status', $savedRoute) }}" class="status-toggle">
                                    @csrf
                                    @method('PATCH')
                                    <input
                                        id="toggle-mobile-{{ $savedRoute->id }}"
                                        type="checkbox"
                                        class="toggle-input"
                                        name="is_active"
                                        value="1"
                                        {{ $savedRoute->is_active ? 'checked' : '' }}
                                        onchange="this.form.submit()"
                                    >
                                    <label for="toggle-mobile-{{ $savedRoute->id }}" class="toggle-track">
                                        <span class="toggle-thumb"></span>
                                    </label>
                                    <span class="status-text">{{ $savedRoute->is_active ? 'Hidup' : 'Mati' }}</span>
                                </form>
                                <div class="route-head-actions">
                                    <a href="{{ route('saved-routes.edit', $savedRoute) }}" class="route-edit-chip">Sunting</a>
                                    <form method="POST" action="{{ route('saved-routes.destroy', $savedRoute) }}" onsubmit="return confirm('Padam laluan ini? Semua trip yang menggunakan laluan ini akan turut dipadam.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="route-edit-chip action-btn-danger">Padam</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="route-table-wrap">
                    <table class="route-table">
                        <thead>
                        <tr>
                            <th>Laluan</th>
                            <th>Titik A</th>
                            <th>Titik B</th>
                            <th>Tambang</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                        </thead>
                        <tbody id="routeTableBody">
                        @foreach($savedRoutes as $savedRoute)
                            <tr data-route-name="{{ strtolower($savedRoute->route_name ?: 'Laluan Tidak Bertajuk') }}">
                                <td>
                                    <span class="route-name-main">{{ $savedRoute->route_name ?: 'Laluan Tidak Bertajuk' }}</span>
                                    <span class="route-name-sub">Laluan tersimpan</span>
                                </td>
                                <td>
                                    <div class="route-line">
                                        <span class="point-dot pickup">A</span>
                                        <div class="route-line-text"><strong>{{ $savedRoute->point_a_name }}</strong></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="route-line">
                                        <span class="point-dot destination">B</span>
                                        <div class="route-line-text"><strong>{{ $savedRoute->point_b_name }}</strong></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="desktop-fare-card">
                                        <span class="desktop-fare-value">RM {{ number_format((float) $savedRoute->default_fare, 2) }}</span>
                                    </div>
                                </td>
                                <td class="desktop-status-col">
                                    <form method="POST" action="{{ route('saved-routes.toggle-status', $savedRoute) }}" class="status-toggle">
                                        @csrf
                                        @method('PATCH')
                                        <input
                                            id="toggle-desktop-{{ $savedRoute->id }}"
                                            type="checkbox"
                                            class="toggle-input"
                                            name="is_active"
                                            value="1"
                                            {{ $savedRoute->is_active ? 'checked' : '' }}
                                            onchange="this.form.submit()"
                                        >
                                        <label for="toggle-desktop-{{ $savedRoute->id }}" class="toggle-track">
                                            <span class="toggle-thumb"></span>
                                        </label>
                                        <span class="status-text">{{ $savedRoute->is_active ? 'Hidup' : 'Mati' }}</span>
                                    </form>
                                </td>
                                <td class="desktop-actions-col">
                                    <div class="route-actions">
                                        <a href="{{ route('saved-routes.edit', $savedRoute) }}" class="action-link desktop-edit-btn">Sunting</a>
                                        <form method="POST" action="{{ route('saved-routes.destroy', $savedRoute) }}" onsubmit="return confirm('Padam laluan ini? Semua trip yang menggunakan laluan ini akan turut dipadam.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-link action-btn-danger">Padam</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div id="routeSearchEmpty" class="empty-state" style="display:none; margin-top:10px; padding: 32px 24px;">
                    <i class="fa-solid fa-magnifying-glass empty-state-icon"></i>
                    <p class="empty-state-title">Tiada laluan ditemui</p>
                    <p class="empty-state-copy">Cuba cari dengan nama laluan yang lain.</p>
                </div>
            @endif
        </section>
    </div>

    <div class="pagination-wrap">
        {{ $savedRoutes->links() }}
    </div>

    <script>
        (function () {
            var searchInput = document.getElementById('routeSearchInput');
            if (!searchInput) return;

            var mobileItems = Array.prototype.slice.call(document.querySelectorAll('.route-mobile-item[data-route-name]'));
            var desktopRows = Array.prototype.slice.call(document.querySelectorAll('#routeTableBody tr[data-route-name]'));
            var emptyState = document.getElementById('routeSearchEmpty');

            function normalize(text) {
                return (text || '').toLowerCase().trim();
            }

            function applyFilter() {
                var query = normalize(searchInput.value);
                var visibleCount = 0;

                desktopRows.forEach(function (row) {
                    var routeName = normalize(row.getAttribute('data-route-name'));
                    var matched = routeName.indexOf(query) !== -1;
                    row.style.display = matched ? '' : 'none';
                    if (matched) visibleCount++;
                });

                mobileItems.forEach(function (item) {
                    var routeName = normalize(item.getAttribute('data-route-name'));
                    var matched = routeName.indexOf(query) !== -1;
                    item.style.display = matched ? '' : 'none';
                });

                if (emptyState) {
                    emptyState.style.display = visibleCount ? 'none' : 'block';
                }
            }

            searchInput.addEventListener('input', applyFilter);
        })();
    </script>
@endsection

