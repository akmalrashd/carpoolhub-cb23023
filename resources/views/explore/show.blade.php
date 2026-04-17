@extends('layouts.app')

@section('content')
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
            ? 'Include Driver'
            : 'Exclude Driver';
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

        .trip-rollup-grid { display: grid; gap: 8px; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .trip-rollup-item { border: 1px solid #dbe2ea; border-radius: 12px; background: #fff; padding: 10px; display: grid; gap: 2px; }
        .trip-rollup-title { font-size: 12px; color: #64748b; font-weight: 700; }
        .trip-rollup-value { font-size: 18px; color: #0f172a; font-weight: 700; }

        .trip-show-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; align-items: center; }
        .trip-show-btn { border: 1px solid #dbe2ea; border-radius: 10px; padding: 8px 12px; font-size: 13px; font-weight: 700; text-decoration: none; background: #fff; color: #0f172a; cursor: pointer; }
        .trip-show-btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
        .trip-show-btn.disabled { background: #f1f5f9; border-color: #e2e8f0; color: #94a3b8; pointer-events: none; }
        .trip-show-btn.warning { background: #fefce8; border-color: #fde68a; color: #854d0e; }
        .trip-show-btn.success { background: #f0fdf4; border-color: #86efac; color: #166534; }
        .request-form { display: grid; gap: 8px; width: min(420px, 100%); }
        .request-input { border: 1px solid #dbe2ea; border-radius: 10px; background: #f8fafc; color: #0f172a; padding: 9px 10px; font-size: 13px; }
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

        @media (min-width: 768px) {
            .trip-show-card { padding: 16px; }
            .trip-point-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .trip-rollup-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
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
                        <span class="trip-modal-value">Two Way</span>
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
                        <span class="trip-passenger-count">{{ $passengerCount }} passenger{{ $passengerCount === 1 ? '' : 's' }}</span>
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
                            <span class="trip-passenger-email">No passenger records found for this trip.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="trip-show-card">
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

            <div class="trip-show-actions">
                <a href="{{ route('explore.index') }}" class="trip-show-btn">Back</a>
                @if($isJoined)
                    <span class="trip-show-btn success disabled">Joined</span>
                @elseif($myRequest && $myRequest->status === 'pending')
                    <span class="trip-show-btn warning disabled">Request Sent</span>
                    <form method="POST" action="{{ route('explore.join-requests.cancel', $myRequest) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="trip-show-btn">Cancel Request</button>
                    </form>
                @elseif($isFull || ! $trip->is_open_for_request)
                    <span class="trip-show-btn disabled">Not Available</span>
                @else
                    <form method="POST" action="{{ route('explore.request-join', $trip) }}" class="request-form">
                        @csrf
                        <input class="request-input" type="text" name="request_note" placeholder="Request note (optional)">
                        <button type="submit" class="trip-show-btn primary">Request to Join</button>
                    </form>
                @endif
            </div>

            <div class="trip-contact-actions">
                @php
                    $waUrl = ($canViewDriverWhatsapp ?? false) ? $trip->driver?->whatsapp_url : null;
                    $emailUrl = ($canViewDriverEmail ?? false) && $trip->driver?->email
                        ? ('mailto:' . $trip->driver->email)
                        : null;
                @endphp
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
    </div>
@endsection

