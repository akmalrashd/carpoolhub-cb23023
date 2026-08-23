<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <title>Log In | CarpoolHub</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/icon.png') }}">
    {{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
    <link rel="stylesheet" href="{{ asset('css/auth-login.css') }}?v={{ filemtime(public_path('css/auth-login.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/bg-pattern.css') }}?v={{ filemtime(public_path('css/bg-pattern.css')) }}">
    @include('layouts.partials.pwa-head')
    {{-- Only ever does anything inside Telegram's own Mini App webview —
         see the inline script before </body>. A no-op everywhere else. --}}
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
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
                Share the ride,<br>
                <span>save the cost.</span>
            </h2>

            <p class="brand-tagline">
                Connect with colleagues and neighbours heading your way —
                split fares fairly, ride with verified drivers, and make
                your daily commute simpler.
            </p>

            <ul class="brand-features">
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-route"></i></span>
                    Smart matching to your route
                </li>
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-wallet"></i></span>
                    Fair, transparent fare splitting
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

            <div class="login-card">

                {{-- Mobile logo (hidden on desktop) --}}
                <div class="mobile-logo-wrap">
                    <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="" class="mobile-logo-icon">
                    <span class="mobile-logo-text">Carpool<span>Hub</span></span>
                </div>

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

    // Telegram Mini App auto-login. Landing here at all means the auth
    // middleware just redirected an unauthenticated request — Telegram's
    // webview never carries a CarpoolHub session cookie, so every "Open in
    // App" tap would otherwise dead-end on this exact form. Entirely inert
    // outside Telegram: initData is only ever non-empty inside its webview.
    (function () {
        const tg = window.Telegram && window.Telegram.WebApp;
        if (!tg || !tg.initData) return;

        tg.ready();

        fetch('{{ route('telegram.miniapp-auth') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: 'initData=' + encodeURIComponent(tg.initData),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data && data.success && data.redirect) {
                    window.location.href = data.redirect;
                }
                // Any failure (not linked yet, expired, inactive account) —
                // leave the normal login form visible, no special handling.
            })
            .catch(() => { /* fall through to the normal login form */ });
    })();
</script>

</body>
</html>
