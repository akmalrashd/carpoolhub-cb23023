@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/chats.css') }}?v={{ filemtime(public_path('css/chats.css')) }}">
@endpush

@php
    $me = auth()->user();
@endphp

<div class="chat-split">
    <div class="chat-split-list">
        <div class="chat-page">
            @include('chats.partials.list', ['conversations' => $conversations, 'me' => $me, 'unreadByConversation' => $unreadByConversation, 'activeConversationId' => null])
        </div>
    </div>
    {{-- Desktop-only right pane when nothing is open yet — see .chat-split-empty. --}}
    <div class="chat-split-empty">
        <x-empty icon="fa-regular fa-comment-dots" title="Select a chat" body="Choose a conversation from the list to start messaging." />
    </div>
</div>

<script>window.CH_CHATS_INDEX = {
    listUrl: @json(route('refresh.chats.list')),
    // __ID__ is swapped for the real conversation id client-side — one
    // template covers every row instead of round-tripping a URL per row.
    rowUrlTemplate: @json(route('refresh.chats.row', ['conversation' => '__ID__'])),
    ablyTokenUrl: @json(route('chats.ably-token')),
};</script>
<script src="https://cdn.ably.com/lib/ably.min-2.js" crossorigin="anonymous"></script>
<script src="{{ asset('js/chats-index.js') }}?v={{ filemtime(public_path('js/chats-index.js')) }}"></script>

@endsection
