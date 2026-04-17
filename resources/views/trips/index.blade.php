@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <style>
        .trips-page {
            display: grid;
            gap: 12px;
        }

        .trips-title-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .trips-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 30px;
            line-height: 1.05;
            color: #0f172a;
        }

        .trips-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .create-trip-btn {
            text-decoration: none;
            border-radius: 10px;
            padding: 9px 13px;
            background: #facc15;
            border: 1px solid #eab308;
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            box-shadow: 0 4px 12px rgba(250, 204, 21, 0.28);
            transition: transform .16s ease, box-shadow .16s ease, filter .16s ease, background-color .16s ease;
            animation: createTripFloat 2.4s ease-in-out infinite;
        }
        .create-trip-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(250, 204, 21, 0.35);
            filter: saturate(1.02);
        }
        .create-trip-btn:active {
            transform: translateY(0) scale(.98);
            box-shadow: 0 3px 8px rgba(250, 204, 21, 0.25);
        }
        @keyframes createTripFloat {
            0%, 100% {
                transform: translateY(0);
                box-shadow: 0 4px 12px rgba(250, 204, 21, 0.28);
            }
            50% {
                transform: translateY(-1px);
                box-shadow: 0 8px 16px rgba(250, 204, 21, 0.34);
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .create-trip-btn { animation: none; }
        }

        .trips-tools-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .trips-filter-row {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 8px;
            align-items: end;
        }

        .trips-filter-field {
            display: grid;
            gap: 4px;
        }

        .trips-filter-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .trips-filter-input {
            width: 100%;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #f8fafc;
            color: #0f172a;
            padding: 8px 10px;
            font-size: 13px;
            outline: none;
        }

        .trips-filter-actions {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .trips-filter-btn {
            border: 1px solid #dbe2ea;
            border-radius: 9px;
            background: #fff;
            color: #0f172a;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .trips-filter-btn.primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }

        .trips-tools-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .trips-tool-item {
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            border-radius: 12px;
            padding: 8px 10px;
            display: grid;
            gap: 2px;
        }

        .trips-tool-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .trips-tool-value {
            font-size: 16px;
            color: #0f172a;
            font-weight: 700;
            line-height: 1.15;
        }

        .trips-tools-hint {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.4;
        }

        .trips-data-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 10px;
        }

        .trips-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            margin: 0 0 10px;
        }

        .trips-list-title {
            margin: 0;
            font-size: 16px;
            color: #0f172a;
            font-weight: 700;
        }

        .trips-list-subtitle {
            margin: 3px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .trip-mobile-list {
            display: grid;
            gap: 8px;
        }

        .trip-mobile-item {
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #fff;
            padding: 12px;
            display: grid;
            gap: 10px;
        }
        .open-trip-card { cursor: pointer; }
        .trip-focus-highlight {
            border-color: #facc15 !important;
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.38), 0 10px 18px rgba(15, 23, 42, 0.08);
            transition: box-shadow .25s ease, border-color .25s ease;
        }
        .trip-table tr.trip-focus-highlight td {
            background: #fffbeb;
        }

        .trip-mobile-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .trip-route-title {
            margin: 0;
            font-size: 17px;
            line-height: 1.3;
            color: #0f172a;
            font-weight: 700;
        }

        .trip-route-sub {
            margin: 3px 0 0;
            font-size: 12px;
            color: #64748b;
            line-height: 1.35;
        }
        .trip-meta-stack {
            margin-top: 4px;
            display: grid;
            gap: 2px;
        }
        .trip-meta-inline {
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            color: #64748b;
            font-size: 12px;
            line-height: 1.3;
        }
        .trip-meta-inline-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-width: 0;
            white-space: nowrap;
        }
        .trip-meta-inline-item i {
            color: #92400e;
            font-size: 10px;
            flex: 0 0 auto;
        }

        .trip-meta-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .trip-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #475569;
            padding: 4px 9px;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .trip-meta-pill i {
            font-size: 10px;
        }

        .trip-meta-pill.driver {
            color: #1e293b;
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .trip-meta-pill.mode {
            color: #92400e;
            border-color: #fde68a;
            background: #fffbeb;
        }

        .trip-meta-pill.route {
            color: #0f766e;
            border-color: #99f6e4;
            background: #f0fdfa;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            padding: 4px 9px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-draft { color: #475569; border-color: #cbd5e1; background: #f8fafc; }
        .status-scheduled { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
        .status-recorded { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .status-cancelled { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
        .status-confirmed { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
        .status-completed { color: #166534; border-color: #86efac; background: #f0fdf4; }

        .trip-detail-grid {
            display: grid;
            gap: 6px;
        }

        .trip-detail-line {
            font-size: 13px;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .trip-detail-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .trip-detail-value {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
        }

        .trip-bottom-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        .trip-fare-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 10px;
            padding: 8px 10px;
            display: grid;
            gap: 2px;
        }

        .trip-fare-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .trip-fare-value {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.15;
        }

        .trip-actions {
            display: inline-flex;
            gap: 7px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .trip-action-btn {
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            padding: 7px 9px;
            text-decoration: none;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
        }
        .trip-action-btn.disabled {
            background: #f1f5f9;
            border-color: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            pointer-events: none;
        }

        .trip-action-form {
            margin: 0;
        }

        .trip-action-btn-danger {
            border-color: #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        .trip-table-wrap {
            display: none;
            overflow-x: hidden;
        }

        .trip-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .trip-table th,
        .trip-table td {
            padding: 11px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
        }

        .trip-table th {
            font-size: 12px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .trip-table td {
            word-break: break-word;
        }

        .trip-table td:nth-child(1),
        .trip-table th:nth-child(1) {
            width: 16%;
        }

        .trip-table td:nth-child(2),
        .trip-table th:nth-child(2) {
            width: 34%;
        }

        .trip-table td:nth-child(3),
        .trip-table th:nth-child(3) {
            width: 12%;
        }

        .trip-table td:nth-child(4),
        .trip-table th:nth-child(4) {
            width: 10%;
        }

        .trip-table td:nth-child(5),
        .trip-table th:nth-child(5) {
            width: 10%;
            text-align: right;
        }

        .trip-table td:nth-child(6),
        .trip-table th:nth-child(6) {
            width: 18%;
            text-align: right;
        }

        .trip-route-main {
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
        }
        .trip-route-subline {
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.35;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .trip-route-subline i {
            color: #92400e;
            font-size: 10px;
            flex: 0 0 auto;
        }
        .trip-route-subline span {
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .trip-route-type-line {
            color: #64748b;
            font-weight: 400;
        }
        .trip-inline-details-btn {
            margin-top: 2px;
            border: 0;
            background: transparent;
            padding: 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        .trip-inline-details-btn i {
            font-size: 11px;
        }
        .trip-inline-details-btn:hover {
            color: #92400e;
        }

        .trip-route-meta {
            margin-top: 4px;
        }

        .trip-table .trip-meta-pill {
            white-space: nowrap;
            font-size: 11px;
            padding: 4px 8px;
        }
        .trip-table .trip-meta-pill.mode {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        .trip-table-date {
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
        }
        .trip-table-time {
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }
        .trip-table-driver {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.2;
        }
        .trip-table-fare {
            color: #0f172a;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.1;
        }

        .trip-table .trip-actions {
            width: 100%;
            justify-content: flex-end;
            gap: 6px;
        }

        .trip-table .trip-action-btn {
            padding: 7px 10px;
            font-size: 12px;
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

        .trip-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .52);
            z-index: 2600;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }
        .trip-modal.show { display: flex; }
        .trip-modal-card {
            width: min(700px, 100%);
            max-height: min(82vh, 760px);
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #fff;
            padding: 14px;
            padding-top: 14px;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto;
            gap: 10px;
            overflow: hidden;
            position: relative;
        }
        .trip-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .trip-modal-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            padding-right: 0;
        }
        .trip-modal-close {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
        }
        .trip-modal-close:hover { background: #f8fafc; color: #0f172a; }
        .trip-modal-scroll {
            min-height: 0;
            overflow: auto;
            display: grid;
            gap: 10px;
            padding-right: 2px;
        }
        .trip-modal-grid {
            display: grid;
            gap: 7px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .trip-details-pairs {
            display: grid;
            gap: 7px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .trip-modal-grid-2 {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .trip-modal-grid-2.wide { grid-column: 1 / -1; }
        .trip-modal-line {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 10px;
            padding: 8px 10px;
            display: grid;
            gap: 2px;
        }
        .trip-modal-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .trip-icon-label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .trip-icon-label i {
            font-size: 11px;
            color: inherit;
        }
        .trip-modal-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .trip-modal-hint {
            color: #64748b;
            font-size: 11px;
            font-style: italic;
        }
        .trip-status-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
        }
        .trip-status-draft { color: #475569; border-color: #cbd5e1; background: #f8fafc; }
        .trip-status-scheduled { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
        .trip-status-recorded { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .trip-status-cancelled { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
        .trip-modal-driver {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .trip-modal-driver-avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 auto;
        }
        .trip-modal-driver-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .trip-modal-driver-meta {
            min-width: 0;
            display: grid;
            gap: 1px;
        }
        .trip-modal-driver-name {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.2;
        }
        .trip-modal-driver-email {
            color: #64748b;
            font-size: 12px;
            line-height: 1.2;
            word-break: break-word;
        }
        .trip-passenger-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .trip-passenger-count {
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #334155;
            border-radius: 999px;
            padding: 3px 9px;
            font-size: 11px;
            font-weight: 700;
        }
        .trip-passenger-list {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 10px;
            padding: 8px;
            display: grid;
            gap: 7px;
            margin-top: 6px;
        }
        .trip-passenger-item {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 9px;
            padding: 7px 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .trip-passenger-avatar {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 auto;
        }
        .trip-passenger-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .trip-passenger-meta {
            min-width: 0;
            display: grid;
            gap: 1px;
            flex: 1 1 auto;
        }
        .trip-passenger-name {
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.2;
        }
        .trip-passenger-email {
            color: #64748b;
            font-size: 11px;
            line-height: 1.2;
            word-break: break-word;
        }
        .trip-passenger-role {
            border: 1px solid #dbe2ea;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            background: #fff;
            margin-left: auto;
            white-space: nowrap;
        }
        .trip-point-cards {
            display: grid;
            gap: 7px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
        }
        .trip-point-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 10px;
            padding: 8px 10px;
            display: grid;
            gap: 3px;
            align-content: start;
            min-height: 100%;
        }
        .trip-point-card.pickup {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }
        .trip-point-card.destination {
            border-color: #fde68a;
            background: #fffbeb;
        }
        .trip-point-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .trip-point-card.pickup .trip-point-label { color: #166534; }
        .trip-point-card.destination .trip-point-label { color: #92400e; }
        .trip-point-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.3;
            word-break: break-word;
        }
        .trip-map-card {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            padding: 8px;
            display: grid;
            gap: 6px;
        }
        .trip-map-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .trip-map-hint {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
        }
        .trip-map-head .trip-modal-label {
            margin: 0;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .trip-modal-map {
            width: 100%;
            height: 150px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            user-select: none;
        }
        .trip-modal-map .leaflet-container { width: 100%; height: 100%; }
        .trip-modal-map .leaflet-control-attribution { display: none; }
        .trip-contact-bar {
            margin: 0 -14px -14px;
            padding: 10px 14px calc(10px + env(safe-area-inset-bottom, 0px));
            border-top: 1px solid #dbe2ea;
            background: rgba(255, 255, 255, 0.98);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .trip-contact-text {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }
        .trip-contact-actions {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .trip-contact-link {
            border: 1px solid #dbe2ea;
            border-radius: 9px;
            background: #fff;
            color: #0f172a;
            height: 33px;
            padding: 0 10px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .trip-contact-link.whatsapp {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }
        .trip-contact-link.email {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        .trip-contact-link.is-disabled {
            pointer-events: none;
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
        }

        @media (min-width: 640px) {
            .trip-details-pairs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 767px) {
            .trip-modal {
                align-items: center;
                justify-content: center;
                padding: calc(env(safe-area-inset-top, 0px) + 88px) 12px calc(env(safe-area-inset-bottom, 0px) + 98px);
            }
            .trip-modal-card {
                width: 100%;
                max-height: 100%;
                overflow: auto;
                border-radius: 16px;
            }
        }
        @media (max-width: 420px) {
            .trip-details-pairs { grid-template-columns: repeat(1, minmax(0, 1fr)); }
            .trip-point-cards { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        }

        @media (min-width: 1024px) {
            .trip-mobile-list {
                display: none;
            }

            .trip-table-wrap {
                display: block;
            }

            .trips-data-card {
                padding: 14px;
            }
        }

        @media (min-width: 768px) {
            .trips-tools-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .trips-filter-row {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(170px, 220px) auto;
            }
        }
    </style>

    @php
        $tripCount = $trips->count();
        $draftCount = $trips->where('status', 'draft')->count();
        $scheduledCount = $trips->whereIn('status', ['scheduled', 'confirmed'])->count();
        $recordedCount = $trips->whereIn('status', ['recorded', 'completed'])->count();
    @endphp

    <div class="trips-page">
        <section class="trips-title-card">
            <div>
                <h1 class="trips-title">Trips</h1>
                <p class="trips-subtitle">Plan, track, and manage your active trip schedules.</p>
            </div>
            @if(in_array(auth()->user()->role, ['admin', 'driver'], true))
                <a href="{{ route('trips.create') }}" class="create-trip-btn">
                    <i class="fa-solid fa-plus"></i>
                    <span>Create Trip</span>
                </a>
            @endif
        </section>

        <section class="trips-tools-card">
            <div class="trips-tools-grid">
                <div class="trips-tool-item">
                    <span class="trips-tool-label">Draft</span>
                    <span class="trips-tool-value">{{ $draftCount }}</span>
                </div>
                <div class="trips-tool-item">
                    <span class="trips-tool-label">Scheduled</span>
                    <span class="trips-tool-value">{{ $scheduledCount }}</span>
                </div>
                <div class="trips-tool-item">
                    <span class="trips-tool-label">Recorded</span>
                    <span class="trips-tool-value">{{ $recordedCount }}</span>
                </div>
                <div class="trips-tool-item">
                    <span class="trips-tool-label">Current List</span>
                    <span class="trips-tool-value">{{ $tripCount }}</span>
                </div>
            </div>
            <p class="trips-tools-hint">
                Two-way trips are shown as one combined item. Fare shown here follows your role view (admin sees total, others see personal share).
            </p>
        </section>

        <section class="trips-data-card">
            <div class="trips-list-header">
                <div>
                    <h2 class="trips-list-title">Trip List</h2>
                </div>
            </div>

            <form method="GET" action="{{ route('trips.index') }}" class="trips-filter-row" style="margin-bottom:10px;">
                <div class="trips-filter-field">
                    <label class="trips-filter-label" for="trip_date_from">From Date</label>
                    <input
                        id="trip_date_from"
                        name="date_from"
                        type="date"
                        class="trips-filter-input"
                        value="{{ $filters['date_from'] ?? request('date_from') }}"
                    >
                </div>
                <div class="trips-filter-field">
                    <label class="trips-filter-label" for="trip_date_to">To Date</label>
                    <input
                        id="trip_date_to"
                        name="date_to"
                        type="date"
                        class="trips-filter-input"
                        value="{{ $filters['date_to'] ?? request('date_to') }}"
                    >
                </div>
                <div class="trips-filter-field">
                    <label class="trips-filter-label" for="trip_visibility">Visibility</label>
                    <select id="trip_visibility" name="visibility" class="trips-filter-input">
                        <option value="">All</option>
                        <option value="public" {{ ($filters['visibility'] ?? request('visibility')) === 'public' ? 'selected' : '' }}>Public</option>
                        <option value="private" {{ ($filters['visibility'] ?? request('visibility')) === 'private' ? 'selected' : '' }}>Private</option>
                    </select>
                </div>
                <div class="trips-filter-actions">
                    <button type="submit" class="trips-filter-btn primary">Filter</button>
                    <a href="{{ route('trips.index') }}" class="trips-filter-btn">Reset</a>
                </div>
            </form>

            @if($trips->isEmpty())
                <div class="empty-state">No trips yet. Create your first trip to get started.</div>
            @else
                <div class="trip-mobile-list">
                    @foreach($trips as $trip)
                        @php
                            $hasReturn = (bool) $trip->returnTrip;
                            $pickupName = $trip->pickup_name ?? 'Pickup';
                            $destinationName = $trip->destination_name ?? 'Destination';
                            $directionText = $pickupName . ' -> ' . $destinationName;
                            $returnDirectionText = $destinationName . ' -> ' . $pickupName;
                            $routeName = $trip->savedRoute?->route_name ?: $directionText;
                            $modeText = $hasReturn ? 'Two Way' : 'One Way';
                            $visibilityText = ucfirst((string) ($trip->visibility ?? 'private')) . ' Trip';
                            $visibilityIcon = ($trip->visibility ?? 'private') === 'public' ? 'fa-solid fa-lock-open' : 'fa-solid fa-lock';
                            $combinedFare = (float) $trip->fare_total + (float) ($trip->returnTrip?->fare_total ?? 0);
                            $myFare = (float) ($trip->payments->first()?->amount_due ?? 0)
                                + (float) ($trip->returnTrip?->payments?->first()?->amount_due ?? 0);
                            $showTotalFare = auth()->user()->role === 'admin';
                            $fareLabel = 'Fare';
                            $displayFare = $showTotalFare ? $combinedFare : $myFare;
                            $pairedTripId = $trip->returnTrip?->id;
                            $paymentFocusIds = array_values(array_filter([
                                (int) $trip->id,
                                $pairedTripId ? (int) $pairedTripId : null,
                            ]));
                            $paymentFocusQuery = implode(',', $paymentFocusIds);
                            $participantPayload = $trip->participants
                                ->map(fn ($participant) => [
                                    'user_id' => $participant->user_id,
                                    'name' => $participant->user?->name ?? '-',
                                    'email' => $participant->user?->email ?? '',
                                    'photo_url' => $participant->user?->profile_photo_url ?? null,
                                    'is_driver' => (bool) $participant->is_driver,
                                ])
                                ->values();
                            $participantPayloadB64 = base64_encode($participantPayload->toJson());
                            $passengerCount = (int) $trip->participants->where('is_driver', false)->count();
                            if ($passengerCount === 0 && (int) $trip->participant_count > 0) {
                                $passengerCount = (int) $trip->participant_count;
                            }
                            $splitType = ((int) $trip->participant_count > $passengerCount)
                                ? 'Include Driver in Fare Split'
                                : 'Exclude Driver from Fare Split';
                        @endphp
                        <article class="trip-mobile-item open-trip-card" data-trip-anchor="{{ $trip->id }}">
                            <div class="trip-mobile-head">
                                <div>
                                    <h2 class="trip-route-title">{{ $routeName }}</h2>
                                    <div class="trip-meta-inline">
                                        <span class="trip-meta-inline-item">
                                            <i class="fa-solid fa-user"></i>
                                            <span>{{ $trip->driver?->name ?: '-' }}</span>
                                        </span>
                                        <span class="trip-meta-inline-item">
                                            <i class="fa-solid fa-route"></i>
                                            <span>{{ $modeText }}</span>
                                        </span>
                                        <span class="trip-meta-inline-item">
                                            <i class="{{ $visibilityIcon }}"></i>
                                            <span>{{ $visibilityText }}</span>
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        class="trip-inline-details-btn open-trip-modal-btn"
                                        data-trip-id="{{ $trip->id }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-route-name="{{ $routeName }}"
                                        data-driver-name="{{ $trip->driver?->name ?: '-' }}"
                                        data-driver-id="{{ $trip->driver_id }}"
                                        data-driver-email="{{ $trip->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $trip->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $trip->driver?->whatsapp_digits ?: '' }}"
                                        data-mode="{{ $modeText }}"
                                        data-status="{{ ucfirst($trip->status) }}"
                                        data-outbound-datetime="{{ $trip->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-return-datetime="{{ $trip->returnTrip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-outbound-route="{{ $directionText }}"
                                        data-return-route="{{ $returnDirectionText }}"
                                        data-fare-label="{{ $fareLabel }}"
                                        data-fare-display="RM {{ number_format($displayFare, 2) }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                                        data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                                        data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                                        data-total-passengers="{{ $passengerCount }}"
                                        data-split-type="{{ $splitType }}"
                                        data-participants-b64="{{ $participantPayloadB64 }}"
                                    >
                                        <i class="fa-regular fa-eye"></i>
                                        <span>See Details</span>
                                    </button>
                                </div>
                                <span class="status-chip status-{{ strtolower($trip->status) }}">{{ ucfirst($trip->status) }}</span>
                            </div>

                            <div class="trip-detail-grid">
                                <div class="trip-detail-line">
                                    <span class="trip-detail-label">Date & Time</span>
                                    <span class="trip-detail-value">{{ $trip->trip_datetime?->format('Y-m-d H:i') ?: '-' }}</span>
                                </div>
                            </div>

                            <div class="trip-bottom-row">
                                <div class="trip-fare-card">
                                    <span class="trip-fare-label">{{ $fareLabel }}</span>
                                    <span class="trip-fare-value">RM {{ number_format($displayFare, 2) }}</span>
                                </div>
                                <div class="trip-actions">
                                    @if($trip->status === 'draft')
                                        <span class="trip-action-btn disabled" title="Draft trip has no payment yet">Payments</span>
                                    @elseif($trip->status === 'scheduled')
                                        @if(($trip->visibility === 'public') && (auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id))
                                            <a href="{{ route('trips.requests.index', $trip) }}" class="trip-action-btn">Manage Requests</a>
                                        @else
                                            <span class="trip-action-btn disabled" title="Payment opens after trip time">Payments</span>
                                        @endif
                                    @else
                                        <a href="{{ route('payments.index', ['trip_id' => $trip->id, 'trip_ids' => $paymentFocusQuery]) }}" class="trip-action-btn">Payments</a>
                                    @endif
                                    @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                                        <a href="{{ route('trips.edit', $trip) }}" class="trip-action-btn">Edit</a>
                                        <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="trip-action-form" onsubmit="return confirm('Delete this trip and all related records?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="trip-action-btn trip-action-btn-danger">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="trip-table-wrap">
                    <table class="trip-table">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Route</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Fare</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($trips as $trip)
                            @php
                                $hasReturn = (bool) $trip->returnTrip;
                                $pickupName = $trip->pickup_name ?? 'Pickup';
                                $destinationName = $trip->destination_name ?? 'Destination';
                                $directionText = $pickupName . ' -> ' . $destinationName;
                                $returnDirectionText = $destinationName . ' -> ' . $pickupName;
                                $routeName = $trip->savedRoute?->route_name ?: $directionText;
                                $modeText = $hasReturn ? 'Two Way' : 'One Way';
                                $visibilityText = ucfirst((string) ($trip->visibility ?? 'private')) . ' Trip';
                                $visibilityIcon = ($trip->visibility ?? 'private') === 'public' ? 'fa-solid fa-lock-open' : 'fa-solid fa-lock';
                                $combinedFare = (float) $trip->fare_total + (float) ($trip->returnTrip?->fare_total ?? 0);
                                $myFare = (float) ($trip->payments->first()?->amount_due ?? 0)
                                    + (float) ($trip->returnTrip?->payments?->first()?->amount_due ?? 0);
                                $showTotalFare = auth()->user()->role === 'admin';
                                $displayFare = $showTotalFare ? $combinedFare : $myFare;
                                $pairedTripId = $trip->returnTrip?->id;
                                $paymentFocusIds = array_values(array_filter([
                                    (int) $trip->id,
                                    $pairedTripId ? (int) $pairedTripId : null,
                                ]));
                                $paymentFocusQuery = implode(',', $paymentFocusIds);
                                $pickupShort = \Illuminate\Support\Str::limit($pickupName, 34, '...');
                                $destinationShort = \Illuminate\Support\Str::limit($destinationName, 34, '...');
                                $participantPayload = $trip->participants
                                    ->map(fn ($participant) => [
                                        'user_id' => $participant->user_id,
                                        'name' => $participant->user?->name ?? '-',
                                        'email' => $participant->user?->email ?? '',
                                        'photo_url' => $participant->user?->profile_photo_url ?? null,
                                        'is_driver' => (bool) $participant->is_driver,
                                    ])
                                    ->values();
                                $participantPayloadB64 = base64_encode($participantPayload->toJson());
                                $passengerCount = (int) $trip->participants->where('is_driver', false)->count();
                                if ($passengerCount === 0 && (int) $trip->participant_count > 0) {
                                    $passengerCount = (int) $trip->participant_count;
                                }
                                $splitType = ((int) $trip->participant_count > $passengerCount)
                                    ? 'Include Driver in Fare Split'
                                    : 'Exclude Driver from Fare Split';
                            @endphp
                            <tr class="open-trip-card" data-trip-anchor="{{ $trip->id }}">
                                <td>
                                    <div class="trip-table-date">{{ $trip->trip_datetime?->format('d M Y') ?: '-' }}</div>
                                    <div class="trip-table-time">{{ $trip->trip_datetime?->format('H:i') ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="trip-route-main">{{ $routeName }}</div>
                                    <div class="trip-route-subline" title="{{ $directionText }}">
                                        <i class="fa-solid fa-location-arrow"></i>
                                        <span>{{ $pickupShort }} -> {{ $destinationShort }}</span>
                                    </div>
                                    <div class="trip-route-subline trip-route-type-line">
                                        <i class="fa-solid fa-route"></i>
                                        <span>{{ $modeText }}</span>
                                    </div>
                                    <div class="trip-route-subline trip-route-type-line">
                                        <i class="{{ $visibilityIcon }}"></i>
                                        <span>{{ $visibilityText }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="trip-inline-details-btn open-trip-modal-btn"
                                        data-trip-id="{{ $trip->id }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-route-name="{{ $routeName }}"
                                        data-driver-name="{{ $trip->driver?->name ?: '-' }}"
                                        data-driver-id="{{ $trip->driver_id }}"
                                        data-driver-email="{{ $trip->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $trip->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $trip->driver?->whatsapp_digits ?: '' }}"
                                        data-mode="{{ $modeText }}"
                                        data-status="{{ ucfirst($trip->status) }}"
                                        data-outbound-datetime="{{ $trip->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-return-datetime="{{ $trip->returnTrip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-outbound-route="{{ $directionText }}"
                                        data-return-route="{{ $returnDirectionText }}"
                                        data-fare-label="Fare"
                                        data-fare-display="RM {{ number_format($displayFare, 2) }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                                        data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                                        data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                                        data-total-passengers="{{ $passengerCount }}"
                                        data-split-type="{{ $splitType }}"
                                        data-participants-b64="{{ $participantPayloadB64 }}"
                                    >
                                        <i class="fa-regular fa-eye"></i>
                                        <span>See Details</span>
                                    </button>
                                </td>
                                <td><span class="trip-table-driver">{{ $trip->driver?->name ?: '-' }}</span></td>
                                <td><span class="status-chip status-{{ strtolower($trip->status) }}">{{ ucfirst($trip->status) }}</span></td>
                                <td><span class="trip-table-fare">RM {{ number_format($displayFare, 2) }}</span></td>
                                <td>
                                    <div class="trip-actions" style="justify-content:flex-end;">
                                        @if($trip->status === 'draft')
                                            <span class="trip-action-btn disabled" title="Draft trip has no payment yet">Payments</span>
                                        @elseif($trip->status === 'scheduled')
                                            @if(($trip->visibility === 'public') && (auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id))
                                                <a href="{{ route('trips.requests.index', $trip) }}" class="trip-action-btn">Manage Requests</a>
                                            @else
                                                <span class="trip-action-btn disabled" title="Payment opens after trip time">Payments</span>
                                            @endif
                                        @else
                                            <a href="{{ route('payments.index', ['trip_id' => $trip->id, 'trip_ids' => $paymentFocusQuery]) }}" class="trip-action-btn">Payments</a>
                                        @endif
                                        @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                                            <a href="{{ route('trips.edit', $trip) }}" class="trip-action-btn">Edit</a>
                                            <form action="{{ route('trips.destroy', $trip) }}" method="POST" class="trip-action-form" onsubmit="return confirm('Delete this trip and all related records?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="trip-action-btn trip-action-btn-danger">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="pagination-wrap">
        {{ $trips->appends(request()->query())->links() }}
    </div>

    <div class="trip-modal" id="tripDetailsModal" aria-hidden="true">
        <div class="trip-modal-card">
            <div class="trip-modal-head">
                <h3 class="trip-modal-title">Trip Details</h3>
                <button type="button" class="trip-modal-close" id="tripDetailsCloseBtn" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-modal-scroll">
                <div class="trip-modal-grid">
                    <div class="trip-details-pairs">
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Trip ID</span>
                            <span class="trip-modal-value" id="tripModalTripIds">-</span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-regular fa-calendar"></i>Date & Time</span>
                            <span class="trip-modal-value" id="tripModalOutboundTime">-</span>
                        </div>
                    </div>
                    <div class="trip-modal-line">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-road"></i>Route Name</span>
                        <span class="trip-modal-value" id="tripModalRouteName">-</span>
                    </div>
                    <div class="trip-point-cards">
                        <div class="trip-point-card pickup">
                            <span class="trip-point-label" id="tripModalPointALabel"><i class="fa-solid fa-location-dot"></i>Pickup Point</span>
                            <span class="trip-point-value" id="tripModalPickupPoint">-</span>
                        </div>
                        <div class="trip-point-card destination">
                            <span class="trip-point-label" id="tripModalPointBLabel"><i class="fa-solid fa-flag-checkered"></i>Destination Point</span>
                            <span class="trip-point-value" id="tripModalDestinationPoint">-</span>
                        </div>
                    </div>
                    <div class="trip-map-card">
                        <div class="trip-map-head">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-regular fa-map"></i>Route Preview</span>
                            <span class="trip-map-hint">View only</span>
                        </div>
                        <div class="trip-modal-map" id="tripModalMap"></div>
                    </div>
                    <div class="trip-modal-line">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Driver</span>
                        <div class="trip-modal-driver">
                            <span class="trip-modal-driver-avatar" id="tripModalDriverAvatar">D</span>
                            <span class="trip-modal-driver-meta">
                                <span class="trip-modal-driver-name" id="tripModalDriver">-</span>
                                <span class="trip-modal-driver-email" id="tripModalDriverEmail">-</span>
                            </span>
                        </div>
                    </div>
                    <div class="trip-modal-line">
                        <div class="trip-passenger-header">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-users"></i>Passengers</span>
                            <span class="trip-passenger-count" id="tripModalPassengerCount">0 passengers</span>
                        </div>
                        <div class="trip-passenger-list" id="tripModalPassengerList"></div>
                    </div>
                    <div class="trip-details-pairs">
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-route"></i>Trip Type</span>
                            <span class="trip-modal-value" id="tripModalMode">-</span>
                            <span class="trip-modal-hint" id="tripModalPairHint" style="display:none;"></span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-regular fa-circle-check"></i>Status</span>
                            <span class="trip-modal-value trip-status-badge" id="tripModalStatus">-</span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user-group"></i>Total Passengers</span>
                            <span class="trip-modal-value" id="tripModalTotalPassengers">-</span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-scale-balanced"></i>Fare Split Type</span>
                            <span class="trip-modal-value" id="tripModalSplitType">-</span>
                        </div>
                        <div class="trip-modal-line">
                            <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-wallet"></i><span id="tripModalFareLabel">Fare</span></span>
                            <span class="trip-modal-value" id="tripModalFareValue">-</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="trip-contact-bar">
                <p class="trip-contact-text">Having issues with this trip? Please contact the driver.</p>
                <div class="trip-contact-actions">
                    <a href="#" target="_blank" rel="noopener" class="trip-contact-link whatsapp is-disabled" id="tripModalWhatsapp">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>WhatsApp</span>
                    </a>
                    <a href="#" class="trip-contact-link email is-disabled" id="tripModalEmail">
                        <i class="fa-regular fa-envelope"></i>
                        <span>Email Driver</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const params = new URLSearchParams(window.location.search);
            const focusTrip = String(params.get('focus_trip') || '').trim();
            if (!focusTrip) return;

            const targets = Array.from(document.querySelectorAll('[data-trip-anchor]'))
                .filter((el) => String(el.getAttribute('data-trip-anchor') || '').trim() === focusTrip);
            if (targets.length === 0) return;

            const target = targets.find((el) => el instanceof HTMLElement && el.offsetParent !== null) || targets[0];
            if (!(target instanceof HTMLElement)) return;

            requestAnimationFrame(() => {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                target.classList.add('trip-focus-highlight');
                window.setTimeout(() => target.classList.remove('trip-focus-highlight'), 2200);
            });
        })();

        (() => {
            const modal = document.getElementById('tripDetailsModal');
            const closeBtn = document.getElementById('tripDetailsCloseBtn');
            const detailButtons = document.querySelectorAll('.open-trip-modal-btn');
            if (!modal || !closeBtn || detailButtons.length === 0) return;
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const tripIdsEl = document.getElementById('tripModalTripIds');
            const modeEl = document.getElementById('tripModalMode');
            const pairHintEl = document.getElementById('tripModalPairHint');
            const routeNameEl = document.getElementById('tripModalRouteName');
            const driverEl = document.getElementById('tripModalDriver');
            const driverAvatarEl = document.getElementById('tripModalDriverAvatar');
            const driverEmailEl = document.getElementById('tripModalDriverEmail');
            const statusEl = document.getElementById('tripModalStatus');
            const outboundTimeEl = document.getElementById('tripModalOutboundTime');
            const fareLabelEl = document.getElementById('tripModalFareLabel');
            const fareValueEl = document.getElementById('tripModalFareValue');
            const totalPassengersEl = document.getElementById('tripModalTotalPassengers');
            const splitTypeEl = document.getElementById('tripModalSplitType');
            const passengerCountEl = document.getElementById('tripModalPassengerCount');
            const passengerListEl = document.getElementById('tripModalPassengerList');
            const pickupPointEl = document.getElementById('tripModalPickupPoint');
            const destinationPointEl = document.getElementById('tripModalDestinationPoint');
            const pointALabelEl = document.getElementById('tripModalPointALabel');
            const pointBLabelEl = document.getElementById('tripModalPointBLabel');
            const mapEl = document.getElementById('tripModalMap');
            const whatsappEl = document.getElementById('tripModalWhatsapp');
            const emailEl = document.getElementById('tripModalEmail');

            let miniMap = null;
            let routeLayer = null;
            let markerLayer = null;

            const toNum = (v) => {
                const n = Number.parseFloat(String(v ?? '').trim());
                return Number.isFinite(n) ? n : null;
            };
            const toStatusSlug = (value) => String(value || '')
                .trim()
                .toLowerCase()
                .replace(/\s+/g, '_')
                .replace(/[^a-z0-9_]/g, '');
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const renderPassengerList = (participantsRaw, driverIdRaw = null) => {
                if (!passengerListEl || !passengerCountEl) return;
                const participants = Array.isArray(participantsRaw) ? participantsRaw : [];
                const toBool = (value) => value === true || value === 1 || value === '1';
                const driverId = Number.parseInt(String(driverIdRaw ?? ''), 10);
                const passengers = participants.filter((item) => {
                    if (!item || (!item.name && !item.email)) return false;
                    if (toBool(item?.is_driver)) return false;
                    const uid = Number.parseInt(String(item?.user_id ?? ''), 10);
                    if (Number.isFinite(driverId) && driverId > 0 && Number.isFinite(uid) && uid === driverId) return false;
                    return true;
                });

                passengerCountEl.textContent = `${passengers.length} passenger${passengers.length === 1 ? '' : 's'}`;

                if (passengers.length === 0) {
                    passengerListEl.innerHTML = '<div class="trip-passenger-email">No passenger records found for this trip.</div>';
                    return;
                }

                passengerListEl.innerHTML = passengers.map((item) => {
                    const name = escapeHtml(item?.name || '-');
                    const email = escapeHtml(item?.email || '');
                    const avatarHtml = item?.photo_url
                        ? `<span class="trip-passenger-avatar"><img src="${escapeHtml(item.photo_url)}" alt="${name}"></span>`
                        : `<span class="trip-passenger-avatar">${escapeHtml((item?.name || 'U').trim().charAt(0).toUpperCase() || 'U')}</span>`;

                    return `
                        <div class="trip-passenger-item">
                            ${avatarHtml}
                            <div class="trip-passenger-meta">
                                <span class="trip-passenger-name">${name}</span>
                                <span class="trip-passenger-email">${email || '-'}</span>
                            </div>
                            <span class="trip-passenger-role">Passenger</span>
                        </div>
                    `;
                }).join('');
            };

            const ensureMap = () => {
                if (!mapEl || typeof window.L === 'undefined') return null;
                if (miniMap) return miniMap;

                mapEl.innerHTML = '';
                miniMap = window.L.map(mapEl, {
                    zoomControl: false,
                    attributionControl: false,
                    dragging: false,
                    scrollWheelZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    keyboard: false,
                    tap: false,
                    touchZoom: false,
                });

                window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                }).addTo(miniMap);

                return miniMap;
            };

            const drawMap = async (pickupLat, pickupLng, destinationLat, destinationLng) => {
                const map = ensureMap();
                if (!map) return;
                if ([pickupLat, pickupLng, destinationLat, destinationLng].some((v) => v === null)) return;

                if (routeLayer) { map.removeLayer(routeLayer); routeLayer = null; }
                if (markerLayer) { map.removeLayer(markerLayer); markerLayer = null; }

                markerLayer = window.L.layerGroup([
                    window.L.circleMarker([pickupLat, pickupLng], {
                        radius: 6, color: '#fff', weight: 2, fillColor: '#16a34a', fillOpacity: 1
                    }),
                    window.L.circleMarker([destinationLat, destinationLng], {
                        radius: 6, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1
                    }),
                ]).addTo(map);

                map.fitBounds(window.L.latLngBounds([[pickupLat, pickupLng], [destinationLat, destinationLng]]), { padding: [16, 16] });

                const url = 'https://router.project-osrm.org/route/v1/driving/'
                    + `${encodeURIComponent(pickupLng)},${encodeURIComponent(pickupLat)};`
                    + `${encodeURIComponent(destinationLng)},${encodeURIComponent(destinationLat)}`
                    + '?overview=full&geometries=geojson&alternatives=false&steps=false';

                try {
                    const response = await fetch(url, { method: 'GET' });
                    if (!response.ok) throw new Error('route');
                    const payload = await response.json();
                    const geometry = payload?.routes?.[0]?.geometry?.coordinates ?? [];
                    const latLngs = geometry
                        .map((coord) => [Number(coord[1]), Number(coord[0])])
                        .filter((coord) => Number.isFinite(coord[0]) && Number.isFinite(coord[1]));

                    if (latLngs.length > 1) {
                        routeLayer = window.L.polyline(latLngs, { color: '#1d4ed8', weight: 4, opacity: 0.95 }).addTo(map);
                        map.fitBounds(routeLayer.getBounds(), { padding: [16, 16] });
                    } else {
                        routeLayer = window.L.polyline([[pickupLat, pickupLng], [destinationLat, destinationLng]], {
                            color: '#60a5fa', weight: 3, opacity: 0.9, dashArray: '8 6'
                        }).addTo(map);
                    }
                } catch (_e) {
                    routeLayer = window.L.polyline([[pickupLat, pickupLng], [destinationLat, destinationLng]], {
                        color: '#60a5fa', weight: 3, opacity: 0.9, dashArray: '8 6'
                    }).addTo(map);
                }
            };

            detailButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const tripId = String(btn.dataset.tripId || '-');
                    const pairedTripId = String(btn.dataset.pairedTripId || '').trim();
                    const isTwoWay = String(btn.dataset.mode || '').toLowerCase().includes('two way');
                    const driverId = Number.parseInt(String(btn.dataset.driverId || ''), 10);
                    const driverEmail = String(btn.dataset.driverEmail || '').trim();
                    const driverWhatsappUrl = String(btn.dataset.driverWhatsappUrl || '').trim();
                    const driverPhoneRaw = String(btn.dataset.driverPhone || '');
                    let participantsPayload = [];
                    try {
                        const encoded = String(btn.dataset.participantsB64 || '').trim();
                        if (encoded) {
                            participantsPayload = JSON.parse(atob(encoded));
                        } else {
                            participantsPayload = JSON.parse(btn.dataset.participants || '[]');
                        }
                    } catch (_e) {
                        participantsPayload = [];
                    }
                    const digitsRaw = driverPhoneRaw.replace(/\D+/g, '');
                    let waDigits = digitsRaw.replace(/^00+/, '');
                    if (/^01\d{8,9}$/.test(waDigits)) {
                        waDigits = `60${waDigits.slice(1)}`;
                    }
                    const waUrl = /^https?:\/\/wa\.me\/\d+$/i.test(driverWhatsappUrl)
                        ? driverWhatsappUrl
                        : (waDigits ? `https://wa.me/${waDigits}` : '');

                    if (tripIdsEl) tripIdsEl.textContent = pairedTripId ? `#${tripId} & #${pairedTripId}` : `#${tripId}`;
                    if (modeEl) modeEl.textContent = btn.dataset.mode || '-';
                    if (pairHintEl) {
                        if (isTwoWay && pairedTripId) {
                            pairHintEl.textContent = `Paired trip: Trip #${pairedTripId}`;
                            pairHintEl.style.display = 'block';
                        } else {
                            pairHintEl.textContent = '';
                            pairHintEl.style.display = 'none';
                        }
                    }
                    if (routeNameEl) routeNameEl.textContent = btn.dataset.routeName || '-';
                    if (driverEl) driverEl.textContent = btn.dataset.driverName || '-';
                    if (driverAvatarEl) driverAvatarEl.textContent = ((btn.dataset.driverName || 'D').trim().charAt(0) || 'D').toUpperCase();
                    if (driverEmailEl) driverEmailEl.textContent = driverEmail || '-';
                    if (statusEl) {
                        const statusText = btn.dataset.status || '-';
                        const slug = toStatusSlug(statusText);
                        statusEl.textContent = statusText;
                        statusEl.className = `trip-modal-value trip-status-badge trip-status-${slug || 'draft'}`;
                    }
                    if (outboundTimeEl) outboundTimeEl.textContent = btn.dataset.outboundDatetime || '-';
                    if (fareLabelEl) fareLabelEl.textContent = btn.dataset.fareLabel || 'Fare';
                    if (fareValueEl) fareValueEl.textContent = btn.dataset.fareDisplay || '-';
                    const totalPassengersText = btn.dataset.totalPassengers || '0';
                    if (totalPassengersEl) totalPassengersEl.textContent = totalPassengersText;
                    if (splitTypeEl) splitTypeEl.textContent = btn.dataset.splitType || '-';
                    renderPassengerList(participantsPayload, driverId);
                    if (passengerCountEl && (!participantsPayload || participantsPayload.length === 0)) {
                        const n = Number.parseInt(totalPassengersText, 10);
                        if (Number.isFinite(n) && n > 0) {
                            passengerCountEl.textContent = `${n} passenger${n === 1 ? '' : 's'}`;
                        }
                    }
                    if (pointALabelEl) {
                        pointALabelEl.innerHTML = '<i class="fa-solid fa-location-dot"></i>Pickup Point';
                    }
                    if (pointBLabelEl) {
                        pointBLabelEl.innerHTML = '<i class="fa-solid fa-flag-checkered"></i>Destination Point';
                    }
                    if (pickupPointEl) pickupPointEl.textContent = btn.dataset.pickupName || '-';
                    if (destinationPointEl) destinationPointEl.textContent = btn.dataset.destinationName || '-';
                    if (emailEl) {
                        if (driverEmail) {
                            emailEl.classList.remove('is-disabled');
                            emailEl.setAttribute('href', `mailto:${driverEmail}`);
                        } else {
                            emailEl.classList.add('is-disabled');
                            emailEl.setAttribute('href', '#');
                        }
                    }
                    if (whatsappEl) {
                        if (waUrl) {
                            whatsappEl.classList.remove('is-disabled');
                            whatsappEl.setAttribute('href', waUrl);
                        } else {
                            whatsappEl.classList.add('is-disabled');
                            whatsappEl.setAttribute('href', '#');
                        }
                    }

                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');

                    const pickupLat = toNum(btn.dataset.pickupLat);
                    const pickupLng = toNum(btn.dataset.pickupLng);
                    const destinationLat = toNum(btn.dataset.destinationLat);
                    const destinationLng = toNum(btn.dataset.destinationLng);

                    setTimeout(() => {
                        drawMap(pickupLat, pickupLng, destinationLat, destinationLng).then(() => {
                            if (miniMap) miniMap.invalidateSize();
                        });
                    }, 40);
                });
            });

            const interactiveSelector = 'a, button, input, select, textarea, form, label';
            document.querySelectorAll('.open-trip-card').forEach((card) => {
                card.addEventListener('click', (event) => {
                    const target = event.target;
                    if (!(target instanceof Element)) return;
                    if (target.closest(interactiveSelector)) return;
                    const btn = card.querySelector('.open-trip-modal-btn');
                    if (btn instanceof HTMLButtonElement) btn.click();
                });
            });

            const closeModal = () => {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            };
            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
        })();
    </script>
@endsection

