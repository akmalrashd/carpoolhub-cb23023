@if($requests->isEmpty())
    <div class="empty-state">No join requests yet.</div>
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
                $requesterPhotoUrl = $requestRow->user?->profile_photo
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($requestRow->user->profile_photo)
                    : null;
                $requesterInitial = strtoupper(substr(trim($requesterName) ?: 'U', 0, 1));
            @endphp
            <article class="request-item">
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
                            <div class="request-meta">Requested {{ $requestRow->created_at?->diffForHumans() }}</div>
                        </div>
                    </div>
                    <span class="request-chip {{ $chipClass }}">{{ ucfirst($requestRow->status) }}</span>
                </div>
                <div class="request-reliability">
                    <div class="request-reliability-top">
                        <span class="request-reliability-title-group">
                            <span class="request-reliability-title">AI Passenger Risk</span>
                            <button type="button" class="rating-info-btn open-rating-info-btn" aria-label="How AI risk scoring works">
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
                                Reliability: {{ number_format((float) $aiRisk['payment_reliability_score'], 1) }}/5.0
                            </span>
                            <span class="request-reliability-item">
                                <i class="fas fa-file-invoice-dollar"></i>
                                Active due cases: {{ (int) $reliability['unpaid_cases'] }}
                            </span>
                            <span class="request-reliability-item">
                                <i class="fas fa-clock"></i>
                                Cancelled requests: {{ (int) ($aiRisk['features']['cancelled_request_count'] ?? 0) }}
                            </span>
                            <span class="request-reliability-item">
                                <i class="fas fa-user-clock"></i>
                                Absent history: {{ (int) ($aiRisk['features']['attendance_absent_count'] ?? 0) }}
                            </span>
                        </span>
                    </div>
                    @if(!empty($aiRisk['reasons']))
                        <div class="request-note">
                            AI reasons: {{ implode(' ', array_slice((array) $aiRisk['reasons'], 0, 3)) }}
                        </div>
                    @endif
                </div>
                @if($requestRow->request_note)
                    <div class="request-note">Passenger note: {{ $requestRow->request_note }}</div>
                @endif
                @if($requestRow->response_note)
                    <div class="request-note">Response note: {{ $requestRow->response_note }}</div>
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
