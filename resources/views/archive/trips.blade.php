@extends('layouts.app')

@section('content')
    <style>
        .archive-trips-page { display: grid; gap: 16px; }

        /* ── Shared card shell ── */
        .archive-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 20px;
            box-shadow: var(--shadow-1);
        }

        /* ── Hero ── */
        .archive-hero {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: var(--shadow-1);
        }

        .archive-title {
            margin: 0;
            font-family: var(--font-display), sans-serif;
            font-size: clamp(1.4rem, 2vw, 1.8rem);
            color: var(--ink);
            line-height: 1.05;
        }

        .archive-subtitle { margin: 6px 0 0; color: var(--muted); font-size: 14px; }

        /* ── Filter row ── */
        .archive-filter-row {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr;
        }

        .archive-label {
            display: block;
            font-size: 12px;
            color: var(--ink-3);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .archive-select {
            width: 100%;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            color: var(--ink);
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
        }

        .archive-select:focus { border-color: var(--ch-yellow); }

        /* ── Trip cards ── */
        .archive-grid { display: grid; gap: 10px; }

        .archive-trip-item {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface);
            padding: 16px;
            display: grid;
            gap: 12px;
            transition: box-shadow .15s;
        }

        .archive-trip-item:hover { box-shadow: var(--shadow-2); }

        .archive-trip-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .archive-route-title {
            margin: 0;
            color: var(--ink);
            font-size: 16px;
            font-weight: 700;
            line-height: 1.3;
        }

        .archive-route-sub {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.35;
        }

        .archive-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: var(--r-pill);
            border: 1px solid var(--hairline);
            background: var(--surface-2);
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .archive-chip.mode {
            color: var(--warning-ink);
            border-color: var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
        }

        .archive-chip.completed {
            color: var(--success-ink);
            border-color: #86efac;
            background: var(--success-soft);
        }

        .archive-chip.cancelled {
            color: var(--muted);
            border-color: var(--hairline-strong);
            background: var(--surface-2);
        }

        /* ── Detail lines ── */
        .archive-trip-grid { display: grid; gap: 6px; }

        .archive-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            padding: 8px 12px;
            font-size: 12px;
            color: var(--ink-3);
        }

        .archive-line strong {
            color: var(--ink);
            text-align: right;
        }

        /* ── Empty state ── */
        .archive-empty {
            border: 1px dashed var(--hairline-strong);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 32px;
            color: var(--muted);
            font-size: 14px;
            text-align: center;
        }

        /* ── Modal ── */
        .archive-modal {
            position: fixed;
            inset: 0;
            background: rgba(11, 18, 32, .54);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 2600;
        }

        .archive-modal.show { display: flex; }

        .archive-modal-card {
            width: min(700px, 100%);
            max-height: 84vh;
            overflow: auto;
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            background: var(--surface);
            padding: 20px;
            display: grid;
            gap: 12px;
            box-shadow: var(--shadow-3);
        }

        .archive-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .archive-modal-title {
            margin: 0;
            color: var(--ink);
            font-size: 18px;
            font-weight: 700;
            font-family: var(--font-display), sans-serif;
        }

        .archive-modal-close {
            width: 34px;
            height: 34px;
            border-radius: var(--r-sm);
            border: 1px solid var(--hairline);
            background: var(--surface);
            color: var(--ink-3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }

        .archive-modal-grid { display: grid; gap: 8px; }

        .archive-modal-line {
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            padding: 10px 12px;
            display: grid;
            gap: 2px;
        }

        .archive-modal-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .archive-modal-value {
            color: var(--ink);
            font-size: 13px;
            font-weight: 600;
            word-break: break-word;
        }

        /* ── Point cards ── */
        .archive-point-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr;
        }

        .archive-point-card {
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            padding: 10px 12px;
            display: grid;
            gap: 3px;
        }

        .archive-point-card.pickup {
            border-color: #bbf7d0;
            background: var(--success-soft);
        }

        .archive-point-card.destination {
            border-color: #bfdbfe;
            background: var(--info-soft);
        }

        .archive-point-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .archive-point-card.pickup .archive-point-label { color: var(--success-ink); }
        .archive-point-card.destination .archive-point-label { color: var(--info-ink); }

        .archive-point-value {
            color: var(--ink);
            font-size: 13px;
            font-weight: 700;
            line-height: 1.3;
        }

        /* ── Passenger list ── */
        .archive-passenger-list { display: grid; gap: 6px; }

        .archive-passenger-item {
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            background: var(--surface);
            padding: 8px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .archive-passenger-name { color: var(--ink); font-size: 13px; font-weight: 700; }
        .archive-passenger-email { color: var(--muted); font-size: 11px; }

        /* ── Responsive ── */
        @media (min-width: 768px) {
            .archive-filter-row { grid-template-columns: 1fr auto auto; align-items: end; }
            .archive-point-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

    <div class="archive-trips-page">

        {{-- Hero --}}
        <section class="archive-hero">
            <div>
                <h1 class="archive-title">Archived Trips</h1>
                <p class="archive-subtitle">Read-only trip history for the selected archive month.</p>
            </div>
            <a href="{{ route('archive.index', ['month' => $monthKey]) }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Archive
            </a>
        </section>

        {{-- Month Filter --}}
        <section class="archive-card">
            <form method="GET" action="{{ route('archive.trips.index') }}" class="archive-filter-row">
                <div>
                    <label class="archive-label" for="month">Month</label>
                    <select class="archive-select" name="month" id="month">
                        <option value="" disabled {{ $monthKey ? '' : 'selected' }}>Select archive month</option>
                        @foreach($months as $month)
                            <option value="{{ $month }}" {{ $monthKey === $month ? 'selected' : '' }}>{{ $month }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-dark btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
                <a href="{{ route('archive.trips.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </form>
        </section>

        {{-- Trip List --}}
        <section class="archive-card">
            @if($archivedTrips->isEmpty())
                <div class="archive-empty">
                    <i class="fa-solid fa-box-open" style="font-size:24px; margin-bottom:8px; display:block; color:var(--muted-2);"></i>
                    No archived trips found for this month.
                </div>
            @else
                <div class="archive-grid">
                    @foreach($archivedTrips as $trip)
                        @php
                            $hasReturn = (bool) $trip->returnTrip;
                            $pickupName = $trip->pickup_name ?? 'Pickup';
                            $destinationName = $trip->destination_name ?? 'Destination';
                            $routeName = $trip->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                            $participants = $trip->participants->filter(fn ($item) => ! $item->is_driver)->values();
                            $participantPayload = $participants->map(fn ($participant) => [
                                'name' => $participant->user?->name ?? '-',
                                'email' => $participant->user?->email ?? '-',
                            ])->values();
                        @endphp
                        <article class="archive-trip-item">
                            <div class="archive-trip-head">
                                <div>
                                    <h2 class="archive-route-title">{{ $routeName }}</h2>
                                    <p class="archive-route-sub">{{ $trip->trip_datetime?->format('Y-m-d H:i') ?: '—' }} &bull; {{ $trip->billingCycle?->month_key ?: '—' }}</p>
                                </div>
                                <span class="archive-chip mode">{{ $hasReturn ? 'Two-way' : 'One-way' }}</span>
                            </div>
                            <div class="archive-trip-grid">
                                <div class="archive-line"><span>Driver</span><strong>{{ $trip->driver?->name ?: '—' }}</strong></div>
                                <div class="archive-line"><span>Pickup</span><strong>{{ $pickupName }}</strong></div>
                                <div class="archive-line"><span>Destination</span><strong>{{ $destinationName }}</strong></div>
                                <div class="archive-line"><span>Total Fare</span><strong>RM {{ number_format((float) $trip->fare_total, 2) }}</strong></div>
                            </div>
                            <div>
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-sm open-archive-trip-modal"
                                    data-route-name="{{ $routeName }}"
                                    data-trip-ids="{{ $hasReturn && $trip->returnTrip ? ('#' . $trip->id . ' & #' . $trip->returnTrip->id) : ('#' . $trip->id) }}"
                                    data-datetime="{{ $trip->trip_datetime?->format('Y-m-d H:i') ?: '—' }}"
                                    data-driver="{{ $trip->driver?->name ?: '—' }}"
                                    data-pickup="{{ $pickupName }}"
                                    data-destination="{{ $destinationName }}"
                                    data-mode="{{ $hasReturn ? 'Two-way' : 'One-way' }}"
                                    data-fare="RM {{ number_format((float) (($trip->fare_total ?? 0) + ($trip->returnTrip?->fare_total ?? 0)), 2) }}"
                                    data-participants='@json($participantPayload)'
                                >
                                    <i class="fa-regular fa-eye"></i> View Details
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div style="margin-top:14px;">{{ $archivedTrips->appends(request()->query())->links() }}</div>
            @endif
        </section>
    </div>

    {{-- Trip Detail Modal --}}
    <div class="archive-modal" id="archiveTripModal" aria-hidden="true">
        <div class="archive-modal-card">
            <div class="archive-modal-head">
                <h3 class="archive-modal-title">Archived Trip Details</h3>
                <button type="button" class="archive-modal-close" id="archiveTripModalClose"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="archive-modal-grid">
                <div class="archive-modal-line">
                    <span class="archive-modal-label">Trip ID</span>
                    <span class="archive-modal-value" id="archiveTripIds">-</span>
                </div>
                <div class="archive-modal-line">
                    <span class="archive-modal-label">Route</span>
                    <span class="archive-modal-value" id="archiveTripRouteName">-</span>
                </div>
                <div class="archive-modal-line">
                    <span class="archive-modal-label">Date &amp; Time</span>
                    <span class="archive-modal-value" id="archiveTripDatetime">-</span>
                </div>
                <div class="archive-modal-line">
                    <span class="archive-modal-label">Driver</span>
                    <span class="archive-modal-value" id="archiveTripDriver">-</span>
                </div>
                <div class="archive-point-grid">
                    <div class="archive-point-card pickup">
                        <span class="archive-point-label"><i class="fa-solid fa-location-dot"></i> Pickup Point</span>
                        <span class="archive-point-value" id="archiveTripPickup">-</span>
                    </div>
                    <div class="archive-point-card destination">
                        <span class="archive-point-label"><i class="fa-solid fa-flag-checkered"></i> Destination Point</span>
                        <span class="archive-point-value" id="archiveTripDestination">-</span>
                    </div>
                </div>
                <div class="archive-modal-line">
                    <span class="archive-modal-label">Trip Type</span>
                    <span class="archive-modal-value" id="archiveTripMode">-</span>
                </div>
                <div class="archive-modal-line">
                    <span class="archive-modal-label">Combined Fare</span>
                    <span class="archive-modal-value" id="archiveTripFare">-</span>
                </div>
                <div class="archive-modal-line">
                    <span class="archive-modal-label">Passengers</span>
                    <div class="archive-passenger-list" id="archiveTripPassengers"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('archiveTripModal');
            const closeBtn = document.getElementById('archiveTripModalClose');
            const buttons = document.querySelectorAll('.open-archive-trip-modal');
            if (!modal || !closeBtn || buttons.length === 0) return;
            if (modal.parentElement !== document.body) document.body.appendChild(modal);

            const tripIdsEl = document.getElementById('archiveTripIds');
            const routeNameEl = document.getElementById('archiveTripRouteName');
            const datetimeEl = document.getElementById('archiveTripDatetime');
            const driverEl = document.getElementById('archiveTripDriver');
            const pickupEl = document.getElementById('archiveTripPickup');
            const destinationEl = document.getElementById('archiveTripDestination');
            const modeEl = document.getElementById('archiveTripMode');
            const fareEl = document.getElementById('archiveTripFare');
            const passengersEl = document.getElementById('archiveTripPassengers');

            const closeModal = () => {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            };

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    if (tripIdsEl) tripIdsEl.textContent = button.dataset.tripIds || '-';
                    if (routeNameEl) routeNameEl.textContent = button.dataset.routeName || '-';
                    if (datetimeEl) datetimeEl.textContent = button.dataset.datetime || '-';
                    if (driverEl) driverEl.textContent = button.dataset.driver || '-';
                    if (pickupEl) pickupEl.textContent = button.dataset.pickup || '-';
                    if (destinationEl) destinationEl.textContent = button.dataset.destination || '-';
                    if (modeEl) modeEl.textContent = button.dataset.mode || '-';
                    if (fareEl) fareEl.textContent = button.dataset.fare || '-';

                    let participants = [];
                    try {
                        participants = JSON.parse(button.dataset.participants || '[]');
                    } catch (_error) {
                        participants = [];
                    }

                    if (passengersEl) {
                        passengersEl.innerHTML = participants.length
                            ? participants.map((item) => `<div class="archive-passenger-item"><div><div class="archive-passenger-name">${item.name || '-'}</div><div class="archive-passenger-email">${item.email || '-'}</div></div></div>`).join('')
                            : '<div class="archive-passenger-email">No passenger records found.</div>';
                    }

                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                });
            });

            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
        })();
    </script>
@endsection
