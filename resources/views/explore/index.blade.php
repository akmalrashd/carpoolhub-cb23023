@extends('layouts.app')

@section('content')
    <style>
        .explore-page { display: grid; gap: 14px; }
        .explore-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }
        .explore-title { margin: 0; font-family: Poppins, sans-serif; font-size: 28px; color: #0f172a; line-height: 1.08; }
        .explore-subtitle { margin: 6px 0 0; color: #64748b; font-size: 14px; }

        .explore-search-cta {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            text-decoration: none;
            color: inherit;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }
        .explore-search-cta:hover {
            border-color: #cbd5e1;
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }
        .explore-search-left { display: inline-flex; align-items: center; gap: 10px; min-width: 0; }
        .explore-search-icon {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: 1px solid #fde68a;
            background: #fffbeb;
            color: #92400e;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex: 0 0 auto;
        }
        .explore-search-text { display: grid; gap: 1px; min-width: 0; }
        .explore-search-title { color: #0f172a; font-size: 16px; font-weight: 700; line-height: 1.2; }
        .explore-search-hint { color: #64748b; font-size: 12px; line-height: 1.2; }
        .explore-search-right { color: #92400e; font-size: 12px; font-weight: 700; white-space: nowrap; }

        .explore-custom-notice {
            position: sticky;
            top: 84px;
            z-index: 5;
            border: 1px solid #fde68a;
            border-radius: 12px;
            background: #fffbeb;
            color: #713f12;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 18px rgba(146, 64, 14, 0.08);
        }
        .explore-custom-notice-icon {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: #fef3c7;
            color: #92400e;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }
        .explore-custom-notice-text {
            display: grid;
            gap: 1px;
            min-width: 0;
        }
        .explore-custom-notice-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.2;
        }
        .explore-custom-notice-copy {
            color: #92400e;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
        }

        .explore-chip-row { display: flex; gap: 7px; flex-wrap: wrap; margin-top: 10px; }
        .explore-filter-chip {
            border: 1px solid #dbe2ea;
            border-radius: 999px;
            background: #fff;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            padding: 5px 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: border-color .16s ease, background-color .16s ease, color .16s ease;
        }
        .explore-filter-chip.active { border-color: #fde68a; background: #fffbeb; color: #92400e; }
        .explore-filter-chip:hover { border-color: #fde68a; background: #fffbeb; color: #92400e; }

        .explore-section-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
        .explore-section-title { margin: 0; color: #0f172a; font-size: 16px; font-weight: 700; }
        .explore-section-link { color: #92400e; font-size: 12px; font-weight: 700; text-decoration: none; }

        .explore-grid { display: grid; gap: 12px; }
        .explore-grid.domino-float .explore-item {
            animation: none;
        }
        .explore-grid.domino-float:hover .explore-item {
            animation-play-state: paused;
        }
        @keyframes exploreDominoFloat {
            0%, 8% {
                transform: translateY(0);
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            }
            12% {
                transform: translateY(-5px);
                box-shadow: 0 14px 24px rgba(15, 23, 42, 0.12);
            }
            18%, 100% {
                transform: translateY(0);
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            }
        }
        .explore-item {
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #fff;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow: hidden;
            box-shadow: 0 5px 14px rgba(15, 23, 42, 0.04);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .open-explore-card { cursor: pointer; }
        .explore-item:hover {
            transform: translateY(-2px);
            border-color: #cbd5e1;
            box-shadow: 0 14px 24px rgba(15, 23, 42, 0.08);
        }
        .explore-item.is-focus {
            border-color: #facc15;
            box-shadow: 0 0 0 3px rgba(250, 204, 21, .22), 0 12px 24px rgba(15, 23, 42, .1);
            animation: exploreFocusPulse 1.2s ease;
        }
        @keyframes exploreFocusPulse {
            0% { transform: scale(1); }
            35% { transform: scale(1.01); }
            100% { transform: scale(1); }
        }
        .explore-item-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: flex-start;
            gap: 10px;
            min-width: 0;
        }
        .explore-item-head > div { min-width: 0; width: 100%; flex: 1 1 auto; }
        .explore-route {
            margin: 0;
            font-size: 16px;
            line-height: 1.25;
            color: #0f172a;
            font-weight: 700;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .explore-price-stack {
            display: grid;
            gap: 2px;
            justify-items: end;
            white-space: nowrap;
        }
        .explore-price-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .explore-price {
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
            line-height: 1.1;
        }
        .explore-quick-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px 10px;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }
        .explore-quick-row span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-width: 0;
        }
        .explore-quick-row i {
            color: #64748b;
            font-size: 11px;
        }
        .explore-meta {
            margin: 3px 0 0;
            color: #64748b;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            max-width: 100%;
        }
        .explore-meta i {
            color: #92400e;
            font-size: 11px;
            flex: 0 0 auto;
        }
        .explore-meta span {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
        }
        .explore-meta-inline {
            margin: 0;
            color: #64748b;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            min-width: 0;
        }
        .explore-meta-inline-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-width: 0;
            white-space: nowrap;
            border: 0;
            background: transparent;
            border-radius: 0;
            padding: 0;
        }
        .explore-meta-inline-item i {
            color: #92400e;
            font-size: 11px;
            flex: 0 0 auto;
        }
        .explore-meta-inline-item span {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .explore-meta-line {
            margin: 2px 0 0;
            color: #64748b;
            font-size: 12px;
            display: grid;
            grid-template-columns: 12px auto minmax(0, 1fr);
            align-items: center;
            column-gap: 6px;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            min-width: 0;
        }
        .explore-meta-line i {
            color: #92400e;
            font-size: 11px;
            width: 12px;
            text-align: center;
        }
        .explore-meta-line .meta-label {
            font-weight: 700;
            white-space: nowrap;
        }
        .explore-meta-line .meta-value {
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
        }
        .explore-route-path {
            display: grid;
            gap: 6px;
            padding: 8px 10px;
            border: 1px solid #edf2f7;
            border-radius: 10px;
            background: #fbfdff;
        }
        .explore-route-point {
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr);
            align-items: center;
            gap: 8px;
            min-width: 0;
        }
        .explore-route-point + .explore-route-point {
            padding-top: 6px;
            border-top: 1px solid #eef2f7;
        }
        .explore-route-point-icon {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
            flex: 0 0 auto;
        }
        .explore-route-point.pickup .explore-route-point-icon {
            color: #15803d;
            background: #dcfce7;
            border: 1px solid #86efac;
        }
        .explore-route-point.destination .explore-route-point-icon {
            color: #b45309;
            background: #fffbeb;
            border: 1px solid #fde68a;
        }
        .explore-route-point-text {
            display: grid;
            gap: 2px;
            min-width: 0;
        }
        .explore-route-point-label {
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .explore-route-point-value {
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .explore-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            max-width: 100%;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            padding: 4px 9px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            flex: 0 0 auto;
            align-self: flex-start;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
        }
        .chip-available { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .chip-full { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
        .chip-request { color: #854d0e; border-color: #fde68a; background: #fefce8; }
        .chip-joined { color: #92400e; border-color: #fde68a; background: #fffbeb; }
        .chip-ai { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
        .chip-custom-ok { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .chip-custom-review { color: #854d0e; border-color: #fde68a; background: #fffbeb; }
        .chip-custom-blocked { color: #991b1b; border-color: #fecaca; background: #fff1f2; }
        .explore-ai-reasons { display: flex; gap: 6px; flex-wrap: wrap; }
        .explore-ai-reason {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #475569;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 700;
        }
        .explore-route-insights {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border: 0;
            border-radius: 0;
            background: transparent;
            padding: 0;
        }
        .explore-route-insights-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .explore-route-insights-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .explore-route-insights-title i { color: #92400e; font-size: 11px; }
        .explore-route-insights-meta {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
        }
        .explore-route-insight-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .explore-route-note {
            display: none;
            margin: 0;
            color: #64748b;
            font-size: 11px;
            line-height: 1.35;
        }

        .explore-detail-grid {
            display: grid;
            gap: 8px;
        }

        .explore-status-stack {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-start;
            min-width: 0;
        }

        .explore-detail {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            padding: 10px 11px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 4px;
            font-size: 13px;
            color: #334155;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
            min-width: 0;
        }
        .explore-detail span {
            min-width: 0;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .explore-detail strong {
            color: #0f172a;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: left;
            font-size: 16px;
        }
        .explore-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
            padding-top: 0;
        }
        .explore-actions form { margin: 0; }

        .explore-btn {
            border: 1px solid #dbe2ea;
            border-radius: 9px;
            background: #fff;
            color: #0f172a;
            padding: 9px 11px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
            min-width: 0;
        }
        .explore-btn:hover {
            transform: translateY(-1px);
            border-color: #cbd5e1;
            box-shadow: 0 8px 14px rgba(15, 23, 42, 0.08);
        }
        .explore-btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .explore-btn.success { background: #f0fdf4; border-color: #86efac; color: #166534; }
        .explore-btn.warning { background: #fefce8; border-color: #fde68a; color: #854d0e; }
        .explore-btn.disabled { background: #f1f5f9; border-color: #e2e8f0; color: #94a3b8; pointer-events: none; }

        .explore-suggested-list { display: grid; gap: 8px; margin: 0; }
        .explore-suggested-item {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
            padding: 10px 12px;
            text-decoration: none;
            transition: border-color .16s ease, background-color .16s ease, color .16s ease;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .explore-suggested-item:hover {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .empty-state { border: 1px dashed #dbe2ea; border-radius: 12px; background: #f8fafc; padding: 48px 24px; color: #64748b; font-size: 14px; text-align: center; }
        .empty-state-icon { font-size: 32px; color: #cbd5e1; margin-bottom: 12px; display: block; }
        .empty-state-title { font-size: 15px; font-weight: 700; color: #475569; margin: 0 0 4px; }
        .empty-state-copy { margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.5; }

        @media (min-width: 768px) {
            .explore-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .explore-detail-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .explore-suggested-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 640px) {
            .explore-item-head {
                gap: 16px;
            }
            .explore-item-head > div { width: auto; }
            .explore-status-stack {
                width: auto;
                flex: 0 0 auto;
                justify-content: flex-end;
            }
            .explore-chip {
                align-self: auto;
                justify-content: center;
                min-width: 73px;
            }
            .explore-status-stack .chip-ai {
                min-width: 112px;
            }
        }
        @media (min-width: 768px) and (max-width: 1120px) {
            .explore-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 767px) {
            .explore-page,
            .explore-card,
            .explore-grid,
            .explore-item {
                min-width: 0;
            }
            .explore-card {
                padding: 12px;
                border-radius: 16px;
            }
            .explore-custom-notice {
                top: 76px;
                align-items: flex-start;
            }
            .explore-section-head {
                align-items: flex-start;
            }
            .explore-section-title {
                line-height: 1.2;
            }
            .explore-subtitle {
                line-height: 1.45;
                overflow-wrap: anywhere;
            }
            .explore-meta-inline {
                gap: 6px 9px;
            }
            .explore-actions {
                justify-content: stretch;
                align-items: stretch;
            }
            .explore-actions .explore-btn,
            .explore-actions form {
                flex: 1 1 132px;
            }
            .explore-actions form .explore-btn {
                width: 100%;
            }
        }
        @media (max-width: 430px) {
            .explore-item-head {
                grid-template-columns: minmax(0, 1fr);
            }
            .explore-price-stack {
                justify-items: start;
                grid-template-columns: auto auto;
                align-items: baseline;
                gap: 6px;
            }
            .explore-section-head,
            .explore-search-cta {
                flex-direction: column;
                align-items: stretch;
            }
            .explore-section-link,
            .explore-search-right {
                align-self: flex-start;
            }
            .explore-detail {
                padding: 9px 10px;
            }
            .explore-detail strong {
                max-width: 48%;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .explore-grid.domino-float .explore-item {
                animation: none !important;
            }
        }
        html, body {
            overflow-x: clip;
        }
    </style>

    @php
        $aiRecommendationMap = $aiRecommendationMap ?? [];
        $recommendedTripIds = $recommendedTripIds ?? [];
        $activeTimeframe = $filters['timeframe'] ?? request('timeframe', '');
        $activeSeats = $filters['seats'] ?? request('seats', '');
        $focusTripId = (int) request('focus_trip', 0);
        $searchEditQuery = request()->except(['page', 'focus_trip']);
        $searchDestination = trim((string) request('destination', ''));
        $searchPickup = trim((string) request('pickup', ''));
        $searchDate = trim((string) request('date', ''));
        $searchSeats = trim((string) request('seats', ''));
        $searchSort = trim((string) request('sort', 'nearest'));
        $searchRadiusRaw = request('radius_km');
        $searchRadius = is_numeric($searchRadiusRaw) ? (float) $searchRadiusRaw : null;
        $searchPickupLat = is_numeric(request('pickup_lat')) ? (float) request('pickup_lat') : null;
        $searchPickupLng = is_numeric(request('pickup_lng')) ? (float) request('pickup_lng') : null;
        $searchDestinationLat = is_numeric(request('destination_lat')) ? (float) request('destination_lat') : null;
        $searchDestinationLng = is_numeric(request('destination_lng')) ? (float) request('destination_lng') : null;
        $hasPickupPin = $searchPickupLat !== null && $searchPickupLng !== null;
        $hasDestinationPin = $searchDestinationLat !== null && $searchDestinationLng !== null;
        $hasSearchSummary = $searchDestination !== '' || $searchPickup !== '' || $searchDate !== '' || $searchSeats !== '' || (request()->filled('center_lat') && request()->filled('center_lng')) || $hasPickupPin || $hasDestinationPin;

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
            if ($routeKm <= 3) {
                return ['route' => 0.40, 'endpoint' => 0.50];
            }
            if ($routeKm <= 10) {
                return ['route' => 0.70, 'endpoint' => 0.80];
            }
            if ($routeKm <= 25) {
                return ['route' => 1.00, 'endpoint' => 1.20];
            }

            return ['route' => 1.30, 'endpoint' => 1.50];
        };
        $searchPointFit = static function ($trip, ?float $pointLat, ?float $pointLng) use ($distanceKm, $distanceToSegmentKm, $allowedRadiiForKm): ?array {
            if ($pointLat === null || $pointLng === null) {
                return null;
            }

            $pickupLat = is_numeric($trip->pickup_latitude) ? (float) $trip->pickup_latitude : null;
            $pickupLng = is_numeric($trip->pickup_longitude) ? (float) $trip->pickup_longitude : null;
            $dropoffLat = is_numeric($trip->destination_latitude) ? (float) $trip->destination_latitude : null;
            $dropoffLng = is_numeric($trip->destination_longitude) ? (float) $trip->destination_longitude : null;

            if ($pickupLat === null || $pickupLng === null || $dropoffLat === null || $dropoffLng === null) {
                return ['state' => 'review', 'distance' => null, 'label' => 'Route map unavailable'];
            }

            $routeKm = max(0.01, $distanceKm($pickupLat, $pickupLng, $dropoffLat, $dropoffLng));
            $radii = $allowedRadiiForKm($routeKm);
            $routeDistance = $distanceToSegmentKm($pointLat, $pointLng, $pickupLat, $pickupLng, $dropoffLat, $dropoffLng);
            $endpointDistance = min(
                $distanceKm($pointLat, $pointLng, $pickupLat, $pickupLng),
                $distanceKm($pointLat, $pointLng, $dropoffLat, $dropoffLng)
            );
            $nearestDistance = min($routeDistance, $endpointDistance);
            $isAllowed = $routeDistance <= $radii['route'] || $endpointDistance <= $radii['endpoint'];

            return [
                'state' => $isAllowed ? 'ok' : 'blocked',
                'distance' => $nearestDistance,
                'route_radius' => $radii['route'],
                'endpoint_radius' => $radii['endpoint'],
                'label' => $isAllowed
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
        if ($searchSeats === '1') {
            $summaryParts[] = 'with 1 seat';
        } elseif ($searchSeats === '2plus') {
            $summaryParts[] = 'with 2+ seats';
        }
        if (request()->filled('center_lat') && request()->filled('center_lng') && $searchRadius !== null && $searchRadius > 0) {
            $summaryParts[] = 'within ' . rtrim(rtrim(number_format($searchRadius, 1, '.', ''), '0'), '.') . ' km pin radius';
        }
        if ($hasPickupPin) {
            $summaryParts[] = 'pickup pin set';
        }
        if ($hasDestinationPin) {
            $summaryParts[] = 'destination pin set';
        }
        if ($searchSort === 'latest') {
            $summaryParts[] = 'sorted by latest date';
        } else {
            $summaryParts[] = 'sorted by nearest date';
        }

        $searchSummaryText = $summaryParts
            ? ('Showing trips ' . implode(', ', $summaryParts) . '.')
            : 'Tap to search destination, date, and seats';
        $searchSummaryTitle = $hasSearchSummary
            ? 'Search Summary'
            : 'Where do you want to go?';
    @endphp

    <div class="explore-page">
        <section class="explore-card">
            <h1 class="explore-title">Explore Public Trips</h1>
            <p class="explore-subtitle">Browse upcoming trips or search destination to find your ride.</p>

            <a href="{{ route('explore.search', $searchEditQuery) }}" class="explore-search-cta" style="margin-top:10px;">
                <span class="explore-search-left">
                    <span class="explore-search-icon"><i class="fa-solid fa-location-dot"></i></span>
                    <span class="explore-search-text">
                        <span class="explore-search-title">{{ $searchSummaryTitle }}</span>
                        <span class="explore-search-hint">{{ $searchSummaryText }}</span>
                    </span>
                </span>
                <span class="explore-search-right">Cari <i class="fa-solid fa-arrow-right"></i></span>
            </a>

            <div class="explore-chip-row">
                <a href="{{ route('explore.index', array_merge(request()->except(['page', 'timeframe']), ['timeframe' => 'today'])) }}" class="explore-filter-chip {{ $activeTimeframe === 'today' ? 'active' : '' }}">Today</a>
                <a href="{{ route('explore.index', array_merge(request()->except(['page', 'timeframe']), ['timeframe' => 'tomorrow'])) }}" class="explore-filter-chip {{ $activeTimeframe === 'tomorrow' ? 'active' : '' }}">Tomorrow</a>
                <a href="{{ route('explore.index', array_merge(request()->except(['page', 'timeframe']), ['timeframe' => 'weekend'])) }}" class="explore-filter-chip {{ $activeTimeframe === 'weekend' ? 'active' : '' }}">Weekend</a>
                <a href="{{ route('explore.index', array_merge(request()->except(['page', 'seats']), ['seats' => '1'])) }}" class="explore-filter-chip {{ $activeSeats === '1' ? 'active' : '' }}">1 seat</a>
                <a href="{{ route('explore.index', array_merge(request()->except(['page', 'seats']), ['seats' => '2plus'])) }}" class="explore-filter-chip {{ $activeSeats === '2plus' ? 'active' : '' }}">2+ seats</a>
                <a href="{{ route('explore.index') }}" class="explore-filter-chip">Clear</a>
            </div>
        </section>

        <div class="explore-custom-notice">
            <span class="explore-custom-notice-icon">
                <i class="fa-solid fa-map-pin"></i>
            </span>
            <span class="explore-custom-notice-text">
                <span class="explore-custom-notice-title">Need a nearby pickup or drop-off?</span>
                <span class="explore-custom-notice-copy">Open a trip and request a custom pickup or destination before sending your join request.</span>
            </span>
        </div>

        <section class="explore-card">
            <div class="explore-section-head">
                <h2 class="explore-section-title">Upcoming Public Trips</h2>
                <a href="{{ route('explore.search', $searchEditQuery) }}" class="explore-section-link">Carian Lanjutan</a>
            </div>
            <p class="explore-subtitle" style="margin:0 0 10px;">AI matching ranks route fit, usual timing, seat availability, fare, and connection trust.</p>

            @if($trips->isEmpty())
                <div class="empty-state">
                    <i class="fa-solid fa-compass empty-state-icon"></i>
                    <p class="empty-state-title">Tiada trip tersedia</p>
                    <p class="empty-state-copy">Tiada trip awam buat masa ini. Cuba semak semula kemudian atau guna carian lanjutan.</p>
                </div>
            @else
                <div class="explore-grid domino-float" style="--domino-count: {{ max($trips->count(), 1) }};">
                    @foreach($trips as $trip)
                        @php
                            $routeName = $trip->savedRoute?->route_name ?: (($trip->pickup_name ?? 'Pickup') . ' -> ' . ($trip->destination_name ?? 'Destination'));
                            $pickupText = $trip->pickup_name ?? 'Pickup';
                            $destinationText = $trip->destination_name ?? 'Destination';
                            $pickupShortText = \Illuminate\Support\Str::limit($pickupText, 52, '...');
                            $destinationShortText = \Illuminate\Support\Str::limit($destinationText, 52, '...');
                            $tripTypeText = ((string) ($trip->trip_mode ?? 'one_way')) === 'two_way' ? 'Two Way' : 'One Way';
                            $visibilityText = ucfirst((string) ($trip->visibility ?? 'public')) . ' Trip';
                            $visibilityIcon = ($trip->visibility ?? 'public') === 'public' ? 'fa-solid fa-lock-open' : 'fa-solid fa-lock';
                            $takenSeats = (int) $trip->participants->where('is_driver', false)->count();
                            $availableSeats = $trip->seat_limit ? max(0, (int) $trip->seat_limit - $takenSeats) : null;
                            $myRequest = $trip->joinRequests->first();
                            $isJoined = $trip->participants->contains(fn ($participant) => (int) $participant->user_id === (int) auth()->id());
                            $aiRecommendation = $aiRecommendationMap[$trip->id] ?? null;
                            $isRecommended = in_array((int) $trip->id, $recommendedTripIds, true);
                            $stateText = $isJoined
                                ? 'Joined'
                                : (($myRequest && $myRequest->status === 'pending')
                                    ? 'Request Sent'
                                    : (($availableSeats !== null && $availableSeats <= 0) ? 'Full' : 'Available'));
                            $chipClass = $isJoined
                                ? 'chip-joined'
                                : (($myRequest && $myRequest->status === 'pending')
                                    ? 'chip-request'
                                    : (($availableSeats !== null && $availableSeats <= 0) ? 'chip-full' : 'chip-available'));
                            $pickupFit = $searchPointFit($trip, $searchPickupLat, $searchPickupLng);
                            $destinationFit = $searchPointFit($trip, $searchDestinationLat, $searchDestinationLng);
                            $hasAnySearchPin = $pickupFit !== null || $destinationFit !== null;
                            $customFitRows = [];
                            if ($pickupFit !== null) {
                                $customFitRows[] = [
                                    'text' => ($pickupFit['state'] === 'ok' ? 'Pickup can be custom' : 'Pickup needs review'),
                                    'class' => $pickupFit['state'] === 'ok' ? 'chip-custom-ok' : 'chip-custom-blocked',
                                    'note' => $pickupFit['label'],
                                ];
                            }
                            if ($destinationFit !== null) {
                                $customFitRows[] = [
                                    'text' => ($destinationFit['state'] === 'ok' ? 'Drop-off can be custom' : 'Drop-off needs review'),
                                    'class' => $destinationFit['state'] === 'ok' ? 'chip-custom-ok' : 'chip-custom-blocked',
                                    'note' => $destinationFit['label'],
                                ];
                            }
                            if (! $hasAnySearchPin) {
                                $routeHasCoordinates = is_numeric($trip->pickup_latitude)
                                    && is_numeric($trip->pickup_longitude)
                                    && is_numeric($trip->destination_latitude)
                                    && is_numeric($trip->destination_longitude);
                                $customFitRows[] = [
                                    'text' => $routeHasCoordinates ? 'Driver review required' : 'Custom route needs review',
                                    'class' => $routeHasCoordinates ? 'chip-custom-review' : 'chip-custom-blocked',
                                    'note' => $routeHasCoordinates
                                        ? 'You can request a nearby pickup or drop-off. The driver will approve or reject it after reviewing the route.'
                                        : 'Driver route has no complete map coordinates.',
                                ];
                            }
                            $customRadiusLabel = null;
                            if (is_numeric($trip->pickup_latitude) && is_numeric($trip->pickup_longitude) && is_numeric($trip->destination_latitude) && is_numeric($trip->destination_longitude)) {
                                $routeKmForRadius = max(0.01, $distanceKm((float) $trip->pickup_latitude, (float) $trip->pickup_longitude, (float) $trip->destination_latitude, (float) $trip->destination_longitude));
                                $radiusInfo = $allowedRadiiForKm($routeKmForRadius);
                                $customRadiusLabel = 'route ' . number_format($radiusInfo['route'] * 1000, 0) . 'm / endpoints ' . number_format($radiusInfo['endpoint'] * 1000, 0) . 'm';
                            }
                        @endphp
                        <article
                            id="exploreTripCard{{ $trip->id }}"
                            class="explore-item open-explore-card {{ $focusTripId === (int) $trip->id ? 'is-focus' : '' }}"
                            data-trip-url="{{ route('explore.show', $trip) }}"
                            style="--domino-index: {{ $loop->index }};"
                            tabindex="0"
                            role="link"
                            @if($focusTripId === (int) $trip->id) data-explore-focus-card="1" @endif
                        >
                            <div class="explore-item-head">
                                <div>
                                    <h2 class="explore-route">{{ $routeName }}</h2>
                                    <div class="explore-quick-row" style="margin-top:7px;">
                                        <span>
                                            <i class="fa-regular fa-calendar"></i>
                                            {{ $trip->trip_datetime?->format('D, d M Y') ?: '-' }}
                                        </span>
                                        <span>
                                            <i class="fa-regular fa-clock"></i>
                                            {{ $trip->trip_datetime?->format('H:i') ?: '-' }}
                                        </span>
                                        <span>
                                            <i class="fa-solid fa-chair"></i>
                                            {{ $availableSeats !== null ? ($availableSeats . ' / ' . (int) $trip->seat_limit . ' seats') : 'Open seats' }}
                                        </span>
                                    </div>
                                    <div class="explore-meta-inline" style="margin-top:7px;">
                                        <span class="explore-meta-inline-item">
                                            <i class="fa-solid fa-user"></i>
                                            <span>{{ $trip->driver?->name ?: '-' }}</span>
                                        </span>
                                        @if($aiRecommendation)
                                            <span class="explore-meta-inline-item">
                                                <i class="fa-solid fa-sparkles"></i>
                                                <span>{{ number_format((float) ($aiRecommendation['match_score'] ?? 0), 0) }}% match</span>
                                            </span>
                                        @endif
                                        <span class="explore-meta-inline-item">
                                            <i class="fa-solid fa-route"></i>
                                            <span>{{ $tripTypeText }}</span>
                                        </span>
                                        <span class="explore-meta-inline-item">
                                            <i class="{{ $visibilityIcon }}"></i>
                                            <span>{{ $visibilityText }}</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="explore-price-stack">
                                    <span class="explore-price-label">Fare</span>
                                    <span class="explore-price">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                </div>
                            </div>
                            <div class="explore-route-path">
                                <div class="explore-route-point pickup" title="{{ $pickupText }}">
                                    <span class="explore-route-point-icon">A</span>
                                    <span class="explore-route-point-text">
                                        <span class="explore-route-point-label">Pickup</span>
                                        <span class="explore-route-point-value">{{ $pickupShortText }}</span>
                                    </span>
                                </div>
                                <div class="explore-route-point destination" title="{{ $destinationText }}">
                                    <span class="explore-route-point-icon">B</span>
                                    <span class="explore-route-point-text">
                                        <span class="explore-route-point-label">Destination</span>
                                        <span class="explore-route-point-value">{{ $destinationShortText }}</span>
                                    </span>
                                </div>
                            </div>
                            @if($trip->public_note)
                                <div class="explore-ai-reasons">
                                    <span class="explore-ai-reason">Note: {{ \Illuminate\Support\Str::limit($trip->public_note, 56, '...') }}</span>
                                </div>
                            @endif
                            <div class="explore-actions">
                                <a href="{{ route('explore.show', $trip) }}" class="explore-btn">
                                    <i class="fa-regular fa-eye"></i>
                                    Lihat Butiran
                                </a>
                                @if($isJoined)
                                    <span class="explore-btn success disabled"><i class="fa-solid fa-check"></i> Telah Sertai</span>
                                @elseif($myRequest && $myRequest->status === 'pending')
                                    <span class="explore-btn warning disabled"><i class="fa-regular fa-clock"></i> Permohonan Dihantar</span>
                                @elseif($availableSeats !== null && $availableSeats <= 0)
                                    <span class="explore-btn disabled"><i class="fa-solid fa-ban"></i> Penuh</span>
                                @else
                                    <a href="{{ route('explore.show', $trip) }}#join-request" class="explore-btn primary">
                                        <i class="fa-solid fa-user-plus"></i>
                                        Mohon Sertai
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <div>
            {{ $trips->appends(request()->query())->links() }}
        </div>
    </div>

    <script>
        (() => {
            const target = document.querySelector('[data-explore-focus-card="1"]');
            if (!target) return;
            window.setTimeout(() => {
                const y = target.getBoundingClientRect().top + window.scrollY - 104;
                window.scrollTo({ top: Math.max(y, 0), behavior: 'smooth' });
            }, 220);
        })();

        (() => {
            const cards = document.querySelectorAll('.open-explore-card[data-trip-url]');
            if (!cards.length) return;

            const isInteractiveTarget = (target) => {
                return !!target.closest('a, button, input, textarea, select, label, form');
            };

            cards.forEach((card) => {
                const url = card.getAttribute('data-trip-url');
                if (!url) return;

                card.addEventListener('click', (event) => {
                    if (isInteractiveTarget(event.target)) return;
                    window.location.href = url;
                });

                card.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') return;
                    if (isInteractiveTarget(event.target)) return;
                    event.preventDefault();
                    window.location.href = url;
                });
            });
        })();
    </script>
@endsection

