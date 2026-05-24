@extends('layouts.app')

@section('content')

@php
    use App\Models\User;
    $totalUsers    = User::count();
    $adminCount    = User::where('role', 'admin')->count();
    $driverCount   = User::where('role', 'driver')->count();
    $passengerCount= User::where('role', 'passenger')->count();
@endphp

{{-- Page header --}}
<div style="margin-bottom: 20px;">
    <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.10em; text-transform: uppercase; color: var(--muted); margin-bottom: 4px; font-family: var(--font-display);">
        Admin Panel
    </div>
    <h1 style="margin: 0; font-family: var(--font-display); font-size: clamp(22px, 5vw, 28px); font-weight: 700; color: var(--ink); line-height: 1.2;">
        Users
    </h1>
</div>

{{-- Error banner --}}
@if($errors->any())
    <div style="margin-bottom: 14px; padding: 12px 14px; border-radius: var(--r-md); border: 1px solid rgba(220,38,38,0.28); background: var(--danger-soft); color: var(--danger-ink); font-size: 14px; font-weight: 500;">
        <i class="fa-solid fa-circle-exclamation" style="margin-right: 6px;"></i>{{ $errors->first() }}
    </div>
@endif

{{-- Stats strip --}}
<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 18px;">
    {{-- Total --}}
    <div class="app-card" style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: var(--r-sm); background: var(--ch-yellow-tint); border: 1px solid var(--ch-yellow-line); display: grid; place-items: center; flex-shrink: 0;">
            <i class="fa-solid fa-users" style="color: var(--ch-yellow-ink); font-size: 15px;"></i>
        </div>
        <div>
            <div style="font-size: 22px; font-weight: 700; font-family: var(--font-display); color: var(--ink); line-height: 1;">{{ $totalUsers }}</div>
            <div style="font-size: 12px; color: var(--muted); margin-top: 2px; font-weight: 500;">Total Users</div>
        </div>
    </div>
    {{-- Admins --}}
    <div class="app-card" style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: var(--r-sm); background: var(--danger-soft); border: 1px solid rgba(220,38,38,0.2); display: grid; place-items: center; flex-shrink: 0;">
            <i class="fa-solid fa-user-shield" style="color: var(--danger-ink); font-size: 15px;"></i>
        </div>
        <div>
            <div style="font-size: 22px; font-weight: 700; font-family: var(--font-display); color: var(--ink); line-height: 1;">{{ $adminCount }}</div>
            <div style="font-size: 12px; color: var(--muted); margin-top: 2px; font-weight: 500;">Admins</div>
        </div>
    </div>
    {{-- Drivers --}}
    <div class="app-card" style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: var(--r-sm); background: var(--info-soft); border: 1px solid rgba(37,99,235,0.2); display: grid; place-items: center; flex-shrink: 0;">
            <i class="fa-solid fa-car" style="color: var(--info-ink); font-size: 15px;"></i>
        </div>
        <div>
            <div style="font-size: 22px; font-weight: 700; font-family: var(--font-display); color: var(--ink); line-height: 1;">{{ $driverCount }}</div>
            <div style="font-size: 12px; color: var(--muted); margin-top: 2px; font-weight: 500;">Drivers</div>
        </div>
    </div>
    {{-- Passengers --}}
    <div class="app-card" style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: var(--r-sm); background: var(--surface-2); border: 1px solid var(--hairline-strong); display: grid; place-items: center; flex-shrink: 0;">
            <i class="fa-solid fa-user" style="color: var(--muted); font-size: 15px;"></i>
        </div>
        <div>
            <div style="font-size: 22px; font-weight: 700; font-family: var(--font-display); color: var(--ink); line-height: 1;">{{ $passengerCount }}</div>
            <div style="font-size: 12px; color: var(--muted); margin-top: 2px; font-weight: 500;">Passengers</div>
        </div>
    </div>
</div>

{{-- Search / filter bar --}}
<div class="app-card" style="margin-bottom: 14px;">
    <form method="GET" action="{{ route('admin.users.index') }}">
        <div style="display: grid; gap: 10px;">
            {{-- Search --}}
            <div style="position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 13px; pointer-events: none;"></i>
                <input
                    id="q"
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search by name, email or phone…"
                    style="width: 100%; border: 1px solid var(--hairline-strong); border-radius: var(--r-sm); padding: 9px 12px 9px 34px; font-size: 14px; color: var(--ink); background: var(--surface); outline: none; font-family: var(--font-ui);"
                    onfocus="this.style.borderColor='var(--ch-yellow-deep)';this.style.boxShadow='0 0 0 3px rgba(250,204,21,0.20)'"
                    onblur="this.style.borderColor='var(--hairline-strong)';this.style.boxShadow='none'"
                >
            </div>

            {{-- Role + Status + button row --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; align-items: end;">
                {{-- Role filter --}}
                <div>
                    <label for="role" style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 4px;">Role</label>
                    <select
                        id="role"
                        name="role"
                        style="width: 100%; border: 1px solid var(--hairline-strong); border-radius: var(--r-sm); padding: 8px 10px; font-size: 13px; color: var(--ink); background: var(--surface); font-family: var(--font-ui); outline: none;"
                    >
                        <option value="">All Roles</option>
                        @foreach(['admin', 'driver', 'passenger'] as $role)
                            <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status filter --}}
                <div>
                    <label for="active" style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 4px;">Status</label>
                    <select
                        id="active"
                        name="active"
                        style="width: 100%; border: 1px solid var(--hairline-strong); border-radius: var(--r-sm); padding: 8px 10px; font-size: 13px; color: var(--ink); background: var(--surface); font-family: var(--font-ui); outline: none;"
                    >
                        <option value="">All Status</option>
                        <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                {{-- Filter button --}}
                <button type="submit" class="btn btn-primary btn-sm" style="white-space: nowrap;">
                    <i class="fa-solid fa-filter" style="font-size: 11px;"></i> Filter
                </button>
            </div>

            {{-- Clear filters link (shown when any filter is active) --}}
            @if(request('q') || request('role') || request('active') !== null && request('active') !== '')
                <div>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm" style="text-decoration: none;">
                        <i class="fa-solid fa-xmark" style="font-size: 11px;"></i> Clear Filters
                    </a>
                </div>
            @endif
        </div>
    </form>
</div>

{{-- ── DESKTOP TABLE ── --}}
<div class="app-card" style="padding: 0; overflow: hidden;">

    @forelse($users as $user)
        @if($loop->first)
            {{-- Table header (desktop only) --}}
            <div style="display: none;" class="users-table-head">
                {{-- rendered via CSS below --}}
            </div>
            <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 640px;">
                <thead>
                    <tr style="background: var(--surface-2); border-bottom: 1px solid var(--hairline);">
                        <th style="text-align: left; padding: 11px 16px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); white-space: nowrap;">User</th>
                        <th style="text-align: left; padding: 11px 16px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted);">Role</th>
                        <th style="text-align: left; padding: 11px 16px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted);">Status</th>
                        <th style="text-align: left; padding: 11px 16px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted);">Joined</th>
                        <th style="text-align: right; padding: 11px 16px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted);">Actions</th>
                    </tr>
                </thead>
                <tbody>
        @endif

                    <tr style="border-bottom: 1px solid var(--hairline); transition: background 0.1s ease;" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">
                        {{-- Avatar + Name + Email --}}
                        <td style="padding: 12px 16px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 36px; height: 36px; border-radius: 999px; background: var(--ink); color: #fff; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; font-family: var(--font-display);">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div style="min-width: 0;">
                                    <div style="font-weight: 600; font-size: 14px; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $user->name }}</div>
                                    <div style="font-size: 12px; color: var(--muted); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $user->email }}</div>
                                    @if($user->phone)
                                        <div style="font-size: 12px; color: var(--muted-2); margin-top: 1px;">{{ $user->phone }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Role badge --}}
                        <td style="padding: 12px 16px; white-space: nowrap;">
                            @if($user->role === 'admin')
                                <span class="badge badge-danger"><span class="dot"></span>Admin</span>
                            @elseif($user->role === 'driver')
                                <span class="badge badge-info"><span class="dot"></span>Driver</span>
                            @else
                                <span class="badge">Passenger</span>
                            @endif
                        </td>

                        {{-- Status badge --}}
                        <td style="padding: 12px 16px; white-space: nowrap;">
                            @if($user->is_active)
                                <span class="badge badge-success"><span class="dot"></span>Active</span>
                            @else
                                <span class="badge"><span class="dot" style="background: var(--muted-2);"></span>Inactive</span>
                            @endif
                        </td>

                        {{-- Joined date --}}
                        <td style="padding: 12px 16px; font-size: 13px; color: var(--muted); white-space: nowrap;">
                            {{ $user->created_at?->format('d M Y') ?? '—' }}
                        </td>

                        {{-- Actions --}}
                        <td style="padding: 12px 16px; text-align: right;">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}" style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end; flex-wrap: wrap;">
                                @csrf
                                @method('PATCH')
                                <select
                                    name="role"
                                    aria-label="Role for {{ $user->name }}"
                                    style="border: 1px solid var(--hairline-strong); border-radius: var(--r-sm); padding: 6px 8px; font-size: 12px; color: var(--ink); background: var(--surface); font-family: var(--font-ui); outline: none; width: 110px;"
                                >
                                    @foreach(['admin', 'driver', 'passenger'] as $role)
                                        <option value="{{ $role }}" {{ $user->role === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                                    @endforeach
                                </select>
                                <select
                                    name="is_active"
                                    aria-label="Status for {{ $user->name }}"
                                    style="border: 1px solid var(--hairline-strong); border-radius: var(--r-sm); padding: 6px 8px; font-size: 12px; color: var(--ink); background: var(--surface); font-family: var(--font-ui); outline: none; width: 100px;"
                                >
                                    <option value="1" {{ $user->is_active ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ ! $user->is_active ? 'selected' : '' }}>Inactive</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            </form>
                        </td>
                    </tr>

        @if($loop->last)
                </tbody>
            </table>
            </div>
        @endif

    @empty
        {{-- Empty state --}}
        <div style="padding: 56px 24px; text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: var(--r-md); background: var(--surface-2); border: 1px solid var(--hairline-strong); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                <i class="fa-solid fa-users" style="font-size: 22px; color: var(--muted-2);"></i>
            </div>
            <div style="font-size: 16px; font-weight: 700; font-family: var(--font-display); color: var(--ink); margin-bottom: 6px;">No users found</div>
            <div style="font-size: 14px; color: var(--muted);">Try adjusting your search or filters.</div>
            @if(request('q') || request('role') || request('active') !== null && request('active') !== '')
                <div style="margin-top: 14px;">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm" style="text-decoration: none;">Clear Filters</a>
                </div>
            @endif
        </div>
    @endforelse

    {{-- Pagination --}}
    @if($users->hasPages())
        <div style="padding: 12px 16px; border-top: 1px solid var(--hairline); display: flex; justify-content: flex-end;">
            {{ $users->links() }}
        </div>
    @endif
</div>

{{-- ── MOBILE CARD LIST ── --}}
{{-- Shown only on small screens via inline media query scoping via a style block --}}
<style>
    .users-desktop-table { display: block; }
    .users-mobile-list   { display: none; }

    @media (max-width: 639px) {
        /* On narrow screens the horizontal scroll table is kept but
           we also ensure the action form wraps nicely. */
        .user-action-form {
            flex-direction: column;
            align-items: stretch !important;
        }
        .user-action-form select,
        .user-action-form button {
            width: 100% !important;
        }
    }

    /* Pagination overrides for CarpoolHub tokens */
    nav[role="navigation"] {
        display: flex;
        justify-content: flex-end;
    }
    nav[role="navigation"] span,
    nav[role="navigation"] a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 6px;
        border-radius: var(--r-sm);
        border: 1px solid var(--hairline-strong);
        font-size: 13px;
        font-weight: 600;
        color: var(--ink);
        background: var(--surface);
        text-decoration: none;
        margin: 0 2px;
        transition: background .12s, border-color .12s;
    }
    nav[role="navigation"] a:hover {
        background: var(--surface-2);
    }
    nav[role="navigation"] span[aria-current="page"] {
        background: var(--ch-yellow);
        border-color: var(--ch-yellow-deep);
        color: var(--ch-yellow-ink);
    }
    nav[role="navigation"] span.dots {
        border-color: transparent;
        background: transparent;
    }
</style>

@endsection
