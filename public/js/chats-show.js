/* Per-thread logic only (message list, composer, polling, Ably, Trip
   Details header freshness) — everything that depends on which specific
   conversation is open. Re-executed on every chat switch by
   chat-thread-controller.js's mount() (a fresh <script> element re-runs
   even though the file was already loaded once), NOT just once at initial
   page load, since #chatMessages/#chatComposerForm/etc. are replaced along
   with the rest of the thread pane's markup each time.

   Because this file runs more than once per page view, every setInterval
   and the Ably connection below register their own teardown against
   window.__chatThreadSignal (a fresh AbortController the controller creates
   right before injecting each new thread's HTML+scripts) — without that,
   switching chats N times would leave N-1 stale pollers/connections still
   running in the background, each still fetching for a conversation that's
   no longer on screen. Element-specific listeners (composer form/input,
   attach button) don't need this: their elements are removed from the DOM
   by the same swap, which drops those listeners along with them.

   The invite-connections picker and the photo lightbox used to live in this
   file too, but both only ever needed to react to shell-level, load-once
   markup that survives a thread swap (see chat-invite-connections.js /
   chat-lightbox.js), so they were pulled out entirely rather than carrying
   this file's re-run/teardown complexity for no reason. */
(() => {
    const CFG = window.CH_CHAT;
    if (!CFG) return;

    const signal = window.__chatThreadSignal;

    const messagesEl = document.getElementById('chatMessages');
    const messagesInnerEl = document.getElementById('chatMessagesInner');
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
        if (!messagesEl) return;
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    // Initial mount only — chatUnreadSeparator (chats/partials/thread.blade.php)
    // marks where the messages that were still unread when this thread was
    // opened begin, computed server-side from the watermark captured before
    // ChatController::show()'s own markRead() call moves it. A chat with
    // several unread messages should land there (WhatsApp-style), not jump
    // straight to the very bottom and skip past the earlier ones.
    const scrollToUnreadOrBottom = () => {
        if (!messagesEl) return;
        const marker = document.getElementById('chatUnreadSeparator');
        if (marker) {
            messagesEl.scrollTop = Math.max(0, marker.offsetTop - messagesEl.clientHeight * 0.25);
            return;
        }
        scrollToBottom();
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
        if (!messagesInnerEl || renderedIds.has(Number(message.id))) return;
        renderedIds.add(Number(message.id));
        lastMessageId = Math.max(lastMessageId, Number(message.id));

        const dateKey = message.created_at ? dateKeyFor(message.created_at) : null;
        if (dateKey && dateKey !== lastDateKey) {
            lastDateKey = dateKey;
            messagesInnerEl.appendChild(buildDateSeparator(dateKey));
        }

        messagesInnerEl.appendChild(buildBubble(message));
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
        }, { signal });

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
        }, { signal });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form.requestSubmit();
            }
        }, { signal });
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
        attachBtn.addEventListener('click', () => attachInput.click(), { signal });

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
        }, { signal });
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
    const pollIntervalId = window.setInterval(poll, 25000);
    signal?.addEventListener('abort', () => window.clearInterval(pollIntervalId));

    // ── Trip Details / Manage Requests freshness ─────────────────────
    // Neither popup has its own live channel — they're just read off the
    // header trigger button's data-* attributes at the moment it's
    // clicked. Quietly refreshing that dataset in the background (instead
    // of touching trip-details-modal.js / trip-requests-modal.js, which
    // are shared with trips/index.blade.php) is enough to make both
    // popups reflect a join request, approval, or trip edit made
    // elsewhere without the viewer reloading this page first.
    const tripModalBtn = document.querySelector('.chat-thread-info.open-trip-modal-btn');
    let tripModalRefreshInFlight = false;
    async function refreshTripModalData() {
        if (!CFG.tripModalRefreshUrl || !tripModalBtn || tripModalRefreshInFlight || document.visibilityState !== 'visible') return;
        tripModalRefreshInFlight = true;
        try {
            const response = await fetch(CFG.tripModalRefreshUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            const data = payload.trip_modal_data;

            if (!data) {
                // The trip was cancelled/removed while this page was open —
                // same "nothing left to show" state the initial page load
                // handles by disabling the trigger entirely.
                tripModalBtn.disabled = true;
                return;
            }

            Object.entries(data).forEach(([key, value]) => {
                tripModalBtn.dataset[key] = value ?? '';
            });
        } catch {
            // silent — next tick tries again
        } finally {
            tripModalRefreshInFlight = false;
        }
    }
    if (tripModalBtn) {
        const tripModalIntervalId = window.setInterval(refreshTripModalData, 20000);
        signal?.addEventListener('abort', () => window.clearInterval(tripModalIntervalId));
    }

    // ── Ably realtime ────────────────────────────────────────────────
    function initAbly() {
        if (typeof Ably === 'undefined') return;

        const ably = new Ably.Realtime({
            authUrl: CFG.ablyTokenUrl,
            authMethod: 'GET',
            authParams: { conversation_id: CFG.conversationId },
        });

        ably.connection.on('failed', () => { /* poll() above still covers delivery */ });
        signal?.addEventListener('abort', () => ably.close());

        const channel = ably.channels.get(`private:conversation.${CFG.conversationId}`);
        channel.subscribe('message.sent', (msg) => appendMessage(msg.data));
    }
    initAbly();

    scrollToUnreadOrBottom();
})();
