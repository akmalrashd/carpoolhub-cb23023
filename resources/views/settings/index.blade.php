@extends('layouts.app')

@section('content')
    @php
        $photoUrl = $user->profile_photo ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo) : null;
        $duitnowQrUrl = $user->payment_qr_duitnow_url;
        $tngQrUrl = $user->payment_qr_tng_url;
        $countryOptions = [
            '+60' => 'MY (+60)',
            '+65' => 'SG (+65)',
            '+62' => 'ID (+62)',
            '+66' => 'TH (+66)',
            '+63' => 'PH (+63)',
            '+673' => 'BN (+673)',
            '+84' => 'VN (+84)',
            '+91' => 'IN (+91)',
            '+1' => 'US/CA (+1)',
            '+44' => 'GB (+44)',
        ];
        $oldCode = old('whatsapp_country_code');
        $oldNumber = old('whatsapp_number');
        $parsedCode = '+60';
        $parsedNumber = '';
        $existingPhone = (string) ($user->phone ?? '');
        $phoneDigits = preg_replace('/\D+/', '', $existingPhone);
        if ($phoneDigits) {
            foreach (array_keys($countryOptions) as $code) {
                $codeDigits = ltrim($code, '+');
                if (str_starts_with($phoneDigits, $codeDigits)) {
                    $parsedCode = $code;
                    $parsedNumber = substr($phoneDigits, strlen($codeDigits)) ?: '';
                    break;
                }
            }
        }
        $selectedCode = $oldCode ?: $parsedCode;
        $selectedNumber = $oldNumber ?? $parsedNumber;
        $selectedEmailVisibility = (string) old('email_visible', $user->email_visible ?: 'visible_friend');
        $selectedPhoneVisibility = (string) old('phone_visible', $user->phone_visible ?: 'visible_friend');
        $paymentBankOptions = [
            'Maybank',
            'CIMB Bank',
            'Public Bank',
            'RHB Bank',
            'Hong Leong Bank',
            'AmBank',
            'Bank Islam',
            'Bank Muamalat',
            'Affin Bank',
            'Alliance Bank',
            'Bank Simpanan Nasional (BSN)',
            'Bank Rakyat',
            'UOB Malaysia',
            'OCBC Malaysia',
            'HSBC Bank Malaysia',
            'Standard Chartered Malaysia',
            'Citibank Malaysia',
            'MBSB Bank',
            'Agrobank',
            'GXBank',
            'AEON Bank',
            'Touch n Go eWallet',
            'GrabPay',
            'Boost',
            'ShopeePay',
            'MAE Wallet',
            'BigPay',
            'Setel Wallet',
        ];
        $selectedPaymentBank = (string) old('payment_bank_name', $user->payment_bank_name ?? '');
        $isDriverOrAdmin = in_array($user->role, ['driver', 'admin'], true);
    @endphp

    <style>
        /* ── Centered Container ── */
        .profile-page-container {
            max-width: 780px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-sizing: border-box;
        }

        /* ── Page Header ── */
        .settings-header {
            margin-bottom: 2px;
        }
        .pg-eyebrow {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            font-family: var(--font-ui), sans-serif;
            margin: 0 0 4px;
        }
        .pg-title {
            margin: 0 0 4px;
            font-family: var(--font-display), sans-serif;
            font-size: clamp(1.4rem, 2.2vw, 1.75rem);
            font-weight: 800;
            color: var(--ink);
            line-height: 1.1;
        }
        .pg-sub {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        /* ── Hero Profile Header Card ── */
        .settings-hero-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-xl);
            padding: 20px;
            box-shadow: var(--shadow-1);
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }
        .settings-hero-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--ch-yellow), #f59e0b);
        }
        .settings-hero-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .settings-hero-avatar {
            width: 72px;
            height: 72px;
            border-radius: 999px;
            border: 3px solid var(--surface);
            box-shadow: 0 0 0 2px var(--hairline-strong);
            background: var(--canvas);
            color: var(--ink);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 26px;
            overflow: hidden;
            font-family: var(--font-display), sans-serif;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .settings-hero-avatar:hover {
            transform: scale(1.04);
            box-shadow: 0 0 0 3px var(--ch-yellow);
        }
        .settings-hero-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-cam-badge {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 26px;
            height: 26px;
            border-radius: 999px;
            background: var(--ch-yellow);
            border: 2px solid var(--surface);
            color: var(--ch-yellow-ink);
            display: grid;
            place-items: center;
            font-size: 11px;
            cursor: pointer;
            box-shadow: var(--shadow-1);
            transition: transform .15s ease;
        }
        .avatar-cam-badge:hover {
            transform: scale(1.15);
        }
        .settings-hero-info {
            flex: 1;
            min-width: 0;
        }
        .settings-hero-name {
            font-family: var(--font-display), sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--ink);
            margin: 0 0 3px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .settings-hero-email {
            font-size: 13px;
            color: var(--muted);
            margin: 0 0 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
        }
        .role-pill.driver {
            background: var(--ch-yellow-tint);
            color: var(--warning-ink);
            border: 1px solid var(--ch-yellow-line);
        }
        .role-pill.passenger {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .role-pill.admin {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .hero-meta-strip {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 11px;
            color: var(--muted);
            font-weight: 600;
        }
        .hero-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* ── Navigation Tabs ── */
        .settings-nav {
            display: flex;
            width: 100%;
            max-width: 100%;
            gap: 4px;
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            border-radius: 14px;
            padding: 4px;
            box-shadow: none;
            box-sizing: border-box;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .settings-nav::-webkit-scrollbar { display: none; }

        .settings-nav-btn {
            flex: 1 0 auto;
            min-width: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 10px;
            border-radius: 10px; /* Rounded Rectangle, NOT oval! */
            border: 1px solid transparent;
            background: transparent;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            font-family: var(--font-ui), sans-serif;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
            text-align: center;
        }
        @media (max-width: 480px) {
            .settings-nav-btn {
                font-size: 11px;
                padding: 8px 6px;
                gap: 4px;
            }
        }
        .settings-nav-btn:hover {
            color: var(--ink);
        }
        .settings-nav-btn.is-active {
            background: var(--surface);
            border-color: var(--hairline);
            color: var(--ink);
            box-shadow: var(--shadow-1);
            font-weight: 800;
        }
        .settings-nav-btn i {
            font-size: 14px;
            color: inherit;
        }

        /* ── Content Panels ── */
        .settings-content {
            width: 100%;
            box-sizing: border-box;
        }
        .settings-panel-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-xl);
            padding: 22px;
            box-shadow: var(--shadow-1);
            display: none;
            width: 100%;
            box-sizing: border-box;
            animation: fadeInTab .22s ease-out;
        }
        .settings-panel-card.is-active {
            display: block;
        }

        @keyframes fadeInTab {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .panel-head {
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--hairline);
        }
        .panel-title {
            font-family: var(--font-display), sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--ink);
            margin: 0 0 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel-desc {
            font-size: 13px;
            color: var(--muted);
            margin: 0;
        }

        /* ── Form Styling ── */
        .form-grid {
            display: grid;
            gap: 16px;
        }
        .form-group {
            display: grid;
            gap: 6px;
        }
        .form-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--ink-2);
            margin: 0;
            font-family: var(--font-ui), sans-serif;
        }

        /* Modern Prefix Input Group */
        .input-wrap {
            display: flex;
            align-items: center;
            background: var(--surface-2);
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-md);
            overflow: hidden;
            transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
        }
        .input-wrap:focus-within {
            border-color: var(--ch-yellow);
            box-shadow: 0 0 0 3px var(--ch-yellow-tint);
            background: var(--surface);
        }
        .input-icon {
            padding: 0 12px;
            color: var(--muted);
            font-size: 14px;
            flex-shrink: 0;
        }
        .input-field {
            flex: 1;
            min-width: 0;
            border: 0;
            background: transparent;
            color: var(--ink);
            padding: 11px 12px 11px 0;
            font-size: 14px;
            font-family: var(--font-ui), sans-serif;
            outline: none;
        }
        .input-field:disabled, .input-field[readonly] {
            color: var(--muted);
            cursor: not-allowed;
        }

        .select-field {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath fill='%2364748b' d='M1.17.97a.75.75 0 0 1 1.06 0L5 3.74 7.77.97a.75.75 0 0 1 1.06 1.06L5.53 5.33a.75.75 0 0 1-1.06 0L1.17 2.03a.75.75 0 0 1 0-1.06z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 10px 7px;
            padding-right: 26px !important;
            cursor: pointer;
        }

        .vis-select {
            width: 140px;
            border-left: 1px solid var(--hairline);
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-2);
            padding-left: 10px;
        }

        /* Password toggle button */
        .pass-toggle-btn {
            background: transparent;
            border: 0;
            padding: 0 12px;
            color: var(--muted);
            cursor: pointer;
            font-size: 14px;
            transition: color .15s ease;
        }
        .pass-toggle-btn:hover { color: var(--ink); }

        /* ── Interactive QR Upload Section ── */
        .qr-upload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-top: 6px;
        }
        .qr-card {
            border: 2px dashed var(--hairline-strong);
            border-radius: var(--r-lg);
            background: var(--surface-2);
            padding: 16px;
            text-align: center;
            position: relative;
            transition: border-color .18s ease, background .18s ease;
        }
        .qr-card:hover {
            border-color: var(--ch-yellow);
            background: var(--surface);
        }
        .qr-preview-box {
            width: 140px;
            height: 140px;
            margin: 0 auto 12px;
            border-radius: var(--r-md);
            background: var(--canvas);
            border: 1px solid var(--hairline);
            display: grid;
            place-items: center;
            overflow: hidden;
            position: relative;
        }
        .qr-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .qr-empty-icon {
            font-size: 32px;
            color: var(--muted);
        }
        .qr-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            margin: 0 0 2px;
        }
        .qr-sub {
            font-size: 11px;
            color: var(--muted);
            margin: 0 0 12px;
        }
        .qr-btn-wrap {
            display: flex;
            justify-content: center;
            gap: 8px;
        }
        .qr-file-input {
            display: none;
        }

        /* ── Submit Row ── */
        .form-actions {
            margin-top: 10px;
            padding-top: 16px;
            border-top: 1px solid var(--hairline);
            display: flex;
            justify-content: flex-end;
        }
        .btn-submit-yellow {
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
            border: 1px solid var(--ch-yellow-line);
            border-radius: var(--r-md);
            padding: 11px 22px;
            font-size: 14px;
            font-weight: 800;
            font-family: var(--font-display), sans-serif;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-yellow);
            transition: transform .16s ease, background .16s ease, box-shadow .16s ease;
        }
        .btn-submit-yellow:hover {
            background: var(--ch-yellow-deep);
            transform: translateY(-1px);
            box-shadow: var(--shadow-yellow), 0 4px 12px rgba(234,179,8,.25);
        }

        /* ── Alert Toast ── */
        .settings-alert {
            padding: 12px 16px;
            border-radius: var(--r-md);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .settings-alert.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
        }
        .settings-alert.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
    </style>

    <div class="profile-page-container">
        {{-- Header --}}
        <div class="settings-header">
            <p class="pg-eyebrow">Account</p>
            <h1 class="pg-title">Settings & Profile</h1>
            <p class="pg-sub">Manage your personal information, payment methods, and account security.</p>
        </div>

        {{-- Status Notifications --}}
        @if(session('status'))
            <div class="settings-alert success">
                <i class="fa-solid fa-circle-check" style="font-size:16px;"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="settings-alert error">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:16px;"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Hero Profile Card --}}
        <div class="settings-hero-card">
            <div class="settings-hero-avatar-wrap">
                <div class="settings-hero-avatar" onclick="document.getElementById('avatarFileInput').click()" title="Click to upload profile photo">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="{{ $user->name }}">
                    @else
                        <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>
                <button type="button" class="avatar-cam-badge" onclick="document.getElementById('avatarFileInput').click()" title="Change photo">
                    <i class="fa-solid fa-camera"></i>
                </button>
            </div>
            <div class="settings-hero-info">
                <h2 class="settings-hero-name">
                    {{ $user->name }}
                    <span class="role-pill {{ strtolower($user->role ?? 'passenger') }}">
                        @if($user->role === 'driver')
                            <i class="fa-solid fa-car"></i> Driver
                        @elseif($user->role === 'admin')
                            <i class="fa-solid fa-user-shield"></i> Admin
                        @else
                            <i class="fa-solid fa-user"></i> Passenger
                        @endif
                    </span>
                </h2>
                <div class="settings-hero-email">
                    <i class="fa-solid fa-envelope"></i> {{ $user->email }}
                </div>
                <div class="hero-meta-strip">
                    <span class="hero-meta-item"><i class="fa-solid fa-calendar-check" style="color:var(--ch-yellow-ink)"></i> Member since {{ $user->created_at?->format('M Y') ?? '2026' }}</span>
                    <span class="hero-meta-item"><i class="fa-solid fa-shield-check" style="color:#10b981"></i> Verified Account</span>
                </div>
            </div>
        </div>

        {{-- Segmented Tab Switcher --}}
        <div class="settings-nav" role="tablist">
            <button type="button" class="settings-nav-btn is-active" id="nav-btn-profile" onclick="switchSettingsTab('profile')">
                <i class="fa-solid fa-user"></i>
                <span>Profile Details</span>
            </button>
            <button type="button" class="settings-nav-btn" id="nav-btn-payment" onclick="switchSettingsTab('payment')">
                <i class="fa-solid fa-wallet"></i>
                <span>Payment Methods</span>
            </button>
            <button type="button" class="settings-nav-btn" id="nav-btn-security" onclick="switchSettingsTab('security')">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Security & Password</span>
            </button>
        </div>

        {{-- Panels Container --}}
        <div class="settings-content">

            {{-- ─────────────────────────────────────────────────────────────
                 TAB 1: PROFILE DETAILS
            ─────────────────────────────────────────────────────────────── --}}
            <div class="settings-panel-card is-active" id="panel-profile">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-user-gear"></i> Personal Profile</h3>
                    <p class="panel-desc">Update your name, contact details, and visibility settings across CarpoolHub.</p>
                </div>

                <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    {{-- Hidden Avatar Input triggered by Hero Avatar Camera button --}}
                    <input type="file" name="profile_photo" id="avatarFileInput" accept="image/*" class="sr-only" onchange="previewAvatar(this)">

                    <div class="form-grid">
                        {{-- Full Name --}}
                        <div class="form-group">
                            <label class="form-label" for="profileName">Full Name</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                                <input type="text" id="profileName" name="name" class="input-field" value="{{ old('name', $user->name) }}" required placeholder="Enter your full name">
                            </div>
                        </div>

                        {{-- Email Address (Read-only) + Email Visibility --}}
                        <div class="form-group">
                            <label class="form-label" for="profileEmail">Email Address</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" id="profileEmail" class="input-field" value="{{ $user->email }}" disabled readonly>
                                <span class="input-icon" title="Email is locked"><i class="fa-solid fa-lock" style="font-size:12px;"></i></span>
                                <select name="email_visible" class="input-field select-field vis-select" title="Who can see your email">
                                    <option value="visible_public" {{ $selectedEmailVisibility === 'visible_public' ? 'selected' : '' }}>Public</option>
                                    <option value="visible_friend" {{ $selectedEmailVisibility === 'visible_friend' ? 'selected' : '' }}>Connections Only</option>
                                    <option value="unvisible" {{ $selectedEmailVisibility === 'unvisible' ? 'selected' : '' }}>Private</option>
                                </select>
                            </div>
                            <span style="font-size:11px; color:var(--muted); margin-top:2px;">Email address is fixed to your account credentials.</span>
                        </div>

                        {{-- Phone / WhatsApp Number + Visibility --}}
                        <div class="form-group">
                            <label class="form-label" for="profilePhone">Phone / WhatsApp Number</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-brands fa-whatsapp"></i></span>
                                <select name="whatsapp_country_code" class="input-field select-field" style="width:110px; flex:0 0 auto; border-right:1px solid var(--hairline);" title="Country Code">
                                    @foreach($countryOptions as $code => $label)
                                        <option value="{{ $code }}" {{ $selectedCode === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="tel" id="profilePhone" name="whatsapp_number" class="input-field" value="{{ $selectedNumber }}" placeholder="e.g. 1110000011">
                                <select name="phone_visible" class="input-field select-field vis-select" title="Who can see your phone number">
                                    <option value="visible_public" {{ $selectedPhoneVisibility === 'visible_public' ? 'selected' : '' }}>Public</option>
                                    <option value="visible_friend" {{ $selectedPhoneVisibility === 'visible_friend' ? 'selected' : '' }}>Connections Only</option>
                                    <option value="unvisible" {{ $selectedPhoneVisibility === 'unvisible' ? 'selected' : '' }}>Private</option>
                                </select>
                            </div>
                        </div>

                        {{-- Vehicle Details (Driver & Admin) --}}
                        @if($isDriverOrAdmin)
                            <div class="form-group" style="margin-top:6px;">
                                <label class="form-label">Vehicle Details</label>
                                <div class="input-wrap">
                                    <span class="input-icon"><i class="fa-solid fa-car"></i></span>
                                    <input type="text" name="vehicle_model" class="input-field" value="{{ old('vehicle_model', $user->vehicle_model) }}" placeholder="Model (e.g. Perodua Myvi 1.5)" style="border-right:1px solid var(--hairline);">
                                    <input type="text" name="vehicle_plate" class="input-field" value="{{ old('vehicle_plate', $user->vehicle_plate) }}" placeholder="Plate (e.g. VAB 1234)" style="width:140px; flex:0 0 auto; padding-left:12px;">
                                </div>
                            </div>
                        @endif

                        <div class="form-actions">
                            <button type="submit" class="btn-submit-yellow">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Save Profile Details
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ─────────────────────────────────────────────────────────────
                 TAB 2: PAYMENT METHODS & QR
            ─────────────────────────────────────────────────────────────── --}}
            <div class="settings-panel-card" id="panel-payment">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-wallet"></i> Payment Methods & QR</h3>
                    <p class="panel-desc">Configure your bank account and upload QR codes to collect passenger fares easily.</p>
                </div>

                <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="form-grid">
                        {{-- Bank / E-Wallet Name --}}
                        <div class="form-group">
                            <label class="form-label" for="paymentBank">Bank / E-Wallet Provider</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-solid fa-building-columns"></i></span>
                                <select id="paymentBank" name="payment_bank_name" class="input-field select-field">
                                    <option value="">Select Bank / E-Wallet...</option>
                                    @foreach($paymentBankOptions as $bank)
                                        <option value="{{ $bank }}" {{ $selectedPaymentBank === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Account Holder Name --}}
                        <div class="form-group">
                            <label class="form-label" for="paymentAccountName">Account Holder Name</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-solid fa-id-card"></i></span>
                                <input type="text" id="paymentAccountName" name="payment_account_name" class="input-field" value="{{ old('payment_account_name', $user->payment_account_name) }}" placeholder="Full name as registered in bank account">
                            </div>
                        </div>

                        {{-- Account / Phone Number --}}
                        <div class="form-group">
                            <label class="form-label" for="paymentAccountNumber">Account Number / DuitNow ID</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-solid fa-credit-card"></i></span>
                                <input type="text" id="paymentAccountNumber" name="payment_account_number" class="input-field" value="{{ old('payment_account_number', $user->payment_account_number) }}" placeholder="e.g. 156012345678 or 01112844464">
                            </div>
                        </div>

                        {{-- Interactive QR Code Upload Section --}}
                        <div style="margin-top:10px;">
                            <label class="form-label">Payment QR Codes</label>
                            <div class="qr-upload-grid">
                                {{-- DuitNow QR Card --}}
                                <div class="qr-card">
                                    <div class="qr-preview-box">
                                        @if($duitnowQrUrl)
                                            <img id="duitnowQrPreview" src="{{ $duitnowQrUrl }}" alt="DuitNow QR">
                                        @else
                                            <div id="duitnowEmptyIcon" class="qr-empty-icon"><i class="fa-solid fa-qrcode"></i></div>
                                            <img id="duitnowQrPreview" src="" alt="" style="display:none;">
                                        @endif
                                    </div>
                                    <h4 class="qr-title">DuitNow QR</h4>
                                    <p class="qr-sub">Upload DuitNow QR image (JPG / PNG)</p>
                                    <div class="qr-btn-wrap">
                                        <button type="button" class="btn btn-ghost btn-xs" onclick="document.getElementById('duitnowQrInput').click()">
                                            <i class="fa-solid fa-upload"></i> {{ $duitnowQrUrl ? 'Change' : 'Upload' }}
                                        </button>
                                        <input type="file" id="duitnowQrInput" name="payment_qr_duitnow" accept="image/*" class="qr-file-input" onchange="previewQr(this, 'duitnowQrPreview', 'duitnowEmptyIcon')">
                                    </div>
                                </div>

                                {{-- Touch 'n Go QR Card --}}
                                <div class="qr-card">
                                    <div class="qr-preview-box">
                                        @if($tngQrUrl)
                                            <img id="tngQrPreview" src="{{ $tngQrUrl }}" alt="Touch n Go QR">
                                        @else
                                            <div id="tngEmptyIcon" class="qr-empty-icon"><i class="fa-solid fa-qrcode"></i></div>
                                            <img id="tngQrPreview" src="" alt="" style="display:none;">
                                        @endif
                                    </div>
                                    <h4 class="qr-title">Touch 'n Go QR</h4>
                                    <p class="qr-sub">Upload Touch 'n Go QR image (JPG / PNG)</p>
                                    <div class="qr-btn-wrap">
                                        <button type="button" class="btn btn-ghost btn-xs" onclick="document.getElementById('tngQrInput').click()">
                                            <i class="fa-solid fa-upload"></i> {{ $tngQrUrl ? 'Change' : 'Upload' }}
                                        </button>
                                        <input type="file" id="tngQrInput" name="payment_qr_tng" accept="image/*" class="qr-file-input" onchange="previewQr(this, 'tngQrPreview', 'tngEmptyIcon')">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit-yellow">
                                <i class="fa-solid fa-wallet"></i>
                                Save Payment Details
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ─────────────────────────────────────────────────────────────
                 TAB 3: SECURITY & PASSWORD
            ─────────────────────────────────────────────────────────────── --}}
            <div class="settings-panel-card" id="panel-security">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-shield-halved"></i> Security & Password</h3>
                    <p class="panel-desc">Update your password to keep your account safe.</p>
                </div>

                <form method="POST" action="{{ route('settings.password.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="form-grid">
                        {{-- Current Password --}}
                        <div class="form-group">
                            <label class="form-label" for="currentPassword">Current Password</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" id="currentPassword" name="current_password" class="input-field" required placeholder="Enter current password">
                                <button type="button" class="pass-toggle-btn" onclick="togglePassVisibility('currentPassword', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        {{-- New Password --}}
                        <div class="form-group">
                            <label class="form-label" for="newPassword">New Password</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-solid fa-key"></i></span>
                                <input type="password" id="newPassword" name="new_password" class="input-field" required minlength="8" placeholder="Minimum 8 characters">
                                <button type="button" class="pass-toggle-btn" onclick="togglePassVisibility('newPassword', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Confirm New Password --}}
                        <div class="form-group">
                            <label class="form-label" for="confirmPassword">Confirm New Password</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-solid fa-shield-check"></i></span>
                                <input type="password" id="confirmPassword" name="new_password_confirmation" class="input-field" required placeholder="Re-enter new password">
                                <button type="button" class="pass-toggle-btn" onclick="togglePassVisibility('confirmPassword', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit-yellow">
                                <i class="fa-solid fa-shield-halved"></i>
                                Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        // ── Tab Switcher Logic ───────────────────────────────────────────
        function switchSettingsTab(tabName) {
            const tabs = ['profile', 'payment', 'security'];
            if (!tabs.includes(tabName)) return;

            tabs.forEach(t => {
                const btn = document.getElementById(`nav-btn-${t}`);
                const panel = document.getElementById(`panel-${t}`);
                if (btn && panel) {
                    if (t === tabName) {
                        btn.classList.add('is-active');
                        panel.classList.add('is-active');
                    } else {
                        btn.classList.remove('is-active');
                        panel.classList.remove('is-active');
                    }
                }
            });

            if (history.pushState) {
                history.pushState(null, null, `#${tabName}`);
            } else {
                location.hash = `#${tabName}`;
            }
        }

        // ── Password Visibility Toggle ───────────────────────────────────
        function togglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        }

        // ── Avatar Preview ───────────────────────────────────────────────
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const heroAvatar = document.querySelector('.settings-hero-avatar');
                    if (heroAvatar) {
                        heroAvatar.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // ── QR Code Image Preview ─────────────────────────────────────────
        function previewQr(input, imgId, emptyIconId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.getElementById(imgId);
                    const icon = document.getElementById(emptyIconId);
                    if (img) {
                        img.src = e.target.result;
                        img.style.display = 'block';
                    }
                    if (icon) {
                        icon.style.display = 'none';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // ── Check Initial Hash on Page Load ──────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            const hash = location.hash.replace('#', '');
            if (['profile', 'payment', 'security'].includes(hash)) {
                switchSettingsTab(hash);
            }
        });
    </script>
@endsection
