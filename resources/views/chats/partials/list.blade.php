{{-- Chat list pane — shared by chats/index.blade.php (full width on mobile,
     left pane on desktop) and chats/show.blade.php (desktop's left pane
     only; hidden on mobile, where a thread takes the whole screen). Same
     $conversations/$me/$unreadByConversation the controller already builds
     for the index page; $activeConversationId (a public_id or null)
     highlights whichever thread is currently open in the right pane. --}}
<div>
    <p class="chat-eyebrow">{{ ucfirst($me->role) }}</p>
    <h1 class="chat-title">Chat</h1>
    <p class="chat-sub">Trip chats appear a few days before departure and close a few days after, so there's nothing to manage.</p>
</div>

<div class="chat-search">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="search" id="chatSearchInput" placeholder="Search trips or passengers...">
</div>

<div class="chat-list" id="chatList">
    @forelse($conversations as $conversation)
        @include('chats.partials.row', ['conversation' => $conversation, 'me' => $me, 'unreadRecord' => $unreadByConversation->get($conversation->id), 'activeConversationId' => $activeConversationId ?? null])
    @empty
        <x-empty icon="fa-regular fa-comments" title="No trip chats yet" body="A chat appears here once you have an upcoming trip with other people on it." />
    @endforelse
</div>
