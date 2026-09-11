@extends('layouts.app')

@section('content')
    @php
        $photoUrl = $user->profile_photo_url;
        $duitnowQrUrl = $user->payment_qr_duitnow_url;
        $tngQrUrl = $user->payment_qr_tng_url;
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
        // Admin isn't subject to driver verification and can't create trips
        // (see TripController::ensureCanManage), so vehicle/license/payment
        // fields below are driver-only, not "driver or admin" like they used
        // to be — an admin account never had a legitimate use for them.
        $isDriver = $user->role === 'driver';

        // Profile completeness — computed from real fields, not a fake trust badge.
        // Name and email are already required at signup, so they count as done from
        // the start instead of the ring showing a discouraging 0% on every fresh account.
        $completionItems = [
            ['label' => 'Full name', 'done' => trim((string) $user->name) !== '', 'tab' => 'profile'],
            ['label' => 'Email address', 'done' => trim((string) $user->email) !== '', 'tab' => 'profile'],
            ['label' => 'Profile photo', 'done' => (bool) $photoUrl, 'tab' => 'profile'],
            ['label' => 'Phone number', 'done' => trim((string) $user->phone) !== '', 'tab' => 'profile'],
        ];
        // Payment Methods is a driver-only concern (you only need to receive fares
        // if you drive), so it's the only tab hidden for passengers entirely.
        if ($isDriver) {
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
            if (array_intersect($errorKeys, ['current_password', 'new_password', 'new_password_confirmation', 'google'])) {
                $errorTab = 'security';
            } elseif (array_intersect($errorKeys, ['payment_account_name', 'payment_account_number', 'payment_bank_name', 'payment_qr_duitnow', 'payment_qr_tng'])) {
                $errorTab = 'payment';
            } else {
                $errorTab = 'profile';
            }
        }
    @endphp

    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/settings.css') }}?v={{ filemtime(public_path('css/settings.css')) }}">
    @endpush

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
                        @if($user->role === 'driver' && $user->driver_verification_status)
                            @php
                                // Same accountStatusLabel() the admin Users table reads — a driver
                                // seeing "Verified" here while admin's table said "Active" for the
                                // identical state was one more inconsistent word for the same thing.
                                $acctStatus = $user->accountStatusLabel();
                                $vBadgeClass = match($acctStatus['label']) {
                                    'Active' => 'badge-success',
                                    'Suspended' => 'badge-danger',
                                    'Rejected' => 'badge-danger',
                                    default => 'badge-warning',
                                };
                                $vBadgeIcon = match($acctStatus['label']) {
                                    'Active' => 'fa-circle-check',
                                    'Suspended' => 'fa-circle-pause',
                                    'Rejected' => 'fa-circle-xmark',
                                    default => 'fa-clock',
                                };
                            @endphp
                            <span class="badge {{ $vBadgeClass }}"><i class="fa-solid {{ $vBadgeIcon }}"></i> {{ $acctStatus['label'] }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Profile completeness — real, computed from actual saved fields. --}}
            <div class="settings-hero-completion">
                <div class="hero-completion-top">
                    <div class="hero-completion-bar-block" role="img" aria-label="Profile {{ $completionPercent }}% complete">
                        <div class="hero-completion-bar-head">
                            <strong>Profile {{ $completionPercent }}% complete</strong>
                            @if($completionPercent < 100)
                                <span class="hero-steps-left">{{ count($missingItems) }} step{{ count($missingItems) === 1 ? '' : 's' }} left</span>
                            @else
                                <span class="hero-steps-left is-done"><i class="fa-solid fa-circle-check"></i> All set</span>
                            @endif
                        </div>
                        <div class="hero-progress-track">
                            <div class="hero-progress-fill" style="width: {{ $completionPercent }}%"></div>
                        </div>
                    </div>
                    @if($completionPercent < 100)
                        <button type="button" class="hero-completion-cta" onclick="switchSettingsTab('{{ $firstMissingTab }}')">
                            Complete <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    @endif
                </div>
                @if($completionPercent < 100)
                    <p class="hero-completion-missing"><i class="fa-solid fa-circle-info"></i> Missing: {{ implode(', ', array_column($missingItems, 'label')) }}</p>
                @else
                    <p class="hero-completion-missing is-done"><i class="fa-solid fa-sparkles"></i> All your details are set up. Nice work!</p>
                @endif
            </div>
        </div>

        {{-- Two-column shell: nav rail on desktop, scrollable tab strip on mobile --}}
        <div class="settings-shell">
            {{-- Nav rail on desktop; on mobile, the quickbar header + tab strip
                 merge into one card (.settings-nav-wrap carries the card
                 chrome there so there's a single border/shadow, not two
                 stacked boxes). Title/icon/step swap live via
                 updateQuickbar() in settings.js whenever the tab changes. --}}
            <div class="settings-nav-wrap">
                <div class="settings-quickbar">
                    <div class="settings-quickbar-title">
                        <span class="settings-quickbar-icon" id="quickbarIcon"><i class="fa-solid fa-user"></i></span>
                        <strong id="quickbarTitle">Profile Settings</strong>
                    </div>
                    <span class="settings-quickbar-step" id="quickbarStep">1 of {{ $isDriver ? 4 : 3 }}</span>
                </div>

                <div class="settings-nav" role="tablist" aria-label="Settings sections">
                    <button type="button" class="settings-nav-btn is-active" id="nav-btn-profile" role="tab" aria-selected="true" aria-controls="panel-profile" onclick="switchSettingsTab('profile')">
                        <span class="nav-btn-icon"><i class="fa-solid fa-user"></i></span>
                        <span class="nav-btn-text">
                            <span class="nav-btn-label">Profile</span>
                            <span class="nav-btn-desc">Name, contact{{ $isDriver ? ', vehicle & docs' : '' }}</span>
                        </span>
                    </button>
                    @if($isDriver)
                        <button type="button" class="settings-nav-btn" id="nav-btn-payment" role="tab" aria-selected="false" aria-controls="panel-payment" onclick="switchSettingsTab('payment')">
                            <span class="nav-btn-icon"><i class="fa-solid fa-wallet"></i></span>
                            <span class="nav-btn-text">
                                <span class="nav-btn-label">Payouts</span>
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
                    <button type="button" class="settings-nav-btn" id="nav-btn-notifications" role="tab" aria-selected="false" aria-controls="panel-notifications" onclick="switchSettingsTab('notifications')">
                        <span class="nav-btn-icon">
                            <i class="fa-solid fa-bell"></i>
                            @if($telegramConfigured && ! $user->telegram_chat_id)
                                <span class="nav-badge-dot" aria-hidden="true"></span>
                            @endif
                        </span>
                        <span class="nav-btn-text">
                            <span class="nav-btn-label">Alerts</span>
                            <span class="nav-btn-desc">Push & Telegram alerts</span>
                        </span>
                    </button>
                </div>
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

                        {{-- ── Basic Information ─────────────────────────────── --}}
                        <div class="settings-subcard">
                            <div class="settings-subcard-head">
                                <h4 class="form-section-title"><i class="fa-solid fa-id-badge"></i> Basic Information</h4>
                                <span class="subcard-badge">Public profile</span>
                            </div>

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
                                <div class="form-label-row">
                                    <label class="form-label" for="profileEmail">Email Address</label>
                                    <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Verified</span>
                                </div>
                                <div class="input-wrap">
                                    <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                                    <input type="email" id="profileEmail" class="input-field" value="{{ $user->email }}" disabled readonly>
                                    <span class="input-icon" title="Email is locked"><i class="fa-solid fa-lock" style="font-size:12px;"></i></span>
                                </div>
                                <span class="field-hint">Email address is fixed to your account credentials.</span>

                                <span class="form-label" style="display:block;margin-top:12px;">Who can see your email</span>
                                <div class="quick-switch" role="radiogroup" aria-label="Who can see your email">
                                    <input type="radio" class="sr-only" name="email_visible" id="emailVisPublic" value="visible_public" {{ $selectedEmailVisibility === 'visible_public' ? 'checked' : '' }}>
                                    <label for="emailVisPublic">Public</label>
                                    <input type="radio" class="sr-only" name="email_visible" id="emailVisFriend" value="visible_friend" {{ $selectedEmailVisibility === 'visible_friend' ? 'checked' : '' }}>
                                    <label for="emailVisFriend">Connections</label>
                                    <input type="radio" class="sr-only" name="email_visible" id="emailVisPrivate" value="unvisible" {{ $selectedEmailVisibility === 'unvisible' ? 'checked' : '' }}>
                                    <label for="emailVisPrivate">Private</label>
                                </div>
                            </div>
                        </div>

                        {{-- ── Contact & Privacy ─────────────────────────────── --}}
                        <div class="settings-subcard">
                            <div class="settings-subcard-head">
                                <h4 class="form-section-title"><i class="fa-solid fa-address-book"></i> Contact & Privacy</h4>
                                <span class="subcard-badge">Riders only</span>
                            </div>

                            {{-- Phone / WhatsApp Number + Visibility --}}
                            <div class="form-group">
                                <label class="form-label" for="profilePhone">Phone / WhatsApp Number</label>
                                <div class="input-wrap @error('phone') has-error @enderror">
                                    <span class="input-icon"><i class="fa-brands fa-whatsapp"></i></span>
                                    <input type="tel" id="profilePhone" name="phone" class="input-field" value="{{ old('phone', $user->phone) }}" placeholder="+60 12-345 6789">
                                </div>
                                @error('phone')
                                    <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                                <span class="field-hint">Used for trip coordination — visibility controls who else on CarpoolHub can see it.</span>

                                <span class="form-label" style="display:block;margin-top:12px;">Phone visibility to other members</span>
                                <div class="quick-switch" role="radiogroup" aria-label="Who can see your phone number">
                                    <input type="radio" class="sr-only" name="phone_visible" id="phoneVisPublic" value="visible_public" {{ $selectedPhoneVisibility === 'visible_public' ? 'checked' : '' }}>
                                    <label for="phoneVisPublic">Public</label>
                                    <input type="radio" class="sr-only" name="phone_visible" id="phoneVisFriend" value="visible_friend" {{ $selectedPhoneVisibility === 'visible_friend' ? 'checked' : '' }}>
                                    <label for="phoneVisFriend">Connections</label>
                                    <input type="radio" class="sr-only" name="phone_visible" id="phoneVisPrivate" value="unvisible" {{ $selectedPhoneVisibility === 'unvisible' ? 'checked' : '' }}>
                                    <label for="phoneVisPrivate">Private</label>
                                </div>
                            </div>
                        </div>

                        {{-- ── Vehicle & Credentials (Driver only) ───────────── --}}
                        @if($isDriver)
                            @php
                                $docStatusMap = [
                                    'approved' => ['label' => 'Verified', 'class' => 'is-verified', 'icon' => 'fa-circle-check'],
                                    'pending'  => ['label' => 'Pending review', 'class' => 'is-pending', 'icon' => 'fa-clock'],
                                    'rejected' => ['label' => 'Rejected', 'class' => 'is-rejected', 'icon' => 'fa-circle-xmark'],
                                ];
                                $missingDocStatus = ['label' => 'Not submitted', 'class' => 'is-missing', 'icon' => 'fa-circle-exclamation'];
                                $licenseStatus = $user->driving_license_photo
                                    ? ($docStatusMap[$user->driver_verification_status] ?? $docStatusMap['pending'])
                                    : $missingDocStatus;
                                $selfieStatus = $user->selfie_photo
                                    ? ($docStatusMap[$user->driver_verification_status] ?? $docStatusMap['pending'])
                                    : $missingDocStatus;
                                $licenseExpiryText = $licenseStatus['class'] === 'is-verified' && $user->driving_license_expiry
                                    ? ' • Exp: ' . $user->driving_license_expiry->format('m/Y')
                                    : '';
                            @endphp
                            <div class="settings-subcard">
                                <div class="settings-subcard-head">
                                    <h4 class="form-section-title"><i class="fa-solid fa-car-side"></i> Vehicle & Credentials</h4>
                                    <span class="subcard-badge is-accent">Driver Pass</span>
                                </div>

                                @if($user->driver_verification_status === 'rejected' && $user->driver_verification_reason)
                                    <div class="settings-alert error">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <span><strong>Application rejected:</strong> {{ $user->driver_verification_reason }} — update your details below and save to resubmit.</span>
                                    </div>
                                @endif

                                <div class="form-grid-2col">
                                    <div class="form-group">
                                        <label class="form-label" for="vehicleModel">Car Model</label>
                                        <div class="input-wrap @error('vehicle_model') has-error @enderror">
                                            <span class="input-icon"><i class="fa-solid fa-car"></i></span>
                                            <input type="text" id="vehicleModel" name="vehicle_model" class="input-field" value="{{ old('vehicle_model', $user->vehicle_model) }}" placeholder="e.g. Perodua Myvi 1.5" list="vehicle-model-suggestions">
                                            <datalist id="vehicle-model-suggestions">
                                                @foreach (config('vehicle_fuel_consumption', []) as $vehicleOption)
                                                    <option value="{{ $vehicleOption['label'] }}"></option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                        @error('vehicle_model')
                                            <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="vehiclePlate">Plate Number</label>
                                        <div class="input-wrap @error('vehicle_plate') has-error @enderror">
                                            <span class="input-icon"><i class="fa-solid fa-id-card"></i></span>
                                            <input type="text" id="vehiclePlate" name="vehicle_plate" class="input-field" value="{{ old('vehicle_plate', $user->vehicle_plate) }}" placeholder="e.g. VAB 1234">
                                        </div>
                                        @error('vehicle_plate')
                                            <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Driver Verification Documents — editable; resubmitting sends the account back for review (see SettingsService::updateProfile). --}}
                                <div>
                                    <label class="form-label">Required Document Status</label>
                                    <p class="field-hint" style="margin:2px 0 10px;">Uploading a new license or selfie sends your account back for admin review — you'll be notified once it's checked again.</p>

                                    <div class="doc-status-list">
                                        {{-- License Photo --}}
                                        <div class="doc-status-row">
                                            <span class="doc-status-icon">
                                                <img id="licensePreview" src="{{ $user->driving_license_photo ?: '' }}" alt="Driver's License" style="{{ $user->driving_license_photo ? '' : 'display:none;' }}">
                                                <i class="fa-solid fa-id-card" id="licenseEmptyIcon" style="{{ $user->driving_license_photo ? 'display:none;' : '' }}"></i>
                                            </span>
                                            <div class="doc-status-text">
                                                <strong>Driver's License</strong>
                                                <span class="doc-status-line {{ $licenseStatus['class'] }}"><i class="fa-solid {{ $licenseStatus['icon'] }}"></i> {{ $licenseStatus['label'] }}{{ $licenseExpiryText }}</span>
                                            </div>
                                            <button type="button" class="doc-status-btn {{ $user->driving_license_photo ? '' : 'is-primary' }}" onclick="document.getElementById('licenseInput').click()">
                                                {{ $user->driving_license_photo ? 'Replace' : 'Upload' }}
                                            </button>
                                            <input type="file" id="licenseInput" name="driving_license_photo" accept="image/*" class="qr-file-input" onchange="previewQr(this, 'licensePreview', 'licenseEmptyIcon')">
                                        </div>
                                        @error('driving_license_photo')
                                            <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror

                                        {{-- Selfie Photo --}}
                                        <div class="doc-status-row">
                                            <span class="doc-status-icon">
                                                <img id="selfiePreview" src="{{ $user->selfie_photo ?: '' }}" alt="Driver Selfie Verification" style="{{ $user->selfie_photo ? '' : 'display:none;' }}">
                                                <i class="fa-solid fa-user-shield" id="selfieEmptyIcon" style="{{ $user->selfie_photo ? 'display:none;' : '' }}"></i>
                                            </span>
                                            <div class="doc-status-text">
                                                <strong>Driver Selfie Verification</strong>
                                                <span class="doc-status-line {{ $selfieStatus['class'] }}"><i class="fa-solid {{ $selfieStatus['icon'] }}"></i> {{ $selfieStatus['label'] }}</span>
                                            </div>
                                            <button type="button" class="doc-status-btn {{ $user->selfie_photo ? '' : 'is-primary' }}" onclick="document.getElementById('selfieInput').click()">
                                                {{ $user->selfie_photo ? 'Replace' : 'Upload' }}
                                            </button>
                                            <input type="file" id="selfieInput" name="selfie_photo" accept="image/*" class="qr-file-input" onchange="previewQr(this, 'selfiePreview', 'selfieEmptyIcon')">
                                        </div>
                                        @error('selfie_photo')
                                            <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group" style="margin-top:14px;">
                                        <label class="form-label" for="drivingLicenseExpiry">License Expiry Date</label>
                                        <div class="input-wrap @error('driving_license_expiry') has-error @enderror">
                                            <span class="input-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                            <input type="date" id="drivingLicenseExpiry" name="driving_license_expiry" class="input-field"
                                                value="{{ old('driving_license_expiry', $user->driving_license_expiry?->toDateString()) }}">
                                            @if($user->driving_license_expiry && $user->driving_license_expiry->isPast())
                                                <span class="badge badge-danger" style="margin-left:8px;">Expired</span>
                                            @endif
                                        </div>
                                        @error('driving_license_expiry')
                                            <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
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
                 TAB 2: PAYMENT METHODS & QR (driver only — passengers only
                 ever pay drivers, and admin never drives/collects fares)
            ─────────────────────────────────────────────────────────────── --}}
            @if($isDriver)
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

                @php
                    $googleGIcon = '<svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.9-2.26 5.36-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>';
                @endphp
                <div class="channel-row is-bind-row" style="margin-bottom: 18px;">
                    <span class="channel-row-icon google">{!! $googleGIcon !!}</span>
                    <div class="channel-row-main">
                        <div class="channel-row-headline">
                            <div class="channel-row-title">
                                Google
                                @if($user->google_id)
                                    <span class="channel-status-pill on">Connected</span>
                                @else
                                    <span class="channel-status-pill off">Not connected</span>
                                @endif
                            </div>
                            @if($user->google_id)
                                <form method="POST" action="{{ route('settings.google.unlink') }}">
                                    @csrf
                                    <button type="submit" class="btn-bind ghost">Unbind</button>
                                </form>
                            @else
                                <a href="{{ route('auth.google.redirect', ['purpose' => 'link']) }}" class="btn-bind">Bind</a>
                            @endif
                        </div>
                        <div class="channel-row-desc">
                            @if($user->google_id)
                                Signed in with Google via {{ $user->email }}.
                            @else
                                Faster login — must match this account's email ({{ $user->email }}).
                            @endif
                        </div>
                    </div>
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
                            <span class="form-note-inline"><i class="fa-solid fa-circle-info"></i> Signs you out of every other device — this one stays signed in.</span>
                            <button type="submit" class="btn-submit-yellow">
                                <i class="fa-solid fa-shield-halved"></i>
                                Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ─────────────────────────────────────────────────────────────
                 TAB 4: NOTIFICATIONS (Web Push + Telegram)
            ─────────────────────────────────────────────────────────────── --}}
            <div class="settings-panel-card" id="panel-notifications" data-tab="notifications">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-bell"></i> Notifications</h3>
                    <p class="panel-desc">Trip updates, join requests, and payments always land in your in-app list. Turn on a channel below to get alerted even when you're not in the app.</p>
                </div>

                <div class="channel-row">
                    <div class="channel-row-info">
                        <span class="channel-row-icon push"><i class="fa-solid fa-desktop"></i></span>
                        <div class="channel-row-text">
                            <div class="channel-row-title">
                                Browser Push
                                <span class="channel-status-pill off" id="pushStatusPill">Checking&hellip;</span>
                            </div>
                            <div class="channel-row-desc" id="pushStatusDesc">Alerts on this device, even when CarpoolHub is closed.</div>
                        </div>
                    </div>
                    <div class="channel-row-action">
                        <button type="button" class="btn-submit-yellow" id="pushEnableBtn" hidden>
                            <i class="fa-solid fa-bell"></i> Enable
                        </button>
                        <button type="button" class="btn-submit-ghost" id="pushDisableBtn" hidden>
                            <i class="fa-solid fa-bell-slash"></i> Disable
                        </button>
                    </div>
                </div>

                <div class="channel-row">
                    <div class="channel-row-info">
                        <span class="channel-row-icon telegram"><i class="fa-brands fa-telegram"></i></span>
                        <div class="channel-row-text">
                            <div class="channel-row-title">
                                Telegram
                                @if($user->telegram_chat_id)
                                    <span class="channel-status-pill on">Connected</span>
                                @else
                                    <span class="channel-status-pill off">Not connected</span>
                                @endif
                            </div>
                            @if($user->telegram_chat_id)
                                <div class="channel-row-desc">Sending alerts to {{ $user->telegram_username ? '@'.$user->telegram_username : 'your linked Telegram account' }}.</div>
                            @elseif($telegramConfigured)
                                <div class="channel-row-desc">Reliable, instant alerts on any device.</div>
                                <ul class="channel-perk-list">
                                    <li><i class="fa-solid fa-bolt"></i> Works on phone &amp; desktop</li>
                                    <li><i class="fa-solid fa-mobile-screen-button"></i> Opens as a Mini App — no login needed</li>
                                </ul>
                            @else
                                <div class="channel-row-desc">Telegram isn't set up on this server yet.</div>
                            @endif
                        </div>
                    </div>
                    <div class="channel-row-action">
                        @if($user->telegram_chat_id)
                            <form method="POST" action="{{ route('telegram.unlink') }}">
                                @csrf
                                <button type="submit" class="btn-submit-ghost">
                                    <i class="fa-solid fa-link-slash"></i> Disconnect
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('telegram.link') }}">
                                @csrf
                                <button type="submit" class="btn-submit-yellow" @if(!$telegramConfigured) disabled @endif>
                                    <i class="fa-brands fa-telegram"></i> Connect Telegram
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            </div>
        </div>
    </div>

    <script src="{{ asset('js/settings.js') }}?v={{ filemtime(public_path('js/settings.js')) }}"></script>
    <script>
        window.__vapidPublicKey = @json(config('app.vapid_public_key'));
        window.__csrfToken = @json(csrf_token());
    </script>
    <script src="{{ asset('js/push-notifications.js') }}?v={{ filemtime(public_path('js/push-notifications.js')) }}"></script>
@endsection
