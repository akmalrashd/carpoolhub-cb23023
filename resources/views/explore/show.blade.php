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
        .trip-show-page { display: grid; gap: 12px; }
        .trip-show-card { background: #fff; border: 1px solid #dbe2ea; border-radius: 16px; padding: 14px; }
        .trip-show-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .trip-show-title { margin: 0; color: #0f172a; font-family: Poppins, sans-serif; font-size: 28px; line-height: 1.1; }
        .trip-show-subtitle { margin: 6px 0 0; color: #64748b; font-size: 14px; }

        .trip-modal-grid { display: grid; gap: 7px; margin-top: 10px; }
        .trip-modal-line { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; padding: 8px 10px; display: grid; gap: 2px; }
        .trip-modal-label { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .03em; font-weight: 700; }
        .trip-modal-value { color: #0f172a; font-size: 13px; font-weight: 600; word-break: break-word; }

        .trip-point-cards { display: grid; gap: 7px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .trip-point-card { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; padding: 8px 10px; display: grid; gap: 3px; }
        .trip-point-card.pickup { border-color: #bbf7d0; background: #f0fdf4; }
        .trip-point-card.destination { border-color: #bfdbfe; background: #eff6ff; }
        .trip-point-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; display: inline-flex; align-items: center; gap: 5px; }
        .trip-point-card.pickup .trip-point-label { color: #166534; }
        .trip-point-card.destination .trip-point-label { color: #1e3a8a; }
        .trip-point-value { color: #0f172a; font-size: 13px; font-weight: 700; line-height: 1.3; }

        .trip-driver-content { display: flex; align-items: center; gap: 10px; }
        .trip-driver-avatar { width: 34px; height: 34px; border-radius: 999px; border: 1px solid #dbe2ea; background: #f8fafc; color: #0f172a; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .trip-driver-meta { display: grid; gap: 1px; min-width: 0; }
        .trip-driver-name { color: #0f172a; font-size: 14px; font-weight: 700; line-height: 1.2; }
        .trip-driver-email { color: #64748b; font-size: 12px; line-height: 1.2; word-break: break-word; }

        .trip-passenger-header { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .trip-passenger-count { border: 1px solid #dbe2ea; background: #fff; color: #334155; border-radius: 999px; padding: 3px 9px; font-size: 11px; font-weight: 700; }
        .trip-passenger-list { border: 1px solid #e2e8f0; background: #fff; border-radius: 10px; padding: 8px; display: grid; gap: 7px; }
        .trip-passenger-item { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 9px; padding: 7px 8px; display: flex; align-items: center; gap: 8px; }
        .trip-passenger-avatar { width: 30px; height: 30px; border-radius: 999px; border: 1px solid #dbe2ea; background: #fff; color: #0f172a; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .trip-passenger-meta { min-width: 0; display: grid; gap: 1px; flex: 1 1 auto; }
        .trip-passenger-name { color: #0f172a; font-size: 13px; font-weight: 700; line-height: 1.2; }
        .trip-passenger-email { color: #64748b; font-size: 11px; line-height: 1.2; word-break: break-word; }

        .trip-rollup-grid { display: grid; gap: 8px; grid-template-columns: repeat(1, minmax(0, 1fr)); margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
        .trip-rollup-item { border: 1px solid #dbe2ea; border-radius: 12px; background: #fff; padding: 10px; display: grid; gap: 2px; }
        .trip-rollup-title { font-size: 12px; color: #64748b; font-weight: 700; }
        .trip-rollup-value { font-size: 18px; color: #0f172a; font-weight: 700; }

        .trip-show-actions { display: grid; gap: 10px; margin-top: 10px; align-items: stretch; width: 100%; }
        .trip-show-btn { border: 1px solid #dbe2ea; border-radius: 10px; padding: 8px 12px; font-size: 13px; font-weight: 700; text-decoration: none; background: #fff; color: #0f172a; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; text-align: center; }
        .trip-show-btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .trip-show-btn.danger { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .trip-show-btn.danger:hover { background: #fee2e2; border-color: #fca5a5; }
        .trip-show-btn.disabled { background: #f1f5f9; border-color: #e2e8f0; color: #94a3b8; pointer-events: none; }
        .trip-show-btn.warning { background: #fefce8; border-color: #fde68a; color: #854d0e; }
        .trip-show-btn.success { background: #f0fdf4; border-color: #86efac; color: #166534; }
        .request-form { display: grid; gap: 9px; width: 100%; }
        .request-input { border: 1px solid #dbe2ea; border-radius: 10px; background: #f8fafc; color: #0f172a; padding: 9px 10px; font-size: 13px; }
        .request-route-card { border: 1px solid #dbe2ea; border-radius: 14px; background: #fff; padding: 14px; display: grid; gap: 12px; scroll-margin-top: 96px; }
        .request-route-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0; }
        .request-route-title-block { display: grid; gap: 3px; min-width: 0; }
        .request-route-title { color: #0f172a; font-size: 15px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
        .request-route-subtitle { margin: 0; color: #64748b; font-size: 12px; line-height: 1.35; }
        .request-route-badge { border: 1px solid #bfdbfe; background: #eff6ff; color: #1e3a8a; border-radius: 999px; padding: 5px 9px; font-size: 11px; font-weight: 800; white-space: nowrap; }
        .request-section { border: 1px solid #e2e8f0; background: #fff; border-radius: 12px; padding: 12px; display: grid; gap: 10px; }
        .request-section[hidden] { display: none; }
        .request-main-grid { display: grid; gap: 12px; align-items: stretch; }
        .request-control-column { display: grid; gap: 12px; align-content: start; }
        .request-map-column { display: grid; gap: 12px; align-content: start; }
        .request-section-head { display: grid; gap: 3px; }
        .request-section-title { color: #0f172a; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
        .request-section-hint { color: #64748b; font-size: 12px; line-height: 1.35; }
        .request-mode-grid { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 10px; }
        .request-option-group { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 11px; padding: 12px; display: grid; gap: 10px; align-content: start; }
        .request-option-label { color: #475569; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; }
        .request-radio-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .request-option { min-height: 38px; border: 1px solid #dbe2ea; border-radius: 10px; background: #fff; color: #334155; padding: 7px 10px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; text-align: center; }
        .request-option input { margin: 0; accent-color: #0f172a; }
        .request-help { margin: 0; color: #64748b; font-size: 12px; line-height: 1.35; }
        .request-time-row { display: grid; gap: 5px; }
        .request-time-row label { color: #475569; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; }
        .request-map-card { border: 1px solid #dbe2ea; border-radius: 12px; background: #f8fafc; padding: 12px; display: grid; gap: 10px; }
        .request-map-card[hidden] { display: none; }
        .request-map-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .request-map-title { color: #475569; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; display: inline-flex; align-items: center; gap: 6px; }
        .request-map-targets { display: inline-flex; gap: 6px; flex-wrap: wrap; }
        .request-map-target { border: 1px solid #dbe2ea; border-radius: 999px; background: #fff; color: #334155; height: 30px; padding: 0 10px; font-size: 12px; font-weight: 800; cursor: pointer; }
        .request-map-target.active { border-color: #0f172a; background: #0f172a; color: #fff; }
        .request-route-map { width: 100%; height: 260px; border: 1px solid #dbe2ea; border-radius: 10px; overflow: hidden; background: #eef2f7; }
        .request-route-map .leaflet-control-attribution { display: none; }
        .request-map-status { border: 1px solid #dbe2ea; background: #fff; border-radius: 9px; color: #475569; padding: 7px 9px; font-size: 12px; font-weight: 700; line-height: 1.35; }
        .request-map-status.blocked { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }
        .request-map-status.ok { border-color: #bbf7d0; background: #f0fdf4; color: #166534; }
        .request-map-legend { display: flex; align-items: center; gap: 7px 10px; flex-wrap: wrap; color: #64748b; font-size: 11px; font-weight: 700; }
        .request-map-legend span { display: inline-flex; align-items: center; gap: 5px; }
        .request-map-legend i { width: 18px; height: 5px; border-radius: 999px; display: inline-block; }
        .legend-route { background: #94a3b8; }
        .legend-preview { background: #1d4ed8; }
        .legend-zone { background: rgba(15, 23, 42, .18); }
        .legend-driver-point { width: 8px !important; height: 8px !important; background: #16a34a; }
        .legend-pin { width: 8px !important; height: 8px !important; background: #0f172a; }
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
        .request-fare-preview { border: 1px solid #fde68a; border-radius: 11px; background: #fffbeb; padding: 9px; display: grid; gap: 8px; }
        .request-fare-preview-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .request-fare-preview-title { color: #854d0e; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
        .request-fare-preview-badge { border: 1px solid #fcd34d; border-radius: 999px; background: rgba(255, 255, 255, .75); color: #92400e; padding: 4px 8px; font-size: 11px; font-weight: 800; }
        .request-fare-grid { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 7px; }
        .request-fare-item { border: 1px solid #fde68a; border-radius: 9px; background: rgba(255, 255, 255, .72); padding: 8px; display: grid; gap: 2px; }
        .request-fare-label { color: #92400e; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; }
        .request-fare-value { color: #0f172a; font-size: 15px; font-weight: 800; }
        .request-fare-note { margin: 0; color: #854d0e; font-size: 12px; line-height: 1.35; }
        .request-submit-row { border: 1px solid #e2e8f0; background: #fff; border-radius: 12px; padding: 12px; display: grid; gap: 10px; }
        .request-submit-actions { display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
        .request-action-bar { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; width: 100%; }
        .request-action-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .request-action-right { margin-left: auto; justify-content: flex-end; }
        .request-current-route { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; padding: 8px 10px; color: #334155; font-size: 12px; display: grid; gap: 3px; }
        .request-current-route strong { color: #0f172a; font-size: 13px; }
        .request-sent-card { display: grid; gap: 12px; width: 100%; }
        .request-sent-head { display: grid; gap: 4px; }
        .request-sent-title { margin: 0; color: #0f172a; font-family: Poppins, sans-serif; font-size: 20px; line-height: 1.15; }
        .request-sent-subtitle { margin: 0; color: #64748b; font-size: 12px; line-height: 1.35; }
        .request-sent-map-card { border: 1px solid #dbe2ea; background: #f8fafc; border-radius: 12px; padding: 10px; display: grid; gap: 8px; }
        .request-sent-map-title { color: #475569; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .request-sent-map { width: 100%; height: 240px; border: 1px solid #dbe2ea; border-radius: 10px; overflow: hidden; background: #eef2f7; }
        .request-sent-map .leaflet-control-attribution { display: none; }
        .request-sent-meta { display: flex; align-items: center; gap: 7px 10px; flex-wrap: wrap; color: #64748b; font-size: 11px; font-weight: 700; }
        .request-sent-meta span { display: inline-flex; align-items: center; gap: 5px; }
        .trip-note { border: 1px solid #fde68a; background: #fffbeb; color: #854d0e; border-radius: 10px; padding: 8px 10px; font-size: 13px; }
        .trip-contact-actions {
            margin-top: 8px;
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .trip-contact-link {
            border: 1px solid #dbe2ea;
            border-radius: 9px;
            background: #fff;
            color: #0f172a;
            height: 34px;
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
        @media (max-width: 767px) {
            .request-action-bar {
                justify-content: stretch;
            }
            .request-action-right {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                width: 100%;
                margin-left: 0;
            }
            .request-action-right .trip-show-btn,
            .request-action-right form .trip-show-btn {
                width: 100%;
                min-height: 40px;
            }
            .request-action-right form { width: 100%; }
        }

        @media (min-width: 768px) {
            .trip-show-card { padding: 16px; }
            .trip-point-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .trip-rollup-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
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
    </style>

    <div class="trip-show-page">
        <section class="trip-show-card">
            <div class="trip-show-head">
                <div>
                    <h1 class="trip-show-title">Trip Details</h1>
                    <p class="trip-show-subtitle">{{ $routeName }}</p>
                </div>
            </div>

            @if(!empty($aiRecommendation))
                <div class="trip-note" style="margin-top:10px;">
                    AI Match: {{ number_format((float) ($aiRecommendation['match_score'] ?? 0), 0) }}%.
                    {{ implode(' ', array_slice((array) ($aiRecommendation['explanations'] ?? []), 0, 3)) }}
                </div>
            @endif

            <div class="trip-modal-grid">
                <div class="trip-modal-line">
                    <span class="trip-modal-label">Trip ID</span>
                    <span class="trip-modal-value">{{ $tripIdDisplay }}</span>
                </div>
                <div class="trip-modal-line">
                    <span class="trip-modal-label">Date & Time</span>
                    <span class="trip-modal-value">{{ $trip->trip_datetime?->format('Y-m-d H:i') ?: '-' }}</span>
                </div>
                @if($isTwoWay)
                    <div class="trip-modal-line">
                        <span class="trip-modal-label">Trip Type</span>
                        <span class="trip-modal-value">Two-way</span>
                    </div>
@endif
                <div class="trip-point-cards">
                    <div class="trip-point-card pickup">
                        <span class="trip-point-label"><i class="fa-solid fa-location-dot"></i>Pickup Point</span>
                        <span class="trip-point-value">{{ $pickupName }}</span>
                    </div>
                    <div class="trip-point-card destination">
                        <span class="trip-point-label"><i class="fa-solid fa-flag-checkered"></i>Destination Point</span>
                        <span class="trip-point-value">{{ $destinationName }}</span>
                    </div>
                </div>

                <div class="trip-modal-line">
                    <span class="trip-modal-label">Driver</span>
                    <div class="trip-driver-content">
                        <span class="trip-driver-avatar">{{ strtoupper(substr((string) ($trip->driver?->name ?? 'D'), 0, 1)) }}</span>
                        <span class="trip-driver-meta">
                            <span class="trip-driver-name">{{ $trip->driver?->name ?: '-' }}</span>
                            <span class="trip-driver-email">{{ $trip->driver?->email ?: '-' }}</span>
                        </span>
                    </div>
                </div>

                <div class="trip-modal-line">
                    <div class="trip-passenger-header">
                        <span class="trip-modal-label">Passengers</span>
                        <span class="trip-passenger-count">{{ $passengerCount }} passengers</span>
                    </div>
                    <div class="trip-passenger-list">
                        @forelse($passengers as $participant)
                            <div class="trip-passenger-item">
                                <span class="trip-passenger-avatar">{{ strtoupper(substr((string) ($participant->user?->name ?? 'P'), 0, 1)) }}</span>
                                <span class="trip-passenger-meta">
                                    <span class="trip-passenger-name">{{ $participant->user?->name ?: '-' }}</span>
                                    <span class="trip-passenger-email">{{ $participant->user?->email ?: '-' }}</span>
                                </span>
                            </div>
                        @empty
                            <span class="trip-passenger-email">No passenger records for this trip.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="trip-rollup-grid">
                <div class="trip-rollup-item">
                    <div class="trip-rollup-title">Status</div>
                    <div class="trip-rollup-value">{{ $statusText }}</div>
                </div>
                <div class="trip-rollup-item">
                    <div class="trip-rollup-title">Seats</div>
                    <div class="trip-rollup-value">{{ $availableSeats !== null ? ($availableSeats . ' / ' . (int) $trip->seat_limit) : 'Open' }}</div>
                </div>
                <div class="trip-rollup-item">
                    <div class="trip-rollup-title">Fare / Person</div>
                    <div class="trip-rollup-value">RM {{ number_format((float) $trip->fare_per_person, 2) }}</div>
                </div>
                <div class="trip-rollup-item">
                    <div class="trip-rollup-title">Visibility</div>
                    <div class="trip-rollup-value">Public</div>
                </div>
                <div class="trip-rollup-item">
                    <div class="trip-rollup-title">Fare Split</div>
                    <div class="trip-rollup-value">{{ $splitTypeText }}</div>
                </div>
            </div>

            @if($trip->public_note)
                <div class="trip-note" style="margin-top:10px;">
                    {{ $trip->public_note }}
                </div>
            @endif

            @php
                $waUrl = ($canViewDriverWhatsapp ?? false) ? $trip->driver?->whatsapp_url : null;
                $emailUrl = ($canViewDriverEmail ?? false) && $trip->driver?->email
                    ? ('mailto:' . $trip->driver->email)
                    : null;
            @endphp
            <div class="trip-contact-actions">
                <a href="{{ $waUrl ?: '#' }}" target="_blank" rel="noopener" class="trip-contact-link whatsapp {{ $waUrl ? '' : 'is-disabled' }}">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>WhatsApp Driver</span>
                </a>
                <a href="{{ $emailUrl ?: '#' }}" class="trip-contact-link email {{ $emailUrl ? '' : 'is-disabled' }}">
                    <i class="fa-regular fa-envelope"></i>
                    <span>Email Driver</span>
                </a>
            </div>

        </section>

        <section class="trip-show-card">
            <div class="trip-show-actions">
                @if($isJoined)
                    <div class="request-action-bar">
                        <div class="request-action-right">
                            <a href="{{ route('explore.index') }}" class="trip-show-btn">Back</a>
                            <span class="trip-show-btn success disabled">Already Joined</span>
                        </div>
                    </div>
                @elseif($myRequest && $myRequest->status === 'pending')
                    <div class="request-sent-card">
                        <div class="request-sent-head">
                            <h2 class="request-sent-title">Request Sent</h2>
                            <p class="request-sent-subtitle">Your request is waiting for driver approval.</p>
                        </div>
                        @if($myRequest->routePoint)
                            <div class="request-sent-map-card">
                                <span class="request-sent-map-title">{{ $myRequest->routePoint->route_fit_label ?: 'Route preview' }}</span>
                                <div
                                    id="sentRequestMap"
                                    class="request-sent-map"
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
                    </div>
                    <div class="request-action-bar">
                        <div class="request-action-right">
                            <a href="{{ route('explore.index') }}" class="trip-show-btn">Back</a>
                            <form method="POST" action="{{ route('explore.join-requests.cancel', $myRequest) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="trip-show-btn danger">Cancel Request</button>
                            </form>
                        </div>
                    </div>
                @elseif($isFull || ! $trip->is_open_for_request)
                    <div class="request-action-bar">
                        <div class="request-action-right">
                            <a href="{{ route('explore.index') }}" class="trip-show-btn">Back</a>
                            <span class="trip-show-btn disabled">Not Available</span>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('explore.request-join', $trip) }}" class="request-form">
                        @csrf
                        <div class="request-route-card" id="join-request">
                            <div class="request-route-head">
                                <div class="request-route-title-block">
                                    <div class="request-route-title">
                                        <i class="fa-solid fa-route"></i>
                                        <span>Join Route Settings</span>
                                    </div>
                                    <p class="request-route-subtitle">Use the standard trip points or pin nearby stops along the current route. The driver will review your request before approval.</p>
                                </div>
                                <span class="request-route-badge">Public request</span>
                            </div>

                            <div class="request-main-grid">
                                <div class="request-control-column">
                                    <div class="request-section">
                                        <div class="request-section-head">
                                            <span class="request-section-title"><i class="fa-solid fa-map-location-dot"></i>Pickup and drop-off</span>
                                            <span class="request-section-hint">Use the default trip points or choose custom and pin nearby stops on the map.</span>
                                        </div>
                                        <div class="request-mode-grid">
                                            <div class="request-option-group">
                                                <span class="request-option-label">Pickup</span>
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
                                            <div class="request-option-group">
                                                <span class="request-option-label">Drop-off</span>
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

                                    <input type="hidden" name="fare_override_amount" value="{{ old('fare_override_amount') }}">
                                    <input type="hidden" name="detour_distance_km" value="{{ old('detour_distance_km') }}">
                                </div>

                                <div class="request-map-column">
                                    <div class="request-section" data-preview-section>
                                        <div class="request-section-head">
                                            <span class="request-section-title"><i class="fa-solid fa-route"></i>Route & Fare Preview</span>
                                            <span class="request-section-hint">The normal fare remains the base; custom pricing is calculated from the driver's original route distance.</span>
                                        </div>
                                        <div class="request-map-card" data-route-picker>
                                            <div class="request-map-head">
                                                <span class="request-map-title">Pin percustom stops</span>
                                                <span class="request-map-targets">
                                                    <button type="button" class="request-map-target active" data-map-target="pickup" hidden>Pin Pickup</button>
                                                    <button type="button" class="request-map-target" data-map-target="dropoff" hidden>Pin Drop-off</button>
                                                </span>
                                            </div>
                                            <div id="requestRouteMap" class="request-route-map"></div>
                                            <div class="request-map-legend">
                                                <span><i class="legend-route"></i>Current route</span>
                                                <span><i class="legend-preview"></i>Suggested join route</span>
                                                <span><i class="legend-zone"></i><span id="routeAllowedLabel">Kawasan dibenarkan</span></span>
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
                                                        <span class="request-fare-label">Your fare</span>
                                                        <span class="request-fare-value" id="farePreviewPassenger">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                                    </div>
                                                    <div class="request-fare-item">
                                                        <span class="request-fare-label">Agihan lain</span>
                                                        <span class="request-fare-value" id="farePreviewOthers">RM {{ number_format((float) $trip->fare_per_person, 2) }}</span>
                                                    </div>
                                                </div>
                                                <p class="request-fare-note" id="farePreviewNote">The normal fare remains the base. Custom pickup/drop-off only adds an extra charge based on distance from the original route.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="request-submit-row">
                                <input class="request-input" type="text" name="request_note" placeholder="Request note (optional)">
                                <p class="request-help">After approval, arrange final timing and coordination with the driver through WhatsApp or email.</p>
                                <div class="request-action-bar">
                                    <div class="request-action-right">
                                        <a href="{{ route('explore.index') }}" class="trip-show-btn">Back</a>
                                        <button type="submit" class="trip-show-btn primary">Request to Join</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>

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

            const mapEl = document.getElementById('requestRouteMap');
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
                const hiddenFareInput = document.querySelector('input[name="fare_override_amount"]');
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
                    document.getElementById('farePreviewNote').textContent = 'Pin the custom pickup/drop-off on the map to preview suggested route and fare.';
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
                    hiddenFareInput.value = hasCustom ? passengerFare.toFixed(2) : '';
                }
                if (hiddenDetourInput) {
                    hiddenDetourInput.value = hasCustom ? routeDeviationKm.toFixed(2) : '';
                }
                document.getElementById('farePreviewBadge').textContent = hasCustom ? 'Custom route charge' : 'Default split';
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
    </script>
@endsection
