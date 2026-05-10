@extends('layouts.app')

@section('content')
    <style>
        .notif-page { display: grid; gap: 12px; }
        .notif-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
        }
        .notif-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
        }
        .notif-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 30px;
            line-height: 1.05;
            color: #0f172a;
        }
        .notif-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }
        .notif-tools {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .notif-unread {
            border: 1px solid #dbe2ea;
            border-radius: 999px;
            padding: 5px 10px;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }
        .notif-btn {
            border: 1px solid #fde68a;
            border-radius: 10px;
            background: #fffbeb;
            color: #92400e;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 12px;
            cursor: pointer;
        }
        .notif-list {
            display: grid;
            gap: 8px;
        }
        .notif-item {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            display: grid;
            gap: 6px;
        }
        .notif-item.unread {
            border-color: #fde68a;
            background: #fffbeb;
        }
        .notif-item-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .notif-type {
            border: 1px solid #dbe2ea;
            border-radius: 999px;
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            padding: 4px 8px;
        }
        .notif-status {
            font-size: 12px;
            font-weight: 700;
        }
        .notif-status.read { color: #166534; }
        .notif-status.unread { color: #b45309; }
        .notif-item-title {
            margin: 0;
            font-size: 19px;
            font-weight: 700;
            line-height: 1.2;
        }
        .notif-item-title a {
            color: #0f172a;
            text-decoration: none;
        }
        .notif-item-title a:hover { color: #1d4ed8; }
        .notif-item-message {
            margin: 0;
            font-size: 14px;
            color: #475569;
            line-height: 1.4;
        }
        .notif-item-message a {
            color: inherit;
            text-decoration: none;
        }
        .notif-item-message a:hover { color: #1d4ed8; }
        .notif-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .notif-time {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }
        .notif-link-btn {
            border: 1px solid #dbe2ea;
            border-radius: 9px;
            background: #fff;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 10px;
            cursor: pointer;
        }
    </style>

    <div class="notif-page">
        <section class="notif-card">
            <div class="notif-head">
                <div>
                    <h1 class="notif-title">Notifications</h1>
                    <p class="notif-subtitle">Track approvals, reminders, and system updates.</p>
                </div>
                <div class="notif-tools">
                    <span class="notif-unread">Unread: {{ $unreadCount }}</span>
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="notif-btn">Mark All as Read</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="notif-card">
            <div class="notif-list">
                @forelse($notifications as $notification)
                    <article class="notif-item {{ $notification->is_read ? '' : 'unread' }}">
                        <div class="notif-item-top">
                            <span class="notif-type">{{ ucfirst($notification->type) }}</span>
                            <span class="notif-status {{ $notification->is_read ? 'read' : 'unread' }}">
                                {{ $notification->is_read ? 'Read' : 'Unread' }}
                            </span>
                        </div>
                        <h2 class="notif-item-title">
                            <a href="{{ route('notifications.open', $notification) }}">{{ $notification->title }}</a>
                        </h2>
                        <p class="notif-item-message">
                            <a href="{{ route('notifications.open', $notification) }}">{{ $notification->message }}</a>
                        </p>
                        <div class="notif-item-row">
                            <span class="notif-time">{{ $notification->created_at?->format('d M Y, H:i') }}</span>
                            @if(! $notification->is_read)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="notif-link-btn">Mark as Read</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <article class="notif-item">
                        <p class="notif-item-message">No notifications found.</p>
                    </article>
                @endforelse
            </div>

            <div style="margin-top:12px;">
                {{ $notifications->links() }}
            </div>
        </section>
    </div>
@endsection
