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
            display: inline-flex;
            gap: 4px;
            flex-wrap: nowrap;
            padding: 4px;
            border: 1px solid var(--hairline);
            border-radius: 10px;
            background: var(--surface-2);
            max-width: 100%;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            margin-bottom: 16px;
        }

        .notif-tabs::-webkit-scrollbar { display: none; }

        .notif-tab-btn {
            display: inline-flex;
            align-items: center;
            flex: 0 0 auto;
            border: 1px solid transparent;
            border-radius: 8px;
            background: transparent;
            color: var(--muted);
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            transition: background .14s, border-color .14s, color .14s;
            font-family: var(--font-ui), sans-serif;
        }

        .notif-tab-btn:hover {
            background: var(--canvas);
            border-color: var(--ch-yellow-line);
            color: var(--ink-2);
            text-decoration: none;
        }

        .notif-tab-btn.is-active {
            background: var(--surface);
            border-color: var(--hairline);
            color: var(--ink);
            box-shadow: var(--shadow-1);
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
            <button type="button" class="btn btn-ghost btn-sm" id="notif-clear-read-btn"
                    data-url="{{ route('notifications.clear-read') }}"
                    title="Delete all read notifications">
                <i class="fa-solid fa-trash-can"></i> Clear read
            </button>
            <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0;" id="notif-mark-all-form">
                @csrf
                @method('PATCH')
                <button type="button" class="btn btn-ghost btn-sm" id="notif-mark-all-btn">
                    <i class="fa-solid fa-check-double"></i> Mark all as read
                </button>
            </form>
        </div>
    </div>

    {{-- ── Tab strip (server-side filter) ── --}}
    @php
        $tabs = [
            'all'        => ['label' => 'All',         'icon' => 'fa-solid fa-inbox'],
            'unread'     => ['label' => 'Unread',      'icon' => 'fa-solid fa-circle'],
            'trip'       => ['label' => 'Trips',       'icon' => 'fa-solid fa-car-side'],
            'payment'    => ['label' => 'Payments',    'icon' => 'fa-solid fa-coins'],
            'connection' => ['label' => 'Connections', 'icon' => 'fa-solid fa-user-group'],
            'system'     => ['label' => 'System',      'icon' => 'fa-solid fa-gear'],
            'route'      => ['label' => 'Routes',      'icon' => 'fa-solid fa-route'],
        ];
    @endphp
    <div class="notif-tabs" role="tablist" aria-label="Filter notifications">
        @foreach($tabs as $key => $tab)
            @php $count = $tabCounts[$key] ?? 0; @endphp
            @if($count > 0 || $key === 'all')
                <a
                    href="{{ route('notifications.index', $key !== 'all' ? ['filter' => $key] : []) }}"
                    class="notif-tab-btn {{ $filter === $key ? 'is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
                >{{ $tab['label'] }} &middot; {{ $count }}</a>
            @endif
        @endforeach
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
                        @if($notification->created_at?->isAfter(now()->subDay()))
                            {{ $notification->created_at->diffForHumans() }}
                        @else
                            {{ $notification->created_at?->format('d M Y, H:i') }}
                        @endif
                    </span>
                </div>

                {{-- Actions --}}
                <div class="notif-row-actions">
                    <a href="{{ route('notifications.open', $notification) }}" class="btn btn-ghost btn-sm">
                        Open
                    </a>
                    @if($isUnread)
                        <button type="button"
                                class="btn btn-ghost btn-sm notif-mark-read-btn"
                                data-notif-id="{{ $notification->id }}"
                                data-url="{{ route('notifications.read', $notification) }}"
                                style="font-size:11px; padding:0 10px; height:28px;"
                                title="Mark as read">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    @endif
                    <button type="button"
                            class="btn btn-ghost btn-sm notif-delete-btn"
                            data-notif-id="{{ $notification->id }}"
                            data-was-unread="{{ $isUnread ? '1' : '0' }}"
                            data-url="{{ route('notifications.destroy', $notification) }}"
                            style="font-size:11px; padding:0 10px; height:28px; color:var(--danger);"
                            title="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>
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
                {{ $notifications->appends(['filter' => $filter])->links() }}
            </div>
        @endif

    </div>

<script>
(function () {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function fadeRemove(row) {
        row.style.transition = 'opacity .2s, max-height .25s';
        row.style.opacity = '0';
        row.style.overflow = 'hidden';
        setTimeout(function () { row.remove(); }, 250);
    }

    function adjustUnreadBadge(delta) {
        var badge = document.querySelector('.notif-unread-badge');
        if (!badge) return;
        var current = parseInt(badge.textContent.replace(/\D/g, ''), 10) || 0;
        var next = current + delta;
        if (next <= 0) {
            badge.style.display = 'none';
        } else {
            badge.querySelector('i').insertAdjacentText('afterend', ' ' + next + ' Unread');
            badge.textContent = '';
            badge.innerHTML = '<i class="fa-solid fa-bell"></i> ' + next + ' Unread';
        }
    }

    /* ── Mark as read (AJAX, no reload) ── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.notif-mark-read-btn');
        if (!btn) return;
        e.preventDefault();
        fetch(btn.dataset.url, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            credentials: 'same-origin',
        }).then(function (r) {
            if (!r.ok) return;
            var row = btn.closest('.notif-row');
            if (!row) return;
            row.classList.remove('is-unread');
            row.classList.add('is-read');
            row.style.borderLeft = '';
            row.style.background = '';
            btn.remove();
            adjustUnreadBadge(-1);
        }).catch(function () {});
    });

    /* ── Delete notification (AJAX) ── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.notif-delete-btn');
        if (!btn) return;
        e.preventDefault();
        var wasUnread = btn.dataset.wasUnread === '1';
        fetch(btn.dataset.url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            credentials: 'same-origin',
        }).then(function (r) {
            if (!r.ok) return;
            var row = btn.closest('.notif-row');
            if (row) fadeRemove(row);
            if (wasUnread) adjustUnreadBadge(-1);
        }).catch(function () {});
    });

    /* ── Clear all read (AJAX) ── */
    var clearBtn = document.getElementById('notif-clear-read-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            fetch(clearBtn.dataset.url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
            }).then(function (r) {
                if (!r.ok) return;
                document.querySelectorAll('.notif-row.is-read').forEach(fadeRemove);
            }).catch(function () {});
        });
    }

    /* ── Mark all as read (AJAX) ── */
    var markAllBtn = document.getElementById('notif-mark-all-btn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
            var form = document.getElementById('notif-mark-all-form');
            fetch(form.action, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json',
                           'Content-Type': 'application/x-www-form-urlencoded' },
                body: '_method=PATCH',
                credentials: 'same-origin',
            }).then(function (r) {
                if (!r.ok) return;
                document.querySelectorAll('.notif-row.is-unread').forEach(function (row) {
                    row.classList.remove('is-unread');
                    row.classList.add('is-read');
                    row.style.borderLeft = '';
                    row.style.background = '';
                    var readBtn = row.querySelector('.notif-mark-read-btn');
                    if (readBtn) readBtn.remove();
                });
                var badge = document.querySelector('.notif-unread-badge');
                if (badge) badge.style.display = 'none';
            }).catch(function () {});
        });
    }
})();
</script>

@endsection
