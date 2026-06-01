@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    @php
        $role = auth()->user()->role;
        $reviewTitle = $role === 'admin' ? 'Global Review Queue' : 'Driver Review Queue';
        $reviewSubtitle = $role === 'admin'
            ? 'Review and confirm all pending payments across trips.'
            : 'Review and confirm payments for trips you drive.';
    @endphp

    <style>
        .payments-page { display: grid; gap: 12px; }
        .payments-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 14px;
        }
        #queue-summary {
            /* keep anchor target visible below fixed mobile header */
            scroll-margin-top: calc(env(safe-area-inset-top, 0px) + 96px);
        }
        #archived-queue {
            scroll-margin-top: calc(env(safe-area-inset-top, 0px) + 120px);
        }
        .payments-title {
            margin: 0;
            font-family: Poppins, sans-serif;
            font-size: 30px;
            color: #0f172a;
            line-height: 1.05;
        }
        .payments-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }
        .payments-tools-card {
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            padding: 12px;
            display: grid;
            gap: 10px;
        }
        .payments-tools-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .payments-tool-item {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #f8fafc;
            padding: 8px 10px;
            display: grid;
            gap: 2px;
        }
        .payments-tool-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 700;
        }
        .payments-tool-value {
            font-size: 16px;
            color: #0f172a;
            font-weight: 700;
            line-height: 1.15;
        }
        .payments-note {
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 12px;
            padding: 10px 12px;
            color: #92400e;
            font-size: 13px;
            line-height: 1.4;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .payments-clear-link {
            color: #92400e;
            text-decoration: none;
            font-weight: 700;
        }
        .payments-archive-cta {
            margin-top: 14px;
            border: 1px solid #fde68a;
            background: linear-gradient(135deg, #fffbeb 0%, #fff7d6 100%);
            border-radius: 16px;
            padding: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .payments-archive-cta-copy {
            display: grid;
            gap: 3px;
        }
        .payments-archive-cta-label {
            font-size: 11px;
            color: #b45309;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .payments-archive-cta-title {
            font-size: 16px;
            color: #0f172a;
            font-weight: 800;
            line-height: 1.2;
        }
        .payments-archive-cta-meta {
            font-size: 13px;
            color: #92400e;
            font-weight: 600;
        }
        .payments-archive-cta-link {
            min-height: 44px;
            border-radius: 12px;
            border: 1px solid #0f172a;
            background: #0f172a;
            color: #fff;
            padding: 10px 14px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        }
        .payments-alert {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
        }
        .payments-success {
            border: 1px solid #86efac;
            background: #f0fdf4;
            color: #166534;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
        }
        .payments-summary-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .summary-item {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            display: grid;
            gap: 2px;
        }
        .summary-label { font-size: 12px; color: #64748b; font-weight: 700; }
        .summary-value { font-size: 18px; color: #0f172a; font-weight: 700; }
        .summary-count { font-size: 12px; color: #64748b; }
        .payment-focus-highlight {
            border-color: #facc15 !important;
            box-shadow: 0 0 0 2px rgba(250, 204, 21, 0.35);
            transition: box-shadow .24s ease, border-color .24s ease;
        }
        .debt-summary-card {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px;
            display: grid;
            gap: 8px;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .debt-summary-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .debt-summary-title {
            margin: 0;
            font-size: 13px;
            color: #0f172a;
            font-weight: 700;
        }
        .debt-summary-total {
            border: 1px solid #fcd34d;
            background: #fef9c3;
            color: #854d0e;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 700;
        }
        .debt-list {
            display: grid;
            gap: 6px;
        }
        .debt-item {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 10px;
            padding: 7px 9px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .debt-name {
            font-size: 12px;
            color: #0f172a;
            font-weight: 700;
        }
        .debt-meta {
            font-size: 11px;
            color: #64748b;
        }
        .debt-amount {
            font-size: 13px;
            color: #b45309;
            font-weight: 700;
            white-space: nowrap;
        }
        .payments-section-title {
            margin: 0 0 8px;
            font-size: 18px;
            color: #0f172a;
            font-weight: 700;
        }
        .payments-section-subtitle {
            margin: -4px 0 10px;
            font-size: 13px;
            color: #64748b;
        }
        .payments-filter-panel {
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #f8fafc;
            padding: 12px;
            margin: 0 0 12px;
            display: grid;
            gap: 8px;
        }
        .payments-filter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .payments-filter-hint {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }
        .payments-filter-toggle {
            display: none;
            min-height: 40px;
            border-radius: 11px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            padding: 0 12px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }
        .payments-filter-toggle i {
            font-size: 12px;
        }
        .payments-filter-body {
            display: grid;
            gap: 8px;
        }
        .payments-filter-grid {
            display: grid;
            gap: 10px;
        }
        .payments-filter-field {
            display: grid;
            gap: 6px;
        }
        .payments-filter-field label {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .payments-filter-input {
            width: 100%;
            min-height: 46px;
            border-radius: 12px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
        }
        .payments-filter-input:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, .2);
        }
        .payments-filter-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .payments-filter-reset {
            min-height: 44px;
            border-radius: 12px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }
        .payments-filter-hidden {
            display: none !important;
        }
        .payments-filter-empty {
            display: none;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            color: #64748b;
            padding: 14px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }
        .payments-filter-empty.show {
            display: block;
        }
        .payments-mobile-list {
            display: grid;
            gap: 8px;
        }
        .payment-mobile-item {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            display: grid;
            gap: 8px;
        }
        .open-trip-card {
            cursor: pointer;
        }
        .payment-mobile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }
        .payment-mobile-trip {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }
        .payment-mobile-sub {
            margin-top: 2px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.35;
        }
        .payment-mobile-amount-card {
            border: 1px solid #fcd34d;
            border-radius: 12px;
            background: #fffbeb;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .payment-mobile-amount-label {
            color: #92400e;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .payment-mobile-amount-value {
            color: #92400e;
            font-size: 20px;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }
        .payment-mobile-grid {
            display: grid;
            gap: 6px;
        }
        .payment-mobile-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            background: #f8fafc;
            padding: 7px 8px;
            font-size: 12px;
            color: #334155;
        }
        .payment-mobile-line strong {
            color: #0f172a;
            text-align: right;
        }
        .payments-table-wrap {
            display: none;
            overflow: auto;
        }
        .payments-table {
            width: 100%;
            border-collapse: collapse;
        }
        .payments-table th,
        .payments-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
            font-size: 13px;
            color: #334155;
        }
        .payments-table th {
            font-size: 12px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
            white-space: nowrap;
        }
        .payments-table .right { text-align: right; }
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
        .status-unpaid { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
        .status-pending { color: #854d0e; border-color: #fde68a; background: #fefce8; }
        .status-paid { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .payments-action-row {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .queue-actions {
            width: 100%;
            display: grid;
            gap: 8px;
            justify-items: end;
        }
        .queue-actions-main {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .queue-actions-secondary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .payments-input {
            border-radius: 8px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #0f172a;
            padding: 6px 8px;
            font-size: 12px;
            outline: none;
            width: 120px;
        }
        select.payments-input {
            appearance: auto;
            -webkit-appearance: menulist;
            -moz-appearance: menulist;
            width: 138px;
            padding-right: 8px;
            background-color: #f8fafc;
            cursor: pointer;
        }
        .payments-btn {
            border-radius: 8px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            height: 34px;
            min-width: 96px;
            padding: 0 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            line-height: 1;
        }
        .btn-icon {
            font-size: 11px;
            margin-right: 5px;
        }
        .reminder-btn {
            min-width: 92px;
        }
        .payments-btn-primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }
        .payments-btn-success {
            background: #f0fdf4;
            border-color: #86efac;
            color: #166534;
        }
        .payments-btn-danger {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }
        .payments-btn-soft {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #475569;
        }
        .payments-btn-icon {
            width: 34px;
            min-width: 34px;
            padding: 0;
            font-size: 13px;
        }
        .payments-btn-highlight {
            background: var(--ch-yellow);
            border-color: var(--ch-yellow-line);
            color: var(--ch-yellow-ink);
            box-shadow: none;
        }
        .payments-btn:disabled,
        .payments-btn.is-disabled {
            background: #f1f5f9;
            border-color: #dbe2ea;
            color: #94a3b8;
            cursor: not-allowed;
        }
        .payments-link {
            color: #64748b;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        button.payments-link {
            border: 0;
            background: transparent;
            padding: 0;
            cursor: pointer;
        }
        .payments-link i {
            font-size: 10px;
        }
        .request-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .52);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 2600;
        }
        .request-modal.show {
            display: flex;
        }
        .request-modal-card {
            width: min(560px, 100%);
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #fff;
            padding: 14px;
            padding-top: 44px;
            display: grid;
            gap: 10px;
            max-height: 100%;
            overflow: auto;
            position: relative;
        }
        .trip-details-card {
            width: min(700px, 100%);
            max-height: min(82vh, 760px);
            overflow: hidden;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto;
            padding-top: 14px;
        }
        .trip-details-scroll {
            min-height: 0;
            overflow: auto;
            display: grid;
            gap: 10px;
            padding-right: 2px;
        }
        .modal-close-x {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #475569;
            font-size: 0;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }
        .modal-close-x:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .modal-close-x::before {
            content: 'x';
            font-size: 15px;
            line-height: 1;
            text-transform: uppercase;
        }
        .request-modal-title {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
            padding-right: 38px;
        }
        .request-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .request-modal-head .request-modal-title {
            padding-right: 0;
        }
        .modal-close-square {
            position: static;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #475569;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }
        .modal-close-square i {
            font-size: 14px;
        }
        .modal-close-square:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .request-modal-grid {
            display: grid;
            gap: 7px;
        }
        .request-modal-line {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 10px;
            padding: 8px 10px;
            display: grid;
            gap: 2px;
        }
        .request-modal-label {
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .03em;
            font-weight: 700;
        }
        .request-modal-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .trip-details-pairs {
            display: grid;
            gap: 7px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .trip-point-cards {
            display: grid;
            gap: 7px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
        }
        .trip-point-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 10px;
            padding: 8px 10px;
            display: grid;
            gap: 3px;
            align-content: start;
            min-height: 100%;
        }
        .trip-point-card.pickup {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }
        .trip-point-card.destination {
            border-color: #fde68a;
            background: #fffbeb;
        }
        .trip-point-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .trip-point-card.pickup .trip-point-label { color: #166534; }
        .trip-point-card.destination .trip-point-label { color: #92400e; }
        .trip-point-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.3;
            word-break: break-word;
        }
        .trip-mini-map-card {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            background: #fff;
            padding: 8px;
            display: grid;
            gap: 6px;
        }
        .trip-mini-map-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .trip-mini-map-title {
            margin: 0;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .trip-mini-map-title i { color: inherit; }
        .trip-mini-map-hint {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
        }
        .trip-mini-map {
            width: 100%;
            height: 150px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            user-select: none;
        }
        .trip-mini-map .leaflet-container {
            width: 100%;
            height: 100%;
            font: inherit;
        }
        .trip-mini-map .leaflet-control-attribution {
            display: none;
        }
        .trip-mini-map .map-bg-grid {
            stroke: rgba(100, 116, 139, 0.14);
            stroke-width: 1;
            fill: none;
        }
        .trip-mini-map .map-route {
            stroke: #1d4ed8;
            stroke-width: 3;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 6 4;
        }
        .trip-mini-map .map-pin-pickup {
            fill: #16a34a;
            stroke: #fff;
            stroke-width: 2;
        }
        .trip-mini-map .map-pin-destination {
            fill: #2563eb;
            stroke: #fff;
            stroke-width: 2;
        }
        .trip-mini-map .map-label {
            font-size: 10px;
            font-weight: 700;
            fill: #334155;
        }
        .trip-mini-map-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            padding: 10px;
        }
        .trip-icon-label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .trip-icon-label i {
            font-size: 11px;
            color: inherit;
        }
        .trip-accent-value {
            color: #92400e;
            font-weight: 700;
        }
        .trip-status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            width: fit-content;
        }
        .trip-status-draft { color: #475569; border-color: #cbd5e1; background: #f8fafc; }
        .trip-status-scheduled { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
        .trip-status-recorded { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .trip-status-cancelled { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
        .trip-status-unpaid { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
        .trip-status-pending_confirmation { color: #854d0e; border-color: #fde68a; background: #fffbeb; }
        .trip-status-paid { color: #166534; border-color: #86efac; background: #f0fdf4; }
        .trip-amount-due-card {
            border-color: #fcd34d;
            background: #fffbeb;
            box-shadow: inset 0 0 0 1px rgba(251, 191, 36, 0.2);
        }
        .trip-amount-due-card .request-modal-label {
            color: #92400e;
        }
        .trip-amount-due-card .trip-icon-label i {
            color: #a16207;
        }
        .trip-amount-due-card .request-modal-value {
            color: #92400e;
            font-size: 16px;
        }
        .trip-amount-due-hint {
            margin-top: 2px;
            color: #a16207;
            font-size: 11px;
            line-height: 1.2;
            font-weight: 400;
            font-style: italic;
        }
        .trip-inline-hint {
            margin-top: 2px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.2;
            font-style: italic;
            font-weight: 400;
        }
        .trip-driver-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .trip-driver-avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }
        .trip-driver-meta {
            display: grid;
            gap: 1px;
            min-width: 0;
        }
        .trip-driver-name {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.2;
        }
        .trip-driver-email {
            color: #64748b;
            font-size: 12px;
            line-height: 1.2;
            word-break: break-word;
        }
        .trip-passenger-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .trip-passenger-count {
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #334155;
            border-radius: 999px;
            padding: 3px 9px;
            font-size: 11px;
            font-weight: 700;
        }
        .trip-passenger-list {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 10px;
            padding: 8px;
            display: grid;
            gap: 7px;
        }
        .trip-passenger-item {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 9px;
            padding: 7px 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .trip-passenger-avatar {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #fff;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 auto;
        }
        .trip-passenger-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .trip-passenger-meta {
            min-width: 0;
            display: grid;
            gap: 1px;
            flex: 1 1 auto;
        }
        .trip-passenger-name {
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.2;
        }
        .trip-passenger-email {
            color: #64748b;
            font-size: 11px;
            line-height: 1.2;
            word-break: break-word;
        }
        .trip-passenger-role {
            border: 1px solid #dbe2ea;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
            color: #475569;
            background: #fff;
        }
        .trip-passenger-role.driver {
            color: #92400e;
            border-color: #fde68a;
            background: #fffbeb;
        }
        .trip-contact-bar {
            margin: 0 -14px -14px;
            padding: 10px 14px calc(10px + env(safe-area-inset-bottom, 0px));
            border-top: 1px solid #dbe2ea;
            background: rgba(255, 255, 255, 0.98);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .trip-contact-text {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }
        .trip-contact-actions {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .trip-contact-link {
            border: 1px solid #dbe2ea;
            border-radius: 9px;
            background: #fff;
            color: #0f172a;
            height: 33px;
            padding: 0 10px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .trip-contact-link.whatsapp {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }
        .trip-contact-link.email {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        .trip-contact-link.is-disabled {
            pointer-events: none;
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
        }
        .driver-payment-head {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .driver-payment-details-card {
            width: min(700px, 100%);
            max-height: min(82vh, 760px);
            overflow: hidden;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto;
            padding-top: 14px;
        }
        .driver-payment-scroll {
            min-height: 0;
            overflow: auto;
            display: grid;
            gap: 10px;
            padding-right: 2px;
            padding-bottom: 4px;
        }
        .driver-payment-avatar {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            color: #0f172a;
            font-weight: 800;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 auto;
        }
        .driver-payment-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .driver-payment-meta {
            min-width: 0;
            display: grid;
            gap: 1px;
        }
        .driver-payment-name {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.2;
        }
        .driver-payment-email {
            color: #64748b;
            font-size: 12px;
            line-height: 1.2;
            word-break: break-word;
        }
        .driver-payment-qr-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .driver-payment-qr-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 10px;
            padding: 8px;
            display: grid;
            gap: 6px;
        }
        .driver-payment-qr-title {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .03em;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .driver-payment-qr-preview {
            width: 100%;
            height: 140px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .driver-payment-qr-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            background: #fff;
        }
        .driver-payment-qr-empty {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            padding: 0 8px;
        }
        .request-modal-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .request-modal-primary-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .reject-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }
        .reject-reason-input {
            width: 100%;
            min-height: 92px;
            resize: vertical;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            padding: 10px;
            font-size: 13px;
            color: #0f172a;
            background: #f8fafc;
            outline: none;
        }
        @media (min-width: 768px) {
            #queue-summary {
                scroll-margin-top: 76px;
            }
            #archived-queue {
                scroll-margin-top: 96px;
            }
            .payments-card { padding: 16px; }
            .payments-summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .payments-tools-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .payments-filter-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); align-items: end; }
        }
        @media (max-width: 420px) {
            .trip-details-pairs {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            .trip-point-cards {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            .driver-payment-qr-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
        }
        @media (max-width: 767px) {
            .payments-filter-panel {
                padding: 10px;
            }
            .payments-filter-toggle {
                display: inline-flex;
            }
            .payments-filter-body {
                display: none;
            }
            .payments-filter-panel.is-open .payments-filter-body {
                display: grid;
            }
            .payments-filter-panel.has-active-filter .payments-filter-body {
                display: grid;
            }
            .payments-filter-panel.has-active-filter .payments-filter-toggle {
                border-color: #fde68a;
                background: #fffbeb;
                color: #92400e;
            }
            #my-payments-list .payment-mobile-item {
                padding: 12px;
                gap: 10px;
            }
            #my-payments-list .payment-mobile-top {
                align-items: flex-start;
            }
            #my-payments-list .payment-mobile-top > div:first-child {
                min-width: 0;
            }
            #my-payments-list .payment-mobile-sub {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            #my-payments-list .payment-mobile-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
            #my-payments-list .payment-mobile-line {
                background: #fff;
            }
            #my-payments-list .payment-mobile-item .payments-action-row,
            #my-payments-list .payment-mobile-item form.payments-action-row,
            #my-payments-list .payment-mobile-item .queue-actions,
            #my-payments-list .payment-mobile-item .queue-actions-main,
            #my-payments-list .payment-mobile-item .queue-actions-secondary {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                justify-items: stretch;
                gap: 8px;
            }
            #my-payments-list .payment-mobile-item .payments-input,
            #my-payments-list .payment-mobile-item .payments-btn {
                width: 100%;
                min-height: 44px;
            }
            #my-payments-list .payment-mobile-item .payments-btn-icon {
                width: 100%;
                min-width: 0;
                gap: 7px;
            }
            #my-payments-list .payment-mobile-item .payments-link {
                min-height: 34px;
            }
            .request-modal {
                align-items: center;
                justify-content: center;
                /* keep modal clear from fixed header and bottom nav */
                padding: calc(env(safe-area-inset-top, 0px) + 88px) 12px calc(env(safe-area-inset-bottom, 0px) + 98px);
            }
            .request-modal-card {
                width: 100%;
                max-height: 100%;
                overflow: auto;
                border-radius: 16px;
            }
            .trip-details-card {
                max-height: 100%;
                overflow: hidden;
            }
            .driver-payment-details-card {
                max-height: 100%;
                overflow: hidden;
            }
        }
        @media (min-width: 1024px) {
            .payments-filter-body {
                display: grid !important;
            }
            .payments-mobile-list { display: none; }
            .payments-table-wrap { display: block; }
            .payments-table .reminder-btn {
                min-width: 92px;
            }
        }

        /* ── Design-spec layout ── */
        .pmt-page-eyebrow { font-size:11px; font-weight:800; color:var(--muted); letter-spacing:.06em; text-transform:uppercase; margin-bottom:4px; }
        .pmt-page-h1 { margin:0 0 4px; font-family:var(--font-display); font-size:28px; font-weight:800; color:var(--ink); letter-spacing:-0.02em; }
        .pmt-page-sub { margin:0; color:var(--muted); font-size:13.5px; }
        .pmt-header-row { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
        .pmt-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-top:12px; }
        .pmt-tab { display:inline-flex; align-items:center; border:1px solid var(--hairline-strong); border-radius:var(--r-pill); background:var(--surface-2); color:var(--muted); padding:5px 14px; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap; transition:background .14s,border-color .14s,color .14s; }
        .pmt-tab.active { background:var(--ch-yellow); border-color:var(--ch-yellow-deep); color:var(--ch-yellow-ink); }
        @media (min-width:1024px) { .pmt-body-grid { display:grid; grid-template-columns:3fr 1fr; gap:18px; } }

        .payments-page {
            max-width: 1120px;
            margin: 0 auto;
            padding: 4px 0 22px;
            gap: 14px;
        }
        .payments-card,
        .payments-tools-card {
            border-color: #eadfcb;
            border-radius: 18px;
            background: #fffdf9;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
        }
        .payments-card:first-child {
            border: 0;
            box-shadow: none;
            background: transparent;
            padding: 0;
        }
        .pmt-page-eyebrow { color: #64748b; }
        .pmt-page-h1 {
            font-size: clamp(28px, 4vw, 42px);
            letter-spacing: 0;
        }
        .pmt-page-sub { max-width: 560px; }
        .pmt-tabs {
            width: fit-content;
            border: 1px solid #eadfcb;
            border-radius: 14px;
            background: #fffdf9;
            padding: 4px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }
        .pmt-tab {
            min-height: 34px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #475569;
            padding: 0 14px;
        }
        .pmt-tab.active {
            background: #fff7d6;
            color: #0f172a;
            border-color: transparent;
        }
        .payments-tools-card {
            padding: 14px;
        }
        .payments-tool-item,
        .summary-item {
            border-color: #eadfcb;
            background: #fffaf0;
            border-radius: 14px;
        }
        .payments-tool-value,
        .summary-value {
            font-size: 20px;
            font-family: var(--font-display);
            letter-spacing: 0;
        }
        .payments-section-title {
            font-family: var(--font-display);
            font-size: 18px;
            letter-spacing: 0;
        }
        .payments-section-subtitle,
        .payments-filter-panel {
            display: none;
        }
        .payments-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .payments-table th {
            background: #fffaf0;
            color: #64748b;
            font-size: 11px;
            letter-spacing: .08em;
        }
        .payments-table td {
            border-top: 1px solid #efe5d4;
            vertical-align: middle;
        }
        .payments-table tbody tr:first-child td {
            border-top: 0;
        }
        .payments-table tbody tr:hover td {
            background: #fffaf0;
        }
        .status-chip {
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 800;
        }
        .payments-btn,
        .payments-link {
            border-radius: 12px;
            font-weight: 800;
        }
        .payments-link {
            min-height: 34px;
            padding: 0 12px;
            border: 1px solid #eadfcb;
            background: #fffdf9;
            color: #0f172a;
            margin-top: 6px;
        }
        .payments-action-row {
            justify-content: flex-end;
        }
        .payments-action-row .payments-input[type="text"] {
            display: none;
        }
        .payments-btn-icon.open-driver-payment-details-btn {
            display: none;
        }
        .payment-mobile-item {
            border-color: #eadfcb;
            background: #fffdf9;
        }
        .payment-mobile-amount-card {
            background: #fffaf0;
            border-color: #eadfcb;
        }

        .payments-hero-card .pmt-header-row {
            align-items: center;
        }
        .payments-back-btn,
        .payments-profile-pill {
            width: 42px;
            height: 42px;
            border: 1px solid #eadfcb;
            border-radius: 12px;
            background: #fffdf9;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: 800;
            flex: 0 0 auto;
        }
        .payments-profile-pill {
            border-color: #facc15;
            border-radius: 999px;
            background: #fff9db;
            font-size: 12px;
        }
        .payments-mobile-total {
            display: grid;
            gap: 8px;
            margin: 0 -14px;
            padding: 18px 14px;
            background: linear-gradient(135deg, #fff7b8 0%, #facc15 100%);
            color: #0f172a;
        }
        .payments-summary-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }
        .payments-summary-title-block {
            display: grid;
            gap: 2px;
            min-width: 0;
        }
        .payments-summary-mode-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .payments-summary-switch {
            display: inline-flex;
            flex: 0 0 auto;
            padding: 3px;
            border: 1px solid rgba(120, 90, 0, .18);
            border-radius: 12px;
            background: rgba(255, 255, 255, .38);
        }
        .payments-summary-switch label {
            min-height: 32px;
            border-radius: 9px;
            padding: 7px 10px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        #mobileSummaryDriver:checked ~ .payments-summary-top label[for="mobileSummaryDriver"],
        #mobileSummaryPassenger:checked ~ .payments-summary-top label[for="mobileSummaryPassenger"],
        #desktopSummaryDriver:checked ~ .payments-summary-top label[for="desktopSummaryDriver"],
        #desktopSummaryPassenger:checked ~ .payments-summary-top label[for="desktopSummaryPassenger"] {
            background: rgba(255, 255, 255, .78);
            color: var(--ink);
            box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
        }
        .payments-summary-mode-panel {
            display: none;
            gap: 8px;
        }
        #mobileSummaryDriver:checked ~ .payments-summary-driver-panel,
        #mobileSummaryPassenger:checked ~ .payments-summary-passenger-panel,
        #desktopSummaryDriver:checked ~ .payments-summary-driver-panel,
        #desktopSummaryPassenger:checked ~ .payments-summary-passenger-panel {
            display: grid;
        }
        .payments-mobile-total span,
        .payments-total-label {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .12em;
            color: #5f4b08;
        }
        .payments-mobile-total strong {
            font-family: var(--font-display);
            font-size: 30px;
            line-height: 1;
        }
        .payments-mobile-total small {
            font-size: 12px;
            font-weight: 800;
        }
        .payments-main-grid {
            display: grid;
            gap: 14px;
        }
        .payments-side-panel {
            display: none;
        }
        .payments-total-card {
            border: 1px solid #facc15;
            border-radius: 18px;
            background: #fff8cf;
            padding: 22px;
            display: grid;
            gap: 14px;
            position: sticky;
            top: 18px;
        }
        .payments-total-card strong {
            font-family: var(--font-display);
            font-size: 34px;
            line-height: 1;
            color: #0f172a;
        }
        .payments-total-card small {
            color: #047857;
            font-weight: 800;
        }
        .payments-total-line {
            border-top: 1px solid rgba(120, 90, 0, .18);
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: #0f172a;
            font-size: 13px;
        }
        .payments-total-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }
        .payments-total-metric {
            border: 1px solid rgba(120, 90, 0, .18);
            border-radius: 12px;
            background: rgba(255, 255, 255, .45);
            padding: 12px 8px;
            display: grid;
            align-content: start;
            gap: 12px;
            min-height: 76px;
            min-width: 0;
        }
        .payments-total-metric span,
        .payments-summary-detail-row span,
        .payments-summary-detail-empty {
            color: var(--muted);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
            line-height: 1.35;
            max-width: 100%;
        }
        .payments-total-metric b {
            color: var(--ink);
            font-size: 14px;
            font-weight: 900;
            white-space: nowrap;
            line-height: 1.15;
            max-width: 100%;
        }
        .payments-summary-detail {
            border-top: 1px solid rgba(120, 90, 0, .18);
            padding-top: 10px;
            display: grid;
            gap: 8px;
        }
        .payments-summary-detail summary {
            cursor: pointer;
            color: var(--ink);
            font-size: 12px;
            font-weight: 900;
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .payments-summary-detail summary::-webkit-details-marker {
            display: none;
        }
        .payments-summary-detail summary::after {
            content: "\f078";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: var(--muted);
            font-size: 10px;
            transition: transform .16s ease;
        }
        .payments-summary-detail[open] summary::after {
            transform: rotate(180deg);
        }
        .payments-summary-detail-list {
            display: grid;
            gap: 8px;
            margin-top: 2px;
        }
        .payments-summary-detail-row {
            border: 1px solid rgba(120, 90, 0, .18);
            border-radius: 12px;
            background: rgba(255, 255, 255, .58);
            padding: 10px;
            display: grid;
            gap: 8px;
        }
        .payments-summary-detail-row strong {
            color: var(--ink);
            font-family: var(--font-display), sans-serif;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.25;
        }
        .payments-summary-detail-row small {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            line-height: 1.35;
        }
        .payments-summary-amount-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .payments-summary-amount-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            min-height: 24px;
            border: 1px solid rgba(120, 90, 0, .16);
            border-radius: 999px;
            background: rgba(255, 255, 255, .65);
            color: var(--muted);
            padding: 4px 8px;
            font-size: 10.5px;
            font-weight: 900;
            line-height: 1;
            white-space: nowrap;
        }
        .payments-summary-amount-chip strong {
            font-family: var(--font-ui), sans-serif;
            font-size: 10.5px;
            color: var(--ink);
            line-height: 1;
        }
        .payments-summary-amount-chip.is-unpaid {
            border-color: #fecaca;
            background: #fff1f2;
            color: #991b1b;
        }
        .payments-summary-amount-chip.is-sent,
        .payments-summary-amount-chip.is-pending {
            border-color: #fde68a;
            background: #fffbeb;
            color: #854d0e;
        }
        .payments-summary-amount-chip.is-paid {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #047857;
        }
        .payments-summary-amount-chip.is-total {
            border-color: var(--ch-yellow-line);
            background: var(--ch-yellow);
            color: var(--ch-yellow-ink);
        }
        .payments-summary-detail-empty {
            border: 1px dashed rgba(120, 90, 0, .25);
            border-radius: 12px;
            padding: 10px;
            text-align: center;
            text-transform: none;
            letter-spacing: 0;
        }
        .payments-summary-panel {
            border-top: 1px solid rgba(120, 90, 0, .18);
            padding-top: 14px;
            display: grid;
            gap: 12px;
        }
        .payments-summary-panel-title {
            color: var(--ink);
            font-size: 14px;
            font-weight: 900;
        }
        .payments-tools-card,
        .payments-summary-card {
            display: none;
        }
        .payments-ledger-card {
            padding: 0;
            overflow: hidden;
        }
        .payments-ledger-card > .payments-section-title,
        .payments-ledger-card > .payments-section-subtitle {
            display: none;
        }
        .payment-person-block {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .payment-avatar {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            border: 1px solid #facc15;
            background: #fff9db;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 900;
            flex: 0 0 auto;
            overflow: hidden;
        }
        .payment-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .payment-name,
        .payment-route-title {
            font-size: 13px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.25;
        }
        .payment-meta {
            margin-top: 2px;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
        }
        .payment-method-pill {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            border: 1px solid #eadfcb;
            border-radius: 999px;
            padding: 0 9px;
            background: #fffdf9;
            color: #0f172a;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }
        .payment-table-amount,
        .payment-mobile-amount-line {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
            white-space: nowrap;
        }
        .payment-mobile-side {
            display: grid;
            justify-items: end;
            gap: 5px;
        }
        .payment-card-hit {
            margin: 0;
            min-height: 28px;
            padding: 0 10px;
        }
        .payment-route-title {
            margin: 0;
            font-size: 15.5px;
            font-weight: 900;
            color: var(--ink);
            line-height: 1.25;
        }
        .payment-meta-inline {
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            color: var(--muted);
            font-size: 12px;
        }
        .payment-meta-inline-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-width: 0;
        }
        .payment-meta-inline-item i {
            color: var(--muted-2);
            font-size: 11px;
        }
        .payment-inline-details-btn {
            margin-top: 8px;
            border: 0;
            background: transparent;
            color: var(--ink-2);
            padding: 0;
            font-size: 12px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        .payment-inline-details-btn i {
            color: var(--muted);
        }
        .payment-detail-grid {
            display: grid;
            gap: 8px;
        }
        .payment-detail-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--hairline);
        }
        .payment-detail-date,
        .payment-detail-method {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
        }
        .payment-detail-method {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            border: 1px solid var(--hairline);
            border-radius: var(--r-pill);
            background: var(--surface-2);
            color: var(--ink-2);
            padding: 0 9px;
            font-size: 11px;
        }
        .payment-bottom-row {
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
        }
        .payment-fare-card {
            border: 0;
            background: transparent;
            padding: 0;
            display: grid;
            gap: 2px;
            min-width: 0;
        }
        .payment-fare-label {
            font-size: 10px;
            color: var(--muted);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .payment-fare-value {
            color: var(--ink);
            font-size: 16px;
            font-weight: 900;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .payment-bottom-row .payments-btn {
            min-height: 42px;
            border-radius: 10px;
            min-width: 106px;
            gap: 7px;
            font-size: 13px;
        }
        .payment-bottom-row .payments-btn-primary {
            background: var(--ch-yellow);
            border-color: var(--ch-yellow-deep);
            color: var(--ch-yellow-ink);
        }
        .payment-receipt-card {
            border: 1px solid var(--hairline);
            border-radius: 14px;
            background: var(--surface);
            overflow: hidden;
        }
        .payment-receipt-total {
            background: var(--ch-yellow-tint);
            border-bottom: 1px solid var(--ch-yellow-line);
            padding: 14px;
            display: grid;
            gap: 4px;
        }
        .payment-receipt-total span {
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .payment-receipt-total strong {
            color: var(--ink);
            font-size: 26px;
            font-family: var(--font-display), sans-serif;
            line-height: 1;
        }
        .payment-receipt-total small {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }
        .payment-receipt-lines {
            display: grid;
            gap: 0;
        }
        .payment-receipt-lines div {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 14px;
            border-top: 1px solid var(--hairline);
            font-size: 13px;
        }
        .payment-receipt-lines div:first-child {
            border-top: 0;
        }
        .payment-receipt-lines span {
            color: var(--muted);
            font-weight: 800;
        }
        .payment-receipt-lines strong {
            color: var(--ink);
            text-align: right;
            font-weight: 800;
        }
        .trip-payment-review-modal {
            position: fixed;
            inset: 0;
            z-index: 5000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(15, 23, 42, .48);
        }
        .trip-payment-review-modal.is-open {
            display: flex;
        }
        .trip-payment-review-card {
            width: min(520px, 100%);
            max-height: min(720px, calc(100vh - 120px));
            overflow: hidden;
            border-radius: 18px;
            border: 1px solid var(--hairline);
            background: var(--surface);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .22);
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 5001;
        }
        .trip-payment-review-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 16px 12px;
            border-bottom: 1px solid var(--hairline);
        }
        .trip-payment-review-title {
            margin: 0;
            color: var(--ink);
            font-size: 18px;
            font-weight: 900;
            font-family: var(--font-display), sans-serif;
        }
        .trip-payment-review-sub {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }
        .trip-payment-review-close {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            cursor: pointer;
        }
        .trip-payment-review-list {
            display: grid;
            gap: 10px;
            padding: 12px 16px 16px;
            overflow-y: auto;
            min-height: 0;
            max-height: min(560px, calc(100vh - 238px));
            overscroll-behavior: contain;
        }
        .trip-receipt-card {
            border: 1px solid var(--hairline);
            border-radius: 14px;
            background: var(--surface);
            padding: 14px;
            display: grid;
            gap: 12px;
        }
        .trip-receipt-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid var(--hairline);
            padding-bottom: 10px;
        }
        .trip-receipt-title {
            margin: 0;
            color: var(--ink);
            font: 900 18px/1.1 var(--font-display), sans-serif;
        }
        .trip-receipt-id {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            margin-top: 3px;
        }
        .trip-receipt-status {
            border-radius: var(--r-pill);
            border: 1px solid #86efac;
            background: var(--success-soft);
            color: var(--success-ink);
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }
        .trip-receipt-lines {
            display: grid;
            gap: 8px;
        }
        .trip-receipt-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }
        .trip-receipt-line strong {
            color: var(--ink);
            text-align: right;
        }
        .trip-receipt-total {
            border-radius: 12px;
            background: var(--surface-2);
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .trip-receipt-total span {
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .trip-receipt-total strong {
            color: var(--ink);
            font: 900 24px/1 var(--font-display), sans-serif;
        }
        .trip-payment-review-item {
            border: 1px solid var(--hairline);
            border-radius: 14px;
            background: var(--surface);
            padding: 12px;
            display: grid;
            gap: 10px;
            min-height: 0;
        }
        .trip-payment-review-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }
        .trip-payment-review-person {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }
        .trip-payment-review-avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: 1px solid var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            color: var(--ch-yellow-ink);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 900;
            flex: 0 0 auto;
        }
        .trip-payment-review-name {
            display: block;
            color: var(--ink);
            font-size: 13px;
            font-weight: 900;
            line-height: 1.2;
        }
        .trip-payment-review-route {
            display: block;
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            margin-top: 2px;
        }
        .trip-payment-review-status {
            border-radius: var(--r-pill);
            border: 1px solid var(--ch-yellow-line);
            background: var(--ch-yellow-tint);
            color: var(--ch-yellow-ink);
            padding: 4px 9px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }
        .trip-payment-review-amount {
            border-radius: 10px;
            background: var(--surface-2);
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            min-height: 68px;
        }
        .trip-payment-review-amount span {
            display: block;
            color: var(--muted);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .trip-payment-review-amount strong {
            display: block;
            color: var(--ink);
            font-size: 20px;
            font-weight: 900;
            line-height: 1.1;
            font-family: var(--font-display), sans-serif;
        }
        .trip-payment-popup-result {
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            background: #f0fdf4;
            padding: 22px 16px;
            display: grid;
            justify-items: center;
            gap: 8px;
            text-align: center;
            color: #166534;
            animation: paymentResultIn .22s ease-out both;
        }
        .trip-payment-popup-result.error {
            border-color: #fecaca;
            background: var(--danger-soft);
            color: var(--danger);
        }
        .trip-payment-popup-icon {
            width: 46px;
            height: 46px;
            border-radius: 999px;
            background: #16a34a;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 10px 24px rgba(22, 163, 74, .24);
        }
        .trip-payment-popup-result.error .trip-payment-popup-icon {
            background: var(--danger);
        }
        .trip-payment-popup-title {
            color: #14532d;
            font-size: 15px;
            font-weight: 900;
        }
        .trip-payment-popup-message {
            color: #166534;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.45;
        }
        .trip-payment-popup-result.error .trip-payment-popup-title,
        .trip-payment-popup-result.error .trip-payment-popup-message {
            color: var(--danger);
        }
        @keyframes paymentResultIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .trip-paynow-form {
            display: grid;
            gap: 10px;
        }
        .trip-paynow-fields {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 8px;
        }
        .trip-paynow-input {
            width: 100%;
            min-height: 40px;
            border-radius: 10px;
            border: 1px solid var(--hairline-strong);
            background: var(--surface);
            color: var(--ink);
            padding: 0 11px;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            outline: none;
        }
        .trip-paynow-input:focus {
            border-color: var(--ch-yellow-line);
            box-shadow: 0 0 0 3px rgba(250, 204, 21, .18);
        }
        .trip-paynow-submit {
            width: 100%;
            min-height: 42px;
            border-radius: 11px;
            border: 1px solid var(--ink);
            background: var(--ink);
            color: #fff;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
        }
        .payment-paynow-driver {
            border: 1px solid var(--hairline);
            border-radius: 12px;
            background: var(--surface-2);
            padding: 12px;
            display: grid;
            gap: 10px;
        }
        .payment-paynow-driver .driver-payment-qr-preview {
            height: 120px;
            background: var(--surface);
        }
        .payment-paynow-driver .trip-details-pairs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .payment-paynow-driver .request-modal-line {
            background: var(--surface);
            min-height: 0;
        }
        @media (max-width: 520px) {
            .trip-paynow-fields {
                grid-template-columns: 1fr;
            }
            .payment-paynow-driver .trip-details-pairs,
            .payment-paynow-driver .driver-payment-qr-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .payments-page {
                max-width: 420px;
                padding: 0 10px 22px;
                gap: 10px;
            }
            .payments-hero-card {
                position: sticky;
                top: 0;
                z-index: 20;
                padding: 10px 0 !important;
                background: #fbf8ef !important;
            }
            .payments-hero-card .pmt-page-eyebrow,
            .payments-hero-card .pmt-page-sub,
            .payments-hero-card .pmt-tabs {
                display: none;
            }
            .pmt-page-h1 {
                font-size: 18px;
            }
            .payment-mobile-item {
                border-radius: 18px;
                padding: 12px;
                box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
            }
            .payment-mobile-sub {
                font-size: 11px;
                font-weight: 700;
            }
            .status-chip {
                padding: 4px 9px;
                font-size: 11px;
            }
        }

        @media (min-width: 768px) {
            .payments-mobile-total {
                display: none;
            }
            .payments-main-grid {
                grid-template-columns: minmax(0, 1fr) 280px;
                align-items: start;
            }
            .payments-side-panel {
                display: grid;
                gap: 14px;
            }
            .payments-ledger-card {
                padding: 0;
            }
            .payments-table-wrap {
                display: block !important;
            }
            .payments-mobile-list {
                display: none !important;
            }
        }

        /* Final payments polish: keep this after legacy page rules. */
        .payments-page {
            background: transparent;
        }
        .payments-page-header {
            padding: 10px 2px 4px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .payments-page-header-left {
            display: grid;
            gap: 2px;
        }
        .payments-eyebrow {
            margin: 0;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .payments-h1 {
            margin: 0;
            font-family: var(--font-display), sans-serif;
            font-size: 36px;
            font-weight: 900;
            line-height: 1.1;
            color: var(--ink);
        }
        .payments-sub {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 13px;
            max-width: 620px;
        }
        .payments-tab-strip {
            display: inline-flex;
            gap: 4px;
            flex-wrap: wrap;
            margin-top: 14px;
            padding: 4px;
            border: 1px solid var(--hairline);
            border-radius: 10px;
            background: var(--surface-2);
            width: fit-content;
        }
        .payments-tab {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: transparent;
            color: var(--muted);
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            transition: background .14s, border-color .14s, color .14s;
        }
        .payments-tab:hover {
            background: var(--canvas);
            border-color: var(--ch-yellow-line);
            color: var(--ink-2);
        }
        .payments-tab.active {
            background: var(--surface);
            border-color: var(--hairline);
            color: var(--ink);
            box-shadow: var(--shadow-1);
        }
        .payments-tab span {
            color: inherit;
        }
        .payments-page-header-left .payments-tab-strip + .payments-tab-strip {
            display: none;
        }
        .payments-header-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .payments-filter-launch {
            box-shadow: none;
        }
        .payments-ledger-card .trips-filter-form {
            gap: 10px;
            background: var(--surface-2);
            border: 1px solid var(--hairline);
            border-radius: var(--r-md);
            padding: 14px;
        }
        .trips-filter-hint {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            grid-column: 1 / -1;
        }
        .trips-filter-field {
            display: grid;
            gap: 4px;
        }
        .trips-filter-label {
            font-size: 11px;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .trips-filter-input {
            width: 100%;
            border: 1px solid var(--hairline-strong);
            border-radius: var(--r-sm);
            background: var(--surface);
            color: var(--ink);
            padding: 8px 10px;
            font-size: 13px;
            outline: none;
            font-family: var(--font-ui), sans-serif;
        }
        .trips-filter-input:focus {
            border-color: var(--ch-yellow-line);
            box-shadow: 0 0 0 2px rgba(250, 204, 21, .18);
        }
        .trips-filter-actions {
            display: inline-flex;
            align-items: flex-end;
        }
        @media (min-width: 640px) {
            .payments-ledger-card .trips-filter-form {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(140px, 180px) minmax(180px, 1fr) auto;
                align-items: end;
            }
        }
        .payments-ledger-card .payments-filter-panel.is-open,
        .payments-ledger-card .payments-filter-panel.has-active-filter {
            display: grid;
            margin: 14px 20px 0;
        }
        .payments-ledger-card .payments-filter-panel {
            display: none;
        }
        .payments-hero-card {
            border: 1px solid var(--hairline-strong) !important;
            border-radius: 18px !important;
            background: var(--surface) !important;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06) !important;
            padding: 14px !important;
        }
        .payments-hero-card .pmt-header-row {
            margin-bottom: 0;
            flex-wrap: nowrap;
        }
        .payments-ledger-card,
        .payments-total-card {
            border-color: var(--hairline-strong);
            background: var(--surface);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
        }
        .payments-ledger-card {
            border-radius: 18px;
        }
        .payments-total-card {
            background: linear-gradient(135deg, var(--ch-yellow-tint) 0%, #fff4a3 100%);
        }
        .payments-table th {
            background: var(--surface-2);
            color: var(--muted);
        }
        .payments-table td {
            background: var(--surface);
            border-color: var(--hairline);
        }
        .payments-table tbody tr:hover td {
            background: var(--ch-yellow-tint);
        }
        @media (min-width: 768px) {
            .payments-ledger-card {
                overflow: hidden;
            }
            .payments-table-wrap {
                overflow-x: auto;
            }
            .payments-table {
                width: 100%;
                table-layout: fixed;
                border-collapse: collapse;
                font-family: var(--font-ui), sans-serif;
            }
            .payments-table thead tr {
                background: var(--surface-2);
            }
            .payments-table th,
            .payments-table td {
                padding: 14px 16px;
                border-bottom: 1px solid var(--hairline);
                vertical-align: top;
                text-align: left;
            }
            .payments-table th {
                background: transparent;
                color: var(--muted);
                font-size: 11px;
                font-weight: 900;
                letter-spacing: .04em;
                text-transform: uppercase;
                white-space: nowrap;
            }
            .payments-table td {
                color: var(--ink);
                font-size: 13px;
                word-break: normal;
            }
            .payments-table th:nth-child(1),
            .payments-table td:nth-child(1) {
                width: 16%;
            }
            .payments-table th:nth-child(2),
            .payments-table td:nth-child(2) {
                width: 34%;
            }
            .payments-table th:nth-child(3),
            .payments-table td:nth-child(3) {
                width: 12%;
            }
            .payments-table th:nth-child(4),
            .payments-table td:nth-child(4) {
                width: 12%;
                text-align: right;
            }
            .payments-table th:nth-child(5),
            .payments-table td:nth-child(5) {
                width: 12%;
            }
            .payments-table th:nth-child(6),
            .payments-table td:nth-child(6) {
                width: 14%;
                text-align: center !important;
            }
            .payments-table tbody tr:last-child td {
                border-bottom: 0;
            }
            .payments-table tbody tr:hover td {
                background: var(--ch-yellow-tint);
            }
            .payments-table .payment-route-title {
                margin: 0;
                color: var(--ink);
                font-size: 13px;
                font-weight: 900;
                line-height: 1.3;
                white-space: normal;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }
            .payments-table .payments-link {
                display: none;
            }
            .payments-table .payment-person-block {
                gap: 0;
            }
            .payments-table .payment-name {
                color: var(--ink);
                font-size: 13px;
                font-weight: 900;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 140px;
            }
            .payments-table .payment-meta {
                margin-top: 3px;
                color: var(--muted);
                font-size: 11px;
                font-weight: 800;
            }
            .payments-table .payment-trip-meta {
                display: flex;
                align-items: center;
                gap: 7px;
                flex-wrap: wrap;
                margin-top: 6px;
                color: var(--muted);
                font-size: 11px;
                font-weight: 800;
                line-height: 1.2;
            }
            .payments-table .payment-trip-meta span {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                min-width: 0;
            }
            .payments-table .payment-method-pill {
                min-height: 28px;
                border-color: var(--hairline);
                background: var(--surface-2);
                color: var(--ink-2);
                padding: 0 10px;
                font-size: 11px;
                font-weight: 900;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .payments-table .status-chip {
                min-height: 26px;
                padding: 5px 10px;
                font-size: 11px;
                font-weight: 900;
                white-space: nowrap;
            }
            .payments-table .payment-table-amount {
                color: var(--ink);
                font-family: var(--font-display), sans-serif;
                font-size: 13.5px;
                font-weight: 900;
                white-space: nowrap;
            }
            .payments-table .payment-table-amount + div {
                margin-top: 3px;
                color: var(--muted) !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                line-height: 1.25;
            }
            .payments-table .payment-table-date {
                display: inline-grid;
                gap: 2px;
                white-space: nowrap;
                color: var(--ink);
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
                font-size: 11px;
                font-weight: 900;
                line-height: 1.2;
            }
            .payments-table .payment-table-time {
                color: var(--muted);
                font-size: 10.5px;
                font-weight: 900;
            }
            .payments-table .payments-action-row,
            .payments-table form.payments-action-row {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                gap: 8px;
                flex-wrap: nowrap;
                margin: 0;
                width: 100%;
            }
            .payments-table .payments-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-height: 38px !important;
                width: auto !important;
                min-width: 96px !important;
                border-radius: 10px;
                padding: 9px 12px;
                font-size: 12px;
                font-weight: 900;
                box-shadow: none;
                white-space: nowrap;
            }
            .payments-table td:nth-child(6) .payments-btn-primary {
                max-width: 100%;
            }
            .payments-table .payments-btn i {
                width: 14px;
                min-width: 14px;
                font-size: 12px;
                line-height: 1;
                flex: 0 0 auto;
                text-align: center;
            }
            .payments-table .payment-action-note {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                min-height: 34px;
                border-radius: 10px;
                background: var(--ch-yellow-tint);
                border: 1px solid var(--ch-yellow-line);
                color: #854d0e;
                padding: 6px 10px;
                font-size: 11px;
                font-weight: 900;
                line-height: 1.2;
                text-align: center;
            }
            .payments-table .payments-btn-primary {
                background: var(--ch-yellow);
                border-color: var(--ch-yellow-line);
                color: var(--ch-yellow-ink);
            }
            .payments-table .payments-btn-primary:hover {
                background: var(--ch-yellow-deep);
                border-color: var(--ch-yellow-deep);
                color: var(--ch-yellow-ink);
            }
            .payments-table .payments-btn-soft {
                background: var(--surface);
                border-color: var(--hairline-strong);
                color: var(--ink);
            }
            .payments-table .payments-btn-soft:hover {
                background: var(--ch-yellow-tint);
                border-color: var(--ch-yellow-line);
            }
            .payments-table .payment-table-action {
                border-color: var(--ch-yellow-line);
                background: var(--ch-yellow);
                color: var(--ch-yellow-ink);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-width: 96px !important;
                width: 96px !important;
                min-height: 38px !important;
                padding: 9px 12px;
                font-weight: 900;
            }
            .payments-table .payment-table-action:hover {
                border-color: var(--ch-yellow-deep);
                background: var(--ch-yellow-deep);
                color: var(--ch-yellow-ink);
            }
            .payments-table .payment-table-action i {
                width: 14px;
                min-width: 14px;
                font-size: 12px !important;
                line-height: 1;
                text-align: center;
            }
            .payments-table .payment-table-action.is-muted {
                border-color: var(--hairline-strong);
                background: var(--surface-2);
                color: var(--muted);
            }
            .payments-table .payment-table-action.is-muted:hover {
                border-color: var(--hairline-strong);
                background: var(--surface-2);
                color: var(--ink-2);
            }
            .payments-table .payments-input[type="text"] {
                display: none;
            }
            .payments-table .payments-btn-icon {
                width: 34px !important;
                min-width: 34px !important;
                padding: 0;
            }
            .payments-table .open-driver-payment-details-btn {
                order: 1;
            }
            .payments-table .open-driver-payment-details-btn + .payments-btn-primary {
                order: 0;
            }
        }
        .payments-mobile-total {
            margin: 0;
            border: 1px solid var(--ch-yellow-line);
            border-radius: 18px;
            background: linear-gradient(135deg, var(--ch-yellow-tint) 0%, var(--ch-yellow) 100%);
            box-shadow: 0 10px 24px rgba(250, 204, 21, .16);
        }
        .payment-mobile-item {
            border-color: var(--hairline-strong) !important;
            background: var(--surface) !important;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
        }
        .payment-mobile-top {
            align-items: flex-start;
        }
        .payment-mobile-side {
            min-width: 116px;
        }
        .payment-mobile-grid {
            margin-top: -2px;
        }
        .payment-mobile-line {
            border: 0;
            background: transparent;
            padding: 0;
            align-items: flex-start;
        }
        .payment-mobile-line span {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
        }
        .payment-mobile-line strong {
            font-size: 12px;
            line-height: 1.3;
        }
        .payment-card-hit,
        .payments-link {
            border-radius: 10px;
            background: var(--surface-2);
            border-color: var(--hairline);
            margin: 0;
        }
        #my-payments-list .payment-mobile-item .payments-action-row,
        #my-payments-list .payment-mobile-item form.payments-action-row {
            display: flex !important;
            justify-content: flex-end;
            justify-items: end;
            margin-top: 2px;
        }
        #my-payments-list .payment-mobile-item .payments-btn {
            width: auto !important;
            min-width: 112px !important;
            min-height: 36px !important;
            border-radius: 11px;
            padding: 0 14px;
        }
        #my-payments-list .payment-mobile-item .payments-btn-primary {
            background: var(--ch-yellow);
            border-color: var(--ch-yellow-deep);
            color: var(--ch-yellow-ink);
        }
        .payments-archive-cta,
        #archived-queue {
            display: none !important;
        }
        #queue-summary {
            display: none;
        }
        #driver-review-list {
            display: none !important;
        }

        @media (max-width: 767px) {
            .payments-page {
                max-width: 100%;
                padding: 10px 14px calc(env(safe-area-inset-bottom, 0px) + 92px);
                gap: 12px;
            }
            .payments-page-header {
                padding: 2px 2px 0;
            }
            .payments-h1 {
                font-size: 30px;
            }
            .payments-sub {
                font-size: 12.5px;
            }
            .payments-tab-strip {
                width: 100%;
                display: inline-flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                scrollbar-width: none;
                margin-top: 12px;
            }
            .payments-tab-strip::-webkit-scrollbar {
                display: none;
            }
            .payments-tab {
                justify-content: center;
                padding: 8px 16px;
                flex: 0 0 auto;
            }
            .payments-header-actions {
                width: 100%;
            }
            .payments-filter-launch {
                min-width: 98px;
            }
            .payments-ledger-card .payments-filter-panel.is-open,
            .payments-ledger-card .payments-filter-panel.has-active-filter {
                margin: 0 0 12px;
            }
            .payments-hero-card {
                position: static !important;
                padding: 12px !important;
            }
            .payments-back-btn,
            .payments-profile-pill {
                width: 38px;
                height: 38px;
                border-radius: 12px;
            }
            .pmt-page-h1 {
                font-size: 18px !important;
                margin: 0;
            }
            .payments-mobile-total {
                padding: 18px 16px;
            }
            .payments-mobile-total .payments-total-metric {
                min-height: 70px;
                padding: 10px;
                gap: 10px;
            }
            .payments-mobile-total .payments-total-metric b {
                font-size: 18px;
            }
            .payment-mobile-item {
                border-radius: 16px;
                padding: 14px 14px 12px;
                gap: 10px;
            }
            .payments-table-wrap {
                display: none !important;
            }
            .payments-ledger-card {
                border: 0;
                background: transparent;
                box-shadow: none;
                padding: 0;
                overflow: visible;
            }
            .payment-table-amount,
            .payment-mobile-amount-line {
                font-size: 13px;
            }
            #my-payments-list .payment-mobile-item .payments-action-row,
            #my-payments-list .payment-mobile-item form.payments-action-row {
                margin-top: 0;
            }
            #my-payments-list .payment-mobile-item .payments-btn {
                min-width: 102px !important;
            }
        }
    </style>

    @php
        $myRecordCount = isset($myPayments) ? $myPayments->total() : 0;
        $queueCount = (isset($driverPayments) && $driverPayments ? $driverPayments->total() : 0);
        $isAdmin = $role === 'admin';
        $isPassenger = $role === 'passenger';
        $hasSplitPaymentDirections = $canReviewQueue && ! $isAdmin;
        $unpaidAmt = (float) ($summary['my']['unpaid']['amount'] ?? $summary['driver']['unpaid']['amount'] ?? 0);
        $pendingAmt = (float) ($summary['my']['pending_confirmation']['amount'] ?? $summary['driver']['pending_confirmation']['amount'] ?? 0);
        $paidAmt = (float) ($summary['my']['paid']['amount'] ?? 0);
        $myTotalAmt = (float) (($summary['my']['unpaid']['amount'] ?? 0) + ($summary['my']['pending_confirmation']['amount'] ?? 0) + ($summary['my']['paid']['amount'] ?? 0));
        $paidOutAmt = (float) (($summary['my']['unpaid']['amount'] ?? 0) + ($summary['my']['pending_confirmation']['amount'] ?? 0));
        $monthLabel = $summaryLabel;
        $allLivePayments = collect($myPayments?->items() ?? [])
            ->merge(collect(($driverPayments ?? null)?->items() ?? []))
            ->unique(fn ($payment) => $payment->id . ':' . $payment->trip_id)
            ->sortByDesc(fn ($payment) => $payment->trip?->trip_datetime?->timestamp ?? $payment->id)
            ->values();
        $paymentPerspective = fn ($payment): string => (
            ((int) ($payment->trip?->driver_id ?? 0) === (int) auth()->id()
                && (int) $payment->user_id !== (int) auth()->id())
            || $isAdmin
        )
                ? 'collect'
                : 'pay';
        $activePaymentFilter = $filters['payment_filter'] ?? request('payment_filter', 'all');
        $activeDirection = $filters['direction'] ?? request('direction', 'all');
        $paymentTabUrl = fn (array $overrides = []) => route('payments.index', array_filter(
            array_merge(request()->except(['payment_filter', 'direction', 'mine_page', 'driver_page']), $overrides),
            fn ($value) => $value !== null && $value !== '' && $value !== 'all'
        ));
        $allLiveCount = (int) ($paymentCounts['all'] ?? $allLivePayments->count());
        $payCount = (int) ($paymentCounts['pay'] ?? $allLivePayments->filter(fn ($payment) => $paymentPerspective($payment) === 'pay')->count());
        $collectCount = (int) ($paymentCounts['collect'] ?? $allLivePayments->filter(fn ($payment) => $paymentPerspective($payment) === 'collect')->count());
        $unpaidCount = (int) ($paymentCounts['unpaid'] ?? $allLivePayments->where('payment_status', 'unpaid')->count());
        $reviewCount = (int) ($paymentCounts['review'] ?? $allLivePayments->where('payment_status', 'pending_confirmation')->count());
        $confirmedCount = (int) ($paymentCounts['confirmed'] ?? $allLivePayments->where('payment_status', 'paid')->count());
        $paymentInitials = fn (?string $name): string => collect(explode(' ', trim((string) $name)))
            ->filter()
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('') ?: 'P';
        $paymentFareBreakdown = function ($payment): array {
            $trip = $payment->trip;
            $routePoint = $trip?->passengerRoutePoints
                ?->first(fn ($point) => (int) $point->user_id === (int) $payment->user_id
                    && in_array((string) $point->status, ['accepted', 'approved'], true)
                    && (float) ($point->extra_fee_amount ?? 0) > 0);
            $extra = (float) ($routePoint?->extra_fee_amount ?? 0);
            $total = (float) ($payment->amount_due ?? 0);
            $base = $extra > 0 ? max(0, $total - $extra) : $total;
            $pickupLabel = $routePoint && ! $routePoint->uses_default_pickup
                ? ($routePoint->pickup_name ?: 'Custom pickup')
                : null;
            $dropoffLabel = $routePoint && ! $routePoint->uses_default_dropoff
                ? ($routePoint->dropoff_name ?: 'Custom drop-off')
                : null;

            return [
                'base' => $base,
                'extra' => $extra,
                'total' => $total,
                'has_extra' => $extra > 0,
                'custom_stop' => trim(implode(' -> ', array_filter([$pickupLabel, $dropoffLabel]))) ?: null,
            ];
        };
        $driverUnpaidAmount = (float) ($summary['driver']['unpaid']['amount'] ?? 0);
        $driverPendingAmount = (float) ($summary['driver']['pending_confirmation']['amount'] ?? 0);
        $driverPaidAmount = (float) ($summary['driver']['paid']['amount'] ?? 0);
        $myUnpaidAmount = (float) ($summary['my']['unpaid']['amount'] ?? 0);
        $myPendingAmount = (float) ($summary['my']['pending_confirmation']['amount'] ?? 0);
        $myPaidAmount = (float) ($summary['my']['paid']['amount'] ?? 0);
        $summaryMainLabel = $isAdmin ? 'All payments' : ($canReviewQueue ? 'To collect' : 'To pay');
        $summaryMainAmount = ($canReviewQueue || $isAdmin)
            ? ($driverUnpaidAmount + $driverPendingAmount + $driverPaidAmount)
            : ($myUnpaidAmount + $myPendingAmount);
        $summaryPrimaryAmount = ($canReviewQueue || $isAdmin) ? $driverUnpaidAmount : $myUnpaidAmount;
        $summaryPrimaryLabel = ($canReviewQueue || $isAdmin) ? 'Unpaid by passengers' : 'Unpaid to drivers';
        $summarySecondaryAmount = ($canReviewQueue || $isAdmin) ? $driverPendingAmount : $myPendingAmount;
        $summarySecondaryLabel = $canReviewQueue ? 'Pending confirmation' : 'Pending confirmation';
        $summaryPaidAmount = ($canReviewQueue || $isAdmin) ? $driverPaidAmount : $myPaidAmount;
        $driverCollectionRows = $allLivePayments
            ->filter(fn ($payment) => $paymentPerspective($payment) === 'collect' && in_array((string) $payment->payment_status, ['unpaid', 'pending_confirmation', 'paid'], true))
            ->groupBy(fn ($payment) => $payment->user?->name ?: 'Passenger')
            ->map(function ($rows, $passengerName) {
                $unpaid = (float) $rows->where('payment_status', 'unpaid')->sum('amount_due');
                $pending = (float) $rows->where('payment_status', 'pending_confirmation')->sum('amount_due');
                $paid = (float) $rows->where('payment_status', 'paid')->sum('amount_due');

                return [
                    'name' => $passengerName,
                    'unpaid' => $unpaid,
                    'pending' => $pending,
                    'paid' => $paid,
                    'total' => $unpaid + $pending + $paid,
                    'records' => $rows->count(),
                ];
            })
            ->values();
        $passengerPayRows = $allLivePayments
            ->filter(fn ($payment) => $paymentPerspective($payment) === 'pay' && in_array((string) $payment->payment_status, ['unpaid', 'pending_confirmation', 'paid'], true))
            ->groupBy(fn ($payment) => $payment->trip?->driver?->name ?: 'Driver')
            ->map(function ($rows, $driverName) {
                $unpaid = (float) $rows->where('payment_status', 'unpaid')->sum('amount_due');
                $pending = (float) $rows->where('payment_status', 'pending_confirmation')->sum('amount_due');
                $paid = (float) $rows->where('payment_status', 'paid')->sum('amount_due');

                return [
                    'name' => $driverName,
                    'unpaid' => $unpaid,
                    'pending' => $pending,
                    'paid' => $paid,
                    'total' => $unpaid + $pending + $paid,
                    'records' => $rows->count(),
                    'next_trip' => $rows->first()?->trip?->savedRoute?->route_name
                        ?: trim(($rows->first()?->trip?->pickup_name ?: '-') . ' -> ' . ($rows->first()?->trip?->destination_name ?: '-')),
                ];
            })
            ->values();
        $summaryDetailRows = $isAdmin ? $driverCollectionRows : $passengerPayRows;
        $summaryDetailTitle = $isAdmin ? 'All user payments' : 'Where you still need to pay';
        $summaryRecordCount = $isAdmin ? $allLiveCount : ($canReviewQueue ? $collectCount : $payCount);
        $mainPaymentsPaginator = $isAdmin ? $driverPayments : $myPayments;
    @endphp

    <div class="payments-page">
        <section class="payments-page-header">
            <div class="payments-page-header-left">
                <p class="payments-eyebrow">Payments</p>
                <h1 class="payments-h1">{{ $isPassenger ? 'Your payments' : 'Payment ledger' }}</h1>
                <p class="payments-sub">
                    {{ $isAdmin ? 'Review every user payment in one admin ledger.' : ($canReviewQueue ? 'Payments you need to pay are separated from fares you collect as a driver.' : 'Track fares you need to pay and payments already confirmed.') }}
                </p>
                <div class="payments-tab-strip">
                    <a class="payments-tab {{ $activePaymentFilter === 'all' && $activeDirection === 'all' ? 'active' : '' }}" href="{{ $paymentTabUrl(['payment_filter' => 'all', 'direction' => 'all']) }}">All &middot; {{ $allLiveCount }}</a>
                    @if($hasSplitPaymentDirections)
                        <a class="payments-tab {{ $activeDirection === 'pay' ? 'active' : '' }}" href="{{ $paymentTabUrl(['direction' => 'pay']) }}">To pay &middot; {{ $payCount }}</a>
                        <a class="payments-tab {{ $activeDirection === 'collect' ? 'active' : '' }}" href="{{ $paymentTabUrl(['direction' => 'collect']) }}">To collect &middot; {{ $collectCount }}</a>
                    @else
                        <a class="payments-tab {{ $activePaymentFilter === 'unpaid' ? 'active' : '' }}" href="{{ $paymentTabUrl(['payment_filter' => 'unpaid']) }}">Unpaid &middot; {{ $unpaidCount }}</a>
                    @endif
                    <a class="payments-tab {{ $activePaymentFilter === 'review' ? 'active' : '' }}" href="{{ $paymentTabUrl(['payment_filter' => 'review']) }}">{{ $canReviewQueue ? 'Review' : 'Pending' }} &middot; {{ $reviewCount }}</a>
                    <a class="payments-tab {{ $activePaymentFilter === 'confirmed' ? 'active' : '' }}" href="{{ $paymentTabUrl(['payment_filter' => 'confirmed']) }}">Confirmed &middot; {{ $confirmedCount }}</a>
                </div>
                <div class="payments-tab-strip">
                    <button class="payments-tab active" type="button" onclick="pmtTab(this,'all')">All · {{ $allLiveCount }}</button>
                    @if($hasSplitPaymentDirections)
                        <button class="payments-tab" type="button" onclick="pmtTab(this,'pay')">To pay · {{ $payCount }}</button>
                        <button class="payments-tab" type="button" onclick="pmtTab(this,'collect')">To collect · {{ $collectCount }}</button>
                    @else
                        <button class="payments-tab" type="button" onclick="pmtTab(this,'unpaid')">Unpaid · {{ $unpaidCount }}</button>
                    @endif
                    <button class="payments-tab" type="button" onclick="pmtTab(this,'review')">{{ $canReviewQueue ? 'Review' : 'Pending' }} · {{ $reviewCount }}</button>
                    <button class="payments-tab" type="button" onclick="pmtTab(this,'confirmed')">Confirmed · {{ $confirmedCount }}</button>
                </div>
            </div>
            <div class="payments-header-actions">
                <button type="button" class="btn btn-ghost btn-sm payments-filter-launch" data-payments-filter-launch>
                    <i class="fa-solid fa-sliders"></i>
                    Filter
                </button>
            </div>
        </section>

        <section class="payments-mobile-total">
            @if($hasSplitPaymentDirections)
                <input class="payments-summary-mode-input" type="radio" name="mobile_summary_mode" id="mobileSummaryDriver" checked>
                <input class="payments-summary-mode-input" type="radio" name="mobile_summary_mode" id="mobileSummaryPassenger">
                <div class="payments-summary-top">
                    <div class="payments-summary-title-block">
                        <span>{{ strtoupper($monthLabel) }}</span>
                    </div>
                    <div class="payments-summary-switch" aria-label="Summary view">
                        <label for="mobileSummaryDriver">As driver</label>
                        <label for="mobileSummaryPassenger">As passenger</label>
                    </div>
                </div>
                <div class="payments-summary-mode-panel payments-summary-driver-panel">
                    <strong>RM {{ number_format($driverUnpaidAmount + $driverPendingAmount + $driverPaidAmount, 2) }}</strong>
                    <small>To collect · {{ $collectCount }} records</small>
                    <div class="payments-total-metrics">
                        <div class="payments-total-metric">
                            <span>Unpaid</span>
                            <b>RM {{ number_format($driverUnpaidAmount, 2) }}</b>
                        </div>
                        <div class="payments-total-metric">
                            <span>Pending</span>
                            <b>RM {{ number_format($driverPendingAmount, 2) }}</b>
                        </div>
                        <div class="payments-total-metric">
                            <span>Paid</span>
                            <b>RM {{ number_format($driverPaidAmount, 2) }}</b>
                        </div>
                    </div>
                <details class="payments-summary-detail">
                    <summary>Passenger payment status</summary>
                    <div class="payments-summary-detail-list">
                        @forelse($driverCollectionRows as $debtRow)
                            <div class="payments-summary-detail-row">
                                <span>{{ $debtRow['records'] }} records</span>
                                <strong>{{ $debtRow['name'] }}</strong>
                                <div class="payments-summary-amount-row">
                                    <span class="payments-summary-amount-chip is-unpaid">Unpaid <strong>RM {{ number_format((float) $debtRow['unpaid'], 2) }}</strong></span>
                                    <span class="payments-summary-amount-chip is-pending">Pending <strong>RM {{ number_format((float) $debtRow['pending'], 2) }}</strong></span>
                                    <span class="payments-summary-amount-chip is-paid">Paid <strong>RM {{ number_format((float) $debtRow['paid'], 2) }}</strong></span>
                                    <span class="payments-summary-amount-chip is-total">Total <strong>RM {{ number_format((float) $debtRow['total'], 2) }}</strong></span>
                                </div>
                            </div>
                        @empty
                            <div class="payments-summary-detail-empty">No passenger payment still pending.</div>
                        @endforelse
                    </div>
                </details>
                </div>
                <div class="payments-summary-mode-panel payments-summary-passenger-panel">
                    <strong>RM {{ number_format($myUnpaidAmount + $myPendingAmount, 2) }}</strong>
                    <small>To pay · Paid RM {{ number_format($myPaidAmount, 2) }}</small>
                    <div class="payments-total-metrics">
                        <div class="payments-total-metric">
                            <span>Unpaid</span>
                            <b>RM {{ number_format($myUnpaidAmount, 2) }}</b>
                        </div>
                        <div class="payments-total-metric">
                            <span>Pending</span>
                            <b>RM {{ number_format($myPendingAmount, 2) }}</b>
                        </div>
                        <div class="payments-total-metric">
                            <span>Paid</span>
                            <b>RM {{ number_format($myPaidAmount, 2) }}</b>
                        </div>
                    </div>
                <details class="payments-summary-detail">
                    <summary>Your passenger payments</summary>
                    <div class="payments-summary-detail-list">
                        @forelse($passengerPayRows as $payRow)
                            <div class="payments-summary-detail-row">
                                <span>{{ $payRow['records'] }} records</span>
                                <strong>{{ $payRow['name'] }}</strong>
                                <div class="payments-summary-amount-row">
                                    <span class="payments-summary-amount-chip is-unpaid">Unpaid <strong>RM {{ number_format((float) $payRow['unpaid'], 2) }}</strong></span>
                                    <span class="payments-summary-amount-chip is-pending">Pending <strong>RM {{ number_format((float) $payRow['pending'], 2) }}</strong></span>
                                    <span class="payments-summary-amount-chip is-paid">Paid <strong>RM {{ number_format((float) $payRow['paid'], 2) }}</strong></span>
                                </div>
                            </div>
                        @empty
                            <div class="payments-summary-detail-empty">No active passenger payment due.</div>
                        @endforelse
                    </div>
                </details>
                </div>
            @else
                <div class="payments-summary-top">
                    <div class="payments-summary-title-block">
                        <span>{{ strtoupper($monthLabel) }}</span>
                        <strong>RM {{ number_format($summaryMainAmount, 2) }}</strong>
                        <small>{{ $summaryMainLabel }} · {{ $summaryRecordCount }} records</small>
                    </div>
                </div>
                <div class="payments-total-metrics">
                    <div class="payments-total-metric">
                        <span>Unpaid</span>
                        <b>RM {{ number_format($summaryPrimaryAmount, 2) }}</b>
                    </div>
                    <div class="payments-total-metric">
                        <span>Pending</span>
                        <b>RM {{ number_format($summarySecondaryAmount, 2) }}</b>
                    </div>
                    <div class="payments-total-metric">
                        <span>Paid</span>
                        <b>RM {{ number_format($summaryPaidAmount, 2) }}</b>
                    </div>
                </div>
                <details class="payments-summary-detail">
                    <summary>{{ $summaryDetailTitle }}</summary>
                    <div class="payments-summary-detail-list">
                        @forelse($summaryDetailRows as $payRow)
                            <div class="payments-summary-detail-row">
                                <span>{{ $payRow['records'] }} records</span>
                                <strong>{{ $payRow['name'] }}</strong>
                                <div class="payments-summary-amount-row">
                                    <span class="payments-summary-amount-chip is-unpaid">Unpaid <strong>RM {{ number_format((float) $payRow['unpaid'], 2) }}</strong></span>
                                    <span class="payments-summary-amount-chip is-pending">Pending <strong>RM {{ number_format((float) $payRow['pending'], 2) }}</strong></span>
                                    <span class="payments-summary-amount-chip is-paid">Paid <strong>RM {{ number_format((float) $payRow['paid'], 2) }}</strong></span>
                                </div>
                            </div>
                        @empty
                            <div class="payments-summary-detail-empty">No active payment due right now.</div>
                        @endforelse
                    </div>
                </details>
            @endif
        </section>

        <section class="payments-tools-card">
            <div class="payments-tools-grid">
                <div class="payments-tool-item">
                    <span class="payments-tool-label">My Records</span>
                    <span class="payments-tool-value" id="paymentsToolMyRecords">{{ $myRecordCount }}</span>
                </div>
                @if(! $isPassenger)
                    <div class="payments-tool-item">
                        <span class="payments-tool-label">Queue Records</span>
                        <span class="payments-tool-value" id="paymentsToolQueueRecords">{{ $queueCount }}</span>
                    </div>
                @endif
                <div class="payments-tool-item">
                    <span class="payments-tool-label">Total Unpaid</span>
                    <span class="payments-tool-value" id="paymentsToolUnpaidAmount">RM {{ number_format($unpaidAmt, 2) }}</span>
                </div>
                <div class="payments-tool-item">
                    <span class="payments-tool-label">Pending Amount</span>
                    <span class="payments-tool-value" id="paymentsToolPendingAmount">RM {{ number_format($pendingAmt, 2) }}</span>
                </div>
            </div>
        </section>

        @if(session('status'))
            <section class="payments-success">{{ session('status') }}</section>
        @endif

        @if($errors->any())
            <section class="payments-alert">{{ $errors->first() }}</section>
        @endif

        @if($showMyPayments)
            <section class="payments-card payments-summary-card">
                <h2 class="payments-section-title">My Summary</h2>
                <div class="payments-summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Unpaid</div>
                        <div id="paymentsMyUnpaidAmount" class="summary-value">RM {{ number_format((float) ($summary['my']['unpaid']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsMyUnpaidCount" class="summary-count">{{ $summary['my']['unpaid']['count'] ?? 0 }} records</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">My Pending</div>
                        <div id="paymentsMyPendingAmount" class="summary-value">RM {{ number_format((float) ($summary['my']['pending_confirmation']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsMyPendingCount" class="summary-count">{{ $summary['my']['pending_confirmation']['count'] ?? 0 }} records</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Paid</div>
                        <div id="paymentsMyPaidAmount" class="summary-value">RM {{ number_format((float) ($summary['my']['paid']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsMyPaidCount" class="summary-count">{{ $summary['my']['paid']['count'] ?? 0 }} records</div>
                    </div>
                </div>
            </section>
        @endif

            <div class="payments-main-grid">
            <section class="payments-card payments-ledger-card" id="my-payments-list">
                <h2 class="payments-section-title">Transactions</h2>
                <p class="payments-section-subtitle">Track fares paid as a passenger and received as a driver.</p>
                <form method="GET" action="{{ route('payments.index') }}" class="payments-filter-panel trips-filter-form" id="paymentsFilterPanel" style="{{ request()->hasAny(['date_from','date_to','visibility','payment_search']) ? 'display:grid' : 'display:none' }}">
                    @if($activePaymentFilter !== 'all')
                        <input type="hidden" name="payment_filter" value="{{ $activePaymentFilter }}">
                    @endif
                    @if($activeDirection !== 'all')
                        <input type="hidden" name="direction" value="{{ $activeDirection }}">
                    @endif
                    <p class="trips-filter-hint">Filters apply automatically on change.</p>
                    <div class="trips-filter-field">
                        <label class="trips-filter-label" for="myPaymentsFromDate">From Date</label>
                        <input id="myPaymentsFromDate" name="date_from" class="trips-filter-input" type="date" value="{{ $filters['date_from'] ?? request('date_from') }}">
                    </div>
                    <div class="trips-filter-field">
                        <label class="trips-filter-label" for="myPaymentsToDate">To Date</label>
                        <input id="myPaymentsToDate" name="date_to" class="trips-filter-input" type="date" value="{{ $filters['date_to'] ?? request('date_to') }}">
                    </div>
                    <div class="trips-filter-field">
                        <label class="trips-filter-label" for="myPaymentsVisibility">Visibility</label>
                        <select id="myPaymentsVisibility" name="visibility" class="trips-filter-input">
                            <option value="">All</option>
                            <option value="public" {{ ($filters['visibility'] ?? request('visibility')) === 'public' ? 'selected' : '' }}>Public</option>
                            <option value="private" {{ ($filters['visibility'] ?? request('visibility')) === 'private' ? 'selected' : '' }}>Private</option>
                        </select>
                    </div>
                    <div class="trips-filter-field">
                        <label class="trips-filter-label" for="myPaymentsPassengerSearch">Search</label>
                        <input id="myPaymentsPassengerSearch" name="payment_search" class="trips-filter-input" type="search" placeholder="Trip, driver, or passenger" value="{{ $filters['payment_search'] ?? request('payment_search') }}">
                    </div>
                    <div class="trips-filter-actions">
                        <a href="{{ route('payments.index', array_filter(['payment_filter' => $activePaymentFilter !== 'all' ? $activePaymentFilter : null, 'direction' => $activeDirection !== 'all' ? $activeDirection : null])) }}" class="btn btn-ghost btn-sm">Reset</a>
                    </div>
                </form>
                <div class="payments-mobile-list">
                    @forelse($allLivePayments as $payment)
                        @php
                            $isReturnTrip = (bool) ($payment->trip?->is_return_trip ?? false);
                            $pickupName = $payment->trip?->pickup_name ?? '-';
                            $pickupLat = $payment->trip?->pickup_latitude ?? '';
                            $pickupLng = $payment->trip?->pickup_longitude ?? '';
                            $destinationName = $payment->trip?->destination_name ?? '-';
                            $destinationLat = $payment->trip?->destination_latitude ?? '';
                            $destinationLng = $payment->trip?->destination_longitude ?? '';
                            $pairedTripId = $isReturnTrip
                                ? ($payment->trip?->parentTrip?->id)
                                : ($payment->trip?->returnTrip?->id);
                            $routeLabel = $payment->trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                            $statusClass = $payment->payment_status === 'paid'
                                ? 'status-paid'
                                : ($payment->payment_status === 'pending_confirmation' ? 'status-pending' : 'status-unpaid');
                            $statusText = $payment->payment_status === 'pending_confirmation'
                                ? 'Pending Confirmation'
                                : ($payment->payment_status === 'paid' ? 'Paid' : ($payment->payment_status === 'unpaid' ? 'Unpaid' : ucfirst($payment->payment_status)));
                            $methodLabel = match ($payment->payment_method) {
                                'duitnow_qr' => 'DuitNow QR',
                                'bank_account' => 'Bank Account',
                                'digital_wallet' => 'Digital Wallet',
                                'others' => 'Others',
                                default => '-',
                            };
                            $reminderMeta = $reminderState[$payment->id] ?? ['can_send' => true, 'seconds_left' => 0];
                            $canSendReminder = (bool) $reminderMeta['can_send'];
                            $secondsLeft = (int) ($reminderMeta['seconds_left'] ?? 0);
                            $participantsPayload = $payment->trip?->participants?->map(function ($participant) {
                                $participantUser = $participant->user;
                                return [
                                    'name' => $participantUser?->name ?: '-',
                                    'email' => $participantUser?->email ?: '',
                                    'photo_url' => $participantUser?->profile_photo
                                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($participantUser->profile_photo)
                                        : null,
                                    'is_driver' => (bool) $participant->is_driver,
                                ];
                            })->values()->all() ?? [];
                            $driverPhotoUrl = $payment->trip?->driver?->profile_photo
                                ? \Illuminate\Support\Facades\Storage::disk('public')->url($payment->trip->driver->profile_photo)
                                : '';
                            $driverBank = $payment->trip?->driver?->payment_bank_name ?: '-';
                            $driverAccountName = $payment->trip?->driver?->payment_account_name ?: '-';
                            $driverAccountNumber = $payment->trip?->driver?->payment_account_number ?: '-';
                            $driverDuitnowQr = $payment->trip?->driver?->payment_qr_duitnow_url ?: '';
                            $driverTngQr = $payment->trip?->driver?->payment_qr_tng_url ?: '';
                            $fareBreakdown = $paymentFareBreakdown($payment);
                            $isDriverQueueRecord = $isAdmin || (
                                (int) ($payment->trip?->driver_id ?? 0) === (int) auth()->id()
                                && (int) $payment->user_id !== (int) auth()->id()
                            );
                            $counterparty = $isDriverQueueRecord
                                ? ($payment->user?->name ?: '-')
                                : ($payment->trip?->driver?->name ?: '-');
                            $initials = $paymentInitials($counterparty);
                            $amountSign = $isDriverQueueRecord ? '+' : '-';
                            $shortStatusText = $payment->payment_status === 'pending_confirmation'
                                ? ($isAdmin ? 'Admin Review' : 'Driver Review')
                                : $statusText;
                            $perspective = $isDriverQueueRecord ? 'collect' : 'pay';
                            $perspectiveLabel = $isAdmin ? 'Admin review' : ($isDriverQueueRecord ? 'You collect' : 'You pay');
                            $paymentActionLabel = $isDriverQueueRecord
                                ? ($payment->payment_status === 'pending_confirmation' ? 'Review' : ($payment->payment_status === 'unpaid' ? 'Notify' : 'Receipt'))
                                : ($payment->payment_status === 'unpaid' ? 'Pay' : ($payment->payment_status === 'pending_confirmation' ? 'Pending' : 'Receipt'));
                            $paymentActionIcon = $isDriverQueueRecord
                                ? ($payment->payment_status === 'pending_confirmation' ? 'fa-solid fa-clipboard-check' : ($payment->payment_status === 'unpaid' ? 'fa-regular fa-bell' : 'fa-solid fa-receipt'))
                                : ($payment->payment_status === 'unpaid' ? 'fa-solid fa-credit-card' : ($payment->payment_status === 'pending_confirmation' ? 'fa-regular fa-clock' : 'fa-solid fa-receipt'));
                        @endphp
                        <article
                            class="payment-mobile-item open-trip-card js-payment-filter-item"
                            data-payment-perspective="{{ $perspective }}"
                            data-pmt-status="{{ $payment->payment_status }}"
                            data-filter-date="{{ $payment->trip?->trip_datetime?->format('Y-m-d') ?: '' }}"
                            data-filter-visibility="{{ $payment->trip?->visibility ?: '' }}"
                            data-filter-person="{{ trim(($payment->user?->name ?: auth()->user()->name) . ' ' . ($payment->trip?->driver?->name ?: '')) }}"
                            data-trip-id="{{ $payment->trip_id }}"
                            data-route="{{ $routeLabel }}"
                            data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                            data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                            data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                            data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                            data-pickup-name="{{ $pickupName }}"
                            data-pickup-lat="{{ $pickupLat }}"
                            data-pickup-lng="{{ $pickupLng }}"
                            data-destination-name="{{ $destinationName }}"
                            data-destination-lat="{{ $destinationLat }}"
                            data-destination-lng="{{ $destinationLng }}"
                            data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                            data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                            data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                            data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                            data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                            data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                            data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                            data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                            data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                            data-payment-status="{{ $statusText }}"
                            data-payment-method="{{ $methodLabel }}"
                            data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                            data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                            data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                            data-participants='@json($participantsPayload)'
                            data-passenger-count="{{ count($participantsPayload) }}"
                        >
                            <div class="payment-mobile-top">
                                <div style="min-width:0;">
                                    <h2 class="payment-route-title">{{ $routeLabel }}</h2>
                                    <div class="payment-meta-inline">
                                        <span class="payment-meta-inline-item">
                                            <i class="fa-solid fa-user"></i>
                                            <span>{{ $counterparty }}</span>
                                        </span>
                                        <span class="payment-meta-inline-item">
                                            <i class="{{ $isDriverQueueRecord ? 'fa-solid fa-sack-dollar' : 'fa-solid fa-credit-card' }}"></i>
                                            <span>{{ $perspectiveLabel }}</span>
                                        </span>
                                    </div>
                                </div>
                                <span class="status-chip {{ $statusClass }}">{{ $shortStatusText }}</span>
                            </div>
                            <div class="payment-detail-grid">
                                <div class="payment-detail-line">
                                    @if($payment->trip?->trip_datetime)
                                        <span class="payment-detail-date">{{ $payment->trip->trip_datetime->format('d M Y') }} &middot; {{ $payment->trip->trip_datetime->format('H:i') }}</span>
                                    @else
                                        <span class="payment-detail-date">-</span>
                                    @endif
                                </div>
                            </div>
                            <div class="payment-bottom-row">
                                <div class="payment-fare-card">
                                    <span class="payment-fare-value">RM {{ number_format((float) $payment->amount_due, 2) }}</span>
                                    @if($fareBreakdown['has_extra'])
                                        <span class="payment-fare-label">Base RM {{ number_format((float) $fareBreakdown['base'], 2) }} + Extra RM {{ number_format((float) $fareBreakdown['extra'], 2) }}</span>
                                    @endif
                                </div>
                                <div class="payments-action-row">
                                @if($isDriverQueueRecord && $payment->payment_status === 'pending_confirmation')
                                    <button
                                        type="button"
                                        class="payments-btn payments-btn-highlight open-request-btn"
                                        data-passenger="{{ $payment->user?->name ?: '-' }}"
                                        data-trip="#{{ $payment->trip_id }}"
                                        data-method="{{ $methodLabel }}"
                                        data-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-approve-action="{{ route('payments.confirm-paid', $payment) }}"
                                        data-reject-action="{{ route('payments.reject-paid', $payment) }}"
                                    ><i class="{{ $paymentActionIcon }}"></i> {{ $paymentActionLabel }}</button>
                                @elseif($isDriverQueueRecord && $payment->payment_status === 'unpaid')
                                    <form method="POST" action="{{ route('payments.send-reminder', $payment) }}" class="payments-action-row">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="payments-btn payments-btn-soft {{ $canSendReminder ? '' : 'is-disabled' }} reminder-btn"
                                            {{ $canSendReminder ? '' : 'disabled' }}
                                            data-payment-id="{{ $payment->id }}"
                                            data-seconds-left="{{ $secondsLeft }}"
                                        >
                                            <i class="{{ $paymentActionIcon }}"></i>
                                            {!! $canSendReminder ? $paymentActionLabel : gmdate('H:i:s', $secondsLeft) !!}
                                        </button>
                                    </form>
                                @elseif(! $isDriverQueueRecord && $payment->payment_status === 'unpaid')
                                    <form method="POST" action="{{ route('payments.mark-paid', $payment) }}" class="payments-action-row">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="payment_method" value="duitnow_qr">
                                        <input class="payments-input" type="text" name="remarks" placeholder="Remarks">
                                        <button
                                            type="button"
                                            class="payments-btn payments-btn-primary open-payment-paynow-btn"
                                            data-action="{{ route('payments.mark-paid', $payment) }}"
                                            data-passenger="{{ $payment->user?->name ?: auth()->user()->name }}"
                                            data-initials="{{ $paymentInitials($payment->user?->name ?: auth()->user()->name) }}"
                                            data-trip="Trip #{{ $payment->trip_id }}"
                                            data-route="{{ $routeLabel }}"
                                            data-amount="{{ number_format((float) $payment->amount_due, 2) }}"
                                            data-base-amount="{{ number_format((float) $fareBreakdown['base'], 2) }}"
                                            data-extra-fee="{{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                            data-has-extra="{{ $fareBreakdown['has_extra'] ? '1' : '0' }}"
                                            data-driver-name="{{ $payment->trip?->driver?->name ?: '-' }}"
                                            data-driver-email="{{ $payment->trip?->driver?->email ?: '-' }}"
                                            data-driver-photo="{{ $driverPhotoUrl }}"
                                            data-driver-bank="{{ $driverBank }}"
                                            data-driver-account-name="{{ $driverAccountName }}"
                                            data-driver-account-number="{{ $driverAccountNumber }}"
                                            data-driver-duitnow-qr="{{ $driverDuitnowQr }}"
                                            data-driver-tng-qr="{{ $driverTngQr }}"
                                        ><i class="{{ $paymentActionIcon }}"></i> {{ $paymentActionLabel }}</button>
                                    </form>
                                @elseif($payment->payment_status === 'pending_confirmation')
                                    <button
                                        type="button"
                                        class="payments-btn payments-btn-soft open-trip-modal-btn"
                                        data-trip-id="{{ $payment->trip_id }}"
                                        data-route="{{ $routeLabel }}"
                                        data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                        data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $pickupLat }}"
                                        data-pickup-lng="{{ $pickupLng }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $destinationLat }}"
                                        data-destination-lng="{{ $destinationLng }}"
                                        data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                        data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                                        data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                                        data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                                        data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                        data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                        data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                                        data-payment-status="{{ $statusText }}"
                                        data-payment-method="{{ $methodLabel }}"
                                        data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-participants='@json($participantsPayload)'
                                        data-passenger-count="{{ count($participantsPayload) }}"
                                    ><i class="{{ $paymentActionIcon }}"></i> {{ $paymentActionLabel }}</button>
                                @else
                                    <button
                                        type="button"
                                        class="payments-btn payments-btn-primary open-payment-receipt-btn"
                                        data-receipt-no="PAY-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}"
                                        data-route="{{ $routeLabel }}"
                                        data-passenger="{{ $payment->user?->name ?: '-' }}"
                                        data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                        data-amount="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                        data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                        data-has-extra="{{ $fareBreakdown['has_extra'] ? '1' : '0' }}"
                                        data-method="{{ $methodLabel }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('d M Y, H:i') ?: '-' }}"
                                        data-confirmed-at="{{ $payment->confirmed_at?->format('d M Y, H:i') ?: '-' }}"
                                    ><i class="{{ $paymentActionIcon }}"></i> {{ $paymentActionLabel }}</button>
                                @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="payment-mobile-item" style="text-align:center; padding:32px 16px; color:#64748b; font-size:13px;">No payment records found.</div>
                    @endforelse
                </div>
                <div class="payments-table-wrap">
                    <table class="payments-table">
                        <thead>
                        <tr>
                            <th>Counterparty</th>
                            <th>Trip</th>
                            <th>Status</th>
                            <th class="right">Amount</th>
                            <th>Date</th>
                            <th class="right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                    @forelse($allLivePayments as $payment)
                            @php
                                $isReturnTrip = (bool) ($payment->trip?->is_return_trip ?? false);
                                $pickupName = $payment->trip?->pickup_name ?? '-';
                                $pickupLat = $payment->trip?->pickup_latitude ?? '';
                                $pickupLng = $payment->trip?->pickup_longitude ?? '';
                                $destinationName = $payment->trip?->destination_name ?? '-';
                                $destinationLat = $payment->trip?->destination_latitude ?? '';
                                $destinationLng = $payment->trip?->destination_longitude ?? '';
                                $pairedTripId = $isReturnTrip
                                    ? ($payment->trip?->parentTrip?->id)
                                    : ($payment->trip?->returnTrip?->id);
                                $routeLabel = $payment->trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                                $statusClass = $payment->payment_status === 'paid'
                                    ? 'status-paid'
                                    : ($payment->payment_status === 'pending_confirmation' ? 'status-pending' : 'status-unpaid');
                                $statusText = $payment->payment_status === 'pending_confirmation'
                                    ? 'Pending Confirmation'
                                    : ucfirst($payment->payment_status);
                                $methodLabel = match ($payment->payment_method) {
                                    'duitnow_qr' => 'DuitNow QR',
                                    'bank_account' => 'Bank Account',
                                    'digital_wallet' => 'Digital Wallet',
                                    'others' => 'Others',
                                    default => '-',
                                };
                                $reminderMeta = $reminderState[$payment->id] ?? ['can_send' => true, 'seconds_left' => 0];
                                $canSendReminder = (bool) $reminderMeta['can_send'];
                                $secondsLeft = (int) ($reminderMeta['seconds_left'] ?? 0);
                                $participantsPayload = $payment->trip?->participants?->map(function ($participant) {
                                    $participantUser = $participant->user;
                                    return [
                                        'name' => $participantUser?->name ?: '-',
                                        'email' => $participantUser?->email ?: '',
                                        'photo_url' => $participantUser?->profile_photo
                                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($participantUser->profile_photo)
                                            : null,
                                        'is_driver' => (bool) $participant->is_driver,
                                    ];
                                })->values()->all() ?? [];
                                $driverPhotoUrl = $payment->trip?->driver?->profile_photo
                                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($payment->trip->driver->profile_photo)
                                    : '';
                                $driverBank = $payment->trip?->driver?->payment_bank_name ?: '-';
                                $driverAccountName = $payment->trip?->driver?->payment_account_name ?: '-';
                                $driverAccountNumber = $payment->trip?->driver?->payment_account_number ?: '-';
                                $driverDuitnowQr = $payment->trip?->driver?->payment_qr_duitnow_url ?: '';
                                $driverTngQr = $payment->trip?->driver?->payment_qr_tng_url ?: '';
                                $fareBreakdown = $paymentFareBreakdown($payment);
                                $isDriverQueueRecord = $isAdmin || (
                                    (int) ($payment->trip?->driver_id ?? 0) === (int) auth()->id()
                                    && (int) $payment->user_id !== (int) auth()->id()
                                );
                                $counterparty = $isDriverQueueRecord
                                    ? ($payment->user?->name ?: '-')
                                    : ($payment->trip?->driver?->name ?: '-');
                            $amountSign = $isDriverQueueRecord ? '+' : '-';
                            $shortStatusText = $payment->payment_status === 'pending_confirmation'
                                ? ($isAdmin ? 'Admin Review' : 'Driver Review')
                                : $statusText;
                            $perspective = $isDriverQueueRecord ? 'collect' : 'pay';
                            $perspectiveLabel = $isAdmin ? 'Admin review' : ($isDriverQueueRecord ? 'You collect' : 'You pay');
                        @endphp
                            <tr
                                class="open-trip-card js-payment-filter-item"
                                data-payment-perspective="{{ $perspective }}"
                                data-pmt-status="{{ $payment->payment_status }}"
                                data-filter-date="{{ $payment->trip?->trip_datetime?->format('Y-m-d') ?: '' }}"
                                data-filter-visibility="{{ $payment->trip?->visibility ?: '' }}"
                                data-filter-person="{{ trim(($payment->user?->name ?: auth()->user()->name) . ' ' . ($payment->trip?->driver?->name ?: '')) }}"
                                data-trip-id="{{ $payment->trip_id }}"
                                data-route="{{ $routeLabel }}"
                                data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                                data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                                data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                                data-pickup-name="{{ $pickupName }}"
                                data-pickup-lat="{{ $pickupLat }}"
                                data-pickup-lng="{{ $pickupLng }}"
                                data-destination-name="{{ $destinationName }}"
                                data-destination-lat="{{ $destinationLat }}"
                                data-destination-lng="{{ $destinationLng }}"
                                data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                                data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                                data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                                data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                                data-payment-status="{{ $statusText }}"
                                data-payment-method="{{ $methodLabel }}"
                                data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                data-participants='@json($participantsPayload)'
                                data-passenger-count="{{ count($participantsPayload) }}"
                            >
                                <td>
                                    <div class="payment-person-block">
                                        <div>
                                            <div class="payment-name">{{ $counterparty }}</div>
                                            <div class="payment-meta">{{ $perspectiveLabel }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="payment-route-title">{{ $routeLabel }}</div>
                                    <div class="payment-trip-meta">
                                        <span><i class="fa-solid fa-hashtag"></i> Trip {{ $payment->trip_id }}</span>
                                        <span><i class="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'fa-solid fa-repeat' : 'fa-solid fa-route' }}"></i> {{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two-way' : 'One-way' }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="payments-link open-trip-modal-btn"
                                        data-trip-id="{{ $payment->trip_id }}"
                                        data-route="{{ $routeLabel }}"
                                        data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                        data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $pickupLat }}"
                                        data-pickup-lng="{{ $pickupLng }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $destinationLat }}"
                                        data-destination-lng="{{ $destinationLng }}"
                                        data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                        data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                                        data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                                        data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                                        data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                        data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                        data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                                        data-payment-status="{{ $statusText }}"
                                        data-payment-method="{{ $methodLabel }}"
                                        data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-participants='@json($participantsPayload)'
                                        data-passenger-count="{{ count($participantsPayload) }}"
                                    ><span>View</span></button>
                                </td>
                                <td><span class="status-chip {{ $statusClass }}">{{ $shortStatusText }}</span></td>
                                <td class="right">
                                    <span class="payment-table-amount">{{ $amountSign }}RM {{ number_format((float) $payment->amount_due, 2) }}</span>
                                    @if($fareBreakdown['has_extra'])
                                        <div style="font-size:11px;color:#64748b;font-weight:700;">Base RM {{ number_format((float) $fareBreakdown['base'], 2) }} + Extra RM {{ number_format((float) $fareBreakdown['extra'], 2) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->trip?->trip_datetime)
                                        <span class="payment-table-date">
                                            {{ $payment->trip->trip_datetime->format('d M Y') }}
                                            <span class="payment-table-time">{{ $payment->trip->trip_datetime->format('H:i') }}</span>
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="right">
                                    @if($isDriverQueueRecord && $payment->payment_status === 'pending_confirmation')
                                        <div class="payments-action-row">
                                            <button
                                                type="button"
                                                class="payments-btn payment-table-action open-request-btn"
                                                data-passenger="{{ $payment->user?->name ?: '-' }}"
                                                data-trip="#{{ $payment->trip_id }}"
                                                data-method="{{ $methodLabel }}"
                                                data-remarks="{{ $payment->remarks ?: '-' }}"
                                                data-marked="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                                data-approve-action="{{ route('payments.confirm-paid', $payment) }}"
                                                data-reject-action="{{ route('payments.reject-paid', $payment) }}"
                                            ><i class="fa-solid fa-clipboard-check"></i> Review</button>
                                        </div>
                                    @elseif($isDriverQueueRecord && $payment->payment_status === 'unpaid')
                                        <form method="POST" action="{{ route('payments.send-reminder', $payment) }}" class="payments-action-row">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="payments-btn payment-table-action {{ $canSendReminder ? '' : 'is-disabled' }} reminder-btn"
                                                {{ $canSendReminder ? '' : 'disabled' }}
                                                data-payment-id="{{ $payment->id }}"
                                                data-seconds-left="{{ $secondsLeft }}"
                                            >
                                                @if($canSendReminder)
                                                    <i class="fa-regular fa-bell"></i> Notify
                                                @else
                                                    {{ gmdate('H:i:s', $secondsLeft) }}
                                                @endif
                                            </button>
                                        </form>
                                    @elseif(! $isDriverQueueRecord && $payment->payment_status === 'unpaid')
                                        <form method="POST" action="{{ route('payments.mark-paid', $payment) }}" class="payments-action-row">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="payment_method" value="duitnow_qr">
                                            <input class="payments-input" type="text" name="remarks" placeholder="Remarks">
                                            <button
                                                type="button"
                                                class="payments-btn payment-table-action open-payment-paynow-btn"
                                                data-action="{{ route('payments.mark-paid', $payment) }}"
                                                data-passenger="{{ $payment->user?->name ?: auth()->user()->name }}"
                                                data-initials="{{ $paymentInitials($payment->user?->name ?: auth()->user()->name) }}"
                                                data-trip="Trip #{{ $payment->trip_id }}"
                                                data-route="{{ $routeLabel }}"
                                                data-amount="{{ number_format((float) $payment->amount_due, 2) }}"
                                                data-base-amount="{{ number_format((float) $fareBreakdown['base'], 2) }}"
                                                data-extra-fee="{{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                                data-has-extra="{{ $fareBreakdown['has_extra'] ? '1' : '0' }}"
                                                data-driver-name="{{ $payment->trip?->driver?->name ?: '-' }}"
                                                data-driver-email="{{ $payment->trip?->driver?->email ?: '-' }}"
                                                data-driver-photo="{{ $driverPhotoUrl }}"
                                                data-driver-bank="{{ $driverBank }}"
                                                data-driver-account-name="{{ $driverAccountName }}"
                                                data-driver-account-number="{{ $driverAccountNumber }}"
                                                data-driver-duitnow-qr="{{ $driverDuitnowQr }}"
                                                data-driver-tng-qr="{{ $driverTngQr }}"
                                            ><i class="fa-solid fa-credit-card"></i> Pay</button>
                                        </form>
                                    @elseif($payment->payment_status === 'pending_confirmation')
                                        <span class="payments-btn payment-table-action is-muted"><i class="fa-regular fa-clock"></i> Pending</span>
                                    @else
                                        <button
                                            type="button"
                                            class="payments-btn payment-table-action open-payment-receipt-btn"
                                            data-receipt-no="PAY-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}"
                                            data-route="{{ $routeLabel }}"
                                            data-passenger="{{ $payment->user?->name ?: '-' }}"
                                            data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                            data-amount="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                            data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                            data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                            data-has-extra="{{ $fareBreakdown['has_extra'] ? '1' : '0' }}"
                                            data-method="{{ $methodLabel }}"
                                            data-marked-at="{{ $payment->marked_paid_at?->format('d M Y, H:i') ?: '-' }}"
                                            data-confirmed-at="{{ $payment->confirmed_at?->format('d M Y, H:i') ?: '-' }}"
                                        ><i class="fa-solid fa-receipt"></i> Receipt</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No payment records found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="payments-filter-empty" data-filter-empty>No payment records match the current filters.</div>
                @if($mainPaymentsPaginator)
                <div style="margin-top:12px;">
                    {{ $mainPaymentsPaginator->appends(request()->query())->links() }}
                </div>
                @endif
            </section>
            <aside class="payments-side-panel">
                <section class="payments-total-card">
                    @if($hasSplitPaymentDirections)
                        <input class="payments-summary-mode-input" type="radio" name="desktop_summary_mode" id="desktopSummaryDriver" checked>
                        <input class="payments-summary-mode-input" type="radio" name="desktop_summary_mode" id="desktopSummaryPassenger">
                        <div class="payments-summary-top">
                            <span class="payments-total-label">{{ strtoupper($monthLabel) }}</span>
                            <div class="payments-summary-switch" aria-label="Summary view">
                                <label for="desktopSummaryDriver">As driver</label>
                                <label for="desktopSummaryPassenger">As passenger</label>
                            </div>
                        </div>
                        <div class="payments-summary-mode-panel payments-summary-driver-panel">
                            <strong>RM {{ number_format($driverUnpaidAmount + $driverPendingAmount + $driverPaidAmount, 2) }}</strong>
                            <small>To collect · driver collection view</small>
                            <div class="payments-total-metrics">
                                <div class="payments-total-metric">
                                    <span>Unpaid by passengers</span>
                                    <b>RM {{ number_format($driverUnpaidAmount, 2) }}</b>
                                </div>
                                <div class="payments-total-metric">
                                    <span>Pending confirmation</span>
                                    <b>RM {{ number_format($driverPendingAmount, 2) }}</b>
                                </div>
                                <div class="payments-total-metric">
                                    <span>Paid received</span>
                                    <b>RM {{ number_format($driverPaidAmount, 2) }}</b>
                                </div>
                            </div>
                        <div class="payments-summary-panel">
                            <div class="payments-summary-panel-title">Passenger payment status</div>
                            <div class="payments-summary-detail-list">
                                @forelse($driverCollectionRows as $debtRow)
                                    <div class="payments-summary-detail-row">
                                        <span>{{ $debtRow['records'] }} records</span>
                                        <strong>{{ $debtRow['name'] }}</strong>
                                        <div class="payments-summary-amount-row">
                                            <span class="payments-summary-amount-chip is-unpaid">Unpaid <strong>RM {{ number_format((float) $debtRow['unpaid'], 2) }}</strong></span>
                                            <span class="payments-summary-amount-chip is-pending">Pending <strong>RM {{ number_format((float) $debtRow['pending'], 2) }}</strong></span>
                                            <span class="payments-summary-amount-chip is-paid">Paid <strong>RM {{ number_format((float) $debtRow['paid'], 2) }}</strong></span>
                                            <span class="payments-summary-amount-chip is-total">Total <strong>RM {{ number_format((float) $debtRow['total'], 2) }}</strong></span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="payments-summary-detail-empty">No passenger payment still pending.</div>
                                @endforelse
                            </div>
                        </div>
                        </div>
                        <div class="payments-summary-mode-panel payments-summary-passenger-panel">
                            <strong>RM {{ number_format($myUnpaidAmount + $myPendingAmount, 2) }}</strong>
                            <small>To pay · passenger payment view</small>
                            <div class="payments-total-metrics">
                                <div class="payments-total-metric">
                                    <span>Unpaid to drivers</span>
                                    <b>RM {{ number_format($myUnpaidAmount, 2) }}</b>
                                </div>
                                <div class="payments-total-metric">
                                    <span>Pending confirmation</span>
                                    <b>RM {{ number_format($myPendingAmount, 2) }}</b>
                                </div>
                                <div class="payments-total-metric">
                                    <span>Paid</span>
                                    <b>RM {{ number_format($myPaidAmount, 2) }}</b>
                                </div>
                            </div>
                        <div class="payments-summary-panel">
                            <div class="payments-summary-panel-title">Your passenger payments</div>
                            <div class="payments-summary-detail-list">
                                @forelse($passengerPayRows as $payRow)
                                    <div class="payments-summary-detail-row">
                                        <span>{{ $payRow['records'] }} records</span>
                                        <strong>{{ $payRow['name'] }}</strong>
                                        <div class="payments-summary-amount-row">
                                            <span class="payments-summary-amount-chip is-unpaid">Unpaid <strong>RM {{ number_format((float) $payRow['unpaid'], 2) }}</strong></span>
                                            <span class="payments-summary-amount-chip is-pending">Pending <strong>RM {{ number_format((float) $payRow['pending'], 2) }}</strong></span>
                                            <span class="payments-summary-amount-chip is-paid">Paid <strong>RM {{ number_format((float) $payRow['paid'], 2) }}</strong></span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="payments-summary-detail-empty">No active passenger payment due.</div>
                                @endforelse
                            </div>
                        </div>
                        </div>
                    @else
                        <span class="payments-total-label">{{ strtoupper($monthLabel) }}</span>
                        <strong>RM {{ number_format($summaryMainAmount, 2) }}</strong>
                        <small>{{ $summaryMainLabel }} · {{ $isAdmin ? 'admin payment view' : 'passenger payment view' }}</small>
                        <div class="payments-total-metrics">
                            <div class="payments-total-metric">
                                <span>Unpaid</span>
                                <b>RM {{ number_format($summaryPrimaryAmount, 2) }}</b>
                            </div>
                            <div class="payments-total-metric">
                                <span>Pending</span>
                                <b>RM {{ number_format($summarySecondaryAmount, 2) }}</b>
                            </div>
                            <div class="payments-total-metric">
                                <span>Paid</span>
                                <b>RM {{ number_format($summaryPaidAmount, 2) }}</b>
                            </div>
                        </div>
                        <div class="payments-summary-panel">
                            <div class="payments-summary-panel-title">{{ $summaryDetailTitle }}</div>
                            <div class="payments-summary-detail-list">
                                @forelse($summaryDetailRows as $payRow)
                                    <div class="payments-summary-detail-row">
                                        <span>{{ $payRow['records'] }} records</span>
                                        <strong>{{ $payRow['name'] }}</strong>
                                        <div class="payments-summary-amount-row">
                                            <span class="payments-summary-amount-chip is-unpaid">Unpaid <strong>RM {{ number_format((float) $payRow['unpaid'], 2) }}</strong></span>
                                            <span class="payments-summary-amount-chip is-pending">Pending <strong>RM {{ number_format((float) $payRow['pending'], 2) }}</strong></span>
                                            <span class="payments-summary-amount-chip is-paid">Paid <strong>RM {{ number_format((float) $payRow['paid'], 2) }}</strong></span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="payments-summary-detail-empty">No active payment due right now.</div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </section>
            </aside>
            </div>

        @if($hasSplitPaymentDirections)
            <section class="payments-card" id="queue-summary">
                <h2 class="payments-section-title">Queue Summary</h2>
                <div class="payments-summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Queue Unpaid</div>
                        <div id="paymentsQueueUnpaidAmount" class="summary-value">RM {{ number_format((float) ($summary['driver']['unpaid']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsQueueUnpaidCount" class="summary-count">{{ $summary['driver']['unpaid']['count'] ?? 0 }} records</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Queue Pending</div>
                        <div id="paymentsQueuePendingAmount" class="summary-value">RM {{ number_format((float) ($summary['driver']['pending_confirmation']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsQueuePendingCount" class="summary-count">{{ $summary['driver']['pending_confirmation']['count'] ?? 0 }} records</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Queue Paid</div>
                        <div id="paymentsQueuePaidAmount" class="summary-value">RM {{ number_format((float) ($summary['driver']['paid']['amount'] ?? 0), 2) }}</div>
                        <div id="paymentsQueuePaidCount" class="summary-count">{{ $summary['driver']['paid']['count'] ?? 0 }} records</div>
                    </div>
                </div>

                <div class="debt-summary-card">
                    <div class="debt-summary-top">
                        <h3 class="debt-summary-title">Passenger Debt Summary</h3>
                        <span id="paymentsDebtTotal" class="debt-summary-total">
                            RM {{ number_format((float) ($passengerDebtSummary['total_amount'] ?? 0), 2) }}
                        </span>
                    </div>
                    <div id="paymentsDebtMeta" class="summary-count">
                        {{ (int) ($passengerDebtSummary['passenger_count'] ?? 0) }} passengers,
                        {{ (int) ($passengerDebtSummary['total_records'] ?? 0) }} active records (unpaid + pending).
                    </div>
                    <div class="debt-list">
                        @forelse(($passengerDebtSummary['rows'] ?? []) as $debtRow)
                            <div class="debt-item">
                                <div>
                                    <div class="debt-name">{{ $debtRow['passenger_name'] }}</div>
                                    <div class="debt-meta">
                                        {{ $debtRow['records'] }} records •
                                        Unpaid RM {{ number_format((float) $debtRow['unpaid_amount'], 2) }} •
                                        Pending RM {{ number_format((float) $debtRow['pending_amount'], 2) }}
                                    </div>
                                </div>
                                <div class="debt-amount">RM {{ number_format((float) $debtRow['total_amount'], 2) }}</div>
                            </div>
                        @empty
                            <div class="debt-item">
                                <div class="debt-meta">No active debt records for passengers.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="payments-card" id="driver-review-list">
                <h2 class="payments-section-title">{{ $reviewTitle }}</h2>
                <p class="payments-section-subtitle">{{ $reviewSubtitle }}</p>
                <div class="payments-filter-panel js-payments-filter" data-filter-scope="#driver-review-list">
                    <div class="payments-filter-head">
                        <p class="payments-filter-hint">Filters apply automatically.</p>
                        <button type="button" class="payments-filter-toggle" data-filter-toggle aria-expanded="false">
                            <i class="fa-solid fa-filter"></i><span>Filter</span>
                        </button>
                    </div>
                    <div class="payments-filter-body">
                        <div class="payments-filter-grid">
                            <div class="payments-filter-field">
                                <label for="driverReviewFromDate">From Date</label>
                                <input id="driverReviewFromDate" class="payments-filter-input" type="date" data-filter-from>
                            </div>
                            <div class="payments-filter-field">
                                <label for="driverReviewToDate">To Date</label>
                                <input id="driverReviewToDate" class="payments-filter-input" type="date" data-filter-to>
                            </div>
                            <div class="payments-filter-field">
                                <label for="driverReviewVisibility">Visibility</label>
                                <select id="driverReviewVisibility" class="payments-filter-input" data-filter-visibility>
                                    <option value="">All</option>
                                    <option value="public">Public</option>
                                    <option value="private">Private</option>
                                </select>
                            </div>
                            <div class="payments-filter-field">
                                <label for="driverReviewPassengerSearch">Search Passenger</label>
                                <input id="driverReviewPassengerSearch" class="payments-filter-input" type="search" placeholder="Search passenger name" data-filter-person>
                            </div>
                        </div>
                        <div class="payments-filter-actions">
                            <button type="button" class="payments-filter-reset" data-filter-reset>Reset</button>
                        </div>
                    </div>
                </div>
                <div class="payments-mobile-list">
                    @forelse(($driverPayments ?? collect()) as $payment)
                        @php
                            $isReturnTrip = (bool) ($payment->trip?->is_return_trip ?? false);
                            $pickupName = $payment->trip?->pickup_name ?? '-';
                            $pickupLat = $payment->trip?->pickup_latitude ?? '';
                            $pickupLng = $payment->trip?->pickup_longitude ?? '';
                            $destinationName = $payment->trip?->destination_name ?? '-';
                            $destinationLat = $payment->trip?->destination_latitude ?? '';
                            $destinationLng = $payment->trip?->destination_longitude ?? '';
                            $pairedTripId = $isReturnTrip
                                ? ($payment->trip?->parentTrip?->id)
                                : ($payment->trip?->returnTrip?->id);
                            $routeLabel = $payment->trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                            $statusClass = $payment->payment_status === 'paid'
                                ? 'status-paid'
                                : ($payment->payment_status === 'pending_confirmation' ? 'status-pending' : 'status-unpaid');
                            $statusText = $payment->payment_status === 'pending_confirmation'
                                ? 'Pending Confirmation'
                                : ($payment->payment_status === 'paid' ? 'Paid' : ($payment->payment_status === 'unpaid' ? 'Unpaid' : ucfirst($payment->payment_status)));
                            $methodLabel = match ($payment->payment_method) {
                                'duitnow_qr' => 'DuitNow QR',
                                'bank_account' => 'Bank Account',
                                'digital_wallet' => 'Digital Wallet',
                                'others' => 'Others',
                                default => '-',
                            };
                            $reminderMeta = $reminderState[$payment->id] ?? ['can_send' => true, 'seconds_left' => 0];
                            $canSendReminder = (bool) $reminderMeta['can_send'];
                            $secondsLeft = (int) ($reminderMeta['seconds_left'] ?? 0);
                            $participantsPayload = $payment->trip?->participants?->map(function ($participant) {
                                $participantUser = $participant->user;
                                return [
                                    'name' => $participantUser?->name ?: '-',
                                    'email' => $participantUser?->email ?: '',
                                    'photo_url' => $participantUser?->profile_photo
                                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($participantUser->profile_photo)
                                        : null,
                                    'is_driver' => (bool) $participant->is_driver,
                                ];
                            })->values()->all() ?? [];
                            $fareBreakdown = $paymentFareBreakdown($payment);
                        @endphp
                        <article
                            class="payment-mobile-item open-trip-card js-payment-filter-item"
                            data-filter-date="{{ $payment->trip?->trip_datetime?->format('Y-m-d') ?: '' }}"
                            data-filter-visibility="{{ $payment->trip?->visibility ?: '' }}"
                            data-filter-person="{{ $payment->user?->name ?: '' }}"
                        >
                            <div class="payment-mobile-top">
                                <div>
                                    <div class="payment-mobile-trip">Trip #{{ $payment->trip_id }}</div>
                                    <div class="payment-mobile-sub">{{ $routeLabel }}</div>
                                    <button
                                        type="button"
                                        class="payments-link open-trip-modal-btn"
                                        data-trip-id="{{ $payment->trip_id }}"
                                        data-route="{{ $routeLabel }}"
                                        data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                        data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $pickupLat }}"
                                        data-pickup-lng="{{ $pickupLng }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $destinationLat }}"
                                        data-destination-lng="{{ $destinationLng }}"
                                        data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                        data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                                        data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                                        data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                                        data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                        data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                        data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                                        data-payment-status="{{ $statusText }}"
                                        data-payment-method="{{ $methodLabel }}"
                                        data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-participants='@json($participantsPayload)'
                                        data-passenger-count="{{ count($participantsPayload) }}"
                                    ><i class="fa-regular fa-eye"></i><span>See Details</span></button>
                                </div>
                                <span class="status-chip {{ $statusClass }}">{{ $statusText }}</span>
                            </div>
                            <div class="payment-mobile-grid">
                                <div class="payment-mobile-line">
                                    <span>Passenger</span>
                                    <strong>{{ $payment->user?->name ?: '-' }}</strong>
                                </div>
                                <div class="payment-mobile-line">
                                    <span>Amount</span>
                                    <strong>
                                        RM {{ number_format((float) $payment->amount_due, 2) }}
                                        @if($fareBreakdown['has_extra'])
                                            <small style="display:block;color:#64748b;">Base RM {{ number_format((float) $fareBreakdown['base'], 2) }} + Extra RM {{ number_format((float) $fareBreakdown['extra'], 2) }}</small>
                                        @endif
                                    </strong>
                                </div>
                                <div class="payment-mobile-line">
                                    <span>Marked At</span>
                                    <strong>{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}</strong>
                                </div>
                            </div>
                            <div class="queue-actions">
                                <div class="queue-actions-main">
                                @if($payment->payment_status === 'pending_confirmation')
                                    <button
                                        type="button"
                                        class="payments-btn payments-btn-highlight open-request-btn"
                                        data-passenger="{{ $payment->user?->name ?: '-' }}"
                                        data-trip="#{{ $payment->trip_id }}"
                                        data-method="{{ $methodLabel }}"
                                        data-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-approve-action="{{ route('payments.confirm-paid', $payment) }}"
                                        data-reject-action="{{ route('payments.reject-paid', $payment) }}"
                                    >
                                        View Request
                                    </button>
                                @endif

                                @if($payment->payment_status === 'unpaid')
                                    <form method="POST" action="{{ route('payments.send-reminder', $payment) }}" class="payments-action-row">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="payments-btn {{ $canSendReminder ? '' : 'is-disabled' }} reminder-btn"
                                            {{ $canSendReminder ? '' : 'disabled' }}
                                            data-payment-id="{{ $payment->id }}"
                                            data-seconds-left="{{ $secondsLeft }}"
                                        >
                                            {!! $canSendReminder
                                                ? '<i class="fa-regular fa-bell btn-icon"></i>Peringatan'
                                                : '<i class="fa-regular fa-clock btn-icon"></i>' . gmdate('H:i:s', $secondsLeft) !!}
                                        </button>
                                    </form>
                                @endif

                                @if($payment->payment_status === 'pending_confirmation')
                                    <form method="POST" action="{{ route('payments.confirm-paid', $payment) }}" class="payments-action-row">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="payments-btn payments-btn-success">Approve</button>
                                    </form>
                                    <button
                                        type="button"
                                        class="payments-btn payments-btn-danger open-reject-btn"
                                        data-action="{{ route('payments.reject-paid', $payment) }}"
                                        data-passenger="{{ $payment->user?->name ?: '-' }}"
                                        data-trip="#{{ $payment->trip_id }}"
                                    >
                                        Reject
                                    </button>
                                @elseif($payment->payment_status === 'unpaid')
                                    <form method="POST" action="{{ route('payments.confirm-paid', $payment) }}" class="payments-action-row">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="payments-btn payments-btn-primary">Tandai Paid</button>
                                    </form>
                                @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="payment-mobile-item" style="text-align:center; padding:32px 16px; color:#64748b; font-size:13px;">No records in the queue.</div>
                    @endforelse
                </div>
                <div class="payments-table-wrap">
                    <table class="payments-table">
                        <thead>
                        <tr>
                            <th>Trip</th>
                            <th>Passenger</th>
                            <th class="right">Amount</th>
                            <th>Marked At</th>
                            <th>Status</th>
                            <th class="right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse(($driverPayments ?? collect()) as $payment)
                        @php
                            $isReturnTrip = (bool) ($payment->trip?->is_return_trip ?? false);
                            $pickupName = $payment->trip?->pickup_name ?? '-';
                            $pickupLat = $payment->trip?->pickup_latitude ?? '';
                            $pickupLng = $payment->trip?->pickup_longitude ?? '';
                            $destinationName = $payment->trip?->destination_name ?? '-';
                            $destinationLat = $payment->trip?->destination_latitude ?? '';
                            $destinationLng = $payment->trip?->destination_longitude ?? '';
                            $pairedTripId = $isReturnTrip
                                ? ($payment->trip?->parentTrip?->id)
                                : ($payment->trip?->returnTrip?->id);
                            $routeLabel = $payment->trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
                            $statusClass = $payment->payment_status === 'paid'
                                ? 'status-paid'
                                : ($payment->payment_status === 'pending_confirmation' ? 'status-pending' : 'status-unpaid');
                            $statusText = $payment->payment_status === 'pending_confirmation'
                                ? 'Pending Confirmation'
                                : ($payment->payment_status === 'paid' ? 'Paid' : ($payment->payment_status === 'unpaid' ? 'Unpaid' : ucfirst($payment->payment_status)));
                            $methodLabel = match ($payment->payment_method) {
                                'duitnow_qr' => 'DuitNow QR',
                                'bank_account' => 'Bank Account',
                                'digital_wallet' => 'Digital Wallet',
                                'others' => 'Others',
                                default => '-',
                            };
                            $participantsPayload = $payment->trip?->participants?->map(function ($participant) {
                                $participantUser = $participant->user;
                                return [
                                    'name' => $participantUser?->name ?: '-',
                                    'email' => $participantUser?->email ?: '',
                                    'photo_url' => $participantUser?->profile_photo
                                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($participantUser->profile_photo)
                                        : null,
                                    'is_driver' => (bool) $participant->is_driver,
                                ];
                            })->values()->all() ?? [];
                            $fareBreakdown = $paymentFareBreakdown($payment);
                        @endphp
                            <tr
                                class="open-trip-card js-payment-filter-item"
                                data-filter-date="{{ $payment->trip?->trip_datetime?->format('Y-m-d') ?: '' }}"
                                data-filter-visibility="{{ $payment->trip?->visibility ?: '' }}"
                                data-filter-person="{{ $payment->user?->name ?: '' }}"
                            >
                                <td>
                                    <div>#{{ $payment->trip_id }}</div>
                                    <div style="font-size:12px; color:#64748b;">{{ $routeLabel }}</div>
                                    <button
                                        type="button"
                                        class="payments-link open-trip-modal-btn"
                                        data-trip-id="{{ $payment->trip_id }}"
                                        data-route="{{ $routeLabel }}"
                                        data-driver="{{ $payment->trip?->driver?->name ?: '-' }}"
                                        data-driver-email="{{ $payment->trip?->driver?->email ?: '' }}"
                                        data-driver-whatsapp-url="{{ $payment->trip?->driver?->whatsapp_url ?: '' }}"
                                        data-driver-phone="{{ $payment->trip?->driver?->whatsapp_digits ?: '' }}"
                                        data-pickup-name="{{ $pickupName }}"
                                        data-pickup-lat="{{ $pickupLat }}"
                                        data-pickup-lng="{{ $pickupLng }}"
                                        data-destination-name="{{ $destinationName }}"
                                        data-destination-lat="{{ $destinationLat }}"
                                        data-destination-lng="{{ $destinationLng }}"
                                        data-datetime="{{ $payment->trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                        data-mode="{{ ($payment->trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                        data-status="{{ $payment->trip?->status ? ucfirst($payment->trip->status) : '-' }}"
                                        data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                        data-fare-total="RM {{ number_format((float) ($payment->trip?->fare_total ?? 0), 2) }}"
                                        data-fare-per-person="RM {{ number_format((float) ($payment->trip?->fare_per_person ?? 0), 2) }}"
                                        data-base-fare="RM {{ number_format((float) $fareBreakdown['base'], 2) }}"
                                        data-extra-fee="RM {{ number_format((float) $fareBreakdown['extra'], 2) }}"
                                        data-custom-stop="{{ $fareBreakdown['custom_stop'] ?: '' }}"
                                        data-payment-status="{{ $statusText }}"
                                        data-payment-method="{{ $methodLabel }}"
                                        data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-participants='@json($participantsPayload)'
                                        data-passenger-count="{{ count($participantsPayload) }}"
                                    ><i class="fa-regular fa-eye"></i><span>See Details</span></button>
                                </td>
                                <td>{{ $payment->user?->name ?: '-' }}</td>
                                <td class="right">
                                    RM {{ number_format((float) $payment->amount_due, 2) }}
                                    @if($fareBreakdown['has_extra'])
                                        <div style="font-size:11px;color:#64748b;font-weight:700;">Base RM {{ number_format((float) $fareBreakdown['base'], 2) }} + Extra RM {{ number_format((float) $fareBreakdown['extra'], 2) }}</div>
                                    @endif
                                </td>
                                <td>{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}</td>
                                <td><span class="status-chip {{ $statusClass }}">{{ $statusText }}</span></td>
                                <td class="right">
                                    <div class="queue-actions">
                                        <div class="queue-actions-main">
                                        @if($payment->payment_status === 'pending_confirmation')
                                            <button
                                                type="button"
                                                class="payments-btn payments-btn-highlight open-request-btn"
                                                data-passenger="{{ $payment->user?->name ?: '-' }}"
                                                data-trip="#{{ $payment->trip_id }}"
                                                data-method="{{ $methodLabel }}"
                                                data-remarks="{{ $payment->remarks ?: '-' }}"
                                                data-marked="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                                data-approve-action="{{ route('payments.confirm-paid', $payment) }}"
                                                data-reject-action="{{ route('payments.reject-paid', $payment) }}"
                                            >
                                                View Request
                                            </button>
                                        @endif
                                        @if($payment->payment_status === 'unpaid')
                                            <form method="POST" action="{{ route('payments.send-reminder', $payment) }}" class="payments-action-row">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="payments-btn {{ $canSendReminder ? '' : 'is-disabled' }} reminder-btn"
                                                    {{ $canSendReminder ? '' : 'disabled' }}
                                                    data-payment-id="{{ $payment->id }}"
                                                    data-seconds-left="{{ $secondsLeft }}"
                                                >
                                                    {!! $canSendReminder
                                                        ? '<i class="fa-regular fa-bell btn-icon"></i>Notify'
                                                        : '<i class="fa-regular fa-clock btn-icon"></i>' . gmdate('H:i:s', $secondsLeft) !!}
                                                </button>
                                            </form>
                                        @endif
                                        @if($payment->payment_status === 'pending_confirmation')
                                            <form method="POST" action="{{ route('payments.confirm-paid', $payment) }}" class="payments-action-row">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="payments-btn payments-btn-success">Approve</button>
                                            </form>
                                            <button
                                                type="button"
                                                class="payments-btn payments-btn-danger open-reject-btn"
                                                data-action="{{ route('payments.reject-paid', $payment) }}"
                                                data-passenger="{{ $payment->user?->name ?: '-' }}"
                                                data-trip="#{{ $payment->trip_id }}"
                                            >
                                                Reject
                                            </button>
                                        @elseif($payment->payment_status === 'unpaid')
                                            <form method="POST" action="{{ route('payments.confirm-paid', $payment) }}" class="payments-action-row">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="payments-btn payments-btn-primary">Tandai Paid</button>
                                            </form>
                                        @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No payment records found in queue.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($driverPayments)
                    <div style="margin-top:12px;">
                        {{ $driverPayments->appends(request()->query())->links() }}
                    </div>
                @endif
                <div class="payments-filter-empty" data-filter-empty>No payment records match the current filters.</div>
            </section>
        @endif
    </div>

    <div class="request-modal" id="requestModal" aria-hidden="true">
        <div class="request-modal-card">
            <div class="request-modal-head">
                <h3 class="request-modal-title">Payment Request Details</h3>
                <button type="button" class="modal-close-square" id="requestModalCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="request-modal-grid">
                <div class="request-modal-line">
                    <span class="request-modal-label">Passenger</span>
                    <span class="request-modal-value" id="requestModalPassenger">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label">Trip</span>
                    <span class="request-modal-value" id="requestModalTrip">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label">Payment Method</span>
                    <span class="request-modal-value" id="requestModalMethod">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label">Passenger Remarks</span>
                    <span class="request-modal-value" id="requestModalRemarks">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label">Marked At</span>
                    <span class="request-modal-value" id="requestModalMarked">-</span>
                </div>
            </div>
            <div class="request-modal-actions">
                <div class="request-modal-primary-actions">
                    <form id="requestModalApproveForm" method="POST" class="payments-action-row">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="payments-btn payments-btn-success">Approve</button>
                    </form>
                    <button type="button" class="payments-btn payments-btn-danger" id="requestModalReject">Reject</button>
                </div>
                <button type="button" class="payments-btn" id="requestModalClose">Close</button>
            </div>
        </div>
    </div>

    <div class="trip-payment-review-modal" id="paymentPayNowModal" aria-hidden="true">
        <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="paymentPayNowTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="paymentPayNowTitle">Pay now</h3>
                    <p class="trip-payment-review-sub" id="paymentPayNowSub">Mark your trip payment as paid.</p>
                </div>
                <button type="button" class="trip-payment-review-close" id="paymentPayNowClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list" id="paymentPayNowList"></div>
        </div>
    </div>

    <div class="trip-payment-review-modal" id="paymentReceiptModal" aria-hidden="true">
        <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="paymentReceiptTitle">
            <div class="trip-payment-review-head">
                <div>
                    <h3 class="trip-payment-review-title" id="paymentReceiptTitle">Payment receipt</h3>
                    <p class="trip-payment-review-sub">View your confirmed payment record.</p>
                </div>
                <button type="button" class="trip-payment-review-close" id="paymentReceiptClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="trip-payment-review-list">
                <article class="trip-receipt-card">
                    <div class="trip-receipt-head">
                        <span>
                            <h4 class="trip-receipt-title">CarpoolHub Receipt</h4>
                            <span class="trip-receipt-id" id="paymentReceiptNo">PAY-000000</span>
                        </span>
                        <span class="trip-receipt-status paid">Paid</span>
                    </div>
                    <div class="trip-receipt-total">
                        <span>Amount paid</span>
                        <strong id="paymentReceiptAmount">RM 0.00</strong>
                    </div>
                    <div class="trip-receipt-lines">
                        <div class="trip-receipt-line"><span>Route</span><strong id="paymentReceiptRoute">-</strong></div>
                        <div class="trip-receipt-line"><span>Passenger</span><strong id="paymentReceiptPassenger">-</strong></div>
                        <div class="trip-receipt-line"><span>Driver</span><strong id="paymentReceiptDriver">-</strong></div>
                        <div class="trip-receipt-line"><span>Method</span><strong id="paymentReceiptMethod">-</strong></div>
                        <div class="trip-receipt-line"><span>Marked paid</span><strong id="paymentReceiptMarked">-</strong></div>
                        <div class="trip-receipt-line"><span>Confirmed</span><strong id="paymentReceiptConfirmed">-</strong></div>
                        <div class="trip-receipt-line" id="paymentReceiptBreakdownRow" style="display:none;"><span>Breakdown</span><strong id="paymentReceiptBreakdown">-</strong></div>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <div class="request-modal" id="tripDetailsModal" aria-hidden="true">
        <div class="request-modal-card trip-details-card">
            <div class="request-modal-head">
                <h3 class="request-modal-title">Trip Details</h3>
                <button type="button" class="modal-close-square" id="tripDetailsCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="trip-details-scroll">
            <div class="request-modal-grid">
                <div class="trip-details-pairs">
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Trip</span>
                        <span class="request-modal-value" id="tripDetailsId">-</span>
                    </div>
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-regular fa-calendar"></i>Date & Time</span>
                        <span class="request-modal-value" id="tripDetailsDatetime">-</span>
                    </div>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-road"></i>Route</span>
                    <span class="request-modal-value" id="tripDetailsRoute">-</span>
                </div>
                <div class="trip-point-cards">
                    <div class="trip-point-card pickup">
                        <span class="trip-point-label"><i class="fa-solid fa-location-dot"></i>Pickup Point</span>
                        <span class="trip-point-value" id="tripDetailsPickupPoint">-</span>
                    </div>
                    <div class="trip-point-card destination">
                        <span class="trip-point-label"><i class="fa-solid fa-flag-checkered"></i>Destination Point</span>
                        <span class="trip-point-value" id="tripDetailsDestinationPoint">-</span>
                    </div>
                </div>
                <div class="trip-mini-map-card">
                    <div class="trip-mini-map-head">
                        <p class="trip-mini-map-title"><i class="fa-regular fa-map"></i>Route Preview</p>
                        <span class="trip-mini-map-hint">View only</span>
                    </div>
                    <div class="trip-mini-map" id="tripDetailsMiniMap"></div>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Driver</span>
                    <div class="trip-driver-content">
                        <span class="trip-driver-avatar" id="tripDetailsDriverAvatar">D</span>
                        <span class="trip-driver-meta">
                            <span class="trip-driver-name" id="tripDetailsDriver">-</span>
                            <span class="trip-driver-email" id="tripDetailsDriverEmail">-</span>
                        </span>
                    </div>
                </div>
                <div class="request-modal-line">
                    <div class="trip-passenger-header">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-users"></i>Passengers</span>
                        <span class="trip-passenger-count" id="tripDetailsPassengerCount">0 passengers</span>
                    </div>
                    <div class="trip-passenger-list" id="tripDetailsPassengerList"></div>
                </div>
                <div class="trip-details-pairs">
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-route"></i>Trip Type</span>
                        <span class="request-modal-value" id="tripDetailsMode">-</span>
                        <span class="trip-inline-hint" id="tripDetailsPairHint" style="display:none;"></span>
                    </div>
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-regular fa-circle-check"></i>Status</span>
                        <span class="request-modal-value trip-accent-value" id="tripDetailsStatus">-</span>
                    </div>
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-user-group"></i>Total Passengers</span>
                        <span class="request-modal-value" id="tripDetailsTotalPassengers">-</span>
                    </div>
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-scale-balanced"></i>Fare Split Type</span>
                        <span class="request-modal-value" id="tripDetailsSplitType">-</span>
                    </div>
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-regular fa-credit-card"></i>Payment Status</span>
                        <span class="request-modal-value trip-accent-value" id="tripDetailsPaymentStatus">-</span>
                    </div>
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-wallet"></i>Payment Method</span>
                        <span class="request-modal-value" id="tripDetailsPaymentMethod">-</span>
                    </div>
                    <div class="request-modal-line trip-amount-due-card">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-money-bill-wave"></i>Amount Due</span>
                        <span class="request-modal-value" id="tripDetailsAmountDue">-</span>
                        <span class="trip-amount-due-hint" id="tripDetailsFareBreakdown">Base split + custom extra, if any.</span>
                    </div>
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-route"></i>Custom Stop Extra</span>
                        <span class="request-modal-value" id="tripDetailsExtraFee">-</span>
                        <span class="trip-amount-due-hint" id="tripDetailsCustomStop">Only charged to the passenger using the custom stop.</span>
                    </div>
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-sack-dollar"></i>Total Trip Fare</span>
                        <span class="request-modal-value" id="tripDetailsFareTotal">-</span>
                        <span class="trip-amount-due-hint" style="color:#64748b;">This is the full fare for the whole trip.</span>
                    </div>
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-regular fa-clock"></i>Marked At</span>
                        <span class="request-modal-value" id="tripDetailsMarkedAt">-</span>
                    </div>
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-regular fa-note-sticky"></i>Payment Remarks</span>
                        <span class="request-modal-value" id="tripDetailsPaymentRemarks">-</span>
                    </div>
                </div>
            </div>
            </div>
            <div class="trip-contact-bar">
                <p class="trip-contact-text">Having issues with this trip? Please contact the driver.</p>
                <div class="trip-contact-actions">
                    <a href="#" target="_blank" rel="noopener" class="trip-contact-link whatsapp is-disabled" id="tripDetailsWhatsapp">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>WhatsApp</span>
                    </a>
                    <a href="#" class="trip-contact-link email is-disabled" id="tripDetailsEmail">
                        <i class="fa-regular fa-envelope"></i>
                        <span>Email Driver</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="request-modal" id="rejectModal" aria-hidden="true">
        <div class="request-modal-card">
            <div class="request-modal-head">
                <h3 class="request-modal-title">Reject Payment</h3>
                <button type="button" class="modal-close-square" id="rejectModalCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="request-modal-grid">
                <div class="request-modal-line">
                    <span class="request-modal-label">Passenger</span>
                    <span class="request-modal-value" id="rejectModalPassenger">-</span>
                </div>
                <div class="request-modal-line">
                    <span class="request-modal-label">Trip</span>
                    <span class="request-modal-value" id="rejectModalTrip">-</span>
                </div>
                <form id="rejectModalForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <textarea
                        class="reject-reason-input"
                        id="rejectModalReason"
                        name="rejection_reason"
                        placeholder="Write the rejection reason..."
                        required
                    ></textarea>
                </form>
            </div>
            <div class="reject-modal-actions">
                <button type="button" class="payments-btn" id="rejectModalCancel">Cancel</button>
                <button type="submit" class="payments-btn payments-btn-danger" form="rejectModalForm">Reject</button>
            </div>
        </div>
    </div>

    <div class="request-modal" id="driverPaymentDetailsModal" aria-hidden="true">
        <div class="request-modal-card trip-details-card driver-payment-details-card">
            <div class="request-modal-head">
                <h3 class="request-modal-title">Driver Payment Details</h3>
                <button type="button" class="modal-close-square" id="driverPaymentDetailsCloseTop" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="driver-payment-scroll">
                <div class="request-modal-grid">
                    <div class="request-modal-line">
                        <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-id-badge"></i>Driver Profile</span>
                        <div class="driver-payment-head">
                            <span class="driver-payment-avatar" id="driverPaymentAvatar">D</span>
                            <span class="driver-payment-meta">
                                <span class="driver-payment-name" id="driverPaymentName">-</span>
                                <span class="driver-payment-email" id="driverPaymentEmail">-</span>
                            </span>
                        </div>
                    </div>
                    <div class="trip-details-pairs">
                        <div class="request-modal-line">
                            <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-building-columns"></i>Bank / Wallet</span>
                            <span class="request-modal-value" id="driverPaymentBank">-</span>
                        </div>
                        <div class="request-modal-line">
                            <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Account Holder Name</span>
                            <span class="request-modal-value" id="driverPaymentAccountName">-</span>
                        </div>
                        <div class="request-modal-line">
                            <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Account Number</span>
                            <span class="request-modal-value" id="driverPaymentAccountNumber">-</span>
                        </div>
                    </div>
                    <div class="driver-payment-qr-grid">
                        <div class="driver-payment-qr-card">
                            <span class="driver-payment-qr-title"><i class="fa-solid fa-qrcode"></i> DuitNow QR</span>
                            <div class="driver-payment-qr-preview" id="driverPaymentDuitnowWrap">
                                <span class="driver-payment-qr-empty">No QR uploaded</span>
                            </div>
                        </div>
                        <div class="driver-payment-qr-card">
                            <span class="driver-payment-qr-title"><i class="fa-solid fa-qrcode"></i> Touch 'n Go QR</span>
                            <div class="driver-payment-qr-preview" id="driverPaymentTngWrap">
                                <span class="driver-payment-qr-empty">No QR uploaded</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="request-modal-actions" style="justify-content:flex-end;">
                <button type="button" class="payments-btn" id="driverPaymentDetailsClose">Close</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const params = new URLSearchParams(window.location.search);
            const multiIds = String(params.get('trip_ids') || '')
                .split(',')
                .map((id) => id.trim())
                .filter(Boolean);
            const focusIds = [...new Set(
                multiIds.length > 0
                    ? multiIds
                    : [String(params.get('trip_id') || '').trim()].filter(Boolean)
            )];

            if (focusIds.length > 0) {
                const targets = [];
                const isVisibleTarget = (el) => {
                    if (!el) return false;
                    if (el.offsetParent !== null) return true;
                    const rect = el.getBoundingClientRect();
                    return rect.width > 0 && rect.height > 0;
                };
                focusIds.forEach((tripId) => {
                    document
                        .querySelectorAll(`.open-trip-modal-btn[data-trip-id="${tripId}"]`)
                        .forEach((btn) => {
                            const target = btn.closest('.open-trip-card') || btn.closest('tr') || btn;
                            if (target && !targets.includes(target)) {
                                targets.push(target);
                            }
                        });
                });

                if (targets.length > 0) {
                    window.setTimeout(() => {
                        const myPaymentTargets = targets.filter((target) => {
                            return target.closest && target.closest('#my-payments-list');
                        });
                        const scopedTargets = myPaymentTargets.length > 0 ? myPaymentTargets : targets;
                        const preferredTargets = scopedTargets.filter((target) => isVisibleTarget(target));
                        const activeTargets = preferredTargets.length > 0 ? preferredTargets : scopedTargets;
                        const stickyHeader = document.querySelector('.mobile-header, .desktop-topbar');
                        const bottomNav = document.querySelector('.mobile-bottom-nav');
                        const headerHeight = stickyHeader ? stickyHeader.getBoundingClientRect().height : 0;
                        const bottomNavHeight = bottomNav && window.getComputedStyle(bottomNav).display !== 'none'
                            ? bottomNav.getBoundingClientRect().height
                            : 0;
                        const topGap = 18;
                        const bottomGap = 18;
                        const targetBounds = activeTargets.reduce((bounds, target) => {
                            const rect = target.getBoundingClientRect();
                            const top = rect.top + window.scrollY;
                            const bottom = rect.bottom + window.scrollY;
                            return {
                                top: Math.min(bounds.top, top),
                                bottom: Math.max(bounds.bottom, bottom)
                            };
                        }, { top: Number.POSITIVE_INFINITY, bottom: 0 });
                        const availableHeight = Math.max(
                            window.innerHeight - headerHeight - bottomNavHeight - topGap - bottomGap,
                            160
                        );
                        const rangeHeight = Math.max(targetBounds.bottom - targetBounds.top, 0);
                        const scrollTop = rangeHeight <= availableHeight
                            ? Math.max(
                                targetBounds.top - headerHeight - topGap - ((availableHeight - rangeHeight) / 2),
                                0
                            )
                            : Math.max(targetBounds.top - headerHeight - topGap, 0);
                        window.scrollTo({ top: scrollTop, behavior: 'smooth' });
                        window.setTimeout(() => {
                            const viewportTop = headerHeight + topGap;
                            const viewportBottom = window.innerHeight - bottomNavHeight - bottomGap;
                            const hiddenTarget = activeTargets.find((target) => {
                                const rect = target.getBoundingClientRect();
                                return rect.top < viewportTop || rect.bottom > viewportBottom;
                            });

                            if (!hiddenTarget) return;

                            const anchorTarget = focusIds.length > 1
                                ? activeTargets[activeTargets.length - 1]
                                : hiddenTarget;
                            const anchorTop = anchorTarget.getBoundingClientRect().top + window.scrollY;
                            window.scrollTo({
                                top: Math.max(anchorTop - headerHeight - topGap, 0),
                                behavior: 'smooth'
                            });
                        }, 520);
                        activeTargets.forEach((target) => target.classList.add('payment-focus-highlight'));
                        window.setTimeout(() => {
                            activeTargets.forEach((target) => target.classList.remove('payment-focus-highlight'));
                        }, 2400);
                    }, 140);
                }
            }
        })();

        (() => {
            const modal = document.getElementById('paymentPayNowModal');
            const list = document.getElementById('paymentPayNowList');
            const sub = document.getElementById('paymentPayNowSub');
            const closeBtn = document.getElementById('paymentPayNowClose');
            if (!modal || !list || !closeBtn) return;

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const csrf = @json(csrf_token());
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
            };
            const resultHtml = (message, isError = false) => `
                <div class="trip-payment-popup-result ${isError ? 'error' : ''}">
                    <span class="trip-payment-popup-icon"><i class="fa-solid ${isError ? 'fa-xmark' : 'fa-check'}"></i></span>
                    <span class="trip-payment-popup-title">${isError ? 'Action failed' : 'Successful'}</span>
                    <span class="trip-payment-popup-message">${escapeHtml(message)}</span>
                </div>
            `;
            const qrPreviewHtml = (url, label) => {
                const safeUrl = String(url || '').trim();
                return safeUrl
                    ? `<img src="${escapeHtml(safeUrl)}" alt="${escapeHtml(label)}">`
                    : '<span class="driver-payment-qr-empty">No QR uploaded</span>';
            };

            document.addEventListener('click', (event) => {
                const button = event.target instanceof Element
                    ? event.target.closest('.open-payment-paynow-btn')
                    : null;
                if (!(button instanceof HTMLElement)) return;

                event.preventDefault();
                event.stopPropagation();
                const fareBreakdown = button.dataset.hasExtra === '1'
                    ? `<span style="display:block;color:#64748b;font-size:12px;">Base RM ${escapeHtml(button.dataset.baseAmount || '0.00')} + extra RM ${escapeHtml(button.dataset.extraFee || '0.00')}</span>`
                    : '';
                const driverName = button.dataset.driverName || '-';
                const driverEmail = button.dataset.driverEmail || '-';
                const driverPhoto = String(button.dataset.driverPhoto || '').trim();
                const driverAvatar = driverPhoto
                    ? `<img src="${escapeHtml(driverPhoto)}" alt="${escapeHtml(driverName)}">`
                    : escapeHtml((driverName.trim().charAt(0) || 'D').toUpperCase());
                if (sub) sub.textContent = button.dataset.route || 'Mark your trip payment as paid.';
                list.innerHTML = `
                    <article class="trip-payment-review-item">
                        <div class="trip-payment-review-top">
                            <div class="trip-payment-review-person">
                                <span class="trip-payment-review-avatar">${escapeHtml(button.dataset.initials || 'P')}</span>
                                <span>
                                    <span class="trip-payment-review-name">${escapeHtml(button.dataset.passenger || 'Passenger')}</span>
                                    <span class="trip-payment-review-route">${escapeHtml(button.dataset.trip || 'Trip')} &middot; DuitNow</span>
                                </span>
                            </div>
                            <span class="trip-payment-review-status">Unpaid</span>
                        </div>
                        <div class="trip-payment-review-amount">
                            <span>
                                <span>Amount due</span>
                                <strong>RM ${escapeHtml(button.dataset.amount || '0.00')}</strong>
                                ${fareBreakdown}
                            </span>
                        </div>
                        <div class="payment-paynow-driver">
                            <div class="driver-payment-head">
                                <span class="driver-payment-avatar">${driverAvatar}</span>
                                <span class="driver-payment-meta">
                                    <span class="driver-payment-name">${escapeHtml(driverName)}</span>
                                    <span class="driver-payment-email">${escapeHtml(driverEmail)}</span>
                                </span>
                            </div>
                            <div class="trip-details-pairs">
                                <div class="request-modal-line">
                                    <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-building-columns"></i>Bank / Wallet</span>
                                    <span class="request-modal-value">${escapeHtml(button.dataset.driverBank || '-')}</span>
                                </div>
                                <div class="request-modal-line">
                                    <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Account Holder</span>
                                    <span class="request-modal-value">${escapeHtml(button.dataset.driverAccountName || '-')}</span>
                                </div>
                                <div class="request-modal-line">
                                    <span class="request-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Account Number</span>
                                    <span class="request-modal-value">${escapeHtml(button.dataset.driverAccountNumber || '-')}</span>
                                </div>
                            </div>
                            <div class="driver-payment-qr-grid">
                                <div class="driver-payment-qr-card">
                                    <span class="driver-payment-qr-title"><i class="fa-solid fa-qrcode"></i>DuitNow QR</span>
                                    <div class="driver-payment-qr-preview">${qrPreviewHtml(button.dataset.driverDuitnowQr, 'DuitNow QR')}</div>
                                </div>
                                <div class="driver-payment-qr-card">
                                    <span class="driver-payment-qr-title"><i class="fa-solid fa-qrcode"></i>Touch 'n Go QR</span>
                                    <div class="driver-payment-qr-preview">${qrPreviewHtml(button.dataset.driverTngQr, "Touch 'n Go QR")}</div>
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="${escapeHtml(button.dataset.action || '#')}" class="trip-paynow-form">
                            <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                            <input type="hidden" name="_method" value="PATCH">
                            <div class="trip-paynow-fields">
                                <select class="trip-paynow-input" name="payment_method" required>
                                    <option value="" disabled selected>Select method</option>
                                    <option value="duitnow_qr">DuitNow QR</option>
                                    <option value="bank_account">Bank Account</option>
                                    <option value="digital_wallet">Digital Wallet</option>
                                    <option value="others">Others</option>
                                </select>
                                <input class="trip-paynow-input" type="text" name="remarks" placeholder="Remarks">
                            </div>
                            <button type="submit" class="trip-paynow-submit">Mark as paid</button>
                        </form>
                    </article>
                `;
                document.querySelectorAll('.request-modal.show, .trip-payment-review-modal.is-open').forEach((openModal) => {
                    if (openModal !== modal) {
                        openModal.classList.remove('show', 'is-open');
                        openModal.setAttribute('aria-hidden', 'true');
                    }
                });
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
            }, true);

            list.addEventListener('submit', (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || !form.classList.contains('trip-paynow-form')) return;
                event.preventDefault();

                const card = form.closest('.trip-payment-review-item') || list;
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing';
                }

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(async (response) => {
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(payload.message || 'The payment action could not be completed.');
                        }
                        card.innerHTML = resultHtml(payload.message || 'Payment updated.');
                        window.setTimeout(() => window.location.reload(), 900);
                    })
                    .catch((error) => {
                        card.innerHTML = resultHtml(error.message || 'The payment action could not be completed.', true);
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });
            });

            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
        })();

        (() => {
            const modal = document.getElementById('paymentReceiptModal');
            if (!modal) return;
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            const closeBtn = document.getElementById('paymentReceiptClose');
            const breakdownRow = document.getElementById('paymentReceiptBreakdownRow');
            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value || '-';
            };
            const close = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
            };

            document.addEventListener('click', (event) => {
                const button = event.target instanceof Element
                    ? event.target.closest('.open-payment-receipt-btn')
                    : null;
                if (!(button instanceof HTMLElement)) return;

                event.preventDefault();
                event.stopImmediatePropagation();
                event.stopPropagation();
                setText('paymentReceiptNo', button.dataset.receiptNo);
                setText('paymentReceiptAmount', button.dataset.amount);
                setText('paymentReceiptRoute', button.dataset.route);
                setText('paymentReceiptPassenger', button.dataset.passenger);
                setText('paymentReceiptDriver', button.dataset.driver);
                setText('paymentReceiptMethod', button.dataset.method);
                setText('paymentReceiptMarked', button.dataset.markedAt);
                setText('paymentReceiptConfirmed', button.dataset.confirmedAt);
                const hasExtra = button.dataset.hasExtra === '1';
                setText('paymentReceiptBreakdown', hasExtra ? `${button.dataset.baseFare || 'RM 0.00'} base + ${button.dataset.extraFee || 'RM 0.00'} extra` : '-');
                if (breakdownRow) breakdownRow.style.display = hasExtra ? 'flex' : 'none';
                document.querySelectorAll('.request-modal.show, .trip-payment-review-modal.is-open').forEach((openModal) => {
                    if (openModal !== modal) {
                        openModal.classList.remove('show', 'is-open');
                        openModal.setAttribute('aria-hidden', 'true');
                    }
                });
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
            }, true);

            closeBtn?.addEventListener('click', close);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) close();
            });
        })();

        (() => {
            const tripDetailsModal = document.getElementById('tripDetailsModal');
            const tripDetailsCloseTop = document.getElementById('tripDetailsCloseTop');
            const tripDetailButtons = document.querySelectorAll('.open-trip-modal-btn');

            if (tripDetailsModal && tripDetailsCloseTop && tripDetailButtons.length > 0) {
                if (tripDetailsModal.parentElement !== document.body) {
                    document.body.appendChild(tripDetailsModal);
                }
                const tripDetailsId = document.getElementById('tripDetailsId');
                const tripDetailsRoute = document.getElementById('tripDetailsRoute');
                const tripDetailsDriver = document.getElementById('tripDetailsDriver');
                const tripDetailsDriverAvatar = document.getElementById('tripDetailsDriverAvatar');
                const tripDetailsDriverEmail = document.getElementById('tripDetailsDriverEmail');
                const tripDetailsPickupPoint = document.getElementById('tripDetailsPickupPoint');
                const tripDetailsDestinationPoint = document.getElementById('tripDetailsDestinationPoint');
                const tripDetailsMiniMap = document.getElementById('tripDetailsMiniMap');
                const tripDetailsPassengerCount = document.getElementById('tripDetailsPassengerCount');
                const tripDetailsPassengerList = document.getElementById('tripDetailsPassengerList');
                const tripDetailsTotalPassengers = document.getElementById('tripDetailsTotalPassengers');
                const tripDetailsSplitType = document.getElementById('tripDetailsSplitType');
                const tripDetailsPairHint = document.getElementById('tripDetailsPairHint');
                const tripDetailsDatetime = document.getElementById('tripDetailsDatetime');
                const tripDetailsMode = document.getElementById('tripDetailsMode');
                const tripDetailsStatus = document.getElementById('tripDetailsStatus');
                const tripDetailsAmountDue = document.getElementById('tripDetailsAmountDue');
                const tripDetailsFareBreakdown = document.getElementById('tripDetailsFareBreakdown');
                const tripDetailsExtraFee = document.getElementById('tripDetailsExtraFee');
                const tripDetailsCustomStop = document.getElementById('tripDetailsCustomStop');
                const tripDetailsFareTotal = document.getElementById('tripDetailsFareTotal');
                const tripDetailsPaymentStatus = document.getElementById('tripDetailsPaymentStatus');
                const tripDetailsPaymentMethod = document.getElementById('tripDetailsPaymentMethod');
                const tripDetailsPaymentRemarks = document.getElementById('tripDetailsPaymentRemarks');
                const tripDetailsMarkedAt = document.getElementById('tripDetailsMarkedAt');
                const tripDetailsWhatsapp = document.getElementById('tripDetailsWhatsapp');
                const tripDetailsEmail = document.getElementById('tripDetailsEmail');
                let tripMiniMap = null;
                let tripMiniRouteLayer = null;
                let tripMiniMarkerLayer = null;
                let tripMiniSeedLine = null;
                const toNum = (v) => {
                    const n = Number.parseFloat(String(v ?? '').trim());
                    return Number.isFinite(n) ? n : null;
                };
                const toSlug = (value) => String(value || '')
                    .trim()
                    .toLowerCase()
                    .replace(/\s+/g, '_')
                    .replace(/[^a-z0-9_]/g, '');
                const setStatusBadge = (el, value) => {
                    if (!el) return;
                    const slug = toSlug(value);
                    el.textContent = value || '-';
                    el.className = `request-modal-value trip-status-badge trip-status-${slug || 'draft'}`;
                };
                const esc = (value) => String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
                const renderPassengerList = (participantsRaw) => {
                    if (!tripDetailsPassengerList || !tripDetailsPassengerCount) return;
                    const participants = Array.isArray(participantsRaw) ? participantsRaw : [];
                    const passengerOnly = participants.filter((item) => !item?.is_driver);
                    const driverIncludedInSplit = participants.some((item) => !!item?.is_driver);

                    tripDetailsPassengerCount.textContent = `${passengerOnly.length} passenger${passengerOnly.length === 1 ? '' : 's'}`;
                    if (tripDetailsTotalPassengers) {
                        tripDetailsTotalPassengers.textContent = String(passengerOnly.length);
                    }
                    if (tripDetailsSplitType) {
                        tripDetailsSplitType.textContent = driverIncludedInSplit
                            ? 'Include Driver in Fare Split'
                            : 'Exclude Driver from Fare Split';
                    }

                    if (passengerOnly.length === 0) {
                        tripDetailsPassengerList.innerHTML = '<div class="trip-passenger-email">No passenger records found for this trip.</div>';
                        return;
                    }

                    tripDetailsPassengerList.innerHTML = passengerOnly.map((item) => {
                        const name = esc(item?.name || '-');
                        const email = esc(item?.email || '');
                        const avatarHtml = item?.photo_url
                            ? `<span class="trip-passenger-avatar"><img src="${esc(item.photo_url)}" alt="${name}"></span>`
                            : `<span class="trip-passenger-avatar">${esc((item?.name || 'U').trim().charAt(0).toUpperCase() || 'U')}</span>`;

                        return `
                            <div class="trip-passenger-item">
                                ${avatarHtml}
                                <div class="trip-passenger-meta">
                                    <span class="trip-passenger-name">${name}</span>
                                    <span class="trip-passenger-email">${email || '-'}</span>
                                </div>
                                <span class="trip-passenger-role">Passenger</span>
                            </div>
                        `;
                    }).join('');
                };
                const ensureMiniMap = () => {
                    if (!tripDetailsMiniMap || typeof window.L === 'undefined') return null;
                    if (tripMiniMap) return tripMiniMap;

                    tripDetailsMiniMap.innerHTML = '';
                    tripMiniMap = window.L.map(tripDetailsMiniMap, {
                        zoomControl: false,
                        attributionControl: false,
                        dragging: false,
                        scrollWheelZoom: false,
                        doubleClickZoom: false,
                        boxZoom: false,
                        keyboard: false,
                        tap: false,
                        touchZoom: false,
                    });

                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                    }).addTo(tripMiniMap);

                    return tripMiniMap;
                };
                const drawRouteFallback = (map, pickupLat, pickupLng, destinationLat, destinationLng) => {
                    const coords = [[pickupLat, pickupLng], [destinationLat, destinationLng]];
                    tripMiniRouteLayer = window.L.polyline(coords, {
                        color: '#1d4ed8',
                        weight: 4,
                        opacity: 0.95,
                        dashArray: '8 6',
                    }).addTo(map);
                    map.fitBounds(tripMiniRouteLayer.getBounds(), { padding: [16, 16] });
                };
                const drawMiniMap = async (pickupLat, pickupLng, destinationLat, destinationLng) => {
                    if (!tripDetailsMiniMap) return;
                    if ([pickupLat, pickupLng, destinationLat, destinationLng].some((v) => v === null)) {
                        if (tripMiniMap) {
                            tripMiniMap.remove();
                            tripMiniMap = null;
                        }
                        tripDetailsMiniMap.innerHTML = '<div class="trip-mini-map-empty">Route preview is unavailable for this trip.</div>';
                        return;
                    }

                    const map = ensureMiniMap();
                    if (!map) {
                        tripDetailsMiniMap.innerHTML = '<div class="trip-mini-map-empty">Route preview is unavailable for this trip.</div>';
                        return;
                    }

                    if (tripMiniRouteLayer) {
                        map.removeLayer(tripMiniRouteLayer);
                        tripMiniRouteLayer = null;
                    }
                    if (tripMiniSeedLine) {
                        map.removeLayer(tripMiniSeedLine);
                        tripMiniSeedLine = null;
                    }
                    if (tripMiniMarkerLayer) {
                        map.removeLayer(tripMiniMarkerLayer);
                        tripMiniMarkerLayer = null;
                    }

                    tripMiniMarkerLayer = window.L.layerGroup([
                        window.L.circleMarker([pickupLat, pickupLng], {
                            radius: 6,
                            color: '#ffffff',
                            weight: 2,
                            fillColor: '#16a34a',
                            fillOpacity: 1,
                        }),
                        window.L.circleMarker([destinationLat, destinationLng], {
                            radius: 6,
                            color: '#ffffff',
                            weight: 2,
                            fillColor: '#2563eb',
                            fillOpacity: 1,
                        }),
                    ]).addTo(map);
                    const markerBounds = window.L.latLngBounds([[pickupLat, pickupLng], [destinationLat, destinationLng]]);
                    map.fitBounds(markerBounds, { padding: [18, 18] });

                    // Always show at least a direct connection first so users can see the path immediately.
                    tripMiniSeedLine = window.L.polyline([[pickupLat, pickupLng], [destinationLat, destinationLng]], {
                        color: '#60a5fa',
                        weight: 3,
                        opacity: 0.9,
                        dashArray: '8 6',
                    }).addTo(map);

                    const routeUrl = 'https://router.project-osrm.org/route/v1/driving/'
                        + `${encodeURIComponent(pickupLng)},${encodeURIComponent(pickupLat)};`
                        + `${encodeURIComponent(destinationLng)},${encodeURIComponent(destinationLat)}`
                        + '?overview=full&geometries=geojson&alternatives=false&steps=false';

                    try {
                        const response = await fetch(routeUrl, { method: 'GET' });
                        if (!response.ok) throw new Error('route fetch failed');
                        const payload = await response.json();
                        const geometry = payload?.routes?.[0]?.geometry?.coordinates ?? [];
                        const latLngs = geometry
                            .map((coord) => [Number(coord[1]), Number(coord[0])])
                            .filter((coord) => Number.isFinite(coord[0]) && Number.isFinite(coord[1]));

                        if (latLngs.length > 1) {
                            if (tripMiniSeedLine) {
                                map.removeLayer(tripMiniSeedLine);
                                tripMiniSeedLine = null;
                            }
                            tripMiniRouteLayer = window.L.polyline(latLngs, {
                                color: '#1d4ed8',
                                weight: 4,
                                opacity: 0.95,
                            }).addTo(map);
                            tripMiniRouteLayer.bringToFront();
                            map.fitBounds(tripMiniRouteLayer.getBounds(), { padding: [16, 16] });
                        } else {
                            drawRouteFallback(map, pickupLat, pickupLng, destinationLat, destinationLng);
                        }
                    } catch (error) {
                        drawRouteFallback(map, pickupLat, pickupLng, destinationLat, destinationLng);
                    }
                };

                const openTripDetails = (source) => {
                        const driverName = source.dataset.driver || '-';
                        const driverEmail = source.dataset.driverEmail || '';
                        const driverWhatsappUrl = (source.dataset.driverWhatsappUrl || '').trim();
                        const driverPhoneRaw = source.dataset.driverPhone || '';
                        const digitsRaw = driverPhoneRaw.replace(/\D+/g, '');
                        let waDigits = digitsRaw.replace(/^00+/, '');
                        if (/^01\d{8,9}$/.test(waDigits)) {
                            waDigits = `60${waDigits.slice(1)}`;
                        }
                        const waUrl = /^https?:\/\/wa\.me\/\d+$/i.test(driverWhatsappUrl)
                            ? driverWhatsappUrl
                            : (waDigits ? `https://wa.me/${waDigits}` : '');
                        const pickupName = source.dataset.pickupName || '-';
                        const destinationName = source.dataset.destinationName || '-';
                        const pickupLat = toNum(source.dataset.pickupLat);
                        const pickupLng = toNum(source.dataset.pickupLng);
                        const destinationLat = toNum(source.dataset.destinationLat);
                        const destinationLng = toNum(source.dataset.destinationLng);
                        let participantsPayload = [];
                        try {
                            participantsPayload = JSON.parse(source.dataset.participants || '[]');
                        } catch (_e) {
                            participantsPayload = [];
                        }

                        if (tripDetailsId) tripDetailsId.textContent = `#${source.dataset.tripId || '-'}`;
                        if (tripDetailsRoute) tripDetailsRoute.textContent = source.dataset.route || '-';
                        if (tripDetailsPickupPoint) tripDetailsPickupPoint.textContent = pickupName;
                        if (tripDetailsDestinationPoint) tripDetailsDestinationPoint.textContent = destinationName;
                        if (tripDetailsDriver) tripDetailsDriver.textContent = driverName;
                        if (tripDetailsDriverAvatar) tripDetailsDriverAvatar.textContent = (driverName.trim().charAt(0) || 'D').toUpperCase();
                        if (tripDetailsDriverEmail) tripDetailsDriverEmail.textContent = driverEmail || '-';
                        if (tripDetailsDatetime) tripDetailsDatetime.textContent = source.dataset.datetime || '-';
                        if (tripDetailsMode) tripDetailsMode.textContent = source.dataset.mode || '-';
                        if (tripDetailsPairHint) {
                            const pairedTripId = String(source.dataset.pairedTripId || '').trim();
                            const isTwoWay = String(source.dataset.mode || '').toLowerCase().includes('two way');
                            if (isTwoWay && pairedTripId) {
                                tripDetailsPairHint.textContent = `Paired trip: Trip #${pairedTripId}`;
                                tripDetailsPairHint.style.display = 'block';
                            } else {
                                tripDetailsPairHint.textContent = '';
                                tripDetailsPairHint.style.display = 'none';
                            }
                        }
                        renderPassengerList(participantsPayload);
                        setStatusBadge(tripDetailsStatus, source.dataset.status || '-');
                        if (tripDetailsAmountDue) tripDetailsAmountDue.textContent = source.dataset.amountDue || '-';
                        if (tripDetailsFareBreakdown) {
                            const extraFee = String(source.dataset.extraFee || 'RM 0.00').trim();
                            tripDetailsFareBreakdown.textContent = extraFee !== 'RM 0.00'
                                ? `${source.dataset.baseFare || 'RM 0.00'} base split + ${extraFee} custom extra`
                                : 'This passenger pays the normal base split.';
                        }
                        if (tripDetailsExtraFee) tripDetailsExtraFee.textContent = source.dataset.extraFee || 'RM 0.00';
                        if (tripDetailsCustomStop) {
                            const customStop = String(source.dataset.customStop || '').trim();
                            tripDetailsCustomStop.textContent = customStop
                                ? `${customStop}. Extra fee applies only to this passenger.`
                                : 'No custom stop extra for this passenger.';
                        }
                        if (tripDetailsFareTotal) tripDetailsFareTotal.textContent = source.dataset.fareTotal || '-';
                        setStatusBadge(tripDetailsPaymentStatus, source.dataset.paymentStatus || '-');
                        if (tripDetailsPaymentMethod) tripDetailsPaymentMethod.textContent = source.dataset.paymentMethod || '-';
                        if (tripDetailsPaymentRemarks) tripDetailsPaymentRemarks.textContent = source.dataset.paymentRemarks || '-';
                        if (tripDetailsMarkedAt) tripDetailsMarkedAt.textContent = source.dataset.markedAt || '-';
                        if (tripDetailsEmail) {
                            if (driverEmail) {
                                tripDetailsEmail.classList.remove('is-disabled');
                                tripDetailsEmail.setAttribute('href', `mailto:${driverEmail}`);
                            } else {
                                tripDetailsEmail.classList.add('is-disabled');
                                tripDetailsEmail.setAttribute('href', '#');
                            }
                        }
                        if (tripDetailsWhatsapp) {
                            if (waUrl) {
                                tripDetailsWhatsapp.classList.remove('is-disabled');
                                tripDetailsWhatsapp.setAttribute('href', waUrl);
                            } else {
                                tripDetailsWhatsapp.classList.add('is-disabled');
                                tripDetailsWhatsapp.setAttribute('href', '#');
                            }
                        }
                        tripDetailsModal.classList.add('show');
                        tripDetailsModal.setAttribute('aria-hidden', 'false');
                        document.body.classList.add('modal-open');
                        setTimeout(() => {
                            drawMiniMap(pickupLat, pickupLng, destinationLat, destinationLng).then(() => {
                                if (tripMiniMap) tripMiniMap.invalidateSize();
                            });
                        }, 40);
                };

                tripDetailButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        openTripDetails(button);
                    });
                });

                const interactiveSelector = 'a, button, input, select, textarea, form, label';
                const openTripCards = document.querySelectorAll('.open-trip-card');
                openTripCards.forEach((card) => {
                    card.addEventListener('click', (event) => {
                        const target = event.target;
                        if (!(target instanceof Element)) return;
                        if (target.closest(interactiveSelector)) return;

                        const detailBtn = card.querySelector('.open-trip-modal-btn');
                        if (detailBtn instanceof HTMLButtonElement) {
                            detailBtn.click();
                        } else if (card instanceof HTMLElement && card.dataset.tripId) {
                            openTripDetails(card);
                        }
                    });
                });

                const closeTripDetailsModal = () => {
                    tripDetailsModal.classList.remove('show');
                    tripDetailsModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                };

                tripDetailsCloseTop.addEventListener('click', closeTripDetailsModal);
                tripDetailsModal.addEventListener('click', (event) => {
                    if (event.target === tripDetailsModal) closeTripDetailsModal();
                });
            }

            const modal = document.getElementById('requestModal');
            const closeBtn = document.getElementById('requestModalClose');
            const closeBtnTop = document.getElementById('requestModalCloseTop');
            if (modal && closeBtn) {
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
                const passengerEl = document.getElementById('requestModalPassenger');
                const tripEl = document.getElementById('requestModalTrip');
                const methodEl = document.getElementById('requestModalMethod');
                const remarksEl = document.getElementById('requestModalRemarks');
                const markedEl = document.getElementById('requestModalMarked');
                const approveForm = document.getElementById('requestModalApproveForm');
                const rejectFromRequestBtn = document.getElementById('requestModalReject');

                const openButtons = document.querySelectorAll('.open-request-btn');
                openButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        passengerEl.textContent = button.dataset.passenger || '-';
                        tripEl.textContent = button.dataset.trip || '-';
                        methodEl.textContent = button.dataset.method || '-';
                        remarksEl.textContent = button.dataset.remarks || '-';
                        markedEl.textContent = button.dataset.marked || '-';
                        if (approveForm) {
                            approveForm.setAttribute('action', button.dataset.approveAction || '');
                        }
                        if (rejectFromRequestBtn) {
                            rejectFromRequestBtn.dataset.action = button.dataset.rejectAction || '';
                            rejectFromRequestBtn.dataset.passenger = button.dataset.passenger || '-';
                            rejectFromRequestBtn.dataset.trip = button.dataset.trip || '-';
                        }
                        modal.classList.add('show');
                        modal.setAttribute('aria-hidden', 'false');
                        document.body.classList.add('modal-open');
                    });
                });

                const closeModal = () => {
                    modal.classList.remove('show');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                    if (approveForm) {
                        approveForm.setAttribute('action', '');
                    }
                };

                closeBtn.addEventListener('click', closeModal);
                if (closeBtnTop) closeBtnTop.addEventListener('click', closeModal);
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal();
                });
            }

            const rejectModal = document.getElementById('rejectModal');
            const rejectCancelBtn = document.getElementById('rejectModalCancel');
            const rejectCloseTopBtn = document.getElementById('rejectModalCloseTop');
            const rejectForm = document.getElementById('rejectModalForm');
            const rejectPassengerEl = document.getElementById('rejectModalPassenger');
            const rejectTripEl = document.getElementById('rejectModalTrip');
            const rejectReasonEl = document.getElementById('rejectModalReason');
            const openRejectButtons = document.querySelectorAll('.open-reject-btn');

            if (rejectModal && rejectCancelBtn && rejectForm) {
                if (rejectModal.parentElement !== document.body) {
                    document.body.appendChild(rejectModal);
                }
                const openRejectModal = (action, passenger, trip) => {
                    rejectForm.setAttribute('action', action || '');
                    if (rejectPassengerEl) rejectPassengerEl.textContent = passenger || '-';
                    if (rejectTripEl) rejectTripEl.textContent = trip || '-';
                    rejectModal.classList.add('show');
                    rejectModal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                    setTimeout(() => {
                        if (!rejectReasonEl) return;
                        try {
                            rejectReasonEl.focus({ preventScroll: true });
                        } catch (_error) {
                            rejectReasonEl.focus();
                        }
                    }, 30);
                };

                const closeRejectModal = () => {
                    rejectModal.classList.remove('show');
                    rejectModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                    rejectForm.setAttribute('action', '');
                    if (rejectReasonEl) rejectReasonEl.value = '';
                };

                openRejectButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        openRejectModal(button.dataset.action, button.dataset.passenger, button.dataset.trip);
                    });
                });

                const rejectFromRequestBtn = document.getElementById('requestModalReject');
                if (rejectFromRequestBtn) {
                    rejectFromRequestBtn.addEventListener('click', () => {
                        if (modal) {
                            modal.classList.remove('show');
                            modal.setAttribute('aria-hidden', 'true');
                            document.body.classList.remove('modal-open');
                        }
                        openRejectModal(
                            rejectFromRequestBtn.dataset.action,
                            rejectFromRequestBtn.dataset.passenger,
                            rejectFromRequestBtn.dataset.trip
                        );
                    });
                }

                rejectCancelBtn.addEventListener('click', closeRejectModal);
                if (rejectCloseTopBtn) rejectCloseTopBtn.addEventListener('click', closeRejectModal);
                rejectModal.addEventListener('click', (event) => {
                    if (event.target === rejectModal) closeRejectModal();
                });
            }

            const driverPaymentDetailsModal = document.getElementById('driverPaymentDetailsModal');
            const driverPaymentDetailsClose = document.getElementById('driverPaymentDetailsClose');
            const driverPaymentDetailsCloseTop = document.getElementById('driverPaymentDetailsCloseTop');
            const driverPaymentButtons = document.querySelectorAll('.open-driver-payment-details-btn');
            if (driverPaymentDetailsModal && driverPaymentButtons.length) {
                if (driverPaymentDetailsModal.parentElement !== document.body) {
                    document.body.appendChild(driverPaymentDetailsModal);
                }
                const driverPaymentAvatar = document.getElementById('driverPaymentAvatar');
                const driverPaymentName = document.getElementById('driverPaymentName');
                const driverPaymentEmail = document.getElementById('driverPaymentEmail');
                const driverPaymentBank = document.getElementById('driverPaymentBank');
                const driverPaymentAccountName = document.getElementById('driverPaymentAccountName');
                const driverPaymentAccountNumber = document.getElementById('driverPaymentAccountNumber');
                const driverPaymentDuitnowWrap = document.getElementById('driverPaymentDuitnowWrap');
                const driverPaymentTngWrap = document.getElementById('driverPaymentTngWrap');

                const renderQr = (wrapEl, qrUrl, label) => {
                    if (!wrapEl) return;
                    const url = String(qrUrl || '').trim();
                    if (!url) {
                        wrapEl.innerHTML = '<span class="driver-payment-qr-empty">No QR uploaded</span>';
                        return;
                    }
                    wrapEl.innerHTML = `<img src="${url}" alt="${label}">`;
                };

                const closeDriverPaymentModal = () => {
                    driverPaymentDetailsModal.classList.remove('show');
                    driverPaymentDetailsModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                };

                driverPaymentButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const driverName = String(button.dataset.driverName || '-').trim() || '-';
                        const driverEmail = String(button.dataset.driverEmail || '-').trim() || '-';
                        const driverPhoto = String(button.dataset.driverPhoto || '').trim();

                        if (driverPaymentName) driverPaymentName.textContent = driverName;
                        if (driverPaymentEmail) driverPaymentEmail.textContent = driverEmail;
                        if (driverPaymentBank) driverPaymentBank.textContent = button.dataset.driverBank || '-';
                        if (driverPaymentAccountName) driverPaymentAccountName.textContent = button.dataset.driverAccountName || '-';
                        if (driverPaymentAccountNumber) driverPaymentAccountNumber.textContent = button.dataset.driverAccountNumber || '-';

                        if (driverPaymentAvatar) {
                            if (driverPhoto) {
                                driverPaymentAvatar.innerHTML = `<img src="${driverPhoto}" alt="${driverName}">`;
                            } else {
                                driverPaymentAvatar.textContent = (driverName.charAt(0) || 'D').toUpperCase();
                            }
                        }

                        renderQr(driverPaymentDuitnowWrap, button.dataset.driverDuitnowQr, 'DuitNow QR');
                        renderQr(driverPaymentTngWrap, button.dataset.driverTngQr, "Touch 'n Go QR");

                        driverPaymentDetailsModal.classList.add('show');
                        driverPaymentDetailsModal.setAttribute('aria-hidden', 'false');
                        document.body.classList.add('modal-open');
                    });
                });

                if (driverPaymentDetailsClose) driverPaymentDetailsClose.addEventListener('click', closeDriverPaymentModal);
                if (driverPaymentDetailsCloseTop) driverPaymentDetailsCloseTop.addEventListener('click', closeDriverPaymentModal);
                driverPaymentDetailsModal.addEventListener('click', (event) => {
                    if (event.target === driverPaymentDetailsModal) closeDriverPaymentModal();
                });
            }

            document.querySelectorAll('.js-payments-filter').forEach((panel) => {
                const scopeSelector = panel.dataset.filterScope || '';
                const scope = scopeSelector ? document.querySelector(scopeSelector) : null;
                if (!scope) return;

                const applyFilter = () => {
                    const fromDate = panel.querySelector('[data-filter-from]')?.value || '';
                    const toDate = panel.querySelector('[data-filter-to]')?.value || '';
                    const visibility = panel.querySelector('[data-filter-visibility]')?.value || '';
                    const person = (panel.querySelector('[data-filter-person]')?.value || '').trim().toLowerCase();
                    const items = Array.from(scope.querySelectorAll('.js-payment-filter-item'));
                    let visibleCount = 0;

                    items.forEach((item) => {
                        const itemDate = item.dataset.filterDate || '';
                        const itemVisibility = item.dataset.filterVisibility || '';
                        const itemPerson = (item.dataset.filterPerson || '').toLowerCase();
                        const statusHidden = item.dataset.statusHidden === '1';
                        const isVisible = (!fromDate || itemDate >= fromDate)
                            && (!toDate || itemDate <= toDate)
                            && (!visibility || itemVisibility === visibility)
                            && (!person || itemPerson.includes(person))
                            && !statusHidden;

                        item.classList.toggle('payments-filter-hidden', !isVisible);
                        if (isVisible) visibleCount += 1;
                    });

                    const emptyState = scope.querySelector('[data-filter-empty]');
                    if (emptyState) {
                        emptyState.classList.toggle('show', items.length > 0 && visibleCount === 0);
                    }

                    const hasActiveFilter = Boolean(fromDate || toDate || visibility || person);
                    panel.classList.toggle('has-active-filter', hasActiveFilter);
                };

                panel.querySelector('[data-filter-toggle]')?.addEventListener('click', () => {
                    const isOpen = panel.classList.toggle('is-open');
                    panel.querySelector('[data-filter-toggle]')?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
                panel.querySelectorAll('input, select').forEach((field) => {
                    field.addEventListener('input', applyFilter);
                    field.addEventListener('change', applyFilter);
                });
                panel.querySelector('[data-filter-reset]')?.addEventListener('click', () => {
                    panel.querySelectorAll('input, select').forEach((field) => {
                        field.value = '';
                    });
                    panel.classList.remove('is-open');
                    panel.querySelector('[data-filter-toggle]')?.setAttribute('aria-expanded', 'false');
                    applyFilter();
                    panel.style.display = 'none';
                });

                applyFilter();
            });

            document.querySelector('[data-payments-filter-launch]')?.addEventListener('click', () => {
                const panel = document.getElementById('paymentsFilterPanel');
                if (!panel) return;
                const isOpen = panel.style.display !== 'none' && panel.style.display !== '';
                panel.style.display = isOpen ? 'none' : 'grid';
                if (isOpen) {
                    return;
                }
                if (!isOpen) {
                    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });

            const paymentsFilterPanel = document.getElementById('paymentsFilterPanel');
            if (paymentsFilterPanel instanceof HTMLFormElement) {
                let paymentFilterTimer = null;
                const submitPaymentFilter = () => {
                    window.clearTimeout(paymentFilterTimer);
                    paymentFilterTimer = window.setTimeout(() => paymentsFilterPanel.requestSubmit(), 250);
                };
                paymentsFilterPanel.querySelectorAll('input, select').forEach((field) => {
                    field.addEventListener('change', submitPaymentFilter);
                });
            }

            const reminderButtons = Array.from(document.querySelectorAll('.reminder-btn[data-seconds-left]'));
            if (reminderButtons.length === 0) return;

            const pad = (value) => String(value).padStart(2, '0');
            const toHms = (seconds) => {
                const h = Math.floor(seconds / 3600);
                const m = Math.floor((seconds % 3600) / 60);
                const s = seconds % 60;
                return `${pad(h)}:${pad(m)}:${pad(s)}`;
            };

            const states = {};
            reminderButtons.forEach((button) => {
                const paymentId = button.dataset.paymentId || `row-${Math.random()}`;
                const seconds = parseInt(button.dataset.secondsLeft || '0', 10);
                const safeSeconds = Number.isNaN(seconds) ? 0 : Math.max(0, seconds);
                states[paymentId] = Math.max(states[paymentId] ?? 0, safeSeconds);
            });

            const renderByState = () => {
                reminderButtons.forEach((button) => {
                    const paymentId = button.dataset.paymentId;
                    const secondsLeft = paymentId && states[paymentId] ? states[paymentId] : 0;

                    if (secondsLeft <= 0) {
                        button.disabled = false;
                        button.classList.remove('is-disabled');
                        button.innerHTML = '<i class="fa-regular fa-bell btn-icon"></i>Notify';
                        button.dataset.secondsLeft = '0';
                        return;
                    }

                    button.disabled = true;
                    button.classList.add('is-disabled');
                    button.dataset.secondsLeft = String(secondsLeft);
                    button.innerHTML = `<i class="fa-regular fa-clock btn-icon"></i>${toHms(secondsLeft)}`;
                });
            };

            renderByState();

            const tick = () => {
                Object.keys(states).forEach((key) => {
                    if (states[key] > 0) {
                        states[key] -= 1;
                    }
                });
                renderByState();
            };

            setInterval(tick, 1000);
        })();

        (() => {
            const endpointBase = @json(route('refresh.payments.summary'));
            const currentParams = new URLSearchParams(window.location.search);
            const endpoint = currentParams.toString() ? `${endpointBase}?${currentParams.toString()}` : endpointBase;
            const money = (value) => `RM ${Number(value || 0).toFixed(2)}`;
            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value;
            };

            let inFlight = false;
            const poll = async () => {
                if (inFlight || document.visibilityState !== 'visible') return;
                inFlight = true;
                try {
                    const response = await fetch(endpoint, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) return;
                    const payload = await response.json();
                    const summary = payload?.summary || {};
                    const toolCounts = payload?.tool_counts || {};
                    const debt = payload?.passenger_debt_summary || null;

                    setText('paymentsToolMyRecords', String(Number(toolCounts.my_records || 0)));
                    setText('paymentsToolQueueRecords', String(Number(toolCounts.queue_records || 0)));
                    setText('paymentsToolUnpaidAmount', money(toolCounts.unpaid_amount || 0));
                    setText('paymentsToolPendingAmount', money(toolCounts.pending_amount || 0));

                    setText('paymentsMyUnpaidAmount', money(summary?.my?.unpaid?.amount || 0));
                    setText('paymentsMyUnpaidCount', `${Number(summary?.my?.unpaid?.count || 0)} records`);
                    setText('paymentsMyPendingAmount', money(summary?.my?.pending_confirmation?.amount || 0));
                    setText('paymentsMyPendingCount', `${Number(summary?.my?.pending_confirmation?.count || 0)} records`);
                    setText('paymentsMyPaidAmount', money(summary?.my?.paid?.amount || 0));
                    setText('paymentsMyPaidCount', `${Number(summary?.my?.paid?.count || 0)} records`);

                    setText('paymentsQueueUnpaidAmount', money(summary?.driver?.unpaid?.amount || 0));
                    setText('paymentsQueueUnpaidCount', `${Number(summary?.driver?.unpaid?.count || 0)} records`);
                    setText('paymentsQueuePendingAmount', money(summary?.driver?.pending_confirmation?.amount || 0));
                    setText('paymentsQueuePendingCount', `${Number(summary?.driver?.pending_confirmation?.count || 0)} records`);
                    setText('paymentsQueuePaidAmount', money(summary?.driver?.paid?.amount || 0));
                    setText('paymentsQueuePaidCount', `${Number(summary?.driver?.paid?.count || 0)} records`);

                    if (debt) {
                        setText('paymentsDebtTotal', money(debt.total_amount || 0));
                        setText(
                            'paymentsDebtMeta',
                            `${Number(debt.passenger_count || 0)} passengers, ${Number(debt.total_records || 0)} active records (unpaid + pending).`
                        );
                    }
                } catch (_error) {
                } finally {
                    inFlight = false;
                }
            };

            window.setInterval(poll, 5000);
        })();

        function pmtTab(btn, tab) {
            document.querySelectorAll('.payments-tab').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            document.querySelectorAll('.js-payment-filter-item[data-pmt-status]').forEach(function(row) {
                var s = row.dataset.pmtStatus;
                var perspective = row.dataset.paymentPerspective || '';
                var show = tab === 'all'
                    || (tab === 'pay' && perspective === 'pay')
                    || (tab === 'collect' && perspective === 'collect')
                    || (tab === 'unpaid' && s === 'unpaid')
                    || (tab === 'review' && s === 'pending_confirmation')
                    || (tab === 'confirmed' && s === 'paid')
                    || (tab === 'disputed' && s === 'disputed');
                row.dataset.statusHidden = show ? '0' : '1';
            });
            document.querySelectorAll('.js-payments-filter').forEach(function(panel) {
                panel.querySelector('input, select')?.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }
    </script>
@endsection
