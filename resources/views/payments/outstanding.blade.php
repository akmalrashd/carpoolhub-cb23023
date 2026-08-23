@extends('layouts.app')

@section('content')
    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/payments.css') }}?v={{ filemtime(public_path('css/payments.css')) }}">
    @endpush

    <div class="payments-page">
        <section class="payments-page-header">
            <div class="payments-page-header-left">
                <p class="payments-eyebrow">Payments</p>
                <h1 class="payments-h1">Outstanding Summary</h1>
                <p class="payments-sub">Every unpaid or pending amount, broken down by month and who it's with — useful if you'd rather settle in one go than per trip.</p>
            </div>
            <a href="{{ route('payments.index') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Payments
            </a>
        </section>

        <section class="payments-card">
            <h2 class="payments-section-title">What You Owe</h2>
            <p class="payments-section-subtitle">Trips where you rode — outstanding until the driver confirms.</p>

            <div class="debt-summary-card">
                <div class="debt-summary-top">
                    <h3 class="debt-summary-title">Total Outstanding</h3>
                    <span class="debt-summary-total">RM {{ number_format((float) $owedByMe['total_amount'], 2) }}</span>
                </div>

                @forelse($owedByMe['months'] as $month)
                    <div class="outstanding-month-group">
                        <div class="outstanding-month-label">
                            {{ $month['month_label'] }}
                            <span class="outstanding-month-total">RM {{ number_format((float) $month['total'], 2) }}</span>
                        </div>
                        <div class="debt-list">
                            @foreach($month['rows'] as $row)
                                <div class="debt-item">
                                    <div>
                                        <div class="debt-name">{{ $row['counterparty_name'] }}</div>
                                        <div class="debt-meta">{{ $row['records'] }} trip{{ $row['records'] === 1 ? '' : 's' }}</div>
                                    </div>
                                    <div class="debt-amount">RM {{ number_format((float) $row['amount'], 2) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="debt-item">
                        <div class="debt-meta">Nothing outstanding — you're all settled up.</div>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="payments-card">
            <h2 class="payments-section-title">Owed to You</h2>
            <p class="payments-section-subtitle">Trips you drove — passengers who haven't settled yet.</p>

            <div class="debt-summary-card">
                <div class="debt-summary-top">
                    <h3 class="debt-summary-title">Total Outstanding</h3>
                    <span class="debt-summary-total">RM {{ number_format((float) $owedToMe['total_amount'], 2) }}</span>
                </div>

                @forelse($owedToMe['months'] as $month)
                    <div class="outstanding-month-group">
                        <div class="outstanding-month-label">
                            {{ $month['month_label'] }}
                            <span class="outstanding-month-total">RM {{ number_format((float) $month['total'], 2) }}</span>
                        </div>
                        <div class="debt-list">
                            @foreach($month['rows'] as $row)
                                <div class="debt-item">
                                    <div>
                                        <div class="debt-name">{{ $row['counterparty_name'] }}</div>
                                        <div class="debt-meta">{{ $row['records'] }} trip{{ $row['records'] === 1 ? '' : 's' }}</div>
                                    </div>
                                    <div class="debt-amount">RM {{ number_format((float) $row['amount'], 2) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="debt-item">
                        <div class="debt-meta">No outstanding passenger payments.</div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <style>
        .outstanding-month-group { margin-top: 14px; }
        .outstanding-month-group:first-child { margin-top: 8px; }
        .outstanding-month-label {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 12.5px; font-weight: 800; color: var(--muted);
            text-transform: uppercase; letter-spacing: .03em;
            padding: 6px 2px; border-bottom: 1px solid var(--hairline);
            margin-bottom: 4px;
        }
        .outstanding-month-total { font-family: var(--font-mono, monospace); color: var(--ink); }
    </style>
@endsection
