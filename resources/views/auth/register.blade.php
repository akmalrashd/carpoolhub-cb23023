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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/icon.png') }}">
    {{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
    <link rel="stylesheet" href="{{ asset('css/auth-register.css') }}?v={{ filemtime(public_path('css/auth-register.css')) }}">
    @include('layouts.partials.pwa-head')
</head>
<body>

<div class="login-shell">

    {{-- ── Left brand panel (desktop) ── --}}
    <aside class="brand-panel">
        <div class="brand-panel-inner">
            <img
                class="brand-logo"
                src="{{ asset('assets/branding/logo-horizontal-w.png') }}"
                alt="CarpoolHub"
            >

            <div class="brand-pill">
                <i class="fa-solid fa-user-plus"></i>
                Free to join
            </div>

            <h2 class="brand-heading">
                Your commute,<br>
                <span>smarter every day.</span>
            </h2>

            <p class="brand-tagline">
                Join thousands of Malaysians already sharing rides.
                Save money, reduce traffic, and connect with your community.
            </p>

            <ul class="brand-features">
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-clock"></i></span>
                    Register in under 2 minutes
                </li>
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-coins"></i></span>
                    Earn or save on every trip
                </li>
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-shield-halved"></i></span>
                    Verified community members
                </li>
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-route"></i></span>
                    Smart route matching
                </li>
            </ul>
        </div>
    </aside>

    {{-- ── Right form panel ── --}}
    <main class="form-panel">
        <div class="form-box">

            {{-- Mobile logo (hidden on desktop) --}}
            <div class="mobile-logo-wrap">
                <img
                    src="{{ asset('assets/branding/logo-horizontal-b.png') }}"
                    alt="CarpoolHub"
                >
            </div>

            <div class="login-card">

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

                <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf

                    {{-- ── Account info ── --}}
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
                                    autofocus
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

                    {{-- ── Role ── --}}
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

                    {{-- ── Vehicle info (driver only) ── --}}
                    <div class="vehicle-section {{ old('role') === 'driver' ? 'visible' : '' }}" id="vehicle-section">
                        <div class="section-label">Vehicle info</div>
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
                                        >
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
                                                accept="image/*"
                                                onchange="handleLicenseUpload(this)"
                                            >
                                        </label>
                                    </div>
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
                                                accept="image/*"
                                                onchange="handleSelfieUpload(this)"
                                            >
                                        </label>
                                    </div>
                                    @error('selfie_photo')
                                        <span class="field-error">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                        </div>
                    </div>

                    {{-- ── Password ── --}}
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
                        </div>

                    </div>{{-- /.field-group --}}

                    <button type="submit" class="btn-primary-login">
                        <i class="fa-solid fa-user-plus"></i>
                        Create account
                    </button>

                    <p class="terms-note">
                        By registering, you agree to use CarpoolHub responsibly
                        and within our community guidelines.
                    </p>

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
