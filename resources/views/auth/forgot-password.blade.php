<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <title>Forgot Password | CarpoolHub</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/icon.png') }}">
    {{-- Same shared classes as the login page (login-shell, login-card, field-row, etc.) — no page-specific CSS needed. --}}
    <link rel="stylesheet" href="{{ asset('css/auth-login.css') }}?v={{ filemtime(public_path('css/auth-login.css')) }}">
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
                Locked out?<br>
                <span>Let's get you back in.</span>
            </h2>

            <p class="brand-tagline">
                Enter the email on your account and we'll send you a link
                to choose a new password.
            </p>

            <ul class="brand-features">
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-shield-halved"></i></span>
                    Secure, one-time reset link
                </li>
                <li>
                    <span class="feat-icon"><i class="fa-solid fa-clock"></i></span>
                    Link expires after 60 minutes
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
                    <h1 class="login-card-title">Forgot password?</h1>
                    <p class="login-card-sub">No worries — enter your email and we'll send you a reset link.</p>
                </div>

                {{-- Session status (e.g. reset link sent) --}}
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

                <form method="POST" action="{{ route('password.email') }}" novalidate>
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

                    </div>{{-- /.field-group --}}

                    <button type="submit" class="btn-primary-login" style="margin-top: 22px;">
                        <i class="fa-solid fa-paper-plane"></i>
                        Send reset link
                    </button>

                </form>

                <div class="card-divider"></div>

                <p class="register-prompt">
                    <a href="{{ route('login') }}"><i class="fa-solid fa-arrow-left" style="margin-right: 6px;"></i>Back to log in</a>
                </p>

            </div>{{-- /.login-card --}}
        </div>{{-- /.form-box --}}
    </main>

</div>{{-- /.login-shell --}}

</body>
</html>
