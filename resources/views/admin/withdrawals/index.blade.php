@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
@endpush

<div class="au-page">

<div>
    <p class="au-eyebrow">Admin Panel</p>
    <h1 class="au-title">Withdrawals</h1>
    <p class="au-sub">Review driver withdrawal requests. Transfer the money yourself via your own bank, then mark the request paid.</p>
</div>

@include('layouts.partials.admin-subnav')

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

<div class="card card-pad-lg">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
        <select name="status" class="eu-select" style="max-width:180px;" onchange="this.form.submit()">
            <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="paid" {{ $filters['status'] === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="rejected" {{ $filters['status'] === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
        <input type="text" name="q" class="eu-select" style="flex:1;min-width:160px;" placeholder="Search driver name…" value="{{ $filters['q'] ?? '' }}">
        <button type="submit" class="lr-approve-btn"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
    </form>

    @forelse($withdrawals as $withdrawal)
        <div class="dac-wrap" style="background:{{ $loop->odd ? 'var(--surface)' : 'var(--surface-2)' }};">
            <div class="dac-body">
                <div class="dac-row1">
                    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
                        <div class="dac-avatar" @unless($withdrawal->user?->profile_photo_url) style="background:{{ $withdrawal->user?->avatar_color }};" @endunless>
                            @if($withdrawal->user?->profile_photo_url)
                                <img src="{{ $withdrawal->user->profile_photo_url }}" alt="{{ $withdrawal->user->name }}">
                            @else
                                {{ $withdrawal->user?->avatar_initial }}
                            @endif
                        </div>
                        <div class="dac-info">
                            <div class="dac-name">{{ $withdrawal->user?->name ?: 'Unknown driver' }}</div>
                            <div class="dac-meta">{{ $withdrawal->user?->email }}</div>
                            <div class="dac-meta" style="margin-top:1px;">
                                <i class="fa-solid fa-building-columns" style="font-size:10px;opacity:.6;margin-right:3px;"></i>
                                {{ $withdrawal->destination_bank_name }} · {{ $withdrawal->destination_account_name }} ({{ $withdrawal->destination_account_number }})
                            </div>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:17px;font-weight:900;color:var(--ink);">RM {{ number_format($withdrawal->amount, 2) }}</div>
                        <span class="status-pill status-{{ $withdrawal->status === 'paid' ? 'active' : $withdrawal->status }}" style="margin-top:4px;">{{ ucfirst($withdrawal->status) }}</span>
                    </div>
                </div>

                @if($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)
                    <div class="dac-meta" style="margin-top:6px;color:#dc2626;"><i class="fa-solid fa-circle-info"></i> {{ $withdrawal->rejection_reason }}</div>
                @endif
                @if($withdrawal->status === 'paid' && $withdrawal->admin_remarks)
                    <div class="dac-meta" style="margin-top:6px;"><i class="fa-solid fa-note-sticky"></i> {{ $withdrawal->admin_remarks }}</div>
                @endif

                @if($withdrawal->isPending())
                    <div style="display:flex;gap:8px;margin-top:10px;">
                        <form method="POST" action="{{ route('admin.withdrawals.mark-paid', $withdrawal) }}" style="flex:1;">
                            @csrf @method('PATCH')
                            <button type="submit" class="lr-approve-btn" style="width:100%;justify-content:center;" onclick="return confirm('Confirm you have transferred RM {{ number_format($withdrawal->amount, 2) }} to this driver\'s bank account?')">
                                <i class="fa-solid fa-check"></i> Mark Paid
                            </button>
                        </form>
                        <button type="button" class="lr-reject-btn" style="flex:1;justify-content:center;" onclick="openWithdrawalRejectModal({{ $withdrawal->id }}, '{{ addslashes($withdrawal->user?->name ?: 'this driver') }}')">
                            <i class="fa-solid fa-xmark"></i> Reject
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <p style="text-align:center;padding:24px 0;color:var(--muted);font-size:13px;">No {{ $filters['status'] }} withdrawal requests.</p>
    @endforelse

    {{ $withdrawals->links() }}
</div>

</div>{{-- /au-page --}}

<div id="withdrawal-reject-modal" class="eu-backdrop" onclick="if(event.target===this)closeWithdrawalRejectModal()">
    <div class="eu-drawer">
        <div class="eu-drag-handle">
            <div class="eu-pill"></div>
            <div class="eu-top-row">
                <div class="eu-title" id="wr-title">Reject Withdrawal</div>
                <button type="button" class="lr-close-x" onclick="closeWithdrawalRejectModal()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>
        <div class="eu-sub">This reason is shown to the driver, and the amount is returned to their wallet.</div>
        <form id="wr-form" method="POST">
            @csrf @method('PATCH')
            <div class="eu-field">
                <label class="eu-label" for="wr-reason">Reason</label>
                <textarea id="wr-reason" name="reason" class="eu-select" rows="4"
                    placeholder="e.g. Bank account details could not be verified." required></textarea>
            </div>
            <button type="submit" class="lr-reject-btn" style="width:100%; justify-content:center;">
                <i class="fa-solid fa-circle-xmark"></i> Confirm Rejection
            </button>
        </form>
        <button type="button" onclick="closeWithdrawalRejectModal()" style="width:100%;margin-top:8px;padding:9px;border-radius:var(--r-sm);border:1px solid var(--hairline-strong);background:var(--surface);color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font-ui);">Cancel</button>
    </div>
</div>

<script>
function openWithdrawalRejectModal(id, name) {
    document.getElementById('wr-title').textContent = 'Reject: ' + name;
    document.getElementById('wr-form').action = '{{ url('/admin/withdrawals') }}/' + id + '/reject';
    document.getElementById('wr-reason').value = '';
    document.getElementById('withdrawal-reject-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeWithdrawalRejectModal() {
    document.getElementById('withdrawal-reject-modal').style.display = 'none';
    document.body.style.overflow = '';
}
window.CarpoolBottomSheet?.enable({
    modal: document.getElementById('withdrawal-reject-modal'),
    card: document.querySelector('#withdrawal-reject-modal .eu-drawer'),
    head: document.querySelector('#withdrawal-reject-modal .eu-drag-handle'),
    closeFn: closeWithdrawalRejectModal,
    breakpoint: 640,
});
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeWithdrawalRejectModal(); });
</script>

@endsection
