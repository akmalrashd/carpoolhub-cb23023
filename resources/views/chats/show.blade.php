@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/chats.css') }}?v={{ filemtime(public_path('css/chats.css')) }}">
@if($tripModalData)
    {{-- Trip Details popup reuse — same modal/CSS/JS as trips/index.blade.php. --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="{{ asset('css/trips.css') }}?v={{ filemtime(public_path('css/trips.css')) }}">
@endif
@endpush

@php
    $me = auth()->user();
    $others = $conversation->participants->filter(fn ($p) => $p->user_id !== $me->id)->values();
    $memberCount = $conversation->participants->count();
    $othersExtra = max(0, $others->count() - 3);
    $subParts = array_filter([
        $conversation->trip_ref_snapshot,
        $memberCount.' '.\Illuminate\Support\Str::plural('member', $memberCount),
    ]);
    $pickerOptionsUrl = $conversation->trip_id ? route('trips.chat.picker-options', $conversation->trip_id) : null;
    $inviteUrl = $conversation->trip_id ? route('trips.chat.invite', $conversation->trip_id) : null;
@endphp

<div class="chat-thread-page">
    <div class="chat-thread-header" id="chatThreadHead">
        <a href="{{ route('chats.index') }}" class="chat-thread-back" aria-label="Back to chats">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <button
            type="button"
            class="chat-thread-info {{ $tripModalData ? 'open-trip-modal-btn' : '' }}"
            @if(! $tripModalData) disabled @endif
            @if($tripModalData)
                data-trip-id="{{ $tripModalData['tripId'] }}"
                data-trip-ref="{{ $tripModalData['tripRef'] }}"
                data-route-name="{{ $tripModalData['routeName'] }}"
                data-driver-name="{{ $tripModalData['driverName'] }}"
                data-driver-id="{{ $tripModalData['driverId'] }}"
                data-driver-photo="{{ $tripModalData['driverPhoto'] }}"
                data-driver-email="{{ $tripModalData['driverEmail'] }}"
                data-driver-whatsapp-url="{{ $tripModalData['driverWhatsappUrl'] }}"
                data-driver-phone="{{ $tripModalData['driverPhone'] }}"
                data-mode="{{ $tripModalData['mode'] }}"
                data-status="{{ $tripModalData['status'] }}"
                data-outbound-datetime="{{ $tripModalData['outboundDatetime'] }}"
                data-fare-label="{{ $tripModalData['fareLabel'] }}"
                data-fare-display="{{ $tripModalData['fareDisplay'] }}"
                data-pickup-name="{{ $tripModalData['pickupName'] }}"
                data-pickup-lat="{{ $tripModalData['pickupLat'] }}"
                data-pickup-lng="{{ $tripModalData['pickupLng'] }}"
                data-destination-name="{{ $tripModalData['destinationName'] }}"
                data-destination-lat="{{ $tripModalData['destinationLat'] }}"
                data-destination-lng="{{ $tripModalData['destinationLng'] }}"
                data-total-passengers="{{ $tripModalData['totalPassengers'] }}"
                data-split-type="{{ $tripModalData['splitType'] }}"
                data-participants-b64="{{ $tripModalData['participantsB64'] }}"
                data-route-points-b64="{{ $tripModalData['routePointsB64'] }}"
                data-can-manage="{{ $tripModalData['canManage'] }}"
                data-can-delete="{{ $tripModalData['canDelete'] }}"
                data-edit-url="{{ $tripModalData['editUrl'] }}"
                data-delete-url="{{ $tripModalData['deleteUrl'] }}"
            @endif
        >
            <span class="chat-thread-avatars">
                @forelse($others->take(3) as $participant)
                    <x-avatar :user="$participant->user" size="sm" class="chat-thread-avatar" />
                @empty
                    <x-avatar :user="$conversation->driver" size="sm" class="chat-thread-avatar" />
                @endforelse
                @if($othersExtra > 0)
                    <span class="chat-thread-avatar chat-thread-avatar-badge">+{{ $othersExtra }}</span>
                @endif
            </span>
            <span class="chat-thread-headtext">
                <span class="chat-thread-title">{{ $conversation->route_snapshot ?: 'Trip chat' }}</span>
                <span class="chat-thread-sub">{{ implode(' · ', $subParts) }}</span>
            </span>
        </button>
        @if($isChatAdmin && $conversation->isPrivate())
            <button type="button" class="chat-thread-invite-btn" id="chatInviteBtn" aria-label="Invite connections" title="Invite connections">
                <i class="fa-solid fa-user-plus"></i>
            </button>
        @endif
    </div>

    @if($conversation->scheduled_purge_at)
        <div class="chat-thread-banner">
            <i class="fa-solid fa-hourglass-half"></i>
            This chat closes {{ $conversation->scheduled_purge_at->diffForHumans() }}.
        </div>
    @endif

    <div class="chat-thread-messages" id="chatMessages">
        @foreach($messages as $message)
            @php $isOwn = $message->sender_id === $me->id; @endphp
            @if($message->isSystem())
                <div class="chat-bubble-row is-system">
                    <span class="chat-bubble-system">{{ $message->body }}</span>
                </div>
            @else
                <div class="chat-bubble-row {{ $isOwn ? 'is-own' : '' }}">
                    @unless($isOwn)
                        <x-avatar :user="$message->sender" size="sm" class="chat-bubble-avatar" />
                    @endunless
                    <div class="chat-bubble-col">
                        @if(! $isOwn && $memberCount > 2)
                            <span class="chat-bubble-sender">{{ $message->sender?->name ?? 'Deleted user' }}</span>
                        @endif
                        <div class="chat-bubble">{{ $message->body }}</div>
                        <span class="chat-bubble-time">{{ $message->created_at?->format('g:i A') }}</span>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @if($isOpen)
        <form id="chatComposerForm" class="chat-composer">
            <textarea id="chatComposerInput" class="chat-composer-input" rows="1" maxlength="2000" placeholder="Type a message..." required></textarea>
            <button type="submit" class="chat-composer-send" id="chatComposerSend" aria-label="Send">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    @else
        <div class="chat-composer-locked">
            <i class="fa-regular fa-clock"></i>
            This chat opens {{ $conversation->opens_at?->diffForHumans() }}.
        </div>
    @endif
</div>

@if($tripModalData)
    @include('trips.partials.trip-details-modal')
@endif

<script>window.CH_CHAT = {
    csrf: @json(csrf_token()),
    conversationId: @json($conversation->id),
    myUserId: @json($me->id),
    lastMessageId: @json($messages->last()?->id ?? 0),
    sendUrl: @json(route('chats.messages.store', $conversation)),
    pollUrl: @json(route('refresh.chats.messages', $conversation)),
    ablyTokenUrl: @json(route('chats.ably-token')),
    readUrl: @json(route('chats.read', $conversation)),
    pickerOptionsUrl: @json($pickerOptionsUrl),
    inviteUrl: @json($inviteUrl),
};</script>
<script src="https://cdn.ably.com/lib/ably.min-2.js" crossorigin="anonymous"></script>
<script src="{{ asset('js/chats-show.js') }}?v={{ filemtime(public_path('js/chats-show.js')) }}"></script>
@if($tripModalData)
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="{{ asset('js/trip-details-modal.js') }}?v={{ filemtime(public_path('js/trip-details-modal.js')) }}"></script>
@endif

@endsection
