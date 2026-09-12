@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin-system-settings.css') }}?v={{ filemtime(public_path('css/admin-system-settings.css')) }}">
@endpush

@php
    $liveSource = $livePrices['source'] ?? 'unknown';
    $liveFields = [
        'fuel_price_ron95_budi' => ['label' => 'RON95 — BUDI subsidised', 'icon' => 'fa-gas-pump', 'live' => $livePrices['RON95']['budi'] ?? null],
        'fuel_price_ron95_market' => ['label' => 'RON95 — market rate', 'icon' => 'fa-gas-pump', 'live' => $livePrices['RON95']['market'] ?? null],
        'fuel_price_ron97_market' => ['label' => 'RON97', 'icon' => 'fa-gas-pump', 'live' => $livePrices['RON97']['market'] ?? null],
        'fuel_price_diesel_market' => ['label' => 'Diesel', 'icon' => 'fa-truck', 'live' => $livePrices['Diesel']['market'] ?? null],
    ];
@endphp

<div class="au-page">

<div>
    <p class="au-eyebrow">Admin Panel</p>
    <h1 class="au-title">System Settings</h1>
    <p class="au-sub">Platform-wide configuration. Currently: fuel price fallback for the fare advisor.</p>
</div>

@include('layouts.partials.admin-subnav')

@if($errors->any())
    <div style="padding:12px 16px;border-radius:var(--r-md);border:1px solid rgba(220,38,38,.28);background:var(--danger-soft);color:var(--danger-ink);font-size:14px;font-weight:500;">
        <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>{{ $errors->first() }}
    </div>
@endif
@if(session('status'))
    <div style="padding:12px 16px;border-radius:var(--r-md);border:1px solid #bbf7d0;background:#f0fdf4;color:#15803d;font-size:14px;font-weight:600;">
        <i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>{{ session('status') }}
    </div>
@endif

<div class="card card-pad-lg">
    <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:4px;">
        <div class="ss-header-icon"><i class="fa-solid fa-gas-pump"></i></div>
        <div>
            <h3 class="h3" style="margin:0;">Fuel Price Fallback</h3>
            <p class="t-sm text-muted" style="margin:2px 0 0;">Used only when the live data.gov.my API is unreachable — the app checks these values first, before the hardcoded default baked into the code.</p>
        </div>
    </div>

    <div class="ss-live-status">
        <span class="badge {{ $liveSource === 'data.gov.my' ? 'badge-success' : ($liveSource === 'admin_override' ? 'badge-warning' : 'badge-danger') }}">
            <i class="fa-solid {{ $liveSource === 'data.gov.my' ? 'fa-signal' : 'fa-triangle-exclamation' }}"></i>
            {{ match($liveSource) { 'data.gov.my' => 'Live API is currently working', 'admin_override' => 'API is down — using this fallback right now', default => 'API is down — using the hardcoded default' } }}
        </span>
        @if(!empty($livePrices['as_of']))
            <span class="t-xs text-muted">as of {{ $livePrices['as_of'] }}</span>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.system-settings.update') }}" id="ss-form" style="margin-top:18px;">
        @csrf
        @method('PATCH')

        @foreach($liveFields as $name => $field)
            <div class="ss-field-row">
                <div class="ss-field-icon"><i class="fa-solid {{ $field['icon'] }}"></i></div>
                <div class="ss-field-main">
                    <div class="field-label" style="margin-bottom:6px;">{{ $field['label'] }} (RM/litre)</div>
                    <input type="number" step="0.01" min="0" max="20" name="{{ $name }}" class="input @error($name) has-error @enderror"
                        value="{{ old($name, $fuelFallback[$name]) }}">
                    @error($name)
                        <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                    @enderror
                </div>
                <div class="ss-field-live">
                    <span class="t-xs text-muted">Live now</span>
                    <strong>{{ $field['live'] !== null ? number_format($field['live'], 2) : '—' }}</strong>
                </div>
            </div>
        @endforeach

        <button type="submit" class="btn btn-primary btn-block" id="ss-submit-btn" style="margin-top:6px;">
            <i class="fa-solid fa-floppy-disk"></i> <span id="ss-submit-label">Save Fallback Prices</span>
        </button>
    </form>
</div>

<div class="card card-pad-lg" style="margin-top:16px;">
    <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:4px;">
        <div class="ss-header-icon"><i class="fa-solid fa-wallet"></i></div>
        <div>
            <h3 class="h3" style="margin:0;">Online Payment & Wallet</h3>
            <p class="t-sm text-muted" style="margin:2px 0 0;">Controls the ToyyibPay "Pay Online" option on the Pay Now popup and driver withdrawal requests.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.system-settings.update-gateway') }}" id="gw-form" style="margin-top:18px;">
        @csrf
        @method('PATCH')

        <div class="ss-field-row">
            <div class="ss-field-icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
            <div class="ss-field-main">
                <div class="field-label" style="margin-bottom:6px;">Gateway fee (RM, charged to passenger on top of fare)</div>
                <input type="number" step="0.01" min="0" max="50" name="gateway_fee_flat_amount" class="input @error('gateway_fee_flat_amount') has-error @enderror"
                    value="{{ old('gateway_fee_flat_amount', $gatewaySettings['gateway_fee_flat_amount']) }}">
                @error('gateway_fee_flat_amount')
                    <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="ss-field-row">
            <div class="ss-field-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="ss-field-main">
                <div class="field-label" style="margin-bottom:6px;">Minimum withdrawal amount (RM)</div>
                <input type="number" step="0.01" min="0" max="1000" name="wallet_min_withdrawal_amount" class="input @error('wallet_min_withdrawal_amount') has-error @enderror"
                    value="{{ old('wallet_min_withdrawal_amount', $gatewaySettings['wallet_min_withdrawal_amount']) }}">
                @error('wallet_min_withdrawal_amount')
                    <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" id="gw-submit-btn" style="margin-top:6px;">
            <i class="fa-solid fa-floppy-disk"></i> <span id="gw-submit-label">Save Payment Settings</span>
        </button>
    </form>
</div>

</div>{{-- /au-page --}}

<script>
    document.getElementById('ss-form').addEventListener('submit', function () {
        document.getElementById('ss-submit-btn').disabled = true;
        document.getElementById('ss-submit-label').textContent = 'Saving…';
    });
    document.getElementById('gw-form').addEventListener('submit', function () {
        document.getElementById('gw-submit-btn').disabled = true;
        document.getElementById('gw-submit-label').textContent = 'Saving…';
    });
</script>

@endsection
