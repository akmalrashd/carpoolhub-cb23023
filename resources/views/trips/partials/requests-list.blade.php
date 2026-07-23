@if($requests->isEmpty())
    <div class="empty-state">
        <i class="fa-solid fa-inbox empty-state-icon"></i>
        <p class="empty-state-title">No requests yet</p>
        <p class="empty-state-copy">No passengers have submitted requests for this trip yet.</p>
    </div>
@else
    <div class="request-list">
        @foreach($requests as $requestRow)
            @php
                $chipClass = match ($requestRow->status) {
                    'approved' => 'chip-approved',
                    'rejected', 'cancelled' => 'chip-rejected',
                    default => 'chip-pending',
                };
                $reliability = $reliabilityMap[$requestRow->user_id] ?? [
                    'score' => 5.0,
                    'label' => 'Excellent',
                    'unpaid_cases' => 0,
                    'outstanding_amount' => 0.0,
                    'oldest_overdue_days' => 0,
                ];
                $aiRisk = $aiRiskMap[$requestRow->user_id] ?? [
                    'score' => 70,
                    'risk_level' => 'Moderate Risk',
                    'payment_reliability_score' => 5.0,
                    'reasons' => ['No AI risk explanation available yet.'],
                    'features' => [
                        'cancelled_request_count' => 0,
                        'attendance_absent_count' => 0,
                    ],
                ];
                $riskClass = match (strtolower((string) $aiRisk['risk_level'])) {
                    'low risk' => 'risk-excellent',
                    'moderate risk' => 'risk-good',
                    'high risk' => 'risk-risky',
                    'very high risk' => 'risk-high-risk',
                    'moderate' => 'risk-moderate',
                    'risky' => 'risk-risky',
                    'high risk' => 'risk-high-risk',
                    default => 'risk-moderate',
                };
                $requesterName = (string) ($requestRow->user?->name ?? '-');
                $requesterPhotoUrl = $requestRow->user?->profile_photo_url;
                $requesterInitial = strtoupper(substr(trim($requesterName) ?: 'U', 0, 1));
                $routePoint = $requestRow->routePoint;
                $routeFitDisplay = $routePoint && $routePoint->route_fit_score !== null
                    ? ((int) $routePoint->route_fit_score . '% sesuai')
                    : 'Route review';
                $statusLabel = match ($requestRow->status) {
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'cancelled' => 'Cancelled',
                    default => 'Pending',
                };
            @endphp
            <article class="request-item" data-request-status="{{ strtolower((string) $requestRow->status) }}" data-request-search="{{ strtolower(trim($requesterName . ' ' . ($requestRow->user?->email ?? '') . ' ' . ($requestRow->status ?? '') . ' ' . ($requestRow->request_note ?? '') . ' ' . ($requestRow->response_note ?? '') . ' ' . ($routePoint?->route_fit_label ?? '') . ' ' . ($routePoint?->pickup_name ?? '') . ' ' . ($routePoint?->dropoff_name ?? ''))) }}">
                <div class="request-head">
                    <div class="request-user">
                        <span class="request-avatar">
                            @if($requesterPhotoUrl)
                                <img src="{{ $requesterPhotoUrl }}" alt="{{ $requesterName }}">
                            @else
                                {{ $requesterInitial }}
                            @endif
                        </span>
                        <div class="request-user-meta">
                            <h2 class="request-name">{{ $requesterName }}</h2>
                            <div class="request-meta">Dimohon {{ $requestRow->created_at?->diffForHumans() }}</div>
                        </div>
                    </div>
                    <span class="request-chip {{ $chipClass }}">{{ $statusLabel }}</span>
                </div>
                <div class="request-reliability">
                    <div class="request-reliability-top">
                        <span class="request-reliability-title-group">
                            <span class="request-reliability-title">AI Passenger Risk</span>
                            <button type="button" class="rating-info-btn open-rating-info-btn" aria-label="Cara pemarkahan risiko AI berfungsi">
                                <i class="fas fa-circle-info"></i>
                            </button>
                        </span>
                        <span class="request-reliability-label {{ $riskClass }}">{{ $aiRisk['risk_level'] }}</span>
                    </div>
                    <div class="request-reliability-score">
                        <span class="value">{{ (int) $aiRisk['score'] }}</span>/100
                    </div>
                    <div class="request-reliability-meta">
                        <span class="request-reliability-items">
                            <span class="request-reliability-item">
                                <i class="fas fa-shield-heart"></i>
                                Kebolehpercayaan: {{ number_format((float) $aiRisk['payment_reliability_score'], 1) }}/5.0
                            </span>
                            <span class="request-reliability-item">
                                <i class="fas fa-file-invoice-dollar"></i>
                                Kes aktif tertunggak: {{ (int) $reliability['unpaid_cases'] }}
                            </span>
                            <span class="request-reliability-item">
                                <i class="fas fa-clock"></i>
                                Cancelled requests: {{ (int) ($aiRisk['features']['cancelled_request_count'] ?? 0) }}
                            </span>
                            <span class="request-reliability-item">
                                <i class="fas fa-user-clock"></i>
                                Absence history: {{ (int) ($aiRisk['features']['attendance_absent_count'] ?? 0) }}
                            </span>
                        </span>
                    </div>
                    @if(!empty($aiRisk['reasons']))
                        <div class="request-note">
                            Sebab AI: {{ implode(' ', array_slice((array) $aiRisk['reasons'], 0, 3)) }}
                        </div>
                    @endif
                </div>
                @if($routePoint)
                    @php
                        $distanceSummary = collect([
                            $routePoint->uses_default_pickup ? null : ($routePoint->pickup_distance_km !== null ? ('pickup ' . number_format((float) $routePoint->pickup_distance_km, 2) . ' km') : null),
                            $routePoint->uses_default_dropoff ? null : ($routePoint->dropoff_distance_km !== null ? ('drop-off ' . number_format((float) $routePoint->dropoff_distance_km, 2) . ' km') : null),
                        ])->filter()->implode(' / ');
                    @endphp
                    <div class="request-route-point-card">
                        <div class="request-route-point-head">
                            <span class="request-route-point-title">
                                <i class="fas fa-route"></i>
                                Route option
                            </span>
                            <span class="request-route-fit">{{ $routeFitDisplay }} · {{ $routePoint->route_fit_label ?: 'Driver review required' }}</span>
                        </div>
                        <div class="request-route-point-grid">
                            <div class="request-route-point-item">
                                <span class="request-route-point-label">Pickup</span>
                                <span class="request-route-point-value">{{ $routePoint->uses_default_pickup ? 'Default Point A' : 'Custom pickup' }}</span>
                                <span class="request-route-point-meta">{{ $routePoint->uses_default_pickup ? "Use driver's route starting point" : 'View numbered map pin' }}</span>
                            </div>
                            <div class="request-route-point-item">
                                <span class="request-route-point-label">Drop-off</span>
                                <span class="request-route-point-value">{{ $routePoint->uses_default_dropoff ? 'Default Point B' : 'Custom drop-off' }}</span>
                                <span class="request-route-point-meta">{{ $routePoint->uses_default_dropoff ? "Use driver's route ending point" : 'View numbered map pin' }}</span>
                            </div>
                            <div class="request-route-point-item">
                                <span class="request-route-point-label">Extra fee</span>
                                <span class="request-route-point-value">{{ $routePoint->extra_fee_amount !== null ? ('+ RM ' . number_format((float) $routePoint->extra_fee_amount, 2)) : 'No extra fee' }}</span>
                            <span class="request-route-point-meta">
                                {{ $routePoint->detour_distance_km !== null ? ('Detour ' . number_format((float) $routePoint->detour_distance_km, 2) . ' km from original route') : 'Preview only, driver review before approval' }}
                                {{ $distanceSummary ? (' - ' . $distanceSummary . ' from route') : '' }}
                            </span>
                            </div>
                            <div class="request-route-point-item route-check-redundant">
                                <span class="request-route-point-label">Route review</span>
                                <span class="request-route-point-value">
                                    @if($routePoint->pickup_distance_km !== null || $routePoint->dropoff_distance_km !== null)
                                        Pickup {{ $routePoint->pickup_distance_km ?? '-' }} km · Drop-off {{ $routePoint->dropoff_distance_km ?? '-' }} km
                                    @else
                                        No coordinate review
                                    @endif
                                </span>
                                <span class="request-route-point-meta">Time can be confirmed after approval</span>
                            </div>
                        </div>
                    </div>
                @endif
                @if($requestRow->request_note)
                    <div class="request-note">Passenger note: {{ $requestRow->request_note }}</div>
                @endif
                @if($requestRow->response_note)
                    <div class="request-note">Note respons: {{ $requestRow->response_note }}</div>
                @endif

                @if($requestRow->status === 'pending')
                    <div class="request-actions">
                        <button
                            type="button"
                            class="btn success open-approve-btn"
                            data-action="{{ route('trips.join-requests.respond', $requestRow) }}"
                            data-passenger="{{ $requesterName }}"
                            data-trip="#{{ $trip->id }}"
                        ><i class="fas fa-solid fa-check"></i>Approve</button>
                        <button
                            type="button"
                            class="btn danger open-reject-btn"
                            data-action="{{ route('trips.join-requests.respond', $requestRow) }}"
                            data-passenger="{{ $requesterName }}"
                            data-trip="#{{ $trip->id }}"
                        ><i class="fas fa-solid fa-xmark"></i>Reject</button>
                    </div>
                @endif
            </article>
        @endforeach
    </div>
@endif
