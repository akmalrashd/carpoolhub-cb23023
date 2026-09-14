{{-- "Rate Your Driver" — passenger-facing, public trips only. Reuses the
     same modal shell/classes as chats/partials/invite-modal.blade.php
     (trips.css) instead of duplicating that CSS. Driven by public/js/
     rate-trip-modal.js, opened via any .open-rate-trip-modal-btn trigger
     (Trip Details modal, trips-list row, the in-chat Hexa CTA) carrying
     data-trip-id/data-rate-url/data-driver-name/data-route-name. --}}
<div class="trip-payment-review-modal" id="rateTripModal" aria-hidden="true">
    <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="rateTripTitle">
        <div class="trip-payment-review-head">
            <div>
                <h3 class="trip-payment-review-title" id="rateTripTitle">Rate Your Driver</h3>
                <p class="trip-payment-review-sub" id="rateTripSub">-</p>
            </div>
            <button type="button" class="trip-payment-review-close" id="rateTripClose" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div style="padding:28px 16px; text-align:center;">
            <div class="rate-trip-stars" id="rateTripStars" data-value="0">
                @for($i = 1; $i <= 5; $i++)
                    <button type="button" class="rate-trip-star" data-star="{{ $i }}" aria-label="{{ $i }} star{{ $i > 1 ? 's' : '' }}">
                        <i class="fa-regular fa-star"></i>
                    </button>
                @endfor
            </div>
            <p class="rate-trip-hint" id="rateTripHint">Tap a star to rate</p>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px; padding:12px 16px 16px; border-top:1px solid var(--hairline);">
            <button type="button" class="btn btn-ghost btn-sm" id="rateTripCancel">Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" id="rateTripSubmit" disabled>Submit Rating</button>
        </div>
    </div>
</div>
