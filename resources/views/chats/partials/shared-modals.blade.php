{{-- Every modal a chat thread can trigger, rendered exactly once at the
     page-shell level (chats/index.blade.php AND chats/show.blade.php both
     include this) rather than inside the swappable thread partial
     (chats.partials.thread). All of these already delegate their trigger
     bindings on `document` and read everything they need from the clicked
     trigger's data-* attributes — the same pattern trips/index.blade.php
     uses for the identical modals — so a fresh conversation's trigger
     buttons (rendered inside the thread partial, which DOES get replaced
     on every chat switch) are picked up automatically without these modals
     or their scripts ever needing to re-run. Including them inside the
     swappable partial instead would pile up a duplicate hidden modal (and
     a duplicate set of document-level listeners) on every single chat
     switch — see chat-thread-controller.js's header comment. --}}
<div class="chat-lightbox" id="chatLightbox" aria-hidden="true">
    <div class="chat-lightbox-viewport" id="chatLightboxViewport">
        <img class="chat-lightbox-img" id="chatLightboxImg" src="" alt="Photo">
    </div>
</div>

@include('chats.partials.invite-modal')
@include('trips.partials.rate-trip-modal')
@include('trips.partials.trip-details-modal')
@include('trips.partials.trip-requests-modal')

<script>window.CH_TRIPS = { csrf: @json(csrf_token()) };</script>
<script src="{{ asset('js/chat-lightbox.js') }}?v={{ filemtime(public_path('js/chat-lightbox.js')) }}"></script>
<script src="{{ asset('js/chat-invite-connections.js') }}?v={{ filemtime(public_path('js/chat-invite-connections.js')) }}"></script>
<script src="{{ asset('js/rate-trip-modal.js') }}?v={{ filemtime(public_path('js/rate-trip-modal.js')) }}"></script>
<script src="{{ asset('js/trip-details-modal.js') }}?v={{ filemtime(public_path('js/trip-details-modal.js')) }}"></script>
<script src="{{ asset('js/trip-requests-modal.js') }}?v={{ filemtime(public_path('js/trip-requests-modal.js')) }}"></script>
