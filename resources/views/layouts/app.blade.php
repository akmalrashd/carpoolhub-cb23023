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
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/icon.png') }}">

    <style>
        :root {
            /* Design tokens — CarpoolHub warm yellow system */
            --ch-yellow: #FACC15;
            --ch-yellow-deep: #E6B800;
            --ch-yellow-ink: #2A1E04;
            --ch-yellow-soft: #FFF4B8;
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
            --canvas-2: #FAF8F2;
            --success: #16A34A; --success-soft: #DCFCE7; --success-ink: #065F46;
            --warning: #B45309; --warning-soft: #FEF3C7; --warning-ink: #78350F;
            --danger: #DC2626; --danger-soft: #FEE2E2; --danger-ink: #7F1D1D;
            --info: #2563EB; --info-soft: #DBEAFE; --info-ink: #1E3A8A;
            --r-xs: 6px; --r-sm: 10px; --r-md: 14px; --r-lg: 18px; --r-xl: 22px; --r-pill: 999px;
            --shadow-1: 0 1px 2px rgba(11,18,32,0.04), 0 1px 0 rgba(11,18,32,0.02);
            --shadow-2: 0 6px 18px rgba(11,18,32,0.06), 0 2px 4px rgba(11,18,32,0.04);
            --shadow-3: 0 18px 40px rgba(11,18,32,0.10), 0 6px 12px rgba(11,18,32,0.06);
            --shadow-yellow: 0 8px 20px rgba(234,179,8,0.30);
            --font-display: "Poppins", "Inter", system-ui, sans-serif;
            --font-ui: "Inter", system-ui, sans-serif;
            --font-mono: "JetBrains Mono", ui-monospace, monospace;
            /* Backward-compat aliases for existing pages */
            --bg: var(--canvas);
            --bg-glow: rgba(42, 30, 4, 0.04);
            --secondary: var(--surface);
            --card: var(--surface);
            --border: var(--hairline);
            --text: var(--ink);
            --text-muted: var(--muted);
            --accent-soft: var(--surface-2);
            --success-bg: var(--success-soft);
            --success-border: rgba(22,163,74,0.25);
            --success-text: var(--success-ink);
        }

        * {
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: var(--font-ui);
            color: var(--ink);
            background: var(--canvas);
        }
        body.modal-open {
            overflow: hidden;
            touch-action: none;
        }
        .card,
        .panel,
        .container-box {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-2);
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
            padding: 0 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            background: var(--surface);
            border-bottom: 1px solid var(--hairline);
            box-shadow: none;
        }
        .navbar,
        .desktop-topbar {
            box-shadow: var(--shadow-1);
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
            background: linear-gradient(90deg, var(--ch-yellow) 0%, #f59e0b 100%);
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
            gap: 8px;
            min-width: 0;
            position: relative;
            z-index: 2;
            flex: 1 1 auto;
        }

        .mobile-back-btn {
            width: 40px;
            height: 40px;
            border-radius: 9px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            display: grid;
            place-items: center;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 14px;
        }

        .mobile-back-btn:hover {
            background: var(--surface-2);
        }

        .menu-toggle-btn {
            width: 40px;
            height: 40px;
            border-radius: 9px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
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
            background: var(--ink);
            border-radius: 2px;
            transition: transform 0.2s ease, opacity 0.2s ease;
            transform-origin: center;
        }

        .mobile-brand-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
            display: block;
            flex-shrink: 0;
        }
        .mobile-brand-mark {
            width: 25px;
            height: 25px;
            border-radius: 7px;
            display: inline-grid;
            place-items: center;
            background: var(--ink);
            color: var(--muted);
            box-shadow: inset 0 0 0 2px var(--ch-yellow);
            font-size: 12px;
            flex-shrink: 0;
        }
        .mobile-brand-word {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
            line-height: 1;
            color: var(--ink);
            white-space: nowrap;
            letter-spacing: 0;
        }
        .mobile-brand-word span { color: var(--ch-yellow-deep); }
        .mobile-brand-text {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 800;
            line-height: 1;
            color: var(--ink);
            white-space: nowrap;
        }
        .mobile-brand-text span { color: var(--ch-yellow); }
        .mobile-home-title {
            display: grid;
            gap: 1px;
            min-width: 118px;
            padding-left: 14px;
            border-left: 1px solid var(--hairline);
            line-height: 1.05;
        }
        .mobile-home-title strong {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
            color: var(--ink);
        }
        .mobile-home-title span {
            font-size: 13px;
            font-weight: 700;
            color: #7c8ba1;
        }
        .header-logo-link {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .mobile-header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 2;
            flex-shrink: 0;
        }

        .mobile-header.has-back-btn .mobile-brand-logo {
            position: static;
            transform: none;
            max-width: none;
        }

        @media (max-width: 430px) {
            .mobile-header { height: 68px; padding: 0 8px; gap: 6px; }
            .page-load-line { top: 68px; }
            .mobile-header-left { gap: 6px; }
            .mobile-back-btn { width: 38px; height: 38px; }
            .header-logo-link { gap: 6px; }
            .mobile-brand-logo { width: 28px; height: 28px; }
            .mobile-brand-word,
            .mobile-brand-text { font-size: 18px; }
            .mobile-brand-mark { width: 23px; height: 23px; font-size: 11px; }
            .mobile-home-title { min-width: 92px; padding-left: 10px; }
            .mobile-home-title strong { font-size: 19px; }
            .mobile-home-title span { font-size: 12px; }
            .mobile-header-right { gap: 6px; }
            .notification-toggle,
            .profile-toggle { width: 44px; height: 44px; }
            .avatar-initial { width: 36px; height: 36px; font-size: 13px; }
        }

        .mobile-header.has-back-btn .mobile-header-left {
            position: static;
        }

        .notification-wrap { position: relative; }

        .notification-toggle {
            width: 52px;
            height: 52px;
            border-radius: 13px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            display: grid;
            place-items: center;
            cursor: pointer;
            list-style: none;
            position: relative;
            font-size: 16px;
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
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 10px;
            z-index: 2100;
            box-shadow: var(--shadow-3);
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
            background: var(--surface);
            border-bottom: 1px solid var(--hairline);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-item { border: 1px solid var(--hairline); border-radius: 10px; padding: 8px; margin-bottom: 8px; }
        .notification-item.unread { background: var(--surface-2); }
        .notification-item-link { text-decoration: none; color: inherit; display: block; border-radius: 8px; }
        .notification-item-link:hover .notification-item-title { color: var(--info); }
        .notification-item-title { font-weight: 600; margin-bottom: 3px; line-height: 1.25; }
        .notification-item-message { color: var(--muted); font-size: 13px; margin-bottom: 6px; line-height: 1.35; }
        .notification-item-row { display: flex; justify-content: space-between; align-items: center; }
        .notification-item-time { color: var(--muted); font-size: 12px; }
        .notification-empty { color: var(--muted); font-size: 13px; padding: 4px 0; }
        .notification-footer {
            position: sticky;
            bottom: -10px;
            margin: 6px -10px -10px;
            padding: 8px 10px;
            background: var(--surface);
            border-top: 1px solid var(--hairline);
            display: flex;
            justify-content: center;
        }
        .notification-view-all {
            color: var(--ink);
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            padding: 2px 4px;
            line-height: 1;
        }
        .notification-view-all:hover {
            color: var(--info);
        }
        .link-action { border: none; background: transparent; color: var(--ink); font-size: 12px; cursor: pointer; font-weight: 600; }
        .profile-wrap { position: relative; }

        .profile-toggle {
            width: 52px;
            height: 52px;
            border-radius: 999px;
            border: 1.5px solid var(--ch-yellow-line);
            background: var(--surface);
            color: var(--ink);
            cursor: pointer;
            display: grid;
            place-items: center;
            list-style: none;
        }

        .avatar-initial {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            background: var(--ink);
            color: #fff;
            font-size: 14px;
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
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 8px;
            z-index: 2100;
            box-shadow: var(--shadow-3);
            display: grid;
            gap: 4px;
        }

        .profile-menu-link,
        .profile-menu-btn {
            width: 100%;
            text-decoration: none;
            color: var(--ink);
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
            background: var(--surface-2);
        }

        .mobile-drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 18, 32, 0.55);
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
            background: var(--surface);
            transform: translateX(-100%);
            transition: transform 0.2s ease;
            z-index: 2201;
            display: flex;
            flex-direction: column;
            color: var(--ink);
            box-shadow: 8px 0 26px rgba(11, 18, 32, 0.28);
        }

        body.mobile-drawer-open { overflow: hidden; }
        body.mobile-drawer-open .mobile-drawer { transform: translateX(0); }
        body.mobile-drawer-open .mobile-drawer-overlay { opacity: 1; pointer-events: auto; }

        .mobile-drawer-head {
            min-height: 112px;
            padding: 12px;
            background: var(--surface);
            border-bottom: 1px solid var(--hairline);
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
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
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
            font-family: var(--font-display);
            font-size: 18px;
            color: var(--ink);
            font-weight: 700;
        }

        .mobile-drawer-search {
            margin-top: 12px;
            width: 100%;
            border-radius: var(--r-sm);
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            font-size: 14px;
            padding: 9px 10px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .mobile-drawer-search:focus {
            border-color: var(--ch-yellow-deep);
            box-shadow: 0 0 0 4px rgba(250,204,21,0.22);
        }

        .mobile-drawer-nav {
            padding: 10px 0;
            overflow-y: auto;
            display: grid;
            gap: 2px;
        }

        .mobile-drawer-nav a {
            text-decoration: none;
            color: var(--ink-3);
            padding: 11px 14px;
            font-size: 15px;
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

        .mobile-drawer-nav a.active {
            color: var(--ink);
            font-weight: 700;
            background: var(--ch-yellow-tint);
        }

        .mobile-drawer-nav a:hover {
            background: var(--surface-2);
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
            background: var(--success-soft);
            border: 1px solid var(--success-border);
            color: var(--success-ink);
            padding: 10px 12px;
            border-radius: var(--r-md);
            margin-bottom: 12px;
            transition: opacity .35s ease, transform .35s ease;
        }

        .status-banner.hide {
            opacity: 0;
            transform: translateY(-4px);
            pointer-events: none;
        }

        .app-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 14px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid var(--hairline);
            padding: 3px 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-success { color: var(--success-ink); border-color: rgba(22,163,74,0.3); background: var(--success-soft); }
        .status-danger { color: var(--danger-ink); border-color: rgba(220,38,38,0.28); background: var(--danger-soft); }
        .status-warning { color: var(--warning-ink); border-color: rgba(180,83,9,0.28); background: var(--warning-soft); }
        .status-info { color: var(--info-ink); border-color: rgba(37,99,235,0.28); background: var(--info-soft); }

        .mobile-bottom-nav {
            position: fixed;
            left: 6px;
            right: 6px;
            bottom: 0;
            z-index: 1900;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--hairline);
            border-bottom: 0;
            border-radius: 20px 20px 0 0;
            backdrop-filter: blur(14px);
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            padding: 8px 8px max(10px, env(safe-area-inset-bottom));
            gap: 6px;
            box-shadow: 0 -10px 30px rgba(11,18,32,0.12);
        }

        .mobile-bottom-nav > a,
        .mobile-bottom-nav > details > summary {
            text-decoration: none;
            color: var(--muted);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-height: 58px;
            border-radius: 14px;
            border: 1px solid transparent;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            list-style: none;
            position: relative;
            overflow: hidden;
            transition: transform 0.16s ease, background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease;
        }

        .mobile-bottom-nav .icon {
            font-size: 17px;
            line-height: 1;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .mobile-bottom-nav .icon i { transition: transform 0.16s ease; }

        .mobile-bottom-nav > a.nav-fab {
            margin-top: 0;
            min-height: 58px;
            color: var(--muted);
            font-weight: 800;
            overflow: visible;
            border-radius: 12px;
            border-color: var(--hairline-strong);
            background: var(--surface);
            box-shadow: none;
            gap: 2px;
        }
        .mobile-bottom-nav > a.nav-fab .icon {
            width: auto;
            height: 18px;
            border-radius: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
            color: inherit;
            font-size: 18px;
        }
        .mobile-bottom-nav > a.nav-fab:hover {
            background: var(--ch-yellow-tint);
            border-color: var(--ch-yellow-line);
            color: var(--ch-yellow-ink);
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(234,179,8,0.14);
        }
        .mobile-bottom-nav > a.nav-fab:hover .icon {
            background: transparent;
        }

        .mobile-bottom-nav > a.active,
        .mobile-bottom-nav > details > summary.active {
            color: var(--ch-yellow-ink);
            border-color: var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            font-weight: 700;
        }
        .mobile-bottom-nav > a.nav-fab.active {
            background: var(--ch-yellow);
            border-color: var(--ch-yellow);
            color: var(--ch-yellow-ink);
            box-shadow: 0 10px 20px rgba(234,179,8,0.24);
        }
        .mobile-bottom-nav > a.nav-fab.active .icon {
            transform: translateY(-1px);
        }
        .mobile-bottom-nav > a:hover,
        .mobile-bottom-nav > details > summary:hover {
            color: var(--ink);
            border-color: var(--hairline-strong);
            background: var(--surface-2);
        }
        .mobile-bottom-nav > a.nav-fab:not(.active):not(:hover) {
            color: var(--muted);
            border-color: var(--hairline-strong);
            background: var(--surface);
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
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 8px;
            display: grid;
            gap: 4px;
            box-shadow: var(--shadow-3);
        }

        .mobile-bottom-nav .more-sheet a {
            text-decoration: none;
            color: var(--ink);
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .mobile-bottom-nav .more-sheet a:hover { background: var(--surface-2); }

        @media (min-width: 1024px) {
            body.mobile-drawer-open {
                overflow: auto !important;
            }
            body.mobile-drawer-open .mobile-drawer-overlay,
            body.mobile-drawer-open .mobile-drawer {
                opacity: 0 !important;
                pointer-events: none !important;
                transform: none !important;
            }
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
                background: var(--surface);
                border-bottom: 1px solid var(--hairline);
                box-shadow: var(--shadow-1);
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
                width: 176px;
                overflow: hidden;
            }

            .desktop-brand-logo {
                width: auto;
                max-width: 54px;
                height: 46px;
                object-fit: contain;
                display: block;
            }
            .desktop-brand-wrap::after {
                content: "CarpoolHub";
                font-family: var(--font-display);
                font-size: 18px;
                font-weight: 800;
                color: var(--ink);
                white-space: nowrap;
            }

            .desktop-topbar-right {
                display: flex;
                align-items: center;
                gap: 10px;
                color: var(--ink);
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
                background: var(--surface);
                overflow-y: auto;
                overflow-x: hidden;
                padding: 10px 8px;
                box-shadow: inset -1px 0 0 var(--hairline);
            }

            /* Sidebar group labels */
            .desktop-nav-group { margin-bottom: 4px; }
            .desktop-nav-group-label {
                display: none;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.10em;
                text-transform: uppercase;
                color: var(--muted-2);
                padding: 8px 10px 2px;
                white-space: nowrap;
            }
            body.sidebar-expanded .desktop-nav-group-label { display: block; }

            .desktop-nav {
                display: grid;
                gap: 2px;
            }

            .desktop-nav a {
                text-decoration: none;
                color: var(--muted);
                min-height: 44px;
                border-radius: var(--r-sm);
                padding: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0;
                font-size: 16px;
                white-space: nowrap;
                position: relative;
                overflow: hidden;
                transition: background .15s ease, color .15s ease;
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

            .desktop-nav a:hover {
                background: var(--surface-2);
                color: var(--ink);
            }

            /* Active: yellow left-rail bar */
            .desktop-nav a.active {
                color: var(--ink);
                background: var(--ch-yellow-tint);
                font-weight: 700;
            }
            .desktop-nav a.active::before {
                content: '';
                position: absolute;
                left: 0;
                top: 4px;
                bottom: 4px;
                width: 3px;
                background: var(--ch-yellow);
                border-radius: 0 2px 2px 0;
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

        /* Global component classes */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            height: 40px; padding: 0 16px; border-radius: var(--r-sm);
            font-family: var(--font-display); font-weight: 700; font-size: 14px;
            letter-spacing: -0.005em; border: 1px solid transparent;
            cursor: pointer; text-decoration: none; white-space: nowrap;
            transition: transform .08s ease, background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: var(--ch-yellow); color: var(--ch-yellow-ink); box-shadow: 0 1px 0 rgba(11,18,32,0.05), inset 0 -1px 0 rgba(11,18,32,0.06); }
        .btn-primary:hover { background: var(--ch-yellow-deep); }
        .btn-dark { background: var(--ink); color: #fff; }
        .btn-dark:hover { background: var(--ink-2); }
        .btn-ghost { background: transparent; color: var(--ink); border-color: var(--hairline-strong); }
        .btn-ghost:hover { background: var(--surface-2); }
        .btn-soft { background: var(--ch-yellow-tint); color: var(--ch-yellow-ink); border-color: var(--ch-yellow-line); }
        .btn-soft:hover { background: var(--ch-yellow-soft); }
        .btn-danger { background: var(--danger-soft); color: var(--danger-ink); border-color: rgba(220,38,38,0.25); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-sm { height: 32px; padding: 0 12px; font-size: 13px; }
        .btn-lg { height: 48px; padding: 0 20px; font-size: 15px; border-radius: var(--r-md); }
        .btn-block { width: 100%; }

        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            height: 24px; padding: 0 9px; border-radius: var(--r-pill);
            font-size: 11.5px; font-weight: 700; line-height: 1; letter-spacing: 0.01em;
            font-family: var(--font-display);
            border: 1px solid var(--hairline-strong); color: var(--ink-2); background: var(--surface);
        }
        .badge .dot { width: 6px; height: 6px; border-radius: 99px; background: currentColor; flex-shrink: 0; }
        .badge-success { background: var(--success-soft); color: var(--success-ink); border-color: rgba(22,163,74,0.25); }
        .badge-warning { background: var(--warning-soft); color: var(--warning-ink); border-color: rgba(180,83,9,0.28); }
        .badge-danger { background: var(--danger-soft); color: var(--danger-ink); border-color: rgba(220,38,38,0.25); }
        .badge-info { background: var(--info-soft); color: var(--info-ink); border-color: rgba(37,99,235,0.22); }
        .badge-yellow { background: var(--ch-yellow-tint); color: var(--ch-yellow-ink); border-color: var(--ch-yellow-line); }
        .badge-dark { background: var(--ink); color: #fff; border-color: var(--ink); }
        .badge-lg { height: 28px; padding: 0 12px; font-size: 12.5px; }
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
                    <img src="{{ asset('assets/branding/logo-horizontal-b.png') }}" alt="CarpoolHub" class="desktop-brand-logo">
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
                <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="CarpoolHub" class="mobile-drawer-logo">
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

        function forceCloseMobileDrawerOnDesktop() {
            if (!desktopQuery.matches) {
                return;
            }

            document.body.classList.remove('mobile-drawer-open');

            var drawer = document.getElementById('mobileDrawer');
            var overlay = document.getElementById('mobileDrawerOverlay');

            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
                overlay.style.display = 'none';
            }

            if (drawer) {
                drawer.style.pointerEvents = 'none';
                drawer.style.display = 'none';
            }
        }

        function syncDesktop() {
            var expanded = localStorage.getItem(desktopKey) === '1';
            document.body.classList.toggle('sidebar-expanded', expanded && desktopQuery.matches);
            forceCloseMobileDrawerOnDesktop();
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
        forceCloseMobileDrawerOnDesktop();
        window.addEventListener('resize', forceCloseMobileDrawerOnDesktop);
        window.addEventListener('pageshow', forceCloseMobileDrawerOnDesktop);

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
            if (mobileOverlay) {
                mobileOverlay.style.display = '';
                mobileOverlay.style.opacity = '';
                mobileOverlay.style.pointerEvents = '';
            }
            var drawer = document.getElementById('mobileDrawer');
            if (drawer) {
                drawer.style.display = '';
                drawer.style.pointerEvents = '';
            }
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
