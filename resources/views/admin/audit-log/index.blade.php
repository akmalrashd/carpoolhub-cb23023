@extends('layouts.app')

@section('content')

@php
    $hasActiveFilters = collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $todayCount = $view === 'admin' ? $logs->getCollection()->filter(fn ($log) => $log->created_at?->isToday())->count() : 0;
    $roleBadge = fn (?string $role) => match ($role) {
        'admin' => ['role-admin', 'fa-user-shield'],
        'driver' => ['role-driver', 'fa-car'],
        default => ['role-passenger', 'fa-user'],
    };
    $statusBadge = fn (string $status) => match ($status) {
        'paid' => ['status-active', 'Paid'],
        'pending_confirmation' => ['status-pending', 'Pending'],
        default => ['status-rejected', 'Unpaid'],
    };
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin-audit-log.css') }}?v={{ filemtime(public_path('css/admin-audit-log.css')) }}">
@endpush

<div class="au-page">

<div>
    <p class="au-eyebrow">Admin Panel</p>
    <h1 class="au-title">Audit Log</h1>
    <p class="au-sub">Accountability and dispute-evidence trail: admin actions, payment status changes, and trip cancellations.</p>
</div>

@include('layouts.partials.admin-subnav')

{{-- In-page view tabs: same dataset-switcher idea as admin-subnav, but
     switching ?view= on this one route instead of navigating between routes. --}}
<nav class="subview-tabs">
    <a href="{{ route('admin.audit-log.index', ['view' => 'admin']) }}" class="{{ $view === 'admin' ? 'active' : '' }}">
        <i class="fa-solid fa-user-shield"></i> Admin Actions <span class="subview-count">{{ $viewCounts['admin'] }}</span>
    </a>
    <a href="{{ route('admin.audit-log.index', ['view' => 'payments']) }}" class="{{ $view === 'payments' ? 'active' : '' }}">
        <i class="fa-solid fa-wallet"></i> Payment History <span class="subview-count">{{ $viewCounts['payments'] }}</span>
    </a>
    <a href="{{ route('admin.audit-log.index', ['view' => 'cancellations']) }}" class="{{ $view === 'cancellations' ? 'active' : '' }}">
        <i class="fa-solid fa-calendar-xmark"></i> Trip Cancellations <span class="subview-count">{{ $viewCounts['cancellations'] }}</span>
    </a>
</nav>

{{-- Stats --}}
@if($view === 'admin')
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
@elseif($view === 'payments')
<div class="au-stats">
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--ch-yellow-tint);border:1px solid var(--ch-yellow-line);">
            <i class="fa-solid fa-clock-rotate-left" style="color:var(--ch-yellow-ink);"></i>
        </div>
        <div class="au-stat-val">{{ $stats['total'] }}</div>
        <div class="au-stat-lbl">Total Status Changes</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--info-soft);border:1px solid rgba(37,99,235,.2);">
            <i class="fa-regular fa-clock" style="color:var(--info-ink);"></i>
        </div>
        <div class="au-stat-val">{{ $stats['today'] }}</div>
        <div class="au-stat-lbl">Today</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:#f0fdf4;border:1px solid #bbf7d0;">
            <i class="fa-solid fa-circle-check" style="color:#15803d;"></i>
        </div>
        <div class="au-stat-val">{{ $stats['marked_paid'] }}</div>
        <div class="au-stat-lbl">Marked Paid</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:#fef2f2;border:1px solid rgba(220,38,38,.2);">
            <i class="fa-solid fa-circle-xmark" style="color:#dc2626;"></i>
        </div>
        <div class="au-stat-val">{{ $stats['rejected_or_reversed'] }}</div>
        <div class="au-stat-lbl">Rejected / Reversed</div>
    </div>
</div>
@else
<div class="au-stats">
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--ch-yellow-tint);border:1px solid var(--ch-yellow-line);">
            <i class="fa-solid fa-calendar-xmark" style="color:var(--ch-yellow-ink);"></i>
        </div>
        <div class="au-stat-val">{{ $stats['total'] }}</div>
        <div class="au-stat-lbl">Total Cancellations</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--info-soft);border:1px solid rgba(37,99,235,.2);">
            <i class="fa-regular fa-clock" style="color:var(--info-ink);"></i>
        </div>
        <div class="au-stat-val">{{ $stats['today'] }}</div>
        <div class="au-stat-lbl">Today</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:var(--surface-2);border:1px solid var(--hairline-strong);">
            <i class="fa-solid fa-comment-dots" style="color:var(--muted);"></i>
        </div>
        <div class="au-stat-val">{{ $stats['with_reason'] }}</div>
        <div class="au-stat-lbl">With Reason Given</div>
    </div>
    <div class="au-stat-card">
        <div class="au-stat-icon" style="background:#fef2f2;border:1px solid rgba(220,38,38,.2);">
            <i class="fa-solid fa-car" style="color:#dc2626;"></i>
        </div>
        <div class="au-stat-val">{{ $stats['distinct_drivers'] }}</div>
        <div class="au-stat-lbl">Drivers Involved</div>
    </div>
</div>
@endif

{{-- Filter bar --}}
<div class="au-filter-card">
    <form method="GET" action="{{ route('admin.audit-log.index') }}">
        <input type="hidden" name="view" value="{{ $view }}">
        <div class="al-filter-row">
            <div class="al-filter-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $view === 'admin' ? 'Search description, action, or admin name…' : 'Search reason or name…' }}" class="au-input" style="padding-left:34px;">
            </div>
        </div>
        <div class="al-filter-row al-filter-row-controls">
            @if($view === 'admin')
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
            @elseif($view === 'payments')
                <select name="to_status" class="au-select">
                    <option value="">All outcomes</option>
                    <option value="paid" {{ ($filters['to_status'] ?? '') === 'paid' ? 'selected' : '' }}>Marked Paid</option>
                    <option value="pending_confirmation" {{ ($filters['to_status'] ?? '') === 'pending_confirmation' ? 'selected' : '' }}>Submitted for Review</option>
                    <option value="unpaid" {{ ($filters['to_status'] ?? '') === 'unpaid' ? 'selected' : '' }}>Rejected / Reversed</option>
                </select>
            @endif
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="au-select" title="From date">
            <span class="al-filter-to">to</span>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="au-select" title="To date">
            <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                <i class="fa-solid fa-filter" style="font-size:11px;"></i> Filter
            </button>
        </div>
        @if($hasActiveFilters)
            <div style="margin-top:8px;">
                <a href="{{ route('admin.audit-log.index', ['view' => $view]) }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">
                    <i class="fa-solid fa-xmark" style="font-size:11px;"></i> Clear Filters
                </a>
            </div>
        @endif
    </form>
</div>

<div style="background:var(--surface);border:1px solid var(--hairline);border-radius:var(--r-md);overflow:hidden;">
@if($view === 'admin')
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
                    <td style="font-weight:600;">
                        {{ $log->admin?->name ?? 'System' }}
                        @if(!$log->admin_id)<span class="t-xs text-muted" style="display:block;font-weight:500;">Automated</span>@endif
                    </td>
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
                <div style="margin-top:14px;"><a href="{{ route('admin.audit-log.index', ['view' => $view]) }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">Clear Filters</a></div>
            @else
                <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--ink);margin-bottom:6px;">No admin actions logged yet</div>
                <div style="font-size:14px;color:var(--muted);">Actions like role changes, suspensions, and payment overrides will show up here.</div>
            @endif
        </div>
    @endforelse
@elseif($view === 'payments')
    @forelse($logs as $log)
        @if($loop->first)
            <div class="au-table-wrap">
            <table class="au-table">
                <thead><tr>
                    <th>When</th><th>Payer</th><th>Change</th><th>Actor</th><th>Reason</th><th>Previously</th>
                </tr></thead>
                <tbody>
        @endif
                @php
                    [$toClass, $toLabel] = $statusBadge($log->to_status);
                    [$fromClass, $fromLabel] = $statusBadge($log->from_status);
                    [$actorRoleClass, $actorRoleIcon] = $roleBadge($log->actor_role);
                    $prev = $log->previous_state ?? [];
                    $prevBits = collect([
                        $prev['payment_method'] ?? null,
                        !empty($prev['remarks']) ? \Illuminate\Support\Str::limit($prev['remarks'], 40) : null,
                        !empty($prev['marked_paid_at']) ? 'marked ' . \Illuminate\Support\Carbon::parse($prev['marked_paid_at'])->format('d M, h:ia') : null,
                    ])->filter();
                @endphp
                <tr>
                    <td style="font-size:13px;color:var(--muted);white-space:nowrap;">
                        {{ $log->created_at?->format('d M Y, h:i A') }}
                        <div class="t-xs text-muted">{{ $log->created_at?->diffForHumans() }}</div>
                    </td>
                    <td style="font-weight:600;">{{ $log->payer?->name ?? 'Unknown' }}<div class="t-xs text-muted">RM {{ number_format((float) $log->amount_due, 2) }}</div></td>
                    <td style="white-space:nowrap;">
                        <span class="status-pill {{ $fromClass }}">{{ $fromLabel }}</span>
                        <i class="fa-solid fa-arrow-right" style="font-size:10px;color:var(--muted-2);margin:0 4px;"></i>
                        <span class="status-pill {{ $toClass }}">{{ $toLabel }}</span>
                    </td>
                    <td style="font-size:13px;">
                        {{ $log->actor?->name ?? 'Unknown' }}
                        <span class="role-pill {{ $actorRoleClass }}" style="margin-left:4px;"><i class="fa-solid {{ $actorRoleIcon }}" style="font-size:9px;"></i> {{ ucfirst($log->actor_role ?? '—') }}</span>
                    </td>
                    <td style="font-size:13px;color:var(--ink-2);max-width:260px;">{{ $log->reason ?? '—' }}</td>
                    <td style="font-size:12px;color:var(--muted);max-width:220px;">{{ $prevBits->isNotEmpty() ? $prevBits->implode(' · ') : '—' }}</td>
                </tr>
        @if($loop->last)
                </tbody></table>
            </div>
        @endif
    @empty
        <div style="padding:56px 24px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:var(--r-md);background:var(--surface-2);border:1px solid var(--hairline-strong);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                <i class="fa-solid fa-wallet" style="font-size:22px;color:var(--muted-2);"></i>
            </div>
            @if($hasActiveFilters)
                <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--ink);margin-bottom:6px;">No changes match your filters</div>
                <div style="font-size:14px;color:var(--muted);">Try widening the date range or clearing a filter.</div>
                <div style="margin-top:14px;"><a href="{{ route('admin.audit-log.index', ['view' => $view]) }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">Clear Filters</a></div>
            @else
                <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--ink);margin-bottom:6px;">No payment status changes logged yet</div>
                <div style="font-size:14px;color:var(--muted);">Marking, confirming, rejecting, or reversing a payment will show up here.</div>
            @endif
        </div>
    @endforelse
@else
    @forelse($logs as $log)
        @if($loop->first)
            <div class="au-table-wrap">
            <table class="au-table">
                <thead><tr>
                    <th>When</th><th>Trip</th><th>Driver</th><th>Cancelled By</th><th>Reason</th><th>Affected</th>
                </tr></thead>
                <tbody>
        @endif
                @php
                    [$cancellerRoleClass, $cancellerRoleIcon] = $roleBadge($log->cancelled_by_role);
                    $snap = $log->trip_snapshot ?? [];
                    $routeLabel = trim(($snap['pickup_name'] ?? 'Pickup') . ' → ' . ($snap['destination_name'] ?? 'Destination'));
                    $participantCount = count($log->participants_snapshot ?? []);
                    $paymentCount = count($log->payments_snapshot ?? []);
                @endphp
                <tr>
                    <td style="font-size:13px;color:var(--muted);white-space:nowrap;">
                        {{ $log->created_at?->format('d M Y, h:i A') }}
                        <div class="t-xs text-muted">{{ $log->created_at?->diffForHumans() }}</div>
                    </td>
                    <td style="font-size:13px;color:var(--ink);max-width:260px;">
                        {{ $routeLabel }}
                        <div class="t-xs text-muted">
                            Was due {{ $log->trip_datetime?->format('d M Y, h:i A') ?? '—' }}
                            @if($log->trip_datetime)
                                ({{ $log->trip_datetime->isPast() ? 'cancelled after' : $log->created_at?->diffInMinutes($log->trip_datetime) . ' min before' }} departure)
                            @endif
                        </div>
                    </td>
                    <td style="font-weight:600;">{{ $log->driver?->name ?? 'Unknown' }}</td>
                    <td style="font-size:13px;">
                        {{ $log->canceller?->name ?? 'Unknown' }}
                        <span class="role-pill {{ $cancellerRoleClass }}" style="margin-left:4px;"><i class="fa-solid {{ $cancellerRoleIcon }}" style="font-size:9px;"></i> {{ ucfirst($log->cancelled_by_role ?? '—') }}</span>
                    </td>
                    <td style="font-size:13px;color:var(--ink-2);max-width:240px;">{{ $log->reason ?? '—' }}</td>
                    <td style="font-size:12px;color:var(--muted);white-space:nowrap;">{{ $participantCount }} riders · {{ $paymentCount }} payments</td>
                </tr>
        @if($loop->last)
                </tbody></table>
            </div>
        @endif
    @empty
        <div style="padding:56px 24px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:var(--r-md);background:var(--surface-2);border:1px solid var(--hairline-strong);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                <i class="fa-solid fa-calendar-xmark" style="font-size:22px;color:var(--muted-2);"></i>
            </div>
            @if($hasActiveFilters)
                <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--ink);margin-bottom:6px;">No cancellations match your filters</div>
                <div style="font-size:14px;color:var(--muted);">Try widening the date range or clearing a filter.</div>
                <div style="margin-top:14px;"><a href="{{ route('admin.audit-log.index', ['view' => $view]) }}" class="btn btn-ghost btn-sm" style="text-decoration:none;">Clear Filters</a></div>
            @else
                <div style="font-size:16px;font-weight:700;font-family:var(--font-display);color:var(--ink);margin-bottom:6px;">No trips cancelled yet</div>
                <div style="font-size:14px;color:var(--muted);">When a driver or admin cancels a trip, a snapshot will show up here.</div>
            @endif
        </div>
    @endforelse
@endif
    @if($logs->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--hairline);display:flex;justify-content:flex-end;">
            {{ $logs->links() }}
        </div>
    @endif
</div>

</div>{{-- /au-page --}}

@endsection
