@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/wallet.css') }}?v={{ filemtime(public_path('css/wallet.css')) }}">
@endpush

@php
    $user = auth()->user();
    $hasBankDetails = $user->payment_bank_name && $user->payment_account_name && $user->payment_account_number;
@endphp

<div class="wallet-page">
    <div>
        <p class="wallet-eyebrow">Driver</p>
        <h1 class="wallet-title">Wallet</h1>
        <p class="wallet-sub">Earnings from trip payments made via ToyyibPay land here — withdraw anytime to your bank account.</p>
    </div>

    <div class="wallet-balance-card">
        <div class="wallet-balance-card-glow"></div>
        <div class="wallet-balance-top">
            <div class="wallet-balance-label">Available Balance</div>
            <div class="wallet-balance-amount">RM {{ number_format($summary['balance'], 2) }}</div>
        </div>
        <div class="wallet-balance-stats">
            <div class="wallet-balance-stat">
                <span>Lifetime earned</span>
                <strong>RM {{ number_format($summary['lifetime_credited'], 2) }}</strong>
            </div>
            <div class="wallet-balance-stat">
                <span>Pending withdrawal</span>
                <strong>RM {{ number_format($summary['pending_withdrawals'], 2) }}</strong>
            </div>
        </div>
        <button type="button" class="wallet-withdraw-btn" id="walletWithdrawBtn">
            <i class="fa-solid fa-money-bill-transfer"></i> Withdraw
        </button>
    </div>

    <div class="wallet-section">
        <h3 class="wallet-section-title">My Withdrawal Requests</h3>
        @forelse($withdrawals as $withdrawal)
            <div class="wallet-withdrawal-row">
                <div class="wallet-withdrawal-main">
                    <div class="wallet-withdrawal-amount">RM {{ number_format($withdrawal->amount, 2) }}</div>
                    <div class="wallet-withdrawal-date">{{ $withdrawal->created_at->format('d M Y, H:i') }}</div>
                    @if($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)
                        <div class="wallet-withdrawal-reason"><i class="fa-solid fa-circle-info"></i> {{ $withdrawal->rejection_reason }}</div>
                    @endif
                </div>
                <span class="wallet-status-pill status-{{ $withdrawal->status }}">{{ ucfirst($withdrawal->status) }}</span>
            </div>
        @empty
            <p class="wallet-empty">No withdrawal requests yet.</p>
        @endforelse
        {{ $withdrawals->links() }}
    </div>

    <div class="wallet-section">
        <h3 class="wallet-section-title">Transaction History</h3>
        @forelse($transactions as $txn)
            <div class="wallet-txn-row">
                <div class="wallet-txn-icon {{ $txn->direction }}">
                    <i class="fa-solid {{ $txn->direction === 'credit' ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                </div>
                <div class="wallet-txn-main">
                    <div class="wallet-txn-desc">{{ $txn->description ?: ucfirst(str_replace('_', ' ', $txn->reason)) }}</div>
                    <div class="wallet-txn-date">{{ $txn->created_at?->format('d M Y, H:i') }}</div>
                </div>
                <div class="wallet-txn-amount {{ $txn->direction }}">{{ $txn->direction === 'credit' ? '+' : '-' }}RM {{ number_format($txn->amount, 2) }}</div>
            </div>
        @empty
            <p class="wallet-empty">No transactions yet.</p>
        @endforelse
        {{ $transactions->links() }}
    </div>
</div>

<div class="wallet-modal" id="walletWithdrawModal" aria-hidden="true">
    <div class="wallet-modal-card" role="dialog" aria-modal="true" aria-labelledby="walletWithdrawTitle">
        <div class="wallet-modal-head" id="walletWithdrawHead">
            <div class="wallet-modal-pill"></div>
            <div class="wallet-modal-top-row">
                <h3 class="wallet-modal-title" id="walletWithdrawTitle">Withdraw</h3>
                <button type="button" class="wallet-modal-close" id="walletWithdrawClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <div class="wallet-modal-body">
            <div class="wallet-modal-balance">Available: <strong>RM {{ number_format($summary['balance'], 2) }}</strong></div>

            @if(!$hasBankDetails)
                <div class="wallet-modal-warning">
                    <span class="wallet-modal-warning-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div class="wallet-modal-warning-body">
                        <p class="wallet-modal-warning-text">You need to add your bank details before requesting a withdrawal.</p>
                        <a href="{{ route('settings.index') }}#payment" class="wallet-modal-warning-link">
                            Add bank details <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('wallet.withdrawals.store') }}" id="walletWithdrawForm">
                    @csrf
                    <label class="wallet-modal-label" for="walletWithdrawAmount">Amount (RM)</label>
                    <input type="number" step="0.01" min="{{ $minWithdrawalAmount }}" id="walletWithdrawAmount" name="amount"
                        class="wallet-modal-input @error('amount') has-error @enderror"
                        placeholder="Min RM {{ number_format($minWithdrawalAmount, 2) }}" value="{{ old('amount') }}" required>
                    @error('amount')
                        <span class="wallet-modal-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                    @enderror

                    <div class="wallet-modal-destination">
                        <div class="wallet-modal-destination-label">Transfer to</div>
                        <div class="wallet-modal-destination-card">
                            <span class="wallet-modal-destination-avatar"><i class="fa-solid fa-building-columns"></i></span>
                            <div class="wallet-modal-destination-details">
                                <div class="wallet-modal-destination-bank">{{ $user->payment_bank_name }}</div>
                                <div class="wallet-modal-destination-account">{{ $user->payment_account_name }}</div>
                                <div class="wallet-modal-destination-number">{{ $user->payment_account_number }}</div>
                            </div>
                            <a href="{{ route('settings.index') }}#payment" class="wallet-modal-destination-edit" aria-label="Change bank details">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                        </div>
                    </div>

                    <button type="submit" class="wallet-modal-submit">Request Withdrawal</button>
                </form>
            @endif
        </div>
    </div>
</div>

<script src="{{ asset('js/wallet-index.js') }}?v={{ filemtime(public_path('js/wallet-index.js')) }}"></script>

@endsection
