<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <title>Verify Your Email | CarpoolHub</title>
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
                One last step<br>
                <span>before you ride.</span>
            </h2>

            <p class="brand-tagline">
                Confirming your email keeps CarpoolHub trips, payments, and
                messages going to a real inbox — yours.
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
                    <h1 class="login-card-title">Verify your email</h1>
                    <p class="login-card-sub">
                        We sent a verification link to
                        <strong>{{ auth()->user()->email }}</strong>.
                        Click it to unlock your account.
                    </p>
                </div>

                {{-- Session status (e.g. link resent) --}}
                @if (session('status'))
                    <div class="status-banner" role="alert">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn-primary-login">
                        <i class="fa-solid fa-paper-plane"></i>
                        Resend verification email
                    </button>
                </form>

                <div class="card-divider"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <p class="register-prompt">
                        Wrong account?
                        <button type="submit" style="background:none; border:none; padding:0; font:inherit; cursor:pointer; color:var(--ink-2); font-weight:600; text-decoration:none; border-bottom:1.5px solid var(--ch-yellow-line);">Log out</button>
                    </p>
                </form>

            </div>{{-- /.login-card --}}
        </div>{{-- /.form-box --}}
    </main>

</div>{{-- /.login-shell --}}

</body>
</html>
