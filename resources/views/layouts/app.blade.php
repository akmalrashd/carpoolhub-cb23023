<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarpoolHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('build/assets/branding/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('build/assets/branding/icon.png') }}">

    <style>
        :root {
            --bg: #eef2f7;
            --bg-glow: rgba(17, 24, 39, 0.06);
            --secondary: #ffffff;
            --card: #ffffff;
            --border: #dbe2ea;
            --text: #0f172a;
            --text-muted: #64748b;
            --accent-soft: #f1f5f9;
            --danger: #dc2626;
            --success-bg: rgba(22, 163, 74, 0.1);
            --success-border: rgba(22, 163, 74, 0.25);
            --success-text: #166534;
        }

        * {
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: Inter, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top, #f9fafb, #e5e7eb),
                linear-gradient(135deg, #f3f4f6, #e5e7eb);
            background-attachment: fixed;
        }
        body.modal-open {
            overflow: hidden;
            touch-action: none;
        }
        .card,
        .panel,
        .container-box {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }
        .page-wrapper {
            padding: 16px;
        }

        a { color: inherit; }

        .mobile-header {
            position: sticky;
            top: 0;
            z-index: 2000;
            height: 72px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background:
                radial-gradient(circle at 85% -10%, var(--bg-glow), transparent 35%),
                radial-gradient(circle at 10% 110%, rgba(30, 41, 59, 0.06), transparent 42%),
                #ffffff;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .navbar,
        .desktop-topbar {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .page-load-line {
            position: fixed;
            left: 0;
            right: 0;
            top: 72px;
            height: 3px;
            z-index: 1900;
            pointer-events: none;
            opacity: 0;
            transition: opacity .16s ease;
            overflow: hidden;
        }
        .page-load-line.active {
            opacity: 1;
        }
        .page-load-line::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 100%;
            transform-origin: left center;
            transform: scaleX(0);
            background: linear-gradient(90deg, #facc15 0%, #f59e0b 100%);
            animation: pageLoadScale 1.05s cubic-bezier(0.22, 1, 0.36, 1) infinite;
            box-shadow: 0 0 10px rgba(250, 204, 21, 0.58);
        }
        @keyframes pageLoadScale {
            0% { transform: scaleX(0); opacity: .9; }
            85% { transform: scaleX(1); opacity: 1; }
            100% { transform: scaleX(1); opacity: .55; }
        }

        .mobile-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            position: relative;
            z-index: 2;
        }

        .mobile-back-btn {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text);
            display: grid;
            place-items: center;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 14px;
        }

        .mobile-back-btn:hover {
            background: var(--accent-soft);
        }

        .menu-toggle-btn {
            width: 40px;
            height: 40px;
            border-radius: 9px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text);
            display: grid;
            place-items: center;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 15px;
        }

        .desktop-menu-toggle {
            position: relative;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .desktop-menu-toggle .bar {
            width: 16px;
            height: 2px;
            background: var(--text);
            border-radius: 2px;
            transition: transform 0.2s ease, opacity 0.2s ease;
            transform-origin: center;
        }

        .mobile-brand-logo {
            width: 170px;
            max-width: 50vw;
            height: auto;
            object-fit: contain;
            display: block;
        }
        .header-logo-link {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .mobile-header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 2;
        }

        .mobile-header.has-back-btn .mobile-brand-logo {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            max-width: 46vw;
            z-index: 1;
        }

        .mobile-header.has-back-btn .mobile-header-left {
            position: static;
        }

        .notification-wrap { position: relative; }

        .notification-toggle {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text);
            display: grid;
            place-items: center;
            cursor: pointer;
            list-style: none;
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: var(--danger);
            color: #fff;
            border-radius: 999px;
            min-width: 18px;
            height: 18px;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        .notification-dropdown {
            position: absolute;
            right: 0;
            margin-top: 8px;
            width: min(90vw, 340px);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px;
            z-index: 2100;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
            max-height: min(68vh, 520px);
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        .notification-dropdown-head {
            position: sticky;
            top: -10px;
            z-index: 1;
            margin: -10px -10px 8px;
            padding: 10px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-item { border: 1px solid var(--border); border-radius: 10px; padding: 8px; margin-bottom: 8px; }
        .notification-item.unread { background: #f8fafc; }
        .notification-item-link { text-decoration: none; color: inherit; display: block; border-radius: 8px; }
        .notification-item-link:hover .notification-item-title { color: #1d4ed8; }
        .notification-item-title { font-weight: 600; margin-bottom: 3px; line-height: 1.25; }
        .notification-item-message { color: var(--text-muted); font-size: 13px; margin-bottom: 6px; line-height: 1.35; }
        .notification-item-row { display: flex; justify-content: space-between; align-items: center; }
        .notification-item-time { color: var(--text-muted); font-size: 12px; }
        .notification-empty { color: var(--text-muted); font-size: 13px; padding: 4px 0; }
        .notification-footer {
            position: sticky;
            bottom: -10px;
            margin: 6px -10px -10px;
            padding: 8px 10px;
            background: #ffffff;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: center;
        }
        .notification-view-all {
            color: #0f172a;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            padding: 2px 4px;
            line-height: 1;
        }
        .notification-view-all:hover {
            color: #1d4ed8;
        }
        .link-action { border: none; background: transparent; color: #0f172a; font-size: 12px; cursor: pointer; }
        .profile-wrap { position: relative; }

        .profile-toggle {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text);
            cursor: pointer;
            display: grid;
            place-items: center;
            list-style: none;
        }

        .avatar-initial {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: #0f172a;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .profile-dropdown {
            position: absolute;
            right: 0;
            margin-top: 8px;
            width: 180px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 8px;
            z-index: 2100;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
            display: grid;
            gap: 4px;
        }

        .profile-menu-link,
        .profile-menu-btn {
            width: 100%;
            text-decoration: none;
            color: var(--text);
            border: 0;
            background: transparent;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 9px;
            cursor: pointer;
        }

        .profile-menu-link:hover,
        .profile-menu-btn:hover {
            background: var(--accent-soft);
        }

        .mobile-drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.18s ease;
            z-index: 2200;
        }

        .mobile-drawer {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: min(84vw, 360px);
            background: #ffffff;
            transform: translateX(-100%);
            transition: transform 0.2s ease;
            z-index: 2201;
            display: flex;
            flex-direction: column;
            color: var(--text);
            box-shadow: 8px 0 26px rgba(15, 23, 42, 0.28);
        }

        body.mobile-drawer-open { overflow: hidden; }
        body.mobile-drawer-open .mobile-drawer { transform: translateX(0); }
        body.mobile-drawer-open .mobile-drawer-overlay { opacity: 1; pointer-events: auto; }

        .mobile-drawer-head {
            min-height: 112px;
            padding: 12px;
            background:
                radial-gradient(circle at 85% -10%, var(--bg-glow), transparent 35%),
                radial-gradient(circle at 10% 110%, rgba(30, 41, 59, 0.06), transparent 42%),
                #ffffff;
            border-bottom: 1px solid var(--border);
        }

        .mobile-drawer-head-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mobile-close-btn {
            width: 40px;
            height: 40px;
            border-radius: 9px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text);
            display: grid;
            place-items: center;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 15px;
        }

        .mobile-drawer-logo {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .mobile-drawer-title {
            font-family: Poppins, sans-serif;
            font-size: 18px;
            color: var(--text);
            font-weight: 600;
        }

        .mobile-drawer-search {
            margin-top: 12px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: #0f172a;
            font-size: 14px;
            padding: 9px 10px;
            outline: none;
        }

        .mobile-drawer-nav {
            padding: 10px 0;
            overflow-y: auto;
            display: grid;
            gap: 2px;
        }

        .mobile-drawer-nav a {
            text-decoration: none;
            color: var(--text);
            padding: 11px 14px;
            font-size: 16px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mobile-drawer-nav a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .mobile-drawer-nav a.active,
        .mobile-drawer-nav a:hover {
            background: var(--accent-soft);
        }

        .desktop-topbar,
        .desktop-sidebar {
            display: none;
        }

        .app-shell {
            min-height: calc(100vh - 72px);
        }

        .main-content {
            padding: 14px 14px 94px;
            will-change: transform, opacity;
        }
        .main-content.page-enter-from-right { animation: pageEnterFromRight .34s cubic-bezier(0.22, 1, 0.36, 1); }
        .main-content.page-enter-from-left { animation: pageEnterFromLeft .34s cubic-bezier(0.22, 1, 0.36, 1); }
        .main-content.page-exit-to-left { animation: pageExitToLeft .24s cubic-bezier(0.4, 0, 0.2, 1); }
        .main-content.page-exit-to-right { animation: pageExitToRight .24s cubic-bezier(0.4, 0, 0.2, 1); }
        .main-content.page-enter-fade { animation: pageEnterFade .3s cubic-bezier(0.22, 1, 0.36, 1); }
        .main-content.page-exit-fade { animation: pageExitFade .2s cubic-bezier(0.4, 0, 0.2, 1); }
        @keyframes pageEnterFromRight {
            from { opacity: .7; transform: translateX(26px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes pageEnterFromLeft {
            from { opacity: .7; transform: translateX(-26px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes pageExitToLeft {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: .7; transform: translateX(-22px); }
        }
        @keyframes pageExitToRight {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: .7; transform: translateX(22px); }
        }
        @keyframes pageEnterFade {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pageExitFade {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-8px); }
        }
        @supports (view-transition-name: root) {
            @view-transition {
                navigation: auto;
            }
            ::view-transition-old(root) {
                animation: 220ms cubic-bezier(0.4, 0, 0.2, 1) both fadeOld;
            }
            ::view-transition-new(root) {
                animation: 320ms cubic-bezier(0.22, 1, 0.36, 1) both fadeNew;
            }
            @keyframes fadeOld {
                from { opacity: 1; }
                to { opacity: .78; }
            }
            @keyframes fadeNew {
                from { opacity: .78; }
                to { opacity: 1; }
            }
        }

        .status-banner {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
            padding: 10px 12px;
            border-radius: 12px;
            margin-bottom: 12px;
            transition: opacity .35s ease, transform .35s ease;
        }

        .status-banner.hide {
            opacity: 0;
            transform: translateY(-4px);
            pointer-events: none;
        }

        .app-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid var(--border);
            padding: 3px 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-success { color: #166534; border-color: rgba(22, 163, 74, 0.3); }
        .status-danger { color: #b91c1c; border-color: rgba(220, 38, 38, 0.28); }
        .status-warning { color: #b45309; border-color: rgba(180, 83, 9, 0.28); }
        .status-info { color: #1d4ed8; border-color: rgba(29, 78, 216, 0.28); }

        .mobile-bottom-nav {
            position: fixed;
            left: 8px;
            right: 8px;
            bottom: 8px;
            z-index: 1900;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--border);
            border-top: 1px solid #e5e7eb;
            border-radius: 16px;
            backdrop-filter: blur(10px);
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 6px 6px max(6px, env(safe-area-inset-bottom));
            gap: 4px;
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
        }

        .mobile-bottom-nav > a,
        .mobile-bottom-nav > details > summary {
            text-decoration: none;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            min-height: 50px;
            border-radius: 11px;
            border: 1px solid transparent;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            list-style: none;
            position: relative;
            overflow: hidden;
            transition: transform 0.16s ease, background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease;
        }

        .mobile-bottom-nav .icon {
            font-size: 15px;
            line-height: 1;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .mobile-bottom-nav .icon i { transition: transform 0.16s ease; }

        .mobile-bottom-nav > a.active,
        .mobile-bottom-nav > details > summary.active,
        .mobile-bottom-nav > a:hover,
        .mobile-bottom-nav > details > summary:hover {
            color: var(--text);
            border-color: #d1d9e2;
            background: var(--accent-soft);
        }
        .mobile-bottom-nav > a.active .icon i,
        .mobile-bottom-nav > details > summary.active .icon i {
            transform: translateY(-1px);
        }
        .mobile-bottom-nav > a:active,
        .mobile-bottom-nav > details > summary:active {
            transform: scale(0.96);
        }
        .mobile-bottom-nav > a.tap-animate,
        .mobile-bottom-nav > details > summary.tap-animate {
            animation: navTapPop 0.22s ease;
        }
        @keyframes navTapPop {
            0% { transform: scale(1); }
            50% { transform: scale(0.93); }
            100% { transform: scale(1); }
        }

        .mobile-bottom-nav .more-menu { position: relative; }

        .mobile-bottom-nav .more-sheet {
            position: absolute;
            right: 0;
            bottom: 60px;
            width: 200px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 8px;
            display: grid;
            gap: 4px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }

        .mobile-bottom-nav .more-sheet a {
            text-decoration: none;
            color: var(--text);
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .mobile-bottom-nav .more-sheet a:hover { background: var(--accent-soft); }

        @media (min-width: 1024px) {
            .mobile-header,
            .mobile-drawer,
            .mobile-drawer-overlay,
            .mobile-bottom-nav {
                display: none !important;
            }

            .desktop-topbar {
                height: 72px;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 2100;
                padding: 0 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                background:
                    radial-gradient(circle at 85% -10%, var(--bg-glow), transparent 35%),
                    radial-gradient(circle at 10% 110%, rgba(30, 41, 59, 0.06), transparent 42%),
                    #ffffff;
                border-bottom: 1px solid var(--border);
            }
            .notification-dropdown {
                max-height: min(62vh, 520px);
            }
            .page-load-line {
                top: 72px;
            }

            .desktop-topbar-left {
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 0;
            }

            .desktop-menu-toggle { margin-right: 4px; }

            .desktop-brand-wrap {
                display: flex;
                align-items: center;
                gap: 8px;
                min-width: 0;
            }

            .desktop-brand-logo {
                width: 240px;
                max-width: min(40vw, 280px);
                height: auto;
                object-fit: contain;
                display: block;
            }

            .desktop-topbar-right {
                display: flex;
                align-items: center;
                gap: 10px;
                color: var(--text);
            }

            .app-shell {
                padding-top: 72px;
                min-height: 100vh;
                display: grid;
                grid-template-columns: 70px 1fr;
                transition: grid-template-columns 0.2s ease;
            }

            body.sidebar-expanded .app-shell {
                grid-template-columns: 242px 1fr;
            }

            .desktop-sidebar {
                display: block;
                position: sticky;
                top: 72px;
                height: calc(100vh - 72px);
                background: var(--secondary);
                overflow: hidden;
                padding: 10px 8px;
                box-shadow: inset -1px 0 0 var(--border);
            }

            .desktop-nav {
                display: grid;
                gap: 4px;
            }

            .desktop-nav a {
                text-decoration: none;
                color: var(--text-muted);
                min-height: 44px;
                border-radius: 10px;
                padding: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0;
                font-size: 16px;
                white-space: nowrap;
            }

            .desktop-nav a i {
                width: 18px;
                text-align: center;
                font-size: 16px;
                flex-shrink: 0;
            }

            .desktop-nav-label {
                display: none;
            }

            .desktop-nav a.active,
            .desktop-nav a:hover {
                background: var(--accent-soft);
                color: var(--text);
            }

            body.sidebar-expanded .desktop-nav a {
                justify-content: flex-start;
                gap: 12px;
                font-size: 15px;
                padding: 10px 12px;
            }

            body.sidebar-expanded .desktop-nav-label {
                display: inline;
            }

            body.sidebar-expanded .desktop-menu-toggle .bar:nth-child(1) {
                transform: translateY(7px) rotate(45deg);
            }

            body.sidebar-expanded .desktop-menu-toggle .bar:nth-child(2) {
                opacity: 0;
            }

            body.sidebar-expanded .desktop-menu-toggle .bar:nth-child(3) {
                transform: translateY(-7px) rotate(-45deg);
            }

            .main-content {
                padding: 18px 18px 24px;
            }
        }
    </style>
</head>
<body>
@php
    $headerNotifications = collect();
    $headerUnreadCount = 0;
    if (auth()->check()) {
        $headerNotifications = \App\Models\UserNotification::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(6)
            ->get();
        $headerUnreadCount = \App\Models\UserNotification::query()
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();
    }
@endphp

@auth
    @include('layouts.partials.mobile-header')

    <header class="desktop-topbar">
        <div class="desktop-topbar-left">
            <button type="button" class="menu-toggle-btn desktop-menu-toggle" id="sidebarToggle" aria-label="Togol bar sisi">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
            <div class="desktop-brand-wrap">
                <a href="{{ route('home') }}" class="header-logo-link" aria-label="Go to Home">
                    <img src="{{ asset('build/assets/branding/logo-horizontal-b.png') }}" alt="CarpoolHub" class="desktop-brand-logo">
                </a>
            </div>
        </div>
        <div class="desktop-topbar-right">
            <details class="notification-wrap">
                <summary class="notification-toggle">
                    <i class="fa-solid fa-bell" aria-hidden="true"></i>
                    @if($headerUnreadCount > 0)
                        <span class="notification-badge">{{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}</span>
                    @endif
                </summary>
                <div class="notification-dropdown">
                    <div class="notification-dropdown-head">
                        <strong>Notifications</strong>
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="link-action">Mark All</button>
                        </form>
                    </div>
                    <div class="notification-items" data-notification-items>
                        @forelse($headerNotifications as $notification)
                            <div class="notification-item {{ $notification->is_read ? '' : 'unread' }}">
                                <a href="{{ route('notifications.open', $notification) }}" class="notification-item-link">
                                    <div class="notification-item-title">{{ $notification->title }}</div>
                                    <div class="notification-item-message">{{ $notification->message }}</div>
                                </a>
                                <div class="notification-item-row">
                                    <span class="notification-item-time">{{ $notification->created_at?->diffForHumans() }}</span>
                                    @if(! $notification->is_read)
                                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="link-action">Read</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="notification-empty">No notifications.</div>
                        @endforelse
                    </div>
                    <div class="notification-footer">
                        <a href="{{ route('notifications.index') }}" class="notification-view-all">View All</a>
                    </div>
                </div>
            </details>
            <details class="profile-wrap">
                <summary class="profile-toggle">
                    <span class="avatar-initial">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                </summary>
                <div class="profile-dropdown">
                    <a href="{{ route('profile.index') }}" class="profile-menu-link">
                        <i class="fa-solid fa-gear"></i>
                        <span>Profile</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="profile-menu-btn">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </header>
    <div class="page-load-line" id="pageLoadLine" aria-hidden="true"></div>

    <div class="mobile-drawer-overlay" id="mobileDrawerOverlay"></div>
    <aside class="mobile-drawer" id="mobileDrawer">
        <div class="mobile-drawer-head">
            <div class="mobile-drawer-head-row">
                <button type="button" class="mobile-close-btn" id="mobileDrawerClose" aria-label="Close menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <img src="{{ asset('build/assets/branding/logo-small-b.png') }}" alt="CarpoolHub" class="mobile-drawer-logo">
                <div class="mobile-drawer-title">CarpoolHub</div>
            </div>
            <input type="text" class="mobile-drawer-search" placeholder="Search menu..." aria-label="Search menu">
        </div>

        <nav class="mobile-drawer-nav">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') || request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fa-solid fa-house"></i><span>Home</span></a>
            <a href="{{ route('trips.create') }}"><i class="fa-solid fa-plus"></i><span>New Trip</span></a>
            <a href="{{ route('trips.index') }}" class="{{ request()->routeIs('trips.*') ? 'active' : '' }}"><i class="fa-solid fa-clock-rotate-left"></i><span>My Trips</span></a>
            <a href="{{ route('explore.index') }}" class="{{ request()->routeIs('explore.*') ? 'active' : '' }}"><i class="fa-solid fa-compass"></i><span>Explore</span></a>
            <a href="{{ route('saved-routes.index') }}" class="{{ request()->routeIs('saved-routes.*') ? 'active' : '' }}"><i class="fa-solid fa-route"></i><span>Routes</span></a>
            <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}"><i class="fa-solid fa-receipt"></i><span>Payments</span></a>
            <a href="{{ route('connections.index') }}" class="{{ request()->routeIs('connections.*') ? 'active' : '' }}"><i class="fa-solid fa-user-group"></i><span>Connections</span></a>
            <a href="{{ route('billing-cycles.index') }}" class="{{ request()->routeIs('billing-cycles.*') ? 'active' : '' }}"><i class="fa-solid fa-calendar-check"></i><span>Monthly Summary</span></a>
            <a href="{{ route('archive.index') }}" class="{{ request()->routeIs('archive.*') ? 'active' : '' }}"><i class="fa-solid fa-box-archive"></i><span>Archive</span></a>
            <a href="{{ route('profile.index') }}" class="{{ request()->routeIs('profile.*') || request()->routeIs('settings.*') ? 'active' : '' }}"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
            <form action="{{ route('logout') }}" method="POST" style="padding: 0 8px;">
                @csrf
                <button type="submit" class="profile-menu-btn" style="width:100%;">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
            @if(auth()->check() && auth()->user()->role === 'admin')
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="fa-solid fa-users-gear"></i><span>Users Admin</span></a>
                <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i><span>Reports</span></a>
            @endif
        </nav>
    </aside>
@endauth

<div class="app-shell">
    @auth
        @include('layouts.partials.desktop-sidebar')
    @endauth

    <main>
        <section class="main-content">
            @if(session('status'))
                <div class="status-banner">{{ session('status') }}</div>
            @endif

            @yield('content')
        </section>
    </main>
</div>

@auth
    @include('layouts.partials.mobile-bottom-nav')
@endauth

<script>
    (function () {
        var pageLoadLine = document.getElementById('pageLoadLine');
        var pageLoading = false;

        function startPageLoadLine() {
            if (!pageLoadLine || pageLoading) {
                return;
            }
            pageLoading = true;
            pageLoadLine.classList.add('active');
        }

        function stopPageLoadLine() {
            if (!pageLoadLine) {
                return;
            }
            pageLoadLine.classList.remove('active');
            pageLoading = false;
        }

        window.addEventListener('pageshow', stopPageLoadLine);
        window.addEventListener('load', stopPageLoadLine);

        var desktopToggle = document.getElementById('sidebarToggle');
        var desktopKey = 'carpoolhub_sidebar_expanded';
        var desktopQuery = window.matchMedia('(min-width: 1024px)');

        function syncDesktop() {
            var expanded = localStorage.getItem(desktopKey) === '1';
            document.body.classList.toggle('sidebar-expanded', expanded && desktopQuery.matches);
        }

        syncDesktop();

        if (desktopToggle) {
            desktopToggle.addEventListener('click', function () {
                var nowExpanded = !document.body.classList.contains('sidebar-expanded');
                document.body.classList.toggle('sidebar-expanded', nowExpanded);
                localStorage.setItem(desktopKey, nowExpanded ? '1' : '0');
            });
        }

        desktopQuery.addEventListener('change', syncDesktop);

        var mobileToggle = document.getElementById('mobileMenuToggle');
        var mobileBackBtn = document.getElementById('mobileBackBtn');
        var mobileClose = document.getElementById('mobileDrawerClose');
        var mobileOverlay = document.getElementById('mobileDrawerOverlay');
        var dropdownDetails = document.querySelectorAll('.notification-wrap, .profile-wrap, .more-menu');

        function closeOpenPopups() {
            dropdownDetails.forEach(function (detail) {
                if (detail.hasAttribute('open')) {
                    detail.removeAttribute('open');
                }
            });
        }

        function openDrawer() {
            document.body.classList.add('mobile-drawer-open');
        }

        function closeDrawer() {
            document.body.classList.remove('mobile-drawer-open');
        }

        if (mobileToggle) {
            mobileToggle.addEventListener('click', openDrawer);
        }

        if (mobileBackBtn) {
            mobileBackBtn.addEventListener('click', function () {
                var beforeBackEvent = new CustomEvent('carpoolhub:mobile-back', { cancelable: true });
                var shouldContinueDefaultBack = window.dispatchEvent(beforeBackEvent);
                if (!shouldContinueDefaultBack) {
                    return;
                }

                if (document.referrer && document.referrer.indexOf(window.location.origin) === 0) {
                    window.history.back();
                    return;
                }

                var fallbackUrl = mobileBackBtn.getAttribute('data-fallback-url') || '/home';
                window.location.href = fallbackUrl;
            });
        }

        if (mobileClose) {
            mobileClose.addEventListener('click', closeDrawer);
        }

        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', closeDrawer);
        }

        document.addEventListener('click', function (event) {
            dropdownDetails.forEach(function (detail) {
                if (detail.hasAttribute('open') && !detail.contains(event.target)) {
                    detail.removeAttribute('open');
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeOpenPopups();
                closeDrawer();
            }
        });

        var statusBanner = document.querySelector('.status-banner');
        if (statusBanner) {
            window.setTimeout(function () {
                statusBanner.classList.add('hide');
                window.setTimeout(function () {
                    if (statusBanner && statusBanner.parentNode) {
                        statusBanner.parentNode.removeChild(statusBanner);
                    }
                }, 380);
            }, 3200);
        }

        var bottomNavItems = document.querySelectorAll('.mobile-bottom-nav > a, .mobile-bottom-nav > details > summary');
        var bottomNavLinks = Array.prototype.slice.call(document.querySelectorAll('.mobile-bottom-nav > a'));
        var desktopNavLinks = Array.prototype.slice.call(document.querySelectorAll('.desktop-nav a'));
        var mainContent = document.querySelector('.main-content');
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var isMobileViewport = window.matchMedia('(max-width: 1023px)').matches;
        var isDesktopViewport = window.matchMedia('(min-width: 1024px)').matches;
        var enterKey = 'carpoolhub_mobile_enter_direction';
        var desktopEnterKey = 'carpoolhub_desktop_enter_direction';

        if (!reduceMotion && mainContent) {
            var savedEnterDirection = sessionStorage.getItem(enterKey);
            var savedDesktopEnterDirection = sessionStorage.getItem(desktopEnterKey);
            if (isMobileViewport && (savedEnterDirection === 'left' || savedEnterDirection === 'right')) {
                mainContent.classList.add(savedEnterDirection === 'right' ? 'page-enter-from-right' : 'page-enter-from-left');
                sessionStorage.removeItem(enterKey);
            } else if (isDesktopViewport && (savedDesktopEnterDirection === 'left' || savedDesktopEnterDirection === 'right')) {
                mainContent.classList.add(savedDesktopEnterDirection === 'right' ? 'page-enter-from-right' : 'page-enter-from-left');
                sessionStorage.removeItem(desktopEnterKey);
            } else if (isDesktopViewport) {
                mainContent.classList.add('page-enter-fade');
            }
        }

        if (mainContent) {
            mainContent.addEventListener('animationend', function () {
                mainContent.classList.remove('page-enter-from-right', 'page-enter-from-left', 'page-exit-to-left', 'page-exit-to-right', 'page-enter-fade', 'page-exit-fade');
            });
        }

        bottomNavItems.forEach(function (item) {
            item.addEventListener('click', function (event) {
                item.classList.remove('tap-animate');
                void item.offsetWidth;
                item.classList.add('tap-animate');

                if (item.tagName !== 'A') {
                    return;
                }

                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                var href = item.getAttribute('href');
                if (!href || href.charAt(0) === '#') {
                    return;
                }

                if (reduceMotion || !isMobileViewport || !mainContent) {
                    return;
                }

                var currentIndex = bottomNavLinks.findIndex(function (link) { return link.classList.contains('active'); });
                var targetIndex = bottomNavLinks.indexOf(item);
                if (currentIndex === -1 || targetIndex === -1 || currentIndex === targetIndex) {
                    return;
                }

                event.preventDefault();
                startPageLoadLine();

                var movingRight = targetIndex > currentIndex;
                var exitClass = movingRight ? 'page-exit-to-left' : 'page-exit-to-right';
                var enterDirection = movingRight ? 'right' : 'left';
                sessionStorage.setItem(enterKey, enterDirection);

                mainContent.classList.remove('page-exit-to-left', 'page-exit-to-right', 'page-enter-from-right', 'page-enter-from-left');
                void mainContent.offsetWidth;
                mainContent.classList.add(exitClass);

                window.setTimeout(function () {
                    window.location.href = href;
                }, 230);
            });
        });

        document.addEventListener('click', function (event) {
            var link = event.target.closest('a[href]');
            if (!link) {
                return;
            }
            if (link.classList.contains('mobile-back-btn') || link.hasAttribute('download')) {
                return;
            }
            if (link.target && link.target.toLowerCase() === '_blank') {
                return;
            }
            var href = link.getAttribute('href');
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
                return;
            }
            if (link.origin !== window.location.origin) {
                return;
            }
            if (link.pathname === window.location.pathname && link.search === window.location.search && link.hash) {
                return;
            }

            if (!reduceMotion && isDesktopViewport && mainContent) {
                var desktopTargetIndex = desktopNavLinks.indexOf(link);
                var desktopCurrentIndex = desktopNavLinks.findIndex(function (navLink) { return navLink.classList.contains('active'); });

                if (desktopTargetIndex !== -1 && desktopCurrentIndex !== -1 && desktopTargetIndex !== desktopCurrentIndex) {
                    event.preventDefault();
                    startPageLoadLine();

                    var desktopMovingRight = desktopTargetIndex > desktopCurrentIndex;
                    var desktopExitClass = desktopMovingRight ? 'page-exit-to-left' : 'page-exit-to-right';
                    var desktopEnterDirection = desktopMovingRight ? 'right' : 'left';
                    sessionStorage.setItem(desktopEnterKey, desktopEnterDirection);

                    mainContent.classList.remove('page-exit-to-left', 'page-exit-to-right', 'page-enter-from-right', 'page-enter-from-left', 'page-enter-fade', 'page-exit-fade');
                    void mainContent.offsetWidth;
                    mainContent.classList.add(desktopExitClass);

                    window.setTimeout(function () {
                        window.location.href = href;
                    }, 220);
                    return;
                }

                if (desktopTargetIndex === -1) {
                    event.preventDefault();
                    startPageLoadLine();

                    mainContent.classList.remove('page-exit-to-left', 'page-exit-to-right', 'page-enter-from-right', 'page-enter-from-left', 'page-enter-fade', 'page-exit-fade');
                    void mainContent.offsetWidth;
                    mainContent.classList.add('page-exit-fade');

                    window.setTimeout(function () {
                        window.location.href = href;
                    }, 190);
                    return;
                }
            }

            startPageLoadLine();
        });

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            startPageLoadLine();
        });

        var notificationsEndpoint = @json(route('refresh.notifications.latest'));
        var csrfToken = @json(csrf_token());
        var notificationsInFlight = false;
        var readRouteTemplate = @json(route('notifications.read', ['notification' => '__ID__']));
        function escHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function buildNotificationItemHtml(item) {
            var readAction = readRouteTemplate.replace('__ID__', String(item.id));
            var unreadClass = item.is_read ? '' : ' unread';
            var readButton = item.is_read
                ? ''
                : '<form method=\"POST\" action=\"' + readAction + '\">\
                        <input type=\"hidden\" name=\"_token\" value=\"' + csrfToken + '\">\
                        <input type=\"hidden\" name=\"_method\" value=\"PATCH\">\
                        <button type=\"submit\" class=\"link-action\">Read</button>\
                   </form>';
            return '<div class=\"notification-item' + unreadClass + '\">\
                        <a href=\"' + escHtml(item.open_url || item.target_url || '#') + '\" class=\"notification-item-link\">\
                            <div class=\"notification-item-title\">' + escHtml(item.title || '') + '</div>\
                            <div class=\"notification-item-message\">' + escHtml(item.message || '') + '</div>\
                        </a>\
                        <div class=\"notification-item-row\">\
                            <span class=\"notification-item-time\">' + escHtml(item.time_ago || '') + '</span>\
                            ' + readButton + '\
                        </div>\
                    </div>';
        }

        function refreshNotificationDropdown(payload) {
            var unreadCount = Number(payload && payload.unread_count ? payload.unread_count : 0);
            var notificationItems = Array.isArray(payload && payload.notifications) ? payload.notifications : [];

            document.querySelectorAll('.notification-badge').forEach(function (badge) {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                }
            });

            var listHtml = notificationItems.length
                ? notificationItems.map(buildNotificationItemHtml).join('')
                : '<div class=\"notification-empty\">No notifications.</div>';

            document.querySelectorAll('[data-notification-items]').forEach(function (container) {
                container.innerHTML = listHtml;
            });
        }

        function shouldPollNotifications() {
            if (document.visibilityState !== 'visible') {
                return false;
            }
            var openDropdown = document.querySelector('.notification-wrap[open]');
            if (openDropdown) {
                return true;
            }
            return window.location.pathname === @json(route('notifications.index', [], false));
        }

        function pollNotifications() {
            if (notificationsInFlight || !shouldPollNotifications()) {
                return;
            }
            notificationsInFlight = true;
            fetch(notificationsEndpoint, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) return null;
                    return response.json();
                })
                .then(function (payload) {
                    if (payload) refreshNotificationDropdown(payload);
                })
                .catch(function () {})
                .finally(function () {
                    notificationsInFlight = false;
                });
        }

        window.setInterval(pollNotifications, 5000);
    })();
</script>
</body>
</html>
