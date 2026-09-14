@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/chats.css') }}?v={{ filemtime(public_path('css/chats.css')) }}">
{{-- Unconditional now (not per-conversation) — see chats/partials/shared-modals.blade.php's
     header comment for why the modals these feed are always loaded too. --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="{{ asset('css/trips.css') }}?v={{ filemtime(public_path('css/trips.css')) }}">
@endpush

@php
    $me = auth()->user();
@endphp

{{-- Both must run BEFORE chats.partials.thread's own <script> tags (only
     present once a chat-row click swaps a real thread into #chatThreadMount
     below) — see chats/show.blade.php's identical comment and
     chat-thread-controller.js's header comment. --}}
<script src="{{ asset('js/chat-thread-controller.js') }}?v={{ filemtime(public_path('js/chat-thread-controller.js')) }}"></script>
<script src="https://cdn.ably.com/lib/ably.min-2.js" crossorigin="anonymous"></script>

<div class="chat-split">
    <div class="chat-split-list">
        <div class="chat-page">
            @include('chats.partials.list', ['conversations' => $conversations, 'me' => $me, 'unreadByConversation' => $unreadByConversation, 'activeConversationId' => null])
        </div>
    </div>
    {{-- chat-thread-controller.js's mount() replaces this element's entire
         innerHTML (and re-runs whatever <script> tags come with it) once a
         chat row is clicked — see that file's header comment. Starts out
         holding just the "Select a chat" placeholder (desktop-only; see
         .chat-split-empty). --}}
    <div class="chat-split-thread-mount" id="chatThreadMount">
        @include('chats.partials.thread-empty')
    </div>
</div>

@include('chats.partials.shared-modals')

<script>window.CH_CHATS_INDEX = {
    listUrl: @json(route('refresh.chats.list')),
    // __ID__ is swapped for the real conversation id client-side — one
    // template covers every row instead of round-tripping a URL per row.
    rowUrlTemplate: @json(route('refresh.chats.row', ['conversation' => '__ID__'])),
    ablyTokenUrl: @json(route('chats.ably-token')),
};</script>
<script src="{{ asset('js/chats-index.js') }}?v={{ filemtime(public_path('js/chats-index.js')) }}"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

@endsection
