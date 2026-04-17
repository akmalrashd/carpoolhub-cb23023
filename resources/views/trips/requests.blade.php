@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <style>
        .trip-requests-page { display: grid; gap: 12px; }
        .trip-requests-card { background: #fff; border: 1px solid #dbe2ea; border-radius: 16px; padding: 14px; }
        .trip-requests-title { margin: 0; font-family: Poppins, sans-serif; font-size: 28px; color: #0f172a; line-height: 1.1; }
        .trip-requests-subtitle { margin: 6px 0 0; color: #64748b; font-size: 14px; }
        .trip-route-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .trip-route-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #475569;
            font-weight: 600;
        }
        .trip-route-item i {
            font-size: 11px;
            color: #64748b;
        }
        .trip-sub-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .trip-sub-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #475569;
            font-weight: 600;
        }
        .trip-sub-meta-item i {
            font-size: 11px;
            color: #64748b;
        }
        .trip-sub-meta-item.public-open { color: #166534; }
        .trip-sub-meta-item.public-open i { color: #166534; }
        .trip-sub-meta-item.public-closed { color: #b91c1c; }
        .trip-sub-meta-item.public-closed i { color: #b91c1c; }
        .trip-requests-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; flex-wrap: wrap; }

        .btn { border: 1px solid #dbe2ea; border-radius: 9px; background: #fff; color: #0f172a; padding: 8px 10px; font-size: 12px; font-weight: 700; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .btn.success { background: #f0fdf4; border-color: #86efac; color: #166534; }
        .btn.danger { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .btn.warning { background: #fefce8; border-color: #fde68a; color: #854d0e; }

        .request-list { display: grid; gap: 8px; }
        .request-item { border: 1px solid #dbe2ea; border-radius: 12px; background: #fff; padding: 10px; display: grid; gap: 8px; }
        .request-head { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
        .request-user { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .request-avatar {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 auto;
        }
        .request-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .request-user-meta { min-width: 0; }
        .request-name { margin: 0; color: #0f172a; font-size: 16px; font-weight: 700; }
        .request-meta { color: #64748b; font-size: 12px; margin-top: 2px; }
        .request-chip { display: inline-flex; align-items: center; border-radius: 999px; border: 1px solid #dbe2ea; padding: 4px 9px; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .chip-pending { color: #854d0e; border-color: #fde68a; background: #fefce8; }
        .chip-approved { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .chip-rejected, .chip-cancelled { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }

        .request-note { border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; color: #334155; font-size: 13px; padding: 8px 10px; }

        .request-reliability { border: 1px solid #dbe2ea; border-radius: 10px; background: #f8fafc; padding: 8px 10px; display: grid; gap: 5px; }
        .request-reliability-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .request-reliability-title-group { display: inline-flex; align-items: center; gap: 6px; }
        .request-reliability-title { color: #475569; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .rating-info-btn {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            cursor: pointer;
        }
        .rating-info-btn:hover { background: #dbeafe; border-color: #93c5fd; }
        .request-reliability-score { color: #0f172a; font-size: 15px; font-weight: 700; line-height: 1; }
        .request-reliability-score .value { font-size: 18px; }
        .request-reliability-label { display: inline-flex; align-items: center; border-radius: 999px; border: 1px solid #dbe2ea; padding: 3px 8px; font-size: 11px; font-weight: 700; white-space: nowrap; }
        .risk-excellent { color: #166534; background: #f0fdf4; border-color: #86efac; }
        .risk-good { color: #1d4ed8; background: #eff6ff; border-color: #bfdbfe; }
        .risk-moderate { color: #854d0e; background: #fefce8; border-color: #fde68a; }
        .risk-risky { color: #b45309; background: #fff7ed; border-color: #fdba74; }
        .risk-high-risk { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
        .request-reliability-meta { color: #64748b; font-size: 12px; line-height: 1.35; }
        .request-reliability-items {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 10px;
        }
        .request-reliability-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #475569;
            font-weight: 600;
        }
        .request-reliability-item i {
            font-size: 11px;
            color: #64748b;
        }

        .request-actions { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
        .empty-state { border: 1px dashed #dbe2ea; border-radius: 12px; background: #f8fafc; padding: 14px; color: #64748b; font-size: 14px; text-align: center; }

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
            border-color: #bfdbfe;
            background: #eff6ff;
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
        .trip-point-card.destination .trip-point-label { color: #1e3a8a; }
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
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1e3a8a;
        }
        .trip-contact-link.is-disabled {
            pointer-events: none;
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
        }
        .request-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .52);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 2600;
        }
        .request-modal.show { display: flex; }
        .request-modal-card {
            width: min(560px, 100%);
            max-height: min(84vh, 760px);
            overflow: auto;
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 20px;
            padding: 18px;
            display: grid;
            gap: 12px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.2);
            position: relative;
        }
        .modal-close-x {
            position: absolute;
            right: 14px;
            top: 12px;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #475569;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .request-modal-title {
            margin: 0;
            padding-right: 34px;
            color: #0f172a;
            font-size: 36px;
            line-height: 1.05;
            font-weight: 700;
            letter-spacing: -0.02em;
            font-family: Poppins, sans-serif;
        }
        .request-modal-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .request-modal-line {
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            border-radius: 12px;
            padding: 10px 12px;
            display: grid;
            gap: 3px;
        }
        .request-modal-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 700;
        }
        .request-modal-value {
            font-size: 16px;
            color: #0f172a;
            font-weight: 700;
            line-height: 1.25;
            word-break: break-word;
            white-space: pre-wrap;
        }
        .reject-reason-input {
            width: 100%;
            min-height: 120px;
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #f8fafc;
            padding: 14px 16px;
            color: #0f172a;
            font-size: 15px;
            line-height: 1.45;
            resize: vertical;
            font-family: inherit;
        }
        .reject-reason-input::placeholder { color: #94a3b8; }
        .reject-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .approve-reason-input {
            width: 100%;
            min-height: 120px;
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #f8fafc;
            padding: 14px 16px;
            color: #0f172a;
            font-size: 15px;
            line-height: 1.45;
            resize: vertical;
            font-family: inherit;
        }
        .approve-reason-input::placeholder { color: #94a3b8; }
        .approve-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .rating-info-formula {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px 12px;
            color: #334155;
            font-size: 13px;
            line-height: 1.45;
        }
        .rating-info-formula strong { color: #0f172a; }
        .rating-info-groups {
            display: grid;
            gap: 10px;
        }
        .rating-info-group {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            display: grid;
            gap: 7px;
        }
        .rating-info-group-title {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .rating-info-group-title i { color: #1e3a8a; font-size: 11px; }
        .rating-info-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 6px;
        }
        .rating-info-list li {
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            background: #f8fafc;
            padding: 7px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }
        .rating-info-range {
            color: #334155;
            font-size: 12px;
            font-weight: 600;
        }
        .rating-info-penalty {
            color: #b45309;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
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
            .request-modal {
                align-items: center;
                justify-content: center;
                padding: calc(env(safe-area-inset-top, 0px) + 88px) 12px calc(env(safe-area-inset-bottom, 0px) + 98px);
            }
            .request-modal-card {
                width: 100%;
                max-height: 100%;
                border-radius: 16px;
                padding: 16px;
            }
            .request-modal-title {
                font-size: 26px;
            }
        }
        @media (max-width: 420px) {
            .trip-details-pairs { grid-template-columns: repeat(1, minmax(0, 1fr)); }
            .trip-point-cards { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        }
    </style>

    @php
        $routeName = $trip->savedRoute?->route_name ?: (($trip->pickup_name ?? 'Pickup') . ' -> ' . ($trip->destination_name ?? 'Destination'));
        $pickupName = $trip->pickup_name ?? 'Pickup';
        $destinationName = $trip->destination_name ?? 'Destination';
        $takenSeats = (int) $trip->participants->where('is_driver', false)->count();
        $availableSeats = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : null;
        $hasReturn = (bool) $trip->returnTrip;
        $directionText = $pickupName . ' -> ' . $destinationName;
        $returnDirectionText = $destinationName . ' -> ' . $pickupName;
        $modeText = $hasReturn ? 'Two Way' : 'One Way';
        $combinedFare = (float) $trip->fare_total + (float) ($trip->returnTrip?->fare_total ?? 0);
        $myFare = (float) ($trip->payments->first()?->amount_due ?? 0)
            + (float) ($trip->returnTrip?->payments?->first()?->amount_due ?? 0);
        $showTotalFare = auth()->user()->role === 'admin';
        $displayFare = $showTotalFare ? $combinedFare : $myFare;
        $pairedTripId = $trip->returnTrip?->id;
        $participantPayload = $trip->participants
            ->map(fn ($participant) => [
                'user_id' => $participant->user_id,
                'name' => $participant->user?->name ?? '-',
                'email' => $participant->user?->email ?? '',
                'photo_url' => $participant->user?->profile_photo
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($participant->user->profile_photo)
                    : null,
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
        $reliabilityScoreConfig = (array) config('passenger_reliability.score', []);
        $amountPenaltyConfig = (array) config('passenger_reliability.amount_penalties', []);
        $overduePenaltyConfig = (array) config('passenger_reliability.overdue_penalties', []);
        $casePenaltyConfig = (array) config('passenger_reliability.case_penalties', []);
        $riskLabelConfig = (array) config('passenger_reliability.risk_labels', []);
    @endphp

    <div class="trip-requests-page">
        <section class="trip-requests-card">
            <div class="trip-requests-top">
                <div>
                    <h1 class="trip-requests-title">Join Requests</h1>
                    <p class="trip-requests-subtitle">
                        <span class="trip-route-meta">
                            <span class="trip-route-item">
                                <i class="fas fa-route"></i>
                                {{ $routeName }}
                            </span>
                            <span class="trip-route-item">
                                <i class="fas fa-hashtag"></i>
                                Trip #{{ $trip->id }}
                            </span>
                        </span>
                    </p>
                    @if($trip->visibility === 'public')
                        <p class="trip-requests-subtitle">
                            <span class="trip-sub-meta">
                                <span id="tripPublicJoinMeta" class="trip-sub-meta-item {{ $trip->is_open_for_request ? 'public-open' : 'public-closed' }}">
                                    <i id="tripPublicJoinIcon" class="fas {{ $trip->is_open_for_request ? 'fa-lock-open' : 'fa-lock' }}"></i>
                                    <span id="tripPublicJoinText">Public Join: {{ $trip->is_open_for_request ? 'Open' : 'Closed' }}</span>
                                </span>
                            </span>
                        </p>
                    @endif
                    <p class="trip-requests-subtitle">
                        <span class="trip-sub-meta">
                            <span class="trip-sub-meta-item">
                                <i class="fas fa-chair"></i>
                                <span>Seats: <span id="tripSeatText">{{ $availableSeats !== null ? ($availableSeats . ' available / ' . (int) $trip->seat_limit) : 'Open' }}</span></span>
                            </span>
                            <span class="trip-sub-meta-item">
                                <i class="fas fa-circle-check"></i>
                                <span>Status: <span id="tripStatusText">{{ ucfirst($trip->status) }}</span></span>
                            </span>
                        </span>
                    </p>
                </div>
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <button
                        type="button"
                        class="btn open-trip-modal-btn"
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
                    ><i class="fa-regular fa-eye"></i><span>Trip Details</span></button>
                    @if($trip->visibility === 'public')
                        <form method="POST" action="{{ route('trips.requests.toggle-open', $trip) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_open_for_request" value="{{ $trip->is_open_for_request ? '0' : '1' }}">
                            <button type="submit" class="btn {{ $trip->is_open_for_request ? 'danger' : 'success' }}">
                                <i class="fas {{ $trip->is_open_for_request ? 'fa-lock' : 'fa-lock-open' }}"></i>
                                {{ $trip->is_open_for_request ? 'Close Public Joining' : 'Open Public Joining' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        <section class="trip-requests-card">
            <div id="tripRequestsListContainer">
                @include('trips.partials.requests-list', ['requests' => $requests, 'reliabilityMap' => $reliabilityMap, 'aiRiskMap' => $aiRiskMap, 'trip' => $trip])
            </div>
        </section>

        <div id="tripRequestsPaginationContainer">{{ $requests->links() }}</div>
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

    <div class="request-modal" id="rejectModal" aria-hidden="true">
        <div class="request-modal-card">
            <button type="button" class="modal-close-x" id="rejectModalCloseTop" aria-label="Close">&times;</button>
            <h3 class="request-modal-title">Reject Join Request</h3>
            <div class="request-modal-grid">
                <div class="request-modal-line">
                    <span class="request-modal-label">Passenger</span>
                    <span class="request-modal-value" id="rejectModalPassenger">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label">Trip</span>
                    <span class="request-modal-value" id="rejectModalTrip">-</span>
                </div>
                <form id="rejectModalForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="reject">
                    <textarea
                        class="reject-reason-input"
                        id="rejectModalReason"
                        name="response_note"
                        placeholder="Write rejection reason..."
                        required
                    ></textarea>
                </form>
            </div>
            <div class="reject-modal-actions">
                <button type="button" class="btn" id="rejectModalCancel">Cancel</button>
                <button type="submit" class="btn danger" form="rejectModalForm"><i class="fas fa-solid fa-xmark"></i>Reject</button>
            </div>
        </div>
    </div>

    <div class="request-modal" id="approveModal" aria-hidden="true">
        <div class="request-modal-card">
            <button type="button" class="modal-close-x" id="approveModalCloseTop" aria-label="Close">&times;</button>
            <h3 class="request-modal-title">Approve Join Request</h3>
            <div class="request-modal-grid">
                <div class="request-modal-line">
                    <span class="request-modal-label">Passenger</span>
                    <span class="request-modal-value" id="approveModalPassenger">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label">Trip</span>
                    <span class="request-modal-value" id="approveModalTrip">-</span>
                </div>
                <form id="approveModalForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="approve">
                    <textarea
                        class="approve-reason-input"
                        id="approveModalReason"
                        name="response_note"
                        placeholder="Write approval note (optional)..."
                    ></textarea>
                </form>
            </div>
            <div class="approve-modal-actions">
                <button type="button" class="btn" id="approveModalCancel">Cancel</button>
                <button type="submit" class="btn success" form="approveModalForm"><i class="fas fa-solid fa-check"></i>Approve</button>
            </div>
        </div>
    </div>

    <div class="request-modal" id="ratingInfoModal" aria-hidden="true">
        <div class="request-modal-card">
            <button type="button" class="modal-close-x" id="ratingInfoCloseTop" aria-label="Close">&times;</button>
            <h3 class="request-modal-title">AI Risk And Reliability Details</h3>
            <div class="request-modal-grid">
                <div class="rating-info-formula">
                    <strong>AI risk score:</strong>
                    Starts from a base score and adjusts using payment reliability, unpaid debt, cancellations, and attendance history.
                    <br>
                    <strong>Formula:</strong>
                    Score = Base ({{ number_format((float) ($reliabilityScoreConfig['base'] ?? 5.0), 1) }})
                    - Amount Penalty - Overdue Penalty - Case Penalty,
                    then clamped to {{ number_format((float) ($reliabilityScoreConfig['min'] ?? 1.0), 1) }} - {{ number_format((float) ($reliabilityScoreConfig['max'] ?? 5.0), 1) }}.
                    <br>
                    <strong>Outstanding due</strong> includes <code>unpaid</code> + <code>pending_confirmation</code> payments from non-draft, non-scheduled trips.
                </div>

                <div class="rating-info-groups">
                    <div class="rating-info-group">
                        <div class="rating-info-group-title"><i class="fas fa-wallet"></i>Amount Penalty</div>
                        <ul class="rating-info-list">
                            @foreach($amountPenaltyConfig as $range)
                                @php
                                    $min = $range['min'] ?? null;
                                    $max = $range['max'] ?? null;
                                    $rangeText = $max === null
                                        ? ('RM ' . number_format((float) $min, 2) . '+')
                                        : ('RM ' . number_format((float) $min, 2) . ' - RM ' . number_format((float) $max, 2));
                                @endphp
                                <li>
                                    <span class="rating-info-range">{{ $rangeText }}</span>
                                    <span class="rating-info-penalty">-{{ number_format((float) ($range['penalty'] ?? 0), 1) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rating-info-group">
                        <div class="rating-info-group-title"><i class="fas fa-clock"></i>Overdue Penalty</div>
                        <ul class="rating-info-list">
                            @foreach($overduePenaltyConfig as $range)
                                @php
                                    $min = (int) ($range['min'] ?? 0);
                                    $max = $range['max'] ?? null;
                                    $rangeText = $max === null
                                        ? ($min . '+ day(s)')
                                        : ($min . ' - ' . (int) $max . ' day(s)');
                                @endphp
                                <li>
                                    <span class="rating-info-range">{{ $rangeText }}</span>
                                    <span class="rating-info-penalty">-{{ number_format((float) ($range['penalty'] ?? 0), 1) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rating-info-group">
                        <div class="rating-info-group-title"><i class="fas fa-file-invoice-dollar"></i>Case Count Penalty</div>
                        <ul class="rating-info-list">
                            @foreach($casePenaltyConfig as $range)
                                @php
                                    $min = (int) ($range['min'] ?? 0);
                                    $max = $range['max'] ?? null;
                                    $rangeText = $max === null
                                        ? ($min . '+ case(s)')
                                        : ($min . ' - ' . (int) $max . ' case(s)');
                                @endphp
                                <li>
                                    <span class="rating-info-range">{{ $rangeText }}</span>
                                    <span class="rating-info-penalty">-{{ number_format((float) ($range['penalty'] ?? 0), 1) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rating-info-group">
                        <div class="rating-info-group-title"><i class="fas fa-shield-heart"></i>Risk Label</div>
                        <ul class="rating-info-list">
                            @foreach($riskLabelConfig as $range)
                                @php
                                    $min = (float) ($range['min'] ?? 0);
                                    $max = (float) ($range['max'] ?? $min);
                                @endphp
                                <li>
                                    <span class="rating-info-range">{{ number_format($min, 1) }} - {{ number_format($max, 1) }}</span>
                                    <span class="rating-info-penalty" style="color:#1e3a8a;">{{ $range['label'] ?? '-' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="approve-modal-actions">
                <button type="button" class="btn" id="ratingInfoCloseBtn">Close</button>
            </div>
        </div>
    </div>


    <script>
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

        (() => {
            const rejectModal = document.getElementById('rejectModal');
            const rejectCancelBtn = document.getElementById('rejectModalCancel');
            const rejectCloseTopBtn = document.getElementById('rejectModalCloseTop');
            const rejectForm = document.getElementById('rejectModalForm');
            const rejectPassengerEl = document.getElementById('rejectModalPassenger');
            const rejectTripEl = document.getElementById('rejectModalTrip');
            const rejectReasonEl = document.getElementById('rejectModalReason');
            if (!rejectModal || !rejectCancelBtn || !rejectForm) return;

            const openRejectModal = (action, passenger, trip) => {
                rejectForm.setAttribute('action', action || '');
                if (rejectPassengerEl) rejectPassengerEl.textContent = passenger || '-';
                if (rejectTripEl) rejectTripEl.textContent = trip || '-';
                rejectModal.classList.add('show');
                rejectModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => rejectReasonEl?.focus(), 30);
            };

            const closeRejectModal = () => {
                rejectModal.classList.remove('show');
                rejectModal.setAttribute('aria-hidden', 'true');
                rejectForm.setAttribute('action', '');
                if (rejectReasonEl) rejectReasonEl.value = '';
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.open-reject-btn');
                if (!button) return;
                openRejectModal(button.dataset.action, button.dataset.passenger, button.dataset.trip);
            });

            rejectCancelBtn.addEventListener('click', closeRejectModal);
            if (rejectCloseTopBtn) rejectCloseTopBtn.addEventListener('click', closeRejectModal);
            rejectModal.addEventListener('click', (event) => {
                if (event.target === rejectModal) closeRejectModal();
            });
        })();

        (() => {
            const approveModal = document.getElementById('approveModal');
            const approveCancelBtn = document.getElementById('approveModalCancel');
            const approveCloseTopBtn = document.getElementById('approveModalCloseTop');
            const approveForm = document.getElementById('approveModalForm');
            const approvePassengerEl = document.getElementById('approveModalPassenger');
            const approveTripEl = document.getElementById('approveModalTrip');
            const approveReasonEl = document.getElementById('approveModalReason');
            if (!approveModal || !approveCancelBtn || !approveForm) return;

            const openApproveModal = (action, passenger, trip) => {
                approveForm.setAttribute('action', action || '');
                if (approvePassengerEl) approvePassengerEl.textContent = passenger || '-';
                if (approveTripEl) approveTripEl.textContent = trip || '-';
                approveModal.classList.add('show');
                approveModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => approveReasonEl?.focus(), 30);
            };

            const closeApproveModal = () => {
                approveModal.classList.remove('show');
                approveModal.setAttribute('aria-hidden', 'true');
                approveForm.setAttribute('action', '');
                if (approveReasonEl) approveReasonEl.value = '';
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.open-approve-btn');
                if (!button) return;
                openApproveModal(button.dataset.action, button.dataset.passenger, button.dataset.trip);
            });

            approveCancelBtn.addEventListener('click', closeApproveModal);
            if (approveCloseTopBtn) approveCloseTopBtn.addEventListener('click', closeApproveModal);
            approveModal.addEventListener('click', (event) => {
                if (event.target === approveModal) closeApproveModal();
            });
        })();

        (() => {
            const infoModal = document.getElementById('ratingInfoModal');
            const infoCloseBtn = document.getElementById('ratingInfoCloseBtn');
            const infoCloseTopBtn = document.getElementById('ratingInfoCloseTop');
            if (!infoModal || !infoCloseBtn) return;

            const openInfoModal = () => {
                infoModal.classList.add('show');
                infoModal.setAttribute('aria-hidden', 'false');
            };

            const closeInfoModal = () => {
                infoModal.classList.remove('show');
                infoModal.setAttribute('aria-hidden', 'true');
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.open-rating-info-btn');
                if (!button) return;
                openInfoModal();
            });

            infoCloseBtn.addEventListener('click', closeInfoModal);
            if (infoCloseTopBtn) infoCloseTopBtn.addEventListener('click', closeInfoModal);
            infoModal.addEventListener('click', (event) => {
                if (event.target === infoModal) closeInfoModal();
            });
        })();

        (() => {
            const listContainer = document.getElementById('tripRequestsListContainer');
            const paginationContainer = document.getElementById('tripRequestsPaginationContainer');
            const seatTextEl = document.getElementById('tripSeatText');
            const statusTextEl = document.getElementById('tripStatusText');
            const publicJoinTextEl = document.getElementById('tripPublicJoinText');
            const publicJoinMetaEl = document.getElementById('tripPublicJoinMeta');
            const publicJoinIconEl = document.getElementById('tripPublicJoinIcon');
            if (!listContainer || !paginationContainer) return;

            const endpoint = @json(route('refresh.trips.requests', $trip));
            const pollMs = 5000;
            let inFlight = false;

            const syncTripMeta = (tripPayload) => {
                if (!tripPayload || typeof tripPayload !== 'object') return;
                if (seatTextEl && typeof tripPayload.available_seats_text === 'string') {
                    seatTextEl.textContent = tripPayload.available_seats_text;
                }
                if (statusTextEl && typeof tripPayload.status_text === 'string') {
                    statusTextEl.textContent = tripPayload.status_text;
                }
                if (publicJoinTextEl && publicJoinMetaEl && publicJoinIconEl && tripPayload.visibility === 'public') {
                    const open = !!tripPayload.is_open_for_request;
                    publicJoinTextEl.textContent = `Public Join: ${open ? 'Open' : 'Closed'}`;
                    publicJoinIconEl.className = `fas ${open ? 'fa-lock-open' : 'fa-lock'}`;
                    publicJoinMetaEl.classList.toggle('public-open', open);
                    publicJoinMetaEl.classList.toggle('public-closed', !open);
                }
            };

            const poll = async () => {
                if (inFlight || document.visibilityState !== 'visible') return;
                inFlight = true;
                try {
                    const response = await fetch(endpoint + '?page=' + encodeURIComponent(@json((int) request('page', 1))), {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) return;
                    const payload = await response.json();
                    if (typeof payload?.requests_html === 'string') {
                        listContainer.innerHTML = payload.requests_html;
                    }
                    if (typeof payload?.pagination_html === 'string') {
                        paginationContainer.innerHTML = payload.pagination_html;
                    }
                    syncTripMeta(payload?.trip);
                } catch (_error) {
                } finally {
                    inFlight = false;
                }
            };

            window.setInterval(poll, pollMs);
        })();
    </script>
@endsection




