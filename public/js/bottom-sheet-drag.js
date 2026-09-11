/* Drag-to-dismiss for the app's mobile bottom-sheet modals — every popup
   that switches to a translateY(100%)<->translateY(0) sheet with a pill
   "grabber" under some max-width breakpoint (trips, explore, payments,
   admin, etc.) shares this one implementation instead of each modal's own
   JS re-rolling the same pointer math.

   Usage: window.CarpoolBottomSheet.enable({ modal, card, head, closeFn })
   - modal: the fixed overlay element (fades via style.opacity while dragging)
   - card: the sheet itself (moves via style.transform while dragging)
   - head: the drag surface — normally the header row the grabber sits over;
     dragging never starts on a button/link/input inside it, so a close
     button or any other control in the header still works normally
   - closeFn: called when the drag crosses the distance or velocity
     threshold; ownership of what "closed" means (classList, aria-hidden,
     body scroll lock, ...) stays with the caller, same as clicking the X

   On release, control is handed straight back to the CSS transition that
   already drives the modal's own open/close animation — there is no
   separate hand-rolled closing animation to keep in sync with it. */
(function () {
    'use strict';

    function enable(options) {
        const modal = options.modal;
        const card = options.card;
        const head = options.head;
        const closeFn = options.closeFn;
        if (!modal || !card || !head || typeof closeFn !== 'function') return;

        const breakpoint = options.breakpoint || 767;
        const dismissDistance = options.dismissDistance || 120;
        const dismissVelocity = options.dismissVelocity || 0.5;
        const maxFadeDistance = options.maxFadeDistance || 400;
        const isMobileSheet = () => window.matchMedia(`(max-width: ${breakpoint}px)`).matches;

        let dragging = false;
        let dragPointerId = null;
        let startY = 0;
        let deltaY = 0;
        let startedAt = 0;

        const endDrag = () => {
            if (!dragging) return;
            dragging = false;
            card.style.transition = '';
            modal.style.transition = '';
            card.style.transform = '';
            modal.style.opacity = '';

            const elapsed = Math.max(1, Date.now() - startedAt);
            const velocity = deltaY / elapsed;
            if (deltaY > dismissDistance || velocity > dismissVelocity) {
                closeFn();
            }
        };

        head.addEventListener('pointerdown', (event) => {
            if (!isMobileSheet() || event.target.closest('button, a, input, select, textarea')) return;
            dragging = true;
            dragPointerId = event.pointerId;
            startY = event.clientY;
            deltaY = 0;
            startedAt = Date.now();
            card.style.transition = 'none';
            modal.style.transition = 'none';
            head.setPointerCapture?.(event.pointerId);
        });
        head.addEventListener('pointermove', (event) => {
            if (!dragging || event.pointerId !== dragPointerId) return;
            deltaY = Math.max(0, event.clientY - startY);
            card.style.transform = `translateY(${deltaY}px)`;
            modal.style.opacity = String(Math.max(0, 1 - deltaY / maxFadeDistance));
        });
        head.addEventListener('pointerup', endDrag);
        head.addEventListener('pointercancel', endDrag);
    }

    window.CarpoolBottomSheet = { enable: enable };
})();
