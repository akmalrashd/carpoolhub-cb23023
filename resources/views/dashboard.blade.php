@extends('layouts.app')

@section('content')
    <style>
        .app-shell {
            background:
                radial-gradient(circle at 85% -10%, rgba(17, 24, 39, 0.06), transparent 35%),
                radial-gradient(circle at 10% 110%, rgba(30, 41, 59, 0.06), transparent 42%),
                #f5f7fa;
        }

        .dashboard-shell {
            display: grid;
            gap: 14px;
        }

        .welcome-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc 68%, #eef3f9);
            border: 1px solid #dbe2ea;
            border-radius: 18px;
            padding: 16px;
        }

        .welcome-meta {
            margin: 0 0 8px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .welcome-title {
            font-family: Poppins, sans-serif;
            font-size: 31px;
            margin: 0;
            letter-spacing: -0.01em;
            color: #0f172a;
            line-height: 1.1;
        }

        .welcome-subtitle {
            margin: 8px 0 0;
            font-size: 15px;
            color: #64748b;
        }

        .welcome-actions {
            margin-top: 14px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .welcome-btn {
            text-decoration: none;
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .welcome-btn.primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }

        .stats-stack {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            padding: 12px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            right: -10px;
            top: -12px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #eff6ff;
        }

        .stat-label {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 21px;
            font-weight: 700;
            line-height: 1.1;
            color: #0f172a;
            position: relative;
            z-index: 1;
        }

        .section-card {
            background: #ffffff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 12px;
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .section-title {
            margin: 0;
            font-size: 23px;
            font-family: Poppins, sans-serif;
        }

        .section-link {
            text-decoration: none;
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .quick-link {
            text-decoration: none;
            color: #0f172a;
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #f8fafc;
            padding: 12px 10px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            display: grid;
            gap: 8px;
            justify-items: center;
            position: relative;
            transition: transform 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        }

        .quick-link:hover {
            background: #f1f5f9;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .quick-image-wrap {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            background: #ecf2f7;
            border: 1px solid #dbe2ea;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .quick-image {
            width: 42px;
            height: 42px;
            object-fit: contain;
            display: block;
        }

        .quick-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            border-radius: 999px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #c2410c;
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
            padding: 4px 6px;
        }

        .quick-title {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
            color: #0f172a;
        }

        .quick-meta {
            font-size: 11px;
            color: #64748b;
            line-height: 1.2;
        }

        .trip-mobile-list {
            display: grid;
            gap: 8px;
        }

        .trip-mobile-item {
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            padding: 11px;
            background: #f8fafc;
        }

        .trip-name {
            font-weight: 600;
            font-size: 14px;
            line-height: 1.3;
            color: #0f172a;
        }

        .trip-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            font-size: 13px;
        }

        .trip-muted,
        .empty-note {
            color: #64748b;
            font-size: 12px;
        }

        .trip-table-wrap { display: none; }

        .status-pill {
            display: inline-flex;
            align-items: center;
            border: 1px solid #dbe2ea;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 12px;
            color: #475569;
        }

        @media (min-width: 768px) {
            .welcome-title {
                font-size: 34px;
            }

            .stats-stack {
                grid-template-columns: repeat(4, 1fr);
            }

            .quick-actions {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .dashboard-shell {
                gap: 16px;
            }

            .welcome-card {
                padding: 16px;
            }

            .trip-mobile-list { display: none; }
            .trip-table-wrap { display: block; overflow-x: auto; }

            .trip-table {
                width: 100%;
                border-collapse: collapse;
            }

            .trip-table th,
            .trip-table td {
                padding: 10px;
                border-bottom: 1px solid #e2e8f0;
                text-align: left;
            }

            .trip-table th:last-child,
            .trip-table td:last-child { text-align: right; }
        }
    </style>

    <div class="dashboard-shell">
        <section class="welcome-card">
            <p class="welcome-meta">{{ now()->format('l, d M Y') }}</p>
            <h1 class="welcome-title">Selamat kembali, {{ auth()->user()->name }}.</h1>
            <p class="welcome-subtitle">Berikut adalah ringkasan akaun anda hari ini. Gunakan perkhidmatan di bawah untuk meneruskan dengan cepat.</p>
            <div class="welcome-actions">
                <a href="{{ route('trips.create') }}" class="welcome-btn primary"><i class="fa-solid fa-plus"></i><span>Mulakan Trip</span></a>
                <a href="{{ route('saved-routes.index') }}" class="welcome-btn"><i class="fa-solid fa-route"></i><span>Laluan Saya</span></a>
            </div>
        </section>

        <section class="stats-stack">
            <div class="stat-card">
                <div class="stat-label">Jumlah Trip</div>
                <div class="stat-value">{{ $stats['total_trips'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Amaun Belum Bayar</div>
                <div class="stat-value">RM {{ number_format((float) ($stats['unpaid_amount'] ?? 0), 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Laluan Tersimpan</div>
                <div class="stat-value">{{ $stats['saved_routes'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Notifikasi Belum Dibaca</div>
                <div class="stat-value">{{ $stats['unread_notifications'] ?? 0 }}</div>
            </div>
        </section>

        <section class="section-card">
            <div class="section-head">
                <h2 class="section-title">Perkhidmatan</h2>
            </div>
            <div class="quick-actions">
                <a href="{{ route('trips.create') }}" class="quick-link">
                    <span class="quick-image-wrap">
                        <img src="{{ asset('assets/illustrations/quick-trip-3d.svg') }}" alt="" class="quick-image">
                    </span>
                    <span class="quick-title">Mulakan Trip</span>
                    <span class="quick-meta">Cipta perjalanan sekarang</span>
                </a>
                <a href="{{ route('saved-routes.create') }}" class="quick-link">
                    <span class="quick-image-wrap">
                        <img src="{{ asset('assets/illustrations/quick-route-3d.svg') }}" alt="" class="quick-image">
                    </span>
                    <span class="quick-title">Simpan Laluan</span>
                    <span class="quick-meta">Pin laluan kegemaran</span>
                </a>
                <a href="{{ route('payments.index') }}" class="quick-link">
                    <span class="quick-badge">RM</span>
                    <span class="quick-image-wrap">
                        <img src="{{ asset('assets/illustrations/quick-payment-3d.svg') }}" alt="" class="quick-image">
                    </span>
                    <span class="quick-title">Agih Tambang</span>
                    <span class="quick-meta">Pantau pembayaran tertangguh</span>
                </a>
                <a href="{{ route('connections.index') }}" class="quick-link">
                    <span class="quick-image-wrap">
                        <img src="{{ asset('assets/illustrations/quick-connection-3d.svg') }}" alt="" class="quick-image">
                    </span>
                    <span class="quick-title">Jemput Penumpang</span>
                    <span class="quick-meta">Urus pasukan carpool</span>
                </a>
            </div>
        </section>

        <section class="section-card">
            <div class="section-head">
                <h2 class="section-title">Trip Terkini</h2>
                <a href="{{ route('trips.index') }}" class="section-link">Lihat semua</a>
            </div>

            @if(($recentTrips ?? collect())->isEmpty())
                <p class="empty-note" style="margin:0;">Tiada trip lagi.</p>
            @else
                <div class="trip-mobile-list">
                    @foreach($recentTrips as $trip)
                        <article class="trip-mobile-item">
                            <div class="trip-name">
                                {{ $trip->savedRoute?->route_name ?? $trip->destination_name ?? '-' }}
                            </div>
                            <div class="trip-muted">{{ $trip->trip_datetime?->format('Y-m-d H:i') }}</div>
                            <div class="trip-row">
                                <span class="status-pill">{{ ucfirst($trip->status) }}</span>
                                <strong>RM {{ number_format((float) $trip->fare_total, 2) }}</strong>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="trip-table-wrap">
                    <table class="trip-table">
                        <thead>
                        <tr>
                            <th>Tarikh</th>
                            <th>Laluan</th>
                            <th>Status</th>
                            <th>Tambang</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($recentTrips as $trip)
                            <tr>
                                <td>{{ $trip->trip_datetime?->format('Y-m-d H:i') }}</td>
                                <td>{{ $trip->savedRoute?->route_name ?? $trip->destination_name ?? '-' }}</td>
                                <td>{{ ucfirst($trip->status) }}</td>
                                <td>RM {{ number_format((float) $trip->fare_total, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
