<style>
/* ── Two-column page grid ────────────────────────────────────────── */
.tf-page-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 18px;
    padding: 20px 28px 28px;
}

@media (min-width: 1024px) {
    .tf-page-grid {
        grid-template-columns: 1.45fr 1fr;
        align-items: start;
    }
}

/* ── Left column ─────────────────────────────────────────────────── */
.tf-left-col {
    display: grid;
    gap: 18px;
}

/* ── Right column ────────────────────────────────────────────────── */
.tf-right-col {
    display: grid;
    gap: 18px;
}

@media (min-width: 1024px) {
    .tf-right-col {
        position: sticky;
        top: 80px;
        align-content: start;
    }
}

/* ── Section cards ───────────────────────────────────────────────── */
.tf-card {
    background: var(--surface);
    border: 1px solid var(--hairline);
    border-radius: var(--r-lg);
    padding: 18px 20px;
    box-shadow: var(--shadow-1);
}

.tf-card-pad {
    padding: 18px 20px;
}

.tf-card-flush {
    padding: 0;
    overflow: hidden;
}

.tf-card.is-route-locked {
    opacity: .48;
    filter: grayscale(.35) blur(.4px);
    pointer-events: none;
    user-select: none;
}

.tf-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--hairline);
}

.tf-section-icon {
    width: 30px;
    height: 30px;
    border-radius: var(--r-sm);
    background: var(--ch-yellow-tint);
    border: 1px solid var(--ch-yellow-line);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    font-size: 12px;
    color: var(--ch-yellow-ink);
}

.tf-section-title {
    margin: 0;
    font-family: var(--font-display), sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: var(--ink);
    line-height: 1.2;
}

/* ── Route option grid ───────────────────────────────────────────── */
.tf-route-grid {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 10px;
}

@media (min-width: 600px) {
    .tf-route-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

.tf-route-option {
    display: block;
    border: 2px solid var(--hairline-strong);
    border-radius: var(--r-md);
    background: var(--surface-2);
    padding: 12px;
    cursor: pointer;
    transition: border-color .12s, background .12s;
    text-align: left;
}

.tf-route-option:has(input:checked) {
    border-color: var(--ch-yellow-deep);
    background: var(--ch-yellow-tint);
}

.tf-route-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
    width: 0;
    height: 0;
}

.tf-route-option-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--ink);
    line-height: 1.3;
    margin-bottom: 4px;
}

.tf-route-option-meta {
    font-size: 11px;
    color: var(--muted);
    line-height: 1.4;
}

/* ── Saved route dropdown picker (kept for fallback/no-route) ────── */
.saved-route-tools {
    display: flex;
    align-items: center;
    gap: 8px;
}

.saved-route-toolbar { margin-left: auto; }

.add-route-btn {
    border-radius: var(--r-sm);
    border: 1px solid var(--hairline-strong);
    background: var(--surface);
    color: var(--ink-2);
    text-decoration: none;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background .12s;
}

.add-route-btn:hover { background: var(--canvas); }

.route-picker { position: relative; }

.route-picker-trigger {
    width: 100%;
    border-radius: var(--r-sm);
    border: 1px solid var(--hairline-strong);
    background: var(--surface);
    color: var(--ink);
    padding: 10px 12px;
    font-size: 14px;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    transition: border-color .15s ease, box-shadow .15s ease;
}

.route-picker-trigger:focus {
    outline: none;
    border-color: var(--ch-yellow-deep);
    box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
}

.route-picker-trigger i {
    font-size: 11px;
    color: var(--muted);
    transition: transform .15s ease;
}

.route-picker.open .route-picker-trigger { border-color: var(--ch-yellow-deep); }
.route-picker.open .route-picker-trigger i { transform: rotate(180deg); }

.route-picker-placeholder { color: var(--muted); }

.route-picker-panel {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 6px);
    z-index: 70;
    border: 1px solid var(--hairline-strong);
    background: var(--surface);
    border-radius: var(--r-md);
    box-shadow: var(--shadow-3);
    padding: 8px;
    display: none;
}

.route-picker.open .route-picker-panel { display: block; }

.route-picker-search {
    width: 100%;
    border-radius: var(--r-sm);
    border: 1px solid var(--hairline-strong);
    background: var(--surface-2);
    color: var(--ink);
    padding: 8px 10px;
    font-size: 13px;
    outline: none;
    margin-bottom: 8px;
}

.route-picker-search:focus {
    border-color: var(--ch-yellow-deep);
    background: var(--surface);
}

.route-picker-list {
    max-height: 220px;
    overflow: auto;
    border: 1px solid var(--hairline);
    border-radius: var(--r-sm);
    background: var(--surface);
}

.route-picker-option {
    width: 100%;
    border: 0;
    border-bottom: 1px solid var(--hairline);
    background: var(--surface);
    color: var(--ink);
    padding: 10px;
    text-align: left;
    cursor: pointer;
    font-size: 13px;
    line-height: 1.35;
}

.route-picker-option:last-child { border-bottom: 0; }
.route-picker-option:hover { background: var(--ch-yellow-tint); }

.route-picker-option.active {
    background: var(--ch-yellow);
    color: var(--ch-yellow-ink);
    font-weight: 700;
}

.route-picker-empty {
    padding: 10px;
    color: var(--muted);
    font-size: 12px;
}

.route-select-native {
    position: absolute;
    left: 0;
    top: 0;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}

/* ── Schedule grid ───────────────────────────────────────────────── */
.tf-sched-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

@media (min-width: 600px) {
    .tf-sched-grid { grid-template-columns: 1fr 1fr 1fr; }
}

.tf-sched-row2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 12px;
}

/* ── Fare grid ───────────────────────────────────────────────────── */
.tf-fare-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

@media (min-width: 600px) {
    .tf-fare-grid { grid-template-columns: 1fr 1fr 1fr; }
}

/* ── Generic form pieces ─────────────────────────────────────────── */
.tf-field {
    display: grid;
    gap: 5px;
}

.tf-input,
.tf-select,
.tf-textarea {
    width: 100%;
    border-radius: var(--r-sm);
    border: 1px solid var(--hairline-strong);
    background: var(--surface);
    color: var(--ink);
    padding: 10px 12px;
    font-size: 14px;
    font-family: var(--font-ui), sans-serif;
    outline: none;
    transition: border-color .15s ease, box-shadow .15s ease;
}

.tf-input:focus,
.tf-select:focus,
.tf-textarea:focus {
    border-color: var(--ch-yellow-deep);
    box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
}

.tf-input.is-error,
.tf-select.is-error,
.tf-textarea.is-error {
    border-color: var(--danger);
    background: var(--danger-soft);
}

.tf-input-mono {
    font-family: var(--font-mono, monospace);
    font-size: 15px;
    font-weight: 700;
}

.tf-input-readonly {
    background: var(--ch-yellow-tint);
    border-color: var(--ch-yellow-line);
    color: var(--ch-yellow-ink);
    cursor: default;
}

/* ── Tab-style toggle buttons ────────────────────────────────────── */
.tf-tab-row {
    display: flex;
    border: 1px solid var(--hairline-strong);
    border-radius: var(--r-sm);
    overflow: hidden;
    background: var(--surface-2);
}

.tf-tab-btn {
    flex: 1;
    border: 0;
    border-right: 1px solid var(--hairline-strong);
    background: transparent;
    color: var(--ink-2);
    font-size: 13px;
    font-weight: 600;
    font-family: var(--font-ui), sans-serif;
    padding: 9px 8px;
    cursor: pointer;
    transition: background .12s, color .12s;
    position: relative;
}

.tf-tab-btn:last-child { border-right: 0; }

.tf-tab-btn:has(input:checked),
.tf-tab-btn.is-active {
    background: var(--ch-yellow);
    color: var(--ch-yellow-ink);
}

.tf-tab-btn input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}

/* ── Choice items ────────────────────────────────────────────────── */
.tf-choice-grid {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 500px) {
    .tf-choice-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

.tf-choice-item {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--hairline-strong);
    border-radius: var(--r-sm);
    background: var(--surface-2);
    padding: 10px 12px;
    font-size: 13px;
    color: var(--ink-2);
    cursor: pointer;
    transition: background .12s, border-color .12s;
}

.tf-choice-item:has(input:checked) {
    background: var(--ch-yellow-tint);
    border-color: var(--ch-yellow-line);
}

.tf-choice-item input[type="radio"] {
    width: 16px;
    height: 16px;
    accent-color: var(--ch-yellow-deep);
}

.tf-choice-text {
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;
}

.tf-choice-hint {
    font-size: 10px;
    color: var(--muted);
    font-weight: 500;
}

/* ── Toggle row ──────────────────────────────────────────────────── */
.tf-toggle-row {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--hairline-strong);
    border-radius: var(--r-sm);
    background: var(--surface-2);
    padding: 10px 12px;
    font-size: 13px;
    color: var(--ink-2);
    cursor: pointer;
    transition: background .12s, border-color .12s;
}

.tf-toggle-row:hover { background: var(--canvas); }

.tf-toggle-row input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--ch-yellow-deep);
    flex: 0 0 auto;
}

/* ── Direction section ───────────────────────────────────────────── */
.direction-section {
    border: 1px solid var(--hairline-strong);
    border-radius: var(--r-md);
    background: var(--surface-2);
    padding: 14px;
    display: grid;
    gap: 12px;
    margin-top: 12px;
    transition: opacity .16s ease, filter .16s ease;
}

.direction-section[hidden] { display: none; }

.direction-section.is-disabled {
    opacity: .65;
    filter: grayscale(.1);
    pointer-events: none;
}

.direction-section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
}

.direction-title {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: var(--ink);
}

.direction-points {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 500px) {
    .direction-points { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

.direction-point-card {
    border: 1px solid var(--hairline);
    border-radius: var(--r-sm);
    background: var(--surface);
    padding: 10px;
    display: grid;
    gap: 3px;
}

.direction-point-card.pickup {
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.direction-point-card.destination {
    border-color: #bfdbfe;
    background: #eff6ff;
}

.direction-point-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.direction-point-card.pickup .direction-point-label { color: #166534; }
.direction-point-card.destination .direction-point-label { color: #1d4ed8; }

.direction-point-value {
    font-size: 13px;
    font-weight: 700;
    color: var(--ink);
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.direction-custom-stops {
    border: 1px dashed var(--hairline-strong);
    border-radius: var(--r-sm);
    background: var(--surface-2);
    padding: 10px;
    display: grid;
    gap: 7px;
}
.direction-custom-stops[hidden] { display: none; }
.direction-custom-title {
    margin: 0;
    color: var(--ink);
    font-size: 12px;
    font-weight: 800;
}
.direction-custom-list {
    display: grid;
    gap: 6px;
}
.direction-custom-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    color: var(--ink-2);
    font-size: 12px;
    line-height: 1.35;
}
.direction-custom-name {
    font-weight: 800;
    color: var(--ink);
}
.direction-custom-extra {
    color: var(--ch-yellow-ink);
    font-weight: 900;
    white-space: nowrap;
}

.direction-swap-btn {
    border-radius: var(--r-sm);
    border: 1px solid var(--hairline-strong);
    background: var(--surface);
    color: var(--ink);
    font-size: 12px;
    font-weight: 700;
    padding: 7px 10px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .12s;
}

.direction-swap-btn:hover { background: var(--canvas); }

.direction-swap-btn:disabled {
    background: var(--canvas);
    color: var(--muted-2);
    border-color: var(--hairline);
    cursor: not-allowed;
}

.direction-return-block {
    border-top: 1px dashed var(--hairline-strong);
    padding-top: 12px;
    display: grid;
    gap: 8px;
}

.direction-return-block[hidden] { display: none; }

/* ── Participants card ───────────────────────────────────────────── */
.tf-participants-card {
    border: 1px solid var(--hairline-strong);
    border-radius: var(--r-md);
    background: var(--surface-2);
    padding: 10px;
    max-height: 240px;
    overflow: auto;
    display: grid;
    gap: 8px;
}

.tf-participant-card {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid var(--hairline);
    border-radius: var(--r-sm);
    background: var(--surface);
    padding: 9px 10px;
    color: var(--ink-2);
    cursor: pointer;
    transition: background .12s, border-color .12s;
}

.tf-participant-card:has(input:checked) {
    background: var(--ch-yellow-tint);
    border-color: var(--ch-yellow-line);
}

.tf-participant-card input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--ch-yellow-deep);
    flex: 0 0 auto;
}

.tf-participant-avatar {
    width: 32px;
    height: 32px;
    border-radius: var(--r-pill);
    border: 1px solid var(--hairline);
    background: var(--canvas);
    color: var(--ink);
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}

.tf-participant-meta {
    min-width: 0;
    display: grid;
    gap: 1px;
}

.tf-participant-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--ink);
    line-height: 1.25;
}

.tf-participant-email {
    font-size: 11px;
    color: var(--muted);
    line-height: 1.2;
    word-break: break-word;
}

.tf-participant-preset-note {
    display: none;
    color: var(--ch-yellow-ink);
    font-size: 11px;
    font-weight: 800;
    line-height: 1.2;
}
.tf-participant-card.has-route-preset .tf-participant-preset-note {
    display: inline;
}

.tf-participants-empty {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
}

.tf-participants-connection-help {
    margin-top: 6px;
    font-size: 12px;
    color: var(--muted);
}

.tf-participants-connection-help a {
    color: var(--info);
    font-weight: 700;
    text-decoration: none;
}

.tf-participants-connection-help a:hover { text-decoration: underline; }

/* ── Conditional fields ──────────────────────────────────────────── */
.tf-conditional-group { display: grid; gap: 12px; }

/* ── Fare preview (within section) ──────────────────────────────── */
.tf-fare-preview {
    border: 1px solid var(--ch-yellow-line);
    background: var(--ch-yellow-tint);
    border-radius: var(--r-md);
    padding: 14px;
    display: grid;
    gap: 10px;
    margin-top: 12px;
}

.tf-fare-preview-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--warning);
    margin: 0;
}

.tf-fare-preview-grid {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.tf-fare-preview-item {
    background: rgba(255, 255, 255, 0.75);
    border: 1px solid var(--ch-yellow-line);
    border-radius: var(--r-sm);
    padding: 10px 12px;
    display: grid;
    gap: 2px;
}

.tf-fare-preview-label {
    font-size: 11px;
    color: var(--warning);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.tf-fare-preview-value {
    font-size: 18px;
    color: var(--ch-yellow-ink);
    font-weight: 700;
    line-height: 1.15;
    font-family: var(--font-display), sans-serif;
}

/* ── Right-panel summary ─────────────────────────────────────────── */
.tf-summary-kv {
    display: grid;
    gap: 8px;
    margin-top: 10px;
}

.tf-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--hairline);
}

.tf-summary-row:last-child { border-bottom: 0; padding-bottom: 0; }

.tf-summary-key { color: var(--muted); font-weight: 600; }
.tf-summary-val { color: var(--ink); font-weight: 700; text-align: right; }

.tf-tip-box {
    background: var(--ch-yellow-tint);
    border: 1px solid var(--ch-yellow-line);
    border-radius: var(--r-md);
    padding: 12px;
    font-size: 12px;
    color: var(--ch-yellow-ink);
    line-height: 1.5;
    margin-top: 14px;
}

/* ── Map preview ─────────────────────────────────────────────────── */
#trip-map {
    height: 220px;
    width: 100%;
    background: var(--surface-2);
}

.tf-map-stops {
    padding: 12px 14px;
    display: grid;
    gap: 6px;
    border-top: 1px solid var(--hairline);
}

.tf-map-stop-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--ink-2);
}

.tf-map-stop-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex: 0 0 auto;
}

.tf-map-stop-dot.pickup { background: #16a34a; }
.tf-map-stop-dot.destination { background: #2563eb; }

/* ── Error / actions ─────────────────────────────────────────────── */
.tf-error-banner {
    margin-top: 4px;
    color: var(--danger-ink);
    border: 1px solid var(--danger);
    background: var(--danger-soft);
    border-radius: var(--r-sm);
    padding: 10px 12px;
    font-size: 13px;
    line-height: 1.4;
}

.tf-field-error {
    font-size: 12px;
    color: var(--danger);
    margin: 0;
    font-weight: 600;
}

.tf-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    padding: 0 28px 28px;
}

.tf-actions .btn-ghost { margin-right: auto; }

.tf-mobile-publish-bar {
    display: none;
}

.tf-mobile-summary-card {
    display: none;
}

.trip-publish-btn.is-incomplete,
.tf-mobile-publish-btn.is-incomplete {
    background: #e5e7eb !important;
    border-color: #d1d5db !important;
    color: #64748b !important;
    box-shadow: none !important;
}

@media (max-width: 1023px) {
    .tf-page-grid {
        padding-bottom: 12px;
    }

    .tf-right-col {
        display: none;
    }

    .trip-publish-btn[data-trip-publish-button],
    .trip-create-cancel-top {
        display: none;
    }

    .tf-mobile-publish-bar {
        position: fixed;
        left: 6px;
        right: 6px;
        bottom: calc(76px + env(safe-area-inset-bottom, 0px));
        z-index: 1885;
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        border: 1px solid var(--hairline);
        border-bottom: 0;
        border-radius: 20px 20px 0 0;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 -8px 22px rgba(11,18,32,0.10);
        padding: 9px 10px 8px;
        backdrop-filter: blur(14px);
    }

    .tf-mobile-publish-bar.is-incomplete {
        display: none;
    }

    .tf-mobile-publish-summary {
        min-width: 0;
        display: grid;
        gap: 4px;
    }

    .tf-mobile-publish-bar:not(.is-incomplete) .tf-mobile-publish-summary {
        display: none;
    }

    .tf-mobile-publish-bar:not(.is-incomplete) {
        display: none;
    }

    .tf-mobile-publish-title {
        color: var(--ink);
        font-size: 13px;
        font-weight: 950;
        line-height: 1.2;
    }

    .tf-mobile-publish-meta {
        color: var(--muted);
        font-size: 10.5px;
        font-weight: 750;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tf-mobile-step-pill {
        width: fit-content;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 999px;
        border: 1px solid var(--ch-yellow-line);
        background: var(--ch-yellow-tint);
        color: var(--ch-yellow-ink);
        padding: 3px 8px;
        font-size: 9.5px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .tf-mobile-publish-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px 18px;
        width: min(100%, 360px);
        margin-inline: auto;
    }

    .tf-mobile-publish-item {
        min-width: 0;
        display: grid;
        gap: 1px;
        justify-items: start;
        text-align: left;
    }

    .tf-mobile-publish-item span {
        color: var(--muted);
        font-size: 9.5px;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .tf-mobile-publish-item strong {
        color: var(--ink);
        font-size: 11.5px;
        font-weight: 900;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tf-mobile-publish-bar.is-incomplete .tf-mobile-publish-grid {
        display: none;
    }

    .tf-mobile-summary-card {
        display: block;
        margin: -6px 28px 108px;
    }

    .tf-mobile-summary-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-top: 14px;
    }

    .tf-mobile-publish-btn {
        min-height: 42px;
        border-radius: 13px;
        padding: 0 14px;
        white-space: nowrap;
        width: 100%;
    }

    .tf-mobile-publish-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .tf-mobile-cancel-btn {
        min-height: 42px;
        border-radius: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-weight: 850;
    }

    .tf-mobile-publish-bar.is-incomplete .tf-mobile-cancel-btn,
    .tf-mobile-publish-bar.is-incomplete .tf-mobile-publish-btn {
        min-height: 40px;
    }

    .tf-mobile-publish-bar.is-incomplete {
        grid-template-columns: minmax(0, 1fr);
    }

    .tf-mobile-publish-bar.is-incomplete .tf-mobile-publish-summary {
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 4px 8px;
    }

    .tf-mobile-publish-bar.is-incomplete .tf-mobile-publish-title {
        grid-column: 2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tf-mobile-publish-bar.is-incomplete .tf-mobile-publish-meta {
        display: none;
    }

    .tf-mobile-publish-bar.is-incomplete .tf-mobile-publish-actions {
        display: none;
    }

    .tf-mobile-publish-bar.is-incomplete .tf-mobile-step-pill {
        grid-row: 1;
        grid-column: 1;
        white-space: nowrap;
    }

    .tf-mobile-publish-bar:not(.is-incomplete) .tf-mobile-step-pill {
        display: none;
    }
}

@media (max-width: 380px) {
    .tf-mobile-summary-card {
        margin-inline: 16px;
        margin-bottom: 112px;
    }

    .tf-mobile-publish-bar {
        padding: 8px;
    }

    .tf-mobile-publish-title {
        font-size: 12px;
    }

    .tf-mobile-publish-actions {
        grid-template-columns: .75fr 1.25fr;
        gap: 6px;
    }

    .tf-mobile-cancel-btn,
    .tf-mobile-publish-btn {
        font-size: 13px;
        padding-inline: 8px;
    }
}

@media (max-width: 1023px) and (min-height: 760px) {
    .tf-mobile-publish-bar {
        bottom: calc(74px + env(safe-area-inset-bottom, 0px));
    }
}

/* ── Cancel modal ────────────────────────────────────────────────── */
.tf-cancel-modal {
    position: fixed;
    inset: 0;
    background: rgba(11, 18, 32, 0.45);
    backdrop-filter: blur(3px);
    z-index: 3000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.tf-cancel-modal.show { display: flex; }

.tf-cancel-card {
    width: min(100%, 400px);
    background: var(--surface);
    border: 1px solid var(--hairline-strong);
    border-radius: var(--r-lg);
    padding: 20px;
    display: grid;
    gap: 12px;
    box-shadow: var(--shadow-3);
}

.tf-cancel-card-title {
    margin: 0;
    font-family: var(--font-display), sans-serif;
    font-size: 18px;
    color: var(--ink);
    font-weight: 700;
}

.tf-cancel-card-text {
    margin: 0;
    font-size: 13px;
    color: var(--ink-3);
    line-height: 1.5;
}

.tf-cancel-card-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

/* ── Mobile: hide right panel ────────────────────────────────────── */
@media (max-width: 1023px) {
    .tf-right-col { order: -1; }
}
</style>

@php
    $currentTripType = old('trip_type', (isset($trip) && (($trip->trip_mode ?? 'one_way') === 'two_way' || $trip->returnTrip)) ? 'two_way' : 'one_way');
    $selectedSavedRouteId = old('saved_route_id', $trip->saved_route_id ?? request('route_id'));
    $outboundPickupKey = old('outbound_pickup_key');
    if ($outboundPickupKey === null && isset($trip) && $trip?->savedRoute) {
        $outboundPickupKey = $trip->pickup_name === $trip->savedRoute->point_b_name ? 'point_b' : 'point_a';
    }
    $outboundPickupKey = $outboundPickupKey ?: 'point_a';

    $outboundDestinationKey = old('outbound_destination_key');
    if ($outboundDestinationKey === null && isset($trip) && $trip?->savedRoute) {
        $outboundDestinationKey = $trip->destination_name === $trip->savedRoute->point_a_name ? 'point_a' : 'point_b';
    }
    $outboundDestinationKey = $outboundDestinationKey ?: ($outboundPickupKey === 'point_a' ? 'point_b' : 'point_a');

    $returnPickupKey = old('return_pickup_key');
    if ($returnPickupKey === null && isset($trip) && $trip?->returnTrip && $trip?->savedRoute) {
        $returnPickupKey = $trip->returnTrip->pickup_name === $trip->savedRoute->point_b_name ? 'point_b' : 'point_a';
    }
    $returnPickupKey = $returnPickupKey ?: ($outboundPickupKey === 'point_a' ? 'point_b' : 'point_a');

    $returnDestinationKey = old('return_destination_key');
    if ($returnDestinationKey === null && isset($trip) && $trip?->returnTrip && $trip?->savedRoute) {
        $returnDestinationKey = $trip->returnTrip->destination_name === $trip->savedRoute->point_a_name ? 'point_a' : 'point_b';
    }
    $returnDestinationKey = $returnDestinationKey ?: ($returnPickupKey === 'point_a' ? 'point_b' : 'point_a');
@endphp

{{-- ── Hidden inputs: direction keys ──────────────────────────────── --}}
<input type="hidden" name="outbound_pickup_key" id="outbound_pickup_key" value="{{ $outboundPickupKey }}">
<input type="hidden" name="outbound_destination_key" id="outbound_destination_key" value="{{ $outboundDestinationKey }}">
<input type="hidden" name="return_pickup_key" id="return_pickup_key" value="{{ $returnPickupKey }}">
<input type="hidden" name="return_destination_key" id="return_destination_key" value="{{ $returnDestinationKey }}">
<input id="status_system_input" type="hidden" name="status" value="">

{{-- ── Validation error banner ──────────────────────────────────────── --}}
@if($errors->any())
    <div class="tf-error-banner" style="margin:0 28px 0;margin-top:16px;">
        <strong>Please fix the following:</strong>
        <ul style="margin:6px 0 0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- ── Two-column page grid ─────────────────────────────────────────── --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="tf-page-grid">

    {{-- ═══════════════════════════════════ --}}
    {{-- LEFT COLUMN ───────────────────────── --}}
    {{-- ═══════════════════════════════════ --}}
    <div class="tf-left-col">

        {{-- ── SECTION 1 · SAVED ROUTE ───────── --}}
        <div class="tf-card">
            <div class="tf-section-header">
                <span class="tf-section-icon"><i class="fa-solid fa-route"></i></span>
                <h2 class="tf-section-title">1 · Saved Route</h2>
                <div style="margin-left:auto">
                    <a href="{{ route('saved-routes.create') }}" class="add-route-btn">
                        <i class="fa-solid fa-plus"></i>
                        <span>New Saved Route&hellip;</span>
                    </a>
                </div>
            </div>

            {{-- Route picker dropdown (hidden select drives logic) --}}
            <div class="route-picker" id="savedRoutePicker">
                <button type="button" class="route-picker-trigger" id="savedRouteTrigger">
                    <span id="savedRouteTriggerText" class="route-picker-placeholder">-- Search or select a route --</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="route-picker-panel" id="savedRoutePanel">
                    <input id="savedRouteSearchInput" class="route-picker-search" type="text" placeholder="Search routes…" autocomplete="off">
                    <div class="route-picker-list" id="savedRouteList"></div>
                </div>
                <select id="saved_route_id" class="tf-select route-select-native" name="saved_route_id" required>
                    <option value="">Select a route</option>
                    @foreach($savedRoutes as $savedRoute)
                        @php
                            $presetStopPayload = $savedRoute->passengerStops
                                ->where('is_active', true)
                                ->map(fn ($stop) => [
                                    'user_id' => (string) $stop->user_id,
                                    'name' => $stop->user?->name ?: 'Passenger',
                                    'pickup_name' => $stop->pickup_name,
                                    'dropoff_name' => $stop->dropoff_name,
                                    'extra_fee_amount' => $stop->extra_fee_amount !== null ? number_format((float) $stop->extra_fee_amount, 2, '.', '') : '0.00',
                                ])
                                ->values();
                        @endphp
                        <option
                            value="{{ $savedRoute->id }}"
                            data-fare="{{ number_format((float) $savedRoute->default_fare, 2, '.', '') }}"
                            data-point-a-name="{{ $savedRoute->point_a_name }}"
                            data-point-a-lat="{{ $savedRoute->point_a_latitude }}"
                            data-point-a-lng="{{ $savedRoute->point_a_longitude }}"
                            data-point-b-name="{{ $savedRoute->point_b_name }}"
                            data-point-b-lat="{{ $savedRoute->point_b_latitude }}"
                            data-point-b-lng="{{ $savedRoute->point_b_longitude }}"
                            data-preset-passengers='@json($savedRoute->passengerStops->where("is_active", true)->pluck("user_id")->values())'
                            data-preset-stops='@json($presetStopPayload)'
                            {{ (string) $selectedSavedRouteId === (string) $savedRoute->id ? 'selected' : '' }}
                        >
                            {{ $savedRoute->route_name ?: $savedRoute->point_a_name.' -> '.$savedRoute->point_b_name }} (RM {{ number_format((float) $savedRoute->default_fare, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>
            @error('saved_route_id')
                <p class="tf-field-error" style="margin-top:4px">{{ $message }}</p>
            @enderror

            {{-- Direction section --}}
            <div class="direction-section" id="outboundDirectionSection">
                <div class="direction-section-head">
                    <div>
                        <p class="direction-title">Trip Direction</p>
                        <p class="field-hint" id="directionHelpText">Select a saved route and trip type first, then set the actual pickup and destination here.</p>
                    </div>
                    <button type="button" class="direction-swap-btn" id="swapOutboundDirectionBtn">
                        <i class="fa-solid fa-right-left"></i>
                        <span>Switch</span>
                    </button>
                </div>
                <div class="direction-points">
                    <div class="direction-point-card pickup">
                        <span class="direction-point-label"><i class="fa-solid fa-location-dot"></i>Pickup Point</span>
                        <span class="direction-point-value" id="outboundPickupPreview">Select a saved route first.</span>
                    </div>
                    <div class="direction-point-card destination">
                        <span class="direction-point-label"><i class="fa-solid fa-flag-checkered"></i>Destination Point</span>
                        <span class="direction-point-value" id="outboundDestinationPreview">Select a saved route first.</span>
                    </div>
                </div>
                <div class="direction-custom-stops" id="directionCustomStops" hidden>
                    <p class="direction-custom-title">Custom passenger stops on this trip</p>
                    <div class="direction-custom-list" id="directionCustomStopsList"></div>
                </div>
                <div class="direction-return-block" id="returnDirectionBlock" hidden>
                    <p class="direction-title">Return Trip Direction</p>
                    <div class="direction-points">
                        <div class="direction-point-card pickup">
                            <span class="direction-point-label"><i class="fa-solid fa-location-dot"></i>Pickup Point</span>
                            <span class="direction-point-value" id="returnPickupPreview">Select a saved route first.</span>
                        </div>
                        <div class="direction-point-card destination">
                            <span class="direction-point-label"><i class="fa-solid fa-flag-checkered"></i>Destination Point</span>
                            <span class="direction-point-value" id="returnDestinationPreview">Select a saved route first.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── SECTION 2 · SCHEDULE & CAPACITY ── --}}
        <div class="tf-card" data-route-dependent-section>
            <div class="tf-section-header">
                <span class="tf-section-icon"><i class="fa-regular fa-calendar"></i></span>
                <h2 class="tf-section-title">2 · Schedule &amp; Capacity</h2>
            </div>

            {{-- Row 1: datetime · trip type · visibility --}}
            <div class="tf-sched-grid">
                {{-- Departure Date & Time --}}
                <div class="tf-field">
                    <label class="field-label" for="trip_datetime">Departure Date &amp; Time</label>
                    <input
                        id="trip_datetime"
                        class="tf-input"
                        type="datetime-local"
                        name="trip_datetime"
                        value="{{ old('trip_datetime', isset($trip) ? $trip->trip_datetime?->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                        required
                    >
                    @error('trip_datetime')
                        <p class="tf-field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Trip Type (create only) --}}
                @if(!isset($trip) || !$trip)
                    <div class="tf-field">
                        <label class="field-label">Trip Type</label>
                        <div class="tf-tab-row">
                            <label class="tf-tab-btn" for="trip_type_one_way">
                                <input
                                    id="trip_type_one_way"
                                    type="radio"
                                    name="trip_type"
                                    value="one_way"
                                    {{ old('trip_type') === 'one_way' || old('trip_type') === null ? 'checked' : '' }}
                                    required
                                >
                                One-way
                            </label>
                            <label class="tf-tab-btn" for="trip_type_two_way">
                                <input
                                    id="trip_type_two_way"
                                    type="radio"
                                    name="trip_type"
                                    value="two_way"
                                    {{ old('trip_type') === 'two_way' ? 'checked' : '' }}
                                    required
                                >
                                Two-way
                            </label>
                        </div>
                        @error('trip_type')
                            <p class="tf-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    {{-- Spacer so grid stays tidy on edit --}}
                    <div></div>
                @endif

                {{-- Visibility --}}
                <div class="tf-field">
                    <label class="field-label">Visibility</label>
                    <div class="tf-tab-row">
                        <label class="tf-tab-btn" for="visibility_private">
                            <input
                                id="visibility_private"
                                type="radio"
                                name="visibility"
                                value="private"
                                {{ old('visibility', $trip->visibility ?? 'private') === 'private' ? 'checked' : '' }}
                                required
                            >
                            Private
                        </label>
                        <label class="tf-tab-btn" for="visibility_public">
                            <input
                                id="visibility_public"
                                type="radio"
                                name="visibility"
                                value="public"
                                {{ old('visibility', $trip->visibility ?? 'private') === 'public' ? 'checked' : '' }}
                                required
                            >
                            Public
                        </label>
                    </div>
                    @error('visibility')
                        <p class="tf-field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Row 2: seat limit + public note (visibility-conditional) --}}
            <div class="tf-sched-row2">
                {{-- Public-only: seat limit --}}
                <div class="tf-conditional-group" id="publicTripFields" style="grid-column:1/-1;display:none">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="tf-field">
                            <label class="field-label" for="seat_limit">Seat Limit</label>
                            <input
                                id="seat_limit"
                                class="tf-input"
                                type="number"
                                name="seat_limit"
                                min="1"
                                max="20"
                                value="{{ old('seat_limit', $trip->seat_limit ?? '') }}"
                            >
                            <p class="field-hint">For passengers only.</p>
                            @error('seat_limit')
                                <p class="tf-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="tf-field">
                            <label class="field-label" for="public_note">Public Note</label>
                            <textarea id="public_note" class="tf-textarea" name="public_note" rows="2">{{ old('public_note', $trip->public_note ?? '') }}</textarea>
                            <p class="field-hint">Short note shown on Explore.</p>
                            @error('public_note')
                                <p class="tf-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── SECTION 3 · FARE ──────────────── --}}
        <div class="tf-card" data-route-dependent-section>
            <div class="tf-section-header">
                <span class="tf-section-icon"><i class="fa-solid fa-sack-dollar"></i></span>
                <h2 class="tf-section-title">3 · Fare</h2>
            </div>

            <div class="tf-fare-grid">
                {{-- Fare split toggle --}}
                <div class="tf-field">
                    <label class="field-label">Fare Split</label>
                    <input type="hidden" name="include_driver_in_split" value="0">
                    <label class="tf-toggle-row" for="include_driver_in_split">
                        <input
                            id="include_driver_in_split"
                            type="checkbox"
                            name="include_driver_in_split"
                            value="1"
                            {{ (string) old('include_driver_in_split', '1') === '1' ? 'checked' : '' }}
                        >
                        <span>Include driver in split</span>
                    </label>
                    <p class="field-hint">Tick to include the driver in the fare split count.</p>
                </div>

                {{-- Total fare (auto from route, read-only preview) --}}
                <div class="tf-field">
                    <label class="field-label">Total Fare</label>
                    <div class="tf-input tf-input-mono tf-input-readonly" id="fare_total_preview" style="cursor:default">RM 0.00</div>
                    <p class="field-hint">From the selected saved route.</p>
                </div>

                {{-- Per seat (calculated) --}}
                <div class="tf-field">
                    <label class="field-label">Per Seat (auto)</label>
                    <div class="tf-input tf-input-mono tf-input-readonly" id="fare_per_person_preview" style="cursor:default">RM 0.00</div>
                    <p class="field-hint">Passengers see this amount.</p>
                </div>
            </div>

            {{-- Fare preview breakdown --}}
            <div class="tf-fare-preview">
                <p class="tf-fare-preview-title"><i class="fa-solid fa-calculator" style="margin-right:5px"></i>Fare Preview</p>
                <p class="field-hint" id="fare_preview_hint">One-way fare preview.</p>
                <div class="tf-fare-preview-grid">
                    <div class="tf-fare-preview-item">
                        <span class="tf-fare-preview-label">Total Fare</span>
                        <span class="tf-fare-preview-value" id="fare_total_preview_detail">RM 0.00</span>
                    </div>
                    <div class="tf-fare-preview-item">
                        <span class="tf-fare-preview-label">Split Count</span>
                        <span class="tf-fare-preview-value" id="participant_count_preview">1</span>
                    </div>
                    <div class="tf-fare-preview-item">
                        <span class="tf-fare-preview-label">Fare / Person</span>
                        <span class="tf-fare-preview-value" id="fare_per_person_preview_detail">RM 0.00</span>
                    </div>
                </div>
            </div>
            <p class="field-hint" style="margin-top:8px">Passengers see the per-seat fare. The total is drawn from the saved route default.</p>
        </div>

        {{-- ── SECTION 4 · INVITE PASSENGERS ─── --}}
        <div class="tf-card" data-route-dependent-section>
            <div class="tf-section-header">
                <span class="tf-section-icon"><i class="fa-solid fa-users"></i></span>
                <h2 class="tf-section-title">4 · Invite Passengers <span style="font-weight:400;color:var(--muted)">(optional)</span></h2>
            </div>

            <label class="field-label" id="passengerSelectionLabel">Passengers (Accepted Connections)</label>
            <div class="tf-participants-card" style="margin-top:6px">
                @forelse($selectableParticipants as $participant)
                    <label class="tf-participant-card">
                        <input
                            type="checkbox"
                            name="participant_ids[]"
                            value="{{ $participant->id }}"
                            {{ in_array($participant->id, old('participant_ids', $selectedParticipants ?? []), true) ? 'checked' : '' }}
                        >
                        <span class="tf-participant-avatar" aria-hidden="true">{{ strtoupper(substr($participant->name, 0, 1)) }}</span>
                        <span class="tf-participant-meta">
                            <span class="tf-participant-name">{{ $participant->name }}</span>
                            <span class="tf-participant-email">{{ $participant->email }}</span>
                            <span class="tf-participant-preset-note" data-preset-note></span>
                        </span>
                    </label>
                @empty
                    <p class="tf-participants-empty">No accepted connections yet.</p>
                @endforelse
            </div>
            <p class="tf-participants-connection-help">Don't see your friend here? <a href="{{ route('connections.index') }}">Open Connections to add them first.</a></p>
            <p class="field-hint" id="passengerSelectionHint" style="margin-top:4px">Select trusted passengers for a private trip. Public trips can receive join requests from Explore.</p>
            <p class="field-hint" id="privateRoutePointHint">Private passengers use the trip date/time and route points by default.</p>

            {{-- Notes --}}
            <div class="tf-field" style="margin-top:14px">
                <label class="field-label" for="note">Notes</label>
                <textarea id="note" class="tf-textarea" name="note" rows="3">{{ old('note', $trip->note ?? '') }}</textarea>
                @error('note')
                    <p class="tf-field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

    </div>
    {{-- /LEFT COLUMN --}}

    {{-- ═══════════════════════════════════ --}}
    {{-- RIGHT COLUMN ──────────────────────── --}}
    {{-- ═══════════════════════════════════ --}}
    <div class="tf-right-col">

        {{-- ── Map preview card ────────────── --}}
        @if(isset($trip) && $trip)
        <div class="tf-card tf-card-flush">
            <div id="trip-map"></div>
            <div class="tf-map-stops">
                <div class="tf-map-stop-row">
                    <span class="tf-map-stop-dot pickup"></span>
                    <span id="mapPickupLabel" style="font-size:12px;color:var(--ink-2)">Pickup — select a route</span>
                </div>
                <div class="tf-map-stop-row">
                    <span class="tf-map-stop-dot destination"></span>
                    <span id="mapDestinationLabel" style="font-size:12px;color:var(--ink-2)">Destination — select a route</span>
                </div>
            </div>
        </div>
        @else
            <span id="mapPickupLabel" hidden></span>
            <span id="mapDestinationLabel" hidden></span>
        @endif

        {{-- ── Summary card ─────────────────── --}}
        <div class="tf-card tf-card-pad">
            <h3 style="margin:0 0 2px;font-family:var(--font-display);font-size:15px;font-weight:700;color:var(--ink)">Summary</h3>
            <div class="tf-summary-kv">
                <div class="tf-summary-row">
                    <span class="tf-summary-key">Date &amp; Time</span>
                    <span class="tf-summary-val" id="summaryDate">—</span>
                </div>
                <div class="tf-summary-row">
                    <span class="tf-summary-key">Trip Type</span>
                    <span class="tf-summary-val" id="summaryTripType">One-way</span>
                </div>
                <div class="tf-summary-row">
                    <span class="tf-summary-key">Visibility</span>
                    <span class="tf-summary-val" id="summaryVisibility">Private</span>
                </div>
                <div class="tf-summary-row">
                    <span class="tf-summary-key">Seats</span>
                    <span class="tf-summary-val" id="summarySeats">—</span>
                </div>
                <div class="tf-summary-row">
                    <span class="tf-summary-key">Per Seat</span>
                    <span class="tf-summary-val" id="summaryPerSeat">RM 0.00</span>
                </div>
            </div>

            <div class="tf-tip-box" id="summaryTipBox">
                <i class="fa-solid fa-bolt" style="margin-right:4px"></i>
                Public trips with open seats receive more join requests from Explore. Set visibility to <strong>Public</strong> to reach more passengers.
            </div>
        </div>

    </div>
    {{-- /RIGHT COLUMN --}}

</div>
{{-- /tf-page-grid --}}

{{-- ── Cancel confirm modal ────────────────────────────────────────── --}}
@if(!isset($trip) || !$trip)
    <div class="tf-card tf-card-pad tf-mobile-summary-card">
        <h3 style="margin:0 0 2px;font-family:var(--font-display);font-size:15px;font-weight:700;color:var(--ink)">Trip summary</h3>
        <p class="field-hint" id="mobileSummaryInlineMeta" style="margin:2px 0 12px">Complete the required details to review your trip.</p>
        <div class="tf-summary-kv">
            <div class="tf-summary-row">
                <span class="tf-summary-key">Date &amp; Time</span>
                <span class="tf-summary-val" id="mobileSummaryInlineDate">-</span>
            </div>
            <div class="tf-summary-row">
                <span class="tf-summary-key">Trip Type</span>
                <span class="tf-summary-val" id="mobileSummaryInlineTripType">One-way</span>
            </div>
            <div class="tf-summary-row">
                <span class="tf-summary-key">Visibility</span>
                <span class="tf-summary-val" id="mobileSummaryInlineVisibility">Private</span>
            </div>
            <div class="tf-summary-row">
                <span class="tf-summary-key">Seats</span>
                <span class="tf-summary-val" id="mobileSummaryInlineSeats">-</span>
            </div>
            <div class="tf-summary-row">
                <span class="tf-summary-key">Per Seat</span>
                <span class="tf-summary-val" id="mobileSummaryInlinePerSeat">RM 0.00</span>
            </div>
        </div>
        <div class="tf-mobile-summary-actions">
            <a href="{{ route('trips.index') }}" class="btn btn-ghost tf-mobile-cancel-btn">Cancel</a>
            <button type="submit" class="btn btn-primary tf-mobile-publish-btn" form="tripCreateForm" data-trip-publish-button>
                Publish
                <i class="fa-solid fa-arrow-right" style="font-size:11px"></i>
            </button>
        </div>
    </div>
@endif

@if(!isset($trip) || !$trip)
    <div class="tf-mobile-publish-bar" aria-label="Trip publish summary">
        <div class="tf-mobile-publish-summary">
            <span class="tf-mobile-step-pill" id="mobilePublishStep">Step 1 of 3</span>
            <span class="tf-mobile-publish-title" id="mobilePublishTitle">Choose a saved route</span>
            <span class="tf-mobile-publish-meta" id="mobilePublishSummary">Select a saved route first.</span>
            <div class="tf-mobile-publish-grid">
                <span class="tf-mobile-publish-item">
                    <span>Date</span>
                    <strong id="mobileSummaryDate">-</strong>
                </span>
                <span class="tf-mobile-publish-item">
                    <span>Trip Type</span>
                    <strong id="mobileSummaryTripType">One-way</strong>
                </span>
                <span class="tf-mobile-publish-item">
                    <span>Visibility</span>
                    <strong id="mobileSummaryVisibility">Private</strong>
                </span>
                <span class="tf-mobile-publish-item">
                    <span>Seats</span>
                    <strong id="mobileSummarySeats">-</strong>
                </span>
                <span class="tf-mobile-publish-item">
                    <span>Per Seat</span>
                    <strong id="mobileSummaryPerSeat">RM 0.00</strong>
                </span>
            </div>
        </div>
        <div class="tf-mobile-publish-actions">
            <a href="{{ route('trips.index') }}" class="btn btn-ghost tf-mobile-cancel-btn">Cancel</a>
            <button type="submit" class="btn btn-primary tf-mobile-publish-btn" form="tripCreateForm" data-trip-publish-button>
                Publish
                <i class="fa-solid fa-arrow-right" style="font-size:11px"></i>
            </button>
        </div>
    </div>
@endif

<div class="tf-cancel-modal" id="tripCancelModal" aria-hidden="true">
    <div class="tf-cancel-card" role="dialog" aria-modal="true" aria-labelledby="tripCancelTitle">
        <h3 class="tf-cancel-card-title" id="tripCancelTitle">Discard changes?</h3>
        <p class="tf-cancel-card-text">You have unsaved trip details. Do you want to save them as a draft or discard this form?</p>
        <div class="tf-cancel-card-actions">
            <button type="button" class="btn btn-ghost btn-sm" id="tripKeepEditingBtn">Keep Editing</button>
            <button type="button" class="btn btn-danger btn-sm" id="tripDiscardBtn">Discard</button>
            <button type="button" class="btn btn-dark btn-sm" id="tripSaveDraftBtn">Save Draft</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.querySelector('form[action*="trips"]');
        const routeSelect = document.getElementById('saved_route_id');
        const routePicker = document.getElementById('savedRoutePicker');
        const routeTrigger = document.getElementById('savedRouteTrigger');
        const routeTriggerText = document.getElementById('savedRouteTriggerText');
        const routePanel = document.getElementById('savedRoutePanel');
        const routeList = document.getElementById('savedRouteList');
        const routeSearchInput = document.getElementById('savedRouteSearchInput');
        const checkboxes = document.querySelectorAll('input[name="participant_ids[]"]');
        // Fare display targets — two sets: the card row displays + the preview breakdown
        const totalFareEl = document.getElementById('fare_total_preview');
        const totalFareDetailEl = document.getElementById('fare_total_preview_detail');
        const participantCountEl = document.getElementById('participant_count_preview');
        const farePerPersonEl = document.getElementById('fare_per_person_preview');
        const farePerPersonDetailEl = document.getElementById('fare_per_person_preview_detail');
        const statusInput = document.getElementById('status_system_input');
        const modal = document.getElementById('tripCancelModal');
        const keepEditingBtn = document.getElementById('tripKeepEditingBtn');
        const discardBtn = document.getElementById('tripDiscardBtn');
        const saveDraftBtn = document.getElementById('tripSaveDraftBtn');
        const noteInput = document.getElementById('note');
        const tripDatetime = document.getElementById('trip_datetime');
        const includeDriverCheckbox = document.getElementById('include_driver_in_split');
        const tripTypeInputs = document.querySelectorAll('input[name="trip_type"]');
        const visibilityInputs = document.querySelectorAll('input[name="visibility"]');
        const publicTripFields = document.getElementById('publicTripFields');
        const seatLimitInput = document.getElementById('seat_limit');
        const passengerSelectionLabel = document.getElementById('passengerSelectionLabel');
        const passengerSelectionHint = document.getElementById('passengerSelectionHint');
        const farePreviewHint = document.getElementById('fare_preview_hint');
        const outboundPickupKeyInput = document.getElementById('outbound_pickup_key');
        const outboundDestinationKeyInput = document.getElementById('outbound_destination_key');
        const returnPickupKeyInput = document.getElementById('return_pickup_key');
        const returnDestinationKeyInput = document.getElementById('return_destination_key');
        const outboundPickupPreview = document.getElementById('outboundPickupPreview');
        const outboundDestinationPreview = document.getElementById('outboundDestinationPreview');
        const returnPickupPreview = document.getElementById('returnPickupPreview');
        const returnDestinationPreview = document.getElementById('returnDestinationPreview');
        const outboundDirectionSection = document.getElementById('outboundDirectionSection');
        const returnDirectionBlock = document.getElementById('returnDirectionBlock');
        const swapOutboundDirectionBtn = document.getElementById('swapOutboundDirectionBtn');
        const directionHelpText = document.getElementById('directionHelpText');
        const directionCustomStops = document.getElementById('directionCustomStops');
        const directionCustomStopsList = document.getElementById('directionCustomStopsList');
        // Summary panel refs
        const summaryDate = document.getElementById('summaryDate');
        const summaryTripType = document.getElementById('summaryTripType');
        const summaryVisibility = document.getElementById('summaryVisibility');
        const summarySeats = document.getElementById('summarySeats');
        const summaryPerSeat = document.getElementById('summaryPerSeat');
        const summaryTipBox = document.getElementById('summaryTipBox');
        const mobileSummaryDate = document.getElementById('mobileSummaryDate');
        const mobileSummaryTripType = document.getElementById('mobileSummaryTripType');
        const mobileSummaryVisibility = document.getElementById('mobileSummaryVisibility');
        const mobileSummarySeats = document.getElementById('mobileSummarySeats');
        const mobileSummaryPerSeat = document.getElementById('mobileSummaryPerSeat');
        const mobileSummaryInlineMeta = document.getElementById('mobileSummaryInlineMeta');
        const mobileSummaryInlineDate = document.getElementById('mobileSummaryInlineDate');
        const mobileSummaryInlineTripType = document.getElementById('mobileSummaryInlineTripType');
        const mobileSummaryInlineVisibility = document.getElementById('mobileSummaryInlineVisibility');
        const mobileSummaryInlineSeats = document.getElementById('mobileSummaryInlineSeats');
        const mobileSummaryInlinePerSeat = document.getElementById('mobileSummaryInlinePerSeat');
        // Map labels
        const mapPickupLabel = document.getElementById('mapPickupLabel');
        const mapDestinationLabel = document.getElementById('mapDestinationLabel');
        const publishButtons = document.querySelectorAll('[data-trip-publish-button]');
        const mobilePublishBar = document.querySelector('.tf-mobile-publish-bar');
        const mobilePublishStep = document.getElementById('mobilePublishStep');
        const mobilePublishTitle = document.getElementById('mobilePublishTitle');
        const mobilePublishSummary = document.getElementById('mobilePublishSummary');
        const routeDependentSections = document.querySelectorAll('[data-route-dependent-section]');

        const fallbackTripType = @json($currentTripType);
        let isSubmittingForm = false;

        if (!form || !routeSelect || !statusInput) return;

        if (mobilePublishBar && mobilePublishBar.parentElement !== document.body) {
            document.body.appendChild(mobilePublishBar);
        }

        const initialSnapshot = {
            savedRouteId: routeSelect.value,
            tripDatetime: tripDatetime ? tripDatetime.value : '',
            note: noteInput ? noteInput.value : '',
            publicNote: document.getElementById('public_note') ? document.getElementById('public_note').value : '',
            includeDriver: includeDriverCheckbox ? includeDriverCheckbox.checked : true,
            visibility: selectedVisibility(),
            seatLimit: seatLimitInput ? seatLimitInput.value : '',
            tripType: selectedTripType(),
            outboundPickupKey: outboundPickupKeyInput ? outboundPickupKeyInput.value : '',
            outboundDestinationKey: outboundDestinationKeyInput ? outboundDestinationKeyInput.value : '',
            returnPickupKey: returnPickupKeyInput ? returnPickupKeyInput.value : '',
            returnDestinationKey: returnDestinationKeyInput ? returnDestinationKeyInput.value : '',
            checkedParticipants: Array.from(checkboxes).filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value).join(',')
        };

        function selectedFare() {
            const option = routeSelect.options[routeSelect.selectedIndex];
            return parseFloat(option && option.dataset ? (option.dataset.fare || '0') : '0') || 0;
        }

        function selectedTripType() {
            const chosen = Array.from(tripTypeInputs).find((input) => input.checked);
            if (chosen) return chosen.value;
            return tripTypeInputs.length ? '' : fallbackTripType;
        }

        function selectedVisibility() {
            const chosen = Array.from(visibilityInputs).find((input) => input.checked);
            return chosen ? chosen.value : 'private';
        }

        function tripMultiplier() {
            return selectedTripType() === 'two_way' ? 2 : 1;
        }

        function participantCount() {
            const isPublic = selectedVisibility() === 'public';
            if (isPublic) {
                const seatLimit = seatLimitInput ? (parseInt(seatLimitInput.value || '0', 10) || 0) : 0;
                const includeDriver = includeDriverCheckbox && includeDriverCheckbox.checked;
                const splitCount = seatLimit + (includeDriver ? 1 : 0);
                return splitCount > 0 ? splitCount : (includeDriver ? 1 : 0);
            }

            let count = includeDriverCheckbox && includeDriverCheckbox.checked ? 1 : 0;
            checkboxes.forEach((checkbox) => {
                if (checkbox.checked) count += 1;
            });
            return count;
        }

        function recalc() {
            const total = selectedFare() * tripMultiplier();
            const count = participantCount();
            const perPerson = count > 0 ? (total / count) : 0;
            const totalFmt = 'RM ' + total.toFixed(2);
            const perFmt = 'RM ' + perPerson.toFixed(2);

            // Update fare card row displays
            if (totalFareEl) totalFareEl.textContent = totalFmt;
            if (farePerPersonEl) farePerPersonEl.textContent = perFmt;
            // Update fare preview breakdown
            if (totalFareDetailEl) totalFareDetailEl.textContent = totalFmt;
            if (participantCountEl) participantCountEl.textContent = String(count);
            if (farePerPersonDetailEl) farePerPersonDetailEl.textContent = perFmt;

            if (farePreviewHint) {
                const publicHint = selectedVisibility() === 'public'
                    ? ' Public split count follows the passenger seat limit.'
                    : '';
                farePreviewHint.textContent = tripMultiplier() === 2
                    ? 'Two-way selected: total includes the return trip fare.' + publicHint
                    : 'One-way fare preview.' + publicHint;
            }

            updateSummaryPanel(perFmt);
            updateDirectionVisibility();
        }

        function updateSummaryPanel(perFmt) {
            // Date summary
            if (summaryDate && tripDatetime && tripDatetime.value) {
                const d = new Date(tripDatetime.value);
                summaryDate.textContent = isNaN(d.getTime()) ? '—' : d.toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
            }
            // Visibility summary
            if (summaryVisibility) {
                const vis = selectedVisibility();
                summaryVisibility.textContent = vis.charAt(0).toUpperCase() + vis.slice(1);
            }
            if (summaryTripType) {
                summaryTripType.textContent = selectedTripType() === 'two_way' ? 'Two-way' : 'One-way';
            }
            // Seats summary
            if (summarySeats) {
                const isPublic = selectedVisibility() === 'public';
                const sl = seatLimitInput && seatLimitInput.value ? seatLimitInput.value : null;
                summarySeats.textContent = isPublic && sl ? sl + ' passenger seats' : participantCount() + ' person(s)';
            }
            // Per seat
            if (summaryPerSeat) summaryPerSeat.textContent = perFmt || 'RM 0.00';
            if (mobileSummaryDate && summaryDate) mobileSummaryDate.textContent = summaryDate.textContent || '-';
            if (mobileSummaryTripType) mobileSummaryTripType.textContent = selectedTripType() === 'two_way' ? 'Two-way' : 'One-way';
            if (mobileSummaryVisibility && summaryVisibility) mobileSummaryVisibility.textContent = summaryVisibility.textContent || 'Private';
            if (mobileSummarySeats && summarySeats) mobileSummarySeats.textContent = summarySeats.textContent || '-';
            if (mobileSummaryPerSeat) mobileSummaryPerSeat.textContent = perFmt || 'RM 0.00';
            if (mobileSummaryInlineDate && summaryDate) mobileSummaryInlineDate.textContent = summaryDate.textContent || '-';
            if (mobileSummaryInlineTripType) mobileSummaryInlineTripType.textContent = selectedTripType() === 'two_way' ? 'Two-way' : 'One-way';
            if (mobileSummaryInlineVisibility && summaryVisibility) mobileSummaryInlineVisibility.textContent = summaryVisibility.textContent || 'Private';
            if (mobileSummaryInlineSeats && summarySeats) mobileSummaryInlineSeats.textContent = summarySeats.textContent || '-';
            if (mobileSummaryInlinePerSeat) mobileSummaryInlinePerSeat.textContent = perFmt || 'RM 0.00';
            updatePublishState(perFmt);
        }

        function publishBlocker() {
            if (!routeSelect.value) {
                return {
                    element: routePicker || routeSelect,
                    step: 'Step 1 of 3',
                    title: 'Choose a saved route',
                    message: 'Select a saved route first.',
                };
            }
            if (tripDatetime && !tripDatetime.value) {
                return {
                    element: tripDatetime,
                    step: 'Step 2 of 3',
                    title: 'Set date and time',
                    message: 'Choose a departure date and time.',
                };
            }
            if (!selectedTripType()) {
                const firstTripType = tripTypeInputs[0];
                return {
                    element: firstTripType || form,
                    step: 'Step 2 of 3',
                    title: 'Choose trip direction',
                    message: 'Choose one-way or two-way.',
                };
            }
            if (selectedVisibility() === 'public' && seatLimitInput && !(parseInt(seatLimitInput.value || '0', 10) > 0)) {
                return {
                    element: seatLimitInput,
                    step: 'Step 3 of 3',
                    title: 'Set public seats',
                    message: 'Set passenger seats for a public trip.',
                };
            }
            return null;
        }

        function focusPublishBlocker(blocker) {
            if (!blocker || !blocker.element) return;
            blocker.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => {
                if (blocker.element === routePicker && routeTrigger) {
                    routeTrigger.focus();
                    openRoutePicker();
                    return;
                }
                if (typeof blocker.element.focus === 'function') {
                    blocker.element.focus({ preventScroll: true });
                }
            }, 250);
        }

        function updatePublishState(perFmt = null) {
            updateRouteDependentSections();
            const blocker = publishBlocker();
            publishButtons.forEach((button) => {
                button.classList.toggle('is-incomplete', Boolean(blocker));
                button.setAttribute('aria-disabled', blocker ? 'true' : 'false');
                button.title = blocker ? blocker.message : '';
            });
            if (mobilePublishBar) {
                mobilePublishBar.classList.toggle('is-incomplete', Boolean(blocker));
            }

            if (mobilePublishSummary) {
                if (blocker) {
                    if (mobilePublishStep) mobilePublishStep.textContent = blocker.step || 'Next step';
                    if (mobilePublishTitle) mobilePublishTitle.textContent = blocker.title || 'Complete trip details';
                    mobilePublishSummary.textContent = blocker.message;
                    return;
                }
                if (mobilePublishStep) mobilePublishStep.textContent = 'Trip summary';
                if (mobilePublishTitle) mobilePublishTitle.textContent = 'Trip summary';
                const visibility = selectedVisibility();
                const seatText = summarySeats ? summarySeats.textContent : '';
                const tripTypeText = selectedTripType() === 'two_way' ? 'Two-way' : 'One-way';
                const compactSummary = [
                    tripTypeText,
                    visibility.charAt(0).toUpperCase() + visibility.slice(1),
                    seatText,
                    perFmt || (summaryPerSeat ? summaryPerSeat.textContent : 'RM 0.00'),
                ].filter(Boolean).join(' · ');
                mobilePublishSummary.textContent = compactSummary;
                if (mobileSummaryInlineMeta) mobileSummaryInlineMeta.textContent = compactSummary;
            }
        }

        function updateRouteDependentSections() {
            const hasRoute = Boolean(routeSelect.value);

            routeDependentSections.forEach((section) => {
                section.classList.toggle('is-route-locked', !hasRoute);
                section.setAttribute('aria-disabled', hasRoute ? 'false' : 'true');

                section.querySelectorAll('input, select, textarea, button').forEach((control) => {
                    control.disabled = !hasRoute;
                });

                section.querySelectorAll('a').forEach((link) => {
                    if (!hasRoute) {
                        if (!link.hasAttribute('data-route-lock-tabindex')) {
                            link.setAttribute('data-route-lock-tabindex', link.getAttribute('tabindex') || '');
                        }
                        link.setAttribute('tabindex', '-1');
                        link.setAttribute('aria-disabled', 'true');
                        return;
                    }

                    const previousTabIndex = link.getAttribute('data-route-lock-tabindex');
                    if (previousTabIndex === '') {
                        link.removeAttribute('tabindex');
                    } else if (previousTabIndex !== null) {
                        link.setAttribute('tabindex', previousTabIndex);
                    }
                    link.removeAttribute('data-route-lock-tabindex');
                    link.removeAttribute('aria-disabled');
                });
            });
        }

        function currentSnapshot() {
            return {
                savedRouteId: routeSelect.value,
                tripDatetime: tripDatetime ? tripDatetime.value : '',
                note: noteInput ? noteInput.value : '',
                publicNote: document.getElementById('public_note') ? document.getElementById('public_note').value : '',
                includeDriver: includeDriverCheckbox ? includeDriverCheckbox.checked : true,
                visibility: selectedVisibility(),
                seatLimit: seatLimitInput ? seatLimitInput.value : '',
                tripType: selectedTripType(),
                outboundPickupKey: outboundPickupKeyInput ? outboundPickupKeyInput.value : '',
                outboundDestinationKey: outboundDestinationKeyInput ? outboundDestinationKeyInput.value : '',
                returnPickupKey: returnPickupKeyInput ? returnPickupKeyInput.value : '',
                returnDestinationKey: returnDestinationKeyInput ? returnDestinationKeyInput.value : '',
                checkedParticipants: Array.from(checkboxes).filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value).join(',')
            };
        }

        function hasFormProgress() {
            const current = currentSnapshot();
            return current.savedRouteId !== '' ||
                current.note.trim() !== '' ||
                current.checkedParticipants !== '' ||
                (current.tripDatetime !== '' && current.tripDatetime !== initialSnapshot.tripDatetime) ||
                current.savedRouteId !== initialSnapshot.savedRouteId ||
                current.note !== initialSnapshot.note ||
                current.publicNote !== initialSnapshot.publicNote ||
                current.includeDriver !== initialSnapshot.includeDriver ||
                current.visibility !== initialSnapshot.visibility ||
                current.seatLimit !== initialSnapshot.seatLimit ||
                current.tripType !== initialSnapshot.tripType ||
                current.outboundPickupKey !== initialSnapshot.outboundPickupKey ||
                current.outboundDestinationKey !== initialSnapshot.outboundDestinationKey ||
                current.returnPickupKey !== initialSnapshot.returnPickupKey ||
                current.returnDestinationKey !== initialSnapshot.returnDestinationKey ||
                current.checkedParticipants !== initialSnapshot.checkedParticipants;
        }

        function updateVisibilityFields() {
            const visibility = selectedVisibility();
            const isPublic = visibility === 'public';

            if (publicTripFields) {
                publicTripFields.style.display = isPublic ? 'grid' : 'none';
            }
            if (seatLimitInput) {
                seatLimitInput.required = isPublic;
            }
            if (passengerSelectionLabel) {
                passengerSelectionLabel.textContent = isPublic
                    ? 'Preselected Passengers (Optional)'
                    : 'Passengers (Accepted Connections)';
            }
            if (passengerSelectionHint) {
                passengerSelectionHint.textContent = isPublic
                    ? 'Optional: add trusted passengers first. Preselected passengers use the seat limit, and remaining seats stay open for Explore requests.'
                    : 'Select trusted passengers for a private trip.';
            }
            // Summary tip box
            if (summaryTipBox) {
                summaryTipBox.style.display = isPublic ? 'none' : '';
            }
        }

        function selectedRouteData() {
            const option = routeSelect.options[routeSelect.selectedIndex];
            if (!option || !option.value || !option.dataset) return null;

            return {
                point_a: {
                    name: option.dataset.pointAName || 'Point A',
                    lat: option.dataset.pointALat || '',
                    lng: option.dataset.pointALng || '',
                },
                point_b: {
                    name: option.dataset.pointBName || 'Point B',
                    lat: option.dataset.pointBLat || '',
                    lng: option.dataset.pointBLng || '',
                },
            };
        }

        function selectedRoutePresetPassengerIds() {
            const option = routeSelect.options[routeSelect.selectedIndex];
            if (!option || !option.dataset || !option.dataset.presetPassengers) return [];

            try {
                const ids = JSON.parse(option.dataset.presetPassengers);
                return Array.isArray(ids) ? ids.map((id) => String(id)) : [];
            } catch (error) {
                return [];
            }
        }

        function selectedRoutePresetStops() {
            const option = routeSelect.options[routeSelect.selectedIndex];
            if (!option || !option.dataset || !option.dataset.presetStops) return [];

            try {
                const stops = JSON.parse(option.dataset.presetStops);
                return Array.isArray(stops) ? stops : [];
            } catch (error) {
                return [];
            }
        }

        function money(value) {
            const amount = parseFloat(value || '0') || 0;
            return 'RM ' + amount.toFixed(2);
        }

        function applyPresetPassengers() {
            const ids = selectedRoutePresetPassengerIds();
            const stopsByUser = selectedRoutePresetStops().reduce((carry, stop) => {
                carry[String(stop.user_id)] = stop;
                return carry;
            }, {});

            checkboxes.forEach((checkbox) => {
                const id = String(checkbox.value);
                const card = checkbox.closest('.tf-participant-card');
                const note = card ? card.querySelector('[data-preset-note]') : null;
                if (card) card.classList.remove('has-route-preset');
                if (note) note.textContent = '';

                if (ids.includes(id)) {
                    checkbox.checked = true;
                    if (card) card.classList.add('has-route-preset');
                    if (note) {
                        const stop = stopsByUser[id] || {};
                        const extra = parseFloat(stop.extra_fee_amount || '0') || 0;
                        note.textContent = extra > 0
                            ? 'Custom stop auto-added · Extra ' + money(extra)
                            : 'Custom stop auto-added';
                    }
                }
            });
        }

        function renderDirectionCustomStops() {
            if (!directionCustomStops || !directionCustomStopsList) return;
            const stops = selectedRoutePresetStops();
            if (!stops.length) {
                directionCustomStops.hidden = true;
                directionCustomStopsList.innerHTML = '';
                return;
            }

            directionCustomStops.hidden = false;
            directionCustomStopsList.innerHTML = stops.map((stop) => {
                const extra = parseFloat(stop.extra_fee_amount || '0') || 0;
                const name = String(stop.name || 'Passenger')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                const pickup = String(stop.pickup_name || 'custom pickup')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                const dropoff = String(stop.dropoff_name || 'custom drop-off')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                return '<div class="direction-custom-item">'
                    + '<span><span class="direction-custom-name">' + name + '</span>: '
                    + pickup + ' → ' + dropoff + '</span>'
                    + '<span class="direction-custom-extra">' + (extra > 0 ? '+ ' + money(extra) : 'No extra') + '</span>'
                    + '</div>';
            }).join('');
        }

        function normalizePointKey(value, fallback = 'point_a') {
            return value === 'point_b' ? 'point_b' : fallback;
        }

        function oppositePointKey(value) {
            return normalizePointKey(value) === 'point_a' ? 'point_b' : 'point_a';
        }

        function hasDirectionPrerequisites() {
            return Boolean(selectedRouteData()) && Boolean(selectedTripType());
        }

        function syncDirectionKeys() {
            const outboundPickupKey = normalizePointKey(outboundPickupKeyInput ? outboundPickupKeyInput.value : 'point_a');
            const outboundDestinationKey = oppositePointKey(outboundPickupKey);
            const returnPickupKey = outboundDestinationKey;
            const returnDestinationKey = outboundPickupKey;

            if (outboundPickupKeyInput) outboundPickupKeyInput.value = outboundPickupKey;
            if (outboundDestinationKeyInput) outboundDestinationKeyInput.value = outboundDestinationKey;
            if (returnPickupKeyInput) returnPickupKeyInput.value = returnPickupKey;
            if (returnDestinationKeyInput) returnDestinationKeyInput.value = returnDestinationKey;
        }

        function updateDirectionPreview() {
            const routeData = selectedRouteData();
            const tripType = selectedTripType();
            const isReady = hasDirectionPrerequisites();
            if (!isReady) {
                const waitingText = routeData ? 'Select a trip type first.' : 'Select a saved route first.';
                if (outboundPickupPreview) outboundPickupPreview.textContent = waitingText;
                if (outboundDestinationPreview) outboundDestinationPreview.textContent = waitingText;
                if (returnPickupPreview) returnPickupPreview.textContent = waitingText;
                if (returnDestinationPreview) returnDestinationPreview.textContent = waitingText;
                renderDirectionCustomStops();
                if (outboundDirectionSection) outboundDirectionSection.classList.add('is-disabled');
                if (swapOutboundDirectionBtn) swapOutboundDirectionBtn.disabled = true;
                if (directionHelpText) directionHelpText.textContent = 'Select a saved route and trip type first, then set the actual pickup and destination here.';
                if (mapPickupLabel) mapPickupLabel.textContent = 'Pickup — select a route';
                if (mapDestinationLabel) mapDestinationLabel.textContent = 'Destination — select a route';
                return;
            }

            syncDirectionKeys();

            const outboundPickupKey = normalizePointKey(outboundPickupKeyInput ? outboundPickupKeyInput.value : 'point_a');
            const outboundDestinationKey = oppositePointKey(outboundPickupKey);
            const returnPickupKey = outboundDestinationKey;
            const returnDestinationKey = outboundPickupKey;

            if (outboundPickupPreview) outboundPickupPreview.textContent = routeData[outboundPickupKey].name || '-';
            if (outboundDestinationPreview) outboundDestinationPreview.textContent = routeData[outboundDestinationKey].name || '-';
            if (returnPickupPreview) returnPickupPreview.textContent = routeData[returnPickupKey].name || '-';
            if (returnDestinationPreview) returnDestinationPreview.textContent = routeData[returnDestinationKey].name || '-';
            if (outboundDirectionSection) outboundDirectionSection.classList.toggle('is-disabled', !isReady);
            if (swapOutboundDirectionBtn) swapOutboundDirectionBtn.disabled = !isReady;
            if (directionHelpText) {
                directionHelpText.textContent = tripType === 'two_way'
                    ? 'Saved routes only store addresses. This card shows outbound and return directions together.'
                    : 'Saved routes only store addresses. Set the actual pickup and destination here.';
            }
            // Update map labels
            if (mapPickupLabel) mapPickupLabel.textContent = routeData[outboundPickupKey].name || 'Pickup';
            if (mapDestinationLabel) mapDestinationLabel.textContent = routeData[outboundDestinationKey].name || 'Destination';
            renderDirectionCustomStops();
        }

        function updateDirectionVisibility() {
            const isTwoWay = selectedTripType() === 'two_way';
            if (outboundDirectionSection) outboundDirectionSection.hidden = false;
            if (returnDirectionBlock) returnDirectionBlock.hidden = !isTwoWay;
        }

        function swapDirection() {
            if (!outboundPickupKeyInput || !hasDirectionPrerequisites()) return;
            outboundPickupKeyInput.value = oppositePointKey(outboundPickupKeyInput.value);
            syncDirectionKeys();
            updateDirectionPreview();
        }

        function showModal() {
            if (!modal) return;
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }

        function hideModal() {
            if (!modal) return;
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        }

        function submitAsDraft() {
            isSubmittingForm = true;
            statusInput.value = 'draft';
            form.submit();
        }

        function selectedRouteText() {
            const option = routeSelect.options[routeSelect.selectedIndex];
            if (!option || !option.value) return '-- Search or select a route --';
            return (option.textContent || '').trim();
        }

        function updateRouteTriggerText() {
            if (!routeTriggerText) return;
            const text = selectedRouteText();
            routeTriggerText.textContent = text;
            routeTriggerText.classList.toggle('route-picker-placeholder', text === '-- Search or select a route --');
        }

        function getRouteOptions() {
            return Array.from(routeSelect.options).filter((option) => option.value);
        }

        function renderRouteOptions() {
            if (!routeList) return;
            const query = (routeSearchInput && routeSearchInput.value ? routeSearchInput.value : '').toLowerCase().trim();
            const selected = routeSelect.value;
            const options = getRouteOptions().filter((option) => {
                const text = (option.textContent || '').toLowerCase();
                return text.indexOf(query) !== -1;
            });

            if (!options.length) {
                routeList.innerHTML = '<div class="route-picker-empty">No routes found. Try another keyword.</div>';
                return;
            }

            routeList.innerHTML = options.map((option) => {
                const value = String(option.value);
                const text = (option.textContent || '').trim()
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                const isActive = selected === value ? ' active' : '';
                return '<button type="button" class="route-picker-option' + isActive + '" data-value="' + value + '">' + text + '</button>';
            }).join('');

            Array.from(routeList.querySelectorAll('.route-picker-option')).forEach((button) => {
                button.addEventListener('click', function () {
                    routeSelect.value = button.getAttribute('data-value') || '';
                    applyPresetPassengers();
                    updateRouteTriggerText();
                    syncDirectionKeys();
                    updateDirectionPreview();
                    recalc();
                    closeRoutePicker();
                });
            });
        }

        function openRoutePicker() {
            if (!routePicker) return;
            routePicker.classList.add('open');
            renderRouteOptions();
            if (routeSearchInput) {
                routeSearchInput.value = '';
                routeSearchInput.focus();
            }
        }

        function closeRoutePicker() {
            if (!routePicker) return;
            routePicker.classList.remove('open');
        }

        routeSelect.addEventListener('change', () => {
            applyPresetPassengers();
            syncDirectionKeys();
            updateDirectionPreview();
            recalc();
        });
        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', () => {
            if (!checkbox.checked) {
                const card = checkbox.closest('.tf-participant-card');
                const note = card ? card.querySelector('[data-preset-note]') : null;
                if (card) card.classList.remove('has-route-preset');
                if (note) note.textContent = '';
            }
            recalc();
        }));
        if (includeDriverCheckbox) {
            includeDriverCheckbox.addEventListener('change', recalc);
        }
        tripTypeInputs.forEach((input) => input.addEventListener('change', () => {
            syncDirectionKeys();
            updateDirectionPreview();
            recalc();
        }));
        visibilityInputs.forEach((input) => input.addEventListener('change', () => {
            updateVisibilityFields();
            recalc();
        }));
        if (seatLimitInput) {
            seatLimitInput.addEventListener('input', recalc);
            seatLimitInput.addEventListener('change', recalc);
        }
        if (tripDatetime) {
            tripDatetime.addEventListener('change', () => recalc());
            tripDatetime.addEventListener('input', () => recalc());
        }

        if (routeTrigger) {
            routeTrigger.addEventListener('click', function () {
                if (!routePicker) return;
                if (routePicker.classList.contains('open')) {
                    closeRoutePicker();
                } else {
                    openRoutePicker();
                }
            });
        }

        if (routeSearchInput) {
            routeSearchInput.addEventListener('input', renderRouteOptions);
        }

        if (swapOutboundDirectionBtn) {
            swapOutboundDirectionBtn.addEventListener('click', () => {
                swapDirection();
            });
        }

        document.addEventListener('click', function (event) {
            if (!routePicker) return;
            if (!routePicker.contains(event.target)) {
                closeRoutePicker();
            }
        });

        // Back-navigation guard wired to header Cancel button via event
        window.addEventListener('carpoolhub:mobile-back', function (event) {
            if (!hasFormProgress()) {
                return;
            }
            event.preventDefault();
            showModal();
        });

        if (keepEditingBtn) keepEditingBtn.addEventListener('click', hideModal);
        if (discardBtn) {
            discardBtn.addEventListener('click', function () {
                isSubmittingForm = true;
                window.location.href = "{{ route('trips.index') }}";
            });
        }
        if (saveDraftBtn) saveDraftBtn.addEventListener('click', submitAsDraft);

        publishButtons.forEach((button) => {
            button.addEventListener('click', function (event) {
                const blocker = publishBlocker();
                if (!blocker) return;
                event.preventDefault();
                event.stopPropagation();
                focusPublishBlocker(blocker);
            });
        });

        form.addEventListener('submit', function (event) {
            if (statusInput.value !== 'draft') {
                const blocker = publishBlocker();
                if (blocker) {
                    event.preventDefault();
                    focusPublishBlocker(blocker);
                    return;
                }
            }
            isSubmittingForm = true;
        });

        // Intercept browser/device back to show cancel-confirm modal when form has progress.
        if (!window.__carpoolhubTripCreateBackGuardApplied) {
            window.__carpoolhubTripCreateBackGuardApplied = true;
            history.pushState({ tripCreateGuard: true }, '', window.location.href);
            window.addEventListener('popstate', function () {
                if (isSubmittingForm || !hasFormProgress()) {
                    return;
                }
                showModal();
                history.pushState({ tripCreateGuard: true }, '', window.location.href);
            });
        }

        window.addEventListener('beforeunload', function (event) {
            if (isSubmittingForm || !hasFormProgress()) {
                return;
            }
            event.preventDefault();
            event.returnValue = 'You have unsaved trip data. If you leave now, your input will be lost.';
        });

        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) hideModal();
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hideModal();
                closeRoutePicker();
            }
        });

        updateRouteTriggerText();
        applyPresetPassengers();
        syncDirectionKeys();
        updateDirectionPreview();
        updateVisibilityFields();
        recalc();
    })();
</script>
