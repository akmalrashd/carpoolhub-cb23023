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
    @endphp

    <style>
        /* ── Page header ── */
        .pg-eyebrow {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            font-family: var(--font-ui), sans-serif;
            margin: 0 0 4px;
        }

        .pg-title {
            margin: 0 0 2px;
            font-family: var(--font-display), sans-serif;
            font-size: clamp(1.4rem, 2.2vw, 1.75rem);
            font-weight: 700;
            color: var(--ink);
            line-height: 1.1;
        }

        .pg-sub {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        /* ── Layout ── */
        .settings-page {
            display: grid;
            gap: 0;
            align-items: start;
        }

        @media (min-width: 860px) {
            .settings-page {
                grid-template-columns: 220px minmax(0, 1fr);
                gap: 18px;
                padding: 20px 0 28px;
                align-items: start;
            }
        }

        /* ── Left rail nav ── */
        .settings-rail {
            position: sticky;
            top: 88px;
            display: none;
            flex-direction: column;
            gap: 2px;
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 10px;
            box-shadow: var(--shadow-1);
        }

        @media (min-width: 860px) {
            .settings-rail { display: flex; }
        }

        .settings-rail-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--r-sm);
            border: 1px solid transparent;
            color: var(--ink-3);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background .15s, color .15s, border-color .15s;
            cursor: pointer;
            background: transparent;
            font-family: var(--font-ui), sans-serif;
        }

        .settings-rail-link:hover {
            background: var(--canvas);
            color: var(--ink);
        }

        .settings-rail-link.is-active {
            background: var(--ch-yellow-tint);
            border-color: var(--ch-yellow-line);
            color: var(--warning-ink);
            font-weight: 700;
        }

        .settings-rail-link i {
            width: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        /* ── Mobile tab strip ── */
        .settings-tabs {
            display: flex;
            gap: 4px;
            overflow-x: auto;
            padding: 0 2px 12px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .settings-tabs::-webkit-scrollbar { display: none; }

        @media (min-width: 860px) {
            .settings-tabs { display: none; }
        }

        .settings-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: var(--r-pill);
            border: 1px solid var(--hairline);
            background: var(--surface);
            color: var(--ink-3);
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s;
            font-family: var(--font-ui), sans-serif;
        }

        .settings-tab-btn:hover {
            background: var(--canvas);
            color: var(--ink);
        }

        .settings-tab-btn.is-active {
            background: var(--ch-yellow-tint);
            border-color: var(--ch-yellow-line);
            color: var(--warning-ink);
            font-weight: 700;
        }

        /* ── Right column: stacked sections ── */
        .settings-body {
            display: grid;
            gap: 16px;
        }

        /* ── Section cards ── */
        .settings-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 20px;
            box-shadow: var(--shadow-1);
            scroll-margin-top: 88px;
        }

        .settings-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .settings-section-title {
            margin: 0;
            font-family: var(--font-display), sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: var(--ink);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .settings-section-title i {
            color: var(--muted);
            font-size: 15px;
        }

        /* ── Avatar ── */
        .settings-avatar-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .settings-avatar {
            width: 64px;
            height: 64px;
            border-radius: var(--r-pill);
            border: 2px solid var(--hairline);
            background: var(--canvas);
            color: var(--ink-2);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 22px;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
            cursor: pointer;
            transition: border-color .16s, box-shadow .16s, transform .16s;
            font-family: var(--font-display), sans-serif;
        }

        .settings-avatar:hover {
            border-color: var(--ch-yellow);
            box-shadow: 0 0 0 4px var(--ch-yellow-tint);
            transform: translateY(-1px);
        }

        .settings-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .settings-avatar-cam {
            position: absolute;
            right: -4px;
            bottom: -4px;
            width: 22px;
            height: 22px;
            border-radius: var(--r-pill);
            background: var(--surface);
            border: 1px solid var(--hairline);
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            box-shadow: var(--shadow-1);
        }

        .settings-avatar-meta strong {
            display: block;
            font-size: 15px;
            color: var(--ink);
            font-weight: 700;
        }

        .settings-avatar-meta span {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }

        /* ── Form fields ── */
        .settings-form {
            display: grid;
            gap: 14px;
        }

        .settings-field {
            display: grid;
            gap: 6px;
        }

        .settings-label {
            font-size: 11px;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin: 0;
            font-family: var(--font-ui), sans-serif;
        }

        .settings-input-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            align-items: center;
        }

        .settings-input {
            width: 100%;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            color: var(--ink);
            padding: 10px 12px;
            font-size: 14px;
            font-family: var(--font-ui), sans-serif;
            outline: none;
            transition: border-color .16s, box-shadow .16s, background .16s;
        }

        .settings-input:disabled {
            background: var(--canvas);
            color: var(--muted);
            border-color: var(--hairline);
            cursor: not-allowed;
        }

        .settings-input:focus {
            border-color: var(--ch-yellow);
            box-shadow: 0 0 0 3px var(--ch-yellow-tint);
            background: var(--surface);
        }

        /* compound phone + email group */
        .settings-input-group {
            display: flex;
            align-items: stretch;
            width: 100%;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            overflow: hidden;
            transition: border-color .16s, box-shadow .16s, background .16s;
        }

        .settings-input-group:focus-within {
            border-color: var(--ch-yellow);
            box-shadow: 0 0 0 3px var(--ch-yellow-tint);
            background: var(--surface);
        }

        .settings-input-group .settings-input {
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .settings-input-group .settings-input:focus {
            border: 0;
            box-shadow: none;
            background: var(--surface);
        }

        .settings-input-group .settings-input:disabled {
            background: transparent;
        }

        .settings-group-divider {
            width: 1px;
            background: var(--hairline);
            flex-shrink: 0;
            align-self: stretch;
        }

        /* country code select inside group */
        .settings-country-select {
            width: var(--country-width, 130px);
            flex: 0 0 auto;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: var(--ink);
            padding: 10px 22px 10px 10px;
            font-size: 13px;
            font-family: var(--font-ui), sans-serif;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath fill='%2364748b' d='M1.17.97a.75.75 0 0 1 1.06 0L5 3.74 7.77.97a.75.75 0 0 1 1.06 1.06L5.53 5.33a.75.75 0 0 1-1.06 0L1.17 2.03a.75.75 0 0 1 0-1.06z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 6px center;
            background-size: 10px 7px;
            transition: color .16s;
        }

        .settings-country-select:disabled {
            color: var(--muted);
            cursor: not-allowed;
        }

        /* visibility select inside group */
        .settings-vis-select {
            width: 144px;
            flex: 0 0 auto;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: var(--ink);
            padding: 10px 22px 10px 10px;
            font-size: 11px;
            font-weight: 700;
            font-family: var(--font-ui), sans-serif;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath fill='%2364748b' d='M1.17.97a.75.75 0 0 1 1.06 0L5 3.74 7.77.97a.75.75 0 0 1 1.06 1.06L5.53 5.33a.75.75 0 0 1-1.06 0L1.17 2.03a.75.75 0 0 1 0-1.06z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 6px center;
            background-size: 10px 7px;
            transition: color .16s;
        }

        .settings-vis-select:disabled {
            color: var(--muted);
            cursor: not-allowed;
        }

        /* edit pencil/close button */
        .settings-edit-btn {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            border-radius: var(--r-sm);
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-color .16s, color .16s, background .16s, transform .16s;
        }

        .settings-edit-btn:hover {
            border-color: var(--ch-yellow);
            background: var(--ch-yellow-tint);
            color: var(--warning-ink);
            transform: translateY(-1px);
        }

        /* expand/collapse toggle */
        .settings-toggle-btn {
            width: 36px;
            height: 36px;
            border-radius: var(--r-sm);
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: border-color .16s, color .16s, background .16s;
        }

        .settings-toggle-btn:hover {
            border-color: var(--ch-yellow);
            background: var(--ch-yellow-tint);
            color: var(--warning-ink);
        }

        /* hint text */
        .settings-hint {
            margin: 2px 0 0;
            color: var(--muted-2);
            font-size: 11px;
            font-weight: 600;
            font-family: var(--font-ui), sans-serif;
        }

        /* hidden file input */
        .profile-file-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
            overflow: hidden;
        }

        /* collapse panel */
        .settings-collapse {
            display: none;
            gap: 14px;
        }

        .settings-collapse.is-open {
            display: grid;
        }

        /* QR grid */
        .settings-qr-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        @media (min-width: 540px) {
            .settings-qr-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        .settings-qr-card {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .settings-qr-label {
            margin: 0;
            color: var(--ink-3);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-family: var(--font-ui), sans-serif;
        }

        .settings-qr-preview {
            width: 100%;
            height: 160px;
            border: 1px dashed var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .settings-qr-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .settings-qr-empty {
            color: var(--muted-2);
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            font-family: var(--font-ui), sans-serif;
        }

        /* role badge */
        .settings-role-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: var(--r-pill);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .02em;
            font-family: var(--font-ui), sans-serif;
            background: var(--ch-yellow-tint);
            border: 1px solid var(--ch-yellow-line);
            color: var(--warning-ink);
        }

        /* error banner */
        .settings-error {
            border: 1px solid var(--danger);
            background: var(--danger-soft);
            color: var(--danger-ink);
            border-radius: var(--r-md);
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        /* save button (yellow) */
        .settings-save-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 20px;
            border-radius: var(--r-sm);
            border: 1px solid var(--ch-yellow-deep);
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
            font-weight: 800;
            font-size: 14px;
            font-family: var(--font-ui), sans-serif;
            cursor: pointer;
            width: fit-content;
            transition: background .16s, box-shadow .16s, transform .16s;
        }

        .settings-save-btn:hover {
            background: var(--ch-yellow-deep);
            box-shadow: 0 6px 14px rgba(250, 204, 21, 0.35);
            transform: translateY(-1px);
        }

        .settings-save-btn:disabled {
            background: var(--hairline);
            border-color: var(--hairline);
            color: var(--muted);
            box-shadow: none;
            transform: none;
            cursor: not-allowed;
        }

        /* divider */
        .settings-divider {
            height: 1px;
            background: var(--hairline);
            margin: 4px 0;
        }
    </style>

    {{-- ── Page header ── --}}
    <div style="margin-bottom:16px;">
        <p class="pg-eyebrow">Account</p>
        <h1 class="pg-title">Settings &amp; profile</h1>
        <p class="pg-sub">Your account, vehicle, payment methods and notification preferences.</p>
    </div>

    @if($errors->any())
        <div class="settings-error">
            <strong>Please fix the following:</strong><br>
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('status') || session('success'))
        <div class="status-banner" id="statusBanner">
            <i class="fas fa-circle-check"></i>
            {{ session('status') ?? session('success') }}
        </div>
    @endif

    {{-- ── Mobile tab strip ── --}}
    <div class="settings-tabs" role="tablist" aria-label="Settings sections">
        <button type="button" class="settings-tab-btn is-active" data-section-tab="profile" role="tab">
            <i class="fas fa-user-circle"></i> Profile
        </button>
        <button type="button" class="settings-tab-btn" data-section-tab="payment" role="tab">
            <i class="fas fa-wallet"></i> Payment
        </button>
        <button type="button" class="settings-tab-btn" data-section-tab="password" role="tab">
            <i class="fas fa-shield-halved"></i> Password
        </button>
    </div>

    <div class="settings-page">
        {{-- ── Left rail navigation ── --}}
        <nav class="settings-rail" aria-label="Settings navigation">
            <a href="#section-profile" class="settings-rail-link is-active" data-section-link="profile">
                <i class="fas fa-user-circle"></i>
                Profile
            </a>
            <a href="#section-payment" class="settings-rail-link" data-section-link="payment">
                <i class="fas fa-wallet"></i>
                Payment
            </a>
            <a href="#section-password" class="settings-rail-link" data-section-link="password">
                <i class="fas fa-shield-halved"></i>
                Password
            </a>
        </nav>

        {{-- ── Right body ── --}}
        <div class="settings-body">

            {{-- ════════════════════════════════
                 SECTION: Profile
            ════════════════════════════════ --}}
            <section class="settings-card" id="section-profile" data-section="profile">
                <div class="settings-section-head">
                    <h2 class="settings-section-title">
                        <i class="fas fa-user-circle"></i>
                        Account Details
                    </h2>
                    @if(isset($user->role))
                        <span class="settings-role-badge">
                            <i class="fas fa-circle-check"></i>
                            {{ ucfirst($user->role) }}
                        </span>
                    @endif
                </div>

                {{-- Avatar --}}
                <div class="settings-avatar-row">
                    <label for="profile_photo" class="settings-avatar" title="Click to update profile photo">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Profile Photo">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                        <span class="settings-avatar-cam"><i class="fas fa-camera"></i></span>
                    </label>
                    <div class="settings-avatar-meta">
                        <strong>{{ $user->name }}</strong>
                        <span>{{ $user->email }}</span>
                        @if($user->phone)
                            <span>{{ $user->phone }}</span>
                        @endif
                        <span>Click the avatar to upload a new photo (JPG / PNG, max 2 MB)</span>
                        <span id="profilePhotoHint" style="color:var(--ch-yellow-deep); font-size:11px; font-weight:700;"></span>
                    </div>
                </div>

                <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="settings-form" id="accountProfileForm">
                    @csrf
                    @method('PATCH')

                    <input id="profile_photo" type="file" name="profile_photo" accept="image/*" class="profile-file-hidden">

                    {{-- Name --}}
                    <div class="settings-field">
                        <label for="name" class="settings-label">Full Name</label>
                        <div class="settings-input-row">
                            <input id="name" type="text" name="name"
                                value="{{ old('name', $user->name) }}"
                                required
                                class="settings-input"
                                disabled>
                            <button type="button" class="settings-edit-btn" data-target="name" aria-label="Edit name">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="settings-field">
                        <label for="email" class="settings-label">Email Address</label>
                        <div class="settings-input-row">
                            <div class="settings-input-group">
                                <input id="email" type="email" name="email"
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    class="settings-input"
                                    data-locked="1"
                                    disabled>
                                <div class="settings-group-divider"></div>
                                <select id="email_visible" name="email_visible"
                                    class="settings-vis-select"
                                    data-auto-save-visibility="1"
                                    disabled>
                                    <option value="visible_public" {{ $selectedEmailVisibility === 'visible_public' ? 'selected' : '' }}>Visible to Public</option>
                                    <option value="visible_friend" {{ $selectedEmailVisibility === 'visible_friend' ? 'selected' : '' }}>Visible to Connections</option>
                                    <option value="unvisible"      {{ $selectedEmailVisibility === 'unvisible'      ? 'selected' : '' }}>Hidden</option>
                                </select>
                            </div>
                            <button type="button" class="settings-edit-btn" data-target="email_visible" aria-label="Edit email visibility">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                        <p class="settings-hint">Email address is locked and cannot be changed here.</p>
                    </div>

                    {{-- Phone --}}
                    <div class="settings-field">
                        <label for="whatsapp_number" class="settings-label">Phone / WhatsApp</label>
                        <div class="settings-input-row">
                            <div class="settings-input-group">
                                <select id="whatsapp_country_code" name="whatsapp_country_code"
                                    class="settings-country-select settings-phone-input"
                                    disabled>
                                    @foreach($countryOptions as $code => $label)
                                        <option value="{{ $code }}" {{ $selectedCode === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="settings-group-divider"></div>
                                <input id="whatsapp_number"
                                    type="text"
                                    name="whatsapp_number"
                                    value="{{ $selectedNumber }}"
                                    class="settings-input settings-phone-input"
                                    inputmode="numeric"
                                    autocomplete="tel"
                                    placeholder="012-345 6789"
                                    disabled>
                                <div class="settings-group-divider"></div>
                                <select id="phone_visible" name="phone_visible"
                                    class="settings-vis-select settings-phone-input"
                                    data-auto-save-visibility="1"
                                    disabled>
                                    <option value="visible_public" {{ $selectedPhoneVisibility === 'visible_public' ? 'selected' : '' }}>Visible to Public</option>
                                    <option value="visible_friend" {{ $selectedPhoneVisibility === 'visible_friend' ? 'selected' : '' }}>Visible to Connections</option>
                                    <option value="unvisible"      {{ $selectedPhoneVisibility === 'unvisible'      ? 'selected' : '' }}>Hidden</option>
                                </select>
                            </div>
                            <button type="button" class="settings-edit-btn" data-target-group=".settings-phone-input" aria-label="Edit phone number">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                        <p class="settings-hint">Used for WhatsApp contact buttons in trips and payments.</p>
                    </div>

                    {{-- Vehicle --}}
                    <div class="settings-field">
                        <label for="vehicle_model" class="settings-label">Vehicle</label>
                        <div class="settings-input-row">
                            <div class="settings-input-group">
                                <input id="vehicle_model"
                                    type="text"
                                    name="vehicle_model"
                                    value="{{ old('vehicle_model', $user->vehicle_model) }}"
                                    class="settings-input settings-vehicle-input"
                                    placeholder="Perodua Bezza"
                                    disabled>
                                <div class="settings-group-divider"></div>
                                <input id="vehicle_plate"
                                    type="text"
                                    name="vehicle_plate"
                                    value="{{ old('vehicle_plate', $user->vehicle_plate) }}"
                                    class="settings-input settings-vehicle-input"
                                    placeholder="WMK 4821"
                                    disabled>
                            </div>
                            <button type="button" class="settings-edit-btn" data-target-group=".settings-vehicle-input" aria-label="Edit vehicle">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                        <p class="settings-hint">Shown on public trip cards so passengers can identify your car.</p>
                    </div>

                    <div>
                        <button type="submit" class="settings-save-btn" id="updateProfileBtn" disabled>
                            <i class="fas fa-floppy-disk"></i>
                            Save Profile
                        </button>
                    </div>
                </form>
            </section>

            {{-- ════════════════════════════════
                 SECTION: Payment Details
            ════════════════════════════════ --}}
            <section class="settings-card" id="section-payment" data-section="payment">
                <div class="settings-section-head">
                    <h2 class="settings-section-title">
                        <i class="fas fa-wallet"></i>
                        Payment Details
                    </h2>
                    <button type="button" class="settings-toggle-btn" id="paymentDetailsToggleBtn"
                        aria-label="Toggle payment details form"
                        aria-expanded="false"
                        aria-controls="paymentDetailsCollapse">
                        <i class="fas fa-pen" id="paymentDetailsToggleIcon"></i>
                    </button>
                </div>

                <p class="settings-hint" style="margin-bottom:4px;">
                    Set your bank or e-wallet, account holder name, account number, and QR codes (DuitNow / Touch 'n Go) so passengers can pay you easily.
                </p>

                <div class="settings-collapse" id="paymentDetailsCollapse">
                    <div class="settings-divider" style="margin-top:10px;"></div>

                    <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="settings-form" id="paymentDetailsForm">
                        @csrf
                        @method('PATCH')

                        {{-- Bank / Wallet --}}
                        <div class="settings-field">
                            <label for="payment_bank_name" class="settings-label">Bank / E-Wallet</label>
                            <div class="settings-input-row">
                                <select id="payment_bank_name" name="payment_bank_name"
                                    class="settings-input payment-details-input"
                                    disabled>
                                    <option value="">Select bank or e-wallet&hellip;</option>
                                    @foreach($paymentBankOptions as $bankName)
                                        <option value="{{ $bankName }}" {{ $selectedPaymentBank === $bankName ? 'selected' : '' }}>{{ $bankName }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="settings-edit-btn payment-edit-btn" data-target="payment_bank_name" aria-label="Edit bank or wallet">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Account holder name --}}
                        <div class="settings-field">
                            <label for="payment_account_name" class="settings-label">Account Holder Name</label>
                            <div class="settings-input-row">
                                <input id="payment_account_name" type="text" name="payment_account_name"
                                    value="{{ old('payment_account_name', $user->payment_account_name) }}"
                                    class="settings-input payment-details-input"
                                    placeholder="e.g. Ahmad Hakimi"
                                    disabled>
                                <button type="button" class="settings-edit-btn payment-edit-btn" data-target="payment_account_name" aria-label="Edit account holder name">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Account number --}}
                        <div class="settings-field">
                            <label for="payment_account_number" class="settings-label">Account Number</label>
                            <div class="settings-input-row">
                                <input id="payment_account_number" type="text" name="payment_account_number"
                                    value="{{ old('payment_account_number', $user->payment_account_number) }}"
                                    class="settings-input payment-details-input"
                                    inputmode="numeric"
                                    placeholder="e.g. 01112844464"
                                    disabled>
                                <button type="button" class="settings-edit-btn payment-edit-btn" data-target="payment_account_number" aria-label="Edit account number">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                        </div>

                        {{-- QR codes --}}
                        <div class="settings-qr-grid">
                            <div class="settings-qr-card">
                                <h3 class="settings-qr-label">DuitNow QR</h3>
                                <div class="settings-qr-preview">
                                    @if($duitnowQrUrl)
                                        <img src="{{ $duitnowQrUrl }}" alt="DuitNow QR">
                                    @else
                                        <span class="settings-qr-empty">No QR uploaded</span>
                                    @endif
                                </div>
                                <div class="settings-input-row">
                                    <input id="payment_qr_duitnow" type="file" name="payment_qr_duitnow"
                                        accept="image/*"
                                        class="settings-input payment-details-input"
                                        disabled>
                                    <button type="button" class="settings-edit-btn payment-edit-btn" data-target="payment_qr_duitnow" aria-label="Upload DuitNow QR">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="settings-qr-card">
                                <h3 class="settings-qr-label">Touch 'n Go QR</h3>
                                <div class="settings-qr-preview">
                                    @if($tngQrUrl)
                                        <img src="{{ $tngQrUrl }}" alt="Touch 'n Go QR">
                                    @else
                                        <span class="settings-qr-empty">No QR uploaded</span>
                                    @endif
                                </div>
                                <div class="settings-input-row">
                                    <input id="payment_qr_tng" type="file" name="payment_qr_tng"
                                        accept="image/*"
                                        class="settings-input payment-details-input"
                                        disabled>
                                    <button type="button" class="settings-edit-btn payment-edit-btn" data-target="payment_qr_tng" aria-label="Upload Touch n Go QR">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="settings-save-btn" id="savePaymentDetailsBtn" disabled>
                                <i class="fas fa-floppy-disk"></i>
                                Save Payment Details
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- ════════════════════════════════
                 SECTION: Change Password
            ════════════════════════════════ --}}
            <section class="settings-card" id="section-password" data-section="password">
                <div class="settings-section-head">
                    <h2 class="settings-section-title">
                        <i class="fas fa-shield-halved"></i>
                        Change Password
                    </h2>
                    <button type="button" class="settings-toggle-btn" id="passwordToggleBtn"
                        aria-label="Toggle password form"
                        aria-expanded="false"
                        aria-controls="passwordCollapse">
                        <i class="fas fa-pen" id="passwordToggleIcon"></i>
                    </button>
                </div>

                <p class="settings-hint" style="margin-bottom:4px;">
                    Use a strong password of at least 8 characters. You will stay logged in after changing your password.
                </p>

                <form method="POST" action="{{ route('settings.password.update') }}" class="settings-form" id="passwordForm">
                    @csrf
                    @method('PATCH')

                    <div class="settings-collapse" id="passwordCollapse">
                        <div class="settings-divider" style="margin-top:10px;"></div>

                        <div class="settings-field">
                            <label for="current_password" class="settings-label">Current Password</label>
                            <input id="current_password" type="password" name="current_password"
                                required
                                class="settings-input"
                                autocomplete="current-password"
                                disabled>
                        </div>

                        <div class="settings-field">
                            <label for="new_password" class="settings-label">New Password</label>
                            <input id="new_password" type="password" name="new_password"
                                required
                                class="settings-input"
                                autocomplete="new-password"
                                disabled>
                        </div>

                        <div class="settings-field">
                            <label for="new_password_confirmation" class="settings-label">Confirm New Password</label>
                            <input id="new_password_confirmation" type="password" name="new_password_confirmation"
                                required
                                class="settings-input"
                                autocomplete="new-password"
                                disabled>
                        </div>

                        <div>
                            <button type="submit" class="settings-save-btn" id="savePasswordBtn" disabled>
                                <i class="fas fa-key"></i>
                                Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </section>

        </div>{{-- /.settings-body --}}
    </div>{{-- /.settings-page --}}

    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.12.4/build/js/utils.js"></script>
    <script>
        (function () {
            /* ── Helpers ── */
            function setEditButtonState(button, isOpen) {
                if (!button) return;
                var icon = button.querySelector('i');
                if (icon) icon.className = isOpen ? 'fas fa-xmark' : 'fas fa-pen';
                button.setAttribute('data-open', isOpen ? '1' : '0');
            }

            /* ── Pencil edit buttons (inline unlock) ── */
            var editButtons = document.querySelectorAll('.settings-edit-btn');
            editButtons.forEach(function (button) {
                setEditButtonState(button, false);
                button.addEventListener('click', function () {
                    var targetGroup = button.getAttribute('data-target-group');
                    if (targetGroup) {
                        var groupedInputs = document.querySelectorAll(targetGroup);
                        if (!groupedInputs.length) return;
                        var isOpen = button.getAttribute('data-open') === '1';
                        groupedInputs.forEach(function (field) { field.disabled = isOpen; });
                        setEditButtonState(button, !isOpen);
                        if (!isOpen) groupedInputs[0].focus();
                        return;
                    }
                    var targetId = button.getAttribute('data-target');
                    var input = targetId ? document.getElementById(targetId) : null;
                    if (!input) return;
                    var isOpen = button.getAttribute('data-open') === '1';
                    input.disabled = isOpen;
                    setEditButtonState(button, !isOpen);
                    if (!isOpen) {
                        input.focus();
                        if (typeof input.setSelectionRange === 'function') {
                            var len = input.value ? input.value.length : 0;
                            input.setSelectionRange(len, len);
                        }
                    }
                });
            });

            /* ── Photo upload hint ── */
            var profilePhotoInput = document.getElementById('profile_photo');
            var profilePhotoHint  = document.getElementById('profilePhotoHint');
            if (profilePhotoInput && profilePhotoHint) {
                profilePhotoInput.addEventListener('change', function () {
                    if (!profilePhotoInput.files || !profilePhotoInput.files.length) return;
                    profilePhotoHint.textContent = 'Selected: ' + profilePhotoInput.files[0].name;
                });
            }

            /* ── Phone number: numeric only ── */
            var whatsappNumberInput = document.getElementById('whatsapp_number');
            if (whatsappNumberInput) {
                whatsappNumberInput.addEventListener('input', function () {
                    whatsappNumberInput.value = String(whatsappNumberInput.value || '').replace(/[^\d]/g, '');
                });
            }
            var paymentAccountNumberInput = document.getElementById('payment_account_number');
            if (paymentAccountNumberInput) {
                paymentAccountNumberInput.addEventListener('input', function () {
                    paymentAccountNumberInput.value = String(paymentAccountNumberInput.value || '').replace(/[^\d]/g, '');
                });
            }

            /* ── Country select placeholder helpers ── */
            var phoneCountry = document.getElementById('whatsapp_country_code');

            function fallbackExampleByCode(dialCode) {
                var map = {
                    '+60': '0123456789', '+65': '81234567', '+62': '081234567890',
                    '+66': '0812345678', '+63': '09171234567', '+81': '09012345678',
                    '+82': '01012345678', '+44': '07123456789', '+1': '2015550123',
                    '+91': '09876543210', '+86': '13123456789'
                };
                return map[dialCode] || '1234567890';
            }

            function toIso2FromOption(option) {
                if (!option) return '';
                var datasetIso = String(option.getAttribute('data-iso') || '').trim();
                if (datasetIso) return datasetIso.toLowerCase();
                var label = String(option.textContent || '');
                var match = label.match(/^([A-Z]{2})\s*\(/);
                return match ? match[1].toLowerCase() : '';
            }

            function updateWhatsappPlaceholder() {
                if (!phoneCountry || !whatsappNumberInput) return;
                var selectedOpt = phoneCountry.options[phoneCountry.selectedIndex];
                var dialCode = selectedOpt ? String(selectedOpt.value || '') : '';
                var iso2 = toIso2FromOption(selectedOpt);
                var example = '';
                if (window.intlTelInputUtils && iso2) {
                    try {
                        example = window.intlTelInputUtils.getExampleNumber(
                            iso2, true, window.intlTelInputUtils.numberType.MOBILE
                        ) || '';
                    } catch (_error) {}
                }
                if (example) {
                    var dialDigits = String(dialCode || '').replace(/[^\d]/g, '');
                    var exampleDigits = String(example || '').replace(/[^\d]/g, '');
                    if (dialDigits && exampleDigits.startsWith(dialDigits)) exampleDigits = exampleDigits.slice(dialDigits.length);
                    example = exampleDigits;
                } else {
                    example = fallbackExampleByCode(dialCode);
                }
                whatsappNumberInput.placeholder = example || '1234567890';
            }

            function updateCountrySelectWidth() {
                if (!phoneCountry) return;
                var selectedOpt = phoneCountry.options[phoneCountry.selectedIndex];
                var label = selectedOpt ? String(selectedOpt.textContent || '').trim() : '';
                var charCount = Math.max(label.length + 2, 12);
                var widthCh = Math.min(Math.max(charCount, 12), 18);
                phoneCountry.style.setProperty('--country-width', widthCh + 'ch');
            }

            function escapeHtml(value) {
                return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            }

            function normalizeDialCode(raw) {
                var digits = String(raw || '').replace(/[^\d]/g, '');
                return digits ? ('+' + digits) : '';
            }

            async function populateAllCountries() {
                if (!phoneCountry) return;
                var selectedCode = normalizeDialCode(phoneCountry.value) || '+60';
                try {
                    var response = await fetch('https://restcountries.com/v3.1/all?fields=name,cca2,idd');
                    if (!response.ok) return;
                    var rows = await response.json();
                    if (!Array.isArray(rows) || !rows.length) return;
                    var options = [];
                    rows.forEach(function (row) {
                        var iso2 = String(row && row.cca2 ? row.cca2 : '').toUpperCase();
                        var countryName = String(row && row.name && row.name.common ? row.name.common : '').trim();
                        var root = row && row.idd ? row.idd.root : null;
                        var suffixes = row && row.idd && Array.isArray(row.idd.suffixes) ? row.idd.suffixes : [];
                        if (!iso2 || !countryName || !root || !suffixes.length) return;
                        var dialCode = normalizeDialCode(String(root) + String(suffixes[0]));
                        if (!dialCode) return;
                        options.push({ iso2: iso2, countryName: countryName, dialCode: dialCode, label: countryName + ' (' + dialCode + ')' });
                    });
                    var seen = {};
                    options = options.filter(function (item) {
                        if (seen[item.iso2]) return false;
                        seen[item.iso2] = true;
                        return true;
                    }).sort(function (a, b) {
                        if (a.iso2 === 'MY') return -1;
                        if (b.iso2 === 'MY') return 1;
                        return a.countryName.localeCompare(b.countryName);
                    });
                    if (!options.length) return;
                    var hasSelectedCode = options.some(function (item) { return item.dialCode === selectedCode; });
                    if (!hasSelectedCode) selectedCode = '+60';
                    phoneCountry.innerHTML = options.map(function (item) {
                        var selected = item.dialCode === selectedCode || (item.iso2 === 'MY' && selectedCode === '+60');
                        return '<option value="' + escapeHtml(item.dialCode) + '" data-iso="' + escapeHtml(item.iso2.toLowerCase()) + '"' + (selected ? ' selected' : '') + '>' + escapeHtml(item.label) + '</option>';
                    }).join('');
                } catch (_error) {
                    // keep server-side fallback options
                } finally {
                    updateCountrySelectWidth();
                    updateWhatsappPlaceholder();
                }
            }

            if (phoneCountry) {
                phoneCountry.addEventListener('change', function () {
                    updateCountrySelectWidth();
                    updateWhatsappPlaceholder();
                });
            }
            populateAllCountries();

            /* ── Password collapse panel ── */
            var passwordToggleBtn    = document.getElementById('passwordToggleBtn');
            var passwordToggleIcon   = document.getElementById('passwordToggleIcon');
            var passwordCollapse     = document.getElementById('passwordCollapse');
            var passwordInputs       = passwordCollapse ? passwordCollapse.querySelectorAll('input') : [];

            function setPasswordPanel(open) {
                if (!passwordCollapse || !passwordToggleBtn || !passwordToggleIcon) return;
                passwordCollapse.classList.toggle('is-open', open);
                passwordToggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                passwordToggleBtn.setAttribute('aria-label', open ? 'Close password form' : 'Open password form');
                passwordToggleIcon.className = open ? 'fas fa-xmark' : 'fas fa-pen';
                passwordInputs.forEach(function (input) {
                    input.disabled = !open;
                    if (!open) input.value = '';
                });
                if (!open) {
                    var savePasswordBtnEl = document.getElementById('savePasswordBtn');
                    if (savePasswordBtnEl) savePasswordBtnEl.disabled = true;
                }
                if (open && passwordInputs.length) passwordInputs[0].focus();
            }

            if (passwordToggleBtn) {
                passwordToggleBtn.addEventListener('click', function () {
                    var isOpen = passwordCollapse && passwordCollapse.classList.contains('is-open');
                    setPasswordPanel(!isOpen);
                });
                setPasswordPanel(false);
            }

            /* ── Payment Details collapse panel ── */
            var paymentDetailsToggleBtn   = document.getElementById('paymentDetailsToggleBtn');
            var paymentDetailsToggleIcon  = document.getElementById('paymentDetailsToggleIcon');
            var paymentDetailsCollapse    = document.getElementById('paymentDetailsCollapse');
            var paymentInputs             = paymentDetailsCollapse ? paymentDetailsCollapse.querySelectorAll('.payment-details-input') : [];
            var paymentEditButtons        = paymentDetailsCollapse ? paymentDetailsCollapse.querySelectorAll('.payment-edit-btn') : [];

            function setPaymentDetailsPanel(open) {
                if (!paymentDetailsCollapse || !paymentDetailsToggleBtn || !paymentDetailsToggleIcon) return;
                paymentDetailsCollapse.classList.toggle('is-open', open);
                paymentDetailsToggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                paymentDetailsToggleBtn.setAttribute('aria-label', open ? 'Close payment details form' : 'Open payment details form');
                paymentDetailsToggleIcon.className = open ? 'fas fa-xmark' : 'fas fa-pen';
                if (!open) {
                    paymentInputs.forEach(function (input) { input.disabled = true; });
                    paymentEditButtons.forEach(function (btn) { setEditButtonState(btn, false); });
                    var savePaymentBtnEl = document.getElementById('savePaymentDetailsBtn');
                    if (savePaymentBtnEl) savePaymentBtnEl.disabled = true;
                }
            }

            if (paymentDetailsToggleBtn) {
                paymentDetailsToggleBtn.addEventListener('click', function () {
                    var isOpen = paymentDetailsCollapse && paymentDetailsCollapse.classList.contains('is-open');
                    setPaymentDetailsPanel(!isOpen);
                });
                setPaymentDetailsPanel(false);
            }

            /* ── Visibility auto-save selects ── */
            var visibilitySelects = document.querySelectorAll('select[data-auto-save-visibility="1"]');
            visibilitySelects.forEach(function (selectEl) {
                selectEl.addEventListener('change', function () {
                    if (selectEl.disabled) return;
                    var payload = new FormData();
                    payload.append('_token', '{{ csrf_token() }}');
                    payload.append('_method', 'PATCH');
                    payload.append(selectEl.name, String(selectEl.value || ''));
                    fetch('{{ route('settings.profile.update') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: payload
                    }).catch(function () {
                        // keep UI responsive even if network fails
                    }).finally(function () {
                        if (selectEl.id === 'phone_visible') {
                            var phoneInputs = document.querySelectorAll('.settings-phone-input');
                            phoneInputs.forEach(function (field) { field.disabled = true; });
                            var phoneEditBtn = document.querySelector('.settings-edit-btn[data-target-group=".settings-phone-input"]');
                            setEditButtonState(phoneEditBtn, false);
                        } else {
                            selectEl.disabled = true;
                            var singleEditBtn = document.querySelector('.settings-edit-btn[data-target="' + selectEl.id + '"]');
                            setEditButtonState(singleEditBtn, false);
                        }
                    });
                });
            });

            /* ── Profile form: enable save when something changes ── */
            var accountProfileForm = document.getElementById('accountProfileForm');
            var updateProfileBtn   = document.getElementById('updateProfileBtn');
            if (accountProfileForm && updateProfileBtn) {
                var trackedTextInputs = accountProfileForm.querySelectorAll('input[type="text"], input[type="email"]');
                var initialValues = new Map();
                trackedTextInputs.forEach(function (inputEl) { initialValues.set(inputEl, String(inputEl.value || '')); });

                function updateProfileSubmitState() {
                    var hasChanges = false;
                    trackedTextInputs.forEach(function (inputEl) {
                        if (hasChanges) return;
                        if (initialValues.get(inputEl) !== String(inputEl.value || '')) hasChanges = true;
                    });
                    updateProfileBtn.disabled = !hasChanges;
                }

                trackedTextInputs.forEach(function (inputEl) {
                    inputEl.addEventListener('input', updateProfileSubmitState);
                    inputEl.addEventListener('change', updateProfileSubmitState);
                });
                accountProfileForm.addEventListener('submit', function () { updateProfileBtn.disabled = true; });
                updateProfileSubmitState();
            }

            /* ── Payment form: enable save when something changes ── */
            var paymentDetailsForm  = document.getElementById('paymentDetailsForm');
            var savePaymentDetailsBtn = document.getElementById('savePaymentDetailsBtn');
            if (paymentDetailsForm && savePaymentDetailsBtn) {
                var paymentTrackedFields = paymentDetailsForm.querySelectorAll('input, select, textarea');
                var paymentInitialValues = new Map();
                paymentTrackedFields.forEach(function (field) {
                    paymentInitialValues.set(field, field.type === 'file' ? '' : String(field.value || ''));
                });

                function updatePaymentSubmitState() {
                    var hasChanges = false;
                    paymentTrackedFields.forEach(function (field) {
                        if (hasChanges) return;
                        if (field.type === 'file') { if (field.files && field.files.length > 0) hasChanges = true; return; }
                        if (paymentInitialValues.get(field) !== String(field.value || '')) hasChanges = true;
                    });
                    savePaymentDetailsBtn.disabled = !hasChanges;
                }

                paymentTrackedFields.forEach(function (field) {
                    field.addEventListener('input', updatePaymentSubmitState);
                    field.addEventListener('change', updatePaymentSubmitState);
                });
                paymentDetailsForm.addEventListener('submit', function () { savePaymentDetailsBtn.disabled = true; });
                updatePaymentSubmitState();
            }

            /* ── Password form: enable save when something typed ── */
            var passwordForm    = document.getElementById('passwordForm');
            var savePasswordBtn = document.getElementById('savePasswordBtn');
            if (passwordForm && savePasswordBtn) {
                var passwordFields = passwordForm.querySelectorAll('input[type="password"]');

                function updatePasswordSubmitState() {
                    var hasValue = false;
                    passwordFields.forEach(function (field) {
                        if (hasValue) return;
                        if (String(field.value || '').trim() !== '') hasValue = true;
                    });
                    savePasswordBtn.disabled = !hasValue;
                }

                passwordFields.forEach(function (field) {
                    field.addEventListener('input', updatePasswordSubmitState);
                    field.addEventListener('change', updatePasswordSubmitState);
                });
                passwordForm.addEventListener('submit', function () { savePasswordBtn.disabled = true; });
                updatePasswordSubmitState();
            }

            /* ── Re-enable disabled (but not locked) fields on form submit ── */
            var profileForms = document.querySelectorAll('form.settings-form');
            profileForms.forEach(function (form) {
                form.addEventListener('submit', function () {
                    form.querySelectorAll('input:disabled').forEach(function (field) {
                        if (field.getAttribute('data-locked') === '1') return;
                        field.disabled = false;
                    });
                });
            });

            /* ── Mobile tab strip + rail link active highlighting ── */
            var tabButtons   = document.querySelectorAll('[data-section-tab]');
            var railLinks    = document.querySelectorAll('[data-section-link]');
            var sectionCards = document.querySelectorAll('[data-section]');

            function activateSection(sectionKey) {
                tabButtons.forEach(function (btn) {
                    btn.classList.toggle('is-active', btn.getAttribute('data-section-tab') === sectionKey);
                });
                railLinks.forEach(function (link) {
                    link.classList.toggle('is-active', link.getAttribute('data-section-link') === sectionKey);
                });
            }

            tabButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var key = btn.getAttribute('data-section-tab');
                    activateSection(key);
                    var target = document.getElementById('section-' + key);
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            railLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    activateSection(link.getAttribute('data-section-link'));
                });
            });

            /* Intersection observer to sync rail + tabs while scrolling */
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            var key = entry.target.getAttribute('data-section');
                            if (key) activateSection(key);
                        }
                    });
                }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });

                sectionCards.forEach(function (card) { observer.observe(card); });
            }

            /* ── Auto-dismiss status banner ── */
            var statusBanner = document.getElementById('statusBanner');
            if (statusBanner) {
                setTimeout(function () { statusBanner.classList.add('hide'); }, 4000);
            }
        })();
    </script>
@endsection
