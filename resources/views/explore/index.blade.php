@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <style>
        /* ── Explore Index Page ─────────────────────────────────────── */

        .xp-wrap {
            padding: 0 18px 28px;
            max-width: 1280px;
            margin: 0 auto;
        }

        /* ── Page Header ───────────────────────────────────────────── */
        .xp-page-header {
            padding: 20px 0 0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .xp-header-copy { min-width: 0; }
        .xp-eyebrow {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--muted);
            margin: 0 0 4px;
        }
        .xp-title {
            margin: 0;
            font-family: var(--font-display);
            font-size: 28px;
            font-weight: 800;
            color: var(--ink);
            line-height: 1.1;
        }
        .xp-subtitle {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }
        .xp-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        /* ── Search bar ────────────────────────────────────────────── */
        .xp-search-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: 10px;
            padding: 9px;
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: var(--shadow-1);
        }
        .xp-search-field {
            flex: 1;
            min-width: 200px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--surface-2);
            border: 1px solid var(--hairline-strong);
            border-radius: 8px;
            padding: 8px 11px;
        }
        .xp-search-field input {
            flex: 1;
            min-width: 0;
            border: none;
            background: transparent;
            color: var(--ink);
            font-size: 13px;
            outline: none;
        }
        .xp-search-field input::placeholder { color: var(--muted); }
        .xp-search-field i { color: var(--muted); font-size: 13px; flex-shrink: 0; }
        .xp-search-field i.pickup-pin { color: #16a34a; }
        .xp-search-separator {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 14px;
            flex-shrink: 0;
        }
        .xp-search-date {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--surface-2);
            border: 1px solid var(--hairline-strong);
            border-radius: 8px;
            padding: 8px 11px;
        }
        .xp-search-date input {
            border: none;
            background: transparent;
            color: var(--ink);
            font-size: 13px;
            outline: none;
        }
        .xp-search-date i { color: var(--muted); font-size: 13px; flex-shrink: 0; }
        .xp-advanced-mobile {
            display: none;
        }

        /* ── Filter chips row ──────────────────────────────────────── */
        .xp-chips-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding: 11px 0 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .xp-chips-row::-webkit-scrollbar { display: none; }
        .xp-chips-spacer { flex: 1; }
        .xp-mobile-chip { display: none; }
        .xp-desktop-chip {
            font-size: 12px;
            font-weight: 800;
            padding: 7px 13px;
            border-radius: 999px;
            background: var(--surface);
            color: var(--ink-2);
        }
        .xp-desktop-chip.active {
            background: var(--ch-yellow);
            border-color: var(--ch-yellow);
            color: var(--ch-yellow-ink);
        }
        .xp-sort-label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* ── Left: trip list ───────────────────────────────────────── */
        .xp-list { display: grid; gap: 12px; align-content: start; }

        /* Skeleton trip card ────────────────────────────── */
        .xp-skel-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 16px;
            display: grid;
            gap: 10px;
            box-shadow: var(--shadow-1);
        }
        /* Fade overlay for list during search --*/
        .xp-list-loading { pointer-events: none; opacity: 0.4; }
        #xp-skel-list { display: none; }

        /* ── Main body grid ────────────────────────────────────────── */
        .xp-body {
            padding-top: 14px;
            display: grid;
            gap: 18px;
        }
        @media (min-width: 1024px) {
            .xp-body { grid-template-columns: minmax(0, 700px) minmax(300px, 1fr); }
        }

        /* ── Left: trip list ───────────────────────────────────────── */
        .xp-list { display: grid; gap: 12px; align-content: start; }

        /* ── Empty state ───────────────────────────────────────────── */
        .xp-empty {
            border: 1.5px dashed var(--hairline-strong);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 48px 24px;
            text-align: center;
        }
        .xp-empty-icon {
            font-size: 34px;
            color: var(--muted-2);
            display: block;
            margin-bottom: 12px;
        }
        .xp-empty-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--ink-3);
            margin: 0 0 4px;
        }
        .xp-empty-copy {
            font-size: 13px;
            color: var(--muted-2);
            margin: 0;
            line-height: 1.5;
        }

        /* ── Trip card ─────────────────────────────────────────────── */
        .xp-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-1);
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .xp-card:hover {
            transform: translateY(-2px);
            border-color: var(--hairline-strong);
            box-shadow: var(--shadow-2);
        }
        .xp-card.is-focus {
            border-color: var(--ch-yellow);
            box-shadow: 0 0 0 3px rgba(250,204,21,.22), var(--shadow-3);
        }
        .xp-card.is-recommended {
            border-color: var(--ch-yellow-line);
            box-shadow: 0 0 0 2px rgba(250,204,21,.18), var(--shadow-1);
        }
        .xp-rec-strip {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            padding: 6px 14px;
            background: var(--ch-yellow-tint);
            border-bottom: 1px solid var(--ch-yellow-line);
            font-size: 11px;
            font-weight: 900;
            color: var(--warning-ink);
        }
        .xp-rec-strip i { font-size: 10px; }
        .xp-rec-pill {
            border: 1px solid var(--ch-yellow-line);
            border-radius: 999px;
            background: var(--surface);
            color: var(--warning-ink);
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 700;
        }
        .xp-card-body {
            padding: 13px 14px;
            display: grid;
            gap: 10px;
        }
        .xp-card-main {
            display: grid;
            gap: 10px;
            min-width: 0;
        }

        /* Driver row */
        .xp-driver-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .xp-avatar {
            width: 34px;
            height: 34px;
            border-radius: var(--r-pill);
            background: var(--ch-yellow);
            border: 1px solid var(--ch-yellow-line);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: var(--ch-yellow-ink);
            flex-shrink: 0;
            font-weight: 800;
            text-transform: uppercase;
        }
        .xp-driver-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .xp-driver-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .xp-driver-rating {
            font-size: 11px;
            color: var(--warning-ink);
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .xp-driver-badge {
            margin-left: auto;
            flex-shrink: 0;
        }

        /* Route timeline */
        .xp-route-timeline {
            display: grid;
            gap: 1px;
        }
        .xp-timeline-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .xp-timeline-track {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
            width: 14px;
            padding-top: 3px;
        }
        .xp-timeline-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .xp-timeline-dot.pickup {
            background: #16a34a;
            border: 2px solid #86efac;
        }
        .xp-timeline-dot.dest {
            background: #1e293b;
            border: 2px solid #94a3b8;
        }
        .xp-timeline-dot.custom {
            background: var(--ch-yellow-deep);
            border: 2px solid var(--ch-yellow-line);
        }
        .xp-timeline-line {
            width: 2px;
            height: 18px;
            background: var(--hairline-strong);
            margin: 2px 0;
            flex-shrink: 0;
        }
        .xp-timeline-text {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1.4;
            padding-top: 1px;
        }
        .xp-timeline-note {
            font-size: 11.5px;
            color: var(--muted);
            font-weight: 500;
            padding-top: 1px;
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Card footer */
        .xp-card-footer {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            padding-top: 9px;
            border-top: 1px solid var(--hairline);
        }
        .xp-footer-time {
            font-family: var(--font-mono);
            font-size: 13px;
            font-weight: 700;
            color: var(--ink-3);
        }
        .xp-footer-dot {
            color: var(--muted-2);
            font-size: 12px;
        }
        .xp-footer-seats {
            font-size: 12px;
            font-weight: 600;
            color: var(--ink-3);
        }
        .xp-footer-fare {
            margin-left: auto;
            font-family: var(--font-display);
            font-size: 17px;
            font-weight: 800;
            color: var(--ink);
            white-space: nowrap;
        }
        .xp-footer-fare.free { color: var(--success-ink); }
        .xp-footer-vehicle {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .xp-desktop-fare-panel {
            display: none;
            min-width: 0;
        }
        .xp-fare-label {
            margin: 0 0 3px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .xp-fare-price {
            margin: 0;
            font-family: var(--font-display);
            font-size: 29px;
            font-weight: 900;
            line-height: 1;
            color: var(--ink);
        }
        .xp-fare-price.free { color: var(--success-ink); }
        .xp-fare-note {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }
        .xp-fare-actions {
            display: grid;
            gap: 8px;
            margin-top: auto;
        }

        /* ── Right: sticky map panel ───────────────────────────────── */
        .xp-map-panel {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-1);
            position: sticky;
            top: 82px;
            height: fit-content;
        }
        .xp-map-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            border-bottom: 1px solid var(--hairline);
            gap: 8px;
        }
        .xp-map-panel-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
            margin: 0;
        }
        #explore-map {
            width: 100%;
            height: 350px;
            background: #e5e7eb;
        }
        #explore-map .leaflet-control-attribution {
            display: none;
        }
        .xp-map-pin {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 3px solid #fff;
            display: block;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .24);
        }
        .xp-map-pin.pickup {
            background: #16a34a;
        }
        .xp-map-pin.destination {
            background: #1e293b;
        }
        .xp-map-popup {
            display: grid;
            gap: 7px;
            min-width: 180px;
            max-width: 220px;
            color: var(--ink);
            font-family: var(--font-ui), sans-serif;
        }
        .xp-map-popup-title {
            font-size: 12px;
            font-weight: 900;
            line-height: 1.25;
        }
        .xp-map-popup-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .xp-map-popup-pill {
            border: 1px solid var(--hairline);
            border-radius: 999px;
            background: var(--surface-2);
            padding: 2px 6px;
            font-size: 10.5px;
            font-weight: 800;
            color: var(--ink-2);
            line-height: 1.35;
        }
        .xp-map-popup-route {
            color: var(--muted);
            font-size: 10.5px;
            font-weight: 700;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .xp-map-popup-list {
            display: grid;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .xp-map-popup-list li {
            border-top: 1px solid var(--hairline);
            padding-top: 6px;
            display: grid;
            gap: 5px;
        }
        .xp-map-popup-trip-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .xp-map-popup-driver {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 12px;
            font-weight: 900;
        }
        .xp-map-popup-action {
            min-height: 30px;
            border-radius: 8px;
            border: 1px solid var(--ch-yellow-line);
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            font: inherit;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
            cursor: pointer;
            box-shadow: 0 6px 14px rgba(250, 204, 21, .18);
        }
        .xp-map-popup-action:hover {
            border-color: var(--ch-yellow-deep);
            background: var(--ch-yellow-deep);
        }
        .xp-map-popup-link {
            min-height: 30px;
            border-radius: 8px;
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink) !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 9px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 900;
        }
        .xp-map-empty {
            min-height: 350px;
            display: grid;
            place-items: center;
            padding: 20px;
            color: var(--muted);
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }
        .xp-map-legend {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            padding: 10px 16px;
            border-top: 1px solid var(--hairline);
            background: var(--surface-2);
        }
        .xp-map-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--muted);
            font-weight: 600;
        }
        .xp-legend-dot {
            width: 9px;
            height: 9px;
            border-radius: var(--r-pill);
            flex-shrink: 0;
        }
        .xp-legend-dot.pickup      { background: #16a34a; }
        .xp-legend-dot.destination { background: #1e293b; }
        .xp-legend-dot.custom      { background: var(--ch-yellow-deep); }

        /* ── Pagination ────────────────────────────────────────────── */
        .xp-pagination { display: flex; justify-content: center; padding-top: 4px; }

        .xp-modal {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(11, 18, 32, .58);
            overflow: hidden;
        }
        .xp-modal.is-open { display: flex; }
        body.explore-modal-open .mobile-header,
        body.explore-modal-open .mobile-bottom-nav,
        body.explore-modal-open .desktop-topbar,
        body.explore-modal-open .desktop-sidebar {
            pointer-events: none;
        }
        .xp-modal-card {
            width: min(700px, 100%);
            max-height: min(calc(100dvh - 36px), 760px);
            border-radius: 20px;
            background: var(--surface);
            border: 1px solid var(--hairline);
            box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
            overflow: hidden;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto;
            position: relative;
            z-index: 1;
        }
        @media (min-width: 640px) {
            .xp-modal-card {
                transform: none;
            }
        }
        @media (min-width: 1024px) {
            .xp-modal {
                padding-left: 18px;
            }
        }
        .xp-modal-head {
            padding: 14px 18px 12px;
            border-bottom: 1px solid var(--hairline);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }
        .xp-modal-title {
            margin: 0;
            color: var(--ink);
            font: 900 18px/1.15 var(--font-display), sans-serif;
        }
        .xp-modal-sub {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 750;
            line-height: 1.35;
        }
        .xp-modal-close {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex: 0 0 auto;
        }
        .xp-modal-body {
            min-height: 0;
            overflow: auto;
            padding: 16px 18px 24px;
            display: grid;
            gap: 12px;
            overscroll-behavior: contain;
        }
        .xp-modal-driver {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .xp-modal-avatar {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            color: var(--ch-yellow-ink);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 900;
            flex: 0 0 auto;
        }
        .xp-modal-kv {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .xp-modal-kv-item {
            border: 1px solid var(--hairline);
            border-radius: 13px;
            background: var(--surface-2);
            padding: 11px 12px;
            display: grid;
            gap: 2px;
        }
        .xp-modal-kv-item span {
            color: var(--muted);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .xp-modal-kv-item strong {
            color: var(--ink);
            font-size: 13px;
            font-weight: 900;
            line-height: 1.25;
            word-break: break-word;
        }
        .xp-modal-route {
            border: 1px solid var(--hairline);
            border-radius: 14px;
            padding: 14px;
            display: grid;
            gap: 13px;
        }
        .xp-modal-point {
            display: grid;
            grid-template-columns: 18px minmax(0, 1fr);
            gap: 9px;
            align-items: start;
        }
        .xp-modal-point-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            margin-top: 4px;
            background: #16a34a;
            box-shadow: 0 0 0 4px #dcfce7;
        }
        .xp-modal-point-dot.dest {
            background: #1e293b;
            box-shadow: 0 0 0 4px #e2e8f0;
        }
        .xp-modal-point span {
            display: block;
            color: var(--muted);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .xp-modal-point strong {
            display: block;
            margin-top: 2px;
            color: var(--ink);
            font-size: 13px;
            font-weight: 900;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }
        .xp-modal-join-fields {
            display: grid;
            gap: 8px;
        }
        .xp-modal-pref {
            border: 1px solid var(--hairline);
            border-radius: 14px;
            background: var(--surface);
            padding: 12px;
            display: grid;
            gap: 10px;
        }
        .xp-modal-pref-title {
            margin: 0;
            color: var(--ink);
            font-size: 13px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .xp-modal-pref-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .xp-modal-pref-group {
            display: grid;
            gap: 7px;
            min-width: 0;
        }
        .xp-modal-pref-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .xp-modal-radio-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
        }
        .xp-modal-radio {
            border: 1px solid var(--hairline-strong);
            border-radius: 10px;
            background: var(--surface-2);
            color: var(--ink-2);
            padding: 8px;
            font-size: 12px;
            font-weight: 850;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .xp-modal-radio:has(input:checked) {
            border-color: var(--ch-yellow-deep);
            background: var(--ch-yellow-tint);
            color: var(--ch-yellow-ink);
        }
        .xp-modal-radio input {
            accent-color: var(--ch-yellow-deep);
            margin: 0;
        }
        .xp-modal-input {
            width: 100%;
            min-height: 40px;
            border-radius: 10px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            padding: 0 11px;
            font: inherit;
            font-size: 13px;
            outline: none;
        }
        .xp-modal-input[hidden] {
            display: none;
        }
        .xp-modal-help {
            margin: 0;
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            line-height: 1.35;
        }
        .xp-modal-fare-preview {
            border: 1px solid var(--ch-yellow-line);
            border-radius: 12px;
            background: var(--ch-yellow-tint);
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .xp-modal-fare-preview span {
            color: var(--warning-ink);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .xp-modal-fare-preview strong {
            color: var(--ch-yellow-ink);
            font-size: 15px;
            font-weight: 950;
            white-space: nowrap;
        }
        .request-map-card {
            border: 1px solid var(--hairline);
            border-radius: 14px;
            background: var(--surface-2);
            padding: 12px;
            display: grid;
            gap: 10px;
        }
        .request-map-card[hidden] {
            display: none;
        }
        .request-map-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .request-map-title {
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .request-map-targets {
            display: inline-flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .request-map-target {
            border: 1px solid var(--hairline-strong);
            border-radius: 999px;
            background: var(--surface);
            color: var(--ink-2);
            height: 30px;
            padding: 0 12px;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
        }
        .request-map-target.active {
            border-color: var(--ink);
            background: var(--ink);
            color: #fff;
        }
        .request-route-map {
            width: 100%;
            height: 260px;
            border: 1px solid var(--hairline);
            border-radius: 10px;
            overflow: hidden;
            background: #eef2f7;
        }
        .request-route-map .leaflet-control-attribution {
            display: none;
        }
        .request-map-legend {
            display: flex;
            align-items: center;
            gap: 7px 10px;
            flex-wrap: wrap;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
        }
        .request-map-legend span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .request-map-legend i {
            width: 18px;
            height: 5px;
            border-radius: 999px;
            display: inline-block;
        }
        .legend-route { background: #94a3b8; }
        .legend-preview { background: #1d4ed8; }
        .legend-zone { background: rgba(15, 23, 42, .18); }
        .legend-pin {
            width: 8px !important;
            height: 8px !important;
            background: var(--ink);
        }
        .request-map-status {
            border: 1px solid var(--hairline);
            background: var(--surface);
            border-radius: 10px;
            color: var(--ink-3);
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.4;
        }
        .request-map-status.blocked {
            border-color: #fecaca;
            background: var(--danger-soft);
            color: var(--danger-ink);
        }
        .request-map-status.ok {
            border-color: #86efac;
            background: var(--success-soft);
            color: var(--success-ink);
        }
        .request-fare-preview {
            border: 1px solid var(--ch-yellow-line);
            border-radius: 10px;
            background: var(--ch-yellow-tint);
            padding: 10px;
            display: grid;
            gap: 8px;
        }
        .request-fare-preview-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .request-fare-preview-title {
            color: var(--warning-ink);
            font-size: 12px;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .request-fare-preview-badge {
            border: 1px solid #fcd34d;
            border-radius: 999px;
            background: rgba(255,255,255,.75);
            color: var(--warning-ink);
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 900;
        }
        .request-fare-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 7px;
        }
        .request-fare-item {
            border: 1px solid var(--ch-yellow-line);
            border-radius: 10px;
            background: rgba(255,255,255,.72);
            padding: 8px;
            display: grid;
            gap: 2px;
        }
        .request-fare-label {
            color: var(--warning);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .request-fare-value {
            color: var(--ch-yellow-ink);
            font-size: 15px;
            font-weight: 900;
        }
        .request-fare-note {
            margin: 0;
            color: var(--warning-ink);
            font-size: 12px;
            line-height: 1.4;
        }
        .passenger-pin-icon {
            position: relative;
            width: 25px;
            height: 25px;
            border-radius: 999px 999px 999px 4px;
            transform: rotate(-45deg);
            border: 2px solid #fff;
            box-shadow: 0 6px 12px rgba(15, 23, 42, .28), 0 0 0 1px rgba(15, 23, 42, .14);
            display: block;
        }
        .passenger-pin-icon.pickup { background: #7c3aed; }
        .passenger-pin-icon.dropoff { background: #ea580c; }
        .passenger-pin-icon::after {
            content: "";
            position: absolute;
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #fff;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
        .xp-modal-note {
            width: 100%;
            min-height: 76px;
            border-radius: 12px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            padding: 10px 12px;
            resize: vertical;
            font: inherit;
            font-size: 13px;
            outline: none;
        }
        .xp-modal-foot {
            border-top: 1px solid var(--hairline);
            background: rgba(255,255,255,.96);
            padding: 12px 18px;
            display: grid;
            gap: 8px;
            position: relative;
            z-index: 1;
        }
        .xp-modal-join-btn {
            width: 100%;
            min-height: 46px;
            border: 1px solid var(--ch-yellow-deep);
            border-radius: 13px;
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .xp-modal-join-btn:disabled {
            border-color: var(--hairline-strong);
            background: var(--surface-2);
            color: var(--muted);
            cursor: default;
        }
        .xp-modal-feedback {
            color: var(--success-ink);
            font-size: 12px;
            font-weight: 800;
            text-align: center;
        }

        @media (max-width: 639px) {
            .xp-modal {
                inset: 0 !important;
                display: none;
                align-items: flex-start !important;
                justify-content: center !important;
                padding: 84px 12px 104px !important;
                background: rgba(11, 18, 32, .58);
            }
            .xp-modal.is-open {
                display: flex !important;
            }
            .xp-modal-card {
                width: 100% !important;
                height: calc(100svh - 96px - 118px) !important;
                max-height: calc(100svh - 96px - 118px) !important;
                border-radius: 18px;
                border: 1px solid var(--hairline);
                overflow: hidden;
                box-shadow: 0 22px 58px rgba(15, 23, 42, .32);
            }
            .xp-modal-head {
                padding: 10px 14px 9px;
                align-items: center;
                position: relative;
                z-index: 2;
                background: var(--surface);
            }
            .xp-modal-title {
                font-size: 15px;
                line-height: 1.2;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .xp-modal-sub {
                font-size: 11px;
            }
            .xp-modal-close {
                width: 34px;
                height: 34px;
                border-radius: 11px;
            }
            .xp-modal-body {
                padding: 12px 14px 18px;
                gap: 10px;
            }
            .xp-modal-driver {
                gap: 9px;
            }
            .xp-modal-avatar {
                width: 38px;
                height: 38px;
            }
            .xp-modal-kv {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
            }
            .xp-modal-kv-item {
                padding: 10px;
                min-width: 0;
            }
            .xp-modal-kv-item strong {
                font-size: 12px;
            }
            .xp-modal-route {
                padding: 11px;
                gap: 12px;
            }
            .xp-modal-point {
                gap: 8px;
            }
            .xp-modal-point strong {
                font-size: 12.5px;
                line-height: 1.28;
            }
            .xp-modal-note {
                min-height: 64px;
                max-height: 96px;
            }
            .xp-modal-pref-grid {
                grid-template-columns: 1fr;
                gap: 9px;
            }
            .xp-modal-foot {
                padding: 10px 14px 12px;
                box-shadow: 0 -10px 24px rgba(15, 23, 42, .08);
                position: relative;
                z-index: 3;
            }
            .xp-modal-join-btn {
                min-height: 42px;
            }
        }

        @media (min-width: 640px) and (max-width: 1023px) {
            .xp-modal {
                inset: 0;
                padding-top: 90px;
                padding-bottom: 24px;
            }
        }

        @media (max-width: 390px) {
            .xp-modal-kv {
                grid-template-columns: 1fr;
            }
        }

        /* ── Mobile overrides ──────────────────────────────────────── */
        @media (max-width: 1023px) {
            .xp-wrap {
                padding: 12px 10px 104px;
                max-width: 430px;
                background: var(--canvas);
            }
            .xp-page-header { display: none; }
            .xp-search-card {
                border: 0;
                border-radius: 12px 12px 0 0;
                padding: 12px 12px 0;
                background: #fff;
                box-shadow: none;
                gap: 8px;
                position: relative;
            }
            .xp-search-field {
                min-width: 100%;
                height: 40px;
                background: #fff;
                border-color: var(--hairline-strong);
                border-radius: 7px;
                padding: 0 11px;
                box-shadow: 0 1px 0 rgba(15, 23, 42, .03);
            }
            .xp-search-card .xp-search-field:first-child {
                padding-right: 50px;
            }
            .xp-search-separator {
                width: 32px;
                height: 32px;
                margin: 0;
                border: 1px solid var(--hairline-strong);
                border-radius: 8px;
                background: #fff;
                position: absolute;
                top: 16px;
                right: 20px;
                z-index: 2;
                box-shadow: 0 2px 6px rgba(15, 23, 42, .05);
            }
            .xp-search-date {
                flex: 1 1 100%;
                min-width: 100%;
                height: 40px;
                background: #fff;
                border-radius: 7px;
                padding: 0 11px;
                box-shadow: 0 1px 0 rgba(15, 23, 42, .03);
            }
            .xp-search-card .btn {
                height: 38px;
                padding: 0 15px;
                border-radius: 9px;
                gap: 7px;
            }
            .xp-search-card button.btn {
                flex: 1 1 0;
                order: 5;
                background: var(--ch-yellow);
                color: var(--ch-yellow-ink);
                border-color: var(--ch-yellow);
                font-weight: 900;
            }
            .xp-search-card button.btn:hover {
                background: var(--ch-yellow-deep);
            }
            .xp-advanced-mobile {
                display: inline-flex;
                flex: 1 1 0;
                order: 4;
                background: #fff;
                color: var(--ink);
                border-color: var(--hairline-strong);
                font-weight: 800;
            }
            .xp-desktop-label { display: none; }
            .xp-mobile-label { display: inline; }
            .xp-mobile-chip { display: inline-flex; }
            .xp-desktop-chip,
            .xp-sort-label,
            .xp-chips-spacer {
                display: none !important;
            }
            .xp-chips-row {
                padding-top: 11px;
                gap: 6px;
                margin-left: 0;
                margin-right: 0;
                padding-left: 12px;
                padding-right: 12px;
                padding-bottom: 12px;
                background: #fff;
                border-radius: 0 0 12px 12px;
            }
            .xp-chips-row .chip {
                min-height: 31px;
                padding: 0 12px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                white-space: nowrap;
                line-height: 1;
                flex: 0 0 auto;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 800;
            }
            .xp-chips-row .chip.active {
                background: var(--ch-yellow);
                border-color: var(--ch-yellow);
                color: var(--ch-yellow-ink);
            }
            .xp-sort-label {
                display: none !important;
            }
            .xp-chips-row .xp-desktop-chip[href*="sort"] {
                display: inline-flex !important;
            }
            .xp-body { padding-top: 12px; gap: 12px; }
            .xp-list { gap: 10px; }
            .xp-card {
                border-radius: 13px;
                border-color: var(--hairline);
                box-shadow: 0 5px 12px rgba(15,23,42,.06);
            }
            .xp-card:hover { transform: none; }
            .xp-card-body { padding: 12px 13px 11px; gap: 0; }
            .xp-card-main { display: grid; gap: 9px; }
            .xp-avatar {
                width: 26px;
                height: 26px;
                font-size: 10px;
                background: #fff7d6;
            }
            .xp-driver-row {
                display: grid;
                grid-template-columns: 26px minmax(0, 1fr) auto;
                align-items: center;
                gap: 8px;
            }
            .xp-driver-info { min-width: 0; }
            .xp-driver-name {
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                font-size: 12.5px;
                font-weight: 800;
                line-height: 1.1;
            }
            .xp-driver-rating {
                font-size: 10px;
                line-height: 1.1;
            }
            .xp-driver-row .badge:not(.xp-driver-badge) {
                display: none;
            }
            .xp-driver-badge {
                grid-column: 3;
                grid-row: 1;
                max-width: 72px;
                justify-self: end;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                padding: 4px 10px;
                font-size: 10px;
                border-radius: 999px;
            }
            .xp-route-timeline {
                min-width: 0;
                gap: 0;
                padding-left: 1px;
                position: relative;
            }
            .xp-route-timeline::before {
                content: "";
                position: absolute;
                left: 5px;
                top: 12px;
                bottom: 12px;
                width: 0;
                border-left: 2px dotted #b8c6d8;
            }
            .xp-timeline-row {
                min-width: 0;
                display: grid;
                grid-template-columns: 12px minmax(0, 1fr);
                align-items: center;
                gap: 7px;
                min-height: 34px;
            }
            .xp-timeline-track {
                width: 12px;
                padding-top: 0;
                align-items: center;
                justify-content: center;
            }
            .xp-timeline-dot {
                width: 12px;
                height: 12px;
                border: 2px solid #dbeafe;
                position: relative;
                z-index: 1;
            }
            .xp-timeline-text,
            .xp-timeline-note {
                display: grid;
                gap: 1px;
                max-width: 100%;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                font-size: 12px;
                font-weight: 800;
                line-height: 1.15;
                padding-top: 0;
            }
            .xp-mobile-route-label {
                display: block;
                color: #64748b;
                font-size: 9px;
                font-weight: 900;
                letter-spacing: .08em;
                text-transform: uppercase;
                line-height: 1;
            }
            .xp-timeline-value {
                display: block;
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .xp-timeline-line {
                display: none;
            }
            .xp-card-footer {
                display: grid;
                grid-template-columns: auto auto minmax(0, 1fr) auto auto;
                gap: 5px;
                align-items: center;
                border-top-style: dashed;
                padding-top: 11px;
            }
            .xp-footer-time,
            .xp-footer-seats {
                font-size: 11px;
                color: var(--muted);
                font-weight: 700;
            }
            .xp-footer-fare {
                margin-left: 0;
                justify-self: end;
                font-size: 14.5px;
                font-weight: 900;
            }
            .xp-card-footer .btn {
                min-width: 68px;
                height: 31px;
                padding-left: 10px;
                padding-right: 10px;
                border-radius: 9px;
                font-size: 11px;
                font-weight: 900;
            }
            .xp-map-panel { display: none; }
        }

        @media (min-width: 1024px) {
            .xp-mobile-label { display: none; }
            .xp-mobile-route-label { display: none; }
            .xp-mobile-chip { display: none !important; }
            .xp-desktop-label { display: inline; }
            .xp-list {
                max-width: 700px;
                width: 100%;
            }
            .xp-card {
                border-color: var(--ch-yellow-line);
                max-width: 700px;
            }
            .xp-card-body {
                grid-template-columns: minmax(0, 1fr) 200px;
                gap: 0;
                padding: 0;
            }
            .xp-card-main {
                padding: 14px 22px 12px;
                overflow: hidden;
            }
            .xp-driver-row {
                display: grid;
                grid-template-columns: 38px minmax(0, 1fr);
                align-items: center;
                gap: 12px;
            }
            .xp-driver-info { min-width: 0; }
            .xp-avatar {
                width: 38px;
                height: 38px;
                font-size: 12px;
                background: var(--surface);
            }
            .xp-driver-name {
                font-size: 15px;
                line-height: 1.15;
            }
            .xp-driver-rating {
                font-size: 11px;
            }
            .xp-driver-badge {
                margin-left: 0;
                max-width: 82px;
                justify-self: end;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .xp-driver-row .xp-driver-badge {
                display: none;
            }
            .xp-route-timeline {
                gap: 2px;
                padding-left: 3px;
            }
            .xp-timeline-row {
                display: grid;
                grid-template-columns: 16px minmax(0, 1fr);
                gap: 10px;
                min-width: 0;
            }
            .xp-timeline-track {
                width: 16px;
            }
            .xp-timeline-dot {
                width: 13px;
                height: 13px;
            }
            .xp-timeline-line {
                height: 16px;
                border-left: 2px dotted #cbd5e1;
                background: transparent;
                width: 0;
            }
            .xp-timeline-text {
                display: grid;
                gap: 3px;
                min-width: 0;
                font-size: 13.5px;
                font-weight: 800;
                line-height: 1.35;
                white-space: normal;
            }
            .xp-timeline-value {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .xp-timeline-text .xp-desktop-label,
            .xp-timeline-note .xp-desktop-label {
                display: block;
                margin: 0;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: .08em;
                color: var(--muted);
            }
            .xp-timeline-note {
                display: grid;
                gap: 3px;
                min-width: 0;
                font-size: 13px;
                color: var(--ink);
                font-weight: 800;
                white-space: normal;
            }
            .xp-card-footer {
                border-top: 0;
                padding-top: 2px;
                display: grid;
                grid-template-columns: 95px 105px minmax(130px, 1fr);
                gap: 12px;
                color: var(--muted);
                min-width: 0;
                align-items: start;
            }
            .xp-card-footer .xp-footer-dot,
            .xp-card-footer .xp-footer-fare,
            .xp-card-footer .btn {
                display: none;
            }
            .xp-footer-time,
            .xp-footer-seats,
            .xp-footer-vehicle {
                display: block;
                min-width: 0;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: normal;
                font-family: var(--font-sans);
                font-size: 11.5px;
                font-weight: 700;
                line-height: 1.25;
                color: #64748b;
            }
            .xp-footer-time i,
            .xp-footer-seats i,
            .xp-footer-vehicle i {
                margin-right: 5px;
                color: #94a3b8;
                font-size: 10px;
            }
            .xp-footer-vehicle {
                font-weight: 600;
                letter-spacing: .02em;
            }
            .xp-vehicle-model,
            .xp-vehicle-plate {
                display: block;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .xp-desktop-fare-panel {
                display: flex;
                flex-direction: column;
                padding: 22px 18px 18px;
                border-left: 1px solid var(--hairline);
                background: var(--surface-2);
            }
            .xp-fare-price {
                font-size: 26px;
            }
            .xp-fare-note {
                margin-top: 6px;
            }
            .xp-fare-actions {
                gap: 7px;
            }
            .xp-desktop-fare-panel .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (min-width: 1024px) and (max-width: 1180px) {
            .xp-body { grid-template-columns: 1fr; }
            .xp-list {
                max-width: 780px;
                width: 100%;
            }
            .xp-map-panel { display: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .xp-card { transition: none !important; }
        }
    </style>

    @php
        $aiRecommendationMap = $aiRecommendationMap ?? [];
        $recommendedTripIds  = $recommendedTripIds ?? [];
        $activeTimeframe     = $filters['timeframe'] ?? request('timeframe', '');
        $activeSeats         = $filters['seats']     ?? request('seats', '');
        $activeFareMax       = $filters['fare_max']  ?? request('fare_max', '');
        $activeConnections   = $filters['connections'] ?? request('connections', '');
        $focusTripId         = (int) request('focus_trip', 0);
        $searchEditQuery     = request()->except(['page', 'focus_trip']);
        $searchDestination   = trim((string) request('destination', ''));
        $searchPickup        = trim((string) request('pickup', ''));
        $searchDate          = trim((string) request('date', ''));
        $searchSeats         = trim((string) request('seats', ''));
        $searchSort          = trim((string) request('sort', 'nearest'));
        $searchRadiusRaw     = request('radius_km');
        $searchRadius        = is_numeric($searchRadiusRaw) ? (float) $searchRadiusRaw : null;
        $searchPickupLat     = is_numeric(request('pickup_lat'))      ? (float) request('pickup_lat')      : null;
        $searchPickupLng     = is_numeric(request('pickup_lng'))      ? (float) request('pickup_lng')      : null;
        $searchDestinationLat = is_numeric(request('destination_lat')) ? (float) request('destination_lat') : null;
        $searchDestinationLng = is_numeric(request('destination_lng')) ? (float) request('destination_lng') : null;
        $hasPickupPin        = $searchPickupLat !== null && $searchPickupLng !== null;
        $hasDestinationPin   = $searchDestinationLat !== null && $searchDestinationLng !== null;
        $hasSearchSummary    = $searchDestination !== '' || $searchPickup !== '' || $searchDate !== '' || $searchSeats !== '' || (request()->filled('center_lat') && request()->filled('center_lng')) || $hasPickupPin || $hasDestinationPin;

        $distanceKm = static function (float $lat1, float $lng1, float $lat2, float $lng2): float {
            $earthKm = 6371;
            $dLat = deg2rad($lat2 - $lat1);
            $dLng = deg2rad($lng2 - $lng1);
            $a = sin($dLat / 2) ** 2
                + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * (sin($dLng / 2) ** 2);
            return $earthKm * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
        };
        $pointToLocalKm = static function (float $lat, float $lng, float $originLat, float $originLng): array {
            return [
                'x' => ($lng - $originLng) * 111.32 * cos(deg2rad($originLat)),
                'y' => ($lat - $originLat) * 110.57,
            ];
        };
        $distanceToSegmentKm = static function (float $pointLat, float $pointLng, float $startLat, float $startLng, float $endLat, float $endLng) use ($pointToLocalKm): float {
            $p = $pointToLocalKm($pointLat, $pointLng, $startLat, $startLng);
            $b = $pointToLocalKm($endLat, $endLng, $startLat, $startLng);
            $lengthSquared = ($b['x'] ** 2) + ($b['y'] ** 2);
            if ($lengthSquared <= 0) {
                return sqrt(($p['x'] ** 2) + ($p['y'] ** 2));
            }
            $t = max(0, min(1, (($p['x'] * $b['x']) + ($p['y'] * $b['y'])) / $lengthSquared));
            $projection = ['x' => $t * $b['x'], 'y' => $t * $b['y']];
            return sqrt((($p['x'] - $projection['x']) ** 2) + (($p['y'] - $projection['y']) ** 2));
        };
        $allowedRadiiForKm = static function (float $routeKm): array {
            if ($routeKm <= 3)  return ['route' => 0.40, 'endpoint' => 0.50];
            if ($routeKm <= 10) return ['route' => 0.70, 'endpoint' => 0.80];
            if ($routeKm <= 25) return ['route' => 1.00, 'endpoint' => 1.20];
            return ['route' => 1.30, 'endpoint' => 1.50];
        };
        $searchPointFit = static function ($trip, ?float $pointLat, ?float $pointLng) use ($distanceKm, $distanceToSegmentKm, $allowedRadiiForKm): ?array {
            if ($pointLat === null || $pointLng === null) return null;
            $pickupLat  = is_numeric($trip->pickup_latitude)       ? (float) $trip->pickup_latitude       : null;
            $pickupLng  = is_numeric($trip->pickup_longitude)      ? (float) $trip->pickup_longitude      : null;
            $dropoffLat = is_numeric($trip->destination_latitude)  ? (float) $trip->destination_latitude  : null;
            $dropoffLng = is_numeric($trip->destination_longitude) ? (float) $trip->destination_longitude : null;
            if ($pickupLat === null || $pickupLng === null || $dropoffLat === null || $dropoffLng === null) {
                return ['state' => 'review', 'distance' => null, 'label' => 'Route map unavailable'];
            }
            $routeKm        = max(0.01, $distanceKm($pickupLat, $pickupLng, $dropoffLat, $dropoffLng));
            $radii          = $allowedRadiiForKm($routeKm);
            $routeDistance  = $distanceToSegmentKm($pointLat, $pointLng, $pickupLat, $pickupLng, $dropoffLat, $dropoffLng);
            $endpointDistance = min(
                $distanceKm($pointLat, $pointLng, $pickupLat, $pickupLng),
                $distanceKm($pointLat, $pointLng, $dropoffLat, $dropoffLng)
            );
            $nearestDistance = min($routeDistance, $endpointDistance);
            $isAllowed = $routeDistance <= $radii['route'] || $endpointDistance <= $radii['endpoint'];
            return [
                'state'           => $isAllowed ? 'ok' : 'blocked',
                'distance'        => $nearestDistance,
                'route_radius'    => $radii['route'],
                'endpoint_radius' => $radii['endpoint'],
                'label'           => $isAllowed
                    ? (number_format($nearestDistance, 2) . ' km from driver route')
                    : (number_format($nearestDistance, 2) . ' km away, needs manual check'),
            ];
        };

        $summaryParts = [];
        if ($searchDestination !== '') {
            $summaryParts[] = 'to ' . \Illuminate\Support\Str::limit($searchDestination, 56, '...');
        }
        if ($searchPickup !== '') {
            $summaryParts[] = 'from ' . \Illuminate\Support\Str::limit($searchPickup, 40, '...');
        }
        if ($searchDate !== '') {
            try {
                $summaryParts[] = 'on ' . \Illuminate\Support\Carbon::parse($searchDate)->format('d M Y');
            } catch (\Throwable $e) {
                $summaryParts[] = 'on ' . $searchDate;
            }
        }
        if ($searchSeats === '1')      { $summaryParts[] = 'with 1 seat'; }
        elseif ($searchSeats === '2plus') { $summaryParts[] = 'with 2+ seats'; }
        if ($activeFareMax !== '' && is_numeric($activeFareMax)) {
            $summaryParts[] = 'under RM ' . number_format((float) $activeFareMax, 0);
        }
        if ($activeConnections === '1') {
            $summaryParts[] = 'from connections';
        }
        if (request()->filled('center_lat') && request()->filled('center_lng') && $searchRadius !== null && $searchRadius > 0) {
            $summaryParts[] = 'within ' . rtrim(rtrim(number_format($searchRadius, 1, '.', ''), '0'), '.') . ' km pin radius';
        }
        if ($hasPickupPin)      { $summaryParts[] = 'pickup pin set'; }
        if ($hasDestinationPin) { $summaryParts[] = 'destination pin set'; }
        if ($searchSort === 'latest') {
            $summaryParts[] = 'sorted by latest date';
        } else {
            $summaryParts[] = 'sorted by nearest date';
        }

        $searchSummaryText  = $summaryParts
            ? ('Showing trips ' . implode(', ', $summaryParts) . '.')
            : 'Tap to search by destination, date, and seats.';
        $searchSummaryTitle = $hasSearchSummary ? 'Search Summary' : 'Where do you want to go?';
        $acceptedConnectionIdsForChips = auth()->user()?->acceptedConnections()->pluck('users.id')->all() ?? [];
        $visibleTripsForChips = $trips->getCollection();
        $publicTripsCount = $visibleTripsForChips->where('visibility', 'public')->count();
        $connectionTripsCount = $visibleTripsForChips->filter(fn ($trip) => in_array((int) $trip->driver_id, $acceptedConnectionIdsForChips, true))->count();
    @endphp

    <div class="xp-wrap">

        {{-- ── Page Header ─────────────────────────────────────────── --}}
        <div class="xp-page-header">
            <div class="xp-header-copy">
                <p class="xp-eyebrow">Discover</p>
                <h1 class="xp-title">Explore public trips</h1>
                <p class="xp-subtitle">Find rides along your usual routes, or request a custom pickup point.</p>
            </div>
            <div class="xp-header-actions">
                <a href="{{ route('explore.search', $searchEditQuery) }}" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-sliders"></i> Advanced filters
                </a>
                <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Offer a trip
                </a>
            </div>
        </div>

        {{-- ── Search bar ───────────────────────────────────────────── --}}
        <form method="GET" action="{{ route('explore.index') }}" class="xp-search-card">
            <div class="xp-search-field">
                <i class="fa-solid fa-location-dot pickup-pin"></i>
                <input type="text" name="pickup" value="{{ $searchPickup }}" placeholder="From" autocomplete="off">
            </div>
            <div class="xp-search-separator">
                <i class="fa-solid fa-arrow-right-arrow-left" style="font-size:13px;"></i>
            </div>
            <div class="xp-search-field">
                <i class="fa-solid fa-location-dot" style="color:var(--ink-3);"></i>
                <input type="text" name="destination" value="{{ $searchDestination }}" placeholder="To" autocomplete="off">
            </div>
            <div class="xp-search-date">
                <i class="fa-regular fa-calendar"></i>
                <input type="date" name="date" value="{{ $searchDate }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;flex-shrink:0;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Search</span>
            </button>
            <a href="{{ route('explore.search', $searchEditQuery) }}" class="btn btn-ghost btn-sm xp-advanced-mobile">
                <i class="fa-solid fa-sliders"></i>
                <span>Advanced</span>
            </a>
        </form>

        {{-- ── Filter chips + sort ─────────────────────────────────── --}}
        <div class="xp-chips-row">
            <a href="{{ route('explore.index') }}"
               class="chip xp-mobile-chip {{ ($activeTimeframe === '' && $activeSeats === '' && $activeFareMax === '' && $activeConnections === '') ? 'active' : '' }}">
                All @if(!$trips->isEmpty()) &middot; {{ $trips->total() }} @endif
            </a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'fare_max']), ['fare_max' => 10])) }}"
               class="chip xp-mobile-chip {{ (string) $activeFareMax === '10' ? 'active' : '' }}">&le; RM 10</a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'seats']), ['seats' => '2plus'])) }}"
               class="chip xp-mobile-chip {{ $activeSeats === '2plus' ? 'active' : '' }}">2+ seats</a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'connections']), ['connections' => 1])) }}"
               class="chip xp-mobile-chip {{ (string) $activeConnections === '1' ? 'active' : '' }}">Connections</a>

            <a href="{{ route('explore.index') }}"
               class="chip xp-desktop-chip {{ ($activeTimeframe === '' && $activeSeats === '' && $activeFareMax === '' && $activeConnections === '') ? 'active' : '' }}">
                All trips @if(!$trips->isEmpty()) &middot; {{ $trips->total() }} @endif
            </a>
            <a href="{{ route('explore.index', request()->except(['page', 'connections', 'fare_max', 'seats'])) }}"
               class="chip xp-desktop-chip">
                Public Trips &middot; {{ $publicTripsCount }}
            </a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'connections']), ['connections' => 1])) }}"
               class="chip xp-desktop-chip {{ (string) $activeConnections === '1' ? 'active' : '' }}">
                Within Connections &middot; {{ $connectionTripsCount }}
            </a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'fare_max']), ['fare_max' => 10])) }}"
               class="chip xp-desktop-chip {{ (string) $activeFareMax === '10' ? 'active' : '' }}">&le; RM 10</a>
            <a href="{{ route('explore.index', array_merge(request()->except(['page', 'seats']), ['seats' => '2plus'])) }}"
               class="chip xp-desktop-chip {{ $activeSeats === '2plus' ? 'active' : '' }}">2+ seats</a>
            <a href="{{ route('explore.index', request()->except(['page'])) }}"
               class="chip xp-desktop-chip">Auto-approve</a>
            @if($hasSearchSummary || $activeTimeframe !== '' || $activeSeats !== '' || $activeFareMax !== '' || $activeConnections !== '')
                <a href="{{ route('explore.index') }}" class="chip xp-desktop-chip" style="color:var(--danger);border-color:var(--danger-soft);">
                    <i class="fa-solid fa-xmark"></i> Clear
                </a>
            @endif

            @if(!$trips->isEmpty())
                <div class="xp-chips-spacer"></div>
                <span class="xp-sort-label">Sort:</span>
                <a href="{{ route('explore.index', array_merge(request()->except(['page', 'sort']), ['sort' => 'nearest'])) }}"
                   class="chip {{ ($searchSort === 'nearest' || $searchSort === '') ? 'active' : '' }}">Nearest Date</a>
                <a href="{{ route('explore.index', array_merge(request()->except(['page', 'sort']), ['sort' => 'latest'])) }}"
                   class="chip {{ $searchSort === 'latest' ? 'active' : '' }}">Latest Date</a>
            @endif
        </div>

        {{-- ── Main body: list + map ───────────────────────────────── --}}
        <div class="xp-body">

            {{-- Left: trip list --}}
            <div class="xp-list" id="xp-real-list">
                {{-- Skeleton placeholder (hidden, shown on filter submit) --}}
                <div id="xp-skel-list" style="display:none;grid-gap:12px;display:none;">
                    @for($sk = 0; $sk < 5; $sk++)
                        <div class="xp-skel-card">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span class="sk" style="width:40px;height:40px;border-radius:999px;flex-shrink:0;"></span>
                                <div style="flex:1;display:grid;gap:6px;">
                                    <span class="sk" style="height:13px;width:{{ [55,70,48,62,58][$sk] }}%;"></span>
                                    <span class="sk" style="height:11px;width:{{ [38,28,42,32,36][$sk] }}%;"></span>
                                </div>
                                <span class="sk" style="width:62px;height:24px;border-radius:var(--r-pill);"></span>
                            </div>
                            <div style="display:grid;gap:7px;padding:4px 0;">
                                <span class="sk" style="height:12px;width:88%;"></span>
                                <span class="sk" style="height:12px;width:{{ [72,80,68,75,70][$sk] }}%;"></span>
                            </div>
                            <div style="display:flex;gap:8px;">
                                <span class="sk" style="height:28px;flex:1;border-radius:var(--r-pill);"></span>
                                <span class="sk" style="height:28px;flex:1;border-radius:var(--r-pill);"></span>
                                <span class="sk" style="height:28px;flex:1;border-radius:var(--r-pill);"></span>
                            </div>
                        </div>
                    @endfor
                </div>
                @if($trips->isEmpty())
                    <x-empty 
                        icon="fa-solid fa-compass" 
                        title="No trips found" 
                        body="No public trips match your filters right now. Try changing your search or check back later." 
                        style="box-shadow:none; border:none; background:transparent; padding:48px 24px;"
                    >
                        <a href="{{ route('explore.index') }}" class="btn btn-soft" style="margin-top:8px;">Clear Filters</a>
                    </x-empty>
                @else
                    @foreach($trips as $trip)
                        @php
                            $routeName         = $trip->savedRoute?->route_name ?: (($trip->pickup_name ?? 'Pickup') . ' → ' . ($trip->destination_name ?? 'Destination'));
                            $pickupText        = $trip->pickup_name ?? 'Pickup';
                            $destinationText   = $trip->destination_name ?? 'Destination';
                            $pickupShortText   = \Illuminate\Support\Str::limit($pickupText, 52, '...');
                            $destShortText     = \Illuminate\Support\Str::limit($destinationText, 52, '...');
                            $tripTypeText      = ((string) ($trip->trip_mode ?? 'one_way')) === 'two_way' ? 'Two Way' : 'One Way';
                            $visibilityText    = ucfirst((string) ($trip->visibility ?? 'public')) . ' Trip';
                            $visibilityShortText = ucfirst((string) ($trip->visibility ?? 'public'));
                            $visibilityBadge   = ($trip->visibility ?? 'public') === 'public' ? 'badge-info' : 'badge-dark';
                            $takenSeats        = (int) $trip->participants->where('is_driver', false)->count();
                            $availableSeats    = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : null;
                            $isFull            = $availableSeats !== null && $availableSeats <= 0;
                            $isFree            = (float) $trip->fare_per_person <= 0;
                            $myRequest         = $trip->joinRequests->first();
                            $isJoined          = $trip->participants->contains(fn ($p) => (int) $p->user_id === (int) auth()->id());
                            $aiRecommendation  = $aiRecommendationMap[$trip->id] ?? null;
                            $isRecommended     = in_array((int) $trip->id, $recommendedTripIds, true);
                            $driverInitial     = strtoupper(substr($trip->driver?->name ?? '?', 0, 2));
                            $vehicleModel      = trim((string) ($trip->driver?->vehicle_model ?? ''));
                            $vehiclePlate      = trim((string) ($trip->driver?->vehicle_plate ?? ''));
                            $joinState         = $isJoined ? 'joined' : (($myRequest && $myRequest->status === 'pending') ? 'pending' : ($isFull ? 'full' : (! $trip->is_open_for_request ? 'closed' : 'open')));
                            $vehicleText       = trim($vehicleModel . ($vehicleModel && $vehiclePlate ? ' · ' : '') . $vehiclePlate);
                        @endphp

                        <article
                            id="exploreTripCard{{ $trip->id }}"
                            class="xp-card {{ $focusTripId === (int) $trip->id ? 'is-focus' : '' }} {{ $isRecommended ? 'is-recommended' : '' }} open-explore-card"
                            data-trip-url="{{ route('explore.show', $trip) }}"
                            data-join-url="{{ route('explore.request-join', $trip) }}"
                            data-join-state="{{ $joinState }}"
                            data-driver="{{ $trip->driver?->name ?: 'Driver' }}"
                            data-driver-initial="{{ $driverInitial }}"
                            data-rating="{{ number_format($trip->driver?->rating ?? 5.0, 2) }}"
                            data-route-name="{{ $routeName }}"
                            data-pickup="{{ $pickupText }}"
                            data-pickup-lat="{{ $trip->pickup_latitude ?? '' }}"
                            data-pickup-lng="{{ $trip->pickup_longitude ?? '' }}"
                            data-destination="{{ $destinationText }}"
                            data-destination-lat="{{ $trip->destination_latitude ?? '' }}"
                            data-destination-lng="{{ $trip->destination_longitude ?? '' }}"
                            data-time="{{ $trip->trip_datetime?->format('d M Y, H:i') ?: '-' }}"
                            data-seats="{{ $availableSeats !== null ? $availableSeats : '?' }} / {{ (int) $trip->seat_limit }} seats"
                            data-fare="{{ $isFree ? 'Free' : ('RM ' . number_format((float) $trip->fare_per_person, 2)) }}"
                            data-fare-raw="{{ number_format((float) $trip->fare_per_person, 2, '.', '') }}"
                            data-fare-total="{{ number_format((float) ($trip->fare_total ?? $trip->savedRoute?->default_fare ?? $trip->fare_per_person), 2, '.', '') }}"
                            data-vehicle="{{ $vehicleText !== '' ? $vehicleText : 'Vehicle not set' }}"
                            data-visibility="{{ $visibilityText }}"
                            tabindex="0"
                            role="button"
                            @if($focusTripId === (int) $trip->id) data-explore-focus-card="1" @endif
                        >
                            @if($isRecommended)
                            <div class="xp-rec-strip">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                AI Recommended
                                @if(!empty($aiRecommendation['explanations']))
                                    @foreach(array_slice($aiRecommendation['explanations'], 0, 2) as $reason)
                                        <span class="xp-rec-pill">{{ $reason }}</span>
                                    @endforeach
                                @endif
                            </div>
                            @endif
                            <div class="xp-card-body">
                                <div class="xp-card-main">

                                {{-- Driver row --}}
                                    <div class="xp-driver-row">
                                    <span class="xp-avatar">{{ $driverInitial }}</span>
                                    <div class="xp-driver-info">
                                        <span class="xp-driver-name">{{ $trip->driver?->name ?: '—' }}</span>
                                        <span class="xp-driver-rating">
                                            <i class="fa-solid fa-star"></i>
                                            {{ number_format($trip->driver?->rating ?? 5.0, 2) }}
                                            <span class="xp-desktop-label">&middot; {{ $trip->driver?->trips_count ?? 0 }} trips</span>
                                        </span>
                                    </div>
                                    <span class="badge {{ $visibilityBadge }} xp-driver-badge">
                                        <span class="xp-desktop-label">{{ $visibilityText }}</span>
                                        <span class="xp-mobile-label">{{ $visibilityShortText }}</span>
                                    </span>
                                    </div>

                                {{-- Route timeline --}}
                                    <div class="xp-route-timeline">
                                    {{-- Pickup --}}
                                    <div class="xp-timeline-row">
                                        <div class="xp-timeline-track">
                                            <span class="xp-timeline-dot pickup"></span>
                                            <span class="xp-timeline-line"></span>
                                        </div>
                                        <span class="xp-timeline-text">
                                            <span class="xp-desktop-label">PICKUP</span>
                                            <span class="xp-mobile-route-label">Pickup</span>
                                            <span class="xp-timeline-value">{{ $pickupShortText }}</span>
                                        </span>
                                    </div>

                                    {{-- Optional note stop --}}
                                    @if($trip->public_note)
                                        <div class="xp-timeline-row">
                                            <div class="xp-timeline-track">
                                                <span class="xp-timeline-dot custom"></span>
                                                <span class="xp-timeline-line"></span>
                                            </div>
                                            <span class="xp-timeline-note">
                                                <span class="xp-desktop-label">CUSTOM STOP</span>
                                                <span class="xp-mobile-route-label">Custom stop</span>
                                                <span class="xp-timeline-value">{{ \Illuminate\Support\Str::limit($trip->public_note, 70, '...') }}</span>
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Destination --}}
                                    <div class="xp-timeline-row">
                                        <div class="xp-timeline-track">
                                            <span class="xp-timeline-dot dest"></span>
                                        </div>
                                        <span class="xp-timeline-text">
                                            <span class="xp-desktop-label">DESTINATION</span>
                                            <span class="xp-mobile-route-label">Destination</span>
                                            <span class="xp-timeline-value">{{ $destShortText }}</span>
                                        </span>
                                    </div>
                                    </div>

                                {{-- Footer: time · seats | fare + button --}}
                                    <div class="xp-card-footer">
                                    <span class="xp-footer-time">
                                        <span class="xp-desktop-label">
                                            <i class="fa-regular fa-clock"></i>{{ $trip->trip_datetime?->format('d M Y') ?: '—' }}<br>
                                            <span style="padding-left:15px;">{{ $trip->trip_datetime?->format('H:i') ?: '—' }}</span>
                                        </span>
                                        <span class="xp-mobile-label">{{ $trip->trip_datetime?->format('d M Y, H:i') ?: '—' }}</span>
                                    </span>
                                    <span class="xp-footer-dot">&middot;</span>
                                    @if($isFull)
                                        <span class="badge badge-danger" style="font-size:11px;">Full</span>
                                    @else
                                        <span class="xp-footer-seats">
                                            <span class="xp-desktop-label"><i class="fa-regular fa-user"></i>{{ $availableSeats !== null ? $availableSeats : '?' }} / {{ (int) $trip->seat_limit }} seats</span>
                                            <span class="xp-mobile-label">{{ $availableSeats !== null ? $availableSeats : '?' }} seat{{ ($availableSeats !== 1) ? 's' : '' }}</span>
                                        </span>
                                        @endif

                                        @if($vehicleText !== '')
                                            <span class="xp-footer-vehicle xp-desktop-label">
                                                <span class="xp-vehicle-model">{{ $vehicleModel }} @if($vehiclePlate !== '') &middot; @endif</span>
                                                @if($vehiclePlate !== '')
                                                    <span class="xp-vehicle-plate">{{ $vehiclePlate }}</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="xp-footer-vehicle xp-desktop-label">
                                                <span class="xp-vehicle-model">Vehicle</span>
                                                <span class="xp-vehicle-plate">Not set</span>
                                            </span>
                                        @endif

                                        @if($isFree)
                                        <span class="xp-footer-fare free">Free</span>
                                    @else
                                        <span class="xp-footer-fare">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                    @endif

                                    @if($isJoined)
                                        <span class="btn btn-soft btn-sm" style="color:var(--success-ink);border-color:var(--success-soft);">
                                            <i class="fa-solid fa-check"></i> Joined
                                        </span>
                                    @elseif($myRequest && $myRequest->status === 'pending')
                                        <span class="btn btn-soft btn-sm" style="color:var(--warning-ink);border-color:var(--warning-soft);">
                                            <i class="fa-regular fa-clock"></i> Pending
                                        </span>
                                    @elseif($isFull)
                                        <span class="btn btn-soft btn-sm" style="color:var(--muted);cursor:default;">
                                            Full
                                        </span>
                                    @else
                                        <a href="{{ route('explore.show', $trip) }}#join-request" class="btn btn-primary btn-sm open-explore-modal">
                                            Request
                                        </a>
                                    @endif
                                    </div>
                                </div>

                                <aside class="xp-desktop-fare-panel">
                                    <p class="xp-fare-label">Per seat</p>
                                    @if($isFree)
                                        <p class="xp-fare-price free">Free</p>
                                    @else
                                        <p class="xp-fare-price">RM {{ number_format((float) $trip->fare_per_person, 2) }}</p>
                                    @endif
                                    <p class="xp-fare-note">Splits fuel + tolls equally</p>
                                    <div class="xp-fare-actions">
                                        @if($isJoined)
                                            <span class="btn btn-soft btn-sm" style="color:var(--success-ink);border-color:var(--success-soft);">
                                                <i class="fa-solid fa-check"></i> Joined
                                            </span>
                                        @elseif($myRequest && $myRequest->status === 'pending')
                                            <span class="btn btn-soft btn-sm" style="color:var(--warning-ink);border-color:var(--warning-soft);">
                                                <i class="fa-regular fa-clock"></i> Pending
                                            </span>
                                        @elseif($isFull)
                                            <span class="btn btn-soft btn-sm" style="color:var(--muted);cursor:default;">
                                                Full
                                            </span>
                                        @else
                                            <a href="{{ route('explore.show', $trip) }}#join-request" class="btn btn-primary btn-sm open-explore-modal">
                                                Request seat
                                            </a>
                                        @endif
                                        <a href="{{ route('explore.show', $trip) }}" class="btn btn-ghost btn-sm open-explore-modal">
                                            View details
                                        </a>
                                    </div>
                                </aside>
                            </div>
                        </article>
                    @endforeach

                    {{-- Pagination --}}
                    @if($trips->hasPages())
                        <div class="xp-pagination">
                            {{ $trips->appends(request()->query())->links() }}
                        </div>
                    @endif
                @endif
            </div>

            {{-- Right: sticky map panel --}}
            <div class="xp-map-panel">
                <div class="xp-map-panel-header">
                    <h3 class="xp-map-panel-title">Map of nearby trips</h3>
                    @if($trips->total() > 0)
                        <span class="badge badge-dark">{{ $trips->total() }} {{ $trips->total() === 1 ? 'route' : 'routes' }}</span>
                    @endif
                </div>
                <div id="explore-map"></div>
                <div class="xp-map-legend">
                    <span class="xp-map-legend-item">
                        <span class="xp-legend-dot pickup"></span> Pickup
                    </span>
                    <span class="xp-map-legend-item">
                        <span class="xp-legend-dot destination"></span> Destination
                    </span>
                    <span class="xp-map-legend-item">
                        <span class="xp-legend-dot custom"></span> Custom stop
                    </span>
                </div>
            </div>

        </div>{{-- .xp-body --}}

    </div>{{-- .xp-wrap --}}

    <div class="xp-modal" id="exploreTripModal" aria-hidden="true">
        <div class="xp-modal-card" role="dialog" aria-modal="true" aria-labelledby="exploreTripModalTitle">
            <div class="xp-modal-head">
                <div>
                    <h3 class="xp-modal-title" id="exploreTripModalTitle">Trip details</h3>
                    <span class="xp-modal-sub" id="exploreTripModalSub">Review the trip before requesting a seat.</span>
                </div>
                <button type="button" class="xp-modal-close" id="exploreTripModalClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="xp-modal-body">
                <div class="xp-modal-driver">
                    <span class="xp-modal-avatar" id="exploreModalDriverAvatar">DR</span>
                    <span>
                        <strong class="xp-driver-name" id="exploreModalDriver">Driver</strong>
                        <span class="xp-driver-rating"><i class="fa-solid fa-star"></i><span id="exploreModalRating">5.00</span></span>
                    </span>
                    <span class="badge badge-info" id="exploreModalVisibility" style="margin-left:auto">Public</span>
                </div>
                <div class="xp-modal-kv">
                    <div class="xp-modal-kv-item"><span>Time</span><strong id="exploreModalTime">-</strong></div>
                    <div class="xp-modal-kv-item"><span>Seats</span><strong id="exploreModalSeats">-</strong></div>
                    <div class="xp-modal-kv-item"><span>Fare</span><strong id="exploreModalFare">-</strong></div>
                </div>
                <div class="xp-modal-route">
                    <div class="xp-modal-point">
                        <span class="xp-modal-point-dot"></span>
                        <span><span>Pickup</span><strong id="exploreModalPickup">-</strong></span>
                    </div>
                    <div class="xp-modal-point">
                        <span class="xp-modal-point-dot dest"></span>
                        <span><span>Destination</span><strong id="exploreModalDestination">-</strong></span>
                    </div>
                </div>
                <div class="xp-modal-kv">
                    <div class="xp-modal-kv-item" style="grid-column:1/-1"><span>Vehicle</span><strong id="exploreModalVehicle">-</strong></div>
                </div>
                <div class="xp-modal-pref" id="exploreModalRoutePreference">
                    <h4 class="xp-modal-pref-title"><i class="fa-solid fa-route"></i> Your route preference</h4>
                    <div class="xp-modal-pref-grid">
                        <div class="xp-modal-pref-group">
                            <span class="xp-modal-pref-label">Pickup point</span>
                            <div class="xp-modal-radio-row">
                                <label class="xp-modal-radio">
                                    <input type="radio" name="pickup_mode" value="default" form="exploreModalJoinForm" checked>
                                    Use trip pickup
                                </label>
                                <label class="xp-modal-radio">
                                    <input type="radio" name="pickup_mode" value="custom" form="exploreModalJoinForm">
                                    Custom pickup
                                </label>
                            </div>
                        </div>
                        <div class="xp-modal-pref-group">
                            <span class="xp-modal-pref-label">Drop-off point</span>
                            <div class="xp-modal-radio-row">
                                <label class="xp-modal-radio">
                                    <input type="radio" name="dropoff_mode" value="default" form="exploreModalJoinForm" checked>
                                    Use destination
                                </label>
                                <label class="xp-modal-radio">
                                    <input type="radio" name="dropoff_mode" value="custom" form="exploreModalJoinForm">
                                    Custom drop-off
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="xp-modal-pref-group">
                        <span class="xp-modal-pref-label">Preferred pickup time</span>
                        <input class="xp-modal-input" type="datetime-local" name="requested_pickup_time" form="exploreModalJoinForm">
                    </div>
                    <p class="xp-modal-help">Use the standard trip points or pin nearby stops along the current route. The driver will review your request before approval.</p>
                    <div class="request-map-card" id="exploreModalMapCard" data-route-picker>
                        <div class="request-map-head">
                            <span class="request-map-title">Pin custom stops</span>
                            <span class="request-map-targets">
                                <button type="button" class="request-map-target active" data-map-target="pickup" hidden>Pin Pickup</button>
                                <button type="button" class="request-map-target" data-map-target="dropoff" hidden>Pin Drop-off</button>
                            </span>
                        </div>
                        <div id="requestRouteMapForm" class="request-route-map"></div>
                        <div class="request-map-legend">
                            <span><i class="legend-route"></i>Current route</span>
                            <span><i class="legend-preview"></i>Suggested join route</span>
                            <span><i class="legend-zone"></i><span id="routeAllowedLabel">Allowed area</span></span>
                            <span><i class="legend-pin"></i>Your pin</span>
                        </div>
                        <div id="requestMapStatus" class="request-map-status">Default trip points selected. Choose custom pickup or drop-off to pin a nearby stop.</div>
                        <div class="request-fare-preview" id="requestFarePreview">
                            <div class="request-fare-preview-head">
                                <span class="request-fare-preview-title">
                                    <i class="fa-solid fa-receipt"></i>
                                    Fare preview
                                </span>
                                <span class="request-fare-preview-badge" id="farePreviewBadge">Default split</span>
                            </div>
                            <div class="request-fare-grid">
                                <div class="request-fare-item">
                                    <span class="request-fare-label">Base route</span>
                                    <span class="request-fare-value" id="farePreviewRoute">-</span>
                                </div>
                                <div class="request-fare-item">
                                    <span class="request-fare-label">Route detour</span>
                                    <span class="request-fare-value" id="farePreviewSegment">-</span>
                                </div>
                                <div class="request-fare-item">
                                    <span class="request-fare-label">Your total</span>
                                    <span class="request-fare-value" id="farePreviewPassenger">-</span>
                                </div>
                                <div class="request-fare-item">
                                    <span class="request-fare-label">Base split</span>
                                    <span class="request-fare-value" id="farePreviewOthers">-</span>
                                </div>
                            </div>
                            <p class="request-fare-note" id="farePreviewNote">The normal fare remains the base. Custom pickup/drop-off only adds an extra charge based on distance from the original route.</p>
                        </div>
                    </div>
                </div>
                <div class="xp-modal-join-fields" id="exploreModalJoinFields">
                    <textarea class="xp-modal-note" id="exploreModalNote" name="request_note" form="exploreModalJoinForm" placeholder="Optional note for the driver"></textarea>
                </div>
            </div>
            <form class="xp-modal-foot" id="exploreModalJoinForm" method="POST">
                @csrf
                <input type="hidden" name="pickup_latitude" value="">
                <input type="hidden" name="pickup_longitude" value="">
                <input type="hidden" name="pickup_name" value="">
                <input type="hidden" name="dropoff_latitude" value="">
                <input type="hidden" name="dropoff_longitude" value="">
                <input type="hidden" name="dropoff_name" value="">
                <input type="hidden" name="extra_fee_amount" value="">
                <input type="hidden" name="detour_distance_km" value="">
                <button type="submit" class="xp-modal-join-btn" id="exploreModalJoinButton">
                    <i class="fa-solid fa-user-plus"></i>
                    Request seat
                </button>
                <div class="xp-modal-feedback" id="exploreModalFeedback" hidden></div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            // Scroll to focused card on page load
            const target = document.querySelector('[data-explore-focus-card="1"]');
            if (!target) return;
            window.setTimeout(() => {
                const y = target.getBoundingClientRect().top + window.scrollY - 104;
                window.scrollTo({ top: Math.max(y, 0), behavior: 'smooth' });
            }, 220);
        })();

        (async () => {
            const mapEl = document.getElementById('explore-map');
            const cards = Array.from(document.querySelectorAll('.open-explore-card[data-pickup-lat][data-destination-lat]'));
            if (!mapEl) return;

            const toNumber = (value) => {
                const number = Number.parseFloat(String(value ?? ''));
                return Number.isFinite(number) ? number : null;
            };
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const routeRows = cards.map((card, index) => {
                const pickupLat = toNumber(card.dataset.pickupLat);
                const pickupLng = toNumber(card.dataset.pickupLng);
                const destinationLat = toNumber(card.dataset.destinationLat);
                const destinationLng = toNumber(card.dataset.destinationLng);
                if ([pickupLat, pickupLng, destinationLat, destinationLng].some((value) => value === null)) return null;

                return {
                    card,
                    index,
                    routeName: card.dataset.routeName || `Route ${index + 1}`,
                    driver: card.dataset.driver || 'Driver',
                    time: card.dataset.time || '-',
                    seats: card.dataset.seats || '-',
                    fare: card.dataset.fare || '-',
                    pickup: card.dataset.pickup || 'Pickup',
                    destination: card.dataset.destination || 'Destination',
                    tripUrl: card.dataset.tripUrl || '#',
                    pickupPoint: [pickupLat, pickupLng],
                    destinationPoint: [destinationLat, destinationLng],
                    isFocused: card.dataset.exploreFocusCard === '1',
                };
            }).filter(Boolean);
            const routeGroups = Array.from(routeRows.reduce((groups, route) => {
                const key = [
                    route.pickupPoint[0].toFixed(5),
                    route.pickupPoint[1].toFixed(5),
                    route.destinationPoint[0].toFixed(5),
                    route.destinationPoint[1].toFixed(5),
                ].join('|');
                if (!groups.has(key)) {
                    groups.set(key, {
                        key,
                        pickup: route.pickup,
                        destination: route.destination,
                        pickupPoint: route.pickupPoint,
                        destinationPoint: route.destinationPoint,
                        routes: [],
                    });
                }
                groups.get(key).routes.push(route);
                route.groupKey = key;
                return groups;
            }, new Map()).values());

            if (typeof window.L === 'undefined') {
                mapEl.innerHTML = '<div class="xp-map-empty">Map is unavailable right now.</div>';
                return;
            }

            if (!routeRows.length) {
                mapEl.innerHTML = '<div class="xp-map-empty">No mapped route coordinates for the current trips.</div>';
                return;
            }

            const map = window.L.map(mapEl, {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: false,
            });

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);

            const bounds = window.L.latLngBounds([]);
            const routeLayers = new Map();
            const pickupIcon = window.L.divIcon({
                className: '',
                html: '<span class="xp-map-pin pickup"></span>',
                iconSize: [18, 18],
                iconAnchor: [9, 9],
            });
            const destinationIcon = window.L.divIcon({
                className: '',
                html: '<span class="xp-map-pin destination"></span>',
                iconSize: [18, 18],
                iconAnchor: [9, 9],
            });
            const fetchShortestPath = async (group) => {
                const coordinates = [
                    `${encodeURIComponent(group.pickupPoint[1])},${encodeURIComponent(group.pickupPoint[0])}`,
                    `${encodeURIComponent(group.destinationPoint[1])},${encodeURIComponent(group.destinationPoint[0])}`,
                ].join(';');
                const fallback = [group.pickupPoint, group.destinationPoint];
                try {
                    const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`);
                    if (!response.ok) return fallback;
                    const payload = await response.json();
                    const points = (payload?.routes?.[0]?.geometry?.coordinates ?? [])
                        .map((coord) => [Number(coord[1]), Number(coord[0])])
                        .filter((point) => Number.isFinite(point[0]) && Number.isFinite(point[1]));
                    return points.length > 1 ? points : fallback;
                } catch (_error) {
                    return fallback;
                }
            };

            const setRouteActive = (route, active) => {
                const layers = routeLayers.get(route.groupKey);
                if (!layers) return;
                layers.shadow.setStyle({
                    weight: active ? 18 : 12,
                    opacity: active ? 0.22 : 0.12,
                });
                layers.line.setStyle({
                    weight: active ? 6 : 4,
                    opacity: active ? 0.95 : 0.68,
                    color: active ? '#facc15' : '#0f172a',
                });
                if (active) {
                    layers.shadow.bringToFront();
                    layers.line.bringToFront();
                    layers.pickup.bringToFront();
                    layers.destination.bringToFront();
                }
            };

            for (const group of routeGroups) {
                const latLngs = await fetchShortestPath(group);
                latLngs.forEach((point) => bounds.extend(point));
                const isFocused = group.routes.some((route) => route.isFocused);
                const title = group.routes.length === 1
                    ? escapeHtml(group.routes[0].driver)
                    : `${group.routes.length} trips on this route`;
                const tripItems = group.routes.map((route) => `
                    <li>
                        <div class="xp-map-popup-trip-head">
                            <strong class="xp-map-popup-driver">${escapeHtml(route.driver)}</strong>
                            <button type="button" class="xp-map-popup-action" data-map-card-id="${escapeHtml(route.card.id)}">Details</button>
                        </div>
                        <div class="xp-map-popup-meta">
                            <span class="xp-map-popup-pill">${escapeHtml(route.time)}</span>
                            <span class="xp-map-popup-pill">${escapeHtml(route.fare)}</span>
                            <span class="xp-map-popup-pill">${escapeHtml(route.seats)}</span>
                        </div>
                    </li>
                `).join('');

                const popupHtml = `
                    <div class="xp-map-popup">
                        <div class="xp-map-popup-title">${title}</div>
                        <div class="xp-map-popup-route">
                            ${escapeHtml(group.pickup)}<br>
                            ${escapeHtml(group.destination)}
                        </div>
                        <ul class="xp-map-popup-list">${tripItems}</ul>
                    </div>
                `;
                const shadow = window.L.polyline(latLngs, {
                    color: '#0f172a',
                    weight: isFocused ? 18 : 12,
                    opacity: isFocused ? 0.22 : 0.12,
                    lineCap: 'round',
                    interactive: false,
                }).addTo(map);
                const line = window.L.polyline(latLngs, {
                    color: isFocused ? '#facc15' : '#0f172a',
                    weight: isFocused ? 6 : 4,
                    opacity: isFocused ? 0.95 : 0.68,
                    lineCap: 'round',
                }).addTo(map).bindPopup(popupHtml);
                const pickup = window.L.marker(group.pickupPoint, { icon: pickupIcon }).addTo(map).bindTooltip(escapeHtml(group.pickup)).bindPopup(popupHtml);
                const destination = window.L.marker(group.destinationPoint, { icon: destinationIcon }).addTo(map).bindTooltip(escapeHtml(group.destination)).bindPopup(popupHtml);

                routeLayers.set(group.key, { shadow, line, pickup, destination, latLngs });
            }

            routeRows.forEach((route) => {
                route.card.addEventListener('mouseenter', () => setRouteActive(route, true));
                route.card.addEventListener('mouseleave', () => setRouteActive(route, route.isFocused));
                route.card.addEventListener('focus', () => setRouteActive(route, true));
                route.card.addEventListener('blur', () => setRouteActive(route, route.isFocused));
                route.card.addEventListener('click', () => {
                    setRouteActive(route, true);
                    const layers = routeLayers.get(route.groupKey);
                    if (layers) map.fitBounds(window.L.latLngBounds(layers.latLngs), { padding: [42, 42], maxZoom: 15 });
                });
            });

            map.fitBounds(bounds, { padding: [26, 26], maxZoom: 14 });
            window.setTimeout(() => map.invalidateSize(), 120);
        })();

        (() => {
            // Whole-card popup details
            const cards = document.querySelectorAll('.open-explore-card[data-trip-url]');
            const modal = document.getElementById('exploreTripModal');
            const closeBtn = document.getElementById('exploreTripModalClose');
            const form = document.getElementById('exploreModalJoinForm');
            const noteInput = document.getElementById('exploreModalNote');
            const joinBtn = document.getElementById('exploreModalJoinButton');
            const feedback = document.getElementById('exploreModalFeedback');
            const mapCard = document.getElementById('exploreModalMapCard');
            const mapStatus = document.getElementById('requestMapStatus');
            const mapTargetButtons = document.querySelectorAll('[data-map-target]');
            let activeCard = null;
            let activeMapTarget = 'pickup';
            let modalMap = null;
            let routeLayerGroup = null;
            let markerLayerGroup = null;
            let previewLayerGroup = null;
            let pickupMarker = null;
            let dropoffMarker = null;
            let routeLinePoints = [];
            let pickupEndpoint = null;
            let dropoffEndpoint = null;
            let baseRouteDistanceKm = null;
            let previewRequestToken = 0;
            let allowedRouteRadiusKm = 0.2;
            let allowedEndpointRadiusKm = 0.5;
            if (!cards.length) return;

            const isInteractive = (el) => !!el.closest('a, button, input, textarea, select, label, form');
            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value || '-';
            };
            const configureJoinButton = (card) => {
                const state = card.dataset.joinState || 'open';
                const config = {
                    joined: ['Joined', 'fa-solid fa-check', true],
                    pending: ['Request pending', 'fa-regular fa-clock', true],
                    full: ['Trip full', 'fa-solid fa-ban', true],
                    closed: ['Requests closed', 'fa-solid fa-lock', true],
                    open: ['Request seat', 'fa-solid fa-user-plus', false],
                }[state] || ['Request seat', 'fa-solid fa-user-plus', false];

                joinBtn.innerHTML = `<i class="${config[1]}"></i>${config[0]}`;
                joinBtn.disabled = config[2];
                document.querySelectorAll('#exploreModalRoutePreference input').forEach((input) => {
                    input.disabled = false;
                });
            };
            const toNumber = (value) => {
                const number = Number.parseFloat(String(value ?? ''));
                return Number.isFinite(number) ? number : null;
            };
            const formatMoney = (value) => `RM ${Math.max(0, Number(value) || 0).toFixed(2)}`;
            const currentPassengerName = @json(auth()->user()?->name ?? 'Passenger');
            const passengerLabel = (suffix) => `${currentPassengerName} ${suffix}`;
            const formInput = (name) => form.querySelector(`[name="${name}"]`);
            const setStatus = (message, type = '') => {
                if (!mapStatus) return;
                mapStatus.textContent = message;
                mapStatus.classList.toggle('blocked', type === 'blocked');
                mapStatus.classList.toggle('ok', type === 'ok');
            };
            const setMode = (target, mode) => {
                const modeInput = document.querySelector(`#exploreModalRoutePreference input[name="${target}_mode"][value="${mode}"]`);
                if (modeInput) modeInput.checked = true;
            };
            const readMode = (target) => {
                const checked = document.querySelector(`#exploreModalRoutePreference input[name="${target}_mode"]:checked`);
                return checked?.value === 'custom' ? 'custom' : 'default';
            };
            const setCoordinate = (target, lat, lng) => {
                const latInput = formInput(`${target}_latitude`);
                const lngInput = formInput(`${target}_longitude`);
                if (latInput) latInput.value = lat === null ? '' : lat.toFixed(7);
                if (lngInput) lngInput.value = lng === null ? '' : lng.toFixed(7);
            };
            const setPlaceName = (target, value) => {
                const input = formInput(`${target}_name`);
                if (input) input.value = value || '';
            };
            const distanceForPointsKm = (points) => {
                let total = 0;
                for (let index = 0; index < points.length - 1; index += 1) {
                    total += points[index].distanceTo(points[index + 1]) / 1000;
                }
                return total;
            };
            const updateAllowedRadiusForDistance = (distanceKm) => {
                const routeKm = Number(distanceKm) || (pickupEndpoint && dropoffEndpoint ? distanceForPointsKm([pickupEndpoint, dropoffEndpoint]) : 1) || 1;
                if (routeKm <= 3) {
                    allowedRouteRadiusKm = 0.40;
                    allowedEndpointRadiusKm = 0.50;
                } else if (routeKm <= 10) {
                    allowedRouteRadiusKm = 0.70;
                    allowedEndpointRadiusKm = 0.80;
                } else if (routeKm <= 25) {
                    allowedRouteRadiusKm = 1.00;
                    allowedEndpointRadiusKm = 1.20;
                } else {
                    allowedRouteRadiusKm = 1.30;
                    allowedEndpointRadiusKm = 1.50;
                }
                const label = document.getElementById('routeAllowedLabel');
                if (label) label.textContent = `Allowed: route ${Math.round(allowedRouteRadiusKm * 1000)}m, Point A/B ${Math.round(allowedEndpointRadiusKm * 1000)}m`;
            };
            const setMapTarget = (target) => {
                activeMapTarget = target === 'dropoff' ? 'dropoff' : 'pickup';
                mapTargetButtons.forEach((button) => {
                    button.classList.toggle('active', button.dataset.mapTarget === activeMapTarget);
                });
                setStatus(`Tap near the route to pin ${activeMapTarget === 'pickup' ? 'pickup' : 'drop-off'}.`);
            };
            const pointIcon = (type) => window.L.divIcon({
                className: '',
                html: `<span class="passenger-pin-icon ${type === 'dropoff' ? 'dropoff' : 'pickup'}"></span>`,
                iconSize: [25, 25],
                iconAnchor: [12, 25],
                tooltipAnchor: [0, -23],
            });
            const drawRoute = (points) => {
                if (!routeLayerGroup || !modalMap || !pickupEndpoint || !dropoffEndpoint) return;
                routeLayerGroup.clearLayers();
                const latLngs = points.map((point) => Array.isArray(point) ? window.L.latLng(point[0], point[1]) : point);
                window.L.polyline(latLngs, { color: '#475569', weight: 18, opacity: 0.16, lineCap: 'round', interactive: false }).addTo(routeLayerGroup);
                window.L.polyline(latLngs, { color: '#64748b', weight: 4, opacity: 0.82, lineCap: 'round', interactive: false }).addTo(routeLayerGroup);
                window.L.circle(pickupEndpoint, { radius: allowedEndpointRadiusKm * 1000, color: '#16a34a', weight: 1, fillColor: '#16a34a', fillOpacity: 0.07, opacity: 0.28, interactive: false }).addTo(routeLayerGroup);
                window.L.circle(dropoffEndpoint, { radius: allowedEndpointRadiusKm * 1000, color: '#2563eb', weight: 1, fillColor: '#2563eb', fillOpacity: 0.07, opacity: 0.28, interactive: false }).addTo(routeLayerGroup);
                window.L.circleMarker(pickupEndpoint, { radius: 6, color: '#fff', weight: 2, fillColor: '#16a34a', fillOpacity: 1, interactive: false }).addTo(routeLayerGroup);
                window.L.circleMarker(dropoffEndpoint, { radius: 6, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1, interactive: false }).addTo(routeLayerGroup);
                modalMap.fitBounds(window.L.latLngBounds(latLngs), { padding: [22, 22] });
            };
            const pointToLocalKm = (latLng, origin) => {
                const lat = latLng.lat ?? latLng[0];
                const lng = latLng.lng ?? latLng[1];
                const originLat = origin.lat ?? origin[0];
                const originLng = origin.lng ?? origin[1];
                return {
                    x: (lng - originLng) * 111.32 * Math.cos((originLat * Math.PI) / 180),
                    y: (lat - originLat) * 110.57,
                };
            };
            const distanceToSegmentKm = (point, start, end) => {
                const p = pointToLocalKm(point, start);
                const b = pointToLocalKm(end, start);
                const lengthSquared = (b.x ** 2) + (b.y ** 2);
                if (lengthSquared === 0) return Math.sqrt((p.x ** 2) + (p.y ** 2));
                const t = Math.max(0, Math.min(1, ((p.x * b.x) + (p.y * b.y)) / lengthSquared));
                return Math.sqrt(((p.x - (t * b.x)) ** 2) + ((p.y - (t * b.y)) ** 2));
            };
            const distanceToRouteKm = (latLng) => {
                if (routeLinePoints.length < 2) return null;
                let nearest = Infinity;
                for (let index = 0; index < routeLinePoints.length - 1; index += 1) {
                    nearest = Math.min(nearest, distanceToSegmentKm(latLng, routeLinePoints[index], routeLinePoints[index + 1]));
                }
                return Number.isFinite(nearest) ? nearest : null;
            };
            const endpointDistanceKm = (latLng) => {
                if (!pickupEndpoint || !dropoffEndpoint) return Infinity;
                return Math.min(latLng.distanceTo(pickupEndpoint) / 1000, latLng.distanceTo(dropoffEndpoint) / 1000);
            };
            const uniqueWaypoints = (points) => points.reduce((items, point) => {
                if (!point) return items;
                const last = items[items.length - 1];
                if (!last || Math.abs(last.lat - point.lat) > 0.00001 || Math.abs(last.lng - point.lng) > 0.00001) {
                    items.push(point);
                }
                return items;
            }, []);
            const fetchRoute = async (points) => {
                const waypoints = uniqueWaypoints(points);
                if (waypoints.length < 2) return { points: waypoints, distanceKm: 0 };

                const coordinates = waypoints
                    .map((point) => `${encodeURIComponent(point.lng)},${encodeURIComponent(point.lat)}`)
                    .join(';');
                const url = `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`;

                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('route');
                    const payload = await response.json();
                    const route = payload?.routes?.[0];
                    const routePoints = (route?.geometry?.coordinates ?? [])
                        .map((coord) => window.L.latLng(Number(coord[1]), Number(coord[0])))
                        .filter((coord) => Number.isFinite(coord.lat) && Number.isFinite(coord.lng));

                    return {
                        points: routePoints.length > 1 ? routePoints : waypoints,
                        distanceKm: route?.distance ? Number(route.distance) / 1000 : distanceForPointsKm(waypoints),
                    };
                } catch (_error) {
                    return {
                        points: waypoints,
                        distanceKm: distanceForPointsKm(waypoints),
                    };
                }
            };
            const updateFarePreview = async () => {
                const token = ++previewRequestToken;
                const defaultFarePerPerson = Number(activeCard?.dataset.fareRaw || 0);
                const tripFareTotal = Number(activeCard?.dataset.fareTotal || defaultFarePerPerson);
                const customPickup = readMode('pickup') === 'custom';
                const customDropoff = readMode('dropoff') === 'custom';
                const pickupLat = toNumber(formInput('pickup_latitude')?.value);
                const pickupLng = toNumber(formInput('pickup_longitude')?.value);
                const dropoffLat = toNumber(formInput('dropoff_latitude')?.value);
                const dropoffLng = toNumber(formInput('dropoff_longitude')?.value);
                const passengerPickup = customPickup
                    ? (pickupLat !== null && pickupLng !== null ? window.L.latLng(pickupLat, pickupLng) : null)
                    : pickupEndpoint;
                const passengerDropoff = customDropoff
                    ? (dropoffLat !== null && dropoffLng !== null ? window.L.latLng(dropoffLat, dropoffLng) : null)
                    : dropoffEndpoint;
                const hasCustom = customPickup || customDropoff;

                if (!pickupEndpoint || !dropoffEndpoint) return;
                if ((customPickup && !passengerPickup) || (customDropoff && !passengerDropoff)) {
                    const previewPickup = customPickup && passengerPickup ? passengerPickup : pickupEndpoint;
                    const previewDropoff = customDropoff && passengerDropoff ? passengerDropoff : dropoffEndpoint;
                    const previewPoints = [pickupEndpoint, ...(customPickup && passengerPickup ? [passengerPickup] : []), ...(customDropoff && passengerDropoff ? [passengerDropoff] : []), dropoffEndpoint];
                    const previewRoute = await fetchRoute(previewPoints.length > 1 ? previewPoints : [previewPickup, previewDropoff]);
                    if (token !== previewRequestToken) return;
                    previewLayerGroup?.clearLayers();
                    if (previewLayerGroup && previewRoute.points.length > 1) {
                        window.L.polyline(previewRoute.points, { color: '#1d4ed8', weight: 6, opacity: 0.9, lineCap: 'round', interactive: false }).addTo(previewLayerGroup);
                        window.L.polyline(previewRoute.points, { color: '#facc15', weight: 2, opacity: 0.95, dashArray: '7 8', lineCap: 'round', interactive: false }).addTo(previewLayerGroup);
                    }
                    formInput('extra_fee_amount').value = '';
                    formInput('detour_distance_km').value = '';
                    setText('farePreviewBadge', 'Waiting for pin');
                    setText('farePreviewRoute', previewRoute.distanceKm ? `${previewRoute.distanceKm.toFixed(2)} km` : '-');
                    setText('farePreviewSegment', '-');
                    setText('farePreviewPassenger', formatMoney(defaultFarePerPerson));
                    setText('farePreviewOthers', formatMoney(defaultFarePerPerson));
                    setText('farePreviewNote', customDropoff && !passengerDropoff && customPickup && passengerPickup
                        ? 'Pickup custom is already in the suggested route. Pin the custom drop-off to finish fare preview.'
                        : 'Pin custom points to preview base split + extra fee.');
                    return;
                }

                const routePoints = [pickupEndpoint, ...(customPickup ? [passengerPickup] : []), ...(customDropoff ? [passengerDropoff] : []), dropoffEndpoint];
                const suggestedRoute = await fetchRoute(routePoints);
                if (token !== previewRequestToken) return;

                previewLayerGroup?.clearLayers();
                if (previewLayerGroup && suggestedRoute.points.length > 1) {
                    window.L.polyline(suggestedRoute.points, { color: '#1d4ed8', weight: 6, opacity: 0.9, lineCap: 'round', interactive: false }).addTo(previewLayerGroup);
                    window.L.polyline(suggestedRoute.points, { color: '#facc15', weight: 2, opacity: 0.95, dashArray: '7 8', lineCap: 'round', interactive: false }).addTo(previewLayerGroup);
                }

                const baseKm = baseRouteDistanceKm || distanceForPointsKm([pickupEndpoint, dropoffEndpoint]) || 1;
                const routeDeviationKm = hasCustom
                    ? Math.max(0, suggestedRoute.distanceKm - baseKm)
                    : 0;
                const passengerFare = hasCustom ? defaultFarePerPerson + ((routeDeviationKm / baseKm) * tripFareTotal) : defaultFarePerPerson;
                const passengerDelta = passengerFare - defaultFarePerPerson;
                formInput('extra_fee_amount').value = hasCustom ? passengerDelta.toFixed(2) : '';
                formInput('detour_distance_km').value = hasCustom ? routeDeviationKm.toFixed(2) : '';
                setText('farePreviewBadge', hasCustom ? 'Extra fee added' : 'Default split');
                setText('farePreviewRoute', `${baseKm.toFixed(2)} km`);
                setText('farePreviewSegment', `${routeDeviationKm.toFixed(2)} km`);
                setText('farePreviewPassenger', formatMoney(passengerFare));
                setText('farePreviewOthers', formatMoney(defaultFarePerPerson));
                setText('farePreviewNote', hasCustom
                    ? `Normal split stays as base fare. Extra ${formatMoney(passengerDelta)} is based on ${routeDeviationKm.toFixed(2)} km custom deviation from the driver's original route. Driver can review before approve.`
                    : 'Default trip points selected, normal fare split is used.');
            };
            const applyPin = (target, latLng) => {
                setMode(target, 'custom');
                setCoordinate(target, latLng.lat, latLng.lng);
                setPlaceName(target, `Pinned ${target === 'pickup' ? 'pickup' : 'drop-off'} near route (${latLng.lat.toFixed(5)}, ${latLng.lng.toFixed(5)})`);
                if (target === 'pickup') {
                    if (pickupMarker) pickupMarker.remove();
                    pickupMarker = window.L.marker(latLng, { icon: pointIcon('pickup') }).addTo(markerLayerGroup);
                    pickupMarker.bindTooltip(passengerLabel('pickup pin'), { permanent: true, direction: 'top', offset: [0, -10] });
                } else {
                    if (dropoffMarker) dropoffMarker.remove();
                    dropoffMarker = window.L.marker(latLng, { icon: pointIcon('dropoff') }).addTo(markerLayerGroup);
                    dropoffMarker.bindTooltip(passengerLabel('drop-off pin'), { permanent: true, direction: 'top', offset: [0, -10] });
                }
                updateFarePreview();
            };
            const restorePinnedMarkers = () => {
                if (!markerLayerGroup) return;
                const pickupLat = toNumber(formInput('pickup_latitude')?.value);
                const pickupLng = toNumber(formInput('pickup_longitude')?.value);
                const dropoffLat = toNumber(formInput('dropoff_latitude')?.value);
                const dropoffLng = toNumber(formInput('dropoff_longitude')?.value);
                if (readMode('pickup') === 'custom' && pickupLat !== null && pickupLng !== null) {
                    pickupMarker = window.L.marker(window.L.latLng(pickupLat, pickupLng), { icon: pointIcon('pickup') }).addTo(markerLayerGroup);
                    pickupMarker.bindTooltip(passengerLabel('pickup pin'), { permanent: true, direction: 'top', offset: [0, -10] });
                }
                if (readMode('dropoff') === 'custom' && dropoffLat !== null && dropoffLng !== null) {
                    dropoffMarker = window.L.marker(window.L.latLng(dropoffLat, dropoffLng), { icon: pointIcon('dropoff') }).addTo(markerLayerGroup);
                    dropoffMarker.bindTooltip(passengerLabel('drop-off pin'), { permanent: true, direction: 'top', offset: [0, -10] });
                }
            };
            const handleMapClick = (latLng) => {
                if (readMode('pickup') !== 'custom' && readMode('dropoff') !== 'custom') {
                    setStatus('Default trip points selected. Choose custom pickup or drop-off to pin a nearby stop.');
                    return;
                }
                const routeDistance = distanceToRouteKm(latLng);
                const pointDistance = endpointDistanceKm(latLng);
                const allowed = (routeDistance !== null && routeDistance <= allowedRouteRadiusKm) || pointDistance <= allowedEndpointRadiusKm;
                if (!allowed) {
                    const routeDistanceText = routeDistance === null ? 'unknown' : `${routeDistance.toFixed(1)} km`;
                    setStatus(`Blocked: selected point is outside the allowed route area. Nearest route distance: ${routeDistanceText}.`, 'blocked');
                    window.L.circleMarker(latLng, { radius: 8, color: '#b91c1c', weight: 2, fillColor: '#ef4444', fillOpacity: 0.2 }).addTo(markerLayerGroup).bindTooltip('Outside allowed area', { direction: 'top', offset: [0, -8] }).openTooltip();
                    return;
                }
                applyPin(activeMapTarget, latLng);
                const distanceText = routeDistance === null ? 'near endpoint' : `${routeDistance.toFixed(2)} km from route`;
                setStatus(`${activeMapTarget === 'pickup' ? passengerLabel('pickup') : passengerLabel('drop-off')} pin saved, ${distanceText}.`, 'ok');
            };
            const ensureModalMap = async () => {
                if (typeof window.L === 'undefined' || !mapCard || !activeCard) return;
                const pickupLat = toNumber(activeCard.dataset.pickupLat);
                const pickupLng = toNumber(activeCard.dataset.pickupLng);
                const destLat = toNumber(activeCard.dataset.destinationLat);
                const destLng = toNumber(activeCard.dataset.destinationLng);
                const hasDriverPoints = [pickupLat, pickupLng, destLat, destLng].every((value) => value !== null);
                const center = hasDriverPoints ? [pickupLat, pickupLng] : [3.139, 101.6869];

                if (!modalMap) {
                    modalMap = window.L.map('requestRouteMapForm', { zoomControl: true, attributionControl: false, scrollWheelZoom: false }).setView(center, hasDriverPoints ? 13 : 7);
                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(modalMap);
                    modalMap.on('click', (event) => handleMapClick(event.latlng));
                    routeLayerGroup = window.L.layerGroup().addTo(modalMap);
                    markerLayerGroup = window.L.layerGroup().addTo(modalMap);
                    previewLayerGroup = window.L.layerGroup().addTo(modalMap);
                }

                routeLayerGroup.clearLayers();
                markerLayerGroup.clearLayers();
                previewLayerGroup.clearLayers();
                pickupMarker = null;
                dropoffMarker = null;

                if (!hasDriverPoints) {
                    modalMap.setView(center, 7);
                    setStatus('Map is unavailable because this route has no coordinates.', 'blocked');
                    return;
                }

                pickupEndpoint = window.L.latLng(pickupLat, pickupLng);
                dropoffEndpoint = window.L.latLng(destLat, destLng);
                routeLinePoints = [[pickupLat, pickupLng], [destLat, destLng]];
                baseRouteDistanceKm = null;
                updateAllowedRadiusForDistance(distanceForPointsKm([pickupEndpoint, dropoffEndpoint]));
                drawRoute(routeLinePoints);
                restorePinnedMarkers();
                updateFarePreview();
                setTimeout(() => modalMap?.invalidateSize(), 80);

                const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${encodeURIComponent(pickupLng)},${encodeURIComponent(pickupLat)};${encodeURIComponent(destLng)},${encodeURIComponent(destLat)}?overview=full&geometries=geojson&alternatives=false&steps=false`;
                try {
                    const response = await fetch(osrmUrl);
                    const payload = response.ok ? await response.json() : null;
                    if (payload?.routes?.[0]?.distance) {
                        baseRouteDistanceKm = Number(payload.routes[0].distance) / 1000;
                        updateAllowedRadiusForDistance(baseRouteDistanceKm);
                    }
                    const points = (payload?.routes?.[0]?.geometry?.coordinates ?? [])
                        .map((coord) => [Number(coord[1]), Number(coord[0])])
                        .filter((coord) => Number.isFinite(coord[0]) && Number.isFinite(coord[1]));
                    if (points.length > 1) {
                        routeLinePoints = points;
                        drawRoute(routeLinePoints);
                    }
                    updateFarePreview();
                } catch (_error) {
                    updateFarePreview();
                }
            };
            const syncCustomFields = (preferredTarget = null) => {
                const pickupMode = document.querySelector('#exploreModalRoutePreference input[name="pickup_mode"]:checked')?.value || 'default';
                const dropoffMode = document.querySelector('#exploreModalRoutePreference input[name="dropoff_mode"]:checked')?.value || 'default';
                const hasCustom = pickupMode === 'custom' || dropoffMode === 'custom';
                const pickupTarget = document.querySelector('.request-map-target[data-map-target="pickup"]');
                const dropoffTarget = document.querySelector('.request-map-target[data-map-target="dropoff"]');
                if (pickupTarget) pickupTarget.hidden = pickupMode !== 'custom';
                if (dropoffTarget) dropoffTarget.hidden = dropoffMode !== 'custom';
                mapCard.hidden = false;
                if (pickupMode !== 'custom') {
                    setCoordinate('pickup', null, null);
                    setPlaceName('pickup', '');
                    if (pickupMarker) {
                        pickupMarker.remove();
                        pickupMarker = null;
                    }
                }
                if (dropoffMode !== 'custom') {
                    setCoordinate('dropoff', null, null);
                    setPlaceName('dropoff', '');
                    if (dropoffMarker) {
                        dropoffMarker.remove();
                        dropoffMarker = null;
                    }
                }
                if (hasCustom) {
                    if (preferredTarget === 'dropoff' && dropoffMode === 'custom') {
                        setMapTarget('dropoff');
                    } else if (preferredTarget === 'pickup' && pickupMode === 'custom') {
                        setMapTarget('pickup');
                    } else {
                        setMapTarget(pickupMode === 'custom' ? 'pickup' : 'dropoff');
                    }
                } else {
                    mapTargetButtons.forEach((button) => button.classList.remove('active'));
                    setStatus('Default trip points selected. Choose custom pickup or drop-off to pin a nearby stop.');
                }
                ensureModalMap();
                updateFarePreview();
            };
            const openModal = (card) => {
                activeCard = card;
                setText('exploreTripModalTitle', card.dataset.routeName || 'Trip details');
                setText('exploreTripModalSub', `${card.dataset.driver || 'Driver'} · ${card.dataset.time || '-'}`);
                setText('exploreModalDriverAvatar', card.dataset.driverInitial || 'DR');
                setText('exploreModalDriver', card.dataset.driver || 'Driver');
                setText('exploreModalRating', card.dataset.rating || '5.00');
                setText('exploreModalVisibility', card.dataset.visibility || 'Public');
                setText('exploreModalTime', card.dataset.time || '-');
                setText('exploreModalSeats', card.dataset.seats || '-');
                setText('exploreModalFare', card.dataset.fare || '-');
                setText('exploreModalPickup', card.dataset.pickup || '-');
                setText('exploreModalDestination', card.dataset.destination || '-');
                setText('exploreModalVehicle', card.dataset.vehicle || '-');
                form.action = card.dataset.joinUrl || card.dataset.tripUrl || '';
                form.dataset.cardId = card.id || '';
                form.reset();
                form.querySelector('input[name="pickup_latitude"]').value = '';
                form.querySelector('input[name="pickup_longitude"]').value = '';
                form.querySelector('input[name="pickup_name"]').value = '';
                form.querySelector('input[name="dropoff_latitude"]').value = '';
                form.querySelector('input[name="dropoff_longitude"]').value = '';
                form.querySelector('input[name="dropoff_name"]').value = '';
                form.querySelector('input[name="extra_fee_amount"]').value = '';
                form.querySelector('input[name="detour_distance_km"]').value = '';
                if (pickupMarker && modalMap) modalMap.removeLayer(pickupMarker);
                if (dropoffMarker && modalMap) modalMap.removeLayer(dropoffMarker);
                pickupMarker = null;
                dropoffMarker = null;
                noteInput.value = '';
                feedback.hidden = true;
                feedback.textContent = '';
                configureJoinButton(card);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                document.body.classList.add('explore-modal-open');
                syncCustomFields();
            };
            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                document.body.classList.remove('explore-modal-open');
            };

            cards.forEach((card) => {
                card.addEventListener('click', (e) => {
                    if (isInteractive(e.target) && !e.target.closest('.open-explore-modal')) return;
                    e.preventDefault();
                    openModal(card);
                });

                card.addEventListener('keydown', (e) => {
                    if (e.key !== 'Enter' && e.key !== ' ') return;
                    if (isInteractive(e.target)) return;
                    e.preventDefault();
                    openModal(card);
                });
            });
            document.querySelectorAll('.open-explore-modal').forEach((link) => {
                link.addEventListener('click', (event) => {
                    const card = event.currentTarget.closest('.open-explore-card');
                    if (!card) return;
                    event.preventDefault();
                    event.stopPropagation();
                    openModal(card);
                });
            });
            document.addEventListener('click', (event) => {
                const button = event.target.closest('.xp-map-popup-action[data-map-card-id]');
                if (!button) return;
                event.preventDefault();
                event.stopPropagation();
                const card = document.getElementById(button.dataset.mapCardId || '');
                if (card) openModal(card);
            });
            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
            });
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (joinBtn.disabled) return;

                const original = joinBtn.innerHTML;
                joinBtn.disabled = true;
                joinBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Sending';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || 'Join request could not be submitted.');

                    const card = document.getElementById(form.dataset.cardId || '');
                    if (card) {
                        card.dataset.joinState = 'pending';
                        card.querySelectorAll('.btn-primary').forEach((button) => {
                            button.classList.remove('btn-primary');
                            button.classList.add('btn-soft');
                            button.innerHTML = '<i class="fa-regular fa-clock"></i> Pending';
                        });
                    }
                    joinBtn.innerHTML = '<i class="fa-solid fa-check"></i>Request sent';
                    feedback.textContent = payload.message || 'Join request submitted.';
                    feedback.hidden = false;
                } catch (error) {
                    joinBtn.disabled = false;
                    joinBtn.innerHTML = original;
                    feedback.textContent = error.message || 'Join request could not be submitted.';
                    feedback.hidden = false;
                }
            });
            form.addEventListener('change', (event) => {
                if (event.target.matches('input[name="pickup_mode"], input[name="dropoff_mode"]')) {
                    syncCustomFields(event.target.name.replace('_mode', ''));
                }
            });
            document.getElementById('exploreModalRoutePreference')?.addEventListener('change', (event) => {
                if (event.target.matches('input[name="pickup_mode"], input[name="dropoff_mode"]')) {
                    syncCustomFields(event.target.name.replace('_mode', ''));
                }
            });
            mapTargetButtons.forEach((button) => {
                button.addEventListener('click', () => setMapTarget(button.dataset.mapTarget || 'pickup'));
            });
        })();

        /* ── Skeleton: show on filter/search form submit ────────── */
        (function () {
            var skelList = document.getElementById('xp-skel-list');
            var realList = document.getElementById('xp-real-list');

            function showSearchSkeleton() {
                if (!skelList || !realList) return;
                skelList.style.display = 'grid';
                realList.classList.add('xp-list-loading');
            }

            // Intercept search card form submit
            var searchForms = document.querySelectorAll('.xp-search-card form, form[data-search-form]');
            searchForms.forEach(function (form) {
                form.addEventListener('submit', showSearchSkeleton);
            });

            // Also intercept sort/filter chip clicks (href navigation)
            document.querySelectorAll('.xp-sort-row .chip[href]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    showSearchSkeleton();
                });
            });

            // Search button in top bar
            var searchBtn = document.querySelector('.xp-search-btn, button[type="submit"]');
            if (searchBtn) {
                searchBtn.addEventListener('click', function () {
                    setTimeout(showSearchSkeleton, 50);
                });
            }
        })();
    </script>
@endsection
