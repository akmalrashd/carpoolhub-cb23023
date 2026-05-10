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
        .profile-page { display: grid; gap: 12px; }
        .profile-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
        }
        .profile-header-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 30px;
            color: #0f172a;
            line-height: 1.05;
        }
        .profile-header-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }
        .profile-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .profile-section-title {
            margin: 0 0 12px;
            font-size: 18px;
            font-family: Poppins, sans-serif;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .profile-section-head {
            margin: 0 0 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            min-height: 44px;
        }
        .profile-avatar-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #334155;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 18px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .profile-avatar-button {
            cursor: pointer;
            position: relative;
            overflow: visible;
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }
        .profile-avatar-button:hover {
            border-color: #93c5fd;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(15, 23, 42, 0.12);
        }
        .profile-avatar-cam {
            position: absolute;
            right: -6px;
            bottom: -6px;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #dbe2ea;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .profile-avatar-note {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }
        .profile-form { display: grid; gap: 10px; }
        .profile-field { display: grid; gap: 6px; }
        .profile-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            margin: 0;
        }
        .profile-input-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            align-items: center;
        }
        .profile-phone-group {
            display: flex;
            align-items: stretch;
            width: 100%;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #f8fafc;
            overflow: hidden;
            transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
        }
        .profile-phone-group:focus-within {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            background: #fff;
        }
        .profile-select {
            width: var(--country-width, 146px);
            flex: 0 0 auto;
            border: 0;
            border-right: 1px solid #e2e8f0;
            border-radius: 0;
            background: transparent;
            color: #0f172a;
            padding: 10px 20px 10px 12px;
            font-size: 13px;
            outline: none;
            transition: background-color .16s ease, color .16s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath fill='%2364758b' d='M1.17.97a.75.75 0 0 1 1.06 0L5 3.74 7.77.97a.75.75 0 0 1 1.06 1.06L5.53 5.33a.75.75 0 0 1-1.06 0L1.17 2.03a.75.75 0 0 1 0-1.06z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 6px center;
            background-size: 10px 7px;
        }
        .profile-select:disabled {
            background-color: #eef2f7;
            color: #64748b;
            cursor: not-allowed;
        }
        .profile-select:focus {
            background-color: #fff;
        }
        .profile-phone-group .profile-input {
            flex: 1 1 auto;
            min-width: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            color: #1e293b;
        }
        .profile-email-group {
            display: flex;
            align-items: stretch;
            width: 100%;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #f8fafc;
            overflow: hidden;
            transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
        }
        .profile-email-group:focus-within {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            background: #fff;
        }
        .profile-email-group .profile-input {
            flex: 1 1 auto;
            min-width: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }
        .profile-email-group .profile-input:focus {
            border: 0;
            box-shadow: none;
            background: #fff;
        }
        .profile-visibility-select {
            width: 136px;
            flex: 0 0 auto;
            border: 0;
            border-left: 1px solid #e2e8f0;
            border-radius: 0;
            background-color: transparent;
            color: #0f172a;
            padding: 10px 20px 10px 10px;
            font-size: 11px;
            font-weight: 700;
            text-align: left;
            text-align-last: left;
            outline: none;
            transition: background-color .16s ease, color .16s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath fill='%2364758b' d='M1.17.97a.75.75 0 0 1 1.06 0L5 3.74 7.77.97a.75.75 0 0 1 1.06 1.06L5.53 5.33a.75.75 0 0 1-1.06 0L1.17 2.03a.75.75 0 0 1 0-1.06z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 6px center;
            background-size: 10px 7px;
        }
        .profile-visibility-select:disabled {
            background-color: #eef2f7;
            color: #64748b;
            cursor: not-allowed;
        }
        .profile-visibility-select:focus {
            background-color: #fff;
        }
        .profile-phone-group .profile-input:focus {
            border: 0;
            box-shadow: none;
            background: #fff;
        }
        .profile-input {
            width: 100%;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #f8fafc;
            color: #0f172a;
            padding: 10px 11px;
            font-size: 14px;
            outline: none;
            transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
        }
        .profile-input:disabled {
            background: #eef2f7;
            color: #64748b;
            border-color: #e2e8f0;
            cursor: not-allowed;
        }
        .profile-input:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            background: #fff;
        }
        .profile-edit-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform .16s ease, border-color .16s ease, color .16s ease, background-color .16s ease;
        }
        .profile-edit-btn:hover {
            border-color: #93c5fd;
            color: #1d4ed8;
            background: #eff6ff;
            transform: translateY(-1px);
        }
        .password-toggle-btn {
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #fff;
            color: #334155;
            font-weight: 700;
            font-size: 12px;
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            cursor: pointer;
            min-height: 44px;
            transition: border-color .16s ease, color .16s ease, background-color .16s ease;
        }
        .password-toggle-btn:hover {
            border-color: #93c5fd;
            background: #eff6ff;
            color: #1d4ed8;
        }
        .password-collapse {
            display: none;
            gap: 10px;
        }
        .password-collapse.is-open {
            display: grid;
        }
        .payment-details-collapse {
            display: none;
            gap: 10px;
        }
        .payment-details-collapse.is-open {
            display: grid;
        }
        .profile-file-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .profile-help {
            margin-top: 2px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
        }
        .payment-qr-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .payment-qr-card {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            display: grid;
            gap: 8px;
        }
        .payment-qr-title {
            margin: 0;
            color: #334155;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .03em;
            font-weight: 700;
        }
        .payment-qr-preview {
            width: 100%;
            height: 168px;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .payment-qr-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            background: #fff;
        }
        .payment-qr-empty {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            padding: 0 8px;
        }
        .profile-btn {
            border: 1px solid #0f172a;
            border-radius: 10px;
            background: #0f172a;
            color: #ffffff;
            font-weight: 800;
            font-size: 14px;
            padding: 10px 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            width: fit-content;
            cursor: pointer;
            transition: transform .16s ease, box-shadow .16s ease, background-color .16s ease;
        }
        .profile-btn:hover {
            background: #020617;
            box-shadow: 0 8px 14px rgba(15, 23, 42, 0.25);
            transform: translateY(-1px);
        }
        .profile-btn:disabled {
            background: #cbd5e1;
            border-color: #cbd5e1;
            color: #64748b;
            box-shadow: none;
            transform: none;
            cursor: not-allowed;
        }
        .profile-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
            line-height: 1.4;
        }
        @media (min-width: 880px) {
            .profile-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .profile-card { padding: 16px; }
            .payment-qr-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

    <div class="profile-page">
        <section class="profile-card">
            <h1 class="profile-header-title">Profile</h1>
            <p class="profile-header-subtitle">Manage your account details and security settings.</p>
        </section>

        @if($errors->any())
            <div class="profile-error">{{ $errors->first() }}</div>
        @endif

        <div class="profile-grid">
            <section class="profile-card">
                <h2 class="profile-section-title"><i class="fas fa-user-circle"></i> Account Details</h2>

                <div class="profile-avatar-row">
                    <label for="profile_photo" class="profile-avatar profile-avatar-button" title="Click to update profile photo">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Profile Photo">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                        <span class="profile-avatar-cam"><i class="fas fa-camera"></i></span>
                    </label>
                    <div class="profile-avatar-note">
                        Muat naik JPG/PNG (maks 2MB)
                        <div class="profile-help" id="profilePhotoHint">Click the profile frame to choose a photo</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="profile-form" id="accountProfileForm">
                    @csrf
                    @method('PATCH')

                    <input id="profile_photo" type="file" name="profile_photo" accept="image/*" class="profile-file-hidden">

                    <div class="profile-field">
                        <label for="name" class="profile-label">Name</label>
                        <div class="profile-input-row">
                            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="profile-input" disabled>
                            <button type="button" class="profile-edit-btn" data-target="name" aria-label="Edit name">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                    </div>

                    <div class="profile-field">
                        <label for="email" class="profile-label">Email</label>
                        <div class="profile-input-row">
                            <div class="profile-email-group">
                                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="profile-input" data-locked="1" disabled>
                                <select id="email_visible" name="email_visible" class="profile-visibility-select" data-auto-save-visibility="1" disabled>
                                    <option value="visible_public" {{ $selectedEmailVisibility === 'visible_public' ? 'selected' : '' }}>Visible to Public</option>
                                    <option value="visible_friend" {{ $selectedEmailVisibility === 'visible_friend' ? 'selected' : '' }}>Visible to Connections</option>
                                    <option value="unvisible" {{ $selectedEmailVisibility === 'unvisible' ? 'selected' : '' }}>Tersembunyi</option>
                                </select>
                            </div>
                            <button type="button" class="profile-edit-btn" data-target="email_visible" aria-label="Edit email visibility">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                        <div class="profile-help">Email is locked and cannot be edited.</div>
                    </div>

                    <div class="profile-field">
                        <label for="whatsapp_number" class="profile-label">Phone</label>
                        <div class="profile-input-row">
                            <div class="profile-phone-group">
                                <select id="whatsapp_country_code" name="whatsapp_country_code" class="profile-select profile-phone-input" disabled>
                                    @foreach($countryOptions as $code => $label)
                                        <option value="{{ $code }}" {{ $selectedCode === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input
                                    id="whatsapp_number"
                                    type="text"
                                    name="whatsapp_number"
                                    value="{{ $selectedNumber }}"
                                    class="profile-input profile-phone-input"
                                    inputmode="numeric"
                                    autocomplete="tel"
                                    placeholder="012-345 6789"
                                    disabled
                                >
                                <select id="phone_visible" name="phone_visible" class="profile-visibility-select profile-phone-input" data-auto-save-visibility="1" disabled>
                                    <option value="visible_public" {{ $selectedPhoneVisibility === 'visible_public' ? 'selected' : '' }}>Visible to Public</option>
                                    <option value="visible_friend" {{ $selectedPhoneVisibility === 'visible_friend' ? 'selected' : '' }}>Visible to Connections</option>
                                    <option value="unvisible" {{ $selectedPhoneVisibility === 'unvisible' ? 'selected' : '' }}>Tersembunyi</option>
                                </select>
                            </div>
                            <button type="button" class="profile-edit-btn" data-target-group=".profile-phone-input" aria-label="Edit phone">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                        <div class="profile-help">Used for WhatsApp contact buttons in trips and payments.</div>
                    </div>

                    <button type="submit" class="profile-btn" id="updateProfileBtn" disabled>
                        <i class="fas fa-floppy-disk"></i>
                        <span>Update Profile</span>
                    </button>
                </form>
            </section>

            <section class="profile-card">
                <div class="profile-section-head">
                    <h2 class="profile-section-title" style="margin:0;"><i class="fas fa-wallet"></i> Payment Details</h2>
                    <button type="button" class="password-toggle-btn" id="paymentDetailsToggleBtn" aria-label="Toggle payment details form" aria-expanded="false" aria-controls="paymentDetailsCollapse">
                        <i class="fas fa-pen" id="paymentDetailsToggleIcon"></i>
                    </button>
                </div>

                <div class="payment-details-collapse" id="paymentDetailsCollapse">
                    <p class="profile-help" style="margin-top:-6px; margin-bottom:8px;">
                        Set your bank/e-wallet, recipient name, account number, and QR codes (DuitNow / Touch 'n Go) for future trip payment details.
                    </p>

                    <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="profile-form" id="paymentDetailsForm">
                        @csrf
                        @method('PATCH')

                        <div class="profile-field">
                            <label for="payment_bank_name" class="profile-label">Bank / Wallet</label>
                            <div class="profile-input-row">
                                <select
                                    id="payment_bank_name"
                                    name="payment_bank_name"
                                    class="profile-input payment-details-input"
                                    disabled
                                >
                                    <option value="">Select bank / e-wallet</option>
                                    @foreach($paymentBankOptions as $bankName)
                                        <option value="{{ $bankName }}" {{ $selectedPaymentBank === $bankName ? 'selected' : '' }}>{{ $bankName }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="profile-edit-btn payment-edit-btn" data-target="payment_bank_name" aria-label="Edit payment bank or wallet">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                        </div>

                        <div class="profile-field">
                            <label for="payment_account_name" class="profile-label">Account Holder Name</label>
                            <div class="profile-input-row">
                                <input
                                    id="payment_account_name"
                                    type="text"
                                    name="payment_account_name"
                                    value="{{ old('payment_account_name', $user->payment_account_name) }}"
                                    class="profile-input payment-details-input"
                                    placeholder="e.g. Ahmad Hakimi"
                                    disabled
                                >
                                <button type="button" class="profile-edit-btn payment-edit-btn" data-target="payment_account_name" aria-label="Edit payment account name">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                        </div>

                        <div class="profile-field">
                            <label for="payment_account_number" class="profile-label">Account Number Payments</label>
                            <div class="profile-input-row">
                                <input
                                    id="payment_account_number"
                                    type="text"
                                    name="payment_account_number"
                                    value="{{ old('payment_account_number', $user->payment_account_number) }}"
                                    class="profile-input payment-details-input"
                                    inputmode="numeric"
                                    placeholder="e.g. 01112844464"
                                    disabled
                                >
                                <button type="button" class="profile-edit-btn payment-edit-btn" data-target="payment_account_number" aria-label="Edit payment account number">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                        </div>

                        <div class="payment-qr-grid">
                            <div class="payment-qr-card">
                                <h3 class="payment-qr-title">DuitNow QR</h3>
                                <div class="payment-qr-preview">
                                    @if($duitnowQrUrl)
                                        <img src="{{ $duitnowQrUrl }}" alt="DuitNow QR">
                                    @else
                                        <span class="payment-qr-empty">No QR uploaded</span>
                                    @endif
                                </div>
                                <div class="profile-input-row">
                                    <input
                                        id="payment_qr_duitnow"
                                        type="file"
                                        name="payment_qr_duitnow"
                                        accept="image/*"
                                        class="profile-input payment-details-input"
                                        disabled
                                    >
                                    <button type="button" class="profile-edit-btn payment-edit-btn" data-target="payment_qr_duitnow" aria-label="Edit DuitNow QR">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="payment-qr-card">
                                <h3 class="payment-qr-title">Touch 'n Go QR</h3>
                                <div class="payment-qr-preview">
                                    @if($tngQrUrl)
                                        <img src="{{ $tngQrUrl }}" alt="Touch 'n Go QR">
                                    @else
                                        <span class="payment-qr-empty">No QR uploaded</span>
                                    @endif
                                </div>
                                <div class="profile-input-row">
                                    <input
                                        id="payment_qr_tng"
                                        type="file"
                                        name="payment_qr_tng"
                                        accept="image/*"
                                        class="profile-input payment-details-input"
                                        disabled
                                    >
                                    <button type="button" class="profile-edit-btn payment-edit-btn" data-target="payment_qr_tng" aria-label="Edit Touch n Go QR">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="profile-btn" id="savePaymentDetailsBtn" disabled>
                            <i class="fas fa-floppy-disk"></i>
                            <span>Save Payment Details</span>
                        </button>
                    </form>
                </div>
            </section>

            <section class="profile-card">
                <div class="profile-section-head">
                    <h2 class="profile-section-title" style="margin:0;"><i class="fas fa-shield-halved"></i> Change Password</h2>
                    <button type="button" class="password-toggle-btn" id="passwordToggleBtn" aria-label="Toggle password form" aria-expanded="false" aria-controls="passwordCollapse">
                        <i class="fas fa-pen" id="passwordToggleIcon"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('settings.password.update') }}" class="profile-form" id="passwordForm">
                    @csrf
                    @method('PATCH')

                    <div class="password-collapse" id="passwordCollapse">
                        <div class="profile-field">
                            <label for="current_password" class="profile-label">Current Password</label>
                            <input id="current_password" type="password" name="current_password" required class="profile-input" disabled>
                        </div>

                        <div class="profile-field">
                            <label for="new_password" class="profile-label">New Password</label>
                            <input id="new_password" type="password" name="new_password" required class="profile-input" disabled>
                        </div>

                        <div class="profile-field">
                            <label for="new_password_confirmation" class="profile-label">Confirm New Password</label>
                            <input id="new_password_confirmation" type="password" name="new_password_confirmation" required class="profile-input" disabled>
                        </div>

                        <button type="submit" class="profile-btn" id="savePasswordBtn" disabled>
                            <i class="fas fa-key"></i>
                            <span>Save Password</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.12.4/build/js/utils.js"></script>
    <script>
        (function () {
            var phoneCountry = document.getElementById('whatsapp_country_code');
            var editButtons = document.querySelectorAll('.profile-edit-btn');

            function setEditButtonState(button, isOpen) {
                if (!button) return;
                var icon = button.querySelector('i');
                if (icon) {
                    icon.className = isOpen ? 'fas fa-xmark' : 'fas fa-pen';
                }
                button.setAttribute('data-open', isOpen ? '1' : '0');
            }

            editButtons.forEach(function (button) {
                setEditButtonState(button, false);
                button.addEventListener('click', function () {
                    var targetGroup = button.getAttribute('data-target-group');
                    if (targetGroup) {
                        var groupedInputs = document.querySelectorAll(targetGroup);
                        if (!groupedInputs.length) return;
                        var isOpen = button.getAttribute('data-open') === '1';

                        groupedInputs.forEach(function (field) {
                            field.disabled = isOpen;
                        });

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

            var profilePhotoInput = document.getElementById('profile_photo');
            var profilePhotoHint = document.getElementById('profilePhotoHint');
            if (profilePhotoInput && profilePhotoHint) {
                profilePhotoInput.addEventListener('change', function () {
                    if (!profilePhotoInput.files || !profilePhotoInput.files.length) return;
                    profilePhotoHint.textContent = 'Selected: ' + profilePhotoInput.files[0].name;
                });
            }

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

            function fallbackExampleByCode(dialCode) {
                var map = {
                    '+60': '0123456789',
                    '+65': '81234567',
                    '+62': '081234567890',
                    '+66': '0812345678',
                    '+63': '09171234567',
                    '+81': '09012345678',
                    '+82': '01012345678',
                    '+44': '07123456789',
                    '+1': '2015550123',
                    '+91': '09876543210',
                    '+86': '13123456789'
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
                            iso2,
                            true,
                            window.intlTelInputUtils.numberType.MOBILE
                        ) || '';
                    } catch (_error) {}
                }

                if (example) {
                    var dialDigits = String(dialCode || '').replace(/[^\d]/g, '');
                    var exampleDigits = String(example || '').replace(/[^\d]/g, '');
                    if (dialDigits && exampleDigits.startsWith(dialDigits)) {
                        exampleDigits = exampleDigits.slice(dialDigits.length);
                    }
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
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
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
                        options.push({
                            iso2: iso2,
                            countryName: countryName,
                            dialCode: dialCode,
                            label: countryName + ' (' + dialCode + ')',
                        });
                    });

                    var seen = {};
                    options = options.filter(function (item) {
                        var key = item.iso2;
                        if (seen[key]) return false;
                        seen[key] = true;
                        return true;
                    }).sort(function (a, b) {
                        if (a.iso2 === 'MY') return -1;
                        if (b.iso2 === 'MY') return 1;
                        return a.countryName.localeCompare(b.countryName);
                    });

                    if (!options.length) return;

                    var hasSelectedCode = options.some(function (item) {
                        return item.dialCode === selectedCode;
                    });
                    if (!hasSelectedCode) selectedCode = '+60';

                    phoneCountry.innerHTML = options.map(function (item) {
                        var selected = item.dialCode === selectedCode || (item.iso2 === 'MY' && selectedCode === '+60');
                        return '<option value="' + escapeHtml(item.dialCode) + '" data-iso="' + escapeHtml(item.iso2.toLowerCase()) + '"' + (selected ? ' selected' : '') + '>' + escapeHtml(item.label) + '</option>';
                    }).join('');
                } catch (_error) {
                    // keep fallback options already rendered server-side
                } finally {
                    updateCountrySelectWidth();
                    updateWhatsappPlaceholder();
                }
            }

            var passwordToggleBtn = document.getElementById('passwordToggleBtn');
            var passwordToggleIcon = document.getElementById('passwordToggleIcon');
            var passwordCollapse = document.getElementById('passwordCollapse');
            var passwordInputs = passwordCollapse ? passwordCollapse.querySelectorAll('input') : [];
            var paymentDetailsToggleBtn = document.getElementById('paymentDetailsToggleBtn');
            var paymentDetailsToggleIcon = document.getElementById('paymentDetailsToggleIcon');
            var paymentDetailsCollapse = document.getElementById('paymentDetailsCollapse');
            var paymentInputs = paymentDetailsCollapse ? paymentDetailsCollapse.querySelectorAll('.payment-details-input') : [];
            var paymentEditButtons = paymentDetailsCollapse ? paymentDetailsCollapse.querySelectorAll('.payment-edit-btn') : [];

            function setPasswordPanel(open) {
                if (!passwordCollapse || !passwordToggleBtn || !passwordToggleIcon) return;
                passwordCollapse.classList.toggle('is-open', open);
                passwordToggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                passwordToggleBtn.setAttribute('aria-label', open ? 'Close password form' : 'Open password form');
                passwordToggleIcon.className = open ? 'fas fa-xmark' : 'fas fa-pen';
                passwordInputs.forEach(function (input) {
                    input.disabled = !open;
                    if (!open) {
                        input.value = '';
                    }
                });
                if (!open) {
                    var savePasswordBtnEl = document.getElementById('savePasswordBtn');
                    if (savePasswordBtnEl) savePasswordBtnEl.disabled = true;
                }
                if (open && passwordInputs.length) {
                    passwordInputs[0].focus();
                }
            }

            if (passwordToggleBtn) {
                passwordToggleBtn.addEventListener('click', function () {
                    var isOpen = passwordCollapse && passwordCollapse.classList.contains('is-open');
                    setPasswordPanel(!isOpen);
                });
                setPasswordPanel(false);
            }

            function setPaymentDetailsPanel(open) {
                if (!paymentDetailsCollapse || !paymentDetailsToggleBtn || !paymentDetailsToggleIcon) return;
                paymentDetailsCollapse.classList.toggle('is-open', open);
                paymentDetailsToggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                paymentDetailsToggleBtn.setAttribute('aria-label', open ? 'Close payment details form' : 'Open payment details form');
                paymentDetailsToggleIcon.className = open ? 'fas fa-xmark' : 'fas fa-pen';

                if (!open) {
                    paymentInputs.forEach(function (input) {
                        input.disabled = true;
                    });
                    paymentEditButtons.forEach(function (btn) {
                        setEditButtonState(btn, false);
                    });
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

            if (phoneCountry) {
                phoneCountry.addEventListener('change', function () {
                    updateCountrySelectWidth();
                    updateWhatsappPlaceholder();
                });
            }
            populateAllCountries();

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
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: payload
                    }).catch(function () {
                        // keep UI responsive even if network fails; user can still save manually
                    }).finally(function () {
                        if (selectEl.id === 'phone_visible') {
                            var phoneInputs = document.querySelectorAll('.profile-phone-input');
                            phoneInputs.forEach(function (field) {
                                field.disabled = true;
                            });
                            var phoneEditBtn = document.querySelector('.profile-edit-btn[data-target-group=".profile-phone-input"]');
                            setEditButtonState(phoneEditBtn, false);
                        } else {
                            selectEl.disabled = true;
                            var singleEditBtn = document.querySelector('.profile-edit-btn[data-target="' + selectEl.id + '"]');
                            setEditButtonState(singleEditBtn, false);
                        }
                    });
                });
            });

            var accountProfileForm = document.getElementById('accountProfileForm');
            var updateProfileBtn = document.getElementById('updateProfileBtn');
            if (accountProfileForm && updateProfileBtn) {
                var trackedTextInputs = accountProfileForm.querySelectorAll('input[type="text"], input[type="email"]');
                var initialValues = new Map();

                trackedTextInputs.forEach(function (inputEl) {
                    initialValues.set(inputEl, String(inputEl.value || ''));
                });

                function updateProfileSubmitState() {
                    var hasChanges = false;
                    trackedTextInputs.forEach(function (inputEl) {
                        if (hasChanges) return;
                        var before = initialValues.get(inputEl);
                        var now = String(inputEl.value || '');
                        if (before !== now) {
                            hasChanges = true;
                        }
                    });
                    updateProfileBtn.disabled = !hasChanges;
                }

                trackedTextInputs.forEach(function (inputEl) {
                    inputEl.addEventListener('input', updateProfileSubmitState);
                    inputEl.addEventListener('change', updateProfileSubmitState);
                });

                accountProfileForm.addEventListener('submit', function () {
                    updateProfileBtn.disabled = true;
                });

                updateProfileSubmitState();
            }

            var paymentDetailsForm = document.getElementById('paymentDetailsForm');
            var savePaymentDetailsBtn = document.getElementById('savePaymentDetailsBtn');
            if (paymentDetailsForm && savePaymentDetailsBtn) {
                var paymentTrackedFields = paymentDetailsForm.querySelectorAll('input, select, textarea');
                var paymentInitialValues = new Map();

                paymentTrackedFields.forEach(function (field) {
                    if (field.type === 'file') {
                        paymentInitialValues.set(field, '');
                        return;
                    }
                    paymentInitialValues.set(field, String(field.value || ''));
                });

                function updatePaymentSubmitState() {
                    var hasChanges = false;
                    paymentTrackedFields.forEach(function (field) {
                        if (hasChanges) return;
                        if (field.type === 'file') {
                            if (field.files && field.files.length > 0) {
                                hasChanges = true;
                            }
                            return;
                        }
                        var before = paymentInitialValues.get(field);
                        var now = String(field.value || '');
                        if (before !== now) {
                            hasChanges = true;
                        }
                    });
                    savePaymentDetailsBtn.disabled = !hasChanges;
                }

                paymentTrackedFields.forEach(function (field) {
                    field.addEventListener('input', updatePaymentSubmitState);
                    field.addEventListener('change', updatePaymentSubmitState);
                });

                paymentDetailsForm.addEventListener('submit', function () {
                    savePaymentDetailsBtn.disabled = true;
                });

                updatePaymentSubmitState();
            }

            var passwordForm = document.getElementById('passwordForm');
            var savePasswordBtn = document.getElementById('savePasswordBtn');
            if (passwordForm && savePasswordBtn) {
                var passwordFields = passwordForm.querySelectorAll('input[type="password"]');

                function updatePasswordSubmitState() {
                    var hasValue = false;
                    passwordFields.forEach(function (field) {
                        if (hasValue) return;
                        if (String(field.value || '').trim() !== '') {
                            hasValue = true;
                        }
                    });
                    savePasswordBtn.disabled = !hasValue;
                }

                passwordFields.forEach(function (field) {
                    field.addEventListener('input', updatePasswordSubmitState);
                    field.addEventListener('change', updatePasswordSubmitState);
                });

                passwordForm.addEventListener('submit', function () {
                    savePasswordBtn.disabled = true;
                });

                updatePasswordSubmitState();
            }

            var profileForms = document.querySelectorAll('form.profile-form');
            profileForms.forEach(function (form) {
                form.addEventListener('submit', function () {
                    var disabledInputs = form.querySelectorAll('input:disabled');
                    disabledInputs.forEach(function (field) {
                        if (field.getAttribute('data-locked') === '1') return;
                        field.disabled = false;
                    });
                });
            });
        })();
    </script>
@endsection
