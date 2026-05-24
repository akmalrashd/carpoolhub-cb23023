@extends('layouts.app')

@section('content')
    <style>
        /* ── Page header ── */
        .pg-eyebrow {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            font-family: var(--font-ui), sans-serif;
            margin: 0 0 4px;
        }

        .pg-title {
            margin: 0 0 2px;
            font-family: var(--font-display), sans-serif;
            font-size: clamp(1.4rem, 2.2vw, 1.75rem);
            font-weight: 700;
            color: var(--ink);
            line-height: 1.1;
        }

        .pg-sub {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .pg-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .pg-header-actions {
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            flex-shrink: 0;
        }

        /* ── Unread badge ── */
        .notif-unread-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid var(--ch-yellow-line);
            border-radius: var(--r-pill);
            padding: 5px 12px;
            background: var(--ch-yellow-tint);
            color: var(--warning);
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        /* ── Tab strip ── */
        .notif-tabs {
            display: flex;
            gap: 4px;
            overflow-x: auto;
            padding-bottom: 14px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .notif-tabs::-webkit-scrollbar { display: none; }

        .notif-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: var(--r-pill);
            border: 1px solid var(--hairline);
            background: var(--surface);
            color: var(--ink-3);
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s;
            font-family: var(--font-ui), sans-serif;
        }

        .notif-tab-btn:hover {
            background: var(--canvas);
            color: var(--ink);
        }

        .notif-tab-btn.is-active {
            background: var(--ch-yellow-tint);
            border-color: var(--ch-yellow-line);
            color: var(--warning-ink);
            font-weight: 700;
        }

        .notif-tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: var(--r-pill);
            background: var(--hairline-strong);
            color: var(--ink-3);
            font-size: 10px;
            font-weight: 800;
        }

        .notif-tab-btn.is-active .notif-tab-count {
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
        }

        /* ── Main notifications card (padding:0) ── */
        .notif-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 0;
            box-shadow: var(--shadow-1);
            overflow: hidden;
        }

        /* ── Notification rows ── */
        .notif-row {
            display: flex;
            gap: 14px;
            padding: 16px;
            border-bottom: 1px solid var(--hairline);
            align-items: flex-start;
            transition: background .15s;
        }

        .notif-row:last-child {
            border-bottom: 0;
        }

        .notif-row:hover {
            background: var(--surface-2);
        }

        /* Unread: yellow-tint bg + yellow left border */
        .notif-row.is-unread {
            background: var(--ch-yellow-tint);
            border-left: 3px solid var(--ch-yellow);
        }

        .notif-row.is-unread:hover {
            background: var(--ch-yellow-soft);
        }

        /* ── Icon box ── */
        .notif-icon-box {
            width: 40px;
            height: 40px;
            border-radius: var(--r-sm);
            border: 1px solid var(--hairline);
            display: grid;
            place-items: center;
            font-size: 16px;
            flex: 0 0 auto;
        }

        .notif-icon-trip       { background: var(--info-soft);    color: var(--info);    border-color: rgba(37,99,235,.18); }
        .notif-icon-payment    { background: var(--success-soft); color: var(--success); border-color: rgba(22,163,74,.18); }
        .notif-icon-connection { background: var(--warning-soft); color: var(--warning); border-color: rgba(180,83,9,.18); }
        .notif-icon-system     { background: var(--canvas);       color: var(--muted);   border-color: var(--hairline); }
        .notif-icon-alert      { background: var(--danger-soft);  color: var(--danger);  border-color: rgba(220,38,38,.18); }
        .notif-icon-default    { background: var(--canvas);       color: var(--ink-3);   border-color: var(--hairline); }

        /* ── Row content ── */
        .notif-content {
            flex: 1;
            min-width: 0;
        }

        .notif-content-title {
            margin: 0 0 3px;
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.3;
        }

        .notif-content-title a {
            color: inherit;
            text-decoration: none;
        }

        .notif-content-title a:hover { color: var(--info); }

        .notif-content-body {
            margin: 0 0 6px;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.45;
        }

        .notif-content-body a {
            color: inherit;
            text-decoration: none;
        }

        .notif-content-body a:hover { color: var(--info); }

        .notif-content-time {
            font-size: 11px;
            font-weight: 600;
            color: var(--muted-2);
            font-family: var(--font-mono), monospace;
        }

        /* ── Row actions (right side) ── */
        .notif-row-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
            flex-shrink: 0;
        }

        /* ── Group label ── */
        .notif-group-label {
            padding: 10px 16px 6px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--muted);
            border-bottom: 1px solid var(--hairline);
            background: var(--surface-2);
        }

        /* ── Empty state ── */
        .notif-empty {
            padding: 48px 20px 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            text-align: center;
        }

        .notif-empty-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--r-pill);
            background: var(--canvas);
            display: grid;
            place-items: center;
            font-size: 26px;
            color: var(--muted-2);
        }

        .notif-empty-text {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink-3);
        }

        .notif-empty-sub {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
        }

        /* ── Pagination area ── */
        .notif-pagination {
            padding: 14px 16px;
            border-top: 1px solid var(--hairline);
        }
    </style>

    {{-- ── Page header ── --}}
    <div class="pg-header">
        <div>
            <p class="pg-eyebrow">Inbox</p>
            <h1 class="pg-title">Notifications</h1>
            <p class="pg-sub">All your trip, payment and connection updates.</p>
        </div>
        <div class="pg-header-actions">
            @if($unreadCount > 0)
                <span class="notif-unread-badge">
                    <i class="fa-solid fa-bell"></i> {{ $unreadCount }} Unread
                </span>
            @endif
            <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0;">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-check-double"></i> Mark all as read
                </button>
            </form>
            <a href="{{ route('settings.index') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-sliders"></i> Preferences
            </a>
        </div>
    </div>

    {{-- ── Tab strip ── --}}
    @php
        $allCount    = $notifications->total();
        $tripCount   = $notifications->getCollection()->where('type', 'trip')->count();
        $payCount    = $notifications->getCollection()->where('type', 'payment')->count();
    @endphp
    <div class="notif-tabs" role="tablist" aria-label="Filter notifications">
        <button type="button" class="notif-tab-btn is-active" data-notif-tab="all">
            All <span class="notif-tab-count">{{ $allCount }}</span>
        </button>
        <button type="button" class="notif-tab-btn" data-notif-tab="unread">
            Unread <span class="notif-tab-count">{{ $unreadCount }}</span>
        </button>
        <button type="button" class="notif-tab-btn" data-notif-tab="trip">
            Trips
        </button>
        <button type="button" class="notif-tab-btn" data-notif-tab="payment">
            Payments
        </button>
    </div>

    {{-- ── Notifications card ── --}}
    <div class="notif-card">

        @php $shownReadGroup = false; $shownUnreadGroup = false; @endphp

        @forelse($notifications as $notification)
            @php
                $type    = strtolower($notification->type ?? '');
                $iconMap = [
                    'trip'       => ['class' => 'notif-icon-trip',       'icon' => 'fa-solid fa-route'],
                    'payment'    => ['class' => 'notif-icon-payment',    'icon' => 'fa-solid fa-coins'],
                    'connection' => ['class' => 'notif-icon-connection', 'icon' => 'fa-solid fa-user-group'],
                    'system'     => ['class' => 'notif-icon-system',     'icon' => 'fa-solid fa-gear'],
                    'alert'      => ['class' => 'notif-icon-alert',      'icon' => 'fa-solid fa-triangle-exclamation'],
                ];
                $iconCfg  = $iconMap[$type] ?? ['class' => 'notif-icon-default', 'icon' => 'fa-solid fa-bell'];
                $isUnread = ! $notification->is_read;
            @endphp

            {{-- Unread group label (once) --}}
            @if($isUnread && ! $shownUnreadGroup)
                <div class="notif-group-label">Unread</div>
                @php $shownUnreadGroup = true; @endphp
            @endif

            {{-- Read group label (once, after unread section) --}}
            @if(! $isUnread && ! $shownReadGroup)
                <div class="notif-group-label">Earlier</div>
                @php $shownReadGroup = true; @endphp
            @endif

            <div class="notif-row {{ $isUnread ? 'is-unread' : 'is-read' }}" data-notif-type="{{ $type }}">

                {{-- Icon --}}
                <div class="notif-icon-box {{ $iconCfg['class'] }}">
                    <i class="{{ $iconCfg['icon'] }}"></i>
                </div>

                {{-- Content --}}
                <div class="notif-content">
                    <h2 class="notif-content-title">
                        <a href="{{ route('notifications.open', $notification) }}">{{ $notification->title }}</a>
                    </h2>
                    <p class="notif-content-body">
                        <a href="{{ route('notifications.open', $notification) }}">{{ $notification->message }}</a>
                    </p>
                    <span class="notif-content-time">
                        <i class="fa-regular fa-clock" style="margin-right:3px;"></i>
                        {{ $notification->created_at?->format('d M Y, H:i') }}
                    </span>
                </div>

                {{-- Actions --}}
                <div class="notif-row-actions">
                    <a href="{{ route('notifications.open', $notification) }}" class="btn btn-ghost btn-sm">
                        Open
                    </a>
                    @if($isUnread)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}" style="margin:0;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-ghost btn-sm" style="font-size:11px; padding:0 10px; height:28px;" title="Mark as read">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </form>
                    @endif
                </div>

            </div>

        @empty
            <div class="notif-empty">
                <div class="notif-empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
                <p class="notif-empty-text">No notifications yet</p>
                <p class="notif-empty-sub">You're all caught up — check back later.</p>
            </div>
        @endforelse

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div class="notif-pagination">
                {{ $notifications->links() }}
            </div>
        @endif

    </div>

    <script>
        (function () {
            var tabBtns = document.querySelectorAll('[data-notif-tab]');
            var rows    = document.querySelectorAll('.notif-row[data-notif-type]');

            tabBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var tab = btn.getAttribute('data-notif-tab');

                    /* Update active tab */
                    tabBtns.forEach(function (b) { b.classList.remove('is-active'); });
                    btn.classList.add('is-active');

                    /* Filter rows */
                    rows.forEach(function (row) {
                        var type    = row.getAttribute('data-notif-type');
                        var unread  = row.classList.contains('is-unread');
                        var visible = false;

                        if (tab === 'all')    visible = true;
                        else if (tab === 'unread')  visible = unread;
                        else if (tab === 'trip')    visible = type === 'trip';
                        else if (tab === 'payment') visible = type === 'payment';

                        row.style.display = visible ? '' : 'none';
                    });

                    /* Hide group labels if no visible rows follow them */
                    document.querySelectorAll('.notif-group-label').forEach(function (label) {
                        var next = label.nextElementSibling;
                        var hasVisible = false;
                        while (next && !next.classList.contains('notif-group-label') && !next.classList.contains('notif-pagination')) {
                            if (next.style.display !== 'none') { hasVisible = true; break; }
                            next = next.nextElementSibling;
                        }
                        label.style.display = hasVisible ? '' : 'none';
                    });
                });
            });
        })();
    </script>
@endsection
