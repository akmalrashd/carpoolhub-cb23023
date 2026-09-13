@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
<link rel="stylesheet" href="{{ asset('css/chats.css') }}?v={{ filemtime(public_path('css/chats.css')) }}">
@endpush

<div class="au-page">

<div>
    <p class="au-eyebrow">Admin Panel · Conversations</p>
    <h1 class="au-title">{{ $conversation->route_snapshot ?: 'Trip chat' }}</h1>
    <p class="au-sub">
        {{ $conversation->trip_ref_snapshot }} · Driver: {{ $conversation->driver?->name ?: 'Unknown' }} ·
        Members: {{ $conversation->participants->map(fn ($p) => $p->user?->name)->filter()->implode(', ') }}
    </p>
</div>

<a href="{{ route('admin.conversations.index') }}" class="lr-approve-btn" style="width:fit-content;">
    <i class="fa-solid fa-arrow-left"></i> Back to Conversations
</a>

<div class="card card-pad-lg">
    <div class="chat-thread-messages" style="padding:0;">
        @forelse($messages as $message)
            @if($message->isSystem())
                <div class="chat-bubble-row is-system">
                    <span class="chat-bubble-system">{{ $message->body }}</span>
                </div>
            @else
                <div class="chat-bubble-row">
                    <x-avatar :user="$message->sender" size="sm" class="chat-bubble-avatar" />
                    <div class="chat-bubble-col">
                        <span class="chat-bubble-sender">{{ $message->sender?->name ?? 'Deleted user' }}</span>
                        <div class="chat-bubble">{{ $message->body }}</div>
                        <span class="chat-bubble-time">{{ $message->created_at?->clone()->setTimezone(\App\Models\Trip::TIMEZONE)->format('d M Y, g:i A') }}</span>
                    </div>
                </div>
            @endif
        @empty
            <p style="text-align:center;color:var(--muted);padding:32px 0;">No messages in this conversation.</p>
        @endforelse
    </div>
</div>

</div>

@endsection
