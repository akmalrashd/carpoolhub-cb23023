@extends('layouts.app')

@section('content')
    <style>
        .connections-page {
            display: grid;
            gap: 12px;
        }

        .connections-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 18px;
            padding: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }

        .connections-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .connections-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 30px;
            color: #0f172a;
            line-height: 1.05;
        }

        .connections-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .connections-badges {
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .count-chip {
            border: 1px solid #dbe2ea;
            border-radius: 999px;
            padding: 6px 10px;
            background: #f8fafc;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .section-title {
            margin: 0;
            font-size: 18px;
            color: #0f172a;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .search-row {
            margin-top: 10px;
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr auto;
        }

        .search-input {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #0f172a;
            padding: 9px 11px;
            font-size: 14px;
            outline: none;
        }

        .search-input:focus {
            border-color: #94a3b8;
            background: #fff;
        }

        .btn {
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            padding: 9px 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-dark {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }

        .btn-success {
            background: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }

        .btn-danger {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }
        .btn-danger-soft {
            background: #fff;
            border-color: #fecaca;
            color: #b91c1c;
            padding: 8px 12px;
            border-radius: 8px;
        }
        .btn-danger-soft:hover {
            background: #fef2f2;
        }

        .btn-sm {
            padding: 7px 10px;
            font-size: 12px;
            border-radius: 8px;
        }

        .form-error {
            margin-top: 8px;
            color: #b91c1c;
            border: 1px solid rgba(185, 28, 28, 0.25);
            background: rgba(185, 28, 28, 0.06);
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
        }

        .hint-text {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .table-wrap {
            margin-top: 12px;
            overflow: auto;
            border: 0;
            border-radius: 0;
            background: #fff;
            padding: 0;
            box-shadow: none;
        }

        .ui-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ui-table th,
        .ui-table td {
            padding: 11px;
            border: 0;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
            background: transparent;
        }

        .ui-table th {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
            white-space: nowrap;
            background: transparent;
        }

        .ui-table td:last-child,
        .ui-table th:last-child {
            text-align: right;
        }
        .ui-table tbody tr td:first-child {
            padding-left: 11px;
        }
        .ui-table tbody tr td:last-child {
            padding-right: 11px;
        }
        .ui-table tbody tr {
            transition: background-color .18s ease;
        }
        .ui-table tbody tr:hover td {
            background: #f8fafc;
            border-color: #e2e8f0;
        }
        .ui-table form {
            margin: 0;
            display: inline-flex;
            justify-content: flex-end;
        }

        .person-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .person-avatar {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #0f172a;
            display: grid;
            place-items: center;
            font-size: 14px;
            font-weight: 800;
            flex: 0 0 auto;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }
        .person-meta {
            min-width: 0;
            display: grid;
            gap: 1px;
        }
        .person-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
        }
        .person-sub {
            font-size: 12px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 340px;
        }
        .connection-email {
            display: inline-block;
            max-width: 100%;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: middle;
        }
        .role-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #0f172a;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            padding: 4px 9px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-accepted { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .status-outgoing_pending { color: #854d0e; border-color: #fde68a; background: #fefce8; }
        .status-incoming_pending { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
        .status-rejected_by_you,
        .status-rejected_you,
        .status-blocked { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
        .status-none { color: #475569; border-color: #dbe2ea; background: #f8fafc; }

        .panel-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .request-list {
            margin-top: 10px;
            display: grid;
            gap: 9px;
        }

        .request-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 11px;
            background: #fff;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.04);
        }

        .request-name {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }

        .request-email {
            margin: 4px 0 0;
            font-size: 12px;
            color: #64748b;
        }

        .request-actions {
            margin-top: 9px;
            display: inline-flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .empty {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        @media (min-width: 1024px) {
            .connections-card {
                padding: 16px;
            }

            .panel-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .search-row {
                grid-template-columns: 1fr;
            }

            .table-wrap {
                overflow: visible;
                padding: 0;
                background: transparent;
                border: 0;
            }

            .ui-table,
            .ui-table tbody,
            .ui-table tr,
            .ui-table td {
                display: block;
                width: 100%;
            }

            .ui-table thead {
                display: none;
            }

            .ui-table tr {
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                background: #fff;
                padding: 10px 12px;
                margin-bottom: 10px;
                box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
            }

            .ui-table td {
                border: 0 !important;
                padding: 6px 0;
                text-align: left !important;
                box-shadow: none !important;
            }

            .ui-table td::before {
                content: attr(data-label);
                display: block;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .02em;
                color: #64748b;
                margin-bottom: 2px;
            }
            .ui-table td:first-child::before {
                display: none;
            }
            .ui-table td[data-label="Tindakan"] {
                padding-top: 10px;
            }
            .ui-table td[data-label="Tindakan"] form,
            .ui-table td[data-label="Tindakan"] .btn {
                width: 100%;
            }
            .person-avatar {
                width: 40px;
                height: 40px;
            }
            .connection-email {
                max-width: 100%;
            }
        }
    </style>

    <div class="connections-page">
        <section class="connections-card">
            <div class="connections-header">
                <div>
                    <h1 class="connections-title">Sambungan</h1>
                    <p class="connections-subtitle">Bina dan urus rangkaian penumpang dipercayai anda.</p>
                </div>
                <div class="connections-badges">
                    <span class="count-chip"><i class="fa-solid fa-inbox"></i> Masuk {{ $incomingRequests->count() }}</span>
                    <span class="count-chip"><i class="fa-regular fa-paper-plane"></i> Keluar {{ $outgoingRequests->count() }}</span>
                    <span class="count-chip"><i class="fa-solid fa-user-check"></i> Diterima {{ $acceptedConnections->count() }}</span>
                </div>
            </div>
        </section>

        <section class="connections-card">
            <h2 class="section-title"><i class="fa-solid fa-user-plus"></i> Cari Pengguna</h2>
            <form method="GET" action="{{ route('connections.index') }}" class="search-row">
                <input
                    class="search-input"
                    type="text"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Cari mengikut nama atau e-mel"
                >
                <button type="submit" class="btn btn-dark">Cari</button>
            </form>

            @if($errors->has('receiver_id'))
                <div class="form-error">{{ $errors->first('receiver_id') }}</div>
            @endif

            @if($searchResults->isNotEmpty())
                <div class="table-wrap">
                    <table class="ui-table">
                        <thead>
                        <tr>
                            <th>Nama</th>
                            <th>E-mel</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($searchResults as $user)
                            @php
                                $statusMap = [
                                    'accepted' => 'Diterima',
                                    'outgoing_pending' => 'Tertangguh (Keluar)',
                                    'incoming_pending' => 'Tertangguh (Masuk)',
                                    'rejected_by_you' => 'Ditolak (Anda)',
                                    'rejected_you' => 'Ditolak (Mereka)',
                                    'blocked' => 'Disekat',
                                    'none' => 'Tiada Sambungan',
                                ];
                                $statusLabel = $statusMap[$user->relationship_status] ?? 'Tidak Diketahui';
                            @endphp
                            <tr>
                                <td data-label="Nama">
                                    <div class="person-cell">
                                        <span class="person-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        <div class="person-meta">
                                            <span class="person-name">{{ $user->name }}</span>
                                            <span class="person-sub">{{ ucfirst($user->role) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="E-mel"><span class="person-sub" style="max-width:280px; display:inline-block;">{{ $user->email }}</span></td>
                                <td data-label="Status">
                                    <span class="status-chip status-{{ $user->relationship_status }}">{{ $statusLabel }}</span>
                                </td>
                                <td data-label="Tindakan">
                                    @if(in_array($user->relationship_status, ['none', 'rejected_by_you', 'rejected_you'], true))
                                        <form method="POST" action="{{ route('connections.requests.store') }}">
                                            @csrf
                                            <input type="hidden" name="receiver_id" value="{{ $user->id }}">
                                            <button type="submit" class="btn btn-dark btn-sm">Hantar Permohonan</button>
                                        </form>
                                    @elseif($user->relationship_status === 'incoming_pending')
                                        <span class="hint-text" style="margin:0;">Respon di bawah</span>
                                    @else
                                        <span class="hint-text" style="margin:0;">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif($q !== '')
                <p class="hint-text">Tiada pengguna dijumpai untuk "{{ $q }}".</p>
            @endif
        </section>

        <section class="panel-grid">
            <div class="connections-card">
                <h2 class="section-title"><i class="fa-solid fa-inbox"></i> Permohonan Masuk</h2>
                @if($incomingRequests->isEmpty())
                    <p class="empty">Tiada permohonan masuk.</p>
                @else
                    <div class="request-list">
                        @foreach($incomingRequests as $connection)
                            <article class="request-item">
                                <h3 class="request-name">{{ $connection->requester->name }}</h3>
                                <p class="request-email">{{ $connection->requester->email }}</p>
                                <div class="request-actions">
                                    <form method="POST" action="{{ route('connections.respond', $connection) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-success btn-sm">Terima</button>
                                    </form>
                                    <form method="POST" action="{{ route('connections.respond', $connection) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm">Tolak</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="connections-card">
                <h2 class="section-title"><i class="fa-regular fa-paper-plane"></i> Permohonan Keluar</h2>
                @if($outgoingRequests->isEmpty())
                    <p class="empty">Tiada permohonan keluar.</p>
                @else
                    <div class="request-list">
                        @foreach($outgoingRequests as $connection)
                            <article class="request-item">
                                <h3 class="request-name">{{ $connection->receiver->name }}</h3>
                                <p class="request-email">{{ $connection->receiver->email }}</p>
                                <div class="request-actions">
                                    <span class="status-chip status-outgoing_pending">Tertangguh</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="connections-card">
            <h2 class="section-title"><i class="fa-solid fa-user-check"></i> Sambungan Diterima</h2>
            @if($acceptedConnections->isEmpty())
                <p class="empty">Tiada sambungan diterima lagi.</p>
            @else
                <div class="table-wrap">
                    <table class="ui-table">
                        <thead>
                        <tr>
                            <th>Nama</th>
                            <th>E-mel</th>
                            <th>Peranan</th>
                            <th>Tindakan</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($acceptedConnections as $connectionUser)
                            <tr>
                                <td data-label="Nama">
                                    <div class="person-cell">
                                        <span class="person-avatar">{{ strtoupper(substr($connectionUser->name, 0, 1)) }}</span>
                                        <div class="person-meta">
                                            <span class="person-name">{{ $connectionUser->name }}</span>
                                            <span class="person-sub">{{ ucfirst($connectionUser->role) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="E-mel"><span class="connection-email">{{ $connectionUser->email }}</span></td>
                                <td data-label="Peranan"><span class="role-pill">{{ ucfirst($connectionUser->role) }}</span></td>
                                <td data-label="Tindakan">
                                    <form method="POST" action="{{ route('connections.remove', $connectionUser) }}" onsubmit="return confirm('Buang sambungan ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger-soft btn-sm">
                                            <i class="fa-solid fa-user-minus"></i>
                                            <span>Buang</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
