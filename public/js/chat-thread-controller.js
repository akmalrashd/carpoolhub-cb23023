/* Swaps the chat thread pane in place (WhatsApp Web style) instead of a
   full page navigation every time a different chat is opened. Owns the
   single #chatThreadMount container that both chats/index.blade.php (starts
   with the "Select a chat" placeholder) and chats/show.blade.php (starts
   with a real thread) render into.

   Must load BEFORE the initial thread's own inline <script>/<script src>
   tags (chats-show.js etc.) — see window.__chatThreadSignal below — so put
   its <script src> ahead of @include('chats.partials.thread', ...) in both
   shell views. */
window.CarpoolChatThread = (() => {
    // Every setInterval / Ably connection / document-level listener that a
    // per-thread script (chats-show.js) registers is tied to this signal,
    // so replacing it here is the entire teardown for whatever the
    // previous thread's scripts started — see chats-show.js's own header
    // comment. Created immediately (not just inside mount()) so the very
    // first thread rendered by the server on initial page load is covered
    // too, not just ones swapped in afterwards.
    let currentAbort = new AbortController();
    window.__chatThreadSignal = currentAbort.signal;

    const mountEl = () => document.getElementById('chatThreadMount');

    // Setting .innerHTML does not execute embedded <script> tags — creating
    // fresh <script> elements and appending them does, every time,
    // regardless of whether the browser already ran that same src before.
    // That's what actually re-initializes chats-show.js (and, on first
    // paint of the page, the inline window.CH_CHAT config + ?open_rate=1
    // auto-open snippet) against the freshly-swapped DOM.
    function runScripts(container) {
        Array.from(container.querySelectorAll('script')).forEach((old) => {
            const fresh = document.createElement('script');
            Array.from(old.attributes).forEach((attr) => fresh.setAttribute(attr.name, attr.value));
            if (!old.src) fresh.textContent = old.textContent;
            old.replaceWith(fresh);
        });
    }

    function clearUnreadOn(url) {
        let path;
        try {
            path = new URL(url, window.location.origin).pathname;
        } catch {
            return;
        }
        document.querySelectorAll('.chat-row').forEach((row) => {
            let rowPath;
            try {
                rowPath = new URL(row.href).pathname;
            } catch {
                return;
            }
            if (rowPath !== path) return;
            row.classList.remove('is-unread');
            row.querySelector('.chat-row-unread-dot')?.remove();
        });
    }

    function highlightActiveRow(url) {
        let path;
        try {
            path = new URL(url, window.location.origin).pathname;
        } catch {
            return;
        }
        document.querySelectorAll('.chat-row').forEach((row) => {
            let rowPath;
            try {
                rowPath = new URL(row.href).pathname;
            } catch {
                return;
            }
            row.classList.toggle('is-active', rowPath === path);
        });
    }

    // One retry before giving up — a transient failure on the first attempt
    // (a dropped connection, a momentary server hiccup) is otherwise enough
    // to fall all the way back to a full page navigation for what's usually
    // a one-off blip; a near-immediate second attempt clears it in practice
    // far more often than it doesn't, keeping the swap seamless instead of
    // visibly degrading on the first sign of trouble. `signal` aborts BOTH
    // attempts at once the moment a newer mount() supersedes this one (see
    // mount() below) — without it, clicking through several chats quickly
    // leaves every earlier click's fetch running in the background, each
    // free to finish later and stomp the newer one's result.
    async function fetchThreadHtml(url, signal) {
        for (let attempt = 0; attempt < 2; attempt++) {
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal,
                });
                if (!response.ok) continue;
                const payload = await response.json();
                if (typeof payload.html === 'string') return payload.html;
            } catch (error) {
                if (error?.name === 'AbortError') return null;
                // otherwise try again below, or fall through to null after the last attempt
            }
        }
        return null;
    }

    async function mount(url, { push = true } = {}) {
        const mount = mountEl();
        if (!mount) {
            window.location.href = url;
            return;
        }

        // Superseding a mount already in flight (clicking a second chat
        // before the first one's fetch has resolved) aborts that one's
        // fetch outright — see fetchThreadHtml's comment — and this mount's
        // own signal is what a later, even-newer mount() will abort in
        // turn. window.__chatThreadSignal is the same signal so the
        // thread's own scripts (chats-show.js) tear down on exactly this
        // same "superseded" event, not a separate one.
        currentAbort.abort();
        currentAbort = new AbortController();
        const mySignal = currentAbort.signal;
        window.__chatThreadSignal = mySignal;

        mount.classList.add('is-loading');
        const html = await fetchThreadHtml(url, mySignal);
        if (mySignal.aborted) return;
        mount.classList.remove('is-loading');

        if (typeof html !== 'string') { window.location.href = url; return; }

        mount.innerHTML = html;
        runScripts(mount);

        if (push) history.pushState({ chatThreadUrl: url }, '', url);
        highlightActiveRow(url);
        clearUnreadOn(url);
    }

    window.addEventListener('popstate', () => {
        mount(window.location.href, { push: false });
    });

    return { mount };
})();
