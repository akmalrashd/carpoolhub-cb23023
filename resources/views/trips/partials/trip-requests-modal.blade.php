{{-- Shared "Manage requests" popup (+ its Reject/Remove reason sub-popups) —
     originally trips/index.blade.php only. Reused as-is (same ids/classes,
     same public/js/trip-requests-modal.js) by any page that renders a
     trigger button with class="open-trip-requests-review" and the full set
     of data-* attributes it reads (see that JS file for the exact list).
     Whichever page renders the trigger is responsible for computing those
     attributes correctly for its own context. --}}
<div class="trip-payment-review-modal" id="tripRequestsReviewModal" aria-hidden="true">
    <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="tripRequestsReviewTitle">
        <div class="trip-payment-review-head">
            <div>
                <h3 class="trip-payment-review-title" id="tripRequestsReviewTitle">Manage requests</h3>
            </div>
            <button type="button" class="trip-payment-review-close" id="tripRequestsReviewClose" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="trip-payment-review-list" id="tripRequestsReviewList"></div>
    </div>
</div>

<div class="trip-payment-review-modal" id="tripRejectRequestModal" aria-hidden="true">
    <div class="trip-payment-review-card trip-reject-request-card" role="dialog" aria-modal="true" aria-labelledby="tripRejectRequestTitle">
        <div class="trip-payment-review-head">
            <div>
                <h3 class="trip-payment-review-title" id="tripRejectRequestTitle">Reject Request</h3>
                <p class="trip-payment-review-sub">State the reason why this join request is rejected.</p>
            </div>
            <button type="button" class="trip-payment-review-close" id="tripRejectRequestCloseTop" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="trip-payment-review-list">
            <div class="trip-secondary-grid" style="grid-template-columns: repeat(2, minmax(0,1fr));">
                <div class="trip-secondary-item">
                    <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Passenger</span>
                    <span class="trip-modal-value" id="tripRejectRequestPassenger">-</span>
                </div>
                <div class="trip-secondary-item">
                    <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Trip Ref</span>
                    <span class="trip-modal-value" id="tripRejectRequestTrip">-</span>
                </div>
            </div>
            <div>
                <label class="trip-modal-label trip-icon-label trip-reject-reason-label" for="tripRejectRequestReason">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#eab308;"></i>Rejection Reason
                </label>
                <textarea
                    class="trip-request-tool trip-reject-reason-input"
                    id="tripRejectRequestReason"
                    rows="4"
                    placeholder="Explain briefly why this request was rejected..."
                    required
                ></textarea>
            </div>
            <div class="trip-reject-request-actions">
                <button type="button" class="trip-action-btn" id="tripRejectRequestCancel">Cancel</button>
                <button type="button" class="trip-payment-review-btn danger trip-reject-request-confirm" id="tripRejectRequestConfirm">
                    <i class="fa-solid fa-xmark"></i> Reject Request
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Remove-participant reason modal — mirrors the reject-request modal above,
     but targets an already-approved passenger instead of a pending request. --}}
<div class="trip-payment-review-modal" id="tripRemoveParticipantModal" aria-hidden="true">
    <div class="trip-payment-review-card trip-reject-request-card" role="dialog" aria-modal="true" aria-labelledby="tripRemoveParticipantTitle">
        <div class="trip-payment-review-head">
            <div>
                <h3 class="trip-payment-review-title" id="tripRemoveParticipantTitle">Remove Passenger</h3>
                <p class="trip-payment-review-sub">State the reason why this passenger is being removed from the trip.</p>
            </div>
            <button type="button" class="trip-payment-review-close" id="tripRemoveParticipantCloseTop" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="trip-payment-review-list">
            <div class="trip-secondary-grid" style="grid-template-columns: repeat(2, minmax(0,1fr));">
                <div class="trip-secondary-item">
                    <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Passenger</span>
                    <span class="trip-modal-value" id="tripRemoveParticipantPassenger">-</span>
                </div>
                <div class="trip-secondary-item">
                    <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-hashtag"></i>Trip Ref</span>
                    <span class="trip-modal-value" id="tripRemoveParticipantTrip">-</span>
                </div>
            </div>
            <div>
                <label class="trip-modal-label trip-icon-label trip-reject-reason-label" for="tripRemoveParticipantReason">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#eab308;"></i>Removal Reason
                </label>
                <textarea
                    class="trip-request-tool trip-reject-reason-input"
                    id="tripRemoveParticipantReason"
                    rows="4"
                    placeholder="Explain briefly why this passenger is being removed..."
                    required
                ></textarea>
            </div>
            <div class="trip-reject-request-actions">
                <button type="button" class="trip-action-btn" id="tripRemoveParticipantCancel">Cancel</button>
                <button type="button" class="trip-payment-review-btn danger trip-reject-request-confirm" id="tripRemoveParticipantConfirm">
                    <i class="fa-solid fa-user-xmark"></i> Remove Passenger
                </button>
            </div>
        </div>
    </div>
</div>
