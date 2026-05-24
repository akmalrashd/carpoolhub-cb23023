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
/* ── Page layout ────────────────────────────────────────────── */
.ts-page { display: grid; gap: 20px; }

/* ── Announcement banner ────────────────────────────────────── */
.ts-announcement {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    background: var(--info-soft);
    border: 1px solid #bfdbfe;
    border-radius: var(--r-md);
    padding: 12px 16px;
}

.ts-announcement-text {
    margin: 0;
    color: var(--info-ink);
    font-size: 13px;
    line-height: 1.45;
    font-weight: 500;
}

/* ── Cards ──────────────────────────────────────────────────── */
.ts-card {
    background: var(--surface);
    border: 1px solid var(--hairline);
    border-radius: var(--r-lg);
    padding: 20px;
    box-shadow: var(--shadow-1);
}

/* ── Card header ────────────────────────────────────────────── */
.ts-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--hairline);
}

.ts-card-title-group { min-width: 0; }

.ts-card-eyebrow {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--muted);
    margin-bottom: 4px;
}

.ts-card-title {
    margin: 0;
    font-family: var(--font-display), sans-serif;
    font-size: 22px;
    font-weight: 800;
    color: var(--ink);
    line-height: 1.15;
}

.ts-card-subtitle {
    margin: 5px 0 0;
    font-size: 13px;
    color: var(--muted);
    line-height: 1.4;
}

.ts-card-actions {
    display: inline-flex;
    gap: 8px;
    flex-wrap: wrap;
    flex-shrink: 0;
}

/* ── Status badge ────────────────────────────────────────────── */
.ts-status-badge {
    display: inline-flex;
    align-items: center;
    border-radius: var(--r-pill);
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid;
}

.ts-status-draft     { color: var(--ink-3); border-color: var(--hairline-strong); background: var(--canvas); }
.ts-status-scheduled { color: var(--info);  border-color: #bfdbfe; background: var(--info-soft); }
.ts-status-recorded  { color: var(--success-ink); border-color: #86efac; background: var(--success-soft); }
.ts-status-cancelled { color: var(--danger-ink); border-color: #fecaca; background: var(--danger-soft); }

/* ── Details grid ────────────────────────────────────────────── */
.ts-details-grid {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.ts-detail-cell {
    background: var(--surface-2);
    border: 1px solid var(--hairline);
    border-radius: var(--r-sm);
    padding: 10px 12px;
    display: grid;
    gap: 3px;
}

.ts-detail-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--muted);
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.ts-detail-label i { font-size: 10px; color: var(--info); }

.ts-detail-value {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
    word-break: break-word;
    line-height: 1.35;
}

/* ── Route point cards ───────────────────────────────────────── */
.ts-point-grid {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.ts-point-card {
    border-radius: var(--r-sm);
    padding: 12px;
    display: grid;
    gap: 4px;
}

.ts-point-card.pickup    { border: 1px solid #bbf7d0; background: #f0fdf4; }
.ts-point-card.destination { border: 1px solid #bfdbfe; background: #eff6ff; }

.ts-point-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.ts-point-card.pickup .ts-point-label    { color: #166534; }
.ts-point-card.destination .ts-point-label { color: #1d4ed8; }

.ts-point-value {
    font-size: 14px;
    font-weight: 700;
    color: var(--ink);
    line-height: 1.3;
}

/* ── Driver row ──────────────────────────────────────────────── */
.ts-driver-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ts-driver-avatar {
    width: 36px;
    height: 36px;
    border-radius: var(--r-pill);
    border: 1px solid var(--hairline);
    background: var(--canvas);
    color: var(--ink);
    font-size: 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}

.ts-driver-meta { display: grid; gap: 1px; min-width: 0; }
.ts-driver-name { font-size: 14px; font-weight: 700; color: var(--ink); line-height: 1.2; }
.ts-driver-email { font-size: 12px; color: var(--muted); line-height: 1.2; word-break: break-word; }

/* ── Passenger list ──────────────────────────────────────────── */
.ts-passenger-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
}

.ts-passenger-count {
    background: var(--canvas);
    border: 1px solid var(--hairline-strong);
    color: var(--ink-2);
    border-radius: var(--r-pill);
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 700;
}

.ts-passenger-list {
    border: 1px solid var(--hairline);
    background: var(--surface-2);
    border-radius: var(--r-sm);
    padding: 8px;
    display: grid;
    gap: 8px;
}

.ts-passenger-item {
    border: 1px solid var(--hairline);
    background: var(--surface);
    border-radius: var(--r-sm);
    padding: 8px 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ts-passenger-avatar {
    width: 30px;
    height: 30px;
    border-radius: var(--r-pill);
    border: 1px solid var(--hairline);
    background: var(--canvas);
    color: var(--ink);
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}

.ts-passenger-meta { min-width: 0; display: grid; gap: 1px; flex: 1 1 auto; }
.ts-passenger-name  { font-size: 13px; font-weight: 700; color: var(--ink); line-height: 1.2; }
.ts-passenger-email { font-size: 11px; color: var(--muted); line-height: 1.2; word-break: break-word; }

.ts-passenger-empty { font-size: 13px; color: var(--muted); }

/* ── Rollup stat tiles ───────────────────────────────────────── */
.ts-rollup-grid {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.ts-rollup-tile {
    background: var(--surface-2);
    border: 1px solid var(--hairline);
    border-radius: var(--r-md);
    padding: 14px;
    display: grid;
    gap: 3px;
}

.ts-rollup-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--muted);
}

.ts-rollup-value {
    font-family: var(--font-display), sans-serif;
    font-size: 22px;
    font-weight: 800;
    color: var(--ink);
    line-height: 1.1;
}

.ts-rollup-meta { font-size: 12px; color: var(--muted); }

/* ── Section divider label ───────────────────────────────────── */
.ts-section-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--muted);
    margin: 0 0 10px;
}

/* ── Responsive ──────────────────────────────────────────────── */
@media (min-width: 640px) {
    .ts-point-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (min-width: 768px) {
    .ts-card { padding: 24px; }
    .ts-details-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ts-rollup-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
</style>

<div class="ts-page">

    {{-- Announcement banner --}}
    <div class="ts-announcement">
        <p class="ts-announcement-text">
            <i class="fa-solid fa-circle-check" style="margin-right:5px;"></i>
            Trip saved successfully. You can manage this trip now or create another trip right away.
        </p>
        <a href="{{ route('trips.create') }}" class="btn btn-primary btn-sm">Create Another Trip</a>
    </div>

    {{-- Main details card --}}
    <section class="ts-card">
        <div class="ts-card-head">
            <div class="ts-card-title-group">
                <p class="ts-card-eyebrow"><i class="fa-solid fa-route"></i> Trip Details</p>
                <h1 class="ts-card-title">{{ $routeName }}</h1>
                <p class="ts-card-subtitle">{{ $tripIds }} &bull; {{ $datetimeText }}</p>
            </div>
            <div class="ts-card-actions">
                @if(($trip->visibility ?? 'private') === 'public' && (auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id))
                    <a href="{{ route('trips.requests.index', $trip) }}" class="btn btn-soft btn-sm">Manage Requests</a>
                @endif
                @if(auth()->user()->role === 'admin' || auth()->id() === $trip->driver_id)
                    <a href="{{ route('trips.edit', $trip) }}" class="btn btn-ghost btn-sm">Edit Trip</a>
                @endif
            </div>
        </div>

        {{-- Route points --}}
        <div class="ts-point-grid" style="margin-bottom:14px;">
            <div class="ts-point-card pickup">
                <span class="ts-point-label"><i class="fa-solid fa-location-dot"></i>{{ $pointALabel }}</span>
                <span class="ts-point-value">{{ $pickupName }}</span>
            </div>
            <div class="ts-point-card destination">
                <span class="ts-point-label"><i class="fa-solid fa-flag-checkered"></i>{{ $pointBLabel }}</span>
                <span class="ts-point-value">{{ $destinationName }}</span>
            </div>
        </div>

        {{-- Detail cells --}}
        <div class="ts-details-grid" style="margin-bottom:14px;">
            <div class="ts-detail-cell">
                <span class="ts-detail-label"><i class="fa-regular fa-calendar"></i>Date & Time</span>
                <span class="ts-detail-value">{{ $datetimeText }}</span>
            </div>
            <div class="ts-detail-cell">
                <span class="ts-detail-label"><i class="fa-solid fa-route"></i>Trip Type</span>
                <span class="ts-detail-value">{{ $modeText }}</span>
            </div>
            <div class="ts-detail-cell">
                <span class="ts-detail-label"><i class="fa-regular fa-circle-check"></i>Status</span>
                <span id="tripShowStatusBadge" class="ts-status-badge ts-status-{{ strtolower($trip->status) }}">{{ $statusText }}</span>
            </div>
            <div class="ts-detail-cell">
                <span class="ts-detail-label"><i class="fa-solid fa-scale-balanced"></i>Fare Split Type</span>
                <span class="ts-detail-value">{{ $splitType }}</span>
            </div>
            <div class="ts-detail-cell">
                <span class="ts-detail-label"><i class="fa-solid fa-sack-dollar"></i>Total Trip Fare</span>
                <span class="ts-detail-value" style="font-size:16px;font-weight:800;">RM {{ number_format($combinedFare, 2) }}</span>
            </div>
            <div class="ts-detail-cell">
                <span class="ts-detail-label"><i class="fa-solid fa-user-group"></i>Total Passengers</span>
                <span id="tripShowTotalPassengers" class="ts-detail-value">{{ $passengerCount }}</span>
            </div>
        </div>

        {{-- Driver --}}
        <div class="ts-detail-cell" style="margin-bottom:14px;">
            <span class="ts-detail-label" style="margin-bottom:6px;"><i class="fa-solid fa-steering-wheel"></i>Driver</span>
            <div class="ts-driver-row">
                <span class="ts-driver-avatar">{{ strtoupper(substr((string) ($trip->driver?->name ?? 'D'), 0, 1)) }}</span>
                <span class="ts-driver-meta">
                    <span class="ts-driver-name">{{ $trip->driver?->name ?: '-' }}</span>
                    <span class="ts-driver-email">{{ $trip->driver?->email ?: '-' }}</span>
                </span>
            </div>
        </div>

        {{-- Passengers --}}
        <div class="ts-detail-cell">
            <div class="ts-passenger-header">
                <span class="ts-detail-label"><i class="fa-solid fa-users"></i>Passengers</span>
                <span id="tripShowPassengerCountChip" class="ts-passenger-count">{{ $passengerCount }} passengers</span>
            </div>
            <div id="tripShowPassengerList" class="ts-passenger-list">
                @forelse($passengers as $participant)
                    <div class="ts-passenger-item">
                        <span class="ts-passenger-avatar">{{ strtoupper(substr((string) ($participant->user?->name ?? 'P'), 0, 1)) }}</span>
                        <span class="ts-passenger-meta">
                            <span class="ts-passenger-name">{{ $participant->user?->name ?: '-' }}</span>
                            <span class="ts-passenger-email">{{ $participant->user?->email ?: '-' }}</span>
                        </span>
                    </div>
                @empty
                    <span class="ts-passenger-empty">No passenger records found for this trip.</span>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Payment rollup card --}}
    <section class="ts-card">
        <p class="ts-section-label"><i class="fa-solid fa-credit-card" style="margin-right:5px;"></i>Payment Summary</p>
        <div class="ts-rollup-grid">
            <div class="ts-rollup-tile">
                <div class="ts-rollup-label">Unpaid</div>
                <div id="tripShowRollupUnpaidAmount" class="ts-rollup-value">RM {{ number_format((float) ($rollups['unpaid']['amount'] ?? 0), 2) }}</div>
                <div id="tripShowRollupUnpaidCount" class="ts-rollup-meta">{{ $rollups['unpaid']['count'] ?? 0 }} payments</div>
            </div>
            <div class="ts-rollup-tile">
                <div class="ts-rollup-label">Pending Confirmation</div>
                <div id="tripShowRollupPendingAmount" class="ts-rollup-value">RM {{ number_format((float) ($rollups['pending_confirmation']['amount'] ?? 0), 2) }}</div>
                <div id="tripShowRollupPendingCount" class="ts-rollup-meta">{{ $rollups['pending_confirmation']['count'] ?? 0 }} payments</div>
            </div>
            <div class="ts-rollup-tile">
                <div class="ts-rollup-label">Paid</div>
                <div id="tripShowRollupPaidAmount" class="ts-rollup-value" style="color:var(--success);">RM {{ number_format((float) ($rollups['paid']['amount'] ?? 0), 2) }}</div>
                <div id="tripShowRollupPaidCount" class="ts-rollup-meta">{{ $rollups['paid']['count'] ?? 0 }} payments</div>
            </div>
            <div class="ts-rollup-tile" style="border-color:var(--ch-yellow-line);background:var(--ch-yellow-tint);">
                <div class="ts-rollup-label">Total</div>
                <div id="tripShowRollupTotalAmount" class="ts-rollup-value" style="color:var(--ch-yellow-ink);">RM {{ number_format((float) ($rollups['total']['amount'] ?? 0), 2) }}</div>
                <div id="tripShowRollupTotalCount" class="ts-rollup-meta">{{ $rollups['total']['count'] ?? 0 }} payments</div>
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
                passengerListEl.innerHTML = '<span class="ts-passenger-empty">No passenger records found for this trip.</span>';
                return;
            }
            passengerListEl.innerHTML = rows.map((row) => `
                <div class="ts-passenger-item">
                    <span class="ts-passenger-avatar">${esc(row.initial || 'P')}</span>
                    <span class="ts-passenger-meta">
                        <span class="ts-passenger-name">${esc(row.name || '-')}</span>
                        <span class="ts-passenger-email">${esc(row.email || '-')}</span>
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
                statusBadgeEl.className = `ts-status-badge ts-status-${statusSlug(statusText) || 'draft'}`;

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
