@extends('layouts.app')

@section('content')
    <style>
        .billing-page {
            display: grid;
            gap: 12px;
        }

        .billing-hero {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .billing-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: clamp(1.6rem, 2.4vw, 2rem);
            line-height: 1.05;
            color: #0f172a;
        }

        .billing-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .billing-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #facc15;
            border-radius: 999px;
            padding: 6px 10px;
            background: #fffbeb;
            color: #92400e;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .billing-fallback-btn {
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #ffffff;
            color: #0f172a;
            padding: 8px 11px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            min-height: 42px;
        }

        .billing-fallback-btn:hover {
            background: #f8fafc;
            transform: translateY(-1px);
        }

        .billing-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
        }

        .billing-open-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
        }

        .billing-open-title {
            margin: 0;
            color: #0f172a;
            font-size: 22px;
            font-family: Poppins, sans-serif;
        }

        .billing-open-range {
            margin-top: 6px;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .billing-open-note {
            margin-top: 6px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.4;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .billing-close-btn {
            border: 1px solid #eab308;
            border-radius: 10px;
            background: #facc15;
            color: #0f172a;
            padding: 9px 12px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            min-height: 42px;
        }

        .billing-close-btn:hover {
            filter: brightness(0.98);
            transform: translateY(-1px);
        }

        .billing-action-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            align-items: stretch;
            width: 100%;
            max-width: 640px;
        }

        .billing-summary-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 12px;
        }

        .billing-kpi {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            padding: 10px;
            background: #f8fafc;
        }

        .billing-kpi-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .billing-kpi-value {
            margin-top: 3px;
            font-size: 21px;
            color: #0f172a;
            font-weight: 800;
            line-height: 1.05;
        }

        .billing-kpi-value.warning {
            color: #b45309;
        }

        .billing-kpi-value.success {
            color: #15803d;
        }

        .billing-history-head {
            margin: 0 0 10px;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .billing-table-wrap {
            overflow-x: auto;
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
        }

        .billing-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        .billing-table th {
            text-align: left;
            padding: 10px;
            font-size: 11px;
            color: #64748b;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
            background: #f8fafc;
            border-bottom: 1px solid #dbe2ea;
            white-space: nowrap;
        }

        .billing-table td {
            padding: 10px;
            color: #0f172a;
            border-bottom: 1px solid #eef2f7;
            font-size: 13px;
            vertical-align: middle;
        }

        .billing-table td.numeric,
        .billing-table th.numeric {
            text-align: right;
        }

        .billing-status {
            font-weight: 700;
        }

        .billing-status.open {
            color: #16a34a;
        }

        .billing-status.closed {
            color: #64748b;
        }

        .billing-mode {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
            border: 1px solid #dbe2ea;
        }

        .billing-mode.manual {
            border-color: #facc15;
            background: #fffbeb;
            color: #92400e;
        }

        .billing-mode.auto {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .billing-empty {
            color: #64748b;
            font-size: 13px;
        }

        @media (min-width: 860px) {
            .billing-summary-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .billing-history-mobile {
            display: grid;
            gap: 8px;
        }

        .billing-history-item {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            display: grid;
            gap: 8px;
        }

        .billing-history-item-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .billing-history-item-month {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        .billing-history-item-range {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .billing-history-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
        }

        .billing-history-stat {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px;
            background: #f8fafc;
        }

        .billing-history-stat-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #64748b;
            font-weight: 700;
        }

        .billing-history-stat-value {
            margin-top: 2px;
            color: #0f172a;
            font-weight: 800;
            font-size: 14px;
            line-height: 1.1;
        }

        .billing-history-meta {
            display: grid;
            gap: 3px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 720px) {
            .billing-hero {
                padding: 12px;
            }

            .billing-title {
                font-size: 1.8rem;
            }

            .billing-subtitle {
                font-size: 13px;
            }

            .billing-badge {
                width: 100%;
                justify-content: center;
            }

            .billing-fallback-btn {
                width: 100%;
                justify-content: center;
            }

            .billing-action-row {
                grid-template-columns: 1fr;
                max-width: 100%;
            }

            .billing-card {
                padding: 12px;
            }

            .billing-open-title {
                font-size: 18px;
            }

            .billing-summary-grid {
                grid-template-columns: 1fr;
            }

            .billing-table-wrap {
                display: none;
            }
        }

        @media (min-width: 721px) {
            .billing-history-mobile {
                display: none;
            }
        }
    </style>

    <div class="billing-page">
        <section class="billing-hero">
            <div>
                <h1 class="billing-title">Monthly Summary</h1>
                <p class="billing-subtitle">Billing cycle and archive overview.</p>
            </div>
            <span class="billing-badge"><i class="fa-solid fa-rotate"></i> Auto-close daily at 00:05</span>
        </section>

        @if($errors->any())
            <div style="padding:10px 12px; border-radius:10px; border:1px solid #fecaca; background:#fef2f2; color:#991b1b;">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="billing-card">
            <div class="billing-open-head">
                <div>
                    <h2 class="billing-open-title">Open Cycle: {{ $openCycle->month_key }}</h2>
                    <div class="billing-open-range">{{ $openCycle->start_date?->format('Y-m-d') }} to {{ $openCycle->end_date?->format('Y-m-d') }}</div>
                    <div class="billing-open-note"><i class="fa-solid fa-circle-info"></i> Manual close is optional. Overdue cycle will be closed automatically.</div>
                </div>

                @if(auth()->user()->role === 'admin' && $openCycle->status === 'open')
                    <div class="billing-action-row">
                        <form method="POST" action="{{ route('billing-cycles.close', $openCycle) }}" onsubmit="return confirm('Close this cycle and archive its trips?');" style="margin:0;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="billing-close-btn"><i class="fa-solid fa-box-archive"></i> Close & Archive</button>
                        </form>
                        @if(($canUndoLatestClose ?? false) && isset($latestClosedCycle))
                            <form method="POST"
                                  action="{{ route('billing-cycles.undo-last-close') }}"
                                  onsubmit="return confirm('Undo last archive fallback for cycle {{ $latestClosedCycle->month_key }}?');"
                                  style="margin:0;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="billing-fallback-btn" title="Fallback cycle {{ $latestClosedCycle->month_key }}">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    Fallback Last Archived
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <div class="billing-summary-grid">
                <article class="billing-kpi">
                    <div class="billing-kpi-label">Trips</div>
                    <div class="billing-kpi-value">{{ (int) ($openSummary['trip_count'] ?? 0) }}</div>
                </article>
                <article class="billing-kpi">
                    <div class="billing-kpi-label">Fare Total</div>
                    <div class="billing-kpi-value">RM {{ number_format((float) ($openSummary['fare_total'] ?? 0), 2) }}</div>
                </article>
                <article class="billing-kpi">
                    <div class="billing-kpi-label">Unpaid + Pending</div>
                    <div class="billing-kpi-value warning">RM {{ number_format((float) ($openSummary['unpaid_pending_total'] ?? 0), 2) }}</div>
                </article>
                <article class="billing-kpi">
                    <div class="billing-kpi-label">Paid Total</div>
                    <div class="billing-kpi-value success">RM {{ number_format((float) ($openSummary['paid_total'] ?? 0), 2) }}</div>
                </article>
            </div>
        </section>

        <section class="billing-card">
            <h2 class="billing-history-head">Cycle History</h2>

            <div class="billing-table-wrap">
                <table class="billing-table">
                    <thead>
                    <tr>
                        <th>Month</th>
                        <th>Date Range</th>
                        <th>Status</th>
                        <th>Closed By</th>
                        <th>Close Mode</th>
                        <th class="numeric">Trips</th>
                        <th class="numeric">Fare Total</th>
                        <th class="numeric">Pending/Unpaid</th>
                        <th class="numeric">Paid</th>
                        <th>Closed At</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($cycles as $cycle)
                        @php($cycleSummary = $summaries[$cycle->id] ?? ['trip_count' => 0, 'fare_total' => 0, 'unpaid_pending_total' => 0, 'paid_total' => 0])
                        <tr>
                            <td>{{ $cycle->month_key }}</td>
                            <td>{{ $cycle->start_date?->format('Y-m-d') }} to {{ $cycle->end_date?->format('Y-m-d') }}</td>
                            <td>
                                <span class="billing-status {{ $cycle->status === 'open' ? 'open' : 'closed' }}">{{ ucfirst($cycle->status) }}</span>
                            </td>
                            <td>{{ $cycle->closer?->name ?? ($cycle->status === 'closed' ? 'System' : '-') }}</td>
                            <td>
                                @if($cycle->status !== 'closed')
                                    <span class="billing-empty">-</span>
                                @elseif($cycle->closed_by)
                                    <span class="billing-mode manual">Manual</span>
                                @else
                                    <span class="billing-mode auto">Auto</span>
                                @endif
                            </td>
                            <td class="numeric">{{ (int) $cycleSummary['trip_count'] }}</td>
                            <td class="numeric">RM {{ number_format((float) $cycleSummary['fare_total'], 2) }}</td>
                            <td class="numeric">RM {{ number_format((float) $cycleSummary['unpaid_pending_total'], 2) }}</td>
                            <td class="numeric">RM {{ number_format((float) $cycleSummary['paid_total'], 2) }}</td>
                            <td>{{ $cycle->closed_at?->format('Y-m-d H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="billing-empty">No cycles found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="billing-history-mobile">
                @forelse($cycles as $cycle)
                    @php($cycleSummary = $summaries[$cycle->id] ?? ['trip_count' => 0, 'fare_total' => 0, 'unpaid_pending_total' => 0, 'paid_total' => 0])
                    <article class="billing-history-item">
                        <div class="billing-history-item-top">
                            <div>
                                <div class="billing-history-item-month">{{ $cycle->month_key }}</div>
                                <div class="billing-history-item-range">{{ $cycle->start_date?->format('Y-m-d') }} to {{ $cycle->end_date?->format('Y-m-d') }}</div>
                            </div>
                            <span class="billing-status {{ $cycle->status === 'open' ? 'open' : 'closed' }}">{{ ucfirst($cycle->status) }}</span>
                        </div>

                        <div>
                            @if($cycle->status !== 'closed')
                                <span class="billing-empty">Close mode: -</span>
                            @elseif($cycle->closed_by)
                                <span class="billing-mode manual">Manual</span>
                            @else
                                <span class="billing-mode auto">Auto</span>
                            @endif
                        </div>

                        <div class="billing-history-stats">
                            <div class="billing-history-stat">
                                <div class="billing-history-stat-label">Trips</div>
                                <div class="billing-history-stat-value">{{ (int) $cycleSummary['trip_count'] }}</div>
                            </div>
                            <div class="billing-history-stat">
                                <div class="billing-history-stat-label">Fare Total</div>
                                <div class="billing-history-stat-value">RM {{ number_format((float) $cycleSummary['fare_total'], 2) }}</div>
                            </div>
                            <div class="billing-history-stat">
                                <div class="billing-history-stat-label">Pending/Unpaid</div>
                                <div class="billing-history-stat-value">RM {{ number_format((float) $cycleSummary['unpaid_pending_total'], 2) }}</div>
                            </div>
                            <div class="billing-history-stat">
                                <div class="billing-history-stat-label">Paid</div>
                                <div class="billing-history-stat-value">RM {{ number_format((float) $cycleSummary['paid_total'], 2) }}</div>
                            </div>
                        </div>

                        <div class="billing-history-meta">
                            <div>Closed by: {{ $cycle->closer?->name ?? ($cycle->status === 'closed' ? 'System' : '-') }}</div>
                            <div>Closed at: {{ $cycle->closed_at?->format('Y-m-d H:i') ?: '-' }}</div>
                        </div>
                    </article>
                @empty
                    <div class="billing-empty">No cycles found.</div>
                @endforelse
            </div>

            <div style="margin-top:12px;">
                {{ $cycles->links() }}
            </div>
        </section>
    </div>
@endsection
