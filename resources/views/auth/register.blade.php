<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <title>Create Account | CarpoolHub</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/icon.png') }}">
    {{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
    <link rel="stylesheet" href="{{ asset('css/auth-register.css') }}?v={{ filemtime(public_path('css/auth-register.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/bg-pattern.css') }}?v={{ filemtime(public_path('css/bg-pattern.css')) }}">
    @include('layouts.partials.pwa-head')
</head>
<body>

@include('layouts.partials.bg-pattern')

<div class="login-shell">

    {{-- ── Left brand panel (desktop) ── --}}
    <aside class="brand-panel">
        <svg class="brand-panel-hex-decor" viewBox="0 0 320 320" aria-hidden="true">
            <polygon
                points="160 20, 280 90, 280 230, 160 300, 40 230, 40 90"
                fill="none"
                stroke="rgba(250,204,21,0.12)"
                stroke-width="40"
            />
        </svg>
        <div class="brand-panel-inner">
            <div class="brand-logo-lockup">
                <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="" class="brand-logo-icon">
                <span class="brand-logo-text">Carpool<span>Hub</span></span>
            </div>

            <h2 class="brand-heading">
                Your commute,<br>
                <span>smarter every day.</span>
            </h2>

            <p class="brand-tagline">
                Create a free account and start splitting fares, matching
                routes, and riding with verified drivers — all in one app.
            </p>

            <ul class="brand-features">
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-clock"></i></span>
                    Register in under 2 minutes
                </li>
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-route"></i></span>
                    Smart matching to your route
                </li>
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-shield-halved"></i></span>
                    Every driver reviewed & verified
                </li>
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                    AI assistant for trip planning & fare pricing
                </li>
            </ul>
        </div>
    </aside>

    {{-- ── Right form panel ── --}}
    <main class="form-panel">
        <div class="form-box">

            {{-- Mobile logo (hidden on desktop) --}}
            <div class="login-card">

                {{-- Mobile logo (hidden on desktop) --}}
                <div class="mobile-logo-wrap">
                    <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="" class="mobile-logo-icon">
                    <span class="mobile-logo-text">Carpool<span>Hub</span></span>
                </div>

                <div class="login-card-header">
                    <h1 class="login-card-title">Create your account</h1>
                    <p class="login-card-sub">Join CarpoolHub and start sharing rides today.</p>
                </div>

                {{-- Validation errors --}}
                @if ($errors->any())
                    <div class="error-alert" role="alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                @php
                    // Land the wizard on the earliest step that actually has an
                    // error after a failed server round-trip, so the user fixes
                    // things in order instead of landing back on step 1 with no
                    // visible clue that (say) the password step is what failed.
                    $errorStep = 1;
                    if ($errors->hasAny(['name', 'email', 'phone'])) {
                        $errorStep = 1;
                    } elseif ($errors->hasAny(['role'])) {
                        $errorStep = 2;
                    } elseif ($errors->hasAny(['vehicle_model', 'vehicle_plate', 'driving_license_photo', 'selfie_photo'])) {
                        $errorStep = 3;
                    } elseif ($errors->hasAny(['password', 'password_confirmation'])) {
                        $errorStep = 4;
                    }
                @endphp

                <form
                    method="POST"
                    action="{{ route('register.store') }}"
                    enctype="multipart/form-data"
                    novalidate
                    id="register-form"
                    data-initial-step="{{ $errorStep }}"
                    data-initial-role="{{ old('role', 'passenger') }}"
                >
                    @csrf

                    {{-- ── Step progress ── --}}
                    <div class="wizard-progress">
                        <div class="wizard-progress-track">
                            <div class="wizard-progress-fill" id="wizard-progress-fill"></div>
                        </div>
                        <span class="wizard-progress-label" id="wizard-progress-label">Step 1 of 3</span>
                    </div>

                    {{-- ── Step 1: Account info ── --}}
                    <div class="wizard-step" data-step="1">
                        <div class="section-label">Account info</div>

                        <div class="field-group">

                            {{-- Full name --}}
                            <div class="field-row">
                                <label class="field-label" for="name">Full name</label>
                                <div class="input-wrap">
                                    <i class="fa-regular fa-user input-icon"></i>
                                    <input
                                        id="name"
                                        class="input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                        type="text"
                                        name="name"
                                        value="{{ old('name') }}"
                                        placeholder="Ahmad bin Ali"
                                        required
                                        autocomplete="name"
                                    >
                                </div>
                                @error('name')
                                    <span class="field-error">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="field-row">
                                <label class="field-label" for="email">Email address</label>
                                <div class="input-wrap">
                                    <i class="fa-regular fa-envelope input-icon"></i>
                                    <input
                                        id="email"
                                        class="input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        placeholder="you@example.com"
                                        required
                                        autocomplete="email"
                                    >
                                </div>
                                @error('email')
                                    <span class="field-error">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div class="field-row">
                                <label class="field-label" for="phone">
                                    Phone number
                                    <span class="optional">(optional)</span>
                                </label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-phone input-icon"></i>
                                    <input
                                        id="phone"
                                        class="input {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                                        type="tel"
                                        name="phone"
                                        value="{{ old('phone') }}"
                                        placeholder="+60 12-345 6789"
                                        autocomplete="tel"
                                    >
                                </div>
                                @error('phone')
                                    <span class="field-error">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                        </div>{{-- /.field-group --}}
                    </div>{{-- /.wizard-step[1] --}}

                    {{-- ── Step 2: Role ── --}}
                    <div class="wizard-step" data-step="2">
                        <div class="section-label">I want to</div>

                        <div class="field-group">
                            <div class="field-row">
                                <div class="role-selector" id="role-selector">

                                    <label class="role-option">
                                        <input
                                            type="radio"
                                            name="role"
                                            value="passenger"
                                            id="role-passenger"
                                            {{ old('role', 'passenger') === 'passenger' ? 'checked' : '' }}
                                        >
                                        <span class="role-card">
                                            <span class="role-icon"><i class="fa-solid fa-person-walking"></i></span>
                                            <span class="role-name">Ride as passenger</span>
                                            <span class="role-desc">Join trips and split costs</span>
                                        </span>
                                    </label>

                                    <label class="role-option">
                                        <input
                                            type="radio"
                                            name="role"
                                            value="driver"
                                            id="role-driver"
                                            {{ old('role') === 'driver' ? 'checked' : '' }}
                                        >
                                        <span class="role-card">
                                            <span class="role-icon"><i class="fa-solid fa-car-side"></i></span>
                                            <span class="role-name">Drive & offer seats</span>
                                            <span class="role-desc">Post trips and earn back fuel costs</span>
                                        </span>
                                    </label>

                                </div>
                                @error('role')
                                    <span class="field-error">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>{{-- /.field-group --}}
                    </div>{{-- /.wizard-step[2] --}}

                    {{-- ── Step 3: Vehicle & verification (driver only, skipped for passengers) ── --}}
                    <div class="wizard-step" data-step="3" id="vehicle-section">
                        <div class="section-label">Vehicle & verification</div>

                        <div class="field-group">
                            <div class="field-row-2">

                                <div class="field-row">
                                    <label class="field-label" for="vehicle_model">Vehicle model</label>
                                    <div class="input-wrap">
                                        <i class="fa-solid fa-car input-icon"></i>
                                        <input
                                            id="vehicle_model"
                                            class="input {{ $errors->has('vehicle_model') ? 'is-invalid' : '' }}"
                                            type="text"
                                            name="vehicle_model"
                                            value="{{ old('vehicle_model') }}"
                                            placeholder="Perodua Myvi"
                                            autocomplete="off"
                                            list="vehicle-model-suggestions"
                                        >
                                        <datalist id="vehicle-model-suggestions">
                                            @foreach (config('vehicle_fuel_consumption', []) as $vehicleOption)
                                                <option value="{{ $vehicleOption['label'] }}"></option>
                                            @endforeach
                                        </datalist>
                                    </div>
                                    @error('vehicle_model')
                                        <span class="field-error">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="field-row">
                                    <label class="field-label" for="vehicle_plate">Plate number</label>
                                    <div class="input-wrap">
                                        <i class="fa-solid fa-id-card input-icon"></i>
                                        <input
                                            id="vehicle_plate"
                                            class="input {{ $errors->has('vehicle_plate') ? 'is-invalid' : '' }}"
                                            type="text"
                                            name="vehicle_plate"
                                            value="{{ old('vehicle_plate') }}"
                                            placeholder="WXX 1234"
                                            autocomplete="off"
                                            style="text-transform: uppercase;"
                                        >
                                    </div>
                                    @error('vehicle_plate')
                                        <span class="field-error">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                            </div>

                            {{-- Driving license photo --}}
                            <div class="field-row">
                                <label class="field-label" for="driving_license_photo">Driving license photo</label>
                                <div class="file-upload-wrap">
                                    <label
                                        class="file-upload-label {{ $errors->has('driving_license_photo') ? 'is-invalid' : '' }}"
                                        id="license-upload-label"
                                    >
                                        <span class="file-upload-icon"><i class="fa-solid fa-id-card" id="license-icon"></i></span>
                                        <span id="license-filename">Upload front of your driving license</span>
                                        <input
                                            type="file"
                                            id="driving_license_photo"
                                            name="driving_license_photo"
                                            accept="image/jpeg,image/png,image/webp"
                                            onchange="handleLicenseUpload(this)"
                                        >
                                    </label>
                                </div>
                                <span class="file-upload-hint">Image only — JPG, PNG, or WEBP · Max 4MB</span>
                                <span class="field-error" id="license-client-error" hidden>
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span></span>
                                </span>
                                @error('driving_license_photo')
                                    <span class="field-error">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            {{-- Selfie photo holding license --}}
                            <div class="field-row" style="margin-top:12px;">
                                <label class="field-label" for="selfie_photo">Selfie with license (Verification)</label>
                                <div class="file-upload-wrap">
                                    <label
                                        class="file-upload-label {{ $errors->has('selfie_photo') ? 'is-invalid' : '' }}"
                                        id="selfie-upload-label"
                                    >
                                        <span class="file-upload-icon"><i class="fa-solid fa-user-shield" id="selfie-icon"></i></span>
                                        <span id="selfie-filename">Upload selfie holding your license</span>
                                        <input
                                            type="file"
                                            id="selfie_photo"
                                            name="selfie_photo"
                                            accept="image/jpeg,image/png,image/webp"
                                            onchange="handleSelfieUpload(this)"
                                        >
                                    </label>
                                </div>
                                <span class="file-upload-hint">Image only — JPG, PNG, or WEBP · Max 5MB</span>
                                <span class="field-error" id="selfie-client-error" hidden>
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span></span>
                                </span>
                                @error('selfie_photo')
                                    <span class="field-error">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                        </div>{{-- /.field-group --}}
                    </div>{{-- /.wizard-step[3] --}}

                    {{-- ── Step 4: Password ── --}}
                    <div class="wizard-step" data-step="4">
                        <div class="section-label">Password</div>

                        <div class="field-group">

                            {{-- Password --}}
                            <div class="field-row">
                                <label class="field-label" for="password">Password</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-lock input-icon"></i>
                                    <input
                                        id="password"
                                        class="input has-toggle {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                        type="password"
                                        name="password"
                                        placeholder="Min. 8 characters"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                    >
                                    <button
                                        type="button"
                                        class="pw-toggle"
                                        aria-label="Toggle password visibility"
                                        onclick="togglePassword('password', 'pw-icon-1')"
                                    >
                                        <i class="fa-regular fa-eye" id="pw-icon-1"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="field-error">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            {{-- Confirm password --}}
                            <div class="field-row">
                                <label class="field-label" for="password_confirmation">Confirm password</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-lock input-icon"></i>
                                    <input
                                        id="password_confirmation"
                                        class="input has-toggle"
                                        type="password"
                                        name="password_confirmation"
                                        placeholder="Repeat your password"
                                        required
                                        autocomplete="new-password"
                                    >
                                    <button
                                        type="button"
                                        class="pw-toggle"
                                        aria-label="Toggle confirm password visibility"
                                        onclick="togglePassword('password_confirmation', 'pw-icon-2')"
                                    >
                                        <i class="fa-regular fa-eye" id="pw-icon-2"></i>
                                    </button>
                                </div>
                                <span class="field-error" id="confirm-password-error" hidden>
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    Passwords do not match.
                                </span>
                            </div>

                        </div>{{-- /.field-group --}}

                        <p class="terms-note">
                            By clicking <strong>Create account</strong>, you agree to our
                            <a href="{{ route('legal.terms') }}#terms" target="_blank" rel="noopener">Terms of Service</a>
                            and
                            <a href="{{ route('legal.terms') }}#privacy" target="_blank" rel="noopener">Privacy Policy</a>.
                            CarpoolHub uses your trip and payment records to personalise
                            features such as route matching, fare suggestions, and
                            driver verification. Your data is handled securely in
                            accordance with the Personal Data Protection Act 2010 (Malaysia).
                        </p>
                    </div>{{-- /.wizard-step[4] --}}

                    {{-- ── Step navigation ── --}}
                    <div class="wizard-nav">
                        <button type="button" class="wizard-btn-back" id="wizard-back-btn">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="wizard-btn-next" id="wizard-next-btn">
                            Next <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button type="submit" class="btn-primary-login wizard-btn-submit" id="wizard-submit-btn">
                            <i class="fa-solid fa-user-plus"></i>
                            Create account
                        </button>
                    </div>

                </form>

                <div class="card-divider"></div>

                <p class="register-prompt">
                    Already have an account?
                    <a href="{{ route('login') }}">Log in here</a>
                </p>

            </div>{{-- /.login-card --}}
        </div>{{-- /.form-box --}}
    </main>

</div>{{-- /.login-shell --}}

<script src="{{ asset('js/auth-register.js') }}?v={{ filemtime(public_path('js/auth-register.js')) }}"></script>

</body>
</html>
