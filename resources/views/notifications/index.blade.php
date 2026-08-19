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
            <button type="button" class="btn btn-ghost btn-sm notif-clear-read-btn" id="notif-clear-read-btn"
                    data-url="{{ route('notifications.clear-read') }}"
                    title="Tidy up — removes notifications you've already read">
                <i class="fa-solid fa-broom"></i> Tidy up
            </button>
            <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0;" id="notif-mark-all-form">
                @csrf
                @method('PATCH')
                <button type="button" class="btn btn-ghost btn-sm notif-mark-all-btn" id="notif-mark-all-btn">
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
    <div class="{{ $notifications->isEmpty() ? '' : 'notif-card' }}">

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
            <div class="ch-empty-state-card">
                <div class="ch-empty-state-icon-box"><i class="fa-regular fa-bell-slash"></i></div>
                <h3 class="ch-empty-state-title">No notifications yet</h3>
                <p class="ch-empty-state-body">You're all caught up — check back later.</p>
            </div>
        @endforelse

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div class="notif-pagination">
                {{ $notifications->appends(['filter' => $filter])->links() }}
            </div>
        @endif

    </div>

<script>window.CH_NOTIFICATIONS = { csrf: @json(csrf_token()) };</script>
<script src="{{ asset('js/notifications.js') }}?v={{ filemtime(public_path('js/notifications.js')) }}"></script>

@endsection
