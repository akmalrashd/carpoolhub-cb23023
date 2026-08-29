@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-users.css') }}?v={{ filemtime(public_path('css/admin-users.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin-messages.css') }}?v={{ filemtime(public_path('css/admin-messages.css')) }}">
@endpush

<div class="au-page">

<div>
    <p class="au-eyebrow">Admin Panel</p>
    <h1 class="au-title">Message Users</h1>
    <p class="au-sub">Notify one user, everyone with a given role, or the whole platform — delivered through each recipient's existing in-app/push/Telegram notifications.</p>
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

<form id="admin-message-form" method="POST" action="{{ route('admin.messages.store') }}" style="display:flex;flex-direction:column;gap:20px;">
    @csrf

    <div class="card card-pad-lg">
        <div class="panel-head">
            <h3 class="panel-title"><i class="fa-solid fa-users"></i> Recipient</h3>
            <p class="panel-desc">Choose who should receive this notification.</p>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;">
            <label class="am-audience-option">
                <input type="radio" name="audience" value="user" checked onchange="updateAdminMessageAudience()">
                <span class="am-audience-icon"><i class="fa-solid fa-user"></i></span>
                <span style="flex:1;">
                    <strong>One user</strong>
                    <div class="t-xs text-muted">Search and pick a single recipient.</div>
                </span>
            </label>
            <div id="admin-message-user-picker" class="am-user-picker">
                <div id="am-picker-search-wrap" class="am-picker-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="am-user-search" class="input" placeholder="Search by name or email…" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="am-user-results">
                    <div id="am-user-results" class="am-user-results" role="listbox"></div>
                </div>
                <div id="am-selected-chip" class="am-selected-chip" style="display:none;">
                    <span class="am-selected-avatar" id="am-selected-avatar">?</span>
                    <span class="am-selected-info">
                        <strong id="am-selected-name">—</strong>
                        <span id="am-selected-email" class="t-xs text-muted">—</span>
                    </span>
                    <button type="button" class="am-selected-clear" onclick="clearSelectedUser()" aria-label="Change recipient">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <input type="hidden" name="user_id" id="am-user-id-input" required>
                <p class="t-xs text-muted" id="am-picker-hint" style="margin-top:6px;">Start typing a name or email to find someone.</p>
            </div>

            <label class="am-audience-option">
                <input type="radio" name="audience" value="role" onchange="updateAdminMessageAudience()">
                <span class="am-audience-icon"><i class="fa-solid fa-users"></i></span>
                <span style="flex:1;">
                    <strong>Everyone with a role</strong>
                    <div class="t-xs text-muted">
                        {{ $roleCounts['admin'] ?? 0 }} admin(s), {{ $roleCounts['driver'] ?? 0 }} driver(s), {{ $roleCounts['passenger'] ?? 0 }} passenger(s)
                    </div>
                </span>
            </label>
            <div id="admin-message-role-picker" style="margin-left:34px;display:none;">
                <select name="role" class="input">
                    <option value="passenger">Passengers ({{ $roleCounts['passenger'] ?? 0 }})</option>
                    <option value="driver">Drivers ({{ $roleCounts['driver'] ?? 0 }})</option>
                    <option value="admin">Admins ({{ $roleCounts['admin'] ?? 0 }})</option>
                </select>
            </div>

            <label class="am-audience-option">
                <input type="radio" name="audience" value="all" onchange="updateAdminMessageAudience()">
                <span class="am-audience-icon"><i class="fa-solid fa-globe"></i></span>
                <span style="flex:1;">
                    <strong>Everyone</strong>
                    <div class="t-xs text-muted">All {{ $totalUsers }} users on the platform.</div>
                </span>
            </label>
        </div>
    </div>

    <div class="card card-pad-lg">
        <div class="panel-head">
            <h3 class="panel-title"><i class="fa-solid fa-comment-dots"></i> Message</h3>
            <p class="panel-desc">Delivered through each recipient's existing in-app, push, and Telegram notifications.</p>
        </div>

        <div class="am-field">
            <div class="field-label" style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span>Title</span>
                <span class="t-xs text-muted" id="am-title-count">0 / 150</span>
            </div>
            <p class="am-field-hint">Shown as the notification headline.</p>
            <input type="text" id="am-title-input" name="title" class="input" placeholder="e.g. Scheduled maintenance tonight" maxlength="150" required value="{{ old('title') }}">
        </div>

        <div class="am-field" style="margin-bottom:0;">
            <div class="field-label" style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span>Message</span>
                <span class="t-xs text-muted" id="am-message-count">0 / 2000</span>
            </div>
            <p class="am-field-hint">The full text recipients will read.</p>
            <textarea id="am-message-input" name="message" class="input" rows="5" maxlength="2000" required placeholder="What do you want to tell them?">{{ old('message') }}</textarea>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block" id="am-submit-btn">
        <i class="fa-solid fa-paper-plane"></i> <span id="am-submit-label">Send Message</span>
    </button>
</form>

</div>{{-- /au-page --}}

@php
    $adminMsgUsersJson = $users->map(function ($u) {
        return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role];
    })->values();
@endphp
<script>
    var ADMIN_MSG_USERS = @json($adminMsgUsersJson);
    var ADMIN_MSG_COUNTS = {
        role: { passenger: {{ $roleCounts['passenger'] ?? 0 }}, driver: {{ $roleCounts['driver'] ?? 0 }}, admin: {{ $roleCounts['admin'] ?? 0 }} },
        all: {{ $totalUsers }}
    };

    function updateAdminMessageAudience() {
        var audience = document.querySelector('input[name="audience"]:checked').value;
        document.getElementById('admin-message-user-picker').style.display = audience === 'user' ? 'block' : 'none';
        document.getElementById('admin-message-role-picker').style.display = audience === 'role' ? 'block' : 'none';
        document.getElementById('am-user-id-input').required = audience === 'user';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function selectUser(user) {
        document.getElementById('am-user-id-input').value = user.id;
        document.getElementById('am-selected-name').textContent = user.name;
        document.getElementById('am-selected-email').textContent = user.email + ' · ' + user.role.charAt(0).toUpperCase() + user.role.slice(1);
        document.getElementById('am-selected-avatar').textContent = user.name.charAt(0).toUpperCase();
        document.getElementById('am-selected-chip').style.display = 'flex';
        document.getElementById('am-picker-search-wrap').style.display = 'none';
        document.getElementById('am-picker-hint').style.display = 'none';
    }

    function clearSelectedUser() {
        document.getElementById('am-user-id-input').value = '';
        document.getElementById('am-selected-chip').style.display = 'none';
        document.getElementById('am-picker-search-wrap').style.display = 'block';
        document.getElementById('am-picker-hint').style.display = 'block';
        var search = document.getElementById('am-user-search');
        search.value = '';
        search.focus();
        renderUserResults('');
    }

    function renderUserResults(query) {
        var resultsEl = document.getElementById('am-user-results');
        query = query.trim().toLowerCase();

        if (query === '') {
            resultsEl.innerHTML = '';
            resultsEl.classList.remove('is-open');
            return;
        }

        var matches = ADMIN_MSG_USERS.filter(function (u) {
            return u.name.toLowerCase().includes(query) || u.email.toLowerCase().includes(query);
        }).slice(0, 8);

        if (matches.length === 0) {
            resultsEl.innerHTML = '<div class="am-user-result-empty">No users match "' + escapeHtml(query) + '".</div>';
            resultsEl.classList.add('is-open');
            return;
        }

        resultsEl.innerHTML = matches.map(function (u) {
            return '<button type="button" class="am-user-result" role="option" data-user-id="' + u.id + '">' +
                '<span class="am-result-avatar">' + escapeHtml(u.name.charAt(0).toUpperCase()) + '</span>' +
                '<span class="am-result-info"><strong>' + escapeHtml(u.name) + '</strong>' +
                '<span class="t-xs text-muted">' + escapeHtml(u.email) + ' · ' + escapeHtml(u.role) + '</span></span>' +
                '</button>';
        }).join('');
        resultsEl.classList.add('is-open');

        resultsEl.querySelectorAll('.am-user-result').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var user = ADMIN_MSG_USERS.find(function (u) { return String(u.id) === btn.dataset.userId; });
                if (user) selectUser(user);
                resultsEl.classList.remove('is-open');
            });
        });
    }

    document.getElementById('am-user-search').addEventListener('input', function (e) {
        renderUserResults(e.target.value);
    });
    document.addEventListener('click', function (e) {
        var wrap = document.getElementById('am-picker-search-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('am-user-results').classList.remove('is-open');
        }
    });

    function updateCounter(inputId, counterId, max) {
        var input = document.getElementById(inputId);
        var counter = document.getElementById(counterId);
        var sync = function () { counter.textContent = input.value.length + ' / ' + max; };
        input.addEventListener('input', sync);
        sync();
    }
    updateCounter('am-title-input', 'am-title-count', 150);
    updateCounter('am-message-input', 'am-message-count', 2000);

    document.getElementById('admin-message-form').addEventListener('submit', function (e) {
        var audience = document.querySelector('input[name="audience"]:checked').value;
        var count;
        if (audience === 'user') {
            count = document.getElementById('am-user-id-input').value ? 1 : 0;
            if (!count) {
                e.preventDefault();
                alert('Please search and select a recipient first.');
                return;
            }
        } else if (audience === 'role') {
            var role = document.querySelector('select[name="role"]').value;
            count = ADMIN_MSG_COUNTS.role[role] || 0;
        } else {
            count = ADMIN_MSG_COUNTS.all;
        }

        if (!confirm('Send this message to ' + count + ' recipient(s)? This cannot be undone.')) {
            e.preventDefault();
            return;
        }

        var btn = document.getElementById('am-submit-btn');
        btn.disabled = true;
        document.getElementById('am-submit-label').textContent = 'Sending…';
    });
</script>

@endsection
