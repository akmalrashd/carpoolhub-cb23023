@extends('layouts.app')

@section('content')
    <style>
        .route-page {
            display: grid;
            gap: 12px;
        }

        .route-title-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .route-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 30px;
            color: #0f172a;
            line-height: 1.05;
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
            padding: 10px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }

        .route-toolbar {
            margin-bottom: 10px;
        }

        .route-search {
            position: relative;
            max-width: 360px;
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
            gap: 8px;
        }

        .route-mobile-item {
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #fff;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .route-mobile-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .route-head-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            flex: 1;
        }

        .route-points {
            display: grid;
            gap: 6px;
        }

        .route-line {
            font-size: 12px;
            color: #64748b;
            display: flex;
            gap: 7px;
            align-items: flex-start;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 9px;
        }

        .route-line strong {
            color: #334155;
            font-weight: 700;
        }

        .point-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            margin-top: 4px;
            flex-shrink: 0;
        }

        .point-dot.pickup {
            background: #16a34a;
        }

        .point-dot.destination {
            background: #dc2626;
        }

        .route-line-text {
            min-width: 0;
            word-break: break-word;
        }

        .route-line-text small {
            display: block;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 1px;
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
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 10px;
            padding: 8px 9px;
            display: grid;
            gap: 2px;
        }

        .fare-head {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .fare-head i {
            font-size: 10px;
            color: #1d4ed8;
            width: 12px;
            text-align: center;
            flex-shrink: 0;
        }

        .fare-label {
            font-size: 11px;
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .fare-value {
            font-size: 22px;
            font-weight: 700;
            color: #1e3a8a;
            line-height: 1.1;
            padding-left: 18px;
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

        .route-table-wrap {
            display: none;
            overflow: auto;
        }

        .route-table {
            width: 100%;
            border-collapse: collapse;
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
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .route-table td:last-child,
        .route-table th:last-child {
            text-align: right;
        }

        .route-table td:nth-child(4),
        .route-table th:nth-child(4) {
            width: 112px;
        }

        .route-table td:nth-child(5),
        .route-table th:nth-child(5) {
            width: 124px;
        }

        .route-table td:nth-child(6),
        .route-table th:nth-child(6) {
            width: 92px;
        }

        .route-table tbody tr {
            transition: background-color .18s ease;
        }

        .route-table tbody tr:hover {
            background: #f8fafc;
        }

        .route-table .route-line {
            padding: 10px 12px;
            border-radius: 12px;
            max-width: 100%;
        }

        .route-table .route-line strong {
            line-height: 1.35;
        }

        .desktop-fare-card {
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 10px;
            padding: 8px 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 96px;
            text-align: left;
        }

        .desktop-fare-value {
            font-size: 16px;
            line-height: 1.2;
            font-weight: 700;
            color: #92400e;
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
            padding: 14px;
            color: #64748b;
            font-size: 14px;
            text-align: center;
        }

        .pagination-wrap {
            margin-top: 12px;
        }

        @media (min-width: 1024px) {
            .route-mobile-list {
                display: none;
            }

            .route-table-wrap {
                display: block;
                border: 0;
                border-radius: 0;
                padding: 0;
                background: transparent;
            }

            .route-data-card {
                padding: 14px;
            }

            .route-table {
                border-collapse: collapse;
                border-spacing: 0;
            }

            .route-table thead th {
                border-bottom: 1px solid #e2e8f0;
                background: transparent;
                color: #64748b;
            }

            .route-table thead th:first-child {
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
                padding-left: 11px;
            }

            .route-table thead th:last-child {
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
                padding-right: 11px;
            }

            .route-table tbody tr td {
                background: transparent;
                border-top: 0;
                border-bottom: 1px solid #e2e8f0;
                border-left: 0 !important;
                border-right: 0 !important;
                padding-top: 11px;
                padding-bottom: 11px;
            }

            .route-table tbody tr td:first-child {
                border-left: 0;
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
                padding-left: 11px;
            }

            .route-table tbody tr td:last-child {
                border-right: 0;
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
                padding-right: 11px;
            }

            .route-table tbody tr:hover td {
                background: #f8fafc;
                border-color: #e2e8f0;
            }

            .route-table .route-line {
                background: transparent;
                border: 0;
                padding: 0;
                border-radius: 0;
            }

            .desktop-fare-card {
                border: 0;
                background: transparent;
                border-radius: 0;
                padding: 0;
                min-width: 0;
            }

            .desktop-fare-value {
                color: #0f172a;
            }
        }
    </style>

    <div class="route-page">
        <section class="route-title-card">
            <div>
                <h1 class="route-title">Saved Routes</h1>
                <p class="route-subtitle">Manage your reusable Point A and Point B routes.</p>
            </div>
            <a href="{{ route('saved-routes.create') }}" class="add-route-btn">
                <i class="fa-solid fa-plus"></i>
                <span>Add Route</span>
            </a>
        </section>

        <section class="route-info-note">
            Save one-way route only. No need to add both directions.
            Fare here is for one-way trip.
            During Create Trip, choose One Way or Two Way and system will auto calculate two-way total if selected.
        </section>

        <section class="route-data-card">
            @if(!$savedRoutes->isEmpty())
                <div class="route-toolbar">
                    <div class="route-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input id="routeSearchInput" type="search" placeholder="Search route name..." autocomplete="off">
                    </div>
                </div>
            @endif

            @if($savedRoutes->isEmpty())
                <div class="empty-state">No saved routes yet. Add your first route to get started.</div>
            @else
                <div class="route-mobile-list">
                    @foreach($savedRoutes as $savedRoute)
                        <article class="route-mobile-item" data-route-name="{{ strtolower($savedRoute->route_name ?: 'Untitled Route') }}">
                            <div class="route-mobile-head">
                                <h2 class="route-item-title">{{ $savedRoute->route_name ?: 'Untitled Route' }}</h2>
                                <div class="route-head-actions">
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
                                    </form>
                                    <a href="{{ route('saved-routes.edit', $savedRoute) }}" class="route-edit-chip">Edit</a>
                                    <form method="POST" action="{{ route('saved-routes.destroy', $savedRoute) }}" onsubmit="return confirm('Delete this route? All trips using this route will be deleted too.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="route-edit-chip action-btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <div class="route-points">
                                <div class="route-line">
                                    <span class="point-dot pickup"></span>
                                    <div class="route-line-text">
                                        <small>Point A</small>
                                        <strong>{{ $savedRoute->point_a_name }}</strong>
                                    </div>
                                </div>
                                <div class="route-line">
                                    <span class="point-dot destination"></span>
                                    <div class="route-line-text">
                                        <small>Point B</small>
                                        <strong>{{ $savedRoute->point_b_name }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="fare-card">
                                <span class="fare-head">
                                    <i class="fa-solid fa-coins"></i>
                                    <span class="fare-label">Fare</span>
                                </span>
                                <span class="fare-value">RM {{ number_format((float) $savedRoute->default_fare, 2) }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="route-table-wrap">
                    <table class="route-table">
                        <thead>
                        <tr>
                            <th>Route</th>
                            <th>Point A</th>
                            <th>Point B</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="routeTableBody">
                        @foreach($savedRoutes as $savedRoute)
                            <tr data-route-name="{{ strtolower($savedRoute->route_name ?: 'Untitled Route') }}">
                                <td>
                                    <span class="route-name-main">{{ $savedRoute->route_name ?: 'Untitled Route' }}</span>
                                    <span class="route-name-sub">Saved route</span>
                                </td>
                                <td>
                                    <div class="route-line">
                                        <span class="point-dot pickup"></span>
                                        <div class="route-line-text"><strong>{{ $savedRoute->point_a_name }}</strong></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="route-line">
                                        <span class="point-dot destination"></span>
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
                                        <span class="status-text">{{ $savedRoute->is_active ? 'On' : 'Off' }}</span>
                                    </form>
                                </td>
                                <td class="desktop-actions-col">
                                    <div class="route-actions">
                                        <a href="{{ route('saved-routes.edit', $savedRoute) }}" class="action-link desktop-edit-btn">Edit</a>
                                        <form method="POST" action="{{ route('saved-routes.destroy', $savedRoute) }}" onsubmit="return confirm('Delete this route? All trips using this route will be deleted too.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-link action-btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div id="routeSearchEmpty" class="empty-state" style="display:none; margin-top:10px;">
                    No route found for your search.
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

