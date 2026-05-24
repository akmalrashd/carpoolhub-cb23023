<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CarpoolHub — Share the Ride, Split the Cost</title>
    <meta name="description" content="CarpoolHub makes carpooling in Malaysia easy. Post a trip, find passengers, and split fuel costs with trusted connections.">

    <!-- Google Fonts: Inter + Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ============================================================
           CARPOOLHUB DESIGN TOKENS
           ============================================================ */
        :root {
            /* Yellow palette */
            --ch-yellow:       #FACC15;
            --ch-yellow-deep:  #E6B800;
            --ch-yellow-ink:   #2A1E04;
            --ch-yellow-tint:  #FFFBEA;
            --ch-yellow-line:  #F2D24A;

            /* Ink / text */
            --ink:   #0B1220;
            --ink-2: #1F2937;
            --ink-3: #475569;
            --muted:   #64748B;
            --muted-2: #94A3B8;

            /* Surfaces */
            --hairline:        #ECE7DA;
            --hairline-strong: #DAD2BE;
            --surface:   #FFFFFF;
            --surface-2: #FAF7EE;
            --canvas:    #F4EFE2;

            /* Success */
            --success:      #16A34A;
            --success-soft: #DCFCE7;
            --success-ink:  #065F46;

            /* Radii */
            --r-sm:   10px;
            --r-md:   14px;
            --r-lg:   18px;
            --r-pill: 999px;

            /* Shadows */
            --shadow-1: 0 1px 3px rgba(11,18,32,.08), 0 1px 2px rgba(11,18,32,.06);
            --shadow-2: 0 4px 12px rgba(11,18,32,.10), 0 2px 6px rgba(11,18,32,.06);
            --shadow-3: 0 12px 32px rgba(11,18,32,.14), 0 4px 12px rgba(11,18,32,.08);

            /* Typography */
            --font-display: "Poppins", system-ui, sans-serif;
            --font-ui:      "Inter",   system-ui, sans-serif;
        }

        /* ============================================================
           RESET & BASE
           ============================================================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-ui);
            color: var(--ink);
            background: var(--surface);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        ul { list-style: none; }
        img { max-width: 100%; display: block; }

        /* ============================================================
           UTILITY CLASSES
           ============================================================ */
        .container {
            width: 100%;
            max-width: 1120px;
            margin-inline: auto;
            padding-inline: 24px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: var(--r-pill);
            font-family: var(--font-ui);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid transparent;
            transition: background .18s, color .18s, border-color .18s, box-shadow .18s, transform .12s;
            white-space: nowrap;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }

        .btn-yellow {
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
            border-color: var(--ch-yellow);
        }
        .btn-yellow:hover {
            background: var(--ch-yellow-deep);
            border-color: var(--ch-yellow-deep);
            box-shadow: 0 4px 16px rgba(250,204,21,.35);
        }

        .btn-ghost-white {
            background: transparent;
            color: #ffffff;
            border-color: rgba(255,255,255,.45);
        }
        .btn-ghost-white:hover {
            background: rgba(255,255,255,.10);
            border-color: rgba(255,255,255,.75);
        }

        .btn-outline {
            background: transparent;
            color: var(--ink);
            border-color: var(--hairline-strong);
        }
        .btn-outline:hover {
            border-color: var(--ch-yellow);
            color: var(--ch-yellow-ink);
        }

        .btn-sm {
            padding: 9px 20px;
            font-size: .875rem;
        }

        /* ============================================================
           NAV
           ============================================================ */
        .site-nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--surface);
            border-bottom: 1px solid var(--hairline);
            box-shadow: var(--shadow-1);
        }

        .site-nav__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }

        .site-nav__logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.01em;
        }

        .site-nav__logo-icon {
            width: 36px;
            height: 36px;
            background: var(--ch-yellow);
            border-radius: var(--r-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--ch-yellow-ink);
            flex-shrink: 0;
        }

        .site-nav__actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* ============================================================
           HERO
           ============================================================ */
        .hero {
            background: var(--ink);
            padding: 100px 0 80px;
            overflow: hidden;
            position: relative;
        }

        /* Subtle grid overlay */
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(250,204,21,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(250,204,21,.04) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        .hero__inner {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(250,204,21,.12);
            border: 1px solid rgba(250,204,21,.25);
            border-radius: var(--r-pill);
            padding: 6px 14px;
            font-size: .8125rem;
            font-weight: 600;
            color: var(--ch-yellow);
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 24px;
        }

        .hero__headline {
            font-family: var(--font-display);
            font-size: clamp(2.25rem, 5vw, 3.5rem);
            font-weight: 800;
            color: #ffffff;
            line-height: 1.15;
            letter-spacing: -.02em;
            margin-bottom: 20px;
        }

        .hero__headline span {
            color: var(--ch-yellow);
        }

        .hero__sub {
            font-size: 1.125rem;
            color: var(--muted-2);
            line-height: 1.7;
            margin-bottom: 36px;
            max-width: 480px;
        }

        .hero__ctas {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: center;
        }

        /* Hero visual — abstract road illustration using CSS + SVG */
        .hero__visual {
            position: relative;
            height: 380px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero__road-svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 12px 40px rgba(250,204,21,.18));
        }

        /* ============================================================
           HOW IT WORKS
           ============================================================ */
        .how {
            padding: 96px 0;
            background: var(--surface-2);
        }

        .section-label {
            text-align: center;
            font-size: .8125rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--ch-yellow-deep);
            margin-bottom: 12px;
        }

        .section-title {
            font-family: var(--font-display);
            text-align: center;
            font-size: clamp(1.75rem, 3.5vw, 2.5rem);
            font-weight: 700;
            color: var(--ink);
            line-height: 1.2;
            letter-spacing: -.015em;
            margin-bottom: 16px;
        }

        .section-sub {
            text-align: center;
            font-size: 1.0625rem;
            color: var(--muted);
            max-width: 540px;
            margin: 0 auto 60px;
            line-height: 1.65;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            position: relative;
        }

        /* connector line between steps */
        .steps-grid::before {
            content: "";
            position: absolute;
            top: 28px;
            left: calc(16.66% + 28px);
            right: calc(16.66% + 28px);
            height: 2px;
            background: linear-gradient(90deg, var(--ch-yellow) 0%, var(--ch-yellow-line) 100%);
            z-index: 0;
        }

        .step-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 36px 28px 32px;
            position: relative;
            z-index: 1;
            box-shadow: var(--shadow-1);
            transition: box-shadow .2s, transform .2s;
        }
        .step-card:hover {
            box-shadow: var(--shadow-3);
            transform: translateY(-4px);
        }

        .step-number {
            width: 56px;
            height: 56px;
            background: var(--ch-yellow);
            border-radius: var(--r-pill);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--ch-yellow-ink);
            margin-bottom: 24px;
            position: relative;
            z-index: 2;
            box-shadow: 0 0 0 6px var(--surface-2);
        }

        .step-icon {
            font-size: 1.5rem;
            margin-bottom: 16px;
            color: var(--ch-yellow-deep);
        }

        .step-title {
            font-family: var(--font-display);
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 10px;
        }

        .step-desc {
            font-size: .9375rem;
            color: var(--muted);
            line-height: 1.6;
        }

        /* ============================================================
           FEATURES
           ============================================================ */
        .features {
            padding: 96px 0;
            background: var(--surface);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .feature-card {
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 32px 24px;
            transition: box-shadow .2s, transform .2s, background .2s;
        }
        .feature-card:hover {
            background: var(--ch-yellow-tint);
            border-color: var(--ch-yellow-line);
            box-shadow: var(--shadow-2);
            transform: translateY(-3px);
        }

        .feature-icon {
            width: 52px;
            height: 52px;
            background: var(--ch-yellow);
            border-radius: var(--r-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--ch-yellow-ink);
            margin-bottom: 20px;
            flex-shrink: 0;
        }

        .feature-title {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 10px;
        }

        .feature-desc {
            font-size: .9rem;
            color: var(--muted);
            line-height: 1.6;
        }

        /* ============================================================
           STATS BAR
           ============================================================ */
        .stats-bar {
            background: var(--ink-2);
            padding: 60px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .stat-item {
            text-align: center;
            padding: 24px 16px;
            position: relative;
        }
        .stat-item + .stat-item::before {
            content: "";
            position: absolute;
            left: 0;
            top: 20%;
            height: 60%;
            width: 1px;
            background: rgba(255,255,255,.12);
        }

        .stat-value {
            font-family: var(--font-display);
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            color: var(--ch-yellow);
            line-height: 1.1;
            margin-bottom: 8px;
            letter-spacing: -.02em;
        }

        .stat-label {
            font-size: .9375rem;
            color: var(--muted-2);
            font-weight: 500;
        }

        /* ============================================================
           CTA SECTION
           ============================================================ */
        .cta-section {
            padding: 100px 0;
            background: var(--surface-2);
            text-align: center;
        }

        .cta-section__inner {
            max-width: 600px;
            margin: 0 auto;
        }

        .cta-section__title {
            font-family: var(--font-display);
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            color: var(--ink);
            line-height: 1.2;
            letter-spacing: -.02em;
            margin-bottom: 16px;
        }

        .cta-section__sub {
            font-size: 1.0625rem;
            color: var(--muted);
            margin-bottom: 40px;
            line-height: 1.65;
        }

        .cta-section__actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        /* ============================================================
           FOOTER
           ============================================================ */
        .site-footer {
            background: var(--ink);
            padding: 40px 0;
            color: var(--muted-2);
        }

        .site-footer__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .site-footer__logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
        }

        .site-footer__logo-icon {
            width: 28px;
            height: 28px;
            background: var(--ch-yellow);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            color: var(--ch-yellow-ink);
        }

        .site-footer__copy {
            font-size: .875rem;
            color: var(--muted);
        }

        .site-footer__links {
            display: flex;
            gap: 24px;
        }

        .site-footer__links a {
            font-size: .875rem;
            color: var(--muted-2);
            transition: color .15s;
        }
        .site-footer__links a:hover {
            color: var(--ch-yellow);
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 900px) {
            .hero__inner {
                grid-template-columns: 1fr;
                gap: 48px;
            }
            .hero__visual { height: 280px; }
            .features-grid { grid-template-columns: repeat(2, 1fr); }
            .steps-grid::before { display: none; }
        }

        @media (max-width: 640px) {
            .hero { padding: 72px 0 60px; }
            .steps-grid { grid-template-columns: 1fr; }
            .features-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .stat-item + .stat-item::before { display: none; }
            .stat-item + .stat-item {
                border-top: 1px solid rgba(255,255,255,.08);
            }
            .site-footer__inner { flex-direction: column; text-align: center; }
            .site-footer__links { justify-content: center; }
            .hero__ctas { flex-direction: column; align-items: flex-start; }
            .cta-section__actions { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>

    {{-- ================================================================
         STICKY NAV
         ================================================================ --}}
    <nav class="site-nav">
        <div class="container">
            <div class="site-nav__inner">
                {{-- Logo --}}
                <a href="{{ url('/') }}" class="site-nav__logo">
                    <div class="site-nav__logo-icon">
                        <i class="fa-solid fa-car-side"></i>
                    </div>
                    CarpoolHub
                </a>

                {{-- Nav actions --}}
                <div class="site-nav__actions">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/home') }}" class="btn btn-sm btn-outline">
                                <i class="fa-solid fa-gauge-simple-high"></i>
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-sm btn-outline">
                                Log In
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-sm btn-yellow">
                                    Sign Up
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-sm btn-yellow">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    {{-- ================================================================
         HERO
         ================================================================ --}}
    <section class="hero">
        <div class="container">
            <div class="hero__inner">

                {{-- Copy --}}
                <div class="hero__copy">
                    <div class="hero__eyebrow">
                        <i class="fa-solid fa-location-dot"></i>
                        Malaysia's Carpooling Platform
                    </div>

                    <h1 class="hero__headline">
                        Share the ride.<br>
                        <span>Split the cost.</span>
                    </h1>

                    <p class="hero__sub">
                        CarpoolHub connects Malaysian drivers and passengers heading the same way.
                        Save on fuel, reduce traffic, and build real connections — one trip at a time.
                    </p>

                    <div class="hero__ctas">
                        @guest
                            <a href="{{ route('login') }}" class="btn btn-yellow" style="font-size:1.0625rem; padding:16px 32px;">
                                <i class="fa-solid fa-rocket"></i>
                                Get Started Free
                            </a>
                        @else
                            <a href="{{ url('/home') }}" class="btn btn-yellow" style="font-size:1.0625rem; padding:16px 32px;">
                                <i class="fa-solid fa-rocket"></i>
                                Go to Dashboard
                            </a>
                        @endguest

                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="btn btn-ghost-white" style="font-size:1.0625rem; padding:16px 32px;">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                Explore Trips
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Hero visual — CSS/SVG road illustration --}}
                <div class="hero__visual" aria-hidden="true">
                    <svg class="hero__road-svg" viewBox="0 0 480 380" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Sky gradient -->
                        <defs>
                            <linearGradient id="skyGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#1F2937"/>
                                <stop offset="100%" stop-color="#0B1220"/>
                            </linearGradient>
                            <linearGradient id="roadGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#374151"/>
                                <stop offset="100%" stop-color="#1F2937"/>
                            </linearGradient>
                            <linearGradient id="sunGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#FACC15"/>
                                <stop offset="100%" stop-color="#E6B800"/>
                            </linearGradient>
                            <radialGradient id="glowGrad" cx="50%" cy="50%" r="50%">
                                <stop offset="0%" stop-color="#FACC15" stop-opacity=".25"/>
                                <stop offset="100%" stop-color="#FACC15" stop-opacity="0"/>
                            </radialGradient>
                            <clipPath id="sceneClip">
                                <rect width="480" height="380" rx="18"/>
                            </clipPath>
                        </defs>

                        <g clip-path="url(#sceneClip)">
                            <!-- Background -->
                            <rect width="480" height="380" fill="url(#skyGrad)"/>

                            <!-- Glow behind sun -->
                            <circle cx="380" cy="80" r="100" fill="url(#glowGrad)"/>

                            <!-- Sun / Moon -->
                            <circle cx="380" cy="80" r="38" fill="url(#sunGrad)" opacity=".92"/>
                            <circle cx="380" cy="80" r="50" fill="none" stroke="#FACC15" stroke-width="1.5" opacity=".3"/>
                            <circle cx="380" cy="80" r="64" fill="none" stroke="#FACC15" stroke-width="1" opacity=".15"/>

                            <!-- Mountains -->
                            <polygon points="0,260 120,120 240,260" fill="#1F2937" opacity=".9"/>
                            <polygon points="160,260 290,100 420,260" fill="#263548" opacity=".85"/>
                            <polygon points="280,260 380,140 480,260" fill="#1F2937" opacity=".9"/>

                            <!-- Ground -->
                            <rect x="0" y="255" width="480" height="125" fill="#111827"/>

                            <!-- Road -->
                            <path d="M120,380 L180,255 L300,255 L360,380 Z" fill="url(#roadGrad)"/>

                            <!-- Road markings — dashes -->
                            <rect x="234" y="270" width="12" height="24" rx="3" fill="#FACC15" opacity=".7"/>
                            <rect x="234" y="308" width="12" height="24" rx="3" fill="#FACC15" opacity=".5"/>
                            <rect x="234" y="346" width="12" height="24" rx="3" fill="#FACC15" opacity=".35"/>

                            <!-- Road edges -->
                            <line x1="180" y1="255" x2="120" y2="380" stroke="#FACC15" stroke-width="2" opacity=".4"/>
                            <line x1="300" y1="255" x2="360" y2="380" stroke="#FACC15" stroke-width="2" opacity=".4"/>

                            <!-- Car body (simplified flat-style) -->
                            <!-- Car shadow -->
                            <ellipse cx="240" cy="248" rx="60" ry="6" fill="#000" opacity=".3"/>

                            <!-- Car body base -->
                            <rect x="188" y="220" width="104" height="30" rx="4" fill="#FACC15"/>

                            <!-- Car roof -->
                            <path d="M208,220 Q220,198 260,198 Q276,198 284,210 L292,220 Z" fill="#E6B800"/>

                            <!-- Windshield -->
                            <path d="M218,220 Q224,206 256,206 Q268,206 274,216 L280,220 Z" fill="#0B1220" opacity=".7"/>

                            <!-- Windows -->
                            <rect x="212" y="202" width="22" height="16" rx="3" fill="#0B1220" opacity=".5"/>
                            <rect x="238" y="200" width="26" height="18" rx="3" fill="#0B1220" opacity=".5"/>

                            <!-- Car front -->
                            <path d="M292,222 L298,235 Q292,242 284,242 L284,222 Z" fill="#E6B800"/>

                            <!-- Car rear -->
                            <path d="M188,222 L182,235 Q188,242 196,242 L196,222 Z" fill="#D4AF00"/>

                            <!-- Wheels -->
                            <circle cx="210" cy="248" r="14" fill="#1F2937"/>
                            <circle cx="210" cy="248" r="9"  fill="#374151"/>
                            <circle cx="210" cy="248" r="4"  fill="#FACC15"/>

                            <circle cx="270" cy="248" r="14" fill="#1F2937"/>
                            <circle cx="270" cy="248" r="9"  fill="#374151"/>
                            <circle cx="270" cy="248" r="4"  fill="#FACC15"/>

                            <!-- Headlights -->
                            <rect x="293" y="228" width="6" height="8" rx="2" fill="#FFFBEA"/>
                            <path d="M299,232 L318,228 L318,236 Z" fill="#FFFBEA" opacity=".35"/>

                            <!-- Taillights -->
                            <rect x="181" y="228" width="5" height="8" rx="2" fill="#EF4444" opacity=".9"/>

                            <!-- Stars -->
                            <circle cx="60"  cy="30"  r="1.5" fill="#ffffff" opacity=".6"/>
                            <circle cx="140" cy="18"  r="1"   fill="#ffffff" opacity=".5"/>
                            <circle cx="210" cy="45"  r="1.5" fill="#ffffff" opacity=".4"/>
                            <circle cx="310" cy="25"  r="1"   fill="#ffffff" opacity=".6"/>
                            <circle cx="90"  cy="70"  r="1"   fill="#ffffff" opacity=".4"/>
                            <circle cx="160" cy="60"  r="1.5" fill="#ffffff" opacity=".5"/>
                            <circle cx="440" cy="40"  r="1"   fill="#ffffff" opacity=".4"/>
                            <circle cx="420" cy="130" r="1.5" fill="#ffffff" opacity=".3"/>

                            <!-- Road lines on ground -->
                            <line x1="0" y1="290" x2="480" y2="290" stroke="#1F2937" stroke-width="1" opacity=".5"/>
                        </g>
                    </svg>
                </div>

            </div>
        </div>
    </section>

    {{-- ================================================================
         HOW IT WORKS
         ================================================================ --}}
    <section class="how" id="how-it-works">
        <div class="container">
            <p class="section-label">How It Works</p>
            <h2 class="section-title">Three simple steps to share your ride</h2>
            <p class="section-sub">
                Whether you're a driver looking to offset fuel costs or a passenger
                who needs a reliable ride, CarpoolHub makes it effortless.
            </p>

            <div class="steps-grid">

                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon"><i class="fa-solid fa-road"></i></div>
                    <h3 class="step-title">Post Your Trip</h3>
                    <p class="step-desc">
                        Drivers set their origin, destination, date, time, available seats,
                        and fare contribution. It takes less than two minutes.
                    </p>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon"><i class="fa-solid fa-hand-pointer"></i></div>
                    <h3 class="step-title">Passengers Request to Join</h3>
                    <p class="step-desc">
                        Passengers browse available trips, find a route that matches their
                        schedule, and send a join request with one tap.
                    </p>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon"><i class="fa-solid fa-handshake"></i></div>
                    <h3 class="step-title">Share the Ride &amp; Split Costs</h3>
                    <p class="step-desc">
                        Once approved, travel together and split the fuel cost fairly.
                        Payments are tracked transparently inside the app.
                    </p>
                </div>

            </div>
        </div>
    </section>

    {{-- ================================================================
         FEATURES
         ================================================================ --}}
    <section class="features" id="features">
        <div class="container">
            <p class="section-label">Platform Features</p>
            <h2 class="section-title">Everything you need to carpool with confidence</h2>
            <p class="section-sub">
                Built specifically for the Malaysian commuter — every feature is designed
                to make shared rides safe, fair, and friction-free.
            </p>

            <div class="features-grid">

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-route"></i>
                    </div>
                    <h3 class="feature-title">Smart Routes</h3>
                    <p class="feature-desc">
                        Intelligent trip matching surfaces rides that genuinely align with
                        your origin and destination, reducing detours and wasted time.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="feature-title">Secure Payments</h3>
                    <p class="feature-desc">
                        Cost-sharing is tracked transparently. Drivers mark payments
                        received; passengers confirm — no disputes, no guesswork.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <h3 class="feature-title">Real-time Updates</h3>
                    <p class="feature-desc">
                        Instant notifications for join requests, approvals, payment
                        confirmations, and trip reminders keep everyone in sync.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <h3 class="feature-title">Build Connections</h3>
                    <p class="feature-desc">
                        Follow regular carpool partners and build a trusted network
                        of fellow commuters you can rely on every week.
                    </p>
                </div>

            </div>
        </div>
    </section>

    {{-- ================================================================
         STATS BAR
         ================================================================ --}}
    <section class="stats-bar">
        <div class="container">
            <div class="stats-grid">

                <div class="stat-item">
                    <div class="stat-value">1,000+</div>
                    <div class="stat-label">Trips Completed</div>
                </div>

                <div class="stat-item">
                    <div class="stat-value">500+</div>
                    <div class="stat-label">Active Users</div>
                </div>

                <div class="stat-item">
                    <div class="stat-value">RM 50,000+</div>
                    <div class="stat-label">Saved on Fuel</div>
                </div>

            </div>
        </div>
    </section>

    {{-- ================================================================
         CTA SECTION
         ================================================================ --}}
    <section class="cta-section">
        <div class="container">
            <div class="cta-section__inner">
                <h2 class="cta-section__title">Ready to carpool?</h2>
                <p class="cta-section__sub">
                    Join hundreds of Malaysian commuters who are already saving money,
                    cutting emissions, and making friends on every journey.
                </p>
                <div class="cta-section__actions">
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-yellow" style="font-size:1.0625rem; padding:16px 36px;">
                            <i class="fa-solid fa-user-plus"></i>
                            Sign Up Free
                        </a>
                    @else
                        <a href="{{ url('/home') }}" class="btn btn-yellow" style="font-size:1.0625rem; padding:16px 36px;">
                            <i class="fa-solid fa-gauge-simple-high"></i>
                            Go to Dashboard
                        </a>
                    @endguest
                    <a href="{{ route('login') }}" class="btn btn-outline" style="font-size:1.0625rem; padding:16px 36px;">
                        Log In
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================================
         FOOTER
         ================================================================ --}}
    <footer class="site-footer">
        <div class="container">
            <div class="site-footer__inner">

                <div class="site-footer__logo">
                    <div class="site-footer__logo-icon">
                        <i class="fa-solid fa-car-side"></i>
                    </div>
                    CarpoolHub
                </div>

                <p class="site-footer__copy">
                    &copy; {{ date('Y') }} CarpoolHub. All rights reserved.
                </p>

                <div class="site-footer__links">
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}">Log In</a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">Sign Up</a>
                    @endif
                </div>

            </div>
        </div>
    </footer>

</body>
</html>
