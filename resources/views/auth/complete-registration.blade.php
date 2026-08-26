<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <title>Finish Signing Up | CarpoolHub</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/icon.png') }}">
    {{-- Reuses the register page's stylesheet — same role-selector, file-upload,
         and field-group classes, none of which depend on the wizard markup. --}}
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
                Almost there.<br>
                <span>Just a few more details.</span>
            </h2>

            <p class="brand-tagline">
                Google confirmed who you are — now tell us how you'll ride,
                so we can match you with the right trips.
            </p>
        </div>
    </aside>

    {{-- ── Right form panel ── --}}
    <main class="form-panel">
        <div class="form-box">

            <div class="login-card">

                {{-- Mobile logo (hidden on desktop) --}}
                <div class="mobile-logo-wrap">
                    <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="" class="mobile-logo-icon">
                    <span class="mobile-logo-text">Carpool<span>Hub</span></span>
                </div>

                <div class="login-card-header">
                    <h1 class="login-card-title">Finish signing up</h1>
                    <p class="login-card-sub">You're signing in with Google — just a couple more details.</p>
                </div>

                {{-- Validation errors --}}
                @if ($errors->any())
                    <div class="error-alert" role="alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                {{-- Google identity — read-only, comes from the OAuth session, not the form --}}
                <div class="identity-preview">
                    <span class="identity-preview-icon"><i class="fa-brands fa-google"></i></span>
                    <div class="identity-preview-text">
                        <div class="identity-preview-name">{{ $name }}</div>
                        <div class="identity-preview-email">{{ $email }}</div>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('register.complete.store') }}"
                    enctype="multipart/form-data"
                    novalidate
                    id="complete-registration-form"
                >
                    @csrf

                    <div class="field-group">

                        {{-- Phone --}}
                        <div class="field-row">
                            <label class="field-label" for="phone">Phone number</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-phone input-icon"></i>
                                <input
                                    id="phone"
                                    class="input {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                                    type="tel"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    placeholder="+60 12-345 6789"
                                    required
                                    autofocus
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

                    {{-- ── Vehicle & verification (driver only) ── --}}
                    <div id="vehicle-section" hidden>
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

                            <div class="field-row">
                                <label class="field-label" for="driving_license_expiry">License expiry date</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-calendar-days input-icon"></i>
                                    <input
                                        id="driving_license_expiry"
                                        class="input {{ $errors->has('driving_license_expiry') ? 'is-invalid' : '' }}"
                                        type="date"
                                        name="driving_license_expiry"
                                        value="{{ old('driving_license_expiry') }}"
                                    >
                                </div>
                                @error('driving_license_expiry')
                                    <span class="field-error">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
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
                    </div>{{-- /#vehicle-section --}}

                    <button type="submit" class="btn-primary-login">
                        <i class="fa-solid fa-user-check"></i>
                        Finish signing up
                    </button>

                </form>

                <div class="card-divider"></div>

                <p class="register-prompt">
                    Not you? <a href="{{ route('login') }}">Back to log in</a>
                </p>

            </div>{{-- /.login-card --}}
        </div>{{-- /.form-box --}}
    </main>

</div>{{-- /.login-shell --}}

{{-- Reuses handleFileUpload/handleLicenseUpload/handleSelfieUpload from the
     register page's script — it self-guards on #register-form not existing
     here, so only those upload helpers actually run. --}}
<script src="{{ asset('js/auth-register.js') }}?v={{ filemtime(public_path('js/auth-register.js')) }}"></script>
<script>
    (() => {
        const vehicleSection = document.getElementById('vehicle-section');
        const vehicleInputs = vehicleSection.querySelectorAll('#vehicle_model, #vehicle_plate, #driving_license_expiry');
        const licenseInput = document.getElementById('driving_license_photo');
        const selfieInput = document.getElementById('selfie_photo');

        const applyRole = () => {
            const isDriver = document.getElementById('role-driver').checked;
            vehicleSection.hidden = !isDriver;
            vehicleInputs.forEach((input) => { input.required = isDriver; });
            licenseInput.required = isDriver;
            selfieInput.required = isDriver;
        };

        document.querySelectorAll('input[name="role"]').forEach((input) => {
            input.addEventListener('change', applyRole);
        });

        applyRole();
    })();
</script>

</body>
</html>
