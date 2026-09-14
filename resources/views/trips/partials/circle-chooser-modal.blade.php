{{-- "Start Group Chat" chooser — a driver with zero circles still opens this
     modal now (see public/js/circle-chooser-modal.js), just straight into
     its name-entry view, since the browser's native prompt() looked out of
     place next to the rest of the app's themed UI. Reuses the same modal
     shell/classes as trip-requests-modal.blade.php (trips.css) instead of
     duplicating that CSS. Content is fetched live via trips.chat.
     circle-options when opened — this page only ever has one trigger
     button, unlike the per-trip-row trip-requests modal, so there's no need
     to bake a payload into data-* attributes ahead of time. --}}
<div class="trip-payment-review-modal" id="circleChooserModal" aria-hidden="true">
    <div class="trip-payment-review-card" role="dialog" aria-modal="true" aria-labelledby="circleChooserTitle">
        <div class="trip-payment-review-head">
            <div>
                <h3 class="trip-payment-review-title" id="circleChooserTitle">Start Group Chat</h3>
                <p class="trip-payment-review-sub" id="circleChooserSub">Reuse a circle you've chatted with before, or start a new one.</p>
            </div>
            <button type="button" class="trip-payment-review-close" id="circleChooserClose" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="trip-payment-review-list" id="circleChooserList"></div>
    </div>
</div>
