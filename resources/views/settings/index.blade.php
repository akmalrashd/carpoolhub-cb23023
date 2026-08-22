@extends('layouts.app')

@section('content')
    @php
        $photoUrl = $user->profile_photo_url;
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

        // Profile completeness — computed from real fields, not a fake trust badge.
        // Name and email are already required at signup, so they count as done from
        // the start instead of the ring showing a discouraging 0% on every fresh account.
        $completionItems = [
            ['label' => 'Full name', 'done' => trim((string) $user->name) !== '', 'tab' => 'profile'],
            ['label' => 'Email address', 'done' => trim((string) $user->email) !== '', 'tab' => 'profile'],
            ['label' => 'Profile photo', 'done' => (bool) $photoUrl, 'tab' => 'profile'],
            ['label' => 'Phone number', 'done' => $phoneDigits !== '', 'tab' => 'profile'],
        ];
        // Payment Methods is a driver-only concern (you only need to receive fares
        // if you drive), so it's the only tab hidden for passengers entirely.
        if ($isDriverOrAdmin) {
            $completionItems[] = ['label' => 'Payment method', 'done' => (bool) ($user->payment_account_number || $duitnowQrUrl || $tngQrUrl), 'tab' => 'payment'];
            $completionItems[] = ['label' => 'Vehicle details', 'done' => (bool) ($user->vehicle_model && $user->vehicle_plate), 'tab' => 'profile'];
            $completionItems[] = ['label' => 'Verification documents', 'done' => (bool) ($user->driving_license_photo && $user->selfie_photo), 'tab' => 'profile'];
        }
        $completionDone = count(array_filter($completionItems, fn($i) => $i['done']));
        $completionTotal = count($completionItems);
        $completionPercent = $completionTotal > 0 ? (int) round($completionDone / $completionTotal * 100) : 100;
        $missingItems = array_values(array_filter($completionItems, fn($i) => ! $i['done']));
        $firstMissingTab = $missingItems[0]['tab'] ?? 'profile';

        // If the page reloaded with validation errors, work out which tab they belong to
        // so the user lands on the panel that actually needs fixing instead of always
        // defaulting back to Profile Details.
        $errorTab = '';
        if ($errors->any()) {
            $errorKeys = $errors->keys();
            if (array_intersect($errorKeys, ['current_password', 'new_password', 'new_password_confirmation'])) {
                $errorTab = 'security';
            } elseif (array_intersect($errorKeys, ['payment_account_name', 'payment_account_number', 'payment_bank_name', 'payment_qr_duitnow', 'payment_qr_tng'])) {
                $errorTab = 'payment';
            } else {
                $errorTab = 'profile';
            }
        }
    @endphp

    {{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
    <link rel="stylesheet" href="{{ asset('css/settings.css') }}?v={{ filemtime(public_path('css/settings.css')) }}">

    <div class="profile-page-container" data-error-tab="{{ $errorTab }}">
        {{-- Header --}}
        <div class="settings-header">
            <p class="pg-eyebrow">Account</p>
            <h1 class="pg-title">Settings & Profile</h1>
            <p class="pg-sub">Manage your personal information, payment methods, and account security.</p>
        </div>

        {{-- Success is already announced by the global toast in layouts/app.blade.php —
             a second static banner here just repeated the same message. --}}
        @if($errors->any())
            <div class="settings-alert error" role="alert" aria-live="assertive">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:16px;"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Hero Profile Card --}}
        <div class="settings-hero-card">
            <div class="settings-hero-top">
                <div class="settings-hero-avatar-wrap">
                    <div class="settings-hero-avatar" onclick="document.getElementById('avatarFileInput').click()" title="Click to upload profile photo">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="{{ $user->name }}">
                        @else
                            <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        @endif
                    </div>
                    <button type="button" class="avatar-cam-badge" onclick="document.getElementById('avatarFileInput').click()" title="Change photo" aria-label="Change profile photo">
                        <i class="fa-solid fa-camera"></i>
                    </button>
                </div>
                <div class="settings-hero-info">
                    <h2 class="settings-hero-name">
                        {{ $user->name }}
                    </h2>
                    <div class="settings-hero-role-line">
                        <span class="role-pill {{ strtolower($user->role ?? 'passenger') }}">
                            @if($user->role === 'driver')
                                <i class="fa-solid fa-car"></i> Driver
                            @elseif($user->role === 'admin')
                                <i class="fa-solid fa-user-shield"></i> Admin
                            @else
                                <i class="fa-solid fa-user"></i> Passenger
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Profile completeness — real, computed from actual saved fields. --}}
            <div class="settings-hero-completion">
                <div class="hero-completion-ring" style="--pct: {{ $completionPercent }}" role="img" aria-label="Profile {{ $completionPercent }}% complete">
                    <span>{{ $completionPercent }}%</span>
                </div>
                <div class="hero-completion-text">
                    @if($completionPercent >= 100)
                        <strong>Profile complete</strong>
                        <span>All your details are set up. Nice work!</span>
                    @else
                        <strong>Profile {{ $completionPercent }}% complete</strong>
                        <span>Missing: {{ implode(', ', array_column($missingItems, 'label')) }}</span>
                    @endif
                </div>
                @if($completionPercent < 100)
                    <button type="button" class="hero-completion-cta" onclick="switchSettingsTab('{{ $firstMissingTab }}')">
                        Complete now <i class="fa-solid fa-arrow-right"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Two-column shell: nav rail on desktop, scrollable tab strip on mobile --}}
        <div class="settings-shell">
            <div class="settings-nav" role="tablist" aria-label="Settings sections">
                <button type="button" class="settings-nav-btn is-active" id="nav-btn-profile" role="tab" aria-selected="true" aria-controls="panel-profile" onclick="switchSettingsTab('profile')">
                    <span class="nav-btn-icon"><i class="fa-solid fa-user"></i></span>
                    <span class="nav-btn-text">
                        <span class="nav-btn-label">Profile Details</span>
                        <span class="nav-btn-desc">Name, contact{{ $isDriverOrAdmin ? ', vehicle & docs' : '' }}</span>
                    </span>
                </button>
                @if($isDriverOrAdmin)
                    <button type="button" class="settings-nav-btn" id="nav-btn-payment" role="tab" aria-selected="false" aria-controls="panel-payment" onclick="switchSettingsTab('payment')">
                        <span class="nav-btn-icon"><i class="fa-solid fa-wallet"></i></span>
                        <span class="nav-btn-text">
                            <span class="nav-btn-label">Payment Methods</span>
                            <span class="nav-btn-desc">Bank account & QR codes</span>
                        </span>
                    </button>
                @endif
                <button type="button" class="settings-nav-btn" id="nav-btn-security" role="tab" aria-selected="false" aria-controls="panel-security" onclick="switchSettingsTab('security')">
                    <span class="nav-btn-icon"><i class="fa-solid fa-shield-halved"></i></span>
                    <span class="nav-btn-text">
                        <span class="nav-btn-label">Security</span>
                        <span class="nav-btn-desc">Login password & sessions</span>
                    </span>
                </button>
            </div>

        {{-- Panels Container --}}
        <div class="settings-content">

            {{-- ─────────────────────────────────────────────────────────────
                 TAB 1: PROFILE DETAILS
            ─────────────────────────────────────────────────────────────── --}}
            <div class="settings-panel-card is-active" id="panel-profile" role="tabpanel" aria-labelledby="nav-btn-profile">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-user-gear"></i> Personal Profile</h3>
                    <p class="panel-desc">Update your name, contact details, and visibility settings across CarpoolHub.</p>
                </div>

                <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" data-tab="profile">
                    @csrf
                    @method('PATCH')

                    {{-- Hidden Avatar Input triggered by Hero Avatar Camera button --}}
                    <input type="file" name="profile_photo" id="avatarFileInput" accept="image/*" class="sr-only" onchange="previewAvatar(this)">
                    <p class="avatar-pending-hint" id="avatarPendingHint" hidden>
                        <i class="fa-solid fa-circle-info"></i> New photo selected — click <strong>Save Profile Details</strong> below to apply it.
                    </p>
                    @error('profile_photo')
                        <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                    @enderror

                    <div class="form-grid">
                        <h4 class="form-section-title"><i class="fa-solid fa-id-badge"></i> Basic Information</h4>

                        {{-- Full Name --}}
                        <div class="form-group">
                            <label class="form-label" for="profileName">Full Name</label>
                            <div class="input-wrap @error('name') has-error @enderror">
                                <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                                <input type="text" id="profileName" name="name" class="input-field" value="{{ old('name', $user->name) }}" required placeholder="Enter your full name">
                            </div>
                            @error('name')
                                <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Email Address (Read-only) + Email Visibility --}}
                        <div class="form-group">
                            <label class="form-label" for="profileEmail">Email Address</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" id="profileEmail" class="input-field" value="{{ $user->email }}" disabled readonly>
                                <span class="input-icon" title="Email is locked"><i class="fa-solid fa-lock" style="font-size:12px;"></i></span>
                                <div class="vis-select-group">
                                    <span class="vis-select-label">Visible to</span>
                                    <select name="email_visible" class="input-field select-field vis-select" title="Who can see your email">
                                        <option value="visible_public" {{ $selectedEmailVisibility === 'visible_public' ? 'selected' : '' }}>Public</option>
                                        <option value="visible_friend" {{ $selectedEmailVisibility === 'visible_friend' ? 'selected' : '' }}>Connections Only</option>
                                        <option value="unvisible" {{ $selectedEmailVisibility === 'unvisible' ? 'selected' : '' }}>Private</option>
                                    </select>
                                </div>
                            </div>
                            <span class="field-hint">Email address is fixed to your account credentials.</span>
                        </div>

                        <h4 class="form-section-title"><i class="fa-solid fa-address-book"></i> Contact & Visibility</h4>

                        {{-- Phone / WhatsApp Number + Visibility --}}
                        <div class="form-group">
                            <label class="form-label" for="profilePhone">Phone / WhatsApp Number</label>
                            <div class="input-wrap @error('whatsapp_number') has-error @enderror">
                                <span class="input-icon"><i class="fa-brands fa-whatsapp"></i></span>
                                <select name="whatsapp_country_code" class="input-field select-field" style="width:110px; flex:0 0 auto; border-right:1px solid var(--hairline);" title="Country Code">
                                    @foreach($countryOptions as $code => $label)
                                        <option value="{{ $code }}" {{ $selectedCode === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="tel" id="profilePhone" name="whatsapp_number" class="input-field" value="{{ $selectedNumber }}" placeholder="e.g. 1110000011">
                                <div class="vis-select-group">
                                    <span class="vis-select-label">Visible to</span>
                                    <select name="phone_visible" class="input-field select-field vis-select" title="Who can see your phone number">
                                        <option value="visible_public" {{ $selectedPhoneVisibility === 'visible_public' ? 'selected' : '' }}>Public</option>
                                        <option value="visible_friend" {{ $selectedPhoneVisibility === 'visible_friend' ? 'selected' : '' }}>Connections Only</option>
                                        <option value="unvisible" {{ $selectedPhoneVisibility === 'unvisible' ? 'selected' : '' }}>Private</option>
                                    </select>
                                </div>
                            </div>
                            @error('whatsapp_number')
                                <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <span class="field-hint">Used for trip coordination — visibility controls who else on CarpoolHub can see it.</span>
                        </div>

                        {{-- Vehicle Details & Verification Documents (Driver & Admin) --}}
                        @if($isDriverOrAdmin)
                            <h4 class="form-section-title"><i class="fa-solid fa-car-side"></i> Vehicle & Verification</h4>

                            <div class="form-group">
                                <label class="form-label">Vehicle Details</label>
                                <div class="input-wrap @error('vehicle_model') has-error @enderror @error('vehicle_plate') has-error @enderror">
                                    <span class="input-icon"><i class="fa-solid fa-car"></i></span>
                                    <input type="text" name="vehicle_model" class="input-field" value="{{ old('vehicle_model', $user->vehicle_model) }}" placeholder="Model (e.g. Perodua Myvi 1.5)" style="border-right:1px solid var(--hairline);">
                                    <div class="vehicle-plate-group">
                                        <span class="input-icon"><i class="fa-solid fa-id-card"></i></span>
                                        <input type="text" name="vehicle_plate" class="input-field" value="{{ old('vehicle_plate', $user->vehicle_plate) }}" placeholder="Plate (e.g. VAB 1234)">
                                    </div>
                                </div>
                                @error('vehicle_model')
                                    <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                                @error('vehicle_plate')
                                    <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Driver Verification Documents — submitted once at registration, view-only here. --}}
                            <div style="margin-top:4px;">
                                <label class="form-label">Driver Verification Documents</label>
                                <p class="field-hint" style="margin:2px 0 10px;">Submitted when you registered. Contact support if you need to update them.</p>
                                <div class="qr-upload-grid">
                                    {{-- License Photo --}}
                                    <div class="qr-card qr-card-readonly">
                                        <div class="qr-preview-box">
                                            @if($user->driving_license_photo)
                                                <img src="{{ $user->driving_license_photo }}" alt="Driving License">
                                            @else
                                                <div class="qr-empty-icon"><i class="fa-solid fa-id-card"></i></div>
                                            @endif
                                        </div>
                                        <h4 class="qr-title">Driving License</h4>
                                        <p class="qr-sub">{{ $user->driving_license_photo ? 'On file' : 'Not submitted' }}</p>
                                    </div>

                                    {{-- Selfie Photo --}}
                                    <div class="qr-card qr-card-readonly">
                                        <div class="qr-preview-box">
                                            @if($user->selfie_photo)
                                                <img src="{{ $user->selfie_photo }}" alt="Selfie with License">
                                            @else
                                                <div class="qr-empty-icon"><i class="fa-solid fa-user-shield"></i></div>
                                            @endif
                                        </div>
                                        <h4 class="qr-title">Selfie Verification</h4>
                                        <p class="qr-sub">{{ $user->selfie_photo ? 'On file' : 'Not submitted' }}</p>
                                    </div>
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
                 TAB 2: PAYMENT METHODS & QR (driver & admin only — passengers
                 only ever pay drivers, they never need to receive fares)
            ─────────────────────────────────────────────────────────────── --}}
            @if($isDriverOrAdmin)
            <div class="settings-panel-card" id="panel-payment">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-wallet"></i> Payment Methods & QR</h3>
                    <p class="panel-desc">Configure your bank account and upload QR codes to collect passenger fares easily.</p>
                </div>

                <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" data-tab="payment">
                    @csrf
                    @method('PATCH')

                    <div class="settings-info-banner">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Add your bank or e-wallet details so trip fares can be paid to you directly by other members.</span>
                    </div>

                    <div class="form-grid">
                        <h4 class="form-section-title"><i class="fa-solid fa-building-columns"></i> Bank / E-Wallet Details</h4>

                        {{-- Bank / E-Wallet Name --}}
                        <div class="form-group">
                            <label class="form-label" for="paymentBank">Bank / E-Wallet Provider</label>
                            <div class="input-wrap @error('payment_bank_name') has-error @enderror">
                                <span class="input-icon"><i class="fa-solid fa-building-columns"></i></span>
                                <select id="paymentBank" name="payment_bank_name" class="input-field select-field">
                                    <option value="">Select Bank / E-Wallet...</option>
                                    @foreach($paymentBankOptions as $bank)
                                        <option value="{{ $bank }}" {{ $selectedPaymentBank === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('payment_bank_name')
                                <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Account Holder Name --}}
                        <div class="form-group">
                            <label class="form-label" for="paymentAccountName">Account Holder Name</label>
                            <div class="input-wrap @error('payment_account_name') has-error @enderror">
                                <span class="input-icon"><i class="fa-solid fa-id-card"></i></span>
                                <input type="text" id="paymentAccountName" name="payment_account_name" class="input-field" value="{{ old('payment_account_name', $user->payment_account_name) }}" placeholder="Full name as registered in bank account">
                            </div>
                            @error('payment_account_name')
                                <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Account / Phone Number --}}
                        <div class="form-group">
                            <label class="form-label" for="paymentAccountNumber">Account Number / DuitNow ID</label>
                            <div class="input-wrap @error('payment_account_number') has-error @enderror">
                                <span class="input-icon"><i class="fa-solid fa-credit-card"></i></span>
                                <input type="text" id="paymentAccountNumber" name="payment_account_number" class="input-field" value="{{ old('payment_account_number', $user->payment_account_number) }}" placeholder="e.g. 156012345678 or 01112844464">
                            </div>
                            @error('payment_account_number')
                                <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Interactive QR Code Upload Section --}}
                        <h4 class="form-section-title"><i class="fa-solid fa-qrcode"></i> Payment QR Codes</h4>
                        <div>
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
                                    @error('payment_qr_duitnow')
                                        <span class="field-error" style="justify-content:center;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
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
                                    @error('payment_qr_tng')
                                        <span class="field-error" style="justify-content:center;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
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
            @endif

            {{-- ─────────────────────────────────────────────────────────────
                 TAB 3: SECURITY & PASSWORD
            ─────────────────────────────────────────────────────────────── --}}
            <div class="settings-panel-card" id="panel-security">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-shield-halved"></i> Security & Password</h3>
                    <p class="panel-desc">Update your password to keep your account safe.</p>
                </div>

                <div class="settings-info-banner">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>For your security, updating your password signs you out of every other device. This device stays signed in.</span>
                </div>

                <form method="POST" action="{{ route('settings.password.update') }}" data-tab="security" id="passwordForm">
                    @csrf
                    @method('PATCH')

                    <div class="form-grid">
                        {{-- Current Password --}}
                        <div class="form-group">
                            <label class="form-label" for="currentPassword">Current Password</label>
                            <div class="input-wrap @error('current_password') has-error @enderror">
                                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" id="currentPassword" name="current_password" class="input-field" required placeholder="Enter current password" autocomplete="current-password">
                                <button type="button" class="pass-toggle-btn" onclick="togglePassVisibility('currentPassword', this)" aria-label="Show current password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        {{-- New Password --}}
                        <div class="form-group">
                            <label class="form-label" for="newPassword">New Password</label>
                            <div class="input-wrap @error('new_password') has-error @enderror">
                                <span class="input-icon"><i class="fa-solid fa-key"></i></span>
                                <input type="password" id="newPassword" name="new_password" class="input-field" required minlength="8" placeholder="Min 8, with uppercase, lowercase & a number" autocomplete="new-password" oninput="handlePasswordStrength(this.value)">
                                <button type="button" class="pass-toggle-btn" onclick="togglePassVisibility('newPassword', this)" aria-label="Show new password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <div class="pw-strength-meter" id="pwStrengthMeter" hidden>
                                <div class="pw-strength-bar"><span id="pwStrengthFill"></span></div>
                                <span class="pw-strength-label" id="pwStrengthLabel"></span>
                            </div>
                            @error('new_password')
                                <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Confirm New Password --}}
                        <div class="form-group">
                            <label class="form-label" for="confirmPassword">Confirm New Password</label>
                            <div class="input-wrap">
                                <span class="input-icon"><i class="fa-solid fa-shield-check"></i></span>
                                <input type="password" id="confirmPassword" name="new_password_confirmation" class="input-field" required placeholder="Re-enter new password" autocomplete="new-password" oninput="handlePasswordMatch()">
                                <button type="button" class="pass-toggle-btn" onclick="togglePassVisibility('confirmPassword', this)" aria-label="Show password confirmation">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <span class="field-match-hint" id="pwMatchHint"></span>
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
    </div>

    <script src="{{ asset('js/settings.js') }}?v={{ filemtime(public_path('js/settings.js')) }}"></script>
@endsection
