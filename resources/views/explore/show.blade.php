@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    @php
        $pickupName = $trip->pickup_name ?? 'Pickup';
        $destinationName = $trip->destination_name ?? 'Destination';
        $routeName = $trip->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
        $isTwoWay = ((string) ($trip->trip_mode ?? 'one_way')) === 'two_way' || (bool) $trip->returnTrip;
        $returnTripId = $trip->returnTrip?->id;
        $tripIdDisplay = ($isTwoWay && $returnTripId)
            ? ('#' . $trip->id . ' & #' . $returnTripId)
            : ('#' . $trip->id);
        $isFull = $availableSeats !== null && $availableSeats <= 0;
        $statusText = ucfirst((string) $trip->status);
        $passengers = $trip->participants->filter(fn ($participant) => ! $participant->is_driver)->values();
        $passengerCount = $passengers->count();
        $splitTypeText = ((int) ($trip->participant_count ?? 0) > $passengerCount)
            ? 'Driver Included'
            : 'Driver Excluded';
        $currentPassengerName = auth()->user()?->name ?: 'Passenger';
    @endphp

    <style>
        /* ── Show page wrapper ──────────────────────────────────────── */
        .ts-wrap {
            padding: 0 28px 28px;
        }

        /* ── Breadcrumb ─────────────────────────────────────────────── */
        .ts-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 20px 0 16px;
            font-size: 13px;
            color: var(--muted);
        }
        .ts-breadcrumb a {
            color: var(--warning-ink);
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .ts-breadcrumb a:hover { text-decoration: underline; }
        .ts-breadcrumb-sep { color: var(--hairline-strong); }

        /* ── Page header ────────────────────────────────────────────── */
        .ts-page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .ts-header-copy { min-width: 0; }
        .ts-eyebrow {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--muted);
            margin: 0 0 4px;
        }
        .ts-page-title {
            margin: 0;
            font-family: var(--font-display), sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--ink);
            line-height: 1.15;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .ts-page-title-arrow { color: var(--ch-yellow-deep); }
        .ts-page-sub {
            margin: 6px 0 0;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }
        .ts-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        /* ── Main body grid ─────────────────────────────────────────── */
        .ts-body-grid {
            display: grid;
            gap: 18px;
            align-items: start;
        }
        @media (min-width: 1024px) {
            .ts-body-grid {
                grid-template-columns: 1.4fr 1fr;
            }
        }

        /* ── Left column ────────────────────────────────────────────── */
        .ts-left-col { display: grid; gap: 18px; align-content: start; }

        /* Map card */
        .ts-map-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: var(--shadow-1);
        }
        .ts-map-inner {
            width: 100%;
            height: 280px;
            background: #eef2f7;
        }
        .ts-map-inner .leaflet-control-attribution { display: none; }
        .ts-map-kv-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            border-top: 1px solid var(--hairline);
        }
        .ts-kv-item {
            padding: 12px 14px;
            display: grid;
            gap: 2px;
        }
        .ts-kv-item + .ts-kv-item {
            border-left: 1px solid var(--hairline);
        }
        .ts-kv-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
        }
        .ts-kv-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.3;
        }

        /* Route preference card */
        .ts-route-pref-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 20px;
            display: grid;
            gap: 16px;
            box-shadow: var(--shadow-1);
            scroll-margin-top: 96px;
        }
        .ts-route-pref-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--ink);
            margin: 0 0 2px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .ts-pref-fields-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        @media (max-width: 639px) {
            .ts-pref-fields-grid { grid-template-columns: 1fr; }
        }
        .ts-pref-field-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
            margin-bottom: 5px;
        }

        /* Estimated fare box */
        .ts-fare-est-box {
            background: var(--ch-yellow-tint);
            border: 1px solid var(--ch-yellow-line);
            border-radius: var(--r-md);
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .ts-fare-est-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--warning-ink);
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .ts-fare-est-amount {
            font-family: var(--font-display), sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: var(--ch-yellow-ink);
            line-height: 1;
        }
        .ts-fare-est-unit {
            font-size: 13px;
            font-weight: 600;
            color: var(--warning);
            margin-top: 2px;
        }

        /* ── Right column ───────────────────────────────────────────── */
        .ts-right-col { display: grid; gap: 18px; align-content: start; }

        /* Shared card base */
        .ts-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: var(--shadow-1);
        }
        .ts-card-header {
            padding: 14px 18px;
            border-bottom: 1px solid var(--hairline);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            background: var(--surface);
        }
        .ts-card-title {
            font-size: 14px;
            font-weight: 800;
            color: var(--ink);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .ts-card-body {
            padding: 18px;
            display: grid;
            gap: 14px;
        }

        /* Driver card */
        .ts-driver-block {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ts-avatar {
            width: 48px;
            height: 48px;
            border-radius: 999px;
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
            font-family: var(--font-display), sans-serif;
            font-size: 18px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 2px solid var(--ch-yellow-line);
        }
        .ts-avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 13px;
            border-width: 1px;
        }
        .ts-driver-meta { min-width: 0; }
        .ts-driver-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.2;
        }
        .ts-driver-email {
            font-size: 12px;
            color: var(--muted);
            word-break: break-word;
            line-height: 1.3;
        }
        .ts-driver-rating {
            font-size: 12px;
            font-weight: 700;
            color: var(--warning-ink);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 2px;
        }

        /* Vehicle KV 2x2 grid */
        .ts-vehicle-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .ts-vehicle-item {
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            padding: 10px 12px;
        }
        .ts-vehicle-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
            margin-bottom: 2px;
        }
        .ts-vehicle-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
        }

        /* Verified badges row */
        .ts-badges-row {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        /* Fare breakdown card */
        .ts-fare-line-list {
            display: grid;
            gap: 8px;
        }
        .ts-fare-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 13px;
        }
        .ts-fare-line-label { color: var(--muted); }
        .ts-fare-line-value { font-weight: 700; color: var(--ink); }
        .ts-fare-total-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border-top: 1px solid var(--hairline);
            padding-top: 10px;
            margin-top: 4px;
        }
        .ts-fare-total-label {
            font-size: 14px;
            font-weight: 800;
            color: var(--ink);
        }
        .ts-fare-total-value {
            font-size: 18px;
            font-weight: 800;
            color: var(--ch-yellow-ink);
            font-family: var(--font-mono, monospace);
        }

        /* Payment method card */
        .ts-payment-list {
            display: grid;
            gap: 8px;
        }
        .ts-payment-option {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            padding: 10px 14px;
            background: var(--surface-2);
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease;
        }
        .ts-payment-option:has(input:checked),
        .ts-payment-option.selected {
            border-color: var(--ch-yellow);
            background: var(--ch-yellow-tint);
        }
        .ts-payment-option input[type="radio"] {
            accent-color: var(--ch-yellow-deep);
            flex-shrink: 0;
        }
        .ts-payment-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
        }
        .ts-payment-sub {
            font-size: 11px;
            color: var(--muted);
            margin-top: 1px;
        }

        /* ── CTA / status card ──────────────────────────────────────── */
        .ts-cta-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-1);
            overflow: hidden;
        }
        .ts-cta-status {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--hairline);
            background: var(--canvas);
        }
        .ts-cta-status-text {
            font-family: var(--font-display), sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
        }
        .ts-cta-status-sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }
        .ts-cta-body { padding: 16px 20px; }
        .ts-cta-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .ts-cta-actions .btn { min-height: 40px; }
        @media (max-width: 639px) {
            .ts-cta-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .ts-cta-actions .btn,
            .ts-cta-actions form,
            .ts-cta-actions form .btn { width: 100%; }
        }

        /* Sent-request map */
        .ts-sent-map-card {
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 12px;
            display: grid;
            gap: 8px;
            margin-bottom: 12px;
        }
        .ts-sent-map-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
        }
        .ts-sent-map {
            width: 100%;
            height: 240px;
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            overflow: hidden;
            background: #eef2f7;
        }
        .ts-sent-map .leaflet-control-attribution { display: none; }

        /* Join request form */
        .ts-join-form { display: grid; gap: 0; }
        .request-route-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 20px;
            display: grid;
            gap: 16px;
            box-shadow: var(--shadow-1);
            scroll-margin-top: 96px;
        }
        .request-route-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--hairline);
        }
        .request-route-title-block { display: grid; gap: 4px; min-width: 0; }
        .request-route-title {
            color: var(--ink);
            font-family: var(--font-display), sans-serif;
            font-size: 15px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .request-route-subtitle { margin: 0; color: var(--muted); font-size: 12px; line-height: 1.4; }
        .request-route-badge {
            border: 1px solid var(--info-soft);
            background: var(--info-soft);
            color: var(--info-ink);
            border-radius: var(--r-pill);
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }
        .request-main-grid { display: grid; gap: 16px; align-items: stretch; }
        .request-control-column { display: grid; gap: 12px; align-content: start; }
        .request-map-column { display: grid; gap: 12px; align-content: start; }
        .request-section {
            border: 1px solid var(--hairline);
            background: var(--surface);
            border-radius: var(--r-md);
            padding: 14px;
            display: grid;
            gap: 12px;
        }
        .request-section[hidden] { display: none; }
        .request-section-head { display: grid; gap: 4px; }
        .request-section-title {
            color: var(--ink);
            font-size: 13px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .request-section-hint { color: var(--muted); font-size: 12px; line-height: 1.4; }
        .request-mode-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 10px;
        }
        .request-option-group {
            border: 1px solid var(--hairline);
            background: var(--surface-2);
            border-radius: var(--r-sm);
            padding: 12px;
            display: grid;
            gap: 10px;
            align-content: start;
        }
        .request-option-label {
            color: var(--ink-3);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .request-radio-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .request-option {
            min-height: 38px;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface);
            color: var(--ink-2);
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            text-align: center;
        }
        .request-option input { margin: 0; accent-color: var(--ink); }
        .request-help { margin: 0; color: var(--muted); font-size: 12px; line-height: 1.4; }
        .request-map-card {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 12px;
            display: grid;
            gap: 10px;
        }
        .request-map-card[hidden] { display: none; }
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
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .request-map-targets { display: inline-flex; gap: 6px; flex-wrap: wrap; }
        .request-map-target {
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-pill);
            background: var(--surface);
            color: var(--ink-2);
            height: 30px;
            padding: 0 12px;
            font-size: 12px;
            font-weight: 800;
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
            border-radius: var(--r-sm);
            overflow: hidden;
            background: #eef2f7;
        }
        .request-route-map .leaflet-control-attribution { display: none; }
        .request-map-status {
            border: 1px solid var(--hairline);
            background: var(--surface);
            border-radius: var(--r-sm);
            color: var(--ink-3);
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }
        .request-map-status.blocked { border-color: #fecaca; background: var(--danger-soft); color: var(--danger-ink); }
        .request-map-status.ok { border-color: #86efac; background: var(--success-soft); color: var(--success-ink); }
        .request-map-legend {
            display: flex;
            align-items: center;
            gap: 7px 10px;
            flex-wrap: wrap;
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
        }
        .request-map-legend span { display: inline-flex; align-items: center; gap: 5px; }
        .request-map-legend i { width: 18px; height: 5px; border-radius: 999px; display: inline-block; }
        .legend-route { background: #94a3b8; }
        .legend-preview { background: #1d4ed8; }
        .legend-zone { background: rgba(15, 23, 42, .18); }
        .legend-driver-point { width: 8px !important; height: 8px !important; background: #16a34a; }
        .legend-pin { width: 8px !important; height: 8px !important; background: var(--ink); }

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

        .request-fare-preview {
            border: 1px solid var(--ch-yellow-line);
            border-radius: var(--r-sm);
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
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .request-fare-preview-badge {
            border: 1px solid #fcd34d;
            border-radius: var(--r-pill);
            background: rgba(255,255,255,.75);
            color: var(--warning-ink);
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 800;
        }
        .request-fare-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 7px;
        }
        .request-fare-item {
            border: 1px solid var(--ch-yellow-line);
            border-radius: var(--r-sm);
            background: rgba(255,255,255,.72);
            padding: 8px;
            display: grid;
            gap: 2px;
        }
        .request-fare-label {
            color: var(--warning);
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .request-fare-value { color: var(--ch-yellow-ink); font-size: 15px; font-weight: 800; }
        .request-fare-note { margin: 0; color: var(--warning-ink); font-size: 12px; line-height: 1.4; }
        .request-submit-row {
            border: 1px solid var(--hairline);
            background: var(--surface-2);
            border-radius: var(--r-md);
            padding: 14px;
            display: grid;
            gap: 12px;
        }
        .request-input {
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface);
            color: var(--ink);
            padding: 10px 12px;
            font-size: 13px;
            width: 100%;
            box-sizing: border-box;
        }
        .request-input:focus {
            outline: 2px solid var(--ch-yellow-deep);
            outline-offset: 1px;
            border-color: var(--ch-yellow-deep);
        }
        .request-action-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            width: 100%;
        }
        @media (max-width: 639px) {
            .request-action-bar { flex-direction: column; align-items: stretch; }
            .request-action-bar .btn,
            .request-action-bar form,
            .request-action-bar form .btn { width: 100%; }
        }
        @media (min-width: 768px) {
            .request-mode-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .request-route-map { height: 280px; }
            .request-fare-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        @media (min-width: 1180px) {
            .request-main-grid { grid-template-columns: minmax(390px, 0.75fr) minmax(620px, 1.25fr); }
            .request-control-column,
            .request-map-column { height: 100%; }
            .request-map-column .request-section { min-height: 100%; }
            .request-route-map { height: 380px; }
            .request-fare-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        /* AI note */
        .ts-ai-note {
            background: var(--info-soft);
            border: 1px solid #93c5fd;
            border-radius: var(--r-sm);
            padding: 10px 12px;
            color: var(--info-ink);
            font-size: 13px;
            line-height: 1.5;
        }

        /* Contact links */
        .ts-contact-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .ts-contact-link {
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            background: var(--surface);
            color: var(--ink);
            height: 36px;
            padding: 0 12px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
        }
        .ts-contact-link.whatsapp { background: var(--success-soft); border-color: #86efac; color: var(--success-ink); }
        .ts-contact-link.email { background: var(--ch-yellow-tint); border-color: var(--ch-yellow-line); color: var(--warning-ink); }
        .ts-contact-link.is-disabled { pointer-events: none; background: var(--surface-2); border-color: var(--hairline); color: var(--muted-2); }

        /* Passengers list */
        .ts-passenger-list { display: grid; gap: 6px; }
        .ts-passenger-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            padding: 8px 10px;
        }
        .ts-passenger-meta { min-width: 0; flex: 1; }
        .ts-passenger-name { font-size: 13px; font-weight: 700; color: var(--ink); line-height: 1.2; }
        .ts-passenger-email { font-size: 11px; color: var(--muted); word-break: break-word; }
        .ts-empty-note { font-size: 13px; color: var(--muted); font-style: italic; }

        /* Mobile overrides */
        @media (max-width: 1023px) {
            .ts-wrap { padding: 0 16px 24px; }
            .ts-page-title { font-size: 20px; }
            .ts-map-kv-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .ts-kv-item:nth-child(3),
            .ts-kv-item:nth-child(4) { border-top: 1px solid var(--hairline); }
            .ts-kv-item:nth-child(1),
            .ts-kv-item:nth-child(3) { border-left: none; }
        }
    </style>

    <div class="ts-wrap">

        {{-- ── Breadcrumb ──────────────────────────────────────────── --}}
        <nav class="ts-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('explore.index') }}"><i class="fa-solid fa-arrow-left"></i> Back to Explore</a>
            <span class="ts-breadcrumb-sep">/</span>
            <span>Trip details</span>
        </nav>

        {{-- ── Page header ─────────────────────────────────────────── --}}
        <div class="ts-page-header">
            <div class="ts-header-copy">
                <p class="ts-eyebrow">Trip details</p>
                <h1 class="ts-page-title">
                    {{ $pickupName }}
                    <span class="ts-page-title-arrow">&#8594;</span>
                    {{ $destinationName }}
                </h1>
                <p class="ts-page-sub">
                    {{ $trip->driver?->name ?: '—' }}
                    &middot; {{ $isTwoWay ? 'Two-way' : 'One-way' }}
                    @if($availableSeats !== null)
                        &middot; {{ $availableSeats > 0 ? $availableSeats . ' seat' . ($availableSeats !== 1 ? 's' : '') . ' available' : 'No seats left' }}
                    @endif
                    &middot; {{ $trip->trip_datetime?->format('D, d M Y · H:i') ?: '—' }}
                </p>
                @if(!empty($aiRecommendation))
                    <div class="ts-ai-note" style="margin-top:10px;">
                        <i class="fa-solid fa-sparkles" style="margin-right:5px;"></i>
                        <strong>AI Match: {{ number_format((float) ($aiRecommendation['match_score'] ?? 0), 0) }}%</strong>
                        &mdash; {{ implode(' ', array_slice((array) ($aiRecommendation['explanations'] ?? []), 0, 3)) }}
                    </div>
                @endif
            </div>
            <div class="ts-header-actions">
                @php
                    $waUrl = ($canViewDriverWhatsapp ?? false) ? $trip->driver?->whatsapp_url : null;
                    $emailUrl = ($canViewDriverEmail ?? false) && $trip->driver?->email
                        ? ('mailto:' . $trip->driver->email)
                        : null;
                @endphp
                <a href="{{ $waUrl ?: '#' }}"
                   class="btn btn-ghost btn-sm ts-contact-link {{ $waUrl ? 'whatsapp' : 'is-disabled' }}"
                   target="_blank" rel="noopener">
                    <i class="fa-brands fa-whatsapp"></i> Message driver
                </a>
                @if($isJoined)
                    <span class="btn btn-soft btn-sm" style="color:var(--success-ink);border-color:var(--success-soft);">
                        <i class="fa-solid fa-check"></i> Approved
                    </span>
                @elseif($myRequest && $myRequest->status === 'pending')
                    <span class="btn btn-soft btn-sm" style="color:var(--warning-ink);border-color:var(--warning-soft);">
                        <i class="fa-regular fa-clock"></i> Pending
                    </span>
                @elseif($isFull || ! $trip->is_open_for_request)
                    <span class="btn btn-soft btn-sm" style="color:var(--muted);cursor:default;">
                        <i class="fa-solid fa-ban"></i> {{ $isFull ? 'Full' : 'Closed' }}
                    </span>
                @else
                    <a href="#join-request" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-user-plus"></i>
                        Request seat &middot; RM {{ number_format((float) $trip->fare_per_person, 2) }}
                    </a>
                @endif
            </div>
        </div>

        {{-- ── Main body grid ──────────────────────────────────────── --}}
        <div class="ts-body-grid">

            {{-- ════════════════════════════════════════════════════════
                 LEFT COLUMN
            ════════════════════════════════════════════════════════ --}}
            <div class="ts-left-col">

                {{-- Map card with 4-col KV grid --}}
                <div class="ts-map-card">
                    <div id="requestRouteMap" class="ts-map-inner"></div>
                    <div class="ts-map-kv-grid">
                        <div class="ts-kv-item">
                            <span class="ts-kv-label">Departure</span>
                            <span class="ts-kv-value">{{ $trip->trip_datetime?->format('H:i') ?: '—' }}</span>
                        </div>
                        <div class="ts-kv-item">
                            <span class="ts-kv-label">ETA</span>
                            <span class="ts-kv-value">—</span>
                        </div>
                        <div class="ts-kv-item">
                            <span class="ts-kv-label">Distance</span>
                            <span class="ts-kv-value">—</span>
                        </div>
                        <div class="ts-kv-item">
                            <span class="ts-kv-label">Stops</span>
                            <span class="ts-kv-value">
                                @php $stopCount = count((array) ($trip->route_stops ?? [])); @endphp
                                {{ $stopCount > 0 ? $stopCount . ' stop' . ($stopCount !== 1 ? 's' : '') : 'Direct' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- "Your route preference" card —
                     When the user hasn't sent a request yet, show the join form here.
                     When they have, show their current status. --}}
                @if($isJoined)
                    <div class="ts-cta-card" id="join-request">
                        <div class="ts-cta-status">
                            <div>
                                <div class="ts-cta-status-text">You're In!</div>
                                <div class="ts-cta-status-sub">You have joined this trip.</div>
                            </div>
                            <span class="badge badge-success" style="font-size:13px;padding:7px 14px;">Approved</span>
                        </div>
                        <div class="ts-cta-body">
                            <div class="ts-cta-actions">
                                <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">Back to Explore</a>
                            </div>
                        </div>
                    </div>

                @elseif($myRequest && $myRequest->status === 'pending')
                    <div class="ts-cta-card" id="join-request">
                        <div class="ts-cta-status">
                            <div>
                                <div class="ts-cta-status-text">Request Pending</div>
                                <div class="ts-cta-status-sub">Waiting for the driver to review your request.</div>
                            </div>
                            <span class="badge badge-info" style="font-size:13px;padding:7px 14px;">Pending</span>
                        </div>
                        <div class="ts-cta-body">
                            @if($myRequest->routePoint)
                                <div class="ts-sent-map-card">
                                    <span class="ts-sent-map-title">{{ $myRequest->routePoint->route_fit_label ?: 'Your route preview' }}</span>
                                    <div
                                        id="sentRequestMap"
                                        class="ts-sent-map"
                                        data-driver-pickup-lat="{{ $trip->pickup_latitude }}"
                                        data-driver-pickup-lng="{{ $trip->pickup_longitude }}"
                                        data-driver-dropoff-lat="{{ $trip->destination_latitude }}"
                                        data-driver-dropoff-lng="{{ $trip->destination_longitude }}"
                                        data-passenger-pickup-lat="{{ $myRequest->routePoint->pickup_latitude }}"
                                        data-passenger-pickup-lng="{{ $myRequest->routePoint->pickup_longitude }}"
                                        data-passenger-dropoff-lat="{{ $myRequest->routePoint->dropoff_latitude }}"
                                        data-passenger-dropoff-lng="{{ $myRequest->routePoint->dropoff_longitude }}"
                                        data-uses-default-pickup="{{ $myRequest->routePoint->uses_default_pickup ? '1' : '0' }}"
                                        data-uses-default-dropoff="{{ $myRequest->routePoint->uses_default_dropoff ? '1' : '0' }}"
                                        data-passenger-name="{{ auth()->user()?->name ?: 'Passenger' }}"
                                    ></div>
                                </div>
                            @endif
                            <div class="ts-cta-actions">
                                <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">Back to Explore</a>
                                <form method="POST" action="{{ route('explore.join-requests.cancel', $myRequest) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-danger btn-sm">Cancel Request</button>
                                </form>
                            </div>
                        </div>
                    </div>

                @elseif($isFull || ! $trip->is_open_for_request)
                    <div class="ts-cta-card" id="join-request">
                        <div class="ts-cta-status">
                            <div>
                                <div class="ts-cta-status-text">{{ $isFull ? 'Trip Full' : 'Not Available' }}</div>
                                <div class="ts-cta-status-sub">{{ $isFull ? 'All seats have been taken.' : 'This trip is not accepting requests right now.' }}</div>
                            </div>
                            <span class="badge badge-danger" style="font-size:13px;padding:7px 14px;">{{ $isFull ? 'Full' : 'Closed' }}</span>
                        </div>
                        <div class="ts-cta-body">
                            <div class="ts-cta-actions">
                                <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">Back to Explore</a>
                            </div>
                        </div>
                    </div>

                @else
                    {{-- Join request form --}}
                    <form method="POST" action="{{ route('explore.request-join', $trip) }}" class="ts-join-form">
                        @csrf
                        <div class="request-route-card" id="join-request">
                            <div class="request-route-head">
                                <div class="request-route-title-block">
                                    <div class="request-route-title">
                                        <i class="fa-solid fa-route"></i>
                                        <span>Your route preference</span>
                                    </div>
                                    <p class="request-route-subtitle">Use the standard trip points or pin nearby stops along the current route. The driver will review your request before approval.</p>
                                </div>
                                <span class="request-route-badge">Public request</span>
                            </div>

                            {{-- Pickup + Drop-off fields in 2-col grid --}}
                            <div class="ts-pref-fields-grid">
                                <div>
                                    <div class="ts-pref-field-label">Pickup point</div>
                                    <div class="request-mode-grid" style="grid-template-columns:1fr;gap:8px;">
                                        <div class="request-option-group">
                                            <div class="request-radio-row">
                                                <label class="request-option">
                                                    <input type="radio" name="pickup_mode" value="default" {{ old('pickup_mode', 'default') === 'default' ? 'checked' : '' }}>
                                                    <span>Use trip pickup</span>
                                                </label>
                                                <label class="request-option">
                                                    <input type="radio" name="pickup_mode" value="custom" {{ old('pickup_mode') === 'custom' ? 'checked' : '' }}>
                                                    <span>Custom pickup</span>
                                                </label>
                                            </div>
                                            <input type="hidden" name="pickup_name" value="{{ old('pickup_name') }}">
                                            <input type="hidden" name="pickup_latitude" value="{{ old('pickup_latitude') }}">
                                            <input type="hidden" name="pickup_longitude" value="{{ old('pickup_longitude') }}">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="ts-pref-field-label">Drop-off point</div>
                                    <div class="request-mode-grid" style="grid-template-columns:1fr;gap:8px;">
                                        <div class="request-option-group">
                                            <div class="request-radio-row">
                                                <label class="request-option">
                                                    <input type="radio" name="dropoff_mode" value="default" {{ old('dropoff_mode', 'default') === 'default' ? 'checked' : '' }}>
                                                    <span>Use destination</span>
                                                </label>
                                                <label class="request-option">
                                                    <input type="radio" name="dropoff_mode" value="custom" {{ old('dropoff_mode') === 'custom' ? 'checked' : '' }}>
                                                    <span>Custom drop-off</span>
                                                </label>
                                            </div>
                                            <input type="hidden" name="dropoff_name" value="{{ old('dropoff_name') }}">
                                            <input type="hidden" name="dropoff_latitude" value="{{ old('dropoff_latitude') }}">
                                            <input type="hidden" name="dropoff_longitude" value="{{ old('dropoff_longitude') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="extra_fee_amount" value="{{ old('extra_fee_amount') }}">
                            <input type="hidden" name="detour_distance_km" value="{{ old('detour_distance_km') }}">

                            {{-- Route & fare map preview --}}
                            <div class="request-map-card" data-route-picker>
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
                                            <span class="request-fare-value" id="farePreviewPassenger">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                        </div>
                                        <div class="request-fare-item">
                                            <span class="request-fare-label">Base split</span>
                                            <span class="request-fare-value" id="farePreviewOthers">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                        </div>
                                    </div>
                                    <p class="request-fare-note" id="farePreviewNote">The normal fare remains the base. Custom pickup/drop-off only adds an extra charge based on distance from the original route.</p>
                                </div>
                            </div>

                            {{-- Estimated fare box --}}
                            <div class="ts-fare-est-box">
                                <div>
                                    <div class="ts-fare-est-label">Estimated fare</div>
                                    <div class="ts-fare-est-amount">RM {{ number_format((float) $trip->fare_per_person, 2) }}</div>
                                    <div class="ts-fare-est-unit">{{ $splitTypeText }}</div>
                                </div>
                                @if($availableSeats !== null)
                                    <span class="badge {{ $availableSeats > 0 ? 'badge-success' : 'badge-danger' }}" style="font-size:13px;padding:6px 14px;">
                                        {{ $availableSeats > 0 ? $availableSeats . ' seat' . ($availableSeats !== 1 ? 's' : '') . ' left' : 'Full' }}
                                    </span>
                                @endif
                            </div>

                            <div class="request-submit-row">
                                <input class="request-input" type="text" name="request_note" placeholder="Add a note for the driver (optional)">
                                <p class="request-help">After approval, arrange final timing and coordination with the driver through WhatsApp or email.</p>
                                <div class="request-action-bar">
                                    <a href="{{ route('explore.index') }}" class="btn btn-ghost btn-sm">Back</a>
                                    <button type="submit" class="btn btn-primary">Send request</button>
                                </div>
                            </div>
                        </div>
                    </form>
                @endif

            </div>{{-- .ts-left-col --}}

            {{-- ════════════════════════════════════════════════════════
                 RIGHT COLUMN
            ════════════════════════════════════════════════════════ --}}
            <div class="ts-right-col">

                {{-- Driver card --}}
                <div class="ts-card">
                    <div class="ts-card-header">
                        <h3 class="ts-card-title"><i class="fa-solid fa-steering-wheel"></i> Driver</h3>
                        @php
                            $statusBadgeClass = match(strtolower($trip->status ?? '')) {
                                'active', 'open'    => 'badge-success',
                                'pending'           => 'badge-warning',
                                'cancelled','closed'=> 'badge-danger',
                                'completed'         => 'badge-info',
                                default             => 'badge-dark',
                            };
                        @endphp
                        <span class="badge {{ $statusBadgeClass }}">{{ $statusText }}</span>
                    </div>
                    <div class="ts-card-body">
                        <div class="ts-driver-block">
                            <span class="ts-avatar">{{ strtoupper(substr((string) ($trip->driver?->name ?? 'D'), 0, 1)) }}</span>
                            <div class="ts-driver-meta">
                                <div class="ts-driver-name">
                                    {{ $trip->driver?->name ?: '—' }}
                                    <span class="badge badge-yellow" style="margin-left:4px;font-size:10px;vertical-align:middle;">Driver</span>
                                </div>
                                <div class="ts-driver-email">{{ $trip->driver?->email ?: '—' }}</div>
                                <div class="ts-driver-rating">
                                    <i class="fa-solid fa-star"></i> 5.0
                                    &middot; {{ $passengerCount }} trip{{ $passengerCount !== 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>

                        {{-- Vehicle KV 2x2 --}}
                        <div class="ts-vehicle-grid">
                            <div class="ts-vehicle-item">
                                <div class="ts-vehicle-label">Car</div>
                                <div class="ts-vehicle-value">—</div>
                            </div>
                            <div class="ts-vehicle-item">
                                <div class="ts-vehicle-label">Plate</div>
                                <div class="ts-vehicle-value">—</div>
                            </div>
                            <div class="ts-vehicle-item">
                                <div class="ts-vehicle-label">Color</div>
                                <div class="ts-vehicle-value">—</div>
                            </div>
                            <div class="ts-vehicle-item">
                                <div class="ts-vehicle-label">Year</div>
                                <div class="ts-vehicle-value">—</div>
                            </div>
                        </div>

                        {{-- Verified badges --}}
                        <div class="ts-badges-row">
                            <span class="badge badge-success"><i class="fa-solid fa-shield-check" style="margin-right:3px;"></i> Verified driver</span>
                            <span class="badge badge-info"><i class="fa-solid fa-id-card" style="margin-right:3px;"></i> IC verified</span>
                        </div>

                        {{-- Trip stat + passengers --}}
                        <div style="font-size:12px;color:var(--muted);font-weight:600;">
                            <i class="fa-solid fa-users" style="margin-right:4px;"></i>
                            {{ $passengerCount }} passenger{{ $passengerCount !== 1 ? 's' : '' }}
                            @if($availableSeats !== null)
                                &middot; {{ $availableSeats }} seat{{ $availableSeats !== 1 ? 's' : '' }} left
                            @endif
                            &middot; Joined {{ $trip->driver?->created_at?->format('M Y') ?: '—' }}
                        </div>

                        {{-- Passengers list --}}
                        @if($passengerCount > 0)
                            <div class="ts-passenger-list">
                                @foreach($passengers as $participant)
                                    <div class="ts-passenger-item">
                                        <span class="ts-avatar ts-avatar-sm">{{ strtoupper(substr((string) ($participant->user?->name ?? 'P'), 0, 1)) }}</span>
                                        <div class="ts-passenger-meta">
                                            <div class="ts-passenger-name">{{ $participant->user?->name ?: '—' }}</div>
                                            <div class="ts-passenger-email">{{ $participant->user?->email ?: '—' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Contact driver --}}
                        <div class="ts-contact-row">
                            <a href="{{ $waUrl ?: '#' }}" target="_blank" rel="noopener"
                               class="ts-contact-link whatsapp {{ $waUrl ? '' : 'is-disabled' }}">
                                <i class="fa-brands fa-whatsapp"></i>
                                <span>WhatsApp</span>
                            </a>
                            <a href="{{ $emailUrl ?: '#' }}"
                               class="ts-contact-link email {{ $emailUrl ? '' : 'is-disabled' }}">
                                <i class="fa-regular fa-envelope"></i>
                                <span>Email</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Fare breakdown card --}}
                <div class="ts-card">
                    <div class="ts-card-header">
                        <h3 class="ts-card-title"><i class="fa-solid fa-receipt"></i> Fare breakdown</h3>
                        @if($isFull)
                            <span class="badge badge-danger">Trip Full</span>
                        @endif
                    </div>
                    <div class="ts-card-body">
                        <div class="ts-fare-line-list">
                            <div class="ts-fare-line">
                                <span class="ts-fare-line-label">Base fare per seat</span>
                                <span class="ts-fare-line-value">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                            </div>
                            <div class="ts-fare-line">
                                <span class="ts-fare-line-label">Detour adjustment</span>
                                <span class="ts-fare-line-value">RM 0.00</span>
                            </div>
                            <div class="ts-fare-line">
                                <span class="ts-fare-line-label">Platform fee</span>
                                <span class="ts-fare-line-value">RM 0.00</span>
                            </div>
                        </div>
                        <div class="ts-fare-total-row">
                            <span class="ts-fare-total-label">Total per seat</span>
                            <span class="ts-fare-total-value">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                        </div>
                        <div style="font-size:11px;color:var(--muted);">{{ $splitTypeText }} &middot; {{ $isTwoWay ? 'Two-way trip' : 'One-way trip' }}</div>

                        {{-- Public note --}}
                        @if($trip->public_note)
                            <div style="background:var(--warning-soft);border:1px solid #fcd34d;border-radius:var(--r-sm);padding:10px 12px;color:var(--warning-ink);font-size:13px;line-height:1.5;">
                                <i class="fa-regular fa-note-sticky" style="margin-right:5px;"></i>{{ $trip->public_note }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Payment method card --}}
                <div class="ts-card">
                    <div class="ts-card-header">
                        <h3 class="ts-card-title"><i class="fa-solid fa-wallet"></i> Payment method</h3>
                    </div>
                    <div class="ts-card-body">
                        <div class="ts-payment-list">
                            <label class="ts-payment-option">
                                <input type="radio" name="payment_method" value="duitnow" checked>
                                <div>
                                    <div class="ts-payment-label">DuitNow QR</div>
                                    <div class="ts-payment-sub">Instant transfer via DuitNow</div>
                                </div>
                            </label>
                            <label class="ts-payment-option">
                                <input type="radio" name="payment_method" value="bsn">
                                <div>
                                    <div class="ts-payment-label">BSN / Online Banking</div>
                                    <div class="ts-payment-sub">Bank transfer before departure</div>
                                </div>
                            </label>
                            <label class="ts-payment-option">
                                <input type="radio" name="payment_method" value="cash">
                                <div>
                                    <div class="ts-payment-label">Cash</div>
                                    <div class="ts-payment-sub">Pay the driver directly</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

            </div>{{-- .ts-right-col --}}

        </div>{{-- .ts-body-grid --}}

    </div>{{-- .ts-wrap --}}

    <script>
        (() => {
            const tripRoute = {
                pickup: {
                    name: @json($pickupName),
                    lat: @json($trip->pickup_latitude),
                    lng: @json($trip->pickup_longitude),
                },
                dropoff: {
                    name: @json($destinationName),
                    lat: @json($trip->destination_latitude),
                    lng: @json($trip->destination_longitude),
                },
            };
            const currentPassengerName = @json($currentPassengerName);
            const passengerLabel = (suffix) => `${currentPassengerName} ${suffix}`;
            const toNumber = (value) => {
                const number = Number.parseFloat(String(value ?? ''));
                return Number.isFinite(number) ? number : null;
            };
            const pickupLat = toNumber(tripRoute.pickup.lat);
            const pickupLng = toNumber(tripRoute.pickup.lng);
            const dropoffLat = toNumber(tripRoute.dropoff.lat);
            const dropoffLng = toNumber(tripRoute.dropoff.lng);
            const routePoints = [pickupLat, pickupLng, dropoffLat, dropoffLng].every((value) => value !== null)
                ? [[pickupLat, pickupLng], [dropoffLat, dropoffLng]]
                : [];
            let activeMapTarget = 'pickup';
            let routeLinePoints = routePoints;
            let pickupMarker = null;
            let dropoffMarker = null;
            let allowedRouteRadiusKm = 0.2;
            let allowedEndpointRadiusKm = 0.5;
            const defaultFarePerPerson = Number(@json((float) $trip->fare_per_person));
            const tripFareTotal = Number(@json((float) $trip->fare_total));
            let baseRouteDistanceKm = null;
            let previewRequestToken = 0;
            let updateFarePreview = () => {};

            const setMode = (target, mode) => {
                const modeInput = document.querySelector(`input[name="${target}_mode"][value="${mode}"]`);
                if (modeInput) modeInput.checked = true;
            };

            const setCoordinate = (target, lat, lng) => {
                const latInput = document.querySelector(`input[name="${target}_latitude"]`);
                const lngInput = document.querySelector(`input[name="${target}_longitude"]`);
                if (latInput) latInput.value = lat === null ? '' : lat.toFixed(7);
                if (lngInput) lngInput.value = lng === null ? '' : lng.toFixed(7);
            };

            const setTextInput = (target, value) => {
                const textInput = document.querySelector(`input[name="${target}_name"]`);
                if (textInput) textInput.value = value;
            };

            const setStatus = (message, type = '') => {
                const status = document.getElementById('requestMapStatus');
                if (!status) return;
                status.textContent = message;
                status.classList.toggle('blocked', type === 'blocked');
                status.classList.toggle('ok', type === 'ok');
            };

            const updateAllowedRadiusForDistance = (distanceKm) => {
                const routeKm = Number(distanceKm) || distanceForPointsKm([pickupEndpoint, dropoffEndpoint]) || 1;

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
                if (label) {
                    label.textContent = `Allowed: route ${Math.round(allowedRouteRadiusKm * 1000)}m, Point A/B ${Math.round(allowedEndpointRadiusKm * 1000)}m`;
                }
            };

            const setActiveMapTarget = (target) => {
                activeMapTarget = target === 'dropoff' ? 'dropoff' : 'pickup';
                document.querySelectorAll('.request-map-target').forEach((button) => {
                    button.classList.toggle('active', button.dataset.mapTarget === activeMapTarget);
                });
                setStatus(`Tap near the route to pin ${activeMapTarget === 'pickup' ? 'pickup' : 'drop-off'}.`);
            };

            const updateMapVisibility = () => {
                const customPickup = document.querySelector('input[name="pickup_mode"]:checked')?.value === 'custom';
                const customDropoff = document.querySelector('input[name="dropoff_mode"]:checked')?.value === 'custom';
                const mapCard = document.querySelector('[data-route-picker]');
                const previewSection = document.querySelector('[data-preview-section]');
                const pickupTarget = document.querySelector('.request-map-target[data-map-target="pickup"]');
                const dropoffTarget = document.querySelector('.request-map-target[data-map-target="dropoff"]');
                const hasCustom = customPickup || customDropoff;

                if (previewSection) previewSection.hidden = false;
                if (mapCard) mapCard.hidden = false;
                if (pickupTarget) pickupTarget.hidden = !customPickup;
                if (dropoffTarget) dropoffTarget.hidden = !customDropoff;

                if (activeMapTarget === 'pickup' && !customPickup && customDropoff) {
                    setActiveMapTarget('dropoff');
                } else if (activeMapTarget === 'dropoff' && !customDropoff && customPickup) {
                    setActiveMapTarget('pickup');
                } else if (!hasCustom) {
                    document.querySelectorAll('.request-map-target').forEach((button) => button.classList.remove('active'));
                    setStatus('Default trip points selected. Choose custom pickup or drop-off to pin a nearby stop.');
                }

                setTimeout(() => {
                    if (!map) return;
                    map.invalidateSize();
                    if (routeLinePoints.length > 1) {
                        drawRoute(routeLinePoints);
                    }
                    if (previewLayerGroup && typeof updateFarePreview === 'function') {
                        updateFarePreview();
                    }
                }, 180);
            };

            document.querySelectorAll('.request-map-target').forEach((button) => {
                button.addEventListener('click', () => setActiveMapTarget(button.dataset.mapTarget));
            });

            document.querySelectorAll('input[name="pickup_mode"], input[name="dropoff_mode"]').forEach((input) => {
                input.addEventListener('change', () => {
                    const target = input.name.replace('_mode', '');
                    if (input.value === 'custom') {
                        setActiveMapTarget(target);
                        setStatus(`Tap near the route to pin ${target === 'pickup' ? 'pickup' : 'drop-off'}.`);
                        updateMapVisibility();
                        return;
                    }
                    setCoordinate(target, null, null);
                    setTextInput(target, '');
                    if (target === 'pickup' && pickupMarker) {
                        pickupMarker.remove();
                        pickupMarker = null;
                    }
                    if (target === 'dropoff' && dropoffMarker) {
                        dropoffMarker.remove();
                        dropoffMarker = null;
                    }
                    setStatus(`${target === 'pickup' ? 'Pickup' : 'Drop-off'} reset to default trip point.`);
                    updateMapVisibility();
                    updateFarePreview();
                });
            });

            const mapEl = document.getElementById('requestRouteMapForm');
            if (!mapEl || !routePoints.length || typeof window.L === 'undefined') {
                if (mapEl) setStatus('Map is unavailable because this route has no coordinates.', 'blocked');
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

            const routeLayerGroup = window.L.layerGroup().addTo(map);
            const markerLayerGroup = window.L.layerGroup().addTo(map);
            const previewLayerGroup = window.L.layerGroup().addTo(map);
            const pickupEndpoint = window.L.latLng(pickupLat, pickupLng);
            const dropoffEndpoint = window.L.latLng(dropoffLat, dropoffLng);

            const drawRoute = (points) => {
                routeLayerGroup.clearLayers();
                const latLngs = points.map((point) => window.L.latLng(point[0], point[1]));
                window.L.polyline(latLngs, {
                    color: '#475569',
                    weight: 18,
                    opacity: 0.16,
                    lineCap: 'round',
                    interactive: false,
                }).addTo(routeLayerGroup);
                window.L.polyline(latLngs, {
                    color: '#64748b',
                    weight: 4,
                    opacity: 0.82,
                    lineCap: 'round',
                    interactive: false,
                }).addTo(routeLayerGroup);
                window.L.circle(pickupEndpoint, {
                    radius: allowedEndpointRadiusKm * 1000,
                    color: '#16a34a',
                    weight: 1,
                    fillColor: '#16a34a',
                    fillOpacity: 0.07,
                    opacity: 0.28,
                    interactive: false,
                }).addTo(routeLayerGroup);
                window.L.circle(dropoffEndpoint, {
                    radius: allowedEndpointRadiusKm * 1000,
                    color: '#2563eb',
                    weight: 1,
                    fillColor: '#2563eb',
                    fillOpacity: 0.07,
                    opacity: 0.28,
                    interactive: false,
                }).addTo(routeLayerGroup);
                window.L.circleMarker(pickupEndpoint, {
                    radius: 6,
                    color: '#fff',
                    weight: 2,
                    fillColor: '#16a34a',
                    fillOpacity: 1,
                    interactive: false,
                }).addTo(routeLayerGroup);
                window.L.circleMarker(dropoffEndpoint, {
                    radius: 6,
                    color: '#fff',
                    weight: 2,
                    fillColor: '#2563eb',
                    fillOpacity: 1,
                    interactive: false,
                }).addTo(routeLayerGroup);
                map.fitBounds(window.L.latLngBounds(latLngs), { padding: [22, 22] });
            };

            const formatMoney = (value) => `RM ${Math.max(0, Number(value) || 0).toFixed(2)}`;

            const readMode = (target) => {
                const checked = document.querySelector(`input[name="${target}_mode"]:checked`);
                return checked?.value === 'custom' ? 'custom' : 'default';
            };

            const readSelectedPoint = (target) => {
                const mode = readMode(target);
                if (mode === 'default') {
                    return target === 'pickup' ? pickupEndpoint : dropoffEndpoint;
                }

                const lat = toNumber(document.querySelector(`input[name="${target}_latitude"]`)?.value);
                const lng = toNumber(document.querySelector(`input[name="${target}_longitude"]`)?.value);
                if (lat === null || lng === null) {
                    return null;
                }

                return window.L.latLng(lat, lng);
            };

            const samePoint = (a, b) => Math.abs(a.lat - b.lat) < 0.00001 && Math.abs(a.lng - b.lng) < 0.00001;

            const uniqueWaypoints = (points) => points.reduce((items, point) => {
                if (!point) return items;
                if (!items.length || !samePoint(items[items.length - 1], point)) {
                    items.push(point);
                }
                return items;
            }, []);

            const distanceForPointsKm = (points) => {
                let total = 0;
                for (let index = 0; index < points.length - 1; index += 1) {
                    total += points[index].distanceTo(points[index + 1]) / 1000;
                }
                return total;
            };

            const fixedJoinRoute = (passengerPickup, passengerDropoff, customPickup, customDropoff) => {
                const stops = [
                    { label: 'Driver pickup', point: pickupEndpoint },
                    ...(customPickup ? [{ label: passengerLabel('pickup'), point: passengerPickup }] : []),
                    ...(customDropoff ? [{ label: passengerLabel('drop-off'), point: passengerDropoff }] : []),
                    { label: 'Driver drop-off', point: dropoffEndpoint },
                ];

                const uniqueStops = stops.reduce((items, stop) => {
                    if (!stop.point) return items;
                    const last = items[items.length - 1];
                    if (!last || !samePoint(last.point, stop.point)) {
                        items.push(stop);
                    }
                    return items;
                }, []);

                return {
                    points: uniqueStops.map((stop) => stop.point),
                    order: uniqueStops.map((stop) => stop.label),
                };
            };

            const fetchRoute = async (points) => {
                const waypoints = uniqueWaypoints(points);
                if (waypoints.length < 2) {
                    return { points: waypoints, distanceKm: 0 };
                }

                const coordinates = waypoints
                    .map((point) => `${encodeURIComponent(point.lng)},${encodeURIComponent(point.lat)}`)
                    .join(';');
                const url = `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`;

                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('route');
                    const payload = await response.json();
                    const route = payload?.routes?.[0];
                    const geometry = route?.geometry?.coordinates ?? [];
                    const routePointsForMap = geometry
                        .map((coord) => window.L.latLng(Number(coord[1]), Number(coord[0])))
                        .filter((coord) => Number.isFinite(coord.lat) && Number.isFinite(coord.lng));

                    return {
                        points: routePointsForMap.length > 1 ? routePointsForMap : waypoints,
                        distanceKm: route?.distance ? (Number(route.distance) / 1000) : distanceForPointsKm(waypoints),
                    };
                } catch (_error) {
                    return {
                        points: waypoints,
                        distanceKm: distanceForPointsKm(waypoints),
                    };
                }
            };

            const drawPreviewRoute = (points) => {
                previewLayerGroup.clearLayers();
                if (!points || points.length < 2) return;

                window.L.polyline(points, {
                    color: '#1d4ed8',
                    weight: 6,
                    opacity: 0.9,
                    lineCap: 'round',
                    interactive: false,
                }).addTo(previewLayerGroup);

                window.L.polyline(points, {
                    color: '#facc15',
                    weight: 2,
                    opacity: 0.95,
                    dashArray: '7 8',
                    lineCap: 'round',
                    interactive: false,
                }).addTo(previewLayerGroup);
            };

            updateFarePreview = async () => {
                const token = ++previewRequestToken;
                const passengerPickup = readSelectedPoint('pickup');
                const passengerDropoff = readSelectedPoint('dropoff');
                const customPickup = readMode('pickup') === 'custom';
                const customDropoff = readMode('dropoff') === 'custom';
                const hasCustom = customPickup || customDropoff;
                const hiddenFareInput = document.querySelector('input[name="extra_fee_amount"]');
                const hiddenDetourInput = document.querySelector('input[name="detour_distance_km"]');

                if ((customPickup && !passengerPickup) || (customDropoff && !passengerDropoff)) {
                    previewLayerGroup.clearLayers();
                    if (hiddenFareInput) hiddenFareInput.value = '';
                    if (hiddenDetourInput) hiddenDetourInput.value = '';
                    document.getElementById('farePreviewBadge').textContent = 'Waiting for pin';
                    document.getElementById('farePreviewRoute').textContent = '-';
                    document.getElementById('farePreviewSegment').textContent = '-';
                    document.getElementById('farePreviewPassenger').textContent = formatMoney(defaultFarePerPerson);
                    document.getElementById('farePreviewOthers').textContent = formatMoney(defaultFarePerPerson);
                    document.getElementById('farePreviewNote').textContent = 'Pin custom points to preview base split + extra fee.';
                    return;
                }

                const routePlan = fixedJoinRoute(passengerPickup, passengerDropoff, customPickup, customDropoff);
                const suggestedRoute = await fetchRoute(routePlan.points);

                if (token !== previewRequestToken) return;

                suggestedRoute.order = routePlan.order;
                drawPreviewRoute(suggestedRoute.points);

                const baseKm = baseRouteDistanceKm || distanceForPointsKm([pickupEndpoint, dropoffEndpoint]) || 1;
                const pickupDeviationKm = customPickup ? (distanceToRouteKm(passengerPickup) ?? endpointDistanceKm(passengerPickup) ?? 0) : 0;
                const dropoffDeviationKm = customDropoff ? (distanceToRouteKm(passengerDropoff) ?? endpointDistanceKm(passengerDropoff) ?? 0) : 0;
                const routeDeviationKm = hasCustom ? Math.max(0, pickupDeviationKm + dropoffDeviationKm) : 0;
                const detourCharge = hasCustom ? ((routeDeviationKm / baseKm) * tripFareTotal) : 0;
                const passengerFare = hasCustom
                    ? defaultFarePerPerson + detourCharge
                    : defaultFarePerPerson;
                const othersFare = defaultFarePerPerson;
                const passengerDelta = passengerFare - defaultFarePerPerson;

                if (hiddenFareInput) {
                    hiddenFareInput.value = hasCustom ? passengerDelta.toFixed(2) : '';
                }
                if (hiddenDetourInput) {
                    hiddenDetourInput.value = hasCustom ? routeDeviationKm.toFixed(2) : '';
                }
                document.getElementById('farePreviewBadge').textContent = hasCustom ? 'Extra fee added' : 'Default split';
                document.getElementById('farePreviewRoute').textContent = `${baseKm.toFixed(2)} km`;
                document.getElementById('farePreviewSegment').textContent = `${routeDeviationKm.toFixed(2)} km`;
                document.getElementById('farePreviewPassenger').textContent = formatMoney(passengerFare);
                document.getElementById('farePreviewOthers').textContent = formatMoney(othersFare);
                document.getElementById('farePreviewNote').textContent = hasCustom
                    ? `Normal split stays as base fare. Extra ${formatMoney(passengerDelta)} is based on ${routeDeviationKm.toFixed(2)} km custom deviation from the driver's original route. Driver can review before approve.`
                    : 'Default trip points selected, normal fare split is used.';
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
                const a = { x: 0, y: 0 };
                const b = pointToLocalKm(end, start);
                const lengthSquared = ((b.x - a.x) ** 2) + ((b.y - a.y) ** 2);
                if (lengthSquared === 0) return Math.sqrt((p.x ** 2) + (p.y ** 2));
                const t = Math.max(0, Math.min(1, (((p.x - a.x) * (b.x - a.x)) + ((p.y - a.y) * (b.y - a.y))) / lengthSquared));
                const projection = {
                    x: a.x + t * (b.x - a.x),
                    y: a.y + t * (b.y - a.y),
                };
                return Math.sqrt(((p.x - projection.x) ** 2) + ((p.y - projection.y) ** 2));
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
                return Math.min(
                    latLng.distanceTo(pickupEndpoint) / 1000,
                    latLng.distanceTo(dropoffEndpoint) / 1000
                );
            };

            const isAllowedPin = (latLng) => {
                const routeDistance = distanceToRouteKm(latLng);
                const pointDistance = endpointDistanceKm(latLng);
                return {
                    allowed: (routeDistance !== null && routeDistance <= allowedRouteRadiusKm) || pointDistance <= allowedEndpointRadiusKm,
                    routeDistance,
                    pointDistance,
                };
            };

            const addPin = (target, latLng) => {
                const pinLabel = target === 'pickup'
                    ? passengerLabel('pickup pin')
                    : passengerLabel('drop-off pin');
                const icon = window.L.divIcon({
                    className: '',
                    html: `<span class="passenger-pin-icon ${target === 'pickup' ? 'pickup' : 'dropoff'}"></span>`,
                    iconSize: [25, 25],
                    iconAnchor: [12, 25],
                    tooltipAnchor: [0, -23],
                });
                const marker = window.L.marker(latLng, {
                    draggable: false,
                    title: pinLabel,
                    icon,
                }).addTo(markerLayerGroup);
                marker.bindTooltip(pinLabel, {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -10],
                });
                return marker;
            };

            const applyPin = (target, latLng) => {
                setMode(target, 'custom');
                setCoordinate(target, latLng.lat, latLng.lng);
                setTextInput(
                    target,
                    `Pinned ${target === 'pickup' ? 'pickup' : 'drop-off'} near route (${latLng.lat.toFixed(5)}, ${latLng.lng.toFixed(5)})`
                );

                if (target === 'pickup') {
                    if (pickupMarker) pickupMarker.remove();
                    pickupMarker = addPin(target, latLng);
                } else {
                    if (dropoffMarker) dropoffMarker.remove();
                    dropoffMarker = addPin(target, latLng);
                }
                updateFarePreview();
            };

            updateAllowedRadiusForDistance(distanceForPointsKm([pickupEndpoint, dropoffEndpoint]));
            setActiveMapTarget('pickup');
            updateMapVisibility();
            updateFarePreview();

            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${encodeURIComponent(pickupLng)},${encodeURIComponent(pickupLat)};${encodeURIComponent(dropoffLng)},${encodeURIComponent(dropoffLat)}?overview=full&geometries=geojson&alternatives=false&steps=false`;
            fetch(osrmUrl)
                .then((response) => response.ok ? response.json() : null)
                .then((payload) => {
                    if (payload?.routes?.[0]?.distance) {
                        baseRouteDistanceKm = Number(payload.routes[0].distance) / 1000;
                        updateAllowedRadiusForDistance(baseRouteDistanceKm);
                    }
                    const geometry = payload?.routes?.[0]?.geometry?.coordinates ?? [];
                    const points = geometry
                        .map((coord) => [Number(coord[1]), Number(coord[0])])
                        .filter((coord) => Number.isFinite(coord[0]) && Number.isFinite(coord[1]));
                    if (points.length > 1) {
                        routeLinePoints = points;
                        drawRoute(routeLinePoints);
                    }
                    updateFarePreview();
                })
                .catch(() => {});

            map.on('click', (event) => {
                if (readMode('pickup') !== 'custom' && readMode('dropoff') !== 'custom') {
                    setStatus('Default trip points selected. Choose custom pickup or drop-off to pin a nearby stop.');
                    return;
                }

                const check = isAllowedPin(event.latlng);
                if (!check.allowed) {
                    const routeDistanceText = check.routeDistance === null ? 'unknown' : `${check.routeDistance.toFixed(1)} km`;
                    setStatus(`Blocked: selected point is outside the allowed route area. Nearest route distance: ${routeDistanceText}.`, 'blocked');
                    window.L.circleMarker(event.latlng, {
                        radius: 8,
                        color: '#b91c1c',
                        weight: 2,
                        fillColor: '#ef4444',
                        fillOpacity: 0.2,
                    }).addTo(markerLayerGroup).bindTooltip('Outside allowed area', {
                        direction: 'top',
                        offset: [0, -8],
                    }).openTooltip();
                    return;
                }

                applyPin(activeMapTarget, event.latlng);
                const distanceText = check.routeDistance === null ? 'near endpoint' : `${check.routeDistance.toFixed(2)} km from route`;
                setStatus(`${activeMapTarget === 'pickup' ? passengerLabel('pickup') : passengerLabel('drop-off')} pin saved, ${distanceText}.`, 'ok');
            });
        })();

        (() => {
            const mapEl = document.getElementById('sentRequestMap');
            if (!mapEl || typeof window.L === 'undefined') return;

            const toNumber = (value) => {
                const number = Number.parseFloat(String(value ?? ''));
                return Number.isFinite(number) ? number : null;
            };
            const pointFrom = (latKey, lngKey) => {
                const lat = toNumber(mapEl.dataset[latKey]);
                const lng = toNumber(mapEl.dataset[lngKey]);
                return lat === null || lng === null ? null : window.L.latLng(lat, lng);
            };
            const samePoint = (a, b) => Math.abs(a.lat - b.lat) < 0.00001 && Math.abs(a.lng - b.lng) < 0.00001;
            const uniqueWaypoints = (points) => points.reduce((items, point) => {
                if (!point) return items;
                if (!items.length || !samePoint(items[items.length - 1], point)) items.push(point);
                return items;
            }, []);
            const fetchRoute = async (points) => {
                const waypoints = uniqueWaypoints(points);
                if (waypoints.length < 2) return waypoints;

                const coordinates = waypoints
                    .map((point) => `${encodeURIComponent(point.lng)},${encodeURIComponent(point.lat)}`)
                    .join(';');
                const url = `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false`;

                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('route');
                    const payload = await response.json();
                    const geometry = payload?.routes?.[0]?.geometry?.coordinates ?? [];
                    const routePoints = geometry
                        .map((coord) => window.L.latLng(Number(coord[1]), Number(coord[0])))
                        .filter((coord) => Number.isFinite(coord.lat) && Number.isFinite(coord.lng));
                    return routePoints.length > 1 ? routePoints : waypoints;
                } catch (_error) {
                    return waypoints;
                }
            };

            const driverPickup = pointFrom('driverPickupLat', 'driverPickupLng');
            const driverDropoff = pointFrom('driverDropoffLat', 'driverDropoffLng');
            const passengerPickup = pointFrom('passengerPickupLat', 'passengerPickupLng');
            const passengerDropoff = pointFrom('passengerDropoffLat', 'passengerDropoffLng');
            if (!driverPickup || !driverDropoff) return;

            const usesDefaultPickup = mapEl.dataset.usesDefaultPickup === '1';
            const usesDefaultDropoff = mapEl.dataset.usesDefaultDropoff === '1';
            const passengerName = mapEl.dataset.passengerName || 'Passenger';
            const requestedWaypoints = uniqueWaypoints([
                driverPickup,
                usesDefaultPickup ? null : passengerPickup,
                usesDefaultDropoff ? null : passengerDropoff,
                driverDropoff,
            ]);

            const map = window.L.map(mapEl, {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: false,
            });
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            const addPin = (point, type, label) => {
                if (!point) return;
                const icon = window.L.divIcon({
                    className: '',
                    html: `<span class="passenger-pin-icon ${type}"></span>`,
                    iconSize: [25, 25],
                    iconAnchor: [12, 25],
                    tooltipAnchor: [0, -23],
                });
                window.L.marker(point, { icon, title: label })
                    .addTo(map)
                    .bindTooltip(label, { permanent: true, direction: 'top', offset: [0, -10] });
            };

            const addDriverPoint = (point, type, label) => {
                if (!point) return;
                window.L.circleMarker(point, {
                    radius: 6,
                    color: '#fff',
                    weight: 2,
                    fillColor: type === 'pickup' ? '#16a34a' : '#2563eb',
                    fillOpacity: 1,
                    interactive: true,
                })
                    .addTo(map)
                    .bindTooltip(label, { permanent: true, direction: 'top', offset: [0, -8] });
            };

            Promise.all([
                fetchRoute([driverPickup, driverDropoff]),
                fetchRoute(requestedWaypoints),
            ]).then(([originalRoute, requestedRoute]) => {
                window.L.polyline(originalRoute, {
                    color: '#64748b',
                    weight: 8,
                    opacity: 0.45,
                    lineCap: 'round',
                    interactive: false,
                }).addTo(map);

                window.L.polyline(requestedRoute, {
                    color: '#1d4ed8',
                    weight: 5,
                    opacity: 0.9,
                    lineCap: 'round',
                    interactive: false,
                }).addTo(map);

                if (!usesDefaultPickup) addPin(passengerPickup, 'pickup', `${passengerName} pickup`);
                if (!usesDefaultDropoff) addPin(passengerDropoff, 'dropoff', `${passengerName} drop-off`);
                addDriverPoint(driverPickup, 'pickup', 'Driver pickup');
                addDriverPoint(driverDropoff, 'dropoff', 'Driver drop-off');

                const bounds = window.L.latLngBounds([...originalRoute, ...requestedRoute]);
                if (bounds.isValid()) map.fitBounds(bounds, { padding: [24, 24] });
                setTimeout(() => map.invalidateSize(), 80);
            });
        })();

        // Also render the top-level route map (right column static preview)
        (() => {
            const mapEl = document.getElementById('requestRouteMap');
            if (!mapEl || typeof window.L === 'undefined') return;

            const toNumber = (value) => {
                const n = Number.parseFloat(String(value ?? ''));
                return Number.isFinite(n) ? n : null;
            };

            const pickupLat = toNumber(@json($trip->pickup_latitude));
            const pickupLng = toNumber(@json($trip->pickup_longitude));
            const dropoffLat = toNumber(@json($trip->destination_latitude));
            const dropoffLng = toNumber(@json($trip->destination_longitude));

            if (pickupLat === null || pickupLng === null || dropoffLat === null || dropoffLng === null) return;

            const map = window.L.map(mapEl, {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: false,
            });
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            const pickup = window.L.latLng(pickupLat, pickupLng);
            const dropoff = window.L.latLng(dropoffLat, dropoffLng);

            window.L.circleMarker(pickup, {
                radius: 7, color: '#fff', weight: 2, fillColor: '#16a34a', fillOpacity: 1,
            }).addTo(map).bindTooltip(@json($pickupName), { permanent: false, direction: 'top' });

            window.L.circleMarker(dropoff, {
                radius: 7, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1,
            }).addTo(map).bindTooltip(@json($destinationName), { permanent: false, direction: 'top' });

            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${encodeURIComponent(pickupLng)},${encodeURIComponent(pickupLat)};${encodeURIComponent(dropoffLng)},${encodeURIComponent(dropoffLat)}?overview=full&geometries=geojson&alternatives=false&steps=false`;
            fetch(osrmUrl)
                .then((r) => r.ok ? r.json() : null)
                .then((payload) => {
                    const geometry = payload?.routes?.[0]?.geometry?.coordinates ?? [];
                    const points = geometry
                        .map((c) => window.L.latLng(Number(c[1]), Number(c[0])))
                        .filter((c) => Number.isFinite(c.lat) && Number.isFinite(c.lng));
                    const line = points.length > 1 ? points : [pickup, dropoff];
                    window.L.polyline(line, { color: '#475569', weight: 4, opacity: 0.75, lineCap: 'round' }).addTo(map);
                    map.fitBounds(window.L.latLngBounds(line), { padding: [28, 28] });
                })
                .catch(() => {
                    window.L.polyline([pickup, dropoff], { color: '#475569', weight: 4, opacity: 0.75 }).addTo(map);
                    map.fitBounds(window.L.latLngBounds([pickup, dropoff]), { padding: [28, 28] });
                });
        })();
    </script>
@endsection
