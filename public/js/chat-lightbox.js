/* Photo lightbox — lives at the shell level (see chat-invite-connections.js's
   header comment for why: this modal's own markup is rendered once outside
   the swappable thread partial, so its triggers — [data-lightbox-src]
   bubbles, which live INSIDE messages that get replaced on every chat
   switch — are matched by document-level delegation instead of binding to
   a specific #chatMessages element that won't exist past the next swap.
   Opens in-page rather than navigating to the data: URI directly: several
   mobile browsers show a blank page for a top-level navigation to a data:
   URI, and this app's global viewport meta disables native pinch-zoom, so
   zooming here is done by hand via touch tracking instead of relying on it. */
(() => {
    const lightbox = document.getElementById('chatLightbox');
    const viewport = document.getElementById('chatLightboxViewport');
    const img = document.getElementById('chatLightboxImg');
    if (!lightbox || !viewport || !img) return;

    let scale = 1;
    let originX = 0;
    let originY = 0;
    let pinchStartDistance = 0;
    let pinchStartScale = 1;
    let panStartX = 0;
    let panStartY = 0;
    let panOriginX = 0;
    let panOriginY = 0;
    let isPanning = false;

    const applyTransform = () => {
        img.style.transform = `translate(${originX}px, ${originY}px) scale(${scale})`;
    };

    const resetTransform = () => {
        scale = 1;
        originX = 0;
        originY = 0;
        applyTransform();
    };

    const open = (src) => {
        img.src = src;
        resetTransform();
        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');
    };

    const close = () => {
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        img.src = '';
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element ? event.target.closest('[data-lightbox-src]') : null;
        if (!trigger) return;
        open(trigger.dataset.lightboxSrc);
    });

    // Tapping the dark backdrop (viewport minus the image itself) closes it
    // — a tap that lands on the image is a zoom gesture, not a close.
    viewport.addEventListener('click', (event) => {
        if (event.target === img) return;
        close();
    });

    const touchDistance = (touches) => {
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.hypot(dx, dy);
    };

    viewport.addEventListener('touchstart', (event) => {
        if (event.touches.length === 2) {
            pinchStartDistance = touchDistance(event.touches);
            pinchStartScale = scale;
            isPanning = false;
        } else if (event.touches.length === 1 && scale > 1) {
            isPanning = true;
            panStartX = event.touches[0].clientX;
            panStartY = event.touches[0].clientY;
            panOriginX = originX;
            panOriginY = originY;
        }
    }, { passive: true });

    viewport.addEventListener('touchmove', (event) => {
        if (event.touches.length === 2) {
            event.preventDefault();
            const distance = touchDistance(event.touches);
            if (pinchStartDistance > 0) {
                scale = Math.min(4, Math.max(1, pinchStartScale * (distance / pinchStartDistance)));
                if (scale === 1) { originX = 0; originY = 0; }
                applyTransform();
            }
        } else if (event.touches.length === 1 && isPanning) {
            event.preventDefault();
            originX = panOriginX + (event.touches[0].clientX - panStartX);
            originY = panOriginY + (event.touches[0].clientY - panStartY);
            applyTransform();
        }
    }, { passive: false });

    viewport.addEventListener('touchend', (event) => {
        if (event.touches.length < 2) pinchStartDistance = 0;
        if (event.touches.length === 0) isPanning = false;
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && lightbox.classList.contains('is-open')) close();
    });
})();
