{{-- Single chat-list row — shared by the initial index render and the
     refresh.chats.list / refresh.chats.row JSON endpoints (chats-index.js
     replaces/reorders this exact markup live via Ably + a polling fallback,
     so this partial must stay the only place that builds a row).

     Two different ids on purpose: data-conversation-id is the route-facing
     public_id (used to build the refresh.chats.row URL — routes now bind
     Conversation by public_id, see HasPublicId), while
     data-conversation-channel-id is the internal sequential id, since that's
     still what the server broadcasts messages on (private:conversation.{id}
     in MessageSent/routes/channels.php was left untouched deliberately —
     only the browsable URL needed hiding, not the pub/sub channel name,
     which a valid Ably token already scopes access to). --}}
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
    $lastReadId = $unreadRecord?->last_read_message_id ?? 0;
    $hasUnread = $lastMessage && $lastMessage->id > $lastReadId && $lastMessage->sender_id !== $me->id;
    $isOpenYet = ! $conversation->opens_at || now()->gte($conversation->opens_at);
    $titleParts = array_filter([$conversation->trip_ref_snapshot, $conversation->route_snapshot ?: 'Trip chat']);
    $isActive = isset($activeConversationId) && $activeConversationId === $conversation->public_id;
    $previewSender = null;
    if ($lastMessage && ! $lastMessage->isSystem()) {
        $previewSender = match (true) {
            $lastMessage->isFromHexa() => 'Hexa',
            $lastMessage->sender_id === $me->id => 'You',
            default => $lastMessage->sender?->name ?? 'Deleted user',
        };
    }
@endphp
<a
    href="{{ route('chats.show', $conversation) }}"
    class="chat-row {{ $hasUnread ? 'is-unread' : '' }} {{ $isActive ? 'is-active' : '' }}"
    data-conversation-id="{{ $conversation->public_id }}"
    data-conversation-channel-id="{{ $conversation->id }}"
    data-last-message-id="{{ $lastMessage->id ?? 0 }}"
>
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
                    @if($previewSender)<strong class="chat-row-preview-sender">{{ $previewSender }}:</strong>@endif
                    @if($lastMessage->type === 'image')
                        <i class="fa-solid fa-camera"></i> Photo
                    @else
                        {{ \Illuminate\Support\Str::limit($lastMessage->body, 60) }}
                    @endif
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
