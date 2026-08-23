@extends('layouts.app')

@section('content')
    @php
        $searchQuery = (string) ($q ?? $search ?? '');
        $connectionIdSet = array_flip($connectedUserIds ?? []);
    @endphp

    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/connections.css') }}?v={{ filemtime(public_path('css/connections.css')) }}">
    @endpush

    <div class="connections-page-container">
        {{-- Header Bar with Find Carpoolers Action Button --}}
        <div class="connections-header-bar">
            <div class="connections-header">
                <p class="pg-eyebrow">Your Network</p>
                <h1 class="pg-title">Connections</h1>
                <p class="pg-sub">Connect with trusted carpoolers to share trips, split fares, and travel together.</p>
            </div>
            <button type="button" class="btn-find-modal-open" id="openFindModalBtn">
                <i class="fa-solid fa-user-plus"></i>
                <span>Find Carpoolers</span>
            </button>
        </div>

        {{-- Segmented Navigation Tabs --}}
        <div class="conn-nav-tabs" role="tablist">
            <button type="button" class="conn-tab-btn {{ $searchQuery ? '' : 'is-active' }}" id="tab-btn-accepted" onclick="switchConnTab('accepted')">
                Connections &middot; {{ $acceptedConnections->count() }}
            </button>
            <button type="button" class="conn-tab-btn" id="tab-btn-incoming" onclick="switchConnTab('incoming')">
                Incoming &middot; {{ $incomingRequests->count() }}
            </button>
            <button type="button" class="conn-tab-btn" id="tab-btn-outgoing" onclick="switchConnTab('outgoing')">
                Outgoing &middot; {{ $outgoingRequests->count() }}
            </button>
        </div>

        {{-- Panels Container --}}
        <div class="conn-content">

            {{-- TAB 1: ACCEPTED CONNECTIONS (Horizontal Row List Layout) --}}
            <div class="conn-panel-card {{ $searchQuery ? '' : 'is-active' }}" id="panel-accepted">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-user-group"></i> My Connections</h3>
                    <span class="conn-count-label">{{ $acceptedConnections->count() }} members</span>
                </div>

                @if($acceptedConnections->isNotEmpty())
                    <div class="conn-list-container">
                        @foreach($acceptedConnections as $connectedUser)
                            @php
                                $photo = $connectedUser->profile_photo_url;
                                $cleanPhone = $connectedUser->showsPhoneTo(true)
                                    ? preg_replace('/\D+/', '', (string) $connectedUser->phone)
                                    : '';
                            @endphp
                            <div class="conn-item-row">
                                <div class="conn-avatar">
                                    @if($photo)
                                        <img src="{{ $photo }}" alt="{{ $connectedUser->name }}">
                                    @else
                                        <span>{{ strtoupper(substr($connectedUser->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="conn-user-details">
                                    <div class="conn-user-name-line">
                                        <span class="conn-user-name">{{ $connectedUser->name }}</span>
                                        <span class="role-pill {{ strtolower($connectedUser->role ?? 'passenger') }}">
                                            @if($connectedUser->role === 'driver')
                                                <i class="fa-solid fa-car"></i> Driver
                                            @else
                                                <i class="fa-solid fa-user"></i> Passenger
                                            @endif
                                        </span>
                                    </div>
                                    @if($connectedUser->showsEmailTo(true))
                                        <p class="conn-user-email">{{ $connectedUser->email }}</p>
                                    @endif
                                </div>
                                <div class="conn-actions-wrap">
                                    @if($cleanPhone)
                                        <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="btn-action-sm btn-action-wa" title="WhatsApp Chat">
                                            <i class="fa-brands fa-whatsapp"></i> Chat
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('connections.remove', $connectedUser->id) }}" onsubmit="return confirm('Remove {{ $connectedUser->name }} from connections?');" style="margin:0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action-sm btn-action-danger">
                                            <i class="fa-solid fa-user-minus"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty
                        icon="fa-solid fa-user-group"
                        title="No Connections Yet"
                        message="Click 'Find Carpoolers' above to search for members and build your network."
                    />
                @endif
            </div>

            {{-- TAB 2: INCOMING REQUESTS --}}
            <div class="conn-panel-card" id="panel-incoming">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-inbox"></i> Incoming Requests</h3>
                    <span class="conn-count-label">{{ $incomingRequests->count() }} pending</span>
                </div>

                @if($incomingRequests->isNotEmpty())
                    <div class="conn-list-container">
                        @foreach($incomingRequests as $req)
                            @php
                                $requester = $req->requester;
                                $reqPhoto = $requester?->profile_photo_url;
                                $showRequesterEmail = $requester && $requester->showsEmailTo(isset($connectionIdSet[$requester->id]));
                            @endphp
                            <div class="conn-item-row">
                                <div class="conn-avatar">
                                    @if($reqPhoto)
                                        <img src="{{ $reqPhoto }}" alt="{{ $requester?->name }}">
                                    @else
                                        <span>{{ strtoupper(substr($requester?->name ?? 'U', 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="conn-user-details">
                                    <div class="conn-user-name-line">
                                        <span class="conn-user-name">{{ $requester?->name }}</span>
                                        <span class="role-pill {{ strtolower($requester->role ?? 'passenger') }}">
                                            @if(($requester->role ?? '') === 'driver')
                                                <i class="fa-solid fa-car"></i> Driver
                                            @else
                                                <i class="fa-solid fa-user"></i> Passenger
                                            @endif
                                        </span>
                                    </div>
                                    <p class="conn-user-email">{{ $showRequesterEmail ? $requester->email . ' • ' : '' }}{{ $req->created_at?->diffForHumans() }}</p>
                                </div>
                                <div class="conn-actions-wrap">
                                    <form method="POST" action="{{ route('connections.respond', $req->id) }}" style="margin:0;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn-action-sm btn-action-accept">
                                            <i class="fa-solid fa-check"></i> Accept
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('connections.respond', $req->id) }}" style="margin:0;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-action-sm btn-action-decline">
                                            <i class="fa-solid fa-xmark"></i> Decline
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty
                        icon="fa-solid fa-inbox"
                        title="No Incoming Requests"
                        message="You don't have any pending connection requests right now."
                    />
                @endif
            </div>

            {{-- TAB 3: OUTGOING REQUESTS --}}
            <div class="conn-panel-card" id="panel-outgoing">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-paper-plane"></i> Outgoing Requests</h3>
                    <span class="conn-count-label">{{ $outgoingRequests->count() }} sent</span>
                </div>

                @if($outgoingRequests->isNotEmpty())
                    <div class="conn-list-container">
                        @foreach($outgoingRequests as $req)
                            @php
                                $receiver = $req->receiver;
                                $recPhoto = $receiver?->profile_photo_url;
                            @endphp
                            <div class="conn-item-row">
                                <div class="conn-avatar">
                                    @if($recPhoto)
                                        <img src="{{ $recPhoto }}" alt="{{ $receiver?->name }}">
                                    @else
                                        <span>{{ strtoupper(substr($receiver?->name ?? 'U', 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="conn-user-details">
                                    <div class="conn-user-name-line">
                                        <span class="conn-user-name">{{ $receiver?->name }}</span>
                                        <span class="role-pill {{ strtolower($receiver->role ?? 'passenger') }}">
                                            @if(($receiver->role ?? '') === 'driver')
                                                <i class="fa-solid fa-car"></i> Driver
                                            @else
                                                <i class="fa-solid fa-user"></i> Passenger
                                            @endif
                                        </span>
                                    </div>
                                    <p class="conn-user-email">Pending response • Sent {{ $req->created_at?->diffForHumans() }}</p>
                                </div>
                                <div class="conn-actions-wrap">
                                    <form method="POST" action="{{ route('connections.cancel', $req->id) }}" style="margin:0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action-sm btn-action-decline">
                                            <i class="fa-solid fa-ban"></i> Cancel Request
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty
                        icon="fa-solid fa-paper-plane"
                        title="No Outgoing Requests"
                        message="You haven't sent any pending connection requests."
                    />
                @endif
            </div>

        </div>
    </div>

    {{-- Modern Find & Add Carpoolers Modal Popup --}}
    <div class="find-modal-backdrop {{ ($searchQuery || request('open_modal')) ? 'show' : '' }}" id="findCarpoolersModal" aria-hidden="{{ ($searchQuery || request('open_modal')) ? 'false' : 'true' }}">
        <div class="find-modal-card" role="dialog" aria-modal="true">
            <div class="find-modal-head">
                <div>
                    <h3 class="find-modal-title">
                        <i class="fa-solid fa-user-plus" style="color: var(--ch-yellow-ink);"></i>
                        Find & Add Carpoolers
                    </h3>
                    <p class="find-modal-sub">Search members by name or email address to expand your trusted network.</p>
                </div>
                <button type="button" class="find-modal-close" id="closeFindModalBtn" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="find-modal-body">
                <form method="GET" action="{{ route('connections.index') }}" class="modal-search-form" id="modalSearchForm">
                    <input type="hidden" name="open_modal" value="1">
                    <div class="conn-search-input-wrap">
                        <span class="conn-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="q" value="{{ $searchQuery }}" id="modalSearchInput" class="conn-search-input" placeholder="Type name or email to search..." autocomplete="off">
                    </div>
                </form>

                <div class="modal-results-area" id="modalResultsArea">
                    @if($searchResults->isNotEmpty())
                        <div class="modal-results-list">
                            @foreach($searchResults as $foundUser)
                                @php
                                    $photo = $foundUser->profile_photo_url;
                                    $relStatus = $foundUser->relationship_status ?? 'none';
                                @endphp
                                <div class="modal-result-row">
                                    <div class="conn-avatar">
                                        @if($photo)
                                            <img src="{{ $photo }}" alt="{{ $foundUser->name }}">
                                        @else
                                            <span>{{ strtoupper(substr($foundUser->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="conn-user-details">
                                        <div class="conn-user-name-line">
                                            <span class="conn-user-name">{{ $foundUser->name }}</span>
                                            <span class="role-pill {{ strtolower($foundUser->role ?? 'passenger') }}">
                                                @if($foundUser->role === 'driver')
                                                    <i class="fa-solid fa-car"></i> Driver
                                                @else
                                                    <i class="fa-solid fa-user"></i> Passenger
                                                @endif
                                            </span>
                                        </div>
                                        @if($foundUser->showsEmailTo(isset($connectionIdSet[$foundUser->id])))
                                            <p class="conn-user-email">{{ $foundUser->email }}</p>
                                        @endif
                                    </div>
                                    <div class="conn-actions-wrap">
                                        @if($relStatus === 'accepted')
                                            <span class="badge-status-connected">
                                                <i class="fa-solid fa-check-double"></i> Connected
                                            </span>
                                        @elseif($relStatus === 'outgoing_pending')
                                            <span class="badge-status-pending">
                                                <i class="fa-solid fa-clock"></i> Sent
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('connections.requests.store') }}" class="add-connection-form" style="margin:0;">
                                                @csrf
                                                <input type="hidden" name="receiver_id" value="{{ $foundUser->id }}">
                                                <button type="submit" class="btn-action-sm btn-add-conn">
                                                    <i class="fa-solid fa-user-plus"></i> Add Connection
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($searchQuery !== '')
                        <div class="modal-empty-state">
                            <i class="fa-solid fa-user-slash"></i>
                            <p class="modal-empty-title">No Carpoolers Found</p>
                            <p class="modal-empty-sub">No members matched "<strong>{{ $searchQuery }}</strong>". Try another name or email.</p>
                        </div>
                    @else
                        <div class="modal-empty-state">
                            <i class="fa-solid fa-users-viewfinder"></i>
                            <p class="modal-empty-title">Search for Carpoolers</p>
                            <p class="modal-empty-sub">Type a name or email address above to search and send connection requests.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/connections.js') }}?v={{ filemtime(public_path('js/connections.js')) }}"></script>
@endsection
