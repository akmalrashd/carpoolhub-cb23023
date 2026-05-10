@extends('layouts.app')

@section('content')
    @php
        $returnTrip = $trip->returnTrip;
        $hasReturn = (bool) $returnTrip;

        $pickupName = $trip->pickup_name ?? 'Pickup';
        $destinationName = $trip->destination_name ?? 'Destination';
        $routeName = $trip->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);

        $modeText = $hasReturn ? 'Two-way' : 'One-way';
        $statusText = ucfirst($trip->status);
        $pointALabel = 'Pickup Point';
        $pointBLabel = 'Destination Point';

        $combinedFare = (float) $trip->fare_total + (float) ($returnTrip?->fare_total ?? 0);
        $tripIds = $hasReturn ? ('#' . $trip->id . ' & #' . $returnTrip->id) : ('#' . $trip->id);
        $datetimeText = $trip->trip_datetime?->format('Y-m-d H:i') ?: '-';

        $passengers = $trip->participants->filter(fn ($participant) => ! $participant->is_driver)->values();
        $passengerCount = $passengers->count();
        $includeDriverInSplit = ((int) $trip->participant_count) > $passengerCount;
        $splitType = $includeDriverInSplit ? 'Driver Included in Fare Split' : 'Driver Excluded from Fare Split';

    @endphp

    <style>
        .trip-show-page { display: grid; gap: 12px; }
        .trip-show-card { background: #fff; border: 1px solid #dbe2ea; border-radius: 16px; padding: 14px; }
        .trip-show-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .trip-show-title { margin: 0; color: #0f172a; font-family: Poppins, sans-serif; font-size: 28px; line-height: 1.1; }
        .trip-show-subtitle { margin: 6px 0 0; color: #64748b; font-size: 14px; }
        .trip-show-actions { display: inline-flex; gap: 7px; flex-wrap: wrap; }
        .trip-show-btn { border: 1px solid #dbe2ea; background: #fff; color: #0f172a; border-radius: 10px; padding: 8px 12px; text-decoration: none; font-size: 13px; font-weight: 700; }
        .trip-show-btn.primary { color: #1e3a8a; border-color: #bfdbfe; background: #eff6ff; }

        .trip-modal-grid { display: grid; gap: 7px; }
        .trip-details-pairs { display: grid; gap: 7px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .trip-modal-line { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; padding: 8px 10px; display: grid; gap: 2px; }
        .trip-modal-label { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .03em; font-weight: 700; }
        .trip-modal-value { color: #0f172a; font-size: 13px; font-weight: 600; word-break: break-word; }
        .trip-icon-label { display: inline-flex; align-items: center; gap: 5px; }
        .trip-icon-label i { font-size: 11px; color: #1e3a8a; }

        .trip-status-badge { display: inline-flex; align-items: center; border-radius: 999px; border: 1px solid #dbe2ea; padding: 4px 10px; font-size: 12px; font-weight: 700; width: fit-content; }
        .trip-status-draft { color: #475569; border-color: #cbd5e1; background: #f8fafc; }
        .trip-status-scheduled { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
        .trip-status-recorded { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .trip-status-cancelled { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }

        .trip-point-cards { display: grid; gap: 7px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .trip-point-card { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; padding: 8px 10px; display: grid; gap: 3px; }
        .trip-point-card.pickup { border-color: #bbf7d0; background: #f0fdf4; }
        .trip-point-card.destination { border-color: #bfdbfe; background: #eff6ff; }
        .trip-point-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; display: inline-flex; align-items: center; gap: 5px; }
        .trip-point-card.pickup .trip-point-label { color: #166534; }
        .trip-point-card.destination .trip-point-label { color: #1e3a8a; }
        .trip-point-value { color: #0f172a; font-size: 13px; font-weight: 700; line-height: 1.3; }

        .trip-announcement {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 12px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .trip-announcement-text {
            margin: 0;
            color: #1e3a8a;
            font-size: 13px;
            line-height: 1.4;
        }

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

        .trip-rollup-grid { display: grid; gap: 8px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .trip-rollup-item { border: 1px solid #dbe2ea; border-radius: 12px; background: #fff; padding: 10px; display: grid; gap: 2px; }
        .trip-rollup-title { font-size: 12px; color: #64748b; font-weight: 700; }
        .trip-rollup-value { font-size: 18px; color: #0f172a; font-weight: 700; }
        .trip-rollup-meta { font-size: 12px; color: #64748b; }

        @media (min-width: 768px) {
            .trip-show-card { padding: 16px; }
            .trip-details-pairs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .trip-point-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .trip-rollup-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
    </style>

    <div class="trip-show-page">
        <div class="trip-announcement">
            <p class="trip-announcement-text">Trip saved successfully. You can manage this trip now or create another trip right away.</p>
            <a href="{{ route('trips.create') }}" class="trip-show-btn primary">Create Another Trip</a>
        </div>

        <section class="trip-show-card">
            <div class="trip-show-head">
                <div>
                    <h1 class="trip-show-title">Trip Details</h1>
                    <p class="trip-show-subtitle">{{ $routeName }}</p>
                </div>
                <div class="trip-show-actions">
                    @if(($trip->visibility ?? 'private') === 'public' && (auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id))
                        <a href="{{ route('trips.requests.index', $trip) }}" class="trip-show-btn primary">Manage Requests</a>
                    @endif
                    @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                        <a href="{{ route('trips.edit', $trip) }}" class="trip-show-btn">Edit Trip</a>
                    @endif
                </div>
            </div>

            <div class="trip-modal-grid" style="margin-top:10px;">
                <div class="trip-details-pairs">
                    <div class="trip-modal-line">
                        <span class="trip-modal-label">Trip ID</span>
                        <span class="trip-modal-value">{{ $tripIds }}</span>
                    </div>
                    <div class="trip-modal-line">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-regular fa-calendar"></i>Date & Time</span>
                        <span class="trip-modal-value">{{ $datetimeText }}</span>
                    </div>
                </div>

                <div class="trip-modal-line">
                    <span class="trip-modal-label">Route Name</span>
                    <span class="trip-modal-value">{{ $routeName }}</span>
                </div>

                <div class="trip-point-cards">
                    <div class="trip-point-card pickup">
                        <span class="trip-point-label"><i class="fa-solid fa-location-dot"></i>{{ $pointALabel }}</span>
                        <span class="trip-point-value">{{ $pickupName }}</span>
                    </div>
                    <div class="trip-point-card destination">
                        <span class="trip-point-label"><i class="fa-solid fa-flag-checkered"></i>{{ $pointBLabel }}</span>
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
                        <span id="tripShowPassengerCountChip" class="trip-passenger-count">{{ $passengerCount }} passengers</span>
                    </div>
                    <div id="tripShowPassengerList" class="trip-passenger-list">
                        @forelse($passengers as $participant)
                            <div class="trip-passenger-item">
                                <span class="trip-passenger-avatar">{{ strtoupper(substr((string) ($participant->user?->name ?? 'P'), 0, 1)) }}</span>
                                <span class="trip-passenger-meta">
                                    <span class="trip-passenger-name">{{ $participant->user?->name ?: '-' }}</span>
                                    <span class="trip-passenger-email">{{ $participant->user?->email ?: '-' }}</span>
                                </span>
                            </div>
                        @empty
                            <span class="trip-passenger-email">No passenger records found for this trip.</span>
                        @endforelse
                    </div>
                </div>

                <div class="trip-details-pairs">
                    <div class="trip-modal-line">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-route"></i>Trip Type</span>
                        <span class="trip-modal-value">{{ $modeText }}</span>
                    </div>
                    <div class="trip-modal-line">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-regular fa-circle-check"></i>Status</span>
                        <span id="tripShowStatusBadge" class="trip-status-badge trip-status-{{ strtolower($trip->status) }}">{{ $statusText }}</span>
                    </div>
                    <div class="trip-modal-line">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user-group"></i>Total Passengers</span>
                        <span id="tripShowTotalPassengers" class="trip-modal-value">{{ $passengerCount }}</span>
                    </div>
                    <div class="trip-modal-line">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-scale-balanced"></i>Fare Split Type</span>
                        <span class="trip-modal-value">{{ $splitType }}</span>
                    </div>
                    <div class="trip-modal-line">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-sack-dollar"></i>Total Trip Fare</span>
                        <span class="trip-modal-value">RM {{ number_format($combinedFare, 2) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="trip-show-card">
            <div class="trip-rollup-grid">
                <div class="trip-rollup-item">
                    <div class="trip-rollup-title">Unpaid</div>
                    <div id="tripShowRollupUnpaidAmount" class="trip-rollup-value">RM {{ number_format((float) ($rollups['unpaid']['amount'] ?? 0), 2) }}</div>
                    <div id="tripShowRollupUnpaidCount" class="trip-rollup-meta">{{ $rollups['unpaid']['count'] ?? 0 }} payments</div>
                </div>
                <div class="trip-rollup-item">
                    <div class="trip-rollup-title">Pending Confirmation</div>
                    <div id="tripShowRollupPendingAmount" class="trip-rollup-value">RM {{ number_format((float) ($rollups['pending_confirmation']['amount'] ?? 0), 2) }}</div>
                    <div id="tripShowRollupPendingCount" class="trip-rollup-meta">{{ $rollups['pending_confirmation']['count'] ?? 0 }} payments</div>
                </div>
                <div class="trip-rollup-item">
                    <div class="trip-rollup-title">Paid</div>
                    <div id="tripShowRollupPaidAmount" class="trip-rollup-value">RM {{ number_format((float) ($rollups['paid']['amount'] ?? 0), 2) }}</div>
                    <div id="tripShowRollupPaidCount" class="trip-rollup-meta">{{ $rollups['paid']['count'] ?? 0 }} payments</div>
                </div>
                <div class="trip-rollup-item">
                    <div class="trip-rollup-title">Total</div>
                    <div id="tripShowRollupTotalAmount" class="trip-rollup-value">RM {{ number_format((float) ($rollups['total']['amount'] ?? 0), 2) }}</div>
                    <div id="tripShowRollupTotalCount" class="trip-rollup-meta">{{ $rollups['total']['count'] ?? 0 }} payments</div>
                </div>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const endpoint = @json(route('refresh.trips.status', $trip));
            const statusBadgeEl = document.getElementById('tripShowStatusBadge');
            const totalPassengersEl = document.getElementById('tripShowTotalPassengers');
            const passengerCountChipEl = document.getElementById('tripShowPassengerCountChip');
            const passengerListEl = document.getElementById('tripShowPassengerList');
            if (!statusBadgeEl || !passengerListEl) return;

            const setText = (id, text) => {
                const el = document.getElementById(id);
                if (el) el.textContent = text;
            };
            const money = (value) => `RM ${Number(value || 0).toFixed(2)}`;
            const statusSlug = (value) => String(value || '')
                .trim()
                .toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^a-z0-9-]/g, '');
            const esc = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const renderPassengers = (passengers) => {
                const rows = Array.isArray(passengers) ? passengers : [];
                if (rows.length === 0) {
                    passengerListEl.innerHTML = '<span class="trip-passenger-email">No passenger records found for this trip.</span>';
                    return;
                }
                passengerListEl.innerHTML = rows.map((row) => `
                    <div class="trip-passenger-item">
                        <span class="trip-passenger-avatar">${esc(row.initial || 'P')}</span>
                        <span class="trip-passenger-meta">
                            <span class="trip-passenger-name">${esc(row.name || '-')}</span>
                            <span class="trip-passenger-email">${esc(row.email || '-')}</span>
                        </span>
                    </div>
                `).join('');
            };

            const applyRollup = (key, payload) => {
                setText(`tripShowRollup${key}Amount`, money(payload?.amount || 0));
                setText(`tripShowRollup${key}Count`, `${Number(payload?.count || 0)} payments`);
            };

            let inFlight = false;
            const poll = async () => {
                if (inFlight || document.visibilityState !== 'visible') return;
                inFlight = true;
                try {
                    const response = await fetch(endpoint, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) return;
                    const payload = await response.json();

                    const statusText = String(payload?.status_text || '-');
                    statusBadgeEl.textContent = statusText;
                    statusBadgeEl.className = `trip-status-badge trip-status-${statusSlug(statusText) || 'draft'}`;

                    const passengerCount = Number(payload?.passenger_count || 0);
                    if (totalPassengersEl) totalPassengersEl.textContent = String(passengerCount);
                    if (passengerCountChipEl) {
                        passengerCountChipEl.textContent = `${passengerCount} passengers`;
                    }
                    renderPassengers(payload?.passengers);

                    applyRollup('Unpaid', payload?.rollups?.unpaid);
                    applyRollup('Pending', payload?.rollups?.pending_confirmation);
                    applyRollup('Paid', payload?.rollups?.paid);
                    applyRollup('Total', payload?.rollups?.total);
                } catch (_error) {
                } finally {
                    inFlight = false;
                }
            };

            window.setInterval(poll, 5000);
        })();
    </script>
@endsection
