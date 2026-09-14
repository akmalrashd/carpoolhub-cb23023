{{-- Desktop-only right pane when no chat is open yet — see .chat-split-empty.
     Lives inside #chatThreadMount so chat-thread-controller.js can swap it
     out for a real thread (chats.partials.thread) without a page reload,
     the same way it swaps a thread back out for this on the way "back". --}}
<div class="chat-split-empty">
    <x-empty icon="fa-regular fa-comment-dots" title="Select a chat" body="Choose a conversation from the list to start messaging." />
</div>
