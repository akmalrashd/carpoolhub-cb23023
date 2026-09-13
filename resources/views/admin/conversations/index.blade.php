@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
@endpush

<div class="au-page">

<div>
    <p class="au-eyebrow">Admin Panel</p>
    <h1 class="au-title">Conversations</h1>
    <p class="au-sub">Read-only. Open any trip chat to investigate a dispute or safety report. Nothing here can be edited or deleted.</p>
</div>

@include('layouts.partials.admin-subnav')

<nav class="subview-tabs">
    <a href="{{ route('admin.audit-log.index', ['view' => 'admin']) }}">
        <i class="fa-solid fa-user-shield"></i> Admin Actions
    </a>
    <a href="{{ route('admin.audit-log.index', ['view' => 'payments']) }}">
        <i class="fa-solid fa-wallet"></i> Payment History
    </a>
    <a href="{{ route('admin.audit-log.index', ['view' => 'cancellations']) }}">
        <i class="fa-solid fa-calendar-xmark"></i> Trip Cancellations
    </a>
    <a href="{{ route('admin.conversations.index') }}" class="active">
        <i class="fa-solid fa-comments"></i> Conversations
    </a>
</nav>

<div class="card card-pad-lg">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
        <input type="text" name="q" class="eu-select" style="flex:1;min-width:200px;" placeholder="Search trip ref, route, or a member's name…" value="{{ $q }}">
        <button type="submit" class="lr-approve-btn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </form>

    @forelse($conversations as $conversation)
        <a href="{{ route('admin.conversations.show', $conversation) }}" class="dac-wrap" style="background:{{ $loop->odd ? 'var(--surface)' : 'var(--surface-2)' }};text-decoration:none;color:inherit;display:block;">
            <div class="dac-body">
                <div class="dac-row1">
                    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
                        <div class="dac-avatar" @unless($conversation->driver?->profile_photo_url) style="background:{{ $conversation->driver?->avatar_color }};" @endunless>
                            @if($conversation->driver?->profile_photo_url)
                                <img src="{{ $conversation->driver->profile_photo_url }}" alt="{{ $conversation->driver->name }}">
                            @else
                                {{ $conversation->driver?->avatar_initial }}
                            @endif
                        </div>
                        <div class="dac-info">
                            <div class="dac-name">{{ $conversation->route_snapshot ?: 'Trip chat' }}</div>
                            <div class="dac-meta">Driver: {{ $conversation->driver?->name ?: 'Unknown' }} · {{ $conversation->trip_ref_snapshot }}</div>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <span class="status-pill status-{{ $conversation->visibility_snapshot === 'public' ? 'active' : 'pending' }}">{{ ucfirst($conversation->visibility_snapshot) }}</span>
                        @if($conversation->scheduled_purge_at)
                            <div class="dac-meta" style="margin-top:4px;color:var(--danger-ink);">Closes {{ $conversation->scheduled_purge_at->diffForHumans() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </a>
    @empty
        <p style="text-align:center;color:var(--muted);padding:32px 0;">No conversations found.</p>
    @endforelse

    <div style="margin-top:12px;">{{ $conversations->links() }}</div>
</div>

</div>

@endsection
