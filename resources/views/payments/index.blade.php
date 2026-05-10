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
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
            box-shadow: 0 0 0 2px rgba(253, 230, 138, 0.45);
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
    </style>

    @php
        $myRecordCount = isset($myPayments) ? $myPayments->total() : 0;
        $queueCount = (isset($driverPayments) && $driverPayments ? $driverPayments->total() : 0)
            + (isset($archivedDriverPayments) && $archivedDriverPayments ? $archivedDriverPayments->total() : 0);
        $isPassenger = $role === 'passenger';
        $unpaidAmt = (float) ($summary['my']['unpaid']['amount'] ?? $summary['driver']['unpaid']['amount'] ?? 0);
        $pendingAmt = (float) ($summary['my']['pending_confirmation']['amount'] ?? $summary['driver']['pending_confirmation']['amount'] ?? 0);
    @endphp

    <div class="payments-page">
        <section class="payments-card">
            <h1 class="payments-title">Payments</h1>
            <p class="payments-subtitle">{{ $isPassenger ? 'Track and submit your trip payments.' : 'Track and manage trip payment records.' }}</p>
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
            <section class="payments-card">
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

            <section class="payments-card" id="my-payments-list">
                <h2 class="payments-section-title">My Payments</h2>
                <p class="payments-section-subtitle">Mark your payments and track driver confirmation.</p>
                <div class="payments-filter-panel js-payments-filter" data-filter-scope="#my-payments-list">
                    <div class="payments-filter-head">
                        <p class="payments-filter-hint">Filters apply automatically.</p>
                        <button type="button" class="payments-filter-toggle" data-filter-toggle aria-expanded="false">
                            <i class="fa-solid fa-filter"></i><span>Filter</span>
                        </button>
                    </div>
                    <div class="payments-filter-body">
                        <div class="payments-filter-grid">
                            <div class="payments-filter-field">
                                <label for="myPaymentsFromDate">From Date</label>
                                <input id="myPaymentsFromDate" class="payments-filter-input" type="date" data-filter-from>
                            </div>
                            <div class="payments-filter-field">
                                <label for="myPaymentsToDate">To Date</label>
                                <input id="myPaymentsToDate" class="payments-filter-input" type="date" data-filter-to>
                            </div>
                            <div class="payments-filter-field">
                                <label for="myPaymentsVisibility">Visibility</label>
                                <select id="myPaymentsVisibility" class="payments-filter-input" data-filter-visibility>
                                    <option value="">All</option>
                                    <option value="public">Public</option>
                                    <option value="private">Private</option>
                                </select>
                            </div>
                            <div class="payments-filter-field">
                                <label for="myPaymentsPassengerSearch">Search Trip or Driver</label>
                                <input id="myPaymentsPassengerSearch" class="payments-filter-input" type="search" placeholder="Search trip or driver" data-filter-person>
                            </div>
                        </div>
                        <div class="payments-filter-actions">
                            <button type="button" class="payments-filter-reset" data-filter-reset>Reset</button>
                        </div>
                    </div>
                </div>
                <div class="payments-mobile-list">
                    @forelse($myPayments as $payment)
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
                        @endphp
                        <article
                            class="payment-mobile-item open-trip-card js-payment-filter-item"
                            data-filter-date="{{ $payment->trip?->trip_datetime?->format('Y-m-d') ?: '' }}"
                            data-filter-visibility="{{ $payment->trip?->visibility ?: '' }}"
                            data-filter-person="{{ trim(($payment->user?->name ?: auth()->user()->name) . ' ' . ($payment->trip?->driver?->name ?: '')) }}"
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
                            <div class="payment-mobile-amount-card">
                                <span class="payment-mobile-amount-label">Amount Due</span>
                                <strong class="payment-mobile-amount-value">RM {{ number_format((float) $payment->amount_due, 2) }}</strong>
                            </div>
                            <div class="payment-mobile-grid">
                                <div class="payment-mobile-line">
                                    <span>Driver</span>
                                    <strong>{{ $payment->trip?->driver?->name ?: '-' }}</strong>
                                </div>
                            </div>
                            <div class="payments-action-row">
                                @if($payment->payment_status === 'unpaid')
                                    <form method="POST" action="{{ route('payments.mark-paid', $payment) }}" class="payments-action-row">
                                        @csrf
                                        @method('PATCH')
                                        <select class="payments-input" name="payment_method" required>
                                            <option value="" disabled selected>Select method</option>
                                            <option value="duitnow_qr">DuitNow QR</option>
                                            <option value="bank_account">Bank Account</option>
                                            <option value="digital_wallet">Digital Wallet</option>
                                            <option value="others">Others</option>
                                        </select>
                                        <input class="payments-input" type="text" name="remarks" placeholder="Remarks">
                                        <button
                                            type="button"
                                            class="payments-btn payments-btn-soft payments-btn-icon open-driver-payment-details-btn"
                                            title="Driver payment details"
                                            data-driver-name="{{ $payment->trip?->driver?->name ?: '-' }}"
                                            data-driver-email="{{ $payment->trip?->driver?->email ?: '-' }}"
                                            data-driver-photo="{{ $driverPhotoUrl }}"
                                            data-driver-bank="{{ $driverBank }}"
                                            data-driver-account-name="{{ $driverAccountName }}"
                                            data-driver-account-number="{{ $driverAccountNumber }}"
                                            data-driver-duitnow-qr="{{ $driverDuitnowQr }}"
                                            data-driver-tng-qr="{{ $driverTngQr }}"
                                        ><i class="fa-solid fa-circle-info"></i><span>Driver Payment Details</span></button>
                                        <button type="submit" class="payments-btn payments-btn-primary">Tandai Paid</button>
                                    </form>
                                @elseif($payment->payment_status === 'pending_confirmation')
                                    <span style="font-size:12px; color:#854d0e; font-weight:700;">Pending Confirmation</span>
                                @else
                                    <span style="font-size:12px; color:#166534; font-weight:700;">Paid</span>
                                @endif
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
                            <th>Trip</th>
                            <th>Driver</th>
                            <th class="right">Amount Due</th>
                            <th>Status</th>
                            <th class="right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($myPayments as $payment)
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
                            @endphp
                            <tr
                                class="open-trip-card js-payment-filter-item"
                                data-filter-date="{{ $payment->trip?->trip_datetime?->format('Y-m-d') ?: '' }}"
                                data-filter-visibility="{{ $payment->trip?->visibility ?: '' }}"
                                data-filter-person="{{ trim(($payment->user?->name ?: auth()->user()->name) . ' ' . ($payment->trip?->driver?->name ?: '')) }}"
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
                                        data-payment-status="{{ $statusText }}"
                                        data-payment-method="{{ $methodLabel }}"
                                        data-payment-remarks="{{ $payment->remarks ?: '-' }}"
                                        data-marked-at="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                        data-paired-trip-id="{{ $pairedTripId ?? '' }}"
                                        data-participants='@json($participantsPayload)'
                                        data-passenger-count="{{ count($participantsPayload) }}"
                                    ><i class="fa-regular fa-eye"></i><span>See Details</span></button>
                                </td>
                                <td>{{ $payment->trip?->driver?->name ?: '-' }}</td>
                                <td class="right">RM {{ number_format((float) $payment->amount_due, 2) }}</td>
                                <td><span class="status-chip {{ $statusClass }}">{{ $statusText }}</span></td>
                                <td class="right">
                                    @if($payment->payment_status === 'unpaid')
                                        <form method="POST" action="{{ route('payments.mark-paid', $payment) }}" class="payments-action-row">
                                            @csrf
                                            @method('PATCH')
                                            <select class="payments-input" name="payment_method" required>
                                                <option value="" disabled selected>Select method</option>
                                                <option value="duitnow_qr">DuitNow QR</option>
                                                <option value="bank_account">Bank Account</option>
                                                <option value="digital_wallet">Digital Wallet</option>
                                                <option value="others">Others</option>
                                            </select>
                                            <input class="payments-input" type="text" name="remarks" placeholder="Remarks">
                                            <button
                                                type="button"
                                                class="payments-btn payments-btn-soft payments-btn-icon open-driver-payment-details-btn"
                                                title="Driver payment details"
                                                data-driver-name="{{ $payment->trip?->driver?->name ?: '-' }}"
                                                data-driver-email="{{ $payment->trip?->driver?->email ?: '-' }}"
                                                data-driver-photo="{{ $driverPhotoUrl }}"
                                                data-driver-bank="{{ $driverBank }}"
                                                data-driver-account-name="{{ $driverAccountName }}"
                                                data-driver-account-number="{{ $driverAccountNumber }}"
                                                data-driver-duitnow-qr="{{ $driverDuitnowQr }}"
                                                data-driver-tng-qr="{{ $driverTngQr }}"
                                            ><i class="fa-solid fa-circle-info"></i></button>
                                            <button type="submit" class="payments-btn payments-btn-primary">Tandai Paid</button>
                                        </form>
                                    @elseif($payment->payment_status === 'pending_confirmation')
                                        <span style="font-size:12px; color:#854d0e; font-weight:700;">Pending Confirmation</span>
                                    @else
                                        <span style="font-size:12px; color:#166534; font-weight:700;">Paid</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No payment records found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="payments-filter-empty" data-filter-empty>No payment records match the current filters.</div>
                <div style="margin-top:12px;">
                    {{ $myPayments->appends(request()->query())->links() }}
                </div>
                @php
                    $archivedOutstandingCount = (int) (($archivedSummary['my']['unpaid']['count'] ?? 0) + ($archivedSummary['my']['pending_confirmation']['count'] ?? 0));
                    $archivedOutstandingAmount = (float) (($archivedSummary['my']['unpaid']['amount'] ?? 0) + ($archivedSummary['my']['pending_confirmation']['amount'] ?? 0));
                @endphp
                @if($archivedOutstandingCount > 0)
                    <div class="payments-archive-cta">
                        <div class="payments-archive-cta-copy">
                            <span class="payments-archive-cta-label">Archived Outstanding</span>
                            <span class="payments-archive-cta-title">You still have archived payment records to review.</span>
                            <span class="payments-archive-cta-meta">
                                {{ $archivedOutstandingCount }} records
                                @if($archivedOutstandingAmount > 0)
                                    • RM {{ number_format($archivedOutstandingAmount, 2) }}
                                @endif
                            </span>
                        </div>
                        <a href="{{ route('archive.payments.index') }}" class="payments-archive-cta-link">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>See Archived Payments
                        </a>
                    </div>
                @endif
            </section>
        @endif

        @if($canReviewQueue)
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
                                    <strong>RM {{ number_format((float) $payment->amount_due, 2) }}</strong>
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
                                <td class="right">RM {{ number_format((float) $payment->amount_due, 2) }}</td>
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
                <div id="archived-queue" style="margin-top:20px; border-top:1px solid #e2e8f0; padding-top:20px;">
                    <h3 class="payments-section-title" style="margin-bottom:6px;">Archived Queue</h3>
                    <p class="payments-section-subtitle">Archived records that still need driver action.</p>

                    <div class="payments-mobile-list">
                        @forelse(($archivedDriverPayments ?? collect()) as $payment)
                            @php
                                $trip = $payment->archivedTrip;
                                $pickupName = $trip?->pickup_name ?? '-';
                                $pickupLat = $trip?->pickup_latitude ?? '';
                                $pickupLng = $trip?->pickup_longitude ?? '';
                                $destinationName = $trip?->destination_name ?? '-';
                                $destinationLat = $trip?->destination_latitude ?? '';
                                $destinationLng = $trip?->destination_longitude ?? '';
                                $pairedTripId = $trip?->parentTrip?->id ?: ($trip?->returnTrip?->id);
                                $routeLabel = $trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
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
                                $reminderMeta = $archivedReminderState[$payment->id] ?? ['can_send' => true, 'seconds_left' => 0];
                                $canSendReminder = (bool) $reminderMeta['can_send'];
                                $secondsLeft = (int) ($reminderMeta['seconds_left'] ?? 0);
                                $participantsPayload = $trip?->participants?->map(function ($participant) {
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
                            @endphp
                            <article class="payment-mobile-item open-trip-card">
                                <div class="payment-mobile-top">
                                    <div>
                                        <div class="payment-mobile-trip">Archived Trip #{{ $trip?->id ?? '-' }}</div>
                                        <div class="payment-mobile-sub">{{ $routeLabel }}</div>
                                        <button
                                            type="button"
                                            class="payments-link open-trip-modal-btn"
                                            data-trip-id="{{ $trip?->id ?? '-' }}"
                                            data-route="{{ $routeLabel }}"
                                            data-driver="{{ $trip?->driver?->name ?: '-' }}"
                                            data-driver-email="{{ $trip?->driver?->email ?: '' }}"
                                            data-driver-whatsapp-url="{{ $trip?->driver?->whatsapp_url ?: '' }}"
                                            data-driver-phone="{{ $trip?->driver?->whatsapp_digits ?: '' }}"
                                            data-pickup-name="{{ $pickupName }}"
                                            data-pickup-lat="{{ $pickupLat }}"
                                            data-pickup-lng="{{ $pickupLng }}"
                                            data-destination-name="{{ $destinationName }}"
                                            data-destination-lat="{{ $destinationLat }}"
                                            data-destination-lng="{{ $destinationLng }}"
                                            data-datetime="{{ $trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                            data-mode="{{ ($trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                            data-status="Archived"
                                            data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                            data-fare-total="RM {{ number_format((float) ($trip?->fare_total ?? 0), 2) }}"
                                            data-fare-per-person="RM {{ number_format((float) ($trip?->fare_per_person ?? 0), 2) }}"
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
                                        <strong>RM {{ number_format((float) $payment->amount_due, 2) }}</strong>
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
                                                data-trip="#{{ $trip?->id ?? '-' }} (Archived)"
                                                data-method="{{ $methodLabel }}"
                                                data-remarks="{{ $payment->remarks ?: '-' }}"
                                                data-marked="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                                data-approve-action="{{ route('archive.payments.confirm-paid', $payment) }}"
                                                data-reject-action="{{ route('archive.payments.reject-paid', $payment) }}"
                                            >
                                                View Request
                                            </button>
                                        @endif
                                        @if($payment->payment_status === 'unpaid')
                                            <form method="POST" action="{{ route('archive.payments.send-reminder', $payment) }}" class="payments-action-row">
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
                                            <form method="POST" action="{{ route('archive.payments.confirm-paid', $payment) }}" class="payments-action-row">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="payments-btn payments-btn-success">Approve</button>
                                            </form>
                                            <button
                                                type="button"
                                                class="payments-btn payments-btn-danger open-reject-btn"
                                                data-action="{{ route('archive.payments.reject-paid', $payment) }}"
                                                data-passenger="{{ $payment->user?->name ?: '-' }}"
                                                data-trip="#{{ $trip?->id ?? '-' }} (Archived)"
                                            >
                                                Reject
                                            </button>
                                        @elseif($payment->payment_status === 'unpaid')
                                            <form method="POST" action="{{ route('archive.payments.confirm-paid', $payment) }}" class="payments-action-row">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="payments-btn payments-btn-primary">Tandai Paid</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="payment-mobile-item">No archived payment records found in queue.</div>
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
                            @forelse(($archivedDriverPayments ?? collect()) as $payment)
                                @php
                                    $trip = $payment->archivedTrip;
                                    $pickupName = $trip?->pickup_name ?? '-';
                                    $pickupLat = $trip?->pickup_latitude ?? '';
                                    $pickupLng = $trip?->pickup_longitude ?? '';
                                    $destinationName = $trip?->destination_name ?? '-';
                                    $destinationLat = $trip?->destination_latitude ?? '';
                                    $destinationLng = $trip?->destination_longitude ?? '';
                                    $pairedTripId = $trip?->parentTrip?->id ?: ($trip?->returnTrip?->id);
                                    $routeLabel = $trip?->savedRoute?->route_name ?: ($pickupName . ' -> ' . $destinationName);
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
                                    $reminderMeta = $archivedReminderState[$payment->id] ?? ['can_send' => true, 'seconds_left' => 0];
                                    $canSendReminder = (bool) $reminderMeta['can_send'];
                                    $secondsLeft = (int) ($reminderMeta['seconds_left'] ?? 0);
                                    $participantsPayload = $trip?->participants?->map(function ($participant) {
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
                                @endphp
                                <tr class="open-trip-card">
                                    <td>
                                        <div>#{{ $trip?->id ?? '-' }}</div>
                                        <div style="font-size:12px; color:#64748b;">{{ $routeLabel }} (Archived)</div>
                                        <button
                                            type="button"
                                            class="payments-link open-trip-modal-btn"
                                            data-trip-id="{{ $trip?->id ?? '-' }}"
                                            data-route="{{ $routeLabel }}"
                                            data-driver="{{ $trip?->driver?->name ?: '-' }}"
                                            data-driver-email="{{ $trip?->driver?->email ?: '' }}"
                                            data-driver-whatsapp-url="{{ $trip?->driver?->whatsapp_url ?: '' }}"
                                            data-driver-phone="{{ $trip?->driver?->whatsapp_digits ?: '' }}"
                                            data-pickup-name="{{ $pickupName }}"
                                            data-pickup-lat="{{ $pickupLat }}"
                                            data-pickup-lng="{{ $pickupLng }}"
                                            data-destination-name="{{ $destinationName }}"
                                            data-destination-lat="{{ $destinationLat }}"
                                            data-destination-lng="{{ $destinationLng }}"
                                            data-datetime="{{ $trip?->trip_datetime?->format('Y-m-d H:i') ?: '-' }}"
                                            data-mode="{{ ($trip?->trip_mode ?? 'one_way') === 'two_way' ? 'Two Way' : 'One Way' }}"
                                            data-status="Archived"
                                            data-amount-due="RM {{ number_format((float) $payment->amount_due, 2) }}"
                                            data-fare-total="RM {{ number_format((float) ($trip?->fare_total ?? 0), 2) }}"
                                            data-fare-per-person="RM {{ number_format((float) ($trip?->fare_per_person ?? 0), 2) }}"
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
                                    <td class="right">RM {{ number_format((float) $payment->amount_due, 2) }}</td>
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
                                                        data-trip="#{{ $trip?->id ?? '-' }} (Archived)"
                                                        data-method="{{ $methodLabel }}"
                                                        data-remarks="{{ $payment->remarks ?: '-' }}"
                                                        data-marked="{{ $payment->marked_paid_at?->format('Y-m-d H:i') ?: '-' }}"
                                                        data-approve-action="{{ route('archive.payments.confirm-paid', $payment) }}"
                                                        data-reject-action="{{ route('archive.payments.reject-paid', $payment) }}"
                                                    >
                                                        View Request
                                                    </button>
                                                @endif
                                                @if($payment->payment_status === 'unpaid')
                                                    <form method="POST" action="{{ route('archive.payments.send-reminder', $payment) }}" class="payments-action-row">
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
                                                    <form method="POST" action="{{ route('archive.payments.confirm-paid', $payment) }}" class="payments-action-row">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="payments-btn payments-btn-success">Approve</button>
                                                    </form>
                                                    <button
                                                        type="button"
                                                        class="payments-btn payments-btn-danger open-reject-btn"
                                                        data-action="{{ route('archive.payments.reject-paid', $payment) }}"
                                                        data-passenger="{{ $payment->user?->name ?: '-' }}"
                                                        data-trip="#{{ $trip?->id ?? '-' }} (Archived)"
                                                    >
                                                        Reject
                                                    </button>
                                                @elseif($payment->payment_status === 'unpaid')
                                                    <form method="POST" action="{{ route('archive.payments.confirm-paid', $payment) }}" class="payments-action-row">
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
                                <tr><td colspan="6">No archived payment records found in queue.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($archivedDriverPayments)
                        <div style="margin-top:12px;">
                            {{ $archivedDriverPayments->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
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
                        <span class="trip-amount-due-hint">This is the amount you need to pay for this trip.</span>
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

                tripDetailButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const driverName = button.dataset.driver || '-';
                        const driverEmail = button.dataset.driverEmail || '';
                        const driverWhatsappUrl = (button.dataset.driverWhatsappUrl || '').trim();
                        const driverPhoneRaw = button.dataset.driverPhone || '';
                        const digitsRaw = driverPhoneRaw.replace(/\D+/g, '');
                        let waDigits = digitsRaw.replace(/^00+/, '');
                        if (/^01\d{8,9}$/.test(waDigits)) {
                            waDigits = `60${waDigits.slice(1)}`;
                        }
                        const waUrl = /^https?:\/\/wa\.me\/\d+$/i.test(driverWhatsappUrl)
                            ? driverWhatsappUrl
                            : (waDigits ? `https://wa.me/${waDigits}` : '');
                        const pickupName = button.dataset.pickupName || '-';
                        const destinationName = button.dataset.destinationName || '-';
                        const pickupLat = toNum(button.dataset.pickupLat);
                        const pickupLng = toNum(button.dataset.pickupLng);
                        const destinationLat = toNum(button.dataset.destinationLat);
                        const destinationLng = toNum(button.dataset.destinationLng);
                        let participantsPayload = [];
                        try {
                            participantsPayload = JSON.parse(button.dataset.participants || '[]');
                        } catch (_e) {
                            participantsPayload = [];
                        }

                        if (tripDetailsId) tripDetailsId.textContent = `#${button.dataset.tripId || '-'}`;
                        if (tripDetailsRoute) tripDetailsRoute.textContent = button.dataset.route || '-';
                        if (tripDetailsPickupPoint) tripDetailsPickupPoint.textContent = pickupName;
                        if (tripDetailsDestinationPoint) tripDetailsDestinationPoint.textContent = destinationName;
                        if (tripDetailsDriver) tripDetailsDriver.textContent = driverName;
                        if (tripDetailsDriverAvatar) tripDetailsDriverAvatar.textContent = (driverName.trim().charAt(0) || 'D').toUpperCase();
                        if (tripDetailsDriverEmail) tripDetailsDriverEmail.textContent = driverEmail || '-';
                        if (tripDetailsDatetime) tripDetailsDatetime.textContent = button.dataset.datetime || '-';
                        if (tripDetailsMode) tripDetailsMode.textContent = button.dataset.mode || '-';
                        if (tripDetailsPairHint) {
                            const pairedTripId = String(button.dataset.pairedTripId || '').trim();
                            const isTwoWay = String(button.dataset.mode || '').toLowerCase().includes('two way');
                            if (isTwoWay && pairedTripId) {
                                tripDetailsPairHint.textContent = `Paired trip: Trip #${pairedTripId}`;
                                tripDetailsPairHint.style.display = 'block';
                            } else {
                                tripDetailsPairHint.textContent = '';
                                tripDetailsPairHint.style.display = 'none';
                            }
                        }
                        renderPassengerList(participantsPayload);
                        setStatusBadge(tripDetailsStatus, button.dataset.status || '-');
                        if (tripDetailsAmountDue) tripDetailsAmountDue.textContent = button.dataset.amountDue || '-';
                        if (tripDetailsFareTotal) tripDetailsFareTotal.textContent = button.dataset.fareTotal || '-';
                        setStatusBadge(tripDetailsPaymentStatus, button.dataset.paymentStatus || '-');
                        if (tripDetailsPaymentMethod) tripDetailsPaymentMethod.textContent = button.dataset.paymentMethod || '-';
                        if (tripDetailsPaymentRemarks) tripDetailsPaymentRemarks.textContent = button.dataset.paymentRemarks || '-';
                        if (tripDetailsMarkedAt) tripDetailsMarkedAt.textContent = button.dataset.markedAt || '-';
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
                        const isVisible = (!fromDate || itemDate >= fromDate)
                            && (!toDate || itemDate <= toDate)
                            && (!visibility || itemVisibility === visibility)
                            && (!person || itemPerson.includes(person));

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
                });

                applyFilter();
            });

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
    </script>
@endsection

