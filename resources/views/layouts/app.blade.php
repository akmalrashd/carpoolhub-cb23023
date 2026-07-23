<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    {{-- user-scalable=no stops pinch zoom of the page itself; the Leaflet maps
         still zoom, they drive it from JS. viewport-fit=cover lets the layout
         reach under a notch, which the safe-area rules then pad back. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
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
            /* Mobile header height. Several rules must track this exactly —
               the load line sits directly under it and the shell subtracts it
               from the viewport — so it lives here rather than being repeated. */
            --mobile-header-h: 64px;
            /* Size of the round-square controls in the mobile header (bento,
               bell, profile) and the avatar inside the profile pill. Mobile
               only — the desktop topbar keeps its own smaller sizes. */
            --header-btn-h: 42px;
            --header-avatar-h: 34px;

            /* ─── LAYOUT CONTROL PANEL — laras saiz rangka di sini ───────────
               Angka-angka ini dulu diulang di banyak tempat dalam fail ini.
               Ubah satu baris di sini, seluruh rangka ikut. (Jarak tepi
               halaman ada dalam --page-gutter di pwa-head.) */
            --desktop-topbar-h: 52px;   /* tinggi bar atas desktop */
            --bottom-nav-h: 83px;       /* tinggi nav bawah telefon */
            --sidebar-w: 70px;          /* lebar sidebar (tutup) */
            --sidebar-w-expanded: 242px;/* lebar sidebar (buka) */

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
            height: var(--mobile-header-h);
            padding: 0 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            background: var(--surface);
            border-bottom: 1px solid var(--hairline);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .navbar,
        .desktop-topbar {
            box-shadow: var(--shadow-1);
        }
        .page-load-line {
            display: none !important;
            position: fixed;
            left: 0;
            right: 0;
            top: var(--mobile-header-h);
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

        /* ── Global skeleton shimmer ─────────────────────────── */
        @keyframes ch-shimmer {
            0%   { background-position: -800px 0; }
            100% { background-position:  800px 0; }
        }
        .sk {
            background: linear-gradient(90deg,
                var(--hairline) 0%,
                var(--canvas-2, #FAF8F2) 40%,
                var(--hairline) 80%
            );
            background-size: 1200px 100%;
            animation: ch-shimmer 1.6s ease-in-out infinite;
            border-radius: var(--r-sm);
            display: block;
        }
        .sk-notif-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 4px;
            border-bottom: 1px solid var(--hairline);
        }
        .sk-notif-row:last-child { border-bottom: none; }

        .mobile-header-left {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
            position: relative;
            z-index: 2;
            flex: 1 1 auto;
        }

        .mobile-back-btn {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            display: grid;
            place-items: center;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 13px;
        }

        .mobile-back-btn:hover {
            background: var(--surface-2);
        }

        .menu-toggle-btn {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            display: grid;
            place-items: center;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 14px;
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
            width: 34px;
            height: 34px;
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
            font-size: 17px;
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
            gap: 6px;
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
            /* Slightly tighter on small phones. Override the tokens, not the
               rules — the rules that consume these live in the max-width:1023px
               block below and would win on source order. */
            :root {
                --mobile-header-h: 58px;
                --header-btn-h: 37px;
                --header-avatar-h: 30px;
            }
            .mobile-header { padding: 0 8px; gap: 4px; }
            .mobile-header-left { gap: 4px; }
            .mobile-back-btn { width: 34px; height: 34px; font-size: 13px; }
            .header-logo-link { gap: 6px; }
            .mobile-brand-logo { width: 28px; height: 28px; }
            .mobile-brand-word,
            .mobile-brand-text { font-size: 17px; }
            .mobile-brand-mark { width: 20px; height: 20px; font-size: 10px; }
            .mobile-home-title { min-width: 80px; padding-left: 8px; }
            .mobile-home-title strong { font-size: 16px; }
            .mobile-home-title span { font-size: 11px; }
            .mobile-header-right { gap: 4px; }
            .notification-toggle,
            .bento-menu-toggle { font-size: 14px; }
            .role-badge { height: 22px; font-size: 10px; padding: 0 7px; gap: 3px; }
        }

        .mobile-header.has-back-btn .mobile-header-left {
            position: static;
        }

        .notification-wrap { position: relative; }

        .notification-toggle {
            width: 36px;
            height: 36px;
            border-radius: 11px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            display: grid;
            place-items: center;
            cursor: pointer;
            list-style: none;
            position: relative;
            font-size: 14px;
        }
        .notification-toggle.has-unread {
            background: var(--surface);
            border-color: var(--hairline-strong);
            color: var(--ink);
        }
        .notification-wrap[open] .notification-toggle {
            background: var(--ch-yellow-tint);
            border-color: var(--ch-yellow-line);
            color: var(--ch-yellow-deep);
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--danger);
            color: #fff;
            border-radius: 999px;
            min-width: 16px;
            height: 16px;
            font-size: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 3px;
            font-weight: 700;
            animation: notifPulse 2s ease-in-out infinite;
        }
        @keyframes notifPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }

        .notification-dropdown {
            position: absolute;
            right: 0;
            margin-top: 8px;
            width: min(90vw, 320px);
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 0;
            z-index: 2100;
            box-shadow: var(--shadow-3);
            max-height: min(65vh, 480px);
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }
        .notification-dropdown::-webkit-scrollbar {
            width: 4px;
        }
        .notification-dropdown::-webkit-scrollbar-track {
            background: transparent;
        }
        .notification-dropdown::-webkit-scrollbar-thumb {
            background: var(--hairline-strong);
            border-radius: 999px;
        }

        .notification-dropdown-head {
            position: sticky;
            top: 0;
            z-index: 2;
            padding: 10px 14px;
            background: var(--surface);
            border-bottom: 1px solid var(--hairline);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
         .notification-dropdown-head strong {
             font-size: 16px;
             font-weight: 800;
             color: var(--ink);
             font-family: var(--font-display), sans-serif;
         }
        .notification-items {
            display: flex;
            flex-direction: column;
        }
        .notification-item {
            display: flex;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--hairline);
            transition: background 0.15s ease;
            text-align: left;
        }
        .notification-item:hover {
            background: var(--surface-2);
        }
        .notification-item.unread {
            background: rgba(244, 239, 226, 0.25);
        }
        .notification-item-icon-col {
            flex-shrink: 0;
        }
        .notification-icon-badge {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .notification-item-content-col {
            flex: 1;
            min-width: 0;
        }
        .notification-item-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .notification-item-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 3px;
        }
        .notification-item-title {
            font-weight: 700;
            font-size: 12px;
            line-height: 1.2;
            color: var(--ink);
        }
        .unread-dot-indicator {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--danger);
            flex-shrink: 0;
        }
        .notification-item-message {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.35;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .notification-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-item-time {
            color: var(--muted);
            font-size: 10px;
        }
        .notification-empty {
            color: var(--muted);
            font-size: 12px;
            padding: 24px 14px;
            text-align: center;
        }
        .notification-footer {
            position: sticky;
            bottom: 0;
            z-index: 2;
            padding: 8px 14px;
            background: var(--surface);
            border-top: 1px solid var(--hairline);
            text-align: center;
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
            height: 36px;
            border-radius: 11px;
            border: 1.5px solid var(--ch-yellow-line);
            background: var(--surface);
            color: var(--ink);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            padding: 0 8px 0 3px;
            gap: 6px;
            list-style: none;
            box-sizing: border-box;
        }
        .profile-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.15;
        }
        .profile-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--ink);
            white-space: nowrap;
        }
        .profile-role {
            font-size: 10px;
            font-weight: 600;
            color: var(--muted);
            text-transform: capitalize;
            white-space: nowrap;
        }
        .profile-chevron {
            font-size: 10px;
            color: var(--muted);
            transition: transform 0.2s ease;
        }
        .profile-wrap[open] .profile-chevron {
            transform: rotate(180deg);
        }
        @media (max-width: 1023px) {
            .profile-details {
                display: none !important;
            }
            /* All three header controls must resolve to the same height here.
               This block previously pinned .profile-toggle to 36px !important
               while the bell used the base 36px, so raising one without the
               other left them visibly mismatched. They now share a token. */
            .profile-toggle {
                height: var(--header-btn-h) !important;
                border-radius: 13px !important;
                padding: 0 8px 0 4px !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 5px !important;
            }
            .notification-toggle,
            .bento-menu-toggle {
                width: var(--header-btn-h);
                height: var(--header-btn-h);
                border-radius: 13px;
            }
            .avatar-initial {
                width: var(--header-avatar-h);
                height: var(--header-avatar-h);
                border-radius: 11px;
                font-size: 13px;
            }
        }

        .avatar-initial {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: var(--ink);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        /* ── Role badge ── */
        .role-badge {
            height: 26px;
            border-radius: 999px;
            padding: 0 9px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            line-height: 1;
            letter-spacing: 0.01em;
        }
        .role-badge i { font-size: 10px; }
        .role-badge-driver {
            background: var(--ink);
            color: #fff;
        }
        .role-badge-passenger {
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
        }
        .role-badge-admin {
            background: var(--danger);
            color: #fff;
        }

        .profile-dropdown {
            position: absolute;
            right: 0;
            margin-top: 8px;
            width: 190px;
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 8px;
            z-index: 2100;
            box-shadow: var(--shadow-3);
            display: grid;
            gap: 4px;
        }
        .profile-dropdown-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 8px 8px;
        }
        .profile-dropdown-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.25;
        }
        .profile-dropdown-name {
            font-size: 13px;
            font-weight: 800;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 130px;
        }
        .profile-dropdown-role {
            font-size: 10px;
            font-weight: 600;
            color: var(--muted);
            text-transform: capitalize;
        }
        .profile-dropdown-divider {
            height: 1px;
            background: var(--hairline);
            margin: 4px 4px 8px;
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

        .profile-menu-btn.profile-menu-logout {
            color: var(--danger);
        }
        .profile-menu-btn.profile-menu-logout:hover {
            background: var(--danger-soft);
            color: var(--danger-ink);
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
            min-height: calc(100vh - var(--mobile-header-h));
        }

        .main-content {
            padding: 14px 14px calc(140px + env(safe-area-inset-bottom, 16px));
        }
        .main-content[class*="page-"] { will-change: transform, opacity; }
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

        /* ── Modern Toast Notification System ── */
        .toast-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: min(90vw, 340px);
            pointer-events: none;
        }
        .toast-card {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15), 0 8px 10px -6px rgba(0,0,0,0.15);
            pointer-events: auto;
            border: 1px solid rgba(255,255,255,0.08);
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
            transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1), 
                        transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            text-align: left;
        }
        .toast-card.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .toast-card.hide {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
        }
        .toast-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            font-size: 11px;
            flex-shrink: 0;
        }
        .toast-icon-success {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }
        .toast-icon-error {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        .toast-icon-info {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }
        .toast-message {
            flex: 1;
            line-height: 1.4;
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
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1900;
            background: rgba(255, 255, 255, 0.94);
            border-top: 1px solid var(--hairline);
            border-radius: 0;
            backdrop-filter: blur(14px);
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            height: calc(var(--bottom-nav-h) + env(safe-area-inset-bottom, 0px));
            padding-bottom: env(safe-area-inset-bottom, 0px);
            box-sizing: border-box;
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
            gap: 2px;
            height: var(--bottom-nav-h);
            min-height: var(--bottom-nav-h);
            padding: 1.6px 0 17.6px;
            font-size: 10px;
            font-weight: 700;
            cursor: pointer;
            list-style: none;
            position: relative;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            box-sizing: border-box;
            transition: transform 0.16s ease, color 0.16s ease;
        }

        .mobile-bottom-nav .icon {
            font-size: 24px;
            line-height: 1;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .mobile-bottom-nav .icon i { transition: transform 0.16s ease; }

        .mobile-bottom-nav > a.active,
        .mobile-bottom-nav > details > summary.active {
            color: var(--ink) !important;
            font-weight: 800;
        }
        .mobile-bottom-nav > a:hover,
        .mobile-bottom-nav > details > summary:hover {
            color: var(--ch-yellow-deep) !important;
        }
        .mobile-bottom-nav > a.active .icon i,
        .mobile-bottom-nav > details > summary.active .icon i {
            transform: scale(1.08);
        }
        .mobile-bottom-nav > a:active,
        .mobile-bottom-nav > details > summary:active {
            transform: scale(0.95);
        }
        .mobile-bottom-nav > a.tap-animate,
        .mobile-bottom-nav > details > summary.tap-animate {
            animation: navTapPop 0.22s ease;
        }
        @keyframes navTapPop {
            0% { transform: scale(1); }
            50% { transform: scale(0.92); }
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
                height: var(--desktop-topbar-h);
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
                top: var(--desktop-topbar-h);
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
                max-width: 44px;
                height: 36px;
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
                padding-top: var(--desktop-topbar-h);
                min-height: 100vh;
                display: grid;
                grid-template-columns: var(--sidebar-w) 1fr;
                transition: grid-template-columns 0.2s ease;
            }

            body.sidebar-expanded .app-shell {
                grid-template-columns: var(--sidebar-w-expanded) 1fr;
            }

            .desktop-sidebar {
                display: block;
                position: sticky;
                top: var(--desktop-topbar-h);
                height: calc(100vh - var(--desktop-topbar-h));
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
        /* ── Bento Menu Dropdown ── */
        .bento-menu-wrap {
            position: relative;
            display: inline-block;
        }
        .bento-menu-toggle {
            width: 36px;
            height: 36px;
            border-radius: 11px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            display: grid;
            place-items: center;
            cursor: pointer;
            list-style: none;
            position: relative;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }
        .bento-menu-toggle::-webkit-details-marker {
            display: none !important;
        }
        .bento-menu-toggle::marker {
            display: none !important;
        }
        .bento-menu-toggle:hover {
            background: var(--surface-2);
        }
        .bento-menu-wrap[open] .bento-menu-toggle {
            background: var(--ch-yellow-tint);
            border-color: var(--ch-yellow-line);
            color: var(--ch-yellow-deep);
        }
        
        .bento-menu-dropdown {
            position: absolute;
            right: 0;
            margin-top: 8px;
            width: min(90vw, 560px);
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 16px;
            z-index: 2100;
            box-shadow: var(--shadow-3);
            max-height: min(85vh, 520px);
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            text-align: left;
        }
        .bento-menu-dropdown::-webkit-scrollbar {
            width: 4px;
        }
        .bento-menu-dropdown::-webkit-scrollbar-track {
            background: transparent;
        }
        .bento-menu-dropdown::-webkit-scrollbar-thumb {
            background: var(--hairline-strong);
            border-radius: 999px;
        }
        
        .bento-menu-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--ink);
            margin: 0 0 12px;
            font-family: var(--font-display), sans-serif;
        }
        
        .bento-menu-container {
            display: flex;
            gap: 16px;
        }
        
        /* Left Column */
        .bento-menu-main {
            flex: 1.3;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        /* Search Box */
        .bento-menu-search-wrap {
            position: relative;
            margin-bottom: 2px;
        }
        .bento-menu-search-wrap i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 12px;
        }
        .bento-menu-search-input {
            width: 100%;
            height: 34px;
            padding: 0 10px 0 30px;
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            border-radius: var(--r-sm);
            color: var(--ink);
            font-size: 12.5px;
            outline: none;
            transition: all 0.2s ease;
        }
        .bento-menu-search-input:focus {
            border-color: var(--ch-yellow-deep);
            background: var(--surface);
            box-shadow: 0 0 0 3px var(--ch-yellow-tint);
        }
        
        /* Categories and items */
        .bento-menu-sections-wrapper {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .bento-section-title {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            margin: 0 0 6px 2px;
        }
        .bento-grid {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .bento-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 6px;
            border-radius: var(--r-sm);
            transition: background 0.15s ease;
            text-decoration: none;
            color: inherit;
        }
        .bento-item:hover {
            background: var(--surface-2);
        }
        .bento-icon-bg {
            width: 32px;
            height: 32px;
            border-radius: var(--r-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }
        .bento-info {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .bento-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--ink);
        }
        .bento-desc {
            font-size: 10.5px;
            color: var(--muted);
            line-height: 1.3;
        }
        
        /* Right Column (Create) */
        .bento-menu-side {
            flex: 1;
            min-width: 0;
            background: var(--surface-2);
            border-radius: var(--r-sm);
            padding: 12px;
            border: 1px solid var(--hairline-strong);
            display: flex;
            flex-direction: column;
            gap: 10px;
            height: fit-content;
        }
        .bento-side-title {
            font-size: 13.5px;
            font-weight: 800;
            color: var(--ink);
            margin: 0 0 2px;
        }
        .bento-side-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .bento-side-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 5px;
            border-radius: var(--r-sm);
            transition: background 0.15s ease;
            text-decoration: none;
            color: inherit;
        }
        .bento-side-item:hover {
            background: var(--surface);
            box-shadow: var(--shadow-1);
        }
        .bento-side-icon-circle {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--hairline-strong);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            flex-shrink: 0;
        }
        .bento-side-info {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .bento-side-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--ink);
        }
        .bento-side-desc {
            font-size: 10px;
            color: var(--muted);
            line-height: 1.3;
        }
        
        @media (max-width: 767px) {
            .bento-menu-dropdown {
                width: min(95vw, 340px);
                max-height: min(80vh, 460px);
                padding: 12px;
            }
            .bento-menu-container {
                flex-direction: column;
                gap: 14px;
            }
            .bento-menu-side {
                padding: 10px;
            }
        }
    </style>
    @include('layouts.partials.pwa-head')
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
            <details class="bento-menu-wrap">
                <summary class="bento-menu-toggle" aria-label="Toggle Menu">
                    <i class="fa-solid fa-grip" aria-hidden="true"></i>
                </summary>
                <div class="bento-menu-dropdown">
                    <h2 class="bento-menu-title">Menu</h2>
                    <div class="bento-menu-container">
                        <!-- Left Panel (Social/Lists) -->
                        <div class="bento-menu-main">
                            <div class="bento-menu-search-wrap">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" placeholder="Search menu..." class="bento-menu-search-input" data-bento-search>
                            </div>
                            <div class="bento-menu-sections-wrapper">
                                <!-- Section: Navigation -->
                                <div class="bento-menu-section" data-bento-section>
                                    <h3 class="bento-section-title">Navigation</h3>
                                    <div class="bento-grid">
                                        <a href="{{ route('home') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(37,99,235,0.1); color: #2563eb;">
                                                <i class="fa-solid fa-house"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Dashboard</strong>
                                                <span class="bento-desc">Go to home view, see trip statistics and summaries.</span>
                                            </div>
                                        </a>
                                        <a href="{{ route('explore.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(147,51,234,0.1); color: #9333ea;">
                                                <i class="fa-solid fa-compass"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Explore</strong>
                                                <span class="bento-desc">Browse, search and filter active carpool trips.</span>
                                            </div>
                                        </a>
                                        <a href="{{ route('trips.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(22,163,74,0.1); color: #16a34a;">
                                                <i class="fa-solid fa-car-side"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">My Trips</strong>
                                                <span class="bento-desc">View and manage your upcoming, past, and draft journeys.</span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                <!-- Section: Workspace -->
                                <div class="bento-menu-section" data-bento-section>
                                    <h3 class="bento-section-title">Workspace</h3>
                                    <div class="bento-grid">
                                        <a href="{{ route('saved-routes.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(220,38,38,0.1); color: #dc2626;">
                                                <i class="fa-solid fa-route"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Saved Routes</strong>
                                                <span class="bento-desc">Quickly define recurrent starting and destination points.</span>
                                            </div>
                                        </a>
                                        <a href="{{ route('connections.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(2,132,199,0.1); color: #0284c7;">
                                                <i class="fa-solid fa-users"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Connections</strong>
                                                <span class="bento-desc">Network with drivers and riders in your circle.</span>
                                            </div>
                                        </a>
                                        <a href="{{ route('payments.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(234,179,8,0.15); color: #ca8a04;">
                                                <i class="fa-solid fa-wallet"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Payments Ledger</strong>
                                                <span class="bento-desc">Track and review trip fees and driver collection receipts.</span>
                                            </div>
                                        </a>
                                        <a href="{{ route('settings.index') }}" class="bento-item" data-bento-item>
                                            <span class="bento-icon-bg" style="background: rgba(100,116,139,0.1); color: #64748b;">
                                                <i class="fa-solid fa-gears"></i>
                                            </span>
                                            <div class="bento-info">
                                                <strong class="bento-name">Account Settings</strong>
                                                <span class="bento-desc">Manage your profile, vehicle, and payment accounts.</span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Right Panel (Create) -->
                        <div class="bento-menu-side">
                            <h3 class="bento-side-title">Create</h3>
                            <div class="bento-side-list">
                                @if(auth()->user()?->role === 'admin')
                                    <a href="{{ route('admin.users.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-user-plus"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">Manage Users</strong>
                                            <span class="bento-side-desc">Register or update user accounts.</span>
                                        </div>
                                    </a>
                                    <a href="{{ route('admin.reports.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-chart-pie"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">View Reports</strong>
                                            <span class="bento-side-desc">Analyze system metrics and export CSVs.</span>
                                        </div>
                                    </a>
                                @elseif(auth()->user()?->role === 'passenger')
                                    <a href="{{ route('explore.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">Request Seat</strong>
                                            <span class="bento-side-desc">Search active rides and request joins.</span>
                                        </div>
                                    </a>
                                    <a href="{{ route('connections.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-user-group"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">Add Connection</strong>
                                            <span class="bento-side-desc">Find and connect with verified drivers.</span>
                                        </div>
                                    </a>
                                @else
                                    {{-- Default / Driver --}}
                                    <a href="{{ route('trips.create') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-plus"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">Post Trip</strong>
                                            <span class="bento-side-desc">Offer empty seats to passengers.</span>
                                        </div>
                                    </a>
                                    <a href="{{ route('saved-routes.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-map-location-dot"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">New Route</strong>
                                            <span class="bento-side-desc">Pre-define a route template.</span>
                                        </div>
                                    </a>
                                    <a href="{{ route('settings.index') }}" class="bento-side-item">
                                        <span class="bento-side-icon-circle">
                                            <i class="fa-solid fa-qrcode"></i>
                                        </span>
                                        <div class="bento-side-info">
                                            <strong class="bento-side-name">Setup Wallet</strong>
                                            <span class="bento-side-desc">Add bank or DuitNow payment details.</span>
                                        </div>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </details>
            <details class="notification-wrap">
                <summary class="notification-toggle {{ $headerUnreadCount > 0 ? 'has-unread' : '' }}">
                    <i class="fa-solid fa-bell" aria-hidden="true"></i>
                    @if($headerUnreadCount > 0)
                        <span class="notification-badge">{{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}</span>
                    @endif
                </summary>
                <div class="notification-dropdown">
                    <div class="notification-dropdown-head">
                        <strong>Notifications</strong>
                        <form method="POST" action="{{ route('notifications.read-all') }}" class="notif-dropdown-mark-all-form">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="link-action">Mark All</button>
                        </form>
                    </div>
                    <div class="notification-items" data-notification-items>
                        {{-- Skeleton shown until first poll resolves --}}
                        <div id="notif-skeleton-initial" style="padding:4px 0;">
                            @for($sk = 0; $sk < 4; $sk++)
                                <div class="sk-notif-row">
                                    <span class="sk" style="width:34px;height:34px;border-radius:999px;flex-shrink:0;"></span>
                                    <div style="flex:1;display:grid;gap:7px;padding-top:3px;">
                                        <span class="sk" style="height:11px;width:{{ [62,75,55,68][$sk] }}%;"></span>
                                        <span class="sk" style="height:10px;width:{{ [38,28,45,32][$sk] }}%;"></span>
                                    </div>
                                </div>
                            @endfor
                        </div>
                        {{-- Real content rendered server-side as fallback --}}
                        <div id="notif-real-content" style="display:none;">
                            @forelse($headerNotifications as $notification)
                                @php
                                    $titleLower = strtolower($notification->title);
                                    $notifIcon = 'fa-bell';
                                    $notifBg = '#f1f5f9';
                                    $notifColor = '#64748b';

                                    if (str_contains($titleLower, 'join') || str_contains($titleLower, 'request')) {
                                        $notifIcon = 'fa-user-plus';
                                        $notifBg = '#e0f2fe';
                                        $notifColor = '#0284c7';
                                    } elseif (str_contains($titleLower, 'payment') || str_contains($titleLower, 'fare') || str_contains($titleLower, 'paid')) {
                                        $notifIcon = 'fa-credit-card';
                                        $notifBg = '#dcfce7';
                                        $notifColor = '#16a34a';
                                    } elseif (str_contains($titleLower, 'trip') || str_contains($titleLower, 'car') || str_contains($titleLower, 'ride')) {
                                        $notifIcon = 'fa-car-side';
                                        $notifBg = '#f3e8ff';
                                        $notifColor = '#9333ea';
                                    }
                                @endphp
                                <div class="notification-item {{ $notification->is_read ? '' : 'unread' }}">
                                    <div class="notification-item-icon-col">
                                        <span class="notification-icon-badge" style="background: {{ $notifBg }}; color: {{ $notifColor }};">
                                            <i class="fa-solid {{ $notifIcon }}"></i>
                                        </span>
                                    </div>
                                    <div class="notification-item-content-col">
                                        <a href="{{ route('notifications.open', $notification) }}" class="notification-item-link">
                                            <div class="notification-item-title-row">
                                                <span class="notification-item-title">{{ $notification->title }}</span>
                                                @if(! $notification->is_read)
                                                    <span class="unread-dot-indicator"></span>
                                                @endif
                                            </div>
                                            <div class="notification-item-message">{{ $notification->message }}</div>
                                        </a>
                                        <div class="notification-item-row">
                                            <span class="notification-item-time">{{ $notification->created_at?->diffForHumans() }}</span>
                                            @if(! $notification->is_read)
                                                <form method="POST" action="{{ route('notifications.read', $notification) }}" class="notif-dropdown-mark-read-form" style="display:inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="link-action">Mark Read</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="notification-empty">No notifications.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="notification-footer">
                        <a href="{{ route('notifications.index') }}" class="notification-view-all">View All</a>
                    </div>
                </div>
            </details>
            <details class="profile-wrap">
                <summary class="profile-toggle">
                    <span class="avatar-initial">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                    <div class="profile-details">
                        <span class="profile-name">{{ explode(' ', auth()->user()->name)[0] }}</span>
                        <span class="profile-role">{{ ucfirst(auth()->user()->role ?? 'driver') }}</span>
                    </div>
                    <i class="fa-solid fa-chevron-down profile-chevron"></i>
                </summary>
                <div class="profile-dropdown">
                    <div class="profile-dropdown-header">
                        <span class="avatar-initial">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                        <div class="profile-dropdown-meta">
                            <span class="profile-dropdown-name">{{ auth()->user()->name }}</span>
                            <span class="profile-dropdown-role">{{ ucfirst(auth()->user()->role ?? 'driver') }}</span>
                        </div>
                    </div>
                    <div class="profile-dropdown-divider"></div>
                    <a href="{{ route('profile.index') }}" class="profile-menu-link">
                        <i class="fa-solid fa-gear"></i>
                        <span>Profile</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="profile-menu-btn profile-menu-logout">
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

        @php
            $drawerRole = auth()->user()?->role;
            $drawerNavItems = match ($drawerRole) {
                'passenger' => [
                    ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
                    ['route' => 'explore.index', 'active' => ['explore.*'], 'icon' => 'fa-solid fa-compass', 'label' => 'Explore'],
                    ['route' => 'trips.index', 'active' => ['trips.*'], 'icon' => 'fa-solid fa-clock-rotate-left', 'label' => 'My Trips'],
                    ['route' => 'payments.index', 'active' => ['payments.*'], 'icon' => 'fa-solid fa-receipt', 'label' => 'Payments'],
                    ['route' => 'connections.index', 'active' => ['connections.*'], 'icon' => 'fa-solid fa-user-group', 'label' => 'Connections'],
                    ['route' => 'profile.index', 'active' => ['profile.*', 'settings.*'], 'icon' => 'fa-solid fa-gear', 'label' => 'Settings'],
                ],
                'admin' => [
                    ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
                    ['route' => 'admin.users.index', 'active' => ['admin.users.*'], 'icon' => 'fa-solid fa-users-gear', 'label' => 'Users Admin'],
                    ['route' => 'admin.reports.index', 'active' => ['admin.reports.*'], 'icon' => 'fa-solid fa-chart-line', 'label' => 'Reports'],
                    ['route' => 'trips.index', 'active' => ['trips.*'], 'icon' => 'fa-solid fa-clock-rotate-left', 'label' => 'All Trips'],
                    ['route' => 'explore.index', 'active' => ['explore.*'], 'icon' => 'fa-solid fa-compass', 'label' => 'Explore'],
                    ['route' => 'saved-routes.index', 'active' => ['saved-routes.*'], 'icon' => 'fa-solid fa-route', 'label' => 'Routes'],
                    ['route' => 'payments.index', 'active' => ['payments.*'], 'icon' => 'fa-solid fa-receipt', 'label' => 'Payments'],
                    ['route' => 'connections.index', 'active' => ['connections.*'], 'icon' => 'fa-solid fa-user-group', 'label' => 'Connections'],
                    ['route' => 'profile.index', 'active' => ['profile.*', 'settings.*'], 'icon' => 'fa-solid fa-gear', 'label' => 'Settings'],
                ],
                default => [
                    ['route' => 'home', 'active' => ['home', 'dashboard'], 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
                    ['route' => 'trips.create', 'active' => ['trips.create'], 'icon' => 'fa-solid fa-plus', 'label' => 'New Trip'],
                    ['route' => 'trips.index', 'active' => ['trips.index', 'trips.show', 'trips.edit', 'trips.requests.*'], 'icon' => 'fa-solid fa-clock-rotate-left', 'label' => 'My Trips'],
                    ['route' => 'explore.index', 'active' => ['explore.*'], 'icon' => 'fa-solid fa-compass', 'label' => 'Explore'],
                    ['route' => 'saved-routes.index', 'active' => ['saved-routes.*'], 'icon' => 'fa-solid fa-route', 'label' => 'Routes'],
                    ['route' => 'payments.index', 'active' => ['payments.*'], 'icon' => 'fa-solid fa-receipt', 'label' => 'Payments'],
                    ['route' => 'connections.index', 'active' => ['connections.*'], 'icon' => 'fa-solid fa-user-group', 'label' => 'Connections'],
                    ['route' => 'profile.index', 'active' => ['profile.*', 'settings.*'], 'icon' => 'fa-solid fa-gear', 'label' => 'Settings'],
                ],
            };
        @endphp

        <nav class="mobile-drawer-nav">
            @foreach($drawerNavItems as $item)
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs(...$item['active']) ? 'active' : '' }}"><i class="{{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span></a>
            @endforeach
            <form action="{{ route('logout') }}" method="POST" style="padding: 0 8px;">
                @csrf
                <button type="submit" class="profile-menu-btn profile-menu-logout" style="width:100%;">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
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
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        window.showToast("{{ session('status') }}", 'success');
                    });
                </script>
            @endif
            @if(session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        window.showToast("{{ session('success') }}", 'success');
                    });
                </script>
            @endif
            @if(session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        window.showToast("{{ session('error') }}", 'error');
                    });
                </script>
            @endif
            @if($errors->any())
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        window.showToast("{{ $errors->first() }}", 'error');
                    });
                </script>
            @endif

            @yield('content')
        </section>
    </main>
</div>

@auth
    @include('layouts.partials.mobile-bottom-nav')
    <x-ai-chat />
@endauth

<script>
    window.showToast = function(message, type) {
        type = type || 'success';
        var container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        var card = document.createElement('div');
        card.className = 'toast-card';
        
        var iconHtml = '';
        if (type === 'success') {
            iconHtml = '<span class="toast-icon toast-icon-success"><i class="fa-solid fa-check"></i></span>';
        } else if (type === 'error') {
            iconHtml = '<span class="toast-icon toast-icon-error"><i class="fa-solid fa-xmark"></i></span>';
        } else {
            iconHtml = '<span class="toast-icon toast-icon-info"><i class="fa-solid fa-info"></i></span>';
        }

        function escHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        card.innerHTML = iconHtml + '<span class="toast-message">' + escHtml(message) + '</span>';
        container.appendChild(card);

        requestAnimationFrame(function() {
            card.classList.add('show');
        });

        setTimeout(function() {
            card.classList.add('hide');
            card.classList.remove('show');
            setTimeout(function() {
                if (card.parentNode) {
                    card.parentNode.removeChild(card);
                }
            }, 350);
        }, 3200);
    };

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
        var dropdownDetails = document.querySelectorAll('.notification-wrap, .profile-wrap, .more-menu, .bento-menu-wrap');

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

        document.addEventListener('submit', function (e) {
            var form = e.target.closest('.notif-dropdown-mark-all-form');
            if (form) {
                e.preventDefault();
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: '_method=PATCH',
                    credentials: 'same-origin'
                }).then(function (r) {
                    if (!r.ok) return;
                    document.querySelectorAll('.notification-badge').forEach(function (badge) {
                        badge.style.display = 'none';
                    });
                    document.querySelectorAll('.notification-item.unread').forEach(function (item) {
                        item.classList.remove('unread');
                    });
                    document.querySelectorAll('.unread-dot-indicator').forEach(function (dot) {
                        dot.remove();
                    });
                    document.querySelectorAll('.notification-item-content-col form').forEach(function (f) {
                        f.remove();
                    });
                    if (window.showToast) {
                        window.showToast("All notifications marked as read.", "success");
                    }
                }).catch(function () {});
            }

            var readForm = e.target.closest('.notif-dropdown-mark-read-form');
            if (readForm) {
                e.preventDefault();
                fetch(readForm.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: '_method=PATCH',
                    credentials: 'same-origin'
                }).then(function (r) {
                    if (!r.ok) return;
                    var item = readForm.closest('.notification-item');
                    if (item) {
                        item.classList.remove('unread');
                        var dot = item.querySelector('.unread-dot-indicator');
                        if (dot) dot.remove();
                    }
                    readForm.remove();
                    document.querySelectorAll('.notification-badge').forEach(function (badge) {
                        var current = parseInt(badge.textContent.replace(/\D/g, ''), 10) || 0;
                        var next = current - 1;
                        if (next <= 0) {
                            badge.style.display = 'none';
                        } else {
                            badge.textContent = next > 99 ? '99+' : next;
                        }
                    });
                    if (window.showToast) {
                        window.showToast("Notification marked as read.", "success");
                    }
                }).catch(function () {});
            }
        });

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
            
            var titleLower = String(item.title || '').toLowerCase();
            var notifIcon = 'fa-bell';
            var notifBg = '#f1f5f9';
            var notifColor = '#64748b';

            if (titleLower.indexOf('join') !== -1 || titleLower.indexOf('request') !== -1) {
                notifIcon = 'fa-user-plus';
                notifBg = '#e0f2fe';
                notifColor = '#0284c7';
            } else if (titleLower.indexOf('payment') !== -1 || titleLower.indexOf('fare') !== -1 || titleLower.indexOf('paid') !== -1) {
                notifIcon = 'fa-credit-card';
                notifBg = '#dcfce7';
                notifColor = '#16a34a';
            } else if (titleLower.indexOf('trip') !== -1 || titleLower.indexOf('car') !== -1 || titleLower.indexOf('ride') !== -1) {
                notifIcon = 'fa-car-side';
                notifBg = '#f3e8ff';
                notifColor = '#9333ea';
            }

            var unreadDot = item.is_read
                ? ''
                : '<span class="unread-dot-indicator"></span>';

            var readButton = item.is_read
                ? ''
                : '<form method="POST" action="' + readAction + '" style="display:inline;">\
                        <input type="hidden" name="_token" value="' + csrfToken + '">\
                        <input type="hidden" name="_method" value="PATCH">\
                        <button type="submit" class="link-action">Mark Read</button>\
                   </form>';

            return '<div class="notification-item' + unreadClass + '">\
                        <div class="notification-item-icon-col">\
                            <span class="notification-icon-badge" style="background: ' + notifBg + '; color: ' + notifColor + ';">\
                                <i class="fa-solid ' + notifIcon + '"></i>\
                            </span>\
                        </div>\
                        <div class="notification-item-content-col">\
                            <a href="' + escHtml(item.open_url || item.target_url || '#') + '" class="notification-item-link">\
                                <div class="notification-item-title-row">\
                                    <span class="notification-item-title">' + escHtml(item.title || '') + '</span>\
                                    ' + unreadDot + '\
                                </div>\
                                <div class="notification-item-message">' + escHtml(item.message || '') + '</div>\
                            </a>\
                            <div class="notification-item-row">\
                                <span class="notification-item-time">' + escHtml(item.time_ago || '') + '</span>\
                                ' + readButton + '\
                            </div>\
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

        var notifFirstLoad = true;

        function showNotifSkeleton() {
            var sk = document.getElementById('notif-skeleton-initial');
            var real = document.getElementById('notif-real-content');
            if (sk) sk.style.display = '';
            if (real) real.style.display = 'none';
        }

        function hideNotifSkeleton() {
            var sk = document.getElementById('notif-skeleton-initial');
            var real = document.getElementById('notif-real-content');
            if (sk) sk.style.display = 'none';
            if (real) real.style.display = '';
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
                    if (payload) {
                        refreshNotificationDropdown(payload);
                        if (notifFirstLoad) {
                            hideNotifSkeleton();
                            notifFirstLoad = false;
                        }
                    }
                })
                .catch(function () {
                    if (notifFirstLoad) { hideNotifSkeleton(); notifFirstLoad = false; }
                })
                .finally(function () {
                    notificationsInFlight = false;
                });
        }

        /* Show skeleton when dropdown is freshly opened */
        document.addEventListener('toggle', function (e) {
            if (e.target && e.target.classList && e.target.classList.contains('notification-wrap')) {
                if (e.target.open && notifFirstLoad) { showNotifSkeleton(); }
            }
        }, true);

        window.setInterval(pollNotifications, 5000);
    })();

    // Bento Menu search filtering
    document.querySelectorAll('[data-bento-search]').forEach(function(searchInput) {
        searchInput.addEventListener('input', function() {
            var query = searchInput.value.trim().toLowerCase();
            var dropdown = searchInput.closest('.bento-menu-dropdown');
            if (!dropdown) return;

            var items = dropdown.querySelectorAll('[data-bento-item]');
            var sections = dropdown.querySelectorAll('[data-bento-section]');

            items.forEach(function(item) {
                var text = item.textContent.toLowerCase();
                if (text.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });

            sections.forEach(function(section) {
                var visibleItems = section.querySelectorAll('[data-bento-item]:not([style*="display: none"])');
                if (visibleItems.length === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = 'block';
                }
            });
        });
    });
</script>
</body>
</html>
