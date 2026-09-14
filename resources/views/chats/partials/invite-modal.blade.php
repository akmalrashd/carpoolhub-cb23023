{{-- "Invite Connections" picker — replaces the old prompt()-based flow
     (type comma-separated names into a browser dialog) with a themed
     checkbox list: search by name/email, tick as many as you like, connections
     already in this chat show greyed out and locked rather than disappearing.
     Reuses the same modal shell/classes as trip-requests-modal.blade.php
     (trips.css) instead of duplicating that CSS. Driven by public/js/
     chats-show.js, populated from a single chats.picker-options fetch —
     search filters that already-loaded list client-side, no re-fetching. --}}
<div class="trip-payment-review-modal" id="inviteConnectionsModal" aria-hidden="true">
    <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="inviteConnectionsTitle">
        <div class="trip-payment-review-head">
            <div>
                <h3 class="trip-payment-review-title" id="inviteConnectionsTitle">Invite Connections</h3>
                <p class="trip-payment-review-sub">Tick who to add to this chat.</p>
            </div>
            <button type="button" class="trip-payment-review-close" id="inviteConnectionsClose" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div style="padding:10px 16px 8px;">
            <input
                type="text"
                id="inviteConnectionsSearch"
                class="trip-request-tool"
                style="width:100%; box-sizing:border-box;"
                placeholder="Search by name or email..."
            >
        </div>
        <div class="trip-payment-review-list" id="inviteConnectionsList" style="padding-top:4px;"></div>
        <div style="display:flex; justify-content:flex-end; gap:8px; padding:12px 16px 16px; border-top:1px solid var(--hairline);">
            <button type="button" class="btn btn-ghost btn-sm" id="inviteConnectionsCancelBtn">Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" id="inviteConnectionsSubmitBtn" disabled><span id="inviteConnectionsCount">Invite (0)</span></button>
        </div>
    </div>
</div>
