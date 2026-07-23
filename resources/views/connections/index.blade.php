@extends('layouts.app')

@section('content')
    @php
        $searchQuery = (string) ($q ?? $search ?? '');
    @endphp

    {{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
    <link rel="stylesheet" href="{{ asset('css/connections.css') }}?v={{ filemtime(public_path('css/connections.css')) }}">

    <div class="connections-page-container">
        {{-- Header --}}
        <div class="connections-header">
            <p class="pg-eyebrow">Your Network</p>
            <h1 class="pg-title">Connections</h1>
            <p class="pg-sub">Connect with trusted carpoolers to share trips, split fares, and travel together.</p>
        </div>

        {{-- Status Notifications --}}
        @if(session('status'))
            <div class="conn-alert success">
                <i class="fa-solid fa-circle-check" style="font-size:16px;"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="conn-alert error">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:16px;"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Search Bar Card --}}
        <div class="conn-search-card">
            <form method="GET" action="{{ route('connections.index') }}" class="conn-search-form">
                <div class="conn-search-input-wrap">
                    <span class="conn-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="q" value="{{ $searchQuery }}" class="conn-search-input" placeholder="Search carpoolers by name or email...">
                </div>
                <button type="submit" class="btn-search-submit">
                    <i class="fa-solid fa-user-plus"></i> Find
                </button>
            </form>
        </div>

        {{-- Segmented Navigation Tabs --}}
        <div class="conn-nav-tabs" role="tablist">
            <button type="button" class="conn-tab-btn {{ $searchQuery ? '' : 'is-active' }}" id="tab-btn-accepted" onclick="switchConnTab('accepted')">
                <i class="fa-solid fa-user-group"></i>
                <span>Connections</span>
                <span class="tab-badge">{{ $acceptedConnections->count() }}</span>
            </button>
            <button type="button" class="conn-tab-btn" id="tab-btn-incoming" onclick="switchConnTab('incoming')">
                <i class="fa-solid fa-inbox"></i>
                <span>Incoming</span>
                <span class="tab-badge {{ $incomingRequests->count() > 0 ? 'highlight' : '' }}">{{ $incomingRequests->count() }}</span>
            </button>
            <button type="button" class="conn-tab-btn" id="tab-btn-outgoing" onclick="switchConnTab('outgoing')">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Outgoing</span>
                <span class="tab-badge">{{ $outgoingRequests->count() }}</span>
            </button>
            <button type="button" class="conn-tab-btn {{ $searchQuery ? 'is-active' : '' }}" id="tab-btn-search" onclick="switchConnTab('search')">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Results</span>
                @if($searchResults->isNotEmpty())
                    <span class="tab-badge highlight">{{ $searchResults->count() }}</span>
                @endif
            </button>
        </div>

        {{-- Panels Container --}}
        <div class="conn-content">

            {{-- ─────────────────────────────────────────────────────────────
                 TAB 1: ACCEPTED CONNECTIONS
            ─────────────────────────────────────────────────────────────── --}}
            <div class="conn-panel-card {{ $searchQuery ? '' : 'is-active' }}" id="panel-accepted">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-user-group"></i> My Connections</h3>
                </div>

                @if($acceptedConnections->isNotEmpty())
                    <div class="members-grid">
                        @foreach($acceptedConnections as $connectedUser)
                            @php
                                $photo = $connectedUser->profile_photo ? \Illuminate\Support\Facades\Storage::disk('public')->url($connectedUser->profile_photo) : null;
                                $cleanPhone = preg_replace('/\D+/', '', (string) $connectedUser->phone);
                            @endphp
                            <div class="member-card">
                                <div class="member-avatar">
                                    @if($photo)
                                        <img src="{{ $photo }}" alt="{{ $connectedUser->name }}">
                                    @else
                                        <span>{{ strtoupper(substr($connectedUser->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="member-info">
                                    <h4 class="member-name">{{ $connectedUser->name }}</h4>
                                    <p class="member-email">{{ $connectedUser->email }}</p>
                                    <span class="role-pill {{ strtolower($connectedUser->role ?? 'passenger') }}">
                                        @if($connectedUser->role === 'driver')
                                            <i class="fa-solid fa-car"></i> Driver
                                        @else
                                            <i class="fa-solid fa-user"></i> Passenger
                                        @endif
                                    </span>
                                </div>
                                <div class="member-actions">
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
                        message="Use the search bar above to find carpoolers and add them to your network."
                    />
                @endif
            </div>

            {{-- ─────────────────────────────────────────────────────────────
                 TAB 2: INCOMING REQUESTS
            ─────────────────────────────────────────────────────────────── --}}
            <div class="conn-panel-card" id="panel-incoming">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-inbox"></i> Incoming Requests</h3>
                </div>

                @if($incomingRequests->isNotEmpty())
                    <div class="req-list-items">
                        @foreach($incomingRequests as $req)
                            @php
                                $requester = $req->requester;
                                $reqPhoto = $requester?->profile_photo ? \Illuminate\Support\Facades\Storage::disk('public')->url($requester->profile_photo) : null;
                            @endphp
                            <div class="req-item-row">
                                <div class="req-avatar">
                                    @if($reqPhoto)
                                        <img src="{{ $reqPhoto }}" alt="{{ $requester?->name }}" style="width:100%; height:100%; border-radius:999px; object-fit:cover;">
                                    @else
                                        <span>{{ strtoupper(substr($requester?->name ?? 'U', 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="req-details">
                                    <h4 class="req-user-name">{{ $requester?->name }}</h4>
                                    <p class="req-user-email">{{ $requester?->email }} • {{ $req->created_at?->diffForHumans() }}</p>
                                </div>
                                <div style="display:flex; gap:6px;">
                                    <form method="POST" action="{{ route('connections.respond', $req->id) }}" style="margin:0;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-primary btn-xs" style="background:var(--ch-yellow); color:var(--ch-yellow-ink); border:1px solid var(--ch-yellow-line); font-weight:800;">
                                            <i class="fa-solid fa-check"></i> Accept
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('connections.respond', $req->id) }}" style="margin:0;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-ghost btn-xs" style="color:var(--danger-ink);">
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

            {{-- ─────────────────────────────────────────────────────────────
                 TAB 3: OUTGOING REQUESTS
            ─────────────────────────────────────────────────────────────── --}}
            <div class="conn-panel-card" id="panel-outgoing">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-paper-plane"></i> Outgoing Requests</h3>
                </div>

                @if($outgoingRequests->isNotEmpty())
                    <div class="req-list-items">
                        @foreach($outgoingRequests as $req)
                            @php
                                $receiver = $req->receiver;
                                $recPhoto = $receiver?->profile_photo ? \Illuminate\Support\Facades\Storage::disk('public')->url($receiver->profile_photo) : null;
                            @endphp
                            <div class="req-item-row">
                                <div class="req-avatar">
                                    @if($recPhoto)
                                        <img src="{{ $recPhoto }}" alt="{{ $receiver?->name }}" style="width:100%; height:100%; border-radius:999px; object-fit:cover;">
                                    @else
                                        <span>{{ strtoupper(substr($receiver?->name ?? 'U', 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="req-details">
                                    <h4 class="req-user-name">{{ $receiver?->name }}</h4>
                                    <p class="req-user-email">Pending response • Sent {{ $req->created_at?->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('connections.respond', $req->id) }}" style="margin:0;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-ghost btn-xs" style="color:var(--muted);">
                                        <i class="fa-solid fa-ban"></i> Cancel Request
                                    </button>
                                </form>
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

            {{-- ─────────────────────────────────────────────────────────────
                 TAB 4: SEARCH RESULTS
            ─────────────────────────────────────────────────────────────── --}}
            <div class="conn-panel-card {{ $searchQuery ? 'is-active' : '' }}" id="panel-search">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa-solid fa-magnifying-glass"></i> Search Results</h3>
                    @if($searchQuery)
                        <span style="font-size:12px; color:var(--muted);">Query: "<strong>{{ $searchQuery }}</strong>"</span>
                    @endif
                </div>

                @if($searchResults->isNotEmpty())
                    <div class="members-grid">
                        @foreach($searchResults as $foundUser)
                            @php
                                $photo = $foundUser->profile_photo ? \Illuminate\Support\Facades\Storage::disk('public')->url($foundUser->profile_photo) : null;
                                $relStatus = $foundUser->relationship_status ?? 'none';
                            @endphp
                            <div class="member-card">
                                <div class="member-avatar">
                                    @if($photo)
                                        <img src="{{ $photo }}" alt="{{ $foundUser->name }}">
                                    @else
                                        <span>{{ strtoupper(substr($foundUser->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="member-info">
                                    <h4 class="member-name">{{ $foundUser->name }}</h4>
                                    <p class="member-email">{{ $foundUser->email }}</p>
                                    <span class="role-pill {{ strtolower($foundUser->role ?? 'passenger') }}">
                                        @if($foundUser->role === 'driver')
                                            <i class="fa-solid fa-car"></i> Driver
                                        @else
                                            <i class="fa-solid fa-user"></i> Passenger
                                        @endif
                                    </span>
                                </div>
                                <div class="member-actions">
                                    @if($relStatus === 'accepted')
                                        <span class="btn-action-sm" style="background:#f0fdf4; color:#15803d; border-color:#bbf7d0;">
                                            <i class="fa-solid fa-check-double"></i> Connected
                                        </span>
                                    @elseif($relStatus === 'outgoing_pending')
                                        <span class="btn-action-sm" style="background:#fefce8; color:#a16207; border-color:#fef08a;">
                                            <i class="fa-solid fa-clock"></i> Request Sent
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('connections.requests.store') }}" style="margin:0; width:100%;">
                                            @csrf
                                            <input type="hidden" name="receiver_id" value="{{ $foundUser->id }}">
                                            <button type="submit" class="btn-action-sm" style="background:var(--ch-yellow); color:var(--ch-yellow-ink); border-color:var(--ch-yellow-line); width:100%; justify-content:center; font-weight:800;">
                                                <i class="fa-solid fa-user-plus"></i> Add Connection
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @if($searchQuery)
                        <x-empty
                            icon="fa-solid fa-user-slash"
                            title="No Users Found"
                            message="No carpoolers matched '{{ $searchQuery }}'. Check spelling or try a different search term."
                        />
                    @else
                        <x-empty
                            icon="fa-solid fa-magnifying-glass"
                            title="Search for Carpoolers"
                            message="Type a name or email address in the search box above to find members and grow your network."
                        />
                    @endif
                @endif
            </div>

        </div>
    </div>

    <script src="{{ asset('js/connections.js') }}?v={{ filemtime(public_path('js/connections.js')) }}"></script>
@endsection
