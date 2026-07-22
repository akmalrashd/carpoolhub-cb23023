<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <title>Log In | CarpoolHub</title>
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

        /* Yellow accent strip */
        .brand-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 10% 90%, rgba(250,204,21,0.18) 0%, transparent 70%),
                radial-gradient(ellipse 60% 50% at 90% 10%, rgba(250,204,21,0.10) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Decorative arc */
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
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background: var(--canvas);
        }

        /* Mobile-only logo shown inside form panel */
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
            max-width: 420px;
        }

        /* ── Login card ── */
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

        /* ── Status / flash ── */
        .status-banner {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--success-soft);
            border: 1px solid rgba(22,163,74,0.25);
            border-radius: var(--r-md);
            padding: 12px 14px;
            color: var(--success-ink);
            font-size: 13.5px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .status-banner i {
            margin-top: 1px;
            flex-shrink: 0;
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

        .error-alert i {
            margin-top: 1px;
            flex-shrink: 0;
        }

        /* ── Field group ── */
        .field-group {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-bottom: 6px;
        }

        .field-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-3);
            letter-spacing: 0.01em;
        }

        /* Input wrapper for icon prefix */
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

        .input-wrap .pw-toggle:hover {
            color: var(--ink-3);
        }

        .input-wrap .input.has-toggle {
            padding-right: 40px;
        }

        /* ── Remember + forgot row ── */
        .meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            margin-bottom: 22px;
            gap: 8px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: var(--ink-3);
            cursor: pointer;
            user-select: none;
        }

        .remember-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--ink-2);
            cursor: pointer;
            margin: 0;
            flex-shrink: 0;
        }

        .forgot-link {
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            white-space: nowrap;
            transition: color 0.15s;
        }

        .forgot-link:hover {
            color: var(--ink);
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

        /* ── Register link ── */
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

        /* ── Responsive ── */
        @media (min-width: 900px) {
            .brand-panel {
                display: flex;
            }

            .mobile-logo-wrap {
                display: none;
            }

            .form-panel {
                padding: 48px 40px;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 28px 20px 24px;
                border-radius: var(--r-lg);
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
                <i class="fa-solid fa-car-side"></i>
                Malaysia's smarter carpool
            </div>

            <h2 class="brand-heading">
                Share the ride,<br>
                <span>save the cost.</span>
            </h2>

            <p class="brand-tagline">
                Connect with colleagues and neighbours going your way.
                Fewer cars, lower costs, better commutes — every day.
            </p>

            <ul class="brand-features">
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-route"></i></span>
                    Smart route matching
                </li>
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-wallet"></i></span>
                    Split costs fairly & transparently
                </li>
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-shield-halved"></i></span>
                    Verified community members
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
                    <h1 class="login-card-title">Welcome back</h1>
                    <p class="login-card-sub">Log in to your CarpoolHub account to continue.</p>
                </div>

                {{-- Session status (e.g. password reset confirmation) --}}
                @if (session('status'))
                    <div class="status-banner" role="alert">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                {{-- Validation errors --}}
                @if ($errors->any())
                    <div class="error-alert" role="alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" novalidate>
                    @csrf

                    <div class="field-group">

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
                                    autofocus
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
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                >
                                <button
                                    type="button"
                                    class="pw-toggle"
                                    aria-label="Toggle password visibility"
                                    onclick="togglePassword()"
                                    id="pw-toggle-btn"
                                >
                                    <i class="fa-regular fa-eye" id="pw-toggle-icon"></i>
                                </button>
                            </div>
                            @error('password')
                                <span class="field-error">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                    </div>{{-- /.field-group --}}

                    {{-- Remember me + Forgot password --}}
                    <div class="meta-row">
                        <label class="remember-label" for="remember">
                            <input
                                id="remember"
                                type="checkbox"
                                name="remember"
                                value="1"
                                {{ old('remember', '1') ? 'checked' : '' }}
                            >
                            Remember me
                        </label>

                        @if (Route::has('password.request'))
                            <a class="forgot-link" href="{{ route('password.request') }}">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="btn-primary-login">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        Log in
                    </button>

                </form>

                <div class="card-divider"></div>

                @if (Route::has('register'))
                <p class="register-prompt">
                    Don't have an account?
                    <a href="{{ route('register') }}">Sign up — it's free</a>
                </p>
                @endif

            </div>{{-- /.login-card --}}
        </div>{{-- /.form-box --}}
    </main>

</div>{{-- /.login-shell --}}

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('pw-toggle-icon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>

</body>
</html>
