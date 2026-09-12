(() => {
    const CFG = window.CH_CHAT;
    if (!CFG) return;

    const messagesEl = document.getElementById('chatMessages');
    const form = document.getElementById('chatComposerForm');
    const input = document.getElementById('chatComposerInput');
    const sendBtn = document.getElementById('chatComposerSend');

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

    const formatTime = (iso) => {
        try {
            return new Date(iso).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        } catch {
            return '';
        }
    };

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

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.textContent = message.body;

        const time = document.createElement('span');
        time.className = 'chat-bubble-time';
        time.textContent = formatTime(message.created_at);

        col.appendChild(bubble);
        col.appendChild(time);
        row.appendChild(col);

        return row;
    }

    function appendMessage(message) {
        if (!messagesEl || renderedIds.has(Number(message.id))) return;
        renderedIds.add(Number(message.id));
        lastMessageId = Math.max(lastMessageId, Number(message.id));
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
                const response = await fetch(CFG.sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CFG.csrf,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ body }),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(payload.message || 'Could not send message.');
                }
                input.value = '';
                input.style.height = '';
                if (payload.message) appendMessage(payload.message);
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

    scrollToBottom();
})();
