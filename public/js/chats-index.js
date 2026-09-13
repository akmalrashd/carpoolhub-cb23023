(() => {
    const CFG = window.CH_CHATS_INDEX;
    if (!CFG) return;

    const list = document.getElementById('chatList');
    if (!list) return;

    // On the chat THREAD page (desktop split-view's left pane), mobile CSS
    // hides this whole pane in favour of the full-screen thread — skip
    // subscribing/polling there entirely rather than holding an Ably
    // connection and a background poll for a pane nobody can see.
    if (list.offsetParent === null) return;

    const rowSelector = (id) => `.chat-row[data-conversation-id="${CSS.escape(String(id))}"]`;

    function replaceOrInsertRow(id, html) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const newRow = wrapper.firstElementChild;
        if (!newRow) return;

        // Empty-state markup (no rows yet) has no .chat-row to replace —
        // clear it out the first time a real row shows up.
        list.querySelector('.ch-empty-state-card')?.remove();

        list.querySelector(rowSelector(id))?.remove();
        // Conversations are ordered by latest activity — the row that just
        // changed always belongs at the top, same as a full refetch would
        // have placed it.
        list.prepend(newRow);
    }

    let rowRefreshInFlight = new Set();
    async function refreshRow(id) {
        if (rowRefreshInFlight.has(id)) return;
        rowRefreshInFlight.add(id);
        try {
            const response = await fetch(CFG.rowUrlTemplate.replace('__ID__', id), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            replaceOrInsertRow(id, payload.html);
        } catch {
            // silent — the periodic full-list poll below still covers it
        } finally {
            rowRefreshInFlight.delete(id);
        }
    }

    // ── Polling fallback — also the only path that notices a conversation
    // this page never subscribed to yet (a brand new one created while the
    // list was already open), since Ably subscriptions below are seeded
    // once from whatever rows rendered at page load. ──
    let listRefreshInFlight = false;
    async function refreshList() {
        if (listRefreshInFlight || document.visibilityState !== 'visible') return;
        listRefreshInFlight = true;
        try {
            const response = await fetch(CFG.listUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            list.innerHTML = payload.html;
            subscribeToVisibleRows();
        } catch {
            // silent — next tick tries again
        } finally {
            listRefreshInFlight = false;
        }
    }
    window.setInterval(refreshList, 25000);

    // ── Ably realtime — instant per-row updates ──────────────────────
    let ably = null;
    const subscribedIds = new Set();

    function subscribeToVisibleRows() {
        // The list started empty (no connection made yet) and a row has
        // since appeared via the poll fallback — connect now instead of
        // leaving that conversation without live coverage until reload.
        if (!ably) { initAbly(); return; }
        list.querySelectorAll('.chat-row[data-conversation-id]').forEach((row) => {
            const id = row.dataset.conversationId;
            if (subscribedIds.has(id)) return;
            subscribedIds.add(id);
            // The channel name is still keyed by the internal sequential id
            // (that's what the server actually broadcasts on) — only the
            // refresh URL above needs the route-facing public id.
            const channel = ably.channels.get(`private:conversation.${row.dataset.conversationChannelId}`);
            channel.subscribe('message.sent', () => refreshRow(id));
        });
    }

    function initAbly() {
        // Nothing to subscribe to yet (empty chat list) — skip connecting
        // at all rather than requesting a token the server would 404 on.
        if (typeof Ably === 'undefined' || !list.querySelector('.chat-row[data-conversation-id]')) return;

        ably = new Ably.Realtime({
            authUrl: CFG.ablyTokenUrl,
            authMethod: 'GET',
        });

        ably.connection.on('failed', () => { /* refreshList() poll above still covers delivery */ });

        subscribeToVisibleRows();
    }
    initAbly();

    // ── Search filter — plain client-side text match, same "hide rows
    // that don't match" pattern used by the Manage Requests search box. ──
    const searchInput = document.getElementById('chatSearchInput');
    searchInput?.addEventListener('input', () => {
        const term = searchInput.value.trim().toLowerCase();
        list.querySelectorAll('.chat-row').forEach((row) => {
            row.classList.toggle('chat-row-hidden', Boolean(term) && !row.textContent.toLowerCase().includes(term));
        });
    });
})();
