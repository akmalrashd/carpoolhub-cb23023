@extends('layouts.app')

@section('content')

@php
    $hasActiveFilters = collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $todayCount = $logs->getCollection()->filter(fn ($log) => $log->created_at?->isToday())->count();
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin-audit-log.css') }}?v={{ filemtime(public_path('css/admin-audit-log.css')) }}">
@endpush

<div class="au-page">

<div>
    <p class="au-eyebrow">Admin Panel</p>
    <h1 class="au-title">Audit Log</h1>
    <p class="au-sub">Every action taken by an admin — role/status changes, driver approvals, payment overrides, messages sent, and settings updates.</p>
</div>

{{-- Stats --}}
<div class="au-stats">
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--ch-yellow-tint);border:1px solid var(--ch-yellow-line);">
            <i class="fa-solid fa-clipboard-list" style="color:var(--ch-yellow-ink);"></i>
        </div>
        <div class="au-stat-val">{{ $logs->total() }}</div>
        <div class="au-stat-lbl">Total Actions</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--info-soft);border:1px solid rgba(37,99,235,.2);">
            <i class="fa-regular fa-clock" style="color:var(--info-ink);"></i>
        </div>
        <div class="au-stat-val">{{ $todayCount }}</div>
        <div class="au-stat-lbl">On This Page, Today</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--surface-2);border:1px solid var(--hairline-strong);">
            <i class="fa-solid fa-user-shield" style="color:var(--muted);"></i>
        </div>
        <div class="au-stat-val">{{ $adminOptions->count() }}</div>
        <div class="au-stat-lbl">Admins With Activity</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:#fef2f2;border:1px solid rgba(220,38,38,.2);">
            <i class="fa-solid fa-tags" style="color:#dc2626;"></i>
        </div>
        <div class="au-stat-val">{{ $actionOptions->count() }}</div>
        <div class="au-stat-lbl">Distinct Action Types</div>
    </div>
</div>

{{-- Filter bar --}}
<div class="au-filter-card">
    <form method="GET" action="{{ route('admin.audit-log.index') }}">
        <div class="al-filter-row">
            <div class="al-filter-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search description, action, or admin name…" class="au-input" style="padding-left:34px;">
            </div>
        </div>
        <div class="al-filter-row al-filter-row-controls">
            <select name="action" class="au-select">
                <option value="">All action types</option>
                @foreach($actionOptions as $actionOption)
                    <option value="{{ $actionOption }}" {{ ($filters['action'] ?? '') === $actionOption ? 'selected' : '' }}>{{ $actionOption }}</option>
                @endforeach
            </select>
            <select name="admin_id" class="au-select">
                <option value="">All admins</option>
                @foreach($adminOptions as $adminOption)
                    <option value="{{ $adminOption->id }}" {{ (string) ($filters['admin_id'] ?? '') === (string) $adminOption->id ? 'selected' : '' }}>{{ $adminOption->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="au-select" title="From date">
            <span class="al-filter-to">to</span>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="au-select" title="To date">
            <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                <i class="fa-solid fa-filter" style="font-size:11px;"></i> Filter
            </button>
        </div>
        @if($hasActiveFilters)
            <div style="margin-top:8px;">
                <a href="{{ route('admin.audit-log.index') }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">
                    <i class="fa-solid fa-xmark" style="font-size:11px;"></i> Clear Filters
                </a>
            </div>
        @endif
    </form>
</div>

<div style="background:var(--surface);border:1px solid var(--hairline);border-radius:var(--r-md);overflow:hidden;">
    @forelse($logs as $log)
        @if($loop->first)
            <div class="au-table-wrap">
            <table class="au-table">
                <thead><tr>
                    <th>When</th><th>Admin</th><th>Action</th><th>Target</th><th>Detail</th>
                </tr></thead>
                <tbody>
        @endif
                @php [$badgeClass, $badgeIcon, $badgeLabel] = $log->badge; @endphp
                <tr>
                    <td style="font-size:13px;color:var(--muted);white-space:nowrap;">
                        {{ $log->created_at?->format('d M Y, h:i A') }}
                        <div class="t-xs text-muted">{{ $log->created_at?->diffForHumans() }}</div>
                    </td>
                    <td style="font-weight:600;">{{ $log->admin?->name ?? 'Unknown' }}</td>
                    <td>
                        <span class="badge {{ $badgeClass }}" title="{{ $badgeLabel }}"><i class="fa-solid {{ $badgeIcon }}"></i> {{ $log->action }}</span>
                    </td>
                    <td style="font-size:13px;color:var(--muted);">
                        @if($log->target_type)
                            {{ ucfirst($log->target_type) }} #{{ $log->target_id }}
                        @else
                            —
                        @endif
                    </td>
                    <td style="font-size:13px;color:var(--ink-2);max-width:360px;">{{ $log->description ?? '—' }}</td>
                </tr>
        @if($loop->last)
                </tbody></table>
            </div>
        @endif
    @empty
        <div style="padding:56px 24px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:var(--r-md);background:var(--surface-2);border:1px solid var(--hairline-strong);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                <i class="fa-solid fa-clipboard-list" style="font-size:22px;color:var(--muted-2);"></i>
            </div>
            @if($hasActiveFilters)
                <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--ink);margin-bottom:6px;">No actions match your filters</div>
                <div style="font-size:14px;color:var(--muted);">Try widening the date range or clearing a filter.</div>
                <div style="margin-top:14px;"><a href="{{ route('admin.audit-log.index') }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">Clear Filters</a></div>
            @else
                <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--ink);margin-bottom:6px;">No admin actions logged yet</div>
                <div style="font-size:14px;color:var(--muted);">Actions like role changes, suspensions, and payment overrides will show up here.</div>
            @endif
        </div>
    @endforelse
    @if($logs->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--hairline);display:flex;justify-content:flex-end;">
            {{ $logs->links() }}
        </div>
    @endif
</div>

</div>{{-- /au-page --}}

@endsection
