@extends('layouts.app')

@section('content')
    <style>
        /* ── Page header ── */
        .pg-eyebrow {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            font-family: var(--font-ui), sans-serif;
            margin: 0 0 4px;
        }

        .pg-title {
            margin: 0 0 2px;
            font-family: var(--font-display), sans-serif;
            font-size: clamp(1.4rem, 2.2vw, 1.75rem);
            font-weight: 700;
            color: var(--ink);
            line-height: 1.1;
        }

        .pg-sub {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .pg-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .pg-header-actions {
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            flex-shrink: 0;
        }

        /* ── Page shell ── */
        .conn-page {
            display: grid;
            gap: 14px;
        }

        /* ── Generic section card ── */
        .conn-card {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 16px 18px;
            box-shadow: var(--shadow-1);
        }

        .conn-section-title {
            margin: 0 0 12px;
            font-size: 16px;
            font-weight: 800;
            color: var(--ink);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .conn-section-title i {
            color: var(--muted);
            font-size: 15px;
        }

        /* ── Count chips in header ── */
        .conn-chips {
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .conn-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-pill);
            padding: 5px 11px;
            background: var(--surface-2);
            color: var(--ink-3);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .conn-chip i { color: var(--muted); }

        /* ── Search row ── */
        .conn-search-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: stretch;
        }

        .conn-search-input {
            width: 100%;
            border-radius: var(--r-sm);
            border: 1px solid var(--hairline-strong);
            background: var(--canvas);
            color: var(--ink);
            padding: 9px 13px;
            font-size: 14px;
            font-family: var(--font-ui), sans-serif;
            outline: none;
            transition: border-color .18s, background .18s;
        }

        .conn-search-input::placeholder { color: var(--muted-2); }

        .conn-search-input:focus {
            border-color: var(--ch-yellow-line);
            background: var(--surface);
            box-shadow: 0 0 0 3px rgba(250,204,21,.18);
        }

        /* ── Hint / error ── */
        .conn-hint {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .conn-form-error {
            margin-top: 8px;
            color: var(--danger);
            border: 1px solid rgba(220,38,38,.25);
            background: var(--danger-soft);
            border-radius: var(--r-sm);
            padding: 8px 12px;
            font-size: 13px;
        }

        /* ── Search results table ── */
        .conn-table-wrap {
            margin-top: 14px;
            overflow-x: auto;
        }

        .conn-table {
            width: 100%;
            border-collapse: collapse;
        }

        .conn-table th,
        .conn-table td {
            padding: 11px 10px;
            border-bottom: 1px solid var(--hairline);
            text-align: left;
            vertical-align: middle;
        }

        .conn-table th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
            white-space: nowrap;
            background: transparent;
        }

        .conn-table td:last-child,
        .conn-table th:last-child { text-align: right; }

        .conn-table tbody tr { transition: background .15s; }

        .conn-table tbody tr:hover td {
            background: var(--ch-yellow-tint);
        }

        .conn-table tbody tr:last-child td { border-bottom: 0; }

        .conn-table form {
            margin: 0;
            display: inline-flex;
            justify-content: flex-end;
        }

        /* ── Person cell ── */
        .person-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .person-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--r-pill);
            border: 2px solid var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            color: var(--ch-yellow-ink);
            display: grid;
            place-items: center;
            font-size: 15px;
            font-weight: 800;
            flex: 0 0 auto;
            text-transform: uppercase;
        }

        .person-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.25;
        }

        .person-sub {
            font-size: 12px;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 320px;
            display: block;
        }

        /* ── Status chips ── */
        .rel-chip {
            display: inline-flex;
            align-items: center;
            border-radius: var(--r-pill);
            border: 1px solid var(--hairline-strong);
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .rel-accepted         { color: var(--success-ink); border-color: #86efac; background: var(--success-soft); }
        .rel-outgoing_pending { color: var(--warning-ink); border-color: #fde68a; background: var(--warning-soft); }
        .rel-incoming_pending { color: var(--info-ink);    border-color: #bfdbfe; background: var(--info-soft); }
        .rel-rejected_by_you,
        .rel-rejected_you,
        .rel-blocked          { color: var(--danger-ink);  border-color: #fecaca; background: var(--danger-soft); }
        .rel-none             { color: var(--ink-3);       border-color: var(--hairline-strong); background: var(--surface-2); }

        /* ── Panel grid (incoming / outgoing) ── */
        .conn-panel-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr;
        }

        @media (min-width: 900px) {
            .conn-panel-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* ── Request list ── */
        .req-list {
            display: grid;
            gap: 9px;
            margin-top: 4px;
        }

        .req-item {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 12px 14px;
            background: var(--surface-2);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .req-avatar {
            width: 38px;
            height: 38px;
            border-radius: var(--r-pill);
            border: 2px solid var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            color: var(--ch-yellow-ink);
            display: grid;
            place-items: center;
            font-size: 14px;
            font-weight: 800;
            flex: 0 0 auto;
            text-transform: uppercase;
        }

        .req-info { flex: 1 1 0; min-width: 0; }

        .req-name {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.25;
        }

        .req-email {
            margin: 2px 0 0;
            font-size: 12px;
            color: var(--muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .req-actions {
            display: inline-flex;
            gap: 7px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ── Accepted connections card grid ── */
        .accept-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            margin-top: 4px;
        }

        @media (min-width: 640px)  { .accept-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .accept-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }

        .accept-card {
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 16px;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            text-align: center;
            transition: box-shadow .18s, transform .18s;
        }

        .accept-card:hover {
            box-shadow: var(--shadow-2);
            transform: translateY(-2px);
        }

        .accept-avatar {
            width: 52px;
            height: 52px;
            border-radius: var(--r-pill);
            border: 2px solid var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            color: var(--ch-yellow-ink);
            display: grid;
            place-items: center;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .accept-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.25;
        }

        .accept-email {
            font-size: 12px;
            color: var(--muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            border-radius: var(--r-pill);
            border: 1px solid var(--hairline-strong);
            padding: 3px 9px;
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-3);
            background: var(--canvas);
        }

        .accept-card form { margin: 0; width: 100%; }

        /* ── Empty state ── */
        .conn-empty {
            padding: 28px 0 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            text-align: center;
            color: var(--muted);
        }

        .conn-empty-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--r-pill);
            background: var(--canvas);
            display: grid;
            place-items: center;
            font-size: 24px;
            color: var(--muted-2);
        }

        .conn-empty-text {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
        }

        /* ── Responsive: mobile table ── */
        @media (max-width: 640px) {
            .conn-search-row {
                grid-template-columns: 1fr;
            }

            .conn-table,
            .conn-table tbody,
            .conn-table tr,
            .conn-table td { display: block; width: 100%; }

            .conn-table thead { display: none; }

            .conn-table tr {
                border: 1px solid var(--hairline);
                border-radius: var(--r-md);
                background: var(--surface);
                padding: 10px 12px;
                margin-bottom: 10px;
            }

            .conn-table td {
                border: 0 !important;
                padding: 6px 0;
                text-align: left !important;
            }

            .conn-table td::before {
                content: attr(data-label);
                display: block;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: var(--muted);
                margin-bottom: 2px;
            }

            .conn-table td:first-child::before { display: none; }

            .conn-table td[data-label="Actions"],
            .conn-table td[data-label="Actions"] form,
            .conn-table td[data-label="Actions"] .btn { width: 100%; }
        }
    </style>

    {{-- ── Page header ── --}}
    <div class="pg-header">
        <div>
            <p class="pg-eyebrow">Your network</p>
            <h1 class="pg-title">Connections</h1>
            <p class="pg-sub">Trusted carpoolers you ride with.</p>
        </div>
        <div class="pg-header-actions">
            <div class="conn-chips">
                <span class="conn-chip"><i class="fa-solid fa-inbox"></i> Incoming <strong>{{ $incomingRequests->count() }}</strong></span>
                <span class="conn-chip"><i class="fa-regular fa-paper-plane"></i> Outgoing <strong>{{ $outgoingRequests->count() }}</strong></span>
                <span class="conn-chip"><i class="fa-solid fa-user-check"></i> Accepted <strong>{{ $acceptedConnections->count() }}</strong></span>
            </div>
        </div>
    </div>

    <div class="conn-page">

        {{-- ── Search ── --}}
        <section class="conn-card">
            <h2 class="conn-section-title"><i class="fa-solid fa-magnifying-glass"></i> Find Users</h2>

            <form method="GET" action="{{ route('connections.index') }}" class="conn-search-row">
                <input
                    class="conn-search-input"
                    type="text"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search by name or email…"
                    autocomplete="off"
                >
                <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
            </form>

            @if($errors->has('receiver_id'))
                <div class="conn-form-error">{{ $errors->first('receiver_id') }}</div>
            @endif

            @if($searchResults->isNotEmpty())
                <div class="conn-table-wrap">
                    <table class="conn-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($searchResults as $user)
                                @php
                                    $statusMap = [
                                        'accepted'         => 'Connected',
                                        'outgoing_pending' => 'Pending (Outgoing)',
                                        'incoming_pending' => 'Pending (Incoming)',
                                        'rejected_by_you'  => 'Rejected by You',
                                        'rejected_you'     => 'Rejected by Them',
                                        'blocked'          => 'Blocked',
                                        'none'             => 'Not Connected',
                                    ];
                                    $statusLabel = $statusMap[$user->relationship_status] ?? 'Unknown';
                                @endphp
                                <tr>
                                    <td data-label="Name">
                                        <div class="person-cell">
                                            <span class="person-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                            <div>
                                                <span class="person-name">{{ $user->name }}</span>
                                                <span class="person-sub">{{ ucfirst($user->role) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Email">
                                        <span class="person-sub" style="max-width:260px;">{{ $user->email }}</span>
                                    </td>
                                    <td data-label="Status">
                                        <span class="rel-chip rel-{{ $user->relationship_status }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td data-label="Actions">
                                        @if(in_array($user->relationship_status, ['none', 'rejected_by_you', 'rejected_you'], true))
                                            <form method="POST" action="{{ route('connections.requests.store') }}">
                                                @csrf
                                                <input type="hidden" name="receiver_id" value="{{ $user->id }}">
                                                <button type="submit" class="btn btn-dark btn-sm">
                                                    <i class="fa-solid fa-user-plus"></i> Send Request
                                                </button>
                                            </form>
                                        @elseif($user->relationship_status === 'incoming_pending')
                                            <span style="font-size:12px; color:var(--muted); font-weight:600;">Respond below</span>
                                        @else
                                            <span style="color:var(--muted-2);">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif($q !== '')
                <p class="conn-hint">No users found for "<strong>{{ $q }}</strong>".</p>
            @endif
        </section>

        {{-- ── Incoming / Outgoing ── --}}
        <div class="conn-panel-grid">

            {{-- Incoming --}}
            <section class="conn-card">
                <h2 class="conn-section-title"><i class="fa-solid fa-inbox"></i> Incoming Requests</h2>

                @if($incomingRequests->isEmpty())
                    <div class="conn-empty">
                        <div class="conn-empty-icon"><i class="fa-solid fa-inbox"></i></div>
                        <p class="conn-empty-text">No incoming requests.</p>
                    </div>
                @else
                    <div class="req-list">
                        @foreach($incomingRequests as $connection)
                            <article class="req-item">
                                <span class="req-avatar">{{ strtoupper(substr($connection->requester->name, 0, 1)) }}</span>
                                <div class="req-info">
                                    <p class="req-name">{{ $connection->requester->name }}</p>
                                    <p class="req-email">{{ $connection->requester->email }}</p>
                                </div>
                                <div class="req-actions">
                                    <form method="POST" action="{{ route('connections.respond', $connection) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-soft btn-sm" style="background:var(--success-soft); border-color:#86efac; color:var(--success-ink);">
                                            <i class="fa-solid fa-check"></i> Accept
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('connections.respond', $connection) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Outgoing --}}
            <section class="conn-card">
                <h2 class="conn-section-title"><i class="fa-regular fa-paper-plane"></i> Outgoing Requests</h2>

                @if($outgoingRequests->isEmpty())
                    <div class="conn-empty">
                        <div class="conn-empty-icon"><i class="fa-regular fa-paper-plane"></i></div>
                        <p class="conn-empty-text">No outgoing requests.</p>
                    </div>
                @else
                    <div class="req-list">
                        @foreach($outgoingRequests as $connection)
                            <article class="req-item">
                                <span class="req-avatar">{{ strtoupper(substr($connection->receiver->name, 0, 1)) }}</span>
                                <div class="req-info">
                                    <p class="req-name">{{ $connection->receiver->name }}</p>
                                    <p class="req-email">{{ $connection->receiver->email }}</p>
                                </div>
                                <div class="req-actions">
                                    <span class="rel-chip rel-outgoing_pending">
                                        <i class="fa-regular fa-clock" style="margin-right:4px;"></i> Pending
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

        </div>

        {{-- ── Accepted Connections ── --}}
        <section class="conn-card">
            <h2 class="conn-section-title"><i class="fa-solid fa-user-check"></i> Accepted Connections</h2>

            @if($acceptedConnections->isEmpty())
                <div class="conn-empty">
                    <div class="conn-empty-icon"><i class="fa-solid fa-users"></i></div>
                    <p class="conn-empty-text">No accepted connections yet.</p>
                    <a href="{{ route('trips.index') }}" class="btn btn-primary btn-sm" style="margin-top:4px;">
                        <i class="fa-solid fa-route"></i> Explore Trips
                    </a>
                </div>
            @else
                <div class="accept-grid">
                    @foreach($acceptedConnections as $connectionUser)
                        <article class="accept-card">
                            <span class="accept-avatar">{{ strtoupper(substr($connectionUser->name, 0, 1)) }}</span>
                            <div class="accept-name">{{ $connectionUser->name }}</div>
                            <div class="accept-email">{{ $connectionUser->email }}</div>
                            <span class="role-pill">{{ ucfirst($connectionUser->role) }}</span>
                            <form method="POST" action="{{ route('connections.remove', $connectionUser) }}" onsubmit="return confirm('Remove this connection?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm btn-block" style="margin-top:4px;">
                                    <i class="fa-solid fa-user-minus"></i> Remove
                                </button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
@endsection
