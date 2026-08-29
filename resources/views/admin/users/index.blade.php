@extends('layouts.app')

@section('content')

@php
    use App\Models\User;
    $totalUsers     = User::count();
    $adminCount     = User::where('role', 'admin')->count();
    $driverCount    = User::where('role', 'driver')->count();
    $passengerCount = User::where('role', 'passenger')->count();
    $pendingCount   = $pendingDrivers->total();
    $approvedDriverCount = User::where('role', 'driver')->where('driver_verification_status', 'approved')->count();
    $rejectedDriverCount = User::where('role', 'driver')->where('driver_verification_status', 'rejected')->count();
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
@endpush

<div class="au-page">

{{-- Page Header --}}
<div>
    <p class="au-eyebrow">Admin Panel</p>
    <h1 class="au-title">User Management</h1>
    <p class="au-sub">Manage accounts, approve drivers, and control access.</p>
</div>

@include('layouts.partials.admin-subnav')

{{-- Manage Users vs Driver Verification were one undivided page: a general
     roster (role/suspend, any account) mixed with document review
     (license/selfie photos, approve/reject) that only ever applies to
     drivers. Split via ?view= like Audit Log's tabs, sharing this one route
     rather than becoming a 6th admin-subnav destination. --}}
<nav class="subview-tabs">
    <a href="{{ route('admin.users.index', ['view' => 'manage']) }}" class="{{ $view === 'manage' ? 'active' : '' }}">
        <i class="fa-solid fa-users-gear"></i> Manage Users
    </a>
    <a href="{{ route('admin.users.index', ['view' => 'verification']) }}" class="{{ $view === 'verification' ? 'active' : '' }}">
        <i class="fa-solid fa-id-card"></i> Driver Verification
        @if($pendingCount > 0)<span class="subview-count">{{ $pendingCount }}</span>@endif
    </a>
</nav>

{{-- Error banner --}}
@if($errors->any())
    <div style="padding:12px 16px;border-radius:var(--r-md);border:1px solid rgba(220,38,38,.28);background:var(--danger-soft);color:var(--danger-ink);font-size:14px;font-weight:500;">
        <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>{{ $errors->first() }}
    </div>
@endif
@if(session('status'))
    <div style="padding:12px 16px;border-radius:var(--r-md);border:1px solid #bbf7d0;background:#f0fdf4;color:#15803d;font-size:14px;font-weight:600;">
        <i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>{{ session('status') }}
    </div>
@endif

@if($view === 'verification')
{{-- ══════════════════════ DRIVER VERIFICATION ══════════════════════ --}}

{{-- Stats --}}
<div class="au-stats">
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:#fef3c7;border:1px solid #fde68a;">
            <i class="fa-solid fa-clock" style="color:#92400e;"></i>
        </div>
        <div class="au-stat-val">{{ $pendingCount }}</div>
        <div class="au-stat-lbl">Pending Review</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:#f0fdf4;border:1px solid #bbf7d0;">
            <i class="fa-solid fa-circle-check" style="color:#15803d;"></i>
        </div>
        <div class="au-stat-val">{{ $approvedDriverCount }}</div>
        <div class="au-stat-lbl">Approved</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:#fef2f2;border:1px solid rgba(220,38,38,.2);">
            <i class="fa-solid fa-circle-xmark" style="color:#dc2626;"></i>
        </div>
        <div class="au-stat-val">{{ $rejectedDriverCount }}</div>
        <div class="au-stat-lbl">Rejected</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--info-soft);border:1px solid rgba(37,99,235,.2);">
            <i class="fa-solid fa-car" style="color:var(--info-ink);"></i>
        </div>
        <div class="au-stat-val">{{ $driverCount }}</div>
        <div class="au-stat-lbl">Total Drivers</div>
    </div>
</div>

{{-- â”€â”€ DRIVER APPROVAL QUEUE â”€â”€ --}}
@if($pendingCount > 0)
<div style="background:var(--surface);border:1px solid #fde68a;border-radius:var(--r-md);overflow:hidden;box-shadow:0 2px 12px rgba(250,204,21,.12);">
    <div class="apq-head">
        <div style="width:32px;height:32px;border-radius:var(--r-sm);background:#fef3c7;border:1px solid #fde68a;display:grid;place-items:center;flex-shrink:0;">
            <i class="fa-solid fa-clock" style="color:#92400e;font-size:13px;"></i>
        </div>
        <div>
            <div class="apq-title">Pending Driver Approvals</div>
            <div style="font-size:12px;color:var(--muted);margin-top:1px;">Review driving license and activate accounts</div>
        </div>
        <span class="apq-badge" style="margin-left:auto;">{{ $pendingCount }} pending</span>
    </div>

    @foreach($pendingDrivers as $pd)
    @php
        $pdColors = ['#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981'];
        $pdColor  = $pdColors[abs(crc32($pd->name)) % 5];
        $pdVehicle = trim(($pd->vehicle_model??'').' '.($pd->vehicle_plate??'')) ?: '—';
    @endphp
    <div class="dac-wrap" style="background:{{ $loop->odd ? 'var(--surface)' : 'var(--surface-2)' }};">
        <div class="dac-body">
            {{-- ROW 1: info on left, thumbnail on right --}}
            <div class="dac-row1">
                {{-- Avatar + info --}}
                <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
                    <div class="dac-avatar" style="background:{{ $pdColor }};">{{ strtoupper(substr($pd->name,0,1)) }}</div>
                    <div class="dac-info">
                        <div class="dac-name">{{ $pd->name }}</div>
                        <div class="dac-meta">{{ $pd->email }}</div>
                        @if($pd->vehicle_model || $pd->vehicle_plate)
                            <div class="dac-meta" style="margin-top:1px;">
                                <i class="fa-solid fa-car" style="font-size:10px;opacity:.6;margin-right:3px;"></i>
                                {{ $pdVehicle }}
                            </div>
                        @endif
                        @if($pd->driving_license_expiry)
                            <div class="dac-meta" style="margin-top:1px;">
                                <i class="fa-solid fa-calendar-days" style="font-size:10px;opacity:.6;margin-right:3px;"></i>
                                Expires {{ $pd->driving_license_expiry->format('d M Y') }}
                                @if($pd->driving_license_expiry->isPast())
                                    <span class="status-pill status-rejected" style="margin-left:6px;">Expired</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                {{-- Verification thumbnail on the RIGHT (Driving License only) --}}
                @if($pd->driving_license_photo)
                    <img class="dac-thumb"
                        src="{{ $pd->driving_license_photo }}"
                        alt="License"
                        title="Click to view & compare verification photos"
                        data-uid="{{ $pd->id }}"
                        data-name="{{ $pd->name }}"
                        data-email="{{ $pd->email }}"
                        data-phone="{{ $pd->phone ?? '—' }}"
                        data-vehicle="{{ $pdVehicle }}"
                        data-active="{{ $pd->is_active ? '1' : '0' }}"
                        data-status="{{ $pd->driver_verification_status }}"
                        data-reason="{{ $pd->driver_verification_reason }}"
                        data-deactivation-reason="{{ $pd->deactivation_reason }}"
                        data-joined="{{ $pd->created_at?->format('d M Y') ?? '—' }}"
                        {{-- data-license deliberately omitted: it held a byte-identical
                             copy of src, doubling this page's weight (these are
                             multi-MB base64 data URIs). openLicenseFromEl already
                             falls back to el.src when alt is "License". --}}
                        data-selfie="{{ $pd->selfie_photo ?? '' }}"
                        onclick="openLicenseFromEl(this)"
                    >
                @else
                    <div class="dac-thumb" style="display:flex;align-items:center;justify-content:center;border:1px dashed var(--hairline-strong);cursor:default;">
                        <i class="fa-solid fa-ban" style="color:var(--muted-2);font-size:16px;"></i>
                    </div>
                @endif
            </div>
            {{-- ROW 2: Approve + Reject --}}
            <div class="dac-row2">
                <form method="POST" action="{{ route('admin.users.approve', $pd) }}" style="display:inline;">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn-approve"><i class="fa-solid fa-circle-check"></i> Approve</button>
                </form>
                <button type="button" class="btn-reject"
                    onclick="openRejectModal('{{ $pd->id }}', '{{ addslashes($pd->name) }}')"
                ><i class="fa-solid fa-circle-xmark"></i> Reject</button>
            </div>
        </div>
    </div>
    @endforeach

    @if($pendingDrivers->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--hairline);display:flex;justify-content:flex-end;">
            {{ $pendingDrivers->links() }}
        </div>
    @endif
</div>
@endif

{{-- Filter bar --}}
<div class="au-filter-card">
    <form method="GET" action="{{ route('admin.users.index') }}">
        <input type="hidden" name="view" value="verification">
        <div class="au-filter-grid">
            <div style="position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:12px;pointer-events:none;"></i>
                <input type="text" name="vq" value="{{ request('vq') }}" placeholder="Search driver by name or emailâ€¦" class="au-input">
            </div>
            <select name="verification_status" class="au-select">
                <option value="">All Statuses</option>
                @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $vOpt => $vLabel)
                    <option value="{{ $vOpt }}" {{ request('verification_status') === $vOpt ? 'selected' : '' }}>{{ $vLabel }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                <i class="fa-solid fa-filter" style="font-size:11px;"></i> Filter
            </button>
        </div>
        @if(request('vq') || request('verification_status'))
            <div style="margin-top:8px;">
                <a href="{{ route('admin.users.index', ['view' => 'verification']) }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">
                    <i class="fa-solid fa-xmark" style="font-size:11px;"></i> Clear Filters
                </a>
            </div>
        @endif
    </form>
</div>

{{-- Drivers table --}}
<div style="background:var(--surface);border:1px solid var(--hairline);border-radius:var(--r-md);overflow:hidden;">
    @forelse($drivers as $driver)
        @php
            $uc = ['#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981'][abs(crc32($driver->name)) % 5];
            $dVehicle = trim(($driver->vehicle_model ?? '').' '.($driver->vehicle_plate ?? '')) ?: '—';
        @endphp
        @if($loop->first)
            <div class="au-table-wrap">
            <table class="au-table">
                <thead><tr>
                    <th>Driver</th><th>Vehicle</th><th>Status</th><th>Joined</th>
                    <th style="text-align:right;">Actions</th>
                </tr></thead>
                <tbody>
        @endif
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                            <div class="au-avatar" style="background:{{ $uc }};">{{ strtoupper(substr($driver->name,0,1)) }}</div>
                            <div style="min-width:0;">
                                <div style="font-weight:700;font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;">{{ $driver->name }}</div>
                                <div style="font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;">{{ $driver->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:13px;color:var(--ink);white-space:nowrap;">{{ $dVehicle }}</td>
                    <td>
                        @php $acctStatus = $driver->accountStatusLabel(); @endphp
                        <span class="status-pill {{ $acctStatus['pill_class'] }}"><span class="dot-sm" style="background:{{ $acctStatus['dot_color'] }};"></span> {{ $acctStatus['label'] }}</span>
                        @if(!empty($acctStatus['reason']))
                            <div class="t-xs text-muted" style="margin-top:3px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $acctStatus['reason'] }}">{{ $acctStatus['reason'] }}</div>
                        @endif
                    </td>
                    <td style="font-size:13px;color:var(--muted);white-space:nowrap;">{{ $driver->created_at?->format('d M Y') ?? 'â€”' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;justify-content:flex-end;flex-wrap:wrap;">
                            <button type="button" class="au-qbtn"
                                style="background:var(--info-soft);color:var(--info-ink);border-color:rgba(37,99,235,.2);"
                                data-uid="{{ $driver->id }}"
                                data-name="{{ $driver->name }}"
                                data-email="{{ $driver->email }}"
                                data-phone="{{ $driver->phone ?? '—' }}"
                                data-vehicle="{{ $dVehicle }}"
                                data-active="{{ $driver->is_active ? '1' : '0' }}"
                                data-status="{{ $driver->driver_verification_status }}"
                                data-reason="{{ $driver->driver_verification_reason }}"
                                data-deactivation-reason="{{ $driver->deactivation_reason }}"
                                data-joined="{{ $driver->created_at?->format('d M Y') ?? '—' }}"
                                data-license="{{ $driver->driving_license_photo ?? '' }}"
                                data-selfie="{{ $driver->selfie_photo ?? '' }}"
                                onclick="openLicenseFromBtn(this)"
                            ><i class="fa-solid fa-id-card" style="font-size:11px;"></i> Review</button>
                        </div>
                    </td>
                </tr>
        @if($loop->last)
                </tbody></table>
            </div>
        @endif
    @empty
        <div style="padding:56px 24px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:var(--r-md);background:var(--surface-2);border:1px solid var(--hairline-strong);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                <i class="fa-solid fa-id-card" style="font-size:22px;color:var(--muted-2);"></i>
            </div>
            <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--ink);margin-bottom:6px;">No drivers found</div>
            <div style="font-size:14px;color:var(--muted);">Try adjusting your search or filters.</div>
            @if(request('vq') || request('verification_status'))
                <div style="margin-top:14px;"><a href="{{ route('admin.users.index', ['view' => 'verification']) }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">Clear Filters</a></div>
            @endif
        </div>
    @endforelse
    @if($drivers->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--hairline);display:flex;justify-content:flex-end;">
            {{ $drivers->links() }}
        </div>
    @endif
</div>

@else
{{-- ══════════════════════ MANAGE USERS ══════════════════════ --}}

{{-- Stats --}}
<div class="au-stats">
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--ch-yellow-tint);border:1px solid var(--ch-yellow-line);">
            <i class="fa-solid fa-users" style="color:var(--ch-yellow-ink);"></i>
        </div>
        <div class="au-stat-val">{{ $totalUsers }}</div>
        <div class="au-stat-lbl">Total Users</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:#fef2f2;border:1px solid rgba(220,38,38,.2);">
            <i class="fa-solid fa-user-shield" style="color:#dc2626;"></i>
        </div>
        <div class="au-stat-val">{{ $adminCount }}</div>
        <div class="au-stat-lbl">Admins</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--info-soft);border:1px solid rgba(37,99,235,.2);">
            <i class="fa-solid fa-car" style="color:var(--info-ink);"></i>
        </div>
        <div class="au-stat-val">{{ $driverCount }}</div>
        <div class="au-stat-lbl">Drivers</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--surface-2);border:1px solid var(--hairline-strong);">
            <i class="fa-solid fa-user" style="color:var(--muted);"></i>
        </div>
        <div class="au-stat-val">{{ $passengerCount }}</div>
        <div class="au-stat-lbl">Passengers</div>
    </div>
</div>

{{-- Filter bar --}}
<div class="au-filter-card">
    <form method="GET" action="{{ route('admin.users.index') }}">
        <input type="hidden" name="view" value="manage">
        <div class="au-filter-grid">
            <div style="position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:12px;pointer-events:none;"></i>
                <input id="q" type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, email or phoneâ€¦" class="au-input">
            </div>
            <select id="role-filter" name="role" class="au-select">
                <option value="">All Roles</option>
                @foreach(['admin','driver','passenger'] as $rOpt)
                    <option value="{{ $rOpt }}" {{ request('role') === $rOpt ? 'selected' : '' }}>{{ ucfirst($rOpt) }}</option>
                @endforeach
            </select>
            <select id="active-filter" name="active" class="au-select">
                <option value="">All Status</option>
                <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Suspended</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                <i class="fa-solid fa-filter" style="font-size:11px;"></i> Filter
            </button>
        </div>
        @if(request('q') || request('role') || (request('active') !== null && request('active') !== ''))
            <div style="margin-top:8px;">
                <a href="{{ route('admin.users.index', ['view' => 'manage']) }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">
                    <i class="fa-solid fa-xmark" style="font-size:11px;"></i> Clear Filters
                </a>
            </div>
        @endif
    </form>
</div>

{{-- Users table --}}
<div style="background:var(--surface);border:1px solid var(--hairline);border-radius:var(--r-md);overflow:hidden;">
    @forelse($users as $user)
        @php $uc = ['#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981'][abs(crc32($user->name)) % 5]; @endphp
        @if($loop->first)
            <div class="au-table-wrap">
            <table class="au-table">
                <thead><tr>
                    <th>User</th><th>Role</th><th>Status</th><th>Joined</th>
                    <th style="text-align:right;">Actions</th>
                </tr></thead>
                <tbody>
        @endif
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                            <div class="au-avatar" style="background:{{ $uc }};">{{ strtoupper(substr($user->name,0,1)) }}</div>
                            <div style="min-width:0;">
                                <div style="font-weight:700;font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;">{{ $user->name }}</div>
                                <div style="font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;">{{ $user->email }}</div>
                                @if($user->phone)<div style="font-size:11px;color:var(--muted-2);margin-top:1px;">{{ $user->phone }}</div>@endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="role-pill {{ $user->role === 'admin' ? 'role-admin' : ($user->role === 'driver' ? 'role-driver' : 'role-passenger') }}">
                            <i class="fa-solid {{ $user->role === 'admin' ? 'fa-user-shield' : ($user->role === 'driver' ? 'fa-car' : 'fa-user') }}" style="font-size:10px;"></i>
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td>
                        @php $acctStatus = $user->accountStatusLabel(); @endphp
                        <span class="status-pill {{ $acctStatus['pill_class'] }}"><span class="dot-sm" style="background:{{ $acctStatus['dot_color'] }};"></span> {{ $acctStatus['label'] }}</span>
                        @if(!empty($acctStatus['reason']))
                            <div class="t-xs text-muted" style="margin-top:3px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $acctStatus['reason'] }}">{{ $acctStatus['reason'] }}</div>
                        @endif
                    </td>
                    <td style="font-size:13px;color:var(--muted);white-space:nowrap;">{{ $user->created_at?->format('d M Y') ?? 'â€”' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;justify-content:flex-end;flex-wrap:wrap;">
                            <button class="au-qbtn au-qbtn-edit"
                                onclick="openEditDrawer('{{ $user->id }}','{{ addslashes($user->name) }}','{{ $user->role }}','{{ $user->is_active?'1':'0' }}')"
                            ><i class="fa-solid fa-pen" style="font-size:11px;"></i> Edit</button>
                        </div>
                    </td>
                </tr>
        @if($loop->last)
                </tbody></table>
            </div>
        @endif
    @empty
        <div style="padding:56px 24px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:var(--r-md);background:var(--surface-2);border:1px solid var(--hairline-strong);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                <i class="fa-solid fa-users" style="font-size:22px;color:var(--muted-2);"></i>
            </div>
            <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--ink);margin-bottom:6px;">No users found</div>
            <div style="font-size:14px;color:var(--muted);">Try adjusting your search or filters.</div>
            @if(request('q') || request('role') || (request('active') !== null && request('active') !== ''))
                <div style="margin-top:14px;"><a href="{{ route('admin.users.index', ['view' => 'manage']) }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">Clear Filters</a></div>
            @endif
        </div>
    @endforelse
    @if($users->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--hairline);display:flex;justify-content:flex-end;">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endif

</div>{{-- /au-page --}}


{{-- ── LICENSE REVIEW MODAL ── --}}
<div id="license-modal" class="lr-backdrop" onclick="if(event.target===this)closeLicenseModal()">
    <div class="lr-modal">
        <div class="lr-pill-handle"></div>
        <div class="lr-top">
            <div class="lr-profile">
                <div id="lr-av" class="au-avatar" style="width:44px;height:44px;font-size:17px;background:#3b82f6;">?</div>
                <div>
                    <p class="lr-profile-name" id="lr-name">—</p>
                    <p class="lr-profile-email" id="lr-email">—</p>
                </div>
            </div>
            <button class="lr-close-x" onclick="closeLicenseModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="lr-scroll-body">
            <div class="lr-info-grid">
                <div class="lr-info-item"><span class="lr-info-lbl">Phone</span><span class="lr-info-val" id="lr-phone">—</span></div>
                <div class="lr-info-item"><span class="lr-info-lbl">Vehicle</span><span class="lr-info-val" id="lr-vehicle">—</span></div>
                <div class="lr-info-item"><span class="lr-info-lbl">Registered</span><span class="lr-info-val" id="lr-joined">—</span></div>
                <div class="lr-info-item"><span class="lr-info-lbl">Status</span><span class="lr-info-val" id="lr-status">—</span></div>
            </div>
            <div class="lr-info-item" id="lr-reason-row" style="display:none;margin-top:8px;">
                <span class="lr-info-lbl" id="lr-reason-lbl">Reason</span>
                <span class="lr-info-val" id="lr-reason" style="display:block;margin-top:2px;">—</span>
            </div>
            <div class="lr-img-section">
                <div class="lr-img-dual-grid">
                    {{-- Selfie Verification Card --}}
                    <div>
                        <div class="lr-img-label" style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fa-solid fa-user-shield" style="color:#10b981;font-size:13px;"></i>
                            <span>Selfie (Holding License)</span>
                        </div>
                        <img id="lr-selfie-img" src="" alt="Selfie Verification" class="lr-license-img" onclick="openFullImage(this.src)">
                        <div id="lr-selfie-empty" style="display:none;width:100%;height:200px;align-items:center;justify-content:center;flex-direction:column;gap:6px;border:1px dashed var(--hairline-strong);border-radius:var(--r-md);background:var(--surface-2);">
                            <i class="fa-solid fa-user-xmark" style="color:var(--muted-2);font-size:24px;"></i>
                            <span style="font-size:12px;color:var(--muted-2);font-weight:600;">No Selfie Uploaded</span>
                        </div>
                    </div>

                    {{-- License Photo Card --}}
                    <div>
                        <div class="lr-img-label" style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fa-solid fa-id-card" style="color:#3b82f6;font-size:13px;"></i>
                            <span>Driving License</span>
                        </div>
                        <img id="lr-license-img" src="" alt="Driving License" class="lr-license-img" onclick="openFullImage(this.src)">
                        <div id="lr-license-empty" style="display:none;width:100%;height:200px;align-items:center;justify-content:center;flex-direction:column;gap:6px;border:1px dashed var(--hairline-strong);border-radius:var(--r-md);background:var(--surface-2);">
                            <i class="fa-solid fa-ban" style="color:var(--muted-2);font-size:24px;"></i>
                            <span style="font-size:12px;color:var(--muted-2);font-weight:600;">No License Uploaded</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="lr-actions">
            <div><span id="lr-badge"></span></div>
            <div class="lr-btn-side">
                <button class="lr-close-footer" onclick="closeLicenseModal()">Close</button>
                <button type="button" class="lr-reject-btn" id="lr-reject-btn" onclick="openRejectModalFromLicense()"><i class="fa-solid fa-circle-xmark"></i> Reject</button>
                <button type="button" class="lr-reject-btn" id="lr-suspend-btn" onclick="openEditDrawerFromLicense()"><i class="fa-solid fa-circle-minus"></i> Suspend</button>
                <form id="lr-reactivate-form" method="POST" style="display:inline;">
                    @csrf @method('PATCH')
                    <input type="hidden" name="role" value="driver">
                    <input type="hidden" name="is_active" value="1">
                    <button type="submit" class="lr-approve-btn" id="lr-reactivate-btn"><i class="fa-solid fa-rotate-left"></i> Reactivate</button>
                </form>
                <form id="lr-approve-form" method="POST" style="display:inline;">
                    @csrf @method('PATCH')
                    <button type="submit" class="lr-approve-btn" id="lr-approve-btn"><i class="fa-solid fa-circle-check"></i> Approve Driver</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- â•â• EDIT USER DRAWER â•â• --}}
<div id="edit-drawer" class="eu-backdrop" onclick="if(event.target===this)closeEditDrawer()">
    <div class="eu-drawer">
        <div class="eu-pill"></div>
        <div class="eu-title" id="eu-title">Edit User</div>
        <div class="eu-sub" id="eu-sub">Update role and account status</div>
        <form id="eu-form" method="POST">
            @csrf @method('PATCH')
            <div class="eu-field">
                <label class="eu-label" for="eu-role">Role</label>
                <select id="eu-role" name="role" class="eu-select">
                    <option value="admin">Admin</option>
                    <option value="driver">Driver</option>
                    <option value="passenger">Passenger</option>
                </select>
            </div>
            <div class="eu-field">
                <label class="eu-label" for="eu-status">Account Status</label>
                <select id="eu-status" name="is_active" class="eu-select" onchange="toggleEditReasonField()">
                    <option value="1">Active</option>
                    <option value="0">Suspended</option>
                </select>
            </div>
            <div class="eu-field" id="eu-reason-field" style="display:none;">
                <label class="eu-label" for="eu-reason" style="display:flex;justify-content:space-between;align-items:baseline;">
                    <span>Reason for suspending</span>
                    <span class="t-xs text-muted" id="eu-reason-count" style="text-transform:none;letter-spacing:normal;font-weight:600;">0 / 1000</span>
                </label>
                <p class="t-xs text-muted" style="margin:0 0 6px;">The user will see this exact text if they try to log in while suspended.</p>
                <textarea id="eu-reason" name="reason" class="eu-select" rows="4" maxlength="1000" placeholder="e.g. Repeated no-show complaints."></textarea>
            </div>
            <div class="eu-field" id="eu-suspend-until-field" style="display:none;">
                <label class="eu-label" for="eu-suspended-until">Suspend Until (optional)</label>
                <p class="t-xs text-muted" style="margin:0 0 6px;">Leave blank for a permanent suspension. Set a date and the account reactivates on its own once it passes.</p>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px;">
                    <button type="button" class="eu-qp-btn" id="eu-qp-1" onclick="setSuspendQuickPick(1,this)">1 Day</button>
                    <button type="button" class="eu-qp-btn" id="eu-qp-7" onclick="setSuspendQuickPick(7,this)">7 Days</button>
                    <button type="button" class="eu-qp-btn" id="eu-qp-30" onclick="setSuspendQuickPick(30,this)">30 Days</button>
                    <button type="button" class="eu-qp-btn active" id="eu-qp-permanent" onclick="setSuspendQuickPick(null,this)">Permanent</button>
                </div>
                <input type="datetime-local" id="eu-suspended-until" name="suspended_until" class="eu-select" onchange="clearSuspendQuickPickHighlight()">
            </div>
            <button type="submit" class="eu-save-btn"><i class="fa-solid fa-floppy-disk" style="margin-right:6px;"></i> Save Changes</button>
        </form>
        <button onclick="closeEditDrawer()" style="width:100%;margin-top:8px;padding:9px;border-radius:var(--r-sm);border:1px solid var(--hairline-strong);background:var(--surface);color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font-ui);">Cancel</button>
    </div>
</div>

{{-- ── REJECT DRIVER MODAL ── --}}
<div id="reject-modal" class="eu-backdrop" onclick="if(event.target===this)closeRejectModal()">
    <div class="eu-drawer">
        <div class="eu-pill"></div>
        <div class="eu-title" id="rj-title">Reject Driver Application</div>
        <div class="eu-sub">This reason is shown to the driver so they know what to fix.</div>
        <form id="rj-form" method="POST">
            @csrf @method('PATCH')
            <div class="eu-field">
                <label class="eu-label" for="rj-reason">Reason</label>
                <textarea id="rj-reason" name="reason" class="eu-select" rows="4"
                    placeholder="e.g. License photo is blurry — please re-upload a clearer photo." required></textarea>
            </div>
            <button type="submit" class="lr-reject-btn" style="width:100%; justify-content:center;">
                <i class="fa-solid fa-circle-xmark"></i> Confirm Rejection
            </button>
        </form>
        <button onclick="closeRejectModal()" style="width:100%;margin-top:8px;padding:9px;border-radius:var(--r-sm);border:1px solid var(--hairline-strong);background:var(--surface);color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font-ui);">Cancel</button>
    </div>
</div>

<script>
const ACOLS = ['#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981'];
function strCol(s){let h=0;for(let i=0;i<s.length;i++)h=s.charCodeAt(i)+((h<<5)-h);return ACOLS[Math.abs(h)%ACOLS.length];}

// Open full resolution base64 image in new tab safely without getting blocked as blank
function openFullImage(src) {
    if (!src || src.trim() === '') return;
    const win = window.open('');
    if (win) {
        win.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>View Document Image</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body { margin: 0; background: #0b1220; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: sans-serif; }
                    img { max-width: 95vw; max-height: 95vh; object-fit: contain; border-radius: 8px; box-shadow: 0 8px 32px rgba(0,0,0,0.5); }
                </style>
            </head>
            <body>
                <img src="${src}" alt="Document Image" />
            </body>
            </html>
        `);
        win.document.close();
    }
}

// Open modal from a thumbnail image element (reads data-* attrs)
function openLicenseFromEl(el){
    const lic = el.dataset.license || (el.alt === 'License' ? el.src : '');
    const sel = el.dataset.selfie  || (el.alt === 'Selfie'  ? el.src : '');
    openLicenseModal(
        el.dataset.uid,
        el.dataset.name,
        el.dataset.email,
        el.dataset.phone,
        el.dataset.vehicle,
        el.dataset.active,
        el.dataset.status,
        el.dataset.reason || '',
        lic,
        sel,
        el.dataset.joined,
        el.dataset.deactivationReason || ''
    );
}

// Open modal from a button element
function openLicenseFromBtn(btn){
    openLicenseModal(
        btn.dataset.uid,
        btn.dataset.name,
        btn.dataset.email,
        btn.dataset.phone,
        btn.dataset.vehicle,
        btn.dataset.active,
        btn.dataset.status,
        btn.dataset.reason || '',
        btn.dataset.license || '',
        btn.dataset.selfie || '',
        btn.dataset.joined,
        btn.dataset.deactivationReason || ''
    );
}

// Helper for opening by user ID
function openLicenseModalById(uid){
    const btn = document.querySelector('[data-lic-uid="'+uid+'"]');
    if(btn){ openLicenseFromEl(btn); }
}

// Tracks who the license modal currently shows, so the reject-reason modal
// (a separate overlay) and the "Suspend" hand-off to the edit drawer know who
// to act on when opened from here.
let lrCurrentUid = null;
let lrCurrentName = null;
let lrCurrentActive = '1';

function openLicenseModal(uid,name,email,phone,vehicle,active,status,reason,licenseImg,selfieImg,joined,deactivationReason){
    lrCurrentUid = uid;
    lrCurrentName = name;
    lrCurrentActive = active;

    document.getElementById('lr-name').textContent    = name;
    document.getElementById('lr-email').textContent   = email;
    document.getElementById('lr-phone').textContent   = phone;
    document.getElementById('lr-vehicle').textContent = vehicle;
    document.getElementById('lr-joined').textContent  = joined;
    const av = document.getElementById('lr-av');
    av.textContent = name.charAt(0).toUpperCase();
    av.style.background = strCol(name);

    // 1. Selfie Image Element
    const sImg = document.getElementById('lr-selfie-img');
    const sEmpty = document.getElementById('lr-selfie-empty');
    if (selfieImg && selfieImg.trim() !== '') {
        sImg.src = selfieImg;
        sImg.style.display = 'block';
        sEmpty.style.display = 'none';
    } else {
        sImg.src = '';
        sImg.style.display = 'none';
        sEmpty.style.display = 'flex';
    }

    // 2. License Image Element
    const lImg = document.getElementById('lr-license-img');
    const lEmpty = document.getElementById('lr-license-empty');
    if (licenseImg && licenseImg.trim() !== '') {
        lImg.src = licenseImg;
        lImg.style.display = 'block';
        lEmpty.style.display = 'none';
    } else {
        lImg.src = '';
        lImg.style.display = 'none';
        lEmpty.style.display = 'flex';
    }

    const base = '{{ url("/admin/users") }}/'+uid;
    document.getElementById('lr-approve-form').action = base + '/approve';
    document.getElementById('lr-reactivate-form').action = base;

    const approveBtn = document.getElementById('lr-approve-btn');
    const rejectBtn = document.getElementById('lr-reject-btn');
    const suspendBtn = document.getElementById('lr-suspend-btn');
    const reactivateForm = document.getElementById('lr-reactivate-form');
    const badge = document.getElementById('lr-badge');
    const stat  = document.getElementById('lr-status');
    const reasonRow = document.getElementById('lr-reason-row');
    const reasonLbl = document.getElementById('lr-reason-lbl');

    // Every button/form defaults hidden, then the branch below shows exactly
    // the actions valid for this driver's actual state (pending / approved /
    // approved-but-suspended / rejected) — the same 4-way split the table's
    // status pill uses, not just active/inactive.
    approveBtn.style.display = 'none';
    rejectBtn.style.display = 'none';
    suspendBtn.style.display = 'none';
    reactivateForm.style.display = 'none';
    reasonRow.style.display = 'none';

    if (status === 'rejected') {
        stat.innerHTML  = '<span class="status-pill status-rejected"><span class="dot-sm" style="background:#dc2626;"></span> Rejected</span>';
        badge.innerHTML = '<span class="status-pill status-rejected"><span class="dot-sm" style="background:#dc2626;"></span> Application Rejected</span>';
        approveBtn.style.display = 'inline-flex';
        approveBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Approve Anyway';
        if (reason) {
            reasonLbl.textContent = 'Rejection Reason';
            document.getElementById('lr-reason').textContent = reason;
            reasonRow.style.display = 'block';
        }
    } else if (status === 'approved' && active === '1') {
        stat.innerHTML  = '<span class="status-pill status-active"><span class="dot-sm" style="background:#16a34a;"></span> Active</span>';
        badge.innerHTML = '<span class="status-pill status-active"><span class="dot-sm" style="background:#16a34a;"></span> Account is Active</span>';
        suspendBtn.style.display = 'inline-flex';
    } else if (status === 'approved') {
        stat.innerHTML  = '<span class="status-pill status-inactive"><span class="dot-sm" style="background:var(--muted-2);"></span> Suspended</span>';
        badge.innerHTML = '<span class="status-pill status-inactive"><span class="dot-sm" style="background:var(--muted-2);"></span> Account Suspended</span>';
        reactivateForm.style.display = 'inline-flex';
        if (deactivationReason) {
            reasonLbl.textContent = 'Suspension Reason';
            document.getElementById('lr-reason').textContent = deactivationReason;
            reasonRow.style.display = 'block';
        }
    } else {
        stat.innerHTML  = '<span class="status-pill status-pending"><span class="dot-sm" style="background:#d97706;"></span> Pending</span>';
        badge.innerHTML = '<span class="status-pill status-pending"><span class="dot-sm" style="background:#d97706;"></span> Awaiting Approval</span>';
        approveBtn.style.display = 'inline-flex';
        approveBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Approve Driver';
        rejectBtn.style.display = 'inline-flex';
    }

    document.getElementById('license-modal').style.display='flex';
    document.body.style.overflow='hidden';
}
function closeLicenseModal(){
    document.getElementById('license-modal').style.display='none';
    document.body.style.overflow='';
}
function openRejectModalFromLicense(){
    if (!lrCurrentUid) return;
    openRejectModal(lrCurrentUid, lrCurrentName);
}

// "Suspend" in the license modal used to submit is_active=0 directly with no
// way to enter a reason — the server then rejects it (reason is required
// when deactivating an active account) with only a generic error banner to
// show for it. Hands off to the edit drawer instead, which already collects
// the reason and shows the character count.
function openEditDrawerFromLicense(){
    if (!lrCurrentUid) return;
    closeLicenseModal();
    openEditDrawer(lrCurrentUid, lrCurrentName, 'driver', lrCurrentActive);
}

let euWasActive = '1';

function openEditDrawer(uid,name,role,active){
    document.getElementById('eu-title').textContent = 'Edit: '+name;
    document.getElementById('eu-sub').textContent   = 'Change role or status for this user.';
    document.getElementById('eu-form').action = '{{ url("/admin/users") }}/'+uid;
    document.getElementById('eu-role').value   = role;
    document.getElementById('eu-status').value = active;
    document.getElementById('eu-reason').value = '';
    document.getElementById('eu-reason-count').textContent = '0 / 1000';
    document.getElementById('eu-suspended-until').value = '';
    setSuspendQuickPick(null, document.getElementById('eu-qp-permanent'));
    euWasActive = active;
    toggleEditReasonField();
    document.getElementById('edit-drawer').style.display='flex';
    document.body.style.overflow='hidden';
}
function closeEditDrawer(){
    document.getElementById('edit-drawer').style.display='none';
    document.body.style.overflow='';
}
function toggleEditReasonField(){
    var status = document.getElementById('eu-status').value;
    var reasonField = document.getElementById('eu-reason-field');
    var reasonInput = document.getElementById('eu-reason');
    var suspendUntilField = document.getElementById('eu-suspend-until-field');
    var isDeactivating = euWasActive === '1' && status === '0';
    reasonField.style.display = isDeactivating ? 'block' : 'none';
    reasonInput.required = isDeactivating;
    suspendUntilField.style.display = isDeactivating ? 'block' : 'none';
}

// datetime-local wants "YYYY-MM-DDTHH:mm" in local time, not the UTC ISO
// string toISOString() gives — build it from local getters instead so the
// picker shows the same wall-clock time the admin actually picked.
function setSuspendQuickPick(days, btn){
    var input = document.getElementById('eu-suspended-until');
    if (days === null) {
        input.value = '';
    } else {
        var d = new Date(Date.now() + days * 86400000);
        var pad = function(n){ return String(n).padStart(2,'0'); };
        input.value = d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes());
    }
    document.querySelectorAll('.eu-qp-btn').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
}
function clearSuspendQuickPickHighlight(){
    document.querySelectorAll('.eu-qp-btn').forEach(function(b){ b.classList.remove('active'); });
}

(function () {
    var reasonInput = document.getElementById('eu-reason');
    var reasonCount = document.getElementById('eu-reason-count');
    var sync = function () { reasonCount.textContent = reasonInput.value.length + ' / 1000'; };
    reasonInput.addEventListener('input', sync);
    sync();
})();

function openRejectModal(uid, name){
    closeLicenseModal();
    document.getElementById('rj-title').textContent = 'Reject: ' + name;
    document.getElementById('rj-form').action = '{{ url("/admin/users") }}/' + uid + '/reject';
    document.getElementById('rj-reason').value = '';
    document.getElementById('reject-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeRejectModal(){
    document.getElementById('reject-modal').style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeLicenseModal();closeEditDrawer();closeRejectModal();}});
</script>

@endsection
