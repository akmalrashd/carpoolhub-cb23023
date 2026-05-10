@if($requests->isEmpty())
    <div class="empty-state">
        <i class="fa-solid fa-inbox empty-state-icon"></i>
        <p class="empty-state-title">Tiada permohonan lagi</p>
        <p class="empty-state-copy">Belum ada penumpang yang menghantar permohonan untuk trip ini.</p>
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
                $requesterPhotoUrl = $requestRow->user?->profile_photo
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($requestRow->user->profile_photo)
                    : null;
                $requesterInitial = strtoupper(substr(trim($requesterName) ?: 'U', 0, 1));
                $routePoint = $requestRow->routePoint;
                $routeFitDisplay = $routePoint && $routePoint->route_fit_score !== null
                    ? ((int) $routePoint->route_fit_score . '% sesuai')
                    : 'Semakan laluan';
                $statusLabel = match ($requestRow->status) {
                    'approved' => 'Diluluskan',
                    'rejected' => 'Ditolak',
                    'cancelled' => 'Dibatalkan',
                    default => 'Tertangguh',
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
                            <span class="request-reliability-title">Risiko Penumpang AI</span>
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
                                Permohonan dibatalkan: {{ (int) ($aiRisk['features']['cancelled_request_count'] ?? 0) }}
                            </span>
                            <span class="request-reliability-item">
                                <i class="fas fa-user-clock"></i>
                                Sejarah ketidakhadiran: {{ (int) ($aiRisk['features']['attendance_absent_count'] ?? 0) }}
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
                            $routePoint->uses_default_dropoff ? null : ($routePoint->dropoff_distance_km !== null ? ('hantar ' . number_format((float) $routePoint->dropoff_distance_km, 2) . ' km') : null),
                        ])->filter()->implode(' / ');
                    @endphp
                    <div class="request-route-point-card">
                        <div class="request-route-point-head">
                            <span class="request-route-point-title">
                                <i class="fas fa-route"></i>
                                Pilihan laluan
                            </span>
                            <span class="request-route-fit">{{ $routeFitDisplay }} · {{ $routePoint->route_fit_label ?: 'Semakan pemandu diperlukan' }}</span>
                        </div>
                        <div class="request-route-point-grid">
                            <div class="request-route-point-item">
                                <span class="request-route-point-label">Pickup</span>
                                <span class="request-route-point-value">{{ $routePoint->uses_default_pickup ? 'Titik A Lalai' : 'Pickup tersuai' }}</span>
                                <span class="request-route-point-meta">{{ $routePoint->uses_default_pickup ? 'Guna titik mula laluan pemandu' : 'Lihat pin peta bernombor' }}</span>
                            </div>
                            <div class="request-route-point-item">
                                <span class="request-route-point-label">Hantar</span>
                                <span class="request-route-point-value">{{ $routePoint->uses_default_dropoff ? 'Titik B Lalai' : 'Hantar tersuai' }}</span>
                                <span class="request-route-point-meta">{{ $routePoint->uses_default_dropoff ? 'Guna titik akhir laluan pemandu' : 'Lihat pin peta bernombor' }}</span>
                            </div>
                            <div class="request-route-point-item">
                                <span class="request-route-point-label">Tambang dicadangkan</span>
                                <span class="request-route-point-value">{{ $routePoint->fare_override_amount !== null ? ('RM ' . number_format((float) $routePoint->fare_override_amount, 2)) : 'Agihan biasa' }}</span>
                            <span class="request-route-point-meta">
                                {{ $routePoint->detour_distance_km !== null ? ('Sisihan ' . number_format((float) $routePoint->detour_distance_km, 2) . ' km dari laluan asal') : 'Pratonton sahaja, semakan pemandu sebelum lulus' }}
                                {{ $distanceSummary ? (' - ' . $distanceSummary . ' dari laluan') : '' }}
                            </span>
                            </div>
                            <div class="request-route-point-item route-check-redundant">
                                <span class="request-route-point-label">Semakan laluan</span>
                                <span class="request-route-point-value">
                                    @if($routePoint->pickup_distance_km !== null || $routePoint->dropoff_distance_km !== null)
                                        Pickup {{ $routePoint->pickup_distance_km ?? '-' }} km · Hantar {{ $routePoint->dropoff_distance_km ?? '-' }} km
                                    @else
                                        Tiada semakan koordinat
                                    @endif
                                </span>
                                <span class="request-route-point-meta">Masa boleh disahkan selepas kelulusan</span>
                            </div>
                        </div>
                    </div>
                @endif
                @if($requestRow->request_note)
                    <div class="request-note">Nota penumpang: {{ $requestRow->request_note }}</div>
                @endif
                @if($requestRow->response_note)
                    <div class="request-note">Nota respons: {{ $requestRow->response_note }}</div>
                @endif

                @if($requestRow->status === 'pending')
                    <div class="request-actions">
                        <button
                            type="button"
                            class="btn success open-approve-btn"
                            data-action="{{ route('trips.join-requests.respond', $requestRow) }}"
                            data-passenger="{{ $requesterName }}"
                            data-trip="#{{ $trip->id }}"
                        ><i class="fas fa-solid fa-check"></i>Luluskan</button>
                        <button
                            type="button"
                            class="btn danger open-reject-btn"
                            data-action="{{ route('trips.join-requests.respond', $requestRow) }}"
                            data-passenger="{{ $requesterName }}"
                            data-trip="#{{ $trip->id }}"
                        ><i class="fas fa-solid fa-xmark"></i>Tolak</button>
                    </div>
                @endif
            </article>
        @endforeach
    </div>
@endif
