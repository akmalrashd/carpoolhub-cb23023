@extends('layouts.app')

@section('content')
    @php
        $searchQuery = (string) ($q ?? $search ?? '');
    @endphp

    <style>
        /* ── Centered Container ── */
        .connections-page-container {
            max-width: 780px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-sizing: border-box;
        }

        /* ── Page Header ── */
        .connections-header {
            margin-bottom: 2px;
        }
        .pg-eyebrow {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            font-family: var(--font-ui), sans-serif;
            margin: 0 0 4px;
        }
        .pg-title {
            margin: 0 0 4px;
            font-family: var(--font-display), sans-serif;
            font-size: clamp(1.4rem, 2.2vw, 1.75rem);
            font-weight: 800;
            color: var(--ink);
            line-height: 1.1;
        }
        .pg-sub {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        /* ── Search Bar Card ── */
        .conn-search-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-xl);
            padding: 16px;
            box-shadow: var(--shadow-1);
            width: 100%;
            box-sizing: border-box;
        }
        .conn-search-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .conn-search-input-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            background: var(--surface-2);
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-md);
            overflow: hidden;
            transition: border-color .16s ease, box-shadow .16s ease;
        }
        .conn-search-input-wrap:focus-within {
            border-color: var(--ch-yellow);
            box-shadow: 0 0 0 3px var(--ch-yellow-tint);
            background: var(--surface);
        }
        .conn-search-icon {
            padding: 0 12px;
            color: var(--muted);
            font-size: 14px;
        }
        .conn-search-input {
            flex: 1;
            border: 0;
            background: transparent;
            color: var(--ink);
            padding: 10px 12px 10px 0;
            font-size: 14px;
            font-family: var(--font-ui), sans-serif;
            outline: none;
        }
        .btn-search-submit {
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
            border: 1px solid var(--ch-yellow-line);
            border-radius: var(--r-md);
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 800;
            font-family: var(--font-display), sans-serif;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: transform .15s ease, background .15s ease;
        }
        .btn-search-submit:hover {
            background: var(--ch-yellow-deep);
            transform: translateY(-1px);
        }

        /* ── Navigation Tabs ── */
        .conn-nav-tabs {
            display: flex;
            width: 100%;
            max-width: 100%;
            gap: 4px;
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            border-radius: 14px;
            padding: 4px;
            box-shadow: none;
            box-sizing: border-box;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .conn-nav-tabs::-webkit-scrollbar { display: none; }

        .conn-tab-btn {
            flex: 1 0 auto;
            min-width: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 12px;
            border-radius: 10px; /* Rounded Rectangle */
            border: 1px solid transparent;
            background: transparent;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            font-family: var(--font-ui), sans-serif;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
            text-align: center;
        }
        @media (max-width: 520px) {
            .conn-tab-btn {
                font-size: 11px;
                padding: 8px 6px;
                gap: 4px;
            }
        }
        .conn-tab-btn:hover {
            color: var(--ink);
        }
        .conn-tab-btn.is-active {
            background: var(--surface);
            border-color: var(--hairline);
            color: var(--ink);
            box-shadow: var(--shadow-1);
            font-weight: 800;
        }
        .tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            background: var(--canvas);
            color: var(--muted);
        }
        .conn-tab-btn.is-active .tab-badge {
            background: var(--ch-yellow-tint);
            color: var(--warning-ink);
        }
        .tab-badge.highlight {
            background: #ef4444;
            color: #ffffff !important;
        }

        /* ── Content Panels ── */
        .conn-content {
            width: 100%;
            box-sizing: border-box;
        }
        .conn-panel-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-xl);
            padding: 20px;
            box-shadow: var(--shadow-1);
            display: none;
            width: 100%;
            box-sizing: border-box;
            animation: fadeInTab .22s ease-out;
        }
        .conn-panel-card.is-active {
            display: block;
        }

        @keyframes fadeInTab {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .panel-head {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--hairline);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .panel-title {
            font-family: var(--font-display), sans-serif;
            font-size: 17px;
            font-weight: 800;
            color: var(--ink);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Cards Grid ── */
        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 14px;
        }
        .member-card {
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            position: relative;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .member-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-2);
            border-color: var(--ch-yellow-line);
            background: var(--surface);
        }
        .member-avatar {
            width: 56px;
            height: 56px;
            border-radius: 999px;
            background: var(--canvas);
            border: 2px solid var(--hairline-strong);
            color: var(--ink);
            display: grid;
            place-items: center;
            font-size: 22px;
            font-weight: 800;
            font-family: var(--font-display), sans-serif;
            overflow: hidden;
            flex-shrink: 0;
        }
        .member-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .member-info {
            width: 100%;
            min-width: 0;
        }
        .member-name {
            font-family: var(--font-display), sans-serif;
            font-size: 15px;
            font-weight: 800;
            color: var(--ink);
            margin: 0 0 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .member-email {
            font-size: 12px;
            color: var(--muted);
            margin: 0 0 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: capitalize;
        }
        .role-pill.driver {
            background: var(--ch-yellow-tint);
            color: var(--warning-ink);
            border: 1px solid var(--ch-yellow-line);
        }
        .role-pill.passenger {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .member-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            margin-top: 4px;
        }
        .btn-action-sm {
            padding: 7px 12px;
            border-radius: var(--r-md);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all .15s ease;
        }
        .btn-action-wa {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }
        .btn-action-wa:hover {
            background: #bbf7d0;
        }
        .btn-action-danger {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        .btn-action-danger:hover {
            background: #fee2e2;
        }

        /* ── Requests List Items ── */
        .req-list-items {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .req-item-row {
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background .15s ease;
        }
        .req-item-row:hover {
            background: var(--surface);
            border-color: var(--hairline-strong);
        }
        .req-avatar {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            background: var(--canvas);
            border: 1px solid var(--hairline-strong);
            color: var(--ink);
            display: grid;
            place-items: center;
            font-size: 18px;
            font-weight: 800;
            flex-shrink: 0;
        }
        .req-details {
            flex: 1;
            min-width: 0;
        }
        .req-user-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
            margin: 0 0 2px;
        }
        .req-user-email {
            font-size: 12px;
            color: var(--muted);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Alert Toast ── */
        .conn-alert {
            padding: 12px 16px;
            border-radius: var(--r-md);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .conn-alert.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
        }
        .conn-alert.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
    </style>

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

    <script>
        function switchConnTab(tabName) {
            const tabs = ['accepted', 'incoming', 'outgoing', 'search'];
            if (!tabs.includes(tabName)) return;

            tabs.forEach(t => {
                const btn = document.getElementById(`tab-btn-${t}`);
                const panel = document.getElementById(`panel-${t}`);
                if (btn && panel) {
                    if (t === tabName) {
                        btn.classList.add('is-active');
                        panel.classList.add('is-active');
                    } else {
                        btn.classList.remove('is-active');
                        panel.classList.remove('is-active');
                    }
                }
            });

            if (history.pushState) {
                history.pushState(null, null, `#${tabName}`);
            } else {
                location.hash = `#${tabName}`;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const hash = location.hash.replace('#', '');
            if (['accepted', 'incoming', 'outgoing', 'search'].includes(hash)) {
                switchConnTab(hash);
            }
        });
    </script>
@endsection
