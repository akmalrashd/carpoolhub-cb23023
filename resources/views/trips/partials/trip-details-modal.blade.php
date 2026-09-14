{{-- Shared "Trip Details" popup — originally trips/index.blade.php only.
     Reused as-is (same ids/classes, same public/js/trip-details-modal.js)
     by any page that renders a trigger button with class="open-trip-modal-btn"
     and the full set of data-* attributes it reads (see that JS file for the
     exact list). Whichever page renders the trigger is responsible for
     computing those attributes correctly for its own context. --}}
<div class="trip-modal" id="tripDetailsModal" aria-hidden="true">
    <div class="trip-modal-card">
        <div class="trip-modal-head">
            <div class="trip-modal-head-text">
                <h3 class="trip-modal-title">Trip Details</h3>
                <span class="trip-status-badge" id="tripModalStatus">-</span>
            </div>
            <button type="button" class="trip-modal-close" id="tripDetailsCloseBtn" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="trip-modal-scroll">
            <div class="trip-modal-grid">
                <div class="trip-meta-line">
                    <span class="trip-meta-item"><i class="fa-solid fa-hashtag"></i><span id="tripModalTripIds">-</span></span>
                    <span class="trip-meta-dot">&middot;</span>
                    <span class="trip-meta-item"><i class="fa-regular fa-calendar"></i><span id="tripModalOutboundTime">-</span></span>
                    <span class="trip-meta-dot">&middot;</span>
                    <span class="trip-meta-item trip-meta-route"><i class="fa-solid fa-road"></i><span class="trip-meta-route-text" id="tripModalRouteName">-</span></span>
                </div>

                <div class="trip-route-card">
                    <div class="trip-modal-map" id="tripModalMap"></div>
                    <div class="trip-route-timeline">
                        <div class="trip-route-point">
                            <span class="trip-route-dot pickup"></span>
                            <span class="trip-route-text">
                                <span class="trip-route-label" id="tripModalPointALabel">Pickup Point</span>
                                <span class="trip-route-value" id="tripModalPickupPoint">-</span>
                            </span>
                        </div>
                        <div class="trip-route-point">
                            <span class="trip-route-dot destination"></span>
                            <span class="trip-route-text">
                                <span class="trip-route-label" id="tripModalPointBLabel">Destination Point</span>
                                <span class="trip-route-value" id="tripModalDestinationPoint">-</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="trip-driver-card">
                    <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user"></i>Driver</span>
                    <span class="trip-driver-row">
                        <span class="trip-modal-driver-avatar" id="tripModalDriverAvatar">D</span>
                        <span class="trip-modal-driver-meta">
                            <span class="trip-modal-driver-name" id="tripModalDriver">-</span>
                            <span class="trip-modal-driver-email" id="tripModalDriverEmail">-</span>
                        </span>
                    </span>
                </div>

                <div class="trip-passenger-card">
                    <div class="trip-passenger-header">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-users"></i>Passengers</span>
                        <span class="trip-passenger-count" id="tripModalPassengerCount">0 passengers</span>
                    </div>
                    <div class="trip-passenger-list" id="tripModalPassengerList"></div>
                </div>

                <div class="trip-secondary-grid">
                    <div class="trip-secondary-item">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-route"></i>Trip Type</span>
                        <span class="trip-modal-value" id="tripModalMode">-</span>
                    </div>
                    <div class="trip-secondary-item">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-user-group"></i>Total Passengers</span>
                        <span class="trip-modal-value" id="tripModalTotalPassengers">-</span>
                    </div>
                    <div class="trip-secondary-item">
                        <span class="trip-modal-label trip-icon-label"><i class="fa-solid fa-scale-balanced"></i>Fare Split Type</span>
                        <span class="trip-modal-value" id="tripModalSplitType">-</span>
                    </div>
                </div>

                <div class="trip-fare-highlight">
                    <span class="trip-fare-highlight-label"><i class="fa-solid fa-wallet"></i><span id="tripModalFareLabel">Fare</span></span>
                    <span class="trip-fare-highlight-value" id="tripModalFareValue">-</span>
                </div>
            </div>
        </div>
        <div class="trip-contact-bar">
            <div class="trip-actions-filled" id="tripModalManageActions" style="display:none;">
                <button type="button" class="trip-action-btn is-filled requests-btn open-trip-requests-review" id="tripModalRequestsBtn" title="Manage requests" style="display:none;">
                    <i class="fa-solid fa-inbox"></i> Requests
                </button>
                <a href="#" class="trip-action-btn is-filled edit-btn" id="tripModalEditBtn">
                    <i class="fa-regular fa-pen-to-square"></i> Edit
                </a>
                <form method="POST" id="tripModalDeleteForm" class="trip-action-form" onsubmit="return confirmTripCancel(this);">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="reason">
                    <button type="submit" class="trip-action-btn is-filled delete-btn">
                        <i class="fa-regular fa-trash-can"></i> Delete
                    </button>
                </form>
            </div>
            <div class="trip-actions-filled" id="tripModalContactActions" style="display:none;">
                {{-- Public trips: everyone stays in the monitored in-app chat, no
                     external fallback — see the Hexa welcome message's own "keep
                     it inside this chat" tip. Disabled + a toast (not hidden) once
                     the chat has been purged, since the trip itself can outlive it. --}}
                <div class="trip-contact-row" id="tripModalChatWrap">
                    <p class="trip-contact-row-label" id="tripModalChatNote">
                        <span id="tripModalChatNoteText">Keep everything about this trip inside the chat so it stays safe and easy to track.</span>
                    </p>
                    <a href="#" class="trip-action-btn is-filled chat-btn" id="tripModalChat">
                        <i class="fa-solid fa-comment-dots"></i> Chat
                    </a>
                </div>
                {{-- Private trips: passengers are hand-picked from the driver's own
                     Connections, so the "keep everything monitored" bar doesn't
                     apply the same way — WhatsApp/Email direct contact stays, with
                     a disclaimer since CarpoolHub has no record of it. --}}
                <div class="trip-contact-row" id="tripModalExternalContact">
                    <p class="trip-contact-row-label">
                        This is a private trip, so you can contact the driver directly here since they're already one of your trusted Connections.
                    </p>
                    <div class="trip-contact-row-icons">
                        <a href="#" class="trip-action-btn is-filled email-btn icon-only" id="tripModalEmail" title="Email">
                            <i class="fa-regular fa-envelope"></i>
                        </a>
                        <a href="#" target="_blank" rel="noopener" class="trip-action-btn is-filled whatsapp-btn icon-only" id="tripModalWhatsapp" title="WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
