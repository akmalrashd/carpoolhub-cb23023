(() => {
    const CFG = window.CH_CHAT;
    if (!CFG) return;

    const messagesEl = document.getElementById('chatMessages');
    const form = document.getElementById('chatComposerForm');
    const input = document.getElementById('chatComposerInput');
    const sendBtn = document.getElementById('chatComposerSend');
    const attachBtn = document.getElementById('chatAttachBtn');
    const attachInput = document.getElementById('chatAttachInput');

    const renderedIds = new Set();
    messagesEl?.querySelectorAll('[data-message-id]').forEach((el) => {
        renderedIds.add(Number(el.dataset.messageId));
    });
    let lastMessageId = Number(CFG.lastMessageId || 0);

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const scrollToBottom = () => {
        window.scrollTo({ top: document.body.scrollHeight });
    };

    // This app has no multi-timezone support — every viewer is assumed to
    // be in Malaysia (see Trip::TIMEZONE on the PHP side), so times/dates
    // are always rendered in this timezone regardless of the device's own
    // clock/locale settings.
    const MY_TZ = 'Asia/Kuala_Lumpur';

    const formatTime = (iso) => {
        try {
            return new Date(iso).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', timeZone: MY_TZ });
        } catch {
            return '';
        }
    };

    // Matches app/Support/ChatDateLabel.php's grouping so a message added
    // live (Ably/poll) gets the same "Today"/"Yesterday"/weekday/full-date
    // separator as the server-rendered history.
    const dateKeyFor = (iso) => {
        try {
            return new Date(iso).toLocaleDateString('en-CA', { timeZone: MY_TZ });
        } catch {
            return null;
        }
    };
    const daysBetween = (fromKey, toKey) => Math.round(
        (new Date(`${toKey}T00:00:00Z`).getTime() - new Date(`${fromKey}T00:00:00Z`).getTime()) / 86400000
    );
    const dateLabelFor = (dateKey) => {
        const todayKey = new Date().toLocaleDateString('en-CA', { timeZone: MY_TZ });
        const daysAgo = daysBetween(dateKey, todayKey);
        const asUtcNoon = new Date(`${dateKey}T12:00:00Z`);
        if (daysAgo === 0) return 'Today';
        if (daysAgo === 1) return 'Yesterday';
        if (daysAgo > 1 && daysAgo < 7) return asUtcNoon.toLocaleDateString('en-US', { weekday: 'long', timeZone: 'UTC' });
        return asUtcNoon.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
    };
    const buildDateSeparator = (dateKey) => {
        const row = document.createElement('div');
        row.className = 'chat-date-separator';
        row.dataset.dateKey = dateKey;
        row.innerHTML = `<span>${escapeHtml(dateLabelFor(dateKey))}</span>`;
        return row;
    };
    let lastDateKey = CFG.lastDateKey || null;

    function buildBubble(message) {
        if (message.type === 'system') {
            const row = document.createElement('div');
            row.className = 'chat-bubble-row is-system';
            row.dataset.messageId = String(message.id);
            row.innerHTML = `<span class="chat-bubble-system">${escapeHtml(message.body)}</span>`;
            return row;
        }

        const isOwn = Number(message.sender_id) === Number(CFG.myUserId);
        const row = document.createElement('div');
        row.className = `chat-bubble-row ${isOwn ? 'is-own' : ''}`;
        row.dataset.messageId = String(message.id);

        const col = document.createElement('div');
        col.className = 'chat-bubble-col';

        if (message.type === 'image') {
            const link = document.createElement('button');
            link.type = 'button';
            link.className = 'chat-bubble-image-link';
            link.dataset.lightboxSrc = message.body;
            const img = document.createElement('img');
            img.className = 'chat-bubble-image';
            img.src = message.body;
            img.alt = 'Photo';
            link.appendChild(img);
            col.appendChild(link);
        } else {
            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble';
            bubble.textContent = message.body;
            col.appendChild(bubble);
        }

        const time = document.createElement('span');
        time.className = 'chat-bubble-time';
        time.textContent = formatTime(message.created_at);

        col.appendChild(time);
        row.appendChild(col);

        return row;
    }

    function appendMessage(message) {
        if (!messagesEl || renderedIds.has(Number(message.id))) return;
        renderedIds.add(Number(message.id));
        lastMessageId = Math.max(lastMessageId, Number(message.id));

        const dateKey = message.created_at ? dateKeyFor(message.created_at) : null;
        if (dateKey && dateKey !== lastDateKey) {
            lastDateKey = dateKey;
            messagesEl.appendChild(buildDateSeparator(dateKey));
        }

        messagesEl.appendChild(buildBubble(message));
        scrollToBottom();
        markReadIfVisible();
    }

    let markReadInFlight = false;
    function markReadIfVisible() {
        if (document.visibilityState !== 'visible' || markReadInFlight) return;
        markReadInFlight = true;
        fetch(CFG.readUrl, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CFG.csrf, Accept: 'application/json' },
        }).catch(() => {}).finally(() => { markReadInFlight = false; });
    }

    // ── Composer ─────────────────────────────────────────────────────
    async function sendPayload(body, type) {
        const response = await fetch(CFG.sendUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CFG.csrf,
                Accept: 'application/json',
            },
            body: JSON.stringify({ body, type }),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(payload.message || 'Could not send message.');
        }
        if (payload.message) appendMessage(payload.message);
    }

    if (form && input && sendBtn) {
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 96) + 'px';
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const body = input.value.trim();
            if (!body) return;

            sendBtn.disabled = true;
            try {
                await sendPayload(body, 'text');
                input.value = '';
                input.style.height = '';
            } catch (error) {
                window.showToast ? window.showToast(error.message, 'error') : alert(error.message);
            } finally {
                sendBtn.disabled = false;
                input.focus();
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form.requestSubmit();
            }
        });
    }

    // ── Photo attachment ─────────────────────────────────────────────
    // Resized + re-encoded client-side before it ever leaves the browser —
    // this is a chat for "which car / where are you" identification, not a
    // photo gallery, and the compressed data URI has to clear both this
    // app's own messages.body storage and Ably's per-message size limit.
    const MAX_IMAGE_DIMENSION = 640;
    const IMAGE_QUALITY = 0.55;

    function compressImageFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onerror = () => reject(new Error('Could not read the photo.'));
            reader.onload = () => {
                const img = new Image();
                img.onerror = () => reject(new Error('Could not read the photo.'));
                img.onload = () => {
                    const scale = Math.min(1, MAX_IMAGE_DIMENSION / Math.max(img.width, img.height));
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.round(img.width * scale);
                    canvas.height = Math.round(img.height * scale);
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    resolve(canvas.toDataURL('image/jpeg', IMAGE_QUALITY));
                };
                img.src = String(reader.result);
            };
            reader.readAsDataURL(file);
        });
    }

    if (attachBtn && attachInput) {
        attachBtn.addEventListener('click', () => attachInput.click());

        attachInput.addEventListener('change', async () => {
            const file = attachInput.files && attachInput.files[0];
            attachInput.value = '';
            if (!file) return;

            attachBtn.disabled = true;
            try {
                const dataUrl = await compressImageFile(file);
                await sendPayload(dataUrl, 'image');
            } catch (error) {
                window.showToast ? window.showToast(error.message, 'error') : alert(error.message);
            } finally {
                attachBtn.disabled = false;
            }
        });
    }

    // ── Polling fallback (catches anything a dropped Ably connection missed) ──
    let pollInFlight = false;
    async function poll() {
        if (pollInFlight || document.visibilityState !== 'visible') return;
        pollInFlight = true;
        try {
            const response = await fetch(`${CFG.pollUrl}?after_id=${lastMessageId}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            (payload.messages || []).forEach(appendMessage);
        } catch {
            // silent — next tick tries again
        } finally {
            pollInFlight = false;
        }
    }
    window.setInterval(poll, 25000);

    // ── Ably realtime ────────────────────────────────────────────────
    function initAbly() {
        if (typeof Ably === 'undefined') return;

        const ably = new Ably.Realtime({
            authUrl: CFG.ablyTokenUrl,
            authMethod: 'GET',
            authParams: { conversation_id: CFG.conversationId },
        });

        ably.connection.on('failed', () => { /* poll() above still covers delivery */ });

        const channel = ably.channels.get(`private:conversation.${CFG.conversationId}`);
        channel.subscribe('message.sent', (msg) => appendMessage(msg.data));
    }
    initAbly();

    // ── Invite from connections (private trip groups only) ──────────
    const inviteBtn = document.getElementById('chatInviteBtn');
    if (inviteBtn && CFG.pickerOptionsUrl && CFG.inviteUrl) {
        inviteBtn.addEventListener('click', async () => {
            let options = [];
            try {
                const response = await fetch(CFG.pickerOptionsUrl, { headers: { Accept: 'application/json' } });
                const payload = await response.json();
                options = payload.connections || [];
            } catch {
                alert('Could not load your connections.');
                return;
            }

            if (options.length === 0) {
                alert('You have no accepted connections to invite.');
                return;
            }

            const names = options.map((o) => o.name);
            const picked = prompt(`Invite who? Type name(s) separated by commas:\n${names.join(', ')}`);
            if (!picked) return;

            const pickedNames = picked.split(',').map((n) => n.trim().toLowerCase()).filter(Boolean);
            const ids = options
                .filter((o) => pickedNames.includes(o.name.toLowerCase()))
                .map((o) => o.id);

            if (ids.length === 0) {
                alert('No matching connection names.');
                return;
            }

            const form2 = document.createElement('form');
            form2.method = 'POST';
            form2.action = CFG.inviteUrl;
            form2.innerHTML = `<input type="hidden" name="_token" value="${escapeHtml(CFG.csrf)}">`
                + ids.map((id) => `<input type="hidden" name="connection_user_ids[]" value="${id}">`).join('');
            document.body.appendChild(form2);
            form2.submit();
        });
    }

    // ── Photo lightbox — pinch-zoom + pan, tap backdrop to close ─────
    // Opens in-page rather than navigating to the data: URI directly:
    // several mobile browsers show a blank page for a top-level
    // navigation to a data: URI, and this app's global viewport meta
    // disables native pinch-zoom (see pwa-head.blade.php), so zooming
    // here is done by hand via touch tracking instead of relying on it.
    const lightbox = document.getElementById('chatLightbox');
    const lightboxViewport = document.getElementById('chatLightboxViewport');
    const lightboxImg = document.getElementById('chatLightboxImg');

    if (lightbox && lightboxViewport && lightboxImg) {
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
            lightboxImg.style.transform = `translate(${originX}px, ${originY}px) scale(${scale})`;
        };

        const resetTransform = () => {
            scale = 1;
            originX = 0;
            originY = 0;
            applyTransform();
        };

        const openLightbox = (src) => {
            lightboxImg.src = src;
            resetTransform();
            lightbox.classList.add('is-open');
            lightbox.setAttribute('aria-hidden', 'false');
        };

        const closeLightbox = () => {
            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden', 'true');
            lightboxImg.src = '';
        };

        messagesEl?.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-lightbox-src]');
            if (!trigger) return;
            openLightbox(trigger.dataset.lightboxSrc);
        });

        // Tapping the dark backdrop (viewport minus the image itself) closes
        // it — a tap that lands on the image is a zoom gesture, not a close.
        lightboxViewport.addEventListener('click', (event) => {
            if (event.target === lightboxImg) return;
            closeLightbox();
        });

        const touchDistance = (touches) => {
            const dx = touches[0].clientX - touches[1].clientX;
            const dy = touches[0].clientY - touches[1].clientY;
            return Math.hypot(dx, dy);
        };

        lightboxViewport.addEventListener('touchstart', (event) => {
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

        lightboxViewport.addEventListener('touchmove', (event) => {
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

        lightboxViewport.addEventListener('touchend', (event) => {
            if (event.touches.length < 2) pinchStartDistance = 0;
            if (event.touches.length === 0) isPanning = false;
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && lightbox.classList.contains('is-open')) closeLightbox();
        });
    }

    scrollToBottom();
})();
