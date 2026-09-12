@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/chats.css') }}?v={{ filemtime(public_path('css/chats.css')) }}">
@endpush

@php
    $me = auth()->user();
@endphp

<div class="chat-page">
    <div>
        <p class="chat-eyebrow">{{ ucfirst($me->role) }}</p>
        <h1 class="chat-title">Chat</h1>
        <p class="chat-sub">Trip chats appear a few days before departure and close a few days after — nothing to manage.</p>
    </div>

    <div class="chat-list">
        @forelse($conversations as $conversation)
            @php
                $lastMessage = $conversation->messages->first();
                $others = $conversation->participants->filter(fn ($p) => $p->user_id !== $me->id)->values();
                if ($others->isEmpty() && $conversation->driver) {
                    $others = collect([$conversation->driver])->map(fn ($u) => (object) ['user' => $u]);
                }
                $othersTotal = $others->count();
                $clusterSlots = $others->take(3);
                $clusterExtra = max(0, $othersTotal - 3);
                $clusterCount = $clusterExtra > 0 ? '4plus' : (string) max($othersTotal, 1);
                $unread = $unreadByConversation->get($conversation->id);
                $lastReadId = $unread?->last_read_message_id ?? 0;
                $hasUnread = $lastMessage && $lastMessage->id > $lastReadId && $lastMessage->sender_id !== $me->id;
                $isOpenYet = ! $conversation->opens_at || now()->gte($conversation->opens_at);
                $titleParts = array_filter([$conversation->trip_ref_snapshot, $conversation->route_snapshot ?: 'Trip chat']);
            @endphp
            <a href="{{ route('chats.show', $conversation) }}" class="chat-row {{ $hasUnread ? 'is-unread' : '' }}">
                <div class="chat-avatar-cluster count-{{ $clusterCount }}">
                    @foreach($clusterSlots as $i => $participant)
                        <span class="cluster-slot cluster-slot-{{ $i }}">
                            <x-avatar :user="$participant->user" size="{{ $i === 0 ? 'md' : 'sm' }}" />
                        </span>
                    @endforeach
                    @if($clusterExtra > 0)
                        <span class="cluster-badge">+{{ $clusterExtra }}</span>
                    @endif
                </div>
                <div class="chat-row-main">
                    <div class="chat-row-top">
                        <span class="chat-row-title">{{ implode(' · ', $titleParts) }}</span>
                        @if($lastMessage)
                            <span class="chat-row-time">{{ $lastMessage->created_at?->diffForHumans(null, true) }}</span>
                        @endif
                    </div>
                    <div class="chat-row-bottom">
                        <span class="chat-row-preview">
                            @if($lastMessage)
                                {{ \Illuminate\Support\Str::limit($lastMessage->body, 60) }}
                            @else
                                No messages yet
                            @endif
                        </span>
                        @if($hasUnread)
                            <span class="chat-row-unread-dot"></span>
                        @endif
                    </div>
                    @if(! $isOpenYet)
                        <span class="chat-row-badge">
                            <i class="fa-regular fa-clock"></i> Opens {{ $conversation->opens_at->diffForHumans() }}
                        </span>
                    @elseif($conversation->scheduled_purge_at)
                        <span class="chat-row-badge is-closing">
                            <i class="fa-solid fa-hourglass-half"></i> Closes {{ $conversation->scheduled_purge_at->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </a>
        @empty
            <div class="chat-empty">
                <i class="fa-regular fa-comments"></i>
                <p>No trip chats yet.</p>
                <span>A chat appears here once you have an upcoming trip with other people on it.</span>
            </div>
        @endforelse
    </div>
</div>

@endsection
