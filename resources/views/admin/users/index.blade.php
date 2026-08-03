@extends('layouts.app')

@section('content')

@php
    use App\Models\User;
    $totalUsers     = User::count();
    $adminCount     = User::where('role', 'admin')->count();
    $driverCount    = User::where('role', 'driver')->count();
    $passengerCount = User::where('role', 'passenger')->count();
    $pendingDrivers = User::where('role', 'driver')->where('is_active', false)->get();
    $pendingCount   = $pendingDrivers->count();
@endphp

{{-- Styles extracted to a cacheable static file; link kept at the same position for identical cascade order. --}}
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">

<div class="au-page">

{{-- Page Header --}}
<div>
    <p class="au-eyebrow">Admin Panel</p>
    <h1 class="au-title">User Management</h1>
    <p class="au-sub">Manage accounts, approve drivers, and control access.</p>
</div>

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
                <form method="POST" action="{{ route('admin.users.update', $pd) }}" style="display:inline;">
                    @csrf @method('PATCH')
                    <input type="hidden" name="role" value="driver">
                    <input type="hidden" name="is_active" value="1">
                    <button type="submit" class="btn-approve"><i class="fa-solid fa-circle-check"></i> Approve</button>
                </form>
                <form method="POST" action="{{ route('admin.users.update', $pd) }}" style="display:inline;">
                    @csrf @method('PATCH')
                    <input type="hidden" name="role" value="driver">
                    <input type="hidden" name="is_active" value="0">
                    <button type="submit" class="btn-reject"
                        onclick="return confirm('Reject driver application for {{ addslashes($pd->name) }}?')"
                    ><i class="fa-solid fa-circle-xmark"></i> Reject</button>
                </form>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endif

{{-- Filter bar --}}
<div class="au-filter-card">
    <form method="GET" action="{{ route('admin.users.index') }}">
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
                <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                <i class="fa-solid fa-filter" style="font-size:11px;"></i> Filter
            </button>
        </div>
        @if(request('q') || request('role') || (request('active') !== null && request('active') !== ''))
            <div style="margin-top:8px;">
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">
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
                        @if($user->is_active)
                            <span class="status-pill status-active"><span class="dot-sm" style="background:#16a34a;"></span> Active</span>
                        @elseif($user->role === 'driver')
                            <span class="status-pill status-pending"><span class="dot-sm" style="background:#d97706;"></span> Pending</span>
                        @else
                            <span class="status-pill status-inactive"><span class="dot-sm" style="background:var(--muted-2);"></span> Inactive</span>
                        @endif
                    </td>
                    <td style="font-size:13px;color:var(--muted);white-space:nowrap;">{{ $user->created_at?->format('d M Y') ?? 'â€”' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;justify-content:flex-end;flex-wrap:wrap;">
                            @if($user->role === 'driver' && ($user->driving_license_photo || $user->selfie_photo))
                                @php $uVehicle = trim(($user->vehicle_model??'').' '.($user->vehicle_plate??'')) ?: '—'; @endphp
                                <button type="button" class="au-qbtn"
                                    style="background:var(--info-soft);color:var(--info-ink);border-color:rgba(37,99,235,.2);"
                                    data-uid="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-phone="{{ $user->phone ?? '—' }}"
                                    data-vehicle="{{ $uVehicle }}"
                                    data-active="{{ $user->is_active ? '1' : '0' }}"
                                    data-joined="{{ $user->created_at?->format('d M Y') ?? '—' }}"
                                    data-license="{{ $user->driving_license_photo ?? '' }}"
                                    data-selfie="{{ $user->selfie_photo ?? '' }}"
                                    onclick="openLicenseFromBtn(this)"
                                ><i class="fa-solid fa-id-card" style="font-size:11px;"></i> License</button>
                            @endif
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
                <div style="margin-top:14px;"><a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">Clear Filters</a></div>
            @endif
        </div>
    @endforelse
    @if($users->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--hairline);display:flex;justify-content:flex-end;">
            {{ $users->links() }}
        </div>
    @endif
</div>

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
                <form id="lr-reject-form" method="POST" style="display:inline;">
                    @csrf @method('PATCH')
                    <input type="hidden" name="role" value="driver">
                    <input type="hidden" name="is_active" value="0">
                    <button type="submit" class="lr-reject-btn" id="lr-reject-btn"><i class="fa-solid fa-circle-xmark"></i> Reject</button>
                </form>
                <form id="lr-approve-form" method="POST" style="display:inline;">
                    @csrf @method('PATCH')
                    <input type="hidden" name="role" value="driver">
                    <input type="hidden" name="is_active" value="1">
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
                <select id="eu-status" name="is_active" class="eu-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive / Suspended</option>
                </select>
            </div>
            <button type="submit" class="eu-save-btn"><i class="fa-solid fa-floppy-disk" style="margin-right:6px;"></i> Save Changes</button>
        </form>
        <button onclick="closeEditDrawer()" style="width:100%;margin-top:8px;padding:9px;border-radius:var(--r-sm);border:1px solid var(--hairline-strong);background:var(--surface);color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font-ui);">Cancel</button>
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
        lic,
        sel,
        el.dataset.joined
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
        btn.dataset.license || '',
        btn.dataset.selfie || '',
        btn.dataset.joined
    );
}

// Helper for opening by user ID
function openLicenseModalById(uid){
    const btn = document.querySelector('[data-lic-uid="'+uid+'"]');
    if(btn){ openLicenseFromEl(btn); }
}

function openLicenseModal(uid,name,email,phone,vehicle,active,licenseImg,selfieImg,joined){
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
    document.getElementById('lr-approve-form').action = base;
    document.getElementById('lr-reject-form').action  = base;
    const ab = document.getElementById('lr-approve-btn');
    const rb = document.getElementById('lr-reject-btn');
    const badge = document.getElementById('lr-badge');
    const stat  = document.getElementById('lr-status');
    if(active==='1'){
        stat.innerHTML  = '<span class="status-pill status-active"><span class="dot-sm" style="background:#16a34a;"></span> Active</span>';
        badge.innerHTML = '<span class="status-pill status-active"><span class="dot-sm" style="background:#16a34a;"></span> Account is Active</span>';
        ab.style.display='none'; rb.style.display='inline-flex';
        rb.innerHTML='<i class="fa-solid fa-circle-minus"></i> Deactivate';
    } else {
        stat.innerHTML  = '<span class="status-pill status-pending"><span class="dot-sm" style="background:#d97706;"></span> Pending</span>';
        badge.innerHTML = '<span class="status-pill status-pending"><span class="dot-sm" style="background:#d97706;"></span> Awaiting Approval</span>';
        ab.style.display='inline-flex'; rb.style.display='inline-flex';
        rb.innerHTML='<i class="fa-solid fa-circle-xmark"></i> Reject';
    }
    document.getElementById('license-modal').style.display='flex';
    document.body.style.overflow='hidden';
}
function closeLicenseModal(){
    document.getElementById('license-modal').style.display='none';
    document.body.style.overflow='';
}

function openEditDrawer(uid,name,role,active){
    document.getElementById('eu-title').textContent = 'Edit: '+name;
    document.getElementById('eu-sub').textContent   = 'Change role or status for this user.';
    document.getElementById('eu-form').action = '{{ url("/admin/users") }}/'+uid;
    document.getElementById('eu-role').value   = role;
    document.getElementById('eu-status').value = active;
    document.getElementById('edit-drawer').style.display='flex';
    document.body.style.overflow='hidden';
}
function closeEditDrawer(){
    document.getElementById('edit-drawer').style.display='none';
    document.body.style.overflow='';
}

document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeLicenseModal();closeEditDrawer();}});
</script>

@endsection
