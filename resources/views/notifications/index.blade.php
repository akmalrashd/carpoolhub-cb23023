@extends('layouts.app')

@section('content')
    {{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}?v={{ filemtime(public_path('css/notifications.css')) }}">

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
            if (window.showToast) {
                window.showToast("Notification marked as read.", "success");
            }
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
            if (window.showToast) {
                window.showToast("Notification deleted.", "success");
            }
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
                if (window.showToast) {
                    window.showToast("Read notifications cleared.", "success");
                }
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
                if (window.showToast) {
                    window.showToast("All notifications marked as read.", "success");
                }
            }).catch(function () {});
        });
    }
})();
</script>

@endsection
