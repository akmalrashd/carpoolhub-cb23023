<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <title>Create Account | CarpoolHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/icon.png') }}">
    <style>
        :root {
            --ch-yellow: #FACC15;
            --ch-yellow-deep: #E6B800;
            --ch-yellow-ink: #2A1E04;
            --ch-yellow-tint: #FFFBEA;
            --ch-yellow-line: #F2D24A;
            --ink: #0B1220;
            --ink-2: #1F2937;
            --ink-3: #475569;
            --muted: #64748B;
            --muted-2: #94A3B8;
            --hairline: #ECE7DA;
            --hairline-strong: #DAD2BE;
            --surface: #FFFFFF;
            --surface-2: #FAF7EE;
            --canvas: #F4EFE2;
            --success: #16A34A; --success-soft: #DCFCE7; --success-ink: #065F46;
            --warning: #B45309; --warning-soft: #FEF3C7; --warning-ink: #78350F;
            --danger: #DC2626; --danger-soft: #FEE2E2; --danger-ink: #7F1D1D;
            --info: #2563EB; --info-soft: #DBEAFE; --info-ink: #1E3A8A;
            --r-sm: 10px; --r-md: 14px; --r-lg: 18px; --r-pill: 999px;
            --shadow-1: 0 1px 2px rgba(11,18,32,0.04);
            --shadow-2: 0 6px 18px rgba(11,18,32,0.06);
            --shadow-3: 0 18px 40px rgba(11,18,32,0.10);
            --font-display: "Poppins", sans-serif;
            --font-ui: "Inter", sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font-ui);
            background: var(--canvas);
            color: var(--ink);
            min-height: 100vh;
        }

        /* ── Two-panel shell ── */
        .login-shell {
            display: flex;
            min-height: 100vh;
        }

        /* ── Left brand panel (desktop only) ── */
        .brand-panel {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            width: 42%;
            flex-shrink: 0;
            padding: 56px 52px;
            background: var(--ink-2);
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 10% 90%, rgba(250,204,21,0.18) 0%, transparent 70%),
                radial-gradient(ellipse 60% 50% at 90% 10%, rgba(250,204,21,0.10) 0%, transparent 70%);
            pointer-events: none;
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            bottom: -80px;
            right: -80px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            border: 40px solid rgba(250,204,21,0.12);
            pointer-events: none;
        }

        .brand-panel-inner {
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            display: block;
            width: min(220px, 80%);
            height: auto;
            margin-bottom: 40px;
        }

        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(250,204,21,0.15);
            border: 1px solid rgba(250,204,21,0.30);
            border-radius: var(--r-pill);
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            color: var(--ch-yellow);
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 22px;
        }

        .brand-heading {
            font-family: var(--font-display);
            font-size: clamp(28px, 3vw, 38px);
            font-weight: 800;
            line-height: 1.18;
            color: #fff;
            margin-bottom: 16px;
        }

        .brand-heading span {
            color: var(--ch-yellow);
        }

        .brand-tagline {
            font-size: 15px;
            color: var(--muted-2);
            line-height: 1.6;
            max-width: 320px;
            margin-bottom: 44px;
        }

        .brand-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .brand-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #CBD5E1;
        }

        .brand-features li .feat-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(250,204,21,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ch-yellow);
            font-size: 13px;
            flex-shrink: 0;
        }

        /* ── Right form panel ── */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 32px 20px 48px;
            background: var(--canvas);
        }

        .mobile-logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 28px;
        }

        .mobile-logo-wrap img {
            width: min(180px, 60%);
            height: auto;
        }

        .form-box {
            width: 100%;
            max-width: 460px;
        }

        /* ── Register card ── */
        .login-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 36px 32px 32px;
            box-shadow: var(--shadow-3);
        }

        .login-card-header {
            margin-bottom: 28px;
        }

        .login-card-title {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .login-card-sub {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.5;
        }

        /* ── Error alert ── */
        .error-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--danger-soft);
            border: 1px solid rgba(220,38,38,0.22);
            border-radius: var(--r-md);
            padding: 12px 14px;
            color: var(--danger-ink);
            font-size: 13.5px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .error-alert i { margin-top: 1px; flex-shrink: 0; }

        /* ── Section label ── */
        .section-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted-2);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-label::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--hairline);
        }

        /* ── Field group ── */
        .field-group {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 22px;
        }

        .field-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .field-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-3);
            letter-spacing: 0.01em;
        }

        .field-label .optional {
            font-weight: 400;
            color: var(--muted-2);
            margin-left: 4px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted-2);
            font-size: 14px;
            pointer-events: none;
        }

        .input-wrap .input {
            padding-left: 40px;
        }

        .input {
            width: 100%;
            height: 44px;
            border: 1.5px solid var(--hairline-strong);
            border-radius: var(--r-md);
            background: var(--surface-2);
            color: var(--ink);
            font-family: var(--font-ui);
            font-size: 14px;
            padding: 0 14px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        .input:focus {
            border-color: var(--ch-yellow-line);
            background: var(--surface);
            box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
        }

        .input.is-invalid {
            border-color: var(--danger);
            background: var(--surface);
        }

        .input.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(220,38,38,0.12);
        }

        .field-error {
            font-size: 12.5px;
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Password toggle */
        .input-wrap .pw-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted-2);
            font-size: 14px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            width: auto;
            height: auto;
            margin: 0;
            transition: color 0.15s;
        }

        .input-wrap .pw-toggle:hover { color: var(--ink-3); }

        .input-wrap .input.has-toggle { padding-right: 40px; }

        /* ── Role selector ── */
        .role-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .role-option {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .role-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px 12px;
            border: 1.5px solid var(--hairline-strong);
            border-radius: var(--r-md);
            background: var(--surface-2);
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
            user-select: none;
            flex: 1;
        }

        .role-card:hover {
            border-color: var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
        }

        .role-option input[type="radio"]:checked + .role-card {
            border-color: var(--ch-yellow);
            background: var(--ch-yellow-tint);
            box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
        }

        .role-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--hairline);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            color: var(--muted);
            transition: background 0.15s, color 0.15s;
        }

        .role-option input[type="radio"]:checked + .role-card .role-icon {
            background: rgba(250,204,21,0.25);
            color: var(--ch-yellow-ink);
        }

        .role-name {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ink-3);
            transition: color 0.15s;
        }

        .role-option input[type="radio"]:checked + .role-card .role-name {
            color: var(--ink);
        }

        .role-desc {
            font-size: 11.5px;
            color: var(--muted-2);
            text-align: center;
            line-height: 1.4;
        }

        /* ── File upload ── */
        .file-upload-wrap {
            position: relative;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            min-height: 44px;
            border: 1.5px dashed var(--hairline-strong);
            border-radius: var(--r-md);
            background: var(--surface-2);
            padding: 10px 14px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            font-size: 14px;
            color: var(--muted);
        }

        .file-upload-label:hover {
            border-color: var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
        }

        .file-upload-label.has-file {
            border-style: solid;
            border-color: var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            color: var(--ink-3);
        }

        .file-upload-label.is-invalid {
            border-color: var(--danger);
            border-style: solid;
        }

        .file-upload-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--r-sm);
            background: var(--hairline);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            color: var(--muted);
            flex-shrink: 0;
            transition: background 0.15s, color 0.15s;
        }

        .file-upload-label.has-file .file-upload-icon {
            background: rgba(250,204,21,0.25);
            color: var(--ch-yellow-ink);
        }

        .file-upload-label input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        /* ── Vehicle section (shown only for driver) ── */
        .vehicle-section {
            display: none;
            flex-direction: column;
            gap: 0;
        }

        .vehicle-section.visible {
            display: flex;
        }

        /* ── Primary button ── */
        .btn-primary-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            height: 48px;
            border: none;
            border-radius: var(--r-md);
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
            font-family: var(--font-ui);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.01em;
            transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
            box-shadow: 0 2px 0 var(--ch-yellow-deep), var(--shadow-1);
        }

        .btn-primary-login:hover {
            background: var(--ch-yellow-deep);
            box-shadow: 0 2px 0 #C9A200, var(--shadow-2);
        }

        .btn-primary-login:active {
            transform: translateY(1px);
            box-shadow: 0 1px 0 var(--ch-yellow-deep);
        }

        /* ── Login link ── */
        .register-prompt {
            margin-top: 24px;
            text-align: center;
            font-size: 13.5px;
            color: var(--muted);
        }

        .register-prompt a {
            color: var(--ink-2);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1.5px solid var(--ch-yellow-line);
            transition: color 0.15s, border-color 0.15s;
        }

        .register-prompt a:hover {
            color: var(--ink);
            border-color: var(--ch-yellow-deep);
        }

        /* ── Divider ── */
        .card-divider {
            height: 1px;
            background: var(--hairline);
            margin: 24px 0;
        }

        /* ── Terms note ── */
        .terms-note {
            font-size: 12px;
            color: var(--muted-2);
            text-align: center;
            line-height: 1.6;
            margin-top: 14px;
        }

        /* ── Responsive ── */
        @media (min-width: 900px) {
            .brand-panel { display: flex; }
            .mobile-logo-wrap { display: none; }
            .form-panel { padding: 48px 40px; align-items: center; }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 28px 20px 24px;
                border-radius: var(--r-lg);
            }
            .field-row-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
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

<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    function handleLicenseUpload(input) {
        const label    = document.getElementById('license-upload-label');
        const filename = document.getElementById('license-filename');
        const icon     = document.getElementById('license-icon');
        if (input.files && input.files[0]) {
            label.classList.add('has-file');
            filename.textContent = input.files[0].name;
            icon.className = 'fa-solid fa-circle-check';
        } else {
            label.classList.remove('has-file');
            filename.textContent = 'Upload front of your driving license';
            icon.className = 'fa-solid fa-id-card';
        }
    }

    function handleSelfieUpload(input) {
        const label    = document.getElementById('selfie-upload-label');
        const filename = document.getElementById('selfie-filename');
        const icon     = document.getElementById('selfie-icon');
        if (input.files && input.files[0]) {
            label.classList.add('has-file');
            filename.textContent = input.files[0].name;
            icon.className = 'fa-solid fa-circle-check';
        } else {
            label.classList.remove('has-file');
            filename.textContent = 'Upload selfie holding your license';
            icon.className = 'fa-solid fa-user-shield';
        }
    }

    // Show/hide vehicle section based on role selection
    const roleInputs = document.querySelectorAll('input[name="role"]');
    const vehicleSection = document.getElementById('vehicle-section');

    function updateVehicleSection() {
        const selected = document.querySelector('input[name="role"]:checked');
        if (selected && selected.value === 'driver') {
            vehicleSection.classList.add('visible');
        } else {
            vehicleSection.classList.remove('visible');
        }
    }

    roleInputs.forEach(input => {
        input.addEventListener('change', updateVehicleSection);
    });
</script>

</body>
</html>
