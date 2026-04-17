@extends('layouts.app')

@section('content')
    <style>
        .explore-page { display: grid; gap: 12px; }
        .explore-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 18px;
            padding: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }
        .explore-title { margin: 0; font-family: Poppins, sans-serif; font-size: 30px; color: #0f172a; line-height: 1.05; }
        .explore-subtitle { margin: 6px 0 0; color: #64748b; font-size: 14px; }

        .explore-search-cta {
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #f8fafc;
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

        .explore-grid { display: grid; gap: 8px; }
        .explore-grid.domino-float .explore-item {
            animation-name: exploreDominoFloat;
            animation-duration: calc(var(--domino-count, 8) * 0.28s + 2.8s);
            animation-timing-function: ease-in-out;
            animation-iteration-count: infinite;
            animation-delay: calc(var(--domino-index, 0) * 0.28s);
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
            display: grid;
            gap: 9px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .open-explore-card { cursor: pointer; }
        .explore-item:hover {
            transform: translateY(-2px);
            border-color: #cbd5e1;
            box-shadow: 0 12px 20px rgba(15, 23, 42, 0.08);
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
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            min-width: 0;
        }
        .explore-item-head > div { min-width: 0; width: 100%; flex: 1 1 auto; }
        .explore-route {
            margin: 0;
            font-size: 17px;
            line-height: 1.25;
            color: #0f172a;
            font-weight: 700;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
            margin: 4px 0 0;
            color: #64748b;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            min-width: 0;
        }
        .explore-meta-inline-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-width: 0;
            white-space: nowrap;
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
            border-radius: 10px;
            background: #f8fafc;
            padding: 8px 10px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 13px;
            color: #334155;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
            min-width: 0;
        }
        .explore-detail span { min-width: 0; }
        .explore-detail strong {
            color: #0f172a;
            max-width: 52%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: right;
        }
        .explore-actions { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
        .explore-actions form { margin: 0; }

        .explore-btn {
            border: 1px solid #dbe2ea;
            border-radius: 9px;
            background: #fff;
            color: #0f172a;
            padding: 8px 10px;
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

        .empty-state { border: 1px dashed #dbe2ea; border-radius: 12px; background: #f8fafc; padding: 14px; color: #64748b; font-size: 14px; text-align: center; }

        @media (min-width: 768px) {
            .explore-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .explore-suggested-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 640px) {
            .explore-item-head {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
            }
            .explore-item-head > div { width: auto; }
            .explore-status-stack {
                width: 128px;
                flex: 0 0 128px;
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
        $hasSearchSummary = $searchDestination !== '' || $searchPickup !== '' || $searchDate !== '' || $searchSeats !== '' || (request()->filled('center_lat') && request()->filled('center_lng'));

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
                <span class="explore-search-right">Search <i class="fa-solid fa-arrow-right"></i></span>
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

        <section class="explore-card">
            <div class="explore-section-head">
                <h2 class="explore-section-title">Upcoming Public Trips</h2>
                <a href="{{ route('explore.search', $searchEditQuery) }}" class="explore-section-link">Advanced search</a>
            </div>
            <p class="explore-subtitle" style="margin:0 0 10px;">AI matching ranks route fit, usual timing, seat availability, fare, and connection trust.</p>

            @if($trips->isEmpty())
                <div class="empty-state">No public trips available right now.</div>
            @else
                <div class="explore-grid domino-float" style="--domino-count: {{ max($trips->count(), 1) }};">
                    @foreach($trips as $trip)
                        @php
                            $routeName = $trip->savedRoute?->route_name ?: (($trip->pickup_name ?? 'Pickup') . ' -> ' . ($trip->destination_name ?? 'Destination'));
                            $pickupText = $trip->pickup_name ?? 'Pickup';
                            $destinationText = $trip->destination_name ?? 'Destination';
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
                                    <div class="explore-meta-inline">
                                        @if($aiRecommendation)
                                            <span class="explore-meta-inline-item">
                                                <i class="fa-solid fa-sparkles"></i>
                                                <span>{{ number_format((float) ($aiRecommendation['match_score'] ?? 0), 0) }}% Match</span>
                                            </span>
                                        @endif
                                        <span class="explore-meta-inline-item">
                                            <i class="fa-solid fa-user"></i>
                                            <span>{{ $trip->driver?->name ?: '-' }}</span>
                                        </span>
                                        <span class="explore-meta-inline-item">
                                            <i class="fa-solid fa-route"></i>
                                            <span>{{ $tripTypeText }}</span>
                                        </span>
                                        <span class="explore-meta-inline-item">
                                            <i class="{{ $visibilityIcon }}"></i>
                                            <span>{{ $visibilityText }}</span>
                                        </span>
                                    </div>
                                    <p class="explore-meta-line" title="{{ $pickupText }}">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <span class="meta-label">Pickup:</span>
                                        <span class="meta-value">{{ $pickupText }}</span>
                                    </p>
                                    <p class="explore-meta-line" title="{{ $destinationText }}">
                                        <i class="fa-solid fa-flag-checkered"></i>
                                        <span class="meta-label">Destination:</span>
                                        <span class="meta-value">{{ $destinationText }}</span>
                                    </p>
                                </div>
                                <div class="explore-status-stack">
                                    @if($isRecommended)
                                        <span class="explore-chip chip-ai">Recommended</span>
                                    @endif
                                    <span class="explore-chip {{ $chipClass }}">{{ $stateText }}</span>
                                </div>
                            </div>
                            @if(!empty($aiRecommendation['explanations']))
                                <div class="explore-ai-reasons">
                                    @foreach(array_slice((array) $aiRecommendation['explanations'], 0, 3) as $reason)
                                        <span class="explore-ai-reason">{{ $reason }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="explore-detail">
                                <span>Date & Time</span>
                                <strong>{{ $trip->trip_datetime?->format('Y-m-d H:i') ?: '-' }}</strong>
                            </div>
                            <div class="explore-detail">
                                <span>Available seats</span>
                                <strong>{{ $availableSeats !== null ? ($availableSeats . ' / ' . (int) $trip->seat_limit) : 'Open' }}</strong>
                            </div>
                            <div class="explore-detail">
                                <span>Fare / person</span>
                                <strong>RM {{ number_format((float) $trip->fare_per_person, 2) }}</strong>
                            </div>
                            @if($trip->public_note)
                                <div class="explore-detail">
                                    <span>Note</span>
                                    <strong>{{ $trip->public_note }}</strong>
                                </div>
                            @endif
                            <div class="explore-actions">
                                <a href="{{ route('explore.show', $trip) }}" class="explore-btn">View Details</a>
                                @if($isJoined)
                                    <span class="explore-btn success disabled">Joined</span>
                                @elseif($myRequest && $myRequest->status === 'pending')
                                    <span class="explore-btn warning disabled">Request Sent</span>
                                @elseif($availableSeats !== null && $availableSeats <= 0)
                                    <span class="explore-btn disabled">Full</span>
                                @else
                                    <form method="POST" action="{{ route('explore.request-join', $trip) }}">
                                        @csrf
                                        <button type="submit" class="explore-btn primary">Request to Join</button>
                                    </form>
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

